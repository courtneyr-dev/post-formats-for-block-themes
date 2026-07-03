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

## Color: the `--pfbt-*` token contract

Every color in every variation resolves through the same three-level chain:

```
--pfbt-{variation}-{role}          ← site/theme override token (always wins)
  → theme palette preset(s)        ← palette-mapped colors only
    → fixed default                 ← skeuomorphic colors, and the last resort
```

In CSS that looks like:

```css
/* Palette-mapped: follows the active theme's palette by default */
.wp-block-image.is-style-tinted-border img {
	border-color: var(--pfbt-tinted-border-color, var(--wp--preset--color--primary, var(--wp--preset--color--accent-1, #2271b1)));
}

/* Fixed skeuomorph: photo paper is white on any palette */
.wp-block-image.is-style-polaroid {
	background: var(--pfbt-polaroid-paper, #ffffff);
}
```

### Palette-mapped vs. fixed

**Palette-mapped variations** pick up the active theme's colors with no configuration. Each color role tries two common palette slug conventions before falling back:

| Role | Preset chain | Fallback |
|---|---|---|
| Accent, decorative (rules, borders — nothing sits on it) | `primary` → `accent-1` | `#2271b1` |
| Accent, solid fill carrying text (bubbles, chips, buttons) | `primary` → `contrast` | `#2271b1` |
| Body ink | `main` → `contrast` | `#0a0a0a` |
| Muted text / captions | `secondary` → `contrast` | `#666666` |
| Card / surface bg | `tertiary` → `base` | `#f5f5f5` |
| Paper / page bg | `base` | `#ffffff` |
| Hairline borders | `border-light` → `tertiary` | `#e5e5e5` |
| Focus ring on accent | `primary-accent` → `base` | `#e8f0fa` |

The first slug set matches themes like Ollie (`primary`, `main`, `secondary`, `tertiary`, `base`); the second matches the Twenty Twenty-Four/Five convention (`contrast`, `base`, `accent-1`). Themes using other slug names should set the `--pfbt-*` tokens directly (see below).

Solid fills that carry text fall back to `contrast` rather than `accent-1` on purpose: `accent-N` slugs make no promise about what text color is readable on them (Twenty Twenty-Five's `accent-1` is a pale highlight yellow), while `contrast`-filled elements are always readable with `base` text — in light and dark palettes alike.

Palette-mapped: **quote** magazine-pull, broadsheet, decorative-marks, side-rule-editorial, speech-bubble, message-bubble, comment-card · **image** caption-card, tinted-border, headline-crop, browser-window (chrome only) · **gallery** caption-prominent, bordered-grid, filter-tags, lookbook-hotspots, card-deck-swipe, before-after-pairs, comparison-pairs, map-pinned-geo, panorama-360.

**Fixed skeuomorphs** deliberately ignore the palette — their colors are physical materials (paper, slate, brass, device hardware) that would break if a dark or brand-heavy palette leaked in. They stay put on every theme, and every one of their colors still has a `--pfbt-*` token if you want to retint them:

Fixed: **quote** post-it, notebook-scrap, typewriter, napkin, library-card, chalkboard, whiteboard, postcard, plaque · **image** polaroid, postcard, photo-strip, index-card (paper/rules; the corner tab follows the accent), phone-frame, code-editor, browser-window traffic-light dots · **gallery** polaroid-stack, lightbox-slideshow (fixed dark overlay, light controls).

Variations not listed in either group (rounded, circle, soft-shadow, justified-rows, masonry-cascade, …) paint no colors of their own — they inherit the theme completely.

### Overriding tokens

Set tokens from a block theme's `theme.json`:

```json
{
	"styles": {
		"css": ":root { --pfbt-side-rule-color: var(--wp--preset--color--brand); --pfbt-postit-bg: #ffe98a; }"
	}
}
```

…or from any stylesheet that loads on the front end:

```css
:root {
	--pfbt-tinted-border-width: 5px;
	--pfbt-window-dot-close: var(--cr-orange);
	--pfbt-code-editor-bg: #0d1117;
}
```

For per-block overrides, use the block's Advanced → Additional CSS Class field and apply a custom selector via your theme.

### Color token reference

Defaults shown as `slug chain → fallback` for palette-mapped tokens, or a literal value for fixed tokens.

**Quote / pullquote**

| Token | Paints | Default |
|---|---|---|
| `--pfbt-magazine-pull-ink` | Body text | `main` → `contrast` → `#0a0a0a` |
| `--pfbt-magazine-pull-accent` | Big quote mark, divider rule | `primary` → `accent-1` → `#2271b1` |
| `--pfbt-magazine-pull-cite` | Citation | `secondary` → `contrast` → `#666666` |
| `--pfbt-broadsheet-ink` | Body text | `main` → `contrast` → `#0a0a0a` |
| `--pfbt-broadsheet-rule` | Double rules top/bottom | `main` → `contrast` → `#0a0a0a` |
| `--pfbt-broadsheet-cite` | Citation + its rule | `secondary` → `contrast` → `#666666` |
| `--pfbt-decorative-marks-mark` | Oversized quote mark | `primary` → `accent-1` → `#2271b1` |
| `--pfbt-decorative-marks-cite` | Citation | `secondary` → `contrast` → `#666666` |
| `--pfbt-side-rule-color` | Inline-start rule | `primary` → `accent-1` → `#2271b1` |
| `--pfbt-side-rule-cite` | Citation | `secondary` → `contrast` → `#666666` |
| `--pfbt-speech-bubble-bg` | Bubble + tail | `tertiary` → `base` → `#f5f5f5` |
| `--pfbt-speech-bubble-fg` | Bubble text | `main` → `contrast` → `#0a0a0a` |
| `--pfbt-speech-bubble-cite` | Speaker line | `secondary` → `contrast` → `#666666` |
| `--pfbt-message-bg` | Bubble | `primary` → `contrast` → `#2271b1` |
| `--pfbt-message-fg` | Bubble text | `base` → `#ffffff` |
| `--pfbt-message-cite` | Sender label | `secondary` → `contrast` → `#666666` |
| `--pfbt-comment-card-bg` | Card | `tertiary` → `base` → `#f5f5f5` |
| `--pfbt-comment-card-fg` | Card text | `main` → `contrast` → `#0a0a0a` |
| `--pfbt-comment-card-meta` | Username | `secondary` → `contrast` → `#666666` |
| `--pfbt-comment-avatar-bg` / `-fg` | Initial avatar | `primary` → `contrast` → `#2271b1` / `base` → `#ffffff` |
| `--pfbt-postit-bg` / `-fg` / `-fold` | Post-it (fixed) | `#fff7a8` / `#1a1a1a` / `rgba(0,0,0,0.08)` |
| `--pfbt-notebook-bg` / `-fg` / `-rule` / `-margin` | Notebook scrap (fixed) | `#fdfaf2` / `#1a1a1a` / blue rule / `#d44` |
| `--pfbt-typewriter-bg` / `-fg` | Typewriter (fixed) | `#f6f1e4` / `#1a1a1a` |
| `--pfbt-napkin-bg` / `-fg` / `-stain` | Napkin (fixed) | `#fdfdfa` / `#1a1a1a` / coffee ring |
| `--pfbt-library-bg` / `-fg` / `-header` / `-header-fg` / `-rule` | Library card (fixed) | cream / ink / dark wood / cream / ruled line |
| `--pfbt-chalkboard-bg` / `-fg` / `-vignette` / `-frame` | Chalkboard (fixed) | slate / chalk / darker slate / wood |
| `--pfbt-whiteboard-bg` / `-marker` / `-cite-marker` | Whiteboard (fixed) | `#fefefe` / blue / red |
| `--pfbt-postcard-bg` / `-fg` / `-edge` / `-divider` / `-stamp` | Quote postcard (fixed) | aged cream set |
| `--pfbt-plaque-light` / `-mid` / `-engraved` / `-frame` | Plaque (fixed) | brass set |

**Image**

| Token | Paints | Default |
|---|---|---|
| `--pfbt-caption-card-bg` / `-fg` | Card / caption | `tertiary` → `base` → `#f5f5f5` / `main` → `contrast` → `#0a0a0a` |
| `--pfbt-tinted-border-color` | Border + outer ring | `primary` → `accent-1` → `#2271b1` |
| `--pfbt-tinted-border-mat-color` | Inner mat | `base` → `#ffffff` |
| `--pfbt-headline-crop-caption` | Caption | `secondary` → `contrast` → `#666666` |
| `--pfbt-browser-window-bg` / `-chrome` / `-border` / `-caption` | Window chrome | `base` / `tertiary` → `base` / `border-light` → `tertiary` / `secondary` → `contrast` |
| `--pfbt-window-dot-close` / `-min` / `-max` | Traffic lights (fixed) | `#fc625d` / `#fdbc40` / `#34c84a` |
| `--pfbt-polaroid-paper` / `-ink` | Polaroid (fixed; shared with gallery polaroid-stack) | `#ffffff` / `#0a0a0a` |
| `--pfbt-image-postcard-tint` / `-edge` / `-caption` | Image postcard (fixed) | `#f5f5f5` / `#666666` / `#666666` |
| `--pfbt-photo-strip-paper` | Strip paper + divider bars (fixed) | `#ffffff` |
| `--pfbt-photo-strip-caption` | Caption | `secondary` → `contrast` → `#666666` |
| `--pfbt-index-card-paper` / `-rule` | Card + ruled lines (fixed) | `#ffffff` / `#e5e5e5` |
| `--pfbt-index-card-tab` | Corner tab | `primary` → `accent-1` → `#2271b1` |
| `--pfbt-index-card-caption` | Caption | `secondary` → `contrast` → `#666666` |
| `--pfbt-phone-frame-bezel` / `-notch` / `-caption` | Device hardware (fixed) | `#0a0a0a` / `#ffffff` / `#fafafa` |
| `--pfbt-code-editor-bg` / `-fg` | Editor chrome (fixed) | `#1e1e1e` / `#fafafa` |

**Gallery**

| Token | Paints | Default |
|---|---|---|
| `--pfbt-caption-prominent-bg` / `-fg` | Card / caption | `tertiary` → `base` / `main` → `contrast` |
| `--pfbt-bordered-grid-color` | Borders + gutter | `primary` → `accent-1` → `#2271b1` |
| `--pfbt-bordered-grid-focus` | Focus outline | `primary-accent` → `base` → `#e8f0fa` |
| `--pfbt-filter-tags-chip-bg` / `-chip-fg` / `-border` | Chips at rest | `tertiary` → `base` / `main` → `contrast` / `border-light` → `tertiary` |
| `--pfbt-filter-tags-accent` / `-accent-fg` | Active chip + focus | `primary` → `contrast` / `base` → `#ffffff` |
| `--pfbt-lookbook-accent` / `-accent-fg` / `-focus` | Hotspot buttons | `primary` → `contrast` / `base` / `primary-accent` → `base` |
| `--pfbt-lookbook-popover-bg` / `-fg` / `-border` | Hotspot popover | `base` / `main` → `contrast` / `border-light` → `tertiary` |
| `--pfbt-card-deck-bg` | Card surface | `base` → `#ffffff` |
| `--pfbt-card-deck-accent` / `-accent-fg` / `-status` | Controls + counter | `primary` → `contrast` / `base` / `secondary` → `contrast` |
| `--pfbt-ba-mat` / `--pfbt-ba-divider` | Comparison mat / slider line | `main` → `contrast` / `base` |
| `--pfbt-comparison-caption` / `--pfbt-comparison-crosshair` | Caption / hover ring | `secondary` → `contrast` / `primary` → `accent-1` |
| `--pfbt-geo-bg` / `-card-bg` / `-card-fg` | Map list surfaces | `tertiary` → `base` / `base` / `main` → `contrast` |
| `--pfbt-panorama-caption` | Caption | `secondary` → `contrast` → `#666666` |
| `--pfbt-polaroid-paper` / `-ink` | Polaroid stack (fixed; shared with image polaroid) | `#ffffff` / `#0a0a0a` |
| `--pfbt-lightbox-overlay` / `-fg` / `-focus` | Lightbox (fixed dark) | `rgba(0,0,0,0.92)` / `#fafafa` / `#e8f0fa` |

These tokens are distinct from the per-format `--pfbt-format-*` tokens documented in [`DESIGN-TOKENS.md`](./DESIGN-TOKENS.md) — those paint the format containers, these paint user-selected block style variations.

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
