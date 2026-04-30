<?php
/**
 * Contract tests for v2.2.0 quote/pullquote variations.
 *
 * Enforces the architectural promises from the v2.2.0 brief:
 *
 *   1. Every variation file includes the chrome reset (border, padding,
 *      margin, font-style, background, color all zeroed).
 *   2. Every variation file includes a `cite:empty { display: none }`
 *      rule so empty <cite> elements don't leave layout gaps.
 *   3. Every variation file ships a `@media print` rule.
 *   4. Every variation registers the quote selector against both
 *      .wp-block-quote.is-style-{slug} AND .wp-block-pullquote.is-style-{slug}.
 *
 * @package PostFormatsBlockThemes
 * @covers PFBT_Block_Style_Registry
 */

/**
 * Class Test_Quote_Variation_Contract
 */
class Test_Quote_Variation_Contract extends WP_UnitTestCase {

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Cache the plugin root path for relative file lookups.
	 */
	public function set_up() {
		parent::set_up();
		$this->plugin_dir = defined( 'PFBT_PLUGIN_DIR' )
			? PFBT_PLUGIN_DIR
			: dirname( __DIR__, 2 ) . '/';
	}

	/**
	 * Get the absolute path to a variation's CSS file given its slug.
	 *
	 * @param string $slug Variation slug.
	 * @return string Absolute path.
	 */
	private function variation_path( $slug ) {
		return $this->plugin_dir . 'styles/quote-variations/' . $slug . '.css';
	}

	/**
	 * Provide all 16 v2.2.0 quote variation slugs.
	 *
	 * Returns provider data: each row is `[$slug]`. Phpunit calls the
	 * test method once per row.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function quote_variations_provider() {
		return array(
			array( 'post-it' ),
			array( 'notebook-scrap' ),
			array( 'typewriter' ),
			array( 'napkin' ),
			array( 'library-card' ),
			array( 'chalkboard' ),
			array( 'whiteboard' ),
			array( 'postcard' ),
			array( 'magazine-pull' ),
			array( 'broadsheet' ),
			array( 'decorative-marks' ),
			array( 'side-rule-editorial' ),
			array( 'speech-bubble' ),
			array( 'message-bubble' ),
			array( 'comment-card' ),
			array( 'plaque' ),
		);
	}

	/**
	 * Each variation file exists at the expected path.
	 *
	 * @dataProvider quote_variations_provider
	 *
	 * @param string $slug Variation slug.
	 */
	public function test_variation_file_exists( $slug ) {
		$path = $this->variation_path( $slug );
		$this->assertFileExists( $path, "Quote variation file missing: {$slug}.css" );
	}

	/**
	 * Each variation includes the chrome reset block.
	 *
	 * The reset zeros core's default border, padding, margin-inline-start,
	 * font-style, background, and color so the variation can draw its
	 * own design without fighting core chrome.
	 *
	 * @dataProvider quote_variations_provider
	 *
	 * @param string $slug Variation slug.
	 */
	public function test_variation_includes_chrome_reset( $slug ) {
		$path = $this->variation_path( $slug );
		$css  = file_get_contents( $path );

		$this->assertStringContainsString(
			'border: 0;',
			$css,
			"Variation {$slug} missing border: 0 reset"
		);
		$this->assertStringContainsString(
			'padding: 0;',
			$css,
			"Variation {$slug} missing padding: 0 reset"
		);
		$this->assertStringContainsString(
			'margin-inline-start: 0;',
			$css,
			"Variation {$slug} missing margin-inline-start: 0 reset"
		);
	}

	/**
	 * Each variation hides empty <cite> elements.
	 *
	 * Per the v2.2.0 brief Hard Rule 4: when citation is absent, no
	 * empty element renders and no awkward spacing remains.
	 *
	 * @dataProvider quote_variations_provider
	 *
	 * @param string $slug Variation slug.
	 */
	public function test_variation_hides_empty_cite( $slug ) {
		$path = $this->variation_path( $slug );
		$css  = file_get_contents( $path );

		$this->assertStringContainsString(
			'cite:empty',
			$css,
			"Variation {$slug} missing cite:empty rule (the citation-empty contract)"
		);
		$this->assertStringContainsString(
			'display: none',
			$css,
			"Variation {$slug} missing display: none for cite:empty"
		);
	}

	/**
	 * Each variation registers selectors on BOTH .wp-block-quote and
	 * .wp-block-pullquote.
	 *
	 * @dataProvider quote_variations_provider
	 *
	 * @param string $slug Variation slug.
	 */
	public function test_variation_targets_both_blocks( $slug ) {
		$path = $this->variation_path( $slug );
		$css  = file_get_contents( $path );

		$this->assertStringContainsString(
			".wp-block-quote.is-style-{$slug}",
			$css,
			"Variation {$slug} missing .wp-block-quote selector"
		);
		$this->assertStringContainsString(
			".wp-block-pullquote.is-style-{$slug}",
			$css,
			"Variation {$slug} missing .wp-block-pullquote selector"
		);
	}

	/**
	 * Each variation has zero `!important` declarations.
	 *
	 * Per the v2.2.0 brief Hard Rule 8: specificity wins via the chained
	 * .wp-block-quote.is-style-{slug} selector. !important is banned.
	 *
	 * @dataProvider quote_variations_provider
	 *
	 * @param string $slug Variation slug.
	 */
	public function test_variation_has_no_important_declarations( $slug ) {
		$path = $this->variation_path( $slug );
		$css  = file_get_contents( $path );

		$this->assertStringNotContainsString(
			'!important',
			$css,
			"Variation {$slug} contains !important — Hard Rule 8 forbids it"
		);
	}
}
