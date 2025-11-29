# Post Format Customization - Quick Start

**3-Minute Guide to Customizing Post Formats**

---

## 🎯 The Basics

Each post format (Aside, Status, Link, Quote, Gallery, Image, Video, Audio, Chat) now has its own customizable colors that you can change through the WordPress Site Editor—**no coding required!**

---

## ⚡ Quick Start (60 Seconds)

### 1. Open Site Editor
```
WordPress Admin → Appearance → Editor
```

### 2. Click Styles
- Look for the **paintbrush icon** in the top-right corner
- Click it

### 3. Click Colors
- You'll see a panel with color options
- Scroll through the list

### 4. Find Format Colors
You'll see colors like:
- "Aside Format Background"
- "Link Format Border"
- "Quote Format Accent"
- Etc.

### 5. Change a Color
- Click on any format color
- Choose a new color from the picker
- See the preview update

### 6. Save
- Click **Save** in the top-right
- Done! ✓

---

## 🎨 What Each Color Controls

| Color Name | What It Changes |
|------------|-----------------|
| **Aside Format Background** | Background behind aside posts |
| **Aside Format Border** | Left edge stripe on aside posts |
| **Status Format Background** | Background behind status updates |
| **Link Format Background** | Background of link boxes |
| **Link Format Border** | Border around links + arrow color |
| **Quote Format Border** | Left edge bar on quotes |
| **Quote Format Accent** | Large quotation mark (") color |
| **Gallery Format Border** | Border around galleries |
| **Image Format Border** | Frame around featured images |
| **Video Format Background** | Background behind videos |
| **Audio Format Background** | Background behind audio players |
| **Chat Format Background** | Background behind chat logs |

---

## 📍 Where to Find It

```
Step-by-step navigation:
1. WordPress Admin
   └─ 2. Appearance
      └─ 3. Editor
         └─ 4. Styles (paintbrush icon)
            └─ 5. Colors
               └─ 6. Scroll to format colors
                  └─ 7. Click and change!
```

---

## 💡 Quick Examples

### Make Link Format Purple
1. Go to: Appearance → Editor → Styles → Colors
2. Find: "Link Format Border"
3. Change to: `#8B5CF6` (purple)
4. Find: "Link Format Background"
5. Change to: `#F5F3FF` (light purple)
6. Save

### Make All Formats Match Brand Colors
1. Go to: Appearance → Editor → Styles → Colors
2. For each format:
   - Set "Background" to your light brand color
   - Set "Border" to your main brand color
3. Save

Result: Consistent branding across all post types!

---

## ✅ Checklist: After Changing Colors

- [ ] Save in Site Editor
- [ ] Clear browser cache (Cmd/Ctrl + Shift + R)
- [ ] View a post with that format on front-end
- [ ] Check it looks good on mobile
- [ ] Test in dark mode (if your theme supports it)

---

## 🔧 Where Formats Appear

Your format colors show up in:

1. **Single Posts** - When viewing individual posts
2. **Blog/Archive Pages** - Post listings with format icons
3. **Query Loop Blocks** - Any custom post grids

All automatically styled with your chosen colors!

---

## 🚀 Next Steps

### Want More Control?

Check out these guides:

1. **`SITE-EDITOR-GUIDE.md`** - Comprehensive customization guide
2. **`STYLING-GUIDE.md`** - Advanced CSS customization
3. **`STYLING-SUMMARY.md`** - Technical reference

### Want Examples?

Common scenarios:

- **Professional/Minimal:** Use grays (#F5F5F5, #333333)
- **Colorful/Playful:** Use rainbow colors (different for each format)
- **Brand Colors:** Use your company's color palette
- **Monochrome:** Use single color with different shades

---

## 🎓 Understanding the System

### How It Works

1. **theme.json** defines color variables
2. **format-styles.css** uses those variables
3. **Site Editor** lets you change the variables
4. **Your site** updates automatically!

### The Magic

When you change a color in Site Editor, WordPress creates a CSS variable:

```css
--wp--preset--color--format-link-border: #8B5CF6;
```

The plugin uses this variable everywhere that format appears:

```css
.format-link {
  border-color: var(--wp--preset--color--format-link-border);
}
```

**Result:** Change once in Site Editor, updates everywhere! ✨

---

## 📱 Mobile Friendly

All format styling is responsive:
- Full effects on desktop
- Optimized spacing on tablet
- Streamlined on mobile

Your colors work everywhere automatically.

---

## ♿ Accessibility Built-In

Format colors respect:
- High contrast mode (borders get thicker)
- Reduced motion preferences (no animations)
- Screen reader announcements (format type spoken)
- Color contrast requirements (when you choose good colors)

**Tip:** Use [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/) to verify your colors meet WCAG AA standards (4.5:1 contrast ratio).

---

## 💬 Common Questions

### Q: Do I need to code?
**A:** No! Everything is done through the visual Site Editor.

### Q: Will my changes affect all posts?
**A:** Changes affect all posts with that specific format. Aside posts get Aside colors, Link posts get Link colors, etc.

### Q: Can I make formats look the same?
**A:** Yes! Just set all formats to the same colors.

### Q: Can I reset to defaults?
**A:** Yes! Remove your custom colors in Site Editor to return to defaults.

### Q: Will updates erase my changes?
**A:** No! Your color choices are stored in your theme settings, not in plugin files.

---

## 🎉 That's It!

You now know how to customize post formats through the Site Editor. It's:

- ✅ **Visual** - No code needed
- ✅ **Fast** - Changes take seconds
- ✅ **Safe** - Can always undo
- ✅ **Powerful** - Controls entire site
- ✅ **Accessible** - Works for everyone

**Go make your formats beautiful!** 🌟

---

## 📞 Need Help?

1. **Can't find the colors?** Make sure you're in Appearance → Editor → Styles → Colors and scroll down
2. **Colors not changing?** Clear cache and hard refresh (Cmd/Ctrl + Shift + R)
3. **Want more control?** Read `STYLING-GUIDE.md` for CSS options
4. **Something broken?** Check that plugin is active and you're using a block theme

**Remember:** Always test changes on a staging site before applying to production!
