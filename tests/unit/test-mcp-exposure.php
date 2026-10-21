<?php
/**
 * MCP exposure of the Post Formats abilities.
 *
 * @package PostFormatsBlockThemes
 */

/**
 * Read abilities are public on the MCP Adapter's default server. Write
 * abilities keep whatever their own registration says; changing that is a
 * separate decision.
 *
 * @covers PFBT_Abilities_Manager
 */
class Test_MCP_Exposure extends WP_UnitTestCase {

	/**
	 * Reset the abilities registry so later tests rebuild it lazily.
	 */
	public function tear_down() {
		$this->reset_abilities_registry();
		parent::tear_down();
	}

	/**
	 * Discard the WP_Abilities_Registry singleton.
	 */
	private function reset_abilities_registry() {
		if ( ! class_exists( 'WP_Abilities_Registry' ) ) {
			return;
		}
		$property = new ReflectionProperty( WP_Abilities_Registry::class, 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Read ability names.
	 *
	 * @return array
	 */
	public static function read_ability_names() {
		$names = PFBT_Abilities_Manager::MCP_PUBLIC_ABILITIES;
		return array_combine(
			$names,
			array_map(
				static function ( $name ) {
					return array( $name );
				},
				$names
			)
		);
	}

	/**
	 * The fourteen reads, by name, so the list can't drift silently.
	 */
	public function test_read_list_is_the_fourteen_reads() {
		$expected = array(
			'post-formats/list-formats',
			'post-formats/get-format-template',
			'post-formats/validate-format',
			'post-formats/get-post-format',
			'post-formats/score-format',
			'post-formats/suggest-format',
			'post-formats/analyze-content',
			'post-formats/validate-format-content',
			'post-formats/get-format-signals',
			'post-formats/mf2-markup',
			'post-formats/mf2-validate',
			'post-formats/posse-prepare',
			'post-formats/posse-targets',
			'post-formats/webmention-context',
		);
		$this->assertSame( $expected, PFBT_Abilities_Manager::MCP_PUBLIC_ABILITIES );
		$this->assertNotContains( 'post-formats/set-post-format', PFBT_Abilities_Manager::MCP_PUBLIC_ABILITIES );
	}

	/**
	 * @dataProvider read_ability_names
	 *
	 * @param string $name Ability name.
	 */
	public function test_read_abilities_are_public( $name ) {
		$args   = array( 'meta' => array( 'show_in_rest' => true ) );
		$result = PFBT_Abilities_Manager::filter_ability_args( $args, $name );

		$this->assertTrue( $result['meta']['mcp']['public'] );
		$this->assertTrue( $result['meta']['show_in_rest'] );
	}

	/**
	 * A write that isn't listed passes through untouched.
	 */
	public function test_set_post_format_is_untouched() {
		$args = array( 'meta' => array( 'show_in_rest' => true ) );

		$this->assertSame( $args, PFBT_Abilities_Manager::filter_ability_args( $args, 'post-formats/set-post-format' ) );
	}

	/**
	 * Writes that already declare their own exposure keep it.
	 */
	public function test_switch_format_keeps_its_own_exposure() {
		$args = array(
			'meta' => array(
				'mcp' => array(
					'public' => true,
					'type'   => 'tool',
				),
			),
		);

		$this->assertSame( $args, PFBT_Abilities_Manager::filter_ability_args( $args, 'post-formats/switch-format' ) );
	}

	/**
	 * Other namespaces are never touched.
	 */
	public function test_other_namespaces_are_untouched() {
		$args = array( 'label' => 'Test' );

		$this->assertSame( $args, PFBT_Abilities_Manager::filter_ability_args( $args, 'other/list-formats' ) );
	}

	/**
	 * End to end: registered reads are public, set-post-format isn't.
	 */
	public function test_registered_abilities_carry_mcp_exposure() {
		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'Abilities API not available on this WordPress version (needs 6.9+).' );
		}

		$this->reset_abilities_registry();
		wp_get_abilities();

		$checked = 0;
		foreach ( PFBT_Abilities_Manager::MCP_PUBLIC_ABILITIES as $name ) {
			$ability = wp_get_ability( $name );
			if ( null === $ability ) {
				continue; // A provider behind an off flag; covered by the unit cases.
			}
			++$checked;
			$meta = $ability->get_meta();
			$this->assertTrue( $meta['mcp']['public'] ?? false, sprintf( '%s should be public on MCP.', $name ) );
		}
		$this->assertGreaterThanOrEqual( 6, $checked, 'At least the core reads should be registered.' );

		$set = wp_get_ability( 'post-formats/set-post-format' );
		$this->assertNotNull( $set );
		$this->assertFalse( $set->get_meta()['mcp']['public'] ?? false );
	}
}
