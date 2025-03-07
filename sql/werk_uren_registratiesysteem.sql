-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 06, 2025 at 11:04 AM
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
-- Database: `werk_uren_registratiesysteem2`
--

-- --------------------------------------------------------

--
-- Table structure for table `chiefs`
--

CREATE TABLE `chiefs` (
  `chief_id` int(11) NOT NULL,
  `telefoon` varchar(50) NOT NULL,
  `adres` varchar(255) NOT NULL,
  `bedrijfnaam` varchar(255) NOT NULL,
  `stad` varchar(255) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `provincie` varchar(255) DEFAULT NULL,
  `land` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chiefs`
--

INSERT INTO `chiefs` (`chief_id`, `telefoon`, `adres`, `bedrijfnaam`, `stad`, `postcode`, `provincie`, `land`) VALUES
(1, '088 786 0100', '1119 Tupolevlaan 1', 'Chiefs of IT', 'Schiphol-Rijk', '1119 NW', 'Noord-Holland', 'Nederland');

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `contact_id` int(11) NOT NULL,
  `voornaam` varchar(255) DEFAULT NULL,
  `achternaam` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefoon` varchar(255) DEFAULT NULL,
  `bericht` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`contact_id`, `voornaam`, `achternaam`, `email`, `telefoon`, `bericht`, `created_at`) VALUES
(1, 'Test', 'User', 'test@example.com', '0612345678', NULL, '2025-03-04 10:43:05');

-- --------------------------------------------------------

--
-- Table structure for table `hours`
--

CREATE TABLE `hours` (
  `hours_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 1,
  `project_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_hours` time NOT NULL,
  `eind_hours` time NOT NULL,
  `hours` decimal(4,2) NOT NULL CHECK (`hours` between 0 and 24),
  `accord` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `contract_hours` decimal(6,2) DEFAULT NULL,
  `beschrijving` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hours`
--

INSERT INTO `hours` (`hours_id`, `user_id`, `project_id`, `date`, `start_hours`, `eind_hours`, `hours`, `accord`, `contract_hours`, `beschrijving`) VALUES
(31, 1, 2, '2025-03-05', '09:00:00', '14:00:00', 5.00, 'Approved', 5.00, 'Front-end'),
(32, 1, 1, '2025-03-04', '09:00:00', '13:00:00', 4.00, 'Rejected', 0.00, '2'),
(33, 1, 1, '2025-03-03', '08:00:00', '13:00:00', 5.00, 'Approved', 209.00, 'awd'),
(35, 1, 1, '2025-03-06', '08:00:00', '14:00:00', 6.00, 'Pending', 920.00, 'Front end'),
(37, 1, 2, '2025-03-07', '08:00:00', '13:00:00', 5.00, 'Pending', 1900.00, 'klnion'),
(38, 1, 2, '2025-02-24', '10:00:00', '14:00:00', 4.00, 'Pending', 0.00, ' jnkn');

-- --------------------------------------------------------

--
-- Table structure for table `klant`
--

CREATE TABLE `klant` (
  `klant_id` int(11) NOT NULL,
  `voornaam` varchar(255) NOT NULL,
  `achternaam` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefoon` varchar(50) NOT NULL,
  `bedrijfnaam` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `klant`
--

INSERT INTO `klant` (`klant_id`, `voornaam`, `achternaam`, `email`, `telefoon`, `bedrijfnaam`) VALUES
(1, 'John', 'Doe', 'johndoe@example.com', '0612345678', 'JohnBV'),
(2, 'Christian', 'de Winter', 'christian.winter@email.com', '0612345678', 'ChristianBV'),
(3, 'Sophie', 'Jansen', 'sophie.jansen@email.com', '0623456789', 'SophieBV'),
(4, 'Mark', 'Vermeulen', 'mark.vermeulen@email.com', '0634567890', 'MarkBV');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `project_id` int(11) NOT NULL,
  `project_naam` varchar(255) NOT NULL,
  `klant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `beschrijving` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`project_id`, `project_naam`, `klant_id`, `user_id`, `beschrijving`) VALUES
(1, 'Website Development', 1, 1, '3'),
(2, 'Mobile App', 2, 2, 'Back-end'),
(3, 'ERP System', 3, 3, 'Enterprise resource planning system implementation');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `achternaam` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefoon` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `achternaam`, `email`, `telefoon`, `password`, `role`) VALUES
(1, 'beau', 'Sulzle', 'beausulzle@gmail.com', '0648247617', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'user'),
(2, 'User2', 'Test', 'user2@example.com', '0623456789', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'user'),
(3, 'admin', 'admin', 'user3@example.com', '0634567890', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chiefs`
--
ALTER TABLE `chiefs`
  ADD PRIMARY KEY (`chief_id`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `hours`
--
ALTER TABLE `hours`
  ADD PRIMARY KEY (`hours_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `klant`
--
ALTER TABLE `klant`
  ADD PRIMARY KEY (`klant_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`project_id`),
  ADD KEY `klant_id` (`klant_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `chiefs`
--
ALTER TABLE `chiefs`
  MODIFY `chief_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `contact_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hours`
--
ALTER TABLE `hours`
  MODIFY `hours_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `klant`
--
ALTER TABLE `klant`
  MODIFY `klant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `hours`
--
ALTER TABLE `hours`
  ADD CONSTRAINT `hours_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hours_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `project`
--
ALTER TABLE `project`
  ADD CONSTRAINT `project_ibfk_1` FOREIGN KEY (`klant_id`) REFERENCES `klant` (`klant_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
