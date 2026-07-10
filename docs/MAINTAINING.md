# Maintaining the Post Formats for Block Themes documentation

The user docs site at <https://courtneyr-dev.github.io/post-formats-for-block-themes/> builds from this directory with [Astro Starlight](https://starlight.astro.build/). This guide covers day-to-day updates. The full cross-plugin standard (page templates, writing style, accessibility and SEO rules) lives in the maintainer's docs workspace: `plugin-docs/wiki/standards/plugin-docs-standard.md`.

## Layout

- `src/content/docs/` — the published pages (Markdown + frontmatter). Only files here are built.
- `src/assets/screenshots/` — screenshot sources; Astro emits optimized, dimensioned images.
- `src/site-meta.mjs` — the plugin's name, URLs, versions. Update this when the plugin header changes.
- `src/components/` — shared breadcrumb and head overrides. Keep in sync with the other plugin repos.
- Everything else at `docs/` root (internal notes, plans, this file) is **not** published.

## Edit a page

1. Edit the file in `src/content/docs/`. Every page needs unique `title` and `description` frontmatter (description 120–155 characters).
2. Internal links are root-relative and include the base path: `/post-formats-for-block-themes/settings/`.
3. Screenshots: reference relatively (`../../assets/screenshots/name.png`) with accurate alt text and a caption paragraph after the image.

## Preview and validate locally

```sh
cd docs
npm ci                 # first time
npm run dev            # live preview at localhost:4321/post-formats-for-block-themes/
npm run build          # must pass before pushing
npm run lint:md        # Markdown structure
npm run lint:prose     # Vale (brew install vale)
npm run check:links    # link check over the built site (SKIP_EXTERNAL=1 for offline)
npm run check:a11y     # axe over key pages (npx playwright install chromium once)
```

## Publish

Open a PR. CI (`.github/workflows/docs.yml`) builds and runs every check on the PR; merging to `main` deploys to GitHub Pages automatically. Never re-enable the legacy Jekyll build — it publishes every Markdown file in `docs/` including internal notes.

## When plugin behavior changes

Follow the release checklist in the PR template: update affected pages, recapture affected screenshots (`npm run screenshots:docs` at the repo root), keep `src/site-meta.mjs` and the landing page's version/requirements text matching the plugin header, and check README.md and readme.txt links still hold.

## Add a page

1. Create `src/content/docs/<slug>.md` with frontmatter.
2. Add the slug to the right sidebar group in `astro.config.mjs`.
3. Link it from at least one related page — no orphans.
