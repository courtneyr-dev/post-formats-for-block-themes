<?php
/**
 * Gallery Post Format Pattern
 *
 * Image gallery post. Starts with a gallery block (using the
 * `justified-rows` block style variation when v2.1.0+ gallery
 * styles are enabled) followed by a paragraph.
 *
 * The variation balances mixed-aspect images into uniform-height
 * rows with no JS. The class is harmless if the image_gallery_styles
 * feature flag is off (falls back to default core gallery styling).
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:gallery {"linkTo":"none","className":"is-style-justified-rows"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped is-style-justified-rows"></figure>
<!-- /wp:gallery -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
