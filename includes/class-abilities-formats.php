<?php
/**
 * Format Operation Abilities for Post Formats Block Themes
 *
 * Registers format-specific operation abilities with the WordPress Abilities API.
 * Provides detect, switch, repair, and stats abilities for MCP and REST consumers.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format Operation Abilities Provider
 *
 * Registers format operation abilities:
 * - post-formats/detect-format  - Detect format from post or content
 * - post-formats/switch-format  - Switch a post's format
 * - post-formats/repair-formats - Bulk repair mismatched formats
 * - post-formats/get-format-stats - Get post count per format
 *
 * @since 1.2.0
 */
class PFBT_Abilities_Formats {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Abilities_Formats|null
	 */
	private static $instance = null;

	/**
	 * Valid format slugs
	 *
	 * @var string[]
	 */
	const FORMAT_ENUM = array(
		'standard',
		'aside',
		'audio',
		'chat',
		'gallery',
		'image',
		'link',
		'quote',
		'status',
		'video',
	);

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Abilities_Formats
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
		// Initialization happens in register().
	}

	/**
	 * Register all format operation abilities
	 *
	 * @since 1.2.0
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$this->register_detect_format();
		$this->register_switch_format();
		$this->register_repair_formats();
		$this->register_get_format_stats();
	}

	/**
	 * Register detect-format ability
	 *
	 * Detects format from a post ID or raw content string.
	 *
	 * @since 1.2.0
	 */
	private function register_detect_format() {
		wp_register_ability(
			'post-formats/detect-format',
			array(
				'label'               => __( 'Detect Post Format', 'post-formats-for-block-themes' ),
				'description'         => __( 'Detect the appropriate post format from a post or content string by analyzing block structure.', 'post-formats-for-block-themes' ),
				'type'                => 'resource',
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'Post ID to detect format for. Takes priority over content.', 'post-formats-for-block-themes' ),
						),
						'content' => array(
							'type'        => 'string',
							'description' => __( 'Raw post content (HTML or block markup) to analyze.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'format'     => array(
							'type'        => 'string',
							'description' => __( 'Detected format slug.', 'post-formats-for-block-themes' ),
						),
						'confidence' => array(
							'type'        => 'number',
							'description' => __( 'Detection confidence from 0.0 to 1.0.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_detect_format' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'resource',
					),
				),
			)
		);
	}

	/**
	 * Execute detect-format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Detection result.
	 */
	public function execute_detect_format( $args ) {
		$post_id = isset( $args['post_id'] ) ? absint( $args['post_id'] ) : 0;
		$content = isset( $args['content'] ) ? wp_kses_post( $args['content'] ) : '';

		// Resolve content from post if post_id provided.
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				return new WP_Error(
					'invalid_post',
					__( 'Post not found.', 'post-formats-for-block-themes' ),
					array( 'status' => 404 )
				);
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return new WP_Error(
					'forbidden',
					__( 'You do not have permission to access this post.', 'post-formats-for-block-themes' ),
					array( 'status' => 403 )
				);
			}
			$content = $post->post_content;
		}

		if ( empty( $content ) ) {
			return new WP_Error(
				'missing_input',
				__( 'Either post_id or content is required.', 'post-formats-for-block-themes' ),
				array( 'status' => 400 )
			);
		}

		// Parse blocks and find first meaningful block.
		$blocks = parse_blocks( $content );
		$blocks = array_values(
			array_filter(
				$blocks,
				function ( $block ) {
					return ! empty( $block['blockName'] );
				}
			)
		);

		if ( empty( $blocks ) ) {
			return array(
				'format'     => 'standard',
				'confidence' => 0.5,
			);
		}

		$first_block = $blocks[0];
		$block_name  = $first_block['blockName'];
		$block_attrs = $first_block['attrs'] ?? array();

		$detected = PFBT_Format_Registry::get_format_by_block( $block_name, $block_attrs );

		return array(
			'format'     => $detected ? esc_html( $detected ) : 'standard',
			'confidence' => $detected ? 0.8 : 0.5,
		);
	}

	/**
	 * Register switch-format ability
	 *
	 * Changes a post's format and records the change.
	 *
	 * @since 1.2.0
	 */
	private function register_switch_format() {
		wp_register_ability(
			'post-formats/switch-format',
			array(
				'label'               => __( 'Switch Post Format', 'post-formats-for-block-themes' ),
				'description'         => __( 'Change the post format for a specific post, recording the old format.', 'post-formats-for-block-themes' ),
				'type'                => 'tool',
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'new_format' ),
					'properties' => array(
						'post_id'    => array(
							'type'        => 'integer',
							'description' => __( 'The post ID to change.', 'post-formats-for-block-themes' ),
						),
						'new_format' => array(
							'type'        => 'string',
							'enum'        => self::FORMAT_ENUM,
							'description' => __( 'Target post format slug.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array(
							'type'        => 'boolean',
							'description' => __( 'Whether the format was changed.', 'post-formats-for-block-themes' ),
						),
						'old_format' => array(
							'type'        => 'string',
							'description' => __( 'The previous format slug.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_switch_format' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}

	/**
	 * Execute switch-format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Switch result.
	 */
	public function execute_switch_format( $args ) {
		$post_id    = absint( $args['post_id'] );
		$new_format = sanitize_key( $args['new_format'] );

		// Validate format slug.
		if ( ! PFBT_Format_Registry::format_exists( $new_format ) ) {
			return new WP_Error(
				'invalid_format',
				__( 'Invalid post format specified.', 'post-formats-for-block-themes' ),
				array( 'status' => 400 )
			);
		}

		// Validate post.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to edit this post.', 'post-formats-for-block-themes' ),
				array( 'status' => 403 )
			);
		}

		// Capture old format.
		$old_format = get_post_format( $post_id );
		if ( false === $old_format ) {
			$old_format = 'standard';
		}

		// WordPress core uses false for standard format.
		$format_value = 'standard' === $new_format ? false : $new_format;
		$result       = set_post_format( $post_id, $format_value );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Mark as manually set.
		update_post_meta( $post_id, '_pfbt_format_manual', true );

		/**
		 * Fires after a post format is switched via the Abilities API.
		 *
		 * @since 1.2.0
		 *
		 * @param int    $post_id    The post ID.
		 * @param string $old_format The previous format slug.
		 * @param string $new_format The new format slug.
		 */
		do_action( 'pfbt_format_switched', $post_id, $old_format, $new_format );

		return array(
			'success'    => true,
			'old_format' => esc_html( $old_format ),
		);
	}

	/**
	 * Register repair-formats ability
	 *
	 * Scans posts for format mismatches and repairs them.
	 *
	 * @since 1.2.0
	 */
	private function register_repair_formats() {
		wp_register_ability(
			'post-formats/repair-formats',
			array(
				'label'               => __( 'Repair Post Formats', 'post-formats-for-block-themes' ),
				'description'         => __( 'Scan posts for format mismatches and repair them by comparing content blocks against the format registry.', 'post-formats-for-block-themes' ),
				'type'                => 'tool',
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_ids' => array(
							'oneOf' => array(
								array(
									'type'        => 'array',
									'items'       => array( 'type' => 'integer' ),
									'description' => __( 'Specific post IDs to repair.', 'post-formats-for-block-themes' ),
								),
								array(
									'type'        => 'string',
									'enum'        => array( 'all' ),
									'description' => __( 'Use "all" to scan all published posts.', 'post-formats-for-block-themes' ),
								),
							),
							'description' => __( 'Post IDs to repair, or "all" to scan every published post.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'repaired' => array(
							'type'        => 'integer',
							'description' => __( 'Number of posts repaired.', 'post-formats-for-block-themes' ),
						),
						'skipped'  => array(
							'type'        => 'integer',
							'description' => __( 'Number of posts skipped (already correct or manually set).', 'post-formats-for-block-themes' ),
						),
						'errors'   => array(
							'type'        => 'array',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'post_id' => array( 'type' => 'integer' ),
									'message' => array( 'type' => 'string' ),
								),
							),
							'description' => __( 'Errors encountered during repair.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_repair_formats' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
				),
			)
		);
	}

	/**
	 * Execute repair-formats ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Repair results.
	 */
	public function execute_repair_formats( $args ) {
		$post_ids_input = $args['post_ids'] ?? 'all';

		$repaired = 0;
		$skipped  = 0;
		$errors   = array();

		// Resolve posts to scan.
		if ( 'all' === $post_ids_input ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 500,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);

			$post_ids = $query->posts;
		} elseif ( is_array( $post_ids_input ) ) {
			$post_ids = array_map( 'absint', $post_ids_input );
			$post_ids = array_filter( $post_ids );
		} else {
			return new WP_Error(
				'invalid_input',
				__( 'post_ids must be an array of integers or "all".', 'post-formats-for-block-themes' ),
				array( 'status' => 400 )
			);
		}

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || 'post' !== $post->post_type ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => __( 'Post not found or not a post type.', 'post-formats-for-block-themes' ),
				);
				continue;
			}

			// Skip manually set formats.
			if ( get_post_meta( $post_id, '_pfbt_format_manual', true ) ) {
				++$skipped;
				continue;
			}

			// Detect format from content.
			$blocks = parse_blocks( $post->post_content );
			$blocks = array_values(
				array_filter(
					$blocks,
					function ( $block ) {
						return ! empty( $block['blockName'] );
					}
				)
			);

			if ( empty( $blocks ) ) {
				++$skipped;
				continue;
			}

			$first_block = $blocks[0];
			$suggested   = PFBT_Format_Registry::get_format_by_block(
				$first_block['blockName'],
				$first_block['attrs'] ?? array()
			);

			if ( ! $suggested ) {
				++$skipped;
				continue;
			}

			// Compare with current format.
			$current = get_post_format( $post_id );
			if ( false === $current ) {
				$current = 'standard';
			}

			if ( $current === $suggested ) {
				++$skipped;
				continue;
			}

			// Apply repair.
			$format_value = 'standard' === $suggested ? false : $suggested;
			$result       = set_post_format( $post_id, $format_value );

			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'post_id' => $post_id,
					'message' => esc_html( $result->get_error_message() ),
				);
				continue;
			}

			/**
			 * Fires after a post format is repaired via the Abilities API.
			 *
			 * @since 1.2.0
			 *
			 * @param int    $post_id   The post ID.
			 * @param string $current   The previous format slug.
			 * @param string $suggested The repaired format slug.
			 */
			do_action( 'pfbt_format_repaired', $post_id, $current, $suggested );

			++$repaired;
		}

		return array(
			'repaired' => $repaired,
			'skipped'  => $skipped,
			'errors'   => $errors,
		);
	}

	/**
	 * Register get-format-stats ability
	 *
	 * Returns post counts per format.
	 *
	 * @since 1.2.0
	 */
	private function register_get_format_stats() {
		wp_register_ability(
			'post-formats/get-format-stats',
			array(
				'label'               => __( 'Get Format Statistics', 'post-formats-for-block-themes' ),
				'description'         => __( 'Get the number of published posts for each post format.', 'post-formats-for-block-themes' ),
				'type'                => 'resource',
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => new stdClass(),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'formats' => array(
							'type'                 => 'object',
							'description'          => __( 'Map of format slug to post count.', 'post-formats-for-block-themes' ),
							'additionalProperties' => array( 'type' => 'integer' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_format_stats' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array(
						'public' => true,
						'type'   => 'resource',
					),
				),
			)
		);
	}

	/**
	 * Execute get-format-stats ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments (unused).
	 * @return array Format statistics.
	 */
	public function execute_get_format_stats( $args ) {
		$counts  = array();
		$formats = PFBT_Format_Registry::get_all_formats();

		// Get all format term counts in one query.
		$terms = get_terms(
			array(
				'taxonomy'   => 'post_format',
				'hide_empty' => false,
			)
		);

		$term_counts    = array();
		$formatted_total = 0;

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				// Term slugs are like "post-format-image".
				$format_slug = str_replace( 'post-format-', '', $term->slug );

				$term_counts[ $format_slug ] = (int) $term->count;
				$formatted_total            += (int) $term->count;
			}
		}

		// Total published posts.
		$total_posts = wp_count_posts( 'post' );
		$total       = (int) ( $total_posts->publish ?? 0 );

		foreach ( $formats as $slug => $format ) {
			if ( 'standard' === $slug ) {
				// Standard = total published minus all formatted.
				$counts[ $slug ] = max( 0, $total - $formatted_total );
			} else {
				$counts[ $slug ] = $term_counts[ $slug ] ?? 0;
			}
		}

		return array(
			'formats' => $counts,
		);
	}
}
