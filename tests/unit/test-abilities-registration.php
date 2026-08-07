<?php
/**
 * Unit tests for Abilities API Registration
 *
 * Tests the WordPress Abilities API integration including
 * feature flags, abilities manager, and core abilities.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

/**
 * Test Feature Flags
 *
 * @covers PFBT_Feature_Flags
 */
class Test_Feature_Flags extends WP_UnitTestCase {

	/**
	 * Test default feature flag values
	 */
	public function test_default_feature_flags() {
		// IndieWeb should be enabled by default.
		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'indieweb_integration' ) );

		// MCP should be enabled by default.
		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'mcp_integration' ) );

		// ActivityPub should be disabled by default.
		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'activitypub_integration' ) );

		// AI suggestions should be enabled by default.
		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'ai_suggestions' ) );

		// Abilities API should be enabled by default.
		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'abilities_api' ) );
	}

	/**
	 * Test option override for feature flags
	 */
	public function test_option_override() {
		// Set option to disable IndieWeb ('0' as a settings form would store;
		// a raw false is indistinguishable from a missing option in WP).
		update_option( 'pfbt_feature_indieweb_integration', '0' );

		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'indieweb_integration' ) );

		// Clean up.
		delete_option( 'pfbt_feature_indieweb_integration' );
	}

	/**
	 * Test filter override for feature flags
	 */
	public function test_filter_override() {
		// Add filter to enable ActivityPub.
		add_filter( 'pfbt_feature_activitypub_integration', '__return_true' );

		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'activitypub_integration' ) );

		// Remove filter.
		remove_filter( 'pfbt_feature_activitypub_integration', '__return_true' );
	}

	/**
	 * Test get_all_flags returns all flags with status
	 */
	public function test_get_all_flags() {
		$flags = PFBT_Feature_Flags::get_all_flags();

		$this->assertIsArray( $flags );
		$this->assertArrayHasKey( 'indieweb_integration', $flags );
		$this->assertArrayHasKey( 'mcp_integration', $flags );
		$this->assertArrayHasKey( 'activitypub_integration', $flags );
		$this->assertArrayHasKey( 'abilities_api', $flags );

		// Check structure of each flag.
		foreach ( $flags as $flag_name => $flag_data ) {
			$this->assertArrayHasKey( 'enabled', $flag_data );
			$this->assertArrayHasKey( 'default', $flag_data );
			$this->assertArrayHasKey( 'source', $flag_data );
		}
	}

	/**
	 * Test set_flag and reset_flag
	 */
	public function test_set_and_reset_flag() {
		// Set a flag.
		$result = PFBT_Feature_Flags::set_flag( 'mcp_integration', false );
		$this->assertTrue( $result );
		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'mcp_integration' ) );

		// Reset the flag.
		PFBT_Feature_Flags::reset_flag( 'mcp_integration' );
		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'mcp_integration' ) );
	}

	/**
	 * Test invalid flag returns false
	 */
	public function test_invalid_flag_returns_false() {
		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'nonexistent_flag' ) );
	}

	/**
	 * Test has_indieweb convenience method
	 */
	public function test_has_indieweb() {
		$this->assertTrue( PFBT_Feature_Flags::has_indieweb() );
	}

	/**
	 * Test has_mcp convenience method
	 */
	public function test_has_mcp() {
		$this->assertTrue( PFBT_Feature_Flags::has_mcp() );
	}

	/**
	 * Test has_activitypub when plugin not active
	 */
	public function test_has_activitypub_without_plugin() {
		// Should be false even if flag is enabled, because plugin is not active.
		add_filter( 'pfbt_feature_activitypub_integration', '__return_true' );

		$this->assertFalse( PFBT_Feature_Flags::has_activitypub() );

		remove_filter( 'pfbt_feature_activitypub_integration', '__return_true' );
	}
}

/**
 * Test Abilities Manager
 *
 * @covers PFBT_Abilities_Manager
 */
class Test_Abilities_Manager extends WP_UnitTestCase {

	/**
	 * Test abilities manager is singleton
	 */
	public function test_singleton_instance() {
		$instance1 = PFBT_Abilities_Manager::instance();
		$instance2 = PFBT_Abilities_Manager::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test category slug constant
	 */
	public function test_category_slug_constant() {
		$this->assertEquals( 'post-formats', PFBT_Abilities_Manager::CATEGORY_SLUG );
	}

	/**
	 * Test is_available static method
	 */
	public function test_is_available() {
		// This depends on whether wp_register_ability exists.
		$expected = function_exists( 'wp_register_ability' );
		$this->assertEquals( $expected, PFBT_Abilities_Manager::is_available() );
	}
}

/**
 * Test Core Abilities
 *
 * Note: These tests mock the Abilities API functions if not available.
 *
 * @covers PFBT_Core_Abilities
 */
class Test_Core_Abilities extends WP_UnitTestCase {

	/**
	 * Core abilities instance
	 *
	 * @var PFBT_Core_Abilities
	 */
	private $abilities;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();
		// The plugin only loads abilities classes when wp_register_ability
		// exists (see PFBT_Feature_Flags::has_abilities_api()); load directly
		// here like test-mcp-abilities.php does so the class under test exists.
		require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-core-abilities.php';
		$this->abilities = PFBT_Core_Abilities::instance();
	}

	/**
	 * Test core abilities is singleton
	 */
	public function test_singleton_instance() {
		$instance1 = PFBT_Core_Abilities::instance();
		$instance2 = PFBT_Core_Abilities::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test namespace constant
	 *
	 * Dashes, not underscores — core rejects any ability name that doesn't match
	 * /^[a-z0-9-]+\/[a-z0-9-]+$/, and the namespace is half of that name.
	 */
	public function test_namespace_constant() {
		$this->assertEquals( 'post-formats', PFBT_Core_Abilities::NAMESPACE );
	}

	/**
	 * Test execute_list_formats returns correct structure
	 */
	public function test_execute_list_formats() {
		$result = $this->abilities->execute_list_formats( array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'formats', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertCount( 10, $result['formats'] );
		$this->assertEquals( 10, $result['total'] );
	}

	/**
	 * Test execute_list_formats includes templates
	 */
	public function test_execute_list_formats_with_templates() {
		$result = $this->abilities->execute_list_formats(
			array( 'include_templates' => true )
		);

		$first_format = $result['formats'][0];
		$this->assertArrayHasKey( 'template', $first_format );
		$this->assertArrayHasKey( 'pattern', $first_format );
	}

	/**
	 * Test execute_list_formats includes counts
	 */
	public function test_execute_list_formats_with_counts() {
		$result = $this->abilities->execute_list_formats(
			array( 'include_counts' => true )
		);

		$first_format = $result['formats'][0];
		$this->assertArrayHasKey( 'post_count', $first_format );
		$this->assertIsInt( $first_format['post_count'] );
	}

	/**
	 * Test execute_get_format_template with valid format
	 */
	public function test_execute_get_format_template_valid() {
		$result = $this->abilities->execute_get_format_template(
			array( 'format' => 'quote' )
		);

		$this->assertIsArray( $result );
		$this->assertEquals( 'quote', $result['format'] );
		$this->assertEquals( 'single-format-quote', $result['template_slug'] );
		$this->assertEquals( 'pfpu/quote', $result['pattern_name'] );
		$this->assertEquals( 'core/quote', $result['first_block'] );
	}

	/**
	 * Test execute_get_format_template with invalid format
	 */
	public function test_execute_get_format_template_invalid() {
		$result = $this->abilities->execute_get_format_template(
			array( 'format' => 'nonexistent' )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_format', $result->get_error_code() );
	}

	/**
	 * Test execute_validate_format with valid content
	 */
	public function test_execute_validate_format_valid() {
		$content = '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Test quote</p></blockquote><!-- /wp:quote -->';

		$result = $this->abilities->execute_validate_format(
			array(
				'format'  => 'quote',
				'content' => $content,
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['valid'] );
		$this->assertEmpty( $result['messages'] );
	}

	/**
	 * Test execute_validate_format with invalid content
	 */
	public function test_execute_validate_format_invalid() {
		$content = '<!-- wp:paragraph --><p>This is not a quote block</p><!-- /wp:paragraph -->';

		$result = $this->abilities->execute_validate_format(
			array(
				'format'  => 'quote',
				'content' => $content,
			)
		);

		$this->assertIsArray( $result );
		$this->assertFalse( $result['valid'] );
		$this->assertNotEmpty( $result['messages'] );
	}

	/**
	 * Test execute_validate_format for status character limit
	 */
	public function test_execute_validate_format_status_char_limit() {
		// Content over 280 characters.
		$long_content = '<!-- wp:paragraph {"className":"status-paragraph"} --><p class="status-paragraph">' . str_repeat( 'a', 300 ) . '</p><!-- /wp:paragraph -->';

		$result = $this->abilities->execute_validate_format(
			array(
				'format'  => 'status',
				'content' => $long_content,
			)
		);

		$this->assertIsArray( $result );
		// Still valid (soft limit), but should have message.
		$this->assertNotEmpty( $result['messages'] );
	}

	/**
	 * Test execute_score_format with quote content
	 */
	public function test_execute_score_format_quote() {
		$content = '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Test quote</p></blockquote><!-- /wp:quote -->';

		$result = $this->abilities->execute_score_format(
			array( 'content' => $content )
		);

		$this->assertIsArray( $result );
		$this->assertEquals( 'quote', $result['detected_format'] );
		$this->assertEquals( 'core/quote', $result['first_block'] );
		$this->assertIsArray( $result['signals'] );
	}

	/**
	 * Test execute_score_format with gallery content
	 */
	public function test_execute_score_format_gallery() {
		$content = '<!-- wp:gallery --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->';

		$result = $this->abilities->execute_score_format(
			array( 'content' => $content )
		);

		$this->assertEquals( 'gallery', $result['detected_format'] );
	}

	/**
	 * Test execute_score_format with empty content
	 */
	public function test_execute_score_format_empty() {
		$result = $this->abilities->execute_score_format(
			array( 'content' => '' )
		);

		$this->assertEquals( 'standard', $result['detected_format'] );
		$this->assertContains( 'no_blocks_found', $result['signals'] );
	}

	/**
	 * Test execute_set_post_format
	 */
	public function test_execute_set_post_format() {
		// Create a test post.
		$post_id = $this->factory->post->create();

		// Set format as admin.
		wp_set_current_user( 1 );

		$result = $this->abilities->execute_set_post_format(
			array(
				'post_id' => $post_id,
				'format'  => 'quote',
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertEquals( $post_id, $result['post_id'] );
		$this->assertEquals( 'quote', $result['format'] );
		$this->assertEquals( 'standard', $result['previous_format'] );

		// Verify format was actually set.
		$this->assertEquals( 'quote', get_post_format( $post_id ) );

		// Verify manual flag was set.
		$this->assertTrue( (bool) get_post_meta( $post_id, '_pfbt_format_manual', true ) );
	}

	/**
	 * Test execute_set_post_format with invalid post
	 */
	public function test_execute_set_post_format_invalid_post() {
		wp_set_current_user( 1 );

		$result = $this->abilities->execute_set_post_format(
			array(
				'post_id' => 999999,
				'format'  => 'quote',
			)
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_post', $result->get_error_code() );
	}

	/**
	 * Test execute_get_post_format
	 */
	public function test_execute_get_post_format() {
		// Create a test post with quote format.
		$post_id = $this->factory->post->create();
		set_post_format( $post_id, 'quote' );

		$result = $this->abilities->execute_get_post_format(
			array( 'post_id' => $post_id )
		);

		$this->assertIsArray( $result );
		$this->assertEquals( $post_id, $result['post_id'] );
		$this->assertEquals( 'quote', $result['format'] );
		$this->assertEquals( 'Quote', $result['format_name'] );
		$this->assertEquals( 'single-format-quote', $result['template_slug'] );
	}

	/**
	 * Test execute_get_post_format with standard format
	 */
	public function test_execute_get_post_format_standard() {
		$post_id = $this->factory->post->create();

		$result = $this->abilities->execute_get_post_format(
			array( 'post_id' => $post_id )
		);

		$this->assertEquals( 'standard', $result['format'] );
	}

	/**
	 * Test execute_get_post_format with invalid post
	 */
	public function test_execute_get_post_format_invalid() {
		$result = $this->abilities->execute_get_post_format(
			array( 'post_id' => 999999 )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_post', $result->get_error_code() );
	}

	/**
	 * Test pfbt_format_changed action fires
	 */
	public function test_format_changed_action_fires() {
		$post_id = $this->factory->post->create();
		wp_set_current_user( 1 );

		$action_fired    = false;
		$received_params = array();

		add_action(
			'pfbt_format_changed',
			function ( $id, $old, $new ) use ( &$action_fired, &$received_params ) {
				$action_fired    = true;
				$received_params = array( $id, $old, $new );
			},
			10,
			3
		);

		$this->abilities->execute_set_post_format(
			array(
				'post_id' => $post_id,
				'format'  => 'gallery',
			)
		);

		$this->assertTrue( $action_fired );
		$this->assertEquals( $post_id, $received_params[0] );
		$this->assertEquals( 'standard', $received_params[1] );
		$this->assertEquals( 'gallery', $received_params[2] );
	}
}
