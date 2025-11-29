# Post Formats Power-Up - Quality Assurance Test Plan

**Version:** 1.0.0
**Last Updated:** November 28, 2025
**Testing Environment:** Local by Flywheel - post-formats-test
**WordPress Version:** 6.8+
**Theme:** Twenty Twenty-Five (Block Theme)
**Required Plugins:** Post Formats Power-Up, Chat Log Block

---

## Table of Contents

1. [Pre-Testing Setup](#pre-testing-setup)
2. [Template System Testing](#template-system-testing)
3. [Format Modal Testing](#format-modal-testing)
4. [Pattern Insertion Testing](#pattern-insertion-testing)
5. [Auto-Detection Testing](#auto-detection-testing)
6. [Format Switcher Testing](#format-switcher-testing)
7. [Individual Format Tests](#individual-format-tests)
8. [Repair Tool Testing](#repair-tool-testing)
9. [Frontend Display Testing](#frontend-display-testing)
10. [Accessibility Testing](#accessibility-testing)
11. [Edge Cases & Error Handling](#edge-cases--error-handling)
12. [Performance Testing](#performance-testing)
13. [Cross-Browser Testing](#cross-browser-testing)
14. [Integration Testing](#integration-testing)
15. [Security Testing](#security-testing)

---

## Pre-Testing Setup

### ✅ Environment Checklist

- [ ] WordPress 6.8+ installed
- [ ] Twenty Twenty-Five theme (or other block theme) active
- [ ] Post Formats Power-Up plugin activated
- [ ] Chat Log Block plugin activated
- [ ] No JavaScript errors in browser console
- [ ] PHP error logging enabled (`WP_DEBUG` and `WP_DEBUG_LOG`)
- [ ] Browser cache cleared
- [ ] Test user has `edit_posts` capability

### Test Data Preparation

Create the following test posts:

1. **Empty new post** (auto-draft status)
2. **Post with only title** (no content)
3. **Post with title + paragraph** (standard format)
4. **Post with gallery as first block** (no format set)
5. **Post with image as first block** (format set to "video" - mismatch)
6. **Post with multiple blocks** (mixed content)
7. **Post with shortcodes** (legacy content)
8. **Post with custom HTML** (advanced users)
9. **Post with reusable blocks**
10. **Published post** (test editing published content)

---

## Template System Testing

### Test 0.1: Template Registration

**Steps:**
1. Go to Appearance → Editor
2. Click "Templates" in sidebar
3. Look for format templates

**Expected Results:**
- [ ] "Aside Format" template visible
- [ ] "Gallery Format" template visible
- [ ] "Link Format" template visible
- [ ] "Image Format" template visible
- [ ] "Quote Format" template visible
- [ ] "Status Format" template visible
- [ ] "Video Format" template visible
- [ ] "Audio Format" template visible
- [ ] "Chat Format" template visible
- [ ] All 9 format templates present
- [ ] Templates show "Plugin: Post Formats Power-Up" source
- [ ] No PHP warnings in debug log about template types

---

### Test 0.2: Template Customization

**Steps:**
1. Go to Appearance → Editor → Templates
2. Click "Aside Format" template
3. Try to edit the template (e.g., change background color of group)
4. Click "Save"

**Expected Results:**
- [ ] Template opens in editor
- [ ] Can modify blocks
- [ ] Can change styles (colors, spacing, etc.)
- [ ] Save completes successfully
- [ ] No error: "No templates exist with that id"
- [ ] Changes persist after reload
- [ ] Customized version stored in database

---

### Test 0.3: Automatic Template Assignment

**Steps:**
1. Create new post
2. Select "Gallery" format from modal
3. Save post
4. Check post meta in database or use query monitor

**Expected Results:**
- [ ] Post meta `_wp_page_template` set to "single-format-gallery"
- [ ] Template automatically assigned on save
- [ ] Viewing post uses Gallery template
- [ ] Changing format to "Video" updates template to "single-format-video"
- [ ] Changing format to "Standard" removes custom template assignment

---

### Test 0.4: Template Frontend Application

**Steps:**
1. Create Aside format post
2. Publish
3. View on frontend
4. Inspect HTML source

**Expected Results:**
- [ ] Post uses `single-format-aside.html` template
- [ ] Template structure matches what's defined in plugin
- [ ] Main group wrapper present
- [ ] Inner group with zero padding present
- [ ] Post content displays
- [ ] Footer and header template parts load

---

### Test 0.5: Template Fallback

**Steps:**
1. Create post with Gallery format
2. Deactivate Post Formats Power-Up plugin
3. View post on frontend

**Expected Results:**
- [ ] Post still displays (falls back to theme's default template)
- [ ] No fatal errors
- [ ] Content remains intact
- [ ] Reactivating plugin restores format template

---

## Format Modal Testing

### Test 1.1: Modal Appearance on New Post

**Steps:**
1. Go to Posts → Add New
2. Wait 500ms (modal delay)

**Expected Results:**
- [ ] Modal appears automatically
- [ ] Modal has title "Choose Post Format"
- [ ] Modal overlay covers editor
- [ ] Modal is centered on screen
- [ ] All 10 formats visible in grid layout
- [ ] Each format shows icon, name, description
- [ ] Standard format has primary button style
- [ ] Other formats have secondary button style
- [ ] Close button (X) visible in top corner

**Edge Cases:**
- [ ] Modal should NOT appear on existing posts
- [ ] Modal should NOT appear on pages
- [ ] Modal should NOT appear twice on refresh
- [ ] Modal should NOT appear when duplicating post

---

### Test 1.2: Modal Keyboard Navigation

**Steps:**
1. Open new post, wait for modal
2. Press Tab key repeatedly

**Expected Results:**
- [ ] Focus moves through all format buttons
- [ ] Focus outline is visible
- [ ] Close button (X) is reachable via Tab
- [ ] Tab order is logical (left-to-right, top-to-bottom)
- [ ] Pressing Escape closes modal
- [ ] Pressing Enter on focused format selects it
- [ ] Focus returns to editor after modal closes

**Accessibility Check:**
- [ ] Use screen reader (VoiceOver/NVDA)
- [ ] Each format announces name and description
- [ ] Modal announces as dialog/modal
- [ ] Close button has accessible label

---

### Test 1.3: Format Selection from Modal

**For each format (all 10), test:**

1. **Standard Format**
   - [ ] Select Standard
   - [ ] Modal closes
   - [ ] Post format set to "Standard" (check sidebar)
   - [ ] No blocks inserted
   - [ ] No template assigned
   - [ ] No screen reader announcement

2. **Aside Format**
   - [ ] Select Aside
   - [ ] Modal closes
   - [ ] Aside pattern inserts
   - [ ] First block is locked Group with "aside-bubble" class
   - [ ] Paragraph inside group has placeholder text
   - [ ] Format shown in sidebar as "Aside"
   - [ ] Template assigned: "single-format-aside"
   - [ ] Screen reader announces "Selected Aside format. Pattern inserted."

3. **Gallery Format**
   - [ ] Select Gallery
   - [ ] Gallery block inserted as first block
   - [ ] Block is locked (cannot move/remove)
   - [ ] Gallery has empty state (prompts for images)
   - [ ] Additional paragraph block below gallery
   - [ ] Template assigned: "single-format-gallery"

4. **Link Format**
   - [ ] Select Link
   - [ ] Bookmark Card block inserted
   - [ ] Block is locked
   - [ ] Placeholder shows URL input
   - [ ] Additional paragraph below
   - [ ] Template assigned: "single-format-link"

5. **Image Format**
   - [ ] Select Image
   - [ ] Image block inserted
   - [ ] Block is locked
   - [ ] Image has upload prompt
   - [ ] Caption field visible
   - [ ] Template assigned: "single-format-image"

6. **Quote Format**
   - [ ] Select Quote
   - [ ] Quote block inserted
   - [ ] Block is locked
   - [ ] Citation field visible
   - [ ] Proper quote styling applied
   - [ ] Template assigned: "single-format-quote"

7. **Status Format**
   - [ ] Select Status
   - [ ] Paragraph with "status-paragraph" class inserted
   - [ ] Block is locked
   - [ ] Placeholder text about 280 characters
   - [ ] Character counter visible (if implemented)
   - [ ] Template assigned: "single-format-status"

8. **Video Format**
   - [ ] Select Video
   - [ ] Video block inserted
   - [ ] Block is locked
   - [ ] Upload/embed prompt visible
   - [ ] Template assigned: "single-format-video"

9. **Audio Format**
   - [ ] Select Audio
   - [ ] Audio block inserted
   - [ ] Block is locked
   - [ ] Upload/embed prompt visible
   - [ ] Template assigned: "single-format-audio"

10. **Chat Format**
    - [ ] Select Chat (Chat Log plugin must be active)
    - [ ] Chat Log block (chatlog/conversation) inserted
    - [ ] Block is locked
    - [ ] Transcript input area visible
    - [ ] Style selector visible (bubbles/IRC/etc)
    - [ ] Template assigned: "single-format-chat"

**Common Checks for All Non-Standard Formats:**
- [ ] First block is always locked
- [ ] Cannot delete first block
- [ ] Cannot move first block
- [ ] Can add blocks after first block
- [ ] Format persists after save
- [ ] Format persists after reload
- [ ] Appropriate template assigned

---

## Pattern Insertion Testing

### Test 2.1: Patterns Appear in Inserter

**Steps:**
1. Create new post
2. Close format modal (or select Standard)
3. Click (+) to open block inserter
4. Click "Patterns" tab
5. Look for "Post formats" category

**Expected Results:**
- [ ] "Post formats" category exists
- [ ] Category contains 10 patterns (or 9 if Chat Log not active)
- [ ] Each pattern has descriptive title: "Aside Post Format", "Gallery Post Format", etc.
- [ ] Clicking pattern inserts it
- [ ] Pattern content matches modal insertion

**Test Each Pattern:**

For each of the 10 formats:
- [ ] Pattern inserts correct first block
- [ ] First block has lock attribute
- [ ] Additional blocks (if any) insert correctly
- [ ] Pattern matches expected layout from specification

---

### Test 2.2: Manual Pattern Insertion Behavior

**Steps:**
1. Create new post, dismiss modal
2. Insert "Gallery Post Format" pattern manually
3. Add some content
4. Save post

**Expected Results:**
- [ ] Pattern inserts successfully
- [ ] Post format does NOT auto-change (pattern ≠ auto-detection)
- [ ] Format stays as "Standard" unless manually changed
- [ ] Locked block cannot be deleted
- [ ] Can add blocks around locked block

**Edge Case:**
- [ ] Insert pattern, then delete all other blocks except locked first block
- [ ] Save - format should still be Standard (manual pattern insertion doesn't trigger format)

---

### Test 2.3: Pattern Search

**Steps:**
1. Open block inserter
2. Type "gallery" in search
3. Check results

**Expected Results:**
- [ ] "Gallery Post Format" pattern appears
- [ ] Pattern is searchable by format name
- [ ] Pattern is searchable by keyword (e.g., "image", "photo" might find Gallery)

---

## Auto-Detection Testing

### Test 3.1: Standard Auto-Detection Flow

**For each format, test auto-detection:**

1. **Gallery Format Detection**
   ```
   Steps:
   1. Create new post (dismiss modal or select Standard)
   2. Insert core/gallery block as FIRST block
   3. Add images to gallery
   4. Save post

   Expected:
   - [ ] Post format automatically changes to "Gallery"
   - [ ] Template assigned: "single-format-gallery"
   - [ ] Meta _pfpu_format_detected set to "gallery"
   - [ ] No manual flag set
   ```

2. **Image Format Detection**
   ```
   Steps:
   1. New post, Standard format
   2. Insert core/image block first
   3. Upload image
   4. Save

   Expected:
   - [ ] Format changes to "Image"
   - [ ] Template assigned: "single-format-image"
   ```

3. **Video Format Detection**
   ```
   Steps:
   1. New post
   2. Insert core/video block first
   3. Add video
   4. Save

   Expected:
   - [ ] Format changes to "Video"
   - [ ] Template assigned: "single-format-video"
   ```

4. **Audio Format Detection**
   ```
   Steps:
   1. New post
   2. Insert core/audio block first
   3. Add audio file
   4. Save

   Expected:
   - [ ] Format changes to "Audio"
   - [ ] Template assigned: "single-format-audio"
   ```

5. **Quote Format Detection**
   ```
   Steps:
   1. New post
   2. Insert core/quote block first
   3. Add quote text
   4. Save

   Expected:
   - [ ] Format changes to "Quote"
   - [ ] Template assigned: "single-format-quote"
   ```

6. **Link Format Detection**
   ```
   Steps:
   1. New post
   2. Insert bookmark-card/bookmark-card block first
   3. Add URL
   4. Save

   Expected:
   - [ ] Format changes to "Link"
   - [ ] Template assigned: "single-format-link"
   - [ ] NOTE: Only works if Bookmark Card plugin active
   ```

7. **Chat Format Detection**
   ```
   Steps:
   1. New post
   2. Insert chatlog/conversation block first
   3. Add transcript
   4. Save

   Expected:
   - [ ] Format changes to "Chat"
   - [ ] Template assigned: "single-format-chat"
   - [ ] NOTE: Only works if Chat Log plugin active
   ```

8. **Aside Format Detection**
   ```
   Steps:
   1. New post
   2. Insert core/group block first
   3. Give group CSS class "aside-bubble"
   4. Save

   Expected:
   - [ ] Format changes to "Aside"
   - [ ] Template assigned: "single-format-aside"
   ```

9. **Status Format Detection**
   ```
   Steps:
   1. New post
   2. Insert core/paragraph block first
   3. Give paragraph CSS class "status-paragraph"
   4. Add text under 280 characters
   5. Save

   Expected:
   - [ ] Format changes to "Status"
   - [ ] Template assigned: "single-format-status"
   ```

10. **Standard Format (Fallback)**
    ```
    Steps:
    1. New post
    2. Insert regular core/paragraph first
    3. Save

    Expected:
    - [ ] Format stays "Standard"
    - [ ] No custom template assigned
    ```

---

### Test 3.2: Auto-Detection Priority & Edge Cases

**Test Case 1: Wrong First Block**
```
Steps:
1. Create Gallery format post (via modal)
2. Add images to gallery
3. Save
4. Edit post
5. Move a paragraph block ABOVE the gallery
6. Save

Expected:
- [ ] Format changes from Gallery to Standard (paragraph is now first)
- [ ] Template changes to default (no custom template)
- [ ] Repair tool should suggest changing back to Gallery
```

**Test Case 2: Deleting First Block**
```
Steps:
1. Create Video format post
2. Delete the locked video block (might need to unlock first)
3. First block is now a paragraph
4. Save

Expected:
- [ ] Format changes to Standard
- [ ] Custom template removed
```

**Test Case 3: Empty Post**
```
Steps:
1. Create new post
2. Add title only, no content
3. Save

Expected:
- [ ] Format is Standard
- [ ] No errors
- [ ] No custom template
```

**Test Case 4: Multiple Format Blocks**
```
Steps:
1. New post
2. Insert Gallery block first
3. Insert Video block second
4. Save

Expected:
- [ ] Format is Gallery (first block wins)
- [ ] Template assigned: "single-format-gallery"
```

**Test Case 5: Manual Override**
```
Steps:
1. Create post with Image block first
2. Save (should detect as Image format)
3. Edit post
4. Manually change format to Quote using sidebar
5. Save
6. Reload post

Expected:
- [ ] Format stays as Quote (manual override)
- [ ] Template assigned: "single-format-quote"
- [ ] Meta _pfpu_format_manual set to true
- [ ] Auto-detection does NOT override on future saves
```

**Test Case 6: Removing Manual Override**
```
Steps:
1. Post with manual format override (from Test 3.2.5)
2. Go to Tools → Post Format Repair
3. Apply suggestion to change format
4. Edit post, change first block
5. Save

Expected:
- [ ] Auto-detection works again
- [ ] Manual flag cleared
- [ ] Correct template assigned
```

---

### Test 3.3: Auto-Detection Performance

**Test with 100 Posts:**
```
Steps:
1. Create 100 posts with different first blocks
2. Monitor PHP error log
3. Check database queries

Expected:
- [ ] No timeout errors
- [ ] No memory errors
- [ ] Detection runs only on save_post
- [ ] No N+1 query problems
- [ ] Template assignment completes for all posts
```

---

## Format Switcher Testing

### Test 4.1: Format Switcher Panel Visibility

**Steps:**
1. Edit any post
2. Look in right sidebar under "Post" tab
3. Find "Document" section

**Expected Results:**
- [ ] "Post Format" dropdown is visible
- [ ] Shows current format
- [ ] All 10 formats available in dropdown
- [ ] Help text: "Choose the format that best matches your content."

---

### Test 4.2: Changing Format Without Content

**Steps:**
1. Create new post (empty, no content)
2. Dismiss format modal
3. Use sidebar dropdown to change format to "Gallery"

**Expected Results:**
- [ ] Format changes immediately
- [ ] No confirmation dialog
- [ ] No pattern inserted (no content to replace)
- [ ] Format shown as "Gallery" in dropdown
- [ ] Format persists after save
- [ ] Template assigned: "single-format-gallery"

---

### Test 4.3: Changing Format With Content

**Steps:**
1. Create post with paragraph of text
2. Use sidebar to change format to "Video"

**Expected Results:**
- [ ] Confirmation modal appears
- [ ] Modal title: "Change Post Format?"
- [ ] Modal text: "Your post already has content. How would you like to proceed?"
- [ ] Three buttons visible:
  - "Replace content with new pattern" (primary)
  - "Keep content, just change format" (secondary)
  - "Cancel" (tertiary)

**Test Each Option:**

**Option 1: Replace Content**
- [ ] Click "Replace content with new pattern"
- [ ] Existing content is removed
- [ ] New format pattern inserted
- [ ] Format changed in sidebar
- [ ] Template assigned: "single-format-video"
- [ ] Manual flag set (API request to set meta)

**Option 2: Keep Content**
- [ ] Click "Keep content, just change format"
- [ ] Existing content remains unchanged
- [ ] Format changed in sidebar
- [ ] Template assigned: "single-format-video"
- [ ] Manual flag set
- [ ] No new blocks inserted

**Option 3: Cancel**
- [ ] Click "Cancel"
- [ ] Modal closes
- [ ] Format remains unchanged
- [ ] Content remains unchanged
- [ ] Template unchanged

---

### Test 4.4: Format Switcher with Locked Blocks

**Steps:**
1. Create Gallery post via modal (has locked gallery block)
2. Add some text after gallery
3. Use sidebar to switch to Video format
4. Choose "Replace content with new pattern"

**Expected Results:**
- [ ] Confirmation modal appears (content exists)
- [ ] Locked gallery block is removed
- [ ] New locked video block inserted
- [ ] Text content below is removed
- [ ] Format changed to Video
- [ ] Template changed to "single-format-video"

---

### Test 4.5: Rapid Format Switching

**Steps:**
1. Create post with content
2. Switch format to Gallery
3. Immediately switch to Video
4. Immediately switch to Audio
5. Save

**Expected Results:**
- [ ] Each switch shows confirmation
- [ ] Final format is Audio
- [ ] Final template is "single-format-audio"
- [ ] No JavaScript errors
- [ ] Post saves correctly
- [ ] Manual flag is set

---

## Individual Format Tests

### Test 5.1: Standard Format

**Creation Test:**
```
1. Create new post
2. Select Standard from modal
3. Add title: "Standard Post Test"
4. Add content: 3 paragraphs, 1 heading, 1 image
5. Save
6. View on frontend

Expected:
- [ ] Title visible on frontend
- [ ] All content displays normally
- [ ] No special styling
- [ ] Format shows as "Standard" in admin
- [ ] No custom template assigned
- [ ] Uses theme's default single post template
```

**Auto-Detection Test:**
```
1. New post, dismiss modal
2. Add paragraph first
3. Save

Expected:
- [ ] Format auto-detected as Standard
- [ ] No custom template
```

---

### Test 5.2: Aside Format

**Creation Test:**
```
1. Create new post
2. Select Aside from modal
3. Note the locked Group block with "aside-bubble" class
4. Add title: "Aside Post Test" (optional)
5. Add content inside the aside bubble: "This is a quick aside about post formats."
6. Add paragraph after the aside: "This is additional context."
7. Save
8. View on frontend

Expected:
- [ ] **Title is NOT visible on frontend** ⚠️
- [ ] Aside content displays in unstyled group (no special background, border, or padding)
- [ ] Text size matches standard post format
- [ ] Text alignment matches standard post format
- [ ] Inner group has zero padding (0 on all sides)
- [ ] No border-radius on group
- [ ] No background color on group
- [ ] Additional paragraph displays normally below
- [ ] Template applied: "single-format-aside"
```

**Auto-Detection Test:**
```
1. New post, dismiss modal
2. Add Group block first
3. Open block settings → Advanced → Additional CSS classes
4. Add class: "aside-bubble"
5. Add content inside group
6. Save

Expected:
- [ ] Format auto-detected as Aside
- [ ] Template assigned: "single-format-aside"
```

**Styling Test:**
```
1. Create Aside post (from modal)
2. Measure text size in browser DevTools
3. Create Standard post
4. Measure text size

Expected:
- [ ] **Text sizes MUST match (both use theme default)**
- [ ] **Text alignment MUST match**
- [ ] **No special font styling applied**
- [ ] Only structure differs (group wrapper present)
```

**Template Customization Test:**
```
1. Go to Appearance → Editor → Templates
2. Find "Aside Format" template
3. Edit template (e.g., add background color to inner group)
4. Save customization
5. View Aside post on frontend

Expected:
- [ ] Template customization applied
- [ ] Can change colors, spacing, typography
- [ ] Changes affect all Aside posts
- [ ] Customizations persist
```

---

### Test 5.3: Gallery Format

**Creation Test:**
```
1. Select Gallery from modal
2. Add title: "Gallery Post Test"
3. Upload 6 images to gallery
4. Set gallery to 3 columns
5. Add captions to 2 images
6. Add paragraph after gallery
7. Save
8. View on frontend

Expected:
- [ ] Gallery displays in 3 columns
- [ ] All 6 images visible
- [ ] Captions show below 2 images
- [ ] Gallery is responsive (check mobile view)
- [ ] Lightbox opens on click (theme-dependent)
- [ ] Additional paragraph visible after gallery
- [ ] Template applied: "single-format-gallery"
```

**Auto-Detection Test:**
```
1. New post
2. Insert core/gallery first
3. Upload images
4. Save

Expected:
- [ ] Format detected as Gallery
- [ ] Template assigned: "single-format-gallery"
```

**Edge Cases:**
```
- [ ] Gallery with 1 image (still Gallery format)
- [ ] Gallery with 50 images (performance check)
- [ ] Gallery with empty state (no images uploaded)
- [ ] Gallery deleted after detection (format changes to Standard, template removed)
```

---

### Test 5.4: Link Format

**Prerequisite:** Bookmark Card plugin must be active (if not, Link format uses core/embed)

**Creation Test:**
```
1. Select Link from modal
2. Add title: "Interesting Article"
3. In bookmark card, enter URL: https://wordpress.org
4. Wait for card to fetch metadata
5. Add commentary paragraph after card
6. Save
7. View on frontend

Expected:
- [ ] Bookmark card displays with fetched data
- [ ] Shows title, description, image (if available)
- [ ] Shows favicon/site icon
- [ ] Card is clickable → opens URL
- [ ] Commentary paragraph visible
- [ ] Format is Link
- [ ] Template applied: "single-format-link"
```

**Auto-Detection Test:**
```
1. New post
2. Insert bookmark-card/bookmark-card first
3. Add URL
4. Save

Expected:
- [ ] Format detected as Link
- [ ] Template assigned: "single-format-link"
```

**Fallback Test (No Bookmark Card Plugin):**
```
1. Deactivate Bookmark Card plugin
2. Create Link format post
3. Check pattern

Expected:
- [ ] Pattern falls back to core/embed or core/paragraph with instructions
- [ ] No errors
- [ ] Format still set to Link
- [ ] Template still assigned
```

---

### Test 5.5: Image Format

**Creation Test:**
```
1. Select Image from modal
2. Add title: "Photo of the Day"
3. Upload image to locked image block (1200x800px)
4. Add alt text: "Sunset over mountains"
5. Add caption: "Taken from Mount Rainier"
6. Add paragraph after image with description
7. Save
8. View on frontend

Expected:
- [ ] Image displays at full size (or theme max-width)
- [ ] Caption visible below image
- [ ] Alt text present in HTML
- [ ] Image is responsive
- [ ] Image opens in lightbox (theme-dependent)
- [ ] Additional content visible
- [ ] Template applied: "single-format-image"
```

**Auto-Detection Test:**
```
1. New post
2. Insert core/image first
3. Upload image
4. Save

Expected:
- [ ] Format detected as Image
- [ ] Template assigned: "single-format-image"
```

**Image Formats Test:**
```
- [ ] JPG image works
- [ ] PNG image works
- [ ] GIF image works
- [ ] WebP image works
- [ ] SVG image works (if allowed)
- [ ] Very large image (5MB+) works
```

---

### Test 5.6: Quote Format

**Creation Test:**
```
1. Select Quote from modal
2. Add title: "Words of Wisdom"
3. In locked quote block, add quote: "The only way to do great work is to love what you do."
4. Add citation: "Steve Jobs"
5. Add paragraph after quote with context
6. Save
7. View on frontend

Expected:
- [ ] Quote displays with quotation marks or styling
- [ ] Citation appears (could be before or after quote)
- [ ] Quote has distinct typography
- [ ] Block quote semantic HTML (<blockquote>)
- [ ] Citation uses <cite> tag
- [ ] Template applied: "single-format-quote"
```

**Auto-Detection Test:**
```
1. New post
2. Insert core/quote first
3. Add quote text
4. Save

Expected:
- [ ] Format detected as Quote
- [ ] Template assigned: "single-format-quote"
```

**Quote Styles Test:**
```
1. Create quote post
2. Try different quote styles (if block has style variations)
3. Check frontend rendering

Expected:
- [ ] All style variations work
- [ ] Styling uses theme colors
```

---

### Test 5.7: Status Format

**Creation Test:**
```
1. Select Status from modal
2. Note the locked paragraph with "status-paragraph" class
3. Add text: "Just finished a great book! Highly recommend 'The Pragmatic Programmer' 📚"
4. Count characters (should be under 280)
5. Save
6. View on frontend

Expected:
- [ ] **Title is NOT visible on frontend** ⚠️
- [ ] Status text displays in unstyled paragraph (no special background or styling)
- [ ] Text size matches standard post format
- [ ] Text alignment matches standard post format
- [ ] Inner group has zero padding (0 on all sides)
- [ ] No border-radius on group
- [ ] No background color on group
- [ ] Timestamp/date is visible
- [ ] Template applied: "single-format-status"
```

**Character Counter Test (if implemented):**
```
1. Create Status post
2. Type characters
3. Watch for counter

Expected:
- [ ] Counter shows remaining characters
- [ ] Counter turns red/warning after 280 characters
- [ ] Warning appears if exceeding limit
- [ ] Can still save (soft limit)
```

**Auto-Detection Test:**
```
1. New post
2. Insert core/paragraph first
3. Add class "status-paragraph" via Advanced → CSS classes
4. Add short text
5. Save

Expected:
- [ ] Format detected as Status
- [ ] Template assigned: "single-format-status"
```

**Styling Test:**
```
1. Create Status post (from modal)
2. Measure text size in browser DevTools
3. Create Standard post
4. Measure text size

Expected:
- [ ] **Text sizes MUST match (both use theme default)**
- [ ] **Text alignment MUST match**
- [ ] **No special font styling applied**
- [ ] Only structure differs (group wrapper present)
```

**Template Customization Test:**
```
1. Go to Appearance → Editor → Templates
2. Find "Status Format" template
3. Edit template (e.g., add background color, change font size)
4. Save customization
5. View Status post on frontend

Expected:
- [ ] Template customization applied
- [ ] Can change colors, spacing, typography
- [ ] Changes affect all Status posts
- [ ] Customizations persist
```

**Edge Cases:**
```
- [ ] Status with exactly 280 characters
- [ ] Status with 281 characters (over limit)
- [ ] Status with emoji (counts as multiple characters?)
- [ ] Status with only emoji
- [ ] Empty status paragraph
```

---

### Test 5.8: Video Format

**Creation Test:**
```
1. Select Video from modal
2. Add title: "Product Demo Video"
3. Upload video to locked video block (MP4, 10MB)
4. OR embed YouTube video: https://www.youtube.com/watch?v=dQw4w9WgXcQ
5. Add caption if supported
6. Add paragraph after video with description
7. Save
8. View on frontend

Expected:
- [ ] Video embeds correctly
- [ ] Video plays inline
- [ ] Video controls are visible
- [ ] Video is responsive
- [ ] Embedded videos show thumbnail
- [ ] Additional content visible
- [ ] Template applied: "single-format-video"
```

**Auto-Detection Test:**
```
1. New post
2. Insert core/video first
3. Add video
4. Save

Expected:
- [ ] Format detected as Video
- [ ] Template assigned: "single-format-video"
```

**Video Formats Test:**
```
- [ ] MP4 upload works
- [ ] WebM upload works
- [ ] YouTube embed works
- [ ] Vimeo embed works
- [ ] Self-hosted video works
- [ ] Video with subtitle track works
```

**Performance Test:**
```
- [ ] Large video (100MB) uploads without timeout
- [ ] Video doesn't auto-play (unless specified)
- [ ] Page loads quickly even with video
```

---

### Test 5.9: Audio Format

**Creation Test:**
```
1. Select Audio from modal
2. Add title: "Podcast Episode 42"
3. Upload audio to locked audio block (MP3, 20MB)
4. OR embed Spotify/SoundCloud link
5. Add paragraph after audio with show notes
6. Save
7. View on frontend

Expected:
- [ ] Audio player displays
- [ ] Play/pause controls work
- [ ] Progress bar works
- [ ] Volume control works
- [ ] Shows audio duration
- [ ] Additional content visible
- [ ] Template applied: "single-format-audio"
```

**Auto-Detection Test:**
```
1. New post
2. Insert core/audio first
3. Add audio file
4. Save

Expected:
- [ ] Format detected as Audio
- [ ] Template assigned: "single-format-audio"
```

**Audio Formats Test:**
```
- [ ] MP3 upload works
- [ ] OGG upload works
- [ ] WAV upload works
- [ ] Spotify embed works
- [ ] SoundCloud embed works
```

---

### Test 5.10: Chat Format

**Prerequisite:** Chat Log Block plugin MUST be active

**Creation Test:**
```
1. Select Chat from modal
2. Locked chatlog/conversation block should insert
3. Add title: "Support Chat Transcript"
4. In chat block, add transcript:
   ```
   John [10:30]: Hi, I need help with my order.
   Sarah [10:31]: Of course! What's your order number?
   John [10:32]: It's #12345
   Sarah [10:33]: Let me look that up for you...
   ```
5. Select chat source: Slack (or keep Auto)
6. Select display style: Bubbles
7. Add paragraph after chat with summary
8. Save
9. View on frontend

Expected:
- [ ] Chat displays in bubble format
- [ ] Each message shows username and timestamp
- [ ] Messages alternate sides (if bubbles style)
- [ ] Proper chat styling applied
- [ ] Different styles work (IRC, Transcript, Timeline)
- [ ] Additional content visible
- [ ] Template applied: "single-format-chat"
```

**Auto-Detection Test:**
```
1. New post
2. Insert chatlog/conversation block first
3. Add transcript
4. Save

Expected:
- [ ] Format detected as Chat
- [ ] Template assigned: "single-format-chat"
```

**Chat Sources Test:**
```
Test each chat source option:
- [ ] Auto (automatic detection)
- [ ] Slack
- [ ] Discord
- [ ] Microsoft Teams
- [ ] WhatsApp
- [ ] Telegram
- [ ] Signal
- [ ] Generic

Expected:
- Each source renders correctly
- Icons/avatars appropriate for source
- Timestamp formats vary by source
```

**Chat Styles Test:**
```
Test each display style:
- [ ] Bubbles (iMessage-like)
- [ ] IRC (classic IRC format)
- [ ] Transcript (simple text)
- [ ] Timeline (time on left)

Expected:
- All styles render correctly
- Switching styles updates preview
```

**Chat Format Without Plugin Test:**
```
1. Deactivate Chat Log Block plugin
2. Create new post
3. Open format modal

Expected:
- [ ] Chat format NOT shown in modal (or grayed out)
- [ ] No errors in console
- [ ] Other formats still work
```

---

## Repair Tool Testing

### Test 6.1: Accessing Repair Tool

**Steps:**
1. Go to WordPress Admin
2. Navigate to Tools → Post Format Repair

**Expected Results:**
- [ ] Page loads without errors
- [ ] Page title: "Post Format Repair Tool"
- [ ] Description text explains what the tool does
- [ ] Scan begins automatically on page load

---

### Test 6.2: Scan Results Display

**Setup:**
Create these test posts first:
1. Post with Gallery block first, format set to Standard (mismatch)
2. Post with Video block first, format set to Video (correct)
3. Post with Image block first, format set to Quote (mismatch)
4. Post with correct formats (x5)
5. Post with no content, format Standard (correct)

**Steps:**
1. Go to Repair Tool page
2. Review scan results

**Expected Results:**
- [ ] "Scan Results" section shows:
  - Total posts scanned (count)
  - Correctly formatted (count with green checkmark)
  - Format mismatches (count with warning icon)
- [ ] Summary numbers are accurate
- [ ] If mismatches: "Detected Mismatches" table appears
- [ ] If no mismatches: Success message appears

---

### Test 6.3: Mismatch Details Table

**Expected Columns:**
- [ ] Post Title (clickable link to edit post)
- [ ] Current Format
- [ ] Suggested Format
- [ ] First Block (block type)
- [ ] Action (Apply button)

**Expected Data:**
- [ ] All mismatched posts listed
- [ ] Post titles are links (open in new tab)
- [ ] Current format shown (e.g., "Standard")
- [ ] Suggested format shown (e.g., "Gallery")
- [ ] First block type shown (e.g., "core/gallery")
- [ ] Each row has "Apply" button

---

### Test 6.4: Dry Run Mode

**Steps:**
1. Go to Repair Tool with mismatches
2. Ensure "Dry run" checkbox is CHECKED
3. Click "Apply All Suggestions"

**Expected Results:**
- [ ] Page reloads
- [ ] Admin notice appears: "Dry run complete. Would have updated X posts."
- [ ] Lists posts that WOULD be changed
- [ ] Posts are NOT actually changed
- [ ] Go to Posts list, verify formats unchanged
- [ ] Scan results still show same mismatches
- [ ] Templates NOT assigned

---

### Test 6.5: Apply Single Format Fix

**Steps:**
1. Create post: Gallery block first, format = Standard
2. Go to Repair Tool
3. Find mismatch in table
4. Click "Apply" button for that specific post

**Expected Results:**
- [ ] Page reloads
- [ ] Admin notice: "Format updated for post: [Post Title]"
- [ ] That post no longer appears in mismatches
- [ ] Go to edit that post - format is now Gallery
- [ ] Template assigned: "single-format-gallery"
- [ ] Revision created before change
- [ ] Other mismatches still remain

---

### Test 6.6: Apply All Suggestions

**Steps:**
1. Create 5 posts with mismatches
2. Go to Repair Tool
3. UNCHECK "Dry run" checkbox
4. Click "Apply All Suggestions"

**Expected Results:**
- [ ] Confirmation appears OR page processes immediately
- [ ] Page reloads
- [ ] Admin notice: "Updated X posts successfully"
- [ ] Scan results now show 0 mismatches
- [ ] All posts have correct formats
- [ ] All posts have correct templates assigned
- [ ] Revisions created for all changed posts

---

### Test 6.7: Format Detection Reference

**Steps:**
1. Scroll to bottom of Repair Tool page

**Expected Results:**
- [ ] Section titled "How Format Detection Works"
- [ ] Lists all 10 formats with their detection rules:
  - Gallery: First block is core/gallery
  - Image: First block is core/image
  - Video: First block is core/video
  - Audio: First block is core/audio
  - Quote: First block is core/quote
  - Link: First block is bookmark-card/bookmark-card
  - **Chat: First block is chatlog/conversation**
  - Aside: First block is core/group with "aside-bubble" class
  - Status: First block is core/paragraph with "status-paragraph" class
  - Standard: Everything else

---

### Test 6.8: Repair Tool Edge Cases

**Test Case 1: No Posts**
```
Steps:
1. Delete all posts
2. Go to Repair Tool

Expected:
- [ ] "Total posts scanned: 0"
- [ ] No errors
- [ ] Success message: "No posts to scan"
```

**Test Case 2: 1000 Posts**
```
Steps:
1. Import 1000 posts
2. Go to Repair Tool

Expected:
- [ ] Tool handles large datasets
- [ ] No timeout
- [ ] Accurate counts
- [ ] Apply All works without timeout
```

**Test Case 3: Manual Format Flag**
```
Steps:
1. Create post with Gallery block first
2. Manually set format to Video (using sidebar)
3. Save (sets manual flag)
4. Go to Repair Tool

Expected:
- [ ] Post shows in mismatches (Gallery suggested, Video current)
- [ ] Applying fix clears manual flag
- [ ] Correct template assigned
- [ ] Auto-detection works on future saves
```

**Test Case 4: Trashed Posts**
```
Steps:
1. Create post with mismatch
2. Move to Trash
3. Go to Repair Tool

Expected:
- [ ] Trashed post NOT included in scan
- [ ] Only published/draft posts scanned
```

**Test Case 5: Scheduled Posts**
```
Steps:
1. Create post with mismatch
2. Schedule for future
3. Go to Repair Tool

Expected:
- [ ] Scheduled post IS included in scan
- [ ] Can apply fix to scheduled post
- [ ] Template assigned correctly
```

---

## Frontend Display Testing

### Test 7.1: Single Post View

**For Each Format, Test:**

```
1. Create and publish post in [FORMAT]
2. Add featured image
3. View single post on frontend

Check:
- [ ] Post displays correctly
- [ ] Correct template applied (inspect page source)
- [ ] Format-specific template structure present
- [ ] Title visible (except Aside/Status)
- [ ] Content readable
- [ ] Featured image displays
- [ ] Metadata (date, author) displays
- [ ] Comments section present (if enabled)
```

---

### Test 7.2: Archive/Blog View

**Steps:**
1. Create 10 posts (1 of each format)
2. Publish all
3. View main blog page

**Expected Results:**
- [ ] All posts appear in chronological order
- [ ] Each post shows appropriate excerpt
- [ ] Featured images display
- [ ] "Read more" links work
- [ ] Pagination works

**Format-Specific Checks:**
- [ ] Aside posts: Title hidden, content preview shown
- [ ] Status posts: Title hidden, status text shown
- [ ] Gallery posts: Gallery preview or first image shown
- [ ] Video posts: Video thumbnail shown
- [ ] Quote posts: Quote text shown in excerpt

---

### Test 7.3: Category/Tag Archives

**Steps:**
1. Create posts in various formats
2. Assign same category to all
3. View category archive

**Expected Results:**
- [ ] All categorized posts appear
- [ ] Archive title shows category name
- [ ] Templates don't interfere with archive display

---

### Test 7.4: Search Results

**Steps:**
1. Use WordPress search
2. Search for term in post with specific format

**Expected Results:**
- [ ] Search finds post
- [ ] Search excerpt highlights term
- [ ] No template errors in search results

---

### Test 7.5: RSS Feed

**Steps:**
1. View RSS feed: `/feed/`
2. Check for posts of each format

**Expected Results:**
- [ ] All posts appear in feed
- [ ] Content rendered correctly
- [ ] Media (images, video) included or linked
- [ ] No broken HTML

---

### Test 7.6: Template Structure Verification

**Steps:**
1. Create posts in each format
2. View on frontend
3. Use browser DevTools to inspect HTML

**Expected Results:**
```
Aside Format:
- [ ] Uses single-format-aside template
- [ ] Main group with constrained layout
- [ ] Inner group with zero padding
- [ ] Post content block
- [ ] Spacer, date, categories, tags, comments

Status Format:
- [ ] Uses single-format-status template
- [ ] Main group with constrained layout
- [ ] Inner group with zero padding
- [ ] Post content block
- [ ] Spacer, date, categories, tags, comments

Gallery Format:
- [ ] Uses single-format-gallery template
- [ ] Locked gallery block present
- [ ] Styled container (if customized)

All Other Formats:
- [ ] Use appropriate single-format-[type] template
- [ ] Template structure matches plugin definition
```

---

### Test 7.7: Title Visibility

**Critical Test for Aside and Status Formats:**

**Setup:**
```
1. Create Aside post with title "Test Aside" and content
2. Create Status post with title "Test Status" and content
3. Create Standard post with title "Test Standard" and content
4. Publish all three
```

**Single Post View:**
```
Expected:
- [ ] Standard post: Title "Test Standard" is VISIBLE
- [ ] Aside post: Title "Test Aside" is HIDDEN ⚠️
- [ ] Status post: Title "Test Status" is HIDDEN ⚠️
```

**Archive View:**
```
Expected:
- [ ] Standard post: Title visible in listing
- [ ] Aside post: Title HIDDEN in listing ⚠️
- [ ] Status post: Title HIDDEN in listing ⚠️
```

**Note:** Title visibility is now controlled by the templates (single-format-aside.html and single-format-status.html don't include the post-title block). If titles are visible, check:
- [ ] Verify correct template is assigned to post
- [ ] Check post meta `_wp_page_template` value
- [ ] Verify template file doesn't include `<!-- wp:post-title /-->` block
- [ ] Check if theme overrides are interfering

---

### Test 7.8: Template Customization Persistence

**Steps:**
1. Go to Appearance → Editor → Templates
2. Edit "Quote Format" template
3. Change quote block border color to red
4. Save
5. View Quote format post on frontend

**Expected Results:**
- [ ] Customization applied (red border)
- [ ] Customization persists across page reloads
- [ ] Customization stored in database
- [ ] Plugin template not modified (customization is override)
- [ ] Can revert to default by deleting customization

---

## Accessibility Testing

### Test 8.1: Keyboard Navigation

**Format Modal:**
```
Steps:
1. Create new post
2. Use only keyboard (no mouse)

Test:
- [ ] Tab reaches modal when it opens
- [ ] Tab moves through all format buttons
- [ ] Shift+Tab goes backward
- [ ] Focus indicators visible
- [ ] Enter key selects focused format
- [ ] Escape key closes modal
- [ ] Focus returns to editor after close
```

**Editor:**
```
Steps:
1. Create post in each format
2. Use only keyboard

Test:
- [ ] Tab navigates between blocks
- [ ] Arrow keys navigate within blocks
- [ ] Can access block toolbar with keyboard
- [ ] Can access block settings with keyboard
- [ ] Cannot delete locked first block
- [ ] Format switcher accessible via Tab
```

---

### Test 8.2: Screen Reader Testing

**Tools:** VoiceOver (Mac), NVDA (Windows), or JAWS

**Format Modal:**
```
Steps:
1. Enable screen reader
2. Create new post
3. Wait for modal

Test:
- [ ] Modal announced as dialog
- [ ] Modal title read aloud
- [ ] Each format button announces name
- [ ] Each format button announces description
- [ ] Button roles correct
- [ ] Close button announced
- [ ] Focus trap works (can't escape modal)
```

**Individual Formats:**
```
For each format:
- [ ] First block has accessible label
- [ ] Locked status announced
- [ ] Block type announced correctly
- [ ] Placeholder text read
- [ ] Help text available
```

**Frontend:**
```
Steps:
1. Publish posts in each format
2. Use screen reader on frontend

Test:
- [ ] Post format announced (if applicable)
- [ ] Headings hierarchy correct
- [ ] Images have alt text
- [ ] Links have descriptive text
- [ ] Media controls accessible
- [ ] No "click here" links
```

---

### Test 8.3: Color Contrast

**Steps:**
1. Use browser accessibility tools or WAVE
2. Check each format on frontend and editor

**Test:**
- [ ] Text on background: minimum 4.5:1 contrast (AA)
- [ ] Large text: minimum 3:1 contrast
- [ ] Format-specific styling maintains contrast
- [ ] Locked block indicator has sufficient contrast
- [ ] Focus indicators have 3:1 contrast
- [ ] Error messages have sufficient contrast

**Template Customization:**
```
If users customize templates with colors:
- [ ] Warn if contrast is insufficient (future feature)
- [ ] Document contrast requirements
```

---

### Test 8.4: ARIA Attributes

**Inspect with DevTools:**

**Format Modal:**
```
Expected ARIA:
- [ ] role="dialog"
- [ ] aria-modal="true"
- [ ] aria-labelledby (references title)
- [ ] aria-describedby (references description)
```

**Locked Blocks:**
```
Expected:
- [ ] aria-label describes lock status
- [ ] aria-disabled on delete/move controls
```

**Format Switcher:**
```
Expected:
- [ ] <select> has associated <label>
- [ ] Help text connected via aria-describedby
```

---

### Test 8.5: Semantic HTML

**Steps:**
1. View page source on frontend
2. Check for semantic HTML5 elements

**Test:**
```
- [ ] Posts use <article> element
- [ ] Headings use <h1>, <h2>, etc. (correct hierarchy)
- [ ] Quotes use <blockquote> and <cite>
- [ ] Time stamps use <time datetime="">
- [ ] Links are <a> with href
- [ ] Buttons are <button> not <a>
- [ ] Forms use <label> for inputs
- [ ] Media has proper attributes (alt, title, etc.)
```

---

### Test 8.6: Form Labels

**Repair Tool:**
```
Check:
- [ ] "Dry run" checkbox has <label>
- [ ] Label text: "Dry run (preview changes without applying)"
- [ ] Clicking label toggles checkbox
- [ ] Help text provided
```

**Format Switcher:**
```
Check:
- [ ] Dropdown has <label> "Post Format"
- [ ] Help text associated via aria-describedby
```

---

### Test 8.7: Focus Management

**Modal Opening:**
```
- [ ] Focus moves to modal when opened
- [ ] Focus trapped in modal (can't Tab out)
- [ ] Focus visible on first focusable element
```

**Modal Closing:**
```
- [ ] Focus returns to trigger element
- [ ] OR focus moves to logical next element
- [ ] No focus lost to <body>
```

**Block Insertion:**
```
- [ ] Focus moves to newly inserted block
- [ ] Can immediately start typing in block
```

---

## Edge Cases & Error Handling

### Test 9.1: Plugin Conflicts

**Test with Popular Plugins:**
```
Install and test with:
- [ ] Yoast SEO
- [ ] Jetpack
- [ ] WooCommerce
- [ ] Contact Form 7
- [ ] Elementor (page builder)
- [ ] Classic Editor plugin

Expected:
- No conflicts
- Both plugins function normally
- No JavaScript errors
```

---

### Test 9.2: Theme Compatibility

**Test with Multiple Themes:**
```
- [ ] Twenty Twenty-Five (default)
- [ ] Twenty Twenty-Four
- [ ] Twenty Twenty-Three
- [ ] Kadence (popular)
- [ ] GeneratePress (popular)

Expected:
- Plugin works on all block themes
- Templates integrate with theme
- No layout breaks
```

**Classic Theme Test:**
```
- [ ] Switch to Twenty Twenty-One (classic theme)
- [ ] Plugin should still work
- [ ] Patterns should insert
- [ ] Templates may have limited effect (expected)
```

---

### Test 9.3: JavaScript Disabled

**Steps:**
1. Disable JavaScript in browser
2. Try to create post

**Expected Results:**
- [ ] Modal doesn't appear (expected)
- [ ] Can still set format via sidebar (falls back to core)
- [ ] Can still manually insert patterns (if patterns registered)
- [ ] Auto-detection still works (PHP-side)
- [ ] Repair tool still works
- [ ] Template assignment still works

---

### Test 9.4: Network / Offline

**Steps:**
1. Start creating post online
2. Disconnect network mid-edit
3. Continue editing

**Expected Results:**
- [ ] Can continue editing
- [ ] Autosave may fail (expected)
- [ ] No crashes
- [ ] Can save when back online

---

### Test 9.5: Very Long Content

**Steps:**
1. Create post with 10,000 words
2. Insert 50 blocks
3. Set format

**Expected Results:**
- [ ] Editor doesn't slow down significantly
- [ ] Save completes successfully
- [ ] Auto-detection runs without timeout
- [ ] Template assignment completes
- [ ] Frontend renders completely

---

### Test 9.6: Special Characters & Languages

**Test Content:**
```
- [ ] Unicode characters: "Hello 世界 🌍"
- [ ] Emoji in status: "Today is great! 😀🎉"
- [ ] Right-to-left text (Arabic): "مرحبا بك"
- [ ] Cyrillic: "Привет"
- [ ] Accented characters: "Café naïve résumé"

Expected:
- All characters display correctly
- No encoding issues
- Format detection works
- Save and load correctly
- Templates apply correctly
```

---

### Test 9.7: Multisite Installation

**If WordPress Multisite:**
```
- [ ] Network activate plugin
- [ ] Test on main site
- [ ] Test on subsite
- [ ] Test per-site activation
- [ ] Patterns work on all sites
- [ ] Repair tool works on each site
- [ ] Templates work on each site
```

---

### Test 9.8: User Roles & Capabilities

**Test with Different User Roles:**

**Administrator:**
```
- [ ] Can access all features
- [ ] Can use Repair Tool
- [ ] Can change formats
- [ ] Can lock/unlock blocks
- [ ] Can customize templates in Site Editor
```

**Editor:**
```
- [ ] Can access all features
- [ ] Can use Repair Tool
- [ ] Can change formats
- [ ] Can customize templates
```

**Author:**
```
- [ ] Can access format modal
- [ ] Can change format on own posts
- [ ] Cannot access Repair Tool (or can only see own posts)
- [ ] Cannot customize templates
```

**Contributor:**
```
- [ ] Can access format modal
- [ ] Can change format on drafts
- [ ] Cannot access Repair Tool
- [ ] Cannot publish (existing WP behavior)
- [ ] Cannot customize templates
```

**Subscriber:**
```
- [ ] Cannot create posts (existing WP behavior)
- [ ] Plugin doesn't appear
```

---

### Test 9.9: Autosave & Revisions

**Autosave Test:**
```
Steps:
1. Create post, select Gallery format
2. Wait for autosave (30 seconds)
3. Close browser tab (don't save manually)
4. Return to post

Expected:
- [ ] Format preserved in autosave
- [ ] Template assignment preserved
- [ ] Pattern blocks preserved
- [ ] Lock status preserved
```

**Revisions Test:**
```
Steps:
1. Create post, set to Video format
2. Save
3. Change to Audio format
4. Save
5. Go to Revisions

Expected:
- [ ] Each revision shows format
- [ ] Can restore previous format
- [ ] Content restored correctly
- [ ] Template assignment restored
```

---

### Test 9.10: Import/Export

**Export Test:**
```
Steps:
1. Create posts in all formats
2. Use Tools → Export
3. Export all content

Expected:
- [ ] Export file includes format metadata
- [ ] Locked blocks export correctly
- [ ] Custom classes preserved
- [ ] Template assignments may not export (expected)
```

**Import Test:**
```
Steps:
1. Import WXR file with formatted posts
2. Check imported posts

Expected:
- [ ] Formats imported correctly
- [ ] Blocks maintain structure
- [ ] Locks preserved (may need re-lock)
- [ ] Auto-detection can fix any issues
- [ ] Templates assigned via auto-detection or manual fix
```

---

### Test 9.11: Database Errors

**Simulate Errors:**
```
(In development environment only)

- [ ] Disable database briefly during save
- [ ] Check for graceful error handling
- [ ] Verify data integrity after reconnect
- [ ] No PHP fatal errors
- [ ] User sees helpful error message
```

---

## Performance Testing

### Test 10.1: Page Load Time

**Measure:**
```
Tools: Browser DevTools → Network tab

Test:
- [ ] Admin post edit page load time
- [ ] Modal appearance delay (should be ~500ms)
- [ ] Block inserter opening time
- [ ] Pattern insertion time
- [ ] Template application time
- [ ] Frontend single post load time

Expected:
- Admin loads in < 2 seconds
- Modal appears in ~0.5 seconds
- Patterns insert instantly
- Frontend loads in < 1 second
```

---

### Test 10.2: JavaScript Bundle Size

**Measure:**
```
Check: /build/index.js file size

Expected:
- [ ] Bundle size < 50KB (minified)
- [ ] Gzipped size < 15KB
- [ ] No unnecessary dependencies
```

---

### Test 10.3: Database Queries

**Measure:**
```
Install Query Monitor plugin

Test:
1. Edit post
2. Check query count

Expected:
- [ ] < 50 queries on post edit screen
- [ ] No duplicate queries
- [ ] No N+1 query problems
- [ ] Format detection adds minimal queries
- [ ] Template assignment adds 1-2 queries max
```

---

### Test 10.4: Large Media Handling

**Test:**
```
- [ ] Upload 50MB video
- [ ] Upload 10MB image
- [ ] Upload 100MB audio file
- [ ] Create gallery with 100 images

Expected:
- No timeouts
- No memory errors
- Progress indicators work
- Format detection completes
- Template assignment completes
```

---

## Cross-Browser Testing

### Test 11.1: Desktop Browsers

**Test in Each Browser:**

**Chrome/Chromium:**
```
- [ ] Modal displays correctly
- [ ] Patterns insert
- [ ] All JavaScript works
- [ ] No console errors
- [ ] Locked blocks work
- [ ] Templates apply
```

**Firefox:**
```
- [ ] Modal displays correctly
- [ ] Patterns insert
- [ ] All JavaScript works
- [ ] No console errors
- [ ] Locked blocks work
- [ ] Templates apply
```

**Safari:**
```
- [ ] Modal displays correctly
- [ ] Patterns insert
- [ ] All JavaScript works
- [ ] No console errors
- [ ] Locked blocks work
- [ ] Templates apply
```

**Edge:**
```
- [ ] Modal displays correctly
- [ ] Patterns insert
- [ ] All JavaScript works
- [ ] No console errors
- [ ] Templates apply
```

---

### Test 11.2: Mobile Browsers

**Test on Mobile Devices:**

**iOS Safari:**
```
- [ ] Admin interface usable
- [ ] Modal displays and works
- [ ] Touch interactions work
- [ ] Patterns insert correctly
- [ ] Frontend displays correctly
- [ ] Templates apply correctly
```

**Android Chrome:**
```
- [ ] Admin interface usable
- [ ] Modal displays and works
- [ ] Touch interactions work
- [ ] Patterns insert correctly
- [ ] Frontend displays correctly
- [ ] Templates apply correctly
```

---

### Test 11.3: Responsive Design

**Test at Breakpoints:**
```
- [ ] 320px (small phone)
- [ ] 375px (iPhone)
- [ ] 768px (tablet portrait)
- [ ] 1024px (tablet landscape)
- [ ] 1280px (desktop)
- [ ] 1920px (large desktop)

Check:
- Modal adapts to screen size
- Format grid reflows
- Content readable at all sizes
- No horizontal scroll
- Touch targets minimum 44x44px
- Templates responsive
```

---

## Integration Testing

### Test 12.1: Chat Log Block Integration

**With Both Plugins Active:**
```
- [ ] Chat format appears in modal
- [ ] Selecting Chat inserts chatlog/conversation block
- [ ] Block is locked
- [ ] Auto-detection works with chat block
- [ ] Repair tool recognizes chat format
- [ ] Frontend displays chat properly
- [ ] Template applied: "single-format-chat"
```

**Chat Log Only (No Post Formats):**
```
- [ ] Chat Log block works standalone
- [ ] Can insert block manually
- [ ] No format auto-assignment
- [ ] No errors
```

**Post Formats Only (No Chat Log):**
```
- [ ] Chat format hidden or grayed out
- [ ] Other 9 formats work
- [ ] No JavaScript errors
- [ ] Repair tool skips chat detection
```

---

### Test 12.2: Bookmark Card Integration

**With Bookmark Card Plugin:**
```
- [ ] Link format uses bookmark-card block
- [ ] Pattern inserts bookmark card
- [ ] Auto-detection works
- [ ] Card fetches metadata
- [ ] Template applied: "single-format-link"
```

**Without Bookmark Card:**
```
- [ ] Link format falls back gracefully
- [ ] Uses core/embed or instruction paragraph
- [ ] No errors
- [ ] Template still applies
```

---

### Test 12.3: Classic Editor Plugin

**If Classic Editor Active:**
```
Expected:
- [ ] Plugin doesn't load (Gutenberg only)
- [ ] No errors
- [ ] Doesn't break classic editor
- [ ] Can switch back to Gutenberg and use plugin
```

---

### Test 12.4: Full Site Editing (FSE)

**In Block Theme with FSE:**
```
- [ ] Plugin works in post editor
- [ ] Doesn't interfere with site editor
- [ ] Template editing works in Site Editor
- [ ] Global styles apply to formats
- [ ] Format templates appear in template list
- [ ] Can customize format templates
```

---

## Security Testing

### Test 13.1: Nonce Verification

**Test:**
```
1. Open browser DevTools → Network tab
2. Change format using switcher
3. Look for API requests

Expected:
- [ ] Nonce included in request
- [ ] Invalid nonce rejected
- [ ] Error message shown if nonce fails
```

---

### Test 13.2: Capability Checks

**Test as Non-Admin User:**
```
- [ ] Cannot access Repair Tool without proper caps
- [ ] Cannot change other users' post formats
- [ ] Cannot bypass locks without permission
- [ ] Cannot customize templates without permission
- [ ] SQL injection attempts fail gracefully
```

---

### Test 13.3: XSS Prevention

**Test:**
```
1. Try to insert JavaScript in format:
   - Post title: `<script>alert('XSS')</script>`
   - Format description: malicious HTML
   - Pattern content: script tags

Expected:
- [ ] All input sanitized
- [ ] Script tags removed or escaped
- [ ] No JavaScript execution
- [ ] Content displays safely
```

---

### Test 13.4: SQL Injection

**Test:**
```
Attempt to inject SQL in:
- Format slug
- Post ID parameter
- Search queries
- Template slugs

Expected:
- [ ] All queries use prepared statements
- [ ] No SQL errors in debug log
- [ ] Invalid input rejected
```

---

### Test 13.5: CSRF Protection

**Test:**
```
1. Copy Repair Tool form
2. Submit from external page
3. Try to apply changes

Expected:
- [ ] Request rejected (missing nonce)
- [ ] User not authenticated
- [ ] Error message shown
- [ ] No changes applied
```

---

## Final Integration Test

### The Complete Workflow

**Start to Finish:**

1. **Install & Activate** (5 min)
   - [ ] Install Post Formats Power-Up
   - [ ] Install Chat Log Block
   - [ ] Activate both
   - [ ] No errors in debug log
   - [ ] Check Appearance → Editor → Templates for format templates

2. **Create One Post Per Format** (20 min)
   - [ ] Create 10 posts (one for each format)
   - [ ] Use modal to select format
   - [ ] Add appropriate content
   - [ ] Add featured images
   - [ ] Verify template assigned for each
   - [ ] Publish all

3. **Test Auto-Detection** (10 min)
   - [ ] Create 5 posts without selecting format
   - [ ] Insert format-specific first blocks
   - [ ] Save and verify auto-detection
   - [ ] Verify template assignment

4. **Test Format Switching** (10 min)
   - [ ] Edit existing posts
   - [ ] Change formats via sidebar
   - [ ] Test "replace content" option
   - [ ] Test "keep content" option
   - [ ] Verify template changes

5. **Test Template Customization** (10 min)
   - [ ] Go to Appearance → Editor → Templates
   - [ ] Edit "Aside Format" template
   - [ ] Add background color
   - [ ] Save
   - [ ] View Aside post, verify customization

6. **Run Repair Tool** (5 min)
   - [ ] Create 3 posts with mismatches
   - [ ] Run Repair Tool scan
   - [ ] Test dry run
   - [ ] Apply fixes
   - [ ] Verify corrections and template assignments

7. **Frontend Verification** (10 min)
   - [ ] View all posts on frontend
   - [ ] Check single post views
   - [ ] Verify Aside and Status posts hide titles
   - [ ] Verify templates applied correctly
   - [ ] Check archive view
   - [ ] Check RSS feed

8. **Accessibility Check** (10 min)
   - [ ] Navigate with keyboard only
   - [ ] Test with screen reader
   - [ ] Check color contrast
   - [ ] Verify ARIA labels

9. **Edge Cases** (10 min)
   - [ ] Empty post
   - [ ] Very long post
   - [ ] Special characters
   - [ ] Multiple format changes

**Total Time: ~1.5 hours for complete test**

---

## Test Results Template

Use this template to record your findings:

```markdown
## Test Results - [DATE]

**Tester:** [Your Name]
**Environment:** [Browser, WP Version, PHP Version]
**Plugins:** Post Formats Power-Up v1.0.0, Chat Log Block v[X]

### ✅ Passed Tests
- Test 0.1: Template registration ✓
- Test 5.2: Aside format unstyled ✓
- Test 7.7: Title visibility ✓
- [List all passing tests]

### ❌ Failed Tests

#### Bug #1: [Description]
- **Test:** [Test number and name]
- **Expected:** [Expected behavior]
- **Actual:** [Actual behavior]
- **Severity:** [High/Medium/Low]
- **Files:** [Relevant files]

### 🔍 Observations
- [Any other notes, suggestions, or concerns]

### 📊 Test Summary
- Total Tests: XX
- Passed: XX
- Failed: XX
- Skipped: XX
- Pass Rate: XX%
```

---

## Template System Architecture

### Understanding the Template System

**Key Concepts:**

1. **Template Files**: Located in `/templates/single-format-[type].html`
   - Written in WordPress block markup
   - Define structure for each format
   - Can be customized via Site Editor

2. **Template Registration**: Handled by `class-format-styles.php`
   - `add_block_templates()` filter adds templates to list
   - `get_block_file_template()` filter loads template content
   - Templates appear in Appearance → Editor → Templates

3. **Automatic Assignment**: Handled by `auto_assign_template()`
   - Runs on `save_post` hook
   - Sets `_wp_page_template` meta based on format
   - Standard format clears template assignment

4. **Template Customization**:
   - Users edit templates in Site Editor
   - WordPress stores customizations in database
   - Original plugin templates remain unchanged
   - Can revert to default by deleting customization

---

## Priority Testing Order

If time is limited, test in this order:

1. **Critical Path** (30 min)
   - Template registration (Test 0.1)
   - Format modal appearance
   - Pattern insertion for all formats
   - Auto-detection basic test
   - Template assignment (Test 0.3)
   - Frontend display check

2. **Core Features** (30 min)
   - Format switcher
   - Template customization (Test 0.2)
   - Repair tool
   - Aside/Status title visibility
   - Aside/Status unstyled appearance
   - Chat Log integration

3. **Quality Assurance** (30 min)
   - Accessibility basics
   - Edge cases
   - Template persistence

4. **Nice to Have** (Time permitting)
   - Cross-browser testing
   - Performance testing
   - Security testing

---

**END OF TEST PLAN**

*Keep this document updated as issues are found and fixed!*
