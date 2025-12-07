# Release Guide for Post Formats for Block Themes v1.1.0

This guide provides step-by-step instructions for releasing version 1.1.0 to both GitHub and WordPress.org SVN using proper Git branching strategy.

**Repository:** https://github.com/courtneyr-dev/post-formats-for-block-themes

**Important:** See BRANCHING-STRATEGY.md for detailed explanation of the branching model.

## Pre-Release Checklist

- [x] All code changes completed and tested
- [x] Version numbers updated in all files
- [x] CHANGELOG.md created/updated
- [x] readme.txt updated with new changelog
- [x] All WordPress.org Plugin Check errors resolved
- [x] All files follow WordPress Coding Standards
- [ ] Set up Git branching structure (develop, release/1.1.0)
- [ ] Final testing in clean WordPress installation
- [ ] Merge release branch to main and tag
- [ ] Create GitHub release
- [ ] Deploy to WordPress.org SVN

## Part 1: GitHub Release with Branching Strategy

### Step 1: Connect to GitHub Repository

The repository exists at https://github.com/courtneyr-dev/post-formats-for-block-themes

```bash
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes

# Check if git is initialized
git status

# If not initialized, initialize it
git init

# Add remote (only if not already added)
git remote add origin https://github.com/courtneyr-dev/post-formats-for-block-themes.git

# Fetch latest from GitHub
git fetch origin

# Check current branches
git branch -a
```

### Step 2: Create develop Branch (First Time Setup)

This establishes the ongoing development branch:

```bash
# Make sure you're on main (or create main from origin/main)
git checkout -b main origin/main

# Create develop branch from main
git checkout -b develop

# Push develop to GitHub
git push -u origin develop
```

### Step 3: Commit v1.1.0 Changes to develop

Your local files contain all v1.1.0 changes:

```bash
# Make sure you're on develop
git checkout develop

# Stage all changes
git add .

# Review what will be committed
git status

# Commit v1.1.0 development work
git commit -m "Prepare v1.1.0 development

- Add Post Format Block integration (forked from Aaron Jorbin's plugin)
- Add admin columns for post formats with filtering and sorting
- Update all 9 templates with categories, tags, and post format display
- Fix template assignment dropdown issues
- Remove all debug error_log() statements for WordPress.org compliance
- Fix variable naming to follow WordPress coding standards
- Fix escaping issues in admin columns
- Add comprehensive testing infrastructure
- Update documentation (CHANGELOG.md, BRANCHING-STRATEGY.md, RELEASE.md)

See CHANGELOG.md for full details"

# Push to GitHub
git push origin develop
```

### Step 4: Create Release Branch

Create a dedicated branch for final testing and release preparation:

```bash
# Create release branch from develop
git checkout -b release/1.1.0 develop

# Push release branch to GitHub
git push -u origin release/1.1.0

# This branch is now ready for final testing
```

### Step 5: Final Testing and Bug Fixes

Test thoroughly on the release/1.1.0 branch. If bugs are found:

```bash
# Make sure you're on release/1.1.0
git checkout release/1.1.0

# Fix any bugs found during testing
# Only bug fixes allowed - NO new features

# Commit fixes
git add .
git commit -m "Fix [describe bug] found during release testing"

# Push fixes
git push origin release/1.1.0
```

### Step 6: Merge Release to main and Tag

Once testing is complete and approved:

```bash
# Switch to main
git checkout main

# Pull latest changes
git pull origin main

# Merge release branch (no fast-forward to preserve history)
git merge --no-ff release/1.1.0 -m "Release version 1.1.0

New Features:
- Post Format Block for frontend display
- Post format column in admin list with filtering
- Post format taxonomy in all templates

Improvements:
- Template assignment system
- REST API support for post formats

Bug Fixes:
- Template dropdown display issues
- Plugin check compliance errors

See CHANGELOG.md for full details"

# Create annotated version tag
git tag -a v1.1.0 -m "Version 1.1.0 - Admin Columns & Post Format Block

New Features:
- Post Format Block for frontend display
- Post format column in admin list with filtering and sorting
- Post format taxonomy display in all 9 format templates
- Dashicons for each post format in admin column
- Screen Options toggle for post format column visibility

Improvements:
- Template assignment system now uses slug-only format
- Post format taxonomy now available in REST API
- Post format support properly merges with theme formats

Bug Fixes:
- Template assignment dropdown showing wrong templates
- Post format support conflicts with themes
- Plugin check errors for WordPress.org compliance

Code Quality:
- Removed all debug error_log() statements
- Fixed variable naming for WordPress coding standards
- Improved output escaping for security

See CHANGELOG.md for complete list of changes"

# Push main branch with tags
git push origin main
git push origin v1.1.0
```

### Step 7: Merge Back to develop

Keep develop in sync with the release:

```bash
# Switch to develop
git checkout develop

# Merge release branch back
git merge --no-ff release/1.1.0 -m "Merge release/1.1.0 back to develop"

# Push to GitHub
git push origin develop
```

### Step 8: Create GitHub Release

1. Go to https://github.com/courtneyr-dev/post-formats-for-block-themes/releases/new
2. Choose tag: `v1.1.0` (should exist from Step 6)
3. Release title: `Version 1.1.0 - Admin Columns & Post Format Block`
4. Description: Copy the "Added", "Changed", "Fixed", and "Removed" sections from CHANGELOG.md
5. Click "Publish release"

### Step 9: Clean Up (Optional)

You can optionally delete the release branch after successful release:

```bash
# Delete local release branch
git branch -d release/1.1.0

# Delete remote release branch (optional - keeping it preserves history)
git push origin --delete release/1.1.0
```

## Part 2: WordPress.org SVN Release

### Prerequisites

- SVN access to WordPress.org plugin repository
- SVN credentials ready

### Step 1: Checkout SVN Repository

```bash
# Navigate to a working directory (not the plugin directory)
cd /Users/crobertson/downloads/postformats

# Checkout the SVN repository (if not already checked out)
svn co https://plugins.svn.wordpress.org/post-formats-for-block-themes post-formats-svn

# Or update existing checkout
cd post-formats-svn
svn up
```

### Step 2: Update Trunk

```bash
# Remove old files from trunk
cd /Users/crobertson/downloads/postformats/post-formats-svn/trunk
rm -rf *

# Copy new plugin files to trunk
rsync -av --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='node_modules' \
  --exclude='.DS_Store' \
  --exclude='RELEASE.md' \
  /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes/ \
  /Users/crobertson/downloads/postformats/post-formats-svn/trunk/

# Check what files have changed
cd /Users/crobertson/downloads/postformats/post-formats-svn
svn status

# Add any new files
svn add trunk/* --force

# Remove any deleted files
svn status | grep '^!' | awk '{print $2}' | xargs svn delete --force

# Review changes
svn diff
```

### Step 3: Update Assets (if needed)

```bash
# Copy banner and icon images to assets directory
cd /Users/crobertson/downloads/postformats/post-formats-svn/assets

# Copy images if they've changed
cp /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes/banner-1544x500.png .
cp /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes/banner-772x250.png .
cp /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes/icon-256x256.png .

# Add if new
svn add *.png --force
```

### Step 4: Commit Changes to Trunk

```bash
cd /Users/crobertson/downloads/postformats/post-formats-svn

# Commit to trunk
svn ci -m "Update trunk to version 1.1.0

New Features:
- Post Format Block for frontend display
- Post format column in admin list with filtering
- Post format taxonomy in all templates

Improvements:
- Template assignment system
- REST API support for post formats

Bug Fixes:
- Template dropdown display issues
- Plugin check compliance errors"
```

### Step 5: Create SVN Tag

```bash
cd /Users/crobertson/downloads/postformats/post-formats-svn

# Create tag from trunk
svn cp trunk tags/1.1.0

# Commit the tag
svn ci -m "Tag version 1.1.0"
```

### Step 6: Verify Release

1. Wait 5-15 minutes for WordPress.org to process
2. Check plugin page: https://wordpress.org/plugins/post-formats-for-block-themes/
3. Verify version shows as 1.1.0
4. Verify changelog displays correctly
5. Test download and installation

## Post-Release Tasks

- [ ] Test installation from WordPress.org
- [ ] Monitor support forum for issues
- [ ] Share release announcement (optional)
- [ ] Create GitHub branch for next version development

## Rollback Procedure (if needed)

If critical issues are found:

```bash
# Revert to previous version in SVN
cd /Users/crobertson/downloads/postformats/post-formats-svn

# Copy old tag back to trunk
svn rm trunk
svn cp tags/1.0.0 trunk
svn ci -m "Rollback to version 1.0.0"

# Update readme stable tag
# Edit trunk/readme.txt and change "Stable tag: 1.1.0" to "Stable tag: 1.0.0"
svn ci trunk/readme.txt -m "Revert stable tag to 1.0.0"
```

## Files Modified in v1.1.0

### Core Files
- `post-formats-for-block-themes.php` - Version bump, new includes
- `readme.txt` - Changelog, stable tag

### New Files
- `blocks/post-format-block/post-format-block.php`
- `blocks/post-format-block/block.json`
- `blocks/post-format-block/index.min.js`
- `includes/class-admin-columns.php`
- `CHANGELOG.md`

### Modified Files
- All 9 template files (single-format-*.html)
- `templates/repair-tool-page.php` - Variable naming
- `includes/class-format-styles.php` - Removed debug statements

### Renamed Files
- `Post Formats for Block Themes (1544 x 500 px).png` → `banner-1544x500.png`
- `Post Formats for Block Themes (256 x 256 px).png` → `icon-256x256.png`
- `Post Formats for Block Themes 772 x 250.png` → `banner-772x250.png`

### Deleted Files
- `.distignore`
- `.wordpress-org/` directory
- All development/test files
- All `.bak`, `.backup` files

## Support

For issues with the release process:
- WordPress.org SVN: https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- GitHub: https://docs.github.com/en/repositories/releasing-projects-on-github

## Notes

- This is the first GitHub release (no v1.0.0 tag exists yet)
- WordPress.org SVN already has v1.0.0 from 2025-01-02
- All WordPress.org Plugin Check errors have been resolved
- Plugin is production-ready and compliant
