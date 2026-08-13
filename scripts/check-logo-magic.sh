#!/usr/bin/env bash
# Fail if registrar logo binaries have invalid / CRLF-mangled magic bytes.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
png="$root/modules/registrars/connectreseller/logo.png"
gif="$root/modules/registrars/connectreseller/logo.gif"
failed=0

hex_prefix() {
  python3 - "$1" "$2" <<'PY'
import pathlib
import sys

path = pathlib.Path(sys.argv[1])
n = int(sys.argv[2])
sys.stdout.write(path.read_bytes()[:n].hex())
PY
}

ascii_prefix() {
  python3 - "$1" "$2" <<'PY'
import pathlib
import sys

path = pathlib.Path(sys.argv[1])
n = int(sys.argv[2])
sys.stdout.write(path.read_bytes()[:n].decode("ascii", errors="replace"))
PY
}

check_png() {
  local file="$1"
  if [[ ! -f "$file" ]]; then
    echo "MISSING: $file" >&2
    failed=1
    return
  fi
  local magic
  magic="$(hex_prefix "$file" 8)"
  if [[ "$magic" != "89504e470d0a1a0a" ]]; then
    echo "BAD PNG magic in $file: $magic (expected 89504e470d0a1a0a)" >&2
    failed=1
  else
    echo "OK PNG: $file ($magic)"
  fi
}

check_gif() {
  local file="$1"
  if [[ ! -f "$file" ]]; then
    echo "MISSING: $file" >&2
    failed=1
    return
  fi
  local magic
  magic="$(ascii_prefix "$file" 6)"
  if [[ "$magic" != "GIF87a" && "$magic" != "GIF89a" ]]; then
    echo "BAD GIF magic in $file: $(hex_prefix "$file" 8) (expected GIF87a/GIF89a)" >&2
    failed=1
  else
    echo "OK GIF: $file ($magic)"
  fi
}

check_png "$png"
check_gif "$gif"

if [[ "$failed" -ne 0 ]]; then
  echo "Logo magic-byte check failed." >&2
  exit 1
fi

echo "Logo magic-byte check passed."
