<?php
/**
 * PHPStan bootstrap — declares runtime constants from the main plugin file
 * so per-file analysis can resolve them.
 *
 * @package post-formats-for-block-themes
 */

define( 'PFBT_VERSION', '2.3.0' );
define( 'PFBT_PLUGIN_DIR', __DIR__ . '/../../' );
define( 'PFBT_PLUGIN_URL', 'https://example.test/wp-content/plugins/post-formats-for-block-themes/' );
define( 'PFBT_PLUGIN_BASENAME', 'post-formats-for-block-themes/post-formats-for-block-themes.php' );
