// Single source of per-plugin parameters for the docs site.
// Everything else (astro.config.mjs, Head.astro, structured data) reads from here.
export default {
	name: 'Post Formats for Block Themes',
	slug: 'post-formats-for-block-themes',
	site: 'https://courtneyr-dev.github.io',
	base: '/post-formats-for-block-themes',
	description:
		'User documentation for Post Formats for Block Themes: bring quote, status, chat, and other post formats to WordPress block themes.',
	github: 'https://github.com/courtneyr-dev/post-formats-for-block-themes',
	wporg: 'https://wordpress.org/plugins/post-formats-for-block-themes/',
	version: '1.1.5',
	requiresWP: '6.9',
	requiresPHP: '7.4',
	author: 'Courtney Robertson',
	authorUrl: 'https://courtneyr.dev/',
};
