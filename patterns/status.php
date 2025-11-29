<?php
/**
 * Status Post Format Pattern
 *
 * Short status update without title, limited to 280 characters (Twitter-style).
 * First block is locked. Character validation handled by JavaScript.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 */

// Locked paragraph with status-paragraph class for validation.
?>
<!-- wp:paragraph {"className":"status-paragraph","lock":{"move":false,"remove":false},"fontSize":"large"} -->
<p class="status-paragraph has-large-font-size"></p>
<!-- /wp:paragraph -->
