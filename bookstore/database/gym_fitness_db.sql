-- =====================================================================
-- Gym & Fitness Center Website - Database Schema & Seed Data
-- Subject: Web Programming (Lab 2 - Tasks 1 & 2)
-- Database: gym_fitness_db
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `gym_fitness_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `gym_fitness_db`;

-- ---------------------------------------------------------------------
-- Table 1: categories (Supporting table for classification)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `fitness_classes`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

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
-- Table 3: fitness_classes (Main business entity)
-- ---------------------------------------------------------------------
CREATE TABLE `fitness_classes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `trainer_name` VARCHAR(100) NOT NULL,
  `schedule_info` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image_url` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_class_category`
    FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`)
    ON DELETE CASCADE
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
(3, 'Alex Nguyen', 'member@gymfitness.local', '$2y$10$tcV8g7eQyxgF1jf.HqGSNutx5QKt7GMD0cjk4jT1FQgAYu4hQNBaO', 'member', NOW());

-- Fitness Classes Seed Data (6 classes)
INSERT INTO `fitness_classes` (`id`, `category_id`, `title`, `description`, `trainer_name`, `schedule_info`, `price`, `image_url`, `created_at`) VALUES
(1, 1, 'Power Barbell Mastery', 'Master the foundational compound lifts: Squat, Bench Press, and Deadlift. Includes video movement analysis, progressive overload planning, and structural joint resilience.', 'Marcus Vance', 'Mon, Wed, Fri • 06:00 AM - 07:30 AM', 35.00, 'images/item1.jpg', NOW()),
(2, 2, 'HIIT Metabolic Inferno', 'A high-octane interval workout designed to incinerate calories and expand your lung capacity using assault bikes, kettlebells, and rowing sprints.', 'Elena Rostova', 'Tue, Thu, Sat • 05:30 PM - 06:30 PM', 25.00, 'images/item2.jpg', NOW()),
(3, 1, 'Olympic Weightlifting & Explosive Power', 'Dedicated technical coaching in the Snatch and Clean & Jerk. Cultivate lightning-fast bar speed, triple extension force, and overhead stability.', 'Marcus Vance', 'Mon, Wed • 07:00 PM - 08:30 PM', 40.00, 'images/item3.jpg', NOW()),
(4, 2, 'Athletic Stamina & Endurance Circuit', 'Hybrid conditioning fusing sled pushes, battle ropes, ski-ergs, and lactate-threshold intervals. Built for members craving unbreakable stamina.', 'Elena Rostova', 'Wed, Fri, Sun • 08:00 AM - 09:15 AM', 28.00, 'images/item4.jpg', NOW()),
(5, 3, 'Dynamic Yoga & Core Flow', 'A dynamic vinyasa flow prioritizing active flexibility, hip capsule opening, and deep transverse abdominis engagement for postural health.', 'Elena Rostova', 'Tue, Thu, Sun • 07:00 AM - 08:00 AM', 22.00, 'images/item5.jpg', NOW()),
(6, 4, 'Boxing Fundamentals & Conditioning', 'Authentic boxing footwork, combination punching on heavy bags, slip line drills, and explosive 3-minute round conditioning.', 'Marcus Vance', 'Mon, Thu • 06:30 PM - 08:00 PM', 30.00, 'images/item6.jpg', NOW());
