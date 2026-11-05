# Block Archives Post Type

Adds a **Post type** control to the core **Archives** block so date archive links can list a custom post type instead of posts only.

## Features

- Inspector control to pick any public, viewable post type.
- Persists the `postType` block attribute in saved content.
- Re-renders archives on the frontend (and in the editor preview) via `widget_archives_args`.
- Editor script loads only through `enqueue_block_editor_assets`.

## Requirements

- WordPress 6.4 or newer
- PHP 8.1 or newer

## Installation

1. Place the plugin in `wp-content/plugins/block-archives-post-type`.
2. Run `npm install` and `npm run build` if built assets are not included.
3. Activate **Block Archives Post Type** in the Plugins screen.

## Development

```bash
npm install
npm run build
```

Watch mode:

```bash
npm start
```

Built files are written to `build/`.

After install or update, deactivate and reactivate the plugin once (or run `wp rewrite flush`) so date archive rewrite rules are registered.

## Block attribute

- `postType` (string, default `post`) — passed to `wp_get_archives()` as `post_type`.

Custom post types need sensible date archives and rewrite rules; otherwise links may be empty or incorrect.

For non-`post` types, archive links use `{post-type-archive}/{year}/` (and `/month/`, `/day/` when applicable) instead of `/2026/?post_type=…`. Paginated views use `{post-type-archive}/{year}/{pagination-base}/{n}/`. By default the pagination base is the translatable string with context `URL slug for archive pagination` (English msgid: `page`, e.g. German `seite`). Override with the filter `block_archives_post_type_pagination_base`, or set a custom pagination base under **Settings → Permalinks** (that value is kept as-is). Legacy URLs redirect with 301.

## License

GPL-2.0-or-later
