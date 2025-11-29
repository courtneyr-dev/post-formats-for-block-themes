<?php
/**
 * Plugin Name: Post Formats Power-Up
 * Plugin URI: https://wordpress.org/plugins/post-formats-power-up/
 * Description: Modernizes WordPress post formats for block themes with format-specific patterns, auto-detection, and enhanced editor experience.
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: Courtney Robertson
 * Author URI: https://profiles.wordpress.org/courane01/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: post-formats-power-up
 * Domain Path: /languages
 *
 * @package PostFormatsPowerUp
 *
 * Accessibility Implementation:
 * - All UI components use semantic HTML and ARIA labels
 * - Modal dialogs support keyboard navigation and focus management
 * - Format selection interface is fully keyboard accessible
 * - Editor components use WordPress accessible components
 * - All strings are translatable with proper text domain
 *
 * Translation Support:
 * - Text Domain: post-formats-power-up
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
define( 'PFPU_VERSION', '1.0.0' );
define( 'PFPU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PFPU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PFPU_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Register format template types VERY early to prevent warnings
 *
 * @since 1.0.0
 */
function pfpu_register_template_types_early( $template_types ) {
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
add_filter( 'default_template_types', 'pfpu_register_template_types_early', 1 );

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
function pfpu_include_files() {
	require_once PFPU_PLUGIN_DIR . 'includes/class-format-registry.php';
	require_once PFPU_PLUGIN_DIR . 'includes/class-format-detector.php';
	require_once PFPU_PLUGIN_DIR . 'includes/class-pattern-manager.php';
	require_once PFPU_PLUGIN_DIR . 'includes/class-block-locker.php';
	require_once PFPU_PLUGIN_DIR . 'includes/class-repair-tool.php';
	require_once PFPU_PLUGIN_DIR . 'includes/class-media-player-integration.php';
	require_once PFPU_PLUGIN_DIR . 'includes/class-format-styles.php';

	// Include Chat Log block (integrated)
	// This provides the chatlog/conversation block for the Chat post format
	require_once PFPU_PLUGIN_DIR . 'blocks/chatlog/chatlog-block.php';
}
add_action( 'plugins_loaded', 'pfpu_include_files' );

/**
 * Initialize plugin
 *
 * Registers theme support for all 10 post formats and initializes
 * plugin components.
 *
 * @since 1.0.0
 */
function pfpu_init() {
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
	PFPU_Format_Registry::instance();
	PFPU_Format_Detector::instance();
	PFPU_Pattern_Manager::instance();
	PFPU_Block_Locker::instance();

	// Register patterns after WordPress is fully loaded.
	add_action( 'init', array( 'PFPU_Pattern_Manager', 'register_all_patterns' ) );
}
add_action( 'after_setup_theme', 'pfpu_init' );

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
function pfpu_enqueue_editor_assets() {
	$screen = get_current_screen();

	// Only load on post editor screens.
	if ( ! $screen || 'post' !== $screen->post_type ) {
		return;
	}

	// Load asset file with dependencies.
	$asset_file = include PFPU_PLUGIN_DIR . 'build/index.asset.php';

	// Editor script.
	wp_enqueue_script(
		'pfpu-editor',
		PFPU_PLUGIN_URL . 'build/index.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	// Load JavaScript translations.
	wp_set_script_translations(
		'pfpu-editor',
		'post-formats-power-up',
		PFPU_PLUGIN_DIR . 'languages'
	);

	// Get pattern content for all formats.
	$patterns = array();
	foreach ( PFPU_Format_Registry::get_all_formats() as $slug => $format ) {
		$pattern_content = PFPU_Pattern_Manager::get_pattern( $slug );
		if ( $pattern_content ) {
			$patterns[ $slug ] = $pattern_content;
		}
	}

	// Pass data to JavaScript.
	wp_localize_script(
		'pfpu-editor',
		'pfpuData',
		array(
			'formats'         => PFPU_Format_Registry::get_all_formats(),
			'patterns'        => $patterns,
			'hasBookmarkCard' => function_exists( 'bookmark_card_register_block' ) || has_block( 'bookmark-card/bookmark-card' ),
			'hasChatLog'      => true, // Chat Log block is now integrated
			'nonce'           => wp_create_nonce( 'pfpu_editor_nonce' ),
			'currentFormat'   => get_post_format() ?: 'standard',
		)
	);

	// Note: Editor styles are inline in the JavaScript components.
	// Frontend styles are loaded via pfpu_enqueue_frontend_styles().
}
add_action( 'enqueue_block_editor_assets', 'pfpu_enqueue_editor_assets' );

/**
 * Enqueue frontend styles
 *
 * Loads format-specific styles that use CSS custom properties
 * from theme.json for consistent theming.
 *
 * @since 1.0.0
 */
function pfpu_enqueue_frontend_assets() {
	wp_enqueue_style(
		'pfpu-format-styles',
		PFPU_PLUGIN_URL . 'styles/format-styles.css',
		array(),
		PFPU_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'pfpu_enqueue_frontend_assets' );

/**
 * Add admin menu for repair tool
 *
 * Creates a menu item under Tools for the Post Format Repair tool.
 *
 * @since 1.0.0
 */
function pfpu_add_admin_menu() {
	add_management_page(
		__( 'Post Format Repair', 'post-formats-power-up' ),
		__( 'Post Format Repair', 'post-formats-power-up' ),
		'manage_options',
		'pfpu-repair-tool',
		array( 'PFPU_Repair_Tool', 'render_page' )
	);
}
add_action( 'admin_menu', 'pfpu_add_admin_menu' );

/**
 * Register block patterns on init
 *
 * Patterns are registered dynamically through the Pattern_Manager class.
 *
 * @since 1.0.0
 */
function pfpu_register_patterns() {
	PFPU_Pattern_Manager::register_all_patterns();
}
add_action( 'init', 'pfpu_register_patterns', 20 );

/**
 * Activation hook
 *
 * Runs when the plugin is activated. Checks for minimum requirements
 * and sets up initial options.
 *
 * @since 1.0.0
 */
function pfpu_activate() {
	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), '6.8', '<' ) ) {
		deactivate_plugins( PFPU_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Post Formats Power-Up requires WordPress 6.8 or higher.', 'post-formats-power-up' ),
			esc_html__( 'Plugin Activation Error', 'post-formats-power-up' ),
			array( 'back_link' => true )
		);
	}

	// Check for block theme.
	if ( ! wp_is_block_theme() ) {
		deactivate_plugins( PFPU_PLUGIN_BASENAME );
		wp_die(
			esc_html__( 'Post Formats Power-Up requires a block theme. Classic themes are not supported.', 'post-formats-power-up' ),
			esc_html__( 'Plugin Activation Error', 'post-formats-power-up' ),
			array( 'back_link' => true )
		);
	}

	// Set default options.
	add_option( 'pfpu_version', PFPU_VERSION );
	add_option( 'pfpu_activated_time', time() );
}
register_activation_hook( __FILE__, 'pfpu_activate' );

/**
 * Deactivation hook
 *
 * Runs when the plugin is deactivated. Cleanup tasks.
 *
 * @since 1.0.0
 */
function pfpu_deactivate() {
	// Cleanup transients.
	delete_transient( 'pfpu_bookmark_card_available' );
	delete_transient( 'pfpu_chatlog_block_available' );
}
register_deactivation_hook( __FILE__, 'pfpu_deactivate' );
