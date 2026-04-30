<?php
/**
 * Link display pattern (archive + single share this file).
 *
 * Twenty Thirteen rendered link posts with the title as an anchor to
 * the external URL stored in post meta `_pfbt_link_url` (registered in
 * Session 8). This pattern uses `wp:post-title {"isLink":true}` for now
 * and Session 8 will rewire the title's href via Block Bindings to the
 * meta value. The original (local) permalink shows as a "Discuss on this
 * site" secondary link below.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--link pfbt-format-link","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--link pfbt-format-link">

	<!-- wp:group {"tagName":"header","className":"pfbt-format-card__head","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<header class="wp-block-group pfbt-format-card__head">
		<!-- wp:post-formats/format-icon {"lock":{"move":false,"remove":true}} /-->
		<!-- wp:post-title {"level":<?php echo 'single' === $pfbt_variant ? 1 : 2; ?>,"isLink":true} /-->
	</header>
	<!-- /wp:group -->

	<!-- wp:group {"className":"pfbt-format-card__body","layout":{"type":"constrained"}} -->
	<div class="wp-block-group pfbt-format-card__body">
		<?php if ( 'single' === $pfbt_variant ) : ?>
			<!-- wp:post-content /-->
		<?php else : ?>
			<!-- wp:post-excerpt {"showMoreOnNewLine":false} /-->
		<?php endif; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"tagName":"footer","className":"pfbt-format-card__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<footer class="wp-block-group pfbt-format-card__meta">
		<!-- wp:post-date /-->
	</footer>
	<!-- /wp:group -->

</article>
<!-- /wp:group -->
