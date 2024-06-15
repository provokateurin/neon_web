<?php

declare(strict_types=1);

namespace OCA\Neon;

use OCP\AppFramework\Http\Response;

class HtmlResponse extends Response {

	public function __construct(
		protected string $content,
	) {
		parent::__construct();

		$this->addHeader('Content-Type', 'text/html');
	}

	public function render(): string {
		return $this->content;
	}
}
