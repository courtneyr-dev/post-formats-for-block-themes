<?php
/**
 * Quote format pattern registration.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Quote Post Format Pattern
 *
 * Quotation or citation. Starts with a quote or pullquote block —
 * whichever the site owner chose on Settings → Post Formats — using
 * the `side-rule-editorial` block style variation when v2.2.0+ quote
 * styles are enabled, followed by a paragraph. side-rule-editorial
 * is intentionally the most minimal of the 16 — reads as "edited"
 * without locking authors into a strong aesthetic, and registers on
 * both core/quote and core/pullquote.
 *
 * The class is harmless if the quote_styles feature flag is off:
 * the variation isn't registered, so it falls back to default core
 * quote rendering.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

if ( 'pullquote' === PFBT_Quote_Block_Setting::get_active_slug() ) :
	?>
<!-- wp:pullquote {"className":"is-style-side-rule-editorial"} -->
<figure class="wp-block-pullquote is-style-side-rule-editorial"><blockquote><p></p><cite></cite></blockquote></figure>
<!-- /wp:pullquote -->
<?php else : ?>
<!-- wp:quote {"className":"is-style-side-rule-editorial"} -->
<blockquote class="wp-block-quote is-style-side-rule-editorial">
<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
</blockquote>
<!-- /wp:quote -->
<?php endif; ?>

<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
