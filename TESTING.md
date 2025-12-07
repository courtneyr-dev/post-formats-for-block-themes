# Testing Guide for Post Formats for Block Themes

This document explains how to run the comprehensive test suite for the Post Formats for Block Themes plugin.

## Quick Start

### Run All Tests

```bash
cd /path/to/plugin
php run-quick-tests.php
```

### Run Tests with Shell Script

```bash
cd /path/to/plugin
./run-tests.sh
```

## Test Files

### Main Test Runners

1. **`run-quick-tests.php`** - PHP-based comprehensive test suite
   - Fast execution
   - No external dependencies
   - Covers all major test categories

2. **`run-tests.sh`** - Bash-based test suite
   - More detailed output
   - Color-coded results
   - Generates timestamped reports

### Configuration Files

3. **`composer.json`** - Development dependencies
   - PHPCS (PHP_CodeSniffer)
   - WPCS (WordPress Coding Standards)
   - PHPStan (Static Analysis)
   - PHPUnit (Unit Testing)

4. **`phpcs.xml`** - Coding standards configuration
   - WordPress coding standards
   - WordPress-Extra standards
   - WordPress-VIP-Go standards
   - Custom rule exclusions

5. **`phpstan.neon`** - Static analysis configuration
   - Level 6 strictness
   - WordPress function stubs
   - Type checking

6. **`phpunit.xml`** - Unit test configuration
   - Test suite definitions
   - Code coverage settings

## Test Categories

### ✅ 1. PHP Syntax Validation
- Validates all PHP files parse correctly
- Checks for syntax errors
- **Files tested:** All `.php` files

### ✅ 2. Plugin Header Validation
- Verifies required plugin headers
- Checks WordPress.org compatibility
- **Validates:** Plugin Name, Description, Version, etc.

### ✅ 3. Text Domain Validation
- Ensures consistent i18n text domain
- Checks all translation functions
- **Expected domain:** `post-formats-for-block-themes`

### ✅ 4. I18n Function Usage
- Validates proper use of `__()`
- Checks `_e()`, `esc_html__()`, etc.
- Ensures all user-facing text is translatable

### ✅ 5. Sanitization and Escaping
- Checks output escaping (`esc_html()`, `esc_attr()`, `esc_url()`)
- Validates input sanitization
- Prevents XSS vulnerabilities

### ✅ 6. Nonce and Capability Checks
- Validates CSRF protection
- Checks capability requirements
- Ensures proper authorization

### ✅ 7. Enqueueing Patterns
- Validates proper use of `wp_enqueue_script()`
- Checks `wp_enqueue_style()` usage
- Ensures no inline scripts/styles (except where appropriate)

### ✅ 8. Deprecated Function Check
- Scans for deprecated WordPress functions
- Checks for removed PHP functions
- Ensures modern API usage

### ✅ 9. File Structure Validation
- Verifies all required files present
- Checks directory organization
- Validates template files

### ✅ 10. Readme.txt Validation
- Checks WordPress.org required sections
- Validates readme format
- Ensures all metadata present

### ✅ 11. Security Pattern Scanning (SAST)
- Scans for dangerous functions (`eval()`, `exec()`, etc.)
- Checks for SQL injection vulnerabilities
- Validates file operations security

### ✅ 12. PHP Compatibility Check
- Validates PHP 7.4+ compatibility
- Checks for deprecated PHP features
- Ensures forward compatibility (PHP 8.0+)

### ✅ 13. Code Quality Metrics
- Analyzes code complexity
- Counts lines of code
- Evaluates class structure

### ✅ 14. JavaScript Validation
- Checks for console statements
- Validates ES6+ syntax
- Ensures proper dependencies

### ✅ 15. CSS Validation
- Counts CSS files
- Checks for best practices
- Validates theme.json usage

## Running Specific Tests

### PHPCS (WordPress Coding Standards)

```bash
composer install
composer phpcs
```

**Fix automatically:**
```bash
composer phpcbf
```

### PHPStan (Static Analysis)

```bash
composer install
composer phpstan
```

### PHP Lint (Syntax Check)

```bash
composer lint
```

### Unit Tests

```bash
composer test
```

## Test Results

### Output Locations

- **Console:** Real-time output with color coding
- **Test Results Directory:** `./test-results/`
- **Report Files:** `test-report-YYYYMMDD-HHMMSS.txt`

### Reading Test Results

```bash
# View latest test report
cat test-results/test-report-*.txt | tail -50

# List all reports
ls -lh test-results/
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php run-quick-tests.php
      - name: PHPCS
        run: composer phpcs
      - name: PHPStan
        run: composer phpstan
```

## Manual Testing Checklist

Beyond automated tests, perform these manual checks:

### Editor Testing
- [ ] Create new post with each format
- [ ] Verify format-specific patterns load
- [ ] Test format switching
- [ ] Check template assignment
- [ ] Validate auto-detection

### Frontend Testing
- [ ] View posts with different formats
- [ ] Check template styling
- [ ] Verify responsive design
- [ ] Test accessibility (keyboard navigation)
- [ ] Validate screen reader support

### Admin Testing
- [ ] Check Posts list format column
- [ ] Test Screen Options toggle
- [ ] Verify format filtering
- [ ] Test Repair Tool
- [ ] Check Site Editor template customization

### Compatibility Testing
- [ ] Test with Twenty Twenty-Five theme
- [ ] Test with other block themes
- [ ] Check multisite compatibility
- [ ] Verify RTL language support
- [ ] Test with high-traffic scenarios

## Performance Testing

### Query Monitor

Install and activate Query Monitor plugin:

```
- Check database queries
- Monitor PHP errors
- Review HTTP requests
- Analyze hook execution times
```

### Lighthouse CI

Run Lighthouse from Chrome DevTools:

```
- Performance score
- Accessibility score
- Best practices score
- SEO score
```

## Accessibility Testing

### Automated Tools

1. **axe DevTools** - Browser extension
2. **WAVE** - Web accessibility evaluation tool
3. **Lighthouse** - Built into Chrome DevTools

### Manual Testing

1. **Keyboard Navigation**
   - Tab through all interactive elements
   - Verify focus indicators
   - Test Esc key (modals)

2. **Screen Reader**
   - Test with NVDA (Windows)
   - Test with JAWS (Windows)
   - Test with VoiceOver (macOS)

3. **Visual Testing**
   - Zoom to 200%
   - Enable Windows High Contrast
   - Test with color blindness simulators

## Security Testing

### WordPress Security Plugins

Test with these plugins active:

- Wordfence Security
- Sucuri Security
- iThemes Security

### Manual Security Checks

1. **Input Validation**
   - Try SQL injection in forms
   - Attempt XSS in content
   - Test CSRF without nonce

2. **File Upload**
   - Try uploading malicious files
   - Test file type restrictions

3. **Authentication**
   - Test capability checks
   - Verify admin-only features restricted

## Test Coverage Goals

| Area | Target | Status |
|------|--------|--------|
| PHP Code Coverage | 80%+ | ⏳ In Progress |
| JavaScript Coverage | 70%+ | ⏳ In Progress |
| Manual Test Cases | 100% | ✅ Complete |
| Accessibility | WCAG 2.1 AA | ✅ Complete |
| Security | OWASP Top 10 | ✅ Complete |

## Troubleshooting

### Tests Fail to Run

```bash
# Check PHP version
php -v

# Verify permissions
chmod +x run-tests.sh

# Check file syntax
php -l run-quick-tests.php
```

### Composer Issues

```bash
# Clear composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor
composer install
```

### False Positives

Some test warnings are expected:

- **phpstan-bootstrap.php** - Test helper file, not production code
- **run-quick-tests.php** - Test script itself (self-references)
- **Code examples in comments** - Documentation only

## Contributing

When contributing code:

1. Run full test suite before committing
2. Fix all failures (warnings OK with justification)
3. Add tests for new features
4. Update documentation
5. Follow WordPress coding standards

## Resources

### WordPress Testing
- [Plugin Handbook - Testing](https://developer.wordpress.org/plugins/testing/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Security Best Practices](https://developer.wordpress.org/plugins/security/)

### Tools Documentation
- [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPStan](https://phpstan.org/user-guide/getting-started)
- [PHPUnit](https://phpunit.de/documentation.html)

---

**Last Updated:** December 7, 2025
**Version:** 1.0.0
