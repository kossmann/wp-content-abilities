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
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_get_post',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
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
                    'description' => 'Publish date (ISO 8601 format). For scheduled posts, use status=future.',
                ),
                'featured_image_id' => array(
                    'type'        => 'integer',
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
                    'description' => 'Post ID of the original post this is a translation of. Requires Polylang. Automatically links this post as a translation and maps categories to their translated equivalents.',
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
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_create_post',
        'permission_callback' => function() {
            return current_user_can( 'publish_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
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
                    'description' => 'New publish date (ISO 8601 format).',
                ),
                'featured_image_id' => array(
                    'type'        => 'integer',
                    'description' => 'Media library ID for featured image. Use 0 to remove.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Change the post language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'description' => 'Post ID of the original post this is a translation of. Requires Polylang. Links this post into the translation group of the given post.',
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
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_update_post',
        'permission_callback' => function() {
            return current_user_can( 'edit_posts' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => true,
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
                    'enum'        => array( 'publish', 'draft', 'pending', 'private', 'any' ),
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
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_get_page',
        'permission_callback' => function() {
            return current_user_can( 'read' );
        },
        'meta' => array(
            'show_in_rest' => true,
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
                    'description' => 'Parent page ID for hierarchical pages.',
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
                    'description' => 'Media library ID for featured image.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Language slug for the page (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'description' => 'Page ID of the original page this is a translation of. Requires Polylang.',
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
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_create_page',
        'permission_callback' => function() {
            return current_user_can( 'publish_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
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
                    'description' => 'New parent page ID.',
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
                    'description' => 'Media library ID for featured image. Use 0 to remove.',
                ),
                'lang' => array(
                    'type'        => 'string',
                    'maxLength'   => 10,
                    'description' => 'Change the page language slug (e.g. "en", "pt"). Requires Polylang.',
                ),
                'translation_of' => array(
                    'type'        => 'integer',
                    'description' => 'Page ID of the original page this is a translation of. Requires Polylang.',
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
            ),
        ),
        'execute_callback'    => 'wp_content_abilities_update_page',
        'permission_callback' => function() {
            return current_user_can( 'edit_pages' );
        },
        'meta' => array(
            'show_in_rest' => true,
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => false,
                'destructive' => false,
                'idempotent'  => true,
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
            'type'       => 'object',
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
            'type'       => 'object',
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
        'input_schema' => array(
            'type'                 => 'object',
            'properties'           => (object) array(),
            'additionalProperties' => false,
        ),
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
            'mcp'          => array( 'public' => true, 'type' => 'tool' ),
            'annotations'  => array(
                'readonly'    => true,
                'destructive' => false,
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
        '/<!-- wp:[a-z\/][^\n]*? -->/i',
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
 * Uses WP core parse_blocks() + serialize_blocks() to re-serialise all block
 * attributes. This ensures that & in URLs is stored as \u0026 (the encoding
 * the block editor expects) regardless of how the AI encoded it when it sent
 * the content back.
 *
 * Falls back silently to the original string if the WP functions are absent
 * (< WP 5.0) or if the content contains no block markers.
 *
 * @param string $content Content from AI input.
 * @return string Normalised content safe to pass to wp_insert_post / wp_update_post.
 */
function wp_content_abilities_normalize_block_json( $content ) {
    if ( $content === '' || strpos( $content, '<!-- wp:' ) === false ) {
        return $content;
    }
    if ( ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_blocks' ) ) {
        return $content;
    }
    return serialize_blocks( parse_blocks( $content ) );
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

    if ( ! $post || $post->post_type !== 'post' ) {
        return new WP_Error( 'not_found', 'Post not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'read_post', $post->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to read this post.', array( 'status' => 403 ) );
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
    );
}

/**
 * Create Post callback
 */
function wp_content_abilities_create_post( $input ) {
    $post_data = array(
        'post_type'      => 'post',
        'post_title'     => $input['title'],
        'post_content'   => wp_content_abilities_normalize_block_json( $input['content'] ?? '' ),
        'post_excerpt'   => $input['excerpt'] ?? '',
        'post_status'    => $input['status'] ?? 'draft',
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

    $result = wp_update_post( $post_data, true );

    if ( is_wp_error( $result ) ) {
        return $result;
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

    if ( ! $post || $post->post_type !== 'page' ) {
        return new WP_Error( 'not_found', 'Page not found.', array( 'status' => 404 ) );
    }

    if ( ! current_user_can( 'read_post', $post->ID ) ) {
        return new WP_Error( 'forbidden', 'You do not have permission to read this page.', array( 'status' => 403 ) );
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
    );
}

/**
 * Create Page callback
 */
function wp_content_abilities_create_page( $input ) {
    $post_data = array(
        'post_type'    => 'page',
        'post_title'   => $input['title'],
        'post_content' => wp_content_abilities_normalize_block_json( $input['content'] ?? '' ),
        'post_excerpt' => $input['excerpt'] ?? '',
        'post_status'  => $input['status'] ?? 'draft',
        'post_name'    => $input['slug'] ?? '',
        'post_parent'  => $input['parent'] ?? 0,
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
        $post_data['post_parent'] = $input['parent'];
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
        update_post_meta( $attach_id, '_wp_attachment_image_alt', $input['alt_text'] );
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
