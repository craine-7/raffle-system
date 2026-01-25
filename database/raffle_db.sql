-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2026 at 08:37 AM
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
-- Database: `raffle_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `token`, `created_at`, `is_active`) VALUES
(1, 'Default Event', 'default', '2026-01-24 15:23:23', 1),
(2, 'Christmas party', '', '2026-01-24 15:33:17', 1),
(4, 'Festival', '3', '2026-01-24 16:37:22', 1),
(10, 'Birthday', 't9es7m4a', '2026-01-25 15:16:34', 1),
(11, 'Birthday n\'ya ', 't9eso409', '2026-01-25 15:26:28', 1);

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `status` enum('active','winner') DEFAULT 'active',
  `event_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `fullname`, `status`, `event_id`) VALUES
(1, 'Aila', 'winner', 1),
(2, 'Noelle', 'winner', 1),
(3, 'kidd', 'winner', 1),
(4, 'Ermaine', 'winner', 1),
(5, 'Ronnie', 'winner', 1),
(6, 'Gregorio', 'winner', 1),
(7, 'Mattikas', 'winner', 1),
(8, 'Pou', 'winner', 1),
(9, 'Notpa', 'winner', 1),
(10, 'James', 'winner', 1),
(11, 'Vince', 'winner', 1),
(12, 'Vinluan Aila', 'active', 1),
(13, 'Baena Vince', 'active', 1),
(14, 'Torres Kidd', 'active', 1),
(15, 'Estrada Noelle', 'active', 1),
(16, 'Ureta Ermaine', 'active', 1),
(17, 'Gregorio James', 'winner', 1),
(18, 'Chan Ian', 'active', 1),
(19, 'Caballero Faith', 'active', 1),
(20, 'Santiago Mark', 'active', 1),
(21, 'Mateo Ryan', 'active', 1),
(22, 'John Doe', 'winner', 2),
(23, 'Jane Smith', 'winner', 2),
(24, 'Robert Johnson', 'winner', 2),
(25, 'Maria Garcia', 'winner', 2),
(26, 'David Wilson', 'winner', 2),
(27, 'Emily Davis', 'winner', 2),
(28, 'Michael Brown', 'winner', 2),
(29, 'Sarah Miller', 'active', 2),
(30, 'John Doe', 'active', 4),
(31, 'Jane Smith', 'active', 4),
(32, 'Robert Johnson', 'active', 4),
(33, 'Maria Garcia', 'active', 4),
(34, 'David Wilson', 'active', 4),
(35, 'Emily Davis', 'winner', 4),
(36, 'Michael Brown', 'active', 4),
(37, 'Sarah Miller', 'active', 4),
(38, 'Robert Poppins', 'active', 2),
(39, 'Noelle Estrada', 'winner', 2),
(40, 'Aila Ramos', 'active', 2),
(41, 'Vinluan Aila', 'active', 10),
(42, 'John Doe', 'active', 10),
(43, 'Jane Smith', 'active', 10),
(44, 'Robert Johnson', 'active', 10),
(45, 'Maria Garcia', 'active', 10),
(46, 'David Wilson', 'active', 10),
(47, 'Emily Davis', 'active', 10),
(48, 'Michael Brown', 'active', 10),
(49, 'Sarah Miller', 'active', 10),
(50, 'Torres Kidd', 'active', 10),
(51, 'John Doe', 'active', 11),
(52, 'Jane Smith', 'active', 11),
(53, 'Robert Johnson', 'active', 11),
(54, 'Maria Garcia', 'active', 11),
(55, 'David Wilson', 'active', 11),
(56, 'Emily Davis', 'active', 11),
(57, 'Michael Brown', 'active', 11),
(58, 'Sarah Miller', 'active', 11);

-- --------------------------------------------------------

--
-- Table structure for table `prize_categories`
--

CREATE TABLE `prize_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT '#667eea'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prize_categories`
--

INSERT INTO `prize_categories` (`id`, `name`, `color`) VALUES
(1, 'Minor Prize', '#6c757d'),
(2, 'Regular Prize', '#198754'),
(3, 'Major Prize', '#0d6efd'),
(4, 'Grand Prize', '#ffc107');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `background` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `background`) VALUES
(1, 'assets/bg/bg_1769320772.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

CREATE TABLE `winners` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `prize` varchar(150) DEFAULT NULL,
  `win_date` datetime DEFAULT current_timestamp(),
  `prize_category_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `winners`
--

INSERT INTO `winners` (`id`, `fullname`, `prize`, `win_date`, `prize_category_id`, `event_id`) VALUES
(1, 'Ermaine', 'Ref with sky way', '2026-01-23 14:47:34', NULL, 1),
(2, 'Notpa', 'ref', '2026-01-23 14:48:04', NULL, 1),
(3, 'Ronnie BAlonte', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(4, 'Mattikas', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(5, 'kidd', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(6, 'Gregorio', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(7, 'Pou', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(8, 'Noelle', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(9, 'Aila', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37', NULL, 1),
(10, 'James', 'Refrigerator with cold convo', '2026-01-23 16:58:04', NULL, 1),
(11, 'Pou', 'Refrigerator with cold convo', '2026-01-23 16:58:04', NULL, 1),
(12, 'Ermaine', 'Refrigerator with cold convo', '2026-01-23 16:58:04', NULL, 1),
(13, 'Ronnie BAlonte', 'Puregold 5k certificate of madness', '2026-01-23 17:02:16', NULL, 1),
(14, 'Gregorio', '1 year supply of vape', '2026-01-23 17:28:17', NULL, 1),
(15, 'Pou', 'Refrigerator with cold convo', '2026-01-23 19:05:22', NULL, 1),
(16, 'Vince', '1 year supply of vape', '2026-01-23 19:08:27', NULL, 1),
(17, 'Aila', '1 year supply of vape', '2026-01-23 19:09:37', NULL, 1),
(18, 'Vince', 'Refrigerator with cold convo', '2026-01-23 19:10:25', NULL, 1),
(19, 'James', '1 year supply of vape', '2026-01-23 19:14:16', NULL, 1),
(20, 'Noelle', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48', NULL, 1),
(21, 'Ronnie', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48', NULL, 1),
(22, 'Mattikas', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48', NULL, 1),
(23, 'Notpa', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48', NULL, 1),
(24, 'Ermaine', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48', NULL, 1),
(25, 'Pou', '1 year supply of vape', '2026-01-23 20:17:18', NULL, 1),
(26, 'Ermaine', 'Electric fan', '2026-01-24 12:29:40', NULL, 1),
(27, 'Vince', 'Electric fan', '2026-01-24 12:29:40', NULL, 1),
(28, 'Gregorio', 'Electric fan', '2026-01-24 12:29:40', NULL, 1),
(29, 'Notpa', 'Washing machine ', '2026-01-24 12:31:06', NULL, 1),
(30, 'Ronnie', 'Washing machine ', '2026-01-24 12:31:06', NULL, 1),
(31, 'kidd', 'Washing machine ', '2026-01-24 12:31:06', NULL, 1),
(32, 'James', 'Washing machine ', '2026-01-24 12:31:06', NULL, 1),
(33, 'Noelle', 'Washing machine ', '2026-01-24 12:31:06', NULL, 1),
(34, 'Mattikas', 'Iphone 17', '2026-01-24 12:31:44', NULL, 1),
(35, 'Aila', 'Motor', '2026-01-24 12:32:05', NULL, 1),
(36, 'Gregorio James', 'Washing machine', '2026-01-24 15:56:39', 1, 1),
(37, 'David Wilson', '1 year supply of vape', '2026-01-24 16:19:12', 1, 2),
(38, 'Emily Davis', 'Raptor', '2026-01-24 16:39:04', 4, 4),
(39, 'Michael Brown', 'Iphone 17', '2026-01-24 17:11:07', 4, 2),
(40, 'Jane Smith', 'Electric fan', '2026-01-24 17:14:54', 1, 2),
(41, 'John Doe', 'Electric fan', '2026-01-24 17:14:54', 1, 2),
(42, 'Emily Davis', 'Electric fan', '2026-01-24 17:14:54', 1, 2),
(43, 'Maria Garcia', 'Electric fan', '2026-01-24 17:17:59', 1, 2),
(44, 'Noelle Estrada', 'Air purifier', '2026-01-24 17:58:06', 2, 2),
(45, 'Robert Johnson', 'Air purifier', '2026-01-24 17:58:06', 2, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `prize_categories`
--
ALTER TABLE `prize_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `winners`
--
ALTER TABLE `winners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prize_category_id` (`prize_category_id`),
  ADD KEY `event_id` (`event_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `prize_categories`
--
ALTER TABLE `prize_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `winners`
--
ALTER TABLE `winners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `participants`
--
ALTER TABLE `participants`
  ADD CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);

--
-- Constraints for table `winners`
--
ALTER TABLE `winners`
  ADD CONSTRAINT `winners_ibfk_1` FOREIGN KEY (`prize_category_id`) REFERENCES `prize_categories` (`id`),
  ADD CONSTRAINT `winners_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
