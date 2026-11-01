-- =====================================================================
-- All Saints' Anglican Church, Yaba — Paystack Donation System
-- Database Schema
-- =====================================================================
-- Import this file into MySQL before running the application, e.g.:
--   mysql -u root -p allsaints_donations < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS allsaints_donations
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE allsaints_donations;

-- ---------------------------------------------------------------------
-- Table: donations
-- Stores every donation attempt. A row is first created with a
-- "pending" status when the donor submits the form, and is updated to
-- "success" or "failed" once Paystack's transaction has been verified
-- server-side. Never trust the browser alone — always verify.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS donations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    phone           VARCHAR(30)     NOT NULL,
    category        VARCHAR(50)     NOT NULL,
    amount          DECIMAL(12,2)   NOT NULL,       -- amount in Naira (not kobo)
    prayer_request  TEXT            NULL,
    reference       VARCHAR(100)    NOT NULL UNIQUE, -- Paystack transaction reference
    status          ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
    channel         VARCHAR(30)     NULL,             -- card, bank, ussd, etc.
    paid_at         DATETIME        NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table: admin_users
-- Church office / finance staff who can log into the admin dashboard.
-- Passwords are stored as bcrypt hashes (see admin/includes/seed notes
-- below) — never store plain-text passwords.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(50)     NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    full_name       VARCHAR(150)    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Seed an initial admin user.
-- IMPORTANT: The hash below corresponds to the password "ChangeMe123!".
-- Log in once, then change it immediately (or generate your own hash
-- with PHP's password_hash() and update this row).
-- ---------------------------------------------------------------------
INSERT INTO admin_users (username, password_hash, full_name)
VALUES (
    'admin',
    '$2b$10$cobsOJe6g05.xzASdB1HfOvtSWXKT/NSJDYW3Bs3UEl72oof2wcTi', -- ChangeMe123!
    'Church Office Administrator'
)
ON DUPLICATE KEY UPDATE username = username;
