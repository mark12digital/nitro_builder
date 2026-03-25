<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Activator {

	public static function activate() {
		if ( ! get_option( NB_TOKEN_OPT ) ) {
			update_option( NB_TOKEN_OPT, bin2hex( random_bytes( 32 ) ), false );
		}
	}

	public static function deactivate() {
		// Token e páginas preservados na desativação.
		// Apenas a desinstalação (uninstall.php) remove dados.
	}
}
