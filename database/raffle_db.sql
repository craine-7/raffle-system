-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 23, 2026 at 12:18 PM
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
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `status` enum('active','winner') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `fullname`, `status`) VALUES
(1, 'Aila', 'active'),
(2, 'Noelle', 'active'),
(3, 'kidd', 'active'),
(4, 'Ermaine', 'active'),
(5, 'Ronnie', 'active'),
(6, 'Gregorio', 'active'),
(7, 'Mattikas', 'active'),
(8, 'Pou', 'active'),
(9, 'Notpa', 'active'),
(10, 'James', 'active'),
(11, 'Vince', 'active');

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
(1, 'assets/bg/bg_1769161271.png');

-- --------------------------------------------------------

--
-- Table structure for table `winners`
--

CREATE TABLE `winners` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `prize` varchar(150) DEFAULT NULL,
  `win_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `winners`
--

INSERT INTO `winners` (`id`, `fullname`, `prize`, `win_date`) VALUES
(1, 'Ermaine', 'Ref with sky way', '2026-01-23 14:47:34'),
(2, 'Notpa', 'ref', '2026-01-23 14:48:04'),
(3, 'Ronnie BAlonte', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(4, 'Mattikas', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(5, 'kidd', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(6, 'Gregorio', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(7, 'Pou', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(8, 'Noelle', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(9, 'Aila', 'Electric Fan - Stand Fan', '2026-01-23 15:27:37'),
(10, 'James', 'Refrigerator with cold convo', '2026-01-23 16:58:04'),
(11, 'Pou', 'Refrigerator with cold convo', '2026-01-23 16:58:04'),
(12, 'Ermaine', 'Refrigerator with cold convo', '2026-01-23 16:58:04'),
(13, 'Ronnie BAlonte', 'Puregold 5k certificate of madness', '2026-01-23 17:02:16'),
(14, 'Gregorio', '1 year supply of vape', '2026-01-23 17:28:17'),
(15, 'Pou', 'Refrigerator with cold convo', '2026-01-23 19:05:22'),
(16, 'Vince', '1 year supply of vape', '2026-01-23 19:08:27'),
(17, 'Aila', '1 year supply of vape', '2026-01-23 19:09:37'),
(18, 'Vince', 'Refrigerator with cold convo', '2026-01-23 19:10:25'),
(19, 'James', '1 year supply of vape', '2026-01-23 19:14:16'),
(20, 'Noelle', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48'),
(21, 'Ronnie', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48'),
(22, 'Mattikas', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48'),
(23, 'Notpa', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48'),
(24, 'Ermaine', 'Puregold 5k certificate of madness', '2026-01-23 19:14:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
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
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `winners`
--
ALTER TABLE `winners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
