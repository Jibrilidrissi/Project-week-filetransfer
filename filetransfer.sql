-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2026 at 11:16 AM
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
-- Database: `filetransfer`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `email`, `password`) VALUES
(1, 's4kr87i9@email.com', 's4kr87i9');

-- --------------------------------------------------------

--
-- Table structure for table `download_logs`
--

CREATE TABLE `download_logs` (
  `id` int(11) NOT NULL,
  `file_id` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `downloader_email` varchar(255) NOT NULL,
  `downloaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `download_logs`
--

INSERT INTO `download_logs` (`id`, `file_id`, `file_name`, `user_id`, `downloader_email`, `downloaded_at`) VALUES
(1, '76724', 'Screenshot 2025-08-27 135702.png', 7, 'jibril@gmail.com', '2026-06-19 14:36:56'),
(2, '76724', 'Screenshot 2025-08-27 135702.png', 1, 'admin@email.com', '2026-06-19 14:37:28'),
(3, '76724', 'Screenshot 2025-08-27 135702.png', 1, 'admin@email.com', '2026-06-19 15:16:05'),
(4, '29674', '6667f666cb3966a752c5e1fa4bab8a6f.png', 1, 's4kr87i9@email.com', '2026-06-22 14:50:52'),
(5, '76689', '6667f666cb3966a752c5e1fa4bab8a6f (1).png', 10, 'gebruiker@email.com', '2026-06-23 13:35:03'),
(6, '67732', '6667f666cb3966a752c5e1fa4bab8a6f (1).png', 1, 's4kr87i9@email.com', '2026-06-24 10:54:12'),
(7, '92687', 'fb958cf285d41c011caa1f994b5c21ee.png', 1, 's4kr87i9@email.com', '2026-06-24 11:08:48'),
(8, '50860', 'Screenshot 2025-08-27 135702.png', 1, 's4kr87i9@email.com', '2026-06-24 11:14:19'),
(9, '50860', 'Screenshot 2025-08-27 135702.png', 1, 's4kr87i9@email.com', '2026-06-24 11:14:25'),
(10, '50860', 'Screenshot 2025-08-27 135702.png', 1, 's4kr87i9@email.com', '2026-06-24 11:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `beschrijving` varchar(255) NOT NULL,
  `uploaded_date` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_id` varchar(255) NOT NULL,
  `file_hash` varchar(64) DEFAULT NULL,
  `file_id` varchar(255) NOT NULL,
  `data` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `Registratie_datum` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `Registratie_datum`) VALUES
(2, 'sopper@email.com', 'sopper', '2026-06-16'),
(4, 'sapper@email.com', 'sapper', '2026-06-17'),
(5, 'email@email.com', 'email', '2026-06-17'),
(6, 'sap@email.com', 'sap', '2026-06-17'),
(7, 'jibril@gmail.com', 'jibril', '2026-06-19'),
(8, 'zeep@email.com', 'zeep', '2026-06-22'),
(9, 'dshuadusha@email.com', '1', '2026-06-22'),
(10, 'gebruiker@email.com', 'gebruik', '2026-06-23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `download_logs`
--
ALTER TABLE `download_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `download_logs`
--
ALTER TABLE `download_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
