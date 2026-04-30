<?php
/**
 * Quote display pattern (archive + single share this file).
 *
 * Title-less format. Format Icon sits above the post content; the post
 * content is expected to start with a core/quote block (the Quote
 * format's locked first block). Single posts may include commentary
 * paragraphs after the quote.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pfbt_variant = $pfbt_pattern_variant ?? 'archive';
?>
<!-- wp:group {"tagName":"article","className":"pfbt-format-card pfbt-format-card--quote pfbt-format-quote","layout":{"type":"constrained"}} -->
<article class="wp-block-group pfbt-format-card pfbt-format-card--quote pfbt-format-quote">

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
	</footer>
	<!-- /wp:group -->

</article>
<!-- /wp:group -->
