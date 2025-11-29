/**
 * Visual Regression Tests - Format Selection Modal
 *
 * Tests visual appearance of modal and format cards to catch
 * unintended styling changes.
 *
 * Run with: npm run test:visual
 * Update snapshots: npx playwright test --update-snapshots
 *
 * @package PostFormatsPowerUp
 */

const { test, expect } = require('@playwright/test');
const { loginToWordPress, goToNewPost, waitForFormatModal } = require('../accessibility/utils');

test.describe('Visual Regression - Format Modal', () => {

	test.beforeEach(async ({ page }) => {
		await loginToWordPress(page);
		await goToNewPost(page);
	});

	/**
	 * Test modal overall appearance
	 *
	 * Catches: Layout breaks, positioning issues, modal size changes
	 */
	test('modal displays correctly', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Wait for modal animation to complete
		await page.waitForTimeout(500);

		// Take full modal screenshot
		await expect(modal).toHaveScreenshot('format-modal-full.png', {
			// Allow 5% pixel difference for anti-aliasing
			maxDiffPixels: 100,
			threshold: 0.05,
		});
	});

	/**
	 * Test individual format cards
	 *
	 * Catches: Icon changes, text wrapping, card sizing
	 */
	test('format cards render consistently', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Screenshot first three format cards
		const formatCards = [
			{ format: 'standard', name: 'Standard' },
			{ format: 'gallery', name: 'Gallery' },
			{ format: 'quote', name: 'Quote' },
		];

		for (const { format, name } of formatCards) {
			const card = modal.locator(`[data-format="${format}"]`);

			await expect(card).toHaveScreenshot(`format-card-${format}.png`, {
				maxDiffPixels: 50,
			});
		}
	});

	/**
	 * Test modal on mobile viewport
	 *
	 * Catches: Responsive layout issues, overflow problems
	 */
	test('modal responsive on mobile', async ({ page }) => {
		// Set mobile viewport
		await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE

		await goToNewPost(page);
		const modal = await waitForFormatModal(page);

		await page.waitForTimeout(500);

		await expect(modal).toHaveScreenshot('format-modal-mobile.png', {
			fullPage: false,
			maxDiffPixels: 100,
		});
	});

	/**
	 * Test modal on tablet viewport
	 */
	test('modal responsive on tablet', async ({ page }) => {
		// Set tablet viewport
		await page.setViewportSize({ width: 768, height: 1024 }); // iPad

		await goToNewPost(page);
		const modal = await waitForFormatModal(page);

		await page.waitForTimeout(500);

		await expect(modal).toHaveScreenshot('format-modal-tablet.png', {
			maxDiffPixels: 100,
		});
	});

	/**
	 * Test hover states (interactive screenshots)
	 *
	 * Catches: Hover effect changes, focus style changes
	 */
	test('format card hover states', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		const galleryCard = modal.locator('[data-format="gallery"]');

		// Hover over card
		await galleryCard.hover();
		await page.waitForTimeout(200); // Wait for hover animation

		await expect(galleryCard).toHaveScreenshot('format-card-hover.png', {
			maxDiffPixels: 50,
		});
	});

	/**
	 * Test focus states
	 *
	 * Catches: Focus indicator changes
	 */
	test('format card focus states', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		const galleryCard = modal.locator('[data-format="gallery"]');

		// Focus card
		await galleryCard.focus();
		await page.waitForTimeout(100);

		await expect(galleryCard).toHaveScreenshot('format-card-focus.png', {
			maxDiffPixels: 50,
		});
	});

	/**
	 * Test selected state (if applicable)
	 */
	test('format card selected state', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Click to select
		const galleryCard = modal.locator('[data-format="gallery"]');
		await galleryCard.click();

		// Wait for selection to register (if modal stays open briefly)
		await page.waitForTimeout(200);

		// If modal closes immediately, this test might need adjustment
		// Take screenshot of editor with gallery block inserted instead
		const editorContent = page.locator('.editor-styles-wrapper');

		await expect(editorContent).toHaveScreenshot('gallery-format-selected.png', {
			maxDiffPixels: 200,
			// Mask dynamic elements
			mask: [page.locator('.editor-post-title')],
		});
	});
});

test.describe('Visual Regression - Format Switcher', () => {

	test.beforeEach(async ({ page }) => {
		await loginToWordPress(page);
		await goToNewPost(page);

		// Close initial modal
		const modal = await waitForFormatModal(page);
		await modal.locator('[data-format="standard"]').click();
	});

	/**
	 * Test format switcher sidebar panel
	 */
	test('format switcher panel displays correctly', async ({ page }) => {
		// Open format switcher
		const switcherButton = page.locator('[aria-label*="Format Switcher"], .pfpu-format-switcher-button');
		await switcherButton.click();

		// Wait for panel to appear
		await page.waitForTimeout(500);

		const panel = page.locator('.pfpu-format-switcher-panel, [class*="format-switcher"]');

		await expect(panel).toHaveScreenshot('format-switcher-panel.png', {
			maxDiffPixels: 100,
		});
	});
});

test.describe('Visual Regression - Published Posts', () => {

	/**
	 * Test aside format on frontend
	 *
	 * Catches: Styling regressions in aside format display
	 */
	test('aside format displays without styling', async ({ page }) => {
		// Navigate to a published aside post
		// You'll need to create a test post first or use a fixture

		await page.goto('/aside-test-post/'); // Adjust URL

		const asideContent = page.locator('.aside-bubble, article');

		await expect(asideContent).toHaveScreenshot('aside-format-frontend.png', {
			maxDiffPixels: 200,
			// Mask dynamic content
			mask: [
				page.locator('.post-date'),
				page.locator('.comment-count'),
			],
		});
	});

	/**
	 * Test status format on frontend
	 */
	test('status format displays correctly', async ({ page }) => {
		await page.goto('/status-test-post/');

		const statusContent = page.locator('.status-paragraph, article');

		await expect(statusContent).toHaveScreenshot('status-format-frontend.png', {
			maxDiffPixels: 200,
			mask: [
				page.locator('.post-date'),
			],
		});
	});
});
