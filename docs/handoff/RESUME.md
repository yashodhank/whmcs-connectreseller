# RESUME — whmcs-connectreseller (3.0.9)

For any CLI (Claude Code, Codex, agy, opencode) picking this up.
This repository is public: no secrets, hostnames, container names, digests or account ids in these files.


1. Read `docs/handoff/STATE.md`, then `TASKS.md` and `DECISIONS.md`.
2. Work in a worktree on `fix/cr-3.0.8-overlay-fold` (or a new `fix/<topic>` from origin/main). Never commit in a dirty user checkout.
3. Run `composer install && vendor/bin/phpunit && vendor/bin/phpcs && vendor/bin/phpstan analyse` before and after a change. A behaviour fix needs a failing test first.
4. Commit locally in small logical commits. Do not push or open PRs; the orchestrator does that.
5. Keep `.agent/`, `.serena/`, `.cursor/` out of git. No secrets; write ADMIN_PATH for the admin folder.
6. Rewrite `STATE.md` when you stop.
