<?php
/**
 * WebP Support Detector
 *
 * @package WebPEasy
 */

namespace WebPEasy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Support_Detector
 *
 * Detects WebP support in the current PHP environment.
 */
class Support_Detector {

	/**
	 * Cached WebP support status.
	 *
	 * @var bool|null
	 */
	private static $webp_supported = null;

	/**
	 * Check if WebP is supported by the current PHP image library.
	 *
	 * @return bool True if WebP is supported, false otherwise.
	 */
	public static function supports_webp() {
		// Return cached result if available.
		if ( null !== self::$webp_supported ) {
			return self::$webp_supported;
		}

		// Check for Imagick support.
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			try {
				$formats = \Imagick::queryFormats( 'WEBP' );
				if ( is_array( $formats ) && in_array( 'WEBP', $formats, true ) ) {
					self::$webp_supported = true;
					return true;
				}
			} catch ( \Exception $e ) {
				// Imagick query failed, try GD.
			}
		}

		// Check for GD support.
		if ( function_exists( 'imagewebp' ) ) {
			self::$webp_supported = true;
			return true;
		}

		// No WebP support found.
		self::$webp_supported = false;
		return false;
	}

	/**
	 * Get the name of the image library being used.
	 *
	 * @return string 'imagick', 'gd', or 'none'.
	 */
	public static function get_image_library() {
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			return 'imagick';
		}

		if ( function_exists( 'gd_info' ) ) {
			return 'gd';
		}

		return 'none';
	}

	/**
	 * Get detailed support information for display.
	 *
	 * @return array {
	 *     Detailed support information.
	 *
	 *     @type bool   $supported     Whether WebP is supported.
	 *     @type string $library       Image library name.
	 *     @type string $library_label Formatted library label.
	 * }
	 */
	public static function get_support_info() {
		$supported = self::supports_webp();
		$library   = self::get_image_library();

		$library_labels = array(
			'imagick' => 'ImageMagick',
			'gd'      => 'GD Library',
			'none'    => __( 'No image library detected', 'WebPeasy' ),
		);

		return array(
			'supported'     => $supported,
			'library'       => $library,
			'library_label' => $library_labels[ $library ],
		);
	}
}
