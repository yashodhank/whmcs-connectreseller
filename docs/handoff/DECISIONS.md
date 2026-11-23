# DECISIONS — whmcs-connectreseller (3.0.9)

## KYC client lookup (2026-11-23 IST)
- Options: keep client_id param / domain row
- Pick: Domain row. If WHMCS never passed client_id, the gate starts working after deploy: Indian clients with .in domains on ConnectReseller will be held for KYC (intended behaviour, but new in practice). Owner sign-off needed.

