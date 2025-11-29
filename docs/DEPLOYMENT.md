# Deployment Guide - Post Formats Power-Up

## Automated WordPress.org Deployment

This plugin uses GitHub Actions to automatically deploy to WordPress.org when you create a new release.

---

## Prerequisites

### 1. WordPress.org Plugin Account

- Plugin must be approved and listed on WordPress.org
- You need WordPress.org SVN credentials
- Plugin slug: `post-formats-power-up`

### 2. GitHub Repository Secrets

Add these secrets to your GitHub repository (`Settings` → `Secrets and variables` → `Actions`):

```
SVN_USERNAME=your-wordpress-org-username
SVN_PASSWORD=your-wordpress-org-password
```

**How to add secrets:**
1. Go to GitHub repository → Settings → Secrets and variables → Actions
2. Click "New repository secret"
3. Add `SVN_USERNAME` with your WordPress.org username
4. Add `SVN_PASSWORD` with your WordPress.org password

---

## Deployment Process

### Automatic Deployment (Recommended)

**Step 1: Prepare Release**

1. Update version numbers in:
   - `post-formats-power-up.php` (Plugin header)
   - `readme.txt` (Stable tag)
   - `package.json` (version)

2. Update `readme.txt` changelog with release notes

3. Commit changes:
   ```bash
   git add .
   git commit -m "Bump version to 1.1.0"
   git push origin main
   ```

**Step 2: Create GitHub Release**

1. Go to GitHub repository → Releases → "Draft a new release"
2. Click "Choose a tag" → Type new version (e.g., `v1.1.0`) → "Create new tag"
3. Set release title: `Version 1.1.0`
4. Add release notes (copy from readme.txt changelog)
5. Click "Publish release"

**Step 3: Automated Deployment Runs**

The GitHub Action will automatically:

✅ Checkout code
✅ Install dependencies (npm & composer)
✅ Build JavaScript assets (`npm run build`)
✅ Generate translation files (`composer i18n`)
✅ Create distribution ZIP
✅ Deploy to WordPress.org SVN
✅ Upload ZIP to GitHub release

**Monitor progress:** Go to repository → Actions tab → Watch the deployment

---

## Manual Deployment

If you need to deploy manually without GitHub Actions:

### 1. Prepare Plugin

```bash
# Install dependencies
npm install
composer install --no-dev --optimize-autoloader

# Build assets
npm run build

# Generate translations
composer i18n
```

### 2. Create Distribution ZIP

```bash
# Create clean distribution
mkdir -p dist
rsync -av --exclude-from='.distignore' . dist/post-formats-power-up/
cd dist
zip -r ../post-formats-power-up.zip post-formats-power-up/
cd ..
```

### 3. Deploy to WordPress.org SVN

```bash
# Checkout SVN repository
svn co https://plugins.svn.wordpress.org/post-formats-power-up svn-repo
cd svn-repo

# Update trunk
rsync -av --delete --exclude='.svn' ../dist/post-formats-power-up/ trunk/

# Add new files
svn add --force trunk/*

# Remove deleted files
svn status trunk/ | grep '^!' | awk '{print $2}' | xargs svn delete

# Commit changes
svn ci -m "Deploying version 1.1.0" --username your-username --password your-password

# Create SVN tag
svn cp trunk tags/1.1.0
svn ci -m "Tagging version 1.1.0" --username your-username --password your-password

# Update assets (screenshots, banner, icon)
svn cp local-assets/* assets/
svn ci -m "Update plugin assets" --username your-username --password your-password
```

---

## Version Numbering

Follow [Semantic Versioning](https://semver.org/):

- **Major** (1.0.0): Breaking changes
- **Minor** (1.1.0): New features, backward compatible
- **Patch** (1.1.1): Bug fixes, backward compatible

---

## Pre-Release Checklist

Before creating a release, ensure:

### ✅ Code Quality
- [ ] All tests pass (`npm run test:all`)
- [ ] PHPCS passes (`composer phpcs`)
- [ ] PHPStan passes (`composer phpstan`)
- [ ] No console errors in browser

### ✅ Version Updates
- [ ] Version in `post-formats-power-up.php` header
- [ ] `Stable tag` in `readme.txt`
- [ ] Version in `package.json`
- [ ] Changelog updated in `readme.txt`

### ✅ Assets
- [ ] JavaScript built (`npm run build`)
- [ ] Translation files generated (`composer i18n`)
- [ ] Screenshots updated (if changed)
- [ ] Plugin banner/icon updated (if changed)

### ✅ Documentation
- [ ] `readme.txt` tested for WordPress.org formatting
- [ ] Installation instructions accurate
- [ ] FAQ updated
- [ ] Known issues documented

### ✅ Testing
- [ ] Test on WordPress 6.4+
- [ ] Test with PHP 7.4, 8.0, 8.1, 8.2
- [ ] Test with block themes
- [ ] Test all post formats
- [ ] Accessibility audit passed
- [ ] Visual regression tests passed

---

## Rollback Procedure

If a release has issues:

### 1. Immediate Rollback

```bash
# Update readme.txt stable tag to previous version
# In your local repo:
git checkout main
# Edit readme.txt, change Stable tag to previous version (e.g., 1.0.0)
git commit -m "Rollback to version 1.0.0"
git push

# Deploy readme update
svn co https://plugins.svn.wordpress.org/post-formats-power-up/trunk
cd trunk
# Update readme.txt
svn ci -m "Rollback to version 1.0.0"
```

### 2. Fix and Re-Release

1. Fix the issue locally
2. Run all tests
3. Bump to patch version (e.g., 1.1.1)
4. Create new release

---

## Deployment Troubleshooting

### GitHub Action Fails

**Check Actions tab for error logs:**

- **Build fails**: Check `npm run build` locally
- **SVN auth fails**: Verify `SVN_USERNAME` and `SVN_PASSWORD` secrets
- **Tests fail**: Fix failing tests before release

### WordPress.org SVN Issues

**Common problems:**

- **403 Forbidden**: Wrong SVN credentials
- **409 Conflict**: SVN out of sync, run `svn update`
- **File conflicts**: Resolve with `svn resolve --accept theirs-full FILE`

### Plugin Not Updating

**If users don't see update:**

1. Check `readme.txt` stable tag matches released version
2. Wait 15-30 minutes for WordPress.org cache
3. Force refresh: https://wordpress.org/plugins/post-formats-power-up/?clear_cache=1

---

## Post-Release Checklist

After successful deployment:

### ✅ Verify Deployment
- [ ] Check WordPress.org plugin page: https://wordpress.org/plugins/post-formats-power-up/
- [ ] Verify version number displays correctly
- [ ] Test "Download" button works
- [ ] Check changelog displays properly

### ✅ Test Live Update
- [ ] Install previous version on test site
- [ ] Trigger update check
- [ ] Verify update appears
- [ ] Test update process completes successfully

### ✅ Monitor
- [ ] Watch WordPress.org support forums for issues
- [ ] Monitor GitHub issues
- [ ] Check error logs for spikes

### ✅ Announce
- [ ] Update plugin documentation site (if applicable)
- [ ] Announce on social media (if applicable)
- [ ] Email changelog to major contributors/users

---

## WordPress.org Assets

### Plugin Header Image (Banner)
- **Size**: 1544×500px or 772×250px
- **Location**: `/.wordpress-org/banner-1544x500.png` (high-res) or `banner-772x250.png` (low-res)
- **Deploy**: Add to SVN `assets/` directory

### Plugin Icon
- **Size**: 256×256px or 128×128px
- **Location**: `/.wordpress-org/icon-256x256.png` or `icon-128x128.png`
- **Deploy**: Add to SVN `assets/` directory

### Screenshots
- **Size**: 1280×720px (recommended)
- **Location**: `/.wordpress-org/screenshot-1.png`, `screenshot-2.png`, etc.
- **Deploy**: Add to SVN `assets/` directory
- **Description**: Add to `readme.txt` "Screenshots" section

---

## Related Documentation

- [WordPress Plugin Handbook - Releasing](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/)
- [10up GitHub Action](https://github.com/10up/action-wordpress-plugin-deploy)
- [Semantic Versioning](https://semver.org/)

---

**Last Updated**: 2025-11-29
**Workflow Version**: 1.0.0
