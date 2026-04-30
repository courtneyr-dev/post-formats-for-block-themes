<?php
/**
 * Status display pattern (archive + single share this file).
 *
 * Title-less format. Twenty Thirteen rendered status posts with the
 * author avatar at the top — this pattern emits an Avatar block + the
 * Format Icon side-by-side, then the content. Suited for Twitter-style
 * 280-character status updates.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--status pfbt-format-status","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--status pfbt-format-status">

	<!-- wp:group {"className":"pfbt-format-card__head","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group pfbt-format-card__head">
		<!-- wp:avatar {"size":48} /-->
		<!-- wp:post-formats/format-icon {"lock":{"move":false,"remove":true}} /-->
	</div>
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
		<!-- wp:post-date {"isLink":true} /-->
		<!-- wp:post-author-name {"isLink":true} /-->
	</footer>
	<!-- /wp:group -->

</article>
<!-- /wp:group -->
