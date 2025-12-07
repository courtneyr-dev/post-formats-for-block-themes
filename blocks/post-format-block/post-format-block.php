<?php
/**
 * Post Format Block - Display block for post formats
 *
 * Adds a Post Format Block as a variation of the core/post-terms block.
 * This allows displaying a post's format in block-based themes.
 *
 * Forked from: Post Format Block by Aaron Jorbin
 * Original Plugin URI: https://wordpress.org/plugins/post-format-block/
 * License: GPL-2.0-or-later
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Post Format block
 *
 * The block type we register is actually a variation of core/post-terms,
 * but this allows it to show up as a "block" in the inserter.
 *
 * @since 1.0.0
 */
function pfbt_post_format_block_init() {
	register_block_type( __DIR__ . '/block.json' );
}
add_action( 'init', 'pfbt_post_format_block_init' );

/**
 * Make the post formats taxonomy available in the REST API
 *
 * This is required for the post-terms block variation to work properly
 * with post formats in the block editor.
 *
 * @since 1.0.0
 *
 * @param array  $args          Taxonomy registration args.
 * @param string $taxonomy_name Taxonomy name.
 * @return array Modified taxonomy args.
 */
function pfbt_post_format_rest_api( $args, $taxonomy_name ) {
	if ( 'post_format' === $taxonomy_name ) {
		$args['show_in_rest'] = true;
	}
	return $args;
}
add_filter( 'register_taxonomy_args', 'pfbt_post_format_rest_api', 10, 2 );
