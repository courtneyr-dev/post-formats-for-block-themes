<?php
/**
 * Format Icon block — registration.
 *
 * Registers the post-formats/format-icon block with WordPress on the
 * `init` hook. The block's render template lives in format-icon.php
 * and is wired via block.json's `"render": "file:./format-icon.php"`
 * directive — the render template is loaded by WordPress per render
 * and is NOT required at plugin bootstrap.
 *
 * Companion to the auto-hooked Format Badge block (blocks/format-badge/).
 * Format Badge auto-injects before core/post-title via blockHooks; Format
 * Icon is for manual placement inside patterns and templates where the
 * theme wants the icon in a known slot rather than always-before-title.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function () {
		register_block_type( __DIR__ );
	}
);
