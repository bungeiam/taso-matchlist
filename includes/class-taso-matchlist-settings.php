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
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
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
			</div>
			<?php
		}
	}
}