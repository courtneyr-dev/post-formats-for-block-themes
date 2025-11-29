<?php
/**
 * Standard Post Format Pattern
 *
 * Default post format with no special first block requirement.
 * Starts with an empty canvas for traditional blog posts.
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 */

// No locked blocks for standard format - full freedom.
echo '<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->';
