---
cli_token: widget
aliases: [Widget, WP_Widget, sidebar]
include_when:
  - Classic Appearance → Widgets / block-based widgets wrapping a PHP WP_Widget
  - Boilerplate version that still ships views/ and classes/widgets/
do_not_include_when:
  - Gutenberg blocks (use always-included Blocks)
  - Current master boilerplate without views/ (command skips the token)
depends_on: []
often_with: []
copied_files:
  psr4: []
  legacy:
    - classes/widgets/main.php
    - views/admin/widget.php
    - views/client/widget.php
boilerplate_class: null
---

# Component: widget

## Intent

Copy the **legacy widget** package: a `WP_Widget` class plus admin and front views.

On current plugin-boilerplate (PSR-4, no `views/` at zip root), **this token does nothing useful**: the command prints that widgets are unsupported and skips folder creation. Prefer a Gutenberg block (`docs/components/_always-included.md`).

## CLI

Only with a boilerplate that still contains widgets:

```bash
composer scaffold-plugin beapi-promo-widget widget --boilerplate-version=2.x.x
```

(Use a real tag that still has `views/` and `classes/widgets/`.)

On Latest/master:

```bash
composer scaffold-plugin beapi-promo-widget widget
```

→ error line, no widget files; other tokens still copy.

## When to pass `widget`

- Ticket explicitly requires a **classic widget**
- You pinned `--boilerplate-version` to a zip that includes `views/`

Do **not** pass `widget` for:

- New plugins on current boilerplate
- “Block in the widget area” (that is still a block)
- Shortcodes

## Coupling

None. Widgets are independent. For new UI, use Blocks (always copied on PSR-4 ≥ 3.2) instead of this token.

The scaffold always `mkdir classes/widgets/` (legacy path) even on PSR-4 when views exist — not PSR-4 `Widgets/`.

## Class surface

Shipped files (legacy layout):

| File | Role |
| --- | --- |
| `classes/widgets/main.php` | Widget class (extend / rename after search-replace). |
| `views/admin/widget.php` | Form in Appearance → Widgets. |
| `views/client/widget.php` | Front-end output. |

Typical WordPress widget API (in that class, depending on boilerplate version):

| Member | Role |
| --- | --- |
| constructor | `id_base`, name, `WP_Widget` options. |
| `widget( $args, $instance )` | Echo front view (`$args` = `before_widget`, etc.). |
| `form( $instance )` | Admin fields. |
| `update( $new_instance, $old_instance )` | Sanitize and return instance. |

Load views via `Helpers::locate_template()` when Helpers is available.

## Post-scaffold recipe

1. Confirm `views/` and `classes/widgets/` exist after the command. If not, drop the token and implement a block.
2. Register the widget on `widgets_init` from `Main`.
3. Replace dummy strings; keep admin vs client views split.
4. Do not add this token “just in case”.

## Do / don’t

- Do pin boilerplate version if you truly need widgets.
- Don’t confuse widgets with `Dynamic_Block`.
- Don’t expect PSR-4 `classes/Widgets/` on current scaffold logic.

## Signals for the agent

widget, `WP_Widget`, sidebar, Appearance → Widgets. If the user says “Gutenberg” or “bloc”, do **not** pass `widget`.
