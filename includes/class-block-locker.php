<?php
/**
 * Block Locker Class
 *
 * Provides utilities for working with locked blocks in post format patterns.
 * Block locking is embedded directly in pattern content via block attributes.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 *
 * Accessibility Note:
 * Block locking does not affect accessibility as it only restricts block
 * movement/removal in the editor. The rendered frontend is unaffected.
 * Editor UI for locked blocks includes proper ARIA labels via WordPress core.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Locker class
 *
 * Singleton class that manages block locking for format patterns.
 *
 * @since 1.0.0
 */
class PFBT_Block_Locker {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var PFBT_Block_Locker|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return PFBT_Block_Locker
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
		// Locking is handled via pattern content, not runtime hooks.
	}

	/**
	 * Generate lock attribute for patterns
	 *
	 * Creates the lock attribute JSON for use in pattern definitions.
	 *
	 * @since 1.0.0
	 *
	 * @param array $lock_settings Lock settings. Default: array( 'move' => false, 'remove' => false ).
	 * @return string JSON-encoded lock attribute.
	 */
	public static function get_lock_attribute( $lock_settings = array() ) {
		$defaults = array(
			'move'   => false,
			'remove' => false,
		);

		$lock = wp_parse_args( $lock_settings, $defaults );

		return wp_json_encode( $lock );
	}

	/**
	 * Check if block is locked
	 *
	 * Analyzes block attributes to determine if block is locked.
	 *
	 * @since 1.0.0
	 *
	 * @param array $block_attrs Block attributes array.
	 * @return bool True if block has lock restrictions.
	 */
	public static function is_block_locked( $block_attrs ) {
		if ( ! isset( $block_attrs['lock'] ) ) {
			return false;
		}

		$lock = $block_attrs['lock'];

		// Check if any locking is applied.
		if ( is_array( $lock ) ) {
			return ! empty( $lock['move'] ) || ! empty( $lock['remove'] );
		}

		return false;
	}

	/**
	 * Get lock level
	 *
	 * Returns the type of lock applied to a block.
	 *
	 * @since 1.0.0
	 *
	 * @param array $block_attrs Block attributes array.
	 * @return string Lock level: 'full', 'move', 'remove', or 'none'.
	 */
	public static function get_lock_level( $block_attrs ) {
		if ( ! isset( $block_attrs['lock'] ) ) {
			return 'none';
		}

		$lock = $block_attrs['lock'];

		if ( ! is_array( $lock ) ) {
			return 'none';
		}

		$move_locked   = ! empty( $lock['move'] );
		$remove_locked = ! empty( $lock['remove'] );

		if ( $move_locked && $remove_locked ) {
			return 'full';
		}

		if ( $move_locked ) {
			return 'move';
		}

		if ( $remove_locked ) {
			return 'remove';
		}

		return 'none';
	}

	/**
	 * Create locked block markup
	 *
	 * Generates block comment markup with lock attribute.
	 * Used when programmatically building patterns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name Block name (e.g., 'core/paragraph').
	 * @param array  $attrs      Block attributes.
	 * @param string $content    Block content.
	 * @param array  $lock       Lock settings.
	 * @return string Block markup with lock.
	 */
	public static function create_locked_block( $block_name, $attrs = array(), $content = '', $lock = array() ) {
		// Add lock to attributes.
		if ( ! empty( $lock ) ) {
			$attrs['lock'] = $lock;
		} else {
			$attrs['lock'] = array(
				'move'   => false,
				'remove' => false,
			);
		}

		// Build block comment.
		$attr_string = wp_json_encode( $attrs );
		$opening     = '<!-- wp:' . $block_name . ' ' . $attr_string . ' -->';
		$closing     = '<!-- /wp:' . $block_name . ' -->';

		return $opening . "\n" . $content . "\n" . $closing;
	}

	/**
	 * Validate block locking in content
	 *
	 * Checks if first block in content has proper locking.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content.
	 * @return bool True if first block is properly locked.
	 */
	public static function validate_first_block_lock( $content ) {
		$blocks = parse_blocks( $content );

		if ( empty( $blocks ) ) {
			return false;
		}

		// Find first meaningful block.
		foreach ( $blocks as $block ) {
			if ( null === $block['blockName'] || empty( $block['blockName'] ) ) {
				continue;
			}

			// Check if it's locked.
			return self::is_block_locked( $block['attrs'] );
		}

		return false;
	}

	/**
	 * Get locking instructions for format
	 *
	 * Returns user-friendly description of why first block is locked.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format Format slug.
	 * @return string Translated description.
	 */
	public static function get_lock_description( $format ) {
		$format_data = PFBT_Format_Registry::get_format( $format );

		if ( ! $format_data ) {
			return '';
		}

		return sprintf(
			/* translators: %s: Format name */
			__( 'The first block in %s format is locked to maintain format consistency. You can add additional blocks below.', 'post-formats-for-block-themes' ),
			$format_data['name']
		);
	}
}
