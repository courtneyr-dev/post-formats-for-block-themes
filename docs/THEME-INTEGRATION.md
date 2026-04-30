# Theme Integration Guide — PFBT 2.0

How a block theme integrates with the Post Formats for Block Themes plugin.

The plugin owns **layout** (which blocks render, in what order, with what classes). The theme owns **paint** (every color, font, spacing scale, border radius, shadow value). This guide covers the contract from the theme's side.

---

## Quick start (60 seconds)

If you do nothing, the plugin's structural treatment renders with neutral inheritance — every format reads as the theme's normal post layout, just with format-distinctive class hooks.

To paint a format with the theme's brand, set the matching `--pfbt-format-X-*` token. Most themes only need to set 4–6 tokens for the formats they care about.

```css
/* In your theme's stylesheet, or theme.json -> styles.css */
:root {
  /* Quote format gets a serif accent and a brand-blue accent bar */
  --pfbt-format-quote-font: "Source Serif 4", serif;
  --pfbt-format-quote-accent: var(--wp--preset--color--brand-blue);

  /* Aside gets a subtle tinted background */
  --pfbt-format-aside-bg: var(--wp--preset--color--surface-soft);
}
```

That's it. The plugin's structural CSS picks up the tokens; formats you didn't paint inherit normal styling.

---

## What the plugin contributes

### Body classes

On singular post views (`is_singular('post')`):

| Class | When |
|---|---|
| `format-{slug}` | WP core (always present for any post format) |
| `pfbt-format-{slug}` | Plugin namespace; same conditions as above |
| `pfbt-format-titleless` | Aside, status, and quote (formats with hidden titles) |
| `has-post-format` | Any non-standard format |

### Post classes

The same four classes plus the WP core ones, applied via `post_class()` so they're present in archive Query Loops too.

### Pattern container classes

The display patterns (Sessions 5 + 8) emit each post in a wrapper with:

- `pfbt-format-card` — every format card
- `pfbt-format-card--{slug}` — format-specific card variant
- `pfbt-format-{slug}` — same slug used in the body class (no `--card` suffix)

Theme authors target these classes for layout (margin, padding, max-width) and the tokens for paint.

### CSS layer

All plugin CSS lives in the `@layer pfbt-format-tokens` cascade layer. Theme styles in any later layer or no layer at all override automatically — no `!important` needed.

---

## Token reference

Full reference: [`/docs/DESIGN-TOKENS.md`](DESIGN-TOKENS.md). Quick cheat sheet:

### Per format

Each non-standard format has up to four base tokens:

```
--pfbt-format-{slug}-bg       Container background
--pfbt-format-{slug}-fg       Container text color
--pfbt-format-{slug}-accent   Format icon, decorative borders, etc.
--pfbt-format-{slug}-font     Container font-family
```

### Format-specific extras

```
--pfbt-format-image-caption-font           Image-format figcaption font
--pfbt-format-chat-row-bg-odd              Odd chat lines background
--pfbt-format-chat-row-bg-even             Even chat lines background
--pfbt-format-chat-speaker-fg              Speaker name color
--pfbt-format-chat-meta-fg                 Timestamp / thread indicator
--pfbt-format-chat-rule                    Borders, dividers, thread lines
--pfbt-format-chat-highlight-bg            Highlighted message bg
--pfbt-format-chat-highlight-accent        Highlighted message border
--pfbt-format-chat-link-fg                 Inline link color
--pfbt-format-chat-link-hover-fg           Link hover
--pfbt-format-chat-code-bg                 Inline <code> background
--pfbt-format-chat-avatar-bg               Avatar fallback bg
--pfbt-format-chat-avatar-fg               Avatar initials color
```

### Sizing tokens (rarely override)

```
--pfbt-format-icon-size   Default 1em (icon scales with text)
--pfbt-format-icon-gap    Default 0.4em (gap between icon + text)
```

---

## Setting tokens from `theme.json`

Block themes (WP 6.6+) can set tokens via `theme.json`'s `styles.css`:

```json
{
  "version": 3,
  "styles": {
    "css": ":root { --pfbt-format-quote-bg: var(--wp--preset--color--surface-soft); --pfbt-format-aside-accent: var(--wp--preset--color--brand-orange); }"
  }
}
```

This is the cleanest integration — tokens land alongside other theme.json styling and respect Site Editor customization order.

---

## Setting tokens from a stylesheet

Themes that prefer a stylesheet enqueue can set tokens normally:

```css
/* style.css or a separately-enqueued sheet */
:root {
  --pfbt-format-quote-bg: var(--wp--preset--color--surface-soft);
  --pfbt-format-aside-accent: var(--wp--preset--color--brand-orange);
}
```

The plugin's CSS lives in `@layer pfbt-format-tokens`. Theme styles outside any `@layer` win automatically. If your theme uses its own layer name, declare it after `pfbt-format-tokens` in the layer order.

---

## Section styles for richer per-format treatment

Tokens cover the common cases. For richer per-format treatment, use a `theme.json` block-level variation:

```json
{
  "version": 3,
  "styles": {
    "blocks": {
      "core/post-template": {
        "variations": {
          "format-quote-card": {
            "css": ".pfbt-format-quote { padding: var(--wp--preset--spacing--40); border-left: 4px solid var(--wp--preset--color--accent); }"
          }
        }
      }
    }
  }
}
```

Reference any `pfbt-format-*` class in the variation's CSS. The variation appears in the editor's Style picker for the chosen block.

---

## Title visibility — opt-back-in

The plugin hides post titles on aside, status, and quote by default (Twenty Thirteen convention). Themes that disagree can opt back in by adding the style class to the Post Title block in their template:

```html
<!-- wp:post-title {"className":"is-style-show-format-title"} /-->
```

The plugin's CSS exempts that class from the title-hiding rule, so the title renders normally.

---

## Templates — opt in to plugin defaults, or ship your own

The plugin ships 18 starter templates (9 single-post + 9 archive variants) in `templates/v2/`. They're **opt-in via a Tools page toggle** (`Tools → Post Format Templates → Use plugin block templates`) and the matching `pfbt_use_block_templates` filter. Default off — most themes ship their own.

When the option is enabled, the plugin filters the WordPress template hierarchy to inject `single-post-{format}` (before `single-post`) and `archive-post-format-{format}` (before `taxonomy-post_format`) for posts that have a non-standard format.

Themes that prefer to ship their own templates have three options:

1. **Don't enable the toggle.** Plugin templates never register; WP routes through the theme's own hierarchy.
2. **Force-disable from the theme.** `add_filter('pfbt_use_block_templates', '__return_false');` in `functions.php` — forces off regardless of the option's setting.
3. **Override at the template-slug level.** If the option is on, themes can still ship `single-post-{format}.html` in their own `templates/` directory; WP picks the theme's version first since theme templates outrank plugin-registered ones.

---

## Patterns — drop in or override

The plugin registers 20 display patterns (10 formats × 2 variants — archive and single):

```
pfbt/aside-archive       pfbt/aside-single
pfbt/audio-archive       pfbt/audio-single
... etc, 10 formats total
```

Themes can:

- **Drop them into a Query Loop's post-template** for archive views: `<!-- wp:pattern {"slug":"pfbt/aside-archive"} /-->`
- **Drop them into a single template** for the post body: `<!-- wp:pattern {"slug":"pfbt/aside-single"} /-->`
- **Override by registering at the same slug** with theme-side `register_block_pattern('pfbt/aside-archive', [...])` calling at later priority.

The display patterns reference the post's content via `core/post-content` (single) or `core/post-excerpt` (archive). All paint comes from tokens.

---

## Format Icon block

Place the Format Icon block anywhere a post context exists (inside Query Loops, single templates, sidebar widgets that read the queried post):

```html
<!-- wp:post-formats/format-icon /-->
```

Output is an inline SVG that inherits text color via `currentColor`. Standard-format posts render nothing (the block bails). The block is locked to specific parent types: `core/post-template`, `core/group`, `core/post-meta`.

For richer customization, see the four filters in [HOOKS-REFERENCE.md](HOOKS-REFERENCE.md):
- `pfbt_format_icon_svg` — full output override
- `pfbt_format_icon_map` — slug → symbol-id mapping
- `pfbt_format_icon_sprite_url` — sprite URL
- `pfbt_format_icon_label` — accessible label text

---

## Block Bindings — bind any block attribute to format data

The `post-formats/format-data` block bindings source exposes 9 keys themes can bind to from any core block:

```
format_name             Slug ('aside', 'quote', etc.)
format_label            Localized label ('Aside', 'Quote', ...)
format_icon             Dashicon name (legacy)
format_icon_svg         Full SVG markup (same as the Format Icon block)
format_permalink_archive  URL to the format's taxonomy archive
has_format              "true" or "false"
char_count              Stripped-content character count
media_url               First URL in post content
quote_attribution       First <cite> text
link_url                External URL for link-format posts (3-layer fallback)
```

Example: bind a paragraph to render the format archive link:

```html
<!-- wp:paragraph {
  "metadata":{"bindings":{
    "content":{"source":"post-formats/format-data","args":{"key":"format_permalink_archive"}}
  }}
} -->
<p></p>
<!-- /wp:paragraph -->
```

---

## Opt-out filters — when the plugin defaults conflict

Three opt-out filters let themes that ship their own complete format styling suppress plugin defaults:

```php
// Skip the plugin's stylesheet (keeps plugin's tokens; theme paints)
add_filter( 'pfbt_enqueue_format_styles', '__return_false' );

// Skip the plugin's theme.json palette merge (legacy 1.x — 2.0 ships
// no palette, so this filter is a no-op in 2.0 but kept for compat)
add_filter( 'pfbt_merge_format_palette', '__return_false' );

// Skip the plugin's get_block_templates filter additions (the
// add_block_templates() body that registers single-format-* templates)
add_filter( 'pfbt_register_format_templates', '__return_false' );

// Skip the opt-in block templates feature entirely
add_filter( 'pfbt_use_block_templates', '__return_false' );
```

---

## Migration from PFBT 1.x

PFBT 2.0 is a major break. See `CHANGELOG.md` for the full upgrade path. Quick summary:

| Old (1.x) | New (2.0) |
|---|---|
| `--wp--preset--color--format-aside-bg` | `--pfbt-format-aside-bg` |
| `--wp--preset--color--format-aside-border` | `--pfbt-format-aside-accent` |
| `--wp--preset--color--format-quote-border` | `--pfbt-format-quote-accent` |
| `--wp--preset--color--format-link-border` | `--pfbt-format-link-accent` |
| `--wp--preset--color--format-chat-bg` | `--pfbt-format-chat-row-bg-odd` and/or `--pfbt-format-chat-row-bg-even` |
| Pattern slugs `pfpu/{format}` | `pfbt/{format}-archive` and `pfbt/{format}-single` |
| Plugin's 12 stock palette entries | Removed — themes own palette |

Sites that customized via the old token names need to rename to the new ones. Sites that didn't customize see no visible change (they saw stock Gutenberg colors before; they see the theme's normal post styling now — usually a visual improvement).

---

## Reference

- Token reference: [`/docs/DESIGN-TOKENS.md`](DESIGN-TOKENS.md)
- Filters and actions: [`/docs/HOOKS-REFERENCE.md`](HOOKS-REFERENCE.md)
- Layout reference: [Twenty Thirteen content-{format}.php](https://themes.trac.wordpress.org/log/twentythirteen)
