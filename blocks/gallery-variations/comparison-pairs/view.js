/**
 * Comparison Pairs — Interactivity API view module.
 *
 * Walks consecutive .wp-block-image children in pairs, attaches a
 * synced hover crosshair: when the user moves over one image, a
 * crosshair pseudo-element appears at the matching relative position
 * on its pair. CSS handles the visual; JS just tracks the cursor.
 *
 * Reduced-motion users see no crosshair (the CSS guard already
 * disables visibility under prefers-reduced-motion: reduce).
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

document.addEventListener("DOMContentLoaded", () => {
	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-comparison-pairs",
	);

	galleries.forEach((gallery) => {
		const items = Array.from(gallery.querySelectorAll(".wp-block-image"));
		for (let i = 0; i + 1 < items.length; i += 2) {
			const a = items[i];
			const b = items[i + 1];

			const crosshairA = document.createElement("span");
			crosshairA.className = "pfbt-comparison-crosshair";
			crosshairA.setAttribute("aria-hidden", "true");
			a.appendChild(crosshairA);

			const crosshairB = document.createElement("span");
			crosshairB.className = "pfbt-comparison-crosshair";
			crosshairB.setAttribute("aria-hidden", "true");
			b.appendChild(crosshairB);

			const sync = (source, syncedOn) => {
				return (event) => {
					const rect = source.getBoundingClientRect();
					const x = ((event.clientX - rect.left) / rect.width) * 100;
					const y = ((event.clientY - rect.top) / rect.height) * 100;
					const cross = syncedOn.querySelector(".pfbt-comparison-crosshair");
					if (cross) {
						cross.style.insetInlineStart = x + "%";
						cross.style.insetBlockStart = y + "%";
					}
				};
			};

			a.addEventListener("pointermove", sync(a, b));
			b.addEventListener("pointermove", sync(b, a));
		}
	});
});
