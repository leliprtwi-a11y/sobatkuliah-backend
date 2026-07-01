#!/bin/bash

# Jalankan scheduler Laravel setiap menit di background
while true; do
  php artisan schedule:run --no-interaction >> storage/logs/scheduler.log 2>&1
  sleep 60
done &

echo "[start.sh] Scheduler berjalan di background (PID $!)"

# Jalankan web server di foreground (Railway butuh proses foreground)
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=${PORT:-8000}