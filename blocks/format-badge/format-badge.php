<?php
/**
 * Format Badge Block
 *
 * Auto-injects a format badge before post titles for non-standard post formats
 * using Block Hooks (blockHooks in block.json). The badge is conditionally
 * removed for standard-format posts via the hooked_block_types filter.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the format badge block
 *
 * Returns an empty string for standard-format posts (no badge).
 * For other formats, renders a span with dashicon and label.
 *
 * @since 1.2.0
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block content.
 * @param WP_Block $block      Block instance.
 * @return string Rendered block HTML.
 */
function pfbt_format_badge_render( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	if ( ! $post_id ) {
		return '';
	}

	$format = get_post_format( $post_id ) ? get_post_format( $post_id ) : 'standard';
	if ( 'standard' === $format ) {
		return '';
	}

	$registry = PFBT_Format_Registry::instance();
	$meta     = $registry->get_format( $format );
	$label    = esc_html( $meta['name'] ?? ucfirst( $format ) );
	$dashicon = esc_attr( $meta['icon'] ?? 'admin-post' );

	// Default icon markup: dashicon span. Themes that ship their own
	// icon sprite can filter this to inject SVG / image / icon-font markup.
	$default_icon_markup = sprintf(
		'<span class="dashicons dashicons-%s" aria-hidden="true"></span>',
		$dashicon
	);

	/**
	 * Filter the icon markup for a Format Badge.
	 *
	 * Default returns a Dashicon span. Themes that want their own
	 * hand-drawn SVG (or any other icon source) can return a custom
	 * markup string. The plugin appends the format label after the
	 * icon markup, so the filter only needs to return the icon itself.
	 *
	 * @since 2.0.1
	 *
	 * @param string $markup  Default Dashicon `<span>`.
	 * @param string $format  Post format slug (e.g. 'aside', 'chat').
	 * @param int    $post_id Current post id.
	 *
	 * Example (theme functions.php):
	 *
	 *     add_filter( 'pfbt_format_badge_icon', function ( $markup, $format ) {
	 *         return sprintf(
	 *             '<svg viewBox="0 0 24 24" aria-hidden="true"><use href="%s/assets/svg/icons.svg#post-icon-%s"></use></svg>',
	 *             esc_url( get_stylesheet_directory_uri() ),
	 *             esc_attr( $format )
	 *         );
	 *     }, 10, 2 );
	 */
	$icon_markup = apply_filters( 'pfbt_format_badge_icon', $default_icon_markup, $format, $post_id );

	$wrapper_attributes = get_block_wrapper_attributes(
		array( 'class' => 'pfbt-format-badge pfbt-format-badge--' . esc_attr( $format ) )
	);

	return sprintf(
		'<span %s>%s %s</span>',
		$wrapper_attributes,
		$icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — filter is responsible for escaping; default markup is built from esc_attr above.
		$label
	);
}

/**
 * Register the format badge block
 *
 * @since 1.2.0
 */
add_action(
	'init',
	function () {
		register_block_type(
			__DIR__ . '/block.json',
			array(
				'render_callback' => 'pfbt_format_badge_render',
			)
		);
	}
);

/**
 * Remove format badge for standard-format posts
 *
 * The blockHooks field in block.json declares the badge before core/post-title
 * by default. This filter removes it when the current post has the standard format.
 *
 * @since 1.2.0
 */
add_filter(
	'hooked_block_types',
	function ( $hooked_blocks, $relative_position, $anchor_block, $context ) {
		if ( 'before' !== $relative_position || 'core/post-title' !== $anchor_block ) {
			return $hooked_blocks;
		}

		// In template context, check current post format.
		if ( $context instanceof WP_Block_Template || is_singular() ) {
			$post_id = get_the_ID();
			if ( $post_id ) {
				$format = get_post_format( $post_id ) ? get_post_format( $post_id ) : 'standard';
				if ( 'standard' === $format ) {
					$hooked_blocks = array_diff( $hooked_blocks, array( 'post-formats/format-badge' ) );
				}
			}
		}

		return $hooked_blocks;
	},
	10,
	4
);
