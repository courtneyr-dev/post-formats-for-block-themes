<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Standard Post Format Pattern
 *
 * Default post format with no special first block requirement.
 * Starts with an empty canvas for traditional blog posts.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// No locked blocks for standard format - full freedom.
echo '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:paragraph -->
	<p></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->';
