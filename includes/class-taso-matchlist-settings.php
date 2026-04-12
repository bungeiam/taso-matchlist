<?php
/**
 * Admin settings page.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Taso_Matchlist_Settings' ) ) {

	/**
	 * Handles admin settings UI and tools.
	 */
	class Taso_Matchlist_Settings {

		/**
		 * Settings page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'taso-matchlist';

		/**
		 * Settings group name.
		 *
		 * @var string
		 */
		const SETTINGS_GROUP = 'taso_matchlist_settings';

		/**
		 * Settings section id.
		 *
		 * @var string
		 */
		const SETTINGS_SECTION = 'taso_matchlist_main_section';

		/**
		 * Test connection admin action.
		 *
		 * @var string
		 */
		const TEST_ACTION = 'taso_matchlist_test_connection';

		/**
		 * Refresh preview admin action.
		 *
		 * @var string
		 */
		const REFRESH_ACTION = 'taso_matchlist_refresh_preview';

		/**
		 * Download preview JSON admin action.
		 *
		 * @var string
		 */
		const DOWNLOAD_JSON_ACTION = 'taso_matchlist_download_preview_json';

		/**
		 * API service.
		 *
		 * @var Taso_Matchlist_API
		 */
		private $api;

		/**
		 * Matches service.
		 *
		 * @var Taso_Matchlist_Matches
		 */
		private $matches;

		/**
		 * Constructor.
		 *
		 * @param Taso_Matchlist_API     $api     API instance.
		 * @param Taso_Matchlist_Matches $matches Matches instance.
		 */
		public function __construct( $api, $matches ) {
			$this->api     = $api;
			$this->matches = $matches;

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_post_' . self::TEST_ACTION, array( $this, 'handle_test_connection' ) );
			add_action( 'admin_post_' . self::REFRESH_ACTION, array( $this, 'handle_refresh_preview' ) );
			add_action( 'admin_post_' . self::DOWNLOAD_JSON_ACTION, array( $this, 'handle_download_preview_json' ) );
			add_action( 'admin_notices', array( $this, 'render_admin_notice' ) );
		}

		/**
		 * Register admin menu.
		 *
		 * @return void
		 */
		public function register_menu() {
			add_options_page(
				__( 'Taso Matchlist', 'taso-matchlist' ),
				__( 'Taso Matchlist', 'taso-matchlist' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render_settings_page' )
			);
		}

		/**
		 * Register plugin settings.
		 *
		 * @return void
		 */
		public function register_settings() {
			register_setting(
				self::SETTINGS_GROUP,
				'taso_matchlist_api_key',
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_api_key' ),
					'default'           => '',
				)
			);

			register_setting(
				self::SETTINGS_GROUP,
				'taso_matchlist_club_id',
				array(
					'type'              => 'string',
					'sanitize_callback' => array( $this, 'sanitize_club_id' ),
					'default'           => '',
				)
			);

			register_setting(
				self::SETTINGS_GROUP,
				'taso_matchlist_days_ahead',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_days_ahead' ),
					'default'           => 90,
				)
			);

			register_setting(
				self::SETTINGS_GROUP,
				'taso_matchlist_cache_minutes',
				array(
					'type'              => 'integer',
					'sanitize_callback' => array( $this, 'sanitize_cache_minutes' ),
					'default'           => 30,
				)
			);

			add_settings_section(
				self::SETTINGS_SECTION,
				__( 'TASO API -asetukset', 'taso-matchlist' ),
				array( $this, 'render_settings_section' ),
				self::MENU_SLUG
			);

			add_settings_field(
				'taso_matchlist_api_key',
				__( 'API-avain', 'taso-matchlist' ),
				array( $this, 'render_api_key_field' ),
				self::MENU_SLUG,
				self::SETTINGS_SECTION
			);

			add_settings_field(
				'taso_matchlist_club_id',
				__( 'Club ID', 'taso-matchlist' ),
				array( $this, 'render_club_id_field' ),
				self::MENU_SLUG,
				self::SETTINGS_SECTION
			);

			add_settings_field(
				'taso_matchlist_days_ahead',
				__( 'Haettavat päivät eteenpäin', 'taso-matchlist' ),
				array( $this, 'render_days_ahead_field' ),
				self::MENU_SLUG,
				self::SETTINGS_SECTION
			);

			add_settings_field(
				'taso_matchlist_cache_minutes',
				__( 'Cache-aika (minuuttia)', 'taso-matchlist' ),
				array( $this, 'render_cache_minutes_field' ),
				self::MENU_SLUG,
				self::SETTINGS_SECTION
			);
		}

		/**
		 * Sanitize API key.
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public function sanitize_api_key( $value ) {
			return is_string( $value ) ? sanitize_text_field( trim( $value ) ) : '';
		}

		/**
		 * Sanitize club id.
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public function sanitize_club_id( $value ) {
			$value = is_string( $value ) ? trim( $value ) : '';
			$value = preg_replace( '/[^0-9]/', '', $value );

			return is_string( $value ) ? $value : '';
		}

		/**
		 * Sanitize days ahead.
		 *
		 * @param mixed $value Raw value.
		 * @return int
		 */
		public function sanitize_days_ahead( $value ) {
			$value = absint( $value );

			if ( $value < 1 ) {
				$value = 90;
			}

			if ( $value > 365 ) {
				$value = 365;
			}

			return $value;
		}

		/**
		 * Sanitize cache minutes.
		 *
		 * @param mixed $value Raw value.
		 * @return int
		 */
		public function sanitize_cache_minutes( $value ) {
			$value = absint( $value );

			if ( $value < 1 ) {
				$value = 30;
			}

			if ( $value > 1440 ) {
				$value = 1440;
			}

			return $value;
		}

		/**
		 * Render section intro.
		 *
		 * @return void
		 */
		public function render_settings_section() {
			echo '<p>' . esc_html__( 'Määritä TASO REST API -yhteyden perusasetukset. API-avainta käytetään vain palvelinpuolella.', 'taso-matchlist' ) . '</p>';
		}

		/**
		 * Render API key field.
		 *
		 * @return void
		 */
		public function render_api_key_field() {
			$value = get_option( 'taso_matchlist_api_key', '' );
			?>
			<input type="text" class="regular-text" name="taso_matchlist_api_key" value="<?php echo esc_attr( $value ); ?>" autocomplete="off" />
			<p class="description"><?php echo esc_html__( 'Syötä TASO-palvelusta saatu API-avain.', 'taso-matchlist' ); ?></p>
			<?php
		}

		/**
		 * Render club id field.
		 *
		 * @return void
		 */
		public function render_club_id_field() {
			$value = get_option( 'taso_matchlist_club_id', '' );
			?>
			<input type="text" class="regular-text" name="taso_matchlist_club_id" value="<?php echo esc_attr( $value ); ?>" inputmode="numeric" />
			<p class="description"><?php echo esc_html__( 'Esimerkiksi Union Plaanin club_id.', 'taso-matchlist' ); ?></p>
			<?php
		}

		/**
		 * Render days ahead field.
		 *
		 * @return void
		 */
		public function render_days_ahead_field() {
			$value = get_option( 'taso_matchlist_days_ahead', 90 );
			?>
			<input type="number" class="small-text" name="taso_matchlist_days_ahead" value="<?php echo esc_attr( (string) absint( $value ) ); ?>" min="1" max="365" step="1" />
			<p class="description"><?php echo esc_html__( 'Kuinka monta päivää eteenpäin otteluita haetaan.', 'taso-matchlist' ); ?></p>
			<?php
		}

		/**
		 * Render cache minutes field.
		 *
		 * @return void
		 */
		public function render_cache_minutes_field() {
			$value = get_option( 'taso_matchlist_cache_minutes', 30 );
			?>
			<input type="number" class="small-text" name="taso_matchlist_cache_minutes" value="<?php echo esc_attr( (string) absint( $value ) ); ?>" min="1" max="1440" step="1" />
			<p class="description"><?php echo esc_html__( 'Kuinka kauan otteludata säilytetään välimuistissa.', 'taso-matchlist' ); ?></p>
			<?php
		}

		/**
		 * Handle test connection action.
		 *
		 * @return void
		 */
		public function handle_test_connection() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sinulla ei ole oikeutta tähän toimintoon.', 'taso-matchlist' ) );
			}

			check_admin_referer( 'taso_matchlist_test_connection' );

			$result = $this->api->test_connection();

			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
				$status  = 'error';
			} else {
				$message = sprintf(
					/* translators: %d: number of returned matches */
					__( 'API-yhteys onnistui. Vastauksesta löytyi %d ottelua valitulla aikavälillä.', 'taso-matchlist' ),
					(int) $result['match_count']
				);
				$status = 'success';
			}

			$redirect_url = add_query_arg(
				array(
					'page'                => self::MENU_SLUG,
					'taso_matchlist_test' => $status,
					'taso_matchlist_msg'  => rawurlencode( $message ),
				),
				admin_url( 'options-general.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Handle preview refresh.
		 *
		 * @return void
		 */
		public function handle_refresh_preview() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sinulla ei ole oikeutta tähän toimintoon.', 'taso-matchlist' ) );
			}

			check_admin_referer( 'taso_matchlist_refresh_preview' );

			$this->matches->clear_cache();
			$result = $this->matches->get_home_matches( true );

			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
				$status  = 'error';
			} else {
				$group_count = count( $result );
				$match_count = 0;

				foreach ( $result as $group ) {
					if ( isset( $group['matches'] ) && is_array( $group['matches'] ) ) {
						$match_count += count( $group['matches'] );
					}
				}

				$message = sprintf(
					/* translators: 1: group count, 2: match count */
					__( 'Otteludata päivitettiin. Päiväryhmiä: %1$d, kotipelejä yhteensä: %2$d.', 'taso-matchlist' ),
					(int) $group_count,
					(int) $match_count
				);
				$status = 'success';
			}

			$redirect_url = add_query_arg(
				array(
					'page'                => self::MENU_SLUG,
					'taso_matchlist_test' => $status,
					'taso_matchlist_msg'  => rawurlencode( $message ),
				),
				admin_url( 'options-general.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Download normalized preview data as JSON.
		 *
		 * @return void
		 */
		public function handle_download_preview_json() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sinulla ei ole oikeutta tähän toimintoon.', 'taso-matchlist' ) );
			}

			check_admin_referer( 'taso_matchlist_download_preview_json' );

			$result = $this->matches->get_home_matches();

			if ( is_wp_error( $result ) ) {
				wp_die( esc_html( $result->get_error_message() ) );
			}

			$json = wp_json_encode(
				$result,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			);

			if ( false === $json ) {
				wp_die( esc_html__( 'JSON-datan muodostaminen epäonnistui.', 'taso-matchlist' ) );
			}

			$filename = 'taso-matchlist-preview-' . gmdate( 'Y-m-d-His' ) . '.json';

			nocache_headers();
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
			header( 'Content-Length: ' . strlen( $json ) );

			echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		/**
		 * Render admin notice after actions.
		 *
		 * @return void
		 */
		public function render_admin_notice() {
			if ( ! isset( $_GET['page'] ) || self::MENU_SLUG !== $_GET['page'] ) {
				return;
			}

			if ( empty( $_GET['taso_matchlist_test'] ) || empty( $_GET['taso_matchlist_msg'] ) ) {
				return;
			}

			$status  = sanitize_key( wp_unslash( $_GET['taso_matchlist_test'] ) );
			$message = sanitize_text_field( wp_unslash( $_GET['taso_matchlist_msg'] ) );
			$class   = 'notice notice-info';

			if ( 'success' === $status ) {
				$class = 'notice notice-success';
			} elseif ( 'error' === $status ) {
				$class = 'notice notice-error';
			}

			?>
			<div class="<?php echo esc_attr( $class ); ?>">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render preview section.
		 *
		 * @return void
		 */
		private function render_preview_section() {
			$result = $this->matches->get_home_matches();
			?>
			<hr />

			<h2><?php echo esc_html__( 'Normalisoidun datan esikatselu', 'taso-matchlist' ); ?></h2>

			<p><?php echo esc_html__( 'Tässä näkyy backendissä normalisoitu ja kotipeleihin suodatettu data ennen frontend-renderöintiä.', 'taso-matchlist' ); ?></p>

			<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0 16px;">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::REFRESH_ACTION ); ?>" />
					<?php wp_nonce_field( 'taso_matchlist_refresh_preview' ); ?>
					<?php submit_button( __( 'Päivitä otteludata nyt', 'taso-matchlist' ), 'secondary', 'submit', false ); ?>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::DOWNLOAD_JSON_ACTION ); ?>" />
					<?php wp_nonce_field( 'taso_matchlist_download_preview_json' ); ?>
					<?php submit_button( __( 'Lataa JSON-tiedosto', 'taso-matchlist' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<?php if ( is_wp_error( $result ) ) : ?>
				<div class="notice notice-error inline">
					<p><?php echo esc_html( $result->get_error_message() ); ?></p>
				</div>
				<?php
				return;
			endif;
			?>

			<?php if ( empty( $result ) ) : ?>
				<div class="notice notice-info inline">
					<p><?php echo esc_html__( 'Ei kotipelejä näytettäväksi tällä hetkellä.', 'taso-matchlist' ); ?></p>
				</div>
				<?php
				return;
			endif;
			?>

			<pre style="max-height:600px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:16px;"><?php echo esc_html( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			<?php
		}

		/**
		 * Render settings page.
		 *
		 * @return void
		 */
		public function render_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap">
				<h1><?php echo esc_html__( 'Taso Matchlist', 'taso-matchlist' ); ?></h1>

				<form method="post" action="options.php">
					<?php
					settings_fields( self::SETTINGS_GROUP );
					do_settings_sections( self::MENU_SLUG );
					submit_button( __( 'Tallenna asetukset', 'taso-matchlist' ) );
					?>
				</form>

				<hr />

				<h2><?php echo esc_html__( 'Yhteystestin työkalut', 'taso-matchlist' ); ?></h2>

				<p><?php echo esc_html__( 'Voit testata TASO API -yhteyden tai päivittää otteludatan esikatselun tästä.', 'taso-matchlist' ); ?></p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>" />
					<?php wp_nonce_field( 'taso_matchlist_test_connection' ); ?>
					<?php submit_button( __( 'Testaa API-yhteys', 'taso-matchlist' ), 'secondary', 'submit', false ); ?>
				</form>

				<?php $this->render_preview_section(); ?>
			</div>
			<?php
		}
	}
}