/**
 * Lightbox Slideshow — Interactivity API view module.
 *
 * Activates on any .wp-block-gallery.is-style-lightbox-slideshow.
 * Each .wp-block-image gets a real <button class="pfbt-lightbox-trigger">
 * appended to it that opens a fullscreen native <dialog> (.showModal()).
 * The dialog supports:
 *
 *   - prev/next nav via on-screen buttons
 *   - keyboard nav: ArrowLeft/ArrowRight, Home/End; Esc-to-close and Tab
 *     focus cycling come from the native <dialog> for free
 *   - focus restored to the button that opened the dialog on close
 *   - live region "image X of Y" announcement on each navigation
 *
 * Implementation note: the dialog is built via createElement (no
 * innerHTML), so even if any string crossed the boundary it could
 * never be parsed as HTML.
 *
 * @package PostFormatsBlockThemes
 * @since 2.1.0
 */

import { store, getContext } from "@wordpress/interactivity";

const NAMESPACE = "post-formats/lightbox-slideshow";

/**
 * Build (or reuse) the dialog element appended to document.body.
 *
 * @returns {HTMLElement} Dialog root with .pfbt-lightbox-dialog children.
 */
function ensureDialog() {
	let dialog = document.querySelector(".pfbt-lightbox-dialog");
	if (dialog) {
		return dialog;
	}
	dialog = document.createElement("dialog");
	dialog.className = "pfbt-lightbox-dialog";
	dialog.setAttribute("role", "dialog");
	dialog.setAttribute("aria-modal", "true");
	dialog.setAttribute("aria-label", "Image lightbox");

	const close = document.createElement("button");
	close.type = "button";
	close.className = "pfbt-lightbox-dialog__close";
	close.setAttribute("aria-label", "Close lightbox");
	close.textContent = "×";

	const prev = document.createElement("button");
	prev.type = "button";
	prev.className = "pfbt-lightbox-dialog__prev";
	prev.setAttribute("aria-label", "Previous image");
	prev.textContent = "‹";

	const img = document.createElement("img");
	img.className = "pfbt-lightbox-dialog__image";
	img.alt = "";

	const next = document.createElement("button");
	next.type = "button";
	next.className = "pfbt-lightbox-dialog__next";
	next.setAttribute("aria-label", "Next image");
	next.textContent = "›";

	const counter = document.createElement("div");
	counter.className = "pfbt-lightbox-dialog__counter";
	counter.setAttribute("aria-live", "polite");

	dialog.appendChild(close);
	dialog.appendChild(prev);
	dialog.appendChild(img);
	dialog.appendChild(next);
	dialog.appendChild(counter);

	// Native <dialog> fires "close" both for explicit close() calls and for
	// the browser's own Escape-key handling, so returning focus here covers
	// both paths from a single place.
	dialog.addEventListener("close", () => {
		state.isOpen = false;
		const returnTarget = state.triggerButton || state.trigger;
		if (returnTarget && typeof returnTarget.focus === "function") {
			returnTarget.focus();
		}
	});

	document.body.appendChild(dialog);
	return dialog;
}

/**
 * Read all images out of a gallery wrapper as { src, alt } pairs.
 *
 * @param {HTMLElement} gallery
 * @returns {Array<{src: string, alt: string}>}
 */
function readGalleryImages(gallery) {
	const items = gallery.querySelectorAll(".wp-block-image img");
	return Array.from(items).map((img) => ({
		src: img.currentSrc || img.src,
		alt: img.alt || "",
	}));
}

const { state } = store(NAMESPACE, {
	state: {
		index: 0,
		images: [],
		isOpen: false,
		gallery: null,
		trigger: null,
		triggerButton: null,
	},

	actions: {
		open(event) {
			const triggerButton = event.currentTarget;
			const item = triggerButton.closest(".wp-block-image");
			if (!item) return;
			const gallery = item.closest(".is-style-lightbox-slideshow");
			if (!gallery) return;

			state.images = readGalleryImages(gallery);
			state.gallery = gallery;
			state.trigger = item;
			state.triggerButton = triggerButton;
			const items = Array.from(
				gallery.querySelectorAll(".wp-block-image"),
			);
			state.index = Math.max(0, items.indexOf(item));
			state.isOpen = true;
			actions.render();
		},

		close() {
			const dialog = document.querySelector(".pfbt-lightbox-dialog");
			if (dialog && dialog.open) {
				dialog.close();
			}
		},

		next() {
			if (!state.images.length) return;
			state.index = (state.index + 1) % state.images.length;
			actions.render();
		},

		prev() {
			if (!state.images.length) return;
			state.index =
				(state.index - 1 + state.images.length) % state.images.length;
			actions.render();
		},

		render() {
			const dialog = ensureDialog();
			const img = dialog.querySelector(".pfbt-lightbox-dialog__image");
			const counter = dialog.querySelector(".pfbt-lightbox-dialog__counter");
			const cur = state.images[state.index];
			if (!cur) return;

			// All-text assignment paths — no innerHTML anywhere.
			img.src = cur.src;
			img.alt = cur.alt;
			counter.textContent = "Image " + (state.index + 1) + " of " + state.images.length;
			if (!dialog.open) {
				dialog.showModal();
			}

			const close = dialog.querySelector(".pfbt-lightbox-dialog__close");
			const prev = dialog.querySelector(".pfbt-lightbox-dialog__prev");
			const next = dialog.querySelector(".pfbt-lightbox-dialog__next");
			if (!dialog.dataset.pfbtBound) {
				close.addEventListener("click", actions.close);
				prev.addEventListener("click", actions.prev);
				next.addEventListener("click", actions.next);
				// Escape-to-close and Tab focus cycling are handled natively
				// by <dialog>.showModal(); only image navigation is custom.
				document.addEventListener("keydown", (e) => {
					if (!state.isOpen) return;
					if (e.key === "ArrowRight") actions.next();
					if (e.key === "ArrowLeft") actions.prev();
					if (e.key === "Home") {
						state.index = 0;
						actions.render();
					}
					if (e.key === "End") {
						state.index = state.images.length - 1;
						actions.render();
					}
				});
				dialog.dataset.pfbtBound = "true";
			}
			close.focus();
		},
	},
});

const { actions } = store(NAMESPACE);

document.addEventListener("DOMContentLoaded", () => {
	const galleries = document.querySelectorAll(
		".wp-block-gallery.is-style-lightbox-slideshow",
	);
	galleries.forEach((gallery) => {
		const items = Array.from(gallery.querySelectorAll(".wp-block-image"));
		items.forEach((item, index) => {
			const img = item.querySelector("img");
			const alt = img && img.alt ? img.alt.trim() : "";
			const label = alt
				? "Open " + alt + " in lightbox"
				: "Open image " + (index + 1) + " in lightbox";

			const trigger = document.createElement("button");
			trigger.type = "button";
			trigger.className = "pfbt-lightbox-trigger";
			trigger.setAttribute("aria-label", label);
			trigger.addEventListener("click", actions.open);
			item.appendChild(trigger);
		});
	});
});
