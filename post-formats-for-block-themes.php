<?php
/**
 * Plugin Name: Post Formats for Block Themes
 * Plugin URI: https://wordpress.org/plugins/post-formats-for-block-themes/
 * Description: Modernizes WordPress post formats for block themes with format-specific patterns, auto-detection, and enhanced editor experience.
 * Version: 2.1.0
 * Requires at least: 6.9
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Author: Courtney Robertson
 * Author URI: https://profiles.wordpress.org/courane01/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: post-formats-for-block-themes
 * Domain Path: /languages
 *
 * @package PostFormatsBlockThemes
 *
 * Accessibility Implementation:
 * - All UI components use semantic HTML and ARIA labels
 * - Modal dialogs support keyboard navigation and focus management
 * - Format selection interface is fully keyboard accessible
 * - Editor components use WordPress accessible components
 * - All strings are translatable with proper text domain
 *
 * Translation Support:
 * - Text Domain: post-formats-for-block-themes
 * - All user-facing strings wrapped in translation functions
 * - JavaScript translations loaded via wp_set_script_translations()
 * - RTL language support included
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants
 */
define( 'PFBT_VERSION', '2.1.0' );
define( 'PFBT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PFBT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Register format template types VERY early to prevent warnings
 *
 * @since 1.0.0
 * @param array $template_types Registered template types.
 * @return array Modified template types.
 */
function pfbt_register_template_types_early( $template_types ) {
	$format_types = array(
		'single-format-aside',
		'single-format-gallery',
		'single-format-link',
		'single-format-image',
		'single-format-quote',
		'single-format-status',
		'single-format-video',
		'single-format-audio',
		'single-format-chat',
	);

	foreach ( $format_types as $type ) {
		if ( ! isset( $template_types[ $type ] ) ) {
			$format                  = str_replace( 'single-format-', '', $type );
			$format_name             = ucfirst( $format );
			$template_types[ $type ] = array(
				'title'       => sprintf( '%s Format', $format_name ),
				'description' => sprintf( 'Displays posts with the %s post format', $format_name ),
			);
		}
	}

	return $template_types;
}
add_filter( 'default_template_types', 'pfbt_register_template_types_early', 1 );

/**
 * Hide format templates from template chooser dropdown
 *
 * NOTE: This filtering is handled by the rest_prepare_wp_template filter
 * in includes/class-format-styles.php for block themes.
 * The theme_post_templates filter is only for classic themes and causes
 * conflicts with block theme template handling.
 *
 * @since 1.1.1
 */

/**
 * Suppress template type warnings from WordPress core timing issue
 *
 * WordPress core's get_block_templates() checks template types before
 * the default_template_types filter runs, causing "Undefined array key"
 * warnings for plugin-registered template types.
 *
 * This error handler is REQUIRED (not debug code) because:
 * - Prevents warnings from displaying to users during development
 * - Prevents modal overlapping issues caused by error output
 * - Only suppresses our specific template type warnings
 * - All other errors pass through normally
 *
 * This is a workaround for WordPress core issue, not a development tool.
 *
 * @since 1.0.0
 */
// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Required to suppress WordPress core timing issue with custom template types. See explanation above.
set_error_handler(
	function ( $errno, $errstr, $errfile, $errline ) {
		// Only suppress our specific template type warnings from WordPress core.
		if ( false !== strpos( $errfile, 'block-template.php' ) &&
			false !== strpos( $errstr, 'single-format-' ) &&
			( false !== strpos( $errstr, 'Undefined array key' ) ||
				false !== strpos( $errstr, 'Undefined index' ) ) ) {
			return true; // Suppress only this specific warning.
		}
		return false; // Let all other errors through normally.
	},
	E_WARNING
);

/**
 * Load plugin text domain for translations
 *
 * Note: WordPress.org automatically loads translations for plugins,
 * so load_plugin_textdomain() is not needed when hosted on WordPress.org.
 *
 * @since 1.0.0
 * @deprecated WordPress handles translations automatically
 */

/**
 * Include required files
 *
 * @since 1.0.0
 */
function pfbt_include_files() {
	require_once PFBT_PLUGIN_DIR . 'includes/class-format-registry.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-format-detector.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-pattern-manager.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-block-locker.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-repair-tool.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-media-player-integration.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-format-styles.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-format-classes.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-link-meta.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-format-helpers.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-block-templates.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-admin-columns.php';

	// Feature flags and Abilities API (v1.2.0+).
	require_once PFBT_PLUGIN_DIR . 'includes/class-pfbt-feature-flags.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-pfbt-abilities-manager.php';
	require_once PFBT_PLUGIN_DIR . 'includes/class-abilities-formats.php';

	// IndieWeb integrations (v1.2.0+).
	require_once PFBT_PLUGIN_DIR . 'includes/mf2/class-pfbt-format-mf2.php';
	require_once PFBT_PLUGIN_DIR . 'includes/webmention/class-pfbt-webmention-context.php';
	require_once PFBT_PLUGIN_DIR . 'includes/posse/class-pfbt-posse-transformer.php';

	// ActivityPub integration (v1.2.0+).
	require_once PFBT_PLUGIN_DIR . 'includes/activitypub/class-pfbt-activitypub-transformer.php';

	// Post Kinds integration (v1.2.0+).
	require_once PFBT_PLUGIN_DIR . 'includes/post-kinds/class-pfbt-post-kinds-integration.php';

	// Block Bindings source (v1.2.0+).
	require_once PFBT_PLUGIN_DIR . 'includes/class-block-bindings-formats.php';

	// Image + Gallery block style variations (v2.1.0+, opt-in).
	require_once PFBT_PLUGIN_DIR . 'includes/class-pfbt-block-style-registry.php';

	// Format Badge block (v1.2.0+).
	require_once PFBT_PLUGIN_DIR . 'blocks/format-badge/format-badge.php';

	// Format Icon block (v2.0+).
	// Registration in index.php; render template in format-icon.php (loaded
	// by WordPress per render via block.json's render directive).
	require_once PFBT_PLUGIN_DIR . 'blocks/format-icon/index.php';

	// Include Chat Log block (integrated).
	// This provides the chatlog/conversation block for the Chat post format.
	require_once PFBT_PLUGIN_DIR . 'blocks/chatlog/chatlog-block.php';

	// Include Post Format Block (integrated).
	// Forked from: https://wordpress.org/plugins/post-format-block/
	// This provides a block variation to display post formats in block themes.
	require_once PFBT_PLUGIN_DIR . 'blocks/post-format-block/post-format-block.php';
}
add_action( 'plugins_loaded', 'pfbt_include_files' );

/**
 * Initialize plugin
 *
 * Registers theme support for all 10 post formats and initializes
 * plugin components. Safely merges with existing theme format support
 * to avoid conflicts.
 *
 * @since 1.0.0
 */
function pfbt_init() {
	// Get existing post format support from theme.
	$existing_formats = get_theme_support( 'post-formats' );

	// Plugin's required formats.
	$plugin_formats = array(
		'aside',
		'gallery',
		'link',
		'image',
		'quote',
		'status',
		'video',
		'audio',
		'chat',
	);

	if ( false === $existing_formats ) {
		// Theme has no post format support - add all plugin formats.
		add_theme_support( 'post-formats', $plugin_formats );
	} else {
		// Theme has some post format support - merge with plugin formats.
		$theme_formats  = is_array( $existing_formats[0] ) ? $existing_formats[0] : array();
		$merged_formats = array_unique( array_merge( $theme_formats, $plugin_formats ) );
		add_theme_support( 'post-formats', $merged_formats );
	}

	// Initialize plugin classes.
	PFBT_Format_Registry::instance();
	PFBT_Format_Detector::instance();
	PFBT_Pattern_Manager::instance();
	PFBT_Block_Locker::instance();
	PFBT_Admin_Columns::instance();

	// 2.0: post + body class additions (replaces the body_class filter
	// in PFBT_Format_Styles::init(), which is now a deprecated stub).
	PFBT_Format_Classes::instance();

	// 2.0: register _pfbt_link_url post meta + Bookmark Card fallback.
	// Wires the external-URL meta that the link display pattern's
	// post-title binds to via Block Bindings (key: link_url).
	PFBT_Link_Meta::instance();

	// 2.0: opt-in single + archive block templates per format. Gated
	// behind the pfbt_use_block_templates option (default false) +
	// matching filter. Inactive when option is off — no hierarchy
	// disruption, no template registrations.
	PFBT_Block_Templates::instance();

	// Initialize Block Bindings source (v1.2.0+).
	if ( PFBT_Feature_Flags::has_block_bindings() ) {
		PFBT_Block_Bindings_Formats::instance();
	}

	// Initialize Image + Gallery block style variations (v2.1.0+).
	// Opt-in via the image_gallery_styles feature flag — registry is
	// only instantiated when the flag is on, so installs that don't
	// enable it pay zero registration cost.
	if ( PFBT_Feature_Flags::has_image_gallery_styles() ) {
		PFBT_Block_Style_Registry::instance()->init();
	}

	// Initialize Abilities API integration (v1.2.0+).
	if ( PFBT_Feature_Flags::has_abilities_api() ) {
		PFBT_Abilities_Manager::instance();
	}

	// Initialize IndieWeb integration (v1.2.0+).
	if ( PFBT_Feature_Flags::has_indieweb() ) {
		PFBT_Format_Mf2::instance();
	}

	// Initialize ActivityPub integration (v1.2.0+).
	// Works when ActivityPub plugin is installed.
	PFBT_ActivityPub_Transformer::instance();

	// Initialize Post Kinds integration (v1.2.0+).
	// Works when Post Kinds plugin is installed.
	PFBT_Post_Kinds_Integration::instance();

	// Register patterns after WordPress is fully loaded.
	add_action( 'init', array( 'PFBT_Pattern_Manager', 'register_all_patterns' ) );
}
add_action( 'after_setup_theme', 'pfbt_init', 99 );

/**
 * Enqueue editor assets
 *
 * Loads JavaScript and CSS for the block editor, including:
 * - Format selection modal
 * - Format switcher sidebar
 * - Status paragraph validation
 *
 * @since 1.0.0
 */
function pfbt_enqueue_editor_assets() {
	$screen = get_current_screen();

	// Only load on post editor screens.
	// Check both post_type and base to handle various editor contexts.
	if ( ! $screen ) {
		return;
	}

	// Allow post editor (edit/add), site editor, and block editor contexts for posts.
	$is_post_editor = 'post' === $screen->post_type;
	$is_post_base   = in_array( $screen->base, array( 'post', 'post-new' ), true );

	if ( ! $is_post_editor && ! $is_post_base ) {
		return;
	}

	// Load asset file with dependencies.
	$asset_path = PFBT_PLUGIN_DIR . 'build/index.asset.php';
	if ( ! file_exists( $asset_path ) ) {
		return;
	}
	$asset_file = include $asset_path;
	if ( ! is_array( $asset_file ) ) {
		return;
	}

	// Editor script.
	wp_enqueue_script(
		'pfpu-editor',
		PFBT_PLUGIN_URL . 'build/index.js',
		isset( $asset_file['dependencies'] ) ? $asset_file['dependencies'] : array(),
		isset( $asset_file['version'] ) ? $asset_file['version'] : PFBT_VERSION,
		true
	);

	// Load JavaScript translations.
	wp_set_script_translations(
		'pfpu-editor',
		'post-formats-for-block-themes',
		PFBT_PLUGIN_DIR . 'languages'
	);

	// Get pattern content for all formats.
	$patterns = array();
	foreach ( PFBT_Format_Registry::get_all_formats() as $slug => $format ) {
		$pattern_content = PFBT_Pattern_Manager::get_pattern( $slug );
		if ( $pattern_content ) {
			$patterns[ $slug ] = $pattern_content;
		}
	}

	// Build Post Kinds integration data.
	$post_kinds_data = array(
		'active'       => class_exists( 'PFBT_Post_Kinds_Integration' ) && PFBT_Post_Kinds_Integration::instance()->is_post_kinds_active(),
		'formatToKind' => array(),
		'kindToFormat' => array(),
	);

	if ( $post_kinds_data['active'] ) {
		$post_kinds_data['formatToKind'] = PFBT_Post_Kinds_Integration::instance()->get_all_format_kind_mappings();
		$post_kinds_data['kindToFormat'] = PFBT_Post_Kinds_Integration::instance()->get_all_kind_format_mappings();
	}

	// Pass data to JavaScript.
	wp_localize_script(
		'pfpu-editor',
		'pfbtData',
		array(
			'formats'         => PFBT_Format_Registry::get_all_formats(),
			'patterns'        => $patterns,
			'hasBookmarkCard' => function_exists( 'bookmark_card_register_block' ) || has_block( 'bookmark-card/bookmark-card' ),
			'hasChatLog'      => true, // Chat Log block is now integrated.
			'nonce'           => wp_create_nonce( 'pfbt_editor_nonce' ),
			'currentFormat'   => get_post_format() ? get_post_format() : 'standard',
			'postKinds'       => $post_kinds_data,
		)
	);

	// Note: Editor styles are inline in the JavaScript components.
	// Frontend styles are loaded via pfbt_enqueue_frontend_styles().
}
add_action( 'enqueue_block_editor_assets', 'pfbt_enqueue_editor_assets' );

/**
 * Enqueue frontend styles
 *
 * Loads format-specific styles that use CSS custom properties
 * from theme.json for consistent theming.
 *
 * Themes that ship their own format styling can opt out by returning
 * false from the `pfbt_enqueue_format_styles` filter:
 *
 *     add_filter( 'pfbt_enqueue_format_styles', '__return_false' );
 *
 * The plugin's stylesheet ships with stock WordPress fallback colors
 * (#0073aa, #f0f0f1, #cccccc, etc.) that override branded child-theme
 * palettes when both target the same `.format-X` body-class selectors.
 *
 * @since 1.0.0
 * @since 1.2.3 Added `pfbt_enqueue_format_styles` filter for opt-out.
 */
function pfbt_enqueue_frontend_assets() {
	/**
	 * Whether to enqueue the plugin's frontend format styles.
	 *
	 * Gates BOTH the legacy format-styles.css (1.x stock-color baseline)
	 * AND the 2.0 format-tokens.css (contract-compliant tokens +
	 * structural CSS). Themes that ship their own format styling end-to-end
	 * can opt out of both with one filter.
	 *
	 * @since 1.2.3
	 *
	 * @param bool $enqueue Default true. Return false to skip the enqueues
	 *                      so a child theme's own format styles win.
	 */
	if ( ! apply_filters( 'pfbt_enqueue_format_styles', true ) ) {
		return;
	}

	// 2.0 contract-compliant tokens + structural CSS. Wrapped in
	// @layer pfbt-format-tokens so theme styles always win the cascade.
	// This is the only frontend stylesheet the plugin enqueues — the
	// legacy 1.x format-styles.css was retired in 2.0 (themes own
	// paint; the plugin contributes only structure). Sites upgrading
	// from 1.x see no visible color regression because the tokens
	// default to neutral inheritance — formats inherit normal theme
	// styling unless the theme paints them.
	wp_enqueue_style(
		'pfbt-format-tokens',
		PFBT_PLUGIN_URL . 'styles/format-tokens.css',
		array(),
		PFBT_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'pfbt_enqueue_frontend_assets' );

/**
 * Add admin menu for repair tool
 *
 * Creates a menu item under Tools for the Post Format Repair tool.
 *
 * @since 1.0.0
 */
function pfbt_add_admin_menu() {
	add_management_page(
		__( 'Post Format Repair', 'post-formats-for-block-themes' ),
		__( 'Post Format Repair', 'post-formats-for-block-themes' ),
		'manage_options',
		'pfbt-repair-tool',
		array( 'PFBT_Repair_Tool', 'render_page' )
	);
}
add_action( 'admin_menu', 'pfbt_add_admin_menu' );

/**
 * Enqueue admin styles for repair tool page
 *
 * Properly enqueues styles using the admin_enqueue_scripts hook.
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix The current admin page hook suffix.
 */
function pfbt_enqueue_repair_tool_styles( $hook_suffix ) {
	PFBT_Repair_Tool::enqueue_styles( $hook_suffix );
}
add_action( 'admin_enqueue_scripts', 'pfbt_enqueue_repair_tool_styles' );

/**
 * Register block patterns on init
 *
 * Patterns are registered dynamically through the Pattern_Manager class.
 *
 * @since 1.0.0
 */
function pfbt_register_patterns() {
	PFBT_Pattern_Manager::register_all_patterns();
}
add_action( 'init', 'pfbt_register_patterns', 20 );

/**
 * Activation hook
 *
 * Runs when the plugin is activated. Checks for minimum requirements
 * and sets up initial options.
 *
 * @since 1.0.0
 */
function pfbt_activate() {
	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), '6.9', '<' ) ) {
		deactivate_plugins( PFBT_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Post Formats for Block Themes requires WordPress 6.9 or higher.', 'post-formats-for-block-themes' ),
			esc_html__( 'Plugin Activation Error', 'post-formats-for-block-themes' ),
			array( 'back_link' => true )
		);
	}

	// Check for block theme.
	if ( ! wp_is_block_theme() ) {
		deactivate_plugins( PFBT_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Post Formats for Block Themes requires a block theme. Classic themes are not supported.', 'post-formats-for-block-themes' ),
			esc_html__( 'Plugin Activation Error', 'post-formats-for-block-themes' ),
			array( 'back_link' => true )
		);
	}

	// Set default options.
	add_option( 'pfbt_version', PFBT_VERSION );
	add_option( 'pfbt_activated_time', time() );
}
register_activation_hook( __FILE__, 'pfbt_activate' );

/**
 * Deactivation hook
 *
 * Runs when the plugin is deactivated. Cleanup tasks.
 *
 * @since 1.0.0
 */
function pfbt_deactivate() {
	// Cleanup transients.
	delete_transient( 'pfbt_bookmark_card_available' );
	delete_transient( 'pfbt_chatlog_block_available' );
	delete_transient( 'pfbt_patterns_registered' );
}
register_deactivation_hook( __FILE__, 'pfbt_deactivate' );

/**
 * Add plugin action links
 *
 * Adds a "Settings" link to the plugin's row on the Plugins page
 * that links to the Post Format Repair tool.
 *
 * @since 1.1.3
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function pfbt_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=pfbt-repair-tool' ) ),
		esc_html__( 'Settings', 'post-formats-for-block-themes' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . PFBT_PLUGIN_BASENAME, 'pfbt_plugin_action_links' );

/**
 * Limit revisions for wp_block post type
 *
 * Synced patterns are stored as wp_block posts. Limit revisions
 * to prevent database bloat from pattern updates.
 *
 * @since 1.1.3
 *
 * @param int     $num  Number of revisions to keep.
 * @param WP_Post $post The post object.
 * @return int Modified number of revisions.
 */
function pfbt_limit_wp_block_revisions( $num, $post ) {
	if ( 'wp_block' === $post->post_type ) {
		return 3;
	}
	return $num;
}
add_filter( 'wp_revisions_to_keep', 'pfbt_limit_wp_block_revisions', 10, 2 );
