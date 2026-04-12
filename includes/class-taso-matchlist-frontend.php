<?php
/**
 * Frontend rendering for Taso Matchlist.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Taso_Matchlist_Frontend' ) ) {
	/**
	 * Frontend renderer.
	 */
	class Taso_Matchlist_Frontend {

		/**
		 * Shortcode name.
		 */
		const SHORTCODE = 'taso_matchlist';

		/**
		 * Style handle.
		 */
		const STYLE_HANDLE = 'taso-matchlist';

		/**
		 * Matches service.
		 *
		 * @var Taso_Matchlist_Matches
		 */
		private $matches;

		/**
		 * Constructor.
		 *
		 * @param Taso_Matchlist_Matches $matches Matches service.
		 */
		public function __construct( $matches ) {
			$this->matches = $matches;

			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
			add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
		}

		/**
		 * Register frontend styles.
		 *
		 * @return void
		 */
		public function register_assets() {
			wp_register_style(
				self::STYLE_HANDLE,
				TASO_MATCHLIST_PLUGIN_URL . 'assets/css/taso-matchlist.css',
				array(),
				TASO_MATCHLIST_VERSION
			);
		}

		/**
		 * Render shortcode output.
		 *
		 * @param array|string $atts Shortcode attributes.
		 * @return string
		 */
		public function render_shortcode( $atts = array() ) {
			unset( $atts );

			wp_enqueue_style( self::STYLE_HANDLE );

			$groups = $this->matches->get_home_matches();
			$error  = null;

			if ( is_wp_error( $groups ) ) {
				$error  = $groups;
				$groups = array();
			}

			$template_path = TASO_MATCHLIST_PLUGIN_DIR . 'templates/match-list.php';

			if ( ! file_exists( $template_path ) ) {
				return '';
			}

			ob_start();
			require $template_path;
			return (string) ob_get_clean();
		}
	}
}