<?php
/**
 * Regression guards for add_block_templates() honoring the query contract.
 *
 * resolve_block_template() sorts results by each slug's position in the
 * requested slug__in list, so any synthetic template returned for a slug
 * the query never asked for triggers an undefined-array-key warning and
 * can outrank the theme's real template (the v2.3.0 badge-template bug,
 * and PR #31's default pseudo-template bug, were both this).
 *
 * @package PostFormatsForBlockThemes
 */

class Test_Template_Query_Contract extends WP_UnitTestCase {

	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	/**
	 * A slug-limited admin query must only ever get slugs it asked for.
	 */
	public function test_slug_limited_query_gets_no_unrequested_slugs() {
		set_current_screen( 'edit' );

		$requested = array( 'single', 'singular', 'index' );
		$result    = PFBT_Format_Styles::add_block_templates(
			array(),
			array( 'slug__in' => $requested ),
			'wp_template'
		);

		$unrequested = array_diff( wp_list_pluck( $result, 'slug' ), $requested );
		$this->assertSame(
			array(),
			array_values( $unrequested ),
			'add_block_templates() returned slugs the query never requested'
		);
	}

	/**
	 * The default pseudo-template still appears where it belongs: the
	 * editor's unfiltered template list.
	 */
	public function test_unfiltered_admin_query_still_gets_default() {
		set_current_screen( 'edit' );

		$result = PFBT_Format_Styles::add_block_templates(
			array(),
			array( 'post_type' => 'post' ),
			'wp_template'
		);

		$this->assertContains(
			'default',
			wp_list_pluck( $result, 'slug' ),
			'The Default pseudo-template is missing from the editor template list'
		);
	}

	/**
	 * A slug-limited query that explicitly asks for default gets it.
	 */
	public function test_default_returned_when_explicitly_requested() {
		set_current_screen( 'edit' );

		$result = PFBT_Format_Styles::add_block_templates(
			array(),
			array( 'slug__in' => array( 'default' ) ),
			'wp_template'
		);

		$this->assertContains( 'default', wp_list_pluck( $result, 'slug' ) );
	}

	/**
	 * Front-end (non-admin, non-REST) queries never get the pseudo-template.
	 */
	public function test_front_end_query_never_gets_default() {
		$result = PFBT_Format_Styles::add_block_templates(
			array(),
			array( 'slug__in' => array( 'single', 'default' ) ),
			'wp_template'
		);

		$this->assertNotContains( 'default', wp_list_pluck( $result, 'slug' ) );
	}
}
