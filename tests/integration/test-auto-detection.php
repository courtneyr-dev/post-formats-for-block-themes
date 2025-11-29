<?php
/**
 * Integration tests for format auto-detection
 *
 * Tests the complete auto-detection workflow with WordPress.
 *
 * @package PostFormatsPowerUp
 */

class Test_Auto_Detection extends WP_UnitTestCase {

	/**
	 * Test gallery block triggers gallery format on save
	 */
	public function test_gallery_block_triggers_gallery_format() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:gallery {"linkTo":"none"} --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->',
			)
		);

		// Trigger save hooks
		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$format = get_post_format( $post_id );
		$this->assertEquals( 'gallery', $format );
	}

	/**
	 * Test quote block triggers quote format
	 */
	public function test_quote_block_triggers_quote_format() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Test quote</p></blockquote><!-- /wp:quote -->',
			)
		);

		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$format = get_post_format( $post_id );
		$this->assertEquals( 'quote', $format );
	}

	/**
	 * Test manual format selection overrides auto-detection
	 */
	public function test_manual_format_overrides_autodetection() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:gallery --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->',
			)
		);

		// Manually set to standard format
		set_post_format( $post_id, 'standard' );
		update_post_meta( $post_id, '_pfpu_manual_format', '1' );

		// Trigger auto-detection
		do_action( 'save_post', $post_id, get_post( $post_id ), true );

		// Should remain standard (manual override)
		$format = get_post_format( $post_id );
		$this->assertEquals( 'standard', $format );
	}

	/**
	 * Test empty post gets standard format
	 */
	public function test_empty_post_gets_standard_format() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '',
			)
		);

		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$format = get_post_format( $post_id );
		$this->assertFalse( $format ); // False means 'standard'
	}
}
