#!/usr/bin/env bash
# Build WHMCS drop-in zip: modules/ + crons/ only.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

raw="${VERSION:-}"
if [[ -z "$raw" ]]; then
  raw="$(git describe --tags --exact-match 2>/dev/null || true)"
fi
if [[ -z "$raw" ]]; then
  echo "VERSION is required (e.g. v3.0.0 or 3.0.0)" >&2
  exit 1
fi

version="${raw#v}"
name="whmcs-connectreseller-${version}"
mkdir -p dist
rm -f "dist/${name}.zip" "dist/${name}.zip.sha256" dist/notes.md

# Deterministic-ish zip: no extra file attributes, only install paths.
(
  cd "$root"
  zip -X -r -q "dist/${name}.zip" modules crons -x "*.DS_Store" "*__MACOSX*"
)

if command -v sha256sum >/dev/null 2>&1; then
  sha256sum "dist/${name}.zip" | tee "dist/${name}.zip.sha256"
else
  shasum -a 256 "dist/${name}.zip" | tee "dist/${name}.zip.sha256"
fi

python3 - "$version" <<'PY'
import re
import sys
from pathlib import Path

version = sys.argv[1]
text = Path("CHANGELOG.md").read_text(encoding="utf-8")
pattern = rf"(## \[{re.escape(version)}\].*?)(?=\n## |\Z)"
match = re.search(pattern, text, re.S)
excerpt = match.group(1).strip() if match else f"Release {version}"
Path("dist/notes.md").write_text(excerpt + "\n", encoding="utf-8")
print("Wrote dist/notes.md")
PY

echo "Built dist/${name}.zip"
