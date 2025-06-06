#!/bin/bash

set -e  # Exit on error
set -u  # Treat unset variables as errors

# Upstream branch to rebase onto
UPSTREAM_BRANCH="upstream/release/v1.11.11"

echo "Starting interactive rebase onto $UPSTREAM_BRANCH..."
git fetch upstream
git rebase -i "$UPSTREAM_BRANCH"

while ! git rebase --continue 2>/dev/null; do
    echo ""
    echo "🔍 Resolving conflicts by keeping YOUR version (--ours)..."

    # Get list of conflicted files
    conflicted_files=$(git diff --name-only --diff-filter=U)

    for file in $conflicted_files; do
        echo "→ Resolving: $file"
        git checkout --ours "$file"
        git add "$file"
    done

    echo "✅ Conflicts resolved, continuing rebase..."
    git rebase --continue || true
done

echo "🎉 Rebase completed successfully!"
