import { PluginArea } from '@wordpress/plugins';
import { screen } from 'storybook/test';
import '../src/editor/index.js';
import { EditorContext, pfbtData } from './editor-context.js';

const NEW_POST = {
	id: 1,
	type: 'post',
	status: 'auto-draft',
	format: 'standard',
};

export default {
	title: 'Editor/Format selection modal',
	parameters: {
		layout: 'fullscreen',
		docs: {
			description: {
				component:
					'Source: `src/editor/index.js` (`FormatSelectionModal`, rendered through `registerPlugin` and `PluginArea`). ' +
					'The modal opens 500 ms after a new post loads, once the Welcome Guide is closed. ' +
					"Choosing a card sets the post format and inserts that format's pattern. The stories pass no pattern markup, so choosing a card here only closes the modal. " +
					'When Post Kinds is active, each card names the kind it sets. ' +
					'Format names and descriptions come from `includes/class-format-registry.php`. The modal renames Standard to "Standard (Single Template)". ' +
					'Dashicons are not loaded here, so the card icons render empty.',
			},
		},
	},
	args: { postKindsActive: false },
	beforeEach: ( { args } ) => {
		window.pfbtData = pfbtData( args );
		return () => delete window.pfbtData;
	},
	render: () => (
		<EditorContext post={ NEW_POST }>
			<PluginArea />
		</EditorContext>
	),
	play: async () => {
		await screen.findByRole( 'dialog', { name: 'Choose Post Format' } );
	},
};

export const NewPost = {};

export const WithPostKinds = { args: { postKindsActive: true } };
