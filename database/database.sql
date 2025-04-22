-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Store only hashed passwords',
  `user_type` enum('applicant','employer','professional') NOT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Set to 1 after OTP verification',
  `email_verification_token` varchar(100) DEFAULT NULL,
  `email_verification_expires` datetime DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `google_id` VARCHAR(255) NULL,
  `auth_provider` ENUM('local', 'google') DEFAULT 'local',
  `profile_picture` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_user_type_status` (`user_type`, `status`, `deleted_at`),
  KEY `idx_users_email_verified` (`email_verified`),
  UNIQUE KEY (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- OAuth tokens table
CREATE TABLE oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (provider, provider_user_id)
);

-- Professionals table
CREATE TABLE `professionals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `profile_image` varchar(100) DEFAULT NULL,
  `license_number` varchar(30) NOT NULL,
  `years_experience` int(11) NOT NULL,
  `education` text NOT NULL,
  `bio` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `website` varchar(100) DEFAULT NULL,
  `profile_completed` tinyint(1) NOT NULL DEFAULT 0,
  `rating` decimal(3,2) DEFAULT NULL,
  `reviews_count` int(11) DEFAULT 0,
  `verification_status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending' COMMENT 'Admin verification status after document review',
  `verification_notes` text DEFAULT NULL COMMENT 'Admin notes about verification',
  `verified_at` datetime DEFAULT NULL COMMENT 'When admin verified the professional',
  `verified_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who verified',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `availability_status` enum('available','busy','unavailable') NOT NULL DEFAULT 'available',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `license_number` (`license_number`),
  KEY `idx_prof_availability_verification_featured` (`availability_status`, `verification_status`, `is_featured`),
  KEY `idx_prof_rating_verification` (`rating`, `verification_status`),
  KEY `idx_prof_verification_status` (`verification_status`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `professionals_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professionals_verified_by_fk` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Service types table
CREATE TABLE `service_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert common service types
INSERT INTO `service_types` (name, description) VALUES
('DIY', 'Self-service option with access to document templates and guides'),
('Consultation', 'Professional advice and guidance with limited support'),
('Complete Process', 'Full end-to-end case management by a professional');

-- Service modes table (how the service can be delivered)
CREATE TABLE `service_modes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert service modes
INSERT INTO `service_modes` (name, description) VALUES
('Chat', 'Text-based messaging through the platform'),
('Video Call', 'Face-to-face video consultation'),
('Phone Call', 'Voice-only consultation'),
('Email', 'Communication via email'),
('Document Review', 'Professional review of submitted documents');

-- Service type modes - which modes are available for each service type
CREATE TABLE `service_type_modes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_type_id` int(11) NOT NULL,
  `service_mode_id` int(11) NOT NULL,
  `is_included` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_type_mode` (`service_type_id`,`service_mode_id`),
  KEY `service_mode_id` (`service_mode_id`),
  CONSTRAINT `service_type_modes_service_type_fk` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_type_modes_service_mode_fk` FOREIGN KEY (`service_mode_id`) REFERENCES `service_modes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Languages table
CREATE TABLE `languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` char(5) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professional languages table
CREATE TABLE `professional_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `proficiency_level` enum('basic','intermediate','fluent','native') DEFAULT 'intermediate',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_language` (`professional_id`,`language_id`),
  KEY `language_id` (`language_id`),
  CONSTRAINT `professional_languages_language_fk` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professional_languages_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Specializations table
CREATE TABLE `specializations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professional specializations table
CREATE TABLE `professional_specializations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_specialization` (`professional_id`,`specialization_id`),
  KEY `specialization_id` (`specialization_id`),
  CONSTRAINT `professional_specializations_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professional_specializations_specialization_fk` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Visa types table
CREATE TABLE `visa_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Case applications table
CREATE TABLE `case_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `professional_id` int(11) DEFAULT NULL,
  `visa_type_id` int(11) NOT NULL,
  `reference_number` varchar(30) NOT NULL,
  `status` enum('new','in_progress','pending_documents','review','approved','rejected') NOT NULL DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `service_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `client_id` (`client_id`),
  KEY `professional_id` (`professional_id`),
  KEY `visa_type_id` (`visa_type_id`),
  KEY `service_type_id` (`service_type_id`),
  KEY `idx_cases_status_professional` (`status`, `professional_id`),
  KEY `idx_cases_status_client` (`status`, `client_id`),
  CONSTRAINT `case_applications_client_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_applications_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `case_applications_visa_type_fk` FOREIGN KEY (`visa_type_id`) REFERENCES `visa_types` (`id`),
  CONSTRAINT `case_applications_service_type_fk` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- First, drop the existing case_notes table that has partitioning
DROP TABLE IF EXISTS `case_notes`;

-- Recreate the case_notes table without partitioning
CREATE TABLE `case_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('client','professional','admin') NOT NULL,
  `content` text NOT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_id` (`case_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_case_notes_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add the foreign key constraints separately
ALTER TABLE `case_notes` 
  ADD CONSTRAINT `case_notes_case_fk` FOREIGN KEY (`case_id`) REFERENCES `case_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_notes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

-- Document types table
CREATE TABLE `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- First, drop the existing documents table that has partitioning
DROP TABLE IF EXISTS `documents`;

-- Recreate the documents table without partitioning
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `professional_id` int(11) DEFAULT NULL,
  `document_type_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `file_path` varchar(100) NOT NULL COMMENT 'Store only filename, keep path in application config',
  `file_size` int(11) DEFAULT NULL COMMENT 'Size in bytes',
  `mime_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_id` (`case_id`),
  KEY `client_id` (`client_id`),
  KEY `professional_id` (`professional_id`),
  KEY `document_type_id` (`document_type_id`),
  KEY `idx_documents_uploaded` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add the foreign key constraints separately
ALTER TABLE `documents` 
  ADD CONSTRAINT `documents_case_fk` FOREIGN KEY (`case_id`) REFERENCES `case_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_client_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_document_type_fk` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  ADD CONSTRAINT `documents_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE SET NULL;

-- Consultant availability table - Updated
CREATE TABLE `consultant_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether the professional is available at all on this date',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_date` (`professional_id`,`date`),
  KEY `idx_availability_date` (`date`),
  CONSTRAINT `consultant_availability_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- First, drop the existing time_slots table 
DROP TABLE IF EXISTS `time_slots`;

-- Recreate the time_slots table with correct syntax
CREATE TABLE `time_slots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `availability_id` int(11) NOT NULL COMMENT 'Reference to consultant_availability',
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `service_mode_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_time_slot` (`professional_id`,`date`,`start_time`),
  KEY `idx_time_slots_date` (`date`),
  KEY `idx_time_slots_available` (`is_booked`,`date`),
  KEY `availability_id` (`availability_id`),
  KEY `service_mode_id` (`service_mode_id`),
  CONSTRAINT `time_slots_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `time_slots_availability_fk` FOREIGN KEY (`availability_id`) REFERENCES `consultant_availability` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_slots_service_mode_fk` FOREIGN KEY (`service_mode_id`) REFERENCES `service_modes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Consultation fees table
CREATE TABLE `consultation_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `consultation_type` enum('video','phone','inperson') NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_consultation_type` (`professional_id`,`consultation_type`),
  CONSTRAINT `consultation_fees_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bookings table
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `time_slot_id` int(11) NOT NULL,
  `service_type_id` int(11) NOT NULL,
  `service_mode_id` int(11) NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `price` decimal(10,2) NOT NULL,
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_id` (`professional_id`),
  KEY `client_id` (`client_id`),
  KEY `time_slot_id` (`time_slot_id`),
  KEY `service_type_id` (`service_type_id`),
  KEY `service_mode_id` (`service_mode_id`),
  KEY `idx_bookings_status_date` (`status`, `created_at`),
  CONSTRAINT `bookings_client_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_time_slot_fk` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_service_type_fk` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`),
  CONSTRAINT `bookings_service_mode_fk` FOREIGN KEY (`service_mode_id`) REFERENCES `service_modes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professional clients table
CREATE TABLE `professional_clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `status` enum('pending','active','completed','archived','rejected') NOT NULL DEFAULT 'pending',
  `initial_message` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_client` (`professional_id`,`client_id`),
  KEY `client_id` (`client_id`),
  KEY `idx_professional_client_status` (`status`),
  CONSTRAINT `professional_clients_client_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `professional_clients_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Reviews table
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `professional_id` (`professional_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_reviews_rating` (`rating`),
  CONSTRAINT `reviews_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notifications table
-- Notifications table without partitioning
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_notifications_read_created` (`user_id`, `is_read`, `created_at`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- System settings table
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Activity log table
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_activity_entity` (`entity_type`, `entity_id`),
  KEY `idx_activity_created` (`created_at`),
  CONSTRAINT `activity_logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Add triggers for booking consistency

-- Chat conversations table
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `professional_id` int(11) NOT NULL,
  `case_id` int(11) DEFAULT NULL,
  `service_type_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_professional_case` (`client_id`,`professional_id`,`case_id`),
  KEY `case_id` (`case_id`),
  KEY `service_type_id` (`service_type_id`),
  CONSTRAINT `conversations_client_fk` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_case_fk` FOREIGN KEY (`case_id`) REFERENCES `case_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_service_type_fk` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Chat messages table
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `service_mode_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`),
  KEY `service_mode_id` (`service_mode_id`),
  KEY `idx_messages_created` (`created_at`),
  KEY `idx_messages_read` (`is_read`),
  CONSTRAINT `chat_messages_conversation_fk` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_service_mode_fk` FOREIGN KEY (`service_mode_id`) REFERENCES `service_modes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- For message attachments (optional)
CREATE TABLE `chat_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `chat_attachments_message_fk` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- Professional service offerings table with pricing control
CREATE TABLE `professional_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `service_type_id` int(11) NOT NULL,
  `is_offered` tinyint(1) NOT NULL DEFAULT 1,
  `custom_price` decimal(10,2) NOT NULL COMMENT 'Price set by the professional for this service',
  `service_description` text DEFAULT NULL COMMENT 'Professional custom description of their service',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_service` (`professional_id`,`service_type_id`),
  KEY `service_type_id` (`service_type_id`),
  CONSTRAINT `professional_services_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `professionals` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `professional_services_service_type_fk` FOREIGN KEY (`service_type_id`) REFERENCES `service_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Professional service mode pricing (optional - for different pricing per mode)
CREATE TABLE `professional_service_mode_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_service_id` int(11) NOT NULL,
  `service_mode_id` int(11) NOT NULL,
  `is_offered` tinyint(1) NOT NULL DEFAULT 1,
  `additional_fee` decimal(10,2) DEFAULT 0.00 COMMENT 'Additional fee for this specific mode',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `prof_service_mode` (`professional_service_id`,`service_mode_id`),
  KEY `service_mode_id` (`service_mode_id`),
  CONSTRAINT `prof_service_mode_pricing_prof_service_fk` FOREIGN KEY (`professional_service_id`) REFERENCES `professional_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prof_service_mode_pricing_service_mode_fk` FOREIGN KEY (`service_mode_id`) REFERENCES `service_modes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Create view for recent conversations
DELIMITER //

-- Trigger to mark time slot as booked when a booking is created
CREATE TRIGGER after_booking_insert
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
  UPDATE time_slots
  SET is_booked = 1
  WHERE id = NEW.time_slot_id;
END;
//

-- Trigger to handle booking cancellations
CREATE TRIGGER after_booking_update
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
  IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
    UPDATE time_slots
    SET is_booked = 0
    WHERE id = NEW.time_slot_id;
  END IF;
END;
//

-- Trigger to prevent booking if the time slot is already booked
CREATE TRIGGER before_booking_insert
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
  DECLARE is_slot_booked BOOLEAN;
  DECLARE slot_mode_id INT;
  DECLARE is_mode_offered BOOLEAN;
  
  -- Check if time slot is already booked
  SELECT is_booked, service_mode_id INTO is_slot_booked, slot_mode_id 
  FROM time_slots WHERE id = NEW.time_slot_id;
  
  IF is_slot_booked THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'This time slot is already booked';
  END IF;
  
  -- Check if the requested service mode matches the time slot's service mode
  IF NEW.service_mode_id != slot_mode_id THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'The requested service mode does not match this time slot';
  END IF;
  
  -- Check if the professional offers this service type and mode
  SELECT COUNT(*) > 0 INTO is_mode_offered
  FROM professional_services ps
  JOIN professional_service_mode_pricing psmp ON ps.id = psmp.professional_service_id
  WHERE ps.professional_id = NEW.professional_id
  AND ps.service_type_id = NEW.service_type_id
  AND psmp.service_mode_id = NEW.service_mode_id
  AND ps.is_offered = 1
  AND psmp.is_offered = 1;
  
  IF NOT is_mode_offered THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'The professional does not offer this service type or mode';
  END IF;
END;
//

-- Trigger to ensure time slots are only created for available days
CREATE TRIGGER before_time_slot_insert
BEFORE INSERT ON time_slots
FOR EACH ROW
BEGIN
  DECLARE is_day_available BOOLEAN;
  
  -- Check if the professional is available on this day
  SELECT is_available INTO is_day_available 
  FROM consultant_availability 
  WHERE id = NEW.availability_id AND professional_id = NEW.professional_id;
  
  IF NOT is_day_available THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'The professional is not available on this date';
  END IF;
END;
//

DELIMITER ;

-- Create stored procedure to generate time slots when a consultant marks a day as available
DROP PROCEDURE IF EXISTS generate_time_slots;
DELIMITER //

CREATE PROCEDURE generate_time_slots(
  IN p_professional_id INT,
  IN p_date DATE,
  IN p_slot_duration INT, -- in minutes
  IN p_service_mode_id INT -- service mode for these slots
)
BEGIN
  DECLARE v_start_time TIME DEFAULT '09:00:00'; -- Default start time 9 AM
  DECLARE v_end_time TIME DEFAULT '17:00:00';   -- Default end time 5 PM
  DECLARE v_current_time TIME;
  DECLARE v_slot_end_time TIME;
  DECLARE v_availability_id INT;
  
  -- Get or create availability record for this date
  SELECT id INTO v_availability_id FROM consultant_availability
  WHERE professional_id = p_professional_id AND date = p_date;
  
  IF v_availability_id IS NULL THEN
    INSERT INTO consultant_availability (professional_id, date, is_available)
    VALUES (p_professional_id, p_date, 1);
    
    SET v_availability_id = LAST_INSERT_ID();
  END IF;
  
  -- Generate slots
  SET v_current_time = v_start_time;
  
  WHILE v_current_time < v_end_time DO
    -- Calculate slot end time
    SET v_slot_end_time = ADDTIME(v_current_time, SEC_TO_TIME(p_slot_duration * 60));
    
    -- Insert the time slot with service mode
    INSERT INTO time_slots (
      professional_id, 
      availability_id, 
      date, 
      start_time, 
      end_time,
      service_mode_id
    ) VALUES (
      p_professional_id, 
      v_availability_id, 
      p_date, 
      v_current_time, 
      v_slot_end_time,
      p_service_mode_id
    )
    ON DUPLICATE KEY UPDATE 
      service_mode_id = p_service_mode_id;
    
    -- Move to next slot
    SET v_current_time = v_slot_end_time;
  END WHILE;
END;
//


DELIMITER ;

-- Replace the view with a stored procedure
DROP VIEW IF EXISTS recent_conversations;

DELIMITER //

CREATE PROCEDURE get_recent_conversations(IN p_current_user_id INT)
BEGIN
    SELECT 
      c.id AS conversation_id,
      c.client_id,
      c.professional_id,
      u1.name AS client_name,
      u2.name AS professional_name,
      (SELECT COUNT(*) FROM chat_messages 
       WHERE conversation_id = c.id AND is_read = 0 AND sender_id != p_current_user_id) AS unread_count,
      (SELECT created_at FROM chat_messages 
       WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_time,
      (SELECT content FROM chat_messages 
       WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message
    FROM conversations c
    JOIN users u1 ON c.client_id = u1.id
    JOIN professionals p ON c.professional_id = p.user_id
    JOIN users u2 ON p.user_id = u2.id
    WHERE c.client_id = p_current_user_id OR c.professional_id = p_current_user_id
    ORDER BY last_message_time DESC;
END//

DELIMITER ;

-- Create a view to show available time slots for booking
CREATE VIEW available_time_slots AS
SELECT 
  ts.id,
  ts.professional_id,
  p.user_id,
  u.name AS professional_name,
  ts.date,
  ts.start_time,
  ts.end_time,
  sm.id AS service_mode_id,
  sm.name AS service_mode_name,
  ps.service_type_id,
  st.name AS service_type_name,
  ps.custom_price AS base_price,
  psmp.additional_fee,
  (ps.custom_price + COALESCE(psmp.additional_fee, 0)) AS total_price
FROM 
  time_slots ts
JOIN professionals p ON ts.professional_id = p.user_id
JOIN users u ON p.user_id = u.id
JOIN consultant_availability ca ON ts.availability_id = ca.id
JOIN service_modes sm ON ts.service_mode_id = sm.id
JOIN professional_services ps ON p.user_id = ps.professional_id
JOIN service_types st ON ps.service_type_id = st.id
LEFT JOIN professional_service_mode_pricing psmp ON ps.id = psmp.professional_service_id AND sm.id = psmp.service_mode_id
WHERE 
  ts.is_booked = 0
  AND ca.is_available = 1
  AND ts.date >= CURDATE()
  AND u.status = 'active'
  AND p.verification_status = 'verified'
  AND p.availability_status = 'available'
  AND u.deleted_at IS NULL
  AND ps.is_offered = 1
  AND COALESCE(psmp.is_offered, 1) = 1;

-- Create view for professional available services with pricing
CREATE VIEW professional_available_services AS
SELECT 
  ps.professional_id,
  u.name AS professional_name,
  st.id AS service_type_id,
  st.name AS service_type,
  ps.custom_price,
  ps.service_description,
  sm.id AS mode_id,
  sm.name AS service_mode,
  stm.is_included,
  COALESCE(psmp.additional_fee, 0) AS additional_fee,
  COALESCE(ps.custom_price + psmp.additional_fee, ps.custom_price) AS total_price,
  COALESCE(psmp.is_offered, 1) AS mode_is_offered
FROM professional_services ps
JOIN service_types st ON ps.service_type_id = st.id
JOIN professionals p ON ps.professional_id = p.user_id
JOIN users u ON p.user_id = u.id
JOIN service_type_modes stm ON st.id = stm.service_type_id
JOIN service_modes sm ON stm.service_mode_id = sm.id
LEFT JOIN professional_service_mode_pricing psmp ON ps.id = psmp.professional_service_id AND sm.id = psmp.service_mode_id
WHERE 
  ps.is_offered = 1
  AND st.is_active = 1
  AND sm.is_active = 1
  AND p.verification_status = 'verified'
  AND u.status = 'active';

-- Replace the second view with a stored procedure
DROP PROCEDURE IF EXISTS get_recent_conversations_with_case;

DELIMITER //

CREATE PROCEDURE get_recent_conversations_with_case(IN p_current_user_id INT)
BEGIN
    SELECT 
      c.id AS conversation_id,
      c.client_id,
      c.professional_id,
      c.case_id,
      c.service_type_id,
      st.name AS service_type_name,
      u1.name AS client_name,
      u2.name AS professional_name,
      ca.reference_number AS case_reference,
      (SELECT COUNT(*) FROM chat_messages 
       WHERE conversation_id = c.id AND is_read = 0 AND sender_id != p_current_user_id) AS unread_count,
      (SELECT created_at FROM chat_messages 
       WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message_time,
      (SELECT content FROM chat_messages 
       WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message
    FROM conversations c
    JOIN users u1 ON c.client_id = u1.id
    JOIN professionals p ON c.professional_id = p.user_id
    JOIN users u2 ON p.user_id = u2.id
    LEFT JOIN case_applications ca ON c.case_id = ca.id
    LEFT JOIN service_types st ON c.service_type_id = st.id
    WHERE c.client_id = p_current_user_id OR c.professional_id = p_current_user_id
    ORDER BY last_message_time DESC;
END//

DELIMITER ;

COMMIT;