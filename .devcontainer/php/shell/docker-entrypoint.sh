#!/bin/bash

echo "=== Container Starting ==="
if [ -f "/usr/local/bin/fix-laravel-permissions.sh" ]; then
    echo "Running Laravel permission fix..."
    /usr/local/bin/fix-laravel-permissions.sh
fi
echo "Starting Apache..."
# 修正点：引数（$@）を渡す必要がある
exec docker-php-entrypoint "$@"