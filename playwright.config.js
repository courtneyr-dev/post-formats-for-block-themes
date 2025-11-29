/**
 * Playwright configuration for Post Formats Power-Up
 *
 * @see https://playwright.dev/docs/test-configuration
 */

// Load environment variables from .env file
require('dotenv').config();

const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
	testDir: './tests',

	// Maximum time one test can run
	timeout: 60 * 1000,

	// Run tests in files in parallel
	fullyParallel: true,

	// Fail the build on CI if you accidentally left test.only in the source code
	forbidOnly: !!process.env.CI,

	// Retry on CI only
	retries: process.env.CI ? 2 : 0,

	// Opt out of parallel tests on CI
	workers: process.env.CI ? 1 : undefined,

	// Reporter to use
	reporter: [
		['html'],
		['list'],
		// On CI, also output GitHub annotations
		...(process.env.CI ? [['github']] : [])
	],

	// Shared settings for all projects
	use: {
		// Base URL for navigation
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',

		// Collect trace on failure
		trace: 'on-first-retry',

		// Screenshot on failure
		screenshot: 'only-on-failure',

		// Video on failure
		video: 'retain-on-failure',
	},

	// Configure projects for major browsers
	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},

		{
			name: 'firefox',
			use: { ...devices['Desktop Firefox'] },
		},

		{
			name: 'webkit',
			use: { ...devices['Desktop Safari'] },
		},

		// Test against mobile viewports
		{
			name: 'Mobile Chrome',
			use: { ...devices['Pixel 5'] },
		},
		{
			name: 'Mobile Safari',
			use: { ...devices['iPhone 12'] },
		},
	],

	// Run your local dev server before starting the tests
	// Uncomment if you want Playwright to start WordPress automatically
	// webServer: {
	// 	command: 'npm run start:wordpress',
	// 	url: 'http://localhost:8888',
	// 	reuseExistingServer: !process.env.CI,
	// },
});
