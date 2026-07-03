<?php
/**
 * Format Classes — body + post class additions for richer theming hooks.
 *
 * The plugin contributes three classes themes can target without ambiguity:
 *
 *   .has-post-format          (post + body) — set whenever get_post_format()
 *                              returns a truthy non-standard slug. Lets themes
 *                              write `body.has-post-format ...` rules without
 *                              having to enumerate the nine format slugs.
 *
 *   .pfbt-format-{slug}       (post + body) — plugin-namespaced parallel to
 *                              WP core's `.format-{slug}`. Lets theme authors
 *                              write rules they know aren't going to collide
 *                              with another plugin's interpretation of the
 *                              core class.
 *
 *   .pfbt-format-titleless    (post + body) — set on aside, status, and
 *                              quote (formats whose post-title is hidden by
 *                              default). Themes can use this as a single hook
 *                              rather than enumerating the three slugs every
 *                              time they want to target the title-less group.
 *
 * The pre-2.0 body_class filter in class-format-styles.php emitted only
 * `.has-post-format` and `.format-{slug}` (the latter is also added by WP
 * core, so it was redundant). This class is the 2.0 replacement; it covers
 * both body and post classes, adds the namespaced + titleless hooks, and
 * runs on the post_class filter as well so templates that use post_class()
 * inside Query Loops get the same hooks.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PFBT_Format_Classes
 *
 * Singleton; registered on plugin bootstrap. Filters body_class and
 * post_class to add the three plugin-namespaced classes.
 *
 * @since 2.0.0
 */
class PFBT_Format_Classes {

	/**
	 * Singleton instance.
	 *
	 * @since 2.0.0
	 * @var PFBT_Format_Classes|null
	 */
	private static $instance = null;

	/**
	 * Slugs whose post-title is hidden by default.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const TITLELESS_FORMATS = array( 'aside', 'status', 'quote' );

	/**
	 * Get singleton instance and register filters.
	 *
	 * @since 2.0.0
	 * @return PFBT_Format_Classes
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wires the filters.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
		add_filter( 'post_class', array( $this, 'filter_post_class' ), 10, 3 );
	}

	/**
	 * Add format classes to body_class on singular post views.
	 *
	 * Mirrors filter_post_class but scoped to the queried post on singular
	 * views. Skips non-singular and non-post contexts so archives and pages
	 * don't get post-format body classes that wouldn't make sense.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $classes Existing body classes.
	 * @return string[] Filtered classes.
	 */
	public function filter_body_class( $classes ) {
		if ( ! is_singular( 'post' ) ) {
			return $classes;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return $classes;
		}

		return array_merge( $classes, $this->classes_for_post( $post_id ) );
	}

	/**
	 * Add format classes to post_class for any post in any context.
	 *
	 * Runs in archive Query Loops and singular views alike. The class
	 * additions are gated by `get_post_format( $post_id )` so standard-
	 * format posts get only `.format-standard` (added by WP core) — no
	 * plugin-namespaced classes for posts without a format.
	 *
	 * @since 2.0.0
	 *
	 * @param string[]    $classes          Existing post classes.
	 * @param string[]    $additional_class Classes passed to post_class().
	 * @param int|WP_Post $post_id          Post ID or object.
	 * @return string[] Filtered classes.
	 */
	public function filter_post_class( $classes, $additional_class, $post_id ) {
		$post_id = is_object( $post_id ) ? $post_id->ID : (int) $post_id;
		if ( ! $post_id ) {
			return $classes;
		}

		// Only add format classes to posts. Pages, attachments, custom
		// post types do not get post-format classes — WP core itself
		// only registers post-format support on the `post` post type.
		if ( 'post' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		return array_merge( $classes, $this->classes_for_post( $post_id ) );
	}

	/**
	 * Compute the plugin's classes for a given post.
	 *
	 * Centralizes the logic so body and post filters return the same
	 * additions for a given post.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return string[] Classes to add (may be empty).
	 */
	private function classes_for_post( $post_id ) {
		$format = get_post_format( $post_id );
		if ( ! $format || 'standard' === $format ) {
			return array();
		}

		$classes = array(
			'has-post-format',
			'pfbt-format-' . sanitize_html_class( $format ),
		);

		if ( in_array( $format, self::TITLELESS_FORMATS, true ) ) {
			$classes[] = 'pfbt-format-titleless';
		}

		/**
		 * Filter the classes the plugin contributes for a given post.
		 *
		 * Lets themes add their own format-aware classes alongside the
		 * plugin's defaults without re-implementing the post_class /
		 * body_class filters from scratch. Note that classes added here
		 * appear in BOTH body and post class lists for the post.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $classes Default classes to add.
		 * @param string   $format  Post format slug.
		 * @param int      $post_id Post ID.
		 */
		$classes = apply_filters( 'pfbt_format_card_classes', $classes, $format, $post_id );

		// Defensively normalize: filter authors might return non-arrays
		// or non-strings; coerce to clean array of strings.
		if ( ! is_array( $classes ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'sanitize_html_class', array_map( 'strval', $classes ) )
			)
		);
	}
}
