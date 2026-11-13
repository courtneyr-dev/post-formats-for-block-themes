<?php
/**
 * PHPUnit bootstrap for the Post Kinds integration suite.
 *
 * @package PostFormatsBlockThemes
 */

$GLOBALS['pfbt_pkiw_doing_it_wrong'] = array();

$pkiw_plugin_dir  = getenv( 'PKIW_PLUGIN_DIR' );
$pkiw_plugin_file = $pkiw_plugin_dir
	? rtrim( $pkiw_plugin_dir, '/\\' ) . '/post-kinds-for-indieweb-in-block-themes.php'
	: '';

if ( ! $pkiw_plugin_dir || ! file_exists( $pkiw_plugin_file ) ) {
	echo "PKIW_PLUGIN_DIR must point to a Post Kinds checkout containing post-kinds-for-indieweb-in-block-themes.php\n";
	exit( 1 );
}

$GLOBALS['pfbt_pkiw_plugin_file'] = $pkiw_plugin_file;

// Composer autoloader must be loaded before WordPress test suite.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php\n";
	echo "Please run: bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Record WordPress incorrect-usage notices raised while both plugins load.
 *
 * @param string $function_name Function that raised the notice.
 * @param string $message       Notice message.
 */
function pfbt_pkiw_record_doing_it_wrong( $function_name, $message ) {
	$GLOBALS['pfbt_pkiw_doing_it_wrong'][] = array( $function_name, $message );
}

tests_add_filter( 'doing_it_wrong_run', 'pfbt_pkiw_record_doing_it_wrong', 10, 2 );

/**
 * Load PFBT before PKIW, matching WordPress's alphabetical plugin order.
 */
function pfbt_pkiw_load_plugins() {
	require dirname( __DIR__ ) . '/post-formats-for-block-themes.php';
	require $GLOBALS['pfbt_pkiw_plugin_file'];
}

tests_add_filter( 'muplugins_loaded', 'pfbt_pkiw_load_plugins' );

// Start up the WordPress testing environment.
require $_tests_dir . '/includes/bootstrap.php';
