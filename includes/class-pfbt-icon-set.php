<?php
/**
 * Post Formats for Block Themes — Icon set picker.
 *
 * Lets site admins pick which sprite the Format Icon block renders. Each
 * "set" is a single SVG file at `img/icon-sets/{slug}/format-icons.svg`
 * with the same 9 `<symbol id="pfbt-format-icon-{format}">` entries — so
 * switching sets is a URL swap, not a markup change.
 *
 * Hooks into the existing `pfbt_format_icon_sprite_url` filter (added in
 * v2.0.0). Themes that filter that hook directly still win over the
 * picker setting; the picker only changes the DEFAULT URL the filter
 * sees, leaving room for theme overrides.
 *
 * Default: 'hand-drawn' (the original sprite shipped in v2.0.0). Setting
 * is stored in the `pfbt_icon_set` option (single string slug). Setting
 * is registered via the Settings API; UI lives at
 * Settings → Post Formats → Icon set.
 *
 * @package PostFormatsBlockThemes
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon set picker — resolves the active sprite URL and registers the option.
 */
class PFBT_Icon_Set {

	/**
	 * Option key storing the chosen icon set slug.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'pfbt_icon_set';

	/**
	 * Default icon set slug.
	 *
	 * @var string
	 */
	const DEFAULT_SET = 'hand-drawn';

	/**
	 * Available icon sets — slug => human-readable label.
	 *
	 * Filterable via `pfbt_available_icon_sets` so themes / extender
	 * plugins can register additional bundled sets.
	 *
	 * @return array<string,string>
	 */
	public static function get_available_sets() {
		$sets = array(
			'hand-drawn' => __( 'Hand-drawn (default)', 'post-formats-for-block-themes' ),
			'filled'     => __( 'Filled silhouettes', 'post-formats-for-block-themes' ),
		);

		/**
		 * Filter the list of available icon sets.
		 *
		 * Each set must be present at `img/icon-sets/{slug}/format-icons.svg`
		 * (or have its URL provided via `pfbt_icon_set_sprite_url`). The
		 * returned array maps slug => translated label shown in the picker.
		 *
		 * @since 2.3.0
		 *
		 * @param array<string,string> $sets Default sets shipped by the plugin.
		 */
		$sets = apply_filters( 'pfbt_available_icon_sets', $sets );

		// Always guarantee the default exists, even if a filter dropped it.
		if ( ! isset( $sets[ self::DEFAULT_SET ] ) ) {
			$sets = array( self::DEFAULT_SET => __( 'Hand-drawn (default)', 'post-formats-for-block-themes' ) ) + $sets;
		}

		return $sets;
	}

	/**
	 * Get the slug of the currently-active icon set.
	 *
	 * Falls back to the default if the saved value isn't in the available
	 * list (covers the case of a previously-registered set being removed).
	 *
	 * @return string
	 */
	public static function get_active_set_slug() {
		$saved   = (string) get_option( self::OPTION_KEY, self::DEFAULT_SET );
		$allowed = self::get_available_sets();
		return isset( $allowed[ $saved ] ) ? $saved : self::DEFAULT_SET;
	}

	/**
	 * Resolve the sprite URL for a given (or active) set slug.
	 *
	 * @param string|null $slug Set slug, or null for the active set.
	 * @return string Absolute URL to the sprite file.
	 */
	public static function get_sprite_url( $slug = null ) {
		if ( null === $slug ) {
			$slug = self::get_active_set_slug();
		}

		$default_url = trailingslashit( PFBT_PLUGIN_URL ) . 'img/icon-sets/' . $slug . '/format-icons.svg';

		/**
		 * Filter the sprite URL for a specific icon set.
		 *
		 * Lets themes / plugins ship their own sprites at custom paths
		 * without needing to filter the (per-render) `pfbt_format_icon_sprite_url`
		 * for every render. Most consumers should NOT need this — register
		 * a new set via `pfbt_available_icon_sets` and place the file at
		 * the conventional path instead.
		 *
		 * @since 2.3.0
		 *
		 * @param string $default_url Absolute URL to {plugin}/img/icon-sets/{slug}/format-icons.svg.
		 * @param string $slug        The set slug being resolved.
		 */
		return (string) apply_filters( 'pfbt_icon_set_sprite_url', $default_url, $slug );
	}

	/**
	 * Hook the active set into the existing per-render sprite filter.
	 *
	 * Priority 5 — runs BEFORE any theme override at the default
	 * priority 10, so themes still win.
	 */
	public static function register_filter() {
		add_filter(
			'pfbt_format_icon_sprite_url',
			array( __CLASS__, 'filter_sprite_url' ),
			5
		);
	}

	/**
	 * Filter callback — replaces the default sprite URL with the active set's URL.
	 *
	 * @param string $url Default sprite URL passed in by the format-icon block.
	 * @return string Active set sprite URL.
	 */
	public static function filter_sprite_url( $url ) {
		// The block hard-codes 'img/format-icons.svg' as its filter input default.
		// We replace that with the active-set URL. Themes filtering at higher
		// priority (10+) still win.
		return self::get_sprite_url();
	}

	/**
	 * Register the WP Settings API entry for the option.
	 */
	public static function register_setting() {
		register_setting(
			'pfbt_settings',
			self::OPTION_KEY,
			array(
				'type'              => 'string',
				'description'       => __( 'Icon set used by the Format Icon block.', 'post-formats-for-block-themes' ),
				'default'           => self::DEFAULT_SET,
				'sanitize_callback' => array( __CLASS__, 'sanitize_set_slug' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Sanitize the icon-set option value — must be a known slug.
	 *
	 * @param mixed $value Raw input from the settings form.
	 * @return string Allowed slug or the default.
	 */
	public static function sanitize_set_slug( $value ) {
		$value   = is_string( $value ) ? sanitize_key( $value ) : '';
		$allowed = self::get_available_sets();
		return isset( $allowed[ $value ] ) ? $value : self::DEFAULT_SET;
	}
}
