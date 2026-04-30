<?php
/**
 * Aside display pattern (archive + single variants share this file).
 *
 * Title-less format: no wp:post-title block, content first. Mirrors
 * Twenty Thirteen's content-aside.php layout.
 *
 * Output shape:
 *   <article class="pfbt-format-card pfbt-format-card--aside pfbt-format-aside">
 *     <span class="pfbt-format-icon pfbt-format-icon--aside">…</span>
 *     <div class="entry-content">
 *       {Excerpt or Content}
 *     </div>
 *     <footer class="pfbt-format-card__meta">
 *       {Date}
 *     </footer>
 *   </article>
 *
 * Variant variable:
 *   $pfbt_pattern_variant — 'archive' (emits Post Excerpt) or 'single'
 *                            (emits Post Content). Set by the registrar
 *                            in class-pattern-manager.php before include.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--aside pfbt-format-aside","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--aside pfbt-format-aside">

	<!-- wp:post-formats/format-icon {"lock":{"move":false,"remove":true}} /-->

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
