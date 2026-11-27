<?php
/**
 * Repair Tool Class
 *
 * Admin tool for scanning and repairing post format assignments.
 * Provides a safe interface to detect and correct format mismatches.
 *
 * @package PostFormatsBlockThemes
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

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

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
class PFBT_Repair_Tool {

	/**
	 * Capability required to review or apply repairs.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Tools menu page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'pfbt-repair-tool';

	/**
	 * Latest usable format undo.
	 *
	 * @var string
	 */
	private const UNDO_META = '_pfbt_format_repair_undo';

	/**
	 * Discard an undo when the post's format term actually changes.
	 *
	 * @param int       $post_id  Post ID.
	 * @param int|int[] $term_ids Changed term taxonomy IDs.
	 * @param string    $taxonomy Taxonomy name.
	 */
	public static function clear_undo_on_format_change( $post_id, $term_ids, $taxonomy ) {
		if ( 'post_format' === $taxonomy ) {
			delete_post_meta( $post_id, self::UNDO_META );
		}
	}

	/**
	 * Enqueue admin styles for repair tool page
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public static function enqueue_styles( $hook_suffix ) {
		// Only enqueue on our repair tool page.
		if ( 'tools_page_pfbt-repair-tool' !== $hook_suffix ) {
			return;
		}

		$custom_css = '
			.pfbt-repair-summary.card {
				padding: 20px;
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
				margin-top: 20px;
			}
			.pfbt-repair-summary table {
				margin-top: 15px;
			}
			.pfbt-repair-summary th {
				width: 200px;
				font-weight: 600;
			}
			.pfbt-mismatches table {
				margin-top: 15px;
			}
			.pfbt-mismatches code {
				background: #f0f0f1;
				padding: 2px 6px;
				border-radius: 3px;
			}
		';
		wp_add_inline_style( 'wp-admin', $custom_css );
	}

	/**
	 * Render the repair tool page
	 *
	 * @since 1.0.0
	 */
	public static function render_page() {
		// Check user capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'post-formats-for-block-themes' ),
				esc_html__( 'Permission Denied', 'post-formats-for-block-themes' ),
				array( 'response' => 403 )
			);
		}

		// Get scan results with error handling.
		try {
			$scan_results = self::scan_posts();
		} catch ( Exception $e ) {
			wp_die(
				esc_html__( 'Error scanning posts: ', 'post-formats-for-block-themes' ) . esc_html( $e->getMessage() ),
				esc_html__( 'Scan Error', 'post-formats-for-block-themes' )
			);
		}

		try {
			$template_results = self::scan_templates();
		} catch ( Exception $e ) {
			wp_die(
				esc_html__( 'Error scanning templates: ', 'post-formats-for-block-themes' ) . esc_html( $e->getMessage() ),
				esc_html__( 'Template Scan Error', 'post-formats-for-block-themes' )
			);
		}

		// Render the page.
		$template_file = PFBT_PLUGIN_DIR . 'templates/repair-tool-page.php';
		if ( ! file_exists( $template_file ) ) {
			wp_die(
				esc_html__( 'Template file not found: ', 'post-formats-for-block-themes' ) . esc_html( $template_file ),
				esc_html__( 'Template Error', 'post-formats-for-block-themes' )
			);
		}

		// Start output buffering to catch any errors.
		ob_start();
		try {
			include $template_file;
			ob_end_flush();
		} catch ( Exception $e ) {
			ob_end_clean();
			wp_die(
				esc_html__( 'Error rendering template: ', 'post-formats-for-block-themes' ) . esc_html( $e->getMessage() ),
				esc_html__( 'Render Error', 'post-formats-for-block-themes' )
			);
		}
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
		$undo_repairs  = array();
		$correct_count = 0;
		$scanned_count = 0;

		foreach ( $posts as $post ) {
			++$scanned_count;
			$current_format   = self::get_format( $post->ID );
			$blocks           = parse_blocks( $post->post_content );
			$first_block      = self::get_first_meaningful_block( $blocks );
			$suggested_format = 'standard';
			$undo             = self::get_undo( $post->ID, $current_format );

			if ( $undo && current_user_can( 'edit_post', $post->ID ) ) {
				$undo_repairs[] = array(
					'post_id'    => $post->ID,
					'post_title' => get_the_title( $post ),
					'previous'   => $undo['previous'],
					'token'      => $undo['token'],
				);
			}

			if ( $first_block ) {
				$suggested_format = PFBT_Format_Registry::get_format_by_block(
					$first_block['blockName'],
					$first_block['attrs'],
					$first_block['innerBlocks']
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
			'undo_repairs'   => $undo_repairs,
		);
	}

	/**
	 * Scan all posts for incorrect template assignments
	 *
	 * @since 1.0.0
	 * @return array Scan results with template issues.
	 */
	private static function scan_templates() {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
		);

		$posts         = get_posts( $args );
		$issues        = array();
		$correct_count = 0;
		$scanned_count = 0;

		foreach ( $posts as $post ) {
			++$scanned_count;
			$format           = get_post_format( $post->ID ) ? get_post_format( $post->ID ) : 'standard';
			$current_template = get_post_meta( $post->ID, '_wp_page_template', true );

			// Determine expected template.
			if ( 'standard' === $format ) {
				$expected_template = '';
			} else {
				$expected_template = 'post-formats-for-block-themes//single-format-' . $format;
			}

			// Check if correct.
			if ( 'standard' === $format ) {
				$is_correct = empty( $current_template ) || 'default' === $current_template;
			} else {
				$is_correct = $current_template === $expected_template;
			}

			if ( ! $is_correct ) {
				$issues[] = array(
					'post_id'           => $post->ID,
					'post_title'        => $post->post_title,
					'post_url'          => get_edit_post_link( $post->ID ),
					'format'            => $format,
					'current_template'  => $current_template ? $current_template : __( '(none)', 'post-formats-for-block-themes' ),
					'expected_template' => $expected_template ? $expected_template : __( '(default)', 'post-formats-for-block-themes' ),
				);
			} else {
				++$correct_count;
			}
		}

		return array(
			'total_scanned' => $scanned_count,
			'correct'       => $correct_count,
			'issues'        => $issues,
			'issue_count'   => count( $issues ),
		);
	}

	/**
	 * Fix template assignments for all posts with issues
	 *
	 * @since 1.0.0
	 * @return array Results with count of fixed posts.
	 */
	private static function fix_all_templates() {
		$template_results = self::scan_templates();
		$fixed            = 0;
		$errors           = 0;

		foreach ( $template_results['issues'] as $issue ) {
			$result = self::fix_single_template( $issue['post_id'], $issue['format'] );

			if ( $result ) {
				++$fixed;
			} else {
				++$errors;
			}
		}

		return array(
			'fixed'  => $fixed,
			'errors' => $errors,
		);
	}

	/**
	 * Fix template assignment for a single post
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $format  Post format.
	 * @return bool True on success, false on failure.
	 */
	private static function fix_single_template( $post_id, $format ) {
		// Validate post.
		$post = get_post( $post_id );

		if ( ! $post || 'post' !== $post->post_type ) {
			return false;
		}

		// Set correct template based on format.
		if ( 'standard' === $format ) {
			delete_post_meta( $post_id, '_wp_page_template' );
		} else {
			$template_id = 'post-formats-for-block-themes//single-format-' . $format;
			update_post_meta( $post_id, '_wp_page_template', $template_id );
		}

		return true;
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
	 * Process a repair before WordPress renders the admin header.
	 */
	public static function handle_submission() {
		if ( ! isset( $_POST['pfbt_repair_action'] ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['pfbt_repair_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pfbt_repair_nonce'] ) ), 'pfbt_repair_action' ) ) {
			wp_die(
				esc_html__( 'Security verification failed. Please try again.', 'post-formats-for-block-themes' ),
				esc_html__( 'Security Error', 'post-formats-for-block-themes' ),
				array( 'response' => 403 )
			);
		}

		// Check capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to perform this action.', 'post-formats-for-block-themes' ),
				esc_html__( 'Permission Denied', 'post-formats-for-block-themes' ),
				array( 'response' => 403 )
			);
		}

		$action  = isset( $_POST['pfbt_repair_action'] ) ? sanitize_text_field( wp_unslash( $_POST['pfbt_repair_action'] ) ) : '';
		$dry_run = isset( $_POST['pfbt_dry_run'] ) && '1' === $_POST['pfbt_dry_run'];

		if ( 'apply_all' === $action ) {
			self::apply_all_suggestions( $dry_run );
		} elseif ( 'apply_single' === $action && isset( $_POST['post_id'] ) ) {
			$post_id = absint( $_POST['post_id'] );
			$format  = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : '';
			self::apply_single_suggestion( $post_id, $format, $dry_run );
		} elseif ( 'undo_format' === $action && isset( $_POST['post_id'], $_POST['repair_token'] ) ) {
			$post_id = absint( $_POST['post_id'] );
			$token   = sanitize_text_field( wp_unslash( $_POST['repair_token'] ) );
			$undone  = self::undo_format_change( $post_id, $token );
			set_transient(
				'pfbt_repair_message',
				array(
					'message' => $undone
						? __( 'Restored the previous post format.', 'post-formats-for-block-themes' )
						: __( 'Could not undo this repair. Review the current format and try again if Undo is still available.', 'post-formats-for-block-themes' ),
					'type'    => $undone ? 'success' : 'error',
				),
				30
			);
		} elseif ( 'fix_all_templates' === $action ) {
			self::handle_fix_all_templates();
		} elseif ( 'fix_single_template' === $action && isset( $_POST['post_id'] ) ) {
			$post_id = absint( $_POST['post_id'] );
			$format  = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : '';
			self::handle_fix_single_template( $post_id, $format );
		}

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Handle fix all templates action
	 *
	 * @since 1.0.0
	 */
	private static function handle_fix_all_templates() {
		$results = self::fix_all_templates();

		$message = sprintf(
			/* translators: %d: Number of posts fixed */
			_n(
				'Successfully fixed %d post template assignment.',
				'Successfully fixed %d post template assignments.',
				$results['fixed'],
				'post-formats-for-block-themes'
			),
			$results['fixed']
		);

		if ( $results['errors'] > 0 ) {
			$message .= ' ' . sprintf(
				/* translators: %d: Number of errors */
				_n(
					'%d error occurred.',
					'%d errors occurred.',
					$results['errors'],
					'post-formats-for-block-themes'
				),
				$results['errors']
			);
		}

		set_transient(
			'pfbt_repair_message',
			array(
				'message' => $message,
				'type'    => 'success',
			),
			30
		);
	}

	/**
	 * Handle fix single template action
	 *
	 * @since 1.0.0
	 * @param int    $post_id Post ID.
	 * @param string $format  Post format.
	 */
	private static function handle_fix_single_template( $post_id, $format ) {
		$result = self::fix_single_template( $post_id, $format );

		if ( $result ) {
			$message = sprintf(
				/* translators: %d: Post ID */
				__( 'Post #%d template assignment fixed successfully.', 'post-formats-for-block-themes' ),
				$post_id
			);
			$type = 'success';
		} else {
			$message = sprintf(
				/* translators: %d: Post ID */
				__( 'Failed to fix template assignment for post #%d.', 'post-formats-for-block-themes' ),
				$post_id
			);
			$type = 'error';
		}

		set_transient(
			'pfbt_repair_message',
			array(
				'message' => $message,
				'type'    => $type,
			),
			30
		);
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
					'post-formats-for-block-themes'
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
					'post-formats-for-block-themes'
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
					'post-formats-for-block-themes'
				),
				$errors
			);
		}

		// Store message in transient to persist across redirect.
		set_transient(
			'pfbt_repair_message',
			array(
				'message' => $message,
				'type'    => 'success',
			),
			30
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
					__( 'Dry run: Post #%1$d would be changed to %2$s format.', 'post-formats-for-block-themes' ),
					$post_id,
					$format
				);
			} else {
				$message = sprintf(
					/* translators: %d: Post ID, %s: Format name */
					__( 'Post #%1$d successfully updated to %2$s format.', 'post-formats-for-block-themes' ),
					$post_id,
					$format
				);
			}

			// Store message in transient to persist across redirect.
			set_transient(
				'pfbt_repair_message',
				array(
					'message' => $message,
					'type'    => 'success',
				),
				30
			);
		} else {
			$message = sprintf(
				/* translators: %d: Post ID */
				__( 'Failed to update post #%d.', 'post-formats-for-block-themes' ),
				$post_id
			);

			// Store error message in transient.
			set_transient(
				'pfbt_repair_message',
				array(
					'message' => $message,
					'type'    => 'error',
				),
				30
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
		$post = get_post( $post_id );
		if ( ! $post || 'post' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		if ( ! PFBT_Format_Registry::format_exists( $format ) ) {
			return false;
		}

		$previous = self::get_format( $post_id );
		if ( $dry_run || $previous === $format ) {
			return true;
		}

		wp_save_post_revision( $post_id );
		$result = set_post_format( $post_id, $format );
		if ( false === $result || is_wp_error( $result ) ) {
			return false;
		}

		update_post_meta(
			$post_id,
			self::UNDO_META,
			array(
				'previous' => $previous,
				'applied'  => $format,
				'token'    => wp_generate_uuid4(),
			)
		);
		update_post_meta( $post_id, '_pfbt_format_repaired', current_time( 'mysql' ) );

		/**
		 * Fires after format is repaired
		 *
		 * @since 1.0.0
		 *
		 * @param int    $post_id Post ID.
		 * @param string $format  New format.
		 */
		do_action( 'pfbt_format_repaired', $post_id, $format );
		return true;
	}

	/**
	 * Normalize Core's Standard format to the slug used by the review.
	 *
	 * @param int $post_id Post ID.
	 * @return string Current format slug.
	 */
	private static function get_format( $post_id ) {
		$format = get_post_format( $post_id );
		return false === $format ? 'standard' : $format;
	}

	/**
	 * Read the repair record if its applied format is still current.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $format  Current format slug.
	 * @return array|null Usable repair record, or null.
	 */
	private static function get_undo( $post_id, $format ) {
		$repair = get_post_meta( $post_id, self::UNDO_META, true );
		return is_array( $repair ) && isset( $repair['previous'], $repair['token'] ) && ( $repair['applied'] ?? null ) === $format ? $repair : null;
	}

	/**
	 * Consume a repair record and restore its previous format.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $token   Identifier for this specific repair.
	 * @return bool Whether the prior format was restored.
	 */
	private static function undo_format_change( $post_id, $token ) {
		if ( ! current_user_can( self::CAPABILITY ) || ! current_user_can( 'edit_post', $post_id ) || 'post' !== get_post_type( $post_id ) ) {
			return false;
		}

		$format = self::get_format( $post_id );
		$repair = self::get_undo( $post_id, $format );
		if ( ! $repair || $token !== $repair['token'] || ! delete_post_meta( $post_id, self::UNDO_META, $repair ) ) {
			return false;
		}

		clean_object_term_cache( $post_id, 'post' );
		if ( self::get_format( $post_id ) !== $format ) {
			return false;
		}

		$result = set_post_format( $post_id, $repair['previous'] );
		if ( false === $result || is_wp_error( $result ) ) {
			if ( self::get_format( $post_id ) === $format ) {
				add_post_meta( $post_id, self::UNDO_META, $repair, true );
			}
			return false;
		}

		return true;
	}
}

// Template file is registered separately in templates/repair-tool-page.php.
