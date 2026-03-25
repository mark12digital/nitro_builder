<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NB_Admin {

	public static function init() {
		add_action( 'admin_menu',                 array( __CLASS__, 'settings_page' ) );
		add_filter( 'manage_pages_columns',        array( __CLASS__, 'add_column' ) );
		add_action( 'manage_pages_custom_column',  array( __CLASS__, 'render_column' ), 10, 2 );
	}

	public static function settings_page() {
		add_options_page(
			esc_html__( 'Nitro Builder', 'nitro-builder' ),
			esc_html__( 'Nitro Builder', 'nitro-builder' ),
			'manage_options',
			'nitro-builder',
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['nb_regenerate'] ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sem permissão.', 'nitro-builder' ) );
			}
			check_admin_referer( 'nb_regenerate_token', '_nb_nonce' );
			$new_token = bin2hex( random_bytes( 32 ) );
			update_option( NB_TOKEN_OPT, $new_token, false );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Token regenerado com sucesso.', 'nitro-builder' ) . '</p></div>';
		}

		$token    = get_option( NB_TOKEN_OPT, '' );
		$api_base = rest_url( NB_NAMESPACE . '/pages' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nitro Builder', 'nitro-builder' ); ?></h1>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'URL base da API', 'nitro-builder' ); ?></th>
					<td>
						<code><?php echo esc_url( $api_base ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Token de autenticação', 'nitro-builder' ); ?></th>
					<td>
						<input
							type="text"
							id="nb-token-field"
							value="<?php echo esc_attr( $token ); ?>"
							class="regular-text"
							readonly
							style="font-family:monospace;"
						/>
						<button
							type="button"
							class="button"
							onclick="
								var f = document.getElementById('nb-token-field');
								f.select();
								document.execCommand('copy');
								this.textContent = '<?php echo esc_js( __( 'Copiado!', 'nitro-builder' ) ); ?>';
								setTimeout(function(){ this.textContent = '<?php echo esc_js( __( 'Copiar', 'nitro-builder' ) ); ?>'; }.bind(this), 2000);
							"
						><?php esc_html_e( 'Copiar', 'nitro-builder' ); ?></button>
						<p class="description"><?php esc_html_e( 'Envie este token no header X-NB-Token em todas as requisições.', 'nitro-builder' ); ?></p>
					</td>
				</tr>
			</table>

			<hr>

			<h2><?php esc_html_e( 'Regenerar token', 'nitro-builder' ); ?></h2>
			<p><?php esc_html_e( 'Ao regenerar, o token atual é imediatamente invalidado. Atualize o token em todos os clientes que o utilizam.', 'nitro-builder' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( 'nb_regenerate_token', '_nb_nonce' ); ?>
				<button
					type="submit"
					name="nb_regenerate"
					value="1"
					class="button button-secondary"
					onclick="return confirm('<?php echo esc_js( __( 'Tem certeza? O token atual será invalidado imediatamente.', 'nitro-builder' ) ); ?>');"
				><?php esc_html_e( 'Regenerar token', 'nitro-builder' ); ?></button>
			</form>
		</div>
		<?php
	}

	public static function add_column( array $columns ): array {
		$columns['nitrobuilder'] = esc_html__( 'Nitro Builder', 'nitro-builder' );
		return $columns;
	}

	public static function render_column( string $column, int $post_id ) {
		if ( 'nitrobuilder' !== $column ) {
			return;
		}

		if ( get_post_meta( $post_id, NB_META_FLAG, true ) ) {
			echo '<span style="color:#6c3eb5;font-weight:600;">&#9679; ' . esc_html__( 'Gerenciada', 'nitro-builder' ) . '</span>';
		}
	}
}
