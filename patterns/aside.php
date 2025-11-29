<?php
/**
 * Aside Post Format Pattern
 *
 * Short note or update without a title. Displays in a bubble style.
 * First block is locked to maintain format integrity.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 */

// Locked group block with aside-bubble class for format detection.
// No styling - appears like standard post content.
?>
<!-- wp:group {"className":"aside-bubble","lock":{"move":false,"remove":false},"layout":{"type":"constrained"}} -->
<div class="wp-block-group aside-bubble">
<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
