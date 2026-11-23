# STATE — whmcs-connectreseller (3.0.9)

Rewritten 2026-11-23 IST. Rewrite, don't append. ≤60 lines.
This repository is public: no secrets, hostnames, container names, digests or account ids in these files.


- Branch: `fix/cr-3.0.8-overlay-fold` (local, not pushed)
- Base: `e758336`; head: `git log --oneline e758336..HEAD`

## True now
- e758336 is origin/main (squash of PR #15, tree == 617cb7a); the prod overlay marker is accurate.
- Both prod patches (#127 compile dir, #111 KYC scoping) are folded; version is 3.0.9.
- PHPUnit 72 tests, phpcs, phpstan clean.

## Next action
Vendor review, then PR to main. After merge: re-bake addon + registrar, set both overlay markers to the merge SHA, and switch sync mode from cherry-pick if nothing else is overlay-only.

## Tests
`composer install && vendor/bin/phpunit && vendor/bin/phpcs && vendor/bin/phpstan analyse`
