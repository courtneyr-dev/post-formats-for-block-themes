<?php
/**
 * Gallery Post Format Pattern.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_gallery = WP_Block_Type_Registry::get_instance()->get_registered( 'core/gallery' );
if ( $pfbt_gallery && block_has_support( $pfbt_gallery, array( 'layout', 'allowEditing' ) ) ) :
	?>
<!-- wp:gallery {"linkTo":"none","layout":{"type":"grid","minimumColumnWidth":"12rem"}} -->
<figure class="wp-block-gallery has-nested-images"></figure>
<!-- /wp:gallery -->
<?php else : ?>
<!-- wp:gallery {"linkTo":"none","className":"is-style-justified-rows"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped is-style-justified-rows"></figure>
<!-- /wp:gallery -->
<?php endif; ?>
