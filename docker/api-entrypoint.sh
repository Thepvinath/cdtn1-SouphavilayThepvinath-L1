#!/bin/sh
# Smart CRM API container start-up.
# 1. migrate.php : creates the tables on an empty database, prints SKIP when already applied
# 2. seed_bt2.php: demo customers + demo accounts, never duplicates on re-run
# 3. PHP built-in server on port 8000 (front controller src/public/index.php)
set -e

echo "[api] Migration -> ${DB_NAME}"
php database/migrate.php "${DB_NAME}"

echo "[api] Seed demo data -> ${DB_NAME}"
php database/seed_bt2.php "${DB_NAME}"

echo "[api] Starting PHP server on 0.0.0.0:8000"
exec php -S 0.0.0.0:8000 -t src/public src/public/index.php
