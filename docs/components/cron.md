---
cli_token: cron
aliases: [Cron, WP-Cron, lock, batch, import]
include_when:
  - Scheduled or CLI batch work that must not overlap
  - Need a lock file + log file per job type
do_not_include_when:
  - One-off `wp_schedule_single_event` with no lock
  - Front-end or editor work only
  - Queue plugins already providing locks (Action Scheduler, etc.) unless you still want this helper
depends_on: []
often_with: [model]
copied_files:
  psr4: [classes/Cron/Cron.php]
  legacy: [classes/cron/cron.php]
boilerplate_class: BEA\PB\Cron
---

# Component: cron

## Intent

Abstract worker for long-running cron/CLI jobs: **lock file** in `wp-content` so two runs cannot overlap, plus **file logs** via `Bea_Log`.

It does not call `wp_schedule_event()`. You still register the hook and cron interval yourself.

## CLI

```bash
composer scaffold-plugin acme-sync cron model
```

Add `model` when the job reads/writes CPT or users.

## When to pass `cron`

- Nightly import/export, sync, cleanup
- WP-CLI command that must refuse a second process
- Need `wp-content/.lock-cron-{type}` (multisite: `lock-cron-{blog_id}-{type}`)

Do **not** pass `cron` for `wp_cron` that only sends one email with no lock.

## Coupling

| Other token | Why |
| --- | --- |
| `model` | Typical for import/update of posts/users. |
| External: `Bea_Log` | `add_log()` instantiates `\Bea_Log`. Without that library, logging throws / fatals. Locks still work. |

Namespace on disk: class `BEA\PB\Cron` in `classes/Cron/Cron.php` (PSR-4 folder vs class namespace).

## Class surface

Abstract class. Set `$type` and implement `process()`.

### Property: `$type` (protected string)

- **Role:** Suffix for lock and log filenames (`cron-{type}.log`, `.lock-cron-{type}`).
- **Why:** Several jobs can coexist. **Must be non-empty** or `get_log_filename()` throws.

### Property: `$log` (private `\Bea_Log`)

- **Role:** Lazy log writer. Do not set manually.

### Property: `$filesystem` (protected `\WP_Filesystem_Direct`)

- Declared; lock helpers use `get_filesystem()` directly.

### `process()` (abstract)

- **Role:** Job body. Call `is_locked()` / `create_lock_file()` / `delete_lock_file()` around it.
- **Arguments:** none (subclass may add WP-CLI args on a wrapper).

### `is_locked(): bool`

- **Role:** Whether the lock file exists (`clearstatcache` first).
- **Arguments:** none.

### `create_lock_file(): bool`

- **Role:** `touch` the lock path under `wp_content_dir()`.
- **Arguments:** none.

### `delete_lock_file(): bool`

- **Role:** Delete lock if present.
- **Arguments:** none.

### `add_log( string $message, string $type = \Bea_Log::gravity_7 ): void` (protected)

- **Role:** Append to `WP_CONTENT_DIR/cron-{type}.log` and `printf` a timestamped line (CLI-friendly).
- **Arguments:**
  - `$message` — log line (escaped for stdout).
  - `$type` — `Bea_Log` severity constant.

Lock path is private (`get_lock_file_path`). Do not change the naming scheme without updating ops docs.

## Post-scaffold recipe

1. Extend `Cron`, set `$type` (`import-members`, `purge-logs`).
2. Implement `process()`:

   ```text
   if ( is_locked() ) { add_log(...); return; }
   create_lock_file();
   try { ... } finally { delete_lock_file(); }
   ```

3. Schedule with `wp_schedule_event` or a WP-CLI command that calls `process()`.
4. Ensure `Bea_Log` is available if you use `add_log()`.
5. Document lock/log files for ops (they live in `wp-content/`, not the plugin dir).

## Do / don’t

- Do unique `$type` per job.
- Do always delete the lock in `finally` (or equivalent) so a crash does not stick forever — or document manual unlock.
- Don’t use this class to *register* cron schedules only.
- Don’t run `process()` on front-end `init`.

## Signals for the agent

cron, WP-Cron, WP-CLI, import, export, sync, batch, lock, overlapping, `wp_schedule_event`.
