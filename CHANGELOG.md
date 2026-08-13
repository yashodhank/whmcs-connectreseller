# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - Unreleased

First community-maintained release of the ConnectReseller WHMCS registrar and
TLD-sync addon. Vendor identity was 2.5.1.

### Changed

- Module version is **3.0.0**. WHMCS registrar `MetaData()['APIVersion']` is the
  function-contract version and is **1.1** (the vendor value `2.5.1` was incorrect).
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

[3.0.0]: https://github.com/yashodhank/whmcs-connectreseller/releases/tag/v3.0.0
