# PulseFlow Music Streaming Platform

## Setup

### Docker database

The quickest local setup is:

```sh
docker compose up -d db
php backend/validate_setup.php
```

The MySQL container exposes port `3306` and imports `music_streaming_db.sql` automatically on its first start. To recreate the database from the SQL file, run `docker compose down -v` before starting it again. This deletes the Docker database volume.

1. Create a MySQL database user that can create and modify `music_streaming_db`.
2. Import the complete `music_streaming_db.sql` file into MySQL.
3. Configure the connection with environment variables:

```sh
export MUSIC_DB_HOST=127.0.0.1
export MUSIC_DB_NAME=music_streaming_db
export MUSIC_DB_USER=music_app
export MUSIC_DB_PASS=your-local-password
```

4. Run the schema check:

```sh
php backend/validate_setup.php
```

5. Serve the project through PHP or Apache with `backend/` available to the frontend. Log in through `frontend/user-login.html`.

## Implemented Features

- User registration and role-based authentication
- Catalog browsing, search, genres, recommendations, favorites, ratings, follows, and playlists
- Stream history and admin analytics
- Admin artist, album, and track management
- Admin audio uploads stored under `backend/uploads/audio`
- Local subscription activation and cancellation workflow

The subscription flow currently updates the local database only. Connect a payment provider and webhook verification before accepting real payments.