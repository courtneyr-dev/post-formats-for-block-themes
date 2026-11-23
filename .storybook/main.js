import { fileURLToPath } from 'node:url';
import { transformWithOxc } from 'vite';

// wp-scripts lets the editor and Chat Log sources keep JSX in .js files.
// Vite infers the parser from the extension, so parse those files as JSX first.
const jsxInJs = {
	name: 'pfbt-jsx-in-js',
	enforce: 'pre',
	transform( code, id ) {
		if ( ! /\.js$/.test( id ) || id.includes( '/node_modules/' ) ) {
			return null;
		}
		return transformWithOxc( code, id, {
			lang: 'jsx',
			jsx: { runtime: 'automatic' },
		} );
	},
};

// The devDependencies resolve to mixed @wordpress majors: block-editor 20 nests
// its own data, components, element and hooks. WordPress externalizes these in
// the plugin build, so the editor gets one copy of each. Storybook bundles them,
// so resolve every @wordpress package from block-editor's tree to keep one copy.
const BLOCK_EDITOR = fileURLToPath(
	import.meta.resolve( '@wordpress/block-editor/package.json' )
);
const oneWordPressCopy = {
	name: 'pfbt-one-wordpress-copy',
	enforce: 'pre',
	resolveId( id ) {
		if ( ! id.startsWith( '@wordpress/' ) ) {
			return null;
		}
		return this.resolve( id, BLOCK_EDITOR, { skipSelf: true } );
	},
};

/** @type {import('@storybook/react-vite').StorybookConfig} */
export default {
	framework: '@storybook/react-vite',
	stories: [ '../stories/**/*.stories.js' ],
	addons: [ '@storybook/addon-a11y' ],
	core: { disableTelemetry: true },
	viteFinal: ( config ) => ( {
		...config,
		plugins: [ oneWordPressCopy, jsxInJs, ...( config.plugins ?? [] ) ],
		// `storybook dev` pre-bundles dependencies without the plugins above.
		optimizeDeps: {
			...config.optimizeDeps,
			include: [
				...( config.optimizeDeps?.include ?? [] ),
				'react',
				'react-dom',
				'react/jsx-runtime',
			],
			rolldownOptions: {
				...config.optimizeDeps?.rolldownOptions,
				moduleTypes: { '.js': 'jsx' },
				plugins: [
					oneWordPressCopy,
					...( config.optimizeDeps?.rolldownOptions?.plugins ?? [] ),
				],
			},
		},
	} ),
};
