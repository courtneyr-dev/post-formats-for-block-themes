// Automated accessibility gate: axe-core over key built pages.
// Fails on any serious or critical violation. This supplements — never
// replaces — the manual keyboard/zoom/screen-reader checks in the standard.
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { startPreview } from './preview-server.mjs';

const PAGES = process.env.A11Y_PAGES
	? process.env.A11Y_PAGES.split(',')
	: ['', 'installation/', 'settings/', 'common-tasks/', 'faq/', 'this-page-does-not-exist/'];

const { base, stop } = await startPreview(4327);
const browser = await chromium.launch();
let failures = 0;

try {
	const context = await browser.newContext();
	const page = await context.newPage();
	for (const path of PAGES) {
		await page.goto(base + path, { waitUntil: 'networkidle' });
		const results = await new AxeBuilder({ page }).analyze();
		const bad = results.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
		console.log(`${path || '(home)'}: ${bad.length} serious/critical violations`);
		for (const v of bad) {
			failures++;
			console.error(`  [${v.impact}] ${v.id}: ${v.help} — ${v.nodes.length} node(s)`);
			for (const n of v.nodes.slice(0, 3)) console.error(`    ${n.target.join(' ')}`);
		}
	}
} finally {
	await browser.close();
	stop();
}
process.exit(failures ? 1 : 0);
