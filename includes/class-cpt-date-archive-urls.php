<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Pretty CPT date archive URLs: {post-type-archive}/{year}/[/month/[/day/]]
 */
final class Block_Archives_Cpt_Date_Archive_Urls {

    public static function register(): void {
        add_action('init', [self::class, 'load_textdomain'], 0);
        add_action('init', [self::class, 'localize_pagination_base'], 9);
        add_action('init', [self::class, 'add_rewrite_rules'], 20);
        add_filter('get_archives_link', [self::class, 'filter_archives_link'], 10, 2);
        add_action('template_redirect', [self::class, 'redirect_legacy_date_archive_urls'], 1);
    }

    public static function load_textdomain(): void {
        load_plugin_textdomain(
            'block-archives-post-type',
            false,
            dirname(plugin_basename(BLOCK_ARCHIVES_POST_TYPE_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * URL slug for /…/{slug}/{n}/ pagination (same as core $wp_rewrite->pagination_base).
     */
    public static function get_pagination_base(): string {
        global $wp_rewrite;

        if ($wp_rewrite instanceof WP_Rewrite && is_string($wp_rewrite->pagination_base) && $wp_rewrite->pagination_base !== '') {
            return $wp_rewrite->pagination_base;
        }

        return self::default_pagination_base();
    }

    public static function default_pagination_base(): string {
        $slug = apply_filters(
            'block_archives_post_type_pagination_base',
            _x('page', 'URL slug for archive pagination', 'block-archives-post-type')
        );

        return sanitize_title((string) $slug);
    }

    /**
     * Use a translatable pagination slug when permalinks still use the core default "page".
     */
    public static function localize_pagination_base(): void {
        global $wp_rewrite;

        if (! $wp_rewrite instanceof WP_Rewrite) {
            return;
        }

        if ($wp_rewrite->pagination_base !== 'page') {
            return;
        }

        $wp_rewrite->pagination_base = self::default_pagination_base();
    }

    public static function activate(): void {
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function add_rewrite_rules(): void {
        $pagination = preg_quote(self::get_pagination_base(), '#');

        foreach (self::get_archivable_post_types() as $post_type) {
            $archive_path = self::get_archive_path($post_type);

            if ($archive_path === null) {
                continue;
            }

            $quoted = preg_quote($archive_path, '#');

            add_rewrite_rule(
                '^' . $quoted . '/([0-9]{4})/' . $pagination . '/([0-9]+)/?$',
                'index.php?post_type=' . $post_type . '&year=$matches[1]&paged=$matches[2]',
                'top'
            );

            add_rewrite_rule(
                '^' . $quoted . '/([0-9]{4})/([0-9]{1,2})/' . $pagination . '/([0-9]+)/?$',
                'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]',
                'top'
            );

            add_rewrite_rule(
                '^' . $quoted . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/' . $pagination . '/([0-9]+)/?$',
                'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]',
                'top'
            );

            add_rewrite_rule(
                '^' . $quoted . '/([0-9]{4})/?$',
                'index.php?post_type=' . $post_type . '&year=$matches[1]',
                'top'
            );

            add_rewrite_rule(
                '^' . $quoted . '/([0-9]{4})/([0-9]{1,2})/?$',
                'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]',
                'top'
            );

            add_rewrite_rule(
                '^' . $quoted . '/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$',
                'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]',
                'top'
            );
        }
    }

    /**
     * @return list<string>
     */
    private static function get_archivable_post_types(): array {
        $types = get_post_types(['public' => true], 'names');
        $out = [];

        foreach ($types as $post_type) {
            if (in_array($post_type, ['post', 'attachment'], true)) {
                continue;
            }

            $object = get_post_type_object($post_type);

            if ($object && ! empty($object->has_archive)) {
                $out[] = $post_type;
            }
        }

        return $out;
    }

    public static function get_archive_path(string $post_type): ?string {
        $link = get_post_type_archive_link($post_type);

        if (! $link) {
            return null;
        }

        $path = wp_parse_url($link, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $path = trim($path, '/');
        $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

        if ($home_path !== '' && str_starts_with($path, $home_path . '/')) {
            $path = trim(substr($path, strlen($home_path)), '/');
        } elseif ($home_path !== '' && $path === $home_path) {
            $path = '';
        }

        return $path !== '' ? $path : null;
    }

    public static function build_date_archive_url(
        string $post_type,
        int $year,
        ?int $month = null,
        ?int $day = null,
        ?int $paged = null
    ): ?string {
        $base = get_post_type_archive_link($post_type);

        if (! $base) {
            return null;
        }

        $url = trailingslashit($base) . $year . '/';

        if ($month !== null && $month > 0) {
            $url .= sprintf('%02d', $month) . '/';
        }

        if ($day !== null && $day > 0) {
            $url .= sprintf('%02d', $day) . '/';
        }

        if ($paged !== null && $paged > 1) {
            $pagination_base = self::get_pagination_base();

            $url = user_trailingslashit($url, 'paged') . $pagination_base . '/' . $paged . '/';
        }

        return $url;
    }

    public static function maybe_replace_archive_url(string $url): string {
        $parts = wp_parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        $post_type = $query['post_type'] ?? '';

        if (! is_string($post_type) || $post_type === '' || $post_type === 'post') {
            return $url;
        }

        if (! get_post_type_object($post_type)?->has_archive) {
            return $url;
        }

        $path = trim($parts['path'] ?? '', '/');
        $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

        if ($home_path !== '' && str_starts_with($path, $home_path . '/')) {
            $path = trim(substr($path, strlen($home_path)), '/');
        } elseif ($home_path !== '' && $path === $home_path) {
            $path = '';
        }

        if (! preg_match('#^(\d{4})(?:/(\d{1,2}))?(?:/(\d{1,2}))?/?$#', $path, $matches)) {
            return $url;
        }

        $year = (int) $matches[1];
        $month = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : null;
        $day = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null;

        $pretty = self::build_date_archive_url($post_type, $year, $month, $day);

        return $pretty ?? $url;
    }

    public static function filter_archives_link(string $link_html, string $url): string {
        $new_url = self::maybe_replace_archive_url($url);

        if ($new_url === $url) {
            return $link_html;
        }

        $escaped_old = esc_url($url);
        $escaped_new = esc_url($new_url);

        return str_replace(
            ["href='{$escaped_old}'", 'href="' . $escaped_old . '"'],
            ["href='{$escaped_new}'", 'href="' . $escaped_new . '"'],
            $link_html
        );
    }

    public static function redirect_legacy_date_archive_urls(): void {
        if (is_admin()) {
            return;
        }

        $post_type = get_query_var('post_type');

        if (! is_string($post_type) || $post_type === '' || $post_type === 'post') {
            return;
        }

        if (! is_date()) {
            return;
        }

        $year = (int) get_query_var('year');

        if ($year < 1) {
            return;
        }

        $month = (int) get_query_var('monthnum');
        $day = (int) get_query_var('day');
        $paged = max(1, (int) get_query_var('paged'));

        $pretty = self::build_date_archive_url(
            $post_type,
            $year,
            $month > 0 ? $month : null,
            $day > 0 ? $day : null,
            $paged
        );

        if ($pretty === null) {
            return;
        }

        $archive_path = self::get_archive_path($post_type);

        if ($archive_path === null) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $request_path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');
        $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

        if ($home_path !== '' && str_starts_with($request_path, $home_path . '/')) {
            $request_path = trim(substr($request_path, strlen($home_path)), '/');
        } elseif ($home_path !== '' && $request_path === $home_path) {
            $request_path = '';
        }

        if (str_starts_with($request_path, $archive_path . '/')) {
            return;
        }

        $pretty_path = trim((string) wp_parse_url($pretty, PHP_URL_PATH), '/');

        if ($home_path !== '' && str_starts_with($pretty_path, $home_path . '/')) {
            $pretty_path = trim(substr($pretty_path, strlen($home_path)), '/');
        }

        if ($request_path === $pretty_path) {
            return;
        }

        wp_safe_redirect($pretty, 301);
        exit;
    }
}
