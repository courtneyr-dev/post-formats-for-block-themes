# WordPress.org Submission Checklist

**Plugin:** Post Formats for Block Themes
**Version:** 1.0.0
**Date:** December 1, 2025

---

## ✅ Pre-Submission Checklist (COMPLETED)

### Files Prepared

- ✅ **Submission ZIP:** `post-formats-for-block-themes-1.0.0.zip` (78KB)
- ✅ **Icon:** `.wordpress-org/icon-256x256.png` (26KB)
- ✅ **Banner Standard:** `.wordpress-org/banner-772x250.png` (345KB)
- ✅ **Banner Retina:** `.wordpress-org/banner-1544x500.png` (1.0MB)
- ✅ **Screenshots:** 6 screenshots in `.wordpress-org/` (screenshot-1.png through screenshot-6.png)
- ✅ **All files under 1MB**

### GitHub

- ✅ All code committed to main branch
- ✅ WordPress.org assets committed to `.wordpress-org/`
- ✅ Pushed to GitHub: https://github.com/courtneyr-dev/post-formats-for-block-themes
- ✅ README.md complete with developer documentation
- ✅ CHANGELOG.md exists
- ✅ Plugin development workflow documentation complete

### Plugin Package

- ✅ Main plugin file: `post-formats-for-block-themes.php` (Version 1.0.0)
- ✅ readme.txt properly formatted for WordPress.org
- ✅ No development files in ZIP (.git, node_modules, tests, etc.)
- ✅ No .bak or .backup files
- ✅ Built JavaScript and CSS included
- ✅ All PHP includes present
- ✅ All 10 format patterns included
- ✅ Site Editor templates included
- ✅ Chat Log block included with build files

### readme.txt Validation

- ✅ Contributors: `courane01` (matches WordPress.org username)
- ✅ Tags: 5 tags maximum, lowercase, hyphens
- ✅ Tested up to: 6.8 (current version)
- ✅ Stable tag: 1.0.0 (matches plugin version)
- ✅ Short description: Under 150 characters
- ✅ All required sections present
- ✅ Screenshot captions provided

### Code Quality

- ✅ WordPress Coding Standards compliant
- ✅ No PHP errors or warnings
- ✅ All user input sanitized
- ✅ All output escaped
- ✅ Nonce verification on all forms
- ✅ Capability checks present
- ✅ No SQL injection vulnerabilities
- ✅ No XSS vulnerabilities
- ✅ Proper text domain usage (`post-formats-for-block-themes`)

### Testing

- ✅ Tested in fresh WordPress 6.8 installation
- ✅ Tested with default theme (Twenty Twenty-Four)
- ✅ All features working as documented
- ✅ No console errors
- ✅ No PHP errors in logs
- ✅ Accessibility tests passing
- ✅ Keyboard navigation working
- ✅ Screen reader compatible

---

## 📋 WordPress.org Submission Steps

### Step 1: Submit Plugin

1. Go to: https://wordpress.org/plugins/developers/add/
2. Upload: `post-formats-for-block-themes-1.0.0.zip`
3. Click "Upload"
4. Wait for automated validation

**Expected:**
- Automated security scan
- ZIP validation
- readme.txt parsing
- Plugin enters review queue

### Step 2: Wait for Review

**Timeline:** Can take **several days to several weeks** depending on review queue

**What Reviewers Check:**
- Security (nonces, sanitization, escaping)
- Guideline compliance
- Code quality
- Trademark issues
- Documentation accuracy

**You'll receive:**
- Email when review is complete
- Approval or list of required changes

### Step 3: If Approved

**You'll receive:**
- Email notification with SVN repository URL
- Plugin slug: `post-formats-for-block-themes`
- SVN repo: `https://plugins.svn.wordpress.org/post-formats-for-block-themes/`

**Next steps:** See "Post-Approval SVN Setup" below

### Step 4: If Changes Requested

**If rejected or changes requested:**

1. **Read email carefully** - note all issues
2. **Fix all issues** - don't skip any
3. **Reply to email** - explain each fix with line numbers
4. **Attach updated ZIP**
5. **Be professional and courteous**

**Example response format:**
```
Thank you for the review. I've addressed all issues:

1. [Issue]: Nonce verification missing
   Fixed: Added wp_verify_nonce() on lines 234, 567

2. [Issue]: Output not escaped
   Fixed: Added esc_html() on lines 123-145

3. [Issue]: Direct file access allowed
   Fixed: Added ABSPATH check on line 32

Updated ZIP attached. All changes tested in fresh WP installation.
```

---

## 📦 Post-Approval: SVN Setup

**After approval, you'll set up SVN to manage your plugin:**

### SVN Structure

```
https://plugins.svn.wordpress.org/post-formats-for-block-themes/
├── trunk/           # Development version
├── tags/            # Release versions
│   └── 1.0.0/       # Version 1.0.0
├── assets/          # WordPress.org assets (separate from code!)
│   ├── icon-256x256.png
│   ├── banner-772x250.png
│   ├── banner-1544x500.png
│   └── screenshot-*.png
└── branches/        # Optional feature branches
```

### Initial SVN Commit Commands

```bash
# 1. Checkout SVN repository
svn co https://plugins.svn.wordpress.org/post-formats-for-block-themes post-formats-svn
cd post-formats-svn

# 2. Copy plugin files to trunk
cp -r /path/to/plugin/files/* trunk/
cd trunk
svn add --force *
svn status

# 3. Commit to trunk
svn ci -m "Initial commit of Post Formats for Block Themes 1.0.0"

# 4. Tag version 1.0.0
cd ..
svn cp trunk tags/1.0.0
svn ci -m "Tagging version 1.0.0"

# 5. Upload assets to assets directory
cp /path/to/.wordpress-org/icon-256x256.png assets/
cp /path/to/.wordpress-org/banner-772x250.png assets/
cp /path/to/.wordpress-org/banner-1544x500.png assets/
cp /path/to/.wordpress-org/screenshot-*.png assets/
cd assets
svn add *.png
svn ci -m "Add plugin assets (icon, banners, screenshots)"
```

**Important:**
- Assets directory is **separate** from plugin code
- Assets don't increase plugin download size
- Users only download plugin code from trunk/tags, not assets
- Assets appear on WordPress.org plugin page

### Verify Plugin Page

After SVN commit, check your plugin page:
- https://wordpress.org/plugins/post-formats-for-block-themes/

**Verify:**
- Icon displays in search results
- Banner displays at top of page
- Screenshots display in order
- Download button works
- readme.txt content displays correctly
- Version shows 1.0.0

---

## 🔄 Future Updates

**For version 1.1.0, 1.0.1, etc.:**

```bash
cd post-formats-svn

# 1. Update trunk with new code
cd trunk
# ... copy updated files
svn status
svn add new-file.php (if new files exist)
svn ci -m "Update feature X for version 1.1.0"

# 2. Tag new version
cd ..
svn cp trunk tags/1.1.0
svn ci -m "Tagging version 1.1.0"

# Users automatically notified of update
```

**Remember:**
- Update version in plugin file
- Update Stable tag in readme.txt
- Update CHANGELOG.md
- Update readme.txt changelog section
- Test new version thoroughly before tagging
- Create Git tag: `git tag 1.1.0 && git push --tags`

---

## 📊 Screenshot Captions Reference

**For WordPress.org readme.txt:**

```
== Screenshots ==

1. Format selection modal with 10 classic post formats and visual cards
2. Chat Log block in editor showing platform auto-detection for Slack transcript
3. Beautifully formatted chat conversation on published post (frontend)
4. Quote format with elegant styling and attribution
5. Status format with Twitter-style microblog layout
6. Repair tool for fixing legacy post format assignments
```

---

## 🎯 Common Rejection Reasons (Avoid These)

### Security Issues
- ❌ Missing nonce verification → ✅ All forms use `wp_nonce_field()` and verify
- ❌ Unescaped output → ✅ All output uses `esc_html()`, `esc_attr()`, etc.
- ❌ Unsanitized input → ✅ All input uses `sanitize_text_field()`, etc.

### Code Issues
- ❌ Including node_modules → ✅ Excluded via .distignore
- ❌ Deprecated functions → ✅ No deprecated WordPress functions used
- ❌ Function name conflicts → ✅ All functions properly namespaced

### Guideline Issues
- ❌ "Wordpress" in text → ✅ Always "WordPress" (capital P)
- ❌ Trademark violations → ✅ No trademark issues in plugin name
- ❌ Phone home without disclosure → ✅ No external calls

### Documentation Issues
- ❌ readme.txt formatting errors → ✅ Validated
- ❌ Contributors don't exist → ✅ `courane01` exists
- ❌ Outdated "Tested up to" → ✅ Current version (6.8)

---

## ✅ Ready to Submit!

**Everything is prepared and ready for WordPress.org submission.**

**Submission URL:** https://wordpress.org/plugins/developers/add/

**Plugin ZIP:** `/Users/crobertson/Downloads/postformats/post-formats-power-up/post-formats-for-block-themes-1.0.0.zip`

**Good luck with your submission!** 🚀
