#!/usr/bin/env bash
set -euo pipefail

# Jeedom dependency install script for speedtest plugin
# Usage: install.sh [/tmp/progress_file]

PROGRESS_FILE="/tmp/jeedom/speedtest/dependance"
if [ "${1:-}" != "" ]; then
  PROGRESS_FILE="$1"
fi

mkdir -p "$(dirname "$PROGRESS_FILE")"
echo 0 > "$PROGRESS_FILE"

cleanup() { rm -f "$PROGRESS_FILE" 2>/dev/null || true; }
trap cleanup EXIT

log_step() {
  echo "$1" > "$PROGRESS_FILE" 2>/dev/null || true
  shift
  echo "[speedtest][deps] $*"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="${SCRIPT_DIR}/venv"

log_step 5 "Check Debian/Ubuntu (apt-get)"
if ! command -v apt-get >/dev/null 2>&1; then
  echo "[speedtest][deps] ERROR: apt-get not found (Debian/Ubuntu required)"
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive
sudo -n true >/dev/null 2>&1 && SUDO="sudo" || SUDO=""

log_step 15 "Install system prerequisites"
$SUDO apt-get update -y
$SUDO apt-get install -y --no-install-recommends \
  python3 python3-venv python3-pip ca-certificates

log_step 30 "Check python3 (>=3.8)"
python3 -c 'import sys; raise SystemExit(0 if sys.version_info >= (3,8) else 1)' \
  || { echo "[speedtest][deps] ERROR: python3 >= 3.8 required"; exit 1; }

log_step 50 "Create venv"
python3 -m venv "$VENV_DIR" || python3 -m venv "$VENV_DIR" --system-site-packages

log_step 65 "Upgrade pip"
"$VENV_DIR/bin/python3" -m pip install -U pip setuptools wheel

log_step 80 "Install speedtest-cli"
"$VENV_DIR/bin/python3" -m pip install speedtest-cli

log_step 90 "Health check"
"$VENV_DIR/bin/speedtest" --version
echo "[speedtest][check] speedtest-cli OK"

log_step 100 "Done"
