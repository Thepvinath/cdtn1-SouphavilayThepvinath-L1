-- SMART CRM - MEKONG MOBILE
-- BT2 - Migration 001: Initial Schema
-- Creates the 5 core tables.
-- Database must be selected by the migration runner.
-- Execute only once per database.

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE users (
    user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

CREATE TABLE customers (
    customer_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    phone_normalized VARCHAR(30) NOT NULL,
    email VARCHAR(150),
    address VARCHAR(255),
    status ENUM('ACTIVE', 'MERGED')
        NOT NULL DEFAULT 'ACTIVE',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,

    INDEX idx_customer_name (full_name),
    INDEX idx_customer_phone (phone_normalized)
);

CREATE TABLE merge_operations (
    merge_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    master_customer_id BIGINT NOT NULL,
    performed_by BIGINT NOT NULL,
    merged_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (master_customer_id)
        REFERENCES customers(customer_id),

    FOREIGN KEY (performed_by)
        REFERENCES users(user_id)
);

CREATE TABLE merge_items (
    merge_item_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    merge_id BIGINT NOT NULL,
    source_customer_id BIGINT NOT NULL,

    UNIQUE KEY uq_merge_source (merge_id, source_customer_id),

    FOREIGN KEY (merge_id)
        REFERENCES merge_operations(merge_id),

    FOREIGN KEY (source_customer_id)
        REFERENCES customers(customer_id)
);
