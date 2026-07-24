<?php
/**
 * Social Share Master core class.
 *
 * @package Social_Share_Master
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Social_Share_Master
 */
class Social_Share_Master {

	const OPTION = 'social_share_master_settings';

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'append_inline_buttons' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_floating' ) );
		add_action( 'wp_head', array( __CLASS__, 'social_meta' ) );
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'enabled'         => 1,
			'floating'        => 1,
			'inline'          => 1,
			'post_types'      => array( 'post', 'page' ),
			'networks'        => array( 'facebook', 'twitter', 'linkedin', 'whatsapp', 'email', 'copy' ),
			'position'        => 'left',
			'show_count'      => 0,
			'button_style'    => 'rounded',
		);
		$settings = get_option( self::OPTION, array() );
		$settings = wp_parse_args( $settings, $defaults );
		if ( ! is_array( $settings['networks'] ) ) {
			$settings['networks'] = $defaults['networks'];
		}
		if ( ! is_array( $settings['post_types'] ) ) {
			$settings['post_types'] = $defaults['post_types'];
		}
		return $settings;
	}

	/**
	 * Add admin menu.
	 */
	public static function add_menu() {
		add_management_page(
			esc_html__( 'Social Share Master', 'social-share-master' ),
			esc_html__( 'Social Share Master', 'social-share-master' ),
			'manage_options',
			'social-share-master',
			array( __CLASS__, 'render_settings' )
		);
	}

	/**
	 * Enqueue admin assets.
     *
     * @param string $hook Hook.
     */
	public static function enqueue_assets( $hook ) {
		if ( 'tools_page_social-share-master' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'ssm-admin', SSM_URL . 'assets/css/admin.css', array(), SSM_VERSION );
	}

	/**
	 * Frontend assets.
	 */
	public static function frontend_assets() {
		wp_enqueue_style( 'ssm-public', SSM_URL . 'assets/css/public.css', array(), SSM_VERSION );
		wp_enqueue_script( 'ssm-public', SSM_URL . 'assets/js/public.js', array(), SSM_VERSION, true );
	}

	/**
	 * Save admin settings.
	 */
	public static function save_settings() {
		if ( ! isset( $_POST['ssm_save'] ) || ! isset( $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'ssm_settings' ) ) {
			return;
		}

		$settings = self::get_settings();
		$settings['enabled']       = isset( $_POST['ssm_enabled'] ) ? 1 : 0;
		$settings['floating']      = isset( $_POST['ssm_floating'] ) ? 1 : 0;
		$settings['inline']        = isset( $_POST['ssm_inline'] ) ? 1 : 0;
		$settings['show_count']    = isset( $_POST['ssm_show_count'] ) ? 1 : 0;
		$settings['position']      = isset( $_POST['ssm_position'] ) && in_array( sanitize_text_field( wp_unslash( $_POST['ssm_position'] ) ), array( 'left', 'right' ), true ) ? sanitize_text_field( wp_unslash( $_POST['ssm_position'] ) ) : 'left';
		$settings['button_style']  = isset( $_POST['ssm_button_style'] ) ? sanitize_text_field( wp_unslash( $_POST['ssm_button_style'] ) ) : 'rounded';
		$settings['networks']      = isset( $_POST['ssm_networks'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ssm_networks'] ) ) : array();
		$settings['post_types']    = isset( $_POST['ssm_post_types'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['ssm_post_types'] ) ) : array();

		update_option( self::OPTION, $settings );
		wp_safe_redirect( add_query_arg( 'ssm_saved', '1', wp_get_referer() ) );
		exit;
	}

	/**
	 * Render admin settings.
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission.', 'social-share-master' ) );
		}

		$settings   = self::get_settings();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$networks   = self::get_networks();
		?>
		<div class="wrap ssm-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php if ( isset( $_GET['ssm_saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'social-share-master' ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'ssm_settings' ); ?>

				<div class="ssm-card">
					<h2><?php esc_html_e( 'General Settings', 'social-share-master' ); ?></h2>
					<label class="ssm-toggle"><input type="checkbox" name="ssm_enabled" value="1" <?php checked( 1, $settings['enabled'] ); ?>> <?php esc_html_e( 'Enable sharing', 'social-share-master' ); ?></label>
					<label class="ssm-toggle"><input type="checkbox" name="ssm_floating" value="1" <?php checked( 1, $settings['floating'] ); ?>> <?php esc_html_e( 'Show floating sidebar', 'social-share-master' ); ?></label>
					<label class="ssm-toggle"><input type="checkbox" name="ssm_inline" value="1" <?php checked( 1, $settings['inline'] ); ?>> <?php esc_html_e( 'Show inline buttons after content', 'social-share-master' ); ?></label>
					<label class="ssm-toggle"><input type="checkbox" name="ssm_show_count" value="1" <?php checked( 1, $settings['show_count'] ); ?>> <?php esc_html_e( 'Show share counts', 'social-share-master' ); ?></label>

					<label class="ssm-label"><?php esc_html_e( 'Floating Position', 'social-share-master' ); ?></label>
					<select name="ssm_position">
						<option value="left" <?php selected( 'left', $settings['position'] ); ?>><?php esc_html_e( 'Left', 'social-share-master' ); ?></option>
						<option value="right" <?php selected( 'right', $settings['position'] ); ?>><?php esc_html_e( 'Right', 'social-share-master' ); ?></option>
					</select>

					<label class="ssm-label"><?php esc_html_e( 'Button Style', 'social-share-master' ); ?></label>
					<select name="ssm_button_style">
						<option value="rounded" <?php selected( 'rounded', $settings['button_style'] ); ?>><?php esc_html_e( 'Rounded', 'social-share-master' ); ?></option>
						<option value="square" <?php selected( 'square', $settings['button_style'] ); ?>><?php esc_html_e( 'Square', 'social-share-master' ); ?></option>
						<option value="minimal" <?php selected( 'minimal', $settings['button_style'] ); ?>><?php esc_html_e( 'Minimal', 'social-share-master' ); ?></option>
					</select>
				</div>

				<div class="ssm-card">
					<h2><?php esc_html_e( 'Post Types', 'social-share-master' ); ?></h2>
					<?php foreach ( $post_types as $pt ) : ?>
						<label class="ssm-check"><input type="checkbox" name="ssm_post_types[]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $settings['post_types'], true ) ); ?>> <?php echo esc_html( $pt->label ); ?></label>
					<?php endforeach; ?>
				</div>

				<div class="ssm-card">
					<h2><?php esc_html_e( 'Networks', 'social-share-master' ); ?></h2>
					<?php foreach ( $networks as $key => $label ) : ?>
						<label class="ssm-check"><input type="checkbox" name="ssm_networks[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $settings['networks'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
				</div>

				<?php submit_button( __( 'Save Settings', 'social-share-master' ), 'primary', 'ssm_save' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get available networks.
     *
     * @return array
	 */
	public static function get_networks() {
		return array(
			'facebook'  => __( 'Facebook', 'social-share-master' ),
			'twitter'   => __( 'Twitter / X', 'social-share-master' ),
			'linkedin'  => __( 'LinkedIn', 'social-share-master' ),
			'whatsapp'  => __( 'WhatsApp', 'social-share-master' ),
			'pinterest' => __( 'Pinterest', 'social-share-master' ),
			'email'     => __( 'Email', 'social-share-master' ),
			'copy'      => __( 'Copy Link', 'social-share-master' ),
		);
	}

	/**
	 * Render share buttons.
     *
     * @param string $context Context.
     * @return string
	 */
	public static function render_buttons( $context = 'inline' ) {
		$settings = self::get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return '';
		}

		if ( 'floating' === $context && empty( $settings['floating'] ) ) {
			return '';
		}
		if ( 'inline' === $context && empty( $settings['inline'] ) ) {
			return '';
		}

		if ( ! in_array( get_post_type(), $settings['post_types'], true ) ) {
			return '';
		}

		$url   = rawurlencode( get_permalink() );
		$title = rawurlencode( get_the_title() );
		$img   = rawurlencode( get_the_post_thumbnail_url( null, 'full' ) ?: '' );

		$links = array(
			'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
			'twitter'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
			'linkedin'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
			'whatsapp'  => 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url,
			'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $img . '&description=' . $title,
			'email'     => 'mailto:?subject=' . $title . '&body=' . $url,
			'copy'      => '#',
		);

		$classes = 'ssm-buttons ' . esc_attr( $context ) . ' style-' . esc_attr( $settings['button_style'] ) . ' position-' . esc_attr( $settings['position'] );
		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>">
			<?php foreach ( $settings['networks'] as $network ) : ?>
				<?php if ( isset( $links[ $network ] ) ) : ?>
					<?php if ( 'copy' === $network ) : ?>
						<button type="button" class="ssm-button ssm-copy" data-network="copy" aria-label="<?php esc_attr_e( 'Copy link', 'social-share-master' ); ?>">
							<span class="ssm-icon"><?php echo self::get_icon( $network ); ?></span>
							<span class="ssm-label"><?php echo esc_html( self::get_networks()[ $network ] ); ?></span>
						</button>
					<?php else : ?>
						<a href="<?php echo esc_url( $links[ $network ] ); ?>" target="_blank" rel="noopener noreferrer" class="ssm-button" data-network="<?php echo esc_attr( $network ); ?>">
							<span class="ssm-icon"><?php echo self::get_icon( $network ); ?></span>
							<span class="ssm-label"><?php echo esc_html( self::get_networks()[ $network ] ); ?></span>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Append inline buttons.
     *
     * @param string $content Content.
     * @return string
	 */
	public static function append_inline_buttons( $content ) {
		if ( is_singular() && ! doing_filter( 'get_the_excerpt' ) ) {
			$buttons = self::render_buttons( 'inline' );
			if ( $buttons ) {
				return $content . $buttons;
			}
		}
		return $content;
	}

	/**
	 * Render floating buttons.
	 */
	public static function render_floating() {
		if ( is_singular() ) {
			echo self::render_buttons( 'floating' );
		}
	}

	/**
	 * Get SVG icon.
     *
     * @param string $network Network.
     * @return string
	 */
	public static function get_icon( $network ) {
		$icons = array(
			'facebook'  => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>',
			'twitter'   => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M23 3a10.9 10.9 0 0 1-3.14 1.53A4.48 4.48 0 0 0 12 8v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>',
			'linkedin'  => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2zM4 6a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/></svg>',
			'whatsapp'  => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.13 1.585 5.931L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>',
			'pinterest' => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.415 0-5.42 2.562-5.42 5.21 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg>',
			'email'     => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path fill="#fff" d="M22 6l-10 7L2 6"/></svg>',
			'copy'      => '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>',
		);
		return isset( $icons[ $network ] ) ? $icons[ $network ] : '';
	}

	/**
	 * Add Open Graph / Twitter meta tags.
	 */
	public static function social_meta() {
		if ( ! is_singular() ) {
			return;
		}

		$settings = self::get_settings();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		$title = esc_attr( get_the_title() );
		$desc  = esc_attr( get_the_excerpt() );
		$url   = esc_url( get_permalink() );
		$img   = get_the_post_thumbnail_url( null, 'full' );

		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:type" content="article">' . "\n";
		if ( $img ) {
			echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		if ( $img ) {
			echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
		}
	}
}
