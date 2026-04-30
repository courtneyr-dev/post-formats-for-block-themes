<?php
/**
 * Test_Format_Classes — body + post class additions for non-standard formats.
 *
 * Verifies PFBT_Format_Classes adds the expected hooks for each format
 * scenario:
 *   - Standard format: no plugin classes (class_for_post returns empty)
 *   - Non-standard format: has-post-format + pfbt-format-{slug}
 *   - Title-less format (aside/status/quote): also pfbt-format-titleless
 *   - Non-post post types: no plugin classes
 *   - Filter callable can append additional classes
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 *
 * @covers \PFBT_Format_Classes
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase

class Test_Format_Classes extends WP_UnitTestCase {

	/**
	 * Cached singleton instance.
	 *
	 * @var PFBT_Format_Classes
	 */
	private $classes;

	/**
	 * Set up — ensure the class is loaded and the singleton initialized.
	 */
	public function set_up() {
		parent::set_up();
		$this->classes = PFBT_Format_Classes::instance();
	}

	/**
	 * Standard-format posts get no plugin-namespaced classes.
	 */
	public function test_standard_format_post_gets_no_plugin_classes() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		// Don't set a format — defaults to standard.

		$classes = apply_filters( 'post_class', array(), array(), $post_id );

		$this->assertNotContains( 'has-post-format', $classes );
		$this->assertNotContains( 'pfbt-format-standard', $classes );
		$this->assertNotContains( 'pfbt-format-titleless', $classes );
	}

	/**
	 * Aside-format posts get all three classes (aside is title-less).
	 */
	public function test_aside_format_post_gets_three_classes() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		set_post_format( $post_id, 'aside' );

		$classes = apply_filters( 'post_class', array(), array(), $post_id );

		$this->assertContains( 'has-post-format', $classes );
		$this->assertContains( 'pfbt-format-aside', $classes );
		$this->assertContains( 'pfbt-format-titleless', $classes );
	}

	/**
	 * Image-format posts get the namespaced class but NOT titleless.
	 */
	public function test_image_format_post_gets_no_titleless() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		set_post_format( $post_id, 'image' );

		$classes = apply_filters( 'post_class', array(), array(), $post_id );

		$this->assertContains( 'has-post-format', $classes );
		$this->assertContains( 'pfbt-format-image', $classes );
		$this->assertNotContains( 'pfbt-format-titleless', $classes );
	}

	/**
	 * Status and Quote — the other two title-less formats.
	 */
	public function test_status_and_quote_are_titleless() {
		foreach ( array( 'status', 'quote' ) as $format ) {
			$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
			set_post_format( $post_id, $format );

			$classes = apply_filters( 'post_class', array(), array(), $post_id );

			$this->assertContains(
				'pfbt-format-titleless',
				$classes,
				"Format '{$format}' should be titleless."
			);
		}
	}

	/**
	 * Non-post post types do NOT get plugin classes (post_format is
	 * registered only on the post post-type by WP core).
	 */
	public function test_non_post_post_types_get_no_classes() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );

		$classes = apply_filters( 'post_class', array(), array(), $page_id );

		$this->assertNotContains( 'has-post-format', $classes );
		foreach ( array( 'aside', 'status', 'quote', 'image', 'gallery', 'audio', 'video', 'link', 'chat' ) as $f ) {
			$this->assertNotContains( "pfbt-format-{$f}", $classes );
		}
	}

	/**
	 * The pfbt_format_card_classes filter can append theme-specific classes.
	 */
	public function test_filter_can_append_classes() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		set_post_format( $post_id, 'aside' );

		$callback = static function ( $defaults, $format, $pid ) {
			$defaults[] = 'theme-aside-treatment';
			$defaults[] = 'theme-format-' . $format;
			return $defaults;
		};
		add_filter( 'pfbt_format_card_classes', $callback, 10, 3 );

		$classes = apply_filters( 'post_class', array(), array(), $post_id );

		$this->assertContains( 'theme-aside-treatment', $classes );
		$this->assertContains( 'theme-format-aside', $classes );

		remove_filter( 'pfbt_format_card_classes', $callback, 10 );
	}

	/**
	 * The body_class filter on singular post views adds the same classes.
	 */
	public function test_body_class_on_singular_post() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		set_post_format( $post_id, 'quote' );

		$this->go_to( get_permalink( $post_id ) );

		$body_classes = get_body_class();

		$this->assertContains( 'has-post-format', $body_classes );
		$this->assertContains( 'pfbt-format-quote', $body_classes );
		$this->assertContains( 'pfbt-format-titleless', $body_classes );
	}

	/**
	 * Body class filter does NOT fire on archive views (post_class
	 * still does, since archives iterate posts).
	 */
	public function test_body_class_skips_archive_views() {
		$this->go_to( '/' );

		$body_classes = get_body_class();

		// Should not have post-format classes on the homepage / blog
		// archive itself; per-post body class is applied only on
		// is_singular('post').
		$this->assertNotContains( 'has-post-format', $body_classes );
	}
}
