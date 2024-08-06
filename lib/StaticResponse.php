<?php

declare(strict_types=1);

namespace OCA\Neon;

use Exception;
use OCP\AppFramework\Http\Response;

/**
 * @template-extends Response<int, array<string, mixed>>
 */
class StaticResponse extends Response {

	/**
	 * @throws Exception
	 */
	public function __construct(
		protected string $path,
		protected string $webAppDir,
		protected string $nonce,
	) {
		parent::__construct();

		$ext = pathinfo($this->path, PATHINFO_EXTENSION);
		$contentType = match ($ext) {
			'js' => 'application/javascript',
			'wasm' => 'application/wasm',
			default => mime_content_type($this->path),
		};

		$this->addHeader('Content-Type', $contentType);
		$this->setETag(include('etag.php'));
		$this->cacheFor(7 * 24 * 60 * 60, true);
	}

	public function render(): string {
		$content = file_get_contents($this->path);
		if (str_ends_with($this->path, 'index.html')) {
			$content = str_replace(
				[
					'<base href="/">',
					'<script',
				],
				[
					'<base href="' . $this->webAppDir . '/static/">',
					'<script nonce="' . $this->nonce . '"',
				],
				$content,
			);
		}

		return $content;
	}
}
