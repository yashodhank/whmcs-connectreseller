# Production deploy

Production on `my.securiace.com` is the baked
`securiace-dev/whmcs-prod-new` image, not a zip copied into the running
container. The canonical procedure, including the Dokploy environment
contract, lives in that repository at
[docs/deployments/dokploy.md](https://github.com/securiace-dev/whmcs-prod-new/blob/main/docs/deployments/dokploy.md).
Do not copy that contract here.

Dokploy compose name: `whmcs-production-jvjwfo`. Container:
`whmcs-production-jvjwfo-app-1` on `ded1`.

The v3.0.5 and v3.0.6 release-zip copies into that container do not survive
the next image rebuild. v3.0.5 was already lost that way.

`whmcs-prod-new` `scripts/sync-overlay-package.sh` cannot bake this module yet.
Catalog entries `connectreseller` and `connect_reseller` have no `sync.sourceRepo`.
The overlay
tree is stale and internally inconsistent: the registrar constant and the addon
`version` are 3.0.4, while the addon `Controller` still says 3.0.3. Wiring that
sync is a later change in `whmcs-prod-new`, followed by a digest deploy.

The PHP runtime upgrade is in `whmcs-prod-new` and is already 8.3.36 on
`my.securiace.com`. This module change is not a deploy.

After a later merge to `whmcs-prod-new` `main`, set only `VCS_REF`,
`BUILD_DATE`, and `WHMCS_IMAGE` to the installer-removed `repo@sha256` digest,
then `compose.update` and `compose.deploy`. Verify the container image id, the
module version constant, and `/` plus `/login`.
