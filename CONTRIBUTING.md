# Contributing to Post Formats Power-Up

Thank you for your interest in contributing! This document provides guidelines for contributing to this WordPress plugin.

## Quick Start

```bash
# Clone repository
git clone https://github.com/your-username/post-formats-power-up.git
cd post-formats-power-up

# Install dependencies
npm install
composer install

# Install test environment
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run tests
npm run test:a11y
composer test
```

## Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Make your changes
4. Write/update tests
5. Run all tests (must pass)
6. Commit following [Conventional Commits](https://www.conventionalcommits.org/)
7. Push and create a Pull Request

## Testing Requirements

**All PRs must include tests** and pass existing tests:

```bash
# Required before PR
npm run test:a11y      # Accessibility tests MUST pass
composer test          # Unit/integration tests
npm run lint:js        # JavaScript linting
composer phpcs         # PHP Coding Standards
```

## Accessibility First

This plugin prioritizes **WCAG 2.2 AA compliance**. All UI changes MUST:

- ✅ Pass automated axe-core tests
- ✅ Work with keyboard only
- ✅ Have visible focus indicators
- ✅ Meet 4.5:1 color contrast
- ✅ Work with screen readers

Test with: `npm run test:a11y:headed`

## Code Standards

- **PHP**: WordPress Coding Standards (run `composer phpcs`)
- **JavaScript**: ESLint with WordPress config (run `npm run lint:js`)
- **Accessibility**: WCAG 2.2 AA (run `npm run test:a11y`)

## Pull Request Checklist

Before submitting a PR, ensure:

- [ ] Tests added/updated and passing
- [ ] Accessibility tests pass
- [ ] Code follows WordPress standards
- [ ] Documentation updated
- [ ] Commit messages follow conventions
- [ ] No console errors or PHP warnings

## Reporting Bugs

When reporting bugs, include:

1. **Steps to reproduce**
2. **Expected behavior**
3. **Actual behavior**
4. **WordPress version**
5. **PHP version**
6. **Theme name**
7. **Screenshots** (if UI issue)

## Translation Contributions

We use [Potomatic](https://github.com/GravityKit/potomatic) for translations:

```bash
# Generate POT file
composer i18n

# Add your translation
cp languages/post-formats-power-up.pot languages/post-formats-power-up-{locale}.po
# Edit the .po file with translations
```

## Questions?

- **GitHub Discussions**: For questions and ideas
- **GitHub Issues**: For bug reports and feature requests
- **WordPress.org Forums**: For user support

Thank you for contributing! 🎉
