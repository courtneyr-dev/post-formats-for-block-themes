<?php
/**
 * Integration tests for the Chat Log Interactivity API contract.
 *
 * @package PostFormatsForBlockThemes
 */

class Test_Chatlog_Interactivity extends WP_UnitTestCase {

	/**
	 * Transcript with two consecutive thread replies.
	 *
	 * @var string
	 */
	private const TRANSCRIPT = "Alice  10:00 AM\nHello there\nBob  10:01 AM (in thread)\nReply one\nCarol  10:02 AM (in thread)\nReply two\nAlice  10:03 AM\nBack in main";

	/**
	 * The block must register only its script module on the front end.
	 */
	public function test_block_registers_only_the_view_script_module() {
		$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'chatlog/conversation' );

		$this->assertInstanceOf( WP_Block_Type::class, $block_type );
		$this->assertEmpty( $block_type->view_script_handles );
		$this->assertNotEmpty( $block_type->view_script_module_ids );
		$this->assertTrue( $block_type->supports['interactivity']['interactive'] ?? false );
	}

	/**
	 * The generated asset manifest must identify the module dependency.
	 */
	public function test_view_asset_declares_interactivity_module_dependency() {
		$asset = require PFBT_CHATLOG_PLUGIN_DIR . 'build/view-interactivity.asset.php';
		$ids   = array_map(
			static function ( $dependency ) {
				return is_array( $dependency ) ? ( $dependency['id'] ?? null ) : $dependency;
			},
			$asset['dependencies'] ?? array()
		);

		$this->assertContains( '@wordpress/interactivity', $ids );
	}

	/**
	 * Core must process initial expanded and collapsed directive state.
	 */
	public function test_rendered_thread_state_is_processed_server_side() {
		$expanded = $this->render_chatlog();
		$this->assertSame( 'true', $this->get_thread_toggle_attribute( $expanded, 'aria-expanded' ) );
		$this->assertNull( $this->get_thread_list_attribute( $expanded, 'hidden' ) );

		$collapsed = $this->render_chatlog( true );
		$this->assertSame( 'false', $this->get_thread_toggle_attribute( $collapsed, 'aria-expanded' ) );
		$this->assertTrue( $this->get_thread_list_attribute( $collapsed, 'hidden' ) );
	}

	/**
	 * Render the fixture through the block parser and renderer.
	 *
	 * @param bool $collapse_threads Whether threads start collapsed.
	 * @return string
	 */
	private function render_chatlog( $collapse_threads = false ) {
		$attributes = array(
			'rawTranscript'   => self::TRANSCRIPT,
			'collapseThreads' => $collapse_threads,
		);

		return do_blocks(
			sprintf(
				'<!-- wp:chatlog/conversation %s /-->',
				wp_json_encode( $attributes )
			)
		);
	}

	/**
	 * Read an attribute from the thread toggle.
	 *
	 * @param string $html      Rendered block markup.
	 * @param string $attribute Attribute name.
	 * @return string|bool|null
	 */
	private function get_thread_toggle_attribute( $html, $attribute ) {
		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'chatlog-thread-toggle' ) ) );

		return $processor->get_attribute( $attribute );
	}

	/**
	 * Read an attribute from the thread list.
	 *
	 * @param string $html      Rendered block markup.
	 * @param string $attribute Attribute name.
	 * @return string|bool|null
	 */
	private function get_thread_list_attribute( $html, $attribute ) {
		$processor = new WP_HTML_Tag_Processor( $html );
		$this->assertTrue( $processor->next_tag( array( 'class_name' => 'chatlog-thread' ) ) );

		return $processor->get_attribute( $attribute );
	}
}
