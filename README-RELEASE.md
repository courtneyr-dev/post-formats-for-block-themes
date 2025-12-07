# Release Documentation Overview

This directory contains comprehensive documentation for releasing version 1.1.0 and managing future releases using Git branching strategy.

## 📚 Documentation Files

### Quick Start (Read This First!)
- **QUICK-START-RELEASE.md** - Copy/paste commands to release v1.1.0 quickly
- **BRANCHING-DIAGRAM.txt** - Visual ASCII diagrams of the Git workflow

### Detailed Guides
- **RELEASE.md** - Complete step-by-step release process for GitHub and WordPress.org SVN
- **BRANCHING-STRATEGY.md** - Full explanation of Git Flow branching model and rationale
- **GIT-TROUBLESHOOTING.md** - Common problems and solutions

### Project Documentation
- **CHANGELOG.md** - Version history for GitHub releases
- **readme.txt** - WordPress.org plugin readme (includes changelog)

## 🚀 Quick Decision Guide

**"I want to release v1.1.0 now"**
→ Read: QUICK-START-RELEASE.md

**"I want to understand the branching strategy first"**
→ Read: BRANCHING-STRATEGY.md, then BRANCHING-DIAGRAM.txt

**"I ran into a Git error"**
→ Read: GIT-TROUBLESHOOTING.md

**"I need detailed step-by-step instructions"**
→ Read: RELEASE.md

**"What changed in v1.1.0?"**
→ Read: CHANGELOG.md

## 📋 Pre-Release Checklist

Before you start, verify:

- ✅ All code changes completed and tested locally
- ✅ Version numbers updated to 1.1.0 in:
  - post-formats-for-block-themes.php (line 6 and 41)
  - readme.txt (line 8)
- ✅ CHANGELOG.md created with v1.1.0 entry
- ✅ readme.txt updated with v1.1.0 changelog (lines 350-428)
- ✅ All WordPress.org Plugin Check errors resolved
- ✅ No debug statements (error_log, var_dump, etc.)
- ✅ All variables properly prefixed with `pfbt_`
- ✅ All output properly escaped
- ✅ Files follow WordPress Coding Standards

## 🌳 Branching Strategy Overview

```
main (production)
  └── develop (ongoing work)
       └── release/1.1.0 (testing)
            ├── merge to main → tag v1.1.0
            └── merge back to develop
```

**Key Branches:**
- `main` - Production code (matches WordPress.org)
- `develop` - Integration branch for next release
- `release/1.1.0` - Release preparation and testing

## 🎯 Release Process Summary

### Part 1: GitHub Release

1. **Setup branches** (first time only)
   - Create `develop` branch from `main`
   - Commit v1.1.0 changes to `develop`
   - Create `release/1.1.0` branch

2. **Test and finalize**
   - Test on `release/1.1.0` branch
   - Fix bugs if found

3. **Merge and tag**
   - Merge `release/1.1.0` to `main`
   - Tag `main` with `v1.1.0`
   - Merge back to `develop`

4. **Create GitHub release**
   - Use tag `v1.1.0`
   - Add changelog description

### Part 2: WordPress.org SVN

1. **Update trunk**
   - Copy files to SVN trunk
   - Commit changes

2. **Create tag**
   - Create `tags/1.1.0` from trunk
   - Commit tag

3. **Verify**
   - Wait 5-15 minutes
   - Check WordPress.org plugin page

## 📁 Files Modified in v1.1.0

### New Files
- `blocks/post-format-block/post-format-block.php`
- `blocks/post-format-block/block.json`
- `blocks/post-format-block/index.min.js`
- `includes/class-admin-columns.php`
- `CHANGELOG.md`
- `BRANCHING-STRATEGY.md`
- `BRANCHING-DIAGRAM.txt`
- `RELEASE.md`
- `QUICK-START-RELEASE.md`
- `GIT-TROUBLESHOOTING.md`
- `README-RELEASE.md` (this file)

### Modified Files
- `post-formats-for-block-themes.php` - Version bump, new includes
- `readme.txt` - Changelog, stable tag
- `templates/single-format-aside.html` - Added taxonomy display
- `templates/single-format-audio.html` - Added taxonomy display
- `templates/single-format-chat.html` - Added taxonomy display
- `templates/single-format-gallery.html` - Added taxonomy display
- `templates/single-format-image.html` - Added taxonomy display
- `templates/single-format-link.html` - Added taxonomy display
- `templates/single-format-quote.html` - Added taxonomy display
- `templates/single-format-status.html` - Added taxonomy display
- `templates/single-format-video.html` - Added taxonomy display
- `templates/repair-tool-page.php` - Variable naming fix
- `includes/class-format-styles.php` - Removed debug statements

### Renamed Files
- `Post Formats for Block Themes (1544 x 500 px).png` → `banner-1544x500.png`
- `Post Formats for Block Themes (256 x 256 px).png` → `icon-256x256.png`
- `Post Formats for Block Themes 772 x 250.png` → `banner-772x250.png`

## ⚙️ Repository Information

- **GitHub:** https://github.com/courtneyr-dev/post-formats-for-block-themes
- **WordPress.org:** https://wordpress.org/plugins/post-formats-for-block-themes/
- **WordPress.org SVN:** https://plugins.svn.wordpress.org/post-formats-for-block-themes/

## 🔄 Future Development Workflow

After releasing v1.1.0, all future development should:

1. **Start from develop**
   ```bash
   git checkout develop
   git pull origin develop
   ```

2. **Make changes and commit regularly**
   ```bash
   git add .
   git commit -m "Add new feature"
   git push origin develop
   ```

3. **When ready for next release (e.g., v1.2.0)**
   ```bash
   git checkout -b release/1.2.0 develop
   # Update version numbers
   # Test
   # Merge to main and tag
   # Deploy to SVN
   ```

## 🆘 Emergency Hotfix

For critical bugs in production:

```bash
# Create hotfix from main
git checkout -b hotfix/1.1.1 main

# Fix bug, update version
# Commit

# Merge to BOTH main and develop
git checkout main
git merge --no-ff hotfix/1.1.1
git tag -a v1.1.1 -m "Hotfix..."
git push origin main --tags

git checkout develop
git merge --no-ff hotfix/1.1.1
git push origin develop

# Deploy to SVN
```

## 📝 Important Notes

### First Release Setup
This is your first release using the branching strategy. After v1.1.0:
- `main` will have v1.1.0 (tagged)
- `develop` will be ready for v1.2.0 work
- Future releases will be much simpler

### WordPress.org Status
- Current version on WordPress.org: 1.0.0
- Plugin is pending approval by WordPress.org team
- v1.1.0 will be submitted after approval

### Files Not Included in WordPress.org Release
These documentation files are for GitHub only (excluded via `.distignore` or manual exclusion):
- BRANCHING-STRATEGY.md
- BRANCHING-DIAGRAM.txt
- RELEASE.md
- QUICK-START-RELEASE.md
- GIT-TROUBLESHOOTING.md
- README-RELEASE.md

## 🎓 Learning Resources

### Git
- Official Git Book: https://git-scm.com/book
- GitHub Guides: https://guides.github.com/
- Git Flow Model: https://nvie.com/posts/a-successful-git-branching-model/

### WordPress Plugin Development
- Plugin Handbook: https://developer.wordpress.org/plugins/
- SVN Guide: https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- Plugin Check Guidelines: https://wordpress.org/plugins/developers/

### Semantic Versioning
- https://semver.org/

## ✅ Release Verification Checklist

After completing the release:

- [ ] GitHub shows v1.1.0 release with tag
- [ ] GitHub has `main`, `develop`, and `release/1.1.0` branches
- [ ] WordPress.org shows version 1.1.0
- [ ] Plugin can be downloaded and installed from WordPress.org
- [ ] Changelog displays correctly on WordPress.org
- [ ] Test installation in clean WordPress site
- [ ] All features work as expected
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors

## 🚦 What's Next?

After successful v1.1.0 release:

1. **Monitor** - Watch for user feedback and bug reports
2. **Plan** - Start planning v1.2.0 features
3. **Develop** - All work on `develop` branch
4. **Document** - Update changelog as you go
5. **Release** - Follow same process for v1.2.0

## 💡 Tips

- **Commit often** - Small, frequent commits are better than large ones
- **Test thoroughly** - Use `release/X.X.X` branch for testing
- **Write good commit messages** - Future you will thank you
- **Keep develop stable** - It should always be in a releasable state
- **Use branches** - Don't work directly on main or develop for major changes
- **Tag everything** - Tags make rollback easy
- **Read the docs** - When in doubt, consult the guides

## 📞 Getting Help

If you encounter issues:

1. Check **GIT-TROUBLESHOOTING.md** for common problems
2. Run diagnostic commands:
   ```bash
   git status
   git branch -a
   git remote -v
   git log --oneline -5
   ```
3. Search GitHub Issues: https://github.com/courtneyr-dev/post-formats-for-block-themes/issues
4. WordPress.org support: https://wordpress.org/support/plugin/post-formats-for-block-themes/

## 🎉 You're Ready!

Everything is prepared for your v1.1.0 release. Start with **QUICK-START-RELEASE.md** and follow the commands. Good luck! 🚀
