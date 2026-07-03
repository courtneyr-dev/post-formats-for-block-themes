<?php
/**
 * Integration tests for the Post Kinds integration
 *
 * Covers the init-deferred active check, the default-on kind/format
 * automations, the reconciled kind↔format maps, and format detection for
 * PKIW kind-card first blocks.
 *
 * @package PostFormatsBlockThemes
 */

class Test_Post_Kinds_Integration extends WP_UnitTestCase {

	/**
	 * Integration instance under test
	 *
	 * @var PFBT_Post_Kinds_Integration|null
	 */
	private $integration = null;

	/**
	 * Default kinds registered by Post Kinds for IndieWeb.
	 *
	 * Mirrors PostKindsForIndieWeb\Taxonomy::$default_kinds.
	 *
	 * @var string[]
	 */
	private const PKIW_KINDS = array(
		'note',
		'article',
		'reply',
		'like',
		'repost',
		'bookmark',
		'rsvp',
		'checkin',
		'listen',
		'watch',
		'read',
		'event',
		'photo',
		'video',
		'review',
		'favorite',
		'jam',
		'wish',
		'mood',
		'acquisition',
		'drink',
		'eat',
		'recipe',
		'play',
	);

	/**
	 * Register the kind taxonomy the way PKIW does, seed its terms, and
	 * build a fresh integration instance against that state.
	 */
	public function set_up() {
		parent::set_up();

		register_taxonomy(
			'kind',
			array( 'post' ),
			array(
				'hierarchical' => false,
				'public'       => true,
			)
		);

		foreach ( self::PKIW_KINDS as $slug ) {
			wp_insert_term( $slug, 'kind' );
		}

		$this->integration = $this->fresh_integration();
	}

	/**
	 * Remove hooks the fresh instance registered and reset global state.
	 */
	public function tear_down() {
		if ( $this->integration ) {
			remove_action( 'init', array( $this->integration, 'register_kind_meta' ), 20 );
			remove_filter( 'pfbt_editor_script_data', array( $this->integration, 'add_kind_mapping_to_editor' ) );
			remove_action( 'set_object_terms', array( $this->integration, 'suggest_kind_on_format_terms' ) );
			remove_action( 'set_object_terms', array( $this->integration, 'suggest_format_on_kind_change' ) );
			remove_action( 'rest_api_init', array( $this->integration, 'register_rest_routes' ) );
			$this->integration = null;
		}

		self::reset_singleton();

		if ( taxonomy_exists( 'kind' ) ) {
			unregister_taxonomy( 'kind' );
		}

		if ( WP_Block_Type_Registry::get_instance()->is_registered( 'post-kinds-indieweb/listen-card' ) ) {
			unregister_block_type( 'post-kinds-indieweb/listen-card' );
		}

		parent::tear_down();
	}

	/**
	 * Null the singleton so the next instance() call re-runs the active check.
	 */
	private static function reset_singleton() {
		$prop = new ReflectionProperty( PFBT_Post_Kinds_Integration::class, 'instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	/**
	 * Build a fresh integration instance against current taxonomy state.
	 *
	 * @return PFBT_Post_Kinds_Integration
	 */
	private function fresh_integration() {
		self::reset_singleton();
		return PFBT_Post_Kinds_Integration::instance();
	}

	/**
	 * The kind taxonomy alone marks the integration active — the PKIW-only
	 * install case that previously returned false at after_setup_theme.
	 */
	public function test_active_with_kind_taxonomy_only() {
		$this->assertTrue( $this->integration->is_post_kinds_active() );
	}

	/**
	 * Hooks register when the integration is active.
	 */
	public function test_hooks_register_when_active() {
		$this->assertNotFalse( has_action( 'set_object_terms', array( $this->integration, 'suggest_kind_on_format_terms' ) ) );
		$this->assertNotFalse( has_action( 'set_object_terms', array( $this->integration, 'suggest_format_on_kind_change' ) ) );
		$this->assertNotFalse( has_action( 'rest_api_init', array( $this->integration, 'register_rest_routes' ) ) );
	}

	/**
	 * No hooks register when nothing kind-shaped is installed.
	 */
	public function test_no_hooks_without_post_kinds() {
		// Detach the active instance so only the inactive one is observed.
		$this->tear_down_integration_hooks();
		unregister_taxonomy( 'kind' );

		$inactive = $this->fresh_integration();

		$this->assertFalse( $inactive->is_post_kinds_active() );
		$this->assertFalse( has_action( 'set_object_terms', array( $inactive, 'suggest_kind_on_format_terms' ) ) );
		$this->assertFalse( has_action( 'set_object_terms', array( $inactive, 'suggest_format_on_kind_change' ) ) );

		$this->integration = null;
	}

	/**
	 * Automations default on: the detector's standard classification on a
	 * fresh post flows through to the article kind with no filters set.
	 */
	public function test_new_standard_post_gets_article_kind() {
		$post_id = $this->factory->post->create( array( 'post_content' => '' ) );

		$this->assertSame( array( 'article' ), wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) ) );
	}

	/**
	 * Setting a format sets the mapped kind (format → kind direction).
	 *
	 * Core has no set_post_format action, so this exercises the
	 * set_object_terms/post_format routing end to end.
	 */
	public function test_setting_format_sets_mapped_kind() {
		$post_id = $this->factory->post->create( array( 'post_content' => '' ) );

		// Clear the kind the creation-time detection assigned.
		wp_set_post_terms( $post_id, array(), 'kind' );

		set_post_format( $post_id, 'audio' );

		$this->assertSame( array( 'listen' ), wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) ) );
	}

	/**
	 * Setting a kind by slug sets the mapped format (kind → format direction).
	 *
	 * wp_set_post_terms() passes slugs, not IDs, to set_object_terms — the
	 * handler must resolve the term from term-taxonomy IDs.
	 */
	public function test_setting_kind_by_slug_sets_mapped_format() {
		$post_id = $this->factory->post->create( array( 'post_content' => '' ) );

		wp_set_post_terms( $post_id, array( 'listen' ), 'kind' );

		$this->assertSame( 'audio', get_post_format( $post_id ) );
	}

	/**
	 * A deliberately chosen kind is not overridden by a later format change.
	 */
	public function test_existing_kind_not_overridden_by_format_change() {
		$post_id = $this->factory->post->create( array( 'post_content' => '' ) );

		wp_set_post_terms( $post_id, array( 'photo' ), 'kind' );
		set_post_format( $post_id, 'audio' );

		$this->assertSame( array( 'photo' ), wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) ) );
	}

	/**
	 * The kind → format map covers exactly PKIW's default kinds — no more
	 * (quotation and tag do not exist in PKIW), no less.
	 */
	public function test_kind_format_map_matches_pkiw_default_kinds() {
		$map = $this->integration->get_all_kind_format_mappings();

		$this->assertEqualsCanonicalizing( self::PKIW_KINDS, array_keys( $map ) );

		foreach ( $map as $kind => $format ) {
			$this->assertTrue(
				PFBT_Format_Registry::format_exists( $format ),
				"Kind '$kind' maps to unregistered format '$format'."
			);
		}
	}

	/**
	 * Every format maps to a kind PKIW actually registers.
	 */
	public function test_format_kind_map_targets_only_real_kinds() {
		$map = $this->integration->get_all_format_kind_mappings();

		$this->assertEqualsCanonicalizing( array_keys( PFBT_Format_Registry::get_all_formats() ), array_keys( $map ) );

		foreach ( $map as $format => $kind ) {
			$this->assertContains(
				$kind,
				self::PKIW_KINDS,
				"Format '$format' maps to nonexistent kind '$kind'."
			);
		}
	}

	/**
	 * A filtered mapping to an unregistered kind must not create a junk term.
	 */
	public function test_unknown_kind_slug_is_not_created() {
		$this->tear_down_integration_hooks();

		add_filter(
			'pfbt_format_kind_map',
			function ( $map ) {
				$map['chat'] = 'zzz-not-a-kind';
				return $map;
			}
		);

		$this->integration = $this->fresh_integration();

		$post_id = $this->factory->post->create( array( 'post_content' => '' ) );
		wp_set_post_terms( $post_id, array(), 'kind' );

		set_post_format( $post_id, 'chat' );

		$this->assertFalse( get_term_by( 'slug', 'zzz-not-a-kind', 'kind' ) );
		$this->assertSame( array(), wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) ) );

		remove_all_filters( 'pfbt_format_kind_map' );
	}

	/**
	 * A contentless registered dynamic kind card — what the Micropub builder
	 * writes — detects its format and cascades to the matching kind.
	 */
	public function test_contentless_listen_card_detects_audio_and_listen_kind() {
		register_block_type(
			'post-kinds-indieweb/listen-card',
			array( 'render_callback' => '__return_empty_string' )
		);

		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:post-kinds-indieweb/listen-card {"trackName":"Test Track"} /-->',
			)
		);

		$this->assertSame( 'audio', get_post_format( $post_id ) );
		$this->assertSame( 'audio', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ) );
		$this->assertSame( array( 'listen' ), wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) ) );
	}

	/**
	 * A contentless block that is NOT registered stays skipped, so the post
	 * falls back to standard — kind cards only count when PKIW provides them.
	 */
	public function test_contentless_unregistered_card_falls_back_to_standard() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:post-kinds-indieweb/wish-card /-->',
			)
		);

		$this->assertSame( 'standard', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ) );
	}

	/**
	 * Kind-card block names map to formats in the registry lookup.
	 *
	 * @dataProvider data_kind_card_formats
	 *
	 * @param string $block_name Kind-card block name.
	 * @param string $expected   Expected format slug.
	 */
	public function test_kind_card_block_maps_to_format( $block_name, $expected ) {
		$this->assertSame( $expected, PFBT_Format_Registry::get_format_by_block( $block_name ) );
	}

	/**
	 * Data provider: kind-card block → expected format.
	 *
	 * @return array<string, array{string, string}>
	 */
	public function data_kind_card_formats() {
		return array(
			'listen card is audio'   => array( 'post-kinds-indieweb/listen-card', 'audio' ),
			'watch card is video'    => array( 'post-kinds-indieweb/watch-card', 'video' ),
			'mood card is status'    => array( 'post-kinds-indieweb/mood-card', 'status' ),
			'eat card is status'     => array( 'post-kinds-indieweb/eat-card', 'status' ),
			'drink card is status'   => array( 'post-kinds-indieweb/drink-card', 'status' ),
			'checkin card is status' => array( 'post-kinds-indieweb/checkin-card', 'status' ),
			'bookmark card is link'  => array( 'post-kinds-indieweb/bookmark-card', 'link' ),
			'like card is aside'     => array( 'post-kinds-indieweb/like-card', 'aside' ),
		);
	}

	/**
	 * Detach the hooks belonging to the current integration instance.
	 */
	private function tear_down_integration_hooks() {
		if ( ! $this->integration ) {
			return;
		}

		remove_action( 'init', array( $this->integration, 'register_kind_meta' ), 20 );
		remove_filter( 'pfbt_editor_script_data', array( $this->integration, 'add_kind_mapping_to_editor' ) );
		remove_action( 'set_object_terms', array( $this->integration, 'suggest_kind_on_format_terms' ) );
		remove_action( 'set_object_terms', array( $this->integration, 'suggest_format_on_kind_change' ) );
		remove_action( 'rest_api_init', array( $this->integration, 'register_rest_routes' ) );
	}
}
