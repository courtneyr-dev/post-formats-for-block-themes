// @ts-check
/**
 * E2E test for the Lightbox Slideshow gallery variation's dialog geometry.
 *
 * The overlay is a native <dialog> opened with showModal(). A native dialog
 * keeps the user-agent `width: fit-content`, so `inset: 0` alone leaves it
 * shrink-wrapped around the image in the viewport's top-left corner. This
 * spec opens the slideshow and checks the dialog covers the whole viewport
 * and that the page behind it is dimmed by the plugin's ::backdrop.
 *
 * Needs the image_gallery_styles flag on. CI sets it after wp-env starts;
 * locally run:
 *   npx wp-env run cli wp option update pfbt_feature_image_gallery_styles 1
 *
 * @package PostFormatsForBlockThemes
 */

const { test, expect } = require("@playwright/test");

test.use({ baseURL: process.env.WP_BASE_URL || "http://localhost:8888" });

// A 2x2 PNG: tiny on purpose, so a shrink-wrapped dialog is obvious.
const PNG_2X2 = Buffer.from(
  "iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAFklEQVR4nGP8z8DAwMDAxMDAwMDAAAANHQEDasKb6QAAAABJRU5ErkJggg==",
  "base64",
);

test.describe("Lightbox Slideshow dialog", () => {
  test.describe.configure({ mode: "serial" });

  /** @type {import('@playwright/test').APIRequestContext} */
  let apiContext;
  /** @type {number[]} */
  const mediaIds = [];
  let pageId = 0;
  let pageUrl = "";

  test.beforeAll(async ({ playwright }) => {
    apiContext = await playwright.request.newContext({
      baseURL: process.env.WP_BASE_URL || "http://localhost:8888",
      // REST rejects a logged-in cookie without a nonce even when Basic auth
      // is valid, so start without the storageState cookies.
      storageState: { cookies: [], origins: [] },
      extraHTTPHeaders: {
        Authorization: `Basic ${Buffer.from(
          `admin:${process.env.WP_APP_PASSWORD || "password"}`,
        ).toString("base64")}`,
      },
    });

    let images = "";
    for (const n of [1, 2]) {
      const res = await apiContext.post("/wp-json/wp/v2/media", {
        headers: {
          "Content-Type": "image/png",
          "Content-Disposition": `attachment; filename="pfbt-lightbox-e2e-${n}.png"`,
        },
        data: PNG_2X2,
      });
      expect(res.ok()).toBeTruthy();
      const media = await res.json();
      mediaIds.push(media.id);
      images += `<!-- wp:image {"id":${media.id},"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="${media.source_url}" alt="Lightbox test image ${n}" class="wp-image-${media.id}"/></figure><!-- /wp:image -->`;
    }

    const pageRes = await apiContext.post("/wp-json/wp/v2/pages", {
      data: {
        title: "E2E Lightbox Slideshow",
        status: "publish",
        content: `<!-- wp:gallery {"linkTo":"none","className":"is-style-lightbox-slideshow"} --><figure class="wp-block-gallery has-nested-images columns-default is-cropped is-style-lightbox-slideshow">${images}</figure><!-- /wp:gallery -->`,
      },
    });
    expect(pageRes.ok()).toBeTruthy();
    const page = await pageRes.json();
    pageId = page.id;
    pageUrl = page.link;
  });

  test.afterAll(async () => {
    if (pageId) {
      await apiContext.delete(`/wp-json/wp/v2/pages/${pageId}?force=true`);
    }
    for (const id of mediaIds) {
      await apiContext.delete(`/wp-json/wp/v2/media/${id}?force=true`);
    }
    await apiContext.dispose();
  });

  test("open dialog covers the viewport and dims the page", async ({ page }) => {
    await page.goto(pageUrl);

    const trigger = page.locator(".pfbt-lightbox-trigger").first();
    await expect(trigger).toBeVisible();
    await trigger.click();

    const dialog = page.locator("dialog.pfbt-lightbox-dialog");
    await expect(dialog).toBeVisible();

    const viewport = page.viewportSize();
    const box = await dialog.boundingBox();
    expect(viewport).not.toBeNull();
    expect(box).not.toBeNull();
    if (!viewport || !box) {
      return;
    }

    // Edge to edge, and no larger than the viewport (padding must not overflow).
    expect(Math.round(box.x)).toBe(0);
    expect(Math.round(box.y)).toBe(0);
    expect(Math.abs(box.width - viewport.width)).toBeLessThanOrEqual(1);
    expect(Math.abs(box.height - viewport.height)).toBeLessThanOrEqual(1);

    // The ::backdrop is the plugin's overlay, not the user-agent's faint default.
    const backdropAlpha = await dialog.evaluate((el) => {
      const color = getComputedStyle(el, "::backdrop").backgroundColor;
      const parts = color.match(/[\d.]+/g) || [];
      return parts.length === 4 ? Number(parts[3]) : parts.length === 3 ? 1 : 0;
    });
    expect(backdropAlpha).toBeGreaterThanOrEqual(0.5);

    await page.keyboard.press("Escape");
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
  });
});
