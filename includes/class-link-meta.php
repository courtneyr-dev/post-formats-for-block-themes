<?php
/**
 * PFBT_Link_Meta — `_pfbt_link_url` post meta + Bookmark Card fallback.
 *
 * 2.0 Session 8. Twenty Thirteen rendered link-format posts with the
 * post title as an anchor to an external URL stored in post meta. This
 * class registers that meta field (`_pfbt_link_url`) via WP's
 * register_post_meta() so it's REST-exposed and editable from the
 * editor's standard fields panel, and exposes the meta value as a
 * Block Bindings key so the link pattern's title can route its href
 * through the binding rather than a hard-coded URL.
 *
 * Bookmark Card fallback: if the Bookmark Card plugin is active and
 * the current post has a `bookmark-card/bookmark-card` block in its
 * content, the `link_url` binding key reads the bookmark card's URL
 * attribute when `_pfbt_link_url` itself is empty. This means link
 * posts created with Bookmark Card "just work" without the user
 * having to duplicate the URL in the meta field.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PFBT_Link_Meta
 *
 * @since 2.0.0
 */
class PFBT_Link_Meta {

	/**
	 * Singleton instance.
	 *
	 * @since 2.0.0
	 * @var PFBT_Link_Meta|null
	 */
	private static $instance = null;

	/**
	 * Meta key for the external URL on link-format posts.
	 *
	 * @since 2.0.0
	 */
	const META_KEY = '_pfbt_link_url';

	/**
	 * Get singleton.
	 *
	 * @since 2.0.0
	 * @return PFBT_Link_Meta
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register the post meta field.
	 *
	 * Single-value, string, REST-exposed, sanitized as a URL on save.
	 * The 'show_in_rest' option lets headless editors set the value
	 * via REST without needing a custom endpoint.
	 *
	 * Auth callback restricts writes to users who can edit the post —
	 * matches WP's standard post-meta auth model.
	 *
	 * @since 2.0.0
	 */
	public function register_meta() {
		register_post_meta(
			'post',
			self::META_KEY,
			array(
				'type'              => 'string',
				'description'       => __( 'External URL for link-format posts. The post title becomes a hyperlink to this URL when the post format is "link".', 'post-formats-for-block-themes' ),
				'single'            => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
				'show_in_rest'      => true,
				'default'           => '',
			)
		);
	}

	/**
	 * Resolve the external URL for a post.
	 *
	 * Lookup order:
	 *   1. Post meta `_pfbt_link_url` (user-set explicit value)
	 *   2. First Bookmark Card block URL in post content (if Bookmark
	 *      Card plugin is active)
	 *   3. Empty string
	 *
	 * Result is escaped via esc_url() before return so callers can
	 * safely emit it directly into href attributes.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID. Defaults to the current post.
	 * @return string Escaped URL, or empty string if not found.
	 */
	public static function get_link_url( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		// Layer 1 — explicit meta value.
		$meta = get_post_meta( $post_id, self::META_KEY, true );
		if ( $meta && is_string( $meta ) ) {
			return esc_url( $meta );
		}

		// Layer 2 — Bookmark Card block fallback.
		if ( has_block( 'bookmark-card/bookmark-card', $post_id ) ) {
			$content = get_post_field( 'post_content', $post_id );
			$blocks  = parse_blocks( $content );
			foreach ( $blocks as $block ) {
				if ( 'bookmark-card/bookmark-card' === ( $block['blockName'] ?? '' ) ) {
					$attrs = $block['attrs'] ?? array();
					$url   = $attrs['url'] ?? '';
					if ( $url ) {
						return esc_url( $url );
					}
				}
			}
		}

		// Layer 3 — no URL found.
		return '';
	}
}
