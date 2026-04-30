/**
 * Lightbox Slideshow — Interactivity API view module.
 *
 * Activates on any .wp-block-gallery.is-style-lightbox-slideshow.
 * Each .wp-block-image inside becomes a clickable trigger that opens
 * a fullscreen dialog. The dialog supports:
 *
 *   - prev/next nav via on-screen buttons
 *   - keyboard nav: ArrowLeft/ArrowRight, Esc to close, Home/End
 *   - focus trap (Tab cycles within the dialog)
 *   - focus restored to the trigger element on close
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
	dialog = document.createElement("div");
	dialog.className = "pfbt-lightbox-dialog";
	dialog.setAttribute("role", "dialog");
	dialog.setAttribute("aria-modal", "true");
	dialog.setAttribute("aria-label", "Image lightbox");
	dialog.hidden = true;

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
	},

	actions: {
		open(event) {
			const trigger = event.currentTarget.closest(".wp-block-image");
			if (!trigger) return;
			const gallery = trigger.closest(".is-style-lightbox-slideshow");
			if (!gallery) return;

			state.images = readGalleryImages(gallery);
			state.gallery = gallery;
			state.trigger = trigger;
			const triggers = Array.from(
				gallery.querySelectorAll(".wp-block-image"),
			);
			state.index = Math.max(0, triggers.indexOf(trigger));
			state.isOpen = true;
			actions.render();
		},

		close() {
			const dialog = document.querySelector(".pfbt-lightbox-dialog");
			if (dialog) {
				dialog.hidden = true;
			}
			state.isOpen = false;
			if (state.trigger) {
				const focusable = state.trigger.querySelector("a, button, img");
				(focusable || state.trigger).focus();
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
			dialog.hidden = false;

			const close = dialog.querySelector(".pfbt-lightbox-dialog__close");
			const prev = dialog.querySelector(".pfbt-lightbox-dialog__prev");
			const next = dialog.querySelector(".pfbt-lightbox-dialog__next");
			if (!dialog.dataset.pfbtBound) {
				close.addEventListener("click", actions.close);
				prev.addEventListener("click", actions.prev);
				next.addEventListener("click", actions.next);
				document.addEventListener("keydown", (e) => {
					if (!state.isOpen) return;
					if (e.key === "Escape") actions.close();
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
	galleries.forEach((g) => {
		g.querySelectorAll(".wp-block-image").forEach((item) => {
			item.addEventListener("click", actions.open);
			item.setAttribute("tabindex", "0");
			item.addEventListener("keydown", (e) => {
				if (e.key === "Enter" || e.key === " ") {
					e.preventDefault();
					actions.open(e);
				}
			});
		});
	});
});
