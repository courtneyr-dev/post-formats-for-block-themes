# Polaroid Audit — Phase 0 of v2.1.0 Image/Gallery Variations PR

**Date:** 2026-04-30
**Source:** Courtney Robertson's staging site (`https://qkf.b0d.myftpupload.com/`)
**Active theme:** `courtneyr-child` (v0.5.61) — child of Ollie. Token vocabulary is Ollie-compatible.

## Finding: no existing polaroid markup to migrate

The v2.1.0 brief's Phase 4 (Polaroid Migration) assumed inline-styled polaroid markup exists on Courtney's homepage and needs to be rewritten to use the new `is-style-polaroid` registered variation. **It doesn't exist.**

### Search methodology

Direct DB queries against `wp_6f38df5e41_posts` (post_status=`publish`, post_type IN `page`,`post`):

| Pattern searched | Matches |
| --- | --- |
| `LIKE '%polaroid%'` | 0 |
| `LIKE '%cr-polaroid%'` | 0 |
| `LIKE '%cr-rotate%'` | 0 |
| `LIKE '%transform:rotate(-%'` | 0 |
| `LIKE '%transform:rotate(1%'` | 0 |
| `LIKE '%transform:rotate(2%'` | 0 |
| `LIKE '%transform:rotate(3%'` | 0 |
| `REGEXP 'rotate\(-?[1-9]...deg'` AND `LIKE '%wp:image%'` | 0 |

### What's actually on the homepage (post 2651)

The pirate-hat photo lives in a `core/cover` block, not a `core/image` block. Markup excerpt:

```html
<!-- wp:cover {"url":".../cropped-courtney-pirate-jpeg.avif","id":10687,"alt":"...","dimRatio":0,"customOverlayColor":"#ae998f","minHeight":100,"minHeightUnit":"%","contentPosition":"bottom center","style":{"border":{"radius":"10px"},"spacing":{...}},"layout":{"type":"constrained"}} -->
```

No rotation, no polaroid frame, no inline polaroid styling. The visual rotation effects on the homepage come from:

1. The theme's `cr-rotate-*` tokens applied via class (theme stylesheet, not block inline styles).
2. The Outermost Icon Block's `transform:rotate(0deg)` (decorative — no actual rotation, the kit just leaves the property at zero).

### Zine-aesthetic rotation tokens (theme, not posts)

The theme defines `--cr-rotate-pos`, `--cr-rotate-neg`, `--cr-rotate-tilt` and applies them in `assets/css/components.css` to elements like `.cr-icon-avatar`, `.cr-callout`, `.cr-tape`. Those rotations apply to UI elements that the registered `is-style-polaroid` variation does NOT cover (and shouldn't — `is-style-polaroid` is for `core/image` blocks specifically).

## Decision: Phase 4 is a no-op

Phase 4 of the brief is skipped. There is nothing to migrate. The `is-style-polaroid` variation still ships and is fully testable via:

- The Phase 2 fixture page used during development.
- Phase 5 visual regression baselines on a fixture post containing one `core/image` block with `is-style-polaroid` applied.
- Author docs (`docs/BLOCK-STYLE-VARIATIONS.md`) showing what the variation looks like.

PR description will note: "Phase 4 (polaroid migration) was a no-op — no existing inline polaroid markup exists on the source site."

## Verification recipe (for future re-audit)

```bash
wp @staging db query "SELECT ID, post_title FROM wp_<prefix>_posts \
  WHERE post_status='publish' AND post_type IN ('page','post') \
  AND (post_content LIKE '%polaroid%' \
    OR post_content REGEXP 'rotate\\\\(-?[1-9][0-9]?(\\\\.[0-9]+)?deg' \
  );"
```

Run this before Phase 5 ships. If it ever returns results, Phase 4 reactivates.
