<?php
/**
 * Tests for PFBT_Icon_Set — the v2.3.0 icon-set picker.
 *
 * Covers:
 * - Default set resolution
 * - Sanitization rejecting unknown slugs
 * - Filter registration on pfbt_format_icon_sprite_url
 * - get_available_sets() always includes the default
 * - URL resolution path shape
 *
 * @package PostFormatsBlockThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__, 2 ) . '/includes/class-pfbt-icon-set.php';

/**
 * @covers PFBT_Icon_Set
 */
class Test_Icon_Set extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( PFBT_Icon_Set::OPTION_KEY );
		remove_all_filters( 'pfbt_available_icon_sets' );
		remove_all_filters( 'pfbt_icon_set_sprite_url' );
		parent::tear_down();
	}

	public function test_default_active_set_is_hand_drawn() {
		$this->assertSame( 'hand-drawn', PFBT_Icon_Set::get_active_set_slug() );
	}

	public function test_saved_set_is_returned_when_known() {
		update_option( PFBT_Icon_Set::OPTION_KEY, 'filled' );
		$this->assertSame( 'filled', PFBT_Icon_Set::get_active_set_slug() );
	}

	public function test_unknown_saved_set_falls_back_to_default() {
		update_option( PFBT_Icon_Set::OPTION_KEY, 'nonexistent' );
		$this->assertSame( 'hand-drawn', PFBT_Icon_Set::get_active_set_slug() );
	}

	public function test_sanitize_rejects_unknown_slug() {
		$this->assertSame( 'hand-drawn', PFBT_Icon_Set::sanitize_set_slug( 'totally-fake' ) );
	}

	public function test_sanitize_accepts_known_slug() {
		$this->assertSame( 'filled', PFBT_Icon_Set::sanitize_set_slug( 'filled' ) );
	}

	public function test_sanitize_handles_non_string_input() {
		$this->assertSame( 'hand-drawn', PFBT_Icon_Set::sanitize_set_slug( null ) );
		$this->assertSame( 'hand-drawn', PFBT_Icon_Set::sanitize_set_slug( array() ) );
		$this->assertSame( 'hand-drawn', PFBT_Icon_Set::sanitize_set_slug( 42 ) );
	}

	public function test_default_set_always_present_after_filter_drops_it() {
		add_filter(
			'pfbt_available_icon_sets',
			static function () {
				return array( 'filled' => 'Filled' );
			}
		);
		$sets = PFBT_Icon_Set::get_available_sets();
		$this->assertArrayHasKey( 'hand-drawn', $sets, 'Default set must always be present even if a filter drops it' );
	}

	public function test_third_party_set_can_register_via_filter() {
		add_filter(
			'pfbt_available_icon_sets',
			static function ( $sets ) {
				$sets['custom'] = 'Custom set';
				return $sets;
			}
		);
		$sets = PFBT_Icon_Set::get_available_sets();
		$this->assertArrayHasKey( 'custom', $sets );
	}

	public function test_get_sprite_url_uses_conventional_path() {
		$url = PFBT_Icon_Set::get_sprite_url( 'filled' );
		$this->assertStringContainsString( 'img/icon-sets/filled/format-icons.svg', $url );
	}

	public function test_filter_registers_at_priority_below_theme_default() {
		PFBT_Icon_Set::register_filter();
		$priority = has_filter( 'pfbt_format_icon_sprite_url', array( 'PFBT_Icon_Set', 'filter_sprite_url' ) );
		$this->assertSame( 5, $priority, 'Filter must register at priority 5 so theme overrides at 10+ win.' );
		remove_filter( 'pfbt_format_icon_sprite_url', array( 'PFBT_Icon_Set', 'filter_sprite_url' ), 5 );
	}

	public function test_pfbt_icon_set_sprite_url_filter_overrides_default() {
		add_filter(
			'pfbt_icon_set_sprite_url',
			static function () {
				return 'https://example.test/custom-sprite.svg';
			}
		);
		$this->assertSame( 'https://example.test/custom-sprite.svg', PFBT_Icon_Set::get_sprite_url( 'hand-drawn' ) );
	}
}
