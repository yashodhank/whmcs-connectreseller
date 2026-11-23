# Production deploy

Production runs this module from a baked WHMCS image built in the operator's
private overlay repository, not from a zip copied into a running container.
Zip copies into a container do not survive the next image rebuild.

The deploy procedure, environment contract, host and container names live in
that private repository. Do not copy them here: this repository is public.

A change merged here is not a deploy. The operator syncs the module into the
overlay (cherry-pick mode for both the addon and the registrar), builds the
image, and deploys it by digest.
