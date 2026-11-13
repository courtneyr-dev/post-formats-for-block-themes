const path = require( 'path' );
const defaultConfigs = require( '@wordpress/scripts/config/webpack.config' );

const [ scriptConfig, moduleConfig ] = defaultConfigs;

scriptConfig.entry = {
	index: path.resolve( __dirname, 'blocks/chatlog/index.js' ),
};
const copyPlugin = scriptConfig.plugins.find(
	( plugin ) => plugin.constructor.name === 'CopyPlugin'
);
copyPlugin.patterns = [
	{
		from: path.resolve( __dirname, 'blocks/chatlog/block.json' ),
		to: 'block.json',
	},
];

moduleConfig.entry = {
	'view-interactivity': path.resolve(
		__dirname,
		'blocks/chatlog/view-interactivity.js'
	),
};

module.exports = [ scriptConfig, moduleConfig ];
