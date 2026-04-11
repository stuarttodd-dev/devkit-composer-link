<div align="center">

<img src="./logo.png" alt="Composer Link" width="420" />

<p><strong>Composer Link</strong><br /><em>Local packages. Fewer emotional incidents.</em></p>

</div>

> **Beta software.** APIs, filenames, and vibes may still shift. Try it in a **throwaway project** first, pin a version once you like it, and don’t blame us if you `composer update` at 4:47pm on a Friday. We’re figuring this out in public.

---

**Imagine this:** you’ve got a main project repository. It depends on several packages. You need to work on one of them and **test that everything still works** before you ship anything—so you edit the root **`composer.json`** to point at a **local path**. Annoying, but okay. You run Composer, **`vendor/`** is wired to your checkout, you change the package, it works. Then you have to **change the main project’s `composer.json` back**, clean up the mess, and hope you didn’t leave half a path repository behind. Nobody’s got time for that loop every time—and you want to **come back later** and have the same setup **without doing the whole dance again**.

**Composer Link** is a **Composer plugin** for that: **point dependencies at real folders** without hand-merging JSON like it’s 2004, **without** accidentally committing `../Desktop/my-fork` to `main`, and **without** explaining to CI why `vendor/` is a haunted house.

**Requirements:** PHP **8.3+** (this package is developed and tested on PHP 8.3, I know... but PHPMD doesn't support anything higher than 8.3 currently..).


## Quick start (smoke test)

This walkthrough does two things: (1) install **Composer Link** into a **throwaway main project** from your local clone, and (2) use **`composer add`** to pull in the bundled **`smoke/smoke-test-package`** so you can run **`composer linked`** and **`composer link-doctor`**.

**Main project** means any folder with its own root **`composer.json`** (here **`~/main-project`**). That is your app; it is **not** the `composer-link` git clone. The plugin only registers when **`half-shell-studios/composer-link`** is a real **installed dependency** of that project—running Composer **only** inside the `composer-link` clone will **not** show `composer link` commands.

### 1. Optional — refresh the smoke library inside the clone

```bash
cd /path/to/composer-link
composer smoke:scaffold
```

Skip if `smoke/test-package/` already looks right.

### 2. Create the main project

Use a **new directory outside** the clone (name it anything you like):

```bash
mkdir -p ~/main-project && cd ~/main-project
composer init --name="qa/consumer" --require="php:^8.3" --no-interaction
composer config version 1.0.0
```

### 3. Install Composer Link from your clone (path `repository` + `require`)

Get an **absolute path** to the clone (the folder that contains **`composer.json`** for `half-shell-studios/composer-link`):

```bash
cd /path/to/composer-link
pwd -P
```

In **`~/main-project/composer.json`**, merge a **`repositories`** block like this (use **your** `pwd` output as **`url`**):

```json
"repositories": {
    "composer-link": {
        "type": "path",
        "url": "/path/to/composer-link"
    }
},
```

Then install the plugin:

```bash
cd ~/main-project
composer require --dev "half-shell-studios/composer-link:@dev"
```

Approve **`allow-plugins`** when prompted, or add [`allow-plugins`](#allow-plugins-composer-22) for `half-shell-studios/composer-link` and run **`composer update`** again.

### 4. Add the bundled smoke package

Bootstrap it from the clone’s **`smoke/test-package`** directory (same base path as step 3 + **`/smoke/test-package`**):

```bash
composer add smoke/smoke-test-package /path/to/composer-link/smoke/test-package
```

Adjust the path to match your machine.

### 5. Check that it worked

```bash
composer linked
composer link-doctor
```

You should see plugin commands in **`composer list`** (e.g. **`composer link`**, **`composer add`**, **`composer linked`**).

### What changed on disk

| Path | Role |
|------|------|
| **`packages-local.json`** | Override **state** (which packages, paths, constraints). Gitignore in real projects. |
| **`composer.local.json`** | Copy of **`composer.json`** plus Composer Link–managed path `repositories` (created on **`local-bootstrap`** or the first **`link` / `add`**). Gitignore it. |
| **`composer.local.lock`** | Lockfile for the local manifest (Composer names it from the JSON file—no separate `COMPOSER_LOCK` variable). Gitignore it. |
| **`composer.json` / `composer.lock`** | Stay the **committed** baseline (unchanged by **`link`** / **`add`**). |
| **`vendor/…`** | Populated when you run **`composer local-install`** or **`COMPOSER=composer.local.json composer …`**. |

**Undo** the smoke test (bootstrap package): `composer help unlink` — usually **`composer unlink smoke/smoke-test-package --remove`**.

---

## Install (Packagist or VCS)

Add Composer Link as a **development** dependency to the **application** that consumes your packages (not inside the library you are building):

```bash
composer require --dev half-shell-studios/composer-link
```

If the package is not on Packagist yet (It isn't), install from GitHub (adjust the URL to your fork):

```bash
composer require --dev half-shell-studios/composer-link:^1.0 \
  --repository='{"type":"vcs","url":"https://github.com/stuarttodd-dev/composer-link.git"}'
```

### `allow-plugins` (Composer 2.2+)

Composer may ask you to **allow this plugin** the first time it installs. Approve the prompt, or add explicitly to the **consuming app’s** `composer.json`:

```json
{
    "config": {
        "allow-plugins": {
            "half-shell-studios/composer-link": true
        }
    }
}
```

This package also declares `"extra": { "plugin-optional": true }` so non-interactive installs can skip the plugin until you opt in—interactive runs will still prompt when needed.

---

## Configuration

| Item | Purpose |
|------|--------|
| **`packages-local.json`** | Link **state** (`packages` entries). **Gitignore.** Override via `extra.composer-link` → `overrides_file`. |
| **`composer.local.json`** | **Gitignore.** Full manifest for local Composer runs; receives merged path `repositories`. Default basename `composer.local.json`; override via `extra.composer-link` → `local_composer_json`. |
| **`composer.local.lock`** | **Gitignore.** Lockfile for `composer.local.json` (Composer derives the name from the JSON filename). |
| **Legacy `composer.local-packages.json`** | If **`packages-local.json` is missing or empty**, still **read** for legacy `packages`. Next write saves to **`packages-local.json`**. |
| Path repositories | Injected into **`composer.local.json`** with a marker so unrelated `repositories` entries are never removed |
| Symlinks | `options.symlink: true` on path repos so `vendor/...` points at your local package for instant edits |

Optional settings are merged from **`extra.composer-link`** in the app’s root `composer.json` (keys match the defaults in this package’s `config/composer-link.php`).

---

## Commands

Available **after** the package is installed as a dependency (not when developing this repo in isolation—see [QA: testing this package](#qa-testing-this-package)).

### Overview

| Command | Purpose |
|--------|--------|
| `composer link` | Link an **existing** dependency to a local path |
| `composer add` | **Bootstrap** a package from a local path before it exists on Packagist (this plugin’s command—not the same as `composer require`) |
| `composer unlink` | Remove the local override / restore constraints |
| `composer promote` | Switch from local path to a published constraint |
| `composer linked` | List packages managed by Composer Link |
| `composer refresh` | Rebuild path repos from `packages-local.json` into `composer.local.json` |
| `composer link-doctor` | Check setup; create or patch `.gitignore` for local files |
| `composer local-bootstrap` | Copy committed `composer.json` / `composer.lock` to local manifest files |
| `composer local-install` | Run `composer install` using the local manifest (`COMPOSER=…`) |
| `composer link-help` | In-terminal overview: commands plus **arguments** and **options** (still use `composer help <cmd>` for full flag docs) |

Quick smoke:

```bash
composer local-bootstrap
composer link vendor/package ../packages/package
composer add vendor/new-package ../packages/new-package
composer local-install
composer linked
composer link-doctor
```

For full flag descriptions and defaults, use **`composer help <command>`** (e.g. **`composer help link`**). Run **`composer link-help`** for a **four-column** summary (command, what it does, positional **arguments**, **options**). Unless you pass **`--no-update`**, commands that run Composer will use **`COMPOSER`** pointed at your **`local_composer_json`** (default **`composer.local.json`**), so **`composer.local.lock`** updates—not the committed **`composer.lock`**.

### Command reference

#### `link`

Use when the package is **already** listed in the **committed** root **`composer.json`** (`require` or `require-dev`). Composer Link records override state in **`packages-local.json`**, merges path `repositories` into **`composer.local.json`**, and may adjust the **active** constraint there so Composer can resolve your checkout.

| | |
|--|--|
| **Syntax** | `composer link <package> <path>` |
| **Arguments** | **`<package>`** — Composer name, e.g. `my-vendor/my-package`. **`<path>`** — Directory containing that package’s `composer.json` (relative to the project root or absolute). |
| **`--constraint` (`-c`)** | Optional. Override the version constraint written into **`composer.local.json`** (e.g. `@dev` if your local branch does not satisfy the original `^x.y`). |
| **`--no-update`** | Do not run **`composer update`** for that package after rewriting manifests. |
| **`--no-symlink`** | Set **`options.symlink: false`** on the path repository so Composer copies/junctions into **`vendor/`** instead of symlinking (useful on Windows without symlink rights). |

**Examples:**

```bash
# Minimal: package must already be in committed composer.json
composer link my-vendor/my-package ../packages/my-package

# Absolute path (e.g. macOS/Linux)
composer link my-vendor/my-package /Users/you/work/my-package

# Local checkout does not satisfy the caret constraint — force a dev constraint
composer link my-vendor/my-package ../packages/my-package --constraint=@dev

# Windows-friendly: install into vendor without creating a symlink
composer link my-vendor/my-package ../packages/my-package --no-symlink

# Only rewrite packages-local.json + composer.local.json; skip composer update
composer link my-vendor/my-package ../packages/my-package --no-update
```

---

#### `add`

Use to **introduce** a dependency that is **not** yet in the committed **`composer.json`**. This is a *bootstrap* flow (new package, often not on Packagist). **Not** the same as **`composer require`**, which only talks to Packagist/VCS remotes.

| | |
|--|--|
| **Syntax** | `composer add <package> <path>` |
| **Arguments** | **`<package>`** — Name from the library’s **`composer.json`**. **`<path>`** — Root of that package on disk. |
| **`--constraint` (`-c`)** | Constraint for **`composer.local.json`**. If omitted, **`@dev`** is used. |
| **`--no-dev`** | Add under **`require`** instead of **`require-dev`**. |
| **`--no-update`** | Skip **`composer update`** after adding. |
| **`--no-symlink`** | Same as on **`link`**: disable symlink for the path install. |

**Examples:**

```bash
# Add as require-dev (default) with default @dev constraint
composer add my-vendor/new-lib ./libs/new-lib

# Add as a production dependency
composer add my-vendor/new-lib ./libs/new-lib --no-dev

# Pin an explicit constraint in composer.local.json
composer add my-vendor/new-lib ./libs/new-lib --constraint=^0.1

# Rewrite manifests only; skip composer update
composer add my-vendor/new-lib ./libs/new-lib --no-update

# Copy/junction instead of symlink into vendor
composer add my-vendor/new-lib ./libs/new-lib --no-symlink
```

Later you can **`promote`** to a published constraint or **`unlink --remove`** to drop the dependency entirely.

---

#### `unlink`

Removes Composer Link’s state for a package and puts **`composer.local.json`** back in line: managed path repositories are rebuilt from whatever remains in **`packages-local.json`**, and constraints are restored or the requirement is removed.

| | |
|--|--|
| **Syntax** | `composer unlink <package>` |
| **`--no-update`** | Skip **`composer update`** / full **`update`** after unlinking. |
| **`--remove`** | **Bootstrap packages only.** Required to actually remove the package from **`require` / `require-dev`** in **`composer.local.json`**. Without **`--remove`**, **`unlink`** on a bootstrap package exits with an error (use **`promote`** instead if you want to keep the dependency with a published version). |

**Override mode** (package originally came from **`link`):** restores the **original** constraint from before linking and runs **`composer update <package>`** (unless **`--no-update`**).

**Bootstrap mode** (package came from **`add`):** you **must** pass **`--remove`** to remove the requirement; then Composer runs a full **`composer update`** with the local manifest so **`composer.local.lock`** reflects the removal.

**Examples:**

```bash
# Package was linked with "composer link" — restore the original constraint from composer.json
composer unlink my-vendor/my-package

# Same, but do not run composer update afterward
composer unlink my-vendor/my-package --no-update

# Package was added with "composer add" — remove it from require / require-dev entirely
composer unlink my-vendor/experimental-package --remove

# Remove bootstrap package without running composer update (manifests may be inconsistent until you run update)
composer unlink my-vendor/experimental-package --remove --no-update
```

---

#### `promote`

Stops treating a package as a Composer Link path override and sets a **published-style** constraint (Packagist/VCS) in **`composer.local.json`**, then updates the install.

| | |
|--|--|
| **Syntax** | `composer promote <package> <constraint>` |
| **Arguments** | **`<constraint>`** — What you would normally require from Packagist, e.g. **`^1.0`**, **`~2.3.0`**. |
| **`--no-update`** | Do not run **`composer update`** after changing the constraint. |

The package must already be tracked in **`packages-local.json`**. After **`promote`**, update the **committed** **`composer.json` / `composer.lock`** when you are ready for the team to use the same versions.

**Examples:**

```bash
# Switch this package to a published semver range (updates composer.local.json + lock unless --no-update)
composer promote my-vendor/my-package ^1.5

# Use a tilde or exact version
composer promote my-vendor/my-package ~2.3.0

# Set the constraint only; run composer update yourself later
composer promote my-vendor/my-package ^1.0 --no-update
```

---

#### `linked`

Lists every package Composer Link is managing from **`packages-local.json`**.

| | |
|--|--|
| **Syntax** | `composer linked` |
| **Output** | Table columns: package name, mode (**`override`** vs **`bootstrap`**), stored path, whether path installs use a symlink, active constraint, and whether the path still exists on disk. |

Named **`linked`** so it does not clash with Composer’s built-in **`composer status`**.

**Examples:**

```bash
composer linked
```

---

#### `refresh`

Rebuilds the **Composer Link–owned** path `repositories` inside **`composer.local.json`** from the current **`packages-local.json`**. Use after hand-editing the override file, or if **`composer.local.json`** drifted.

| | |
|--|--|
| **Syntax** | `composer refresh` |
| **`--no-update`** | Only rewrite **`composer.local.json`**; do **not** run **`composer update`** for linked packages. |

If **`packages-local.json`** is empty, managed repositories are stripped from **`composer.local.json`**.

**Examples:**

```bash
# Rebuild composer.local.json path repos from packages-local.json and run composer update for linked packages
composer refresh

# Only rewrite composer.local.json (no composer update)
composer refresh --no-update
```

---

#### `link-doctor`

Sanity-checks your project for Composer Link:

- **`.gitignore`** — If missing, creates one. If present, appends a marked block so **`packages-local.json`**, **`composer.local.json`**, and **`composer.local.lock`** (names follow your config) are ignored at the repo root.
- **Linked paths** — Verifies each stored path still exists.
- **Repositories** — Counts path repositories tagged with the plugin marker in **`composer.local.json`**.

Exits with a non-zero status if path checks fail (missing directories).

**Examples:**

```bash
# Creates or updates .gitignore, verifies linked paths, reports managed path repo count
composer link-doctor
```

---

#### `local-bootstrap`

Copies the **committed** **`composer.json`** to **`composer.local.json`** (basename configurable via **`local_composer_json`**). If **`composer.lock`** exists, copies it to the matching local lock file (Composer names it from the JSON file, e.g. **`composer.local.lock`**).

| | |
|--|--|
| **Syntax** | `composer local-bootstrap` |
| **`--force` (`-f`)** | Overwrite an existing **`composer.local.json`**. Without **`--force`**, an existing file causes an error. |

Run once per clone (or when you want a clean local manifest from **`composer.json`**). **`composer link`** / **`composer add`** can also create **`composer.local.json`** on first use if it is missing.

**Examples:**

```bash
# First-time copy of composer.json (+ composer.lock if present) → composer.local.json / composer.local.lock
composer local-bootstrap

# Replace existing composer.local.json from committed files
composer local-bootstrap --force
```

---

#### `local-install`

Runs **`composer install`** in your project with **`COMPOSER`** set to the **absolute path** of **`composer.local.json`**, so **`vendor/`** matches **`composer.local.lock`**. Equivalent to:

```bash
COMPOSER=composer.local.json composer install
```

(Use your real filename if you changed **`local_composer_json`**.)

| | |
|--|--|
| **Syntax** | `composer local-install` |
| **Requires** | **`composer.local.json`** must exist (**`local-bootstrap`** or a prior **`link`** / **`add`**). |
| **`--no-dev`** | Omit **`require-dev`** packages. |
| **`--no-progress`** | Disable the progress bar. |
| **`--prefer-dist`** / **`--prefer-source`** | Same meaning as core Composer. |
| **`--ignore-platform-reqs`** | Ignore PHP/extension platform requirements. |
| **`--no-scripts`** | Do not run **`scripts`** defined in the manifest. |

For other **`composer install`** flags, run Composer yourself with **`COMPOSER=…`** set.

**Examples:**

```bash
# Install vendor/ from composer.local.lock (same idea as: COMPOSER=composer.local.json composer install)
composer local-install

# Production-style install (no require-dev)
composer local-install --no-dev

# Prefer dist or source archives
composer local-install --prefer-dist
composer local-install --prefer-source

# Ignore PHP / ext version checks (e.g. quick try on wrong PHP)
composer local-install --ignore-platform-reqs

# Skip composer scripts (faster, but hooks won’t run)
composer local-install --no-scripts

# Quieter CI-style output
composer local-install --no-progress

# Combine flags
composer local-install --no-dev --prefer-dist --no-scripts
```

Equivalent manual invocation (from project root, default filename):

```bash
COMPOSER=composer.local.json composer install --no-dev
```

---

#### `link-help`

Prints a **formatted overview** in the terminal: what **`packages-local.json`** / **`composer.local.json`** are for, and a **table of every plugin command** with **Summary**, **Arguments** (positional), and **Options** (flags). Does not replace **`composer help`** on each command for full descriptions. Handy when you forget a command name or which flags exist.

| | |
|--|--|
| **Syntax** | `composer link-help` |
| **Also** | `composer help link-help` — longer text (same as passing **`--help`** to this command). |

**Examples:**

```bash
composer link-help
composer help link-help
```

---

## Typical workflows

### Override an existing release locally

```text
App requires: "your-vendor/your-package": "^1.5"
Local checkout: ../packages/your-package (composer.json version 1.6.0)
```

```bash
composer link your-vendor/your-package ../packages/your-package
```

If the local version is only a dev branch:

```bash
composer link your-vendor/your-package ../packages/your-package --constraint=@dev
```

### Bootstrap a package before Packagist

```bash
composer add your-vendor/new-package ../packages/new-package
```

Later:

```bash
composer promote your-vendor/new-package ^1.0
```

### After using `composer add`, use `composer link`

If you already used **add**, running **link** for the same package upgrades the stored state to **override** mode—watch the command output for messages.

### Keeping **`composer.json` / `composer.lock` pristine on disk**

That is the default now: path repositories and link-time constraint edits live in **`composer.local.json`**. The committed manifest stays as your team’s source of truth; **`composer install`** in CI uses **`composer.json`** only. Locally, use **`composer local-install`** (or export **`COMPOSER=composer.local.json`**) so **`vendor/`** reflects **`composer.local.lock`**.

---

## Security and Git

### Version control (how this is meant to work)

| Committed to the repo (shared) | Per developer only (not in Git) |
|-------------------------------|----------------------------------|
| Root **`composer.json`** and **`composer.lock`** | **`packages-local.json`**, **`composer.local.json`**, **`composer.local.lock`** — add to **`.gitignore`** |

**Composer** reads whichever manifest **`COMPOSER`** points at (default **`composer.json`**). It does **not** read **`packages-local.json`**; that file is Composer Link’s **state** only. Path `repositories` are merged into **`composer.local.json`** for local runs.

**Someone clones the project** and has no local files: normal. **`composer install`** uses the **committed** `composer.json` / lock.

**Local development:** run **`composer local-bootstrap`**, then **`composer link` / `add`**, then **`composer local-install`**. Before you **commit**, align **`composer.json` / `composer.lock`** with what the team should install (e.g. **`composer promote`**, **`unlink`**, or manual edits to the canonical manifest).

If you **delete** **`packages-local.json`** without unlinking first, **`composer.local.json`** may still contain Composer Link–managed **`repositories`** until you run **`composer refresh`** (empty override strips managed repos)—prefer **`composer unlink`** for a coherent cleanup.

- Review **`composer.lock`** after linking: it may record dev metadata; run **`unlink`** or **`promote`** before release branches if you need a lockfile that matches published packages only.
- **CI** should use a checkout **without** local overrides (no override file, **`composer.json` / lock** as on the default branch).

---

## QA: testing this package

Follow **[Quick start (smoke test)](#quick-start-smoke-test)** first. The notes below are extra context and release checks.

### Why `composer link` does not appear in *this* repository

Composer loads plugins from **installed packages** (`vendor/composer/installed.json`). The **root project** you clone here is not installed as a dependency of itself, so **running Composer inside this repo will not register the plugin**. That is expected. QA always uses a **separate main project** (root `composer.json`) that requires this package (from Packagist, Git, or a **path repository**).

### Extra checks (in your main project)

```bash
composer list | grep -E '^\s+(link|add|unlink|promote|linked|refresh|link-doctor|local-bootstrap|local-install|link-help)\s'
composer help link
```

If `smoke/smoke-test-package` is **already** in the main project’s `composer.json`, use **`composer link`** instead of **`composer add`** (same path: `.../composer-link/smoke/test-package`). Then exercise `composer refresh --no-update` and `composer unlink` as needed (`composer help unlink` — add `--remove` for bootstrap packages when appropriate).

### Release checklist (suggested)

- [ ] `composer require` succeeds in a clean main project; `allow-plugins` documented/works.
- [ ] `composer list` shows `link`, `add`, `unlink`, `promote`, `linked`, `refresh`, `link-doctor`, `local-bootstrap`, `local-install`, `link-help`.
- [ ] `composer link` + `composer linked` against a real path; `packages-local.json` and `composer.local.json` created; gitignore advice makes sense.
- [ ] `composer refresh` with and without `--no-update`.
- [ ] Run **`composer standards:check`** and **`composer tests`** in this repository (see below).

---

## Development of this repository

```bash
git clone git@github.com:stuarttodd-dev/composer-link.git
cd composer-link
docker compose build && docker compose up -d
docker exec composer-link composer install
docker exec composer-link composer standards:check
docker exec composer-link composer tests
```

PHP version in Docker matches production intent: **8.3**.

### Tests and coverage

- **`composer tests`** — runs [Pest](https://pestphp.com/) (unit + integration tests under `tests/`).
- **`composer test:coverage`** — same as **`./vendor/bin/pest --coverage`**. Requires a coverage driver (**PCOV** or **Xdebug**). Without one, Composer prints an error from Pest; install PCOV (`pecl install pcov` and enable the extension) or use Xdebug, then re-run.

Integration tests build temporary project directories and exercise **`ComposerLinkTasks`** without hitting the network (Composer **`update`** is skipped via **`--no-update`** where applicable).

---

## License

MIT (see `composer.json`).
