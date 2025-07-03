
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    estimated_time VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    whatsapp_number VARCHAR(20) NOT NULL,
    shoe_type VARCHAR(255),
    service_id INT NOT NULL,
    photo_path VARCHAR(255),
    delivery_method VARCHAR(50) NOT NULL,
    address TEXT,
    pickup_delivery_schedule DATETIME,
    total_price DECIMAL(10, 2),
    payment_option VARCHAR(50),
    order_status VARCHAR(50) DEFAULT 'Diterima',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

INSERT IGNORE INTO services (id, name, price, estimated_time) VALUES
(1, 'Deep Clean', 50000.00, '2-3 hari'),
(2, 'Fast Clean', 30000.00, '1 hari'),
(3, 'Unyellowing', 75000.00, '3-5 hari'),
(4, 'Repaint', 100000.00, '5-7 hari'),
(5, 'Leather Care', 60000.00, '2-3 hari');

INSERT IGNORE INTO admin_users (id, username, password) VALUES
(1, 'admin', MD5('admin123'));


