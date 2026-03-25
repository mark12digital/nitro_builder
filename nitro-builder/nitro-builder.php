<?php
/**
 * Plugin Name:       Nitro Builder
 * Plugin URI:        https://github.com/mark12digital/nitro_builder
 * Description:       Cria e gerencia páginas WordPress com HTML/CSS/JS puro via API REST, sem interferência do tema ou outros plugins.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mark12 Digital
 * Author URI:        https://github.com/mark12digital
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nitro-builder
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NB_VERSION',    '1.0.0' );
define( 'NB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NB_META_FLAG',  '_nitro_builder_page' );
define( 'NB_META_HTML',  '_nitro_builder_html' );
define( 'NB_TOKEN_OPT',  'nitro_builder_api_token' );
define( 'NB_NAMESPACE',  'nitro-builder/v1' );

// Autoloading: Composer quando disponível, fallback manual caso contrário.
if ( file_exists( NB_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once NB_PLUGIN_DIR . 'vendor/autoload.php';
} else {
	require_once NB_PLUGIN_DIR . 'includes/autoload.php';
}

register_activation_hook( __FILE__, [ 'NitroBuilder\Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'NitroBuilder\Activator', 'deactivate' ] );

function nitro_builder(): \NitroBuilder\Plugin {
	return \NitroBuilder\Plugin::get_instance();
}

add_action( 'plugins_loaded', function () {
	nitro_builder()->init();
} );
