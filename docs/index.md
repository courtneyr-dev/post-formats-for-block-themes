# Post Formats for Block Themes documentation

User documentation for the Post Formats for Block Themes WordPress plugin, version 2.4.0 (plugin header). Start here to find every guide.

## What the plugin does

Post Formats for Block Themes brings WordPress post formats to block themes. A post format is a label you give a post — quote, video, status, chat, and so on — that tells your theme to present that post differently from a regular article. WordPress has supported formats for years, but block themes lost most of the editor tooling around them. This plugin restores and modernizes it:

- A format selection modal when you create a new post, showing all 10 format choices with icons and descriptions.
- A Format Switcher panel in the editor sidebar for changing a post's format mid-edit.
- Format-specific block patterns that insert a locked first block so each format keeps its structure.
- Automatic format detection that suggests a format from your content — applied once, and never overriding a choice you made yourself.
- A Chat Log block for publishing conversation transcripts from Slack, Discord, Teams, Telegram, WhatsApp, or Signal.
- Format Badge, Format Icon, and Post Format blocks for showing a post's format on your site.
- A repair tool at Tools → Post Format Repair that finds and fixes posts whose format doesn't match their content.

## Who it's for

Bloggers and content creators who publish mixed content types (quotes, links, photos, status updates, chat transcripts), site owners migrating from classic themes, and anyone who wants varied post presentation on a block theme without writing code.

## Requirements

- WordPress 6.9 or higher and PHP 7.4 or higher, as of version 2.4.0 (plugin header).
- **A block theme is required.** If your active theme is a classic theme, the plugin refuses to activate: activation stops with an error screen and the plugin deactivates itself. See [Troubleshooting](troubleshooting.md).
- No other plugins are required. Optional integrations light up when their plugins are installed: Bookmark Card (link previews), Able Player (accessible video/audio), Podlove (podcasting), and Post Kinds for IndieWeb.

## Read first

1. [Installation](installation.md) — install from WordPress.org, a ZIP, or GitHub, and confirm it works.
2. [Getting started](getting-started.md) — create your first formatted post.
3. [Settings](settings.md) — icon set, repair tool, and the block templates opt-in.

## All pages

- [Installation](installation.md)
- [Getting started](getting-started.md)
- [Settings](settings.md)
- [Common tasks](common-tasks.md)
- [Screenshots](screenshots.md)
- [Playground preview](playground.md)
- [Troubleshooting](troubleshooting.md)
- [FAQ](faq.md)
- [Privacy and data](privacy-and-data.md)
- [Accessibility](accessibility.md)
- [Documentation plan](documentation-plan.md)

## Detailed guides in this repository

These existing guides go deeper on customization; the pages above link to them rather than repeat them:

- [Format customization quick start](../FORMAT-CUSTOMIZATION-QUICK-START.md) — a 3-minute guide to customizing post formats.
- [Site Editor guide](../SITE-EDITOR-GUIDE.md) — customizing each format's templates and styles through the Site Editor, no code required.
- [Styling summary](../STYLING-SUMMARY.md) — how the plugin's styling and Site Editor integration fit together.
- [Support](../SUPPORT.md) — where to get help and what to include in a report.

## For developers

Developer and theme-author documentation lives alongside these pages:

- [Hooks reference](HOOKS-REFERENCE.md) — every filter and action.
- [Theme integration](THEME-INTEGRATION.md) — the theme "paint" vs plugin "layout" contract.
- [Design tokens](DESIGN-TOKENS.md) — the `format-tokens.css` token reference.
- [Block style variations](BLOCK-STYLE-VARIATIONS.md) — catalog of the 52 opt-in style variations and their token contract.
- [Adding a variation](ADDING-A-VARIATION.md) — how to add an image or gallery style variation.

---

[Documentation home](index.md) · Next: [Installation](installation.md)
