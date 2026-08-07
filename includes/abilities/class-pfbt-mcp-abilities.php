<?php
/**
 * MCP Abilities for Post Formats
 *
 * Registers MCP-related abilities for AI format suggestions
 * using the WordPress Abilities API.
 *
 * @package PostFormatsBlockThemes
 * @since 1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MCP Abilities Provider
 *
 * Provides abilities for AI-powered format suggestions and content analysis.
 * Integrates with MCP (Model Context Protocol) for external AI tool access.
 *
 * @since 1.2.0
 */
class PFBT_MCP_Abilities {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_MCP_Abilities|null
	 */
	private static $instance = null;

	/**
	 * Format analyzer instance
	 *
	 * @var PFBT_Format_Analyzer|null
	 */
	private $analyzer = null;

	/**
	 * Ability namespace
	 *
	 * @var string
	 */
	const NAMESPACE = 'post-formats';

	/**
	 * Get singleton instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_MCP_Abilities
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
		$this->load_dependencies();
	}

	/**
	 * Load required dependencies
	 *
	 * @since 1.2.0
	 */
	private function load_dependencies() {
		if ( ! class_exists( 'PFBT_Format_Analyzer' ) ) {
			require_once PFBT_PLUGIN_DIR . 'includes/mcp/class-pfbt-format-analyzer.php';
		}
		$this->analyzer = PFBT_Format_Analyzer::instance();
	}

	/**
	 * Register all MCP abilities
	 *
	 * @since 1.2.0
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$this->register_suggest_format();
		$this->register_analyze_content();
		$this->register_validate_format_content();
		$this->register_get_format_signals();

		/**
		 * Fires after MCP abilities are registered.
		 *
		 * @since 1.2.0
		 *
		 * @param PFBT_MCP_Abilities $provider The abilities provider instance.
		 */
		do_action( 'pfbt_mcp_abilities_registered', $this );
	}

	/**
	 * Register suggest_format ability
	 *
	 * Analyzes content and suggests the most appropriate post format.
	 *
	 * @since 1.2.0
	 */
	private function register_suggest_format() {
		wp_register_ability(
			self::NAMESPACE . '/suggest-format',
			array(
				'label'               => __( 'Suggest Format', 'post-formats-for-block-themes' ),
				'description'         => __( 'Analyze content and suggest the most appropriate post format based on content signals.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The post content to analyze (HTML allowed).', 'post-formats-for-block-themes' ),
						),
						'title'   => array(
							'type'        => 'string',
							'description' => __( 'Optional post title for additional context.', 'post-formats-for-block-themes' ),
							'default'     => '',
						),
					),
					'required'   => array( 'content' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'suggested_format' => array(
							'type'        => 'string',
							'description' => __( 'The recommended post format.', 'post-formats-for-block-themes' ),
						),
						'confidence'       => array(
							'type'        => 'integer',
							'description' => __( 'Confidence level 0-100.', 'post-formats-for-block-themes' ),
						),
						'reason'           => array(
							'type'        => 'string',
							'description' => __( 'Human-readable reason for suggestion.', 'post-formats-for-block-themes' ),
						),
						'alternatives'     => array(
							'type'        => 'array',
							'description' => __( 'Alternative format suggestions.', 'post-formats-for-block-themes' ),
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'format'     => array( 'type' => 'string' ),
									'confidence' => array( 'type' => 'integer' ),
									'reason'     => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_suggest_format' ),
				'permission_callback' => array( $this, 'can_read_posts' ),
			)
		);
	}

	/**
	 * Register analyze_content ability
	 *
	 * Provides detailed content analysis with all detected signals.
	 *
	 * @since 1.2.0
	 */
	private function register_analyze_content() {
		wp_register_ability(
			self::NAMESPACE . '/analyze-content',
			array(
				'label'               => __( 'Analyze Content', 'post-formats-for-block-themes' ),
				'description'         => __( 'Perform detailed content analysis and return all detected signals with format scores.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The post content to analyze.', 'post-formats-for-block-themes' ),
						),
						'title'   => array(
							'type'        => 'string',
							'description' => __( 'Optional post title.', 'post-formats-for-block-themes' ),
							'default'     => '',
						),
					),
					'required'   => array( 'content' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'suggested_format' => array(
							'type'        => 'string',
							'description' => __( 'The recommended format.', 'post-formats-for-block-themes' ),
						),
						'confidence'       => array(
							'type'        => 'integer',
							'description' => __( 'Confidence level 0-100.', 'post-formats-for-block-themes' ),
						),
						'signals'          => array(
							'type'        => 'object',
							'description' => __( 'All detected content signals.', 'post-formats-for-block-themes' ),
						),
						'scores'           => array(
							'type'        => 'object',
							'description' => __( 'Score for each format.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_analyze_content' ),
				'permission_callback' => array( $this, 'can_read_posts' ),
			)
		);
	}

	/**
	 * Register validate_format_content ability
	 *
	 * Validates if content is appropriate for a specific format.
	 *
	 * @since 1.2.0
	 */
	private function register_validate_format_content() {
		wp_register_ability(
			self::NAMESPACE . '/validate-format-content',
			array(
				'label'               => __( 'Validate Format Content', 'post-formats-for-block-themes' ),
				'description'         => __( 'Validate if content is appropriate for a specific post format.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'content' => array(
							'type'        => 'string',
							'description' => __( 'The post content to validate.', 'post-formats-for-block-themes' ),
						),
						'format'  => array(
							'type'        => 'string',
							'description' => __( 'The format to validate against.', 'post-formats-for-block-themes' ),
							'enum'        => array( 'standard', 'aside', 'chat', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio' ),
						),
						'title'   => array(
							'type'        => 'string',
							'description' => __( 'Optional post title.', 'post-formats-for-block-themes' ),
							'default'     => '',
						),
					),
					'required'   => array( 'content', 'format' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'valid'    => array(
							'type'        => 'boolean',
							'description' => __( 'Whether content is valid for format.', 'post-formats-for-block-themes' ),
						),
						'format'   => array(
							'type'        => 'string',
							'description' => __( 'The format validated against.', 'post-formats-for-block-themes' ),
						),
						'messages' => array(
							'type'        => 'array',
							'description' => __( 'Validation error messages.', 'post-formats-for-block-themes' ),
							'items'       => array( 'type' => 'string' ),
						),
						'warnings' => array(
							'type'        => 'array',
							'description' => __( 'Validation warnings.', 'post-formats-for-block-themes' ),
							'items'       => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_validate_format_content' ),
				'permission_callback' => array( $this, 'can_read_posts' ),
			)
		);
	}

	/**
	 * Register get_format_signals ability
	 *
	 * Returns the signal weights configuration for format detection.
	 *
	 * @since 1.2.0
	 */
	private function register_get_format_signals() {
		wp_register_ability(
			self::NAMESPACE . '/get-format-signals',
			array(
				'label'               => __( 'Get Format Signals', 'post-formats-for-block-themes' ),
				'description'         => __( 'Get the signal weights used for format detection. Useful for understanding how formats are suggested.', 'post-formats-for-block-themes' ),
				'category'            => PFBT_Abilities_Manager::CATEGORY_SLUG,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'format' => array(
							'type'        => 'string',
							'description' => __( 'Optional specific format to get signals for.', 'post-formats-for-block-themes' ),
							'enum'        => array( 'standard', 'aside', 'chat', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'signals' => array(
							'type'        => 'object',
							'description' => __( 'Signal weights for format detection.', 'post-formats-for-block-themes' ),
						),
					),
				),
				'execute_callback'    => array( $this, 'execute_get_format_signals' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Execute suggest_format ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $input Input parameters.
	 * @return array Suggestion result.
	 */
	public function execute_suggest_format( $input ) {
		$content = $input['content'] ?? '';
		$title   = $input['title'] ?? '';

		if ( empty( $content ) ) {
			return array(
				'suggested_format' => 'standard',
				'confidence'       => 0,
				'reason'           => __( 'No content provided.', 'post-formats-for-block-themes' ),
				'alternatives'     => array(),
			);
		}

		$result = $this->analyzer->analyze( $content, $title );

		return array(
			'suggested_format' => $result['suggested_format'],
			'confidence'       => $result['confidence'],
			'reason'           => $result['reason'],
			'alternatives'     => $result['alternatives'],
		);
	}

	/**
	 * Execute analyze_content ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $input Input parameters.
	 * @return array Analysis result.
	 */
	public function execute_analyze_content( $input ) {
		$content = $input['content'] ?? '';
		$title   = $input['title'] ?? '';

		if ( empty( $content ) ) {
			return array(
				'suggested_format' => 'standard',
				'confidence'       => 0,
				'signals'          => array(),
				'scores'           => array(),
			);
		}

		return $this->analyzer->analyze( $content, $title );
	}

	/**
	 * Execute validate_format_content ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $input Input parameters.
	 * @return array Validation result.
	 */
	public function execute_validate_format_content( $input ) {
		$content = $input['content'] ?? '';
		$format  = $input['format'] ?? 'standard';
		$title   = $input['title'] ?? '';

		$result = $this->analyzer->validate_for_format( $content, $format, $title );

		return array(
			'valid'    => $result['valid'],
			'format'   => $result['format'],
			'messages' => $result['messages'],
			'warnings' => $result['warnings'],
		);
	}

	/**
	 * Execute get_format_signals ability
	 *
	 * @since 1.2.0
	 *
	 * @param array $input Input parameters.
	 * @return array Signal weights.
	 */
	public function execute_get_format_signals( $input ) {
		$format = $input['format'] ?? null;

		/**
		 * Filter to get signal weights.
		 *
		 * Uses the same filter as the analyzer for consistency.
		 */
		$weights = apply_filters(
			'pfbt_format_signal_weights',
			array(
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
			)
		);

		if ( $format && isset( $weights[ $format ] ) ) {
			return array(
				'signals' => array( $format => $weights[ $format ] ),
			);
		}

		return array(
			'signals' => $weights,
		);
	}

	/**
	 * Permission callback for read access
	 *
	 * @since 1.2.0
	 *
	 * @return bool Whether user can read posts.
	 */
	public function can_read_posts() {
		return current_user_can( 'read' );
	}

	/**
	 * Get the format analyzer instance
	 *
	 * @since 1.2.0
	 *
	 * @return PFBT_Format_Analyzer The analyzer instance.
	 */
	public function get_analyzer() {
		return $this->analyzer;
	}
}
