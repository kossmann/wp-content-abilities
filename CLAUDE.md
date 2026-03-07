# CLAUDE.md - wp-content-abilities

WordPress plugin that exposes content management via WordPress 6.9+ Abilities API.

## Overview
This is a WordPress plugin (PHP) that enables AI assistants to manage WordPress content through the MCP protocol. Works with mcp-wp-abilities MCP server.

## Structure
```
wp-content-abilities/
├── wp-content-abilities.php   # Main plugin file
├── includes/
│   └── class-abilities.php    # Abilities API registration
└── readme.txt                 # WordPress plugin readme
```

## Development
- Install in WordPress wp-content/plugins/
- Activate via WordPress admin
- Requires WordPress 6.9+

## Constraints
```yaml
rules:
  - id: wordpress-coding-standards
    description: Follow WordPress PHP coding standards
  - id: abilities-api
    description: Use WP 6.9 Abilities API, not custom REST endpoints
```
