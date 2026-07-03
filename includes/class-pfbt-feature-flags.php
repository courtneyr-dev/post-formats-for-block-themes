<?php
/**
 * Feature Flags for Post Formats Block Themes
 *
 * Manages optional feature toggles for IndieWeb, MCP, and ActivityPub integrations.
 * Features can be enabled/disabled via filters, options, or constants.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feature Flags Manager
 *
 * Provides centralized feature flag management for optional plugin integrations.
 * Priority order: constant > filter > option > default.
 *
 * @since 1.2.0
 */
class PFBT_Feature_Flags {

	/**
	 * Default feature flag values
	 *
	 * @var array<string, bool>
	 */
	private static $defaults = array(
		'indieweb_integration'    => true,  // IndieWeb (mf2, POSSE, webmentions) - ON by default.
		'mcp_integration'         => true,  // Model Context Protocol tools - ON by default.
		'activitypub_integration' => false, // ActivityPub federation - OFF by default (requires plugin).
		'ai_suggestions'          => true,  // AI format suggestions in editor - ON by default.
		'posse_preview'           => true,  // POSSE syndication preview - ON by default.
		'federation_preview'      => false, // Federation preview panel - OFF by default.
		'abilities_api'           => true,  // WordPress Abilities API - ON by default.
		'block_bindings'          => true,  // Block Bindings + Block Hooks - ON by default.
		'image_gallery_styles'    => false, // 16 image + 20 gallery block style variations (v2.1.0). OFF by default — opt-in for first release.
		'quote_styles'            => false, // 16 quote/pullquote block style variations (v2.2.0). OFF by default — opt-in for first release.
	);

	/**
	 * Check if a feature is enabled
	 *
	 * Checks in order: constant, filter, option, default.
	 *
	 * @since 1.2.0
	 *
	 * @param string $flag Feature flag name.
	 * @return bool Whether the feature is enabled.
	 */
	public static function is_enabled( $flag ) {
		// 1. Check for constant override (highest priority).
		$constant_name = 'PFBT_FEATURE_' . strtoupper( $flag );
		if ( defined( $constant_name ) ) {
			return (bool) constant( $constant_name );
		}

		// 2. Check filter (allows runtime override).
		$filtered = apply_filters( "pfbt_feature_{$flag}", null );
		if ( null !== $filtered ) {
			return (bool) $filtered;
		}

		// 3. Check option (user/admin setting). Null default distinguishes
		// "not set" from a stored falsy value like '0'.
		$option = get_option( "pfbt_feature_{$flag}", null );
		if ( null !== $option ) {
			return (bool) $option;
		}

		// 4. Return default.
		return self::$defaults[ $flag ] ?? false;
	}

	/**
	 * Check if a feature requires a plugin and if that plugin is active
	 *
	 * @since 1.2.0
	 *
	 * @param string $flag        Feature flag name.
	 * @param string $plugin_file Plugin file path (e.g., 'activitypub/activitypub.php').
	 * @return bool Whether the feature is enabled AND the required plugin is active.
	 */
	public static function requires_plugin( $flag, $plugin_file ) {
		if ( ! self::is_enabled( $flag ) ) {
			return false;
		}

		// Ensure is_plugin_active is available.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}

	/**
	 * Get all feature flags with their current status
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, array{enabled: bool, default: bool, source: string}> Feature flags with status.
	 */
	public static function get_all_flags() {
		$flags = array();

		foreach ( self::$defaults as $flag => $default ) {
			$constant_name = 'PFBT_FEATURE_' . strtoupper( $flag );
			$source        = 'default';

			if ( defined( $constant_name ) ) {
				$source = 'constant';
			} elseif ( has_filter( "pfbt_feature_{$flag}" ) ) {
				$source = 'filter';
			} elseif ( null !== get_option( "pfbt_feature_{$flag}", null ) ) {
				$source = 'option';
			}

			$flags[ $flag ] = array(
				'enabled' => self::is_enabled( $flag ),
				'default' => $default,
				'source'  => $source,
			);
		}

		return $flags;
	}

	/**
	 * Set a feature flag option
	 *
	 * @since 1.2.0
	 *
	 * @param string $flag    Feature flag name.
	 * @param bool   $enabled Whether to enable the feature.
	 * @return bool Whether the option was updated successfully.
	 */
	public static function set_flag( $flag, $enabled ) {
		if ( ! array_key_exists( $flag, self::$defaults ) ) {
			return false;
		}

		// Store '1'/'0' rather than booleans: update_option() silently
		// short-circuits when writing false to a missing option (false is
		// also get_option()'s "missing" sentinel), so a raw false can
		// never disable a default-on feature.
		return update_option( "pfbt_feature_{$flag}", $enabled ? '1' : '0' );
	}

	/**
	 * Reset a feature flag to its default value
	 *
	 * @since 1.2.0
	 *
	 * @param string $flag Feature flag name.
	 * @return bool Whether the option was deleted successfully.
	 */
	public static function reset_flag( $flag ) {
		return delete_option( "pfbt_feature_{$flag}" );
	}

	/**
	 * Check if the Abilities API is available
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether the WordPress Abilities API is available.
	 */
	public static function has_abilities_api() {
		return self::is_enabled( 'abilities_api' ) && function_exists( 'wp_register_ability' );
	}

	/**
	 * Check if IndieWeb features should be active
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether IndieWeb features are enabled.
	 */
	public static function has_indieweb() {
		return self::is_enabled( 'indieweb_integration' );
	}

	/**
	 * Check if MCP integration should be active
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether MCP integration is enabled.
	 */
	public static function has_mcp() {
		return self::is_enabled( 'mcp_integration' );
	}

	/**
	 * Check if ActivityPub integration is available
	 *
	 * Requires both the feature flag AND the ActivityPub plugin.
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether ActivityPub integration is available.
	 */
	/**
	 * Check if Block Bindings are available
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether Block Bindings API is available.
	 */
	public static function has_block_bindings() {
		return self::is_enabled( 'block_bindings' ) && function_exists( 'register_block_bindings_source' );
	}

	/**
	 * Check if Image and Gallery block style variations should be registered
	 *
	 * Adds 16 image and 20 gallery block style variations introduced in
	 * v2.1.0. Defaults to false so existing installs opt in deliberately.
	 *
	 * @since 2.1.0
	 *
	 * @return bool Whether the variations should register.
	 */
	public static function has_image_gallery_styles() {
		return self::is_enabled( 'image_gallery_styles' );
	}

	/**
	 * Check if Quote and Pullquote block style variations should be registered
	 *
	 * Adds 16 quote/pullquote block style variations introduced in v2.2.0.
	 * Defaults to false so existing installs opt in deliberately.
	 *
	 * @since 2.2.0
	 *
	 * @return bool Whether the variations should register.
	 */
	public static function has_quote_styles() {
		return self::is_enabled( 'quote_styles' );
	}

	/**
	 * Check if ActivityPub integration is available.
	 *
	 * @return bool
	 */
	public static function has_activitypub() {
		return self::requires_plugin( 'activitypub_integration', 'activitypub/activitypub.php' );
	}
}
