import { registerBlockVariation } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';

import variations from './variations.json';

const getVariationCopy = ( format ) => {
	switch ( format ) {
		case 'quote':
			return {
				title: __(
					'Format archive: Quote',
					'post-formats-for-block-themes'
				),
				description: __(
					'Lists posts in the Quote format.',
					'post-formats-for-block-themes'
				),
				keyword: __( 'quote', 'post-formats-for-block-themes' ),
				noResults: __(
					'No quote posts yet.',
					'post-formats-for-block-themes'
				),
			};
		case 'link':
			return {
				title: __(
					'Format archive: Link',
					'post-formats-for-block-themes'
				),
				description: __(
					'Lists posts in the Link format.',
					'post-formats-for-block-themes'
				),
				keyword: __( 'link', 'post-formats-for-block-themes' ),
				noResults: __(
					'No link posts yet.',
					'post-formats-for-block-themes'
				),
			};
		case 'status':
			return {
				title: __(
					'Format archive: Status',
					'post-formats-for-block-themes'
				),
				description: __(
					'Lists posts in the Status format.',
					'post-formats-for-block-themes'
				),
				keyword: __( 'status', 'post-formats-for-block-themes' ),
				noResults: __(
					'No status posts yet.',
					'post-formats-for-block-themes'
				),
			};
	}
};

// Bind the date to each post. A bare core/post-date gets `datetime` set to
// the insertion time by the editor (WordPress 6.9+), which would print the
// same date on every post. Core's own Query Loop variations bind it too.
const POST_DATE = [
	'core/post-date',
	{
		isLink: true,
		metadata: {
			bindings: {
				datetime: {
					source: 'core/post-data',
					args: { field: 'date' },
				},
			},
		},
	},
];

const getPostTemplate = ( format ) => {
	if ( 'link' === format ) {
		return [
			'core/post-template',
			{},
			[
				[ 'core/post-title', { isLink: true, level: 2 } ],
				[ 'core/post-excerpt' ],
				POST_DATE,
			],
		];
	}

	return [ 'core/post-template', {}, [ [ 'core/post-content' ], POST_DATE ] ];
};

const getInnerBlocks = ( format, noResults ) => [
	getPostTemplate( format ),
	[
		'core/query-pagination',
		{},
		[
			[ 'core/query-pagination-previous' ],
			[ 'core/query-pagination-numbers' ],
			[ 'core/query-pagination-next' ],
		],
	],
	[
		'core/query-no-results',
		{},
		[ [ 'core/paragraph', { content: noResults } ] ],
	],
];

// Active only while the block still lists exactly this variation's format.
// An isActive path like 'query.format' can't work: core compares arrays by
// identity, so [ 'quote' ] never equals [ 'quote' ].
const isActive = ( blockAttributes, variationAttributes ) => {
	const format = blockAttributes.query?.format;

	return (
		blockAttributes.namespace === variationAttributes.namespace &&
		Array.isArray( format ) &&
		1 === format.length &&
		variationAttributes.query.format[ 0 ] === format[ 0 ]
	);
};

domReady( () => {
	variations.forEach( ( { format, namespace, icon, query } ) => {
		const copy = getVariationCopy( format );

		registerBlockVariation( 'core/query', {
			name: `pfbt-format-archive-${ format }`,
			title: copy.title,
			description: copy.description,
			keywords: [
				__( 'format', 'post-formats-for-block-themes' ),
				__( 'archive', 'post-formats-for-block-themes' ),
				copy.keyword,
			],
			icon,
			attributes: { namespace, query },
			isActive,
			scope: [ 'inserter' ],
			innerBlocks: getInnerBlocks( format, copy.noResults ),
		} );
	} );
} );
