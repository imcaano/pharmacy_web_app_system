-- Sample data for pharmacy web app system

-- Sample users
INSERT INTO users (email, password, user_type, metamask_address) VALUES
('admin@pharmacy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0x1234567890123456789012345678901234567890'),
('pharmacy1@pharmacy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacy', '0x2345678901234567890123456789012345678901'),
('pharmacy2@pharmacy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacy', '0x3456789012345678901234567890123456789012'),
('customer1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0x4567890123456789012345678901234567890123'),
('customer2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '0x5678901234567890123456789012345678901234');

-- Sample medicines
INSERT INTO medicines (name, description, price, stock_quantity, pharmacy_id) VALUES
('Paracetamol 500mg', 'Pain relief and fever reduction', 5.99, 100, 2),
('Ibuprofen 400mg', 'Anti-inflammatory pain relief', 7.50, 75, 2),
('Amoxicillin 250mg', 'Antibiotic for bacterial infections', 12.99, 50, 2),
('Omeprazole 20mg', 'Acid reflux medication', 15.75, 60, 2),
('Aspirin 100mg', 'Blood thinner and pain relief', 4.25, 120, 2),
('Cetirizine 10mg', 'Allergy relief medication', 8.99, 80, 3),
('Loratadine 10mg', 'Non-drowsy allergy relief', 9.50, 65, 3),
('Vitamin D3 1000IU', 'Vitamin D supplement', 11.25, 90, 3),
('Calcium 500mg', 'Calcium supplement', 6.75, 110, 3),
('Fish Oil 1000mg', 'Omega-3 supplement', 14.99, 45, 3);

-- Sample orders
INSERT INTO orders (customer_id, pharmacy_id, status, total_amount, created_at) VALUES
(4, 2, 'pending', 25.48, '2024-01-15 10:30:00'),
(4, 2, 'processing', 18.99, '2024-01-14 14:20:00'),
(5, 3, 'completed', 32.75, '2024-01-13 09:15:00'),
(5, 3, 'pending', 15.50, '2024-01-12 16:45:00'),
(4, 2, 'shipped', 42.25, '2024-01-11 11:30:00');

-- Sample prescriptions
INSERT INTO prescriptions (customer_id, file_url, status, created_at) VALUES
(4, 'uploads/prescription1.pdf', 'pending', '2024-01-15 08:30:00'),
(4, 'uploads/prescription2.pdf', 'approved', '2024-01-14 12:15:00'),
(5, 'uploads/prescription3.pdf', 'pending', '2024-01-13 15:45:00'),
(5, 'uploads/prescription4.pdf', 'rejected', '2024-01-12 10:20:00'),
(4, 'uploads/prescription5.pdf', 'approved', '2024-01-11 14:30:00'); 