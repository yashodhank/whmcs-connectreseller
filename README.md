# ConnectReseller for WHMCS

Community-maintained fork of ConnectReseller’s free open-source WHMCS registrar
plugin **2.5.1**. First fork release identity: **3.0.0**.

This is a drop-in replacement: keep the registrar directory name
`connectreseller` so existing `tblregistrars` rows and production installs
continue to work.

**Not affiliated with ConnectReseller.** Original 2.5.1 code is copyright
ConnectReseller and was distributed as an open-source, free-of-charge WHMCS
plug-in. This repository adds security and PHP compatibility fixes, tests, and
GitHub Releases under the MIT license.

| Field | Value |
|-------|--------|
| Module version | `3.0.0` |
| WHMCS registrar `APIVersion` | `1.1` (function contract; **not** the module version) |
| PHP | **7.4–8.3** (WHMCS 8.x and 9.x) |
| Language ceiling in shipped code | PHP **7.4** only |
| Install paths | `modules/registrars/connectreseller`, `modules/addons/connect_reseller`, `crons/` |

## Attribution

- Original plugin: [ConnectReseller](https://www.connectreseller.com/) WHMCS
  registrar 2.5.1 (open-source / free of charge).
- Official API: [CR API Document V11](https://www.connectreseller.com/resources/downloads/CR_API_Document_V11.pdf)
  (copy in [`docs/vendor/CR_API_Document_V11.pdf`](docs/vendor/CR_API_Document_V11.pdf)).
- Module install notes from the vendor zip:
  [`docs/vendor/module_instruction.pdf`](docs/vendor/module_instruction.pdf).

## Requirements

- WHMCS 8.x (PHP 7.4–8.3) or WHMCS 9.x (PHP 8.2–8.3)
- PHP extensions: `curl`, `json`, `mbstring`
- A ConnectReseller reseller account and API key
- WHMCS server IP whitelisted in the ConnectReseller panel

## Installation

1. Download `whmcs-connectreseller-VERSION.zip` from
   [GitHub Releases](https://github.com/yashodhank/whmcs-connectreseller/releases).
2. Extract into the WHMCS root so these paths exist:
   - `modules/registrars/connectreseller/`
   - `modules/addons/connect_reseller/`
   - `crons/priceSync.php`
   - `crons/kycVerification.php`
3. In **Setup → Products/Services → Domain Registrars**, activate
   **connectreseller**.
4. Enter **API Key**. **Brand Id** is shown for compatibility with the vendor
   UI / docs; V11 authenticates with `APIKey` only. Use Brand Id as the
   reseller ID for connection tests when that field is set. **Coupon Code** is
   optional.
5. Optionally activate the **ConnectReseller** addon for TLD price sync.
   The WHMCS **system cron** (`cron.php`) runs price sync via `AfterCronJob`
   (honoring the addon **Cron Frequency** hours) and `.in` KYC via
   `DailyCronJob`. The standalone `crons/*.php` scripts are optional fallbacks.

WHMCS already emails customers. You can disable ConnectReseller panel customer
emails under **Settings → Panel settings → Customer Emails** to avoid duplicates.

## Configuration

| Setting | Purpose |
|---------|---------|
| API Key | V11 `APIKey` query parameter (required) |
| Brand Id | Vendor UI field; unused by most ESHOP calls; used as `resellerId` for Test Connection (`availablefund`) when present |
| Coupon Code | Passed on register/transfer when set |

## WHMCS contract notes

- `connectreseller_MetaData()['APIVersion']` is **1.1**, the registrar module
  function-contract version required by WHMCS. The vendor shipped `2.5.1` here,
  which is not a valid WHMCS API version.
- `RequestDelete` is **not implemented**. ConnectReseller V11 exposes
  suspend/lock, not domain delete. Use registrar lock/suspend at the panel, or
  wait for a future module release if an official delete endpoint appears.
- `TestConnection` uses V11 **Check Reseller Available funds**
  (`availablefund`).
- `GetDomainInformation` is implemented for WHMCS 7.6+ (preferred over
  separate nameserver + lock calls).

## Development

```bash
composer install
composer test
composer phpstan
composer phpcs
```

Shipped PHP must remain valid on **7.4**: no union types, `match`, constructor
property promotion, named arguments, nullsafe operator, enums, `str_contains`,
or `str_starts_with`. Typed properties and arrow functions are allowed.

CI runs PHP 7.4, 8.1, 8.2, and 8.3. Live API integration tests are opt-in via
`CONNECTRESELLER_API_KEY` and must never log that secret.

## Cron

WHMCS already runs `cron.php` (typically every five minutes, with a daily
pass). This fork hooks that schedule:

| Hook | When | Job |
|------|------|-----|
| Addon `AfterCronJob` | Every system cron, gated by **Cron Frequency** (hours) | TLD price sync |
| Registrar `DailyCronJob` | Once per day | `.in` KYC mail + register pending domains |

Standalone scripts remain for hosts that cannot rely on hooks (or for a one-off
run). Do **not** also crontab them if the WHMCS system cron is active, or jobs
may run twice (KYC is additionally gated to once per calendar day).

| File | Role |
|------|------|
| `crons/priceSync.php` | Optional fallback: same price sync as `AfterCronJob` |
| `crons/kycVerification.php` | Optional fallback: same KYC job as `DailyCronJob` (renamed from vendor `kycVerfication.php`) |

Point `$whmcspath` in a sibling `crons/config.php` if the WHMCS root is not the
parent of `crons/`.

## License

[MIT](LICENSE). Original 2.5.1 plugin: ConnectReseller.
