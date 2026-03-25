<?php
namespace NitroBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	private function __construct() {}

	private function __clone() {}

	public function __wakeup(): void {
		throw new \Exception( 'Deserialização não permitida.' );
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		Api\Pages::init();
		Renderer::init();
		Admin::init();
	}
}
