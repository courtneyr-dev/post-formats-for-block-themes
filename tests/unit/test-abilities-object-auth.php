<?php
/**
 * Unit tests for object-level authorization on post-bound abilities
 *
 * A permission_callback registered with wp_register_ability() only proves
 * the caller holds some sitewide capability; it says nothing about the
 * specific post the caller supplied as input. These tests confirm the
 * shared pfbt_ability_post_or_error() guard closes that gap on every
 * post-bound execute callback.
 *
 * @package PostFormatsBlockThemes
 * @since 1.1.7
 */

/**
 * Test object-level authorization on post-bound abilities
 *
 * @covers PFBT_IndieWeb_Abilities
 * @covers PFBT_Core_Abilities
 */
class Test_Abilities_Object_Auth extends WP_UnitTestCase {

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'PFBT_Format_Mf2' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/mf2/class-pfbt-format-mf2.php';
		}
		if ( ! class_exists( 'PFBT_Posse_Transformer' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/posse/class-pfbt-posse-transformer.php';
		}
		if ( ! class_exists( 'PFBT_Webmention_Context' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/webmention/class-pfbt-webmention-context.php';
		}
		if ( ! class_exists( 'PFBT_IndieWeb_Abilities' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-indieweb-abilities.php';
		}
		if ( ! class_exists( 'PFBT_Core_Abilities' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-core-abilities.php';
		}
	}

	/**
	 * A subscriber must not be able to read a private post's content
	 * through mf2-markup just because they can pass its post_id.
	 */
	public function test_subscriber_cannot_read_private_post_through_mf2_markup() {
		$author  = self::factory()->user->create( array( 'role' => 'author' ) );
		$private = self::factory()->post->create(
			array(
				'post_status'  => 'private',
				'post_author'  => $author,
				'post_content' => 'secret body',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_markup( array( 'post_id' => $private ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
		$this->assertStringNotContainsString( 'secret body', wp_json_encode( $result ) );
	}

	/**
	 * A contributor must not be able to prepare another user's draft
	 * for POSSE syndication just because they can pass its post_id.
	 */
	public function test_contributor_cannot_prepare_posse_for_another_users_draft() {
		$draft = self::factory()->post->create(
			array(
				'post_status'  => 'draft',
				'post_author'  => 1,
				'post_content' => 'draft body',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_posse_prepare( array( 'post_id' => $draft ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
	}

	/**
	 * A non-'post' object (e.g. a synced pattern's wp_block) must be
	 * rejected even for a user with full capabilities.
	 */
	public function test_non_post_types_are_rejected() {
		wp_set_current_user( 1 );
		$block = self::factory()->post->create(
			array(
				'post_type'   => 'wp_block',
				'post_status' => 'publish',
			)
		);

		$abilities = PFBT_Core_Abilities::instance();
		$result    = $abilities->execute_get_post_format( array( 'post_id' => $block ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_not_found', $result->get_error_code() );
	}
}
