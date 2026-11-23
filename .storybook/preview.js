import '@wordpress/components/build-style/style.css';
import '@wordpress/block-editor/build-style/style.css';
import '@wordpress/block-editor/build-style/content.css';
import { WCAG_TAGS } from './a11y-rules.js';

/** @type {import('@storybook/react-vite').Preview} */
export default {
	parameters: {
		layout: 'padded',
		a11y: {
			test: 'error',
			options: { runOnly: { type: 'tag', values: WCAG_TAGS } },
		},
	},
};
