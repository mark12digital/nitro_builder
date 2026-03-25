<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nitro_builder_api_token' );

$pages = get_posts( array(
	'post_type'   => 'page',
	'post_status' => 'any',
	'numberposts' => -1,
	'meta_key'    => '_nitro_builder_page',
	'meta_value'  => '1',
	'fields'      => 'ids',
) );

foreach ( $pages as $page_id ) {
	delete_post_meta( $page_id, '_nitro_builder_page' );
	delete_post_meta( $page_id, '_nitro_builder_html' );
}
