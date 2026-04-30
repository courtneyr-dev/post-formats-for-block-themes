<?php
/**
 * Block Style Variation Registry
 *
 * Registers image and gallery block style variations from definition arrays
 * loaded from `includes/definitions/`. Each registered variation is paired
 * with a stylesheet that loads only when the variation is used on a page,
 * via the `style_handle` argument of `register_block_style()`.
 *
 * Gated behind the `image_gallery_styles` feature flag (default off in
 * v2.1.0 — opt-in for the first release).
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PFBT_Block_Style_Registry
 *
 * Singleton. Drives variation registration on the `init` hook (priority 20
 * to land after pattern registration but before render).
 *
 * @since 2.1.0
 */
class PFBT_Block_Style_Registry {

	/**
	 * Singleton instance.
	 *
	 * @since 2.1.0
	 * @var PFBT_Block_Style_Registry|null
	 */
	private static $instance = null;

	/**
	 * Image variation definitions (resolved + filtered).
	 *
	 * Populated lazily by load_definitions(). Each entry includes a
	 * normalized `style_handle` even when the definition omitted one.
	 *
	 * @since 2.1.0
	 * @var array<string, array<string, mixed>>
	 */
	private $image_variations = array();

	/**
	 * Gallery variation definitions (resolved + filtered).
	 *
	 * @since 2.1.0
	 * @var array<string, array<string, mixed>>
	 */
	private $gallery_variations = array();

	/**
	 * Whether definitions have been loaded yet.
	 *
	 * @since 2.1.0
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * Get the singleton instance.
	 *
	 * @since 2.1.0
	 * @return PFBT_Block_Style_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Bootstrap. Wires `init` action.
	 *
	 * Idempotent — safe to call multiple times. Subsequent calls are no-ops
	 * because `add_action` deduplicates identical callbacks.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Register all configured variations.
	 *
	 * Runs on `init` (priority 20). Loads definition arrays, applies the
	 * `pfbt_image_style_variations` and `pfbt_gallery_style_variations`
	 * filters, then loops through each entry to:
	 *
	 *   1. wp_register_style() the variation's stylesheet.
	 *   2. register_block_style() with `style_handle` so WP loads the
	 *      stylesheet only when the variation is rendered.
	 *
	 * Idempotent: if WP fires `init` more than once in a request (test
	 * environments do), the load guard prevents duplicate filter application.
	 *
	 * @since 2.1.0
	 */
	public function register() {
		$this->load_definitions();

		foreach ( $this->image_variations as $variation ) {
			$this->register_one( 'core/image', $variation );
		}

		foreach ( $this->gallery_variations as $variation ) {
			$this->register_one( 'core/gallery', $variation );
		}
	}

	/**
	 * Load + normalize variation definitions from disk.
	 *
	 * Definitions live in `includes/definitions/{block}-style-variations.php`
	 * and return associative arrays. The `pfbt_image_style_variations` and
	 * `pfbt_gallery_style_variations` filters run after disk load so themes
	 * and other plugins can add, modify, or remove variations.
	 *
	 * Default `style_handle` is computed when the definition omits one:
	 * `pfbt-image-variation-{slug}` or `pfbt-gallery-variation-{slug}`.
	 *
	 * @since 2.1.0
	 */
	private function load_definitions() {
		if ( $this->loaded ) {
			return;
		}

		$image_definitions   = $this->load_definition_file( 'image-style-variations.php' );
		$gallery_definitions = $this->load_definition_file( 'gallery-style-variations.php' );

		/**
		 * Filter the image block style variation definitions.
		 *
		 * Allows themes and other plugins to add, modify, or remove
		 * variations before they're registered. Each entry MUST conform
		 * to the schema documented in
		 * `includes/definitions/image-style-variations.php`.
		 *
		 * @since 2.1.0
		 *
		 * @param array<string, array<string, mixed>> $image_definitions Definitions keyed by slug.
		 */
		$image_definitions = apply_filters( 'pfbt_image_style_variations', $image_definitions );

		/**
		 * Filter the gallery block style variation definitions.
		 *
		 * @since 2.1.0
		 *
		 * @param array<string, array<string, mixed>> $gallery_definitions Definitions keyed by slug.
		 */
		$gallery_definitions = apply_filters( 'pfbt_gallery_style_variations', $gallery_definitions );

		$this->image_variations   = $this->normalize( $image_definitions, 'image' );
		$this->gallery_variations = $this->normalize( $gallery_definitions, 'gallery' );

		$this->loaded = true;
	}

	/**
	 * Read a definition file from disk.
	 *
	 * Files live in `includes/definitions/`. They return an array; if a
	 * file is missing or returns a non-array value, an empty array is
	 * returned and the registry continues without error.
	 *
	 * @since 2.1.0
	 *
	 * @param string $filename Filename within `includes/definitions/`.
	 * @return array<string, array<string, mixed>>
	 */
	private function load_definition_file( $filename ) {
		$path = PFBT_PLUGIN_DIR . 'includes/definitions/' . $filename;

		if ( ! is_readable( $path ) ) {
			return array();
		}

		$definitions = include $path;

		return is_array( $definitions ) ? $definitions : array();
	}

	/**
	 * Normalize a definition array.
	 *
	 * Drops entries that are missing required fields (`slug`, `label`,
	 * `style_path`). Computes a default `style_handle` when omitted.
	 *
	 * @since 2.1.0
	 *
	 * @param array<int|string, array<string, mixed>> $definitions Raw definitions.
	 * @param string                                  $block_kind  Either 'image' or 'gallery'.
	 *                                                             Used for the default style handle.
	 * @return array<string, array<string, mixed>> Normalized, slug-keyed.
	 */
	private function normalize( $definitions, $block_kind ) {
		$normalized = array();

		foreach ( $definitions as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			if ( empty( $entry['slug'] ) || empty( $entry['label'] ) || empty( $entry['style_path'] ) ) {
				continue;
			}

			$slug = sanitize_title( (string) $entry['slug'] );
			if ( '' === $slug ) {
				continue;
			}

			if ( empty( $entry['style_handle'] ) ) {
				$entry['style_handle'] = "pfbt-{$block_kind}-variation-{$slug}";
			}
			$entry['slug'] = $slug;

			$normalized[ $slug ] = $entry;
		}

		return $normalized;
	}

	/**
	 * Register a single variation: stylesheet handle + block style entry.
	 *
	 * Pairing a `style_handle` with `register_block_style()` is the
	 * supported core mechanism (WP 6.1+) for conditional CSS loading —
	 * WP enqueues the handle only when a block on the page uses the
	 * variation.
	 *
	 * @since 2.1.0
	 *
	 * @param string               $block_name  Either 'core/image' or 'core/gallery'.
	 * @param array<string, mixed> $variation   Normalized variation entry.
	 */
	private function register_one( $block_name, array $variation ) {
		$handle     = (string) $variation['style_handle'];
		$style_path = (string) $variation['style_path'];

		// Register the stylesheet handle. wp_register_style() doesn't
		// enqueue — it just makes the handle known so register_block_style
		// can reference it.
		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style(
				$handle,
				PFBT_PLUGIN_URL . ltrim( $style_path, '/' ),
				array(),
				PFBT_VERSION
			);
		}

		register_block_style(
			$block_name,
			array(
				'name'         => $variation['slug'],
				'label'        => $variation['label'],
				'style_handle' => $handle,
			)
		);

		// Optional: register an Interactivity API view module for
		// gallery variations that opt in (e.g., lightbox-slideshow,
		// before-after-pairs). Module is enqueued conditionally too.
		if ( ! empty( $variation['view_module'] ) && ! empty( $variation['view_module_id'] ) && function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module(
				(string) $variation['view_module_id'],
				PFBT_PLUGIN_URL . ltrim( (string) $variation['view_module'], '/' ),
				array( '@wordpress/interactivity' ),
				PFBT_VERSION
			);
		}
	}

	/**
	 * Get image variation definitions (post-filter, post-normalization).
	 *
	 * Used by tests + author docs generators. Triggers a lazy load.
	 *
	 * @since 2.1.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_image_variations() {
		$this->load_definitions();
		return $this->image_variations;
	}

	/**
	 * Get gallery variation definitions (post-filter, post-normalization).
	 *
	 * @since 2.1.0
	 * @return array<string, array<string, mixed>>
	 */
	public function get_gallery_variations() {
		$this->load_definitions();
		return $this->gallery_variations;
	}

	/**
	 * Reset the load guard (test-only).
	 *
	 * Allows the unit suite to re-run load_definitions() after toggling
	 * filters. Not used in production.
	 *
	 * @since 2.1.0
	 */
	public function reset_for_tests() {
		$this->loaded             = false;
		$this->image_variations   = array();
		$this->gallery_variations = array();
	}
}
