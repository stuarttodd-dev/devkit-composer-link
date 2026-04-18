<div align="center">
  <img src="./devkit-logo.png" alt="Composer Link" width="420" />
  <p><strong>Composer Link</strong><br /><em>Local package path overrides for Composer.</em></p>
</div>

> **Beta software.** APIs and file names may change between releases. Test in a temporary project first, then pin the package version for your team.

# devkit-composer-link

**Local path overrides for Composer dependencies**, with a separate local manifest so your committed `composer.json` and `composer.lock` stay clean.

Package: **`devkit/composer-link`**

## Why this exists

When you need to test package changes inside a real app, the usual workflow often requires:

- hand-edit root `composer.json` repositories
- point dependencies at local folders
- remember to undo everything before committing

Composer Link stores local override state in dedicated local files, rebuilds managed path repositories, and keeps your team baseline untouched.

## Prerequisites

- PHP **8.3+**
- Composer **2.2+** (plugin allow-list support)

## Install

Install in the **consuming application** (not in the library repo you are editing):

```bash
composer require --dev devkit/composer-link
```

If you need VCS install from a fork/repo:

```bash
composer require --dev devkit/composer-link:^1.0 \
  --repository='{"type":"vcs","url":"https://github.com/stuarttodd-dev/composer-link.git"}'
```

## Allow the plugin (Composer 2.2+)

Approve the interactive prompt, or add this explicitly:

```json
{
  "config": {
    "allow-plugins": {
      "devkit/composer-link": true
    }
  }
}
```

## Run from project root

All commands should run from your **application root** (where the committed `composer.json` lives).

Use:

```bash
composer link-help
```

If the package is installed correctly, `composer list` will include:
`link`, `add`, `unlink`, `promote`, `linked`, `refresh`, `link-doctor`, `local-bootstrap`, `local-install`, `link-help`.

## At a glance

| Command | In one sentence |
| --- | --- |
| **`link`** | Override an existing dependency to a local path. |
| **`add`** | Bootstrap a dependency from local path before it exists in committed manifest/registry. |
| **`unlink`** | Remove local override state and restore or remove requirement. |
| **`promote`** | Move a locally-managed package back to a published constraint. |
| **`linked`** | Show all packages currently managed by Composer Link. |
| **`refresh`** | Rebuild managed path repositories in local manifest from state file. |
| **`link-doctor`** | Verify local setup and ensure ignore rules/local files are correct. |
| **`local-bootstrap`** | Copy committed manifest/lock into local manifest/lock files. |
| **`local-install`** | Install using local manifest (`COMPOSER=composer.local.json`). |
| **`link-help`** | Print a concise command/arguments/options overview in terminal. |

## Quick start (smoke test)

This validates the plugin in a temporary app and exercises `add`, `linked`, and `link-doctor`.

### 1) (Optional) refresh bundled smoke package

```bash
cd /path/to/composer-link
composer smoke:scaffold
```

### 2) Create a temporary app

```bash
mkdir -p ~/main-project && cd ~/main-project
composer init --name="qa/consumer" --require="php:^8.3" --no-interaction
composer config version 1.0.0
```

### 3) install Composer Link from local clone

Get clone absolute path:

```bash
cd /path/to/composer-link
pwd -P
```

Add a path repo in `~/main-project/composer.json`:

```json
"repositories": {
  "composer-link": {
    "type": "path",
    "url": "/path/to/composer-link"
  }
}
```

Then require it:

```bash
cd ~/main-project
composer require --dev "devkit/composer-link:@dev"
```

### 4) add bundled smoke dependency

```bash
composer add smoke/smoke-test-package /path/to/composer-link/smoke/test-package
```

### 5) verify

```bash
composer linked
composer link-doctor
```

Remove the smoke test dependency when done:

```bash
composer unlink smoke/smoke-test-package --remove
```

## Configuration

Composer Link reads optional config from root `composer.json` under `extra.composer-link`.

```json
{
  "extra": {
    "composer-link": {
      "overrides_file": "packages-local.json",
      "local_composer_json": "composer.local.json"
    }
  }
}
```

| Key | Role |
| --- | --- |
| **`overrides_file`** | Local state file with managed packages, paths, mode, constraints. |
| **`local_composer_json`** | Local Composer manifest used for path repositories and local update/install runs. |

## Files and folders

Default local artifacts:

- **`packages-local.json`**: plugin state (gitignore this).
- **`composer.local.json`**: local manifest (gitignore this).
- **`composer.local.lock`**: lockfile for local manifest (gitignore this).

Committed baseline files stay canonical:

- **`composer.json`**
- **`composer.lock`**

Legacy note: `composer.local-packages.json` is read only as fallback when `packages-local.json` is missing/empty; next write persists to `packages-local.json`.

## Command reference

Use `composer help <command>` for full option docs.

### `link` — override existing dependency

```bash
composer link <package> <path>
```

Examples:

```bash
composer link my-vendor/my-package ../packages/my-package
composer link my-vendor/my-package ../packages/my-package --constraint=@dev
composer link my-vendor/my-package ../packages/my-package --no-update
composer link my-vendor/my-package ../packages/my-package --no-symlink
```

### `add` — bootstrap new local dependency

```bash
composer add <package> <path>
```

Examples:

```bash
composer add my-vendor/new-lib ./libs/new-lib
composer add my-vendor/new-lib ./libs/new-lib --no-dev
composer add my-vendor/new-lib ./libs/new-lib --constraint=^0.1
```

### `unlink` — stop managing a package locally

```bash
composer unlink <package>
```

If package was added via `add`, use `--remove` to remove requirement:

```bash
composer unlink my-vendor/experimental-package --remove
```

Useful flags:

- `--no-update`
- `--remove` (required for bootstrap-mode removals)

### `promote` — move to published constraint

```bash
composer promote <package> <constraint>
```

Examples:

```bash
composer promote my-vendor/my-package ^1.5
composer promote my-vendor/my-package ~2.3.0
composer promote my-vendor/my-package ^1.0 --no-update
```

### `linked` — show managed packages

```bash
composer linked
```

Outputs package, mode (`override`/`bootstrap`), path, symlink behavior, constraint, and path status.

### `refresh` — rebuild managed path repos

```bash
composer refresh
composer refresh --no-update
```

### `link-doctor` — validate setup

```bash
composer link-doctor
```

Checks:

- local ignore block/files
- linked path existence
- count of plugin-managed path repositories in local manifest

### `local-bootstrap` — create local manifest files

```bash
composer local-bootstrap
composer local-bootstrap --force
```

Copies committed `composer.json` (+ `composer.lock` if present) to local equivalents.

### `local-install` — install via local manifest

```bash
composer local-install
```

Equivalent to:

```bash
COMPOSER=composer.local.json composer install
```

Examples:

```bash
composer local-install --no-dev
composer local-install --prefer-dist
composer local-install --no-scripts
```

### `link-help` — command summary in terminal

```bash
composer link-help
composer help link-help
```

## Typical workflows

### Override a released package with local checkout

```bash
composer link your-vendor/your-package ../packages/your-package
```

If local branch/version does not satisfy baseline constraint:

```bash
composer link your-vendor/your-package ../packages/your-package --constraint=@dev
```

### Bootstrap package before Packagist

```bash
composer add your-vendor/new-package ../packages/new-package
```

Later switch to published dependency:

```bash
composer promote your-vendor/new-package ^1.0
```

## Version control and safety

Keep shared baseline committed:

- `composer.json`
- `composer.lock`

Keep local override artifacts out of Git:

- `packages-local.json`
- `composer.local.json`
- `composer.local.lock`

`packages-local.json` is Composer Link state only; Composer itself reads whichever manifest `COMPOSER` points to.

For local workflows:

```bash
composer local-bootstrap
composer link vendor/package ../path/to/package
composer local-install
```

Before merging release work, align committed manifest/lock with intended published constraints (via `promote`, `unlink`, or normal manifest edits).

## QA notes (for this repository)

Running Composer in this plugin repository alone does **not** register plugin commands, because Composer plugins activate when installed as dependencies of another project.

For a full step-by-step manual test plan, use [`QA.md`](./QA.md).

Validate in a separate main project:

```bash
composer list | grep -E '^\s+(link|add|unlink|promote|linked|refresh|link-doctor|local-bootstrap|local-install|link-help)\s'
composer help link
```

## Development

```bash
git clone git@github.com:stuarttodd-dev/composer-link.git
cd composer-link
docker compose build && docker compose up -d
docker exec composer-link composer install
docker exec composer-link composer standards:check
docker exec composer-link composer tests
```

Tests:

- `composer tests` (Pest unit + integration)
- `composer test:coverage` (requires PCOV or Xdebug)

## Support

If this project saves you time and you want to support future updates:

- [Buy Me a Coffee](https://buymeacoffee.com/stuarttodd)

## License

MIT (see `composer.json`).
