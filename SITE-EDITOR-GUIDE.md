# Customizing Post Formats Through Site Editor

**Last Updated:** November 28, 2025

This guide shows you how to customize each post format's appearance using the WordPress Site Editor interface—no code required!

---

## 🎨 Quick Start: Customizing Format Colors

### Step 1: Access the Site Editor

1. Go to **Appearance → Editor** in your WordPress admin
2. Click **Styles** (the paintbrush icon in the top-right)
3. Click **Colors** in the panel that opens

### Step 2: Find Format-Specific Colors

Scroll through the color palette. You'll see colors named for each format:

- **Aside Format Background** - Background color for Aside posts
- **Aside Format Border** - Border color for Aside posts
- **Status Format Background** - Background color for Status posts
- **Link Format Background** - Background color for Link posts
- **Link Format Border** - Border color for Link posts
- **Quote Format Border** - Border/accent color for Quote posts
- **Quote Format Accent** - Quote mark color for Quote posts
- **Gallery Format Border** - Border color for Gallery posts
- **Image Format Border** - Frame color for Image posts
- **Video Format Background** - Background color for Video posts
- **Audio Format Background** - Background color for Audio posts
- **Chat Format Background** - Background color for Chat posts

### Step 3: Customize a Format

**Example: Changing the Link Format to Purple**

1. In Site Editor → Styles → Colors
2. Find **"Link Format Border"**
3. Click on the color swatch
4. Choose your purple color (e.g., #8B5CF6)
5. Find **"Link Format Background"**
6. Click and choose a light purple (e.g., #F5F3FF)
7. Click **Save** in the top-right

Now all Link format posts will have purple accents! 🎉

---

## 📍 Where Each Format Color Is Used

### Aside Format

**Aside Format Background:**
- Applied to the entire content area
- Creates a subtle highlight for aside posts

**Aside Format Border:**
- Left edge border (or right for RTL languages)
- Icon color

### Status Format

**Status Format Background:**
- Background color for the entire status update
- Creates a Twitter/Mastodon-style highlight

### Link Format

**Link Format Background:**
- Background color for the first paragraph containing the link
- Creates a contained box effect

**Link Format Border:**
- Border around the link box
- Color of the underline decoration
- Color of the arrow (→) after links

### Quote Format

**Quote Format Border:**
- Thick left edge border (or right for RTL)
- Creates the classic quote bar

**Quote Format Accent:**
- Color of the large opening quotation mark (")
- Decorative element

### Gallery Format

**Gallery Format Border:**
- Border around gallery blocks
- Creates a framed effect

### Image Format

**Image Format Border:**
- Thick frame around featured images
- Creates a photo frame effect

### Video Format

**Video Format Background:**
- Background behind video embeds
- Padding area color

### Audio Format

**Audio Format Background:**
- Background behind audio players
- Works with AblePlayer and Podlove

### Chat Format

**Chat Format Background:**
- Background behind chat log blocks
- Highlights conversation transcripts

---

## 🎯 Complete Customization Workflow

### Method 1: Quick Color Changes (Easiest)

**Time: 2 minutes**

1. **Appearance → Editor → Styles → Colors**
2. Find the format colors you want to change
3. Click each color and choose new ones
4. Click **Save**

✅ Best for: Quick visual updates, trying different color schemes

### Method 2: Testing in Context

**Time: 5 minutes**

1. Create a test post with a specific format (e.g., Link format)
2. Open **Appearance → Editor → Templates → Single Post**
3. Click **Styles** (paintbrush icon)
4. Click **Colors**
5. Change format colors while viewing the post preview
6. See changes live in the template view
7. Click **Save**

✅ Best for: Seeing exactly how changes will look

### Method 3: Using Additional CSS (Advanced)

**Time: 5-10 minutes**

If you need more control than just colors:

1. **Appearance → Customize → Additional CSS**
2. Add custom CSS overrides:

```css
/* Example: Make Link format stand out more */
.format-link .wp-block-post-content > p:first-of-type {
	/* Your theme.json colors are available as CSS variables */
	background: var(--wp--preset--color--format-link-bg);
	border: 0.25rem solid var(--wp--preset--color--format-link-border);
	padding: 2rem;
	border-radius: 1rem;
	box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.1);
}

/* Example: Change Status format typography */
.format-status .wp-block-post-content {
	background: var(--wp--preset--color--format-status-bg);
	font-size: clamp(1.5rem, 2vw, 2rem);
	font-weight: 600;
}

/* Example: Customize Quote format */
.format-quote .wp-block-quote {
	border-inline-start-width: 0.5rem;
	border-inline-start-color: var(--wp--preset--color--format-quote-border);
}
```

3. Click **Publish**

✅ Best for: Advanced styling beyond colors

---

## 💡 Example Scenarios

### Scenario 1: Brand Color Scheme

**Goal:** Match your brand colors across all formats

**Steps:**
1. Go to **Appearance → Editor → Styles → Colors**
2. Change all "Border" colors to your primary brand color
3. Change all "Background" colors to your secondary/accent color
4. Save

**Result:** All post formats now use your brand colors consistently.

### Scenario 2: Subtle vs. Bold Formats

**Goal:** Some formats subtle, others bold

**For Subtle (Aside, Status, Chat):**
- Use very light background colors (#F9F9F9)
- Use dark but low-opacity borders (#000000 at 10%)

**For Bold (Link, Quote):**
- Use saturated background colors
- Use bright, contrasting border colors

### Scenario 3: Monochrome Professional Look

**Goal:** Clean, minimal, professional appearance

**All Formats:**
- Set all backgrounds to: #F5F5F5 (light gray)
- Set all borders to: #333333 (dark gray)

**Result:** Consistent, professional look across all formats.

### Scenario 4: Colorful, Playful Blog

**Goal:** Each format has a unique color personality

**Color Assignments:**
- **Aside:** Soft blue (#E3F2FD / #2196F3)
- **Status:** Bright yellow (#FFF9C4 / #FFC107)
- **Link:** Vibrant purple (#F3E5F5 / #9C27B0)
- **Quote:** Deep orange (#FFE0B2 / #FF6F00)
- **Gallery:** Teal (#E0F2F1 / #009688)
- **Image:** Pink (#FCE4EC / #E91E63)
- **Video:** Red (#FFEBEE / #F44336)
- **Audio:** Green (#E8F5E9 / #4CAF50)
- **Chat:** Indigo (#E8EAF6 / #3F51B5)

**Steps:**
1. Open **Appearance → Editor → Styles → Colors**
2. Click each format color and set the colors above
3. Save

**Result:** Rainbow of formats, each visually distinct!

---

## 🔍 Checking Your Changes

### View on Single Posts

1. Create test posts with each format
2. View them on the front-end
3. Check that colors appear correctly

### View in Query Loops (Archives)

1. Go to your blog page
2. Check that format icons and styles appear
3. Look for the format-specific highlighting

### Test in Editor

1. Edit a post with a specific format
2. Check that the editor preview shows your colors
3. The editor styling may differ slightly from front-end (this is normal)

---

## 📱 Responsive Behavior

All format styling is responsive:

- **Desktop:** Full styling with all effects
- **Tablet:** Slightly reduced padding
- **Mobile:** Optimized spacing and text sizes

The format colors you choose will work across all screen sizes automatically.

---

## 🌍 Internationalization Support

Format styling works with all languages:

- **RTL Languages** (Arabic, Hebrew, etc.): Borders automatically flip to the correct side
- **All Languages:** Icons and formatting adapt

No additional configuration needed!

---

## ♿ Accessibility

The format colors respect accessibility features:

- **High Contrast Mode:** Borders become thicker automatically
- **Reduced Motion:** No animations
- **Screen Readers:** Format information is announced

When choosing colors, make sure there's enough contrast between:
- Text and background
- Borders and background

**Tip:** Use the [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/) to verify your color combinations meet WCAG standards.

---

## 🛠️ Troubleshooting

### Problem: Color Changes Don't Appear

**Solutions:**
1. Clear your browser cache (Cmd/Ctrl + Shift + R)
2. Clear WordPress cache if using a caching plugin
3. Make sure you clicked **Save** in the Site Editor
4. Check that the plugin is active

### Problem: Some Formats Look Wrong

**Check:**
1. Are you using a block theme? (Plugin requires block themes)
2. Is the post actually set to that format? (Check in editor sidebar)
3. Does your theme override the plugin styles? (Try a default block theme to test)

### Problem: Colors Are Different in Editor vs. Front-End

**This is normal!** The WordPress editor uses slightly different styling than the front-end. Always check the front-end for the true appearance.

---

## 🎓 Advanced: Understanding CSS Custom Properties

When you change colors in Site Editor, WordPress creates CSS custom properties like:

```css
--wp--preset--color--format-aside-bg: #f0f0f1;
--wp--preset--color--format-aside-border: #1e1e1e;
```

These are automatically used in the plugin's stylesheet:

```css
.format-aside .entry-content {
	background: var(--wp--preset--color--format-aside-bg);
	border-color: var(--wp--preset--color--format-aside-border);
}
```

This means:
- ✅ Changes in Site Editor instantly update all formats
- ✅ No need to edit CSS files
- ✅ Works with all block themes
- ✅ Export/import with theme styles

---

## 📚 Quick Reference Table

| What to Customize | Where to Go | What Changes |
|-------------------|-------------|--------------|
| **Format Colors** | Appearance → Editor → Styles → Colors | All format backgrounds, borders, accents |
| **Global Spacing** | Appearance → Editor → Styles → Layout | Padding, margins (affects all formats) |
| **Typography** | Appearance → Editor → Styles → Typography | Font sizes (status format uses large size) |
| **View in Context** | Appearance → Editor → Templates → Single Post | See how formats look in actual template |
| **Quick Test** | Create post → Set format → View on front-end | Verify changes work |

---

## 🎉 You're Ready!

You can now customize every post format through the WordPress Site Editor without touching code. The colors you choose will:

- ✅ Apply to single posts
- ✅ Apply to archive/blog pages
- ✅ Work across all devices
- ✅ Support all languages
- ✅ Maintain accessibility
- ✅ Look great in dark mode

**Have fun making your post formats uniquely yours!** 🎨

---

## 💬 Need More Help?

1. Check `STYLING-GUIDE.md` for CSS customization examples
2. Check `STYLING-SUMMARY.md` for quick technical reference
3. Review `format-styles.css` to see how colors are used
4. Test in a staging environment before applying to live site

**Remember:** You can always reset to defaults by removing your custom colors in the Site Editor!
