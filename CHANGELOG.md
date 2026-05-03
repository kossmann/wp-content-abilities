# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

> **Fork notice:** This project is a fork of [aplaceforallmystuff/wp-content-abilities](https://github.com/aplaceforallmystuff/wp-content-abilities)
> by Jim Christian, maintained here by Daniel Kossmann. The original project appears to be discontinued because the author [migrated from WordPress to Astro](https://jimchristian.net/blog/2026/01/27/rebuilding-my-site/).
> All additions from v1.2.0 onward are authored by Daniel Kossmann.

## [1.2.0] - 2026-05-03

### Added
- `content/list-posts` and `content/get-post` sparse fieldset support
- `content/list-pages` and `content/get-page` sparse fieldset support
- Allowlisted post-meta read and write to content abilities
- Bulk update and delete abilities for posts and pages
- Taxonomy CRUD abilities for categories and tags (`create-category`, `update-category`, `delete-category`, `create-tag`, `update-tag`, `delete-tag`)
- `content/list-revisions` and `content/restore-revision` abilities
- Polylang abilities: `content/list-languages`, `content/get-translation`, `content/list-untranslated`
- Generic custom post type abilities (`list-post-types`, `list-content`, `get-content`, `create-content`, `update-content`, `delete-content`)
- `author` field to `update-post` ability
- Plugin zip build script

### Fixed
- Tightened capability checks, upload validation, and schema constraints across all abilities

### Changed
- Author updated to Daniel Kossmann (fork continuation)
- Plugin URI updated to forked repository

### Removed
- `content/get-site-info` ability

## [1.1.0] - 2025-12-03

### Added
- `content/upload-media` ability - Upload images via base64 or URL
- `content/list-media` ability - List media library items with filters
- Enhanced `create-post` fields: format, sticky, comment_status, ping_status, author
- Full documentation in README.md
- LICENSE file (GPL v2)
- This CHANGELOG

### Changed
- Plugin name standardized to "WP Content Abilities"
- License corrected to GPL-2.0-or-later (WordPress requirement)

## [1.0.0] - 2025-12-03

### Added
- Initial release with 14 content management abilities
- **Posts**: list-posts, get-post, create-post, update-post, delete-post, get-post-revisions
- **Pages**: list-pages, get-page, create-page, update-page, delete-page
- **Taxonomies**: list-categories, list-tags
- **Site**: get-site-info
- MCP Adapter integration via `mcp.public: true` meta
- WordPress 6.9 Abilities API support

### Technical Notes
- Uses `wp_abilities_api_categories_init` hook for category registration
- All abilities exposed via MCP Adapter at `/wp-json/mcp/mcp-adapter-default-server`
- Requires WordPress 6.9+ for Abilities API support
