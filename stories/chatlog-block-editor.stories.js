import { createBlock } from '@wordpress/blocks';
import './chatlog-server-definition.js';
import '../blocks/chatlog/index.js';
import { BlockEditorFrame, EditorContext } from './editor-context.js';

const POST = { id: 1, type: 'post', status: 'draft', format: 'chat' };

// Samples from .wordpress-org/CHAT-LOG-FORMAT-EXAMPLES.md.
const TRANSCRIPTS = {
	slackStyle:
		"sarah  9:30 AM\nHey team! Ready to kick off the new website project?\n\nmike  9:31 AM\nAbsolutely! I've been reviewing the requirements doc.",
	whatsApp:
		"[01/16/2024, 10:23:00] Mom: Good morning! Has everyone thought about the lake house trip next weekend?\n\n[01/16/2024, 10:25:00] Dad: I'm in! Should we leave Friday evening or Saturday morning?",
	webVtt: "WEBVTT\n\n00:00.000 --> 00:04.000\n<v Speaker 1>Good morning everyone and welcome to WebDev Conference 2024.\n\n00:04.500 --> 00:08.000\n<v Speaker 1>Today I'm excited to talk about the future of web accessibility.",
	srt: "1\n00:00:00,000 --> 00:00:03,500\nWelcome to our productivity app tutorial!\n\n2\n00:00:03,500 --> 00:00:07,000\nToday we'll show you how to get started in just 5 minutes.",
	descript:
		"Alice (00:00:01): Hello and welcome to today's podcast!\n\nBob (00:00:15): Thanks for having me!",
};

export default {
	title: 'Editor/Chat Log block',
	parameters: {
		layout: 'fullscreen',
		docs: {
			description: {
				component:
					'Source: `blocks/chatlog/edit.js`, registered by `blocks/chatlog/index.js` from `blocks/chatlog/block.json`. ' +
					'The block shows a transcript textarea and four inspector panels: Display Settings, Threading, Participants and Advanced. ' +
					'With Platform set to Auto-detect, it names the detected platform and a confidence score. ' +
					'The rendered chat appears only on the front end, from the PHP render callback. ' +
					'Transcripts come from `.wordpress-org/CHAT-LOG-FORMAT-EXAMPLES.md`. ' +
					'The editor detector reports Descript-style timecodes as Unknown, though the PHP parser renders them.',
			},
		},
	},
	render: ( attributes ) => (
		<EditorContext post={ POST }>
			<BlockEditorFrame
				initialBlocks={ [
					createBlock( 'chatlog/conversation', attributes ),
				] }
			/>
		</EditorContext>
	),
};

export const Empty = {};

export const DetectedSlackStyle = {
	args: { rawTranscript: TRANSCRIPTS.slackStyle },
};

export const DetectedWhatsApp = {
	args: { rawTranscript: TRANSCRIPTS.whatsApp },
};

export const DetectedWebVtt = { args: { rawTranscript: TRANSCRIPTS.webVtt } };

export const DetectedSrt = { args: { rawTranscript: TRANSCRIPTS.srt } };

export const UnknownFormat = { args: { rawTranscript: TRANSCRIPTS.descript } };

export const PlatformChosen = {
	args: { rawTranscript: TRANSCRIPTS.slackStyle, source: 'slack' },
};

export const TimestampsOff = {
	args: { rawTranscript: TRANSCRIPTS.whatsApp, showTimestamps: false },
};
