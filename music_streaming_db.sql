-- Complete music streaming schema, sample data, indexes, and views.
-- Import this single file to initialize the complete project database.
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 11, 2026 at 04:47 PM
-- Server version: 8.0.43
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `music_streaming_db`
--

CREATE DATABASE IF NOT EXISTS music_streaming_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE music_streaming_db;

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `album_id` int NOT NULL,
  `artist_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `release_date` date DEFAULT NULL,
  `album_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `albums`
--

INSERT INTO `albums` (`album_id`, `artist_id`, `title`, `release_date`, `album_type`) VALUES
(1, 1, 'After Hours', '2020-03-20', 'Studio'),
(2, 2, '1989', '2014-10-27', 'Studio'),
(3, 3, 'Aashiqui 2', '2013-04-26', 'Soundtrack');

-- --------------------------------------------------------

--
-- Table structure for table `artists`
--

CREATE TABLE `artists` (
  `artist_id` int NOT NULL,
  `artist_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `genre` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artists`
--

INSERT INTO `artists` (`artist_id`, `artist_name`, `genre`, `country`, `created_at`) VALUES
(1, 'The Weeknd', 'R&B', 'Canada', '2026-09-10 19:34:32'),
(2, 'Taylor Swift', 'Pop', 'USA', '2026-09-10 19:34:32'),
(3, 'Arijit Singh', 'Playback/Bollywood', 'India', '2026-09-10 19:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `playlist_id` int NOT NULL,
  `user_id` int NOT NULL,
  `playlist_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `is_public` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`playlist_id`, `user_id`, `playlist_name`, `is_public`, `created_at`) VALUES
(1, 1, 'Chill Vibes', 1, '2026-09-10 19:34:33'),
(2, 1, 'Workout Mix', 0, '2026-09-10 19:34:33'),
(3, 2, 'Road Trip Songs', 1, '2026-09-10 19:34:33');

-- --------------------------------------------------------

--
-- Table structure for table `stream_history`
--

CREATE TABLE `stream_history` (
  `stream_id` int NOT NULL,
  `user_id` int NOT NULL,
  `track_id` int NOT NULL,
  `played_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `device_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `session_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stream_history`
--

INSERT INTO `stream_history` (`stream_id`, `user_id`, `track_id`, `played_at`, `device_type`, `session_id`) VALUES
(1, 1, 1, '2026-09-10 19:34:33', 'mobile', 'sess_001'),
(2, 1, 3, '2026-09-10 19:34:33', 'web', 'sess_002'),
(3, 2, 5, '2026-09-10 19:34:33', 'mobile', 'sess_003');

-- --------------------------------------------------------

--
-- Table structure for table `tracks`
--

CREATE TABLE `tracks` (
  `track_id` int NOT NULL,
  `album_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `duration_seconds` int DEFAULT NULL,
  `explicit` tinyint(1) DEFAULT '0',
  `track_number` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tracks`
--

INSERT INTO `tracks` (`track_id`, `album_id`, `title`, `duration_seconds`, `explicit`, `track_number`) VALUES
(1, 1, 'Blinding Lights', 200, 0, 1),
(2, 1, 'Save Your Tears', 215, 0, 2),
(3, 2, 'Shake It Off', 219, 0, 1),
(4, 2, 'Blank Space', 231, 0, 2),
(5, 3, 'Tum Hi Ho', 262, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `subscription_type` enum('free','premium') COLLATE utf8mb4_general_ci DEFAULT 'free',
  `role` enum('user','admin') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `subscription_type`, `role`, `created_at`) VALUES
(1, 'johndoe', 'john@example.com', '$2y$10$examplehashvalue1', 'premium', 'user', '2026-09-10 19:34:33'),
(2, 'sarahk', 'sarah@example.com', '$2y$10$examplehashvalue2', 'free', 'user', '2026-09-10 19:34:33'),
(3, 'adminuser', 'admin@example.com', '$2y$10$examplehashvalue3', 'premium', 'admin', '2026-09-10 19:34:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`album_id`),
  ADD KEY `artist_id` (`artist_id`);

--
-- Indexes for table `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`artist_id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`playlist_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stream_history`
--
ALTER TABLE `stream_history`
  ADD PRIMARY KEY (`stream_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `track_id` (`track_id`);

--
-- Indexes for table `tracks`
--
ALTER TABLE `tracks`
  ADD PRIMARY KEY (`track_id`),
  ADD KEY `album_id` (`album_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `albums`
--
ALTER TABLE `albums`
  MODIFY `album_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `artists`
--
ALTER TABLE `artists`
  MODIFY `artist_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `playlist_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stream_history`
--
ALTER TABLE `stream_history`
  MODIFY `stream_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tracks`
--
ALTER TABLE `tracks`
  MODIFY `track_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `albums_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`artist_id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `stream_history`
--
ALTER TABLE `stream_history`
  ADD CONSTRAINT `stream_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stream_history_ibfk_2` FOREIGN KEY (`track_id`) REFERENCES `tracks` (`track_id`) ON DELETE CASCADE;

--
-- Constraints for table `tracks`
--
ALTER TABLE `tracks`
  ADD CONSTRAINT `tracks_ibfk_1` FOREIGN KEY (`album_id`) REFERENCES `albums` (`album_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- BEGIN COMPLETE SCHEMA UPGRADE
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS genres (
    genre_id INT NOT NULL AUTO_INCREMENT,
    genre_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    PRIMARY KEY (genre_id),
    UNIQUE KEY uq_genres_name (genre_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS track_genres (
    track_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (track_id, genre_id),
    KEY idx_track_genres_genre_id (genre_id),
    CONSTRAINT fk_track_genres_track FOREIGN KEY (track_id) REFERENCES tracks (track_id) ON DELETE CASCADE,
    CONSTRAINT fk_track_genres_genre FOREIGN KEY (genre_id) REFERENCES genres (genre_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS playlist_tracks (
    playlist_id INT NOT NULL,
    track_id INT NOT NULL,
    track_order INT DEFAULT NULL,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (playlist_id, track_id),
    KEY idx_playlist_tracks_track_id (track_id),
    CONSTRAINT fk_playlist_tracks_playlist FOREIGN KEY (playlist_id) REFERENCES playlists (playlist_id) ON DELETE CASCADE,
    CONSTRAINT fk_playlist_tracks_track FOREIGN KEY (track_id) REFERENCES tracks (track_id) ON DELETE CASCADE,
    CONSTRAINT chk_playlist_tracks_order CHECK (track_order IS NULL OR track_order > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS favorites (
    user_id INT NOT NULL,
    track_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, track_id),
    KEY idx_favorites_track_id (track_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_favorites_track FOREIGN KEY (track_id) REFERENCES tracks (track_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS artist_follows (
    user_id INT NOT NULL,
    artist_id INT NOT NULL,
    followed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, artist_id),
    KEY idx_artist_follows_artist_id (artist_id),
    CONSTRAINT fk_artist_follows_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_artist_follows_artist FOREIGN KEY (artist_id) REFERENCES artists (artist_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS ratings (
    user_id INT NOT NULL,
    track_id INT NOT NULL,
    rating TINYINT NOT NULL,
    rated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, track_id),
    KEY idx_ratings_track_id (track_id),
    CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    CONSTRAINT fk_ratings_track FOREIGN KEY (track_id) REFERENCES tracks (track_id) ON DELETE CASCADE,
    CONSTRAINT chk_ratings_value CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS search_history (
    search_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    search_query VARCHAR(255) NOT NULL,
    searched_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (search_id),
    KEY idx_search_history_user_id (user_id),
    KEY idx_search_history_searched_at (searched_at),
    CONSTRAINT fk_search_history_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
    subscription_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    PRIMARY KEY (subscription_id),
    KEY idx_subscriptions_user_id (user_id),
    KEY idx_subscriptions_status (status),
    CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Additive changes to legacy tables. The helper procedure makes these rerunnable.
DROP PROCEDURE IF EXISTS schema_add_column;
DELIMITER $$
CREATE PROCEDURE schema_add_column(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_definition VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND column_name = p_column_name
    ) THEN
        SET @schema_sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_definition);
        PREPARE schema_stmt FROM @schema_sql;
        EXECUTE schema_stmt;
        DEALLOCATE PREPARE schema_stmt;
    END IF;
END$$
DELIMITER ;

CALL schema_add_column('artists', 'bio', 'TEXT NULL');
CALL schema_add_column('artists', 'profile_image', 'VARCHAR(500) NULL');
CALL schema_add_column('artists', 'verified', 'BOOLEAN NOT NULL DEFAULT FALSE');
CALL schema_add_column('albums', 'cover_image', 'VARCHAR(500) NULL');
CALL schema_add_column('albums', 'description', 'TEXT NULL');
CALL schema_add_column('tracks', 'audio_url', 'VARCHAR(500) NULL');
CALL schema_add_column('tracks', 'cover_image', 'VARCHAR(500) NULL');
CALL schema_add_column('tracks', 'lyrics', 'TEXT NULL');
CALL schema_add_column('tracks', 'bitrate', 'INT NULL');
CALL schema_add_column('tracks', 'audio_format', 'VARCHAR(20) NULL');
CALL schema_add_column('tracks', 'sample_rate', 'INT NULL');
CALL schema_add_column('stream_history', 'duration_played', 'INT NULL');
CALL schema_add_column('stream_history', 'completed', 'BOOLEAN NOT NULL DEFAULT FALSE');
DROP PROCEDURE schema_add_column;

-- Only indexes absent from the legacy dump are added.
DROP PROCEDURE IF EXISTS schema_add_index;
DELIMITER $$
CREATE PROCEDURE schema_add_index(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_columns VARCHAR(255)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND index_name = p_index_name
    ) THEN
        SET @schema_sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD INDEX `', p_index_name, '` (', p_columns, ')');
        PREPARE schema_stmt FROM @schema_sql;
        EXECUTE schema_stmt;
        DEALLOCATE PREPARE schema_stmt;
    END IF;
END$$
DELIMITER ;

CALL schema_add_index('stream_history', 'idx_stream_history_played_at', '`played_at`');
CALL schema_add_index('stream_history', 'idx_stream_history_user_played_at', '`user_id`, `played_at`');
CALL schema_add_index('stream_history', 'idx_stream_history_track_played_at', '`track_id`, `played_at`');
DROP PROCEDURE schema_add_index;

-- Fill legacy rows with additive metadata without changing their existing identity.
UPDATE artists SET bio = 'Canadian singer and songwriter known for atmospheric R&B and pop.' WHERE artist_name = 'The Weeknd' AND bio IS NULL;
UPDATE artists SET bio = 'American singer-songwriter whose catalog spans pop, country, and folk.' WHERE artist_name = 'Taylor Swift' AND bio IS NULL;
UPDATE artists SET bio = 'Indian playback singer celebrated for expressive Bollywood vocals.' WHERE artist_name = 'Arijit Singh' AND bio IS NULL;
UPDATE artists SET verified = TRUE WHERE artist_name IN ('The Weeknd', 'Taylor Swift', 'Arijit Singh');
UPDATE albums SET cover_image = CONCAT('/demo/covers/', LOWER(REPLACE(title, ' ', '-')), '.jpg'), description = CONCAT('Demo metadata for ', title, '.') WHERE cover_image IS NULL;
UPDATE tracks SET audio_url = CONCAT('/demo/audio/', LOWER(REPLACE(title, ' ', '-')), '.mp3'), cover_image = CONCAT('/demo/covers/', LOWER(REPLACE(title, ' ', '-')), '.jpg'), bitrate = 320, audio_format = 'mp3', sample_rate = 44100 WHERE audio_url IS NULL;

INSERT IGNORE INTO genres (genre_name, description) VALUES
('Pop', 'Accessible contemporary popular music.'),
('R&B', 'Rhythm and blues with soul and groove influences.'),
('Hip-Hop', 'Rap, beats, and hip-hop culture.'),
('Rock', 'Guitar-driven rock and alternative music.'),
('Electronic', 'Electronic production, dance, and synth-based music.'),
('Classical', 'Orchestral, chamber, and concert music.'),
('Bollywood', 'Indian film music and playback traditions.'),
('Indie', 'Independent and alternative music.'),
('Jazz', 'Improvisation-led jazz and jazz-influenced music.'),
('Country', 'Country, folk, and Americana traditions.'),
('K-Pop', 'Contemporary Korean popular music.'),
('Lo-fi', 'Relaxed, textured, low-fidelity listening music.');

-- Add demo listeners only when their email is not already present.
-- Demo password for these accounts is: demo123
INSERT IGNORE INTO users (username, email, password_hash, subscription_type, role) VALUES
('alexm', 'alex@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'free', 'user'),
('mariar', 'maria@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'premium', 'user'),
('davidk', 'david@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'free', 'user'),
('oliviaw', 'olivia@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'premium', 'user'),
('liamj', 'liam@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'free', 'user'),
('sofiap', 'sofia@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'premium', 'user'),
('noahb', 'noah@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'free', 'user'),
('emilyc', 'emily@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'premium', 'user'),
('ethanw', 'ethan@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'free', 'user'),
('avaj', 'ava@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'premium', 'user'),
('masonl', 'mason@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'free', 'user'),
('isabellag', 'isabella@example.com', '$2y$10$him0ZIipYKCUMvKBFK2ZCOAA7JKulaqJ9CUZQrquu5Z5CwqBQlgcC', 'premium', 'user');

-- Artists are matched by name so existing IDs and rows remain untouched.
INSERT INTO artists (artist_name, genre, country, bio, verified)
SELECT s.artist_name, s.genre, s.country, s.bio, s.verified
FROM (SELECT 'Dua Lipa' artist_name, 'Pop' genre, 'UK' country, 'British pop artist known for dance-pop and disco influences.' bio, TRUE verified UNION ALL
      SELECT 'Drake', 'Hip-Hop', 'Canada', 'Canadian rapper, singer, and songwriter blending hip-hop and R&B.', TRUE UNION ALL
      SELECT 'Beyonce', 'R&B', 'USA', 'American singer and performer with a landmark R&B and pop catalog.', TRUE UNION ALL
      SELECT 'Ed Sheeran', 'Pop', 'UK', 'English singer-songwriter known for melodic pop and acoustic songwriting.', TRUE UNION ALL
      SELECT 'Billie Eilish', 'Pop', 'USA', 'American artist known for intimate vocals and inventive production.', TRUE UNION ALL
      SELECT 'Bruno Mars', 'Pop', 'USA', 'American singer and multi-instrumentalist with funk and soul influences.', TRUE UNION ALL
      SELECT 'Adele', 'Pop', 'UK', 'English vocalist known for soulful ballads and powerful performances.', TRUE UNION ALL
      SELECT 'Coldplay', 'Rock', 'UK', 'British rock band with expansive, melodic anthems.', TRUE UNION ALL
      SELECT 'Imagine Dragons', 'Rock', 'USA', 'American rock band blending alternative rock and electronic textures.', TRUE UNION ALL
      SELECT 'Bad Bunny', 'Hip-Hop', 'Puerto Rico', 'Puerto Rican artist at the center of modern Latin urban music.', TRUE UNION ALL
      SELECT 'BLACKPINK', 'K-Pop', 'South Korea', 'South Korean girl group known for polished pop and hip-hop.', TRUE UNION ALL
      SELECT 'Hans Zimmer', 'Classical', 'Germany', 'Composer known for cinematic orchestral and electronic scores.', TRUE UNION ALL
      SELECT 'A.R. Rahman', 'Bollywood', 'India', 'Indian composer and producer celebrated for film music innovation.', TRUE UNION ALL
      SELECT 'Nucleya', 'Electronic', 'India', 'Indian electronic artist blending bass music with regional sounds.', TRUE UNION ALL
    SELECT 'Norah Jones', 'Jazz', 'USA', 'American singer-songwriter combining jazz, soul, and pop.', TRUE) s
WHERE NOT EXISTS (SELECT 1 FROM artists a WHERE a.artist_name = s.artist_name);

-- Add 17 albums, bringing the clean sample set to 20 albums.
INSERT INTO albums (artist_id, title, release_date, album_type, cover_image, description)
SELECT a.artist_id, s.title, s.release_date, s.album_type, CONCAT('/demo/covers/', s.slug, '.jpg'), s.description
FROM (SELECT 'Dua Lipa' artist_name, 'Future Nostalgia' title, '2020-03-27' release_date, 'Studio' album_type, 'future-nostalgia' slug, 'Dance-pop with a bright retro-futurist pulse.' description UNION ALL
      SELECT 'Drake', 'Views', '2016-04-29', 'Studio', 'views', 'Atmospheric hip-hop and R&B from Toronto.' UNION ALL
      SELECT 'Beyonce', 'Renaissance', '2022-07-29', 'Studio', 'renaissance', 'A celebratory dance and house-inflected pop record.' UNION ALL
      SELECT 'Ed Sheeran', 'Divide', '2017-03-03', 'Studio', 'divide', 'A wide-ranging pop record with acoustic roots.' UNION ALL
      SELECT 'Billie Eilish', 'HIT ME HARD AND SOFT', '2024-05-17', 'Studio', 'hit-me-hard-and-soft', 'A recent, intimate pop album with dynamic production.' UNION ALL
      SELECT 'Bruno Mars', '24K Magic', '2016-11-18', 'Studio', '24k-magic', 'Funk, soul, and pop built for the dance floor.' UNION ALL
      SELECT 'Adele', '30', '2021-11-19', 'Studio', '30', 'Soulful reflections on change and renewal.' UNION ALL
      SELECT 'Coldplay', 'Moon Music', '2024-10-04', 'Studio', 'moon-music', 'A recent melodic rock journey with electronic color.' UNION ALL
      SELECT 'Imagine Dragons', 'Evolve', '2017-06-23', 'Studio', 'evolve', 'Arena-ready alternative rock and pop.' UNION ALL
      SELECT 'Bad Bunny', 'Un Verano Sin Ti', '2022-05-06', 'Studio', 'un-verano-sin-ti', 'Warm, genre-blending Latin urban music.' UNION ALL
      SELECT 'BLACKPINK', 'THE ALBUM', '2020-10-02', 'Studio', 'the-album', 'Confident K-Pop with pop and hip-hop hooks.' UNION ALL
      SELECT 'Hans Zimmer', 'Interstellar', '2014-11-17', 'Soundtrack', 'interstellar', 'Cinematic score inspired by space and time.' UNION ALL
      SELECT 'A.R. Rahman', 'Rockstar', '2011-11-01', 'Soundtrack', 'rockstar', 'Genre-spanning Bollywood film music.' UNION ALL
      SELECT 'Nucleya', 'Tota Myna', '2015-07-31', 'Studio', 'tota-myna', 'Indian electronic bass and festival energy.' UNION ALL
      SELECT 'Norah Jones', 'Come Away with Me', '2002-02-26', 'Studio', 'come-away-with-me', 'Warm jazz-pop songwriting and understated vocals.' UNION ALL
      SELECT 'Taylor Swift', 'Midnights', '2022-10-21', 'Studio', 'midnights', 'Late-night synth pop and reflective songwriting.' UNION ALL
      SELECT 'Arijit Singh', 'Arijit Singh Essentials', '2023-06-15', 'Compilation', 'arijit-singh-essentials', 'A demo collection of modern Bollywood favorites.') s
JOIN artists a ON a.artist_name = s.artist_name
WHERE NOT EXISTS (SELECT 1 FROM albums al WHERE al.artist_id = a.artist_id AND al.title = s.title);

-- Add three representative tracks for every new album (51 tracks plus the five legacy tracks).
INSERT INTO tracks (album_id, title, duration_seconds, explicit, track_number, audio_url, cover_image, lyrics, bitrate, audio_format, sample_rate)
SELECT al.album_id, s.track_title, s.duration_seconds, s.explicit, s.track_number,
       CONCAT('/demo/audio/', s.slug, '.mp3'), al.cover_image,
       CONCAT('Demo lyrics placeholder for ', s.track_title, '.'), 320, 'mp3', 44100
FROM (SELECT 'Future Nostalgia' album_title, 'Levitating' track_title, 203 duration_seconds, 0 explicit, 1 track_number, 'levitating' slug UNION ALL
      SELECT 'Future Nostalgia', 'Physical', 194, 0, 2, 'physical' UNION ALL SELECT 'Future Nostalgia', 'Break My Heart', 221, 0, 3, 'break-my-heart' UNION ALL
      SELECT 'Views', 'One Dance', 173, 0, 1, 'one-dance' UNION ALL SELECT 'Views', 'Hotline Bling', 267, 0, 2, 'hotline-bling' UNION ALL SELECT 'Views', 'Too Good', 263, 0, 3, 'too-good' UNION ALL
      SELECT 'Renaissance', 'Break My Soul', 278, 0, 1, 'break-my-soul' UNION ALL SELECT 'Renaissance', 'Cuff It', 236, 0, 2, 'cuff-it' UNION ALL SELECT 'Renaissance', 'Alien Superstar', 212, 0, 3, 'alien-superstar' UNION ALL
      SELECT 'Divide', 'Shape of You', 234, 0, 1, 'shape-of-you' UNION ALL SELECT 'Divide', 'Perfect', 263, 0, 2, 'perfect' UNION ALL SELECT 'Divide', 'Galway Girl', 170, 0, 3, 'galway-girl' UNION ALL
      SELECT 'HIT ME HARD AND SOFT', 'Lunch', 179, 0, 1, 'lunch' UNION ALL SELECT 'HIT ME HARD AND SOFT', 'Birds of a Feather', 210, 0, 2, 'birds-of-a-feather' UNION ALL SELECT 'HIT ME HARD AND SOFT', 'Chihiro', 303, 0, 3, 'chihiro' UNION ALL
    SELECT '24K Magic', '24K Magic', 226, 0, 1, '24k-magic' UNION ALL SELECT '24K Magic', 'Thats What I Like', 206, 0, 2, 'thats-what-i-like' UNION ALL SELECT '24K Magic', 'Versace on the Floor', 261, 0, 3, 'versace-on-the-floor' UNION ALL
      SELECT '30', 'Easy on Me', 224, 0, 1, 'easy-on-me' UNION ALL SELECT '30', 'Oh My God', 225, 0, 2, 'oh-my-god' UNION ALL SELECT '30', 'My Little Love', 389, 0, 3, 'my-little-love' UNION ALL
    SELECT 'Moon Music', 'feelslikeimfallinginlove', 238, 0, 1, 'feelslikeimfallinginlove' UNION ALL SELECT 'Moon Music', 'Jupiter', 251, 0, 2, 'jupiter' UNION ALL SELECT 'Moon Music', 'We Pray', 219, 0, 3, 'we-pray' UNION ALL
      SELECT 'Evolve', 'Believer', 204, 0, 1, 'believer' UNION ALL SELECT 'Evolve', 'Thunder', 187, 0, 2, 'thunder' UNION ALL SELECT 'Evolve', 'Whatever It Takes', 201, 0, 3, 'whatever-it-takes' UNION ALL
    SELECT 'Un Verano Sin Ti', 'Me Porto Bonito', 178, 1, 1, 'me-porto-bonito' UNION ALL SELECT 'Un Verano Sin Ti', 'Titi Me Pregunto', 243, 1, 2, 'titi-me-pregunto' UNION ALL SELECT 'Un Verano Sin Ti', 'Ojitos Lindos', 258, 0, 3, 'ojitos-lindos' UNION ALL
      SELECT 'THE ALBUM', 'How You Like That', 181, 0, 1, 'how-you-like-that' UNION ALL SELECT 'THE ALBUM', 'Lovesick Girls', 194, 0, 2, 'lovesick-girls' UNION ALL SELECT 'THE ALBUM', 'Pretty Savage', 203, 0, 3, 'pretty-savage' UNION ALL
      SELECT 'Interstellar', 'Dreaming of the Crash', 221, 0, 1, 'dreaming-of-the-crash' UNION ALL SELECT 'Interstellar', 'Cornfield Chase', 129, 0, 2, 'cornfield-chase' UNION ALL SELECT 'Interstellar', 'No Time for Caution', 244, 0, 3, 'no-time-for-caution' UNION ALL
      SELECT 'Rockstar', 'Kun Faya Kun', 470, 0, 1, 'kun-faya-kun' UNION ALL SELECT 'Rockstar', 'Phir Se Ud Chala', 285, 0, 2, 'phir-se-ud-chala' UNION ALL SELECT 'Rockstar', 'Nadaan Parindey', 395, 0, 3, 'nadaan-parindey' UNION ALL
      SELECT 'Tota Myna', 'Aaja', 221, 0, 1, 'aaja' UNION ALL SELECT 'Tota Myna', 'Laung Gawacha', 238, 0, 2, 'laung-gawacha' UNION ALL SELECT 'Tota Myna', 'Tunak Tunak', 207, 0, 3, 'tunak-tunak' UNION ALL
    SELECT 'Come Away with Me', 'Dont Know Why', 186, 0, 1, 'dont-know-why' UNION ALL SELECT 'Come Away with Me', 'Come Away with Me', 196, 0, 2, 'come-away-with-me' UNION ALL SELECT 'Come Away with Me', 'Shoot the Moon', 234, 0, 3, 'shoot-the-moon' UNION ALL
      SELECT 'Midnights', 'Anti-Hero', 200, 0, 1, 'anti-hero' UNION ALL SELECT 'Midnights', 'Lavender Haze', 202, 0, 2, 'lavender-haze' UNION ALL SELECT 'Midnights', 'Maroon', 218, 0, 3, 'maroon' UNION ALL
      SELECT 'Arijit Singh Essentials', 'Kesariya', 268, 0, 1, 'kesariya' UNION ALL SELECT 'Arijit Singh Essentials', 'Chaleya', 200, 0, 2, 'chaleya' UNION ALL SELECT 'Arijit Singh Essentials', 'Tujhe Kitna Chahne Lage', 290, 0, 3, 'tujhe-kitna-chahne-lage') s
JOIN albums al ON al.title = s.album_title
WHERE NOT EXISTS (SELECT 1 FROM tracks t WHERE t.album_id = al.album_id AND t.title = s.track_title);

-- Legacy tracks receive normalized metadata and all tracks now have a playable demo URL.
UPDATE tracks t JOIN albums al ON al.album_id = t.album_id
SET t.audio_url = COALESCE(t.audio_url, CONCAT('/demo/audio/', LOWER(REPLACE(t.title, ' ', '-')), '.mp3')),
    t.cover_image = COALESCE(t.cover_image, al.cover_image), t.lyrics = COALESCE(t.lyrics, CONCAT('Demo lyrics placeholder for ', t.title, '.')),
    t.bitrate = COALESCE(t.bitrate, 320), t.audio_format = COALESCE(t.audio_format, 'mp3'), t.sample_rate = COALESCE(t.sample_rate, 44100);

-- Multiple genres per track demonstrate the normalized many-to-many relationship.
INSERT IGNORE INTO track_genres (track_id, genre_id)
SELECT t.track_id, g.genre_id
FROM tracks t JOIN genres g
WHERE (t.title IN ('Blinding Lights', 'Save Your Tears', 'Levitating', 'Physical', 'Break My Heart', 'Anti-Hero', 'Lavender Haze') AND g.genre_name IN ('Pop', 'R&B'))
   OR (t.title IN ('Blinding Lights', 'Physical', 'Break My Soul', 'Cuff It', '24K Magic') AND g.genre_name = 'Electronic')
    OR (t.title IN ('One Dance', 'Hotline Bling', 'Too Good', 'Me Porto Bonito', 'Titi Me Pregunto', 'Pretty Savage') AND g.genre_name = 'Hip-Hop')
   OR (t.title IN ('Shake It Off', 'Blank Space', 'Shape of You', 'Perfect', 'Easy on Me', 'Oh My God', 'Thunder') AND g.genre_name = 'Pop')
   OR (t.title IN ('Believer', 'Whatever It Takes', 'Jupiter', 'Nadaan Parindey') AND g.genre_name IN ('Rock', 'Indie'))
   OR (t.title IN ('Tum Hi Ho', 'Kun Faya Kun', 'Kesariya', 'Chaleya', 'Tujhe Kitna Chahne Lage') AND g.genre_name = 'Bollywood')
   OR (t.title IN ('Dreaming of the Crash', 'Cornfield Chase', 'No Time for Caution') AND g.genre_name = 'Classical')
   OR (t.title IN ('Aaja', 'Laung Gawacha', 'Tunak Tunak') AND g.genre_name IN ('Electronic', 'Indie'))
    OR (t.title IN ('Dont Know Why', 'Come Away with Me', 'Shoot the Moon') AND g.genre_name IN ('Jazz', 'Indie'))
   OR (t.title IN ('How You Like That', 'Lovesick Girls', 'Pretty Savage') AND g.genre_name = 'K-Pop')
   OR (t.title IN ('Birds of a Feather', 'Chihiro', 'Lunch', 'Maroon') AND g.genre_name = 'Pop');

-- Existing playlists are used by name; no playlist is deleted or recreated.
INSERT IGNORE INTO playlist_tracks (playlist_id, track_id, track_order)
SELECT p.playlist_id, t.track_id, x.track_order
FROM (SELECT 'Chill Vibes' playlist_name, 'Save Your Tears' track_title, 1 track_order UNION ALL
    SELECT 'Chill Vibes', 'Birds of a Feather', 2 UNION ALL SELECT 'Chill Vibes', 'Dont Know Why', 3 UNION ALL SELECT 'Chill Vibes', 'Cornfield Chase', 4 UNION ALL
      SELECT 'Workout Mix', 'Blinding Lights', 1 UNION ALL SELECT 'Workout Mix', 'Physical', 2 UNION ALL SELECT 'Workout Mix', 'Believer', 3 UNION ALL SELECT 'Workout Mix', 'Levitating', 4 UNION ALL
      SELECT 'Road Trip Songs', 'Shape of You', 1 UNION ALL SELECT 'Road Trip Songs', 'Perfect', 2 UNION ALL SELECT 'Road Trip Songs', 'Thunder', 3 UNION ALL SELECT 'Road Trip Songs', 'Kesariya', 4) x
JOIN playlists p ON p.playlist_name = x.playlist_name JOIN tracks t ON t.title = x.track_title;

INSERT IGNORE INTO favorites (user_id, track_id)
SELECT x.user_id, t.track_id FROM (SELECT 1 user_id, 'Blinding Lights' track_title UNION ALL SELECT 1, 'Levitating' UNION ALL SELECT 1, 'Kesariya' UNION ALL SELECT 2, 'Shape of You' UNION ALL SELECT 2, 'Tum Hi Ho' UNION ALL SELECT 2, 'Kun Faya Kun' UNION ALL SELECT 3, 'Believer' UNION ALL SELECT 3, 'Birds of a Feather' UNION ALL SELECT 3, 'One Dance') x JOIN tracks t ON t.title = x.track_title;

INSERT IGNORE INTO artist_follows (user_id, artist_id)
SELECT x.user_id, a.artist_id FROM (SELECT 1 user_id, 'The Weeknd' artist_name UNION ALL SELECT 1, 'Arijit Singh' UNION ALL SELECT 1, 'Dua Lipa' UNION ALL SELECT 2, 'Taylor Swift' UNION ALL SELECT 2, 'Adele' UNION ALL SELECT 2, 'Coldplay' UNION ALL SELECT 3, 'Drake' UNION ALL SELECT 3, 'BLACKPINK' UNION ALL SELECT 3, 'Hans Zimmer') x JOIN artists a ON a.artist_name = x.artist_name;

INSERT IGNORE INTO ratings (user_id, track_id, rating)
SELECT x.user_id, t.track_id, x.rating FROM (SELECT 1 user_id, 'Blinding Lights' track_title, 5 rating UNION ALL SELECT 2, 'Blinding Lights', 4 UNION ALL SELECT 3, 'Blinding Lights', 5 UNION ALL SELECT 1, 'Levitating', 5 UNION ALL SELECT 2, 'Levitating', 4 UNION ALL SELECT 3, 'Levitating', 5 UNION ALL SELECT 1, 'Shape of You', 4 UNION ALL SELECT 2, 'Shape of You', 5 UNION ALL SELECT 1, 'Tum Hi Ho', 5 UNION ALL SELECT 2, 'Tum Hi Ho', 5 UNION ALL SELECT 3, 'Tum Hi Ho', 4 UNION ALL SELECT 1, 'Believer', 3 UNION ALL SELECT 2, 'Believer', 4 UNION ALL SELECT 3, 'One Dance', 4 UNION ALL SELECT 1, 'Birds of a Feather', 5 UNION ALL SELECT 2, 'Birds of a Feather', 5 UNION ALL SELECT 3, 'Anti-Hero', 4) x JOIN tracks t ON t.title = x.track_title;

-- Subscription/search rows are guarded by their natural sample keys for reruns.
INSERT INTO subscriptions (user_id, plan_name, start_date, end_date, status, amount)
SELECT x.user_id, x.plan_name, x.start_date, x.end_date, x.status, x.amount
FROM (SELECT 1 user_id, 'Premium' plan_name, '2026-08-01' start_date, NULL end_date, 'active' status, 9.99 amount UNION ALL
      SELECT 2, 'Free', '2026-01-01', NULL, 'active', 0.00 UNION ALL
      SELECT 3, 'Premium', '2025-01-01', '2026-01-31', 'expired', 9.99 UNION ALL
      SELECT 2, 'Premium', '2025-03-01', '2025-08-31', 'cancelled', 9.99) x
WHERE NOT EXISTS (SELECT 1 FROM subscriptions s WHERE s.user_id = x.user_id AND s.plan_name = x.plan_name AND s.start_date = x.start_date);

INSERT INTO search_history (user_id, search_query, searched_at)
SELECT x.user_id, x.search_query, x.searched_at
FROM (SELECT 1 user_id, 'Taylor Swift' search_query, '2026-09-16 09:10:00' searched_at UNION ALL SELECT 1, 'Blinding Lights', '2026-09-15 18:20:00' UNION ALL SELECT 2, 'The Weeknd', '2026-09-14 12:05:00' UNION ALL SELECT 2, 'Bollywood', '2026-09-13 20:44:00' UNION ALL SELECT 3, 'Pop', '2026-09-12 10:15:00' UNION ALL SELECT 3, 'Rock', '2026-09-11 16:30:00') x
WHERE NOT EXISTS (SELECT 1 FROM search_history h WHERE h.user_id = x.user_id AND h.search_query = x.search_query AND h.searched_at = x.searched_at);

-- Generate 180 varied demo streams without touching the three legacy records.
CREATE TEMPORARY TABLE demo_track_pool AS
SELECT track_id, ROW_NUMBER() OVER (ORDER BY track_id) - 1 AS ordinal FROM tracks;
CREATE TEMPORARY TABLE demo_user_pool AS
SELECT user_id, ROW_NUMBER() OVER (ORDER BY user_id) - 1 AS ordinal FROM users;
SET @demo_track_count = (SELECT COUNT(*) FROM demo_track_pool);
SET @demo_user_count = (SELECT COUNT(*) FROM demo_user_pool);

INSERT INTO stream_history (user_id, track_id, played_at, device_type, session_id, duration_played, completed)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL SELECT n + 1 FROM seq WHERE n < 180
)
SELECT u.user_id, t.track_id,
       CURRENT_TIMESTAMP - INTERVAL MOD(s.n, 28) DAY - INTERVAL MOD(s.n * 379, 86400) SECOND,
       ELT(1 + MOD(s.n, 4), 'mobile', 'web', 'desktop', 'tablet'),
       CONCAT('demo_stream_', LPAD(s.n, 3, '0')),
       CASE WHEN MOD(s.n, 5) = 0 THEN GREATEST(30, 60 + MOD(s.n * 41, 180)) ELSE tr.duration_seconds END,
       CASE WHEN MOD(s.n, 5) = 0 THEN FALSE ELSE TRUE END
FROM seq s
JOIN demo_user_pool u ON u.ordinal = MOD(s.n, @demo_user_count)
JOIN demo_track_pool t ON t.ordinal = MOD(s.n * 7, @demo_track_count)
JOIN tracks tr ON tr.track_id = t.track_id
WHERE NOT EXISTS (SELECT 1 FROM stream_history h WHERE h.session_id = CONCAT('demo_stream_', LPAD(s.n, 3, '0')));

DROP TEMPORARY TABLE demo_track_pool;
DROP TEMPORARY TABLE demo_user_pool;

CREATE OR REPLACE VIEW vw_most_played_tracks AS
SELECT t.track_id, t.title, ar.artist_name, COUNT(sh.stream_id) AS play_count
FROM tracks t JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id
LEFT JOIN stream_history sh ON sh.track_id = t.track_id
GROUP BY t.track_id, t.title, ar.artist_name;

CREATE OR REPLACE VIEW vw_most_liked_tracks AS
SELECT t.track_id, t.title, ar.artist_name, COUNT(f.user_id) AS favorite_count
FROM tracks t JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id
LEFT JOIN favorites f ON f.track_id = t.track_id
GROUP BY t.track_id, t.title, ar.artist_name;

CREATE OR REPLACE VIEW vw_highest_rated_tracks AS
SELECT t.track_id, t.title, ar.artist_name, AVG(r.rating) AS average_rating, COUNT(r.user_id) AS rating_count
FROM tracks t JOIN albums al ON al.album_id = t.album_id JOIN artists ar ON ar.artist_id = al.artist_id
LEFT JOIN ratings r ON r.track_id = t.track_id
GROUP BY t.track_id, t.title, ar.artist_name;

CREATE OR REPLACE VIEW vw_popular_artists AS
SELECT ar.artist_id, ar.artist_name,
       COALESCE(f.follower_count, 0) AS follower_count,
       COALESCE(s.stream_count, 0) AS stream_count
FROM artists ar
LEFT JOIN (
    SELECT af.artist_id, COUNT(*) AS follower_count
    FROM artist_follows af JOIN artists a ON a.artist_id = af.artist_id
    GROUP BY af.artist_id
) f ON f.artist_id = ar.artist_id
LEFT JOIN (
    SELECT al.artist_id, COUNT(sh.stream_id) AS stream_count
    FROM albums al JOIN tracks t ON t.album_id = al.album_id
    LEFT JOIN stream_history sh ON sh.track_id = t.track_id
    GROUP BY al.artist_id
) s ON s.artist_id = ar.artist_id
GROUP BY ar.artist_id, ar.artist_name;

CREATE OR REPLACE VIEW vw_new_releases AS
SELECT al.album_id, al.title, al.release_date, al.album_type, ar.artist_name, al.cover_image
FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id;

CREATE OR REPLACE VIEW vw_highest_rated_albums AS
SELECT al.album_id, al.title, ar.artist_name,
       AVG(r.rating) AS average_rating, COUNT(r.user_id) AS rating_count
FROM albums al JOIN artists ar ON ar.artist_id = al.artist_id
JOIN tracks t ON t.album_id = al.album_id JOIN ratings r ON r.track_id = t.track_id
GROUP BY al.album_id, al.title, ar.artist_name;

CREATE OR REPLACE VIEW vw_highest_rated_artists AS
SELECT ar.artist_id, ar.artist_name,
       AVG(r.rating) AS average_rating, COUNT(r.user_id) AS rating_count
FROM artists ar JOIN albums al ON al.artist_id = ar.artist_id
JOIN tracks t ON t.album_id = al.album_id JOIN ratings r ON r.track_id = t.track_id
GROUP BY ar.artist_id, ar.artist_name;

-- Validation queries (run after the migration; every orphan/invalid query should return zero rows).
-- SELECT al.album_id FROM albums al LEFT JOIN artists ar ON ar.artist_id = al.artist_id WHERE ar.artist_id IS NULL;
-- SELECT t.track_id FROM tracks t LEFT JOIN albums al ON al.album_id = t.album_id WHERE al.album_id IS NULL;
-- SELECT pt.playlist_id, pt.track_id FROM playlist_tracks pt LEFT JOIN playlists p ON p.playlist_id = pt.playlist_id LEFT JOIN tracks t ON t.track_id = pt.track_id WHERE p.playlist_id IS NULL OR t.track_id IS NULL;
-- SELECT tg.track_id, tg.genre_id FROM track_genres tg LEFT JOIN tracks t ON t.track_id = tg.track_id LEFT JOIN genres g ON g.genre_id = tg.genre_id WHERE t.track_id IS NULL OR g.genre_id IS NULL;
-- SELECT f.user_id, f.track_id FROM favorites f LEFT JOIN users u ON u.user_id = f.user_id LEFT JOIN tracks t ON t.track_id = f.track_id WHERE u.user_id IS NULL OR t.track_id IS NULL;
-- SELECT r.user_id, r.track_id FROM ratings r LEFT JOIN users u ON u.user_id = r.user_id LEFT JOIN tracks t ON t.track_id = r.track_id WHERE u.user_id IS NULL OR t.track_id IS NULL;
-- SELECT af.user_id, af.artist_id FROM artist_follows af LEFT JOIN users u ON u.user_id = af.user_id LEFT JOIN artists a ON a.artist_id = af.artist_id WHERE u.user_id IS NULL OR a.artist_id IS NULL;
-- SELECT sh.stream_id FROM stream_history sh LEFT JOIN users u ON u.user_id = sh.user_id LEFT JOIN tracks t ON t.track_id = sh.track_id WHERE u.user_id IS NULL OR t.track_id IS NULL;
-- SELECT email, COUNT(*) AS duplicate_count FROM users GROUP BY email HAVING COUNT(*) > 1;
-- SELECT * FROM ratings WHERE rating NOT BETWEEN 1 AND 5;
-- SELECT * FROM playlist_tracks WHERE track_order IS NOT NULL AND track_order <= 0;
