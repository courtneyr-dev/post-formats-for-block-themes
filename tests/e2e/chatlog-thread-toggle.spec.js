// @ts-check
/**
 * E2E test for the Chat Log thread toggle script module.
 *
 * @package
 */

const { test, expect } = require( '@playwright/test' );

test.use( { baseURL: process.env.WP_BASE_URL || 'http://localhost:8888' } );

const TRANSCRIPT = [
	'Alice  10:00 AM',
	'Hello there',
	'Bob  10:01 AM (in thread)',
	'Reply one',
	'Carol  10:02 AM (in thread)',
	'Reply two',
	'Alice  10:03 AM',
	'Back in main',
].join( '\n' );

test.describe( 'Chat Log thread toggle', () => {
	test.describe.configure( { mode: 'serial' } );

	/** @type {import('@playwright/test').APIRequestContext} */
	let apiContext;
	let postId = 0;
	let postUrl = '';

	test.beforeAll( async ( { playwright } ) => {
		apiContext = await playwright.request.newContext( {
			baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
			storageState: { cookies: [], origins: [] },
			extraHTTPHeaders: {
				Authorization: `Basic ${ Buffer.from(
					`admin:${ process.env.WP_APP_PASSWORD || 'password' }`
				).toString( 'base64' ) }`,
			},
		} );

		const attributes = JSON.stringify( { rawTranscript: TRANSCRIPT } );
		const response = await apiContext.post( '/wp-json/wp/v2/posts', {
			data: {
				title: 'E2E Chat Log thread toggle',
				content: `<!-- wp:chatlog/conversation ${ attributes } /-->`,
				status: 'publish',
				format: 'chat',
			},
		} );
		expect( response.ok() ).toBeTruthy();

		const post = await response.json();
		postId = post.id;
		postUrl = post.link;
	} );

	test.afterAll( async () => {
		if ( postId ) {
			await apiContext.delete(
				`/wp-json/wp/v2/posts/${ postId }?force=true`
			);
		}
		await apiContext.dispose();
	} );

	test( 'loads the module and toggles thread visibility', async ( {
		page,
	} ) => {
		/** @type {Error[]} */
		const pageErrors = [];
		page.on( 'pageerror', ( error ) => pageErrors.push( error ) );

		await page.goto( postUrl );

		const moduleScripts = page.locator( 'script[type="module"]' );
		const chatlogModuleCount = await moduleScripts.evaluateAll(
			( scripts ) =>
				scripts.filter( ( script ) => {
					const identity = `${ script.id } ${
						script.getAttribute( 'src' ) || ''
					}`;
					return (
						identity.includes( 'chatlog' ) &&
						identity.includes( 'view-interactivity' )
					);
				} ).length
		);
		expect( chatlogModuleCount ).toBeGreaterThan( 0 );
		await expect(
			page.locator( 'script[src*="/blocks/chatlog/build/view.js"]' )
		).toHaveCount( 0 );

		const toggle = page.locator( '.chatlog-thread-toggle' );
		const thread = page.locator( '.chatlog-thread' );

		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( thread ).toBeVisible();

		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'false' );
		await expect( thread ).toBeHidden();

		await toggle.click();
		await expect( toggle ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( thread ).toBeVisible();
		expect( pageErrors ).toEqual( [] );
	} );
} );
