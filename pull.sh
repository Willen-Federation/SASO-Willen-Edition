#!/bin/bash
# Webhook target for saso.sksl.jp.
#
# Keep this intentionally narrow: the webhook updates the checked-out tree only
# by fast-forwarding to origin/main. It refuses divergent local changes.

set -euo pipefail

cd "${SASO_WEBHOOK_WORKTREE:-$(cd "$(dirname "$0")" && pwd)}"

git fetch origin main
git merge --ff-only origin/main

exit 0
