-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 12:06 AM
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
-- Database: `pharmacy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `customer_id`, `medicine_id`, `quantity`, `created_at`) VALUES
(6, 12, 4, 1, '2025-07-09 21:37:52');

-- --------------------------------------------------------

--
-- Table structure for table `dispute_logs`
--

CREATE TABLE `dispute_logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','resolved','rejected') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `pharmacy_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `country_of_origin` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `batch_source_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `pharmacy_id`, `name`, `description`, `manufacturer`, `country_of_origin`, `category`, `price`, `stock_quantity`, `expiry_date`, `requires_prescription`, `status`, `created_at`, `batch_source_info`) VALUES
(4, 3, 'PARECTAMOL', NULL, 'hormuuud', 'somlia', 'Antibiotic', 20.00, 1998, '2025-07-19', 0, 'active', '2025-07-07 10:23:44', NULL),
(5, 3, 'anti biotic', NULL, NULL, NULL, 'Antibiotic', 20.00, 1997, NULL, 0, 'active', '2025-07-07 16:05:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `pharmacy_id` int(11) NOT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `payment_method` enum('blockchain','hormuud','evc_plus','cash') DEFAULT 'blockchain',
  `phone_number` varchar(20) DEFAULT NULL,
  `ussd_session_id` varchar(50) DEFAULT NULL,
  `transaction_hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payout_amount` decimal(10,2) DEFAULT NULL,
  `payout_tx_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `pharmacy_id`, `prescription_id`, `total_amount`, `status`, `payment_status`, `payment_method`, `phone_number`, `ussd_session_id`, `transaction_hash`, `created_at`, `payout_amount`, `payout_tx_hash`) VALUES
(2, 12, 3, NULL, 20.00, 'approved', 'pending', 'blockchain', NULL, NULL, '0x1d7ab29d90de313aa02ebc24522ab7307d8f03f37951fb2ce0183aa1026badf3', '2025-07-07 10:29:59', NULL, NULL),
(4, 12, 3, NULL, 20.00, 'approved', 'pending', 'blockchain', NULL, NULL, '0x47addcd6e130c8a34986af5337c709a1b5ca95558cf1c4b1348023db1e8561f8', '2025-07-07 16:18:36', NULL, NULL),
(5, 12, 3, NULL, 20.00, 'pending', 'pending', 'hormuud', '619837755', NULL, NULL, '2025-07-09 21:37:31', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `medicine_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `medicine_id`, `quantity`, `price`) VALUES
(2, 2, 4, 1, 20.00),
(3, 4, 5, 1, 20.00),
(4, 5, 5, 1, 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `pharmacies`
--

CREATE TABLE `pharmacies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `pharmacy_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `wallet_address` varchar(42) DEFAULT NULL,
  `trust_score` decimal(5,2) DEFAULT 100.00,
  `performance` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pharmacies`
--

INSERT INTO `pharmacies` (`id`, `user_id`, `pharmacy_name`, `address`, `phone`, `license_number`, `status`, `wallet_address`, `trust_score`, `performance`) VALUES
(2, 6, 'My Pharmacy', '', NULL, NULL, 'active', NULL, 100.00, NULL),
(3, 11, 'shaafi', 'km4\r\nkm4', '6666', '77777', 'active', NULL, 100.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `doctor_name` varchar(255) DEFAULT NULL,
  `prescription_file` varchar(255) DEFAULT NULL,
  `prescription_hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_notes` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verification_tx_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `customer_id`, `doctor_name`, `prescription_file`, `prescription_hash`, `status`, `created_at`, `verification_status`, `verification_notes`, `verified_at`, `verification_tx_hash`) VALUES
(2, 12, NULL, 'presc_686ba1bb65e453.19727776.pdf', NULL, 'pending', '2025-07-07 10:30:19', 'verified', 'h', '2025-07-07 12:13:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prescription_orders`
--

CREATE TABLE `prescription_orders` (
  `id` int(11) NOT NULL,
  `prescription_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `smart_contract_payments`
--

CREATE TABLE `smart_contract_payments` (
  `id` int(11) NOT NULL,
  `pharmacy_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tx_hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','received','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('admin','pharmacy','customer') NOT NULL,
  `metamask_address` varchar(42) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_hash` varchar(66) DEFAULT NULL,
  `tx_hash` varchar(66) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `user_type`, `metamask_address`, `created_at`, `updated_at`, `user_hash`, `tx_hash`) VALUES
(6, 'abdi@gmail.com', '$2y$10$GbaCfEKRGRFmdSowIGZBrOK1UHq7XdBDTq9Kz5i6PfXgJBQY5yDlq', 'admin', '0x19523a25be5533a3080b07859580e62294235523', '2025-06-23 09:27:15', '2025-06-23 11:23:02', NULL, NULL),
(11, 'iamcaano2@gmail.com', '$2y$10$qOtnrv0hevBsfNtNlndNj.2Ak4MTgcgahSJaAKlEiJnMntn.kekwO', 'pharmacy', '0x2546BcD3c84621e976D8185a91A922aE77ECEc30', '2025-07-07 10:22:59', '2025-07-07 10:22:59', '0x9db22984d7a856e2493387dcb465b5999f9d5989c38676923bc50b3d02151e2d', '0x58fcf08914ea584a30ec238367bf07b2c394940fafaa732c6f6eab7f521d45a7'),
(12, 'imhamza1@yahoo.com', '$2y$10$oi9IeX57vc.xgeeHQ.w.yeA7uNHMMOjvNMnsC1BRYMBGA3QSia9SK', 'admin', '0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266', '2025-07-07 10:29:26', '2025-07-11 21:30:06', '0x200fd56ffa00380272934f34ebab7a36f8de79f7415180729c914a1066269074', '0x77e405704983eee71c88f933da4699b968cb101e2df9d1472f02340eb9088eb7');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `dispute_logs`
--
ALTER TABLE `dispute_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pharmacy_id` (`pharmacy_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `pharmacy_id` (`pharmacy_id`),
  ADD KEY `prescription_id` (`prescription_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `prescription_orders`
--
ALTER TABLE `prescription_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prescription_id` (`prescription_id`),
  ADD KEY `medicine_id` (`medicine_id`);

--
-- Indexes for table `smart_contract_payments`
--
ALTER TABLE `smart_contract_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pharmacy_id` (`pharmacy_id`),
  ADD KEY `order_id` (`order_id`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `metamask_address` (`metamask_address`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `dispute_logs`
--
ALTER TABLE `dispute_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pharmacies`
--
ALTER TABLE `pharmacies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prescription_orders`
--
ALTER TABLE `prescription_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `smart_contract_payments`
--
ALTER TABLE `smart_contract_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `dispute_logs`
--
ALTER TABLE `dispute_logs`
  ADD CONSTRAINT `dispute_logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `dispute_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `medicines_ibfk_1` FOREIGN KEY (`pharmacy_id`) REFERENCES `pharmacies` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`pharmacy_id`) REFERENCES `pharmacies` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `pharmacies`
--
ALTER TABLE `pharmacies`
  ADD CONSTRAINT `pharmacies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `prescriptions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `prescription_orders`
--
ALTER TABLE `prescription_orders`
  ADD CONSTRAINT `prescription_orders_ibfk_1` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prescription_orders_ibfk_2` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `smart_contract_payments`
--
ALTER TABLE `smart_contract_payments`
  ADD CONSTRAINT `smart_contract_payments_ibfk_1` FOREIGN KEY (`pharmacy_id`) REFERENCES `pharmacies` (`id`),
  ADD CONSTRAINT `smart_contract_payments_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
