<?php
/**
 * Unit tests for IndieWeb Integration
 *
 * Tests microformats2 generation, POSSE preparation,
 * and webmention context functionality.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

/**
 * Test Microformats2 Generator
 *
 * @covers PFBT_Format_Mf2
 */
class Test_Format_Mf2 extends WP_UnitTestCase {

	/**
	 * Mf2 instance
	 *
	 * @var PFBT_Format_Mf2
	 */
	private $mf2;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();

		// Ensure the class is loaded.
		if ( ! class_exists( 'PFBT_Format_Mf2' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/mf2/class-pfbt-format-mf2.php';
		}

		$this->mf2 = PFBT_Format_Mf2::instance();
	}

	/**
	 * Test all formats have mf2 mapping
	 */
	public function test_all_formats_have_mf2_mapping() {
		$formats = array(
			'standard',
			'aside',
			'status',
			'quote',
			'link',
			'image',
			'gallery',
			'video',
			'audio',
			'chat',
		);

		foreach ( $formats as $format ) {
			$mf2 = $this->mf2->get_format_mf2( $format );
			$this->assertIsArray( $mf2 );
			$this->assertArrayHasKey( 'entry_class', $mf2 );
			$this->assertArrayHasKey( 'content_class', $mf2 );
		}
	}

	/**
	 * Test standard format mf2 classes
	 */
	public function test_standard_format_mf2() {
		$mf2 = $this->mf2->get_format_mf2( 'standard' );

		$this->assertEquals( 'h-entry', $mf2['entry_class'] );
		$this->assertEquals( 'e-content', $mf2['content_class'] );
		$this->assertEquals( 'p-name', $mf2['name_class'] );
	}

	/**
	 * Test aside format uses p-note
	 */
	public function test_aside_format_uses_p_note() {
		$mf2 = $this->mf2->get_format_mf2( 'aside' );

		$this->assertStringContainsString( 'p-note', $mf2['content_class'] );
		$this->assertEquals( '', $mf2['name_class'] );
	}

	/**
	 * Test status format uses p-note
	 */
	public function test_status_format_uses_p_note() {
		$mf2 = $this->mf2->get_format_mf2( 'status' );

		$this->assertStringContainsString( 'p-note', $mf2['content_class'] );
	}

	/**
	 * Test quote format has h-cite
	 */
	public function test_quote_format_has_h_cite() {
		$mf2 = $this->mf2->get_format_mf2( 'quote' );

		$this->assertStringContainsString( 'h-cite', $mf2['entry_class'] );
		$this->assertArrayHasKey( 'quote_class', $mf2 );
	}

	/**
	 * Test image format has u-photo
	 */
	public function test_image_format_has_u_photo() {
		$mf2 = $this->mf2->get_format_mf2( 'image' );

		$this->assertArrayHasKey( 'photo_class', $mf2 );
		$this->assertEquals( 'u-photo', $mf2['photo_class'] );
	}

	/**
	 * Test video format has u-video
	 */
	public function test_video_format_has_u_video() {
		$mf2 = $this->mf2->get_format_mf2( 'video' );

		$this->assertArrayHasKey( 'video_class', $mf2 );
		$this->assertEquals( 'u-video', $mf2['video_class'] );
	}

	/**
	 * Test audio format has u-audio
	 */
	public function test_audio_format_has_u_audio() {
		$mf2 = $this->mf2->get_format_mf2( 'audio' );

		$this->assertArrayHasKey( 'audio_class', $mf2 );
		$this->assertEquals( 'u-audio', $mf2['audio_class'] );
	}

	/**
	 * Test link format has bookmark class
	 */
	public function test_link_format_has_bookmark() {
		$mf2 = $this->mf2->get_format_mf2( 'link' );

		$this->assertArrayHasKey( 'bookmark_class', $mf2 );
		$this->assertStringContainsString( 'u-bookmark-of', $mf2['bookmark_class'] );
	}

	/**
	 * Test generate mf2 markup for post
	 */
	public function test_generate_mf2_markup() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content here.',
			)
		);

		$markup = $this->mf2->generate_mf2_markup( $post_id );

		$this->assertIsArray( $markup );
		$this->assertArrayHasKey( 'format', $markup );
		$this->assertArrayHasKey( 'entry_class', $markup );
		$this->assertArrayHasKey( 'properties', $markup );

		// Check properties.
		$props = $markup['properties'];
		$this->assertArrayHasKey( 'name', $props );
		$this->assertArrayHasKey( 'content', $props );
		$this->assertArrayHasKey( 'published', $props );
		$this->assertArrayHasKey( 'url', $props );
		$this->assertArrayHasKey( 'author', $props );
	}

	/**
	 * Test mf2 markup includes author h-card
	 */
	public function test_mf2_includes_author_hcard() {
		$post_id = $this->factory->post->create();

		$markup = $this->mf2->generate_mf2_markup( $post_id );

		$author = $markup['properties']['author'][0];
		$this->assertIsArray( $author );
		$this->assertContains( 'h-card', $author['type'] );
		$this->assertArrayHasKey( 'properties', $author );
	}

	/**
	 * Test validate mf2 returns valid result
	 */
	public function test_validate_mf2() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content.',
			)
		);

		$result = $this->mf2->validate_mf2( $post_id );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'valid', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['valid'] );
	}

	/**
	 * Test image format extracts photos
	 */
	public function test_image_format_extracts_photos() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<img src="https://example.com/photo.jpg" alt="Test">',
			)
		);
		set_post_format( $post_id, 'image' );

		$markup = $this->mf2->generate_mf2_markup( $post_id );

		$this->assertArrayHasKey( 'photo', $markup['properties'] );
		$this->assertContains( 'https://example.com/photo.jpg', $markup['properties']['photo'] );
	}

	/**
	 * Test get entry class helper
	 */
	public function test_get_entry_class() {
		$this->assertEquals( 'h-entry', $this->mf2->get_entry_class( 'standard' ) );
		$this->assertStringContainsString( 'h-cite', $this->mf2->get_entry_class( 'quote' ) );
	}

	/**
	 * Test get content class helper
	 */
	public function test_get_content_class() {
		$this->assertEquals( 'e-content', $this->mf2->get_content_class( 'standard' ) );
		$this->assertStringContainsString( 'p-note', $this->mf2->get_content_class( 'aside' ) );
	}

	/**
	 * Test empty/false format returns standard
	 */
	public function test_empty_format_returns_standard() {
		$mf2_false = $this->mf2->get_format_mf2( false );
		$mf2_empty = $this->mf2->get_format_mf2( '' );
		$mf2_standard = $this->mf2->get_format_mf2( 'standard' );

		$this->assertEquals( $mf2_standard, $mf2_false );
		$this->assertEquals( $mf2_standard, $mf2_empty );
	}
}

/**
 * Test POSSE Transformer
 *
 * @covers PFBT_Posse_Transformer
 */
class Test_Posse_Transformer extends WP_UnitTestCase {

	/**
	 * POSSE transformer instance
	 *
	 * @var PFBT_Posse_Transformer
	 */
	private $posse;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'PFBT_Posse_Transformer' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/posse/class-pfbt-posse-transformer.php';
		}

		$this->posse = PFBT_Posse_Transformer::instance();
	}

	/**
	 * Test get targets returns array
	 */
	public function test_get_targets_returns_array() {
		$targets = $this->posse->get_targets();

		$this->assertIsArray( $targets );
		$this->assertNotEmpty( $targets );
	}

	/**
	 * Test default targets include common platforms
	 */
	public function test_default_targets() {
		$targets = $this->posse->get_targets();
		$ids     = array_column( $targets, 'id' );

		$this->assertContains( 'twitter', $ids );
		$this->assertContains( 'mastodon', $ids );
		$this->assertContains( 'bluesky', $ids );
	}

	/**
	 * Test target has required properties
	 */
	public function test_target_has_required_properties() {
		$target = $this->posse->get_target( 'twitter' );

		$this->assertIsArray( $target );
		$this->assertArrayHasKey( 'id', $target );
		$this->assertArrayHasKey( 'name', $target );
		$this->assertArrayHasKey( 'char_limit', $target );
		$this->assertArrayHasKey( 'media_types', $target );
	}

	/**
	 * Test Twitter has 280 char limit
	 */
	public function test_twitter_char_limit() {
		$target = $this->posse->get_target( 'twitter' );

		$this->assertEquals( 280, $target['char_limit'] );
	}

	/**
	 * Test Mastodon has 500 char limit
	 */
	public function test_mastodon_char_limit() {
		$target = $this->posse->get_target( 'mastodon' );

		$this->assertEquals( 500, $target['char_limit'] );
	}

	/**
	 * Test prepare returns content
	 */
	public function test_prepare_returns_content() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content for POSSE.',
			)
		);

		$prepared = $this->posse->prepare( get_post( $post_id ) );

		$this->assertIsArray( $prepared );
		$this->assertArrayHasKey( 'text', $prepared );
		$this->assertArrayHasKey( 'url', $prepared );
		$this->assertArrayHasKey( 'char_count', $prepared );
	}

	/**
	 * Test prepare for specific target
	 */
	public function test_prepare_for_target() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content.',
			)
		);

		$prepared = $this->posse->prepare( get_post( $post_id ), array( 'twitter' ) );

		$this->assertIsArray( $prepared );
		$this->assertArrayHasKey( 'twitter', $prepared );
		$this->assertArrayHasKey( 'target', $prepared['twitter'] );
		$this->assertEquals( 'twitter', $prepared['twitter']['target'] );
	}

	/**
	 * Test status format uses content directly
	 */
	public function test_status_format_content() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => '',
				'post_content' => 'Short status update for testing.',
			)
		);
		set_post_format( $post_id, 'status' );

		$prepared = $this->posse->prepare( get_post( $post_id ) );

		$this->assertStringContainsString( 'Short status update', $prepared['text'] );
	}

	/**
	 * Test long content is truncated
	 */
	public function test_long_content_truncation() {
		$long_content = str_repeat( 'This is a very long post content. ', 50 );
		$post_id      = $this->factory->post->create(
			array(
				'post_title'   => 'Long Post',
				'post_content' => $long_content,
			)
		);

		$prepared = $this->posse->prepare( get_post( $post_id ), array( 'twitter' ) );

		$this->assertLessThanOrEqual( 280, $prepared['twitter']['char_count'] );
		$this->assertTrue( $prepared['twitter']['valid'] );
	}

	/**
	 * Test media extraction from image post
	 */
	public function test_media_extraction_image() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<img src="https://example.com/image.jpg" alt="Test">',
			)
		);
		set_post_format( $post_id, 'image' );

		$prepared = $this->posse->prepare( get_post( $post_id ) );

		$this->assertNotEmpty( $prepared['media'] );
		$this->assertEquals( 'image', $prepared['media'][0]['type'] );
	}

	/**
	 * Test invalid post returns empty
	 */
	public function test_invalid_post_returns_empty() {
		$prepared = $this->posse->prepare( null );

		$this->assertEmpty( $prepared );
	}
}

/**
 * Test Webmention Context
 *
 * @covers PFBT_Webmention_Context
 */
class Test_Webmention_Context extends WP_UnitTestCase {

	/**
	 * Webmention context instance
	 *
	 * @var PFBT_Webmention_Context
	 */
	private $context;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'PFBT_Webmention_Context' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/webmention/class-pfbt-webmention-context.php';
		}

		$this->context = PFBT_Webmention_Context::instance();
	}

	/**
	 * Test all formats have context
	 */
	public function test_all_formats_have_context() {
		$formats = array(
			'standard',
			'aside',
			'status',
			'quote',
			'link',
			'image',
			'gallery',
			'video',
			'audio',
			'chat',
		);

		foreach ( $formats as $format ) {
			$context = $this->context->get_context( $format );

			$this->assertIsArray( $context );
			$this->assertArrayHasKey( 'send_as', $context );
			$this->assertArrayHasKey( 'accept_types', $context );
		}
	}

	/**
	 * Test standard format accepts all types
	 */
	public function test_standard_accepts_all_types() {
		$context = $this->context->get_context( 'standard' );

		$this->assertContains( 'like', $context['accept_types'] );
		$this->assertContains( 'repost', $context['accept_types'] );
		$this->assertContains( 'reply', $context['accept_types'] );
		$this->assertContains( 'mention', $context['accept_types'] );
	}

	/**
	 * Test quote format sends as quotation
	 */
	public function test_quote_sends_as_quotation() {
		$context = $this->context->get_context( 'quote' );

		$this->assertEquals( 'quotation', $context['send_as'] );
	}

	/**
	 * Test link format sends as bookmark
	 */
	public function test_link_sends_as_bookmark() {
		$context = $this->context->get_context( 'link' );

		$this->assertEquals( 'bookmark', $context['send_as'] );
	}

	/**
	 * Test image format sends as photo
	 */
	public function test_image_sends_as_photo() {
		$context = $this->context->get_context( 'image' );

		$this->assertEquals( 'photo', $context['send_as'] );
	}

	/**
	 * Test status format sends as note
	 */
	public function test_status_sends_as_note() {
		$context = $this->context->get_context( 'status' );

		$this->assertEquals( 'note', $context['send_as'] );
	}

	/**
	 * Test get_send_as helper
	 */
	public function test_get_send_as() {
		$this->assertEquals( 'mention', $this->context->get_send_as( 'standard' ) );
		$this->assertEquals( 'note', $this->context->get_send_as( 'aside' ) );
		$this->assertEquals( 'video', $this->context->get_send_as( 'video' ) );
	}

	/**
	 * Test accepts_type helper
	 */
	public function test_accepts_type() {
		$this->assertTrue( $this->context->accepts_type( 'standard', 'reply' ) );
		$this->assertTrue( $this->context->accepts_type( 'image', 'like' ) );
		$this->assertFalse( $this->context->accepts_type( 'link', 'repost' ) );
	}

	/**
	 * Test get display config
	 */
	public function test_get_display_config() {
		$config = $this->context->get_display_config( 'standard' );

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'format', $config );
		$this->assertArrayHasKey( 'sections', $config );
		$this->assertArrayHasKey( 'show_facepile', $config );
	}

	/**
	 * Test display config has expected sections
	 */
	public function test_display_config_sections() {
		$config = $this->context->get_display_config( 'standard' );

		$this->assertArrayHasKey( 'likes', $config['sections'] );
		$this->assertArrayHasKey( 'reposts', $config['sections'] );
		$this->assertArrayHasKey( 'replies', $config['sections'] );
	}

	/**
	 * Test empty format returns standard context
	 */
	public function test_empty_format_returns_standard() {
		$empty_context = $this->context->get_context( '' );
		$false_context = $this->context->get_context( false );
		$standard      = $this->context->get_context( 'standard' );

		$this->assertEquals( $standard['send_as'], $empty_context['send_as'] );
		$this->assertEquals( $standard['send_as'], $false_context['send_as'] );
	}

	/**
	 * Test get all contexts
	 */
	public function test_get_all_contexts() {
		$contexts = $this->context->get_all_contexts();

		$this->assertIsArray( $contexts );
		$this->assertCount( 10, $contexts );
		$this->assertArrayHasKey( 'standard', $contexts );
		$this->assertArrayHasKey( 'quote', $contexts );
	}
}

/**
 * Test IndieWeb Abilities
 *
 * @covers PFBT_IndieWeb_Abilities
 */
class Test_IndieWeb_Abilities extends WP_UnitTestCase {

	/**
	 * IndieWeb abilities instance
	 *
	 * @var PFBT_IndieWeb_Abilities
	 */
	private $abilities;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();

		// Load dependencies.
		if ( ! class_exists( 'PFBT_Format_Mf2' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/mf2/class-pfbt-format-mf2.php';
		}
		if ( ! class_exists( 'PFBT_Posse_Transformer' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/posse/class-pfbt-posse-transformer.php';
		}
		if ( ! class_exists( 'PFBT_Webmention_Context' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/webmention/class-pfbt-webmention-context.php';
		}
		if ( ! class_exists( 'PFBT_IndieWeb_Abilities' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-indieweb-abilities.php';
		}

		$this->abilities = PFBT_IndieWeb_Abilities::instance();
	}

	/**
	 * Test singleton
	 */
	public function test_singleton() {
		$instance1 = PFBT_IndieWeb_Abilities::instance();
		$instance2 = PFBT_IndieWeb_Abilities::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test execute_mf2_markup
	 */
	public function test_execute_mf2_markup() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Content here.',
			)
		);

		wp_set_current_user( 1 );

		$result = $this->abilities->execute_mf2_markup( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'format', $result );
		$this->assertArrayHasKey( 'entry_class', $result );
	}

	/**
	 * Test execute_mf2_markup with invalid post
	 */
	public function test_execute_mf2_markup_invalid_post() {
		$result = $this->abilities->execute_mf2_markup( array( 'post_id' => 999999 ) );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * Test execute_mf2_validate
	 */
	public function test_execute_mf2_validate() {
		$post_id = $this->factory->post->create();

		wp_set_current_user( 1 );

		$result = $this->abilities->execute_mf2_validate( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'valid', $result );
	}

	/**
	 * Test execute_posse_prepare
	 */
	public function test_execute_posse_prepare() {
		$post_id = $this->factory->post->create(
			array(
				'post_title'   => 'POSSE Test',
				'post_content' => 'Content for syndication.',
			)
		);

		wp_set_current_user( 1 );

		$result = $this->abilities->execute_posse_prepare( array( 'post_id' => $post_id ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'post_id', $result );
		$this->assertArrayHasKey( 'format', $result );
		$this->assertArrayHasKey( 'prepared', $result );
	}

	/**
	 * Test execute_posse_targets
	 */
	public function test_execute_posse_targets() {
		$result = $this->abilities->execute_posse_targets( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'targets', $result );
		$this->assertIsArray( $result['targets'] );
	}

	/**
	 * Test execute_webmention_context
	 */
	public function test_execute_webmention_context() {
		$result = $this->abilities->execute_webmention_context( array( 'format' => 'quote' ) );

		$this->assertIsArray( $result );
		$this->assertEquals( 'quote', $result['format'] );
		$this->assertEquals( 'quotation', $result['send_as'] );
	}
}
