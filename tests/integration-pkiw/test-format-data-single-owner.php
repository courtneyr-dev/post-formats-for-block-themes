<?php
/**
 * Integration coverage for PFBT and PKIW sharing a block bindings source.
 *
 * @package PostFormatsBlockThemes
 */

/**
 * Test that PFBT keeps ownership when both plugins load.
 */
class Test_Format_Data_Single_Owner extends WP_UnitTestCase {

	/**
	 * PKIW's block bindings component.
	 *
	 * @var PKIW\Block_Bindings_Formats
	 */
	private $pkiw_bindings;

	/**
	 * Resolve the PKIW-created block bindings component.
	 */
	public function set_up() {
		parent::set_up();

		$this->pkiw_bindings = PKIW\Plugin::get_instance()->get_block_bindings_formats();
	}

	/**
	 * PKIW loads its real source component and hooks its registration callback.
	 */
	public function test_pkiw_loaded_and_hooked_its_source() {
		$this->assertTrue( defined( 'PKIW_VERSION' ) );
		$this->assertTrue( class_exists( 'PKIW\Block_Bindings_Formats' ) );
		$this->assertInstanceOf( 'PKIW\Block_Bindings_Formats', $this->pkiw_bindings );
		$this->assertNotFalse( has_action( 'init', array( $this->pkiw_bindings, 'register_source' ) ) );
	}

	/**
	 * PFBT owns the shared source without a duplicate-registration notice.
	 */
	public function test_pfbt_owns_source_without_incorrect_usage() {
		$source   = $this->get_format_data_source();
		$callback = $this->get_value_callback( $source );

		$this->assertIsArray( $callback );
		$this->assertInstanceOf( 'PFBT_Block_Bindings_Formats', $callback[0] );
		$this->assertSame( array(), $this->get_format_data_incorrect_usage() );
	}

	/**
	 * PKIW can retry registration without replacing PFBT or raising a notice.
	 */
	public function test_pkiw_registration_retry_keeps_owner_without_incorrect_usage() {
		$source_before   = $this->get_format_data_source();
		$callback_before = $this->get_value_callback( $source_before );
		$notices_before  = $this->get_format_data_incorrect_usage();

		$this->pkiw_bindings->register_source();

		$source_after   = $this->get_format_data_source();
		$callback_after = $this->get_value_callback( $source_after );

		$this->assertSame( $source_before, $source_after );
		$this->assertSame( $callback_before[0], $callback_after[0] );
		$this->assertSame( $notices_before, $this->get_format_data_incorrect_usage() );
	}

	/**
	 * Bound paragraph content still resolves with both plugins active.
	 *
	 * Either provider returns the same label, so this checks that content
	 * keeps working; test_pfbt_owns_source_without_incorrect_usage() checks
	 * which plugin owns the source.
	 */
	public function test_format_label_binding_renders_with_both_plugins() {
		$post_id = self::factory()->post->create();
		set_post_format( $post_id, 'quote' );

		$content = '<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"post-formats/format-data","args":{"key":"format_label"}}}}} --><p></p><!-- /wp:paragraph -->';
		$blocks  = parse_blocks( $content );
		$block   = new WP_Block(
			$blocks[0],
			array(
				'postId'   => $post_id,
				'postType' => 'post',
			)
		);

		$this->assertStringContainsString( 'Quote', $block->render() );
	}

	/**
	 * Get the registered shared source.
	 *
	 * @return WP_Block_Bindings_Source
	 */
	private function get_format_data_source() {
		$source = WP_Block_Bindings_Registry::get_instance()->get_registered( 'post-formats/format-data' );

		$this->assertInstanceOf( 'WP_Block_Bindings_Source', $source );

		return $source;
	}

	/**
	 * Read the callback stored by WordPress's source value object.
	 *
	 * @param WP_Block_Bindings_Source $source Registered source.
	 * @return callable
	 */
	private function get_value_callback( $source ) {
		$property = new ReflectionProperty( $source, 'get_value_callback' );

		return $property->getValue( $source );
	}

	/**
	 * Return duplicate source notices captured since bootstrap.
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	private function get_format_data_incorrect_usage() {
		return array_values(
			array_filter(
				$GLOBALS['pfbt_pkiw_doing_it_wrong'],
				static function ( $notice ) {
					return 'WP_Block_Bindings_Registry::register' === $notice[0]
						|| false !== strpos( $notice[1], 'post-formats/format-data' );
				}
			)
		);
	}
}
