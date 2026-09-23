<?php
/**
 * Unit tests for pattern registration at activation and upgrade
 *
 * Regression coverage for an H1 review finding: pfbt_activate() used to
 * call PFBT_Pattern_Manager::force_register_patterns() directly and store
 * pfbt_version. An activation hook fires via an include_once() of the
 * plugin's main file at the exact moment WordPress activates it, which
 * can happen before this same request's own 'plugins_loaded' has fired
 * pfbt_include_files() again — so PFBT_Pattern_Manager was not
 * guaranteed to be loaded, and activation could fatal with "Class
 * PFBT_Pattern_Manager not found". Pattern creation and recording
 * pfbt_version now happen only in pfbt_maybe_upgrade(), on admin_init.
 *
 * @package PostFormatsBlockThemes
 * @since 1.1.7
 */

class Test_Pattern_Activation extends WP_UnitTestCase {

	/**
	 * Stylesheet active before this test switched themes.
	 *
	 * @var string
	 */
	private $original_theme;

	/**
	 * Set up test
	 */
	public function set_up() {
		parent::set_up();

		// pfbt_activate() refuses to proceed on a classic theme; switch
		// to a bundled block theme so the real activation path can run
		// end-to-end in these tests.
		$this->original_theme = get_option( 'stylesheet' );
		switch_theme( 'twentytwentyfour' );

		delete_option( 'pfbt_version' );
		delete_option( 'pfbt_activated_time' );
		delete_transient( 'pfbt_patterns_registered' );
	}

	/**
	 * Tear down test
	 */
	public function tear_down() {
		switch_theme( $this->original_theme );
		delete_option( 'pfbt_version' );
		delete_option( 'pfbt_activated_time' );
		delete_transient( 'pfbt_patterns_registered' );
		parent::tear_down();
	}

	/**
	 * Firing the activation hook must not error, and — this is what would
	 * have caught the original bug — must not itself write pfbt_version
	 * or create any pattern post. Both now happen only in
	 * pfbt_maybe_upgrade() on admin_init.
	 *
	 * set_current_screen( 'plugins' ) is required for the pattern-post
	 * assertion to mean anything: PFBT_Pattern_Manager::register_all_
	 * patterns() no-ops on its own admin/ajax/REST guard whenever
	 * is_admin() is false, so without it the assertion would pass
	 * whether or not pfbt_activate() still called it — a reintroduced
	 * `PFBT_Pattern_Manager::force_register_patterns()` call would go
	 * undetected. With is_admin() true, that regression fails this test
	 * with "10 does not match expected 0".
	 *
	 * A PHPUnit run always has every plugin class loaded up front, unlike
	 * a real activation request, so this still can't reproduce the
	 * original "Class not found" fatal directly. It pins the behavioral
	 * contract the fix establishes instead: activation creates no
	 * patterns and writes no version, in any admin context.
	 */
	public function test_activation_does_not_touch_patterns_or_version() {
		set_current_screen( 'plugins' );

		$blocks_before = wp_count_posts( 'wp_block' );

		do_action( 'activate_' . PFBT_PLUGIN_BASENAME );

		$this->assertFalse(
			get_option( 'pfbt_version' ),
			'Activation must not write pfbt_version — see pfbt_maybe_upgrade() for why.'
		);

		$blocks_after = wp_count_posts( 'wp_block' );
		$this->assertEquals(
			$blocks_before->publish,
			$blocks_after->publish,
			'Activation must not create pattern posts.'
		);
	}

	/**
	 * Activation still sets the plugin's own timestamp option, which has
	 * no dependency on PFBT_Pattern_Manager.
	 */
	public function test_activation_still_records_activated_time() {
		do_action( 'activate_' . PFBT_PLUGIN_BASENAME );

		$this->assertNotFalse( get_option( 'pfbt_activated_time' ) );
	}

	/**
	 * pfbt_maybe_upgrade() is registered on admin_init.
	 */
	public function test_maybe_upgrade_is_registered_on_admin_init() {
		$this->assertNotFalse( has_action( 'admin_init', 'pfbt_maybe_upgrade' ) );
	}

	/**
	 * pfbt_maybe_upgrade(), which fires on admin_init, is where patterns
	 * actually get (re)created and pfbt_version recorded — the path a
	 * fresh install or an in-place upgrade relies on, now that
	 * activation itself does neither.
	 *
	 * Calls the callback directly rather than `do_action( 'admin_init' )`:
	 * firing the whole hook also runs WordPress core's own admin_init
	 * callbacks (wp_admin_headers(), send_frame_options_header()), which
	 * call header() and throw "headers already sent" warnings under the
	 * CLI test runner — noise unrelated to what this test is checking.
	 * test_maybe_upgrade_is_registered_on_admin_init() above confirms the
	 * wiring; this confirms the behavior.
	 */
	public function test_maybe_upgrade_registers_patterns() {
		set_current_screen( 'edit' );

		pfbt_maybe_upgrade();

		$this->assertSame( PFBT_VERSION, get_option( 'pfbt_version' ) );

		$blocks = get_posts(
			array(
				'post_type'      => 'wp_block',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);
		$this->assertNotEmpty( $blocks, 'pfbt_maybe_upgrade() should have created synced pattern posts.' );
	}

	/**
	 * A second run after the version is already current must not re-run
	 * pattern registration.
	 */
	public function test_maybe_upgrade_is_a_noop_once_version_matches() {
		update_option( 'pfbt_version', PFBT_VERSION );
		set_current_screen( 'edit' );

		$blocks_before = wp_count_posts( 'wp_block' );
		pfbt_maybe_upgrade();
		$blocks_after = wp_count_posts( 'wp_block' );

		$this->assertEquals( $blocks_before->publish, $blocks_after->publish );
	}
}
