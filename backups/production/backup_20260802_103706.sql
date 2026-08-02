/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: u929623538_cibil
-- ------------------------------------------------------
-- Server version	11.8.8-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `_archived_activities`
--

DROP TABLE IF EXISTS `_archived_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_activities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_activities` WRITE;
/*!40000 ALTER TABLE `_archived_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_activities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_activity_logs`
--

DROP TABLE IF EXISTS `_archived_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_activity_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_activity_logs` WRITE;
/*!40000 ALTER TABLE `_archived_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_analysis_history`
--

DROP TABLE IF EXISTS `_archived_analysis_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_analysis_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `analysis_result` text DEFAULT NULL,
  `dispute_letter` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_analysis_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_analysis_history` WRITE;
/*!40000 ALTER TABLE `_archived_analysis_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_analysis_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_announcements`
--

DROP TABLE IF EXISTS `_archived_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `target_role` enum('all','admin','partner','client') DEFAULT 'all',
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_announcements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_announcements` WRITE;
/*!40000 ALTER TABLE `_archived_announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_announcements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_backup_logs`
--

DROP TABLE IF EXISTS `_archived_backup_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_backup_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_backup_logs` WRITE;
/*!40000 ALTER TABLE `_archived_backup_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_backup_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_bank_accounts`
--

DROP TABLE IF EXISTS `_archived_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `account_holder_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `account_type` enum('savings','current','cash_credit') DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_number` (`account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_bank_accounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_bank_accounts` WRITE;
/*!40000 ALTER TABLE `_archived_bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_bank_disputes`
--

DROP TABLE IF EXISTS `_archived_bank_disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_bank_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `dispute_no` varchar(50) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_bank_disputes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_bank_disputes` WRITE;
/*!40000 ALTER TABLE `_archived_bank_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_bank_disputes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_bank_reconciliation`
--

DROP TABLE IF EXISTS `_archived_bank_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_bank_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` int(11) NOT NULL,
  `statement_date` date DEFAULT NULL,
  `closing_balance` decimal(15,2) DEFAULT NULL,
  `reconciled_balance` decimal(15,2) DEFAULT NULL,
  `difference` decimal(15,2) DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL,
  `reconciled_at` datetime DEFAULT NULL,
  `status` enum('pending','reconciled','discrepancy') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `bank_account_id` (`bank_account_id`),
  CONSTRAINT `_archived_bank_reconciliation_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `_archived_bank_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_bank_reconciliation`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_bank_reconciliation` WRITE;
/*!40000 ALTER TABLE `_archived_bank_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_bank_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_blog_posts`
--

DROP TABLE IF EXISTS `_archived_blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_blog_posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_blog_posts` WRITE;
/*!40000 ALTER TABLE `_archived_blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_blog_posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_budgets`
--

DROP TABLE IF EXISTS `_archived_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_year` int(11) NOT NULL,
  `budget_month` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `budget_amount` decimal(15,2) DEFAULT NULL,
  `actual_amount` decimal(15,2) DEFAULT 0.00,
  `variance` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_budget` (`budget_year`,`budget_month`,`account_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `_archived_budgets_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_budgets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_budgets` WRITE;
/*!40000 ALTER TABLE `_archived_budgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_budgets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_bureau_disputes`
--

DROP TABLE IF EXISTS `_archived_bureau_disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_bureau_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `bureau` varchar(50) DEFAULT NULL,
  `dispute_no` varchar(50) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `expected_response` date DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_dispute` (`dispute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_bureau_disputes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_bureau_disputes` WRITE;
/*!40000 ALTER TABLE `_archived_bureau_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_bureau_disputes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_call_logs`
--

DROP TABLE IF EXISTS `_archived_call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_call_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `call_type` enum('incoming','outgoing') DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `call_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_agent` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_call_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_call_logs` WRITE;
/*!40000 ALTER TABLE `_archived_call_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_call_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_case_assignments`
--

DROP TABLE IF EXISTS `_archived_case_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_case_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case` (`case_id`),
  KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_case_assignments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_case_assignments` WRITE;
/*!40000 ALTER TABLE `_archived_case_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_case_assignments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_cibil_history`
--

DROP TABLE IF EXISTS `_archived_cibil_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_cibil_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `recorded_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `_archived_cibil_history_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_cibil_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_cibil_history` WRITE;
/*!40000 ALTER TABLE `_archived_cibil_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_cibil_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_activity_log`
--

DROP TABLE IF EXISTS `_archived_client_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_type` (`activity_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_activity_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_activity_log` WRITE;
/*!40000 ALTER TABLE `_archived_client_activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_client_activity_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_agreements`
--

DROP TABLE IF EXISTS `_archived_client_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `agreement_no` varchar(50) DEFAULT NULL,
  `agreement_type` varchar(100) DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `status` enum('draft','sent','signed','expired') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_agreements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_agreements` WRITE;
/*!40000 ALTER TABLE `_archived_client_agreements` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_client_agreements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_issues`
--

DROP TABLE IF EXISTS `_archived_client_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `issue_type` enum('cibil','loan','financial') NOT NULL,
  `problem_description` text NOT NULL,
  `additional_info` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `status` enum('new','in-progress','resolved') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_issues`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_issues` WRITE;
/*!40000 ALTER TABLE `_archived_client_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_client_issues` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_login_history`
--

DROP TABLE IF EXISTS `_archived_client_login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `success` tinyint(4) DEFAULT 1,
  `logout_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_login_time` (`login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_login_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_login_history` WRITE;
/*!40000 ALTER TABLE `_archived_client_login_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_client_login_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_messages`
--

DROP TABLE IF EXISTS `_archived_client_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('client','partner','admin') DEFAULT 'client',
  `message` text NOT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `_archived_client_messages_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `client_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_messages` WRITE;
/*!40000 ALTER TABLE `_archived_client_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_client_messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_payments`
--

DROP TABLE IF EXISTS `_archived_client_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_email` varchar(100) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_payments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_payments` WRITE;
/*!40000 ALTER TABLE `_archived_client_payments` DISABLE KEYS */;
INSERT INTO `_archived_client_payments` VALUES
(1,'client@example.com',1,15000.00,'paid','TXN123456','2026-05-02','2026-05-02 11:34:26');
/*!40000 ALTER TABLE `_archived_client_payments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_profiles`
--

DROP TABLE IF EXISTS `_archived_client_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
  `address_line1` text DEFAULT NULL,
  `address_line2` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `state_code` varchar(10) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `pan_number` varchar(20) DEFAULT NULL,
  `aadhar_number` varchar(20) DEFAULT NULL,
  `aadhar_last4` varchar(4) DEFAULT NULL,
  `voter_id` varchar(20) DEFAULT NULL,
  `passport_number` varchar(20) DEFAULT NULL,
  `employment_type` enum('salaried','self_employed','business','retired','student','unemployed') DEFAULT NULL,
  `employer_name` varchar(200) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT NULL,
  `income_proof_submitted` tinyint(4) DEFAULT 0,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `preferred_language` varchar(10) DEFAULT 'en',
  `email_notifications` tinyint(4) DEFAULT 1,
  `sms_notifications` tinyint(4) DEFAULT 1,
  `whatsapp_notifications` tinyint(4) DEFAULT 0,
  `profile_completed` tinyint(4) DEFAULT 0,
  `kyc_verified` tinyint(4) DEFAULT 0,
  `kyc_verified_at` datetime DEFAULT NULL,
  `kyc_verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_pan` (`pan_number`),
  KEY `idx_phone` (`phone`),
  KEY `idx_kyc_verified` (`kyc_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_profiles` WRITE;
/*!40000 ALTER TABLE `_archived_client_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_client_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_client_reviews`
--

DROP TABLE IF EXISTS `_archived_client_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_client_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_client_reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_client_reviews` WRITE;
/*!40000 ALTER TABLE `_archived_client_reviews` DISABLE KEYS */;
INSERT INTO `_archived_client_reviews` VALUES
(1,'Maneet Singh','maneet@example.com',5,'Thank you sir, thank you very much for doing my work in a very short time. Unauthorized loan removed from my wife\'s CIBIL within weeks.','approved','2026-05-02 11:34:26'),
(2,'Sunil Enterprises','sunil@example.com',5,'Excellent work done by this company. My credit score improved by 87 points after settled account removal.','approved','2026-05-02 11:34:26'),
(3,'Ranjeet Thakur','ranjeet@example.com',5,'Very professional team. They removed a suit filed entry from my CIBIL that was holding back my loan approval.','approved','2026-05-02 11:34:26');
/*!40000 ALTER TABLE `_archived_client_reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_companies`
--

DROP TABLE IF EXISTS `_archived_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) NOT NULL,
  `legal_name` varchar(200) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `cin_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `postal_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`company_name`),
  KEY `idx_gst` (`gst_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_companies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_companies` WRITE;
/*!40000 ALTER TABLE `_archived_companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_companies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_company_assets`
--

DROP TABLE IF EXISTS `_archived_company_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_company_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_code` varchar(50) DEFAULT NULL,
  `asset_name` varchar(200) NOT NULL,
  `asset_type` enum('laptop','desktop','mobile','accessory','furniture','vehicle') DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `warranty_until` date DEFAULT NULL,
  `vendor_name` varchar(200) DEFAULT NULL,
  `status` enum('available','assigned','maintenance','damaged','disposed') DEFAULT 'available',
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `_archived_company_assets_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_company_assets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_company_assets` WRITE;
/*!40000 ALTER TABLE `_archived_company_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_company_assets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_compliance_documents`
--

DROP TABLE IF EXISTS `_archived_compliance_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_compliance_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `doc_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_compliance_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_compliance_documents` WRITE;
/*!40000 ALTER TABLE `_archived_compliance_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_compliance_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_connectors`
--

DROP TABLE IF EXISTS `_archived_connectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_connectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` enum('bank','ca','lawyer','property','vehicle','other') DEFAULT 'other',
  `company` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `leads_referred` int(11) DEFAULT 0,
  `commission_due` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_connectors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_connectors` WRITE;
/*!40000 ALTER TABLE `_archived_connectors` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_connectors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_consent_forms`
--

DROP TABLE IF EXISTS `_archived_consent_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_consent_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `consent_type` varchar(100) DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `provided_date` date DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('pending','provided','expired') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_consent_forms`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_consent_forms` WRITE;
/*!40000 ALTER TABLE `_archived_consent_forms` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_consent_forms` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_contacts`
--

DROP TABLE IF EXISTS `_archived_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `category` enum('bank','ca','lawyer','property','vehicle','others') DEFAULT 'others',
  `name` varchar(100) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_contacts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_contacts` WRITE;
/*!40000 ALTER TABLE `_archived_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_contacts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_coupons`
--

DROP TABLE IF EXISTS `_archived_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'fixed',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT 1,
  `used_count` int(11) DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_coupons`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_coupons` WRITE;
/*!40000 ALTER TABLE `_archived_coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_coupons` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_credit_analysis`
--

DROP TABLE IF EXISTS `_archived_credit_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_credit_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `cibil_score` int(11) DEFAULT NULL,
  `experian_score` int(11) DEFAULT NULL,
  `equifax_score` int(11) DEFAULT NULL,
  `crif_score` int(11) DEFAULT NULL,
  `issues` text DEFAULT NULL,
  `analyst_notes` text DEFAULT NULL,
  `analyst_id` int(11) DEFAULT NULL,
  `status` enum('pending','analyzed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `analyzed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_analyst` (`analyst_id`),
  CONSTRAINT `_archived_credit_analysis_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_credit_analysis`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_credit_analysis` WRITE;
/*!40000 ALTER TABLE `_archived_credit_analysis` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_credit_analysis` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_credit_issues`
--

DROP TABLE IF EXISTS `_archived_credit_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_credit_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `analysis_id` int(11) DEFAULT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','disputed','resolved') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_analysis` (`analysis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_credit_issues`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_credit_issues` WRITE;
/*!40000 ALTER TABLE `_archived_credit_issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_credit_issues` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_customer_requests`
--

DROP TABLE IF EXISTS `_archived_customer_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_customer_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_customer_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_customer_requests` WRITE;
/*!40000 ALTER TABLE `_archived_customer_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_customer_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_daily_operations_reports`
--

DROP TABLE IF EXISTS `_archived_daily_operations_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_daily_operations_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_date` date DEFAULT NULL,
  `cases_opened` int(11) DEFAULT NULL,
  `cases_closed` int(11) DEFAULT NULL,
  `avg_resolution_days` decimal(5,2) DEFAULT NULL,
  `sla_met_percent` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_daily_operations_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_daily_operations_reports` WRITE;
/*!40000 ALTER TABLE `_archived_daily_operations_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_daily_operations_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_disclaimer_consent`
--

DROP TABLE IF EXISTS `_archived_disclaimer_consent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_disclaimer_consent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `consent_given` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_disclaimer_consent`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_disclaimer_consent` WRITE;
/*!40000 ALTER TABLE `_archived_disclaimer_consent` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_disclaimer_consent` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_dispute_documents`
--

DROP TABLE IF EXISTS `_archived_dispute_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_dispute_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `doc_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dispute` (`dispute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_dispute_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_dispute_documents` WRITE;
/*!40000 ALTER TABLE `_archived_dispute_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_dispute_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_document_analyses`
--

DROP TABLE IF EXISTS `_archived_document_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_document_analyses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) DEFAULT 'credit_report',
  `filename` varchar(255) DEFAULT NULL,
  `extracted_text` text DEFAULT NULL,
  `analysis_result` text DEFAULT NULL,
  `dispute_letter` text DEFAULT NULL,
  `guidance` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_document_analyses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_document_analyses` WRITE;
/*!40000 ALTER TABLE `_archived_document_analyses` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_document_analyses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_document_verification_logs`
--

DROP TABLE IF EXISTS `_archived_document_verification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_document_verification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `action` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_document` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_document_verification_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_document_verification_logs` WRITE;
/*!40000 ALTER TABLE `_archived_document_verification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_document_verification_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_documents`
--

DROP TABLE IF EXISTS `_archived_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `verified_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `_archived_documents_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `_archived_documents_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_documents` WRITE;
/*!40000 ALTER TABLE `_archived_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_email_logs`
--

DROP TABLE IF EXISTS `_archived_email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `recipient_email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `template_used` varchar(100) DEFAULT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_email_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_email_logs` WRITE;
/*!40000 ALTER TABLE `_archived_email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_email_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_email_queue`
--

DROP TABLE IF EXISTS `_archived_email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_email_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_email_queue` WRITE;
/*!40000 ALTER TABLE `_archived_email_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_email_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_employee_incentives`
--

DROP TABLE IF EXISTS `_archived_employee_incentives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_employee_incentives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `incentive_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `month_year` varchar(10) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_employee_incentives`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_employee_incentives` WRITE;
/*!40000 ALTER TABLE `_archived_employee_incentives` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_employee_incentives` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_employee_shifts`
--

DROP TABLE IF EXISTS `_archived_employee_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_employee_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `shift_id` (`shift_id`),
  CONSTRAINT `_archived_employee_shifts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `_archived_employee_shifts_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_employee_shifts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_employee_shifts` WRITE;
/*!40000 ALTER TABLE `_archived_employee_shifts` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_employee_shifts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_error_logs`
--

DROP TABLE IF EXISTS `_archived_error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_message` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_error_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_error_logs` WRITE;
/*!40000 ALTER TABLE `_archived_error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_error_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_expenses`
--

DROP TABLE IF EXISTS `_archived_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_expenses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_expenses` WRITE;
/*!40000 ALTER TABLE `_archived_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_expenses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_followup_reminders`
--

DROP TABLE IF EXISTS `_archived_followup_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_followup_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `followup_id` int(11) NOT NULL,
  `reminder_type` enum('whatsapp','email','sms','in_app') DEFAULT 'in_app',
  `sent_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','sent','failed','delivered') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_followup_id` (`followup_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `_archived_followup_reminders_ibfk_1` FOREIGN KEY (`followup_id`) REFERENCES `_archived_followups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_followup_reminders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_followup_reminders` WRITE;
/*!40000 ALTER TABLE `_archived_followup_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_followup_reminders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_followup_settings`
--

DROP TABLE IF EXISTS `_archived_followup_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_followup_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `auto_schedule` tinyint(4) DEFAULT 1,
  `reminder_hours_before` int(11) DEFAULT 24,
  `max_reminders` int(11) DEFAULT 3,
  `reminder_interval_hours` int(11) DEFAULT 24,
  `whatsapp_enabled` tinyint(4) DEFAULT 1,
  `email_enabled` tinyint(4) DEFAULT 1,
  `sms_enabled` tinyint(4) DEFAULT 0,
  `in_app_enabled` tinyint(4) DEFAULT 1,
  `default_priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_id` (`partner_id`),
  CONSTRAINT `_archived_followup_settings_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_followup_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_followup_settings` WRITE;
/*!40000 ALTER TABLE `_archived_followup_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_followup_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_followups`
--

DROP TABLE IF EXISTS `_archived_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `followup_date` datetime NOT NULL,
  `followup_time` time DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','completed','missed','cancelled','rescheduled') DEFAULT 'pending',
  `reminder_sent` tinyint(4) DEFAULT 0,
  `reminder_count` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_followup_date` (`followup_date`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_reminder_sent` (`reminder_sent`),
  CONSTRAINT `_archived_followups_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `partner_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `_archived_followups_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_followups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_followups` WRITE;
/*!40000 ALTER TABLE `_archived_followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_followups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_franchise_commission`
--

DROP TABLE IF EXISTS `_archived_franchise_commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_franchise_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `commission` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `_archived_franchise_commission_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `franchise_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_franchise_commission`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_franchise_commission` WRITE;
/*!40000 ALTER TABLE `_archived_franchise_commission` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_franchise_commission` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_franchise_payouts`
--

DROP TABLE IF EXISTS `_archived_franchise_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_franchise_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processing','paid','rejected') DEFAULT 'pending',
  `request_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `_archived_franchise_payouts_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `franchise_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_franchise_payouts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_franchise_payouts` WRITE;
/*!40000 ALTER TABLE `_archived_franchise_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_franchise_payouts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_franchise_support_tickets`
--

DROP TABLE IF EXISTS `_archived_franchise_support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_franchise_support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `_archived_franchise_support_tickets_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `franchise_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_franchise_support_tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_franchise_support_tickets` WRITE;
/*!40000 ALTER TABLE `_archived_franchise_support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_franchise_support_tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_franchisees`
--

DROP TABLE IF EXISTS `_archived_franchisees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_franchisees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_franchisees`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_franchisees` WRITE;
/*!40000 ALTER TABLE `_archived_franchisees` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_franchisees` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_grievances`
--

DROP TABLE IF EXISTS `_archived_grievances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_grievances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `complaint_text` text NOT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `status` enum('new','in-review','resolved','rejected') DEFAULT 'new',
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_grievances`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_grievances` WRITE;
/*!40000 ALTER TABLE `_archived_grievances` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_grievances` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_gst_invoices`
--

DROP TABLE IF EXISTS `_archived_gst_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_gst_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `case_no` varchar(50) DEFAULT NULL,
  `service_name` varchar(200) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `cgst_rate` decimal(5,2) DEFAULT 9.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_rate` decimal(5,2) DEFAULT 9.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `igst_rate` decimal(5,2) DEFAULT 0.00,
  `igst_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `gst_type` enum('intra_state','inter_state') DEFAULT 'intra_state',
  `status` enum('draft','issued','paid','overdue','cancelled') DEFAULT 'issued',
  `issue_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `billing_name` varchar(200) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `billing_gstin` varchar(50) DEFAULT NULL,
  `billing_pan` varchar(50) DEFAULT NULL,
  `billing_state` varchar(100) DEFAULT NULL,
  `billing_state_code` varchar(10) DEFAULT NULL,
  `billing_email` varchar(100) DEFAULT NULL,
  `billing_phone` varchar(20) DEFAULT NULL,
  `company_name` varchar(200) DEFAULT 'CIBIL Repair Services',
  `company_gstin` varchar(50) DEFAULT '29AAKCI1234G1Z',
  `company_pan` varchar(50) DEFAULT 'AAKCI1234G',
  `company_address` text DEFAULT NULL,
  `company_state` varchar(100) DEFAULT 'Karnataka',
  `company_state_code` varchar(10) DEFAULT '29',
  `payment_terms` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `sac_code` varchar(20) DEFAULT '998311',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `idx_client` (`client_id`),
  KEY `idx_invoice_no` (`invoice_no`),
  KEY `idx_status` (`status`),
  KEY `idx_issue_date` (`issue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_gst_invoices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_gst_invoices` WRITE;
/*!40000 ALTER TABLE `_archived_gst_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_gst_invoices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_gst_returns`
--

DROP TABLE IF EXISTS `_archived_gst_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_gst_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `return_period` varchar(20) DEFAULT NULL,
  `return_type` enum('GSTR1','GSTR3B','GSTR9','GSTR9C') DEFAULT NULL,
  `filing_date` date DEFAULT NULL,
  `total_sales` decimal(15,2) DEFAULT NULL,
  `total_purchases` decimal(15,2) DEFAULT NULL,
  `total_output_tax` decimal(15,2) DEFAULT NULL,
  `total_input_tax` decimal(15,2) DEFAULT NULL,
  `net_tax_payable` decimal(15,2) DEFAULT NULL,
  `status` enum('draft','filed','amended') DEFAULT 'draft',
  `filed_by` int(11) DEFAULT NULL,
  `acknowledgment_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_gst_returns`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_gst_returns` WRITE;
/*!40000 ALTER TABLE `_archived_gst_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_gst_returns` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_interviews`
--

DROP TABLE IF EXISTS `_archived_interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `interviewer_id` int(11) DEFAULT NULL,
  `interview_date` datetime DEFAULT NULL,
  `interview_type` enum('telephonic','video','f2f','technical','hr') DEFAULT NULL,
  `interview_round` int(11) DEFAULT 1,
  `feedback` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `status` enum('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
  `next_interview_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `application_id` (`application_id`),
  KEY `interviewer_id` (`interviewer_id`),
  CONSTRAINT `_archived_interviews_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `_archived_job_applications` (`id`),
  CONSTRAINT `_archived_interviews_ibfk_2` FOREIGN KEY (`interviewer_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_interviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_interviews` WRITE;
/*!40000 ALTER TABLE `_archived_interviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_interviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_invoices`
--

DROP TABLE IF EXISTS `_archived_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `gst` decimal(12,2) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `status` enum('draft','sent','paid','overdue') DEFAULT 'draft',
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_invoice_no` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_invoices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_invoices` WRITE;
/*!40000 ALTER TABLE `_archived_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_invoices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_job_applications`
--

DROP TABLE IF EXISTS `_archived_job_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_opening_id` int(11) NOT NULL,
  `applicant_name` varchar(200) NOT NULL,
  `applicant_email` varchar(100) DEFAULT NULL,
  `applicant_phone` varchar(20) DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `expected_salary` decimal(12,2) DEFAULT NULL,
  `notice_period` int(11) DEFAULT NULL,
  `current_company` varchar(200) DEFAULT NULL,
  `current_ctc` decimal(12,2) DEFAULT NULL,
  `status` enum('applied','screening','interview','offered','hired','rejected') DEFAULT 'applied',
  `source` varchar(100) DEFAULT NULL,
  `applied_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_opening_id` (`job_opening_id`),
  CONSTRAINT `_archived_job_applications_ibfk_1` FOREIGN KEY (`job_opening_id`) REFERENCES `_archived_job_openings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_job_applications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_job_applications` WRITE;
/*!40000 ALTER TABLE `_archived_job_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_job_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_job_openings`
--

DROP TABLE IF EXISTS `_archived_job_openings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_job_openings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_code` varchar(50) DEFAULT NULL,
  `job_title` varchar(200) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `vacancies` int(11) DEFAULT 1,
  `experience_required` varchar(100) DEFAULT NULL,
  `qualification_required` text DEFAULT NULL,
  `skills_required` text DEFAULT NULL,
  `salary_range_min` decimal(12,2) DEFAULT NULL,
  `salary_range_max` decimal(12,2) DEFAULT NULL,
  `job_description` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `job_type` enum('full_time','part_time','contract','internship') DEFAULT NULL,
  `work_mode` enum('remote','onsite','hybrid') DEFAULT NULL,
  `posted_date` date DEFAULT NULL,
  `last_date` date DEFAULT NULL,
  `status` enum('open','closed','on_hold') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_code` (`job_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_job_openings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_job_openings` WRITE;
/*!40000 ALTER TABLE `_archived_job_openings` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_job_openings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_journal_entry_lines`
--

DROP TABLE IF EXISTS `_archived_journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_journal_entry_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `cost_center_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_entry_id` (`journal_entry_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `_archived_journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`),
  CONSTRAINT `_archived_journal_entry_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_journal_entry_lines`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `_archived_journal_entry_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_kyc_records`
--

DROP TABLE IF EXISTS `_archived_kyc_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_kyc_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `submitted_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_kyc_records`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_kyc_records` WRITE;
/*!40000 ALTER TABLE `_archived_kyc_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_kyc_records` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_lead_documents`
--

DROP TABLE IF EXISTS `_archived_lead_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_lead_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_partner` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_lead_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_lead_documents` WRITE;
/*!40000 ALTER TABLE `_archived_lead_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_lead_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_lead_followups`
--

DROP TABLE IF EXISTS `_archived_lead_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_lead_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `followup_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_date` (`followup_date`),
  CONSTRAINT `_archived_lead_followups_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_lead_followups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_lead_followups` WRITE;
/*!40000 ALTER TABLE `_archived_lead_followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_lead_followups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_lead_score_history`
--

DROP TABLE IF EXISTS `_archived_lead_score_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_lead_score_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `old_score` int(11) DEFAULT NULL,
  `new_score` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_lead_score_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_lead_score_history` WRITE;
/*!40000 ALTER TABLE `_archived_lead_score_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_lead_score_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_lead_scores`
--

DROP TABLE IF EXISTS `_archived_lead_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_lead_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `score_percentage` int(11) DEFAULT 0,
  `priority` enum('low','medium','high','urgent') DEFAULT 'low',
  `factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`factors`)),
  `last_calculated` datetime DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_id` (`lead_id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_score` (`score`),
  KEY `idx_priority` (`priority`),
  CONSTRAINT `_archived_lead_scores_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `partner_leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `_archived_lead_scores_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_lead_scores`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_lead_scores` WRITE;
/*!40000 ALTER TABLE `_archived_lead_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_lead_scores` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_lead_scoring_history`
--

DROP TABLE IF EXISTS `_archived_lead_scoring_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_lead_scoring_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `old_score` int(11) DEFAULT NULL,
  `new_score` int(11) DEFAULT NULL,
  `old_priority` varchar(20) DEFAULT NULL,
  `new_priority` varchar(20) DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lead_id` (`lead_id`),
  KEY `idx_partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_lead_scoring_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_lead_scoring_history` WRITE;
/*!40000 ALTER TABLE `_archived_lead_scoring_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_lead_scoring_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_loan_applications`
--

DROP TABLE IF EXISTS `_archived_loan_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_loan_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `loan_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `tenure` int(11) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','approved','rejected') DEFAULT 'pending',
  `sanctioned_amount` decimal(12,2) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `_archived_loan_applications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_loan_applications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_loan_applications` WRITE;
/*!40000 ALTER TABLE `_archived_loan_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_loan_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_loan_commission`
--

DROP TABLE IF EXISTS `_archived_loan_commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_loan_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) DEFAULT NULL,
  `commission` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_loan` (`loan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_loan_commission`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_loan_commission` WRITE;
/*!40000 ALTER TABLE `_archived_loan_commission` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_loan_commission` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_loans`
--

DROP TABLE IF EXISTS `_archived_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_type` enum('personal','business','vehicle','property') DEFAULT NULL,
  `lender_name` varchar(200) DEFAULT NULL,
  `loan_amount` decimal(15,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `loan_start_date` date DEFAULT NULL,
  `loan_end_date` date DEFAULT NULL,
  `emi_amount` decimal(15,2) DEFAULT NULL,
  `total_paid` decimal(15,2) DEFAULT 0.00,
  `balance_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('active','closed','defaulted') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_loans`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_loans` WRITE;
/*!40000 ALTER TABLE `_archived_loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_loans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_login_history`
--

DROP TABLE IF EXISTS `_archived_login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_login_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `failure_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_login_time` (`login_time`),
  CONSTRAINT `_archived_login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_login_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_login_history` WRITE;
/*!40000 ALTER TABLE `_archived_login_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_login_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_loyalty_points`
--

DROP TABLE IF EXISTS `_archived_loyalty_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_loyalty_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) DEFAULT 0,
  `total_earned` int(11) DEFAULT 0,
  `total_redeemed` int(11) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `_archived_loyalty_points_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_loyalty_points`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_loyalty_points` WRITE;
/*!40000 ALTER TABLE `_archived_loyalty_points` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_loyalty_points` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_loyalty_transactions`
--

DROP TABLE IF EXISTS `_archived_loyalty_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_loyalty_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `type` enum('earned','redeemed','expired') DEFAULT 'earned',
  `description` varchar(255) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_loyalty_transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_loyalty_transactions` WRITE;
/*!40000 ALTER TABLE `_archived_loyalty_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_loyalty_transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_magic_links`
--

DROP TABLE IF EXISTS `_archived_magic_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_magic_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `_archived_magic_links_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_magic_links`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_magic_links` WRITE;
/*!40000 ALTER TABLE `_archived_magic_links` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_magic_links` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_mobile_tokens`
--

DROP TABLE IF EXISTS `_archived_mobile_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_mobile_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_mobile_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_mobile_tokens` WRITE;
/*!40000 ALTER TABLE `_archived_mobile_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_mobile_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_ombudsman_cases`
--

DROP TABLE IF EXISTS `_archived_ombudsman_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_ombudsman_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `case_id` varchar(50) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `filed_date` date DEFAULT NULL,
  `hearing_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_ombudsman_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_ombudsman_cases` WRITE;
/*!40000 ALTER TABLE `_archived_ombudsman_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_ombudsman_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_operation_cases`
--

DROP TABLE IF EXISTS `_archived_operation_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_operation_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_no` varchar(50) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','closed') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `sla_due` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_assigned` (`assigned_to`),
  KEY `idx_status` (`status`),
  CONSTRAINT `_archived_operation_cases_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_operation_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_operation_cases` WRITE;
/*!40000 ALTER TABLE `_archived_operation_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_operation_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_operation_tasks`
--

DROP TABLE IF EXISTS `_archived_operation_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_operation_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('todo','in_progress','completed') DEFAULT 'todo',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assigned` (`assigned_to`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_operation_tasks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_operation_tasks` WRITE;
/*!40000 ALTER TABLE `_archived_operation_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_operation_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_otp_verification`
--

DROP TABLE IF EXISTS `_archived_otp_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_otp_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `type` enum('login','verification','reset') DEFAULT 'login',
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_otp_code` (`otp_code`),
  CONSTRAINT `_archived_otp_verification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_otp_verification`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_otp_verification` WRITE;
/*!40000 ALTER TABLE `_archived_otp_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_otp_verification` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_applications`
--

DROP TABLE IF EXISTS `_archived_partner_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `experience` varchar(20) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `status` enum('new','reviewed','approved','rejected') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_applications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_applications` WRITE;
/*!40000 ALTER TABLE `_archived_partner_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_commissions`
--

DROP TABLE IF EXISTS `_archived_partner_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_email` varchar(100) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `connector_id` int(11) DEFAULT NULL,
  `client_name` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `connector_id` (`connector_id`),
  KEY `lead_id` (`lead_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_commissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_commissions` WRITE;
/*!40000 ALTER TABLE `_archived_partner_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_commissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_connectors`
--

DROP TABLE IF EXISTS `_archived_partner_connectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_connectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_connectors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_connectors` WRITE;
/*!40000 ALTER TABLE `_archived_partner_connectors` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_connectors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_documents`
--

DROP TABLE IF EXISTS `_archived_partner_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('active','deleted') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_partner` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_documents` WRITE;
/*!40000 ALTER TABLE `_archived_partner_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_followups`
--

DROP TABLE IF EXISTS `_archived_partner_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `follow_up_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_followup_date` (`follow_up_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_followups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_followups` WRITE;
/*!40000 ALTER TABLE `_archived_partner_followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_followups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_notifications`
--

DROP TABLE IF EXISTS `_archived_partner_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `_archived_partner_notifications_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_notifications` WRITE;
/*!40000 ALTER TABLE `_archived_partner_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_payouts`
--

DROP TABLE IF EXISTS `_archived_partner_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','processing','paid','rejected') DEFAULT 'pending',
  `request_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `_archived_partner_payouts_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_payouts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_payouts` WRITE;
/*!40000 ALTER TABLE `_archived_partner_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_payouts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_settings`
--

DROP TABLE IF EXISTS `_archived_partner_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `weekly_report` tinyint(1) DEFAULT 1,
  `monthly_report` tinyint(1) DEFAULT 1,
  `payout_alerts` tinyint(1) DEFAULT 1,
  `lead_assigned_alerts` tinyint(1) DEFAULT 1,
  `notification_sound` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_settings` WRITE;
/*!40000 ALTER TABLE `_archived_partner_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_ticket_replies`
--

DROP TABLE IF EXISTS `_archived_partner_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('partner','admin') DEFAULT 'partner',
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `_archived_partner_ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `_archived_partner_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_ticket_replies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_ticket_replies` WRITE;
/*!40000 ALTER TABLE `_archived_partner_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_partner_tickets`
--

DROP TABLE IF EXISTS `_archived_partner_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_partner_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `ticket_no` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_no` (`ticket_no`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `_archived_partner_tickets_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_partner_tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_partner_tickets` WRITE;
/*!40000 ALTER TABLE `_archived_partner_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_partner_tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_payment_orders`
--

DROP TABLE IF EXISTS `_archived_payment_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_payment_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'INR',
  `status` enum('created','pending','paid','failed') DEFAULT 'created',
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_payment_id` varchar(100) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_payment_orders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_payment_orders` WRITE;
/*!40000 ALTER TABLE `_archived_payment_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_payment_orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_payment_transactions`
--

DROP TABLE IF EXISTS `_archived_payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_order_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payment_order_id` (`payment_order_id`),
  CONSTRAINT `_archived_payment_transactions_ibfk_1` FOREIGN KEY (`payment_order_id`) REFERENCES `_archived_payment_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_payment_transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_payment_transactions` WRITE;
/*!40000 ALTER TABLE `_archived_payment_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_payout_queue`
--

DROP TABLE IF EXISTS `_archived_payout_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_payout_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `retry_count` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_partner` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_payout_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_payout_queue` WRITE;
/*!40000 ALTER TABLE `_archived_payout_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_payout_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_payout_requests`
--

DROP TABLE IF EXISTS `_archived_payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_payout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` enum('partner','employee') DEFAULT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','processed','failed') DEFAULT 'pending',
  `request_date` date DEFAULT NULL,
  `processed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient_type`,`recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_payout_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_payout_requests` WRITE;
/*!40000 ALTER TABLE `_archived_payout_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_payout_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_performance_reviews`
--

DROP TABLE IF EXISTS `_archived_performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_period_start` date DEFAULT NULL,
  `review_period_end` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `quality_of_work` int(11) DEFAULT NULL,
  `productivity` int(11) DEFAULT NULL,
  `teamwork` int(11) DEFAULT NULL,
  `communication` int(11) DEFAULT NULL,
  `attendance` int(11) DEFAULT NULL,
  `initiative` int(11) DEFAULT NULL,
  `problem_solving` int(11) DEFAULT NULL,
  `leadership` int(11) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_of_improvement` text DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `goals_achieved` text DEFAULT NULL,
  `next_quarter_goals` text DEFAULT NULL,
  `training_required` text DEFAULT NULL,
  `overall_rating` decimal(3,1) DEFAULT NULL,
  `recommendation` enum('promote','retain','need_improvement','terminate') DEFAULT NULL,
  `status` enum('draft','submitted','acknowledged','completed') DEFAULT 'draft',
  `employee_comments` text DEFAULT NULL,
  `employee_acknowledged_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `reviewer_id` (`reviewer_id`),
  CONSTRAINT `_archived_performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `_archived_performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_performance_reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_performance_reviews` WRITE;
/*!40000 ALTER TABLE `_archived_performance_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_performance_reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_privacy_consent`
--

DROP TABLE IF EXISTS `_archived_privacy_consent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_privacy_consent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `consent_given` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_privacy_consent`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_privacy_consent` WRITE;
/*!40000 ALTER TABLE `_archived_privacy_consent` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_privacy_consent` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_product_categories`
--

DROP TABLE IF EXISTS `_archived_product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) DEFAULT NULL,
  `category_name` varchar(100) NOT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_product_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_product_categories` WRITE;
/*!40000 ALTER TABLE `_archived_product_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_product_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_products`
--

DROP TABLE IF EXISTS `_archived_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `buying_price` decimal(12,2) DEFAULT NULL,
  `selling_price` decimal(12,2) DEFAULT NULL,
  `mrp` decimal(12,2) DEFAULT NULL,
  `gst_rate` decimal(5,2) DEFAULT NULL,
  `current_stock` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 0,
  `max_stock_level` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_products`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_products` WRITE;
/*!40000 ALTER TABLE `_archived_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_purchase_order_items`
--

DROP TABLE IF EXISTS `_archived_purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `_archived_purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `_archived_purchase_orders` (`id`),
  CONSTRAINT `_archived_purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `_archived_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_purchase_order_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_purchase_order_items` WRITE;
/*!40000 ALTER TABLE `_archived_purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_purchase_orders`
--

DROP TABLE IF EXISTS `_archived_purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) DEFAULT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `actual_delivery` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT NULL,
  `discount` decimal(15,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('draft','sent','confirmed','delivered','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `_archived_purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_purchase_orders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_purchase_orders` WRITE;
/*!40000 ALTER TABLE `_archived_purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_push_notifications_queue`
--

DROP TABLE IF EXISTS `_archived_push_notifications_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_push_notifications_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_push_notifications_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_push_notifications_queue` WRITE;
/*!40000 ALTER TABLE `_archived_push_notifications_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_push_notifications_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_push_subscriptions`
--

DROP TABLE IF EXISTS `_archived_push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_push_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `p256dh` varchar(200) NOT NULL,
  `auth` varchar(200) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_endpoint` (`endpoint`(255)),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `_archived_push_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_push_subscriptions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_push_subscriptions` WRITE;
/*!40000 ALTER TABLE `_archived_push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_quotations`
--

DROP TABLE IF EXISTS `_archived_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(50) DEFAULT NULL,
  `customer` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_no` (`quote_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_quotations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_quotations` WRITE;
/*!40000 ALTER TABLE `_archived_quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_quotations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_rate_limits`
--

DROP TABLE IF EXISTS `_archived_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT 'analyze',
  `request_count` int(11) DEFAULT 1,
  `first_request` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_user_action` (`user_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_rate_limits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_rate_limits` WRITE;
/*!40000 ALTER TABLE `_archived_rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_rate_limits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_rbi_complaints`
--

DROP TABLE IF EXISTS `_archived_rbi_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_rbi_complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `complaint_id` varchar(50) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `filed_date` date DEFAULT NULL,
  `rbi_reference` varchar(50) DEFAULT NULL,
  `status` enum('pending','resolved','closed') DEFAULT 'pending',
  `resolution_notes` text DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_rbi_complaints`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_rbi_complaints` WRITE;
/*!40000 ALTER TABLE `_archived_rbi_complaints` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_rbi_complaints` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_reconciliation`
--

DROP TABLE IF EXISTS `_archived_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `bank_amount` decimal(12,2) DEFAULT NULL,
  `system_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','reconciled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_reconciliation`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_reconciliation` WRITE;
/*!40000 ALTER TABLE `_archived_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_referrals`
--

DROP TABLE IF EXISTS `_archived_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referrer_id` int(11) NOT NULL,
  `referred_email` varchar(255) DEFAULT NULL,
  `referred_name` varchar(100) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `status` enum('pending','registered','converted','paid') DEFAULT 'pending',
  `commission_earned` decimal(10,2) DEFAULT 0.00,
  `converted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_referrer` (`referrer_id`),
  KEY `idx_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_referrals`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_referrals` WRITE;
/*!40000 ALTER TABLE `_archived_referrals` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_referrals` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_refund_consent`
--

DROP TABLE IF EXISTS `_archived_refund_consent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_refund_consent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `consent_given` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_refund_consent`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_refund_consent` WRITE;
/*!40000 ALTER TABLE `_archived_refund_consent` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_refund_consent` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_registration_codes`
--

DROP TABLE IF EXISTS `_archived_registration_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_registration_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `role` enum('admin','partner','client') NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_for_role` enum('partner','client') NOT NULL,
  `assigned_to_email` varchar(100) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_by_user_id` int(11) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_registration_codes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_registration_codes` WRITE;
/*!40000 ALTER TABLE `_archived_registration_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_registration_codes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_report_filters`
--

DROP TABLE IF EXISTS `_archived_report_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_report_filters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `filter_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`filter_config`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  CONSTRAINT `_archived_report_filters_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_report_filters`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_report_filters` WRITE;
/*!40000 ALTER TABLE `_archived_report_filters` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_report_filters` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_transactions`
--

DROP TABLE IF EXISTS `_archived_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(200) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_transactions` WRITE;
/*!40000 ALTER TABLE `_archived_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `_archived_unified_reviews`
--

DROP TABLE IF EXISTS `_archived_unified_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `_archived_unified_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `review_source` enum('website','client_portal','admin') DEFAULT 'website',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `responded_at` datetime DEFAULT NULL,
  `response_text` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_rating` (`rating`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `_archived_unified_reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `_archived_unified_reviews` WRITE;
/*!40000 ALTER TABLE `_archived_unified_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `_archived_unified_reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES
(1,1,'wallet_add','Added ₹5,000.00 to wallet. Method: UPI','2026-07-28 07:40:12'),
(2,1,'wallet_add','Added ₹10,000.00 to wallet. Method: NEFT','2026-07-28 07:41:57'),
(3,1,'wallet_add','Added ₹5,000.00 to wallet. Method: UPI','2026-07-28 08:13:48'),
(4,16,'change_password','Successfully changed account password','2026-07-31 01:28:25');
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  KEY `idx_action` (`action`),
  KEY `idx_user_name` (`user_name`),
  KEY `idx_details` (`details`(100)),
  KEY `idx_composite` (`action`(50),`user_name`(50),`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES
(16,1,'Admin','Activity logs cleared','Cleared activity logs - Mode: all, Deleted: 15 logs, Archived: 15 logs','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 23:07:30'),
(17,NULL,'Admin','Created customer','Customer ID: 12, Name: Test Customer 1785022455090, Email: test1785022455090@customer.com','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-25 23:34:15'),
(18,1,'Admin','Converted lead to sale','Lead ID: 1, Customer: Ankit Sharma, Sale Amount: ₹15000, Sale ID: 10','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-25 23:38:33'),
(19,1,'Admin','Converted quotation to sale','Quote: QUO001, Customer: , Sale ID: 11, Amount: ₹15000','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-25 23:40:29'),
(20,1,'Admin','Customer created','Created customer: Test Customer 1785023951797 (test1785023951797@customer.com)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-25 23:59:12'),
(21,1,'Admin','Customer created','Created customer: New Customer 1785023984551 (new1785023984551@customer.com)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-25 23:59:45'),
(22,1,'Admin','Customer created','Created customer: Test Customer 1785024272753 (test1785024272753@customer.com)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:04:33'),
(23,1,'Admin','Deleted registration code','Deleted registration code: PRTN-A8X2K9 (ID: 1)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:06:01'),
(24,1,'Admin','Deleted success story','Deleted success story: Maneet Singh (ID: 1)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:07:06'),
(25,1,'Admin','Deleted backup','Deleted backup file: backup_2026-07-25_22-50-53.sql','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:13:46'),
(26,1,'Admin','Deleted entity','Deleted Other: HDFC Bank (ID: 1)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:15:08'),
(27,1,'Admin','Deleted document','Deleted document: aadhar_priya.pdf (Type: aadhar) for email: priya@example.com','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:24:43'),
(28,1,'Admin','Deleted expense','Deleted expense: Marketing (ID: 5, Amount: ₹15000.00)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:27:24'),
(29,1,'Admin','Deleted lead','Deleted lead: Ankit Sharma (ID: 1, Status: converted)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:29:21'),
(30,1,'Admin','Deleted partner','Deleted partner: Delhi Credit Solutions (ID: 1)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:30:35'),
(31,1,'Admin','Deleted quotation','Deleted quotation: QUO001 (ID: 1, Customer: , Amount: ₹15000.00)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 00:50:02'),
(32,1,'Admin','Deleted customer request','Deleted customer request: Suresh Singh (ID: 1, Service: Written Off)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 01:12:52'),
(33,1,'Admin','Deleted sale','Deleted sale: Rajesh Kumar (ID: 1, Amount: ₹15000.00)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 01:16:26'),
(34,1,'Admin','Deleted user','Deleted user: New Client 1785018634186 (ID: 5, Role: client)','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-26 01:17:56'),
(35,1,'Admin','Deleted poster','Deleted poster: test_poster.jpg (ID: 5)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:36:35'),
(36,1,'Admin','Permanently deleted poster','Permanently deleted poster: test_poster.jpg (ID: 5)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:37:07'),
(37,1,'Admin','Deleted poster','Deleted poster: banner1.jpg (ID: 2)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:37:37'),
(38,1,'Admin','Deleted poster','Deleted poster: banner2.jpg (ID: 3)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:38:01'),
(39,1,'Admin','Permanently deleted poster','Permanently deleted poster: banner2.jpg (ID: 3)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:38:26'),
(40,1,'Admin','Deleted poster','Deleted poster: logo.png (ID: 4)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:39:13'),
(41,1,'Admin','Uploaded posters','Uploaded 1 poster(s)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:40:05'),
(42,1,'Admin','Deleted poster','Deleted poster: ChatGPT Image Jul 18, 2026, 05_10_04 PM.png (ID: 6)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:41:08'),
(43,1,'Admin','Uploaded posters','Uploaded 1 poster(s)','2409:40e5:11a2:9070:e4f7:1889:e221:1cb2',NULL,'2026-07-26 11:41:19'),
(44,NULL,'Admin','Code Generated','PRTN-4AI8IS for partner','2409:40e5:1120:655d:4cfb:8b04:8dc0:7189',NULL,'2026-07-28 06:53:00'),
(45,NULL,'Admin','Code Generated','PRTN-KISOLF for partner','2409:40e5:1120:655d:4cfb:8b04:8dc0:7189',NULL,'2026-07-28 06:53:05'),
(46,1,'Admin','Created lead','Lead ID: 15, Name: Body CSRF Test 1785319185421','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 09:59:45'),
(47,1,'Admin','Created lead','Lead ID: 16, Name: Test Lead 1785319367082','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 10:02:46'),
(48,1,'Admin','Updated lead','Lead ID: 16','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 10:02:46'),
(49,1,'Admin','Deleted lead','Lead ID: 16, Name: Test Lead 1785319367082','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 10:02:47'),
(50,1,'Admin','Test Action','This is a test activity log entry','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-07-29 10:04:21'),
(51,1,'Admin','Login','User logged in successfully','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 10:18:43'),
(52,1,'Admin','Login','User logged in successfully','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 10:20:02'),
(53,NULL,'System','Created customer from payment','Customer ID: 25, Name: Rahul Sharma','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 11:57:19'),
(54,NULL,'Admin','Created Bank','Entity ID: 9, Name: Axis Bank 1785328333235','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:32:13'),
(55,NULL,'Admin','Created Bank','Entity ID: 10, Name: Axis Bank 1785328355697','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:32:35'),
(56,NULL,'Admin','Created Bank','Entity ID: 11, Name: My Bank','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:32:58'),
(57,NULL,'Admin','Created Law Firm / Advocate','Entity ID: 12, Name: My Law Firm 1785328429679','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:33:49'),
(58,NULL,'Admin','Created Bank','Entity ID: 13, Name: My Bank 1785328452514','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:34:12'),
(59,NULL,'Admin','Created Bank','Entity ID: 14, Name: My Entity 1785328470849','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:34:30'),
(60,NULL,'Admin','Created Bank','Entity ID: 15, Name: My Entity 1785328489554','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:34:49'),
(61,NULL,'Admin','Created Bank','Entity ID: 16, Name: Entity 1785328505115','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:35:04'),
(62,NULL,'Admin','Created customer','Customer ID: 26, Name: Test Customer 1785329021042','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:43:40'),
(63,NULL,'Admin','Created customer','Customer ID: 27, Name: New Customer 1785329044384','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:44:04'),
(64,NULL,'Admin','Updated customer','Customer ID: 26, Name: Updated Customer Name 1785329104063','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:45:03'),
(65,NULL,'Admin','Created customer','Customer ID: 28, Name: Customer 1785329120302','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:45:20'),
(66,NULL,'Admin','Updated customer','Customer ID: 26, Name: Updated 1785329140801','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:45:40'),
(67,NULL,'Admin','Updated customer','Customer ID: 26, Name: Updated 1785329231152','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:47:11'),
(68,NULL,'Admin','Created customer','Customer ID: 29, Name: Customer 1785329231151','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:47:11'),
(69,NULL,'Admin','Created customer','Customer ID: 30, Name: Test 1785329253704','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:47:33'),
(70,NULL,'Admin','Created Bank','Entity ID: 17, Name: Entity 1785329253704','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:47:33'),
(71,NULL,'Admin','Updated customer','Customer ID: 30, Name: Updated Customer 1785329324126','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:48:44'),
(72,NULL,'Admin','Created customer','Customer ID: 31, Name: Customer 1785329324125','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:48:44'),
(73,NULL,'Admin','Created customer','Customer ID: 32, Name: Test 1785329343954','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:49:03'),
(74,NULL,'Admin','Created Bank','Entity ID: 18, Name: Entity 1785329343954','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:49:04'),
(75,NULL,'Admin','Created lead','Lead ID: 17, Name: Test Lead 1785329467337, Priority: high','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:51:07'),
(76,NULL,'Admin','Created lead','Lead ID: 18, Name: New Lead 1785329492364, Priority: medium','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:51:32'),
(77,NULL,'Admin','Created customer','Customer ID: 33, Name: Test 1785329523765','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:52:03'),
(78,NULL,'Admin','Created Bank','Entity ID: 19, Name: Entity 1785329523765','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:52:03'),
(79,NULL,'Admin','Created lead','Lead ID: 19, Name: Lead 1785329523765, Priority: medium','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:52:03'),
(80,NULL,'Admin','Created customer','Customer ID: 34, Name: Customer 1785329558760','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:52:38'),
(81,NULL,'Admin','Created partner','Partner ID: 23, Name: Test Partner 1785329659592','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:54:19'),
(82,NULL,'Admin','Created partner','Partner ID: 24, Name: Partner 1785329682633','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:54:42'),
(83,NULL,'Admin','Created customer from payment','Customer ID: 35, Name: Rajesh Kumar','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 12:55:54'),
(84,NULL,'Admin','Created partner','Partner ID: 25, Name: Test Partner 1785330466935','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 13:07:47'),
(85,NULL,'Admin','Created partner','Partner ID: 26, Name: Amit Singh 1785335437560','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:30:38'),
(86,NULL,'Admin','Created partner','Partner ID: 27, Name: New Partner 1785335461826','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:31:02'),
(87,NULL,'Admin','Created partner','Partner ID: 28, Name: Partner 1785335484647','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:31:25'),
(88,NULL,'Admin','Updated user','User ID: 1, Name: Updated Admin Name','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:40:06'),
(89,NULL,'Admin','Updated user','User ID: 2, Name: Amit Singh, Status changed from active to inactive','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:40:31'),
(90,NULL,'Admin','Updated user','User ID: 1, Name: New Name','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:40:31'),
(91,NULL,'Admin','Updated user','User ID: 3, Name: Rajesh Kumar, Role changed from client to partner','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:40:31'),
(92,NULL,'Admin','Created Bank','Entity ID: 20, Name: Test Bank 1785336190742','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:43:11'),
(93,NULL,'Admin','Updated Bank','Entity ID: 20, Name: Updated Bank 1785336207839','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:43:28'),
(94,NULL,'Admin','Created Bank','Entity ID: 21, Name: New Entity 1785336229357','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:43:49'),
(95,NULL,'Admin','Created customer','Customer ID: 36, Name: Test Customer 1785337041289','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:57:21'),
(96,NULL,'Admin','Updated customer','Customer ID: 36, Name: Updated Customer 1785337041566','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:57:21'),
(97,NULL,'Admin','Created customer','Customer ID: 37, Name: New Customer 1785337082357','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:58:02'),
(98,NULL,'Admin','Updated customer','Customer ID: 36, Name: Final Updated Name 1785337107671','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:58:28'),
(99,NULL,'Admin','Created customer','Customer ID: 38, Name: Customer 1785337126780','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:58:47'),
(100,NULL,'Admin','Updated expense','Expense ID: 1, Category: Marketing','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 14:59:44'),
(101,NULL,'Admin','Created customer','Customer ID: 39, Name: Test Lead 1785329467337','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 15:02:30'),
(102,NULL,'Admin','Updated partner','Partner ID: 28, Name: Updated Partner 1785337454770','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 15:04:15'),
(103,NULL,'Admin','Updated partner','Partner ID: 28, Name: Updated Partner 1785339403514','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 15:36:43'),
(104,NULL,'Admin','Updated customer request','Request ID: 5, Name: Updated Request Name, Status changed from pending to in_progress','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 15:39:44'),
(105,NULL,'Admin','Updated user','User ID: 1, Name: Updated Admin 1785340564688','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 15:56:04'),
(106,NULL,'Admin','Updated user','User ID: 1, Name: Final Admin Name 1785340617600','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 15:56:57'),
(107,NULL,'Admin','Created customer','Customer ID: 40, Name: Test Customer 1785342317620','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:25:17'),
(108,NULL,'Admin','Created lead','Lead ID: 20, Name: Test Lead 1785342318066, Priority: medium','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:25:18'),
(109,NULL,'Admin','Created partner','Partner ID: 29, Name: Test Partner 1785342318192','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:25:18'),
(110,NULL,'Admin','Created customer','Customer ID: 41, Name: Test Customer 1785342380066','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:26:20'),
(111,NULL,'Admin','Created lead','Lead ID: 21, Name: Test Lead 1785342380358, Priority: medium','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:26:20'),
(112,NULL,'Admin','Created partner','Partner ID: 30, Name: Test Partner 1785342380492','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:26:20'),
(113,NULL,'Admin','Created customer','Customer ID: 42, Name: Test Customer 1785342650443','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:30:50'),
(114,NULL,'Admin','Created lead','Lead ID: 22, Name: Test Lead 1785342650884, Priority: medium','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:30:50'),
(115,NULL,'Admin','Created partner','Partner ID: 31, Name: Test Partner 1785342651017','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:30:51'),
(116,NULL,'Admin','Created customer','Customer ID: 43, Name: Console Test 1785342877468','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:34:37'),
(117,NULL,'Admin','Created lead','Lead ID: 23, Name: Console Lead 1785342877778, Priority: high','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:34:37'),
(118,NULL,'Admin','Created partner','Partner ID: 32, Name: Console Partner 1785342877906','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:34:38'),
(119,NULL,'Admin','Created lead','Lead ID: 24, Name: Check Lead, Priority: medium','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',NULL,'2026-07-29 16:37:59'),
(120,1,'Admin','Lead Deleted','Check Lead','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-07-29 16:54:14'),
(121,8,'Your Name','Partner Added','kundan','2409:40e5:1164:c69d:e19f:8e14:2054:6fd4','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-07-30 10:24:47'),
(122,8,'Your Name','Partner Added','Laxmi','2409:40e5:1054:e1d0:240c:46aa:187:d97a','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-07-30 23:15:51'),
(123,8,'Your Name','Partner Added','Kundan','2409:40e5:1010:b489:39e4:af4:694a:d0d7','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-07-31 08:29:33');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `activity_logs_archive`
--

DROP TABLE IF EXISTS `activity_logs_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT current_timestamp(),
  `archived_by` int(11) DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_original_id` (`original_id`),
  KEY `idx_archived_at` (`archived_at`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_archived_by` (`archived_by`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs_archive`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `activity_logs_archive` WRITE;
/*!40000 ALTER TABLE `activity_logs_archive` DISABLE KEYS */;
INSERT INTO `activity_logs_archive` VALUES
(1,1,1,'System','Database Initialized','CRM database created and seeded','127.0.0.1',NULL,'2026-07-19 07:51:56','2026-07-25 23:07:30',1,'0'),
(2,2,1,'Admin','First Login','Admin dashboard accessed','192.168.1.1',NULL,'2026-07-19 07:51:56','2026-07-25 23:07:30',1,'0'),
(3,3,NULL,'Admin','partner_create','Created partner: TestPartner (ID: 4)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 16:28:01','2026-07-25 23:07:30',1,'0'),
(4,4,NULL,'Admin','partner_create','Created partner: Console Test Partner (ID: 5)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 16:58:18','2026-07-25 23:07:30',1,'0'),
(5,5,NULL,'Admin','partner_create','Created partner: Test Partner (ID: 13)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:20','2026-07-25 23:07:30',1,'0'),
(6,6,NULL,'Admin','partner_create','Created partner: Delhi Finance (ID: 14)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:51','2026-07-25 23:07:30',1,'0'),
(7,7,NULL,'Admin','partner_create','Created partner: Bangalore Solutions (ID: 15)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:51','2026-07-25 23:07:30',1,'0'),
(8,8,NULL,'Admin','partner_create','Created partner: Mumbai Credit (ID: 16)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:51','2026-07-25 23:07:30',1,'0'),
(9,9,NULL,'Admin','Created customer','Customer ID: 9, Name: Simple Customer 1785013101654, Email: simple1785013101654@customer.com','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 20:58:21','2026-07-25 23:07:30',1,'0'),
(10,10,NULL,'Admin','Created customer','Customer ID: 10, Name: Full Customer 1785013156664, Email: full1785013156664@customer.com','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 20:59:17','2026-07-25 23:07:30',1,'0'),
(11,11,NULL,'Admin','Created customer','Customer ID: 11, Name: Test Customer 1785013354835, Email: test1785013354835@customer.com','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:02:35','2026-07-25 23:07:30',1,'0'),
(12,12,NULL,'Admin','Test Log','This is a test log entry','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:13:23','2026-07-25 23:07:30',1,'0'),
(13,13,NULL,'Admin','Test Log','This is a test log entry','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:16:03','2026-07-25 23:07:30',1,'0'),
(14,14,NULL,'Admin','Lead Added','Test Lead 1785015508586 (medium priority)','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:38:28','2026-07-25 23:07:30',1,'0'),
(15,15,NULL,'Admin','Lead Added','Console Test 1785016149614 (medium priority)','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:49:09','2026-07-25 23:07:30',1,'0');
/*!40000 ALTER TABLE `activity_logs_archive` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','partner','client') DEFAULT 'client',
  `mobile` varchar(15) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remember_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES
(1,'Super Admin','admin@cibilrepair.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','admin',NULL,1,'2026-05-29 21:32:03','2409:40e5:1172:185:7c7e:4d8f:d31a:1fd8',0,'2026-05-29 14:37:51','2026-05-29 21:32:03','$2y$10$Z1Kth.91e4e41H4oumQ9jOeAABAeoSrGN2cpCoM3JWmxSKeXmuFIG','2026-06-28 21:32:03'),
(2,'Kundan Khatri','kundankhatri@gmail.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','admin',NULL,1,NULL,NULL,0,'2026-05-29 14:37:51','2026-05-29 15:15:41',NULL,NULL),
(3,'Demo Partner','partner@cibilrepair.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','partner',NULL,1,NULL,NULL,1,'2026-05-29 14:37:51','2026-05-29 18:23:54',NULL,NULL),
(4,'Demo Client','client@cibilrepair.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','client',NULL,1,NULL,NULL,0,'2026-05-29 14:37:51','2026-05-29 15:15:41',NULL,NULL);
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_predictions`
--

DROP TABLE IF EXISTS `ai_predictions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_predictions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prediction_type` varchar(100) DEFAULT NULL,
  `predicted_value` decimal(12,2) DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `prediction_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_predictions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_predictions` WRITE;
/*!40000 ALTER TABLE `ai_predictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_predictions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `analyses`
--

DROP TABLE IF EXISTS `analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analyses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(30) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `result` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_analysis_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analyses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `analyses` WRITE;
/*!40000 ALTER TABLE `analyses` DISABLE KEYS */;
/*!40000 ALTER TABLE `analyses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('general','holiday','policy','event','urgent') NOT NULL DEFAULT 'general',
  `target_audience` varchar(100) DEFAULT 'all',
  `priority` int(1) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `api_logs`
--

DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_data`)),
  `response_status` int(11) DEFAULT NULL,
  `response_time` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_api_user` (`user_id`),
  KEY `idx_api_created` (`created_at`),
  CONSTRAINT `api_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `api_logs` WRITE;
/*!40000 ALTER TABLE `api_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `check_in_status` enum('on_time','late','early') DEFAULT NULL,
  `status` varchar(20) DEFAULT 'present',
  `attendance_note` text DEFAULT NULL,
  `working_hours` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `late_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`employee_id`,`attendance_date`),
  CONSTRAINT `fk_attendance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES
(1,1,'2026-08-02','09:30:00','18:30:00',NULL,'present',NULL,9.00,NULL,NULL,'2026-08-02 09:29:09');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES
(2,NULL,'INSERT','payments',2,NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(3,NULL,'INSERT','payments',3,NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"1999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(4,NULL,'INSERT','payments',4,NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"9999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
(5,NULL,'INSERT','payments',5,NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(6,NULL,'INSERT','payments',6,NULL,'{\"client\":\"Amit Patel\",\"amount\":\"2999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(7,NULL,'INSERT','payments',7,NULL,'{\"client\":\"Neha Gupta\",\"amount\":\"1999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
(8,NULL,'INSERT','payments',8,NULL,'{\"client\":\"Vikram Singh\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(9,NULL,'INSERT','payments',9,NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"25000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
(10,NULL,'INSERT','payments',10,NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"15000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
(11,NULL,'INSERT','payments',11,NULL,'{\"client\":\"Amit Patel\",\"amount\":\"50000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-05 15:55:39'),
(12,NULL,'INSERT','payments',12,NULL,'{\"client\":\"Neha Gupta\",\"amount\":\"10000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
(13,NULL,'INSERT','payments',13,NULL,'{\"client\":\"Suresh Singh\",\"amount\":\"25000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
(14,NULL,'INSERT','payments',14,NULL,'{\"client\":\"Anita Desai\",\"amount\":\"15000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
(15,NULL,'UPDATE','leads',25,'{\"status\":\"new\"}','{\"status\":\"contacted\"}',NULL,NULL,'2026-06-28 15:42:58'),
(16,NULL,'UPDATE','leads',25,'{\"status\":\"contacted\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:00:32'),
(17,NULL,'UPDATE','leads',23,'{\"status\":\"contacted\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:02:12'),
(18,NULL,'UPDATE','leads',14,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:12:26'),
(19,NULL,'UPDATE','leads',15,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:12:26'),
(20,NULL,'UPDATE','leads',6,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-19 07:54:26'),
(21,NULL,'INSERT','payments',15,NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"25000.00\",\"status\":\"completed\"}',NULL,NULL,'2026-07-25 22:45:29'),
(22,NULL,'UPDATE','leads',1,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-25 23:38:33'),
(23,NULL,'UPDATE','leads',4,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 10:56:31'),
(24,NULL,'UPDATE','leads',5,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 10:57:04'),
(25,NULL,'UPDATE','leads',2,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 10:58:52'),
(26,NULL,'UPDATE','leads',14,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 10:59:14'),
(27,NULL,'UPDATE','leads',13,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 10:59:35'),
(28,NULL,'UPDATE','leads',12,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 10:59:58'),
(29,NULL,'UPDATE','leads',11,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-28 11:00:34'),
(30,NULL,'INSERT','payments',16,NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"999.00\",\"status\":\"completed\"}',NULL,NULL,'2026-07-29 06:10:10'),
(31,NULL,'INSERT','payments',17,NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"1499.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 06:10:10'),
(32,NULL,'INSERT','payments',18,NULL,'{\"client\":\"Amit Singh\",\"amount\":\"499.00\",\"status\":\"success\"}',NULL,NULL,'2026-07-29 06:10:10'),
(33,NULL,'INSERT','payments',19,NULL,'{\"client\":\"Sneha Patel\",\"amount\":\"2500.00\",\"status\":\"paid\"}',NULL,NULL,'2026-07-29 06:10:10'),
(34,NULL,'INSERT','payments',20,NULL,'{\"client\":\"Vikram Singh\",\"amount\":\"7500.00\",\"status\":\"completed\"}',NULL,NULL,'2026-07-29 06:10:10'),
(35,NULL,'UPDATE','leads',16,'{\"status\":\"new\"}','{\"status\":\"contacted\"}',NULL,NULL,'2026-07-29 10:02:46'),
(36,NULL,'INSERT','payments',21,NULL,'{\"client\":\"John Doe\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 11:49:15'),
(37,NULL,'INSERT','payments',22,NULL,'{\"client\":\"Full Test User\",\"amount\":\"35000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 11:49:35'),
(38,NULL,'INSERT','payments',23,NULL,'{\"client\":\"Test\",\"amount\":\"5000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 11:50:47'),
(39,NULL,'INSERT','payments',24,NULL,'{\"client\":\"John Doe\",\"amount\":\"5000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 11:51:36'),
(40,NULL,'INSERT','payments',25,NULL,'{\"client\":\"New Client\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 11:52:06'),
(41,NULL,'INSERT','payments',26,NULL,'{\"client\":\"Quick\",\"amount\":\"999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 11:52:46'),
(42,NULL,'INSERT','payments',27,NULL,'{\"client\":\"Rahul Sharma\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 12:01:57'),
(43,NULL,'INSERT','payments',28,NULL,'{\"client\":\"Test Payment\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 12:47:33'),
(44,NULL,'INSERT','payments',29,NULL,'{\"client\":\"Payment 1785329343954\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 12:49:03'),
(45,NULL,'INSERT','payments',30,NULL,'{\"client\":\"Payment 1785329523765\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 12:52:03'),
(46,NULL,'INSERT','payments',31,NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 13:05:22'),
(47,NULL,'INSERT','payments',32,NULL,'{\"client\":\"Customer Name\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 13:05:42'),
(48,NULL,'INSERT','payments',33,NULL,'{\"client\":\"New Customer\",\"amount\":\"20000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 13:06:09'),
(49,NULL,'UPDATE','leads',19,'{\"status\":\"new\"}','{\"status\":\"contacted\"}',NULL,NULL,'2026-07-29 15:01:19'),
(50,NULL,'UPDATE','leads',17,'{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-29 15:02:36'),
(51,NULL,'INSERT','payments',34,NULL,'{\"client\":\"Quotation: Test Customer 1785337734961\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 15:08:55'),
(52,NULL,'INSERT','payments',35,NULL,'{\"client\":\"Quotation: 1785337752441\",\"amount\":\"25000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 15:09:13'),
(53,NULL,'INSERT','payments',36,NULL,'{\"client\":\"Quotation: 1785337768211\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 15:09:28'),
(54,NULL,'INSERT','payments',37,NULL,'{\"client\":\"Quotation: Customer 1785338326792\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 15:18:47'),
(55,NULL,'INSERT','payments',38,NULL,'{\"client\":\"Test Payment 1785342317943\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:25:18'),
(56,NULL,'INSERT','payments',39,NULL,'{\"client\":\"Test Payment 1785342380215\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:26:20'),
(57,NULL,'INSERT','payments',40,NULL,'{\"client\":\"Test Payment 1785342650751\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:30:50'),
(58,NULL,'INSERT','payments',41,NULL,'{\"client\":\"Quotation: Test Customer 1785342818250\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:33:38'),
(59,NULL,'INSERT','payments',42,NULL,'{\"client\":\"Console Payment 1785342877660\",\"amount\":\"5000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:34:37'),
(60,NULL,'INSERT','payments',43,NULL,'{\"client\":\"Quotation: Console Test 1785342878321\",\"amount\":\"15000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:34:38'),
(61,NULL,'INSERT','payments',44,NULL,'{\"client\":\"Check Payment\",\"amount\":\"1000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:37:59'),
(62,NULL,'INSERT','payments',45,NULL,'{\"client\":\"Check Quotation\",\"amount\":\"10000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-07-29 16:37:59');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES
(1,1,'system_initialized','Database setup completed','127.0.0.1',NULL,'2026-06-02 18:07:44'),
(2,0,'INSERT','Table: payments, Record ID: 2, Old: NULL, New: {\"client\":\"Rajesh Kumar\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(3,0,'INSERT','Table: payments, Record ID: 3, Old: NULL, New: {\"client\":\"Rajesh Kumar\",\"amount\":\"1999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(4,0,'INSERT','Table: payments, Record ID: 4, Old: NULL, New: {\"client\":\"Priya Sharma\",\"amount\":\"9999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
(5,0,'INSERT','Table: payments, Record ID: 5, Old: NULL, New: {\"client\":\"Priya Sharma\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(6,0,'INSERT','Table: payments, Record ID: 6, Old: NULL, New: {\"client\":\"Amit Patel\",\"amount\":\"2999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
(7,0,'INSERT','Table: payments, Record ID: 7, Old: NULL, New: {\"client\":\"Neha Gupta\",\"amount\":\"1999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
(8,0,'INSERT','Table: payments, Record ID: 8, Old: NULL, New: {\"client\":\"Vikram Singh\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `audit_trail_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_trail`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `audit_trail` WRITE;
/*!40000 ALTER TABLE `audit_trail` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_trail` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `backup_jobs`
--

DROP TABLE IF EXISTS `backup_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_name` varchar(100) NOT NULL,
  `backup_type` enum('full','incremental','differential') DEFAULT 'full',
  `status` enum('pending','running','completed','failed','cancelled') DEFAULT 'pending',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `schedule` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_backup_status` (`status`),
  KEY `idx_backup_created` (`created_at`),
  CONSTRAINT `backup_jobs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_jobs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `backup_jobs` WRITE;
/*!40000 ALTER TABLE `backup_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `backup_logs`
--

DROP TABLE IF EXISTS `backup_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) DEFAULT 'sql',
  `backup_type` varchar(50) DEFAULT 'full',
  `status` enum('completed','failed','in_progress') DEFAULT 'completed',
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `backup_logs` WRITE;
/*!40000 ALTER TABLE `backup_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bank_details`
--

DROP TABLE IF EXISTS `bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `ifsc_code` varchar(50) DEFAULT NULL,
  `account_holder` varchar(255) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_details`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bank_details` WRITE;
/*!40000 ALTER TABLE `bank_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_details` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bank_reconciliation`
--

DROP TABLE IF EXISTS `bank_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `bank_amount` decimal(12,2) NOT NULL,
  `system_amount` decimal(12,2) DEFAULT 0.00,
  `difference` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','reconciled','exception') DEFAULT 'pending',
  `reconciled_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliation`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bank_reconciliation` WRITE;
/*!40000 ALTER TABLE `bank_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bank_submissions`
--

DROP TABLE IF EXISTS `bank_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `dispute_id` varchar(50) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_bank_customer` (`client_id`),
  CONSTRAINT `fk_bank_customer` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_submissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bank_submissions` WRITE;
/*!40000 ALTER TABLE `bank_submissions` DISABLE KEYS */;
INSERT INTO `bank_submissions` VALUES
(1,3,'HDFC Bank','1234567890','DSP-2024-0002','2026-08-02','pending','2026-08-02 08:29:03'),
(2,5,'SBI Bank','9876543210','DSP-2024-0004','2026-08-02','resolved','2026-08-02 08:29:03'),
(3,7,'ICICI Bank','4567890123','DSP-2024-0006','2026-08-02','pending','2026-08-02 08:29:03');
/*!40000 ALTER TABLE `bank_submissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `banks`
--

DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `banks` WRITE;
/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
INSERT INTO `banks` VALUES
(2,'ICICI Bank','Neha Gupta','neha@icici.com','9876543211','active',NULL,'2026-07-19 07:51:56'),
(3,'SBI','Amit Kumar','amit@sbi.co.in','9876543212','active',NULL,'2026-07-19 07:51:56'),
(4,'Axis Bank','Priya Jain','priya@axisbank.com','9876543213','active',NULL,'2026-07-19 07:51:56'),
(5,'Kotak Mahindra','Vikram Sethi','vikram@kotak.com','9876543214','active',NULL,'2026-07-19 07:51:56'),
(6,'Test Bank 1785011490192','Test Contact','test1785011490192@bank.com','9876543628','active','Bank','2026-07-25 20:31:30'),
(7,'Test Bank 1785012726544','Test Contact','test1785012726544@bank.com','9876543337','active','Bank','2026-07-25 20:52:06'),
(8,'Test Bank 1785022455022','Test Contact','test1785022455022@bank.com','9876543934','active','Bank','2026-07-25 23:34:15'),
(9,'Axis Bank 1785328333235','Priya Jain','priya_1785328333235@axisbank.com','9876543235','active','Entity Type: Bank\nCity: Mumbai\nState: Maharashtra\nPincode: 400001\nCountry: India','2026-07-29 12:32:13'),
(10,'Axis Bank 1785328355697','Priya Jain','priya_1785328355697@axisbank.com','9876543297','active','Entity Type: Bank\nCity: Mumbai\nState: Maharashtra\nPincode: 400001\nCountry: India','2026-07-29 12:32:35'),
(11,'My Bank','John Doe','john@bank.com','9876543210','active','Entity Type: Bank\nCountry: India','2026-07-29 12:32:58'),
(12,'My Law Firm 1785328429679','Adv. John','john_1785328429679@lawfirm.com','9876529679','active','Entity Type: Law Firm / Advocate\nCity: Delhi\nState: Delhi\nPincode: 110001\nCountry: India','2026-07-29 12:33:49'),
(13,'My Bank 1785328452514','Bank Contact','bank_1785328452514@bank.com','9876552514','active','Entity Type: Bank\nCountry: India','2026-07-29 12:34:12'),
(14,'My Entity 1785328470849','Contact Person','entity_1785328470849@example.com','9876570849','active','Entity Type: Bank\nCountry: India','2026-07-29 12:34:30'),
(15,'My Entity 1785328489554','Contact','entity_1785328489554@test.com','9876589554','active','Entity Type: Bank\nCountry: India','2026-07-29 12:34:49'),
(16,'Entity 1785328505115','Contact','entity_1785328505115@test.com','9876505115','active','Entity Type: Bank\nCountry: India','2026-07-29 12:35:04'),
(17,'Entity 1785329253704','Test','entity_1785329253704@test.com','9876553704','active','Entity Type: Bank\nCountry: India','2026-07-29 12:47:33'),
(18,'Entity 1785329343954','Test','entity_1785329343954@test.com','9876543954','active','Entity Type: Bank\nCountry: India','2026-07-29 12:49:04'),
(19,'Entity 1785329523765','Test','entity_1785329523765@test.com','9876523765','active','Entity Type: Bank\nCountry: India','2026-07-29 12:52:03'),
(20,'Updated Bank 1785336207839','Updated Contact Person','updated_1785336207839@bank.com','9876507839','active','Entity Type: Bank\nCity: Delhi\nState: Delhi\nPincode: 110001\nCountry: India','2026-07-29 14:43:11'),
(21,'New Entity 1785336229357','Contact Person','entity_1785336229357@test.com','9876529357','active','Entity Type: Bank\nCountry: India','2026-07-29 14:43:49');
/*!40000 ALTER TABLE `banks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bureau_statistics`
--

DROP TABLE IF EXISTS `bureau_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bureau_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bureau_name` varchar(50) NOT NULL,
  `avg_score` decimal(5,2) DEFAULT 0.00,
  `total_reports` int(11) DEFAULT 0,
  `month_year` date NOT NULL,
  `calculated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bureau_month` (`bureau_name`,`month_year`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bureau_statistics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bureau_statistics` WRITE;
/*!40000 ALTER TABLE `bureau_statistics` DISABLE KEYS */;
INSERT INTO `bureau_statistics` VALUES
(1,'CIBIL',682.00,24,'2026-06-01','2026-06-05 18:21:12'),
(2,'Experian',675.00,22,'2026-06-01','2026-06-05 18:21:12'),
(3,'Equifax',688.00,20,'2026-06-01','2026-06-05 18:21:12'),
(4,'CRIF',680.00,18,'2026-06-01','2026-06-05 18:21:12');
/*!40000 ALTER TABLE `bureau_statistics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bureau_submissions`
--

DROP TABLE IF EXISTS `bureau_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bureau_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `bureau` varchar(50) DEFAULT NULL,
  `dispute_id` varchar(50) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `expected_response` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_bureau_customer` (`client_id`),
  CONSTRAINT `fk_bureau_customer` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bureau_submissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bureau_submissions` WRITE;
/*!40000 ALTER TABLE `bureau_submissions` DISABLE KEYS */;
INSERT INTO `bureau_submissions` VALUES
(1,2,'CIBIL','DSP-2024-0001','2026-08-02','2026-09-01','pending','2026-08-02 08:28:53'),
(2,4,'Experian','DSP-2024-0003','2026-08-02','2026-09-16','pending','2026-08-02 08:28:53'),
(3,6,'Equifax','DSP-2024-0005','2026-08-02','2026-09-01','pending','2026-08-02 08:28:53');
/*!40000 ALTER TABLE `bureau_submissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `business_kpis`
--

DROP TABLE IF EXISTS `business_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kpi_name` varchar(100) NOT NULL,
  `kpi_value` decimal(12,2) DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `recorded_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recorded_date` (`recorded_date`),
  KEY `kpi_name` (`kpi_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_kpis`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `business_kpis` WRITE;
/*!40000 ALTER TABLE `business_kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `business_kpis` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `call_logs`
--

DROP TABLE IF EXISTS `call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `call_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `call_type` enum('incoming','outgoing') DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `call_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `call_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `call_logs` WRITE;
/*!40000 ALTER TABLE `call_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `call_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cas`
--

DROP TABLE IF EXISTS `cas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `firm_name` varchar(150) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `membership_number` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `address` text DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cas_status` (`status`),
  KEY `idx_cas_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cas` WRITE;
/*!40000 ALTER TABLE `cas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `case_assignments`
--

DROP TABLE IF EXISTS `case_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_assignments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `case_assignments` WRITE;
/*!40000 ALTER TABLE `case_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `case_assignments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `case_logs`
--

DROP TABLE IF EXISTS `case_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `case_id` int(10) unsigned NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `case_logs` WRITE;
/*!40000 ALTER TABLE `case_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `case_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `case_timeline`
--

DROP TABLE IF EXISTS `case_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `stage` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  CONSTRAINT `case_timeline_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `client_cases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_timeline`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `case_timeline` WRITE;
/*!40000 ALTER TABLE `case_timeline` DISABLE KEYS */;
INSERT INTO `case_timeline` VALUES
(1,1,'Case Created','completed','Your case has been successfully created','2026-05-09 11:59:01'),
(2,1,'Document Verification','pending','Please upload required documents for verification','2026-05-09 11:59:01'),
(3,1,'Dispute Filed','pending','We will file a dispute with the credit bureau','2026-05-09 11:59:01'),
(4,1,'Bank Response','pending','Waiting for bank response on the dispute','2026-05-09 11:59:01'),
(5,1,'Resolution','pending','Final resolution and CIBIL update','2026-05-09 11:59:01');
/*!40000 ALTER TABLE `case_timeline` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `cases`
--

DROP TABLE IF EXISTS `cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caseNo` varchar(20) NOT NULL,
  `clientId` int(11) NOT NULL,
  `clientName` varchar(100) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `status` enum('pending','in-progress','completed') DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `caseNo` (`caseNo`),
  KEY `clientId` (`clientId`),
  CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`clientId`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `cases` WRITE;
/*!40000 ALTER TABLE `cases` DISABLE KEYS */;
INSERT INTO `cases` VALUES
(1,'CR20250001',1,'Rajesh Kumar','Written Off Clearance','completed',15000.00,'2026-05-02 09:45:36'),
(2,'CR20250002',1,'Rajesh Kumar','Settled Clearance','in-progress',12000.00,'2026-05-02 09:45:36'),
(3,'CR202609465',1,'Rajesh Kumar','CIBIL Dispute Resolution','pending',25000.00,'2026-07-25 22:45:29');
/*!40000 ALTER TABLE `cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) DEFAULT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') DEFAULT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES
(1,'1000','Cash','asset',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(2,'1100','Bank Account','asset',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(3,'1200','Accounts Receivable','asset',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(4,'2000','Accounts Payable','liability',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(5,'2100','GST Payable','liability',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(6,'3000','Capital','equity',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(7,'4000','Sales Revenue','income',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(8,'4100','Service Revenue','income',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(9,'5000','Salary Expense','expense',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(10,'5100','Rent Expense','expense',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37'),
(11,'5200','Utilities Expense','expense',NULL,1,0.00,0.00,NULL,'2026-06-02 13:02:37');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_agreements`
--

DROP TABLE IF EXISTS `client_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `agreement_no` varchar(50) DEFAULT NULL,
  `agreement_type` varchar(100) DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `signed_date` date DEFAULT NULL,
  `status` enum('draft','sent','signed','expired') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_agreements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_agreements` WRITE;
/*!40000 ALTER TABLE `client_agreements` DISABLE KEYS */;
INSERT INTO `client_agreements` VALUES
(1,1,'AG-001',NULL,NULL,'2026-08-01',NULL,NULL,'signed','2026-08-01 17:21:22'),
(2,2,'AG-002',NULL,NULL,'2026-08-01',NULL,NULL,'sent','2026-08-01 17:21:22'),
(3,3,'AG-003',NULL,NULL,'2026-08-01',NULL,NULL,'draft','2026-08-01 17:21:22'),
(4,2,'AG-001',NULL,NULL,'2026-08-01',NULL,NULL,'signed','2026-08-01 17:24:23'),
(5,3,'AG-002',NULL,NULL,'2026-08-01',NULL,NULL,'sent','2026-08-01 17:24:23'),
(6,4,'AG-003',NULL,NULL,'2026-08-01',NULL,NULL,'draft','2026-08-01 17:24:23'),
(7,2,'AG-2024-0001','Credit Repair Service Agreement','Standard terms for credit repair services','2026-08-02','2027-08-02',NULL,'signed','2026-08-02 08:34:10'),
(8,3,'AG-2024-0002','NDA','Non-disclosure agreement for client data','2026-08-02','2028-08-02',NULL,'sent','2026-08-02 08:34:10'),
(9,4,'AG-2024-0003','Consent Form','Client consent for credit report access','2026-08-02','2027-02-02',NULL,'draft','2026-08-02 08:34:10'),
(10,5,'AG-2024-0004','Service Level Agreement','Service level agreement for credit repair','2026-08-02','2027-08-02',NULL,'signed','2026-08-02 08:34:10'),
(11,6,'AG-2024-0005','Credit Repair Service Agreement','Premium service agreement','2026-08-02','2028-08-02',NULL,'expired','2026-08-02 08:34:10'),
(12,2,'AG-2024-0001','Credit Repair Service Agreement','Standard terms for credit repair services','2026-08-02','2027-08-02',NULL,'signed','2026-08-02 08:35:19'),
(13,3,'AG-2024-0002','NDA','Non-disclosure agreement for client data','2026-08-02','2028-08-02',NULL,'sent','2026-08-02 08:35:19'),
(14,4,'AG-2024-0003','Consent Form','Client consent for credit report access','2026-08-02','2027-02-02',NULL,'draft','2026-08-02 08:35:19'),
(15,5,'AG-2024-0004','Service Level Agreement','Service level agreement for credit repair','2026-08-02','2027-08-02',NULL,'signed','2026-08-02 08:35:19'),
(16,6,'AG-2024-0005','Credit Repair Service Agreement','Premium service agreement','2026-08-02','2028-08-02',NULL,'expired','2026-08-02 08:35:19');
/*!40000 ALTER TABLE `client_agreements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_cases`
--

DROP TABLE IF EXISTS `client_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_no` varchar(20) NOT NULL,
  `client_email` varchar(100) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `status` enum('pending','document-verification','dispute-filed','in-progress','bank-response','completed','closed') DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `documents_verified_at` datetime DEFAULT NULL,
  `dispute_filed_at` datetime DEFAULT NULL,
  `bank_responded_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_no` (`case_no`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_cases` WRITE;
/*!40000 ALTER TABLE `client_cases` DISABLE KEYS */;
INSERT INTO `client_cases` VALUES
(1,'CR2025001','client@example.com','Rajesh Kumar','Written Off Clearance','dispute-filed',15000.00,'2026-05-02 09:51:13',NULL,NULL,NULL,NULL,'2026-05-02 09:51:13'),
(2,'CR2026001','client@example.com','Rajesh Kumar','Written Off Clearance','dispute-filed',15000.00,'2026-05-02 11:34:26',NULL,NULL,NULL,NULL,'2026-05-02 11:34:26'),
(3,'CR2026002','client@example.com','Priya Sharma','Settled Clearance','completed',12000.00,'2026-05-02 11:34:26',NULL,NULL,NULL,NULL,'2026-05-02 11:34:26'),
(5,'CASE-2024-001','rajesh@example.com','Rajesh Kumar','CIBIL Dispute Resolution','in-progress',4999.00,'2026-06-02 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
(6,'CASE-2024-002','rajesh@example.com','Rajesh Kumar','Credit Report Analysis','completed',1999.00,'2026-05-03 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
(7,'CASE-2024-003','priya@example.com','Priya Sharma','Written Off Settlement','pending',9999.00,'2026-06-02 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
(8,'CASE-2024-004','priya@example.com','Priya Sharma','Credit Score Improvement','in-progress',4999.00,'2026-05-18 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
(9,'CASE-2024-005','amit@example.com','Amit Patel','Document Verification','completed',2999.00,'2026-04-18 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
(10,'CASE-2024-006','neha@example.com','Neha Gupta','Loan Eligibility Check','pending',1999.00,'2026-06-02 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
(11,'CASE-2024-007','vikram@example.com','Vikram Singh','CIBIL Dispute Resolution','in-progress',4999.00,'2026-05-23 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31');
/*!40000 ALTER TABLE `client_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_disputes`
--

DROP TABLE IF EXISTS `client_disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `dispute_id` varchar(50) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in-progress','resolved','rejected') DEFAULT 'pending',
  `resolution` text DEFAULT NULL,
  `expected_resolution` date DEFAULT NULL,
  `filed_date` date DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispute_id` (`dispute_id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  CONSTRAINT `client_disputes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_disputes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_disputes` WRITE;
/*!40000 ALTER TABLE `client_disputes` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_disputes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_documents`
--

DROP TABLE IF EXISTS `client_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `client_email` varchar(100) DEFAULT NULL,
  `doc_type` enum('pan','aadhar','cibil') DEFAULT 'cibil',
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_data` longtext DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_client_email` (`client_email`),
  KEY `idx_doc_type` (`doc_type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_documents` WRITE;
/*!40000 ALTER TABLE `client_documents` DISABLE KEYS */;
INSERT INTO `client_documents` VALUES
(1,1,'demo@example.com','cibil','test_document.txt',NULL,'This is a test document for CIBIL Repair','text/plain',1,'2026-07-28 10:20:02'),
(2,0,'demo@example.com','pan','pan_card.txt',NULL,'PAN Card Document - Test','text/plain',1,'2026-07-28 10:20:31'),
(3,1,'demo@example.com','aadhar','aadhar_client1.txt',NULL,'Aadhar Card Document - Test with Client ID','text/plain',1,'2026-07-28 10:22:55'),
(4,0,'test@example.com','cibil','test_document.txt',NULL,'This is a test document for CIBIL Repair client.','text/plain',1,'2026-07-29 15:54:20'),
(5,0,'test@example.com','cibil','test_document.txt',NULL,'This is a test document for CIBIL Repair client.','text/plain',1,'2026-07-29 15:54:36'),
(6,0,'test@example.com','cibil','test_document.txt',NULL,'Test document content for update API','text/plain',1,'2026-07-29 16:07:46');
/*!40000 ALTER TABLE `client_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_invoices`
--

DROP TABLE IF EXISTS `client_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('issued','paid','overdue','cancelled') DEFAULT 'issued',
  `invoice_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  CONSTRAINT `client_invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_invoices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_invoices` WRITE;
/*!40000 ALTER TABLE `client_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_invoices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_issues`
--

DROP TABLE IF EXISTS `client_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `problem_description` text NOT NULL,
  `additional_info` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_issues`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_issues` WRITE;
/*!40000 ALTER TABLE `client_issues` DISABLE KEYS */;
INSERT INTO `client_issues` VALUES
(1,'Rajesh Kumar','9876543210','rajesh@example.com','Delhi','CIBIL Score','My CIBIL score is showing incorrect information.','I have attached my CIBIL report.','','pending','2026-07-29 14:18:15');
/*!40000 ALTER TABLE `client_issues` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_notifications`
--

DROP TABLE IF EXISTS `client_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `is_read` tinyint(4) DEFAULT 0,
  `is_archived` tinyint(4) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_type` (`notification_type`),
  KEY `idx_created` (`created_at`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_client_notifications_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_notifications` WRITE;
/*!40000 ALTER TABLE `client_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_payments`
--

DROP TABLE IF EXISTS `client_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `case_no` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  CONSTRAINT `client_payments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_payments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_payments` WRITE;
/*!40000 ALTER TABLE `client_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_payments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `client_scores`
--

DROP TABLE IF EXISTS `client_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `bureau` varchar(50) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `recorded_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `bureau` (`bureau`),
  CONSTRAINT `client_scores_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_scores`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `client_scores` WRITE;
/*!40000 ALTER TABLE `client_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_scores` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `client_summary`
--

DROP TABLE IF EXISTS `client_summary`;
/*!50001 DROP VIEW IF EXISTS `client_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `client_summary` AS SELECT
 NULL AS `client_id`,
 NULL AS `client_name`,
 NULL AS `email`,
 NULL AS `phone`,
 NULL AS `total_cases`,
 NULL AS `total_payments`,
 NULL AS `total_amount`,
 NULL AS `registered_date` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `cases` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `alternate_phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `pan_card` varchar(10) DEFAULT NULL,
  `aadhar_number` varchar(12) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT NULL,
  `cibil_score` int(11) DEFAULT 650,
  `cibil_last_updated` date DEFAULT NULL,
  `total_spent` decimal(12,2) DEFAULT 0.00,
  `lifetime_value` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `assigned_to` bigint(20) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_cibil_score` (`cibil_score`),
  KEY `idx_city` (`city`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_phone` (`phone`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email` (`email`),
  CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(1,'Rajesh Kumar','rajesh@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','9876543210','active',2,'2026-05-02 09:45:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,800,NULL,0.00,0.00,NULL,NULL,'2026-05-18 09:53:32',NULL),
(2,'Priya Sharma','priya@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','8765432109','active',1,'2026-05-02 09:45:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,650,NULL,0.00,0.00,NULL,NULL,'2026-05-18 09:48:05',NULL),
(3,'Admin User','admin@cibilrepair.in','0192023a7bbd73250516f069df18b500','9999999999','active',0,'2026-05-02 15:49:49',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,650,NULL,0.00,0.00,NULL,NULL,'2026-05-18 09:44:10',NULL),
(4,'Test Client','client@cibilrepair.in','3677b23baa08f74c28aba07f0cb6554e','9876543210','active',0,'2026-05-02 15:50:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,650,NULL,0.00,0.00,NULL,NULL,'2026-05-18 09:44:10',NULL),
(5,'Partner User','partner@cibilrepair.in','3c0d9364bee6c8e4e71a2aecdc6cf57f','9876543211','active',0,'2026-05-02 15:50:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,650,NULL,0.00,0.00,NULL,NULL,'2026-05-18 09:44:10',NULL);
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER clients_audit_insert
AFTER INSERT ON clients
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
    VALUES (NEW.user_id, 'INSERT', 'clients', NEW.id, 
            CONCAT('{"name":"', NEW.name, '","email":"', NEW.email, '"}'),
            @ip_address);
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER clients_audit_delete
BEFORE DELETE ON clients
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, ip_address)
    VALUES (OLD.user_id, 'DELETE', 'clients', OLD.id,
            CONCAT('{"name":"', OLD.name, '","email":"', OLD.email, '"}'),
            @ip_address);
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `commissions`
--

DROP TABLE IF EXISTS `commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_email` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `commissions` WRITE;
/*!40000 ALTER TABLE `commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `commissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `compliance_checks`
--

DROP TABLE IF EXISTS `compliance_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `check_type` varchar(100) NOT NULL,
  `check_date` date NOT NULL,
  `status` enum('pending','passed','failed','in_progress') DEFAULT 'pending',
  `findings` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `checked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `checked_by` (`checked_by`),
  KEY `idx_compliance_client` (`client_id`),
  KEY `idx_compliance_status` (`status`),
  CONSTRAINT `compliance_checks_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compliance_checks_ibfk_2` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_checks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `compliance_checks` WRITE;
/*!40000 ALTER TABLE `compliance_checks` DISABLE KEYS */;
INSERT INTO `compliance_checks` VALUES
(5,2,'KYC Verification','2026-07-27','passed','All documents verified',NULL,8,'2026-08-01 18:43:24'),
(6,3,'KYC Verification','2026-07-22','failed','PAN card mismatch',NULL,8,'2026-08-01 18:43:24'),
(7,4,'KYC Verification','2026-07-30','pending','Waiting for address proof',NULL,NULL,'2026-08-01 18:43:24'),
(8,2,'AML Check','2026-07-29','passed','No red flags found',NULL,8,'2026-08-01 18:43:24'),
(9,2,'KYC Verification','2026-07-27','passed','All documents verified successfully',NULL,8,'2026-08-01 18:44:17'),
(10,3,'KYC Verification','2026-07-22','failed','PAN card number mismatch with submitted documents',NULL,8,'2026-08-01 18:44:17'),
(11,4,'KYC Verification','2026-07-30','pending','Waiting for address proof submission',NULL,NULL,'2026-08-01 18:44:17'),
(12,2,'AML Check','2026-07-29','passed','No red flags found in transaction history',NULL,8,'2026-08-01 18:44:17'),
(13,5,'KYC Verification','2026-07-25','passed','Verified with original documents',NULL,8,'2026-08-01 18:44:17'),
(14,6,'KYC Verification','2026-07-31','in_progress','Document verification in progress',NULL,8,'2026-08-01 18:44:17');
/*!40000 ALTER TABLE `compliance_checks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `compliance_documents`
--

DROP TABLE IF EXISTS `compliance_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `doc_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `compliance_documents` WRITE;
/*!40000 ALTER TABLE `compliance_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `connectors`
--

DROP TABLE IF EXISTS `connectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `connectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `leads_referred` int(11) DEFAULT 0,
  `commission_due` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `commission_rate` decimal(5,2) DEFAULT 15.00,
  `commission_earned` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connectors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `connectors` WRITE;
/*!40000 ALTER TABLE `connectors` DISABLE KEYS */;
INSERT INTO `connectors` VALUES
(2,10,'kundan','9153900118','','bank','','','','2026-06-28 14:50:30',0,0.00,'active',15.00,0.00),
(3,10,'Test Connector','9876543210','test@connector.com','bank','HDFC Bank','Mumbai','Test connector','2026-06-29 16:33:44',0,0.00,'active',15.00,0.00),
(4,10,'CA Rajesh','9876543211','rajesh@ca.com','ca','Rajesh & Co','Delhi','Chartered Accountant','2026-06-29 16:34:02',0,0.00,'active',15.00,0.00),
(5,10,'Lawyer Priya','9876543212','priya@legal.com','lawyer','Priya Legal','Mumbai','Legal consultant','2026-06-29 16:34:02',0,0.00,'active',15.00,0.00);
/*!40000 ALTER TABLE `connectors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `consent_forms`
--

DROP TABLE IF EXISTS `consent_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `consent_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `consent_type` varchar(100) DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `provided_date` date DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('pending','provided','expired') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consent_client` (`client_id`),
  KEY `idx_consent_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consent_forms`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `consent_forms` WRITE;
/*!40000 ALTER TABLE `consent_forms` DISABLE KEYS */;
INSERT INTO `consent_forms` VALUES
(1,2,'Credit Report Access','2026-08-01','2026-08-01',NULL,'provided','2026-08-01 17:22:41'),
(2,3,'Data Processing Consent','2026-08-01',NULL,NULL,'pending','2026-08-01 17:22:41'),
(3,4,'Marketing Communication','2026-08-01','2026-08-01',NULL,'provided','2026-08-01 17:22:41'),
(4,5,'Credit Report Access','2026-08-01',NULL,NULL,'pending','2026-08-01 17:22:41'),
(5,2,'Credit Report Access','2026-08-02','2026-08-02',NULL,'provided','2026-08-02 08:34:10'),
(6,3,'Data Processing Consent','2026-08-02',NULL,NULL,'pending','2026-08-02 08:34:10'),
(7,4,'Marketing Consent','2026-08-02','2026-08-02',NULL,'provided','2026-08-02 08:34:10'),
(8,5,'Third Party Sharing','2026-08-02','2026-08-02',NULL,'','2026-08-02 08:34:10'),
(9,6,'Credit Report Access','2026-08-02','2026-08-02',NULL,'provided','2026-08-02 08:34:10'),
(10,2,'Credit Report Access','2026-08-02','2026-08-02',NULL,'provided','2026-08-02 08:35:19'),
(11,3,'Data Processing Consent','2026-08-02',NULL,NULL,'pending','2026-08-02 08:35:19'),
(12,4,'Marketing Consent','2026-08-02','2026-08-02',NULL,'provided','2026-08-02 08:35:19'),
(13,5,'Third Party Sharing','2026-08-02','2026-08-02',NULL,'','2026-08-02 08:35:19'),
(14,6,'Credit Report Access','2026-08-02','2026-08-02',NULL,'provided','2026-08-02 08:35:19');
/*!40000 ALTER TABLE `consent_forms` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_analyses`
--

DROP TABLE IF EXISTS `credit_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_analyses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `analyst_id` int(11) NOT NULL,
  `cibil_score` int(11) NOT NULL,
  `issues_found` int(11) DEFAULT 0,
  `issues_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`issues_list`)),
  `notes` text DEFAULT NULL,
  `recommended_action` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_analyst` (`analyst_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_analyses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_analyses` WRITE;
/*!40000 ALTER TABLE `credit_analyses` DISABLE KEYS */;
INSERT INTO `credit_analyses` VALUES
(1,NULL,72,1,685,3,'[\"written_off\",\"late_payment\",\"incorrect_enquiry\"]','Written off account with HDFC needs dispute',NULL,'2026-06-05 18:20:13','2026-06-05 18:20:13'),
(2,NULL,73,1,720,1,'[\"late_payment\"]','Single late payment from ICICI',NULL,'2026-06-05 18:20:13','2026-06-05 18:20:13'),
(3,NULL,74,1,580,5,'[\"written_off\",\"settled\",\"late_payment\",\"overdue\",\"duplicate_loan\"]','Multiple issues identified',NULL,'2026-06-05 18:20:13','2026-06-05 18:20:13'),
(4,NULL,72,1,685,3,'[\"written_off\",\"late_payment\",\"incorrect_enquiry\"]','Written off account with HDFC needs dispute',NULL,'2026-06-05 18:20:22','2026-06-05 18:20:22'),
(5,NULL,73,1,720,1,'[\"late_payment\"]','Single late payment from ICICI',NULL,'2026-06-05 18:20:22','2026-06-05 18:20:22'),
(6,NULL,74,1,580,5,'[\"written_off\",\"settled\",\"late_payment\",\"overdue\",\"duplicate_loan\"]','Multiple issues identified',NULL,'2026-06-05 18:20:22','2026-06-05 18:20:22'),
(7,NULL,72,1,685,3,'[\"written_off\",\"late_payment\",\"incorrect_enquiry\"]','Written off account with HDFC needs dispute',NULL,'2026-06-05 18:21:12','2026-06-05 18:21:12'),
(8,NULL,73,1,720,1,'[\"late_payment\"]','Single late payment from ICICI',NULL,'2026-06-05 18:21:12','2026-06-05 18:21:12'),
(9,NULL,74,1,580,5,'[\"written_off\",\"settled\",\"late_payment\",\"overdue\",\"duplicate_loan\"]','Multiple issues identified',NULL,'2026-06-05 18:21:12','2026-06-05 18:21:12');
/*!40000 ALTER TABLE `credit_analyses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_analysis`
--

DROP TABLE IF EXISTS `credit_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `report_id` int(11) DEFAULT NULL,
  `cibil_score` int(11) DEFAULT NULL,
  `experian_score` int(11) DEFAULT NULL,
  `equifax_score` int(11) DEFAULT NULL,
  `crif_score` int(11) DEFAULT NULL,
  `issues` text DEFAULT NULL,
  `analyst_notes` text DEFAULT NULL,
  `analyst_id` int(11) DEFAULT NULL,
  `status` enum('pending','analyzed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `analyzed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_analysis`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_analysis` WRITE;
/*!40000 ALTER TABLE `credit_analysis` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_analysis` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_analysis_results`
--

DROP TABLE IF EXISTS `credit_analysis_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_analysis_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `cibil_score` int(11) DEFAULT NULL,
  `experian_score` int(11) DEFAULT NULL,
  `equifax_score` int(11) DEFAULT NULL,
  `crif_score` int(11) DEFAULT NULL,
  `issues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`issues`)),
  `summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `analyzed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `credit_analysis_results_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `credit_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_analysis_results`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_analysis_results` WRITE;
/*!40000 ALTER TABLE `credit_analysis_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_analysis_results` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_history`
--

DROP TABLE IF EXISTS `credit_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_history` WRITE;
/*!40000 ALTER TABLE `credit_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_issues`
--

DROP TABLE IF EXISTS `credit_issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `analysis_id` int(11) DEFAULT NULL,
  `report_id` int(11) DEFAULT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `issue_label` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','disputed','resolved') DEFAULT 'pending',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_issues_client` (`client_id`),
  KEY `idx_issues_type` (`issue_type`),
  KEY `idx_issues_report` (`report_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_issues`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_issues` WRITE;
/*!40000 ALTER TABLE `credit_issues` DISABLE KEYS */;
INSERT INTO `credit_issues` VALUES
(1,72,NULL,NULL,'written_off',NULL,'HDFC Bank',25000.00,'Written off account from 2022','pending',NULL,'2026-06-05 18:21:12'),
(2,72,NULL,NULL,'late_payment',NULL,'ICICI Bank',5000.00,'Late payment of 90+ days','disputed',NULL,'2026-06-05 18:21:12'),
(3,72,NULL,NULL,'incorrect_enquiry',NULL,'Axis Bank',0.00,'Unauthorised hard enquiry','pending',NULL,'2026-06-05 18:21:12'),
(4,73,NULL,NULL,'late_payment',NULL,'ICICI Bank',3500.00,'One time late payment','resolved',NULL,'2026-06-05 18:21:12'),
(5,74,NULL,NULL,'written_off',NULL,'SBI',45000.00,'Credit card written off','pending',NULL,'2026-06-05 18:21:12'),
(6,74,NULL,NULL,'settled',NULL,'HDFC Bank',15000.00,'Loan settled for less than full amount','pending',NULL,'2026-06-05 18:21:12'),
(7,74,NULL,NULL,'overdue',NULL,'Yes Bank',25000.00,'Overdue personal loan EMI','pending',NULL,'2026-06-05 18:21:12'),
(8,75,NULL,NULL,'duplicate_loan',NULL,'ICICI Bank',500000.00,'Duplicate home loan entry','',NULL,'2026-06-05 18:21:12'),
(9,8,NULL,73,'written_off','Written Off Account','HDFC Bank',25000.00,NULL,'pending',NULL,'2026-08-01 17:58:19'),
(10,8,NULL,73,'settled','Settled Account','ICICI Bank',15000.00,NULL,'pending',NULL,'2026-08-01 17:58:19'),
(11,16,NULL,74,'late_payment','Late Payment','SBI',5000.00,NULL,'pending',NULL,'2026-08-01 17:58:19'),
(12,16,NULL,74,'incorrect_enquiry','Incorrect Enquiry','Axis Bank',0.00,NULL,'pending',NULL,'2026-08-01 17:58:19'),
(13,17,NULL,75,'written_off','Written Off Account','Kotak Bank',35000.00,NULL,'pending',NULL,'2026-08-01 17:58:19'),
(14,19,NULL,76,'settled','Settled Account','HDFC Bank',12000.00,NULL,'pending',NULL,'2026-08-01 17:58:19'),
(15,19,NULL,76,'late_payment','Late Payment','ICICI Bank',3000.00,NULL,'pending',NULL,'2026-08-01 17:58:19');
/*!40000 ALTER TABLE `credit_issues` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_reports`
--

DROP TABLE IF EXISTS `credit_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `bureau` varchar(50) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `cibil_score` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `analyzed_by` int(11) DEFAULT NULL,
  `issues_found` text DEFAULT NULL,
  `analyst_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','analyzed') DEFAULT 'pending',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `analyzed_at` timestamp NULL DEFAULT NULL,
  `experian_score` int(11) DEFAULT NULL,
  `equifax_score` int(11) DEFAULT NULL,
  `crif_score` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `bureau` (`bureau`),
  KEY `idx_reports_client` (`client_id`),
  KEY `idx_reports_status` (`status`),
  CONSTRAINT `credit_reports_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_reports` WRITE;
/*!40000 ALTER TABLE `credit_reports` DISABLE KEYS */;
INSERT INTO `credit_reports` VALUES
(69,8,'cibil',720,720,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-22 17:57:37',NULL,NULL,NULL,NULL),
(70,16,'cibil',580,580,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-24 17:57:37',NULL,NULL,NULL,NULL),
(71,17,'cibil',650,650,NULL,NULL,NULL,NULL,NULL,'pending','2026-07-29 17:57:37',NULL,NULL,NULL,NULL),
(72,19,'cibil',450,450,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-17 17:57:37',NULL,NULL,NULL,NULL),
(73,8,'experian',700,700,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-27 17:57:37',NULL,NULL,NULL,NULL),
(74,8,'equifax',710,710,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-25 17:58:07',NULL,NULL,NULL,NULL),
(75,16,'experian',560,560,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-26 17:58:07',NULL,NULL,NULL,NULL),
(76,17,'cibil',620,620,NULL,NULL,NULL,NULL,NULL,'pending','2026-07-30 17:58:07',NULL,NULL,NULL,NULL),
(77,19,'experian',430,430,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-20 17:58:07',NULL,NULL,NULL,NULL),
(78,8,'crif',690,690,NULL,NULL,NULL,NULL,NULL,'analyzed','2026-07-28 17:58:07',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `credit_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_score_history`
--

DROP TABLE IF EXISTS `credit_score_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_score_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `bureau` enum('cibil','experian','equifax','crif') DEFAULT 'cibil',
  `client_email` varchar(100) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  `recorded_date` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_score_client` (`client_id`),
  KEY `idx_score_bureau` (`bureau`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_score_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_score_history` WRITE;
/*!40000 ALTER TABLE `credit_score_history` DISABLE KEYS */;
INSERT INTO `credit_score_history` VALUES
(1,NULL,'cibil','client@example.com',635,'2026-08-01 17:54:36','2025-12-02',NULL),
(2,NULL,'cibil','client@example.com',650,'2026-08-01 17:54:36','2026-01-02',NULL),
(3,NULL,'cibil','client@example.com',668,'2026-08-01 17:54:36','2026-02-02',NULL),
(4,NULL,'cibil','client@example.com',679,'2026-08-01 17:54:36','2026-03-02',NULL),
(5,NULL,'cibil','client@example.com',685,'2026-08-01 17:54:36','2026-04-02',NULL),
(6,NULL,'cibil','client@example.com',695,'2026-08-01 17:54:36','2026-05-02',NULL),
(7,NULL,'cibil','client@example.com',635,'2026-08-01 17:54:36','2025-12-02',NULL),
(8,NULL,'cibil','client@example.com',650,'2026-08-01 17:54:36','2026-01-02',NULL),
(9,NULL,'cibil','client@example.com',668,'2026-08-01 17:54:36','2026-02-02',NULL),
(10,NULL,'cibil','client@example.com',679,'2026-08-01 17:54:36','2026-03-02',NULL),
(11,NULL,'cibil','client@example.com',685,'2026-08-01 17:54:36','2026-04-02',NULL),
(12,NULL,'cibil','client@example.com',695,'2026-08-01 17:54:36','2026-05-02',NULL),
(13,8,'cibil','info@cibilrepair.in',680,'2026-08-01 17:58:33','2026-06-02',NULL),
(14,8,'cibil','info@cibilrepair.in',700,'2026-08-01 17:58:33','2026-07-02',NULL),
(15,8,'cibil','info@cibilrepair.in',720,'2026-08-01 17:58:33','2026-08-01',NULL),
(16,16,'cibil','laxmikundan10@gmail.com',550,'2026-08-01 17:58:33','2026-06-17',NULL),
(17,16,'cibil','laxmikundan10@gmail.com',580,'2026-08-01 17:58:33','2026-08-01',NULL),
(18,17,'cibil','kundankhatri@gmail.com',630,'2026-08-01 17:58:33','2026-07-12',NULL),
(19,17,'cibil','kundankhatri@gmail.com',650,'2026-08-01 17:58:33','2026-08-01',NULL),
(20,19,'cibil','khotegiri@gmail.com',470,'2026-08-01 17:58:33','2026-07-02',NULL),
(21,19,'cibil','khotegiri@gmail.com',450,'2026-08-01 17:58:33','2026-08-01',NULL);
/*!40000 ALTER TABLE `credit_score_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `credit_scores`
--

DROP TABLE IF EXISTS `credit_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `recorded_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_date` (`recorded_date`),
  KEY `idx_credit_client_date` (`client_id`,`recorded_date`),
  CONSTRAINT `credit_scores_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_scores`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `credit_scores` WRITE;
/*!40000 ALTER TABLE `credit_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `credit_scores` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `critical_alerts`
--

DROP TABLE IF EXISTS `critical_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `critical_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `severity` (`severity`),
  KEY `is_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `critical_alerts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `critical_alerts` WRITE;
/*!40000 ALTER TABLE `critical_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `critical_alerts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customer_feedback`
--

DROP TABLE IF EXISTS `customer_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `feedback` text DEFAULT NULL,
  `resolved_satisfied` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_feedback`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customer_feedback` WRITE;
/*!40000 ALTER TABLE `customer_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_feedback` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customer_requests`
--

DROP TABLE IF EXISTS `customer_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `service` varchar(60) NOT NULL DEFAULT 'Written Off',
  `date` date NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `request_type` varchar(50) DEFAULT 'general',
  `notes` text DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `source` varchar(50) DEFAULT 'website',
  `follow_up_date` date DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_req_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customer_requests` WRITE;
/*!40000 ALTER TABLE `customer_requests` DISABLE KEYS */;
INSERT INTO `customer_requests` VALUES
(2,'Kavita Gupta','kavita@example.com','9876543221','Profile Correction','2025-04-21','pending','medium','general',NULL,NULL,'website',NULL,NULL,'2026-07-19 07:51:56'),
(3,'Test Request 1785017894466','request1785017894466@test.com','9876543653','CIBIL Repair','2025-07-26','pending','high','support','Customer needs urgent CIBIL repair assistance',0,'website','2025-08-02',0,'2026-07-25 22:18:14'),
(4,'Test Request','test@example.com','9876543210','CIBIL Repair','2026-07-29','pending','high','general','Need help',NULL,'website',NULL,NULL,'2026-07-29 13:17:00'),
(5,'Updated Request Name','new@example.com','9876543210','CIBIL Repair','2026-07-29','','high','general','Working on this request - assigned to team',1,'website',NULL,NULL,'2026-07-29 13:17:19'),
(6,'Test Request 1785343057852','request_1785343057852@test.com','9876557852','CIBIL Repair','0000-00-00','pending','high','general','Test from console',NULL,'website',NULL,NULL,'2026-07-29 16:37:38'),
(7,'Check Request','check@request.com','9876543210','CIBIL Repair','0000-00-00','pending','medium','general','',NULL,'website',NULL,NULL,'2026-07-29 16:37:59');
/*!40000 ALTER TABLE `customer_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'India',
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `aadhar_number` varchar(12) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `joined` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `service` varchar(100) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `lead_id` (`lead_id`),
  KEY `partner_id` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(2,'Priya Sharma','priya@example.com','9876543211',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-02-10','2026-07-19 07:51:56','Settled',32,16,'lead'),
(3,'Suresh Singh','suresh@example.com','9876543212',NULL,'Chennai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'inactive',NULL,'2025-01-20','2026-07-19 07:51:56','Profile Correction',41,16,'lead'),
(4,'Meena Patel','meena@example.com','9876543213',NULL,'Pune',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-03-05','2026-07-19 07:51:56','Written Off',42,16,'lead'),
(5,'Arun Verma','arun@example.com','9876543220',NULL,'Jaipur',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-04-12','2026-07-19 07:51:56','Settled',43,16,'lead'),
(6,'Kavita Gupta','kavita@example.com','9876543221',NULL,'Hyderabad',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-04-20','2026-07-19 07:51:56','Written Off',38,16,'lead'),
(7,'Deepika Rao','deepika@email.com','9876541005',NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-07-19','2026-07-19 07:54:26','CIBIL Repair',44,16,'lead'),
(9,'Simple Customer 1785013101654','simple1785013101654@customer.com','9876543253',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-25','2026-07-25 20:58:21','Written Off',45,16,'lead'),
(12,'Test Customer 1785022455090','test1785022455090@customer.com','9876543358',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-25','2026-07-25 23:34:15','Written Off',46,16,'lead'),
(14,'Ankit Sharma','ankit@email.com','9876541000',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-07-25','2026-07-25 23:38:33','CIBIL Repair',47,16,'lead'),
(15,'Updated Name','updated@email.com','9876543210',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-25','2026-07-25 23:59:12','Settled',48,16,'lead'),
(17,'Test Customer 1785024272753','test1785024272753@customer.com','9876543444',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-26','2026-07-26 00:04:33','CIBIL Repair',49,16,'lead'),
(18,'Rajesh Kumar','rajesh@example.com','9876543210',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2026-07-28 10:27:07','Written Off',31,16,'lead'),
(22,'Amit Singh','amit.singh@example.com','9876543212',NULL,'Bangalore',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2026-07-28 10:27:56','Profile Correction',41,16,'lead'),
(23,'Sneha Patel','sneha@example.com','9876543213',NULL,'Pune',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2026-07-28 10:27:56','Written Off',34,16,'lead'),
(24,'Vikram Rao','vikram@example.com','9876543214',NULL,'Chennai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2026-07-28 10:27:56','Settled',35,16,'lead'),
(25,'Rahul Sharma','rahul@example.com','9876543210',NULL,'Payment',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-07-29','2026-07-29 11:57:19',NULL,48,16,'lead'),
(26,'Updated 1785329231152','update_1785329231152@example.com','9876531152',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:43:40','Written Off',52,16,'lead'),
(27,'New Customer 1785329044384','customer_1785329044384@example.com','9876544384',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:44:04','Written Off',53,16,'lead'),
(28,'Customer 1785329120302','cust_1785329120302@example.com','9876520302',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:45:20','CIBIL Repair',54,16,'lead'),
(29,'Customer 1785329231151','cust_1785329231151@example.com','9876531151',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:47:11','CIBIL Repair',55,16,'lead'),
(30,'Updated Customer 1785329324126','updated_1785329324126@example.com','9876524126',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:47:33','Written Off',56,16,'lead'),
(31,'Customer 1785329324125','cust_1785329324125@example.com','9876524125',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:48:44','CIBIL Repair',57,16,'lead'),
(32,'Test 1785329343954','test_1785329343954@test.com','9876543954',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:49:03','CIBIL Repair',58,16,'lead'),
(33,'Test 1785329523765','test_1785329523765@test.com','9876523765',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:52:03','CIBIL Repair',59,16,'lead'),
(34,'Customer 1785329558760','cust_1785329558760@example.com','9876558760',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 12:52:38','CIBIL Repair',60,16,'lead'),
(35,'Rajesh Kumar','rajesh_1785329754452@example.com','9876554452',NULL,'Payment',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-07-29','2026-07-29 12:55:54',NULL,61,16,'lead'),
(36,'Final Updated Name 1785337107671','final_1785337107671@example.com','9876507671',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 14:57:21','CIBIL Repair',62,16,'lead'),
(37,'New Customer 1785337082357','new_1785337082357@example.com','9876582357',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 14:58:02','CIBIL Repair',63,16,'lead'),
(38,'Customer 1785337126780','cust_1785337126780@example.com','9876526780',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 14:58:47','CIBIL Repair',64,16,'lead'),
(39,'Test Lead 1785329467337','test_lead_17@example.com','9876567337',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 15:02:30','CIBIL Repair',65,16,'lead'),
(40,'Test Customer 1785342317620','test_1785342317620@example.com','9876517620',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 16:25:17','CIBIL Repair',66,16,'lead'),
(41,'Test Customer 1785342380066','test_1785342380066@example.com','9876580066',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 16:26:20','CIBIL Repair',67,16,'lead'),
(42,'Test Customer 1785342650443','test_1785342650443@example.com','9876550443',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 16:30:50','CIBIL Repair',68,16,'lead'),
(43,'Updated Check','console_1785342877468@test.com','9876577469',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-29','2026-07-29 16:34:37','CIBIL Repair',69,16,'lead'),
(44,'Demo Client','demo@example.com','9876543210',NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2026-08-02 08:02:14',NULL,NULL,NULL,NULL),
(45,'Test Client','test@example.com','9876543211',NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL,'2026-08-02 08:02:14',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `daily_operational_reports`
--

DROP TABLE IF EXISTS `daily_operational_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_operational_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `cases_opened` int(11) DEFAULT 0,
  `cases_closed` int(11) DEFAULT 0,
  `avg_resolution_days` decimal(5,2) DEFAULT 0.00,
  `sla_met_percent` int(11) DEFAULT 0,
  `file_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_operational_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `daily_operational_reports` WRITE;
/*!40000 ALTER TABLE `daily_operational_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_operational_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `daily_operations_reports`
--

DROP TABLE IF EXISTS `daily_operations_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_operations_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_date` date DEFAULT NULL,
  `cases_opened` int(11) DEFAULT NULL,
  `cases_closed` int(11) DEFAULT NULL,
  `avg_resolution_days` decimal(5,2) DEFAULT NULL,
  `sla_met_percent` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_operations_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `daily_operations_reports` WRITE;
/*!40000 ALTER TABLE `daily_operations_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_operations_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `database_cleanup_log`
--

DROP TABLE IF EXISTS `database_cleanup_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `database_cleanup_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `affected_rows` int(11) DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `database_cleanup_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `database_cleanup_log` WRITE;
/*!40000 ALTER TABLE `database_cleanup_log` DISABLE KEYS */;
INSERT INTO `database_cleanup_log` VALUES
(1,'Archived empty table','performance_reviews',NULL,'2026-06-02 20:54:13'),
(2,'Archived empty table','unified_reviews',NULL,'2026-06-02 20:54:13'),
(3,'Archived empty table','franchise_commission',NULL,'2026-06-02 20:54:13'),
(4,'Archived empty table','loan_commission',NULL,'2026-06-02 20:54:13'),
(5,'Archived empty table','partner_commission',NULL,'2026-06-02 20:54:13'),
(6,'Archived empty table','partner_commissions',NULL,'2026-06-02 20:54:13'),
(7,'Archived empty table','transactions',NULL,'2026-06-02 20:55:24'),
(8,'Archived empty table','payment_transactions',NULL,'2026-06-02 20:55:24'),
(9,'Archived empty table','loyalty_transactions',NULL,'2026-06-02 20:55:24'),
(10,'Archived empty table','gst_invoices',0,'2026-06-02 21:12:08'),
(11,'Archived empty table','gst_returns',0,'2026-06-02 21:12:08'),
(12,'Archived empty table','interviews',0,'2026-06-02 21:12:08'),
(13,'Archived empty table','invoices',0,'2026-06-02 21:12:08'),
(14,'Archived empty table','job_applications',0,'2026-06-02 21:12:08'),
(15,'Archived empty table','job_openings',0,'2026-06-02 21:12:08'),
(16,'Archived empty table','journal_entry_lines',0,'2026-06-02 21:12:08'),
(17,'Archived empty table','kyc_records',0,'2026-06-02 21:12:08'),
(18,'Archived empty table','lead_documents',0,'2026-06-02 21:12:08'),
(19,'Archived empty table','lead_followups',0,'2026-06-02 21:12:08'),
(20,'Archived empty table','lead_scores',0,'2026-06-02 21:12:08'),
(21,'Archived empty table','lead_score_history',0,'2026-06-02 21:12:08'),
(22,'Archived empty table','lead_scoring_history',0,'2026-06-02 21:12:08'),
(23,'Archived empty table','loans',0,'2026-06-02 21:12:08'),
(24,'Archived empty table','loan_applications',0,'2026-06-02 21:12:08'),
(25,'Archived empty table','login_history',0,'2026-06-02 21:12:08'),
(26,'Archived empty table','loyalty_points',0,'2026-06-02 21:12:08'),
(27,'Archived empty table','magic_links',0,'2026-06-02 21:12:08'),
(28,'Archived empty table','mobile_tokens',0,'2026-06-02 21:12:08'),
(29,'Archived empty table','ombudsman_cases',0,'2026-06-02 21:12:08'),
(30,'Archived empty table','operation_cases',0,'2026-06-02 21:12:08'),
(31,'Archived empty table','operation_tasks',0,'2026-06-02 21:12:08'),
(32,'Archived empty table','otp_verification',0,'2026-06-02 21:12:08'),
(33,'Archived empty table','partner_applications',0,'2026-06-02 21:12:08'),
(34,'Archived empty table','partner_connectors',0,'2026-06-02 21:12:08');
/*!40000 ALTER TABLE `database_cleanup_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `deleted_users_archive`
--

DROP TABLE IF EXISTS `deleted_users_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deleted_users_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `deleted_by` int(11) NOT NULL,
  `deleted_at` datetime NOT NULL,
  `original_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`original_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_deleted_by` (`deleted_by`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deleted_users_archive`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `deleted_users_archive` WRITE;
/*!40000 ALTER TABLE `deleted_users_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `deleted_users_archive` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_code` varchar(20) DEFAULT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_of_dept` int(11) DEFAULT NULL,
  `parent_department` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_code` (`department_code`),
  KEY `head_of_dept` (`head_of_dept`),
  CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`head_of_dept`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES
(1,'HR','Human Resources','Managing employees, recruitment, payroll',NULL,NULL,'active','2026-06-02 13:02:37'),
(2,'IT','Information Technology','Software development, IT infrastructure',NULL,NULL,'active','2026-06-02 13:02:37'),
(3,'FIN','Finance & Accounts','Accounting, payments, financial reporting',NULL,NULL,'active','2026-06-02 13:02:37'),
(4,'SALES','Sales & Marketing','Lead generation, client acquisition',NULL,NULL,'active','2026-06-02 13:02:37'),
(5,'OPS','Operations','Daily operations, case management',NULL,NULL,'active','2026-06-02 13:02:37'),
(6,'CS','Customer Support','Client support, ticket resolution',NULL,NULL,'active','2026-06-02 13:02:37'),
(13,'DEPT-MGMT','Management','Executive Management',NULL,NULL,'active','2026-06-03 19:02:37'),
(14,'DEPT-SALES','Sales','Sales and Business Development',NULL,NULL,'active','2026-06-03 19:02:37'),
(15,'DEPT-FIN','Finance','Finance and Accounting',NULL,NULL,'active','2026-06-03 19:02:37'),
(16,'DEPT-HR','HR','Human Resources',NULL,NULL,'active','2026-06-03 19:02:37'),
(17,'DEPT-TECH','Technology','IT and Technology',NULL,NULL,'active','2026-06-03 19:02:37'),
(18,'DEPT-OPS','Operations','Operations Management',NULL,NULL,'active','2026-06-03 19:02:37'),
(19,'DEPT-LEGAL','Legal','Legal and Compliance',NULL,NULL,'active','2026-06-03 19:02:37'),
(20,'DEPT-SUPPORT','Support','Customer Support',NULL,NULL,'active','2026-06-03 19:02:37'),
(21,'DEPT-MKT','Marketing','Marketing and Communications',NULL,NULL,'active','2026-06-03 19:02:37'),
(37,NULL,'Administration',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(38,NULL,'Credit Analysis',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(39,NULL,'Dispute Resolution',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(40,NULL,'Sales',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(41,NULL,'Support',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(42,NULL,'HR',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(43,NULL,'Finance',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(44,NULL,'IT',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
(45,'ADMIN','Administration',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
(46,'CA','Credit Analysis',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
(47,'DR','Dispute Resolution',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
(48,'SUPPORT','Support',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
(50,'GEN','General',NULL,NULL,NULL,'active','2026-08-02 07:32:20');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `designations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation_code` varchar(20) DEFAULT NULL,
  `designation_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `reports_to` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `designation_code` (`designation_code`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `designations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
INSERT INTO `designations` VALUES
(1,'CEO','Chief Executive Officer',1,1,NULL,'active'),
(2,'CTO','Chief Technology Officer',2,2,NULL,'active'),
(3,'CFO','Chief Financial Officer',3,2,NULL,'active'),
(4,'HRM','HR Manager',1,3,NULL,'active'),
(5,'TL','Team Lead',2,4,NULL,'active'),
(6,'DEV','Software Developer',2,5,NULL,'active'),
(7,'ACC','Accountant',3,4,NULL,'active'),
(8,'SREP','Sales Representative',4,4,NULL,'active'),
(9,'CSE','Customer Support Executive',6,4,NULL,'active'),
(10,'DESIG-MGMT','General Manager',1,0,NULL,'active'),
(11,'DESIG-MGR','Manager',NULL,0,NULL,'active'),
(12,'DESIG-SR-EXEC','Senior Executive',NULL,0,NULL,'active'),
(13,'DESIG-EXEC','Executive',NULL,0,NULL,'active'),
(14,'DESIG-TL','Team Lead',NULL,0,NULL,'active'),
(15,NULL,'CEO',NULL,NULL,NULL,'active'),
(16,NULL,'Manager',NULL,NULL,NULL,'active'),
(17,NULL,'Team Lead',NULL,NULL,NULL,'active'),
(18,NULL,'Senior Executive',NULL,NULL,NULL,'active'),
(19,NULL,'Executive',NULL,NULL,NULL,'active'),
(20,NULL,'Trainee',NULL,NULL,NULL,'active'),
(21,NULL,'Intern',NULL,NULL,NULL,'active'),
(22,NULL,'CEO',NULL,NULL,NULL,'active'),
(23,NULL,'Manager',NULL,NULL,NULL,'active'),
(24,NULL,'Team Lead',NULL,NULL,NULL,'active'),
(25,NULL,'Senior Executive',NULL,NULL,NULL,'active'),
(26,NULL,'Executive',NULL,NULL,NULL,'active'),
(27,NULL,'Trainee',NULL,NULL,NULL,'active'),
(28,NULL,'Intern',NULL,NULL,NULL,'active'),
(29,NULL,'CEO',NULL,NULL,NULL,'active'),
(30,NULL,'Manager',NULL,NULL,NULL,'active'),
(31,NULL,'Team Lead',NULL,NULL,NULL,'active'),
(32,NULL,'Senior Executive',NULL,NULL,NULL,'active'),
(33,NULL,'Executive',NULL,NULL,NULL,'active'),
(34,NULL,'Trainee',NULL,NULL,NULL,'active'),
(35,NULL,'Intern',NULL,NULL,NULL,'active'),
(36,'EMP','Employee',NULL,NULL,NULL,'active');
/*!40000 ALTER TABLE `designations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dispute_documents`
--

DROP TABLE IF EXISTS `dispute_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dispute_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `doc_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_docs_dispute` (`dispute_id`),
  CONSTRAINT `fk_docs_dispute` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dispute_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dispute_documents` WRITE;
/*!40000 ALTER TABLE `dispute_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `dispute_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `disputes`
--

DROP TABLE IF EXISTS `disputes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_no` varchar(50) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `issue_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `resolution_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispute_no` (`dispute_no`),
  KEY `fk_disputes_customer` (`client_id`),
  CONSTRAINT `fk_disputes_customer` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disputes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `disputes` WRITE;
/*!40000 ALTER TABLE `disputes` DISABLE KEYS */;
INSERT INTO `disputes` VALUES
(2,'DSP-2024-0001',2,'CIBIL','Bureau Dispute','Incorrect late payment reported on CIBIL score','submitted',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(3,'DSP-2024-0002',3,'HDFC Bank','Bank Dispute','Wrongful charge on credit card','under_review',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(4,'DSP-2024-0003',4,'Experian','Bureau Dispute','Identity theft issue - unauthorized account','bank_response',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(5,'DSP-2024-0004',5,'SBI Bank','Bank Dispute','Loan settlement not updated on CIBIL','resolved',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(6,'DSP-2024-0005',6,'Equifax','Bureau Dispute','Duplicate account entry found','draft',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(7,'DSP-2024-0006',7,'ICICI Bank','Bank Dispute','Wrongful EMI deduction','submitted',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(8,'DSP-2024-0007',14,'CIBIL','Bureau Dispute','Wrong address reported on CIBIL','under_review',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30'),
(9,'DSP-2024-0008',15,'Axis Bank','Bank Dispute','Wrongful service charges','pending',NULL,NULL,1,'2026-08-02 08:28:30','2026-08-02 08:28:30');
/*!40000 ALTER TABLE `disputes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_audit_log`
--

DROP TABLE IF EXISTS `dm_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('view','download','upload','update','delete','share','sign') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_document` (`document_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_audit_composite` (`document_id`,`action`,`created_at`),
  CONSTRAINT `dm_audit_log_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dm_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_audit_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_audit_log` WRITE;
/*!40000 ALTER TABLE `dm_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_audit_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_document_permissions`
--

DROP TABLE IF EXISTS `dm_document_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_document_permissions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permission_type` enum('view','download','edit','delete','share') NOT NULL,
  `granted_by` int(11) NOT NULL,
  `granted_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_doc_user_permission` (`document_id`,`user_id`,`permission_type`),
  KEY `idx_document` (`document_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `dm_document_permissions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dm_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dm_document_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_document_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_document_permissions` WRITE;
/*!40000 ALTER TABLE `dm_document_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_document_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_document_tags`
--

DROP TABLE IF EXISTS `dm_document_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_document_tags` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_document_tag` (`document_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `dm_document_tags_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dm_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dm_document_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `dm_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_document_tags`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_document_tags` WRITE;
/*!40000 ALTER TABLE `dm_document_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_document_tags` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_document_versions`
--

DROP TABLE IF EXISTS `dm_document_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_document_versions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `version_number` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `change_notes` text DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_document` (`document_id`),
  CONSTRAINT `dm_document_versions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dm_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_document_versions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_document_versions` WRITE;
/*!40000 ALTER TABLE `dm_document_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_document_versions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_documents`
--

DROP TABLE IF EXISTS `dm_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_documents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` varchar(50) NOT NULL,
  `folder_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT 'application/pdf',
  `version` int(11) DEFAULT 1,
  `status` enum('draft','pending','approved','rejected','archived') DEFAULT 'draft',
  `is_encrypted` tinyint(1) DEFAULT 0,
  `encryption_key` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_id` (`document_id`),
  KEY `idx_folder` (`folder_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_status` (`status`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_documents_composite` (`client_id`,`status`,`uploaded_at`),
  CONSTRAINT `dm_documents_ibfk_1` FOREIGN KEY (`folder_id`) REFERENCES `dm_folders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `dm_documents_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_documents` WRITE;
/*!40000 ALTER TABLE `dm_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_generate_document_id
BEFORE INSERT ON dm_documents
FOR EACH ROW
BEGIN
    IF NEW.document_id IS NULL THEN
        SET NEW.document_id = CONCAT('DOC-', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(COALESCE((SELECT MAX(id) FROM dm_documents), 0) + 1, 6, '0'));
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_document_audit
AFTER INSERT ON dm_documents
FOR EACH ROW
BEGIN
    INSERT INTO dm_audit_log (document_id, user_id, action, ip_address, user_agent)
    VALUES (NEW.id, NEW.uploaded_by, 'upload', 
            (SELECT ip_address FROM (SELECT '127.0.0.1' as ip_address) as tmp), 
            (SELECT user_agent FROM (SELECT 'System' as user_agent) as tmp2));
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `dm_esignatures`
--

DROP TABLE IF EXISTS `dm_esignatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_esignatures` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `document_id` bigint(20) NOT NULL,
  `client_id` int(11) NOT NULL,
  `signer_name` varchar(255) NOT NULL,
  `signer_email` varchar(255) NOT NULL,
  `signature_image` text DEFAULT NULL,
  `signature_hash` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT current_timestamp(),
  `certificate_number` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `idx_document` (`document_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_esignatures_composite` (`document_id`,`signed_at`),
  CONSTRAINT `dm_esignatures_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `dm_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dm_esignatures_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_esignatures`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_esignatures` WRITE;
/*!40000 ALTER TABLE `dm_esignatures` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_esignatures` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_folders`
--

DROP TABLE IF EXISTS `dm_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_folders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folder_name` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `folder_path` varchar(500) DEFAULT NULL,
  `folder_color` varchar(7) DEFAULT '#3b82f6',
  `access_level` enum('public','private','restricted') DEFAULT 'private',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_access_level` (`access_level`),
  CONSTRAINT `dm_folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `dm_folders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_folders`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_folders` WRITE;
/*!40000 ALTER TABLE `dm_folders` DISABLE KEYS */;
INSERT INTO `dm_folders` VALUES
(1,'Client Documents',NULL,'/Client Documents','#3b82f6','private',1,'2026-06-04 18:35:18','2026-06-04 18:35:18'),
(2,'Legal Agreements',NULL,'/Legal Agreements','#3b82f6','restricted',1,'2026-06-04 18:35:18','2026-06-04 18:35:18'),
(3,'KYC Documents',NULL,'/KYC Documents','#3b82f6','restricted',1,'2026-06-04 18:35:18','2026-06-04 18:35:18'),
(4,'Credit Reports',NULL,'/Credit Reports','#3b82f6','private',1,'2026-06-04 18:35:18','2026-06-04 18:35:18'),
(5,'Dispute Letters',NULL,'/Dispute Letters','#3b82f6','private',1,'2026-06-04 18:35:18','2026-06-04 18:35:18'),
(6,'Internal Policies',NULL,'/Internal Policies','#3b82f6','private',1,'2026-06-04 18:35:18','2026-06-04 18:35:18');
/*!40000 ALTER TABLE `dm_folders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_tags`
--

DROP TABLE IF EXISTS `dm_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL,
  `tag_color` varchar(7) DEFAULT '#6b7280',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_tags`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_tags` WRITE;
/*!40000 ALTER TABLE `dm_tags` DISABLE KEYS */;
INSERT INTO `dm_tags` VALUES
(1,'Confidential','#ef4444','2026-06-04 18:35:18'),
(2,'Urgent','#f59e0b','2026-06-04 18:35:18'),
(3,'Archived','#6b7280','2026-06-04 18:35:18'),
(4,'Pending Review','#3b82f6','2026-06-04 18:35:18'),
(5,'Signed','#10b981','2026-06-04 18:35:18');
/*!40000 ALTER TABLE `dm_tags` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_workflow_instances`
--

DROP TABLE IF EXISTS `dm_workflow_instances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_workflow_instances` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `workflow_id` int(11) NOT NULL,
  `document_id` bigint(20) NOT NULL,
  `current_step` int(11) DEFAULT 1,
  `status` enum('pending','in_progress','completed','rejected') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_workflow` (`workflow_id`),
  KEY `idx_document` (`document_id`),
  KEY `idx_status` (`status`),
  KEY `idx_workflow_instances_composite` (`status`,`current_step`),
  CONSTRAINT `dm_workflow_instances_ibfk_1` FOREIGN KEY (`workflow_id`) REFERENCES `dm_workflows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dm_workflow_instances_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `dm_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_workflow_instances`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_workflow_instances` WRITE;
/*!40000 ALTER TABLE `dm_workflow_instances` DISABLE KEYS */;
/*!40000 ALTER TABLE `dm_workflow_instances` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `dm_workflows`
--

DROP TABLE IF EXISTS `dm_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dm_workflows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflow_name` varchar(255) NOT NULL,
  `workflow_type` enum('approval','review','signature','verification') DEFAULT 'approval',
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`steps`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_workflow_type` (`workflow_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dm_workflows`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `dm_workflows` WRITE;
/*!40000 ALTER TABLE `dm_workflows` DISABLE KEYS */;
INSERT INTO `dm_workflows` VALUES
(1,'Document Approval','approval','[{\"step\":1,\"role\":\"manager\",\"action\":\"review\"},{\"step\":2,\"role\":\"admin\",\"action\":\"approve\"}]',1,1,'2026-06-04 18:35:18'),
(2,'Client Signature','signature','[{\"step\":1,\"role\":\"client\",\"action\":\"sign\"},{\"step\":2,\"role\":\"manager\",\"action\":\"verify\"}]',1,1,'2026-06-04 18:35:18'),
(3,'KYC Verification','verification','[{\"step\":1,\"role\":\"compliance\",\"action\":\"verify\"},{\"step\":2,\"role\":\"admin\",\"action\":\"approve\"}]',1,1,'2026-06-04 18:35:18');
/*!40000 ALTER TABLE `dm_workflows` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `document_access_logs`
--

DROP TABLE IF EXISTS `document_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('view','download','edit','share','delete','verify') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `accessed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_access_doc` (`document_id`),
  KEY `idx_access_user` (`user_id`),
  CONSTRAINT `document_access_logs_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_access_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_access_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `document_access_logs` WRITE;
/*!40000 ALTER TABLE `document_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_access_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `document_analyses`
--

DROP TABLE IF EXISTS `document_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_analyses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `analysis_result` text DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_analyses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `document_analyses` WRITE;
/*!40000 ALTER TABLE `document_analyses` DISABLE KEYS */;
INSERT INTO `document_analyses` VALUES
(1,1,'credit_report','sample_report.pdf',NULL,'📊 Credit Score: 720\n✅ Improvement: 45 points\n⚠️ Issues: 2 late payments found','completed','2026-07-28 08:25:36','2026-07-28 08:25:36'),
(2,1,'credit_report','CIBIL_Report.pdf',NULL,'Score: 720. Issues: 2 late payments.','completed','2026-07-29 12:23:43','2026-07-29 12:23:43'),
(3,1,'credit_report','My_CIBIL_Report.pdf',NULL,'CIBIL Score: 680. Issues: 3 late payments. Recommended: Dispute all late payments.','completed','2026-07-29 12:24:07','2026-07-29 12:24:07'),
(4,1,'bank_statement','Bank_Statement_2026.pdf',NULL,'Monthly income: ₹85,000. Monthly expenses: ₹45,000. Savings rate: 47%.','completed','2026-07-29 12:24:07','2026-07-29 12:24:07'),
(5,1,'legal_notice','Legal_Notice_2026.pdf',NULL,'Legal notice from HDFC Bank. Response required within 30 days. Recommended: Consult legal advisor.','completed','2026-07-29 12:24:07','2026-07-29 12:24:07'),
(6,1,'credit_report','CIBIL_Score_Report.pdf',NULL,'CIBIL Score: 710. Status: Good. Recommendations: Maintain low utilization.','completed','2026-07-29 12:24:26','2026-07-29 12:24:26'),
(7,1,'bank_statement','SBI_Statement_2026.pdf',NULL,'Income: ₹75,000. Expenses: ₹55,000. Monthly savings: ₹20,000.','completed','2026-07-29 12:24:26','2026-07-29 12:24:26'),
(8,1,'legal_notice','Court_Notice.pdf',NULL,'Legal notice received. Response deadline: 15 days. Consult legal advisor.','completed','2026-07-29 12:24:26','2026-07-29 12:24:26'),
(9,1,'loan_noc','Loan_NOC_2026.pdf',NULL,'Loan closed. No objection certificate issued. CIBIL should reflect closed status.','completed','2026-07-29 12:24:26','2026-07-29 12:24:26'),
(10,1,'other','Document.pdf',NULL,'General document analyzed. No specific issues found.','completed','2026-07-29 12:24:26','2026-07-29 12:24:26'),
(11,1,'credit_report','New_Report.pdf',NULL,'Your analysis here...','completed','2026-07-29 12:26:32','2026-07-29 12:26:32'),
(12,1,'credit_report','Final_Report.pdf',NULL,'Score: 750. Excellent. No issues found.','completed','2026-07-29 12:26:52','2026-07-29 12:26:52'),
(13,1,'credit_report','document.pdf',NULL,'Your analysis text here...','completed','2026-07-29 12:27:08','2026-07-29 12:27:08'),
(14,1,'credit_report','my_report.pdf',NULL,'Your analysis','completed','2026-07-29 12:27:41','2026-07-29 12:27:41'),
(15,1,'test','test.txt',NULL,'Test analysis','completed','2026-07-29 12:47:33','2026-07-29 12:47:33'),
(16,1,'test','test.txt',NULL,'Test analysis','completed','2026-07-29 12:49:04','2026-07-29 12:49:04'),
(17,1,'test','test.txt',NULL,'Test analysis','completed','2026-07-29 12:52:03','2026-07-29 12:52:03'),
(18,1,'credit_report','test_report.pdf',NULL,'Test analysis result','completed','2026-07-29 16:25:18','2026-07-29 16:25:18'),
(19,1,'credit_report','test_report.pdf',NULL,'Test analysis result','completed','2026-07-29 16:26:20','2026-07-29 16:26:20'),
(20,1,'credit_report','test_report.pdf',NULL,'Test analysis result','completed','2026-07-29 16:30:51','2026-07-29 16:30:51'),
(21,1,'credit_report','console_test.pdf',NULL,'Test analysis from console','completed','2026-07-29 16:34:38','2026-07-29 16:34:38'),
(22,1,'test','check.txt',NULL,'Check analysis','completed','2026-07-29 16:37:59','2026-07-29 16:37:59');
/*!40000 ALTER TABLE `document_analyses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `document_categories`
--

DROP TABLE IF EXISTS `document_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `document_categories` WRITE;
/*!40000 ALTER TABLE `document_categories` DISABLE KEYS */;
INSERT INTO `document_categories` VALUES
(1,'KYC Documents','KYC','Know Your Customer documents - PAN, Aadhaar, etc.',1,'2026-08-01 18:46:11'),
(2,'Legal Documents','LEGAL','Legal agreements, contracts, NDA, etc.',0,'2026-08-01 18:46:11'),
(3,'Credit Reports','CREDIT','Credit bureau reports from CIBIL, Experian, etc.',0,'2026-08-01 18:46:11'),
(4,'Dispute Documents','DISPUTE','Dispute letters, RBI complaints, bank responses',0,'2026-08-01 18:46:11'),
(5,'Invoices','INVOICE','Client invoices and payment receipts',0,'2026-08-01 18:46:11'),
(6,'Agreements','AGREEMENT','Service agreements and contracts with clients',0,'2026-08-01 18:46:11'),
(7,'General','GENERAL','General documents and files',0,'2026-08-01 18:46:11');
/*!40000 ALTER TABLE `document_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `document_sharing`
--

DROP TABLE IF EXISTS `document_sharing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_sharing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `shared_with` int(11) NOT NULL,
  `shared_by` int(11) NOT NULL,
  `permission` enum('view','download','edit') DEFAULT 'view',
  `shared_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `access_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_code` (`access_code`),
  KEY `shared_by` (`shared_by`),
  KEY `idx_sharing_doc` (`document_id`),
  KEY `idx_sharing_user` (`shared_with`),
  CONSTRAINT `document_sharing_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_sharing_ibfk_2` FOREIGN KEY (`shared_with`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_sharing_ibfk_3` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_sharing`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `document_sharing` WRITE;
/*!40000 ALTER TABLE `document_sharing` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_sharing` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `document_templates`
--

DROP TABLE IF EXISTS `document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `template_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `document_templates` WRITE;
/*!40000 ALTER TABLE `document_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `document_versions`
--

DROP TABLE IF EXISTS `document_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` int(11) NOT NULL,
  `version_number` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_versions_doc` (`document_id`),
  CONSTRAINT `document_versions_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_versions_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_versions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `document_versions` WRITE;
/*!40000 ALTER TABLE `document_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_versions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `category` enum('kyc','legal','credit_report','dispute','invoice','agreement','general') DEFAULT 'general',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `file_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','verified','rejected','expired') DEFAULT 'pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_id` (`document_id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_docs_client` (`client_id`),
  KEY `idx_docs_type` (`document_type`),
  KEY `idx_docs_category` (`category`),
  KEY `idx_docs_status` (`status`),
  KEY `idx_docs_uploaded` (`uploaded_at`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_campaigns`
--

DROP TABLE IF EXISTS `email_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(200) NOT NULL,
  `subject_line` varchar(255) DEFAULT NULL,
  `sent_date` date DEFAULT NULL,
  `recipients` int(11) DEFAULT 0,
  `opens` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `unsubscribes` int(11) DEFAULT 0,
  `bounce_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_campaigns`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_campaigns` WRITE;
/*!40000 ALTER TABLE `email_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_campaigns` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_history`
--

DROP TABLE IF EXISTS `email_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `template_key` varchar(100) DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `status` enum('sent','failed','bounced') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipient_email` (`recipient_email`),
  KEY `sent_at` (`sent_at`),
  KEY `template_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_history` WRITE;
/*!40000 ALTER TABLE `email_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_email` varchar(100) DEFAULT NULL,
  `to_email` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(100) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES
(1,'welcome','Welcome to CIBIL Repair','<h2>Welcome {{name}}!</h2><p>Thank you for joining CIBIL Repair. Your account has been created successfully.</p><p><strong>Email:</strong> {{email}}</p><p><strong>Role:</strong> {{role}}</p><a href=\"{{login_link}}\">Click here to login</a>','2026-05-04 09:27:02','2026-08-01 11:59:37'),
(2,'lead_assigned','New Lead Assigned','<h2>New Lead Assigned</h2><p>A new lead has been assigned to you.</p><p><strong>Customer:</strong> {{customer_name}}</p><p><strong>Service:</strong> {{service}}</p><a href=\"{{dashboard_link}}\">View Lead</a>','2026-05-04 09:27:02','2026-08-01 11:59:37'),
(3,'lead_converted','Lead Converted - Commission Earned','<h2>Congratulations! Lead Converted</h2><p>Your lead {{customer_name}} has been converted.</p><p><strong>Commission Earned:</strong> ₹{{commission}}</p><a href=\"{{dashboard_link}}\">View Details</a>','2026-05-04 09:27:02','2026-08-01 11:59:37'),
(4,'payout_processed','Payout Processed','<h2>Payout Processed</h2><p>Your payout request of ₹{{amount}} has been processed.</p><p>Amount will be credited to your bank account within 2-3 business days.</p>','2026-05-04 09:27:02','2026-08-01 11:59:37'),
(5,'ticket_reply','New Reply on Support Ticket','<h2>New Reply on Ticket #{{ticket_no}}</h2><p>{{reply_message}}</p><a href=\"{{ticket_link}}\">View Ticket</a>','2026-05-04 09:27:02','2026-08-01 11:59:37'),
(6,'partner_approved','Partner Application Approved!','<h1>Congratulations, {{name}}!</h1><p>Your partner application has been <strong>approved</strong>.</p><p>You can now access your partner dashboard:</p><p><a href=\"https://cibilrepair.in/partner-dashboard/\">https://cibilrepair.in/partner-dashboard/</a></p><p>Your login credentials have been sent separately.</p><br><p>Best regards,<br>CIBIL Repair Team</p>','2026-08-01 11:59:54','2026-08-01 11:59:54'),
(7,'partner_rejected','Partner Application Update','<h1>Application Update, {{name}}</h1><p>Thank you for your interest in partnering with CIBIL Repair.</p><p>After careful review, we regret to inform you that your application has been <strong>rejected</strong>.</p><p><strong>Reason:</strong> {{reason}}</p><p>You may reapply after 3 months.</p><br><p>Best regards,<br>CIBIL Repair Team</p>','2026-08-01 11:59:54','2026-08-01 11:59:54'),
(8,'followup_reminder','Follow-up Reminder','<p>Dear {{partner_name}},</p><p>This is a reminder about your follow-up with <strong>{{lead_name}}</strong> scheduled for <strong>{{followup_date}}</strong>.</p><p><strong>Notes:</strong> {{followup_notes}}</p><br><p>Best regards,<br>CIBIL Repair Team</p>','2026-08-01 11:59:54','2026-08-01 11:59:54'),
(9,'password_reset','Password Reset Request','<p>Hello {{name}},</p><p>We received a request to reset your password.</p><p>Click the link below to reset your password (valid for 1 hour):</p><p><a href=\"{{reset_link}}\">{{reset_link}}</a></p><p>If you did not request this, please ignore this email.</p><br><p>Best regards,<br>CIBIL Repair Team</p>','2026-08-01 11:59:54','2026-08-01 11:59:54');
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_attendance`
--

DROP TABLE IF EXISTS `employee_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','half_day','work_from_home','on_leave') NOT NULL,
  `late_minutes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_date` (`employee_id`,`attendance_date`),
  KEY `attendance_date` (`attendance_date`),
  KEY `status` (`status`),
  CONSTRAINT `employee_attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_attendance`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_attendance` WRITE;
/*!40000 ALTER TABLE `employee_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_attendance` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `document_type` (`document_type`),
  CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_documents` WRITE;
/*!40000 ALTER TABLE `employee_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_incentives`
--

DROP TABLE IF EXISTS `employee_incentives`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_incentives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `incentive_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `month` varchar(7) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_incentives`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_incentives` WRITE;
/*!40000 ALTER TABLE `employee_incentives` DISABLE KEYS */;
INSERT INTO `employee_incentives` VALUES
(1,5,'Performance Bonus',15000.00,'2025-06','paid','2026-06-05','2026-06-05 16:00:07'),
(2,6,'Referral Bonus',5000.00,'2025-06','pending',NULL,'2026-06-05 16:00:07'),
(3,7,'Quarterly Bonus',25000.00,'2025-06','paid','2026-06-05','2026-06-05 16:00:07');
/*!40000 ALTER TABLE `employee_incentives` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_leave_balances`
--

DROP TABLE IF EXISTS `employee_leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` int(11) DEFAULT 0,
  `used_days` int(11) DEFAULT 0,
  `remaining_days` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_balance` (`employee_id`,`leave_type_id`,`year`),
  KEY `leave_type_id` (`leave_type_id`),
  CONSTRAINT `employee_leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_leave_balances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leave_balances`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_leave_balances` WRITE;
/*!40000 ALTER TABLE `employee_leave_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_leave_balances` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_leave_requests`
--

DROP TABLE IF EXISTS `employee_leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `total_days` decimal(5,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `leave_type_id` (`leave_type_id`),
  CONSTRAINT `employee_leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leave_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_leave_requests` WRITE;
/*!40000 ALTER TABLE `employee_leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_leave_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_leaves`
--

DROP TABLE IF EXISTS `employee_leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('annual','sick','casual','maternity','paternity','comp_off','lop','bereavement') NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` decimal(5,2) NOT NULL,
  `day_type` enum('full','first_half','second_half') DEFAULT 'full',
  `reason` text NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `status` (`status`),
  KEY `from_date` (`from_date`),
  KEY `to_date` (`to_date`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `employee_leaves_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_leaves_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_leaves`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_leaves` WRITE;
/*!40000 ALTER TABLE `employee_leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_leaves` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_payroll`
--

DROP TABLE IF EXISTS `employee_payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic` decimal(12,2) NOT NULL,
  `hra` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `gross_salary` decimal(12,2) NOT NULL,
  `net_salary` decimal(12,2) NOT NULL,
  `status` enum('pending','processed','paid') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_payroll` (`employee_id`,`month`,`year`),
  CONSTRAINT `employee_payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_payroll`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_payroll` WRITE;
/*!40000 ALTER TABLE `employee_payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_payroll` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employee_targets`
--

DROP TABLE IF EXISTS `employee_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `kpi_name` varchar(100) NOT NULL,
  `target_value` decimal(12,2) NOT NULL,
  `achieved_value` decimal(12,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT NULL,
  `period` enum('monthly','quarterly','half_yearly','annual') NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','on_track','achieved','behind','missed') NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `status` (`status`),
  CONSTRAINT `employee_targets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_targets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employee_targets` WRITE;
/*!40000 ALTER TABLE `employee_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_targets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `employee_code` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `nationality` varchar(50) DEFAULT 'Indian',
  `pan_number` varchar(20) DEFAULT NULL,
  `aadhar_number` varchar(20) DEFAULT NULL,
  `passport_number` varchar(20) DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `personal_email` varchar(100) DEFAULT NULL,
  `work_email` varchar(100) DEFAULT NULL,
  `personal_phone` varchar(20) DEFAULT NULL,
  `work_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `reporting_to` int(11) DEFAULT NULL,
  `employment_type` enum('permanent','contract','intern','trainee','consultant') DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `probation_period` int(11) DEFAULT 6,
  `exit_date` date DEFAULT NULL,
  `exit_reason` text DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT NULL,
  `hra` decimal(12,2) DEFAULT NULL,
  `special_allowance` decimal(12,2) DEFAULT NULL,
  `other_allowance` decimal(12,2) DEFAULT NULL,
  `total_ctc` decimal(12,2) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `uan_number` varchar(20) DEFAULT NULL,
  `esi_number` varchar(20) DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `offer_letter_path` varchar(500) DEFAULT NULL,
  `appointment_letter_path` varchar(500) DEFAULT NULL,
  `agreement_path` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','terminated','resigned','on_leave') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  KEY `idx_employee_code` (`employee_code`),
  KEY `idx_department` (`department_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES
(1,NULL,'EMP001','John','Doe','male','1990-05-15','married','Indian',NULL,NULL,NULL,NULL,'john.personal@gmail.com','john.doe@cibilrepair.in','9876543215',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,5,NULL,'permanent','2026-06-02',NULL,6,NULL,NULL,50000.00,25000.00,10000.00,NULL,950000.00,'HDFC Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:04:25','2026-06-02 13:04:25'),
(2,NULL,'EMP002','Priya','Sharma','female','1988-08-20','married','Indian',NULL,NULL,NULL,NULL,'priya.personal@gmail.com','priya.hr@cibilrepair.in','9988776655',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,3,NULL,'permanent','2026-06-02',NULL,6,NULL,NULL,75000.00,37500.00,15000.00,NULL,1450000.00,'ICICI Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:04:41','2026-06-02 13:04:41'),
(3,NULL,'EMP003','Rahul','Gupta','male','1992-03-10','single','Indian',NULL,NULL,NULL,NULL,'rahul.personal@gmail.com','rahul.accounts@cibilrepair.in','8877665544',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,7,NULL,'permanent','2026-06-02',NULL,6,NULL,NULL,45000.00,22500.00,9000.00,NULL,850000.00,'SBI Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:05:03','2026-06-02 13:05:03'),
(4,NULL,'EMP004','Sneha','Reddy','female','1995-07-25','single','Indian',NULL,NULL,NULL,NULL,'sneha.personal@gmail.com','sneha.support@cibilrepair.in','7766554433',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,9,NULL,'permanent','2026-06-02',NULL,6,NULL,NULL,35000.00,17500.00,7000.00,NULL,650000.00,'Axis Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:10:21','2026-06-02 13:10:21'),
(5,NULL,'EMP005','Amit','Verma','male','1993-11-12','single','Indian',NULL,NULL,NULL,NULL,'amit.personal@gmail.com','amit.sales@cibilrepair.in','6655443322',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,8,NULL,'permanent','2026-06-02',NULL,6,NULL,NULL,40000.00,20000.00,8000.00,NULL,750000.00,'HDFC Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:10:32','2026-06-02 13:10:32'),
(6,NULL,'CR-001','Arjun','Sharma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'arjun@cibilrepair.in','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,1,1,NULL,'permanent','2022-03-15',NULL,6,NULL,NULL,45000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(7,NULL,'CR-002','Priya','Mehta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'priya@cibilrepair.in','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,2,2,NULL,'permanent','2020-01-10',NULL,6,NULL,NULL,95000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(8,NULL,'CR-003','Rahul','Verma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rahul@cibilrepair.in','9876543212',NULL,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi',NULL,3,3,NULL,'permanent','2023-06-01',NULL,6,NULL,NULL,38000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(9,NULL,'CR-004','Sunita','Patel','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'sunita@cibilrepair.in','9876543213',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,4,4,NULL,'permanent','2021-09-15',NULL,6,NULL,NULL,52000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(10,NULL,'CR-005','Amit','Kumar','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'amit@cibilrepair.in','9876543214',NULL,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka',NULL,5,5,NULL,'permanent','2022-11-01',NULL,6,NULL,NULL,72000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(11,NULL,'CR-006','Neha','Singh','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'neha@cibilrepair.in','9876543215',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,6,6,NULL,'permanent','2023-02-14',NULL,6,NULL,NULL,30000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(12,NULL,'CR-007','Vikram','Nair','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'vikram@cibilrepair.in','9876543216',NULL,NULL,NULL,NULL,NULL,NULL,'Chennai','Tamil Nadu',NULL,7,7,NULL,'contract','2023-08-01',NULL,6,NULL,NULL,55000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(13,NULL,'CR-008','Pooja','Gupta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'pooja@cibilrepair.in','9876543217',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,1,8,NULL,'permanent','2024-01-15',NULL,6,NULL,NULL,25000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(14,NULL,'CR-009','Rajesh','Iyer','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rajesh@cibilrepair.in','9876543218',NULL,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana',NULL,8,9,NULL,'permanent','2022-05-20',NULL,6,NULL,NULL,35000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(15,NULL,'CR-010','Ankita','Joshi','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'ankita@cibilrepair.in','9876543219',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,9,10,NULL,'permanent','2023-10-01',NULL,6,NULL,NULL,28000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
(16,NULL,'EMP-001','Admin','User','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'admin@cibilrepair.in','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,1,NULL,'permanent','2020-01-01',NULL,6,NULL,NULL,100000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:03:03'),
(17,NULL,'EMP-002','Priya','Mehta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'priya@cibilrepair.in','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,NULL,NULL,'permanent','2020-01-10',NULL,6,NULL,NULL,95000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
(18,NULL,'EMP-003','Rahul','Verma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rahul@cibilrepair.in','9876543212',NULL,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi',NULL,NULL,NULL,NULL,'permanent','2023-06-01',NULL,6,NULL,NULL,38000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
(19,NULL,'EMP-004','Sunita','Patel','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'sunita@cibilrepair.in','9876543213',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,NULL,NULL,'permanent','2021-09-15',NULL,6,NULL,NULL,52000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
(20,NULL,'EMP-005','Arjun','Sharma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'arjun@cibilrepair.in','9876543214',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,NULL,NULL,'permanent','2022-03-15',NULL,6,NULL,NULL,45000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
(21,NULL,'EMP-006','Amit','Kumar','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'amit@cibilrepair.in','9876543215',NULL,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka',NULL,NULL,NULL,NULL,'permanent','2022-11-01',NULL,6,NULL,NULL,72000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
(22,NULL,'EMP-007','Neha','Singh','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'neha@cibilrepair.in','9876543216',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,6,NULL,NULL,'permanent','2023-02-14',NULL,6,NULL,NULL,30000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:03:03','2026-06-03 19:03:03'),
(23,NULL,'EMP-008','Vikram','Nair','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'vikram@cibilrepair.in','9876543217',NULL,NULL,NULL,NULL,NULL,NULL,'Chennai','Tamil Nadu',NULL,3,NULL,NULL,'contract','2023-08-01',NULL,6,NULL,NULL,55000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:03:03','2026-06-03 19:03:03'),
(24,NULL,'EMP-009','Pooja','Gupta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'pooja@cibilrepair.in','9876543218',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,2,NULL,NULL,'permanent','2024-01-15',NULL,6,NULL,NULL,25000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','2026-06-03 19:03:03','2026-06-03 19:03:03'),
(25,NULL,'EMP-010','Rajesh','Iyer','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rajesh@cibilrepair.in','9876543219',NULL,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana',NULL,6,NULL,NULL,'permanent','2022-05-20',NULL,6,NULL,NULL,35000.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:03:03','2026-06-03 19:03:03'),
(33,8,'EMP-8','Your Name','',NULL,NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'info@cibilrepair.in',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-02',NULL,6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-08-02 09:19:56','2026-08-02 09:19:56');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `executive_reports`
--

DROP TABLE IF EXISTS `executive_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `executive_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(200) NOT NULL,
  `period` varchar(50) DEFAULT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `generated_at` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `executive_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `executive_reports` WRITE;
/*!40000 ALTER TABLE `executive_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `executive_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `budget_amount` decimal(15,2) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES
(1,'Salary','Employee salaries',NULL,9),
(2,'Rent','Office rent',NULL,10),
(3,'Electricity','Electricity bills',NULL,11),
(4,'Internet','Internet and broadband',NULL,11),
(5,'Marketing','Marketing expenses',NULL,9),
(6,'Software','Software subscriptions',NULL,9);
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `expense_type` enum('operational','capital','administrative','marketing','salary','utilities') DEFAULT 'operational',
  `payment_method` varchar(50) DEFAULT 'cash',
  `vendor_name` varchar(150) DEFAULT NULL,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `receipt_url` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `gst_amount` decimal(12,2) DEFAULT 0.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `total_with_gst` decimal(12,2) DEFAULT 0.00,
  `is_gst_applicable` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_expenses_date` (`date`),
  KEY `idx_expenses_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES
(1,'Marketing','Google Ads Campaign',15000.00,'2026-07-29','operational','UPI',NULL,NULL,NULL,NULL,NULL,NULL,'paid',18.00,0.00,0.00,0.00,0.00,0,'2026-07-19 07:51:56'),
(2,'Salary','Staff salary April',180000.00,'2025-04-05','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending',18.00,0.00,0.00,0.00,0.00,0,'2026-07-19 07:51:56'),
(3,'Marketing','Google Ads campaign',15000.00,'2025-04-10','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending',18.00,0.00,0.00,0.00,0.00,0,'2026-07-19 07:51:56'),
(4,'Utilities','Electricity + Internet',8000.00,'2025-04-12','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending',18.00,0.00,0.00,0.00,0.00,0,'2026-07-19 07:51:56'),
(6,'Marketing','Google Ads Campaign',15000.00,'2025-07-26','marketing','credit_card','Google Ads',0,'0',NULL,'',NULL,'paid',18.00,0.00,0.00,0.00,0.00,0,'2026-07-25 23:34:16');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES
(1,'What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:07:44'),
(2,'How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:07:44'),
(3,'Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:07:44'),
(4,'What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:07:44'),
(5,'What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:09:04'),
(6,'How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:09:04'),
(7,'Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:09:04'),
(8,'What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:09:04'),
(9,'What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:10:42'),
(10,'How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:10:42'),
(11,'Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:10:42'),
(12,'What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:10:42'),
(13,'What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:12:26'),
(14,'How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:12:26'),
(15,'Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:12:26'),
(16,'What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:12:26');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `followup_templates`
--

DROP TABLE IF EXISTS `followup_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `followup_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `channel` enum('whatsapp','email','sms','all') DEFAULT 'all',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `days_before` int(11) DEFAULT 1,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followup_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `followup_templates` WRITE;
/*!40000 ALTER TABLE `followup_templates` DISABLE KEYS */;
INSERT INTO `followup_templates` VALUES
(1,'lead_new','New Lead - Follow-up Reminder','🔔 *Follow-up Reminder*\n\nLead: {{customer_name}}\nService: {{service}}\n\nPlease contact this lead today to discuss requirements.\n\nLead ID: #{{lead_id}}\n\n_CIBIL Repair_','all','high',1,1,'2026-05-09 08:21:55'),
(2,'lead_contacted','Contacted Lead - Follow-up','📞 *Follow-up Reminder*\n\nCustomer: {{customer_name}}\nYou had previously contacted this lead on {{last_contact_date}}.\n\nPlease follow up to check their decision.\n\nLead ID: #{{lead_id}}','all','medium',2,1,'2026-05-09 08:21:55'),
(3,'lead_conversion','High Potential Lead - Urgent Follow-up','🔥 *URGENT: High Potential Lead*\n\nCustomer: {{customer_name}}\nService: {{service}}\n\nThis lead has high conversion potential. Please follow up today!\n\nLead ID: #{{lead_id}}','all','urgent',1,1,'2026-05-09 08:21:55'),
(4,'lead_inactive','Inactive Lead - Re-engagement','📌 *Re-engagement Reminder*\n\nLead: {{customer_name}}\nLast contact: {{last_contact_date}}\n\nNo activity for {{inactive_days}} days. Try to re-engage this lead.\n\nLead ID: #{{lead_id}}','all','low',5,1,'2026-05-09 08:21:55'),
(5,'lead_won','Won Lead - No Follow-up Needed','✅ *Lead Converted*\n\nCustomer: {{customer_name}}\nThis lead has been converted. No further follow-up needed.\n\nCommission: ₹{{commission_amount}}','all','low',0,1,'2026-05-09 08:21:55');
/*!40000 ALTER TABLE `followup_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `followups`
--

DROP TABLE IF EXISTS `followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `follow_up_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','missed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_lead_id` (`lead_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `followups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `followups` WRITE;
/*!40000 ALTER TABLE `followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `followups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `franchise_leads`
--

DROP TABLE IF EXISTS `franchise_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `franchise_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `status` enum('new','contacted','converted','lost') DEFAULT 'new',
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `franchise_leads_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `franchise_partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `franchise_leads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `franchise_leads` WRITE;
/*!40000 ALTER TABLE `franchise_leads` DISABLE KEYS */;
INSERT INTO `franchise_leads` VALUES
(1,1,'Rajesh Kumar','9876543210',NULL,'Written Off Clearance',NULL,'converted',1500.00,'2026-05-02 20:18:54','2026-05-02 20:18:54'),
(2,1,'Priya Sharma','9876543211',NULL,'Settled Clearance',NULL,'contacted',0.00,'2026-05-02 20:18:54','2026-05-02 20:18:54'),
(3,1,'Amit Verma','9876543212',NULL,'Profile Correction',NULL,'new',0.00,'2026-05-02 20:18:54','2026-05-02 20:18:54'),
(4,1,'Rajesh Kumar','9876543210',NULL,'Written Off Clearance',NULL,'converted',1500.00,'2026-05-02 20:19:39','2026-05-02 20:19:39'),
(5,1,'Priya Sharma','9876543211',NULL,'Settled Clearance',NULL,'contacted',0.00,'2026-05-02 20:19:39','2026-05-02 20:19:39'),
(6,1,'Amit Verma','9876543212',NULL,'Profile Correction',NULL,'new',0.00,'2026-05-02 20:19:39','2026-05-02 20:19:39');
/*!40000 ALTER TABLE `franchise_leads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `franchise_partners`
--

DROP TABLE IF EXISTS `franchise_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `franchise_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `store_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `total_leads` int(11) DEFAULT 0,
  `total_converted` int(11) DEFAULT 0,
  `total_commission` decimal(10,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `franchise_partners`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `franchise_partners` WRITE;
/*!40000 ALTER TABLE `franchise_partners` DISABLE KEYS */;
INSERT INTO `franchise_partners` VALUES
(1,NULL,'Demo Franchise','franchise@cibilrepair.in','c280f51fe732b536823c7cda276b6454','9876543210','Delhi Franchise',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 20:07:31'),
(4,NULL,'ABC Franchise','abc@franchise.com','e99a18c428cb38d5f260853678922e03','9876543211','Mumbai Franchise',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 20:34:46'),
(5,NULL,'XYZ Partner','xyz@partner.com','613d3b9c91e9445abaeca02f2342e5a6','9876543212','Bangalore Franchise',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 20:34:46'),
(6,NULL,'Rahul Franchise','rahul@franchise.com','2acb7811397a5c3bea8cba57b0388b79','9988776655','Bangalore Franchise',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 20:35:06'),
(7,NULL,'Priya Partner','priya@partner.com','48467d2cc726e8847fbc51f5b0bdc1d1','9988776644','Chennai Franchise',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 20:35:06'),
(8,NULL,'Amit Franchise','amit@franchise.com','d2b3f63948406cb893544cee035531d3','9988776633','Kolkata Franchise',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 20:35:06'),
(9,NULL,'Rajesh Kumar','rajesh@franchise.com','bf44e33d9745e04551770c7a5a6cdb3b','9876543210','Delhi Main Franchise','Connaught Place, New Delhi','HDFC Bank','12345678901234','HDFC0001234','Rajesh Kumar',0,0,0.00,0.00,'active','2026-05-02 20:37:07'),
(15,NULL,'Rajesh Kumar','rajesh.new@franchise.com','bf44e33d9745e04551770c7a5a6cdb3b','9876543210','Delhi Main Franchise','Connaught Place, New Delhi','HDFC Bank','12345678901234','HDFC0001234','Rajesh Kumar',0,0,0.00,0.00,'active','2026-05-02 20:39:42'),
(16,NULL,'Partner User','partner@cibilrepair.in','3c0d9364bee6c8e4e71a2aecdc6cf57f','9876543210','Main Store',NULL,NULL,NULL,NULL,NULL,0,0,0.00,0.00,'active','2026-05-02 21:01:03');
/*!40000 ALTER TABLE `franchise_partners` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `franchises`
--

DROP TABLE IF EXISTS `franchises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `franchises` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `franchise_type` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `gst_number` varchar(15) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_franchises_status` (`status`),
  KEY `idx_franchises_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `franchises`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `franchises` WRITE;
/*!40000 ALTER TABLE `franchises` DISABLE KEYS */;
/*!40000 ALTER TABLE `franchises` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `fraud_alerts`
--

DROP TABLE IF EXISTS `fraud_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fraud_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `alert_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('new','investigating','confirmed','false_alarm','resolved') DEFAULT 'new',
  `detected_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resolved_by` (`resolved_by`),
  KEY `idx_alerts_client` (`client_id`),
  KEY `idx_alerts_status` (`status`),
  CONSTRAINT `fraud_alerts_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fraud_alerts_ibfk_2` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fraud_alerts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `fraud_alerts` WRITE;
/*!40000 ALTER TABLE `fraud_alerts` DISABLE KEYS */;
INSERT INTO `fraud_alerts` VALUES
(1,4,'Suspicious Transaction','Multiple high-value transactions detected in short period','critical','investigating','2026-07-30 18:10:32',NULL,NULL,NULL),
(2,2,'Identity Mismatch','IP address mismatch with location','high','new','2026-07-31 18:10:32',NULL,NULL,NULL),
(3,5,'Unusual Pattern','Unusual credit report access pattern','medium','investigating','2026-07-29 18:10:32',NULL,NULL,NULL),
(4,4,'Suspicious Transaction','Multiple high-value transactions detected in short period','critical','investigating','2026-07-30 18:43:24',NULL,NULL,NULL),
(5,2,'Identity Mismatch','IP address mismatch with location','high','new','2026-07-31 18:43:24',NULL,NULL,NULL),
(6,5,'Unusual Pattern','Unusual credit report access pattern','medium','investigating','2026-07-29 18:43:24',NULL,NULL,NULL),
(7,4,'Suspicious Transaction','Multiple high-value transactions detected in short period','critical','investigating','2026-07-30 18:44:17',NULL,NULL,NULL),
(8,2,'Identity Mismatch','IP address mismatch with location','high','new','2026-07-31 18:44:17',NULL,NULL,NULL),
(9,5,'Unusual Pattern','Unusual credit report access pattern','medium','investigating','2026-07-29 18:44:17',NULL,NULL,NULL),
(10,3,'Multiple Login Attempts','Multiple failed login attempts from different IPs','high','new','2026-08-01 06:44:17',NULL,NULL,NULL);
/*!40000 ALTER TABLE `fraud_alerts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `grievances`
--

DROP TABLE IF EXISTS `grievances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grievances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `complaint_id` varchar(50) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `complaint` text NOT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `complaint_id` (`complaint_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grievances`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `grievances` WRITE;
/*!40000 ALTER TABLE `grievances` DISABLE KEYS */;
/*!40000 ALTER TABLE `grievances` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `gst_returns`
--

DROP TABLE IF EXISTS `gst_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gst_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(7) NOT NULL,
  `taxable_value` decimal(15,2) DEFAULT 0.00,
  `cgst` decimal(15,2) DEFAULT 0.00,
  `sgst` decimal(15,2) DEFAULT 0.00,
  `igst` decimal(15,2) DEFAULT 0.00,
  `total_tax` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','filed','pending') DEFAULT 'draft',
  `filed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_period` (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gst_returns`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `gst_returns` WRITE;
/*!40000 ALTER TABLE `gst_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `gst_returns` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `holiday_type` enum('public','company','festival','national') DEFAULT 'public',
  `description` text DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_holiday_date` (`holiday_date`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES
(1,'2024-01-26','Republic Day','national',NULL,2024),
(2,'2024-08-15','Independence Day','national',NULL,2024),
(3,'2024-10-02','Gandhi Jayanti','national',NULL,2024),
(4,'2024-12-25','Christmas','public',NULL,2024),
(5,'2024-10-12','Dussehra','festival',NULL,2024),
(6,'2024-11-14','Diwali','festival',NULL,2024);
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `hr_tickets`
--

DROP TABLE IF EXISTS `hr_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(20) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `employee_id` (`employee_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `hr_tickets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hr_tickets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `hr_tickets` WRITE;
/*!40000 ALTER TABLE `hr_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `integrations_config`
--

DROP TABLE IF EXISTS `integrations_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('payment','sms','whatsapp','email') DEFAULT 'payment',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `name_2` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `integrations_config`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `integrations_config` WRITE;
/*!40000 ALTER TABLE `integrations_config` DISABLE KEYS */;
INSERT INTO `integrations_config` VALUES
(1,'razorpay_key_id','','payment',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(2,'razorpay_key_secret','','payment',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(3,'stripe_public_key','','payment',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(4,'stripe_secret_key','','payment',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(5,'paypal_client_id','','payment',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(6,'paypal_client_secret','','payment',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(7,'twilio_account_sid','','sms',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(8,'twilio_auth_token','','sms',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(9,'twilio_phone_number','','sms',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(10,'msg91_auth_key','','sms',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(11,'msg91_sender_id','','sms',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(12,'msg91_route','4','sms',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(13,'whatsapp_phone_number_id','','whatsapp',1,'2026-08-01 11:58:20','2026-08-01 11:58:20'),
(14,'whatsapp_access_token','','whatsapp',1,'2026-08-01 11:58:20','2026-08-01 11:58:20');
/*!40000 ALTER TABLE `integrations_config` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `package_name` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `gst` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `invoice_date` date DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES
(1,'INV-001',1,'Premium Package',25000.00,4500.00,29500.00,'paid','2026-06-05',NULL,'2026-06-05 15:57:53'),
(2,'INV-002',2,'Basic Package',15000.00,2700.00,17700.00,'paid','2026-06-05',NULL,'2026-06-05 15:57:53'),
(3,'INV-003',1,'Corporate Package',50000.00,9000.00,59000.00,'pending','2026-06-05',NULL,'2026-06-05 15:57:53');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_api_logs`
--

DROP TABLE IF EXISTS `it_api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_api_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `response_time_ms` int(11) DEFAULT 0,
  `status_code` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_size` int(11) DEFAULT 0,
  `response_size` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_endpoint` (`endpoint`),
  KEY `idx_status_code` (`status_code`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_response_time` (`response_time_ms`),
  KEY `idx_api_logs_composite` (`created_at`,`status_code`,`response_time_ms`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_api_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_api_logs` WRITE;
/*!40000 ALTER TABLE `it_api_logs` DISABLE KEYS */;
INSERT INTO `it_api_logs` VALUES
(1,'/api/auth/login','POST',125,200,NULL,'192.168.1.100',0,0,NULL,'2026-06-04 17:50:50'),
(2,'/api/clients/get','GET',89,200,NULL,'192.168.1.101',0,0,NULL,'2026-06-04 17:50:50'),
(3,'/api/dashboard/stats','GET',234,200,NULL,'192.168.1.102',0,0,NULL,'2026-06-04 17:50:50'),
(4,'/api/reports/export','POST',567,500,NULL,'192.168.1.103',0,0,NULL,'2026-06-04 17:50:50'),
(5,'/api/agreements/create','POST',178,201,NULL,'192.168.1.100',0,0,NULL,'2026-06-04 17:50:50');
/*!40000 ALTER TABLE `it_api_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_api_performance_summary`
--

DROP TABLE IF EXISTS `it_api_performance_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_api_performance_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `total_requests` int(11) DEFAULT 0,
  `avg_response_time_ms` decimal(10,2) DEFAULT 0.00,
  `max_response_time_ms` int(11) DEFAULT 0,
  `min_response_time_ms` int(11) DEFAULT 0,
  `error_count` int(11) DEFAULT 0,
  `success_rate` decimal(5,2) DEFAULT 100.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_endpoint_date` (`endpoint`,`date`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_api_performance_summary`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_api_performance_summary` WRITE;
/*!40000 ALTER TABLE `it_api_performance_summary` DISABLE KEYS */;
/*!40000 ALTER TABLE `it_api_performance_summary` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_backup_history`
--

DROP TABLE IF EXISTS `it_backup_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_backup_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(255) NOT NULL,
  `backup_type` enum('full','incremental','daily','weekly') DEFAULT 'daily',
  `backup_size_mb` decimal(10,2) DEFAULT 0.00,
  `backup_path` varchar(500) NOT NULL,
  `status` enum('success','failed','in_progress') DEFAULT 'in_progress',
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_by` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_started_at` (`started_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_backup_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_backup_history` WRITE;
/*!40000 ALTER TABLE `it_backup_history` DISABLE KEYS */;
INSERT INTO `it_backup_history` VALUES
(1,'Full_Backup_Dec_2024','full',2048.50,'/backups/full_backup_20241201.sql','success','2026-06-03 17:50:50','2026-06-03 19:50:50',NULL,'system'),
(2,'Daily_Backup_Dec_2024','daily',156.25,'/backups/daily_backup_20241202.sql','success','2026-06-04 05:50:50','2026-06-04 06:20:50',NULL,'system');
/*!40000 ALTER TABLE `it_backup_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_communication_logs`
--

DROP TABLE IF EXISTS `it_communication_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_communication_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `communication_type` enum('email','sms','whatsapp','notification') NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `status` enum('sent','failed','pending','delivered') DEFAULT 'pending',
  `response_time_ms` int(11) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_communication_type` (`communication_type`),
  KEY `idx_sent_at` (`sent_at`),
  KEY `idx_communication_status` (`status`,`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_communication_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_communication_logs` WRITE;
/*!40000 ALTER TABLE `it_communication_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `it_communication_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_failed_logins`
--

DROP TABLE IF EXISTS `it_failed_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_failed_logins` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `attempt_count` int(11) DEFAULT 1,
  `attempted_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_username` (`username`),
  KEY `idx_attempted_at` (`attempted_at`),
  KEY `idx_failed_logins_composite` (`attempted_at`,`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_failed_logins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_failed_logins` WRITE;
/*!40000 ALTER TABLE `it_failed_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `it_failed_logins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_slow_queries`
--

DROP TABLE IF EXISTS `it_slow_queries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_slow_queries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `query_text` text NOT NULL,
  `query_time_ms` decimal(10,2) NOT NULL,
  `lock_time_ms` decimal(10,2) DEFAULT 0.00,
  `rows_examined` bigint(20) DEFAULT 0,
  `rows_sent` bigint(20) DEFAULT 0,
  `user_host` varchar(100) DEFAULT NULL,
  `database_name` varchar(100) DEFAULT NULL,
  `logged_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_query_time` (`query_time_ms`),
  KEY `idx_logged_at` (`logged_at`),
  KEY `idx_slow_queries_time` (`query_time_ms`,`logged_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_slow_queries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_slow_queries` WRITE;
/*!40000 ALTER TABLE `it_slow_queries` DISABLE KEYS */;
INSERT INTO `it_slow_queries` VALUES
(1,'SELECT * FROM clients c LEFT JOIN agreements a ON c.id = a.client_id WHERE c.created_at > \"2024-01-01\"',3450.50,0.00,125000,2340,NULL,NULL,'2026-06-04 17:50:50'),
(2,'SELECT * FROM audit_logs WHERE created_at BETWEEN \"2024-01-01\" AND \"2024-12-31\"',2890.25,0.00,89000,1200,NULL,NULL,'2026-06-04 17:50:50');
/*!40000 ALTER TABLE `it_slow_queries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_system_alerts`
--

DROP TABLE IF EXISTS `it_system_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_name` varchar(100) NOT NULL,
  `alert_type` enum('cpu','memory','disk','api_response','failed_login','backup') NOT NULL,
  `threshold_value` decimal(10,2) NOT NULL,
  `current_value` decimal(10,2) DEFAULT 0.00,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `last_triggered` datetime DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_system_alerts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_system_alerts` WRITE;
/*!40000 ALTER TABLE `it_system_alerts` DISABLE KEYS */;
INSERT INTO `it_system_alerts` VALUES
(1,'High CPU Usage','cpu',80.00,0.00,'high',1,NULL,0,'2026-06-04 17:50:50','2026-06-04 17:50:50'),
(2,'High Memory Usage','memory',85.00,0.00,'high',1,NULL,0,'2026-06-04 17:50:50','2026-06-04 17:50:50'),
(3,'Low Disk Space','disk',90.00,0.00,'critical',1,NULL,0,'2026-06-04 17:50:50','2026-06-04 17:50:50'),
(4,'Slow API Response','api_response',3000.00,0.00,'medium',1,NULL,0,'2026-06-04 17:50:50','2026-06-04 17:50:50'),
(5,'Multiple Failed Logins','failed_login',10.00,0.00,'high',1,NULL,0,'2026-06-04 17:50:50','2026-06-04 17:50:50'),
(6,'Backup Failed','backup',0.00,0.00,'critical',1,NULL,0,'2026-06-04 17:50:50','2026-06-04 17:50:50');
/*!40000 ALTER TABLE `it_system_alerts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `it_system_health`
--

DROP TABLE IF EXISTS `it_system_health`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_system_health` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(100) NOT NULL,
  `cpu_usage` decimal(5,2) DEFAULT 0.00,
  `memory_usage` decimal(5,2) DEFAULT 0.00,
  `disk_usage` decimal(5,2) DEFAULT 0.00,
  `disk_free_space` bigint(20) DEFAULT 0,
  `disk_total_space` bigint(20) DEFAULT 0,
  `network_in` bigint(20) DEFAULT 0,
  `network_out` bigint(20) DEFAULT 0,
  `active_connections` int(11) DEFAULT 0,
  `uptime_seconds` bigint(20) DEFAULT 0,
  `logged_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logged_at` (`logged_at`),
  KEY `idx_server_name` (`server_name`),
  KEY `idx_health_composite` (`logged_at`,`server_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_system_health`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `it_system_health` WRITE;
/*!40000 ALTER TABLE `it_system_health` DISABLE KEYS */;
INSERT INTO `it_system_health` VALUES
(1,'CRM-Main-Server',45.50,62.30,58.70,0,0,0,0,0,864000,'2026-06-04 17:50:50'),
(2,'CRM-DB-Server',32.10,71.20,45.30,0,0,0,0,0,864000,'2026-06-04 17:50:50'),
(3,'CRM-API-Server',51.20,55.80,32.10,0,0,0,0,0,432000,'2026-06-04 17:50:50');
/*!40000 ALTER TABLE `it_system_health` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_no` varchar(50) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT NULL,
  `total_credit` decimal(15,2) DEFAULT NULL,
  `status` enum('draft','posted','cancelled') DEFAULT 'draft',
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `journal_no` (`journal_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` VALUES
(1,'JE001','2026-06-02',NULL,'Salary payment',50000.00,50000.00,'posted',NULL,NULL,NULL,'2026-06-02 13:03:29');
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `knowledge_base`
--

DROP TABLE IF EXISTS `knowledge_base`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `knowledge_base` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `views` int(11) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `not_helpful_count` int(11) DEFAULT 0,
  `status` enum('draft','published') DEFAULT 'published',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `knowledge_base`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `knowledge_base` WRITE;
/*!40000 ALTER TABLE `knowledge_base` DISABLE KEYS */;
/*!40000 ALTER TABLE `knowledge_base` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `kyc_records`
--

DROP TABLE IF EXISTS `kyc_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verification_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `submitted_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_kyc_client` (`client_id`),
  KEY `idx_kyc_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kyc_records`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `kyc_records` WRITE;
/*!40000 ALTER TABLE `kyc_records` DISABLE KEYS */;
INSERT INTO `kyc_records` VALUES
(1,1,NULL,NULL,'verified',NULL,NULL,NULL,NULL,'2026-08-01 17:19:38'),
(2,2,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-08-01 17:19:38'),
(3,3,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-08-01 17:19:38'),
(4,2,NULL,NULL,'verified',NULL,NULL,NULL,NULL,'2026-08-01 17:23:09'),
(5,3,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-08-01 17:23:09'),
(6,4,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-08-01 17:23:09'),
(7,2,NULL,NULL,'verified',NULL,NULL,NULL,NULL,'2026-08-01 17:23:27'),
(8,3,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-08-01 17:23:27'),
(9,4,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-08-01 17:23:27'),
(10,2,'ABCDE1234F','1234-5678-9012','verified','All documents verified',1,NULL,NULL,'2026-08-02 08:34:10'),
(11,3,'FGHIJ5678G','2345-6789-0123','pending',NULL,NULL,NULL,NULL,'2026-08-02 08:34:10'),
(12,4,'KLMNO9012H','3456-7890-1234','verified','Verified with PAN and Aadhaar',1,NULL,NULL,'2026-08-02 08:34:10'),
(13,5,'PQRST3456I','4567-8901-2345','rejected','PAN card blurry - resubmit',1,NULL,NULL,'2026-08-02 08:34:10'),
(14,6,'UVWXY7890J','5678-9012-3456','pending',NULL,NULL,NULL,NULL,'2026-08-02 08:34:10'),
(15,2,'ABCDE1234F','1234-5678-9012','verified','All documents verified',8,NULL,NULL,'2026-08-02 08:35:19'),
(16,3,'FGHIJ5678G','2345-6789-0123','pending',NULL,NULL,NULL,NULL,'2026-08-02 08:35:19'),
(17,4,'KLMNO9012H','3456-7890-1234','verified','Verified with PAN and Aadhaar',8,NULL,NULL,'2026-08-02 08:35:19'),
(18,5,'PQRST3456I','4567-8901-2345','rejected','PAN card blurry - resubmit',8,NULL,NULL,'2026-08-02 08:35:19'),
(19,6,'UVWXY7890J','5678-9012-3456','pending',NULL,NULL,NULL,NULL,'2026-08-02 08:35:19');
/*!40000 ALTER TABLE `kyc_records` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `lawyers`
--

DROP TABLE IF EXISTS `lawyers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lawyers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `firm_name` varchar(150) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `bar_council_id` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lawyers_status` (`status`),
  KEY `idx_lawyers_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lawyers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `lawyers` WRITE;
/*!40000 ALTER TABLE `lawyers` DISABLE KEYS */;
/*!40000 ALTER TABLE `lawyers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `lead_followups`
--

DROP TABLE IF EXISTS `lead_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `followup_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_date` (`followup_date`),
  CONSTRAINT `lead_followups_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_followups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `lead_followups` WRITE;
/*!40000 ALTER TABLE `lead_followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `lead_followups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `lead_sources`
--

DROP TABLE IF EXISTS `lead_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_name` varchar(100) NOT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `cost_per_lead` decimal(10,2) DEFAULT NULL,
  `conversion_rate` decimal(5,2) DEFAULT NULL,
  `daily_budget` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_sources`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `lead_sources` WRITE;
/*!40000 ALTER TABLE `lead_sources` DISABLE KEYS */;
INSERT INTO `lead_sources` VALUES
(1,'Google Ads','PPC',NULL,3.50,NULL,1,'2026-06-04 17:38:27'),
(2,'Facebook Ads','Social',NULL,2.80,NULL,1,'2026-06-04 17:38:27'),
(3,'Instagram','Social',NULL,2.10,NULL,1,'2026-06-04 17:38:27'),
(4,'LinkedIn','Social',NULL,4.20,NULL,1,'2026-06-04 17:38:27'),
(5,'Email Marketing','Email',NULL,5.00,NULL,1,'2026-06-04 17:38:27'),
(6,'Referral Program','Referral',NULL,8.50,NULL,1,'2026-06-04 17:38:27'),
(7,'Organic Search','SEO',NULL,2.00,NULL,1,'2026-06-04 17:38:27'),
(8,'Direct Traffic','Direct',NULL,1.50,NULL,1,'2026-06-04 17:38:27'),
(9,'Content Marketing','Content',NULL,3.00,NULL,1,'2026-06-04 17:38:27'),
(10,'WhatsApp','Messaging',NULL,6.50,NULL,1,'2026-06-04 17:38:27');
/*!40000 ALTER TABLE `lead_sources` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `status` enum('new','contacted','qualified','nurturing','disqualified','converted','approved','active','lost','rejected','cancelled','pending') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `aadhar` varchar(12) DEFAULT NULL,
  `pan` varchar(10) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `ip_address` varchar(45) DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `assigned_to` int(11) DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'Website',
  `notes` text DEFAULT NULL,
  `issue` varchar(255) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'low',
  `source_type` enum('direct','referral','connector') DEFAULT 'direct',
  `source_id` int(11) DEFAULT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `source_commission_rate` decimal(5,2) DEFAULT 0.00,
  `source_commission_amount` decimal(10,2) DEFAULT 0.00,
  `conversion_value` decimal(10,2) DEFAULT 0.00,
  `qualified_at` datetime DEFAULT NULL,
  `nurturing_at` datetime DEFAULT NULL,
  `lead_score` int(11) DEFAULT 0,
  `follow_up_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_leads_partner` (`partner_id`),
  KEY `idx_leads_status_created` (`status`,`created_at`),
  KEY `idx_source` (`source_type`,`source_id`),
  KEY `idx_service_type` (`service_type`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_converted_at` (`converted_at`),
  KEY `idx_partner_status` (`partner_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES
(31,16,'Rajesh Kumar','9876543001','rajesh@example.com','Written Off Clearance',NULL,'converted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Written Off','Website',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(32,16,'Priya Sharma','9876543002','priya@example.com','Settled Clearance',NULL,'converted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Settled','Referral',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(33,16,'Amit Singh','9876543003','amit@example.com','Suit Filed Clearance',NULL,'converted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Suit Filed','Website',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(34,16,'Sneha Patel','9876543004','sneha@example.com','Credit Report Analysis',NULL,'new','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Credit Report','Social Media',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(35,16,'Vikram Reddy','9876543005','vikram@example.com','Profile Correction',NULL,'contacted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Profile Correction','Referral',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(36,16,'Neha Gupta','9876543006','neha@example.com','Wrong Entry Clearance',NULL,'new','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Wrong Entry','Website',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(37,16,'Rahul Verma','9876543007','rahulv@example.com','Written Off Clearance',NULL,'contacted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Written Off','Call',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(38,16,'Kavita Nair','9876543008','kavita@example.com','Settled Clearance',NULL,'converted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Settled','Website',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(39,16,'Deepak Joshi','9876543009','deepak@example.com','Suit Filed Clearance',NULL,'new','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,'Suit Filed','Email',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(40,16,'Meera Iyer','9876543010','meera@example.com','Credit Report Analysis',NULL,'converted','2026-07-31 04:27:27',NULL,NULL,NULL,0.00,NULL,100,NULL,NULL,'Credit Report','Website',NULL,NULL,NULL,NULL,'urgent','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(41,16,'Suresh Singh','9876543212','suresh@example.com','Profile Correction',NULL,'converted','2026-07-19 07:51:56',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(42,16,'Meena Patel','9876543213','meena@example.com','Written Off',NULL,'converted','2026-07-19 07:51:56',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(43,16,'Arun Verma','9876543220','arun@example.com','Settled',NULL,'converted','2026-07-19 07:51:56',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(44,16,'Deepika Rao','9876541005','deepika@email.com','CIBIL Repair',NULL,'converted','2026-07-19 07:54:26',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(45,16,'Simple Customer 1785013101654','9876543253','simple1785013101654@customer.com','Written Off',NULL,'converted','2026-07-25 20:58:21',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(46,16,'Test Customer 1785022455090','9876543358','test1785022455090@customer.com','Written Off',NULL,'converted','2026-07-25 23:34:15',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(47,16,'Ankit Sharma','9876541000','ankit@email.com','CIBIL Repair',NULL,'converted','2026-07-25 23:38:33',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(48,16,'Updated Name','9876543210','updated@email.com','Settled',NULL,'converted','2026-07-25 23:59:12',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(49,16,'Test Customer 1785024272753','9876543444','test1785024272753@customer.com','CIBIL Repair',NULL,'converted','2026-07-26 00:04:33',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(50,16,'Amit Singh','9876543212','amit.singh@example.com','Profile Correction',NULL,'converted','2026-07-28 10:27:56',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(51,16,'Rahul Sharma','9876543210','rahul@example.com',NULL,NULL,'converted','2026-07-29 11:57:19',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(52,16,'Updated 1785329231152','9876531152','update_1785329231152@example.com','Written Off',NULL,'converted','2026-07-29 12:43:40',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(53,16,'New Customer 1785329044384','9876544384','customer_1785329044384@example.com','Written Off',NULL,'converted','2026-07-29 12:44:04',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(54,16,'Customer 1785329120302','9876520302','cust_1785329120302@example.com','CIBIL Repair',NULL,'converted','2026-07-29 12:45:20',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(55,16,'Customer 1785329231151','9876531151','cust_1785329231151@example.com','CIBIL Repair',NULL,'converted','2026-07-29 12:47:11',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(56,16,'Updated Customer 1785329324126','9876524126','updated_1785329324126@example.com','Written Off',NULL,'converted','2026-07-29 12:47:33',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(57,16,'Customer 1785329324125','9876524125','cust_1785329324125@example.com','CIBIL Repair',NULL,'converted','2026-07-29 12:48:44',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(58,16,'Test 1785329343954','9876543954','test_1785329343954@test.com','CIBIL Repair',NULL,'converted','2026-07-29 12:49:03',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(59,16,'Test 1785329523765','9876523765','test_1785329523765@test.com','CIBIL Repair',NULL,'converted','2026-07-29 12:52:03',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(60,16,'Customer 1785329558760','9876558760','cust_1785329558760@example.com','CIBIL Repair',NULL,'converted','2026-07-29 12:52:38',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(61,16,'Rajesh Kumar','9876554452','rajesh_1785329754452@example.com',NULL,NULL,'converted','2026-07-29 12:55:54',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(62,16,'Final Updated Name 1785337107671','9876507671','final_1785337107671@example.com','CIBIL Repair',NULL,'converted','2026-07-29 14:57:21',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(63,16,'New Customer 1785337082357','9876582357','new_1785337082357@example.com','CIBIL Repair',NULL,'converted','2026-07-29 14:58:02',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(64,16,'Customer 1785337126780','9876526780','cust_1785337126780@example.com','CIBIL Repair',NULL,'converted','2026-07-29 14:58:47',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(65,16,'Test Lead 1785329467337','9876567337','test_lead_17@example.com','CIBIL Repair',NULL,'converted','2026-07-29 15:02:30',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(66,16,'Test Customer 1785342317620','9876517620','test_1785342317620@example.com','CIBIL Repair',NULL,'converted','2026-07-29 16:25:17',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(67,16,'Test Customer 1785342380066','9876580066','test_1785342380066@example.com','CIBIL Repair',NULL,'converted','2026-07-29 16:26:20',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(68,16,'Test Customer 1785342650443','9876550443','test_1785342650443@example.com','CIBIL Repair',NULL,'converted','2026-07-29 16:30:50',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL),
(69,16,'Updated Check','9876577469','console_1785342877468@test.com','CIBIL Repair',NULL,'converted','2026-07-29 16:34:37',NULL,NULL,NULL,0.00,NULL,0,NULL,NULL,NULL,'direct',NULL,NULL,NULL,NULL,'low','direct',NULL,NULL,0.00,0.00,0.00,NULL,NULL,0,NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER leads_audit_update
AFTER UPDATE ON leads
FOR EACH ROW
BEGIN
    IF NOT (OLD.status <=> NEW.status) THEN
        INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value)
        VALUES (NULL, 'UPDATE', 'leads', NEW.id,
                CONCAT('{"status":"', OLD.status, '"}'),
                CONCAT('{"status":"', NEW.status, '"}'));
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `leave_balance`
--

DROP TABLE IF EXISTS `leave_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `annual` decimal(5,2) DEFAULT 18.00,
  `annual_used` decimal(5,2) DEFAULT 0.00,
  `sick` decimal(5,2) DEFAULT 10.00,
  `sick_used` decimal(5,2) DEFAULT 0.00,
  `casual` decimal(5,2) DEFAULT 6.00,
  `casual_used` decimal(5,2) DEFAULT 0.00,
  `comp_off` decimal(5,2) DEFAULT 0.00,
  `comp_off_used` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_year` (`employee_id`,`year`),
  CONSTRAINT `leave_balance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balance`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leave_balance` WRITE;
/*!40000 ALTER TABLE `leave_balance` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_balance` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `balance` int(11) DEFAULT 0,
  `balance_year` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_balance` (`employee_id`,`leave_type_id`,`balance_year`),
  KEY `fk_balance_type` (`leave_type_id`),
  CONSTRAINT `fk_balance_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_balance_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES
(1,13,1,12,2026,'2026-08-02 09:25:47'),
(2,13,2,10,2026,'2026-08-02 09:25:47'),
(3,13,3,15,2026,'2026-08-02 09:25:47'),
(4,13,5,180,2026,'2026-08-02 09:25:47'),
(5,13,6,15,2026,'2026-08-02 09:25:47'),
(6,24,1,12,2026,'2026-08-02 09:25:47'),
(7,24,2,10,2026,'2026-08-02 09:25:47'),
(8,24,3,15,2026,'2026-08-02 09:25:47'),
(9,24,5,180,2026,'2026-08-02 09:25:47'),
(10,24,6,15,2026,'2026-08-02 09:25:47'),
(11,1,1,12,2026,'2026-08-02 09:25:47'),
(12,1,2,10,2026,'2026-08-02 09:25:47'),
(13,1,3,15,2026,'2026-08-02 09:25:47'),
(14,1,5,180,2026,'2026-08-02 09:25:47'),
(15,1,6,15,2026,'2026-08-02 09:25:47'),
(16,2,1,12,2026,'2026-08-02 09:25:47'),
(17,2,2,10,2026,'2026-08-02 09:25:47'),
(18,2,3,15,2026,'2026-08-02 09:25:47'),
(19,2,5,180,2026,'2026-08-02 09:25:47'),
(20,2,6,15,2026,'2026-08-02 09:25:47'),
(21,3,1,12,2026,'2026-08-02 09:25:47'),
(22,3,2,10,2026,'2026-08-02 09:25:47'),
(23,3,3,15,2026,'2026-08-02 09:25:47'),
(24,3,5,180,2026,'2026-08-02 09:25:47'),
(25,3,6,15,2026,'2026-08-02 09:25:47'),
(26,4,1,12,2026,'2026-08-02 09:25:47'),
(27,4,2,10,2026,'2026-08-02 09:25:47'),
(28,4,3,15,2026,'2026-08-02 09:25:47'),
(29,4,5,180,2026,'2026-08-02 09:25:47'),
(30,4,6,15,2026,'2026-08-02 09:25:47'),
(31,5,1,12,2026,'2026-08-02 09:25:47'),
(32,5,2,10,2026,'2026-08-02 09:25:47'),
(33,5,3,15,2026,'2026-08-02 09:25:47'),
(34,5,5,180,2026,'2026-08-02 09:25:47'),
(35,5,6,15,2026,'2026-08-02 09:25:47'),
(36,6,1,12,2026,'2026-08-02 09:25:47'),
(37,6,2,10,2026,'2026-08-02 09:25:47'),
(38,6,3,15,2026,'2026-08-02 09:25:47'),
(39,6,5,180,2026,'2026-08-02 09:25:47'),
(40,6,6,15,2026,'2026-08-02 09:25:47'),
(41,7,1,12,2026,'2026-08-02 09:25:47'),
(42,7,2,10,2026,'2026-08-02 09:25:47'),
(43,7,3,15,2026,'2026-08-02 09:25:47'),
(44,7,5,180,2026,'2026-08-02 09:25:47'),
(45,7,6,15,2026,'2026-08-02 09:25:47'),
(46,8,1,12,2026,'2026-08-02 09:25:47'),
(47,8,2,10,2026,'2026-08-02 09:25:47'),
(48,8,3,15,2026,'2026-08-02 09:25:47'),
(49,8,5,180,2026,'2026-08-02 09:25:47'),
(50,8,6,15,2026,'2026-08-02 09:25:47'),
(51,9,1,12,2026,'2026-08-02 09:25:47'),
(52,9,2,10,2026,'2026-08-02 09:25:47'),
(53,9,3,15,2026,'2026-08-02 09:25:47'),
(54,9,5,180,2026,'2026-08-02 09:25:47'),
(55,9,6,15,2026,'2026-08-02 09:25:47'),
(56,10,1,12,2026,'2026-08-02 09:25:47'),
(57,10,2,10,2026,'2026-08-02 09:25:47'),
(58,10,3,15,2026,'2026-08-02 09:25:47'),
(59,10,5,180,2026,'2026-08-02 09:25:47'),
(60,10,6,15,2026,'2026-08-02 09:25:47'),
(61,11,1,12,2026,'2026-08-02 09:25:47'),
(62,11,2,10,2026,'2026-08-02 09:25:47'),
(63,11,3,15,2026,'2026-08-02 09:25:47'),
(64,11,5,180,2026,'2026-08-02 09:25:47'),
(65,11,6,15,2026,'2026-08-02 09:25:47'),
(66,12,1,12,2026,'2026-08-02 09:25:47'),
(67,12,2,10,2026,'2026-08-02 09:25:47'),
(68,12,3,15,2026,'2026-08-02 09:25:47'),
(69,12,5,180,2026,'2026-08-02 09:25:47'),
(70,12,6,15,2026,'2026-08-02 09:25:47'),
(71,14,1,12,2026,'2026-08-02 09:25:47'),
(72,14,2,10,2026,'2026-08-02 09:25:47'),
(73,14,3,15,2026,'2026-08-02 09:25:47'),
(74,14,5,180,2026,'2026-08-02 09:25:47'),
(75,14,6,15,2026,'2026-08-02 09:25:47'),
(76,15,1,12,2026,'2026-08-02 09:25:47'),
(77,15,2,10,2026,'2026-08-02 09:25:47'),
(78,15,3,15,2026,'2026-08-02 09:25:47'),
(79,15,5,180,2026,'2026-08-02 09:25:47'),
(80,15,6,15,2026,'2026-08-02 09:25:47'),
(81,16,1,12,2026,'2026-08-02 09:25:47'),
(82,16,2,10,2026,'2026-08-02 09:25:47'),
(83,16,3,15,2026,'2026-08-02 09:25:47'),
(84,16,5,180,2026,'2026-08-02 09:25:47'),
(85,16,6,15,2026,'2026-08-02 09:25:47'),
(86,17,1,12,2026,'2026-08-02 09:25:47'),
(87,17,2,10,2026,'2026-08-02 09:25:47'),
(88,17,3,15,2026,'2026-08-02 09:25:47'),
(89,17,5,180,2026,'2026-08-02 09:25:47'),
(90,17,6,15,2026,'2026-08-02 09:25:47'),
(91,18,1,12,2026,'2026-08-02 09:25:47'),
(92,18,2,10,2026,'2026-08-02 09:25:47'),
(93,18,3,15,2026,'2026-08-02 09:25:47'),
(94,18,5,180,2026,'2026-08-02 09:25:47'),
(95,18,6,15,2026,'2026-08-02 09:25:47'),
(96,19,1,12,2026,'2026-08-02 09:25:47'),
(97,19,2,10,2026,'2026-08-02 09:25:47'),
(98,19,3,15,2026,'2026-08-02 09:25:47'),
(99,19,5,180,2026,'2026-08-02 09:25:47'),
(100,19,6,15,2026,'2026-08-02 09:25:47'),
(101,20,1,12,2026,'2026-08-02 09:25:47'),
(102,20,2,10,2026,'2026-08-02 09:25:47'),
(103,20,3,15,2026,'2026-08-02 09:25:47'),
(104,20,5,180,2026,'2026-08-02 09:25:47'),
(105,20,6,15,2026,'2026-08-02 09:25:47'),
(106,21,1,12,2026,'2026-08-02 09:25:47'),
(107,21,2,10,2026,'2026-08-02 09:25:47'),
(108,21,3,15,2026,'2026-08-02 09:25:47'),
(109,21,5,180,2026,'2026-08-02 09:25:47'),
(110,21,6,15,2026,'2026-08-02 09:25:47'),
(111,22,1,12,2026,'2026-08-02 09:25:47'),
(112,22,2,10,2026,'2026-08-02 09:25:47'),
(113,22,3,15,2026,'2026-08-02 09:25:47'),
(114,22,5,180,2026,'2026-08-02 09:25:47'),
(115,22,6,15,2026,'2026-08-02 09:25:47'),
(116,23,1,12,2026,'2026-08-02 09:25:47'),
(117,23,2,10,2026,'2026-08-02 09:25:47'),
(118,23,3,15,2026,'2026-08-02 09:25:47'),
(119,23,5,180,2026,'2026-08-02 09:25:47'),
(120,23,6,15,2026,'2026-08-02 09:25:47'),
(121,25,1,12,2026,'2026-08-02 09:25:47'),
(122,25,2,10,2026,'2026-08-02 09:25:47'),
(123,25,3,15,2026,'2026-08-02 09:25:47'),
(124,25,5,180,2026,'2026-08-02 09:25:47'),
(125,25,6,15,2026,'2026-08-02 09:25:47'),
(126,33,1,12,2026,'2026-08-02 09:25:47'),
(127,33,2,10,2026,'2026-08-02 09:25:47'),
(128,33,3,15,2026,'2026-08-02 09:25:47'),
(129,33,5,180,2026,'2026-08-02 09:25:47'),
(130,33,6,15,2026,'2026-08-02 09:25:47');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `total_days` int(11) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `admin_comments` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_leaves_employee` (`employee_id`),
  KEY `fk_leaves_type` (`leave_type_id`),
  CONSTRAINT `fk_leaves_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leaves_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES
(1,1,1,'2026-08-10','2026-08-10',1,'Personal work','pending',NULL,NULL,NULL,'2026-08-02 09:30:39');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `leave_code` varchar(20) DEFAULT NULL,
  `leave_name` varchar(100) NOT NULL,
  `days_per_year` int(11) DEFAULT 0,
  `carry_forward` tinyint(4) DEFAULT 0,
  `max_carry_forward` int(11) DEFAULT 0,
  `paid` tinyint(4) DEFAULT 1,
  `requires_approval` tinyint(4) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_code` (`leave_code`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES
(1,'CL','Casual Leave',12,0,0,1,1,'active'),
(2,'SL','Sick Leave',10,0,0,1,1,'active'),
(3,'EL','Earned Leave',15,1,0,1,1,'active'),
(4,'LOP','Loss of Pay',0,0,0,0,1,'active'),
(5,'ML','Maternity Leave',180,0,0,1,1,'active'),
(6,'PL','Paternity Leave',15,0,0,1,1,'active');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_agreements`
--

DROP TABLE IF EXISTS `legal_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agreement_no` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `agreement_type` enum('credit_repair_service','partner_agreement','nda','consent_form','service_contract') DEFAULT 'credit_repair_service',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `terms_conditions` longtext DEFAULT NULL,
  `issue_date` date NOT NULL,
  `signed_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('draft','sent','signed','expired','cancelled') DEFAULT 'draft',
  `signed_by` int(11) DEFAULT NULL,
  `signature_hash` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `agreement_no` (`agreement_no`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_agreement_no` (`agreement_no`),
  KEY `idx_issue_date` (`issue_date`),
  KEY `idx_legal_agreements_client_status` (`client_id`,`status`),
  CONSTRAINT `legal_agreements_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_agreements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_agreements` WRITE;
/*!40000 ALTER TABLE `legal_agreements` DISABLE KEYS */;
INSERT INTO `legal_agreements` VALUES
(1,'',1,'credit_repair_service','Credit Repair Service Agreement',NULL,'Standard terms and conditions apply...','2026-06-04',NULL,'2027-06-04','draft',NULL,NULL,NULL,1,'2026-06-04 17:47:56','2026-06-04 17:47:56');
/*!40000 ALTER TABLE `legal_agreements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS generate_agreement_no
BEFORE INSERT ON legal_agreements
FOR EACH ROW
BEGIN
    IF NEW.agreement_no IS NULL THEN
        SET NEW.agreement_no = CONCAT('AGR-', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(NEW.id, 6, '0'));
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `legal_audit_logs`
--

DROP TABLE IF EXISTS `legal_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_audit_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `action_type` enum('login','logout','create','update','delete','view','export','sign','verify') NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_legal_audit_user_date` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_audit_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_audit_logs` WRITE;
/*!40000 ALTER TABLE `legal_audit_logs` DISABLE KEYS */;
INSERT INTO `legal_audit_logs` VALUES
(1,1,'System Admin','admin','system_initialized','create','system',NULL,'Legal compliance module initialized','127.0.0.1','System',NULL,NULL,NULL,NULL,NULL,'2026-06-04 17:47:56');
/*!40000 ALTER TABLE `legal_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_compliance_policies`
--

DROP TABLE IF EXISTS `legal_compliance_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_compliance_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(200) NOT NULL,
  `policy_type` enum('data_privacy','terms_of_service','cookie_policy','refund_policy','kyc_policy','complaint_redressal') NOT NULL,
  `policy_version` varchar(20) NOT NULL,
  `policy_content` longtext NOT NULL,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `approved_by` int(11) NOT NULL,
  `approved_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `policy_name` (`policy_name`),
  KEY `idx_policy_type` (`policy_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_compliance_policies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_compliance_policies` WRITE;
/*!40000 ALTER TABLE `legal_compliance_policies` DISABLE KEYS */;
INSERT INTO `legal_compliance_policies` VALUES
(1,'Data Privacy Policy','data_privacy','v2.0','This policy outlines how we collect, use, store, and protect client personal data in compliance with DPDP Act 2023 and RBI guidelines. We collect only necessary data, store with 256-bit encryption, retain for 7 years, and never share without explicit consent.','2024-01-01',NULL,1,1,'2026-06-04 17:47:56','2026-06-04 17:47:56','2026-06-04 17:47:56'),
(2,'KYC Verification Policy','kyc_policy','v1.5','All clients must complete KYC verification within 30 days of onboarding. Acceptable documents: PAN Card, Aadhaar Card, Passport, Voter ID. Video KYC available for remote verification.','2024-01-01',NULL,1,1,'2026-06-04 17:47:56','2026-06-04 17:47:56','2026-06-04 17:47:56'),
(3,'Complaint Redressal Policy','complaint_redressal','v1.0','Client complaints are acknowledged within 24 hours, resolved within 7 business days. Escalation matrix: Level 1 - Support, Level 2 - Manager, Level 3 - Compliance Officer, Level 4 - RBI Ombudsman.','2024-01-01',NULL,1,1,'2026-06-04 17:47:56','2026-06-04 17:47:56','2026-06-04 17:47:56');
/*!40000 ALTER TABLE `legal_compliance_policies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_compliance_reports`
--

DROP TABLE IF EXISTS `legal_compliance_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_compliance_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('monthly_compliance','quarterly_audit','annual_review','rbi_report','kyc_report') NOT NULL,
  `report_period_start` date NOT NULL,
  `report_period_end` date NOT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`report_data`)),
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','submitted','approved','archived') DEFAULT 'draft',
  PRIMARY KEY (`id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_period` (`report_period_start`,`report_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_compliance_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_compliance_reports` WRITE;
/*!40000 ALTER TABLE `legal_compliance_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_compliance_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_consent_forms`
--

DROP TABLE IF EXISTS `legal_consent_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_consent_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `consent_type` enum('data_collection','credit_report_access','marketing','terms_acceptance','gdpr','kyc_consent') NOT NULL,
  `consent_version` varchar(20) NOT NULL,
  `requested_date` datetime DEFAULT current_timestamp(),
  `provided_date` datetime DEFAULT NULL,
  `consent_given` tinyint(1) DEFAULT 0,
  `consent_method` enum('electronic_signature','checkbox','verbal','written') DEFAULT 'electronic_signature',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `revocation_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_consent_type` (`consent_type`),
  KEY `idx_status` (`consent_given`),
  KEY `idx_legal_consent_client_type` (`client_id`,`consent_type`),
  CONSTRAINT `legal_consent_forms_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_consent_forms`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_consent_forms` WRITE;
/*!40000 ALTER TABLE `legal_consent_forms` DISABLE KEYS */;
INSERT INTO `legal_consent_forms` VALUES
(1,1,'data_collection','v1.0','2026-06-04 17:47:56',NULL,1,'electronic_signature',NULL,NULL,NULL,NULL,NULL,'2026-06-04 17:47:56');
/*!40000 ALTER TABLE `legal_consent_forms` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_kyc_verification`
--

DROP TABLE IF EXISTS `legal_kyc_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_kyc_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `pan_document_path` varchar(500) DEFAULT NULL,
  `aadhaar_number` varchar(20) DEFAULT NULL,
  `aadhaar_document_path` varchar(500) DEFAULT NULL,
  `passport_number` varchar(20) DEFAULT NULL,
  `passport_document_path` varchar(500) DEFAULT NULL,
  `driving_license` varchar(50) DEFAULT NULL,
  `voter_id` varchar(50) DEFAULT NULL,
  `bank_account_proof` varchar(500) DEFAULT NULL,
  `business_registration` varchar(500) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected','expired') DEFAULT 'pending',
  `verification_date` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`verification_status`),
  KEY `idx_pan` (`pan_number`),
  KEY `idx_aadhaar` (`aadhaar_number`),
  KEY `idx_legal_kyc_status` (`verification_status`),
  CONSTRAINT `legal_kyc_verification_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_kyc_verification`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_kyc_verification` WRITE;
/*!40000 ALTER TABLE `legal_kyc_verification` DISABLE KEYS */;
INSERT INTO `legal_kyc_verification` VALUES
(1,1,'ABCDE1234F',NULL,'1234-5678-9012',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-06-04 17:47:56','2026-06-04 17:47:56');
/*!40000 ALTER TABLE `legal_kyc_verification` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_privacy_logs`
--

DROP TABLE IF EXISTS `legal_privacy_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_privacy_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `data_type` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `consent_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `accessed_by` int(11) NOT NULL,
  `accessed_at` timestamp NULL DEFAULT current_timestamp(),
  `data_retention_days` int(11) DEFAULT 2555,
  `is_compliant` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_action` (`action`),
  KEY `idx_accessed_at` (`accessed_at`),
  KEY `idx_legal_privacy_client` (`client_id`,`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_privacy_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_privacy_logs` WRITE;
/*!40000 ALTER TABLE `legal_privacy_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_privacy_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `legal_rbi_complaints`
--

DROP TABLE IF EXISTS `legal_rbi_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_rbi_complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `complaint_id` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `bank_name` varchar(200) NOT NULL,
  `bureau_name` varchar(100) DEFAULT NULL,
  `complaint_type` enum('credit_report_error','identity_theft','fraud','harassment','data_misuse','service_delay') NOT NULL,
  `complaint_details` text NOT NULL,
  `filed_date` date NOT NULL,
  `rbi_reference_number` varchar(100) DEFAULT NULL,
  `rbi_acknowledgement_date` date DEFAULT NULL,
  `status` enum('filed','under_review','resolved','rejected','appealed') DEFAULT 'filed',
  `resolution_date` date DEFAULT NULL,
  `resolution_details` text DEFAULT NULL,
  `compensation_amount` decimal(12,2) DEFAULT 0.00,
  `follow_up_required` tinyint(1) DEFAULT 0,
  `next_follow_up` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `complaint_id` (`complaint_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_complaint_id` (`complaint_id`),
  KEY `idx_status` (`status`),
  KEY `idx_filed_date` (`filed_date`),
  KEY `idx_legal_rbi_status` (`status`,`filed_date`),
  CONSTRAINT `legal_rbi_complaints_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_rbi_complaints`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_rbi_complaints` WRITE;
/*!40000 ALTER TABLE `legal_rbi_complaints` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_rbi_complaints` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS generate_complaint_id
BEFORE INSERT ON legal_rbi_complaints
FOR EACH ROW
BEGIN
    IF NEW.complaint_id IS NULL THEN
        SET NEW.complaint_id = CONCAT('RBI-', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(NEW.id, 6, '0'));
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `legal_verification_docs`
--

DROP TABLE IF EXISTS `legal_verification_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_verification_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` enum('pan_card','aadhaar_card','passport','bank_statement','business_registration','income_proof','address_proof','photograph') NOT NULL,
  `document_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `file_type` varchar(50) DEFAULT 'application/pdf',
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `verified_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_status` (`verified_status`),
  CONSTRAINT `legal_verification_docs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_verification_docs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `legal_verification_docs` WRITE;
/*!40000 ALTER TABLE `legal_verification_docs` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_verification_docs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loan_applications`
--

DROP TABLE IF EXISTS `loan_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `loan_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `tenure` int(11) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','approved','rejected') DEFAULT 'pending',
  `sanctioned_amount` decimal(12,2) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_applications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loan_applications` WRITE;
/*!40000 ALTER TABLE `loan_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `loan_commission`
--

DROP TABLE IF EXISTS `loan_commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) DEFAULT NULL,
  `commission` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_commission`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `loan_commission` WRITE;
/*!40000 ALTER TABLE `loan_commission` DISABLE KEYS */;
/*!40000 ALTER TABLE `loan_commission` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `maintenance_schedule`
--

DROP TABLE IF EXISTS `maintenance_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `component` varchar(100) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `scheduled_start` datetime NOT NULL,
  `scheduled_end` datetime NOT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `idx_maintenance_status` (`status`),
  KEY `idx_maintenance_start` (`scheduled_start`),
  CONSTRAINT `maintenance_schedule_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_schedule_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_schedule`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `maintenance_schedule` WRITE;
/*!40000 ALTER TABLE `maintenance_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenance_schedule` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `marketing_campaigns`
--

DROP TABLE IF EXISTS `marketing_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketing_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_name` varchar(200) NOT NULL,
  `campaign_type` enum('email','social','ppc','seo','content','referral','event') DEFAULT 'email',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `actual_cost` decimal(12,2) DEFAULT NULL,
  `expected_revenue` decimal(12,2) DEFAULT NULL,
  `actual_revenue` decimal(12,2) DEFAULT NULL,
  `leads_generated` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `status` enum('planned','active','completed','paused','cancelled') DEFAULT 'planned',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaign_type` (`campaign_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketing_campaigns`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `marketing_campaigns` WRITE;
/*!40000 ALTER TABLE `marketing_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `marketing_campaigns` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `monthly_revenue`
--

DROP TABLE IF EXISTS `monthly_revenue`;
/*!50001 DROP VIEW IF EXISTS `monthly_revenue`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `monthly_revenue` AS SELECT
 NULL AS `month`,
 NULL AS `transaction_count`,
 NULL AS `total_revenue`,
 NULL AS `average_transaction` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `email_case_update` tinyint(4) DEFAULT 1,
  `email_payment` tinyint(4) DEFAULT 1,
  `email_document` tinyint(4) DEFAULT 1,
  `email_dispute` tinyint(4) DEFAULT 1,
  `email_ticket` tinyint(4) DEFAULT 1,
  `email_promotion` tinyint(4) DEFAULT 0,
  `sms_case_update` tinyint(4) DEFAULT 0,
  `sms_payment` tinyint(4) DEFAULT 1,
  `sms_document` tinyint(4) DEFAULT 0,
  `sms_dispute` tinyint(4) DEFAULT 0,
  `sms_ticket` tinyint(4) DEFAULT 0,
  `push_case_update` tinyint(4) DEFAULT 1,
  `push_payment` tinyint(4) DEFAULT 1,
  `push_document` tinyint(4) DEFAULT 1,
  `push_dispute` tinyint(4) DEFAULT 1,
  `push_ticket` tinyint(4) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
INSERT INTO `notification_preferences` VALUES
(1,72,1,1,1,1,1,0,0,1,0,0,0,1,1,1,1,1,'2026-06-02 12:49:31'),
(2,77,1,1,1,1,1,0,0,1,0,0,0,1,1,1,1,1,'2026-06-02 16:39:03'),
(3,78,1,1,1,1,1,0,0,1,0,0,0,1,1,1,1,1,'2026-06-02 16:39:45'),
(4,74,1,1,1,1,1,0,0,1,0,0,0,1,1,1,1,1,'2026-06-06 05:38:57'),
(5,110,1,1,1,1,1,0,0,1,0,0,0,1,1,1,1,1,'2026-06-06 13:18:51');
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES
(1,47,'Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success',1,'2026-05-04 07:54:51'),
(2,47,'New Features Added','Notification system, activity log, and backup features are now available.','info',1,'2026-05-04 07:54:51'),
(3,47,'Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning',1,'2026-05-04 07:54:51'),
(4,52,'Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success',1,'2026-05-09 16:09:01'),
(5,52,'New Features Added','Notification system, activity log, and backup features are now available.','info',1,'2026-05-09 16:09:01'),
(6,52,'Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning',1,'2026-05-09 16:09:01'),
(7,1,'Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success',1,'2026-05-29 17:58:45'),
(8,1,'New Features Added','Notification system, activity log, and backup features are now available.','info',1,'2026-05-29 17:58:45'),
(9,1,'Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning',1,'2026-05-29 17:58:45'),
(10,82,'Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success',0,'2026-06-02 15:24:16'),
(11,82,'New Features Added','Notification system, activity log, and backup features are now available.','info',0,'2026-06-02 15:24:16'),
(12,82,'Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning',0,'2026-06-02 15:24:16'),
(13,1,'Test Notification 4:07:42 PM','This is a test notification','info',1,'2026-07-29 10:37:42'),
(14,1,'Test Notification 4:12:36 PM','This is a test notification from console','info',1,'2026-07-29 10:42:36'),
(15,1,'Urgent Notification 4:12:36 PM','This is an urgent notification','',1,'2026-07-29 10:42:36');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ombudsman_cases`
--

DROP TABLE IF EXISTS `ombudsman_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ombudsman_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `case_id` varchar(50) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `filed_date` date DEFAULT NULL,
  `hearing_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ombudsman_customer` (`client_id`),
  CONSTRAINT `fk_ombudsman_customer` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ombudsman_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ombudsman_cases` WRITE;
/*!40000 ALTER TABLE `ombudsman_cases` DISABLE KEYS */;
INSERT INTO `ombudsman_cases` VALUES
(1,5,'OMB-2024-001','SBI Bank','2026-08-02','2026-10-01','pending','2026-08-02 08:29:23'),
(2,3,'OMB-2024-002','HDFC Bank','2026-08-02','2026-09-16','pending','2026-08-02 08:29:23');
/*!40000 ALTER TABLE `ombudsman_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `operation_cases`
--

DROP TABLE IF EXISTS `operation_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operation_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_no` varchar(50) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','closed') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `sla_due` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operation_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `operation_cases` WRITE;
/*!40000 ALTER TABLE `operation_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `operation_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `operation_tasks`
--

DROP TABLE IF EXISTS `operation_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operation_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('todo','in_progress','completed') DEFAULT 'todo',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operation_tasks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `operation_tasks` WRITE;
/*!40000 ALTER TABLE `operation_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `operation_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `operational_tasks`
--

DROP TABLE IF EXISTS `operational_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `operational_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('todo','in_progress','completed') DEFAULT 'todo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `operational_tasks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `operational_tasks` WRITE;
/*!40000 ALTER TABLE `operational_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `operational_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_applications`
--

DROP TABLE IF EXISTS `partner_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `partner_type` varchar(50) DEFAULT 'Business',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `ref_number` varchar(50) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `application_date` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_applications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_applications` WRITE;
/*!40000 ALTER TABLE `partner_applications` DISABLE KEYS */;
INSERT INTO `partner_applications` VALUES
(1,'John Doe','john@example.com','9876543210','Business','pending','Interested in partnership',NULL,NULL,NULL,NULL,'2026-07-28 06:45:47','2026-07-28 06:45:47','2026-07-28 06:45:47'),
(2,'Jane Smith','jane@example.com','9876543211','Individual','approved','Approved by admin',NULL,NULL,NULL,NULL,'2026-07-28 06:45:47','2026-07-28 06:45:47','2026-07-28 06:45:47'),
(3,'Bob Johnson','bob@example.com','9876543212','Business','rejected','Incomplete documentation',NULL,NULL,NULL,NULL,'2026-07-28 06:45:47','2026-07-28 06:45:47','2026-07-28 06:45:47');
/*!40000 ALTER TABLE `partner_applications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_commission`
--

DROP TABLE IF EXISTS `partner_commission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `service_amount` decimal(12,2) DEFAULT NULL,
  `commission_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('earned','paid') DEFAULT 'earned',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_commission`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_commission` WRITE;
/*!40000 ALTER TABLE `partner_commission` DISABLE KEYS */;
INSERT INTO `partner_commission` VALUES
(1,1,NULL,'Rajesh Kumar','Credit Repair',25000.00,3750.00,'earned','2026-06-05 15:58:41'),
(2,2,NULL,'Priya Sharma','Loan Assistance',15000.00,1800.00,'paid','2026-06-05 15:58:41'),
(3,1,NULL,'Amit Patel','Corporate Package',50000.00,7500.00,'earned','2026-06-05 15:58:41');
/*!40000 ALTER TABLE `partner_commission` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_commissions`
--

DROP TABLE IF EXISTS `partner_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `service_amount` decimal(10,2) DEFAULT 0.00,
  `commission_rate` int(11) NOT NULL DEFAULT 20,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('earned','pending','paid') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_commissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_commissions` WRITE;
/*!40000 ALTER TABLE `partner_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_commissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_connectors`
--

DROP TABLE IF EXISTS `partner_connectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_connectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` enum('bank','ca','lawyer','property','vehicle','other') DEFAULT 'other',
  `company` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `leads_referred` int(11) DEFAULT 0,
  `commission_due` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_connectors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_connectors` WRITE;
/*!40000 ALTER TABLE `partner_connectors` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_connectors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_contacts`
--

DROP TABLE IF EXISTS `partner_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `category` enum('bank','ca','lawyer','property','vehicle','others') NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_contacts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_contacts` WRITE;
/*!40000 ALTER TABLE `partner_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_contacts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_documents`
--

DROP TABLE IF EXISTS `partner_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_documents` WRITE;
/*!40000 ALTER TABLE `partner_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_followups`
--

DROP TABLE IF EXISTS `partner_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `follow_up_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_followups`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_followups` WRITE;
/*!40000 ALTER TABLE `partner_followups` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_followups` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_leaderboard`
--

DROP TABLE IF EXISTS `partner_leaderboard`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_leaderboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(100) NOT NULL,
  `points` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_leaderboard`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_leaderboard` WRITE;
/*!40000 ALTER TABLE `partner_leaderboard` DISABLE KEYS */;
INSERT INTO `partner_leaderboard` VALUES
(1,'Apex Financial Services',2840,'active','2026-05-02 10:27:36'),
(2,'Credit Guru Associates',2750,'active','2026-05-02 10:27:36'),
(3,'Elite Credit Solutions',2610,'active','2026-05-02 10:27:36'),
(4,'Rapid Resolve Partners',2490,'active','2026-05-02 10:27:36'),
(5,'TrustLink Advisors',2320,'active','2026-05-02 10:27:36'),
(6,'Vision Credit Care',2180,'active','2026-05-02 10:27:36'),
(7,'NexGen Fintech',1890,'active','2026-05-02 10:27:36'),
(8,'Prime Assist Group',1650,'inactive','2026-05-02 10:27:36'),
(9,'Apex Financial Services',2840,'active','2026-05-02 10:35:33'),
(10,'Credit Guru Associates',2750,'active','2026-05-02 10:35:33'),
(11,'Elite Credit Solutions',2610,'active','2026-05-02 10:35:33'),
(12,'Rapid Resolve Partners',2490,'active','2026-05-02 10:35:33'),
(13,'TrustLink Advisors',2320,'active','2026-05-02 10:35:33'),
(14,'Vision Credit Care',2180,'active','2026-05-02 10:35:33'),
(15,'NexGen Fintech',1890,'active','2026-05-02 10:35:33'),
(16,'Prime Assist Group',1650,'inactive','2026-05-02 10:35:33');
/*!40000 ALTER TABLE `partner_leaderboard` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_leads`
--

DROP TABLE IF EXISTS `partner_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `status` enum('new','contacted','converted','lost') DEFAULT 'new',
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `next_followup` datetime DEFAULT NULL,
  `last_followup` datetime DEFAULT NULL,
  `followup_count` int(11) DEFAULT 0,
  `lead_score` int(11) DEFAULT 0,
  `lead_priority` enum('low','medium','high','urgent') DEFAULT 'low',
  `response_time` int(11) DEFAULT NULL,
  `call_count` int(11) DEFAULT 0,
  `email_count` int(11) DEFAULT 0,
  `client_id` int(11) DEFAULT NULL,
  `case_status` varchar(50) DEFAULT 'pending',
  `case_progress` int(11) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'Website',
  `source_type` varchar(50) DEFAULT 'direct',
  `source_id` int(11) DEFAULT NULL,
  `source_name` varchar(255) DEFAULT NULL,
  `source_commission_rate` decimal(5,2) DEFAULT 0.00,
  `source_commission_amount` decimal(10,2) DEFAULT 0.00,
  `score` int(11) DEFAULT 0,
  `priority` varchar(20) DEFAULT 'low',
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_partner_id` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_leads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_leads` WRITE;
/*!40000 ALTER TABLE `partner_leads` DISABLE KEYS */;
INSERT INTO `partner_leads` VALUES
(1,46,'Test Customer','9999999999',NULL,'Written Off Clearance','new',0.00,NULL,'2026-05-09 09:01:22','2026-05-09 11:42:21',NULL,NULL,0,100,'urgent',NULL,0,0,NULL,'pending',0,NULL,NULL,NULL,NULL,'Website','direct',NULL,NULL,0.00,0.00,0,'low');
/*!40000 ALTER TABLE `partner_leads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_notifications`
--

DROP TABLE IF EXISTS `partner_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_notifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_notifications` WRITE;
/*!40000 ALTER TABLE `partner_notifications` DISABLE KEYS */;
INSERT INTO `partner_notifications` VALUES
(1,83,'Welcome to Partner Dashboard! 🎉','Thank you for joining CIBIL Repair as a partner. Start adding leads to earn up to 50% commission!',0,'success','2026-06-06 11:28:26'),
(2,10,'Welcome to Partner Dashboard! 🎉','Thank you for joining CIBIL Repair as a partner. Start adding leads to earn up to 50% commission!',0,'success','2026-06-28 10:53:32'),
(3,9,'Welcome! 🎉','Welcome to your partner dashboard. Start adding leads to earn commissions!',0,'success','2026-07-30 08:47:04'),
(4,15,'Welcome! 🎉','Welcome to your partner dashboard. Start adding leads to earn commissions!',0,'success','2026-07-31 00:33:02'),
(5,16,'Welcome! 🎉','Welcome to your partner dashboard. Start adding leads to earn commissions!',0,'success','2026-07-31 01:26:59');
/*!40000 ALTER TABLE `partner_notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_payouts`
--

DROP TABLE IF EXISTS `partner_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','processing','paid','rejected') DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `paid_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_payouts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_payouts` WRITE;
/*!40000 ALTER TABLE `partner_payouts` DISABLE KEYS */;
INSERT INTO `partner_payouts` VALUES
(1,10,1500.00,'bank_transfer','Test payout - Commission for June 2026','paid','PAY-TEST-001','2026-06-30 21:23:48','2026-06-30 21:25:32'),
(2,10,2500.00,'upi','Commission - July 2026','pending','PAY-002','2026-06-30 21:25:24',NULL),
(3,10,5000.00,'bank_transfer','Commission - June 2026 - PAID','paid','PAY-003','2026-06-23 21:25:24',NULL);
/*!40000 ALTER TABLE `partner_payouts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_profiles`
--

DROP TABLE IF EXISTS `partner_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `notification_prefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_prefs`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_id` (`partner_id`),
  CONSTRAINT `partner_profiles_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_profiles` WRITE;
/*!40000 ALTER TABLE `partner_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_referrals`
--

DROP TABLE IF EXISTS `partner_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `referred_name` varchar(255) DEFAULT NULL,
  `referred_email` varchar(255) DEFAULT NULL,
  `referred_phone` varchar(20) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'partner',
  `notes` text DEFAULT NULL,
  `status` enum('registered','converted','inactive') DEFAULT 'registered',
  `commission_earned` decimal(10,2) DEFAULT 0.00,
  `registered_at` timestamp NULL DEFAULT current_timestamp(),
  `converted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_referral_code` (`referral_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_referrals`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_referrals` WRITE;
/*!40000 ALTER TABLE `partner_referrals` DISABLE KEYS */;
INSERT INTO `partner_referrals` VALUES
(1,10,'PART-D3D94468','kundan','kundankhatri@gmail.com','9470637604','connector','','converted',500.00,'2026-06-29 18:10:05',NULL,'2026-06-29 18:10:05',10.00,0.00),
(2,10,'PART-D3D94468','kundan','kundankhatri46@gmail.com','9470637603','partner','API test from console','registered',1500.00,'2026-06-29 18:14:38',NULL,'2026-06-29 18:14:38',25.00,0.00);
/*!40000 ALTER TABLE `partner_referrals` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_ticket_replies`
--

DROP TABLE IF EXISTS `partner_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('partner','admin') DEFAULT 'partner',
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  CONSTRAINT `partner_ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `partner_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_ticket_replies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_ticket_replies` WRITE;
/*!40000 ALTER TABLE `partner_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_tickets`
--

DROP TABLE IF EXISTS `partner_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `admin_response` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_tickets` WRITE;
/*!40000 ALTER TABLE `partner_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partner_tiers`
--

DROP TABLE IF EXISTS `partner_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partner_tiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `tier_level` tinyint(4) DEFAULT 1,
  `tier_name` enum('Bronze','Silver','Gold','Platinum','Diamond') DEFAULT 'Bronze',
  `commission_rate` decimal(5,2) DEFAULT 30.00,
  `total_conversions` int(11) DEFAULT 0,
  `current_month_conversions` int(11) DEFAULT 0,
  `tier_updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_id` (`partner_id`),
  KEY `idx_tier_level` (`tier_level`),
  CONSTRAINT `partner_tiers_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_tiers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partner_tiers` WRITE;
/*!40000 ALTER TABLE `partner_tiers` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_tiers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `total_leads` int(11) DEFAULT 0,
  `total_converted` int(11) DEFAULT 0,
  `total_commission` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `tier_id` int(11) DEFAULT 1,
  `gst_number` varchar(15) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `partner_type` enum('individual','firm','company') DEFAULT 'individual',
  `tier_level` enum('bronze','silver','gold','platinum','diamond') DEFAULT 'bronze',
  `base_commission_rate` decimal(5,2) DEFAULT 30.00,
  `current_commission_rate` decimal(5,2) DEFAULT 30.00,
  `total_conversions` int(11) DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_commission_earned` decimal(12,2) DEFAULT 0.00,
  `pending_payout` decimal(12,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 4.50,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `kyc_status` enum('pending','submitted','verified','rejected') DEFAULT 'pending',
  `user_id` int(11) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `monthly_referrals` int(11) DEFAULT 0,
  `tier_updated_at` datetime DEFAULT NULL,
  `tier` int(11) DEFAULT 1,
  `allow_payouts` tinyint(1) DEFAULT 1,
  `allow_referrals` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_phone` (`phone`),
  KEY `idx_tier_level` (`tier_level`),
  KEY `idx_kyc_status` (`kyc_status`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES
(40,'Micro Vision','Dhanbad','Laxmi','laxmikundan10@gmail.com',NULL,'9905482503',NULL,NULL,NULL,NULL,NULL,25.00,0,0,0.00,'active',NULL,'2026-07-31 01:15:28',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','silver',25.00,25.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',16,NULL,0,'2026-08-01 00:00:01',2,1,1),
(41,'Kundan','Patna','kundan kumar','kundankhatri@gmail.com',NULL,'8709455441',NULL,NULL,NULL,NULL,NULL,20.00,0,0,0.00,'active','','2026-07-31 08:29:33',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',17,NULL,0,'2026-08-01 00:00:01',1,1,1),
(42,'Kundan Khatri','Patna, Bihar','Kundan Khatri','khotegiri@gmail.com',NULL,'9876543210','KK Credit Solutions',NULL,NULL,NULL,NULL,20.00,0,0,0.00,'active',NULL,'2026-07-31 09:52:44',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',19,NULL,0,'2026-08-01 00:00:01',1,1,1);
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `partners_backup_202501`
--

DROP TABLE IF EXISTS `partners_backup_202501`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners_backup_202501` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `total_leads` int(11) DEFAULT 0,
  `total_converted` int(11) DEFAULT 0,
  `total_commission` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `tier_id` int(11) DEFAULT 1,
  `gst_number` varchar(15) DEFAULT NULL,
  `pan_number` varchar(10) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `partner_type` enum('individual','firm','company') DEFAULT 'individual',
  `tier_level` enum('bronze','silver','gold','platinum','diamond') DEFAULT 'bronze',
  `base_commission_rate` decimal(5,2) DEFAULT 30.00,
  `current_commission_rate` decimal(5,2) DEFAULT 30.00,
  `total_conversions` int(11) DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_commission_earned` decimal(12,2) DEFAULT 0.00,
  `pending_payout` decimal(12,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 4.50,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `kyc_status` enum('pending','submitted','verified','rejected') DEFAULT 'pending',
  `user_id` int(11) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `monthly_referrals` int(11) DEFAULT 0,
  `tier_updated_at` datetime DEFAULT NULL,
  `tier` int(11) DEFAULT 1,
  `allow_payouts` tinyint(1) DEFAULT 1,
  `allow_referrals` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners_backup_202501`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `partners_backup_202501` WRITE;
/*!40000 ALTER TABLE `partners_backup_202501` DISABLE KEYS */;
INSERT INTO `partners_backup_202501` VALUES
(2,'Mumbai Finance Hub',NULL,NULL,'ramesh@mfh.in',NULL,'9876543216','Mumbai Finance Hub',NULL,NULL,NULL,NULL,10.00,0,0,0.00,'active',NULL,'2026-07-19 07:51:56',NULL,1,NULL,NULL,'Ramesh Patil',NULL,NULL,'company','silver',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'verified',NULL,NULL,0,NULL,1,1,1),
(3,'Chennai CIBIL Experts',NULL,NULL,'kiran@cce.in',NULL,'9876543217','Chennai CIBIL Experts',NULL,NULL,NULL,NULL,11.00,0,0,0.00,'active',NULL,'2026-07-19 07:51:56',NULL,1,NULL,NULL,'Kiran Nair',NULL,NULL,'company','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'verified',NULL,NULL,0,NULL,1,1,1),
(5,'Console Test Partner',NULL,NULL,'',NULL,'9876543219',NULL,NULL,NULL,NULL,NULL,10.00,0,0,0.00,'active',NULL,'2026-07-19 16:58:18',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(13,'Test Partner',NULL,NULL,'test@partner.com',NULL,'9876543210','Test Company','','','','',10.00,0,0,0.00,'active',NULL,'2026-07-19 17:16:20',NULL,1,'','','','','','individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,'','','pending',NULL,NULL,0,NULL,1,1,1),
(14,'Delhi Finance',NULL,NULL,'delhi@finance.com',NULL,'9876543100','','','','','',10.00,0,0,0.00,'active',NULL,'2026-07-19 17:16:51',NULL,1,'','','','','','individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,'','','pending',NULL,NULL,0,NULL,1,1,1),
(15,'Bangalore Solutions',NULL,NULL,'bangalore@solutions.com',NULL,'9876543102','','','','','',10.00,0,0,0.00,'active',NULL,'2026-07-19 17:16:51',NULL,1,'','','','','','individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,'','','pending',NULL,NULL,0,NULL,1,1,1),
(16,'Mumbai Credit',NULL,NULL,'mumbai@credit.com',NULL,'9876543101','','','','','',10.00,0,0,0.00,'active',NULL,'2026-07-19 17:16:51',NULL,1,'','','','','','individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,'','','pending',NULL,NULL,0,NULL,1,1,1),
(20,'Fixed Partner 1785016990796','Mumbai','John Doe','fixed1785016990796@test.com',NULL,'9876543773',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active','Company: Test Company\nAddress: 123 Test Street\n','2026-07-25 22:03:10',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(21,'Full Test 1785017085629','Delhi NCR','Rahul Sharma','full1785017085629@partner.com',NULL,'9876543966',NULL,NULL,NULL,NULL,NULL,25.00,0,0,0.00,'active','Company: ABC Solutions Pvt Ltd\nAddress: 456, Connaught Place, New Delhi\n','2026-07-25 22:04:46',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(22,'Test Partner 1785022455234','Mumbai','John Doe','partner1785022455234@test.com',NULL,'9876543329',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active','','2026-07-25 23:34:15',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(23,'Test Partner 1785329659592','Delhi NCR','Amit Singh','partner_1785329659592@example.com',NULL,'9876559592',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active',NULL,'2026-07-29 12:54:19',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(24,'Partner 1785329682633','Mumbai','Owner Name','partner_1785329682633@example.com',NULL,'9876582633',NULL,NULL,NULL,NULL,NULL,20.00,0,0,0.00,'active',NULL,'2026-07-29 12:54:42',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(25,'Test Partner 1785330466935','Delhi NCR','Amit Singh','partner_1785330466935@example.com',NULL,'9876566935',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active',NULL,'2026-07-29 13:07:47',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(26,'Amit Singh 1785335437560','Delhi','Amit Singh','amit_1785335437560@partner.com',NULL,'9876537560',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'pending',NULL,'2026-07-29 14:30:38',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(27,'New Partner 1785335461826','Mumbai','Owner Name','partner_1785335461826@example.com',NULL,'9876561826',NULL,NULL,NULL,NULL,NULL,20.00,0,0,0.00,'pending',NULL,'2026-07-29 14:31:02',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(28,'Updated Partner 1785339403514','Delhi NCR','New Owner','updated_1785339403514@partner.com',NULL,'9876503514',NULL,NULL,NULL,NULL,NULL,25.00,0,0,0.00,'active',NULL,'2026-07-29 14:31:25',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(29,'Test Partner 1785342318192','Delhi','Test Owner','partner_1785342318192@example.com',NULL,'9876518192',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active',NULL,'2026-07-29 16:25:18',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(30,'Test Partner 1785342380492','Delhi','Test Owner','partner_1785342380492@example.com',NULL,'9876580492',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active',NULL,'2026-07-29 16:26:20',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(31,'Test Partner 1785342651017','Delhi','Test Owner','partner_1785342651017@example.com',NULL,'9876551017',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active',NULL,'2026-07-29 16:30:51',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(32,'Console Partner 1785342877906','Delhi','Test Owner','partner_1785342877906@test.com',NULL,'9876577906',NULL,NULL,NULL,NULL,NULL,15.00,0,0,0.00,'active',NULL,'2026-07-29 16:34:38',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(33,'kundan','patna','kundan','kundankhatri@gmail.com',NULL,'9470637504',NULL,NULL,NULL,NULL,NULL,10.00,0,0,0.00,'active','','2026-07-30 10:24:46',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(34,'New Partner',NULL,NULL,'partner@cibilrepair.in',NULL,'',NULL,NULL,NULL,NULL,NULL,25.00,0,0,0.00,'active',NULL,'2026-07-30 14:00:13',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',9,NULL,0,NULL,2,1,1),
(35,'Laxmi','Dhanbad','Laxmi Kumari','laxmikundan10@gmail.com',NULL,'9905482503',NULL,NULL,NULL,NULL,NULL,25.00,0,0,0.00,'active','','2026-07-30 23:15:51',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',NULL,NULL,0,NULL,1,1,1),
(38,'Laxmi',NULL,NULL,'loanwala75@gmail.com',NULL,'8709455441',NULL,NULL,NULL,NULL,NULL,25.00,0,0,0.00,'active',NULL,'2026-07-30 23:30:42',NULL,1,NULL,NULL,NULL,NULL,NULL,'individual','bronze',30.00,30.00,0,0.00,0.00,0.00,4.50,NULL,NULL,'pending',14,NULL,0,NULL,2,1,1);
/*!40000 ALTER TABLE `partners_backup_202501` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientName` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'success',
  `date` date DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `package` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_client` (`clientName`),
  KEY `idx_payments_client` (`clientName`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_date` (`date`),
  KEY `idx_payments_date_status` (`date`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(1,'Rajesh Kumar',15000.00,'Written Off Clearance','success','2025-04-15',NULL,NULL,NULL,NULL),
(2,'Rajesh Kumar',4999.00,'CIBIL Dispute Resolution','success','2026-06-02',NULL,NULL,NULL,NULL),
(3,'Rajesh Kumar',1999.00,'Credit Report Analysis','success','2026-05-03',NULL,NULL,NULL,NULL),
(4,'Priya Sharma',9999.00,'Written Off Settlement','pending','2026-06-02',NULL,NULL,NULL,NULL),
(5,'Priya Sharma',4999.00,'Credit Score Improvement','success','2026-05-18',NULL,NULL,NULL,NULL),
(6,'Amit Patel',2999.00,'Document Verification','success','2026-04-18',NULL,NULL,NULL,NULL),
(7,'Neha Gupta',1999.00,'Loan Eligibility Check','pending','2026-06-02',NULL,NULL,NULL,NULL),
(8,'Vikram Singh',4999.00,'CIBIL Dispute Resolution','success','2026-05-23',NULL,NULL,NULL,NULL),
(9,'Rajesh Kumar',25000.00,'Credit Repair','paid','2026-06-05',NULL,'UPI','Premium Package',NULL),
(10,'Priya Sharma',15000.00,'Credit Repair','paid','2026-06-05',NULL,'Credit Card','Basic Package',NULL),
(11,'Amit Patel',50000.00,'Corporate','pending','2026-06-05',NULL,'NEFT','Corporate Package',NULL),
(12,'Neha Gupta',10000.00,'Loan','paid','2026-05-31',NULL,'UPI','Loan Assistance',NULL),
(13,'Suresh Singh',25000.00,'Credit Repair','paid','2026-05-26',NULL,'Card','Premium Package',NULL),
(14,'Anita Desai',15000.00,'Credit Repair','Completed','2026-05-21',NULL,'Cash','Basic Package',NULL),
(15,'Rajesh Kumar',25000.00,'CIBIL Dispute Resolution','completed','2026-07-25',NULL,'UPI','Premium Package',NULL),
(16,'Rajesh Kumar',999.00,'CIBIL Score Repair','completed','2026-07-29','TXN100001','UPI','Premium',1),
(17,'Priya Sharma',1499.00,'Dispute Resolution','pending','2026-07-29','TXN100002','Credit Card','Standard',1),
(18,'Amit Singh',499.00,'Credit Consultation','success','2026-07-29','TXN100003','Debit Card','Basic',1),
(19,'Sneha Patel',2500.00,'Legal Protection','paid','2026-07-29','TXN100004','Bank Transfer','Enterprise',1),
(20,'Vikram Singh',7500.00,'Written Off Clearance','completed','2026-07-29','TXN100005','UPI','Premium Plus',1),
(21,'John Doe',15000.00,'CIBIL Score Repair','pending','2026-07-29','TXN1785325755924','UPI','Premium',1),
(22,'Full Test User',35000.00,'Premium CIBIL Repair','success','2026-07-29','TXN815367','Bank Transfer','Enterprise',1),
(25,'New Client',15000.00,'CIBIL Repair','pending','2026-07-29','TXN219917','Cash','Basic',1),
(26,'Quick',999.00,'CIBIL Repair','pending','2026-07-29','TXN289970','Cash','Basic',1),
(27,'Rahul Sharma',15000.00,'CIBIL Score Repair','pending','2026-07-29','TXN527830','razorpay','Premium',1),
(28,'Test Payment',1000.00,'Test','pending','2026-07-29','TXN108464','Cash','Basic',1),
(29,'Payment 1785329343954',1000.00,'Test','pending','2026-07-29','TXN330300','Cash','Basic',1),
(30,'Payment 1785329523765',1000.00,'Test','pending','2026-07-29','TXN866493','Cash','Basic',1),
(31,'Rajesh Kumar',15000.00,'CIBIL Score Repair','pending','2026-07-29','TXN132875','UPI','Premium',1),
(32,'Customer Name',15000.00,'CIBIL Repair','pending','2026-07-29','TXN559019','UPI','Basic',1),
(33,'New Customer',20000.00,'CIBIL Repair','completed','2026-07-29','TXN538134','UPI','Premium',1),
(34,'Quotation: Test Customer 1785337734961',15000.00,'CIBIL Score Repair','sent','2026-07-29','TXN970449','Quotation','Premium',1),
(35,'Quotation: 1785337752441',25000.00,'Premium CIBIL Repair','pending','2026-07-29','TXN300903','Quotation','Premium',1),
(36,'Quotation: 1785337768211',15000.00,'CIBIL Repair','pending','2026-07-29','TXN752250','Quotation','Basic',1),
(37,'Quotation: Customer 1785338326792',15000.00,'CIBIL Score Repair','sent','2026-07-29','TXN695735','Quotation','Premium',1),
(38,'Test Payment 1785342317943',1000.00,'CIBIL Repair','pending','2026-07-29','TXN505626','UPI','Basic',1),
(39,'Test Payment 1785342380215',1000.00,'CIBIL Repair','pending','2026-07-29','TXN168219','UPI','Basic',1),
(40,'Test Payment 1785342650751',1000.00,'CIBIL Repair','pending','2026-07-29','TXN175895','UPI','Basic',1),
(41,'Quotation: Test Customer 1785342818250',15000.00,'CIBIL Score Repair','pending','2026-07-29','TXN257041','Quotation','Premium',1),
(42,'Console Payment 1785342877660',5000.00,'CIBIL Repair','pending','2026-07-29','TXN734913','UPI','Basic',1),
(43,'Quotation: Console Test 1785342878321',15000.00,'CIBIL Repair','pending','2026-07-29','TXN959862','Quotation','Basic',1),
(44,'Check Payment',1000.00,'CIBIL Repair','pending','2026-07-29','TXN328463','Cash','Basic',1),
(45,'Check Quotation',10000.00,'CIBIL Repair','pending','2026-07-29','TXN577499','Quotation','Basic',1);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER payments_audit_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    INSERT INTO audit_log (user_id, action, table_name, record_id, new_value)
    VALUES (NULL, 'INSERT', 'payments', NEW.id,
            CONCAT('{"client":"', NEW.clientName, '","amount":"', NEW.amount, '","status":"', NEW.status, '"}'));
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `payout_requests`
--

DROP TABLE IF EXISTS `payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payout_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` enum('partner','employee') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','rejected') DEFAULT 'pending',
  `request_date` date DEFAULT NULL,
  `processed_date` date DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `recipient_type` (`recipient_type`,`recipient_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payout_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payout_requests` WRITE;
/*!40000 ALTER TABLE `payout_requests` DISABLE KEYS */;
INSERT INTO `payout_requests` VALUES
(1,'partner',1,3750.00,NULL,'pending','2026-06-05',NULL,NULL,'2026-06-05 15:59:26'),
(2,'partner',2,1800.00,NULL,'completed','2026-05-31',NULL,NULL,'2026-06-05 15:59:26'),
(3,'employee',5,5000.00,NULL,'pending','2026-06-05',NULL,NULL,'2026-06-05 15:59:26'),
(4,'partner',10,5000.00,'Bank Transfer','pending','2026-06-29',NULL,NULL,'2026-06-29 15:30:35');
/*!40000 ALTER TABLE `payout_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payouts`
--

DROP TABLE IF EXISTS `payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending','processing','paid','rejected') DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `request_date` timestamp NULL DEFAULT current_timestamp(),
  `paid_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payouts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payouts` WRITE;
/*!40000 ALTER TABLE `payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payouts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) DEFAULT NULL,
  `payroll_date` date DEFAULT NULL,
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `hra` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `total_earnings` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_payroll_employee` (`employee_id`),
  CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
INSERT INTO `payroll` VALUES
(1,1,'2026-08-01',50000.00,20000.00,10000.00,80000.00,6000.00,74000.00,'processed','2026-08-02 07:57:15'),
(2,2,'2026-08-01',75000.00,30000.00,15000.00,120000.00,9000.00,111000.00,'processed','2026-08-02 07:57:15'),
(3,3,'2026-08-01',45000.00,18000.00,9000.00,72000.00,5400.00,66600.00,'processed','2026-08-02 07:57:15'),
(4,4,'2026-08-01',35000.00,14000.00,7000.00,56000.00,4200.00,51800.00,'processed','2026-08-02 07:57:15'),
(5,5,'2026-08-01',40000.00,16000.00,8000.00,64000.00,4800.00,59200.00,'processed','2026-08-02 07:57:15'),
(6,6,'2026-08-01',45000.00,18000.00,9000.00,72000.00,5400.00,66600.00,'processed','2026-08-02 07:57:15'),
(7,7,'2026-08-01',95000.00,38000.00,19000.00,152000.00,11400.00,140600.00,'processed','2026-08-02 07:57:15'),
(8,8,'2026-08-01',38000.00,15200.00,7600.00,60800.00,4560.00,56240.00,'processed','2026-08-02 07:57:15'),
(9,9,'2026-08-01',52000.00,20800.00,10400.00,83200.00,6240.00,76960.00,'processed','2026-08-02 07:57:15'),
(10,10,'2026-08-01',72000.00,28800.00,14400.00,115200.00,8640.00,106560.00,'processed','2026-08-02 07:57:15'),
(11,11,'2026-08-01',30000.00,12000.00,6000.00,48000.00,3600.00,44400.00,'processed','2026-08-02 07:57:15'),
(12,12,'2026-08-01',55000.00,22000.00,11000.00,88000.00,6600.00,81400.00,'processed','2026-08-02 07:57:15'),
(13,14,'2026-08-01',35000.00,14000.00,7000.00,56000.00,4200.00,51800.00,'processed','2026-08-02 07:57:15'),
(14,15,'2026-08-01',28000.00,11200.00,5600.00,44800.00,3360.00,41440.00,'processed','2026-08-02 07:57:15'),
(15,16,'2026-08-01',100000.00,40000.00,20000.00,160000.00,12000.00,148000.00,'processed','2026-08-02 07:57:15'),
(16,17,'2026-08-01',95000.00,38000.00,19000.00,152000.00,11400.00,140600.00,'processed','2026-08-02 07:57:15'),
(17,18,'2026-08-01',38000.00,15200.00,7600.00,60800.00,4560.00,56240.00,'processed','2026-08-02 07:57:15'),
(18,19,'2026-08-01',52000.00,20800.00,10400.00,83200.00,6240.00,76960.00,'processed','2026-08-02 07:57:15'),
(19,20,'2026-08-01',45000.00,18000.00,9000.00,72000.00,5400.00,66600.00,'processed','2026-08-02 07:57:15'),
(20,21,'2026-08-01',72000.00,28800.00,14400.00,115200.00,8640.00,106560.00,'processed','2026-08-02 07:57:15'),
(21,22,'2026-08-01',30000.00,12000.00,6000.00,48000.00,3600.00,44400.00,'processed','2026-08-02 07:57:15'),
(22,23,'2026-08-01',55000.00,22000.00,11000.00,88000.00,6600.00,81400.00,'processed','2026-08-02 07:57:15'),
(23,25,'2026-08-01',35000.00,14000.00,7000.00,56000.00,4200.00,51800.00,'processed','2026-08-02 07:57:15'),
(24,1,'2026-08-02',50000.00,15000.00,5000.00,0.00,3000.00,67000.00,'processed','2026-08-02 09:29:43');
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pending_reports`
--

DROP TABLE IF EXISTS `pending_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pending_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `bureau` varchar(50) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('pending','processing','analyzed') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pending_reports` WRITE;
/*!40000 ALTER TABLE `pending_reports` DISABLE KEYS */;
INSERT INTO `pending_reports` VALUES
(1,75,'Neha Gupta','CIBIL',NULL,1,'2026-06-05 18:21:12','pending',NULL),
(2,76,'Vikram Singh','Experian',NULL,1,'2026-06-05 18:21:12','pending',NULL),
(3,110,'Client User','Equifax',NULL,1,'2026-06-05 18:21:12','pending',NULL),
(4,72,'Rajesh Kumar','CRIF',NULL,1,'2026-06-05 18:21:12','processing',NULL);
/*!40000 ALTER TABLE `pending_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `performance_reviews`
--

DROP TABLE IF EXISTS `performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_period` varchar(20) NOT NULL,
  `quality_score` decimal(3,1) DEFAULT NULL,
  `attendance_score` decimal(3,1) DEFAULT NULL,
  `communication_score` decimal(3,1) DEFAULT NULL,
  `teamwork_score` decimal(3,1) DEFAULT NULL,
  `problem_solving_score` decimal(3,1) DEFAULT NULL,
  `overall_rating` decimal(3,1) DEFAULT NULL,
  `goals_achieved` int(3) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `weaknesses` text DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `development_goals` text DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','completed') NOT NULL DEFAULT 'draft',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `review_period` (`review_period`),
  CONSTRAINT `performance_reviews_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `performance_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `performance_reviews` WRITE;
/*!40000 ALTER TABLE `performance_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_category` (`category`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=289 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'view_users','View Users','User Management','Can view all users','2026-06-02 18:48:31'),
(2,'create_users','Create Users','User Management','Can create new users','2026-06-02 18:48:31'),
(3,'edit_users','Edit Users','User Management','Can edit user details','2026-06-02 18:48:31'),
(4,'delete_users','Delete Users','User Management','Can delete users','2026-06-02 18:48:31'),
(5,'assign_roles','Assign Roles','User Management','Can assign roles to users','2026-06-02 18:48:31'),
(6,'view_clients','View Clients','Client Management','Can view all clients','2026-06-02 18:48:31'),
(7,'create_clients','Create Clients','Client Management','Can add new clients','2026-06-02 18:48:31'),
(8,'edit_clients','Edit Clients','Client Management','Can edit client details','2026-06-02 18:48:31'),
(9,'delete_clients','Delete Clients','Client Management','Can delete clients','2026-06-02 18:48:31'),
(10,'import_clients','Import Clients','Client Management','Can import clients in bulk','2026-06-02 18:48:31'),
(11,'view_cases','View Cases','Case Management','Can view all cases','2026-06-02 18:48:31'),
(12,'create_cases','Create Cases','Case Management','Can create new cases','2026-06-02 18:48:31'),
(13,'edit_cases','Edit Cases','Case Management','Can edit case details','2026-06-02 18:48:31'),
(14,'assign_cases','Assign Cases','Case Management','Can assign cases to team members','2026-06-02 18:48:31'),
(15,'close_cases','Close Cases','Case Management','Can close/resolve cases','2026-06-02 18:48:31'),
(16,'view_disputes','View Disputes','Dispute Management','Can view all disputes','2026-06-02 18:48:31'),
(17,'file_disputes','File Disputes','Dispute Management','Can file new disputes','2026-06-02 18:48:31'),
(18,'update_disputes','Update Disputes','Dispute Management','Can update dispute status','2026-06-02 18:48:31'),
(19,'resolve_disputes','Resolve Disputes','Dispute Management','Can resolve disputes','2026-06-02 18:48:31'),
(20,'view_payments','View Payments','Payment Management','Can view all payments','2026-06-02 18:48:31'),
(21,'create_payments','Create Payments','Payment Management','Can record payments','2026-06-02 18:48:31'),
(22,'refund_payments','Refund Payments','Payment Management','Can process refunds','2026-06-02 18:48:31'),
(23,'view_invoices','View Invoices','Payment Management','Can view invoices','2026-06-02 18:48:31'),
(24,'generate_invoices','Generate Invoices','Payment Management','Can generate invoices','2026-06-02 18:48:31'),
(25,'view_partners','View Partners','Partner Management','Can view all partners','2026-06-02 18:48:31'),
(26,'approve_partners','Approve Partners','Partner Management','Can approve partner applications','2026-06-02 18:48:31'),
(27,'manage_commissions','Manage Commissions','Partner Management','Can manage partner commissions','2026-06-02 18:48:31'),
(28,'view_partner_reports','View Partner Reports','Partner Management','Can view partner performance reports','2026-06-02 18:48:31'),
(29,'view_employees','View Employees','HR Management','Can view all employees','2026-06-02 18:48:31'),
(30,'manage_attendance','Manage Attendance','HR Management','Can manage employee attendance','2026-06-02 18:48:31'),
(31,'approve_leaves','Approve Leaves','HR Management','Can approve leave requests','2026-06-02 18:48:31'),
(32,'process_payroll','Process Payroll','HR Management','Can process monthly payroll','2026-06-02 18:48:31'),
(33,'manage_departments','Manage Departments','HR Management','Can manage departments','2026-06-02 18:48:31'),
(34,'view_reports','View Reports','Reports','Can view all reports','2026-06-02 18:48:31'),
(35,'export_reports','Export Reports','Reports','Can export reports to Excel/PDF','2026-06-02 18:48:31'),
(36,'view_dashboard','View Dashboard','Reports','Can view admin dashboard','2026-06-02 18:48:31'),
(37,'view_analytics','View Analytics','Reports','Can view analytics and charts','2026-06-02 18:48:31'),
(38,'manage_settings','Manage Settings','System','Can manage system settings','2026-06-02 18:48:31'),
(39,'view_audit_logs','View Audit Logs','System','Can view audit logs','2026-06-02 18:48:31'),
(40,'manage_backup','Manage Backup','System','Can manage database backups','2026-06-02 18:48:31'),
(41,'view_activity_logs','View Activity Logs','System','Can view user activity logs','2026-06-02 18:48:31');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pm_activities`
--

DROP TABLE IF EXISTS `pm_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_activities` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('comment','status_change','task_completed','milestone_achieved','file_upload') NOT NULL,
  `activity_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_activities_composite` (`project_id`,`created_at`),
  CONSTRAINT `pm_activities_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `pm_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_activities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_activities` WRITE;
/*!40000 ALTER TABLE `pm_activities` DISABLE KEYS */;
INSERT INTO `pm_activities` VALUES
(1,1,1,'comment','Project initiated. Credit reports requested from all 3 bureaus.','2026-06-04 18:12:40'),
(2,1,1,'status_change','Project status changed from Planning to In Progress','2026-06-04 18:12:40'),
(3,1,2,'task_completed','Initial Credit Report Analysis task completed','2026-06-04 18:12:40');
/*!40000 ALTER TABLE `pm_activities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pm_documents`
--

DROP TABLE IF EXISTS `pm_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_documents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_document_type` (`document_type`),
  CONSTRAINT `pm_documents_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `pm_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_documents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_documents` WRITE;
/*!40000 ALTER TABLE `pm_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `pm_documents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pm_milestones`
--

DROP TABLE IF EXISTS `pm_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `milestone_name` varchar(255) NOT NULL,
  `milestone_description` text DEFAULT NULL,
  `target_date` date NOT NULL,
  `achieved_date` date DEFAULT NULL,
  `status` enum('pending','achieved','missed') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`status`),
  KEY `idx_target_date` (`target_date`),
  KEY `idx_milestones_composite` (`project_id`,`status`,`target_date`),
  CONSTRAINT `pm_milestones_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `pm_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_milestones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_milestones` WRITE;
/*!40000 ALTER TABLE `pm_milestones` DISABLE KEYS */;
INSERT INTO `pm_milestones` VALUES
(1,1,'Credit Report Obtained','Successfully obtained all 3 credit reports','2026-06-11',NULL,'achieved',0,'2026-06-04 18:12:40'),
(2,1,'Disputes Filed','All disputes filed with credit bureaus','2026-06-25',NULL,'pending',0,'2026-06-04 18:12:40'),
(3,1,'First Response Received','Received first response from bureau','2026-07-19',NULL,'pending',0,'2026-06-04 18:12:40'),
(4,1,'Errors Corrected','All identified errors corrected','2026-08-18',NULL,'pending',0,'2026-06-04 18:12:40'),
(5,1,'Project Completion','Project successfully completed','2026-09-02',NULL,'pending',0,'2026-06-04 18:12:40');
/*!40000 ALTER TABLE `pm_milestones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pm_projects`
--

DROP TABLE IF EXISTS `pm_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_type` enum('credit_repair','dispute_resolution','consultation','training','other') DEFAULT 'credit_repair',
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `target_end_date` date NOT NULL,
  `actual_end_date` date DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT 0.00,
  `actual_cost` decimal(12,2) DEFAULT 0.00,
  `status` enum('planning','in_progress','on_hold','completed','cancelled') DEFAULT 'planning',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `completion_percentage` int(11) DEFAULT 0,
  `project_manager` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_project_manager` (`project_manager`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_projects_composite` (`status`,`priority`,`target_end_date`),
  CONSTRAINT `pm_projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_projects`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_projects` WRITE;
/*!40000 ALTER TABLE `pm_projects` DISABLE KEYS */;
INSERT INTO `pm_projects` VALUES
(1,'PRJ-2024001','Credit Repair - John Doe',1,'credit_repair','Full credit repair including dispute with CIBIL, Experian, and Equifax','2026-06-04','2026-09-02',NULL,25000.00,0.00,'in_progress','high',0,1,1,'2026-06-04 18:12:40','2026-06-04 18:12:40');
/*!40000 ALTER TABLE `pm_projects` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_generate_project_code
BEFORE INSERT ON pm_projects
FOR EACH ROW
BEGIN
    IF NEW.project_code IS NULL THEN
        SET NEW.project_code = CONCAT('PRJ-', DATE_FORMAT(NOW(), '%Y'), '-', LPAD(NEW.id, 6, '0'));
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pm_task_dependencies`
--

DROP TABLE IF EXISTS `pm_task_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_task_dependencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `depends_on_task_id` int(11) NOT NULL,
  `dependency_type` enum('finish_to_start','start_to_start','finish_to_finish') DEFAULT 'finish_to_start',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dependency` (`task_id`,`depends_on_task_id`),
  KEY `depends_on_task_id` (`depends_on_task_id`),
  CONSTRAINT `pm_task_dependencies_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `pm_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pm_task_dependencies_ibfk_2` FOREIGN KEY (`depends_on_task_id`) REFERENCES `pm_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_task_dependencies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_task_dependencies` WRITE;
/*!40000 ALTER TABLE `pm_task_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `pm_task_dependencies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pm_tasks`
--

DROP TABLE IF EXISTS `pm_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','review','completed','blocked') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `due_date` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT 0.00,
  `actual_hours` decimal(5,2) DEFAULT 0.00,
  `depends_on` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `depends_on` (`depends_on`),
  KEY `idx_tasks_composite` (`project_id`,`status`,`due_date`),
  CONSTRAINT `pm_tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `pm_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pm_tasks_ibfk_2` FOREIGN KEY (`depends_on`) REFERENCES `pm_tasks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_tasks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_tasks` WRITE;
/*!40000 ALTER TABLE `pm_tasks` DISABLE KEYS */;
INSERT INTO `pm_tasks` VALUES
(1,1,'Initial Credit Report Analysis','Analyze all 3 credit bureau reports',1,'completed','high','2026-06-09',NULL,8.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40'),
(2,1,'Identify Dispute Items','Identify all errors and discrepancies',1,'completed','high','2026-06-14',NULL,4.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40'),
(3,1,'Prepare Dispute Letters','Draft dispute letters for each bureau',1,'in_progress','high','2026-06-19',NULL,12.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40'),
(4,1,'Submit Disputes to CIBIL','File disputes with CIBIL',1,'pending','medium','2026-06-24',NULL,4.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40'),
(5,1,'Follow-up with Bureaus','Regular follow-up on dispute status',1,'pending','medium','2026-07-19',NULL,8.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40'),
(6,1,'Review Resolution','Review bureau responses and resolutions',1,'pending','high','2026-08-18',NULL,6.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40'),
(7,1,'Final Report to Client','Prepare and share final credit report',1,'pending','medium','2026-09-02',NULL,4.00,0.00,NULL,1,'2026-06-04 18:12:40','2026-06-04 18:12:40');
/*!40000 ALTER TABLE `pm_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_update_project_completion
AFTER UPDATE ON pm_tasks
FOR EACH ROW
BEGIN
    DECLARE total_tasks INT;
    DECLARE completed_tasks INT;
    DECLARE new_percentage INT;
    
    SELECT COUNT(*) INTO total_tasks FROM pm_tasks WHERE project_id = NEW.project_id;
    SELECT COUNT(*) INTO completed_tasks FROM pm_tasks WHERE project_id = NEW.project_id AND status = 'completed';
    
    IF total_tasks > 0 THEN
        SET new_percentage = (completed_tasks / total_tasks) * 100;
        UPDATE pm_projects SET completion_percentage = new_percentage WHERE id = NEW.project_id;
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pm_team_members`
--

DROP TABLE IF EXISTS `pm_team_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(100) NOT NULL,
  `allocation_percentage` int(11) DEFAULT 100,
  `joined_at` date NOT NULL,
  `left_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_user` (`project_id`,`user_id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `pm_team_members_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `pm_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_team_members`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_team_members` WRITE;
/*!40000 ALTER TABLE `pm_team_members` DISABLE KEYS */;
INSERT INTO `pm_team_members` VALUES
(1,1,1,'Project Manager',50,'2026-06-04',NULL,1,'2026-06-04 18:12:40'),
(2,1,2,'Credit Analyst',100,'2026-06-04',NULL,1,'2026-06-04 18:12:40'),
(3,1,3,'Dispute Specialist',75,'2026-06-04',NULL,1,'2026-06-04 18:12:40');
/*!40000 ALTER TABLE `pm_team_members` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pm_templates`
--

DROP TABLE IF EXISTS `pm_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pm_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `template_type` enum('credit_repair','dispute','consultation') DEFAULT 'credit_repair',
  `template_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`template_data`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_template_type` (`template_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pm_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pm_templates` WRITE;
/*!40000 ALTER TABLE `pm_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `pm_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `posters`
--

DROP TABLE IF EXISTS `posters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) unsigned DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'uncategorized',
  `tags` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_by` (`deleted_by`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posters`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `posters` WRITE;
/*!40000 ALTER TABLE `posters` DISABLE KEYS */;
INSERT INTO `posters` VALUES
(2,'poster_1.jpg','banner1.jpg','/uploads/posters/poster_1.jpg',102400,'2026-07-26 01:23:08','2026-07-26 11:37:37',1,NULL,NULL,NULL,NULL,NULL,NULL,'uncategorized',NULL,1,0,0,NULL,'2026-07-26 11:37:37'),
(4,'poster_3.png','logo.png','/uploads/posters/poster_3.png',51200,'2026-07-26 01:23:08','2026-07-26 11:39:13',1,NULL,NULL,NULL,NULL,NULL,NULL,'uncategorized',NULL,1,0,0,NULL,'2026-07-26 11:39:13'),
(6,'6a65f2154fc9a.png','ChatGPT Image Jul 18, 2026, 05_10_04 PM.png','/uploads/posters/6a65f2154fc9a.png',2064871,'2026-07-26 11:40:05','2026-07-26 11:41:08',1,NULL,NULL,NULL,NULL,NULL,NULL,'uncategorized',NULL,1,0,0,NULL,'2026-07-26 11:41:08'),
(7,'6a65f25fb8fc3.jpeg','WhatsApp Image 2025-12-16 at 15.38.20.jpeg','/uploads/posters/6a65f25fb8fc3.jpeg',94508,'2026-07-26 11:41:19',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uncategorized',NULL,1,0,0,NULL,'2026-07-26 11:41:19'),
(8,'1785342068_4be9721cf1aef880.jpg','test_poster.jpg','uploads/posters/1785342068_4be9721cf1aef880.jpg',4,'2026-07-29 16:21:08',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uncategorized',NULL,1,0,0,NULL,'2026-07-29 16:21:08');
/*!40000 ALTER TABLE `posters` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `privacy_logs`
--

DROP TABLE IF EXISTS `privacy_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `privacy_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_privacy_client` (`client_id`),
  KEY `idx_privacy_action` (`action`),
  CONSTRAINT `privacy_logs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `privacy_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `privacy_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `privacy_logs` WRITE;
/*!40000 ALTER TABLE `privacy_logs` DISABLE KEYS */;
INSERT INTO `privacy_logs` VALUES
(7,2,8,'data_access','Viewed credit report for Priya Sharma','192.168.1.1',NULL,'2026-08-01 17:24:03'),
(8,3,8,'consent_update','Updated consent for Suresh Singh','192.168.1.1',NULL,'2026-08-01 17:24:03'),
(9,4,16,'data_export','Exported client data for Meena Patel','192.168.1.2',NULL,'2026-08-01 17:24:03'),
(14,2,8,'Data Accessed',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:03'),
(15,3,8,'Data Modified',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:03'),
(16,4,8,'Consent Updated',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:03'),
(17,5,8,'Data Exported',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:03'),
(18,2,8,'Data Accessed',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:19'),
(19,3,8,'Data Modified',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:19'),
(20,4,8,'Consent Updated',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:19'),
(21,5,8,'Data Exported',NULL,'192.168.1.1',NULL,'2026-08-02 08:35:19');
/*!40000 ALTER TABLE `privacy_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `project_activity_log`
--

DROP TABLE IF EXISTS `project_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_activity_project` (`project_id`),
  CONSTRAINT `project_activity_log_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_activity_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_activity_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `project_activity_log` WRITE;
/*!40000 ALTER TABLE `project_activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_activity_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `project_comments`
--

DROP TABLE IF EXISTS `project_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_comments_project` (`project_id`),
  CONSTRAINT `project_comments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `project_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_comments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `project_comments` WRITE;
/*!40000 ALTER TABLE `project_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_comments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `project_milestones`
--

DROP TABLE IF EXISTS `project_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_milestones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `milestone_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','delayed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_milestones_project` (`project_id`),
  CONSTRAINT `project_milestones_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_milestones`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `project_milestones` WRITE;
/*!40000 ALTER TABLE `project_milestones` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_milestones` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `project_tasks`
--

DROP TABLE IF EXISTS `project_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `task_id` varchar(50) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` enum('todo','in_progress','review','done','blocked') DEFAULT 'todo',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `estimated_hours` decimal(10,2) DEFAULT 0.00,
  `actual_hours` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `dependencies` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `idx_tasks_project` (`project_id`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_assigned` (`assigned_to`),
  CONSTRAINT `project_tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_tasks_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_tasks_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_tasks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `project_tasks` WRITE;
/*!40000 ALTER TABLE `project_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `project_team`
--

DROP TABLE IF EXISTS `project_team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(100) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_user` (`project_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_team_project` (`project_id`),
  CONSTRAINT `project_team_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_team_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_team`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `project_team` WRITE;
/*!40000 ALTER TABLE `project_team` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_team` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `project_type` enum('internal','client','development','maintenance','support') DEFAULT 'internal',
  `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT 0.00,
  `actual_cost` decimal(15,2) DEFAULT 0.00,
  `project_manager` int(11) DEFAULT NULL,
  `team_lead` int(11) DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `health_score` int(11) DEFAULT 0,
  `risks` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`),
  UNIQUE KEY `project_code` (`project_code`),
  KEY `client_id` (`client_id`),
  KEY `project_manager` (`project_manager`),
  KEY `team_lead` (`team_lead`),
  KEY `created_by` (`created_by`),
  KEY `idx_projects_status` (`status`),
  KEY `idx_projects_progress` (`progress`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`project_manager`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_ibfk_3` FOREIGN KEY (`team_lead`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES
(1,'PRJ-2024-001','Website Redesign','WEB-2024-001','Complete redesign of company website with new branding',NULL,'internal','active','high','2026-07-02','2026-09-30',NULL,150000.00,0.00,NULL,NULL,45,0,NULL,NULL,NULL,'2026-08-01 18:55:38','2026-08-01 18:55:38'),
(2,'PRJ-2024-002','Mobile App Development','APP-2024-002','Develop mobile app for client portal',NULL,'internal','planning','high','2026-08-16','2026-11-29',NULL,250000.00,0.00,NULL,NULL,0,0,NULL,NULL,NULL,'2026-08-01 18:55:38','2026-08-01 18:55:38'),
(3,'PRJ-2024-003','Database Migration','DB-2024-003','Migrate legacy database to new infrastructure',NULL,'internal','active','critical','2026-07-17','2026-09-15',NULL,80000.00,0.00,NULL,NULL,60,0,NULL,NULL,NULL,'2026-08-01 18:55:38','2026-08-01 18:55:38'),
(4,'PRJ-2024-004','API Integration','API-2024-004','Integrate third-party payment gateway',NULL,'internal','on_hold','medium','2026-07-22','2026-08-31',NULL,50000.00,0.00,NULL,NULL,30,0,NULL,NULL,NULL,'2026-08-01 18:55:38','2026-08-01 18:55:38');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_agent_evaluations`
--

DROP TABLE IF EXISTS `qa_agent_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_agent_evaluations` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `scorecard_id` int(11) NOT NULL,
  `evaluation_date` date NOT NULL,
  `total_score` int(11) DEFAULT 0,
  `criteria_scores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`criteria_scores`)),
  `strengths` text DEFAULT NULL,
  `areas_for_improvement` text DEFAULT NULL,
  `action_plan` text DEFAULT NULL,
  `next_evaluation_date` date DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','closed') DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_agent` (`agent_id`),
  KEY `idx_evaluator` (`evaluator_id`),
  KEY `idx_evaluation_date` (`evaluation_date`),
  KEY `idx_qa_evaluations_composite` (`agent_id`,`evaluation_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_agent_evaluations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_agent_evaluations` WRITE;
/*!40000 ALTER TABLE `qa_agent_evaluations` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_agent_evaluations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_agent_performance`
--

DROP TABLE IF EXISTS `qa_agent_performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_agent_performance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `tickets_assigned` int(11) DEFAULT 0,
  `tickets_resolved` int(11) DEFAULT 0,
  `avg_response_time_minutes` int(11) DEFAULT 0,
  `avg_resolution_hours` decimal(5,2) DEFAULT 0.00,
  `csat_score` decimal(3,2) DEFAULT 0.00,
  `quality_score` decimal(5,2) DEFAULT 0.00,
  `first_contact_resolution_rate` decimal(5,2) DEFAULT 0.00,
  `escalation_rate` decimal(5,2) DEFAULT 0.00,
  `adherence_percentage` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agent_date` (`agent_id`,`date`),
  KEY `idx_date` (`date`),
  KEY `idx_agent` (`agent_id`),
  KEY `idx_qa_performance_composite` (`agent_id`,`date`,`quality_score`),
  CONSTRAINT `qa_agent_performance_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_agent_performance`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_agent_performance` WRITE;
/*!40000 ALTER TABLE `qa_agent_performance` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_agent_performance` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_bugs`
--

DROP TABLE IF EXISTS `qa_bugs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_bugs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bug_id` varchar(50) NOT NULL,
  `project_id` int(11) NOT NULL,
  `test_case_id` int(11) DEFAULT NULL,
  `bug_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `steps_to_reproduce` text DEFAULT NULL,
  `severity` enum('critical','major','minor','trivial') DEFAULT 'major',
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `status` enum('new','open','in_progress','fixed','verified','closed','reopened') DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `fixed_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `environment` varchar(255) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bug_id` (`bug_id`),
  KEY `project_id` (`project_id`),
  KEY `test_case_id` (`test_case_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `reported_by` (`reported_by`),
  KEY `fixed_by` (`fixed_by`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `qa_bugs_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `qa_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qa_bugs_ibfk_2` FOREIGN KEY (`test_case_id`) REFERENCES `test_cases` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_bugs_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_bugs_ibfk_4` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_bugs_ibfk_5` FOREIGN KEY (`fixed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_bugs_ibfk_6` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_bugs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_bugs` WRITE;
/*!40000 ALTER TABLE `qa_bugs` DISABLE KEYS */;
INSERT INTO `qa_bugs` VALUES
(1,'BUG-2024-001',1,2,'Login button not responding','Login button becomes unresponsive after 3 failed attempts','1. Go to login page\n2. Enter wrong password 3 times\n3. Click login button','critical','high','open',8,8,NULL,NULL,'Production','Chrome 120','Windows 11',NULL,NULL,NULL,NULL,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(2,'BUG-2024-002',1,2,'Password reset email delay','Password reset email takes more than 5 minutes to arrive','1. Request password reset\n2. Wait for email\n3. Check inbox','major','medium','in_progress',8,8,NULL,NULL,'Production','All','All',NULL,NULL,NULL,NULL,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(3,'BUG-2024-003',2,4,'App crashes on startup','Mobile app crashes on first launch after installation','1. Install app\n2. Launch app\n3. App crashes immediately','critical','high','new',8,8,NULL,NULL,'Production','N/A','Android 14',NULL,NULL,NULL,NULL,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(4,'BUG-2024-004',1,3,'Slow dashboard loading','Dashboard takes more than 5 seconds to load','1. Login\n2. Navigate to dashboard\n3. Wait for load','minor','low','fixed',8,8,NULL,NULL,'Staging','Chrome','Windows 11',NULL,NULL,NULL,NULL,'2026-08-01 19:52:58','2026-08-01 19:52:58');
/*!40000 ALTER TABLE `qa_bugs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_calibration_sessions`
--

DROP TABLE IF EXISTS `qa_calibration_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_calibration_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_name` varchar(255) NOT NULL,
  `session_date` datetime NOT NULL,
  `attendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`attendees`)),
  `reviewed_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`reviewed_items`)),
  `calibration_notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_session_date` (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_calibration_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_calibration_sessions` WRITE;
/*!40000 ALTER TABLE `qa_calibration_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_calibration_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_call_recordings`
--

DROP TABLE IF EXISTS `qa_call_recordings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_call_recordings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) NOT NULL,
  `recording_url` varchar(500) NOT NULL,
  `recording_duration` int(11) DEFAULT 0,
  `file_size` bigint(20) DEFAULT 0,
  `reviewed_by` int(11) DEFAULT NULL,
  `quality_score` int(11) DEFAULT 0 CHECK (`quality_score` between 0 and 100),
  `review_notes` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_call` (`call_id`),
  KEY `idx_reviewed_by` (`reviewed_by`),
  CONSTRAINT `qa_call_recordings_ibfk_1` FOREIGN KEY (`call_id`) REFERENCES `support_calls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_call_recordings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_call_recordings` WRITE;
/*!40000 ALTER TABLE `qa_call_recordings` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_call_recordings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_complaint_categories`
--

DROP TABLE IF EXISTS `qa_complaint_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_complaint_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `escalation_level` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_severity` (`severity`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_complaint_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_complaint_categories` WRITE;
/*!40000 ALTER TABLE `qa_complaint_categories` DISABLE KEYS */;
INSERT INTO `qa_complaint_categories` VALUES
(1,'Service Delay',NULL,'medium',1,'2026-06-04 18:47:55'),
(2,'Credit Report Error',NULL,'high',2,'2026-06-04 18:47:55'),
(3,'Billing Issue',NULL,'medium',1,'2026-06-04 18:47:55'),
(4,'Poor Customer Service',NULL,'medium',1,'2026-06-04 18:47:55'),
(5,'Dispute Resolution Failure',NULL,'high',2,'2026-06-04 18:47:55'),
(6,'Data Privacy Concern',NULL,'critical',3,'2026-06-04 18:47:55'),
(7,'Technical Issue',NULL,'low',1,'2026-06-04 18:47:55'),
(8,'Miscommunication',NULL,'low',1,'2026-06-04 18:47:55');
/*!40000 ALTER TABLE `qa_complaint_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_complaints`
--

DROP TABLE IF EXISTS `qa_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_complaints` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `complaint_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','investigating','resolved','closed','escalated') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `compensation_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `complaint_number` (`complaint_number`),
  KEY `idx_client` (`client_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_qa_complaints_composite` (`status`,`severity`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_complaints`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_complaints` WRITE;
/*!40000 ALTER TABLE `qa_complaints` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_complaints` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_metrics`
--

DROP TABLE IF EXISTS `qa_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) DEFAULT 0.00,
  `target_value` decimal(10,2) DEFAULT 0.00,
  `category` varchar(50) DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_metrics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_metrics` WRITE;
/*!40000 ALTER TABLE `qa_metrics` DISABLE KEYS */;
INSERT INTO `qa_metrics` VALUES
(1,1,'Test Coverage',85.50,90.00,'quality','2026-08-01 19:52:58'),
(2,1,'Bug Density',2.30,1.50,'quality','2026-08-01 19:52:58'),
(3,1,'Pass Rate',78.00,90.00,'quality','2026-08-01 19:52:58'),
(4,2,'Test Coverage',65.00,85.00,'quality','2026-08-01 19:52:58'),
(5,2,'Bug Density',3.10,1.50,'quality','2026-08-01 19:52:58'),
(6,2,'Pass Rate',60.00,85.00,'quality','2026-08-01 19:52:58'),
(7,1,'Automation Rate',45.00,70.00,'efficiency','2026-08-01 19:52:58'),
(8,1,'Defect Removal Efficiency',82.00,90.00,'efficiency','2026-08-01 19:52:58');
/*!40000 ALTER TABLE `qa_metrics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_projects`
--

DROP TABLE IF EXISTS `qa_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive','completed','archived') DEFAULT 'active',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `qa_lead` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`),
  KEY `fk_qaprojects_qa_lead` (`qa_lead`),
  KEY `fk_qaprojects_created_by` (`created_by`),
  CONSTRAINT `fk_qaprojects_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_qaprojects_qa_lead` FOREIGN KEY (`qa_lead`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_projects_ibfk_1` FOREIGN KEY (`qa_lead`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `qa_projects_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_projects`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_projects` WRITE;
/*!40000 ALTER TABLE `qa_projects` DISABLE KEYS */;
INSERT INTO `qa_projects` VALUES
(1,'QA-2024-001','Website Redesign QA','Quality assurance for website redesign project including all modules','active','high','2026-07-02','2026-09-30',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(2,'QA-2024-002','Mobile App Testing','Testing mobile app for client portal on iOS and Android','active','high','2026-07-17','2026-09-15',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(3,'QA-2024-003','API Integration QA','QA for third-party API integrations and data flow','active','medium','2026-07-22','2026-08-31',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(4,'QA-2024-004','Security Audit','Comprehensive security audit and penetration testing','completed','critical','2026-06-02','2026-08-01',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58');
/*!40000 ALTER TABLE `qa_projects` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_scorecards`
--

DROP TABLE IF EXISTS `qa_scorecards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_scorecards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scorecard_name` varchar(255) NOT NULL,
  `department` varchar(100) NOT NULL,
  `criteria` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`criteria`)),
  `weightage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`weightage`)),
  `passing_score` int(11) DEFAULT 70,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_scorecards`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_scorecards` WRITE;
/*!40000 ALTER TABLE `qa_scorecards` DISABLE KEYS */;
INSERT INTO `qa_scorecards` VALUES
(1,'Support Agent Scorecard','Customer Support','[\"Communication\",\"Product Knowledge\",\"Resolution Time\",\"Empathy\",\"Compliance\"]','[25,25,20,15,15]',75,1,1,'2026-06-04 18:47:55','2026-06-04 18:47:55'),
(2,'Credit Analyst Scorecard','Credit Analysis','[\"Accuracy\",\"Timeliness\",\"Documentation\",\"Communication\",\"Compliance\"]','[30,20,20,15,15]',80,1,1,'2026-06-04 18:47:55','2026-06-04 18:47:55');
/*!40000 ALTER TABLE `qa_scorecards` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_survey_responses`
--

DROP TABLE IF EXISTS `qa_survey_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_survey_responses` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `responses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`responses`)),
  `overall_rating` int(11) DEFAULT 0,
  `feedback_text` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_survey` (`survey_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `idx_qa_surveys_composite` (`survey_id`,`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_survey_responses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_survey_responses` WRITE;
/*!40000 ALTER TABLE `qa_survey_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `qa_survey_responses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_surveys`
--

DROP TABLE IF EXISTS `qa_surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_surveys` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `survey_name` varchar(255) NOT NULL,
  `survey_type` enum('csat','nps','feedback','post_resolution') DEFAULT 'csat',
  `questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`questions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_survey_type` (`survey_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_surveys`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_surveys` WRITE;
/*!40000 ALTER TABLE `qa_surveys` DISABLE KEYS */;
INSERT INTO `qa_surveys` VALUES
(1,'Customer Satisfaction Survey','csat','{\"questions\":[{\"q\":\"How satisfied are you with our service?\",\"type\":\"rating\"},{\"q\":\"Would you recommend us to others?\",\"type\":\"yes_no\"},{\"q\":\"Any additional feedback?\",\"type\":\"text\"}]}',1,1,'2026-06-04 18:47:55'),
(2,'Post-Resolution Survey','post_resolution','{\"questions\":[{\"q\":\"Was your issue resolved satisfactorily?\",\"type\":\"rating\"},{\"q\":\"How would you rate the agent?\",\"type\":\"rating\"},{\"q\":\"How can we improve?\",\"type\":\"text\"}]}',1,1,'2026-06-04 18:47:55');
/*!40000 ALTER TABLE `qa_surveys` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `qa_ticket_reviews`
--

DROP TABLE IF EXISTS `qa_ticket_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `qa_ticket_reviews` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `review_date` date NOT NULL,
  `quality_score` int(11) DEFAULT 0 CHECK (`quality_score` between 0 and 100),
  `resolution_accuracy` int(11) DEFAULT 0 CHECK (`resolution_accuracy` between 0 and 10),
  `communication_skills` int(11) DEFAULT 0 CHECK (`communication_skills` between 0 and 10),
  `empathy_score` int(11) DEFAULT 0 CHECK (`empathy_score` between 0 and 10),
  `compliance_score` int(11) DEFAULT 0 CHECK (`compliance_score` between 0 and 10),
  `response_time_score` int(11) DEFAULT 0 CHECK (`response_time_score` between 0 and 10),
  `comments` text DEFAULT NULL,
  `action_items` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_agent` (`agent_id`),
  KEY `idx_review_date` (`review_date`),
  CONSTRAINT `qa_ticket_reviews_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qa_ticket_reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `qa_ticket_reviews` WRITE;
/*!40000 ALTER TABLE `qa_ticket_reviews` DISABLE KEYS */;
INSERT INTO `qa_ticket_reviews` VALUES
(1,1,1,1,'2026-06-04',85,8,9,8,9,7,'Good work on this ticket',NULL,'2026-06-04 18:47:55');
/*!40000 ALTER TABLE `qa_ticket_reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(50) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_gst` varchar(15) DEFAULT NULL,
  `customer_pan` varchar(10) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_city` varchar(60) DEFAULT NULL,
  `customer_state` varchar(50) DEFAULT NULL,
  `customer_pincode` varchar(10) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `gst_amount` decimal(12,2) DEFAULT 0.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `total_with_gst` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','sent','accepted','rejected','expired','converted') DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quote_no` (`quote_no`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES
(1,'QUO20260001','Test Customer 1785028122721',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair',15000.00,18.00,2700.00,0.00,0.00,17700.00,'draft','2026-08-25',NULL,NULL,'2026-07-26 01:08:43','2026-07-26 01:08:43'),
(2,'QUO2026000002','Updated Customer Name','updated@example.com','9876543210','','','','Mumbai','Maharashtra','','Premium CIBIL Repair',25000.00,18.00,4500.00,2250.00,2250.00,29500.00,'sent','2026-08-28','Updated via API',0,'2026-07-29 15:32:20','2026-07-29 15:33:57');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `rbi_complaints`
--

DROP TABLE IF EXISTS `rbi_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rbi_complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `complaint_id` varchar(50) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `filed_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_rbi_customer` (`client_id`),
  CONSTRAINT `fk_rbi_customer` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rbi_complaints`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `rbi_complaints` WRITE;
/*!40000 ALTER TABLE `rbi_complaints` DISABLE KEYS */;
INSERT INTO `rbi_complaints` VALUES
(1,3,'RBI-2024-001','HDFC Bank','2026-08-02','pending','2026-08-02 08:29:12'),
(2,5,'RBI-2024-002','SBI Bank','2026-08-02','under_review','2026-08-02 08:29:12');
/*!40000 ALTER TABLE `rbi_complaints` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `real_estate_agents`
--

DROP TABLE IF EXISTS `real_estate_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `real_estate_agents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `agency_name` varchar(150) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT 0,
  `address` text DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_agents_status` (`status`),
  KEY `idx_agents_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `real_estate_agents`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `real_estate_agents` WRITE;
/*!40000 ALTER TABLE `real_estate_agents` DISABLE KEYS */;
/*!40000 ALTER TABLE `real_estate_agents` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reconciliation`
--

DROP TABLE IF EXISTS `reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `bank_amount` decimal(12,2) DEFAULT NULL,
  `system_amount` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','reconciled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reconciliation`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reconciliation` WRITE;
/*!40000 ALTER TABLE `reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `reconciliation` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `referral_commissions`
--

DROP TABLE IF EXISTS `referral_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referral_code` varchar(50) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_code` (`referral_code`),
  KEY `idx_partner` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_commissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `referral_commissions` WRITE;
/*!40000 ALTER TABLE `referral_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_commissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `referral_tracking`
--

DROP TABLE IF EXISTS `referral_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `referral_code` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referred_url` varchar(255) DEFAULT NULL,
  `status` enum('click','signup','converted') DEFAULT 'click',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_code` (`referral_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_tracking`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `referral_tracking` WRITE;
/*!40000 ALTER TABLE `referral_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_tracking` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `referrals`
--

DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `referrals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `type` enum('partner','connector','client') DEFAULT 'partner',
  `referral_code` varchar(50) DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `earnings` decimal(10,2) DEFAULT 0.00,
  `leads_referred` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referrals`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `referrals` WRITE;
/*!40000 ALTER TABLE `referrals` DISABLE KEYS */;
/*!40000 ALTER TABLE `referrals` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `registration_codes`
--

DROP TABLE IF EXISTS `registration_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registration_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  `assigned_to_email` varchar(100) DEFAULT NULL,
  `used_by_user_id` int(11) DEFAULT NULL,
  `expiry_days` int(11) DEFAULT 30,
  `expires_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registration_codes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `registration_codes` WRITE;
/*!40000 ALTER TABLE `registration_codes` DISABLE KEYS */;
INSERT INTO `registration_codes` VALUES
(2,'CLNT-M3N7P1','client','',NULL,15,'2025-05-15 00:00:00',1,NULL,1,'2026-07-19 07:51:56'),
(3,'CIBIL-6104167A','client','testclient@example.com',NULL,30,'2026-08-24 23:50:25',0,NULL,1,'2026-07-25 23:50:25'),
(4,'CIBIL-6B6E17CB','client','newclient@example.com',NULL,30,'2026-08-24 23:54:19',0,NULL,1,'2026-07-25 23:54:19'),
(5,'PRTN-A8X2K9','partner','newpartner@example.com',NULL,30,'2026-09-24 01:10:45',0,NULL,1,'2026-07-26 01:10:45'),
(8,'CIBIL-49CB024B','client','test@example.com',NULL,30,'2026-08-25 01:11:00',0,NULL,1,'2026-07-26 01:11:00'),
(9,'CIBIL-89FC56E2','partner','partner@example.com',NULL,30,'2026-08-27 06:51:41',0,NULL,1,'2026-07-28 06:51:41'),
(10,'CIBIL-5C98C1A5','partner','partner@example.com',NULL,30,'2026-08-27 06:52:07',0,NULL,1,'2026-07-28 06:52:07'),
(11,'CIBIL-ECD4CEFB','partner','partner@example.com',NULL,30,'2026-08-27 06:52:30',0,NULL,1,'2026-07-28 06:52:30'),
(12,'CIBIL-3AC99C6B','partner','',NULL,30,'2026-08-27 06:53:00',0,NULL,1,'2026-07-28 06:53:00'),
(13,'CIBIL-36E35915','partner','',NULL,30,'2026-08-27 06:53:05',0,NULL,1,'2026-07-28 06:53:05'),
(14,'CIBIL-1F0A5E46','partner','',NULL,30,'2026-08-27 06:54:17',0,NULL,1,'2026-07-28 06:54:17'),
(15,'CIBIL-81EEB5FD','partner','',NULL,30,'2026-08-27 06:55:29',0,NULL,1,'2026-07-28 06:55:29'),
(16,'12345678901234567890123456789012345678901234567890','partner','partner@example.com',NULL,7,'2026-08-05 12:11:35',0,NULL,NULL,'2026-07-29 12:11:35'),
(17,'24aacf268c57030f45830d8191270ecfc191d80221891a4c3d','partner','newpartner@example.com',NULL,7,'2026-08-05 12:11:49',0,NULL,NULL,'2026-07-29 12:11:49');
/*!40000 ALTER TABLE `registration_codes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `repair_strategies`
--

DROP TABLE IF EXISTS `repair_strategies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_strategies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_type` varchar(100) DEFAULT NULL,
  `strategy` text DEFAULT NULL,
  `estimated_days` int(11) DEFAULT NULL,
  `success_rate` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_strategies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `repair_strategies` WRITE;
/*!40000 ALTER TABLE `repair_strategies` DISABLE KEYS */;
INSERT INTO `repair_strategies` VALUES
(1,'Written Off Account','File dispute with bank, obtain NOC, escalate to RBI if needed',45,90,'2026-06-02 18:07:44'),
(2,'Settled Account','Request closure letter, file with bureau for status update',30,85,'2026-06-02 18:07:44'),
(3,'Late Payment','Submit proof of on-time payment, request correction',20,75,'2026-06-02 18:07:44'),
(4,'Incorrect Enquiry','File dispute as unauthorized enquiry',15,85,'2026-06-02 18:07:44'),
(5,'Duplicate Loan','Request bank to report correct status, file merger request',25,80,'2026-06-02 18:07:44'),
(6,'Identity Mismatch','Submit KYC documents for correction across all bureaus',20,90,'2026-06-02 18:07:44');
/*!40000 ALTER TABLE `repair_strategies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reply_templates`
--

DROP TABLE IF EXISTS `reply_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reply_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `template` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reply_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reply_templates` WRITE;
/*!40000 ALTER TABLE `reply_templates` DISABLE KEYS */;
INSERT INTO `reply_templates` VALUES
(1,'Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
(2,'Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
(3,'Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
(4,'Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.'),
(5,'Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
(6,'Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
(7,'Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
(8,'Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.'),
(9,'Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
(10,'Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
(11,'Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
(12,'Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.'),
(13,'Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
(14,'Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
(15,'Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
(16,'Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.');
/*!40000 ALTER TABLE `reply_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `report_history`
--

DROP TABLE IF EXISTS `report_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `format` enum('pdf','excel','csv') DEFAULT 'pdf',
  `generated_at` datetime DEFAULT current_timestamp(),
  `downloaded_at` datetime DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `email_sent` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_generated_at` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `report_history` WRITE;
/*!40000 ALTER TABLE `report_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `report_templates`
--

DROP TABLE IF EXISTS `report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `report_type` enum('leads','commission','payouts','customers','performance','combined') DEFAULT 'leads',
  `columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`columns`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `date_range` enum('today','yesterday','week','month','quarter','year','custom') DEFAULT 'month',
  `sort_by` varchar(50) DEFAULT NULL,
  `sort_order` enum('ASC','DESC') DEFAULT 'DESC',
  `group_by` varchar(50) DEFAULT NULL,
  `chart_type` enum('bar','line','pie','table') DEFAULT 'table',
  `is_favorite` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_is_favorite` (`is_favorite`),
  CONSTRAINT `report_templates_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `report_templates` WRITE;
/*!40000 ALTER TABLE `report_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES
(1,'Maneet Singh','maneet@example.com',5,'Thank you sir, thank you very much for doing my work in a very short time. Unauthorized loan removed from my wife\'s CIBIL within weeks.','approved','2026-05-02 11:34:26'),
(2,'Sunil Enterprises','sunil@example.com',5,'Excellent work done by this company. My credit score improved by 87 points after settled account removal.','approved','2026-05-02 11:34:26'),
(3,'Ranjeet Thakur','ranjeet@example.com',5,'Very professional team. They removed a suit filed entry from my CIBIL that was holding back my loan approval.','approved','2026-05-02 11:34:26'),
(7,'Rajesh Kumar','rajesh@example.com',5,'Excellent service! CIBIL Repair helped me improve my score significantly.','pending','2026-07-29 14:34:47'),
(8,'Priya Sharma','priya@example.com',5,'Great service! My CIBIL score improved by 100 points.','pending','2026-07-29 14:35:06'),
(9,'Test User','test_1785342318702@example.com',5,'Great service!','pending','2026-07-29 16:25:18'),
(10,'Test User','test_1785342381075@example.com',5,'Great service!','pending','2026-07-29 16:26:21'),
(11,'Test User','test_1785342651566@example.com',5,'Great service!','pending','2026-07-29 16:30:51'),
(12,'Test User','test_1785342739525@example.com',5,'Great service!','pending','2026-07-29 16:32:19'),
(13,'Console Review','review_1785342878459@test.com',5,'Great service! Test from console.','pending','2026-07-29 16:34:38'),
(14,'Check Review','check@review.com',5,'Great service!','pending','2026-07-29 16:37:59');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_assessments`
--

DROP TABLE IF EXISTS `risk_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `risk_score` int(11) DEFAULT 0,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'low',
  `credit_risk_score` int(11) DEFAULT 0,
  `fraud_risk_score` int(11) DEFAULT 0,
  `compliance_risk_score` int(11) DEFAULT 0,
  `operational_risk_score` int(11) DEFAULT 0,
  `factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`factors`)),
  `recommendations` text DEFAULT NULL,
  `assessed_by` int(11) DEFAULT NULL,
  `status` enum('pending','reviewed','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `assessed_by` (`assessed_by`),
  KEY `idx_risk_client` (`client_id`),
  KEY `idx_risk_status` (`status`),
  KEY `idx_risk_level` (`risk_level`),
  CONSTRAINT `risk_assessments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `risk_assessments_ibfk_2` FOREIGN KEY (`assessed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_assessments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_assessments` WRITE;
/*!40000 ALTER TABLE `risk_assessments` DISABLE KEYS */;
INSERT INTO `risk_assessments` VALUES
(1,2,'2026-08-01',75,'high',80,70,75,65,NULL,NULL,NULL,'reviewed','2026-08-01 18:10:32','2026-08-01 18:10:32'),
(2,3,'2026-07-25',45,'medium',50,40,55,35,NULL,NULL,NULL,'approved','2026-08-01 18:10:32','2026-08-01 18:10:32'),
(3,4,'2026-07-18',85,'critical',90,85,80,75,NULL,NULL,NULL,'pending','2026-08-01 18:10:32','2026-08-01 18:10:32'),
(4,5,'2026-07-29',30,'low',35,25,40,20,NULL,NULL,NULL,'reviewed','2026-08-01 18:10:32','2026-08-01 18:10:32'),
(5,2,'2026-08-01',75,'high',80,70,75,65,NULL,NULL,8,'reviewed','2026-08-01 18:43:24','2026-08-01 18:43:24'),
(6,3,'2026-07-25',45,'medium',50,40,55,35,NULL,NULL,8,'approved','2026-08-01 18:43:24','2026-08-01 18:43:24'),
(7,4,'2026-07-18',85,'critical',90,85,80,75,NULL,NULL,NULL,'pending','2026-08-01 18:43:24','2026-08-01 18:43:24'),
(8,5,'2026-07-29',30,'low',35,25,40,20,NULL,NULL,8,'reviewed','2026-08-01 18:43:24','2026-08-01 18:43:24'),
(9,2,'2026-08-01',75,'high',80,70,75,65,NULL,NULL,8,'reviewed','2026-08-01 18:44:17','2026-08-01 18:44:17'),
(10,3,'2026-07-25',45,'medium',50,40,55,35,NULL,NULL,8,'approved','2026-08-01 18:44:17','2026-08-01 18:44:17'),
(11,4,'2026-07-18',85,'critical',90,85,80,75,NULL,NULL,NULL,'pending','2026-08-01 18:44:17','2026-08-01 18:44:17'),
(12,5,'2026-07-29',30,'low',35,25,40,20,NULL,NULL,8,'reviewed','2026-08-01 18:44:17','2026-08-01 18:44:17'),
(13,6,'2026-07-27',55,'medium',60,50,55,45,NULL,NULL,NULL,'pending','2026-08-01 18:44:17','2026-08-01 18:44:17');
/*!40000 ALTER TABLE `risk_assessments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_audit_findings`
--

DROP TABLE IF EXISTS `risk_audit_findings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_audit_findings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_type` enum('internal','external','regulatory','security') NOT NULL,
  `finding_title` varchar(255) NOT NULL,
  `finding_description` longtext NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `department` varchar(100) NOT NULL,
  `discovered_date` date NOT NULL,
  `reported_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `target_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `status` enum('open','in_progress','remediated','waived','closed') DEFAULT 'open',
  `remediation_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_type` (`audit_type`),
  KEY `idx_status` (`status`),
  KEY `idx_discovered_date` (`discovered_date`),
  KEY `idx_audit_composite` (`status`,`discovered_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_audit_findings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_audit_findings` WRITE;
/*!40000 ALTER TABLE `risk_audit_findings` DISABLE KEYS */;
INSERT INTO `risk_audit_findings` VALUES
(1,'internal','Incomplete KYC Documentation','5 client records missing Aadhaar verification','high','Operations','0000-00-00',1,NULL,NULL,NULL,'in_progress',NULL,'2026-06-04 18:02:25','2026-06-04 18:02:25');
/*!40000 ALTER TABLE `risk_audit_findings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_compliance_breaches`
--

DROP TABLE IF EXISTS `risk_compliance_breaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_compliance_breaches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `breach_type` enum('data_privacy','kyc_violation','sla_breach','document_forgery','unauthorized_access') NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` text NOT NULL,
  `affected_entity` varchar(100) DEFAULT NULL,
  `detected_at` timestamp NULL DEFAULT current_timestamp(),
  `reported_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('open','investigating','mitigated','closed') DEFAULT 'open',
  `mitigation_plan` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `penalty_amount` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_breach_type` (`breach_type`),
  KEY `idx_status` (`status`),
  KEY `idx_detected_at` (`detected_at`),
  KEY `idx_compliance_composite` (`status`,`detected_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_compliance_breaches`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_compliance_breaches` WRITE;
/*!40000 ALTER TABLE `risk_compliance_breaches` DISABLE KEYS */;
INSERT INTO `risk_compliance_breaches` VALUES
(1,'sla_breach','high','Ticket #TKT-001 missed SLA response time by 4 hours',NULL,'2026-06-04 18:02:25',1,NULL,'investigating',NULL,NULL,0.00);
/*!40000 ALTER TABLE `risk_compliance_breaches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_fraud_alerts`
--

DROP TABLE IF EXISTS `risk_fraud_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_fraud_alerts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `alert_type` enum('suspicious_login','unusual_activity','duplicate_account','fake_document','payment_fraud','identity_theft') NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `alert_details` text NOT NULL,
  `triggered_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `resolution_status` enum('pending','investigating','confirmed','false_positive','resolved') DEFAULT 'pending',
  `resolution_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`resolution_status`),
  KEY `idx_triggered_at` (`triggered_at`),
  KEY `idx_fraud_alerts_composite` (`severity`,`resolution_status`,`triggered_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_fraud_alerts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_fraud_alerts` WRITE;
/*!40000 ALTER TABLE `risk_fraud_alerts` DISABLE KEYS */;
INSERT INTO `risk_fraud_alerts` VALUES
(1,'suspicious_login','user',1,'medium','Multiple login attempts from different locations within 10 minutes','2026-06-04 16:02:25',NULL,NULL,'pending',NULL);
/*!40000 ALTER TABLE `risk_fraud_alerts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_kyc_assessment`
--

DROP TABLE IF EXISTS `risk_kyc_assessment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_kyc_assessment` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `document_quality_score` decimal(5,2) DEFAULT 0.00,
  `identity_verification_status` enum('pending','verified','failed','suspicious') DEFAULT 'pending',
  `document_matches` tinyint(1) DEFAULT 0,
  `face_match_score` decimal(5,2) DEFAULT 0.00,
  `address_verification_status` enum('pending','verified','failed') DEFAULT 'pending',
  `risk_indicators` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_indicators`)),
  `final_risk_score` decimal(5,2) DEFAULT 0.00,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'low',
  `assessed_at` timestamp NULL DEFAULT current_timestamp(),
  `assessed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_risk_level` (`risk_level`),
  KEY `idx_verification` (`identity_verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_kyc_assessment`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_kyc_assessment` WRITE;
/*!40000 ALTER TABLE `risk_kyc_assessment` DISABLE KEYS */;
/*!40000 ALTER TABLE `risk_kyc_assessment` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_metrics`
--

DROP TABLE IF EXISTS `risk_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,2) DEFAULT 0.00,
  `target_value` decimal(10,2) DEFAULT 0.00,
  `category` varchar(50) DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_metrics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_metrics` WRITE;
/*!40000 ALTER TABLE `risk_metrics` DISABLE KEYS */;
INSERT INTO `risk_metrics` VALUES
(1,'Overall Risk Score',58.50,50.00,'overall','2026-08-01 18:43:24'),
(2,'Credit Risk',63.80,55.00,'credit','2026-08-01 18:43:24'),
(3,'Fraud Risk',52.30,45.00,'fraud','2026-08-01 18:43:24'),
(4,'Compliance Risk',56.70,50.00,'compliance','2026-08-01 18:43:24'),
(5,'Operational Risk',45.20,40.00,'operational','2026-08-01 18:43:24'),
(6,'Overall Risk Score',58.50,50.00,'overall','2026-08-01 18:44:17'),
(7,'Credit Risk',63.80,55.00,'credit','2026-08-01 18:44:17'),
(8,'Fraud Risk',52.30,45.00,'fraud','2026-08-01 18:44:17'),
(9,'Compliance Risk',56.70,50.00,'compliance','2026-08-01 18:44:17'),
(10,'Operational Risk',45.20,40.00,'operational','2026-08-01 18:44:17');
/*!40000 ALTER TABLE `risk_metrics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_mitigation_plans`
--

DROP TABLE IF EXISTS `risk_mitigation_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_mitigation_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `risk_type` varchar(100) NOT NULL,
  `plan` text NOT NULL,
  `timeline` varchar(100) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `idx_mitigation_client` (`client_id`),
  KEY `idx_mitigation_status` (`status`),
  CONSTRAINT `risk_mitigation_plans_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `risk_mitigation_plans_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_mitigation_plans`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_mitigation_plans` WRITE;
/*!40000 ALTER TABLE `risk_mitigation_plans` DISABLE KEYS */;
INSERT INTO `risk_mitigation_plans` VALUES
(1,4,'Fraud Risk','Immediate account freeze and investigation','24 hours','in_progress',8,'2026-08-01 18:43:24',NULL),
(2,2,'High Risk','Enhanced monitoring and review','7 days','pending',8,'2026-08-01 18:43:24',NULL),
(3,3,'Compliance Risk','Submit corrected PAN document','3 days','in_progress',8,'2026-08-01 18:43:24',NULL),
(4,4,'Fraud Risk','Immediate account freeze and investigation of all recent transactions','24 hours','in_progress',8,'2026-08-01 18:44:17',NULL),
(5,2,'High Risk','Enhanced monitoring and review of all account activities for 30 days','7 days','pending',8,'2026-08-01 18:44:17',NULL),
(6,3,'Compliance Risk','Submit corrected PAN document and update KYC records','3 days','in_progress',8,'2026-08-01 18:44:17',NULL),
(7,5,'Medium Risk','Regular monitoring and quarterly review','15 days','pending',16,'2026-08-01 18:44:17',NULL);
/*!40000 ALTER TABLE `risk_mitigation_plans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_profiles`
--

DROP TABLE IF EXISTS `risk_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('client','transaction','dispute','user') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `risk_score` decimal(5,2) DEFAULT 0.00,
  `risk_level` enum('low','medium','high','critical') DEFAULT 'low',
  `risk_factors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`risk_factors`)),
  `last_assessed_at` timestamp NULL DEFAULT current_timestamp(),
  `assessed_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_risk_level` (`risk_level`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_risk_profiles_composite` (`risk_level`,`risk_score`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_profiles` WRITE;
/*!40000 ALTER TABLE `risk_profiles` DISABLE KEYS */;
INSERT INTO `risk_profiles` VALUES
(1,'client',1,15.50,'low',NULL,'2026-06-04 18:02:25',1,'Regular client with good history','2026-06-04 18:02:25','2026-06-04 18:02:25');
/*!40000 ALTER TABLE `risk_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_update_risk_level
BEFORE UPDATE ON risk_profiles
FOR EACH ROW
BEGIN
    IF NEW.risk_score >= 80 THEN
        SET NEW.risk_level = 'critical';
    ELSEIF NEW.risk_score >= 60 THEN
        SET NEW.risk_level = 'high';
    ELSEIF NEW.risk_score >= 30 THEN
        SET NEW.risk_level = 'medium';
    ELSE
        SET NEW.risk_level = 'low';
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `risk_rules_config`
--

DROP TABLE IF EXISTS `risk_rules_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_rules_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(200) NOT NULL,
  `rule_category` varchar(100) NOT NULL,
  `rule_condition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`rule_condition`)),
  `action_taken` varchar(255) DEFAULT NULL,
  `risk_score_weight` int(11) DEFAULT 1,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rule_name` (`rule_name`),
  KEY `idx_category` (`rule_category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_rules_config`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_rules_config` WRITE;
/*!40000 ALTER TABLE `risk_rules_config` DISABLE KEYS */;
INSERT INTO `risk_rules_config` VALUES
(1,'Multiple Failed Logins','authentication','{\"attempts\": \">5\", \"timeframe\": \"15_minutes\"}',NULL,8,'high',1,1,'2026-06-04 18:02:25','2026-06-04 18:02:25'),
(2,'Unusual Transaction Amount','transaction','{\"amount\": \">50000\", \"frequency\": \"daily\"}',NULL,7,'high',1,1,'2026-06-04 18:02:25','2026-06-04 18:02:25'),
(3,'Multiple IP Addresses','session','{\"different_ips\": \">3\", \"timeframe\": \"1_hour\"}',NULL,5,'medium',1,1,'2026-06-04 18:02:25','2026-06-04 18:02:25'),
(4,'Document Mismatch','kyc','{\"pan_aadhaar_name\": \"mismatch\"}',NULL,10,'critical',1,1,'2026-06-04 18:02:25','2026-06-04 18:02:25'),
(5,'Rapid Successive Transactions','transaction','{\"count\": \">10\", \"timeframe\": \"1_hour\"}',NULL,9,'critical',1,1,'2026-06-04 18:02:25','2026-06-04 18:02:25'),
(6,'Suspicious Location','login','{\"country\": \"high_risk\"}',NULL,6,'high',1,1,'2026-06-04 18:02:25','2026-06-04 18:02:25');
/*!40000 ALTER TABLE `risk_rules_config` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_suspicious_activities`
--

DROP TABLE IF EXISTS `risk_suspicious_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_suspicious_activities` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `activity_details` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `risk_score` int(11) DEFAULT 0,
  `is_blocked` tinyint(1) DEFAULT 0,
  `flagged_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_flagged_at` (`flagged_at`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_suspicious_composite` (`flagged_at`,`risk_score`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_suspicious_activities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_suspicious_activities` WRITE;
/*!40000 ALTER TABLE `risk_suspicious_activities` DISABLE KEYS */;
INSERT INTO `risk_suspicious_activities` VALUES
(1,1,'client','rapid_page_access','Accessed 50+ pages in 2 minutes','192.168.1.100',NULL,75,0,'2026-06-04 18:02:25',NULL,NULL);
/*!40000 ALTER TABLE `risk_suspicious_activities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `risk_transaction_monitoring`
--

DROP TABLE IF EXISTS `risk_transaction_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `risk_transaction_monitoring` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(100) NOT NULL,
  `client_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `device_fingerprint` varchar(255) DEFAULT NULL,
  `risk_score` decimal(5,2) DEFAULT 0.00,
  `is_fraudulent` tinyint(1) DEFAULT 0,
  `fraud_reason` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_risk_score` (`risk_score`),
  KEY `idx_transaction_date` (`created_at`),
  KEY `idx_is_fraudulent` (`is_fraudulent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `risk_transaction_monitoring`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `risk_transaction_monitoring` WRITE;
/*!40000 ALTER TABLE `risk_transaction_monitoring` DISABLE KEYS */;
/*!40000 ALTER TABLE `risk_transaction_monitoring` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_high_risk_transaction
AFTER INSERT ON risk_transaction_monitoring
FOR EACH ROW
BEGIN
    IF NEW.risk_score >= 70 THEN
        INSERT INTO risk_fraud_alerts (alert_type, entity_type, entity_id, severity, alert_details, triggered_at)
        VALUES ('unusual_activity', 'transaction', NEW.id, 'high', CONCAT('High-risk transaction detected: ', NEW.transaction_id), NOW());
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES
(1,1,11,'2026-06-02 18:48:31'),
(2,1,12,'2026-06-02 18:48:31'),
(3,1,13,'2026-06-02 18:48:31'),
(4,1,14,'2026-06-02 18:48:31'),
(5,1,15,'2026-06-02 18:48:31'),
(6,1,6,'2026-06-02 18:48:31'),
(7,1,7,'2026-06-02 18:48:31'),
(8,1,8,'2026-06-02 18:48:31'),
(9,1,9,'2026-06-02 18:48:31'),
(10,1,10,'2026-06-02 18:48:31'),
(11,1,16,'2026-06-02 18:48:31'),
(12,1,17,'2026-06-02 18:48:31'),
(13,1,18,'2026-06-02 18:48:31'),
(14,1,19,'2026-06-02 18:48:31'),
(15,1,29,'2026-06-02 18:48:31'),
(16,1,30,'2026-06-02 18:48:31'),
(17,1,31,'2026-06-02 18:48:31'),
(18,1,32,'2026-06-02 18:48:31'),
(19,1,33,'2026-06-02 18:48:31'),
(20,1,25,'2026-06-02 18:48:31'),
(21,1,26,'2026-06-02 18:48:31'),
(22,1,27,'2026-06-02 18:48:31'),
(23,1,28,'2026-06-02 18:48:31'),
(24,1,20,'2026-06-02 18:48:31'),
(25,1,21,'2026-06-02 18:48:31'),
(26,1,22,'2026-06-02 18:48:31'),
(27,1,23,'2026-06-02 18:48:31'),
(28,1,24,'2026-06-02 18:48:31'),
(29,1,34,'2026-06-02 18:48:31'),
(30,1,35,'2026-06-02 18:48:31'),
(31,1,36,'2026-06-02 18:48:31'),
(32,1,37,'2026-06-02 18:48:31'),
(33,1,38,'2026-06-02 18:48:31'),
(34,1,39,'2026-06-02 18:48:31'),
(35,1,40,'2026-06-02 18:48:31'),
(36,1,41,'2026-06-02 18:48:31'),
(37,1,1,'2026-06-02 18:48:31'),
(38,1,2,'2026-06-02 18:48:31'),
(39,1,3,'2026-06-02 18:48:31'),
(40,1,4,'2026-06-02 18:48:31'),
(41,1,5,'2026-06-02 18:48:31'),
(64,2,26,'2026-06-02 18:48:31'),
(65,2,14,'2026-06-02 18:48:31'),
(66,2,15,'2026-06-02 18:48:31'),
(67,2,12,'2026-06-02 18:48:31'),
(68,2,7,'2026-06-02 18:48:31'),
(69,2,21,'2026-06-02 18:48:31'),
(70,2,2,'2026-06-02 18:48:31'),
(71,2,13,'2026-06-02 18:48:31'),
(72,2,8,'2026-06-02 18:48:31'),
(73,2,3,'2026-06-02 18:48:31'),
(74,2,35,'2026-06-02 18:48:31'),
(75,2,17,'2026-06-02 18:48:31'),
(76,2,24,'2026-06-02 18:48:31'),
(77,2,38,'2026-06-02 18:48:31'),
(78,2,18,'2026-06-02 18:48:31'),
(79,2,37,'2026-06-02 18:48:31'),
(80,2,39,'2026-06-02 18:48:31'),
(81,2,11,'2026-06-02 18:48:31'),
(82,2,6,'2026-06-02 18:48:31'),
(83,2,36,'2026-06-02 18:48:31'),
(84,2,16,'2026-06-02 18:48:31'),
(85,2,23,'2026-06-02 18:48:31'),
(86,2,25,'2026-06-02 18:48:31'),
(87,2,20,'2026-06-02 18:48:31'),
(88,2,34,'2026-06-02 18:48:31'),
(89,2,1,'2026-06-02 18:48:31'),
(95,3,31,'2026-06-02 18:48:31'),
(96,3,35,'2026-06-02 18:48:31'),
(97,3,30,'2026-06-02 18:48:31'),
(98,3,33,'2026-06-02 18:48:31'),
(99,3,32,'2026-06-02 18:48:31'),
(100,3,36,'2026-06-02 18:48:31'),
(101,3,29,'2026-06-02 18:48:31'),
(102,3,34,'2026-06-02 18:48:31'),
(110,4,21,'2026-06-02 18:48:31'),
(111,4,35,'2026-06-02 18:48:31'),
(112,4,24,'2026-06-02 18:48:31'),
(113,4,27,'2026-06-02 18:48:31'),
(114,4,22,'2026-06-02 18:48:31'),
(115,4,36,'2026-06-02 18:48:31'),
(116,4,23,'2026-06-02 18:48:31'),
(117,4,25,'2026-06-02 18:48:31'),
(118,4,20,'2026-06-02 18:48:31'),
(119,4,34,'2026-06-02 18:48:31');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `level` int(11) DEFAULT 0,
  `is_system` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_name` (`name`),
  KEY `idx_level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'super_admin','Super Administrator','Full system access with all permissions',1,1,'2026-06-02 18:48:31'),
(2,'admin','Administrator','Platform administrator',2,1,'2026-06-02 18:48:31'),
(3,'hr_manager','HR Manager','Manage employees, attendance, payroll',3,1,'2026-06-02 18:48:31'),
(4,'finance_manager','Finance Manager','Manage payments, invoices, commissions',4,1,'2026-06-02 18:48:31'),
(5,'credit_analyst','Credit Analyst','Analyze credit reports and issues',5,1,'2026-06-02 18:48:31'),
(6,'dispute_team','Dispute Team','Process disputes with bureaus and banks',6,1,'2026-06-02 18:48:31'),
(7,'support_agent','Support Agent','Handle customer support tickets',7,1,'2026-06-02 18:48:31'),
(8,'sales_executive','Sales Executive','Manage leads and client acquisition',8,1,'2026-06-02 18:48:31'),
(9,'operations_manager','Operations Manager','Manage team workload and SLAs',9,1,'2026-06-02 18:48:31'),
(10,'partner','Partner','External partner/agent',10,1,'2026-06-02 18:48:31'),
(11,'client','Client','End customer',11,1,'2026-06-02 18:48:31'),
(12,'employee','Employee','Internal employee',12,1,'2026-06-02 18:48:31');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_gst` varchar(15) DEFAULT NULL,
  `customer_pan` varchar(10) DEFAULT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_city` varchar(60) DEFAULT NULL,
  `customer_state` varchar(50) DEFAULT NULL,
  `customer_pincode` varchar(10) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `gst_rate` decimal(5,2) DEFAULT 18.00,
  `gst_amount` decimal(12,2) DEFAULT 0.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `total_with_gst` decimal(12,2) DEFAULT 0.00,
  `is_gst_applicable` tinyint(1) DEFAULT 1,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `invoice_no` varchar(50) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `partner_id` int(11) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `sale_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES
(2,2,'Priya Sharma','priya@example.com','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Settled',22000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,2640.00,NULL,'completed','2025-02-15','2026-07-19 07:51:56'),
(3,3,'Meena Patel','meena@example.com','9876543213',NULL,NULL,NULL,NULL,NULL,NULL,'Profile Correction',8000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,960.00,NULL,'completed','2025-03-10','2026-07-19 07:51:56'),
(4,4,'Arun Verma','arun@example.com','9876543220',NULL,NULL,NULL,NULL,NULL,NULL,'Written Off',18000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,2160.00,NULL,'completed','2025-04-05','2026-07-19 07:51:56'),
(5,5,'Kavita Gupta','kavita@example.com','9876543221',NULL,NULL,NULL,NULL,NULL,NULL,'Settled',25000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,3000.00,NULL,'pending','2025-04-18','2026-07-19 07:51:56'),
(6,6,'Deepika Rao','deepika@email.com','9876541005',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair',15000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,1800.00,NULL,'completed','2026-07-19','2026-07-19 07:54:26'),
(7,6,'Deepika Rao','deepika@email.com','9876541005',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair',15000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,1800.00,NULL,'completed','2026-07-19','2026-07-19 07:56:24'),
(8,0,'Rajesh Kumar','rajesh@example.com','9876543210','22AAAAA0000A1Z5','AAAAA1234A','','','','','CIBIL Repair',15000.00,18.00,2700.00,1350.00,1350.00,17700.00,1,'UPI','',0.00,0,'completed','2026-07-25','2026-07-25 22:22:39'),
(9,0,'Test Sale 1785022455645','sale1785022455645@test.com','9876543237','','','','','','','CIBIL Repair',15000.00,18.00,2700.00,1350.00,1350.00,17700.00,1,'UPI','',0.00,0,'completed','2026-07-25','2026-07-25 23:34:16'),
(10,1,'Ankit Sharma','ankit@email.com','9876541000',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair',15000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,0.00,0,'completed','2026-07-25','2026-07-25 23:38:33'),
(11,NULL,'','rajesh@example.com','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair',15000.00,18.00,0.00,0.00,0.00,0.00,1,'Cash',NULL,0.00,0,'completed','2026-07-25','2026-07-25 23:40:29'),
(12,NULL,'Rajesh Kumar','rajesh@example.com','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Score Repair',15000.00,18.00,2700.00,0.00,0.00,17700.00,1,'Cash','INV-20260011',NULL,NULL,'completed','2026-07-29','2026-07-29 13:19:10'),
(13,NULL,'New Customer','new@example.com','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair',25000.00,18.00,4500.00,0.00,0.00,29500.00,1,'UPI','INV-20260012',NULL,NULL,'completed','2026-07-29','2026-07-29 13:19:27'),
(14,NULL,'Test Customer 1785339686088','test_1785339686088@example.com','9876586088',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Score Repair',15000.00,18.00,2700.00,0.00,0.00,17700.00,1,'UPI','INV-20260013',NULL,NULL,'pending','2026-07-29','2026-07-29 15:41:26');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sales_activities`
--

DROP TABLE IF EXISTS `sales_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_person_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `activity_type` enum('call','meeting','email','demo','follow_up','proposal') NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `activity_date` datetime DEFAULT NULL,
  `outcome` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sales_person_id` (`sales_person_id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `sales_activities_ibfk_1` FOREIGN KEY (`sales_person_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_activities_ibfk_2` FOREIGN KEY (`lead_id`) REFERENCES `sales_leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_activities`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sales_activities` WRITE;
/*!40000 ALTER TABLE `sales_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_activities` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sales_commissions`
--

DROP TABLE IF EXISTS `sales_commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_commissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_person_id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `sale_amount` decimal(12,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','paid') DEFAULT 'pending',
  `sale_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sales_person_id` (`sales_person_id`),
  KEY `status` (`status`),
  CONSTRAINT `sales_commissions_ibfk_1` FOREIGN KEY (`sales_person_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_commissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sales_commissions` WRITE;
/*!40000 ALTER TABLE `sales_commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_commissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sales_leads`
--

DROP TABLE IF EXISTS `sales_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_person_id` int(11) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `client_phone` varchar(20) DEFAULT NULL,
  `client_email` varchar(100) DEFAULT NULL,
  `service_interest` varchar(100) DEFAULT NULL,
  `source` varchar(50) DEFAULT NULL,
  `stage` enum('new','contacted','qualified','proposal','negotiation','won','lost') DEFAULT 'new',
  `expected_amount` decimal(12,2) DEFAULT NULL,
  `probability` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `expected_close_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sales_person_id` (`sales_person_id`),
  KEY `stage` (`stage`),
  CONSTRAINT `sales_leads_ibfk_1` FOREIGN KEY (`sales_person_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_leads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sales_leads` WRITE;
/*!40000 ALTER TABLE `sales_leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_leads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sales_targets`
--

DROP TABLE IF EXISTS `sales_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sales_person_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `target_amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_target` (`sales_person_id`,`month`,`year`),
  CONSTRAINT `sales_targets_ibfk_1` FOREIGN KEY (`sales_person_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_targets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sales_targets` WRITE;
/*!40000 ALTER TABLE `sales_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_targets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `scheduled_reports`
--

DROP TABLE IF EXISTS `scheduled_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduled_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `schedule_type` enum('daily','weekly','monthly','quarterly') DEFAULT 'weekly',
  `schedule_day` int(11) DEFAULT 1,
  `schedule_time` time DEFAULT '09:00:00',
  `recipient_email` varchar(255) NOT NULL,
  `recipient_phone` varchar(20) DEFAULT NULL,
  `format` enum('pdf','excel','both') DEFAULT 'pdf',
  `is_active` tinyint(4) DEFAULT 1,
  `last_sent_at` datetime DEFAULT NULL,
  `next_send_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_template_id` (`template_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_next_send_at` (`next_send_at`),
  CONSTRAINT `scheduled_reports_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheduled_reports_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `report_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheduled_reports`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `scheduled_reports` WRITE;
/*!40000 ALTER TABLE `scheduled_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheduled_reports` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `scoring_rules`
--

DROP TABLE IF EXISTS `scoring_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scoring_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `field` varchar(50) NOT NULL,
  `condition` varchar(50) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `weight` decimal(5,2) DEFAULT 1.00,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scoring_rules`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `scoring_rules` WRITE;
/*!40000 ALTER TABLE `scoring_rules` DISABLE KEYS */;
INSERT INTO `scoring_rules` VALUES
(1,'Website Lead','source','equals','Website',25,1.00,1,'2026-05-09 10:39:45'),
(2,'Referral Lead','source','equals','Referral',30,1.00,1,'2026-05-09 10:39:45'),
(3,'Social Media Lead','source','equals','Social Media',20,1.00,1,'2026-05-09 10:39:45'),
(4,'Call Lead','source','equals','Call',15,1.00,1,'2026-05-09 10:39:45'),
(5,'Written Off Clearance','service_type','equals','Written Off Clearance',35,1.00,1,'2026-05-09 10:39:45'),
(6,'Settled Clearance','service_type','equals','Settled Clearance',30,1.00,1,'2026-05-09 10:39:45'),
(7,'Suit Filed Clearance','service_type','equals','Suit Filed Clearance',40,1.00,1,'2026-05-09 10:39:45'),
(8,'Credit Report Analysis','service_type','equals','Credit Report Analysis',20,1.00,1,'2026-05-09 10:39:45'),
(9,'Profile Correction','service_type','equals','Profile Correction',15,1.00,1,'2026-05-09 10:39:45'),
(10,'Wrong Entry Clearance','service_type','equals','Wrong Entry Clearance',25,1.00,1,'2026-05-09 10:39:45'),
(11,'Lead Age 0-3 days','age_days','between','0,3',30,1.00,1,'2026-05-09 10:39:45'),
(12,'Lead Age 4-7 days','age_days','between','4,7',20,1.00,1,'2026-05-09 10:39:45'),
(13,'Lead Age 8-14 days','age_days','between','8,14',10,1.00,1,'2026-05-09 10:39:45'),
(14,'Lead Age 15+ days','age_days','greater','14',0,1.00,1,'2026-05-09 10:39:45'),
(15,'Status New','status','equals','new',25,1.00,1,'2026-05-09 10:39:45'),
(16,'Status Contacted','status','equals','contacted',15,1.00,1,'2026-05-09 10:39:45'),
(17,'Status Converted','status','equals','converted',0,1.00,1,'2026-05-09 10:39:45'),
(18,'Status Lost','status','equals','lost',0,1.00,1,'2026-05-09 10:39:45');
/*!40000 ALTER TABLE `scoring_rules` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) NOT NULL,
  `severity` enum('info','warning','error','critical') DEFAULT 'info',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_security_user` (`user_id`),
  KEY `idx_security_created` (`created_at`),
  CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `server_metrics`
--

DROP TABLE IF EXISTS `server_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(100) NOT NULL,
  `cpu_usage` decimal(5,2) DEFAULT 0.00,
  `memory_usage` decimal(5,2) DEFAULT 0.00,
  `disk_usage` decimal(5,2) DEFAULT 0.00,
  `disk_total` bigint(20) DEFAULT 0,
  `disk_used` bigint(20) DEFAULT 0,
  `network_in` bigint(20) DEFAULT 0,
  `network_out` bigint(20) DEFAULT 0,
  `load_avg` decimal(5,2) DEFAULT 0.00,
  `recorded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_metrics_server` (`server_name`),
  KEY `idx_metrics_recorded` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `server_metrics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `server_metrics` WRITE;
/*!40000 ALTER TABLE `server_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `server_metrics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `service_requests`
--

DROP TABLE IF EXISTS `service_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `request_date` datetime DEFAULT current_timestamp(),
  `completion_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_requests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `service_requests` WRITE;
/*!40000 ALTER TABLE `service_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_requests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'other',
  `description` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`benefits`)),
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT '30 days',
  `status` enum('active','inactive','draft') DEFAULT 'active',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_popular` tinyint(1) DEFAULT 0,
  `icon` varchar(10) DEFAULT NULL,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES
(1,'Updated Service','premium','Updated description',NULL,NULL,NULL,1499.00,'30-45 days','active',1,0,'⭐',1,'2026-05-02 10:11:25',NULL),
(2,'Settled Clearance','other','Remove settled accounts and improve your score',NULL,NULL,NULL,12000.00,'30 days','active',0,0,'💰',1,'2026-05-02 10:11:25',NULL),
(3,'Suit Filed Clearance','other','Remove suit filed entries from your CIBIL report',NULL,NULL,NULL,20000.00,'30 days','active',0,0,'⚖️',1,'2026-05-02 10:11:25',NULL),
(4,'Credit Report Analysis','other','Detailed analysis of your credit report',NULL,NULL,NULL,5000.00,'30 days','active',0,0,'📊',1,'2026-05-02 10:11:25',NULL),
(5,'Wrong Entry Clearance','other','Remove incorrect entries from your report',NULL,NULL,NULL,8000.00,'30 days','active',0,0,'❌',1,'2026-05-02 10:11:25',NULL),
(6,'Profile Correction','other','Fix personal information discrepancies',NULL,NULL,NULL,3000.00,'30 days','active',0,0,'👤',1,'2026-05-02 10:11:25',NULL),
(7,'Written Off Clearance','other','Remove written‑off accounts from your credit report and boost your CIBIL score significantly.','🔹 WHAT IS A WRITTEN‑OFF ACCOUNT?\n• A lender gives up on recovering a debt and marks it as \"written off\".\n• The entry stays on your credit report for 7+ years.\n• It drops your score drastically.\n\n🔹 HOW WE REMOVE IT\n• Step 1: Analyse your documents.\n• Step 2: Send legal notices.\n• Step 3: Negotiate with the bank.\n• Step 4: File disputes with bureaus.\n• Step 5: Follow up until deleted.\n\n🔹 WHAT YOU GAIN\n• Score improvement of 50–100 points.\n• Eligibility for loans & credit cards.\n\n🔹 98% success rate + money‑back guarantee.','[\"Eliminates the most damaging negative entry\",\"Improves credit score by 50–100 points\",\"Restores eligibility for home/car loans\",\"Clears path for credit card approvals\",\"100% legal process – money‑back guarantee\"]','[\"PAN Card (mandatory)\",\"Aadhar Card (mandatory)\",\"Latest CIBIL Report\",\"Loan account details (if available)\",\"Bank statements (helpful)\"]',15000.00,'30 days','active',0,0,'📝',1,'2026-05-02 11:28:27',NULL),
(8,'Settled Clearance','other','Convert settled accounts to closed status and improve your creditworthiness.','🔹 WHAT IS A SETTLED ACCOUNT?\n• You paid less than full outstanding.\n• It signals risk to lenders.\n\n🔹 HOW WE CONVERT TO \"CLOSED\"\n• Collect settlement letter.\n• Draft legal notices.\n• Negotiate with bank.\n• Escalate to bureaus.\n• Verify updated status.\n\n🔹 WHAT YOU GAIN\n• Score improvement 40–80 points.\n• Become eligible for new credit.\n• Better interest rates.','[\"Removes the stigma of partial payment\",\"Boosts credit score by 40–80 points\",\"Makes you eligible for new credit cards & loans\",\"Improves loan terms (lower interest rates)\",\"No hidden charges – transparent pricing\"]','[\"PAN Card\",\"Aadhar Card\",\"Settlement Letter from Bank\",\"Latest CIBIL Report\",\"Payment proof of settlement\"]',12000.00,'30 days','active',0,0,'💰',1,'2026-05-02 11:28:27',NULL),
(9,'Suit Filed Clearance','other','Remove legal suit entries from your CIBIL report and rebuild your credit.','🔹 WHAT IS A SUIT FILED ENTRY?\n• Legal action taken against you.\n• Blocks almost all loan approvals.\n\n🔹 HOW WE REMOVE IT\n• Review court documents.\n• Coordinate with lawyer.\n• File for withdrawal.\n• Obtain court order.\n• Approach bureaus for deletion.\n\n🔹 WHAT YOU GAIN\n• Score improvement 80–150 points.\n• Eligibility for home loans, business credit.\n• Legal tag removed permanently.','[\"Removes the most damaging legal record\",\"Improves credit score by 80–150 points\",\"Restores eligibility for home loans, business credit\",\"Ends legal tag from your profile\",\"Free legal consultation included\"]','[\"PAN Card\",\"Aadhar Card\",\"Court case documents (if any)\",\"Latest CIBIL Report\",\"Loan/account details\"]',20000.00,'30 days','active',0,0,'⚖️',1,'2026-05-02 11:28:27',NULL),
(10,'Credit Report Analysis','other','Forensic review of your credit report to find hidden errors and improvement areas.','🔹 FORENSIC ANALYSIS\n• Manual examination of every entry.\n• Identify errors, duplicates, identity theft.\n\n🔹 WHAT YOU GET\n• 20+ page detailed report.\n• Personal consultation with expert.\n• Prioritised action plan.\n\n🔹 MONEY‑BACK GUARANTEE\n• If no errors found, you pay nothing.','[\"Uncover hidden errors you never knew existed\",\"Understand exactly why your score is low\",\"Receive a step‑by‑step repair plan\",\"Avoid loan rejection due to hidden mistakes\",\"Money‑back guarantee if no errors found\"]','[\"Latest CIBIL Report (free from bank/website)\",\"PAN Card (optional)\",\"Aadhar Card (optional)\"]',5000.00,'30 days','active',0,0,'📊',1,'2026-05-02 11:28:27',NULL),
(11,'Wrong Entry Clearance','other','Remove unauthorised loans, incorrect personal data, and other reporting errors.','🔹 TYPES OF WRONG ENTRIES\n• Unauthorised loans (fraud/identity theft).\n• Name/address/DOB mismatches.\n• Duplicate accounts.\n• Incorrect payment status.\n\n🔹 HOW WE FIX THEM\n• Gather proof.\n• File formal dispute with bureau.\n• Liaise with bank.\n• Escalate to ombudsman if needed.\n• Confirm removal.\n\n🔹 WEEKLY UPDATES + TRANSPARENCY','[\"Removes fraudulent/unauthorised loans\",\"Corrects personal data mismatches\",\"Eliminates duplicate accounts\",\"Restores true creditworthiness\",\"Regular progress updates\"]','[\"PAN Card\",\"Aadhar Card\",\"Latest CIBIL Report\",\"Proof of correct information (self‑attested)\"]',8000.00,'30 days','active',0,0,'❌',1,'2026-05-02 11:28:27',NULL),
(12,'Profile Correction','other','Fix name, address, DOB & PAN mismatches in your credit report.','🔹 COMMON ERRORS\n• Name spelling difference.\n• Old address.\n• Wrong DOB.\n• PAN not linked.\n\n🔹 HOW WE CORRECT THEM\n• Collect proof documents.\n• Prepare correction requests for all bureaus.\n• Submit with evidence.\n• Follow up until updated.\n• Provide clean profile confirmation.\n\n🔹 FAST – Average 4 days resolution.','[\"Loan applications no longer rejected due to profile mismatch\",\"Ensures accurate data for lenders\",\"Prevents identity‑theft issues\",\"Improves overall credit reputation\",\"Quick 3‑5 day turnaround\"]','[\"PAN Card\",\"Aadhar Card\",\"Latest CIBIL Report\",\"Proof of correct information (self‑attested)\"]',3000.00,'30 days','active',0,0,'👤',1,'2026-05-02 11:28:27',NULL),
(13,'Written Off Clearance','other','Remove written-off accounts from your credit report',NULL,NULL,NULL,15000.00,'30-45 days','active',0,0,'📝',1,'2026-05-02 14:29:31',NULL),
(14,'Settled Clearance','other','Convert settled accounts to closed status',NULL,NULL,NULL,12000.00,'30-45 days','active',0,0,'💰',1,'2026-05-02 14:29:31',NULL),
(15,'Suit Filed Clearance','other','Remove legal suit entries from CIBIL',NULL,NULL,NULL,20000.00,'30-45 days','active',0,0,'⚖️',1,'2026-05-02 14:29:31',NULL),
(16,'Credit Report Analysis','other','Detailed forensic review of your credit report',NULL,NULL,NULL,5000.00,'30-45 days','active',0,0,'📊',1,'2026-05-02 14:29:31',NULL),
(17,'Profile Correction','other','Fix personal information discrepancies',NULL,NULL,NULL,3000.00,'30-45 days','active',0,0,'👤',1,'2026-05-02 14:29:31',NULL),
(18,'Wrong Entry Clearance','other','Remove unauthorized loans and errors',NULL,NULL,NULL,8000.00,'30-45 days','active',0,0,'❌',1,'2026-05-02 14:29:31',NULL),
(19,'CIBIL Score Repair','credit_repair','Complete credit score repair service. We identify inaccuracies, file disputes, and work with credit bureaus to improve your score.',NULL,NULL,NULL,999.00,'30-45 days','active',1,1,'📈',1,'2026-07-27 09:13:06',NULL),
(20,'Dispute Resolution','dispute','Professional dispute filing service. We draft and file disputes with credit bureaus and financial institutions on your behalf.',NULL,NULL,NULL,1499.00,'15-20 days','active',1,0,'⚖️',1,'2026-07-27 09:13:06',NULL),
(21,'Credit Consultation','consulting','One-on-one consultation with credit experts. Get personalized advice on improving your credit profile and financial health.',NULL,NULL,NULL,499.00,'1-2 days','active',0,1,'💡',1,'2026-07-27 09:13:06',NULL),
(22,'Legal Credit Protection','legal','Legal protection services for credit-related disputes. We provide legal documentation and representation for credit issues.',NULL,NULL,NULL,2499.00,'60-90 days','inactive',0,0,'🏛️',1,'2026-07-27 09:13:06',NULL),
(23,'Financial Planning','financial','Comprehensive financial planning service. We help you create a roadmap to financial stability and better credit.',NULL,NULL,NULL,1999.00,'45-60 days','draft',0,0,'💰',1,'2026-07-27 09:13:06',NULL),
(24,'Settlement Negotiation','dispute','Professional negotiation services for debt settlement. We work with creditors to negotiate favorable settlement terms.',NULL,NULL,NULL,1299.00,'30-45 days','active',0,1,'🤝',1,'2026-07-27 09:13:06',NULL),
(25,'Credit Monitoring','financial','24/7 credit monitoring service with alerts for any changes to your credit report.',NULL,NULL,NULL,299.00,'Monthly','active',1,0,'🔍',1,'2026-07-27 09:13:06',NULL),
(26,'Debt Consolidation','financial','Professional debt consolidation service to help you manage multiple debts with a single payment.',NULL,NULL,NULL,1999.00,'60-90 days','draft',0,0,'📊',1,'2026-07-27 09:13:06',NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) DEFAULT 'general',
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json','array','email','url','phone','color','textarea') DEFAULT 'string',
  `is_encrypted` tinyint(1) DEFAULT 0,
  `is_system` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `options` text DEFAULT NULL,
  `validation_rules` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`),
  UNIQUE KEY `unique_category_key` (`category`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'general','company_name','CIBIL Repair India','string',0,0,'Company name',NULL,NULL,'2026-07-29 13:20:27',NULL),
(2,'general','company_email','contact@cibilrepair.in','string',0,0,NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
(3,'general','company_phone','+91 99054 82503','string',0,0,NULL,NULL,NULL,'2026-07-29 13:20:27',NULL),
(4,'general','company_website','https://cibilrepair.in','string',0,0,NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
(5,'general','two_factor','enabled','string',0,0,NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
(6,'general','session_timeout','60','string',0,0,NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
(8,'success_stories','story_1785331440432','{\"name\":\"Rajesh Kumar\",\"city\":\"Delhi\",\"achievement\":\"Improved CIBIL score by 150 points\",\"oldScore\":550,\"newScore\":700,\"review\":\"CIBIL Repair helped me improve my score significantly!\",\"rating\":5,\"date\":\"2026-07-29T13:24:00.432Z\"}','string',0,0,NULL,NULL,NULL,'2026-07-29 13:24:00',NULL),
(9,'client_documents','doc_1785336859378','{\"client_id\":1,\"document_name\":\"CIBIL Report\",\"document_type\":\"credit_report\",\"status\":\"pending\"}','string',0,0,NULL,NULL,NULL,'2026-07-29 14:54:19',NULL),
(10,'client_documents','doc_update_1785336866692','{\"document_id\":1,\"document_name\":\"Updated CIBIL Report\",\"status\":\"verified\",\"updated_at\":\"2026-07-29T14:54:26.692Z\"}','string',0,0,NULL,NULL,NULL,'2026-07-29 14:54:27',NULL),
(11,'client_documents','doc_1785336886331','{\"client_id\":1,\"document_name\":\"CIBIL Report\",\"document_type\":\"credit_report\",\"status\":\"pending\"}','string',0,0,NULL,NULL,NULL,'2026-07-29 14:54:46',NULL),
(12,'client_documents','doc_update_1785336893169','{\"document_id\":1,\"document_name\":\"Updated Report\",\"status\":\"verified\"}','string',0,0,NULL,NULL,NULL,'2026-07-29 14:54:53',NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `shifts`
--

DROP TABLE IF EXISTS `shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_name` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `grace_minutes` int(11) DEFAULT 15,
  `break_minutes` int(11) DEFAULT 60,
  `working_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shifts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `shifts` WRITE;
/*!40000 ALTER TABLE `shifts` DISABLE KEYS */;
INSERT INTO `shifts` VALUES
(1,'Morning Shift','09:00:00','18:00:00',15,60,8.00,'active'),
(2,'Evening Shift','14:00:00','23:00:00',15,60,8.00,'active'),
(3,'Night Shift','22:00:00','06:00:00',15,60,8.00,'active');
/*!40000 ALTER TABLE `shifts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sla_metrics`
--

DROP TABLE IF EXISTS `sla_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sla_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `response_time` int(11) DEFAULT NULL COMMENT 'Response time in minutes',
  `resolution_time` int(11) DEFAULT NULL COMMENT 'Resolution time in minutes',
  `sla_met` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `sla_metrics_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sla_metrics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sla_metrics` WRITE;
/*!40000 ALTER TABLE `sla_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `sla_metrics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `sla_rules`
--

DROP TABLE IF EXISTS `sla_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sla_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `response_hours` int(11) NOT NULL,
  `resolution_hours` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sla_rules`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `sla_rules` WRITE;
/*!40000 ALTER TABLE `sla_rules` DISABLE KEYS */;
INSERT INTO `sla_rules` VALUES
(1,'urgent',1,4,'2026-06-04 17:18:49'),
(2,'high',2,8,'2026-06-04 17:18:49'),
(3,'medium',4,24,'2026-06-04 17:18:49'),
(4,'low',8,48,'2026-06-04 17:18:49');
/*!40000 ALTER TABLE `sla_rules` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `social_media_posts`
--

DROP TABLE IF EXISTS `social_media_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_media_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL,
  `post_content` text DEFAULT NULL,
  `post_url` varchar(500) DEFAULT NULL,
  `post_date` datetime DEFAULT NULL,
  `impressions` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `comments` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `platform` (`platform`),
  KEY `post_date` (`post_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_media_posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `social_media_posts` WRITE;
/*!40000 ALTER TABLE `social_media_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_media_posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('purchase','sale','return','adjustment','damage') DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `movement_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `_archived_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `success_stories`
--

DROP TABLE IF EXISTS `success_stories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `success_stories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `achievement` varchar(200) DEFAULT NULL,
  `old_score` int(11) DEFAULT NULL,
  `new_score` int(11) DEFAULT NULL,
  `review` text NOT NULL,
  `rating` int(11) DEFAULT 5,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `success_stories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `success_stories` WRITE;
/*!40000 ALTER TABLE `success_stories` DISABLE KEYS */;
INSERT INTO `success_stories` VALUES
(2,'Priya Sharma','Mumbai','Credit Card Approved',650,730,'Professional and transparent service. They guided me through every step. My settled account was converted to closed and my score improved by 80 points. Highly recommended!',5,'approved','2026-05-11 15:28:50',NULL),
(3,'Rajesh Kumar','Bangalore','Business Loan Approved',580,710,'I had multiple wrong entries. The experts removed all of them within 30 days. My loan got approved immediately after. Best decision ever!',5,'approved','2026-05-11 15:28:50',NULL),
(4,'Test User','Delhi','CIBIL Score Improved',550,720,'Great service! My CIBIL score improved significantly.',5,'pending','2026-07-29 08:52:58',NULL),
(5,'Test User','Delhi','CIBIL Score Improved',550,720,'Great service! My CIBIL score improved significantly.',5,'pending','2026-07-29 08:53:04',NULL),
(6,'Rajesh Kumar','Delhi','',0,0,'Great service!',5,'pending','2026-07-29 13:27:40',NULL),
(7,'Priya Sharma','Mumbai','CIBIL score improved from 600 to 750',600,750,'Excellent service! My credit score improved within 60 days.',5,'pending','2026-07-29 13:27:58',NULL),
(8,'Amit Singh','Bangalore','CIBIL score improved from 550 to 720',550,720,'Very professional team. Highly recommended!',5,'pending','2026-07-29 13:28:18',NULL);
/*!40000 ALTER TABLE `success_stories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `gst_number` varchar(50) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `payment_terms` int(11) DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT NULL,
  `opening_balance` decimal(12,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_agent_performance`
--

DROP TABLE IF EXISTS `support_agent_performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_agent_performance` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `tickets_assigned` int(11) DEFAULT 0,
  `tickets_resolved` int(11) DEFAULT 0,
  `avg_response_time_minutes` int(11) DEFAULT 0,
  `avg_resolution_time_hours` decimal(5,2) DEFAULT 0.00,
  `csat_score` decimal(3,2) DEFAULT 0.00,
  `chat_count` int(11) DEFAULT 0,
  `call_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agent_date` (`agent_id`,`date`),
  KEY `idx_date` (`date`),
  KEY `idx_agent` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_agent_performance`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_agent_performance` WRITE;
/*!40000 ALTER TABLE `support_agent_performance` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_agent_performance` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_calls`
--

DROP TABLE IF EXISTS `support_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_calls` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `call_type` enum('inbound','outbound') NOT NULL,
  `call_duration` int(11) DEFAULT 0,
  `call_summary` text DEFAULT NULL,
  `call_recording_url` varchar(500) DEFAULT NULL,
  `resolution_status` enum('resolved','follow_up','escalated') DEFAULT 'resolved',
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_agent` (`agent_id`),
  KEY `idx_call_date` (`created_at`),
  KEY `idx_resolution` (`resolution_status`),
  KEY `idx_calls_composite` (`client_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_calls`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_calls` WRITE;
/*!40000 ALTER TABLE `support_calls` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_calls` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_csat`
--

DROP TABLE IF EXISTS `support_csat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_csat` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_csat`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_csat` WRITE;
/*!40000 ALTER TABLE `support_csat` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_csat` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_emails`
--

DROP TABLE IF EXISTS `support_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_emails` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `from_email` varchar(255) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message_body` longtext NOT NULL,
  `direction` enum('incoming','outgoing') DEFAULT 'incoming',
  `status` enum('received','replied','forwarded','archived') DEFAULT 'received',
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `received_at` timestamp NULL DEFAULT current_timestamp(),
  `replied_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_from_email` (`from_email`),
  KEY `idx_status` (`status`),
  KEY `idx_received_at` (`received_at`),
  KEY `idx_emails_composite` (`status`,`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_emails`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_emails` WRITE;
/*!40000 ALTER TABLE `support_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_emails` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_escalations`
--

DROP TABLE IF EXISTS `support_escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_escalations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `escalation_reason` varchar(255) NOT NULL,
  `escalation_level` enum('level1','level2','level3','management') DEFAULT 'level1',
  `escalated_to` int(11) NOT NULL,
  `escalated_by` int(11) NOT NULL,
  `status` enum('pending','reviewed','resolved','rejected') DEFAULT 'pending',
  `resolution_notes` text DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_status` (`status`),
  KEY `idx_escalated_at` (`escalated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_escalations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_escalations` WRITE;
/*!40000 ALTER TABLE `support_escalations` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_escalations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_faqs`
--

DROP TABLE IF EXISTS `support_faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(500) NOT NULL,
  `answer` longtext NOT NULL,
  `category` varchar(100) NOT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `views_count` int(11) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `not_helpful_count` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`),
  FULLTEXT KEY `idx_search` (`question`,`answer`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_faqs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_faqs` WRITE;
/*!40000 ALTER TABLE `support_faqs` DISABLE KEYS */;
INSERT INTO `support_faqs` VALUES
(1,'How long does credit repair take?','Credit repair typically takes 3-6 months depending on the complexity of errors and the credit bureau\'s response time. We provide monthly updates on your progress.','credit_repair',NULL,0,0,0,1,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(2,'What documents do I need to start?','You need: 1) PAN Card, 2) Aadhaar Card, 3) Latest credit report from CIBIL, 4) Address proof, 5) Income proof (if applicable).','documentation',NULL,0,0,0,1,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(3,'How do I check my dispute status?','You can check dispute status in real-time from your client dashboard. Login and go to \"Dispute Tracker\" section to see updates.','disputes',NULL,0,0,0,1,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(4,'What is your refund policy?','We offer a 30-day money-back guarantee if no progress is made on your credit repair case. Full refund policy details are in your service agreement.','billing',NULL,0,0,0,1,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(5,'Can I dispute errors myself?','Yes, you can dispute errors yourself for free. However, our experts have higher success rates due to experience with credit laws and bureau procedures.','disputes',NULL,0,0,0,1,1,'2026-06-04 17:59:36','2026-06-04 17:59:36');
/*!40000 ALTER TABLE `support_faqs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_reply_templates`
--

DROP TABLE IF EXISTS `support_reply_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_reply_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL,
  `template` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `created_by` int(11) NOT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_reply_templates`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_reply_templates` WRITE;
/*!40000 ALTER TABLE `support_reply_templates` DISABLE KEYS */;
INSERT INTO `support_reply_templates` VALUES
(1,'Ticket Acknowledgment','ticket_response','Thank you for contacting support. Your ticket #{ticket_id} has been received and assigned to our team. We will respond within {response_time} hours.',NULL,1,0,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(2,'Dispute Status Update','dispute','Dear {client_name},\n\nYour dispute regarding {dispute_item} is currently under review with {bureau_name}. Current status: {status}. We will update you once we receive a response.\n\nBest regards,\nSupport Team',NULL,1,0,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(3,'Document Request','document','Dear {client_name},\n\nWe require the following document(s) to proceed with your case:\n{missing_documents}\n\nPlease upload these documents to your client portal.\n\nThank you,\nDocument Team',NULL,1,0,1,'2026-06-04 17:59:36','2026-06-04 17:59:36'),
(4,'Resolution Confirmation','resolution','Dear {client_name},\n\nWe are pleased to inform you that your issue has been resolved. Resolution summary: {resolution_summary}\n\nPlease take a moment to rate your experience: {csat_link}\n\nBest regards,\nSupport Team',NULL,1,0,1,'2026-06-04 17:59:36','2026-06-04 17:59:36');
/*!40000 ALTER TABLE `support_reply_templates` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_sla_config`
--

DROP TABLE IF EXISTS `support_sla_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_sla_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `response_time_hours` int(11) NOT NULL,
  `resolution_time_hours` int(11) NOT NULL,
  `escalation_after_hours` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `priority` (`priority`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_sla_config`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_sla_config` WRITE;
/*!40000 ALTER TABLE `support_sla_config` DISABLE KEYS */;
INSERT INTO `support_sla_config` VALUES
(1,'low',24,72,48,1,'2026-06-04 17:59:36'),
(2,'medium',12,48,24,1,'2026-06-04 17:59:36'),
(3,'high',4,24,8,1,'2026-06-04 17:59:36'),
(4,'urgent',1,8,2,1,'2026-06-04 17:59:36');
/*!40000 ALTER TABLE `support_sla_config` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_sla_tracking`
--

DROP TABLE IF EXISTS `support_sla_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_sla_tracking` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `response_due_at` datetime NOT NULL,
  `resolution_due_at` datetime NOT NULL,
  `response_met` tinyint(1) DEFAULT 0,
  `resolution_met` tinyint(1) DEFAULT 0,
  `response_breach_reason` text DEFAULT NULL,
  `resolution_breach_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_due_dates` (`response_due_at`,`resolution_due_at`),
  KEY `idx_breach` (`response_met`,`resolution_met`),
  KEY `idx_sla_tracking_composite` (`response_due_at`,`resolution_met`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_sla_tracking`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_sla_tracking` WRITE;
/*!40000 ALTER TABLE `support_sla_tracking` DISABLE KEYS */;
INSERT INTO `support_sla_tracking` VALUES
(1,6,'medium','2026-07-26 11:10:40','2026-07-27 23:10:40',0,0,NULL,NULL,'2026-07-25 23:10:40'),
(2,7,'medium','2026-07-26 11:34:16','2026-07-27 23:34:16',0,0,NULL,NULL,'2026-07-25 23:34:16'),
(3,8,'medium','2026-07-26 11:55:43','2026-07-27 23:55:43',0,0,NULL,NULL,'2026-07-25 23:55:43');
/*!40000 ALTER TABLE `support_sla_tracking` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_ticket_replies`
--

DROP TABLE IF EXISTS `support_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_internal_note` tinyint(1) DEFAULT 0,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ticket_replies_composite` (`ticket_id`,`created_at`),
  CONSTRAINT `support_ticket_replies_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_ticket_replies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_ticket_replies` WRITE;
/*!40000 ALTER TABLE `support_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_email` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('open','in-progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `ticket_no` varchar(50) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `category` varchar(50) DEFAULT 'general',
  `assigned_to` int(11) DEFAULT NULL,
  `sla_due` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tickets_email_status` (`client_email`,`status`),
  KEY `idx_tickets_status_created` (`status`,`created_at`),
  KEY `idx_tickets_composite` (`status`,`priority`,`created_at`),
  KEY `idx_tickets_email` (`client_email`),
  KEY `idx_tickets_user` (`user_id`),
  KEY `idx_tickets_status` (`status`),
  KEY `idx_tickets_priority` (`priority`),
  KEY `idx_tickets_assigned` (`assigned_to`),
  CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `support_tickets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
INSERT INTO `support_tickets` VALUES
(1,'rajesh@example.com',NULL,'Unable to upload documents','I am facing issues while uploading my bank statement. The page shows error.',NULL,'resolved','2026-05-18 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(2,'priya@example.com',NULL,'Question about dispute status','My written off dispute with ICICI Bank, when will it be resolved?',NULL,'in-progress','2026-05-28 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(3,'amit@example.com',NULL,'Payment receipt not received','I made payment of ₹2,999 but haven\'t received the receipt yet.',NULL,'in-progress','2026-05-31 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(4,'neha@example.com',NULL,'Credit score not updating','My CIBIL score shows old data from last month.',NULL,'in-progress','2026-06-01 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(5,'vikram@example.com',NULL,'General inquiry about services','What are your charges for credit repair services?',NULL,'closed','2026-05-03 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(6,'priya@example.com',NULL,'Issue with CIBIL report','My CIBIL score is showing incorrect information',NULL,'open','2026-07-25 23:10:40','TKT-202607-000000','medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(7,'priya@example.com',NULL,'Test Ticket 1785022456011','This is a test ticket from the API test script',NULL,'open','2026-07-25 23:34:16','TKT-202607-000000','medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24'),
(8,'priya@example.com',NULL,'Issue with CIBIL report','My CIBIL score is showing incorrect information. Please help me resolve this issue.',NULL,'open','2026-07-25 23:55:43','TKT-202607-000000','medium','general',NULL,NULL,NULL,NULL,NULL,'2026-08-01 18:06:24');
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_generate_ticket_no
BEFORE INSERT ON support_tickets
FOR EACH ROW
BEGIN
    IF NEW.ticket_no IS NULL THEN
        SET NEW.ticket_no = CONCAT('TKT-', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(NEW.id, 6, '0'));
    END IF;
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`u929623538_cibilrepair`@`127.0.0.1`*/ /*!50003 TRIGGER IF NOT EXISTS trigger_sla_tracking
AFTER INSERT ON support_tickets
FOR EACH ROW
BEGIN
    DECLARE response_hours INT;
    DECLARE resolution_hours INT;
    
    SELECT response_time_hours, resolution_time_hours INTO response_hours, resolution_hours
    FROM support_sla_config WHERE priority = NEW.priority;
    
    INSERT INTO support_sla_tracking (ticket_id, priority, response_due_at, resolution_due_at)
    VALUES (NEW.id, NEW.priority, DATE_ADD(NOW(), INTERVAL response_hours HOUR), DATE_ADD(NOW(), INTERVAL resolution_hours HOUR));
END 
*/;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `support_whatsapp_chats`
--

DROP TABLE IF EXISTS `support_whatsapp_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_whatsapp_chats` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `message_type` enum('incoming','outgoing') NOT NULL,
  `message_text` text NOT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `status` enum('sent','delivered','read','failed') DEFAULT 'sent',
  `read_at` datetime DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_whatsapp_composite` (`client_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_whatsapp_chats`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `support_whatsapp_chats` WRITE;
/*!40000 ALTER TABLE `support_whatsapp_chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_whatsapp_chats` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_permissions`
--

DROP TABLE IF EXISTS `system_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_permissions` WRITE;
/*!40000 ALTER TABLE `system_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_role_permissions`
--

DROP TABLE IF EXISTS `system_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `system_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `system_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `system_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `system_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_role_permissions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_role_permissions` WRITE;
/*!40000 ALTER TABLE `system_role_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_roles`
--

DROP TABLE IF EXISTS `system_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_roles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_roles` WRITE;
/*!40000 ALTER TABLE `system_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) DEFAULT 'string',
  `category` varchar(50) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES
(1,'site_name','CIBIL Repair','string','general','Website name','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(2,'site_tagline','Better Credit. Better Future.','string','general','Website tagline','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(3,'site_email','contact@cibilrepair.in','string','general','Contact email','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(4,'site_phone','+91 87094 55441','string','general','Contact phone','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(5,'site_address','Delhi NCR, India','string','general','Office address','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(6,'maintenance_mode','0','boolean','general','Maintenance mode','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(7,'gst_rate','18','integer','general','GST rate','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(8,'commission_rate','10','integer','general','Commission rate','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(9,'items_per_page','20','integer','general','Items per page','2026-07-29 09:01:44','2026-07-29 09:01:44'),
(10,'company_name','CIBIL Repair India','string','general',NULL,'2026-07-29 15:53:23','2026-07-29 15:53:23'),
(11,'company_email','contact@cibilrepair.in','string','general',NULL,'2026-07-29 15:53:23','2026-07-29 15:53:23'),
(12,'company_phone','+91 99054 82503','string','general',NULL,'2026-07-29 15:53:23','2026-07-29 15:53:23'),
(13,'company_address','Delhi NCR, India','string','general',NULL,'2026-07-29 15:53:23','2026-07-29 15:53:23'),
(14,'test_setting','check_1785343079829','string','general',NULL,'2026-07-29 16:25:18','2026-07-29 16:37:59');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `system_status`
--

DROP TABLE IF EXISTS `system_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `component_name` varchar(100) NOT NULL,
  `status` enum('online','offline','maintenance','warning','error') DEFAULT 'online',
  `uptime` int(11) DEFAULT 0,
  `response_time` int(11) DEFAULT 0,
  `last_checked` timestamp NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_component` (`component_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_status`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `system_status` WRITE;
/*!40000 ALTER TABLE `system_status` DISABLE KEYS */;
INSERT INTO `system_status` VALUES
(1,'Web Server','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(2,'Database Server','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(3,'File Server','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(4,'Email Server','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(5,'Cache Server','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(6,'API Gateway','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(7,'Redis Cache','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31'),
(8,'Queue Worker','online',0,0,'2026-08-01 18:50:31',NULL,'2026-08-01 18:50:31','2026-08-01 18:50:31');
/*!40000 ALTER TABLE `system_status` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `assigned_by` int(10) unsigned NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_id` (`task_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_assigned_by` (`assigned_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES
(1,'TASK-001','Welcome to ORION CRM','This is your first task. Start managing your business efficiently!','medium','pending',NULL,NULL,1,NULL,'2026-05-20 06:34:22','2026-05-20 06:34:22'),
(3,'TASK-002','Review Partner Applications','Check pending partner registrations','high','pending','2026-05-22',NULL,1,NULL,'2026-05-20 06:36:35','2026-05-20 06:36:35'),
(4,'TASK-003','Follow up with clients','Call pending customers for feedback','medium','pending','2026-05-20',NULL,1,NULL,'2026-05-20 06:36:35','2026-05-20 06:36:35');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `taxes`
--

DROP TABLE IF EXISTS `taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_name` varchar(50) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_type` enum('GST','Income Tax','TDS','PT','Others') DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taxes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `taxes` WRITE;
/*!40000 ALTER TABLE `taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `taxes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `terms_consent`
--

DROP TABLE IF EXISTS `terms_consent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms_consent` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `consent_given` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms_consent`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `terms_consent` WRITE;
/*!40000 ALTER TABLE `terms_consent` DISABLE KEYS */;
/*!40000 ALTER TABLE `terms_consent` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `test_cases`
--

DROP TABLE IF EXISTS `test_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_id` varchar(50) NOT NULL,
  `project_id` int(11) NOT NULL,
  `test_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `test_type` enum('functional','performance','security','usability','integration','regression','smoke') DEFAULT 'functional',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `preconditions` text DEFAULT NULL,
  `test_steps` text DEFAULT NULL,
  `expected_result` text DEFAULT NULL,
  `actual_result` text DEFAULT NULL,
  `status` enum('draft','pending','approved','failed','passed','blocked') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_id` (`test_id`),
  KEY `fk_testcases_project` (`project_id`),
  KEY `fk_testcases_created_by` (`created_by`),
  KEY `fk_testcases_assigned_to` (`assigned_to`),
  CONSTRAINT `fk_testcases_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_testcases_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_testcases_project` FOREIGN KEY (`project_id`) REFERENCES `qa_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `test_cases_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `qa_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `test_cases_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `test_cases_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `test_cases` WRITE;
/*!40000 ALTER TABLE `test_cases` DISABLE KEYS */;
INSERT INTO `test_cases` VALUES
(1,'TC-2024-001',1,'Login Functionality Test','Verify user login works correctly with valid credentials','functional','high','User registered in system','1. Navigate to login page\n2. Enter valid email\n3. Enter valid password\n4. Click Login','User should be redirected to dashboard',NULL,'passed',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(2,'TC-2024-002',1,'Password Reset Flow','Test password reset email and link functionality','functional','medium','User registered with email','1. Click Forgot Password\n2. Enter email\n3. Check email for reset link\n4. Click reset link\n5. Enter new password','User should be able to reset password',NULL,'pending',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(3,'TC-2024-003',1,'Dashboard Loading Performance','Test dashboard load time under normal conditions','performance','high','System under normal load','1. Login as user\n2. Navigate to dashboard\n3. Measure load time','Dashboard should load within 3 seconds',NULL,'draft',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(4,'TC-2024-004',2,'App Launch Performance','Test app launch time and responsiveness','performance','high','App installed on device','1. Launch app\n2. Measure cold start time\n3. Navigate to home screen','App should launch within 2 seconds',NULL,'draft',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(5,'TC-2024-005',2,'Push Notification Test','Verify push notifications are received','functional','medium','Device registered for notifications','1. Send test notification\n2. Verify device receives it\n3. Click notification\n4. Verify navigation','Notification should be received and actionable',NULL,'approved',8,8,'2026-08-01 19:52:58','2026-08-01 19:52:58');
/*!40000 ALTER TABLE `test_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `test_executions`
--

DROP TABLE IF EXISTS `test_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_executions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `test_case_id` int(11) NOT NULL,
  `executed_by` int(11) NOT NULL,
  `execution_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('passed','failed','blocked','skipped') DEFAULT 'passed',
  `actual_result` text DEFAULT NULL,
  `execution_notes` text DEFAULT NULL,
  `screenshot_file` varchar(255) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `build_version` varchar(50) DEFAULT NULL,
  `environment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_executions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `test_executions` WRITE;
/*!40000 ALTER TABLE `test_executions` DISABLE KEYS */;
INSERT INTO `test_executions` VALUES
(1,1,8,'2026-08-01 19:52:58','passed','Login successful, redirect to dashboard','Test passed on Chrome and Firefox',NULL,5,'v2.0.1','Production'),
(2,1,8,'2026-08-01 19:52:58','passed','Login successful','Retest after fix',NULL,4,'v2.0.2','Staging'),
(3,2,8,'2026-08-01 19:52:58','failed','Reset email not received within 5 minutes','Email delay issue identified',NULL,8,'v2.0.1','Production');
/*!40000 ALTER TABLE `test_executions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `test_suite_cases`
--

DROP TABLE IF EXISTS `test_suite_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_suite_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suite_id` int(11) NOT NULL,
  `test_case_id` int(11) NOT NULL,
  `order_no` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_suite_case` (`suite_id`,`test_case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_suite_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `test_suite_cases` WRITE;
/*!40000 ALTER TABLE `test_suite_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_suite_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `test_suites`
--

DROP TABLE IF EXISTS `test_suites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_suites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `suite_id` varchar(50) NOT NULL,
  `project_id` int(11) NOT NULL,
  `suite_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('draft','active','completed','archived') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `suite_id` (`suite_id`),
  KEY `fk_testsuites_project` (`project_id`),
  KEY `fk_testsuites_created_by` (`created_by`),
  CONSTRAINT `fk_testsuites_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_testsuites_project` FOREIGN KEY (`project_id`) REFERENCES `qa_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_suites`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `test_suites` WRITE;
/*!40000 ALTER TABLE `test_suites` DISABLE KEYS */;
INSERT INTO `test_suites` VALUES
(1,'SUITE-001',1,'Functional Tests','All functional test cases for website','high','active',8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(2,'SUITE-002',1,'Regression Tests','Regression test suite for website','high','active',8,'2026-08-01 19:52:58','2026-08-01 19:52:58'),
(3,'SUITE-003',2,'App Functional Tests','Mobile app functional test suite','high','draft',8,'2026-08-01 19:52:58','2026-08-01 19:52:58');
/*!40000 ALTER TABLE `test_suites` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ticket_categories`
--

DROP TABLE IF EXISTS `ticket_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ticket_categories` WRITE;
/*!40000 ALTER TABLE `ticket_categories` DISABLE KEYS */;
INSERT INTO `ticket_categories` VALUES
(1,'General Inquiry','General questions about services',1,1),
(2,'Technical Issue','Problems with dashboard or login',1,2),
(3,'Payment Related','Issues with payments, invoices, refunds',1,3),
(4,'Case Status','Updates about your credit repair case',1,4),
(5,'Document Support','Help with document upload and verification',1,5),
(6,'Dispute Assistance','Help with filing or tracking disputes',1,6),
(7,'Report Error','Report incorrect information in CIBIL report',1,7),
(8,'Feedback','Share your feedback or suggestions',1,8);
/*!40000 ALTER TABLE `ticket_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ticket_escalations`
--

DROP TABLE IF EXISTS `ticket_escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_escalations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending',
  `escalated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_escalations_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_escalations`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ticket_escalations` WRITE;
/*!40000 ALTER TABLE `ticket_escalations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_escalations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ticket_replies`
--

DROP TABLE IF EXISTS `ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_admin_reply` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_replies_ticket` (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_replies`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ticket_replies` WRITE;
/*!40000 ALTER TABLE `ticket_replies` DISABLE KEYS */;
INSERT INTO `ticket_replies` VALUES
(1,1,72,'I have tried multiple times but still getting error.',NULL,0,'2026-05-19 12:55:32',NULL,0),
(2,1,0,'Please clear your browser cache and try again. Also ensure file size is less than 5MB.',NULL,1,'2026-05-20 12:55:32',NULL,0),
(3,1,72,'It worked! Thank you for your help.',NULL,0,'2026-05-21 12:55:32',NULL,0),
(4,2,73,'Any update on my dispute? It has been 2 weeks.',NULL,0,'2026-05-29 12:55:32',NULL,0),
(5,2,0,'We have escalated this to the bank. Will update you within 3 days.',NULL,1,'2026-05-30 12:55:32',NULL,0),
(6,3,74,'Please check and send the receipt.',NULL,0,'2026-06-01 12:55:32',NULL,0),
(7,1,0,'We are looking into your issue. Will update soon.',NULL,1,'2026-06-02 13:12:42',NULL,0);
/*!40000 ALTER TABLE `ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tier_history`
--

DROP TABLE IF EXISTS `tier_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tier_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `old_tier` tinyint(4) DEFAULT NULL,
  `new_tier` tinyint(4) DEFAULT NULL,
  `old_commission` decimal(5,2) DEFAULT NULL,
  `new_commission` decimal(5,2) DEFAULT NULL,
  `changed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_partner_id` (`partner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tier_history`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tier_history` WRITE;
/*!40000 ALTER TABLE `tier_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `tier_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_assessment_results`
--

DROP TABLE IF EXISTS `training_assessment_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_assessment_results` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `passed` tinyint(1) DEFAULT 0,
  `answers_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers_json`)),
  `completed_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assessment` (`assessment_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_passed` (`passed`),
  CONSTRAINT `training_assessment_results_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `training_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_assessment_results_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_assessment_results_ibfk_3` FOREIGN KEY (`assessment_id`) REFERENCES `training_assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_assessment_results_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_assessment_results`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_assessment_results` WRITE;
/*!40000 ALTER TABLE `training_assessment_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_assessment_results` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_assessments`
--

DROP TABLE IF EXISTS `training_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `assessment_name` varchar(255) NOT NULL,
  `total_questions` int(11) DEFAULT 0,
  `passing_score` int(11) DEFAULT 70,
  `time_limit_minutes` int(11) DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  CONSTRAINT `training_assessments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_assessments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_assessments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_assessments` WRITE;
/*!40000 ALTER TABLE `training_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_assessments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_certifications`
--

DROP TABLE IF EXISTS `training_certifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_certifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certification_name` varchar(255) NOT NULL,
  `issuing_body` varchar(255) NOT NULL,
  `certification_type` enum('professional','compliance','technical','leadership') DEFAULT 'professional',
  `validity_years` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_certification_type` (`certification_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_certifications`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_certifications` WRITE;
/*!40000 ALTER TABLE `training_certifications` DISABLE KEYS */;
INSERT INTO `training_certifications` VALUES
(1,'Certified Credit Repair Specialist','Credit Repair Association','professional',2,1,'2026-06-04 18:22:23'),
(2,'GDPR Compliance Professional','International Compliance Institute','compliance',1,1,'2026-06-04 18:22:23'),
(3,'Data Protection Officer','Data Protection Council','compliance',2,1,'2026-06-04 18:22:23'),
(4,'Credit Analysis Professional','Credit Institute','technical',3,1,'2026-06-04 18:22:23'),
(5,'Customer Service Excellence','Service Quality International','professional',2,1,'2026-06-04 18:22:23'),
(6,'Certified Credit Repair Specialist','Credit Repair Association','professional',2,1,'2026-06-04 18:23:23'),
(7,'GDPR Compliance Professional','International Compliance Institute','compliance',1,1,'2026-06-04 18:23:23'),
(8,'Data Protection Officer','Data Protection Council','compliance',2,1,'2026-06-04 18:23:23'),
(9,'Credit Analysis Professional','Credit Institute','technical',3,1,'2026-06-04 18:23:23'),
(10,'Customer Service Excellence','Service Quality International','professional',2,1,'2026-06-04 18:23:23'),
(11,'Credit Repair Specialist','CIBIL Repair Academy','professional',2,1,'2026-08-01 19:06:33'),
(12,'Customer Service Professional','CIBIL Repair Academy','professional',1,1,'2026-08-01 19:06:33');
/*!40000 ALTER TABLE `training_certifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_courses`
--

DROP TABLE IF EXISTS `training_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_type` enum('compliance','technical','soft_skills','product_knowledge','leadership') DEFAULT 'technical',
  `description` text DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT 0.00,
  `passing_score` int(11) DEFAULT 70,
  `is_mandatory` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_course_type` (`course_type`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_mandatory` (`is_mandatory`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_courses`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_courses` WRITE;
/*!40000 ALTER TABLE `training_courses` DISABLE KEYS */;
INSERT INTO `training_courses` VALUES
(1,'COMP-101','GDPR Compliance Training','compliance',NULL,4.00,80,1,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(2,'COMP-102','Data Privacy & Protection','compliance',NULL,3.00,75,1,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(3,'COMP-103','Anti-Money Laundering (AML)','compliance',NULL,5.00,80,1,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(4,'TECH-101','Credit Report Analysis','technical',NULL,8.00,70,1,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(5,'TECH-102','Dispute Management System','technical',NULL,6.00,75,1,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(6,'PROD-101','CIBIL Repair Process','product_knowledge',NULL,4.00,70,1,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(7,'SOFT-101','Customer Communication Skills','soft_skills',NULL,3.00,65,0,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(8,'SOFT-102','Conflict Resolution','soft_skills',NULL,2.00,60,0,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23'),
(9,'LEAD-101','Team Leadership Basics','leadership',NULL,10.00,75,0,1,1,'2026-06-04 18:22:23','2026-06-04 18:22:23');
/*!40000 ALTER TABLE `training_courses` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_employee_certs`
--

DROP TABLE IF EXISTS `training_employee_certs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_employee_certs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `certification_id` int(11) NOT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `issued_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `idx_user` (`user_id`),
  KEY `idx_certification` (`certification_id`),
  KEY `idx_expiry_date` (`expiry_date`),
  CONSTRAINT `training_employee_certs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_employee_certs_ibfk_2` FOREIGN KEY (`certification_id`) REFERENCES `training_certifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_employee_certs_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_employee_certs_ibfk_4` FOREIGN KEY (`certification_id`) REFERENCES `training_certifications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_employee_certs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_employee_certs` WRITE;
/*!40000 ALTER TABLE `training_employee_certs` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_employee_certs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_employee_skills`
--

DROP TABLE IF EXISTS `training_employee_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_employee_skills` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `assessed_by` int(11) DEFAULT NULL,
  `assessed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_skill` (`user_id`,`skill_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_skill` (`skill_id`),
  CONSTRAINT `training_employee_skills_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_employee_skills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `training_skills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_employee_skills_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_employee_skills_ibfk_4` FOREIGN KEY (`skill_id`) REFERENCES `training_skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_employee_skills`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_employee_skills` WRITE;
/*!40000 ALTER TABLE `training_employee_skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_employee_skills` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_employees`
--

DROP TABLE IF EXISTS `training_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `completion_status` enum('registered','in_progress','completed','dropped') DEFAULT 'registered',
  `completion_date` date DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `feedback_rating` int(11) DEFAULT NULL,
  `feedback_comments` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `training_id` (`training_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `training_employees_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`),
  CONSTRAINT `training_employees_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_employees`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_employees` WRITE;
/*!40000 ALTER TABLE `training_employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_employees` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_enrollments`
--

DROP TABLE IF EXISTS `training_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_enrollments` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `progress_percentage` int(11) DEFAULT 0,
  `status` enum('not_started','in_progress','completed','failed','expired') DEFAULT 'not_started',
  `score` int(11) DEFAULT NULL,
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_path` varchar(500) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_course` (`user_id`,`course_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_completion_date` (`completion_date`),
  CONSTRAINT `training_enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_enrollments_ibfk_4` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_enrollments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_enrollments` WRITE;
/*!40000 ALTER TABLE `training_enrollments` DISABLE KEYS */;
INSERT INTO `training_enrollments` VALUES
(8,8,1,'2026-08-01',NULL,50,'in_progress',0,0,NULL,NULL,'2026-08-01 19:06:33','2026-08-01 19:06:33'),
(9,16,1,'2026-08-01',NULL,0,'not_started',0,0,NULL,NULL,'2026-08-01 19:06:33','2026-08-01 19:06:33'),
(10,17,2,'2026-08-01',NULL,30,'in_progress',0,0,NULL,NULL,'2026-08-01 19:06:33','2026-08-01 19:06:33');
/*!40000 ALTER TABLE `training_enrollments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_feedback`
--

DROP TABLE IF EXISTS `training_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT 0,
  `content_rating` int(11) DEFAULT 0,
  `trainer_rating` int(11) DEFAULT 0,
  `overall_rating` int(11) DEFAULT 0,
  `feedback` text DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_feedback`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_feedback` WRITE;
/*!40000 ALTER TABLE `training_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_feedback` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_materials`
--

DROP TABLE IF EXISTS `training_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` int(11) NOT NULL,
  `material_name` varchar(255) NOT NULL,
  `material_type` varchar(50) DEFAULT 'document',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_materials`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_materials` WRITE;
/*!40000 ALTER TABLE `training_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_materials` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_programs`
--

DROP TABLE IF EXISTS `training_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(50) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('technical','soft_skills','compliance','leadership','sales','product_knowledge','onboarding') DEFAULT 'technical',
  `type` enum('online','in_person','hybrid','self_paced') DEFAULT 'online',
  `duration_hours` int(11) DEFAULT 0,
  `duration_days` int(11) DEFAULT 0,
  `trainer` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','in_progress','completed','cancelled') DEFAULT 'draft',
  `target_audience` varchar(100) DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `materials` text DEFAULT NULL,
  `max_participants` int(11) DEFAULT 0,
  `cost` decimal(10,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_id` (`program_id`),
  KEY `fk_training_programs_created_by` (`created_by`),
  CONSTRAINT `fk_training_programs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `training_programs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_programs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_programs` WRITE;
/*!40000 ALTER TABLE `training_programs` DISABLE KEYS */;
INSERT INTO `training_programs` VALUES
(1,'TRN-2024-001','Credit Repair Fundamentals','Comprehensive training on credit repair processes and best practices.','technical','online',8,0,'Senior Trainer','published',NULL,NULL,NULL,NULL,0,0.00,8,'2026-08-01 19:06:33','2026-08-01 19:06:33'),
(2,'TRN-2024-002','Customer Service Excellence','Training on handling customer queries and building relationships.','soft_skills','in_person',6,0,'HR Manager','published',NULL,NULL,NULL,NULL,0,0.00,8,'2026-08-01 19:06:33','2026-08-01 19:06:33'),
(3,'TRN-2024-003','Compliance & Regulatory Standards','Understanding regulatory requirements and compliance procedures.','compliance','online',4,0,'Compliance Officer','published',NULL,NULL,NULL,NULL,0,0.00,8,'2026-08-01 19:06:33','2026-08-01 19:06:33'),
(4,'TRN-2024-004','Advanced Sales Techniques','Advanced sales techniques for credit repair services.','sales','hybrid',10,0,'Sales Director','draft',NULL,NULL,NULL,NULL,0,0.00,8,'2026-08-01 19:06:33','2026-08-01 19:06:33');
/*!40000 ALTER TABLE `training_programs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_session_attendees`
--

DROP TABLE IF EXISTS `training_session_attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_session_attendees` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `attendance_status` enum('registered','attended','absent','cancelled') DEFAULT 'registered',
  `feedback` text DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `attended_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_user` (`session_id`,`user_id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `training_session_attendees_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `training_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_session_attendees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_session_attendees_ibfk_3` FOREIGN KEY (`session_id`) REFERENCES `training_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_session_attendees_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_session_attendees`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_session_attendees` WRITE;
/*!40000 ALTER TABLE `training_session_attendees` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_session_attendees` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_sessions`
--

DROP TABLE IF EXISTS `training_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_name` varchar(255) NOT NULL,
  `course_id` int(11) NOT NULL,
  `trainer_name` varchar(255) NOT NULL,
  `session_type` enum('classroom','virtual','webinar','self_paced') DEFAULT 'virtual',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `max_capacity` int(11) DEFAULT 50,
  `current_enrollment` int(11) DEFAULT 0,
  `location` varchar(500) DEFAULT NULL,
  `meeting_link` varchar(500) DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  CONSTRAINT `training_sessions_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `training_sessions_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_sessions` WRITE;
/*!40000 ALTER TABLE `training_sessions` DISABLE KEYS */;
INSERT INTO `training_sessions` VALUES
(3,'GDPR Compliance Training - Batch 1',1,'John Smith','virtual','2026-06-11 18:24:13','2026-06-11 22:24:13',30,0,NULL,NULL,'scheduled',1,'2026-06-04 18:24:13'),
(4,'Credit Report Analysis Workshop',4,'Jane Doe','classroom','2026-06-18 18:24:13','2026-06-19 02:24:13',20,0,NULL,NULL,'scheduled',1,'2026-06-04 18:24:13'),
(5,'Batch 1 - Credit Repair',1,'Senior Trainer','virtual','2026-08-08 19:06:33','2026-08-09 19:06:33',20,5,'Online - Zoom',NULL,'scheduled',8,'2026-08-01 19:06:33'),
(6,'Batch 2 - Credit Repair',1,'Senior Trainer','virtual','2026-08-15 19:06:33','2026-08-16 19:06:33',25,3,'Online - Zoom',NULL,'scheduled',8,'2026-08-01 19:06:33'),
(7,'Customer Service Batch A',2,'HR Manager','classroom','2026-08-06 19:06:33','2026-08-07 19:06:33',15,8,'Training Room A - Ground Floor',NULL,'scheduled',8,'2026-08-01 19:06:33');
/*!40000 ALTER TABLE `training_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `training_skills`
--

DROP TABLE IF EXISTS `training_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(100) NOT NULL,
  `skill_category` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `skill_name` (`skill_name`),
  KEY `idx_category` (`skill_category`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_skills`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `training_skills` WRITE;
/*!40000 ALTER TABLE `training_skills` DISABLE KEYS */;
INSERT INTO `training_skills` VALUES
(1,'Credit Report Analysis','technical',1,'2026-06-04 18:22:23'),
(2,'Dispute Resolution','technical',1,'2026-06-04 18:22:23'),
(3,'Customer Communication','soft_skills',1,'2026-06-04 18:22:23'),
(4,'Compliance Knowledge','compliance',1,'2026-06-04 18:22:23'),
(5,'Leadership','leadership',1,'2026-06-04 18:22:23'),
(6,'Time Management','soft_skills',1,'2026-06-04 18:22:23'),
(7,'Problem Solving','soft_skills',1,'2026-06-04 18:22:23'),
(8,'Data Analysis','technical',1,'2026-06-04 18:22:23');
/*!40000 ALTER TABLE `training_skills` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `trainings`
--

DROP TABLE IF EXISTS `trainings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_code` varchar(50) DEFAULT NULL,
  `training_name` varchar(200) NOT NULL,
  `training_type` enum('technical','soft_skill','compliance','leadership','onboarding') DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `certification_provided` tinyint(4) DEFAULT 0,
  `cost_per_participant` decimal(12,2) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `training_code` (`training_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `trainings` WRITE;
/*!40000 ALTER TABLE `trainings` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) NOT NULL,
  `date` date DEFAULT curdate(),
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `method` varchar(50) DEFAULT 'Cash',
  `fee_amount` decimal(10,2) DEFAULT 0.00,
  `gst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `balance_after` decimal(15,2) DEFAULT 0.00,
  `reference_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wallet_id` (`wallet_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES
(1,1,'2026-07-28','Initial wallet setup',0.00,'credit','System',0.00,0.00,0.00,0.00,NULL,'completed','2026-07-28 07:36:48'),
(2,1,'2026-07-28','Test deposit via UPI',5000.00,'credit','0',0.00,900.00,5900.00,40000.00,'','completed','2026-07-28 07:40:12'),
(3,1,'2026-07-28','Test withdrawal to bank',2000.00,'debit','0',0.00,0.00,0.00,38000.00,NULL,'completed','2026-07-28 07:41:15'),
(4,1,'2026-07-28','Business deposit',10000.00,'credit','0',0.00,1800.00,11800.00,48000.00,'','completed','2026-07-28 07:41:57'),
(5,1,'2026-07-28','Deposit',5000.00,'credit','0',0.00,900.00,5900.00,53000.00,'','completed','2026-07-28 08:13:48'),
(6,1,'2026-07-28','Withdrawal',2000.00,'debit','0',0.00,0.00,0.00,51000.00,NULL,'completed','2026-07-28 08:13:48'),
(7,0,'2026-07-29','Withdrawal',1000.00,'debit','Bank Transfer',0.00,0.00,0.00,50000.00,NULL,'completed','2026-07-29 16:22:45'),
(8,0,'2026-07-29','Wallet recharge',5000.00,'credit','UPI',0.00,0.00,0.00,55000.00,NULL,'completed','2026-07-29 16:22:45'),
(9,0,'2026-07-29','Withdrawal to bank account',1000.00,'debit','Bank Transfer',0.00,0.00,0.00,54000.00,NULL,'completed','2026-07-29 16:23:31'),
(10,0,'2026-07-29','Test recharge',5000.00,'credit','UPI',0.00,0.00,0.00,59000.00,NULL,'completed','2026-07-29 16:25:18'),
(11,0,'2026-07-29','Test withdrawal',1000.00,'debit','Bank Transfer',0.00,0.00,0.00,58000.00,NULL,'completed','2026-07-29 16:25:18'),
(12,0,'2026-07-29','Test recharge',5000.00,'credit','UPI',0.00,0.00,0.00,63000.00,NULL,'completed','2026-07-29 16:26:20'),
(13,0,'2026-07-29','Test withdrawal',1000.00,'debit','Bank Transfer',0.00,0.00,0.00,62000.00,NULL,'completed','2026-07-29 16:26:21'),
(14,0,'2026-07-29','Test recharge',5000.00,'credit','UPI',0.00,0.00,0.00,67000.00,NULL,'completed','2026-07-29 16:30:51'),
(15,0,'2026-07-29','Test withdrawal',1000.00,'debit','Bank Transfer',0.00,0.00,0.00,66000.00,NULL,'completed','2026-07-29 16:30:51'),
(16,0,'2026-07-29','Test from console',1000.00,'credit','Console Test',0.00,0.00,0.00,67000.00,NULL,'completed','2026-07-29 16:34:38'),
(17,0,'2026-07-29','Withdraw test',500.00,'debit','Console Test',0.00,0.00,0.00,66500.00,NULL,'completed','2026-07-29 16:34:38');
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_2fa`
--

DROP TABLE IF EXISTS `user_2fa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_2fa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `is_enabled` tinyint(4) DEFAULT 0,
  `backup_codes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `user_2fa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_2fa`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_2fa` WRITE;
/*!40000 ALTER TABLE `user_2fa` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_2fa` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `activity_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`activity_type`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_activity` WRITE;
/*!40000 ALTER TABLE `user_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_activity` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_logins`
--

DROP TABLE IF EXISTS `user_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `success` tinyint(4) DEFAULT 1,
  `failure_reason` varchar(100) DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_login_time` (`login_time`),
  CONSTRAINT `user_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_logins`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_logins` WRITE;
/*!40000 ALTER TABLE `user_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_logins` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_metadata`
--

DROP TABLE IF EXISTS `user_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `meta_key` varchar(100) NOT NULL,
  `meta_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_meta` (`user_id`,`meta_key`),
  KEY `idx_key` (`meta_key`),
  CONSTRAINT `user_metadata_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_metadata`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_metadata` WRITE;
/*!40000 ALTER TABLE `user_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_metadata` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `language` varchar(10) DEFAULT 'en',
  `date_format` varchar(20) DEFAULT 'd-m-Y',
  `time_format` varchar(20) DEFAULT 'h:i A',
  `notification_email` tinyint(4) DEFAULT 1,
  `notification_sms` tinyint(4) DEFAULT 0,
  `notification_push` tinyint(4) DEFAULT 1,
  `dashboard_layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dashboard_layout`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `postal_code` varchar(20) DEFAULT NULL,
  `phone_alternate` varchar(20) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_relation` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `user_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_security`
--

DROP TABLE IF EXISTS `user_security`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `twofa_enabled` tinyint(4) DEFAULT 0,
  `twofa_secret` varchar(100) DEFAULT NULL,
  `twofa_backup_codes` text DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `password_expiry_days` int(11) DEFAULT 90,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_reset_token` (`reset_token`),
  CONSTRAINT `user_security_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_security`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_security` WRITE;
/*!40000 ALTER TABLE `user_security` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_security` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tokens`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `user_tokens` WRITE;
/*!40000 ALTER TABLE `user_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client','partner','employee','hr') NOT NULL DEFAULT 'client',
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','active','inactive','deleted') DEFAULT 'approved',
  `status_changed_at` datetime DEFAULT NULL,
  `status_changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `unique_code` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_verified_by_code` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `twofa_enabled` tinyint(4) DEFAULT 0,
  `twofa_secret` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `postal_code` varchar(20) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `account_source` varchar(50) DEFAULT 'direct',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `language` varchar(10) DEFAULT 'en',
  `deleted_at` datetime DEFAULT NULL,
  `twofa_code` varchar(10) DEFAULT NULL,
  `twofa_expiry` datetime DEFAULT NULL,
  `employee_code` varchar(20) DEFAULT NULL,
  `partner_code` varchar(20) DEFAULT NULL,
  `client_code` varchar(20) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `employee_status` enum('active','probation','notice','inactive','terminated') DEFAULT 'active',
  `referral_code` varchar(50) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `partner_code` (`partner_code`),
  UNIQUE KEY `client_code` (`client_code`),
  KEY `fk_users_created_by` (`created_by`),
  KEY `idx_users_department` (`department_id`),
  KEY `idx_users_company` (`company_id`),
  KEY `idx_users_last_login` (`last_login`),
  KEY `idx_users_last_activity` (`last_activity`),
  KEY `idx_users_role_id` (`role_id`),
  KEY `idx_users_deleted_at` (`deleted_at`),
  KEY `fk_users_supervisor` (`supervisor_id`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_by` (`deleted_by`),
  KEY `idx_status_changed_at` (`status_changed_at`),
  KEY `idx_status_changed_by` (`status_changed_by`),
  KEY `idx_status_deleted` (`status`,`deleted_at`),
  KEY `idx_reset_token` (`reset_token`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `_archived_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_10` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_11` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_12` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_13` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_4` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_5` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_6` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_7` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_8` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_ibfk_9` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(8,'Your Name','info@cibilrepair.in','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,NULL,NULL,'active',NULL,NULL,'2026-07-27 15:27:43','2026-07-30 08:13:31',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(16,'Micro Vision','laxmikundan10@gmail.com','$2y$10$/wU4TtsSgNrCLOirq0VXyOIeAUAXsE2xL.nDeF8Y/SpY4zikJ9Y6q','partner','9905482503',NULL,NULL,'active',NULL,NULL,'2026-07-31 01:15:28','2026-07-31 01:28:25',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(17,'Kundan','kundankhatri@gmail.com','$2y$10$6dUULpp2nv5jgBP2cr6U6eEqHfrzUdZEPXOMLXQlJ/./H9Y5rXnha','partner','8709455441',NULL,NULL,'active',NULL,NULL,'2026-07-31 09:34:23','2026-07-31 09:34:23',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(19,'Kundan Khatri','khotegiri@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uHEWG/igi','partner','9876543210',NULL,NULL,'active',NULL,NULL,'2026-07-31 09:52:44','2026-07-31 09:52:44',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users_backup_202501`
--

DROP TABLE IF EXISTS `users_backup_202501`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_backup_202501` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','client','partner','employee','hr') NOT NULL DEFAULT 'client',
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','active','inactive','deleted') DEFAULT 'approved',
  `status_changed_at` datetime DEFAULT NULL,
  `status_changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `unique_code` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_verified_by_code` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `twofa_enabled` tinyint(4) DEFAULT 0,
  `twofa_secret` varchar(100) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `postal_code` varchar(20) DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `account_source` varchar(50) DEFAULT 'direct',
  `timezone` varchar(50) DEFAULT 'Asia/Kolkata',
  `language` varchar(10) DEFAULT 'en',
  `deleted_at` datetime DEFAULT NULL,
  `twofa_code` varchar(10) DEFAULT NULL,
  `twofa_expiry` datetime DEFAULT NULL,
  `employee_code` varchar(20) DEFAULT NULL,
  `partner_code` varchar(20) DEFAULT NULL,
  `client_code` varchar(20) DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `joined_date` date DEFAULT NULL,
  `exit_date` date DEFAULT NULL,
  `employee_status` enum('active','probation','notice','inactive','terminated') DEFAULT 'active',
  `referral_code` varchar(50) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_backup_202501`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users_backup_202501` WRITE;
/*!40000 ALTER TABLE `users_backup_202501` DISABLE KEYS */;
INSERT INTO `users_backup_202501` VALUES
(1,'Final Admin Name 1785340617600','final_1785340617600@cibilrepair.in','0192023a7bbd73250516f069df18b500','admin','9876517600','Mumbai',NULL,'active',NULL,NULL,'2026-07-19 07:51:56','2026-07-29 15:56:57','2026-07-29 10:20:02',NULL,0,NULL,NULL,NULL,0,'f6a783f322761f8b41ce0a8194ddac519ed9e40800fb7c5bd4faa951fcc40c4a','2026-07-27 16:21:07','2409:40e5:1123:de59:e8e1:bd90:5d6f:dda3',0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(4,'Priya Sharma','priya@example.com','$2y$10$H7zPZq7zPZq7zPZq7zPZquO5uO5uO5uO5uO5uO5uO5uO5uO5uO5u','client','9876543211','Mumbai',NULL,'active',NULL,NULL,'2026-07-19 07:51:56','2026-07-27 15:19:50',NULL,NULL,0,NULL,NULL,NULL,0,'4aad599b41c7d513161013b99334d6eb5fce8fcd0c1830e9c2945a8eefd212d8','2026-07-27 16:19:50',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(6,'Test Pending User 1785020267255','pending1785020267255@test.com','','client','9876543168',NULL,NULL,'active','2026-07-25 23:01:54',1,'2026-07-25 22:57:47','2026-07-25 23:03:49',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(7,'Kundan Khatri','kundankhatri@gmail.com','0192023a7bbd73250516f069df18b500','admin',NULL,NULL,NULL,'active',NULL,NULL,'2026-07-27 15:16:05','2026-07-29 11:04:29',NULL,NULL,0,NULL,NULL,NULL,0,'09f05e63b26283e96c66b368c782c49dd4c96a34dee1733b1aa00310fc1fbd11','2026-07-27 16:16:21',NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(8,'Your Name','info@cibilrepair.in','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',NULL,NULL,NULL,'active',NULL,NULL,'2026-07-27 15:27:43','2026-07-30 08:13:31',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(9,'New Partner','partner@cibilrepair.in','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','partner',NULL,NULL,NULL,'active',NULL,NULL,'2026-07-30 08:46:31','2026-07-30 08:46:31',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
(14,'Laxmi','loanwala75@gmail.com','1716b5654e1785687b2a8aed2fc71046','partner','8709455441',NULL,NULL,'active',NULL,NULL,'2026-07-30 23:30:42','2026-07-30 23:30:42',NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL);
/*!40000 ALTER TABLE `users_backup_202501` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `verification_docs`
--

DROP TABLE IF EXISTS `verification_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `doc_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_docs_client` (`client_id`),
  KEY `idx_docs_status` (`status`),
  CONSTRAINT `verification_docs_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `verification_docs_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_docs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `verification_docs` WRITE;
/*!40000 ALTER TABLE `verification_docs` DISABLE KEYS */;
INSERT INTO `verification_docs` VALUES
(9,2,'PAN Card - Priya Sharma','pan','/uploads/pan_priya.pdf',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-01 17:23:43','2026-08-01 17:23:43'),
(10,2,'Aadhaar Card - Priya Sharma','aadhaar','/uploads/aadhaar_priya.pdf',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-01 17:23:43','2026-08-01 17:23:43'),
(11,3,'Aadhaar Card - Suresh Singh','aadhaar','/uploads/aadhaar_suresh.pdf',NULL,NULL,'pending',NULL,NULL,NULL,'2026-08-01 17:23:43','2026-08-01 17:23:43'),
(12,4,'Passport - Meena Patel','passport','/uploads/passport_meena.pdf',NULL,NULL,'pending',NULL,NULL,NULL,'2026-08-01 17:23:43','2026-08-01 17:23:43'),
(13,2,'PAN Card - Priya Sharma','pan_card','',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-02 08:34:10','2026-08-02 08:34:10'),
(14,2,'Aadhaar Card - Priya Sharma','aadhaar','',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-02 08:34:10','2026-08-02 08:34:10'),
(15,3,'PAN Card - Suresh Singh','pan_card','',NULL,NULL,'pending',NULL,NULL,NULL,'2026-08-02 08:34:10','2026-08-02 08:34:10'),
(16,4,'Passport - Meena Patel','passport','',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-02 08:34:10','2026-08-02 08:34:10'),
(17,5,'Voter ID - Arun Verma','voter_id','',NULL,NULL,'pending',NULL,NULL,NULL,'2026-08-02 08:34:10','2026-08-02 08:34:10'),
(18,2,'PAN Card - Priya Sharma','pan_card','',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-02 08:35:19','2026-08-02 08:35:19'),
(19,2,'Aadhaar Card - Priya Sharma','aadhaar','',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-02 08:35:19','2026-08-02 08:35:19'),
(20,3,'PAN Card - Suresh Singh','pan_card','',NULL,NULL,'pending',NULL,NULL,NULL,'2026-08-02 08:35:19','2026-08-02 08:35:19'),
(21,4,'Passport - Meena Patel','passport','',NULL,NULL,'verified',NULL,NULL,NULL,'2026-08-02 08:35:19','2026-08-02 08:35:19'),
(22,5,'Voter ID - Arun Verma','voter_id','',NULL,NULL,'pending',NULL,NULL,NULL,'2026-08-02 08:35:19','2026-08-02 08:35:19');
/*!40000 ALTER TABLE `verification_docs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Temporary table structure for view `vw_active_agreements`
--

DROP TABLE IF EXISTS `vw_active_agreements`;
/*!50001 DROP VIEW IF EXISTS `vw_active_agreements`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_active_agreements` AS SELECT
 NULL AS `id`,
 NULL AS `agreement_no`,
 NULL AS `client_name`,
 NULL AS `agreement_type`,
 NULL AS `issue_date`,
 NULL AS `expiry_date`,
 NULL AS `status`,
 NULL AS `days_remaining` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_api_error_rate`
--

DROP TABLE IF EXISTS `vw_api_error_rate`;
/*!50001 DROP VIEW IF EXISTS `vw_api_error_rate`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_api_error_rate` AS SELECT
 NULL AS `hour`,
 NULL AS `total_requests`,
 NULL AS `errors`,
 NULL AS `error_rate` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_current_system_status`
--

DROP TABLE IF EXISTS `vw_current_system_status`;
/*!50001 DROP VIEW IF EXISTS `vw_current_system_status`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_current_system_status` AS SELECT
 NULL AS `server_name`,
 NULL AS `cpu_usage`,
 NULL AS `memory_usage`,
 NULL AS `disk_usage`,
 NULL AS `cpu_status`,
 NULL AS `memory_status`,
 NULL AS `last_update` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_partner_tiers`
--

DROP TABLE IF EXISTS `vw_partner_tiers`;
/*!50001 DROP VIEW IF EXISTS `vw_partner_tiers`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_partner_tiers` AS SELECT
 NULL AS `partner_id`,
 NULL AS `partner_name`,
 NULL AS `partner_email`,
 NULL AS `tier_id`,
 NULL AS `tier_name`,
 NULL AS `commission_rate`,
 NULL AS `monthly_referrals`,
 NULL AS `tier_updated_at`,
 NULL AS `total_conversions` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `vw_pending_kyc`
--

DROP TABLE IF EXISTS `vw_pending_kyc`;
/*!50001 DROP VIEW IF EXISTS `vw_pending_kyc`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `vw_pending_kyc` AS SELECT
 NULL AS `id`,
 NULL AS `client_name`,
 NULL AS `client_email`,
 NULL AS `client_phone`,
 NULL AS `verification_status`,
 NULL AS `submitted_at`,
 NULL AS `days_pending` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `wallet`
--

DROP TABLE IF EXISTS `wallet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 1,
  `balance` decimal(10,2) DEFAULT 0.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `wallet` WRITE;
/*!40000 ALTER TABLE `wallet` DISABLE KEYS */;
INSERT INTO `wallet` VALUES
(1,1,66500.00,'2026-07-29 16:34:38'),
(2,8,0.00,'2026-07-30 08:14:11'),
(3,9,0.00,'2026-07-30 15:17:40'),
(4,15,0.00,'2026-07-31 00:35:43'),
(5,16,0.00,'2026-07-31 01:34:43');
/*!40000 ALTER TABLE `wallet` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('credit','debit') DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `whatsapp_chats`
--

DROP TABLE IF EXISTS `whatsapp_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `is_incoming` tinyint(4) DEFAULT 1,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_chats`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `whatsapp_chats` WRITE;
/*!40000 ALTER TABLE `whatsapp_chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_chats` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `whatsapp_logs`
--

DROP TABLE IF EXISTS `whatsapp_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_number` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_logs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `whatsapp_logs` WRITE;
/*!40000 ALTER TABLE `whatsapp_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_logs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `whatsapp_queue`
--

DROP TABLE IF EXISTS `whatsapp_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_queue`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `whatsapp_queue` WRITE;
/*!40000 ALTER TABLE `whatsapp_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_queue` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `work_cases`
--

DROP TABLE IF EXISTS `work_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_no` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','closed') DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `sla_due` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_no` (`case_no`),
  KEY `assigned_to` (`assigned_to`),
  KEY `status` (`status`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `work_cases_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_cases`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_cases` WRITE;
/*!40000 ALTER TABLE `work_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_cases` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Final view structure for view `client_summary`
--

/*!50001 DROP VIEW IF EXISTS `client_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `client_summary` AS select `u`.`id` AS `client_id`,`u`.`name` AS `client_name`,`u`.`email` AS `email`,`u`.`phone` AS `phone`,(select count(0) from `client_cases` where `client_id` = `u`.`id`) AS `total_cases`,(select count(0) from `payments` where `client_id` = `u`.`id` and `payments`.`status` = 'success') AS `total_payments`,(select sum(`payments`.`amount`) from `payments` where `client_id` = `u`.`id` and `payments`.`status` = 'success') AS `total_amount`,`u`.`created_at` AS `registered_date` from `users` `u` where `u`.`role` = 'client' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `monthly_revenue`
--

/*!50001 DROP VIEW IF EXISTS `monthly_revenue`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `monthly_revenue` AS select date_format(`payments`.`date`,'%Y-%m') AS `month`,count(0) AS `transaction_count`,sum(`payments`.`amount`) AS `total_revenue`,avg(`payments`.`amount`) AS `average_transaction` from `payments` where `payments`.`status` = 'success' group by date_format(`payments`.`date`,'%Y-%m') order by date_format(`payments`.`date`,'%Y-%m') desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_active_agreements`
--

/*!50001 DROP VIEW IF EXISTS `vw_active_agreements`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_active_agreements` AS select `la`.`id` AS `id`,`la`.`agreement_no` AS `agreement_no`,`c`.`name` AS `client_name`,`la`.`agreement_type` AS `agreement_type`,`la`.`issue_date` AS `issue_date`,`la`.`expiry_date` AS `expiry_date`,`la`.`status` AS `status`,to_days(`la`.`expiry_date`) - to_days(curdate()) AS `days_remaining` from (`legal_agreements` `la` join `clients` `c` on(`la`.`client_id` = `c`.`id`)) where `la`.`status` in ('sent','signed') and (`la`.`expiry_date` is null or `la`.`expiry_date` >= curdate()) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_api_error_rate`
--

/*!50001 DROP VIEW IF EXISTS `vw_api_error_rate`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_api_error_rate` AS select hour(`it_api_logs`.`created_at`) AS `hour`,count(0) AS `total_requests`,sum(case when `it_api_logs`.`status_code` >= 400 then 1 else 0 end) AS `errors`,round(sum(case when `it_api_logs`.`status_code` >= 400 then 1 else 0 end) / count(0) * 100,2) AS `error_rate` from `it_api_logs` where `it_api_logs`.`created_at` >= current_timestamp() - interval 24 hour group by hour(`it_api_logs`.`created_at`) order by hour(`it_api_logs`.`created_at`) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_current_system_status`
--

/*!50001 DROP VIEW IF EXISTS `vw_current_system_status`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_current_system_status` AS select `it_system_health`.`server_name` AS `server_name`,`it_system_health`.`cpu_usage` AS `cpu_usage`,`it_system_health`.`memory_usage` AS `memory_usage`,`it_system_health`.`disk_usage` AS `disk_usage`,case when `it_system_health`.`cpu_usage` > 80 then 'Critical' when `it_system_health`.`cpu_usage` > 60 then 'Warning' else 'Healthy' end AS `cpu_status`,case when `it_system_health`.`memory_usage` > 85 then 'Critical' when `it_system_health`.`memory_usage` > 70 then 'Warning' else 'Healthy' end AS `memory_status`,`it_system_health`.`logged_at` AS `last_update` from `it_system_health` where (`it_system_health`.`server_name`,`it_system_health`.`logged_at`) in (select `it_system_health`.`server_name`,max(`it_system_health`.`logged_at`) from `it_system_health` group by `it_system_health`.`server_name`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_partner_tiers`
--

/*!50001 DROP VIEW IF EXISTS `vw_partner_tiers`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_partner_tiers` AS select `u`.`id` AS `partner_id`,`u`.`name` AS `partner_name`,`u`.`email` AS `partner_email`,`p`.`tier_id` AS `tier_id`,case `p`.`tier_id` when 1 then 'Bronze' when 2 then 'Silver' when 3 then 'Gold' when 4 then 'Platinum' when 5 then 'Diamond' else 'Bronze' end AS `tier_name`,case `p`.`tier_id` when 1 then 20 when 2 then 25 when 3 then 30 when 4 then 35 when 5 then 40 else 20 end AS `commission_rate`,`p`.`monthly_referrals` AS `monthly_referrals`,`p`.`tier_updated_at` AS `tier_updated_at`,(select count(0) from `leads` where `leads`.`partner_id` = `u`.`id` and `leads`.`status` = 'converted') AS `total_conversions` from (`users` `u` join `partners` `p` on(`u`.`id` = `p`.`user_id`)) where `u`.`role` = 'partner' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `vw_pending_kyc`
--

/*!50001 DROP VIEW IF EXISTS `vw_pending_kyc`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_pending_kyc` AS select `lk`.`id` AS `id`,`c`.`name` AS `client_name`,`c`.`email` AS `client_email`,`c`.`phone` AS `client_phone`,`lk`.`verification_status` AS `verification_status`,`lk`.`submitted_at` AS `submitted_at`,to_days(curdate()) - to_days(`lk`.`submitted_at`) AS `days_pending` from (`legal_kyc_verification` `lk` join `clients` `c` on(`lk`.`client_id` = `c`.`id`)) where `lk`.`verification_status` = 'pending' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-08-02 10:36:25
