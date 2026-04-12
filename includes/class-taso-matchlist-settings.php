<?php
/**
 * Admin settings class.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Taso_Matchlist_Settings' ) ) {

	/**
	 * Handles admin settings.
	 */
	class Taso_Matchlist_Settings {

		/**
		 * Settings page slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'taso-matchlist';

		/**
		 * Settings group.
		 *
		 * @var string
		 */
		const SETTINGS_GROUP = 'taso_matchlist_settings_group';

		/**
		 * Settings section id.
		 *
		 * @var string
		 */
		const SETTINGS_SECTION = 'taso_matchlist_main_section';

		/**
		 * Test action.
		 *
		 * @var string
		 */
		const TEST_ACTION = 'taso_matchlist_test_api_connection';

		/**
		 * API client instance.
		 *
		 * @var Taso_Matchlist_API
		 */
		private $api;

		/**
		 * Constructor.
		 *
		 * @param Taso_Matchlist_API $api API client.
		 */
		public function __construct( $api ) {
			$this->api = $api;

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_post_' . self::TEST_ACTION, array( $this, 'handle_test_connection' ) );
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
			<input
				type="password"
				id="taso_matchlist_api_key"
				name="taso_matchlist_api_key"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
				autocomplete="off"
			/>
			<p class="description">
				<?php esc_html_e( 'TASO REST API -avain. Säilyy WordPressin asetuksissa eikä näy frontendissä.', 'taso-matchlist' ); ?>
			</p>
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
			<input
				type="text"
				id="taso_matchlist_club_id"
				name="taso_matchlist_club_id"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text"
				inputmode="numeric"
			/>
			<p class="description">
				<?php esc_html_e( 'Union Plaanin TASO club_id. Ottelut haetaan tämän tunnisteen perusteella.', 'taso-matchlist' ); ?>
			</p>
			<?php
		}

		/**
		 * Render days ahead field.
		 *
		 * @return void
		 */
		public function render_days_ahead_field() {
			$value = absint( get_option( 'taso_matchlist_days_ahead', 90 ) );
			?>
			<input
				type="number"
				id="taso_matchlist_days_ahead"
				name="taso_matchlist_days_ahead"
				value="<?php echo esc_attr( $value ); ?>"
				class="small-text"
				min="1"
				max="365"
				step="1"
			/>
			<p class="description">
				<?php esc_html_e( 'Kuinka monta päivää eteenpäin otteluita haetaan. Oletus 90.', 'taso-matchlist' ); ?>
			</p>
			<?php
		}

		/**
		 * Render cache minutes field.
		 *
		 * @return void
		 */
		public function render_cache_minutes_field() {
			$value = absint( get_option( 'taso_matchlist_cache_minutes', 30 ) );
			?>
			<input
				type="number"
				id="taso_matchlist_cache_minutes"
				name="taso_matchlist_cache_minutes"
				value="<?php echo esc_attr( $value ); ?>"
				class="small-text"
				min="1"
				max="1440"
				step="1"
			/>
			<p class="description">
				<?php esc_html_e( 'Kuinka kauan normalisoitu otteludata pidetään välimuistissa. Oletus 30 minuuttia.', 'taso-matchlist' ); ?>
			</p>
			<?php
		}

		/**
		 * Handle API connection test.
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
		 * Render admin notice after test action.
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

			$class = 'notice notice-info';

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
				<h1><?php esc_html_e( 'Taso Matchlist', 'taso-matchlist' ); ?></h1>

				<form action="options.php" method="post">
					<?php
					settings_fields( self::SETTINGS_GROUP );
					do_settings_sections( self::MENU_SLUG );
					submit_button( __( 'Tallenna asetukset', 'taso-matchlist' ) );
					?>
				</form>

				<hr>

				<h2><?php esc_html_e( 'Yhteystesti', 'taso-matchlist' ); ?></h2>
				<p><?php esc_html_e( 'Testaa, että API-avain, Club ID ja TASO-yhteys toimivat nykyisillä asetuksilla.', 'taso-matchlist' ); ?></p>

				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
					<?php wp_nonce_field( 'taso_matchlist_test_connection' ); ?>
					<?php submit_button( __( 'Testaa API-yhteys', 'taso-matchlist' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
			<?php
		}
	}
}