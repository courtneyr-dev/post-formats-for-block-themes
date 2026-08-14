<?php
/**
 * Test_ActivityPub_Transformer — $post argument handling in the filter callbacks.
 *
 * The activitypub_* filters do not guarantee a WP_Post. Depending on the call
 * site the second argument arrives as a post ID, and dereferencing it directly
 * emitted "Attempt to read property post_type on string" on PHP 8 (#26).
 *
 * These tests assert on the warning itself rather than the return value,
 * because the callbacks already returned the input unchanged when the
 * dereference failed — the bug was noisy, not wrong, so a test that only
 * checked return values passed both before and after the fix.
 *
 * @package PostFormatsBlockThemes
 * @since 1.1.6
 *
 * @covers \PFBT_ActivityPub_Transformer
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase

class Test_ActivityPub_Transformer extends WP_UnitTestCase {

	/**
	 * Transformer under test.
	 *
	 * @var PFBT_ActivityPub_Transformer
	 */
	private $transformer;

	/**
	 * A published post carrying a non-standard format.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * PHP diagnostics captured while a callback ran.
	 *
	 * @var array<int, string>
	 */
	private $captured = array();

	/**
	 * Set up the transformer and a formatted post.
	 */
	public function set_up() {
		parent::set_up();

		$this->transformer = PFBT_ActivityPub_Transformer::instance();

		$this->post_id = self::factory()->post->create(
			array(
				'post_type'    => 'post',
				'post_title'   => 'warning probe',
				'post_status'  => 'publish',
				'post_content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->',
			)
		);

		set_post_format( $this->post_id, 'status' );
	}

	/**
	 * Run a callback with PHP diagnostics captured rather than reported.
	 *
	 * set_error_handler is used instead of PHPUnit's convertWarningsToExceptions
	 * so a single test can assert on the *count* of warnings, which is what #26
	 * reported: six for one post, zero once guarded.
	 *
	 * @param callable $callback Callback to run.
	 * @return mixed Whatever the callback returned.
	 */
	private function capture_diagnostics( callable $callback ) {
		$this->captured = array();

		set_error_handler(
			function ( $errno, $errstr ) {
				$this->captured[] = $errstr;
				return true;
			},
			E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE
		);

		try {
			return $callback();
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * Assert the last captured run produced no property-access diagnostics.
	 *
	 * Scoped to "Attempt to read property" so an unrelated notice from
	 * elsewhere in the stack cannot mask or fake this regression.
	 */
	private function assertNoPropertyWarnings( string $context ) {
		$property = array_filter(
			$this->captured,
			static function ( $message ) {
				return false !== strpos( $message, 'Attempt to read property' );
			}
		);

		$this->assertSame(
			array(),
			array_values( $property ),
			"$context emitted a property-access warning: " . implode( ' | ', $property )
		);
	}

	/**
	 * A post ID is what ActivityPub actually passes, and it must not warn.
	 */
	public function test_filter_object_type_accepts_a_post_id() {
		$type = $this->capture_diagnostics(
			function () {
				return $this->transformer->filter_object_type( 'Note', $this->post_id, '' );
			}
		);

		$this->assertNoPropertyWarnings( 'filter_object_type' );
		$this->assertIsString( $type );
	}

	/**
	 * The same ID must resolve far enough to apply the format mapping,
	 * so the guard cannot be "return early on anything that is not a WP_Post".
	 */
	public function test_filter_object_type_still_maps_the_format() {
		$from_id   = $this->transformer->filter_object_type( 'Article', $this->post_id, '' );
		$from_post = $this->transformer->filter_object_type( 'Article', get_post( $this->post_id ), '' );

		$this->assertSame(
			$from_post,
			$from_id,
			'A post ID and its WP_Post must produce the same object type.'
		);
		$this->assertSame(
			$this->transformer->get_type_for_format( 'status' ),
			$from_id,
			'The status format mapping should still be applied when given an ID.'
		);
	}

	/**
	 * A WP_Post keeps working — the guard must not regress the normal path.
	 */
	public function test_filter_object_type_accepts_a_wp_post() {
		$this->capture_diagnostics(
			function () {
				return $this->transformer->filter_object_type( 'Note', get_post( $this->post_id ), '' );
			}
		);

		$this->assertNoPropertyWarnings( 'filter_object_type with WP_Post' );
	}

	/**
	 * filter_object_array is the callback #26 quoted at line 192.
	 */
	public function test_filter_object_array_accepts_a_post_id() {
		$object = $this->capture_diagnostics(
			function () {
				return $this->transformer->filter_object_array( array( 'type' => 'Note' ), $this->post_id, '' );
			}
		);

		$this->assertNoPropertyWarnings( 'filter_object_array' );
		$this->assertIsArray( $object );
	}

	/**
	 * filter_content is the third callback, and takes only two arguments.
	 */
	public function test_filter_content_accepts_a_post_id() {
		$content = $this->capture_diagnostics(
			function () {
				return $this->transformer->filter_content( 'body', $this->post_id );
			}
		);

		$this->assertNoPropertyWarnings( 'filter_content' );
		$this->assertIsString( $content );
	}

	/**
	 * Values that resolve to no post must be returned unchanged, not warned about.
	 *
	 * @dataProvider unresolvable_values
	 *
	 * @param mixed  $value Value standing in for the post argument.
	 * @param string $label Human label for the failure message.
	 */
	public function test_unresolvable_values_pass_through_quietly( $value, string $label ) {
		$results = $this->capture_diagnostics(
			function () use ( $value ) {
				return array(
					'type'    => $this->transformer->filter_object_type( 'Note', $value, '' ),
					'array'   => $this->transformer->filter_object_array( array( 'type' => 'Note' ), $value, '' ),
					'content' => $this->transformer->filter_content( 'body', $value ),
				);
			}
		);

		$this->assertNoPropertyWarnings( "callbacks given $label" );
		$this->assertSame( 'Note', $results['type'], "$label should leave the type untouched." );
		$this->assertSame( array( 'type' => 'Note' ), $results['array'], "$label should leave the object untouched." );
		$this->assertSame( 'body', $results['content'], "$label should leave the content untouched." );
	}

	/**
	 * Values the filters might realistically hand over.
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function unresolvable_values(): array {
		return array(
			'a non-numeric string' => array( 'not-a-post', 'a non-numeric string' ),
			'an empty string'      => array( '', 'an empty string' ),
			'null'                 => array( null, 'null' ),
			'a post ID that does not exist' => array( 999999, 'a missing post ID' ),
			'an unrelated object'  => array( new stdClass(), 'an unrelated object' ),
		);
	}

	/**
	 * The whole set of callbacks over one post, which is what #26 counted.
	 *
	 * Reported as six warnings for a single post creation before the guard,
	 * and zero after.
	 */
	public function test_a_full_pass_over_one_post_is_silent() {
		$this->capture_diagnostics(
			function () {
				foreach ( array( $this->post_id, (string) $this->post_id, get_post( $this->post_id ) ) as $post ) {
					$this->transformer->filter_object_type( 'Note', $post, '' );
					$this->transformer->filter_object_array( array( 'type' => 'Note' ), $post, '' );
					$this->transformer->filter_content( 'body', $post );
				}
			}
		);

		$this->assertNoPropertyWarnings( 'a full pass over one post' );
	}
}
