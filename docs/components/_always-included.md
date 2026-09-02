---
cli_token: null
aliases: [Main, Helpers, Singleton, Blocks, bootstrap]
include_when:
  - Every scaffolded plugin
do_not_include_when: []
depends_on: []
often_with: []
copied_files:
  psr4:
    - "{folder}.php"
    - classes/Main.php
    - classes/Helpers.php
    - classes/Singleton.php
    - classes/Blocks.php
    - classes/Blocks/Block.php
    - classes/Blocks/Block_Interface.php
    - classes/Blocks/Dynamic_Block.php
    - classes/Blocks/Dynamic_Block_Interface.php
    - classes/Blocks/Acf_Block.php
    - classes/Blocks/Acf_Block_Interface.php
    - classes/Blocks/Acf_Json_Block.php
  legacy:
    - "{folder}.php"
    - compat.php
    - autoload.php
    - classes/plugin.php
    - classes/main.php
    - classes/helpers.php
    - classes/singleton.php
    - classes/admin/main.php
boilerplate_class: BEA\PB\Main
---

# Always included (not CLI tokens)

These files are copied for every plugin. Do **not** pass `main`, `blocks`, or `helpers` as components.

On PSR-4 boilerplates (no `autoload.php` in the zip), Blocks are included when `classes/Blocks.php` exists (boilerplate ≥ 3.2). `Acf_Json_Block` is included when present (≥ 3.5).

## Bootstrap (`{folder}.php`)

Defines version, views folder constant, CPT/tax placeholders, `*_URL` / `*_DIR` / `*_PLUGIN_BASENAME`, then boots `Main` and `Blocks` on `plugins_loaded`.

After scaffold, register services from this file or from `Main::init()` — keep a single entry.

## `Main`

| Member | Role |
| --- | --- |
| `use Singleton` | One instance via `Main::get_instance()`. |
| `init()` | Hook setup. Default: `init` → `init_translations()`. Add CPT, taxonomies, factories here. |
| `init_translations()` | `load_plugin_textdomain` on `init` (keep it on `init`, not `plugins_loaded`). |

## `Singleton` (trait)

| Member | Role |
| --- | --- |
| `get_instance()` | Create or return the unique instance; constructor calls `init()`. |
| `init()` | Override in the using class. |
| `__clone` / `__sleep` / `__wakeup` | Forbidden. |
| `destroy()` | Reset the instance (tests). |

## `Helpers`

Stateless utilities. Prefer static calls.

| Method | Arguments | Role |
| --- | --- | --- |
| `locate_template( string $tpl )` | Template name without `.php` | Theme `views/{view-folder}/` first, then plugin `views/`. Filter: `beapi_helpers_locate_template_templates`. |
| `include_template` / similar loaders | Template name + context | Render after locate. |

Use for plugin views (shortcodes, blocks, widgets). The view folder name comes from the interactive prompt.

## `Blocks` + block base classes

Registry, not a CLI component. Gutenberg work does **not** need extra tokens.

| Piece | Role |
| --- | --- |
| `Blocks::register_blocks()` | Instantiates FQCNs from `$blocks`, filter `{folder_underscored}_blocks`. |
| `Block` | `register_block_type( $this->get_slug(), $this->get_block_args() )`. |
| `Dynamic_Block` | Same, plus `render_callback` → `render()`. |
| `Acf_Block` | Registers on `acf/init`; can load `assets/acf/php/{slug}.php`. |
| `Acf_Json_Block` | JSON-based ACF block (when the boilerplate ships it). |

### Post-scaffold recipe (blocks)

1. Add a class extending `Block`, `Dynamic_Block`, or `Acf_Block`.
2. Implement `get_slug()` (non-empty or `init()` throws).
3. Append the FQCN to the `$blocks` array in `Blocks::register_blocks()` or via the filter.
4. Do not pass a `block` CLI token.

## Signals that must **not** add a component

- “Create a plugin”, “CPT”, “taxonomy”, “i18n”, “Gutenberg block”, “ACF block”, “template helper”.
