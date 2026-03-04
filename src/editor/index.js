/**
 * Post Formats for Block Themes - Editor JavaScript
 *
 * Main entry point for editor functionality including:
 * - Format selection modal on new post
 * - Format change watcher for pattern insertion
 * - Status paragraph 280-character validation
 *
 * @package PostFormatsBlockThemes
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
import { useEffect, useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { addFilter } from '@wordpress/hooks';
import { parse } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Track which formats have had patterns inserted to prevent duplicates
 */
const insertedPatterns = {};

/**
 * Helper function to insert pattern for a format
 *
 * @param {string} formatSlug - The format slug
 * @param {Function} insertBlocks - The insertBlocks dispatch function
 * @param {Function} resetBlocks - The resetBlocks dispatch function (optional, for replacing all content)
 * @param {boolean} replace - Whether to replace all content or insert at beginning
 */
const insertPatternForFormat = (formatSlug, insertBlocks, resetBlocks = null, replace = false) => {
	// Prevent duplicate insertions
	if (insertedPatterns[formatSlug]) {
		return false;
	}

	if (!window.pfbtData?.patterns?.[formatSlug]) {
		return false;
	}

	const patternContent = window.pfbtData.patterns[formatSlug];
	const blocks = parse(patternContent);

	if (blocks.length > 0) {
		if (replace && resetBlocks) {
			resetBlocks(blocks);
		} else {
			insertBlocks(blocks, 0, undefined, false);
		}
		// Mark as inserted
		insertedPatterns[formatSlug] = true;
		return true;
	}
	return false;
};

/**
 * Format Change Watcher Component
 *
 * Watches for format changes via the sidebar dropdown and inserts
 * the appropriate pattern when format changes.
 * Uses shared insertedPatterns tracking to prevent duplicates with modal.
 */
const FormatChangeWatcher = () => {
	const previousFormat = useRef(null);

	const { currentFormat, postType, blocks } = useSelect((select) => {
		const editor = select('core/editor');
		const blockEditor = select('core/block-editor');

		return {
			currentFormat: editor.getEditedPostAttribute('format') || 'standard',
			postType: editor.getCurrentPostType(),
			blocks: blockEditor.getBlocks(),
		};
	}, []);

	const { insertBlocks, resetBlocks } = useDispatch('core/block-editor');

	useEffect(() => {
		// Only for posts
		if (postType !== 'post') return;

		// Skip if format hasn't actually changed
		if (previousFormat.current === currentFormat) return;

		// Skip if this is the initial load (previousFormat is null)
		const isInitialLoad = previousFormat.current === null;
		previousFormat.current = currentFormat;

		if (isInitialLoad) return;

		// Skip standard format - no pattern to insert
		if (currentFormat === 'standard') return;

		// Check if content is empty or just has empty paragraph
		const isContentEmpty = blocks.length === 0 ||
			(blocks.length === 1 && blocks[0].name === 'core/paragraph' && !blocks[0].attributes.content);

		// Insert pattern (insertPatternForFormat handles duplicate prevention)
		if (window.pfbtData?.patterns?.[currentFormat]) {
			const inserted = insertPatternForFormat(
				currentFormat,
				insertBlocks,
				resetBlocks,
				isContentEmpty
			);

			if (inserted) {
				// Announce to screen readers
				const formatName = window.pfbtData.formats?.[currentFormat]?.name || currentFormat;
				speak(
					sprintf(
						__('Switched to %s format. Pattern inserted.', 'post-formats-for-block-themes'),
						formatName
					),
					'polite'
				);
			}
		}
	}, [currentFormat, postType, blocks, insertBlocks, resetBlocks]);

	return null;
};

/**
 * Format Selection Modal Component
 *
 * Displays on new post creation to help users choose the appropriate format.
 * Accessible modal with keyboard navigation and screen reader support.
 */

/**
 * Get suggested Post Kind for a format
 *
 * @param {string} formatSlug - Format slug
 * @return {string|null} Suggested kind or null
 */
const getSuggestedKind = (formatSlug) => {
	const postKindsData = window.pfbtData?.postKinds;
	if (!postKindsData?.active || !postKindsData?.formatToKind) {
		return null;
	}
	return postKindsData.formatToKind[formatSlug] || null;
};

/**
 * Get human-readable kind name
 *
 * @param {string} kindSlug - Kind slug
 * @return {string} Human-readable name
 */
const getKindDisplayName = (kindSlug) => {
	const kindNames = {
		article: __('Article', 'post-formats-for-block-themes'),
		note: __('Note', 'post-formats-for-block-themes'),
		photo: __('Photo', 'post-formats-for-block-themes'),
		video: __('Video', 'post-formats-for-block-themes'),
		bookmark: __('Bookmark', 'post-formats-for-block-themes'),
		quotation: __('Quotation', 'post-formats-for-block-themes'),
		listen: __('Listen', 'post-formats-for-block-themes'),
		watch: __('Watch', 'post-formats-for-block-themes'),
		reply: __('Reply', 'post-formats-for-block-themes'),
		like: __('Like', 'post-formats-for-block-themes'),
		repost: __('Repost', 'post-formats-for-block-themes'),
		checkin: __('Checkin', 'post-formats-for-block-themes'),
	};
	return kindNames[kindSlug] || kindSlug;
};

const FormatSelectionModal = () => {
	const [isOpen, setIsOpen] = useState(false);
	const [hasShown, setHasShown] = useState(false);

	const { isNewPost, currentFormat, postType, postId } = useSelect((select) => {
		const editor = select('core/editor');
		const post = editor.getCurrentPost();

		return {
			isNewPost: ! post.id || post.status === 'auto-draft',
			currentFormat: editor.getEditedPostAttribute('format') || 'standard',
			postType: post.type,
			postId: post.id,
		};
	}, []);

	const { editPost } = useDispatch('core/editor');
	const { insertBlocks, resetBlocks } = useDispatch('core/block-editor');

	// Check if Post Kinds is active
	const postKindsActive = window.pfbtData?.postKinds?.active || false;

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
		// Get suggested Post Kind if Post Kinds plugin is active
		const suggestedKind = getSuggestedKind(formatSlug);

		// Set the post format
		// Format templates are applied on the front-end only via PHP template hierarchy
		// This keeps the editor showing just post content, not the full template
		editPost({ format: formatSlug });

		// Set Post Kind via Reactions for IndieWeb custom event
		if (postKindsActive && suggestedKind) {
			window.dispatchEvent(new CustomEvent('reactions-indieweb-set-kind', {
				detail: { kind: suggestedKind }
			}));
		}

		// Insert pattern if not standard
		if (formatSlug !== 'standard' && window.pfbtData?.formats?.[formatSlug]) {
			const format = window.pfbtData.formats[formatSlug];

			// Insert the pattern blocks (replace since it's a new post)
			insertPatternForFormat(formatSlug, insertBlocks, resetBlocks, true);

			// Announce to screen readers
			const announcement = suggestedKind
				? sprintf(
					/* translators: 1: Format name, 2: Kind name */
					__('Selected %1$s format with %2$s kind. Pattern inserted.', 'post-formats-for-block-themes'),
					format.name,
					getKindDisplayName(suggestedKind)
				)
				: sprintf(
					/* translators: %s: Format name */
					__('Selected %s format. Pattern inserted.', 'post-formats-for-block-themes'),
					format.name
				);

			speak(announcement, 'polite');
		}

		setIsOpen(false);
	};

	if (!isOpen || !window.pfbtData) {
		return null;
	}

	const formats = window.pfbtData.formats;

	// Enhance format display with template information and Post Kind suggestion
	const formatsWithTemplateInfo = Object.entries(formats).map(([slug, format]) => {
		const suggestedKind = getSuggestedKind(slug);

		if (slug === 'standard') {
			return [slug, {
				...format,
				name: __('Standard (Single Template)', 'post-formats-for-block-themes'),
				description: __('Default post format using the Single template', 'post-formats-for-block-themes'),
				suggestedKind,
			}];
		}
		return [slug, { ...format, suggestedKind }];
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
							{postKindsActive && format.suggestedKind && (
								<p className="pfpu-format-kind-hint">
									{sprintf(
										/* translators: %s: Kind name */
										__('→ Sets Post Kind: %s', 'post-formats-for-block-themes'),
										getKindDisplayName(format.suggestedKind)
									)}
								</p>
							)}
						</CardBody>
					</Card>
				))}
			</div>
		</Modal>
	);
};


/**
 * Register Plugin
 *
 * Registers format selection modal and format change watcher.
 * Uses WordPress's built-in format selector instead of custom panel.
 * Status format validation handled by inline counter (see addFilter below).
 */
registerPlugin('post-formats-for-block-themes', {
	render: () => {
		return (
			<>
				<FormatSelectionModal />
				<FormatChangeWatcher />
			</>
		);
	},
});

/**
 * Add character counter to paragraphs in Status format posts
 *
 * Uses a higher-order component to add real-time character counting
 * to any paragraph block when the post format is "status".
 * This approach works regardless of whether the block has a special class.
 */
const withStatusCharacterCounter = createHigherOrderComponent((BlockEdit) => {
	return (props) => {
		const { name, attributes } = props;

		// Get the current post format
		const postFormat = useSelect((select) => {
			const editor = select('core/editor');
			return editor ? editor.getEditedPostAttribute('format') : null;
		}, []);

		// Only apply to paragraph blocks with the status-paragraph class
		// This ensures we only show the counter on the designated status paragraph,
		// not on every paragraph in a status post
		const hasStatusClass = attributes.className?.includes('status-paragraph');

		if (name !== 'core/paragraph' || !hasStatusClass) {
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
}, 'withStatusCharacterCounter');

addFilter(
	'editor.BlockEdit',
	'pfpu/status-paragraph-counter',
	withStatusCharacterCounter
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

	.pfpu-format-kind-hint {
		font-size: 0.75rem;
		color: #2271b1;
		margin-top: 0.25rem;
		font-style: italic;
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
		margin-bottom: 30px;
	}

	.pfpu-char-counter {
		position: absolute;
		bottom: -25px;
		right: 0;
		font-size: 0.875rem;
		color: #757575;
		background: #fff;
		padding: 2px 8px;
		border-radius: 3px;
		box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	}

	.pfpu-char-counter.is-warning {
		color: #f0b849;
		font-weight: 600;
	}

	.pfpu-char-counter.is-over-limit {
		color: #d63638;
		font-weight: 600;
		background: #fcf0f1;
	}
`;

// Inject editor styles
if (typeof document !== 'undefined') {
	const styleEl = document.createElement('style');
	styleEl.textContent = editorStyles;
	document.head.appendChild(styleEl);
}
