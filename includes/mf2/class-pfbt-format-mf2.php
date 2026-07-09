<?php
/**
 * Microformats2 Generator for Post Formats
 *
 * Generates microformats2 (mf2) markup for post formats, enabling
 * IndieWeb compatibility and machine-readable content.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 *
 * @see https://microformats.org/wiki/microformats2
 * @see https://indieweb.org/microformats
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Microformats2 Generator
 *
 * Provides format-specific microformats2 classes and markup for IndieWeb compatibility.
 * Each post format maps to specific mf2 vocabulary for optimal syndication and parsing.
 *
 * @since 1.2.0
 */
class PFBT_Format_Mf2 {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Format_Mf2|null
	 */
	private static $instance = null;

	/**
	 * Format to mf2 mapping
	 *
	 * Maps WordPress post formats to their primary mf2 classes.
	 *
	 * @var array<string, array>
	 */
	private $format_mf2_map = array();

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Format_Mf2
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
		$this->init_format_map();
		$this->init_hooks();
	}

	/**
	 * Initialize format to mf2 mapping
	 *
	 * @since 1.2.0
	 */
	private function init_format_map() {
		$this->format_mf2_map = array(
			'standard' => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'description'   => __( 'Standard blog post entry.', 'post-formats-for-block-themes' ),
			),
			'aside'    => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'p-note e-content',
				'name_class'    => '',
				'description'   => __( 'Short note without a title, similar to a tweet.', 'post-formats-for-block-themes' ),
			),
			'status'   => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'p-note e-content',
				'name_class'    => '',
				'description'   => __( 'Short status update, like a tweet or toot.', 'post-formats-for-block-themes' ),
			),
			'quote'    => array(
				'entry_class'   => 'h-entry h-cite',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'quote_class'   => 'p-content',
				'author_class'  => 'p-author h-card',
				'source_class'  => 'u-url',
				'description'   => __( 'Quotation with citation, using h-cite.', 'post-formats-for-block-themes' ),
			),
			'link'     => array(
				'entry_class'    => 'h-entry',
				'content_class'  => 'e-content',
				'name_class'     => 'p-name',
				'bookmark_class' => 'u-bookmark-of h-cite',
				'description'    => __( 'Link post with bookmark reference.', 'post-formats-for-block-themes' ),
			),
			'image'    => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'photo_class'   => 'u-photo',
				'description'   => __( 'Single image post with u-photo.', 'post-formats-for-block-themes' ),
			),
			'gallery'  => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'photo_class'   => 'u-photo',
				'description'   => __( 'Multiple images, each marked as u-photo.', 'post-formats-for-block-themes' ),
			),
			'video'    => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'video_class'   => 'u-video',
				'description'   => __( 'Video post with u-video.', 'post-formats-for-block-themes' ),
			),
			'audio'    => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'audio_class'   => 'u-audio',
				'description'   => __( 'Audio post with u-audio.', 'post-formats-for-block-themes' ),
			),
			'chat'     => array(
				'entry_class'   => 'h-entry',
				'content_class' => 'e-content',
				'name_class'    => 'p-name',
				'description'   => __( 'Conversation transcript.', 'post-formats-for-block-themes' ),
			),
		);

		/**
		 * Filter the format to mf2 mapping.
		 *
		 * @since 1.2.0
		 *
		 * @param array $format_mf2_map The format to mf2 mapping array.
		 */
		$this->format_mf2_map = apply_filters( 'pfbt_format_mf2_map', $this->format_mf2_map );
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.2.0
	 */
	private function init_hooks() {
		// Only add hooks if IndieWeb integration is enabled.
		if ( ! PFBT_Feature_Flags::has_indieweb() ) {
			return;
		}

		// Add mf2 classes to post wrapper.
		add_filter( 'post_class', array( $this, 'add_mf2_post_classes' ), 10, 3 );

		// Add mf2 to content wrapper.
		add_filter( 'the_content', array( $this, 'wrap_content_mf2' ), 5 );

		// Add author h-card.
		add_filter( 'the_author', array( $this, 'wrap_author_hcard' ) );
	}

	/**
	 * Get mf2 classes for a post format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return array Mf2 class configuration.
	 */
	public function get_format_mf2( $format ) {
		if ( empty( $format ) || false === $format ) {
			$format = 'standard';
		}

		return $this->format_mf2_map[ $format ] ?? $this->format_mf2_map['standard'];
	}

	/**
	 * Get entry class for a format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return string Entry class(es).
	 */
	public function get_entry_class( $format ) {
		$mf2 = $this->get_format_mf2( $format );
		return $mf2['entry_class'] ?? 'h-entry';
	}

	/**
	 * Get content class for a format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Post format slug.
	 * @return string Content class(es).
	 */
	public function get_content_class( $format ) {
		$mf2 = $this->get_format_mf2( $format );
		return $mf2['content_class'] ?? 'e-content';
	}

	/**
	 * Add mf2 classes to post_class filter
	 *
	 * @since 1.2.0
	 *
	 * @param array $classes          Existing classes.
	 * @param array $additional_class Additional classes passed to post_class().
	 * @param int   $post_id          Post ID.
	 * @return array Modified classes.
	 */
	public function add_mf2_post_classes( $classes, $additional_class, $post_id ) {
		if ( ! is_singular( 'post' ) ) {
			return $classes;
		}

		$format      = get_post_format( $post_id );
		$entry_class = $this->get_entry_class( $format );

		// Add each mf2 class individually.
		$mf2_classes = explode( ' ', $entry_class );
		foreach ( $mf2_classes as $mf2_class ) {
			if ( ! in_array( $mf2_class, $classes, true ) ) {
				$classes[] = $mf2_class;
			}
		}

		return $classes;
	}

	/**
	 * Wrap content with mf2 e-content class
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function wrap_content_mf2( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$format        = get_post_format();
		$content_class = $this->get_content_class( $format );

		// Wrap content in div with mf2 classes.
		$content = sprintf(
			'<div class="%s">%s</div>',
			esc_attr( $content_class ),
			$content
		);

		return $content;
	}

	/**
	 * Wrap author name with h-card
	 *
	 * @since 1.2.0
	 *
	 * @param string $author Author display name.
	 * @return string Modified author with h-card.
	 */
	public function wrap_author_hcard( $author ) {
		if ( ! is_singular( 'post' ) ) {
			return $author;
		}

		$author_url = get_author_posts_url( get_the_author_meta( 'ID' ) );

		return sprintf(
			'<a class="p-author h-card" href="%s">%s</a>',
			esc_url( $author_url ),
			esc_html( $author )
		);
	}

	/**
	 * Generate full mf2 markup for a post
	 *
	 * @since 1.2.0
	 *
	 * @param int|WP_Post $post Post ID or object.
	 * @return array Mf2 markup data.
	 */
	public function generate_mf2_markup( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return array();
		}

		$format = get_post_format( $post->ID );
		if ( false === $format ) {
			$format = 'standard';
		}

		$mf2_config = $this->get_format_mf2( $format );

		$markup = array(
			'format'        => $format,
			'entry_class'   => $mf2_config['entry_class'],
			'content_class' => $mf2_config['content_class'],
			'properties'    => array(
				'name'      => array( get_the_title( $post ) ),
				'content'   => array(
					array(
						'html'  => apply_filters( 'the_content', $post->post_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying the core content filter to render HTML, not declaring a new hook.
						'value' => wp_strip_all_tags( $post->post_content ),
					),
				),
				'published' => array( get_the_date( 'c', $post ) ),
				'updated'   => array( get_the_modified_date( 'c', $post ) ),
				'url'       => array( get_permalink( $post ) ),
				'author'    => array(
					array(
						'type'       => array( 'h-card' ),
						'properties' => array(
							'name' => array( get_the_author_meta( 'display_name', $post->post_author ) ),
							'url'  => array( get_author_posts_url( $post->post_author ) ),
						),
					),
				),
			),
		);

		// Add format-specific properties.
		$markup = $this->add_format_specific_properties( $markup, $post, $format );

		/**
		 * Filter the generated mf2 markup.
		 *
		 * @since 1.2.0
		 *
		 * @param array   $markup The mf2 markup array.
		 * @param WP_Post $post   The post object.
		 * @param string  $format The post format.
		 */
		return apply_filters( 'pfbt_mf2_markup', $markup, $post, $format );
	}

	/**
	 * Add format-specific mf2 properties
	 *
	 * @since 1.2.0
	 *
	 * @param array   $markup Existing markup.
	 * @param WP_Post $post   Post object.
	 * @param string  $format Post format.
	 * @return array Modified markup.
	 */
	private function add_format_specific_properties( $markup, $post, $format ) {
		switch ( $format ) {
			case 'image':
			case 'gallery':
				// Extract images from content.
				$photos = $this->extract_photos_from_content( $post->post_content );
				if ( ! empty( $photos ) ) {
					$markup['properties']['photo'] = $photos;
				}
				break;

			case 'video':
				// Extract video URLs.
				$videos = $this->extract_videos_from_content( $post->post_content );
				if ( ! empty( $videos ) ) {
					$markup['properties']['video'] = $videos;
				}
				break;

			case 'audio':
				// Extract audio URLs.
				$audios = $this->extract_audios_from_content( $post->post_content );
				if ( ! empty( $audios ) ) {
					$markup['properties']['audio'] = $audios;
				}
				break;

			case 'quote':
				// Extract quotation.
				$quote = $this->extract_quote_from_content( $post->post_content );
				if ( ! empty( $quote ) ) {
					$markup['properties']['quotation-of'] = array( $quote );
				}
				break;

			case 'link':
				// Extract bookmark URL.
				$bookmark = $this->extract_bookmark_from_content( $post->post_content );
				if ( ! empty( $bookmark ) ) {
					$markup['properties']['bookmark-of'] = array( $bookmark );
				}
				break;

			case 'aside':
			case 'status':
				// These are notes - remove name if empty.
				if ( empty( trim( get_the_title( $post ) ) ) ) {
					unset( $markup['properties']['name'] );
				}
				break;
		}

		return $markup;
	}

	/**
	 * Extract photo URLs from content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return array Photo URLs.
	 */
	private function extract_photos_from_content( $content ) {
		$photos = array();

		// Match img src attributes.
		if ( preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches ) ) {
			$photos = $matches[1];
		}

		return array_unique( $photos );
	}

	/**
	 * Extract video URLs from content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return array Video URLs.
	 */
	private function extract_videos_from_content( $content ) {
		$videos = array();

		// Match video src attributes.
		if ( preg_match_all( '/<video[^>]+src=["\']([^"\']+)["\']/', $content, $matches ) ) {
			$videos = array_merge( $videos, $matches[1] );
		}

		// Match source elements within video tags.
		if ( preg_match_all( '/<source[^>]+src=["\']([^"\']+)["\'][^>]+type=["\']video/', $content, $matches ) ) {
			$videos = array_merge( $videos, $matches[1] );
		}

		return array_unique( $videos );
	}

	/**
	 * Extract audio URLs from content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return array Audio URLs.
	 */
	private function extract_audios_from_content( $content ) {
		$audios = array();

		// Match audio src attributes.
		if ( preg_match_all( '/<audio[^>]+src=["\']([^"\']+)["\']/', $content, $matches ) ) {
			$audios = array_merge( $audios, $matches[1] );
		}

		// Match source elements within audio tags.
		if ( preg_match_all( '/<source[^>]+src=["\']([^"\']+)["\'][^>]+type=["\']audio/', $content, $matches ) ) {
			$audios = array_merge( $audios, $matches[1] );
		}

		return array_unique( $audios );
	}

	/**
	 * Extract quote citation from content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return array|null Quote citation data.
	 */
	private function extract_quote_from_content( $content ) {
		// Look for cite attribute in blockquote.
		if ( preg_match( '/<blockquote[^>]+cite=["\']([^"\']+)["\']/', $content, $matches ) ) {
			return array(
				'type'       => array( 'h-cite' ),
				'properties' => array(
					'url' => array( $matches[1] ),
				),
			);
		}

		return null;
	}

	/**
	 * Extract bookmark URL from content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return array|null Bookmark data.
	 */
	private function extract_bookmark_from_content( $content ) {
		// Look for first link in content.
		if ( preg_match( '/<a[^>]+href=["\']([^"\']+)["\']/', $content, $matches ) ) {
			$url = $matches[1];

			// Skip internal links.
			$site_url = home_url();
			if ( strpos( $url, $site_url ) === 0 ) {
				return null;
			}

			return array(
				'type'       => array( 'h-cite' ),
				'properties' => array(
					'url' => array( $url ),
				),
			);
		}

		return null;
	}

	/**
	 * Validate mf2 output for a post
	 *
	 * @since 1.2.0
	 *
	 * @param int|WP_Post $post Post ID or object.
	 * @return array Validation result.
	 */
	public function validate_mf2( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return array(
				'valid'  => false,
				'errors' => array( __( 'Invalid post.', 'post-formats-for-block-themes' ) ),
			);
		}

		$markup = $this->generate_mf2_markup( $post );
		$errors = array();

		// Check required properties.
		if ( empty( $markup['properties']['url'] ) ) {
			$errors[] = __( 'Missing required u-url property.', 'post-formats-for-block-themes' );
		}

		if ( empty( $markup['properties']['published'] ) ) {
			$errors[] = __( 'Missing required dt-published property.', 'post-formats-for-block-themes' );
		}

		if ( empty( $markup['properties']['author'] ) ) {
			$errors[] = __( 'Missing required p-author property.', 'post-formats-for-block-themes' );
		}

		// Format-specific validation.
		$format = get_post_format( $post->ID );

		if ( in_array( $format, array( 'image', 'gallery' ), true ) ) {
			if ( empty( $markup['properties']['photo'] ) ) {
				$errors[] = __( 'Image/Gallery format should have u-photo property.', 'post-formats-for-block-themes' );
			}
		}

		if ( 'video' === $format && empty( $markup['properties']['video'] ) ) {
			$errors[] = __( 'Video format should have u-video property.', 'post-formats-for-block-themes' );
		}

		if ( 'audio' === $format && empty( $markup['properties']['audio'] ) ) {
			$errors[] = __( 'Audio format should have u-audio property.', 'post-formats-for-block-themes' );
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
			'markup' => $markup,
		);
	}

	/**
	 * Get all format mf2 mappings
	 *
	 * @since 1.2.0
	 *
	 * @return array All format mf2 mappings.
	 */
	public function get_all_format_mf2() {
		return $this->format_mf2_map;
	}
}
