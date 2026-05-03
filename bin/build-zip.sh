#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="wp-content-abilities"
INSTALL_DIR="prod"  # matches wp-content/plugins/prod/ on the target site
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION=$(grep -m1 'Version:' "$ROOT_DIR/$PLUGIN_SLUG.php" | sed 's/.*Version:[[:space:]]*//')
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
DIST_DIR="$ROOT_DIR/dist"
STAGE_DIR="$DIST_DIR/$INSTALL_DIR"

rm -rf "$STAGE_DIR"
mkdir -p "$STAGE_DIR"

cp "$ROOT_DIR/$PLUGIN_SLUG.php" "$STAGE_DIR/"
cp "$ROOT_DIR/README.md" "$STAGE_DIR/"
cp "$ROOT_DIR/CHANGELOG.md" "$STAGE_DIR/"
cp "$ROOT_DIR/LICENSE" "$STAGE_DIR/"

cd "$DIST_DIR"
rm -f "$ZIP_NAME"
zip -r "$ZIP_NAME" "$INSTALL_DIR/" --exclude "*.DS_Store"
rm -rf "$STAGE_DIR"

echo "Built: dist/$ZIP_NAME"
