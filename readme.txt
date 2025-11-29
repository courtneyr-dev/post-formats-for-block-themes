=== Post Formats Power-Up ===
Contributors: courane01
Tags: post-formats, gutenberg, block-theme, patterns, accessibility
Requires at least: 6.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modernize WordPress post formats for block themes with format-specific patterns, automatic detection, and an enhanced editor experience.

== Description ==

**Post Formats Power-Up** brings modern post format functionality to block themes with intelligent pattern insertion, automatic format detection, and a streamlined editing experience.

= Key Features =

**Format-Specific Block Patterns**
Choose from 10 professionally-designed patterns tailored to each post format: Aside, Audio, Chat, Gallery, Image, Link, Quote, Status, Video, and Standard. Each pattern includes locked first blocks to maintain format consistency while allowing full creative freedom for additional content.

**Automatic Format Detection**
The plugin intelligently detects your post format based on content structure. Add a gallery block and it automatically sets the Gallery format. Insert a quote block and it becomes a Quote post. Smart detection works on save while respecting manual format selections.

**Enhanced Editor Experience**
* **Format Selection Modal** – Choose your format when creating new posts with visual cards showing each format's purpose
* **Format Switcher Panel** – Change formats mid-edit with smart content preservation options
* **Status Validation** – Twitter-style 280-character limit with real-time character counter for Status format
* **Repair Tool** – Admin interface to scan and fix format mismatches across all posts

**Built for Accessibility**
All features meet WCAG 2.2 AA standards with keyboard navigation, screen reader support, and semantic HTML throughout.

**Theme-Agnostic Styling**
Format styles use CSS custom properties from your theme.json, ensuring perfect integration with any block theme's color palette and typography.

**Block Theme Requirement**
This plugin requires WordPress 6.8+ and a block theme. Classic themes are not supported.

= Supported Post Formats =

1. **Standard** – Traditional blog post with full title and content
2. **Aside** – Short note or update in a styled bubble without title
3. **Status** – Twitter-style status update (280 characters, no title)
4. **Link** – Link sharing with Bookmark Card plugin integration
5. **Gallery** – Photo gallery starting with gallery block
6. **Image** – Single image post with prominent image display
7. **Quote** – Quotation or citation with enhanced quote styling
8. **Video** – Video content with native or embed blocks
9. **Audio** – Audio file or podcast embed
10. **Chat** – Conversation transcript (requires Chat Log plugin)

= Format Auto-Detection =

The plugin automatically detects and applies the correct format based on your first block:
* Gallery block → Gallery format
* Image block → Image format
* Video block → Video format
* Audio block → Audio format
* Quote block → Quote format
* Bookmark Card block → Link format
* Chat Log block → Chat format
* Group with "aside-bubble" class → Aside format
* Paragraph with "status-paragraph" class → Status format
* Everything else → Standard format

= Developer-Friendly =

Extensive hooks and filters for customization:
* `pfpu_registered_formats` – Modify or add format definitions
* `pfpu_detected_format` – Filter auto-detected formats
* `pfpu_format_detected` – Action after format detection
* `pfpu_format_repaired` – Action after repair tool fixes format

= Translation Ready =

Fully internationalized with complete translation support. Includes POT file for translators. RTL language support included.

== Installation ==

= Minimum Requirements =

* WordPress 6.8 or higher
* PHP 7.4 or higher
* A block theme (Classic themes not supported)

= Automatic Installation =

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins → Add New
3. Search for "Post Formats Power-Up"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin

= After Activation =

1. Create a new post – the format selection modal will appear
2. Choose your desired format and start creating
3. Visit Tools → Post Format Repair to scan existing posts

== Frequently Asked Questions ==

= Does this work with classic themes? =

No, this plugin is specifically designed for block themes and requires WordPress 6.8 or higher. Classic themes use different templating systems that aren't compatible with this plugin's approach.

= Will this work with my existing posts? =

Yes! Use the Repair Tool (Tools → Post Format Repair) to scan your existing posts and automatically detect appropriate formats based on their content structure.

= What happens if I deactivate the plugin? =

Post format assignments remain in your database and will continue to work if your theme supports post formats. Pattern-inserted blocks remain as standard WordPress blocks. No data is lost.

= Can I customize the format patterns? =

Yes! Patterns are regular WordPress block patterns registered in the 'theme' category. You can override them by registering your own patterns with the same names in your theme.

= Does the Status format prevent publishing over 280 characters? =

No, the 280-character limit is a soft suggestion, not a hard block. You'll see warnings but can still publish longer status updates if needed.

= What is the Link format Bookmark Card integration? =

If you have the Bookmark Card plugin installed, Link format patterns use rich link preview cards. Without it, a simple linked paragraph is used instead. Both options work perfectly.

= How do I use the Chat format? =

The Chat format requires the Chat Log plugin. Install and activate that plugin, then the Chat format will use its conversation block. Without it, you'll see a "missing block" notice.

= Is this plugin accessible? =

Yes! All features meet WCAG 2.2 AA standards including keyboard navigation, screen reader support, sufficient color contrast, and semantic HTML. Tested with NVDA, JAWS, and VoiceOver.

= Can I change formats after creating a post? =

Absolutely! Use the Format Switcher in the post sidebar. If your post has content, you'll be asked whether to replace it with the new format's pattern or keep your existing content.

= How does auto-detection know when NOT to change my format? =

Auto-detection only runs on posts where you haven't explicitly chosen a format. Once you select a format manually (via the modal or switcher), auto-detection respects your choice and won't override it.

== Screenshots ==

1. Format selection modal on new post creation with all 10 formats
2. Status format with real-time character counter
3. Format switcher sidebar panel
4. Repair tool admin interface showing detected mismatches
5. Aside format bubble styling in Twenty Twenty-Five theme
6. Gallery format pattern with locked gallery block

== Changelog ==

= 1.0.0 - 2025-01-XX =
* Initial release
* Format selection modal for new posts
* 10 format-specific block patterns with locked first blocks
* Automatic format detection on save
* Format switcher sidebar panel with content preservation
* Status format 280-character validation and counter
* Repair tool for scanning and fixing format mismatches
* Theme-agnostic styling with CSS custom properties
* Full WCAG 2.2 AA accessibility compliance
* Complete internationalization support
* Bookmark Card plugin integration for Link format
* Chat Log plugin integration for Chat format
* RTL language support
* Extensive developer hooks and filters

== Upgrade Notice ==

= 1.0.0 =
Initial release. Requires WordPress 6.8+ and a block theme.

== Additional Information ==

= Integrations =

**Bookmark Card Plugin**
Link format automatically detects and uses Bookmark Card blocks when the plugin is active. Gracefully falls back to standard linked paragraphs when not installed.

**Chat Log Plugin**
Chat format uses the Chat Log block (chatlog/conversation) for conversation transcripts. The Chat format requires this plugin to function properly.

= Performance =

Post Formats Power-Up is built for performance:
* JavaScript only loads in the post editor, never on the frontend
* CSS is minimal and uses native CSS custom properties
* Auto-detection runs only on post save, not on every page load
* No database queries on frontend display
* All assets are properly enqueued and versioned for caching

= Privacy =

This plugin does not:
* Collect or store any user data
* Make external API calls
* Set cookies
* Track users
* Share data with third parties

Format selections and post meta are stored only in your WordPress database.

= Support =

For support, please use the WordPress.org support forums or visit our documentation at https://example.com/post-formats-power-up/docs/

= Contributing =

Contributions are welcome! Visit our GitHub repository at https://github.com/yourname/post-formats-power-up

== Credits ==

* Inspired by WordPress Twenty Thirteen theme's post format treatments
* Built with WordPress Gutenberg components for accessibility
* Uses Dashicons for format icons
