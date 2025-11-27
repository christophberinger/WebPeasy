<?php
/**
 * Plugin Name: WebPeasy
 * Plugin URI: https://beringer.io/webpeasy
 * Description: Automatically delivers frontend images as WebP with configurable compression quality, while leaving the Media Library and original files untouched.
 * Version: 1.0.0
 * Author: Beringer
 * Author URI: https://beringer.io
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: WebPeasy
 * Domain Path: /languages
 *
 * @package WebPEasy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WEBPEASY_VERSION', '1.0.0' );
define( 'WEBPEASY_FILE', __FILE__ );
define( 'WEBPEASY_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEBPEASY_URL', plugin_dir_url( __FILE__ ) );

// Require the main plugin class.
require_once WEBPEASY_PATH . 'includes/class-plugin.php';

/**
 * Bootstrap the plugin.
 *
 * @return void
 */
function webpeasy_bootstrap() {
	WebPEasy\Plugin::load( WEBPEASY_FILE );
}

// Initialize the plugin.
add_action( 'plugins_loaded', 'webpeasy_bootstrap' );
