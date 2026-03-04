<?php
/**
 * Integration tests for Block Bindings Format Data Source
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

class Test_Block_Bindings_Formats extends WP_UnitTestCase {

	/**
	 * Block bindings instance
	 *
	 * @var PFBT_Block_Bindings_Formats
	 */
	private $bindings;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();
		$this->bindings = PFBT_Block_Bindings_Formats::instance();
	}

	/**
	 * Create a mock block instance with post context
	 *
	 * @param int $post_id Post ID to set in context.
	 * @return stdClass
	 */
	private function get_mock_block( $post_id ) {
		$block          = new stdClass();
		$block->context = array( 'postId' => $post_id );
		return $block;
	}

	/**
	 * Test binding source is registered
	 */
	public function test_source_registered() {
		$source = WP_Block_Bindings_Registry::get_instance()->get_registered( 'post-formats/format-data' );
		$this->assertNotNull( $source );
	}

	/**
	 * Test format_label returns human-readable name
	 */
	public function test_format_label_returns_human_name() {
		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'quote' );

		$result = $this->bindings->get_value(
			array( 'key' => 'format_label' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertEquals( 'Quote', $result );
	}

	/**
	 * Test has_format returns 'false' for standard posts
	 */
	public function test_has_format_returns_false_for_standard() {
		$post_id = $this->factory->post->create();

		$result = $this->bindings->get_value(
			array( 'key' => 'has_format' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertEquals( 'false', $result );
	}

	/**
	 * Test has_format returns 'true' for aside posts
	 */
	public function test_has_format_returns_true_for_aside() {
		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'aside' );

		$result = $this->bindings->get_value(
			array( 'key' => 'has_format' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertEquals( 'true', $result );
	}

	/**
	 * Test char_count returns string length of stripped content
	 */
	public function test_char_count_returns_string_length() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => 'Hello World',
			)
		);

		$result = $this->bindings->get_value(
			array( 'key' => 'char_count' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertEquals( '11', $result );
	}

	/**
	 * Test media_url extracts first URL from content
	 */
	public function test_media_url_extracts_first_url() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => 'Check this out: https://example.com/video.mp4 and more text.',
			)
		);

		$result = $this->bindings->get_value(
			array( 'key' => 'media_url' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertEquals( 'https://example.com/video.mp4', $result );
	}

	/**
	 * Test quote_attribution extracts cite element text
	 */
	public function test_quote_attribution_extracts_cite() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Some quote</p><cite>Author Name</cite></blockquote><!-- /wp:quote -->',
			)
		);

		$result = $this->bindings->get_value(
			array( 'key' => 'quote_attribution' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertEquals( 'Author Name', $result );
	}

	/**
	 * Test unknown key returns null
	 */
	public function test_unknown_key_returns_null() {
		$post_id = $this->factory->post->create();

		$result = $this->bindings->get_value(
			array( 'key' => 'nonexistent' ),
			$this->get_mock_block( $post_id ),
			'content'
		);

		$this->assertNull( $result );
	}
}
