#!/bin/ash
set -eu

check_process() {
  status=$(supervisorctl status "$1")
  printf '%s\n' "$status" | awk '$2 != "RUNNING" { exit 1 }'
}

check_process nginx
check_process php-fpm
if [ "$QUEUE_WORKER_ENABLED" = true ]; then
  check_process queue-worker
fi
if [ "$SCHEDULER_ENABLED" = true ]; then
  check_process scheduler
fi
curl --fail --silent --show-error --max-time 5 http://127.0.0.1:8080/ping | grep -qx pong
