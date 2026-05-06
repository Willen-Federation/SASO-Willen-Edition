#!/bin/bash
# Webhook target for saso.sksl.jp.
#
# Keep this intentionally narrow: the webhook should refresh remote refs only,
# without modifying the checked-out live tree.

set -euo pipefail

cd "${SASO_WEBHOOK_WORKTREE:-$(cd "$(dirname "$0")" && pwd)}"

git fetch origin

exit 0
