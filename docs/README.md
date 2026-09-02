# Scaffold documentation (for humans and agents)

These files describe **when** to run `composer scaffold-plugin` and **which CLI tokens** to pass. They also describe the boilerplate class surface copied for each token, so an agent can continue after the command.

## How to use this folder

1. Read [decision-guide.md](decision-guide.md) first. Pick tokens from the brief, not from habit.
2. Read [command.md](command.md) for `folder`, options, and interactive prompts.
3. Open one file per chosen token under [components/](components/).
4. After scaffolding, follow the **Post-scaffold recipe** in each chosen file.

Always-copied classes (not CLI tokens) are listed in [components/_always-included.md](components/_always-included.md).

## File contract

Every component file uses the same shape:

- YAML front matter (`cli_token`, `include_when`, `do_not_include_when`, coupling, copied paths)
- Intent
- CLI
- When to pass / when not
- Coupling
- Class surface (properties and methods)
- Post-scaffold recipe
- Do / don’t
- Signals for the agent

CLI tokens are lowercase: `controller`, `cron`, `model`, `route`, `widget`, `shortcode`.

Do not confuse:

| Vocabulary | Example |
| --- | --- |
| CLI token | `route` |
| PHP class | `Router` |
| Directory | `classes/Routes/` |
