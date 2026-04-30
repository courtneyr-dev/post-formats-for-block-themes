<?php
/**
 * Image display pattern (archive + single share this file).
 *
 * Has-title format. Featured Image dominates above title — Twenty
 * Thirteen used this layout to give image-format posts photo-first
 * presence. Single posts then show full content below; archive teasers
 * show excerpt.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--image pfbt-format-image","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--image pfbt-format-image">

	<!-- wp:post-featured-image {"isLink":true,"className":"pfbt-format-card__featured"} /-->

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
		<?php if ( 'single' === $pfbt_variant ) : ?>
			<!-- wp:post-terms {"term":"category"} /-->
		<?php endif; ?>
	</footer>
	<!-- /wp:group -->

</article>
<!-- /wp:group -->
