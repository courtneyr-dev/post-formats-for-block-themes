# Post Formats for Block Themes

[![WordPress Version](https://img.shields.io/badge/WordPress-6.8%2B-blue.svg)](https://wordpress.org/plugins/post-formats-for-block-themes/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/courtneyr-dev?label=Sponsor&logo=GitHub)](https://github.com/sponsors/courtneyr-dev)

A modern WordPress plugin that brings post format functionality to block themes with intelligent patterns, automatic detection, and an enhanced editor experience.

**✨ Includes integrated Chat Log block** - Display beautiful conversation transcripts from Slack, Discord, Teams, WhatsApp, Telegram, Signal, and more. Supports SRT subtitles, VTT captions, .docx documents, and .html files. No separate plugin needed!

---

## 📥 Download

<!-- This section will be updated after WordPress.org approval -->

### From WordPress.org (Recommended)

**Coming Soon!** This plugin is currently under review by the WordPress.org plugin team.

Once approved, you'll be able to install it directly from:
👉 **[WordPress.org Plugin Directory](https://wordpress.org/plugins/post-formats-for-block-themes/)** _(pending approval)_

### From GitHub Releases

Download the latest release: [Releases](https://github.com/courtneyr-dev/post-formats-for-block-themes/releases)

---

## ✨ Features

### Core Functionality

- **10 Format-Specific Block Patterns** with locked first blocks for consistency
- **Integrated Chat Log Block** for conversation transcripts (chatlog/conversation block)
  - Platforms: Slack, Discord, Microsoft Teams, WhatsApp, Telegram, Signal
  - File formats: SRT subtitles, VTT captions, .docx documents, .html files
- **Automatic Format Detection** based on content structure
- **Format Selection Modal** on new post creation with visual format cards
- **Format Switcher Panel** for mid-edit format changes with content preservation
- **Status Format Validation** with Twitter-style 280-character limit and real-time counter
- **Post Format Repair Tool** to scan and fix format mismatches across all posts
- **Block Theme Integration** with CSS custom properties from theme.json

### Plugin Integrations

- **[Bookmark Card](https://wordpress.org/plugins/bookmark-card/)** - Enhanced link previews for Link format
- **[Podlove Podcasting Plugin](https://wordpress.org/plugins/podlove-podcasting-plugin-for-wordpress/)** - Advanced podcast features for Audio format
- **[Able Player](https://wordpress.org/plugins/ableplayer/)** - Accessible media player for Video and Audio formats

All integrations are optional with graceful fallbacks to WordPress core blocks.

---

## 📋 Requirements

- **WordPress:** 6.8 or higher
- **PHP:** 7.4 or higher (8.0+ recommended)
- **Theme:** Block theme with Full Site Editing (classic themes not supported)
- **Browser:** Modern browser with JavaScript enabled

### Recommended Block Themes

- Twenty Twenty-Five (WordPress 2025 default)
- Twenty Twenty-Four (WordPress 2024 default)
- Twenty Twenty-Three (WordPress 2023 default)
- Any modern block theme from [WordPress.org Theme Directory](https://wordpress.org/themes/)

---

## 🚀 Installation

### Option 1: From WordPress.org (After Approval)

1. Go to **Plugins → Add New** in your WordPress admin
2. Search for **"Post Formats for Block Themes"**
3. Click **Install Now** and then **Activate**

### Option 2: Manual Installation

1. Download the latest release ZIP from [GitHub Releases](https://github.com/courtneyr-dev/post-formats-for-block-themes/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Option 3: Development Installation

```bash
# Clone the repository
git clone https://github.com/courtneyr-dev/post-formats-for-block-themes.git
cd post-formats-for-block-themes

# Install Node dependencies
npm install

# Install Composer dependencies (optional, for development tools)
composer install

# Build JavaScript assets
npm run build

# Copy to WordPress plugins directory
# Example for Local by Flywheel:
cp -r . ~/Local\ Sites/your-site/app/public/wp-content/plugins/post-formats-for-block-themes/

# Activate via WordPress admin or WP-CLI
wp plugin activate post-formats-for-block-themes
```

---

## 🛠️ Development

### Prerequisites

- **Node.js** 18+ (LTS recommended)
- **npm** or **yarn**
- **Composer** (for PHP development tools)
- **WordPress** 6.8+ local development environment

### Quick Start

```bash
# Install all dependencies
npm install
composer install

# Start development with hot reload
npm start

# Build for production
npm run build

# Run linting
npm run lint:js    # ESLint for JavaScript
npm run lint:css   # Stylelint for CSS
composer phpcs     # PHP_CodeSniffer for PHP
composer phpstan   # PHPStan static analysis
```

### Testing

```bash
# Run all tests
npm run test:all

# Individual test suites
npm run test:a11y         # Accessibility tests (Playwright + axe-core)
npm run test:e2e          # End-to-end tests (Playwright)
npm run test:visual       # Visual regression tests
npm run test:performance  # Performance benchmarks

# PHP tests
composer test             # Run all PHP tests
composer phpunit          # Unit tests only
```

### Project Structure

```
post-formats-for-block-themes/
├── .github/
│   └── workflows/        # CI/CD pipelines
├── bin/                  # Helper scripts
│   ├── prepare-release.sh
│   └── detect-local-url.sh
├── blocks/
│   └── chatlog/          # Integrated Chat Log block
├── build/                # Compiled JavaScript (generated)
├── docs/                 # Documentation
│   ├── TESTING-SUMMARY.md
│   ├── DEPLOYMENT.md
│   └── SETUP-COMPLETE.md
├── includes/             # PHP classes
│   ├── class-format-registry.php
│   ├── class-format-detector.php
│   ├── class-pattern-manager.php
│   ├── class-block-locker.php
│   └── class-repair-tool.php
├── patterns/             # Block pattern PHP files
├── src/
│   └── editor/           # JavaScript source files
│       ├── format-modal/
│       └── format-switcher/
├── styles/               # CSS files
├── templates/            # Admin page templates
├── tests/                # Test suites
│   ├── accessibility/
│   ├── e2e/
│   ├── visual/
│   ├── performance/
│   └── unit/
├── post-formats-for-block-themes.php  # Main plugin file
├── package.json
├── composer.json
└── readme.txt            # WordPress.org readme
```

---

## 📸 Screenshots

_Screenshots will be added after WordPress.org approval._

1. **Format Selection Modal** - Visual card interface showing all 10 post formats
2. **Format Switcher Panel** - Sidebar panel for changing formats mid-edit
3. **Quote Format Pattern** - Enhanced pullquote with attribution
4. **Chat Log Block** - Slack conversation with avatars and timestamps
5. **Repair Tool** - Admin interface for scanning and fixing format mismatches
6. **Status Format** - Twitter-style composer with 280-character counter
7. **Auto-Detection** - Notification when format is automatically detected
8. **Gallery Format** - Responsive image grid pattern

---

## 🎯 Usage

### For Users

1. **Create a New Post** – Format selection modal appears automatically
2. **Choose Your Format** – Select from 10 format options with descriptions
3. **Start Writing** – Pattern is inserted with locked first block for consistency
4. **Change Formats** – Use the Format Switcher in the sidebar at any time
5. **Fix Old Posts** – Run **Tools → Post Format Repair** to scan existing posts

### For Developers

#### Register Custom Format

```php
add_filter( 'pfbt_registered_formats', function( $formats ) {
    $formats['review'] = [
        'name'         => __( 'Review', 'my-theme' ),
        'description'  => __( 'Product or service review', 'my-theme' ),
        'icon'         => 'star-filled',
        'pattern_slug' => 'my-theme/review-pattern',
    ];
    return $formats;
} );
```

#### Custom Detection Logic

```php
add_filter( 'pfbt_detected_format', function( $format, $first_block, $all_blocks ) {
    // Detect custom block as Gallery format
    if ( $first_block['blockName'] === 'my-plugin/custom-gallery' ) {
        return 'gallery';
    }

    // Use default detection
    return $format;
}, 10, 3 );
```

#### Modify Pattern Content

```php
add_filter( 'pfbt_pattern_content', function( $content, $format_slug ) {
    // Add custom block to Quote patterns
    if ( $format_slug === 'quote' ) {
        $content .= '<!-- wp:paragraph {"className":"quote-source"} -->';
        $content .= '<p class="quote-source">Source information</p>';
        $content .= '<!-- /wp:paragraph -->';
    }
    return $content;
}, 10, 2 );
```

#### React to Format Detection

```php
add_action( 'pfbt_format_detected', function( $post_id, $format, $post ) {
    // Log format detection
    error_log( sprintf( 'Post %d detected as %s format', $post_id, $format ) );

    // Send notification for video posts
    if ( $format === 'video' ) {
        wp_mail(
            'editor@example.com',
            'New Video Post Published',
            sprintf( 'A new video post was published: %s', get_permalink( $post_id ) )
        );
    }
}, 10, 3 );
```

#### Track Format Changes

```php
add_action( 'pfbt_format_changed', function( $post_id, $old_format, $new_format ) {
    // Update custom meta when format changes
    update_post_meta( $post_id, '_format_changed_at', current_time( 'timestamp' ) );
    update_post_meta( $post_id, '_previous_format', $old_format );

    // Analytics tracking
    do_action( 'track_event', 'format_switched', [
        'post_id'    => $post_id,
        'from'       => $old_format,
        'to'         => $new_format,
    ] );
}, 10, 3 );
```

#### Post-Repair Actions

```php
add_action( 'pfbt_format_repaired', function( $post_id, $format ) {
    // Clear caches after format repair
    clean_post_cache( $post_id );

    // Update repair log
    $repairs = get_option( 'pfbt_repair_log', [] );
    $repairs[] = [
        'post_id'   => $post_id,
        'format'    => $format,
        'timestamp' => current_time( 'timestamp' ),
    ];
    update_option( 'pfbt_repair_log', $repairs );
}, 10, 2 );
```

### Available Filters

- `pfbt_registered_formats` - Modify or add format definitions
- `pfbt_detected_format` - Filter auto-detected format before assignment
- `pfbt_pattern_content` - Modify pattern HTML before insertion
- `pfbt_format_detection_rules` - Customize detection rules

### Available Actions

- `pfbt_format_detected` - Runs after automatic format detection
- `pfbt_format_changed` - Runs when user changes format via switcher
- `pfbt_format_repaired` - Runs after repair tool fixes a format
- `pfbt_pattern_inserted` - Runs after pattern is inserted into post

---

## 🏗️ Architecture

### Design Principles

- **Separation of Concerns** - Each PHP class handles one responsibility
- **WordPress Standards** - Follows WordPress Coding Standards and best practices
- **Extensibility** - Comprehensive hooks for customization without modifying core
- **Performance** - JavaScript only loads in editor, no frontend overhead
- **Graceful Degradation** - Plugin integrations work optionally with fallbacks

### Key Components

#### PHP Classes

- **`Format_Registry`** - Manages format definitions and metadata
- **`Format_Detector`** - Analyzes post content to determine format
- **`Pattern_Manager`** - Handles pattern registration and retrieval
- **`Block_Locker`** - Manages block locking for first blocks
- **`Repair_Tool`** - Admin interface for scanning and fixing formats
- **`Format_Styles`** - Enqueues theme-aware CSS

#### JavaScript Modules

- **`format-modal/`** - Modal UI for format selection on new posts
- **`format-switcher/`** - Sidebar panel for changing formats mid-edit
- **Status paragraph validation** - Real-time character counter for Status format

#### Block Patterns

Each format has a dedicated pattern file in `/patterns/`:
- `standard.php`, `aside.php`, `status.php`, `link.php`
- `gallery.php`, `image.php`, `quote.php`, `video.php`, `audio.php`, `chat.php`

Patterns use block locking to maintain format consistency while allowing content flexibility.

---

## 🤝 Contributing

Contributions are welcome! Here's how to get started:

### Ways to Contribute

- 🐛 **Report bugs** via [GitHub Issues](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues)
- 💡 **Suggest features** by opening a discussion
- 🔧 **Submit pull requests** for bug fixes or enhancements
- 🌍 **Translate** the plugin via [WordPress.org translation system](https://translate.wordpress.org/) _(after approval)_
- 📖 **Improve documentation** by fixing typos or adding examples
- 💬 **Answer questions** in support forums _(after approval)_

### Development Workflow

1. **Fork** the repository
2. **Create a feature branch**: `git checkout -b feature/your-feature-name`
3. **Make changes** following WordPress Coding Standards
4. **Test thoroughly**:
   - Run `npm run lint:js` and `composer phpcs`
   - Run `npm run test:all` to verify tests pass
   - Test manually in multiple browsers if UI changes
5. **Commit** with clear, descriptive messages
6. **Push** to your fork and **submit a pull request**

### Coding Standards

- **PHP**: WordPress Coding Standards (enforced via PHP_CodeSniffer)
- **JavaScript**: WordPress JavaScript Coding Standards (enforced via ESLint)
- **CSS**: WordPress CSS Coding Standards (enforced via Stylelint)
- **Accessibility**: Follow WCAG guidelines for any UI changes
- **Testing**: Add tests for new features when applicable

### Testing Requirements

All PRs should:
- ✅ Pass existing automated tests
- ✅ Include new tests for added functionality
- ✅ Be manually tested in WordPress 6.8+ with a block theme
- ✅ Work in latest Chrome, Firefox, Safari, and Edge

---

## 📄 License

This plugin is licensed under the **GPL v2 or later**.

```
Copyright (C) 2025 Courtney Robertson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

Full license text: [GPL-2.0](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 🙏 Credits

- **Inspired by:** WordPress [Twenty Thirteen theme](https://wordpress.org/themes/twentythirteen/)'s post format treatments
- **Built with:** WordPress [Gutenberg components](https://developer.wordpress.org/block-editor/reference-guides/components/) and [Block Editor APIs](https://developer.wordpress.org/block-editor/)
- **Icons:** [Dashicons](https://developer.wordpress.org/resource/dashicons/)
- **Developed by:** [Courtney Robertson](https://github.com/courtneyr-dev)

### Special Thanks

- WordPress core team for the block editor
- Gutenberg contributors for accessible components
- WordPress.org plugin review team
- All contributors and testers

---

## 💬 Support

### For Users

- **WordPress.org Support Forums**: [Plugin Support](https://wordpress.org/support/plugin/post-formats-for-block-themes/) _(after approval)_
- **Documentation**: See [readme.txt](readme.txt) for comprehensive user guide
- **FAQ**: Check [Frequently Asked Questions](https://wordpress.org/plugins/post-formats-for-block-themes/#faq) _(after approval)_

### For Developers

- **GitHub Issues**: [Report bugs or request features](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues)
- **GitHub Discussions**: [Ask questions or share ideas](https://github.com/courtneyr-dev/post-formats-for-block-themes/discussions)
- **Technical Docs**: See `/docs/` directory for testing, deployment, and architecture guides

---

## 🚢 Deployment

### Creating a Release

```bash
# Prepare release (runs tests, builds assets, updates versions)
./bin/prepare-release.sh 1.0.0

# Create GitHub release
gh release create v1.0.0 \
  --title "Version 1.0.0 - Initial Release" \
  --notes "See CHANGELOG for details"

# Deployment to WordPress.org happens automatically via GitHub Actions
```

See [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for complete deployment guide.

---

## 📚 Additional Documentation

- **[Testing Guide](docs/TESTING-SUMMARY.md)** - Comprehensive testing documentation
- **[Deployment Guide](docs/DEPLOYMENT.md)** - WordPress.org deployment process
- **[Setup Complete](docs/SETUP-COMPLETE.md)** - Infrastructure overview

---

## 🔗 Links

- **WordPress.org**: https://wordpress.org/plugins/post-formats-for-block-themes/ _(pending approval)_
- **GitHub Repository**: https://github.com/courtneyr-dev/post-formats-for-block-themes
- **Issue Tracker**: https://github.com/courtneyr-dev/post-formats-for-block-themes/issues
- **Sponsor**: https://github.com/sponsors/courtneyr-dev

---

<div align="center">

**Made with ❤️ for the WordPress block theme community**

[![GitHub stars](https://img.shields.io/github/stars/courtneyr-dev/post-formats-for-block-themes?style=social)](https://github.com/courtneyr-dev/post-formats-for-block-themes/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/courtneyr-dev/post-formats-for-block-themes?style=social)](https://github.com/courtneyr-dev/post-formats-for-block-themes/network/members)

</div>
