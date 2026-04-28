<?php
/**
 * Status format pattern registration.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Status Post Format Pattern
 *
 * Short status update without title, limited to 280 characters (Twitter-style).
 * Character validation handled by JavaScript.
 * Can be swapped with Post Kinds mood-card block.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Check if Post Kinds for IndieWeb mood-card block is available.
$pfbt_has_mood_card = \WP_Block_Type_Registry::get_instance()->is_registered( 'post-kinds-indieweb/mood-card' );

if ( $pfbt_has_mood_card ) {
	?>
<!-- wp:post-kinds-indieweb/mood-card /-->

<!-- wp:paragraph {"className":"status-paragraph","fontSize":"large"} -->
<p class="status-paragraph has-large-font-size"></p>
<!-- /wp:paragraph -->
	<?php
} else {
	// Single paragraph with status-paragraph class for character counter.
	?>
<!-- wp:paragraph {"className":"status-paragraph","fontSize":"large"} -->
<p class="status-paragraph has-large-font-size"></p>
<!-- /wp:paragraph -->
	<?php
}
?>
