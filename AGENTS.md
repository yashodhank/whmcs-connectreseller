# AGENTS.md — whmcs-connectreseller

Cross-tool entry point. Read this, then `docs/STATE.md`.

## Purpose

Community fork (MIT, public repo) of ConnectReseller's free open-source WHMCS registrar plugin 2.5.1. Drop-in: the registrar directory name stays `connectreseller` so `tblregistrars` rows and production installs keep working. Adds security and PHP-compat fixes, tests, releases. Not affiliated with ConnectReseller.

## Layout

- `modules/registrars/connectreseller/` — registrar (`connectreseller.php`, `lib/`, `hooks.php`). Version: `CONNECTRESELLER_MODULE_VERSION`.
- `modules/addons/connect_reseller/` — admin addon (`connect_reseller.php`, `lib/Admin/Controller.php`, templates, assets, lang).
- `crons/` — `priceSync.php`, `kycVerification.php`.
- `tests/` — PHPUnit (`Unit/`, `Support/`, `fixtures/`). `scripts/` — `build-release.sh`, `check-logo-magic.sh`.
- `docs/production-deploy.md` (deploy notes), `docs/vendor/` (vendor API PDFs; do not edit).

## Commands (match `.github/workflows/ci.yml`)

```bash
composer install --prefer-dist --no-interaction
composer validate --no-check-publish --strict
composer test                  # phpunit
composer phpstan               # phpstan analyse
composer phpcs
composer check-logos           # registrar logo magic bytes
bash scripts/build-release.sh  # release zip dry run (modules/ + crons/ only)
bash scripts/check-continuity.sh
```

## Hard rules

- Shipped code stays PHP **7.4**-parseable (platform pin 7.4.33); supported WHMCS 8.x (PHP 7.4–8.3) and 9.x (PHP 8.2–8.3). Keep the registrar `APIVersion` `1.1` (a function contract, not the module version).
- A version bump touches every place that carries it: `CONNECTRESELLER_MODULE_VERSION`, addon `'version'`, `Admin/Controller::MODULE_VERSION`, `header.tpl` fallbacks, READMEs, `CHANGELOG.md`, `docs/STATE.md`.
- No API keys, reseller IDs or hostnames in the repo; write `ADMIN_PATH`. Attribution to the original plugin stays.
- Prod has lagged this repo (LIVE-DRIFTED). Never deploy from this desk; deploys belong to CntbOps after Mr. Y's pick.

## Branch / PR conventions

Worktree from `origin/main`; branch `feat|fix|docs|chore/<scope>`; Conventional Commit subjects (`fix(addon): ... (3.0.8)`); PR against `main`. This desk may merge module PRs after review; leave `whmcs-prod-new` PRs for WHMCS-Prod; never commit directly to `main`.

## Definition of done

Commands above pass; CHANGELOG entry for any version change; `docs/STATE.md` updated; no secrets.

## Continuity (tool-agnostic)

This repo follows the desk continuity standard (`CONTINUITY-STANDARD.md` in desk-os). Claude Code, Codex, agy, OpenCode, Cursor and any future tool start here.

- **Entry point:** this file (`AGENTS.md`). `CLAUDE.md`, `GEMINI.md` and `.cursor/rules/agents.mdc` only point here.
- **Session state:** `docs/STATE.md` — current version, what is in flight, next steps, last-session summary. Rewrite it at the end of every session (IST timestamps).
- **Decisions:** `docs/decisions/` (ADR files). Add one when the owner or an architecture choice is made. Do not rewrite old ADRs.
- **Agreement gate:** `bash scripts/check-continuity.sh` must pass before push (`docs/continuity.conf`). It fails if the version, CHANGELOG and STATE disagree.
- **Checkpoints:** small commits; push the feature or WIP branch before you stop; leave no uncommitted work in the worktree; never switch or dirty the user's primary checkout. Use a new worktree from `origin/<default-branch>`.
- **Desk checklists:** `SESSION-START.md` and `SESSION-END.md` in desk-os (`/workspace/whmcs-modules/desk-os/` or `~/Projects/whmcs-modules/desk-os/`).
- Enable hooks in each clone or worktree: `git config core.hooksPath .githooks`.

### Hard rules (fleet)

- No secrets in chat or commits. Never print the live admin folder name (write `ADMIN_PATH`). Never circumvent ionCube. Never touch the WHMCS core licence.
- Production is read-only for this desk. No live deploy. `whmcs-prod-new` merges belong to WHMCS-Prod; deploys belong to CntbOps after Mr. Y's own pick.
- Local checks only; no paid GitHub Actions. Use `gh-account` (unset `GIT_CONFIG_*` first). Repos stay private unless the owner says otherwise.
