-- =====================================================================
-- TIGER FITNESS (Gym & Fitness Center Website) - Database Schema & Seed Data
-- Subject: Web Programming (Labs 2 & 3 - Personal Topic T1)
-- Database: gym_fitness_db
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `gym_fitness_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `gym_fitness_db`;

-- Drop child tables first to satisfy foreign key dependencies cleanly
DROP TABLE IF EXISTS `class_bookings`;
DROP TABLE IF EXISTS `user_memberships`;
DROP TABLE IF EXISTS `membership_plans`;
DROP TABLE IF EXISTS `fitness_classes`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- ---------------------------------------------------------------------
-- Table 1: categories (Supporting table for class classification)
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 2: users (Authentication and Role-Based Access Control)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'trainer', 'member') NOT NULL DEFAULT 'member',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 3: fitness_classes (Main business entity with max_capacity)
-- ---------------------------------------------------------------------
CREATE TABLE `fitness_classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `trainer_name` VARCHAR(100) NOT NULL,
  `schedule_info` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_capacity` INT NOT NULL DEFAULT 15,
  `image_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_class_category`
    FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 4: membership_plans (Lab 3 T1 Workflow Step 1: Membership Tiers)
-- ---------------------------------------------------------------------
CREATE TABLE `membership_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `tier_code` VARCHAR(30) NOT NULL UNIQUE,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `duration_days` INT NOT NULL DEFAULT 30,
  `max_active_bookings` INT NOT NULL DEFAULT 5,
  `description` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 5: user_memberships (Active/Expired Member Subscriptions)
-- ---------------------------------------------------------------------
CREATE TABLE `user_memberships` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `plan_id` INT NOT NULL,
  `status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_membership_status` (`user_id`, `status`, `end_date`),
  CONSTRAINT `fk_membership_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_membership_plan`
    FOREIGN KEY (`plan_id`)
    REFERENCES `membership_plans` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table 6: class_bookings (Lab 3 T1 Workflow: Membership -> Class -> Schedule -> Booking)
-- ---------------------------------------------------------------------
CREATE TABLE `class_bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `membership_id` INT NULL,
  `booking_date` DATE NOT NULL,
  `status` ENUM('confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'confirmed',
  `notes` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_class_date_status` (`class_id`, `booking_date`, `status`),
  INDEX `idx_user_booking_status` (`user_id`, `status`, `booking_date`),
  CONSTRAINT `fk_booking_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_class`
    FOREIGN KEY (`class_id`)
    REFERENCES `fitness_classes` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_membership`
    FOREIGN KEY (`membership_id`)
    REFERENCES `user_memberships` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- SEED DATA
-- =====================================================================

-- Categories Seed Data (4 categories)
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Strength & Hypertrophy', 'Progressive resistance training targeting muscular hypertrophy, strength, and barbell biomechanics.'),
(2, 'Cardio & Conditioning', 'High-intensity interval training, metabolic conditioning, and endurance circuits to boost VO2 max.'),
(3, 'Mobility & Core', 'Functional flexibility, spinal decompression, active mobility flows, and core stabilization.'),
(4, 'Combat & Martial Arts', 'Pugilistic fundamentals, heavy bag conditioning, and tactical combat athletic training.');

-- Users Seed Data (Default password for all accounts is: Password123!)
-- Bcrypt hash generated via password_hash('Password123!', PASSWORD_BCRYPT)
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Marcus Vance', 'admin@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'admin', NOW()),
(2, 'Elena Rostova', 'trainer@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'trainer', NOW()),
(3, 'Alex Nguyen', 'member@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'member', NOW()),
(4, 'Linh Tran', 'member2@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'member', NOW()),
(5, 'David Pham', 'member3@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'member', NOW()),
(6, 'Sofia Le', 'member4@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'member', NOW());

-- Fitness Classes Seed Data (6 classes with realistic capacities)
INSERT INTO `fitness_classes` (`id`, `category_id`, `title`, `description`, `trainer_name`, `schedule_info`, `price`, `max_capacity`, `image_url`, `created_at`) VALUES
(1, 1, 'Power Barbell Mastery', 'Master the foundational compound lifts: Squat, Bench Press, and Deadlift. Includes video movement analysis, progressive overload planning, and structural joint resilience.', 'Marcus Vance', 'Mon, Wed, Fri • 06:00 AM - 07:30 AM', 35.00, 12, 'images/item1.jpg', NOW()),
(2, 2, 'HIIT Metabolic Inferno', 'A high-octane interval workout designed to incinerate calories and expand your lung capacity using assault bikes, kettlebells, and rowing sprints.', 'Elena Rostova', 'Tue, Thu, Sat • 05:30 PM - 06:30 PM', 25.00, 16, 'images/item2.jpg', NOW()),
(3, 1, 'Olympic Weightlifting & Explosive Power', 'Dedicated technical coaching in the Snatch and Clean & Jerk. Cultivate lightning-fast bar speed, triple extension force, and overhead stability.', 'Marcus Vance', 'Mon, Wed • 07:00 PM - 08:30 PM', 40.00, 5, 'images/item3.jpg', NOW()),
(4, 2, 'Athletic Stamina & Endurance Circuit', 'Hybrid conditioning fusing sled pushes, battle ropes, ski-ergs, and lactate-threshold intervals. Built for members craving unbreakable stamina.', 'Elena Rostova', 'Wed, Fri, Sun • 08:00 AM - 09:15 AM', 28.00, 14, 'images/item4.jpg', NOW()),
(5, 3, 'Dynamic Yoga & Core Flow', 'A dynamic vinyasa flow prioritizing active flexibility, hip capsule opening, and deep transverse abdominis engagement for postural health.', 'Elena Rostova', 'Tue, Thu, Sun • 07:00 AM - 08:00 AM', 22.00, 20, 'images/item5.jpg', NOW()),
(6, 4, 'Boxing Fundamentals & Conditioning', 'Authentic boxing footwork, combination punching on heavy bags, slip line drills, and explosive 3-minute round conditioning.', 'Marcus Vance', 'Mon, Thu • 06:30 PM - 08:00 PM', 30.00, 10, 'images/item6.jpg', NOW());

-- Membership Plans Seed Data (3 tiers)
INSERT INTO `membership_plans` (`id`, `name`, `tier_code`, `price`, `duration_days`, `max_active_bookings`, `description`) VALUES
(1, 'Starter Drop-In Pass', 'STARTER', 29.00, 14, 2, 'Ideal for casual athletes. Includes access to open gym zones and up to 2 simultaneous class reservations.'),
(2, 'Tiger Pro Monthly', 'PRO', 79.00, 30, 5, 'Our signature performance membership supporting up to 5 concurrent scheduled class bookings and recovery lounge access.'),
(3, 'Apex VIP Unlimited', 'VIP', 149.00, 90, 15, 'All-access quarterly pass with priority barbell platforms, 15 concurrent class bookings, and biometric tracking.');

-- User Memberships Seed Data (Active plans for seeded users)
INSERT INTO `user_memberships` (`id`, `user_id`, `plan_id`, `status`, `start_date`, `end_date`, `created_at`) VALUES
(1, 1, 3, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), NOW()),
(2, 2, 2, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW()),
(3, 3, 2, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW()),
(4, 4, 2, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW()),
(5, 5, 1, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), NOW()),
(6, 6, 2, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW());

-- Class Bookings Seed Data
-- Note: Class 3 (max_capacity = 5) has 4/5 confirmed spots tomorrow, leaving 1 spot to demonstrate live capacity enforcement!
INSERT INTO `class_bookings` (`id`, `user_id`, `class_id`, `membership_id`, `booking_date`, `status`, `notes`, `created_at`) VALUES
(1, 3, 1, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'completed', 'Completed baseline squat assessment.', NOW()),
(2, 3, 2, 3, CURDATE(), 'confirmed', 'Focus on aerobic threshold pacing.', NOW()),
(3, 4, 1, 4, CURDATE(), 'confirmed', 'Working on deadlift lockout technique.', NOW()),
(4, 5, 6, 5, CURDATE(), 'confirmed', 'Bringing personal hand wraps.', NOW()),
(5, 1, 3, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed', 'Clean & Jerk complex.', NOW()),
(6, 2, 3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed', 'Snatch overhead stability work.', NOW()),
(7, 4, 3, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed', 'Olympic lifting form check.', NOW()),
(8, 5, 3, 5, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed', 'First Olympic lifting clinic.', NOW()),
(9, 4, 1, 4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed', 'Morning barbell session.', NOW()),
(10, 6, 5, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed', 'Hip mobility & core session.', NOW());
