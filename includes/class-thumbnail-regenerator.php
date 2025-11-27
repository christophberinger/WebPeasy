<?php
/**
 * Thumbnail Regenerator
 *
 * @package WebPEasy
 */

namespace WebPEasy;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Thumbnail_Regenerator
 *
 * Handles bulk regeneration of thumbnails for existing images.
 */
class Thumbnail_Regenerator {

	/**
	 * Get total count of images in media library.
	 *
	 * @return int Total number of images.
	 */
	public function get_total_images() {
		$query = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/gif' ),
				'post_status'    => 'inherit',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
			)
		);

		return $query->found_posts;
	}

	/**
	 * Get batch of image IDs to process.
	 *
	 * @param int $offset Starting offset.
	 * @param int $limit  Number of images to retrieve.
	 * @return array Array of attachment IDs.
	 */
	public function get_image_batch( $offset = 0, $limit = 5 ) {
		$query = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => array( 'image/jpeg', 'image/png', 'image/gif' ),
				'post_status'    => 'inherit',
				'posts_per_page' => $limit,
				'offset'         => $offset,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		return $query->posts;
	}

	/**
	 * Regenerate thumbnails for a single attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|WP_Error Result array with success status and message.
	 */
	public function regenerate_attachment( $attachment_id ) {
		// Verify attachment exists and is an image.
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return new \WP_Error(
				'invalid_attachment',
				__( 'Attachment is not an image.', 'WebPeasy' )
			);
		}

		// Get the file path.
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new \WP_Error(
				'file_not_found',
				__( 'Image file not found.', 'WebPeasy' )
			);
		}

		// Note: We keep original thumbnails and generate WebP versions alongside them.
		// This allows .htaccess rewrite rules to serve WebP when available,
		// while old JPG/PNG URLs continue to work as fallbacks.

		// Require image functions.
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Regenerate thumbnails (will create WebP versions alongside originals).
		$new_metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );

		if ( is_wp_error( $new_metadata ) ) {
			return $new_metadata;
		}

		if ( empty( $new_metadata ) ) {
			return new \WP_Error(
				'regeneration_failed',
				__( 'Failed to generate attachment metadata.', 'WebPeasy' )
			);
		}

		// Update metadata.
		wp_update_attachment_metadata( $attachment_id, $new_metadata );

		return array(
			'success'       => true,
			'attachment_id' => $attachment_id,
			'message'       => __( 'Thumbnails regenerated successfully.', 'WebPeasy' ),
		);
	}

	/**
	 * Process a batch of images.
	 *
	 * @param int $offset Starting offset.
	 * @param int $limit  Number of images to process.
	 * @return array Result array with processed count and any errors.
	 */
	public function process_batch( $offset = 0, $limit = 5 ) {
		$images = $this->get_image_batch( $offset, $limit );

		$results = array(
			'processed' => 0,
			'errors'    => 0,
			'messages'  => array(),
		);

		foreach ( $images as $attachment_id ) {
			$result = $this->regenerate_attachment( $attachment_id );

			if ( is_wp_error( $result ) ) {
				$results['errors']++;
				$results['messages'][] = sprintf(
					/* translators: 1: Attachment ID, 2: Error message */
					__( 'Failed to process attachment #%1$d: %2$s', 'WebPeasy' ),
					$attachment_id,
					$result->get_error_message()
				);
			} else {
				$results['processed']++;
			}
		}

		return $results;
	}
}
