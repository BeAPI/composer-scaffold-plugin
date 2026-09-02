---
cli_token: shortcode
aliases: [Shortcode, Shortcode_Factory, add_shortcode]
include_when:
  - Content editors insert `[tag]` in posts/pages
  - Several shortcodes sharing attribute defaults + render
do_not_include_when:
  - Blocks-only UI (prefer always-included Blocks)
  - Admin-only tools with no `[tag]`
depends_on: []
often_with: [model]
copied_files:
  psr4:
    - classes/Shortcodes/Shortcode.php
    - classes/Shortcodes/Shortcode_Factory.php
  legacy:
    - classes/shortcodes/shortcode.php
    - classes/shortcodes/shortcode-factory.php
boilerplate_class: BEA\PB\Shortcodes\Shortcode
---

# Component: shortcode

## Intent

Base class + factory to register WordPress shortcodes as small classes: one `[tag]`, default attributes, `render()`.

Use when the editor still needs `[my_plugin_foo]`. For new editorial UI, prefer blocks (always copied); both can coexist.

## CLI

```bash
composer scaffold-plugin beapi-cta shortcode
```

Add `model` if render loads a CPT/user.

## When to pass `shortcode`

- Spec mentions a shortcode / `[...]` in content
- Multiple tags with the same attribute/render pattern
- Need to keep `add_shortcode` out of `Main` besides factory calls

Do **not** pass `shortcode` for Gutenberg blocks, REST, or widgets.

## Coupling

| Other token | Why |
| --- | --- |
| `model` | Render often wraps a post. |
| Always-included `Helpers` | Locate plugin/theme views inside `render()`. |

Factory resolves classes **in the Shortcodes namespace only**. Pass the short class name, not the FQCN.

## Class surface — `Shortcode` (abstract)

### Property: `$tag` (protected string)

- **Role:** The `[tag]` registered with `add_shortcode`.
- **Why:** Must be unique site-wide. Use a plugin prefix (`beapi_account_form` or `acme_cta`).

### Property: `$defaults` (protected array)

- **Role:** Allowed attributes and defaults for `shortcode_atts`.
- **Shape:** `[ 'id' => 0, 'title' => '' ]`.

### `add(): void`

- **Role:** `add_shortcode( $this->tag, [ $this, 'render' ] )`.
- **Arguments:** none.
- **When:** From the factory (or once on `init`).

### `attributes( $attributes = [] )`

- **Role:** Merge user attributes with `$defaults` (`shortcode_atts`, third arg = `$tag`).
- **Arguments:** `$attributes` — raw shortcode atts from WordPress.
- **Call from:** `render()`.

### `render( $attributes = [], $content = '' ): string` (abstract)

- **Role:** Return HTML (do not echo).
- **Arguments:**
  - `$attributes` — raw atts; run through `attributes()` first.
  - `$content` — enclosed shortcode content, if any.

## Class surface — `Shortcode_Factory`

### `register( string $class_name ): Shortcode|\WP_Error` (static)

- **Role:** Prefix with `BEA\PB\Shortcodes\` (after namespace replace: `{YourNs}\Shortcodes\`), `new` the class, call `add()`.
- **Arguments:** `$class_name` — **short** name (`Cta_Banner`), not `Cta_Banner::class` if that includes another namespace.
- **Returns:** instance, or `WP_Error` if the class is missing / not a `Shortcode` subclass / constructor throws.
- **Note:** Docblock mentions Singleton; implementation is `new $class_name()`.

## Post-scaffold recipe

1. Create `classes/Shortcodes/My_Tag.php` extending `Shortcode`.
2. Set `$tag` and `$defaults`.
3. Implement `render()`: `$atts = $this->attributes( $attributes );` then return markup (or `Helpers` view).
4. On `init` (e.g. `Main::init()`): `Shortcode_Factory::register( 'My_Tag' );`.
5. Document the tag and attributes for editors.

## Do / don’t

- Do prefix `$tag` to avoid collisions.
- Do return a string from `render()`.
- Don’t pass the FQCN into `register()` — namespace is prepended.
- Don’t use this factory for blocks.

## Signals for the agent

shortcode, `[tag]`, `add_shortcode`, enclosed content, editor token in post body.
