<?php
/**
 * Format Registry Class
 *
 * Manages format definitions, metadata, and configurations for all
 * supported post formats.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 *
 * Accessibility: All format metadata includes semantic descriptions
 * that are screen-reader friendly and properly internationalized.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format Registry class
 *
 * Singleton class that registers and manages post format definitions.
 *
 * @since 1.0.0
 */
class PFPU_Format_Registry {

	/**
	 * Single instance of the class
	 *
	 * @since 1.0.0
	 * @var PFPU_Format_Registry|null
	 */
	private static $instance = null;

	/**
	 * Registered formats
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $formats = array();

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return PFPU_Format_Registry
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
	 * Registers all format definitions.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->register_formats();
	}

	/**
	 * Register all post format definitions
	 *
	 * Defines metadata for each format including:
	 * - Name (translatable)
	 * - Description (translatable, screen-reader friendly)
	 * - Icon (dashicon class)
	 * - Pattern requirements
	 * - Display settings
	 *
	 * @since 1.0.0
	 */
	private function register_formats() {
		$this->formats = array(
			'standard' => array(
				'name'          => __( 'Standard', 'post-formats-power-up' ),
				'description'   => __( 'Default post format with full title and content. Best for traditional blog posts.', 'post-formats-power-up' ),
				'icon'          => 'admin-post',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => '',
				'pattern_name'  => 'pfpu/standard',
			),
			'aside'    => array(
				'name'          => __( 'Aside', 'post-formats-power-up' ),
				'description'   => __( 'Short note or update without a title. Displays in a bubble style with minimized metadata.', 'post-formats-power-up' ),
				'icon'          => 'format-aside',
				'title_visible' => false,
				'meta_behavior' => 'minimized',
				'first_block'   => 'core/group',
				'pattern_name'  => 'pfpu/aside',
			),
			'audio'    => array(
				'name'          => __( 'Audio', 'post-formats-power-up' ),
				'description'   => __( 'Audio file or embed. Starts with an audio block for podcasts or music.', 'post-formats-power-up' ),
				'icon'          => 'format-audio',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => 'core/audio',
				'pattern_name'  => 'pfpu/audio',
			),
			'chat'     => array(
				'name'          => __( 'Chat', 'post-formats-power-up' ),
				'description'   => __( 'Chat transcript or conversation log. Supports Slack, Discord, Teams, WhatsApp, and transcript formats (VTT, SRT, TXT, MD, DOC, HTML, RTF).', 'post-formats-power-up' ),
				'icon'          => 'format-chat',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => 'chatlog/conversation',
				'pattern_name'  => 'pfpu/chat',
			),
			'gallery'  => array(
				'name'          => __( 'Gallery', 'post-formats-power-up' ),
				'description'   => __( 'Image gallery post. Starts with a gallery block for multiple images.', 'post-formats-power-up' ),
				'icon'          => 'format-gallery',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => 'core/gallery',
				'pattern_name'  => 'pfpu/gallery',
			),
			'image'    => array(
				'name'          => __( 'Image', 'post-formats-power-up' ),
				'description'   => __( 'Single image post. Starts with an image block for photo-centric content.', 'post-formats-power-up' ),
				'icon'          => 'format-image',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => 'core/image',
				'pattern_name'  => 'pfpu/image',
			),
			'link'     => array(
				'name'           => __( 'Link', 'post-formats-power-up' ),
				'description'    => __( 'Link to external content. Uses Bookmark Card plugin if available, otherwise starts with link paragraph.', 'post-formats-power-up' ),
				'icon'           => 'admin-links',
				'title_visible'  => true,
				'meta_behavior'  => 'normal',
				'first_block'    => 'bookmark-card/bookmark-card',
				'fallback_block' => 'core/paragraph',
				'pattern_name'   => 'pfpu/link',
			),
			'quote'    => array(
				'name'          => __( 'Quote', 'post-formats-power-up' ),
				'description'   => __( 'Quotation or citation. Starts with a quote block for highlighted text.', 'post-formats-power-up' ),
				'icon'          => 'format-quote',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => 'core/quote',
				'pattern_name'  => 'pfpu/quote',
			),
			'status'   => array(
				'name'          => __( 'Status', 'post-formats-power-up' ),
				'description'   => __( 'Short status update without title. Limited to 280 characters, Twitter-style.', 'post-formats-power-up' ),
				'icon'          => 'format-status',
				'title_visible' => false,
				'meta_behavior' => 'author_date_only',
				'first_block'   => 'core/paragraph',
				'char_limit'    => 280,
				'pattern_name'  => 'pfpu/status',
			),
			'video'    => array(
				'name'          => __( 'Video', 'post-formats-power-up' ),
				'description'   => __( 'Video file or embed. Starts with a video block for multimedia content.', 'post-formats-power-up' ),
				'icon'          => 'format-video',
				'title_visible' => true,
				'meta_behavior' => 'normal',
				'first_block'   => 'core/video',
				'pattern_name'  => 'pfpu/video',
			),
		);

		/**
		 * Filter registered post formats
		 *
		 * Allows themes and plugins to modify or extend format definitions.
		 *
		 * @since 1.0.0
		 *
		 * @param array $formats Array of format definitions.
		 */
		$this->formats = apply_filters( 'pfpu_registered_formats', $this->formats );
	}

	/**
	 * Get all registered formats
	 *
	 * @since 1.0.0
	 * @return array Array of format definitions.
	 */
	public static function get_all_formats() {
		$instance = self::instance();
		return $instance->formats;
	}

	/**
	 * Get specific format definition
	 *
	 * @since 1.0.0
	 *
	 * @param string $format_slug Format slug (e.g., 'aside', 'gallery').
	 * @return array|null Format definition or null if not found.
	 */
	public static function get_format( $format_slug ) {
		$instance = self::instance();

		// Treat false/empty as 'standard'.
		if ( ! $format_slug || false === $format_slug ) {
			$format_slug = 'standard';
		}

		return isset( $instance->formats[ $format_slug ] ) ? $instance->formats[ $format_slug ] : null;
	}

	/**
	 * Check if format exists
	 *
	 * @since 1.0.0
	 *
	 * @param string $format_slug Format slug to check.
	 * @return bool True if format exists.
	 */
	public static function format_exists( $format_slug ) {
		return null !== self::get_format( $format_slug );
	}

	/**
	 * Get format by first block type
	 *
	 * Used by auto-detection to determine format from content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $block_name Block name (e.g., 'core/image').
	 * @param array  $block_attrs Block attributes (optional).
	 * @return string|false Format slug or false if no match.
	 */
	public static function get_format_by_block( $block_name, $block_attrs = array() ) {
		$instance = self::instance();

		foreach ( $instance->formats as $slug => $format ) {
			// Skip standard format.
			if ( 'standard' === $slug ) {
				continue;
			}

			// Check if block matches.
			if ( $format['first_block'] === $block_name ) {
				// Special handling for aside (needs specific class).
				if ( 'aside' === $slug ) {
					if ( isset( $block_attrs['className'] ) && strpos( $block_attrs['className'], 'aside-bubble' ) !== false ) {
						return $slug;
					}
					continue;
				}

				// Special handling for status (needs specific class or variation).
				if ( 'status' === $slug ) {
					if ( isset( $block_attrs['className'] ) && strpos( $block_attrs['className'], 'status-paragraph' ) !== false ) {
						return $slug;
					}
					continue;
				}

				return $slug;
			}
		}

		// Fallback to standard.
		return 'standard';
	}

	/**
	 * Get formats sorted for display
	 *
	 * Returns formats with Standard first, then alphabetically.
	 *
	 * @since 1.0.0
	 * @return array Sorted array of formats.
	 */
	public static function get_sorted_formats() {
		$instance = self::instance();
		$formats  = $instance->formats;

		// Extract standard.
		$standard = array( 'standard' => $formats['standard'] );
		unset( $formats['standard'] );

		// Sort remaining alphabetically.
		ksort( $formats );

		// Return with standard first.
		return array_merge( $standard, $formats );
	}
}
