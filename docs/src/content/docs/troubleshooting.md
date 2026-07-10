---
title: Troubleshooting
description: "Fixes for common problems: the classic-theme activation stop, missing format modal, unstyled variations, and detection surprises."
---

Symptoms, likely causes, and fixes for the problems users actually hit, based on the plugin's changelog and support docs.

## The plugin won't activate, or deactivated itself

**Symptom:** activation stops with "Post Formats for Block Themes requires a block theme. Classic themes are not supported." (or the WordPress 6.9 version message), and the plugin shows as deactivated.

**Cause:** the plugin checks requirements at activation. A classic theme (or WordPress older than 6.9) fails the check, and the plugin deactivates itself rather than run partially.

**Fix:**

1. Switch to a block theme (Appearance → Themes; Twenty Twenty-Five is a good default), or update WordPress to 6.9+.
2. Activate the plugin again.

**Check next:** confirm your theme is really a block theme — it should offer **Appearance → Editor** (the Site Editor), not Appearance → Customize alone.

## Quote or Video posts show "unexpected or invalid content"

**Symptom:** a Quote or Video format post shows a block validation error in the editor.

**Cause:** a bug in versions before 1.1.5 — the Quote and Video patterns failed core block validation.

**Fix:** update the plugin to 1.1.5 or later, then open the post and use "Attempt Block Recovery" if the editor offers it.

## Style variations look unstyled on the site

**Symptom:** you enabled the opt-in block style variations, picked one in the editor, and the frontend shows no styling.

**Cause:** in versions before 1.1.5, the variation CSS was never enqueued on the frontend.

**Fix:** update to 1.1.5 or later. Remember the 52 variations are off by default and are enabled with a feature flag (code), not a settings screen — see [Settings](/post-formats-for-block-themes/settings/).

## The format modal is stuck behind or on top of the welcome guide

**Symptom:** on a fresh site, the format selection modal and the editor's welcome guide overlap.

**Cause:** fixed in 1.1.5 — the modal now waits for the welcome guide.

**Fix:** update the plugin. As a workaround, close the welcome guide; the modal is reachable underneath.

## The Format Badge doesn't appear before post titles

**Symptom:** no badge shows on posts even though the Format Badge block is hooked before the post title.

**Cause:** in versions before 1.1.5 the hooked block didn't render.

**Fix:** update to 1.1.5 or later. If you still don't see it, check whether the badge block was removed from the template in the Site Editor.

## A post's format doesn't match its content

**Symptom:** posts labeled with one format (say Chat) whose content is something else, often after imports or theme migrations.

**Fix:** run **Tools → Post Format Repair**. Scan, review the suggested formats, preview with the dry run, then apply per post or in bulk. A revision is saved per post before changes.

## Auto-detect isn't changing my post's format

**Symptom:** you changed a post's first block, saved, and the format stayed the same.

**Cause:** this is by design. Detection applies once, on the first save from content, and never overrides a manual choice. It won't keep re-deciding as you edit.

**Fix:** set the format yourself in the Format Switcher panel, or use the Repair Tool for many posts at once.

## The Chat Log block doesn't parse my transcript

**Symptom:** pasted chat text renders as one blob or attributes messages to the wrong speaker.

**Cause:** the paste doesn't match a supported pattern.

**Fix:** compare your paste with the working patterns in the [chat log format examples](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/.wordpress-org/CHAT-LOG-FORMAT-EXAMPLES.md) — for example, the Slack/Discord pattern requires two spaces between name and time (`sarah  9:30 AM`). Set the **Platform** control explicitly instead of Auto-detect if needed.

## When to open an issue

If none of the above fits:

1. Read [SUPPORT.md](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/SUPPORT.md) for first steps and what to include.
2. Update to the latest plugin version and retest — many past problems (see above) are version-specific.
3. Open an issue at [github.com/courtneyr-dev/post-formats-for-block-themes/issues](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues) with your WordPress version, theme, plugin version, and steps to reproduce.
