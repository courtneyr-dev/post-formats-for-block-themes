# Changelog

All notable changes to Post Formats for Block Themes will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- WordPress 6.8 or higher
- Block theme (Classic themes not supported)
- PHP 7.4 or higher

[1.1.3]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.2...v1.1.3
[1.1.2]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/courtneyr-dev/post-formats-for-block-themes/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/courtneyr-dev/post-formats-for-block-themes/releases/tag/v1.0.0
