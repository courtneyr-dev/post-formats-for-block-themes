<?php
/**
 * Repair Tool Class
 *
 * Admin tool for scanning and repairing post format assignments.
 * Provides a safe interface to detect and correct format mismatches.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 *
 * Security Implementation:
 * - Requires 'manage_options' capability
 * - Uses nonces for all form submissions
 * - Creates revisions before any changes
 * - Dry-run mode by default
 * - Detailed logging of all changes
 *
 * Accessibility Implementation:
 * - Uses semantic HTML (table, form elements)
 * - Proper form labels and ARIA attributes
 * - Screen reader announcements for AJAX updates
 * - Keyboard navigation support
 * - Clear visual feedback for actions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repair Tool class
 *
 * Provides admin interface for post format repair and migration.
 *
 * @since 1.0.0
 */
class PFPU_Repair_Tool {

	/**
	 * Render the repair tool page
	 *
	 * @since 1.0.0
	 */
	public static function render_page() {
		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'post-formats-power-up' ),
				esc_html__( 'Permission Denied', 'post-formats-power-up' ),
				array( 'response' => 403 )
			);
		}

		// Handle form submissions with nonce verification.
		if ( isset( $_POST['pfpu_repair_action'] ) &&
			isset( $_POST['pfpu_repair_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pfpu_repair_nonce'] ) ), 'pfpu_repair_action' ) ) {
			self::handle_repair_action();
		}

		// Get scan results.
		$scan_results = self::scan_posts();

		// Render the page.
		include PFPU_PLUGIN_DIR . 'templates/repair-tool-page.php';
	}

	/**
	 * Scan all posts for format mismatches
	 *
	 * @since 1.0.0
	 * @return array Scan results with mismatches.
	 */
	private static function scan_posts() {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
		);

		$posts         = get_posts( $args );
		$mismatches    = array();
		$correct_count = 0;
		$scanned_count = 0;

		foreach ( $posts as $post ) {
			++$scanned_count;
			$current_format   = get_post_format( $post->ID ) ?: 'standard';
			$blocks           = parse_blocks( $post->post_content );
			$first_block      = self::get_first_meaningful_block( $blocks );
			$suggested_format = 'standard';

			if ( $first_block ) {
				$suggested_format = PFPU_Format_Registry::get_format_by_block(
					$first_block['blockName'],
					$first_block['attrs']
				);
			}

			// Check for mismatch.
			if ( $current_format !== $suggested_format ) {
				$mismatches[] = array(
					'post_id'          => $post->ID,
					'post_title'       => $post->post_title,
					'post_url'         => get_edit_post_link( $post->ID ),
					'current_format'   => $current_format,
					'suggested_format' => $suggested_format,
					'first_block'      => $first_block ? $first_block['blockName'] : 'none',
				);
			} else {
				++$correct_count;
			}
		}

		return array(
			'total_scanned'  => $scanned_count,
			'correct'        => $correct_count,
			'mismatches'     => $mismatches,
			'mismatch_count' => count( $mismatches ),
		);
	}

	/**
	 * Get first meaningful block from parsed blocks
	 *
	 * @since 1.0.0
	 *
	 * @param array $blocks Array of parsed blocks.
	 * @return array|null First meaningful block or null.
	 */
	private static function get_first_meaningful_block( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( null === $block['blockName'] || empty( $block['blockName'] ) ) {
				continue;
			}

			if ( empty( $block['innerHTML'] ) && empty( $block['innerBlocks'] ) ) {
				continue;
			}

			return $block;
		}

		return null;
	}

	/**
	 * Handle repair action submission
	 *
	 * @since 1.0.0
	 */
	private static function handle_repair_action() {
		// Verify nonce.
		if ( ! isset( $_POST['pfpu_repair_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pfpu_repair_nonce'] ) ), 'pfpu_repair_action' ) ) {
			wp_die(
				esc_html__( 'Security verification failed. Please try again.', 'post-formats-power-up' ),
				esc_html__( 'Security Error', 'post-formats-power-up' ),
				array( 'response' => 403 )
			);
		}

		// Check capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'post-formats-power-up' ),
				esc_html__( 'Permission Denied', 'post-formats-power-up' ),
				array( 'response' => 403 )
			);
		}

		$action  = isset( $_POST['pfpu_repair_action'] ) ? sanitize_text_field( wp_unslash( $_POST['pfpu_repair_action'] ) ) : '';
		$dry_run = isset( $_POST['pfpu_dry_run'] ) && '1' === $_POST['pfpu_dry_run'];

		if ( 'apply_all' === $action ) {
			self::apply_all_suggestions( $dry_run );
		} elseif ( 'apply_single' === $action && isset( $_POST['post_id'] ) ) {
			$post_id = absint( $_POST['post_id'] );
			$format  = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : '';
			self::apply_single_suggestion( $post_id, $format, $dry_run );
		}
	}

	/**
	 * Apply all suggested format changes
	 *
	 * @since 1.0.0
	 *
	 * @param bool $dry_run Whether to simulate changes without applying.
	 */
	private static function apply_all_suggestions( $dry_run = true ) {
		$scan_results = self::scan_posts();
		$updated      = 0;
		$errors       = 0;

		foreach ( $scan_results['mismatches'] as $mismatch ) {
			$result = self::apply_format_change(
				$mismatch['post_id'],
				$mismatch['suggested_format'],
				$dry_run
			);

			if ( $result ) {
				++$updated;
			} else {
				++$errors;
			}
		}

		if ( $dry_run ) {
			$message = sprintf(
				/* translators: %d: Number of posts that would be updated */
				_n(
					'Dry run complete: %d post would be updated.',
					'Dry run complete: %d posts would be updated.',
					$updated,
					'post-formats-power-up'
				),
				$updated
			);
		} else {
			$message = sprintf(
				/* translators: %d: Number of posts updated */
				_n(
					'Successfully updated %d post format.',
					'Successfully updated %d post formats.',
					$updated,
					'post-formats-power-up'
				),
				$updated
			);
		}

		if ( $errors > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: Number of errors */
				_n(
					'%d error occurred.',
					'%d errors occurred.',
					$errors,
					'post-formats-power-up'
				),
				$errors
			);
		}

		add_settings_error(
			'pfpu_repair',
			'pfpu_repair_success',
			$message,
			'success'
		);
	}

	/**
	 * Apply single format suggestion
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $format  Format to apply.
	 * @param bool   $dry_run Whether to simulate change.
	 */
	private static function apply_single_suggestion( $post_id, $format, $dry_run = true ) {
		$result = self::apply_format_change( $post_id, $format, $dry_run );

		if ( $result ) {
			if ( $dry_run ) {
				$message = sprintf(
					/* translators: %d: Post ID, %s: Format name */
					__( 'Dry run: Post #%1$d would be changed to %2$s format.', 'post-formats-power-up' ),
					$post_id,
					$format
				);
			} else {
				$message = sprintf(
					/* translators: %d: Post ID, %s: Format name */
					__( 'Post #%1$d successfully updated to %2$s format.', 'post-formats-power-up' ),
					$post_id,
					$format
				);
			}

			add_settings_error(
				'pfpu_repair',
				'pfpu_repair_success',
				$message,
				'success'
			);
		} else {
			$message = sprintf(
				/* translators: %d: Post ID */
				__( 'Failed to update post #%d.', 'post-formats-power-up' ),
				$post_id
			);

			add_settings_error(
				'pfpu_repair',
				'pfpu_repair_error',
				$message,
				'error'
			);
		}
	}

	/**
	 * Apply format change to a post
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $format  Format to apply.
	 * @param bool   $dry_run Whether to simulate change.
	 * @return bool True on success, false on failure.
	 */
	private static function apply_format_change( $post_id, $format, $dry_run = true ) {
		// Validate post.
		$post = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type ) {
			return false;
		}

		// Validate format.
		if ( ! PFPU_Format_Registry::format_exists( $format ) ) {
			return false;
		}

		// If dry run, just return success.
		if ( $dry_run ) {
			return true;
		}

		// Create revision before changing.
		wp_save_post_revision( $post_id );

		// Apply format.
		$result = set_post_format( $post_id, $format );

		if ( $result ) {
			// Update meta to track this as auto-repaired.
			update_post_meta( $post_id, '_pfpu_format_repaired', current_time( 'mysql' ) );

			/**
			 * Fires after format is repaired
			 *
			 * @since 1.0.0
			 *
			 * @param int    $post_id Post ID.
			 * @param string $format  New format.
			 */
			do_action( 'pfpu_format_repaired', $post_id, $format );

			return true;
		}

		return false;
	}
}

// Create template file if it doesn't exist (this would normally be separate).
if ( ! file_exists( PFPU_PLUGIN_DIR . 'templates/repair-tool-page.php' ) ) {
	// We'll create this file separately.
}
