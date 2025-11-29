/**
 * Accessibility Testing Utilities
 *
 * Helper functions for WCAG 2.2 AA compliance testing
 *
 * @package PostFormatsPowerUp
 */

/**
 * Login to WordPress admin
 *
 * @param {import('@playwright/test').Page} page
 */
export async function loginToWordPress(page) {
	await page.goto('/wp-login.php');

	// Check if already logged in
	const isLoggedIn = await page.locator('body.wp-admin').count() > 0;
	if (isLoggedIn) {
		return;
	}

	// Fill in credentials
	await page.fill('#user_login', process.env.WP_USERNAME || 'admin');
	await page.fill('#user_pass', process.env.WP_PASSWORD || 'password');
	await page.click('#wp-submit');

	// Wait for dashboard
	await page.waitForURL('**/wp-admin/**');
}

/**
 * Navigate to new post editor
 *
 * @param {import('@playwright/test').Page} page
 */
export async function goToNewPost(page) {
	await page.goto('/wp-admin/post-new.php');

	// Wait for editor to load
	await page.waitForSelector('.edit-post-layout', { timeout: 10000 });

	// Close welcome guide if present
	const welcomeGuide = page.locator('[aria-label="Close"]').first();
	if (await welcomeGuide.isVisible()) {
		await welcomeGuide.click();
	}
}

/**
 * Wait for format modal to appear
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<import('@playwright/test').Locator>}
 */
export async function waitForFormatModal(page) {
	const modal = page.locator('.pfpu-format-modal, [role="dialog"][aria-label*="format"]');
	await modal.waitFor({ state: 'visible', timeout: 5000 });
	return modal;
}

/**
 * Get all focusable elements within a container
 *
 * @param {import('@playwright/test').Locator} container
 * @returns {Promise<import('@playwright/test').Locator>}
 */
export async function getFocusableElements(container) {
	return container.locator(
		'a[href], button:not([disabled]), textarea:not([disabled]), ' +
		'input:not([disabled]), select:not([disabled]), ' +
		'[tabindex]:not([tabindex="-1"])'
	);
}

/**
 * Check if element has visible focus indicator
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} element
 * @returns {Promise<boolean>}
 */
export async function hasVisibleFocusIndicator(page, element) {
	// Focus the element
	await element.focus();

	// Get computed styles
	const outline = await element.evaluate(el => {
		const styles = window.getComputedStyle(el);
		return {
			outlineWidth: styles.outlineWidth,
			outlineStyle: styles.outlineStyle,
			outlineColor: styles.outlineColor,
			boxShadow: styles.boxShadow,
		};
	});

	// Check if focus is visible (outline or box-shadow)
	const hasOutline = outline.outlineWidth !== '0px' &&
	                   outline.outlineStyle !== 'none';
	const hasBoxShadow = outline.boxShadow !== 'none';

	return hasOutline || hasBoxShadow;
}

/**
 * Simulate screen reader announcement check
 *
 * WordPress uses wp.a11y.speak() for screen reader announcements.
 * This checks if announcements are being made.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string[]>} Array of announcements
 */
export async function getScreenReaderAnnouncements(page) {
	// wp.a11y.speak() adds content to these regions
	const politeRegion = await page.locator('#wp-a11y-speak-polite').textContent();
	const assertiveRegion = await page.locator('#wp-a11y-speak-assertive').textContent();

	const announcements = [];
	if (politeRegion) announcements.push(politeRegion.trim());
	if (assertiveRegion) announcements.push(assertiveRegion.trim());

	return announcements.filter(Boolean);
}

/**
 * Check color contrast ratio
 *
 * @param {import('@playwright/test').Locator} element
 * @returns {Promise<{ratio: number, passes: boolean}>}
 */
export async function checkColorContrast(element) {
	const contrast = await element.evaluate((el) => {
		const styles = window.getComputedStyle(el);

		// Get colors
		const textColor = styles.color;
		const backgroundColor = styles.backgroundColor;
		const fontSize = parseFloat(styles.fontSize);

		// Parse RGB values
		const parseRGB = (color) => {
			const match = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
			return match ? [
				parseInt(match[1]),
				parseInt(match[2]),
				parseInt(match[3])
			] : null;
		};

		// Calculate relative luminance
		const getLuminance = (rgb) => {
			const [r, g, b] = rgb.map(val => {
				const normalized = val / 255;
				return normalized <= 0.03928
					? normalized / 12.92
					: Math.pow((normalized + 0.055) / 1.055, 2.4);
			});
			return 0.2126 * r + 0.7152 * g + 0.0722 * b;
		};

		const text = parseRGB(textColor);
		const bg = parseRGB(backgroundColor);

		if (!text || !bg) {
			return { ratio: 0, passes: false, error: 'Could not parse colors' };
		}

		const textLum = getLuminance(text);
		const bgLum = getLuminance(bg);

		// Calculate contrast ratio
		const lighter = Math.max(textLum, bgLum);
		const darker = Math.min(textLum, bgLum);
		const ratio = (lighter + 0.05) / (darker + 0.05);

		// WCAG AA requirements
		const isLargeText = fontSize >= 18 || (fontSize >= 14 && styles.fontWeight >= 700);
		const requiredRatio = isLargeText ? 3 : 4.5;

		return {
			ratio: ratio,
			passes: ratio >= requiredRatio,
			isLargeText: isLargeText,
			requiredRatio: requiredRatio
		};
	});

	return contrast;
}

/**
 * Check if modal implements proper focus trap
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} modal
 * @returns {Promise<boolean>}
 */
export async function hasFocusTrap(page, modal) {
	// Get first and last focusable elements
	const focusable = await getFocusableElements(modal);
	const count = await focusable.count();

	if (count === 0) return false;

	const firstFocusable = focusable.first();
	const lastFocusable = focusable.nth(count - 1);

	// Focus first element
	await firstFocusable.focus();

	// Tab backwards should go to last element
	await page.keyboard.press('Shift+Tab');
	const focusedAfterBackward = page.locator(':focus');
	const isLastFocused = await lastFocusable.evaluate((el, focused) => {
		return el === focused;
	}, await focusedAfterBackward.elementHandle());

	if (!isLastFocused) return false;

	// Tab forward should go to first element
	await page.keyboard.press('Tab');
	const focusedAfterForward = page.locator(':focus');
	const isFirstFocused = await firstFocusable.evaluate((el, focused) => {
		return el === focused;
	}, await focusedAfterForward.elementHandle());

	return isFirstFocused;
}
