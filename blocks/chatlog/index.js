/**
 * Chat Log Block Registration
 *
 * Registers the Chat Log block with WordPress Gutenberg. This file is the
 * entry point for the block editor JavaScript.
 *
 * The block uses:
 * - Dynamic rendering (server-side with PHP render callback)
 * - Block API v3
 * - React for the editor interface
 * - SCSS for styling
 *
 * @since 1.0.0
 * @package ChatLog
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './style.scss';
import Edit from './edit';
import metadata from './block.json';

/**
 * Register the Chat Log block
 *
 * Registers the block type with WordPress using metadata from block.json.
 * The block uses server-side rendering (dynamic block) so the save function
 * returns null. All rendering logic is in the PHP render callback
 * (chatlog_render_callback).
 *
 * @since 1.0.0
 *
 * @see chatlog_render_callback() PHP render callback function
 * @see Edit React component for block editor
 *
 * @example
 * // Block is automatically registered when this file loads
 * // Block name from metadata: 'chatlog/conversation'
 */
registerBlockType( metadata.name, {
	/**
	 * Edit component
	 *
	 * React component for the block editor interface.
	 *
	 * @type {Function}
	 */
	edit: Edit,

	/**
	 * Save callback
	 *
	 * Returns null because this is a dynamic block rendered server-side.
	 * WordPress will call the PHP render callback instead.
	 *
	 * @return {null} Always null for dynamic blocks
	 */
	save: () => null,
} );
