<?php
/**
 * ActivityPub Transformer for Post Formats
 *
 * Integrates with the ActivityPub plugin to map post formats to
 * appropriate ActivityPub object types (Note vs Article).
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 *
 * @see https://www.w3.org/TR/activitystreams-vocabulary/#object-types
 * @see https://github.com/Automattic/wordpress-activitypub
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ActivityPub Transformer
 *
 * Maps WordPress post formats to ActivityPub object types for proper
 * federation to Mastodon, Pleroma, and other ActivityPub services.
 *
 * Object Type Mapping:
 * - Note: Short-form content (status, aside) - displays inline in Mastodon
 * - Article: Long-form content (standard) - displays as link with preview
 * - Image/Video/Audio: Media types with attachments
 *
 * @since 1.2.0
 */
class PFBT_ActivityPub_Transformer {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_ActivityPub_Transformer|null
	 */
	private static $instance = null;

	/**
	 * Format to ActivityPub type mapping
	 *
	 * @var array<string, string>
	 */
	private $format_type_map = array();

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_ActivityPub_Transformer
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->init_type_map();
		$this->init_hooks();
	}

	/**
	 * Initialize format to ActivityPub type mapping
	 *
	 * @since 1.2.0
	 */
	private function init_type_map() {
		/**
		 * Map post formats to ActivityPub object types.
		 *
		 * Note: Used for short-form, title-less content (shows inline in Mastodon)
		 * Article: Used for long-form content with titles (shows as link preview)
		 * Image/Video/Audio: Media types (Note with attachments)
		 *
		 * @see https://www.w3.org/TR/activitystreams-vocabulary/#object-types
		 */
		$this->format_type_map = array(
			'standard' => 'Article',  // Traditional blog post with title.
			'aside'    => 'Note',     // Short note without title - like a toot.
			'status'   => 'Note',     // Status update - exactly like a toot.
			'quote'    => 'Note',     // Quote with citation - Note with content.
			'link'     => 'Note',     // Link share - Note with link attachment.
			'image'    => 'Note',     // Image post - Note with image attachment.
			'gallery'  => 'Note',     // Gallery - Note with multiple images.
			'video'    => 'Note',     // Video post - Note with video attachment.
			'audio'    => 'Note',     // Audio/podcast - Note with audio attachment.
			'chat'     => 'Note',     // Chat log - Note (could be Article if long).
		);

		/**
		 * Filter the format to ActivityPub type mapping.
		 *
		 * @since 1.2.0
		 *
		 * @param array $format_type_map The format to type mapping array.
		 */
		$this->format_type_map = apply_filters( 'pfbt_activitypub_type_map', $this->format_type_map );
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.2.0
	 */
	private function init_hooks() {
		// Only add hooks if ActivityPub plugin is active.
		if ( ! $this->is_activitypub_active() ) {
			return;
		}

		// Filter the ActivityPub object type.
		add_filter( 'activitypub_activity_object_type', array( $this, 'filter_object_type' ), 10, 3 );

		// Filter the ActivityPub object array.
		add_filter( 'activitypub_activity_object_array', array( $this, 'filter_object_array' ), 10, 3 );

		// Filter content for Note types (strip title for cleaner federation).
		add_filter( 'activitypub_the_content', array( $this, 'filter_content' ), 10, 2 );

		// Add format-specific summary for Article types.
		add_filter( 'activitypub_activity_object_array', array( $this, 'add_article_summary' ), 15, 3 );
	}

	/**
	 * Check if ActivityPub plugin is active
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if ActivityPub is active.
	 */
	public function is_activitypub_active() {
		return defined( 'ACTIVITYPUB_PLUGIN_VERSION' ) || class_exists( 'Activitypub\Activitypub' );
	}

	/**
	 * Get ActivityPub type for a post format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return string ActivityPub object type.
	 */
	public function get_type_for_format( $format ) {
		if ( empty( $format ) || false === $format ) {
			$format = 'standard';
		}

		return $this->format_type_map[ $format ] ?? 'Article';
	}

	/**
	 * Filter ActivityPub object type based on post format
	 *
	 * @since 1.2.0
	 *
	 * @param string  $type    Current object type.
	 * @param WP_Post $post    Post object.
	 * @param string  $context Context (e.g., 'create', 'update').
	 * @return string Modified object type.
	 */
	public function filter_object_type( $type, $post, $context = '' ) {
		if ( 'post' !== $post->post_type ) {
			return $type;
		}

		$format = get_post_format( $post->ID );
		return $this->get_type_for_format( $format );
	}

	/**
	 * Filter ActivityPub object array based on post format
	 *
	 * @since 1.2.0
	 *
	 * @param array   $object  ActivityPub object array.
	 * @param WP_Post $post    Post object.
	 * @param string  $context Context.
	 * @return array Modified object array.
	 */
	public function filter_object_array( $object, $post, $context = '' ) {
		if ( 'post' !== $post->post_type ) {
			return $object;
		}

		$format = get_post_format( $post->ID );
		$type   = $this->get_type_for_format( $format );

		// Update the type.
		$object['type'] = $type;

		// For Note types, we may want to adjust the name/content structure.
		if ( 'Note' === $type ) {
			// Notes typically don't have a name (title) in ActivityPub.
			// The content IS the post, not a preview.
			if ( in_array( $format, array( 'aside', 'status' ), true ) ) {
				// Remove name for truly title-less formats.
				unset( $object['name'] );
			}
		}

		// Add format as a tag for discoverability.
		if ( ! isset( $object['tag'] ) ) {
			$object['tag'] = array();
		}

		$object['tag'][] = array(
			'type' => 'Hashtag',
			'name' => '#' . $format,
			'href' => home_url( '/format/' . $format . '/' ),
		);

		return $object;
	}

	/**
	 * Filter content for Note types
	 *
	 * For Note types (aside, status), we want the full content to appear
	 * inline in Mastodon timelines, not just a link.
	 *
	 * @since 1.2.0
	 *
	 * @param string  $content Post content.
	 * @param WP_Post $post    Post object.
	 * @return string Modified content.
	 */
	public function filter_content( $content, $post ) {
		if ( 'post' !== $post->post_type ) {
			return $content;
		}

		$format = get_post_format( $post->ID );
		$type   = $this->get_type_for_format( $format );

		if ( 'Note' === $type ) {
			// For status format, ensure we're respecting the 280 char convention.
			if ( 'status' === $format ) {
				// Content should already be short, but we'll respect it.
				$content = wp_strip_all_tags( $content );
				// Mastodon supports 500 chars, but we're mimicking Twitter's 280.
			}

			// For aside, quote, link - include the full formatted content.
			// The ActivityPub plugin handles HTML to plain text conversion.
		}

		return $content;
	}

	/**
	 * Add summary for Article types
	 *
	 * Articles in ActivityPub should have a summary (excerpt) for the preview.
	 *
	 * @since 1.2.0
	 *
	 * @param array   $object  ActivityPub object array.
	 * @param WP_Post $post    Post object.
	 * @param string  $context Context.
	 * @return array Modified object array.
	 */
	public function add_article_summary( $object, $post, $context = '' ) {
		if ( 'Article' !== ( $object['type'] ?? '' ) ) {
			return $object;
		}

		// Add excerpt as summary if not already set.
		if ( empty( $object['summary'] ) ) {
			$excerpt = get_the_excerpt( $post );
			if ( ! empty( $excerpt ) ) {
				$object['summary'] = wp_strip_all_tags( $excerpt );
			}
		}

		return $object;
	}

	/**
	 * Get recommended character limit for a format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return int|null Character limit or null for no limit.
	 */
	public function get_char_limit_for_format( $format ) {
		$limits = array(
			'status' => 280,  // Twitter-style status.
			'aside'  => 500,  // Mastodon's limit.
		);

		/**
		 * Filter character limits for formats.
		 *
		 * @since 1.2.0
		 *
		 * @param array $limits Format to character limit mapping.
		 */
		$limits = apply_filters( 'pfbt_format_char_limits', $limits );

		return $limits[ $format ] ?? null;
	}

	/**
	 * Check if a format should federate as Note
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return bool True if format should be a Note.
	 */
	public function is_note_format( $format ) {
		return 'Note' === $this->get_type_for_format( $format );
	}

	/**
	 * Get all format type mappings
	 *
	 * @since 1.2.0
	 *
	 * @return array All format to type mappings.
	 */
	public function get_all_type_mappings() {
		return $this->format_type_map;
	}
}
