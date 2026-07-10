---
title: Installation
description: "Install Post Formats for Block Themes from WordPress.org, a ZIP, or GitHub, activate it on a block theme, and confirm the format modal appears."
---

How to install and activate Post Formats for Block Themes, and how to confirm it's working.

## Before you start

- WordPress 6.9 or higher and PHP 7.4 or higher, as of version 1.1.5 (plugin header).
- **Your active theme must be a block theme** (a theme with a `theme.json` and block templates, such as Twenty Twenty-Five). This is a hard requirement — see [what happens if requirements aren't met](#what-happens-if-requirements-arent-met).
- JavaScript enabled in your browser (the editor features are JavaScript-based).

## Install from WordPress.org (recommended)

The plugin is listed in the WordPress.org plugin directory.

1. Log in to wp-admin.
2. Go to **Plugins → Add New**.
3. Search for **Post Formats for Block Themes**.
4. Click **Install Now**, then **Activate**.

## Install from a ZIP file

Use a ZIP from WordPress.org or from the [GitHub releases page](https://github.com/courtneyr-dev/post-formats-for-block-themes/releases).

1. Download the plugin ZIP.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

## Install from GitHub (developers)

Clone the repository into your plugins directory:

```bash
cd wp-content/plugins
git clone https://github.com/courtneyr-dev/post-formats-for-block-themes.git
```

The compiled editor script (`build/index.js`) is included in the repository, so no build step is required before activation. If you plan to modify the editor JavaScript in `src/`, rebuild with `npm install && npm run build`.

Activate from **Plugins** in wp-admin as usual.

## What happens if requirements aren't met

The plugin checks requirements at activation:

- **Classic theme active:** activation stops with the error "Post Formats for Block Themes requires a block theme. Classic themes are not supported." and the plugin deactivates itself. Switch to a block theme, then activate again.
- **WordPress older than 6.9:** activation stops with "Post Formats for Block Themes requires WordPress 6.9 or higher." and the plugin deactivates itself. Update WordPress first.

Both checks show a link back to the plugins screen; nothing else is changed on your site.

## Confirm it's working

1. Go to **Posts → Add New**.
2. The **Choose Post Format** modal should appear, showing 10 format cards (Standard, Aside, Audio, Chat, Gallery, Image, Link, Quote, Status, Video).

![Format selection modal on a new post showing post format cards with icons and descriptions](../../assets/screenshots/editor-format-selection-modal.png)

You should also see **Tools → Post Format Repair** and **Settings → Post Formats** in the wp-admin menu.
