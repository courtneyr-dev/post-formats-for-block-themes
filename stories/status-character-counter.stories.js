import { createBlock, registerBlockType } from '@wordpress/blocks';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import '../src/editor/index.js';
import '../styles/status-counter-editor.css';
import { BlockEditorFrame, EditorContext } from './editor-context.js';

function StatusParagraphEdit( { attributes, setAttributes } ) {
	return (
		<div { ...useBlockProps() }>
			<RichText
				tagName="p"
				aria-label="Status"
				value={ attributes.content }
				onChange={ ( content ) => setAttributes( { content } ) }
			/>
		</div>
	);
}

registerBlockType( 'core/paragraph', {
	apiVersion: 3,
	title: 'Paragraph',
	category: 'text',
	attributes: { content: { type: 'string', default: '' } },
	edit: StatusParagraphEdit,
	save: () => null,
} );

const statusText = ( length ) =>
	'Status posts stay short, like a note between longer posts. '
		.repeat( 6 )
		.slice( 0, length );

const STATUS_POST = { id: 1, type: 'post', status: 'draft', format: 'status' };

export default {
	title: 'Editor/Status character counter',
	parameters: {
		docs: {
			description: {
				component:
					'Source: `src/editor/index.js` (`withStatusCharacterCounter` on the `editor.BlockEdit` filter). ' +
					'The counter wraps any `core/paragraph` block with the `status-paragraph` class, which `patterns/status.php` inserts. ' +
					'It counts plain text against 280 characters, turns to a warning at 20 or fewer, and to an error past the limit. ' +
					'The paragraph here is a minimal RichText stand-in, because `@wordpress/block-library` is not a dependency. Type in it to watch the count change.',
			},
		},
	},
	render: ( { content } ) => (
		<EditorContext post={ STATUS_POST }>
			<BlockEditorFrame
				initialBlocks={ [
					createBlock( 'core/paragraph', {
						className: 'status-paragraph',
						content,
					} ),
				] }
			/>
		</EditorContext>
	),
};

export const UnderLimit = { args: { content: statusText( 120 ) } };

export const NearLimit = { args: { content: statusText( 265 ) } };

export const OverLimit = { args: { content: statusText( 300 ) } };
