<?php
/**
 * Abilities Manager for Post Formats Block Themes
 *
 * Manages registration of WordPress Abilities API abilities for post formats.
 * Provides a central registry for all plugin abilities.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abilities Manager
 *
 * Handles registration and management of WordPress Abilities API abilities.
 * Uses singleton pattern for consistent state across the plugin.
 *
 * @since 1.2.0
 */
class PFBT_Abilities_Manager {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Abilities_Manager|null
	 */
	private static $instance = null;

	/**
	 * Registered ability providers
	 *
	 * @var array<string, object>
	 */
	private $providers = array();

	/**
	 * Plugin ability category slug
	 *
	 * @var string
	 */
	const CATEGORY_SLUG = 'post-formats';

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Abilities_Manager
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
		$this->init();
	}

	/**
	 * Initialize abilities registration
	 *
	 * @since 1.2.0
	 */
	private function init() {
		// Only initialize if Abilities API is available.
		if ( ! PFBT_Feature_Flags::has_abilities_api() ) {
			return;
		}

		// Register category first.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );

		// Register abilities after category.
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register the post-formats ability category
	 *
	 * @since 1.2.0
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY_SLUG,
			array(
				'label'       => __( 'Post Formats', 'post-formats-for-block-themes' ),
				'description' => __( 'Abilities for managing and querying WordPress post formats in block themes.', 'post-formats-for-block-themes' ),
			)
		);
	}

	/**
	 * Register all plugin abilities
	 *
	 * @since 1.2.0
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Load and register core abilities.
		$this->register_core_abilities();

		// Register format operation abilities.
		$this->register_format_abilities();

		// Register IndieWeb abilities if enabled.
		if ( PFBT_Feature_Flags::has_indieweb() ) {
			$this->register_indieweb_abilities();
		}

		// Register MCP abilities if enabled.
		if ( PFBT_Feature_Flags::has_mcp() ) {
			$this->register_mcp_abilities();
		}

		// Register ActivityPub abilities if available.
		if ( PFBT_Feature_Flags::has_activitypub() ) {
			$this->register_activitypub_abilities();
		}

		/**
		 * Fires after all post format abilities are registered.
		 *
		 * Allows other plugins to register additional abilities in the post-formats category.
		 *
		 * @since 1.2.0
		 *
		 * @param PFBT_Abilities_Manager $manager The abilities manager instance.
		 */
		do_action( 'pfbt_abilities_registered', $this );
	}

	/**
	 * Register core post format abilities
	 *
	 * @since 1.2.0
	 */
	private function register_core_abilities() {
		if ( ! class_exists( 'PFBT_Core_Abilities' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-core-abilities.php';
		}

		$provider                = PFBT_Core_Abilities::instance();
		$this->providers['core'] = $provider;
		$provider->register();
	}

	/**
	 * Register format operation abilities (detect, switch, repair, stats)
	 *
	 * @since 1.2.0
	 */
	private function register_format_abilities() {
		if ( ! class_exists( 'PFBT_Abilities_Formats' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/class-abilities-formats.php';
		}

		$provider                   = PFBT_Abilities_Formats::instance();
		$this->providers['formats'] = $provider;
		$provider->register();
	}

	/**
	 * Register IndieWeb abilities (mf2, POSSE, webmentions)
	 *
	 * @since 1.2.0
	 */
	private function register_indieweb_abilities() {
		if ( ! class_exists( 'PFBT_IndieWeb_Abilities' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-indieweb-abilities.php';
		}

		$provider                    = PFBT_IndieWeb_Abilities::instance();
		$this->providers['indieweb'] = $provider;
		$provider->register();
	}

	/**
	 * Register MCP bridge abilities
	 *
	 * @since 1.2.0
	 */
	private function register_mcp_abilities() {
		if ( ! class_exists( 'PFBT_MCP_Abilities' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/abilities/class-pfbt-mcp-abilities.php';
		}

		$provider               = PFBT_MCP_Abilities::instance();
		$this->providers['mcp'] = $provider;
		$provider->register();
	}

	/**
	 * Register ActivityPub abilities
	 *
	 * @since 1.2.0
	 */
	private function register_activitypub_abilities() {
		// ActivityPub abilities will be added in Phase 4.
		// Placeholder for future implementation.
	}

	/**
	 * Get a registered ability provider
	 *
	 * @since 1.2.0
	 *
	 * @param string $name Provider name (core, indieweb, mcp, activitypub).
	 * @return object|null The provider instance or null if not registered.
	 */
	public function get_provider( $name ) {
		return $this->providers[ $name ] ?? null;
	}

	/**
	 * Get all registered providers
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, object> All registered providers.
	 */
	public function get_providers() {
		return $this->providers;
	}

	/**
	 * Check if the Abilities API is available and enabled
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether abilities are available.
	 */
	public static function is_available() {
		return PFBT_Feature_Flags::has_abilities_api();
	}
}
