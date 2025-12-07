# Quick Start: Release v1.1.0

This is a condensed version of the release process. For detailed explanations, see:
- **RELEASE.md** - Complete step-by-step release instructions
- **BRANCHING-STRATEGY.md** - Detailed branching model explanation

## Overview

You're releasing v1.1.0 using a proper Git branching strategy:
- `main` = production (matches WordPress.org)
- `develop` = ongoing development
- `release/1.1.0` = release preparation and testing

## Prerequisites

✅ All changes are in your local files
✅ Version numbers updated to 1.1.0
✅ CHANGELOG.md created
✅ readme.txt updated
✅ WordPress.org plugin check passed

## Part 1: GitHub Release (Copy & Paste Commands)

### Step 1: Navigate to Plugin Directory

```bash
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes
```

### Step 2: Initialize Git (if needed)

```bash
# Check if git is initialized
git status

# If not initialized:
git init

# Add GitHub remote
git remote add origin https://github.com/courtneyr-dev/post-formats-for-block-themes.git

# Fetch from GitHub
git fetch origin
```

### Step 3: Create and Setup Branches

```bash
# Create local main from GitHub
git checkout -b main origin/main

# Create develop branch
git checkout -b develop

# Add your v1.1.0 changes
git add .

# Commit to develop
git commit -m "Prepare v1.1.0 development

- Add Post Format Block integration
- Add admin columns for post formats
- Update all templates with taxonomy display
- Fix template assignment issues
- WordPress.org compliance fixes

See CHANGELOG.md for details"

# Push develop to GitHub
git push -u origin develop

# Create release branch
git checkout -b release/1.1.0 develop

# Push release branch
git push -u origin release/1.1.0
```

### Step 4: Test on release/1.1.0 Branch

Do your final testing now. If you find bugs:

```bash
# Make fixes
git add .
git commit -m "Fix [bug description]"
git push origin release/1.1.0
```

### Step 5: Merge to main and Tag

```bash
# Switch to main
git checkout main
git pull origin main

# Merge release (no fast-forward)
git merge --no-ff release/1.1.0 -m "Release version 1.1.0"

# Create version tag
git tag -a v1.1.0 -m "Version 1.1.0 - Admin Columns & Post Format Block

See CHANGELOG.md for details"

# Push main and tags
git push origin main
git push origin v1.1.0

# Merge back to develop
git checkout develop
git merge --no-ff release/1.1.0 -m "Merge release/1.1.0 back to develop"
git push origin develop
```

### Step 6: Create GitHub Release (Web Interface)

1. Go to: https://github.com/courtneyr-dev/post-formats-for-block-themes/releases/new
2. Choose tag: `v1.1.0`
3. Title: `Version 1.1.0 - Admin Columns & Post Format Block`
4. Description: Copy from CHANGELOG.md
5. Publish release

## Part 2: WordPress.org SVN Release

### Step 1: Navigate and Update SVN

```bash
# Go to SVN directory
cd /Users/crobertson/downloads/postformats

# If first time, checkout SVN:
svn co https://plugins.svn.wordpress.org/post-formats-for-block-themes post-formats-svn

# Or update existing:
cd post-formats-svn
svn up
```

### Step 2: Copy Files to Trunk

```bash
# Clear trunk
cd /Users/crobertson/downloads/postformats/post-formats-svn/trunk
rm -rf *

# Copy new files (excludes git, node_modules, etc.)
rsync -av \
  --exclude='.git' \
  --exclude='.gitignore' \
  --exclude='node_modules' \
  --exclude='.DS_Store' \
  --exclude='RELEASE.md' \
  --exclude='BRANCHING-STRATEGY.md' \
  --exclude='QUICK-START-RELEASE.md' \
  /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes/ \
  /Users/crobertson/downloads/postformats/post-formats-svn/trunk/

# Go to SVN root
cd /Users/crobertson/downloads/postformats/post-formats-svn

# Add new files
svn add trunk/* --force

# Remove deleted files
svn status | grep '^!' | awk '{print $2}' | xargs svn delete --force 2>/dev/null || true

# Check what changed
svn status
```

### Step 3: Commit to Trunk

```bash
cd /Users/crobertson/downloads/postformats/post-formats-svn

svn ci -m "Update trunk to version 1.1.0

New Features:
- Post Format Block for frontend display
- Post format column in admin list

Improvements:
- Template assignment system
- REST API support

Bug Fixes:
- Template dropdown issues
- Plugin check compliance

See readme.txt changelog for details"
```

### Step 4: Create Tag

```bash
cd /Users/crobertson/downloads/postformats/post-formats-svn

# Create tag from trunk
svn cp trunk tags/1.1.0

# Commit tag
svn ci -m "Tag version 1.1.0"
```

### Step 5: Verify Release

Wait 5-15 minutes, then check:
- Plugin page: https://wordpress.org/plugins/post-formats-for-block-themes/
- Version shows 1.1.0
- Changelog displays correctly

## Visual Workflow

```
1. Local files (v1.1.0 changes)
   ↓
2. commit to develop branch
   ↓
3. create release/1.1.0 branch
   ↓
4. test & fix bugs on release/1.1.0
   ↓
5. merge to main + create tag v1.1.0
   ↓
6. merge back to develop
   ↓
7. create GitHub release
   ↓
8. deploy to WordPress.org SVN
```

## What Each Branch Contains

**After Release:**

- `main` - v1.1.0 production code (tagged v1.1.0)
- `develop` - v1.1.0 code (ready for next development)
- `release/1.1.0` - can be deleted or kept for history

## Future Development

For your next feature (e.g., v1.2.0):

```bash
# Start from develop
git checkout develop
git pull origin develop

# Make changes, commit regularly
git add .
git commit -m "Add new feature"
git push origin develop

# When ready to release v1.2.0, repeat the process:
git checkout -b release/1.2.0 develop
# ... update version numbers ...
# ... test ...
# ... merge to main and tag ...
```

## Emergency Hotfix (Critical Bug in Production)

```bash
# Create hotfix from main
git checkout -b hotfix/1.1.1 main

# Fix bug, update version to 1.1.1
git add .
git commit -m "Fix critical bug"

# Merge to main
git checkout main
git merge --no-ff hotfix/1.1.1
git tag -a v1.1.1 -m "Hotfix 1.1.1"
git push origin main --tags

# Merge to develop
git checkout develop
git merge --no-ff hotfix/1.1.1
git push origin develop

# Deploy to SVN same as above
```

## Need Help?

- **Full instructions:** See RELEASE.md
- **Branching details:** See BRANCHING-STRATEGY.md
- **Git basics:** https://git-scm.com/book
- **SVN guide:** https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/

## Common Issues

**"fatal: not a git repository"**
→ Run `git init` in the plugin directory

**"remote origin already exists"**
→ Skip the `git remote add` command, run `git fetch origin` instead

**"branch main already exists"**
→ Run `git checkout main` instead of `git checkout -b main origin/main`

**"svn: E155037: Previous operation has not finished"**
→ Run `svn cleanup` then try again

**GitHub asks for password (deprecated)**
→ Use Personal Access Token: https://github.com/settings/tokens

## Post-Release Checklist

- [ ] GitHub shows v1.1.0 release
- [ ] WordPress.org shows v1.1.0
- [ ] Test install from WordPress.org
- [ ] Continue development on `develop` branch
