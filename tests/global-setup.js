/**
 * Playwright global setup
 *
 * Logs in as admin ONCE and saves the authenticated storage state for all
 * test projects. Per-test logins ran concurrently (fullyParallel) and
 * WordPress loses session tokens when the same user logs in in parallel
 * (lost update on the session_tokens user meta), bouncing tests to
 * wp-login.php?reauth=1.
 *
 * @package PostFormatsPowerUp
 */

const fs = require('fs');
const path = require('path');
const { chromium } = require('@playwright/test');
const { loginToWordPress, STORAGE_STATE_PATH } = require('./accessibility/utils');

module.exports = async () => {
	const baseURL = process.env.WP_BASE_URL || 'http://localhost:8888';

	fs.mkdirSync(path.dirname(STORAGE_STATE_PATH), { recursive: true });

	const browser = await chromium.launch();
	const page = await browser.newPage({ baseURL });
	await loginToWordPress(page);
	await page.context().storageState({ path: STORAGE_STATE_PATH });
	await browser.close();
};
