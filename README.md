# WP Content Abilities

A WordPress plugin that exposes content management capabilities via the WordPress 6.9 Abilities API, enabling AI assistants to create, read, update, and delete WordPress content through the MCP protocol.

## Fork Notice

This is a fork of [aplaceforallmystuff/wp-content-abilities](https://github.com/aplaceforallmystuff/wp-content-abilities) by [Jim Christian](https://jimchristian.net), maintained here by [Daniel Kossmann](https://www.danielkossmann.com/). The original project appears to be discontinued because the author [migrated from WordPress to Astro](https://jimchristian.net/blog/2026/01/27/rebuilding-my-site/). Thanks Jim for the original work! Licensed under GPL v2 or later, same as the original.

## Important Notices

### Experimental Technology

This plugin uses **WordPress 6.9's Abilities API**, which is a new and evolving feature. The API may change in future WordPress releases, potentially requiring updates to this plugin.

### Technical Users Only

This plugin is designed for developers and technical users who are comfortable with:
- Command-line tools and API interactions
- WordPress plugin management
- Application Passwords and authentication
- The Model Context Protocol (MCP)

### Security Considerations

- **Application Passwords** grant full API access with your user's permissions
- Only create Application Passwords for trusted automation tools
- Use accounts with minimal required permissions (Editor role for content management)
- Regularly audit and revoke unused Application Passwords
- HTTPS is strongly recommended for all API communications

### No Warranty

This plugin is provided "as is" without warranty of any kind. Use at your own risk. Always backup your WordPress site before installing new plugins.

## Requirements

### Core Requirements

| Requirement | Version | Purpose |
|-------------|---------|---------|
| WordPress | 6.9+ | Provides the Abilities API framework |
| PHP | 8.0+ | Required by WordPress 6.9 |

### Required Plugins

| Plugin | Source | Purpose |
|--------|--------|---------|
| **MCP Adapter** | [WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter) | Provides HTTP transport for MCP protocol at `/wp-json/mcp/mcp-adapter-default-server` |

### Optional Plugins

| Plugin | Source | Purpose |
|--------|--------|---------|
| AI Experiments | [wordpress/ai](https://wordpress.org/plugins/ai/) | Provides additional AI-focused abilities (get-post-details, get-post-terms) |

### Authentication

The plugin uses WordPress's built-in authentication. For programmatic access:
- Create an **Application Password** in WordPress (Users → Your Profile → Application Passwords)
- Use Basic Auth with your username and application password

## Installation

1. Upload `wp-content-abilities.php` to `/wp-content/plugins/wp-content-abilities/`
2. Activate the plugin through the 'Plugins' menu
3. Ensure MCP Adapter plugin is also installed and activated
4. Create an Application Password for API access

## Available Abilities

### Posts (5 abilities)

| Ability | Description |
|---------|-------------|
| `content/list-posts` | List posts with filters (status, category, tag, author, language, search) |
| `content/get-post` | Get a single post by ID with full content, metadata, categories, and tags |
| `content/create-post` | Create a new post with all fields |
| `content/update-post` | Update an existing post (only provided fields are changed) |
| `content/delete-post` | Delete a post (move to trash or force delete) |

### Pages (5 abilities)

| Ability | Description |
|---------|-------------|
| `content/list-pages` | List pages with optional filters |
| `content/get-page` | Get a single page by ID with full content |
| `content/create-page` | Create a new page (supports title, content, parent, menu order, template) |
| `content/update-page` | Update an existing page (only provided fields are changed) |
| `content/delete-page` | Delete a page (move to trash or force delete) |

### Revisions (2 abilities)

| Ability | Description |
|---------|-------------|
| `content/list-revisions` | List all revisions of a post or page in reverse-chronological order |
| `content/restore-revision` | Revert a post or page to a specific revision |

### Categories (4 abilities)

| Ability | Description |
|---------|-------------|
| `content/list-categories` | List all categories with post counts |
| `content/create-category` | Create a new category (supports parent and Polylang language) |
| `content/update-category` | Update an existing category |
| `content/delete-category` | Permanently delete a category |

### Tags (4 abilities)

| Ability | Description |
|---------|-------------|
| `content/list-tags` | List all tags with post counts |
| `content/create-tag` | Create a new tag |
| `content/update-tag` | Update an existing tag |
| `content/delete-tag` | Permanently delete a tag |

### Media (2 abilities)

| Ability | Description |
|---------|-------------|
| `content/upload-media` | Upload an image via base64 or URL |
| `content/list-media` | List media library items with optional filters |

### Bulk Operations (4 abilities)

| Ability | Description |
|---------|-------------|
| `content/bulk-update-posts` | Apply the same field changes to a list of posts |
| `content/bulk-update-pages` | Apply the same field changes to a list of pages |
| `content/bulk-delete-posts` | Delete a list of posts (honours trash unless force=true) |
| `content/bulk-delete-pages` | Delete a list of pages (honours trash unless force=true) |

### Custom Post Types (6 abilities)

| Ability | Description |
|---------|-------------|
| `content/list-post-types` | List post types available to the content abilities |
| `content/list-content` | List entries of a custom post type |
| `content/get-content` | Get a single custom post type entry by ID |
| `content/create-content` | Create a new entry of a custom post type |
| `content/update-content` | Update an existing custom post type entry |
| `content/delete-content` | Delete a custom post type entry |

### Languages / Polylang (3 abilities)

> These abilities require the [Polylang](https://wordpress.org/plugins/polylang/) plugin.

| Ability | Description |
|---------|-------------|
| `content/list-languages` | List all languages configured in Polylang |
| `content/get-translation` | Get the translated counterpart of a post, page, or media item |
| `content/list-untranslated` | List posts or pages missing a translation in a target language |

## Create Post Fields

The `content/create-post` ability supports all WordPress post fields:

```json
{
  "title": "Post Title",
  "content": "<p>HTML content</p>",
  "excerpt": "Optional excerpt",
  "status": "draft|publish|pending|private|future",
  "slug": "custom-url-slug",
  "categories": ["category-slug-1", "category-slug-2"],
  "tags": ["Tag Name 1", "Tag Name 2"],
  "date": "2025-12-25T09:00:00",
  "featured_image_id": 123,
  "format": "standard|aside|gallery|link|image|quote|status|video|audio|chat",
  "sticky": false,
  "comment_status": "open|closed",
  "ping_status": "open|closed",
  "author": "username"
}
```

## Usage via MCP

### Initialize Session

```bash
curl -s -u "username:app-password" -X POST \
  -H "Content-Type: application/json" \
  -D headers.txt \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"claude","version":"1.0.0"}}}' \
  "https://your-site.com/wp-json/mcp/mcp-adapter-default-server"
```

### List Available Tools

```bash
SESSION_ID=$(grep -i "mcp-session-id" headers.txt | awk '{print $2}' | tr -d '\r')

curl -s -u "username:app-password" -X POST \
  -H "Content-Type: application/json" \
  -H "Mcp-Session-Id: $SESSION_ID" \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' \
  "https://your-site.com/wp-json/mcp/mcp-adapter-default-server"
```

### Execute an Ability

```bash
curl -s -u "username:app-password" -X POST \
  -H "Content-Type: application/json" \
  -H "Mcp-Session-Id: $SESSION_ID" \
  -d '{"jsonrpc":"2.0","id":3,"method":"tools/call","params":{"name":"mcp-adapter-execute-ability","arguments":{"ability_name":"content/create-post","parameters":{"title":"My New Post","content":"<p>Post content here</p>","status":"draft"}}}}' \
  "https://your-site.com/wp-json/mcp/mcp-adapter-default-server"
```

## Troubleshooting

### Abilities not appearing in MCP

1. Ensure MCP Adapter plugin is active
2. Check that abilities have `mcp.public: true` in their meta
3. Verify you're using the correct endpoint: `/wp-json/mcp/mcp-adapter-default-server`

### Authentication errors

1. Verify Application Password is correctly formatted (spaces removed)
2. Use format: `username:xxxx xxxx xxxx xxxx xxxx xxxx`
3. Ensure user has appropriate capabilities (editor or admin role)

### Media upload fails

1. Check upload directory permissions
2. Ensure file size is within WordPress limits
3. Verify MIME type is allowed

## Development

This plugin uses WordPress's Abilities API hooks:

- `wp_abilities_api_categories_init` - Register ability categories
- `wp_abilities_api_init` - Register individual abilities

Each ability requires:
- `label` and `description` for documentation
- `category` for grouping
- `input_schema` defining expected parameters
- `callback` function to execute the ability
- `meta.mcp.public = true` for MCP exposure

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Contributing

Issues and pull requests welcome at [GitHub](https://github.com/kossmann/wp-content-abilities).

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

Daniel Kossmann - [danielkossmann.com](https://www.danielkossmann.com/)

## Disclaimer

This plugin is not affiliated with or endorsed by Automattic, WordPress.org, or the WordPress Foundation. "WordPress" is a registered trademark of the WordPress Foundation.
