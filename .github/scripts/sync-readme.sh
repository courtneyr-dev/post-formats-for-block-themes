#!/usr/bin/env bash
# Copies readme.txt to WordPress.org SVN trunk and the current stable tag.
# Only updates a release that already exists; never creates or switches the
# stable tag. Run by .github/workflows/readme-sync.yml.
#
# Adapted from https://github.com/ProgressPlanner/pdf-xml-sitemap
# (.github/scripts/sync-readme.sh, GPL-3.0, Joost de Valk). CI only: this file
# is not part of the plugin package.
set -euo pipefail

slug='post-formats-for-block-themes'

read_stable_tag() {
  sed -nE 's/^[Ss][Tt][Aa][Bb][Ll][Ee] [Tt][Aa][Gg]:[[:space:]]*([^[:space:]]+)[[:space:]]*$/\1/p' "$1"
}

stable_tag=$(read_stable_tag readme.txt)
if [[ ! "$stable_tag" =~ ^[0-9]+(\.[0-9]+)*$ ]]; then
  echo 'Expected one numeric Stable tag in readme.txt.' >&2
  exit 1
fi

svn_url="https://plugins.svn.wordpress.org/$slug"
svn_dir=$(mktemp -d)
trap 'rm -rf "$svn_dir"' EXIT

svn checkout --non-interactive --depth empty "$svn_url" "$svn_dir"
svn update --non-interactive --depth empty "$svn_dir/trunk" "$svn_dir/tags"
svn update --non-interactive --depth empty "$svn_dir/tags/$stable_tag"
svn update --non-interactive "$svn_dir/trunk/readme.txt" "$svn_dir/tags/$stable_tag/readme.txt"

for target in "$svn_dir/trunk/readme.txt" "$svn_dir/tags/$stable_tag/readme.txt"; do
  if [[ ! -f "$target" ]] || [[ "$(read_stable_tag "$target")" != "$stable_tag" ]]; then
    echo "The Stable tag in readme.txt ($stable_tag) must match the published trunk and tag readmes. Release the new version instead." >&2
    exit 1
  fi
done

cp readme.txt "$svn_dir/trunk/readme.txt"
cp readme.txt "$svn_dir/tags/$stable_tag/readme.txt"

if [[ -z "$(svn status "$svn_dir")" ]]; then
  echo 'Both published readmes are already up to date.'
  exit 0
fi

svn diff "$svn_dir"
if [[ "${DRY_RUN:-false}" == 'true' ]]; then
  echo 'Dry run: no files were committed.'
  exit 0
fi

: "${SVN_USERNAME:?SVN_USERNAME is required}"
: "${SVN_PASSWORD:?SVN_PASSWORD is required}"
svn_auth=(--username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive --no-auth-cache)
svn commit "$svn_dir/trunk/readme.txt" "$svn_dir/tags/$stable_tag/readme.txt" \
  --message "Update $slug readme.txt for $stable_tag" "${svn_auth[@]}"
