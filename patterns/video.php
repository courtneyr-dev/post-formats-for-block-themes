<?php
/**
 * Video format pattern registration.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Video Post Format Pattern
 *
 * Video file or embed. Starts with a video block followed by a paragraph.
 * Can be swapped with Post Kinds watch-card block.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Check if Post Kinds for IndieWeb watch-card block is available.
$pfbt_has_watch_card = \WP_Block_Type_Registry::get_instance()->is_registered( 'post-kinds-indieweb/watch-card' );

if ( $pfbt_has_watch_card ) {
	?>
<!-- wp:post-kinds-indieweb/watch-card /-->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
	<?php
} else {
	?>
<!-- wp:video -->
<figure class="wp-block-video"></figure>
<!-- /wp:video -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
	<?php
}
?>
