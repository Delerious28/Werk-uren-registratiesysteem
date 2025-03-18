-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 01:03 PM
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
    (1, '088 786 0100', '1119 Tupolevlaan', 'Chiefs of IT', 'Schiphol-Rijk', '1119 NW', 'Noord-Holland', 'Nederland');

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
    (1, 'hans', 'User', 'test@example.com', '0612345678', NULL, '2025-03-04 10:43:05');

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
                                                                                                                                                        (32, 1, 1, '2025-03-04', '09:00:00', '13:00:00', 4.00, 'Approved', 0.00, '2'),
                                                                                                                                                        (33, 1, 1, '2025-03-03', '08:00:00', '13:00:00', 5.00, 'Approved', 209.00, 'awd'),
                                                                                                                                                        (35, 1, 1, '2025-03-06', '08:00:00', '14:00:00', 6.00, 'Approved', 920.00, 'Front end'),
                                                                                                                                                        (37, 1, 2, '2025-03-07', '08:00:00', '13:00:00', 5.00, 'Approved', 1900.00, 'klnion'),
                                                                                                                                                        (38, 1, 2, '2025-02-24', '10:00:00', '14:00:00', 4.00, 'Pending', 0.00, ' jnkn'),
                                                                                                                                                        (39, 1, 1, '2025-03-10', '09:00:00', '17:00:00', 8.00, 'Approved', 200.00, 'Front-end development'),
                                                                                                                                                        (40, 1, 2, '2025-03-11', '08:00:00', '12:00:00', 4.00, 'Approved', 150.00, 'Back-end development'),
                                                                                                                                                        (41, 1, 3, '2025-03-12', '10:00:00', '15:00:00', 5.00, 'Approved', 250.00, 'API integration'),
                                                                                                                                                        (42, 1, 1, '2025-03-13', '09:00:00', '18:00:00', 9.00, 'Approved', 300.00, 'Database optimization'),
                                                                                                                                                        (43, 1, 2, '2025-03-14', '08:00:00', '16:00:00', 8.00, 'Approved', 220.00, 'Bug fixing'),
                                                                                                                                                        (44, 1, 3, '2025-03-15', '11:00:00', '14:00:00', 3.00, 'Approved', 180.00, 'UI/UX testing'),
                                                                                                                                                        (45, 1, 1, '2025-03-16', '09:00:00', '17:00:00', 8.00, 'Approved', 210.00, 'Front-end redesign'),
                                                                                                                                                        (46, 1, 2, '2025-03-17', '10:00:00', '14:00:00', 4.00, 'Approved', 160.00, 'Server-side scripting'),
                                                                                                                                                        (47, 1, 3, '2025-03-18', '09:00:00', '15:00:00', 6.00, 'Approved', 230.00, 'API documentation'),
                                                                                                                                                        (48, 1, 1, '2025-03-19', '08:00:00', '16:00:00', 8.00, 'Approved', 240.00, 'Performance testing'),
                                                                                                                                                        (49, 1, 2, '2025-03-20', '09:00:00', '17:00:00', 8.00, 'Approved', 200.00, 'Code review'),
                                                                                                                                                        (50, 1, 3, '2025-03-21', '08:00:00', '12:00:00', 4.00, 'Approved', 210.00, 'Security audit'),
                                                                                                                                                        (51, 1, 1, '2025-03-22', '09:00:00', '18:00:00', 9.00, 'Approved', 250.00, 'Integration testing'),
                                                                                                                                                        (52, 1, 2, '2025-03-23', '10:00:00', '14:00:00', 4.00, 'Approved', 220.00, 'Project management');

-- --------------------------------------------------------

--
-- Table structure for table `klant`
--

CREATE TABLE `klant` (
                         `klant_id` int(11) NOT NULL,
                         `voornaam` varchar(255) NOT NULL,
                         `achternaam` varchar(255) NOT NULL,
                         `password` varchar(255) NOT NULL,
                         `role` varchar(50) NOT NULL DEFAULT 'klant',
                         `email` varchar(255) NOT NULL,
                         `telefoon` varchar(50) NOT NULL,
                         `bedrijfnaam` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `klant`
--

INSERT INTO `klant` (`klant_id`, `voornaam`, `achternaam`, `password`, `role`, `email`, `telefoon`, `bedrijfnaam`) VALUES
                                                                                                                       (1, 'John', 'Doe', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'klant', 'johndoe@example.com', '0612345678', 'JohnBV'),
                                                                                                                       (2, 'Christian', 'de Winter', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'klant', 'christian.winter@gmail.com', '0612345678', 'ChristianBV'),
                                                                                                                       (3, 'Sophie1', 'Jansen', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'klant', 'sophie.jansen@email.com', '0623456789', 'SophieBV'),
                                                                                                                       (4, 'Mark', 'Vermeulen', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'klant', 'mark.vermeulen@email.com', '0634567890', 'MarkBV'),
                                                                                                                       (5, 'test', 'test', '$2y$10$rAr1yYD5rB0YkcJRl/2PfOBQgT5fFew919hFQE/VTnKZOKH5XTAP2', 'klant', 'klanttest@gmail.com', '06', 'Klantestbv'),
                                                                                                                       (6, 'testklant', 'testklant', '$2y$10$/4Dhde6uWJr8Glv6PY1kIOFX/5dmvkQUwk7TuSYfDvnRnEpafV2s2', 'klant', 'testklant@gmail.com', '06', 'testklantBV');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
                           `project_id` int(11) NOT NULL,
                           `project_naam` varchar(255) NOT NULL,
                           `klant_id` int(11) NOT NULL,
                           `user_id` int(11) DEFAULT NULL,
                           `beschrijving` text NOT NULL,
                           `users` varchar(255) NOT NULL DEFAULT '',
                           `contract_uren` decimal(6,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`project_id`, `project_naam`, `klant_id`, `user_id`, `beschrijving`, `users`, `contract_uren`) VALUES
                                                                                                                          (1, 'Website Development', 1, 1, '3', '', 0.00),
                                                                                                                          (2, 'Mobile App', 2, 2, 'Back-end', '', 0.00),
                                                                                                                          (3, 'ERP System', 3, 3, 'Enterprise resource planning system implementation', '', 0.00),
                                                                                                                          (4, 'Tesla', 1, NULL, 'koop een echte auto', '', 0.00),
                                                                                                                          (7, 'test', 6, NULL, 'jow', '', 2000.00),
                                                                                                                          (8, 'test2', 2, NULL, 'Hi', '', 69.00),
                                                                                                                          (9, 'test3', 6, NULL, 'test3', '', 55.00);

-- --------------------------------------------------------

--
-- Table structure for table `project_users`
--

CREATE TABLE `project_users` (
                                 `project_id` int(11) NOT NULL,
                                 `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_users`
--

INSERT INTO `project_users` (`project_id`, `user_id`) VALUES
                                                          (1, 1),
                                                          (1, 7),
                                                          (3, 1),
                                                          (7, 7),
                                                          (8, 5),
                                                          (8, 7),
                                                          (9, 7);

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
                                                                                                   (1, 'user', 'Aveiro', 'beausulzle@gmail.com', '0648247617', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'user'),
                                                                                                   (2, 'klant', 'Cuccittini', 'user2@example.com', '0623456789', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'klant'),
                                                                                                   (3, 'admin', 'Lottin', 'user3@example.com', '0634567890', '$2y$10$hO4jjhDNRNG1jMfM3nTkc.3cawInIMNMcWSW9agbvbvWROeO5Lf7C', 'admin'),
                                                                                                   (5, 'Andrew', 'SINJA', 'andrew@gmail.com', '06', '$2y$10$6Ig.y7awnidzhyvNVhOJquXHiIqRweH9D5Wug5o4AP2w.By1ybZPS', 'user'),
                                                                                                   (7, 'beau', 'sulzle', 'beau@gmail.com', '00', '$2y$10$iKS/KdYFuZ2eb15VpCu8eugYeqjK.fVVjZT2Vh8OlKLIxa3VNYAte', 'user');

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
-- Indexes for table `project_users`
--
ALTER TABLE `project_users`
    ADD PRIMARY KEY (`project_id`,`user_id`),
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
    MODIFY `hours_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `klant`
--
ALTER TABLE `klant`
    MODIFY `klant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
    MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
    MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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

--
-- Constraints for table `project_users`
--
ALTER TABLE `project_users`
    ADD CONSTRAINT `project_users_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `project_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
