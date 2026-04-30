<?php
/**
 * Link display pattern (archive + single share this file).
 *
 * Twenty Thirteen rendered link posts with the title as an anchor to
 * an external URL. PFBT 2.0 stores that URL in post meta `_pfbt_link_url`
 * (registered by PFBT_Link_Meta in Session 8) and the post-title block
 * uses Block Bindings to bind its `url` attribute to the
 * post-formats/format-data binding source's `link_url` key.
 *
 * Lookup order for the external URL (handled by PFBT_Link_Meta::get_link_url()):
 *   1. _pfbt_link_url meta value (user-set)
 *   2. First Bookmark Card block's url attribute in post content
 *   3. Falls back to the post permalink (since isLink stays true)
 *
 * The original (local) permalink can show as a "Discuss on this site"
 * secondary link via a separate <wp:read-more> or paragraph block in
 * post content.
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
		<!-- wp:post-title {"level":<?php echo 'single' === $pfbt_variant ? 1 : 2; ?>,"isLink":true,"metadata":{"bindings":{"url":{"source":"post-formats/format-data","args":{"key":"link_url"}}}}} /-->
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
