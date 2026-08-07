<?php
/**
 * IndieWeb Abilities for Post Formats Block Themes
 *
 * Registers IndieWeb-related abilities with the WordPress Abilities API.
 * Provides machine-readable operations for mf2 markup, POSSE preparation,
 * and webmention handling.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IndieWeb Abilities Provider
 *
 * Registers IndieWeb abilities:
 * - post-formats/mf2-markup - Generate mf2 markup for a post
 * - post-formats/mf2-validate - Validate mf2 output
 * - post-formats/posse-prepare - Prepare content for POSSE
 * - post-formats/posse-targets - Get available syndication targets
 * - post-formats/webmention-context - Get webmention context for format
 *
 * @since 1.2.0
 */
class PFBT_IndieWeb_Abilities {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_IndieWeb_Abilities|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_IndieWeb_Abilities
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
		// Load dependencies.
		$this->load_dependencies();
	}

	/**
	 * Load required dependencies
	 *
	 * @since 1.2.0
	 */
	private function load_dependencies() {
		if ( ! class_exists( 'PFBT_Format_Mf2' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/mf2/class-pfbt-format-mf2.php';
		}

		if ( ! class_exists( 'PFBT_Posse_Transformer' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/posse/class-pfbt-posse-transformer.php';
		}

		if ( ! class_exists( 'PFBT_Webmention_Context' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/webmention/class-pfbt-webmention-context.php';
		}
	}

	/**
	 * Register all IndieWeb abilities
	 *
	 * @since 1.2.0
	 */
	public function register() {
		$this->register_mf2_markup();
		$this->register_mf2_validate();
		$this->register_posse_prepare();
		$this->register_posse_targets();
		$this->register_webmention_context();
	}

	/**
	 * Register mf2_markup ability
	 *
	 * @since 1.2.0
	 */
	private function register_mf2_markup() {
		wp_register_ability(
			'post-formats/mf2-markup',
			array(
				'label'               => __( 'Generate Microformats2 Markup', 'post-formats-for-block-themes' ),
				'description'         => __( 'Generate microformats2 markup for a post based on its format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'format'        => array( 'type' => 'string' ),
						'entry_class'   => array( 'type' => 'string' ),
						'content_class' => array( 'type' => 'string' ),
						'properties'    => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_mf2_markup' ),
				'permission_callback' => function () {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute mf2_markup ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Output data or error.
	 */
	public function execute_mf2_markup( $args ) {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}

		$mf2    = PFBT_Format_Mf2::instance();
		$markup = $mf2->generate_mf2_markup( $post );

		return $markup;
	}

	/**
	 * Register mf2_validate ability
	 *
	 * @since 1.2.0
	 */
	private function register_mf2_validate() {
		wp_register_ability(
			'post-formats/mf2-validate',
			array(
				'label'               => __( 'Validate Microformats2 Output', 'post-formats-for-block-themes' ),
				'description'         => __( 'Validate that a post has correct microformats2 markup for its format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'valid'  => array( 'type' => 'boolean' ),
						'errors' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'markup' => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_mf2_validate' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute mf2_validate ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Output data or error.
	 */
	public function execute_mf2_validate( $args ) {
		$post_id = absint( $args['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}

		$mf2    = PFBT_Format_Mf2::instance();
		$result = $mf2->validate_mf2( $post );

		return $result;
	}

	/**
	 * Register posse_prepare ability
	 *
	 * @since 1.2.0
	 */
	private function register_posse_prepare() {
		wp_register_ability(
			'post-formats/posse-prepare',
			array(
				'label'               => __( 'Prepare POSSE Content', 'post-formats-for-block-themes' ),
				'description'         => __( 'Prepare post content for POSSE (Publish Own Site, Syndicate Elsewhere) syndication.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID.', 'post-formats-for-block-themes' ),
						),
						'targets' => array(
							'type'        => 'array',
							'items'       => array( 'type' => 'string' ),
							'description' => __( 'Target platforms (twitter, mastodon, bluesky, etc.).', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'  => array( 'type' => 'integer' ),
						'format'   => array( 'type' => 'string' ),
						'prepared' => array(
							'type'       => 'object',
							'properties' => array(
								'text'       => array( 'type' => 'string' ),
								'url'        => array( 'type' => 'string' ),
								'media'      => array( 'type' => 'array' ),
								'char_count' => array( 'type' => 'integer' ),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_posse_prepare' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute posse_prepare ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Output data or error.
	 */
	public function execute_posse_prepare( $args ) {
		$post_id = absint( $args['post_id'] );
		$targets = $args['targets'] ?? array();
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}

		$transformer = PFBT_Posse_Transformer::instance();
		$prepared    = $transformer->prepare( $post, $targets );

		return array(
			'post_id'  => $post_id,
			'format'   => get_post_format( $post_id ) ? get_post_format( $post_id ) : 'standard',
			'prepared' => $prepared,
		);
	}

	/**
	 * Register posse_targets ability
	 *
	 * @since 1.2.0
	 */
	private function register_posse_targets() {
		wp_register_ability(
			'post-formats/posse-targets',
			array(
				'label'               => __( 'Get POSSE Targets', 'post-formats-for-block-themes' ),
				'description'         => __( 'Get available POSSE syndication targets and their configurations.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'targets' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'          => array( 'type' => 'string' ),
									'name'        => array( 'type' => 'string' ),
									'char_limit'  => array( 'type' => 'integer' ),
									'media_types' => array( 'type' => 'array' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_posse_targets' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute posse_targets ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array Output data.
	 */
	public function execute_posse_targets( $args ) {
		$transformer = PFBT_Posse_Transformer::instance();
		return array(
			'targets' => $transformer->get_targets(),
		);
	}

	/**
	 * Register webmention_context ability
	 *
	 * @since 1.2.0
	 */
	private function register_webmention_context() {
		wp_register_ability(
			'post-formats/webmention-context',
			array(
				'label'               => __( 'Get Webmention Context', 'post-formats-for-block-themes' ),
				'description'         => __( 'Get webmention sending and receiving context for a post format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'format' ),
					'properties' => array(
						'format' => array(
							'type'        => 'string',
							'enum'        => array( 'standard', 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ),
							'description' => __( 'The post format.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'format'       => array( 'type' => 'string' ),
						'send_as'      => array( 'type' => 'string' ),
						'accept_types' => array( 'type' => 'array' ),
						'properties'   => array( 'type' => 'object' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_webmention_context' ),
				'permission_callback' => function () {
					return current_user_can( 'read' );
				},
				'meta'                => array(
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute webmention_context ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array Output data.
	 */
	public function execute_webmention_context( $args ) {
		$format  = $args['format'];
		$context = PFBT_Webmention_Context::instance();

		return $context->get_context( $format );
	}
}
