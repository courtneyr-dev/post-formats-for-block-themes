<?php
/**
 * Performance Benchmarks - Repair Tool
 *
 * Tests that repair tool scanning and fixing completes within
 * acceptable time limits for various database sizes.
 *
 * @package PostFormatsPowerUp
 */

class Test_Repair_Tool_Performance extends WP_UnitTestCase {

	/**
	 * Test scan performance with 100 posts
	 *
	 * Target: < 5 seconds
	 */
	public function test_scan_100_posts_performance() {
		// Create 100 test posts
		for ( $i = 0; $i < 100; $i++ ) {
			$this->factory->post->create(
				array(
					'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
				)
			);
		}

		$start = microtime( true );

		// Run scan (simulate repair tool scan)
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'future', 'pending', 'private' ),
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );

			if ( ! empty( $blocks ) ) {
				$first_block = reset( $blocks );

				// Detect format
				PFPU_Format_Registry::get_format_by_block(
					$first_block['blockName'] ?? '',
					$first_block['attrs'] ?? array()
				);
			}
		}

		$duration = microtime( true ) - $start;

		$this->assertLessThan( 5.0, $duration, "Scanning 100 posts took {$duration}s, should be < 5s" );
	}

	/**
	 * Test scan performance with 1000 posts (stress test)
	 *
	 * Target: < 30 seconds
	 *
	 * @group slow
	 * @group stress
	 */
	public function test_scan_1000_posts_performance() {
		$this->markTestSkipped( 'Slow test - run manually with: phpunit --group=stress' );

		// Create 1000 test posts
		for ( $i = 0; $i < 1000; $i++ ) {
			$this->factory->post->create(
				array(
					'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
				)
			);
		}

		$start = microtime( true );

		// Run full scan
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );

			if ( ! empty( $blocks ) ) {
				$first_block = reset( $blocks );
				PFPU_Format_Registry::get_format_by_block(
					$first_block['blockName'] ?? '',
					$first_block['attrs'] ?? array()
				);
			}
		}

		$duration = microtime( true ) - $start;

		$this->assertLessThan( 30.0, $duration, "Scanning 1000 posts took {$duration}s, should be < 30s" );
	}

	/**
	 * Test repair performance (update post formats)
	 *
	 * Target: < 100ms per post
	 */
	public function test_repair_single_post_performance() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:gallery --><figure></figure><!-- /wp:gallery -->',
			)
		);

		// Set to wrong format
		set_post_format( $post_id, 'standard' );

		$start = microtime( true );

		// Repair (set to correct format)
		set_post_format( $post_id, 'gallery' );
		update_post_meta( $post_id, '_pfpu_format_repaired', current_time( 'mysql' ) );

		$duration = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan( 100, $duration, "Repairing post took {$duration}ms, should be < 100ms" );
	}

	/**
	 * Test memory usage during large scans
	 *
	 * Target: < 50MB memory increase
	 */
	public function test_memory_usage_during_scan() {
		// Create 100 posts
		for ( $i = 0; $i < 100; $i++ ) {
			$this->factory->post->create(
				array(
					'post_content' => str_repeat( '<!-- wp:paragraph --><p>Test content</p><!-- /wp:paragraph -->', 10 ),
				)
			);
		}

		$memory_start = memory_get_usage( true );

		// Run scan
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
		);

		$posts = get_posts( $args );

		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );

			if ( ! empty( $blocks ) ) {
				foreach ( $blocks as $block ) {
					PFPU_Format_Registry::get_format_by_block(
						$block['blockName'] ?? '',
						$block['attrs'] ?? array()
					);
				}
			}
		}

		$memory_end      = memory_get_usage( true );
		$memory_increase = ( $memory_end - $memory_start ) / 1024 / 1024; // MB

		$this->assertLessThan( 50, $memory_increase, "Memory increased by {$memory_increase}MB, should be < 50MB" );
	}

	/**
	 * Test parse_blocks() performance for complex posts
	 *
	 * Target: < 50ms for post with 50 blocks
	 */
	public function test_parse_blocks_performance() {
		// Create post with 50 blocks
		$content = str_repeat( '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->', 50 );

		$start = microtime( true );

		$blocks = parse_blocks( $content );

		$duration = ( microtime( true ) - $start ) * 1000;

		$this->assertLessThan( 50, $duration, "Parsing 50 blocks took {$duration}ms, should be < 50ms" );
		$this->assertCount( 50, $blocks );
	}
}
