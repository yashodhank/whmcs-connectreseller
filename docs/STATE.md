# STATE — ConnectReseller for WHMCS

**Updated:** 2026-11-23 IST · **Writer:** continuity pass (docs only)
**Version:** 3.0.9 on branch `fix/cr-3.0.8-overlay-fold` (main is 3.0.8 at e758336; CHANGELOG, registrar, addon and README agree)
**Prod:** LIVE-DRIFTED. Prod has been behind this repo; confirm the deployed version before assuming parity.

## In flight

- Local branch `fix/cr-3.0.8-overlay-fold` (2026-11-23 pre-9.1 audit, not pushed yet): 3.0.9: fold of prod #127/#111, KYC client lookup from the domain row, relative compile dir. Details: `docs/handoff/STATE.md`, `docs/handoff/TASKS.md`. Tests pass on PHP 7.4/8.3/8.4.

## Next

- Nothing queued by this pass. Any prod sync is CntbOps's step after Mr. Y's pick.

## Last session

- Continuity pass: new `AGENTS.md` from README, CI workflow and tree; pointers, ADR 0001, check script, `pre-push` hook. No product code changed.
