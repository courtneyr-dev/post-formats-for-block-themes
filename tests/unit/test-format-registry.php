<?php
/**
 * Unit tests for Format Registry
 *
 * Tests the format detection logic in isolation.
 *
 * @package PostFormatsBlockThemes
 * @covers PFBT_Format_Registry
 */

class Test_Format_Registry extends WP_UnitTestCase {

	/**
	 * Reset option + registry singleton so per-test quote block choices
	 * don't leak into other tests.
	 */
	public function tear_down() {
		delete_option( PFBT_Quote_Block_Setting::OPTION_KEY );
		$this->reset_registry_singleton();
		parent::tear_down();
	}

	/**
	 * Null the registry singleton so the next call re-reads options.
	 */
	private function reset_registry_singleton() {
		$prop = new ReflectionProperty( 'PFBT_Format_Registry', 'instance' );
		$prop->setValue( null, null );
	}

	/**
	 * Test that all 10 formats are registered
	 */
	public function test_all_formats_registered() {
		$formats = PFBT_Format_Registry::get_all_formats();

		$this->assertCount( 10, $formats );

		$expected_formats = array(
			'standard',
			'aside',
			'gallery',
			'link',
			'image',
			'quote',
			'status',
			'video',
			'audio',
			'chat',
		);

		foreach ( $expected_formats as $format_slug ) {
			$this->assertArrayHasKey( $format_slug, $formats );
		}
	}

	/**
	 * Test gallery block detection
	 *
	 * @covers PFBT_Format_Registry::get_format_by_block
	 */
	public function test_gallery_block_detected() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/gallery',
			array()
		);

		$this->assertEquals( 'gallery', $format );
	}

	/**
	 * Test quote block detection
	 */
	public function test_quote_block_detected() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/quote',
			array()
		);

		$this->assertEquals( 'quote', $format );
	}

	/**
	 * Test pullquote block detection maps to quote
	 *
	 * The docs promise pullquote-first posts suggest the Quote format
	 * (the quote docs screenshots seed pullquote content); detection
	 * accepts it via the quote format's alt_blocks alias.
	 *
	 * @covers PFBT_Format_Registry::get_format_by_block
	 */
	public function test_pullquote_block_detected_as_quote() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/pullquote',
			array()
		);

		$this->assertEquals( 'quote', $format );
	}

	/**
	 * Test aside format detection via class
	 */
	public function test_aside_detected_by_class() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/group',
			array( 'className' => 'aside-bubble' )
		);

		$this->assertEquals( 'aside', $format );
	}

	/**
	 * Test status format detection via class
	 */
	public function test_status_detected_by_class() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/paragraph',
			array( 'className' => 'status-paragraph' )
		);

		$this->assertEquals( 'status', $format );
	}

	/**
	 * Test video block detection
	 */
	public function test_video_block_detected() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/video',
			array()
		);

		$this->assertEquals( 'video', $format );
	}

	/**
	 * Test audio block detection
	 */
	public function test_audio_block_detected() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/audio',
			array()
		);

		$this->assertEquals( 'audio', $format );
	}

	/**
	 * Test image block detection
	 */
	public function test_image_block_detected() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/image',
			array()
		);

		$this->assertEquals( 'image', $format );
	}

	/**
	 * Test that unknown blocks return standard
	 */
	public function test_unknown_block_returns_standard() {
		$format = PFBT_Format_Registry::get_format_by_block(
			'core/paragraph',
			array()
		);

		$this->assertEquals( 'standard', $format );
	}

	/**
	 * Test the quote format's default block follows the site setting
	 *
	 * Default is core/quote; choosing pullquote flips first_block while
	 * the other block stays a detection alias, so BOTH block types keep
	 * detecting as quote under either setting.
	 *
	 * @covers PFBT_Format_Registry::get_format_by_block
	 */
	public function test_quote_default_block_follows_setting() {
		// Default: quote is primary.
		$quote = PFBT_Format_Registry::get_format( 'quote' );
		$this->assertSame( 'core/quote', $quote['first_block'] );
		$this->assertSame( array( 'core/pullquote' ), $quote['alt_blocks'] );

		// Flip the setting to pullquote.
		update_option( PFBT_Quote_Block_Setting::OPTION_KEY, 'pullquote' );
		$this->reset_registry_singleton();

		$quote = PFBT_Format_Registry::get_format( 'quote' );
		$this->assertSame( 'core/pullquote', $quote['first_block'] );
		$this->assertSame( array( 'core/quote' ), $quote['alt_blocks'] );

		// Detection accepts both blocks regardless of the chosen default.
		$this->assertEquals( 'quote', PFBT_Format_Registry::get_format_by_block( 'core/quote', array() ) );
		$this->assertEquals( 'quote', PFBT_Format_Registry::get_format_by_block( 'core/pullquote', array() ) );
	}

	/**
	 * Test the quote block setting rejects invalid values
	 *
	 * @covers PFBT_Quote_Block_Setting::sanitize_choice
	 */
	public function test_quote_block_setting_sanitizes_invalid_values() {
		$this->assertSame( 'quote', PFBT_Quote_Block_Setting::sanitize_choice( 'not-a-choice' ) );
		$this->assertSame( 'quote', PFBT_Quote_Block_Setting::sanitize_choice( array( 'pullquote' ) ) );
		$this->assertSame( 'pullquote', PFBT_Quote_Block_Setting::sanitize_choice( 'pullquote' ) );
		$this->assertSame( 'quote', PFBT_Quote_Block_Setting::sanitize_choice( 'quote' ) );
	}

	/**
	 * Test the quote pattern inserts the chosen block
	 *
	 * @covers PFBT_Quote_Block_Setting::get_active_slug
	 */
	public function test_quote_pattern_content_follows_setting() {
		$pattern = PFBT_Pattern_Manager::get_pattern( 'quote' );
		$this->assertStringStartsWith( '<!-- wp:quote', trim( $pattern ) );

		update_option( PFBT_Quote_Block_Setting::OPTION_KEY, 'pullquote' );

		$pattern = PFBT_Pattern_Manager::get_pattern( 'quote' );
		$this->assertStringStartsWith( '<!-- wp:pullquote', trim( $pattern ) );
	}

	/**
	 * Test format_exists method
	 */
	public function test_format_exists() {
		$this->assertTrue( PFBT_Format_Registry::format_exists( 'gallery' ) );
		$this->assertTrue( PFBT_Format_Registry::format_exists( 'aside' ) );
		$this->assertFalse( PFBT_Format_Registry::format_exists( 'nonexistent' ) );
	}

	/**
	 * Regression test: Ensure aside pattern is unstyled
	 *
	 * Bug fix from v1.0.1 - aside pattern was inserting styled content
	 */
	public function test_aside_pattern_is_unstyled_regression() {
		$pattern = PFBT_Pattern_Manager::get_pattern( 'aside' );

		// Should not contain styling attributes
		$this->assertStringNotContainsString( 'backgroundColor', $pattern );
		$this->assertStringNotContainsString( 'padding', $pattern );
		$this->assertStringNotContainsString( 'border', $pattern );

		// Should not contain instructional text
		$this->assertStringNotContainsString( 'Share a quick thought', $pattern );
		$this->assertStringNotContainsString( 'Add more content', $pattern );
	}
}
