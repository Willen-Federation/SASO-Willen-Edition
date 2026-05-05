#!/bin/bash
# Live-site publish for saso.sksl.jp.
#
# This directory IS the served document root. Local edits accumulate over time
# (server-specific config.json, the auth.providers route re-add, the ui()
# helpers autoload entry, etc.) — they are intentionally not committed and
# need to survive `git pull`. The flow below is the manual stash/pull/reapply
# dance that has worked in past sessions, automated and made conflict-safe:
#
#   1. Snapshot every unstaged tracked-file change into a labelled stash.
#   2. Fast-forward main from origin (refuses if origin diverges instead of
#      silently producing a merge commit).
#   3. Re-apply the stash with `apply` (NOT `pop`) so the stash entry is
#      preserved if anything goes wrong.
#   4. On conflict: hard-reset the conflicting paths back to upstream so the
#      site keeps booting, and leave the stash in `git stash list` for a
#      human to inspect. Exit non-zero so cron / the operator notices.
#   5. Reinstall PHP deps, touch the front controller to invalidate opcache.
#
# Conservative on purpose: we'd rather leave server-specific tweaks behind
# (recoverable from the stash) than serve a tree with conflict markers in it.

set -euo pipefail

cd /home/schicksal/domains/saso.sksl.jp/public_html

STASH_LABEL="live-site local edits pre-pull $(date +%F-%H%M)"
HAVE_STASH=0

# 1. Stash any unstaged tracked changes.
if [ -n "$(git diff --name-only HEAD)" ]; then
    git stash push -u=false -m "$STASH_LABEL"
    HAVE_STASH=1
fi

# 2. Fast-forward.
git fetch origin
git pull --ff-only origin main

# 3 & 4. Re-apply stash safely.
if [ "$HAVE_STASH" = "1" ]; then
    if git stash apply --quiet; then
        git stash drop --quiet
    else
        echo ""
        echo "============================================================"
        echo "WARNING: local edits conflicted with the upstream pull."
        echo "Conflicting files have been reset to the upstream version so"
        echo "the live site keeps serving. Your local edits are preserved"
        echo "in the most recent stash entry:"
        echo ""
        git stash list | head -1
        echo ""
        echo "Inspect the diff with:"
        echo "  git -C $(pwd) stash show -p stash@{0}"
        echo "Then re-apply per file as needed and re-run composer install."
        echo "============================================================"
        # Hard-reset just the unmerged paths.
        UNMERGED="$(git diff --name-only --diff-filter=U)"
        if [ -n "$UNMERGED" ]; then
            # shellcheck disable=SC2086
            git checkout HEAD -- $UNMERGED
        fi
        # Continue with composer / opcache so the rest of the pull lands.
    fi
fi

# 5. Composer + opcache.
if [ -x composer.phar ] || [ -f composer.phar ]; then
    php composer.phar install --no-dev --optimize-autoloader
elif command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader
else
    echo "WARNING: no composer binary found; skipping dependency install."
fi

touch index.php

# Surface non-zero on stash conflict so the caller notices.
if [ "$HAVE_STASH" = "1" ] && git stash list | grep -q "$STASH_LABEL"; then
    exit 1
fi

exit 0
