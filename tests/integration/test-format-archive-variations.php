<?php
/**
 * Integration tests for the post format archive Query Loop variations.
 *
 * @package PostFormatsBlockThemes
 */

/**
 * Tests the editor asset and core Query Loop format filtering contract.
 */
class Test_Format_Archive_Variations extends WP_UnitTestCase {

	/**
	 * Dequeue the editor asset between tests.
	 */
	public function tear_down() {
		wp_dequeue_script( PFBT_Format_Archive_Variations::SCRIPT_HANDLE );

		parent::tear_down();
	}

	/**
	 * The editor asset is enqueued when its generated asset metadata exists.
	 */
	public function test_editor_asset_is_enqueued() {
		$this->assertFileExists( PFBT_PLUGIN_DIR . 'build/format-archive-variations.asset.php' );

		do_action( 'enqueue_block_editor_assets' );

		$this->assertTrue(
			wp_script_is( PFBT_Format_Archive_Variations::SCRIPT_HANDLE, 'enqueued' )
		);
	}

	/**
	 * Each JSON query preset renders only posts assigned to its format.
	 */
	public function test_variation_queries_filter_posts_by_format() {
		$posts = array();

		foreach ( array( 'quote', 'link', 'status', 'aside', 'standard' ) as $format ) {
			$post_id = self::factory()->post->create(
				array(
					'post_status' => 'publish',
					'post_title'  => 'PFBT format query ' . $format,
				)
			);

			if ( 'standard' !== $format ) {
				set_post_format( $post_id, $format );
			}

			$posts[ $format ] = get_the_title( $post_id );
		}

		foreach ( $this->get_variations() as $variation ) {
			$attributes = wp_json_encode(
				array(
					'namespace' => $variation['namespace'],
					'query'     => $variation['query'],
				)
			);
			$blocks     = sprintf(
				'<!-- wp:query %1$s --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title /--><!-- /wp:post-template --></div><!-- /wp:query -->',
				$attributes
			);
			$rendered   = do_blocks( $blocks );

			foreach ( $posts as $format => $title ) {
				if ( $variation['format'] === $format ) {
					$this->assertStringContainsString( $title, $rendered );
				} else {
					$this->assertStringNotContainsString( $title, $rendered );
				}
			}
		}
	}

	/**
	 * Read the shared variation definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_variations() {
		$json = file_get_contents( PFBT_PLUGIN_DIR . 'src/format-archive-variations/variations.json' );

		$this->assertNotFalse( $json );

		$variations = json_decode( $json, true );
		$this->assertIsArray( $variations );

		return $variations;
	}
}
