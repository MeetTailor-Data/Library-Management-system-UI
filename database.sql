-- ==========================================================
-- Smart Library Management System (SLMS) - Database Schema
-- Database: MySQL / MariaDB (Compatible with SQLite/PostgreSQL)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `library_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `library_db`;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    `created_at` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `books`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `books` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `author` VARCHAR(150) NOT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'General',
    `isbn` VARCHAR(50) NOT NULL UNIQUE,
    `total_copies` INT NOT NULL DEFAULT 1,
    `available_copies` INT NOT NULL DEFAULT 1,
    `image` VARCHAR(255) DEFAULT 'b1.jpg',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `issued_books` (Circulation)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `issued_books` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(50) NOT NULL,
    `student_name` VARCHAR(100) NOT NULL,
    `book_id` INT NOT NULL,
    `book_title` VARCHAR(255) NOT NULL,
    `isbn` VARCHAR(50) NOT NULL,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `return_date` DATE DEFAULT NULL,
    `status` ENUM('Issued', 'Overdue', 'Returned') NOT NULL DEFAULT 'Issued',
    `fine` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_book` (`book_id`),
    CONSTRAINT `fk_issued_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for `messages` (Contact Form)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('Unread', 'Replied') NOT NULL DEFAULT 'Unread',
    `created_at` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SEED DATA
-- ==========================================================

-- Insert Users (Default Admin & Students)
INSERT INTO `users` (`id`, `user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'ADMIN01', 'System Librarian', 'admin@library.com', 'admin123', 'admin', '2026-01-01'),
(2, 'STU101', 'John Doe', 'john@example.com', 'student123', 'student', '2026-02-15'),
(3, 'STU102', 'Emily Clark', 'emily@example.com', 'student123', 'student', '2026-03-10'),
(4, 'STU103', 'David Miller', 'david@example.com', 'student123', 'student', '2026-04-05')
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

-- Insert Initial Book Catalog
INSERT INTO `books` (`id`, `title`, `author`, `category`, `isbn`, `total_copies`, `available_copies`, `image`, `description`) VALUES
(1, 'The Wealth of Nations', 'Adam Smith', 'Economics', '978-0140432084', 5, 4, 'b9.jpg', 'A foundational work of classical economics exploring division of labor, productivity, and free markets.'),
(2, 'The World as I See It', 'Albert Einstein', 'Science', '978-0806527901', 4, 4, 'b10.jpg', 'Albert Einstein\'s personal reflections on physics, humanity, peace, and the universe.'),
(3, 'Computer Networks & Systems', 'Andrew S. Tanenbaum', 'Technology', '978-0132126953', 4, 3, 'b1.jpg', 'Complete guide to modern networking principles, architecture, and protocols.'),
(4, 'Clean Architecture & Code', 'Robert C. Martin', 'Technology', '978-0134494166', 3, 2, 'b2.jpg', 'A craftsman\'s guide to software structure, design patterns, and clean programming principles.'),
(5, 'Brief History of Time', 'Stephen Hawking', 'Science', '978-0553380163', 6, 6, 'b3.jpg', 'An iconic exploration of space, black holes, time, and the origins of the cosmos.'),
(6, 'To Kill a Mockingbird', 'Harper Lee', 'Literature', '978-0061120084', 5, 5, 'b4.jpg', 'Classic literary masterpiece exploring justice, empathy, and integrity in American history.'),
(7, 'Sapiens: A Brief History', 'Yuval Noah Harari', 'History', '978-0062316097', 4, 4, 'b5.jpg', 'How humankind evolved from primitive foragers to masters of Planet Earth.'),
(8, 'Principles of Economics', 'N. Gregory Mankiw', 'Economics', '978-1305585126', 3, 2, 'b6.jpg', 'Standard textbook on micro and macroeconomic principles and fiscal policies.'),
(9, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Literature', '978-0743273565', 4, 4, 'b7.jpg', 'A portrait of the Jazz Age, wealth, disillusionment, and the American dream.'),
(10, 'Ancient World Civilizations', 'Peter Heather', 'History', '978-0195155846', 4, 4, 'b8.jpg', 'Detailed study of early civilizations, empire building, and societal evolutions.')
ON DUPLICATE KEY UPDATE `isbn` = VALUES(`isbn`);

-- Insert Issued Books Records
INSERT INTO `issued_books` (`id`, `user_id`, `student_name`, `book_id`, `book_title`, `isbn`, `issue_date`, `due_date`, `return_date`, `status`, `fine`) VALUES
(1, 'STU101', 'John Doe', 1, 'The Wealth of Nations', '978-0140432084', '2026-09-18', '2026-10-02', NULL, 'Issued', 0.00),
(2, 'STU102', 'Emily Clark', 4, 'Clean Architecture & Code', '978-0134494166', '2026-09-10', '2026-09-24', NULL, 'Overdue', 40.00),
(3, 'STU103', 'David Miller', 7, 'Sapiens: A Brief History', '978-0062316097', '2026-08-01', '2026-08-15', '2026-08-14', 'Returned', 0.00)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- Insert Contact Inquiries
INSERT INTO `messages` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Rahul Sharma', 'rahul@gmail.com', 'Book Request: Data Structures', 'Can you please add more copies of Data Structures by Cormen in the library catalog?', 'Unread', '2026-09-28'),
(2, 'Ananya Patel', 'ananya@gmail.com', 'Membership Renewal', 'Hi, I want to know how I can renew my semester membership card online.', 'Replied', '2026-09-30')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);
