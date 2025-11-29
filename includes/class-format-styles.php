<?php
/**
 * Format Styles - Site Editor Integration
 *
 * Registers block styles and theme.json integration for per-format customization.
 * Allows users to customize each post format through the WordPress Site Editor.
 *
 * @package PostFormatsPowerUp
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
class PFPU_Format_Styles {

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
		add_action( 'save_post', array( __CLASS__, 'auto_assign_template' ), 10, 2 );
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
				'label' => __( 'Show Format Styles', 'post-formats-power-up' ),
			)
		);

		// Register styles for individual post format variations.
		$formats = PFPU_Format_Registry::get_all_formats();

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
						__( '%s Format Style', 'post-formats-power-up' ),
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

		$templates = array(
			'single-format-aside'   => __( 'Aside Format', 'post-formats-power-up' ),
			'single-format-gallery' => __( 'Gallery Format', 'post-formats-power-up' ),
			'single-format-link'    => __( 'Link Format', 'post-formats-power-up' ),
			'single-format-image'   => __( 'Image Format', 'post-formats-power-up' ),
			'single-format-quote'   => __( 'Quote Format', 'post-formats-power-up' ),
			'single-format-status'  => __( 'Status Format', 'post-formats-power-up' ),
			'single-format-video'   => __( 'Video Format', 'post-formats-power-up' ),
			'single-format-audio'   => __( 'Audio Format', 'post-formats-power-up' ),
			'single-format-chat'    => __( 'Chat Format', 'post-formats-power-up' ),
		);

		foreach ( $templates as $slug => $title ) {
			$template_file = PFPU_PLUGIN_DIR . 'templates/' . $slug . '.html';

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
			$template->id          = 'post-formats-power-up//' . $slug;
			$template->theme       = get_stylesheet();
			$template->content     = file_get_contents( $template_file );
			$template->source      = 'plugin';
			$template->type        = 'wp_template';
			$template->title       = $title;
			$template->description = sprintf(
				/* translators: %s: Format name */
				__( 'Template for displaying %s posts', 'post-formats-power-up' ),
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

		$template_file = PFPU_PLUGIN_DIR . 'templates/' . $slug . '.html';

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
		$plugin_theme_json_file = PFPU_PLUGIN_DIR . 'theme.json';

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

		// Check if this template exists.
		$template_file = PFPU_PLUGIN_DIR . 'templates/' . $template_slug . '.html';

		if ( file_exists( $template_file ) ) {
			// Assign the template.
			update_post_meta( $post_id, '_wp_page_template', $template_slug );
		}
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
				'name'  => __( 'Aside Format', 'post-formats-power-up' ),
				'slug'  => 'format-aside',
				'color' => '#f0f0f1',
			),
			'status'  => array(
				'name'  => __( 'Status Format', 'post-formats-power-up' ),
				'slug'  => 'format-status',
				'color' => '#f0f0f1',
			),
			'link'    => array(
				'name'  => __( 'Link Format', 'post-formats-power-up' ),
				'slug'  => 'format-link',
				'color' => '#0073aa',
			),
			'quote'   => array(
				'name'  => __( 'Quote Format', 'post-formats-power-up' ),
				'slug'  => 'format-quote',
				'color' => '#0073aa',
			),
			'gallery' => array(
				'name'  => __( 'Gallery Format', 'post-formats-power-up' ),
				'slug'  => 'format-gallery',
				'color' => '#f0f0f1',
			),
			'image'   => array(
				'name'  => __( 'Image Format', 'post-formats-power-up' ),
				'slug'  => 'format-image',
				'color' => '#cccccc',
			),
			'video'   => array(
				'name'  => __( 'Video Format', 'post-formats-power-up' ),
				'slug'  => 'format-video',
				'color' => '#f0f0f1',
			),
			'audio'   => array(
				'name'  => __( 'Audio Format', 'post-formats-power-up' ),
				'slug'  => 'format-audio',
				'color' => '#f0f0f1',
			),
			'chat'    => array(
				'name'  => __( 'Chat Format', 'post-formats-power-up' ),
				'slug'  => 'format-chat',
				'color' => '#f0f0f1',
			),
		);
	}
}

// Initialize format styles.
PFPU_Format_Styles::init();
