/**
 * Frontend JavaScript for Chat Log block
 *
 * Provides progressive enhancement for interactive features. Core functionality
 * of the Chat Log block works without JavaScript (server-side rendering), but
 * this script adds:
 * - Thread expansion/collapse functionality
 * - Accessible keyboard interactions
 * - ARIA attribute updates
 *
 * All enhancements follow progressive enhancement principles and maintain
 * WCAG 2.2 AA accessibility compliance.
 *
 * @since 1.0.0
 * @package ChatLog
 */

/**
 * Initialize Chat Log interactive features
 *
 * Sets up event listeners for thread toggle buttons and other interactive
 * elements. Runs after DOM is fully loaded to ensure all elements are available.
 *
 * @since 1.0.0
 *
 * @listens DOMContentLoaded
 *
 * @return {void}
 */
document.addEventListener('DOMContentLoaded', function() {
	/**
	 * Thread toggle functionality (future enhancement)
	 *
	 * Allows users to expand/collapse threaded replies in chat conversations.
	 * Updates ARIA attributes for screen reader accessibility.
	 *
	 * @since 1.0.0
	 */
	const threadToggles = document.querySelectorAll('.chatlog-thread-toggle');
	
	/**
	 * Add click event listeners to each thread toggle
	 *
	 * Toggles the collapsed state of threaded replies and updates
	 * ARIA attributes for accessibility.
	 *
	 * @param {Element} toggle Individual thread toggle button
	 */
	threadToggles.forEach(function(toggle) {
		/**
		 * Handle thread toggle click
		 *
		 * Finds the associated thread element and toggles its collapsed state.
		 * Updates the aria-expanded attribute to reflect current state.
		 *
		 * @listens click
		 * @this {Element} The clicked toggle button
		 */
		toggle.addEventListener('click', function() {
			const thread = this.parentElement.querySelector('.chatlog-thread');
			if (thread) {
				thread.classList.toggle('chatlog-thread--collapsed');
				this.setAttribute(
					'aria-expanded',
					thread.classList.contains('chatlog-thread--collapsed') ? 'false' : 'true'
				);
			}
		});
	});
});
