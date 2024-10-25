#!/usr/bin/env bash

set -o errexit -o errtrace -o nounset -o pipefail

: ${GITHUB_CHANGELOG_GENERATOR:=github_changelog_generator}
: ${GEM:=gem}

generate_changelog() {
    local -r sVersion="${1?One parameter required: <release-to-generate>}"

    if ! command -v "${GITHUB_CHANGELOG_GENERATOR}" >/dev/null 2>&1;then
        echo "This script requires the '${GITHUB_CHANGELOG_GENERATOR}' Ruby Gem"

        if ! command -v "${GEM}" >/dev/null 2>&1;then
            echo "Could not find the '${GEM}' command needed to install 'github_changelog_generator'!" >&2
            echo 'Aborting.'
            exit 67
        else
            echo "Installing '${GITHUB_CHANGELOG_GENERATOR}'..."
            gem install github_changelog_generator
        fi
    fi

    local -r sChangelog="$(
        "${GITHUB_CHANGELOG_GENERATOR}"                 \
            --user Dealerdirect                         \
            --project phpcodesniffer-composer-installer \
            --token "$(cat ~/.github-token)"            \
            --future-release "${sVersion}"              \
            --enhancement-label '### Changes'           \
            --bugs-label '### Fixes'                    \
            --issues-label '### Closes'                 \
            --usernames-as-github-logins                \
            --bug-labels 'bug - confirmed'              \
            --enhancement-labels  'improvement','documentation','builds / deploys / releases','feature request' \
            --exclude-labels 'bug - unconfirmed',"can't reproduce / won't fix",'invalid','triage' \
            --unreleased-only                           \
            --output '' 2>/dev/null
    )" || echo "There was a problem running '${GITHUB_CHANGELOG_GENERATOR}'"

    echo "${sChangelog}" | sed -E 's/\[\\(#[0-9]+)\]\([^)]+\)/\1/' | head -n -3
}

if [[ "${BASH_SOURCE[0]}" != "$0" ]]; then
    export -f generate_changelog
else
    generate_changelog "${@}"
    exit $?
fi
