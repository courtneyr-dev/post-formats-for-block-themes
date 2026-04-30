<?php
/**
 * Audio display pattern (archive + single share this file).
 *
 * Has-title format. Audio player appears via wp:post-content / excerpt.
 * The .pfbt-format-audio container is paint-neutral by default — themes
 * that want an inverted "audio booth" treatment set --pfbt-format-audio-bg
 * and --pfbt-format-audio-fg in their stylesheet. The plugin never
 * forces an inverted treatment.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--audio pfbt-format-audio","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--audio pfbt-format-audio">

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
