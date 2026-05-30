-- Create and switch to the database
CREATE DATABASE IF NOT EXISTS pearlz;
USE pearlz;

-- 1. Users Table (For online customers, VIPs, and Admins)
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'customer', -- e.g., 'admin', 'customer', 'vip'
    phone VARCHAR(20),
    shipping_address TEXT,
    billing_address TEXT,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Categories Table
CREATE TABLE Categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);

-- 3. Products Table (Includes customization flags)
CREATE TABLE Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    base_sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    is_limited_edition BOOLEAN DEFAULT TRUE,
    allows_custom_text BOOLEAN DEFAULT FALSE, 
    custom_text_limit INT DEFAULT 0, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES Categories(category_id) ON DELETE SET NULL
);

-- 4. Product_Variants Table (Strict inventory control)
CREATE TABLE Product_Variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_sku VARCHAR(50) NOT NULL UNIQUE,
    variant_name VARCHAR(100) NOT NULL, 
    price_override DECIMAL(10, 2), 
    stock_quantity INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE
);

-- 5. Product_Images Table (Shopee-style image rendering)
CREATE TABLE Product_Images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT NULL, 
    image_url VARCHAR(255) NOT NULL,
    image_type VARCHAR(50) DEFAULT 'feature', -- 'feature', 'gallery', or 'variant'
    alt_text VARCHAR(100),
    is_primary BOOLEAN DEFAULT FALSE, 
    display_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES Product_Variants(variant_id) ON DELETE CASCADE
);

-- 6. Orders Table (Updated for Walk-in / Guest Checkout)
CREATE TABLE Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- NULL allows for walk-in/guest orders
    order_source VARCHAR(50) DEFAULT 'online', -- e.g., 'online', 'in_person_pos'
    
    -- Guest checkout info (Used if user_id is NULL)
    guest_first_name VARCHAR(50),
    guest_last_name VARCHAR(50),
    guest_email VARCHAR(100),
    guest_phone VARCHAR(20),
    
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending', 
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- 7. Order_Items Table (Tracks variants and custom engravings)
CREATE TABLE Order_Items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    variant_id INT NOT NULL, 
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL, 
    custom_text VARCHAR(255) NULL, 
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES Product_Variants(variant_id) ON DELETE RESTRICT
);

-- 8. Payments Table
CREATE TABLE Payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL, -- e.g., 'Credit Card', 'Cash' (for in-person)
    transaction_id VARCHAR(100) UNIQUE,
    amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status VARCHAR(50) DEFAULT 'Completed', 
    FOREIGN KEY (order_id) REFERENCES Orders(order_id) ON DELETE CASCADE
);