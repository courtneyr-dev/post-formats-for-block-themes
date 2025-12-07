# Git Branching Strategy for Post Formats for Block Themes

This document explains the branching strategy for managing releases and development.

## Overview

We use a simplified **Git Flow** strategy optimized for WordPress plugin development:

- `main` - Production-ready code (matches WordPress.org releases)
- `develop` - Integration branch for next release
- `release/x.x.x` - Release preparation branches
- `feature/name` - Feature development branches (optional)
- `hotfix/name` - Emergency fixes for production (optional)

## Branch Descriptions

### main (Production Branch)
- Always matches the current WordPress.org release
- Protected branch - no direct commits
- Only merged from `release/x.x.x` or `hotfix/x.x.x` branches
- Tagged with version numbers (v1.0.0, v1.1.0, etc.)

### develop (Integration Branch)
- Active development happens here
- All new features merge into develop first
- Should always be stable enough to create a release from
- This is where day-to-day work accumulates

### release/x.x.x (Release Preparation)
- Created from `develop` when ready to release
- Version numbers updated here
- Changelog finalized here
- Bug fixes only (no new features)
- Testing happens on this branch
- Merged to both `main` and back to `develop`

### feature/name (Optional)
- Created from `develop` for new features
- Merged back to `develop` when complete
- Deleted after merge

### hotfix/x.x.x (Emergency Fixes)
- Created from `main` for critical production bugs
- Merged to both `main` and `develop`
- Tagged immediately

## Current Repository State

Based on the GitHub repository at https://github.com/courtneyr-dev/post-formats-for-block-themes:

- **Current branch:** `main`
- **Latest commit:** `f86544e` (23 commits total)
- **Current version:** 1.0.0 (pending WordPress.org approval)
- **Files present:** 34 items including source code, tests, documentation

## Setting Up for v1.1.0 Release

Since this is your first structured release after initial launch, we'll set up the branching strategy now.

### Step 1: Create develop branch from main

```bash
# Navigate to local repository
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes

# Make sure we have the latest from GitHub
git remote add origin https://github.com/courtneyr-dev/post-formats-for-block-themes.git
git fetch origin

# Create develop branch from current main
git checkout -b develop origin/main

# Push develop branch to GitHub
git push -u origin develop
```

### Step 2: Copy current working changes to develop

Your current local files have all the v1.1.0 changes. We need to commit these to develop:

```bash
# Make sure you're on develop
git checkout develop

# Stage all changes
git add .

# Check what will be committed
git status

# Commit the v1.1.0 changes
git commit -m "Prepare v1.1.0 development

- Add Post Format Block integration
- Add admin columns for post formats
- Update all templates with taxonomy display
- Fix template assignment issues
- Remove debug statements and ensure WordPress.org compliance
- Add comprehensive testing infrastructure
- Update documentation and changelogs

See CHANGELOG.md for full details"

# Push to GitHub
git push origin develop
```

### Step 3: Create release branch for v1.1.0

```bash
# Create release branch from develop
git checkout -b release/1.1.0 develop

# This branch is now ready for final testing and release
git push -u origin release/1.1.0
```

### Step 4: Final verification on release branch

```bash
# Make sure you're on release/1.1.0
git checkout release/1.1.0

# Verify all version numbers are correct
grep -r "Version.*1.1.0" post-formats-for-block-themes.php
grep "Stable tag.*1.1.0" readme.txt

# Run any final tests
# Check for WordPress.org compliance
# Test in clean WordPress installation
```

### Step 5: Merge to main and tag

Once release/1.1.0 is tested and approved:

```bash
# Switch to main
git checkout main

# Merge release branch (no fast-forward to preserve history)
git merge --no-ff release/1.1.0 -m "Release version 1.1.0"

# Create version tag
git tag -a v1.1.0 -m "Version 1.1.0 - Admin Columns & Post Format Block

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

# Push main with tags
git push origin main
git push origin v1.1.0
```

### Step 6: Merge back to develop

Keep develop in sync with the release:

```bash
# Switch to develop
git checkout develop

# Merge the release branch back
git merge --no-ff release/1.1.0 -m "Merge release/1.1.0 back to develop"

# Push to GitHub
git push origin develop
```

### Step 7: Create GitHub Release

1. Go to https://github.com/courtneyr-dev/post-formats-for-block-themes/releases/new
2. Choose tag: `v1.1.0`
3. Release title: `Version 1.1.0 - Admin Columns & Post Format Block`
4. Description: Copy from CHANGELOG.md
5. Click "Publish release"

### Step 8: Deploy to WordPress.org SVN

Follow the SVN instructions in RELEASE.md (unchanged from before).

## Future Workflow

For future releases (e.g., v1.2.0):

### Starting New Development

```bash
# Always work from develop branch
git checkout develop
git pull origin develop

# Optional: Create feature branch for major features
git checkout -b feature/new-awesome-feature develop

# Make changes, commit regularly
git add .
git commit -m "Add awesome new feature"

# When feature is complete, merge back to develop
git checkout develop
git merge --no-ff feature/new-awesome-feature
git push origin develop

# Delete feature branch
git branch -d feature/new-awesome-feature
```

### Preparing Next Release

```bash
# Create release branch when develop is ready
git checkout -b release/1.2.0 develop

# Update version numbers in files
# Update CHANGELOG.md
# Update readme.txt

# Commit version changes
git add .
git commit -m "Bump version to 1.2.0"

# Push release branch
git push -u origin release/1.2.0

# Test thoroughly on this branch
# Fix any bugs found during testing
# NO new features on release branches
```

### Completing Release

```bash
# Merge to main
git checkout main
git merge --no-ff release/1.2.0 -m "Release version 1.2.0"

# Tag the release
git tag -a v1.2.0 -m "Version 1.2.0 - Description"
git push origin main --tags

# Merge back to develop
git checkout develop
git merge --no-ff release/1.2.0
git push origin develop

# Delete release branch (optional, can keep for history)
git branch -d release/1.2.0
git push origin --delete release/1.2.0
```

## Handling Hotfixes

If a critical bug is found in production (main):

```bash
# Create hotfix branch from main
git checkout -b hotfix/1.1.1 main

# Fix the bug
# Update version to 1.1.1
# Update changelog

# Commit the fix
git add .
git commit -m "Fix critical bug in feature X"

# Merge to main
git checkout main
git merge --no-ff hotfix/1.1.1
git tag -a v1.1.1 -m "Hotfix 1.1.1 - Fix critical bug"
git push origin main --tags

# Merge to develop
git checkout develop
git merge --no-ff hotfix/1.1.1
git push origin develop

# Delete hotfix branch
git branch -d hotfix/1.1.1
```

## Branch Protection Rules (Recommended)

Set up on GitHub under Settings > Branches:

### For main branch:
- ✅ Require pull request reviews before merging
- ✅ Require status checks to pass
- ✅ Require branches to be up to date
- ✅ Include administrators (enforce rules for everyone)

### For develop branch:
- ✅ Require status checks to pass
- Optional: Require pull request reviews

## Visual Workflow

```
main (production)
  |
  |--- tag v1.0.0
  |
  |--- develop (ongoing work)
        |
        |--- feature/admin-columns (merged)
        |--- feature/post-format-block (merged)
        |
        |--- release/1.1.0 (testing & version updates)
              |
              |--- merge to main --> tag v1.1.0
              |--- merge back to develop
              |
develop (continue work on v1.2.0)
```

## Advantages of This Strategy

1. **Clear separation:** Production code (main) vs development code (develop)
2. **Safe releases:** Release branches allow testing without blocking development
3. **Emergency fixes:** Hotfix branches for production issues
4. **Clean history:** No fast-forward merges preserve branch structure
5. **Easy rollback:** Tags make it simple to revert to any version
6. **Parallel work:** Multiple features can be developed simultaneously

## Commands Reference

```bash
# Check current branch
git branch

# View all branches (local and remote)
git branch -a

# Switch branches
git checkout branch-name

# Create new branch
git checkout -b new-branch-name

# Merge branch (no fast-forward)
git merge --no-ff branch-name

# Create annotated tag
git tag -a v1.1.0 -m "Message"

# Push tags
git push origin --tags

# View tags
git tag -l

# Delete local branch
git branch -d branch-name

# Delete remote branch
git push origin --delete branch-name
```

## Need Help?

- Git Flow guide: https://nvie.com/posts/a-successful-git-branching-model/
- GitHub Flow (simpler): https://guides.github.com/introduction/flow/
- Git branching docs: https://git-scm.com/book/en/v2/Git-Branching-Branching-Workflows

## Notes for This Project

- First structured release setup (v1.1.0)
- Repository already exists with v1.0.0 code
- v1.0.0 is pending WordPress.org approval
- v1.1.0 adds significant new features
- Future releases will follow this workflow from day one
