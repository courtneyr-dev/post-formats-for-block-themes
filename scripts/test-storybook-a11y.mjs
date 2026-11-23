/* eslint-disable no-console */
import { createServer } from 'node:http';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize, resolve as resolvePath } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';
import { AxeBuilder } from '@axe-core/playwright';
import { WCAG_TAGS } from '../.storybook/a11y-rules.js';

// Defaults to `npm run build-storybook` output. The Docs workflow passes the
// copy it publishes, docs/dist/storybook.
const ROOT = process.argv[ 2 ]
	? resolvePath( process.argv[ 2 ] )
	: fileURLToPath( new URL( '../storybook-static', import.meta.url ) );
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

// Violations in Storybook 10.6.6's own manager page, not in this repo's code.
// Each entry must still occur, so a Storybook fix forces its removal here.
const KNOWN_IN_STORYBOOK_UI = [
	{
		rule: 'label-content-name-mismatch',
		// With one sidebar root axe names the button by its class alone. With
		// more roots it prefixes the root id and adds the attributes.
		target: /^(#[a-z0-9-]+ > )?\.css-[a-z0-9]+(\[aria-label="Collapse"\]\[data-action="collapse-root"\])?$/,
		html: /^<button aria-label="Collapse" data-action="collapse-root"/,
		why: 'the sidebar section button shows its name and is labeled "Collapse"',
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
		`${ ROOT }/index.json is missing. Run \`npm run build-storybook\` first.`
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

	// The manager page, with every story listed in the sidebar. The preview
	// iframe is excluded: the loop above checks each story on its own.
	const manager = await context.newPage();
	await manager.goto( `${ url }/` );
	await manager.waitForSelector( '[data-nodetype="component"]' );
	for ( const button of await manager
		.locator( '[data-action="expand-all"]' )
		.all() ) {
		await button.click();
	}
	const listed = await manager.locator( '[data-nodetype="story"]' ).count();
	if ( listed !== stories.length ) {
		failures += 1;
		console.log(
			`FAIL  manager sidebar lists ${ listed } stories, index.json has ${ stories.length }`
		);
	}
	const { violations: managerViolations } = await new AxeBuilder( {
		page: manager,
	} )
		.exclude( 'iframe' )
		.withTags( WCAG_TAGS )
		.analyze();
	await manager.close();
	const seenUi = new Set();
	let freshUi = 0;
	for ( const v of managerViolations ) {
		for ( const node of v.nodes ) {
			const target = node.target.join( ' ' );
			const match = KNOWN_IN_STORYBOOK_UI.find(
				( k ) =>
					k.rule === v.id &&
					k.target.test( target ) &&
					k.html.test( node.html )
			);
			if ( match ) {
				seenUi.add( match );
			} else {
				freshUi += 1;
				console.log(
					`FAIL  manager ${ v.id }: ${ target }  ${ v.help }`
				);
			}
		}
	}
	failures += freshUi;
	for ( const k of KNOWN_IN_STORYBOOK_UI ) {
		if ( seenUi.has( k ) ) {
			console.log( `known manager ${ k.rule } (${ k.why })` );
		} else {
			failures += 1;
			console.log(
				`FAIL  known manager violation no longer occurs, remove it from KNOWN_IN_STORYBOOK_UI: ${ k.rule }`
			);
		}
	}
	console.log(
		`${ freshUi ? 'FAIL' : 'pass' }  manager page, ${ listed } stories listed, ${ freshUi } new violations`
	);

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
