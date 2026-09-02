# Command reference

```bash
composer scaffold-plugin [--boilerplate-version VERSION] [--no-autoload] <folder> [<components>...]
```

The command downloads [bea-plugin-boilerplate](https://github.com/BeAPI/bea-plugin-boilerplate), copies a subset of files into the WordPress plugins directory (via `composer/installers` `wordpress-plugin` type), then search/replaces placeholders.

It is **interactive**: after files are copied, it asks for human-facing strings. An agent should derive those answers from `<folder>` and the brief, then confirm them.

## Argument: `folder` (required)

| | |
| --- | --- |
| **Role** | Plugin directory name, PHP entry file `{folder}.php`, and WordPress text domain. |
| **Constraints** | WordPress slug: lowercase, hyphens, no spaces. Must not already exist as a plugin folder. |
| **Replaced in code** | `bea-plugin-boilerplate` → `{folder}`; `init_bea_pb_plugin` → `init_{folder_with_underscores}_plugin`; `bea_pb_blocks` → `{folder_with_underscores}_blocks`. |
| **Why** | One identifier for path, bootstrap function, and i18n. |

Examples: agency `beapi-account`; project `acme-intranet`. Agency plugins use the `beapi-` prefix. Project plugins use `{project}-` (client/project slug), not `beapi-`.

## Argument: `components` (optional, array)

Space-separated list from:

`controller` `cron` `model` `route` `widget` `shortcode`

Unknown values are skipped silently. Empty list = core files only (Main, Helpers, Singleton, Blocks on PSR-4).

The command prints the selection and asks for confirmation before download/copy.

See [decision-guide.md](decision-guide.md) and each file in [components/](components/).

## Option: `--boilerplate-version`

| | |
| --- | --- |
| **Default** | `Latest` (GitHub `master.zip`). |
| **Role** | Pin the boilerplate git tag/branch used as the zip (e.g. `3.5.1`). |
| **When** | Reproducing an old plugin, or widgets that disappeared from current master. |
| **When not** | New plugins: omit it. |

## Option: `--no-autoload`

| | |
| --- | --- |
| **Default** | Off. On PSR-4 boilerplates the command writes `autoload.psr-4` in the **project** `composer.json`. |
| **Role** | Skip that write. |
| **When** | The host project must not be modified, or autoload is handled elsewhere. |
| **When not** | Standard WordPress Composer installs: leave unset, then `composer dump-autoload`. |

Non-PSR-4 boilerplates (legacy `autoload.php`) never touch Composer autoload.

## Interactive prompts

Empty answers are rejected. Each value is confirmed with “Is that Ok?”.

| Prompt | Placeholder replaced | How to derive |
| --- | --- | --- |
| Plugin real name | `BEA Plugin Name` | Human title, e.g. folder `beapi-account` → `Be API Account`, folder `acme-intranet` → `Acme Intranet`. |
| Plugin description | `Your plugin description` | One line from the ticket (shown in `plugins.php`). |
| Namespace | `BEA\PB` | PSR-4 vendor + plugin. Agency: `Beapi\Account`. Project: `Acme\Intranet`. Use `\` in the answer. |
| Constants prefix | `BEA_PB_` | `UPPER_SNAKE_` of the namespace. A trailing `_` is added if missing. |
| View folder name | `bea-pb` | Usually the same as `folder`. Used in `BEA_*_VIEWS_FOLDER_NAME` and `Helpers::locate_template()`. |

Do not leave boilerplate strings (`BEA\PB`, `BEA Plugin Name`) in a generated plugin.

## Behaviour notes for agents

- Target path comes from Composer’s installer for `wordpress-plugin`. If that path cannot be resolved, the command fails.
- If `{plugins}/{folder}` already exists, the command fails. It does not merge or update.
- Boilerplate is cached under `{vendor}/boilerplate`. A previous download can stick until that directory is removed.
- PSR-4 is detected when `autoload.php` is **absent** from the boilerplate zip.
- Widgets: if `views/` is missing from the zip, the token is skipped with an error line; other components still copy.
- Success on PSR-4 + autoload: run `composer dump-autoload` before loading the plugin.

## Typical agent run

```bash
composer scaffold-plugin beapi-account route controller
```

Suggested prompt answers (agency):

- Name: `Be API Account`
- Description: `Front-end account area with custom rewrites`
- Namespace: `Beapi\Account`
- Constants: `BEAPI_ACCOUNT_`
- Views: `beapi-account`

Project plugin example: `composer scaffold-plugin acme-account route controller` → namespace `Acme\Account`, constants `ACME_ACCOUNT_`, views `acme-account`.
