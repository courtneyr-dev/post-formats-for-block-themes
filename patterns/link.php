<?php
/**
 * Link format pattern registration.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Link Post Format Pattern
 *
 * Link to external content. Uses Bookmark Card plugin if available,
 * otherwise falls back to paragraph with link styling.
 * Followed by a paragraph for additional commentary.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Check if Bookmark Card plugin is available.
$pfbt_has_bookmark_card = function_exists( 'bookmark_card_register_block' ) || has_block( 'bookmark-card/bookmark-card' );

if ( $pfbt_has_bookmark_card ) {
	// Use Bookmark Card block.
	?>
<!-- wp:bookmark-card/bookmark-card /-->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
	<?php
} else {
	// Fallback to paragraph with link placeholder.
	?>
<!-- wp:paragraph {"className":"link-format-fallback","fontSize":"large"} -->
<p class="link-format-fallback has-large-font-size"><a href="#"></a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
	<?php
}
?>
