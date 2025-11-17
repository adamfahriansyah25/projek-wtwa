-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 05:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `chesslearn`
--

-- --------------------------------------------------------

--
-- Table structure for table `active_games`
--

CREATE TABLE `active_games` (
  `id` int(11) NOT NULL,
  `game_code` varchar(10) NOT NULL,
  `white_player_id` int(11) DEFAULT NULL,
  `black_player_id` int(11) DEFAULT NULL,
  `current_fen` text DEFAULT NULL,
  `turn` enum('white','black') DEFAULT 'white',
  `status` enum('waiting','active','finished') DEFAULT 'waiting',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `white_player_id` int(11) NOT NULL,
  `black_player_id` int(11) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `game_status` enum('active','finished','draw','resigned') NOT NULL DEFAULT 'active',
  `moves` text DEFAULT NULL,
  `move_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_history`
--

CREATE TABLE `game_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `opponent` varchar(100) NOT NULL,
  `result` enum('win','loss','draw') NOT NULL,
  `game_type` enum('ai','multiplayer') NOT NULL,
  `moves_count` int(11) DEFAULT 0,
  `duration` int(11) DEFAULT 0,
  `played_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_history`
--

INSERT INTO `game_history` (`id`, `user_id`, `opponent`, `result`, `game_type`, `moves_count`, `duration`, `played_at`) VALUES
(12, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 03:57:09'),
(13, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 03:57:27'),
(14, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 03:58:17'),
(15, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 04:08:28'),
(16, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 04:11:20'),
(17, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 04:13:07'),
(18, 10, 'Unknown', 'draw', 'ai', 0, 0, '2025-11-17 04:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `game_stats`
--

CREATE TABLE `game_stats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_games` int(11) DEFAULT 0,
  `wins` int(11) DEFAULT 0,
  `losses` int(11) DEFAULT 0,
  `draws` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_stats`
--

INSERT INTO `game_stats` (`id`, `user_id`, `total_games`, `wins`, `losses`, `draws`, `updated_at`) VALUES
(2, 10, 7, 0, 0, 7, '2025-11-17 04:20:00');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `log_level` enum('INFO','WARNING','ERROR') NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `log_level`, `message`, `user_id`, `username`, `created_at`) VALUES
(1, 'INFO', 'New user registered: \'user\'', NULL, 'user', '2025-11-16 22:47:59'),
(2, 'INFO', 'User \'user\' logged out.', NULL, 'user', '2025-11-16 22:48:27'),
(3, 'INFO', 'User \'user\' logged in successfully.', NULL, 'user', '2025-11-16 22:48:43'),
(4, 'INFO', 'User \'user\' logged out.', NULL, 'user', '2025-11-16 22:48:57'),
(5, 'INFO', 'New user registered: \'adam\'', NULL, 'adam', '2025-11-16 22:49:20'),
(6, 'INFO', 'User \'adam\' logged out.', NULL, 'adam', '2025-11-16 23:15:28'),
(7, 'INFO', 'New user registered: \'test\'', NULL, 'test', '2025-11-16 23:15:53'),
(8, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-16 23:16:02'),
(9, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-16 23:16:32'),
(10, 'INFO', 'New user registered: \'foto\'', NULL, 'foto', '2025-11-16 23:17:51'),
(11, 'INFO', 'User \'foto\' logged in successfully.', NULL, 'foto', '2025-11-16 23:18:01'),
(12, 'INFO', 'User \'foto\' logged out.', NULL, 'foto', '2025-11-16 23:23:10'),
(70, 'INFO', 'New user registered: \'test\'', NULL, 'test', '2025-11-16 23:48:33'),
(71, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-16 23:48:39'),
(72, 'WARNING', 'Failed login attempt for user \'test\'', NULL, NULL, '2025-11-16 23:50:40'),
(73, 'WARNING', 'Failed login attempt for user \'test\'', NULL, NULL, '2025-11-16 23:50:40'),
(74, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-16 23:50:48'),
(75, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-16 23:52:32'),
(76, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-16 23:55:16'),
(77, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 00:08:11'),
(78, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 00:08:27'),
(79, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 00:09:37'),
(80, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 00:15:09'),
(81, 'INFO', 'User \'admin\' logged in successfully.', NULL, 'admin', '2025-11-17 00:15:20'),
(82, 'INFO', 'User \'admin\' logged out.', NULL, 'admin', '2025-11-17 00:15:44'),
(83, 'WARNING', 'Failed login attempt for user \'test\'', NULL, NULL, '2025-11-17 00:15:49'),
(84, 'WARNING', 'Failed login attempt for user \'test\'', NULL, NULL, '2025-11-17 00:15:49'),
(85, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 00:15:54'),
(86, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 00:26:10'),
(87, 'INFO', 'User \'admin\' logged in successfully.', NULL, 'admin', '2025-11-17 00:26:18'),
(88, 'INFO', 'User \'admin\' logged out.', NULL, 'admin', '2025-11-17 00:28:05'),
(89, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 00:28:10'),
(90, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 00:51:31'),
(91, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:51:38'),
(92, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:51:39'),
(93, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:06'),
(94, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:06'),
(95, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:10'),
(96, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:10'),
(97, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 00:52:18'),
(98, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 00:52:19'),
(99, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:26'),
(100, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:26'),
(101, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:44'),
(102, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:44'),
(103, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:58'),
(104, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:52:58'),
(105, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:53:15'),
(106, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:53:15'),
(107, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:53:34'),
(108, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:53:34'),
(109, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:53:45'),
(110, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 00:53:45'),
(111, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 00:53:54'),
(112, 'INFO', 'User \'adam\' logged out.', NULL, 'adam', '2025-11-17 01:22:50'),
(113, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 01:23:01'),
(114, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 01:23:01'),
(115, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 01:23:06'),
(116, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 01:23:55'),
(117, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 01:32:32'),
(118, 'INFO', 'New user registered: \'adam\'', NULL, 'adam', '2025-11-17 01:33:01'),
(119, 'INFO', 'User \'adam\' logged in successfully.', NULL, 'adam', '2025-11-17 01:33:08'),
(120, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 01:33:16'),
(121, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 02:33:32'),
(122, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 02:33:39'),
(123, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 02:33:39'),
(124, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 02:33:48'),
(125, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 03:03:12'),
(126, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:03:22'),
(127, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:03:22'),
(128, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:04:06'),
(129, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:04:06'),
(130, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:06:27'),
(131, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:06:27'),
(132, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:07:04'),
(133, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:07:04'),
(134, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 03:08:01'),
(135, 'INFO', 'Admin user \'admin\' created successfully.', NULL, 'admin', '2025-11-17 03:11:10'),
(136, 'INFO', 'User ID 5 promoted to admin.', NULL, NULL, '2025-11-17 03:11:15'),
(137, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 03:11:26'),
(138, 'INFO', 'User \'admin\' logged in successfully.', NULL, 'admin', '2025-11-17 03:11:32'),
(139, 'INFO', 'User \'admin\' logged out.', NULL, 'admin', '2025-11-17 03:45:28'),
(140, 'WARNING', 'Failed login attempt for user \'test\'', NULL, NULL, '2025-11-17 03:45:33'),
(141, 'WARNING', 'Failed login attempt for user \'test\'', NULL, NULL, '2025-11-17 03:45:33'),
(142, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 03:45:39'),
(143, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 03:45:46'),
(144, 'WARNING', 'Failed login attempt for user \'adam\'', NULL, NULL, '2025-11-17 03:45:56'),
(145, 'WARNING', 'Failed login attempt for user \'adam\'', NULL, NULL, '2025-11-17 03:45:56'),
(146, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 03:46:27'),
(147, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 03:48:43'),
(148, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 03:48:59'),
(149, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 03:49:00'),
(150, 'INFO', 'User \'admin\' logged in successfully.', NULL, 'admin', '2025-11-17 03:49:13'),
(151, 'INFO', 'User \'admin\' logged out.', NULL, 'admin', '2025-11-17 03:50:14'),
(152, 'INFO', 'User \'admin\' logged in successfully.', NULL, 'admin', '2025-11-17 03:50:25'),
(153, 'INFO', 'User \'admin\' logged out.', NULL, 'admin', '2025-11-17 03:50:28'),
(154, 'INFO', 'User \'test\' logged in successfully.', NULL, 'test', '2025-11-17 03:50:33'),
(166, 'INFO', 'User \'test\' logged out.', NULL, 'test', '2025-11-17 03:55:24'),
(167, 'WARNING', 'Failed login attempt for user \'adam\'', NULL, NULL, '2025-11-17 03:55:37'),
(168, 'WARNING', 'Failed login attempt for user \'adam\'', NULL, NULL, '2025-11-17 03:55:37'),
(169, 'INFO', 'New user registered: \'test\'', 10, 'test', '2025-11-17 03:56:51'),
(170, 'INFO', 'User \'test\' logged in successfully.', 10, 'test', '2025-11-17 03:56:57'),
(171, 'INFO', 'User \'test\' logged out.', 10, 'test', '2025-11-17 03:59:03'),
(172, 'WARNING', 'Failed login attempt for user \'admin@gmail.com\'', NULL, NULL, '2025-11-17 03:59:11'),
(173, 'WARNING', 'Failed login attempt for user \'admin@gmail.com\'', NULL, NULL, '2025-11-17 03:59:11'),
(174, 'WARNING', 'Failed login attempt for user \'admin@gmail.com\'', NULL, NULL, '2025-11-17 03:59:24'),
(175, 'WARNING', 'Failed login attempt for user \'admin@gmail.com\'', NULL, NULL, '2025-11-17 03:59:24'),
(176, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:59:49'),
(177, 'WARNING', 'Failed login attempt for user \'admin\'', NULL, NULL, '2025-11-17 03:59:49'),
(178, 'WARNING', 'Failed login attempt for user \'admin@gmail.com\'', NULL, NULL, '2025-11-17 03:59:58'),
(179, 'WARNING', 'Failed login attempt for user \'admin@gmail.com\'', NULL, NULL, '2025-11-17 03:59:58'),
(180, 'INFO', 'User \'test\' logged in successfully.', 10, 'test', '2025-11-17 04:03:50'),
(181, 'INFO', 'User \'test\' logged out.', 10, 'test', '2025-11-17 04:04:15'),
(182, 'INFO', 'User \'admin\' logged in successfully.', 11, 'admin', '2025-11-17 04:06:50'),
(183, 'INFO', 'User \'admin\' logged out.', 11, 'admin', '2025-11-17 04:06:54'),
(184, 'WARNING', 'Failed login attempt for user \'test@net.com\'', NULL, NULL, '2025-11-17 04:07:01'),
(185, 'INFO', 'User \'test\' logged in successfully.', 10, 'test', '2025-11-17 04:07:09'),
(186, 'INFO', 'User \'test\' logged out.', 10, 'test', '2025-11-17 04:11:43'),
(187, 'INFO', 'User \'admin\' logged in successfully.', 11, 'admin', '2025-11-17 04:11:48'),
(188, 'INFO', 'User \'admin\' logged out.', 11, 'admin', '2025-11-17 04:12:48'),
(189, 'INFO', 'User \'test\' logged in successfully.', 10, 'test', '2025-11-17 04:12:55'),
(190, 'INFO', 'User \'test\' logged in successfully.', 10, 'test', '2025-11-17 04:19:53');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `elo_rating` int(11) DEFAULT 1200,
  `games_won` int(11) DEFAULT 0,
  `games_lost` int(11) DEFAULT 0,
  `games_draw` int(11) DEFAULT 0,
  `total_games` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `avatar_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `elo_rating`, `games_won`, `games_lost`, `games_draw`, `total_games`, `created_at`, `last_login`, `is_admin`, `avatar_url`) VALUES
(10, 'test', 'test@net.com', '$2y$10$rj.yVd90NEUpR5KLFT9CAen.ngS3SdVkJpTTXIUIeJwzf.cWRcw8W', 1200, 0, 0, 0, 0, '2025-11-17 03:56:51', '2025-11-17 04:19:53', 0, 'avatar_10_1763351811.png'),
(11, 'admin', 'admin@gmail.com', '$2y$10$/HfBKMZwi0rjJyx/l9E6TufEk7hWqGDGbSsyH4xsl9vPiTgIdtkle', 1500, 0, 0, 0, 0, '2025-11-17 04:06:30', '2025-11-17 04:11:48', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `active_games`
--
ALTER TABLE `active_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_code` (`game_code`),
  ADD UNIQUE KEY `unique_game_code` (`game_code`),
  ADD KEY `idx_white_player` (`white_player_id`),
  ADD KEY `idx_black_player` (`black_player_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD KEY `white_player_id` (`white_player_id`),
  ADD KEY `black_player_id` (`black_player_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Indexes for table `game_history`
--
ALTER TABLE `game_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_played_at` (`played_at`),
  ADD KEY `idx_result` (`result`);

--
-- Indexes for table `game_stats`
--
ALTER TABLE `game_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_stats` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `active_games`
--
ALTER TABLE `active_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `game_history`
--
ALTER TABLE `game_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `game_stats`
--
ALTER TABLE `game_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `active_games`
--
ALTER TABLE `active_games`
  ADD CONSTRAINT `fk_active_black` FOREIGN KEY (`black_player_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_active_white` FOREIGN KEY (`white_player_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `game_history`
--
ALTER TABLE `game_history`
  ADD CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_stats`
--
ALTER TABLE `game_stats`
  ADD CONSTRAINT `fk_stats_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
