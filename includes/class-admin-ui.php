<?php
/**
 * Admin UI
 *
 * @package WebPEasy
 */

namespace WebPEasy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_UI
 *
 * Handles the admin interface for the plugin.
 */
class Admin_UI {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Thumbnail regenerator instance.
	 *
	 * @var Thumbnail_Regenerator
	 */
	private $regenerator;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings    = $settings;
		$this->regenerator = new Thumbnail_Regenerator();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_notices', array( $this, 'show_webp_support_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// AJAX endpoints for thumbnail regeneration.
		add_action( 'wp_ajax_webpeasy_get_image_count', array( $this, 'ajax_get_image_count' ) );
		add_action( 'wp_ajax_webpeasy_regenerate_batch', array( $this, 'ajax_regenerate_batch' ) );
	}

	/**
	 * Add settings page to WordPress admin.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'WebPeasy Settings', 'webpeasy' ),
			__( 'WebPeasy', 'webpeasy' ),
			'manage_options',
			'webpeasy',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings with WordPress.
	 *
	 * @return void
	 */
	public function register_settings() {
		$this->settings->register();
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		// Only load on our settings page.
		if ( 'settings_page_webpeasy' !== $hook ) {
			return;
		}

		// Add inline styles for better UI.
		$css = '
		.webpeasy-settings {
			max-width: 800px;
		}
		.webpeasy-settings .status-box {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-left: 4px solid #2271b1;
			padding: 15px;
			margin: 20px 0;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}
		.webpeasy-settings .status-box.unsupported {
			border-left-color: #d63638;
		}
		.webpeasy-settings .status-box h3 {
			margin-top: 0;
			margin-bottom: 10px;
		}
		.webpeasy-settings .status-item {
			margin: 8px 0;
			display: flex;
			align-items: center;
		}
		.webpeasy-settings .status-item .dashicons {
			margin-right: 8px;
		}
		.webpeasy-settings .status-item .dashicons-yes {
			color: #00a32a;
		}
		.webpeasy-settings .status-item .dashicons-no {
			color: #d63638;
		}
		.webpeasy-settings .form-table th {
			width: 200px;
		}
		.webpeasy-settings .quality-input-wrapper {
			display: flex;
			align-items: center;
			gap: 15px;
		}
		.webpeasy-settings .quality-slider {
			width: 300px;
		}
		.webpeasy-settings .quality-value {
			font-weight: 600;
			min-width: 40px;
		}
		.webpeasy-settings .description {
			margin-top: 8px;
		}
		.webpeasy-settings .info-box {
			background: #f0f6fc;
			border: 1px solid #c3c4c7;
			border-left: 4px solid #2271b1;
			padding: 15px;
			margin: 20px 0;
		}
		.webpeasy-settings .info-box h4 {
			margin-top: 0;
			margin-bottom: 10px;
		}
		.webpeasy-settings .info-box ul {
			margin: 10px 0;
			padding-left: 20px;
		}
		@keyframes rotation {
			from {
				transform: rotate(0deg);
			}
			to {
				transform: rotate(359deg);
			}
		}
		';

		wp_add_inline_style( 'wp-admin', $css );
	}

	/**
	 * Show admin notice if WebP is not supported.
	 *
	 * @return void
	 */
	public function show_webp_support_notice() {
		// Only show on relevant admin pages.
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'upload', 'settings_page_webpeasy' ), true ) ) {
			return;
		}

		// Check if user has dismissed the notice.
		$dismissed = get_user_meta( get_current_user_id(), 'webpeasy_webp_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		// Show notice only if WebP is not supported.
		if ( ! Support_Detector::supports_webp() ) {
			$info = Support_Detector::get_support_info();
			?>
			<div class="notice notice-warning is-dismissible" data-notice="webpeasy-webp">
				<p>
					<strong><?php esc_html_e( 'WebPeasy:', 'webpeasy' ); ?></strong>
					<?php
					printf(
						/* translators: %s: Image library name */
						esc_html__( 'WebP format is not supported by your current PHP configuration (%s). The plugin is active but will not convert images to WebP until WebP support is available.', 'webpeasy' ),
						esc_html( $info['library_label'] )
					);
					?>
				</p>
				<p>
					<?php esc_html_e( 'Please contact your hosting provider to enable WebP support in ImageMagick or GD.', 'webpeasy' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get support info.
		$support_info = Support_Detector::get_support_info();
		$webp_quality = $this->settings->get( 'webp_quality' );

		?>
		<div class="wrap webpeasy-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors( 'webpeasy_messages' ); ?>

			<!-- Status Box -->
			<div class="status-box <?php echo $support_info['supported'] ? '' : 'unsupported'; ?>">
				<h3><?php esc_html_e( 'WebP Support Status', 'webpeasy' ); ?></h3>

				<div class="status-item">
					<span class="dashicons <?php echo $support_info['supported'] ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
					<strong><?php esc_html_e( 'WebP Support:', 'webpeasy' ); ?></strong>
					<span style="margin-left: 8px;">
						<?php
						if ( $support_info['supported'] ) {
							esc_html_e( 'Enabled', 'webpeasy' );
						} else {
							esc_html_e( 'Disabled', 'webpeasy' );
						}
						?>
					</span>
				</div>

				<div class="status-item">
					<span class="dashicons dashicons-admin-settings"></span>
					<strong><?php esc_html_e( 'Image Library:', 'webpeasy' ); ?></strong>
					<span style="margin-left: 8px;"><?php echo esc_html( $support_info['library_label'] ); ?></span>
				</div>

				<?php if ( $support_info['supported'] ) : ?>
					<div class="status-item">
						<span class="dashicons dashicons-images-alt2"></span>
						<strong><?php esc_html_e( 'Active Conversions:', 'webpeasy' ); ?></strong>
						<span style="margin-left: 8px;">
							<?php esc_html_e( 'JPEG → WebP, PNG → WebP', 'webpeasy' ); ?>
						</span>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! $support_info['supported'] ) : ?>
				<div class="notice notice-error inline">
					<p>
						<strong><?php esc_html_e( 'WebP conversion is currently disabled.', 'webpeasy' ); ?></strong>
						<?php esc_html_e( 'The settings below will have no effect until WebP support is enabled in your PHP environment.', 'webpeasy' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<!-- Settings Form -->
			<form action="options.php" method="post">
				<?php
				settings_fields( 'webpeasy_settings_group' );
				?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="webp_quality">
									<?php esc_html_e( 'WebP Quality', 'webpeasy' ); ?>
								</label>
							</th>
							<td>
								<div class="quality-input-wrapper">
									<input
										type="range"
										id="webp_quality_slider"
										class="quality-slider"
										min="0"
										max="100"
										step="1"
										value="<?php echo esc_attr( $webp_quality ); ?>"
										<?php disabled( ! $support_info['supported'] ); ?>
									/>
									<input
										type="number"
										name="webpeasy_settings[webp_quality]"
										id="webp_quality"
										class="small-text quality-value"
										min="0"
										max="100"
										step="1"
										value="<?php echo esc_attr( $webp_quality ); ?>"
										<?php disabled( ! $support_info['supported'] ); ?>
									/>
								</div>
								<p class="description">
									<?php
									esc_html_e( 'Set the compression quality for WebP images (0-100). Higher values produce better quality but larger file sizes. Recommended: 80-85.', 'webpeasy' );
									?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Save Settings', 'webpeasy' ) ); ?>
			</form>

			<!-- Information Box -->
			<div class="info-box">
				<h4><?php esc_html_e( 'How It Works', 'webpeasy' ); ?></h4>
				<ul>
					<li><?php esc_html_e( 'Original files in your Media Library remain untouched (JPEG, PNG, etc.)', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'All new image sizes (thumbnails, medium, large) are automatically generated as WebP', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'Frontend images are served as WebP for better performance', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'No changes to attachment metadata or database structure', 'webpeasy' ); ?></li>
				</ul>

				<h4><?php esc_html_e( 'Regenerate Existing Images', 'webpeasy' ); ?></h4>
				<p>
					<?php esc_html_e( 'Generate WebP versions for all existing images. This plugin uses smart PHP-based URL rewriting:', 'webpeasy' ); ?>
				</p>
				<ul style="margin-left: 20px; margin-bottom: 15px;">
					<li><?php esc_html_e( 'Generates WebP versions alongside original JPG/PNG thumbnails', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'Automatically replaces URLs with WebP when browsers support it', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'Falls back to original format for older browsers', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'Works on any server (Apache, Nginx, etc.) - no .htaccess needed', 'webpeasy' ); ?></li>
					<li><?php esc_html_e( 'No database modifications - your original URLs are preserved', 'webpeasy' ); ?></li>
				</ul>
				<p style="background: #d1ecf1; border-left: 4px solid #0c5460; padding: 10px; margin: 15px 0;">
					<strong><?php esc_html_e( 'How it works:', 'webpeasy' ); ?></strong>
					<?php esc_html_e( 'The plugin intercepts page output and automatically replaces image.jpg URLs with image.webp if the WebP file exists and the browser supports it. Everything happens transparently!', 'webpeasy' ); ?>
				</p>

				<div style="margin: 20px 0;">
					<button type="button" id="webpeasy-regenerate-btn" class="button button-primary">
						<?php esc_html_e( 'Regenerate Thumbnails', 'webpeasy' ); ?>
					</button>
				</div>

				<div id="webpeasy-regeneration-progress" style="display: none; margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px;">
					<div style="margin-bottom: 10px;">
						<strong id="webpeasy-progress-status">
							<span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span>
							<?php esc_html_e( 'Processing images...', 'webpeasy' ); ?>
						</strong>
					</div>
					<div style="background: #f0f0f1; border-radius: 4px; height: 24px; position: relative; overflow: hidden;">
						<div id="webpeasy-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s ease;"></div>
						<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: space-between; padding: 0 10px; font-size: 12px; font-weight: 600;">
							<span id="webpeasy-progress-text">0 / 0</span>
							<span id="webpeasy-progress-percent">0%</span>
						</div>
					</div>
					<div style="margin-top: 10px;">
						<button type="button" id="webpeasy-cancel-btn" class="button">
							<?php esc_html_e( 'Cancel', 'webpeasy' ); ?>
						</button>
					</div>
				</div>
			</div>

			<script>
			(function() {
				// Sync slider and number input
				const slider = document.getElementById('webp_quality_slider');
				const numberInput = document.getElementById('webp_quality');

				if (slider && numberInput) {
					slider.addEventListener('input', function() {
						numberInput.value = this.value;
					});

					numberInput.addEventListener('input', function() {
						slider.value = this.value;
					});
				}
			})();
			</script>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our settings page.
		if ( 'settings_page_webpeasy' !== $hook ) {
			return;
		}

		// Ensure jQuery is loaded.
		wp_enqueue_script( 'jquery' );

		// Create a nonce for AJAX requests.
		$nonce = wp_create_nonce( 'webpeasy_regenerate' );

		// Inline script for thumbnail regeneration.
		$script = "
jQuery(document).ready(function($) {
	'use strict';

	let isProcessing = false;
	let totalImages = 0;
	let processedImages = 0;

	// Start regeneration
	$('#webpeasy-regenerate-btn').on('click', function(e) {
		e.preventDefault();

		if (isProcessing) {
			return;
		}

		if (!confirm('" . esc_js( __( 'This will generate WebP versions for all images in your media library. Original files will be preserved. This may take a while. Continue?', 'webpeasy' ) ) . "')) {
			return;
		}

		startRegeneration();
	});

	// Cancel regeneration
	$('#webpeasy-cancel-btn').on('click', function(e) {
		e.preventDefault();
		cancelRegeneration();
	});

	function startRegeneration() {
		isProcessing = true;
		processedImages = 0;

		// Show progress container
		$('#webpeasy-regeneration-progress').show();
		$('#webpeasy-regenerate-btn').prop('disabled', true);
		$('#webpeasy-progress-status').html('<span class=\"dashicons dashicons-update\" style=\"animation: rotation 2s infinite linear;\"></span> " . esc_js( __( 'Processing images...', 'webpeasy' ) ) . "');
		$('#webpeasy-cancel-btn').show();

		// Get total image count
		$.post(ajaxurl, {
			action: 'webpeasy_get_image_count',
			nonce: '" . esc_js( $nonce ) . "'
		}, function(response) {
			if (response.success) {
				totalImages = response.data.total;

				if (totalImages === 0) {
					showError('" . esc_js( __( 'No images found in media library.', 'webpeasy' ) ) . "');
					return;
				}

				updateProgress();
				processNextBatch();
			} else {
				showError(response.data.message || '" . esc_js( __( 'Failed to get image count.', 'webpeasy' ) ) . "');
			}
		}).fail(function() {
			showError('" . esc_js( __( 'Failed to connect to server.', 'webpeasy' ) ) . "');
		});
	}

	function processNextBatch() {
		if (!isProcessing) {
			return;
		}

		if (processedImages >= totalImages) {
			completeRegeneration();
			return;
		}

		$.post(ajaxurl, {
			action: 'webpeasy_regenerate_batch',
			nonce: '" . esc_js( $nonce ) . "',
			offset: processedImages,
			limit: 5
		}, function(response) {
			if (response.success) {
				processedImages += response.data.processed;
				updateProgress();
				setTimeout(processNextBatch, 100);
			} else {
				showError(response.data.message || '" . esc_js( __( 'Failed to process batch.', 'webpeasy' ) ) . "');
			}
		}).fail(function() {
			showError('" . esc_js( __( 'Failed to connect to server.', 'webpeasy' ) ) . "');
		});
	}

	function updateProgress() {
		const percent = totalImages > 0 ? Math.round((processedImages / totalImages) * 100) : 0;
		$('#webpeasy-progress-bar').css('width', percent + '%');
		$('#webpeasy-progress-text').text(processedImages + ' / ' + totalImages);
		$('#webpeasy-progress-percent').text(percent + '%');
	}

	function completeRegeneration() {
		isProcessing = false;
		$('#webpeasy-progress-status').html('<span class=\"dashicons dashicons-yes\" style=\"color: #00a32a;\"></span> " . esc_js( __( 'Regeneration complete!', 'webpeasy' ) ) . "');
		$('#webpeasy-cancel-btn').hide();
		$('#webpeasy-regenerate-btn').prop('disabled', false).text('" . esc_js( __( 'Regenerate Again', 'webpeasy' ) ) . "');
	}

	function cancelRegeneration() {
		isProcessing = false;
		$('#webpeasy-progress-status').html('<span class=\"dashicons dashicons-warning\" style=\"color: #dba617;\"></span> " . esc_js( __( 'Regeneration cancelled.', 'webpeasy' ) ) . "');
		$('#webpeasy-cancel-btn').hide();
		$('#webpeasy-regenerate-btn').prop('disabled', false);
	}

	function showError(message) {
		isProcessing = false;
		$('#webpeasy-progress-status').html('<span class=\"dashicons dashicons-no\" style=\"color: #d63638;\"></span> " . esc_js( __( 'Error:', 'webpeasy' ) ) . " ' + message);
		$('#webpeasy-cancel-btn').hide();
		$('#webpeasy-regenerate-btn').prop('disabled', false);
		$('#webpeasy-regeneration-progress').show();
	}
});
		";

		wp_add_inline_script( 'jquery', $script );
	}

	/**
	 * AJAX handler to get total image count.
	 *
	 * @return void
	 */
	public function ajax_get_image_count() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'webpeasy_regenerate', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'webpeasy' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'webpeasy' ),
				)
			);
		}

		$total = $this->regenerator->get_total_images();

		wp_send_json_success(
			array(
				'total' => $total,
			)
		);
	}

	/**
	 * AJAX handler to regenerate a batch of images.
	 *
	 * @return void
	 */
	public function ajax_regenerate_batch() {
		// Verify nonce.
		if ( ! check_ajax_referer( 'webpeasy_regenerate', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'webpeasy' ),
				)
			);
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'webpeasy' ),
				)
			);
		}

		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$limit  = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 5;

		// Ensure reasonable batch size.
		$limit = min( $limit, 10 );

		$result = $this->regenerator->process_batch( $offset, $limit );

		wp_send_json_success(
			array(
				'processed' => $result['processed'],
				'errors'    => $result['errors'],
				'messages'  => $result['messages'],
			)
		);
	}
}
