<?php
/**
 * Quote block choice setting.
 *
 * Lets site owners choose which block the Quote format uses as its
 * default first block: core/quote or core/pullquote. Auto-detection
 * always accepts both (the non-default one stays registered as an
 * alt_blocks alias on the quote format); this setting only controls
 * which block the quote pattern inserts and which block the plugin
 * reports as the format's first_block.
 *
 * @package PostFormatsBlockThemes
 * @since 2.4.0
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quote block choice setting.
 *
 * @since 2.4.0
 */
class PFBT_Quote_Block_Setting {

	/**
	 * Option key storing the chosen slug ('quote' or 'pullquote').
	 *
	 * @var string
	 */
	const OPTION_KEY = 'pfbt_quote_block';

	/**
	 * Default choice.
	 *
	 * @var string
	 */
	const DEFAULT_CHOICE = 'quote';

	/**
	 * Hook everything up.
	 *
	 * @since 2.4.0
	 */
	public static function init() {
		// The synced quote pattern (wp_block) sits behind a week-long
		// transient; bust it when the choice changes so the stored
		// pattern re-renders with the newly chosen block.
		add_action( 'update_option_' . self::OPTION_KEY, array( 'PFBT_Pattern_Manager', 'force_register_patterns' ) );
		add_action( 'add_option_' . self::OPTION_KEY, array( 'PFBT_Pattern_Manager', 'force_register_patterns' ) );
	}

	/**
	 * Get the available choices.
	 *
	 * @since 2.4.0
	 * @return array Slug => array with 'label' and 'block' keys.
	 */
	public static function get_available_choices() {
		return array(
			'quote'     => array(
				'label' => __( 'Quote block (default)', 'post-formats-for-block-themes' ),
				'block' => 'core/quote',
			),
			'pullquote' => array(
				'label' => __( 'Pullquote block', 'post-formats-for-block-themes' ),
				'block' => 'core/pullquote',
			),
		);
	}

	/**
	 * Get the active choice slug, validated against the allowed list.
	 *
	 * @since 2.4.0
	 * @return string 'quote' or 'pullquote'.
	 */
	public static function get_active_slug() {
		$saved   = (string) get_option( self::OPTION_KEY, self::DEFAULT_CHOICE );
		$allowed = self::get_available_choices();

		return isset( $allowed[ $saved ] ) ? $saved : self::DEFAULT_CHOICE;
	}

	/**
	 * Get the block name for the active choice.
	 *
	 * @since 2.4.0
	 * @return string 'core/quote' or 'core/pullquote'.
	 */
	public static function get_block_name() {
		$choices = self::get_available_choices();

		return $choices[ self::get_active_slug() ]['block'];
	}

	/**
	 * Get the block name for the non-active choice.
	 *
	 * Detection registers this as an alias so pullquote-first and
	 * quote-first posts both classify as quote regardless of the
	 * chosen default.
	 *
	 * @since 2.4.0
	 * @return string 'core/quote' or 'core/pullquote'.
	 */
	public static function get_alternate_block_name() {
		return 'quote' === self::get_active_slug() ? 'core/pullquote' : 'core/quote';
	}

	/**
	 * Register the setting with the WP Settings API.
	 *
	 * @since 2.4.0
	 */
	public static function register_setting() {
		register_setting(
			'pfbt_settings',
			self::OPTION_KEY,
			array(
				'type'              => 'string',
				'default'           => self::DEFAULT_CHOICE,
				'sanitize_callback' => array( __CLASS__, 'sanitize_choice' ),
			)
		);
	}

	/**
	 * Sanitize a submitted choice.
	 *
	 * @since 2.4.0
	 *
	 * @param mixed $value Submitted value.
	 * @return string Valid choice slug.
	 */
	public static function sanitize_choice( $value ) {
		$value   = is_string( $value ) ? sanitize_key( $value ) : '';
		$allowed = self::get_available_choices();

		return isset( $allowed[ $value ] ) ? $value : self::DEFAULT_CHOICE;
	}
}
