<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Gallery Post Format Pattern
 *
 * Image gallery post. Starts with a gallery block.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:gallery {"linkTo":"none"} -->
	<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
	<!-- wp:image -->
	<figure class="wp-block-image"><img alt=""/></figure>
	<!-- /wp:image -->
	</figure>
	<!-- /wp:gallery -->
</div>
<!-- /wp:group -->

