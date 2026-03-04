# Security Policy

## Supported Versions

| Version | Supported |
| ------- | --------- |
| 1.2.x   | Yes       |
| 1.1.x   | Yes       |
| < 1.1   | No        |

## Reporting a Vulnerability

If you discover a security vulnerability in Post Formats for Block Themes, report it responsibly. **Do not open a public GitHub issue.**

### How to Report

1. Email **courtney@developer.developer** with the subject line: `[SECURITY] Post Formats for Block Themes`
2. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Affected version(s)
   - Potential impact
   - Suggested fix (if you have one)

### What to Expect

- **Acknowledgment**: Within 48 hours of your report
- **Assessment**: Within 5 business days, you'll receive an initial assessment
- **Resolution**: Critical vulnerabilities are patched within 7 days; lower-severity issues within 30 days
- **Disclosure**: Coordinated disclosure after a fix is released

### Scope

The following are in scope for security reports:

- Cross-site scripting (XSS) in admin pages or block output
- SQL injection in database queries
- Cross-site request forgery (CSRF) in admin actions
- Unauthorized access to post format operations
- Data exposure through REST API endpoints
- Path traversal in file operations
- Privilege escalation

### Out of Scope

- Vulnerabilities in WordPress core
- Vulnerabilities in third-party plugins (Bookmark Card, Podlove, Able Player)
- Issues requiring physical access to the server
- Social engineering attacks

## Security Practices

This plugin follows WordPress security best practices:

- All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- All input sanitized with `sanitize_text_field()`, `absint()`, `wp_unslash()`
- Nonce verification on all form submissions and AJAX requests
- Capability checks (`current_user_can()`) on all admin operations
- Prepared statements for all database queries
- No direct file system access without WordPress APIs
