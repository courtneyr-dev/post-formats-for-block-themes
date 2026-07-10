// Shared helper: build output must exist (run `npm run build` first).
// Starts `astro preview` and resolves with the base URL + a stop function.
import { spawn } from 'node:child_process';

export async function startPreview(port = 4325) {
	// detached => own process group, so stop() can kill astro's whole tree
	// (killing the npx wrapper alone leaves the server running on Linux CI).
	const child = spawn('npx', ['astro', 'preview', '--port', String(port)], {
		stdio: ['ignore', 'pipe', 'pipe'],
		detached: true,
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
			if (res.ok) {
				const stop = () => {
					try {
						process.kill(-child.pid, 'SIGTERM');
					} catch {
						child.kill('SIGTERM');
					}
				};
				return { base, meta, stop };
			}
		} catch {
			// Not up yet.
		}
		await new Promise((r) => setTimeout(r, 500));
	}
	try {
		process.kill(-child.pid, 'SIGTERM');
	} catch {
		child.kill('SIGTERM');
	}
	throw new Error(`astro preview did not become ready.\n${output}`);
}
