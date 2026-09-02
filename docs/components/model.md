---
cli_token: model
aliases: [Model, User, WP_Post wrapper, entity]
include_when:
  - Domain logic around a CPT (`WP_Post`) beyond raw `get_post()`
  - Domain logic around `WP_User` (profile, meta, ACF on users)
  - Shared get/update meta, terms, thumbnail, P2P helpers
do_not_include_when:
  - Plugin never loads posts/users in PHP (blocks-only with no PHP model)
  - You only call `get_post()` once in a template
depends_on: []
often_with: [cron, controller, shortcode]
copied_files:
  psr4:
    - classes/Models/Model.php
    - classes/Models/User.php
  legacy:
    - classes/models/model.php
    - classes/models/user.php
boilerplate_class: BEA\PB\Models\Model
---

# Component: model

## Intent

One token copies **two** bases:

- `Model` — wrap a `WP_Post` of a given CPT (meta/ACF, terms, thumbnail, update/delete).
- `User` — wrap a `WP_User` (meta/ACF, identity, capabilities).

Use them to keep business rules off templates and off `Main`.

## CLI

```bash
composer scaffold-plugin beapi-events model
```

## When to pass `model`

- Custom post type with PHP around fields, taxonomies, permalinks
- Member/user features (create user, ACF on profile)
- Cron/import that upserts posts or users
- Shortcode/block that needs a typed object instead of `WP_Post`

Do **not** pass `model` for a plugin that only registers a CPT and uses the REST/editor UI.

There is no separate `user` token.

## Coupling

| Other token | Why |
| --- | --- |
| `cron` | Imports usually go through `Model::update` / `User::create`. |
| `controller` | Page loads an entity then renders. |
| `shortcode` | Shortcode render loads a post model. |
| Optional: ACF | `get_meta` / `update_meta` use `get_field` / `update_field` when they exist; otherwise post/user meta. |
| Optional: Posts 2 Posts | `connect()` / `disconnect()` no-op (`false`) if `p2p_type` is missing. |

## Class surface — `Model` (abstract)

### Property: `$post_type` (protected string)

- **Role:** CPT this class accepts. Constructor throws `InvalidArgumentException` if `$post->post_type` differs.
- **Why:** One PHP class per CPT.

### Property: `$ID` (protected int) / `$wp_object` (public `\WP_Post`) / `$fields` (protected array)

- Identity, native post, cached ACF name→key map.

### `__construct( \WP_Post $post_obj )`

- **Role:** Bind post; validate CPT.
- **Arguments:** `$post_obj` — must match `$post_type`.

### `get_model( \WP_Post $post_obj ): object|\WP_Error` (static)

- **Role:** Factory from `get_post_type_object( $post )->model_class`.
- **Arguments:** `$post_obj`.
- **Why:** Register `'model_class' => My_Event::class` on `register_post_type` args (custom key). Missing class → `WP_Error`.

### Accessors

| Method | Role |
| --- | --- |
| `get_id()` | Post ID. |
| `get_title()` | `get_the_title`. |
| `get_post_type()` | Post type string. |
| `get_object()` | Underlying `WP_Post`. |
| `get_permalink( array $args = [] )` | Permalink plus query args. |

### Meta / ACF

| Method | Arguments | Role |
| --- | --- | --- |
| `get_meta( string $key, $format = true )` | `$key` ACF or meta key; `$format` ACF only | ACF `get_field` or `get_post_meta`. Empty key → `false`. |
| `update_meta( string $key, $value = '' )` | | If `update_meta_{$key}` exists on the class, call it; else `update_content_meta`. |
| `update_content_meta` (protected) | `$key`, `$value` | ACF `update_field` or `update_post_meta`. |

### Terms / media / P2P / lifecycle

| Method | Arguments | Role |
| --- | --- | --- |
| `set_terms( $terms, string $taxonomy, $append = false )` | Like `wp_set_object_terms` | Assign terms. |
| `get_terms( string $taxonomy, array $args = [] )` | Empty `$args` → `get_the_terms` | List terms. |
| `get_first_term( string $taxonomy, array $args = [] )` | | First term or `null`. |
| `has_terms( string $taxonomy )` | | `is_object_in_term`. |
| `get_thumbnail` / `get_thumbnail_id` / `set_thumbnail` / `has_thumbnail` | Size / id as in WP | Featured image. |
| `connect` / `disconnect` (protected) | `$object_id`, `$connection_type`, optional `$metas` | P2P; `false` if plugin absent. |
| `update( array $data )` | Post fields + extras | Merge with `get_all_data()`, `wp_update_post`. |
| `delete( $force_delete = false )` | | `wp_delete_post`. |
| `get_all_data()` | | Post array + ACF + non-internal meta + term IDs. |

`filter_post_array` / `filter_post_keys` restrict updates to `WP_Post` properties plus `import_id`, `context`, `tags_input`, `tax_input`, `post_category`.

## Class surface — `User` (concrete, extendable)

### `__construct( \WP_User $user_obj )`

- Throws `InvalidArgumentException` if `! $user_obj->exists()`.

### `create( array $args, $user_email = null ): User|\WP_Error` (static)

- **Role:** Insert user. Prefer a single `$args` array (`wp_insert_user` keys). Second arg is deprecated.
- **Defaults:** random `user_pass` if omitted.

### Other methods

| Method | Role |
| --- | --- |
| `get_id()` / `get_user()` | ID and `WP_User`. |
| `get_avatar( $size, $default_url, $alt, $args )` | `get_avatar`. |
| `get_first_name()` / `get_last_name()` / `get_email()` | `false` if empty. |
| `has_cap( string $capability, ... )` | Forwards extra args to `WP_User::has_cap`. |
| `get_meta` / `update_meta` | User meta or ACF (`user_{id}`). Supports `update_meta_{$key}`. |
| `delete( $reassign = null )` | `wp_delete_user`. |
| `get_permalink( array $args = [] )` | Author URL from `get_the_author_meta( 'url' )`; note inverted empty check in boilerplate — verify before relying on it. |

## Post-scaffold recipe

1. For a CPT: extend `Model`, set `$post_type`, add `'model_class'` on `register_post_type`.
2. Instantiate with `new Event( $post )` or `Model::get_model( $post )`.
3. For users: use `User` or a subclass; create via `User::create( [ 'user_login' => ..., 'user_email' => ... ] )`.
4. Prefer `get_meta` / `update_meta` over raw meta in features.
5. Override `update_meta_{$key}` when a field needs extra logic.

## Do / don’t

- Do one `Model` subclass per CPT.
- Do not wrap posts of another CPT in the wrong class (constructor throws).
- Don’t use `Model` for REST schema; it is a PHP domain layer.

## Signals for the agent

model, entity, wrapper, CPT PHP, `WP_Post`, `WP_User`, ACF on post/user, P2P, import users/posts.
