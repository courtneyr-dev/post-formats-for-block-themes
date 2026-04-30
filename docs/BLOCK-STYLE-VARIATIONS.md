# Block Style Variations

PFBT ships block style variations across four core blocks:

- **v2.1.0:** 16 image variations on `core/image` and 20 gallery variations on `core/gallery`.
- **v2.2.0:** 16 quote variations on `core/quote` AND `core/pullquote`.

Each set is gated behind its own independent feature flag — sites can opt into image/gallery without quote, or vice versa.

## Enabling the variations

Both feature flags default to **off**. Turn each on independently:

```php
// v2.1.0 image + gallery variations
define( 'PFBT_FEATURE_IMAGE_GALLERY_STYLES', true );
add_filter( 'pfbt_feature_image_gallery_styles', '__return_true' );
update_option( 'pfbt_feature_image_gallery_styles', true );

// v2.2.0 quote + pullquote variations
define( 'PFBT_FEATURE_QUOTE_STYLES', true );
add_filter( 'pfbt_feature_quote_styles', '__return_true' );
update_option( 'pfbt_feature_quote_styles', true );
```

Priority order for both flags: constant > filter > option > default. When a flag is off, the corresponding variations don't register — zero cost.

## How loading works

Each variation pairs a `register_block_style()` entry with a stylesheet handle. Per-variation CSS files live in `styles/image-variations/{slug}.css` and `styles/gallery-variations/{slug}.css` and load only when the variation is rendered on a page (WP 6.1+ conditional-loading mechanism). View modules for Interactivity-API galleries (`blocks/gallery-variations/{slug}/view.js`) are also registered against the variation's class so they only load when the gallery is in use.

Total CSS shipped (all 36 variations + IA modules) is well under the 60 KB / 12 KB caps in the v2.1.0 brief.

## Image variations (16)

### Everyday / Content (5)

| Slug | When to use |
|---|---|
| `rounded` | Soft rounded corners. Works for any image; minimum-fuss styling. |
| `circle` | Portraits and avatar-style images. Forces 1:1 + circular clip. |
| `soft-shadow` | When you want the image to lift off the page without a border. Drop-shadow follows transparent silhouettes (PNG/SVG). |
| `tinted-border` | When the image needs a brand-colored frame. 3px brand border with a 4px white inner mat. |
| `caption-card` | When you want image + caption to read as one unified card. **Used as the default in `patterns/image.php`.** |

### Nostalgic / Tactile (5)

| Slug | When to use |
|---|---|
| `polaroid` | Personal/casual posts; family photos, travel snaps. White card with wide bottom edge for the caption, slight rotation that straightens on hover. |
| `postcard` | Travel and place-based content. 4:3 crop with a dashed inline-end border and cream tint. |
| `photo-strip` | Photobooth-style memory grids; pairs and quartets. CSS-only divider bars. |
| `magazine-cutout` | Editorial collages and zine layouts. SVG-mask torn edge. |
| `index-card` | Recipe and how-to content. Ruled-paper background with a corner tab. |

### Editorial / Print (3)

| Slug | When to use |
|---|---|
| `headline-crop` | Hero images and section banners. 21:9 cinematic crop with a small uppercase caption underneath. |
| `duotone-mood` | Mood pieces. CSS-filter-chain duotone; override `--pfbt-duotone-hue` per-block to retint. |
| `halftone` | Editorial posts that need a print-comic feel. Subtle dot-pattern overlay. |

### Device / Mockup (3)

| Slug | When to use |
|---|---|
| `phone-frame` | Mobile-first product showcases. Stylized phone frame with notch. 9:19.5. |
| `browser-window` | Web/SaaS screenshots. Browser chrome with traffic-light dots. |
| `code-editor` | Code screenshots. Dark editor chrome with sidebar. |

## Gallery variations (20)

### CSS-only (8)

| Slug | When to use |
|---|---|
| `justified-rows` | Mixed-aspect-ratio collections. Items balance into uniform-height rows. **Default in `patterns/gallery.php`.** |
| `square-tile` | Avatar grids, icon sets, product tiles. 1:1 with cover crop. |
| `polaroid-stack` | Personal photo collections. Each item gets a polaroid frame with randomized rotation. |
| `filmstrip-snap` | Long sequential collections. Single horizontal row with native scroll-snap. |
| `caption-prominent` | When captions matter. Cards with image + larger real-text caption. |
| `duotone-mood-gallery` | Mood collections. Whole gallery duotone-tinted. |
| `mosaic-spotlight` | 5-image highlight reels. 1-large + 4-small. |
| `bordered-grid` | Brutalist look. Thick brand-color borders between cells. |

### Interactivity API (8) — vanilla JS, no third-party libraries

| Slug | When to use |
|---|---|
| `masonry-cascade` | Mixed-height collections. CSS multi-column with native masonry @supports progressive enhancement. **No JS required.** |
| `headline-mosaic` | Editorial highlight reels. Set `data-shape="A\|B\|C"` to pick the layout. |
| `lightbox-slideshow` | When the gallery should expand to a fullscreen viewer. Click any image to open a focus-trapped dialog with prev/next + arrow-key/Esc keyboard nav + live "image X of Y" announcements. |
| `before-after-pairs` | Diff comparisons. Pairs of images become draggable comparison sliders (native range input, keyboard-accessible). |
| `filter-tags` | Filterable collections. Chip filter bar built from per-image tags. **Data binding required:** set `data-pfbt-tags="x y z"` on each image. |
| `lookbook-hotspots` | Product/style imagery with annotations. Hotspot buttons over each image. **Data binding required:** set `data-pfbt-hotspots='[{"x":25,"y":40,"label":"T-shirt"}]'` on each image. |
| `card-deck-swipe` | Sequential card stacks. Prev/next chevrons + arrow-key navigation. Live region announces position. |
| `photo-essay-scroll` | Scrollytelling. Sticky media column synced to scroll-step text. CSS sticky does the work; the IA module is a progressive enhancement. |
| `comparison-pairs` | Side-by-side analysis. Optional synced hover crosshair (disabled under reduced-motion). |

### Advanced (3) — SSR fallbacks

| Slug | When to use |
|---|---|
| `map-pinned-geo` | Geo-tagged collections. **v2.1.0 ships the SSR text-list fallback** per Hard Rule 5 (no third-party JS libraries). Tile rendering is a v2.2 roadmap item. |
| `panorama-360` | Panoramic photos. **v2.1.0 ships the SSR flat-image fallback** with horizontal scroll-snap. WebGL viewer is a v2.2 roadmap item. |
| `dynamic-query-gallery` | Auto-curated galleries fed by a Query Loop. Set `data-pfbt-layout="grid\|tile\|mosaic"` to pick the inner layout. |

## Quote variations (16)

Variations register on **both** `core/quote` and `core/pullquote`. Each one resets the default core chrome (left border, padding, italic body) before drawing its own design — no inner block structure required from authors.

**Citation handling:** every variation positions and styles the `<cite>` to fit its visual context. When citation is empty, the element is hidden via `cite:empty { display: none; }` so no awkward gap remains.

### Tactile / Nostalgic (8)

| Slug | When to use |
|---|---|
| `post-it` | Personal notes, reminders, scratch ideas. Yellow square card with folded corner and slight rotation. Citation rendered inside, em-dash prefixed. |
| `notebook-scrap` | Field notes, drafted thoughts. Torn top edge, ruled lines, red margin line. Citation as a signature line. |
| `typewriter` | Manuscript-style quotes, retro vibes. Monospace on cream paper with faint ink-bleed. Citation prefixed `--`. |
| `napkin` | Casual ideas, "scrawled-on-the-back-of" feel. Soft white card with coffee-ring stain and torn corner. |
| `library-card` | Book quotes, references. Cream card with ruled lines and a dark header bar. Citation as the all-caps title. |
| `chalkboard` | Classroom feel, lecture quotes. Dark slate with chalk-style text and wooden frame. Print inverts to plain. |
| `whiteboard` | Brainstorm quotes. White card with blue-marker body and red-marker citation. |
| `postcard` | Travel quotes, "wish you were here" feel. Landscape card with vertical divider — quote on inline-start, citation + stamp on inline-end. |

### Editorial / Print (4)

| Slug | When to use |
|---|---|
| `magazine-pull` | Big editorial moments. Large display font with oversized decorative quote mark behind the text. |
| `broadsheet` | Classical centered quote treatment. Centered serif with double-rule top and bottom and corner quotation marks. |
| `decorative-marks` | Editorial restraint with one design beat. Oversized opening quote mark in the upper-inline-start corner. |
| `side-rule-editorial` | The minimal default. Thick brand-color rule on inline-start, clean serif body. **Used as the default in `patterns/quote.php`.** |

### Conversational / Digital (3)

| Slug | When to use |
|---|---|
| `speech-bubble` | Conversational extracts, dialogue. Rounded card with a tail. Citation rendered below as the speaker (not inside). |
| `message-bubble` | SMS/chat-style snippets. Accent-colored rounded rectangle. Citation below as sender label. |
| `comment-card` | Quoted comments, testimonials. Card with decorative initial avatar — set `data-pfbt-avatar="A"` on the block via Advanced → HTML attribute (or via Block Bindings). Citation as username. |

### Decorative (1)

| Slug | When to use |
|---|---|
| `plaque` | Award-style quotes, dedication blocks. Engraved metal plaque with ornamental border and embossed text. |

## Customization

Every aesthetic value in every variation routes through a CSS custom property with a sensible fallback:

```css
.wp-block-image.is-style-tinted-border img {
	border-color: var(--wp--preset--color--primary, #2271b1);
	border-width: var(--pfbt-tinted-border-width, 3px);
}
```

To override per-site, set the variable in your theme's stylesheet, theme.json (custom block-styles), or a child theme:

```css
:root {
	--pfbt-tinted-border-width: 5px;
	--pfbt-window-dot-close: var(--cr-orange);
}
```

For per-block overrides, use the block's Advanced → Additional CSS Class field and apply a custom selector via your theme.

## Accessibility

Every variation has been authored against the v2.1.0 brief's a11y gates:

- Reduced-motion guards on every transition and transform (rotation set to 0 under `prefers-reduced-motion: reduce`).
- Logical CSS properties throughout for RTL safety.
- Lightbox traps focus, supports Esc/arrow keys, restores focus on close, announces position via aria-live.
- Filter chips are real `<button>` elements with `aria-pressed`.
- Hotspot buttons are real `<button>` elements with aria-label.
- Captions use real `<figcaption>`; alt text remains the canonical accessibility text for image content.
- All interactive states have visible focus outlines.
- Print stylesheets in every variation flatten decorative effects to readable plain-document output.

## Adding your own variations

See [`ADDING-A-VARIATION.md`](./ADDING-A-VARIATION.md).
