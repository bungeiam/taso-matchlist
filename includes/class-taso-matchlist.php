<?php
/**
 * Main plugin class.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Taso_Matchlist' ) ) {

	/**
	 * Main plugin bootstrap class.
	 */
	final class Taso_Matchlist {

		/**
		 * Singleton instance.
		 *
		 * @var Taso_Matchlist|null
		 */
		private static $instance = null;

		/**
		 * Settings instance.
		 *
		 * @var Taso_Matchlist_Settings|null
		 */
		private $settings = null;

		/**
		 * API instance.
		 *
		 * @var Taso_Matchlist_API|null
		 */
		private $api = null;

		/**
		 * Matches instance.
		 *
		 * @var Taso_Matchlist_Matches|null
		 */
		private $matches = null;

		/**
		 * Frontend instance.
		 *
		 * @var Taso_Matchlist_Frontend|null
		 */
		private $frontend = null;

		/**
		 * Returns singleton instance.
		 *
		 * @return Taso_Matchlist
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			$this->includes();
			$this->setup_hooks();
		}

		/**
		 * Prevent cloning.
		 */
		private function __clone() {}

		/**
		 * Prevent unserializing.
		 *
		 * @throws Exception If someone tries to unserialize the singleton.
		 * @return void
		 */
		public function __wakeup() {
			throw new Exception( 'Cannot unserialize singleton.' );
		}

		/**
		 * Load required files.
		 *
		 * @return void
		 */
		private function includes() {
			require_once TASO_MATCHLIST_PLUGIN_DIR . 'includes/class-taso-matchlist-api.php';
			require_once TASO_MATCHLIST_PLUGIN_DIR . 'includes/class-taso-matchlist-matches.php';
			require_once TASO_MATCHLIST_PLUGIN_DIR . 'includes/class-taso-matchlist-settings.php';
			require_once TASO_MATCHLIST_PLUGIN_DIR . 'includes/class-taso-matchlist-frontend.php';
		}

		/**
		 * Register hooks.
		 *
		 * @return void
		 */
		private function setup_hooks() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Load plugin translations.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain(
				'taso-matchlist',
				false,
				dirname( TASO_MATCHLIST_BASENAME ) . '/languages'
			);
		}

		/**
		 * Plugin init hook.
		 *
		 * @return void
		 */
		public function init() {
			$this->maybe_upgrade();
			$this->init_components();
		}

		/**
		 * Initialize plugin components.
		 *
		 * @return void
		 */
		private function init_components() {
			if ( null !== $this->api ) {
				return;
			}

			$this->api      = new Taso_Matchlist_API();
			$this->matches  = new Taso_Matchlist_Matches( $this->api );
			$this->frontend = new Taso_Matchlist_Frontend( $this->matches );

			if ( is_admin() ) {
				$this->settings = new Taso_Matchlist_Settings( $this->api, $this->matches );
			}
		}

		/**
		 * Handles version upgrades.
		 *
		 * @return void
		 */
		private function maybe_upgrade() {
			$stored_version = get_option( 'taso_matchlist_version', '' );

			if ( TASO_MATCHLIST_VERSION !== $stored_version ) {
				update_option( 'taso_matchlist_version', TASO_MATCHLIST_VERSION );
			}
		}
	}
}