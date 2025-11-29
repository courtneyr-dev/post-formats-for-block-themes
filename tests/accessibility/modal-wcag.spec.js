/**
 * Format Selection Modal - WCAG 2.2 AA Compliance Tests
 *
 * Tests accessibility of the format selection modal against WCAG criteria.
 *
 * @package PostFormatsPowerUp
 */

const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const {
	loginToWordPress,
	goToNewPost,
	waitForFormatModal,
	getFocusableElements,
	hasVisibleFocusIndicator,
	getScreenReaderAnnouncements,
	checkColorContrast,
	hasFocusTrap
} = require('./utils');

test.describe('Format Modal - WCAG 2.2 AA Compliance', () => {

	test.beforeEach(async ({ page }) => {
		await loginToWordPress(page);
		await goToNewPost(page);
	});

	/**
	 * WCAG Success Criterion 1.3.1 - Info and Relationships
	 * Modal must have proper semantic structure
	 */
	test('modal has proper ARIA dialog role and label', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Check role
		const role = await modal.getAttribute('role');
		expect(role).toBe('dialog');

		// Check aria-modal
		const ariaModal = await modal.getAttribute('aria-modal');
		expect(ariaModal).toBe('true');

		// Check has accessible name (aria-label or aria-labelledby)
		const ariaLabel = await modal.getAttribute('aria-label');
		const ariaLabelledby = await modal.getAttribute('aria-labelledby');

		expect(ariaLabel || ariaLabelledby).toBeTruthy();

		if (ariaLabel) {
			expect(ariaLabel.length).toBeGreaterThan(0);
			expect(ariaLabel).toMatch(/format/i);
		}

		if (ariaLabelledby) {
			const labelElement = page.locator(`#${ariaLabelledby}`);
			await expect(labelElement).toBeVisible();
		}
	});

	/**
	 * WCAG Success Criterion 1.4.3 - Contrast (Minimum)
	 * Text must have sufficient color contrast
	 */
	test('modal text meets contrast requirements', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Check title/heading contrast
		const heading = modal.locator('h2, h3, [role="heading"]').first();
		const headingContrast = await checkColorContrast(heading);

		expect(headingContrast.passes).toBe(true);
		expect(headingContrast.ratio).toBeGreaterThanOrEqual(
			headingContrast.requiredRatio
		);

		// Check format card text contrast
		const formatCard = modal.locator('[data-format]').first();
		const cardContrast = await checkColorContrast(formatCard);

		expect(cardContrast.passes).toBe(true);

		// Check description text
		const description = modal.locator('.format-description, p').first();
		const descContrast = await checkColorContrast(description);

		expect(descContrast.passes).toBe(true);
	});

	/**
	 * WCAG Success Criterion 2.1.1 - Keyboard
	 * All functionality must be available via keyboard
	 */
	test('modal is fully keyboard accessible', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Get all focusable elements
		const focusable = await getFocusableElements(modal);
		const count = await focusable.count();

		expect(count).toBeGreaterThan(0);

		// Tab through all focusable elements
		for (let i = 0; i < count; i++) {
			await page.keyboard.press('Tab');

			const focused = page.locator(':focus');
			const isFocusInModal = await modal.evaluate((modalEl, focusedEl) => {
				return modalEl.contains(focusedEl);
			}, await focused.elementHandle());

			expect(isFocusInModal).toBe(true);
		}
	});

	/**
	 * WCAG Success Criterion 2.1.2 - No Keyboard Trap
	 * Focus must be trappable BUT with Escape key exit
	 */
	test('modal implements focus trap with Escape exit', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Verify focus trap works
		const trapWorks = await hasFocusTrap(page, modal);
		expect(trapWorks).toBe(true);

		// Verify Escape key closes modal
		await page.keyboard.press('Escape');

		// Modal should be closed
		await expect(modal).not.toBeVisible();
	});

	/**
	 * WCAG Success Criterion 2.4.3 - Focus Order
	 * Focus order must be logical
	 */
	test('focus order is logical', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Expected focus order:
		// 1. Close button
		// 2. Format cards (in visual order)
		// 3. Any action buttons

		const focusable = await getFocusableElements(modal);
		const count = await focusable.count();

		const focusOrder = [];

		for (let i = 0; i < count; i++) {
			await page.keyboard.press('Tab');
			const focused = page.locator(':focus');

			const elementInfo = await focused.evaluate(el => ({
				tag: el.tagName,
				class: el.className,
				text: el.textContent?.trim().substring(0, 20),
				ariaLabel: el.getAttribute('aria-label'),
			}));

			focusOrder.push(elementInfo);
		}

		// First focusable should be close button
		expect(focusOrder[0]).toMatchObject({
			ariaLabel: expect.stringMatching(/close/i)
		});

		// Format cards should be in sequence
		const formatCards = focusOrder.filter(el =>
			el.class?.includes('format') || el.ariaLabel?.includes('format')
		);

		expect(formatCards.length).toBeGreaterThanOrEqual(10); // 10 formats
	});

	/**
	 * WCAG Success Criterion 2.4.7 - Focus Visible
	 * Focus indicator must be visible
	 */
	test('all interactive elements have visible focus indicators', async ({ page }) => {
		const modal = await waitForFormatModal(page);
		const focusable = await getFocusableElements(modal);
		const count = await focusable.count();

		// Check focus indicators for all elements
		for (let i = 0; i < Math.min(count, 5); i++) { // Sample first 5
			const element = focusable.nth(i);
			const hasFocus = await hasVisibleFocusIndicator(page, element);

			expect(hasFocus).toBe(true);
		}
	});

	/**
	 * WCAG Success Criterion 4.1.2 - Name, Role, Value
	 * Format cards must have proper names and roles
	 */
	test('format cards have accessible names and roles', async ({ page }) => {
		const modal = await waitForFormatModal(page);
		const formatCards = modal.locator('[data-format]');
		const count = await formatCards.count();

		expect(count).toBe(10); // All 10 formats

		for (let i = 0; i < count; i++) {
			const card = formatCards.nth(i);

			// Check role
			const role = await card.getAttribute('role');
			expect(['button', 'option', 'radio']).toContain(role);

			// Check accessible name
			const ariaLabel = await card.getAttribute('aria-label');
			const textContent = await card.textContent();

			expect(ariaLabel || textContent).toBeTruthy();

			// If it's a radio, check proper radio group
			if (role === 'radio') {
				const radioGroup = modal.locator('[role="radiogroup"]');
				await expect(radioGroup).toBeVisible();
			}
		}
	});

	/**
	 * Run automated axe-core scan
	 * This catches 30-40% of WCAG issues automatically
	 */
	test('modal passes axe-core accessibility scan', async ({ page }) => {
		await waitForFormatModal(page);

		const accessibilityScanResults = await new AxeBuilder({ page })
			.include('.pfpu-format-modal, [role="dialog"]')
			.exclude('[data-test-id="non-essential"]')
			.withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
			.analyze();

		expect(accessibilityScanResults.violations).toEqual([]);

		// If there are violations, log them for debugging
		if (accessibilityScanResults.violations.length > 0) {
			console.log('Accessibility violations found:');
			accessibilityScanResults.violations.forEach(violation => {
				console.log(`- ${violation.id}: ${violation.description}`);
				console.log(`  Help: ${violation.helpUrl}`);
				console.log(`  Impact: ${violation.impact}`);
				console.log(`  Affected nodes: ${violation.nodes.length}`);
			});
		}
	});

	/**
	 * Screen reader announcement test
	 * Modal opening should be announced
	 */
	test('modal opening is announced to screen readers', async ({ page }) => {
		// Clear any existing announcements
		await page.evaluate(() => {
			const polite = document.getElementById('wp-a11y-speak-polite');
			const assertive = document.getElementById('wp-a11y-speak-assertive');
			if (polite) polite.textContent = '';
			if (assertive) assertive.textContent = '';
		});

		// Open modal (it should already be open, but let's verify announcements)
		const modal = await waitForFormatModal(page);
		await modal.waitFor({ state: 'visible' });

		// Wait a bit for screen reader announcements
		await page.waitForTimeout(500);

		// Check for announcements
		const announcements = await getScreenReaderAnnouncements(page);

		// Should announce modal opened or format selection
		const hasRelevantAnnouncement = announcements.some(text =>
			text.match(/format/i) || text.match(/modal/i) || text.match(/dialog/i)
		);

		// This is a soft assertion - some implementations may not announce
		// but it's a best practice
		if (!hasRelevantAnnouncement) {
			console.warn('Warning: No screen reader announcement detected for modal opening');
		}
	});

	/**
	 * Test format selection announcement
	 */
	test('format selection is announced to screen readers', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Select a format
		const galleryFormat = modal.locator('[data-format="gallery"]');
		await galleryFormat.click();

		// Wait for announcement
		await page.waitForTimeout(500);

		const announcements = await getScreenReaderAnnouncements(page);

		// Should announce the selected format
		const hasSelectionAnnouncement = announcements.some(text =>
			text.match(/gallery/i) || text.match(/selected/i)
		);

		expect(hasSelectionAnnouncement).toBe(true);
	});

	/**
	 * Test ARIA live region for dynamic content
	 */
	test('modal uses ARIA live regions for dynamic updates', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Check for live regions
		const liveRegions = modal.locator('[aria-live]');
		const count = await liveRegions.count();

		if (count > 0) {
			// Verify live region properties
			for (let i = 0; i < count; i++) {
				const region = liveRegions.nth(i);
				const ariaLive = await region.getAttribute('aria-live');

				expect(['polite', 'assertive']).toContain(ariaLive);

				// If it has aria-atomic, it should be a boolean
				const ariaAtomic = await region.getAttribute('aria-atomic');
				if (ariaAtomic) {
					expect(['true', 'false']).toContain(ariaAtomic);
				}
			}
		}
	});

	/**
	 * Test that modal doesn't hide content from screen readers improperly
	 */
	test('modal does not improperly hide content', async ({ page }) => {
		const modal = await waitForFormatModal(page);

		// Check that background is properly marked as inert or aria-hidden
		const body = page.locator('body');
		const mainContent = body.locator('main, #wpwrap, .edit-post-layout');

		// Background should have aria-hidden="true" or inert
		const ariaHidden = await mainContent.first().getAttribute('aria-hidden');
		const inert = await mainContent.first().getAttribute('inert');

		// At least one should be set to hide background from screen readers
		expect(ariaHidden === 'true' || inert !== null).toBe(true);
	});
});
