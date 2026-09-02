---
cli_token: route
aliases: [Router, Routes, rewrite, pretty URL, query var]
include_when:
  - Custom front-end URLs that are not a native CPT/taxonomy archive
  - Mapping internal query vars to public slugs
  - Building links/redirects to those custom pages
do_not_include_when:
  - Only using WP REST API or admin-ajax
  - Only native permalinks (post, page, CPT registered with rewrite)
  - Only Gutenberg blocks / shortcodes / cron
depends_on: []
often_with: [controller]
copied_files:
  psr4: [classes/Routes/Router.php]
  legacy: [classes/routes/router.php]
boilerplate_class: BEA\PB\Routes\Router
---

# Component: route (Router)

## Intent

Register a dictionary of **internal keys → public URL slugs**, then generate `home_url()` links from those keys. Use it when the plugin owns custom rewritten pages (account, wizard, dashboard) that WordPress does not create by itself.

This is not a REST router and not a replacement for `add_rewrite_rule()`. Rewrites stay in WordPress APIs (or HM Rewrite). Router only stores the slug map and builds URLs.

## CLI

```bash
composer scaffold-plugin my-account-plugin route controller
```

Pass `controller` in the same command if those URLs have page logic (forms, steps, redirects).

## When to pass `route`

Pass the token if **any** of these is true:

- Custom pretty URLs for a **plugin page**
- Multi-step flow (`/account/create`, `/account/create/step-2`)
- PHP must generate links or redirects to those pages
- A Controller will call `Router::get_url_complex()`

Do **not** pass `route` if URLs are only:

- `/wp-json/...`
- CPT single/archive from `register_post_type`
- Query args on an existing page (`?foo=1`) with no slug map

## Coupling

| Other token | Why |
| --- | --- |
| `controller` | `Controller::get_form_url()` and `redirect()` call `Router::get_url_complex()`. Without `route`, those methods cannot run. |
| *(none)* | Router can be used alone for URL helpers. |

Instantiate Router early (e.g. from `Main`) so `$rewrite_elements` is filled before any `get_url*` call.

## Class surface

### Property: `$rewrite_elements` (private static)

- **Role:** Canonical map `internal_query_key => public_slug`.
- **Filled in:** constructor (ships as `[]` — **must be filled**).
- **Shape:**

```php
[
    'registration' => 'account-creation',
]
```

- **Why:** Change the public slug in one place. Controllers keep the internal key (`registration`).

### Constructor `__construct()`

- **Role:** Initialize the map. Registers no WordPress hooks.
- **Arguments:** none.
- **When:** Once per request, before generating URLs.

### `get_rewrite_elements(): array`

- **Role:** Read the full map (debug, or drive rewrite registration from the same source).
- **Arguments:** none.

### `rewrite_slug( string $query_var = '' ): string`

- **Role:** Translate one internal key to its public slug.
- **Arguments:**
  - `$query_var` — key in `$rewrite_elements` (e.g. `'registration'`).
- **Returns:** public slug, or `''` if unknown.
- **Why:** Never hardcode public slugs in Controllers.

### `get_url( string $query_var, array $params = [] ): string|false`

- **Role:** Single-segment URL: `{home}/{slug}/` plus optional query args.
- **Arguments:**
  - `$query_var` — internal key (not the public slug).
  - `$params` — passed to `add_query_arg` (e.g. `['step' => 2]`).
- **Returns:** `false` if the key is unknown.
- **Use when:** one rewrite segment only.

### `get_url_complex( array $slugs, array $params = [] ): string|false`

- **Role:** Multi-segment URL. Each item is resolved via `rewrite_slug()`; unknown items are used **verbatim**.
- **Arguments:**
  - `$slugs` — internal keys and/or literal segments. One item → delegates to `get_url()`.
  - `$params` — extra query args.
- **Use when:** `/account-creation/step-2`, or Controller `page_slug` plus extras.
- **Controller:** `get_form_url( $args )` = `get_url_complex( [ $this->page_slug ], $args )`.

### `get_post_type_permalink_rewrite( string $post_type ): string`

- **Role:** Derive the permastruct for a CPT/page from `$wp_rewrite`.
- **Arguments:** `$post_type` (`'page'` uses the page permastruct, otherwise extra permastruct).
- **Returns:** `''` if no tokens. **Does not use** `$rewrite_elements`.
- **Use when:** mixing custom plugin pages with native CPT permalinks.

## Post-scaffold recipe

1. Open `classes/Routes/Router.php` and fill `$rewrite_elements` in `__construct()`.
2. Register matching rewrite rules (same internal keys in `query`, same public slugs).
3. If pages have PHP logic: extend `Controller`, set `$page_slug` to the **internal** key.
4. Generate links only via `Router::get_url` / `get_url_complex` (or `$controller->get_form_url()`).
5. Flush rewrite rules on plugin activation (Router does not do this).

## Do / don’t

- Do keep internal keys stable; change only the right-hand slug.
- Do pass `route controller` together for custom page flows.
- Don’t use Router for REST routes (`register_rest_route`).
- Don’t pass the public slug into `get_url()` — pass the internal key.
- Don’t leave `$rewrite_elements = []` — the class is a no-op until filled.

## Signals for the agent

rewrite, custom slug, pretty URL, virtual page, wizard, account, front dashboard, `query_var`, `add_rewrite_rule`, HM Rewrite, `get_url`, plugin permalink.
