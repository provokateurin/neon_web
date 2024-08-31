<?php

declare(strict_types=1);

namespace OCA\Neon\Controller;

use Exception;
use OC;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\Neon\AppInfo\Application;
use OCA\Neon\HtmlResponse;
use OCA\Neon\StaticResponse;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;

/** @psalm-suppress UnusedClass */
class PageController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		protected IAppManager $appManager,
		protected ContentSecurityPolicyNonceManager $nonceManager,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[PublicPage]
	public function index(): HtmlResponse {
		$cacheBusterValue = $this->config->getAppValue('theming', 'cachebuster', '0');
		$iconUrl = $this->urlGenerator->linkToRoute('theming.Icon.getFavicon', ['app' => Application::APP_ID]) . '?v=' . $cacheBusterValue;

		$response = new HtmlResponse(
			'
<html>
	<head>
		<title>Neon</title>
		<link rel="icon" href="' . $iconUrl . '">
	</head>
	<body style="margin: 0">
		<iframe src="static/index.html" style="border: 0; width: 100%; height: 100%" />
	</body>
</html>
',
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain("'self'");
		$response->setContentSecurityPolicy($csp);

		return $response;
	}

	/**
	 * @throws AppPathNotFoundException
	 * @throws Exception
	 */
	#[NoCSRFRequired]
	#[PublicPage]
	public function static(string $path): NotFoundResponse|StaticResponse {
		$baseDir = $this->appManager->getAppPath(Application::APP_ID);
		// This is safe to path traversals as any /static/../ would no longer be handled by this route.
		$file = $baseDir . '/static/' . $path;
		if (!file_exists($file)) {
			return new NotFoundResponse();
		}

		$nonce = $this->nonceManager->getNonce();
		$response = new StaticResponse(
			$file,
			OC::$WEBROOT . '/index.php/apps/' . Application::APP_ID,
			$nonce,
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameAncestorDomain("'self'");
		$csp->addAllowedFrameAncestorDomain("'self'");
		$csp->addAllowedScriptDomain("'unsafe-eval'");
		$csp->addAllowedScriptDomain("'unsafe-inline'");
		$csp->addAllowedScriptDomain('*');
		$csp->addAllowedConnectDomain('*');
		$csp->addAllowedMediaDomain('data:');
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
