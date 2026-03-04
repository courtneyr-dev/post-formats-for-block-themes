## Description

Brief description of your changes.

## Related Issues

Fixes #(issue number)

## Type of Change

- [ ] Bug fix (non-breaking change that fixes an issue)
- [ ] New feature (non-breaking change that adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to change)
- [ ] Documentation update

## Testing Checklist

- [ ] Tested with WordPress 6.9+ and a block theme (e.g., Twenty Twenty-Five)
- [ ] Tested in Chrome, Firefox, Safari, and Edge
- [ ] Keyboard navigation works for any UI changes
- [ ] Screen reader tested for any UI changes
- [ ] No PHP errors, warnings, or notices
- [ ] No browser console errors

## Code Quality Checklist

- [ ] Follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [ ] All output escaped (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`)
- [ ] All input sanitized (`sanitize_text_field()`, `absint()`)
- [ ] Nonce verification on form submissions
- [ ] Capability checks on admin operations
- [ ] Strings use the `post-formats-for-block-themes` text domain
- [ ] `composer phpcs` passes
- [ ] `npm run lint:js` passes

## Screenshots

If your change affects the UI, add before/after screenshots.
