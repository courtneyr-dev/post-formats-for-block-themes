/**
 * Card Deck Swipe — Interactivity API view module.
 *
 * Manages the active card index for each .is-style-card-deck-swipe
 * gallery. Adds prev/next chevron controls below the deck. Sets
 * data-pfbt-deck-pos on each item (0 = active, 1/2 = behind, -1 =
 * hidden). Live region announces "Card X of Y" on each navigation.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

document.addEventListener("DOMContentLoaded", () => {
	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-card-deck-swipe",
	);

	galleries.forEach((gallery) => {
		const items = Array.from(gallery.querySelectorAll(".wp-block-image"));
		if (items.length === 0) return;

		let active = 0;

		const controls = document.createElement("div");
		controls.className = "pfbt-card-deck-controls";

		const prev = document.createElement("button");
		prev.type = "button";
		prev.setAttribute("aria-label", "Previous card");
		prev.textContent = "‹";

		const next = document.createElement("button");
		next.type = "button";
		next.setAttribute("aria-label", "Next card");
		next.textContent = "›";

		const status = document.createElement("span");
		status.className = "pfbt-card-deck-status";
		status.setAttribute("role", "status");
		status.setAttribute("aria-live", "polite");

		controls.appendChild(prev);
		controls.appendChild(status);
		controls.appendChild(next);
		gallery.appendChild(controls);

		const update = () => {
			items.forEach((item, idx) => {
				const offset = (idx - active + items.length) % items.length;
				if (offset > 2) {
					item.setAttribute("data-pfbt-deck-pos", "-1");
				} else {
					item.setAttribute("data-pfbt-deck-pos", String(offset));
				}
			});
			status.textContent = "Card " + (active + 1) + " of " + items.length;
		};

		prev.addEventListener("click", () => {
			active = (active - 1 + items.length) % items.length;
			update();
		});

		next.addEventListener("click", () => {
			active = (active + 1) % items.length;
			update();
		});

		gallery.addEventListener("keydown", (e) => {
			if (e.key === "ArrowLeft") {
				active = (active - 1 + items.length) % items.length;
				update();
			} else if (e.key === "ArrowRight") {
				active = (active + 1) % items.length;
				update();
			}
		});

		update();
	});
});
