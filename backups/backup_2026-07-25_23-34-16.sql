-- ============================================================
-- CIBIL REPAIR CRM Database Backup
-- Generated: 2026-07-25 23:34:16
-- Tables: 382
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
-- Table: `_archived_activities`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_activities`;
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

-- --------------------------------------------------------
-- Table: `_archived_activity_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_activity_logs`;
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

-- --------------------------------------------------------
-- Table: `_archived_analysis_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_analysis_history`;
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

-- --------------------------------------------------------
-- Table: `_archived_announcements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_announcements`;
CREATE TABLE `_archived_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `target_role` enum('all','admin','partner','client') DEFAULT 'all',
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_backup_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_backup_logs`;
CREATE TABLE `_archived_backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_bank_accounts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_bank_accounts`;
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

-- --------------------------------------------------------
-- Table: `_archived_bank_disputes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_bank_disputes`;
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

-- --------------------------------------------------------
-- Table: `_archived_bank_reconciliation`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_bank_reconciliation`;
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

-- --------------------------------------------------------
-- Table: `_archived_blog_posts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_blog_posts`;
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

-- --------------------------------------------------------
-- Table: `_archived_budgets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_budgets`;
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

-- --------------------------------------------------------
-- Table: `_archived_bureau_disputes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_bureau_disputes`;
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

-- --------------------------------------------------------
-- Table: `_archived_call_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_call_logs`;
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

-- --------------------------------------------------------
-- Table: `_archived_case_assignments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_case_assignments`;
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

-- --------------------------------------------------------
-- Table: `_archived_cibil_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_cibil_history`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_activity_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_activity_log`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_agreements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_agreements`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_issues`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_issues`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_login_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_login_history`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_messages`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_messages`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_payments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_payments`;
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

INSERT INTO `_archived_client_payments` VALUES 
('1','client@example.com','1','15000.00','paid','TXN123456','2026-05-02','2026-05-02 11:34:26');

-- --------------------------------------------------------
-- Table: `_archived_client_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_profiles`;
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

-- --------------------------------------------------------
-- Table: `_archived_client_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_client_reviews`;
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

INSERT INTO `_archived_client_reviews` VALUES 
('1','Maneet Singh','maneet@example.com','5','Thank you sir, thank you very much for doing my work in a very short time. Unauthorized loan removed from my wife\'s CIBIL within weeks.','approved','2026-05-02 11:34:26'),
('2','Sunil Enterprises','sunil@example.com','5','Excellent work done by this company. My credit score improved by 87 points after settled account removal.','approved','2026-05-02 11:34:26'),
('3','Ranjeet Thakur','ranjeet@example.com','5','Very professional team. They removed a suit filed entry from my CIBIL that was holding back my loan approval.','approved','2026-05-02 11:34:26');

-- --------------------------------------------------------
-- Table: `_archived_companies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_companies`;
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

-- --------------------------------------------------------
-- Table: `_archived_company_assets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_company_assets`;
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

-- --------------------------------------------------------
-- Table: `_archived_compliance_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_compliance_documents`;
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

-- --------------------------------------------------------
-- Table: `_archived_connectors`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_connectors`;
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

-- --------------------------------------------------------
-- Table: `_archived_consent_forms`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_consent_forms`;
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

-- --------------------------------------------------------
-- Table: `_archived_contacts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_contacts`;
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

-- --------------------------------------------------------
-- Table: `_archived_coupons`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_coupons`;
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

-- --------------------------------------------------------
-- Table: `_archived_credit_analysis`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_credit_analysis`;
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

-- --------------------------------------------------------
-- Table: `_archived_credit_issues`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_credit_issues`;
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

-- --------------------------------------------------------
-- Table: `_archived_customer_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_customer_requests`;
CREATE TABLE `_archived_customer_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `service` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_daily_operations_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_daily_operations_reports`;
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

-- --------------------------------------------------------
-- Table: `_archived_disclaimer_consent`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_disclaimer_consent`;
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

-- --------------------------------------------------------
-- Table: `_archived_dispute_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_dispute_documents`;
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

-- --------------------------------------------------------
-- Table: `_archived_document_analyses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_document_analyses`;
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

-- --------------------------------------------------------
-- Table: `_archived_document_verification_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_document_verification_logs`;
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

-- --------------------------------------------------------
-- Table: `_archived_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_documents`;
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

-- --------------------------------------------------------
-- Table: `_archived_email_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_email_logs`;
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

-- --------------------------------------------------------
-- Table: `_archived_email_queue`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_email_queue`;
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

-- --------------------------------------------------------
-- Table: `_archived_employee_incentives`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_employee_incentives`;
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

-- --------------------------------------------------------
-- Table: `_archived_employee_shifts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_employee_shifts`;
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

-- --------------------------------------------------------
-- Table: `_archived_error_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_error_logs`;
CREATE TABLE `_archived_error_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_message` text DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------
-- Table: `_archived_expenses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_expenses`;
CREATE TABLE `_archived_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_followup_reminders`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_followup_reminders`;
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

-- --------------------------------------------------------
-- Table: `_archived_followup_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_followup_settings`;
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

-- --------------------------------------------------------
-- Table: `_archived_followups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_followups`;
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

-- --------------------------------------------------------
-- Table: `_archived_franchise_commission`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_franchise_commission`;
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

-- --------------------------------------------------------
-- Table: `_archived_franchise_payouts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_franchise_payouts`;
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

-- --------------------------------------------------------
-- Table: `_archived_franchise_support_tickets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_franchise_support_tickets`;
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

-- --------------------------------------------------------
-- Table: `_archived_franchisees`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_franchisees`;
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

-- --------------------------------------------------------
-- Table: `_archived_grievances`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_grievances`;
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

-- --------------------------------------------------------
-- Table: `_archived_gst_invoices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_gst_invoices`;
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

-- --------------------------------------------------------
-- Table: `_archived_gst_returns`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_gst_returns`;
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

-- --------------------------------------------------------
-- Table: `_archived_interviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_interviews`;
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

-- --------------------------------------------------------
-- Table: `_archived_invoices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_invoices`;
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

-- --------------------------------------------------------
-- Table: `_archived_job_applications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_job_applications`;
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

-- --------------------------------------------------------
-- Table: `_archived_job_openings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_job_openings`;
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

-- --------------------------------------------------------
-- Table: `_archived_journal_entry_lines`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_journal_entry_lines`;
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

-- --------------------------------------------------------
-- Table: `_archived_kyc_records`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_kyc_records`;
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

-- --------------------------------------------------------
-- Table: `_archived_lead_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_lead_documents`;
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

-- --------------------------------------------------------
-- Table: `_archived_lead_followups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_lead_followups`;
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

-- --------------------------------------------------------
-- Table: `_archived_lead_score_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_lead_score_history`;
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

-- --------------------------------------------------------
-- Table: `_archived_lead_scores`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_lead_scores`;
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

-- --------------------------------------------------------
-- Table: `_archived_lead_scoring_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_lead_scoring_history`;
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

-- --------------------------------------------------------
-- Table: `_archived_loan_applications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_loan_applications`;
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

-- --------------------------------------------------------
-- Table: `_archived_loan_commission`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_loan_commission`;
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

-- --------------------------------------------------------
-- Table: `_archived_loans`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_loans`;
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

-- --------------------------------------------------------
-- Table: `_archived_login_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_login_history`;
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

-- --------------------------------------------------------
-- Table: `_archived_loyalty_points`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_loyalty_points`;
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

-- --------------------------------------------------------
-- Table: `_archived_loyalty_transactions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_loyalty_transactions`;
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

-- --------------------------------------------------------
-- Table: `_archived_magic_links`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_magic_links`;
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

-- --------------------------------------------------------
-- Table: `_archived_mobile_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_mobile_tokens`;
CREATE TABLE `_archived_mobile_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_ombudsman_cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_ombudsman_cases`;
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

-- --------------------------------------------------------
-- Table: `_archived_operation_cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_operation_cases`;
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

-- --------------------------------------------------------
-- Table: `_archived_operation_tasks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_operation_tasks`;
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

-- --------------------------------------------------------
-- Table: `_archived_otp_verification`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_otp_verification`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_applications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_applications`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_commissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_commissions`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_connectors`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_connectors`;
CREATE TABLE `_archived_partner_connectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_email` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `rate` decimal(5,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_partner_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_documents`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_followups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_followups`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_notifications`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_payouts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_payouts`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_settings`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_ticket_replies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_ticket_replies`;
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

-- --------------------------------------------------------
-- Table: `_archived_partner_tickets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_partner_tickets`;
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

-- --------------------------------------------------------
-- Table: `_archived_payment_orders`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_payment_orders`;
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

-- --------------------------------------------------------
-- Table: `_archived_payment_transactions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_payment_transactions`;
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

-- --------------------------------------------------------
-- Table: `_archived_payout_queue`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_payout_queue`;
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

-- --------------------------------------------------------
-- Table: `_archived_payout_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_payout_requests`;
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

-- --------------------------------------------------------
-- Table: `_archived_performance_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_performance_reviews`;
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

-- --------------------------------------------------------
-- Table: `_archived_privacy_consent`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_privacy_consent`;
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

-- --------------------------------------------------------
-- Table: `_archived_product_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_product_categories`;
CREATE TABLE `_archived_product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) DEFAULT NULL,
  `category_name` varchar(100) NOT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_products`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_products`;
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

-- --------------------------------------------------------
-- Table: `_archived_purchase_order_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_purchase_order_items`;
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

-- --------------------------------------------------------
-- Table: `_archived_purchase_orders`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_purchase_orders`;
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

-- --------------------------------------------------------
-- Table: `_archived_push_notifications_queue`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_push_notifications_queue`;
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

-- --------------------------------------------------------
-- Table: `_archived_push_subscriptions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_push_subscriptions`;
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

-- --------------------------------------------------------
-- Table: `_archived_quotations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_quotations`;
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

-- --------------------------------------------------------
-- Table: `_archived_rate_limits`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_rate_limits`;
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

-- --------------------------------------------------------
-- Table: `_archived_rbi_complaints`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_rbi_complaints`;
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

-- --------------------------------------------------------
-- Table: `_archived_reconciliation`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_reconciliation`;
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

-- --------------------------------------------------------
-- Table: `_archived_referrals`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_referrals`;
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

-- --------------------------------------------------------
-- Table: `_archived_refund_consent`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_refund_consent`;
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

-- --------------------------------------------------------
-- Table: `_archived_registration_codes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_registration_codes`;
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

-- --------------------------------------------------------
-- Table: `_archived_report_filters`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_report_filters`;
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

-- --------------------------------------------------------
-- Table: `_archived_transactions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_transactions`;
CREATE TABLE `_archived_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(200) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `_archived_unified_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `_archived_unified_reviews`;
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

-- --------------------------------------------------------
-- Table: `activities`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activities`;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `activity_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_log`;
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

-- --------------------------------------------------------
-- Table: `activity_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_logs`;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` VALUES 
('16','1','Admin','Activity logs cleared','Cleared activity logs - Mode: all, Deleted: 15 logs, Archived: 15 logs','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 23:07:30'),
('17',NULL,'Admin','Created customer','Customer ID: 12, Name: Test Customer 1785022455090, Email: test1785022455090@customer.com','2409:40e5:104b:6862:b800:420a:a525:82bf',NULL,'2026-07-25 23:34:15');

-- --------------------------------------------------------
-- Table: `activity_logs_archive`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_logs_archive`;
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

INSERT INTO `activity_logs_archive` VALUES 
('1','1','1','System','Database Initialized','CRM database created and seeded','127.0.0.1',NULL,'2026-07-19 07:51:56','2026-07-25 23:07:30','1','0'),
('2','2','1','Admin','First Login','Admin dashboard accessed','192.168.1.1',NULL,'2026-07-19 07:51:56','2026-07-25 23:07:30','1','0'),
('3','3',NULL,'Admin','partner_create','Created partner: TestPartner (ID: 4)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 16:28:01','2026-07-25 23:07:30','1','0'),
('4','4',NULL,'Admin','partner_create','Created partner: Console Test Partner (ID: 5)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 16:58:18','2026-07-25 23:07:30','1','0'),
('5','5',NULL,'Admin','partner_create','Created partner: Test Partner (ID: 13)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:20','2026-07-25 23:07:30','1','0'),
('6','6',NULL,'Admin','partner_create','Created partner: Delhi Finance (ID: 14)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:51','2026-07-25 23:07:30','1','0'),
('7','7',NULL,'Admin','partner_create','Created partner: Bangalore Solutions (ID: 15)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:51','2026-07-25 23:07:30','1','0'),
('8','8',NULL,'Admin','partner_create','Created partner: Mumbai Credit (ID: 16)','2409:40e5:11e0:612b:a190:57fc:bec9:ece3',NULL,'2026-07-19 17:16:51','2026-07-25 23:07:30','1','0'),
('9','9',NULL,'Admin','Created customer','Customer ID: 9, Name: Simple Customer 1785013101654, Email: simple1785013101654@customer.com','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 20:58:21','2026-07-25 23:07:30','1','0'),
('10','10',NULL,'Admin','Created customer','Customer ID: 10, Name: Full Customer 1785013156664, Email: full1785013156664@customer.com','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 20:59:17','2026-07-25 23:07:30','1','0'),
('11','11',NULL,'Admin','Created customer','Customer ID: 11, Name: Test Customer 1785013354835, Email: test1785013354835@customer.com','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:02:35','2026-07-25 23:07:30','1','0'),
('12','12',NULL,'Admin','Test Log','This is a test log entry','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:13:23','2026-07-25 23:07:30','1','0'),
('13','13',NULL,'Admin','Test Log','This is a test log entry','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:16:03','2026-07-25 23:07:30','1','0'),
('14','14',NULL,'Admin','Lead Added','Test Lead 1785015508586 (medium priority)','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:38:28','2026-07-25 23:07:30','1','0'),
('15','15',NULL,'Admin','Lead Added','Console Test 1785016149614 (medium priority)','2409:40e5:1015:6fdd:899b:d35f:3315:e082',NULL,'2026-07-25 21:49:09','2026-07-25 23:07:30','1','0');

-- --------------------------------------------------------
-- Table: `admin_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `admin_notifications`;
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

-- --------------------------------------------------------
-- Table: `admin_users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `admin_users`;
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

INSERT INTO `admin_users` VALUES 
('1','Super Admin','admin@cibilrepair.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','admin',NULL,'1','2026-05-29 21:32:03','2409:40e5:1172:185:7c7e:4d8f:d31a:1fd8','0','2026-05-29 14:37:51','2026-05-29 21:32:03','$2y$10$Z1Kth.91e4e41H4oumQ9jOeAABAeoSrGN2cpCoM3JWmxSKeXmuFIG','2026-06-28 21:32:03'),
('2','Kundan Khatri','kundankhatri@gmail.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','admin',NULL,'1',NULL,NULL,'0','2026-05-29 14:37:51','2026-05-29 15:15:41',NULL,NULL),
('3','Demo Partner','partner@cibilrepair.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','partner',NULL,'1',NULL,NULL,'1','2026-05-29 14:37:51','2026-05-29 18:23:54',NULL,NULL),
('4','Demo Client','client@cibilrepair.com','$2y$10$Z/WgU0HhwDHNcwNDtUvNm.lWK660wYv/879czby85Z4udHQY/9jk.','client',NULL,'1',NULL,NULL,'0','2026-05-29 14:37:51','2026-05-29 15:15:41',NULL,NULL);

-- --------------------------------------------------------
-- Table: `ai_predictions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ai_predictions`;
CREATE TABLE `ai_predictions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prediction_type` varchar(100) DEFAULT NULL,
  `predicted_value` decimal(12,2) DEFAULT NULL,
  `confidence` decimal(5,2) DEFAULT NULL,
  `prediction_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `analyses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `analyses`;
CREATE TABLE `analyses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(30) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `result` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_analysis_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `announcements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `announcements`;
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

-- --------------------------------------------------------
-- Table: `attendance`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `check_in_location` varchar(255) DEFAULT NULL,
  `check_out_location` varchar(255) DEFAULT NULL,
  `check_in_ip` varchar(45) DEFAULT NULL,
  `check_out_ip` varchar(45) DEFAULT NULL,
  `working_hours` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `late_minutes` int(11) DEFAULT 0,
  `early_exit_minutes` int(11) DEFAULT 0,
  `status` enum('present','absent','half_day','holiday','week_off','leave') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_date` (`employee_id`,`attendance_date`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `attendance` VALUES 
('6','1','2026-06-02','09:15:00','18:30:00',NULL,NULL,NULL,NULL,'8.50',NULL,'0','0','present',NULL,'2026-06-02 13:08:34'),
('7','2','2026-06-02','09:30:00','18:45:00',NULL,NULL,NULL,NULL,'8.25',NULL,'0','0','present',NULL,'2026-06-02 13:08:34'),
('8','3','2026-06-02','09:00:00','18:00:00',NULL,NULL,NULL,NULL,'8.00',NULL,'0','0','present',NULL,'2026-06-02 13:08:34'),
('9','4','2026-06-02','10:00:00','19:00:00',NULL,NULL,NULL,NULL,'8.00',NULL,'0','0','',NULL,'2026-06-02 13:10:42'),
('10','5','2026-06-02',NULL,NULL,NULL,NULL,NULL,NULL,'0.00',NULL,'0','0','absent',NULL,'2026-06-02 13:10:42'),
('11','1','2026-06-03','09:00:00','18:00:00',NULL,NULL,NULL,NULL,'9.00',NULL,'0','0','present',NULL,'2026-06-03 19:03:52'),
('12','2','2026-06-03','09:30:00','18:30:00',NULL,NULL,NULL,NULL,'9.00',NULL,'0','0','present',NULL,'2026-06-03 19:03:52'),
('13','3','2026-06-03','09:45:00','18:15:00',NULL,NULL,NULL,NULL,'8.50',NULL,'0','0','present',NULL,'2026-06-03 19:03:52'),
('14','4','2026-06-03','09:20:00','18:00:00',NULL,NULL,NULL,NULL,'8.67',NULL,'0','0','present',NULL,'2026-06-03 19:03:52'),
('15','5','2026-06-03','09:15:00','18:00:00',NULL,NULL,NULL,NULL,'8.75',NULL,'0','0','present',NULL,'2026-06-03 19:03:52'),
('16','1','2026-06-03','09:00:00','18:00:00',NULL,NULL,NULL,NULL,'9.00',NULL,'0','0','present',NULL,'2026-06-03 19:04:16'),
('17','2','2026-06-03','09:30:00','18:30:00',NULL,NULL,NULL,NULL,'9.00',NULL,'0','0','present',NULL,'2026-06-03 19:04:16'),
('18','3','2026-06-03','09:45:00','18:15:00',NULL,NULL,NULL,NULL,'8.50',NULL,'0','0','present',NULL,'2026-06-03 19:04:16'),
('19','4','2026-06-03','09:20:00','18:00:00',NULL,NULL,NULL,NULL,'8.67',NULL,'0','0','present',NULL,'2026-06-03 19:04:16'),
('20','5','2026-06-03','09:15:00','18:00:00',NULL,NULL,NULL,NULL,'8.75',NULL,'0','0','present',NULL,'2026-06-03 19:04:16'),
('21','6','2026-06-03','10:00:00','19:00:00',NULL,NULL,NULL,NULL,'9.00',NULL,'0','0','present',NULL,'2026-06-03 19:04:16'),
('22','7','2026-06-03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0','0','absent',NULL,'2026-06-03 19:04:16'),
('23','8','2026-06-03','09:30:00','17:30:00',NULL,NULL,NULL,NULL,'8.00',NULL,'0','0','present',NULL,'2026-06-03 19:04:16');

-- --------------------------------------------------------
-- Table: `audit_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `audit_log`;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `audit_log` VALUES 
('2',NULL,'INSERT','payments','2',NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('3',NULL,'INSERT','payments','3',NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"1999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('4',NULL,'INSERT','payments','4',NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"9999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
('5',NULL,'INSERT','payments','5',NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('6',NULL,'INSERT','payments','6',NULL,'{\"client\":\"Amit Patel\",\"amount\":\"2999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('7',NULL,'INSERT','payments','7',NULL,'{\"client\":\"Neha Gupta\",\"amount\":\"1999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
('8',NULL,'INSERT','payments','8',NULL,'{\"client\":\"Vikram Singh\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('9',NULL,'INSERT','payments','9',NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"25000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
('10',NULL,'INSERT','payments','10',NULL,'{\"client\":\"Priya Sharma\",\"amount\":\"15000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
('11',NULL,'INSERT','payments','11',NULL,'{\"client\":\"Amit Patel\",\"amount\":\"50000.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-05 15:55:39'),
('12',NULL,'INSERT','payments','12',NULL,'{\"client\":\"Neha Gupta\",\"amount\":\"10000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
('13',NULL,'INSERT','payments','13',NULL,'{\"client\":\"Suresh Singh\",\"amount\":\"25000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
('14',NULL,'INSERT','payments','14',NULL,'{\"client\":\"Anita Desai\",\"amount\":\"15000.00\",\"status\":\"paid\"}',NULL,NULL,'2026-06-05 15:55:39'),
('15',NULL,'UPDATE','leads','25','{\"status\":\"new\"}','{\"status\":\"contacted\"}',NULL,NULL,'2026-06-28 15:42:58'),
('16',NULL,'UPDATE','leads','25','{\"status\":\"contacted\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:00:32'),
('17',NULL,'UPDATE','leads','23','{\"status\":\"contacted\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:02:12'),
('18',NULL,'UPDATE','leads','14','{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:12:26'),
('19',NULL,'UPDATE','leads','15','{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-06-29 15:12:26'),
('20',NULL,'UPDATE','leads','6','{\"status\":\"new\"}','{\"status\":\"converted\"}',NULL,NULL,'2026-07-19 07:54:26'),
('21',NULL,'INSERT','payments','15',NULL,'{\"client\":\"Rajesh Kumar\",\"amount\":\"25000.00\",\"status\":\"completed\"}',NULL,NULL,'2026-07-25 22:45:29');

-- --------------------------------------------------------
-- Table: `audit_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `audit_logs`;
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

INSERT INTO `audit_logs` VALUES 
('1','1','system_initialized','Database setup completed','127.0.0.1',NULL,'2026-06-02 18:07:44'),
('2','0','INSERT','Table: payments, Record ID: 2, Old: NULL, New: {\"client\":\"Rajesh Kumar\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('3','0','INSERT','Table: payments, Record ID: 3, Old: NULL, New: {\"client\":\"Rajesh Kumar\",\"amount\":\"1999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('4','0','INSERT','Table: payments, Record ID: 4, Old: NULL, New: {\"client\":\"Priya Sharma\",\"amount\":\"9999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
('5','0','INSERT','Table: payments, Record ID: 5, Old: NULL, New: {\"client\":\"Priya Sharma\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('6','0','INSERT','Table: payments, Record ID: 6, Old: NULL, New: {\"client\":\"Amit Patel\",\"amount\":\"2999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51'),
('7','0','INSERT','Table: payments, Record ID: 7, Old: NULL, New: {\"client\":\"Neha Gupta\",\"amount\":\"1999.00\",\"status\":\"pending\"}',NULL,NULL,'2026-06-02 12:45:51'),
('8','0','INSERT','Table: payments, Record ID: 8, Old: NULL, New: {\"client\":\"Vikram Singh\",\"amount\":\"4999.00\",\"status\":\"success\"}',NULL,NULL,'2026-06-02 12:45:51');

-- --------------------------------------------------------
-- Table: `backup_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `backup_logs`;
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

-- --------------------------------------------------------
-- Table: `bank_details`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bank_details`;
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

-- --------------------------------------------------------
-- Table: `bank_reconciliation`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bank_reconciliation`;
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

-- --------------------------------------------------------
-- Table: `bank_submissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bank_submissions`;
CREATE TABLE `bank_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `dispute_id_bank` varchar(100) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispute_id` (`dispute_id`),
  KEY `bank_name` (`bank_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `banks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `banks`;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `banks` VALUES 
('1','HDFC Bank','Rahul Mehta','rahul@hdfc.com','9876543210','active',NULL,'2026-07-19 07:51:56'),
('2','ICICI Bank','Neha Gupta','neha@icici.com','9876543211','active',NULL,'2026-07-19 07:51:56'),
('3','SBI','Amit Kumar','amit@sbi.co.in','9876543212','active',NULL,'2026-07-19 07:51:56'),
('4','Axis Bank','Priya Jain','priya@axisbank.com','9876543213','active',NULL,'2026-07-19 07:51:56'),
('5','Kotak Mahindra','Vikram Sethi','vikram@kotak.com','9876543214','active',NULL,'2026-07-19 07:51:56'),
('6','Test Bank 1785011490192','Test Contact','test1785011490192@bank.com','9876543628','active','Bank','2026-07-25 20:31:30'),
('7','Test Bank 1785012726544','Test Contact','test1785012726544@bank.com','9876543337','active','Bank','2026-07-25 20:52:06'),
('8','Test Bank 1785022455022','Test Contact','test1785022455022@bank.com','9876543934','active','Bank','2026-07-25 23:34:15');

-- --------------------------------------------------------
-- Table: `bureau_statistics`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bureau_statistics`;
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

INSERT INTO `bureau_statistics` VALUES 
('1','CIBIL','682.00','24','2026-06-01','2026-06-05 18:21:12'),
('2','Experian','675.00','22','2026-06-01','2026-06-05 18:21:12'),
('3','Equifax','688.00','20','2026-06-01','2026-06-05 18:21:12'),
('4','CRIF','680.00','18','2026-06-01','2026-06-05 18:21:12');

-- --------------------------------------------------------
-- Table: `bureau_submissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bureau_submissions`;
CREATE TABLE `bureau_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `bureau` enum('CIBIL','Experian','Equifax','CRIF') NOT NULL,
  `dispute_id_bureau` varchar(100) DEFAULT NULL,
  `submission_date` date DEFAULT NULL,
  `expected_response` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispute_id` (`dispute_id`),
  KEY `bureau` (`bureau`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `business_kpis`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `business_kpis`;
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

-- --------------------------------------------------------
-- Table: `call_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `call_logs`;
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

-- --------------------------------------------------------
-- Table: `cas`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cas`;
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

-- --------------------------------------------------------
-- Table: `case_assignments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `case_assignments`;
CREATE TABLE `case_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `case_timeline`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `case_timeline`;
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

INSERT INTO `case_timeline` VALUES 
('1','1','Case Created','completed','Your case has been successfully created','2026-05-09 11:59:01'),
('2','1','Document Verification','pending','Please upload required documents for verification','2026-05-09 11:59:01'),
('3','1','Dispute Filed','pending','We will file a dispute with the credit bureau','2026-05-09 11:59:01'),
('4','1','Bank Response','pending','Waiting for bank response on the dispute','2026-05-09 11:59:01'),
('5','1','Resolution','pending','Final resolution and CIBIL update','2026-05-09 11:59:01');

-- --------------------------------------------------------
-- Table: `cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cases`;
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

INSERT INTO `cases` VALUES 
('1','CR20250001','1','Rajesh Kumar','Written Off Clearance','completed','15000.00','2026-05-02 09:45:36'),
('2','CR20250002','1','Rajesh Kumar','Settled Clearance','in-progress','12000.00','2026-05-02 09:45:36'),
('3','CR202609465','1','Rajesh Kumar','CIBIL Dispute Resolution','pending','25000.00','2026-07-25 22:45:29');

-- --------------------------------------------------------
-- Table: `chart_of_accounts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `chart_of_accounts`;
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

INSERT INTO `chart_of_accounts` VALUES 
('1','1000','Cash','asset',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('2','1100','Bank Account','asset',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('3','1200','Accounts Receivable','asset',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('4','2000','Accounts Payable','liability',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('5','2100','GST Payable','liability',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('6','3000','Capital','equity',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('7','4000','Sales Revenue','income',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('8','4100','Service Revenue','income',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('9','5000','Salary Expense','expense',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('10','5100','Rent Expense','expense',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37'),
('11','5200','Utilities Expense','expense',NULL,'1','0.00','0.00',NULL,'2026-06-02 13:02:37');

-- --------------------------------------------------------
-- Table: `client_agreements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_agreements`;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `client_cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_cases`;
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

INSERT INTO `client_cases` VALUES 
('1','CR2025001','client@example.com','Rajesh Kumar','Written Off Clearance','dispute-filed','15000.00','2026-05-02 09:51:13',NULL,NULL,NULL,NULL,'2026-05-02 09:51:13'),
('2','CR2026001','client@example.com','Rajesh Kumar','Written Off Clearance','dispute-filed','15000.00','2026-05-02 11:34:26',NULL,NULL,NULL,NULL,'2026-05-02 11:34:26'),
('3','CR2026002','client@example.com','Priya Sharma','Settled Clearance','completed','12000.00','2026-05-02 11:34:26',NULL,NULL,NULL,NULL,'2026-05-02 11:34:26'),
('5','CASE-2024-001','rajesh@example.com','Rajesh Kumar','CIBIL Dispute Resolution','in-progress','4999.00','2026-06-02 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
('6','CASE-2024-002','rajesh@example.com','Rajesh Kumar','Credit Report Analysis','completed','1999.00','2026-05-03 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
('7','CASE-2024-003','priya@example.com','Priya Sharma','Written Off Settlement','pending','9999.00','2026-06-02 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
('8','CASE-2024-004','priya@example.com','Priya Sharma','Credit Score Improvement','in-progress','4999.00','2026-05-18 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
('9','CASE-2024-005','amit@example.com','Amit Patel','Document Verification','completed','2999.00','2026-04-18 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
('10','CASE-2024-006','neha@example.com','Neha Gupta','Loan Eligibility Check','pending','1999.00','2026-06-02 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31'),
('11','CASE-2024-007','vikram@example.com','Vikram Singh','CIBIL Dispute Resolution','in-progress','4999.00','2026-05-23 12:44:31',NULL,NULL,NULL,NULL,'2026-06-02 12:44:31');

-- --------------------------------------------------------
-- Table: `client_disputes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_disputes`;
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

-- --------------------------------------------------------
-- Table: `client_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_documents`;
CREATE TABLE `client_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_email` varchar(100) NOT NULL,
  `doc_type` enum('pan','aadhar','cibil') NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_data` longtext DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doc_type` (`client_email`,`doc_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `client_documents` VALUES 
('1','rajesh@example.com','aadhar','aadhar_rajesh_front.pdf','uploads/aadhar_rajesh.pdf',NULL,'application/pdf','2026-05-13 12:47:38'),
('2','rajesh@example.com','pan','pan_rajesh.pdf','uploads/pan_rajesh.pdf',NULL,'application/pdf','2026-05-13 12:47:38'),
('3','priya@example.com','aadhar','aadhar_priya.pdf','uploads/aadhar_priya.pdf',NULL,'application/pdf','2026-05-18 12:47:38'),
('4','priya@example.com','pan','pan_priya.pdf','uploads/pan_priya.pdf',NULL,'application/pdf','2026-05-18 12:47:38'),
('5','priya@example.com','cibil','cibil_report_priya.pdf','uploads/cibil_priya.pdf',NULL,'application/pdf','2026-05-23 12:47:38'),
('6','amit@example.com','pan','pan_amit.pdf','uploads/pan_amit.pdf',NULL,'application/pdf','2026-05-03 12:47:38'),
('7','amit@example.com','aadhar','aadhar_amit.pdf','uploads/aadhar_amit.pdf',NULL,'application/pdf','2026-05-03 12:47:38'),
('8','neha@example.com','cibil','cibil_report_neha.pdf','uploads/cibil_neha.pdf',NULL,'application/pdf','2026-05-28 12:47:38'),
('9','vikram@example.com','aadhar','aadhar_vikram.pdf','uploads/aadhar_vikram.pdf',NULL,'application/pdf','2026-05-23 12:47:38'),
('10','vikram@example.com','pan','pan_vikram.pdf','uploads/pan_vikram.pdf',NULL,'application/pdf','2026-05-23 12:47:38');

-- --------------------------------------------------------
-- Table: `client_invoices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_invoices`;
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

-- --------------------------------------------------------
-- Table: `client_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_notifications`;
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

-- --------------------------------------------------------
-- Table: `client_payments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_payments`;
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

-- --------------------------------------------------------
-- Table: `client_scores`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_scores`;
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

-- --------------------------------------------------------
-- Table: `client_summary`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `client_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `client_summary` AS select `u`.`id` AS `client_id`,`u`.`name` AS `client_name`,`u`.`email` AS `email`,`u`.`phone` AS `phone`,(select count(0) from `client_cases` where `client_id` = `u`.`id`) AS `total_cases`,(select count(0) from `payments` where `client_id` = `u`.`id` and `payments`.`status` = 'success') AS `total_payments`,(select sum(`payments`.`amount`) from `payments` where `client_id` = `u`.`id` and `payments`.`status` = 'success') AS `total_amount`,`u`.`created_at` AS `registered_date` from `users` `u` where `u`.`role` = 'client';

INSERT INTO `client_summary` VALUES 
('3','Rajesh Kumar','rajesh@example.com','9876543210','10','6','34995.00','2026-07-19 07:51:56'),
('4','Priya Sharma','priya@example.com','9876543211','10','6','34995.00','2026-07-19 07:51:56'),
('5','New Client 1785018634186','new1785018634186@client.com','9876543338','10','6','34995.00','2026-07-25 22:30:33'),
('6','Test Pending User 1785020267255','pending1785020267255@test.com','9876543168','10','6','34995.00','2026-07-25 22:57:47');

-- --------------------------------------------------------
-- Table: `clients`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `clients`;
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

INSERT INTO `clients` VALUES 
('1','Rajesh Kumar','rajesh@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','9876543210','active','2','2026-05-02 09:45:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'800',NULL,'0.00','0.00',NULL,NULL,'2026-05-18 09:53:32',NULL),
('2','Priya Sharma','priya@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','8765432109','active','1','2026-05-02 09:45:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'650',NULL,'0.00','0.00',NULL,NULL,'2026-05-18 09:48:05',NULL),
('3','Admin User','admin@cibilrepair.in','0192023a7bbd73250516f069df18b500','9999999999','active','0','2026-05-02 15:49:49',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'650',NULL,'0.00','0.00',NULL,NULL,'2026-05-18 09:44:10',NULL),
('4','Test Client','client@cibilrepair.in','3677b23baa08f74c28aba07f0cb6554e','9876543210','active','0','2026-05-02 15:50:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'650',NULL,'0.00','0.00',NULL,NULL,'2026-05-18 09:44:10',NULL),
('5','Partner User','partner@cibilrepair.in','3c0d9364bee6c8e4e71a2aecdc6cf57f','9876543211','active','0','2026-05-02 15:50:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'650',NULL,'0.00','0.00',NULL,NULL,'2026-05-18 09:44:10',NULL);

-- --------------------------------------------------------
-- Table: `commissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `commissions`;
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

INSERT INTO `commissions` VALUES 
('1','rajesh@example.com','5000.00','pending','2026-05-09','Written Off Clearance',NULL);

-- --------------------------------------------------------
-- Table: `compliance_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `compliance_documents`;
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

-- --------------------------------------------------------
-- Table: `connectors`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `connectors`;
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

INSERT INTO `connectors` VALUES 
('2','10','kundan','9153900118','','bank','','','','2026-06-28 14:50:30','0','0.00','active','15.00','0.00'),
('3','10','Test Connector','9876543210','test@connector.com','bank','HDFC Bank','Mumbai','Test connector','2026-06-29 16:33:44','0','0.00','active','15.00','0.00'),
('4','10','CA Rajesh','9876543211','rajesh@ca.com','ca','Rajesh & Co','Delhi','Chartered Accountant','2026-06-29 16:34:02','0','0.00','active','15.00','0.00'),
('5','10','Lawyer Priya','9876543212','priya@legal.com','lawyer','Priya Legal','Mumbai','Legal consultant','2026-06-29 16:34:02','0','0.00','active','15.00','0.00');

-- --------------------------------------------------------
-- Table: `consent_forms`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `consent_forms`;
CREATE TABLE `consent_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `consent_type` varchar(100) DEFAULT NULL,
  `requested_date` date DEFAULT NULL,
  `provided_date` date DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('pending','provided','expired') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `contacts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `contacts`;
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

INSERT INTO `contacts` VALUES 
('3','10','property','Santosh Kumar Pandey','Manager','07364979367','Santoshpandey@gmail.ocm','M/s Santosh Enterprises','Dhanbad','Jharkhan','826001','','2026-07-01 14:57:13','2026-07-01 15:19:00');

-- --------------------------------------------------------
-- Table: `credit_analyses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_analyses`;
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

INSERT INTO `credit_analyses` VALUES 
('1',NULL,'72','1','685','3','[\"written_off\",\"late_payment\",\"incorrect_enquiry\"]','Written off account with HDFC needs dispute',NULL,'2026-06-05 18:20:13','2026-06-05 18:20:13'),
('2',NULL,'73','1','720','1','[\"late_payment\"]','Single late payment from ICICI',NULL,'2026-06-05 18:20:13','2026-06-05 18:20:13'),
('3',NULL,'74','1','580','5','[\"written_off\",\"settled\",\"late_payment\",\"overdue\",\"duplicate_loan\"]','Multiple issues identified',NULL,'2026-06-05 18:20:13','2026-06-05 18:20:13'),
('4',NULL,'72','1','685','3','[\"written_off\",\"late_payment\",\"incorrect_enquiry\"]','Written off account with HDFC needs dispute',NULL,'2026-06-05 18:20:22','2026-06-05 18:20:22'),
('5',NULL,'73','1','720','1','[\"late_payment\"]','Single late payment from ICICI',NULL,'2026-06-05 18:20:22','2026-06-05 18:20:22'),
('6',NULL,'74','1','580','5','[\"written_off\",\"settled\",\"late_payment\",\"overdue\",\"duplicate_loan\"]','Multiple issues identified',NULL,'2026-06-05 18:20:22','2026-06-05 18:20:22'),
('7',NULL,'72','1','685','3','[\"written_off\",\"late_payment\",\"incorrect_enquiry\"]','Written off account with HDFC needs dispute',NULL,'2026-06-05 18:21:12','2026-06-05 18:21:12'),
('8',NULL,'73','1','720','1','[\"late_payment\"]','Single late payment from ICICI',NULL,'2026-06-05 18:21:12','2026-06-05 18:21:12'),
('9',NULL,'74','1','580','5','[\"written_off\",\"settled\",\"late_payment\",\"overdue\",\"duplicate_loan\"]','Multiple issues identified',NULL,'2026-06-05 18:21:12','2026-06-05 18:21:12');

-- --------------------------------------------------------
-- Table: `credit_analysis`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_analysis`;
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

-- --------------------------------------------------------
-- Table: `credit_analysis_results`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_analysis_results`;
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

-- --------------------------------------------------------
-- Table: `credit_issues`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_issues`;
CREATE TABLE `credit_issues` (
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
  KEY `idx_client` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `credit_issues` VALUES 
('1','72',NULL,'written_off','HDFC Bank','25000.00','Written off account from 2022','pending','2026-06-05 18:21:12'),
('2','72',NULL,'late_payment','ICICI Bank','5000.00','Late payment of 90+ days','disputed','2026-06-05 18:21:12'),
('3','72',NULL,'incorrect_enquiry','Axis Bank','0.00','Unauthorised hard enquiry','pending','2026-06-05 18:21:12'),
('4','73',NULL,'late_payment','ICICI Bank','3500.00','One time late payment','resolved','2026-06-05 18:21:12'),
('5','74',NULL,'written_off','SBI','45000.00','Credit card written off','pending','2026-06-05 18:21:12'),
('6','74',NULL,'settled','HDFC Bank','15000.00','Loan settled for less than full amount','pending','2026-06-05 18:21:12'),
('7','74',NULL,'overdue','Yes Bank','25000.00','Overdue personal loan EMI','pending','2026-06-05 18:21:12'),
('8','75',NULL,'duplicate_loan','ICICI Bank','500000.00','Duplicate home loan entry','','2026-06-05 18:21:12');

-- --------------------------------------------------------
-- Table: `credit_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_reports`;
CREATE TABLE `credit_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `bureau` varchar(50) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `analyzed_by` int(11) DEFAULT NULL,
  `issues_found` text DEFAULT NULL,
  `analyst_notes` text DEFAULT NULL,
  `status` enum('pending','analyzed') DEFAULT 'pending',
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `analyzed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `bureau` (`bureau`),
  CONSTRAINT `credit_reports_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `credit_score_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_score_history`;
CREATE TABLE `credit_score_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_email` varchar(100) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `recorded_date` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `credit_score_history` VALUES 
('1','client@example.com','635','2025-12-02',NULL),
('2','client@example.com','650','2026-01-02',NULL),
('3','client@example.com','668','2026-02-02',NULL),
('4','client@example.com','679','2026-03-02',NULL),
('5','client@example.com','685','2026-04-02',NULL),
('6','client@example.com','695','2026-05-02',NULL),
('7','client@example.com','635','2025-12-02',NULL),
('8','client@example.com','650','2026-01-02',NULL),
('9','client@example.com','668','2026-02-02',NULL),
('10','client@example.com','679','2026-03-02',NULL),
('11','client@example.com','685','2026-04-02',NULL),
('12','client@example.com','695','2026-05-02',NULL);

-- --------------------------------------------------------
-- Table: `credit_scores`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `credit_scores`;
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

-- --------------------------------------------------------
-- Table: `critical_alerts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `critical_alerts`;
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

-- --------------------------------------------------------
-- Table: `customer_feedback`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `customer_feedback`;
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

-- --------------------------------------------------------
-- Table: `customer_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `customer_requests`;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_requests` VALUES 
('1','Suresh Singh','suresh@example.com','9876543212','Written Off','2025-03-15','pending','medium','general',NULL,NULL,'website',NULL,NULL,'2026-07-19 07:51:56'),
('2','Kavita Gupta','kavita@example.com','9876543221','Profile Correction','2025-04-21','pending','medium','general',NULL,NULL,'website',NULL,NULL,'2026-07-19 07:51:56'),
('3','Test Request 1785017894466','request1785017894466@test.com','9876543653','CIBIL Repair','2025-07-26','pending','high','support','Customer needs urgent CIBIL repair assistance','0','website','2025-08-02','0','2026-07-25 22:18:14');

-- --------------------------------------------------------
-- Table: `customers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `customers`;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` VALUES 
('1','Rajesh Kumar','rajesh@example.com','9876543210',NULL,'Delhi',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-01-15','2026-07-19 07:51:56','Written Off'),
('2','Priya Sharma','priya@example.com','9876543211',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-02-10','2026-07-19 07:51:56','Settled'),
('3','Suresh Singh','suresh@example.com','9876543212',NULL,'Chennai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'inactive',NULL,'2025-01-20','2026-07-19 07:51:56','Profile Correction'),
('4','Meena Patel','meena@example.com','9876543213',NULL,'Pune',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-03-05','2026-07-19 07:51:56','Written Off'),
('5','Arun Verma','arun@example.com','9876543220',NULL,'Jaipur',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-04-12','2026-07-19 07:51:56','Settled'),
('6','Kavita Gupta','kavita@example.com','9876543221',NULL,'Hyderabad',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2025-04-20','2026-07-19 07:51:56','Written Off'),
('7','Deepika Rao','deepika@email.com','9876541005',NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,'2026-07-19','2026-07-19 07:54:26','CIBIL Repair'),
('9','Simple Customer 1785013101654','simple1785013101654@customer.com','9876543253',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-25','2026-07-25 20:58:21','Written Off'),
('12','Test Customer 1785022455090','test1785022455090@customer.com','9876543358',NULL,'Mumbai',NULL,NULL,NULL,'India',NULL,NULL,NULL,NULL,NULL,NULL,'active','','2026-07-25','2026-07-25 23:34:15','Written Off');

-- --------------------------------------------------------
-- Table: `daily_operational_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `daily_operational_reports`;
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

-- --------------------------------------------------------
-- Table: `daily_operations_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `daily_operations_reports`;
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

-- --------------------------------------------------------
-- Table: `database_cleanup_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `database_cleanup_log`;
CREATE TABLE `database_cleanup_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(255) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `affected_rows` int(11) DEFAULT NULL,
  `executed_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `database_cleanup_log` VALUES 
('1','Archived empty table','performance_reviews',NULL,'2026-06-02 20:54:13'),
('2','Archived empty table','unified_reviews',NULL,'2026-06-02 20:54:13'),
('3','Archived empty table','franchise_commission',NULL,'2026-06-02 20:54:13'),
('4','Archived empty table','loan_commission',NULL,'2026-06-02 20:54:13'),
('5','Archived empty table','partner_commission',NULL,'2026-06-02 20:54:13'),
('6','Archived empty table','partner_commissions',NULL,'2026-06-02 20:54:13'),
('7','Archived empty table','transactions',NULL,'2026-06-02 20:55:24'),
('8','Archived empty table','payment_transactions',NULL,'2026-06-02 20:55:24'),
('9','Archived empty table','loyalty_transactions',NULL,'2026-06-02 20:55:24'),
('10','Archived empty table','gst_invoices','0','2026-06-02 21:12:08'),
('11','Archived empty table','gst_returns','0','2026-06-02 21:12:08'),
('12','Archived empty table','interviews','0','2026-06-02 21:12:08'),
('13','Archived empty table','invoices','0','2026-06-02 21:12:08'),
('14','Archived empty table','job_applications','0','2026-06-02 21:12:08'),
('15','Archived empty table','job_openings','0','2026-06-02 21:12:08'),
('16','Archived empty table','journal_entry_lines','0','2026-06-02 21:12:08'),
('17','Archived empty table','kyc_records','0','2026-06-02 21:12:08'),
('18','Archived empty table','lead_documents','0','2026-06-02 21:12:08'),
('19','Archived empty table','lead_followups','0','2026-06-02 21:12:08'),
('20','Archived empty table','lead_scores','0','2026-06-02 21:12:08'),
('21','Archived empty table','lead_score_history','0','2026-06-02 21:12:08'),
('22','Archived empty table','lead_scoring_history','0','2026-06-02 21:12:08'),
('23','Archived empty table','loans','0','2026-06-02 21:12:08'),
('24','Archived empty table','loan_applications','0','2026-06-02 21:12:08'),
('25','Archived empty table','login_history','0','2026-06-02 21:12:08'),
('26','Archived empty table','loyalty_points','0','2026-06-02 21:12:08'),
('27','Archived empty table','magic_links','0','2026-06-02 21:12:08'),
('28','Archived empty table','mobile_tokens','0','2026-06-02 21:12:08'),
('29','Archived empty table','ombudsman_cases','0','2026-06-02 21:12:08'),
('30','Archived empty table','operation_cases','0','2026-06-02 21:12:08'),
('31','Archived empty table','operation_tasks','0','2026-06-02 21:12:08'),
('32','Archived empty table','otp_verification','0','2026-06-02 21:12:08'),
('33','Archived empty table','partner_applications','0','2026-06-02 21:12:08'),
('34','Archived empty table','partner_connectors','0','2026-06-02 21:12:08');

-- --------------------------------------------------------
-- Table: `deleted_users_archive`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `deleted_users_archive`;
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

-- --------------------------------------------------------
-- Table: `departments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `departments`;
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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` VALUES 
('1','HR','Human Resources','Managing employees, recruitment, payroll',NULL,NULL,'active','2026-06-02 13:02:37'),
('2','IT','Information Technology','Software development, IT infrastructure',NULL,NULL,'active','2026-06-02 13:02:37'),
('3','FIN','Finance & Accounts','Accounting, payments, financial reporting',NULL,NULL,'active','2026-06-02 13:02:37'),
('4','SALES','Sales & Marketing','Lead generation, client acquisition',NULL,NULL,'active','2026-06-02 13:02:37'),
('5','OPS','Operations','Daily operations, case management',NULL,NULL,'active','2026-06-02 13:02:37'),
('6','CS','Customer Support','Client support, ticket resolution',NULL,NULL,'active','2026-06-02 13:02:37'),
('13','DEPT-MGMT','Management','Executive Management',NULL,NULL,'active','2026-06-03 19:02:37'),
('14','DEPT-SALES','Sales','Sales and Business Development',NULL,NULL,'active','2026-06-03 19:02:37'),
('15','DEPT-FIN','Finance','Finance and Accounting',NULL,NULL,'active','2026-06-03 19:02:37'),
('16','DEPT-HR','HR','Human Resources',NULL,NULL,'active','2026-06-03 19:02:37'),
('17','DEPT-TECH','Technology','IT and Technology',NULL,NULL,'active','2026-06-03 19:02:37'),
('18','DEPT-OPS','Operations','Operations Management',NULL,NULL,'active','2026-06-03 19:02:37'),
('19','DEPT-LEGAL','Legal','Legal and Compliance',NULL,NULL,'active','2026-06-03 19:02:37'),
('20','DEPT-SUPPORT','Support','Customer Support',NULL,NULL,'active','2026-06-03 19:02:37'),
('21','DEPT-MKT','Marketing','Marketing and Communications',NULL,NULL,'active','2026-06-03 19:02:37'),
('37',NULL,'Administration',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('38',NULL,'Credit Analysis',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('39',NULL,'Dispute Resolution',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('40',NULL,'Sales',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('41',NULL,'Support',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('42',NULL,'HR',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('43',NULL,'Finance',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('44',NULL,'IT',NULL,NULL,NULL,'active','2026-06-04 16:38:38'),
('45','ADMIN','Administration',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
('46','CA','Credit Analysis',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
('47','DR','Dispute Resolution',NULL,NULL,NULL,'active','2026-06-04 16:46:02'),
('48','SUPPORT','Support',NULL,NULL,NULL,'active','2026-06-04 16:46:02');

-- --------------------------------------------------------
-- Table: `designations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `designations`;
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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `designations` VALUES 
('1','CEO','Chief Executive Officer','1','1',NULL,'active'),
('2','CTO','Chief Technology Officer','2','2',NULL,'active'),
('3','CFO','Chief Financial Officer','3','2',NULL,'active'),
('4','HRM','HR Manager','1','3',NULL,'active'),
('5','TL','Team Lead','2','4',NULL,'active'),
('6','DEV','Software Developer','2','5',NULL,'active'),
('7','ACC','Accountant','3','4',NULL,'active'),
('8','SREP','Sales Representative','4','4',NULL,'active'),
('9','CSE','Customer Support Executive','6','4',NULL,'active'),
('10','DESIG-MGMT','General Manager','1','0',NULL,'active'),
('11','DESIG-MGR','Manager',NULL,'0',NULL,'active'),
('12','DESIG-SR-EXEC','Senior Executive',NULL,'0',NULL,'active'),
('13','DESIG-EXEC','Executive',NULL,'0',NULL,'active'),
('14','DESIG-TL','Team Lead',NULL,'0',NULL,'active'),
('15',NULL,'CEO',NULL,NULL,NULL,'active'),
('16',NULL,'Manager',NULL,NULL,NULL,'active'),
('17',NULL,'Team Lead',NULL,NULL,NULL,'active'),
('18',NULL,'Senior Executive',NULL,NULL,NULL,'active'),
('19',NULL,'Executive',NULL,NULL,NULL,'active'),
('20',NULL,'Trainee',NULL,NULL,NULL,'active'),
('21',NULL,'Intern',NULL,NULL,NULL,'active'),
('22',NULL,'CEO',NULL,NULL,NULL,'active'),
('23',NULL,'Manager',NULL,NULL,NULL,'active'),
('24',NULL,'Team Lead',NULL,NULL,NULL,'active'),
('25',NULL,'Senior Executive',NULL,NULL,NULL,'active'),
('26',NULL,'Executive',NULL,NULL,NULL,'active'),
('27',NULL,'Trainee',NULL,NULL,NULL,'active'),
('28',NULL,'Intern',NULL,NULL,NULL,'active'),
('29',NULL,'CEO',NULL,NULL,NULL,'active'),
('30',NULL,'Manager',NULL,NULL,NULL,'active'),
('31',NULL,'Team Lead',NULL,NULL,NULL,'active'),
('32',NULL,'Senior Executive',NULL,NULL,NULL,'active'),
('33',NULL,'Executive',NULL,NULL,NULL,'active'),
('34',NULL,'Trainee',NULL,NULL,NULL,'active'),
('35',NULL,'Intern',NULL,NULL,NULL,'active');

-- --------------------------------------------------------
-- Table: `dispute_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dispute_documents`;
CREATE TABLE `dispute_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dispute_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) DEFAULT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `dispute_id` (`dispute_id`),
  CONSTRAINT `dispute_documents_ibfk_1` FOREIGN KEY (`dispute_id`) REFERENCES `disputes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `disputes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `disputes`;
CREATE TABLE `disputes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `dispute_id` varchar(50) DEFAULT NULL,
  `bank_name` varchar(100) NOT NULL,
  `issue_type` varchar(100) NOT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in_progress','resolved','rejected','closed') DEFAULT 'pending',
  `filed_date` date DEFAULT NULL,
  `expected_resolution` date DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `case_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `dispute_no` varchar(50) DEFAULT NULL,
  `submitted_date` date DEFAULT NULL,
  `resolution_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dispute_id` (`dispute_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dispute_id` (`dispute_id`),
  KEY `idx_disputes_client` (`client_id`),
  KEY `idx_disputes_client_status` (`client_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `disputes` VALUES 
('1','72','DSP20240001','HDFC Bank','Wrong Late Payment',NULL,NULL,'Late payment reported incorrectly for December 2023. I have proof of on-time payment.','in_progress','2024-01-20','2024-03-20',NULL,NULL,NULL,'2026-06-02 12:46:33','2026-06-02 12:46:33',NULL,NULL,NULL),
('2','73','DSP20240002','ICICI Bank','Written Off Entry',NULL,NULL,'Account shown as written off but was settled in full on 15th Jan 2024.','pending','2024-02-01','2024-03-15',NULL,NULL,NULL,'2026-06-02 12:46:33','2026-06-02 12:46:33',NULL,NULL,NULL),
('3','75','DSP20240003','SBI Card','Duplicate Account',NULL,NULL,'Same credit card account shown twice in CIBIL report. Account number ending with 1234.','in_progress','2024-01-10','2024-02-28',NULL,NULL,NULL,'2026-06-02 12:46:33','2026-06-02 12:46:33',NULL,NULL,NULL);

-- --------------------------------------------------------
-- Table: `dm_audit_log`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_audit_log`;
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

-- --------------------------------------------------------
-- Table: `dm_document_permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_document_permissions`;
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

-- --------------------------------------------------------
-- Table: `dm_document_tags`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_document_tags`;
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

-- --------------------------------------------------------
-- Table: `dm_document_versions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_document_versions`;
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

-- --------------------------------------------------------
-- Table: `dm_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_documents`;
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

-- --------------------------------------------------------
-- Table: `dm_esignatures`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_esignatures`;
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

-- --------------------------------------------------------
-- Table: `dm_folders`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_folders`;
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

INSERT INTO `dm_folders` VALUES 
('1','Client Documents',NULL,'/Client Documents','#3b82f6','private','1','2026-06-04 18:35:18','2026-06-04 18:35:18'),
('2','Legal Agreements',NULL,'/Legal Agreements','#3b82f6','restricted','1','2026-06-04 18:35:18','2026-06-04 18:35:18'),
('3','KYC Documents',NULL,'/KYC Documents','#3b82f6','restricted','1','2026-06-04 18:35:18','2026-06-04 18:35:18'),
('4','Credit Reports',NULL,'/Credit Reports','#3b82f6','private','1','2026-06-04 18:35:18','2026-06-04 18:35:18'),
('5','Dispute Letters',NULL,'/Dispute Letters','#3b82f6','private','1','2026-06-04 18:35:18','2026-06-04 18:35:18'),
('6','Internal Policies',NULL,'/Internal Policies','#3b82f6','private','1','2026-06-04 18:35:18','2026-06-04 18:35:18');

-- --------------------------------------------------------
-- Table: `dm_tags`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_tags`;
CREATE TABLE `dm_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL,
  `tag_color` varchar(7) DEFAULT '#6b7280',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dm_tags` VALUES 
('1','Confidential','#ef4444','2026-06-04 18:35:18'),
('2','Urgent','#f59e0b','2026-06-04 18:35:18'),
('3','Archived','#6b7280','2026-06-04 18:35:18'),
('4','Pending Review','#3b82f6','2026-06-04 18:35:18'),
('5','Signed','#10b981','2026-06-04 18:35:18');

-- --------------------------------------------------------
-- Table: `dm_workflow_instances`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_workflow_instances`;
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

-- --------------------------------------------------------
-- Table: `dm_workflows`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `dm_workflows`;
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

INSERT INTO `dm_workflows` VALUES 
('1','Document Approval','approval','[{\"step\":1,\"role\":\"manager\",\"action\":\"review\"},{\"step\":2,\"role\":\"admin\",\"action\":\"approve\"}]','1','1','2026-06-04 18:35:18'),
('2','Client Signature','signature','[{\"step\":1,\"role\":\"client\",\"action\":\"sign\"},{\"step\":2,\"role\":\"manager\",\"action\":\"verify\"}]','1','1','2026-06-04 18:35:18'),
('3','KYC Verification','verification','[{\"step\":1,\"role\":\"compliance\",\"action\":\"verify\"},{\"step\":2,\"role\":\"admin\",\"action\":\"approve\"}]','1','1','2026-06-04 18:35:18');

-- --------------------------------------------------------
-- Table: `email_campaigns`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_campaigns`;
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

-- --------------------------------------------------------
-- Table: `email_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_logs`;
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

-- --------------------------------------------------------
-- Table: `email_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `email_templates`;
CREATE TABLE `email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_templates` VALUES 
('1','welcome','Welcome to CIBIL Repair','<h2>Welcome {{name}}!</h2><p>Thank you for joining CIBIL Repair. Your account has been created successfully.</p><p><strong>Email:</strong> {{email}}</p><p><strong>Role:</strong> {{role}}</p><a href=\"{{login_link}}\">Click here to login</a>','2026-05-04 09:27:02'),
('2','lead_assigned','New Lead Assigned','<h2>New Lead Assigned</h2><p>A new lead has been assigned to you.</p><p><strong>Customer:</strong> {{customer_name}}</p><p><strong>Service:</strong> {{service}}</p><a href=\"{{dashboard_link}}\">View Lead</a>','2026-05-04 09:27:02'),
('3','lead_converted','Lead Converted - Commission Earned','<h2>Congratulations! Lead Converted</h2><p>Your lead {{customer_name}} has been converted.</p><p><strong>Commission Earned:</strong> ₹{{commission}}</p><a href=\"{{dashboard_link}}\">View Details</a>','2026-05-04 09:27:02'),
('4','payout_processed','Payout Processed','<h2>Payout Processed</h2><p>Your payout request of ₹{{amount}} has been processed.</p><p>Amount will be credited to your bank account within 2-3 business days.</p>','2026-05-04 09:27:02'),
('5','ticket_reply','New Reply on Support Ticket','<h2>New Reply on Ticket #{{ticket_no}}</h2><p>{{reply_message}}</p><a href=\"{{ticket_link}}\">View Ticket</a>','2026-05-04 09:27:02');

-- --------------------------------------------------------
-- Table: `employee_attendance`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_attendance`;
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

-- --------------------------------------------------------
-- Table: `employee_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_documents`;
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

-- --------------------------------------------------------
-- Table: `employee_incentives`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_incentives`;
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

INSERT INTO `employee_incentives` VALUES 
('1','5','Performance Bonus','15000.00','2025-06','paid','2026-06-05','2026-06-05 16:00:07'),
('2','6','Referral Bonus','5000.00','2025-06','pending',NULL,'2026-06-05 16:00:07'),
('3','7','Quarterly Bonus','25000.00','2025-06','paid','2026-06-05','2026-06-05 16:00:07');

-- --------------------------------------------------------
-- Table: `employee_leave_balances`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_leave_balances`;
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

-- --------------------------------------------------------
-- Table: `employee_leave_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_leave_requests`;
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

-- --------------------------------------------------------
-- Table: `employee_leaves`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_leaves`;
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

-- --------------------------------------------------------
-- Table: `employee_payroll`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_payroll`;
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

-- --------------------------------------------------------
-- Table: `employee_targets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employee_targets`;
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

-- --------------------------------------------------------
-- Table: `employees`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employees`;
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `employees` VALUES 
('1',NULL,'EMP001','John','Doe','male','1990-05-15','married','Indian',NULL,NULL,NULL,NULL,'john.personal@gmail.com','john.doe@cibilrepair.in','9876543215',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1','5',NULL,'permanent','2026-06-02',NULL,'6',NULL,NULL,'50000.00','25000.00','10000.00',NULL,'950000.00','HDFC Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:04:25','2026-06-02 13:04:25'),
('2',NULL,'EMP002','Priya','Sharma','female','1988-08-20','married','Indian',NULL,NULL,NULL,NULL,'priya.personal@gmail.com','priya.hr@cibilrepair.in','9988776655',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1','3',NULL,'permanent','2026-06-02',NULL,'6',NULL,NULL,'75000.00','37500.00','15000.00',NULL,'1450000.00','ICICI Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:04:41','2026-06-02 13:04:41'),
('3',NULL,'EMP003','Rahul','Gupta','male','1992-03-10','single','Indian',NULL,NULL,NULL,NULL,'rahul.personal@gmail.com','rahul.accounts@cibilrepair.in','8877665544',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'3','7',NULL,'permanent','2026-06-02',NULL,'6',NULL,NULL,'45000.00','22500.00','9000.00',NULL,'850000.00','SBI Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:05:03','2026-06-02 13:05:03'),
('4',NULL,'EMP004','Sneha','Reddy','female','1995-07-25','single','Indian',NULL,NULL,NULL,NULL,'sneha.personal@gmail.com','sneha.support@cibilrepair.in','7766554433',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'6','9',NULL,'permanent','2026-06-02',NULL,'6',NULL,NULL,'35000.00','17500.00','7000.00',NULL,'650000.00','Axis Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:10:21','2026-06-02 13:10:21'),
('5',NULL,'EMP005','Amit','Verma','male','1993-11-12','single','Indian',NULL,NULL,NULL,NULL,'amit.personal@gmail.com','amit.sales@cibilrepair.in','6655443322',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'4','8',NULL,'permanent','2026-06-02',NULL,'6',NULL,NULL,'40000.00','20000.00','8000.00',NULL,'750000.00','HDFC Bank',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-02 13:10:32','2026-06-02 13:10:32'),
('6',NULL,'CR-001','Arjun','Sharma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'arjun@cibilrepair.in','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'1','1',NULL,'permanent','2022-03-15',NULL,'6',NULL,NULL,'45000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('7',NULL,'CR-002','Priya','Mehta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'priya@cibilrepair.in','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'2','2',NULL,'permanent','2020-01-10',NULL,'6',NULL,NULL,'95000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('8',NULL,'CR-003','Rahul','Verma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rahul@cibilrepair.in','9876543212',NULL,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi',NULL,'3','3',NULL,'permanent','2023-06-01',NULL,'6',NULL,NULL,'38000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('9',NULL,'CR-004','Sunita','Patel','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'sunita@cibilrepair.in','9876543213',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'4','4',NULL,'permanent','2021-09-15',NULL,'6',NULL,NULL,'52000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('10',NULL,'CR-005','Amit','Kumar','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'amit@cibilrepair.in','9876543214',NULL,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka',NULL,'5','5',NULL,'permanent','2022-11-01',NULL,'6',NULL,NULL,'72000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('11',NULL,'CR-006','Neha','Singh','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'neha@cibilrepair.in','9876543215',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'6','6',NULL,'permanent','2023-02-14',NULL,'6',NULL,NULL,'30000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('12',NULL,'CR-007','Vikram','Nair','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'vikram@cibilrepair.in','9876543216',NULL,NULL,NULL,NULL,NULL,NULL,'Chennai','Tamil Nadu',NULL,'7','7',NULL,'contract','2023-08-01',NULL,'6',NULL,NULL,'55000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('13',NULL,'CR-008','Pooja','Gupta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'pooja@cibilrepair.in','9876543217',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'1','8',NULL,'permanent','2024-01-15',NULL,'6',NULL,NULL,'25000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('14',NULL,'CR-009','Rajesh','Iyer','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rajesh@cibilrepair.in','9876543218',NULL,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana',NULL,'8','9',NULL,'permanent','2022-05-20',NULL,'6',NULL,NULL,'35000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('15',NULL,'CR-010','Ankita','Joshi','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'ankita@cibilrepair.in','9876543219',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'9','10',NULL,'permanent','2023-10-01',NULL,'6',NULL,NULL,'28000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:00:40','2026-06-03 19:00:40'),
('16',NULL,'EMP-001','Admin','User','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'admin@cibilrepair.in','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,'1',NULL,'permanent','2020-01-01',NULL,'6',NULL,NULL,'100000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:03:03'),
('17',NULL,'EMP-002','Priya','Mehta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'priya@cibilrepair.in','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,NULL,NULL,'permanent','2020-01-10',NULL,'6',NULL,NULL,'95000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
('18',NULL,'EMP-003','Rahul','Verma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rahul@cibilrepair.in','9876543212',NULL,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi',NULL,NULL,NULL,NULL,'permanent','2023-06-01',NULL,'6',NULL,NULL,'38000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
('19',NULL,'EMP-004','Sunita','Patel','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'sunita@cibilrepair.in','9876543213',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,NULL,NULL,'permanent','2021-09-15',NULL,'6',NULL,NULL,'52000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
('20',NULL,'EMP-005','Arjun','Sharma','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'arjun@cibilrepair.in','9876543214',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,NULL,NULL,NULL,'permanent','2022-03-15',NULL,'6',NULL,NULL,'45000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
('21',NULL,'EMP-006','Amit','Kumar','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'amit@cibilrepair.in','9876543215',NULL,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka',NULL,NULL,NULL,NULL,'permanent','2022-11-01',NULL,'6',NULL,NULL,'72000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:01:55','2026-06-03 19:01:55'),
('22',NULL,'EMP-007','Neha','Singh','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'neha@cibilrepair.in','9876543216',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'6',NULL,NULL,'permanent','2023-02-14',NULL,'6',NULL,NULL,'30000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:03:03','2026-06-03 19:03:03'),
('23',NULL,'EMP-008','Vikram','Nair','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'vikram@cibilrepair.in','9876543217',NULL,NULL,NULL,NULL,NULL,NULL,'Chennai','Tamil Nadu',NULL,'3',NULL,NULL,'contract','2023-08-01',NULL,'6',NULL,NULL,'55000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:03:03','2026-06-03 19:03:03'),
('24',NULL,'EMP-009','Pooja','Gupta','female',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'pooja@cibilrepair.in','9876543218',NULL,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra',NULL,'2',NULL,NULL,'permanent','2024-01-15',NULL,'6',NULL,NULL,'25000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','2026-06-03 19:03:03','2026-06-03 19:03:03'),
('25',NULL,'EMP-010','Rajesh','Iyer','male',NULL,NULL,'Indian',NULL,NULL,NULL,NULL,NULL,'rajesh@cibilrepair.in','9876543219',NULL,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana',NULL,'6',NULL,NULL,'permanent','2022-05-20',NULL,'6',NULL,NULL,'35000.00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-06-03 19:03:03','2026-06-03 19:03:03');

-- --------------------------------------------------------
-- Table: `executive_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `executive_reports`;
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

-- --------------------------------------------------------
-- Table: `expense_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `expense_categories`;
CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `budget_amount` decimal(15,2) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `expense_categories` VALUES 
('1','Salary','Employee salaries',NULL,'9'),
('2','Rent','Office rent',NULL,'10'),
('3','Electricity','Electricity bills',NULL,'11'),
('4','Internet','Internet and broadband',NULL,'11'),
('5','Marketing','Marketing expenses',NULL,'9'),
('6','Software','Software subscriptions',NULL,'9');

-- --------------------------------------------------------
-- Table: `expenses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `expenses`;
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

INSERT INTO `expenses` VALUES 
('1','Rent','Office rent April','25000.00','2025-04-01','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending','18.00','0.00','0.00','0.00','0.00','0','2026-07-19 07:51:56'),
('2','Salary','Staff salary April','180000.00','2025-04-05','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending','18.00','0.00','0.00','0.00','0.00','0','2026-07-19 07:51:56'),
('3','Marketing','Google Ads campaign','15000.00','2025-04-10','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending','18.00','0.00','0.00','0.00','0.00','0','2026-07-19 07:51:56'),
('4','Utilities','Electricity + Internet','8000.00','2025-04-12','operational','cash',NULL,NULL,NULL,NULL,NULL,NULL,'pending','18.00','0.00','0.00','0.00','0.00','0','2026-07-19 07:51:56'),
('5','Marketing','Google Ads Campaign','15000.00','2025-07-26','marketing','credit_card','Google Ads','0','0',NULL,'Monthly Google Ads spend',NULL,'paid','18.00','0.00','0.00','0.00','0.00','0','2026-07-25 21:09:01'),
('6','Marketing','Google Ads Campaign','15000.00','2025-07-26','marketing','credit_card','Google Ads','0','0',NULL,'',NULL,'paid','18.00','0.00','0.00','0.00','0.00','0','2026-07-25 23:34:16');

-- --------------------------------------------------------
-- Table: `faqs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `faqs`;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `faqs` VALUES 
('1','What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:07:44'),
('2','How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:07:44'),
('3','Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:07:44'),
('4','What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:07:44'),
('5','What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:09:04'),
('6','How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:09:04'),
('7','Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:09:04'),
('8','What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:09:04'),
('9','What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:10:42'),
('10','How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:10:42'),
('11','Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:10:42'),
('12','What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:10:42'),
('13','What is a good CIBIL score for a home loan?','A score of 750 or above is considered excellent and gives you the best home loan rates.','Score','2026-06-02 18:12:26'),
('14','How long does credit repair take?','Simple cases take 15-30 days. Written-off and settled accounts take 45-60 days.','Process','2026-06-02 18:12:26'),
('15','Is credit repair legal in India?','Yes, our entire process is 100% legal and RBI-compliant.','Legal','2026-06-02 18:12:26'),
('16','What is your money-back guarantee?','If we cannot resolve the specific issues we agreed to fix, we refund your payment in full.','Pricing','2026-06-02 18:12:26');

-- --------------------------------------------------------
-- Table: `followup_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `followup_templates`;
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

INSERT INTO `followup_templates` VALUES 
('1','lead_new','New Lead - Follow-up Reminder','🔔 *Follow-up Reminder*\n\nLead: {{customer_name}}\nService: {{service}}\n\nPlease contact this lead today to discuss requirements.\n\nLead ID: #{{lead_id}}\n\n_CIBIL Repair_','all','high','1','1','2026-05-09 08:21:55'),
('2','lead_contacted','Contacted Lead - Follow-up','📞 *Follow-up Reminder*\n\nCustomer: {{customer_name}}\nYou had previously contacted this lead on {{last_contact_date}}.\n\nPlease follow up to check their decision.\n\nLead ID: #{{lead_id}}','all','medium','2','1','2026-05-09 08:21:55'),
('3','lead_conversion','High Potential Lead - Urgent Follow-up','🔥 *URGENT: High Potential Lead*\n\nCustomer: {{customer_name}}\nService: {{service}}\n\nThis lead has high conversion potential. Please follow up today!\n\nLead ID: #{{lead_id}}','all','urgent','1','1','2026-05-09 08:21:55'),
('4','lead_inactive','Inactive Lead - Re-engagement','📌 *Re-engagement Reminder*\n\nLead: {{customer_name}}\nLast contact: {{last_contact_date}}\n\nNo activity for {{inactive_days}} days. Try to re-engage this lead.\n\nLead ID: #{{lead_id}}','all','low','5','1','2026-05-09 08:21:55'),
('5','lead_won','Won Lead - No Follow-up Needed','✅ *Lead Converted*\n\nCustomer: {{customer_name}}\nThis lead has been converted. No further follow-up needed.\n\nCommission: ₹{{commission_amount}}','all','low','0','1','2026-05-09 08:21:55');

-- --------------------------------------------------------
-- Table: `followups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `followups`;
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

INSERT INTO `followups` VALUES 
('1','10','25','2026-06-29 03:16:00','','pending','2026-06-28 15:42:58',NULL),
('2','10','25','2026-06-29 21:13:00','','pending','2026-06-28 15:43:39',NULL),
('3','10','24','2026-06-29 21:14:00','','pending','2026-06-28 15:44:06',NULL),
('4','10','23','2026-06-29 21:14:00','','pending','2026-06-28 15:44:33',NULL);

-- --------------------------------------------------------
-- Table: `franchise_leads`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `franchise_leads`;
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

INSERT INTO `franchise_leads` VALUES 
('1','1','Rajesh Kumar','9876543210',NULL,'Written Off Clearance',NULL,'converted','1500.00','2026-05-02 20:18:54','2026-05-02 20:18:54'),
('2','1','Priya Sharma','9876543211',NULL,'Settled Clearance',NULL,'contacted','0.00','2026-05-02 20:18:54','2026-05-02 20:18:54'),
('3','1','Amit Verma','9876543212',NULL,'Profile Correction',NULL,'new','0.00','2026-05-02 20:18:54','2026-05-02 20:18:54'),
('4','1','Rajesh Kumar','9876543210',NULL,'Written Off Clearance',NULL,'converted','1500.00','2026-05-02 20:19:39','2026-05-02 20:19:39'),
('5','1','Priya Sharma','9876543211',NULL,'Settled Clearance',NULL,'contacted','0.00','2026-05-02 20:19:39','2026-05-02 20:19:39'),
('6','1','Amit Verma','9876543212',NULL,'Profile Correction',NULL,'new','0.00','2026-05-02 20:19:39','2026-05-02 20:19:39');

-- --------------------------------------------------------
-- Table: `franchise_partners`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `franchise_partners`;
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

INSERT INTO `franchise_partners` VALUES 
('1',NULL,'Demo Franchise','franchise@cibilrepair.in','c280f51fe732b536823c7cda276b6454','9876543210','Delhi Franchise',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 20:07:31'),
('4',NULL,'ABC Franchise','abc@franchise.com','e99a18c428cb38d5f260853678922e03','9876543211','Mumbai Franchise',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 20:34:46'),
('5',NULL,'XYZ Partner','xyz@partner.com','613d3b9c91e9445abaeca02f2342e5a6','9876543212','Bangalore Franchise',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 20:34:46'),
('6',NULL,'Rahul Franchise','rahul@franchise.com','2acb7811397a5c3bea8cba57b0388b79','9988776655','Bangalore Franchise',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 20:35:06'),
('7',NULL,'Priya Partner','priya@partner.com','48467d2cc726e8847fbc51f5b0bdc1d1','9988776644','Chennai Franchise',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 20:35:06'),
('8',NULL,'Amit Franchise','amit@franchise.com','d2b3f63948406cb893544cee035531d3','9988776633','Kolkata Franchise',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 20:35:06'),
('9',NULL,'Rajesh Kumar','rajesh@franchise.com','bf44e33d9745e04551770c7a5a6cdb3b','9876543210','Delhi Main Franchise','Connaught Place, New Delhi','HDFC Bank','12345678901234','HDFC0001234','Rajesh Kumar','0','0','0.00','0.00','active','2026-05-02 20:37:07'),
('15',NULL,'Rajesh Kumar','rajesh.new@franchise.com','bf44e33d9745e04551770c7a5a6cdb3b','9876543210','Delhi Main Franchise','Connaught Place, New Delhi','HDFC Bank','12345678901234','HDFC0001234','Rajesh Kumar','0','0','0.00','0.00','active','2026-05-02 20:39:42'),
('16',NULL,'Partner User','partner@cibilrepair.in','3c0d9364bee6c8e4e71a2aecdc6cf57f','9876543210','Main Store',NULL,NULL,NULL,NULL,NULL,'0','0','0.00','0.00','active','2026-05-02 21:01:03');

-- --------------------------------------------------------
-- Table: `franchises`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `franchises`;
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

-- --------------------------------------------------------
-- Table: `grievances`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `grievances`;
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

-- --------------------------------------------------------
-- Table: `gst_returns`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `gst_returns`;
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

-- --------------------------------------------------------
-- Table: `holidays`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `holidays`;
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

INSERT INTO `holidays` VALUES 
('1','2024-01-26','Republic Day','national',NULL,'2024'),
('2','2024-08-15','Independence Day','national',NULL,'2024'),
('3','2024-10-02','Gandhi Jayanti','national',NULL,'2024'),
('4','2024-12-25','Christmas','public',NULL,'2024'),
('5','2024-10-12','Dussehra','festival',NULL,'2024'),
('6','2024-11-14','Diwali','festival',NULL,'2024');

-- --------------------------------------------------------
-- Table: `hr_tickets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `hr_tickets`;
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

-- --------------------------------------------------------
-- Table: `invoices`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `invoices`;
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

INSERT INTO `invoices` VALUES 
('1','INV-001','1','Premium Package','25000.00','4500.00','29500.00','paid','2026-06-05',NULL,'2026-06-05 15:57:53'),
('2','INV-002','2','Basic Package','15000.00','2700.00','17700.00','paid','2026-06-05',NULL,'2026-06-05 15:57:53'),
('3','INV-003','1','Corporate Package','50000.00','9000.00','59000.00','pending','2026-06-05',NULL,'2026-06-05 15:57:53');

-- --------------------------------------------------------
-- Table: `it_api_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_api_logs`;
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

INSERT INTO `it_api_logs` VALUES 
('1','/api/auth/login','POST','125','200',NULL,'192.168.1.100','0','0',NULL,'2026-06-04 17:50:50'),
('2','/api/clients/get','GET','89','200',NULL,'192.168.1.101','0','0',NULL,'2026-06-04 17:50:50'),
('3','/api/dashboard/stats','GET','234','200',NULL,'192.168.1.102','0','0',NULL,'2026-06-04 17:50:50'),
('4','/api/reports/export','POST','567','500',NULL,'192.168.1.103','0','0',NULL,'2026-06-04 17:50:50'),
('5','/api/agreements/create','POST','178','201',NULL,'192.168.1.100','0','0',NULL,'2026-06-04 17:50:50');

-- --------------------------------------------------------
-- Table: `it_api_performance_summary`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_api_performance_summary`;
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

-- --------------------------------------------------------
-- Table: `it_backup_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_backup_history`;
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

INSERT INTO `it_backup_history` VALUES 
('1','Full_Backup_Dec_2024','full','2048.50','/backups/full_backup_20241201.sql','success','2026-06-03 17:50:50','2026-06-03 19:50:50',NULL,'system'),
('2','Daily_Backup_Dec_2024','daily','156.25','/backups/daily_backup_20241202.sql','success','2026-06-04 05:50:50','2026-06-04 06:20:50',NULL,'system');

-- --------------------------------------------------------
-- Table: `it_communication_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_communication_logs`;
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

-- --------------------------------------------------------
-- Table: `it_failed_logins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_failed_logins`;
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

-- --------------------------------------------------------
-- Table: `it_slow_queries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_slow_queries`;
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

INSERT INTO `it_slow_queries` VALUES 
('1','SELECT * FROM clients c LEFT JOIN agreements a ON c.id = a.client_id WHERE c.created_at > \"2024-01-01\"','3450.50','0.00','125000','2340',NULL,NULL,'2026-06-04 17:50:50'),
('2','SELECT * FROM audit_logs WHERE created_at BETWEEN \"2024-01-01\" AND \"2024-12-31\"','2890.25','0.00','89000','1200',NULL,NULL,'2026-06-04 17:50:50');

-- --------------------------------------------------------
-- Table: `it_system_alerts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_system_alerts`;
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

INSERT INTO `it_system_alerts` VALUES 
('1','High CPU Usage','cpu','80.00','0.00','high','1',NULL,'0','2026-06-04 17:50:50','2026-06-04 17:50:50'),
('2','High Memory Usage','memory','85.00','0.00','high','1',NULL,'0','2026-06-04 17:50:50','2026-06-04 17:50:50'),
('3','Low Disk Space','disk','90.00','0.00','critical','1',NULL,'0','2026-06-04 17:50:50','2026-06-04 17:50:50'),
('4','Slow API Response','api_response','3000.00','0.00','medium','1',NULL,'0','2026-06-04 17:50:50','2026-06-04 17:50:50'),
('5','Multiple Failed Logins','failed_login','10.00','0.00','high','1',NULL,'0','2026-06-04 17:50:50','2026-06-04 17:50:50'),
('6','Backup Failed','backup','0.00','0.00','critical','1',NULL,'0','2026-06-04 17:50:50','2026-06-04 17:50:50');

-- --------------------------------------------------------
-- Table: `it_system_health`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `it_system_health`;
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

INSERT INTO `it_system_health` VALUES 
('1','CRM-Main-Server','45.50','62.30','58.70','0','0','0','0','0','864000','2026-06-04 17:50:50'),
('2','CRM-DB-Server','32.10','71.20','45.30','0','0','0','0','0','864000','2026-06-04 17:50:50'),
('3','CRM-API-Server','51.20','55.80','32.10','0','0','0','0','0','432000','2026-06-04 17:50:50');

-- --------------------------------------------------------
-- Table: `journal_entries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `journal_entries`;
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

INSERT INTO `journal_entries` VALUES 
('1','JE001','2026-06-02',NULL,'Salary payment','50000.00','50000.00','posted',NULL,NULL,NULL,'2026-06-02 13:03:29');

-- --------------------------------------------------------
-- Table: `knowledge_base`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `knowledge_base`;
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

-- --------------------------------------------------------
-- Table: `kyc_records`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `kyc_records`;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `lawyers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `lawyers`;
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

-- --------------------------------------------------------
-- Table: `lead_followups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `lead_followups`;
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

-- --------------------------------------------------------
-- Table: `lead_sources`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `lead_sources`;
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

INSERT INTO `lead_sources` VALUES 
('1','Google Ads','PPC',NULL,'3.50',NULL,'1','2026-06-04 17:38:27'),
('2','Facebook Ads','Social',NULL,'2.80',NULL,'1','2026-06-04 17:38:27'),
('3','Instagram','Social',NULL,'2.10',NULL,'1','2026-06-04 17:38:27'),
('4','LinkedIn','Social',NULL,'4.20',NULL,'1','2026-06-04 17:38:27'),
('5','Email Marketing','Email',NULL,'5.00',NULL,'1','2026-06-04 17:38:27'),
('6','Referral Program','Referral',NULL,'8.50',NULL,'1','2026-06-04 17:38:27'),
('7','Organic Search','SEO',NULL,'2.00',NULL,'1','2026-06-04 17:38:27'),
('8','Direct Traffic','Direct',NULL,'1.50',NULL,'1','2026-06-04 17:38:27'),
('9','Content Marketing','Content',NULL,'3.00',NULL,'1','2026-06-04 17:38:27'),
('10','WhatsApp','Messaging',NULL,'6.50',NULL,'1','2026-06-04 17:38:27');

-- --------------------------------------------------------
-- Table: `leads`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leads`;
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
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_partner_id` (`partner_id`),
  KEY `idx_leads_partner` (`partner_id`),
  KEY `idx_leads_status_created` (`status`,`created_at`),
  KEY `idx_source` (`source_type`,`source_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leads` VALUES 
('1',NULL,'Ankit Sharma','9876541000','ankit@email.com','CIBIL Repair','CIBIL Score Repair','new','2026-07-19 07:51:56',NULL,NULL,'Need CIBIL repair urgently','0.00',NULL,'0',NULL,NULL,NULL,'Website',NULL,NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00'),
('2',NULL,'Pooja Verma','9876541001','pooja@email.com','Settlement','Loan Settlement','new','2026-07-19 07:51:56',NULL,NULL,'Credit score dropped after settlement','0.00',NULL,'0',NULL,NULL,NULL,'Website',NULL,NULL,NULL,NULL,'medium','direct',NULL,NULL,'0.00','0.00'),
('3',NULL,'Ramesh Nair','9876541002','ramesh@email.com','Settlement','Loan Settlement','contacted','2026-07-19 07:51:56',NULL,NULL,'Need help with loan settlement','0.00',NULL,'0',NULL,NULL,NULL,'Referral',NULL,NULL,NULL,NULL,'medium','referral',NULL,NULL,'0.00','0.00'),
('4',NULL,'Sita Devi','9876541003','sita@email.com','Profile Correction','CIBIL Profile Update','new','2026-07-19 07:51:56',NULL,NULL,'Profile correction needed','0.00',NULL,'0',NULL,NULL,NULL,'Website',NULL,NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00'),
('5',NULL,'Mohit Agarwal','9876541004','mohit@email.com','Written Off','Loan Write-off','new','2026-07-19 07:51:56',NULL,NULL,'Loan write-off help required','0.00',NULL,'0',NULL,NULL,NULL,'Google Ads',NULL,NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00'),
('6',NULL,'Deepika Rao','9876541005','deepika@email.com','CIBIL Repair','CIBIL Score Repair','converted','2026-07-19 07:51:56',NULL,NULL,'CIBIL score is very low after default','0.00',NULL,'0',NULL,NULL,NULL,'Website',NULL,NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00'),
('7',NULL,'Karan Mehta','9876541006','karan@email.com','CIBIL Repair','CIBIL Score Improvement','new','2026-07-19 07:51:56',NULL,NULL,'Want to improve credit score','0.00',NULL,'0',NULL,NULL,NULL,'Referral',NULL,NULL,NULL,NULL,'medium','referral',NULL,NULL,'0.00','0.00'),
('8',NULL,'Sunita Agarwal','9876541007','sunita@email.com','Profile Correction','CIBIL Profile Correction','contacted','2026-07-19 07:51:56',NULL,NULL,'Two accounts showing wrong data','0.00',NULL,'0',NULL,NULL,NULL,'Website',NULL,NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00'),
('9',NULL,'Vikash Pandey','9876541008','vikash@email.com','Dispute','CIBIL Dispute Filing','new','2026-07-19 07:51:56',NULL,NULL,'Need CIBIL dispute filing','0.00',NULL,'0',NULL,NULL,NULL,'Google Ads',NULL,NULL,NULL,NULL,'medium','direct',NULL,NULL,'0.00','0.00'),
('10',NULL,'Nisha Rawat','9876541009','nisha@email.com','Settlement','Loan Settlement','new','2026-07-19 07:51:56',NULL,NULL,'Settlement letter but score not updated','0.00',NULL,'0',NULL,NULL,NULL,'Website',NULL,NULL,NULL,NULL,'medium','direct',NULL,NULL,'0.00','0.00'),
('11','0','Test Lead 1785014297649','9876543912','lead1785014297649@test.com','CIBIL Repair',NULL,'new','2026-07-25 21:18:17',NULL,NULL,'I need help with my CIBIL score','15000.00',NULL,'0',NULL,NULL,NULL,'website','Interested in CIBIL repair services',NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00'),
('12','0','Test Lead 1785015508586','9876543792','lead1785015508586@test.com','CIBIL Repair',NULL,'new','2026-07-25 21:38:28',NULL,NULL,'Test message','0.00',NULL,'0',NULL,NULL,NULL,'website','Added from admin dashboard',NULL,NULL,NULL,'medium','direct',NULL,NULL,'0.00','0.00'),
('13','0','Console Test 1785016149614','9876543177','console1785016149614@test.com','CIBIL Repair',NULL,'new','2026-07-25 21:49:09',NULL,NULL,'Test from console','0.00',NULL,'0',NULL,NULL,NULL,'website','Added from admin dashboard',NULL,NULL,NULL,'medium','direct',NULL,NULL,'0.00','0.00'),
('14','0','Test Lead 1785022455168','9876543415','lead1785022455168@test.com','CIBIL Repair',NULL,'new','2026-07-25 23:34:15',NULL,NULL,'Test lead message','0.00',NULL,'0',NULL,NULL,NULL,'website','',NULL,NULL,NULL,'high','direct',NULL,NULL,'0.00','0.00');

-- --------------------------------------------------------
-- Table: `leave_balance`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leave_balance`;
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

-- --------------------------------------------------------
-- Table: `leave_balances`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leave_balances`;
CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` decimal(5,1) DEFAULT 0.0,
  `used_days` decimal(5,1) DEFAULT 0.0,
  `pending_days` decimal(5,1) DEFAULT 0.0,
  `carried_forward` decimal(5,1) DEFAULT 0.0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_leave_year` (`employee_id`,`leave_type_id`,`year`),
  CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_balances` VALUES 
('1','1','1','2024','12.0','2.0','0.0','0.0','2026-06-02 13:08:58'),
('2','1','2','2024','12.0','1.0','0.0','0.0','2026-06-02 13:08:58'),
('3','1','3','2024','15.0','0.0','0.0','0.0','2026-06-02 13:08:58'),
('4','2','1','2024','12.0','3.0','1.0','0.0','2026-06-02 13:08:58'),
('5','2','2','2024','12.0','0.0','0.0','0.0','2026-06-02 13:08:58'),
('6','2','3','2024','15.0','0.0','0.0','0.0','2026-06-02 13:08:58'),
('7','3','1','2024','12.0','1.0','0.0','0.0','2026-06-02 13:08:58'),
('8','3','2','2024','12.0','0.0','0.0','0.0','2026-06-02 13:08:58'),
('9','3','3','2024','15.0','0.0','0.0','0.0','2026-06-02 13:08:58'),
('10','4','1','2024','12.0','0.0','0.0','0.0','2026-06-02 13:10:50'),
('11','4','2','2024','12.0','0.0','0.0','0.0','2026-06-02 13:10:50'),
('12','4','3','2024','15.0','0.0','0.0','0.0','2026-06-02 13:10:50'),
('13','5','1','2024','12.0','0.0','0.0','0.0','2026-06-02 13:10:50'),
('14','5','2','2024','12.0','0.0','0.0','0.0','2026-06-02 13:10:50'),
('15','5','3','2024','15.0','0.0','0.0','0.0','2026-06-02 13:10:50');

-- --------------------------------------------------------
-- Table: `leave_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `total_days` decimal(5,1) NOT NULL,
  `reason` text DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `leave_type_id` (`leave_type_id`),
  CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_requests` VALUES 
('1','1','1','2026-06-07','2026-06-07','1.0','Doctor appointment',NULL,'approved',NULL,NULL,NULL,'2026-06-02 13:09:08'),
('2','2','1','2026-06-12','2026-06-14','3.0','Family function',NULL,'approved','1','2026-06-02 13:12:35',NULL,'2026-06-02 13:09:08'),
('3','3','2','2026-06-04','2026-06-04','1.0','Not feeling well',NULL,'approved','1','2026-06-02 13:12:35',NULL,'2026-06-02 13:09:08');

-- --------------------------------------------------------
-- Table: `leave_types`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leave_types`;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_types` VALUES 
('1','CL','Casual Leave','12','0','0','1','1','active'),
('2','SL','Sick Leave','10','0','0','1','1','active'),
('3','EL','Earned Leave','15','1','0','1','1','active'),
('4','LOP','Loss of Pay','0','0','0','0','1','active'),
('5','ML','Maternity Leave','180','0','0','1','1','active'),
('6','PL','Paternity Leave','15','0','0','1','1','active');

-- --------------------------------------------------------
-- Table: `legal_agreements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_agreements`;
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

INSERT INTO `legal_agreements` VALUES 
('1','','1','credit_repair_service','Credit Repair Service Agreement',NULL,'Standard terms and conditions apply...','2026-06-04',NULL,'2027-06-04','draft',NULL,NULL,NULL,'1','2026-06-04 17:47:56','2026-06-04 17:47:56');

-- --------------------------------------------------------
-- Table: `legal_audit_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_audit_logs`;
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

INSERT INTO `legal_audit_logs` VALUES 
('1','1','System Admin','admin','system_initialized','create','system',NULL,'Legal compliance module initialized','127.0.0.1','System',NULL,NULL,NULL,NULL,NULL,'2026-06-04 17:47:56');

-- --------------------------------------------------------
-- Table: `legal_compliance_policies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_compliance_policies`;
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

INSERT INTO `legal_compliance_policies` VALUES 
('1','Data Privacy Policy','data_privacy','v2.0','This policy outlines how we collect, use, store, and protect client personal data in compliance with DPDP Act 2023 and RBI guidelines. We collect only necessary data, store with 256-bit encryption, retain for 7 years, and never share without explicit consent.','2024-01-01',NULL,'1','1','2026-06-04 17:47:56','2026-06-04 17:47:56','2026-06-04 17:47:56'),
('2','KYC Verification Policy','kyc_policy','v1.5','All clients must complete KYC verification within 30 days of onboarding. Acceptable documents: PAN Card, Aadhaar Card, Passport, Voter ID. Video KYC available for remote verification.','2024-01-01',NULL,'1','1','2026-06-04 17:47:56','2026-06-04 17:47:56','2026-06-04 17:47:56'),
('3','Complaint Redressal Policy','complaint_redressal','v1.0','Client complaints are acknowledged within 24 hours, resolved within 7 business days. Escalation matrix: Level 1 - Support, Level 2 - Manager, Level 3 - Compliance Officer, Level 4 - RBI Ombudsman.','2024-01-01',NULL,'1','1','2026-06-04 17:47:56','2026-06-04 17:47:56','2026-06-04 17:47:56');

-- --------------------------------------------------------
-- Table: `legal_compliance_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_compliance_reports`;
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

-- --------------------------------------------------------
-- Table: `legal_consent_forms`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_consent_forms`;
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

INSERT INTO `legal_consent_forms` VALUES 
('1','1','data_collection','v1.0','2026-06-04 17:47:56',NULL,'1','electronic_signature',NULL,NULL,NULL,NULL,NULL,'2026-06-04 17:47:56');

-- --------------------------------------------------------
-- Table: `legal_kyc_verification`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_kyc_verification`;
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

INSERT INTO `legal_kyc_verification` VALUES 
('1','1','ABCDE1234F',NULL,'1234-5678-9012',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,NULL,NULL,'2026-06-04 17:47:56','2026-06-04 17:47:56');

-- --------------------------------------------------------
-- Table: `legal_privacy_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_privacy_logs`;
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

-- --------------------------------------------------------
-- Table: `legal_rbi_complaints`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_rbi_complaints`;
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

-- --------------------------------------------------------
-- Table: `legal_verification_docs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `legal_verification_docs`;
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

-- --------------------------------------------------------
-- Table: `loan_applications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `loan_applications`;
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

-- --------------------------------------------------------
-- Table: `loan_commission`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `loan_commission`;
CREATE TABLE `loan_commission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) DEFAULT NULL,
  `commission` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `marketing_campaigns`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `marketing_campaigns`;
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

-- --------------------------------------------------------
-- Table: `monthly_revenue`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `monthly_revenue`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `monthly_revenue` AS select date_format(`payments`.`date`,'%Y-%m') AS `month`,count(0) AS `transaction_count`,sum(`payments`.`amount`) AS `total_revenue`,avg(`payments`.`amount`) AS `average_transaction` from `payments` where `payments`.`status` = 'success' group by date_format(`payments`.`date`,'%Y-%m') order by date_format(`payments`.`date`,'%Y-%m') desc;

INSERT INTO `monthly_revenue` VALUES 
('2026-06','1','4999.00','4999.000000'),
('2026-05','3','11997.00','3999.000000'),
('2026-04','1','2999.00','2999.000000'),
('2025-04','1','15000.00','15000.000000');

-- --------------------------------------------------------
-- Table: `notification_preferences`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notification_preferences`;
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

INSERT INTO `notification_preferences` VALUES 
('1','72','1','1','1','1','1','0','0','1','0','0','0','1','1','1','1','1','2026-06-02 12:49:31'),
('2','77','1','1','1','1','1','0','0','1','0','0','0','1','1','1','1','1','2026-06-02 16:39:03'),
('3','78','1','1','1','1','1','0','0','1','0','0','0','1','1','1','1','1','2026-06-02 16:39:45'),
('4','74','1','1','1','1','1','0','0','1','0','0','0','1','1','1','1','1','2026-06-06 05:38:57'),
('5','110','1','1','1','1','1','0','0','1','0','0','0','1','1','1','1','1','2026-06-06 13:18:51');

-- --------------------------------------------------------
-- Table: `notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notifications`;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `notifications` VALUES 
('1','47','Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success','1','2026-05-04 07:54:51'),
('2','47','New Features Added','Notification system, activity log, and backup features are now available.','info','1','2026-05-04 07:54:51'),
('3','47','Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning','1','2026-05-04 07:54:51'),
('4','52','Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success','1','2026-05-09 16:09:01'),
('5','52','New Features Added','Notification system, activity log, and backup features are now available.','info','1','2026-05-09 16:09:01'),
('6','52','Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning','1','2026-05-09 16:09:01'),
('7','1','Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success','0','2026-05-29 17:58:45'),
('8','1','New Features Added','Notification system, activity log, and backup features are now available.','info','0','2026-05-29 17:58:45'),
('9','1','Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning','0','2026-05-29 17:58:45'),
('10','82','Welcome to Dashboard','Welcome to your admin dashboard! Explore all features.','success','0','2026-06-02 15:24:16'),
('11','82','New Features Added','Notification system, activity log, and backup features are now available.','info','0','2026-06-02 15:24:16'),
('12','82','Security Tip','Regular backups are recommended. Use the Backup feature to secure your data.','warning','0','2026-06-02 15:24:16');

-- --------------------------------------------------------
-- Table: `ombudsman_cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ombudsman_cases`;
CREATE TABLE `ombudsman_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `case_id` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `filing_date` date DEFAULT NULL,
  `hearing_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `verdict` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `operation_cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `operation_cases`;
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

-- --------------------------------------------------------
-- Table: `operation_tasks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `operation_tasks`;
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

-- --------------------------------------------------------
-- Table: `operational_tasks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `operational_tasks`;
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

-- --------------------------------------------------------
-- Table: `partner_applications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_applications`;
CREATE TABLE `partner_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ref_number` varchar(40) NOT NULL,
  `partner_type` varchar(60) NOT NULL DEFAULT 'Individual',
  `name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `alt_email` varchar(200) DEFAULT NULL,
  `dob` varchar(30) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `aadhaar` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pin_code` varchar(20) DEFAULT NULL,
  `work_location` varchar(255) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `role_title` varchar(200) DEFAULT NULL,
  `gst` varchar(20) DEFAULT NULL,
  `experience` varchar(100) DEFAULT NULL,
  `volume` varchar(60) DEFAULT NULL,
  `sector` varchar(100) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `ref_code` varchar(60) DEFAULT NULL,
  `background` text DEFAULT NULL,
  `payout_method` varchar(60) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(60) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `pan_file_url` varchar(512) DEFAULT NULL,
  `aadhaar_file_url` varchar(512) DEFAULT NULL,
  `photo_file_url` varchar(255) DEFAULT NULL,
  `bank_file_url` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(60) DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `gst_file_url` varchar(255) DEFAULT NULL,
  `biz_file_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ref` (`ref_number`),
  KEY `idx_email` (`email`(100)),
  KEY `idx_status` (`status`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partner_applications` VALUES 
('14','CR-PART-20260625-EF8F4C','Individual','Siddharth Singh','siddharthsingh115@gmail.com','7376350774','7376350774','siddharthsingh115@gmail.com','1996-06-20','GROPS3377D','448776526837','PRAYAGRAJ','Uttar Pradesh','221503',NULL,'Digital Sanskar','Managing director','','1–3 years','1-5','Banking / Finance','Google Search','','','Bank Transfer','','ICICI Bank','628201561332','ICIC0006282','https://cibilrepair.in/download.php?file=PAN_1782366248_ef2c87d3.jpg','https://cibilrepair.in/download.php?file=AADHAAR_1782366248_582f71a1.jpg','https://cibilrepair.in/download.php?file=PHOTO_1782366248_96128a53.jpg','https://cibilrepair.in/download.php?file=BANK_PROOF_1782366248_62f2b153.jpg','pending',NULL,NULL,NULL,NULL,NULL,'2409:40e3:20c1:fb6e:60a0:e6ff:fea5:e692','2026-06-25 05:44:08',NULL,NULL),
('20','CR-PART-20260705-2C5540','Individual','Abhishek','falcon.rnc08@gmail.com','8235249812','8235249812','falcon.rnc08@gmail.com','1978-03-24','AUYPA3626N','804451553770','Ranchi','Jharkhand','834009','Ranchi','','','','5–10 years','15-30','Banking / Finance','Google Search','','Ex Banker by profession with 7+ years of experience as generalist and credit officer. Well versed with pre and post loan sanction process, customer credit needs and glitches.\r\nCan contact with the local banks, loan agencies to find the potential customer for referrals.','Bank Transfer','7488757844@ybl','Punjab National Bank','6816000100030388','PUNB0681600','https://cibilrepair.in/download.php?file=PAN_1783263856_a6bfd8af.jpg','https://cibilrepair.in/download.php?file=AADHAAR_1783263856_38940faa.jpg','https://cibilrepair.in/download.php?file=PHOTO_1783263856_f727dbc2.jpg','https://cibilrepair.in/download.php?file=BANK_PROOF_1783263856_4ec4cab3.jpg','pending',NULL,NULL,NULL,NULL,NULL,'2401:4900:71d4:a5a1:6440:84ff:feb2:b2e8','2026-07-05 15:04:16',NULL,NULL),
('21','CR-PART-20260710-0CD06A','Agency','ARUPP0819M','advnilesh.pujara@gmail.com','9227488177','9227488177','advnilesh.pujara@gmail.com','1982-09-22','ARUPP0819M','764931202401','RAJKOT','Gujarat','360007','Rajkot','Divine Legal Counsel','Advocate','','10+ years','1-5','Legal / Lawyers','LinkedIn','','I have a 12 years of experience in Banking & Finance, Real Estate.','Bank Transfer','','Axis Bank','925020025764363','UTIB0004281','https://cibilrepair.in/download.php?file=PAN_1783666433_1595b299.pdf','https://cibilrepair.in/download.php?file=AADHAAR_1783666433_d860839b.pdf','https://cibilrepair.in/download.php?file=PHOTO_1783666433_e6c8ad73.jpg','https://cibilrepair.in/download.php?file=BANK_PROOF_1783666433_dd03406c.pdf','pending',NULL,NULL,NULL,NULL,NULL,'2405:f600:8:6b1d:7816:cd2a:e6e6:2560','2026-07-10 06:53:53',NULL,NULL),
('22','CR-PART-20260721-89FAD5','DSA','YOGENDRA SINGH','ramyafinancialconsultants@gmail.com','8171867186','8171867186','sisodiya.yogendra1992@gmail.com','1992-10-02','DQZPS6894A','801927841240','MATHURA','Uttar Pradesh','281403','Mathura','RAMYA FINANCIAL SERVICES','PROPRIETOR','','5–10 years','5-15','Banking / Finance','LinkedIn','','A banking and housing finance professional with over 6 years of experience in Home Loan and Loan Against Property (LAP) underwriting, credit appraisal, and mortgage operations. Worked with leading housing finance companies including SMFG India Home Finance Ltd., Aadhar Housing Finance Ltd., Tata Capital Housing Finance Ltd., and Aditya Birla Housing Finance Ltd.\r\n\r\nPossess strong expertise in evaluating customer eligibility, credit risk assessment, income analysis, bank statement verification, CIBIL analysis, property valuation review, and legal and technical due diligence. Successfully underwrote approximately 60–70 loan applications per month with an average loan ticket size of ₹30 lakh, maintaining an approval ratio of 60–70% while ensuring compliance with lending policies and a turnaround time of around 3 days.\r\n\r\nProficient in FinnOne, Loan Origination System (LOS), Loan Management System (LMS), Microsoft Excel, and Tally ERP 9. Experienced in working closely with Sales, Legal, Technical, Operations, and Collections teams to facilitate timely loan processing and maintain portfolio quality.\r\n\r\nWith a comprehensive understanding of retail lending and credit processes, I am now leveraging my industry expertise as a Direct Selling Agent (DSA) to help customers secure suitable Home Loans and Loan Against Property solutions while delivering quality business to banks and financial institutions.','Bank Transfer','','SBI','20234447410','SBIN0017682','https://cibilrepair.in/download.php?file=PAN_1784632094_325cdb27.pdf','https://cibilrepair.in/download.php?file=AADHAAR_1784632094_8b4ac96e.pdf','https://cibilrepair.in/download.php?file=PHOTO_1784632094_fbe688c1.jpeg','https://cibilrepair.in/download.php?file=BANK_PROOF_1784632094_fd1e7d7e.pdf','pending',NULL,NULL,NULL,NULL,NULL,'2401:4900:bfa1:8b4e:69fc:c19:b705:31e','2026-07-21 11:08:14',NULL,NULL);

-- --------------------------------------------------------
-- Table: `partner_commission`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_commission`;
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

INSERT INTO `partner_commission` VALUES 
('1','1',NULL,'Rajesh Kumar','Credit Repair','25000.00','3750.00','earned','2026-06-05 15:58:41'),
('2','2',NULL,'Priya Sharma','Loan Assistance','15000.00','1800.00','paid','2026-06-05 15:58:41'),
('3','1',NULL,'Amit Patel','Corporate Package','50000.00','7500.00','earned','2026-06-05 15:58:41');

-- --------------------------------------------------------
-- Table: `partner_commissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_commissions`;
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

-- --------------------------------------------------------
-- Table: `partner_connectors`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_connectors`;
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

-- --------------------------------------------------------
-- Table: `partner_contacts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_contacts`;
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

-- --------------------------------------------------------
-- Table: `partner_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_documents`;
CREATE TABLE `partner_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `document_type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------
-- Table: `partner_followups`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_followups`;
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

-- --------------------------------------------------------
-- Table: `partner_leaderboard`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_leaderboard`;
CREATE TABLE `partner_leaderboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(100) NOT NULL,
  `points` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partner_leaderboard` VALUES 
('1','Apex Financial Services','2840','active','2026-05-02 10:27:36'),
('2','Credit Guru Associates','2750','active','2026-05-02 10:27:36'),
('3','Elite Credit Solutions','2610','active','2026-05-02 10:27:36'),
('4','Rapid Resolve Partners','2490','active','2026-05-02 10:27:36'),
('5','TrustLink Advisors','2320','active','2026-05-02 10:27:36'),
('6','Vision Credit Care','2180','active','2026-05-02 10:27:36'),
('7','NexGen Fintech','1890','active','2026-05-02 10:27:36'),
('8','Prime Assist Group','1650','inactive','2026-05-02 10:27:36'),
('9','Apex Financial Services','2840','active','2026-05-02 10:35:33'),
('10','Credit Guru Associates','2750','active','2026-05-02 10:35:33'),
('11','Elite Credit Solutions','2610','active','2026-05-02 10:35:33'),
('12','Rapid Resolve Partners','2490','active','2026-05-02 10:35:33'),
('13','TrustLink Advisors','2320','active','2026-05-02 10:35:33'),
('14','Vision Credit Care','2180','active','2026-05-02 10:35:33'),
('15','NexGen Fintech','1890','active','2026-05-02 10:35:33'),
('16','Prime Assist Group','1650','inactive','2026-05-02 10:35:33');

-- --------------------------------------------------------
-- Table: `partner_leads`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_leads`;
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
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  KEY `idx_partner_id` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partner_leads` VALUES 
('1','46','Test Customer','9999999999',NULL,'Written Off Clearance','new','0.00',NULL,'2026-05-09 09:01:22','2026-05-09 11:42:21',NULL,NULL,'0','100','urgent',NULL,'0','0',NULL,'pending','0',NULL,NULL,NULL,NULL,'Website');

-- --------------------------------------------------------
-- Table: `partner_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_notifications`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partner_notifications` VALUES 
('1','83','Welcome to Partner Dashboard! 🎉','Thank you for joining CIBIL Repair as a partner. Start adding leads to earn up to 50% commission!','0','success','2026-06-06 11:28:26'),
('2','10','Welcome to Partner Dashboard! 🎉','Thank you for joining CIBIL Repair as a partner. Start adding leads to earn up to 50% commission!','0','success','2026-06-28 10:53:32');

-- --------------------------------------------------------
-- Table: `partner_payouts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_payouts`;
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

INSERT INTO `partner_payouts` VALUES 
('1','10','1500.00','bank_transfer','Test payout - Commission for June 2026','paid','PAY-TEST-001','2026-06-30 21:23:48','2026-06-30 21:25:32'),
('2','10','2500.00','upi','Commission - July 2026','pending','PAY-002','2026-06-30 21:25:24',NULL),
('3','10','5000.00','bank_transfer','Commission - June 2026 - PAID','paid','PAY-003','2026-06-23 21:25:24',NULL);

-- --------------------------------------------------------
-- Table: `partner_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_profiles`;
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

-- --------------------------------------------------------
-- Table: `partner_referrals`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_referrals`;
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

INSERT INTO `partner_referrals` VALUES 
('1','10','PART-D3D94468','kundan','kundankhatri@gmail.com','9470637604','connector','','converted','500.00','2026-06-29 18:10:05',NULL,'2026-06-29 18:10:05','10.00','0.00'),
('2','10','PART-D3D94468','kundan','kundankhatri46@gmail.com','9470637603','partner','API test from console','registered','1500.00','2026-06-29 18:14:38',NULL,'2026-06-29 18:14:38','25.00','0.00');

-- --------------------------------------------------------
-- Table: `partner_ticket_replies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_ticket_replies`;
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

-- --------------------------------------------------------
-- Table: `partner_tickets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_tickets`;
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

-- --------------------------------------------------------
-- Table: `partner_tiers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partner_tiers`;
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

-- --------------------------------------------------------
-- Table: `partners`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `partners`;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `idx_phone` (`phone`),
  KEY `idx_tier_level` (`tier_level`),
  KEY `idx_kyc_status` (`kyc_status`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `partners` VALUES 
('1','Delhi Credit Solutions',NULL,NULL,'amit@dcs.in',NULL,'9876543215','Delhi Credit Solutions',NULL,NULL,NULL,NULL,'12.00','0','0','0.00','active',NULL,'2026-07-19 07:51:56',NULL,'1',NULL,NULL,'Amit Singh',NULL,NULL,'company','silver','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'verified',NULL,NULL,'0',NULL),
('2','Mumbai Finance Hub',NULL,NULL,'ramesh@mfh.in',NULL,'9876543216','Mumbai Finance Hub',NULL,NULL,NULL,NULL,'10.00','0','0','0.00','active',NULL,'2026-07-19 07:51:56',NULL,'1',NULL,NULL,'Ramesh Patil',NULL,NULL,'company','silver','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'verified',NULL,NULL,'0',NULL),
('3','Chennai CIBIL Experts',NULL,NULL,'kiran@cce.in',NULL,'9876543217','Chennai CIBIL Experts',NULL,NULL,NULL,NULL,'11.00','0','0','0.00','active',NULL,'2026-07-19 07:51:56',NULL,'1',NULL,NULL,'Kiran Nair',NULL,NULL,'company','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'verified',NULL,NULL,'0',NULL),
('5','Console Test Partner',NULL,NULL,'',NULL,'9876543219',NULL,NULL,NULL,NULL,NULL,'10.00','0','0','0.00','active',NULL,'2026-07-19 16:58:18',NULL,'1',NULL,NULL,NULL,NULL,NULL,'individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'pending',NULL,NULL,'0',NULL),
('13','Test Partner',NULL,NULL,'test@partner.com',NULL,'9876543210','Test Company','','','','','10.00','0','0','0.00','active',NULL,'2026-07-19 17:16:20',NULL,'1','','','','','','individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50','','','pending',NULL,NULL,'0',NULL),
('14','Delhi Finance',NULL,NULL,'delhi@finance.com',NULL,'9876543100','','','','','','10.00','0','0','0.00','active',NULL,'2026-07-19 17:16:51',NULL,'1','','','','','','individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50','','','pending',NULL,NULL,'0',NULL),
('15','Bangalore Solutions',NULL,NULL,'bangalore@solutions.com',NULL,'9876543102','','','','','','10.00','0','0','0.00','active',NULL,'2026-07-19 17:16:51',NULL,'1','','','','','','individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50','','','pending',NULL,NULL,'0',NULL),
('16','Mumbai Credit',NULL,NULL,'mumbai@credit.com',NULL,'9876543101','','','','','','10.00','0','0','0.00','active',NULL,'2026-07-19 17:16:51',NULL,'1','','','','','','individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50','','','pending',NULL,NULL,'0',NULL),
('20','Fixed Partner 1785016990796','Mumbai','John Doe','fixed1785016990796@test.com',NULL,'9876543773',NULL,NULL,NULL,NULL,NULL,'15.00','0','0','0.00','active','Company: Test Company\nAddress: 123 Test Street\n','2026-07-25 22:03:10',NULL,'1',NULL,NULL,NULL,NULL,NULL,'individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'pending',NULL,NULL,'0',NULL),
('21','Full Test 1785017085629','Delhi NCR','Rahul Sharma','full1785017085629@partner.com',NULL,'9876543966',NULL,NULL,NULL,NULL,NULL,'25.00','0','0','0.00','active','Company: ABC Solutions Pvt Ltd\nAddress: 456, Connaught Place, New Delhi\n','2026-07-25 22:04:46',NULL,'1',NULL,NULL,NULL,NULL,NULL,'individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'pending',NULL,NULL,'0',NULL),
('22','Test Partner 1785022455234','Mumbai','John Doe','partner1785022455234@test.com',NULL,'9876543329',NULL,NULL,NULL,NULL,NULL,'15.00','0','0','0.00','active','','2026-07-25 23:34:15',NULL,'1',NULL,NULL,NULL,NULL,NULL,'individual','bronze','30.00','30.00','0','0.00','0.00','0.00','4.50',NULL,NULL,'pending',NULL,NULL,'0',NULL);

-- --------------------------------------------------------
-- Table: `payments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payments`;
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
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_client` (`clientName`),
  KEY `idx_payments_client` (`clientName`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_date` (`date`),
  KEY `idx_payments_date_status` (`date`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payments` VALUES 
('1','Rajesh Kumar','15000.00','Written Off Clearance','success','2025-04-15',NULL,NULL,NULL),
('2','Rajesh Kumar','4999.00','CIBIL Dispute Resolution','success','2026-06-02',NULL,NULL,NULL),
('3','Rajesh Kumar','1999.00','Credit Report Analysis','success','2026-05-03',NULL,NULL,NULL),
('4','Priya Sharma','9999.00','Written Off Settlement','pending','2026-06-02',NULL,NULL,NULL),
('5','Priya Sharma','4999.00','Credit Score Improvement','success','2026-05-18',NULL,NULL,NULL),
('6','Amit Patel','2999.00','Document Verification','success','2026-04-18',NULL,NULL,NULL),
('7','Neha Gupta','1999.00','Loan Eligibility Check','pending','2026-06-02',NULL,NULL,NULL),
('8','Vikram Singh','4999.00','CIBIL Dispute Resolution','success','2026-05-23',NULL,NULL,NULL),
('9','Rajesh Kumar','25000.00','Credit Repair','paid','2026-06-05',NULL,'UPI','Premium Package'),
('10','Priya Sharma','15000.00','Credit Repair','paid','2026-06-05',NULL,'Credit Card','Basic Package'),
('11','Amit Patel','50000.00','Corporate','pending','2026-06-05',NULL,'NEFT','Corporate Package'),
('12','Neha Gupta','10000.00','Loan','paid','2026-05-31',NULL,'UPI','Loan Assistance'),
('13','Suresh Singh','25000.00','Credit Repair','paid','2026-05-26',NULL,'Card','Premium Package'),
('14','Anita Desai','15000.00','Credit Repair','paid','2026-05-21',NULL,'Cash','Basic Package'),
('15','Rajesh Kumar','25000.00','CIBIL Dispute Resolution','completed','2026-07-25',NULL,'UPI','Premium Package');

-- --------------------------------------------------------
-- Table: `payout_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payout_requests`;
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

INSERT INTO `payout_requests` VALUES 
('1','partner','1','3750.00',NULL,'pending','2026-06-05',NULL,NULL,'2026-06-05 15:59:26'),
('2','partner','2','1800.00',NULL,'completed','2026-05-31',NULL,NULL,'2026-06-05 15:59:26'),
('3','employee','5','5000.00',NULL,'pending','2026-06-05',NULL,NULL,'2026-06-05 15:59:26'),
('4','partner','10','5000.00','Bank Transfer','pending','2026-06-29',NULL,NULL,'2026-06-29 15:30:35');

-- --------------------------------------------------------
-- Table: `payouts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payouts`;
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

-- --------------------------------------------------------
-- Table: `payroll`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payroll`;
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `payroll_date` date NOT NULL,
  `basic` decimal(12,2) DEFAULT NULL,
  `hra` decimal(12,2) DEFAULT NULL,
  `conveyance` decimal(12,2) DEFAULT NULL,
  `medical_allowance` decimal(12,2) DEFAULT NULL,
  `special_allowance` decimal(12,2) DEFAULT NULL,
  `performance_bonus` decimal(12,2) DEFAULT NULL,
  `overtime_pay` decimal(12,2) DEFAULT NULL,
  `other_earnings` decimal(12,2) DEFAULT NULL,
  `total_earnings` decimal(12,2) DEFAULT NULL,
  `pf_deduction` decimal(12,2) DEFAULT NULL,
  `esi_deduction` decimal(12,2) DEFAULT NULL,
  `professional_tax` decimal(12,2) DEFAULT NULL,
  `tds` decimal(12,2) DEFAULT NULL,
  `loan_deduction` decimal(12,2) DEFAULT NULL,
  `advance_deduction` decimal(12,2) DEFAULT NULL,
  `other_deductions` decimal(12,2) DEFAULT NULL,
  `total_deductions` decimal(12,2) DEFAULT NULL,
  `net_salary` decimal(12,2) DEFAULT NULL,
  `payment_mode` enum('bank_transfer','cheque','cash') DEFAULT 'bank_transfer',
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('pending','processed','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_month` (`employee_id`,`year`,`month`),
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `payroll` VALUES 
('1','1','6','2026','2026-06-02','50000.00','25000.00',NULL,NULL,'10000.00',NULL,NULL,NULL,'85000.00','1800.00',NULL,'200.00','2000.00',NULL,NULL,NULL,'4000.00','81000.00','bank_transfer',NULL,NULL,'processed',NULL,NULL,'2026-06-02 13:09:19'),
('2','2','6','2026','2026-06-02','75000.00','37500.00',NULL,NULL,'15000.00',NULL,NULL,NULL,'127500.00','2700.00',NULL,'300.00','5000.00',NULL,NULL,NULL,'8000.00','119500.00','bank_transfer',NULL,NULL,'processed',NULL,NULL,'2026-06-02 13:09:19'),
('3','3','6','2026','2026-06-02','45000.00','22500.00',NULL,NULL,'9000.00',NULL,NULL,NULL,'76500.00','1620.00',NULL,'180.00','1500.00',NULL,NULL,NULL,'3300.00','73200.00','bank_transfer',NULL,'2026-06-02','processed',NULL,NULL,'2026-06-02 13:09:19'),
('4','4','6','2026','2026-06-02','35000.00','17500.00',NULL,NULL,'7000.00',NULL,NULL,NULL,'59500.00','1260.00',NULL,'140.00','1000.00',NULL,NULL,NULL,'2400.00','57100.00','bank_transfer',NULL,NULL,'processed',NULL,NULL,'2026-06-02 13:11:00'),
('5','5','6','2026','2026-06-02','40000.00','20000.00',NULL,NULL,'8000.00',NULL,NULL,NULL,'68000.00','1440.00',NULL,'160.00','1200.00',NULL,NULL,NULL,'2800.00','65200.00','bank_transfer',NULL,'2026-06-02','processed',NULL,NULL,'2026-06-02 13:11:00');

-- --------------------------------------------------------
-- Table: `pending_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pending_reports`;
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

INSERT INTO `pending_reports` VALUES 
('1','75','Neha Gupta','CIBIL',NULL,'1','2026-06-05 18:21:12','pending',NULL),
('2','76','Vikram Singh','Experian',NULL,'1','2026-06-05 18:21:12','pending',NULL),
('3','110','Client User','Equifax',NULL,'1','2026-06-05 18:21:12','pending',NULL),
('4','72','Rajesh Kumar','CRIF',NULL,'1','2026-06-05 18:21:12','processing',NULL);

-- --------------------------------------------------------
-- Table: `performance_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `performance_reviews`;
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

-- --------------------------------------------------------
-- Table: `permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;
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

INSERT INTO `permissions` VALUES 
('1','view_users','View Users','User Management','Can view all users','2026-06-02 18:48:31'),
('2','create_users','Create Users','User Management','Can create new users','2026-06-02 18:48:31'),
('3','edit_users','Edit Users','User Management','Can edit user details','2026-06-02 18:48:31'),
('4','delete_users','Delete Users','User Management','Can delete users','2026-06-02 18:48:31'),
('5','assign_roles','Assign Roles','User Management','Can assign roles to users','2026-06-02 18:48:31'),
('6','view_clients','View Clients','Client Management','Can view all clients','2026-06-02 18:48:31'),
('7','create_clients','Create Clients','Client Management','Can add new clients','2026-06-02 18:48:31'),
('8','edit_clients','Edit Clients','Client Management','Can edit client details','2026-06-02 18:48:31'),
('9','delete_clients','Delete Clients','Client Management','Can delete clients','2026-06-02 18:48:31'),
('10','import_clients','Import Clients','Client Management','Can import clients in bulk','2026-06-02 18:48:31'),
('11','view_cases','View Cases','Case Management','Can view all cases','2026-06-02 18:48:31'),
('12','create_cases','Create Cases','Case Management','Can create new cases','2026-06-02 18:48:31'),
('13','edit_cases','Edit Cases','Case Management','Can edit case details','2026-06-02 18:48:31'),
('14','assign_cases','Assign Cases','Case Management','Can assign cases to team members','2026-06-02 18:48:31'),
('15','close_cases','Close Cases','Case Management','Can close/resolve cases','2026-06-02 18:48:31'),
('16','view_disputes','View Disputes','Dispute Management','Can view all disputes','2026-06-02 18:48:31'),
('17','file_disputes','File Disputes','Dispute Management','Can file new disputes','2026-06-02 18:48:31'),
('18','update_disputes','Update Disputes','Dispute Management','Can update dispute status','2026-06-02 18:48:31'),
('19','resolve_disputes','Resolve Disputes','Dispute Management','Can resolve disputes','2026-06-02 18:48:31'),
('20','view_payments','View Payments','Payment Management','Can view all payments','2026-06-02 18:48:31'),
('21','create_payments','Create Payments','Payment Management','Can record payments','2026-06-02 18:48:31'),
('22','refund_payments','Refund Payments','Payment Management','Can process refunds','2026-06-02 18:48:31'),
('23','view_invoices','View Invoices','Payment Management','Can view invoices','2026-06-02 18:48:31'),
('24','generate_invoices','Generate Invoices','Payment Management','Can generate invoices','2026-06-02 18:48:31'),
('25','view_partners','View Partners','Partner Management','Can view all partners','2026-06-02 18:48:31'),
('26','approve_partners','Approve Partners','Partner Management','Can approve partner applications','2026-06-02 18:48:31'),
('27','manage_commissions','Manage Commissions','Partner Management','Can manage partner commissions','2026-06-02 18:48:31'),
('28','view_partner_reports','View Partner Reports','Partner Management','Can view partner performance reports','2026-06-02 18:48:31'),
('29','view_employees','View Employees','HR Management','Can view all employees','2026-06-02 18:48:31'),
('30','manage_attendance','Manage Attendance','HR Management','Can manage employee attendance','2026-06-02 18:48:31'),
('31','approve_leaves','Approve Leaves','HR Management','Can approve leave requests','2026-06-02 18:48:31'),
('32','process_payroll','Process Payroll','HR Management','Can process monthly payroll','2026-06-02 18:48:31'),
('33','manage_departments','Manage Departments','HR Management','Can manage departments','2026-06-02 18:48:31'),
('34','view_reports','View Reports','Reports','Can view all reports','2026-06-02 18:48:31'),
('35','export_reports','Export Reports','Reports','Can export reports to Excel/PDF','2026-06-02 18:48:31'),
('36','view_dashboard','View Dashboard','Reports','Can view admin dashboard','2026-06-02 18:48:31'),
('37','view_analytics','View Analytics','Reports','Can view analytics and charts','2026-06-02 18:48:31'),
('38','manage_settings','Manage Settings','System','Can manage system settings','2026-06-02 18:48:31'),
('39','view_audit_logs','View Audit Logs','System','Can view audit logs','2026-06-02 18:48:31'),
('40','manage_backup','Manage Backup','System','Can manage database backups','2026-06-02 18:48:31'),
('41','view_activity_logs','View Activity Logs','System','Can view user activity logs','2026-06-02 18:48:31');

-- --------------------------------------------------------
-- Table: `pm_activities`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_activities`;
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

INSERT INTO `pm_activities` VALUES 
('1','1','1','comment','Project initiated. Credit reports requested from all 3 bureaus.','2026-06-04 18:12:40'),
('2','1','1','status_change','Project status changed from Planning to In Progress','2026-06-04 18:12:40'),
('3','1','2','task_completed','Initial Credit Report Analysis task completed','2026-06-04 18:12:40');

-- --------------------------------------------------------
-- Table: `pm_documents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_documents`;
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

-- --------------------------------------------------------
-- Table: `pm_milestones`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_milestones`;
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

INSERT INTO `pm_milestones` VALUES 
('1','1','Credit Report Obtained','Successfully obtained all 3 credit reports','2026-06-11',NULL,'achieved','0','2026-06-04 18:12:40'),
('2','1','Disputes Filed','All disputes filed with credit bureaus','2026-06-25',NULL,'pending','0','2026-06-04 18:12:40'),
('3','1','First Response Received','Received first response from bureau','2026-07-19',NULL,'pending','0','2026-06-04 18:12:40'),
('4','1','Errors Corrected','All identified errors corrected','2026-08-18',NULL,'pending','0','2026-06-04 18:12:40'),
('5','1','Project Completion','Project successfully completed','2026-09-02',NULL,'pending','0','2026-06-04 18:12:40');

-- --------------------------------------------------------
-- Table: `pm_projects`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_projects`;
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

INSERT INTO `pm_projects` VALUES 
('1','PRJ-2024001','Credit Repair - John Doe','1','credit_repair','Full credit repair including dispute with CIBIL, Experian, and Equifax','2026-06-04','2026-09-02',NULL,'25000.00','0.00','in_progress','high','0','1','1','2026-06-04 18:12:40','2026-06-04 18:12:40');

-- --------------------------------------------------------
-- Table: `pm_task_dependencies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_task_dependencies`;
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

-- --------------------------------------------------------
-- Table: `pm_tasks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_tasks`;
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

INSERT INTO `pm_tasks` VALUES 
('1','1','Initial Credit Report Analysis','Analyze all 3 credit bureau reports','1','completed','high','2026-06-09',NULL,'8.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40'),
('2','1','Identify Dispute Items','Identify all errors and discrepancies','1','completed','high','2026-06-14',NULL,'4.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40'),
('3','1','Prepare Dispute Letters','Draft dispute letters for each bureau','1','in_progress','high','2026-06-19',NULL,'12.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40'),
('4','1','Submit Disputes to CIBIL','File disputes with CIBIL','1','pending','medium','2026-06-24',NULL,'4.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40'),
('5','1','Follow-up with Bureaus','Regular follow-up on dispute status','1','pending','medium','2026-07-19',NULL,'8.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40'),
('6','1','Review Resolution','Review bureau responses and resolutions','1','pending','high','2026-08-18',NULL,'6.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40'),
('7','1','Final Report to Client','Prepare and share final credit report','1','pending','medium','2026-09-02',NULL,'4.00','0.00',NULL,'1','2026-06-04 18:12:40','2026-06-04 18:12:40');

-- --------------------------------------------------------
-- Table: `pm_team_members`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_team_members`;
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

INSERT INTO `pm_team_members` VALUES 
('1','1','1','Project Manager','50','2026-06-04',NULL,'1','2026-06-04 18:12:40'),
('2','1','2','Credit Analyst','100','2026-06-04',NULL,'1','2026-06-04 18:12:40'),
('3','1','3','Dispute Specialist','75','2026-06-04',NULL,'1','2026-06-04 18:12:40');

-- --------------------------------------------------------
-- Table: `pm_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pm_templates`;
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

-- --------------------------------------------------------
-- Table: `posters`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `posters`;
CREATE TABLE `posters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(10) unsigned DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_by` (`deleted_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `qa_agent_evaluations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_agent_evaluations`;
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

-- --------------------------------------------------------
-- Table: `qa_agent_performance`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_agent_performance`;
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

-- --------------------------------------------------------
-- Table: `qa_calibration_sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_calibration_sessions`;
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

-- --------------------------------------------------------
-- Table: `qa_call_recordings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_call_recordings`;
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

-- --------------------------------------------------------
-- Table: `qa_complaint_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_complaint_categories`;
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

INSERT INTO `qa_complaint_categories` VALUES 
('1','Service Delay',NULL,'medium','1','2026-06-04 18:47:55'),
('2','Credit Report Error',NULL,'high','2','2026-06-04 18:47:55'),
('3','Billing Issue',NULL,'medium','1','2026-06-04 18:47:55'),
('4','Poor Customer Service',NULL,'medium','1','2026-06-04 18:47:55'),
('5','Dispute Resolution Failure',NULL,'high','2','2026-06-04 18:47:55'),
('6','Data Privacy Concern',NULL,'critical','3','2026-06-04 18:47:55'),
('7','Technical Issue',NULL,'low','1','2026-06-04 18:47:55'),
('8','Miscommunication',NULL,'low','1','2026-06-04 18:47:55');

-- --------------------------------------------------------
-- Table: `qa_complaints`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_complaints`;
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

-- --------------------------------------------------------
-- Table: `qa_scorecards`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_scorecards`;
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

INSERT INTO `qa_scorecards` VALUES 
('1','Support Agent Scorecard','Customer Support','[\"Communication\",\"Product Knowledge\",\"Resolution Time\",\"Empathy\",\"Compliance\"]','[25,25,20,15,15]','75','1','1','2026-06-04 18:47:55','2026-06-04 18:47:55'),
('2','Credit Analyst Scorecard','Credit Analysis','[\"Accuracy\",\"Timeliness\",\"Documentation\",\"Communication\",\"Compliance\"]','[30,20,20,15,15]','80','1','1','2026-06-04 18:47:55','2026-06-04 18:47:55');

-- --------------------------------------------------------
-- Table: `qa_survey_responses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_survey_responses`;
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

-- --------------------------------------------------------
-- Table: `qa_surveys`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_surveys`;
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

INSERT INTO `qa_surveys` VALUES 
('1','Customer Satisfaction Survey','csat','{\"questions\":[{\"q\":\"How satisfied are you with our service?\",\"type\":\"rating\"},{\"q\":\"Would you recommend us to others?\",\"type\":\"yes_no\"},{\"q\":\"Any additional feedback?\",\"type\":\"text\"}]}','1','1','2026-06-04 18:47:55'),
('2','Post-Resolution Survey','post_resolution','{\"questions\":[{\"q\":\"Was your issue resolved satisfactorily?\",\"type\":\"rating\"},{\"q\":\"How would you rate the agent?\",\"type\":\"rating\"},{\"q\":\"How can we improve?\",\"type\":\"text\"}]}','1','1','2026-06-04 18:47:55');

-- --------------------------------------------------------
-- Table: `qa_ticket_reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `qa_ticket_reviews`;
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

INSERT INTO `qa_ticket_reviews` VALUES 
('1','1','1','1','2026-06-04','85','8','9','8','9','7','Good work on this ticket',NULL,'2026-06-04 18:47:55');

-- --------------------------------------------------------
-- Table: `quotations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `quotations`;
CREATE TABLE `quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_no` varchar(50) NOT NULL,
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
  `gst_amount` decimal(12,2) DEFAULT 0.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `total_with_gst` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','sent','accepted','rejected','expired') DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `quote_no` (`quote_no`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `quotations` VALUES 
('1','QUO001','Rajesh Kumar','rajesh@example.com','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'Written Off','15000.00','0.00','0.00','0.00','0.00','sent','2025-04-15','Initial quotation for written off settlement',NULL,'2026-07-19 07:51:56'),
('2','QUO002','Priya Sharma','priya@example.com','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Settled','22000.00','0.00','0.00','0.00','0.00','draft','2025-05-01','Draft quotation for settlement case',NULL,'2026-07-19 07:51:56'),
('3','QUO20260003','Rajesh Kumar','rajesh@example.com','9876543210','22AAAAA0000A1Z5','AAAAA1234A','123, MG Road','Mumbai','Maharashtra','400001','CIBIL Repair','15000.00','2700.00','1350.00','1350.00','17700.00','draft','2025-08-26','Initial quotation for CIBIL repair services','0','2026-07-25 22:14:48'),
('4','QUO20260004','Test Customer 1785022455576','test1785022455576@quotation.com','9876543626','','','','','','','CIBIL Repair','15000.00','2700.00','1350.00','1350.00','17700.00','draft','2025-08-26','Test quotation','0','2026-07-25 23:34:16');

-- --------------------------------------------------------
-- Table: `rbi_complaints`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `rbi_complaints`;
CREATE TABLE `rbi_complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `complaint_id` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `filing_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `resolution_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `real_estate_agents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `real_estate_agents`;
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

-- --------------------------------------------------------
-- Table: `reconciliation`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reconciliation`;
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

-- --------------------------------------------------------
-- Table: `referral_commissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `referral_commissions`;
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

-- --------------------------------------------------------
-- Table: `referral_tracking`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `referral_tracking`;
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

-- --------------------------------------------------------
-- Table: `referrals`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `referrals`;
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

-- --------------------------------------------------------
-- Table: `registration_codes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `registration_codes`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `registration_codes` VALUES 
('1','PRTN-A8X2K9','partner','newpartner@example.com',NULL,'30','2025-06-01 00:00:00','0',NULL,'1','2026-07-19 07:51:56'),
('2','CLNT-M3N7P1','client','',NULL,'15','2025-05-15 00:00:00','1',NULL,'1','2026-07-19 07:51:56');

-- --------------------------------------------------------
-- Table: `repair_strategies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `repair_strategies`;
CREATE TABLE `repair_strategies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_type` varchar(100) DEFAULT NULL,
  `strategy` text DEFAULT NULL,
  `estimated_days` int(11) DEFAULT NULL,
  `success_rate` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `repair_strategies` VALUES 
('1','Written Off Account','File dispute with bank, obtain NOC, escalate to RBI if needed','45','90','2026-06-02 18:07:44'),
('2','Settled Account','Request closure letter, file with bureau for status update','30','85','2026-06-02 18:07:44'),
('3','Late Payment','Submit proof of on-time payment, request correction','20','75','2026-06-02 18:07:44'),
('4','Incorrect Enquiry','File dispute as unauthorized enquiry','15','85','2026-06-02 18:07:44'),
('5','Duplicate Loan','Request bank to report correct status, file merger request','25','80','2026-06-02 18:07:44'),
('6','Identity Mismatch','Submit KYC documents for correction across all bureaus','20','90','2026-06-02 18:07:44'),
('7','Written Off Account','File dispute with bank, obtain NOC, escalate to RBI if needed','45','90','2026-06-02 18:09:04'),
('8','Settled Account','Request closure letter, file with bureau for status update','30','85','2026-06-02 18:09:04'),
('9','Late Payment','Submit proof of on-time payment, request correction','20','75','2026-06-02 18:09:04'),
('10','Incorrect Enquiry','File dispute as unauthorized enquiry','15','85','2026-06-02 18:09:04'),
('11','Duplicate Loan','Request bank to report correct status, file merger request','25','80','2026-06-02 18:09:04'),
('12','Identity Mismatch','Submit KYC documents for correction across all bureaus','20','90','2026-06-02 18:09:04'),
('13','Written Off Account','File dispute with bank, obtain NOC, escalate to RBI if needed','45','90','2026-06-02 18:10:42'),
('14','Settled Account','Request closure letter, file with bureau for status update','30','85','2026-06-02 18:10:42'),
('15','Late Payment','Submit proof of on-time payment, request correction','20','75','2026-06-02 18:10:42'),
('16','Incorrect Enquiry','File dispute as unauthorized enquiry','15','85','2026-06-02 18:10:42'),
('17','Duplicate Loan','Request bank to report correct status, file merger request','25','80','2026-06-02 18:10:42'),
('18','Identity Mismatch','Submit KYC documents for correction across all bureaus','20','90','2026-06-02 18:10:42'),
('19','Written Off Account','File dispute with bank, obtain NOC, escalate to RBI if needed','45','90','2026-06-02 18:11:53'),
('20','Settled Account','Request closure letter, file with bureau for status update','30','85','2026-06-02 18:11:53'),
('21','Late Payment','Submit proof of on-time payment, request correction','20','75','2026-06-02 18:11:53'),
('22','Incorrect Enquiry','File dispute as unauthorized enquiry','15','85','2026-06-02 18:11:53'),
('23','Duplicate Loan','Request bank to report correct status, file merger request','25','80','2026-06-02 18:11:53'),
('24','Identity Mismatch','Submit KYC documents for correction across all bureaus','20','90','2026-06-02 18:11:53'),
('25','Written Off Account','File dispute with bank, obtain NOC, escalate to RBI if needed','45','90','2026-06-02 18:12:26'),
('26','Settled Account','Request closure letter, file with bureau for status update','30','85','2026-06-02 18:12:26'),
('27','Late Payment','Submit proof of on-time payment, request correction','20','75','2026-06-02 18:12:26'),
('28','Incorrect Enquiry','File dispute as unauthorized enquiry','15','85','2026-06-02 18:12:26'),
('29','Duplicate Loan','Request bank to report correct status, file merger request','25','80','2026-06-02 18:12:26'),
('30','Identity Mismatch','Submit KYC documents for correction across all bureaus','20','90','2026-06-02 18:12:26'),
('31','Written Off Account','File dispute with bureau, obtain NOC from bank, escalate to RBI if needed','45','85','2026-06-04 17:15:55'),
('32','Settled Account','Get settlement letter from bank, file dispute for status update to \"closed\"','30','90','2026-06-04 17:15:55'),
('33','Late Payment','Check bank records for payment proof, file dispute with evidence','20','75','2026-06-04 17:15:55'),
('34','Incorrect Enquiry','Identify unauthorized enquiry, file dispute for removal','15','95','2026-06-04 17:15:55'),
('35','Duplicate Loan','Flag duplicate entry, request bureau to remove duplicate','25','88','2026-06-04 17:15:55'),
('36','Identity Mismatch','Submit correct ID proofs, file correction request with bureau','20','92','2026-06-04 17:15:55'),
('37','Written Off Account','Dispute the account with credit bureau. Provide settlement letter or NOC if already paid.','45','75','2026-06-05 18:21:12'),
('38','Settled Account','Request bank to update status to \"Closed\" instead of \"Settled\".','30','65','2026-06-05 18:21:12'),
('39','Late Payment','Send goodwill letter to bank explaining circumstances.','15','55','2026-06-05 18:21:12'),
('40','Incorrect Enquiry','File dispute stating enquiry was without authorization.','20','85','2026-06-05 18:21:12'),
('41','Duplicate Loan','Request bank to remove duplicate entry.','25','80','2026-06-05 18:21:12'),
('42','Identity Mismatch','Submit correct identity proof to bureau.','15','90','2026-06-05 18:21:12');

-- --------------------------------------------------------
-- Table: `reply_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reply_templates`;
CREATE TABLE `reply_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `template` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reply_templates` VALUES 
('1','Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
('2','Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
('3','Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
('4','Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.'),
('5','Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
('6','Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
('7','Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
('8','Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.'),
('9','Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
('10','Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
('11','Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
('12','Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.'),
('13','Welcome Message','General','Thank you for contacting CIBIL Repair. Our team will review your query and get back to you within 24 hours.'),
('14','Document Request','Documents','Please upload the following documents: 1) PAN Card, 2) Aadhaar Card, 3) Latest CIBIL Report.'),
('15','Status Update','Case','Your case is currently in progress. The dispute has been filed with the bank and we are awaiting their response.'),
('16','Resolution','Case','Great news! Your issue has been resolved. The entry has been updated in the credit bureau records.');

-- --------------------------------------------------------
-- Table: `report_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `report_history`;
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

-- --------------------------------------------------------
-- Table: `report_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `report_templates`;
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

-- --------------------------------------------------------
-- Table: `reviews`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review_text` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reviews` VALUES 
('1','Maneet Singh','maneet@example.com','5','Thank you sir, thank you very much for doing my work in a very short time. Unauthorized loan removed from my wife\'s CIBIL within weeks.','approved','2026-05-02 11:34:26'),
('2','Sunil Enterprises','sunil@example.com','5','Excellent work done by this company. My credit score improved by 87 points after settled account removal.','approved','2026-05-02 11:34:26'),
('3','Ranjeet Thakur','ranjeet@example.com','5','Very professional team. They removed a suit filed entry from my CIBIL that was holding back my loan approval.','approved','2026-05-02 11:34:26');

-- --------------------------------------------------------
-- Table: `risk_audit_findings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_audit_findings`;
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

INSERT INTO `risk_audit_findings` VALUES 
('1','internal','Incomplete KYC Documentation','5 client records missing Aadhaar verification','high','Operations','0000-00-00','1',NULL,NULL,NULL,'in_progress',NULL,'2026-06-04 18:02:25','2026-06-04 18:02:25');

-- --------------------------------------------------------
-- Table: `risk_compliance_breaches`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_compliance_breaches`;
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

INSERT INTO `risk_compliance_breaches` VALUES 
('1','sla_breach','high','Ticket #TKT-001 missed SLA response time by 4 hours',NULL,'2026-06-04 18:02:25','1',NULL,'investigating',NULL,NULL,'0.00');

-- --------------------------------------------------------
-- Table: `risk_fraud_alerts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_fraud_alerts`;
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

INSERT INTO `risk_fraud_alerts` VALUES 
('1','suspicious_login','user','1','medium','Multiple login attempts from different locations within 10 minutes','2026-06-04 16:02:25',NULL,NULL,'pending',NULL);

-- --------------------------------------------------------
-- Table: `risk_kyc_assessment`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_kyc_assessment`;
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

-- --------------------------------------------------------
-- Table: `risk_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_profiles`;
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

INSERT INTO `risk_profiles` VALUES 
('1','client','1','15.50','low',NULL,'2026-06-04 18:02:25','1','Regular client with good history','2026-06-04 18:02:25','2026-06-04 18:02:25');

-- --------------------------------------------------------
-- Table: `risk_rules_config`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_rules_config`;
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

INSERT INTO `risk_rules_config` VALUES 
('1','Multiple Failed Logins','authentication','{\"attempts\": \">5\", \"timeframe\": \"15_minutes\"}',NULL,'8','high','1','1','2026-06-04 18:02:25','2026-06-04 18:02:25'),
('2','Unusual Transaction Amount','transaction','{\"amount\": \">50000\", \"frequency\": \"daily\"}',NULL,'7','high','1','1','2026-06-04 18:02:25','2026-06-04 18:02:25'),
('3','Multiple IP Addresses','session','{\"different_ips\": \">3\", \"timeframe\": \"1_hour\"}',NULL,'5','medium','1','1','2026-06-04 18:02:25','2026-06-04 18:02:25'),
('4','Document Mismatch','kyc','{\"pan_aadhaar_name\": \"mismatch\"}',NULL,'10','critical','1','1','2026-06-04 18:02:25','2026-06-04 18:02:25'),
('5','Rapid Successive Transactions','transaction','{\"count\": \">10\", \"timeframe\": \"1_hour\"}',NULL,'9','critical','1','1','2026-06-04 18:02:25','2026-06-04 18:02:25'),
('6','Suspicious Location','login','{\"country\": \"high_risk\"}',NULL,'6','high','1','1','2026-06-04 18:02:25','2026-06-04 18:02:25');

-- --------------------------------------------------------
-- Table: `risk_suspicious_activities`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_suspicious_activities`;
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

INSERT INTO `risk_suspicious_activities` VALUES 
('1','1','client','rapid_page_access','Accessed 50+ pages in 2 minutes','192.168.1.100',NULL,'75','0','2026-06-04 18:02:25',NULL,NULL);

-- --------------------------------------------------------
-- Table: `risk_transaction_monitoring`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `risk_transaction_monitoring`;
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

-- --------------------------------------------------------
-- Table: `role_permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `role_permissions`;
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

INSERT INTO `role_permissions` VALUES 
('1','1','11','2026-06-02 18:48:31'),
('2','1','12','2026-06-02 18:48:31'),
('3','1','13','2026-06-02 18:48:31'),
('4','1','14','2026-06-02 18:48:31'),
('5','1','15','2026-06-02 18:48:31'),
('6','1','6','2026-06-02 18:48:31'),
('7','1','7','2026-06-02 18:48:31'),
('8','1','8','2026-06-02 18:48:31'),
('9','1','9','2026-06-02 18:48:31'),
('10','1','10','2026-06-02 18:48:31'),
('11','1','16','2026-06-02 18:48:31'),
('12','1','17','2026-06-02 18:48:31'),
('13','1','18','2026-06-02 18:48:31'),
('14','1','19','2026-06-02 18:48:31'),
('15','1','29','2026-06-02 18:48:31'),
('16','1','30','2026-06-02 18:48:31'),
('17','1','31','2026-06-02 18:48:31'),
('18','1','32','2026-06-02 18:48:31'),
('19','1','33','2026-06-02 18:48:31'),
('20','1','25','2026-06-02 18:48:31'),
('21','1','26','2026-06-02 18:48:31'),
('22','1','27','2026-06-02 18:48:31'),
('23','1','28','2026-06-02 18:48:31'),
('24','1','20','2026-06-02 18:48:31'),
('25','1','21','2026-06-02 18:48:31'),
('26','1','22','2026-06-02 18:48:31'),
('27','1','23','2026-06-02 18:48:31'),
('28','1','24','2026-06-02 18:48:31'),
('29','1','34','2026-06-02 18:48:31'),
('30','1','35','2026-06-02 18:48:31'),
('31','1','36','2026-06-02 18:48:31'),
('32','1','37','2026-06-02 18:48:31'),
('33','1','38','2026-06-02 18:48:31'),
('34','1','39','2026-06-02 18:48:31'),
('35','1','40','2026-06-02 18:48:31'),
('36','1','41','2026-06-02 18:48:31'),
('37','1','1','2026-06-02 18:48:31'),
('38','1','2','2026-06-02 18:48:31'),
('39','1','3','2026-06-02 18:48:31'),
('40','1','4','2026-06-02 18:48:31'),
('41','1','5','2026-06-02 18:48:31'),
('64','2','26','2026-06-02 18:48:31'),
('65','2','14','2026-06-02 18:48:31'),
('66','2','15','2026-06-02 18:48:31'),
('67','2','12','2026-06-02 18:48:31'),
('68','2','7','2026-06-02 18:48:31'),
('69','2','21','2026-06-02 18:48:31'),
('70','2','2','2026-06-02 18:48:31'),
('71','2','13','2026-06-02 18:48:31'),
('72','2','8','2026-06-02 18:48:31'),
('73','2','3','2026-06-02 18:48:31'),
('74','2','35','2026-06-02 18:48:31'),
('75','2','17','2026-06-02 18:48:31'),
('76','2','24','2026-06-02 18:48:31'),
('77','2','38','2026-06-02 18:48:31'),
('78','2','18','2026-06-02 18:48:31'),
('79','2','37','2026-06-02 18:48:31'),
('80','2','39','2026-06-02 18:48:31'),
('81','2','11','2026-06-02 18:48:31'),
('82','2','6','2026-06-02 18:48:31'),
('83','2','36','2026-06-02 18:48:31'),
('84','2','16','2026-06-02 18:48:31'),
('85','2','23','2026-06-02 18:48:31'),
('86','2','25','2026-06-02 18:48:31'),
('87','2','20','2026-06-02 18:48:31'),
('88','2','34','2026-06-02 18:48:31'),
('89','2','1','2026-06-02 18:48:31'),
('95','3','31','2026-06-02 18:48:31'),
('96','3','35','2026-06-02 18:48:31'),
('97','3','30','2026-06-02 18:48:31'),
('98','3','33','2026-06-02 18:48:31'),
('99','3','32','2026-06-02 18:48:31'),
('100','3','36','2026-06-02 18:48:31'),
('101','3','29','2026-06-02 18:48:31'),
('102','3','34','2026-06-02 18:48:31'),
('110','4','21','2026-06-02 18:48:31'),
('111','4','35','2026-06-02 18:48:31'),
('112','4','24','2026-06-02 18:48:31'),
('113','4','27','2026-06-02 18:48:31'),
('114','4','22','2026-06-02 18:48:31'),
('115','4','36','2026-06-02 18:48:31'),
('116','4','23','2026-06-02 18:48:31'),
('117','4','25','2026-06-02 18:48:31'),
('118','4','20','2026-06-02 18:48:31'),
('119','4','34','2026-06-02 18:48:31');

-- --------------------------------------------------------
-- Table: `roles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `roles`;
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

INSERT INTO `roles` VALUES 
('1','super_admin','Super Administrator','Full system access with all permissions','1','1','2026-06-02 18:48:31'),
('2','admin','Administrator','Platform administrator','2','1','2026-06-02 18:48:31'),
('3','hr_manager','HR Manager','Manage employees, attendance, payroll','3','1','2026-06-02 18:48:31'),
('4','finance_manager','Finance Manager','Manage payments, invoices, commissions','4','1','2026-06-02 18:48:31'),
('5','credit_analyst','Credit Analyst','Analyze credit reports and issues','5','1','2026-06-02 18:48:31'),
('6','dispute_team','Dispute Team','Process disputes with bureaus and banks','6','1','2026-06-02 18:48:31'),
('7','support_agent','Support Agent','Handle customer support tickets','7','1','2026-06-02 18:48:31'),
('8','sales_executive','Sales Executive','Manage leads and client acquisition','8','1','2026-06-02 18:48:31'),
('9','operations_manager','Operations Manager','Manage team workload and SLAs','9','1','2026-06-02 18:48:31'),
('10','partner','Partner','External partner/agent','10','1','2026-06-02 18:48:31'),
('11','client','Client','End customer','11','1','2026-06-02 18:48:31'),
('12','employee','Employee','Internal employee','12','1','2026-06-02 18:48:31');

-- --------------------------------------------------------
-- Table: `sales`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales`;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sales` VALUES 
('1','1','Rajesh Kumar','rajesh@example.com','9876543210',NULL,NULL,NULL,NULL,NULL,NULL,'Written Off','15000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'1800.00',NULL,'completed','2025-01-20','2026-07-19 07:51:56'),
('2','2','Priya Sharma','priya@example.com','9876543211',NULL,NULL,NULL,NULL,NULL,NULL,'Settled','22000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'2640.00',NULL,'completed','2025-02-15','2026-07-19 07:51:56'),
('3','3','Meena Patel','meena@example.com','9876543213',NULL,NULL,NULL,NULL,NULL,NULL,'Profile Correction','8000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'960.00',NULL,'completed','2025-03-10','2026-07-19 07:51:56'),
('4','4','Arun Verma','arun@example.com','9876543220',NULL,NULL,NULL,NULL,NULL,NULL,'Written Off','18000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'2160.00',NULL,'completed','2025-04-05','2026-07-19 07:51:56'),
('5','5','Kavita Gupta','kavita@example.com','9876543221',NULL,NULL,NULL,NULL,NULL,NULL,'Settled','25000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'3000.00',NULL,'pending','2025-04-18','2026-07-19 07:51:56'),
('6','6','Deepika Rao','deepika@email.com','9876541005',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair','15000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'1800.00',NULL,'completed','2026-07-19','2026-07-19 07:54:26'),
('7','6','Deepika Rao','deepika@email.com','9876541005',NULL,NULL,NULL,NULL,NULL,NULL,'CIBIL Repair','15000.00','18.00','0.00','0.00','0.00','0.00','1','Cash',NULL,'1800.00',NULL,'completed','2026-07-19','2026-07-19 07:56:24'),
('8','0','Rajesh Kumar','rajesh@example.com','9876543210','22AAAAA0000A1Z5','AAAAA1234A','','','','','CIBIL Repair','15000.00','18.00','2700.00','1350.00','1350.00','17700.00','1','UPI','','0.00','0','completed','2026-07-25','2026-07-25 22:22:39'),
('9','0','Test Sale 1785022455645','sale1785022455645@test.com','9876543237','','','','','','','CIBIL Repair','15000.00','18.00','2700.00','1350.00','1350.00','17700.00','1','UPI','','0.00','0','completed','2026-07-25','2026-07-25 23:34:16');

-- --------------------------------------------------------
-- Table: `sales_activities`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales_activities`;
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

-- --------------------------------------------------------
-- Table: `sales_commissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales_commissions`;
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

-- --------------------------------------------------------
-- Table: `sales_leads`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales_leads`;
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

-- --------------------------------------------------------
-- Table: `sales_targets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sales_targets`;
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

-- --------------------------------------------------------
-- Table: `scheduled_reports`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `scheduled_reports`;
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

-- --------------------------------------------------------
-- Table: `scoring_rules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `scoring_rules`;
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

INSERT INTO `scoring_rules` VALUES 
('1','Website Lead','source','equals','Website','25','1.00','1','2026-05-09 10:39:45'),
('2','Referral Lead','source','equals','Referral','30','1.00','1','2026-05-09 10:39:45'),
('3','Social Media Lead','source','equals','Social Media','20','1.00','1','2026-05-09 10:39:45'),
('4','Call Lead','source','equals','Call','15','1.00','1','2026-05-09 10:39:45'),
('5','Written Off Clearance','service_type','equals','Written Off Clearance','35','1.00','1','2026-05-09 10:39:45'),
('6','Settled Clearance','service_type','equals','Settled Clearance','30','1.00','1','2026-05-09 10:39:45'),
('7','Suit Filed Clearance','service_type','equals','Suit Filed Clearance','40','1.00','1','2026-05-09 10:39:45'),
('8','Credit Report Analysis','service_type','equals','Credit Report Analysis','20','1.00','1','2026-05-09 10:39:45'),
('9','Profile Correction','service_type','equals','Profile Correction','15','1.00','1','2026-05-09 10:39:45'),
('10','Wrong Entry Clearance','service_type','equals','Wrong Entry Clearance','25','1.00','1','2026-05-09 10:39:45'),
('11','Lead Age 0-3 days','age_days','between','0,3','30','1.00','1','2026-05-09 10:39:45'),
('12','Lead Age 4-7 days','age_days','between','4,7','20','1.00','1','2026-05-09 10:39:45'),
('13','Lead Age 8-14 days','age_days','between','8,14','10','1.00','1','2026-05-09 10:39:45'),
('14','Lead Age 15+ days','age_days','greater','14','0','1.00','1','2026-05-09 10:39:45'),
('15','Status New','status','equals','new','25','1.00','1','2026-05-09 10:39:45'),
('16','Status Contacted','status','equals','contacted','15','1.00','1','2026-05-09 10:39:45'),
('17','Status Converted','status','equals','converted','0','1.00','1','2026-05-09 10:39:45'),
('18','Status Lost','status','equals','lost','0','1.00','1','2026-05-09 10:39:45');

-- --------------------------------------------------------
-- Table: `service_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `service_requests`;
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

-- --------------------------------------------------------
-- Table: `services`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `details` text DEFAULT NULL,
  `benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`benefits`)),
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `price` decimal(10,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT '30 days',
  `icon` varchar(10) DEFAULT NULL,
  `active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `services` VALUES 
('1','Written Off Clearance','Remove written-off accounts from your credit report',NULL,NULL,NULL,'15000.00','30 days','📝','1','2026-05-02 10:11:25'),
('2','Settled Clearance','Remove settled accounts and improve your score',NULL,NULL,NULL,'12000.00','30 days','💰','1','2026-05-02 10:11:25'),
('3','Suit Filed Clearance','Remove suit filed entries from your CIBIL report',NULL,NULL,NULL,'20000.00','30 days','⚖️','1','2026-05-02 10:11:25'),
('4','Credit Report Analysis','Detailed analysis of your credit report',NULL,NULL,NULL,'5000.00','30 days','📊','1','2026-05-02 10:11:25'),
('5','Wrong Entry Clearance','Remove incorrect entries from your report',NULL,NULL,NULL,'8000.00','30 days','❌','1','2026-05-02 10:11:25'),
('6','Profile Correction','Fix personal information discrepancies',NULL,NULL,NULL,'3000.00','30 days','👤','1','2026-05-02 10:11:25'),
('7','Written Off Clearance','Remove written‑off accounts from your credit report and boost your CIBIL score significantly.','🔹 WHAT IS A WRITTEN‑OFF ACCOUNT?\n• A lender gives up on recovering a debt and marks it as \"written off\".\n• The entry stays on your credit report for 7+ years.\n• It drops your score drastically.\n\n🔹 HOW WE REMOVE IT\n• Step 1: Analyse your documents.\n• Step 2: Send legal notices.\n• Step 3: Negotiate with the bank.\n• Step 4: File disputes with bureaus.\n• Step 5: Follow up until deleted.\n\n🔹 WHAT YOU GAIN\n• Score improvement of 50–100 points.\n• Eligibility for loans & credit cards.\n\n🔹 98% success rate + money‑back guarantee.','[\"Eliminates the most damaging negative entry\",\"Improves credit score by 50–100 points\",\"Restores eligibility for home/car loans\",\"Clears path for credit card approvals\",\"100% legal process – money‑back guarantee\"]','[\"PAN Card (mandatory)\",\"Aadhar Card (mandatory)\",\"Latest CIBIL Report\",\"Loan account details (if available)\",\"Bank statements (helpful)\"]','15000.00','30 days','📝','1','2026-05-02 11:28:27'),
('8','Settled Clearance','Convert settled accounts to closed status and improve your creditworthiness.','🔹 WHAT IS A SETTLED ACCOUNT?\n• You paid less than full outstanding.\n• It signals risk to lenders.\n\n🔹 HOW WE CONVERT TO \"CLOSED\"\n• Collect settlement letter.\n• Draft legal notices.\n• Negotiate with bank.\n• Escalate to bureaus.\n• Verify updated status.\n\n🔹 WHAT YOU GAIN\n• Score improvement 40–80 points.\n• Become eligible for new credit.\n• Better interest rates.','[\"Removes the stigma of partial payment\",\"Boosts credit score by 40–80 points\",\"Makes you eligible for new credit cards & loans\",\"Improves loan terms (lower interest rates)\",\"No hidden charges – transparent pricing\"]','[\"PAN Card\",\"Aadhar Card\",\"Settlement Letter from Bank\",\"Latest CIBIL Report\",\"Payment proof of settlement\"]','12000.00','30 days','💰','1','2026-05-02 11:28:27'),
('9','Suit Filed Clearance','Remove legal suit entries from your CIBIL report and rebuild your credit.','🔹 WHAT IS A SUIT FILED ENTRY?\n• Legal action taken against you.\n• Blocks almost all loan approvals.\n\n🔹 HOW WE REMOVE IT\n• Review court documents.\n• Coordinate with lawyer.\n• File for withdrawal.\n• Obtain court order.\n• Approach bureaus for deletion.\n\n🔹 WHAT YOU GAIN\n• Score improvement 80–150 points.\n• Eligibility for home loans, business credit.\n• Legal tag removed permanently.','[\"Removes the most damaging legal record\",\"Improves credit score by 80–150 points\",\"Restores eligibility for home loans, business credit\",\"Ends legal tag from your profile\",\"Free legal consultation included\"]','[\"PAN Card\",\"Aadhar Card\",\"Court case documents (if any)\",\"Latest CIBIL Report\",\"Loan/account details\"]','20000.00','30 days','⚖️','1','2026-05-02 11:28:27'),
('10','Credit Report Analysis','Forensic review of your credit report to find hidden errors and improvement areas.','🔹 FORENSIC ANALYSIS\n• Manual examination of every entry.\n• Identify errors, duplicates, identity theft.\n\n🔹 WHAT YOU GET\n• 20+ page detailed report.\n• Personal consultation with expert.\n• Prioritised action plan.\n\n🔹 MONEY‑BACK GUARANTEE\n• If no errors found, you pay nothing.','[\"Uncover hidden errors you never knew existed\",\"Understand exactly why your score is low\",\"Receive a step‑by‑step repair plan\",\"Avoid loan rejection due to hidden mistakes\",\"Money‑back guarantee if no errors found\"]','[\"Latest CIBIL Report (free from bank/website)\",\"PAN Card (optional)\",\"Aadhar Card (optional)\"]','5000.00','30 days','📊','1','2026-05-02 11:28:27'),
('11','Wrong Entry Clearance','Remove unauthorised loans, incorrect personal data, and other reporting errors.','🔹 TYPES OF WRONG ENTRIES\n• Unauthorised loans (fraud/identity theft).\n• Name/address/DOB mismatches.\n• Duplicate accounts.\n• Incorrect payment status.\n\n🔹 HOW WE FIX THEM\n• Gather proof.\n• File formal dispute with bureau.\n• Liaise with bank.\n• Escalate to ombudsman if needed.\n• Confirm removal.\n\n🔹 WEEKLY UPDATES + TRANSPARENCY','[\"Removes fraudulent/unauthorised loans\",\"Corrects personal data mismatches\",\"Eliminates duplicate accounts\",\"Restores true creditworthiness\",\"Regular progress updates\"]','[\"PAN Card\",\"Aadhar Card\",\"Latest CIBIL Report\",\"Proof of correct information (self‑attested)\"]','8000.00','30 days','❌','1','2026-05-02 11:28:27'),
('12','Profile Correction','Fix name, address, DOB & PAN mismatches in your credit report.','🔹 COMMON ERRORS\n• Name spelling difference.\n• Old address.\n• Wrong DOB.\n• PAN not linked.\n\n🔹 HOW WE CORRECT THEM\n• Collect proof documents.\n• Prepare correction requests for all bureaus.\n• Submit with evidence.\n• Follow up until updated.\n• Provide clean profile confirmation.\n\n🔹 FAST – Average 4 days resolution.','[\"Loan applications no longer rejected due to profile mismatch\",\"Ensures accurate data for lenders\",\"Prevents identity‑theft issues\",\"Improves overall credit reputation\",\"Quick 3‑5 day turnaround\"]','[\"PAN Card\",\"Aadhar Card\",\"Latest CIBIL Report\",\"Proof of correct information (self‑attested)\"]','3000.00','30 days','👤','1','2026-05-02 11:28:27'),
('13','Written Off Clearance','Remove written-off accounts from your credit report',NULL,NULL,NULL,'15000.00','30-45 days','📝','1','2026-05-02 14:29:31'),
('14','Settled Clearance','Convert settled accounts to closed status',NULL,NULL,NULL,'12000.00','30-45 days','💰','1','2026-05-02 14:29:31'),
('15','Suit Filed Clearance','Remove legal suit entries from CIBIL',NULL,NULL,NULL,'20000.00','30-45 days','⚖️','1','2026-05-02 14:29:31'),
('16','Credit Report Analysis','Detailed forensic review of your credit report',NULL,NULL,NULL,'5000.00','30-45 days','📊','1','2026-05-02 14:29:31'),
('17','Profile Correction','Fix personal information discrepancies',NULL,NULL,NULL,'3000.00','30-45 days','👤','1','2026-05-02 14:29:31'),
('18','Wrong Entry Clearance','Remove unauthorized loans and errors',NULL,NULL,NULL,'8000.00','30-45 days','❌','1','2026-05-02 14:29:31');

-- --------------------------------------------------------
-- Table: `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` VALUES 
('1','general','company_name','CIBIL Repair','string','0','0',NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
('2','general','company_email','contact@cibilrepair.in','string','0','0',NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
('3','general','company_phone','+91 87094 55441','string','0','0',NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
('4','general','company_website','https://cibilrepair.in','string','0','0',NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
('5','general','two_factor','enabled','string','0','0',NULL,NULL,NULL,'2026-07-19 07:51:56',NULL),
('6','general','session_timeout','60','string','0','0',NULL,NULL,NULL,'2026-07-19 07:51:56',NULL);

-- --------------------------------------------------------
-- Table: `shifts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `shifts`;
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

INSERT INTO `shifts` VALUES 
('1','Morning Shift','09:00:00','18:00:00','15','60','8.00','active'),
('2','Evening Shift','14:00:00','23:00:00','15','60','8.00','active'),
('3','Night Shift','22:00:00','06:00:00','15','60','8.00','active');

-- --------------------------------------------------------
-- Table: `sla_rules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sla_rules`;
CREATE TABLE `sla_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` enum('low','medium','high','urgent') NOT NULL,
  `response_hours` int(11) NOT NULL,
  `resolution_hours` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sla_rules` VALUES 
('1','urgent','1','4','2026-06-04 17:18:49'),
('2','high','2','8','2026-06-04 17:18:49'),
('3','medium','4','24','2026-06-04 17:18:49'),
('4','low','8','48','2026-06-04 17:18:49');

-- --------------------------------------------------------
-- Table: `social_media_posts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `social_media_posts`;
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

-- --------------------------------------------------------
-- Table: `stock_movements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `stock_movements`;
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

-- --------------------------------------------------------
-- Table: `success_stories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `success_stories`;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `success_stories` VALUES 
('1','Maneet Singh','Delhi','Home Loan Approved','620','745','My CIBIL score went from 620 to 745 in just 45 days. The team removed my written-off account and now I got my home loan approved. Absolutely life-changing!','5','approved','2026-05-11 15:28:50',NULL),
('2','Priya Sharma','Mumbai','Credit Card Approved','650','730','Professional and transparent service. They guided me through every step. My settled account was converted to closed and my score improved by 80 points. Highly recommended!','5','approved','2026-05-11 15:28:50',NULL),
('3','Rajesh Kumar','Bangalore','Business Loan Approved','580','710','I had multiple wrong entries. The experts removed all of them within 30 days. My loan got approved immediately after. Best decision ever!','5','approved','2026-05-11 15:28:50',NULL);

-- --------------------------------------------------------
-- Table: `suppliers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `suppliers`;
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

-- --------------------------------------------------------
-- Table: `support_agent_performance`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_agent_performance`;
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

-- --------------------------------------------------------
-- Table: `support_calls`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_calls`;
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

-- --------------------------------------------------------
-- Table: `support_csat`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_csat`;
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

-- --------------------------------------------------------
-- Table: `support_emails`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_emails`;
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

-- --------------------------------------------------------
-- Table: `support_escalations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_escalations`;
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

-- --------------------------------------------------------
-- Table: `support_faqs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_faqs`;
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

INSERT INTO `support_faqs` VALUES 
('1','How long does credit repair take?','Credit repair typically takes 3-6 months depending on the complexity of errors and the credit bureau\'s response time. We provide monthly updates on your progress.','credit_repair',NULL,'0','0','0','1','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('2','What documents do I need to start?','You need: 1) PAN Card, 2) Aadhaar Card, 3) Latest credit report from CIBIL, 4) Address proof, 5) Income proof (if applicable).','documentation',NULL,'0','0','0','1','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('3','How do I check my dispute status?','You can check dispute status in real-time from your client dashboard. Login and go to \"Dispute Tracker\" section to see updates.','disputes',NULL,'0','0','0','1','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('4','What is your refund policy?','We offer a 30-day money-back guarantee if no progress is made on your credit repair case. Full refund policy details are in your service agreement.','billing',NULL,'0','0','0','1','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('5','Can I dispute errors myself?','Yes, you can dispute errors yourself for free. However, our experts have higher success rates due to experience with credit laws and bureau procedures.','disputes',NULL,'0','0','0','1','1','2026-06-04 17:59:36','2026-06-04 17:59:36');

-- --------------------------------------------------------
-- Table: `support_reply_templates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_reply_templates`;
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

INSERT INTO `support_reply_templates` VALUES 
('1','Ticket Acknowledgment','ticket_response','Thank you for contacting support. Your ticket #{ticket_id} has been received and assigned to our team. We will respond within {response_time} hours.',NULL,'1','0','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('2','Dispute Status Update','dispute','Dear {client_name},\n\nYour dispute regarding {dispute_item} is currently under review with {bureau_name}. Current status: {status}. We will update you once we receive a response.\n\nBest regards,\nSupport Team',NULL,'1','0','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('3','Document Request','document','Dear {client_name},\n\nWe require the following document(s) to proceed with your case:\n{missing_documents}\n\nPlease upload these documents to your client portal.\n\nThank you,\nDocument Team',NULL,'1','0','1','2026-06-04 17:59:36','2026-06-04 17:59:36'),
('4','Resolution Confirmation','resolution','Dear {client_name},\n\nWe are pleased to inform you that your issue has been resolved. Resolution summary: {resolution_summary}\n\nPlease take a moment to rate your experience: {csat_link}\n\nBest regards,\nSupport Team',NULL,'1','0','1','2026-06-04 17:59:36','2026-06-04 17:59:36');

-- --------------------------------------------------------
-- Table: `support_sla_config`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_sla_config`;
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

INSERT INTO `support_sla_config` VALUES 
('1','low','24','72','48','1','2026-06-04 17:59:36'),
('2','medium','12','48','24','1','2026-06-04 17:59:36'),
('3','high','4','24','8','1','2026-06-04 17:59:36'),
('4','urgent','1','8','2','1','2026-06-04 17:59:36');

-- --------------------------------------------------------
-- Table: `support_sla_tracking`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_sla_tracking`;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `support_sla_tracking` VALUES 
('1','6','medium','2026-07-26 11:10:40','2026-07-27 23:10:40','0','0',NULL,NULL,'2026-07-25 23:10:40'),
('2','7','medium','2026-07-26 11:34:16','2026-07-27 23:34:16','0','0',NULL,NULL,'2026-07-25 23:34:16');

-- --------------------------------------------------------
-- Table: `support_ticket_replies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_ticket_replies`;
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

-- --------------------------------------------------------
-- Table: `support_tickets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_email` varchar(100) NOT NULL,
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
  PRIMARY KEY (`id`),
  KEY `idx_tickets_email_status` (`client_email`,`status`),
  KEY `idx_tickets_status_created` (`status`,`created_at`),
  KEY `idx_tickets_composite` (`status`,`priority`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `support_tickets` VALUES 
('1','rajesh@example.com','Unable to upload documents','I am facing issues while uploading my bank statement. The page shows error.',NULL,'resolved','2026-05-18 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL),
('2','priya@example.com','Question about dispute status','My written off dispute with ICICI Bank, when will it be resolved?',NULL,'in-progress','2026-05-28 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL),
('3','amit@example.com','Payment receipt not received','I made payment of ₹2,999 but haven\'t received the receipt yet.',NULL,'in-progress','2026-05-31 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL),
('4','neha@example.com','Credit score not updating','My CIBIL score shows old data from last month.',NULL,'in-progress','2026-06-01 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL),
('5','vikram@example.com','General inquiry about services','What are your charges for credit repair services?',NULL,'closed','2026-05-03 12:54:23',NULL,'medium','general',NULL,NULL,NULL,NULL),
('6','priya@example.com','Issue with CIBIL report','My CIBIL score is showing incorrect information',NULL,'open','2026-07-25 23:10:40','TKT-202607-000000','medium','general',NULL,NULL,NULL,NULL),
('7','priya@example.com','Test Ticket 1785022456011','This is a test ticket from the API test script',NULL,'open','2026-07-25 23:34:16','TKT-202607-000000','medium','general',NULL,NULL,NULL,NULL);

-- --------------------------------------------------------
-- Table: `support_whatsapp_chats`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `support_whatsapp_chats`;
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

-- --------------------------------------------------------
-- Table: `system_permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_permissions`;
CREATE TABLE `system_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `system_role_permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_role_permissions`;
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

-- --------------------------------------------------------
-- Table: `system_roles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_roles`;
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

-- --------------------------------------------------------
-- Table: `system_settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `tasks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tasks`;
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

INSERT INTO `tasks` VALUES 
('1','TASK-001','Welcome to ORION CRM','This is your first task. Start managing your business efficiently!','medium','pending',NULL,NULL,'1',NULL,'2026-05-20 06:34:22','2026-05-20 06:34:22'),
('3','TASK-002','Review Partner Applications','Check pending partner registrations','high','pending','2026-05-22',NULL,'1',NULL,'2026-05-20 06:36:35','2026-05-20 06:36:35'),
('4','TASK-003','Follow up with clients','Call pending customers for feedback','medium','pending','2026-05-20',NULL,'1',NULL,'2026-05-20 06:36:35','2026-05-20 06:36:35');

-- --------------------------------------------------------
-- Table: `taxes`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `taxes`;
CREATE TABLE `taxes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_name` varchar(50) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT NULL,
  `tax_type` enum('GST','Income Tax','TDS','PT','Others') DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `terms_consent`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `terms_consent`;
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

-- --------------------------------------------------------
-- Table: `ticket_categories`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ticket_categories`;
CREATE TABLE `ticket_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ticket_categories` VALUES 
('1','General Inquiry','General questions about services','1','1'),
('2','Technical Issue','Problems with dashboard or login','1','2'),
('3','Payment Related','Issues with payments, invoices, refunds','1','3'),
('4','Case Status','Updates about your credit repair case','1','4'),
('5','Document Support','Help with document upload and verification','1','5'),
('6','Dispute Assistance','Help with filing or tracking disputes','1','6'),
('7','Report Error','Report incorrect information in CIBIL report','1','7'),
('8','Feedback','Share your feedback or suggestions','1','8');

-- --------------------------------------------------------
-- Table: `ticket_escalations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ticket_escalations`;
CREATE TABLE `ticket_escalations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending',
  `escalated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `ticket_replies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_admin_reply` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ticket` (`ticket_id`),
  KEY `idx_partner` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ticket_replies` VALUES 
('1','1','72','I have tried multiple times but still getting error.',NULL,'0','2026-05-19 12:55:32'),
('2','1','0','Please clear your browser cache and try again. Also ensure file size is less than 5MB.',NULL,'1','2026-05-20 12:55:32'),
('3','1','72','It worked! Thank you for your help.',NULL,'0','2026-05-21 12:55:32'),
('4','2','73','Any update on my dispute? It has been 2 weeks.',NULL,'0','2026-05-29 12:55:32'),
('5','2','0','We have escalated this to the bank. Will update you within 3 days.',NULL,'1','2026-05-30 12:55:32'),
('6','3','74','Please check and send the receipt.',NULL,'0','2026-06-01 12:55:32'),
('7','1','0','We are looking into your issue. Will update soon.',NULL,'1','2026-06-02 13:12:42');

-- --------------------------------------------------------
-- Table: `tickets`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tickets`;
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

-- --------------------------------------------------------
-- Table: `tier_history`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tier_history`;
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

-- --------------------------------------------------------
-- Table: `training_assessment_results`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_assessment_results`;
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

-- --------------------------------------------------------
-- Table: `training_assessments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_assessments`;
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

-- --------------------------------------------------------
-- Table: `training_certifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_certifications`;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `training_certifications` VALUES 
('1','Certified Credit Repair Specialist','Credit Repair Association','professional','2','1','2026-06-04 18:22:23'),
('2','GDPR Compliance Professional','International Compliance Institute','compliance','1','1','2026-06-04 18:22:23'),
('3','Data Protection Officer','Data Protection Council','compliance','2','1','2026-06-04 18:22:23'),
('4','Credit Analysis Professional','Credit Institute','technical','3','1','2026-06-04 18:22:23'),
('5','Customer Service Excellence','Service Quality International','professional','2','1','2026-06-04 18:22:23'),
('6','Certified Credit Repair Specialist','Credit Repair Association','professional','2','1','2026-06-04 18:23:23'),
('7','GDPR Compliance Professional','International Compliance Institute','compliance','1','1','2026-06-04 18:23:23'),
('8','Data Protection Officer','Data Protection Council','compliance','2','1','2026-06-04 18:23:23'),
('9','Credit Analysis Professional','Credit Institute','technical','3','1','2026-06-04 18:23:23'),
('10','Customer Service Excellence','Service Quality International','professional','2','1','2026-06-04 18:23:23');

-- --------------------------------------------------------
-- Table: `training_courses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_courses`;
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

INSERT INTO `training_courses` VALUES 
('1','COMP-101','GDPR Compliance Training','compliance',NULL,'4.00','80','1','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('2','COMP-102','Data Privacy & Protection','compliance',NULL,'3.00','75','1','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('3','COMP-103','Anti-Money Laundering (AML)','compliance',NULL,'5.00','80','1','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('4','TECH-101','Credit Report Analysis','technical',NULL,'8.00','70','1','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('5','TECH-102','Dispute Management System','technical',NULL,'6.00','75','1','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('6','PROD-101','CIBIL Repair Process','product_knowledge',NULL,'4.00','70','1','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('7','SOFT-101','Customer Communication Skills','soft_skills',NULL,'3.00','65','0','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('8','SOFT-102','Conflict Resolution','soft_skills',NULL,'2.00','60','0','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23'),
('9','LEAD-101','Team Leadership Basics','leadership',NULL,'10.00','75','0','1','1','2026-06-04 18:22:23','2026-06-04 18:22:23');

-- --------------------------------------------------------
-- Table: `training_employee_certs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_employee_certs`;
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

-- --------------------------------------------------------
-- Table: `training_employee_skills`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_employee_skills`;
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

-- --------------------------------------------------------
-- Table: `training_employees`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_employees`;
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

-- --------------------------------------------------------
-- Table: `training_enrollments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_enrollments`;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `training_session_attendees`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_session_attendees`;
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

-- --------------------------------------------------------
-- Table: `training_sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_sessions`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `training_sessions` VALUES 
('3','GDPR Compliance Training - Batch 1','1','John Smith','virtual','2026-06-11 18:24:13','2026-06-11 22:24:13','30','0',NULL,NULL,'scheduled','1','2026-06-04 18:24:13'),
('4','Credit Report Analysis Workshop','4','Jane Doe','classroom','2026-06-18 18:24:13','2026-06-19 02:24:13','20','0',NULL,NULL,'scheduled','1','2026-06-04 18:24:13');

-- --------------------------------------------------------
-- Table: `training_skills`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `training_skills`;
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

INSERT INTO `training_skills` VALUES 
('1','Credit Report Analysis','technical','1','2026-06-04 18:22:23'),
('2','Dispute Resolution','technical','1','2026-06-04 18:22:23'),
('3','Customer Communication','soft_skills','1','2026-06-04 18:22:23'),
('4','Compliance Knowledge','compliance','1','2026-06-04 18:22:23'),
('5','Leadership','leadership','1','2026-06-04 18:22:23'),
('6','Time Management','soft_skills','1','2026-06-04 18:22:23'),
('7','Problem Solving','soft_skills','1','2026-06-04 18:22:23'),
('8','Data Analysis','technical','1','2026-06-04 18:22:23');

-- --------------------------------------------------------
-- Table: `trainings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `trainings`;
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

-- --------------------------------------------------------
-- Table: `transactions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` enum('credit','debit') NOT NULL,
  `method` varchar(50) DEFAULT NULL,
  `fee_amount` decimal(12,2) DEFAULT 0.00,
  `gst_amount` decimal(12,2) DEFAULT 0.00,
  `cgst_amount` decimal(12,2) DEFAULT 0.00,
  `sgst_amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `reference_id` varchar(100) DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `partner_id` int(10) unsigned DEFAULT NULL,
  `balance_after` decimal(14,2) DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tx_date` (`date`),
  KEY `idx_tx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transactions` VALUES 
('1','2025-04-20','Payment from Rajesh Kumar','15000.00','credit','UPI','0.00','0.00','0.00','0.00','0.00',NULL,NULL,NULL,'0.00','2026-07-19 07:51:56','2026-07-25 19:27:46'),
('2','2025-04-18','Salary disbursement','180000.00','debit','NEFT','0.00','0.00','0.00','0.00','0.00',NULL,NULL,NULL,'0.00','2026-07-19 07:51:56','2026-07-25 19:27:46'),
('3','2025-04-15','Payment from Priya Sharma','22000.00','credit','UPI','0.00','0.00','0.00','0.00','0.00',NULL,NULL,NULL,'0.00','2026-07-19 07:51:56','2026-07-25 19:27:46'),
('4','2025-04-12','Google Ads payment','15000.00','debit','Credit Card','0.00','0.00','0.00','0.00','0.00',NULL,NULL,NULL,'0.00','2026-07-19 07:51:56','2026-07-25 19:27:46'),
('5','2025-04-10','Commission payout — Delhi Credit','1800.00','debit','NEFT','0.00','0.00','0.00','0.00','0.00',NULL,NULL,NULL,'0.00','2026-07-19 07:51:56','2026-07-25 19:27:46'),
('6','2025-04-05','Payment from Meena Patel','8000.00','credit','UPI','0.00','0.00','0.00','0.00','0.00',NULL,NULL,NULL,'0.00','2026-07-19 07:51:56','2026-07-25 19:27:46');

-- --------------------------------------------------------
-- Table: `user_2fa`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_2fa`;
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

-- --------------------------------------------------------
-- Table: `user_activity`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_activity`;
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

-- --------------------------------------------------------
-- Table: `user_logins`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_logins`;
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

-- --------------------------------------------------------
-- Table: `user_metadata`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_metadata`;
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

-- --------------------------------------------------------
-- Table: `user_preferences`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_preferences`;
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

-- --------------------------------------------------------
-- Table: `user_profiles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_profiles`;
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

-- --------------------------------------------------------
-- Table: `user_security`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_security`;
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

-- --------------------------------------------------------
-- Table: `user_sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_sessions`;
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

-- --------------------------------------------------------
-- Table: `user_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_tokens`;
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

-- --------------------------------------------------------
-- Table: `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` VALUES 
('1','Admin','admin@cibilrepair.in','$2y$10$hjPfamqTctWFc3Zp.CMM7u8ucrbEncRmXLaTqDhcH8G/loGVT8u2m','admin','8709455441','Delhi',NULL,'active',NULL,NULL,'2026-07-19 07:51:56','2026-07-19 14:58:11',NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
('2','Amit Singh','amit@dcs.in','$2y$10$H7zPZq7zPZq7zPZq7zPZquO5uO5uO5uO5uO5uO5uO5uO5uO5uO5u','partner','9876543215','Delhi',NULL,'active',NULL,NULL,'2026-07-19 07:51:56','2026-07-19 14:35:30',NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
('3','Rajesh Kumar','rajesh@example.com','$2y$10$H7zPZq7zPZq7zPZq7zPZquO5uO5uO5uO5uO5uO5uO5uO5uO5uO5u','client','9876543210','Delhi',NULL,'active',NULL,NULL,'2026-07-19 07:51:56','2026-07-19 14:35:30',NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
('4','Priya Sharma','priya@example.com','$2y$10$H7zPZq7zPZq7zPZq7zPZquO5uO5uO5uO5uO5uO5uO5uO5uO5uO5u','client','9876543211','Mumbai',NULL,'active',NULL,NULL,'2026-07-19 07:51:56','2026-07-19 14:35:30',NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
('5','New Client 1785018634186','new1785018634186@client.com','','client','9876543338',NULL,NULL,'active','2026-07-25 22:52:50','1','2026-07-25 22:30:33','2026-07-25 22:52:50',NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL),
('6','Test Pending User 1785020267255','pending1785020267255@test.com','','client','9876543168',NULL,NULL,'active','2026-07-25 23:01:54','1','2026-07-25 22:57:47','2026-07-25 23:03:49',NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,'0',NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,NULL,NULL,'direct','Asia/Kolkata','en',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active',NULL,NULL);

-- --------------------------------------------------------
-- Table: `vw_active_agreements`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vw_active_agreements`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_active_agreements` AS select `la`.`id` AS `id`,`la`.`agreement_no` AS `agreement_no`,`c`.`name` AS `client_name`,`la`.`agreement_type` AS `agreement_type`,`la`.`issue_date` AS `issue_date`,`la`.`expiry_date` AS `expiry_date`,`la`.`status` AS `status`,to_days(`la`.`expiry_date`) - to_days(curdate()) AS `days_remaining` from (`legal_agreements` `la` join `clients` `c` on(`la`.`client_id` = `c`.`id`)) where `la`.`status` in ('sent','signed') and (`la`.`expiry_date` is null or `la`.`expiry_date` >= curdate());

-- --------------------------------------------------------
-- Table: `vw_api_error_rate`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vw_api_error_rate`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_api_error_rate` AS select hour(`it_api_logs`.`created_at`) AS `hour`,count(0) AS `total_requests`,sum(case when `it_api_logs`.`status_code` >= 400 then 1 else 0 end) AS `errors`,round(sum(case when `it_api_logs`.`status_code` >= 400 then 1 else 0 end) / count(0) * 100,2) AS `error_rate` from `it_api_logs` where `it_api_logs`.`created_at` >= current_timestamp() - interval 24 hour group by hour(`it_api_logs`.`created_at`) order by hour(`it_api_logs`.`created_at`) desc;

-- --------------------------------------------------------
-- Table: `vw_current_system_status`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vw_current_system_status`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_current_system_status` AS select `it_system_health`.`server_name` AS `server_name`,`it_system_health`.`cpu_usage` AS `cpu_usage`,`it_system_health`.`memory_usage` AS `memory_usage`,`it_system_health`.`disk_usage` AS `disk_usage`,case when `it_system_health`.`cpu_usage` > 80 then 'Critical' when `it_system_health`.`cpu_usage` > 60 then 'Warning' else 'Healthy' end AS `cpu_status`,case when `it_system_health`.`memory_usage` > 85 then 'Critical' when `it_system_health`.`memory_usage` > 70 then 'Warning' else 'Healthy' end AS `memory_status`,`it_system_health`.`logged_at` AS `last_update` from `it_system_health` where (`it_system_health`.`server_name`,`it_system_health`.`logged_at`) in (select `it_system_health`.`server_name`,max(`it_system_health`.`logged_at`) from `it_system_health` group by `it_system_health`.`server_name`);

INSERT INTO `vw_current_system_status` VALUES 
('CRM-API-Server','51.20','55.80','32.10','Healthy','Healthy','2026-06-04 17:50:50'),
('CRM-DB-Server','32.10','71.20','45.30','Healthy','Warning','2026-06-04 17:50:50'),
('CRM-Main-Server','45.50','62.30','58.70','Healthy','Healthy','2026-06-04 17:50:50');

-- --------------------------------------------------------
-- Table: `vw_partner_tiers`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vw_partner_tiers`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_partner_tiers` AS select `u`.`id` AS `partner_id`,`u`.`name` AS `partner_name`,`u`.`email` AS `partner_email`,`p`.`tier_id` AS `tier_id`,case `p`.`tier_id` when 1 then 'Bronze' when 2 then 'Silver' when 3 then 'Gold' when 4 then 'Platinum' when 5 then 'Diamond' else 'Bronze' end AS `tier_name`,case `p`.`tier_id` when 1 then 20 when 2 then 25 when 3 then 30 when 4 then 35 when 5 then 40 else 20 end AS `commission_rate`,`p`.`monthly_referrals` AS `monthly_referrals`,`p`.`tier_updated_at` AS `tier_updated_at`,(select count(0) from `leads` where `leads`.`partner_id` = `u`.`id` and `leads`.`status` = 'converted') AS `total_conversions` from (`users` `u` join `partners` `p` on(`u`.`id` = `p`.`user_id`)) where `u`.`role` = 'partner';

-- --------------------------------------------------------
-- Table: `vw_pending_kyc`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `vw_pending_kyc`;
CREATE ALGORITHM=UNDEFINED DEFINER=`u929623538_cibilrepair`@`127.0.0.1` SQL SECURITY DEFINER VIEW `vw_pending_kyc` AS select `lk`.`id` AS `id`,`c`.`name` AS `client_name`,`c`.`email` AS `client_email`,`c`.`phone` AS `client_phone`,`lk`.`verification_status` AS `verification_status`,`lk`.`submitted_at` AS `submitted_at`,to_days(curdate()) - to_days(`lk`.`submitted_at`) AS `days_pending` from (`legal_kyc_verification` `lk` join `clients` `c` on(`lk`.`client_id` = `c`.`id`)) where `lk`.`verification_status` = 'pending';

INSERT INTO `vw_pending_kyc` VALUES 
('1','Rajesh Kumar','rajesh@example.com','9876543210','pending','2026-06-04 17:47:56','51');

-- --------------------------------------------------------
-- Table: `wallet`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `wallet`;
CREATE TABLE `wallet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `balance` decimal(10,2) DEFAULT 0.00,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `wallet` VALUES 
('1','5000.00','2026-05-26 07:29:51');

-- --------------------------------------------------------
-- Table: `wallet_transactions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `wallet_transactions`;
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

-- --------------------------------------------------------
-- Table: `whatsapp_chats`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `whatsapp_chats`;
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

-- --------------------------------------------------------
-- Table: `whatsapp_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `whatsapp_logs`;
CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_number` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: `work_cases`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `work_cases`;
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

SET FOREIGN_KEY_CHECKS=1;
