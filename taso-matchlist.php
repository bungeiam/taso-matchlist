<?php
/**
 * Plugin Name:       Taso Matchlist
 * Plugin URI:        https://github.com/bungeiam/taso-matchlist
 * Description:       Hakee TASO REST API:sta Union Plaanin otteluita ja näyttää ne WordPress-sivulla.
 * Version:           0.1.0
 * Author:            Joona
 * Author URI:        https://github.com/bungeiam
 * Text Domain:       taso-matchlist
 * Domain Path:       /languages
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TASO_MATCHLIST_VERSION', '0.1.0' );
define( 'TASO_MATCHLIST_PLUGIN_FILE', __FILE__ );
define( 'TASO_MATCHLIST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TASO_MATCHLIST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TASO_MATCHLIST_BASENAME', plugin_basename( __FILE__ ) );

require_once TASO_MATCHLIST_PLUGIN_DIR . 'includes/class-taso-matchlist.php';

/**
 * Starts the plugin.
 *
 * @return Taso_Matchlist
 */
function taso_matchlist() {
	return Taso_Matchlist::instance();
}

taso_matchlist();

/**
 * Runs on plugin activation.
 *
 * @return void
 */
function taso_matchlist_activate() {
	if ( get_option( 'taso_matchlist_version' ) !== TASO_MATCHLIST_VERSION ) {
		update_option( 'taso_matchlist_version', TASO_MATCHLIST_VERSION );
	}
}
register_activation_hook( __FILE__, 'taso_matchlist_activate' );