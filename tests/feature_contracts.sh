#!/usr/bin/env sh
set -eu

grep -q 'add_to_playlist' backend/api.php
grep -q 'enhancePlaylistActions' backend/home.php
grep -q 'search_suggestions.php' backend/home.php
grep -q 'AbortController' backend/home.php
grep -q 'loginRateLimitExceeded' backend/login.php
grep -q 'is_uploaded_file' backend/functions.php
grep -q 'notifications' backend/notifications.php
grep -q 'mobile-menu-toggle' backend/home.php
grep -q 'CREATE TABLE IF NOT EXISTS `notifications`' music_streaming_db.sql
grep -q 'CREATE TABLE IF NOT EXISTS `login_attempts`' music_streaming_db.sql

printf '%s\n' 'Feature contract checks passed.'