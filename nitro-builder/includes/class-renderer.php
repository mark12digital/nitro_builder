<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Renderer {

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render' ), 1 );
	}

	public static function maybe_render() {
		if ( ! is_singular( 'page' ) ) {
			return;
		}

		$page_id = get_queried_object_id();

		if ( ! get_post_meta( $page_id, NB_META_FLAG, true ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_all' ), PHP_INT_MAX );

		$html = get_post_meta( $page_id, NB_META_HTML, true );

		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
		exit;
	}

	public static function dequeue_all() {
		global $wp_scripts, $wp_styles;
		$wp_scripts->queue = array();
		$wp_styles->queue  = array();
	}
}
