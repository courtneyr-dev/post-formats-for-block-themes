#!/bin/bash
#
# Script to rename Post Formats Power-Up to Post Formats for Block Themes
# This performs systematic search and replace across all plugin files
#

set -e

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PLUGIN_DIR"

echo "🔄 Renaming plugin from 'Post Formats Power-Up' to 'Post Formats for Block Themes'"
echo "Working directory: $PLUGIN_DIR"
echo ""

# Function to perform safe sed replacement (works on both Linux and macOS)
safe_sed() {
    local pattern="$1"
    local replacement="$2"
    local file="$3"

    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' "$pattern" "$file"
    else
        # Linux
        sed -i "$pattern" "$file"
    fi
}

# Count total files to process
total_files=0

# Find all PHP files (excluding vendor, node_modules, build)
php_files=$(find . -type f -name "*.php" ! -path "*/vendor/*" ! -path "*/node_modules/*" ! -path "*/build/*" ! -path "*/.git/*" | wc -l | tr -d ' ')
# Find all JS files
js_files=$(find ./src -type f \( -name "*.js" -o -name "*.jsx" \) 2>/dev/null | wc -l | tr -d ' ')
# Find config and doc files
config_files=$(find . -maxdepth 1 -type f \( -name "*.json" -o -name "*.md" -o -name "*.txt" \) ! -name "package-lock.json" | wc -l | tr -d ' ')

total_files=$((php_files + js_files + config_files))

echo "📊 Files to process:"
echo "   - PHP files: $php_files"
echo "   - JavaScript files: $js_files"
echo "   - Config/docs: $config_files"
echo "   - Total: $total_files"
echo ""

processed=0

# 1. Update PHP constants (PFPU_ to PFBT_)
echo "1️⃣  Updating PHP constants (PFPU_ → PFBT_)..."
for file in $(find . -type f -name "*.php" ! -path "*/vendor/*" ! -path "*/node_modules/*" ! -path "*/build/*" ! -path "*/.git/*"); do
    if grep -q "PFPU_" "$file" 2>/dev/null; then
        safe_sed 's/PFPU_/PFBT_/g' "$file"
        ((processed++))
        echo "   ✓ $file"
    fi
done

# 2. Update PHP function prefixes (pfpu_ to pfbt_)
echo ""
echo "2️⃣  Updating PHP function prefixes (pfpu_ → pfbt_)..."
for file in $(find . -type f -name "*.php" ! -path "*/vendor/*" ! -path "*/node_modules/*" ! -path "*/build/*" ! -path "*/.git/*"); do
    if grep -q "pfpu_" "$file" 2>/dev/null; then
        safe_sed 's/pfpu_/pfbt_/g' "$file"
        echo "   ✓ $file"
    fi
done

# 3. Update JavaScript object names (pfpuData to pfbtData)
echo ""
echo "3️⃣  Updating JavaScript objects (pfpuData → pfbtData)..."
for file in $(find ./src -type f \( -name "*.js" -o -name "*.jsx" \) 2>/dev/null); do
    if grep -q "pfpuData" "$file" 2>/dev/null; then
        safe_sed 's/pfpuData/pfbtData/g' "$file"
        echo "   ✓ $file"
    fi
done

# Also update in main PHP file where it's localized
safe_sed 's/pfpuData/pfbtData/g' "post-formats-for-block-themes.php"

# 4. Update text domain (post-formats-power-up to post-formats-for-block-themes)
echo ""
echo "4️⃣  Updating text domain (post-formats-power-up → post-formats-for-block-themes)..."
for file in $(find . -type f \( -name "*.php" -o -name "*.js" -o -name "*.jsx" -o -name "*.json" \) ! -path "*/vendor/*" ! -path "*/node_modules/*" ! -path "*/build/*" ! -path "*/.git/*" ! -name "package-lock.json"); do
    if grep -q "post-formats-power-up" "$file" 2>/dev/null; then
        safe_sed 's/post-formats-power-up/post-formats-for-block-themes/g' "$file"
        echo "   ✓ $file"
    fi
done

# 5. Update plugin display name
echo ""
echo "5️⃣  Updating plugin name (Post Formats Power-Up → Post Formats for Block Themes)..."
for file in $(find . -type f \( -name "*.php" -o -name "*.md" -o -name "*.txt" \) ! -path "*/vendor/*" ! -path "*/node_modules/*" ! -path "*/build/*" ! -path "*/.git/*"); do
    if grep -q "Post Formats Power-Up" "$file" 2>/dev/null; then
        safe_sed 's/Post Formats Power-Up/Post Formats for Block Themes/g' "$file"
        echo "   ✓ $file"
    fi
done

# 6. Update @package annotation
echo ""
echo "6️⃣  Updating @package annotations..."
for file in $(find . -type f -name "*.php" ! -path "*/vendor/*" ! -path "*/node_modules/*" ! -path "*/build/*" ! -path "*/.git/*"); do
    if grep -q "@package PostFormatsPowerUp" "$file" 2>/dev/null; then
        safe_sed 's/@package PostFormatsPowerUp/@package PostFormatsBlockThemes/g' "$file"
        echo "   ✓ $file"
    fi
done

# 7. Update package.json name
echo ""
echo "7️⃣  Updating package.json..."
if [ -f "package.json" ]; then
    safe_sed 's/"name": "post-formats-power-up"/"name": "post-formats-for-block-themes"/g' "package.json"
    echo "   ✓ package.json"
fi

# 8. Update composer.json name (if exists)
if [ -f "composer.json" ]; then
    safe_sed 's/"name": ".*post-formats-power-up.*"/"name": "courtneyr-dev\/post-formats-for-block-themes"/g' "composer.json"
    echo "   ✓ composer.json"
fi

# 9. Update GitHub workflow files
echo ""
echo "8️⃣  Updating GitHub workflows..."
for file in .github/workflows/*.yml; do
    if [ -f "$file" ]; then
        safe_sed 's/post-formats-power-up/post-formats-for-block-themes/g' "$file"
        safe_sed 's/Post Formats Power-Up/Post Formats for Block Themes/g' "$file"
        echo "   ✓ $file"
    fi
done

# 10. Update plugin URI in main file
echo ""
echo "9️⃣  Updating plugin URIs..."
safe_sed 's|https://wordpress.org/plugins/post-formats-power-up/|https://wordpress.org/plugins/post-formats-for-block-themes/|g' "post-formats-for-block-themes.php"
safe_sed 's|https://github.com/.*/post-formats-power-up|https://github.com/courtneyr-dev/post-formats-for-block-themes|g' "readme.txt"
safe_sed 's|https://github.com/.*/post-formats-power-up|https://github.com/courtneyr-dev/post-formats-for-block-themes|g' "README.md"

echo ""
echo "✅ Renaming complete!"
echo ""
echo "📋 Summary:"
echo "   - PHP constants: PFPU_ → PFBT_"
echo "   - PHP functions: pfpu_ → pfbt_"
echo "   - JS objects: pfpuData → pfbtData"
echo "   - Slug: post-formats-power-up → post-formats-for-block-themes"
echo "   - Name: Post Formats Power-Up → Post Formats for Block Themes"
echo ""
echo "🔍 Next steps:"
echo "   1. Review changes: git diff"
echo "   2. Rebuild assets: npm run build"
echo "   3. Test plugin activation"
echo "   4. Run tests: npm run test:all"
echo ""
