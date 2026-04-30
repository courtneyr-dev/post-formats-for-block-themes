/**
 * Lookbook Hotspots — Interactivity API view module.
 *
 * For each image with `data-pfbt-hotspots` (JSON array of {x, y, label}),
 * build an absolutely-positioned <button> for each entry. Each hotspot
 * has its own popover with the label.
 *
 * Hotspot data shape: [{ "x": 25, "y": 40, "label": "T-shirt" }, ...]
 * Coordinates are percentages relative to the image (0-100).
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

document.addEventListener("DOMContentLoaded", () => {
	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-lookbook-hotspots",
	);

	galleries.forEach((gallery) => {
		const items = gallery.querySelectorAll(".wp-block-image");
		items.forEach((item) => {
			const raw = item.dataset.pfbtHotspots;
			if (!raw) return;

			let hotspots;
			try {
				hotspots = JSON.parse(raw);
			} catch (err) {
				return;
			}
			if (!Array.isArray(hotspots) || hotspots.length === 0) return;

			hotspots.forEach((spot, index) => {
				if (typeof spot.x !== "number" || typeof spot.y !== "number") return;

				const button = document.createElement("button");
				button.type = "button";
				button.className = "pfbt-hotspot";
				button.style.insetInlineStart = spot.x + "%";
				button.style.insetBlockStart = spot.y + "%";
				button.setAttribute(
					"aria-label",
					typeof spot.label === "string" ? spot.label : "Hotspot " + (index + 1),
				);

				const popover = document.createElement("span");
				popover.className = "pfbt-hotspot__popover";
				popover.setAttribute("role", "tooltip");
				popover.textContent =
					typeof spot.label === "string" ? spot.label : "";
				button.appendChild(popover);

				item.appendChild(button);
			});
		});
	});
});
