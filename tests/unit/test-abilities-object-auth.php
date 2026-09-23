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

	/**
	 * A contributor must still be able to prepare their OWN draft for
	 * POSSE syndication — the guard checks the post, not the role.
	 */
	public function test_contributor_can_prepare_posse_for_own_draft() {
		$contributor = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$draft       = self::factory()->post->create(
			array(
				'post_status'  => 'draft',
				'post_author'  => $contributor,
				'post_content' => 'my own draft body',
			)
		);
		wp_set_current_user( $contributor );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_posse_prepare( array( 'post_id' => $draft ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'prepared', $result );
	}

	/**
	 * A contributor must still be able to validate mf2 markup on their
	 * OWN draft.
	 */
	public function test_contributor_can_validate_mf2_for_own_draft() {
		$contributor = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$draft       = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => $contributor,
			)
		);
		wp_set_current_user( $contributor );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_validate( array( 'post_id' => $draft ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'valid', $result );
	}

	/**
	 * A contributor must not be able to validate mf2 markup on another
	 * user's draft.
	 */
	public function test_contributor_cannot_validate_mf2_for_another_users_draft() {
		$draft = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => 1,
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_validate( array( 'post_id' => $draft ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
	}

	/**
	 * A subscriber must be able to read an ordinary published post
	 * through mf2-markup — the guard should not deny access it isn't
	 * meant to.
	 */
	public function test_subscriber_can_read_published_post_through_mf2_markup() {
		$published = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_content' => 'public body',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_markup( array( 'post_id' => $published ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'format', $result );
	}

	/**
	 * A subscriber must be able to read the format of an ordinary
	 * published post through get-post-format.
	 */
	public function test_subscriber_can_get_post_format_for_published_post() {
		$published = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$abilities = PFBT_Core_Abilities::instance();
		$result    = $abilities->execute_get_post_format( array( 'post_id' => $published ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'format', $result );
	}

	/**
	 * A contributor must not be able to read another user's draft's
	 * format through get-post-format. A draft is neither public nor
	 * 'private' status, so WordPress's own read_post mapping falls back
	 * to requiring edit rights on it — this confirms the guard doesn't
	 * accidentally loosen that.
	 */
	public function test_contributor_cannot_get_post_format_for_another_users_draft() {
		$draft = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_author' => 1,
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$abilities = PFBT_Core_Abilities::instance();
		$result    = $abilities->execute_get_post_format( array( 'post_id' => $draft ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
	}

	/**
	 * A subscriber must not be able to read a password-protected
	 * PUBLISHED post's content through mf2-markup. read_post maps to the
	 * primitive 'read' capability for any published post regardless of
	 * its password, so without an explicit check any logged-in user
	 * could bypass the password wall entirely.
	 */
	public function test_subscriber_cannot_read_password_protected_post_through_mf2_markup() {
		$protected = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_author'   => 1,
				'post_password' => 'secret123',
				'post_content'  => 'protected body',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_markup( array( 'post_id' => $protected ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
	}

	/**
	 * The post's own author, who can edit it, is exempt from the
	 * password check. post_password_required() itself has no such
	 * exemption; this mirrors WP_REST_Posts_Controller::can_access_
	 * password_content(), which grants password-protected content to
	 * anyone who holds edit_post on it in the REST API's 'edit' context.
	 */
	public function test_author_can_read_own_password_protected_post_through_mf2_markup() {
		$author    = self::factory()->user->create( array( 'role' => 'author' ) );
		$protected = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_author'   => $author,
				'post_password' => 'secret123',
			)
		);
		wp_set_current_user( $author );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_markup( array( 'post_id' => $protected ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'format', $result );
	}

	/**
	 * A contributor must not be able to prepare another user's PUBLISHED
	 * post for POSSE syndication.
	 *
	 * A published post matters here, not a draft: downgrading the
	 * guard's capability check on posse-prepare from 'edit_post' to
	 * 'read_post' would still pass every existing denial test in this
	 * file, because those all use another user's DRAFT — a non-public,
	 * non-'private' status, so core's own read_post mapping already
	 * falls back to requiring edit rights regardless of which of the two
	 * capabilities the guard asks for. A PUBLISHED post's read_post maps
	 * to the plain 'read' capability instead, which every logged-in
	 * role — including Contributor — holds. So only a published post
	 * actually distinguishes "correctly checks edit_post" from "was
	 * silently downgraded to read_post".
	 */
	public function test_contributor_cannot_prepare_posse_for_another_users_published_post() {
		$published = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_author' => 1,
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_posse_prepare( array( 'post_id' => $published ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
	}

	/**
	 * A contributor must not be able to validate mf2 markup for another
	 * user's PUBLISHED post. See the docblock above on the posse-prepare
	 * equivalent for why a published post, not a draft, is what actually
	 * pins the edit_post (vs. read_post) capability check on this
	 * ability.
	 */
	public function test_contributor_cannot_validate_mf2_for_another_users_published_post() {
		$published = self::factory()->post->create(
			array(
				'post_status' => 'publish',
				'post_author' => 1,
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'contributor' ) ) );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_validate( array( 'post_id' => $published ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
	}

	/**
	 * post_id = 0 must not fall back to resolving the global $post.
	 * get_post( 0 ) does exactly that, so the guard's own $post_id < 1
	 * check has to run before ever calling get_post(). Uses an admin
	 * caller deliberately: an admin CAN read the private post sitting in
	 * the global, so this only passes if the guard's id check runs at
	 * all — a subscriber caller would get 'pfbt_forbidden' either way
	 * and wouldn't distinguish the two code paths.
	 */
	public function test_post_id_zero_does_not_fall_back_to_global_post() {
		global $post;

		$original_global_post = $post;

		$private_post = self::factory()->post->create(
			array(
				'post_status' => 'private',
				'post_author' => 1,
			)
		);
		$post         = get_post( $private_post );
		wp_set_current_user( 1 );

		$abilities = PFBT_IndieWeb_Abilities::instance();
		$result    = $abilities->execute_mf2_markup( array( 'post_id' => 0 ) );

		$post = $original_global_post;

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_not_found', $result->get_error_code() );
	}

	/**
	 * The full round trip through the Abilities API — wp_get_ability()
	 * then ->execute(), rather than calling the execute_* method on the
	 * provider class directly — must still surface this plugin's own
	 * pfbt_forbidden error, not get swallowed into the framework's
	 * generic ability_invalid_permissions. WP_Ability::execute() only
	 * substitutes that generic error when check_permissions() itself
	 * (the ability-level permission_callback) denies; a WP_Error
	 * returned by the execute_callback — which is where this guard
	 * runs — passes straight through unchanged.
	 */
	public function test_mf2_markup_ability_denies_subscriber_on_private_post_via_wp_get_ability() {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available in this WordPress version.' );
		}

		if ( ! wp_has_ability( 'post-formats/mf2-markup' ) ) {
			do_action( 'wp_abilities_api_init' );
		}

		$ability = wp_get_ability( 'post-formats/mf2-markup' );
		$this->assertNotNull( $ability, 'post-formats/mf2-markup should be a registered ability.' );

		$private = self::factory()->post->create(
			array(
				'post_status'  => 'private',
				'post_author'  => 1,
				'post_content' => 'secret body',
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$result = $ability->execute( array( 'post_id' => $private ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'pfbt_forbidden', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}
}
