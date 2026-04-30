<?php
/**
 * Post Kinds Integration
 *
 * Integrates with the Post Kinds plugin to suggest appropriate kinds
 * based on selected post format.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 *
 * @see https://github.com/dshanske/indieweb-post-kinds
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Kinds Integration
 *
 * Maps WordPress post formats to IndieWeb Post Kinds for semantic meaning.
 *
 * Post Format = Presentation (how it looks)
 * Post Kind = Intent (what type of interaction)
 *
 * Mapping:
 * - Audio format → Listen kind (podcast, music)
 * - Video format → Watch kind (video content)
 * - Image/Gallery → Photo kind
 * - Quote → Quotation kind
 * - Link → Bookmark kind
 * - Status/Aside → Note kind
 * - Standard → Article kind
 *
 * @since 1.2.0
 */
class PFBT_Post_Kinds_Integration {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Post_Kinds_Integration|null
	 */
	private static $instance = null;

	/**
	 * Format to Kind mapping
	 *
	 * @var array<string, string>
	 */
	private $format_kind_map = array();

	/**
	 * Kind to Format mapping (reverse)
	 *
	 * @var array<string, string>
	 */
	private $kind_format_map = array();

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Post_Kinds_Integration
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->init_mappings();
		$this->init_hooks();
	}

	/**
	 * Initialize format/kind mappings
	 *
	 * @since 1.2.0
	 */
	private function init_mappings() {
		/**
		 * Format to Kind mapping.
		 *
		 * When a user selects a post format, suggest the corresponding kind.
		 * Multiple formats can map to the same kind.
		 */
		$this->format_kind_map = array(
			'standard' => 'article',   // Long-form blog post.
			'aside'    => 'note',      // Short note.
			'status'   => 'note',      // Status update (also note).
			'quote'    => 'quotation', // Quote with attribution.
			'link'     => 'bookmark',  // Link/bookmark.
			'image'    => 'photo',     // Single image.
			'gallery'  => 'photo',     // Multiple images (still photo kind).
			'video'    => 'watch',     // Video content.
			'audio'    => 'listen',    // Audio/podcast content.
			'chat'     => 'note',      // Chat transcript.
		);

		/**
		 * Kind to Format mapping (for reverse suggestions).
		 *
		 * When a user selects a post kind, suggest the corresponding format.
		 */
		$this->kind_format_map = array(
			'article'   => 'standard',
			'note'      => 'aside',    // Default note to aside (status is more restrictive).
			'quotation' => 'quote',
			'bookmark'  => 'link',
			'photo'     => 'image',
			'watch'     => 'video',
			'listen'    => 'audio',
			'reply'     => 'aside',    // Replies are typically short.
			'like'      => 'aside',    // Likes can use aside format.
			'repost'    => 'aside',    // Reposts/boosts.
			'rsvp'      => 'aside',    // Event RSVPs.
			'checkin'   => 'status',   // Location checkins.
			'tag'       => 'aside',    // Tag responses.
		);

		/**
		 * Filter the format to kind mapping.
		 *
		 * @since 1.2.0
		 *
		 * @param array $format_kind_map The format to kind mapping.
		 */
		$this->format_kind_map = apply_filters( 'pfbt_format_kind_map', $this->format_kind_map );

		/**
		 * Filter the kind to format mapping.
		 *
		 * @since 1.2.0
		 *
		 * @param array $kind_format_map The kind to format mapping.
		 */
		$this->kind_format_map = apply_filters( 'pfbt_kind_format_map', $this->kind_format_map );
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.2.0
	 */
	private function init_hooks() {
		// Only add hooks if Post Kinds plugin is active.
		if ( ! $this->is_post_kinds_active() ) {
			return;
		}

		// Register IndieBlocks kind meta for REST API if not already registered.
		add_action( 'init', array( $this, 'register_kind_meta' ), 20 );

		// Pass mapping data to JavaScript editor.
		add_filter( 'pfbt_editor_script_data', array( $this, 'add_kind_mapping_to_editor' ) );

		// Suggest kind when format is set.
		add_action( 'set_post_format', array( $this, 'suggest_kind_on_format_change' ), 10, 3 );

		// Optionally auto-set format when kind is set.
		add_action( 'set_object_terms', array( $this, 'suggest_format_on_kind_change' ), 10, 6 );

		// Add REST API endpoint for kind suggestions.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register IndieBlocks kind meta for REST API access
	 *
	 * IndieBlocks doesn't register _indieblocks_kind with show_in_rest,
	 * so we register it ourselves to enable setting via the block editor.
	 *
	 * @since 1.2.0
	 */
	public function register_kind_meta() {
		// Check if we're using IndieBlocks (which uses post meta).
		if ( ! class_exists( 'IndieBlocks\\IndieBlocks' ) && ! class_exists( 'IndieBlocks\\Post_Types' ) ) {
			return;
		}

		// Check if _indieblocks_kind is already registered with show_in_rest.
		$registered_meta = get_registered_meta_keys( 'post', 'post' );
		if ( isset( $registered_meta['_indieblocks_kind'] ) && ! empty( $registered_meta['_indieblocks_kind']['show_in_rest'] ) ) {
			return;
		}

		// Register the meta key with REST API support.
		register_post_meta(
			'post',
			'_indieblocks_kind',
			array(
				'show_in_rest'      => true,
				'single'            => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Check if Post Kinds or IndieBlocks plugin is active
	 *
	 * Supports:
	 * - Post Kinds plugin (Kind_Taxonomy class or 'kind' taxonomy)
	 * - IndieBlocks plugin (has Post Kind meta box feature)
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if Post Kinds or IndieBlocks is active.
	 */
	public function is_post_kinds_active() {
		// Check for Post Kinds plugin.
		if ( class_exists( 'Kind_Taxonomy' ) || taxonomy_exists( 'kind' ) ) {
			return true;
		}

		// Check for IndieBlocks plugin with Post Kinds feature.
		if ( class_exists( 'IndieBlocks\\Post_Types' ) || class_exists( 'IndieBlocks\\IndieBlocks' ) ) {
			return true;
		}

		// Check if the indieblocks_kind meta is registered.
		$registered_meta = get_registered_meta_keys( 'post' );
		if ( isset( $registered_meta['_indieblocks_kind'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get suggested kind for a format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return string|null Suggested kind slug or null.
	 */
	public function get_kind_for_format( $format ) {
		if ( empty( $format ) || false === $format ) {
			$format = 'standard';
		}

		return $this->format_kind_map[ $format ] ?? null;
	}

	/**
	 * Get suggested format for a kind
	 *
	 * @since 1.2.0
	 *
	 * @param string $kind Post kind slug.
	 * @return string|null Suggested format slug or null.
	 */
	public function get_format_for_kind( $kind ) {
		return $this->kind_format_map[ $kind ] ?? null;
	}

	/**
	 * Add kind mapping data to editor JavaScript
	 *
	 * @since 1.2.0
	 *
	 * @param array $data Existing editor data.
	 * @return array Modified editor data.
	 */
	public function add_kind_mapping_to_editor( $data ) {
		$data['postKinds'] = array(
			'active'       => $this->is_post_kinds_active(),
			'formatToKind' => $this->format_kind_map,
			'kindToFormat' => $this->kind_format_map,
		);

		return $data;
	}

	/**
	 * Suggest kind when post format is changed
	 *
	 * This fires after set_post_format() and can optionally set the kind.
	 *
	 * @since 1.2.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $format  New format.
	 * @param string $previous_format Previous format.
	 */
	public function suggest_kind_on_format_change( $post_id, $format, $previous_format = '' ) {
		// Only proceed if auto-suggestion is enabled.
		if ( ! $this->should_auto_suggest_kind() ) {
			return;
		}

		$suggested_kind = $this->get_kind_for_format( $format );

		if ( ! $suggested_kind ) {
			return;
		}

		// Check if kind is already set to something specific.
		$current_kind = $this->get_post_kind( $post_id );

		// Don't override if user has already set a kind.
		if ( $current_kind && 'note' !== $current_kind ) {
			return;
		}

		// Set the suggested kind.
		$this->set_post_kind( $post_id, $suggested_kind );

		/**
		 * Fires after auto-suggesting a kind based on format.
		 *
		 * @since 1.2.0
		 *
		 * @param int    $post_id        Post ID.
		 * @param string $suggested_kind The suggested kind.
		 * @param string $format         The post format.
		 */
		do_action( 'pfbt_kind_auto_suggested', $post_id, $suggested_kind, $format );
	}

	/**
	 * Suggest format when post kind is changed
	 *
	 * @since 1.2.0
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      Array of term IDs.
	 * @param array  $tt_ids     Array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append terms.
	 * @param array  $old_tt_ids Array of old term taxonomy IDs.
	 */
	public function suggest_format_on_kind_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( 'kind' !== $taxonomy ) {
			return;
		}

		// Only proceed if auto-suggestion is enabled.
		if ( ! $this->should_auto_suggest_format() ) {
			return;
		}

		// Get the new kind slug.
		if ( empty( $terms ) ) {
			return;
		}

		$term = get_term( $terms[0], 'kind' );
		if ( ! $term || is_wp_error( $term ) ) {
			return;
		}

		$kind             = $term->slug;
		$suggested_format = $this->get_format_for_kind( $kind );

		if ( ! $suggested_format ) {
			return;
		}

		// Check if format is already set.
		$current_format = get_post_format( $object_id );

		// Don't override if user has already set a non-standard format.
		if ( $current_format && 'standard' !== $current_format ) {
			return;
		}

		// Set the suggested format.
		set_post_format( $object_id, $suggested_format );

		/**
		 * Fires after auto-suggesting a format based on kind.
		 *
		 * @since 1.2.0
		 *
		 * @param int    $object_id        Post ID.
		 * @param string $suggested_format The suggested format.
		 * @param string $kind             The post kind.
		 */
		do_action( 'pfbt_format_auto_suggested', $object_id, $suggested_format, $kind );
	}

	/**
	 * Check if auto-suggesting kinds is enabled
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if auto-suggestion is enabled.
	 */
	private function should_auto_suggest_kind() {
		/**
		 * Filter whether to auto-suggest post kinds based on format.
		 *
		 * @since 1.2.0
		 *
		 * @param bool $enabled Whether auto-suggestion is enabled.
		 */
		return apply_filters( 'pfbt_auto_suggest_kind', false );
	}

	/**
	 * Check if auto-suggesting formats is enabled
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if auto-suggestion is enabled.
	 */
	private function should_auto_suggest_format() {
		/**
		 * Filter whether to auto-suggest post formats based on kind.
		 *
		 * @since 1.2.0
		 *
		 * @param bool $enabled Whether auto-suggestion is enabled.
		 */
		return apply_filters( 'pfbt_auto_suggest_format', false );
	}

	/**
	 * Get post kind for a post
	 *
	 * @since 1.2.0
	 *
	 * @param int $post_id Post ID.
	 * @return string|null Kind slug or null.
	 */
	private function get_post_kind( $post_id ) {
		if ( function_exists( 'get_post_kind_slug' ) ) {
			return get_post_kind_slug( $post_id );
		}

		$terms = wp_get_post_terms( $post_id, 'kind', array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		return $terms[0];
	}

	/**
	 * Set post kind for a post
	 *
	 * @since 1.2.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $kind    Kind slug.
	 * @return bool True on success.
	 */
	private function set_post_kind( $post_id, $kind ) {
		if ( function_exists( 'set_post_kind' ) ) {
			return set_post_kind( $post_id, $kind );
		}

		$result = wp_set_post_terms( $post_id, array( $kind ), 'kind' );
		return ! is_wp_error( $result );
	}

	/**
	 * Register REST API routes
	 *
	 * @since 1.2.0
	 */
	public function register_rest_routes() {
		register_rest_route(
			'pfbt/v1',
			'/kind-suggestion',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_kind_suggestion' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'format' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'pfbt/v1',
			'/format-suggestion',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_format_suggestion' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'kind' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * REST API callback for kind suggestion
	 *
	 * @since 1.2.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function rest_get_kind_suggestion( $request ) {
		$format = $request->get_param( 'format' );
		$kind   = $this->get_kind_for_format( $format );

		return rest_ensure_response(
			array(
				'format'         => $format,
				'suggested_kind' => $kind,
			)
		);
	}

	/**
	 * REST API callback for format suggestion
	 *
	 * @since 1.2.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response.
	 */
	public function rest_get_format_suggestion( $request ) {
		$kind   = $request->get_param( 'kind' );
		$format = $this->get_format_for_kind( $kind );

		return rest_ensure_response(
			array(
				'kind'             => $kind,
				'suggested_format' => $format,
			)
		);
	}

	/**
	 * Get all format to kind mappings
	 *
	 * @since 1.2.0
	 *
	 * @return array All mappings.
	 */
	public function get_all_format_kind_mappings() {
		return $this->format_kind_map;
	}

	/**
	 * Get all kind to format mappings
	 *
	 * @since 1.2.0
	 *
	 * @return array All mappings.
	 */
	public function get_all_kind_format_mappings() {
		return $this->kind_format_map;
	}
}
