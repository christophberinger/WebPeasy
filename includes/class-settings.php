<?php
/**
 * Settings Manager
 *
 * @package WebPEasy
 */

namespace WebPEasy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Manages plugin settings using the WordPress Settings API.
 */
class Settings {

	/**
	 * Option name in wp_options table.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'webpeasy_settings';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = array(
		'webp_quality' => 82,
	);

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private $settings = null;

	/**
	 * Get a setting value.
	 *
	 * @param string|null $key     Setting key. If null, returns all settings.
	 * @param mixed       $default Default value if key doesn't exist.
	 * @return mixed Setting value or default.
	 */
	public function get( $key = null, $default = null ) {
		// Load settings if not cached.
		if ( null === $this->settings ) {
			$this->settings = get_option( self::OPTION_NAME, array() );

			// Ensure we have an array.
			if ( ! is_array( $this->settings ) ) {
				$this->settings = array();
			}

			// Merge with defaults.
			$this->settings = wp_parse_args( $this->settings, $this->defaults );
		}

		// Return all settings if no key specified.
		if ( null === $key ) {
			return $this->settings;
		}

		// Return specific setting or default.
		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		// Check if there's a default for this key.
		if ( null === $default && isset( $this->defaults[ $key ] ) ) {
			return $this->defaults[ $key ];
		}

		return $default;
	}

	/**
	 * Update settings.
	 *
	 * @param array $values Settings values to update.
	 * @return bool True if settings were updated successfully.
	 */
	public function update( $values ) {
		if ( ! is_array( $values ) ) {
			return false;
		}

		// Get current settings.
		$current = $this->get();

		// Sanitize and merge new values.
		$sanitized = $this->sanitize( $values );
		$updated   = array_merge( $current, $sanitized );

		// Update option.
		$result = update_option( self::OPTION_NAME, $updated );

		// Clear cache.
		$this->settings = null;

		return $result;
	}

	/**
	 * Sanitize settings values.
	 *
	 * @param array $values Raw settings values.
	 * @return array Sanitized settings values.
	 */
	public function sanitize( $values ) {
		$sanitized = array();

		// Sanitize webp_quality.
		if ( isset( $values['webp_quality'] ) ) {
			$quality = absint( $values['webp_quality'] );
			// Clamp between 0 and 100.
			$sanitized['webp_quality'] = max( 0, min( 100, $quality ) );
		}

		return $sanitized;
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @return bool True if settings were reset successfully.
	 */
	public function reset() {
		$result = update_option( self::OPTION_NAME, $this->defaults );

		// Clear cache.
		$this->settings = null;

		return $result;
	}

	/**
	 * Register settings with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		register_setting(
			'webpeasy_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults,
			)
		);
	}
}
