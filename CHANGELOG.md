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
