---
title: Playground preview
description: "How the WordPress Playground live preview works for Post Formats for Block Themes, what it demonstrates, and how to run it locally."
---

How the WordPress Playground live preview works for this plugin, what it demonstrates, and how to run it locally.

## What the preview shows

The blueprint at [`.wordpress-org/blueprints/blueprint.json`](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/.wordpress-org/blueprints/blueprint.json) builds a demo site with:

- The plugin installed from WordPress.org and activated.
- The Twenty Twenty-Five block theme (the plugin requires a block theme).
- Theme unit test content plus ten demo posts, one per post format — standard, aside, gallery, link, image, quote, status, video, audio, and a chat post.
- A logged-in admin session landing on **Posts → All Posts**, where the format column and demo posts are visible. From there, open any post to see its format in the editor, or add a new post to see the format selection modal.

## Run it locally

```sh
npx @wp-playground/cli@latest server --blueprint .wordpress-org/blueprints/blueprint.json --port 9414
```

Then open `http://127.0.0.1:9414/wp-admin/edit.php`. No Docker needed. The first boot downloads WordPress and the demo content, so allow a few minutes.

To preview your local working copy instead of the released plugin, mount the repo and skip the blueprint's self-install by using the capture-script environment: see [screenshots.md](/post-formats-for-block-themes/screenshots/) and `scripts/capture-docs-screenshots.js`, which boot Playground with `--auto-mount`.

## Version caveat

The blueprint installs the plugin from the WordPress.org directory, so the public Live Preview demonstrates whatever release the directory currently serves — check the listing's version against these docs, which describe 1.1.5. Deploys are a maintainer decision tracked in the [documentation plan](https://github.com/courtneyr-dev/post-formats-for-block-themes/blob/main/docs/documentation-plan.md).

## WordPress.org Live Preview (maintainer actions)

The listing already shows a Live Preview button. To update what it demonstrates:

1. Deploy the current plugin version to SVN (`trunk/` + tag) — the existing `deploy-wporg.yml` workflow handles this on release publish.
2. Copy `.wordpress-org/blueprints/blueprint.json` to the SVN `assets/blueprints/blueprint.json` path.
3. Verify the preview from the plugin page after the assets sync.
