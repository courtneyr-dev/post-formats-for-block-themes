---
title: Adding a variation
description: "How to add your own block style variation to the plugin: the registry entry, CSS rules under the token contract, and the tests to update."
---

How to ship a new variation under `is-style-{slug}` for `core/image` or `core/gallery`.

## 1. Pick a slug + name

Slug: lowercase, hyphenated, must produce a unique `is-style-{slug}` class. Avoid collisions with existing entries in `includes/definitions/{block}-style-variations.php`.

Label: short, translatable, surfaced in the block-styles picker.

## 2. Write the CSS

Create `styles/{block}-variations/{slug}.css` (one file per variation).

```css
/**
 * Image variation: {Name}
 *
 * {What it does, one sentence. Why it exists.}
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

.wp-block-image.is-style-{slug} {
 /* layout / decoration */
}

.wp-block-image.is-style-{slug} img {
 /* image rules */
}

@media (prefers-reduced-motion: no-preference) {
 /* any transitions or transforms go here */
}

@media print {
 /* flatten decorative effects */
}
```

### Quality gates (the brief's Section 8)

Before merging, verify:

1. **Token check.** No bare hex colors, no bare `px` values (except 1px borders), no bare `rem` values outside `var()` fallbacks. Wrap any magic value in `var(--pfbt-{slug}-{property}, fallback)` so themes can override.
2. **Reduced-motion check.** Every `transition:` and `transform:` lives inside `@media (prefers-reduced-motion: no-preference)`.
3. **Specificity check.** No `!important`. Specificity wins via `.wp-block-image.is-style-{slug}` (one chained class beats core's plain `.wp-block-image`).
4. **Print check.** A `@media print` rule flattens shadows, rotations, decorative borders.
5. **RTL check.** Logical properties only (`inline-start`, `inline-end`, `padding-inline-*`). No `left`/`right`/`padding-left`/`padding-right`.
6. **Single-file check.** All variation CSS in one file. No imports from other variation files.
7. **i18n check.** Label uses `__()` with the plugin text domain.

## 3. Add the definition entry

Open `includes/definitions/{block}-style-variations.php` and append:

```php
array(
 'slug'        => 'my-new-variation',
 'label'       => __( 'My New Variation', 'post-formats-for-block-themes' ),
 'style_path'  => 'styles/image-variations/my-new-variation.css',
 'description' => __( 'One-sentence description for docs.', 'post-formats-for-block-themes' ),
),
```

`style_handle` is computed automatically (`pfbt-{block}-variation-{slug}`); supply your own if you want to share a stylesheet across variations.

For Interactivity-API variations, add `view_module` and `view_module_id`:

```php
array(
 'slug'           => 'my-interactive',
 'label'          => __( 'My Interactive', 'post-formats-for-block-themes' ),
 'style_path'     => 'styles/gallery-variations/my-interactive.css',
 'view_module'    => 'blocks/gallery-variations/my-interactive/view.js',
 'view_module_id' => 'post-formats/my-interactive',
),
```

## 4. Add the third-party hook (optional)

Themes and other plugins can register variations without forking PFBT. Use the registry's filters:

```php
add_filter( 'pfbt_image_style_variations', function ( $defs ) {
 $defs[] = array(
  'slug'       => 'my-theme-tape',
  'label'      => __( 'Tape Frame', 'my-theme' ),
  'style_path' => 'styles/image-variations/tape.css',
  // `style_path` here is a path under PFBT_PLUGIN_URL; for theme-
  // shipped variations, use a fully qualified URL via a custom
  // `style_handle` you wp_register_style yourself.
 );
 return $defs;
} );
```

## 5. Test

Locally:

```bash
composer phpcs                  # WPCS lint
composer phpstan                # static analysis
composer phpunit -- --filter Test_Block_Style_Registry
```

In the editor:

1. Enable the feature flag (`add_filter( 'pfbt_feature_image_gallery_styles', '__return_true' );` in a mu-plugin).
2. Insert an Image (or Gallery) block on a fresh post.
3. Sidebar → Styles → confirm your variation appears with the correct label.
4. Click it → confirm the editor preview updates.
5. Save → view on front-end → hard-refresh.
6. Open DevTools → Network tab → filter for your stylesheet handle → confirm it loads only when the variation is in use.
7. Toggle dark mode (or switch to Twenty Twenty-Five) → confirm tokens fall back gracefully.

## 6. Update the docs

Add an entry to `docs/BLOCK-STYLE-VARIATIONS.md` so authors know the variation exists and when to use it.
