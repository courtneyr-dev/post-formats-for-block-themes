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
		// 2.0: body_class addition moved to PFBT_Format_Classes (covers
		// both body_class and post_class, adds plugin-namespaced hooks
		// and the .pfbt-format-titleless aggregate). The legacy method
		// add_format_body_classes() remains as a deprecated stub for
		// backward compatibility with code that called it directly.
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'merge_theme_json' ) );
		add_filter( 'get_block_templates', array( __CLASS__, 'add_block_templates' ), 10, 3 );
		add_filter( 'pre_get_block_file_template', array( __CLASS__, 'get_block_file_template' ), 10, 3 );
		// Taxonomy handler disabled - causes conflicts with REST API handler
		// add_action( 'set_object_terms', array( __CLASS__, 'on_format_change' ), 10, 6 );
		add_action( 'rest_after_insert_post', array( __CLASS__, 'rest_assign_template' ), 999, 3 );
		add_action( 'rest_insert_post', array( __CLASS__, 'rest_assign_template_early' ), 1, 3 );
		add_filter( 'rest_wp_template_query', array( __CLASS__, 'filter_rest_template_query' ), 10, 2 );
		add_filter( 'rest_prepare_wp_template', array( __CLASS__, 'hide_format_templates_from_rest' ), 10, 3 );
		add_filter( 'rest_prepare_post', array( __CLASS__, 'filter_post_rest_response' ), 10, 3 );
		add_filter( 'default_page_template_title', array( __CLASS__, 'fix_template_display_name' ), 10, 2 );
		add_action( 'updated_post_meta', array( __CLASS__, 'watch_template_meta_changes' ), 10, 4 );
	}

	/**
	 * Early template handler - runs BEFORE anything else can set template
	 *
	 * @since 1.1.1
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a post, false when updating.
	 */
	public static function rest_assign_template_early( $post, $request, $creating ) {
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// If format param is in request and it's being set to standard/empty
		if ( $request->has_param( 'format' ) ) {
			$format = $request->get_param( 'format' );
			// Standard format (false, empty, or 'standard' string)
			if ( empty( $format ) || 'standard' === $format || false === $format ) {
				// Force delete any format template immediately
				delete_post_meta( $post->ID, '_wp_page_template' );
				error_log( "PFBT EARLY: Cleared template for post {$post->ID} due to standard format in request" );
			}
		}
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

		error_log( "PFBT REST: === START rest_assign_template for post {$post->ID} ===" );
		error_log( "PFBT REST: Request has 'format' param: " . ( $request->has_param( 'format' ) ? 'YES (' . $request->get_param( 'format' ) . ')' : 'NO' ) );
		error_log( "PFBT REST: Request has 'template' param: " . ( $request->has_param( 'template' ) ? 'YES (' . $request->get_param( 'template' ) . ')' : 'NO' ) );

		// Check if user manually selected a template in this request
		if ( $request->has_param( 'template' ) ) {
			$manual_template = $request->get_param( 'template' );

			if ( '' === $manual_template || 'default' === $manual_template ) {
				// User selected "Default" or cleared the template
				delete_post_meta( $post->ID, '_wp_page_template' );
				delete_post_meta( $post->ID, '_pfbt_manual_template' );
				delete_post_meta( $post->ID, '_pfbt_manual_template_slug' );
				error_log( "PFBT REST: User selected 'Default' template for post {$post->ID}, cleared template assignment and override flags" );
			} else {
				// User manually selected a specific template
				update_post_meta( $post->ID, '_pfbt_manual_template', true );
				update_post_meta( $post->ID, '_pfbt_manual_template_slug', $manual_template );
				error_log( "PFBT REST: User manually selected template '{$manual_template}' for post {$post->ID}, set override flag" );

				// Set the template meta (WordPress doesn't do this automatically for custom template values)
				update_post_meta( $post->ID, '_wp_page_template', $manual_template );
			}

			error_log( 'PFBT REST: === END (manual template in request) ===' );
			return;
		}

		// Check if user has manual override active
		$has_manual_override = get_post_meta( $post->ID, '_pfbt_manual_template', true );

		if ( $has_manual_override ) {
			// User has taken control, don't auto-assign
			$manual_template_slug = get_post_meta( $post->ID, '_pfbt_manual_template_slug', true );
			error_log( "PFBT REST: Manual override active for post {$post->ID}, keeping template: {$manual_template_slug}" );
			error_log( 'PFBT REST: === END (manual override active) ===' );
			return;
		}

		// Get the ACTUAL current format from the post (after WordPress has processed it)
		$current_format   = get_post_format( $post->ID );
		$raw_format_value = $current_format;

		// Normalize - WordPress stores standard as false/empty
		$current_format = empty( $current_format ) || false === $current_format ? 'standard' : $current_format;

		error_log( 'PFBT REST: get_post_format() returned: ' . var_export( $raw_format_value, true ) );
		error_log( "PFBT REST: Normalized format: {$current_format}" );

		// Get current template assignment
		$current_template = get_post_meta( $post->ID, '_wp_page_template', true );
		error_log( 'PFBT REST: Current template meta: ' . ( $current_template ? $current_template : 'EMPTY' ) );

		// Auto-assign template based on format (no manual override)
		if ( 'standard' === $current_format ) {
			// Standard format should NEVER have a format template
			if ( $current_template && strpos( $current_template, 'single-format-' ) === 0 ) {
				delete_post_meta( $post->ID, '_wp_page_template' );
				error_log( "PFBT REST: ❌ CLEARED format template for standard post {$post->ID}, was: {$current_template}" );

				// Verify it was actually deleted
				$verify = get_post_meta( $post->ID, '_wp_page_template', true );
				error_log( 'PFBT REST: After delete, template meta is now: ' . ( $verify ? $verify : 'EMPTY' ) );
			} elseif ( $current_template && strpos( $current_template, 'single-format-' ) !== 0 ) {
				error_log( "PFBT REST: ⚠️ Standard post {$post->ID} has non-format template: {$current_template} (leaving as-is)" );
			} else {
				error_log( "PFBT REST: ✅ Post {$post->ID} is standard and has no template (correct)" );
			}
		} else {
			// Format post should have matching format template
			$expected_template = 'single-format-' . $current_format;
			if ( $current_template !== $expected_template ) {
				update_post_meta( $post->ID, '_wp_page_template', $expected_template );
				error_log( "PFBT REST: ✅ Auto-assigned template {$expected_template} for post {$post->ID} (format: {$current_format})" );
			} else {
				error_log( "PFBT REST: ✅ Post {$post->ID} already has correct template: {$expected_template}" );
			}
		}

		error_log( 'PFBT REST: === END rest_assign_template ===' );
	}

	/**
	 * Watch for template meta changes to debug what's setting it
	 *
	 * @since 1.1.1
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $object_id  Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 */
	public static function watch_template_meta_changes( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( '_wp_page_template' === $meta_key ) {
			$post = get_post( $object_id );
			if ( $post && 'post' === $post->post_type ) {
				$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 );
				$caller    = isset( $backtrace[3] ) ? $backtrace[3]['function'] : 'unknown';
				error_log( "PFBT WATCH: Template meta updated for post {$object_id} to '{$meta_value}' by {$caller}" );
			}
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
	 * Filter post REST API response to fix template display
	 *
	 * Ensures the template field correctly shows 'default' when no template is assigned.
	 *
	 * @since 1.1.1
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response Modified response.
	 */
	public static function filter_post_rest_response( $response, $post, $request ) {
		// Only handle posts
		if ( 'post' !== $post->post_type ) {
			return $response;
		}

		$template_meta        = get_post_meta( $post->ID, '_wp_page_template', true );
		$template_in_response = $response->data['template'] ?? '';

		// If template meta is empty but response has a template, fix it.
		if ( empty( $template_meta ) && ! empty( $template_in_response ) ) {
			$response->data['template'] = 'default';
		} elseif ( empty( $template_meta ) ) {
			// If template meta is empty, ensure response shows 'default'.
			$response->data['template'] = 'default';
			error_log( "PFBT POST REST: ✅ Set template to 'default' for post {$post->ID}" );
		}

		return $response;
	}

	/**
	 * Filter REST API template query parameters
	 *
	 * Ensures the 'single' template is included when querying templates for posts.
	 *
	 * @since 1.1.1
	 * @param array           $args    Query parameters.
	 * @param WP_REST_Request $request REST request object.
	 * @return array Modified query parameters.
	 */
	public static function filter_rest_template_query( $args, $request ) {
		error_log( 'PFBT REST QUERY: Template query args: ' . print_r( $args, true ) );

		// If querying for post templates, ensure 'single' is explicitly included
		if ( isset( $args['post_type'] ) && 'post' === $args['post_type'] ) {
			// If slug__in is set and doesn't include 'single', add it
			if ( isset( $args['slug__in'] ) && is_array( $args['slug__in'] ) ) {
				if ( ! in_array( 'single', $args['slug__in'], true ) ) {
					$args['slug__in'][] = 'single';
					error_log( "PFBT REST QUERY: Added 'single' to slug__in array" );
				}
			} else {
				// No slug__in set, create one with single
				$args['slug__in'] = array( 'single' );
				error_log( "PFBT REST QUERY: Created slug__in array with 'single'" );
			}
		}

		return $args;
	}

	/**
	 * Add metadata to format templates in REST API response
	 *
	 * Adds classification metadata to help visually separate format templates
	 * from theme templates in the editor UI.
	 *
	 * @since 1.1.1
	 * @param WP_REST_Response  $response The response object.
	 * @param WP_Block_Template $template The template object.
	 * @param WP_REST_Request   $request The request object.
	 * @return WP_REST_Response Modified response with template metadata.
	 */
	public static function hide_format_templates_from_rest( $response, $template, $request ) {
		// Log ALL templates going through REST API
		error_log( "PFBT REST FILTER: Processing template '{$template->slug}' for REST response" );

		// Check if this is the "Default" pseudo-template
		if ( 'default' === $template->slug ) {
			$response->data['pfbt_template_type']     = 'default';
			$response->data['pfbt_is_default_option'] = true;
		} elseif ( strpos( $template->slug, 'single-format-' ) === 0 ) {
			// Check if this is a format template.
			// Add classification metadata for visual separation
			$response->data['pfbt_template_type']   = 'format';
			$response->data['pfbt_format_name']     = str_replace( 'single-format-', '', $template->slug );
			$response->data['pfbt_auto_applicable'] = true;

			error_log( "PFBT REST: Added metadata to format template {$template->slug}" );
		} else {
			// Theme template
			$response->data['pfbt_template_type'] = 'theme';
			error_log( "PFBT REST: Marked as theme template: {$template->slug}" );
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
			$format      = str_replace( 'single-format-', '', $template );
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
	 * @since 1.2.4 Added `pfbt_register_format_templates` filter for opt-out.
	 *
	 * Themes that ship their own `single` template + format-specific
	 * styling can opt out by returning false:
	 *
	 *     add_filter( 'pfbt_register_format_templates', '__return_false' );
	 *
	 * Without opt-out this function does three things on every
	 * `get_block_templates` query: (1) ensures `single` is in results
	 * via `array_unshift` (which can outrank `front-page` / `page-X` /
	 * `home` in the renderer's first-match resolution and cause non-post
	 * URLs to render with `single.html`), (2) adds a "Default" pseudo-
	 * template for the editor, and (3) registers the 9 `single-format-*`
	 * templates. Heavily-themed sites that already cover these surfaces
	 * see issues like the homepage rendering with `single` markup.
	 *
	 * @param WP_Block_Template[] $query_result Array of found block templates.
	 * @param array               $query        Arguments to retrieve templates.
	 * @param string              $template_type Template type: 'wp_template' or 'wp_template_part'.
	 * @return WP_Block_Template[] Modified array of block templates.
	 */
	public static function add_block_templates( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		/**
		 * Whether to register the plugin's `single` + format-specific templates.
		 *
		 * @since 1.2.4
		 *
		 * @param bool $register Default true. Return false to skip the
		 *                       entire add_block_templates() body so the
		 *                       theme's own template hierarchy is used
		 *                       unmodified.
		 */
		if ( ! apply_filters( 'pfbt_register_format_templates', true ) ) {
			return $query_result;
		}

		error_log( 'PFBT: add_block_templates called with ' . count( $query_result ) . ' templates' );
		error_log( 'PFBT: Query params: ' . print_r( $query, true ) );

		// Log what templates we currently have
		$template_slugs = array_map(
			function ( $t ) {
				return $t->slug;
			},
			$query_result
		);
		error_log( 'PFBT: Current templates: ' . implode( ', ', $template_slugs ) );

		/*
		 * v1.2.5 — narrow the single-template injection to queries that
		 * actually want it.
		 *
		 * Prior versions ran the array_unshift unconditionally, which
		 * outranked `front-page` / `page-X` / `home` / `archive` in the
		 * block-template renderer's first-match resolution. Result:
		 * pages with their own resolved templates ended up rendering
		 * with `single.html` markup. The behavior is correct only when:
		 *
		 *   1. `slug__in` is empty (full editor template list query —
		 *      the editor's "Choose a Template" panel asks for everything),
		 *      OR
		 *   2. `slug__in` explicitly contains `single` (the editor or a
		 *      caller is looking for the single template specifically),
		 *      OR
		 *   3. `post_type` is `post` (editor querying templates assignable
		 *      to a post — which is what the original comment was for).
		 *
		 * For any other query (front-page resolution, page resolution,
		 * archive resolution, etc.), don't disturb the result order.
		 */
		$wants_single = false;
		if ( ! isset( $query['slug__in'] ) || empty( $query['slug__in'] ) ) {
			// No slug filter → editor's full template list query.
			$wants_single = true;
		} elseif ( is_array( $query['slug__in'] ) && in_array( 'single', $query['slug__in'], true ) ) {
			// Caller explicitly asks for single.
			$wants_single = true;
		} elseif ( isset( $query['post_type'] ) && 'post' === $query['post_type'] ) {
			// Editor querying templates for a post.
			$wants_single = true;
		}

		if ( $wants_single ) {
			// Ensure theme's single template is available for posts.
			// The theme's single template often doesn't have post_types set,
			// so it gets filtered out — fetch it and add it to results.
			$single_found = false;
			foreach ( $query_result as $template ) {
				if ( 'single' === $template->slug ) {
					$single_found = true;
					error_log( "PFBT: Found 'single' template in results" );
					// Ensure it's assigned to post type
					if ( ! isset( $template->post_types ) || ! is_array( $template->post_types ) ) {
						$template->post_types = array();
					}
					if ( ! in_array( 'post', $template->post_types, true ) ) {
						$template->post_types[] = 'post';
						error_log( "PFBT: Added 'post' to single template post_types" );
					}
					break;
				}
			}

			// If single template wasn't in the filtered results, fetch it and add it
			if ( ! $single_found ) {
				error_log( 'PFBT: Single template NOT found, attempting to fetch and add it' );

				// Try to get the single template directly from the theme
				// Avoid recursion by using remove_filter temporarily
				remove_filter( 'get_block_templates', array( __CLASS__, 'add_block_templates' ), 10 );
				$all_templates = get_block_templates( array(), $template_type );
				add_filter( 'get_block_templates', array( __CLASS__, 'add_block_templates' ), 10, 3 );

				error_log( 'PFBT: Fetched ' . count( $all_templates ) . ' templates without filter' );

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
						$single_found = true; // Mark as found
						error_log( "PFBT: Successfully added 'single' template to results" );
						break;
					}
				}

				if ( ! $single_found ) {
					error_log( "PFBT: WARNING - Could not find 'single' template even after fetching all templates" );
				}
			}
		} else {
			error_log( 'PFBT: Skipped single-template injection — query does not target it (slug__in=' . wp_json_encode( $query['slug__in'] ?? null ) . ', post_type=' . ( $query['post_type'] ?? 'n/a' ) . ')' );
		}

		// Add "Default" pseudo-template option ONLY in admin
		// This allows users to explicitly clear template assignment in the editor
		// Skip on front-end to avoid "Undefined array key" errors in block-template.php
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			$default_exists = false;
			foreach ( $query_result as $existing_template ) {
				if ( 'default' === $existing_template->slug ) {
					$default_exists = true;
					break;
				}
			}

			if ( ! $default_exists ) {
				$default_template                 = new WP_Block_Template();
				$default_template->slug           = 'default';
				$default_template->id             = get_stylesheet() . '//default';
				$default_template->theme          = get_stylesheet();
				$default_template->content        = '';
				$default_template->source         = 'plugin';
				$default_template->type           = 'wp_template';
				$default_template->title          = __( 'Default', 'post-formats-for-block-themes' );
				$default_template->description    = __( 'Use default template hierarchy (no specific template)', 'post-formats-for-block-themes' );
				$default_template->status         = 'publish';
				$default_template->has_theme_file = false;
				$default_template->is_custom      = false;
				$default_template->post_types     = array( 'post' );
				$default_template->author         = null;
				$default_template->origin         = 'plugin';

				// Add at the very beginning so it's the first option
				array_unshift( $query_result, $default_template );
			}
		}

		// Add format templates to BOTH post editor and site editor
		// Visual separation will be handled by CSS styling
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

		// Log final templates being returned
		$final_slugs = array_map(
			function ( $t ) {
				return $t->slug;
			},
			$query_result
		);
		error_log( 'PFBT: Returning ' . count( $query_result ) . ' templates: ' . implode( ', ', $final_slugs ) );

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
	 * Merge plugin theme.json with the theme's theme.json data.
	 *
	 * This makes the format colors and templates appear in the Site Editor
	 * without clobbering any contributions from the active theme. The merge
	 * is additive: palette and gradient entries are added if their slugs do
	 * not already exist; custom templates and template parts are added if
	 * their names do not already exist; styles are deep-merged with theme
	 * values winning on collisions.
	 *
	 * @since 1.0.0
	 * @since 1.2.1 Fix: was wholesale-replacing theme data via update_with().
	 * @since 1.2.3 Added `pfbt_merge_format_palette` filter for opt-out.
	 *
	 * Themes whose palette is fully bespoke can opt out by returning false:
	 *
	 *     add_filter( 'pfbt_merge_format_palette', '__return_false' );
	 *
	 * The plugin's palette additions (format-aside-bg, format-link-border, etc.)
	 * are stock Gutenberg defaults intended as sensible defaults for
	 * un-customized themes. They appear in the WP color picker alongside any
	 * branded swatches, which can be visual clutter in heavily-customized themes.
	 *
	 * @param WP_Theme_JSON_Data $theme_json Theme JSON data.
	 * @return WP_Theme_JSON_Data Modified theme JSON data.
	 */
	public static function merge_theme_json( $theme_json ) {
		/**
		 * Whether to merge the plugin's theme.json palette + settings into the
		 * site's resolved theme.json data.
		 *
		 * @since 1.2.3
		 *
		 * @param bool $merge Default true. Return false to skip the merge so
		 *                    the theme's palette is used unmodified.
		 */
		if ( ! apply_filters( 'pfbt_merge_format_palette', true ) ) {
			return $theme_json;
		}

		$plugin_theme_json_file = PFBT_PLUGIN_DIR . 'theme.json';

		if ( ! file_exists( $plugin_theme_json_file ) ) {
			return $theme_json;
		}

		$plugin_data = json_decode(
			file_get_contents( $plugin_theme_json_file ),
			true
		);

		if ( ! is_array( $plugin_data ) ) {
			return $theme_json;
		}

		$existing = $theme_json->get_data();
		$merged   = self::deep_merge_theme_json_data( $existing, $plugin_data );

		return $theme_json->update_with( $merged );
	}

	/**
	 * Additively merge plugin theme.json data into existing theme.json data.
	 *
	 * Theme values always win on slug/name collisions. Plugin values are added
	 * only when they do not collide with existing entries.
	 *
	 * @since 1.2.1
	 *
	 * @param array $existing Existing theme.json data (from theme + earlier filters).
	 * @param array $plugin   Plugin's own theme.json data.
	 * @return array Merged data.
	 */
	private static function deep_merge_theme_json_data( array $existing, array $plugin ): array {
		// Top-level scalars: $schema, version, title — theme wins.
		foreach ( array( '$schema', 'version', 'title' ) as $key ) {
			if ( ! isset( $existing[ $key ] ) && isset( $plugin[ $key ] ) ) {
				$existing[ $key ] = $plugin[ $key ];
			}
		}

		// settings.color.palette, gradients, duotone — additive merge by slug.
		foreach ( array( 'palette', 'gradients', 'duotone' ) as $color_key ) {
			$plugin_entries = $plugin['settings']['color'][ $color_key ] ?? null;
			if ( ! is_array( $plugin_entries ) || empty( $plugin_entries ) ) {
				continue;
			}

			if ( ! isset( $existing['settings'] ) ) {
				$existing['settings'] = array();
			}
			if ( ! isset( $existing['settings']['color'] ) ) {
				$existing['settings']['color'] = array();
			}

			$existing_entries = $existing['settings']['color'][ $color_key ] ?? array();
			$existing_slugs   = is_array( $existing_entries )
				? array_filter( array_column( $existing_entries, 'slug' ) )
				: array();

			foreach ( $plugin_entries as $entry ) {
				if ( ! is_array( $entry ) || ! isset( $entry['slug'] ) ) {
					continue;
				}
				if ( ! in_array( $entry['slug'], $existing_slugs, true ) ) {
					$existing_entries[] = $entry;
					$existing_slugs[]   = $entry['slug'];
				}
			}

			$existing['settings']['color'][ $color_key ] = $existing_entries;
		}

		// styles — deep merge, theme values win on scalar collisions.
		if ( isset( $plugin['styles'] ) && is_array( $plugin['styles'] ) ) {
			if ( ! isset( $existing['styles'] ) || ! is_array( $existing['styles'] ) ) {
				$existing['styles'] = $plugin['styles'];
			} else {
				// array_replace_recursive merges nested arrays; $existing is second so theme keys win.
				$existing['styles'] = array_replace_recursive(
					$plugin['styles'],
					$existing['styles']
				);
			}
		}

		// customTemplates and templateParts — additive merge by name.
		foreach ( array( 'customTemplates', 'templateParts' ) as $list_key ) {
			$plugin_items = $plugin[ $list_key ] ?? null;
			if ( ! is_array( $plugin_items ) || empty( $plugin_items ) ) {
				continue;
			}

			$existing_items = $existing[ $list_key ] ?? array();
			$existing_names = is_array( $existing_items )
				? array_filter( array_column( $existing_items, 'name' ) )
				: array();

			foreach ( $plugin_items as $item ) {
				if ( ! is_array( $item ) || ! isset( $item['name'] ) ) {
					continue;
				}
				if ( ! in_array( $item['name'], $existing_names, true ) ) {
					$existing_items[] = $item;
					$existing_names[] = $item['name'];
				}
			}

			$existing[ $list_key ] = $existing_items;
		}

		return $existing;
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
