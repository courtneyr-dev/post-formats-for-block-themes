import {
	createRegistry,
	createReduxStore,
	RegistryProvider,
	useRegistry,
} from '@wordpress/data';
import {
	BlockEditorProvider,
	BlockInspector,
	BlockList,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { useMemo, useState } from '@wordpress/element';

// Mirrors PFBT_Format_Registry::get_all_formats() in includes/class-format-registry.php.
const FORMATS = {
	standard: {
		name: 'Standard',
		description:
			'Default post format with full title and content. Best for traditional blog posts.',
		icon: 'admin-post',
	},
	aside: {
		name: 'Aside',
		description:
			'Short note or update without a title. Displays in a bubble style with minimized metadata.',
		icon: 'format-aside',
	},
	audio: {
		name: 'Audio',
		description:
			'Audio file or embed. Starts with an audio block for podcasts or music.',
		icon: 'format-audio',
	},
	chat: {
		name: 'Chat',
		description:
			'Chat transcript or conversation log. Paste text from Slack, Discord, Teams, Telegram, WhatsApp or Signal, or a WebVTT, SubRip or plain-text transcript.',
		icon: 'format-chat',
	},
	gallery: {
		name: 'Gallery',
		description:
			'Image gallery post. Starts with a gallery block for multiple images.',
		icon: 'format-gallery',
	},
	image: {
		name: 'Image',
		description:
			'Single image post. Starts with an image block for photo-centric content.',
		icon: 'format-image',
	},
	link: {
		name: 'Link',
		description:
			'Link to external content. Uses Bookmark Card plugin if available, otherwise starts with link paragraph.',
		icon: 'admin-links',
	},
	quote: {
		name: 'Quote',
		description:
			'Quotation or citation. Starts with a quote block for highlighted text.',
		icon: 'format-quote',
	},
	status: {
		name: 'Status',
		description:
			'Short status update without title. Limited to 280 characters, Twitter-style.',
		icon: 'format-status',
	},
	video: {
		name: 'Video',
		description:
			'Video file or embed. Starts with a video block for multimedia content.',
		icon: 'format-video',
	},
};

// Mirrors the format_kind_map in includes/post-kinds/class-pfbt-post-kinds-integration.php.
const FORMAT_TO_KIND = {
	standard: 'article',
	aside: 'note',
	status: 'note',
	quote: 'note',
	link: 'bookmark',
	image: 'photo',
	gallery: 'photo',
	video: 'watch',
	audio: 'listen',
	chat: 'note',
};

export const pfbtData = ( { postKindsActive = false } = {} ) => ( {
	formats: FORMATS,
	patterns: {},
	hasBookmarkCard: false,
	hasChatLog: true,
	currentFormat: 'standard',
	postKinds: {
		active: postKindsActive,
		formatToKind: postKindsActive ? FORMAT_TO_KIND : {},
		kindToFormat: {},
	},
} );

const editorStore = ( post ) =>
	createReduxStore( 'core/editor', {
		reducer: ( state = post, action ) =>
			action.type === 'EDIT_POST' ? { ...state, ...action.edits } : state,
		selectors: {
			getCurrentPost: ( state ) => state,
			getCurrentPostType: ( state ) => state.type,
			getEditedPostAttribute: ( state, key ) => state[ key ],
		},
		actions: {
			editPost: ( edits ) => ( { type: 'EDIT_POST', edits } ),
		},
	} );

const editPostStore = ( activeFeatures ) =>
	createReduxStore( 'core/edit-post', {
		reducer: ( state = activeFeatures ) => state,
		selectors: {
			isFeatureActive: ( state, feature ) => state.includes( feature ),
		},
	} );

const NO_FEATURES = [];

export function EditorContext( {
	post,
	activeFeatures = NO_FEATURES,
	children,
} ) {
	const parent = useRegistry();
	const registry = useMemo( () => {
		const next = createRegistry( {}, parent );
		next.register( blockEditorStore );
		next.register( editorStore( post ) );
		next.register( editPostStore( activeFeatures ) );
		return next;
	}, [ parent, post, activeFeatures ] );
	return <RegistryProvider value={ registry }>{ children }</RegistryProvider>;
}

// Keyed by the first block's clientId so new story args remount the editor
// instead of keeping the blocks from the previous render.
export function BlockEditorFrame( { initialBlocks } ) {
	return (
		<BlockEditorFrameInner
			key={ initialBlocks[ 0 ].clientId }
			initialBlocks={ initialBlocks }
		/>
	);
}

function BlockEditorFrameInner( { initialBlocks } ) {
	const [ blocks, setBlocks ] = useState( initialBlocks );
	const selection = useMemo( () => {
		const clientId = initialBlocks[ 0 ].clientId;
		return {
			selectionStart: { clientId },
			selectionEnd: { clientId },
		};
	}, [ initialBlocks ] );
	return (
		<BlockEditorProvider
			value={ blocks }
			selection={ selection }
			useSubRegistry={ false }
			onInput={ setBlocks }
			onChange={ setBlocks }
		>
			<div
				style={ { display: 'flex', gap: '24px', alignItems: 'start' } }
			>
				<div className="editor-styles-wrapper" style={ { flex: 1 } }>
					<BlockList />
				</div>
				<div
					style={ {
						width: '280px',
						borderInlineStart: '1px solid #ddd',
					} }
				>
					<BlockInspector />
				</div>
			</div>
		</BlockEditorProvider>
	);
}
