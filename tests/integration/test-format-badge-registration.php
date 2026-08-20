<?php
/**
 * Regression guards for the format-badge block registration.
 *
 * PR #32 originally passed supports via register_block_type() args, which
 * core merges over block.json settings with a top-level array_merge — the
 * whole supports array from block.json was silently replaced. These tests
 * pin the registered block to whatever block.json declares.
 *
 * @package PostFormatsForBlockThemes
 */

class Test_Format_Badge_Registration extends WP_UnitTestCase {

	/**
	 * The registered block's supports must match block.json exactly.
	 *
	 * Guards against register_block_type() args clobbering metadata:
	 * any supports key passed in args replaces the entire block.json
	 * supports array, not just the one key.
	 */
	public function test_registered_supports_match_block_json() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'post-formats/format-badge' );
		$this->assertInstanceOf( WP_Block_Type::class, $block, 'format-badge block is not registered' );

		$metadata = wp_json_file_decode(
			PFBT_PLUGIN_DIR . 'blocks/format-badge/block.json',
			array( 'associative' => true )
		);
		$this->assertIsArray( $metadata['supports'] ?? null, 'block.json has no supports' );

		$this->assertSame(
			$metadata['supports'],
			$block->supports,
			'Registered supports diverge from block.json — check register_block_type() args for a supports override'
		);
	}

	/**
	 * The autoRegister flag (WP 7.0 PHP-only block registration) must stay
	 * set alongside a render callback, or the editor shows "your site
	 * doesn't include support for this block" wherever the badge is hooked.
	 */
	public function test_auto_register_flag_is_set() {
		$block = WP_Block_Type_Registry::get_instance()->get_registered( 'post-formats/format-badge' );
		$this->assertInstanceOf( WP_Block_Type::class, $block );
		$this->assertNotEmpty( $block->supports['autoRegister'] ?? null, 'supports.autoRegister is not set' );
		$this->assertNotEmpty( $block->render_callback, 'autoRegister requires a render callback' );
	}
}
