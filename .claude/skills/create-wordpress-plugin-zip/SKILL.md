---
name: create-wordpress-plugin-zip
description: Creates or updates a bin/build-zip.sh script that packages a WordPress plugin as an installable .zip file for manual upload via WP Admin. Use this skill whenever the user wants to build, package, distribute, or manually update a WordPress plugin — including when they mention uploading a zip to wp-admin, replacing an existing plugin, or "zipping up" the plugin. Also use when the user gets errors like "installing as a new plugin instead of replacing" — this is almost always a zip folder structure problem this skill knows how to fix.
---

## What this skill does

Generates (or updates) `bin/build-zip.sh` — a shell script that packages the plugin into a correctly structured `.zip` for WP Admin upload. The zip must contain a single top-level folder whose name matches the plugin's **installed directory name** on the target site, otherwise WordPress treats the upload as a new plugin rather than an update.

## Critical: folder name inside the zip

WordPress identifies a plugin by `<folder>/<file>.php`. When you upload a zip, WP extracts the top-level folder and drops it into `wp-content/plugins/`. If that folder name doesn't match the existing install, WordPress creates a second, duplicate plugin entry.

**How to find the installed folder name**: look at the WP Admin plugin action URL:
```
?plugin=prod%2Fwp-content-abilities.php
         ^^^^
         This is the folder name — "prod" here, not "wp-content-abilities"
```

Always ask the user for this if it's not already known and can't be inferred from context. The folder name is often (but not always) the plugin slug — don't assume.

## Steps

### 1. Identify inputs

Collect these before writing the script:
- **Plugin main file** — the `.php` file with the plugin header (e.g. `wp-content-abilities.php`)
- **Installed folder name** — from WP Admin URL or user confirmation (see above)
- **Files to include** — at minimum the main PHP file; also include `README.md`, `CHANGELOG.md`, `LICENSE`, and any `includes/` or `assets/` directories that ship with the plugin. Exclude dev-only files (`.claude/`, `bin/`, `dist/`, `.git/`, `*.zip`, `node_modules/`).

### 2. Create or update `bin/build-zip.sh`

```bash
#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="<plugin-slug>"         # e.g. wp-content-abilities
INSTALL_DIR="<installed-folder>"    # folder name on target WP (e.g. "prod" or "wp-content-abilities")
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION=$(grep -m1 'Version:' "$ROOT_DIR/$PLUGIN_SLUG.php" | sed 's/.*Version:[[:space:]]*//')
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
DIST_DIR="$ROOT_DIR/dist"
STAGE_DIR="$DIST_DIR/$INSTALL_DIR"

rm -rf "$STAGE_DIR"
mkdir -p "$STAGE_DIR"

# Copy plugin files into the staging folder
cp "$ROOT_DIR/$PLUGIN_SLUG.php" "$STAGE_DIR/"
cp "$ROOT_DIR/README.md"        "$STAGE_DIR/"
cp "$ROOT_DIR/CHANGELOG.md"     "$STAGE_DIR/"
cp "$ROOT_DIR/LICENSE"          "$STAGE_DIR/"
# cp -r "$ROOT_DIR/includes"    "$STAGE_DIR/"  # uncomment if needed

cd "$DIST_DIR"
rm -f "$ZIP_NAME"
zip -r "$ZIP_NAME" "$INSTALL_DIR/" --exclude "*.DS_Store"
rm -rf "$STAGE_DIR"

echo "Built: dist/$ZIP_NAME"
```

Make it executable: `chmod +x bin/build-zip.sh`

### 3. Update .gitignore

Make sure `dist/*.zip` (or `*.zip`) is in `.gitignore` so built artifacts aren't committed.

### 4. Run and verify

```bash
./bin/build-zip.sh
unzip -l dist/<plugin-name>-*.zip
```

The listing must show `<install-dir>/<plugin-file>.php` — the top-level entry must match the installed folder name exactly.

## Common failure modes

| Symptom | Cause | Fix |
|---|---|---|
| WP installs as a new plugin instead of replacing | Wrong folder name in zip | Set `INSTALL_DIR` to the folder name from the WP Admin URL |
| "Plugin could not be activated — fatal error" | Files at zip root (no wrapping folder) | Ensure staging dir wraps everything inside `$INSTALL_DIR/` |
| Version in zip name is wrong | `grep` not matching the header format | Check `Plugin Name:` vs `Version:` spelling in the PHP header |
