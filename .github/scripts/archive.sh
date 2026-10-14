#!/usr/bin/env bash
# Packs the release archive from a checkout whose frontend is already built.
set -euo pipefail

rm -rf node_modules tests CONTRIBUTING.md flake.lock flake.nix phpunit.xml shell.nix
tar -czf panel.tar.gz * .editorconfig .env.example .gitignore .oxfmtrc.json
