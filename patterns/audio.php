<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Audio Post Format Pattern
 *
 * Audio file or embed. Starts with a core audio block followed by a paragraph.
 * Can be swapped with Podlove Podcast Publisher, Able Player, or Post Kinds listen-card blocks.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Check if Post Kinds for IndieWeb listen-card block is available.
$pfbt_has_listen_card = \WP_Block_Type_Registry::get_instance()->is_registered( 'post-kinds-indieweb/listen-card' );

if ( $pfbt_has_listen_card ) {
	?>
<!-- wp:post-kinds-indieweb/listen-card /-->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
	<?php
} else {
	?>
<!-- wp:audio -->
<figure class="wp-block-audio"><audio controls></audio></figure>
<!-- /wp:audio -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
	<?php
}
?>
