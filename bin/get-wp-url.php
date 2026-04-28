<?php
/**
 * Get WordPress site URL from wp_options table.
 *
 * @package Post_Formats_For_Block_Themes
 */

// Load WordPress.
$wp_path = getenv( 'WP_PATH' ) ? getenv( 'WP_PATH' ) : '/Users/crobertson/Local Sites/post-formats-test/app/public';
require_once $wp_path . '/wp-load.php';

// Get site URL.
echo esc_url( get_option( 'siteurl' ) );
