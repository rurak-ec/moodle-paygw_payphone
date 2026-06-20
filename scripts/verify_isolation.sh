#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="${ROOT_DIR}/workspace/paygw_payphone"

fail=0
for path in \
  "${PLUGIN_DIR}/version.php" \
  "${PLUGIN_DIR}/classes/gateway.php" \
  "${PLUGIN_DIR}/lang/en/paygw_payphone.php" \
  "${PLUGIN_DIR}/amd/build/gateways_modal.min.js"; do
  if [[ ! -e "${path}" ]]; then
    echo "MISSING: ${path}" >&2
    fail=1
  fi
done

if [[ -d "${PLUGIN_DIR}/lang/es" ]]; then
  echo "WARNING: lang/es is present in the plugin; it must live in /translations (English-only ships)." >&2
  fail=1
fi

if [[ "${fail}" -eq 0 ]]; then
  echo "OK: workspace structure looks good."
fi
exit "${fail}"
