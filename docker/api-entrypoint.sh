#!/bin/sh
# Smart CRM API container start-up.
# 0. wait for the database
# 1. migrate.php : creates the tables on an empty database, prints SKIP when already applied
# 2. seed_bt2.php: demo customers + demo accounts, never duplicates on re-run
# 3. PHP built-in server on port 8000 (front controller src/public/index.php)
set -e

# Wait until MySQL accepts connections (max. 60 s). docker compose already starts the API
# after the database is healthy; a hosting platform without start order needs this wait.
i=0
until php -r 'try { new PDO("mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: "3306"), getenv("DB_USER"), getenv("DB_PASSWORD") ?: ""); } catch (Throwable $e) { exit(1); }' 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -ge 30 ]; then
        echo "[api] Database still not reachable after 60 s, continuing (the migration will report the error)"
        break
    fi
    echo "[api] Waiting for the database at ${DB_HOST} ..."
    sleep 2
done

echo "[api] Migration -> ${DB_NAME}"
php database/migrate.php "${DB_NAME}"

echo "[api] Seed demo data -> ${DB_NAME}"
php database/seed_bt2.php "${DB_NAME}"

# API_BIND: listen address, default 0.0.0.0 (IPv4). Set API_BIND=[::] on a platform whose
# private network is IPv6 (TD-17).
echo "[api] Starting PHP server on ${API_BIND:-0.0.0.0}:8000"
exec php -S "${API_BIND:-0.0.0.0}:8000" -t src/public src/public/index.php
