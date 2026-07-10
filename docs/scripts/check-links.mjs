// Link check over the built site. Internal links always; external links
// too unless SKIP_EXTERNAL=1 (CI skips externals to stay deterministic).
import { LinkChecker } from 'linkinator';
import { startPreview } from './preview-server.mjs';

const { base, stop } = await startPreview(4326);
const skipExternal = process.env.SKIP_EXTERNAL === '1';

try {
	const checker = new LinkChecker();
	const result = await checker.check({
		path: base,
		recurse: true,
		...(skipExternal && {
			linksToSkip: async (link) => !link.startsWith('http://localhost'),
		}),
	});
	const broken = result.links.filter((l) => l.state === 'BROKEN');
	console.log(`Checked ${result.links.length} links; ${broken.length} broken.`);
	for (const l of broken) console.error(`BROKEN ${l.status} ${l.url} (on ${l.parent})`);
	process.exitCode = broken.length ? 1 : 0;
} finally {
	stop();
}
