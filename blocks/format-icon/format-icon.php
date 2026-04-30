<?php
/**
 * Format Icon block — server-side render callback.
 *
 * Renders an SVG icon representing the current post's format. The block
 * uses `usesContext` to receive the post id from a Query Loop or single
 * post template; falls back to `get_the_ID()` when context is missing.
 *
 * Standard-format posts render nothing (empty string) so the block is a
 * no-op outside formatted-post contexts.
 *
 * Output shape:
 *
 *   <span class="pfbt-format-icon pfbt-format-icon--{slug}">
 *     <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
 *       <use href=".../img/format-icons.svg#pfbt-format-icon-{slug}"></use>
 *     </svg>
 *     <span class="screen-reader-text">{Format name}</span>
 *   </span>
 *
 * Filters:
 *   - `pfbt_format_icon_map` — slug => symbol-id mapping. Lets themes
 *     remap a format slug to a different sprite symbol.
 *   - `pfbt_format_icon_sprite_url` — URL of the SVG sprite. Lets themes
 *     point at their own sprite without filtering each icon individually.
 *   - `pfbt_format_icon_svg` — full SVG output for a given slug. Highest
 *     priority hook; returning a non-null value skips sprite resolution
 *     entirely. Useful for inlining custom icons or swapping to an icon
 *     font / image source.
 *   - `pfbt_format_icon_label` — the screen-reader label for a given
 *     slug. Defaults to the registered format name.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (always empty — block has no inner blocks).
 * @var WP_Block $block      Block instance with context.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_post_id = $block->context['postId'] ?? get_the_ID();
if ( ! $pfbt_post_id ) {
	return;
}

$pfbt_format = get_post_format( $pfbt_post_id );

// Standard-format posts (false from get_post_format, or explicit "standard")
// have no icon. Bail without echoing so the block doesn't emit an empty
// wrapper that would still take up space in the layout.
if ( ! $pfbt_format || 'standard' === $pfbt_format ) {
	return;
}

/**
 * Allow themes to short-circuit the entire icon resolution.
 *
 * Returning any non-null value skips the sprite reference entirely.
 * Useful for theme authors who want to inline custom icons, swap to
 * an icon font, or replace the sprite mechanism wholesale.
 *
 * @since 2.0.0
 *
 * @param string|null $svg          Default null (use sprite). Return a
 *                                  full <svg>...</svg> string to override.
 * @param string      $format       Post format slug (e.g. 'aside').
 * @param int         $pfbt_post_id Current post id.
 */
$pfbt_short_circuit = apply_filters( 'pfbt_format_icon_svg', null, $pfbt_format, $pfbt_post_id );

if ( null !== $pfbt_short_circuit ) {
	$pfbt_svg = (string) $pfbt_short_circuit;
} else {
	/**
	 * Map a format slug to a sprite symbol id.
	 *
	 * Default: pfbt-format-icon-{slug}. Themes can remap a slug to a
	 * different symbol id (for instance, to share one symbol across
	 * multiple format slugs).
	 *
	 * @since 2.0.0
	 *
	 * @param array $map slug => symbol-id mapping.
	 */
	$pfbt_icon_map = apply_filters(
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

	$pfbt_symbol_id = $pfbt_icon_map[ $pfbt_format ] ?? null;
	if ( ! $pfbt_symbol_id ) {
		// Format has no mapped icon — bail rather than emit a broken use href.
		return;
	}

	/**
	 * URL of the SVG sprite. Defaults to the plugin's bundled sprite.
	 *
	 * Themes that want to ship their own sprite (with the same symbol ids)
	 * can filter this without overriding individual icons.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url Sprite URL.
	 */
	$pfbt_sprite_url = apply_filters(
		'pfbt_format_icon_sprite_url',
		PFBT_PLUGIN_URL . 'img/format-icons.svg'
	);

	$pfbt_svg = sprintf(
		'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="%1$s#%2$s"></use></svg>',
		esc_url( $pfbt_sprite_url ),
		esc_attr( $pfbt_symbol_id )
	);
}

/**
 * Resolve the screen-reader label for the format.
 *
 * Defaults to the registered format name from PFBT_Format_Registry.
 * Filterable so themes can shorten or rephrase ("Aside" → "Note", etc.).
 *
 * @since 2.0.0
 *
 * @param string $label  Default label (from registry).
 * @param string $format Post format slug.
 */
$pfbt_format_def = class_exists( 'PFBT_Format_Registry' )
	? PFBT_Format_Registry::get_format( $pfbt_format )
	: null;
$pfbt_default_label = $pfbt_format_def['name'] ?? ucfirst( $pfbt_format );
$pfbt_label = apply_filters( 'pfbt_format_icon_label', $pfbt_default_label, $pfbt_format );

$pfbt_show_label = ! empty( $attributes['showLabel'] );

$pfbt_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'pfbt-format-icon pfbt-format-icon--' . sanitize_html_class( $pfbt_format ),
	)
);

if ( $pfbt_show_label ) {
	// Visible label — icon + text both shown.
	printf(
		'<span %1$s>%2$s<span class="pfbt-format-icon__label">%3$s</span></span>',
		$pfbt_wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — wrapper attributes are pre-escaped by get_block_wrapper_attributes()
		$pfbt_svg, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — SVG markup is built from esc_url + esc_attr
		esc_html( $pfbt_label )
	);
	return;
}

// Default: icon visible, label for screen readers only.
printf(
	'<span %1$s>%2$s<span class="screen-reader-text">%3$s</span></span>',
	$pfbt_wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — wrapper attributes are pre-escaped by get_block_wrapper_attributes()
	$pfbt_svg, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — SVG markup is built from esc_url + esc_attr
	esc_html( $pfbt_label )
);
