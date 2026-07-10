// Shared helper: build output must exist (run `npm run build` first).
// Starts `astro preview` and resolves with the base URL + a stop function.
import { spawn } from 'node:child_process';

export async function startPreview(port = 4325) {
	const child = spawn('npx', ['astro', 'preview', '--port', String(port)], {
		stdio: ['ignore', 'pipe', 'pipe'],
	});
	let output = '';
	child.stdout.on('data', (d) => (output += d));
	child.stderr.on('data', (d) => (output += d));

	const { default: meta } = await import('../src/site-meta.mjs');
	const base = `http://localhost:${port}${meta.base}/`;

	const deadline = Date.now() + 60_000;
	while (Date.now() < deadline) {
		try {
			const res = await fetch(base);
			if (res.ok) return { base, meta, stop: () => child.kill('SIGTERM') };
		} catch {
			// Not up yet.
		}
		await new Promise((r) => setTimeout(r, 500));
	}
	child.kill('SIGTERM');
	throw new Error(`astro preview did not become ready.\n${output}`);
}
