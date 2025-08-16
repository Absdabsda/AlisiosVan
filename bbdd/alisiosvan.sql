-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 11, 2025 at 06:27 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `alisiosvan`
--

-- --------------------------------------------------------

--
-- Table structure for table `blackout_dates`
--

CREATE TABLE `blackout_dates` (
  `id` int(10) UNSIGNED NOT NULL,
  `camper_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(120) DEFAULT NULL
) ;

--
-- Dumping data for table `blackout_dates`
--

INSERT INTO `blackout_dates` (`id`, `camper_id`, `start_date`, `end_date`, `reason`) VALUES
(1, 3, '2025-09-20', '2025-09-25', 'Maintenance');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `camper_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','paid','cancelled','expired','refunded') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `stripe_session_id` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `camper_id`, `customer_id`, `start_date`, `end_date`, `status`, `total_amount`, `notes`, `created_at`, `updated_at`, `stripe_session_id`) VALUES
(1, 1, 1, '2025-09-05', '2025-09-10', 'paid', 575.00, NULL, '2025-08-11 09:54:05', NULL, NULL),
(2, 2, 1, '2025-09-15', '2025-09-17', 'pending', 200.00, NULL, '2025-08-11 09:54:05', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `campers`
--

CREATE TABLE `campers` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `series` varchar(20) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `seats` tinyint(3) UNSIGNED DEFAULT 4,
  `beds` tinyint(3) UNSIGNED DEFAULT 2,
  `description` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `campers`
--

INSERT INTO `campers` (`id`, `slug`, `name`, `series`, `price_per_night`, `image`, `seats`, `beds`, `description`, `active`, `created_at`) VALUES
(1, 'matcha', 'Matcha', 'T3', 115.00, NULL, 4, 2, 'VW T3 green', 1, '2025-08-11 09:54:05'),
(2, 'skye', 'Skye', 'T3', 100.00, NULL, 4, 2, 'VW T3 blue', 1, '2025-08-11 09:54:05'),
(3, 'rusty', 'Rusty', 'T4', 85.00, NULL, 4, 2, 'VW T4 orange', 1, '2025-08-11 09:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `email`, `phone`, `created_at`) VALUES
(1, 'Test', 'User', 'test@example.com', '+34600000000', '2025-08-11 09:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `booking_id` bigint(20) UNSIGNED NOT NULL,
  `provider` enum('stripe') DEFAULT 'stripe',
  `provider_payment_intent` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'EUR',
  `status` enum('requires_payment','succeeded','processing','canceled','refunded','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(10) UNSIGNED NOT NULL,
  `camper_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','paid','canceled') NOT NULL DEFAULT 'pending',
  `stripe_session_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `camper_id`, `customer_id`, `start_date`, `end_date`, `status`, `stripe_session_id`, `stripe_payment_intent`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, '2025-08-11', '2025-08-19', 'pending', NULL, NULL, NULL, '2025-08-11 13:27:04', '2025-08-11 13:27:04'),
(2, 3, NULL, '2025-09-01', '2025-09-12', 'pending', NULL, NULL, NULL, '2025-08-11 13:33:02', '2025-08-11 13:33:02'),
(3, 3, NULL, '2025-08-11', '2025-08-18', 'pending', NULL, NULL, NULL, '2025-08-11 14:04:47', '2025-08-11 14:04:47'),
(4, 2, NULL, '2025-08-11', '2025-08-16', 'pending', NULL, NULL, NULL, '2025-08-11 14:06:29', '2025-08-11 14:06:29'),
(5, 3, NULL, '2025-08-31', '2025-09-02', 'pending', NULL, NULL, NULL, '2025-08-11 14:22:43', '2025-08-11 14:22:43'),
(6, 1, NULL, '2025-09-22', '2025-09-25', 'pending', NULL, NULL, NULL, '2025-08-11 14:31:08', '2025-08-11 14:31:08'),
(7, 2, NULL, '2025-09-22', '2025-09-25', 'pending', NULL, NULL, NULL, '2025-08-11 14:31:24', '2025-08-11 14:31:24'),
(8, 2, NULL, '2025-09-16', '2025-09-19', 'paid', 'cs_test_a1J6RkMi4aIJIN3UJxbWLOH9qwligUOLFsJDQ68zcFxy4umlVqK1o94XcF', 'pi_3RuuwQ5g17AOVKUf0zh5XhYz', '2025-08-11 14:42:00', '2025-08-11 14:34:44', '2025-08-11 14:42:00'),
(9, 1, NULL, '2025-09-14', '2025-09-20', 'paid', 'cs_test_a1fDxqaZKWSywKdA1Irr3k2PSeYkYo2U8mPOoTZncpN1Pg3tqu9yLETg2b', 'pi_3Ruv4g5g17AOVKUf1ZJBQ6oO', '2025-08-11 14:43:41', '2025-08-11 14:42:59', '2025-08-11 14:43:41'),
(10, 1, NULL, '2025-08-29', '2025-09-04', 'paid', 'cs_test_a1mE0ks8dNP1IplZlfLtb3hsyBQhi2xXJgRSeiSGTTZpz0Y4YT7wVVKlkz', 'pi_3RuvDZ5g17AOVKUf1AaEnmRO', '2025-08-11 14:52:52', '2025-08-11 14:52:30', '2025-08-11 14:52:52'),
(11, 3, NULL, '2025-08-12', '2025-08-16', 'pending', 'cs_test_a1aanhjfB8UIimbpBesTBVUSqaG1qkLPnSEpzXOVOdnhOVzTdN976XOcGi', NULL, NULL, '2025-08-11 18:27:02', '2025-08-11 18:27:03'),
(12, 3, NULL, '2025-08-12', '2025-08-16', 'paid', 'cs_test_a1swUENyyY6lIr2Y3wNJwbJQGhgXOUlm6zFmV8u7anIYnhFw6251ZWfzoS', 'pi_3RuyZE5g17AOVKUf0TDjj1B7', '2025-08-11 18:27:27', '2025-08-11 18:27:04', '2025-08-11 18:27:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blackout_dates`
--
ALTER TABLE `blackout_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blackout_camper_dates` (`camper_id`,`start_date`,`end_date`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stripe_session_id` (`stripe_session_id`),
  ADD KEY `fk_bookings_customer` (`customer_id`),
  ADD KEY `idx_bookings_camper_dates` (`camper_id`,`start_date`,`end_date`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_status_created` (`status`,`created_at`);

--
-- Indexes for table `campers`
--
ALTER TABLE `campers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_customer_email` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_payment_booking` (`booking_id`),
  ADD KEY `idx_provider_intent` (`provider_payment_intent`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_res_customer` (`customer_id`),
  ADD KEY `idx_camper_dates` (`camper_id`,`start_date`,`end_date`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_stripe_session_id` (`stripe_session_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blackout_dates`
--
ALTER TABLE `blackout_dates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campers`
--
ALTER TABLE `campers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blackout_dates`
--
ALTER TABLE `blackout_dates`
  ADD CONSTRAINT `fk_blackout_camper` FOREIGN KEY (`camper_id`) REFERENCES `campers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_camper` FOREIGN KEY (`camper_id`) REFERENCES `campers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookings_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_res_camper` FOREIGN KEY (`camper_id`) REFERENCES `campers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
