<?php
/**
 * Pattern Manager Class
 *
 * Manages registration and insertion of block patterns for each post format.
 * Patterns are registered in the 'theme' category with postFormats metadata.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 *
 * Accessibility Implementation:
 * - All patterns use semantic HTML and proper heading hierarchy
 * - Block locking information embedded in pattern metadata
 * - Patterns designed for keyboard navigation compatibility
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pattern Manager class
 *
 * Singleton class that handles block pattern registration and management.
 *
 * @since 1.0.0
 */
class PFBT_Pattern_Manager {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var PFBT_Pattern_Manager|null
	 */
	private static $instance = null;

	/**
	 * Registered patterns
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $patterns = array();

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return PFBT_Pattern_Manager
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
	 * @since 1.0.0
	 */
	private function __construct() {
		// Register custom pattern category.
		add_action( 'init', array( $this, 'register_pattern_category' ), 9 );
	}

	/**
	 * Register custom pattern category for post formats
	 *
	 * @since 1.0.0
	 */
	public function register_pattern_category() {
		register_block_pattern_category(
			'post-formats',
			array(
				'label'       => __( 'Post formats', 'post-formats-for-block-themes' ),
				'description' => __( 'Patterns for different post format types', 'post-formats-for-block-themes' ),
			)
		);
	}

	/**
	 * Register all patterns
	 *
	 * Creates synced reusable blocks for each pattern.
	 * Only synced blocks are created to avoid duplicates.
	 *
	 * @since 1.0.0
	 */
	public static function register_all_patterns() {
		$instance = self::instance();
		$formats  = PFBT_Format_Registry::get_all_formats();

		foreach ( $formats as $slug => $format ) {
			// Only create synced patterns, no regular patterns.
			$instance->create_synced_pattern( $slug, $format );
		}
	}

	/**
	 * Register a single pattern
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug   Format slug.
	 * @param array  $format Format definition.
	 */
	private function register_pattern( $slug, $format ) {
		$pattern_file = PFBT_PLUGIN_DIR . 'patterns/' . $slug . '.php';

		// Check if pattern file exists.
		if ( ! file_exists( $pattern_file ) ) {
			return;
		}

		// Load pattern content.
		$pattern_content = $this->load_pattern_file( $pattern_file );

		if ( empty( $pattern_content ) ) {
			return;
		}

		// Register the pattern.
		register_block_pattern(
			'pfpu/' . $slug,
			array(
				'title'       => sprintf(
					/* translators: %s: Format name */
					__( 'PFBT %s Post Format', 'post-formats-for-block-themes' ),
					$format['name']
				),
				'description' => $format['description'],
				'content'     => $pattern_content,
				'categories'  => array( 'pfbt-post-formats' ),
				'postTypes'   => array( 'post' ),
				'postFormats' => array( $slug ),
				'keywords'    => array(
					$slug,
					'post-format',
					$format['name'],
					'pfbt',
				),
			)
		);

		$this->patterns[ $slug ] = $pattern_content;
	}

	/**
	 * Create or update synced reusable block for pattern
	 *
	 * Creates a wp_block post type (reusable block) for the pattern.
	 * This allows the pattern to be synced - when admin edits it,
	 * all instances update automatically.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug   Format slug.
	 * @param array  $format Format definition.
	 */
	private function create_synced_pattern( $slug, $format ) {
		$pattern_file = PFBT_PLUGIN_DIR . 'patterns/' . $slug . '.php';

		if ( ! file_exists( $pattern_file ) ) {
			return;
		}

		// Load pattern content.
		$pattern_content = $this->load_pattern_file( $pattern_file );

		if ( empty( $pattern_content ) ) {
			return;
		}

		// Create unique slug for the reusable block.
		$block_slug = 'pfpu-' . $slug . '-pattern';

		// Check if reusable block already exists.
		$existing_block = get_page_by_path( $block_slug, OBJECT, 'wp_block' );

		$block_title = sprintf(
			/* translators: %s: Format name */
			__( 'PFBT %s Post Format', 'post-formats-for-block-themes' ),
			$format['name']
		);

		$block_data = array(
			'post_title'   => $block_title,
			'post_content' => $pattern_content,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
			'post_name'    => $block_slug,
		);

		if ( $existing_block ) {
			// Update existing reusable block.
			$block_data['ID'] = $existing_block->ID;
			$block_id         = wp_update_post( $block_data );
		} else {
			// Create new reusable block.
			$block_id = wp_insert_post( $block_data );

			// Add metadata to identify this as a post format pattern.
			if ( $block_id && ! is_wp_error( $block_id ) ) {
				update_post_meta( $block_id, '_pfpu_post_format', $slug );
			}
		}

		// Assign to Post formats category.
		if ( $block_id && ! is_wp_error( $block_id ) ) {
			// Get or create the category term.
			$category_term = get_term_by( 'slug', 'post-formats', 'wp_pattern_category' );

			if ( ! $category_term ) {
				$category_term = wp_insert_term(
					__( 'Post formats', 'post-formats-for-block-themes' ),
					'wp_pattern_category',
					array( 'slug' => 'post-formats' )
				);
			}

			// Assign the category to the block.
			if ( $category_term && ! is_wp_error( $category_term ) ) {
				$term_id = is_array( $category_term ) ? $category_term['term_id'] : $category_term->term_id;
				wp_set_object_terms( $block_id, $term_id, 'wp_pattern_category' );
			}
		}
	}

	/**
	 * Load pattern file
	 *
	 * Includes the pattern PHP file and returns the rendered content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_path Path to pattern file.
	 * @return string Pattern content.
	 */
	private function load_pattern_file( $file_path ) {
		ob_start();
		include $file_path;
		return ob_get_clean();
	}

	/**
	 * Get pattern content
	 *
	 * Returns the rendered content for a specific pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Format slug.
	 * @return string|false Pattern content or false if not found.
	 */
	public static function get_pattern( $slug ) {
		$instance = self::instance();

		if ( isset( $instance->patterns[ $slug ] ) ) {
			return $instance->patterns[ $slug ];
		}

		// Try to load it if not already loaded.
		$pattern_file = PFBT_PLUGIN_DIR . 'patterns/' . $slug . '.php';

		if ( file_exists( $pattern_file ) ) {
			return $instance->load_pattern_file( $pattern_file );
		}

		return false;
	}

	/**
	 * Insert pattern blocks into post
	 *
	 * Helper method to programmatically insert pattern blocks
	 * into post content. Used by format switcher.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern_content Pattern HTML/block markup.
	 * @param string $existing_content Existing post content (optional).
	 * @param string $mode 'replace' or 'prepend'. Default 'replace'.
	 * @return string New post content.
	 */
	public static function insert_pattern( $pattern_content, $existing_content = '', $mode = 'replace' ) {
		if ( 'replace' === $mode ) {
			return $pattern_content;
		}

		if ( 'prepend' === $mode ) {
			return $pattern_content . "\n\n" . $existing_content;
		}

		return $existing_content;
	}

	/**
	 * Check if pattern has locked blocks
	 *
	 * Analyzes pattern content to determine if it contains locked blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pattern_content Pattern HTML/block markup.
	 * @return bool True if pattern contains locked blocks.
	 */
	public static function has_locked_blocks( $pattern_content ) {
		return strpos( $pattern_content, '"lock"' ) !== false;
	}

	/**
	 * Get all registered pattern names
	 *
	 * @since 1.0.0
	 * @return array Array of pattern names (prefixed with 'pfpu/').
	 */
	public static function get_pattern_names() {
		$formats = PFBT_Format_Registry::get_all_formats();
		$names   = array();

		foreach ( $formats as $slug => $format ) {
			$names[] = 'pfpu/' . $slug;
		}

		return $names;
	}

	/**
	 * Get synced pattern block ID for a format
	 *
	 * Returns the wp_block post ID for a synced pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Format slug.
	 * @return int|false Block ID or false if not found.
	 */
	public static function get_synced_pattern_id( $slug ) {
		$block_slug = 'pfpu-' . $slug . '-pattern';
		$block      = get_page_by_path( $block_slug, OBJECT, 'wp_block' );

		return $block ? $block->ID : false;
	}

	/**
	 * Get synced pattern block reference markup
	 *
	 * Returns the block markup to reference a synced pattern.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Format slug.
	 * @return string|false Block reference markup or false if not found.
	 */
	public static function get_synced_pattern_reference( $slug ) {
		$block_id = self::get_synced_pattern_id( $slug );

		if ( ! $block_id ) {
			return false;
		}

		return sprintf( '<!-- wp:block {"ref":%d} /-->', $block_id );
	}

	/**
	 * Clean up old pattern registrations and categories
	 *
	 * Removes old category terms and unregisters regular patterns.
	 *
	 * @since 1.0.0
	 */
	public static function cleanup_old_patterns() {
		// Unregister old category with uppercase 'F'.
		unregister_block_pattern_category( 'pfbt-post-formats' );

		// Delete old taxonomy term if it exists.
		$old_term = get_term_by( 'slug', 'pfbt-post-formats', 'wp_pattern_category' );
		if ( $old_term && ! is_wp_error( $old_term ) ) {
			wp_delete_term( $old_term->term_id, 'wp_pattern_category' );
		}

		// Unregister regular patterns (they're registered as 'pfpu/{slug}').
		$formats = PFBT_Format_Registry::get_all_formats();
		foreach ( $formats as $slug => $format ) {
			unregister_block_pattern( 'pfpu/' . $slug );
		}
	}
}
