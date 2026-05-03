<?php
/**
 * Plugin Name: WP Content Abilities
 * Plugin URI: https://github.com/aplaceforallmystuff/wp-content-abilities
 * Description: Exposes content management capabilities via the WordPress 6.9 Abilities API for AI assistants and MCP clients. Create, update, delete, and list posts, pages, and media.
 * Version: 1.1.0
 * Author: Jim Christian
 * Author URI: https://jimchristian.net
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Text Domain: wp-content-abilities
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the content category first (must use categories_init hook)
 */
add_action( 'wp_abilities_api_categories_init', 'wp_content_abilities_register_category' );

function wp_content_abilities_register_category() {
    if ( function_exists( 'wp_register_ability_category' ) ) {
        wp_register_ability_category( 'content', array(
            'label'       => __( 'Content Management', 'wp-content-abilities' ),
            'description' => __( 'Abilities for managing posts, pages, and other content.', 'wp-content-abilities' ),
        ) );
    }
}

/**
 * Register all content management abilities
 */
add_action( 'wp_abilities_api_init', 'wp_content_abilities_register', 10 );

function wp_content_abilities_register() {

    // =========================================================================
    // POST ABILITIES
    // =========================================================================

    /**
     * List Posts
     */
    wp_register_ability( 'content/list-posts', array(
        'label'       => __( 'List Posts', 'wp-content-abilities' ),
        'description' => __( 'Retrieves a list of posts with optional filtering by status, category, tag, author, language, or search term.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'any' ),
                    'default'     => 'any',
                    'description' => 'Filter by post status.',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'description' => 'Number of posts to return.',
                ),
                'page' => array(
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                    'description' => 'Page number for pagination.',
                ),
                'search' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Search posts by keyword.',
                ),
                'category' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Filter by category slug.',
                ),
                'tag' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Filter by tag slug.',
                ),
                'author' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Filter by author ID.',
                ),
                'orderby' => array(
                    'type'        => 'string',
                    'enum'        => array( 'date', 'title', 'modified', 'ID' ),
                    'default'     => 'date',
                    'description' => 'Order posts by field.',
                ),
                'order' => array(
                    'type'        => 'string',
                    'enum'        => array( 'ASC', 'DESC', 'asc', 'desc' ),
                    'default'     => 'DESC',
                    'description' => 'Sort order.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Filter by language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'posts' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'             => array( 'type' => 'integer' ),
                            'title'          => array( 'type' => 'string' ),
                            'slug'           => array( 'type' => 'string' ),
                            'status'         => array( 'type' => 'string' ),
                            'date'           => array( 'type' => 'string' ),
                            'modified'       => array( 'type' => 'string' ),
                            'excerpt'        => array( 'type' => 'string' ),
                            'author'         => array( 'type' => 'integer' ),
                            'categories'     => array( 'type' => 'array' ),
                            'tags'           => array( 'type' => 'array' ),
                            'lang'           => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'total'       => array( 'type' => 'integer' ),
                'total_pages' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_posts',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Get Post
     */
    wp_register_ability( 'content/get-post', array(
        'label'       => __( 'Get Post', 'wp-content-abilities' ),
        'description' => __( 'Retrieves a single post by ID, including full content, metadata, categories, and tags.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The post ID.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'             => array( 'type' => 'integer' ),
                'title'          => array( 'type' => 'string' ),
                'slug'           => array( 'type' => 'string' ),
                'content'        => array( 'type' => 'string' ),
                'excerpt'        => array( 'type' => 'string' ),
                'status'         => array( 'type' => 'string' ),
                'date'           => array( 'type' => 'string' ),
                'modified'       => array( 'type' => 'string' ),
                'author'         => array( 'type' => 'integer' ),
                'author_name'    => array( 'type' => 'string' ),
                'featured_image' => array( 'type' => 'string' ),
                'categories'     => array( 'type' => 'array' ),
                'tags'           => array( 'type' => 'array' ),
                'url'            => array( 'type' => 'string' ),
                'lang'           => array( 'type' => 'string' ),
                'translations'   => array( 'type' => 'object', 'description' => 'Map of language slug to post ID for all translations. Requires Polylang.' ),
                'meta'           => array( 'type' => 'object', 'description' => 'Allowlisted custom field values for this post. Empty when no keys are allowlisted.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_get_post',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Create Post
     */
    wp_register_ability( 'content/create-post', array(
        'label'       => __( 'Create Post', 'wp-content-abilities' ),
        'description' => __( 'Creates a new post with full control over all fields including format, sticky, comments, and more.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'title' ),
            'properties' => array(
                'title' => array(
                    'type'        => 'string',
                    'maxLength'   => 500,
                    'description' => 'The post title.',
                ),
                'content' => array(
                    'type'        => 'string',
                    'maxLength'   => 2000000,
                    'description' => 'The post content (HTML supported).',
                ),
                'excerpt' => array(
                    'type'        => 'string',
                    'maxLength'   => 1000,
                    'description' => 'The post excerpt/summary.',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future' ),
                    'default'     => 'draft',
                    'description' => 'The post status. Defaults to draft.',
                ),
                'slug' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'The post slug (URL-friendly name).',
                ),
                'categories' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string', 'maxLength' => 200 ),
                    'maxItems'    => 50,
                    'description' => 'Category slugs to assign.',
                ),
                'tags' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string', 'maxLength' => 200 ),
                    'maxItems'    => 50,
                    'description' => 'Tag names to assign (will be created if they do not exist).',
                ),
                'date' => array(
                    'type'        => 'string',
                    'maxLength'   => 30,
                    'pattern'     => '^\\d{4}-\\d{2}-\\d{2}([T ]\\d{2}:\\d{2}(:\\d{2})?(Z|[+-]\\d{2}:?\\d{2})?)?$',
                    'description' => 'Publish date (ISO 8601 format, e.g. "2026-05-02T14:30:00"). For scheduled posts, use status=future.',
                ),
                'featured_image_id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Media library ID for featured image.',
                ),
                'format' => array(
                    'type'        => 'string',
                    'enum'        => array( 'standard', 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat' ),
                    'default'     => 'standard',
                    'description' => 'Post format.',
                ),
                'sticky' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Pin post to front page.',
                ),
                'comment_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'default'     => 'closed',
                    'description' => 'Whether comments are allowed.',
                ),
                'ping_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'default'     => 'closed',
                    'description' => 'Whether pingbacks/trackbacks are allowed.',
                ),
                'author' => array(
                    'type'        => 'string',
                    'maxLength'   => 60,
                    'description' => 'Author username. Defaults to authenticated user.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Language slug for the post (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Post ID of the original post this is a translation of. Requires Polylang. Automatically links this post as a translation and maps categories to their translated equivalents.',
                ),
                'meta' => array(
                    'type'                 => 'object',
                    'description'          => 'Custom field values keyed by meta key. Only keys registered via the wp_content_abilities_meta_allowlist filter are written; others are silently ignored.',
                    'additionalProperties' => true,
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'title'        => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'status'       => array( 'type' => 'string' ),
                'url'          => array( 'type' => 'string' ),
                'edit_url'     => array( 'type' => 'string' ),
                'format'       => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object', 'description' => 'Map of language slug to post ID for all translations.' ),
                'meta'         => array( 'type' => 'object', 'description' => 'Allowlisted custom field values after save.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_create_post',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Update Post
     */
    wp_register_ability( 'content/update-post', array(
        'label'       => __( 'Update Post', 'wp-content-abilities' ),
        'description' => __( 'Updates an existing post. Only provided fields will be updated; others remain unchanged.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The post ID to update.',
                ),
                'title' => array(
                    'type'        => 'string',
                    'maxLength'   => 500,
                    'description' => 'The new post title.',
                ),
                'content' => array(
                    'type'        => 'string',
                    'maxLength'   => 2000000,
                    'description' => 'The new post content.',
                ),
                'excerpt' => array(
                    'type'        => 'string',
                    'maxLength'   => 1000,
                    'description' => 'The new post excerpt.',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future' ),
                    'description' => 'The new post status.',
                ),
                'slug' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'The new post slug.',
                ),
                'categories' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string', 'maxLength' => 200 ),
                    'maxItems'    => 50,
                    'description' => 'Category slugs (replaces existing).',
                ),
                'tags' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string', 'maxLength' => 200 ),
                    'maxItems'    => 50,
                    'description' => 'Tag slugs (replaces existing).',
                ),
                'date' => array(
                    'type'        => 'string',
                    'maxLength'   => 30,
                    'pattern'     => '^\\d{4}-\\d{2}-\\d{2}([T ]\\d{2}:\\d{2}(:\\d{2})?(Z|[+-]\\d{2}:?\\d{2})?)?$',
                    'description' => 'New publish date (ISO 8601 format, e.g. "2026-05-02T14:30:00").',
                ),
                'featured_image_id' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Media library ID for featured image. Use 0 to remove.',
                ),
                'comment_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'description' => 'Whether comments are allowed.',
                ),
                'ping_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'description' => 'Whether pingbacks/trackbacks are allowed.',
                ),
                'format' => array(
                    'type'        => 'string',
                    'enum'        => array( 'standard', 'aside', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio', 'chat' ),
                    'description' => 'Post format.',
                ),
                'sticky' => array(
                    'type'        => 'boolean',
                    'description' => 'Pin post to front page.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Change the post language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Post ID of the original post this is a translation of. Requires Polylang. Links this post into the translation group of the given post.',
                ),
                'meta' => array(
                    'type'                 => 'object',
                    'description'          => 'Custom field values keyed by meta key. Only keys registered via the wp_content_abilities_meta_allowlist filter are written; others are silently ignored. Pass an empty value to delete a key.',
                    'additionalProperties' => true,
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'title'        => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'status'       => array( 'type' => 'string' ),
                'url'          => array( 'type' => 'string' ),
                'modified'     => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object', 'description' => 'Map of language slug to post ID for all translations.' ),
                'meta'         => array( 'type' => 'object', 'description' => 'Allowlisted custom field values after save.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_update_post',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Delete Post
     */
    wp_register_ability( 'content/delete-post', array(
        'label'       => __( 'Delete Post', 'wp-content-abilities' ),
        'description' => __( 'Deletes a post. By default moves to trash; use force=true to permanently delete.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The post ID to delete.',
                ),
                'force' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'If true, permanently deletes instead of trashing.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'      => array( 'type' => 'integer' ),
                'deleted' => array( 'type' => 'boolean' ),
                'trashed' => array( 'type' => 'boolean' ),
                'title'   => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_delete_post',
        'permission_callback' => function() {
            return current_user_can( 'delete_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // PAGE ABILITIES
    // =========================================================================

    /**
     * List Pages
     */
    wp_register_ability( 'content/list-pages', array(
        'label'       => __( 'List Pages', 'wp-content-abilities' ),
        'description' => __( 'Retrieves a list of pages with optional filtering.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'any' ),
                    'default'     => 'any',
                    'description' => 'Filter by page status.',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'default'     => 10,
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'description' => 'Number of pages to return.',
                ),
                'page' => array(
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                    'description' => 'Page number for pagination.',
                ),
                'search' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Search pages by keyword.',
                ),
                'parent' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Filter by parent page ID. Use 0 for top-level pages.',
                ),
                'orderby' => array(
                    'type'        => 'string',
                    'enum'        => array( 'date', 'title', 'modified', 'menu_order', 'ID' ),
                    'default'     => 'menu_order',
                    'description' => 'Order pages by field.',
                ),
                'order' => array(
                    'type'        => 'string',
                    'enum'        => array( 'ASC', 'DESC', 'asc', 'desc' ),
                    'default'     => 'ASC',
                    'description' => 'Sort order.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Filter by language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'pages' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'         => array( 'type' => 'integer' ),
                            'title'      => array( 'type' => 'string' ),
                            'slug'       => array( 'type' => 'string' ),
                            'status'     => array( 'type' => 'string' ),
                            'date'       => array( 'type' => 'string' ),
                            'modified'   => array( 'type' => 'string' ),
                            'parent'     => array( 'type' => 'integer' ),
                            'menu_order' => array( 'type' => 'integer' ),
                            'lang'       => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'total'       => array( 'type' => 'integer' ),
                'total_pages' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_pages',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Get Page
     */
    wp_register_ability( 'content/get-page', array(
        'label'       => __( 'Get Page', 'wp-content-abilities' ),
        'description' => __( 'Retrieves a single page by ID, including full content.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The page ID.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'             => array( 'type' => 'integer' ),
                'title'          => array( 'type' => 'string' ),
                'slug'           => array( 'type' => 'string' ),
                'content'        => array( 'type' => 'string' ),
                'excerpt'        => array( 'type' => 'string' ),
                'status'         => array( 'type' => 'string' ),
                'date'           => array( 'type' => 'string' ),
                'modified'       => array( 'type' => 'string' ),
                'parent'         => array( 'type' => 'integer' ),
                'menu_order'     => array( 'type' => 'integer' ),
                'template'       => array( 'type' => 'string' ),
                'featured_image' => array( 'type' => 'string' ),
                'url'            => array( 'type' => 'string' ),
                'lang'           => array( 'type' => 'string' ),
                'translations'   => array( 'type' => 'object', 'description' => 'Map of language slug to page ID for all translations. Requires Polylang.' ),
                'meta'           => array( 'type' => 'object', 'description' => 'Allowlisted custom field values for this page. Empty when no keys are allowlisted.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_get_page',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Create Page
     */
    wp_register_ability( 'content/create-page', array(
        'label'       => __( 'Create Page', 'wp-content-abilities' ),
        'description' => __( 'Creates a new page. Supports title, content, parent page, menu order, and page template.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'title' ),
            'properties' => array(
                'title' => array(
                    'type'        => 'string',
                    'maxLength'   => 500,
                    'description' => 'The page title.',
                ),
                'content' => array(
                    'type'        => 'string',
                    'maxLength'   => 2000000,
                    'description' => 'The page content.',
                ),
                'excerpt' => array(
                    'type'        => 'string',
                    'maxLength'   => 1000,
                    'description' => 'The page excerpt.',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private' ),
                    'default'     => 'draft',
                    'description' => 'The page status.',
                ),
                'slug' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'The page slug.',
                ),
                'parent' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Parent page ID for hierarchical pages. Use 0 for top-level.',
                ),
                'menu_order' => array(
                    'type'        => 'integer',
                    'description' => 'Order in page lists.',
                ),
                'template' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Page template filename.',
                ),
                'featured_image_id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Media library ID for featured image.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Language slug for the page (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Page ID of the original page this is a translation of. Requires Polylang.',
                ),
                'meta' => array(
                    'type'                 => 'object',
                    'description'          => 'Custom field values keyed by meta key. Only keys registered via the wp_content_abilities_meta_allowlist filter are written; others are silently ignored.',
                    'additionalProperties' => true,
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'title'        => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'status'       => array( 'type' => 'string' ),
                'url'          => array( 'type' => 'string' ),
                'edit_url'     => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object', 'description' => 'Map of language slug to page ID for all translations.' ),
                'meta'         => array( 'type' => 'object', 'description' => 'Allowlisted custom field values after save.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_create_page',
        'permission_callback' => function() {
            return current_user_can( 'edit_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Update Page
     */
    wp_register_ability( 'content/update-page', array(
        'label'       => __( 'Update Page', 'wp-content-abilities' ),
        'description' => __( 'Updates an existing page. Only provided fields will be updated.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The page ID to update.',
                ),
                'title' => array(
                    'type'        => 'string',
                    'maxLength'   => 500,
                    'description' => 'The new page title.',
                ),
                'content' => array(
                    'type'        => 'string',
                    'maxLength'   => 2000000,
                    'description' => 'The new page content.',
                ),
                'excerpt' => array(
                    'type'        => 'string',
                    'maxLength'   => 1000,
                    'description' => 'The new page excerpt.',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private' ),
                    'description' => 'The new page status.',
                ),
                'slug' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'The new page slug.',
                ),
                'parent' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'New parent page ID. Use 0 for top-level.',
                ),
                'menu_order' => array(
                    'type'        => 'integer',
                    'description' => 'New menu order.',
                ),
                'template' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'New page template.',
                ),
                'featured_image_id' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Media library ID for featured image. Use 0 to remove.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Change the page language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Page ID of the original page this is a translation of. Requires Polylang.',
                ),
                'meta' => array(
                    'type'                 => 'object',
                    'description'          => 'Custom field values keyed by meta key. Only keys registered via the wp_content_abilities_meta_allowlist filter are written; others are silently ignored. Pass an empty value to delete a key.',
                    'additionalProperties' => true,
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'title'        => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'status'       => array( 'type' => 'string' ),
                'url'          => array( 'type' => 'string' ),
                'modified'     => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object', 'description' => 'Map of language slug to page ID for all translations.' ),
                'meta'         => array( 'type' => 'object', 'description' => 'Allowlisted custom field values after save.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_update_page',
        'permission_callback' => function() {
            return current_user_can( 'edit_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Delete Page
     */
    wp_register_ability( 'content/delete-page', array(
        'label'       => __( 'Delete Page', 'wp-content-abilities' ),
        'description' => __( 'Deletes a page. By default moves to trash; use force=true to permanently delete.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The page ID to delete.',
                ),
                'force' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'If true, permanently deletes instead of trashing.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'      => array( 'type' => 'integer' ),
                'deleted' => array( 'type' => 'boolean' ),
                'trashed' => array( 'type' => 'boolean' ),
                'title'   => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_delete_page',
        'permission_callback' => function() {
            return current_user_can( 'delete_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // TAXONOMY ABILITIES
    // =========================================================================

    /**
     * List Categories
     */
    wp_register_ability( 'content/list-categories', array(
        'label'       => __( 'List Categories', 'wp-content-abilities' ),
        'description' => __( 'Retrieves all categories with their post counts.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'properties' => array(
                'hide_empty' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Hide categories with no posts.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Filter categories by language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'categories' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'          => array( 'type' => 'integer' ),
                            'name'        => array( 'type' => 'string' ),
                            'slug'        => array( 'type' => 'string' ),
                            'description' => array( 'type' => 'string' ),
                            'parent'      => array( 'type' => 'integer' ),
                            'count'       => array( 'type' => 'integer' ),
                            'lang'        => array( 'type' => 'string' ),
                        ),
                    ),
                ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_categories',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * List Tags
     */
    wp_register_ability( 'content/list-tags', array(
        'label'       => __( 'List Tags', 'wp-content-abilities' ),
        'description' => __( 'Retrieves all tags with their post counts.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'properties' => array(
                'hide_empty' => array(
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Hide tags with no posts.',
                ),
                'search' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Search tags by name.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Filter tags by language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'tags' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'          => array( 'type' => 'integer' ),
                            'name'        => array( 'type' => 'string' ),
                            'slug'        => array( 'type' => 'string' ),
                            'description' => array( 'type' => 'string' ),
                            'count'       => array( 'type' => 'integer' ),
                            'lang'        => array( 'type' => 'string' ),
                        ),
                    ),
                ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_tags',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // MEDIA ABILITIES
    // =========================================================================

    /**
     * Upload Media
     */
    wp_register_ability( 'content/upload-media', array(
        'label'       => __( 'Upload Media', 'wp-content-abilities' ),
        'description' => __( 'Uploads an image to the WordPress media library from base64 data or URL. Returns the attachment ID for use as featured image.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'filename' ),
            'properties' => array(
                'filename' => array(
                    'type'        => 'string',
                    'maxLength'   => 255,
                    'description' => 'Filename with extension (e.g., "my-image.jpg").',
                ),
                'base64' => array(
                    'type'        => 'string',
                    'maxLength'   => 20000000,
                    'description' => 'Base64-encoded image data (without data URI prefix).',
                ),
                'url' => array(
                    'type'        => 'string',
                    'maxLength'   => 2083,
                    'description' => 'URL to download image from. Use either base64 or url, not both.',
                ),
                'title' => array(
                    'type'        => 'string',
                    'maxLength'   => 500,
                    'description' => 'Title for the media item.',
                ),
                'alt_text' => array(
                    'type'        => 'string',
                    'maxLength'   => 500,
                    'description' => 'Alt text for accessibility.',
                ),
                'caption' => array(
                    'type'        => 'string',
                    'maxLength'   => 1000,
                    'description' => 'Caption for the media item.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'maxLength'   => 5000,
                    'description' => 'Description of the media item.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Language slug for this media item (e.g. "en", "pt"). Only applies when Polylang media translation is enabled.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Attachment ID of the original media item this is a translation of. Only applies when Polylang media translation is enabled.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'url'          => array( 'type' => 'string' ),
                'filename'     => array( 'type' => 'string' ),
                'title'        => array( 'type' => 'string' ),
                'mime_type'    => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object', 'description' => 'Map of language slug to attachment ID for all translations. Requires Polylang media translation.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_upload_media',
        'permission_callback' => function() {
            return current_user_can( 'upload_files' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * List Media
     */
    wp_register_ability( 'content/list-media', array(
        'label'       => __( 'List Media', 'wp-content-abilities' ),
        'description' => __( 'Lists media library items with optional filtering by type and search.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'per_page' => array(
                    'type'        => 'integer',
                    'default'     => 20,
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'description' => 'Number of items to return.',
                ),
                'page' => array(
                    'type'        => 'integer',
                    'default'     => 1,
                    'minimum'     => 1,
                    'description' => 'Page number for pagination.',
                ),
                'mime_type' => array(
                    'type'        => 'string',
                    'maxLength'   => 100,
                    'description' => 'Filter by MIME type (e.g., "image", "image/jpeg", "application/pdf").',
                ),
                'search' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Search by filename or title.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Filter by language slug (e.g. "en", "pt"). Only applies when Polylang media translation is enabled.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'media' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'           => array( 'type' => 'integer' ),
                            'title'        => array( 'type' => 'string' ),
                            'filename'     => array( 'type' => 'string' ),
                            'url'          => array( 'type' => 'string' ),
                            'mime_type'    => array( 'type' => 'string' ),
                            'date'         => array( 'type' => 'string' ),
                            'lang'         => array( 'type' => 'string' ),
                            'translations' => array( 'type' => 'object' ),
                        ),
                    ),
                ),
                'total'       => array( 'type' => 'integer' ),
                'total_pages' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_media',
        'permission_callback' => function() {
            return current_user_can( 'upload_files' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // POLYLANG LANGUAGE ABILITIES
    // =========================================================================

    /**
     * List Languages
     */
    wp_register_ability( 'content/list-languages', array(
        'label'       => __( 'List Languages', 'wp-content-abilities' ),
        'description' => __( 'Returns all languages configured in Polylang. Use the slug value as the lang parameter in other abilities.', 'wp-content-abilities' ),
        'category'    => 'content',
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'languages' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'slug'    => array( 'type' => 'string' ),
                            'name'    => array( 'type' => 'string' ),
                            'locale'  => array( 'type' => 'string' ),
                            'default' => array( 'type' => 'boolean' ),
                        ),
                    ),
                ),
                'polylang_active'          => array( 'type' => 'boolean' ),
                'media_translation_enabled' => array( 'type' => 'boolean', 'description' => 'Whether Polylang media translation is enabled. When true, media items have per-language versions; when false, media is shared across languages.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_languages',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Get Translation
     */
    wp_register_ability( 'content/get-translation', array(
        'label'       => __( 'Get Translation', 'wp-content-abilities' ),
        'description' => __( 'Given a post, page, or media ID and a target language slug, returns the ID and URL of the translated counterpart. Requires Polylang.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id', 'lang' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The source post, page, or media attachment ID.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Target language slug (e.g. "pt", "en").',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'found'         => array( 'type' => 'boolean', 'description' => 'Whether a translation exists in the requested language.' ),
                'id'            => array( 'type' => 'integer', 'description' => 'Translated post/page/media ID, or 0 if not found.' ),
                'url'           => array( 'type' => 'string' ),
                'lang'          => array( 'type' => 'string' ),
                'source_id'     => array( 'type' => 'integer' ),
                'source_lang'   => array( 'type' => 'string' ),
                'all_translations' => array( 'type' => 'object', 'description' => 'Complete map of language slug to ID for all translations of the source.' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_get_translation',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * List Untranslated
     *
     * Find posts/pages in source_lang that lack a translation in target_lang.
     * Closes the "what still needs translating" workflow that otherwise costs
     * one round-trip per post via get-translation.
     */
    wp_register_ability( 'content/list-untranslated', array(
        'label'       => __( 'List Untranslated', 'wp-content-abilities' ),
        'description' => __( 'Lists posts or pages in the source language that have no translation in the target language. Requires Polylang.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'source_lang', 'target_lang' ),
            'properties' => array(
                'source_lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Source language slug (e.g. "en").',
                ),
                'target_lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Target language slug (e.g. "pt"). Posts WITHOUT a translation in this language are returned.',
                ),
                'post_type' => array(
                    'type'        => 'string',
                    'enum'        => array( 'post', 'page' ),
                    'default'     => 'post',
                    'description' => 'Post type to search (post or page).',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'any' ),
                    'default'     => 'publish',
                    'description' => 'Filter source posts by status.',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'default'     => 20,
                    'description' => 'Maximum source posts to scan per page.',
                ),
                'page' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'default'     => 1,
                    'description' => 'Page number for pagination over the source-language scan.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'untranslated' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'       => array( 'type' => 'integer' ),
                            'title'    => array( 'type' => 'string' ),
                            'slug'     => array( 'type' => 'string' ),
                            'status'   => array( 'type' => 'string' ),
                            'modified' => array( 'type' => 'string' ),
                            'url'      => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'source_lang'      => array( 'type' => 'string' ),
                'target_lang'      => array( 'type' => 'string' ),
                'post_type'        => array( 'type' => 'string' ),
                'scanned'          => array( 'type' => 'integer', 'description' => 'Number of source posts scanned in this page.' ),
                'total_source'     => array( 'type' => 'integer', 'description' => 'Total source-language posts matching status filter.' ),
                'total_pages'      => array( 'type' => 'integer' ),
                'polylang_active'  => array( 'type' => 'boolean' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_untranslated',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // REVISION ABILITIES
    // =========================================================================

    /**
     * List Revisions
     *
     * Gives an AI an undo trail. Pair with restore-revision to recover from a
     * bad update.
     */
    wp_register_ability( 'content/list-revisions', array(
        'label'       => __( 'List Revisions', 'wp-content-abilities' ),
        'description' => __( 'Lists all revisions of a post or page in reverse-chronological order, including author and modification time.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The parent post or page ID.',
                ),
                'per_page' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'default'     => 20,
                    'description' => 'Maximum revisions to return.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'revisions' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'          => array( 'type' => 'integer' ),
                            'parent_id'   => array( 'type' => 'integer' ),
                            'author'      => array( 'type' => 'integer' ),
                            'author_name' => array( 'type' => 'string' ),
                            'date'        => array( 'type' => 'string' ),
                            'modified'    => array( 'type' => 'string' ),
                            'title'       => array( 'type' => 'string' ),
                            'is_autosave' => array( 'type' => 'boolean' ),
                        ),
                    ),
                ),
                'total' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_list_revisions',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Restore Revision
     *
     * Reverts a post or page to the state captured by a specific revision.
     * The current state becomes a new revision (no data loss).
     */
    wp_register_ability( 'content/restore-revision', array(
        'label'       => __( 'Restore Revision', 'wp-content-abilities' ),
        'description' => __( 'Reverts a post or page to a specific revision. The current state becomes a new revision so the change is itself reversible.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id', 'revision_id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The parent post or page ID.',
                ),
                'revision_id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'The revision ID to restore (from list-revisions).',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'          => array( 'type' => 'integer' ),
                'revision_id' => array( 'type' => 'integer' ),
                'restored'    => array( 'type' => 'boolean' ),
                'modified'    => array( 'type' => 'string' ),
                'url'         => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_restore_revision',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // TAXONOMY CRUD ABILITIES
    // =========================================================================

    /**
     * Create Category
     */
    wp_register_ability( 'content/create-category', array(
        'label'       => __( 'Create Category', 'wp-content-abilities' ),
        'description' => __( 'Creates a new category. Optionally sets a parent and Polylang language linkage.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'name' ),
            'properties' => array(
                'name' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Category name.',
                ),
                'slug' => array(
                    'type'        => 'string',
                    'maxLength'   => 200,
                    'description' => 'Category slug. Auto-generated from name if omitted.',
                ),
                'description' => array(
                    'type'        => 'string',
                    'maxLength'   => 5000,
                    'description' => 'Category description.',
                ),
                'parent' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Parent category ID. Use 0 for top-level.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Language slug for the category. Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Existing category ID this category translates. Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'name'         => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'parent'       => array( 'type' => 'integer' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_create_category',
        'permission_callback' => function() {
            return current_user_can( 'manage_categories' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Update Category
     */
    wp_register_ability( 'content/update-category', array(
        'label'       => __( 'Update Category', 'wp-content-abilities' ),
        'description' => __( 'Updates an existing category. Only provided fields are changed.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Category ID to update.',
                ),
                'name'        => array( 'type' => 'string', 'maxLength' => 200, 'description' => 'New name.' ),
                'slug'        => array( 'type' => 'string', 'maxLength' => 200, 'description' => 'New slug.' ),
                'description' => array( 'type' => 'string', 'maxLength' => 5000, 'description' => 'New description.' ),
                'parent'      => array( 'type' => 'integer', 'minimum' => 0, 'description' => 'New parent category ID. Use 0 for top-level.' ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Change the category language slug. Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Existing category ID this category translates. Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'name'         => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'parent'       => array( 'type' => 'integer' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_update_category',
        'permission_callback' => function() {
            return current_user_can( 'manage_categories' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Delete Category
     */
    wp_register_ability( 'content/delete-category', array(
        'label'       => __( 'Delete Category', 'wp-content-abilities' ),
        'description' => __( 'Permanently deletes a category. Posts assigned only to this category fall back to the default category.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Category ID to delete.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'      => array( 'type' => 'integer' ),
                'deleted' => array( 'type' => 'boolean' ),
                'name'    => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_delete_category',
        'permission_callback' => function() {
            return current_user_can( 'manage_categories' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Create Tag
     */
    wp_register_ability( 'content/create-tag', array(
        'label'       => __( 'Create Tag', 'wp-content-abilities' ),
        'description' => __( 'Creates a new post tag.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'name' ),
            'properties' => array(
                'name'        => array( 'type' => 'string', 'maxLength' => 200, 'description' => 'Tag name.' ),
                'slug'        => array( 'type' => 'string', 'maxLength' => 200, 'description' => 'Tag slug.' ),
                'description' => array( 'type' => 'string', 'maxLength' => 5000, 'description' => 'Tag description.' ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Language slug for the tag. Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Existing tag ID this tag translates. Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'name'         => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_create_tag',
        'permission_callback' => function() {
            return current_user_can( 'manage_categories' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Update Tag
     */
    wp_register_ability( 'content/update-tag', array(
        'label'       => __( 'Update Tag', 'wp-content-abilities' ),
        'description' => __( 'Updates an existing tag. Only provided fields are changed.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id'          => array( 'type' => 'integer', 'minimum' => 1, 'description' => 'Tag ID to update.' ),
                'name'        => array( 'type' => 'string', 'maxLength' => 200, 'description' => 'New name.' ),
                'slug'        => array( 'type' => 'string', 'maxLength' => 200, 'description' => 'New slug.' ),
                'description' => array( 'type' => 'string', 'maxLength' => 5000, 'description' => 'New description.' ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Change the tag language slug. Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Existing tag ID this tag translates. Requires Polylang.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'           => array( 'type' => 'integer' ),
                'name'         => array( 'type' => 'string' ),
                'slug'         => array( 'type' => 'string' ),
                'lang'         => array( 'type' => 'string' ),
                'translations' => array( 'type' => 'object' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_update_tag',
        'permission_callback' => function() {
            return current_user_can( 'manage_categories' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Delete Tag
     */
    wp_register_ability( 'content/delete-tag', array(
        'label'       => __( 'Delete Tag', 'wp-content-abilities' ),
        'description' => __( 'Permanently deletes a tag. Posts retain other tags they have.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'id' ),
            'properties' => array(
                'id' => array(
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Tag ID to delete.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'id'      => array( 'type' => 'integer' ),
                'deleted' => array( 'type' => 'boolean' ),
                'name'    => array( 'type' => 'string' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_delete_tag',
        'permission_callback' => function() {
            return current_user_can( 'manage_categories' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );

    // =========================================================================
    // BULK OPERATIONS
    // =========================================================================

    /**
     * Bulk Update Posts
     */
    wp_register_ability( 'content/bulk-update-posts', array(
        'label'       => __( 'Bulk Update Posts', 'wp-content-abilities' ),
        'description' => __( 'Applies the same field changes to a list of posts. Re-checks edit permission per post and returns per-item success/failure.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'ids' ),
            'properties' => array(
                'ids' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'integer', 'minimum' => 1 ),
                    'minItems'    => 1,
                    'maxItems'    => 50,
                    'description' => 'Post IDs to update (max 50 per call).',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
                    'description' => 'New status to apply to every post.',
                ),
                'comment_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'description' => 'Comment status to apply to every post.',
                ),
                'ping_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'description' => 'Ping status to apply to every post.',
                ),
                'sticky' => array(
                    'type'        => 'boolean',
                    'description' => 'Mark all posts sticky (true) or unsticky (false).',
                ),
                'add_categories' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string', 'maxLength' => 200 ),
                    'maxItems'    => 50,
                    'description' => 'Category slugs to add to every post (existing categories are preserved).',
                ),
                'add_tags' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string', 'maxLength' => 200 ),
                    'maxItems'    => 50,
                    'description' => 'Tag names/slugs to add to every post (existing tags are preserved).',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'results' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'      => array( 'type' => 'integer' ),
                            'success' => array( 'type' => 'boolean' ),
                            'error'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'success_count' => array( 'type' => 'integer' ),
                'failure_count' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_bulk_update_posts',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Bulk Update Pages
     */
    wp_register_ability( 'content/bulk-update-pages', array(
        'label'       => __( 'Bulk Update Pages', 'wp-content-abilities' ),
        'description' => __( 'Applies the same field changes to a list of pages. Re-checks edit permission per page and returns per-item success/failure.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'ids' ),
            'properties' => array(
                'ids' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'integer', 'minimum' => 1 ),
                    'minItems'    => 1,
                    'maxItems'    => 50,
                    'description' => 'Page IDs to update (max 50 per call).',
                ),
                'status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
                    'description' => 'New status to apply to every page.',
                ),
                'comment_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'description' => 'Comment status to apply to every page.',
                ),
                'ping_status' => array(
                    'type'        => 'string',
                    'enum'        => array( 'open', 'closed' ),
                    'description' => 'Ping status to apply to every page.',
                ),
                'parent' => array(
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Parent page ID to apply to every page (0 for top-level).',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'results' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'      => array( 'type' => 'integer' ),
                            'success' => array( 'type' => 'boolean' ),
                            'error'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'success_count' => array( 'type' => 'integer' ),
                'failure_count' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_bulk_update_pages',
        'permission_callback' => function() {
            return current_user_can( 'edit_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => false,
            ),
        ),
    ) );

    /**
     * Bulk Delete Posts
     */
    wp_register_ability( 'content/bulk-delete-posts', array(
        'label'       => __( 'Bulk Delete Posts', 'wp-content-abilities' ),
        'description' => __( 'Deletes a list of posts. Honours the trash workflow unless force=true. Re-checks delete permission per post.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'ids' ),
            'properties' => array(
                'ids' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'integer', 'minimum' => 1 ),
                    'minItems'    => 1,
                    'maxItems'    => 50,
                    'description' => 'Post IDs to delete (max 50 per call).',
                ),
                'force' => array(
                    'type'        => 'boolean',
                    'description' => 'Bypass trash and permanently delete each post.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'results' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'      => array( 'type' => 'integer' ),
                            'success' => array( 'type' => 'boolean' ),
                            'trashed' => array( 'type' => 'boolean' ),
                            'error'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'success_count' => array( 'type' => 'integer' ),
                'failure_count' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_bulk_delete_posts',
        'permission_callback' => function() {
            return current_user_can( 'delete_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );

    /**
     * Bulk Delete Pages
     */
    wp_register_ability( 'content/bulk-delete-pages', array(
        'label'       => __( 'Bulk Delete Pages', 'wp-content-abilities' ),
        'description' => __( 'Deletes a list of pages. Honours the trash workflow unless force=true. Re-checks delete permission per page.', 'wp-content-abilities' ),
        'category'    => 'content',
        'input_schema' => array(
            'type'       => 'object',
            'required'   => array( 'ids' ),
            'properties' => array(
                'ids' => array(
                    'type'        => 'array',
                    'items'       => array( 'type' => 'integer', 'minimum' => 1 ),
                    'minItems'    => 1,
                    'maxItems'    => 50,
                    'description' => 'Page IDs to delete (max 50 per call).',
                ),
                'force' => array(
                    'type'        => 'boolean',
                    'description' => 'Bypass trash and permanently delete each page.',
                ),
            ),
            'additionalProperties' => false,
        ),
        'output_schema' => array(
            'type'       => 'object',
            'properties' => array(
                'results' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'id'      => array( 'type' => 'integer' ),
                            'success' => array( 'type' => 'boolean' ),
                            'trashed' => array( 'type' => 'boolean' ),
                            'error'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'success_count' => array( 'type' => 'integer' ),
                'failure_count' => array( 'type' => 'integer' ),
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_bulk_delete_pages',
        'permission_callback' => function() {
            return current_user_can( 'delete_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'readonly'     => false,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => true,
                'idempotent'  => true,
            ),
        ),
    ) );
}

// =============================================================================
// CALLBACK IMPLEMENTATIONS
// =============================================================================

/**
 * Decode JSON unicode escapes in Gutenberg block comment attributes.
 *
 * When the WordPress block editor serialises block attributes it JSON-encodes
 * characters like & as \u0026 (and < > as \u003C \u003E) for XSS safety.
 * Returning those escape sequences verbatim to the AI is risky: models often
 * strip the leading backslash during translation, producing the corrupt literal
 * "u0026" instead of the correct "\u0026".
 *
 * This function replaces those escapes with the plain characters *only inside
 * block-opening comment delimiters*, so the AI always sees a clean & character
 * and can faithfully reproduce it. wp_content_abilities_normalize_block_json()
 * then re-encodes correctly when the content is saved back.
 *
 * @param string $content Raw post_content from the database.
 * @return string Content with block-attribute escapes decoded.
 */
function wp_content_abilities_decode_block_attrs( $content ) {
    if ( strpos( $content, '<!-- wp:' ) === false ) {
        return $content;
    }
    // Match only opening block comment lines: <!-- wp:name {...} -->
    // The [^\n]* keeps the regex on a single line, which is how WP stores them.
    return preg_replace_callback(
        '/<!-- wp:[a-z\/][^\n]* -->/i',
        static function ( $m ) {
            return str_replace(
                array( '\u0026', '\u003C', '\u003E', '\u003c', '\u003e' ),
                array( '&',      '<',      '>',      '<',      '>'      ),
                $m[0]
            );
        },
        $content
    );
}

/**
 * Normalize Gutenberg block JSON encoding before saving to the database.
 *
 * Two-step process:
 *
 * 1. parse_blocks() + serialize_blocks() re-serialises block attributes
 *    through WordPress's own encoder, which converts & → \u0026 etc.
 *
 * 2. A direct regex pass then fixes any remaining bare uXXXX sequences that
 *    are NOT preceded by a backslash (i.e. the AI dropped the leading \).
 *    parse_blocks/serialize_blocks alone cannot fix "u0026" → "\u0026"
 *    because PHP's json_encode treats "u0026" as ordinary text and emits it
 *    unchanged; only the regex catches that specific case.
 *
 * @param string $content Content from AI input.
 * @return string Normalised content safe to pass to wp_insert_post / wp_update_post.
 */
function wp_content_abilities_normalize_block_json( $content ) {
    if ( $content === '' || strpos( $content, '<!-- wp:' ) === false ) {
        return $content;
    }

    // Step 1: re-serialise via WP core (handles & → \u0026 and similar).
    if ( function_exists( 'parse_blocks' ) && function_exists( 'serialize_blocks' ) ) {
        $serialized = serialize_blocks( parse_blocks( $content ) );
        if ( ! empty( $serialized ) ) {
            $content = $serialized;
        }
    }

    // Step 2: safety-net regex — fix bare uXXXX escapes (backslash dropped by AI)
    // only inside opening block comment delimiters <!-- wp:name {...} -->.
    // The lookbehind (?<!\\) ensures we never double-add the backslash to an
    // already-correct \u0026.
    return preg_replace_callback(
        '/<!-- wp:[a-z\/][^\n]* -->/i',
        static function ( $m ) {
            // (a) literal & → & (JSON-required encoding; JS JSON.stringify
            //     doesn't escape & so it arrives as a bare ampersand).
            $c = str_replace( '&', '\\u0026', $m[0] );
            // (b) bare uXXXX → \uXXXX (backslash dropped by AI). Lookbehind
            //     prevents double-escaping an already-correct &.
            $c = preg_replace( '/(?<!\\\\)u0026/i', '\\\\u0026', $c );
            $c = preg_replace( '/(?<!\\\\)u003[Cc]/',  '\\\\u003C', $c );
            $c = preg_replace( '/(?<!\\\\)u003[Ee]/',  '\\\\u003E', $c );
            return $c;
        },
        $content
    );
}

/**
 * Resolve a requested post status to what the current user is allowed to see.
 *
 * Subscribers (capability: read) may only list published content.
 * Authors/editors (capability: edit_posts) may also see their own drafts,
 * pending, private, and future posts.
 * Editors/admins (capability: edit_others_posts) may use 'any'.
 *
 * @param string $requested The status requested by the caller.
 * @param string $post_type 'post' or 'page' — used to pick the right caps.
 * @return string Sanitised status safe to pass to WP_Query.
 */
function wp_content_abilities_resolve_status( $requested, $post_type = 'post' ) {
    $edit_cap        = ( 'page' === $post_type ) ? 'edit_pages'        : 'edit_posts';
    $edit_others_cap = ( 'page' === $post_type ) ? 'edit_others_pages' : 'edit_others_posts';

    // Only editors/admins can request 'any' or see content from all authors.
    if ( 'any' === $requested ) {
        return current_user_can( $edit_others_cap ) ? 'any' : 'publish';
    }

    // Non-published statuses require at least author-level capability.
    $non_published = array( 'draft', 'pending', 'private', 'future' );
    if ( in_array( $requested, $non_published, true ) ) {
        return current_user_can( $edit_cap ) ? $requested : 'publish';
    }

    return 'publish';
}

/**
 * Authorize a write to the given post status.
 *
 * wp_insert_post / wp_update_post don't enforce capability-per-status when
 * called outside the admin UI — a Contributor with edit_posts could otherwise
 * publish or set 'private' by passing the status string directly.
 *
 * @param string $status    The target post status.
 * @param string $post_type 'post' or 'page' — picks the *_pages variants.
 * @return true|WP_Error    True if allowed; WP_Error with status 403 otherwise.
 */
function wp_content_abilities_check_status_cap( $status, $post_type = 'post' ) {
    $publish_cap = ( 'page' === $post_type ) ? 'publish_pages'         : 'publish_posts';
    $private_cap = ( 'page' === $post_type ) ? 'publish_pages'         : 'publish_posts'; // WP doesn't expose publish_private_pages; use publish_pages.
    if ( 'post' === $post_type ) {
        $private_cap = 'publish_private_posts';
    }

    if ( in_array( $status, array( 'publish', 'future' ), true ) && ! current_user_can( $publish_cap ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to publish.', array( 'status' => 403 ) );
    }
    if ( 'private' === $status && ! current_user_can( $private_cap ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to create private content.', array( 'status' => 403 ) );
    }
    return true;
}

/**
 * Return the allowlist of post meta keys that abilities may read or write.
 *
 * The default is empty — meta is opt-in. Sites must register specific keys
 * via the `wp_content_abilities_meta_allowlist` filter:
 *
 *     add_filter( 'wp_content_abilities_meta_allowlist', function( $keys ) {
 *         $keys[] = 'my_custom_field';
 *         return $keys;
 *     } );
 *
 * Internal WordPress keys (those starting with "_") and core author/format
 * meta are always excluded for safety.
 *
 * @return string[] Allowlisted meta keys.
 */
function wp_content_abilities_get_meta_allowlist() {
    $keys = apply_filters( 'wp_content_abilities_meta_allowlist', array() );
    if ( ! is_array( $keys ) ) {
        return array();
    }
    $clean = array();
    foreach ( $keys as $key ) {
        if ( ! is_string( $key ) || '' === $key ) {
            continue;
        }
        if ( strpos( $key, '_' ) === 0 ) {
            continue; // Reject internal/protected keys.
        }
        $clean[] = $key;
    }
    return array_values( array_unique( $clean ) );
}

/**
 * Read allowlisted meta values for a post into a flat associative array.
 *
 * @param int $post_id The post ID.
 * @return array|object Map of key → value, or (object) array() when empty.
 */
function wp_content_abilities_read_meta( $post_id ) {
    $allowlist = wp_content_abilities_get_meta_allowlist();
    if ( empty( $allowlist ) ) {
        return (object) array();
    }
    $out = array();
    foreach ( $allowlist as $key ) {
        $value = get_post_meta( $post_id, $key, true );
        if ( '' === $value || null === $value ) {
            continue;
        }
        $out[ $key ] = $value;
    }
    return empty( $out ) ? (object) array() : $out;
}

/**
 * Apply ability-supplied meta writes to a post, restricted to the allowlist.
 *
 * Sites can register a per-key sanitizer via the
 * `wp_content_abilities_meta_sanitize_{$key}` filter. When no sanitizer is
 * registered, scalars fall through `sanitize_text_field` and arrays are
 * recursively cleaned.
 *
 * @param int   $post_id     The post ID.
 * @param mixed $meta_input  The raw `meta` input (expected: associative array).
 * @return void
 */
function wp_content_abilities_apply_meta_writes( $post_id, $meta_input ) {
    if ( ! is_array( $meta_input ) || empty( $meta_input ) ) {
        return;
    }
    $allowlist = wp_content_abilities_get_meta_allowlist();
    if ( empty( $allowlist ) ) {
        return;
    }
    foreach ( $meta_input as $key => $value ) {
        if ( ! is_string( $key ) || ! in_array( $key, $allowlist, true ) ) {
            continue;
        }
        $sanitized = apply_filters( "wp_content_abilities_meta_sanitize_{$key}", null, $value, $post_id );
        if ( null === $sanitized ) {
            $sanitized = is_scalar( $value )
                ? sanitize_text_field( (string) $value )
                : map_deep( $value, 'sanitize_text_field' );
        }
        if ( null === $sanitized || '' === $sanitized ) {
            delete_post_meta( $post_id, $key );
        } else {
            update_post_meta( $post_id, $key, $sanitized );
        }
    }
}

/**
 * List Posts callback
 */
function wp_content_abilities_list_posts( $input ) {
    $requested = $input['status'] ?? 'any';
    $status     = wp_content_abilities_resolve_status( $requested, 'post' );

    $args = array(
        'post_type'      => 'post',
        'post_status'    => $status,
        'posts_per_page' => $input['per_page'] ?? 10,
        'paged'          => $input['page'] ?? 1,
        'orderby'        => $input['orderby'] ?? 'date',
        'order'          => strtoupper( $input['order'] ?? 'DESC' ),
    );

    if ( ! empty( $input['search'] ) ) {
        $args['s'] = $input['search'];
    }

    if ( ! empty( $input['category'] ) ) {
        $args['category_name'] = $input['category'];
    }

    if ( ! empty( $input['tag'] ) ) {
        $args['tag'] = $input['tag'];
    }

    if ( ! empty( $input['author'] ) ) {
        $args['author'] = $input['author'];
    }

    // Polylang: filter by language slug if provided.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_languages_list' ) ) {
        $args['lang'] = sanitize_key( $input['lang'] );
    }

    $query = new WP_Query( $args );
    $posts = array();

    foreach ( $query->posts as $post ) {
        $posts[] = array(
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'slug'       => $post->post_name,
            'status'     => $post->post_status,
            'date'       => $post->post_date,
            'modified'   => $post->post_modified,
            'excerpt'    => wp_trim_words( $post->post_excerpt ?: $post->post_content, 30 ),
            'author'     => (int) $post->post_author,
            'categories' => wp_get_post_categories( $post->ID, array( 'fields' => 'slugs' ) ),
            'tags'       => wp_get_post_tags( $post->ID, array( 'fields' => 'slugs' ) ),
            'lang'       => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
        );
    }

    return array(
        'posts'       => $posts,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
    );
}

/**
 * Get Post callback
 */
function wp_content_abilities_get_post( $input ) {
    $post = get_post( $input['id'] );

    // Return identical "not found" for missing-ID, wrong-type, AND no-permission
    // cases so unauthenticated probes can't distinguish private/draft existence
    // from non-existence.
    if ( ! $post || $post->post_type !== 'post' || ! current_user_can( 'read_post', $post->ID ) ) {
        return new WP_Error( 'not_found', 'Post not found.', array( 'status' => 404 ) );
    }

    $author = get_userdata( $post->post_author );
    $thumbnail_id = get_post_thumbnail_id( $post->ID );

    return array(
        'id'             => $post->ID,
        'title'          => $post->post_title,
        'slug'           => $post->post_name,
        'content'        => wp_content_abilities_decode_block_attrs( $post->post_content ),
        'excerpt'        => $post->post_excerpt,
        'status'         => $post->post_status,
        'date'           => $post->post_date,
        'modified'       => $post->post_modified,
        'author'         => (int) $post->post_author,
        'author_name'    => $author ? $author->display_name : '',
        'featured_image' => $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '',
        'categories'     => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ),
        'tags'           => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
        'url'            => get_permalink( $post->ID ),
        'lang'           => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
        'translations'   => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post->ID ) : (object) array(),
        'meta'           => wp_content_abilities_read_meta( $post->ID ),
    );
}

/**
 * Create Post callback
 */
function wp_content_abilities_create_post( $input ) {
    $status = $input['status'] ?? 'draft';

    $cap_check = wp_content_abilities_check_status_cap( $status, 'post' );
    if ( is_wp_error( $cap_check ) ) {
        return $cap_check;
    }

    $post_data = array(
        'post_type'      => 'post',
        'post_title'     => $input['title'],
        'post_content'   => wp_content_abilities_normalize_block_json( $input['content'] ?? '' ),
        'post_excerpt'   => $input['excerpt'] ?? '',
        'post_status'    => $status,
        'post_name'      => $input['slug'] ?? '',
        'comment_status' => $input['comment_status'] ?? 'closed',
        'ping_status'    => $input['ping_status'] ?? 'closed',
    );

    if ( ! empty( $input['date'] ) ) {
        $post_data['post_date'] = $input['date'];
    }

    // Handle author by username
    if ( ! empty( $input['author'] ) && current_user_can( 'edit_others_posts' ) ) {
        $author = get_user_by( 'login', $input['author'] );
        if ( $author ) {
            $post_data['post_author'] = $author->ID;
        }
    }

    // Handle categories — if Polylang lang is set, try to map to translated category equivalents.
    if ( ! empty( $input['categories'] ) ) {
        $cat_ids = array();
        $lang    = ! empty( $input['lang'] ) ? sanitize_key( $input['lang'] ) : '';
        foreach ( $input['categories'] as $slug ) {
            $cat = get_category_by_slug( $slug );
            if ( $cat ) {
                if ( $lang && function_exists( 'pll_get_term' ) ) {
                    $translated_id = pll_get_term( $cat->term_id, $lang );
                    $cat_ids[] = $translated_id ? (int) $translated_id : $cat->term_id;
                } else {
                    $cat_ids[] = $cat->term_id;
                }
            }
        }
        $post_data['post_category'] = $cat_ids;
    }

    $post_id = wp_insert_post( $post_data, true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    // Handle tags — if Polylang lang is set, map to translated tag equivalents.
    if ( ! empty( $input['tags'] ) ) {
        $lang = ! empty( $input['lang'] ) ? sanitize_key( $input['lang'] ) : '';
        if ( $lang && function_exists( 'pll_get_term' ) ) {
            $term_refs = array();
            foreach ( $input['tags'] as $tag_name ) {
                $term = get_term_by( 'slug', $tag_name, 'post_tag' )
                     ?: get_term_by( 'name', $tag_name, 'post_tag' );
                if ( $term ) {
                    $term_refs[] = (int) ( pll_get_term( $term->term_id, $lang ) ?: $term->term_id );
                } else {
                    $term_refs[] = $tag_name; // new tag, wp_set_post_tags will create it
                }
            }
            wp_set_post_tags( $post_id, $term_refs );
        } else {
            wp_set_post_tags( $post_id, $input['tags'] );
        }
    }

    // Handle featured image — verify the attachment exists, is an image,
    // and belongs to the current user (or the user can edit others' posts).
    if ( ! empty( $input['featured_image_id'] ) ) {
        $attachment = get_post( $input['featured_image_id'] );
        if ( $attachment && 'attachment' === $attachment->post_type
            && wp_attachment_is_image( $attachment->ID )
            && ( (int) $attachment->post_author === get_current_user_id() || current_user_can( 'edit_others_posts' ) )
        ) {
            set_post_thumbnail( $post_id, $attachment->ID );
        }
    }

    // Handle post format
    if ( ! empty( $input['format'] ) && $input['format'] !== 'standard' ) {
        set_post_format( $post_id, $input['format'] );
    }

    // Handle sticky
    if ( ! empty( $input['sticky'] ) && $input['sticky'] === true ) {
        stick_post( $post_id );
    }

    // Handle Polylang language and translation linking.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_post_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_post_language( $post_id, $lang );

        // Link as translation of another post if requested.
        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
            $original_id  = (int) $input['translation_of'];
            $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
            $translations[ $lang ] = $post_id;
            pll_save_post_translations( $translations );
        }
    }

    // Allowlisted custom fields.
    if ( isset( $input['meta'] ) ) {
        wp_content_abilities_apply_meta_writes( $post_id, $input['meta'] );
    }

    $post = get_post( $post_id );

    return array(
        'id'           => $post_id,
        'title'        => $post->post_title,
        'slug'         => $post->post_name,
        'status'       => $post->post_status,
        'url'          => get_permalink( $post_id ),
        'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
        'format'       => get_post_format( $post_id ) ?: 'standard',
        'lang'         => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post_id ) : '',
        'translations' => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post_id ) : (object) array(),
        'meta'         => wp_content_abilities_read_meta( $post_id ),
    );
}

/**
 * Update Post callback
 */
function wp_content_abilities_update_post( $input ) {
    $post = get_post( $input['id'] );

    if ( ! $post || $post->post_type !== 'post' ) {
        return new WP_Error( 'not_found', 'Post not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'edit_post', $post->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to edit this post.', array( 'status' => 403 ) );
    }

    if ( isset( $input['status'] ) ) {
        $cap_check = wp_content_abilities_check_status_cap( $input['status'], 'post' );
        if ( is_wp_error( $cap_check ) ) {
            return $cap_check;
        }
    }

    $post_data = array( 'ID' => $input['id'] );

    if ( isset( $input['title'] ) ) {
        $post_data['post_title'] = $input['title'];
    }
    if ( isset( $input['content'] ) ) {
        $post_data['post_content'] = wp_content_abilities_normalize_block_json( $input['content'] );
    }
    if ( isset( $input['excerpt'] ) ) {
        $post_data['post_excerpt'] = $input['excerpt'];
    }
    if ( isset( $input['status'] ) ) {
        $post_data['post_status'] = $input['status'];
    }
    if ( isset( $input['slug'] ) ) {
        $post_data['post_name'] = $input['slug'];
    }
    if ( isset( $input['date'] ) ) {
        $post_data['post_date'] = $input['date'];
    }
    if ( isset( $input['comment_status'] ) ) {
        $post_data['comment_status'] = $input['comment_status'];
    }
    if ( isset( $input['ping_status'] ) ) {
        $post_data['ping_status'] = $input['ping_status'];
    }

    $result = wp_update_post( $post_data, true );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    // Post format
    if ( isset( $input['format'] ) ) {
        if ( 'standard' === $input['format'] ) {
            set_post_format( $input['id'], false );
        } else {
            set_post_format( $input['id'], $input['format'] );
        }
    }

    // Sticky
    if ( isset( $input['sticky'] ) ) {
        if ( true === $input['sticky'] ) {
            stick_post( $input['id'] );
        } else {
            unstick_post( $input['id'] );
        }
    }

    // Handle categories — if Polylang lang is set, try to map to translated category equivalents.
    if ( isset( $input['categories'] ) ) {
        $cat_ids = array();
        $lang    = ! empty( $input['lang'] ) ? sanitize_key( $input['lang'] ) : (
            function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $input['id'] ) : ''
        );
        foreach ( $input['categories'] as $slug ) {
            $cat = get_category_by_slug( $slug );
            if ( $cat ) {
                if ( $lang && function_exists( 'pll_get_term' ) ) {
                    $translated_id = pll_get_term( $cat->term_id, $lang );
                    $cat_ids[] = $translated_id ? (int) $translated_id : $cat->term_id;
                } else {
                    $cat_ids[] = $cat->term_id;
                }
            }
        }
        wp_set_post_categories( $input['id'], $cat_ids );
    }

    // Handle tags — if Polylang lang is set, map to translated tag equivalents.
    if ( isset( $input['tags'] ) ) {
        $lang = ! empty( $input['lang'] ) ? sanitize_key( $input['lang'] ) : (
            function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $input['id'] ) : ''
        );
        if ( $lang && function_exists( 'pll_get_term' ) ) {
            $term_refs = array();
            foreach ( $input['tags'] as $tag_name ) {
                $term = get_term_by( 'slug', $tag_name, 'post_tag' )
                     ?: get_term_by( 'name', $tag_name, 'post_tag' );
                if ( $term ) {
                    $term_refs[] = (int) ( pll_get_term( $term->term_id, $lang ) ?: $term->term_id );
                } else {
                    $term_refs[] = $tag_name;
                }
            }
            wp_set_post_tags( $input['id'], $term_refs );
        } else {
            wp_set_post_tags( $input['id'], $input['tags'] );
        }
    }

    // Handle featured image — verify the attachment exists, is an image,
    // and belongs to the current user (or the user can edit others' posts).
    if ( isset( $input['featured_image_id'] ) ) {
        if ( 0 === $input['featured_image_id'] ) {
            delete_post_thumbnail( $input['id'] );
        } else {
            $attachment = get_post( $input['featured_image_id'] );
            if ( $attachment && 'attachment' === $attachment->post_type
                && wp_attachment_is_image( $attachment->ID )
                && ( (int) $attachment->post_author === get_current_user_id() || current_user_can( 'edit_others_posts' ) )
            ) {
                set_post_thumbnail( $input['id'], $attachment->ID );
            }
        }
    }

    // Handle Polylang language and translation linking.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_post_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_post_language( $input['id'], $lang );

        // Link as translation of another post if requested.
        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
            $original_id  = (int) $input['translation_of'];
            $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
            $translations[ $lang ] = $input['id'];
            pll_save_post_translations( $translations );
        }
    } elseif ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
        // No lang change, but still link as translation.
        $lang         = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $input['id'] ) : '';
        $original_id  = (int) $input['translation_of'];
        if ( $lang ) {
            $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
            $translations[ $lang ] = $input['id'];
            pll_save_post_translations( $translations );
        }
    }

    // Allowlisted custom fields.
    if ( isset( $input['meta'] ) ) {
        wp_content_abilities_apply_meta_writes( $input['id'], $input['meta'] );
    }

    $post = get_post( $input['id'] );

    return array(
        'id'           => $post->ID,
        'title'        => $post->post_title,
        'slug'         => $post->post_name,
        'status'       => $post->post_status,
        'url'          => get_permalink( $post->ID ),
        'modified'     => $post->post_modified,
        'lang'         => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
        'translations' => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post->ID ) : (object) array(),
        'meta'         => wp_content_abilities_read_meta( $post->ID ),
    );
}

/**
 * Delete Post callback
 */
function wp_content_abilities_delete_post( $input ) {
    $post = get_post( $input['id'] );

    if ( ! $post || $post->post_type !== 'post' ) {
        return new WP_Error( 'not_found', 'Post not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'delete_post', $post->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to delete this post.', array( 'status' => 403 ) );
    }

    $title = $post->post_title;
    $force = $input['force'] ?? false;

    $result = wp_delete_post( $input['id'], $force );

    if ( ! $result ) {
        return new WP_Error( 'delete_failed', 'Failed to delete post.', array( 'status' => 500 ) );
    }

    return array(
        'id'      => $input['id'],
        'deleted' => $force,
        'trashed' => ! $force,
        'title'   => $title,
    );
}

/**
 * List Pages callback
 */
function wp_content_abilities_list_pages( $input ) {
    $requested = $input['status'] ?? 'any';
    $status     = wp_content_abilities_resolve_status( $requested, 'page' );

    $args = array(
        'post_type'      => 'page',
        'post_status'    => $status,
        'posts_per_page' => $input['per_page'] ?? 10,
        'paged'          => $input['page'] ?? 1,
        'orderby'        => $input['orderby'] ?? 'menu_order',
        'order'          => strtoupper( $input['order'] ?? 'ASC' ),
    );

    if ( ! empty( $input['search'] ) ) {
        $args['s'] = $input['search'];
    }

    if ( isset( $input['parent'] ) ) {
        $args['post_parent'] = $input['parent'];
    }

    // Polylang: filter by language slug if provided.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_languages_list' ) ) {
        $args['lang'] = sanitize_key( $input['lang'] );
    }

    $query = new WP_Query( $args );
    $pages = array();

    foreach ( $query->posts as $post ) {
        $pages[] = array(
            'id'         => $post->ID,
            'title'      => $post->post_title,
            'slug'       => $post->post_name,
            'status'     => $post->post_status,
            'date'       => $post->post_date,
            'modified'   => $post->post_modified,
            'parent'     => (int) $post->post_parent,
            'menu_order' => (int) $post->menu_order,
            'lang'       => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
        );
    }

    return array(
        'pages'       => $pages,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
    );
}

/**
 * Get Page callback
 */
function wp_content_abilities_get_page( $input ) {
    $post = get_post( $input['id'] );

    // Return identical "not found" for missing-ID, wrong-type, AND no-permission
    // cases so unauthenticated probes can't distinguish private/draft existence
    // from non-existence.
    if ( ! $post || $post->post_type !== 'page' || ! current_user_can( 'read_post', $post->ID ) ) {
        return new WP_Error( 'not_found', 'Page not found.', array( 'status' => 404 ) );
    }

    $thumbnail_id = get_post_thumbnail_id( $post->ID );

    return array(
        'id'             => $post->ID,
        'title'          => $post->post_title,
        'slug'           => $post->post_name,
        'content'        => wp_content_abilities_decode_block_attrs( $post->post_content ),
        'excerpt'        => $post->post_excerpt,
        'status'         => $post->post_status,
        'date'           => $post->post_date,
        'modified'       => $post->post_modified,
        'parent'         => (int) $post->post_parent,
        'menu_order'     => (int) $post->menu_order,
        'template'       => get_page_template_slug( $post->ID ),
        'featured_image' => $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : '',
        'url'            => get_permalink( $post->ID ),
        'lang'           => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
        'translations'   => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post->ID ) : (object) array(),
        'meta'           => wp_content_abilities_read_meta( $post->ID ),
    );
}

/**
 * Create Page callback
 */
function wp_content_abilities_create_page( $input ) {
    $status = $input['status'] ?? 'draft';

    $cap_check = wp_content_abilities_check_status_cap( $status, 'page' );
    if ( is_wp_error( $cap_check ) ) {
        return $cap_check;
    }

    // Validate parent: must exist and be a page.
    $parent_id = isset( $input['parent'] ) ? (int) $input['parent'] : 0;
    if ( $parent_id > 0 ) {
        $parent_post = get_post( $parent_id );
        if ( ! $parent_post || 'page' !== $parent_post->post_type ) {
            return new WP_Error( 'invalid_parent', 'Parent page not found.', array( 'status' => 400 ) );
        }
    }

    $post_data = array(
        'post_type'    => 'page',
        'post_title'   => $input['title'],
        'post_content' => wp_content_abilities_normalize_block_json( $input['content'] ?? '' ),
        'post_excerpt' => $input['excerpt'] ?? '',
        'post_status'  => $status,
        'post_name'    => $input['slug'] ?? '',
        'post_parent'  => $parent_id,
        'menu_order'   => $input['menu_order'] ?? 0,
    );

    $post_id = wp_insert_post( $post_data, true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }

    // Handle template — validate against registered templates to prevent LFI.
    if ( ! empty( $input['template'] ) ) {
        $valid_templates = array_keys( wp_get_theme()->get_page_templates() );
        $valid_templates[] = 'default';
        if ( in_array( $input['template'], $valid_templates, true ) ) {
            update_post_meta( $post_id, '_wp_page_template', $input['template'] );
        }
    }

    // Handle featured image — verify the attachment exists, is an image,
    // and belongs to the current user (or the user can edit others' pages).
    if ( ! empty( $input['featured_image_id'] ) ) {
        $attachment = get_post( $input['featured_image_id'] );
        if ( $attachment && 'attachment' === $attachment->post_type
            && wp_attachment_is_image( $attachment->ID )
            && ( (int) $attachment->post_author === get_current_user_id() || current_user_can( 'edit_others_pages' ) )
        ) {
            set_post_thumbnail( $post_id, $attachment->ID );
        }
    }

    // Handle Polylang language and translation linking.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_post_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_post_language( $post_id, $lang );

        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
            $original_id  = (int) $input['translation_of'];
            $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
            $translations[ $lang ] = $post_id;
            pll_save_post_translations( $translations );
        }
    }

    // Allowlisted custom fields.
    if ( isset( $input['meta'] ) ) {
        wp_content_abilities_apply_meta_writes( $post_id, $input['meta'] );
    }

    $post = get_post( $post_id );

    return array(
        'id'           => $post_id,
        'title'        => $post->post_title,
        'slug'         => $post->post_name,
        'status'       => $post->post_status,
        'url'          => get_permalink( $post_id ),
        'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
        'lang'         => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post_id ) : '',
        'translations' => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post_id ) : (object) array(),
        'meta'         => wp_content_abilities_read_meta( $post_id ),
    );
}

/**
 * Update Page callback
 */
function wp_content_abilities_update_page( $input ) {
    $post = get_post( $input['id'] );

    if ( ! $post || $post->post_type !== 'page' ) {
        return new WP_Error( 'not_found', 'Page not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'edit_post', $post->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to edit this page.', array( 'status' => 403 ) );
    }

    if ( isset( $input['status'] ) ) {
        $cap_check = wp_content_abilities_check_status_cap( $input['status'], 'page' );
        if ( is_wp_error( $cap_check ) ) {
            return $cap_check;
        }
    }

    // Validate new parent: must exist, be a page, and not create a cycle.
    if ( isset( $input['parent'] ) ) {
        $new_parent = (int) $input['parent'];
        if ( $new_parent > 0 ) {
            if ( $new_parent === (int) $input['id'] ) {
                return new WP_Error( 'invalid_parent', 'A page cannot be its own parent.', array( 'status' => 400 ) );
            }
            $parent_post = get_post( $new_parent );
            if ( ! $parent_post || 'page' !== $parent_post->post_type ) {
                return new WP_Error( 'invalid_parent', 'Parent page not found.', array( 'status' => 400 ) );
            }
            // Walk ancestry of the proposed parent; reject if this page is in it (cycle).
            $ancestors = get_post_ancestors( $new_parent );
            if ( in_array( (int) $input['id'], array_map( 'intval', $ancestors ), true ) ) {
                return new WP_Error( 'invalid_parent', 'Setting this parent would create a hierarchy cycle.', array( 'status' => 400 ) );
            }
        }
    }

    $post_data = array( 'ID' => $input['id'] );

    if ( isset( $input['title'] ) ) {
        $post_data['post_title'] = $input['title'];
    }
    if ( isset( $input['content'] ) ) {
        $post_data['post_content'] = wp_content_abilities_normalize_block_json( $input['content'] );
    }
    if ( isset( $input['excerpt'] ) ) {
        $post_data['post_excerpt'] = $input['excerpt'];
    }
    if ( isset( $input['status'] ) ) {
        $post_data['post_status'] = $input['status'];
    }
    if ( isset( $input['slug'] ) ) {
        $post_data['post_name'] = $input['slug'];
    }
    if ( isset( $input['parent'] ) ) {
        $post_data['post_parent'] = (int) $input['parent'];
    }
    if ( isset( $input['menu_order'] ) ) {
        $post_data['menu_order'] = $input['menu_order'];
    }

    $result = wp_update_post( $post_data, true );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    // Handle template — validate against registered templates to prevent LFI.
    if ( isset( $input['template'] ) ) {
        $valid_templates = array_keys( wp_get_theme()->get_page_templates() );
        $valid_templates[] = 'default';
        if ( in_array( $input['template'], $valid_templates, true ) ) {
            update_post_meta( $input['id'], '_wp_page_template', $input['template'] );
        }
    }

    // Handle featured image — verify the attachment exists, is an image,
    // and belongs to the current user (or the user can edit others' pages).
    if ( isset( $input['featured_image_id'] ) ) {
        if ( 0 === $input['featured_image_id'] ) {
            delete_post_thumbnail( $input['id'] );
        } else {
            $attachment = get_post( $input['featured_image_id'] );
            if ( $attachment && 'attachment' === $attachment->post_type
                && wp_attachment_is_image( $attachment->ID )
                && ( (int) $attachment->post_author === get_current_user_id() || current_user_can( 'edit_others_pages' ) )
            ) {
                set_post_thumbnail( $input['id'], $attachment->ID );
            }
        }
    }

    // Handle Polylang language and translation linking.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_post_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_post_language( $input['id'], $lang );

        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
            $original_id  = (int) $input['translation_of'];
            $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
            $translations[ $lang ] = $input['id'];
            pll_save_post_translations( $translations );
        }
    } elseif ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
        $lang        = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $input['id'] ) : '';
        $original_id = (int) $input['translation_of'];
        if ( $lang ) {
            $translations         = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
            $translations[ $lang ] = $input['id'];
            pll_save_post_translations( $translations );
        }
    }

    // Allowlisted custom fields.
    if ( isset( $input['meta'] ) ) {
        wp_content_abilities_apply_meta_writes( $input['id'], $input['meta'] );
    }

    $post = get_post( $input['id'] );

    return array(
        'id'           => $post->ID,
        'title'        => $post->post_title,
        'slug'         => $post->post_name,
        'status'       => $post->post_status,
        'url'          => get_permalink( $post->ID ),
        'modified'     => $post->post_modified,
        'lang'         => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post->ID ) : '',
        'translations' => function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $post->ID ) : (object) array(),
        'meta'         => wp_content_abilities_read_meta( $post->ID ),
    );
}

/**
 * Delete Page callback
 */
function wp_content_abilities_delete_page( $input ) {
    $post = get_post( $input['id'] );

    if ( ! $post || $post->post_type !== 'page' ) {
        return new WP_Error( 'not_found', 'Page not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'delete_post', $post->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to delete this page.', array( 'status' => 403 ) );
    }

    $title = $post->post_title;
    $force = $input['force'] ?? false;

    $result = wp_delete_post( $input['id'], $force );

    if ( ! $result ) {
        return new WP_Error( 'delete_failed', 'Failed to delete page.', array( 'status' => 500 ) );
    }

    return array(
        'id'      => $input['id'],
        'deleted' => $force,
        'trashed' => ! $force,
        'title'   => $title,
    );
}

/**
 * List Categories callback
 */
function wp_content_abilities_list_categories( $input ) {
    $args = array(
        'hide_empty' => $input['hide_empty'] ?? false,
    );

    // Polylang: filter by language slug if provided.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_get_term_language' ) ) {
        $args['lang'] = sanitize_key( $input['lang'] );
    }

    $categories = get_categories( $args );
    $result = array();

    foreach ( $categories as $cat ) {
        $result[] = array(
            'id'          => $cat->term_id,
            'name'        => $cat->name,
            'slug'        => $cat->slug,
            'description' => $cat->description,
            'parent'      => $cat->parent,
            'count'       => $cat->count,
            'lang'        => function_exists( 'pll_get_term_language' ) ? (string) pll_get_term_language( $cat->term_id ) : '',
        );
    }

    return array( 'categories' => $result );
}

/**
 * List Tags callback
 */
function wp_content_abilities_list_tags( $input ) {
    $args = array(
        'hide_empty' => $input['hide_empty'] ?? false,
    );

    if ( ! empty( $input['search'] ) ) {
        $args['search'] = $input['search'];
    }

    // Polylang: filter by language slug if provided.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_get_term_language' ) ) {
        $args['lang'] = sanitize_key( $input['lang'] );
    }

    $tags = get_tags( $args );
    $result = array();

    foreach ( $tags as $tag ) {
        $result[] = array(
            'id'          => $tag->term_id,
            'name'        => $tag->name,
            'slug'        => $tag->slug,
            'description' => $tag->description,
            'count'       => $tag->count,
            'lang'        => function_exists( 'pll_get_term_language' ) ? (string) pll_get_term_language( $tag->term_id ) : '',
        );
    }

    return array( 'tags' => $result );
}

/**
 * Upload Media callback
 */
function wp_content_abilities_upload_media( $input ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $filename = sanitize_file_name( $input['filename'] );

    // Reject disallowed extensions before fetching any data.
    $ext_check = wp_check_filetype( $filename );
    if ( empty( $ext_check['type'] ) ) {
        return new WP_Error( 'invalid_filetype', 'Invalid file type.', array( 'status' => 400 ) );
    }

    $image_data = null;

    // Get image data from base64 or URL
    if ( ! empty( $input['base64'] ) ) {
        $image_data = base64_decode( $input['base64'] );
        if ( $image_data === false ) {
            return new WP_Error( 'invalid_base64', 'Invalid base64 data.', array( 'status' => 400 ) );
        }
    } elseif ( ! empty( $input['url'] ) ) {
        $url = $input['url'];

        // wp_http_validate_url() rejects non-http(s) schemes and malformed URLs.
        if ( ! wp_http_validate_url( $url ) ) {
            return new WP_Error( 'invalid_url', 'Invalid URL provided.', array( 'status' => 400 ) );
        }

        // wp_safe_remote_get() uses WordPress's own safe HTTP transport which
        // blocks requests to private/loopback/reserved IP ranges at the socket
        // level — after DNS resolution — eliminating SSRF and DNS-rebinding
        // risks that a pre-resolution gethostbyname() check cannot prevent.
        $response = wp_safe_remote_get( $url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'download_failed', 'Failed to download image: ' . $response->get_error_message(), array( 'status' => 400 ) );
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return new WP_Error( 'download_failed', sprintf( 'Remote returned HTTP %d.', $code ), array( 'status' => 400 ) );
        }
        $image_data = wp_remote_retrieve_body( $response );
    } else {
        return new WP_Error( 'no_image_data', 'Either base64 or url must be provided.', array( 'status' => 400 ) );
    }

    if ( empty( $image_data ) ) {
        return new WP_Error( 'empty_image', 'Image data is empty.', array( 'status' => 400 ) );
    }

    // Write to a temp file so wp_check_filetype_and_ext() can inspect actual content.
    $tmp_file = wp_tempnam( $filename );
    if ( false === file_put_contents( $tmp_file, $image_data ) ) {
        return new WP_Error( 'save_failed', 'Failed to save file.', array( 'status' => 500 ) );
    }

    // Content-aware MIME validation (checks magic bytes, not just extension).
    $filetype = wp_check_filetype_and_ext( $tmp_file, $filename );
    if ( empty( $filetype['type'] ) || empty( $filetype['ext'] ) ) {
        unlink( $tmp_file );
        return new WP_Error( 'invalid_filetype', 'Invalid or disallowed file type.', array( 'status' => 400 ) );
    }

    // If WP detected an extension mismatch (e.g. evil.jpg actually a PNG),
    // use the corrected filename so the file lands on disk with a name that
    // matches its real MIME type.
    if ( ! empty( $filetype['proper_filename'] ) ) {
        $filename = $filetype['proper_filename'];
    }

    // Get upload directory and move temp file into place.
    $upload_dir = wp_upload_dir();
    if ( $upload_dir['error'] ) {
        unlink( $tmp_file );
        return new WP_Error( 'upload_dir_error', $upload_dir['error'], array( 'status' => 500 ) );
    }

    $unique_filename = wp_unique_filename( $upload_dir['path'], $filename );
    $file_path       = $upload_dir['path'] . '/' . $unique_filename;

    if ( ! rename( $tmp_file, $file_path ) ) {
        unlink( $tmp_file );
        return new WP_Error( 'save_failed', 'Failed to move file to upload directory.', array( 'status' => 500 ) );
    }

    // Prepare attachment data
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => $input['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ),
        'post_content'   => $input['description'] ?? '',
        'post_excerpt'   => $input['caption'] ?? '',
        'post_status'    => 'inherit',
    );

    // Insert attachment
    $attach_id = wp_insert_attachment( $attachment, $file_path );
    if ( is_wp_error( $attach_id ) ) {
        unlink( $file_path );
        return $attach_id;
    }

    // Generate metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // Set alt text
    if ( ! empty( $input['alt_text'] ) ) {
        update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
    }

    // Polylang media translation (only when the feature is enabled in Polylang settings).
    $media_translation = function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( 'attachment' );
    if ( $media_translation ) {
        if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_post_language' ) ) {
            $lang = sanitize_key( $input['lang'] );
            pll_set_post_language( $attach_id, $lang );

            if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_post_translations' ) ) {
                $original_id  = (int) $input['translation_of'];
                $translations = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $original_id ) : array();
                $translations[ $lang ] = $attach_id;
                pll_save_post_translations( $translations );
            }
        }
    }

    $lang_out         = ( $media_translation && function_exists( 'pll_get_post_language' ) ) ? (string) pll_get_post_language( $attach_id ) : '';
    $translations_out = ( $media_translation && function_exists( 'pll_get_post_translations' ) ) ? pll_get_post_translations( $attach_id ) : (object) array();

    return array(
        'id'           => $attach_id,
        'url'          => wp_get_attachment_url( $attach_id ),
        'filename'     => $unique_filename,
        'title'        => get_the_title( $attach_id ),
        'mime_type'    => $filetype['type'],
        'lang'         => $lang_out,
        'translations' => $translations_out,
    );
}

/**
 * List Media callback
 */
function wp_content_abilities_list_media( $input ) {
    $args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => $input['per_page'] ?? 20,
        'paged'          => $input['page'] ?? 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( ! empty( $input['mime_type'] ) ) {
        $args['post_mime_type'] = $input['mime_type'];
    }

    if ( ! empty( $input['search'] ) ) {
        $args['s'] = $input['search'];
    }

    // Polylang: filter by language only when media translation is enabled.
    $media_translation = function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( 'attachment' );
    if ( $media_translation && ! empty( $input['lang'] ) && function_exists( 'pll_languages_list' ) ) {
        $args['lang'] = sanitize_key( $input['lang'] );
    }

    $query = new WP_Query( $args );
    $media = array();

    foreach ( $query->posts as $attachment ) {
        $lang         = ( $media_translation && function_exists( 'pll_get_post_language' ) ) ? (string) pll_get_post_language( $attachment->ID ) : '';
        $translations = ( $media_translation && function_exists( 'pll_get_post_translations' ) ) ? pll_get_post_translations( $attachment->ID ) : (object) array();
        $media[] = array(
            'id'           => $attachment->ID,
            'title'        => $attachment->post_title,
            'filename'     => basename( get_attached_file( $attachment->ID ) ),
            'url'          => wp_get_attachment_url( $attachment->ID ),
            'mime_type'    => $attachment->post_mime_type,
            'date'         => $attachment->post_date,
            'lang'         => $lang,
            'translations' => $translations,
        );
    }

    return array(
        'media'       => $media,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
    );
}

/**
 * List Languages callback (Polylang)
 */
function wp_content_abilities_list_languages( $input ) {
    if ( ! function_exists( 'pll_languages_list' ) ) {
        return array(
            'languages'       => array(),
            'polylang_active' => false,
        );
    }

    $default_lang = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
    $slugs        = pll_languages_list( array( 'fields' => 'slug' ) );
    $names        = pll_languages_list( array( 'fields' => 'name' ) );
    $locales      = pll_languages_list( array( 'fields' => 'locale' ) );
    $languages    = array();

    foreach ( $slugs as $i => $slug ) {
        $languages[] = array(
            'slug'    => $slug,
            'name'    => $names[ $i ] ?? $slug,
            'locale'  => $locales[ $i ] ?? '',
            'default' => ( $slug === $default_lang ),
        );
    }

    return array(
        'languages'                => $languages,
        'polylang_active'          => true,
        'media_translation_enabled' => function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( 'attachment' ),
    );
}

/**
 * Get Translation callback (Polylang)
 */
function wp_content_abilities_get_translation( $input ) {
    if ( ! function_exists( 'pll_get_post' ) ) {
        return new WP_Error( 'polylang_inactive', 'Polylang is not active.', array( 'status' => 503 ) );
    }

    $source_id   = (int) $input['id'];
    $target_lang = sanitize_key( $input['lang'] );

    $source_post = get_post( $source_id );
    if ( ! $source_post ) {
        return new WP_Error( 'not_found', 'Source post/page/media not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'read_post', $source_id ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to read this content.', array( 'status' => 403 ) );
    }

    $source_lang  = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $source_id ) : '';
    $all_trans    = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $source_id ) : array();
    $trans_id     = (int) pll_get_post( $source_id, $target_lang );

    if ( ! $trans_id ) {
        return array(
            'found'            => false,
            'id'               => 0,
            'url'              => '',
            'lang'             => $target_lang,
            'source_id'        => $source_id,
            'source_lang'      => $source_lang,
            'all_translations' => $all_trans ?: (object) array(),
        );
    }

    return array(
        'found'            => true,
        'id'               => $trans_id,
        'url'              => get_permalink( $trans_id ) ?: '',
        'lang'             => $target_lang,
        'source_id'        => $source_id,
        'source_lang'      => $source_lang,
        'all_translations' => $all_trans ?: (object) array(),
    );
}

/**
 * List Untranslated callback (Polylang)
 *
 * Scans posts in source_lang and filters to those with no translation in
 * target_lang. The scan is paginated against the source-language query so
 * the full table never has to fit in one request.
 */
function wp_content_abilities_list_untranslated( $input ) {
    if ( ! function_exists( 'pll_get_post' ) || ! function_exists( 'pll_languages_list' ) ) {
        return array(
            'untranslated'    => array(),
            'source_lang'     => $input['source_lang'] ?? '',
            'target_lang'     => $input['target_lang'] ?? '',
            'post_type'       => $input['post_type'] ?? 'post',
            'scanned'         => 0,
            'total_source'    => 0,
            'total_pages'     => 0,
            'polylang_active' => false,
        );
    }

    $source_lang = sanitize_key( $input['source_lang'] );
    $target_lang = sanitize_key( $input['target_lang'] );
    $post_type   = ( isset( $input['post_type'] ) && 'page' === $input['post_type'] ) ? 'page' : 'post';
    $requested   = $input['status'] ?? 'publish';
    $status      = wp_content_abilities_resolve_status( $requested, $post_type );

    $args = array(
        'post_type'      => $post_type,
        'post_status'    => $status,
        'posts_per_page' => $input['per_page'] ?? 20,
        'paged'          => $input['page'] ?? 1,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'lang'           => $source_lang,
    );

    $query        = new WP_Query( $args );
    $untranslated = array();

    foreach ( $query->posts as $post ) {
        $trans_id = (int) pll_get_post( $post->ID, $target_lang );
        if ( $trans_id ) {
            continue;
        }
        $untranslated[] = array(
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'slug'     => $post->post_name,
            'status'   => $post->post_status,
            'modified' => $post->post_modified,
            'url'      => get_permalink( $post->ID ) ?: '',
        );
    }

    return array(
        'untranslated'    => $untranslated,
        'source_lang'     => $source_lang,
        'target_lang'     => $target_lang,
        'post_type'       => $post_type,
        'scanned'         => count( $query->posts ),
        'total_source'    => (int) $query->found_posts,
        'total_pages'     => (int) $query->max_num_pages,
        'polylang_active' => true,
    );
}

/**
 * List Revisions callback
 */
function wp_content_abilities_list_revisions( $input ) {
    $parent = get_post( $input['id'] );
    if ( ! $parent || ! in_array( $parent->post_type, array( 'post', 'page' ), true ) ) {
        return new WP_Error( 'not_found', 'Post or page not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'edit_post', $parent->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to view revisions of this content.', array( 'status' => 403 ) );
    }

    $per_page  = $input['per_page'] ?? 20;
    $revisions = wp_get_post_revisions(
        $parent->ID,
        array(
            'posts_per_page' => $per_page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );

    $out = array();
    foreach ( $revisions as $rev ) {
        $author = get_userdata( $rev->post_author );
        $out[]  = array(
            'id'          => (int) $rev->ID,
            'parent_id'   => (int) $rev->post_parent,
            'author'      => (int) $rev->post_author,
            'author_name' => $author ? $author->display_name : '',
            'date'        => $rev->post_date,
            'modified'    => $rev->post_modified,
            'title'       => $rev->post_title,
            'is_autosave' => wp_is_post_autosave( $rev ) ? true : false,
        );
    }

    return array(
        'revisions' => $out,
        'total'     => count( $out ),
    );
}

/**
 * Restore Revision callback
 */
function wp_content_abilities_restore_revision( $input ) {
    $parent = get_post( $input['id'] );
    if ( ! $parent || ! in_array( $parent->post_type, array( 'post', 'page' ), true ) ) {
        return new WP_Error( 'not_found', 'Post or page not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'edit_post', $parent->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to edit this content.', array( 'status' => 403 ) );
    }

    $revision = wp_get_post_revision( $input['revision_id'] );
    if ( ! $revision || (int) $revision->post_parent !== (int) $parent->ID ) {
        return new WP_Error( 'invalid_revision', 'Revision does not belong to the given parent.', array( 'status' => 400 ) );
    }

    $result = wp_restore_post_revision( $revision->ID );
    if ( ! $result || is_wp_error( $result ) ) {
        return is_wp_error( $result )
            ? $result
            : new WP_Error( 'restore_failed', 'Failed to restore revision.', array( 'status' => 500 ) );
    }

    $restored = get_post( $parent->ID );

    return array(
        'id'          => (int) $parent->ID,
        'revision_id' => (int) $revision->ID,
        'restored'    => true,
        'modified'    => $restored ? $restored->post_modified : '',
        'url'         => get_permalink( $parent->ID ) ?: '',
    );
}

/**
 * Internal helper: insert a taxonomy term with optional Polylang linkage.
 *
 * @param string $taxonomy 'category' or 'post_tag'.
 * @param array  $input    Sanitized ability input.
 * @return array|WP_Error  Term row for output, or WP_Error.
 */
function wp_content_abilities_insert_term( $taxonomy, $input ) {
    $name = isset( $input['name'] ) ? wp_strip_all_tags( (string) $input['name'] ) : '';
    if ( '' === trim( $name ) ) {
        return new WP_Error( 'invalid_name', 'Term name is required.', array( 'status' => 400 ) );
    }

    $args = array();
    if ( ! empty( $input['slug'] ) ) {
        $args['slug'] = sanitize_title( $input['slug'] );
    }
    if ( isset( $input['description'] ) ) {
        $args['description'] = wp_kses_post( $input['description'] );
    }
    if ( 'category' === $taxonomy && isset( $input['parent'] ) ) {
        $parent_id = (int) $input['parent'];
        if ( $parent_id > 0 ) {
            $parent_term = get_term( $parent_id, 'category' );
            if ( ! $parent_term || is_wp_error( $parent_term ) ) {
                return new WP_Error( 'invalid_parent', 'Parent category does not exist.', array( 'status' => 400 ) );
            }
        }
        $args['parent'] = max( 0, $parent_id );
    }

    $result = wp_insert_term( $name, $taxonomy, $args );
    if ( is_wp_error( $result ) ) {
        return $result;
    }

    $term_id = (int) $result['term_id'];

    // Polylang: assign language and link translation.
    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_term_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_term_language( $term_id, $lang );

        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_term_translations' ) && function_exists( 'pll_get_term_translations' ) ) {
            $source_id = (int) $input['translation_of'];
            $existing  = pll_get_term_translations( $source_id );
            if ( ! is_array( $existing ) ) {
                $existing = array();
            }
            $existing[ $lang ] = $term_id;
            pll_save_term_translations( $existing );
        }
    }

    return wp_content_abilities_format_term_output( $term_id, $taxonomy );
}

/**
 * Internal helper: format a term as ability output (with translations map).
 */
function wp_content_abilities_format_term_output( $term_id, $taxonomy ) {
    $term = get_term( $term_id, $taxonomy );
    if ( ! $term || is_wp_error( $term ) ) {
        return new WP_Error( 'not_found', 'Term not found after save.', array( 'status' => 500 ) );
    }

    $translations = function_exists( 'pll_get_term_translations' )
        ? pll_get_term_translations( $term_id )
        : array();
    if ( ! is_array( $translations ) ) {
        $translations = array();
    }

    $out = array(
        'id'           => (int) $term->term_id,
        'name'         => $term->name,
        'slug'         => $term->slug,
        'description'  => $term->description,
        'lang'         => function_exists( 'pll_get_term_language' ) ? (string) pll_get_term_language( $term_id ) : '',
        'translations' => empty( $translations ) ? (object) array() : $translations,
    );

    if ( 'category' === $taxonomy ) {
        $out['parent'] = (int) $term->parent;
    }

    return $out;
}

/**
 * Create Category callback
 */
function wp_content_abilities_create_category( $input ) {
    return wp_content_abilities_insert_term( 'category', $input );
}

/**
 * Update Category callback
 */
function wp_content_abilities_update_category( $input ) {
    $term_id = (int) $input['id'];
    $term    = get_term( $term_id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        return new WP_Error( 'not_found', 'Category not found.', array( 'status' => 404 ) );
    }

    $args = array();
    if ( isset( $input['name'] ) ) {
        $name = wp_strip_all_tags( (string) $input['name'] );
        if ( '' === trim( $name ) ) {
            return new WP_Error( 'invalid_name', 'Category name cannot be empty.', array( 'status' => 400 ) );
        }
        $args['name'] = $name;
    }
    if ( isset( $input['slug'] ) ) {
        $args['slug'] = sanitize_title( $input['slug'] );
    }
    if ( isset( $input['description'] ) ) {
        $args['description'] = wp_kses_post( $input['description'] );
    }
    if ( isset( $input['parent'] ) ) {
        $parent_id = (int) $input['parent'];
        if ( $parent_id === $term_id ) {
            return new WP_Error( 'invalid_parent', 'A category cannot be its own parent.', array( 'status' => 400 ) );
        }
        if ( $parent_id > 0 ) {
            $parent_term = get_term( $parent_id, 'category' );
            if ( ! $parent_term || is_wp_error( $parent_term ) ) {
                return new WP_Error( 'invalid_parent', 'Parent category does not exist.', array( 'status' => 400 ) );
            }
            // Prevent cycles: walk ancestors of proposed parent.
            $ancestors = get_ancestors( $parent_id, 'category', 'taxonomy' );
            if ( in_array( $term_id, array_map( 'intval', $ancestors ), true ) ) {
                return new WP_Error( 'invalid_parent', 'Parent assignment would create a cycle.', array( 'status' => 400 ) );
            }
        }
        $args['parent'] = max( 0, $parent_id );
    }

    if ( ! empty( $args ) ) {
        $result = wp_update_term( $term_id, 'category', $args );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
    }

    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_term_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_term_language( $term_id, $lang );

        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_term_translations' ) && function_exists( 'pll_get_term_translations' ) ) {
            $source_id = (int) $input['translation_of'];
            $existing  = pll_get_term_translations( $source_id );
            if ( ! is_array( $existing ) ) {
                $existing = array();
            }
            $existing[ $lang ] = $term_id;
            pll_save_term_translations( $existing );
        }
    }

    return wp_content_abilities_format_term_output( $term_id, 'category' );
}

/**
 * Delete Category callback
 */
function wp_content_abilities_delete_category( $input ) {
    $term_id = (int) $input['id'];
    $term    = get_term( $term_id, 'category' );
    if ( ! $term || is_wp_error( $term ) ) {
        return new WP_Error( 'not_found', 'Category not found.', array( 'status' => 404 ) );
    }

    $default = (int) get_option( 'default_category' );
    if ( $default && $term_id === $default ) {
        return new WP_Error( 'forbidden', 'Cannot delete the default category.', array( 'status' => 403 ) );
    }

    $name   = $term->name;
    $result = wp_delete_term( $term_id, 'category' );
    if ( is_wp_error( $result ) ) {
        return $result;
    }
    if ( false === $result || 0 === $result ) {
        return new WP_Error( 'delete_failed', 'Failed to delete category.', array( 'status' => 500 ) );
    }

    return array(
        'id'      => $term_id,
        'deleted' => true,
        'name'    => $name,
    );
}

/**
 * Create Tag callback
 */
function wp_content_abilities_create_tag( $input ) {
    return wp_content_abilities_insert_term( 'post_tag', $input );
}

/**
 * Update Tag callback
 */
function wp_content_abilities_update_tag( $input ) {
    $term_id = (int) $input['id'];
    $term    = get_term( $term_id, 'post_tag' );
    if ( ! $term || is_wp_error( $term ) ) {
        return new WP_Error( 'not_found', 'Tag not found.', array( 'status' => 404 ) );
    }

    $args = array();
    if ( isset( $input['name'] ) ) {
        $name = wp_strip_all_tags( (string) $input['name'] );
        if ( '' === trim( $name ) ) {
            return new WP_Error( 'invalid_name', 'Tag name cannot be empty.', array( 'status' => 400 ) );
        }
        $args['name'] = $name;
    }
    if ( isset( $input['slug'] ) ) {
        $args['slug'] = sanitize_title( $input['slug'] );
    }
    if ( isset( $input['description'] ) ) {
        $args['description'] = wp_kses_post( $input['description'] );
    }

    if ( ! empty( $args ) ) {
        $result = wp_update_term( $term_id, 'post_tag', $args );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
    }

    if ( ! empty( $input['lang'] ) && function_exists( 'pll_set_term_language' ) ) {
        $lang = sanitize_key( $input['lang'] );
        pll_set_term_language( $term_id, $lang );

        if ( ! empty( $input['translation_of'] ) && function_exists( 'pll_save_term_translations' ) && function_exists( 'pll_get_term_translations' ) ) {
            $source_id = (int) $input['translation_of'];
            $existing  = pll_get_term_translations( $source_id );
            if ( ! is_array( $existing ) ) {
                $existing = array();
            }
            $existing[ $lang ] = $term_id;
            pll_save_term_translations( $existing );
        }
    }

    return wp_content_abilities_format_term_output( $term_id, 'post_tag' );
}

/**
 * Delete Tag callback
 */
function wp_content_abilities_delete_tag( $input ) {
    $term_id = (int) $input['id'];
    $term    = get_term( $term_id, 'post_tag' );
    if ( ! $term || is_wp_error( $term ) ) {
        return new WP_Error( 'not_found', 'Tag not found.', array( 'status' => 404 ) );
    }

    $name   = $term->name;
    $result = wp_delete_term( $term_id, 'post_tag' );
    if ( is_wp_error( $result ) ) {
        return $result;
    }
    if ( false === $result || 0 === $result ) {
        return new WP_Error( 'delete_failed', 'Failed to delete tag.', array( 'status' => 500 ) );
    }

    return array(
        'id'      => $term_id,
        'deleted' => true,
        'name'    => $name,
    );
}

/**
 * Internal helper: bulk-update implementation shared by post and page abilities.
 *
 * @param array  $input     Sanitized ability input.
 * @param string $post_type 'post' or 'page'.
 * @return array            { results, success_count, failure_count }.
 */
function wp_content_abilities_bulk_update( $input, $post_type ) {
    $ids     = array_values( array_unique( array_map( 'intval', (array) $input['ids'] ) ) );
    $results = array();
    $success = 0;
    $failure = 0;

    // Resolve any add_categories slugs to IDs once, before the loop.
    $category_ids_to_add = array();
    if ( 'post' === $post_type && ! empty( $input['add_categories'] ) ) {
        foreach ( (array) $input['add_categories'] as $slug ) {
            $cat = get_category_by_slug( sanitize_title( $slug ) );
            if ( $cat ) {
                $category_ids_to_add[] = (int) $cat->term_id;
            }
        }
    }

    foreach ( $ids as $id ) {
        if ( $id <= 0 ) {
            $results[] = array( 'id' => $id, 'success' => false, 'error' => 'Invalid ID.' );
            $failure++;
            continue;
        }

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== $post_type ) {
            $results[] = array( 'id' => $id, 'success' => false, 'error' => 'Not found.' );
            $failure++;
            continue;
        }

        if ( ! current_user_can( 'edit_post', $id ) ) {
            $results[] = array( 'id' => $id, 'success' => false, 'error' => 'Forbidden.' );
            $failure++;
            continue;
        }

        if ( isset( $input['status'] ) ) {
            $cap_check = wp_content_abilities_check_status_cap( $input['status'], $post_type );
            if ( is_wp_error( $cap_check ) ) {
                $results[] = array( 'id' => $id, 'success' => false, 'error' => $cap_check->get_error_message() );
                $failure++;
                continue;
            }
        }

        $post_data = array( 'ID' => $id );
        if ( isset( $input['status'] ) ) {
            $post_data['post_status'] = $input['status'];
        }
        if ( isset( $input['comment_status'] ) ) {
            $post_data['comment_status'] = $input['comment_status'];
        }
        if ( isset( $input['ping_status'] ) ) {
            $post_data['ping_status'] = $input['ping_status'];
        }
        if ( 'page' === $post_type && isset( $input['parent'] ) ) {
            $parent_id = (int) $input['parent'];
            if ( $parent_id > 0 ) {
                if ( $parent_id === $id ) {
                    $results[] = array( 'id' => $id, 'success' => false, 'error' => 'A page cannot be its own parent.' );
                    $failure++;
                    continue;
                }
                $parent_post = get_post( $parent_id );
                if ( ! $parent_post || 'page' !== $parent_post->post_type ) {
                    $results[] = array( 'id' => $id, 'success' => false, 'error' => 'Parent page not found.' );
                    $failure++;
                    continue;
                }
                $ancestors = get_post_ancestors( $parent_id );
                if ( in_array( $id, array_map( 'intval', $ancestors ), true ) ) {
                    $results[] = array( 'id' => $id, 'success' => false, 'error' => 'Parent assignment would create a cycle.' );
                    $failure++;
                    continue;
                }
            }
            $post_data['post_parent'] = max( 0, $parent_id );
        }

        if ( count( $post_data ) > 1 ) {
            $update = wp_update_post( $post_data, true );
            if ( is_wp_error( $update ) ) {
                $results[] = array( 'id' => $id, 'success' => false, 'error' => $update->get_error_message() );
                $failure++;
                continue;
            }
        }

        // Posts only: sticky, add_categories, add_tags.
        if ( 'post' === $post_type ) {
            if ( isset( $input['sticky'] ) ) {
                if ( true === $input['sticky'] ) {
                    stick_post( $id );
                } else {
                    unstick_post( $id );
                }
            }
            if ( ! empty( $category_ids_to_add ) ) {
                wp_set_post_categories( $id, $category_ids_to_add, true );
            }
            if ( ! empty( $input['add_tags'] ) ) {
                wp_set_post_tags( $id, (array) $input['add_tags'], true );
            }
        }

        $results[] = array( 'id' => $id, 'success' => true );
        $success++;
    }

    return array(
        'results'       => $results,
        'success_count' => $success,
        'failure_count' => $failure,
    );
}

/**
 * Internal helper: bulk-delete implementation shared by post and page abilities.
 *
 * @param array  $input     Sanitized ability input.
 * @param string $post_type 'post' or 'page'.
 * @return array            { results, success_count, failure_count }.
 */
function wp_content_abilities_bulk_delete( $input, $post_type ) {
    $ids     = array_values( array_unique( array_map( 'intval', (array) $input['ids'] ) ) );
    $force   = ! empty( $input['force'] );
    $results = array();
    $success = 0;
    $failure = 0;

    foreach ( $ids as $id ) {
        if ( $id <= 0 ) {
            $results[] = array( 'id' => $id, 'success' => false, 'trashed' => false, 'error' => 'Invalid ID.' );
            $failure++;
            continue;
        }

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== $post_type ) {
            $results[] = array( 'id' => $id, 'success' => false, 'trashed' => false, 'error' => 'Not found.' );
            $failure++;
            continue;
        }

        if ( ! current_user_can( 'delete_post', $id ) ) {
            $results[] = array( 'id' => $id, 'success' => false, 'trashed' => false, 'error' => 'Forbidden.' );
            $failure++;
            continue;
        }

        $deleted = wp_delete_post( $id, $force );
        if ( ! $deleted ) {
            $results[] = array( 'id' => $id, 'success' => false, 'trashed' => false, 'error' => 'Delete failed.' );
            $failure++;
            continue;
        }

        $trashed = ! $force && EMPTY_TRASH_DAYS && 'trash' === get_post_status( $id );
        $results[] = array( 'id' => $id, 'success' => true, 'trashed' => (bool) $trashed );
        $success++;
    }

    return array(
        'results'       => $results,
        'success_count' => $success,
        'failure_count' => $failure,
    );
}

/**
 * Bulk Update Posts callback
 */
function wp_content_abilities_bulk_update_posts( $input ) {
    return wp_content_abilities_bulk_update( $input, 'post' );
}

/**
 * Bulk Update Pages callback
 */
function wp_content_abilities_bulk_update_pages( $input ) {
    return wp_content_abilities_bulk_update( $input, 'page' );
}

/**
 * Bulk Delete Posts callback
 */
function wp_content_abilities_bulk_delete_posts( $input ) {
    return wp_content_abilities_bulk_delete( $input, 'post' );
}

/**
 * Bulk Delete Pages callback
 */
function wp_content_abilities_bulk_delete_pages( $input ) {
    return wp_content_abilities_bulk_delete( $input, 'page' );
}
