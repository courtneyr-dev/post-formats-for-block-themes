<?php
/**
 * Block Bindings Source for Post Format Data
 *
 * Registers a block bindings source that lets any core block bind its
 * attributes to post format metadata (label, icon, character count, etc.).
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Bindings Format Data Provider
 *
 * Registers the `post-formats/format-data` binding source with 7 keys:
 * - format_name        - Raw format slug (e.g., "aside")
 * - format_label       - Human-readable label (e.g., "Aside")
 * - format_icon        - Dashicon name without prefix (e.g., "format-aside")
 * - has_format         - String "true"/"false" whether post has non-standard format
 * - char_count         - Character count of post content (stripped of HTML)
 * - media_url          - First URL found in post content
 * - quote_attribution  - Text inside first <cite> element
 *
 * @since 1.2.0
 */
class PFBT_Block_Bindings_Formats {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Block_Bindings_Formats|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Block_Bindings_Formats
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->register();
	}

	/**
	 * Register the block bindings source
	 *
	 * @since 1.2.0
	 */
	private function register() {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			'post-formats/format-data',
			array(
				'label'              => __( 'Post Format Data', 'post-formats-for-block-themes' ),
				'get_value_callback' => array( $this, 'get_value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	/**
	 * Get a bound value for a given key
	 *
	 * @since 1.2.0
	 *
	 * @param array              $source_args    Binding source arguments. Must contain 'key'.
	 * @param WP_Block           $block_instance The block instance requesting the value.
	 * @param string             $attribute_name The attribute being bound.
	 * @return string|null The bound value, or null if key is unknown.
	 */
	public function get_value( array $source_args, $block_instance, $attribute_name ) {
		$post_id = $block_instance->context['postId'] ?? get_the_ID();
		if ( ! $post_id ) {
			return null;
		}

		$format   = get_post_format( $post_id ) ?: 'standard';
		$key      = $source_args['key'] ?? '';
		$registry = PFBT_Format_Registry::instance();
		$meta     = $registry->get_format( $format );

		switch ( $key ) {
			case 'format_name':
				return esc_html( $format );

			case 'format_label':
				return esc_html( $meta['name'] ?? ucfirst( $format ) );

			case 'format_icon':
				return esc_attr( $meta['icon'] ?? 'admin-post' );

			case 'has_format':
				return 'standard' !== $format ? 'true' : 'false';

			case 'char_count':
				$content = get_post_field( 'post_content', $post_id );
				return (string) mb_strlen( wp_strip_all_tags( $content ) );

			case 'media_url':
				$content = get_post_field( 'post_content', $post_id );
				if ( preg_match( '#https?://[^\s<>"\']+#i', $content, $matches ) ) {
					return esc_url( $matches[0] );
				}
				return '';

			case 'quote_attribution':
				$content = get_post_field( 'post_content', $post_id );
				if ( preg_match( '#<cite[^>]*>(.*?)</cite>#is', $content, $matches ) ) {
					return esc_html( wp_strip_all_tags( $matches[1] ) );
				}
				return '';

			default:
				return null;
		}
	}
}
