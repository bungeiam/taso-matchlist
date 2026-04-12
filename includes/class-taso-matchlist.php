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
			$this->setup_hooks();
		}

		/**
		 * Prevent cloning.
		 */
		private function __clone() {}

		/**
		 * Prevent unserializing.
		 */
		public function __wakeup() {
			throw new Exception( 'Cannot unserialize singleton.' );
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