<?php
/**
 * PFBT_Format_Helpers — first-media extraction utilities.
 *
 * 2.0 Session 9. Provides helpers that pattern + template code can use
 * to extract the first gallery / video / audio block from post content
 * for archive teasers without rendering the full content. The display
 * patterns in patterns/display/{format}.php currently rely on
 * core/post-content (single) or core/post-excerpt (archive) and let
 * WP handle rendering — these helpers let theme authors and future
 * patterns build richer archive teasers (e.g., "first gallery as
 * grid + post title below" instead of plain excerpt).
 *
 * All helpers operate on parsed blocks via parse_blocks() so they
 * correctly skip plain-HTML content (classic editor) and respect
 * inner block structure.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PFBT_Format_Helpers
 *
 * Static utility class — no singleton needed since helpers are
 * stateless. Public methods are also exposed as namespaced functions
 * (pfbt_get_first_gallery, etc.) for ergonomic use in patterns.
 *
 * @since 2.0.0
 */
class PFBT_Format_Helpers {

	/**
	 * Find the first matching block in a post's parsed content tree.
	 *
	 * Walks the block tree depth-first looking for the first block whose
	 * blockName matches any of the candidates. Returns the parsed block
	 * array (with blockName, attrs, innerBlocks, innerHTML, innerContent)
	 * or null if no match.
	 *
	 * @since 2.0.0
	 *
	 * @param int      $post_id    Post ID. Defaults to current post.
	 * @param string[] $candidates Block names to match (e.g., ['core/gallery']).
	 *                              First match wins; pass core/X first if you
	 *                              want core blocks to outrank custom ones.
	 * @return array|null Parsed block array or null if not found.
	 */
	public static function find_first_block( $post_id = 0, array $candidates = array() ) {
		$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
		if ( ! $post_id || empty( $candidates ) ) {
			return null;
		}

		$content = get_post_field( 'post_content', $post_id );
		if ( ! $content ) {
			return null;
		}

		$blocks = parse_blocks( $content );
		return self::walk_blocks( $blocks, $candidates );
	}

	/**
	 * Recursive depth-first walk for find_first_block.
	 *
	 * @since 2.0.0
	 *
	 * @param array    $blocks     Parsed blocks at this level.
	 * @param string[] $candidates Block names to match.
	 * @return array|null Matching block or null.
	 */
	private static function walk_blocks( array $blocks, array $candidates ) {
		foreach ( $blocks as $block ) {
			$name = $block['blockName'] ?? '';
			if ( $name && in_array( $name, $candidates, true ) ) {
				return $block;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = self::walk_blocks( $block['innerBlocks'], $candidates );
				if ( $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * First gallery block in a post.
	 *
	 * Matches core/gallery first; jetpack/tiled-gallery and other
	 * gallery-shaped blocks can be added via the
	 * `pfbt_first_gallery_candidates` filter.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID. Defaults to current post.
	 * @return array|null Parsed block or null.
	 */
	public static function get_first_gallery( $post_id = 0 ) {
		/**
		 * Block names considered "gallery" for the first-gallery lookup.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $candidates Default ['core/gallery'].
		 */
		$candidates = apply_filters( 'pfbt_first_gallery_candidates', array( 'core/gallery' ) );
		return self::find_first_block( $post_id, (array) $candidates );
	}

	/**
	 * First video block in a post.
	 *
	 * Matches core/video and core/embed (since most users embed YouTube /
	 * Vimeo / etc. via core/embed which then resolves to a video). Themes
	 * can narrow or widen via pfbt_first_video_candidates.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Parsed block or null.
	 */
	public static function get_first_video( $post_id = 0 ) {
		/**
		 * Block names considered "video" for the first-video lookup.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $candidates Default ['core/video', 'core/embed'].
		 */
		$candidates = apply_filters(
			'pfbt_first_video_candidates',
			array( 'core/video', 'core/embed' )
		);
		return self::find_first_block( $post_id, (array) $candidates );
	}

	/**
	 * First audio block in a post.
	 *
	 * Matches core/audio first, plus any podcast-publisher integration
	 * blocks (Podlove, etc.) via the filter.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Parsed block or null.
	 */
	public static function get_first_audio( $post_id = 0 ) {
		/**
		 * Block names considered "audio" for the first-audio lookup.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $candidates Default ['core/audio'].
		 */
		$candidates = apply_filters( 'pfbt_first_audio_candidates', array( 'core/audio' ) );
		return self::find_first_block( $post_id, (array) $candidates );
	}

	/**
	 * Render a parsed block back to HTML.
	 *
	 * Convenience wrapper around WP's render_block() that handles the
	 * null case (no matching block found) by returning an empty string.
	 * Useful for archive teasers that want to show "first gallery
	 * inline" without rendering the full content.
	 *
	 * @since 2.0.0
	 *
	 * @param array|null $block Parsed block from find_first_block / get_first_X.
	 * @return string Rendered HTML, or empty string when $block is null.
	 */
	public static function render( $block ) {
		if ( ! is_array( $block ) ) {
			return '';
		}
		return (string) render_block( $block );
	}
}

/**
 * Procedural-style aliases for ergonomic use in patterns + templates.
 *
 * Patterns can read more naturally with `pfbt_get_first_gallery()` than
 * `PFBT_Format_Helpers::get_first_gallery()`. Both call sites are
 * supported.
 *
 * @since 2.0.0
 */

if ( ! function_exists( 'pfbt_get_first_gallery' ) ) {
	/**
	 * Get the first gallery block in a post's content.
	 *
	 * @since 2.0.0
	 * @see   PFBT_Format_Helpers::get_first_gallery()
	 *
	 * @param int $post_id Post ID. Defaults to current post.
	 * @return array|null Parsed block or null.
	 */
	function pfbt_get_first_gallery( $post_id = 0 ) {
		return PFBT_Format_Helpers::get_first_gallery( $post_id );
	}
}

if ( ! function_exists( 'pfbt_get_first_video' ) ) {
	/**
	 * Get the first video / embed block in a post's content.
	 *
	 * @since 2.0.0
	 * @see   PFBT_Format_Helpers::get_first_video()
	 *
	 * @param int $post_id Post ID. Defaults to current post.
	 * @return array|null Parsed block or null.
	 */
	function pfbt_get_first_video( $post_id = 0 ) {
		return PFBT_Format_Helpers::get_first_video( $post_id );
	}
}

if ( ! function_exists( 'pfbt_get_first_audio' ) ) {
	/**
	 * Get the first audio block in a post's content.
	 *
	 * @since 2.0.0
	 * @see   PFBT_Format_Helpers::get_first_audio()
	 *
	 * @param int $post_id Post ID. Defaults to current post.
	 * @return array|null Parsed block or null.
	 */
	function pfbt_get_first_audio( $post_id = 0 ) {
		return PFBT_Format_Helpers::get_first_audio( $post_id );
	}
}
