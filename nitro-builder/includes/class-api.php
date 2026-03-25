<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_API {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		register_rest_route( NB_NAMESPACE, '/pages', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_create' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'handle_list' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			),
		) );

		register_rest_route( NB_NAMESPACE, '/pages/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'handle_get' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'handle_update' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( __CLASS__, 'handle_delete' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
			),
		) );
	}

	public static function check_permission( WP_REST_Request $request ) {
		$token  = $request->get_header( 'X-NB-Token' );
		$stored = get_option( NB_TOKEN_OPT, '' );

		if ( ! $token || ! $stored || ! hash_equals( $stored, $token ) ) {
			return new WP_Error(
				'nb_forbidden',
				__( 'Token inválido ou ausente.', 'nitro-builder' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	private static function get_owned_page( int $id ) {
		$post = get_post( $id );

		if ( ! $post || 'page' !== $post->post_type || ! get_post_meta( $id, NB_META_FLAG, true ) ) {
			return new WP_Error(
				'nb_not_found',
				__( 'Página não encontrada.', 'nitro-builder' ),
				array( 'status' => 404 )
			);
		}

		return $post;
	}

	private static function format_response( WP_Post $post, bool $include_html = false ): array {
		$data = array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'slug'       => $post->post_name,
			'status'     => $post->post_status,
			'url'        => get_permalink( $post->ID ),
			'created_at' => $post->post_date,
			'updated_at' => $post->post_modified,
		);

		if ( $include_html ) {
			$data['html'] = get_post_meta( $post->ID, NB_META_HTML, true );
		}

		return $data;
	}

	public static function handle_create( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		$title = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
		$html  = $params['html'] ?? '';

		if ( ! $title ) {
			return new WP_Error( 'nb_missing_title', __( 'O campo "title" é obrigatório.', 'nitro-builder' ), array( 'status' => 400 ) );
		}
		if ( ! $html ) {
			return new WP_Error( 'nb_missing_html', __( 'O campo "html" é obrigatório.', 'nitro-builder' ), array( 'status' => 400 ) );
		}

		$status = in_array( $params['status'] ?? '', array( 'draft', 'private', 'publish' ), true )
			? $params['status']
			: 'publish';

		$args = array(
			'post_type'    => 'page',
			'post_title'   => $title,
			'post_status'  => $status,
			'post_content' => '',
		);

		if ( ! empty( $params['slug'] ) ) {
			$args['post_name'] = sanitize_title( $params['slug'] );
		}

		$post_id = wp_insert_post( $args, true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'nb_insert_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
		}

		update_post_meta( $post_id, NB_META_FLAG, '1' );
		// HTML salvo sem sanitização — intencional (conteúdo vem de IA controlada pelo usuário).
		update_post_meta( $post_id, NB_META_HTML, $html );

		$response = rest_ensure_response( self::format_response( get_post( $post_id ) ) );
		$response->set_status( 201 );

		return $response;
	}

	public static function handle_list( WP_REST_Request $request ) {
		$posts = get_posts( array(
			'post_type'   => 'page',
			'post_status' => array( 'publish', 'draft', 'private' ),
			'numberposts' => -1,
			'meta_key'    => NB_META_FLAG,
			'meta_value'  => '1',
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		return rest_ensure_response( array_map( array( __CLASS__, 'format_response' ), $posts ) );
	}

	public static function handle_get( WP_REST_Request $request ) {
		$post = self::get_owned_page( (int) $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		return rest_ensure_response( self::format_response( $post, true ) );
	}

	public static function handle_update( WP_REST_Request $request ) {
		$post = self::get_owned_page( (int) $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$params = $request->get_json_params();
		$args   = array( 'ID' => $post->ID );

		if ( isset( $params['title'] ) ) {
			$args['post_title'] = sanitize_text_field( $params['title'] );
		}
		if ( isset( $params['slug'] ) ) {
			$args['post_name'] = sanitize_title( $params['slug'] );
		}
		if ( isset( $params['status'] ) && in_array( $params['status'], array( 'draft', 'private', 'publish' ), true ) ) {
			$args['post_status'] = $params['status'];
		}

		if ( count( $args ) > 1 ) {
			$result = wp_update_post( $args, true );
			if ( is_wp_error( $result ) ) {
				return new WP_Error( 'nb_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
			}
		}

		if ( isset( $params['html'] ) ) {
			update_post_meta( $post->ID, NB_META_HTML, $params['html'] );
		}

		return rest_ensure_response( self::format_response( get_post( $post->ID ) ) );
	}

	public static function handle_delete( WP_REST_Request $request ) {
		$post = self::get_owned_page( (int) $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$deleted = wp_delete_post( $post->ID, true );

		if ( ! $deleted ) {
			return new WP_Error( 'nb_delete_failed', __( 'Falha ao excluir a página.', 'nitro-builder' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'deleted' => true, 'id' => $post->ID ) );
	}
}
