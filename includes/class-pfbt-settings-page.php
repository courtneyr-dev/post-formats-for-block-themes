<?php
/**
 * Post Formats for Block Themes — Admin settings page.
 *
 * Adds Settings → Post Formats. Currently houses one option (icon set
 * picker, v2.3.0). Future settings can extend the same page.
 *
 * Accessibility decisions:
 * - Radio inputs grouped inside a <fieldset> with a <legend> so screen
 *   readers announce the group purpose before each option.
 * - Each option label contains BOTH a visible icon preview AND the
 *   set's human name so users with low vision and users with screen
 *   readers both get a complete description.
 * - Icon preview SVGs are aria-hidden; the accessible name comes from
 *   the label text. Pure decoration shouldn't double-announce.
 * - Form actions go through the WP Settings API (nonce + capability
 *   handled by options.php).
 * - Keyboard-only navigation tested: tab focuses each radio, space
 *   selects, enter submits the form.
 *
 * @package PostFormatsBlockThemes
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page for Post Formats for Block Themes.
 */
class PFBT_Settings_Page {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'pfbt-settings';

	/**
	 * Capability required to view + change settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Hook everything up.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Register the Settings → Post Formats menu entry.
	 */
	public static function add_menu_page() {
		add_options_page(
			__( 'Post Formats', 'post-formats-for-block-themes' ),
			__( 'Post Formats', 'post-formats-for-block-themes' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Hook the Settings API entries.
	 */
	public static function register_settings() {
		PFBT_Icon_Set::register_setting();

		add_settings_section(
			'pfbt_icon_section',
			__( 'Format icons', 'post-formats-for-block-themes' ),
			array( __CLASS__, 'render_icon_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			PFBT_Icon_Set::OPTION_KEY,
			__( 'Icon set', 'post-formats-for-block-themes' ),
			array( __CLASS__, 'render_icon_set_picker' ),
			self::PAGE_SLUG,
			'pfbt_icon_section',
			array( 'label_for' => PFBT_Icon_Set::OPTION_KEY . '_hand-drawn' )
		);
	}

	/**
	 * Section intro paragraph — explains what the Format Icon block uses.
	 */
	public static function render_icon_section_intro() {
		echo '<p>' . esc_html__( 'Pick the icon style used by the Format Icon block. Switching sets is instant; no posts need to be re-saved. Themes that filter icons directly via pfbt_format_badge_icon will continue to override your choice — this picker controls what the plugin renders by default.', 'post-formats-for-block-themes' ) . '</p>';
	}

	/**
	 * Render the icon-set picker — fieldset + radios with previews.
	 */
	public static function render_icon_set_picker() {
		$active = PFBT_Icon_Set::get_active_set_slug();
		$sets   = PFBT_Icon_Set::get_available_sets();
		?>
		<fieldset class="pfbt-icon-set-picker">
			<legend class="screen-reader-text"><?php esc_html_e( 'Select the icon set', 'post-formats-for-block-themes' ); ?></legend>

			<?php foreach ( $sets as $slug => $label ) : ?>
				<?php
				$input_id    = PFBT_Icon_Set::OPTION_KEY . '_' . $slug;
				$sprite_url  = PFBT_Icon_Set::get_sprite_url( $slug );
				$is_selected = ( $slug === $active );
				?>
				<label for="<?php echo esc_attr( $input_id ); ?>" class="pfbt-icon-set-option<?php echo $is_selected ? ' is-selected' : ''; ?>">
					<input
						type="radio"
						id="<?php echo esc_attr( $input_id ); ?>"
						name="<?php echo esc_attr( PFBT_Icon_Set::OPTION_KEY ); ?>"
						value="<?php echo esc_attr( $slug ); ?>"
						<?php checked( $slug, $active ); ?>
					/>
					<span class="pfbt-icon-set-option__label"><?php echo esc_html( $label ); ?></span>
					<span class="pfbt-icon-set-option__preview" aria-hidden="true">
						<?php
						$preview_formats = array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' );
						foreach ( $preview_formats as $format ) :
							?>
							<svg viewBox="0 0 24 24" width="28" height="28" focusable="false" aria-hidden="true">
								<use href="<?php echo esc_url( $sprite_url ); ?>#pfbt-format-icon-<?php echo esc_attr( $format ); ?>"></use>
							</svg>
						<?php endforeach; ?>
					</span>
				</label>
			<?php endforeach; ?>
		</fieldset>

		<style>
			.pfbt-icon-set-picker { display: flex; flex-direction: column; gap: 0.75rem; max-width: 720px; }
			.pfbt-icon-set-option { display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem; border: 2px solid #c3c4c7; border-radius: 6px; cursor: pointer; background: #fff; }
			.pfbt-icon-set-option.is-selected { border-color: #2271b1; background: #f6f7f7; }
			.pfbt-icon-set-option:focus-within { outline: 3px solid #2271b1; outline-offset: 2px; }
			.pfbt-icon-set-option__label { font-weight: 600; min-width: 12rem; }
			.pfbt-icon-set-option__preview { display: flex; flex-wrap: wrap; gap: 0.4rem; color: #1d2327; flex: 1; }
			.pfbt-icon-set-option__preview svg { flex-shrink: 0; }
			.pfbt-icon-set-option input[type="radio"] { flex-shrink: 0; }
			@media (prefers-reduced-motion: no-preference) {
				.pfbt-icon-set-option { transition: border-color 0.15s ease, background 0.15s ease; }
			}
			@media (prefers-contrast: more) {
				.pfbt-icon-set-option { border-width: 3px; }
				.pfbt-icon-set-option.is-selected { border-color: #000; background: #fff; }
			}
		</style>
		<?php
	}

	/**
	 * Render the page wrapper — calls the WP Settings API to print sections + fields.
	 */
	public static function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'post-formats-for-block-themes' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'pfbt_settings' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save changes', 'post-formats-for-block-themes' ) );
				?>
			</form>
		</div>
		<?php
	}
}
