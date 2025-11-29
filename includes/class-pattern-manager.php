<?php
/**
 * Pattern Manager Class
 *
 * Manages registration and insertion of block patterns for each post format.
 * Patterns are registered in the 'theme' category with postFormats metadata.
 *
 * @package PostFormatsPowerUp
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
class PFPU_Pattern_Manager {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var PFPU_Pattern_Manager|null
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
	 * @return PFPU_Pattern_Manager
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
		// Patterns will be registered via static method.
	}

	/**
	 * Register all patterns
	 *
	 * Loads pattern files and registers them with WordPress.
	 *
	 * @since 1.0.0
	 */
	public static function register_all_patterns() {
		$instance = self::instance();
		$formats  = PFPU_Format_Registry::get_all_formats();

		foreach ( $formats as $slug => $format ) {
			$instance->register_pattern( $slug, $format );
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
		$pattern_file = PFPU_PLUGIN_DIR . 'patterns/' . $slug . '.php';

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
					__( '%s Post Format', 'post-formats-power-up' ),
					$format['name']
				),
				'description' => $format['description'],
				'content'     => $pattern_content,
				'categories'  => array( 'theme' ),
				'postTypes'   => array( 'post' ),
				'postFormats' => array( $slug ),
				'keywords'    => array(
					$slug,
					'post-format',
					$format['name'],
				),
			)
		);

		$this->patterns[ $slug ] = $pattern_content;
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
		$pattern_file = PFPU_PLUGIN_DIR . 'patterns/' . $slug . '.php';

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
		$formats = PFPU_Format_Registry::get_all_formats();
		$names   = array();

		foreach ( $formats as $slug => $format ) {
			$names[] = 'pfpu/' . $slug;
		}

		return $names;
	}
}
