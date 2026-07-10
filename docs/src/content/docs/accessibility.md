---
title: Accessibility
description: "The accessibility behavior built into the plugin's UI, the testing evidence in the repository, and what still needs verification."
---

The accessibility behavior built into the plugin's UI, the evidence in the repository, and what still needs testing.

The plugin's readme describes a WCAG 2.2 AA design goal. This page doesn't repeat that as a compliance claim; it describes what the code and tests show.

## Editor

- **Format selection modal** is built on the `@wordpress/components` Modal, which provides keyboard navigation and focus management. The editor script uses `wp.a11y.speak()` to announce changes to screen readers.
- **Status format counter** gives real-time character-count validation with accessible announcements, not just a visual number.
- **Format Icon block** renders the format name as screen-reader-only text by default; a `showLabel` setting makes it visible. The block removes itself on standard-format posts instead of rendering an empty icon.

## Admin screens

The Settings → Post Formats page groups the icon set choice in a `<fieldset>` with a `<legend>`, marks the decorative icon previews `aria-hidden`, and includes `prefers-reduced-motion` and `prefers-contrast` handling in its styles.

## Frontend

- The Chat Log block's stated design is "accessible, threaded formatting" for transcripts; avatars and metadata are rendered as real text and markup, not images of text.
- Media integrations were chosen with playback accessibility in mind: Able Player (an accessible media player) is supported for video and audio formats when installed.
- RTL (right-to-left) language support is included, with RTL stylesheets in the Chat Log build.

## Testing that exists in the repository

- A dedicated automated accessibility suite: `tests/accessibility/modal-wcag.spec.js`, which runs axe (via `@axe-core/playwright`) against the format modal. The npm script `test:a11y` runs it.
- End-to-end tests run across Chromium, Firefox, WebKit, and mobile viewports, which exercises the UI beyond a single browser.

## What still needs testing

- Automated coverage currently targets the modal; the Format Switcher panel, settings pages, Repair Tool, and frontend block output don't have their own axe suites in the repository.
- No recorded manual screen reader (NVDA/JAWS/VoiceOver) or keyboard-only test results are in the repository.
- Color contrast of the opt-in style variations across arbitrary theme palettes isn't covered by the automated tests.

If you rely on assistive technology and hit a problem, please report it — see [SUPPORT.md](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/SUPPORT.md) and the [issues page](https://github.com/courtneyr-dev/post-formats-for-block-themes/issues).
