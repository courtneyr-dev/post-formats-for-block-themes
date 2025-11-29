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
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { SelectControl, Button, Modal, Card, CardBody, Notice } from '@wordpress/components';
import { useSelect, useDispatch, subscribe } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { addFilter } from '@wordpress/hooks';
import { parse, createBlock } from '@wordpress/blocks';

/**
 * Helper function to insert pattern for a format
 *
 * @param {string} formatSlug - The format slug
 * @param {Function} insertBlocks - The insertBlocks dispatch function
 */
const insertPatternForFormat = (formatSlug, insertBlocks) => {
	if (!window.pfpuData.patterns || !window.pfpuData.patterns[formatSlug]) {
		return;
	}

	const patternContent = window.pfpuData.patterns[formatSlug];
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
		if (isNewPost && postType === 'post' && !hasShown && window.pfpuData) {
			// Small delay to ensure editor is fully loaded
			setTimeout(() => {
				setIsOpen(true);
				setHasShown(true);
			}, 500);
		}
	}, [isNewPost, postType, hasShown]);

	const handleFormatSelect = (formatSlug) => {
		// Set the post format
		editPost({ format: formatSlug });

		// Insert pattern if not standard
		if (formatSlug !== 'standard' && window.pfpuData.formats[formatSlug]) {
			const format = window.pfpuData.formats[formatSlug];

			// Insert the pattern blocks
			insertPatternForFormat(formatSlug, insertBlocks);

			// Announce to screen readers
			speak(
				sprintf(
					/* translators: %s: Format name */
					__('Selected %s format. Pattern inserted.', 'post-formats-power-up'),
					format.name
				),
				'polite'
			);
		}

		setIsOpen(false);
	};

	if (!isOpen || !window.pfpuData) {
		return null;
	}

	const formats = window.pfpuData.formats;
	// Sort: Standard first, then alphabetically
	const sortedFormats = Object.entries(formats).sort((a, b) => {
		if (a[0] === 'standard') return -1;
		if (b[0] === 'standard') return 1;
		return a[1].name.localeCompare(b[1].name);
	});

	return (
		<Modal
			title={__('Choose Post Format', 'post-formats-power-up')}
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
 * Format Switcher Panel Component
 *
 * Sidebar panel for changing format mid-edit with confirmation dialog.
 */
const FormatSwitcherPanel = () => {
	const [showConfirm, setShowConfirm] = useState(false);
	const [pendingFormat, setPendingFormat] = useState('');

	const { currentFormat, hasBlocks } = useSelect((select) => {
		const editor = select('core/editor');
		const blockEditor = select('core/block-editor');

		return {
			currentFormat: editor.getEditedPostAttribute('format') || 'standard',
			hasBlocks: blockEditor.getBlocks().length > 0,
		};
	}, []);

	const { editPost } = useDispatch('core/editor');

	const handleFormatChange = (newFormat) => {
		if (hasBlocks && newFormat !== currentFormat) {
			// Show confirmation dialog
			setPendingFormat(newFormat);
			setShowConfirm(true);
		} else {
			// No content, just change format
			applyFormat(newFormat);
		}
	};

	const applyFormat = (formatSlug, mode = 'replace') => {
		editPost({ format: formatSlug });

		// Mark as manually set to prevent auto-detection override
		wp.apiFetch({
			path: `/wp/v2/posts/${wp.data.select('core/editor').getCurrentPostId()}`,
			method: 'POST',
			data: {
				meta: {
					_pfpu_format_manual: true,
				},
			},
		});

		speak(
			sprintf(
				/* translators: %s: Format name */
				__('Format changed to %s', 'post-formats-power-up'),
				window.pfpuData.formats[formatSlug]?.name || formatSlug
			),
			'polite'
		);

		setShowConfirm(false);
	};

	if (!window.pfpuData) {
		return null;
	}

	const formats = window.pfpuData.formats;

	return (
		<>
			<SelectControl
				label={__('Post Format', 'post-formats-power-up')}
				value={currentFormat}
				options={Object.entries(formats).map(([slug, format]) => ({
					label: format.name,
					value: slug,
				}))}
				onChange={handleFormatChange}
				help={__('Choose the format that best matches your content.', 'post-formats-power-up')}
			/>

			{showConfirm && (
				<Modal
					title={__('Change Post Format?', 'post-formats-power-up')}
					onRequestClose={() => setShowConfirm(false)}
				>
					<p>
						{__('Your post already has content. How would you like to proceed?', 'post-formats-power-up')}
					</p>
					<div className="pfpu-format-change-actions">
						<Button
							variant="primary"
							onClick={() => applyFormat(pendingFormat, 'replace')}
						>
							{__('Replace content with new pattern', 'post-formats-power-up')}
						</Button>
						<Button
							variant="secondary"
							onClick={() => applyFormat(pendingFormat, 'keep')}
						>
							{__('Keep content, just change format', 'post-formats-power-up')}
						</Button>
						<Button
							variant="tertiary"
							onClick={() => setShowConfirm(false)}
						>
							{__('Cancel', 'post-formats-power-up')}
						</Button>
					</div>
				</Modal>
			)}
		</>
	);
};

/**
 * Status Paragraph Validator Component
 *
 * Adds character counter and validation to status format paragraphs.
 */
const StatusParagraphValidator = () => {
	const { currentFormat, blocks } = useSelect((select) => {
		const editor = select('core/editor');
		const blockEditor = select('core/block-editor');

		return {
			currentFormat: editor.getEditedPostAttribute('format') || 'standard',
			blocks: blockEditor.getBlocks(),
		};
	}, []);

	if (currentFormat !== 'status') {
		return null;
	}

	// Find status paragraph block
	const statusBlock = blocks.find(block =>
		block.name === 'core/paragraph' &&
		block.attributes.className?.includes('status-paragraph')
	);

	if (!statusBlock) {
		return null;
	}

	const content = statusBlock.attributes.content || '';
	const plainText = content.replace(/<[^>]*>/g, ''); // Strip HTML
	const charCount = plainText.length;
	const isWarning = charCount >= 260;
	const isError = charCount > 280;

	return (
		<div className="pfpu-status-validator">
			<Notice
				status={isError ? 'error' : (isWarning ? 'warning' : 'info')}
				isDismissible={false}
			>
				<p>
					<strong>
						{sprintf(
							/* translators: 1: Current character count, 2: Maximum characters */
							__('%1$d / %2$d characters', 'post-formats-power-up'),
							charCount,
							280
						)}
					</strong>
				</p>
				{isError && (
					<p>
						{__('Status updates should be 280 characters or less for best display.', 'post-formats-power-up')}
					</p>
				)}
				{isWarning && !isError && (
					<p>
						{__('Approaching character limit. Consider shortening your status.', 'post-formats-power-up')}
					</p>
				)}
			</Notice>
		</div>
	);
};

/**
 * Register Plugin
 *
 * Registers all components as a WordPress plugin.
 */
registerPlugin('post-formats-power-up', {
	render: () => {
		return (
			<>
				<FormatSelectionModal />
				<PluginDocumentSettingPanel
					name="post-format-switcher"
					title={__('Post Format', 'post-formats-power-up')}
					className="pfpu-format-switcher-panel"
				>
					<FormatSwitcherPanel />
					<StatusParagraphValidator />
				</PluginDocumentSettingPanel>
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
								__('%d characters remaining', 'post-formats-power-up'),
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
`;

// Inject editor styles
if (typeof document !== 'undefined') {
	const styleEl = document.createElement('style');
	styleEl.textContent = editorStyles;
	document.head.appendChild(styleEl);
}
