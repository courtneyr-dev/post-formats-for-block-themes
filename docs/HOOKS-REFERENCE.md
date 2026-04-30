# Hooks Reference — PFBT 2.0

Every filter and action the plugin exposes, what it does, and when each was added.

---

## Filters

### Existing 1.x filters (preserved)

#### `pfbt_registered_formats`

Modify the format definition array before it's frozen.

```php
/**
 * @param array $formats slug => format-definition map.
 */
apply_filters( 'pfbt_registered_formats', $formats );
```

Used in `class-format-registry.php`. Themes/plugins can add new format definitions or modify existing ones (e.g., change the `first_block` for the link format if a different bookmark plugin is preferred).

---

### 1.2.x opt-out filters

#### `pfbt_enqueue_format_styles` (1.2.3+)

Whether to enqueue the plugin's frontend format stylesheets.

```php
add_filter( 'pfbt_enqueue_format_styles', '__return_false' );
```

When `false`, neither `format-tokens.css` nor `format-styles.css` enqueues. Use when the theme ships its own complete format styling end-to-end.

#### `pfbt_merge_format_palette` (1.2.3+)

Whether to merge the plugin's `theme.json` palette additions into the resolved theme.json.

```php
add_filter( 'pfbt_merge_format_palette', '__return_false' );
```

In 2.0 this filter is a no-op since the plugin no longer ships palette entries — kept for backward compatibility with 1.x consumers.

#### `pfbt_register_format_templates` (1.2.4+)

Whether to register the plugin's `single-format-{slug}` templates into the get_block_templates query result.

```php
add_filter( 'pfbt_register_format_templates', '__return_false' );
```

Gates the entire `add_block_templates()` body in `class-format-styles.php`. Use when the theme ships its own complete template hierarchy.

---

### 2.0 filters — Format Icon block

#### `pfbt_format_icon_svg`

Full short-circuit override for the Format Icon block's SVG output.

```php
/**
 * @param string|null $svg          Default null (use sprite). Return a
 *                                   full <svg>...</svg> string to override.
 * @param string      $format       Post format slug.
 * @param int         $post_id      Current post id.
 */
apply_filters( 'pfbt_format_icon_svg', $svg, $format, $post_id );
```

Returning a non-null string skips sprite resolution entirely. Use to inline custom icons, swap to an icon font, or use image source.

#### `pfbt_format_icon_map`

Map a format slug to a sprite symbol id.

```php
/**
 * @param array $map slug => symbol-id mapping.
 */
apply_filters( 'pfbt_format_icon_map', $map );
```

Default maps each of the 9 non-standard slugs to `pfbt-format-icon-{slug}`. Themes can remap to share one symbol across multiple slugs or point at a different sprite layout.

#### `pfbt_format_icon_sprite_url`

URL of the SVG sprite the Format Icon block references.

```php
/**
 * @param string $url Sprite URL.
 */
apply_filters( 'pfbt_format_icon_sprite_url', PFBT_PLUGIN_URL . 'img/format-icons.svg' );
```

Themes that ship their own sprite (with the same symbol ids) can repoint the URL without overriding individual icons.

#### `pfbt_format_icon_label`

Screen-reader label for the format inside the Format Icon block.

```php
/**
 * @param string $label  Default from PFBT_Format_Registry name.
 * @param string $format Post format slug.
 */
apply_filters( 'pfbt_format_icon_label', $label, $format );
```

Use to shorten or rephrase ("Aside" → "Note", "Status" → "Update", etc.).

---

### 2.0 filters — Format Classes

#### `pfbt_format_card_classes`

Append additional classes to a post's body + post class lists.

```php
/**
 * @param string[] $classes Default classes (has-post-format, pfbt-format-X,
 *                          and pfbt-format-titleless if applicable).
 * @param string   $format  Post format slug.
 * @param int      $post_id Post ID.
 */
apply_filters( 'pfbt_format_card_classes', $classes, $format, $post_id );
```

Classes added here appear in BOTH `body_class()` and `post_class()` for the post. Useful for theme-specific format treatments without re-implementing the filters.

---

### 2.0 filters — First-Media Helpers

#### `pfbt_first_gallery_candidates`

Block names considered "gallery" for the `pfbt_get_first_gallery()` lookup.

```php
/**
 * @param string[] $candidates Default ['core/gallery'].
 */
apply_filters( 'pfbt_first_gallery_candidates', array( 'core/gallery' ) );
```

#### `pfbt_first_video_candidates`

Block names considered "video".

```php
/**
 * @param string[] $candidates Default ['core/video', 'core/embed'].
 */
apply_filters( 'pfbt_first_video_candidates', array( 'core/video', 'core/embed' ) );
```

#### `pfbt_first_audio_candidates`

Block names considered "audio".

```php
/**
 * @param string[] $candidates Default ['core/audio'].
 */
apply_filters( 'pfbt_first_audio_candidates', array( 'core/audio' ) );
```

---

### 2.0 filters — Block Templates (opt-in)

#### `pfbt_use_block_templates`

Whether to register the plugin's opt-in single + archive templates per format.

```php
/**
 * @param bool $enabled Default reads pfbt_use_block_templates option (false).
 */
apply_filters( 'pfbt_use_block_templates', $enabled );
```

Force-disable for themes that ship their own templates:

```php
add_filter( 'pfbt_use_block_templates', '__return_false' );
```

Force-enable regardless of the site's option setting:

```php
add_filter( 'pfbt_use_block_templates', '__return_true' );
```

When `true`, the plugin both registers the 18 templates (9 single + 9 archive) AND filters `template_hierarchy` to inject format-specific slugs.

---

## Block Bindings — `post-formats/format-data` source

Not a filter, but the binding source's keys are the documented public API. Bind any core block attribute to format data:

| Key | Returns | Notes |
|---|---|---|
| `format_name` | Slug string ('aside', 'quote', ...) | Returns 'standard' for un-formatted posts |
| `format_label` | Localized label ('Aside', 'Quote', ...) | From PFBT_Format_Registry::name |
| `format_icon` | Dashicon name | Legacy 1.x; use format_icon_svg in 2.0 |
| `format_icon_svg` | Full SVG markup | Empty for standard; routes through pfbt_format_icon_svg + map + sprite-url filters |
| `has_format` | Boolean string ("true" / "false") | "false" for standard format |
| `char_count` | Stripped-HTML character count | Useful for status format's 280-char display |
| `media_url` | First URL found in post content | Regex-matched |
| `quote_attribution` | First `<cite>` text | Regex-matched |
| `link_url` | External URL for link-format posts | 3-layer fallback: `_pfbt_link_url` meta → first Bookmark Card block url → empty. Empty for non-link formats. |
| `format_permalink_archive` | URL to format's taxonomy archive | Empty for standard |

### Example: bind a paragraph to the format's archive link

```html
<!-- wp:paragraph {
  "metadata":{"bindings":{
    "content":{"source":"post-formats/format-data","args":{"key":"format_permalink_archive"}}
  }}
} -->
<p>(archive URL injected here)</p>
<!-- /wp:paragraph -->
```

---

## Actions

### `pfbt_abilities_registered` (1.2.0+)

Fires after the plugin's WordPress Abilities API integrations register. Hook into this to register additional abilities that depend on the plugin's core abilities being loaded.

```php
/**
 * @param array $abilities Registered abilities map.
 */
do_action( 'pfbt_abilities_registered', $abilities );
```

---

## Options (used as filter contexts)

### `pfbt_use_block_templates`

Boolean option (default `false`). Controls the opt-in block templates feature. Toggleable via `Tools → Post Format Templates`.

The matching `pfbt_use_block_templates` filter (above) takes precedence — themes can force-disable regardless of the option's setting.

### `pfbt_enable_ai`

Boolean option. When `true`, AI features (smart format detection for ambiguous content, Chat Log summarization) load if the WP AI Client is available.

```php
if ( function_exists( 'wp_ai_client_prompt' ) && get_option( 'pfbt_enable_ai' ) ) {
    /* AI features */
}
```

---

## Constants

| Constant | Value | Purpose |
|---|---|---|
| `PFBT_VERSION` | `1.2.5` (2.0.0 in 2.0) | Plugin version |
| `PFBT_PLUGIN_DIR` | Plugin directory path | For internal `require_once` |
| `PFBT_PLUGIN_URL` | Plugin URL | For asset loading |
| `PFBT_PLUGIN_BASENAME` | `post-formats-for-block-themes/post-formats-for-block-themes.php` | For activation/deactivation hooks |

---

## Class API

| Class | Purpose |
|---|---|
| `PFBT_Format_Registry` | Format definitions (singleton) |
| `PFBT_Format_Detector` | Auto-detect format from content (singleton) |
| `PFBT_Pattern_Manager` | Pattern + display pattern registration (singleton) |
| `PFBT_Block_Locker` | Lock the first block per format in editor |
| `PFBT_Repair_Tool` | Tools page repair utility |
| `PFBT_Admin_Columns` | Format column in Posts list table |
| `PFBT_Format_Styles` | Legacy 1.x styles (deprecated body_class stub) |
| `PFBT_Format_Classes` | 2.0 body + post class additions (singleton) |
| `PFBT_Block_Bindings_Formats` | post-formats/format-data binding source (singleton) |
| `PFBT_Link_Meta` | `_pfbt_link_url` meta + Bookmark Card fallback (singleton) |
| `PFBT_Format_Helpers` | Static utility class for first-media extraction |
| `PFBT_Block_Templates` | Opt-in block templates registry (singleton) |

All singletons expose `::instance()`. Helpers exposed as procedural functions for ergonomic pattern use:

- `pfbt_get_first_gallery( $post_id = 0 )`
- `pfbt_get_first_video( $post_id = 0 )`
- `pfbt_get_first_audio( $post_id = 0 )`
