<?php
/**
 * Quote / Pullquote block style variation definitions.
 *
 * Returns an associative array consumed by PFBT_Block_Style_Registry.
 * Each entry registers a `register_block_style()` call paired with a
 * conditionally-loaded stylesheet. By default, each variation registers
 * against BOTH `core/quote` and `core/pullquote` — set `block_names`
 * in the entry to override (e.g. for a variation whose visual conceit
 * only makes sense on one).
 *
 * Schema:
 *
 *   slug         (string, required) — variation slug; produces `is-style-{slug}`.
 *   label        (string, required) — translatable label shown in the picker.
 *   style_path   (string, required) — relative path to the variation's CSS.
 *   style_handle (string, optional) — defaults to `pfbt-quote-variation-{slug}`.
 *   block_names  (array,  optional) — defaults to ['core/quote', 'core/pullquote'].
 *   description  (string, optional) — short description for docs.
 *
 * Filter: `pfbt_quote_style_variations`.
 *
 * @package PostFormatsBlockThemes
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	// ============ Tactile / Nostalgic (8) ============

	array(
		'slug'        => 'post-it',
		'label'       => __( 'Post-it Note', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/post-it.css',
		'description' => __( 'Yellow square card with folded corner and slight rotation. Citation rendered inside, em-dash prefixed.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'notebook-scrap',
		'label'       => __( 'Notebook Scrap', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/notebook-scrap.css',
		'description' => __( 'Torn top edge, ruled lines, red margin line. Citation as a signature line.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'typewriter',
		'label'       => __( 'Typewriter', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/typewriter.css',
		'description' => __( 'Monospace on cream paper with faint ink-bleed shadow. Citation prefixed "-- ".', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'napkin',
		'label'       => __( 'Napkin', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/napkin.css',
		'description' => __( 'Soft white card with a coffee-ring stain and torn corner. Casual italic body.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'library-card',
		'label'       => __( 'Library Card', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/library-card.css',
		'description' => __( 'Cream card with ruled lines and a dark header bar. Citation as the all-caps title.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'chalkboard',
		'label'       => __( 'Chalkboard', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/chalkboard.css',
		'description' => __( 'Dark slate with chalk-style text and a wooden frame. Print inverts to plain.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'whiteboard',
		'label'       => __( 'Whiteboard', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/whiteboard.css',
		'description' => __( 'Clean white card with blue-marker body and red-marker citation.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'postcard',
		'label'       => __( 'Postcard', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/postcard.css',
		'description' => __( 'Landscape card with vertical divider — quote on inline-start, citation + stamp on inline-end.', 'post-formats-for-block-themes' ),
	),

	// ============ Editorial / Print (4) ============

	array(
		'slug'        => 'magazine-pull',
		'label'       => __( 'Magazine Pullquote', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/magazine-pull.css',
		'description' => __( 'Large display font with oversized decorative quote mark behind the text.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'broadsheet',
		'label'       => __( 'Broadsheet', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/broadsheet.css',
		'description' => __( 'Centered serif with double-rule top and bottom and corner quotation marks.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'decorative-marks',
		'label'       => __( 'Decorative Marks', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/decorative-marks.css',
		'description' => __( 'Oversized opening quote mark in the upper inline-start corner. Body sits to its inline-end.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'side-rule-editorial',
		'label'       => __( 'Side Rule', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/side-rule-editorial.css',
		'description' => __( 'Minimal: thick brand-color rule on inline-start, clean serif body. Pattern default.', 'post-formats-for-block-themes' ),
	),

	// ============ Conversational / Digital (3) ============

	array(
		'slug'        => 'speech-bubble',
		'label'       => __( 'Speech Bubble', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/speech-bubble.css',
		'description' => __( 'Rounded card with a tail. Citation rendered below as the speaker.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'message-bubble',
		'label'       => __( 'Message Bubble', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/message-bubble.css',
		'description' => __( 'SMS-style accent-colored bubble. Citation below as sender label.', 'post-formats-for-block-themes' ),
	),
	array(
		'slug'        => 'comment-card',
		'label'       => __( 'Comment Card', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/comment-card.css',
		'description' => __( 'Card with decorative initial avatar (set data-pfbt-avatar="A" on the block). Citation as username.', 'post-formats-for-block-themes' ),
	),

	// ============ Decorative (1) ============

	array(
		'slug'        => 'plaque',
		'label'       => __( 'Plaque', 'post-formats-for-block-themes' ),
		'style_path'  => 'styles/quote-variations/plaque.css',
		'description' => __( 'Engraved metal plaque with ornamental border and embossed text.', 'post-formats-for-block-themes' ),
	),
);
