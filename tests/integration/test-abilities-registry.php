<?php
/**
 * Integration tests for what actually lands in the Abilities registry.
 *
 * @package PostFormatsBlockThemes
 * @since 1.3.0
 */

/**
 * Assert every expected ability is present in wp_get_abilities().
 *
 * Core rejects an ability whose name doesn't match /^[a-z0-9-]+\/[a-z0-9-]+$/,
 * and separately rejects one whose name is already taken. Both failures are a
 * _doing_it_wrong() notice and nothing else, so with WP_DEBUG off they leave no
 * trace — which is how fifteen `post_formats/*` names sat unregistered from
 * 1.2.0 until the WP 7.1-RC1 audit.
 *
 * test-abilities-registration.php covers feature flags and the execute_*
 * callbacks; neither can tell "registered" from "silently refused". This file
 * is the one that can.
 *
 * @covers PFBT_Abilities_Manager
 */
class Test_Abilities_Registry extends WP_UnitTestCase {

	/**
	 * Core's ability name grammar, copied from WP_Abilities_Registry::register().
	 */
	const NAME_PATTERN = '/^[a-z0-9-]+\/[a-z0-9-]+$/';

	/**
	 * Every ability this plugin registers with default feature flags.
	 *
	 * IndieWeb and MCP both default to enabled; ActivityPub defaults to off and
	 * registers nothing yet, so it contributes no names here.
	 *
	 * @return string[]
	 */
	private function expected_abilities() {
		return array(
			// PFBT_Abilities_Formats.
			'post-formats/detect-format',
			'post-formats/switch-format',
			'post-formats/repair-formats',
			'post-formats/get-format-stats',

			// PFBT_Core_Abilities.
			'post-formats/list-formats',
			'post-formats/get-format-template',
			'post-formats/validate-format',
			'post-formats/set-post-format',
			'post-formats/get-post-format',
			'post-formats/score-format',

			// PFBT_IndieWeb_Abilities.
			'post-formats/mf2-markup',
			'post-formats/mf2-validate',
			'post-formats/posse-prepare',
			'post-formats/posse-targets',
			'post-formats/webmention-context',

			// PFBT_MCP_Abilities.
			'post-formats/suggest-format',
			'post-formats/analyze-content',
			'post-formats/validate-format-content',
			'post-formats/get-format-signals',
		);
	}

	/**
	 * Every expected name satisfies core's grammar.
	 *
	 * Runs without the Abilities API present so a bad name fails fast and names
	 * itself, instead of surfacing as a missing-key assertion.
	 */
	public function test_expected_names_match_core_grammar() {
		foreach ( $this->expected_abilities() as $name ) {
			$this->assertMatchesRegularExpression(
				self::NAME_PATTERN,
				$name,
				sprintf( 'Ability "%s" would be rejected by WP_Abilities_Registry::register().', $name )
			);
		}
	}

	/**
	 * Expected names are unique.
	 *
	 * Four classes register into one namespace, so a rename that collapses two
	 * onto the same name loses whichever registers second.
	 */
	public function test_expected_names_are_unique() {
		$expected = $this->expected_abilities();

		$this->assertSame(
			$expected,
			array_values( array_unique( $expected ) ),
			'Duplicate ability names — the second registration would be rejected.'
		);
	}

	/**
	 * Every expected ability is present in the registry after init.
	 */
	public function test_expected_abilities_are_registered() {
		$this->skip_without_abilities_api();

		$registered = array_keys( wp_get_abilities() );
		$missing    = array_diff( $this->expected_abilities(), $registered );

		$this->assertSame(
			array(),
			array_values( $missing ),
			'Expected abilities are missing from the registry: ' . implode( ', ', $missing )
		);
	}

	/**
	 * Nothing registers under post-formats/ that this test doesn't know about.
	 *
	 * Keeps the expected list honest — a new ability has to be added here, which
	 * is what makes the missing-name assertion above meaningful.
	 */
	public function test_no_unexpected_abilities_are_registered() {
		$this->skip_without_abilities_api();

		$registered = array_keys( wp_get_abilities( array( 'namespace' => 'post-formats' ) ) );
		$unexpected = array_diff( $registered, $this->expected_abilities() );

		$this->assertSame(
			array(),
			array_values( $unexpected ),
			'Abilities registered but not listed in expected_abilities(): ' . implode( ', ', $unexpected )
		);
	}

	/**
	 * Skip when running against a WordPress without the Abilities API.
	 */
	private function skip_without_abilities_api() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			$this->markTestSkipped( 'Abilities API not available on this WordPress version (needs 6.9+).' );
		}
	}
}
