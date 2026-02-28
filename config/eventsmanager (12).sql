-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 28, 2026 at 05:22 PM
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
-- Database: `eventsmanager`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `client_name` varchar(50) DEFAULT NULL,
  `client_address` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `pincode` int(11) DEFAULT NULL,
  `contact` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `client_name`, `client_address`, `state`, `city`, `pincode`, `contact`, `created_at`, `updated_at`) VALUES
(8, 'Test', 'vapi', 'Gujarat', 'Vapi', 432555, '9627358363', '2026-02-16 10:51:58', '2026-02-16 10:51:58'),
(9, 'User', 'vapi', 'Gujarat', 'Vapi', 432123, '9887876543', '2026-02-19 05:52:22', '2026-02-19 06:19:58'),
(10, 'Client', 'vapi', 'Gujarat', 'Vapi', 432123, '8765434567', '2026-02-19 09:57:54', '2026-02-19 09:57:54'),
(11, 'user2', 'dahanu', 'Maharashtra', 'Dahanu', 401601, '0987654324', '2026-02-28 13:38:27', '2026-02-28 13:38:27');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` text DEFAULT NULL,
  `budget` int(11) NOT NULL,
  `service` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `client_name`, `start_date`, `end_date`, `start_time`, `end_time`, `venue`, `budget`, `service`, `description`, `created_at`, `updated_at`) VALUES
(20, 'Birthday', 'Client', '2026-02-23 00:00:00', '2026-02-23 00:00:00', '18:00:00', '23:00:00', 'Mumbai', 10000, '[{\"service\":\"Catering\",\"subcategories\":[\"Dinner\",\"Lunch\",\"Snacks\"],\"requirements\":[{\"type\":\"Dinner\",\"description\":\"..\",\"amount\":\"5000\",\"startTime\":\"20:30\",\"endTime\":\"23:00\"},{\"type\":\"Snacks\",\"description\":\"...\",\"amount\":\"1000\",\"startTime\":\"18:30\",\"endTime\":\"20:30\"}],\"cateringType\":\"\",\"cateringStartTime\":\"\",\"cateringEndTime\":\"\"},{\"service\":\"Decoration\",\"subcategories\":[\"Floral Arrangements\",\"Stage\",\"Tables\"],\"requirements\":[{\"type\":\"Floral Arrangements\",\"description\":\"..\",\"amount\":\"1000\",\"startTime\":\"\",\"endTime\":\"\"},{\"type\":\"Stage\",\"description\":\"2 stages\",\"amount\":\"1000\",\"startTime\":\"\",\"endTime\":\"\"},{\"type\":\"Tables\",\"description\":\"10 tables\",\"amount\":\"1000\",\"startTime\":\"\",\"endTime\":\"\"}],\"cateringType\":\"\",\"cateringStartTime\":\"\",\"cateringEndTime\":\"\"},{\"service\":\"Photography\",\"subcategories\":[\"Drone Shooting\",\"Photo Album\",\"Photos & Videos\"],\"requirements\":[{\"type\":\"Drone Shooting\",\"description\":\"..\",\"amount\":\"3000\",\"startTime\":\"\",\"endTime\":\"\"},{\"type\":\"Photos & Videos\",\"description\":\"..\",\"amount\":\"1000\",\"startTime\":\"\",\"endTime\":\"\"}],\"cateringType\":\"\",\"cateringStartTime\":\"\",\"cateringEndTime\":\"\"}]', '..', '2026-02-20 07:00:15', '2026-02-20 09:01:41'),
(21, 'Birthday', 'Test', '2026-02-23 00:00:00', '2026-02-23 00:00:00', '12:59:00', '17:55:00', 'Mumbai', 7000, '[{\"service\":\"Decoration\",\"subcategories\":[\"Floral Arrangements\",\"Stage\",\"Tables\"],\"requirements\":[{\"type\":\"Floral Arrangements\",\"description\":\"Red flowers\",\"amount\":\"1000\"},{\"type\":\"Stage\",\"description\":\"2 stages\",\"amount\":\"1000\"}]},{\"service\":\"Photography\",\"subcategories\":[\"Drone Shooting\",\"Photo Album\",\"Photos & Videos\"],\"requirements\":[{\"type\":\"Photos & Videos\",\"description\":\"..\",\"amount\":\"5000\"}]}]', '...', '2026-02-20 07:25:49', '2026-02-20 07:25:49');

-- --------------------------------------------------------

--
-- Table structure for table `event_vendors`
--

CREATE TABLE `event_vendors` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL COMMENT 'Parent service, e.g. Catering, Decoration',
  `requirement_type` varchar(100) NOT NULL COMMENT 'Sub-requirement, e.g. Desserts, Stage, Drone Shooting',
  `requirement_count` int(11) NOT NULL DEFAULT 1 COMMENT 'Count of units required',
  `amount` decimal(10,2) DEFAULT NULL COMMENT 'Agreed amount for this vendor/requirement',
  `notes` text DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Vendor assignments at sub-requirement granularity';

--
-- Dumping data for table `event_vendors`
--

INSERT INTO `event_vendors` (`id`, `event_id`, `vendor_id`, `service_name`, `requirement_type`, `requirement_count`, `amount`, `notes`, `assigned_at`, `updated_at`) VALUES
(64, 20, 7, 'Catering', 'Dinner', 1, 2000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(65, 20, 7, 'Catering', 'Snacks', 1, 1000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(66, 20, 6, 'Decoration', 'Floral Arrangements', 1, 1000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(67, 20, 5, 'Decoration', 'Stage', 1, 1000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(68, 20, 5, 'Decoration', 'Tables', 1, 1000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(69, 20, 8, 'Photography', 'Drone Shooting', 1, 3000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(70, 20, 8, 'Photography', 'Photos & Videos', 1, 1000.00, '', '2026-02-20 07:13:52', '2026-02-20 07:13:52'),
(74, 21, 6, 'Decoration', 'Floral Arrangements', 1, 500.00, '', '2026-02-20 11:00:01', '2026-02-20 11:00:01'),
(75, 21, 6, 'Decoration', 'Stage', 1, 500.00, '', '2026-02-20 11:00:01', '2026-02-20 11:00:01'),
(76, 21, 8, 'Photography', 'Photos & Videos', 1, 4000.00, '', '2026-02-20 11:00:01', '2026-02-20 11:00:01');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `country` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `description` text DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `updated_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `country`, `state`, `city`, `status`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'India', 'gujarat', 'Vapi', 'Active', '.', NULL, NULL, '2026-01-28 07:49:37', '2026-01-28 07:49:37'),
(2, 'India', 'Maharastra', 'Mumbai', 'Active', '.', NULL, NULL, '2026-01-28 09:26:53', '2026-01-28 18:00:58'),
(3, 'India', 'Maharastra', 'Pune', 'Active', '.', NULL, NULL, '2026-01-28 18:25:11', '2026-01-28 18:25:11');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `amount_received` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `payment_mode` varchar(50) DEFAULT 'Cash',
  `note` text DEFAULT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `event_vendor_id` int(11) DEFAULT NULL,
  `vendor_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `event_id`, `amount_received`, `payment_date`, `payment_mode`, `note`, `transaction_id`, `created_at`, `event_vendor_id`, `vendor_id`) VALUES
(9, 20, 5000.00, '2026-02-20', 'Cash', '', '1A', '2026-02-20 16:47:18', NULL, NULL),
(10, 20, 500.00, '2026-02-28', 'Cash', '', '', '2026-02-28 19:06:38', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `requirements`
--

CREATE TABLE `requirements` (
  `requirement_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `requirement_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL COMMENT 'e.g., pieces, sets, hours, persons',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `parent_id`, `is_active`) VALUES
(7, 'Decoration', NULL, 1),
(8, 'Catering', NULL, 1),
(9, 'Photography', NULL, 1),
(10, 'Desserts', 8, 1),
(11, 'Beverages', 8, 1),
(12, 'Main Course', 8, 1),
(13, 'Snacks', 8, 1),
(14, 'Table', 7, 1),
(15, 'Chair', 7, 1),
(16, 'Stage', 7, 1),
(17, 'Floral arrangements', 7, 1),
(18, 'Photography', 9, 1),
(19, 'Videography', 9, 1),
(20, 'Photo Album', 9, 1),
(21, 'Catering (Pure Veg)', NULL, 1),
(22, 'Lunch', 21, 1),
(23, 'Snacks', 21, 1),
(24, 'Dinner', 21, 1),
(25, 'test', NULL, 1),
(26, 'tes', 25, 1);

-- --------------------------------------------------------

--
-- Table structure for table `services_category`
--

CREATE TABLE `services_category` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services_category`
--

INSERT INTO `services_category` (`id`, `name`, `category`, `status`) VALUES
(1, 'Catering', 'Main', 'Active'),
(2, 'Dinner', 'Catering', 'Active'),
(3, 'Decoration', 'Main', 'Active'),
(4, 'Stage', 'Decoration', 'Active'),
(5, 'Tables', 'Decoration', 'Active'),
(6, 'Photography', 'Main', 'Active'),
(7, 'Photos & Videos', 'Photography', 'Active'),
(8, 'Photo Album', 'Photography', 'Active'),
(9, 'Lunch', 'Catering', 'Active'),
(10, 'Snacks', 'Catering', 'Active'),
(11, 'Floral Arrangements', 'Decoration', 'Active'),
(12, 'Drone Shooting', 'Photography', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `service_detail_templates`
--

CREATE TABLE `service_detail_templates` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `field_type` enum('text','number','select','checkbox') DEFAULT 'text',
  `field_options` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', 'admin1', '2026-01-27 07:56:25', '2026-02-17 08:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` int(11) NOT NULL,
  `vendor_name` varchar(100) NOT NULL,
  `service_type` varchar(50) NOT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`vendor_id`, `vendor_name`, `service_type`, `contact_phone`, `address`, `country`, `state`, `city`, `status`, `created_at`, `updated_at`) VALUES
(5, 'Xyz', 'Decoration', '8724234987', 'Mumbai', 'India', 'Maharastra', 'Mumbai', 'Active', '2026-02-20 07:01:06', '2026-02-20 07:01:06'),
(6, 'Abc', 'Decoration', '8634357644', 'Mumbai', 'India', 'Maharastra', 'Mumbai', 'Active', '2026-02-20 07:01:39', '2026-02-20 07:01:39'),
(7, 'Vendor', 'Catering', '7336866454', 'Vapi', 'India', 'Gujarat', 'Vapi', 'Active', '2026-02-20 07:02:56', '2026-02-20 07:02:56'),
(8, 'qwerty', 'Photography', '9634567876', 'Vapi', 'India', 'Gujarat', 'Vapi', 'Active', '2026-02-20 07:03:32', '2026-02-20 07:03:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `client_name` (`client_name`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_vendors`
--
ALTER TABLE `event_vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_requirement` (`event_id`,`requirement_type`,`service_name`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_service` (`service_name`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_location` (`country`,`state`,`city`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique` (`transaction_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `requirements`
--
ALTER TABLE `requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `idx_service_id` (`service_id`),
  ADD KEY `idx_service_name` (`service_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `services_category`
--
ALTER TABLE `services_category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_detail_templates`
--
ALTER TABLE `service_detail_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `event_vendors`
--
ALTER TABLE `event_vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `requirements`
--
ALTER TABLE `requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `services_category`
--
ALTER TABLE `services_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `service_detail_templates`
--
ALTER TABLE `service_detail_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `vendor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_vendors`
--
ALTER TABLE `event_vendors`
  ADD CONSTRAINT `fk_ev_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ev_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
