-- SMART CRM - MEKONG MOBILE
-- BT2 - Seed Data
-- Demo data for local development and testing

USE smartcrm;

START TRANSACTION;

-- 1. Seed roles
INSERT IGNORE INTO roles (role_name)
VALUES
    ('SALES_STAFF'),
    ('STORE_MANAGER'),
    ('MARKETING');

-- 2. Seed customer MASTER
INSERT INTO customers (
    full_name,
    phone,
    phone_normalized,
    email,
    address,
    status
)
SELECT
    'BT2 DEMO MASTER',
    '0888802026',
    '0888802026',
    'bt2.master@example.com',
    'Viet Nam',
    'ACTIVE'
WHERE NOT EXISTS (
    SELECT 1
    FROM customers
    WHERE email = 'bt2.master@example.com'
);

-- 3. Seed customer SOURCE
-- Same normalized phone for duplicate detection
INSERT INTO customers (
    full_name,
    phone,
    phone_normalized,
    email,
    address,
    status
)
SELECT
    'BT2 DEMO SOURCE',
    '0888802026',
    '0888802026',
    'bt2.source@example.com',
    'Viet Nam',
    'ACTIVE'
WHERE NOT EXISTS (
    SELECT 1
    FROM customers
    WHERE email = 'bt2.source@example.com'
);

-- 4. Seed independent customer
INSERT INTO customers (
    full_name,
    phone,
    phone_normalized,
    email,
    address,
    status
)
SELECT
    'BT2 DEMO NORMAL',
    '0888802027',
    '0888802027',
    'bt2.normal@example.com',
    'Viet Nam',
    'ACTIVE'
WHERE NOT EXISTS (
    SELECT 1
    FROM customers
    WHERE email = 'bt2.normal@example.com'
);

COMMIT;
