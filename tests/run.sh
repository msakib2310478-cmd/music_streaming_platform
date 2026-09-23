#!/usr/bin/env sh
set -eu

for file in backend/*.php; do
    php -l "$file" >/dev/null
done

php backend/validate_setup.php
printf '%s\n' 'PHP syntax and database setup checks passed.'