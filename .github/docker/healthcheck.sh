#!/bin/ash
set -eu

required="nginx php-fpm"
if [ "$QUEUE_WORKER_ENABLED" = true ]; then
  required="$required queue-worker"
fi
if [ "$SCHEDULER_ENABLED" = true ]; then
  required="$required scheduler"
fi

# Query every program in one call; each supervisorctl run starts a Python interpreter.
# Fail on any line that is not RUNNING, and on missing lines if supervisord is unreachable.
# shellcheck disable=SC2086
supervisorctl status $required | awk -v expected="$(echo $required | wc -w)" '
  $2 != "RUNNING" { bad = 1 }
  END { exit (bad || NR != expected) }
'
curl --fail --silent --show-error --max-time 5 http://127.0.0.1:8080/ping | grep -qx pong
