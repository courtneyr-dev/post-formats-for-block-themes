---
title: Post Formats for Block Themes
description: "User documentation for Post Formats for Block Themes: bring quote, status, chat, and other post formats to WordPress block themes."
---

Post Formats for Block Themes brings WordPress post formats to block themes. These docs help you install the plugin, create formatted posts, and tune how formats look on your site.

## What the plugin does

A post format is a label you give a post — quote, video, status, chat, and so on — that tells your theme to present that post differently from a regular article. WordPress has supported formats for years, but block themes lost most of the editor tooling around them. This plugin restores and modernizes it:

- A format selection modal when you create a new post, showing all 10 format choices with icons and descriptions.
- A Format Switcher panel in the editor sidebar for changing a post's format mid-edit.
- Format-specific block patterns that insert a locked first block so each format keeps its structure.
- Automatic format detection that suggests a format from your content — applied once, and never overriding a choice you made yourself.
- A Chat Log block for publishing conversation transcripts from Slack, Discord, Teams, Telegram, WhatsApp, or Signal.
- Format Badge, Format Icon, and Post Format blocks for showing a post's format on your site.
- A repair tool at **Tools → Post Format Repair** that finds and fixes posts whose format doesn't match their content.

## Who it's for

Bloggers and content creators who publish mixed content types (quotes, links, photos, status updates, chat transcripts), site owners migrating from classic themes, and anyone who wants varied post presentation on a block theme without writing code.

## Before you install

- WordPress 6.9 or higher and PHP 7.4 or higher.
- **A block theme is required.** If your active theme is a classic theme, activation stops with an error screen and the plugin deactivates itself. See [Troubleshooting](/post-formats-for-block-themes/troubleshooting/).
- No other plugins are required. Optional integrations light up when their plugins are installed: Bookmark Card (link previews), Able Player (accessible video/audio), Podlove (podcasting), and Post Kinds for IndieWeb.

## Get the plugin

Post Formats for Block Themes is published in the [WordPress.org plugin directory](https://wordpress.org/plugins/post-formats-for-block-themes/) — install it from **Plugins → Add New Plugin** in wp-admin. [Installation](/post-formats-for-block-themes/installation/) also covers ZIP and GitHub installs, and [Playground preview](/post-formats-for-block-themes/playground/) lets you try it in your browser first.

## Get started

1. [Installation](/post-formats-for-block-themes/installation/) — install and confirm it works.
2. [Getting started](/post-formats-for-block-themes/getting-started/) — create your first formatted post.
3. [Settings](/post-formats-for-block-themes/settings/) — icon set, repair tool, and the block templates opt-in.

## Get help

- [Troubleshooting](/post-formats-for-block-themes/troubleshooting/) — symptoms, causes, and fixes.
- [FAQ](/post-formats-for-block-themes/faq/) — quick answers to common questions.
- [Report an issue](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues) on GitHub.

## For theme developers

The **For theme developers** section in the sidebar documents the plugin's styling contract: the [hooks reference](/post-formats-for-block-themes/hooks-reference/), [theme integration guide](/post-formats-for-block-themes/theme-integration/), [design tokens](/post-formats-for-block-themes/design-tokens/), the [block style variations catalog](/post-formats-for-block-themes/block-style-variations/), and [how to add a variation](/post-formats-for-block-themes/adding-a-variation/).

## Source code

The plugin is developed in the open at [github.com/courtneyr-dev/post-formats-for-block-themes](https://github.com/courtneyr-dev/post-formats-for-block-themes).
