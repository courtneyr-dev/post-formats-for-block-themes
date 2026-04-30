/**
 * Before/After Pairs — Interactivity API view module.
 *
 * For each .wp-block-gallery.is-style-before-after-pairs, walk
 * consecutive .wp-block-image children in pairs, wrap them in a
 * .pfbt-ba-pair container, append a range input, and update the
 * --pfbt-ba-position custom property as the user drags.
 *
 * Range input is keyboard-accessible by default (arrow keys nudge,
 * Home/End jump). No focus trap needed — input lives in normal flow.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

import { store } from "@wordpress/interactivity";

const NAMESPACE = "post-formats/before-after-pairs";

store(NAMESPACE, {
	actions: {
		setPosition(event) {
			const pair = event.currentTarget.closest(".pfbt-ba-pair");
			if (!pair) return;
			const value = event.currentTarget.value;
			pair.style.setProperty("--pfbt-ba-position", value + "%");
		},
	},
});

document.addEventListener("DOMContentLoaded", () => {
	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-before-after-pairs",
	);

	galleries.forEach((gallery) => {
		const items = Array.from(gallery.querySelectorAll(".wp-block-image"));
		// Walk in pairs of two; skip a trailing odd image.
		for (let i = 0; i + 1 < items.length; i += 2) {
			const before = items[i];
			const after = items[i + 1];

			const pair = document.createElement("div");
			pair.className = "pfbt-ba-pair";
			pair.style.setProperty("--pfbt-ba-position", "50%");

			const beforeImg = before.querySelector("img");
			const afterImg = after.querySelector("img");
			if (!beforeImg || !afterImg) continue;

			const beforeWrap = document.createElement("div");
			beforeWrap.className = "pfbt-ba-before";
			beforeWrap.appendChild(beforeImg.cloneNode(true));

			const afterWrap = document.createElement("div");
			afterWrap.className = "pfbt-ba-after";
			afterWrap.appendChild(afterImg.cloneNode(true));

			const range = document.createElement("input");
			range.type = "range";
			range.min = "0";
			range.max = "100";
			range.value = "50";
			range.setAttribute(
				"aria-label",
				"Before/after comparison slider; left for before, right for after",
			);
			range.addEventListener("input", (e) => {
				pair.style.setProperty("--pfbt-ba-position", e.currentTarget.value + "%");
			});

			pair.appendChild(beforeWrap);
			pair.appendChild(afterWrap);
			pair.appendChild(range);

			before.replaceWith(pair);
			after.remove();
		}
	});
});
