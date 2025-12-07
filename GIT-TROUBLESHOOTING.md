# Git Troubleshooting Guide

Common issues you might encounter during the release process and how to fix them.

## Table of Contents

1. [Git Setup Issues](#git-setup-issues)
2. [Branch Issues](#branch-issues)
3. [Remote/Push Issues](#remotepush-issues)
4. [Merge Conflicts](#merge-conflicts)
5. [Tag Issues](#tag-issues)
6. [Undo/Rollback Operations](#undorollback-operations)
7. [SVN Issues](#svn-issues)

---

## Git Setup Issues

### "fatal: not a git repository"

**Problem:** Git is not initialized in the directory

**Solution:**
```bash
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes
git init
```

### "remote origin already exists"

**Problem:** You're trying to add a remote that already exists

**Solution:**
```bash
# Check existing remotes
git remote -v

# If wrong URL, update it
git remote set-url origin https://github.com/courtneyr-dev/post-formats-for-block-themes.git

# Or remove and re-add
git remote remove origin
git remote add origin https://github.com/courtneyr-dev/post-formats-for-block-themes.git
```

### "Permission denied (publickey)"

**Problem:** SSH authentication not set up or key not added to GitHub

**Solution Option 1 - Use HTTPS instead:**
```bash
git remote set-url origin https://github.com/courtneyr-dev/post-formats-for-block-themes.git
```

**Solution Option 2 - Set up SSH key:**
1. Generate SSH key: https://docs.github.com/en/authentication/connecting-to-github-with-ssh/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent
2. Add to GitHub: https://docs.github.com/en/authentication/connecting-to-github-with-ssh/adding-a-new-ssh-key-to-your-github-account

### "Support for password authentication was removed"

**Problem:** GitHub deprecated password authentication

**Solution:** Use Personal Access Token (PAT)
1. Go to https://github.com/settings/tokens
2. Click "Generate new token (classic)"
3. Select scopes: `repo` (full control of private repositories)
4. Copy the token
5. When git asks for password, paste the token instead

**Or store credentials:**
```bash
# Store credentials permanently
git config --global credential.helper store

# Next time you push, enter username and token (not password)
git push origin main
```

---

## Branch Issues

### "branch 'main' already exists"

**Problem:** You're trying to create a branch that exists

**Solution:**
```bash
# Just switch to it instead
git checkout main

# Or if you want to reset it to origin/main
git checkout main
git reset --hard origin/main
```

### "Your branch is behind 'origin/main'"

**Problem:** Remote has newer commits than your local branch

**Solution:**
```bash
git pull origin main

# Or if you want to discard local changes
git fetch origin
git reset --hard origin/main
```

### "Your branch and 'origin/main' have diverged"

**Problem:** Local and remote branches have different commits

**Solution:**
```bash
# If you want to keep your changes
git pull --rebase origin main

# If you want to discard local changes
git fetch origin
git reset --hard origin/main
```

### "fatal: refusing to merge unrelated histories"

**Problem:** Local repo and GitHub repo don't share common history

**Solution:**
```bash
git pull origin main --allow-unrelated-histories
```

### Can't switch branches: "Please commit your changes or stash them"

**Problem:** You have uncommitted changes

**Solution Option 1 - Commit changes:**
```bash
git add .
git commit -m "WIP: saving work in progress"
git checkout other-branch
```

**Solution Option 2 - Stash changes (temporary storage):**
```bash
git stash
git checkout other-branch

# Later, to restore stashed changes:
git stash pop
```

---

## Remote/Push Issues

### "Updates were rejected because the remote contains work"

**Problem:** Remote has commits you don't have locally

**Solution:**
```bash
# Pull and merge
git pull origin main

# Resolve any conflicts if they occur
# Then push
git push origin main
```

### "failed to push some refs"

**Problem:** Usually means remote has newer commits or you're behind

**Solution:**
```bash
# Fetch and check status
git fetch origin
git status

# Pull changes
git pull origin main

# Push again
git push origin main
```

### "error: src refspec main does not match any"

**Problem:** Branch doesn't exist or has no commits

**Solution:**
```bash
# Make sure you have commits
git log

# If no commits, make initial commit
git add .
git commit -m "Initial commit"

# Then push
git push origin main
```

### "Everything up-to-date" but tag not pushed

**Problem:** Tags aren't pushed automatically

**Solution:**
```bash
# Push specific tag
git push origin v1.1.0

# Or push all tags
git push origin --tags
```

---

## Merge Conflicts

### "Automatic merge failed; fix conflicts"

**Problem:** Git can't automatically merge changes

**Solution:**
```bash
# Check which files have conflicts
git status

# Open conflicted files, you'll see markers like:
<<<<<<< HEAD
Your changes
=======
Their changes
>>>>>>> branch-name

# Edit the file to resolve conflicts (remove markers, keep what you want)

# After resolving all conflicts:
git add .
git commit -m "Resolve merge conflicts"
```

### Abort a merge that went wrong

**Solution:**
```bash
git merge --abort
```

### Complex conflict you can't resolve

**Solution - Use one side completely:**
```bash
# Use "ours" (your current branch)
git checkout --ours path/to/file.php
git add path/to/file.php

# Use "theirs" (branch being merged)
git checkout --theirs path/to/file.php
git add path/to/file.php

# After choosing for all conflicts:
git commit -m "Resolve merge conflicts"
```

---

## Tag Issues

### "tag 'v1.1.0' already exists"

**Problem:** You're trying to create a tag that exists

**Solution - Delete and recreate:**
```bash
# Delete local tag
git tag -d v1.1.0

# Delete remote tag
git push origin :refs/tags/v1.1.0

# Create new tag
git tag -a v1.1.0 -m "Version 1.1.0"

# Push new tag
git push origin v1.1.0
```

### Tag points to wrong commit

**Problem:** Tagged the wrong commit

**Solution:**
```bash
# Delete tag
git tag -d v1.1.0
git push origin :refs/tags/v1.1.0

# Find the right commit
git log

# Create tag on specific commit
git tag -a v1.1.0 <commit-hash> -m "Version 1.1.0"

# Push
git push origin v1.1.0
```

### List all tags

```bash
git tag -l

# Show tag details
git show v1.1.0
```

---

## Undo/Rollback Operations

### Undo last commit (keep changes)

```bash
# Undo commit but keep changes staged
git reset --soft HEAD~1

# Undo commit and unstage changes (keep in working directory)
git reset HEAD~1

# Undo commit and discard all changes (DANGEROUS!)
git reset --hard HEAD~1
```

### Undo changes to a specific file

```bash
# Discard changes to one file
git checkout -- path/to/file.php

# Or with newer Git syntax
git restore path/to/file.php
```

### Revert a pushed commit

```bash
# Find commit hash
git log

# Create a new commit that undoes the specified commit
git revert <commit-hash>
git push origin main
```

### Accidentally committed to wrong branch

```bash
# Create new branch with your changes
git branch correct-branch

# Reset current branch to before your commits
git reset --hard HEAD~1

# Switch to correct branch
git checkout correct-branch
```

### Discard all local changes

```bash
# Discard all uncommitted changes (DANGEROUS!)
git reset --hard HEAD

# Remove untracked files too
git clean -fd
```

---

## SVN Issues

### "svn: E155037: Previous operation has not finished"

**Problem:** SVN working copy is locked

**Solution:**
```bash
cd /Users/crobertson/downloads/postformats/post-formats-svn
svn cleanup
```

### "svn: E155004: Working copy is too old"

**Problem:** SVN format needs upgrade

**Solution:**
```bash
cd /Users/crobertson/downloads/postformats/post-formats-svn
svn upgrade
```

### "svn: E200009: Can't add file: it's already under version control"

**Problem:** File already tracked by SVN

**Solution:**
```bash
# Just commit without adding
svn ci -m "Update files"

# Or if you need to add only new files
svn add trunk/* --force
```

### "svn: E195012: Can't commit to repository"

**Problem:** Need to update working copy first

**Solution:**
```bash
svn up
# Resolve any conflicts
svn ci -m "Your message"
```

### Check SVN status and fix issues

```bash
# See what's changed
svn status

# Legend:
# M = modified
# A = added
# D = deleted
# ? = not under version control
# ! = missing

# Add all untracked files
svn status | grep '^?' | awk '{print $2}' | xargs svn add 2>/dev/null || true

# Remove all missing files
svn status | grep '^!' | awk '{print $2}' | xargs svn delete --force 2>/dev/null || true
```

---

## Prevention Tips

### Before Starting Work

```bash
# Always pull latest changes first
git checkout develop
git pull origin develop
```

### Before Pushing

```bash
# Check what you're about to push
git status
git diff origin/main

# Make sure you're on the right branch
git branch
```

### Regular Backups

```bash
# Create a backup branch before risky operations
git branch backup-2024-12-07

# Or tag current state
git tag backup-before-merge

# Later, restore if needed
git checkout backup-2024-12-07
```

### Use Git GUI Tools

If command line is overwhelming, use a GUI:
- **GitHub Desktop** - https://desktop.github.com/
- **Sourcetree** - https://www.sourcetreeapp.com/
- **GitKraken** - https://www.gitkraken.com/
- **VS Code** - Built-in Git support

---

## Useful Git Commands

### Check Status
```bash
# Current branch and changes
git status

# Branch list
git branch -a

# Log with graph
git log --oneline --graph --all

# What will be pushed
git diff origin/main
```

### Inspect Changes
```bash
# See all changes
git diff

# See staged changes
git diff --staged

# See changes in specific file
git diff path/to/file.php
```

### Branch Management
```bash
# List all branches
git branch -a

# Delete local branch
git branch -d branch-name

# Delete remote branch
git push origin --delete branch-name

# Rename current branch
git branch -m new-name
```

### Remote Management
```bash
# List remotes
git remote -v

# Show remote details
git remote show origin

# Update remote URL
git remote set-url origin https://github.com/user/repo.git
```

---

## Emergency: Start Over

If everything is broken and you want to start fresh:

### Option 1: Re-clone from GitHub

```bash
# Backup your local changes
cp -r /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes /tmp/plugin-backup

# Remove git folder
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes
rm -rf .git

# Clone fresh from GitHub
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins
rm -rf post-formats-for-block-themes
git clone https://github.com/courtneyr-dev/post-formats-for-block-themes.git

# Copy your changes back if needed
```

### Option 2: Reset to Remote State

```bash
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes

# Fetch latest
git fetch origin

# Reset to remote main
git checkout main
git reset --hard origin/main

# Clean untracked files
git clean -fd
```

---

## Getting Help

- **Git Documentation:** https://git-scm.com/doc
- **GitHub Guides:** https://guides.github.com/
- **SVN Book:** http://svnbook.red-bean.com/
- **WordPress SVN:** https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/

## Need More Help?

Run these commands and share the output:

```bash
cd /Users/crobertson/Local\ Sites/post-formats-test/app/public/wp-content/plugins/post-formats-for-block-themes

# Check Git status
git status
git branch -a
git remote -v
git log --oneline -5

# Check SVN status (if applicable)
cd /Users/crobertson/downloads/postformats/post-formats-svn
svn status
svn info
```

This information helps diagnose issues.
