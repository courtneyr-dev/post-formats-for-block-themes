<?php
/**
 * Video display pattern (archive + single share this file).
 *
 * Has-title format. The first video block (core/video or core/embed)
 * appears at the top via wp:post-content; the format-tokens.css layer
 * ensures it renders at 16:9 aspect ratio by default.
 *
 * Session 9 will add pfbt_get_first_video() so archive teasers can
 * extract and render only the first video without the full content;
 * for now the archive teaser shows excerpt and only the single shows
 * the embedded video.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--video pfbt-format-video","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--video pfbt-format-video">

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
