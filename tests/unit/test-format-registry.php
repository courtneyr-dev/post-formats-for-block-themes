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
	 * Explicit extension mappings take precedence over Playlist inference.
	 */
	public function test_playlist_respects_registered_format() {
		$property = new ReflectionProperty( PFBT_Format_Registry::class, 'instance' );
		$original = $property->getValue();
		$map      = static function ( $formats ) {
			$formats['video']['first_block'] = 'core/playlist';
			return $formats;
		};
		$playlist = parse_blocks( '<!-- wp:playlist --><figure><!-- wp:playlist-track {"src":"https://example.org/track.mp3"} /--></figure><!-- /wp:playlist -->' )[0];
		add_filter( 'pfbt_registered_formats', $map );
		$property->setValue( null, null );

		try {
			$this->assertSame(
				'video',
				PFBT_Format_Registry::get_format_by_block( $playlist['blockName'], $playlist['attrs'], $playlist['innerBlocks'] )
			);
		} finally {
			$property->setValue( null, $original );
			remove_filter( 'pfbt_registered_formats', $map );
		}
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
