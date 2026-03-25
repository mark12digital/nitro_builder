<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NB_Plugin {

	private static ?NB_Plugin $instance = null;

	private function __construct() {
		$this->load_dependencies();
		$this->register_hooks();
	}

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

	private function load_dependencies(): void {
		require_once NB_PLUGIN_DIR . 'includes/class-activator.php';
		require_once NB_PLUGIN_DIR . 'includes/class-api.php';
		require_once NB_PLUGIN_DIR . 'includes/class-renderer.php';
		require_once NB_PLUGIN_DIR . 'includes/class-admin.php';
	}

	private function register_hooks(): void {
		NB_API::init();
		NB_Renderer::init();
		NB_Admin::init();
	}
}
