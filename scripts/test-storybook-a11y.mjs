/* eslint-disable no-console */
import { createServer } from 'node:http';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { WCAG_TAGS } from '../.storybook/a11y-rules.js';

const ROOT = fileURLToPath( new URL( '../storybook-static', import.meta.url ) );
const TYPES = {
	'.html': 'text/html',
	'.js': 'text/javascript',
	'.css': 'text/css',
	'.json': 'application/json',
	'.svg': 'image/svg+xml',
	'.woff2': 'font/woff2',
};

// Pre-existing violations in shipped components. Each entry must still occur,
// so a fix forces its removal here.
const COUNTER = 'editor-status-character-counter';
const KNOWN = [
	// .pfpu-char-counter.is-warning in src/editor/index.js: #f0b849 on #fff, 1.8:1.
	{
		story: `${ COUNTER }--near-limit`,
		rule: 'color-contrast',
		target: /^\.pfpu-char-counter > span$/,
	},
	// .pfpu-char-counter.is-over-limit in src/editor/index.js: #d63638 on #fcf0f1, 4.24:1.
	{
		story: `${ COUNTER }--over-limit`,
		rule: 'color-contrast',
		target: /^\.pfpu-char-counter > span$/,
	},
];

const known = ( story, rule, target ) =>
	KNOWN.find(
		( k ) => k.story === story && k.rule === rule && k.target.test( target )
	);

function serve() {
	const server = createServer( async ( req, res ) => {
		const path = normalize(
			decodeURIComponent(
				new URL( req.url, 'http://localhost' ).pathname
			)
		);
		const file = join(
			ROOT,
			path.endsWith( '/' ) ? `${ path }index.html` : path
		);
		try {
			const body = await readFile( file );
			res.writeHead( 200, {
				'content-type':
					TYPES[ extname( file ) ] ?? 'application/octet-stream',
			} );
			res.end( body );
		} catch {
			res.writeHead( 404 ).end();
		}
	} );
	return new Promise( ( resolve ) =>
		server.listen( 0, '127.0.0.1', () =>
			resolve( {
				server,
				url: `http://127.0.0.1:${ server.address().port }`,
			} )
		)
	);
}

if ( ! existsSync( join( ROOT, 'index.json' ) ) ) {
	throw new Error(
		'storybook-static/index.json is missing. Run `npm run build-storybook` first.'
	);
}

const index = JSON.parse(
	await readFile( join( ROOT, 'index.json' ), 'utf8' )
);
const stories = Object.values( index.entries ).filter(
	( e ) => e.type === 'story'
);
const { server, url } = await serve();
const browser = await chromium.launch( { channel: 'chrome' } );
let failures = 0;
const seenKnown = new Set();

try {
	const context = await browser.newContext();
	for ( const story of stories ) {
		const page = await context.newPage();
		const pageErrors = [];
		page.on( 'pageerror', ( error ) => pageErrors.push( error.message ) );
		// A throwing play function only logs console.error. The server has no
		// favicon, and Chrome requests one on the first page load.
		page.on( 'console', ( message ) => {
			if (
				message.type() === 'error' &&
				! message.location().url.endsWith( '/favicon.ico' )
			) {
				pageErrors.push( message.text() );
			}
		} );
		await page.goto(
			`${ url }/iframe.html?id=${ story.id }&viewMode=story`
		);
		const phase = await page
			.waitForFunction(
				() => {
					const current =
						window.__STORYBOOK_PREVIEW__?.currentRender?.phase;
					return (
						[ 'finished', 'errored', 'aborted' ].includes(
							current
						) && current
					);
				},
				null,
				{ timeout: 30000 }
			)
			.then( ( handle ) => handle.jsonValue() );
		const threw = await page.$( 'body.sb-show-errordisplay' );
		if ( phase !== 'finished' || threw || pageErrors.length ) {
			failures += 1;
			console.log(
				`FAIL  ${ story.id }  story errored (phase "${ phase }")`
			);
			for ( const message of pageErrors ) {
				console.log( `        ${ message }` );
			}
			await page.close();
			continue;
		}

		const { violations } = await new AxeBuilder( { page } )
			.withTags( WCAG_TAGS )
			.analyze();
		await page.close();

		const fresh = [];
		for ( const v of violations ) {
			for ( const node of v.nodes ) {
				const target = node.target.join( ' ' );
				const match = known( story.id, v.id, target );
				if ( match ) {
					seenKnown.add( match );
				} else {
					fresh.push( `${ v.id }: ${ target }  ${ v.help }` );
				}
			}
		}
		failures += fresh.length;
		console.log( `${ fresh.length ? 'FAIL' : 'pass' }  ${ story.id }` );
		for ( const line of fresh ) {
			console.log( `        ${ line }` );
		}
	}

	for ( const k of KNOWN ) {
		if ( seenKnown.has( k ) ) {
			console.log(
				`known ${ k.story } ${ k.rule } ${ k.target.source }`
			);
		} else {
			failures += 1;
			console.log(
				`FAIL  known violation no longer occurs, remove it from KNOWN: ${ k.story } ${ k.rule }`
			);
		}
	}
	console.log(
		`\n${ stories.length } stories, ${ failures } failures, ${
			seenKnown.size
		} known (axe tags: ${ WCAG_TAGS.join( ', ' ) })`
	);
} finally {
	await browser.close();
	server.close();
}

if ( failures > 0 ) {
	process.exitCode = 1;
}
