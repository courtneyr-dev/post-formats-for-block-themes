<?php
/**
 * Unit tests for PFBT_Block_Style_Registry.
 *
 * @package PostFormatsBlockThemes
 * @covers PFBT_Block_Style_Registry
 */

/**
 * Class Test_Block_Style_Registry
 */
class Test_Block_Style_Registry extends WP_UnitTestCase {

	/**
	 * Reset the registry's load guard between tests so filter changes
	 * take effect on each new run.
	 */
	public function set_up() {
		parent::set_up();
		PFBT_Block_Style_Registry::instance()->reset_for_tests();

		// Default to "off" — each test that needs the flag enabled
		// flips it explicitly via filter.
		remove_all_filters( 'pfbt_feature_image_gallery_styles' );
		remove_all_filters( 'pfbt_image_style_variations' );
		remove_all_filters( 'pfbt_gallery_style_variations' );
	}

	/**
	 * Reset registry + remove filters between tests so state doesn't leak.
	 */
	public function tear_down() {
		PFBT_Block_Style_Registry::instance()->reset_for_tests();
		remove_all_filters( 'pfbt_feature_image_gallery_styles' );
		remove_all_filters( 'pfbt_image_style_variations' );
		remove_all_filters( 'pfbt_gallery_style_variations' );
		parent::tear_down();
	}

	/**
	 * The registry class is autoloaded by the main plugin file.
	 */
	public function test_class_is_loaded() {
		$this->assertTrue( class_exists( 'PFBT_Block_Style_Registry' ) );
	}

	/**
	 * The feature flag exists and defaults to false (opt-in for v2.1.0).
	 */
	public function test_feature_flag_default_is_off() {
		$this->assertFalse( PFBT_Feature_Flags::is_enabled( 'image_gallery_styles' ) );
		$this->assertFalse( PFBT_Feature_Flags::has_image_gallery_styles() );
	}

	/**
	 * The flag can be flipped on via filter.
	 */
	public function test_feature_flag_can_be_enabled() {
		add_filter( 'pfbt_feature_image_gallery_styles', '__return_true' );
		$this->assertTrue( PFBT_Feature_Flags::has_image_gallery_styles() );
	}

	/**
	 * The registry's accessors return arrays even when the disk
	 * definitions are empty (Phase 1 starting state).
	 */
	public function test_empty_definitions_return_empty_arrays() {
		$registry = PFBT_Block_Style_Registry::instance();
		$this->assertIsArray( $registry->get_image_variations() );
		$this->assertIsArray( $registry->get_gallery_variations() );
	}

	/**
	 * The pfbt_image_style_variations filter can add a variation.
	 *
	 * Confirms the registry pulls definitions through the filter so
	 * downstream extensions can register their own variations.
	 */
	public function test_image_variations_filter_can_add_entry() {
		add_filter(
			'pfbt_image_style_variations',
			static function ( $defs ) {
				$defs[] = array(
					'slug'       => 'test-fixture',
					'label'      => 'Test Fixture',
					'style_path' => 'styles/image-variations/test-fixture.css',
				);
				return $defs;
			}
		);

		PFBT_Block_Style_Registry::instance()->reset_for_tests();
		$variations = PFBT_Block_Style_Registry::instance()->get_image_variations();

		$this->assertArrayHasKey( 'test-fixture', $variations );
		$this->assertSame( 'Test Fixture', $variations['test-fixture']['label'] );
		$this->assertSame(
			'pfbt-image-variation-test-fixture',
			$variations['test-fixture']['style_handle'],
			'Default style_handle is computed when omitted.'
		);
	}

	/**
	 * Same shape for gallery variations.
	 */
	public function test_gallery_variations_filter_can_add_entry() {
		add_filter(
			'pfbt_gallery_style_variations',
			static function ( $defs ) {
				$defs[] = array(
					'slug'       => 'test-grid',
					'label'      => 'Test Grid',
					'style_path' => 'styles/gallery-variations/test-grid.css',
				);
				return $defs;
			}
		);

		PFBT_Block_Style_Registry::instance()->reset_for_tests();
		$variations = PFBT_Block_Style_Registry::instance()->get_gallery_variations();

		$this->assertArrayHasKey( 'test-grid', $variations );
		$this->assertSame(
			'pfbt-gallery-variation-test-grid',
			$variations['test-grid']['style_handle']
		);
	}

	/**
	 * Definitions missing required fields are dropped silently.
	 *
	 * The normalize() guard prevents misconfigured third-party entries
	 * from crashing the registry — they're just skipped. The 16 baseline
	 * variations shipped in v2.1.0 still register; only the malformed
	 * additions get dropped.
	 */
	public function test_invalid_definitions_are_dropped() {
		$baseline_count = count( PFBT_Block_Style_Registry::instance()->get_image_variations() );

		add_filter(
			'pfbt_image_style_variations',
			static function ( $defs ) {
				$defs[] = array( 'slug' => 'missing-label-and-path' );
				$defs[] = array(
					'label'      => 'Missing slug',
					'style_path' => 'x.css',
				);
				$defs[] = 'not-an-array';
				return $defs;
			}
		);

		PFBT_Block_Style_Registry::instance()->reset_for_tests();
		$variations = PFBT_Block_Style_Registry::instance()->get_image_variations();

		$this->assertArrayNotHasKey( 'missing-label-and-path', $variations );
		$this->assertCount(
			$baseline_count,
			$variations,
			'Malformed entries should be dropped; baseline count unchanged.'
		);
	}

	/**
	 * Explicit style_handle in the definition wins over the default.
	 */
	public function test_explicit_style_handle_is_preserved() {
		add_filter(
			'pfbt_image_style_variations',
			static function ( $defs ) {
				$defs[] = array(
					'slug'         => 'custom-handle',
					'label'        => 'Custom Handle',
					'style_path'   => 'styles/image-variations/custom-handle.css',
					'style_handle' => 'my-theme-custom-handle',
				);
				return $defs;
			}
		);

		PFBT_Block_Style_Registry::instance()->reset_for_tests();
		$variations = PFBT_Block_Style_Registry::instance()->get_image_variations();

		$this->assertSame( 'my-theme-custom-handle', $variations['custom-handle']['style_handle'] );
	}

	/**
	 * Register() runs without errors when the flag is on and the
	 * definitions arrays are populated. Smoke test only — verifying
	 * actual register_block_style() side effects requires the WP
	 * block-styles registry, which set_up() inherits via WP_UnitTestCase.
	 */
	public function test_register_runs_clean_with_fixture_variations() {
		add_filter( 'pfbt_feature_image_gallery_styles', '__return_true' );
		add_filter(
			'pfbt_image_style_variations',
			static function ( $defs ) {
				$defs[] = array(
					'slug'       => 'smoke-test',
					'label'      => 'Smoke Test',
					'style_path' => 'styles/image-variations/smoke-test.css',
				);
				return $defs;
			}
		);

		$registry = PFBT_Block_Style_Registry::instance();
		$registry->reset_for_tests();
		$registry->register();

		$this->assertTrue( wp_style_is( 'pfbt-image-variation-smoke-test', 'registered' ) );
	}
}
