<?php
/**
 * Plugin Name: Block Archives Post Type
 * Description: Adds a post type setting to the core Archives block.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * Text Domain: block-archives-post-type
 * Domain Path: /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-cpt-date-archive-urls.php';

if (! defined('BLOCK_ARCHIVES_POST_TYPE_PLUGIN_FILE')) {
    define('BLOCK_ARCHIVES_POST_TYPE_PLUGIN_FILE', __FILE__);
}

const BLOCK_ARCHIVES_POST_TYPE_ATTRIBUTE = 'postType';

add_filter('register_block_type_args', static function (array $args, string $block_type): array {
    if ('core/archives' !== $block_type) {
        return $args;
    }

    if (! isset($args['attributes']) || ! is_array($args['attributes'])) {
        $args['attributes'] = [];
    }

    $args['attributes'][BLOCK_ARCHIVES_POST_TYPE_ATTRIBUTE] = [
        'type' => 'string',
        'default' => 'post',
    ];

    return $args;
}, 10, 2);

add_filter('render_block_core/archives', static function (string $block_content, array $parsed_block, WP_Block $block): string {
    $attributes = $block->attributes ?? ($parsed_block['attrs'] ?? []);
    $post_type = $attributes[BLOCK_ARCHIVES_POST_TYPE_ATTRIBUTE] ?? 'post';

    if (! is_string($post_type) || 'post' === $post_type) {
        return $block_content;
    }

    if (! post_type_exists($post_type)) {
        return $block_content;
    }

    if (! function_exists('render_block_core_archives')) {
        return $block_content;
    }

    $filter = static function (array $args) use ($post_type): array {
        $args['post_type'] = $post_type;

        return $args;
    };

    add_filter('widget_archives_args', $filter, 10, 1);
    add_filter('widget_archives_dropdown_args', $filter, 10, 1);

    $content = render_block_core_archives($attributes);

    remove_filter('widget_archives_args', $filter, 10);
    remove_filter('widget_archives_dropdown_args', $filter, 10);

    return is_string($content) ? $content : $block_content;
}, 10, 3);

add_action('enqueue_block_editor_assets', static function (): void {
    $asset_file = __DIR__ . '/build/index.tsx.asset.php';
    $script_file = __DIR__ . '/build/index.tsx.js';

    if (! is_readable($asset_file) || ! is_readable($script_file)) {
        return;
    }

    $asset = require $asset_file;

    wp_enqueue_script(
        'block-archives-post-type-editor',
        plugins_url('build/index.tsx.js', __FILE__),
        $asset['dependencies'] ?? [],
        $asset['version'] ?? filemtime($script_file),
        true,
    );
});

Block_Archives_Cpt_Date_Archive_Urls::register();

register_activation_hook(__FILE__, [Block_Archives_Cpt_Date_Archive_Urls::class, 'activate']);
register_deactivation_hook(__FILE__, [Block_Archives_Cpt_Date_Archive_Urls::class, 'deactivate']);
