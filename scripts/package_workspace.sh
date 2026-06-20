#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="${1:-${ROOT_DIR}/workspace/paygw_payphone}"
BUILD_DIR="${ROOT_DIR}/build"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_ZIP="${BUILD_DIR}/payphone_${STAMP}.zip"
STAGE_DIR="$(mktemp -d)"

if [[ ! -d "${SRC_DIR}" ]]; then
  echo "ERROR: workspace plugin not found: ${SRC_DIR}" >&2
  echo "Ensure workspace/paygw_payphone exists." >&2
  exit 1
fi

mkdir -p "${BUILD_DIR}"

# NOTE: The AMD module is ES6 (import/export) and is compiled to amd/build/ with
# `grunt amd` (rollup). Do NOT copy amd/src over amd/build here -- the committed
# amd/build/*.min.js is the rollup output and must ship as-is.

cleanup() {
  rm -rf "${STAGE_DIR}"
}
trap cleanup EXIT

# Moodle expects the gateway folder name inside payment/gateway/ to be "payphone"
# (the plugin name without the "paygw_" type prefix), not "paygw_payphone".
cp -a "${SRC_DIR}" "${STAGE_DIR}/payphone"

(
  cd "${STAGE_DIR}"
  zip -rq "${OUT_ZIP}" "payphone" -x '*.DS_Store' '*__MACOSX*' '*/.git/*'
)

echo "OK: package created"
echo "  ${OUT_ZIP}"
