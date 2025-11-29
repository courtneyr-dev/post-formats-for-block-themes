<?php
/**
 * Link Post Format Pattern
 *
 * Link to external content. Uses Bookmark Card plugin if available,
 * otherwise falls back to paragraph with link styling.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 */

// Check if Bookmark Card plugin is available.
$pfpu_has_bookmark_card = function_exists( 'bookmark_card_register_block' ) || has_block( 'bookmark-card/bookmark-card' );

if ( $pfpu_has_bookmark_card ) {
	// Use Bookmark Card block (locked).
	?>
	<!-- wp:bookmark-card/bookmark-card {"lock":{"move":false,"remove":false}} /-->
	<?php
} else {
	// Fallback to paragraph with link placeholder (locked).
	?>
	<!-- wp:paragraph {"className":"link-format-fallback","lock":{"move":false,"remove":false},"fontSize":"large"} -->
	<p class="link-format-fallback has-large-font-size"><a href="#"></a></p>
	<!-- /wp:paragraph -->
	<?php
}
