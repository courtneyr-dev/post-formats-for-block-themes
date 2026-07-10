# FAQ

Answers to the questions users ask most about Post Formats for Block Themes.

## Does it work with classic themes?

No. A block theme is required, and the plugin deactivates itself at activation on a classic theme. Classic themes use PHP templates that are incompatible with the plugin's block patterns and templates. The readme suggests migrating to a modern block theme such as Twenty Twenty-Five.

## How many formats do I get?

Ten choices in the editor: the nine formats the plugin registers (aside, gallery, link, image, quote, status, video, audio, chat) plus WordPress's default standard format. If your theme already declares formats, they're merged in, not overwritten.

## Will it change my existing posts?

No. Existing posts keep their formats and content. The format modal only appears on new posts. If you want to bring old posts in line, the Repair Tool (Tools → Post Format Repair) suggests fixes and previews them with a dry run before applying anything, creating a revision per post.

## Why did my post get a format I didn't pick?

Auto-detection assigned one from your content on first save. It applies once and never overrides a manual choice — pick the format you want in the Format Switcher and it sticks.

## Why is the first block in a format pattern locked?

To preserve the format's structure — a quote post keeps its pullquote, a gallery post keeps its gallery. You edit the content inside the locked block and add anything you like after it.

## Which chat platforms does the Chat Log block understand?

Slack, Discord, Microsoft Teams, Telegram, WhatsApp, and Signal transcripts, with auto-detection of the platform. Display styles: bubbles, IRC, transcript, and timeline. Working paste patterns are documented in the [chat log format examples](../.wordpress-org/CHAT-LOG-FORMAT-EXAMPLES.md).

## Can I restyle a format without code?

Yes. Format templates and styles are editable in the Site Editor like any other template, and Global Styles applies. See the [Site Editor guide](../SITE-EDITOR-GUIDE.md). The plugin deliberately ships structure, not design — your theme's look wins by default.

## What are the "52 block style variations" and how do I turn them on?

Opt-in style variations for image (16), gallery (20), and quote/pullquote (16) blocks. They're off by default and there's no settings toggle — enabling them requires a feature flag set in code (filter, option, or constant), so plan on developer help. See [Settings](settings.md) and [Block style variations](BLOCK-STYLE-VARIATIONS.md).

## Does it work with other plugins?

Optional integrations activate when the other plugin is installed: Bookmark Card (richer link-format previews), Able Player (accessible audio/video playback), Podlove (podcasting), and Post Kinds for IndieWeb (maps formats to post kinds, such as Video → Watch). All are optional with fallbacks to core blocks.

## Does it slow down or restyle my whole site?

The plugin enqueues one frontend stylesheet of structural tokens, wrapped in a CSS layer so theme styles take precedence, and adds format-related CSS classes to the body and posts. Site owners can remove even that stylesheet with a filter (`pfbt_enqueue_format_styles`).

## What happens if I deactivate or uninstall it?

Your posts and their formats remain — formats are stored in core WordPress taxonomy. The plugin's editor tooling, patterns, blocks, and templates stop being available; posts using the Chat Log or format blocks will show them as missing blocks in the editor. On deactivation the plugin cleans up its transients.

---

[Documentation home](index.md) · Previous: [Troubleshooting](troubleshooting.md) · Next: [Privacy and data](privacy-and-data.md)
