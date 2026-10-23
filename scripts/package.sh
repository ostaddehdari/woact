#!/usr/bin/env bash
set -Eeuo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
build_dir="$project_root/build"
version="0.3.0-rc1"

rm -rf "$build_dir"
mkdir -p "$build_dir/package/wp-content/themes" "$build_dir/package/wp-content/plugins"
cp -R "$project_root/wordpress/themes/woact" "$build_dir/package/wp-content/themes/woact"
cp -R "$project_root/wordpress/plugins/woact-core" "$build_dir/package/wp-content/plugins/woact-core"

(
  cd "$build_dir/package/wp-content/themes"
  zip -qr "$build_dir/woact-theme-$version.zip" woact
)
(
  cd "$build_dir/package/wp-content/plugins"
  zip -qr "$build_dir/woact-core-$version.zip" woact-core
)
(
  cd "$build_dir/package"
  zip -qr "$build_dir/woact-stage3-$version.zip" wp-content
)

printf 'Packages created in %s\n' "$build_dir"

