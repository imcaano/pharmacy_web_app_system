-- Add payment_method column to orders table
ALTER TABLE `orders` ADD COLUMN `payment_method` ENUM('blockchain', 'hormuud', 'evc_plus', 'cash') DEFAULT 'blockchain' AFTER `payment_status`;

-- Add phone_number column for payment tracking
ALTER TABLE `orders` ADD COLUMN `phone_number` VARCHAR(20) DEFAULT NULL AFTER `payment_method`;

-- Add ussd_session_id for Hormuud USSD tracking
ALTER TABLE `orders` ADD COLUMN `ussd_session_id` VARCHAR(50) DEFAULT NULL AFTER `phone_number`; 