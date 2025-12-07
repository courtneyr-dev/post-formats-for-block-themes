<?php
/**
 * Format Detector Class
 *
 * Automatically detects and sets post formats based on content structure.
 * Analyzes the first block in post content to determine the appropriate format.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 *
 * Security Implementation:
 * - Capability checks before modifying post data
 * - Respects user-set formats (won't override manual selections)
 * - Uses post meta to track format origin
 * - Validates post types and autosave/revision scenarios
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format Detector class
 *
 * Singleton class that handles automatic format detection on post save.
 *
 * @since 1.0.0
 */
class PFBT_Format_Detector {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var PFBT_Format_Detector|null
	 */
	private static $instance = null;

	/**
	 * Meta key for tracking manual format selection
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_MANUAL = '_pfbt_format_manual';

	/**
	 * Meta key for tracking detected format
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const META_KEY_DETECTED = '_pfbt_format_detected';

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return PFBT_Format_Detector
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Hooks into WordPress actions for format detection.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'save_post', array( $this, 'detect_and_set_format' ), 10, 3 );
		add_action( 'rest_after_insert_post', array( $this, 'detect_format_rest' ), 10, 2 );
	}

	/**
	 * Detect and set post format on save
	 *
	 * Analyzes post content on save and sets the appropriate format
	 * unless the user has explicitly set a format.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function detect_and_set_format( $post_id, $post, $update ) {
		// Skip autosaves.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Skip revisions.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only process 'post' post type.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Check user capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if format was manually set.
		$manual_format = get_post_meta( $post_id, self::META_KEY_MANUAL, true );

		// If user explicitly set format via UI, respect it.
		if ( $manual_format ) {
			// User has taken control, don't auto-detect.
			return;
		}

		// Detect format from content.
		$detected_format = $this->detect_format_from_content( $post->post_content );

		// Store detected format for reference.
		update_post_meta( $post_id, self::META_KEY_DETECTED, $detected_format );

		// Set the post format.
		set_post_format( $post_id, $detected_format );

		/**
		 * Fires after format is auto-detected and set
		 *
		 * @since 1.0.0
		 *
		 * @param int    $post_id         Post ID.
		 * @param string $detected_format Detected format slug.
		 * @param WP_Post $post           Post object.
		 */
		do_action( 'pfbt_format_detected', $post_id, $detected_format, $post );
	}

	/**
	 * Detect format for REST API saves
	 *
	 * Handles format detection when posts are saved via the REST API
	 * (which includes the block editor).
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post         $post    Inserted or updated post object.
	 * @param WP_REST_Request $request Request object.
	 */
	public function detect_format_rest( $post, $request ) {
		// Only process 'post' post type.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Check if format parameter was sent in the request.
		if ( $request->has_param( 'format' ) ) {
			// User explicitly set format, mark as manual.
			update_post_meta( $post->ID, self::META_KEY_MANUAL, true );
			return;
		}

		// Otherwise, run standard detection.
		$this->detect_and_set_format( $post->ID, $post, true );
	}

	/**
	 * Detect format from post content
	 *
	 * Parses blocks from post content and determines the appropriate
	 * format based on the first block.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content (block HTML).
	 * @return string Format slug (e.g., 'gallery', 'image', 'standard').
	 */
	private function detect_format_from_content( $content ) {
		// Parse blocks from content.
		$blocks = parse_blocks( $content );

		// If no blocks or empty, default to standard.
		if ( empty( $blocks ) ) {
			return 'standard';
		}

		// Find the first non-empty block.
		$first_block = $this->get_first_meaningful_block( $blocks );

		// If no meaningful block found, default to standard.
		if ( ! $first_block ) {
			return 'standard';
		}

		// Get format based on block type.
		$format = PFBT_Format_Registry::get_format_by_block(
			$first_block['blockName'],
			$first_block['attrs']
		);

		/**
		 * Filter detected format
		 *
		 * Allows modification of auto-detected format before it's applied.
		 *
		 * @since 1.0.0
		 *
		 * @param string $format      Detected format slug.
		 * @param array  $first_block First block data.
		 * @param array  $all_blocks  All parsed blocks.
		 */
		return apply_filters( 'pfbt_detected_format', $format, $first_block, $blocks );
	}

	/**
	 * Get first meaningful block
	 *
	 * Finds the first block with actual content, skipping empty blocks
	 * and core null blocks (which represent HTML comments or whitespace).
	 *
	 * @since 1.0.0
	 *
	 * @param array $blocks Array of parsed blocks.
	 * @return array|null First meaningful block or null if none found.
	 */
	private function get_first_meaningful_block( $blocks ) {
		foreach ( $blocks as $block ) {
			// Skip null blocks (HTML comments, whitespace).
			if ( null === $block['blockName'] ) {
				continue;
			}

			// Skip empty blocks.
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			// Skip blocks with no content.
			if ( empty( $block['innerHTML'] ) && empty( $block['innerBlocks'] ) ) {
				continue;
			}

			return $block;
		}

		return null;
	}

	/**
	 * Mark format as manually set
	 *
	 * Call this when the user explicitly selects a format to prevent
	 * auto-detection from overriding their choice.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public static function mark_as_manual( $post_id ) {
		update_post_meta( $post_id, self::META_KEY_MANUAL, true );
	}

	/**
	 * Clear manual format flag
	 *
	 * Allows auto-detection to resume for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 */
	public static function clear_manual_flag( $post_id ) {
		delete_post_meta( $post_id, self::META_KEY_MANUAL );
	}

	/**
	 * Check if format was manually set
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if format was manually set.
	 */
	public static function is_manual( $post_id ) {
		return (bool) get_post_meta( $post_id, self::META_KEY_MANUAL, true );
	}

	/**
	 * Get detected format
	 *
	 * Returns the format that was auto-detected, even if it's not
	 * currently applied (e.g., if user manually changed it).
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return string|false Detected format slug or false if none.
	 */
	public static function get_detected_format( $post_id ) {
		return get_post_meta( $post_id, self::META_KEY_DETECTED, true );
	}
}
