-- Payment tables for Waafi Pay integration

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_id VARCHAR(255),
    transaction_id VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    customer_phone VARCHAR(20),
    customer_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);

-- Payment logs table for tracking callbacks
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(255) NOT NULL,
    payment_id VARCHAR(255),
    status VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2),
    order_id INT,
    callback_data TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_order_id (order_id)
);

-- Add payment columns to orders table if they don't exist
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_id VARCHAR(255),
ADD COLUMN IF NOT EXISTS transaction_id VARCHAR(255),
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed', 'cancelled') DEFAULT 'pending';

-- Sample data for testing
INSERT INTO payments (order_id, payment_id, transaction_id, amount, currency, status, customer_phone, customer_name) VALUES
(1, 'PAY001', 'TXN001', 25.99, 'USD', 'completed', '+252615301507', 'Test Customer'),
(2, 'PAY002', 'TXN002', 15.50, 'USD', 'pending', '+252615301508', 'John Doe'); 