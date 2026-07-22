---
title: Common tasks
description: "Step-by-step instructions for everyday format work: quotes, chat transcripts, status posts, switching formats, and repairing mismatched posts."
---

Step-by-step instructions for the everyday things you'll do with Post Formats for Block Themes.

## Choose a format for a new post

1. Go to **Posts → Add New**.
2. In the **Choose Post Format** modal, pick one of the 10 formats. Its pattern is inserted with a locked first block.
3. To skip formats entirely, choose **Standard** or close the modal.

![Format selection modal on a new post showing post format cards with icons and descriptions](../../assets/screenshots/editor-format-selection-modal.png)

## Change a post's format

1. Open the post in the editor.
2. In the post sidebar, find the **Format Switcher** panel.
3. Pick the new format from the dropdown, and choose whether to replace your content with the new format's pattern or keep it.

## Publish a chat transcript

1. Create a new post and choose the **Chat** format (or insert the **Chat Log** block into any post).
2. Paste your transcript into the block. Slack, Discord, Teams, Telegram, WhatsApp, and Signal exports are supported; the block auto-detects the platform and shows what it detected (for example "Detected: WhatsApp").
3. Adjust display settings in the block sidebar: platform, display style, avatars, timestamps and their format, thread collapsing, and participant list.
4. Publish. The transcript renders as an accessible, threaded conversation.

![Chat Log block in the editor with a pasted transcript, detected platform, and display settings in the sidebar](../../assets/screenshots/editor-chat-log.png)

![Published chat log on the frontend showing avatars, usernames, timestamps, and bubble-style messages](../../assets/screenshots/frontend-chat-log.png)

If a paste doesn't parse, check the expected line patterns in the [chat log format examples](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/.wordpress-org/CHAT-LOG-FORMAT-EXAMPLES.md) — for example, Slack-style pastes need two spaces between the name and the time (`sarah  9:30 AM`).

## Change how a chat log is displayed

1. Select the Chat Log block.
2. In the block sidebar, change **Display style**: bubbles (default), IRC, transcript, or timeline.
3. Toggle avatars, timestamps, and the participant list to taste.

## Post a short status update

1. Create a new post and choose the **Status** format.
2. Type your update. A live counter shows how many of the 280 characters remain, social-media style, and warns when you go over the limit.

![Status format post in the editor showing the remaining-characters counter below the text](../../assets/screenshots/editor-status-format.png)

## Display a post's format on your site

Three blocks show format information; add them in the Site Editor (to templates) or in individual posts:

- **Format Badge** — shows the format as a badge. It's automatically inserted before the post title via Block Hooks, so you may already have it; you can remove or move it in the Site Editor.
- **Format Icon** — renders the format's SVG icon. By default the format name is available to screen readers only; enable **Show label** for visible text. The block hides itself on standard-format posts.
- **Post Format** — shows the format as a text link to the format archive, with alignment and separator options.

![Format badge displayed before a post title on the frontend](../../assets/screenshots/frontend-format-badge.png)

## Change the format icon style

1. Go to **Settings → Post Formats**.
2. Under **Icon set**, choose **Hand-drawn (default)** or **Filled silhouettes**.
3. Save. The change is instant; no posts need re-saving.

## Fix posts whose format doesn't match their content

1. Go to **Tools → Post Format Repair**.
2. Review the scan results and the detected mismatches.
3. Leave **Dry run** checked to preview, then uncheck it and click **Apply All Suggestions** — or use the per-post **Apply** buttons. A revision is created for each post before changes are applied.

![Post Format Repair tool showing scan results with all posts correctly formatted and no mismatches](../../assets/screenshots/admin-repair-tool.png)

## Customize a format's appearance

Use the Site Editor — no code needed. Format templates and styles are editable like any other template. Follow the [Site Editor guide](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/SITE-EDITOR-GUIDE.md) or the [format customization quick start](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/FORMAT-CUSTOMIZATION-QUICK-START.md).

## Enable the plugin's block templates

If your theme has no format-specific templates:

1. Go to **Tools → Post Format Templates**.
2. Check **"Use the plugin's opt-in single + archive templates per post format."** and save.
3. Single posts and post-format archives now use the plugin's 18 starter templates, which you can customize in the Site Editor.

![Post Format Templates page with the opt-in checkbox and the list of single and archive templates](../../assets/screenshots/admin-block-templates.png)

## Confirm auto-detection applied a format

1. Create a new post, skip the modal, and add a distinctive first block (for example, a pullquote).
2. Save the post.
3. Check the Format Switcher or the post sidebar: the detected format (Quote, in this example) is applied. It's applied once — later edits won't change it, and it never overrides a format you set manually.
