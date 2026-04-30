/**
 * Photo Essay Scroll — Interactivity API view module.
 *
 * Optional enhancement: IntersectionObserver tracks which figcaption
 * is currently visible and adds an `is-active` class to that segment
 * (and the linked image) so themes can bump styling on focus. Pure
 * progressive enhancement — the CSS sticky layout works without it.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

document.addEventListener("DOMContentLoaded", () => {
	if (typeof IntersectionObserver === "undefined") return;

	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-photo-essay-scroll",
	);

	galleries.forEach((gallery) => {
		const captions = gallery.querySelectorAll(".wp-block-image figcaption");
		if (captions.length === 0) return;

		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						captions.forEach((c) => c.classList.remove("is-active"));
						entry.target.classList.add("is-active");
					}
				});
			},
			{ rootMargin: "-30% 0px -50% 0px", threshold: 0 },
		);

		captions.forEach((caption) => observer.observe(caption));
	});
});
