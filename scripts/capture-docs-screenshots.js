#!/usr/bin/env node
/**
 * Capture documentation screenshots for Post Formats for Block Themes.
 *
 * Boots a disposable WordPress via WordPress Playground CLI (no Docker needed),
 * mounts this plugin, seeds demo content (placeholder media, a gallery draft,
 * a published quote post), and captures the screens listed in
 * docs/src/content/docs/screenshots.md into docs/src/assets/screenshots/.
 *
 * Prerequisites:
 *   - Node.js 18+
 *   - npm install (installs Playwright from devDependencies)
 *   - npx playwright install chromium (once, to download the browser)
 *
 * Usage:
 *   node scripts/capture-docs-screenshots.js
 *
 * Environment variables:
 *   WP_BASE_URL      Capture against an already-running WordPress instead of
 *                    launching Playground (must be logged-in-accessible or a
 *                    Playground --login server). No credentials are stored here.
 *   PLAYGROUND_PORT  Port for the disposable Playground server (default 9400).
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const REPO_ROOT = path.resolve(__dirname, '..');
const OUT_DIR = path.join(REPO_ROOT, 'docs', 'src', 'assets', 'screenshots');
const PORT = process.env.PLAYGROUND_PORT || '9400';
const EXTERNAL_URL = process.env.WP_BASE_URL || '';
const BASE = EXTERNAL_URL || `http://127.0.0.1:${PORT}`;

function resolveChromium() {
	try {
		return require('playwright').chromium;
	} catch (e) {
		try {
			return require('@playwright/test').chromium;
		} catch (e2) {
			console.error(
				'Playwright is not installed. Run `npm install` in the repo root, then `npx playwright install chromium`.'
			);
			process.exit(1);
		}
	}
}

async function waitForServer(url, timeoutMs) {
	const deadline = Date.now() + timeoutMs;
	while (Date.now() < deadline) {
		try {
			const res = await fetch(url, { redirect: 'manual' });
			if (res.status > 0 && res.status < 500) {
				return;
			}
		} catch (e) {
			// Not up yet.
		}
		await new Promise((r) => setTimeout(r, 2000));
	}
	throw new Error(`WordPress did not become reachable at ${url} within ${timeoutMs / 1000}s.`);
}

function launchPlayground() {
	console.log(`Starting WordPress Playground on port ${PORT} (this downloads WordPress on first run)...`);
	const child = spawn(
		'npx',
		['--yes', '@wp-playground/cli@latest', 'server', '--auto-mount', REPO_ROOT, '--login', '--port', PORT],
		{ stdio: ['ignore', 'pipe', 'pipe'] }
	);
	child.stdout.on('data', (d) => process.stdout.write(`[playground] ${d}`));
	child.stderr.on('data', (d) => process.stderr.write(`[playground] ${d}`));
	child.on('exit', (code) => {
		if (code && code !== 0 && !shuttingDown) {
			console.error(`Playground exited unexpectedly with code ${code}.`);
			process.exit(1);
		}
	});
	return child;
}

let shuttingDown = false;

(async () => {
	fs.mkdirSync(OUT_DIR, { recursive: true });
	const chromium = resolveChromium();

	let playground = null;
	if (!EXTERNAL_URL) {
		playground = launchPlayground();
	}

	try {
		await waitForServer(BASE + '/', 240000);
		console.log(`WordPress is up at ${BASE}`);

		const browser = await chromium.launch();
		const ctx = await browser.newContext({
			viewport: { width: 1280, height: 800 },
			deviceScaleFactor: 2,
		});
		const page = await ctx.newPage();

		// Prime the logged-in admin session (Playground --login authenticates the first visit).
		await page.goto(BASE + '/wp-admin/', { waitUntil: 'networkidle' });
		if (!/wp-admin/.test(page.url()) || /wp-login/.test(page.url())) {
			throw new Error(
				`Could not reach a logged-in wp-admin at ${BASE}. If you passed WP_BASE_URL, make sure the session does not require interactive login.`
			);
		}

		// Dismiss the editor welcome guide before any editor visit.
		await page.addInitScript(() => {
			const prefs = {
				'core/edit-post': { welcomeGuide: false, fullscreenMode: false },
				core: { welcomeGuide: false },
			};
			try {
				localStorage.setItem('WP_PREFERENCES_USER_1', JSON.stringify(prefs));
			} catch (e) {
				/* no-op */
			}
		});

		const shoot = async (file) => {
			const target = path.join(OUT_DIR, file);
			await page.screenshot({ path: target });
			console.log(`captured ${path.relative(REPO_ROOT, target)}`);
		};

		// REST helpers (cookie session shared with the browser context, plus a
		// fresh REST nonce from the core rest-nonce ajax action).
		const nonceRes = await ctx.request.get(BASE + '/wp-admin/admin-ajax.php?action=rest-nonce');
		if (!nonceRes.ok()) {
			throw new Error(`Could not fetch a REST nonce (HTTP ${nonceRes.status()}).`);
		}
		const nonce = (await nonceRes.text()).trim();

		const rest = async (route, options) => {
			const res = await ctx.request.post(BASE + '/wp-json/wp/v2/' + route, {
				...options,
				headers: { 'X-WP-Nonce': nonce, ...(options.headers || {}) },
			});
			if (!res.ok()) {
				throw new Error(`REST ${route} failed (HTTP ${res.status()}): ${await res.text()}`);
			}
			return res.json();
		};

		// 1. Settings → Post Formats (icon set picker).
		await page.goto(BASE + '/wp-admin/options-general.php?page=pfbt-settings', { waitUntil: 'networkidle' });
		await shoot('admin-settings-post-formats.png');

		// 2. Tools → Post Format Repair. Captured before any content is
		// seeded so the scan shows a clean, mismatch-free site (matching the
		// documented alt text for this screenshot).
		await page.goto(BASE + '/wp-admin/tools.php?page=pfbt-repair-tool', { waitUntil: 'networkidle' });
		await shoot('admin-repair-tool.png');

		// 3. New post → format selection modal.
		await page.goto(BASE + '/wp-admin/post-new.php', { waitUntil: 'domcontentloaded' });
		await page.waitForSelector('.components-modal__frame', { timeout: 30000 });
		await page.waitForTimeout(2000);
		await shoot('editor-format-selection-modal.png');

		// Seed six placeholder images: render simple gradient cards in the
		// browser and screenshot them, then upload each as a real media
		// library attachment so galleries show loaded images, not broken icons.
		const seedPage = await ctx.newPage();
		const swatches = [
			['#1d4ed8', '#60a5fa', 'One'],
			['#047857', '#6ee7b7', 'Two'],
			['#b45309', '#fcd34d', 'Three'],
			['#9d174d', '#f9a8d4', 'Four'],
			['#4c1d95', '#c4b5fd', 'Five'],
			['#0e7490', '#67e8f9', 'Six'],
		];
		const mediaIds = [];
		const mediaUrls = [];
		for (let i = 0; i < swatches.length; i++) {
			const [from, to, label] = swatches[i];
			await seedPage.setContent(
				`<div style="width:1200px;height:800px;display:flex;align-items:center;justify-content:center;` +
					`background:linear-gradient(135deg,${from},${to});margin:0;">` +
					`<span style="font:700 96px/1 -apple-system,'Segoe UI',sans-serif;color:rgba(255,255,255,0.85);">${label}</span></div>`
			);
			const buffer = await seedPage.screenshot({
				clip: { x: 0, y: 0, width: 1200, height: 800 },
				type: 'jpeg',
				quality: 85,
			});
			const media = await rest('media', {
				headers: {
					'Content-Disposition': `attachment; filename="pfbt-gallery-${i + 1}.jpg"`,
					'Content-Type': 'image/jpeg',
				},
				data: buffer,
			});
			mediaIds.push(media.id);
			mediaUrls.push(media.source_url);
			console.log(`uploaded placeholder image ${media.id}`);
		}
		await seedPage.close();

		// Seed a gallery-format draft using the locked gallery pattern shape.
		const galleryImages = mediaIds
			.map(
				(id, i) =>
					`<!-- wp:image {"id":${id},"sizeSlug":"large","linkDestination":"none"} -->` +
					`<figure class="wp-block-image size-large"><img src="${mediaUrls[i]}" alt="" class="wp-image-${id}"/></figure>` +
					`<!-- /wp:image -->`
			)
			.join('');
		const galleryPost = await rest('posts', {
			data: {
				title: 'Six frames from the workshop',
				status: 'draft',
				format: 'gallery',
				content:
					`<!-- wp:gallery {"linkTo":"none","className":"is-style-justified-rows","lock":{"move":true,"remove":true}} -->` +
					`<figure class="wp-block-gallery has-nested-images columns-default is-cropped is-style-justified-rows">${galleryImages}</figure>` +
					`<!-- /wp:gallery -->` +
					`<!-- wp:paragraph --><p>Highlights from this month's letterpress workshop.</p><!-- /wp:paragraph -->`,
			},
		});
		console.log(`created gallery draft ${galleryPost.id}`);

		// Seed a published quote post for the frontend Format Badge shot.
		const quotePost = await rest('posts', {
			data: {
				title: 'On doing the work',
				status: 'publish',
				format: 'quote',
				content:
					`<!-- wp:pullquote {"lock":{"move":true,"remove":true}} -->` +
					`<figure class="wp-block-pullquote"><blockquote><p>We are what we repeatedly do. Excellence, then, is not an act, but a habit.</p>` +
					`<cite>Will Durant</cite></blockquote></figure>` +
					`<!-- /wp:pullquote -->`,
			},
		});
		console.log(`created published quote post ${quotePost.id}`);

		// 4. Tools → Post Format Templates (opt-in checkbox + template list).
		await page.goto(BASE + '/wp-admin/tools.php?page=pfbt-block-templates', { waitUntil: 'networkidle' });
		await shoot('admin-block-templates.png');

		// 5. Gallery-format draft in the editor (locked pattern, responsive grid).
		await page.goto(BASE + `/wp-admin/post.php?post=${galleryPost.id}&action=edit`, {
			waitUntil: 'domcontentloaded',
		});
		const canvas = page.frameLocator('iframe[name="editor-canvas"]');
		await canvas.locator('figure.wp-block-gallery img').nth(5).waitFor({ timeout: 60000 });
		// Let every gallery image finish painting before the shot.
		await page.waitForFunction(() => {
			const frame = document.querySelector('iframe[name="editor-canvas"]');
			const imgs = frame?.contentDocument?.querySelectorAll('figure.wp-block-gallery img');
			return imgs && imgs.length >= 6 && [...imgs].every((img) => img.complete && img.naturalWidth > 0);
		}, { timeout: 60000 });
		await page.waitForTimeout(2000);
		await shoot('editor-gallery-format.png');

		// 6. Format control in the editor sidebar on the gallery draft — the
		// mid-edit format switcher (core's Format control in Summary).
		await page.evaluate(() => {
			window.wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/document');
		});
		// WP 7.x renders the Format row as a popover button whose accessible
		// name is "Change format: <value>", not the old .editor-post-format.
		const formatButtonName = /^Change format:/;
		const formatButton = page
			.locator('.editor-sidebar, .interface-complementary-area')
			.getByRole('button', { name: formatButtonName })
			.first();
		await formatButton.waitFor({ timeout: 30000 });
		await formatButton.scrollIntoViewIfNeeded();
		await formatButton.click();
		await page.waitForTimeout(1500);
		await shoot('editor-format-switcher.png');

		// 7. Apply-once auto-detection: a quote-block draft saved with no
		// explicit format gets Quote on first save (detection keys on the
		// first block; core/quote maps to the quote format). Fail loudly if
		// detection did not run — the screenshot must not fake the behavior.
		const autodetectPost = await rest('posts', {
			data: {
				title: 'Notes on habit',
				status: 'draft',
				content:
					`<!-- wp:quote --><blockquote class="wp-block-quote">` +
					`<!-- wp:paragraph --><p>The chains of habit are too weak to be felt until they are too strong to be broken.</p><!-- /wp:paragraph -->` +
					`<cite>Samuel Johnson</cite></blockquote><!-- /wp:quote -->`,
			},
		});
		if (autodetectPost.format !== 'quote') {
			throw new Error(
				`Auto-detection did not apply the quote format (got "${autodetectPost.format}"). ` +
					'The editor-autodetect-applied.png shot would misrepresent behavior — aborting.'
			);
		}
		console.log(`autodetect applied "${autodetectPost.format}" to draft ${autodetectPost.id}`);
		await page.goto(BASE + `/wp-admin/post.php?post=${autodetectPost.id}&action=edit`, {
			waitUntil: 'domcontentloaded',
		});
		await canvas.locator('blockquote.wp-block-quote').waitFor({ timeout: 60000 });
		await page.evaluate(() => {
			window.wp.data.dispatch('core/edit-post').openGeneralSidebar('edit-post/document');
		});
		const autodetectFormatButton = page
			.locator('.editor-sidebar, .interface-complementary-area')
			.getByRole('button', { name: formatButtonName })
			.first();
		await autodetectFormatButton.waitFor({ timeout: 30000 });
		await autodetectFormatButton.scrollIntoViewIfNeeded();
		await page.waitForTimeout(1500);
		await shoot('editor-autodetect-applied.png');

		// 8. Published quote post on the frontend (Format Badge before the title).
		await page.goto(BASE + `/?p=${quotePost.id}`, { waitUntil: 'networkidle' });
		await page.waitForSelector('.pfbt-format-badge', { timeout: 30000 });
		await shoot('frontend-format-badge.png');

		await browser.close();
		console.log(`Done. Screenshots are in ${path.relative(REPO_ROOT, OUT_DIR)}/`);
	} finally {
		if (playground) {
			shuttingDown = true;
			playground.kill('SIGTERM');
		}
	}
})().catch((e) => {
	console.error(e.message || e);
	process.exit(1);
});
