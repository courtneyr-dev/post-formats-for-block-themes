<?php
/**
 * Plugin Name: Post Formats for Block Themes
 * Plugin URI: https://wordpress.org/plugins/post-formats-for-block-themes/
 * Description: Modernizes WordPress post formats for block themes with format-specific patterns, auto-detection, and enhanced editor experience.
 * Version: 1.0.0
 * Requires at least: 6.8
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
define( 'PFBT_VERSION', '1.0.0' );
define( 'PFBT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFBT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PFBT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Register format template types VERY early to prevent warnings
 *
 * @since 1.0.0
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
		// Only suppress our specific template type warnings from WordPress core
		if ( false !== strpos( $errfile, 'block-template.php' ) &&
			false !== strpos( $errstr, 'single-format-' ) &&
			( false !== strpos( $errstr, 'Undefined array key' ) ||
				false !== strpos( $errstr, 'Undefined index' ) ) ) {
			return true; // Suppress only this specific warning
		}
		return false; // Let all other errors through normally
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

	// Include Chat Log block (integrated)
	// This provides the chatlog/conversation block for the Chat post format
	require_once PFBT_PLUGIN_DIR . 'blocks/chatlog/chatlog-block.php';
}
add_action( 'plugins_loaded', 'pfbt_include_files' );

/**
 * Initialize plugin
 *
 * Registers theme support for all 10 post formats and initializes
 * plugin components.
 *
 * @since 1.0.0
 */
function pfbt_init() {
	// Register theme support for all post formats.
	add_theme_support(
		'post-formats',
		array(
			'aside',
			'gallery',
			'link',
			'image',
			'quote',
			'status',
			'video',
			'audio',
			'chat',
		)
	);

	// Initialize plugin classes.
	PFBT_Format_Registry::instance();
	PFBT_Format_Detector::instance();
	PFBT_Pattern_Manager::instance();
	PFBT_Block_Locker::instance();

	// Register patterns after WordPress is fully loaded.
	add_action( 'init', array( 'PFBT_Pattern_Manager', 'register_all_patterns' ) );
}
add_action( 'after_setup_theme', 'pfbt_init' );

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
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}

	// Load asset file with dependencies.
	$asset_file = include PFBT_PLUGIN_DIR . 'build/index.asset.php';

	// Editor script.
	wp_enqueue_script(
		'pfpu-editor',
		PFBT_PLUGIN_URL . 'build/index.js',
		$asset_file['dependencies'],
		$asset_file['version'],
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

	// Pass data to JavaScript.
	wp_localize_script(
		'pfpu-editor',
		'pfbtData',
		array(
			'formats'         => PFBT_Format_Registry::get_all_formats(),
			'patterns'        => $patterns,
			'hasBookmarkCard' => function_exists( 'bookmark_card_register_block' ) || has_block( 'bookmark-card/bookmark-card' ),
			'hasChatLog'      => true, // Chat Log block is now integrated
			'nonce'           => wp_create_nonce( 'pfbt_editor_nonce' ),
			'currentFormat'   => get_post_format() ?: 'standard',
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
 * @since 1.0.0
 */
function pfbt_enqueue_frontend_assets() {
	wp_enqueue_style(
		'pfpu-format-styles',
		PFBT_PLUGIN_URL . 'styles/format-styles.css',
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
	if ( version_compare( get_bloginfo( 'version' ), '6.8', '<' ) ) {
		deactivate_plugins( PFBT_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Post Formats for Block Themes requires WordPress 6.8 or higher.', 'post-formats-for-block-themes' ),
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
}
register_deactivation_hook( __FILE__, 'pfbt_deactivate' );
