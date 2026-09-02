# Agent instructions — Composer Scaffold Plugin

When creating a new WordPress plugin with this package, **do not guess CLI flags**. Read the docs in this order:

1. [docs/decision-guide.md](docs/decision-guide.md) — which tokens to pass
2. [docs/command.md](docs/command.md) — `folder`, options, interactive prompts
3. One file per token under [docs/components/](docs/components/)
4. [docs/components/_always-included.md](docs/components/_always-included.md) — Main, Helpers, Blocks (not tokens)

## Command

```bash
composer scaffold-plugin [--boilerplate-version VERSION] [--no-autoload] <folder> [<components>...]
```

Allowed components (lowercase): `controller`, `cron`, `model`, `route`, `widget`, `shortcode`.

## Hard rules

- Derive `folder` as a WP slug (hyphens). Agency plugins: `beapi-{feature}`. Project plugins: `{project}-{feature}`.
- Pass only tokens justified by the brief (see decision-guide). Empty component list is valid.
- `controller` for rewrite pages ⇒ also pass `route`.
- Gutenberg/ACF blocks ⇒ no extra token.
- REST API ⇒ not `route`.
- `widget` on current boilerplate master is a no-op; use blocks or pin `--boilerplate-version`.
- After PSR-4 scaffold, run `composer dump-autoload` unless `--no-autoload`.
- Fill interactive prompts from the brief; never leave `BEA\PB` / `BEA Plugin Name`.
- The command fails if the plugin folder already exists; it does not update in place.

## After scaffolding

Follow each chosen component’s **Post-scaffold recipe** (empty `$rewrite_elements`, `$blocks`, `Main::init()`, shortcode factory, cron `$type`, etc.).
