# CLAUDE.md — Post Formats for Block Themes

## Project

- **Slug:** post-formats-for-block-themes
- **Text Domain:** post-formats-for-block-themes
- **Prefix:** `pfbt_`
- **Min WP:** 6.9 | **Min PHP:** 7.4
- **Repo:** https://github.com/courtneyr-dev/post-formats-for-block-themes

## What It Does

Post format functionality for block themes. 10 format-specific block patterns with locked first blocks, automatic format detection, format selection modal, format switcher, Status format with 280-char limit, Post Format Repair Tool, integrated Chat Log block.

## Architecture

```
post-formats-for-block-themes.php   # Main plugin file, constants, bootstrap
includes/
  class-format-registry.php         # Format registration and metadata
  class-format-detector.php         # Auto-detection logic
  class-pattern-manager.php         # Pattern registration and management
  class-block-locker.php            # First-block locking per format
  class-repair-tool.php             # Post Format Repair Tool
  class-admin-columns.php           # Admin list table columns
  class-format-styles.php           # Format-specific styling
  class-pfbt-feature-flags.php      # Feature flag management
  class-pfbt-abilities-manager.php  # Abilities API manager
  abilities/                        # WP Abilities API integrations
  mcp/                              # MCP server integrations
  mf2/                              # Microformats2 output
  posse/                            # POSSE syndication transforms
  webmention/                       # Webmention context
  activitypub/                      # ActivityPub transformer
  post-kinds/                       # Post Kinds plugin integration
blocks/
  chatlog/                          # Chat Log block (edit.js, view.js, block.json)
  post-format-block/                # Post Format display block
patterns/                           # 10 format patterns (standard, aside, audio, chat, gallery, image, link, quote, status, video)
src/editor/index.js                 # Editor JS entry point (modal, switcher, sidebar)
build/                              # Compiled assets (wp-scripts)
styles/                             # Format-specific style variations
templates/                          # Block template parts
```

## Build Commands

```bash
# JavaScript
npm run build                # Build editor JS (wp-scripts)
npm run start                # Watch mode

# PHP quality
composer phpcs               # WPCS lint
composer phpcbf              # WPCS auto-fix
composer phpstan             # Static analysis
composer phpunit             # Unit tests
composer test                # All PHP checks (phpcs + phpstan + phpunit)

# Testing
npm run test:js              # JS unit tests
npm run test:e2e             # Playwright E2E
npm run test:a11y            # Accessibility tests
npm run test:all             # Full suite (composer test + a11y + e2e)

# i18n
composer i18n                # Generate .pot file
```

## Standards

- **WPCS** — WordPress Coding Standards via phpcs/phpcbf
- **Security Trinity** — nonce verification, capability checks, data sanitization/escaping on every user input and output
- **i18n** — All user-facing strings wrapped in `__()`, `_e()`, `esc_html__()`, etc. with text domain `post-formats-for-block-themes`
- **Block API** — `apiVersion: 3` for all blocks
- **Accessibility** — Semantic HTML, ARIA labels, keyboard navigation, focus management. Floor is WCAG 2.2 AA: semantic HTML first, ARIA only where semantics can't do the job, and a keyboard-only pass plus a screen reader spot-check before shipping any UI change.
- **PHPStan** — Static analysis on `includes/`, `blocks/`, and main plugin file
- **Testing to capacity** — Every feature or bugfix lands with tests. Write the failing test first, then make it pass. Cover edge and failure paths, not just the happy path. No OR-assertions (a test that passes on multiple different behaviors isn't testing anything). No self-grading tests. CI green is the source of truth over local runs — if CI and a local run disagree, trust CI.
- **Security by default** — Sanitize input, validate data, escape output on every boundary (this is the Security Trinity bullet above, applied project-wide as a default, not just a per-PR checklist item). Secrets live in env/config, never in code or commits. Public-facing features get a security review before shipping.

## Release Policy

Never cut a release, tag, or deploy without Courtney's explicit go. This repo's releases auto-deploy to wordpress.org: publishing a GitHub release, or manually running `workflow_dispatch` on `.github/workflows/deploy-wporg.yml`, ships straight to production via WordPress.org SVN. There's no staging step in that pipeline — a tag/release is a live deploy. The explicit-go gate is mandatory, not optional, for exactly that reason.

## WP 7.0 Upgrade — Branch: `feature/wp70-api-integration`

### Priority Order

1. **Abilities API** — `detect-format`, `switch-format`, `repair-formats`, `get-format-stats`
2. **Block Bindings** — `post-formats/format-data` source (format name, icon, metadata, `has_format` boolean)
3. **Block Hooks** — Auto-inject format indicator icon before post title in `single.html`/`archive.html`
4. **Interactivity API** — Live format switcher, Status char counter, Chat Log frontend interactivity
5. **PHP-only block** — `post-formats/format-badge` (pill/tag/icon-only style)
6. **WP AI Client** — Smart format detection for ambiguous content, Chat Log summarization

### Version Gate Pattern

```php
if ( version_compare( get_bloginfo( 'version' ), '7.0', '>=' ) ) {
    /* 7.0 features */
}

if ( function_exists( 'wp_register_ability' ) ) {
    /* abilities */
}

if ( function_exists( 'wp_ai_client_prompt' ) && get_option( 'pfbt_enable_ai' ) ) {
    /* AI features — opt-in only */
}
```

### Key API Notes

- **Abilities:** `execute_callback`, categories registered on `wp_abilities_api_categories_init`
- **Block Hooks:** `"blockHooks": { "core/post-title": "before" }` in `block.json` + `hooked_block_types` PHP filter for conditional format-based insertion
- **Interactivity:** `wp_interactivity_state( 'post-formats', [...] )`, `viewScriptModule` in `block.json`

## Conventions

- **Commit messages (going forward):** Emoji-Log format — emoji + CAPS label, imperative mood, exactly seven prefixes: `📦 NEW:` `👌 IMPROVE:` `🐛 FIX:` `📖 DOC:` `🚀 RELEASE:` `🤖 TEST:` `‼️ BREAKING:`. Existing history uses conventional-commit-style prefixes (`feat:`, `fix:`, `test:`, `docs:`, `ci:`) — that's the established practice up to this point, not something to rewrite. Emoji-Log applies from here forward.
- Class files: `class-{name}.php` in `includes/`
- Class names: `PFBT_{Name}` or descriptive (e.g., `Format_Registry`)
- Constants: `PFBT_VERSION`, `PFBT_PLUGIN_DIR`, `PFBT_PLUGIN_URL`, `PFBT_PLUGIN_BASENAME`
- Hooks prefix: `pfbt_`
- Options prefix: `pfbt_`
- REST namespace: `pfbt/v1`
- Block namespace: `post-formats/`
- All direct file access guarded with `if ( ! defined( 'ABSPATH' ) ) { exit; }`
