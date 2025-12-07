<?php
/**
 * Format Styles - Site Editor Integration
 *
 * Registers block styles and theme.json integration for per-format customization.
 * Allows users to customize each post format through the WordPress Site Editor.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format Styles Class
 *
 * Provides Site Editor integration for customizing post format appearance.
 *
 * @since 1.0.0
 */
class PFBT_Format_Styles {

	/**
	 * Initialize the format styles
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_block_styles' ) );
		add_filter( 'body_class', array( __CLASS__, 'add_format_body_classes' ) );
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'merge_theme_json' ) );
		add_filter( 'get_block_templates', array( __CLASS__, 'add_block_templates' ), 10, 3 );
		add_filter( 'pre_get_block_file_template', array( __CLASS__, 'get_block_file_template' ), 10, 3 );
		add_action( 'set_object_terms', array( __CLASS__, 'on_format_change' ), 10, 6 );
		add_action( 'rest_after_insert_post', array( __CLASS__, 'rest_assign_template' ), 10, 3 );
		add_filter( 'rest_prepare_wp_template', array( __CLASS__, 'hide_format_templates_from_rest' ), 10, 3 );
		add_filter( 'default_page_template_title', array( __CLASS__, 'fix_template_display_name' ), 10, 3 );
	}

	/**
	 * Assign template via REST API (block editor)
	 *
	 * @since 1.0.0
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a post, false when updating.
	 */
	public static function rest_assign_template( $post, $request, $creating ) {
		// Only handle posts
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Check if user manually selected a template
		$manual_template = $request->get_param( 'template' );
		if ( ! empty( $manual_template ) ) {
			// If it's empty string, clear template
			if ( '' === $manual_template ) {
				delete_post_meta( $post->ID, '_wp_page_template' );
				return;
			}
			// Otherwise it's a regular template, let WordPress handle it
			return;
		}

		// Auto-assign based on format
		$format = $request->get_param( 'format' );

		// If no format in request, get from post
		if ( empty( $format ) ) {
			$format = get_post_format( $post->ID );
		}

		// Default to standard if still empty
		if ( empty( $format ) || false === $format ) {
			$format = 'standard';
		}

		// Assign template based on format
		if ( 'standard' === $format ) {
			delete_post_meta( $post->ID, '_wp_page_template' );
		} else {
			$template_slug = 'single-format-' . $format;
			// Store just the slug (not full ID) so WordPress editor can match it
			update_post_meta( $post->ID, '_wp_page_template', $template_slug );
		}
	}

	/**
	 * Handle format change via taxonomy
	 *
	 * Fires when post format taxonomy term is set
	 *
	 * @since 1.0.0
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object term IDs or slugs.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function on_format_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		// Only handle post_format taxonomy
		if ( 'post_format' !== $taxonomy ) {
			return;
		}

		// Get the post
		$post = get_post( $object_id );
		if ( ! $post || 'post' !== $post->post_type ) {
			return;
		}

		// Extract format from terms
		// Terms can be slugs like 'post-format-gallery' or empty array for standard
		$format = 'standard';
		if ( ! empty( $terms ) ) {
			// Get first term
			$term_slug = is_array( $terms ) ? reset( $terms ) : $terms;
			// Remove 'post-format-' prefix if present
			if ( is_string( $term_slug ) && strpos( $term_slug, 'post-format-' ) === 0 ) {
				$format = str_replace( 'post-format-', '', $term_slug );
			} else {
				// Might be a term ID, get the actual term
				$term = get_term( $term_slug, 'post_format' );
				if ( $term && ! is_wp_error( $term ) ) {
					$format = str_replace( 'post-format-', '', $term->slug );
				}
			}
		}

		// Assign template based on format
		if ( 'standard' === $format || empty( $format ) ) {
			delete_post_meta( $object_id, '_wp_page_template' );
		} else {
			$template_slug = 'single-format-' . $format;
			// Store just the slug (not full ID) so WordPress editor can match it
			update_post_meta( $object_id, '_wp_page_template', $template_slug );
		}
	}

	/**
	 * Hide format templates from REST API template list
	 *
	 * Format templates are assigned automatically based on post format,
	 * so they shouldn't appear in the template dropdown in the editor.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Block_Template $template The template object.
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|null Modified response or null to hide.
	 */
	public static function hide_format_templates_from_rest( $response, $template, $request ) {
		// Check if this is a format template
		if ( strpos( $template->slug, 'single-format-' ) === 0 ) {
			// Check if this request is for the editor (not Site Editor)
			$context = $request->get_param( 'context' );

			// Hide from editor context (template dropdown)
			// but allow in Site Editor context (for customization)
			if ( 'edit' === $context ) {
				return null; // Return null to exclude from response
			}
		}

		return $response;
	}

	/**
	 * Fix template display name to show correct format template
	 *
	 * This ensures the template dropdown shows the correct template name
	 * that matches the post's format.
	 *
	 * @since 1.0.0
	 * @param string $title Template title.
	 * @param string $template Template slug.
	 * @return string Modified title.
	 */
	public static function fix_template_display_name( $title, $template ) {
		// Only for posts with format templates
		if ( strpos( $template, 'single-format-' ) === 0 ) {
			$format = str_replace( 'single-format-', '', $template );
			$format_name = ucfirst( $format );
			return sprintf( '%s Format', $format_name );
		}

		return $title;
	}

	/**
	 * Register block styles for each post format
	 *
	 * These styles can be selected in the Site Editor's Styles panel.
	 *
	 * @since 1.0.0
	 */
	public static function register_block_styles() {
		// Register styles for Post Template block (used in Query Loops).
		register_block_style(
			'core/post-template',
			array(
				'name'  => 'format-aware',
				'label' => __( 'Show Format Styles', 'post-formats-for-block-themes' ),
			)
		);

		// Register styles for individual post format variations.
		$formats = PFBT_Format_Registry::get_all_formats();

		foreach ( $formats as $slug => $format_data ) {
			// Skip standard format.
			if ( 'standard' === $slug ) {
				continue;
			}

			// Register for Post Content block.
			register_block_style(
				'core/post-content',
				array(
					'name'  => 'format-' . $slug,
					'label' => sprintf(
						/* translators: %s: Format name */
						__( '%s Format Style', 'post-formats-for-block-themes' ),
						$format_data['name']
					),
				)
			);
		}
	}

	/**
	 * Add format templates to the block templates list
	 *
	 * @since 1.0.0
	 * @param WP_Block_Template[] $query_result Array of found block templates.
	 * @param array               $query        Arguments to retrieve templates.
	 * @param string              $template_type Template type: 'wp_template' or 'wp_template_part'.
	 * @return WP_Block_Template[] Modified array of block templates.
	 */
	public static function add_block_templates( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		// Ensure theme's single template is available for posts
		// The theme's single template often doesn't have post_types set, so it gets filtered out
		// We need to explicitly fetch it and add it to the results
		$single_found = false;
		foreach ( $query_result as $template ) {
			if ( 'single' === $template->slug ) {
				$single_found = true;
				// Ensure it's assigned to post type
				if ( ! isset( $template->post_types ) || ! is_array( $template->post_types ) ) {
					$template->post_types = array();
				}
				if ( ! in_array( 'post', $template->post_types, true ) ) {
					$template->post_types[] = 'post';
				}
				break;
			}
		}

		// If single template wasn't in the filtered results, fetch it and add it
		if ( ! $single_found ) {
			// Get all templates without post_type filter
			$all_templates = get_block_templates( array(), $template_type );
			foreach ( $all_templates as $template ) {
				if ( 'single' === $template->slug ) {
					// Clone the template and set post_types
					$single_template = clone $template;
					if ( ! isset( $single_template->post_types ) || ! is_array( $single_template->post_types ) ) {
						$single_template->post_types = array();
					}
					if ( ! in_array( 'post', $single_template->post_types, true ) ) {
						$single_template->post_types[] = 'post';
					}
					// Add it to the beginning of results so it's the default
					array_unshift( $query_result, $single_template );
					break;
				}
			}
		}

		$templates = array(
			'single-format-aside'   => __( 'Aside Format', 'post-formats-for-block-themes' ),
			'single-format-gallery' => __( 'Gallery Format', 'post-formats-for-block-themes' ),
			'single-format-link'    => __( 'Link Format', 'post-formats-for-block-themes' ),
			'single-format-image'   => __( 'Image Format', 'post-formats-for-block-themes' ),
			'single-format-quote'   => __( 'Quote Format', 'post-formats-for-block-themes' ),
			'single-format-status'  => __( 'Status Format', 'post-formats-for-block-themes' ),
			'single-format-video'   => __( 'Video Format', 'post-formats-for-block-themes' ),
			'single-format-audio'   => __( 'Audio Format', 'post-formats-for-block-themes' ),
			'single-format-chat'    => __( 'Chat Format', 'post-formats-for-block-themes' ),
		);

		foreach ( $templates as $slug => $title ) {
			$template_file = PFBT_PLUGIN_DIR . 'templates/' . $slug . '.html';

			if ( ! file_exists( $template_file ) ) {
				continue;
			}

			// Check if this template already exists in the results.
			$template_exists = false;
			foreach ( $query_result as $existing_template ) {
				if ( $existing_template->slug === $slug ) {
					$template_exists = true;
					break;
				}
			}

			if ( $template_exists ) {
				continue;
			}

			$template              = new WP_Block_Template();
			$template->slug        = $slug;
			$template->id          = 'post-formats-for-block-themes//' . $slug;
			$template->theme       = get_stylesheet();
			$template->content     = file_get_contents( $template_file );
			$template->source      = 'plugin';
			$template->type        = 'wp_template';
			$template->title       = $title;
			$template->description = sprintf(
				/* translators: %s: Format name */
				__( 'Template for displaying %s posts', 'post-formats-for-block-themes' ),
				strtolower( $title )
			);
			$template->status         = 'publish';
			$template->has_theme_file = false;
			$template->is_custom      = true;
			$template->post_types     = array( 'post' );
			$template->author         = null;
			$template->origin         = 'plugin';

			$query_result[] = $template;
		}

		return $query_result;
	}

	/**
	 * Get block file template for format templates
	 *
	 * Allows WordPress to load plugin-provided templates for editing.
	 *
	 * @since 1.0.0
	 * @param WP_Block_Template|null $template      Template object or null.
	 * @param string                 $id            Template ID.
	 * @param string                 $template_type Template type.
	 * @return WP_Block_Template|null Template object or null.
	 */
	public static function get_block_file_template( $template, $id, $template_type ) {
		// Extract slug from ID (format: theme//slug).
		$slug = substr( $id, strpos( $id, '//' ) + 2 );

		// Check if this is one of our format templates.
		if ( strpos( $slug, 'single-format-' ) !== 0 ) {
			return $template;
		}

		$template_file = PFBT_PLUGIN_DIR . 'templates/' . $slug . '.html';

		if ( ! file_exists( $template_file ) ) {
			return $template;
		}

		$template                 = new WP_Block_Template();
		$template->id             = $id;
		$template->theme          = get_stylesheet();
		$template->content        = file_get_contents( $template_file );
		$template->slug           = $slug;
		$template->source         = 'plugin';
		$template->type           = $template_type;
		$template->title          = ucfirst( str_replace( array( 'single-format-', '-' ), array( '', ' ' ), $slug ) ) . ' Format';
		$template->status         = 'publish';
		$template->has_theme_file = false;
		$template->is_custom      = true;
		$template->post_types     = array( 'post' );

		return $template;
	}

	/**
	 * Merge plugin theme.json with theme's theme.json
	 *
	 * This makes the format colors appear in the Site Editor.
	 *
	 * @since 1.0.0
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON data.
	 * @return WP_Theme_JSON_Data Modified theme JSON data.
	 */
	public static function merge_theme_json( $theme_json ) {
		$plugin_theme_json_file = PFBT_PLUGIN_DIR . 'theme.json';

		if ( ! file_exists( $plugin_theme_json_file ) ) {
			return $theme_json;
		}

		$plugin_theme_json_data = json_decode(
			file_get_contents( $plugin_theme_json_file ),
			true
		);

		if ( ! $plugin_theme_json_data ) {
			return $theme_json;
		}

		return $theme_json->update_with( $plugin_theme_json_data );
	}

	/**
	 * Add format-specific body classes
	 *
	 * @since 1.0.0
	 * @param array $classes Body classes.
	 * @return array Modified body classes.
	 */
	public static function add_format_body_classes( $classes ) {
		if ( is_singular( 'post' ) ) {
			$format = get_post_format();
			if ( $format ) {
				$classes[] = 'has-post-format';
				$classes[] = 'format-' . $format;
			} else {
				$classes[] = 'format-standard';
			}
		}

		return $classes;
	}

	/**
	 * Automatically assign template based on post format
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function auto_assign_template( $post_id, $post ) {
		// Skip autosaves and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only for posts.
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Get the post format.
		$format = get_post_format( $post_id );

		// If no format or standard, don't assign a custom template.
		if ( ! $format || 'standard' === $format ) {
			delete_post_meta( $post_id, '_wp_page_template' );
			return;
		}

		// Map format to template slug.
		$template_slug = 'single-format-' . $format;

		// Store just the slug (not full ID) so WordPress editor can match it
		update_post_meta( $post_id, '_wp_page_template', $template_slug );
	}

	/**
	 * Get format color definitions for theme.json
	 *
	 * Returns an array of color definitions for each format.
	 *
	 * @since 1.0.0
	 * @return array Format color definitions.
	 */
	public static function get_format_colors() {
		return array(
			'aside'   => array(
				'name'  => __( 'Aside Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-aside',
				'color' => '#f0f0f1',
			),
			'status'  => array(
				'name'  => __( 'Status Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-status',
				'color' => '#f0f0f1',
			),
			'link'    => array(
				'name'  => __( 'Link Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-link',
				'color' => '#0073aa',
			),
			'quote'   => array(
				'name'  => __( 'Quote Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-quote',
				'color' => '#0073aa',
			),
			'gallery' => array(
				'name'  => __( 'Gallery Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-gallery',
				'color' => '#f0f0f1',
			),
			'image'   => array(
				'name'  => __( 'Image Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-image',
				'color' => '#cccccc',
			),
			'video'   => array(
				'name'  => __( 'Video Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-video',
				'color' => '#f0f0f1',
			),
			'audio'   => array(
				'name'  => __( 'Audio Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-audio',
				'color' => '#f0f0f1',
			),
			'chat'    => array(
				'name'  => __( 'Chat Format', 'post-formats-for-block-themes' ),
				'slug'  => 'format-chat',
				'color' => '#f0f0f1',
			),
		);
	}
}

// Initialize format styles.
PFBT_Format_Styles::init();
