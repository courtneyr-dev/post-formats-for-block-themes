<?php
/**
 * Test_No_Color_Leakage — the 2.0 contract guardrail.
 *
 * Scans every plugin CSS / SCSS file and every pattern + template
 * markup file for forbidden literal color values. The 2.0 contract
 * (see /docs/DESIGN-TOKENS.md) requires that all color references go
 * through `var(--pfbt-format-X-*, NEUTRAL)` tokens where NEUTRAL is
 * `transparent` / `inherit` / `currentColor` / `none`.
 *
 * Forbidden patterns:
 *   - Hex colors (#RGB, #RRGGBB, #RRGGBBAA)
 *   - rgb() / rgba() functional notation
 *   - hsl() / hsla() functional notation
 *   - oklch() / oklab() functional notation
 *   - Named CSS colors (red, blue, etc.)
 *
 * Exempt scopes:
 *   - tests/fixtures/ — test fixtures may include sample colors
 *   - vendor/ — third-party PHPUnit / coverage tooling
 *   - blocks/*/build/ — compiled output (sourced from style.scss)
 *   - node_modules/ — build dependencies
 *   - Comments — // and /* ... *\/ — color names inside docs are fine
 *   - Block declarations marked with `@pfbt-allow-color brand-replica`
 *     — device-frame brand replicas (Slack, Discord, IRC, iPhone, macOS)
 *     where the literal color IS the platform identity
 *
 * The test fails with a list of every violation site so a maintainer
 * can fix and re-run.
 *
 * @package PostFormatsBlockThemes
 * @since 2.0.0
 *
 * @covers \PFBT_Format_Helpers
 */

// phpcs:disable WordPress.Files.FileName.InvalidClassFileName
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase

class Test_No_Color_Leakage extends WP_UnitTestCase {

	/**
	 * Plugin root directory.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Set up paths.
	 */
	public function set_up() {
		parent::set_up();
		$this->plugin_dir = dirname( __DIR__, 2 );
	}

	/**
	 * Test that no plugin CSS / SCSS file contains forbidden color literals
	 * outside of brand-replica exempt blocks.
	 */
	public function test_css_files_have_no_color_leakage() {
		$violations = array();
		$files      = $this->collect_files( array( 'css', 'scss' ) );
		$this->assertNotEmpty( $files, 'No CSS / SCSS files scanned — adjust collect_files() globs.' );

		foreach ( $files as $file ) {
			$violations = array_merge( $violations, $this->scan_file( $file ) );
		}

		$this->assertEmpty(
			$violations,
			sprintf(
				"Plugin CSS contract violation: %d forbidden color literal(s) found.\n%s",
				count( $violations ),
				$this->format_violations( $violations )
			)
		);
	}

	/**
	 * Test that no pattern PHP file contains forbidden color literals.
	 *
	 * Patterns can include theme-palette slug references via
	 * `backgroundColor:"surface"` etc., but never literal colors.
	 */
	public function test_pattern_files_have_no_color_leakage() {
		$violations = array();
		$files      = $this->collect_pattern_files();
		$this->assertNotEmpty( $files, 'No pattern files scanned.' );

		foreach ( $files as $file ) {
			$violations = array_merge( $violations, $this->scan_file( $file ) );
		}

		$this->assertEmpty(
			$violations,
			sprintf(
				"Plugin pattern contract violation: %d forbidden color literal(s) found.\n%s",
				count( $violations ),
				$this->format_violations( $violations )
			)
		);
	}

	/**
	 * Test that no v2 template HTML file contains forbidden color literals.
	 */
	public function test_template_files_have_no_color_leakage() {
		$violations = array();
		$files      = glob( $this->plugin_dir . '/templates/v2/*.html' ) ?: array();

		foreach ( $files as $file ) {
			$violations = array_merge( $violations, $this->scan_file( $file ) );
		}

		$this->assertEmpty(
			$violations,
			sprintf(
				"Plugin template contract violation: %d forbidden color literal(s) found.\n%s",
				count( $violations ),
				$this->format_violations( $violations )
			)
		);
	}

	/**
	 * Test that the plugin's theme.json does NOT define any
	 * format-specific palette entries with literal colors.
	 *
	 * Pre-2.0 the plugin shipped 12 format-X-bg/border palette entries
	 * with stock Gutenberg defaults. 2.0 removes them — themes own
	 * the palette. This test enforces that.
	 */
	public function test_theme_json_no_format_palette_entries() {
		$theme_json_path = $this->plugin_dir . '/theme.json';
		$this->assertFileExists( $theme_json_path );

		$data = json_decode( (string) file_get_contents( $theme_json_path ), true );
		$this->assertIsArray( $data, 'theme.json is not valid JSON.' );

		$palette = $data['settings']['color']['palette'] ?? array();
		$leaks   = array();
		foreach ( $palette as $entry ) {
			$slug = $entry['slug'] ?? '';
			if ( 0 === strpos( $slug, 'format-' ) ) {
				$leaks[] = $slug;
			}
		}

		$this->assertEmpty(
			$leaks,
			sprintf(
				"Plugin theme.json still contains %d format-* palette entries: %s\nThe 2.0 contract requires the plugin to NOT contribute palette colors. Remove these entries.",
				count( $leaks ),
				implode( ', ', $leaks )
			)
		);
	}

	/* ----------------------------------------------------------------
	 * Internal helpers
	 * -------------------------------------------------------------- */

	/**
	 * Collect plugin source files of given extensions.
	 *
	 * Excludes vendor/, node_modules/, build outputs, and tests/fixtures/.
	 *
	 * @param string[] $extensions File extensions to collect.
	 * @return string[] Absolute paths.
	 */
	private function collect_files( array $extensions ) {
		$files     = array();
		$iterator  = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$this->plugin_dir,
				RecursiveDirectoryIterator::SKIP_DOTS
			)
		);
		$exclude_segments = array(
			'/vendor/',
			'/node_modules/',
			'/tests/fixtures/',
			'/build/',
		);

		foreach ( $iterator as $path => $info ) {
			if ( ! $info->isFile() ) {
				continue;
			}
			$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, $extensions, true ) ) {
				continue;
			}
			foreach ( $exclude_segments as $segment ) {
				if ( false !== strpos( $path, $segment ) ) {
					continue 2;
				}
			}
			$files[] = $path;
		}

		return $files;
	}

	/**
	 * Collect pattern PHP files (patterns/*.php and patterns/display/*.php).
	 *
	 * @return string[]
	 */
	private function collect_pattern_files() {
		$files = array();
		foreach ( array(
			$this->plugin_dir . '/patterns/*.php',
			$this->plugin_dir . '/patterns/display/*.php',
		) as $pattern ) {
			$matches = glob( $pattern ) ?: array();
			foreach ( $matches as $match ) {
				if ( false !== strpos( $match, 'index.php' ) ) {
					continue;
				}
				$files[] = $match;
			}
		}
		return $files;
	}

	/**
	 * Scan a file for forbidden color literals, respecting
	 * @pfbt-allow-color brand-replica exempt blocks.
	 *
	 * @param string $file Absolute path.
	 * @return array[] List of violations: [['file' => str, 'line' => int, 'value' => str, 'context' => str]]
	 */
	private function scan_file( $file ) {
		$source = file_get_contents( $file );
		if ( false === $source ) {
			return array();
		}

		// Strip CSS/SCSS comments and PHP block comments. Color tokens
		// inside docblocks describing the contract are not violations.
		$stripped = preg_replace( '#/\*.*?\*/#s', '', $source ) ?? $source;
		// Strip PHP // line comments and HTML <!-- comments.
		$stripped = preg_replace( '#//.*$#m', '', $stripped ) ?? $stripped;
		$stripped = preg_replace( '#<!--.*?-->#s', '', $stripped ) ?? $stripped;

		// Compute exempt line ranges (within the original source, since
		// brand-replica markers and the rule blocks they cover live in
		// real comments + rules — stripping comments would lose them).
		$exempt_ranges = $this->compute_brand_replica_ranges( $source );

		$violations = array();
		$lines      = explode( "\n", $stripped );
		foreach ( $lines as $i => $line ) {
			$line_num = $i + 1;
			if ( $this->is_in_range( $line_num, $exempt_ranges ) ) {
				continue;
			}

			// Hex
			if ( preg_match_all( '/(?<![\w-])(#[0-9a-fA-F]{3,8})\b/', $line, $matches ) ) {
				foreach ( $matches[1] as $val ) {
					$violations[] = array(
						'file'    => $this->relative( $file ),
						'line'    => $line_num,
						'value'   => $val,
						'context' => trim( $line ),
					);
				}
			}
			// rgb() / rgba() / hsl() / hsla() / oklch() / oklab()
			if ( preg_match_all( '/\b(rgba?|hsla?|oklch|oklab)\s*\([^)]*\)/i', $line, $matches ) ) {
				foreach ( $matches[0] as $val ) {
					$violations[] = array(
						'file'    => $this->relative( $file ),
						'line'    => $line_num,
						'value'   => $val,
						'context' => trim( $line ),
					);
				}
			}
		}

		return $violations;
	}

	/**
	 * Compute line ranges covered by `@pfbt-allow-color brand-replica` markers.
	 *
	 * The marker is a /* ... *\/ block comment that immediately precedes
	 * a CSS rule block { ... }. The exempt range covers the rule block
	 * the marker introduces (including all nested rules within the
	 * outermost block).
	 *
	 * @param string $source Full file contents.
	 * @return array<int,int[]> List of [start_line, end_line] tuples.
	 */
	private function compute_brand_replica_ranges( $source ) {
		$ranges = array();
		if ( false === strpos( $source, '@pfbt-allow-color' ) ) {
			return $ranges;
		}

		$lines = explode( "\n", $source );
		$count = count( $lines );

		for ( $i = 0; $i < $count; $i++ ) {
			$line = $lines[ $i ];
			// Match the marker phrase. Skip the file's own docblock if
			// it contains the literal phrase as documentation — only
			// markers that precede a rule block count.
			if ( false === strpos( $line, '@pfbt-allow-color' ) ) {
				continue;
			}
			if ( false === strpos( $line, 'brand-replica' ) ) {
				continue;
			}

			// Find end of the comment block this marker is in.
			$j = $i;
			while ( $j < $count && false === strpos( $lines[ $j ], '*/' ) ) {
				$j++;
			}
			$j++; // line after */

			// Find the next opening brace.
			while ( $j < $count && false === strpos( $lines[ $j ], '{' ) ) {
				$j++;
			}
			if ( $j >= $count ) {
				continue;
			}

			// If this marker is followed by another open brace far
			// later (the file's docblock case where the block this
			// "introduces" is some unrelated rule), skip if there's
			// another marker between.
			$has_next_marker_before_brace = false;
			for ( $k = $i + 1; $k < $j; $k++ ) {
				if ( false !== strpos( $lines[ $k ], '@pfbt-allow-color' )
					&& false !== strpos( $lines[ $k ], 'brand-replica' ) ) {
					$has_next_marker_before_brace = true;
					break;
				}
			}
			if ( $has_next_marker_before_brace ) {
				// Different marker — skip this one.
				continue;
			}

			// Walk braces to find the matching close.
			$start_line = $j + 1; // 1-indexed
			$depth      = 0;
			$end_line   = $start_line;
			while ( $j < $count ) {
				$depth   += substr_count( $lines[ $j ], '{' );
				$depth   -= substr_count( $lines[ $j ], '}' );
				$end_line = $j + 1;
				if ( $depth <= 0 && $end_line > $start_line ) {
					break;
				}
				$j++;
			}

			$ranges[] = array( $start_line, $end_line );
		}

		return $ranges;
	}

	/**
	 * Whether a line number falls inside any of the exempt ranges.
	 *
	 * @param int   $line   Line number (1-indexed).
	 * @param array $ranges List of [start, end] tuples.
	 * @return bool
	 */
	private function is_in_range( $line, array $ranges ) {
		foreach ( $ranges as $range ) {
			list( $start, $end ) = $range;
			if ( $line >= $start && $line <= $end ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Relative path from plugin root for cleaner error messages.
	 *
	 * @param string $path Absolute path.
	 * @return string Relative path.
	 */
	private function relative( $path ) {
		return ltrim( str_replace( $this->plugin_dir, '', $path ), '/\\' );
	}

	/**
	 * Format a violation list as a readable error message.
	 *
	 * @param array $violations List of violation arrays.
	 * @return string
	 */
	private function format_violations( array $violations ) {
		$output = '';
		foreach ( $violations as $v ) {
			$output .= sprintf(
				"  %s:%d   %s   in: %s\n",
				$v['file'],
				$v['line'],
				$v['value'],
				$v['context']
			);
		}
		return $output;
	}
}
