<?php
/**
 * Format Analyzer for Post Formats
 *
 * Analyzes post content to detect signals that suggest appropriate post formats.
 * Used by MCP tools to provide AI-powered format suggestions.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format Analyzer
 *
 * Detects content signals (URLs, blockquotes, media, length) to suggest
 * the most appropriate post format for content.
 *
 * @since 1.2.0
 */
class PFBT_Format_Analyzer {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Format_Analyzer|null
	 */
	private static $instance = null;

	/**
	 * Signal weights for format detection
	 *
	 * @var array
	 */
	private $signal_weights = array();

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Format_Analyzer
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
		$this->init_signal_weights();
	}

	/**
	 * Initialize signal weights for format detection
	 *
	 * @since 1.2.0
	 */
	private function init_signal_weights() {
		$this->signal_weights = array(
			'status'   => array(
				'short_content'    => 50,
				'no_title'         => 30,
				'no_media'         => 10,
				'single_paragraph' => 20,
				'character_limit'  => 280,
			),
			'aside'    => array(
				'short_content'   => 40,
				'no_title'        => 20,
				'no_media'        => 10,
				'few_paragraphs'  => 15,
				'character_limit' => 500,
			),
			'quote'    => array(
				'has_blockquote'  => 60,
				'has_cite'        => 30,
				'quotation_marks' => 20,
				'short_content'   => 10,
			),
			'link'     => array(
				'dominant_url'     => 50,
				'external_link'    => 30,
				'short_commentary' => 20,
				'link_in_title'    => 25,
			),
			'image'    => array(
				'single_image'   => 60,
				'image_dominant' => 30,
				'minimal_text'   => 20,
				'has_figure'     => 15,
			),
			'gallery'  => array(
				'multiple_images' => 60,
				'gallery_block'   => 40,
				'image_grid'      => 30,
				'minimal_text'    => 10,
			),
			'video'    => array(
				'has_video'     => 60,
				'video_embed'   => 40,
				'youtube_vimeo' => 30,
				'minimal_text'  => 10,
			),
			'audio'    => array(
				'has_audio'    => 60,
				'audio_embed'  => 40,
				'podcast_link' => 30,
				'minimal_text' => 10,
			),
			'chat'     => array(
				'chat_pattern'      => 60,
				'dialogue_markers'  => 40,
				'speaker_labels'    => 30,
				'alternating_lines' => 20,
			),
			'standard' => array(
				'long_content'      => 20,
				'multiple_sections' => 15,
				'has_headings'      => 10,
				'mixed_media'       => 10,
			),
		);

		/**
		 * Filter signal weights for format detection.
		 *
		 * @since 1.2.0
		 *
		 * @param array $signal_weights The signal weight configurations.
		 */
		$this->signal_weights = apply_filters( 'pfbt_format_signal_weights', $this->signal_weights );
	}

	/**
	 * Analyze content and suggest format
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content to analyze.
	 * @param string $title   Optional post title.
	 * @return array Analysis results with suggested format and confidence.
	 */
	public function analyze( $content, $title = '' ) {
		$signals = $this->detect_signals( $content, $title );
		$scores  = $this->calculate_scores( $signals );

		// Sort by score descending.
		arsort( $scores );

		$suggested_format = array_key_first( $scores );

		// Status/aside are length-shape formats: they only apply when no
		// structural format is clearly signaled. A short post whose content
		// is an explicit quote, link, gallery, etc. takes that format even
		// though brevity also scores highly. 60 = the weight each structural
		// format assigns its defining signal.
		if ( in_array( $suggested_format, array( 'status', 'aside' ), true ) ) {
			foreach ( $scores as $format => $score ) {
				if ( in_array( $format, array( 'status', 'aside', 'standard' ), true ) ) {
					continue;
				}
				if ( $score >= 60 ) {
					$suggested_format = $format;
				}
				break;
			}
		}

		$max_score = $scores[ $suggested_format ];

		// Calculate confidence (0-100).
		$total_possible = $this->get_max_possible_score( $suggested_format );
		$confidence     = $total_possible > 0 ? min( 100, round( ( $max_score / $total_possible ) * 100 ) ) : 0;

		// Get alternative suggestions.
		$alternatives = array();
		$count        = 0;
		foreach ( $scores as $format => $score ) {
			if ( $format === $suggested_format ) {
				continue;
			}
			if ( $score > 0 && $count < 2 ) {
				$alt_total      = $this->get_max_possible_score( $format );
				$alt_confidence = $alt_total > 0 ? min( 100, round( ( $score / $alt_total ) * 100 ) ) : 0;
				$alternatives[] = array(
					'format'     => $format,
					'confidence' => $alt_confidence,
					'reason'     => $this->get_format_reason( $format, $signals ),
				);
				++$count;
			}
		}

		return array(
			'suggested_format' => $suggested_format,
			'confidence'       => $confidence,
			'reason'           => $this->get_format_reason( $suggested_format, $signals ),
			'alternatives'     => $alternatives,
			'signals'          => $signals,
			'scores'           => $scores,
		);
	}

	/**
	 * Detect content signals
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @param string $title   Post title.
	 * @return array Detected signals.
	 */
	private function detect_signals( $content, $title ) {
		// Turn block/paragraph boundaries into newlines before stripping so
		// line-anchored patterns (speaker labels, chat lines) still see them.
		$plain_content = wp_strip_all_tags( preg_replace( '/<\/(p|div|li|h[1-6])>|<br\s*\/?>/i', "\n", $content ) );
		$char_count    = mb_strlen( $plain_content );
		$word_count    = str_word_count( $plain_content );

		$signals = array(
			// Content length signals.
			'char_count'        => $char_count,
			'word_count'        => $word_count,
			'short_content'     => $char_count <= 280,
			'medium_content'    => $char_count > 280 && $char_count <= 500,
			'long_content'      => $char_count > 1000,

			// Structure signals.
			'no_title'          => empty( trim( $title ) ),
			'has_title'         => ! empty( trim( $title ) ),
			'single_paragraph'  => substr_count( $content, '</p>' ) <= 1,
			'few_paragraphs'    => substr_count( $content, '</p>' ) <= 3,
			'multiple_sections' => substr_count( $content, '</p>' ) > 5,
			'has_headings'      => (bool) preg_match( '/<h[1-6][^>]*>/', $content ),

			// Quote signals.
			'has_blockquote'    => stripos( $content, '<blockquote' ) !== false,
			'has_cite'          => stripos( $content, '<cite' ) !== false,
			'quotation_marks'   => (bool) preg_match( '/[""\'\'「」『』«»]/', $plain_content ),

			// Link signals.
			'has_links'         => (bool) preg_match( '/<a[^>]+href=/', $content ),
			'external_link'     => $this->has_external_link( $content ),
			'dominant_url'      => $this->is_url_dominant( $content ),
			'link_in_title'     => (bool) preg_match( '/https?:\/\//', $title ),

			// Image signals.
			'has_images'        => (bool) preg_match( '/<img[^>]+>/', $content ),
			'single_image'      => preg_match_all( '/<img[^>]+>/', $content ) === 1,
			'multiple_images'   => preg_match_all( '/<img[^>]+>/', $content ) > 1,
			'image_dominant'    => $this->is_media_dominant( $content, 'image' ),
			'has_figure'        => stripos( $content, '<figure' ) !== false,
			'gallery_block'     => stripos( $content, 'wp-block-gallery' ) !== false,

			// Video signals.
			'has_video'         => $this->has_video( $content ),
			'video_embed'       => $this->has_video_embed( $content ),
			'youtube_vimeo'     => $this->has_youtube_vimeo( $content ),

			// Audio signals.
			'has_audio'         => $this->has_audio( $content ),
			'audio_embed'       => $this->has_audio_embed( $content ),
			'podcast_link'      => $this->has_podcast_link( $content ),

			// Chat signals.
			'chat_pattern'      => $this->has_chat_pattern( $plain_content ),
			'dialogue_markers'  => $this->has_dialogue_markers( $plain_content ),
			'speaker_labels'    => $this->has_speaker_labels( $plain_content ),
			'alternating_lines' => $this->has_alternating_lines( $content ),

			// Media general.
			'no_media'          => ! $this->has_any_media( $content ),
			'minimal_text'      => $word_count < 50,
			'mixed_media'       => $this->has_mixed_media( $content ),
		);

		/**
		 * Filter detected content signals.
		 *
		 * @since 1.2.0
		 *
		 * @param array  $signals Detected signals.
		 * @param string $content The content being analyzed.
		 * @param string $title   The title being analyzed.
		 */
		return apply_filters( 'pfbt_format_signals', $signals, $content, $title );
	}

	/**
	 * Calculate format scores based on signals
	 *
	 * @since 1.2.0
	 *
	 * @param array $signals Detected signals.
	 * @return array Format scores.
	 */
	private function calculate_scores( $signals ) {
		$scores = array();

		foreach ( $this->signal_weights as $format => $weights ) {
			$score = 0;

			foreach ( $weights as $signal => $weight ) {
				// Skip non-boolean config values.
				if ( 'character_limit' === $signal ) {
					continue;
				}

				if ( isset( $signals[ $signal ] ) && $signals[ $signal ] ) {
					$score += $weight;
				}
			}

			// Special handling for status/aside character limits.
			if ( in_array( $format, array( 'status', 'aside' ), true ) ) {
				$limit = $weights['character_limit'] ?? 280;
				if ( $signals['char_count'] <= $limit ) {
					$score += 20;
				}
			}

			$scores[ $format ] = $score;
		}

		return $scores;
	}

	/**
	 * Get maximum possible score for a format
	 *
	 * @since 1.2.0
	 *
	 * @param string $format Format slug.
	 * @return int Maximum possible score.
	 */
	private function get_max_possible_score( $format ) {
		if ( ! isset( $this->signal_weights[ $format ] ) ) {
			return 100;
		}

		$total = 0;
		foreach ( $this->signal_weights[ $format ] as $signal => $weight ) {
			if ( 'character_limit' !== $signal ) {
				$total += $weight;
			}
		}

		// Add bonus for character limit formats.
		if ( in_array( $format, array( 'status', 'aside' ), true ) ) {
			$total += 20;
		}

		return $total;
	}

	/**
	 * Get human-readable reason for format suggestion
	 *
	 * @since 1.2.0
	 *
	 * @param string $format  Suggested format.
	 * @param array  $signals Detected signals.
	 * @return string Reason for suggestion.
	 */
	private function get_format_reason( $format, $signals ) {
		$reasons = array();

		switch ( $format ) {
			case 'status':
				if ( $signals['short_content'] ) {
					$reasons[] = __( 'short content under 280 characters', 'post-formats-for-block-themes' );
				}
				if ( $signals['no_title'] ) {
					$reasons[] = __( 'no title', 'post-formats-for-block-themes' );
				}
				if ( $signals['single_paragraph'] ) {
					$reasons[] = __( 'single paragraph', 'post-formats-for-block-themes' );
				}
				break;

			case 'aside':
				if ( $signals['medium_content'] || $signals['short_content'] ) {
					$reasons[] = __( 'brief content', 'post-formats-for-block-themes' );
				}
				if ( $signals['few_paragraphs'] ) {
					$reasons[] = __( 'few paragraphs', 'post-formats-for-block-themes' );
				}
				break;

			case 'quote':
				if ( $signals['has_blockquote'] ) {
					$reasons[] = __( 'contains blockquote', 'post-formats-for-block-themes' );
				}
				if ( $signals['has_cite'] ) {
					$reasons[] = __( 'has citation', 'post-formats-for-block-themes' );
				}
				break;

			case 'link':
				if ( $signals['dominant_url'] ) {
					$reasons[] = __( 'URL is primary content', 'post-formats-for-block-themes' );
				}
				if ( $signals['external_link'] ) {
					$reasons[] = __( 'links to external site', 'post-formats-for-block-themes' );
				}
				break;

			case 'image':
				if ( $signals['single_image'] ) {
					$reasons[] = __( 'single image', 'post-formats-for-block-themes' );
				}
				if ( $signals['image_dominant'] ) {
					$reasons[] = __( 'image is primary content', 'post-formats-for-block-themes' );
				}
				break;

			case 'gallery':
				if ( $signals['multiple_images'] ) {
					$reasons[] = __( 'multiple images', 'post-formats-for-block-themes' );
				}
				if ( $signals['gallery_block'] ) {
					$reasons[] = __( 'gallery block detected', 'post-formats-for-block-themes' );
				}
				break;

			case 'video':
				if ( $signals['has_video'] ) {
					$reasons[] = __( 'contains video', 'post-formats-for-block-themes' );
				}
				if ( $signals['youtube_vimeo'] ) {
					$reasons[] = __( 'YouTube/Vimeo embed', 'post-formats-for-block-themes' );
				}
				break;

			case 'audio':
				if ( $signals['has_audio'] ) {
					$reasons[] = __( 'contains audio', 'post-formats-for-block-themes' );
				}
				if ( $signals['podcast_link'] ) {
					$reasons[] = __( 'podcast content', 'post-formats-for-block-themes' );
				}
				break;

			case 'chat':
				if ( $signals['chat_pattern'] ) {
					$reasons[] = __( 'chat/dialogue pattern', 'post-formats-for-block-themes' );
				}
				if ( $signals['speaker_labels'] ) {
					$reasons[] = __( 'speaker labels detected', 'post-formats-for-block-themes' );
				}
				break;

			default:
				if ( $signals['long_content'] ) {
					$reasons[] = __( 'long-form content', 'post-formats-for-block-themes' );
				}
				if ( $signals['has_headings'] ) {
					$reasons[] = __( 'structured with headings', 'post-formats-for-block-themes' );
				}
				if ( $signals['mixed_media'] ) {
					$reasons[] = __( 'mixed media types', 'post-formats-for-block-themes' );
				}
				if ( empty( $reasons ) ) {
					$reasons[] = __( 'general article content', 'post-formats-for-block-themes' );
				}
				break;
		}

		return implode( ', ', $reasons );
	}

	/**
	 * Check if content has external links
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has external links.
	 */
	private function has_external_link( $content ) {
		if ( ! preg_match_all( '/<a[^>]+href=["\']([^"\']+)["\']/', $content, $matches ) ) {
			return false;
		}

		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		foreach ( $matches[1] as $url ) {
			$url_host = wp_parse_url( $url, PHP_URL_HOST );
			if ( $url_host && $url_host !== $site_host ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if URL is dominant content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether URL is dominant.
	 */
	private function is_url_dominant( $content ) {
		$plain      = wp_strip_all_tags( $content );
		$link_count = preg_match_all( '/<a[^>]+>/', $content );
		$word_count = str_word_count( $plain );

		// URL is dominant if there's a link and very little other text.
		return $link_count >= 1 && $word_count < 30;
	}

	/**
	 * Check if media is dominant in content
	 *
	 * @since 1.2.0
	 *
	 * @param string $content    Post content.
	 * @param string $media_type Media type to check.
	 * @return bool Whether media is dominant.
	 */
	private function is_media_dominant( $content, $media_type ) {
		$plain      = wp_strip_all_tags( $content );
		$word_count = str_word_count( $plain );

		$has_media = false;
		switch ( $media_type ) {
			case 'image':
				$has_media = (bool) preg_match( '/<img[^>]+>/', $content );
				break;
			case 'video':
				$has_media = $this->has_video( $content );
				break;
			case 'audio':
				$has_media = $this->has_audio( $content );
				break;
		}

		return $has_media && $word_count < 100;
	}

	/**
	 * Check if content has video
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has video.
	 */
	private function has_video( $content ) {
		return (bool) preg_match( '/<video[^>]*>|wp-block-video|wp-block-embed.*?youtube|wp-block-embed.*?vimeo/i', $content );
	}

	/**
	 * Check if content has video embed
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has video embed.
	 */
	private function has_video_embed( $content ) {
		return (bool) preg_match( '/wp-block-embed|<iframe[^>]+(?:youtube|vimeo|dailymotion)/i', $content );
	}

	/**
	 * Check if content has YouTube or Vimeo
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has YouTube/Vimeo.
	 */
	private function has_youtube_vimeo( $content ) {
		return (bool) preg_match( '/youtube\.com|youtu\.be|vimeo\.com/i', $content );
	}

	/**
	 * Check if content has audio
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has audio.
	 */
	private function has_audio( $content ) {
		return (bool) preg_match( '/<audio[^>]*>|wp-block-audio|wp-block-embed.*?soundcloud|wp-block-embed.*?spotify/i', $content );
	}

	/**
	 * Check if content has audio embed
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has audio embed.
	 */
	private function has_audio_embed( $content ) {
		return (bool) preg_match( '/wp-block-embed.*?(?:soundcloud|spotify|bandcamp)|<iframe[^>]+(?:soundcloud|spotify)/i', $content );
	}

	/**
	 * Check if content has podcast link
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has podcast link.
	 */
	private function has_podcast_link( $content ) {
		return (bool) preg_match( '/podcasts?\.apple\.com|spotify\.com\/episode|anchor\.fm|overcast\.fm/i', $content );
	}

	/**
	 * Check if content has chat pattern
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has chat pattern.
	 */
	private function has_chat_pattern( $content ) {
		// Look for common chat patterns like "Name: message" or "[timestamp] Name: message".
		return (bool) preg_match( '/^[\[\(]?\d{1,2}:\d{2}[\]\)]?\s*[A-Z][a-z]+:|^[A-Z][a-z]+\s*:/m', wp_strip_all_tags( $content ) );
	}

	/**
	 * Check if content has dialogue markers
	 *
	 * @since 1.2.0
	 *
	 * @param string $plain_content Plain text content.
	 * @return bool Whether content has dialogue markers.
	 */
	private function has_dialogue_markers( $plain_content ) {
		// Look for dialogue indicators.
		$patterns = array(
			'/^[-–—]\s/m',          // Dash at line start.
			'/:\s*$/m',              // Colon at line end.
			'/^>[^>]/m',             // Quote marker.
		);

		$matches = 0;
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $plain_content ) ) {
				++$matches;
			}
		}

		return $matches >= 2;
	}

	/**
	 * Check if content has speaker labels
	 *
	 * @since 1.2.0
	 *
	 * @param string $plain_content Plain text content.
	 * @return bool Whether content has speaker labels.
	 */
	private function has_speaker_labels( $plain_content ) {
		// Multiple lines starting with capitalized name followed by colon.
		$matches = preg_match_all( '/^[A-Z][a-z]+(?:\s+[A-Z][a-z]+)?:/m', $plain_content );
		return $matches >= 3;
	}

	/**
	 * Check if content has alternating lines (chat pattern)
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has alternating lines.
	 */
	private function has_alternating_lines( $content ) {
		$paragraphs = preg_match_all( '/<p[^>]*>/', $content );
		$brs        = substr_count( $content, '<br' );

		// Chat typically has many short lines.
		return ( $paragraphs >= 5 || $brs >= 5 );
	}

	/**
	 * Check if content has any media
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has any media.
	 */
	private function has_any_media( $content ) {
		return (bool) preg_match( '/<img|<video|<audio|<iframe|wp-block-embed|wp-block-gallery/i', $content );
	}

	/**
	 * Check if content has mixed media types
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @return bool Whether content has mixed media.
	 */
	private function has_mixed_media( $content ) {
		$types = 0;

		if ( preg_match( '/<img[^>]+>/', $content ) ) {
			++$types;
		}
		if ( $this->has_video( $content ) ) {
			++$types;
		}
		if ( $this->has_audio( $content ) ) {
			++$types;
		}

		return $types >= 2;
	}

	/**
	 * Validate content for a specific format
	 *
	 * @since 1.2.0
	 *
	 * @param string $content Post content.
	 * @param string $format  Format to validate against.
	 * @param string $title   Optional post title.
	 * @return array Validation result with valid status and messages.
	 */
	public function validate_for_format( $content, $format, $title = '' ) {
		$signals  = $this->detect_signals( $content, $title );
		$messages = array();
		$warnings = array();
		$valid    = true;

		switch ( $format ) {
			case 'status':
				if ( $signals['char_count'] > 280 ) {
					$warnings[] = sprintf(
						/* translators: %d: character count */
						__( 'Status posts are typically under 280 characters. Current: %d', 'post-formats-for-block-themes' ),
						$signals['char_count']
					);
				}
				if ( $signals['has_title'] ) {
					$warnings[] = __( 'Status posts typically have no title.', 'post-formats-for-block-themes' );
				}
				break;

			case 'quote':
				if ( ! $signals['has_blockquote'] && ! $signals['quotation_marks'] ) {
					$warnings[] = __( 'Quote posts should contain a blockquote or quoted text.', 'post-formats-for-block-themes' );
				}
				break;

			case 'link':
				if ( ! $signals['has_links'] ) {
					$valid      = false;
					$messages[] = __( 'Link posts must contain at least one link.', 'post-formats-for-block-themes' );
				}
				break;

			case 'image':
				if ( ! $signals['has_images'] ) {
					$valid      = false;
					$messages[] = __( 'Image posts must contain at least one image.', 'post-formats-for-block-themes' );
				}
				if ( $signals['multiple_images'] ) {
					$warnings[] = __( 'Multiple images detected. Consider using Gallery format.', 'post-formats-for-block-themes' );
				}
				break;

			case 'gallery':
				if ( ! $signals['multiple_images'] && ! $signals['gallery_block'] ) {
					$warnings[] = __( 'Gallery posts typically contain multiple images.', 'post-formats-for-block-themes' );
				}
				break;

			case 'video':
				if ( ! $signals['has_video'] ) {
					$valid      = false;
					$messages[] = __( 'Video posts must contain video content.', 'post-formats-for-block-themes' );
				}
				break;

			case 'audio':
				if ( ! $signals['has_audio'] ) {
					$valid      = false;
					$messages[] = __( 'Audio posts must contain audio content.', 'post-formats-for-block-themes' );
				}
				break;

			case 'chat':
				if ( ! $signals['chat_pattern'] && ! $signals['speaker_labels'] ) {
					$warnings[] = __( 'Chat posts should contain dialogue with speaker labels.', 'post-formats-for-block-themes' );
				}
				break;
		}

		return array(
			'valid'    => $valid,
			'format'   => $format,
			'messages' => $messages,
			'warnings' => $warnings,
			'signals'  => $signals,
		);
	}
}
