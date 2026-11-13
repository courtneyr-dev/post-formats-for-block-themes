/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	TextareaControl,
	Notice,
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './editor.scss';

/**
 * Edit component for Chat Log block
 *
 * Main editor interface for the Chat Log block. Provides:
 * - Textarea for pasting chat transcripts
 * - Platform auto-detection with confidence indicator
 * - Inspector controls for all display customizations
 * - Real-time preview notice
 *
 * The component uses React hooks for state management and effects
 * for platform detection. All settings are stored in block attributes
 * and persist across editor sessions.
 *
 * @since 1.0.0
 *
 * @param {Object}   props               Block props provided by WordPress
 * @param {Object}   props.attributes    Current block attributes from block.json
 * @param {Function} props.setAttributes Function to update block attributes
 *
 * @return {JSX.Element} React component with editor UI and inspector controls
 *
 * @example
 * // This component is registered in index.js:
 * registerBlockType( metadata.name, {
 *     edit: Edit,
 *     save: () => null,
 * } );
 */
export default function Edit( { attributes, setAttributes } ) {
	const {
		rawTranscript,
		source,
		displayStyle,
		showAvatars,
		showTimestamps,
		timestampFormat,
		collapseThreads,
		maxVisibleMessages,
		showParticipantList,
		highlightCurrentUser,
		deviceFrame,
	} = attributes;

	const [ detectedPlatform, setDetectedPlatform ] = useState( null );
	const [ confidence, setConfidence ] = useState( 0 );

	const blockProps = useBlockProps( {
		className: `chatlog-editor chatlog--${displayStyle}`,
	} );

	// Simple platform detection for preview
	useEffect( () => {
		if ( ! rawTranscript ) {
			setDetectedPlatform( null );
			return;
		}

		// Basic detection patterns
		// Check for Slack JSON export format (rarely used)
		if ( rawTranscript.includes( '"type":"message"' ) && rawTranscript.includes( '"user":' ) ) {
			setDetectedPlatform( 'Slack' );
			setConfidence( 95 );
		}
		// Check for WhatsApp format: [DD/MM/YYYY, HH:MM:SS] Name: Message
		else if ( /^\[[\d\/]+,\s*[\d:]+\]/.test( rawTranscript ) ) {
			setDetectedPlatform( 'WhatsApp' );
			setConfidence( 90 );
		}
		// Check for VTT format: WEBVTT header
		else if ( /^WEBVTT/m.test( rawTranscript ) || rawTranscript.includes( '<v ' ) ) {
			setDetectedPlatform( 'VTT Captions' );
			setConfidence( 95 );
		}
		// Check for SRT format: numeric ID followed by timecode
		else if ( /^\d+\s*\n\d{2}:\d{2}:\d{2},\d{3}\s*-->/m.test( rawTranscript ) ) {
			setDetectedPlatform( 'SRT Subtitles' );
			setConfidence( 95 );
		}
		// Check for Discord-specific indicators
		else if ( rawTranscript.includes( '#' ) && /\d{1,2}:\d{2}\s*[AP]M/i.test( rawTranscript ) ) {
			setDetectedPlatform( 'Discord' );
			setConfidence( 75 );
		}
		// Check for generic chat format with AM/PM times
		// Note: Slack, Teams, Telegram, Signal all use same format - can't differentiate!
		else if ( /\d{1,2}:\d{2}\s*[AP]M/i.test( rawTranscript ) ) {
			setDetectedPlatform( 'Slack/Teams/Telegram/Signal' );
			setConfidence( 60 );
		}
		// Unknown format
		else {
			setDetectedPlatform( 'Unknown' );
			setConfidence( 30 );
		}
	}, [ rawTranscript ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'post-formats-for-block-themes' ) }>
					<SelectControl
						label={ __( 'Platform', 'post-formats-for-block-themes' ) }
						value={ source }
						options={ [
							{ label: __( 'Auto-detect', 'post-formats-for-block-themes' ), value: 'auto' },
							{ label: 'Slack', value: 'slack' },
							{ label: 'Discord', value: 'discord' },
							{ label: 'Microsoft Teams', value: 'teams' },
							{ label: 'Telegram', value: 'telegram' },
							{ label: 'WhatsApp', value: 'whatsapp' },
							{ label: 'Signal', value: 'signal' },
						{ label: __( '— Transcript Formats —', 'post-formats-for-block-themes' ), value: '', disabled: true },
						{ label: 'WebVTT (.vtt)', value: 'vtt' },
						{ label: 'SubRip (.srt)', value: 'srt' },
						{ label: __( 'Plain text transcript', 'post-formats-for-block-themes' ), value: 'transcript' },
						] }
						onChange={ ( value ) => setAttributes( { source: value } ) }
						help={ __( 'Select the chat platform or transcript format. Auto-detect works for most formats.', 'post-formats-for-block-themes' ) }
					/>


					<SelectControl
						label={ __( 'Display Style', 'post-formats-for-block-themes' ) }
						value={ displayStyle }
						options={ [
							{ label: __( 'Bubbles', 'post-formats-for-block-themes' ), value: 'bubbles' },
							{ label: __( 'IRC', 'post-formats-for-block-themes' ), value: 'irc' },
							{ label: __( 'Transcript', 'post-formats-for-block-themes' ), value: 'transcript' },
							{ label: __( 'Timeline', 'post-formats-for-block-themes' ), value: 'timeline' },
						] }
						onChange={ ( value ) => setAttributes( { displayStyle: value } ) }
						help={ __( 'Choose how messages are displayed visually.', 'post-formats-for-block-themes' ) }
					/>

					<ToggleControl
						label={ __( 'Show Avatars', 'post-formats-for-block-themes' ) }
						checked={ showAvatars }
						onChange={ ( value ) => setAttributes( { showAvatars: value } ) }
						help={ __( 'Display participant avatars with Gravatar integration and initials fallback.', 'post-formats-for-block-themes' ) }
					/>

					<ToggleControl
						label={ __( 'Show Timestamps', 'post-formats-for-block-themes' ) }
						checked={ showTimestamps }
						onChange={ ( value ) => setAttributes( { showTimestamps: value } ) }
					/>

					{ showTimestamps && (
						<SelectControl
							label={ __( 'Timestamp Format', 'post-formats-for-block-themes' ) }
							value={ timestampFormat }
							options={ [
								{ label: __( 'Time Only (10:23 AM)', 'post-formats-for-block-themes' ), value: 'time-only' },
								{ label: __( 'Absolute (Jan 15, 2025 10:23 AM)', 'post-formats-for-block-themes' ), value: 'absolute' },
								{ label: __( 'Relative (2 hours ago)', 'post-formats-for-block-themes' ), value: 'relative' },
							] }
							onChange={ ( value ) => setAttributes( { timestampFormat: value } ) }
						/>
					) }

					<SelectControl
						label={ __( 'Device Frame', 'post-formats-for-block-themes' ) }
						value={ deviceFrame }
						options={ [
							{ label: __( 'None', 'post-formats-for-block-themes' ), value: 'none' },
							{ label: __( 'Phone', 'post-formats-for-block-themes' ), value: 'phone' },
							{ label: __( 'Desktop', 'post-formats-for-block-themes' ), value: 'desktop' },
							{ label: __( 'Slack App', 'post-formats-for-block-themes' ), value: 'slack-app' },
							{ label: __( 'Discord App', 'post-formats-for-block-themes' ), value: 'discord-app' },
						] }
						onChange={ ( value ) => setAttributes( { deviceFrame: value } ) }
						help={ __( 'Display the chat in a device mockup frame.', 'post-formats-for-block-themes' ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Threading', 'post-formats-for-block-themes' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Collapse Threads', 'post-formats-for-block-themes' ) }
						checked={ collapseThreads }
						onChange={ ( value ) => setAttributes( { collapseThreads: value } ) }
						help={ __( 'Collapse threaded replies by default. Users can expand them.', 'post-formats-for-block-themes' ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Participants', 'post-formats-for-block-themes' ) } initialOpen={ false }>
					<ToggleControl
						label={ __( 'Show Participant List', 'post-formats-for-block-themes' ) }
						checked={ showParticipantList }
						onChange={ ( value ) => setAttributes( { showParticipantList: value } ) }
						help={ __( 'Display a list of all participants at the top of the transcript.', 'post-formats-for-block-themes' ) }
					/>

					<TextControl
						label={ __( 'Highlight User', 'post-formats-for-block-themes' ) }
						value={ highlightCurrentUser }
						onChange={ ( value ) => setAttributes( { highlightCurrentUser: value } ) }
						help={ __( 'Enter a username to highlight (useful for "you" styling). Leave empty for no highlighting.', 'post-formats-for-block-themes' ) }
						placeholder={ __( 'e.g., Courtney Robertson', 'post-formats-for-block-themes' ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Advanced', 'post-formats-for-block-themes' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Maximum Visible Messages', 'post-formats-for-block-themes' ) }
						type="number"
						value={ maxVisibleMessages }
						onChange={ ( value ) => setAttributes( { maxVisibleMessages: parseInt( value ) || 0 } ) }
						help={ __( 'Limit displayed messages for long transcripts. 0 = show all.', 'post-formats-for-block-themes' ) }
						min={ 0 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ detectedPlatform && source === 'auto' && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'Detected:', 'post-formats-for-block-themes' ) } { detectedPlatform } ({ confidence }% { __( 'confidence', 'post-formats-for-block-themes' ) })
					</Notice>
				) }

				<TextareaControl
					label={ __( 'Paste Your Chat Transcript', 'post-formats-for-block-themes' ) }
					value={ rawTranscript }
					onChange={ ( value ) => setAttributes( { rawTranscript: value } ) }
					placeholder={ __(
						'Paste your chat transcript here.\n\nWorks with text copied from:\n• Chat apps: Slack, Discord, Teams, WhatsApp, Telegram, Signal\n• Transcripts: WebVTT, SubRip, plain text\n\nAuto-detect identifies the format.',
						'post-formats-for-block-themes'
					) }
					rows={ 15 }
					className="chatlog-transcript-input"
					help={ __(
						'Copy your chat messages and paste them here. Chat Log will automatically detect the platform and format.',
						'post-formats-for-block-themes'
					) }
				/>

				{ rawTranscript && (
					<div className="chatlog-preview-notice">
						<Notice status="success" isDismissible={ false }>
							{ __(
								'Preview will appear on the frontend. The transcript will be parsed and displayed according to your settings.',
								'post-formats-for-block-themes'
							) }
						</Notice>
					</div>
				) }
			</div>
		</>
	);
}
