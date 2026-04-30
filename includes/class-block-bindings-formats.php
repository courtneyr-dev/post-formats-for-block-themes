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

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

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
	 * @param array    $source_args    Binding source arguments. Must contain 'key'.
	 * @param WP_Block $block_instance The block instance requesting the value.
	 * @param string   $attribute_name The attribute being bound.
	 * @return string|null The bound value, or null if key is unknown.
	 */
	public function get_value( array $source_args, $block_instance, $attribute_name ) {
		$post_id = $block_instance->context['postId'] ?? get_the_ID();
		if ( ! $post_id ) {
			return null;
		}

		$format   = get_post_format( $post_id ) ? get_post_format( $post_id ) : 'standard';
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

			case 'format_icon_svg':
				/*
				 * Return the SVG markup the Format Icon block would emit
				 * for the current post's format. Routes through the same
				 * filters the block uses (pfbt_format_icon_svg short-circuit,
				 * pfbt_format_icon_map slug-to-symbol-id, and
				 * pfbt_format_icon_sprite_url) so a binding-driven block
				 * sees the same icon as a directly-placed Format Icon block.
				 *
				 * Standard format returns an empty string — same behavior
				 * as the Format Icon block, which renders nothing for
				 * standard posts.
				 *
				 * @since 2.0.0
				 */
				if ( ! $format || 'standard' === $format ) {
					return '';
				}

				$short = apply_filters( 'pfbt_format_icon_svg', null, $format, $post_id );
				if ( null !== $short ) {
					return (string) $short;
				}

				$map = apply_filters(
					'pfbt_format_icon_map',
					array(
						'aside'   => 'pfbt-format-icon-aside',
						'audio'   => 'pfbt-format-icon-audio',
						'chat'    => 'pfbt-format-icon-chat',
						'gallery' => 'pfbt-format-icon-gallery',
						'image'   => 'pfbt-format-icon-image',
						'link'    => 'pfbt-format-icon-link',
						'quote'   => 'pfbt-format-icon-quote',
						'status'  => 'pfbt-format-icon-status',
						'video'   => 'pfbt-format-icon-video',
					)
				);

				$symbol_id = $map[ $format ] ?? null;
				if ( ! $symbol_id ) {
					return '';
				}

				$sprite_url = apply_filters(
					'pfbt_format_icon_sprite_url',
					PFBT_PLUGIN_URL . 'img/format-icons.svg'
				);

				return sprintf(
					'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="%1$s#%2$s"></use></svg>',
					esc_url( $sprite_url ),
					esc_attr( $symbol_id )
				);

			case 'link_url':
				/*
				 * External URL for link-format posts. Reads
				 * PFBT_Link_Meta::get_link_url() which checks the
				 * `_pfbt_link_url` post meta first, falls back to the
				 * Bookmark Card plugin's first bookmark URL, returns
				 * empty string if neither.
				 *
				 * For non-link-format posts this returns empty so
				 * blocks bound to link_url on a non-link post don't
				 * render a misleading anchor.
				 *
				 * @since 2.0.0
				 */
				if ( 'link' !== $format ) {
					return '';
				}
				if ( ! class_exists( 'PFBT_Link_Meta' ) ) {
					return '';
				}
				return PFBT_Link_Meta::get_link_url( $post_id );

			case 'format_permalink_archive':
				/*
				 * Return the URL to the post-format taxonomy archive
				 * (e.g. /type/aside/) for the current post's format.
				 * Returns empty string for standard-format posts since
				 * /type/standard/ would be misleading — standard posts
				 * appear in the main blog archive, not a typed one.
				 *
				 * @since 2.0.0
				 */
				if ( ! $format || 'standard' === $format ) {
					return '';
				}

				$term = get_term_by( 'slug', 'post-format-' . $format, 'post_format' );
				if ( ! $term || is_wp_error( $term ) ) {
					return '';
				}

				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					return '';
				}

				return esc_url( $url );

			default:
				return null;
		}
	}
}
