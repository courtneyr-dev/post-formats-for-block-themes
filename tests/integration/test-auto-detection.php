<?php
/**
 * Integration tests for format auto-detection
 *
 * Tests the complete auto-detection workflow with WordPress, including the
 * v1.2.0 re-enable + apply-once semantics and the C1 coordination contract
 * with upstream Micropub clients (Outpost).
 *
 * @package PostFormatsBlockThemes
 */

class Test_Auto_Detection extends WP_UnitTestCase {

	/**
	 * First save on a gallery-first-block post applies gallery format and writes audit meta.
	 */
	public function test_first_save_applies_gallery_and_writes_audit() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:gallery {"linkTo":"none"} --><figure class="wp-block-gallery"></figure><!-- /wp:gallery -->',
			)
		);

		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$this->assertSame( 'gallery', get_post_format( $post_id ) );
		$this->assertSame( 'gallery', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ) );
		$this->assertSame( '1', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_APPLIED, true ) );
	}

	/**
	 * Quote-first-block post applies quote format.
	 */
	public function test_first_save_applies_quote_format() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Test quote</p></blockquote><!-- /wp:quote -->',
			)
		);

		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$this->assertSame( 'quote', get_post_format( $post_id ) );
	}

	/**
	 * Empty content resolves to standard, sets applied flag.
	 *
	 * WordPress returns false from get_post_format() for the standard format.
	 */
	public function test_empty_post_resolves_to_standard() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '',
			)
		);

		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$this->assertFalse( get_post_format( $post_id ) );
		$this->assertSame( 'standard', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ) );
	}

	/**
	 * Manual flag prevents detector from overriding the user's choice.
	 *
	 * Pre-v1.2.0 regression bug: the original test used the wrong meta key
	 * (`_pfbt_manual_format` instead of `_pfbt_format_manual`), so this
	 * assertion never exercised the manual-override path. Fixed to use the
	 * actual constant exported by PFBT_Format_Detector.
	 */
	public function test_manual_flag_blocks_format_apply_but_writes_audit() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:audio --><figure class="wp-block-audio"></figure><!-- /wp:audio -->',
			)
		);

		PFBT_Format_Detector::mark_as_manual( $post_id );
		set_post_format( $post_id, 'standard' );

		do_action( 'save_post', $post_id, get_post( $post_id ), false );

		$this->assertFalse( get_post_format( $post_id ), 'Manual flag must prevent format override' );
		$this->assertSame( 'audio', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ), 'Audit meta must record what would have been detected' );
		$this->assertEmpty( get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_APPLIED, true ), 'Applied flag must NOT be set when manual flag blocked application' );
	}

	/**
	 * Subsequent save on the same post does NOT reclassify (v1.1.2 regression guard).
	 *
	 * Reproduces the bug that caused autodetection to be disabled in commit d01027f:
	 *   1. User creates a post that happens to start with a video block.
	 *   2. Detector classifies it as 'video' on first save.
	 *   3. User changes the format to 'standard' in the block editor.
	 *   4. User saves again WITHOUT touching the format dropdown (Gutenberg omits
	 *      the `format` REST param when unchanged).
	 *   5. PRE-FIX: detector runs detection again, reclassifies as 'video', user's
	 *      'standard' choice silently reverts.
	 *   6. POST-FIX: applied-flag guard means the second save is a no-op for format,
	 *      but the audit meta still refreshes.
	 */
	public function test_subsequent_save_does_not_reclassify_after_user_change() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:video --><figure class="wp-block-video"></figure><!-- /wp:video -->',
			)
		);

		// First save: detector applies video.
		do_action( 'save_post', $post_id, get_post( $post_id ), false );
		$this->assertSame( 'video', get_post_format( $post_id ) );
		$this->assertSame( '1', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_APPLIED, true ) );

		// User manually changes format to standard (no manual flag set, since the
		// editor would set that only if the format param differs in the REST call —
		// here we simulate the block-editor side mutating the taxonomy directly).
		set_post_format( $post_id, 'standard' );

		// Second save with same content. Without the applied-flag guard, this is the
		// scenario where detector would reclassify back to 'video'.
		do_action( 'save_post', $post_id, get_post( $post_id ), true );

		$this->assertFalse( get_post_format( $post_id ), 'Standard format must persist; detector must not reclassify on subsequent save' );
		$this->assertSame( 'video', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ), 'Audit meta records current detection result even when not applied' );
	}

	/**
	 * Outpost C1 coordination — mp-post-format pre-set + mark_as_manual preserves Outpost's choice.
	 *
	 * Simulates the Outpost Micropub bridge sequence: Shanske's Micropub plugin
	 * creates the post → PFBT detector fires on save_post → Outpost's after_micropub
	 * hook reads mp-post-format from the request and overrides PFBT's choice while
	 * marking the post as manual for future saves.
	 *
	 * Expected end state:
	 *   - Outpost's chosen format is preserved (NOT overridden by detection)
	 *   - Manual flag persists across subsequent saves
	 *   - Audit meta records what PFBT would have detected (for divergence telemetry)
	 */
	public function test_outpost_coordination_preserves_mp_post_format() {
		$post_id = $this->factory->post->create(
			array(
				'post_content' => '<!-- wp:image --><figure class="wp-block-image"><img src="cover.jpg"/></figure><!-- /wp:image -->',
			)
		);

		// PFBT detector fires first (via save_post during wp_insert_post).
		do_action( 'save_post', $post_id, get_post( $post_id ), false );
		$this->assertSame( 'image', get_post_format( $post_id ) );
		$this->assertSame( 'image', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ) );

		// Outpost bridge runs at after_micropub: overrides to 'audio' (Listen variant)
		// and marks the post as manual so future saves respect Outpost's choice.
		set_post_format( $post_id, 'audio' );
		PFBT_Format_Detector::mark_as_manual( $post_id );

		// Simulate a later save (Outpost re-sync, user edit, plugin running again).
		do_action( 'save_post', $post_id, get_post( $post_id ), true );

		$this->assertSame( 'audio', get_post_format( $post_id ), 'Outpost-set format must be preserved across saves' );
		$this->assertTrue( PFBT_Format_Detector::is_manual( $post_id ), 'Manual flag must persist' );
		$this->assertSame( 'image', get_post_meta( $post_id, PFBT_Format_Detector::META_KEY_DETECTED, true ), 'Audit meta records what PFBT would have detected' );
	}
}
