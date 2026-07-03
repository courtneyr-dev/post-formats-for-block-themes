<?php
/**
 * PFBT_Block_Templates — opt-in single + archive templates per format.
 *
 * 2.0 Session 6 work. Registers 18 templates (9 singles + 9 archives)
 * via WP 6.7+'s register_block_template() API and filters the template
 * hierarchy so WP routes single posts and post-format taxonomy archives
 * to the format-specific variants when available.
 *
 * Behavior is gated by the `pfbt_use_block_templates` option (default
 * false) and the matching filter (default option value). Themes that
 * ship their own format-specific templates leave the option off; the
 * plugin's templates never register and never enter the hierarchy.
 *
 * Templates load their content from /templates/v2/{name}.html files in
 * the plugin so theme authors can read them as starter examples and
 * copy/adapt into their own theme/templates/.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block template registry for post-format-specific templates.
 *
 * @since 2.0.0
 */
class PFBT_Block_Templates {

	/**
	 * Singleton instance.
	 *
	 * @since 2.0.0
	 * @var PFBT_Block_Templates|null
	 */
	private static $instance = null;

	/**
	 * Option key controlling whether the plugin's templates register.
	 *
	 * @since 2.0.0
	 */
	const OPTION_KEY = 'pfbt_use_block_templates';

	/**
	 * Plugin namespace used in template ids.
	 *
	 * Ids must be `{theme-or-plugin}//{slug}` per WP 6.7's
	 * register_block_template() docs. Using the plugin slug keeps the
	 * templates clearly attributable in the editor.
	 *
	 * @since 2.0.0
	 */
	const TEMPLATE_NAMESPACE = 'post-formats-for-block-themes';

	/**
	 * The 9 non-standard formats that get their own templates.
	 *
	 * Standard format intentionally does NOT get a single-post-standard
	 * template — standard posts use the theme's own single template,
	 * which is already optimal for their use case. Adding a plugin
	 * template for standard would override the theme's template
	 * unnecessarily.
	 *
	 * @since 2.0.0
	 */
	const FORMATS = array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' );

	/**
	 * Get singleton instance.
	 *
	 * @since 2.0.0
	 * @return PFBT_Block_Templates
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — wires the registration + hierarchy filters.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_templates' ) );
		add_filter( 'template_hierarchy', array( $this, 'filter_template_hierarchy' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ), 11 );
	}

	/**
	 * Register the boolean option with WP Settings API.
	 *
	 * @since 2.0.0
	 */
	public function register_setting() {
		register_setting(
			'pfbt_block_templates_group',
			self::OPTION_KEY,
			array(
				'type'              => 'boolean',
				'description'       => __( 'Use the plugin\'s opt-in single + archive templates per post format.', 'post-formats-for-block-themes' ),
				'sanitize_callback' => static function ( $value ) {
					return (bool) $value;
				},
				'default'           => false,
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Register the Tools subpage hosting the toggle.
	 *
	 * @since 2.0.0
	 */
	public function register_admin_page() {
		add_management_page(
			__( 'Post Format Templates', 'post-formats-for-block-themes' ),
			__( 'Post Format Templates', 'post-formats-for-block-themes' ),
			'manage_options',
			'pfbt-block-templates',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the Tools subpage.
	 *
	 * Single-purpose: toggle the option on or off. Includes a list of
	 * the 18 templates the option controls so admins can see exactly
	 * what they're enabling.
	 *
	 * @since 2.0.0
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'post-formats-for-block-themes' ) );
		}
		$enabled = (bool) get_option( self::OPTION_KEY, false );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Post Format Templates', 'post-formats-for-block-themes' ); ?></h1>

			<p>
				<?php
				esc_html_e(
					'Most block themes ship their own templates for post formats. Enable this option only if your active theme does NOT provide format-specific templates and you want the plugin\'s starter templates to render single posts and post-format archives.',
					'post-formats-for-block-themes'
				);
				?>
			</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'pfbt_block_templates_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Plugin block templates', 'post-formats-for-block-themes' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>" value="1" <?php checked( $enabled ); ?> />
								<?php esc_html_e( 'Use the plugin\'s opt-in single + archive templates per post format.', 'post-formats-for-block-themes' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When enabled, single posts and post-format taxonomy archives use the plugin\'s starter templates. The plugin filters the WordPress template hierarchy to inject format-specific slugs (single-post-{format} and archive-post-format-{format}) ahead of the generic ones.', 'post-formats-for-block-themes' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Templates this option controls', 'post-formats-for-block-themes' ); ?></h2>
			<p><?php esc_html_e( 'Eighteen templates total — nine single-post variants and nine archive variants:', 'post-formats-for-block-themes' ); ?></p>
			<ul>
				<?php foreach ( self::FORMATS as $format ) : ?>
					<li>
						<code>single-post-<?php echo esc_html( $format ); ?></code>
						&nbsp;|&nbsp;
						<code>archive-post-format-<?php echo esc_html( $format ); ?></code>
					</li>
				<?php endforeach; ?>
			</ul>

			<p>
				<?php
				printf(
					/* translators: %s: link to documentation */
					esc_html__( 'See %s for the full template content and integration notes.', 'post-formats-for-block-themes' ),
					'<code>/templates/v2/*.html</code>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Whether the option + filter say templates should register.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = (bool) get_option( self::OPTION_KEY, false );

		/**
		 * Whether the plugin's opt-in block templates should register.
		 *
		 * Themes that ship their own format-specific templates can
		 * force-disable via:
		 *
		 *     add_filter( 'pfbt_use_block_templates', '__return_false' );
		 *
		 * Themes that want the plugin's templates regardless of the
		 * site's option setting can force-enable:
		 *
		 *     add_filter( 'pfbt_use_block_templates', '__return_true' );
		 *
		 * @since 2.0.0
		 *
		 * @param bool $enabled Default reads pfbt_use_block_templates option.
		 */
		return (bool) apply_filters( 'pfbt_use_block_templates', $enabled );
	}

	/**
	 * Register all 18 templates if enabled.
	 *
	 * @since 2.0.0
	 */
	public function register_templates() {
		if ( ! function_exists( 'register_block_template' ) ) {
			// WP < 6.7 — no API, silent skip.
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		foreach ( self::FORMATS as $format ) {
			$this->register_one(
				"single-post-{$format}",
				sprintf(
				/* translators: %s: format name */
					__( 'Single Post — %s', 'post-formats-for-block-themes' ),
					ucfirst( $format )
				),
				sprintf(
				/* translators: %s: format name */
					__( 'Single-post template for the %s post format.', 'post-formats-for-block-themes' ),
					ucfirst( $format )
				)
			);

			$this->register_one(
				"archive-post-format-{$format}",
				sprintf(
				/* translators: %s: format name */
					__( 'Archive — %s', 'post-formats-for-block-themes' ),
					ucfirst( $format )
				),
				sprintf(
				/* translators: %s: format name */
					__( 'Archive template for the %s post format taxonomy.', 'post-formats-for-block-themes' ),
					ucfirst( $format )
				)
			);
		}
	}

	/**
	 * Register a single template by name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $name        Template name (e.g. 'single-post-aside').
	 * @param string $title       Editor display title.
	 * @param string $description Editor description.
	 */
	private function register_one( $name, $title, $description ) {
		$file = PFBT_PLUGIN_DIR . "templates/v2/{$name}.html";
		if ( ! file_exists( $file ) ) {
			return;
		}

		$content = file_get_contents( $file );
		if ( false === $content || '' === trim( $content ) ) {
			return;
		}

		// Strip leading HTML comment block (it's documentation for theme
		// authors reading the file, not template content). Anything
		// before the first <!-- wp: marker is dropped.
		$first_block = strpos( $content, '<!-- wp:' );
		if ( false !== $first_block ) {
			$content = substr( $content, $first_block );
		}

		register_block_template(
			self::TEMPLATE_NAMESPACE . '//' . $name,
			array(
				'title'       => $title,
				'description' => $description,
				'content'     => $content,
				// post_types empty so the editor's "Templates" panel
				// surfaces these for editing in any context.
				'post_types'  => array( 'post' ),
			)
		);
	}

	/**
	 * Inject format-specific slugs into the template hierarchy.
	 *
	 * WP's default hierarchy doesn't include `single-post-{format}` or
	 * `archive-post-format-{format}`. This filter prepends those slugs
	 * before the generic ones so when the plugin's templates are
	 * registered, the renderer picks them up automatically for posts
	 * that have a non-standard format.
	 *
	 * Returns the original templates array unchanged when the option is
	 * off — no hierarchy disruption when the feature is disabled.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $templates Default WP template hierarchy slugs.
	 * @return string[] Filtered hierarchy.
	 */
	public function filter_template_hierarchy( $templates ) {
		if ( ! $this->is_enabled() ) {
			return $templates;
		}

		// Single posts: inject single-post-{format} before single-post.
		if ( is_singular( 'post' ) ) {
			$format = get_post_format( get_queried_object_id() );
			if ( $format && in_array( $format, self::FORMATS, true ) ) {
				$injected = "single-post-{$format}";
				if ( ! in_array( $injected, $templates, true ) ) {
					array_unshift( $templates, $injected );
				}
			}
		}

		// Post-format taxonomy archives: inject archive-post-format-{format}
		// before taxonomy-post_format.
		if ( is_tax( 'post_format' ) ) {
			$term = get_queried_object();
			if ( $term && isset( $term->slug ) && 0 === strpos( $term->slug, 'post-format-' ) ) {
				$format = substr( $term->slug, strlen( 'post-format-' ) );
				if ( in_array( $format, self::FORMATS, true ) ) {
					$injected = "archive-post-format-{$format}";
					if ( ! in_array( $injected, $templates, true ) ) {
						array_unshift( $templates, $injected );
					}
				}
			}
		}

		return $templates;
	}
}
