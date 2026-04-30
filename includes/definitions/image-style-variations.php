<?php
/**
 * Image block style variation definitions.
 *
 * Returns an associative array of variation definitions consumed by
 * PFBT_Block_Style_Registry::register(). Each entry feeds into a
 * register_block_style( 'core/image', ... ) call paired with a
 * conditionally-loaded stylesheet.
 *
 * Schema:
 *
 *   slug         (string, required) — variation slug; produces `is-style-{slug}`.
 *   label        (string, required) — translatable label shown in the picker.
 *   style_path   (string, required) — relative path under the plugin URL where
 *                                     the variation's CSS file lives.
 *   style_handle (string, optional) — explicit handle. Defaults to
 *                                     `pfbt-image-variation-{slug}` when omitted.
 *   description  (string, optional) — short description for docs.
 *
 * Filter: `pfbt_image_style_variations` — modify or extend this array
 * before registration.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	// ============ Everyday / Content (5) ============

	array(
		'slug'        => 'rounded',
		'label'       => __( 'Rounded', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/rounded.css',
		'description' => __( 'Soft rounded corners on the image and any caption.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'circle',
		'label'       => __( 'Circle', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/circle.css',
		'description' => __( 'Forces a 1:1 aspect ratio and circular clip. Best for portraits.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'soft-shadow',
		'label'       => __( 'Soft Shadow', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/soft-shadow.css',
		'description' => __( 'Drop shadow that follows the image silhouette (no border).', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'tinted-border',
		'label'       => __( 'Tinted Border', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/tinted-border.css',
		'description' => __( '3px solid brand-color border with a 4px white inner mat.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'caption-card',
		'label'       => __( 'Caption Card', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/caption-card.css',
		'description' => __( 'Image plus caption inside a unified card with shared bg, radius, padding.', 'post-formats-for-block-themes' ),
	),

	// ============ Nostalgic / Tactile (5) ============

	array(
		'slug'        => 'polaroid',
		'label'       => __( 'Polaroid', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/polaroid.css',
		'description' => __( 'White card frame with a wide bottom edge for the caption. Slight rotation that straightens on hover.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'postcard',
		'label'       => __( 'Postcard', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/postcard.css',
		'description' => __( 'Landscape 4:3 crop with a dashed inline-end border and cream tint.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'photo-strip',
		'label'       => __( 'Photo Strip', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/photo-strip.css',
		'description' => __( 'Photobooth-style strip with horizontal divider bars. CSS-only.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'magazine-cutout',
		'label'       => __( 'Magazine Cutout', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/magazine-cutout.css',
		'description' => __( 'Torn-paper edge effect via SVG mask. Decorative.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'index-card',
		'label'       => __( 'Index Card', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/index-card.css',
		'description' => __( 'Ruled-paper background with a tab on the top inline-start corner.', 'post-formats-for-block-themes' ),
	),

	// ============ Editorial / Print (3) ============

	array(
		'slug'        => 'headline-crop',
		'label'       => __( 'Headline Crop', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/headline-crop.css',
		'description' => __( 'Cinematic 21:9 strip with a small uppercase caption underneath.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'duotone-mood',
		'label'       => __( 'Duotone Mood', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/duotone-mood.css',
		'description' => __( 'CSS-filter-chain duotone. Override --pfbt-duotone-hue to retint per-block.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'halftone',
		'label'       => __( 'Halftone', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/halftone.css',
		'description' => __( 'Subtle dot-pattern overlay; underlying image contrast preserved.', 'post-formats-for-block-themes' ),
	),

	// ============ Device / Mockup (3) ============

	array(
		'slug'        => 'phone-frame',
		'label'       => __( 'Phone Frame', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/phone-frame.css',
		'description' => __( 'Stylized phone frame with a notch. 9:19.5 aspect ratio.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'browser-window',
		'label'       => __( 'Browser Window', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/browser-window.css',
		'description' => __( 'Browser chrome with traffic-light dots. Designed for screenshots.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'code-editor',
		'label'       => __( 'Code Editor', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/image-variations/code-editor.css',
		'description' => __( 'Dark editor chrome with a sidebar. Designed for code screenshots.', 'post-formats-for-block-themes' ),
	),
);
