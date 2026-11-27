<?php
/**
 * Integration tests for the Repair Tool's format-only undo.
 *
 * @package PostFormatsForBlockThemes
 */

class Test_Repair_Undo extends WP_UnitTestCase {

	/**
	 * Create a published Standard post whose lead block suggests Audio.
	 *
	 * @return int Post ID.
	 */
	private function create_mismatch() {
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:audio --><figure class="wp-block-audio"><audio controls src="sample.mp3"></audio></figure><!-- /wp:audio -->',
			)
		);
		set_post_format( $post_id, 'standard' );

		return $post_id;
	}

	/**
	 * Exercise the callback targets without the admin request redirect.
	 *
	 * @param string $method Repair Tool method name.
	 * @param array  $args   Method arguments.
	 * @return mixed Method result.
	 */
	private function repair_tool( $method, ...$args ) {
		$callback = new ReflectionMethod( PFBT_Repair_Tool::class, $method );
		return $callback->invokeArgs( null, $args );
	}

	/**
	 * Undo removes Audio archive membership while preserving a body edit.
	 */
	public function test_undo_restores_standard_and_preserves_body_edit() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$post_id = $this->create_mismatch();

		$this->assertTrue( $this->repair_tool( 'apply_format_change', $post_id, 'audio', false ) );
		$repair = get_post_meta( $post_id, '_pfbt_format_repair_undo', true );
		$this->assertSame( 'standard', $repair['previous'] ?? null );
		$this->assertSame( 'audio', get_post_format( $post_id ) );

		$archive = array(
			'fields'      => 'ids',
			'post_status' => 'publish',
			'post_format' => 'post-format-audio',
		);
		$this->assertContains( $post_id, get_posts( $archive ) );

		$body = get_post_field( 'post_content', $post_id ) . '<!-- wp:paragraph --><p>Independent body edit.</p><!-- /wp:paragraph -->';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $body,
			)
		);

		$this->assertTrue( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
		$this->assertFalse( get_post_format( $post_id ) );
		$this->assertNotContains( $post_id, get_posts( $archive ) );
		$this->assertSame( $body, get_post_field( 'post_content', $post_id ) );
		$this->assertFalse( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
	}

	/**
	 * A dry run and an unauthorized or stale request leave the post unchanged.
	 */
	public function test_undo_refuses_unauthorized_and_stale_requests() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$post_id = $this->create_mismatch();

		$this->assertTrue( $this->repair_tool( 'apply_format_change', $post_id, 'audio', true ) );
		$this->assertFalse( get_post_format( $post_id ) );
		$this->assertFalse( metadata_exists( 'post', $post_id, '_pfbt_format_repair_undo' ) );

		$this->assertTrue( $this->repair_tool( 'apply_format_change', $post_id, 'audio', false ) );
		$repair = get_post_meta( $post_id, '_pfbt_format_repair_undo', true );
		$this->assertSame( 'audio', $repair['applied'] ?? null );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertFalse( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
		$this->assertSame( 'audio', get_post_format( $post_id ) );

		wp_set_current_user( $admin_id );
		$deny_post = static function ( $caps, $cap, $user_id, $args ) use ( $post_id ) {
			return 'edit_post' === $cap && $post_id === ( $args[0] ?? null ) ? array( 'do_not_allow' ) : $caps;
		};
		add_filter( 'map_meta_cap', $deny_post, 10, 4 );
		try {
			$this->assertFalse( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
			$this->assertSame( 'audio', get_post_format( $post_id ) );
		} finally {
			remove_filter( 'map_meta_cap', $deny_post, 10 );
		}

		set_post_format( $post_id, 'video' );
		$this->assertFalse( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
		$this->assertSame( 'video', get_post_format( $post_id ) );

		set_post_format( $post_id, 'audio' );
		$this->assertFalse( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
		$this->assertSame( 'audio', get_post_format( $post_id ) );

		set_post_format( $post_id, 'standard' );
		$this->assertTrue( $this->repair_tool( 'apply_format_change', $post_id, 'audio', false ) );
		$this->assertFalse( $this->repair_tool( 'undo_format_change', $post_id, $repair['token'] ) );
		$this->assertSame( 'audio', get_post_format( $post_id ) );
	}

	/**
	 * The command shares the Tools page capability and points to scan results.
	 */
	public function test_command_registration_requires_tools_capability() {
		$scripts  = wp_scripts();
		$handle   = 'wp-core-commands';
		$original = $scripts->get_data( $handle, 'after' );
		$enqueued = wp_script_is( $handle, 'enqueued' );
		wp_enqueue_script( $handle );

		try {
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
			pfbt_enqueue_repair_review_command();
			$this->assertSame( $original, $scripts->get_data( $handle, 'after' ) );

			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
			pfbt_enqueue_repair_review_command();
			$script = implode( "\n", $scripts->get_data( $handle, 'after' ) );
			$this->assertStringContainsString( 'tools.php?page=' . PFBT_Repair_Tool::PAGE_SLUG . '#pfbt-repair-scan-results', $script );
		} finally {
			$scripts->add_data( $handle, 'after', $original );
			if ( ! $enqueued ) {
				wp_dequeue_script( $handle );
			}
		}
	}
}
