<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Status Post Format Pattern
 *
 * Short status update without title, limited to 280 characters (Twitter-style).
 * Character validation handled by JavaScript.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Paragraph with status-paragraph class for validation.
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:paragraph {"className":"status-paragraph","fontSize":"large"} -->
	<p class="status-paragraph has-large-font-size"></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

