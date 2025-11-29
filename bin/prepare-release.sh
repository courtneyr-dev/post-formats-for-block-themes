#!/bin/bash

# Post Formats Power-Up - Release Preparation Script
# Automates version bumping and pre-release checks

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check if version argument provided
if [ -z "$1" ]; then
    print_error "Version number required"
    echo "Usage: ./bin/prepare-release.sh <version>"
    echo "Example: ./bin/prepare-release.sh 1.1.0"
    exit 1
fi

NEW_VERSION=$1
PLUGIN_FILE="post-formats-power-up.php"
README_FILE="readme.txt"
PACKAGE_FILE="package.json"

print_header "Post Formats Power-Up Release Preparation"
print_info "Preparing release version: ${NEW_VERSION}"
echo ""

# Validate version format
if ! [[ $NEW_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    print_error "Invalid version format. Use semantic versioning (e.g., 1.1.0)"
    exit 1
fi

# Check if we're on main branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$CURRENT_BRANCH" != "main" ] && [ "$CURRENT_BRANCH" != "master" ]; then
    print_warning "You're on branch '$CURRENT_BRANCH', not 'main'"
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check for uncommitted changes
if [[ -n $(git status --porcelain) ]]; then
    print_warning "You have uncommitted changes"
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

print_header "Step 1: Running Tests"

# Run tests
print_info "Running PHPCS..."
if composer phpcs 2>&1 | grep -q "FOUND 0 ERRORS"; then
    print_success "PHPCS passed"
else
    print_warning "PHPCS found violations"
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

print_info "Running PHPStan..."
if composer phpstan > /dev/null 2>&1; then
    print_success "PHPStan passed"
else
    print_warning "PHPStan found issues"
    read -p "Continue anyway? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

print_header "Step 2: Updating Version Numbers"

# Update plugin file
print_info "Updating ${PLUGIN_FILE}..."
sed -i.bak "s/\* Version: .*/\* Version: ${NEW_VERSION}/" "${PLUGIN_FILE}"
rm "${PLUGIN_FILE}.bak"
print_success "Updated plugin file version"

# Update readme.txt stable tag
print_info "Updating ${README_FILE}..."
sed -i.bak "s/^Stable tag: .*/Stable tag: ${NEW_VERSION}/" "${README_FILE}"
rm "${README_FILE}.bak"
print_success "Updated readme.txt stable tag"

# Update package.json
print_info "Updating ${PACKAGE_FILE}..."
sed -i.bak "s/\"version\": \".*\"/\"version\": \"${NEW_VERSION}\"/" "${PACKAGE_FILE}"
rm "${PACKAGE_FILE}.bak"
print_success "Updated package.json version"

print_header "Step 3: Building Assets"

# Install dependencies
print_info "Installing npm dependencies..."
npm install > /dev/null 2>&1
print_success "npm dependencies installed"

# Build JavaScript
print_info "Building JavaScript assets..."
npm run build > /dev/null 2>&1
print_success "JavaScript assets built"

# Generate translations
print_info "Generating translation files..."
composer i18n > /dev/null 2>&1 || print_warning "Translation generation skipped (wp-cli not available)"

print_header "Step 4: Updating Changelog"

print_info "Don't forget to update the changelog in ${README_FILE}!"
print_info "Add release notes for version ${NEW_VERSION}"
echo ""
read -p "Press Enter when you've updated the changelog..."

print_header "Step 5: Git Commit"

# Show changes
echo ""
print_info "Files changed:"
git diff --stat
echo ""

read -p "Commit these changes? (Y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
    git add "${PLUGIN_FILE}" "${README_FILE}" "${PACKAGE_FILE}" build/
    git commit -m "Bump version to ${NEW_VERSION}"
    print_success "Changes committed"

    read -p "Push to origin? (Y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
        git push origin "${CURRENT_BRANCH}"
        print_success "Changes pushed to origin"
    fi
else
    print_warning "Skipped commit"
fi

print_header "Release Preparation Complete!"

echo ""
print_info "Next steps:"
echo "  1. Go to GitHub → Releases → 'Draft a new release'"
echo "  2. Create tag: v${NEW_VERSION}"
echo "  3. Set title: Version ${NEW_VERSION}"
echo "  4. Add release notes from changelog"
echo "  5. Publish release"
echo ""
print_info "The GitHub Action will automatically deploy to WordPress.org"
echo ""
