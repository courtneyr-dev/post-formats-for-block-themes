import { unstable__bootstrapServerSideBlockDefinitions } from '@wordpress/blocks'; // eslint-disable-line camelcase
import metadata from '../blocks/chatlog/block.json';

// WordPress registers block.json in PHP and hands the definition to the editor
// before blocks/chatlog/index.js calls registerBlockType( metadata.name ).
unstable__bootstrapServerSideBlockDefinitions( {
	[ metadata.name ]: metadata,
} );
