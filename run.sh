#!/bin/bash
# Run the app from repo root. Uses 44/ as document root.
cd "$(dirname "$0")"
export DB_USER="${DB_USER:-root}"
export DB_PASS="${DB_PASS:-root}"
echo "Starting at http://localhost:8000 (document root: 44/)"
exec php -S localhost:8000 -t 44
