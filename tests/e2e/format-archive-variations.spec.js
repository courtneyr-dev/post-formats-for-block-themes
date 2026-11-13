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
			// New pages open the "Choose a pattern" modal when the theme has
			// page patterns; it would cover the Block Inserter.
			dispatch( 'core/preferences' ).set(
				'core',
				'enableChoosePatternModal',
				false
			);
		} );
		await page.reload();
		await page.waitForSelector( '.edit-post-layout', { timeout: 10000 } );

		await page
			.getByRole( 'button', { name: 'Block Inserter', exact: true } )
			.click();
		await page.getByPlaceholder( 'Search' ).fill( 'Format archive: Quote' );
		await page
			.getByRole( 'option', { name: 'Format archive: Quote' } )
			.click();

		const state = await page.evaluate( () => {
			const editor = window.wp.data.select( 'core/block-editor' );
			const query = editor
				.getBlocks()
				.find( ( block ) => block.name === 'core/query' );
			const findDate = ( blocks ) => {
				for ( const block of blocks ) {
					if ( block.name === 'core/post-date' ) {
						return block;
					}
					const inner = findDate( block.innerBlocks );
					if ( inner ) {
						return inner;
					}
				}
				return null;
			};
			const date = findDate( query.innerBlocks );
			const activeFor = ( format ) =>
				window.wp.data
					.select( 'core/blocks' )
					.getActiveBlockVariation( 'core/query', {
						...query.attributes,
						query: { ...query.attributes.query, format },
					} )?.name;
			return {
				dateBinding: date.attributes.metadata?.bindings?.datetime,
				dateFixed: date.attributes.datetime,
				activeQuote: activeFor( [ 'quote' ] ),
				activeAside: activeFor( [ 'aside' ] ),
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
			};
		} );

		expect( state.dateBinding ).toEqual( {
			source: 'core/post-data',
			args: { field: 'date' },
		} );
		expect( state.dateFixed ).toBeUndefined();
		expect( state.activeQuote ).toBe( 'pfbt-format-archive-quote' );
		expect( state.activeAside ).not.toBe( 'pfbt-format-archive-quote' );

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
