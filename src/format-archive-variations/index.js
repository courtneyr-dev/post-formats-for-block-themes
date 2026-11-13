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

const getPostTemplate = ( format ) => {
	if ( 'link' === format ) {
		return [
			'core/post-template',
			{},
			[
				[ 'core/post-title', { isLink: true, level: 2 } ],
				[ 'core/post-excerpt' ],
				[ 'core/post-date', { isLink: true } ],
			],
		];
	}

	return [
		'core/post-template',
		{},
		[ [ 'core/post-content' ], [ 'core/post-date', { isLink: true } ] ],
	];
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
			isActive: [ 'namespace' ],
			scope: [ 'inserter' ],
			innerBlocks: getInnerBlocks( format, copy.noResults ),
		} );
	} );
} );
