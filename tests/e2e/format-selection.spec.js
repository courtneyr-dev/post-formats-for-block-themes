/**
 * E2E tests for format selection workflow
 *
 * Tests the complete user journey in the editor.
 *
 * @package PostFormatsPowerUp
 */

const { test, expect } = require('@playwright/test');
const { loginToWordPress, goToNewPost, waitForFormatModal } = require('../accessibility/utils');

test.describe('Format Selection Workflow', () => {

	test.beforeEach(async ({ page }) => {
		await loginToWordPress(page);
	});

	test('modal displays on new post creation', async ({ page }) => {
		await goToNewPost(page);

		// Modal should appear automatically
		const modal = await waitForFormatModal(page);
		await expect(modal).toBeVisible();

		// Should show all 10 formats
		const formatCards = modal.locator('[data-format]');
		await expect(formatCards).toHaveCount(10);
	});

	test('can select gallery format and pattern inserts', async ({ page }) => {
		await goToNewPost(page);

		const modal = await waitForFormatModal(page);

		// Select gallery format
		await modal.locator('[data-format="gallery"]').click();

		// Modal should close
		await expect(modal).not.toBeVisible();

		// Gallery block should be inserted
		await expect(page.locator('.wp-block-gallery')).toBeVisible();
	});

	test('format switcher allows changing format mid-edit', async ({ page }) => {
		await goToNewPost(page);

		// Close initial modal by selecting standard
		const modal = await waitForFormatModal(page);
		await modal.locator('[data-format="standard"]').click();

		// Open format switcher in sidebar
		await page.click('[aria-label*="Format Switcher"], .pfpu-format-switcher-button');

		// Change to quote format
		await page.click('[data-format="quote"]');

		// Quote block should be inserted
		await expect(page.locator('.wp-block-quote')).toBeVisible();
	});

	test('switching formats with existing content shows confirmation', async ({ page }) => {
		await goToNewPost(page);

		// Select standard format
		const modal = await waitForFormatModal(page);
		await modal.locator('[data-format="standard"]').click();

		// Add some content
		await page.fill('.editor-post-title__input', 'Test Post');
		await page.type('.block-editor-default-block-appender textarea', 'Test content');

		// Try to switch format
		await page.click('[aria-label*="Format Switcher"]');
		await page.click('[data-format="gallery"]');

		// Confirmation dialog should appear
		const confirmDialog = page.locator('[role="dialog"]').last();
		await expect(confirmDialog).toBeVisible();
		await expect(confirmDialog).toContainText(/replace.*content|keep.*content/i);
	});
});
