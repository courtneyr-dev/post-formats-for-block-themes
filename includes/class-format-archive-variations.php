<?php
/**
 * Query Loop variations for post format archives.
 *
 * @package PostFormatsBlockThemes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the post format archive Query Loop variations in every block editor.
 */
class PFBT_Format_Archive_Variations {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const SCRIPT_HANDLE = 'pfbt-format-archive-variations';

	/**
	 * Singleton instance.
	 *
	 * @var PFBT_Format_Archive_Variations|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return PFBT_Format_Archive_Variations
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register editor asset hooks.
	 */
	private function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue the Query Loop variations in every block editor.
	 */
	public function enqueue() {
		$asset_path = PFBT_PLUGIN_DIR . 'build/format-archive-variations.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset_file = include $asset_path;
		if ( ! is_array( $asset_file ) ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			PFBT_PLUGIN_URL . 'build/format-archive-variations.js',
			isset( $asset_file['dependencies'] ) ? $asset_file['dependencies'] : array(),
			isset( $asset_file['version'] ) ? $asset_file['version'] : PFBT_VERSION,
			true
		);

		wp_set_script_translations(
			self::SCRIPT_HANDLE,
			'post-formats-for-block-themes',
			PFBT_PLUGIN_DIR . 'languages'
		);
	}
}
