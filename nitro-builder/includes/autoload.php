<?php
/**
 * Autoloader de fallback — usado enquanto vendor/autoload.php não existe.
 * Após rodar `composer install` na pasta do plugin, este arquivo é ignorado
 * automaticamente e o autoloader do Composer assume.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register( function ( $class ) {
	$map = array(
		'NitroBuilder\\Plugin'    => NB_PLUGIN_DIR . 'includes/class-plugin.php',
		'NitroBuilder\\Activator' => NB_PLUGIN_DIR . 'includes/class-activator.php',
		'NitroBuilder\\Renderer'  => NB_PLUGIN_DIR . 'includes/class-renderer.php',
		'NitroBuilder\\Admin'     => NB_PLUGIN_DIR . 'includes/class-admin.php',
		'NitroBuilder\\Api\\Pages' => NB_PLUGIN_DIR . 'includes/api/class-api-pages.php',
	);

	if ( isset( $map[ $class ] ) ) {
		require_once $map[ $class ];
	}
} );
