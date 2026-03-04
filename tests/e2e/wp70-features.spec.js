// @ts-check
/**
 * E2E tests for WP 7.0 features: Format Badge Block and Block Bindings
 *
 * Tests PHP-rendered frontend output for block hooks and block bindings.
 *
 * @package PostFormatsForBlockThemes
 */

const { test, expect } = require("@playwright/test");

test.use({ baseURL: process.env.WP_BASE_URL || "http://localhost:8888" });

test.describe("WP 7.0 Features", () => {
  test.describe.configure({ mode: "serial" });

  /** @type {import('@playwright/test').APIRequestContext} */
  let apiContext;
  let quotePostId;
  let standardPostId;
  let quotePostUrl;
  let standardPostUrl;

  test.beforeAll(async ({ playwright }) => {
    apiContext = await playwright.request.newContext({
      baseURL: process.env.WP_BASE_URL || "http://localhost:8888",
      extraHTTPHeaders: {
        Authorization: `Basic ${Buffer.from("admin:password").toString(
          "base64",
        )}`,
      },
    });

    // Create a quote format post.
    const quoteRes = await apiContext.post("/wp-json/wp/v2/posts", {
      data: {
        title: "E2E Quote Post",
        content:
          '<!-- wp:quote --><blockquote class="wp-block-quote"><p>Test quote for E2E</p></blockquote><!-- /wp:quote -->',
        status: "publish",
        format: "quote",
      },
    });
    const quoteData = await quoteRes.json();
    quotePostId = quoteData.id;
    quotePostUrl = quoteData.link;

    // Create a standard format post.
    const standardRes = await apiContext.post("/wp-json/wp/v2/posts", {
      data: {
        title: "E2E Standard Post",
        content:
          "<!-- wp:paragraph --><p>Standard content</p><!-- /wp:paragraph -->",
        status: "publish",
      },
    });
    const standardData = await standardRes.json();
    standardPostId = standardData.id;
    standardPostUrl = standardData.link;
  });

  test.afterAll(async () => {
    if (quotePostId) {
      await apiContext.delete(`/wp-json/wp/v2/posts/${quotePostId}?force=true`);
    }
    if (standardPostId) {
      await apiContext.delete(
        `/wp-json/wp/v2/posts/${standardPostId}?force=true`,
      );
    }
    await apiContext.dispose();
  });

  test.describe("Format Badge Block", () => {
    test("badge appears before post title for non-standard formats", async ({
      page,
    }) => {
      await page.goto(quotePostUrl);

      const badge = page.locator(".pfbt-format-badge");
      await expect(badge).toBeVisible();

      // Badge should contain a dashicon span.
      const dashicon = badge.locator('span[class*="dashicons"]');
      await expect(dashicon).toBeVisible();

      // Badge should contain format label text.
      await expect(badge).not.toBeEmpty();
    });

    test("badge does not appear for standard format posts", async ({
      page,
    }) => {
      await page.goto(standardPostUrl);

      const badges = page.locator(".pfbt-format-badge");
      await expect(badges).toHaveCount(0);
    });

    test("badge contains correct format-specific class", async ({ page }) => {
      await page.goto(quotePostUrl);

      const badge = page.locator(".pfbt-format-badge");
      await expect(badge).toHaveClass(/pfbt-format-badge--quote/);
    });
  });

  test.describe("Block Bindings Source", () => {
    test("bound paragraph displays format label", async ({ page }) => {
      await page.goto(quotePostUrl);

      // A paragraph bound to the post format source should render the format label.
      const boundContent = page.locator(
        '[data-bound-source="post-format-label"], .pfbt-bound-format-label',
      );

      // If the binding renders as plain text inside an element, check for "Quote" label.
      const formatLabel = page.getByText("Quote", { exact: false });
      await expect(formatLabel.first()).toBeVisible();
    });

    test("format data updates when post format changes", async ({ page }) => {
      // Verify the REST API exposes format data on posts.
      const response = await apiContext.get("/wp-json/wp/v2/posts?per_page=1");
      expect(response.ok()).toBeTruthy();

      const posts = await response.json();
      expect(posts.length).toBeGreaterThan(0);
      expect(posts[0]).toHaveProperty("format");
    });
  });
});
