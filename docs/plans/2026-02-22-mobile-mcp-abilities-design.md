# Mobile MCP Posting via WordPress Abilities API

**Date:** 2026-02-22
**Status:** Approved
**Scope:** Post Formats for Block Themes, Post Kinds for IndieWeb, Link Extension for XFN, WP Pinch (community PR)

## Problem

The WordPress mobile app is slow and doesn't support post formats, IndieWeb post kinds, or XFN relationships. Mobile posting should feel as fast as sharing to social media.

## Decision

Use the WordPress Abilities API (6.9+) with the official WordPress MCP adapter. Each plugin registers its own abilities independently. The MCP adapter discovers them all and exposes them to any MCP client, including Claude mobile (iOS/Android).

**Rejected alternatives:**
- Custom MCP server plugin: duplicates adapter functionality, more maintenance
- Micropub MCP server: good idea but separate project, not needed for this scope

## Architecture

```
Claude iOS/Android
        |
        v
WordPress MCP Adapter (remote HTTP + OAuth 2.1)
        |
        v
WordPress 6.9+ Abilities API
        |
        +-- post_formats/*  (Post Formats for Block Themes - already done)
        +-- post_kinds/*    (Post Kinds for IndieWeb - new)
        +-- xfn/*           (Link Extension for XFN - new)
```

## Plugin 1: Post Formats for Block Themes (existing)

Already has 15 abilities across 3 providers. No changes needed.

**Core abilities (6):** list_formats, get_format_template, validate_format, set_post_format, get_post_format, detect_format
**MCP abilities (4):** suggest_format, analyze_content, validate_format_content, get_format_signals
**IndieWeb abilities (5):** mf2_markup, mf2_validate, posse_prepare, posse_targets, webmention_context

## Plugin 2: Post Kinds for IndieWeb (new integration)

### New files

- `includes/class-pkiw-abilities-manager.php` - orchestrator, registers category + loads providers
- `includes/abilities/class-pkiw-core-abilities.php` - CRUD abilities for all 25 kinds
- `includes/abilities/class-pkiw-lookup-abilities.php` - wraps existing REST lookup endpoints
- `includes/class-pkiw-feature-flags.php` - feature flag system

### Ability category: `post-kinds`

### Core abilities (7)

| Ability | Input | Output | Permission |
|---|---|---|---|
| `post_kinds/set_kind` | post_id, kind_slug | success, kind | edit_posts |
| `post_kinds/get_kind` | post_id | kind_slug, kind_label, kind_description | read |
| `post_kinds/list_kinds` | -- | array of 25 kinds with slugs, labels, descriptions | read |
| `post_kinds/create_post` | kind, title, content, kind-specific meta | post_id, edit_url, view_url | edit_posts |
| `post_kinds/update_post_meta` | post_id, meta_key, meta_value | success | edit_posts |
| `post_kinds/get_post_meta` | post_id, meta_keys (optional) | key-value pairs | read |
| `post_kinds/list_kind_fields` | kind_slug | array of meta field names, types, descriptions | read |

### Lookup abilities (6)

| Ability | Wraps endpoint |
|---|---|
| `post_kinds/lookup_music` | /lookup/music |
| `post_kinds/lookup_video` | /lookup/video |
| `post_kinds/lookup_book` | /lookup/book |
| `post_kinds/lookup_podcast` | /lookup/podcast |
| `post_kinds/lookup_venue` | /lookup/venue |
| `post_kinds/lookup_game` | /lookup/game |

### create_post behavior

Accepts a `kind` slug plus kind-specific meta fields. Creates a post, assigns the kind taxonomy term, sets all meta in one call. The `list_kind_fields` ability tells MCP clients which fields each kind accepts.

### All 25 kinds supported

note, article, reply, like, repost, bookmark, rsvp, checkin, listen, watch, read, event, photo, video, review, favorite, jam, wish, mood, acquisition, drink, eat, recipe, play

## Plugin 3: Link Extension for XFN (new integration)

### New files

- `includes/class-xfn-abilities-manager.php` - orchestrator
- `includes/abilities/class-xfn-core-abilities.php` - XFN abilities
- `includes/class-xfn-meta-mirror.php` - post meta storage + sync to block HTML

### Modified files

- `link-extension-for-xfn.php` - load classes, register meta, hook save logic

### Post meta mirror

XFN data lives in HTML `rel` attributes on links inside block content. MCP clients can't manipulate HTML directly. Solution: a post meta mirror.

New meta field `_xfn_relationships` stores structured data:

```php
[
    ['url' => 'https://example.com/alice', 'rels' => ['friend', 'met']],
    ['url' => 'https://example.com/bob',   'rels' => ['acquaintance']],
]
```

**Sync rules:**
- On post save: iterate meta, find matching `<a>` tags in post_content, set/update rel attributes. Preserves non-XFN rels (nofollow, noopener).
- On block editor save: editor UI writes win. Meta mirror syncs to match block attributes.
- Conflict resolution: a `_xfn_meta_source` flag tracks last writer. Block editor writes always take precedence.

### Meta registration

```php
register_post_meta('post', '_xfn_relationships', [
    'type'         => 'array',
    'single'       => true,
    'show_in_rest' => [
        'schema' => [
            'type'  => 'array',
            'items' => [
                'type'       => 'object',
                'properties' => [
                    'url'  => ['type' => 'string', 'format' => 'uri'],
                    'rels' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        ],
    ],
    'auth_callback'     => function() { return current_user_can('edit_posts'); },
    'sanitize_callback' => [XFN_Meta_Mirror::class, 'sanitize_relationships'],
]);
```

### Ability category: `xfn-relationships`

### Abilities (5)

| Ability | Input | Output | Permission |
|---|---|---|---|
| `xfn/set_relationships` | post_id, relationships array | success, applied count | edit_posts |
| `xfn/get_relationships` | post_id | array of {url, rels} | read |
| `xfn/add_relationship` | post_id, url, rels array | success | edit_posts |
| `xfn/remove_relationship` | post_id, url | success | edit_posts |
| `xfn/validate_relationships` | rels array | valid, warnings | read |

### XFN exclusivity validation

- friendship: one of contact/acquaintance/friend
- geographical: one of co-resident/neighbor
- family: one of child/parent/sibling/spouse/kin

## WP Pinch Community PR

Add `format` parameter to `create-post` and `update-post` abilities:

```php
'format' => [
    'type'        => 'string',
    'description' => 'Post format',
    'enum'        => ['standard','aside','chat','gallery','link','image','quote','status','video','audio'],
    'default'     => 'standard',
]
```

Execute callbacks call `set_post_format($post_id, $format)`. Output of `get-post` includes `get_post_format($post_id)`.

## Deployment

1. WordPress 6.9+ with Abilities API
2. WordPress MCP adapter configured for remote HTTP + OAuth 2.1
3. All three plugins installed with abilities feature flags enabled
4. MCP adapter URL added as custom connector at claude.ai (Settings > Connectors)
5. Claude mobile app syncs the connector automatically

## Out of scope

- No custom MCP server code
- No Micropub integration
- No changes to existing block editor UIs
- No mobile app development
- No coordinator/glue plugin
