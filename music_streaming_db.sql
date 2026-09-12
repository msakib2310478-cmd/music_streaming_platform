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
