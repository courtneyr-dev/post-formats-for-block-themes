/**
 * Post Formats Power-Up - Editor JavaScript
 *
 * Main entry point for editor functionality including:
 * - Format selection modal on new post
 * - Format switcher sidebar panel
 * - Status paragraph 280-character validation
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 *
 * Accessibility Implementation:
 * - Modal uses @wordpress/components Modal (fully accessible)
 * - Keyboard navigation support (Tab, Escape, Enter)
 * - Screen reader announcements via wp.a11y.speak()
 * - Focus management on modal open/close
 * - ARIA labels on all interactive elements
 */

import { registerPlugin } from '@wordpress/plugins';
import { Button, Modal, Card, CardBody } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { addFilter } from '@wordpress/hooks';
import { parse } from '@wordpress/blocks';

/**
 * Helper function to insert pattern for a format
 *
 * @param {string} formatSlug - The format slug
 * @param {Function} insertBlocks - The insertBlocks dispatch function
 */
const insertPatternForFormat = (formatSlug, insertBlocks) => {
	if (!window.pfbtData.patterns || !window.pfbtData.patterns[formatSlug]) {
		return;
	}

	const patternContent = window.pfbtData.patterns[formatSlug];
	const blocks = parse(patternContent);

	if (blocks.length > 0) {
		insertBlocks(blocks, 0, undefined, false);
	}
};

/**
 * Format Selection Modal Component
 *
 * Displays on new post creation to help users choose the appropriate format.
 * Accessible modal with keyboard navigation and screen reader support.
 */
const FormatSelectionModal = () => {
	const [isOpen, setIsOpen] = useState(false);
	const [hasShown, setHasShown] = useState(false);

	const { isNewPost, currentFormat, postType } = useSelect((select) => {
		const editor = select('core/editor');
		const post = editor.getCurrentPost();

		return {
			isNewPost: ! post.id || post.status === 'auto-draft',
			currentFormat: editor.getEditedPostAttribute('format') || 'standard',
			postType: post.type,
		};
	}, []);

	const { editPost } = useDispatch('core/editor');
	const { insertBlocks } = useDispatch('core/block-editor');

	// Show modal on new post (only once)
	useEffect(() => {
		if (isNewPost && postType === 'post' && !hasShown && window.pfbtData) {
			// Small delay to ensure editor is fully loaded
			setTimeout(() => {
				setIsOpen(true);
				setHasShown(true);
			}, 500);
		}
	}, [isNewPost, postType, hasShown]);

	const handleFormatSelect = (formatSlug) => {
		// Set the format AND template together to prevent race conditions
		const templateValue = formatSlug === 'standard' ? '' : `single-format-${formatSlug}`;

		editPost({
			format: formatSlug,
			template: templateValue
		});

		// Insert pattern if not standard
		if (formatSlug !== 'standard' && window.pfbtData.formats[formatSlug]) {
			const format = window.pfbtData.formats[formatSlug];

			// Insert the pattern blocks
			insertPatternForFormat(formatSlug, insertBlocks);

			// Announce to screen readers
			speak(
				sprintf(
					/* translators: %s: Format name */
					__('Selected %s format. Pattern inserted.', 'post-formats-for-block-themes'),
					format.name
				),
				'polite'
			);
		}

		setIsOpen(false);
	};

	if (!isOpen || !window.pfbtData) {
		return null;
	}

	const formats = window.pfbtData.formats;

	// Enhance format display with template information
	const formatsWithTemplateInfo = Object.entries(formats).map(([slug, format]) => {
		if (slug === 'standard') {
			return [slug, {
				...format,
				name: __('Standard (Single Template)', 'post-formats-for-block-themes'),
				description: __('Default post format using the Single template', 'post-formats-for-block-themes')
			}];
		}
		return [slug, format];
	});

	// Sort: Standard first, then alphabetically
	const sortedFormats = formatsWithTemplateInfo.sort((a, b) => {
		if (a[0] === 'standard') return -1;
		if (b[0] === 'standard') return 1;
		return a[1].name.localeCompare(b[1].name);
	});

	return (
		<Modal
			title={__('Choose Post Format', 'post-formats-for-block-themes')}
			onRequestClose={() => setIsOpen(false)}
			className="pfpu-format-modal"
		>
			<div className="pfpu-format-grid">
				{sortedFormats.map(([slug, format]) => (
					<Card key={slug} className="pfpu-format-card">
						<CardBody>
							<Button
								onClick={() => handleFormatSelect(slug)}
								className="pfpu-format-button"
								variant={slug === 'standard' ? 'primary' : 'secondary'}
							>
								<span className={`dashicons dashicons-${format.icon}`} aria-hidden="true"></span>
								<span className="pfpu-format-name">{format.name}</span>
							</Button>
							<p className="pfpu-format-description">{format.description}</p>
						</CardBody>
					</Card>
				))}
			</div>
		</Modal>
	);
};


/**
 * Status Paragraph Validator Notice Component
 *
 * Shows character count as an editor notice for status format posts.
 */
const StatusParagraphValidatorNotice = () => {
	const { createNotice, removeNotice } = useDispatch('core/notices');
	const { currentFormat, blocks } = useSelect((select) => {
		const editor = select('core/editor');
		const blockEditor = select('core/block-editor');

		return {
			currentFormat: editor.getEditedPostAttribute('format') || 'standard',
			blocks: blockEditor.getBlocks(),
		};
	}, []);

	useEffect(() => {
		if (currentFormat !== 'status') {
			removeNotice('pfbt-status-char-count');
			return;
		}

		const statusBlock = blocks.find(block =>
			block.name === 'core/paragraph' &&
			block.attributes.className?.includes('status-paragraph')
		);

		if (!statusBlock) {
			removeNotice('pfbt-status-char-count');
			return;
		}

		const content = statusBlock.attributes.content || '';
		const plainText = content.replace(/<[^>]*>/g, '');
		const charCount = plainText.length;

		if (charCount > 280) {
			createNotice(
				'error',
				sprintf(
					/* translators: %d: Current character count */
					__('%d / 280 characters - Status updates should be 280 characters or less', 'post-formats-for-block-themes'),
					charCount
				),
				{
					id: 'pfbt-status-char-count',
					isDismissible: false,
				}
			);
		} else if (charCount >= 260) {
			createNotice(
				'warning',
				sprintf(
					/* translators: %d: Current character count */
					__('%d / 280 characters - Approaching limit', 'post-formats-for-block-themes'),
					charCount
				),
				{
					id: 'pfbt-status-char-count',
					isDismissible: false,
				}
			);
		} else {
			removeNotice('pfbt-status-char-count');
		}
	}, [currentFormat, blocks, createNotice, removeNotice]);

	return null;
};

/**
 * Register Plugin
 *
 * Registers format selection modal and status validator.
 * Uses WordPress's built-in format selector instead of custom panel.
 */
registerPlugin('post-formats-for-block-themes', {
	render: () => {
		return (
			<>
				<FormatSelectionModal />
				<StatusParagraphValidatorNotice />
			</>
		);
	},
});

/**
 * Add character counter to status paragraphs in block editor
 *
 * Uses block filters to add real-time character counting.
 */
addFilter(
	'editor.BlockEdit',
	'pfpu/status-paragraph-counter',
	(BlockEdit) => {
		return (props) => {
			const { name, attributes, setAttributes } = props;

			// Only apply to paragraphs with status-paragraph class
			if (
				name !== 'core/paragraph' ||
				!attributes.className?.includes('status-paragraph')
			) {
				return <BlockEdit {...props} />;
			}

			const content = attributes.content || '';
			const plainText = content.replace(/<[^>]*>/g, '');
			const charCount = plainText.length;
			const remaining = 280 - charCount;
			const isOver = remaining < 0;

			return (
				<div className="pfpu-status-paragraph-wrapper">
					<BlockEdit {...props} />
					<div
						className={`pfpu-char-counter ${isOver ? 'is-over-limit' : ''} ${remaining <= 20 ? 'is-warning' : ''}`}
						aria-live="polite"
						aria-atomic="true"
					>
						<span>
							{sprintf(
								/* translators: %d: Remaining characters */
								__('%d characters remaining', 'post-formats-for-block-themes'),
								remaining
							)}
						</span>
					</div>
				</div>
			);
		};
	}
);

/**
 * Editor Styles
 *
 * Inline styles for editor components (will be extracted to editor.css in build)
 */
const editorStyles = `
	.pfpu-format-modal {
		max-width: 800px;
	}

	.pfpu-format-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		gap: 1rem;
		margin-top: 1rem;
	}

	.pfpu-format-card {
		text-align: center;
	}

	.pfpu-format-button {
		width: 100%;
		height: auto;
		padding: 1rem;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 0.5rem;
	}

	.pfpu-format-button .dashicons {
		font-size: 2rem;
		width: 2rem;
		height: 2rem;
	}

	.pfpu-format-name {
		font-weight: 600;
	}

	.pfpu-format-description {
		font-size: 0.875rem;
		color: #757575;
		margin-top: 0.5rem;
	}

	.pfpu-format-change-actions {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
		margin-top: 1rem;
	}

	.pfpu-status-validator {
		margin-top: 1rem;
	}

	.pfpu-status-paragraph-wrapper {
		position: relative;
	}

	.pfpu-char-counter {
		position: absolute;
		bottom: -30px;
		right: 0;
		font-size: 0.875rem;
		color: #757575;
	}

	.pfpu-char-counter.is-warning {
		color: #f0b849;
		font-weight: 600;
	}

	.pfpu-char-counter.is-over-limit {
		color: #d63638;
		font-weight: 600;
	}

	/* Template Modal - Visual Separation for Format Templates */

	/* Target template items in the template selection modal */
	/* Format templates have "Format" in their title (e.g., "Gallery Format", "Aside Format") */

	/* Template card styling - targets the button/card elements */
	.edit-post-template__panel .components-panel__body button[aria-label*="Format"],
	.edit-site-template-card button[aria-label*="Format"],
	.block-editor-block-card button[aria-label*="Format"] {
		border-left: 4px solid var(--wp-admin-theme-color, #2271b1) !important;
		padding-left: 12px !important;
		background: rgba(34, 113, 177, 0.05) !important;
		position: relative;
	}

	/* Add emoji badge to format templates */
	.edit-post-template__panel .components-panel__body button[aria-label*="Format"]::before,
	.edit-site-template-card button[aria-label*="Format"]::before,
	.block-editor-block-card button[aria-label*="Format"]::before {
		content: "🎨 ";
		margin-right: 4px;
		font-size: 14px;
	}

	/* Template dropdown/modal items */
	.edit-post-template-dropdown__content button[aria-label*="Format"],
	.components-dropdown-menu__menu button[aria-label*="Format"] {
		border-left: 4px solid var(--wp-admin-theme-color, #2271b1);
		padding-left: 12px;
		background: rgba(34, 113, 177, 0.05);
	}

	/* Add visual indicator to currently selected format template */
	.edit-post-template__panel .components-panel__body .is-selected[aria-label*="Format"],
	button[aria-checked="true"][aria-label*="Format"] {
		background: rgba(34, 113, 177, 0.1) !important;
		font-weight: 600;
	}

	/* Template name in sidebar - show when auto-applied */
	.edit-post-template__panel .components-panel__body .components-base-control__help {
		font-style: italic;
		color: #757575;
	}
`;

// Inject editor styles
if (typeof document !== 'undefined') {
	const styleEl = document.createElement('style');
	styleEl.textContent = editorStyles;
	document.head.appendChild(styleEl);
}
