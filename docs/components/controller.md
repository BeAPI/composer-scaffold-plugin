---
cli_token: controller
aliases: [Controller, page controller, form controller]
include_when:
  - Custom rewritten pages that need PHP (forms, steps, access checks)
  - Selecting “the controller for the current request” from query vars
  - Redirects back to the same virtual page
do_not_include_when:
  - REST controllers (`WP_REST_Controller`)
  - Admin pages (`add_menu_page`) only
  - Block / shortcode render only
depends_on: [route]
often_with: [route]
copied_files:
  psr4: [classes/Controllers/Controller.php]
  legacy: [classes/controllers/controller.php]
boilerplate_class: BEA\PB\Controllers\Controller
---

# Component: controller

## Intent

Abstract front controller for **one rewritten plugin page**. It knows how to detect “am I on my page?”, build that page’s URL via `Router`, and redirect to it.

You extend it once per virtual page (registration, dashboard, settings). It is not an MVC HTTP router and not `WP_REST_Controller`.

## CLI

```bash
composer scaffold-plugin my-account-plugin route controller
```

Always pass `route` with `controller` when using `get_form_url()` / `redirect()` (they call `Router`).

## When to pass `controller`

- Front-end flow tied to a custom rewrite + query var
- Form POST handling on that page
- Multi-step pages that redirect to themselves with extra args
- Need `get_current_controller()` to pick the active page class

Do **not** pass `controller` for wp-admin screens, REST, or Gutenberg save logic.

## Coupling

| Other token | Why |
| --- | --- |
| `route` (required in practice) | `use BEA\PB\Routes\Router` and `Router::get_url_complex()`. |
| `model` | Optional, if the page loads/saves CPT or users. |

`page_query_var` still contains the placeholder `bea_pb_page` until you change it (the scaffold does not replace that string except via generic `bea-pb` view-folder replace — **set the query var explicitly**).

## Class surface

Abstract class. Implement a concrete subclass + singleton (or equivalent) with `get_instance()` because `get_current_controller()` / `filter_classes()` call `get_instance()` and `is_page()`.

### Property: `$page_slug` (protected string)

- **Role:** Internal rewrite key for this page (must match a key in `Router::$rewrite_elements`).
- **Why:** Identity of the controller vs the current request.

### Property: `$page_query_var` (protected string, default `'bea_pb_page'`)

- **Role:** Query var that rewrite rules set, e.g. `index.php?bea_pb_page=registration`.
- **Why:** `is_page()` compares `get_query_var( $page_query_var )` to `$page_slug`.
- **Do:** Align this with the rewrite `query` string. Prefer a plugin-prefixed var after scaffold.

### `is_page(): bool` (protected)

- **Role:** Whether this controller owns the current request.
- **Arguments:** none.

### `get_form_url( array $args = [] ): string|false`

- **Role:** Public URL of this page, optional query args.
- **Arguments:** `$args` — extra `add_query_arg` parameters.
- **Why:** Forms and links must not hardcode slugs.

### `redirect( array $args = [] ): void` (protected)

- **Role:** `wp_safe_redirect` to `get_form_url( $args )` then `exit`.
- **Arguments:** `$args` — same as `get_form_url`.

### `get_current_controller(): self|\WP_Error` (static)

- **Role:** Among declared subclasses, return the one whose `is_page()` is true.
- **Returns:** `WP_Error('no-controller')` if none match.
- **Constraint:** Subclasses must be loaded (`get_declared_classes()`). `filter_classes` currently checks `\BEA\PB\Controller` — after namespace replace, **fix that FQCN** if it was not rewritten (search for leftover `BEA\PB\Controller` vs `...\Controllers\Controller`).

### `filter_classes( string $class_name ): bool` (static)

- **Role:** Used by `array_filter` on declared classes.
- **Arguments:** `$class_name` — candidate FQCN.

### `get_default_data(): array`

- **Role:** Default view/form data; override in the subclass.
- **Arguments:** none.

## Post-scaffold recipe

1. Pass `route` as well; fill `Router::$rewrite_elements`.
2. Extend `Controller`, set `$page_slug` (internal key) and `$page_query_var`.
3. Add rewrite rules that set that query var to `$page_slug`.
4. Hook `wp` (or similar), `if ( $this->is_page() )` then handle POST / enqueue / template.
5. Use `get_form_url()` / `redirect()`, never raw `home_url( 'account-creation' )`.
6. Provide `get_instance()` on the concrete class (Singleton trait is the usual pattern).

## Do / don’t

- Do one subclass per virtual page.
- Do keep `$page_slug` equal to the Router internal key, not the public slug.
- Don’t use this for REST or `admin_post_` handlers.
- Don’t instantiate Controller without Router on disk.

## Signals for the agent

controller, form page, tunnel, étape, `is_page`, virtual page, rewrite page, dashboard front, `get_form_url`.
