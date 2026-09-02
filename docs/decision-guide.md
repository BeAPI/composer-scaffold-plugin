# Decision guide

Use this page to choose CLI tokens from a ticket, spec, or user request. Tokens are additive: pass only what the new plugin will use on day one.

## Command skeleton

```bash
composer scaffold-plugin [--boilerplate-version VERSION] [--no-autoload] <folder> [<components>...]
```

`<folder>` is required (plugin directory / text domain). Components are optional and space-separated.

## Folder naming

| Context | Pattern | Example |
| --- | --- | --- |
| Agency plugin | `beapi-{feature}` | `beapi-account` |
| Project plugin | `{project}-{feature}` | `acme-intranet` |

Do not use the old `bea-` prefix. Do not prefix project plugins with `beapi-`.

## Signal → token

| Signal in the brief | Token(s) | Why |
| --- | --- | --- |
| Custom front URLs, rewrite, query vars, wizard / account / dashboard pages | `route` + `controller` | `Controller` builds URLs through `Router`. |
| Custom pages with forms / steps / redirects, but URLs already exist | `controller` | Still pass `route` if `get_form_url()` is used. |
| WP-Cron, WP-CLI batch, import, lock file, avoid overlapping runs | `cron` | Lock + log helpers. Needs `Bea_Log`. |
| Wrap `WP_Post` / CPT with meta, ACF, terms, permalinks | `model` | Copies `Model` and `User`. |
| Wrap `WP_User` (members, authors) | `model` | `User` ships with the same token. |
| Classic sidebar widget | `widget` | Only if the boilerplate version still has `views/` widgets. Prefer blocks on current boilerplate. |
| `[shortcode]` in content | `shortcode` | Base class + factory. |
| Gutenberg / ACF blocks | *(none)* | Block classes are always copied on PSR-4 boilerplates ≥ 3.2. |
| CPT / taxonomy registration only | *(none)* | Register in `Main::init()`. |
| REST API only (`register_rest_route`) | *(none)* | Not `route`. |
| Helpers, templates, i18n, singleton | *(none)* | Always copied. |

## Coupling rules

- If you pass `controller` for rewrite-based pages, pass `route` too.
- `route` alone is valid if you only need URL helpers.
- `model` is enough for both posts and users; there is no `user` token.
- `widget` on current master boilerplate: the command prints an error and skips the folder. Use blocks instead, or pin `--boilerplate-version` to a release that still contains widgets.
- Never invent tokens (`block`, `helper`, `main`, `acf`). Unknown strings are ignored.

## Minimal examples

Plugin that only registers a CPT and ACF blocks:

```bash
composer scaffold-plugin beapi-events
```

Account area with pretty URLs:

```bash
composer scaffold-plugin beapi-account route controller
```

Nightly import of a CPT:

```bash
composer scaffold-plugin acme-sync model cron
```

Editorial shortcode plus CPT model:

```bash
composer scaffold-plugin acme-highlights model shortcode
```

## After the command

1. Confirm Composer added the PSR-4 mapping (unless `--no-autoload`).
2. Run `composer dump-autoload`.
3. Implement empty extension points (`$rewrite_elements`, `$blocks = []`, `Main::init()`).
4. Do not copy extra component folders by hand: re-run is not idempotent (existing folder = error).
