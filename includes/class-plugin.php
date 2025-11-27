<?php
/**
 * Main Plugin Class
 *
 * @package WebPEasy
 */

namespace WebPEasy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Require dependencies.
require_once __DIR__ . '/class-support-detector.php';
require_once __DIR__ . '/class-settings.php';
require_once __DIR__ . '/class-thumbnail-regenerator.php';
require_once __DIR__ . '/class-admin-ui.php';

/**
 * Class Plugin
 *
 * Main plugin orchestrator that wires all components together.
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Main plugin file path.
	 *
	 * @var string
	 */
	private $main_file;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Admin UI instance.
	 *
	 * @var Admin_UI
	 */
	private $admin_ui;

	/**
	 * Constructor.
	 *
	 * @param string $main_file Main plugin file path.
	 */
	private function __construct( $main_file ) {
		$this->main_file = $main_file;
		$this->init_components();
		$this->register_hooks();
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components() {
		// Initialize Settings.
		$this->settings = new Settings();

		// Initialize Admin UI.
		$this->admin_ui = new Admin_UI( $this->settings );
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		// Register admin UI hooks.
		$this->admin_ui->register();

		// Register image conversion hooks only if WebP is supported.
		if ( Support_Detector::supports_webp() ) {
			add_filter( 'image_editor_output_format', array( $this, 'filter_image_editor_output_format' ), 10, 3 );
			add_filter( 'wp_editor_set_quality', array( $this, 'filter_editor_quality' ), 10, 2 );

			// Enable output buffering for URL rewriting.
			add_action( 'template_redirect', array( $this, 'start_output_buffer' ), 0 );
		}

		// Add plugin action links.
		add_filter( 'plugin_action_links_' . plugin_basename( $this->main_file ), array( $this, 'add_action_links' ) );
	}

	/**
	 * Filter image editor output format to convert to WebP.
	 *
	 * This filter maps source mime types to WebP for all generated image sizes.
	 * The original uploaded file remains unchanged.
	 *
	 * @param array  $formats   Array of mime type mappings (source => target).
	 * @param string $filename  Image filename.
	 * @param string $mime_type Source mime type.
	 * @return array Modified formats array.
	 */
	public function filter_image_editor_output_format( $formats, $filename, $mime_type ) {
		// Only proceed if WebP is supported.
		if ( ! Support_Detector::supports_webp() ) {
			return $formats;
		}

		// Initialize formats array if needed.
		if ( ! is_array( $formats ) ) {
			$formats = array();
		}

		// List of mime types we want to convert to WebP.
		$convertible_types = array(
			'image/jpeg',
			'image/png',
			'image/gif',
		);

		// Don't convert SVG or other vector formats.
		$skip_types = array(
			'image/svg+xml',
			'image/webp', // Already WebP.
		);

		// Skip if this is a type we shouldn't convert.
		if ( in_array( $mime_type, $skip_types, true ) ) {
			return $formats;
		}

		// Convert supported types to WebP.
		if ( in_array( $mime_type, $convertible_types, true ) ) {
			$formats[ $mime_type ] = 'image/webp';
		}

		return $formats;
	}

	/**
	 * Filter image editor quality based on output format.
	 *
	 * Applies the configured WebP quality setting when generating WebP images.
	 *
	 * @param int    $quality   Current quality setting (1-100).
	 * @param string $mime_type Output mime type.
	 * @return int Modified quality setting.
	 */
	public function filter_editor_quality( $quality, $mime_type ) {
		// Only modify quality for WebP images.
		if ( 'image/webp' !== $mime_type ) {
			return $quality;
		}

		// Get configured WebP quality from settings.
		$webp_quality = $this->settings->get( 'webp_quality', 82 );

		// Ensure quality is within valid range.
		$webp_quality = max( 0, min( 100, (int) $webp_quality ) );

		return $webp_quality;
	}

	/**
	 * Add plugin action links on plugins page.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=webpeasy' ),
			__( 'Settings', 'WebPeasy' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Start output buffering to rewrite image URLs.
	 *
	 * @return void
	 */
	public function start_output_buffer() {
		// Only process frontend requests.
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		// Check if browser supports WebP.
		if ( ! $this->browser_supports_webp() ) {
			return;
		}

		ob_start( array( $this, 'replace_images_with_webp' ) );
	}

	/**
	 * Check if browser supports WebP.
	 *
	 * @return bool True if WebP is supported, false otherwise.
	 */
	private function browser_supports_webp() {
		if ( ! isset( $_SERVER['HTTP_ACCEPT'] ) ) {
			return false;
		}

		$http_accept = wp_unslash( $_SERVER['HTTP_ACCEPT'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return strpos( $http_accept, 'image/webp' ) !== false;
	}

	/**
	 * Replace image URLs with WebP versions in HTML output.
	 *
	 * @param string $buffer HTML buffer.
	 * @return string Modified HTML buffer.
	 */
	public function replace_images_with_webp( $buffer ) {
		// Don't process if buffer is empty or not HTML.
		if ( empty( $buffer ) || stripos( $buffer, '<html' ) === false ) {
			return $buffer;
		}

		// Get upload directory info.
		$upload_dir = wp_upload_dir();
		$base_url   = $upload_dir['baseurl'];
		$base_dir   = $upload_dir['basedir'];

		// Pattern to match image URLs in src, srcset, and data attributes.
		$pattern = '#(?<=src="|srcset="|data-src="|data-srcset="|href=")(' . preg_quote( $base_url, '#' ) . '/[^"\']*?\.(jpe?g|png|gif))(?=["\'])#i';

		// Replace callback.
		$buffer = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $base_url, $base_dir ) {
				$original_url = $matches[0];
				$webp_url     = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $original_url );

				// Convert URL to file path.
				$webp_path = str_replace( $base_url, $base_dir, $webp_url );

				// Check if WebP file exists.
				if ( file_exists( $webp_path ) ) {
					return $webp_url;
				}

				// Return original if WebP doesn't exist.
				return $original_url;
			},
			$buffer
		);

		return $buffer;
	}

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin Plugin instance.
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Load and initialize the plugin.
	 *
	 * @param string $main_file Main plugin file path.
	 * @return Plugin Plugin instance.
	 */
	public static function load( $main_file ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $main_file );
		}

		return self::$instance;
	}
}
