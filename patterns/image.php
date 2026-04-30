<?php
/**
 * Image Post Format Pattern
 *
 * Single image post. Starts with an image block (using the
 * `caption-card` block style variation when v2.1.0+ image styles
 * are enabled) followed by a paragraph.
 *
 * The variation flips the figure into a unified card surface — image
 * + caption inside one bordered, padded shape. The class is harmless
 * if the image_gallery_styles feature flag is off (it just falls back
 * to default core image styling).
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:image {"sizeSlug":"large","className":"is-style-caption-card"} -->
<figure class="wp-block-image size-large is-style-caption-card"><img alt=""/></figure>
<!-- /wp:image -->

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
