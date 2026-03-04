/**
 * Interactivity API store for Chat Log block
 *
 * Replaces vanilla JS (view.js) with declarative state management
 * for thread collapse/expand and timestamp visibility toggle.
 * Requires WordPress 6.5+ with Interactivity API support.
 *
 * @since 1.2.0
 * @package PostFormatsBlockThemes
 */

import { store, getContext } from "@wordpress/interactivity";

store("post-formats/chatlog", {
  actions: {
    toggleThread() {
      const ctx = getContext();
      ctx.isThreadExpanded = !ctx.isThreadExpanded;
    },
    toggleTimestamps() {
      const ctx = getContext();
      ctx.showTimestamps = !ctx.showTimestamps;
    },
  },
});
