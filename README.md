# Post Formats Power-Up

A modern WordPress plugin that brings post format functionality to block themes with intelligent patterns, automatic detection, and an enhanced editor experience.

**✨ Includes integrated Chat Log block** - Display beautiful conversation transcripts from Slack, Discord, Teams, WhatsApp, and more. No separate plugin needed!

## Features

- **10 Format-Specific Patterns** with locked first blocks for consistency
- **Integrated Chat Log Block** for conversation transcripts (chatlog/conversation)
- **Automatic Format Detection** based on content structure
- **Format Selection Modal** on new post creation
- **Format Switcher Panel** for mid-edit format changes
- **Status Format Validation** with 280-character limit
- **Repair Tool** to scan and fix format mismatches
- **Full Accessibility** (WCAG 2.2 AA compliant)
- **Block Theme Integration** with CSS custom properties

## Requirements

- WordPress 6.8 or higher
- PHP 7.4 or higher
- A block theme (classic themes not supported)

## Installation

### From WordPress.org

1. Go to Plugins → Add New in your WordPress admin
2. Search for "Post Formats Power-Up"
3. Install and activate

### Manual Installation

1. Clone this repository or download the ZIP
2. Run `npm install` to install dependencies
3. Run `npm run build` to compile assets
4. Copy to your WordPress `wp-content/plugins` directory
5. Activate via WordPress admin

## Development

### Prerequisites

- Node.js 18+
- npm or yarn
- Composer (optional, for development tools)

### Setup

```bash
# Install dependencies
npm install

# Start development with hot reload
npm start

# Build for production
npm run build

# Lint JavaScript
npm run lint:js

# Lint CSS
npm run lint:css
```

### File Structure

```
post-formats-power-up/
├── includes/          # PHP classes
├── patterns/          # Block pattern definitions
├── src/              # JavaScript source files
│   └── editor/       # Editor components
├── styles/           # CSS files
├── templates/        # Admin page templates
└── build/            # Compiled assets (generated)
```

## Usage

### For Users

1. **Create a New Post** – Format selection modal appears automatically
2. **Choose Your Format** – Select from 10 format options
3. **Start Writing** – Pattern is inserted with locked first block
4. **Change Formats** – Use the Format Switcher in the sidebar
5. **Fix Old Posts** – Run Tools → Post Format Repair

### For Developers

#### Filters

```php
// Modify format definitions
add_filter('pfpu_registered_formats', function($formats) {
    $formats['my-format'] = [
        'name' => 'My Format',
        'description' => 'Custom format',
        'icon' => 'admin-post',
        // ...
    ];
    return $formats;
});

// Filter auto-detected format
add_filter('pfpu_detected_format', function($format, $first_block, $all_blocks) {
    // Custom detection logic
    return $format;
}, 10, 3);
```

#### Actions

```php
// After format is detected
add_action('pfpu_format_detected', function($post_id, $format, $post) {
    // Custom logic after detection
}, 10, 3);

// After format is repaired
add_action('pfpu_format_repaired', function($post_id, $format) {
    // Custom logic after repair
}, 10, 2);
```

## Accessibility

This plugin meets WCAG 2.2 AA standards:

- ✅ Keyboard navigation throughout
- ✅ Screen reader announcements
- ✅ Semantic HTML structure
- ✅ Sufficient color contrast
- ✅ Focus management in modals
- ✅ ARIA labels on interactive elements
- ✅ RTL language support

## Plugin Integrations

### Bookmark Card Plugin

Link format automatically uses Bookmark Card blocks when the plugin is active, with graceful fallback to standard paragraphs.

### Chat Log Plugin

Chat format requires this plugin (chatlog/conversation block) for conversation transcript blocks.

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Ensure accessibility compliance
5. Add tests if applicable
6. Submit a pull request

## License

GPL v2 or later - see [LICENSE](LICENSE) file for details.

## Credits

- Inspired by WordPress Twenty Thirteen theme post format treatments
- Built with WordPress Gutenberg components
- Uses Dashicons for format icons

## Support

- **Documentation**: [Plugin documentation](https://example.com/post-formats-power-up/docs/)
- **WordPress.org**: [Support forums](https://wordpress.org/support/plugin/post-formats-power-up/)
- **GitHub**: [Issue tracker](https://github.com/yourname/post-formats-power-up/issues)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) or the readme.txt file for detailed version history.

---

Made with ❤️ for the WordPress block theme community
