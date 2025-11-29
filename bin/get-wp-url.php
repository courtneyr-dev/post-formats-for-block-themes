<?php
/**
 * Get WordPress site URL from wp_options table
 */

// Load WordPress
$wp_path = getenv('WP_PATH') ?: '/Users/crobertson/Local Sites/post-formats-test/app/public';
require_once $wp_path . '/wp-load.php';

// Get site URL
echo get_option('siteurl');
