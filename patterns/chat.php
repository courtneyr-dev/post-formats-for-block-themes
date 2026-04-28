<?php
/**
 * Chat format pattern registration.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Chat Post Format Pattern
 *
 * Chat transcript or conversation log. Uses the Chat Log block followed by a paragraph.
 *
 * Note: Requires the Chat Log plugin to be active.
 * If plugin is inactive, block will show as "missing block" (standard WP behavior).
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */
?>
<!-- wp:chatlog/conversation /-->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
