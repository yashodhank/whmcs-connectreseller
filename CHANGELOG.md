# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.3] - 2026-08-13

Sync TLDs table stayed empty while a red growl dumped the DataTables JSON
(`recordsTotal`, `status: true`). v3.0.2 only stopped mislabeling HTML as an
API-key error; it still growled `xhr.responseText` when DataTables hit
`parsererror` (WHMCS wrapping / invalid JSON) or when `message` was empty.

### Fixed

- DataTables AJAX parses the body as text, recovers a `data` array even on
  HTTP/parse errors, and never growls the raw JSON payload.
- `domainTable` cells are well-formed escaped HTML; JSON uses
  `JSON_UNESCAPED_UNICODE`. AJAX replies discard output buffers and send
  `Content-Type: application/json` before exit.

### Changed

- Module / addon version is **3.0.3**.

[3.0.3]: https://github.com/yashodhank/whmcs-connectreseller/releases/tag/v3.0.3

## [3.0.2] - 2026-08-13

Addon Sync/Automation AJAX must return JSON on errors; CSRF must not wrap
DataTables in HTML.

### Fixed

- Sync and Automation AJAX failures exit with JSON instead of WHMCS admin HTML,
  so DataTables no longer show a false “API key” growl.
- AJAX CSRF validation is non-fatal (JSON error) instead of `die()`.
- Automation tab action stays on `moduleLink`.
- Client JS no longer treats any HTML (`<`) as a missing API key.

### Changed

- Module / addon version is **3.0.2**.

[3.0.2]: https://github.com/yashodhank/whmcs-connectreseller/releases/tag/v3.0.2

## [3.0.1] - 2026-08-13

Addon admin UI / UX hardening and registrar logo binary repair.

### Fixed

- Registrar `logo.png` / `logo.gif` must remain valid binary PNG/GIF (not
  CRLF-mangled); Domain Registrars list no longer shows a broken image when
  logos are regenerated correctly.
- Sync TLDs and Automation DataTables init is page-gated (`#domainTable` /
  `#tldTable`) so the wrong table no longer leaves **Processing…** stuck.
- Addon AJAX uses an explicit module URL and admin CSRF token
  (`window.ConnectResellerAddon`); cron forms include CSRF tokens.
- Missing API key / `tldsync` failures return DataTables-shaped JSON instead of
  HTML warnings; Automation empty list explains that Sync/Import must run first.
- `tldsync` statusCode detection distinguishes list success from error objects.
- Cron manual-run results distinguish success vs error (e.g. KYC unavailable).
- Sync Import reloads the table after success; Import is disabled with zero
  selection and shows a real busy state.
- Font Awesome loads once from the addon header; Sync nav uses a sync icon.
- Sync table has a single Import control and clearer Price · Cost · Margin
  column headers; reduced forced `nowrap` horizontal scroll.

### Changed

- Module / addon version is **3.0.1**.
- Automation copy: “Status is Enabled”; empty-state callout links back to Sync.

[3.0.1]: https://github.com/yashodhank/whmcs-connectreseller/releases/tag/v3.0.1

## [3.0.0] - 2026-08-13

First community-maintained release of the ConnectReseller WHMCS registrar and
TLD-sync addon. Vendor identity was 2.5.1.

### Changed

- Module version is **3.0.0**. WHMCS registrar `MetaData()['APIVersion']` is the
  function-contract version and is **1.1** (the vendor value `2.5.1` was incorrect).
- `TestConnection` uses V11 Check Reseller Available funds (`availablefund`).
- `GetDomainInformation` is implemented for WHMCS 7.6+.
- License is MIT, with attribution to ConnectReseller for the original 2.5.1 code.
- PHP language ceiling in shipped code is 7.4; CI covers 7.4, 8.1, 8.2, and 8.3.

### Security

- API keys and passwords are redacted from `logModuleCall` output.
- Reseller AddClient passwords are random (`random_bytes`) and are not logged.
- KYC AJAX requires an authenticated admin session and WHMCS CSRF token.
- HTML from order info and KYC status is escaped.
- KYC schema is created on first use, not when `hooks.php` is included.
- cURL verifies TLS (`CURLOPT_SSL_VERIFYPEER` / `VERIFYHOST`).
- Hook API URLs are built with `http_build_query`.

### Fixed

- GetEPPCode IDN lookup used an undefined `$domainname`.
- Admin domain tab called leftover `switchepp_logoutepp()`.
- GetContactDetails billing/admin address mapping and `%30` encoding typo.
- SaveDNS returned an undefined `$values` on success.
- Addon `str_contains()` replaced with `strpos()` for PHP 7.4.
- `count(null)` in registrar and addon Helpers (fatal on PHP 8+).
- KYC cron undefined `$result` / `$client->clientId`.
- Removed `ini_set("display_errors", "1")` from the registrar module.

### Added

- Repository layout matching WHMCS install paths (`modules/`, `crons/`).
- Vendor API documentation under `docs/vendor/`.
- Composer dev tooling (PHPUnit, PHPStan, PHPCS).
- Injectable `ApiClient` with mocked HTTP unit tests.
- Tag-driven GitHub Release zip (`whmcs-connectreseller-VERSION.zip`) with SHA256.

### Refactor

- Shared `ApiClient` for registrar Helper, addon Helper, and hooks.
- Lock/unlock admin and client-area actions share `DomainLock`.
- Registrar actions live in `lib/` services (`Dns`, `Contacts`, `Transfers`, `Pricing`, `Nameservers`, `DomainLifecycle`) with thin `connectreseller_*` wrappers.
- TLD price sync fetches `tldsync` once instead of per WHMCS TLD.
- `.in` KYC detection no longer matches TLDs that merely end in `in` (e.g. `.berlin`).
- Price sync and KYC run from the WHMCS system cron (`AfterCronJob` / `DailyCronJob`); standalone `crons/` scripts are optional.

### Cron

- Skip KYC when the registrar is inactive or `APIKey` is empty; skip price sync
  when the addon is disabled or credentials are missing.
- `CronGuard` takes a `tblconfiguration` compare-and-set lock (1 hour TTL) so
  DailyCronJob, AfterCronJob, and leftover `crons/*.php` cannot overlap.
- Price sync last-run is a unix timestamp (`ConnectResellerPriceSyncLastRun`),
  not `mod_cron_status.time` (`TIME` / `H:i:s`). Empty **Cron Frequency**
  defaults to 24 hours.
- KYC iterates `mod_kycpending_domains` (Indian clients with pending `.in`
  domains), not every row in `tblclients`.
- Both jobs chunk work and persist a cursor so AfterCronJob / DailyCronJob stay
  inside a PHP time budget.
- CI runs static analysis once (PHP 8.2) and PHPUnit across 7.4/8.1/8.2/8.3 with
  Composer cache; release checksums use the zip basename for `sha256sum -c`.
- Price sync skips frequency checks **before** taking a lock; caches `tldsync`
  for in-progress passes; cursor is last processed domain pricing id.
- KYC continues in-progress chunks on `AfterCronJob` (not only once per day).
- JSON string bodies redact `APIKey` / `Password` / `authCode`; hook `callCurl`
  returns the real HTTP status; GetDomainInformation applies registration
  status; contact phones are no longer truncated on read.
- Addon Enable/Disable tab shows cron last-run / cursor and manual run buttons.

[3.0.0]: https://github.com/yashodhank/whmcs-connectreseller/releases/tag/v3.0.0
