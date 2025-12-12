-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 21, 2025 at 06:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pupbcadmin1`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `created_at`, `last_login`) VALUES
(3, 'admin', '$2y$10$cXXV17jMeClXArcfcsskcuPyg1f1ftSkV0mapdJefjE8F5CAhLxVO', 'Site Administrator', 'admin@example.com', '2025-10-13 06:23:20', '2025-10-21 04:39:59');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `cta_label` varchar(100) DEFAULT NULL,
  `cta_url` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `category`, `publish_date`, `cta_label`, `cta_url`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Enrollment for 1st Semester AY 2025-2026', 'Enrollment for the upcoming semester will run from July 15 to August 2. Please prepare all required documents before visiting the registrar.', 'Registrar', '2025-07-01', 'View requirements', '#', 1, NULL, '2025-10-12 06:08:00', '2025-10-12 06:08:00'),
(2, 'Scholarship Applications Open', 'Students are invited to submit their scholarship applications through the Office of Student Affairs beginning June 20.', 'Student Affairs', '2025-06-20', 'Apply now', '#', 1, NULL, '2025-10-12 06:08:00', '2025-10-12 06:08:00'),
(3, 'Campus ID Validation Schedule', 'Campus ID validation will take place at the Student Center every Tuesday and Thursday, 9 AM to 4 PM.', 'Administration', '2025-06-15', 'View schedule', '#', 1, NULL, '2025-10-12 06:08:00', '2025-10-12 06:08:00'),
(4, 'Enrollment for 1st Semester AY 2025-2026', 'Enrollment for the upcoming semester will run from July 15 to August 2. Please prepare all required documents before visiting the registrar.', 'Registrar', '2025-07-01', 'View requirements', '#', 1, NULL, '2025-10-12 06:13:35', '2025-10-12 06:13:35'),
(5, 'Scholarship Applications Open', 'Students are invited to submit their scholarship applications through the Office of Student Affairs beginning June 20.', 'Student Affairs', '2025-06-20', 'Apply now', '#', 1, NULL, '2025-10-12 06:13:35', '2025-10-12 06:13:35'),
(6, 'Campus ID Validation Schedule', 'Campus ID validation will take place at the Student Center every Tuesday and Thursday, 9 AM to 4 PM.', 'Administration', '2025-06-15', 'View schedule', '#', 1, NULL, '2025-10-12 06:13:35', '2025-10-12 06:13:35'),
(7, 'I HAVE A NEWS', 'THE MAN IS BEHIND THE BACL', 'THE BIG NEWS', '2025-10-16', '', '', 1, NULL, '2025-10-12 06:29:27', '2025-10-12 06:29:27');

-- --------------------------------------------------------

--
-- Table structure for table `media_library`
--

CREATE TABLE `media_library` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `media_type` enum('image','video') NOT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `body` longtext DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_visits`
--

CREATE TABLE `page_visits` (
  `id` int(11) NOT NULL,
  `page_name` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `visit_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_visits`
--

INSERT INTO `page_visits` (`id`, `page_name`, `ip_address`, `visit_date`, `visit_time`) VALUES
(1, 'about.php', '::1', '2025-10-15', '2025-10-15 06:19:11'),
(2, 'faq.php', '::1', '2025-10-15', '2025-10-15 07:53:04'),
(3, 'programs.php', '::1', '2025-10-15', '2025-10-15 07:53:12'),
(4, 'admission_guide.php', '::1', '2025-10-15', '2025-10-15 07:53:15'),
(5, 'services.php', '::1', '2025-10-15', '2025-10-15 07:53:19'),
(6, 'campuslife.php', '::1', '2025-10-15', '2025-10-15 07:53:22'),
(7, 'contact.php', '::1', '2025-10-15', '2025-10-15 07:53:25'),
(8, 'faq.php', '192.168.1.2', '2025-10-15', '2025-10-15 08:28:05'),
(9, 'about.php', '192.168.1.2', '2025-10-15', '2025-10-15 08:28:47'),
(10, 'faq.php', '::1', '2025-10-16', '2025-10-16 05:44:20'),
(11, 'about.php', '::1', '2025-10-16', '2025-10-16 05:44:31'),
(12, 'admission_guide.php', '::1', '2025-10-16', '2025-10-16 05:44:38'),
(13, 'services.php', '::1', '2025-10-16', '2025-10-16 05:44:40'),
(14, 'campuslife.php', '::1', '2025-10-16', '2025-10-16 05:44:45'),
(15, 'contact.php', '::1', '2025-10-16', '2025-10-16 05:44:47'),
(16, 'programs.php', '::1', '2025-10-16', '2025-10-16 05:44:49'),
(17, 'faq.php', '::1', '2025-10-17', '2025-10-17 07:22:04'),
(18, 'admission_guide.php', '::1', '2025-10-17', '2025-10-17 07:22:09'),
(19, 'about.php', '::1', '2025-10-17', '2025-10-17 07:22:15'),
(20, 'programs.php', '::1', '2025-10-17', '2025-10-17 07:22:35'),
(21, 'services.php', '::1', '2025-10-17', '2025-10-17 07:54:22'),
(22, 'campuslife.php', '::1', '2025-10-17', '2025-10-17 07:54:26'),
(23, 'contact.php', '::1', '2025-10-17', '2025-10-17 07:54:32'),
(24, 'faq.php', '::1', '2025-10-18', '2025-10-18 08:35:08'),
(25, 'about.php', '::1', '2025-10-18', '2025-10-18 10:04:41'),
(26, 'programs.php', '::1', '2025-10-18', '2025-10-18 10:08:13'),
(27, 'admission_guide.php', '::1', '2025-10-18', '2025-10-18 10:08:19'),
(28, 'services.php', '::1', '2025-10-18', '2025-10-18 10:08:23'),
(29, 'campuslife.php', '::1', '2025-10-18', '2025-10-18 10:08:26'),
(30, 'contact.php', '::1', '2025-10-18', '2025-10-18 10:08:31'),
(31, 'faq.php', '::1', '2025-10-21', '2025-10-21 02:20:35'),
(32, 'about.php', '::1', '2025-10-21', '2025-10-21 02:20:37'),
(33, 'programs.php', '::1', '2025-10-21', '2025-10-21 02:20:41'),
(34, 'admission_guide.php', '::1', '2025-10-21', '2025-10-21 02:20:45'),
(35, 'services.php', '::1', '2025-10-21', '2025-10-21 02:20:48'),
(36, 'campuslife.php', '::1', '2025-10-21', '2025-10-21 02:20:50'),
(37, 'contact.php', '::1', '2025-10-21', '2025-10-21 02:20:55'),
(38, 'faq', '::1', '2025-10-21', '2025-10-21 04:36:30');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(150) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_title', 'POLYTECHNIC UNIVERSITY OF THE PHILIPPINES', '2025-10-13 06:23:20'),
(2, 'campus_name', 'Biñan Campus', '2025-10-12 06:06:49'),
(3, 'hero_heading', 'Serving the Nation through Quality Public Education', '2025-10-12 06:06:49'),
(4, 'hero_text', '<p>Welcome to the PUP Bi&ntilde;an Campus homepage - your hub for announcements, admissions, academic programs, student services, and campus life.</p>', '2025-10-13 07:02:49'),
(5, 'logo_path', 'images/uploads/media_68eca449ecf993.31603485.png', '2025-10-13 07:03:37'),
(6, 'hero_image_path', 'images/uploads/media_68eca4741dcc95.76310992.jpg', '2025-10-13 07:04:20'),
(7, 'footer_about', '<p style=\"text-align: left;\">PUP Bi&ntilde;an Campus is part of the country\'s largest state university system,</p>\r\n<p style=\"text-align: left;\">committed to accessible</p>\r\n<p style=\"text-align: left;\">and excellent public higher education..</p>', '2025-10-15 08:11:41'),
(8, 'footer_address', '<p>Brgy. Zapote Binan, Laguna</p>', '2025-10-13 07:01:38'),
(9, 'footer_email', 'binan@pup.edu.ph', '2025-10-13 07:01:38'),
(10, 'footer_phone', '+63 49 544-0627', '2025-10-13 07:01:38'),
(11, 'footer_facebook', 'https://www.youtube.com/', '2025-10-21 02:35:08'),
(12, 'footer_x', 'https://www.youtube.com/', '2025-10-21 02:35:08'),
(13, 'footer_youtube', 'https://www.youtube.com/', '2025-10-21 02:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(10) UNSIGNED NOT NULL,
  `label` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `label`, `url`, `created_at`, `updated_at`) VALUES
(1, 'linkedin', 'https://www.linkedin.com/feed/', '2025-10-21 02:50:41', '2025-10-21 02:50:41'),
(2, 'facebook', 'https://www.facebook.com/Loytipon', '2025-10-21 02:51:55', '2025-10-21 02:51:55');

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(100) NOT NULL,
  `visit_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitors`
--

INSERT INTO `visitors` (`id`, `ip_address`, `visit_time`) VALUES
(1, '::1', '2025-10-08 09:07:29'),
(2, '::1', '2025-10-08 09:07:33'),
(3, '::1', '2025-10-08 09:08:27'),
(4, '::1', '2025-10-08 09:09:49'),
(5, '::1', '2025-10-08 09:10:31'),
(6, '::1', '2025-10-08 09:11:23'),
(7, '::1', '2025-10-08 09:12:36'),
(8, '::1', '2025-10-08 09:12:47'),
(9, '::1', '2025-10-08 09:13:47'),
(10, '::1', '2025-10-08 09:16:42'),
(11, '::1', '2025-10-08 09:17:48'),
(12, '::1', '2025-10-08 09:17:49'),
(13, '::1', '2025-10-08 09:18:27'),
(14, '::1', '2025-10-08 09:18:46'),
(15, '::1', '2025-10-08 09:19:01'),
(16, '::1', '2025-10-08 09:22:51'),
(17, '::1', '2025-10-08 09:24:32'),
(18, '::1', '2025-10-08 09:24:51'),
(19, '::1', '2025-10-08 09:25:10'),
(20, '::1', '2025-10-08 09:25:36'),
(21, '::1', '2025-10-08 09:25:47'),
(22, '::1', '2025-10-08 09:26:00'),
(23, '::1', '2025-10-08 09:26:11'),
(24, '::1', '2025-10-08 09:26:24'),
(25, '::1', '2025-10-10 05:49:09'),
(26, '::1', '2025-10-10 07:40:38'),
(27, '::1', '2025-10-10 07:41:03'),
(28, '::1', '2025-10-10 07:54:03'),
(29, '::1', '2025-10-10 08:05:42'),
(30, '::1', '2025-10-10 08:13:49'),
(31, '::1', '2025-10-10 08:27:01'),
(32, '::1', '2025-10-10 08:27:11'),
(33, '::1', '2025-10-10 08:27:21'),
(34, '::1', '2025-10-10 08:27:36'),
(35, '::1', '2025-10-10 08:44:07'),
(36, '::1', '2025-10-10 08:44:15'),
(37, '::1', '2025-10-10 08:44:21'),
(38, '::1', '2025-10-10 08:44:23'),
(39, '::1', '2025-10-10 09:12:27'),
(40, '::1', '2025-10-10 09:13:44'),
(41, '::1', '2025-10-10 09:14:03'),
(42, '::1', '2025-10-10 09:20:39'),
(43, '::1', '2025-10-10 09:21:50'),
(44, '::1', '2025-10-10 09:24:56'),
(45, '::1', '2025-10-10 09:25:22'),
(46, '::1', '2025-10-10 09:25:24'),
(47, '::1', '2025-10-10 09:25:37'),
(48, '::1', '2025-10-10 09:34:30'),
(49, '::1', '2025-10-10 09:34:34'),
(50, '::1', '2025-10-10 09:38:48'),
(51, '::1', '2025-10-10 10:25:50'),
(52, '::1', '2025-10-12 05:16:08'),
(53, '::1', '2025-10-12 06:02:01'),
(54, '::1', '2025-10-12 06:02:14'),
(55, '::1', '2025-10-12 06:19:43'),
(56, '::1', '2025-10-12 06:27:15'),
(57, '::1', '2025-10-12 06:27:42'),
(58, '::1', '2025-10-12 06:28:12'),
(59, '::1', '2025-10-12 06:28:14'),
(60, '::1', '2025-10-12 06:28:55'),
(61, '::1', '2025-10-12 06:29:31'),
(62, '::1', '2025-10-12 06:30:00'),
(63, '::1', '2025-10-12 06:31:22'),
(64, '::1', '2025-10-12 06:32:45'),
(65, '::1', '2025-10-12 07:04:09'),
(66, '::1', '2025-10-13 06:01:01'),
(67, '::1', '2025-10-13 06:17:37'),
(68, '::1', '2025-10-13 06:26:57'),
(69, '::1', '2025-10-13 06:32:25'),
(70, '::1', '2025-10-13 06:32:49'),
(71, '::1', '2025-10-13 06:37:04'),
(72, '::1', '2025-10-13 06:44:53'),
(73, '::1', '2025-10-13 06:46:22'),
(74, '::1', '2025-10-13 06:48:54'),
(75, '::1', '2025-10-13 06:49:01'),
(76, '::1', '2025-10-13 06:49:24'),
(77, '::1', '2025-10-13 06:51:28'),
(78, '::1', '2025-10-13 06:51:51'),
(79, '::1', '2025-10-13 06:52:00'),
(80, '::1', '2025-10-13 06:52:51'),
(81, '::1', '2025-10-13 06:52:58'),
(82, '::1', '2025-10-13 06:53:01'),
(83, '::1', '2025-10-13 06:54:25'),
(84, '::1', '2025-10-13 06:54:26'),
(85, '::1', '2025-10-13 07:01:54'),
(86, '::1', '2025-10-13 07:02:14'),
(87, '::1', '2025-10-13 07:02:53'),
(88, '::1', '2025-10-13 07:04:00'),
(89, '::1', '2025-10-13 07:04:08'),
(90, '::1', '2025-10-13 07:04:25'),
(91, '::1', '2025-10-13 07:04:31'),
(92, '::1', '2025-10-13 07:04:35'),
(93, '::1', '2025-10-13 07:06:19'),
(94, '::1', '2025-10-13 07:07:00'),
(95, '::1', '2025-10-13 07:07:18'),
(96, '::1', '2025-10-13 07:08:18'),
(97, '::1', '2025-10-13 07:08:25'),
(98, '::1', '2025-10-13 07:08:37'),
(99, '::1', '2025-10-13 07:09:52'),
(100, '::1', '2025-10-13 07:10:00'),
(101, '::1', '2025-10-13 07:10:03'),
(102, '::1', '2025-10-13 07:10:12'),
(103, '::1', '2025-10-13 07:10:17'),
(104, '::1', '2025-10-13 07:10:32'),
(105, '::1', '2025-10-13 07:10:37'),
(106, '::1', '2025-10-13 07:11:36'),
(107, '::1', '2025-10-13 07:11:58'),
(108, '::1', '2025-10-13 07:12:13'),
(109, '::1', '2025-10-13 07:12:24'),
(110, '::1', '2025-10-13 07:13:14'),
(111, '::1', '2025-10-13 07:13:23'),
(112, '::1', '2025-10-13 07:13:40'),
(113, '::1', '2025-10-13 07:13:47'),
(114, '::1', '2025-10-13 07:13:54'),
(115, '::1', '2025-10-13 07:13:55'),
(116, '::1', '2025-10-13 07:14:47'),
(117, '::1', '2025-10-13 07:14:53'),
(118, '::1', '2025-10-13 07:15:10'),
(119, '::1', '2025-10-13 07:15:27'),
(120, '::1', '2025-10-13 07:15:39'),
(121, '::1', '2025-10-13 07:15:53'),
(122, '::1', '2025-10-13 07:16:08'),
(123, '::1', '2025-10-13 07:16:16'),
(124, '::1', '2025-10-13 07:16:28'),
(125, '::1', '2025-10-13 07:16:35'),
(126, '::1', '2025-10-13 07:16:37'),
(127, '::1', '2025-10-13 07:16:37'),
(128, '::1', '2025-10-13 07:16:39'),
(129, '::1', '2025-10-13 07:19:38'),
(130, '::1', '2025-10-13 07:24:27'),
(131, '::1', '2025-10-13 07:24:28'),
(132, '::1', '2025-10-13 07:24:36'),
(133, '::1', '2025-10-13 07:24:37'),
(134, '::1', '2025-10-13 07:24:53'),
(135, '::1', '2025-10-13 07:25:07'),
(136, '::1', '2025-10-13 07:25:55'),
(137, '::1', '2025-10-13 07:26:11'),
(138, '::1', '2025-10-13 07:26:12'),
(139, '::1', '2025-10-13 07:26:27'),
(140, '::1', '2025-10-13 07:26:28'),
(141, '::1', '2025-10-13 07:26:42'),
(142, '::1', '2025-10-13 07:26:49'),
(143, '::1', '2025-10-13 07:26:55'),
(144, '::1', '2025-10-13 07:27:04'),
(145, '::1', '2025-10-13 07:27:13'),
(146, '::1', '2025-10-13 07:27:32'),
(147, '::1', '2025-10-13 07:27:33'),
(148, '::1', '2025-10-13 07:28:15'),
(149, '::1', '2025-10-13 07:28:17'),
(150, '::1', '2025-10-13 07:28:20'),
(151, '::1', '2025-10-13 07:28:28'),
(152, '::1', '2025-10-13 07:28:51'),
(153, '::1', '2025-10-13 07:28:59'),
(154, '::1', '2025-10-13 07:29:07'),
(155, '::1', '2025-10-13 07:29:22'),
(156, '::1', '2025-10-13 07:30:45'),
(157, '::1', '2025-10-13 07:30:53'),
(158, '::1', '2025-10-13 07:31:19'),
(159, '::1', '2025-10-13 07:31:34'),
(160, '::1', '2025-10-13 07:33:40'),
(161, '::1', '2025-10-13 07:33:54'),
(162, '::1', '2025-10-13 07:33:57'),
(163, '::1', '2025-10-13 07:34:04'),
(164, '::1', '2025-10-13 07:34:40'),
(165, '::1', '2025-10-13 07:34:49'),
(166, '::1', '2025-10-13 07:34:58'),
(167, '::1', '2025-10-13 07:35:04'),
(168, '::1', '2025-10-13 07:35:12'),
(169, '::1', '2025-10-13 07:38:38'),
(170, '::1', '2025-10-13 07:39:33'),
(171, '::1', '2025-10-13 07:44:04'),
(172, '::1', '2025-10-13 07:44:24'),
(173, '::1', '2025-10-13 07:44:25'),
(174, '::1', '2025-10-13 07:44:36'),
(175, '::1', '2025-10-13 07:44:45'),
(176, '::1', '2025-10-13 07:44:54'),
(177, '::1', '2025-10-13 07:44:55'),
(178, '::1', '2025-10-13 07:45:04'),
(179, '::1', '2025-10-13 07:45:17'),
(180, '::1', '2025-10-13 07:47:50'),
(181, '::1', '2025-10-13 07:49:27'),
(182, '::1', '2025-10-13 07:49:34'),
(183, '::1', '2025-10-13 07:49:50'),
(184, '::1', '2025-10-13 07:49:59'),
(185, '::1', '2025-10-13 07:50:08'),
(186, '::1', '2025-10-13 07:50:28'),
(187, '::1', '2025-10-13 07:50:48'),
(188, '::1', '2025-10-13 07:50:57'),
(189, '::1', '2025-10-13 07:51:05'),
(190, '::1', '2025-10-13 07:51:32'),
(191, '::1', '2025-10-13 07:51:39'),
(192, '::1', '2025-10-13 07:51:45'),
(193, '::1', '2025-10-13 07:54:04'),
(194, '::1', '2025-10-13 07:54:06'),
(195, '::1', '2025-10-13 07:54:28'),
(196, '::1', '2025-10-13 07:54:35'),
(197, '::1', '2025-10-13 07:54:35'),
(198, '::1', '2025-10-13 07:55:26'),
(199, '::1', '2025-10-13 07:55:36'),
(200, '::1', '2025-10-13 07:55:51'),
(201, '::1', '2025-10-13 07:55:57'),
(202, '::1', '2025-10-13 07:56:23'),
(203, '::1', '2025-10-13 07:56:28'),
(204, '::1', '2025-10-13 08:01:05'),
(205, '::1', '2025-10-13 08:01:37'),
(206, '::1', '2025-10-13 08:01:38'),
(207, '::1', '2025-10-13 08:01:39'),
(208, '::1', '2025-10-13 08:01:40'),
(209, '::1', '2025-10-13 08:01:40'),
(210, '::1', '2025-10-13 08:02:38'),
(211, '::1', '2025-10-13 08:02:44'),
(212, '::1', '2025-10-13 08:03:15'),
(213, '::1', '2025-10-13 08:13:02'),
(214, '::1', '2025-10-13 08:22:22'),
(215, '::1', '2025-10-13 08:22:55'),
(216, '::1', '2025-10-13 08:26:51'),
(217, '::1', '2025-10-15 03:30:09'),
(218, '::1', '2025-10-15 03:30:50'),
(219, '::1', '2025-10-15 03:30:53'),
(220, '::1', '2025-10-15 03:32:11'),
(221, '::1', '2025-10-15 04:38:51'),
(222, '::1', '2025-10-15 04:40:47'),
(223, '::1', '2025-10-15 04:45:40'),
(224, '::1', '2025-10-15 04:58:01'),
(225, '::1', '2025-10-15 04:58:07'),
(226, '::1', '2025-10-15 05:18:27'),
(227, '::1', '2025-10-15 05:23:57'),
(228, '::1', '2025-10-15 05:24:20'),
(229, '::1', '2025-10-15 05:24:37'),
(230, '::1', '2025-10-15 05:25:30'),
(231, '::1', '2025-10-15 05:25:46'),
(232, '::1', '2025-10-15 05:34:05'),
(233, '::1', '2025-10-15 05:36:45'),
(234, '::1', '2025-10-15 05:37:13'),
(235, '::1', '2025-10-15 05:37:16'),
(236, '::1', '2025-10-15 05:40:49'),
(237, '::1', '2025-10-15 05:40:51'),
(238, '::1', '2025-10-15 05:41:40'),
(239, '::1', '2025-10-15 05:42:01'),
(240, '::1', '2025-10-15 05:43:06'),
(241, '::1', '2025-10-15 05:47:46'),
(242, '::1', '2025-10-15 05:48:38'),
(243, '::1', '2025-10-15 05:48:52'),
(244, '::1', '2025-10-15 05:50:02'),
(245, '::1', '2025-10-15 05:51:14'),
(246, '::1', '2025-10-15 05:51:42'),
(247, '::1', '2025-10-15 05:51:55'),
(248, '::1', '2025-10-15 05:52:13'),
(249, '::1', '2025-10-15 05:52:29'),
(250, '::1', '2025-10-15 05:52:40'),
(251, '::1', '2025-10-15 05:53:03'),
(252, '::1', '2025-10-15 05:54:57'),
(253, '::1', '2025-10-15 05:55:18'),
(254, '::1', '2025-10-15 05:55:28'),
(255, '::1', '2025-10-15 05:55:50'),
(256, '::1', '2025-10-15 05:56:07'),
(257, '::1', '2025-10-15 05:56:26'),
(258, '::1', '2025-10-15 05:56:43'),
(259, '::1', '2025-10-15 05:56:57'),
(260, '::1', '2025-10-15 05:57:19'),
(261, '::1', '2025-10-15 05:57:40'),
(262, '::1', '2025-10-15 05:57:53'),
(263, '::1', '2025-10-15 05:58:25'),
(264, '::1', '2025-10-15 05:58:59'),
(265, '::1', '2025-10-15 05:59:12'),
(266, '::1', '2025-10-15 05:59:49'),
(267, '::1', '2025-10-15 06:00:23'),
(268, '::1', '2025-10-15 06:00:38'),
(269, '::1', '2025-10-15 06:15:18'),
(270, '::1', '2025-10-15 06:18:23'),
(271, '::1', '2025-10-15 06:26:43'),
(272, '::1', '2025-10-15 06:27:07'),
(273, '::1', '2025-10-15 06:27:13'),
(274, '::1', '2025-10-15 06:57:32'),
(275, '::1', '2025-10-15 06:57:38'),
(276, '::1', '2025-10-15 06:57:52'),
(277, '::1', '2025-10-15 07:41:12'),
(278, '::1', '2025-10-15 07:41:42'),
(279, '::1', '2025-10-15 07:53:03'),
(280, '::1', '2025-10-15 07:53:36'),
(281, '::1', '2025-10-15 07:53:46'),
(282, '::1', '2025-10-15 07:54:04'),
(283, '::1', '2025-10-15 07:54:13'),
(284, '::1', '2025-10-15 07:55:07'),
(285, '::1', '2025-10-15 08:02:11'),
(286, '::1', '2025-10-15 08:10:48'),
(287, '::1', '2025-10-15 08:12:16'),
(288, '192.168.1.2', '2025-10-15 08:28:05'),
(289, '::1', '2025-10-15 08:31:07'),
(290, '::1', '2025-10-15 08:33:15'),
(291, '::1', '2025-10-15 08:33:32'),
(292, '192.168.1.2', '2025-10-15 08:50:58'),
(293, '192.168.1.2', '2025-10-15 08:51:06'),
(294, '::1', '2025-10-16 05:44:17'),
(295, '::1', '2025-10-16 05:44:57'),
(296, '::1', '2025-10-17 07:22:03'),
(297, '::1', '2025-10-17 07:22:27'),
(298, '::1', '2025-10-17 07:33:56'),
(299, '::1', '2025-10-17 07:54:36'),
(300, '::1', '2025-10-17 08:14:05'),
(301, '::1', '2025-10-17 08:18:55'),
(302, '::1', '2025-10-17 08:22:07'),
(303, '::1', '2025-10-17 08:23:43'),
(304, '::1', '2025-10-17 08:25:08'),
(305, '::1', '2025-10-17 08:41:41'),
(306, '::1', '2025-10-17 08:44:11'),
(307, '::1', '2025-10-17 08:59:42'),
(308, '::1', '2025-10-17 09:11:37'),
(309, '::1', '2025-10-17 09:46:32'),
(310, '::1', '2025-10-18 08:35:07'),
(311, '::1', '2025-10-18 10:07:00'),
(312, '::1', '2025-10-18 10:08:41'),
(313, '::1', '2025-10-18 10:09:52'),
(314, '::1', '2025-10-21 02:20:35'),
(315, '::1', '2025-10-21 02:20:59'),
(316, '::1', '2025-10-21 02:35:12'),
(317, '::1', '2025-10-21 02:39:25'),
(318, '::1', '2025-10-21 02:50:44'),
(319, '::1', '2025-10-21 02:51:59'),
(320, '::1', '2025-10-21 02:58:50'),
(321, '::1', '2025-10-21 03:01:38'),
(322, '::1', '2025-10-21 04:36:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_publish_date` (`publish_date`),
  ADD KEY `fk_announcements_admin` (`created_by`);

--
-- Indexes for table `media_library`
--
ALTER TABLE `media_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_type` (`media_type`),
  ADD KEY `fk_media_admin` (`uploaded_by`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_news_publish_date` (`publish_date`),
  ADD KEY `fk_news_admin` (`created_by`);

--
-- Indexes for table `page_visits`
--
ALTER TABLE `page_visits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_social_label` (`label`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `media_library`
--
ALTER TABLE `media_library`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `page_visits`
--
ALTER TABLE `page_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_announcements_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media_library`
--
ALTER TABLE `media_library`
  ADD CONSTRAINT `fk_media_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `fk_news_admin` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
