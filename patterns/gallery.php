<?php
/**
 * Gallery Post Format Pattern
 *
 * Image gallery post. Starts with a gallery block.
 * First block is locked to maintain format integrity.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 */
?>
<!-- wp:gallery {"linkTo":"none","lock":{"move":false,"remove":false}} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
<!-- wp:image -->
<figure class="wp-block-image"><img alt=""/></figure>
<!-- /wp:image -->
</figure>
<!-- /wp:gallery -->
