#!/usr/bin/env sh
set -eu

PHP_BIN="${PHP_BIN:-/usr/bin/php}"
if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php)"
fi

for file in backend/*.php; do
    "$PHP_BIN" -l "$file" >/dev/null
done

"$PHP_BIN" backend/validate_setup.php
sh tests/feature_contracts.sh
printf '%s\n' 'PHP syntax and database setup checks passed.'