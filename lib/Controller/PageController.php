<?php

declare(strict_types=1);

namespace OCA\Neon\Controller;

use Exception;
use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\Neon\AppInfo\Application;
use OCA\Neon\StaticResponse;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

/** @psalm-suppress UnusedClass */
class PageController extends Controller {

	public function __construct(
		string                                      $appName,
		IRequest                                    $request,
		protected IAppManager                       $appManager,
		protected ContentSecurityPolicyNonceManager $nonceManager,
	) {
		parent::__construct($appName, $request);
	}

	#[NoCSRFRequired]
	#[PublicPage]
	public function index(): TemplateResponse {
		$response = new TemplateResponse(
			Application::APP_ID,
			'index',
			[],
			TemplateResponse::RENDER_AS_PUBLIC
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

		$webAppDir = $this->appManager->getAppWebPath(Application::APP_ID);
		$nonce = $this->nonceManager->getNonce();
		$response = new StaticResponse(
			$file,
			'/index.php' . $webAppDir,
			$nonce,
		);

		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameAncestorDomain("'self'");
		$csp->addAllowedFrameAncestorDomain("'self'");
		$csp->addAllowedScriptDomain("'unsafe-eval'");
		$csp->addAllowedScriptDomain("'unsafe-inline'");
		$csp->addAllowedScriptDomain('*');
		$csp->addAllowedConnectDomain("https://fonts.gstatic.com/");
		$csp->addAllowedMediaDomain("data:");
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
