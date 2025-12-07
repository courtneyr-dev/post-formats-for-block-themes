<?php
/**
 * Media Player Integration
 *
 * Provides integration with popular WordPress media player plugins:
 * - AblePlayer (https://wordpress.org/plugins/ableplayer/)
 * - Podlove Podcasting Plugin (https://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/)
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Player Integration Class
 *
 * Detects and provides compatibility with AblePlayer and Podlove plugins.
 *
 * @since 1.0.0
 */
class PFBT_Media_Player_Integration {

	/**
	 * Initialize the integration
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_integration_styles' ) );
		add_filter( 'render_block', array( __CLASS__, 'enhance_media_blocks' ), 10, 2 );
	}

	/**
	 * Check if AblePlayer is active
	 *
	 * @since 1.0.0
	 * @return bool True if AblePlayer is active.
	 */
	public static function is_ableplayer_active() {
		return class_exists( 'AblePlayer' ) || defined( 'ABLEPLAYER_VERSION' );
	}

	/**
	 * Check if Podlove Podcasting Plugin is active
	 *
	 * @since 1.0.0
	 * @return bool True if Podlove is active.
	 */
	public static function is_podlove_active() {
		return class_exists( '\\Podlove\\Podcast' ) || defined( 'PODLOVE_VERSION' );
	}

	/**
	 * Enqueue integration styles if needed
	 *
	 * @since 1.0.0
	 */
	public static function enqueue_integration_styles() {
		// Only enqueue if we're on a post with audio or video format
		if ( ! is_singular() || ! has_post_format( array( 'audio', 'video' ) ) ) {
			return;
		}

		// Add inline styles for better player integration
		$custom_css = '';

		if ( self::is_ableplayer_active() ) {
			$custom_css .= '
				/* AblePlayer Integration */
				.format-audio .able-player,
				.format-video .able-player {
					width: 100%;
					max-width: 100%;
				}
				.format-audio .able-controls,
				.format-video .able-controls {
					background: var(--wp--preset--color--base, #f0f0f1);
				}
			';
		}

		if ( self::is_podlove_active() ) {
			$custom_css .= '
				/* Podlove Web Player Integration */
				.format-audio .podlove-web-player {
					width: 100%;
					max-width: 100%;
					border-radius: var(--pfpu-border-radius-large, 0.75rem);
					overflow: hidden;
				}
				.format-audio .podlove-web-player-root {
					background: var(--wp--preset--color--base, #f0f0f1);
				}
			';
		}

		if ( ! empty( $custom_css ) ) {
			wp_add_inline_style( 'pfpu-format-styles', $custom_css );
		}
	}

	/**
	 * Enhance media blocks with format-specific classes
	 *
	 * @since 1.0.0
	 * @param string $block_content Block HTML content.
	 * @param array  $block Block data.
	 * @return string Modified block content.
	 */
	public static function enhance_media_blocks( $block_content, $block ) {
		// Only process audio and video blocks
		if ( ! in_array( $block['blockName'], array( 'core/audio', 'core/video', 'core/embed' ), true ) ) {
			return $block_content;
		}

		// Only on audio/video format posts
		if ( ! has_post_format( array( 'audio', 'video' ) ) ) {
			return $block_content;
		}

		// Add helpful data attributes for player detection
		$format  = get_post_format();
		$players = array();

		if ( self::is_ableplayer_active() ) {
			$players[] = 'ableplayer';
		}

		if ( self::is_podlove_active() ) {
			$players[] = 'podlove';
		}

		if ( ! empty( $players ) ) {
			$data_attr = sprintf(
				' data-format="%s" data-players="%s"',
				esc_attr( $format ),
				esc_attr( implode( ',', $players ) )
			);

			// Add data attributes to the outermost element
			$block_content = preg_replace(
				'/^<(div|figure)([^>]*)>/',
				'<$1$2' . $data_attr . '>',
				$block_content,
				1
			);
		}

		return $block_content;
	}

	/**
	 * Get integration info for admin display
	 *
	 * @since 1.0.0
	 * @return array Integration status.
	 */
	public static function get_integration_info() {
		return array(
			'ableplayer' => array(
				'active'      => self::is_ableplayer_active(),
				'name'        => 'AblePlayer',
				'url'         => 'https://wordpress.org/plugins/ableplayer/',
				'description' => 'Fully accessible cross-browser HTML5 media player',
			),
			'podlove'    => array(
				'active'      => self::is_podlove_active(),
				'name'        => 'Podlove Podcasting Plugin',
				'url'         => 'https://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/',
				'description' => 'Professional podcast publishing for WordPress',
			),
		);
	}
}

// Initialize the integration
PFBT_Media_Player_Integration::init();
