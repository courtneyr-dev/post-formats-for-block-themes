<?php
/**
 * Integration tests for Abilities API wiring
 *
 * Tests the integration between feature flags, abilities manager,
 * and core abilities for WP 7.0 compatibility.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

class Test_Abilities_Integration extends WP_UnitTestCase {

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
		require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-core-abilities.php';
		$this->abilities = PFBT_Core_Abilities::instance();
	}

	/**
	 * Tear down test
	 */
	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Test abilities manager singleton returns same instance
	 */
	public function test_abilities_manager_singleton() {
		$instance1 = PFBT_Abilities_Manager::instance();
		$instance2 = PFBT_Abilities_Manager::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test feature flag controls abilities registration
	 */
	public function test_feature_flag_controls_abilities() {
		PFBT_Feature_Flags::set_flag( 'abilities_api', false );

		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'abilities_api' ) );

		PFBT_Feature_Flags::reset_flag( 'abilities_api' );
	}

	/**
	 * Test block bindings feature flag is enabled by default
	 */
	public function test_block_bindings_feature_flag_default_enabled() {
		$this->assertTrue( PFBT_Feature_Flags::is_enabled( 'block_bindings' ) );
	}

	/**
	 * Test block bindings feature flag can be disabled via option
	 */
	public function test_block_bindings_feature_flag_can_be_disabled() {
		PFBT_Feature_Flags::set_flag( 'block_bindings', false );

		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'block_bindings' ) );

		PFBT_Feature_Flags::reset_flag( 'block_bindings' );
	}

	/**
	 * Test set format fires pfbt_format_changed action with correct params
	 */
	public function test_set_format_fires_action() {
		$post_id = $this->factory->post->create();
		wp_set_current_user( 1 );

		$action_fired    = false;
		$received_params = array();

		add_action(
			'pfbt_format_changed',
			function ( $id, $old, $new ) use ( &$action_fired, &$received_params ) {
				$action_fired    = true;
				$received_params = array(
					'post_id'    => $id,
					'old_format' => $old,
					'new_format' => $new,
				);
			},
			10,
			3
		);

		$this->abilities->execute_set_post_format(
			array(
				'post_id' => $post_id,
				'format'  => 'aside',
			)
		);

		$this->assertTrue( $action_fired, 'pfbt_format_changed action should have fired.' );
		$this->assertEquals( $post_id, $received_params['post_id'] );
		$this->assertEquals( 'standard', $received_params['old_format'] );
		$this->assertEquals( 'aside', $received_params['new_format'] );
	}

	/**
	 * Test detect format and set format roundtrip
	 */
	public function test_detect_and_set_format_roundtrip() {
		$content = '<!-- wp:gallery --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->';

		$post_id = $this->factory->post->create(
			array(
				'post_content' => $content,
			)
		);

		wp_set_current_user( 1 );

		// Detect format from content.
		$detected = $this->abilities->execute_detect_format(
			array( 'content' => $content )
		);

		$this->assertIsArray( $detected );
		$this->assertEquals( 'gallery', $detected['detected_format'] );

		// Set the detected format on the post.
		$result = $this->abilities->execute_set_post_format(
			array(
				'post_id' => $post_id,
				'format'  => $detected['detected_format'],
			)
		);

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );

		// Verify format persisted correctly.
		$this->assertEquals( 'gallery', get_post_format( $post_id ) );
	}
}
