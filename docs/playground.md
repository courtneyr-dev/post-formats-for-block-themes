# Playground preview

How the WordPress Playground live preview works for this plugin, what it demonstrates, and how to run it locally.

## What the preview shows

The blueprint at [`.wordpress-org/blueprints/blueprint.json`](../.wordpress-org/blueprints/blueprint.json) builds a demo site with:

- The plugin installed from WordPress.org and activated.
- The Twenty Twenty-Five block theme (the plugin requires a block theme).
- Theme unit test content plus ten demo posts, one per post format — standard, aside, gallery, link, image, quote, status, video, audio, and a chat post.
- A logged-in admin session landing on **Posts → All Posts**, where the format column and demo posts are visible. From there, open any post to see its format in the editor, or add a new post to see the format selection modal.

## Run it locally

```
npx @wp-playground/cli@latest server --blueprint .wordpress-org/blueprints/blueprint.json --port 9414
```

Then open `http://127.0.0.1:9414/wp-admin/edit.php`. No Docker needed. The first boot downloads WordPress and the demo content, so allow a few minutes.

To preview your local working copy instead of the released plugin, mount the repo and skip the blueprint's self-install by using the capture-script environment: see [screenshots.md](screenshots.md) and `scripts/capture-docs-screenshots.js`, which boot Playground with `--auto-mount`.

## Version caveat

The blueprint installs the plugin from the WordPress.org directory, which currently serves the last deployed release (1.1.4) — not the 2.4.0 code in this repository. The public Live Preview on WordPress.org demonstrates the old version until a 2.x release is deployed to SVN. That deploy is a maintainer decision tracked in the [documentation plan](documentation-plan.md).

## WordPress.org Live Preview (maintainer actions)

The listing already shows a Live Preview button. To update what it demonstrates:

1. Deploy the current plugin version to SVN (`trunk/` + tag) — the existing `deploy-wporg.yml` workflow handles this on release publish.
2. Copy `.wordpress-org/blueprints/blueprint.json` to the SVN `assets/blueprints/blueprint.json` path.
3. Verify the preview from the plugin page after the assets sync.

---

[Documentation home](index.md) · Previous: [Screenshots](screenshots.md) · Next: [Troubleshooting](troubleshooting.md)
