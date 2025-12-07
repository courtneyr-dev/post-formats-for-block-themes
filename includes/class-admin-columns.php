<?php
/**
 * Admin Columns - Post Format columns for Posts list
 *
 * Adds a post format column to the Posts admin list table,
 * similar to how categories and tags are displayed.
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PFBT_Admin_Columns
 *
 * Handles the display of post format columns in the WordPress admin
 * Posts list table, with Screen Options toggle support.
 *
 * @since 1.0.0
 */
class PFBT_Admin_Columns {

	/**
	 * Singleton instance
	 *
	 * @var PFBT_Admin_Columns|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @since 1.0.0
	 * @return PFBT_Admin_Columns
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
	 * @since 1.0.0
	 */
	private function __construct() {
		// Add column to posts list
		add_filter( 'manage_posts_columns', array( $this, 'add_format_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'render_format_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'make_format_column_sortable' ) );

		// Handle sorting
		add_action( 'pre_get_posts', array( $this, 'handle_format_column_sorting' ) );

		// Screen Options support
		add_filter( 'manage_edit-post_columns', array( $this, 'handle_screen_options' ) );
		add_filter( 'default_hidden_columns', array( $this, 'set_default_hidden_columns' ), 10, 2 );

		// Add inline styles for the column
		add_action( 'admin_head-edit.php', array( $this, 'add_column_styles' ) );
	}

	/**
	 * Add post format column to posts list
	 *
	 * Inserts the format column after the title column,
	 * similar to categories and tags placement.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_format_column( $columns ) {
		// Insert after title column
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['format'] = __( 'Format', 'post-formats-for-block-themes' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render post format column content
	 *
	 * Displays the post format with a link to filter posts by that format.
	 * Shows "Standard" for posts without a specific format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column_name Column identifier.
	 * @param int    $post_id     Post ID.
	 */
	public function render_format_column( $column_name, $post_id ) {
		if ( 'format' !== $column_name ) {
			return;
		}

		$format = get_post_format( $post_id );

		// If no format, it's Standard
		if ( ! $format ) {
			$format = 'standard';
		}

		// Get format display name
		$format_strings = PFBT_Format_Registry::get_all_formats();
		$format_name = isset( $format_strings[ $format ] ) ? $format_strings[ $format ]['name'] : ucfirst( $format );

		// Get format icon
		$icon = $this->get_format_icon( $format );

		// Build filter URL
		if ( 'standard' === $format ) {
			// For standard format, we need to show posts WITHOUT any format taxonomy term
			$filter_url = add_query_arg(
				array(
					'post_type'   => 'post',
					'post_format' => 'standard',
				),
				admin_url( 'edit.php' )
			);
		} else {
			// For other formats, use the taxonomy term
			$term = get_term_by( 'slug', 'post-format-' . $format, 'post_format' );
			if ( $term ) {
				$filter_url = add_query_arg(
					array(
						'post_format' => $term->slug,
					),
					admin_url( 'edit.php' )
				);
			} else {
				// Fallback if term doesn't exist
				$filter_url = admin_url( 'edit.php?post_type=post' );
			}
		}

		// Output format with icon and link
		printf(
			'<a href="%s" class="post-format-link" title="%s">%s<span class="post-format-name">%s</span></a>',
			esc_url( $filter_url ),
			/* translators: %s: Format name */
			esc_attr( sprintf( __( 'Filter by %s format', 'post-formats-for-block-themes' ), $format_name ) ),
			wp_kses( $icon, array( 'span' => array( 'class' => array(), 'aria-hidden' => array() ) ) ),
			esc_html( $format_name )
		);
	}

	/**
	 * Get icon HTML for a format
	 *
	 * Returns a dashicon appropriate for each post format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format Post format slug.
	 * @return string Icon HTML.
	 */
	private function get_format_icon( $format ) {
		$icons = array(
			'standard' => 'dashicons-admin-post',
			'aside'    => 'dashicons-aside',
			'gallery'  => 'dashicons-format-gallery',
			'link'     => 'dashicons-admin-links',
			'image'    => 'dashicons-format-image',
			'quote'    => 'dashicons-format-quote',
			'status'   => 'dashicons-format-status',
			'video'    => 'dashicons-format-video',
			'audio'    => 'dashicons-format-audio',
			'chat'     => 'dashicons-format-chat',
		);

		$icon = isset( $icons[ $format ] ) ? $icons[ $format ] : 'dashicons-admin-post';

		return sprintf( '<span class="dashicons %s" aria-hidden="true"></span>', esc_attr( $icon ) );
	}

	/**
	 * Make format column sortable
	 *
	 * Allows users to sort posts by format in the admin list.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function make_format_column_sortable( $columns ) {
		$columns['format'] = 'post_format';
		return $columns;
	}

	/**
	 * Handle format column sorting
	 *
	 * Modifies the query to sort by post format taxonomy when requested.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query object.
	 */
	public function handle_format_column_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'post_format' === $orderby ) {
			$query->set(
				'orderby',
				array(
					'taxonomy' => $query->get( 'order' ) ?: 'ASC',
				)
			);
			$query->set( 'taxonomy', 'post_format' );
		}
	}

	/**
	 * Handle Screen Options visibility
	 *
	 * The format column is managed through WordPress's built-in
	 * Screen Options panel. Users can toggle it on/off like other columns.
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns Columns to display.
	 * @return array Filtered columns.
	 */
	public function handle_screen_options( $columns ) {
		// WordPress automatically handles Screen Options visibility
		// This filter is called after checking user preferences
		return $columns;
	}

	/**
	 * Set default hidden columns
	 *
	 * Makes the format column visible by default when users first
	 * visit the Posts page.
	 *
	 * @since 1.0.0
	 *
	 * @param array     $hidden Hidden columns.
	 * @param WP_Screen $screen Current screen.
	 * @return array Modified hidden columns.
	 */
	public function set_default_hidden_columns( $hidden, $screen ) {
		// Only apply to edit-post screen
		if ( 'edit-post' === $screen->id ) {
			// Remove 'format' from hidden columns to make it visible by default
			// Users can still hide it via Screen Options
			$hidden = array_diff( $hidden, array( 'format' ) );
		}
		return $hidden;
	}

	/**
	 * Add inline styles for format column
	 *
	 * Styles the format column to match WordPress's category/tag columns.
	 *
	 * @since 1.0.0
	 */
	public function add_column_styles() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-post' !== $screen->id ) {
			return;
		}
		?>
		<style>
			.column-format {
				width: 10%;
			}
			.post-format-link {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				text-decoration: none;
			}
			.post-format-link:hover {
				text-decoration: underline;
			}
			.post-format-link .dashicons {
				font-size: 16px;
				width: 16px;
				height: 16px;
			}
			.post-format-name {
				vertical-align: middle;
			}
			/* Match category/tag column styling */
			.column-format a {
				color: #2271b1;
			}
			.column-format a:hover {
				color: #135e96;
			}
		</style>
		<?php
	}
}
