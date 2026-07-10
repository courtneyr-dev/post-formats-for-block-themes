# Privacy and data

What the plugin stores, what it sends, and what it adds to your pages, based on a code audit of version 2.4.0.

## External requests: none found

A search across the plugin's PHP and JavaScript (`includes/`, `blocks/`, `src/`) for outbound HTTP mechanisms (`wp_remote_*`, `curl_*`, `fsockopen`, `file_get_contents` with URLs, `WP_Http`) found no external network calls. The plugin does not phone home, load remote assets, or send content to third-party services.

The IndieWeb, ActivityPub, and POSSE modules transform content for *other* plugins to send; they hook into those host plugins rather than making requests themselves. If you use those companion plugins, their own privacy behavior applies. (A line-by-line audit of every transformer when host plugins are active is listed under maintainer review.)

## Options stored (site-wide, in wp_options)

- `pfbt_version` — installed plugin version.
- `pfbt_activated_time` — activation timestamp.
- `pfbt_icon_set` — chosen icon set.
- `pfbt_use_block_templates` — the block templates opt-in.
- `pfbt_feature_{flag}` — feature flag values, when set.

## Post meta stored (per post, prefixed `_pfbt_`)

- `_pfbt_link_url` — the link-format URL (exposed via the REST API with sanitization and permission checks).
- `_pfbt_format_manual` — records that a format was chosen manually, so auto-detection won't override it.
- `_pfbt_format_repaired` — records a Repair Tool fix.
- Format-detector keys recording the detected and applied format.
- `_pfbt_manual_template` / `_pfbt_manual_template_slug` — manual template choices.

None of this is visitor data; it describes the post itself.

## Transients

`pfbt_bookmark_card_available`, `pfbt_chatlog_block_available`, and `pfbt_patterns_registered` cache availability checks. All are deleted on deactivation.

## User data

The plugin stores no user meta and collects nothing about site visitors. It sets no cookies of its own and adds no analytics.

**Chat Log content is user-entered only.** When you paste a transcript, the names, avatars/initials, timestamps, and messages you publish come from what you pasted — the block fetches nothing from Slack, Discord, or any other platform. Treat transcripts like any other content you publish: remove anything the participants wouldn't want public.

## Frontend markup

- CSS classes on the body and posts: `.pfbt-format-{slug}`, `.pfbt-format-titleless`, alongside core's `.format-{slug}`.
- One structural stylesheet (`format-tokens.css`), removable with the `pfbt_enqueue_format_styles` filter.
- Optional Format Badge / Format Icon output where those blocks are used.

## Other WordPress data it touches

- Assigns terms in core's `post_format` taxonomy (that's what a post format is). With Post Kinds for IndieWeb active, it can also assign a matching `kind` term.
- Creates a revision before each Repair Tool change.
- Limits revisions for reusable blocks (`wp_block`) to 3.
- Does not modify comments, links, or users.

## What couldn't be fully verified

- Outbound behavior of IndieWeb/ActivityPub/POSSE transformers when their host plugins are active (none found in this plugin's code; flagged for maintainer confirmation).
- An exhaustive search for custom REST routes (none were found; the plugin registers meta and filters template REST responses).

---

[Documentation home](index.md) · Previous: [FAQ](faq.md) · Next: [Accessibility](accessibility.md)
