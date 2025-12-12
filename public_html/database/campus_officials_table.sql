-- Campus Officials Table
-- Stores both Branch Officials and Support Personnel

CREATE TABLE IF NOT EXISTS `campus_officials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `role` VARCHAR(255) NOT NULL,
  `photo_path` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('branch_official', 'support_personnel') NOT NULL DEFAULT 'branch_official',
  `display_order` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_order` (`type`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default Branch Officials
INSERT INTO `campus_officials` (`name`, `role`, `photo_path`, `type`, `display_order`) VALUES
('Margarita T. Sevilla, Ph. D.', 'Campus Director', 'images/Sevilla, Margarita.jpg', 'branch_official', 1),
('Archie C. Arevalo, LPT, MA', 'Head of Academic Programs', 'images/AREVALO, ARCHIE.jpg', 'branch_official', 2),
('Cheryl Joyce D. Jurado, LPT, MEM', 'Head of Student Affairs and Services', 'images/JURADO, CHERYL JOYCE D..jpg', 'branch_official', 3),
('Ma. Gemalyn S. Austria, MEM', 'Head of Admission and Registration', 'images/Maam Gem.png', 'branch_official', 4),
('Manalo David B. Rivera', 'Collecting and Disturbing Officer', 'images/RIVERA, MANOLO DAVID.jpg', 'branch_official', 5),
('Genino P. Abelida, Jr., LPT', 'Administrative Officer', 'images/Sir Abelida.png', 'branch_official', 6),
('Rhod Phillip Corro, LPT, MBA', 'Research and Extension Coordinator', 'images/Sir Corro.png', 'branch_official', 7);

-- Insert default Support Personnel
INSERT INTO `campus_officials` (`name`, `role`, `photo_path`, `type`, `display_order`) VALUES
('Jerwin A. Bismar', 'Guidance Advocate', 'images/Sir Jerwin.png', 'support_personnel', 1),
('Francheska Louise M. Bernardo, RL, MUS', 'Campus Librarian', 'images/BERNARDO_FRANCHESCA LOUISE-PUP FLAG.JPG', 'support_personnel', 2),
('Noemi Apostol', 'Sports Coordinator', 'images/Maam Apostol.png', 'support_personnel', 3),
('Widonna B. Cuenca', 'Admission and Registration Staff', 'images/CUENCA, WIDONNA.jpg', 'support_personnel', 4),
('Engr. Jhun Jhun B. Maravilla', 'IT Coordinator/ Laboratory Technician', 'images/MARAVILLA, JHUN JHUN B..jpg', 'support_personnel', 5),
('Engr. Aaron A. Atienza', 'Student Records Officer/NSTP Coordinator', 'images/ATIENZA, AARON A..jpg', 'support_personnel', 6),
('Nestleson H. Alagon', 'Administrative Aide', 'images/Sir Alagon.png', 'support_personnel', 7),
('Mary Jane G. Malonzo, LPT', 'Admission and Registration Staff', 'images/MALONZO, JANE.jpg', 'support_personnel', 8),
('Kaira Marie D. Formento, RL, MUS', 'Campus Librarian', 'images/MAAM KAI.jpeg', 'support_personnel', 9),
('Rochelle Anne Masangkay, RN', 'Nurse', 'images/MASANGKAY, ROCHELL.jpg', 'support_personnel', 10),
('Paul Vincent A. Vierneza, RN', 'Nurse', 'images/PUPLogo.png', 'support_personnel', 11),
('Romina Concepcion', 'Nursing Aide', 'images/ROMINA.jpg', 'support_personnel', 12);

