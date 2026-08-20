# Changelog

All notable changes to Post Formats for Block Themes will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.6] - 2026-08-20

### Fixed

- The 15 abilities in `PFBT_Core_Abilities`, `PFBT_IndieWeb_Abilities`, and `PFBT_MCP_Abilities` now actually register. Their names used underscores (`post_formats/list_formats`, `post-formats/suggest_format`), and core's `WP_Abilities_Registry::register()` only accepts `/^[a-z0-9-]+\/[a-z0-9-]+$/` — lowercase alphanumerics, dashes, one slash. Each was refused with a `_doing_it_wrong()` notice and nothing else, so with `WP_DEBUG` off they had been missing since 1.2.0 without a trace. `PFBT_Core_Abilities::NAMESPACE` is now `post-formats` and every name uses dashes. No aliases: the old names never registered.
- `PFBT_Abilities_Formats` passed a top-level `type` property on its four abilities, which is not one of `WP_Ability`'s properties. Core discarded it and emitted a `_doing_it_wrong()` notice on every request that touched the registry. The same value was already being set at `meta.mcp.type`, so the top-level key was removed.
- Format icon sprites now ship in distributed builds. `.distignore` excluded the whole `img/` directory, but `img/icon-sets/{slug}/format-icons.svg` loads at runtime, so every packaged install rendered `.pfbt-format-icon` as an empty box. Reported and fixed by @derintolu ([#24](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues/24), [#25](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/25)).
- The ActivityPub transformer no longer assumes its `$post` argument is a `WP_Post`. The `activitypub_*` filters can pass a post ID, which produced "Attempt to read property post_type on string" warnings in three callbacks; the argument is now resolved before use. Reported and fixed by @derintolu ([#26](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues/26), [#27](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/27)), with regression coverage in [#29](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/29).
- The plugin zip no longer omits files required at activation — the packaged build fataled on activation until [#28](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/28).
- The `get-format-stats` ability declares an `mcp` resource URI and `detect-format` is no longer exposed as a resource ([#30](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/30)).
- Slug-limited template queries no longer receive the "Default" pseudo-template they never requested. It leaked into `resolve_block_template()`'s priority sort, producing an undefined-array-key warning when creating a post and letting an empty Default template win Site Editor resolution. Fixed by @tjcafferkey ([#31](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/31)); the Default option still appears where it belongs, in the editor's template list.
- The Format Badge block now registers in the editor without JavaScript via WordPress 7.0's `supports.autoRegister`, ending the "your site doesn't include support for this block" placeholder. Diagnosed and fixed by @tjcafferkey ([#32](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/32)); the flag is declared in `block.json` so the block's other supports survive registration. On WordPress 6.9 the flag is ignored and the previous editor behavior remains.

### Changed

- `PFBT_Core_Abilities`'s content-analysis ability is `post-formats/score-format`, not `post-formats/detect-format`. `PFBT_Abilities_Formats` already registers `detect-format`, which resolves a format from a post ID or raw content and returns no confidence; the renamed one takes content plus an optional title and returns `detected_format`, `confidence`, and `first_block`. Two abilities cannot share a name. `execute_detect_format()` on `PFBT_Core_Abilities` is now `execute_score_format()`.
- Tested up to WordPress 7.1, backed by a runtime pass: the full suite against a real 7.1 install, plus probes confirming synthetic template objects serialize the new `WP_Block_Template::$date` field as `null` through REST and that the badge lands in the 7.1 auto-register bridge ([#34](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/34)).
- `readme.txt` contributors now include @tjcafferkey and @derintolu.

### Added

- `Test_Abilities_Registry` asserts all 19 `post-formats/*` abilities are present in `wp_get_abilities()` after init, that the expected names are unique and satisfy core's grammar, and that nothing registers under the namespace without being listed.
- Six integration tests guarding the two contracts from [#31](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/31)/[#32](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/32): slug-limited template queries never receive unrequested slugs, and the registered Format Badge supports must match `block.json` exactly ([#33](https://github.com/courtneyr-dev/post-formats-for-block-themes/pull/33)). Both proven to fail against the pre-fix code.

## [1.1.5] - 2026-07-10

> **Version renumbering.** WordPress.org never published the 1.2.x–2.3.0 releases (its listing continues from 1.1.4), so the public line resumes at 1.1.5. The 1.2.0–2.3.0 entries below record GitHub-only releases; everything in them ships to WordPress.org as part of 1.1.5.

### Changed

- Every one of the 52 block style variation colors now resolves through a `--pfbt-*` token contract: override token → theme palette preset → fixed default. Palette-mapped variations follow the active theme via dual slug chains (primary→accent-1, main→contrast, tertiary→base, …) so both Ollie-style and Twenty Twenty-Four/Five-style palettes work with zero configuration. Skeuomorphic materials (photo paper, aged card, device chrome, dark editor, lightbox overlay) are detached from palette presets so dark or brand-heavy palettes can't break the metaphor; solid accent fills that carry text chain to `contrast` rather than `accent-1`. The full token reference and mapped/fixed classification is documented in `docs/BLOCK-STYLE-VARIATIONS.md`.

### Fixed

- Post Kinds for IndieWeb integration, four gaps closed: (1) the active check ran before the `kind` taxonomy registered, so hooks never registered on PKIW-only installs — registration now defers to `init:12`; (2) `pfbt_auto_suggest_kind` / `pfbt_auto_suggest_format` default to enabled when the kind taxonomy exists, and the format→kind direction routes through `set_object_terms` on the `post_format` taxonomy (core has no `set_post_format` action); (3) `kind_format_map` now covers all 24 PKIW default kinds and `set_post_kind()` refuses unregistered slugs so a bad mapping can't create junk terms; (4) contentless registered dynamic blocks (what the Micropub builder writes for kind cards) count as meaningful first blocks, mapped to formats via the filterable `pfbt_kind_card_format_map`.

## [2.3.0] - 2026-07-03

### Added

- Block Bindings source (`post-formats/format-data`) for binding core block attributes to post format metadata
- Format Badge block (`post-formats/format-badge`) with Block Hooks auto-injection before post titles
- Seven bindable keys: `format_name`, `format_label`, `format_icon`, `has_format`, `char_count`, `media_url`, `quote_attribution`
- Interactivity API integration for Chat Log block with thread grouping and frontend interactivity
- `block_bindings` feature flag in `PFBT_Feature_Flags`
- SVG icon sets (`PFBT_Icon_Set`, `img/icon-sets/{slug}/format-icons.svg`) with a settings-page picker and `pfbt_icon_sets` / URL filters
- First runnable test suites: PHPUnit (254 tests, 9-job CI matrix), Playwright e2e, and axe accessibility specs

### Changed

- Automatic format detection re-enabled with apply-once semantics: applies once on first save from content, never overrides a manual or externally-set format
- Style-variation CSS and Interactivity view modules now load via a `render_block` filter attached at `init`, because block themes render template HTML before `enqueue_block_assets` and core's `style_handle` conditional loading never fires there

### Fixed

- Quote and Video format patterns emitted markup that fails core block validation ("unexpected or invalid content" recovery prompt)
- All 52 image/gallery/quote style variations rendered unstyled on block-theme front ends (registered, visible in the picker, but their CSS never enqueued)
- Format selection modal stacked on top of the WordPress welcome guide; it now defers until the guide is dismissed (`isFeatureActive` gating)
- Format Badge hooked block did not render (missing render wiring + dashicons on the front end)
- Feature flags could silently fail to store an "off" value (`update_option(false)` no-op)
- Format analyzer let Status/Aside outrank strong structural matches (structural score ≥60 now wins)
- Chat speaker labels failed to match across `</p>` boundaries without newlines

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
