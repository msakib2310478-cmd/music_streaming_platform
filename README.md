# PulseFlow Music Streaming Platform

PulseFlow is a PHP and MySQL music streaming application. It provides user authentication, music discovery, playback tracking, playlists, favorites, ratings, artist follows, recommendations, subscriptions, and an administrator dashboard.

The project is intended for local development and demonstration. Docker supplies the MySQL database, while PHP's built-in development server serves the application.

## Contents

- [Features](#features)
- [Technology](#technology)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Quick Start With Docker](#quick-start-with-docker)
- [Run the Application](#run-the-application)
- [Configuration](#configuration)
- [Application Workflows](#application-workflows)
- [Database](#database)
- [Testing and Validation](#testing-and-validation)
- [Audio Files](#audio-files)
- [Troubleshooting](#troubleshooting)
- [Security Notes](#security-notes)
- [Known Limitations](#known-limitations)

## Features

### User features

- User registration and email/password login
- Role-aware user and administrator sessions
- Home dashboard with trending, popular, recent, liked, and rated tracks
- Search across artists, albums, tracks, and genres
- Track, album, artist, and genre detail pages
- HTML audio playback and stream-history recording
- Favorites
- Track ratings from 1 to 5
- Artist follow/unfollow
- Playlist creation, renaming, visibility changes, track management, and ordering
- Recently played tracks
- Spotify-style Up Next queue with add, remove, reorder, and clear actions
- Collaborative playlists with owner-managed invitations and collaborator track editing
- Multiple credited artists per track, including featured performers
- Genre-based recommendations
- Explainable SQL recommendations using listening, genre, follow, favorite, and rating signals
- Personal listening analytics with total time, top genre/artist, most played track, and seven-day activity
- Global daily, weekly, monthly, rated, favorited, and fastest-trending charts
- Artist performance dashboard with followers, streams, ratings, top songs, and device audience
- Subscription history and local Premium plan activation/cancellation

### Administrator features

- Administrator login with role checks
- User listing and deletion of non-admin users
- Artist, album, and track creation
- Album and track deletion
- Audio-file upload for new tracks
- Stream, device, genre, listener, favorite, and rating analytics

## Technology

- PHP 8.2+ with PDO
- MySQL 8.0+
- Docker Compose
- HTML, CSS, and browser JavaScript
- PHP sessions and CSRF tokens for state-changing forms

## Project Structure

```text
.
├── backend/
│   ├── db.php                 Database connection
│   ├── auth.php               Session and role helpers
│   ├── functions.php          Shared application helpers
│   ├── login.php              User login handler
│   ├── register.php           User registration handler
│   ├── home.php               Database-backed user dashboard
│   ├── analytics.php          Personal listening analytics
│   ├── charts.php             Global charts and growth-based trending
│   ├── artist-dashboard.php   Artist performance reporting by artist id
│   ├── queue.php              Authenticated Up Next queue
│   ├── api.php                User actions and state changes
│   ├── search.php             Search page and search logging
│   ├── playlist.php           Playlist detail and editing
│   ├── user_pages.php         Favorites, history, follows, recommendations, subscriptions
│   ├── admin-dashboard.php    Administrator dashboard
│   ├── admin_actions.php      Administrator write actions
│   ├── validate_setup.php     Database schema validator
│   └── uploads/audio/         Local audio upload destination
├── frontend/
│   ├── user-login.html        User login page
│   ├── user-register.html     User registration page
│   ├── admin-login.html        Administrator login page
│   └── *.css                  Shared authentication and dashboard styles
├── music_streaming_db.sql     Complete schema, seed data, and views
├── docker-compose.yml         MySQL development service
├── tests/run.sh               PHP and database smoke checks
└── index.html                 Public landing page
```

## Requirements

Install or make available:

- Docker Engine
- Docker Compose v2
- PHP 8.2 or newer
- PHP PDO MySQL extension
- PHP Fileinfo extension for audio uploads
- Git, if cloning the repository

Check the local tools:

```sh
docker --version
docker compose version
php -v
php -m | grep -E 'PDO|pdo_mysql|fileinfo'
```

## Quick Start With Docker

From the repository root, start MySQL:

```sh
docker compose up -d db
```

The Compose service:

- Uses MySQL 8.4
- Publishes MySQL on `127.0.0.1:3306`
- Creates database `music_streaming_db`
- Creates user `music_app`
- Uses password `music_app_local` for local development
- Imports `music_streaming_db.sql` automatically on first initialization
- Stores database data in the Docker volume `pulseflow_mysql_data`

Wait until the service is healthy, then validate the PHP connection and schema:

```sh
docker compose ps
php backend/validate_setup.php
```

Expected validator output:

```text
Database setup is valid: 17 required tables, 4 reporting views, and all required columns found.
```

The SQL file is imported only when the database volume is created. To destroy the local database and recreate it from the SQL file:

```sh
docker compose down -v
docker compose up -d db
```

The `-v` option permanently deletes the local Docker database volume and all data stored in it.

For an existing database, apply the updated `music_streaming_db.sql` migration through MySQL or phpMyAdmin. It creates `playback_queue`, `playlist_collaborators`, and `track_artists`, then backfills each existing track's album artist as its primary performer. The migration uses `IF NOT EXISTS` and `INSERT IGNORE` for these additions.

## Run the Application

Start the PHP development server from the repository root:

```sh
php -S 0.0.0.0:8000 -t .
```

Open:

- Public page: `http://localhost:8000/`
- User login: `http://localhost:8000/frontend/user-login.html`
- User registration: `http://localhost:8000/frontend/user-register.html`
- Administrator login: `http://localhost:8000/frontend/admin-login.html`

The PHP server must be started with the repository root as its document root. The frontend forms use relative paths to the `backend/` directory.

## Configuration

`backend/db.php` reads these environment variables and falls back to the Docker development values:

| Variable | Default | Description |
| --- | --- | --- |
| `MUSIC_DB_HOST` | `127.0.0.1` | MySQL hostname or IP address |
| `MUSIC_DB_NAME` | `music_streaming_db` | Database name |
| `MUSIC_DB_USER` | `music_app` | Database user |
| `MUSIC_DB_PASS` | `music_app_local` | Database password |

For a non-Docker database, configure the variables before starting PHP:

```sh
export MUSIC_DB_HOST=127.0.0.1
export MUSIC_DB_NAME=music_streaming_db
export MUSIC_DB_USER=music_app
export MUSIC_DB_PASS='your-password'
php backend/validate_setup.php
```

Do not commit real passwords or production credentials. Use a secrets manager or the hosting platform's environment configuration in deployment environments.

## Application Workflows

### Register and log in as a user

1. Open `frontend/user-register.html`.
2. Submit first name, last name, email, and password.
3. Log in through `frontend/user-login.html`.
4. The application redirects the user to `backend/user-dashbord.php`, which loads the database-backed dashboard from `backend/home.php`.

### Log in as an administrator

The SQL seed includes an administrator account. For local development, `backend/create_admin.php` can create or reset the configured admin account. Run it only in a trusted local environment, because it displays the password in the response.

```sh
php backend/create_admin.php
```

Then open `frontend/admin-login.html`.

### Manage music

Administrators can add artists, albums, and tracks from the dashboard. Tracks can include an audio upload. Albums and tracks can be deleted; database foreign keys cascade related favorites, ratings, playlist entries, and stream history.

### Manage subscriptions

The subscription page lets a logged-in user activate or cancel the local Premium plan. This updates both `users.subscription_type` and the `subscriptions` table in a transaction.

This is a local account-state workflow, not a payment integration. It does not charge money.

## Database

The complete schema is in [music_streaming_db.sql](music_streaming_db.sql). It contains the core catalog tables:

- `users`
- `artists`
- `albums`
- `tracks`
- `playlists`
- `stream_history`

It also contains feature tables:

- `genres` and `track_genres`
- `playlist_tracks`
- `favorites`
- `artist_follows`
- `ratings`
- `search_history`
- `subscriptions`

The schema file also adds media metadata columns, reporting indexes, sample data, foreign keys, and live reporting views: `vw_user_listening_daily`, `vw_track_chart_metrics`, `vw_trending_tracks`, and `vw_artist_dashboard_metrics`. These views support personal analytics, global charts, growth-based trending, and artist reporting without storing duplicated summary data.

The new normalized tables are used as follows:

- `playback_queue`: private ordered queue rows owned by one user.
- `playlist_collaborators`: unique playlist/user memberships; only playlist owners can invite or remove members.
- `track_artists`: performer credits with `artist_role` and `display_order`; album ownership remains in `albums.artist_id`.

All queue and playlist mutation requests go through `backend/api.php`, require a session and CSRF token, and scope their SQL writes to the authenticated user or an authorized playlist membership.

## Testing and Validation

Run the repeatable smoke checks:

```sh
sh tests/run.sh
```

The script:

1. Runs `php -l` against every backend PHP file.
2. Runs `backend/validate_setup.php`.

Test only PHP syntax:

```sh
for file in backend/*.php; do php -l "$file" || exit 1; done
```

Test the Docker service:

```sh
docker compose ps
docker compose logs --tail=50 db
```

The repository also supports a transactional PHP write/read check:

```sh
php -r 'require "backend/db.php"; $pdo->beginTransaction(); $stmt=$pdo->prepare("INSERT INTO artists (artist_name, genre, country) VALUES (:name, :genre, :country)"); $stmt->execute(["name"=>"__php_connection_test__", "genre"=>"Test", "country"=>"Test"]); $pdo->rollBack(); echo "PHP database write test passed.\n";'
```

## Audio Files

Uploaded audio is stored in `backend/uploads/audio/` and is ignored by Git. The administrator track form accepts MP3, WAV, OGG, and M4A-compatible uploads up to 50 MB.

The SQL seed includes demo paths such as `/demo/audio/...`. Those paths are metadata placeholders unless matching files are supplied by the deployment. For reliable playback, upload real audio files through the administrator dashboard or replace the seeded `tracks.audio_url` values with URLs served by your media storage.

For production, store audio in object storage or a dedicated media service instead of the PHP application filesystem.

## Troubleshooting

### `Connection refused` on port 3306

Check that Docker is running and start the database:

```sh
docker compose up -d db
docker compose ps
php backend/validate_setup.php
```

### `MySQL server has gone away` immediately after startup

MySQL may still be importing the schema. Wait until `docker compose ps` reports `(healthy)`, then run:

```sh
php backend/validate_setup.php
```

### Missing tables or columns

The schema was probably only partially imported or an old Docker volume is being reused. Recreate the development database:

```sh
docker compose down -v
docker compose up -d db
php backend/validate_setup.php
```

This deletes all local database data.

### Updates do not save

Confirm all of the following:

1. `php backend/validate_setup.php` passes.
2. The user is logged in with the correct role.
3. The form includes a valid CSRF token.
4. The PHP server is serving the repository root.
5. Browser requests are reaching `backend/api.php` or `backend/admin_actions.php`.

### Audio does not play

Check that the track has a valid `audio_url`, that the file exists, and that the PHP server can serve it. Seeded `/demo/audio/` paths require matching files.

## Security Notes

- Use HTTPS outside local development.
- Set `MUSIC_DB_PASS` through an environment or secret-management system.
- Change the default Docker passwords before sharing the service or deploying it.
- Remove or protect `backend/create_admin.php` outside local development.
- Add login rate limiting before production use.
- Connect a real payment provider only after adding verified webhooks and payment-state reconciliation.
- Store uploaded media outside the web application filesystem in production.
- Review upload limits, MIME validation, and server-side authorization before accepting untrusted media at scale.
- Do not expose raw database exception messages to end users.

## Known Limitations

- Social login buttons are visual placeholders; Google, Apple, and SSO authentication are not implemented.
- The Remember Me controls are visual only and do not create persistent authentication cookies.
- Password reset and profile editing are not implemented.
- Premium activation is local-only and does not process payments.
- The project has smoke validation but does not yet include a full browser end-to-end test suite.
- Demo media URLs may not point to actual audio files.

## Stopping the Development Services

Stop the PHP server with `Ctrl+C`. Stop the database container with:

```sh
docker compose stop db
```

To stop and remove the container while preserving database data:

```sh
docker compose down
```