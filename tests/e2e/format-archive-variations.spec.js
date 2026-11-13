/**
 * E2E tests for post format archive Query Loop variations.
 */

const { test, expect } = require( '@playwright/test' );
const { loginToWordPress } = require( '../accessibility/utils' );

test.describe( 'Format archive Query Loop variations', () => {
	test.beforeEach( async ( { page } ) => {
		await loginToWordPress( page );
	} );

	test( 'inserts the Quote preset in the page editor', async ( { page } ) => {
		await page.goto( '/wp-admin/post-new.php?post_type=page' );
		await page.waitForSelector( '.edit-post-layout', { timeout: 10000 } );

		await page.evaluate( () => {
			const { dispatch } = window.wp.data;
			dispatch( 'core/preferences' ).set(
				'core/edit-post',
				'welcomeGuide',
				false
			);
			dispatch( 'core/preferences' ).set( 'core', 'welcomeGuide', false );
		} );

		await page
			.getByRole( 'button', { name: 'Block Inserter', exact: true } )
			.click();
		await page.getByPlaceholder( 'Search' ).fill( 'Format archive: Quote' );
		await page
			.getByRole( 'option', { name: 'Format archive: Quote' } )
			.click();

		const state = await page.evaluate( () => ( {
			blocks: window.wp.data
				.select( 'core/block-editor' )
				.getBlocks()
				.map( ( block ) => ( {
					name: block.name,
					namespace: block.attributes.namespace,
					format: block.attributes.query?.format,
				} ) ),
			variations: window.wp.blocks
				.getBlockVariations( 'core/query' )
				.map( ( variation ) => variation.name ),
		} ) );

		expect( state.blocks ).toContainEqual( {
			name: 'core/query',
			namespace: 'post-formats/format-archive-quote',
			format: [ 'quote' ],
		} );
		expect( state.variations ).toEqual(
			expect.arrayContaining( [
				'pfbt-format-archive-quote',
				'pfbt-format-archive-link',
				'pfbt-format-archive-status',
			] )
		);
	} );
} );
