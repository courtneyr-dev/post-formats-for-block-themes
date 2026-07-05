---
name: dev-post-formats-for-block-themes
description: Use when working on the Post Formats for Block Themes WordPress plugin (prefix pfbt_) — building, testing, linting, running wp-env, debugging e2e/accessibility tests, or preparing a release. Covers CI matrix versions, wp-env ports, branch conventions, the wp.org auto-deploy gate, and known gotchas from prior debugging sessions.
---

# Post Formats for Block Themes — dev workflow

## Setup

```bash
composer install     # PHP dev toolchain (phpcs, phpstan, phpunit) — requires PHP 8.2+
npm ci                # or npm install — JS toolchain, Playwright, wp-scripts
npx wp-env start      # spins up WordPress at localhost:8888 (only needed for e2e/a11y tests or manual QA)
```

The plugin itself supports PHP 7.4+ for end users (see `CLAUDE.md` Min PHP). That's separate from the composer dev toolchain requirement below — don't conflate the two.

## Build / test / lint commands

```bash
# JavaScript
npm run build                # wp-scripts build src/editor/index.js
npm run start                # watch mode

# PHP quality
composer phpcs                # WPCS lint (uses .phpcs.xml)
composer phpcbf               # WPCS auto-fix
composer phpstan              # static analysis, memory-limit=2G
composer phpunit              # unit tests (uses phpunit.xml.dist)
composer test                 # phpcs + phpstan + phpunit

# Testing
npm run test:js               # wp-scripts test-unit-js
npm run test:e2e              # playwright test tests/e2e
npm run test:a11y             # playwright test tests/accessibility
npm run test:all              # composer test && npm run test:a11y && npm run test:e2e

# i18n
composer i18n                 # wp i18n make-pot . languages/post-formats-for-block-themes.pot
```

`composer phpcs` / `composer phpunit` use the repo's own `.phpcs.xml` and `phpunit.xml.dist` — no extra flags needed locally to match CI.

### CI matrix (what actually runs in `.github/workflows/ci.yml`)

- **Lint job:** PHP 8.2 only. `composer phpcs -- --report=checkstyle | cs2pr --graceful-warnings`, then `composer phpstan`.
- **PHPUnit job:** matrix of PHP 8.2/8.3/8.4 × WordPress 6.9/7.0/latest, MySQL 8.0 service. Runs `bin/install-wp-tests.sh` (needs `subversion` — the workflow explicitly `apt-get install -y subversion` first since GitHub dropped svn from Ubuntu runners in 2024), then `composer phpunit`.
- **E2E job:** Node 20. `npm ci`, `npx playwright install --with-deps`, starts `@wordpress/env@latest`, disables the editor welcome guide and mints an app password via wp-cli, then `npm run test:e2e` with `WP_BASE_URL=http://localhost:8888`.
- **Security job:** `composer audit`, plus split npm audit — `npm audit --omit=dev --audit-level=moderate` (shipped deps gate at moderate) and `npm audit --audit-level=critical` (full dev toolchain gates at critical only, because `@wordpress/scripts` carries advisories with no fixed release).
- **Build job:** only on push to `main`, needs [phpunit, e2e, security] to pass first. Builds JS, `composer install --no-dev`, rsyncs via `.distignore` into `dist/`, zips.

This workflow triggers on push to main/develop and PRs to main. It does not deploy anywhere — safe to run/re-run freely.

## wp-env usage and ports

`.wp-env.json` is minimal (`{ "plugins": ["."] }`) — no port override file exists, so it uses wp-env defaults:

- Site: `http://localhost:8888`
- Tests site: `http://localhost:8889`

For e2e specifically, `playwright.config.js` uses `globalSetup: tests/global-setup.js` and a shared `storageState: tests/.auth/admin.json` across every project — this logs in as admin once instead of per-test, which avoids a WordPress session-token race (see Gotchas). `baseURL` defaults to `http://localhost:8888`, overridable via `WP_BASE_URL`. In CI, the welcome guide is disabled and an app password is minted via wp-cli before e2e runs.

## Branch / PR conventions

- PRs merge straight to `main` — this is trunk-based in actual practice (see `git log --oneline --grep="Merge pull request"` for the pattern: PR #13, #12, #11, #10, #9, #8, #7, #6, #5, #4, #3, #1, all against main).
- `BRANCHING-STRATEGY.md` documents a fuller git-flow model (main/develop/release/hotfix branches) — that's aspirational, not what's followed day to day. An `origin/develop` branch exists but isn't the active integration branch in recent history.
- Branch naming example: docs-only work uses `docs/<slug>` (e.g. this file's own branch, `docs/kickoff-playbook-retrofit`).

## Release steps

1. Confirm Courtney has explicitly signed off on cutting this specific release — never tag/release/deploy without that go-ahead.
2. Cut the GitHub release (or tag) as normal for this repo's process.
3. **Immediately check the deploy actually went out** — a published release is not proof of a live wp.org deploy (see Gotchas below). Don't consider the release done until the deploy run shows green.

`deploy-wporg.yml` triggers only on `release: types: [published]` or manual `workflow_dispatch` (with a `version` input). It strips the leading "v" from the tag to match `readme.txt` Stable tag, runs PHP 8.2 / Node 20, `composer install --no-dev --optimize-autoloader`, `npm run build`, generates translations via `composer i18n` (3 retries, non-fatal — a `::warning::` if `develop.svn.wordpress.org` is unreachable), then deploys via `10up/action-wordpress-plugin-deploy@stable` to WordPress.org SVN (slug `post-formats-for-block-themes`) using `SVN_USERNAME`/`SVN_PASSWORD`, and uploads a release zip back to the GitHub release.

## Deploy verification

After any release, check all three:

```bash
gh run list --workflow=deploy-wporg.yml     # confirm the deploy run is green, not just triggered
```

- `readme.txt` Stable tag matches the version you just released.
- The plugin's wp.org page reflects the new version.

Don't stop at "the GitHub release shows as published" — see the gotcha below for why that's not sufficient.

## Gotchas

### 1. A published release does not mean the plugin is live on wp.org

The v2.3.0 release-triggered deploy (started 2026-07-03T16:05:53Z, run id 28671661759) failed. Every manual `workflow_dispatch` retry that day also failed (16:17, 16:27, 16:30, 16:55, 17:20, 17:51, 18:13, 18:36) until the run at 2026-07-03T18:59:08Z (run id 28678716348) finally succeeded — about 3 hours after the release was published. The fixes were commits `92d0669` ("ci: fix wp.org deploy — install wp-cli, add manual dispatch, normalize version") and `14b1990` ("ci: make .pot generation non-fatal in the wp.org deploy"). The release stayed "published" the whole time regardless of deploy success — GitHub's release state and the wp.org deploy state are decoupled. Always run `gh run list --workflow=deploy-wporg.yml` after cutting a release, not just check the release page.

### 2. Format Badge — three stacked bugs (fixed in v2.3.0, commit 135cb40)

All three landed the same day as the v2.3.0 release, well before it was published (fix commits 09:48-09:49 EDT; release published 16:05:51Z) — so v2.3.0 as released already includes this fix. No further release is needed for it specifically.

- **Wrong templates outranking the theme's own single template.** `add_block_templates()` offered all nine `single-format-*` templates to every `wp_template` query. Unrequested slugs with no hierarchy position sorted first, so `single-format-aside` outranked the theme's real `single` template on every post. Fixed by only adding format templates when the query actually requests them.
- **Block Hooks never fired on plugin-built templates.** Plugin-built `WP_Block_Template` objects bypass core's `_build_block_template_result_from_file()` — the only place core applies Block Hooks — so the badge's `blockHooks: {core/post-title: before}` never injected on plugin-built templates. Fixed by running `apply_block_hooks_to_content()` on the built template in both filters.
- **Icon rendered at zero size for logged-out visitors.** Nothing enqueued Dashicons on the front end, so the badge icon had no font to render from. Fixed by declaring the dashicons style handle in `block.json`.

### 3. `register_block_style()` CSS never loads on block-theme front ends (general gotcha, not PFBT-specific)

Block themes render template HTML before `wp_head`/`enqueue_block_assets` fires, so core's conditional `style_handle` loader for `register_block_style()` never triggers for content already rendered. Fixed (commit 23429af) by keeping `register_block_style()` for the editor's style picker, but adding a `render_block` filter registered at `init` (before template render) that maps block-name + is-style-class to its stylesheet/view-module assets and enqueues them on demand when a block actually renders using that style. Output prints via `wp_footer`. Worth remembering for any future block work in this repo — the standard `register_block_style()` front-end loading path silently doesn't work here.

### 4. Three e2e/parallel-login races (fixed in commit 3e8aff6, plus b87db63)

- `wp_attempt_focus()` on wp-login fires ~200ms after page load and re-selects the username field. Typing that straddles this window steals/garbles the fill. Fixed by waiting for focus to land before filling.
- Parallel logins as the same WP user lose each other's session tokens (lost update on `session_tokens` user meta) and bounce to `wp-login.php?reauth=1`. Fixed with Playwright's `globalSetup` (`tests/global-setup.js`) logging in once and sharing `storageState` (`tests/.auth/admin.json`) across every project; the login helper no-ops when an auth cookie is already present.
- Dismissing the welcome guide by clicking its Close button raced the format modal's overlay — stacked modals intercept pointer events. Fixed by flipping the guide preference directly through `wp.data` instead of clicking. Related: commit b87db63 made the plugin's own format modal defer its 500ms open timer until `select('core/edit-post').isFeatureActive('welcomeGuide')` reports the guide dismissed — gating on this feature-active check specifically (not raw `preferences.get()`) because a fresh user has no scope set (reads as inactive while the guide IS showing), while a stale value in a scope the guide doesn't write would suppress the modal forever.
