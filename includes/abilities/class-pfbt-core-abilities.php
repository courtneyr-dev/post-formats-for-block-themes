<?php
/**
 * Core Abilities for Post Formats Block Themes
 *
 * Registers core post format abilities with the WordPress Abilities API.
 * Provides machine-readable operations for listing, getting, validating,
 * and setting post formats.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Abilities Provider
 *
 * Registers fundamental post format abilities:
 * - post-formats/list-formats - List available formats with metadata
 * - post-formats/get-format-template - Get template for a format
 * - post-formats/validate-format - Validate content for a format
 * - post-formats/set-post-format - Set format on a post
 * - post-formats/get-post-format - Get current format of a post
 * - post-formats/score-format - Score content against formats, with confidence
 *
 * @since 1.2.0
 */
class PFBT_Core_Abilities {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Core_Abilities|null
	 */
	private static $instance = null;

	/**
	 * Ability namespace prefix
	 *
	 * @var string
	 */
	const NAMESPACE = 'post-formats';

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Core_Abilities
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
	 * Register all core abilities
	 *
	 * @since 1.2.0
	 */
	public function register() {
		$this->register_list_formats();
		$this->register_get_format_template();
		$this->register_validate_format();
		$this->register_set_post_format();
		$this->register_get_post_format();
		$this->register_score_format();
	}

	/**
	 * Register list_formats ability
	 *
	 * Lists all available post formats with their metadata.
	 *
	 * @since 1.2.0
	 */
	private function register_list_formats() {
		wp_register_ability(
			self::NAMESPACE . '/list-formats',
			array(
				'label'               => __( 'List Post Formats', 'post-formats-for-block-themes' ),
				'description'         => __( 'Retrieve all available post formats with their metadata, templates, and usage statistics.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'include_templates' => array(
							'type'        => 'boolean',
							'default'     => true,
							'description' => __( 'Include template information for each format.', 'post-formats-for-block-themes' ),
						),
						'include_counts'    => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Include post counts for each format.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'formats' => array(
							'type'        => 'array',
							'description' => __( 'Array of format objects.', 'post-formats-for-block-themes' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'slug'          => array( 'type' => 'string' ),
									'name'          => array( 'type' => 'string' ),
									'description'   => array( 'type' => 'string' ),
									'icon'          => array( 'type' => 'string' ),
									'title_visible' => array( 'type' => 'boolean' ),
									'template'      => array( 'type' => 'string' ),
									'post_count'    => array( 'type' => 'integer' ),
								),
							),
						),
						'total'   => array(
							'type'        => 'integer',
							'description' => __( 'Total number of formats.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_list_formats' ),
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
	 * Execute list_formats ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array Output data.
	 */
	public function execute_list_formats( $args ) {
		$include_templates = $args['include_templates'] ?? true;
		$include_counts    = $args['include_counts'] ?? false;

		$formats      = PFBT_Format_Registry::get_all_formats();
		$format_array = array();

		foreach ( $formats as $slug => $format ) {
			$item = array(
				'slug'          => $slug,
				'name'          => $format['name'],
				'description'   => $format['description'],
				'icon'          => $format['icon'],
				'title_visible' => $format['title_visible'],
			);

			if ( $include_templates ) {
				$item['template'] = 'single-format-' . $slug;
				$item['pattern']  = $format['pattern_name'];
			}

			if ( $include_counts ) {
				$item['post_count'] = $this->get_format_post_count( $slug );
			}

			$format_array[] = $item;
		}

		return array(
			'formats' => $format_array,
			'total'   => count( $format_array ),
		);
	}

	/**
	 * Register get_format_template ability
	 *
	 * Gets template information for a specific format.
	 *
	 * @since 1.2.0
	 */
	private function register_get_format_template() {
		wp_register_ability(
			self::NAMESPACE . '/get-format-template',
			array(
				'label'               => __( 'Get Format Template', 'post-formats-for-block-themes' ),
				'description'         => __( 'Retrieve template and pattern information for a specific post format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'format' ),
					'properties' => array(
						'format'          => array(
							'type'        => 'string',
							'enum'        => array( 'standard', 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ),
							'description' => __( 'The post format slug.', 'post-formats-for-block-themes' ),
						),
						'include_content' => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Include the pattern block content.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'format'          => array( 'type' => 'string' ),
						'template_slug'   => array( 'type' => 'string' ),
						'pattern_name'    => array( 'type' => 'string' ),
						'pattern_content' => array( 'type' => 'string' ),
						'first_block'     => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_format_template' ),
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
	 * Execute get_format_template ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Output data or error.
	 */
	public function execute_get_format_template( $args ) {
		$format_slug = $args['format'];
		$format      = PFBT_Format_Registry::get_format( $format_slug );

		if ( ! $format ) {
			return new WP_Error(
				'invalid_format',
				__( 'Invalid post format specified.', 'post-formats-for-block-themes' ),
				array( 'status' => 400 )
			);
		}

		$result = array(
			'format'        => $format_slug,
			'template_slug' => 'single-format-' . $format_slug,
			'pattern_name'  => $format['pattern_name'],
			'first_block'   => $format['first_block'],
		);

		if ( ! empty( $args['include_content'] ) ) {
			$result['pattern_content'] = PFBT_Pattern_Manager::get_pattern( $format_slug );
		}

		return $result;
	}

	/**
	 * Register validate_format ability
	 *
	 * Validates content against format requirements.
	 *
	 * @since 1.2.0
	 */
	private function register_validate_format() {
		wp_register_ability(
			self::NAMESPACE . '/validate-format',
			array(
				'label'               => __( 'Validate Format Content', 'post-formats-for-block-themes' ),
				'description'         => __( 'Validate that content meets the requirements for a specific post format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'format', 'content' ),
					'properties' => array(
						'format'  => array(
							'type'        => 'string',
							'enum'        => array( 'standard', 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ),
							'description' => __( 'The target post format.', 'post-formats-for-block-themes' ),
						),
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The post content to validate (HTML or block markup).', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'valid'       => array( 'type' => 'boolean' ),
						'messages'    => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
						'suggestions' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_validate_format' ),
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
	 * Execute validate_format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array Validation result.
	 */
	public function execute_validate_format( $args ) {
		$format_slug = $args['format'];
		$content     = $args['content'];
		$format      = PFBT_Format_Registry::get_format( $format_slug );

		if ( ! $format ) {
			return array(
				'valid'       => false,
				'messages'    => array( __( 'Invalid format specified.', 'post-formats-for-block-themes' ) ),
				'suggestions' => array(),
			);
		}

		$messages    = array();
		$suggestions = array();
		$valid       = true;

		// Parse blocks from content.
		$blocks = parse_blocks( $content );
		$blocks = array_filter(
			$blocks,
			function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		);
		$blocks = array_values( $blocks );

		// Check first block requirement.
		if ( ! empty( $format['first_block'] ) && 'standard' !== $format_slug ) {
			if ( empty( $blocks ) ) {
				$valid      = false;
				$messages[] = sprintf(
					/* translators: %s: block name */
					__( 'Content should start with a %s block.', 'post-formats-for-block-themes' ),
					$format['first_block']
				);
			} elseif ( $blocks[0]['blockName'] !== $format['first_block'] ) {
				// Check fallback block.
				$fallback = $format['fallback_block'] ?? null;
				if ( ! $fallback || $blocks[0]['blockName'] !== $fallback ) {
					$valid      = false;
					$messages[] = sprintf(
						/* translators: %1$s: expected block, %2$s: actual block */
						__( 'Expected first block to be %1$s, found %2$s.', 'post-formats-for-block-themes' ),
						$format['first_block'],
						$blocks[0]['blockName']
					);
					$suggestions[] = sprintf(
						/* translators: %s: block name */
						__( 'Consider adding a %s block at the beginning.', 'post-formats-for-block-themes' ),
						$format['first_block']
					);
				}
			}
		}

		// Check character limit for status.
		if ( 'status' === $format_slug ) {
			$char_limit   = $format['char_limit'] ?? 280;
			$text_content = wp_strip_all_tags( $content );
			$char_count   = mb_strlen( $text_content );

			if ( $char_count > $char_limit ) {
				$messages[] = sprintf(
					/* translators: %1$d: character count, %2$d: character limit */
					__( 'Status content is %1$d characters. Recommended limit is %2$d.', 'post-formats-for-block-themes' ),
					$char_count,
					$char_limit
				);
				// This is a soft limit, so still valid.
				$suggestions[] = __( 'Consider shortening the content for better display.', 'post-formats-for-block-themes' );
			}
		}

		return array(
			'valid'       => $valid,
			'messages'    => $messages,
			'suggestions' => $suggestions,
		);
	}

	/**
	 * Register set_post_format ability
	 *
	 * Sets the format on a post.
	 *
	 * @since 1.2.0
	 */
	private function register_set_post_format() {
		wp_register_ability(
			self::NAMESPACE . '/set-post-format',
			array(
				'label'               => __( 'Set Post Format', 'post-formats-for-block-themes' ),
				'description'         => __( 'Set the post format for a specific post.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'post_id', 'format' ),
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'The post ID.', 'post-formats-for-block-themes' ),
						),
						'format'  => array(
							'type'        => 'string',
							'enum'        => array( 'standard', 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ),
							'description' => __( 'The post format to set.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'         => array( 'type' => 'boolean' ),
						'post_id'         => array( 'type' => 'integer' ),
						'format'          => array( 'type' => 'string' ),
						'previous_format' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_set_post_format' ),
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
	 * Execute set_post_format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Result or error.
	 */
	public function execute_set_post_format( $args ) {
		$post_id     = absint( $args['post_id'] );
		$format_slug = $args['format'];

		// Check post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}

		// Check user can edit this post.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'forbidden',
				__( 'You do not have permission to edit this post.', 'post-formats-for-block-themes' ),
				array( 'status' => 403 )
			);
		}

		// Get current format.
		$previous_format = get_post_format( $post_id );
		if ( false === $previous_format ) {
			$previous_format = 'standard';
		}

		// Set format (standard uses false).
		$format_value = 'standard' === $format_slug ? false : $format_slug;
		$result       = set_post_format( $post_id, $format_value );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Mark as manually set.
		update_post_meta( $post_id, '_pfbt_format_manual', true );

		/**
		 * Fires after a post format is changed via the Abilities API.
		 *
		 * @since 1.2.0
		 *
		 * @param int    $post_id         The post ID.
		 * @param string $previous_format The previous format slug.
		 * @param string $format_slug     The new format slug.
		 */
		do_action( 'pfbt_format_changed', $post_id, $previous_format, $format_slug );

		return array(
			'success'         => true,
			'post_id'         => $post_id,
			'format'          => $format_slug,
			'previous_format' => $previous_format,
		);
	}

	/**
	 * Register get_post_format ability
	 *
	 * Gets the current format of a post.
	 *
	 * @since 1.2.0
	 */
	private function register_get_post_format() {
		wp_register_ability(
			self::NAMESPACE . '/get-post-format',
			array(
				'label'               => __( 'Get Post Format', 'post-formats-for-block-themes' ),
				'description'         => __( 'Get the current format of a specific post.', 'post-formats-for-block-themes' ),
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
						'post_id'       => array( 'type' => 'integer' ),
						'format'        => array( 'type' => 'string' ),
						'format_name'   => array( 'type' => 'string' ),
						'manually_set'  => array( 'type' => 'boolean' ),
						'template_slug' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_post_format' ),
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
	 * Execute get_post_format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array|WP_Error Result or error.
	 */
	public function execute_get_post_format( $args ) {
		$post_id = absint( $args['post_id'] );

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'invalid_post',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}

		$format_slug = get_post_format( $post_id );
		if ( false === $format_slug ) {
			$format_slug = 'standard';
		}

		$format       = PFBT_Format_Registry::get_format( $format_slug );
		$manually_set = (bool) get_post_meta( $post_id, '_pfbt_format_manual', true );

		return array(
			'post_id'       => $post_id,
			'format'        => $format_slug,
			'format_name'   => $format ? $format['name'] : $format_slug,
			'manually_set'  => $manually_set,
			'template_slug' => 'single-format-' . $format_slug,
		);
	}

	/**
	 * Register score-format ability
	 *
	 * Analyzes content and returns the best-matching format along with a
	 * confidence score and the first block seen.
	 *
	 * Not named `detect-format`: PFBT_Abilities_Formats already registers
	 * `post-formats/detect-format`, which resolves a format from a post ID or
	 * raw content and returns no confidence. Two abilities cannot share a name
	 * — core rejects whichever registers second — and the scoring output is
	 * the difference worth keeping.
	 *
	 * @since 1.2.0
	 */
	private function register_score_format() {
		wp_register_ability(
			self::NAMESPACE . '/score-format',
			array(
				'label'               => __( 'Score Post Format', 'post-formats-for-block-themes' ),
				'description'         => __( 'Analyze content and detect the most appropriate post format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'content' ),
					'properties' => array(
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The post content to analyze (HTML or block markup).', 'post-formats-for-block-themes' ),
						),
						'title'   => array(
							'type'        => 'string',
							'description' => __( 'Optional post title for additional context.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'detected_format' => array( 'type' => 'string' ),
						'confidence'      => array( 'type' => 'number' ),
						'first_block'     => array( 'type' => 'string' ),
						'signals'         => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_score_format' ),
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
	 * Execute score-format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Input arguments.
	 * @return array Detection result.
	 */
	public function execute_score_format( $args ) {
		$content = $args['content'];
		$signals = array();

		// Parse blocks.
		$blocks = parse_blocks( $content );
		$blocks = array_filter(
			$blocks,
			function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		);
		$blocks = array_values( $blocks );

		if ( empty( $blocks ) ) {
			return array(
				'detected_format' => 'standard',
				'confidence'      => 0.5,
				'first_block'     => null,
				'signals'         => array( 'no_blocks_found' ),
			);
		}

		$first_block = $blocks[0];
		$block_name  = $first_block['blockName'];
		$block_attrs = $first_block['attrs'] ?? array();

		// Detect format from first block.
		$detected_format = PFBT_Format_Registry::get_format_by_block( $block_name, $block_attrs );
		$confidence      = 0.8;

		// Build signals.
		$signals[] = 'first_block:' . $block_name;

		// Special detection for media content.
		if ( preg_match_all( '/<img/', $content, $matches ) ) {
			$image_count = count( $matches[0] );
			if ( $image_count > 1 ) {
				$signals[] = 'multiple_images:' . $image_count;
				if ( 'gallery' !== $detected_format && 'image' !== $detected_format ) {
					$signals[] = 'suggest:gallery';
				}
			} elseif ( 1 === $image_count ) {
				$signals[] = 'single_image';
			}
		}

		// Detect URLs.
		if ( preg_match_all( '/https?:\/\/[^\s<]+/', $content, $matches ) ) {
			$url_count = count( $matches[0] );
			if ( $url_count > 2 ) {
				$signals[] = 'url_heavy:' . $url_count;
			}
		}

		// Detect quotes.
		if ( strpos( $content, '<blockquote' ) !== false ) {
			$signals[] = 'has_blockquote';
		}

		// Detect short content (status-like).
		$text_length = mb_strlen( wp_strip_all_tags( $content ) );
		if ( $text_length < 280 ) {
			$signals[] = 'short_content:' . $text_length;
		}

		return array(
			'detected_format' => $detected_format ? $detected_format : 'standard',
			'confidence'      => $confidence,
			'first_block'     => $block_name,
			'signals'         => $signals,
		);
	}

	/**
	 * Get post count for a format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format_slug Format slug.
	 * @return int Post count.
	 */
	private function get_format_post_count( $format_slug ) {
		if ( 'standard' === $format_slug ) {
			// Standard format = posts without any format term.
			$with_format = wp_count_posts( 'post' );
			$total       = $with_format->publish ?? 0;

			// Get counts for all other formats.
			$terms = get_terms(
				array(
					'taxonomy'   => 'post_format',
					'hide_empty' => false,
				)
			);

			$formatted_count = 0;
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$formatted_count += $term->count;
				}
			}

			return max( 0, $total - $formatted_count );
		}

		// For specific formats.
		$term = get_term_by( 'slug', 'post-format-' . $format_slug, 'post_format' );
		return $term ? $term->count : 0;
	}
}
