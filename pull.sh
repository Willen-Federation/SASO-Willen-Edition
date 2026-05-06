#!/bin/bash
# Webhook target for saso.sksl.jp.
#
# Keep this intentionally narrow: the webhook should refresh remote refs only,
# without modifying the checked-out live tree.

set -euo pipefail

cd /home/schicksal/domains/saso.sksl.jp/public_html

git fetch origin

exit 0
