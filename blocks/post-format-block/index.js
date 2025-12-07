/**
 * Post Format Block - Block variation registration
 *
 * Registers a block variation of core/post-terms to display post formats.
 *
 * Forked from: Post Format Block by Aaron Jorbin
 * Original Plugin URI: https://wordpress.org/plugins/post-format-block/
 * License: GPL-2.0-or-later
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Block Registration Function
import { registerBlockVariation } from '@wordpress/blocks';

// Internationalization
import { __ } from '@wordpress/i18n';

// Icon
import { postCategories as icon } from '@wordpress/icons';

/**
 * Post Format block variation configuration
 *
 * Creates a variation of the core/post-terms block specifically for
 * displaying post formats in block-based themes.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/post-terms/variations.js
 */
const variation = {
	name: 'post_format',
	title: __( 'Post Format', 'post-formats-for-block-themes' ),
	description: __( "Display a post's format", 'post-formats-for-block-themes' ),
	icon,
	isDefault: false,
	attributes: { term: 'post_format' },
	isActive: ( blockAttributes ) => blockAttributes.term === 'post_format',
	scope: [ 'inserter', 'transform' ],
};

registerBlockVariation( 'core/post-terms', variation );
