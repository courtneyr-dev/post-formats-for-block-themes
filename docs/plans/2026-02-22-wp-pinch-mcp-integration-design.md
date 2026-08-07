# WP Pinch MCP Integration Design

**Goal:** Make Post Kinds (13) and XFN (5) abilities discoverable by OpenClaw through WP Pinch's MCP server endpoint.

**Architecture:** Each plugin hooks into WP Pinch's documented extension points to inject its ability names into the MCP server's tool list. No changes to existing ability registration. Abilities continue to work independently without WP Pinch.

**Tech Stack:** WordPress Abilities API, WP Pinch filter hooks

---

## Problem

Post Kinds and XFN plugins register abilities with WordPress's Abilities API on `wp_abilities_api_init`. These abilities exist in WordPress but are invisible to OpenClaw because WP Pinch's custom MCP server only exposes abilities in its hardcoded name list.

WP Pinch provides two extension hooks:

1. `wp_pinch_mcp_server_abilities` (filter) — adds ability names to the custom MCP server
2. `wp_register_ability_args` (filter) — modifies ability registration args (used to set `meta.mcp.public = true`)

## Approach

Each plugin adds a single static method to its existing Abilities Manager class. The method:

1. Filters `wp_pinch_mcp_server_abilities` to append its ability name strings
2. Filters `wp_register_ability_args` to add `meta.mcp.public = true` to its own abilities (for default MCP adapter compatibility)

The method is called conditionally, gated behind `class_exists( 'WP_Pinch\Abilities' )`.

## Post Kinds Ability Names (13)

Core (7):
- `post-kinds/list-kinds`
- `post-kinds/list-kind-fields`
- `post-kinds/create-post`
- `post-kinds/set-kind`
- `post-kinds/get-kind`
- `post-kinds/update-post-meta`
- `post-kinds/get-post-meta`

Lookup (6):
- `post-kinds/lookup-music`
- `post-kinds/lookup-video`
- `post-kinds/lookup-book`
- `post-kinds/lookup-podcast`
- `post-kinds/lookup-venue`
- `post-kinds/lookup-game`

## XFN Ability Names (5)

- `xfn/set-meta-relationships`
- `xfn/get-meta-relationships`
- `xfn/add-meta-relationship`
- `xfn/remove-meta-relationship`
- `xfn/validate-relationships`

## Files Changed

- Post Kinds: `includes/class-abilities-manager.php` — add `register_mcp_hooks()` static method, call from `init()`
- XFN: `includes/class-xfn-abilities-manager.php` — add `register_mcp_hooks()` static method, call from `__construct()`

## No Changes To

- Ability registration code
- Feature flags
- Execute callbacks
- Tests (existing)
- Main plugin files
