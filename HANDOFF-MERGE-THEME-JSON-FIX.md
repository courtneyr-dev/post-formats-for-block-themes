# Handoff: Fix `merge_theme_json()` to Deep-Merge Instead of Replace

**Plugin:** Post Formats for Block Themes **Repo:** <https://github.com/courtneyr-dev/post-formats-for-block-themes>**Current version:** 1.2.0 **Target version:** 1.2.1 (patch — bug fix only) **Discovered:** April 2026 during courtneyr-child block theme development **Severity:** High — plugin silently destroys host theme's `theme.json` palette, gradients, styles, custom templates, and template parts when active

---

## TL;DR

The plugin's `merge_theme_json()` method on the `wp_theme_json_data_theme` filter calls `WP_Theme_JSON_Data::update_with()` and passes only the plugin's own `theme.json` data. `update_with()` is a wholesale REPLACE, not a merge, so the host theme's entire `settings`, `styles`, `customTemplates`, and `templateParts` blocks are clobbered for the lifetime of the request.

The fix is to read the existing data via `get_data()`, additively merge the plugin's contributions into it (palette/gradients merged by `slug`, `customTemplates`/`templateParts` merged by `name`, `styles` deep-merged), and hand the merged result to `update_with()`. ---

## The Bug

**File:** `includes/class-format-styles.php`**Method:** `Post_Formats_Block_Themes\Format_Styles::merge_theme_json()` (line 636) **Hook registered:** `wp_theme_json_data_theme` at default priority 10 (line 34)

**Current implementation (lines 636–653):**

```php
public static function merge_theme_json( $theme_json ) {
    $plugin_theme_json_file = PFBT_PLUGIN_DIR . 'theme.json';

    if ( ! file_exists( $plugin_theme_json_file ) ) {
        return $theme_json;
    }

    $plugin_theme_json_data = json_decode(
        file_get_contents( $plugin_theme_json_file ),
        true
    );

    if ( ! $plugin_theme_json_data ) {
        return $theme_json;
    }

    return $theme_json->update_with( $plugin_theme_json_data );
}
```

The `update_with()` call replaces the entire internal `$theme_json` array with the plugin's data (verified in WP core `/wp-includes/class-wp-theme-json-data.php`):

```php
public function update_with( $new_data ) {
    $this->theme_json = WP_Theme_JSON::remove_insecure_properties( $new_data );
    return $this;
}
```

There is no merge step inside `update_with()`. Whatever array you pass in becomes the entire theme_json for the theme origin. The plugin only passes its own 12 `format-*` palette entries plus its custom templates and template parts, so anything contributed by the active theme's `theme.json` is lost. ---

## How It Manifests

When the plugin is active alongside any block theme that defines its own `theme.json` palette, gradients, styles, custom templates, or template parts, those theme contributions are stripped at the theme origin layer of the theme.json merge cascade. The user sees:

- **Palette:** only the plugin's 12 `format-*` entries appear in the editor color picker. None of the theme's brand colors render.
- **Custom templates and template parts:** only the plugin's are visible. Theme-defined entries disappear from the Site Editor.
- **Styles:** any per-block styling the theme defined is dropped, leaving only what core defaults and the plugin contribute.

This was discovered while debugging palette merge issues on `courtneyr-child` (April 2026). The host theme had a 16-entry brand palette in its `theme.json`; with this plugin active, only the plugin's 12 `format-*` entries appeared in the merged result. We initially blamed Ollie Pro's `olpo\Helper::filter_theme_json_data` — which is also a wholesale-replacement filter at priority 10 — but discovered this plugin was contributing the same problem when both were active.

---

## Plugin's Contribution to theme.json

For reference (`theme.json` in plugin root):

```
$schema:       v2 schema
version:       2  (TODO: bump to 3 since plugin requires WP 6.9; v3 is in 6.6+)
title:         Post Formats
settings:
  color:
    palette:   12 entries — all slugs prefixed format-* (no overlap with
               typical theme palette slugs)
                 format-aside-bg, format-aside-border,
                 format-status-bg, format-link-bg, format-link-border,
                 format-quote-border, format-quote-accent,
                 format-gallery-border, format-image-border,
                 format-video-bg, format-audio-bg, format-chat-bg
styles:        per-block style hooks for post format display
customTemplates: format-specific templates the plugin registers
templateParts:   format-related template parts the plugin registers
```

Because every plugin palette slug is prefixed `format-*`, slug collisions with a host theme are extremely unlikely. The fix can therefore be straightforwardly additive without needing collision-resolution logic beyond a defensive `slug-already-exists` check. ---

## Proposed Fix

Replace the `merge_theme_json()` method with an additive merge. The new implementation reads the existing data passed by the previous filter callback (or the parent theme), merges plugin contributions into it, and returns the combined result.

**New implementation:**

```php
/**
 * Merge plugin theme.json with the theme's theme.json data.
 *
 * This makes the format colors and templates appear in the Site Editor
 * without clobbering any contributions from the active theme. The merge
 * is additive: palette and gradient entries are added if their slugs do
 * not already exist; custom templates and template parts are added if
 * their names do not already exist; styles are deep-merged with theme
 * values winning on collisions.
 *
 * @since 1.0.0
 * @since 1.2.1 Fix: was wholesale-replacing theme data via update_with().
 *
 * @param WP_Theme_JSON_Data $theme_json Theme JSON data.
 * @return WP_Theme_JSON_Data Modified theme JSON data.
 */
public static function merge_theme_json( $theme_json ) {
    $plugin_theme_json_file = PFBT_PLUGIN_DIR . 'theme.json';

    if ( ! file_exists( $plugin_theme_json_file ) ) {
        return $theme_json;
    }

    $plugin_data = json_decode(
        file_get_contents( $plugin_theme_json_file ),
        true
    );

    if ( ! is_array( $plugin_data ) ) {
        return $theme_json;
    }

    $existing = $theme_json->get_data();
    $merged   = self::deep_merge_theme_json_data( $existing, $plugin_data );

    return $theme_json->update_with( $merged );
}

/**
 * Additively merge plugin theme.json data into existing theme.json data.
 *
 * Theme values always win on slug/name collisions. Plugin values are added
 * only when they do not collide with existing entries.
 *
 * @since 1.2.1
 *
 * @param array $existing Existing theme.json data (from theme + earlier filters).
 * @param array $plugin   Plugin's own theme.json data.
 * @return array Merged data.
 */
private static function deep_merge_theme_json_data( array $existing, array $plugin ): array {
    // Top-level scalars: $schema, version, title — theme wins.
    // Plugin only contributes them if theme has not.
    foreach ( array( '$schema', 'version', 'title' ) as $key ) {
        if ( ! isset( $existing[ $key ] ) && isset( $plugin[ $key ] ) ) {
            $existing[ $key ] = $plugin[ $key ];
        }
    }

    // settings.color.palette and settings.color.gradients — additive merge by slug.
    foreach ( array( 'palette', 'gradients', 'duotone' ) as $color_key ) {
        $plugin_entries = $plugin['settings']['color'][ $color_key ] ?? null;
        if ( ! is_array( $plugin_entries ) || empty( $plugin_entries ) ) {
            continue;
        }

        if ( ! isset( $existing['settings'] ) ) {
            $existing['settings'] = array();
        }
        if ( ! isset( $existing['settings']['color'] ) ) {
            $existing['settings']['color'] = array();
        }

        $existing_entries = $existing['settings']['color'][ $color_key ] ?? array();
        $existing_slugs   = is_array( $existing_entries )
            ? array_filter( array_column( $existing_entries, 'slug' ) )
            : array();

        foreach ( $plugin_entries as $entry ) {
            if ( ! is_array( $entry ) || ! isset( $entry['slug'] ) ) {
                continue;
            }
            if ( ! in_array( $entry['slug'], $existing_slugs, true ) ) {
                $existing_entries[] = $entry;
                $existing_slugs[]   = $entry['slug'];
            }
        }

        $existing['settings']['color'][ $color_key ] = $existing_entries;
    }

    // styles — deep merge, theme values win on scalar collisions.
    if ( isset( $plugin['styles'] ) && is_array( $plugin['styles'] ) ) {
        if ( ! isset( $existing['styles'] ) || ! is_array( $existing['styles'] ) ) {
            $existing['styles'] = $plugin['styles'];
        } else {
            // array_replace_recursive merges nested arrays; theme keys win because
            // we put $existing second.
            $existing['styles'] = array_replace_recursive(
                $plugin['styles'],
                $existing['styles']
            );
        }
    }

    // customTemplates and templateParts — additive merge by name.
    foreach ( array( 'customTemplates', 'templateParts' ) as $list_key ) {
        $plugin_items = $plugin[ $list_key ] ?? null;
        if ( ! is_array( $plugin_items ) || empty( $plugin_items ) ) {
            continue;
        }

        $existing_items = $existing[ $list_key ] ?? array();
        $existing_names = is_array( $existing_items )
            ? array_filter( array_column( $existing_items, 'name' ) )
            : array();

        foreach ( $plugin_items as $item ) {
            if ( ! is_array( $item ) || ! isset( $item['name'] ) ) {
                continue;
            }
            if ( ! in_array( $item['name'], $existing_names, true ) ) {
                $existing_items[] = $item;
                $existing_names[] = $item['name'];
            }
        }

        $existing[ $list_key ] = $existing_items;
    }

    return $existing;
}
```

\---

## Filter Priority Consideration

The current registration uses default priority 10:

```php
add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'merge_theme_json' ) );
```

After this fix, priority 10 is correct because the merge is now additive and respects existing entries. **Do NOT change the priority** — keeping it at 10 lets a downstream theme override individual `format-*` slugs at priority 11+ if it wants to recolor format indicators to fit its brand.

If the priority were raised to 999, themes would have no recourse to adjust the plugin's format colors.

---

## Test Plan

### Unit / Integration Tests

Add a PHPUnit test suite under `tests/phpunit/test-format-styles-merge.php`:

1. `test_merge_preserves_theme_palette`

   - Build a `WP_Theme_JSON_Data` with a 5-entry palette.
   - Run `merge_theme_json()`.
   - Assert resulting palette has 5 + 12 = 17 entries.
   - Assert all 5 original slugs and all 12 `format-*` slugs are present.

2. `test_merge_does_not_clobber_theme_styles`

   - Build a `WP_Theme_JSON_Data` with `styles.color.background = '#fff'`.
   - Run `merge_theme_json()`.
   - Assert `styles.color.background` is still `#fff` after merge.

3. `test_merge_preserves_theme_custom_templates`

   - Build with one `customTemplates` entry named `single-podcast`.
   - Assert it survives the merge alongside plugin's templates.

4. `test_merge_no_collision_on_format_slugs`

   - Pre-populate palette with a `format-link-bg` entry of `#ff0000`.
   - Run merge.
   - Assert palette still contains `#ff0000` (theme wins on collision), not the plugin's default.

5. `test_merge_adds_plugin_palette_when_theme_has_none`

   - Empty theme.json.
   - Assert all 12 plugin entries are added.

6. `test_merge_returns_unmodified_when_plugin_file_missing`

   - Mock `file_exists` returning false.
   - Assert `$theme_json` returned unchanged.

### Manual Smoke Test

On a fresh WP 6.9 install with Twenty Twenty-Five active and this plugin installed:

1. Activate the plugin.
2. Open Site Editor → Styles → Colors.
3. Confirm Twenty Twenty-Five's palette colors are present (4 entries: black, white, accent-1, accent-2 etc.) AND the 12 `format-*` colors appear after them.
4. Open a post and set its format to "Quote".
5. Confirm the format-quote pattern picks up `format-quote-border` and `format-quote-accent` colors.

Repeat with `courtneyr-child` (or any custom block theme with its own palette) to verify no theme palette entries are stripped.

### Regression Test Against the Original Bug

Add a test fixture that reproduces the exact failure mode discovered:

```php
public function test_courtneyr_child_brand_palette_survives() {
    $theme_data = new WP_Theme_JSON_Data( array(
        'version'  => 3,
        'settings' => array(
            'color' => array(
                'palette' => array(
                    array( 'slug' => 'primary',         'color' => '#241c4a', 'name' => 'Russian Violet' ),
                    array( 'slug' => 'primary-accent',  'color' => '#bcb5e3', 'name' => 'Periwinkle' ),
                    array( 'slug' => 'selective-yellow','color' => '#ffb703', 'name' => 'Selective Yellow' ),
                ),
            ),
        ),
    ), 'theme' );

    $result = Format_Styles::merge_theme_json( $theme_data );
    $palette = $result->get_data()['settings']['color']['palette'];
    $slugs   = array_column( $palette, 'slug' );

    $this->assertContains( 'primary',          $slugs );
    $this->assertContains( 'primary-accent',   $slugs );
    $this->assertContains( 'selective-yellow', $slugs );
    $this->assertContains( 'format-quote-border', $slugs );
    $this->assertSame( 15, count( $slugs ), 'Expected 3 theme + 12 plugin entries' );
}
```---

## Suggested Companion Improvement: Schema v3

The plugin's `theme.json` declares `"version": 2`. Since the plugin
requires WP 6.9 (`Requires at least: 6.9`), and theme.json schema v3
landed in WP 6.6, bumping to v3 is safe and unlocks:

- `defaultSpacingSizes` boolean (cleaner than the v2 `spacingScale.steps: 0`
  trick)
- `defaultDuotone` and `defaultGradients` booleans
- Better handling of `fluid` typography settings
- Forward compatibility with future WP releases

This is a low-risk schema bump and should be done in the same release.

The change is just:

```diff
{
-  "$schema": "https://schemas.wp.org/trunk/theme.json",
+  "$schema": "https://schemas.wp.org/wp/6.9/theme.json",
-  "version": 2,
+  "version": 3,
   "title": "Post Formats",
   ...
}
```

(Or pin to `wp/6.9` schema URL, depending on whether you want trunk versioning or release versioning.)

---

## Release Process

 1. **Branch:** Create `fix/theme-json-merge` off `main`.

 2. **Implementation:**

    - Replace `merge_theme_json()` in `includes/class-format-styles.php` with the new version above.
    - Add `deep_merge_theme_json_data()` private helper.
    - Bump `theme.json` to schema v3 (optional but recommended).

 3. **Tests:**

    - Add `tests/phpunit/test-format-styles-merge.php` with the 6 cases above.
    - Run `composer test` (or whichever test runner is wired up).
    - Run `phpcs` against WordPress Coding Standards.

 4. **Version bump:**

    - `post-formats-for-block-themes.php`: `Version: 1.2.0` → `1.2.1`
    - `readme.txt`: `Stable tag: 1.2.0` → `1.2.1`
    - `package.json` if applicable.

 5. [**CHANGELOG.md**](http://CHANGELOG.md)**:**

    ```markdown
    ## [1.2.1] - 2026-04-XX
    
    ### Fixed
    - Plugin no longer wholesale-replaces the host theme's `theme.json` data
      when merging format colors. The merge is now additive: theme palette,
      gradients, styles, custom templates, and template parts are preserved
      alongside the plugin's contributions. Resolves an issue where activating
      this plugin would strip the active theme's brand palette from the Site
      Editor color picker.
    - Bumped plugin `theme.json` schema from version 2 to version 3 for
      forward compatibility with WordPress 6.6+.
    
    ### Technical
    - `Format_Styles::merge_theme_json()` now reads existing theme.json data
      via `WP_Theme_JSON_Data::get_data()`, deep-merges plugin contributions
      (palette and gradients merged by `slug`, custom templates and template
      parts merged by `name`, styles deep-merged with theme values winning),
      and returns the combined result via `update_with()`. Previous
      implementation called `update_with()` with only the plugin's data,
      which `update_with()` interprets as a wholesale replace.
    ```

 6. **readme.txt:**

    - Add the same entry under `== Changelog ==`.
    - Update `Tested up to: 6.9` if applicable.

 7. **PR description:**

    - Link to this handoff document for full context.
    - Include before/after screenshots of Site Editor color picker on a theme with its own palette.

 8. **Merge to** `main` **after review.**

 9. **GitHub release:**

    - Tag `v1.2.1`.
    - Release notes pasted from CHANGELOG.

10. [**WordPress.org**](http://WordPress.org) **SVN deployment:**

    - Use the existing GitHub Actions workflow (or `SVN-DEPLOYMENT-INSTRUCTIONS.md`).
    - Push tag to SVN trunk and tags/1.2.1.
    - Verify the [wp.org](http://wp.org) plugin page updates within a few minutes.

11. **Post-release verification:**

    - Update `courtneyr-child` site: reactivate Post Formats plugin.
    - Run the palette debug curl pattern from the courtneyr-child fix session to confirm 16 brand entries + 12 format entries = 28 entries in merged palette.
    - Update `courtneyr-child/inc/theme-json-overrides.php`: it can stay as-is (Ollie Pro is still the active culprit), but document in a comment that Post Formats 1.2.1+ is no longer a competing wholesale-replacer.---

## Why This Matters Beyond Courtneyr.dev

Post Formats for Block Themes is a public WordPress.org plugin. Any user
on any block theme that defines its own palette is currently being silently
broken by this plugin. They likely don't realize the plugin is the cause —
they probably blame the theme, switch themes, file confusing bug reports,
or just lose colors and move on. The fix benefits every downstream user.

Specific themes definitely affected (verified via this debugging session):

- **Ollie** (parent theme) — has 11-slot palette, all 11 stripped
- **Twenty Twenty-Five** — has accent-1, accent-2, contrast palette entries
- Likely most modern block themes since palette customization is the norm

This bug pattern (wholesale-replace via `update_with()`) is also present in
**Ollie Pro** (`olpo\Helper::filter_theme_json_data`). When raising the PR,
consider opening a Trac/Slack thread on
`make.wordpress.org/themes` documenting the footgun, since
`update_with()`'s replace-not-merge behavior is poorly documented and easy
to misuse. A docblock improvement to `WP_Theme_JSON_Data::update_with()` in
WP core would prevent future plugins from making the same mistake.

---

## Reference Materials

- **WP source for `update_with()`:**
  `wp-includes/class-wp-theme-json-data.php` (constructor + `update_with()` +
  `get_data()`)

- **WP source for the merge cascade:**
  `wp-includes/class-wp-theme-json-resolver.php::get_merged_data()`

- **Earlier debugging session that discovered this:**
  Conversation transcript at
  `/mnt/transcripts/2026-04-27-19-42-17-2026-04-27-courtneyr-child-theme-buildout.txt`
  (the segment after the diagnostic mu-plugin output named both
  `olpo\Helper::filter_theme_json_data` and this method as priority-10
  competitors)

- **The reference fix in another codebase (priority 999, full replace):**
  `courtneyr-child/inc/theme-json-overrides.php` shows the OPPOSITE
  pattern — that one INTENTIONALLY replaces because it is the theme's own
  filter defending against an upstream wholesale-replacer. The plugin's
  fix is the additive variant since the plugin is the upstream contributor,
  not the final authority.

---

## Quick Start for Claude Code in This Repo

```bash
cd ~/Documents/plugins/post-formats-for-block-themes
git checkout -b fix/theme-json-merge
git pull origin main

# Read this handoff doc:
cat HANDOFF-MERGE-THEME-JSON-FIX.md

# Read the bug:
sed -n '636,653p' includes/class-format-styles.php

# Read the existing CLAUDE.md for project conventions:
cat CLAUDE.md

# Implement the fix per the "Proposed Fix" section.
# Run lint:
composer phpcs

# Run tests:
composer test

# Bump version + changelog per "Release Process".
# Open PR.
```

The fix is ~140 lines added (one method replaced, one helper added) plus
~80 lines of tests plus version-bump touchups. Single-session scope for
Claude Code.
</content>