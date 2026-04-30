# Post-it Migration Audit — Phase 0 of v2.2.0 Quote Variations

**Date:** 2026-04-30
**Source:** Courtney Robertson's staging site (`https://qkf.b0d.myftpupload.com/`)
**Active theme:** `courtneyr-child` (v0.5.63), child of Ollie. Token vocabulary is Ollie-compatible.

## Finding: no existing post-it markup to migrate

The v2.2.0 brief's Phase 3 (Post-it Migration) assumed inline-styled post-it markup exists on Courtney's site and needs to be rewritten to use the new `is-style-post-it` registered variation. **It doesn't exist.**

### Search methodology

Direct DB queries against `wp_6f38df5e41_posts` (post_status=`publish`, post_type IN `page`,`post`):

| Pattern searched | Matches |
| --- | --- |
| `LIKE '%post-it%'` | 0 |
| `LIKE '%postit%'` | 0 |
| `LIKE '%sticky-note%'` | 0 |
| `LIKE '%cr-postit%'` | 0 |
| `REGEXP 'wp:quote.*"style"'` (inline-styled core/quote blocks) | 0 |

### What's actually on the homepage (post 2651)

Searched the homepage for `wp:quote`, post-it, sticky-note, and inline-style signatures — zero matches. The homepage uses `core/cover`, `core/group`, and pattern blocks; no `core/quote` blocks at all.

### Pattern across the site

The site has many posts with `wp:quote` markup (audit returned 21 posts containing the string), but **none** have inline styles directly on the quote block, and **none** carry post-it / sticky-note class names. They're all default core quote rendering.

This mirrors the v2.1.0 polaroid audit result — Courtney's site uses the theme's design tokens applied via class, not inline-styled block-level treatments.

## Decision: Phase 3 is a no-op

Phase 3 of the v2.2.0 brief is skipped. The `is-style-post-it` variation still ships and is fully testable via:

- The Phase 1 fixture page used during development.
- Phase 4 visual regression baselines on a fixture post containing one `core/quote` block with `is-style-post-it` applied + a `<cite>` element.
- Author docs (`docs/BLOCK-STYLE-VARIATIONS.md`) showing what the variation looks like.

PR description will note: "Phase 3 (post-it migration) was a no-op — no existing post-it markup exists on the source site."

## Verification recipe (for future re-audit)

```bash
wp @staging db query "SELECT ID, post_title FROM wp_<prefix>_posts \
  WHERE post_status='publish' AND post_type IN ('page','post') \
  AND (post_content LIKE '%post-it%' \
    OR post_content LIKE '%postit%' \
    OR post_content REGEXP 'wp:quote[^>]*\"style\"' \
  );"
```

Run this before Phase 4 ships. If it ever returns results, Phase 3 reactivates.
