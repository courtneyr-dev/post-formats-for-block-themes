<?php
/**
 * Standard display pattern (archive + single share this file).
 *
 * Default-format posts. No Format Icon (the block bails for standard
 * format), no plugin-namespaced container class. Reads as the theme's
 * normal post layout — title, meta, content (or excerpt + read-more
 * for archive teasers).
 *
 * Standard format intentionally has no .pfbt-format-card or
 * .pfbt-format-standard wrapper class. The plugin only adds visual
 * affordances for non-standard formats; standard posts inherit the
 * theme's default styling.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--standard","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--standard">

	<!-- wp:group {"tagName":"header","className":"pfbt-format-card__head","layout":{"type":"constrained"}} -->
	<header class="wp-block-group pfbt-format-card__head">
		<!-- wp:post-title {"level":<?php echo 'single' === $pfbt_variant ? 1 : 2; ?>,"isLink":true} /-->
		<!-- wp:post-date /-->
	</header>
	<!-- /wp:group -->

	<!-- wp:group {"className":"pfbt-format-card__body","layout":{"type":"constrained"}} -->
	<div class="wp-block-group pfbt-format-card__body">
		<?php if ( 'single' === $pfbt_variant ) : ?>
			<!-- wp:post-content /-->
		<?php else : ?>
			<!-- wp:post-excerpt {"showMoreOnNewLine":false} /-->
			<!-- wp:read-more /-->
		<?php endif; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"tagName":"footer","className":"pfbt-format-card__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
	<footer class="wp-block-group pfbt-format-card__meta">
		<?php if ( 'single' === $pfbt_variant ) : ?>
			<!-- wp:post-terms {"term":"category"} /-->
			<!-- wp:post-terms {"term":"post_tag"} /-->
		<?php else : ?>
			<!-- wp:post-terms {"term":"category"} /-->
		<?php endif; ?>
	</footer>
	<!-- /wp:group -->

</article>
<!-- /wp:group -->
