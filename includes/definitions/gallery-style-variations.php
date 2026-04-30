<?php
/**
 * Gallery block style variation definitions.
 *
 * Returns an associative array consumed by PFBT_Block_Style_Registry.
 * Each entry registers a `register_block_style( 'core/gallery', ... )`
 * call paired with conditionally-loaded CSS (and optionally an
 * Interactivity API view module).
 *
 * Schema:
 *
 *   slug              (string, required) — variation slug; produces `is-style-{slug}`.
 *   label             (string, required) — translatable label shown in the picker.
 *   style_path        (string, required) — relative path to the variation's CSS.
 *   style_handle      (string, optional) — defaults to `pfbt-gallery-variation-{slug}`.
 *   view_module       (string, optional) — relative path to an IA view module.
 *   view_module_id    (string, optional) — IA module identifier (e.g.
 *                                          `post-formats/lightbox-slideshow`).
 *   description       (string, optional) — short description for docs.
 *
 * Filter: `pfbt_gallery_style_variations`.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	// ============ CSS-only, no JS (8) ============

	array(
		'slug'        => 'justified-rows',
		'label'       => __( 'Justified Rows', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/justified-rows.css',
		'description' => __( 'Flex rows balanced to a target height. Tab order = DOM order.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'square-tile',
		'label'       => __( 'Square Tile', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/square-tile.css',
		'description' => __( 'CSS Grid with 1:1 tiles, object-fit cover. Best for icons/avatars.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'polaroid-stack',
		'label'       => __( 'Polaroid Stack', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/polaroid-stack.css',
		'description' => __( 'Each item gets a polaroid frame with randomized rotation; reduced-motion zeros it.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'filmstrip-snap',
		'label'       => __( 'Filmstrip Scroll Snap', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/filmstrip-snap.css',
		'description' => __( 'Single horizontal row with native scroll-snap. No JS.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'caption-prominent',
		'label'       => __( 'Caption Prominent', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/caption-prominent.css',
		'description' => __( 'Cards with image plus larger real-text caption below. Uniform 4:3 image aspect.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'duotone-mood-gallery',
		'label'       => __( 'Duotone Mood', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/duotone-mood-gallery.css',
		'description' => __( 'Grid where every child receives the same duotone filter. Override --pfbt-duotone-hue to retint.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'mosaic-spotlight',
		'label'       => __( 'Mosaic Spotlight', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/mosaic-spotlight.css',
		'description' => __( '1-large + 4-small mosaic. First child becomes the hero.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'bordered-grid',
		'label'       => __( 'Bordered Grid', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/bordered-grid.css',
		'description' => __( 'Brutalist grid with thick brand-color borders between cells.', 'post-formats-for-block-themes' ),
	),

	// ============ Interactivity API (8) — masonry-cascade is CSS-only ============

	array(
		'slug'        => 'masonry-cascade',
		'label'       => __( 'Masonry Cascade', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/masonry-cascade.css',
		'description' => __( 'CSS multi-column masonry with native masonry @supports progressive enhancement.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'headline-mosaic',
		'label'       => __( 'Headline Mosaic', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/headline-mosaic.css',
		'description' => __( 'Asymmetric grid; data-shape="A|B|C" picks the layout. DOM order preserved.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'lightbox-slideshow',
		'label'          => __( 'Lightbox Slideshow', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/lightbox-slideshow.css',
		'view_module'    => 'blocks/gallery-variations/lightbox-slideshow/view.js',
		'view_module_id' => 'post-formats/lightbox-slideshow',
		'description'    => __( 'Click to open fullscreen dialog with prev/next, keyboard nav, focus trap, live announcements.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'before-after-pairs',
		'label'          => __( 'Before/After Pairs', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/before-after-pairs.css',
		'view_module'    => 'blocks/gallery-variations/before-after-pairs/view.js',
		'view_module_id' => 'post-formats/before-after-pairs',
		'description'    => __( 'Pairs of images become draggable comparison sliders. Native range input is keyboard-accessible.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'filter-tags',
		'label'          => __( 'Filter Tags', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/filter-tags.css',
		'view_module'    => 'blocks/gallery-variations/filter-tags/view.js',
		'view_module_id' => 'post-formats/filter-tags',
		'description'    => __( 'Chip filter bar built from per-image tags. Data binding required: data-pfbt-tags="x y z".', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'lookbook-hotspots',
		'label'          => __( 'Lookbook Hotspots', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/lookbook-hotspots.css',
		'view_module'    => 'blocks/gallery-variations/lookbook-hotspots/view.js',
		'view_module_id' => 'post-formats/lookbook-hotspots',
		'description'    => __( 'Hotspot buttons over each image. Data binding required: data-pfbt-hotspots JSON array.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'card-deck-swipe',
		'label'          => __( 'Card Deck Swipe', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/card-deck-swipe.css',
		'view_module'    => 'blocks/gallery-variations/card-deck-swipe/view.js',
		'view_module_id' => 'post-formats/card-deck-swipe',
		'description'    => __( 'Stacked cards with prev/next chevrons + arrow-key navigation. Live region announces position.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'photo-essay-scroll',
		'label'          => __( 'Photo Essay Scroll', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/photo-essay-scroll.css',
		'view_module'    => 'blocks/gallery-variations/photo-essay-scroll/view.js',
		'view_module_id' => 'post-formats/photo-essay-scroll',
		'description'    => __( 'Sticky media column synced to scroll-step text. CSS sticky does the work; JS is optional enhancement.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'           => 'comparison-pairs',
		'label'          => __( 'Comparison Pairs', 'post-formats-for-block-themes' ),
		'style_path'     => 'styles/gallery-variations/comparison-pairs.css',
		'view_module'    => 'blocks/gallery-variations/comparison-pairs/view.js',
		'view_module_id' => 'post-formats/comparison-pairs',
		'description'    => __( 'Side-by-side pairs with synced hover crosshair. Disabled under reduced-motion.', 'post-formats-for-block-themes' ),
	),

	// ============ Advanced (3) — SSR fallbacks per Hard Rule 5 ============

	array(
		'slug'        => 'map-pinned-geo',
		'label'       => __( 'Map-Pinned Geo (List Fallback)', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/map-pinned-geo.css',
		'description' => __( 'SSR text-list fallback. Tile rendering is a v2.2 roadmap item.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'panorama-360',
		'label'       => __( 'Panorama 360 (Flat Fallback)', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/panorama-360.css',
		'description' => __( 'SSR flat-image fallback with horizontal scroll-snap. WebGL viewer is a v2.2 roadmap item.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'dynamic-query-gallery',
		'label'       => __( 'Dynamic Query', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/gallery-variations/dynamic-query-gallery.css',
		'description' => __( 'Visual layout for galleries fed by a Query Loop. data-pfbt-layout="grid|tile|mosaic" picks the inner layout.', 'post-formats-for-block-themes' ),
	),
);
