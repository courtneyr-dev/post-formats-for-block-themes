---
title: Design tokens
description: "Reference for the --pfbt-format-* design tokens: what each token paints, its neutral default, and how themes override it."
---

**Version:** 2.0.0
**File:** `/styles/format-tokens.css`
**Layer:** `pfbt-format-tokens` (CSS `@layer`)
**Contract:** Plugin owns layout. Theme owns paint.

---

## The hard contract

The plugin's responsibility is **structure** — which blocks render, in what order, with what classes, and how the page is laid out (titles hidden on aside / status / quote, alternating chat rows, format-icon positioned in a known slot, video at 16:9, gallery as a grid).

The theme's responsibility is **paint** — every color, font, spacing scale, border radius, and shadow value.

The handshake is one rule: every plugin CSS reference to color or typography routes through `var(--pfbt-format-X-*, NEUTRAL)` where `NEUTRAL` is one of:

- `transparent` — for backgrounds
- `inherit` — for foreground colors and font families
- `currentColor` — for accents that should follow the surrounding text color
- `none` — for shadows, borders, etc.

If the theme sets the token, the plugin's structural treatment gets painted in the theme's brand. If the theme stays silent, the format inherits the theme's normal styling — distinctive **structure** (no title, striped chat rows, framed image), no distinctive **paint**.

This is the inverse of pre-2.0 behavior, which shipped stock Gutenberg colors as fallbacks (`#f0f0f1`, `#0073aa`, `#cccccc`) so every format always had visual identity. The 2.0 contract removes those defaults: themes that customized via the old `--wp--preset--color--format-*` tokens must migrate to the new `--pfbt-format-*` tokens **and** ensure they set them. **This is a major-bump breaking change** — see migration notes at the bottom.

---

## How to set a token

### From a block theme's `theme.json` (recommended)

```json
{
  "version": 3,
  "styles": {
    "css": ":root { --pfbt-format-quote-bg: var(--wp--preset--color--surface-soft); --pfbt-format-quote-accent: var(--wp--preset--color--brand-accent); }"
  }
}
```

### From a stylesheet enqueued after the plugin's tokens

```css
:root {
  --pfbt-format-quote-bg: var(--wp--preset--color--surface-soft);
  --pfbt-format-quote-accent: var(--wp--preset--color--brand-accent);
  --pfbt-format-quote-font: "Source Serif 4", serif;
}
```

Order doesn't matter for layer-vs-non-layer; theme rules outside `@layer pfbt-format-tokens` always win. If your theme uses its own `@layer`, declare it after `pfbt-format-tokens` in the layer order.

### Per-format only — never set globally

Themes should override only the formats they care about. Tokens left at their defaults leave that format inheriting the theme's normal styling. There is no "format treatment" baseline that has to be opted out of.

---

## Token reference

### Sizing tokens

These are structural — most themes won't override.

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-icon-size` | `1em` | Format Icon block font-size (icon scales with surrounding text) |
| `--pfbt-format-icon-gap` | `0.4em` | Gap between icon SVG and any sibling text (used in `.pfbt-format-icon` and `.pfbt-format-gallery__count`) |

### Per-format paint tokens

Every value defaults to a neutral fallback. **No format paints anything by default.**

#### Aside

| Token | Default | What this paints | What happens if you don't set it |
| --- | --- | --- | --- |
| `--pfbt-format-aside-bg` | `transparent` | Background of `.pfbt-format-aside` container | Container has no background; theme's normal post styling shows through |
| `--pfbt-format-aside-fg` | `inherit` | Text color of `.pfbt-format-aside` container | Inherits from parent (usually post-content text color) |
| `--pfbt-format-aside-accent` | `currentColor` | Format icon color, decorative borders if a theme adds them | Icon is the same color as surrounding text |
| `--pfbt-format-aside-font` | `inherit` | Font-family of `.pfbt-format-aside` container | Inherits theme's body font |

#### Status

Same shape as Aside.

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-status-bg` | `transparent` | Container background |
| `--pfbt-format-status-fg` | `inherit` | Container text color |
| `--pfbt-format-status-accent` | `currentColor` | Format icon, optional accent borders |
| `--pfbt-format-status-font` | `inherit` | Container font-family |

#### Quote

Same shape. The `accent` token is what most themes use for the decorative `"` glyph or quote-mark.

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-quote-bg` | `transparent` | Container background |
| `--pfbt-format-quote-fg` | `inherit` | Container text color |
| `--pfbt-format-quote-accent` | `currentColor` | Decorative quote glyph, format icon, attribution rule |
| `--pfbt-format-quote-font` | `inherit` | Container font-family (often a serif for editorial quote feel) |

#### Link

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-link-bg` | `transparent` | Container background (Twenty Thirteen used a tinted card) |
| `--pfbt-format-link-fg` | `inherit` | Container text color |
| `--pfbt-format-link-accent` | `currentColor` | External-link arrow indicator (`→`), format icon |
| `--pfbt-format-link-font` | `inherit` | Container font-family |

#### Image

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-image-bg` | `transparent` | Container background (commonly used for a full-bleed dark mat) |
| `--pfbt-format-image-fg` | `inherit` | Container text color (caption + meta) |
| `--pfbt-format-image-caption-font` | `inherit` | `.wp-element-caption` font-family inside the format |

There's no `--pfbt-format-image-accent` because Image format borders should generally be set via the theme's normal `core/image` rules — the format-specific accent doesn't have a clear use.

#### Gallery

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-gallery-bg` | `transparent` | Container background |
| `--pfbt-format-gallery-fg` | `inherit` | Container text color |
| `--pfbt-format-gallery-accent` | `currentColor` | Photo count badge, format icon |

#### Video

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-video-bg` | `transparent` | Container background (often a dark mat for letterboxed video) |
| `--pfbt-format-video-fg` | `inherit` | Container text color |
| `--pfbt-format-video-accent` | `currentColor` | Format icon, optional play-button accent |

#### Audio

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-audio-bg` | `transparent` | Container background. Themes that want an inverted "audio booth" set this to a dark color. **The plugin never forces an inverted treatment.** |
| `--pfbt-format-audio-fg` | `inherit` | Container text color |
| `--pfbt-format-audio-accent` | `currentColor` | Format icon, optional waveform/seek-bar accent |

#### Chat

| Token | Default | Paints |
| --- | --- | --- |
| `--pfbt-format-chat-row-bg-odd` | `transparent` | Background of odd-numbered `.pfbt-chat-line` rows |
| `--pfbt-format-chat-row-bg-even` | `transparent` | Background of even-numbered `.pfbt-chat-line` rows |
| `--pfbt-format-chat-speaker-fg` | `inherit` | Color of `.pfbt-chat-speaker` (the bold name above each line) |
| `--pfbt-format-chat-font` | `inherit` | Font-family of speaker names. Themes often use a monospace or condensed sans for chat feel |

To get visible row striping a theme **must** set both `--pfbt-format-chat-row-bg-odd` and `--pfbt-format-chat-row-bg-even` (one of which can stay transparent). Setting only one produces visible-on-invisible striping that may not have enough contrast — the plugin doesn't guess what looks right.

---

## Structural classes

The plugin emits these classes on the body, post, and per-format containers. Themes can target them in any selector — they are part of the public CSS API.

### Body classes (added by the plugin's body class filter)

| Class | When |
| --- | --- |
| `format-{slug}` | WordPress core; present whenever `get_post_format()` returns the slug |
| `pfbt-format-{slug}` | Plugin; same conditions as above, distinct namespace for theme targeting |
| `pfbt-format-titleless` | Plugin; only on aside, status, quote |

### Post classes (added by the plugin's post class filter)

| Class | When |
| --- | --- |
| `has-post-format` | Always when `get_post_format()` returns truthy |
| `pfbt-format-{slug}` | Same as body class |
| `pfbt-format-titleless` | Aside, status, quote |

### Per-format container classes (emitted by patterns)

These are wrapping `<div>` or `<article>` classes inside the rendered post content. Patterns add them to the outermost block of the format card.

| Class | Where |
| --- | --- |
| `pfbt-format-aside` | Aside pattern outer wrapper |
| `pfbt-format-status` | Status pattern outer wrapper |
| `pfbt-format-quote` | Quote pattern outer wrapper |
| `pfbt-format-link` | Link pattern outer wrapper |
| `pfbt-format-image` | Image pattern outer wrapper |
| `pfbt-format-gallery` | Gallery pattern outer wrapper |
| `pfbt-format-video` | Video pattern outer wrapper |
| `pfbt-format-audio` | Audio pattern outer wrapper |

### Format Icon block classes

| Class | Where |
| --- | --- |
| `pfbt-format-icon` | Outer `<span>` of the Format Icon block |
| `pfbt-format-icon--{slug}` | Modifier on the same span; lets themes write per-format icon rules without targeting `[data-format]` |

### Chat-specific structural classes

| Class | Where |
| --- | --- |
| `pfbt-chat-line` | Each line in a chat log (one speaker turn) |
| `pfbt-chat-speaker` | The speaker's name within a `.pfbt-chat-line` |

### Opt-in style class

| Class | Effect |
|---|---|
| `is-style-show-format-title` | Added to `<!-- wp:post-title -->` to opt back into a visible title on aside, status, or quote |

---

## What the plugin's CSS NEVER includes

The contract excludes:

- Any hex code (`#aabbcc`) outside `tests/fixtures/`
- `rgb()`, `rgba()`, `hsl()`, `hsla()`, `oklch()` literal color values
- Named CSS colors (`black`, `red`, `cornflowerblue`, etc.)
- Hard-coded font family names ("Helvetica", "Georgia", etc.)
- Spacing scales beyond `0`, `1px`, `1em`, `100%`, `50%` (structural minimums)
- Font sizes beyond `1em` and `inherit`
- Shadows, border radii, opacity values that imply a color decision

The test `tests/unit/test-no-color-leakage.php` enforces this rule. Any commit that adds a forbidden value to plugin CSS or pattern markup fails CI.

Opt-in block style variations (`styles/{quote,image,gallery}-variations/`) are excluded — they follow their own three-level token contract (`--pfbt-*` override → theme palette preset → fixed default), documented in [`BLOCK-STYLE-VARIATIONS.md`](/post-formats-for-block-themes/block-style-variations/#color-the---pfbt--token-contract).

---

## Migration from 1.x → 2.0

Pre-2.0, format CSS used the `--wp--preset--color--format-*` namespace with stock Gutenberg fallbacks. Themes that customized via the old tokens need a one-time migration:

| Old token (1.x) | New token (2.0) |
| --- | --- |
| `--wp--preset--color--format-aside-bg` | `--pfbt-format-aside-bg` |
| `--wp--preset--color--format-aside-border` | `--pfbt-format-aside-accent` |
| `--wp--preset--color--format-status-bg` | `--pfbt-format-status-bg` |
| `--wp--preset--color--format-link-bg` | `--pfbt-format-link-bg` |
| `--wp--preset--color--format-link-border` | `--pfbt-format-link-accent` |
| `--wp--preset--color--format-quote-border` | `--pfbt-format-quote-accent` |
| `--wp--preset--color--format-quote-accent` | `--pfbt-format-quote-accent` |
| `--wp--preset--color--format-gallery-border` | `--pfbt-format-gallery-accent` |
| `--wp--preset--color--format-image-border` | (removed; theme's `core/image` rules) |
| `--wp--preset--color--format-video-bg` | `--pfbt-format-video-bg` |
| `--wp--preset--color--format-audio-bg` | `--pfbt-format-audio-bg` |
| `--wp--preset--color--format-chat-bg` | `--pfbt-format-chat-row-bg-odd` and `--pfbt-format-chat-row-bg-even` (now stripes, not single bg) |

The plugin's own `theme.json` no longer ships the 12 `format-*-bg` / `format-*-border` palette entries. Themes that relied on them appearing in the WP color picker now define their own equivalents in their own `theme.json`.

**Sites that did not customize tokens see no visible color change after upgrading.** They saw stock Gutenberg colors before; they see the theme's normal post styling now. Most themes will look BETTER — formatting that previously fought brand identity now inherits it.

---

## Layout reference

The plugin's layout decisions (which blocks render, in what order, with which behaviors) trace to WordPress Twenty Thirteen's `content-{format}.php` template parts. Source-of-truth canonical:

> <https://themes.trac.wordpress.org/log/twentythirteen>

Twenty Thirteen used `get_template_part( 'content', get_post_format() )` from both `index.php` and `single.php`. One template part defined the archive teaser **and** the single. That shared structure is the architectural reason archives and singles felt like the same thing — the plugin reproduces this idea via patterns split into `pfbt/{slug}-archive` and `pfbt/{slug}-single` flavors that share one base file.

Per-format layout matrix (Twenty Thirteen):

| Format | Header | Footer |
| --- | --- | --- |
| Aside | NO | date + edit (single adds entry-meta + author bio) |
| Status | NO | entry-meta (date + author + edit) |
| Quote | NO | entry-meta + comments-link |
| Link | YES — title is `<a href="{external}">` | entry-date inline (single adds entry-meta + author bio) |
| Image | YES | entry-meta + comments-link |
| Gallery | YES | entry-meta + comments-link |
| Video | YES | entry-meta + comments-link |
| Audio | YES | entry-meta only |
| Chat | YES | entry-meta only |

The plugin borrows the shape: aside / status / quote put `the_content()` first with no header; the other six put a header with title + meta first. Colors and fonts are entirely the theme's choice.
