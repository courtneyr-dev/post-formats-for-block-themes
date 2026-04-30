# Changelog

All notable changes to Post Formats for Block Themes will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Block Bindings source (`post-formats/format-data`) for binding core block attributes to post format metadata
- Format Badge block (`post-formats/format-badge`) with Block Hooks auto-injection before post titles
- Seven bindable keys: `format_name`, `format_label`, `format_icon`, `has_format`, `char_count`, `media_url`, `quote_attribution`
- Interactivity API integration for Chat Log block with thread grouping and frontend interactivity
- `block_bindings` feature flag in `PFBT_Feature_Flags`

## [2.1.0] - 2026-04-30

### Added — 36 image and gallery block style variations

**16 image variations** registered on `core/image`, surfaced in the block-styles picker:

- *Everyday/Content (5):* `rounded`, `circle`, `soft-shadow`, `tinted-border`, `caption-card`
- *Nostalgic/Tactile (5):* `polaroid`, `postcard`, `photo-strip`, `magazine-cutout`, `index-card`
- *Editorial/Print (3):* `headline-crop`, `duotone-mood`, `halftone`
- *Device/Mockup (3):* `phone-frame`, `browser-window`, `code-editor`

**20 gallery variations** registered on `core/gallery`:

- *CSS-only (8):* `justified-rows`, `square-tile`, `polaroid-stack`, `filmstrip-snap`, `caption-prominent`, `duotone-mood-gallery`, `mosaic-spotlight`, `bordered-grid`
- *Interactivity API (8):* `masonry-cascade`, `headline-mosaic`, `lightbox-slideshow`, `before-after-pairs`, `filter-tags`, `lookbook-hotspots`, `card-deck-swipe`, `photo-essay-scroll`, `comparison-pairs`
- *Advanced (3, SSR fallbacks):* `map-pinned-geo`, `panorama-360`, `dynamic-query-gallery`

### Infrastructure

- New `PFBT_Block_Style_Registry` singleton class loads variation definitions from `includes/definitions/{block}-style-variations.php` and pairs each with a registered style handle so WP loads CSS conditionally (only on pages where the variation is rendered).
- New `pfbt_image_style_variations` and `pfbt_gallery_style_variations` filters let themes and other plugins extend or modify the shipped variations.
- New `image_gallery_styles` feature flag in `PFBT_Feature_Flags`, default **off** — installs that don't enable it pay zero registration cost.
- Interactivity-API view modules conditionally registered + enqueued when the matching variation is on a page (no JS bundle for sites that don't use the IA variations).

### Default pattern updates

- `patterns/image.php` now defaults to `is-style-caption-card` for new image-format posts.
- `patterns/gallery.php` now defaults to `is-style-justified-rows` for new gallery-format posts.

### Tests

- 9 unit tests in `tests/unit/test-block-style-registry.php` covering flag default, filter extension, normalization, default style_handle computation, and a smoke test for `register_block_style` + `wp_register_style` integration.

### Notes

- Polaroid migration (Phase 4 of the v2.1.0 brief) was a no-op — zero existing polaroid markup was found on the source site. See `docs/POLAROID-AUDIT.md`.
- Map-tile rendering (`map-pinned-geo`) and WebGL panorama viewer (`panorama-360`) are roadmap items for v2.2 — v2.1.0 ships the documented SSR fallbacks (text list and flat scroll-snap respectively) per Hard Rule 5 (no third-party JS libraries).
- Author docs: `docs/BLOCK-STYLE-VARIATIONS.md` and `docs/ADDING-A-VARIATION.md`.

## [2.0.1] - 2026-04-30

### Added

- New filter `pfbt_format_badge_icon` lets themes replace the Format Badge's Dashicon span with their own icon markup (SVG, image, icon font). Default returns the existing dashicon. Theme example in `/docs/THEME-INTEGRATION.md`.
- Expanded block supports across the four plugin blocks:
  - `post-formats/format-badge`: `anchor`, `className`, `__experimentalBorder` (color/radius/width/style), `typography.lineHeight`, `typography.letterSpacing`
  - `post-formats/format-icon`: `anchor`, `className`, `align: [left, center, right]`, `color.background`, `__experimentalBorder`, `typography.lineHeight`
  - `post-formats-for-block-themes/post-format`: `anchor`, `className`, `align: [left, center, right, wide, full]`, `__experimentalBorder`
  - `chatlog/conversation`: was previously declared but unused at render — fixed (see below)

### Fixed

- **Chat Log block now honors its declared block supports.** The render callback hardcoded `<div class="chatlog">` without calling `get_block_wrapper_attributes()`, so the `align`, `className`, `color`, `spacing`, `typography`, and `border` supports declared in `block.json` never reached the DOM. Users who set the chat block to "Wide width" or "Full width" in the editor saw their setting ignored. Fixed by wrapping the rendered output in a `<div %s>` carrying the wrapper attributes.



## [2.0.0] - 2026-04-30

### Major release — format styling system

Twelve-session overhaul that establishes a hard contract between the plugin and consumer themes: **plugin owns layout, theme owns paint**. Every plugin reference to color or typography routes through `var(--pfbt-format-X-*, NEUTRAL)` tokens where NEUTRAL is `transparent` / `inherit` / `currentColor` / `none`. Themes that customize see their brand applied to the plugin's structural treatment; themes that don't customize see the format inherit normal theme styling — distinctive structure, no distinctive paint.

This is a **breaking change**. See the migration table at the bottom.

### Added

- **34 design tokens** (`--pfbt-format-X-*`) in a new `@layer pfbt-format-tokens` cascade layer covering: per-format bg/fg/accent/font for nine non-standard formats, plus extras for chat (row striping, speaker, meta, rule, highlight, link, code, avatar) and image (caption font).
- **Structural CSS** in the same layer: title-hiding for aside / status / quote (with `is-style-show-format-title` opt-back-in class), Format Icon slot, per-format containers, chat row striping, video 16:9 default, gallery photo-count badge slot, `.screen-reader-text` utility.
- **Format Icon block** (`post-formats/format-icon`) — manually-placed standalone icon block for use inside patterns and templates. Renders an SVG that inherits text color via `currentColor`. Standard-format posts render nothing (block bails). Three filters: `pfbt_format_icon_svg` (full short-circuit), `pfbt_format_icon_map` (slug→symbol-id), `pfbt_format_icon_sprite_url` (sprite location). One label filter: `pfbt_format_icon_label`.
- **9-symbol SVG sprite** at `/img/format-icons.svg` — one symbol per non-standard format. 24x24 viewBox, stroke-based icons that follow text color.
- **Three new body + post classes** via new class `PFBT_Format_Classes`:
  - `pfbt-format-{slug}` — plugin-namespaced parallel to WP core's `.format-{slug}`
  - `pfbt-format-titleless` — aside, status, quote (formats with hidden titles)
  - `has-post-format` — any non-standard format
  Filter: `pfbt_format_card_classes` lets themes append their own.
- **Two new block-bindings keys** in the `post-formats/format-data` source: `format_icon_svg` (full SVG markup) and `format_permalink_archive` (URL to format's taxonomy archive). Plus `link_url` for link-format posts (with 3-layer fallback: `_pfbt_link_url` meta → first Bookmark Card block URL → empty).
- **Twenty display patterns** registered under the `pfbt/` namespace — 10 formats × 2 variants (archive + single). Each pair shares one PHP base file via the new `pfbt_pattern_variant` variable. Visual DNA stays identical between variants; only content depth differs (Excerpt vs Content).
- **18 opt-in block templates** in `templates/v2/` — 9 single-post + 9 archive variants. Gated by the `pfbt_use_block_templates` option (default false) + matching filter. New Tools subpage at `Tools → Post Format Templates` for the toggle. When enabled, the plugin filters `template_hierarchy` to inject `single-post-{format}` and `archive-post-format-{format}` ahead of the generic slugs.
- **`_pfbt_link_url` post meta** registered via `register_post_meta` with `show_in_rest=true` — REST-editable external URL for link-format posts. Used by the link display pattern's post-title binding to render the title as an anchor to the external URL. Bookmark Card plugin fallback when meta is empty.
- **Three first-media helpers** for archive teaser composition: `pfbt_get_first_gallery()`, `pfbt_get_first_video()`, `pfbt_get_first_audio()`. Walk parsed blocks depth-first; return null when no match. Three filters extend the candidates: `pfbt_first_gallery_candidates`, `pfbt_first_video_candidates`, `pfbt_first_audio_candidates`.
- **Contract enforcement test** `tests/unit/test-no-color-leakage.php` scans every plugin CSS / SCSS / pattern / template file for forbidden color literals. Fails CI on any violation. Respects `@pfbt-allow-color brand-replica` markers for device-frame brand replicas (Slack, Discord, IRC, iPhone, macOS) where the literal color IS platform identity.
- **PFBT_Format_Classes coverage tests** in `tests/unit/test-format-classes.php` covering all nine formats, the title-less subset, non-post post types, and the filter-extension hook.
- **`/docs/DESIGN-TOKENS.md`** — exhaustive token reference with "What this paints" + "What happens if you don't set it" columns, structural class catalog, migration table.
- **`/docs/THEME-INTEGRATION.md`** — quick-start theme-integration guide covering token setting from `theme.json` or stylesheets, section style variations, opt-in template toggle, opt-out filters, migration from 1.x.
- **`/docs/HOOKS-REFERENCE.md`** — exhaustive list of every filter and action, with code examples and `@since` tags.

### Changed

- **Pattern slug namespace `pfpu/` → `pfbt/`** for all new display patterns. The pre-2.0 `pfpu/` synced reusable blocks (`wp_block` post type entries) remain in the database and are not deleted (would break user content that referenced them).
- **Chatlog block (`blocks/chatlog/style.scss` + `editor.scss`) tokenized** — 30+ raw hex values migrated to `--pfbt-format-chat-*` tokens. Five device-frame selectors (IRC, iPhone, macOS Desktop, Slack-app, Discord-app) keep their literal brand-replica colors with `@pfbt-allow-color brand-replica` markers documenting the contract exemption.
- **Plugin's `theme.json` palette emptied.** The 12 stock-Gutenberg `format-X-bg` / `format-X-border` palette entries (`#f0f0f1`, `#0073aa`, `#cccccc`) are removed. Themes own the palette in 2.0.
- **`PFBT_Format_Styles::add_format_body_classes()` is now a deprecated stub.** The `body_class` filter that called it is no longer registered. `PFBT_Format_Classes` is the single source of truth for body + post class additions.
- **`PFBT_Pattern_Manager` extended** with `register_v2_display_patterns()` on init priority 11 — registers each format's archive + single variants from `patterns/display/{format}.php` via a scope-isolating closure.

### Deprecated

- `--wp--preset--color--format-aside-bg` → use `--pfbt-format-aside-bg`
- `--wp--preset--color--format-aside-border` → use `--pfbt-format-aside-accent`
- `--wp--preset--color--format-status-bg` → use `--pfbt-format-status-bg`
- `--wp--preset--color--format-link-bg` → use `--pfbt-format-link-bg`
- `--wp--preset--color--format-link-border` → use `--pfbt-format-link-accent`
- `--wp--preset--color--format-quote-border` → use `--pfbt-format-quote-accent`
- `--wp--preset--color--format-quote-accent` → use `--pfbt-format-quote-accent`
- `--wp--preset--color--format-gallery-border` → use `--pfbt-format-gallery-accent`
- `--wp--preset--color--format-image-border` → removed; use the theme's normal `core/image` rules instead (no separate format-image accent)
- `--wp--preset--color--format-video-bg` → use `--pfbt-format-video-bg`
- `--wp--preset--color--format-audio-bg` → use `--pfbt-format-audio-bg`
- `--wp--preset--color--format-chat-bg` → use `--pfbt-format-chat-row-bg-odd` and/or `--pfbt-format-chat-row-bg-even` (split into row striping)

### Removed

- Stock Gutenberg color fallbacks in plugin CSS (`#f0f0f1`, `#0073aa`, `#cccccc`, `#f0f8ff`). Replaced with neutral `transparent` / `inherit` / `currentColor` / `none` fallbacks.
- The 12 plugin-contributed palette entries from `theme.json`. Themes that relied on them appearing in the WP color picker now define their own equivalents.
- `customTemplates` entries in plugin's `theme.json` referencing the legacy `single-format-*` template files (those files were never registered with WP and are superseded by the new opt-in `templates/v2/` set).
- The `body_class` filter registration in `PFBT_Format_Styles::init()`. `PFBT_Format_Classes` replaces it.
- The pattern-naming `pfpu/` namespace for new patterns (existing wp_block reusable blocks under that name remain in DB; new patterns register under `pfbt/`).

### Migration

Sites that did NOT customize tokens see no visible color change after upgrading. They saw stock Gutenberg colors before; they see the theme's normal post styling now. Most themes will look BETTER — formatting that previously fought brand identity now inherits it.

Sites that customized via the old `--wp--preset--color--format-*` token names need to rename to `--pfbt-format-*`. Full migration table in `/docs/THEME-INTEGRATION.md`.

Sites that referenced patterns by `pfpu/{format}` slug need to update to `pfbt/{format}-archive` or `pfbt/{format}-single` depending on context.

Sites that customized `format-styles.css` directly need to migrate to the new token system or use the `pfbt_enqueue_format_styles` filter to opt out and ship their own CSS.



## [1.2.5] - 2026-04-29

### Fixed

- **Root-cause fix for homepage / page / archive resolution rendering with `single.html` markup** on heavily-themed sites. `add_block_templates()` (registered on the `get_block_templates` filter) was running `array_unshift($single_template, $query_result)` on every `wp_template` query, which placed `single` at the front of the result array even for queries asking about other slugs (`front-page`, `page-home`, `page`, `singular`, `index`). The block-template renderer's first-match resolution then picked `single` for the front page, leaking post-title, author byline, related-posts, and reading-progress markup onto pages that had their own templates resolved correctly.
- The single-template injection is now scoped to queries that actually want it: empty `slug__in` (editor's full-template list), `slug__in` containing `single`, or `post_type === 'post'` (editor querying templates assignable to a post). For all other queries the result order is preserved.
- The `pfbt_register_format_templates` opt-out filter from 1.2.4 is still honored as a complete bypass; this fix narrows the failure mode for themes that haven't opted out.

## [1.2.4] - 2026-04-29

### Added

- New filter `pfbt_register_format_templates` (default `true`) lets themes opt out of the entire `add_block_templates()` body — the `single` injection, the "Default" pseudo-template, and the nine `single-format-*` registrations. Use:

  ```php
  add_filter( 'pfbt_register_format_templates', '__return_false' );
  ```

  Useful for themes that ship their own template hierarchy and don't want the plugin to interfere. Stop-gap for the underlying bug fixed properly in 1.2.5.

## [1.2.3] - 2026-04-29

### Added

- New filter `pfbt_enqueue_format_styles` (default `true`) lets themes opt out of enqueueing `styles/format-styles.css`. The plugin's stylesheet ships with stock WordPress fallback colors (`#0073aa`, `#f0f0f1`, `#cccccc`, etc.) that override branded child-theme palettes when both target the same `.format-X` body-class selectors. Use:

  ```php
  add_filter( 'pfbt_enqueue_format_styles', '__return_false' );
  ```

- New filter `pfbt_merge_format_palette` (default `true`) lets themes opt out of merging the plugin's `theme.json` palette additions (12 `format-X-bg` / `format-X-border` entries with stock Gutenberg defaults) into the resolved `theme.json`. Useful for themes whose palette is fully bespoke. Use:

  ```php
  add_filter( 'pfbt_merge_format_palette', '__return_false' );
  ```

## [1.2.2] - 2026-04-29

### Fixed

- Bumped version header so Git Updater detects an update on installs that pulled the v1.2.1 release before commit `8daf545` ("fix: add missing activitypub and post-kinds include files") landed. Those installs were missing `includes/activitypub/class-pfbt-activitypub-transformer.php` and `includes/post-kinds/class-pfbt-post-kinds-integration.php`, causing a fatal in `pfbt_include_files()` during `plugins_loaded`. No code changes — release marker only.

## [1.2.1] - 2026-01-15

### Fixed

- Plugin no longer wholesale-replaces the host theme's `theme.json` data when merging format colors. The merge is now additive: theme palette, gradients, styles, custom templates, and template parts are preserved alongside the plugin's contributions. Resolves an issue where activating this plugin would strip the active theme's brand palette from the Site Editor color picker.
- Bumped plugin `theme.json` schema from version 2 to version 3 for forward compatibility with WordPress 6.6+.

## [1.2.0] - 2025-12-20

### Added

- WordPress Abilities API integration (requires WordPress 6.9+)
- Six core abilities for machine-readable post format operations (`list_formats`, `get_format_template`, `validate_format`, `set_post_format`, `get_post_format`, `detect_format`)
- Five IndieWeb abilities for microformats2, POSSE, and webmentions (`mf2_markup`, `mf2_validate`, `posse_prepare`, `posse_targets`, `webmention_context`)
- Four MCP abilities for AI-powered format suggestions (`suggest_format`, `analyze_content`, `validate_format_content`, `get_format_signals`)
- Feature flags system for optional integrations (IndieWeb, MCP, ActivityPub)
- Microformats2 markup generation with format-specific classes (h-entry, p-note, h-cite, u-photo)
- POSSE content preparation for Twitter/X, Mastodon, Bluesky, Threads, LinkedIn, Tumblr
- Webmention context mapping for format-specific interaction types
- `pfbt_abilities_registered` action for extending abilities
- `PFBT_Feature_Flags` class for managing optional feature toggles
- `PFBT_Abilities_Manager` class for Abilities API registration
- `PFBT_Core_Abilities`, `PFBT_IndieWeb_Abilities`, `PFBT_MCP_Abilities` classes
- `PFBT_Format_Analyzer`, `PFBT_Format_Mf2`, `PFBT_Posse_Transformer`, `PFBT_Webmention_Context` classes
- Unit tests for all abilities, IndieWeb, and MCP classes

### Changed

- Minimum WordPress version increased to 6.9 (for Abilities API support)

## [1.1.4] - 2025-12-19

### Fixed

- Critical issue where plugin's theme.json was overriding theme layout settings (contentSize, wideSize), causing blank templates
- Plugin no longer overrides theme spacing settings (spacingSizes)
- Removed appearanceTools setting that could conflict with theme settings

### Changed

- Simplified theme.json to only include format-specific color palette additions
- Plugin now respects all theme layout and spacing settings

## [1.1.3] - 2025-12-18

### Added

- Settings link on Plugins page that links to Post Format Repair tool
- Revision limiter for wp_block post type (limits to 3 revisions to prevent database bloat)
- Asset file for Post Format Block script dependencies

### Changed

- Simplified all format patterns by removing unnecessary wrapper Group blocks
- Status pattern now uses single paragraph with `status-paragraph` class
- Aside pattern now uses single paragraph (no wrapper)
- All other format patterns now use primary block + paragraph structure
- Pattern Manager now uses transient-based caching to avoid unnecessary database operations
- Pattern registration skipped entirely on front-end for better performance
- Pattern updates only occur when content has actually changed

### Fixed

- **Critical:** Performance issue with revision queries on sites with many synced patterns
- Duplicate pattern insertion when selecting format from modal (patterns were being inserted twice)
- Status format character counter appearing twice in editor
- Aside format icon not displaying in Posts admin list (changed from `dashicons-aside` to `dashicons-format-aside`)
- JavaScript error "Cannot read properties of undefined (reading 'postCategories')" in block editor
- Pattern transient cleared on plugin deactivation to ensure fresh patterns on reactivation

### Security

- Added `domReady` wrapper for Post Format Block to prevent race conditions
- Added null check with fallback icon for safer script initialization

## [1.1.2] - 2025-12-11

### Added

- "Default" template option in template chooser that explicitly clears template assignment
- Comprehensive logging system for tracking template assignment and REST API behavior

### Changed

- Simplified editor UI by removing duplicate "Post Format" dropdown from sidebar
- Status format character counter moved from sidebar panel to editor notice
- Format selection modal now shows "Standard (Single Template)" with descriptive text
- "Single" template from theme now properly appears in template chooser
- REST API now correctly returns 'default' template value when no template is assigned

### Fixed

- Standard format posts no longer incorrectly show format templates
- Template chooser modal now displays all available templates including theme's "Single" template
- Editor now properly reflects actual database state for template assignments

## [1.1.1] - 2025-12-09

### Fixed

- Critical issue where format templates were appearing in Template dropdown and hiding theme templates

## [1.1.0] - 2025-12-08

### Added

- Post Format Block - Display block for showing post formats on frontend
- Post format column in Posts admin list with clickable filtering
- Screen Options toggle for post format column visibility
- Post format taxonomy display in all 9 format templates
- Sortable post format column in admin list
- Dashicons for each post format in admin column
- Comprehensive test suite with 15 validation categories
- PHPCS, PHPStan, and PHPUnit configuration files
- Security scanning (SAST) and vulnerability checking
- PHP compatibility checks (7.4 - 8.4)
- Accessibility testing infrastructure
- Complete testing documentation

### Changed

- Template assignment system now uses slug-only format
- All 9 format templates now display categories, tags, and post format
- Post format taxonomy now available in REST API
- Post format support now properly merges with theme's existing format support
- Variable naming to follow WordPress coding standards
- Output escaping in admin columns for security compliance
- File naming (removed spaces from image filenames)

### Fixed

- Template assignment dropdown showing "Aside Format" for all post types
- Post format support conflicting with theme-defined formats
- Duplicate post format registration from Chat Log block
- Template storage format causing UI mismatch in editor sidebar
- Plugin check errors for WordPress.org submission compliance

### Removed

- All debug error_log() statements from production code
- Development files, test scripts, and backup files

## [1.0.0] - 2025-01-02

### Added

- Initial release
- Support for all 9 WordPress post formats (aside, gallery, link, image, quote, status, video, audio, chat)
- Format-specific block patterns with auto-insertion
- Auto-detection of post format based on content
- Chat Log block for chat post format
- Format-specific single post templates
- Post Format Repair tool for bulk template assignment
- Format switcher in block editor sidebar
- Media player integration for audio/video formats
- Format-specific styling using theme.json custom properties
- Block locking for format patterns
- Format validation and content detection
- Accessibility features (ARIA labels, keyboard navigation, semantic HTML)
- Full internationalization support
- RTL language support

### Requirements

- WordPress 6.9 or higher
- Block theme (Classic themes not supported)
- PHP 7.4 or higher

[Unreleased]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.4...v1.2.0
[1.1.4]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.3...v1.1.4
[1.1.3]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.2...v1.1.3
[1.1.2]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/courtneyr-dev/post-formats-for-block-themes/releases/tag/v1.0.0
