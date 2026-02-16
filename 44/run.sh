#!/bin/bash
# Start the PHP dev server (Kubuntu / Linux).
# Usage: ./run.sh   or   php -S localhost:8000 after setting DB_* below.
cd "$(dirname "$0")"
export DB_USER="${DB_USER:-root}"
export DB_PASS="${DB_PASS:-root}"
echo "Starting at http://localhost:8000 (DB_USER=$DB_USER)"
exec php -S localhost:8000
