#!/bin/bash
set -e

while true; do
  start_ts=$(date +%s)
  php artisan schedule:run --no-interaction >> storage/logs/scheduler.log 2>&1
  elapsed=$(( $(date +%s) - start_ts ))
  sleep_for=$(( 60 - elapsed ))
  if [ $sleep_for -gt 0 ]; then sleep $sleep_for; else sleep 1; fi
done &

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"