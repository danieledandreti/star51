-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Creato il: Nov 09, 2025 alle 16:10
-- Versione del server: 8.0.40
-- Versione PHP: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `novastar51_solo`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `ns_admins`
--

CREATE TABLE `ns_admins` (
  `id_admin` int UNSIGNED NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'First name',
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Last name',
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Login username',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hashed password',
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email address',
  `level` tinyint UNSIGNED NOT NULL DEFAULT '3' COMMENT 'Admin level: 0=Super, 1=Admin, 2=Editor, 3=Operator',
  `is_active` tinyint NOT NULL DEFAULT '0' COMMENT 'Active status',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation date',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int UNSIGNED DEFAULT NULL COMMENT 'Created by admin ID',
  `reset_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Password reset token',
  `reset_expires` datetime DEFAULT NULL COMMENT 'Token expiration time',
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Force password change on next login (0=No, 1=Yes)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Administrators management';

-- --------------------------------------------------------

--
-- Struttura della tabella `ns_articles`
--

CREATE TABLE `ns_articles` (
  `id_article` int UNSIGNED NOT NULL,
  `id_subcategory` int UNSIGNED NOT NULL COMMENT 'Subcategory ID reference',
  `article_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Article title',
  `article_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Article content',
  `article_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Article summary',
  `item_collection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Collection type/format',
  `item_year` year DEFAULT NULL COMMENT 'Item year',
  `youtube_video` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'YouTube video ID',
  `image_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primary image',
  `image_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secondary image',
  `publish_date` date DEFAULT NULL COMMENT 'Publication date',
  `show_publish_date` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint NOT NULL DEFAULT '1' COMMENT 'Active status',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation date',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Last update',
  `created_by` int UNSIGNED NOT NULL COMMENT 'Created by admin ID',
  -- GENERATED STORED column: must be last - auto-computed from item_collection, never inserted/updated manually
  `item_format` varchar(50) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when ((lower(`item_collection`) like _utf8mb4'%4k%') or (lower(`item_collection`) like _utf8mb4'%uhd%')) then _utf8mb4'4K UHD' when (`item_collection` like _utf8mb4'%BD%') then _utf8mb4'Blu-ray' when (upper(`item_collection`) like _utf8mb4'%DVD%') then _utf8mb4'DVD' when ((`item_collection` like _utf8mb4'%Stampa%') or (`item_collection` like _utf8mb4'%Canvas%') or (`item_collection` like _utf8mb4'%Print%')) then _utf8mb4'Stampa' else _utf8mb4'Altro' end)) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Articles with Universal Collection System';

-- --------------------------------------------------------

--
-- Struttura della tabella `ns_categories`
--

CREATE TABLE `ns_categories` (
  `id_category` int UNSIGNED NOT NULL,
  `category_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Category name',
  `category_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Category description',
  `is_active` tinyint NOT NULL DEFAULT '1' COMMENT 'Active status',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation date',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Last update',
  `created_by` int UNSIGNED NOT NULL COMMENT 'Created by admin ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categories management';

-- --------------------------------------------------------

--
-- Struttura della tabella `ns_login_security`
--

CREATE TABLE `ns_login_security` (
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Client IP address (supports IPv4 and IPv6)',
  `login_attempts` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Number of failed login attempts',
  `lockout_until` int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Unix timestamp when lockout expires (0 = not locked)',
  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of last login attempt',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'First attempt timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Login security - Rate limiting and brute force protection';

-- --------------------------------------------------------

--
-- Struttura della tabella `ns_requests`
--

CREATE TABLE `ns_requests` (
  `id_request` int UNSIGNED NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'First name',
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Last name',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Email address',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phone number',
  `request_message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Request message',
  `request_status` enum('new','read','replied','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new' COMMENT 'Request status',
  `is_active` tinyint NOT NULL DEFAULT '1' COMMENT 'Active status',
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Request date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User requests management';

-- --------------------------------------------------------

--
-- Struttura della tabella `ns_subcategories`
--

CREATE TABLE `ns_subcategories` (
  `id_subcategory` int UNSIGNED NOT NULL,
  `id_category` int UNSIGNED NOT NULL COMMENT 'Category ID reference',
  `subcategory_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Subcategory name',
  `subcategory_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Subcategory description',
  `is_active` tinyint NOT NULL DEFAULT '1' COMMENT 'Active status',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation date',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int UNSIGNED NOT NULL COMMENT 'Created by admin ID'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Subcategories management';

-- --------------------------------------------------------

--
-- RESERVED SYSTEM DATA - DO NOT DELETE
-- These records are required for system functionality
-- Cat 1/2 and Subcat 1/2 are hardcoded in frontend queries
--

--
-- Reserved Categories (ID 1-2)
--
INSERT INTO `ns_categories` (`id_category`, `category_name`, `category_description`, `is_active`, `created_by`) VALUES
(1, 'Extra', 'System category for orphan articles - DO NOT DELETE', 1, 0),
(2, 'Info', 'System category for News and special content - DO NOT DELETE', 1, 0);

--
-- Reserved Subcategories (ID 1-2)
--
INSERT INTO `ns_subcategories` (`id_subcategory`, `id_category`, `subcategory_name`, `subcategory_description`, `is_active`, `created_by`) VALUES
(1, 1, 'Varie', 'Default container for orphan articles - DO NOT DELETE', 1, 0),
(2, 2, 'News', 'News and updates - Displayed in homepage sidebar', 1, 0);

-- --------------------------------------------------------

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `ns_admins`
--
ALTER TABLE `ns_admins`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_reset_token` (`reset_token`);

--
-- Indici per le tabelle `ns_articles`
--
ALTER TABLE `ns_articles`
  ADD PRIMARY KEY (`id_article`),
  ADD KEY `idx_subcategory` (`id_subcategory`),
  ADD KEY `idx_publish_date` (`publish_date`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_item_format` (`item_format`);

--
-- Indici per le tabelle `ns_categories`
--
ALTER TABLE `ns_categories`
  ADD PRIMARY KEY (`id_category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indici per le tabelle `ns_login_security`
--
ALTER TABLE `ns_login_security`
  ADD PRIMARY KEY (`ip_address`),
  ADD KEY `idx_lockout` (`lockout_until`),
  ADD KEY `idx_last_attempt` (`last_attempt`);

--
-- Indici per le tabelle `ns_requests`
--
ALTER TABLE `ns_requests`
  ADD PRIMARY KEY (`id_request`),
  ADD KEY `idx_request_date` (`request_date`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indici per le tabelle `ns_subcategories`
--
ALTER TABLE `ns_subcategories`
  ADD PRIMARY KEY (`id_subcategory`),
  ADD KEY `idx_category` (`id_category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `ns_admins`
--
ALTER TABLE `ns_admins`
  MODIFY `id_admin` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ns_articles`
--
ALTER TABLE `ns_articles`
  MODIFY `id_article` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ns_categories`
--
ALTER TABLE `ns_categories`
  MODIFY `id_category` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `ns_requests`
--
ALTER TABLE `ns_requests`
  MODIFY `id_request` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `ns_subcategories`
--
ALTER TABLE `ns_subcategories`
  MODIFY `id_subcategory` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
