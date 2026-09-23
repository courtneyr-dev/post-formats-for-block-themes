<?php
/**
 * Tests for MCP Abilities
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

/**
 * Test class for MCP Abilities
 *
 * Tests format analysis, suggestions, and validation.
 *
 * @since 1.2.0
 */
class Test_MCP_Abilities extends WP_UnitTestCase {

	/**
	 * Format analyzer instance
	 *
	 * @var PFBT_Format_Analyzer
	 */
	private $analyzer;

	/**
	 * MCP abilities instance
	 *
	 * @var PFBT_MCP_Abilities
	 */
	private $abilities;

	/**
	 * Set up test fixtures
	 */
	public function set_up() {
		parent::set_up();

		// Load required files.
		require_once PFBT_PLUGIN_DIR . 'includes/mcp/class-pfbt-format-analyzer.php';
		require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-mcp-abilities.php';

		$this->analyzer  = PFBT_Format_Analyzer::instance();
		$this->abilities = PFBT_MCP_Abilities::instance();
	}

	/**
	 * Test analyzer singleton
	 */
	public function test_analyzer_singleton() {
		$instance1 = PFBT_Format_Analyzer::instance();
		$instance2 = PFBT_Format_Analyzer::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test abilities singleton
	 */
	public function test_abilities_singleton() {
		$instance1 = PFBT_MCP_Abilities::instance();
		$instance2 = PFBT_MCP_Abilities::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test status format detection - short content
	 */
	public function test_detect_status_format() {
		$content = '<p>Just shipped a new feature! Feeling great about the progress.</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'status', $result['suggested_format'] );
		$this->assertGreaterThan( 50, $result['confidence'] );
		$this->assertTrue( $result['signals']['short_content'] );
		$this->assertTrue( $result['signals']['single_paragraph'] );
	}

	/**
	 * Test quote format detection
	 */
	public function test_detect_quote_format() {
		$content = '<blockquote><p>The best way to predict the future is to invent it.</p></blockquote><cite>Alan Kay</cite>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'quote', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['has_blockquote'] );
		$this->assertTrue( $result['signals']['has_cite'] );
	}

	/**
	 * Test link format detection
	 */
	public function test_detect_link_format() {
		$content = '<p>Check out <a href="https://example.com/great-article">this great article</a>.</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'link', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['has_links'] );
		$this->assertTrue( $result['signals']['external_link'] );
		$this->assertTrue( $result['signals']['dominant_url'] );
	}

	/**
	 * Test image format detection
	 */
	public function test_detect_image_format() {
		$content = '<figure><img src="https://example.com/photo.jpg" alt="My photo"></figure><p>Caption here</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'image', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['has_images'] );
		$this->assertTrue( $result['signals']['single_image'] );
		$this->assertTrue( $result['signals']['has_figure'] );
	}

	/**
	 * Test gallery format detection
	 */
	public function test_detect_gallery_format() {
		$content = '<div class="wp-block-gallery"><img src="https://example.com/1.jpg"><img src="https://example.com/2.jpg"><img src="https://example.com/3.jpg"></div>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'gallery', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['multiple_images'] );
		$this->assertTrue( $result['signals']['gallery_block'] );
	}

	/**
	 * Test video format detection
	 */
	public function test_detect_video_format() {
		$content = '<figure class="wp-block-embed wp-block-embed-youtube"><div class="wp-block-embed__wrapper">https://youtube.com/watch?v=abc123</div></figure>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'video', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['has_video'] );
		$this->assertTrue( $result['signals']['youtube_vimeo'] );
	}

	/**
	 * Test audio format detection
	 */
	public function test_detect_audio_format() {
		$content = '<figure class="wp-block-audio"><audio src="https://example.com/podcast.mp3" controls></audio></figure>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'audio', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['has_audio'] );
	}

	/**
	 * Test chat format detection
	 */
	public function test_detect_chat_format() {
		$content = '<p>Alice: Hey, how are you?</p><p>Bob: I\'m doing great, thanks!</p><p>Alice: Glad to hear it.</p><p>Bob: Want to grab coffee?</p><p>Alice: Sure thing!</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertEquals( 'chat', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['speaker_labels'] );
	}

	/**
	 * Test standard format detection - long content
	 */
	public function test_detect_standard_format() {
		$content = '<h2>Introduction</h2><p>' . str_repeat( 'This is a long article with lots of content. ', 50 ) . '</p><h2>Section Two</h2><p>' . str_repeat( 'More content here discussing various topics. ', 50 ) . '</p>';

		$result = $this->analyzer->analyze( $content, 'My Long Article' );

		$this->assertEquals( 'standard', $result['suggested_format'] );
		$this->assertTrue( $result['signals']['long_content'] );
		$this->assertTrue( $result['signals']['has_headings'] );
	}

	/**
	 * Test validation for image format - valid
	 */
	public function test_validate_image_format_valid() {
		$content = '<img src="https://example.com/photo.jpg" alt="Test">';

		$result = $this->analyzer->validate_for_format( $content, 'image', '' );

		$this->assertTrue( $result['valid'] );
		$this->assertEmpty( $result['messages'] );
	}

	/**
	 * Test validation for image format - invalid
	 */
	public function test_validate_image_format_invalid() {
		$content = '<p>Just some text, no images here.</p>';

		$result = $this->analyzer->validate_for_format( $content, 'image', '' );

		$this->assertFalse( $result['valid'] );
		$this->assertNotEmpty( $result['messages'] );
	}

	/**
	 * Test validation for video format - invalid
	 */
	public function test_validate_video_format_invalid() {
		$content = '<p>Just text content.</p>';

		$result = $this->analyzer->validate_for_format( $content, 'video', '' );

		$this->assertFalse( $result['valid'] );
	}

	/**
	 * Test validation for audio format - invalid
	 */
	public function test_validate_audio_format_invalid() {
		$content = '<p>Just text content.</p>';

		$result = $this->analyzer->validate_for_format( $content, 'audio', '' );

		$this->assertFalse( $result['valid'] );
	}

	/**
	 * Test validation for link format - invalid
	 */
	public function test_validate_link_format_invalid() {
		$content = '<p>No links in this content.</p>';

		$result = $this->analyzer->validate_for_format( $content, 'link', '' );

		$this->assertFalse( $result['valid'] );
	}

	/**
	 * Test validation warnings for status format
	 */
	public function test_validate_status_format_warnings() {
		$content = '<p>' . str_repeat( 'This is a really long status update. ', 20 ) . '</p>';

		$result = $this->analyzer->validate_for_format( $content, 'status', 'My Title' );

		$this->assertTrue( $result['valid'] );
		$this->assertNotEmpty( $result['warnings'] );
	}

	/**
	 * Test suggest_format ability execution
	 */
	public function test_execute_suggest_format() {
		$input = array(
			'content' => '<blockquote>A wise quote</blockquote>',
			'title'   => '',
		);

		$result = $this->abilities->execute_suggest_format( $input );

		$this->assertArrayHasKey( 'suggested_format', $result );
		$this->assertArrayHasKey( 'confidence', $result );
		$this->assertArrayHasKey( 'reason', $result );
		$this->assertArrayHasKey( 'alternatives', $result );
	}

	/**
	 * Test suggest_format with empty content
	 */
	public function test_execute_suggest_format_empty() {
		$input = array(
			'content' => '',
		);

		$result = $this->abilities->execute_suggest_format( $input );

		$this->assertEquals( 'standard', $result['suggested_format'] );
		$this->assertEquals( 0, $result['confidence'] );
	}

	/**
	 * Test analyze_content ability execution
	 */
	public function test_execute_analyze_content() {
		$input = array(
			'content' => '<p>Short status update.</p>',
			'title'   => '',
		);

		$result = $this->abilities->execute_analyze_content( $input );

		$this->assertArrayHasKey( 'suggested_format', $result );
		$this->assertArrayHasKey( 'signals', $result );
		$this->assertArrayHasKey( 'scores', $result );
		$this->assertIsArray( $result['signals'] );
		$this->assertIsArray( $result['scores'] );
	}

	/**
	 * Test validate_format_content ability execution
	 */
	public function test_execute_validate_format_content() {
		$input = array(
			'content' => '<img src="test.jpg">',
			'format'  => 'image',
			'title'   => '',
		);

		$result = $this->abilities->execute_validate_format_content( $input );

		$this->assertArrayHasKey( 'valid', $result );
		$this->assertArrayHasKey( 'format', $result );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertArrayHasKey( 'warnings', $result );
		$this->assertEquals( 'image', $result['format'] );
	}

	/**
	 * Test get_format_signals ability execution
	 */
	public function test_execute_get_format_signals() {
		$input = array();

		$result = $this->abilities->execute_get_format_signals( $input );

		$this->assertArrayHasKey( 'signals', $result );
		$this->assertArrayHasKey( 'status', $result['signals'] );
		$this->assertArrayHasKey( 'quote', $result['signals'] );
		$this->assertArrayHasKey( 'image', $result['signals'] );
	}

	/**
	 * Test get_format_signals for specific format
	 */
	public function test_execute_get_format_signals_specific() {
		$input = array(
			'format' => 'video',
		);

		$result = $this->abilities->execute_get_format_signals( $input );

		$this->assertArrayHasKey( 'signals', $result );
		$this->assertArrayHasKey( 'video', $result['signals'] );
		$this->assertCount( 1, $result['signals'] );
	}

	/**
	 * Test alternatives are returned in analysis
	 */
	public function test_alternatives_returned() {
		$content = '<p>Short text</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertArrayHasKey( 'alternatives', $result );
		$this->assertIsArray( $result['alternatives'] );

		if ( ! empty( $result['alternatives'] ) ) {
			$alt = $result['alternatives'][0];
			$this->assertArrayHasKey( 'format', $alt );
			$this->assertArrayHasKey( 'confidence', $alt );
			$this->assertArrayHasKey( 'reason', $alt );
		}
	}

	/**
	 * Test signal weights filter
	 */
	public function test_signal_weights_filter() {
		add_filter(
			'pfbt_format_signal_weights',
			function ( $weights ) {
				$weights['custom'] = array(
					'custom_signal' => 100,
				);
				return $weights;
			}
		);

		$result = $this->abilities->execute_get_format_signals( array() );

		$this->assertArrayHasKey( 'custom', $result['signals'] );

		remove_all_filters( 'pfbt_format_signal_weights' );
	}

	/**
	 * Test format signals filter
	 */
	public function test_format_signals_filter() {
		add_filter(
			'pfbt_format_signals',
			function ( $signals ) {
				$signals['custom_signal'] = true;
				return $signals;
			}
		);

		$result = $this->analyzer->analyze( '<p>Test</p>', '' );

		$this->assertTrue( $result['signals']['custom_signal'] );

		remove_all_filters( 'pfbt_format_signals' );
	}

	/**
	 * Test permission callback
	 */
	public function test_can_read_posts_permission() {
		// Log in as subscriber.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( $this->abilities->can_read_posts() );
	}

	/**
	 * get-format-signals (and every other MCP ability, since they all
	 * share this permission_callback) must deny a logged-out caller.
	 * Regression test for get-format-signals having previously been
	 * registered with `permission_callback => '__return_true'`, which
	 * would make this assertion fail.
	 */
	public function test_can_read_posts_denies_anonymous() {
		wp_set_current_user( 0 );

		$this->assertFalse( $this->abilities->can_read_posts() );
	}

	/**
	 * Test get_analyzer method
	 */
	public function test_get_analyzer() {
		$analyzer = $this->abilities->get_analyzer();

		$this->assertInstanceOf( PFBT_Format_Analyzer::class, $analyzer );
	}

	/**
	 * Test aside format detection
	 */
	public function test_detect_aside_format() {
		$content = '<p>This is a brief aside or note.</p><p>Just a couple paragraphs of informal content that doesn\'t need a title.</p>';

		$result = $this->analyzer->analyze( $content, '' );

		// Should suggest aside or status based on signals.
		$this->assertContains(
			$result['suggested_format'],
			array( 'aside', 'status' )
		);
	}

	/**
	 * Test external link detection
	 */
	public function test_external_link_detection() {
		$content = '<p>Check out <a href="https://external-site.com/page">this external link</a>.</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertTrue( $result['signals']['external_link'] );
	}

	/**
	 * Test podcast link detection
	 */
	public function test_podcast_link_detection() {
		$content = '<p>Listen to <a href="https://podcasts.apple.com/podcast/123">my podcast</a>.</p>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertTrue( $result['signals']['podcast_link'] );
	}

	/**
	 * Test Spotify detection
	 */
	public function test_spotify_embed_detection() {
		$content = '<figure class="wp-block-embed wp-block-embed-spotify"><iframe src="https://open.spotify.com/embed/track/abc"></iframe></figure>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertTrue( $result['signals']['has_audio'] );
	}

	/**
	 * Test mixed media detection
	 */
	public function test_mixed_media_detection() {
		$content = '<img src="photo.jpg"><video src="video.mp4"></video>';

		$result = $this->analyzer->analyze( $content, '' );

		$this->assertTrue( $result['signals']['mixed_media'] );
	}

	/**
	 * Test confidence is between 0 and 100
	 */
	public function test_confidence_range() {
		$contents = array(
			'<p>Short</p>',
			'<blockquote>Quote</blockquote>',
			'<img src="test.jpg">',
			str_repeat( '<p>Long content paragraph. </p>', 50 ),
		);

		foreach ( $contents as $content ) {
			$result = $this->analyzer->analyze( $content, '' );
			$this->assertGreaterThanOrEqual( 0, $result['confidence'] );
			$this->assertLessThanOrEqual( 100, $result['confidence'] );
		}
	}
}
