/**
 * Filter Tags — Interactivity API view module.
 *
 * For each .wp-block-gallery.is-style-filter-tags, build a chip bar
 * from the union of all per-image tags (read from data-pfbt-tags),
 * then toggle visibility based on the active chip. Active count is
 * announced via aria-live for screen readers.
 *
 * Items without a data-pfbt-tags attribute are treated as "always
 * visible" (shown under any active filter, including "All").
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

import { store } from "@wordpress/interactivity";

const NAMESPACE = "post-formats/filter-tags";

store(NAMESPACE, {
	actions: {
		setFilter(event) {
			const chip = event.currentTarget;
			const gallery = chip.closest(".wp-block-gallery.is-style-filter-tags");
			if (!gallery) return;

			const filter = chip.dataset.pfbtFilter || "";
			gallery.setAttribute("data-pfbt-active-filter", filter);

			gallery
				.querySelectorAll(".pfbt-filter-tags-bar__chip")
				.forEach((c) => {
					c.setAttribute("aria-pressed", String(c === chip));
				});

			const items = Array.from(
				gallery.querySelectorAll(".wp-block-image"),
			);
			let visible = 0;
			items.forEach((item) => {
				const tags = (item.dataset.pfbtTags || "").split(/\s+/).filter(Boolean);
				const matches =
					filter === "" || tags.length === 0 || tags.includes(filter);
				item.style.display = matches ? "" : "none";
				if (matches) visible += 1;
			});

			const counter = gallery.querySelector(".pfbt-filter-tags-status");
			if (counter) {
				counter.textContent =
					"Showing " + visible + " of " + items.length + " images";
			}
		},
	},
});

document.addEventListener("DOMContentLoaded", () => {
	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-filter-tags",
	);

	galleries.forEach((gallery) => {
		const items = Array.from(gallery.querySelectorAll(".wp-block-image"));
		const tagSet = new Set();
		items.forEach((item) => {
			(item.dataset.pfbtTags || "").split(/\s+/).filter(Boolean).forEach((t) =>
				tagSet.add(t),
			);
		});
		if (tagSet.size === 0) return;

		const bar = document.createElement("div");
		bar.className = "pfbt-filter-tags-bar";
		bar.setAttribute("role", "toolbar");
		bar.setAttribute("aria-label", "Gallery filters");

		const allChip = document.createElement("button");
		allChip.type = "button";
		allChip.className = "pfbt-filter-tags-bar__chip";
		allChip.dataset.pfbtFilter = "";
		allChip.setAttribute("aria-pressed", "true");
		allChip.textContent = "All";
		bar.appendChild(allChip);

		Array.from(tagSet)
			.sort()
			.forEach((tag) => {
				const chip = document.createElement("button");
				chip.type = "button";
				chip.className = "pfbt-filter-tags-bar__chip";
				chip.dataset.pfbtFilter = tag;
				chip.setAttribute("aria-pressed", "false");
				chip.textContent = tag;
				bar.appendChild(chip);
			});

		const status = document.createElement("span");
		status.className = "pfbt-filter-tags-status screen-reader-text";
		status.setAttribute("role", "status");
		status.setAttribute("aria-live", "polite");
		bar.appendChild(status);

		gallery.prepend(bar);
		gallery.setAttribute("data-pfbt-active-filter", "");

		bar.querySelectorAll(".pfbt-filter-tags-bar__chip").forEach((chip) => {
			chip.addEventListener("click", (e) =>
				store(NAMESPACE).actions.setFilter(e),
			);
		});
	});
});
