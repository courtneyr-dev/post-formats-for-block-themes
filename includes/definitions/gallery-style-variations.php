<?php
/**
 * Gallery block style variation definitions.
 *
 * Returns an associative array keyed by variation slug. Each entry is
 * consumed by PFBT_Block_Style_Registry::register() and feeds into a
 * register_block_style( 'core/gallery', ... ) call paired with a
 * conditionally-loaded stylesheet.
 *
 * Schema for each entry:
 *
 *   slug              (string, required) — variation slug; produces `is-style-{slug}` class.
 *   label             (string, required) — translatable label shown in the block-styles picker.
 *   style_path        (string, required) — relative path under the plugin URL where the
 *                                          variation's CSS file lives.
 *   style_handle      (string, optional) — explicit handle. Defaults to
 *                                          `pfbt-gallery-variation-{slug}` when omitted.
 *   view_module       (string, optional) — Interactivity API view module file path,
 *                                          relative to plugin dir. When set, the registry
 *                                          registers the module and enqueues it conditionally.
 *   view_module_id    (string, optional) — module identifier for Interactivity registration
 *                                          (e.g., `post-formats/lightbox-slideshow`).
 *   description       (string, optional) — short description for docs/registry tests.
 *
 * Filter: `pfbt_gallery_style_variations` — modify or extend this array
 * before registration. See class-pfbt-block-style-registry.php for the
 * filter signature.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	// Phase 3 fills this array. Empty in Phase 1 so the registry
	// infrastructure tests can verify it loads cleanly with no styles.
);
