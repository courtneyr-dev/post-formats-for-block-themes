<?php
/**
 * Performance Benchmarks - Format Detection
 *
 * Tests that format detection completes within acceptable time limits.
 *
 * @package PostFormatsPowerUp
 */

class Test_Format_Detection_Performance extends WP_UnitTestCase {

	/**
	 * Test format detection completes quickly for single post
	 *
	 * Target: < 10ms per post
	 */
	public function test_single_post_detection_performance() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:gallery --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->',
			)
		);

		$start = microtime( true );

		// Run detection
		$blocks      = parse_blocks( get_post( $post_id )->post_content );
		$first_block = reset( $blocks );
		$format      = PFPU_Format_Registry::get_format_by_block(
			$first_block['blockName'],
			$first_block['attrs'] ?? array()
		);

		$duration = ( microtime( true ) - $start ) * 1000; // Convert to ms

		$this->assertLessThan( 10, $duration, "Format detection took {$duration}ms, should be < 10ms" );
		$this->assertEquals( 'gallery', $format );
	}

	/**
	 * Test bulk detection performance
	 *
	 * Target: < 5 seconds for 100 posts
	 */
	public function test_bulk_detection_performance() {
		// Create 100 posts with different formats
		$post_ids = array();
		$formats  = array( 'gallery', 'quote', 'video', 'audio', 'image' );

		for ( $i = 0; $i < 100; $i++ ) {
			$format  = $formats[ $i % count( $formats ) ];
			$content = $this->get_sample_content_for_format( $format );

			$post_ids[] = $this->factory->post->create(
				array(
					'post_content' => $content,
				)
			);
		}

		$start = microtime( true );

		// Detect format for all posts
		foreach ( $post_ids as $post_id ) {
			$post   = get_post( $post_id );
			$blocks = parse_blocks( $post->post_content );

			if ( ! empty( $blocks ) ) {
				$first_block = reset( $blocks );
				PFPU_Format_Registry::get_format_by_block(
					$first_block['blockName'],
					$first_block['attrs'] ?? array()
				);
			}
		}

		$duration = microtime( true ) - $start;

		$this->assertLessThan( 5.0, $duration, "Bulk detection took {$duration}s, should be < 5s" );
	}

	/**
	 * Test pattern retrieval performance
	 *
	 * Target: < 5ms per pattern
	 */
	public function test_pattern_retrieval_performance() {
		$formats = PFPU_Format_Registry::get_all_formats();

		$total_time = 0;

		foreach ( array_keys( $formats ) as $format_slug ) {
			$start = microtime( true );

			$pattern = PFPU_Pattern_Manager::get_pattern( $format_slug );

			$duration    = ( microtime( true ) - $start ) * 1000;
			$total_time += $duration;

			$this->assertLessThan( 5, $duration, "Pattern retrieval for {$format_slug} took {$duration}ms" );
			$this->assertNotEmpty( $pattern );
		}

		$avg_time = $total_time / count( $formats );
		$this->assertLessThan( 3, $avg_time, "Average pattern retrieval: {$avg_time}ms" );
	}

	/**
	 * Test format registry initialization
	 *
	 * Target: < 50ms
	 */
	public function test_registry_initialization_performance() {
		// Force re-initialization by clearing static cache if exists
		$start = microtime( true );

		$formats = PFPU_Format_Registry::get_all_formats();

		$duration = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan( 50, $duration, "Registry init took {$duration}ms, should be < 50ms" );
		$this->assertCount( 10, $formats );
	}

	/**
	 * Helper: Generate sample content for format
	 *
	 * @param string $format Format slug.
	 * @return string Block content.
	 */
	private function get_sample_content_for_format( $format ) {
		$templates = array(
			'gallery' => '<!-- wp:gallery --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->',
			'quote'   => '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Test</p></blockquote><!-- /wp:quote -->',
			'video'   => '<!-- wp:video --><figure class="wp-block-video"><video controls></video></figure><!-- /wp:video -->',
			'audio'   => '<!-- wp:audio --><figure class="wp-block-audio"><audio controls></audio></figure><!-- /wp:audio -->',
			'image'   => '<!-- wp:image --><figure class="wp-block-image"><img src="" alt=""/></figure><!-- /wp:image -->',
		);

		return $templates[ $format ] ?? '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
	}
}
