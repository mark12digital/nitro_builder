<?php
/**
 * Plugin Name: Anti-Gravity Pages
 * Plugin URI:  https://github.com/anti-gravity
 * Description: Gerencia páginas WordPress via API REST com HTML/CSS/JS puro, sem interferência do tema ou outros plugins.
 * Version:     1.0.0
 * Author:      Anti-Gravity
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────
// ATIVAÇÃO — gera token inicial
// ─────────────────────────────────────────────

register_activation_hook( __FILE__, 'ag_activate' );

function ag_activate() {
	if ( ! get_option( 'antigravity_api_token' ) ) {
		update_option( 'antigravity_api_token', bin2hex( random_bytes( 32 ) ), false );
	}
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

/**
 * Valida o token da requisição. Usado como permission_callback nas rotas REST.
 */
function ag_authenticate( WP_REST_Request $request ) {
	$token    = $request->get_header( 'X-AG-Token' );
	$stored   = get_option( 'antigravity_api_token', '' );

	if ( ! $token || ! $stored || ! hash_equals( $stored, $token ) ) {
		return new WP_Error(
			'ag_forbidden',
			'Token inválido ou ausente.',
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * Formata um WP_Post como resposta padronizada da API.
 */
function ag_format_page_response( WP_Post $post, bool $include_html = false ): array {
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
		$data['html'] = get_post_meta( $post->ID, '_antigravity_html', true );
	}

	return $data;
}

/**
 * Verifica se um post existe e pertence ao plugin.
 * Retorna WP_Post ou WP_Error 404.
 */
function ag_get_owned_page( int $id ) {
	$post = get_post( $id );

	if ( ! $post || 'page' !== $post->post_type || ! get_post_meta( $id, '_antigravity_page', true ) ) {
		return new WP_Error(
			'ag_not_found',
			'Página não encontrada.',
			array( 'status' => 404 )
		);
	}

	return $post;
}

// ─────────────────────────────────────────────
// API REST — registro de rotas
// ─────────────────────────────────────────────

add_action( 'rest_api_init', 'ag_register_routes' );

function ag_register_routes() {
	$ns = 'antigravity/v1';

	register_rest_route( $ns, '/pages', array(
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ag_create_page',
			'permission_callback' => 'ag_authenticate',
		),
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ag_list_pages',
			'permission_callback' => 'ag_authenticate',
		),
	) );

	register_rest_route( $ns, '/pages/(?P<id>\d+)', array(
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ag_get_page',
			'permission_callback' => 'ag_authenticate',
		),
		array(
			'methods'             => 'PUT',
			'callback'            => 'ag_update_page',
			'permission_callback' => 'ag_authenticate',
		),
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'ag_delete_page',
			'permission_callback' => 'ag_authenticate',
		),
	) );
}

// ─────────────────────────────────────────────
// HANDLERS DA API
// ─────────────────────────────────────────────

/** POST /pages — cria uma nova página */
function ag_create_page( WP_REST_Request $request ) {
	$params = $request->get_json_params();

	$title = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
	$html  = $params['html'] ?? '';

	if ( ! $title ) {
		return new WP_Error( 'ag_missing_title', 'O campo "title" é obrigatório.', array( 'status' => 400 ) );
	}
	if ( ! $html ) {
		return new WP_Error( 'ag_missing_html', 'O campo "html" é obrigatório.', array( 'status' => 400 ) );
	}

	$status = in_array( $params['status'] ?? '', array( 'draft', 'private', 'publish' ), true )
		? $params['status']
		: 'publish';

	$args = array(
		'post_type'   => 'page',
		'post_title'  => $title,
		'post_status' => $status,
		'post_content' => '',
	);

	if ( ! empty( $params['slug'] ) ) {
		$args['post_name'] = sanitize_title( $params['slug'] );
	}

	$post_id = wp_insert_post( $args, true );

	if ( is_wp_error( $post_id ) ) {
		return new WP_Error( 'ag_insert_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
	}

	update_post_meta( $post_id, '_antigravity_page', '1' );
	// HTML salvo sem sanitização — intencional (conteúdo vem de IA controlada pelo usuário)
	update_post_meta( $post_id, '_antigravity_html', $html );

	$post     = get_post( $post_id );
	$response = rest_ensure_response( ag_format_page_response( $post ) );
	$response->set_status( 201 );

	return $response;
}

/** GET /pages — lista todas as páginas do plugin */
function ag_list_pages( WP_REST_Request $request ) {
	$posts = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'private' ),
		'numberposts'    => -1,
		'meta_key'       => '_antigravity_page',
		'meta_value'     => '1',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$data = array_map( fn( $post ) => ag_format_page_response( $post ), $posts );

	return rest_ensure_response( $data );
}

/** GET /pages/{id} — retorna uma página específica com HTML */
function ag_get_page( WP_REST_Request $request ) {
	$post = ag_get_owned_page( (int) $request['id'] );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	return rest_ensure_response( ag_format_page_response( $post, true ) );
}

/** PUT /pages/{id} — atualiza uma página existente */
function ag_update_page( WP_REST_Request $request ) {
	$post = ag_get_owned_page( (int) $request['id'] );

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
			return new WP_Error( 'ag_update_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}
	}

	if ( isset( $params['html'] ) ) {
		update_post_meta( $post->ID, '_antigravity_html', $params['html'] );
	}

	$updated = get_post( $post->ID );

	return rest_ensure_response( ag_format_page_response( $updated ) );
}

/** DELETE /pages/{id} — remove permanentemente uma página */
function ag_delete_page( WP_REST_Request $request ) {
	$post = ag_get_owned_page( (int) $request['id'] );

	if ( is_wp_error( $post ) ) {
		return $post;
	}

	$deleted = wp_delete_post( $post->ID, true );

	if ( ! $deleted ) {
		return new WP_Error( 'ag_delete_failed', 'Falha ao excluir a página.', array( 'status' => 500 ) );
	}

	return rest_ensure_response( array( 'deleted' => true, 'id' => $post->ID ) );
}

// ─────────────────────────────────────────────
// RENDERIZAÇÃO ISOLADA
// ─────────────────────────────────────────────

add_action( 'template_redirect', 'ag_serve_page', 1 );

function ag_serve_page() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	$page_id = get_queried_object_id();

	if ( ! get_post_meta( $page_id, '_antigravity_page', true ) ) {
		return;
	}

	$html = get_post_meta( $page_id, '_antigravity_html', true );

	status_header( 200 );
	header( 'Content-Type: text/html; charset=UTF-8' );
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $html;
	exit;
}

// ─────────────────────────────────────────────
// INTERFACE ADMIN — coluna na listagem de páginas
// ─────────────────────────────────────────────

add_filter( 'manage_pages_columns', 'ag_add_pages_column' );

function ag_add_pages_column( array $columns ): array {
	$columns['antigravity'] = 'Anti-Gravity';
	return $columns;
}

add_action( 'manage_pages_custom_column', 'ag_render_pages_column', 10, 2 );

function ag_render_pages_column( string $column, int $post_id ) {
	if ( 'antigravity' !== $column ) {
		return;
	}

	if ( get_post_meta( $post_id, '_antigravity_page', true ) ) {
		echo '<span style="color:#6c3eb5;font-weight:600;">&#9679; Gerenciada</span>';
	}
}

// ─────────────────────────────────────────────
// INTERFACE ADMIN — página de configurações
// ─────────────────────────────────────────────

add_action( 'admin_menu', 'ag_add_settings_page' );

function ag_add_settings_page() {
	add_options_page(
		'Anti-Gravity Pages',
		'Anti-Gravity',
		'manage_options',
		'antigravity',
		'ag_render_settings_page'
	);
}

function ag_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Processa regeneração do token
	if ( isset( $_POST['ag_regenerate'] ) && check_admin_referer( 'ag_regenerate_token' ) ) {
		$new_token = bin2hex( random_bytes( 32 ) );
		update_option( 'antigravity_api_token', $new_token, false );
		echo '<div class="notice notice-success"><p>Token regenerado com sucesso.</p></div>';
	}

	$token    = get_option( 'antigravity_api_token', '' );
	$api_base = rest_url( 'antigravity/v1/pages' );
	?>
	<div class="wrap">
		<h1>Anti-Gravity Pages</h1>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">URL base da API</th>
				<td>
					<code><?php echo esc_html( $api_base ); ?></code>
				</td>
			</tr>
			<tr>
				<th scope="row">Token de autenticação</th>
				<td>
					<input
						type="text"
						id="ag-token-field"
						value="<?php echo esc_attr( $token ); ?>"
						class="regular-text"
						readonly
						style="font-family:monospace;"
					/>
					<button
						type="button"
						class="button"
						onclick="
							var f = document.getElementById('ag-token-field');
							f.select();
							document.execCommand('copy');
							this.textContent = 'Copiado!';
							setTimeout(function(){ this.textContent = 'Copiar'; }.bind(this), 2000);
						"
					>Copiar</button>
					<p class="description">Envie este token no header <code>X-AG-Token</code> em todas as requisições.</p>
				</td>
			</tr>
		</table>

		<hr>

		<h2>Regenerar token</h2>
		<p>Ao regenerar, o token atual é imediatamente invalidado. Atualize o token em todos os clientes que o utilizam.</p>

		<form method="post">
			<?php wp_nonce_field( 'ag_regenerate_token' ); ?>
			<button
				type="submit"
				name="ag_regenerate"
				value="1"
				class="button button-secondary"
				onclick="return confirm('Tem certeza? O token atual será invalidado imediatamente.');"
			>Regenerar token</button>
		</form>
	</div>
	<?php
}
