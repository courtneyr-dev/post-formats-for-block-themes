# Mobile MCP Abilities API Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Abilities API integration to Post Kinds for IndieWeb and Link Extension for XFN, enabling mobile posting via WordPress MCP adapter + Claude mobile.

**Architecture:** Each plugin registers its own abilities using the same pattern Post Formats already uses: singleton manager, category registration, provider classes. The WordPress MCP adapter discovers all abilities automatically.

**Tech Stack:** PHP 7.4+, WordPress 6.9+ Abilities API (`wp_register_ability`, `wp_register_ability_category`), PHPUnit 9.6, WP_UnitTestCase

---

## Part 1: Post Kinds for IndieWeb — Abilities API

**Repo:** `/Users/crobertson/Documents/Post Kinds for IndieWeb/`
**Namespace:** `PostKindsForIndieWeb\`
**Test command:** `composer test:unit`

### Task 1: Add Feature Flags Class

**Files:**
- Create: `includes/class-feature-flags.php`
- Test: `tests/phpunit/unit/FeatureFlagsTest.php`

**Step 1: Write the failing test**

```php
<?php
namespace PostKindsForIndieWeb\Tests\Unit;

use WP_UnitTestCase;
use PostKindsForIndieWeb\Feature_Flags;

class FeatureFlagsTest extends WP_UnitTestCase {

    public function tear_down(): void {
        delete_option( 'pkiw_feature_flags' );
        parent::tear_down();
    }

    public function test_defaults_return_expected_values(): void {
        $this->assertTrue( Feature_Flags::is_enabled( 'abilities_api' ) );
        $this->assertTrue( Feature_Flags::is_enabled( 'mcp_integration' ) );
    }

    public function test_unknown_flag_returns_false(): void {
        $this->assertFalse( Feature_Flags::is_enabled( 'nonexistent_flag' ) );
    }

    public function test_option_override(): void {
        update_option( 'pkiw_feature_flags', array( 'abilities_api' => false ) );
        $this->assertFalse( Feature_Flags::is_enabled( 'abilities_api' ) );
    }

    public function test_filter_override(): void {
        add_filter( 'pkiw_feature_flag_abilities_api', '__return_false' );
        $this->assertFalse( Feature_Flags::is_enabled( 'abilities_api' ) );
        remove_filter( 'pkiw_feature_flag_abilities_api', '__return_false' );
    }

    public function test_has_abilities_api_checks_function_exists(): void {
        // has_abilities_api returns false when wp_register_ability doesn't exist
        // In test env without Abilities API, this should be false
        $result = Feature_Flags::has_abilities_api();
        $this->assertIsBool( $result );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=FeatureFlagsTest`
Expected: FAIL — class not found

**Step 3: Write minimal implementation**

```php
<?php
declare( strict_types=1 );
namespace PostKindsForIndieWeb;

/**
 * Feature flags for optional integrations.
 */
final class Feature_Flags {

    private static array $defaults = array(
        'abilities_api'    => true,
        'mcp_integration'  => true,
    );

    public static function is_enabled( string $flag ): bool {
        if ( ! isset( self::$defaults[ $flag ] ) ) {
            return false;
        }

        if ( defined( 'PKIW_FLAG_' . strtoupper( $flag ) ) ) {
            return (bool) constant( 'PKIW_FLAG_' . strtoupper( $flag ) );
        }

        $filtered = apply_filters( "pkiw_feature_flag_{$flag}", null );
        if ( null !== $filtered ) {
            return (bool) $filtered;
        }

        $options = get_option( 'pkiw_feature_flags', array() );
        if ( isset( $options[ $flag ] ) ) {
            return (bool) $options[ $flag ];
        }

        return self::$defaults[ $flag ];
    }

    public static function has_abilities_api(): bool {
        return self::is_enabled( 'abilities_api' ) && function_exists( 'wp_register_ability' );
    }

    public static function has_mcp(): bool {
        return self::is_enabled( 'mcp_integration' ) && self::has_abilities_api();
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=FeatureFlagsTest`
Expected: PASS

**Step 5: Commit**

```bash
cd "/Users/crobertson/Documents/Post Kinds for IndieWeb"
git add includes/class-feature-flags.php tests/phpunit/unit/FeatureFlagsTest.php
git commit -m "feat: add feature flags for Abilities API integration"
```

---

### Task 2: Add Abilities Manager

**Files:**
- Create: `includes/class-abilities-manager.php`
- Test: `tests/phpunit/unit/AbilitiesManagerTest.php`

**Step 1: Write the failing test**

```php
<?php
namespace PostKindsForIndieWeb\Tests\Unit;

use WP_UnitTestCase;
use PostKindsForIndieWeb\Abilities_Manager;

class AbilitiesManagerTest extends WP_UnitTestCase {

    public function test_singleton_returns_same_instance(): void {
        $a = Abilities_Manager::instance();
        $b = Abilities_Manager::instance();
        $this->assertSame( $a, $b );
    }

    public function test_category_slug_constant(): void {
        $this->assertSame( 'post-kinds', Abilities_Manager::CATEGORY_SLUG );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=AbilitiesManagerTest`
Expected: FAIL — class not found

**Step 3: Write minimal implementation**

```php
<?php
declare( strict_types=1 );
namespace PostKindsForIndieWeb;

/**
 * Orchestrates Abilities API registration for Post Kinds.
 */
final class Abilities_Manager {

    public const CATEGORY_SLUG = 'post-kinds';

    private static ?Abilities_Manager $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init(): void {
        if ( ! Feature_Flags::has_abilities_api() ) {
            return;
        }
        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    public function register_category(): void {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
            return;
        }
        wp_register_ability_category(
            self::CATEGORY_SLUG,
            array(
                'label'       => __( 'Post Kinds', 'post-kinds-for-indieweb' ),
                'description' => __( 'Abilities for managing IndieWeb post kinds.', 'post-kinds-for-indieweb' ),
            )
        );
    }

    public function register_abilities(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }
        $this->register_core_abilities();
        $this->register_lookup_abilities();

        do_action( 'pkiw_abilities_registered', $this );
    }

    private function register_core_abilities(): void {
        $provider = new Abilities\Core_Abilities();
        $provider->register();
    }

    private function register_lookup_abilities(): void {
        $provider = new Abilities\Lookup_Abilities();
        $provider->register();
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=AbilitiesManagerTest`
Expected: PASS

**Step 5: Commit**

```bash
cd "/Users/crobertson/Documents/Post Kinds for IndieWeb"
git add includes/class-abilities-manager.php tests/phpunit/unit/AbilitiesManagerTest.php
git commit -m "feat: add Abilities Manager orchestrator"
```

---

### Task 3: Add Core Abilities Provider

**Files:**
- Create: `includes/abilities/class-core-abilities.php`
- Test: `tests/phpunit/unit/CoreAbilitiesTest.php`

**Step 1: Write the failing test**

```php
<?php
namespace PostKindsForIndieWeb\Tests\Unit;

use WP_UnitTestCase;
use PostKindsForIndieWeb\Abilities\Core_Abilities;

class CoreAbilitiesTest extends WP_UnitTestCase {

    private Core_Abilities $provider;

    public function set_up(): void {
        parent::set_up();
        $this->provider = new Core_Abilities();
    }

    public function test_execute_list_kinds_returns_all_25(): void {
        $result = $this->provider->execute_list_kinds( array() );
        $this->assertIsArray( $result['kinds'] );
        $this->assertSame( 25, $result['total'] );
    }

    public function test_execute_list_kinds_contains_note(): void {
        $result = $this->provider->execute_list_kinds( array() );
        $slugs  = array_column( $result['kinds'], 'slug' );
        $this->assertContains( 'note', $slugs );
    }

    public function test_execute_list_kind_fields_for_listen(): void {
        $result = $this->provider->execute_list_kind_fields( array( 'kind' => 'listen' ) );
        $this->assertIsArray( $result['fields'] );
        $names = array_column( $result['fields'], 'key' );
        $this->assertContains( 'listen_track', $names );
        $this->assertContains( 'listen_artist', $names );
        $this->assertContains( 'listen_album', $names );
    }

    public function test_execute_list_kind_fields_for_watch(): void {
        $result = $this->provider->execute_list_kind_fields( array( 'kind' => 'watch' ) );
        $names = array_column( $result['fields'], 'key' );
        $this->assertContains( 'watch_title', $names );
        $this->assertContains( 'watch_tmdb_id', $names );
    }

    public function test_execute_list_kind_fields_unknown_kind(): void {
        $result = $this->provider->execute_list_kind_fields( array( 'kind' => 'nonexistent' ) );
        $this->assertArrayHasKey( 'error', $result );
    }

    public function test_execute_create_post_with_listen_kind(): void {
        $result = $this->provider->execute_create_post( array(
            'kind'          => 'listen',
            'title'         => 'Test Listen',
            'content'       => '',
            'status'        => 'draft',
            'listen_track'  => 'Dreams',
            'listen_artist' => 'Fleetwood Mac',
            'listen_album'  => 'Rumours',
        ) );
        $this->assertArrayHasKey( 'post_id', $result );
        $this->assertGreaterThan( 0, $result['post_id'] );

        // Verify kind was assigned.
        $terms = wp_get_post_terms( $result['post_id'], 'kind', array( 'fields' => 'slugs' ) );
        $this->assertContains( 'listen', $terms );

        // Verify meta was set.
        $this->assertSame( 'Dreams', get_post_meta( $result['post_id'], '_postkind_listen_track', true ) );
        $this->assertSame( 'Fleetwood Mac', get_post_meta( $result['post_id'], '_postkind_listen_artist', true ) );
    }

    public function test_execute_set_kind(): void {
        $post_id = self::factory()->post->create();
        $result  = $this->provider->execute_set_kind( array(
            'post_id' => $post_id,
            'kind'    => 'bookmark',
        ) );
        $this->assertTrue( $result['success'] );

        $terms = wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) );
        $this->assertContains( 'bookmark', $terms );
    }

    public function test_execute_get_kind(): void {
        $post_id = self::factory()->post->create();
        wp_set_post_terms( $post_id, array( 'listen' ), 'kind' );

        $result = $this->provider->execute_get_kind( array( 'post_id' => $post_id ) );
        $this->assertSame( 'listen', $result['kind_slug'] );
    }

    public function test_execute_update_post_meta(): void {
        $post_id = self::factory()->post->create();
        $result  = $this->provider->execute_update_post_meta( array(
            'post_id'    => $post_id,
            'meta_key'   => 'listen_track',
            'meta_value' => 'Go Your Own Way',
        ) );
        $this->assertTrue( $result['success'] );
        $this->assertSame( 'Go Your Own Way', get_post_meta( $post_id, '_postkind_listen_track', true ) );
    }

    public function test_execute_get_post_meta(): void {
        $post_id = self::factory()->post->create();
        update_post_meta( $post_id, '_postkind_listen_track', 'The Chain' );
        update_post_meta( $post_id, '_postkind_listen_artist', 'Fleetwood Mac' );

        $result = $this->provider->execute_get_post_meta( array(
            'post_id'   => $post_id,
            'meta_keys' => array( 'listen_track', 'listen_artist' ),
        ) );
        $this->assertSame( 'The Chain', $result['meta']['listen_track'] );
        $this->assertSame( 'Fleetwood Mac', $result['meta']['listen_artist'] );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=CoreAbilitiesTest`
Expected: FAIL — class not found

**Step 3: Write minimal implementation**

```php
<?php
declare( strict_types=1 );
namespace PostKindsForIndieWeb\Abilities;

use PostKindsForIndieWeb\Abilities_Manager;
use PostKindsForIndieWeb\Meta_Fields;
use PostKindsForIndieWeb\Taxonomy;

/**
 * Core CRUD abilities for post kinds.
 */
class Core_Abilities {

    /**
     * Map of kind slugs to their meta field prefixes.
     */
    private const KIND_FIELD_MAP = array(
        'note'        => array(),
        'article'     => array(),
        'reply'       => array( 'cite_' ),
        'like'        => array( 'cite_' ),
        'repost'      => array( 'cite_' ),
        'bookmark'    => array( 'cite_', 'bookmark_' ),
        'rsvp'        => array( 'cite_', 'rsvp_' ),
        'checkin'     => array( 'checkin_', 'geo_' ),
        'listen'      => array( 'listen_' ),
        'watch'       => array( 'watch_' ),
        'read'        => array( 'read_' ),
        'event'       => array( 'event_' ),
        'photo'       => array(),
        'video'       => array(),
        'review'      => array( 'review_', 'cite_' ),
        'favorite'    => array( 'favorite_' ),
        'jam'         => array( 'jam_' ),
        'wish'        => array( 'wish_' ),
        'mood'        => array( 'mood_' ),
        'acquisition' => array( 'acquisition_' ),
        'drink'       => array( 'drink_' ),
        'eat'         => array( 'eat_' ),
        'recipe'      => array( 'recipe_' ),
        'play'        => array( 'play_' ),
    );

    public function register(): void {
        $this->register_list_kinds();
        $this->register_list_kind_fields();
        $this->register_create_post();
        $this->register_set_kind();
        $this->register_get_kind();
        $this->register_update_post_meta();
        $this->register_get_post_meta();
    }

    private function register_list_kinds(): void {
        wp_register_ability(
            'post-kinds/list-kinds',
            array(
                'label'               => __( 'List Post Kinds', 'post-kinds-for-indieweb' ),
                'description'         => __( 'List all available IndieWeb post kinds with descriptions.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => new \stdClass(),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'kinds' => array( 'type' => 'array' ),
                        'total' => array( 'type' => 'integer' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_list_kinds' ),
                'permission_callback' => function () {
                    return current_user_can( 'read' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    private function register_list_kind_fields(): void {
        wp_register_ability(
            'post-kinds/list-kind-fields',
            array(
                'label'               => __( 'List Kind Fields', 'post-kinds-for-indieweb' ),
                'description'         => __( 'List meta fields available for a specific post kind.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'kind' => array(
                            'type'        => 'string',
                            'description' => __( 'Kind slug (e.g., listen, watch, read).', 'post-kinds-for-indieweb' ),
                        ),
                    ),
                    'required'   => array( 'kind' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'kind'   => array( 'type' => 'string' ),
                        'fields' => array( 'type' => 'array' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_list_kind_fields' ),
                'permission_callback' => function () {
                    return current_user_can( 'read' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    private function register_create_post(): void {
        wp_register_ability(
            'post-kinds/create-post',
            array(
                'label'               => __( 'Create Kind Post', 'post-kinds-for-indieweb' ),
                'description'         => __( 'Create a post with a specific kind and kind-specific meta fields. Use list_kind_fields to discover accepted fields for each kind.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'kind'    => array( 'type' => 'string', 'description' => 'Kind slug.' ),
                        'title'   => array( 'type' => 'string', 'default' => '' ),
                        'content' => array( 'type' => 'string', 'default' => '' ),
                        'status'  => array( 'type' => 'string', 'default' => 'draft', 'enum' => array( 'draft', 'publish', 'private' ) ),
                    ),
                    'required'            => array( 'kind' ),
                    'additionalProperties' => true,
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_id'  => array( 'type' => 'integer' ),
                        'edit_url' => array( 'type' => 'string' ),
                        'view_url' => array( 'type' => 'string' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_create_post' ),
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    private function register_set_kind(): void {
        wp_register_ability(
            'post-kinds/set-kind',
            array(
                'label'               => __( 'Set Post Kind', 'post-kinds-for-indieweb' ),
                'description'         => __( 'Assign a kind to an existing post.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_id' => array( 'type' => 'integer' ),
                        'kind'    => array( 'type' => 'string' ),
                    ),
                    'required'   => array( 'post_id', 'kind' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'success' => array( 'type' => 'boolean' ),
                        'kind'    => array( 'type' => 'string' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_set_kind' ),
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    private function register_get_kind(): void {
        wp_register_ability(
            'post-kinds/get-kind',
            array(
                'label'               => __( 'Get Post Kind', 'post-kinds-for-indieweb' ),
                'description'         => __( 'Get the kind assigned to a post.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_id' => array( 'type' => 'integer' ),
                    ),
                    'required'   => array( 'post_id' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'kind_slug'        => array( 'type' => 'string' ),
                        'kind_label'       => array( 'type' => 'string' ),
                        'kind_description' => array( 'type' => 'string' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_get_kind' ),
                'permission_callback' => function () {
                    return current_user_can( 'read' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    private function register_update_post_meta(): void {
        wp_register_ability(
            'post-kinds/update-post-meta',
            array(
                'label'               => __( 'Update Kind Meta', 'post-kinds-for-indieweb' ),
                'description'         => __( 'Update a kind-specific meta field on a post. Field key should omit the _postkind_ prefix.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_id'    => array( 'type' => 'integer' ),
                        'meta_key'   => array( 'type' => 'string', 'description' => 'Field key without _postkind_ prefix.' ),
                        'meta_value' => array( 'description' => 'Value to set.' ),
                    ),
                    'required'   => array( 'post_id', 'meta_key', 'meta_value' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'success' => array( 'type' => 'boolean' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_update_post_meta' ),
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    private function register_get_post_meta(): void {
        wp_register_ability(
            'post-kinds/get-post-meta',
            array(
                'label'               => __( 'Get Kind Meta', 'post-kinds-for-indieweb' ),
                'description'         => __( 'Get kind-specific meta fields from a post. Omit meta_keys to get all kind meta.', 'post-kinds-for-indieweb' ),
                'category'            => Abilities_Manager::CATEGORY_SLUG,
                'input_schema'        => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_id'   => array( 'type' => 'integer' ),
                        'meta_keys' => array(
                            'type'  => 'array',
                            'items' => array( 'type' => 'string' ),
                            'description' => 'Field keys without _postkind_ prefix. Omit for all.',
                        ),
                    ),
                    'required'   => array( 'post_id' ),
                ),
                'output_schema'       => array(
                    'type'       => 'object',
                    'properties' => array(
                        'meta' => array( 'type' => 'object' ),
                    ),
                ),
                'execute_callback'    => array( $this, 'execute_get_post_meta' ),
                'permission_callback' => function () {
                    return current_user_can( 'read' );
                },
                'meta'                => array( 'show_in_rest' => true ),
            )
        );
    }

    // --- Execute callbacks ---

    public function execute_list_kinds( array $args ): array {
        $taxonomy = Taxonomy::instance();
        $terms    = $taxonomy->get_default_terms();
        $kinds    = array();

        foreach ( $terms as $slug => $description ) {
            $kinds[] = array(
                'slug'        => $slug,
                'label'       => ucfirst( $slug ),
                'description' => $description,
            );
        }

        return array(
            'kinds' => $kinds,
            'total' => count( $kinds ),
        );
    }

    public function execute_list_kind_fields( array $args ): array {
        $kind = $args['kind'] ?? '';

        if ( ! isset( self::KIND_FIELD_MAP[ $kind ] ) ) {
            return array( 'error' => sprintf( 'Unknown kind: %s', $kind ) );
        }

        $meta_fields = Meta_Fields::instance();
        $all_fields  = $meta_fields->get_fields();
        $prefixes    = self::KIND_FIELD_MAP[ $kind ];
        $fields      = array();

        foreach ( $all_fields as $key => $field ) {
            foreach ( $prefixes as $prefix ) {
                if ( str_starts_with( $key, $prefix ) ) {
                    $fields[] = array(
                        'key'         => $key,
                        'type'        => $field['type'],
                        'description' => $field['description'],
                        'default'     => $field['default'] ?? null,
                        'enum'        => $field['enum'] ?? null,
                    );
                    break;
                }
            }
        }

        return array(
            'kind'   => $kind,
            'fields' => $fields,
        );
    }

    public function execute_create_post( array $args ): array {
        $kind    = $args['kind'] ?? 'note';
        $title   = $args['title'] ?? '';
        $content = $args['content'] ?? '';
        $status  = $args['status'] ?? 'draft';

        $post_id = wp_insert_post(
            array(
                'post_title'   => sanitize_text_field( $title ),
                'post_content' => wp_kses_post( $content ),
                'post_status'  => $status,
                'post_type'    => 'post',
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return array( 'error' => $post_id->get_error_message() );
        }

        wp_set_post_terms( $post_id, array( $kind ), Taxonomy::TAXONOMY );

        // Set kind-specific meta from remaining args.
        $reserved = array( 'kind', 'title', 'content', 'status' );
        foreach ( $args as $key => $value ) {
            if ( in_array( $key, $reserved, true ) ) {
                continue;
            }
            update_post_meta( $post_id, Meta_Fields::PREFIX . $key, $value );
        }

        return array(
            'post_id'  => $post_id,
            'edit_url' => get_edit_post_link( $post_id, 'raw' ) ?: '',
            'view_url' => get_permalink( $post_id ) ?: '',
        );
    }

    public function execute_set_kind( array $args ): array {
        $post_id = $args['post_id'];
        $kind    = $args['kind'];

        $result = wp_set_post_terms( $post_id, array( $kind ), Taxonomy::TAXONOMY );

        if ( is_wp_error( $result ) ) {
            return array( 'success' => false, 'error' => $result->get_error_message() );
        }

        return array( 'success' => true, 'kind' => $kind );
    }

    public function execute_get_kind( array $args ): array {
        $post_id = $args['post_id'];
        $terms   = wp_get_post_terms( $post_id, Taxonomy::TAXONOMY );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array(
                'kind_slug'        => 'note',
                'kind_label'       => 'Note',
                'kind_description' => 'Short, untitled post similar to a tweet or status update.',
            );
        }

        $term = $terms[0];
        return array(
            'kind_slug'        => $term->slug,
            'kind_label'       => $term->name,
            'kind_description' => $term->description,
        );
    }

    public function execute_update_post_meta( array $args ): array {
        $post_id = $args['post_id'];
        $key     = Meta_Fields::PREFIX . $args['meta_key'];
        $value   = $args['meta_value'];

        update_post_meta( $post_id, $key, $value );

        return array( 'success' => true );
    }

    public function execute_get_post_meta( array $args ): array {
        $post_id   = $args['post_id'];
        $meta_keys = $args['meta_keys'] ?? null;
        $meta      = array();

        if ( $meta_keys ) {
            foreach ( $meta_keys as $key ) {
                $meta[ $key ] = get_post_meta( $post_id, Meta_Fields::PREFIX . $key, true );
            }
        } else {
            $all_meta = get_post_meta( $post_id );
            foreach ( $all_meta as $key => $values ) {
                if ( str_starts_with( $key, Meta_Fields::PREFIX ) ) {
                    $short_key          = substr( $key, strlen( Meta_Fields::PREFIX ) );
                    $meta[ $short_key ] = $values[0] ?? '';
                }
            }
        }

        return array( 'meta' => $meta );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=CoreAbilitiesTest`
Expected: PASS

**Step 5: Commit**

```bash
cd "/Users/crobertson/Documents/Post Kinds for IndieWeb"
git add includes/abilities/class-core-abilities.php tests/phpunit/unit/CoreAbilitiesTest.php
git commit -m "feat: add core abilities provider with 7 abilities for all 25 kinds"
```

---

### Task 4: Add Lookup Abilities Provider

**Files:**
- Create: `includes/abilities/class-lookup-abilities.php`
- Test: `tests/phpunit/unit/LookupAbilitiesTest.php`

**Step 1: Write the failing test**

```php
<?php
namespace PostKindsForIndieWeb\Tests\Unit;

use WP_UnitTestCase;
use PostKindsForIndieWeb\Abilities\Lookup_Abilities;

class LookupAbilitiesTest extends WP_UnitTestCase {

    private Lookup_Abilities $provider;

    public function set_up(): void {
        parent::set_up();
        $this->provider = new Lookup_Abilities();
    }

    public function test_get_lookup_definitions_returns_six(): void {
        $definitions = $this->provider->get_lookup_definitions();
        $this->assertCount( 6, $definitions );
    }

    public function test_lookup_definitions_contain_expected_kinds(): void {
        $definitions = $this->provider->get_lookup_definitions();
        $slugs       = array_keys( $definitions );
        $this->assertContains( 'music', $slugs );
        $this->assertContains( 'video', $slugs );
        $this->assertContains( 'book', $slugs );
        $this->assertContains( 'podcast', $slugs );
        $this->assertContains( 'venue', $slugs );
        $this->assertContains( 'game', $slugs );
    }

    public function test_each_definition_has_required_keys(): void {
        $definitions = $this->provider->get_lookup_definitions();
        foreach ( $definitions as $slug => $def ) {
            $this->assertArrayHasKey( 'label', $def, "Missing label for {$slug}" );
            $this->assertArrayHasKey( 'description', $def, "Missing description for {$slug}" );
            $this->assertArrayHasKey( 'rest_route', $def, "Missing rest_route for {$slug}" );
            $this->assertArrayHasKey( 'input_schema', $def, "Missing input_schema for {$slug}" );
        }
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=LookupAbilitiesTest`
Expected: FAIL — class not found

**Step 3: Write minimal implementation**

```php
<?php
declare( strict_types=1 );
namespace PostKindsForIndieWeb\Abilities;

use PostKindsForIndieWeb\Abilities_Manager;
use PostKindsForIndieWeb\REST_API;

/**
 * Lookup abilities that wrap existing REST API lookup endpoints.
 */
class Lookup_Abilities {

    public function register(): void {
        foreach ( $this->get_lookup_definitions() as $slug => $definition ) {
            wp_register_ability(
                "post-kinds/lookup-{$slug}",
                array(
                    'label'               => $definition['label'],
                    'description'         => $definition['description'],
                    'category'            => Abilities_Manager::CATEGORY_SLUG,
                    'input_schema'        => $definition['input_schema'],
                    'output_schema'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'results' => array( 'type' => 'array' ),
                        ),
                    ),
                    'execute_callback'    => function ( array $args ) use ( $definition ) {
                        return $this->execute_lookup( $definition['rest_route'], $args );
                    },
                    'permission_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                    'meta'                => array( 'show_in_rest' => true ),
                )
            );
        }
    }

    public function get_lookup_definitions(): array {
        return array(
            'music'   => array(
                'label'       => __( 'Lookup Music', 'post-kinds-for-indieweb' ),
                'description' => __( 'Search for music by artist, track, or album name.', 'post-kinds-for-indieweb' ),
                'rest_route'  => '/' . REST_API::NAMESPACE . '/lookup/music',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query'  => array( 'type' => 'string', 'description' => 'Search query (artist, track, or album).' ),
                        'type'   => array( 'type' => 'string', 'enum' => array( 'recording', 'release', 'artist' ), 'default' => 'recording' ),
                    ),
                    'required'   => array( 'query' ),
                ),
            ),
            'video'   => array(
                'label'       => __( 'Lookup Video', 'post-kinds-for-indieweb' ),
                'description' => __( 'Search for movies or TV shows by title.', 'post-kinds-for-indieweb' ),
                'rest_route'  => '/' . REST_API::NAMESPACE . '/lookup/video',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query' => array( 'type' => 'string', 'description' => 'Movie or TV show title.' ),
                        'type'  => array( 'type' => 'string', 'enum' => array( 'movie', 'tv' ), 'default' => 'movie' ),
                    ),
                    'required'   => array( 'query' ),
                ),
            ),
            'book'    => array(
                'label'       => __( 'Lookup Book', 'post-kinds-for-indieweb' ),
                'description' => __( 'Search for books by title, author, or ISBN.', 'post-kinds-for-indieweb' ),
                'rest_route'  => '/' . REST_API::NAMESPACE . '/lookup/book',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query' => array( 'type' => 'string', 'description' => 'Book title, author, or ISBN.' ),
                    ),
                    'required'   => array( 'query' ),
                ),
            ),
            'podcast' => array(
                'label'       => __( 'Lookup Podcast', 'post-kinds-for-indieweb' ),
                'description' => __( 'Search for podcasts by name.', 'post-kinds-for-indieweb' ),
                'rest_route'  => '/' . REST_API::NAMESPACE . '/lookup/podcast',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query' => array( 'type' => 'string', 'description' => 'Podcast name.' ),
                    ),
                    'required'   => array( 'query' ),
                ),
            ),
            'venue'   => array(
                'label'       => __( 'Lookup Venue', 'post-kinds-for-indieweb' ),
                'description' => __( 'Search for venues and places by name or location.', 'post-kinds-for-indieweb' ),
                'rest_route'  => '/' . REST_API::NAMESPACE . '/lookup/venue',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query' => array( 'type' => 'string', 'description' => 'Venue or place name.' ),
                        'll'    => array( 'type' => 'string', 'description' => 'Latitude,longitude for nearby search.' ),
                    ),
                    'required'   => array( 'query' ),
                ),
            ),
            'game'    => array(
                'label'       => __( 'Lookup Game', 'post-kinds-for-indieweb' ),
                'description' => __( 'Search for video games or board games by title.', 'post-kinds-for-indieweb' ),
                'rest_route'  => '/' . REST_API::NAMESPACE . '/lookup/game',
                'input_schema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'query' => array( 'type' => 'string', 'description' => 'Game title.' ),
                    ),
                    'required'   => array( 'query' ),
                ),
            ),
        );
    }

    private function execute_lookup( string $route, array $args ): array {
        $request = new \WP_REST_Request( 'GET', $route );
        foreach ( $args as $key => $value ) {
            $request->set_param( $key, $value );
        }
        $response = rest_do_request( $request );

        if ( $response->is_error() ) {
            return array( 'error' => $response->as_error()->get_error_message() );
        }

        return array( 'results' => $response->get_data() );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test:unit -- --filter=LookupAbilitiesTest`
Expected: PASS

**Step 5: Commit**

```bash
cd "/Users/crobertson/Documents/Post Kinds for IndieWeb"
git add includes/abilities/class-lookup-abilities.php tests/phpunit/unit/LookupAbilitiesTest.php
git commit -m "feat: add lookup abilities wrapping 6 REST API endpoints"
```

---

### Task 5: Wire Abilities into Main Plugin

**Files:**
- Modify: `post-kinds-for-indieweb.php` (add conditional loading)
- Modify: `includes/class-plugin.php` (initialize manager)

**Step 1: Add autoload entry for abilities namespace**

In `composer.json`, verify the PSR-4 autoload covers `includes/abilities/`:
```json
"autoload": {
    "psr-4": {
        "PostKindsForIndieWeb\\": "includes/"
    }
}
```

The `Abilities\Core_Abilities` class maps to `includes/abilities/class-core-abilities.php`. If the autoloader uses WordPress-style file naming (`class-` prefix), verify the mapping works. If not, add a classmap or adjust.

**Step 2: Add initialization to Plugin class**

In `includes/class-plugin.php`, add after existing component initialization:

```php
if ( Feature_Flags::has_abilities_api() ) {
    Abilities_Manager::instance();
}
```

**Step 3: Run full test suite**

Run: `cd "/Users/crobertson/Documents/Post Kinds for IndieWeb" && composer test`
Expected: All tests PASS

**Step 4: Commit**

```bash
cd "/Users/crobertson/Documents/Post Kinds for IndieWeb"
git add composer.json includes/class-plugin.php post-kinds-for-indieweb.php
git commit -m "feat: wire Abilities API into plugin initialization"
```

---

## Part 2: Link Extension for XFN — Abilities API

**Repo:** `/Users/crobertson/Documents/plugins/link-extension-for-xfn/`
**Namespace:** None (WordPress-style class naming, `XFN_Link_Extension`)
**Note:** No PHP testing infrastructure exists. We set it up first.

### Task 6: Set Up PHP Testing Infrastructure

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml.dist`
- Create: `tests/phpunit/bootstrap.php`
- Create: `tests/phpunit/unit/.gitkeep`

**Step 1: Create composer.json**

```json
{
    "name": "courtneyr-dev/link-extension-for-xfn",
    "description": "XFN relationship support for WordPress block editor links.",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.6",
        "yoast/phpunit-polyfills": "^4.0"
    },
    "autoload": {
        "classmap": [
            "includes/"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "LinkExtensionForXFN\\Tests\\": "tests/phpunit/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite=unit"
    }
}
```

**Step 2: Create phpunit.xml.dist**

```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/phpunit/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
>
    <php>
        <const name="PHPUNIT_RUNNING" value="1"/>
    </php>
    <testsuites>
        <testsuite name="unit">
            <directory suffix="Test.php">./tests/phpunit/unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

**Step 3: Create bootstrap.php**

```php
<?php
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( file_exists( dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' ) ) {
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/' );
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( __DIR__, 2 ) . '/link-extension-for-xfn.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
```

**Step 4: Install dependencies and verify**

Run: `cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn" && composer install`
Expected: Dependencies installed

**Step 5: Commit**

```bash
cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn"
git add composer.json phpunit.xml.dist tests/
git commit -m "chore: add PHPUnit testing infrastructure"
```

---

### Task 7: Extract PHP Into Includes Directory

**Files:**
- Create: `includes/class-xfn-meta-mirror.php`
- Create: `includes/class-xfn-feature-flags.php`

**Step 1: Create feature flags**

```php
<?php
/**
 * Feature flags for XFN Link Extension.
 */
final class XFN_Feature_Flags {

    private static array $defaults = array(
        'abilities_api'   => true,
        'meta_mirror'     => true,
    );

    public static function is_enabled( string $flag ): bool {
        if ( ! isset( self::$defaults[ $flag ] ) ) {
            return false;
        }

        $filtered = apply_filters( "xfn_feature_flag_{$flag}", null );
        if ( null !== $filtered ) {
            return (bool) $filtered;
        }

        $options = get_option( 'xfn_feature_flags', array() );
        if ( isset( $options[ $flag ] ) ) {
            return (bool) $options[ $flag ];
        }

        return self::$defaults[ $flag ];
    }

    public static function has_abilities_api(): bool {
        return self::is_enabled( 'abilities_api' ) && function_exists( 'wp_register_ability' );
    }

    public static function has_meta_mirror(): bool {
        return self::is_enabled( 'meta_mirror' );
    }
}
```

**Step 2: Commit**

```bash
cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn"
git add includes/class-xfn-feature-flags.php
git commit -m "feat: add feature flags for Abilities API and meta mirror"
```

---

### Task 8: Add XFN Meta Mirror

**Files:**
- Create: `includes/class-xfn-meta-mirror.php`
- Test: `tests/phpunit/unit/MetaMirrorTest.php`

**Step 1: Write the failing test**

```php
<?php
namespace LinkExtensionForXFN\Tests\Unit;

use WP_UnitTestCase;
use XFN_Meta_Mirror;

class MetaMirrorTest extends WP_UnitTestCase {

    public function tear_down(): void {
        parent::tear_down();
    }

    public function test_sanitize_relationships_valid(): void {
        $input = array(
            array( 'url' => 'https://example.com', 'rels' => array( 'friend', 'met' ) ),
        );
        $result = XFN_Meta_Mirror::sanitize_relationships( $input );
        $this->assertCount( 1, $result );
        $this->assertSame( 'https://example.com', $result[0]['url'] );
        $this->assertContains( 'friend', $result[0]['rels'] );
    }

    public function test_sanitize_relationships_strips_invalid_rels(): void {
        $input = array(
            array( 'url' => 'https://example.com', 'rels' => array( 'friend', 'evil-value', 'met' ) ),
        );
        $result = XFN_Meta_Mirror::sanitize_relationships( $input );
        $this->assertCount( 2, $result[0]['rels'] );
        $this->assertNotContains( 'evil-value', $result[0]['rels'] );
    }

    public function test_sanitize_relationships_strips_invalid_urls(): void {
        $input = array(
            array( 'url' => 'not-a-url', 'rels' => array( 'friend' ) ),
        );
        $result = XFN_Meta_Mirror::sanitize_relationships( $input );
        $this->assertCount( 0, $result );
    }

    public function test_apply_to_content_adds_rel(): void {
        $content = '<p><a href="https://alice.example.com">Alice</a></p>';
        $rels    = array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend', 'met' ) ),
        );
        $result = XFN_Meta_Mirror::apply_to_content( $content, $rels );
        $this->assertStringContainsString( 'rel="friend met"', $result );
    }

    public function test_apply_to_content_preserves_existing_non_xfn_rels(): void {
        $content = '<p><a href="https://alice.example.com" rel="nofollow noopener">Alice</a></p>';
        $rels    = array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend' ) ),
        );
        $result = XFN_Meta_Mirror::apply_to_content( $content, $rels );
        $this->assertStringContainsString( 'nofollow', $result );
        $this->assertStringContainsString( 'noopener', $result );
        $this->assertStringContainsString( 'friend', $result );
    }

    public function test_apply_to_content_skips_unmatched_urls(): void {
        $content = '<p><a href="https://bob.example.com">Bob</a></p>';
        $rels    = array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend' ) ),
        );
        $result = XFN_Meta_Mirror::apply_to_content( $content, $rels );
        $this->assertStringNotContainsString( 'friend', $result );
    }

    public function test_get_and_set_relationships(): void {
        $post_id = self::factory()->post->create();
        $rels    = array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend', 'met' ) ),
        );

        XFN_Meta_Mirror::set_relationships( $post_id, $rels );
        $result = XFN_Meta_Mirror::get_relationships( $post_id );

        $this->assertCount( 1, $result );
        $this->assertSame( 'https://alice.example.com', $result[0]['url'] );
    }

    public function test_add_relationship(): void {
        $post_id = self::factory()->post->create();
        XFN_Meta_Mirror::set_relationships( $post_id, array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend' ) ),
        ) );

        XFN_Meta_Mirror::add_relationship( $post_id, 'https://bob.example.com', array( 'colleague' ) );

        $result = XFN_Meta_Mirror::get_relationships( $post_id );
        $this->assertCount( 2, $result );
    }

    public function test_remove_relationship(): void {
        $post_id = self::factory()->post->create();
        XFN_Meta_Mirror::set_relationships( $post_id, array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend' ) ),
            array( 'url' => 'https://bob.example.com', 'rels' => array( 'colleague' ) ),
        ) );

        XFN_Meta_Mirror::remove_relationship( $post_id, 'https://alice.example.com' );

        $result = XFN_Meta_Mirror::get_relationships( $post_id );
        $this->assertCount( 1, $result );
        $this->assertSame( 'https://bob.example.com', $result[0]['url'] );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn" && composer test:unit -- --filter=MetaMirrorTest`
Expected: FAIL — class not found

**Step 3: Write minimal implementation**

```php
<?php
/**
 * Stores XFN relationships in post meta and syncs to block HTML on save.
 */
final class XFN_Meta_Mirror {

    public const META_KEY    = '_xfn_relationships';
    public const SOURCE_KEY  = '_xfn_meta_source';

    private static array $valid_xfn = array(
        'contact', 'acquaintance', 'friend', 'met',
        'co-worker', 'colleague', 'co-resident', 'neighbor',
        'child', 'parent', 'sibling', 'spouse', 'kin',
        'muse', 'crush', 'date', 'sweetheart', 'me',
    );

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register_meta' ) );
        add_action( 'save_post', array( __CLASS__, 'sync_meta_to_content' ), 20, 2 );
    }

    public static function register_meta(): void {
        register_post_meta( '', self::META_KEY, array(
            'type'              => 'array',
            'single'            => true,
            'default'           => array(),
            'show_in_rest'      => array(
                'schema' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'url'  => array( 'type' => 'string', 'format' => 'uri' ),
                            'rels' => array(
                                'type'  => 'array',
                                'items' => array( 'type' => 'string' ),
                            ),
                        ),
                    ),
                ),
            ),
            'sanitize_callback' => array( __CLASS__, 'sanitize_relationships' ),
            'auth_callback'     => function () {
                return current_user_can( 'edit_posts' );
            },
        ) );
    }

    public static function sanitize_relationships( $input ): array {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $clean = array();
        foreach ( $input as $entry ) {
            if ( ! is_array( $entry ) || empty( $entry['url'] ) || empty( $entry['rels'] ) ) {
                continue;
            }

            $url = esc_url_raw( $entry['url'] );
            if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
                continue;
            }

            $rels = array_filter(
                (array) $entry['rels'],
                function ( $rel ) {
                    return in_array( $rel, self::$valid_xfn, true );
                }
            );

            if ( ! empty( $rels ) ) {
                $clean[] = array(
                    'url'  => $url,
                    'rels' => array_values( $rels ),
                );
            }
        }

        return $clean;
    }

    public static function get_relationships( int $post_id ): array {
        $meta = get_post_meta( $post_id, self::META_KEY, true );
        return is_array( $meta ) ? $meta : array();
    }

    public static function set_relationships( int $post_id, array $relationships ): void {
        $clean = self::sanitize_relationships( $relationships );
        update_post_meta( $post_id, self::META_KEY, $clean );
        update_post_meta( $post_id, self::SOURCE_KEY, 'meta' );
    }

    public static function add_relationship( int $post_id, string $url, array $rels ): void {
        $existing = self::get_relationships( $post_id );

        // Update existing or append.
        $found = false;
        foreach ( $existing as &$entry ) {
            if ( $entry['url'] === $url ) {
                $entry['rels'] = array_values( array_unique( array_merge( $entry['rels'], $rels ) ) );
                $found         = true;
                break;
            }
        }
        unset( $entry );

        if ( ! $found ) {
            $existing[] = array( 'url' => $url, 'rels' => $rels );
        }

        self::set_relationships( $post_id, $existing );
    }

    public static function remove_relationship( int $post_id, string $url ): void {
        $existing = self::get_relationships( $post_id );
        $filtered = array_values( array_filter(
            $existing,
            function ( $entry ) use ( $url ) {
                return $entry['url'] !== $url;
            }
        ) );

        self::set_relationships( $post_id, $filtered );
    }

    public static function apply_to_content( string $content, array $relationships ): string {
        foreach ( $relationships as $entry ) {
            $url      = preg_quote( $entry['url'], '/' );
            $xfn_rels = $entry['rels'];

            $content = preg_replace_callback(
                '/<a\s+([^>]*?)href=["\']' . $url . '["\']([^>]*?)>/i',
                function ( $matches ) use ( $xfn_rels ) {
                    $before = $matches[1];
                    $after  = $matches[2];
                    $tag    = $matches[0];

                    // Extract existing rel.
                    $existing_rel = '';
                    if ( preg_match( '/rel=["\']([^"\']*)["\']/', $before . $after, $rel_match ) ) {
                        $existing_rel = $rel_match[1];
                        $tag = str_replace( $rel_match[0], '', $tag );
                    }

                    // Separate non-XFN rels.
                    $parsed     = XFN_Link_Extension::parse_rel_attribute( $existing_rel );
                    $other_rels = $parsed['other'];

                    // Combine.
                    $new_rel = XFN_Link_Extension::combine_rel_values( $xfn_rels, $other_rels );

                    // Insert rel before closing >.
                    return rtrim( $tag, '>' ) . ' rel="' . esc_attr( $new_rel ) . '">';
                },
                $content
            );
        }

        return $content;
    }

    public static function sync_meta_to_content( int $post_id, \WP_Post $post ): void {
        // Only sync if meta was the last writer.
        $source = get_post_meta( $post_id, self::SOURCE_KEY, true );
        if ( 'meta' !== $source ) {
            return;
        }

        $relationships = self::get_relationships( $post_id );
        if ( empty( $relationships ) ) {
            return;
        }

        $updated_content = self::apply_to_content( $post->post_content, $relationships );
        if ( $updated_content !== $post->post_content ) {
            remove_action( 'save_post', array( __CLASS__, 'sync_meta_to_content' ), 20 );
            wp_update_post( array(
                'ID'           => $post_id,
                'post_content' => $updated_content,
            ) );
            add_action( 'save_post', array( __CLASS__, 'sync_meta_to_content' ), 20, 2 );
        }

        // Reset source flag.
        delete_post_meta( $post_id, self::SOURCE_KEY );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn" && composer test:unit -- --filter=MetaMirrorTest`
Expected: PASS

**Step 5: Commit**

```bash
cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn"
git add includes/class-xfn-meta-mirror.php tests/phpunit/unit/MetaMirrorTest.php
git commit -m "feat: add XFN meta mirror for post meta storage and HTML sync"
```

---

### Task 9: Add XFN Abilities Manager and Provider

**Files:**
- Create: `includes/class-xfn-abilities-manager.php`
- Create: `includes/abilities/class-xfn-core-abilities.php`
- Test: `tests/phpunit/unit/XFNAbilitiesTest.php`

**Step 1: Write the failing test**

```php
<?php
namespace LinkExtensionForXFN\Tests\Unit;

use WP_UnitTestCase;
use XFN_Core_Abilities;

class XFNAbilitiesTest extends WP_UnitTestCase {

    private XFN_Core_Abilities $provider;

    public function set_up(): void {
        parent::set_up();
        $this->provider = new XFN_Core_Abilities();
    }

    public function test_execute_set_relationships(): void {
        $post_id = self::factory()->post->create( array(
            'post_content' => '<p><a href="https://alice.example.com">Alice</a></p>',
        ) );

        $result = $this->provider->execute_set_relationships( array(
            'post_id'       => $post_id,
            'relationships' => array(
                array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend', 'met' ) ),
            ),
        ) );

        $this->assertTrue( $result['success'] );
        $this->assertSame( 1, $result['applied'] );
    }

    public function test_execute_get_relationships(): void {
        $post_id = self::factory()->post->create();
        \XFN_Meta_Mirror::set_relationships( $post_id, array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend' ) ),
        ) );

        $result = $this->provider->execute_get_relationships( array( 'post_id' => $post_id ) );
        $this->assertCount( 1, $result['relationships'] );
    }

    public function test_execute_add_relationship(): void {
        $post_id = self::factory()->post->create();

        $result = $this->provider->execute_add_relationship( array(
            'post_id' => $post_id,
            'url'     => 'https://bob.example.com',
            'rels'    => array( 'colleague', 'met' ),
        ) );

        $this->assertTrue( $result['success'] );
        $stored = \XFN_Meta_Mirror::get_relationships( $post_id );
        $this->assertCount( 1, $stored );
    }

    public function test_execute_remove_relationship(): void {
        $post_id = self::factory()->post->create();
        \XFN_Meta_Mirror::set_relationships( $post_id, array(
            array( 'url' => 'https://alice.example.com', 'rels' => array( 'friend' ) ),
        ) );

        $result = $this->provider->execute_remove_relationship( array(
            'post_id' => $post_id,
            'url'     => 'https://alice.example.com',
        ) );

        $this->assertTrue( $result['success'] );
        $stored = \XFN_Meta_Mirror::get_relationships( $post_id );
        $this->assertCount( 0, $stored );
    }

    public function test_execute_validate_relationships_valid(): void {
        $result = $this->provider->execute_validate_relationships( array(
            'rels' => array( 'friend', 'met', 'colleague' ),
        ) );
        $this->assertTrue( $result['valid'] );
        $this->assertEmpty( $result['warnings'] );
    }

    public function test_execute_validate_relationships_exclusive_violation(): void {
        $result = $this->provider->execute_validate_relationships( array(
            'rels' => array( 'friend', 'acquaintance' ),
        ) );
        $this->assertFalse( $result['valid'] );
        $this->assertNotEmpty( $result['warnings'] );
    }
}
```

**Step 2: Run test to verify it fails**

Run: `cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn" && composer test:unit -- --filter=XFNAbilitiesTest`
Expected: FAIL

**Step 3: Write abilities manager**

```php
<?php
/**
 * Orchestrates Abilities API registration for XFN.
 */
final class XFN_Abilities_Manager {

    public const CATEGORY_SLUG = 'xfn-relationships';

    private static ?XFN_Abilities_Manager $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! XFN_Feature_Flags::has_abilities_api() ) {
            return;
        }
        add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
        add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
    }

    public function register_category(): void {
        if ( ! function_exists( 'wp_register_ability_category' ) ) {
            return;
        }
        wp_register_ability_category(
            self::CATEGORY_SLUG,
            array(
                'label'       => __( 'XFN Relationships', 'link-extension-for-xfn' ),
                'description' => __( 'Abilities for managing XFN relationships on links.', 'link-extension-for-xfn' ),
            )
        );
    }

    public function register_abilities(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) {
            return;
        }
        $provider = new XFN_Core_Abilities();
        $provider->register();
    }
}
```

**Step 4: Write core abilities provider**

```php
<?php
/**
 * XFN relationship abilities for the Abilities API.
 */
class XFN_Core_Abilities {

    private const EXCLUSIVE_GROUPS = array(
        array( 'contact', 'acquaintance', 'friend' ),
        array( 'co-resident', 'neighbor' ),
        array( 'child', 'parent', 'sibling', 'spouse', 'kin' ),
    );

    public function register(): void {
        $this->register_set_relationships();
        $this->register_get_relationships();
        $this->register_add_relationship();
        $this->register_remove_relationship();
        $this->register_validate_relationships();
    }

    private function register_set_relationships(): void {
        wp_register_ability( 'xfn/set-meta-relationships', array(
            'label'               => __( 'Set XFN Relationships', 'link-extension-for-xfn' ),
            'description'         => __( 'Set all XFN relationships for links in a post.', 'link-extension-for-xfn' ),
            'category'            => XFN_Abilities_Manager::CATEGORY_SLUG,
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id'       => array( 'type' => 'integer' ),
                    'relationships' => array(
                        'type'  => 'array',
                        'items' => array(
                            'type'       => 'object',
                            'properties' => array(
                                'url'  => array( 'type' => 'string', 'format' => 'uri' ),
                                'rels' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                            ),
                        ),
                    ),
                ),
                'required' => array( 'post_id', 'relationships' ),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array( 'type' => 'boolean' ),
                    'applied' => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback'    => array( $this, 'execute_set_relationships' ),
            'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
            'meta'                => array( 'show_in_rest' => true ),
        ) );
    }

    private function register_get_relationships(): void {
        wp_register_ability( 'xfn/get-meta-relationships', array(
            'label'               => __( 'Get XFN Relationships', 'link-extension-for-xfn' ),
            'description'         => __( 'Get XFN relationships for links in a post.', 'link-extension-for-xfn' ),
            'category'            => XFN_Abilities_Manager::CATEGORY_SLUG,
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array( 'post_id' => array( 'type' => 'integer' ) ),
                'required'   => array( 'post_id' ),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array( 'relationships' => array( 'type' => 'array' ) ),
            ),
            'execute_callback'    => array( $this, 'execute_get_relationships' ),
            'permission_callback' => function () { return current_user_can( 'read' ); },
            'meta'                => array( 'show_in_rest' => true ),
        ) );
    }

    private function register_add_relationship(): void {
        wp_register_ability( 'xfn/add-meta-relationship', array(
            'label'               => __( 'Add XFN Relationship', 'link-extension-for-xfn' ),
            'description'         => __( 'Add or update an XFN relationship for a specific URL in a post.', 'link-extension-for-xfn' ),
            'category'            => XFN_Abilities_Manager::CATEGORY_SLUG,
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id' => array( 'type' => 'integer' ),
                    'url'     => array( 'type' => 'string', 'format' => 'uri' ),
                    'rels'    => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                ),
                'required'   => array( 'post_id', 'url', 'rels' ),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array( 'success' => array( 'type' => 'boolean' ) ),
            ),
            'execute_callback'    => array( $this, 'execute_add_relationship' ),
            'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
            'meta'                => array( 'show_in_rest' => true ),
        ) );
    }

    private function register_remove_relationship(): void {
        wp_register_ability( 'xfn/remove-meta-relationship', array(
            'label'               => __( 'Remove XFN Relationship', 'link-extension-for-xfn' ),
            'description'         => __( 'Remove XFN relationship for a specific URL from a post.', 'link-extension-for-xfn' ),
            'category'            => XFN_Abilities_Manager::CATEGORY_SLUG,
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array(
                    'post_id' => array( 'type' => 'integer' ),
                    'url'     => array( 'type' => 'string', 'format' => 'uri' ),
                ),
                'required'   => array( 'post_id', 'url' ),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array( 'success' => array( 'type' => 'boolean' ) ),
            ),
            'execute_callback'    => array( $this, 'execute_remove_relationship' ),
            'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
            'meta'                => array( 'show_in_rest' => true ),
        ) );
    }

    private function register_validate_relationships(): void {
        wp_register_ability( 'xfn/validate-relationships', array(
            'label'               => __( 'Validate XFN Relationships', 'link-extension-for-xfn' ),
            'description'         => __( 'Check XFN relationship values for exclusivity violations.', 'link-extension-for-xfn' ),
            'category'            => XFN_Abilities_Manager::CATEGORY_SLUG,
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array(
                    'rels' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                ),
                'required'   => array( 'rels' ),
            ),
            'output_schema'       => array(
                'type'       => 'object',
                'properties' => array(
                    'valid'    => array( 'type' => 'boolean' ),
                    'warnings' => array( 'type' => 'array' ),
                ),
            ),
            'execute_callback'    => array( $this, 'execute_validate_relationships' ),
            'permission_callback' => function () { return current_user_can( 'read' ); },
            'meta'                => array( 'show_in_rest' => true ),
        ) );
    }

    // --- Execute callbacks ---

    public function execute_set_relationships( array $args ): array {
        $post_id       = $args['post_id'];
        $relationships = $args['relationships'];

        XFN_Meta_Mirror::set_relationships( $post_id, $relationships );

        return array(
            'success' => true,
            'applied' => count( $relationships ),
        );
    }

    public function execute_get_relationships( array $args ): array {
        return array(
            'relationships' => XFN_Meta_Mirror::get_relationships( $args['post_id'] ),
        );
    }

    public function execute_add_relationship( array $args ): array {
        XFN_Meta_Mirror::add_relationship( $args['post_id'], $args['url'], $args['rels'] );
        return array( 'success' => true );
    }

    public function execute_remove_relationship( array $args ): array {
        XFN_Meta_Mirror::remove_relationship( $args['post_id'], $args['url'] );
        return array( 'success' => true );
    }

    public function execute_validate_relationships( array $args ): array {
        $rels     = $args['rels'];
        $warnings = array();

        foreach ( self::EXCLUSIVE_GROUPS as $group ) {
            $found = array_intersect( $rels, $group );
            if ( count( $found ) > 1 ) {
                $warnings[] = sprintf(
                    'Mutually exclusive: %s (pick one of: %s)',
                    implode( ', ', $found ),
                    implode( ', ', $group )
                );
            }
        }

        return array(
            'valid'    => empty( $warnings ),
            'warnings' => $warnings,
        );
    }
}
```

**Step 5: Run test to verify it passes**

Run: `cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn" && composer test:unit -- --filter=XFNAbilitiesTest`
Expected: PASS

**Step 6: Commit**

```bash
cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn"
git add includes/class-xfn-abilities-manager.php includes/abilities/class-xfn-core-abilities.php tests/phpunit/unit/XFNAbilitiesTest.php
git commit -m "feat: add XFN Abilities API with 5 abilities"
```

---

### Task 10: Wire XFN Abilities into Main Plugin

**Files:**
- Modify: `link-extension-for-xfn.php` (add includes loading and initialization)

**Step 1: Add file includes and initialization**

At the end of the `__construct()` method in `XFN_Link_Extension`, add:

```php
// Load new classes.
require_once XFN_LINK_EXT_PATH . 'includes/class-xfn-feature-flags.php';
require_once XFN_LINK_EXT_PATH . 'includes/class-xfn-meta-mirror.php';
require_once XFN_LINK_EXT_PATH . 'includes/class-xfn-abilities-manager.php';
require_once XFN_LINK_EXT_PATH . 'includes/abilities/class-xfn-core-abilities.php';

// Initialize meta mirror.
if ( XFN_Feature_Flags::has_meta_mirror() ) {
    XFN_Meta_Mirror::init();
}

// Initialize abilities.
if ( XFN_Feature_Flags::has_abilities_api() ) {
    XFN_Abilities_Manager::instance();
}
```

**Step 2: Run full test suite**

Run: `cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn" && composer test`
Expected: PASS

**Step 3: Commit**

```bash
cd "/Users/crobertson/Documents/plugins/link-extension-for-xfn"
git add link-extension-for-xfn.php
git commit -m "feat: wire meta mirror and Abilities API into plugin"
```

---

## Part 3: WP Pinch Community PR

### Task 11: Fork WP Pinch and Add Post Format Support

**Step 1: Fork and clone**

```bash
cd /Users/crobertson/Documents/plugins
gh repo fork RegionallyFamous/wp-pinch --clone
cd wp-pinch
git checkout -b feat/post-format-support
```

**Step 2: Find and modify create-post ability**

Search for the `create-post` ability registration. Add `format` to the input schema:

```php
'format' => array(
    'type'        => 'string',
    'description' => __( 'Post format.', 'wp-pinch' ),
    'enum'        => array( 'standard', 'aside', 'chat', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio' ),
    'default'     => 'standard',
),
```

In the execute callback, after `wp_insert_post()`, add:

```php
if ( ! empty( $args['format'] ) && 'standard' !== $args['format'] ) {
    set_post_format( $post_id, $args['format'] );
}
```

**Step 3: Modify update-post ability**

Same pattern: add `format` to input schema, add `set_post_format()` call in execute callback.

**Step 4: Modify get-post output**

Add to the response array:

```php
'format' => get_post_format( $post_id ) ?: 'standard',
```

**Step 5: Commit and create PR**

```bash
git add -A
git commit -m "feat: add post format support to create-post, update-post, and get-post"
git push -u origin feat/post-format-support
gh pr create --title "Add post format support" --body "Adds format parameter to create-post and update-post abilities, and includes format in get-post output. Uses standard WordPress set_post_format/get_post_format functions."
```

---

## Verification Checklist

After all tasks complete:

- [ ] Post Kinds: `composer test` passes
- [ ] Post Kinds: Abilities manager loads when `wp_register_ability` exists
- [ ] Post Kinds: All 13 abilities register (7 core + 6 lookup)
- [ ] XFN: `composer test` passes
- [ ] XFN: Meta mirror stores/retrieves relationships
- [ ] XFN: Meta-to-HTML sync works on save
- [ ] XFN: All 5 abilities register
- [ ] WP Pinch: PR submitted with format support
- [ ] All commits have descriptive messages
