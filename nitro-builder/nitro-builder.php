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
define( 'NB_META_FLAG',  '_nitrobuilder_page' );
define( 'NB_META_HTML',  '_nitrobuilder_html' );
define( 'NB_TOKEN_OPT',  'nitrobuilder_api_token' );
define( 'NB_NAMESPACE',  'nitrobuilder/v1' );

require_once NB_PLUGIN_DIR . 'includes/class-activator.php';
require_once NB_PLUGIN_DIR . 'includes/class-api.php';
require_once NB_PLUGIN_DIR . 'includes/class-renderer.php';
require_once NB_PLUGIN_DIR . 'includes/class-admin.php';

register_activation_hook( __FILE__, array( 'NB_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NB_Activator', 'deactivate' ) );

NB_API::init();
NB_Renderer::init();
NB_Admin::init();
