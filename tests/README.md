# Post Formats Power-Up - Testing Guide

This document explains how to run and maintain tests for the Post Formats Power-Up plugin.

## Test Structure

```
tests/
├── accessibility/          # WCAG 2.2 AA compliance tests
│   ├── modal-wcag.spec.js # Modal accessibility tests
│   └── utils.js           # Accessibility testing utilities
├── unit/                  # PHPUnit unit tests
├── integration/           # PHPUnit integration tests
├── visual/               # Visual regression tests
└── bootstrap.php         # PHPUnit bootstrap
```

## Prerequisites

### For PHP Tests (PHPUnit)

1. **Install Composer dependencies**:
   ```bash
   composer install
   ```

2. **Install WordPress test suite**:
   ```bash
   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

   Parameters:
   - `wordpress_test` - Test database name
   - `root` - MySQL username
   - `''` - MySQL password (empty in this example)
   - `localhost` - MySQL host
   - `latest` - WordPress version to test against

### For JavaScript/E2E Tests (Playwright)

1. **Install Node dependencies**:
   ```bash
   npm install
   ```

2. **Install Playwright browsers**:
   ```bash
   npx playwright install
   ```

3. **Configure environment**:
   Copy `.env.example` to `.env` and configure:
   ```env
   WP_BASE_URL=http://localhost:8888
   WP_USERNAME=admin
   WP_PASSWORD=password
   ```

## Running Tests

### Accessibility Tests (Recommended First)

Test WCAG 2.2 AA compliance:

```bash
# Run all accessibility tests
npm run test:a11y

# Run with browser visible (useful for debugging)
npm run test:a11y:headed

# Run specific test file
npx playwright test tests/accessibility/modal-wcag.spec.js
```

**What these tests check**:
- ✅ ARIA roles and labels
- ✅ Keyboard navigation
- ✅ Focus management and trapping
- ✅ Color contrast (WCAG AA: 4.5:1)
- ✅ Screen reader announcements
- ✅ Automated axe-core scan (catches 30-40% of issues)

### PHP Unit Tests

```bash
# Run all unit tests
composer test

# Run specific test suite
vendor/bin/phpunit --testsuite unit

# Run specific test file
vendor/bin/phpunit tests/unit/test-format-registry.php

# Run with code coverage
vendor/bin/phpunit --coverage-html coverage-report
```

### End-to-End Tests

```bash
# Run all E2E tests
npm run test:e2e

# Run with visible browser
npm run test:e2e:headed

# Run specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit
```

### Visual Regression Tests

```bash
# Run visual tests
npm run test:visual

# Update snapshots (after intentional UI changes)
npx playwright test --update-snapshots
```

### Code Quality Checks

```bash
# PHP Code Sniffer (WordPress Coding Standards)
composer phpcs

# Auto-fix coding standards violations
composer phpcbf

# PHPStan static analysis
composer phpstan

# JavaScript linting
npm run lint:js

# CSS linting
npm run lint:css
```

## Test Results

### Viewing Results

- **PHPUnit**: Results display in terminal, HTML coverage in `coverage-report/`
- **Playwright**: HTML report auto-opens after test run, or run:
  ```bash
  npx playwright show-report
  ```

### CI/CD Results

GitHub Actions runs all tests automatically on:
- Every push to `main` or `develop`
- Every pull request
- Nightly builds (full matrix)

View results at: `https://github.com/your-username/post-formats-power-up/actions`

## Writing New Tests

### Accessibility Test Example

```javascript
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const { loginToWordPress, goToNewPost } = require('./utils');

test('my new feature is accessible', async ({ page }) => {
    await loginToWordPress(page);
    await goToNewPost(page);

    // Your test actions
    await page.click('.my-feature-button');

    // Run axe scan
    const results = await new AxeBuilder({ page })
        .include('.my-feature')
        .withTags(['wcag2a', 'wcag2aa', 'wcag22aa'])
        .analyze();

    expect(results.violations).toEqual([]);
});
```

### Unit Test Example

```php
<?php
/**
 * Test my new feature
 *
 * @covers PFPU_My_Class
 */
class Test_My_Feature extends WP_UnitTestCase {

    public function test_my_function_works() {
        $result = my_function( 'input' );

        $this->assertEquals( 'expected', $result );
    }
}
```

## Debugging Tests

### Playwright/E2E Tests

1. **Run with headed mode** to see the browser:
   ```bash
   npm run test:a11y:headed
   ```

2. **Use Playwright Inspector**:
   ```bash
   PWDEBUG=1 npx playwright test
   ```

3. **Take screenshots**:
   ```javascript
   await page.screenshot({ path: 'debug.png' });
   ```

4. **View trace** (if test failed):
   ```bash
   npx playwright show-trace trace.zip
   ```

### PHPUnit Tests

1. **Enable WordPress debug mode** in `tests/bootstrap.php`:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', true );
   ```

2. **Use var_dump()** or `print_r()`:
   ```php
   var_dump( $my_variable );
   $this->fail( 'Debug stop' );
   ```

3. **Run single test**:
   ```bash
   vendor/bin/phpunit --filter test_my_specific_test
   ```

## Accessibility Testing Tools

### Browser Extensions (Manual Testing)

1. **axe DevTools** (Free):
   - [Chrome](https://chrome.google.com/webstore/detail/axe-devtools-web-accessibility-testing/lhdoppojpmngadmnindnejefpokejbdd)
   - [Firefox](https://addons.mozilla.org/en-US/firefox/addon/axe-devtools/)

2. **WAVE** (Free):
   - [Chrome](https://chrome.google.com/webstore/detail/wave-evaluation-tool/jbbplnpkjmmeebjpijfedlgcdilocofh)
   - [Firefox](https://addons.mozilla.org/en-US/firefox/addon/wave-accessibility-tool/)

3. **Lighthouse** (Built into Chrome DevTools):
   - Open DevTools → Lighthouse tab → Run accessibility audit

### Screen Readers (Manual Testing)

- **Windows**: [NVDA](https://www.nvaccess.org/) (free)
- **macOS**: VoiceOver (built-in, activate with Cmd+F5)
- **Linux**: [Orca](https://wiki.gnome.org/Projects/Orca) (free)

### Keyboard Testing Checklist

Test all features using only keyboard:

- [ ] Tab through all interactive elements
- [ ] Shift+Tab goes backwards
- [ ] Enter/Space activates buttons
- [ ] Escape closes modals
- [ ] Arrow keys navigate within components
- [ ] Focus is always visible
- [ ] No keyboard traps (except modal with Escape exit)

## Common Issues & Solutions

### Issue: "Could not find WordPress test suite"

**Solution**: Run the test suite installer:
```bash
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Issue: "Playwright browser not found"

**Solution**: Install browsers:
```bash
npx playwright install
```

### Issue: "axe violations found"

**Solution**:
1. Run test with `--headed` flag to see the issue
2. Check console output for violation details
3. Visit the `helpUrl` provided in violation for guidance
4. Fix the accessibility issue in your code
5. Re-run tests

### Issue: "Modal not found in tests"

**Solution**: Check selector matches your implementation:
```javascript
// Try different selectors
const modal = page.locator('.pfpu-format-modal');
const modal = page.locator('[role="dialog"]');
const modal = page.locator('[aria-label*="format"]');
```

## Test Coverage Goals

| Type | Current | Target |
|------|---------|--------|
| PHP Unit Tests | TBD | 80% |
| PHP Integration Tests | TBD | 60% |
| E2E Critical Paths | TBD | 100% |
| WCAG AA Compliance | TBD | 100% |

Run coverage reports:
```bash
# PHP coverage
composer phpunit -- --coverage-html coverage-report

# JavaScript coverage
npm run test:js -- --coverage
```

## Resources

- [WordPress Plugin Handbook - Testing](https://developer.wordpress.org/plugins/testing/)
- [Playwright Documentation](https://playwright.dev/docs/intro)
- [axe-core Rules](https://github.com/dequelabs/axe-core/blob/develop/doc/rule-descriptions.md)
- [WCAG 2.2 Quick Reference](https://www.w3.org/WAI/WCAG22/quickref/)
- [WordPress Accessibility Handbook](https://make.wordpress.org/accessibility/handbook/)

## Getting Help

- **Bug in tests**: [Open an issue](https://github.com/your-username/post-formats-power-up/issues)
- **Questions**: [GitHub Discussions](https://github.com/your-username/post-formats-power-up/discussions)
- **WordPress Testing**: [WordPress.org Support Forum](https://wordpress.org/support/plugin/post-formats-power-up/)
