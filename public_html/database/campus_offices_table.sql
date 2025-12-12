-- Campus Offices Table
-- Stores campus office information for the Campus Offices page

CREATE TABLE IF NOT EXISTS `campus_offices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(255) NOT NULL,
  `tag` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `hours` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `display_order` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category_order` (`category`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default offices
INSERT INTO `campus_offices` (`category`, `tag`, `name`, `description`, `location`, `hours`, `image`, `display_order`) VALUES
('Admissions & Registrar', 'Admissions', 'Admission & Registrar Office', 'Handles PUPCET and admission, enrollment processing, subject loading, corrections, student records, COR, Form 137/138, TOR, and certifications used for employment or further studies.', 'Ground Floor, Main Building', 'Monday - Friday: 8:00 AM - 5:00 PM', '../images/offices.jpg', 1),
('Scholarships & Financial Aid', 'Financial Aid', 'Scholarships & Financial Assistance', 'University and external scholarships, LGU and partner grants, allowance programs, and TES or other government-funded financial assistance for qualified students.', '2nd Floor, Main Building', 'Monday - Friday: 8:00 AM - 5:00 PM', '../images/offices.jpg', 2),
('Guidance & Counseling', 'Support', 'Guidance & Counseling Office', 'Individual and group counseling, referrals, crisis support, and career guidance, as well as wellness and formation activities that promote holistic student development.', '2nd Floor, Main Building', 'Monday - Friday: 8:00 AM - 5:00 PM', '../images/offices.jpg', 3),
('Library & Learning', 'Resources', 'Library & Learning Resources', 'Access to books, journals, online databases, and other learning materials, plus research assistance and study spaces that support coursework and thesis writing.', '3rd Floor, Main Building', 'Monday - Friday: 7:00 AM - 7:00 PM<br>Saturday: 8:00 AM - 5:00 PM', '../images/library.jpg', 4),
('Student Affairs & Services', 'Student Life', 'Student Affairs & Services', 'Recognized student organizations, councils, and campus activities, and helps implement orientation programs, formation activities, discipline, and student leadership development.', '2nd Floor, Main Building', 'Monday - Friday: 8:00 AM - 5:00 PM', '../images/organizationjpg.jpg', 5),
('IT & Support', 'Technology', 'IT Services', 'Student accounts and access to PUP SIS, campus portals, official email, and on-campus Wi-Fi, and provides basic technical troubleshooting for students and offices.', 'Ground Floor, Main Building', 'Monday - Friday: 8:00 AM - 5:00 PM', '../images/offices.jpg', 6)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`);

