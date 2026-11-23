// Link check over the built site. Internal links always; external links
// too unless SKIP_EXTERNAL=1 (CI skips externals to stay deterministic).
import { LinkChecker } from 'linkinator';
import { startPreview } from './preview-server.mjs';

const { base, stop } = await startPreview(4326);
const skipExternal = process.env.SKIP_EXTERNAL === '1';

try {
	// No docs page links to the Storybook, so seed its manager page and the
	// preview iframe the manager loads.
	const checker = new LinkChecker();
	const result = await checker.check({
		path: [base, `${base}storybook/`, `${base}storybook/iframe.html`],
		recurse: true,
		...(skipExternal && {
			linksToSkip: async (link) => !link.startsWith('http://localhost'),
		}),
	});
	const broken = result.links.filter((l) => l.state === 'BROKEN');
	const storybook = result.links.filter((l) => l.url.startsWith(`${base}storybook/`));
	console.log(`Checked ${result.links.length} links (${storybook.length} under storybook/); ${broken.length} broken.`);
	for (const l of broken) console.error(`BROKEN ${l.status} ${l.url} (on ${l.parent})`);
	stop();
	process.exit(broken.length ? 1 : 0);
} catch (e) {
	console.error(e);
	stop();
	process.exit(1);
}
