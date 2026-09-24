<?php
/**
 * Output-level accessibility fixes for markup the plugin renders on the
 * front end. Companion to the courtneyr-child theme's inc/a11y-output.php —
 * the empty-paragraph filter here matches that theme's implementation so
 * the two hold consistently wherever either is active.
 *
 * @package PostFormatsBlockThemes
 * @since 1.1.8
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PFBT_A11y_Output
 *
 * Singleton; registered on plugin bootstrap. Front-end-only render_block
 * filters that close accessibility gaps in plugin-authored markup.
 *
 * @since 1.1.8
 */
class PFBT_A11y_Output {

	/**
	 * Singleton instance.
	 *
	 * @since 1.1.8
	 * @var PFBT_A11y_Output|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance and register filters.
	 *
	 * @since 1.1.8
	 * @return PFBT_A11y_Output
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wires the filters.
	 *
	 * @since 1.1.8
	 */
	private function __construct() {
		add_filter( 'render_block_core/paragraph', array( $this, 'drop_empty_paragraph' ) );
	}

	/**
	 * An empty paragraph (a stray Enter in the editor, or a format pattern's
	 * placeholder left blank) renders as <p></p> and is announced as a blank
	 * line by screen readers. Drop it on the front end; the editor still
	 * shows the block so authors can click into it.
	 *
	 * Matches the courtneyr-child theme's drop_empty_paragraph() filter so
	 * both hold consistently whether the plugin, the theme, or both are
	 * active on a given install.
	 *
	 * @since 1.1.8
	 *
	 * @param string $content Rendered block.
	 * @return string
	 */
	public function drop_empty_paragraph( $content ) {
		if ( is_admin() ) {
			return $content;
		}
		return preg_match( '#^\s*<p\b[^>]*>(?:\s|&nbsp;|\xC2\xA0)*</p>\s*$#u', $content ) ? '' : $content;
	}
}
