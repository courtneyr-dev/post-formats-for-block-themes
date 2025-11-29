/**
 * Performance Tests - JavaScript Bundle Size & Load Time
 *
 * Tests that JavaScript assets are optimized and load quickly.
 *
 * @package PostFormatsPowerUp
 */

const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

test.describe('JavaScript Performance', () => {

	/**
	 * Test bundle size is within limits
	 *
	 * Target: < 100KB minified
	 */
	test('JavaScript bundle size is acceptable', () => {
		const buildPath = path.join(__dirname, '../../build/index.js');

		if (!fs.existsSync(buildPath)) {
			test.skip('Build file not found - run npm run build first');
			return;
		}

		const stats = fs.statSync(buildPath);
		const sizeKB = stats.size / 1024;

		expect(sizeKB).toBeLessThan(100);

		console.log(`Bundle size: ${sizeKB.toFixed(2)}KB`);
	});

	/**
	 * Test editor loads quickly with plugin active
	 *
	 * Target: < 5 seconds to interactive
	 */
	test('editor loads within acceptable time', async ({ page }) => {
		// Navigate to login
		await page.goto('/wp-login.php');
		await page.fill('#user_login', 'admin');
		await page.fill('#user_pass', 'password');

		const startTime = Date.now();

		await page.click('#wp-submit');
		await page.goto('/wp-admin/post-new.php');

		// Wait for editor to be interactive
		await page.waitForSelector('.edit-post-layout', { timeout: 10000 });
		await page.waitForSelector('.block-editor-default-block-appender', { timeout: 5000 });

		const loadTime = (Date.now() - startTime) / 1000; // seconds

		expect(loadTime).toBeLessThan(5);

		console.log(`Editor load time: ${loadTime.toFixed(2)}s`);
	});

	/**
	 * Test modal opens quickly
	 *
	 * Target: < 500ms
	 */
	test('format modal opens quickly', async ({ page }) => {
		// Login and go to new post
		await page.goto('/wp-login.php');
		await page.fill('#user_login', 'admin');
		await page.fill('#user_pass', 'password');
		await page.click('#wp-submit');

		await page.goto('/wp-admin/post-new.php');
		await page.waitForSelector('.edit-post-layout');

		// Measure modal open time
		const startTime = Date.now();

		// Wait for modal (should appear automatically)
		await page.waitForSelector('.pfpu-format-modal, [role="dialog"]', { timeout: 5000 });

		const openTime = Date.now() - startTime;

		expect(openTime).toBeLessThan(500);

		console.log(`Modal open time: ${openTime}ms`);
	});

	/**
	 * Test no JavaScript errors on editor load
	 */
	test('no console errors on editor load', async ({ page }) => {
		const errors = [];

		page.on('console', msg => {
			if (msg.type() === 'error') {
				errors.push(msg.text());
			}
		});

		page.on('pageerror', error => {
			errors.push(error.message);
		});

		await page.goto('/wp-login.php');
		await page.fill('#user_login', 'admin');
		await page.fill('#user_pass', 'password');
		await page.click('#wp-submit');

		await page.goto('/wp-admin/post-new.php');
		await page.waitForSelector('.edit-post-layout', { timeout: 10000 });

		// Wait a bit for any delayed errors
		await page.waitForTimeout(2000);

		// Filter out known WordPress errors or other plugins
		const pluginErrors = errors.filter(err =>
			err.includes('pfpu') ||
			err.includes('post-formats-power-up') ||
			err.includes('format-modal')
		);

		expect(pluginErrors).toHaveLength(0);

		if (pluginErrors.length > 0) {
			console.log('Console errors found:', pluginErrors);
		}
	});

	/**
	 * Test CSS is loaded and parsed quickly
	 */
	test('CSS loads efficiently', async ({ page }) => {
		await page.goto('/wp-login.php');
		await page.fill('#user_login', 'admin');
		await page.fill('#user_pass', 'password');
		await page.click('#wp-submit');

		await page.goto('/wp-admin/post-new.php');

		// Get performance metrics
		const metrics = await page.evaluate(() => {
			const perfData = performance.getEntriesByType('resource');
			const cssResources = perfData.filter(entry =>
				entry.name.includes('post-formats-power-up') &&
				entry.name.endsWith('.css')
			);

			return cssResources.map(resource => ({
				name: resource.name,
				duration: resource.duration,
				size: resource.transferSize,
			}));
		});

		// Each CSS file should load in < 500ms
		metrics.forEach(resource => {
			expect(resource.duration).toBeLessThan(500);
			console.log(`CSS ${resource.name.split('/').pop()}: ${resource.duration.toFixed(0)}ms, ${(resource.size / 1024).toFixed(1)}KB`);
		});
	});
});

test.describe('Frontend Performance', () => {

	/**
	 * Test published post loads quickly
	 *
	 * Target: < 3 seconds
	 */
	test('published post with format loads quickly', async ({ page }) => {
		const startTime = Date.now();

		// Replace with actual post URL
		await page.goto('/', { waitUntil: 'networkidle' });

		const loadTime = (Date.now() - startTime) / 1000;

		expect(loadTime).toBeLessThan(3);

		console.log(`Frontend load time: ${loadTime.toFixed(2)}s`);
	});

	/**
	 * Test frontend CSS size is minimal
	 */
	test('frontend CSS is lightweight', async ({ page }) => {
		await page.goto('/');

		const cssMetrics = await page.evaluate(() => {
			const perfData = performance.getEntriesByType('resource');
			const pluginCSS = perfData.filter(entry =>
				entry.name.includes('post-formats-power-up') &&
				entry.name.endsWith('.css')
			);

			return pluginCSS.map(resource => ({
				size: resource.transferSize,
				duration: resource.duration,
			}));
		});

		if (cssMetrics.length > 0) {
			// Should be < 10KB
			expect(cssMetrics[0].size).toBeLessThan(10 * 1024);
		}
	});

	/**
	 * Test no frontend JavaScript unless necessary
	 */
	test('no unnecessary JavaScript on frontend', async ({ page }) => {
		await page.goto('/');

		const jsMetrics = await page.evaluate(() => {
			const perfData = performance.getEntriesByType('resource');
			const pluginJS = perfData.filter(entry =>
				entry.name.includes('post-formats-power-up') &&
				entry.name.endsWith('.js')
			);

			return pluginJS.length;
		});

		// Plugin should NOT load JS on frontend (admin only)
		expect(jsMetrics).toBe(0);
	});
});
