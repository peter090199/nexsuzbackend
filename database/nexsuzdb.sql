-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 23, 2026 at 03:31 PM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexsuzdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `application_status_history`
--

DROP TABLE IF EXISTS `application_status_history`;
CREATE TABLE IF NOT EXISTS `application_status_history` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applied_id` bigint UNSIGNED NOT NULL,
  `transNo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recruiter',
  `changed_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ai_snapshot` text COLLATE utf8mb4_unicode_ci,
  `notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `application_status_history_applied_id_index` (`applied_id`),
  KEY `application_status_history_transno_index` (`transNo`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applied_answers`
--

DROP TABLE IF EXISTS `applied_answers`;
CREATE TABLE IF NOT EXISTS `applied_answers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applied_id` bigint UNSIGNED NOT NULL,
  `question_id` bigint UNSIGNED NOT NULL,
  `transNo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer_text` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applied_answers_applied_id_question_id_unique` (`applied_id`,`question_id`),
  KEY `applied_answers_applied_id_index` (`applied_id`),
  KEY `applied_answers_transno_index` (`transNo`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applied_jobs`
--

DROP TABLE IF EXISTS `applied_jobs`;
CREATE TABLE IF NOT EXISTS `applied_jobs` (
  `applied_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `applied_status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'applied_active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`applied_id`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applied_questions`
--

DROP TABLE IF EXISTS `applied_questions`;
CREATE TABLE IF NOT EXISTS `applied_questions` (
  `question_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `answer_type` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `question_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `options` text COLLATE utf8mb4_unicode_ci,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `correct_answer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applied_questions`
--

INSERT INTO `applied_questions` (`question_id`, `transNo`, `question_text`, `answer_text`, `answer_type`, `code`, `role_code`, `job_name`, `company`, `status`, `created_at`, `updated_at`, `question_type`, `options`, `is_required`, `correct_answer`, `sort_order`) VALUES
(257, 'TR-000006', 'Do you have previous call center or customer service experience?', NULL, 'yes', '701', 'DEF-MASTERADMIN', 'Customer Service Representative (CSR)', 'ABC Company', 'active', '2026-11-21 06:45:13', '2026-11-21 06:45:13', 'yes_no', NULL, 1, 'yes', 0),
(258, 'TR-000006', 'Are you willing to work on a shifting schedule, including night shifts?', NULL, 'yes', '701', 'DEF-MASTERADMIN', 'Customer Service Representative (CSR)', 'ABC Company', 'active', '2026-11-21 06:45:13', '2026-11-21 06:45:13', 'yes_no', NULL, 1, 'yes', 1),
(259, 'TR-000006', 'What is your highest educational attainment?', NULL, 'general', '701', 'DEF-MASTERADMIN', 'Customer Service Representative (CSR)', 'ABC Company', 'active', '2026-11-21 06:45:13', '2026-11-21 06:45:13', 'multiple_choice', '[\"Bachelor\",\"Master Degree\",\"High School\"]', 1, NULL, 2),
(260, 'TR-000006', 'Are you comfortable communicating in English?', NULL, 'yes', '701', 'DEF-MASTERADMIN', 'Customer Service Representative (CSR)', 'ABC Company', 'active', '2026-11-21 06:45:13', '2026-11-21 06:45:13', 'yes_no', NULL, 1, 'yes', 3);

-- --------------------------------------------------------

--
-- Table structure for table `applied_resumes`
--

DROP TABLE IF EXISTS `applied_resumes`;
CREATE TABLE IF NOT EXISTS `applied_resumes` (
  `resume_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `resume_pdf` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resume_id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_setting_key_unique` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=234 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `app_settings`
--

INSERT INTO `app_settings` (`id`, `setting_key`, `value`, `created_at`, `updated_at`) VALUES
(175, 'cv.mode', '{\"upload_only\":true}', '2026-11-22 23:40:34', '2026-11-23 04:53:06');

-- --------------------------------------------------------

--
-- Table structure for table `attachmentposts`
--

DROP TABLE IF EXISTS `attachmentposts`;
CREATE TABLE IF NOT EXISTS `attachmentposts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` int NOT NULL,
  `transNo` int NOT NULL,
  `posts_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `posts_uuind` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `path_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `posts_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attachmentposts`
--

INSERT INTO `attachmentposts` (`id`, `code`, `transNo`, `posts_uuid`, `posts_uuind`, `status`, `path_url`, `posts_type`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 711, 1, 'c225d726-722c-4949-a7a8-8b2f23a360d2', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 1, 'storage/app/public/uploads/posts/711/c225d726-722c-4949-a7a8-8b2f23a360d2/1744183737_cutedog.jpg', 'image', 'Human$ crazy', NULL, NULL, NULL),
(2, 711, 1, 'c225d726-722c-4949-a7a8-8b2f23a360d2', '399a6fe7-d1ac-42bf-825c-648294be889f', 1, 'storage/app/public/uploads/posts/711/c225d726-722c-4949-a7a8-8b2f23a360d2/1744183737_DEFAULTPROFILE.png', 'image', 'Human$ crazy', NULL, NULL, NULL),
(3, 711, 1, 'c225d726-722c-4949-a7a8-8b2f23a360d2', '5eeec985-96c9-48e6-886b-6443b0dc5185', 1, 'storage/app/public/uploads/posts/711/c225d726-722c-4949-a7a8-8b2f23a360d2/1744183737_nexsuzlogo.jpg', 'image', 'Human$ crazy', NULL, NULL, NULL),
(122, 714, 42, '3f5bd873-2425-4b87-87f4-9b560428b777', '0d8d64f2-1e95-458d-a931-ecdf0aacd160', 1, '/storage/app/public/uploads/posts/714/3f5bd873-2425-4b87-87f4-9b560428b777/1758350795_FPSO1.jpg', 'image', 'TJ Kim', NULL, NULL, NULL),
(132, 701, 77, '200eb893-a052-44bf-b6e7-005ba6302b77', '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 1, '/storage/app/public/uploads/posts/701/200eb893-a052-44bf-b6e7-005ba6302b77/1762668316_wallpaper.jpg', 'image', 'Pedro yorpo', NULL, NULL, NULL),
(133, 701, 78, 'a66c6328-afe7-4a3b-8b4d-ecb096c4d587', '684b81eb-0baa-4975-88f5-361d48feef46', 1, '/storage/app/public/uploads/posts/701/a66c6328-afe7-4a3b-8b4d-ecb096c4d587/1762696047_wallpaper.jpg', 'image', 'Pedro yorpo', NULL, NULL, NULL),
(134, 701, 78, 'a66c6328-afe7-4a3b-8b4d-ecb096c4d587', '1ab65dc5-a251-4e83-8509-a6d5f21b8e3b', 1, '/storage/app/public/uploads/posts/701/a66c6328-afe7-4a3b-8b4d-ecb096c4d587/1762696047_cover.jpg', 'image', 'Pedro yorpo', NULL, NULL, NULL),
(135, 701, 79, '1b858cf1-456c-4ac0-b90b-c45e917a05e9', '970ea541-a10d-4358-b006-1932fa7d2701', 1, '/storage/app/public/uploads/posts/701/1b858cf1-456c-4ac0-b90b-c45e917a05e9/1762697099_Web Design Promotional Video - After Effects Template.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(136, 701, 80, 'c99c24b6-223b-4ae2-94b9-18c453c421d0', '0dd91479-ad18-4fe1-9d31-368a4d3fd517', 1, '/storage/app/public/uploads/posts/701/c99c24b6-223b-4ae2-94b9-18c453c421d0/1763206440_Web Design Promotional Video - After Effects Template.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(137, 701, 81, 'c61b6ec4-0228-4e50-8c5e-f2c5e080b9ed', 'ab97e310-ec61-4e2a-bd28-cef5979e8cc4', 1, '/storage/app/public/uploads/posts/701/c61b6ec4-0228-4e50-8c5e-f2c5e080b9ed/1763564037_17.11.2025_22.26.44_REC.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(147, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', 'a24a8552-d33c-40fd-b792-4a38c5be6105', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_ESL.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(148, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '96dab581-8d7c-4b47-89b5-b55e1276092b', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_logo1.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(149, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '85281e5e-6c38-4c98-9aa7-7b3e23b5119f', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_logo2.PNG', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(150, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '4b1332e4-11fa-4a4b-90b0-a552479a721e', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_logo.png', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(151, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', 'c807e863-bea0-41dc-8f80-3e90489caf7b', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_devsped.PNG', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(152, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '752817b2-6869-477b-8198-914cc10aaa70', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_ESL.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(153, 701, 19, 'd6eb1ba6-58c6-43b3-8c0c-950de1916a33', '79d38165-610f-4d03-8b5b-3542e2ee665c', 1, '/storage/app/public/uploads/posts/701/d6eb1ba6-58c6-43b3-8c0c-950de1916a33/1764427401_ESL.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-29 14:43:21', NULL),
(154, 701, 87, '654e0ffc-c811-43b0-94c6-0149e7a15596', 'f0f1c044-b731-4afb-b1d6-80e9459f01f1', 1, '/storage/app/public/uploads/posts/701/654e0ffc-c811-43b0-94c6-0149e7a15596/1764683908_SampleVideo_1280x720_5mb.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(156, 701, 88, '3cf1b8b5-90cd-4497-8200-bfcc514acd4f', '26e8fd68-87c0-4076-95bc-f402a3d77065', 1, '/storage/app/public/uploads/posts/701/3cf1b8b5-90cd-4497-8200-bfcc514acd4f/1764767247_20251202223145.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(1044, 701, 1, '9f706ed1-ba67-4193-9f91-42ba47041f4a', 'd783a69c-27ec-4f99-9e36-5ebe09f225ca', 1, 'http://192.168.1.68:8000/storage/uploads/posts/701/9f706ed1-ba67-4193-9f91-42ba47041f4a/1a4c7e31-9ac6-4569-b8f5-81f008369bdc.png', 'image', 'Pedro yorpo', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `candidate_evaluations`
--

DROP TABLE IF EXISTS `candidate_evaluations`;
CREATE TABLE IF NOT EXISTS `candidate_evaluations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applied_id` bigint UNSIGNED NOT NULL,
  `transNo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `overall_score` tinyint UNSIGNED NOT NULL,
  `final_score` tinyint UNSIGNED DEFAULT NULL,
  `recommendation` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_scores` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `strengths` text COLLATE utf8mb4_unicode_ci,
  `weaknesses` text COLLATE utf8mb4_unicode_ci,
  `matched` text COLLATE utf8mb4_unicode_ci,
  `missing` text COLLATE utf8mb4_unicode_ci,
  `answer_evals` longtext COLLATE utf8mb4_unicode_ci,
  `explanation` text COLLATE utf8mb4_unicode_ci,
  `interview_questions` text COLLATE utf8mb4_unicode_ci,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `weights` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `criteria` text COLLATE utf8mb4_unicode_ci,
  `thresholds` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ai',
  `evaluated_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `candidate_evaluations_applied_id_index` (`applied_id`),
  KEY `candidate_evaluations_transno_index` (`transNo`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidate_notes`
--

DROP TABLE IF EXISTS `candidate_notes`;
CREATE TABLE IF NOT EXISTS `candidate_notes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applied_id` bigint UNSIGNED NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `candidate_notes_applied_id_index` (`applied_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commentposts`
--

DROP TABLE IF EXISTS `commentposts`;
CREATE TABLE IF NOT EXISTS `commentposts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `post_uuidOrUind` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `code` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_url` text COLLATE utf8mb4_unicode_ci,
  `date_comment` date NOT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `commentposts_comment_uuid_unique` (`comment_uuid`),
  KEY `commentposts_post_idx` (`post_uuidOrUind`(64),`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1024 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commentposts`
--

INSERT INTO `commentposts` (`id`, `comment_uuid`, `post_uuidOrUind`, `status`, `code`, `comment`, `attachment_url`, `date_comment`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'a721aa1f-ea44-4adb-9213-d7081c00839d', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 711, 'amazing posts peds', NULL, '2025-05-05', 'Human$ crazy', NULL, '2025-05-05 13:29:11', '2025-05-05 13:29:11'),
(21, '66e32530-9d62-43f2-b607-0279465e6c65', '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758', 0, 701, 'testcomment', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:17:51', '2025-05-07 14:17:51'),
(22, '599723f5-8a3f-4ced-8a1b-a36960ab1a92', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 711, 'nc update peds', NULL, '2025-05-09', 'Human$ crazy', 'Human$ crazy', '2025-05-09 13:05:11', '2025-05-23 13:15:32'),
(23, '3bf134ae-d974-4ea7-94fa-eee5464fe141', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 1, 711, 'This is a test comment update.', NULL, '2025-05-09', 'Human$ crazy', NULL, '2025-05-09 14:15:04', '2025-05-09 14:15:04'),
(24, 'c3b773c6-d3fb-4f9c-bc5b-73989e739869', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 711, 'wazzup', NULL, '2025-05-09', 'Human$ crazy', NULL, '2025-05-09 14:27:51', '2025-05-09 14:27:51'),
(63, '59da341d-3343-4036-a8b9-b408a88d8968', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'new update', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:40:00', '2025-06-01 05:04:17'),
(64, '19846833-00a1-4a1f-9693-d55a53da5ce7', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 701, 'nice images222', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:42:08', '2025-06-01 05:11:28'),
(65, '9da38c90-adaf-44af-a260-e253c6a4acbb', '5eeec985-96c9-48e6-886b-6443b0dc5185', 0, 701, 'nice images', NULL, '2025-05-10', 'Pedro yorpo', NULL, '2025-05-10 06:43:02', '2025-05-10 06:43:02'),
(66, '3e359464-695f-4a43-9792-ca47c2c02f21', '399a6fe7-d1ac-42bf-825c-648294be889f', 0, 701, 'nice pic', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:44:04', '2025-06-01 05:12:15'),
(67, '6d95b600-6928-475f-9cc1-b53af8e31c18', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 701, 'nice 1222', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:44:55', '2025-06-01 05:11:33'),
(75, 'c65747af-f2dd-439b-bc6d-fec387b5f26c', '1e7cfa1e-672f-42ab-8283-446ffb902ebc', 0, 711, 'nc pic  update', NULL, '2025-05-15', 'Human$ crazy', 'Human$ crazy', '2025-05-15 00:30:40', '2025-05-19 09:39:16'),
(148, '8b4c00be-8da3-4dc2-8d2b-4aa6651f2165', 'd6eb1ba6-58c6-43b3-8c0c-950de1916a33', 0, 701, 'savav', NULL, '2025-05-28', 'Pedro yorpo', NULL, '2025-05-28 15:01:20', '2025-05-28 15:01:20'),
(149, '6fd802cf-a17b-4ac4-b3cd-bfdd72f312b9', '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758', 0, 701, 'SDCSDC', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:07:45', '2025-05-31 13:07:45'),
(150, '6ed81b14-a31d-49e6-b5be-cb36893c5cc6', 'e4705480-0469-4423-b261-827f672bd958', 0, 701, 'test', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:37:16', '2025-05-31 13:37:16'),
(151, '096d680f-579c-471a-b0a1-fae6a1df672b', 'e4705480-0469-4423-b261-827f672bd958', 0, 701, 'dfdfn', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:42:40', '2025-05-31 13:42:40'),
(157, 'c3336fa3-8f88-40e5-91e4-16a91782305b', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'test2', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:28:45', '2025-06-01 05:03:51'),
(158, 'd29463ab-ecef-49b0-9c98-c0e8cd0983bc', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'dfdf', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:33:29', '2025-06-01 02:33:29'),
(159, '7c4fb0b3-8486-4d02-ae4b-d2c54ca9f577', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'dfdf', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:33:29', '2025-06-01 02:33:29'),
(165, '0b74316c-6200-4171-b202-db872218c838', '4859c8d7-55d4-4d93-b74b-93b091198585', 0, 701, 'test22', NULL, '2025-07-01', 'Pedro yorpo', 'Pedro yorpo', '2025-07-01 14:54:40', '2025-07-01 14:54:59'),
(166, '29ef583d-b675-4e12-805a-e6cd9c7d8946', '5df8c371-cc91-48c4-bcc6-9ed5637296ad', 0, 701, 'sdc', NULL, '2025-07-12', 'Pedro yorpo', 'Pedro yorpo', '2025-07-12 06:23:40', '2025-07-12 06:23:47'),
(168, 'd4facd98-6d51-4d36-8227-e1a01ea4139f', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 701, 'amazing pic', NULL, '2025-07-13', 'Pedro yorpo', NULL, '2025-07-13 14:14:52', '2025-07-13 14:14:52'),
(178, '2b8ebe8f-c992-41cd-944a-9705650a7639', '928785b3-af8d-4124-a889-a40fd4da9c85', 0, 701, 'SBDBSSDB', NULL, '2025-08-26', 'Pedro yorpo', 'Pedro yorpo', '2025-08-26 16:07:04', '2025-08-26 16:07:14'),
(179, 'acaa5718-5410-4b09-aa8e-4f57cd7d908a', '129c8393-8d44-4f06-89fd-836ebe09bb72', 0, 701, 'ascacs', NULL, '2025-09-03', 'Pedro yorpo', NULL, '2025-09-03 14:21:48', '2025-09-03 14:21:48'),
(184, 'b2fb746a-c592-43c8-9738-0622ec9afabb', 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 0, 701, 'amazing', NULL, '2025-09-23', 'Pedro yorpo', NULL, '2025-09-23 14:14:25', '2025-09-23 14:14:25'),
(193, '714f3686-f6ac-4026-8ae9-e7c35fa97701', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 701, 'csdc', NULL, '2025-10-08', 'Pedro yorpo', NULL, '2025-10-08 14:25:41', '2025-10-08 14:25:41'),
(194, 'd2f3b50e-aa88-4555-ba98-c550cd03d957', 'c0ccfb5d-7717-4605-8ca3-346abd8ea2b5', 0, 701, 'test', NULL, '2025-10-09', 'Pedro yorpo', NULL, '2025-10-09 12:52:54', '2025-10-09 12:52:54'),
(198, '342843dc-fa0c-4d51-a985-494a1a62e153', 'af2aa354-4c34-40c6-b48f-aadaf67f38bc', 0, 701, 'Taklong', NULL, '2025-10-18', 'Pedro yorpo', 'Pedro yorpo', '2025-10-18 02:07:43', '2025-11-09 13:07:59'),
(199, '69a95ad5-6289-4fba-8d8b-fe0ae4fabec3', 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 0, 701, 'test 2222', NULL, '2025-10-20', 'Pedro yorpo', 'Pedro yorpo', '2025-10-20 12:58:22', '2025-10-20 12:58:28'),
(200, '06e63de4-13eb-4bd2-9f79-4ae65f4b8915', 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 0, 701, 'dcsdc', NULL, '2025-10-20', 'Pedro yorpo', NULL, '2025-10-20 12:58:35', '2025-10-20 12:58:35'),
(201, 'bfbedd6c-1cc2-41d8-bfe2-47d2280c3ed2', 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 0, 701, 'peds', NULL, '2025-10-20', 'Pedro yorpo', 'Pedro yorpo', '2025-10-20 12:58:42', '2025-10-20 12:58:55'),
(202, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'test', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:13:06', '2025-10-25 05:13:06'),
(203, '95decad3-7b5f-481a-8766-c0aad7a1457f', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'test', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:14:43', '2025-10-25 05:14:43'),
(204, 'fc03b218-b9c1-48c2-824e-b59d25e970de', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'sdsdv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:17:25', '2025-10-25 05:17:25'),
(205, 'ca71d3fb-b37c-4ef2-b9da-c3ceef7b73b9', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'sdvsdv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:17:32', '2025-10-25 05:17:32'),
(206, '6d0933ca-ef0f-4fa1-997b-1fc96fbaed1f', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'sdvsdv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:17:35', '2025-10-25 05:17:35'),
(207, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', '3a478c73-a214-4ca8-a56f-466d954c7a00', 0, 701, 'test', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:59:33', '2025-10-25 05:59:33'),
(208, '33739474-5528-4009-ade2-b90e62a8955b', '3a478c73-a214-4ca8-a56f-466d954c7a00', 0, 701, 'test2', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:59:40', '2025-10-25 05:59:40'),
(209, 'cfa476df-ce3b-4043-b853-9c0d6cb422ca', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'dcscsdc', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:07:11', '2025-10-25 06:07:11'),
(210, 'f5da0e66-9b1d-4aaa-ba11-0f9aa02fab5f', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, 'sdcsc', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:07:17', '2025-10-25 06:07:17'),
(211, '745c38fb-7eab-4677-acdf-eed8b8ef3dc6', '088f2f5d-829f-48d5-abb8-9e804ff3e034', 0, 701, '2333', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:07:27', '2025-10-25 06:07:27'),
(212, 'a50dd7cb-7032-4471-8bf4-f4b86e15dd03', '3a478c73-a214-4ca8-a56f-466d954c7a00', 0, 701, 'test', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:08:55', '2025-10-25 06:08:55'),
(213, '552a0334-f0be-4324-9ae4-c506370bbebd', 'ca5746bf-adf6-48a5-8f1d-39a44ca03461', 0, 701, 'svsdvsdv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:48:13', '2025-10-25 06:48:13'),
(214, '2aa8ea0d-a6f0-4706-9e42-2bee2616aab1', 'd1d52f38-376f-4f98-8e06-a06be62a4444', 0, 701, 'test wwefwefew', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:51:06', '2025-10-25 06:51:06'),
(216, '2374901f-8d3a-4bd8-a49b-d382ee3f9a35', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'Totally agree. Most jobs in the next decade will require at least some understanding of automation and data.', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 07:45:12', '2025-10-25 07:45:12'),
(217, '61550367-f662-4754-9901-d39a3c222ea2', '943be758-3a71-4d1d-8422-7154641c9ec1', 0, 701, 'vdfvdfv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 09:07:53', '2025-10-25 09:07:53'),
(218, 'baa2f528-2a18-4328-9972-9c0cb6793ade', '943be758-3a71-4d1d-8422-7154641c9ec1', 0, 701, 'dfvdfv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 09:07:56', '2025-10-25 09:07:56'),
(219, '50bc310a-4e28-4eac-9e0d-8cfcde31bd1a', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'sdcsdcsc', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 09:11:53', '2025-10-25 09:11:53'),
(220, 'd55a1953-6e44-48b6-b042-ff3b8a1c942d', '943be758-3a71-4d1d-8422-7154641c9ec1', 0, 701, 'csdcscsdv ssvd ssvsv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 10:43:56', '2025-10-25 10:43:56'),
(222, '66676b9e-a0a9-4c12-85a0-12ae84a23b60', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'amazing', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:39:36', '2025-10-25 11:39:36'),
(223, '8b0e9b36-7f0f-447a-b99d-8199ddf5f9d5', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'your so beatiful', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:43:58', '2025-10-25 11:43:58'),
(224, 'fcbfddb1-315a-41ca-b33c-14391eb734be', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'your so beautiful', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:44:06', '2025-10-25 11:44:06'),
(225, '0ea63925-ccae-463d-888b-b5581bef0458', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'your so beatiful', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:44:06', '2025-10-25 11:44:06'),
(226, '6e40e1f1-e230-493e-8f50-2dadf08bf508', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'Test', NULL, '2025-10-28', 'Pedro yorpo', NULL, '2025-10-28 04:34:36', '2025-10-28 04:34:36'),
(228, 'f494a66b-55a5-4ded-8984-2d5b88054cc8', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'sdvsdvsdv', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 04:20:31', '2025-11-09 04:20:31'),
(229, '93aeeaa7-1bc0-424c-b256-659cf353e67f', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 718, 'test', NULL, '2025-11-09', 'Nexsuz User -', NULL, '2025-11-09 04:47:59', '2025-11-09 04:47:59'),
(230, '83dbbba7-b3a8-4bd3-bcd2-1efe52d23d6b', '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 0, 701, 'c cv cv', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 06:05:34', '2025-11-09 06:05:34'),
(231, '559cbe31-5cc0-4dca-ab0d-46d0c83630db', '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 0, 701, 'trtrtbtr', NULL, '2025-11-09', 'Pedro yorpo', 'Pedro yorpo', '2025-11-09 06:05:34', '2025-11-09 12:43:01'),
(232, 'c8f5ede1-4b93-446e-ac24-e14f7a734122', '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 0, 701, 'test', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 12:38:35', '2025-11-09 12:38:35'),
(233, '649de5da-b36c-433b-a4d9-0bb25f3289bb', 'af2aa354-4c34-40c6-b48f-aadaf67f38bc', 0, 701, 'test', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 13:08:04', '2025-11-09 13:08:04'),
(234, '42356574-9b08-49dd-a841-ad41a4aedae5', '684b81eb-0baa-4975-88f5-361d48feef46', 0, 701, 'test', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 13:47:42', '2025-11-09 13:47:42'),
(235, 'ae8d7e0c-7430-46ee-b8a9-c4109ea6327c', '970ea541-a10d-4358-b006-1932fa7d2701', 0, 701, 'amazing', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 14:06:28', '2025-11-09 14:06:28'),
(236, 'dc819b0f-08d9-465b-ba40-5ca0757f59ce', '684b81eb-0baa-4975-88f5-361d48feef46', 0, 701, 'nice', NULL, '2025-11-12', 'Pedro yorpo', NULL, '2025-11-12 12:40:24', '2025-11-12 12:40:24'),
(237, 'ee156432-aa54-454d-8eca-bca4b888b609', '2ef93b3a-b39f-4d05-aa38-5440d098f5eb', 0, 701, 'scsdcsdc', NULL, '2025-11-14', 'Pedro yorpo', NULL, '2025-11-14 14:13:36', '2025-11-14 14:13:36'),
(238, '189f4afe-3bd5-43b6-b6ee-91d25725570b', '970ea541-a10d-4358-b006-1932fa7d2701', 0, 701, 'nice', NULL, '2025-11-14', 'Pedro yorpo', NULL, '2025-11-14 15:25:15', '2025-11-14 15:25:15'),
(239, '4ed81e1d-db8f-438d-b292-04a2c90fd013', '0dd91479-ad18-4fe1-9d31-368a4d3fd517', 0, 701, 'test', NULL, '2025-11-15', 'Pedro yorpo', NULL, '2025-11-15 11:35:22', '2025-11-15 11:35:22'),
(246, 'f4ac2e5d-a9e2-4445-91d0-02c7eff82f7d', 'f128af8c-557c-4b0e-9cf2-4c0c5058cbae', 0, 701, 'TYTYK', NULL, '2026-04-22', 'Pedro yorpo', NULL, '2026-04-22 09:42:23', '2026-04-22 09:42:23'),
(343, '2779f4b2-4725-4902-8aee-05bb2f205050', '06a96dbf-41a6-4188-97f8-b7769dc5491d', 0, 701, 'wecsdc', NULL, '2026-11-16', 'Pedro yorpo', NULL, '2026-11-15 21:27:45', '2026-11-15 21:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `commentreactions`
--

DROP TABLE IF EXISTS `commentreactions`;
CREATE TABLE IF NOT EXISTS `commentreactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_type` enum('comment','reply') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_ref` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` int NOT NULL,
  `reaction` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `commentreactions_one_per_user` (`target_type`,`target_ref`,`code`),
  KEY `commentreactions_target_idx` (`target_type`,`target_ref`)
) ENGINE=MyISAM AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commentreactions`
--

INSERT INTO `commentreactions` (`id`, `target_type`, `target_ref`, `code`, `reaction`, `created_at`, `updated_at`) VALUES
(3, 'comment', '18ec3e90-9171-403c-9373-816818e1058d', 801034, 'Care', '2026-11-15 20:20:52', '2026-11-15 20:20:52'),
(6, 'comment', 'a32d8092-67c4-4a4e-92aa-410ad2cff085', 801043, 'Like', NULL, NULL),
(5, 'reply', '140', 801017, 'Wow', '2026-11-15 20:20:52', '2026-11-15 20:20:52'),
(7, 'comment', '1023bfb2-fa66-4d39-875c-71149f53ceef', 801119, 'Love', '2026-11-15 20:20:53', '2026-11-15 20:20:53'),
(8, 'comment', '1023bfb2-fa66-4d39-875c-71149f53ceef', 801087, 'Love', '2026-11-15 20:20:53', '2026-11-15 20:20:53'),
(9, 'comment', '60ca03b8-cb85-403f-abf0-4ab6a01756e2', 801307, 'Wow', '2026-11-15 20:20:53', '2026-11-15 20:20:53'),
(16, 'comment', '3531a554-bc50-4da3-bc48-00c373bc5211', 801286, 'Care', '2026-11-15 20:41:27', '2026-11-15 20:41:27'),
(19, 'comment', 'a10feb0e-0992-499f-a4c2-caa9e49c4ee1', 801318, 'Like', NULL, NULL),
(18, 'reply', '151', 801240, 'Wow', '2026-11-15 20:41:27', '2026-11-15 20:41:27'),
(20, 'comment', 'd8ad337d-17b6-4df0-8222-297b871df448', 801392, 'Love', '2026-11-15 20:41:27', '2026-11-15 20:41:27'),
(21, 'comment', 'd8ad337d-17b6-4df0-8222-297b871df448', 801362, 'Love', '2026-11-15 20:41:27', '2026-11-15 20:41:27'),
(22, 'comment', '61e561ba-2dd4-44c1-80cb-71e239c11622', 801539, 'Wow', '2026-11-15 20:41:28', '2026-11-15 20:41:28'),
(25, 'comment', '4a331692-08a0-4e02-b7ba-1062b7084428', 801010, 'Care', '2026-11-15 20:43:04', '2026-11-15 20:43:04'),
(28, 'comment', '12ad2db7-cd34-430e-93ec-e5210f83c0d6', 801044, 'Like', NULL, NULL),
(27, 'reply', '158', 800967, 'Wow', '2026-11-15 20:43:04', '2026-11-15 20:43:04'),
(29, 'comment', 'fd77af1d-be7a-4c97-b57f-a76d3c481de5', 801079, 'Love', '2026-11-15 20:43:04', '2026-11-15 20:43:04'),
(30, 'comment', 'fd77af1d-be7a-4c97-b57f-a76d3c481de5', 801068, 'Love', '2026-11-15 20:43:04', '2026-11-15 20:43:04'),
(31, 'comment', '236f1d9b-2b88-436c-a94e-60c70e2bb37f', 801273, 'Wow', '2026-11-15 20:43:05', '2026-11-15 20:43:05'),
(38, 'comment', '3e986c87-2fb3-4f22-91aa-bd0e3ed4ef5f', 801103, 'Care', '2026-11-18 07:22:40', '2026-11-18 07:22:40'),
(99, 'comment', 'be4557e4-2101-409f-a1d5-eb51bfcdeb28', 801342, 'Love', '2026-11-21 20:42:11', '2026-11-21 20:42:11'),
(97, 'reply', '285', 801133, 'Wow', '2026-11-21 20:42:11', '2026-11-21 20:42:11'),
(41, 'comment', 'eaf44354-3ec1-4fd0-b3b6-4cbfb76bbdc6', 801138, 'Like', NULL, NULL),
(40, 'reply', '167', 801070, 'Wow', '2026-11-18 07:22:40', '2026-11-18 07:22:40'),
(42, 'comment', '6f7e2e96-6560-4e19-8651-0570615c3cde', 801189, 'Love', '2026-11-18 07:22:40', '2026-11-18 07:22:40'),
(43, 'comment', '6f7e2e96-6560-4e19-8651-0570615c3cde', 801168, 'Love', '2026-11-18 07:22:40', '2026-11-18 07:22:40'),
(44, 'comment', 'bf878adf-ca64-46e2-8e6b-e717a26d3cb5', 801419, 'Wow', '2026-11-18 07:22:41', '2026-11-18 07:22:41'),
(47, 'comment', 'ed4439ca-fa30-42a9-a100-391ed6989401', 801106, 'Care', '2026-11-18 07:24:07', '2026-11-18 07:24:07'),
(50, 'comment', 'f05c5879-8215-4aba-9a35-20c7dffb024c', 801166, 'Like', NULL, NULL),
(49, 'reply', '174', 801083, 'Wow', '2026-11-18 07:24:07', '2026-11-18 07:24:07'),
(51, 'comment', '9294990f-e37b-4eb4-ba65-09d65a71c52f', 801218, 'Love', '2026-11-18 07:24:08', '2026-11-18 07:24:08'),
(52, 'comment', '9294990f-e37b-4eb4-ba65-09d65a71c52f', 801199, 'Love', '2026-11-18 07:24:08', '2026-11-18 07:24:08'),
(53, 'comment', '7b3823f0-e2aa-48bd-9e61-1a73c75f131f', 801381, 'Wow', '2026-11-18 07:24:08', '2026-11-18 07:24:08'),
(56, 'comment', '162cc8a2-6d3c-434f-8107-3dd49ae56144', 801211, 'Care', '2026-11-18 07:47:10', '2026-11-18 07:47:10'),
(59, 'comment', 'df9eb6b4-81dc-4d2f-b661-6137ade4b6ca', 801266, 'Like', NULL, NULL),
(58, 'reply', '181', 801146, 'Wow', '2026-11-18 07:47:10', '2026-11-18 07:47:10'),
(60, 'comment', '17455d23-54f2-4282-aedf-abfd1073d742', 801389, 'Love', '2026-11-18 07:47:10', '2026-11-18 07:47:10'),
(61, 'comment', '17455d23-54f2-4282-aedf-abfd1073d742', 801330, 'Love', '2026-11-18 07:47:10', '2026-11-18 07:47:10'),
(62, 'comment', '9310953d-e6be-4d2c-965d-b7143623954f', 801585, 'Wow', '2026-11-18 07:47:11', '2026-11-18 07:47:11'),
(65, 'comment', '27530d8d-24f6-4f43-9034-d9aab03d339c', 801022, 'Care', '2026-11-18 08:23:55', '2026-11-18 08:23:55'),
(68, 'comment', 'debc98e4-3c76-4d6e-b43e-ec88fa34b7fe', 801048, 'Like', NULL, NULL),
(67, 'reply', '213', 800987, 'Wow', '2026-11-18 08:23:55', '2026-11-18 08:23:55'),
(69, 'comment', '49c7f0d7-3512-4e25-b1c1-28166ce93235', 801108, 'Love', '2026-11-18 08:23:56', '2026-11-18 08:23:56'),
(70, 'comment', '49c7f0d7-3512-4e25-b1c1-28166ce93235', 801080, 'Love', '2026-11-18 08:23:56', '2026-11-18 08:23:56'),
(71, 'comment', '5a539fcb-ab39-4965-b51c-8a47fe30e4aa', 801300, 'Wow', '2026-11-18 08:23:56', '2026-11-18 08:23:56'),
(74, 'comment', 'de785f19-2585-4e98-8968-66013f3864d9', 801256, 'Care', '2026-11-19 04:05:21', '2026-11-19 04:05:21'),
(77, 'comment', '1c74f4e7-c9f1-4393-9305-c7a58d723f91', 801314, 'Like', NULL, NULL),
(76, 'reply', '252', 801207, 'Wow', '2026-11-19 04:05:21', '2026-11-19 04:05:21'),
(78, 'comment', '199f6754-bedc-4a76-8bc0-3c8bb9c9b4b0', 801393, 'Love', '2026-11-19 04:05:22', '2026-11-19 04:05:22'),
(79, 'comment', '199f6754-bedc-4a76-8bc0-3c8bb9c9b4b0', 801327, 'Love', '2026-11-19 04:05:22', '2026-11-19 04:05:22'),
(80, 'comment', 'addcb001-9a0b-4f3b-9103-5c7b098c19b2', 801548, 'Wow', '2026-11-19 04:05:23', '2026-11-19 04:05:23'),
(83, 'comment', '411c3165-ef5d-4eb3-b21b-d4ee7f3a86aa', 801107, 'Care', '2026-11-19 04:12:06', '2026-11-19 04:12:06'),
(86, 'comment', '8d1cd01f-f7f3-46aa-8f23-03e54f02513e', 801126, 'Like', NULL, NULL),
(85, 'reply', '267', 801080, 'Wow', '2026-11-19 04:12:06', '2026-11-19 04:12:06'),
(87, 'comment', '0b12274b-a6fd-4cbc-a95a-e6a48dc3bff7', 801219, 'Love', '2026-11-19 04:12:07', '2026-11-19 04:12:07'),
(88, 'comment', '0b12274b-a6fd-4cbc-a95a-e6a48dc3bff7', 801182, 'Love', '2026-11-19 04:12:07', '2026-11-19 04:12:07'),
(89, 'comment', '4bacbd0c-0a5e-4e6f-b560-1a230e4e30fa', 801361, 'Wow', '2026-11-19 04:12:08', '2026-11-19 04:12:08'),
(98, 'comment', 'c60878b2-40eb-45d4-92b9-c884404ccaf1', 801264, 'Like', NULL, NULL),
(95, 'comment', 'c459a39c-c7d5-4c82-8571-9e21800fa74d', 801197, 'Care', '2026-11-21 20:42:11', '2026-11-21 20:42:11'),
(100, 'comment', 'be4557e4-2101-409f-a1d5-eb51bfcdeb28', 801289, 'Love', '2026-11-21 20:42:11', '2026-11-21 20:42:11'),
(101, 'comment', '14d0a8cf-4735-4b75-95ef-fe6607723328', 801490, 'Wow', '2026-11-21 20:42:12', '2026-11-21 20:42:12'),
(104, 'comment', '2ff95025-acbe-42a8-b82a-78377e050fa8', 801101, 'Care', '2026-11-21 22:16:35', '2026-11-21 22:16:35'),
(107, 'comment', 'f229240f-e8a0-4a5e-b160-b085b1a6bccf', 801115, 'Like', NULL, NULL),
(106, 'reply', '300', 801045, 'Wow', '2026-11-21 22:16:35', '2026-11-21 22:16:35'),
(108, 'comment', '586db99d-cdef-465d-9158-e363f9a23604', 801192, 'Love', '2026-11-21 22:16:35', '2026-11-21 22:16:35'),
(109, 'comment', '586db99d-cdef-465d-9158-e363f9a23604', 801136, 'Love', '2026-11-21 22:16:35', '2026-11-21 22:16:35'),
(110, 'comment', '41f8a75a-9360-4ead-bac4-730facb70f4f', 801356, 'Wow', '2026-11-21 22:16:37', '2026-11-21 22:16:37'),
(113, 'comment', 'cae3cafc-574d-4269-895e-5a29819f1acb', 801087, 'Care', '2026-11-21 22:28:08', '2026-11-21 22:28:08'),
(116, 'comment', 'eceaf93e-e251-4ad6-9c86-edc5bbd45f0a', 801133, 'Like', NULL, NULL),
(115, 'reply', '315', 801062, 'Wow', '2026-11-21 22:28:08', '2026-11-21 22:28:08'),
(117, 'comment', 'e685d901-db15-4530-ae7e-34175973c415', 801220, 'Love', '2026-11-21 22:28:08', '2026-11-21 22:28:08'),
(118, 'comment', 'e685d901-db15-4530-ae7e-34175973c415', 801162, 'Love', '2026-11-21 22:28:08', '2026-11-21 22:28:08'),
(119, 'comment', '0b7d6d99-d6c8-4fea-9da4-9333c9d73424', 801407, 'Wow', '2026-11-21 22:28:09', '2026-11-21 22:28:09'),
(122, 'comment', 'df0dcd74-98fa-4ac8-a89a-ed1776fb35e8', 800997, 'Care', '2026-11-21 22:29:21', '2026-11-21 22:29:21'),
(125, 'comment', '262693e1-e5e5-46a3-b661-40e1caf24b21', 801017, 'Like', NULL, NULL),
(124, 'reply', '330', 800946, 'Wow', '2026-11-21 22:29:21', '2026-11-21 22:29:21'),
(126, 'comment', '8a7370fa-c174-4965-ad0c-8db3670b2678', 801097, 'Love', '2026-11-21 22:29:21', '2026-11-21 22:29:21'),
(127, 'comment', '8a7370fa-c174-4965-ad0c-8db3670b2678', 801074, 'Love', '2026-11-21 22:29:21', '2026-11-21 22:29:21'),
(128, 'comment', '73fc6abb-bc50-445d-9180-1614a796d5b4', 801220, 'Wow', '2026-11-21 22:29:22', '2026-11-21 22:29:22'),
(131, 'comment', '44cf46b9-a393-47f7-ac56-ec37ae39a162', 801266, 'Care', '2026-11-21 22:30:20', '2026-11-21 22:30:20'),
(134, 'comment', '6c1c7bf8-4d5d-4af1-905c-f23be8d8339e', 801307, 'Like', NULL, NULL),
(133, 'reply', '345', 801203, 'Wow', '2026-11-21 22:30:20', '2026-11-21 22:30:20'),
(135, 'comment', 'f3af448b-1a51-496b-98f6-769e463174e9', 801382, 'Love', '2026-11-21 22:30:20', '2026-11-21 22:30:20'),
(136, 'comment', 'f3af448b-1a51-496b-98f6-769e463174e9', 801343, 'Love', '2026-11-21 22:30:20', '2026-11-21 22:30:20'),
(137, 'comment', '70e2b65a-f971-4a24-ad70-94dee572f315', 801569, 'Wow', '2026-11-21 22:30:21', '2026-11-21 22:30:21'),
(140, 'comment', '839b602e-04b5-44c0-9657-95f4f1db647a', 801168, 'Care', '2026-11-23 05:40:46', '2026-11-23 05:40:46'),
(143, 'comment', 'a4b83e5f-5ef5-4e6e-8ef6-d4f715044070', 801210, 'Like', NULL, NULL),
(142, 'reply', '376', 801108, 'Wow', '2026-11-23 05:40:46', '2026-11-23 05:40:46'),
(144, 'comment', 'cce554e3-332e-441b-935a-333f24edf83f', 801262, 'Love', '2026-11-23 05:40:46', '2026-11-23 05:40:46'),
(145, 'comment', 'cce554e3-332e-441b-935a-333f24edf83f', 801233, 'Love', '2026-11-23 05:40:46', '2026-11-23 05:40:46'),
(146, 'comment', '62219480-ed7e-4d0a-8b49-5d17e4ff8bc1', 801407, 'Wow', '2026-11-23 05:40:47', '2026-11-23 05:40:47'),
(149, 'comment', '451e65cc-db46-4d6d-afc6-a28ebdbf5b2b', 800977, 'Care', '2026-11-23 05:52:37', '2026-11-23 05:52:37'),
(152, 'comment', 'bda9f7e7-4581-4039-a34d-751c0f3e703e', 801015, 'Like', NULL, NULL),
(151, 'reply', '391', 800913, 'Wow', '2026-11-23 05:52:37', '2026-11-23 05:52:37'),
(153, 'comment', '23eb4d2c-349a-4e9d-9f78-d3885957d833', 801085, 'Love', '2026-11-23 05:52:37', '2026-11-23 05:52:37'),
(154, 'comment', '23eb4d2c-349a-4e9d-9f78-d3885957d833', 801040, 'Love', '2026-11-23 05:52:37', '2026-11-23 05:52:37'),
(155, 'comment', '1ed076c0-b06c-49dd-a9f6-1516be9c35bc', 801192, 'Wow', '2026-11-23 05:52:38', '2026-11-23 05:52:38');

-- --------------------------------------------------------

--
-- Table structure for table `commentreplies`
--

DROP TABLE IF EXISTS `commentreplies`;
CREATE TABLE IF NOT EXISTS `commentreplies` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` int NOT NULL DEFAULT '0',
  `code` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment_url` text COLLATE utf8mb4_unicode_ci,
  `date_comment` date NOT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commentreplies_comment_idx` (`comment_uuid`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=393 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commentreplies`
--

INSERT INTO `commentreplies` (`id`, `comment_uuid`, `status`, `code`, `comment`, `attachment_url`, `date_comment`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(26, 'undefined', 0, 701, 'ok', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:17:59', '2025-05-07 14:17:59'),
(27, '66e32530-9d62-43f2-b607-0279465e6c65', 0, 701, 'ok', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:18:33', '2025-05-07 14:18:33'),
(28, '66e32530-9d62-43f2-b607-0279465e6c65', 0, 701, 'fine', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:18:53', '2025-05-07 14:18:53'),
(56, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'new update22', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:40:17', '2025-06-01 05:14:55'),
(57, '3bf134ae-d974-4ea7-94fa-eee5464fe141', 0, 701, 'edit reply comment', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:41:58', '2025-06-01 05:11:03'),
(82, 'undefined', 0, 701, 'asvasv', NULL, '2025-05-28', 'Pedro yorpo', NULL, '2025-05-28 15:01:29', '2025-05-28 15:01:29'),
(83, 'undefined', 0, 701, 'asvasvasv', NULL, '2025-05-28', 'Pedro yorpo', NULL, '2025-05-28 15:01:39', '2025-05-28 15:01:39'),
(84, '8b4c00be-8da3-4dc2-8d2b-4aa6651f2165', 0, 701, 'sdvsdv', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:37:05', '2025-05-31 13:37:05'),
(85, '6ed81b14-a31d-49e6-b5be-cb36893c5cc6', 0, 701, 'new', NULL, '2025-05-31', 'Pedro yorpo', 'Pedro yorpo', '2025-05-31 13:44:49', '2025-06-01 05:14:48'),
(88, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'test2', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 01:54:46', '2025-06-01 04:46:06'),
(89, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'soon', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:01:49', '2025-06-01 05:08:08'),
(90, 'c3336fa3-8f88-40e5-91e4-16a91782305b', 0, 701, 'new', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:38:24', '2025-06-01 05:04:01'),
(91, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'update', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:42:50', '2025-06-02 12:23:03'),
(92, 'd29463ab-ecef-49b0-9c98-c0e8cd0983bc', 0, 701, 'fdvdf', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:44:14', '2025-06-01 02:44:14'),
(93, 'c3336fa3-8f88-40e5-91e4-16a91782305b', 0, 701, 'new2', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:49:07', '2025-06-01 05:04:09'),
(94, '7c4fb0b3-8486-4d02-ae4b-d2c54ca9f577', 0, 701, 'sdvsdv', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:52:44', '2025-06-01 02:52:44'),
(96, '3e359464-695f-4a43-9792-ca47c2c02f21', 0, 701, 'fgfgnfgn', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 05:12:26', '2025-06-01 05:12:32'),
(102, '342843dc-fa0c-4d51-a985-494a1a62e153', 0, 701, 'nice 2', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:20:28', '2025-10-25 10:46:23'),
(103, '95decad3-7b5f-481a-8766-c0aad7a1457f', 0, 701, 'reply222', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:23:31', '2025-10-25 05:55:47'),
(104, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'dvsvdfv', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:24:00', '2025-10-25 10:12:29'),
(105, '342843dc-fa0c-4d51-a985-494a1a62e153', 0, 701, 'nice2', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:25:40', '2025-10-25 05:25:40'),
(106, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'update 2 vdfvdf', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:49:34', '2025-10-25 07:00:59'),
(107, 'ca71d3fb-b37c-4ef2-b9da-c3ceef7b73b9', 0, 701, 'update', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:55:59', '2025-10-25 05:55:59'),
(108, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 701, 'reply 2', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:59:49', '2025-10-25 05:59:49'),
(110, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 701, 'reply amazing', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:10:58', '2025-10-25 06:10:58'),
(111, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'update', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 06:53:33', '2025-10-25 11:54:39'),
(112, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'DVD', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 07:05:34', '2025-10-25 07:05:34'),
(113, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 701, 'GNFGNFG', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 07:06:33', '2025-10-25 07:06:33'),
(115, 'baa2f528-2a18-4328-9972-9c0cb6793ade', 0, 701, 'dfvdfv', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 09:08:03', '2025-10-25 10:44:14'),
(118, '66676b9e-a0a9-4c12-85a0-12ae84a23b60', 0, 701, 'TEST', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:49:07', '2025-10-25 11:49:07'),
(120, '559cbe31-5cc0-4dca-ab0d-46d0c83630db', 0, 701, 'sdvsdv', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 12:48:11', '2025-11-09 12:48:11'),
(121, '559cbe31-5cc0-4dca-ab0d-46d0c83630db', 0, 701, 'd c  xc', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 12:48:18', '2025-11-09 12:48:18'),
(122, '559cbe31-5cc0-4dca-ab0d-46d0c83630db', 0, 701, 'd c  xc', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 12:48:18', '2025-11-09 12:48:18'),
(123, '559cbe31-5cc0-4dca-ab0d-46d0c83630db', 0, 701, 'test', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 12:48:25', '2025-11-09 12:48:25'),
(124, '6e40e1f1-e230-493e-8f50-2dadf08bf508', 0, 701, 'dcsdcsd', NULL, '2025-11-09', 'Pedro yorpo', 'Pedro yorpo', '2025-11-09 13:21:13', '2025-11-09 13:21:31'),
(125, 'c8f5ede1-4b93-446e-ac24-e14f7a734122', 0, 701, 'scsdcsd regergre', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 13:22:08', '2025-11-09 13:22:08'),
(126, '42356574-9b08-49dd-a841-ad41a4aedae5', 0, 701, 'sdvsdv', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 13:47:50', '2025-11-09 13:47:50'),
(127, '42356574-9b08-49dd-a841-ad41a4aedae5', 0, 701, ',yu,yu,', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 13:56:46', '2025-11-09 13:56:46'),
(128, '42356574-9b08-49dd-a841-ad41a4aedae5', 0, 701, 'dcsdcdsc', NULL, '2025-11-12', 'Pedro yorpo', NULL, '2025-11-12 12:40:16', '2025-11-12 12:40:16'),
(129, 'ee156432-aa54-454d-8eca-bca4b888b609', 0, 701, 'sdcsdcsdc', NULL, '2025-11-14', 'Pedro yorpo', 'Pedro yorpo', '2025-11-14 14:13:41', '2025-11-14 14:13:46'),
(132, 'f4ac2e5d-a9e2-4445-91d0-02c7eff82f7d', 0, 701, 'KTYK', NULL, '2026-04-22', 'Pedro yorpo', NULL, '2026-04-22 09:42:27', '2026-04-22 09:42:27');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

DROP TABLE IF EXISTS `companies`;
CREATE TABLE IF NOT EXISTS `companies` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `companycode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `website_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `control_numbers`
--

DROP TABLE IF EXISTS `control_numbers`;
CREATE TABLE IF NOT EXISTS `control_numbers` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `prefix` varchar(10) NOT NULL,
  `last_number` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module` (`module`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `control_numbers`
--

INSERT INTO `control_numbers` (`id`, `module`, `prefix`, `last_number`, `created_at`, `updated_at`) VALUES
(1, 'TRANSNO', 'TRN', 4, NULL, '2026-06-27 19:58:01'),
(2, 'PLAN_FEATURE', 'FID', 0, NULL, NULL),
(3, 'USER_SUBSCRIPTION', 'SUB', 520, NULL, '2026-11-23 05:53:11');

-- --------------------------------------------------------

--
-- Table structure for table `counters`
--

DROP TABLE IF EXISTS `counters`;
CREATE TABLE IF NOT EXISTS `counters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_number` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `counters`
--

INSERT INTO `counters` (`id`, `name`, `last_number`, `updated_at`) VALUES
(1, 'menu_transNo', 33, '2026-11-22 00:16:37');

-- --------------------------------------------------------

--
-- Table structure for table `email_codes`
--

DROP TABLE IF EXISTS `email_codes`;
CREATE TABLE IF NOT EXISTS `email_codes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_codes`
--

INSERT INTO `email_codes` (`id`, `email`, `code`, `created_at`, `updated_at`) VALUES
(8, 'nexsuz.official13@gmail.com', '2xrHlHc', NULL, NULL),
(9, 'nexsuz.official13@gmail.com', 'uRRTxKu', NULL, NULL),
(10, 'nexsuz.official13@gmail.com', 'lfVC51D', NULL, NULL),
(11, 'nexsuz.official13@gmail.com', 'AA2l39z', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_overrides`
--

DROP TABLE IF EXISTS `evaluation_overrides`;
CREATE TABLE IF NOT EXISTS `evaluation_overrides` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `evaluation_id` bigint UNSIGNED NOT NULL,
  `applied_id` bigint UNSIGNED NOT NULL,
  `question_id` bigint UNSIGNED DEFAULT NULL,
  `ai_score` tinyint UNSIGNED NOT NULL,
  `new_score` tinyint UNSIGNED NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_by_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `evaluation_overrides_evaluation_id_index` (`evaluation_id`),
  KEY `evaluation_overrides_applied_id_index` (`applied_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_settings`
--

DROP TABLE IF EXISTS `evaluation_settings`;
CREATE TABLE IF NOT EXISTS `evaluation_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weights` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `thresholds` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `passing_score` tinyint UNSIGNED NOT NULL DEFAULT '70',
  `competencies` text COLLATE utf8mb4_unicode_ci,
  `updated_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluation_settings_transno_unique` (`transNo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_settings`
--

INSERT INTO `evaluation_settings` (`id`, `transNo`, `weights`, `thresholds`, `passing_score`, `competencies`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, NULL, '{\"education\":15,\"experience\":25,\"skills\":30,\"answers\":20,\"competencies\":10}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 70, NULL, NULL, '2026-11-21 08:01:24', '2026-11-21 08:01:24'),
(6, 'TR-000006', '{\"education\":15,\"experience\":25,\"skills\":30,\"answers\":20,\"competencies\":80}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 90, NULL, '701', '2026-11-22 22:38:54', '2026-11-22 22:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `follower_code` bigint NOT NULL,
  `following_code` bigint NOT NULL,
  `follow_status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1510 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_code`, `following_code`, `follow_status`, `created_at`, `updated_at`) VALUES
(1262, 701, 720, 'pending', '2026-11-22 22:28:28', '2026-11-22 22:28:28'),
(1287, 870252, 123, 'pending', '2026-11-23 05:38:35', '2026-11-23 05:38:35'),
(1288, 870277, 123, 'pending', '2026-11-23 05:38:36', '2026-11-23 05:38:36'),
(1289, 870299, 123, 'pending', '2026-11-23 05:38:36', '2026-11-23 05:38:36'),
(1290, 870305, 123, 'pending', '2026-11-23 05:38:36', '2026-11-23 05:38:36'),
(1300, 870182, 123, 'pending', '2026-11-23 05:39:13', '2026-11-23 05:39:13'),
(1301, 870211, 123, 'pending', '2026-11-23 05:39:14', '2026-11-23 05:39:14'),
(1302, 870239, 123, 'pending', '2026-11-23 05:39:14', '2026-11-23 05:39:14'),
(1305, 721, 720, 'pending', '2026-11-23 05:39:25', '2026-11-23 05:39:25'),
(1345, 870292, 123, 'pending', '2026-11-23 05:40:10', '2026-11-23 05:40:10'),
(1346, 870332, 123, 'pending', '2026-11-23 05:40:10', '2026-11-23 05:40:10'),
(1347, 870345, 123, 'pending', '2026-11-23 05:40:11', '2026-11-23 05:40:11'),
(1348, 870371, 123, 'pending', '2026-11-23 05:40:11', '2026-11-23 05:40:11'),
(1398, 870254, 123, 'pending', '2026-11-23 05:41:32', '2026-11-23 05:41:32'),
(1399, 870278, 123, 'pending', '2026-11-23 05:41:32', '2026-11-23 05:41:32'),
(1400, 870296, 123, 'pending', '2026-11-23 05:41:33', '2026-11-23 05:41:33'),
(1401, 870329, 123, 'pending', '2026-11-23 05:41:33', '2026-11-23 05:41:33'),
(1410, 870216, 123, 'pending', '2026-11-23 05:41:57', '2026-11-23 05:41:57'),
(1411, 870218, 123, 'pending', '2026-11-23 05:41:58', '2026-11-23 05:41:58'),
(1412, 870251, 123, 'pending', '2026-11-23 05:41:58', '2026-11-23 05:41:58'),
(1413, 870255, 123, 'pending', '2026-11-23 05:41:58', '2026-11-23 05:41:58'),
(1455, 870223, 123, 'pending', '2026-11-23 05:51:53', '2026-11-23 05:51:53'),
(1506, 870234, 123, 'pending', '2026-11-23 05:53:06', '2026-11-23 05:53:06'),
(1507, 870318, 123, 'pending', '2026-11-23 05:53:07', '2026-11-23 05:53:07');

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

DROP TABLE IF EXISTS `interviews`;
CREATE TABLE IF NOT EXISTS `interviews` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `applied_id` bigint UNSIGNED NOT NULL,
  `transNo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration_min` smallint UNSIGNED NOT NULL DEFAULT '30',
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `interviewer_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interviewer_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `score` tinyint UNSIGNED DEFAULT NULL,
  `result` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interviews_applied_id_index` (`applied_id`),
  KEY `interviews_transno_index` (`transNo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobposting`
--

DROP TABLE IF EXISTS `jobposting`;
CREATE TABLE IF NOT EXISTS `jobposting` (
  `job_id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` int NOT NULL,
  `company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits` text COLLATE utf8mb4_unicode_ci,
  `currency` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_salary` decimal(12,2) DEFAULT NULL,
  `max_salary` decimal(12,2) DEFAULT NULL,
  `job_position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_about` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `qualification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `work_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `recordstatus` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_online` tinyint(1) DEFAULT '0',
  `vacancies` int UNSIGNED DEFAULT '1',
  `employment_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experience_level` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_period` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `education` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required_skills` text COLLATE utf8mb4_unicode_ci,
  `preferred_qualifications` text COLLATE utf8mb4_unicode_ci,
  `application_deadline` date DEFAULT NULL,
  `allow_cv` tinyint(1) NOT NULL DEFAULT '1',
  `application_limit` int UNSIGNED DEFAULT NULL,
  `min_pass_score` tinyint UNSIGNED DEFAULT NULL,
  `auto_screen` tinyint(1) NOT NULL DEFAULT '0',
  `template_id` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`job_id`),
  KEY `jobposting_template_id_index` (`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobposting`
--

INSERT INTO `jobposting` (`job_id`, `transNo`, `code`, `company`, `role_code`, `job_name`, `location`, `benefits`, `currency`, `min_salary`, `max_salary`, `job_position`, `job_description`, `job_about`, `qualification`, `work_type`, `job_image`, `fullname`, `created_at`, `updated_at`, `recordstatus`, `is_online`, `vacancies`, `employment_type`, `experience_level`, `salary_period`, `education`, `required_skills`, `preferred_qualifications`, `application_deadline`, `allow_cv`, `application_limit`, `min_pass_score`, `auto_screen`, `template_id`) VALUES
(148, 'TR-000006', 701, 'ABC Company', 'DEF-MASTERADMIN', 'Customer Service Representative (CSR)', 'Cebu City', 'Competitive salary package.\r\nPerformance-based incentives and bonuses.\r\nPaid training.\r\nNight shift differential.\r\n13th-month pay.\r\nPaid vacation and sick leaves.\r\nGovernment-mandated benefits (SSS, PhilHealth, and Pag-IBIG).\r\nHMO and medical coverage.\r\nCareer growth and promotion opportunities.\r\nCompany-sponsored activities and rewards.\r\nTransportation or meal allowance (depending on the company).\r\nOvertime pay when applicable.', 'PHP', '123333.00', '244444.00', 'TEACHER', '- Answer incoming customer calls and inquiries.\r\n- Provide accurate information about products and services.\r\n- Handle customer complaints and resolve issues professionally.\r\n- Update customer information in the company system.\r\n- Maintain a positive and professional attitude.\r\n- Ensure customer satisfaction.', 'A Call Center Agent is responsible for assisting customers through phone calls, live chat, or email. The role involves answering inquiries, resolving complaints, providing product information, troubleshooting basic issues, and ensuring customer satisfaction.', '- Good verbal and written communication skills.\r\n- Basic computer knowledge.\r\n- Ability to handle different types of customers.\r\n- Willing to work on shifting schedules.', 'Work From Home', '/storage/uploads/701/JobPosting/63470b7b-8e10-4368-b73f-97f6bd2f6324/1795272288.jpg', 'Pedro yorpo', '2026-11-21 06:44:48', '2026-11-21 06:45:13', 'active', 1, 2, 'Full-time', 'Mid-Senior level', 'Monthly', 'At least Senior High School graduate.    High School graduate (old curriculum) may also be accepted.    College level, undergraduate, or college graduate is an advantage.    Fresh graduates are welcome to apply.    No previous call center experience requi', '[\"Excellent\",\"Patient\"]', NULL, '2026-12-31', 1, 12, 90, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_templates`
--

DROP TABLE IF EXISTS `job_templates`;
CREATE TABLE IF NOT EXISTS `job_templates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sections` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_templates_status_deleted_at_index` (`status`,`deleted_at`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_templates`
--

INSERT INTO `job_templates` (`id`, `name`, `description`, `theme`, `sections`, `is_default`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Classic', 'The standard NEXSUZ job layout.', '{\"preset\":\"custom\",\"primary\":\"#334155\",\"accent\":\"#0ea5e9\",\"background\":\"#f1f5f9\",\"surface\":\"#ffffff\",\"text\":\"#1b2a4a\",\"font\":\"tahoma\",\"radius\":6,\"buttonStyle\":\"filled\",\"buttonShape\":\"square\",\"headerStyle\":\"solid\",\"backgroundStyle\":\"solid\",\"layout\":\"two_column\"}', '[{\"key\":\"summary\",\"label\":\"About the job\",\"enabled\":true},{\"key\":\"responsibilities\",\"label\":\"Job description\",\"enabled\":true},{\"key\":\"qualifications\",\"label\":\"Qualifications\",\"enabled\":true},{\"key\":\"education\",\"label\":\"Education\",\"enabled\":true},{\"key\":\"skills\",\"label\":\"Required skills\",\"enabled\":true},{\"key\":\"benefits\",\"label\":\"Benefits and perks\",\"enabled\":true},{\"key\":\"questions\",\"label\":\"Screening questions\",\"enabled\":true}]', 1, 'published', NULL, '701', '2026-11-21 07:45:15', '2026-11-22 23:26:14', NULL),
(2, 'Classic (copy)', 'The standard NEXSUZ job layout.', '{\"preset\":\"classic\",\"primary\":\"#0a66c2\",\"accent\":\"#7a5af8\",\"background\":\"#f3f2ef\",\"surface\":\"#ffffff\",\"text\":\"#1b2a4a\",\"font\":\"verdana\",\"radius\":14,\"buttonStyle\":\"filled\",\"buttonShape\":\"pill\",\"headerStyle\":\"gradient\",\"backgroundStyle\":\"solid\",\"layout\":\"single\"}', '[{\"key\":\"summary\",\"label\":\"About the job\",\"enabled\":true},{\"key\":\"responsibilities\",\"label\":\"Job description\",\"enabled\":true},{\"key\":\"qualifications\",\"label\":\"Qualifications\",\"enabled\":true},{\"key\":\"education\",\"label\":\"Education\",\"enabled\":true},{\"key\":\"skills\",\"label\":\"Required skills\",\"enabled\":true},{\"key\":\"benefits\",\"label\":\"Benefits and perks\",\"enabled\":true},{\"key\":\"questions\",\"label\":\"Screening questions\",\"enabled\":true}]', 0, 'draft', '701', '701', '2026-11-21 07:53:32', '2026-11-21 07:53:38', '2026-11-21 07:53:38'),
(3, 'Classic (copy)', 'The standard NEXSUZ job layout.', '{\"preset\":\"custom\",\"primary\":\"#334155\",\"accent\":\"#0ea5e9\",\"background\":\"#f1f5f9\",\"surface\":\"#ffffff\",\"text\":\"#1b2a4a\",\"font\":\"tahoma\",\"radius\":6,\"buttonStyle\":\"filled\",\"buttonShape\":\"square\",\"headerStyle\":\"solid\",\"backgroundStyle\":\"solid\",\"layout\":\"two_column\"}', '[{\"key\":\"summary\",\"label\":\"About the job\",\"enabled\":true},{\"key\":\"responsibilities\",\"label\":\"Job description\",\"enabled\":true},{\"key\":\"qualifications\",\"label\":\"Qualifications\",\"enabled\":true},{\"key\":\"education\",\"label\":\"Education\",\"enabled\":true},{\"key\":\"skills\",\"label\":\"Required skills\",\"enabled\":true},{\"key\":\"benefits\",\"label\":\"Benefits and perks\",\"enabled\":true},{\"key\":\"questions\",\"label\":\"Screening questions\",\"enabled\":true}]', 0, 'draft', '701', '701', '2026-11-22 23:26:33', '2026-11-22 23:26:38', '2026-11-22 23:26:38');

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
CREATE TABLE IF NOT EXISTS `menus` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` int NOT NULL,
  `desc_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `routes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `transNo`, `desc_code`, `description`, `icon`, `class`, `routes`, `sort`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'top_navigation', 'Home', 'fas fa-home', 'class-home', 'home', 1, 'A', NULL, 'Pedro yorpo', NULL, '2025-10-04 03:52:29'),
(2, 2, 'top_navigation', 'System', 'fa-solid fa-file', 'fa-regular fa-file', '#', 15, 'A', NULL, 'Pedro yorpo', NULL, '2025-10-04 03:44:03'),
(5, 3, 'top_navigation', 'Messaging', 'fas fa-comment', 'fas fa-comment', 'message', 2, 'A', NULL, 'Pedro yorpo', NULL, '2025-10-04 03:44:11'),
(6, 4, 'top_navigation', 'Dashboard', 'fas fa-tachometer-alt', 'dashboard', 'admin-dashboard', 1, 'A', NULL, 'Pedro yorpo', NULL, '2026-11-19 08:35:48'),
(7, 5, 'top_navigation', 'My Nexsuz', 'fas fa-globe', '-', 'network', 3, 'A', NULL, 'Pedro yorpo', NULL, '2025-12-10 12:56:44'),
(10, 8, 'top_navigation', 'Jobs', 'fa-solid fa-briefcase', 'class-jobs', 'recommended-jobs/:transNo', 4, 'A', NULL, 'Pedro yorpo', NULL, '2025-12-17 12:50:09'),
(11, 9, 'top_navigation', 'Job Vacancies', 'fa-solid fa-bullhorn', 'class-jobs', 'vacancies', 4, 'A', NULL, 'Pedro yorpo', NULL, '2025-12-24 03:55:56'),
(12, 10, 'top_navigation', 'Candidates', 'fa-solid fa-users', 'class-candidates', 'candidates', 5, 'A', NULL, 'Pedro yorpo', NULL, '2025-12-20 12:35:13'),
(39, 23, 'top_navigation', 'Client Dashboard', 'fas fa-chart-line', 'icon-dashboard', 'client-dashboard', 1, 'I', 'Pedro yorpo', 'Pedro yorpo', NULL, '2026-11-19 08:36:17'),
(40, 24, 'top_navigation', 'Scheduling Interviews', 'fa-solid fa-user-tie fa-2x', 'fa-solid fa-user-tie fa-2x', 'interviews', 5, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, '2025-12-29 05:10:42'),
(41, 25, 'top_navigation', 'Account', 'fa-solid fa-building', 'clients-company', 'accountmanagement', 8, 'I', 'Pedro yorpo', 'Pedro yorpo', NULL, '2026-11-21 18:13:46'),
(42, 26, 'top_navigation', 'Reports / Analytics', 'fa-solid fa-chart-line', 'reports', 'reports', 9, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(45, 29, 'top_navigation', 'Subscription Plans', 'layers', '-', 'subscriptionplans', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, '2026-11-21 16:11:33'),
(46, 30, 'top_navigation', 'Feature Management', 'blocks', '-', 'featuremanagement', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(47, 31, 'top_navigation', 'Plan Feature Assignment', 'list-checks', '-', 'planfeatureassignment', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(48, 32, 'top_navigation', 'User Subscription Monitoring', 'chart-no-axes-combined', '-', 'usersubscriptionmonitoring', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(49, 33, 'top_navigation', 'Subscription Reports', 'file-chart-column', '-', 'subscriptionreports', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` int DEFAULT NULL,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1694 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `code`, `sender_id`, `receiver_id`, `message`, `created_at`, `updated_at`, `is_read`) VALUES
(890, 701, 91, 102, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:22', '2025-05-03 11:50:39', 1),
(897, 701, 91, 91, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:13:01', '2025-04-26 13:27:38', 1),
(911, 701, 91, 91, 'test', '2025-04-26 08:50:41', '2025-04-26 13:27:38', 1),
(917, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 09:25:55', '2025-04-26 13:27:38', 1),
(924, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 12:32:08', '2025-04-26 13:27:38', 1),
(927, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 13:26:24', '2025-04-26 13:27:38', 1),
(977, 711, 102, 102, 'bossing', '2025-05-15 00:37:28', '2025-05-15 00:37:34', 1),
(992, 701, 91, 102, 'Boss wa jud ka code Karun na week boss Kay Wala Koy wifi nibalhin ko og boarding house', '2025-06-17 09:02:05', '2025-07-17 14:37:14', 1),
(1016, 701, 91, 91, 'test', '2025-10-04 04:21:56', '2025-10-13 13:01:31', 1),
(1020, 701, 91, 91, 'fvdfvdfv', '2025-10-10 12:47:26', '2025-10-13 13:01:31', 1),
(1028, 701, 91, 91, 'cscsdc', '2025-11-08 14:08:31', '2025-11-09 06:07:25', 1),
(1029, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-11-09 06:06:44', '2025-11-09 06:07:15', 1),
(1030, 701, 91, 141, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-11-09 06:07:03', '2025-12-08 13:13:26', 1),
(1140, 701, 91, 91, 'dfbfdb', '2025-12-19 14:31:43', '2026-04-28 23:30:54', 1);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2024_11_02_055944_create_resources_table', 1),
(6, '2024_11_02_060040_create_companies_table', 1),
(7, '2024_11_02_060201_create_roles_table', 1),
(8, '2024_11_02_060556_create_menus_table', 1),
(9, '2024_11_02_060737_create_submenus_table', 1),
(10, '2024_11_02_064146_create_roleaccesssubmenus_table', 1),
(11, '2024_11_02_065922_create_roleaccessmenus_table', 1),
(12, '2024_11_27_054345_create_email_codes_table', 1),
(13, '2024_12_04_070123_create_userprofiles_table', 1),
(14, '2024_12_04_070856_create_usercapabilities_table', 1),
(15, '2024_12_04_070954_create_usereducations_table', 1),
(16, '2024_12_04_093133_create_usertrainings_table', 1),
(17, '2024_12_04_093322_create_userseminars_table', 1),
(18, '2024_12_05_142707_create_usercertificates_table', 1),
(19, '2024_12_05_144202_create_useremploymentrecords_table', 1),
(20, '2026_11_16_130000_add_unique_reaction_per_user_to_reactionpost', 2),
(21, '2026_11_16_130100_create_commentreactions_table', 3),
(22, '2026_11_16_130200_add_attachment_url_and_indexes_to_comments', 4),
(23, '2026_11_18_000000_create_user_notification_settings_table', 5),
(24, '2026_11_19_000000_create_user_blocks_table', 6),
(25, '2026_11_20_000000_add_job_posting_settings', 7),
(26, '2026_11_21_000000_create_job_templates', 8),
(27, '2026_11_22_000000_create_recruitment_pipeline', 9),
(28, '2026_11_22_100000_create_subscription_features_table', 10),
(29, '2026_11_22_200000_create_app_settings_table', 11),
(30, '2026_11_23_000000_create_user_resumes_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_phone_index` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('elizabethpunay01@gmail.com', 'uRWUTv10guhFrmz6SHG6C5PJKlESZdOR5XAQ7UiDSyuwrCSt3JpMryPzMzRgwsgU', '2026-11-22 21:05:54'),
('kimtaejung@163.com', 'zzKSiUhK1I7Ym8HsqypT3IKdpNupzlPSEZuYJAYKnrJe2teiYkNWevsRjRbodO4L', '2026-02-03 09:44:42'),
('pedroyorpo17@gmail.com', 'LeHf7TSOounBdOt3W49wxfAeMablrMpZhszVACeMM3tXrNuyl9s6WuZo7xanOg5y', '2025-09-10 14:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2515 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1596, 'App\\Models\\User', 108, 'Personal Access Token', 'd51c3cb9fd9d1cc72d871eaaf1bb22c9f7f936ea719ea2512b2a373c3e0d97a5', '[\"*\"]', '2025-09-20 06:41:54', NULL, '2025-09-20 06:41:50', '2025-09-20 06:41:54'),
(2194, 'App\\Models\\User', 141, 'Personal Access Token', 'aef91d9e744259abd2e43be7c341b3183ec303e2ce94f7aa089fd1989a2b9e4d', '[\"*\"]', '2026-06-20 23:31:30', NULL, '2026-06-20 22:53:51', '2026-06-20 23:31:30'),
(2456, 'App\\Models\\User', 8274, 'Personal Access Token', 'b98ac8f71d527d776ce5c6dcfa78bcde86120a066cde84b363229e51388b2bc6', '[\"*\"]', '2026-11-22 21:10:28', NULL, '2026-11-22 20:49:29', '2026-11-22 21:10:28'),
(2471, 'App\\Models\\User', 91, 'Personal Access Token', 'fd5bce7ea44f8670e485531884a800cb49bbc052a47dbbda3cbb533922b7460e', '[\"*\"]', '2026-11-23 05:34:09', NULL, '2026-11-22 23:22:36', '2026-11-23 05:34:09'),
(2490, 'App\\Models\\User', 8822, 'Personal Access Token', '121ac3e35df1bb80b8dcfb3217769e324f1ff0d9598701ea6713e326ae7ef3e2', '[\"*\"]', '2026-11-23 06:40:49', NULL, '2026-11-23 05:13:28', '2026-11-23 06:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` int NOT NULL,
  `posts_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `caption` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_posts_uuid_unique` (`posts_uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=2174 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `code`, `posts_uuid`, `transNo`, `caption`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1889, 701, '9f706ed1-ba67-4193-9f91-42ba47041f4a', 1, '🚀 **ONE TEAM. ONE VISION. INFINITE POSSIBILITIES!**\r\nMeet the people behind **NEXSUZ!** 💙\r\n\r\nGreat technology starts with great people. At NEXSUZ, we are more than just a team — we are creators, problem-solvers, innovators, and dreamers working together to turn ideas into meaningful digital solutions.\r\n💻 **We Innovate** – Creating smarter technology for modern businesses.\r\n🤝 **We Collaborate** – Sharing ideas, supporting one another, and growing together.\r\n🔥 **We Take Action** – Turning challenges into opportunities and ideas into reality.\r\n🌱 **We Grow Together** – Learning, improving, and building a better future through technology.\r\nEvery achievement starts with teamwork, and every big dream starts with people who believe in it.\r\n\r\n**This is NEXSUZ. This is our team. This is just the beginning!** 🚀\r\n💬 What does teamwork mean to you? Share your thoughts in the comments!\r\n\r\n❤️ Like, share, and follow our page to be part of our journey.\r\n\r\n#NEXSUZ #TeamNEXSUZ #TogetherWeInnovate #Teamwork #DigitalInnovation #SoftwareDevelopment #BuildingTheFuture', 1, 'Pedro yorpo', 'Pedro yorpo', '2026-11-22 21:29:52', '2026-11-22 21:32:11');

-- --------------------------------------------------------

--
-- Table structure for table `reactionpost`
--

DROP TABLE IF EXISTS `reactionpost`;
CREATE TABLE IF NOT EXISTS `reactionpost` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int DEFAULT NULL,
  `code` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reaction` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `create_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `reactionpost_post_user_unique` (`post_id`,`code`(64))
) ENGINE=InnoDB AUTO_INCREMENT=1001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reactionpost`
--

INSERT INTO `reactionpost` (`id`, `post_id`, `code`, `reaction`, `updated_at`, `create_at`) VALUES
(9, 70, '701', 'Love', '2025-11-09 01:00:58', '2025-11-09 01:00:58'),
(10, 69, '701', 'Wow', '2025-10-08 14:02:16', '2025-10-08 14:02:16'),
(11, 67, '701', 'Love', '2025-10-18 14:01:41', '2025-10-18 14:01:41'),
(12, 66, '701', 'Like', '2025-10-19 12:45:27', '2025-10-19 12:45:27'),
(13, 68, '701', 'Love', '2025-10-10 13:00:06', '2025-10-10 13:00:06'),
(14, 65, '701', 'Love', '2025-10-10 12:59:44', '2025-10-10 12:59:44'),
(15, 64, '701', 'Love', '2025-10-06 12:35:34', '2025-10-06 12:35:34'),
(23, 44, '701', 'Love', '2025-10-19 14:45:51', '2025-10-19 14:45:51'),
(24, 39, '701', 'Love', '2025-10-06 12:35:52', '2025-10-06 12:35:52'),
(25, 1, '701', 'Haha', '2025-11-08 14:23:28', '2025-11-08 14:23:28'),
(26, 72, '701', 'Love', '2025-10-20 13:57:52', '2025-10-20 13:57:52'),
(29, 73, '701', 'Like', '2025-10-19 13:44:36', '2025-10-19 13:44:36'),
(31, 74, '701', 'Love', '2025-10-16 21:56:02', '2025-10-16 21:56:02'),
(33, 76, '701', 'Love', '2025-10-25 10:46:07', '2025-10-25 10:46:07'),
(34, 75, '701', 'Love', '2025-11-08 14:23:02', '2025-11-08 14:23:02'),
(36, 80, '701', 'Haha', '2025-10-25 00:48:04', '2025-10-25 00:48:04'),
(37, 79, '701', 'Like', '2025-10-25 00:41:18', '2025-10-25 00:41:18'),
(38, 88, '701', 'Love', '2025-10-25 00:47:50', '2025-10-25 00:47:50'),
(39, 94, '701', 'Haha', '2025-10-25 00:48:28', '2025-10-25 00:48:28'),
(40, 85, '701', 'Love', '2025-10-25 00:50:41', '2025-10-25 00:50:41'),
(41, 92, '701', 'Like', '2025-10-28 04:35:09', '2025-10-28 04:35:09'),
(44, 96, '701', 'Love', '2026-03-23 07:22:18', '2026-03-23 07:22:18'),
(45, 86, '701', 'Like', '2025-10-25 08:41:56', '2025-10-25 08:41:56'),
(46, 87, '701', 'Like', '2025-10-25 08:41:58', '2025-10-25 08:41:58'),
(47, 84, '701', 'Wow', '2025-10-25 08:42:03', '2025-10-25 08:42:03'),
(48, 83, '701', 'Like', '2025-10-25 08:42:07', '2025-10-25 08:42:07'),
(49, 82, '701', 'Love', '2025-10-25 08:42:09', '2025-10-25 08:42:09'),
(50, 81, '701', 'Wow', '2025-10-25 08:42:11', '2025-10-25 08:42:11'),
(51, 78, '701', 'Angry', '2025-10-25 08:42:18', '2025-10-25 08:42:18'),
(52, 77, '701', 'Like', '2025-10-25 08:42:20', '2025-10-25 08:42:20'),
(53, 93, '701', 'Wow', '2025-10-28 04:35:04', '2025-10-28 04:35:04'),
(61, 97, '701', 'Love', '2025-10-25 11:39:24', '2025-10-25 11:39:24'),
(62, 91, '701', 'Love', '2025-10-28 04:35:15', '2025-10-28 04:35:15'),
(63, 89, '701', 'Sad', '2025-10-28 04:35:25', '2025-10-28 04:35:25'),
(64, 90, '701', 'Love', '2025-10-30 13:20:33', '2025-10-30 13:20:33'),
(71, 113, '701', 'Wow', '2025-11-09 13:48:06', '2025-11-09 13:48:06'),
(73, 115, '701', 'Sad', '2025-11-19 13:46:16', '2025-11-19 13:46:16'),
(74, 108, '701', 'Haha', '2025-11-16 03:01:18', '2025-11-16 03:01:18'),
(78, 112, '701', 'Love', '2025-11-23 09:16:33', '2025-11-23 09:16:33'),
(79, 123, '701', 'Love', '2025-11-25 22:06:14', '2025-11-25 22:06:14'),
(80, 120, '701', 'Haha', '2025-11-29 14:42:30', '2025-11-29 14:42:30'),
(81, 124, '701', 'Love', '2025-12-02 13:44:13', '2025-12-02 13:44:13'),
(88, 129, '701', 'Love', '2026-02-17 15:22:58', '2026-02-17 15:22:58'),
(196, 489, '701', 'Love', '2026-11-15 21:27:41', '2026-11-15 21:27:39'),
(867, 1889, '701', 'Love', '2026-11-22 21:30:17', '2026-11-22 21:30:17');

-- --------------------------------------------------------

--
-- Table structure for table `reactions`
--

DROP TABLE IF EXISTS `reactions`;
CREATE TABLE IF NOT EXISTS `reactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` int NOT NULL,
  `post_uuidOrUind` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reaction` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reactions`
--

INSERT INTO `reactions` (`id`, `code`, `post_uuidOrUind`, `reaction`, `created_at`) VALUES
(5, 711, '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 'Heart', '2025-05-16 01:31:30'),
(15, 711, 'c225d726-722c-4949-a7a8-8b2f23a360d2', 'Angry', '2025-06-06 04:42:07'),
(20, 701, '95af83cd-04a1-4121-9c6c-4406fd01fb63', 'Love', '2025-06-30 12:18:38'),
(23, 701, 'e4705480-0469-4423-b261-827f672bd958', 'Haha', '2025-10-28 14:01:52'),
(24, 701, '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 'Love', '2025-05-25 04:13:35'),
(25, 701, '35e045fe-7da2-4236-aed7-b2091b73291a', 'Like', '2025-05-25 04:30:10'),
(26, 701, 'undefined', 'Sad', '2025-05-31 01:29:54'),
(44, 701, '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758,d6eb1ba6-58c6-43b3-8c0c-950de1916a33', '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758,d6eb1ba6-58c6-43b3-8c0c-950de1916a33', '2025-05-31 00:33:18'),
(45, 701, '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758', 'Love', '2025-06-02 08:06:47'),
(46, 701, 'd6eb1ba6-58c6-43b3-8c0c-950de1916a33', 'Like', '2025-05-31 13:39:30'),
(47, 701, '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 'Like', '2025-05-26 14:49:52'),
(48, 701, '399a6fe7-d1ac-42bf-825c-648294be889f', 'Love', '2025-05-26 14:51:04'),
(51, 711, 'undefined', 'Love', '2025-05-27 00:23:24'),
(52, 711, '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 'Love', '2025-06-06 04:40:50'),
(54, 701, '[object Object]', '[object Object]', '2025-05-31 01:22:01'),
(60, 711, 'aab5830c-ca18-4058-9a18-9e736e874518', 'Love', '2025-06-06 04:42:52'),
(63, 701, '4859c8d7-55d4-4d93-b74b-93b091198585', 'Love', '2025-07-01 14:54:28'),
(64, 701, 'b493ee6a-3753-4ccf-ad76-cc69aabb17b5', 'Love', '2025-07-01 14:55:11'),
(66, 701, '97c65aee-3a27-4e0c-8067-0689f9e8609b', 'Haha', '2025-07-25 03:32:07'),
(68, 701, '928785b3-af8d-4124-a889-a40fd4da9c85', 'Love', '2025-07-17 14:03:07'),
(76, 701, '7267ee5a-dee4-4fe6-8c50-76b1cf4ddbd0', 'Love', '2025-09-03 09:39:31'),
(77, 701, 'f07fdbc5-70aa-486b-815c-cafe9d97247d', 'Like', '2025-09-05 12:48:10'),
(79, 701, 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 'Love', '2025-11-09 01:01:05'),
(83, 701, 'f2191956-3576-48c8-90a9-c79d6062149f', 'Love', '2025-10-04 12:03:44'),
(85, 701, '764f512d-88e2-46e6-971f-f249b3966b27', 'Love', '2025-10-05 10:41:26'),
(86, 701, '129c8393-8d44-4f06-89fd-836ebe09bb72', 'Love', '2025-10-05 11:01:18'),
(88, 701, 'c0ccfb5d-7717-4605-8ca3-346abd8ea2b5', 'Haha', '2025-10-09 12:52:46'),
(90, 701, '62810936-01c5-4e39-aaec-fe563e84d26a', 'Wow', '2025-10-25 01:41:55'),
(91, 701, 'af2aa354-4c34-40c6-b48f-aadaf67f38bc', 'Love', '2025-10-25 10:47:57'),
(92, 701, '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 'Haha', '2025-11-09 13:34:14'),
(93, 701, '684b81eb-0baa-4975-88f5-361d48feef46', 'Haha', '2025-11-12 12:40:58'),
(94, 701, 'd4b0026a-f768-4d98-b0c8-2a17e27b7c61', 'Love', '2025-11-12 12:41:44'),
(96, 701, '0dd91479-ad18-4fe1-9d31-368a4d3fd517', 'Wow', '2025-11-15 11:35:11'),
(97, 701, '970ea541-a10d-4358-b006-1932fa7d2701', 'Haha', '2025-11-23 09:16:49'),
(98, 701, '26e8fd68-87c0-4076-95bc-f402a3d77065', 'Love', '2025-12-19 15:15:51'),
(99, 701, 'dabfdd4a-0bf1-4ba3-94ff-65eae91e0301', 'Love', '2026-02-17 15:23:12');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
CREATE TABLE IF NOT EXISTS `resources` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` int NOT NULL,
  `fname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `age` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `profession` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `industry` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `companywebsite` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coverphoto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default.jpg',
  `date_birth` date DEFAULT NULL,
  `home_country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resumepdf` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_fname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_lname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_mname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_contact_no` int DEFAULT NULL,
  `h1_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_address1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_address2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_companycode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `h1_rolecode` int DEFAULT NULL,
  `h1_designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resources_code_unique` (`code`),
  UNIQUE KEY `resources_email_unique` (`email`),
  UNIQUE KEY `resources_h1_email_unique` (`h1_email`)
) ENGINE=InnoDB AUTO_INCREMENT=1413 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `code`, `fname`, `lname`, `mname`, `fullname`, `contact_no`, `age`, `email`, `profession`, `company`, `industry`, `companywebsite`, `role_code`, `designation`, `coverphoto`, `date_birth`, `home_country`, `current_location`, `profile_picture`, `resumepdf`, `h1_fname`, `h1_lname`, `h1_mname`, `h1_fullname`, `h1_contact_no`, `h1_email`, `h1_address1`, `h1_address2`, `h1_city`, `h1_province`, `h1_postal_code`, `h1_companycode`, `h1_rolecode`, `h1_designation`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(89, 701, 'Admin', 'Nexsuz', '', 'Admin Nexsuz', '+639999990909', NULL, 'pedroyorpo17@gmail.com', 'programmer', 'Nexsuz', 'Civil Services (Government, Armed Forces)', 'nexsuz.com', 'DEF-MASTERADMIN', 'position', 'http://localhost:8000/storage/uploads/701/coverphoto/1df5d1df-1aed-4a89-b441-41fb95d4edc1/1795099544.jpg', '2000-08-29', 'USA', 'Canada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-27 05:28:16', '2026-11-22 22:26:19'),
(99, 711, 'human$', 'crazy', '', 'Human$ crazy', '1234567890', NULL, 'reinjunelaride34@gmail.com', NULL, 'VFI', 'Laboratory', 'mail.com', 'DEF-MASTERADMIN', 'PROFESSIONAL', 'default.jpg', '2025-03-14', 'Canada', 'Canada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-01 21:12:05', '2025-03-01 08:33:40'),
(102, 714, 'TJ', 'Kim', '', 'TJ Kim', '+8618321452737', NULL, 'kimtaejung@163.com', NULL, 'Nexsuz China', 'Human Resources Management/Consultancy', 'nexsuz.com', 'DEF-CLIENT', 'CEO', 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-20 06:39:59', '2025-09-20 06:39:59'),
(111, 718, 'Nexsuz User', '-', '', 'Nexsuz User -', '+639938151373', '1', 'nexsuz.official13@gmail.com', 'Nexsuz', NULL, NULL, NULL, 'DEF-ADMIN', NULL, 'https://exploredition.com/storage/app/public/uploads/718/coverphoto/13eba524-7d6a-4e53-b784-85ad728f8d52/1765201178.PNG', '2000-10-17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-30 14:36:47', '2025-12-08 13:39:38'),
(843, 720, 'Elizabeth', 'Punay', '', 'Elizabeth Punay', '+6309994589906', '1', 'elizabethpunay01@gmail.com', 'Teacher', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', '1999-12-07', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-22 20:46:47', '2026-11-22 21:04:10'),
(862, 721, 'Pedro', 'Yorpo', '', 'Pedro Yorpo', '09938151373', '1', 'pedroyorpo22@gmail.com', 'FullStack Developer', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', '1999-09-01', 'Philippines', 'Philippines', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-11-22 23:32:06', '2026-11-23 05:36:54');

-- --------------------------------------------------------

--
-- Table structure for table `roleaccessmenus`
--

DROP TABLE IF EXISTS `roleaccessmenus`;
CREATE TABLE IF NOT EXISTS `roleaccessmenus` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `rolecode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `menus_id` int NOT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=298 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roleaccessmenus`
--

INSERT INTO `roleaccessmenus` (`id`, `rolecode`, `transNo`, `menus_id`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(21, 'DEF-MASTERADMIN', 10, 2, NULL, NULL, NULL, NULL),
(22, 'DEF-MASTERADMIN', 11, 5, NULL, NULL, NULL, NULL),
(23, 'DEF-MASTERADMIN', 12, 6, NULL, NULL, NULL, NULL),
(24, 'DEF-MASTERADMIN', 13, 7, NULL, NULL, NULL, NULL),
(25, 'DEF-MASTERADMIN', 14, 8, NULL, NULL, NULL, NULL),
(32, 'DEF-SUPERADMIN', 21, 1, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(33, 'DEF-SUPERADMIN', 22, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(34, 'DEF-SUPERADMIN', 23, 7, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(35, 'DEF-SUPERADMIN', 24, 8, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(40, 'DEF-SUPERADMIN', 29, 10, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(42, 'DEF-MASTERADMIN', 31, 11, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(43, 'DEF-USERS', 32, 1, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(44, 'DEF-USERS', 33, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(45, 'DEF-USERS', 34, 7, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(46, 'DEF-USERS', 35, 10, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(50, 'DEF-CLIENT', 39, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(51, 'DEF-CLIENT', 40, 7, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(55, 'DEF-SUPERADMIN', 44, 12, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(56, 'DEF-MASTERADMIN', 45, 12, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(57, 'DEF-CLIENT', 46, 12, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(60, 'DEF-CLIENT', 49, 11, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(61, 'DEF-MASTERADMIN', 50, 32, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(65, 'DEF-SUPERADMIN', 54, 10, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(70, 'DEF-USERS', 59, 9, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(71, 'DEF-MASTERADMIN', 60, 9, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(72, 'DEF-CLIENT', 61, 9, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(74, 'DEF-SUPERADMIN', 63, 6, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(75, 'DEF-CLIENT', 64, 39, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(76, 'DEF-SUPERADMIN', 65, 2, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(77, 'DEF-SUPERADMIN', 66, 11, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(264, 'DEF-ADMIN', 119, 6, NULL, NULL, '2025-12-21 02:33:55', '2025-12-21 02:33:55'),
(265, 'DEF-ADMIN', 120, 2, NULL, NULL, '2025-12-21 02:34:41', '2025-12-21 02:34:41'),
(275, 'DEF-ADMIN', 121, 5, NULL, NULL, '2025-12-21 02:51:35', '2025-12-21 02:51:35'),
(276, 'DEF-SUPERADMIN', 122, 39, NULL, NULL, '2025-12-21 02:53:35', '2025-12-21 02:53:35'),
(278, 'DEF-MASTERADMIN', 123, 40, NULL, NULL, '2025-12-24 04:05:17', '2025-12-24 04:05:17'),
(279, 'DEF-CLIENT', 124, 40, NULL, NULL, '2025-12-24 04:19:09', '2025-12-24 04:19:09'),
(281, 'DEF-MASTERADMIN', 126, 42, NULL, NULL, '2025-12-24 04:25:43', '2025-12-24 04:25:43'),
(283, 'DEF-CLIENT', 128, 42, NULL, NULL, '2025-12-29 05:14:09', '2025-12-29 05:14:09'),
(284, 'DEF-ADMIN', 129, 41, NULL, NULL, '2026-05-30 20:16:57', '2026-05-30 20:16:57'),
(285, 'DEF-ADMIN', 130, 1, NULL, NULL, '2026-05-30 20:18:16', '2026-05-30 20:18:16'),
(287, 'DEF-MASTERADMIN', 132, 1, NULL, NULL, '2026-05-30 20:18:38', '2026-05-30 20:18:38'),
(288, 'DEF-MASTERADMIN', 133, 10, NULL, NULL, '2026-05-30 20:18:38', '2026-05-30 20:18:38'),
(289, 'DEF-CLIENT', 134, 1, NULL, NULL, '2026-11-19 07:37:22', '2026-11-19 07:37:22'),
(290, 'DEF-CLIENT', 135, 10, NULL, NULL, '2026-11-19 07:37:22', '2026-11-19 07:37:22'),
(291, 'DEF-CLIENT', 136, 2, NULL, NULL, '2026-11-19 07:37:22', '2026-11-19 07:37:22'),
(292, 'DEF-CLIENT', 137, 6, NULL, NULL, '2026-11-19 08:28:30', '2026-11-19 08:28:30'),
(293, 'DEF-MASTERADMIN', 138, 45, NULL, NULL, '2026-11-21 16:11:01', '2026-11-21 16:11:01'),
(294, 'DEF-MASTERADMIN', 139, 46, NULL, NULL, '2026-11-21 16:16:55', '2026-11-21 16:16:55'),
(295, 'DEF-MASTERADMIN', 140, 47, NULL, NULL, '2026-11-21 16:16:55', '2026-11-21 16:16:55'),
(296, 'DEF-MASTERADMIN', 141, 48, NULL, NULL, '2026-11-21 16:16:55', '2026-11-21 16:16:55'),
(297, 'DEF-MASTERADMIN', 142, 49, NULL, NULL, '2026-11-21 16:16:55', '2026-11-21 16:16:55');

-- --------------------------------------------------------

--
-- Table structure for table `roleaccesssubmenus`
--

DROP TABLE IF EXISTS `roleaccesssubmenus`;
CREATE TABLE IF NOT EXISTS `roleaccesssubmenus` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `rolecode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `submenus_id` int NOT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roleaccesssubmenus`
--

INSERT INTO `roleaccesssubmenus` (`id`, `rolecode`, `transNo`, `submenus_id`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(5, 'DEF-MASTERADMIN', 10, 1, NULL, NULL, NULL, NULL),
(6, 'DEF-MASTERADMIN', 10, 2, NULL, NULL, NULL, NULL),
(7, 'DEF-MASTERADMIN', 10, 3, NULL, NULL, NULL, NULL),
(8, 'DEF-MASTERADMIN', 10, 4, NULL, NULL, NULL, NULL),
(9, 'DEF-USERS', 15, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(10, 'DEF-MASTERADMIN', 16, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(11, 'DEF-CLIENT', 19, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(12, 'DEF-SUPERADMIN', 25, 5, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(13, 'DEF-MASTERADMIN', 50, 12, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(15, 'DEF-SUPERADMIN', 65, 1, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(16, 'DEF-SUPERADMIN', 65, 2, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(17, 'DEF-SUPERADMIN', 65, 3, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(18, 'DEF-SUPERADMIN', 65, 4, 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(141, 'DEF-ADMIN', 120, 2, NULL, NULL, '2025-12-21 02:34:41', '2025-12-21 02:34:41'),
(149, 'DEF-ADMIN', 129, 22, NULL, NULL, '2026-06-08 05:00:47', '2026-06-08 05:00:47'),
(152, 'DEF-ADMIN', 129, 23, NULL, NULL, '2026-06-20 23:25:07', '2026-06-20 23:25:07'),
(153, 'DEF-MASTERADMIN', 10, 24, NULL, NULL, '2026-11-19 07:18:21', '2026-11-19 07:18:21'),
(154, 'DEF-CLIENT', 136, 24, NULL, NULL, '2026-11-19 07:37:22', '2026-11-19 07:37:22'),
(155, 'DEF-MASTERADMIN', 10, 25, NULL, NULL, '2026-11-21 17:22:12', '2026-11-21 17:22:12'),
(156, 'DEF-MASTERADMIN', 10, 26, NULL, NULL, '2026-11-21 17:46:42', '2026-11-21 17:46:42');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `rolecode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `rolecode`, `description`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(2, 'DEF-USERS', 'System User with Access to Standard Features', 'Rj ediral', NULL, '2024-11-08 00:09:29', '2024-11-08 00:09:29'),
(3, 'DEF-MASTERADMIN', 'System Developer with Full Access to All Modules and Features.', 'Rj ediral', 'Pedro Yorpo', '2024-11-11 16:27:35', '2024-11-12 05:53:02'),
(4, 'DEF-SUPERADMIN', 'Roles', 'Pedro Yorpo', 'Pedro yorpo', '2024-11-16 23:18:13', '2025-01-17 17:08:20'),
(5, 'DEF-CLIENT', 'Standard user with access to client-specific features.', 'Pedro Yorpo', NULL, '2024-11-21 05:43:00', '2024-11-21 05:43:00'),
(10, 'DEF-ADMIN', 'Roles', 'Pedro yorpo', NULL, '2025-10-04 05:17:08', '2025-10-04 05:17:08');

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

DROP TABLE IF EXISTS `saved_jobs`;
CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(8) NOT NULL,
  `job_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `recordstatus` varchar(15) NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_save` (`code`,`job_id`),
  KEY `job_id` (`job_id`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submenus`
--

DROP TABLE IF EXISTS `submenus`;
CREATE TABLE IF NOT EXISTS `submenus` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `transNo` int NOT NULL,
  `desc_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `routes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A',
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submenus`
--

INSERT INTO `submenus` (`id`, `transNo`, `desc_code`, `description`, `icon`, `class`, `routes`, `sort`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'top_navigation', 'Security roles', 'icon-security', 'class-security', 'security', 1, 'A', NULL, NULL, NULL, NULL),
(2, 2, 'top_navigation', 'Users', 'icon-user', 'class-user', 'user', 2, 'A', NULL, NULL, NULL, NULL),
(3, 2, 'top_navigation', 'Menus', 'icon-menu', 'class-menu', 'menu', 3, 'A', NULL, NULL, NULL, NULL),
(4, 2, 'top_navigation', 'Roles', 'icon-role', 'class-role', 'role', 4, 'A', NULL, NULL, NULL, NULL),
(19, 25, 'top_navigation', 'User Plan', '-', '-', 'userplan', 1, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-21 18:13:27'),
(20, 25, 'top_navigation', 'Client Plan', '-', '-', 'clientplan', 2, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-19 08:38:43'),
(21, 25, 'top_navigation', 'Plan Category', '-', '-', 'plancategory', 3, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-19 08:44:00'),
(22, 25, 'top_navigation', 'Features UserPlan', '-', '-', 'featuresplan', 1, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-21 18:13:30'),
(23, 25, 'top_navigation', 'Features ClientPlan', '-', '-', 'features_clientplan', 1, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-19 08:41:01'),
(24, 2, 'top_navigation', 'Company Information', '-', '-', 'company', 1, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-21 17:05:19'),
(25, 2, 'top_navigation', 'Database', 'database', '-', 'database', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(26, 2, 'top_navigation', 'Email Config', 'email', '-', 'emailconfig', 1, 'A', 'Pedro yorpo', 'system', NULL, '2026-11-21 17:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `subscription_features`
--

DROP TABLE IF EXISTS `subscription_features`;
CREATE TABLE IF NOT EXISTS `subscription_features` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `feature_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'star',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_type` enum('toggle','limit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'toggle',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_features_feature_code_unique` (`feature_code`)
) ENGINE=InnoDB AUTO_INCREMENT=952 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_features`
--

INSERT INTO `subscription_features` (`id`, `feature_code`, `name`, `category`, `icon`, `description`, `value_type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'APPLY_JOBS', 'Apply to Jobs', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(2, 'CREATE_RESUME', 'Create Resume', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(3, 'CREATE_PROFILE', 'Create Profile', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(4, 'NETWORK_FEED', 'Join Networking Feed', 'Networking', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(5, 'JOB_ALERTS', 'Receive Job Alerts', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(6, 'CONNECTION_LIMIT', '15 Connections Limit', 'Networking', 'star', NULL, 'limit', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(7, 'SEARCH_ACCESS', 'Restricted Search Access', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(8, 'MESSAGING_ACCESS', 'Limited Messaging', 'Networking', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(9, 'EVERYTHING_FREE', 'Everything in Free', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(10, 'ADVANCED_JOB_SEARCH', 'Advanced Job Search Filters', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(11, '100_CONNECTIONS_LIMIT', '100 Connections Limit', 'Networking', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(12, 'UNLIMITED_JOB', 'Unlimited Job Applications', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(13, 'UNLIMITED_MESSAGING', 'Unlimited Messaging', 'Networking', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(14, 'PROFILE_INSIGHTS', 'Profile Insights', 'Analytics', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(15, 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'Analytics', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(16, 'JOB_RECOMMENDATIONS', 'AI Job Recommendations', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(17, 'APPLICANT_BADGE', 'Featured Applicant Badge', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(18, 'PRIORITY_APPLICATION', 'Priority Application Queue', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(19, 'EVERYTHING_BASIC', 'Everything in Basic', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(20, 'UNLIMITED_CONNECTIONS', 'Unlimited Connections', 'Networking', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(21, 'PROFILE_BOOSTING', 'Profile Boosting', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(22, 'APPLICANT_PLACEMENT', 'Top Applicant Placement', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(23, 'VERIFIED_PREMIUM_BADGE', 'Verified Premium Badge', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(24, 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(25, 'AI_INTERVIEW_PREPARATION', 'AI Interview Preparation', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(26, 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'Analytics', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(27, 'ACCESS_TO_PREMIUM', 'Access to Premium Networking Events', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(28, 'RECRUITER_DIRECT_ACCESS', 'Recruiter Direct Access', 'General', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(29, 'EARLY_ACCESS_JOBS', 'Early Access to New Jobs', 'Jobs', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(30, '1-DAY_PROFILE', '1-Day Profile Boost per Month', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28'),
(806, 'JOB_POSTING', '1 active job posting', 'Jobs', 'person', 'For individual recruiters and small businesses.', 'toggle', 1, '2026-11-22 22:31:08', '2026-11-22 22:31:08'),
(807, 'JOB_APPLICANTS', '30 applicants per month', 'General', 'person', NULL, 'toggle', 1, '2026-11-22 22:53:17', '2026-11-22 22:53:17');

-- --------------------------------------------------------

--
-- Table structure for table `usercapabilities`
--

DROP TABLE IF EXISTS `usercapabilities`;
CREATE TABLE IF NOT EXISTS `usercapabilities` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` int DEFAULT NULL,
  `transNo` int DEFAULT NULL,
  `language` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usercapabilities`
--

INSERT INTO `usercapabilities` (`id`, `code`, `transNo`, `language`, `created_at`, `updated_at`) VALUES
(15, 718, 1, 'Afrikaans', '2025-10-30 14:45:49', '2025-10-30 14:45:49'),
(16, 718, 2, 'Amharic', '2025-10-30 14:45:49', '2025-10-30 14:45:49'),
(19, 720, 1, 'Filipino', '2026-11-22 20:51:11', '2026-11-22 20:51:11'),
(20, 720, 2, 'English', '2026-11-22 20:51:34', '2026-11-22 20:51:34'),
(97, 721, 1, 'English', '2026-11-23 05:07:14', '2026-11-23 05:07:14'),
(98, 721, 2, 'Filipino', '2026-11-23 05:07:14', '2026-11-23 05:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `usercertificates`
--

DROP TABLE IF EXISTS `usercertificates`;
CREATE TABLE IF NOT EXISTS `usercertificates` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `certificate_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_completed` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usercertificates`
--

INSERT INTO `usercertificates` (`id`, `code`, `transNo`, `certificate_title`, `certificate_provider`, `date_completed`, `created_at`, `updated_at`) VALUES
(8, '718', 1, 'SDV', 'SDV', '2025-10-16', '2025-10-30 14:41:10', '2025-10-30 14:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `usereducations`
--

DROP TABLE IF EXISTS `usereducations`;
CREATE TABLE IF NOT EXISTS `usereducations` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` int DEFAULT NULL,
  `transNo` int DEFAULT NULL,
  `highest_education` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_month` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_year` int DEFAULT NULL,
  `end_month` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_year` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usereducations`
--

INSERT INTO `usereducations` (`id`, `code`, `transNo`, `highest_education`, `school_name`, `start_month`, `start_year`, `end_month`, `end_year`, `status`, `created_at`, `updated_at`) VALUES
(12, 718, 1, 'TEST', 'TEST', 'February', 2002, 'February', 2008, 'graduate', '2025-10-30 14:39:43', '2025-10-30 14:39:43'),
(18, 720, 1, 'Bachelor of Elementary Education', 'Cordova Public College', 'August', 2019, 'June', 2023, 'graduate', '2026-11-22 21:03:08', '2026-11-22 21:03:08'),
(99, 721, 1, 'Bachelor', 'Cordova Public College', 'October', 2018, 'November', 2022, 'graduate', '2026-11-23 01:45:57', '2026-11-23 01:45:57'),
(100, 721, 2, 'Bachelor of Information Technology', 'CPC', 'August', 2018, 'December', 2026, 'graduate', '2026-11-23 01:45:57', '2026-11-23 01:45:57'),
(101, 721, 3, 'Bachelor Degree', 'Cordova Public College', 'September', 2018, 'September', 2022, 'graduate', '2026-11-23 05:07:14', '2026-11-23 05:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `useremploymentrecords`
--

DROP TABLE IF EXISTS `useremploymentrecords`;
CREATE TABLE IF NOT EXISTS `useremploymentrecords` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_completed` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `useremploymentrecords`
--

INSERT INTO `useremploymentrecords` (`id`, `code`, `transNo`, `company_name`, `position`, `job_description`, `date_completed`, `created_at`, `updated_at`) VALUES
(10, '718', 1, 'SDV', 'SDV', 'SDV', '2025-10-08', '2025-10-30 14:41:19', '2025-10-30 14:41:19'),
(84, '721', 1, 'Cordova Public College', 'Fullstack Developer', NULL, '2026-11-23', '2026-11-23 01:42:33', '2026-11-23 01:42:33'),
(85, '721', 2, 'and Laravel for Maintain the Company', 'Experience 3 years Angular typescript', 'System in Mariosoft Solutions.\nBasak, Lapu-Lapu City', '2022-01-01', '2026-11-23 01:42:33', '2026-11-23 01:42:33'),
(102, '721', 3, 'Mariosoft Solutions', 'Fullstack Developer', 'Customize Desktop and Web Payroll , Retail Accounting System', '2026-11-23', '2026-11-23 05:07:14', '2026-11-23 05:07:14');

-- --------------------------------------------------------

--
-- Table structure for table `userplandetails`
--

DROP TABLE IF EXISTS `userplandetails`;
CREATE TABLE IF NOT EXISTS `userplandetails` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `planId` varchar(50) NOT NULL,
  `plan_name` varchar(255) NOT NULL,
  `fid` varchar(50) NOT NULL,
  `feature_name` varchar(255) NOT NULL,
  `recordStatus` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `feature_code` varchar(100) DEFAULT NULL,
  `feature_value` varchar(255) DEFAULT NULL,
  `feature_type` varchar(50) DEFAULT NULL,
  `button_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `userplandetails`
--

INSERT INTO `userplandetails` (`id`, `planId`, `plan_name`, `fid`, `feature_name`, `recordStatus`, `created_at`, `updated_at`, `feature_code`, `feature_value`, `feature_type`, `button_name`) VALUES
(5, 'PLN000001', 'Free', 'FID000005', 'Apply to Jobs', 'active', '2026-06-05 22:28:42', '2026-06-26 16:24:41', 'APPLY_JOBS', 'YES', 'Everything in Free', ''),
(4, 'PLN000001', 'Free', 'FID000004', 'Create Resume', 'active', '2026-06-05 22:28:36', '2026-11-23 05:16:19', 'CREATE_RESUME', 'YES', 'Everything in Free', ''),
(3, 'PLN000001', 'Free', 'FID000001', 'Create Profile', 'active', '2026-06-05 22:22:48', '2026-06-20 23:04:47', 'CREATE_PROFILE', 'YES', 'Everything in Free', ''),
(7, 'PLN000001', 'Free', 'FID000007', 'Join Networking Feed', 'active', '2026-06-05 22:28:59', '2026-06-05 22:28:59', 'NETWORK_FEED', 'YES', 'Everything in Free', ''),
(8, 'PLN000001', 'Free', 'FID000008', 'Receive Job Alerts', 'active', '2026-06-05 22:29:07', '2026-06-20 21:34:09', 'JOB_ALERTS', 'YES', 'Everything in Free', ''),
(9, 'PLN000001', 'Free', 'FID000009', '15 Connections Limit', 'active', '2026-06-05 22:29:17', '2026-06-05 22:29:17', 'CONNECTION_LIMIT', '15', 'Everything in Free', ''),
(11, 'PLN000001', 'Free', 'FID000010', 'Restricted Search Access', 'active', '2026-06-05 22:31:16', '2026-11-23 05:16:19', 'SEARCH_ACCESS', 'YES', 'Everything in Free', ''),
(12, 'PLN000001', 'Free', 'FID000012', 'Limited Messaging', 'active', '2026-06-05 22:31:26', '2026-06-20 21:41:17', 'MESSAGING_ACCESS', 'YES', 'Everything in Free', ''),
(13, 'PLN000002', 'Basic', 'FID000013', 'Everything in Free', 'active', '2026-06-05 22:34:27', '2026-06-23 06:39:40', 'EVERYTHING_FREE', 'YES', 'Everything in Basic', ''),
(17, 'PLN000002', 'Basic', 'FID000017', 'Advanced Job Search Filters', 'active', '2026-06-05 22:34:45', '2026-06-23 06:41:05', 'ADVANCED_JOB_SEARCH', 'YES', 'Everything in Basic', ''),
(15, 'PLN000002', 'Basic', 'FID000015', '100 Connections Limit', 'active', '2026-06-05 22:34:30', '2026-06-23 06:40:17', '100_CONNECTIONS_LIMIT', 'YES', 'Everything in Basic', ''),
(16, 'PLN000002', 'Basic', 'FID000016', 'Unlimited Job Applications', 'active', '2026-06-05 22:34:36', '2026-06-23 06:40:41', 'UNLIMITED_JOB', 'YES', 'Everything in Basic', ''),
(18, 'PLN000002', 'Basic', 'FID000018', 'Unlimited Messaging', 'active', '2026-06-05 22:34:55', '2026-06-23 06:41:23', 'UNLIMITED_MESSAGING', 'YES', 'Everything in Basic', ''),
(19, 'PLN000002', 'Basic', 'FID000019', 'Profile Insights', 'active', '2026-06-05 22:35:01', '2026-06-23 06:41:52', 'PROFILE_INSIGHTS', 'YES', 'Everything in Basic', ''),
(20, 'PLN000002', 'Basic', 'FID000020', 'See Who Viewed Your Profile', 'active', '2026-06-05 22:35:10', '2026-06-23 06:42:15', 'VIEWED_YOUR_PROFILE', 'YES', 'Everything in Basic', ''),
(21, 'PLN000002', 'Basic', 'FID000021', 'AI Job Recommendations', 'active', '2026-06-05 22:35:23', '2026-06-23 06:42:29', 'JOB_RECOMMENDATIONS', 'YES', 'Everything in Basic', ''),
(22, 'PLN000002', 'Basic', 'FID000022', 'Featured Applicant Badge', 'active', '2026-06-05 22:35:30', '2026-06-23 06:42:45', 'APPLICANT_BADGE', 'YES', 'Everything in Basic', ''),
(23, 'PLN000002', 'Basic', 'FID000023', 'Priority Application Queue', 'active', '2026-06-05 22:35:38', '2026-06-23 06:42:57', 'PRIORITY_APPLICATION', 'YES', 'Everything in Basic', ''),
(24, 'PLN000003', 'Premium', 'FID000024', 'Everything in Basic', 'active', '2026-06-05 23:14:56', '2026-06-27 19:52:41', 'EVERYTHING_BASIC', 'YES', 'Everything in Premium', ''),
(25, 'PLN000003', 'Premium', 'FID000025', 'Unlimited Connections', 'active', '2026-06-05 23:15:02', '2026-06-27 19:52:58', 'UNLIMITED_CONNECTIONS', 'YES', 'Everything in Premium', ''),
(26, 'PLN000003', 'Premium', 'FID000026', 'Profile Boosting', 'active', '2026-06-05 23:15:08', '2026-06-27 19:53:28', 'PROFILE_BOOSTING', 'YES', 'Everything in Premium', ''),
(27, 'PLN000003', 'Premium', 'FID000027', 'Top Applicant Placement', 'active', '2026-06-05 23:15:19', '2026-06-27 19:53:55', 'APPLICANT_PLACEMENT', 'YES', 'Everything in Premium', ''),
(28, 'PLN000003', 'Premium', 'FID000028', 'Verified Premium Badge', 'active', '2026-06-05 23:15:27', '2026-06-27 19:54:30', 'VERIFIED_PREMIUM_BADGE', 'YES', 'Everything in Premium', ''),
(29, 'PLN000003', 'Premium', 'FID000029', 'AI Resume Optimization', 'active', '2026-06-05 23:15:32', '2026-06-27 19:56:05', 'AI_RESUME_OPTIMIZATION', 'YES', 'Everything in Premium', ''),
(30, 'PLN000003', 'Premium', 'FID000030', 'AI Interview Preparation', 'active', '2026-06-05 23:15:39', '2026-06-27 19:56:20', 'AI_INTERVIEW_PREPARATION', 'YES', 'Everything in Premium', ''),
(31, 'PLN000003', 'Premium', 'FID000031', 'Career Analytics Dashboard', 'active', '2026-06-05 23:15:47', '2026-06-27 19:56:34', 'CAREER_ANALYTICS_DASHBOARD', 'YES', 'Everything in Premium', ''),
(32, 'PLN000003', 'Premium', 'FID000032', 'Access to Premium Networking Events', 'active', '2026-06-05 23:15:53', '2026-06-27 19:56:53', 'ACCESS_TO_PREMIUM', 'YES', 'Everything in Premium', ''),
(33, 'PLN000003', 'Premium', 'FID000033', 'Recruiter Direct Access', 'active', '2026-06-05 23:15:58', '2026-06-27 19:57:08', 'RECRUITER_DIRECT_ACCESS', 'YES', 'Everything in Premium', ''),
(34, 'PLN000003', 'Premium', 'FID000034', 'Early Access to New Jobs', 'active', '2026-06-05 23:16:03', '2026-06-27 19:57:27', 'EARLY_ACCESS_JOBS', 'YES', 'Everything in Premium', ''),
(35, 'PLN000003', 'Premium', 'FID000035', '1-Day Profile Boost per Month', 'active', '2026-06-05 23:16:10', '2026-06-27 19:57:41', '1-DAY_PROFILE', 'YES', 'Everything in Premium', ''),
(36, 'PLN000004', 'Business Unlimited', 'FID000036', 'Everything in Premium', 'active', '2026-06-06 01:11:18', '2026-06-17 05:59:10', '', NULL, 'Business Unlimited', ''),
(37, 'PLN000004', 'Business Unlimited', 'FID000037', 'Unlimited Job Posts', 'active', '2026-06-06 01:11:25', '2026-06-06 01:11:25', NULL, NULL, 'Business Unlimited', ''),
(38, 'PLN000004', 'Business Unlimited', 'FID000038', 'Unlimited Candidate Access', 'active', '2026-06-06 01:11:33', '2026-06-06 01:11:33', NULL, NULL, 'Business Unlimited', ''),
(39, 'PLN000004', 'Business Unlimited', 'FID000039', 'Multi-Recruiter Team Access', 'active', '2026-06-06 01:11:44', '2026-06-06 01:11:44', NULL, NULL, 'Business Unlimited', ''),
(40, 'PLN000004', 'Business Unlimited', 'FID000040', 'Company Branding Page', 'active', '2026-06-06 01:11:59', '2026-06-06 01:11:59', NULL, NULL, 'Business Unlimited', ''),
(41, 'PLN000004', 'Business Unlimited', 'FID000041', 'Advanced Applicant Tracking System', 'active', '2026-06-06 01:12:14', '2026-06-06 01:12:14', NULL, NULL, 'Business Unlimited', ''),
(42, 'PLN000004', 'Business Unlimited', 'FID000042', 'Bulk Messaging to Candidate', 'active', '2026-06-06 01:12:26', '2026-06-06 01:12:26', NULL, NULL, 'Business Unlimited', ''),
(43, 'PLN000004', 'Business Unlimited', 'FID000043', 'AI Talent Matching (Advanced)', 'active', '2026-06-06 01:12:35', '2026-06-06 01:12:35', NULL, NULL, 'Business Unlimited', ''),
(44, 'PLN000004', 'Business Unlimited', 'FID000044', 'Priority Job Listing Boost', 'active', '2026-06-06 01:12:44', '2026-06-06 01:12:44', NULL, NULL, 'Business Unlimited', ''),
(45, 'PLN000004', 'Business Unlimited', 'FID000045', 'Analytics & Hiring Reports', 'active', '2026-06-06 01:12:54', '2026-06-06 01:12:54', NULL, NULL, 'Business Unlimited', ''),
(46, 'PLN000004', 'Business Unlimited', 'FID000046', 'Dedicated Account Manager', 'active', '2026-06-06 01:13:02', '2026-06-06 01:13:02', NULL, NULL, 'Business Unlimited', ''),
(47, 'PLN000004', 'Business Unlimited', 'FID000047', 'Custom Recruitment Workflow', 'active', '2026-06-06 01:13:22', '2026-06-06 01:13:22', NULL, NULL, 'Business Unlimited', ''),
(2302, 'PLN000008', 'Free', 'FID002302', 'Restricted Search Access', 'active', '2026-11-23 05:39:31', '2026-11-23 05:39:31', 'SEARCH_ACCESS', 'YES', NULL, 'STARTER'),
(2301, 'PLN000008', 'Free', 'FID002301', 'Limited Messaging', 'active', '2026-11-23 05:39:31', '2026-11-23 05:39:31', 'MESSAGING_ACCESS', 'YES', NULL, 'STARTER'),
(2300, 'PLN000008', 'Free', 'FID001959', 'Join Networking Feed', 'active', '2026-11-23 05:39:31', '2026-11-23 05:39:31', 'NETWORK_FEED', 'YES', NULL, 'STARTER'),
(1958, 'PLN000001', 'Free', 'FID001958', 'Profile Insights', 'active', '2026-11-23 05:16:42', '2026-11-23 05:16:42', 'PROFILE_INSIGHTS', 'YES', NULL, 'Get started free'),
(1957, 'PLN000001', 'Free', 'FID001957', 'Career Analytics Dashboard', 'active', '2026-11-23 05:16:42', '2026-11-23 05:16:42', 'CAREER_ANALYTICS_DASHBOARD', 'YES', NULL, 'Get started free'),
(1956, 'PLN000001', 'Free', 'FID001956', 'See Who Viewed Your Profile', 'active', '2026-11-23 05:16:36', '2026-11-23 05:16:36', 'VIEWED_YOUR_PROFILE', 'YES', NULL, 'Get started free'),
(1955, 'PLN000001', 'Free', 'FID001955', '30 applicants per month', 'active', '2026-11-23 05:16:34', '2026-11-23 05:16:34', 'JOB_APPLICANTS', 'YES', NULL, 'Get started free'),
(1953, 'PLN000008', 'STARTER', 'FID001953', '30 applicants per month', 'active', '2026-11-22 22:54:31', '2026-11-22 22:54:31', 'JOB_APPLICANTS', 'YES', NULL, 'STARTER'),
(1954, 'PLN000001', 'Free', 'FID001954', 'AI Resume Optimization', 'active', '2026-11-23 05:16:19', '2026-11-23 05:16:19', 'AI_RESUME_OPTIMIZATION', 'YES', NULL, 'Get started free'),
(1952, 'PLN000008', 'STARTER', 'FID000048', '1 active job posting', 'active', '2026-11-22 22:31:31', '2026-11-22 22:31:31', 'JOB_POSTING', 'YES', NULL, 'STARTER');

-- --------------------------------------------------------

--
-- Table structure for table `userplanheader`
--

DROP TABLE IF EXISTS `userplanheader`;
CREATE TABLE IF NOT EXISTS `userplanheader` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `planId` varchar(50) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `button_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tagmonthYear` varchar(50) DEFAULT NULL,
  `tag` varchar(50) DEFAULT NULL,
  `button_color` varchar(30) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sort_number` int NOT NULL,
  `role_code` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `recordStatus` varchar(20) DEFAULT 'ACTIVE',
  `plan_level` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1451 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `userplanheader`
--

INSERT INTO `userplanheader` (`id`, `planId`, `plan_name`, `button_name`, `price`, `tagmonthYear`, `tag`, `button_color`, `description`, `created_at`, `sort_number`, `role_code`, `updated_at`, `recordStatus`, `plan_level`) VALUES
(5, 'PLN000002', 'Basic', 'Upgrade Basic', '199.00', 'Monthly', 'MOST POPULAR', 'accent', 'Ideal for professionals actively growing their career and connections.', '2026-06-05 22:25:58', 2, '\nDEF-USERS', '2026-11-21 17:12:04', 'active', 2),
(4, 'PLN000001', 'Free', 'Get started free', '0.00', 'Monthly', 'STARTER', 'basic', 'Perfect for new users exploring jobs and networking opportunities.', '2026-06-05 21:17:02', 1, 'DEF-USERS', '2026-11-22 21:50:26', 'active', 1),
(6, 'PLN000003', 'Premium', 'Go Premium', '499.00', 'Monthly', 'PREMIUM', 'primary', 'Premium career tools for serious professionals and top candidates.', '2026-06-05 22:26:28', 3, '\nDEF-USERS', '2026-11-21 17:12:06', 'active', 3),
(7, 'PLN000004', 'Business Unlimited', 'Go Business Unlimited', '999.00', 'Monthly', 'BUSINESS', 'warn', 'Full enterprise solution for companies, recruiters, and large organizations.', '2026-06-06 01:04:20', 4, '\nDEF-USERS', '2026-11-21 17:12:06', 'active', 4),
(811, 'PLN000008', 'Free', 'STARTER', '0.00', 'Monthly', 'MOST POPULAR', NULL, 'For individual recruiters and small businesses.', '2026-11-22 21:51:36', 1, 'DEF-CLIENT', '2026-11-23 05:23:54', 'active', 5);

-- --------------------------------------------------------

--
-- Table structure for table `userplans`
--

DROP TABLE IF EXISTS `userplans`;
CREATE TABLE IF NOT EXISTS `userplans` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `planId` varchar(30) NOT NULL,
  `plan_name` varchar(100) NOT NULL,
  `plan_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `plan_level` int NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `duration_days` int DEFAULT '30',
  `recordStatus` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `planId` (`planId`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `userplans`
--

INSERT INTO `userplans` (`id`, `planId`, `plan_name`, `plan_code`, `plan_level`, `price`, `duration_days`, `recordStatus`, `created_at`, `updated_at`) VALUES
(1, 'PLN000003', 'Free', 'FREE', 1, '0.00', 30, 'active', '2026-06-28 02:05:04', '2026-06-28 02:05:04'),
(2, 'PLN000002', 'Basic', 'BASIC', 2, '199.00', 30, 'active', '2026-06-28 02:05:04', '2026-06-28 02:05:04'),
(3, 'PLN000001', 'Premium', 'PREMIUM', 3, '499.00', 30, 'active', '2026-06-28 02:05:04', '2026-06-28 02:05:04'),
(4, 'PLN000004', 'Business Unlimited', 'BUSINESS', 4, '999.00', 30, 'active', '2026-06-28 02:05:04', '2026-06-28 02:05:04');

-- --------------------------------------------------------

--
-- Table structure for table `userprofiles`
--

DROP TABLE IF EXISTS `userprofiles`;
CREATE TABLE IF NOT EXISTS `userprofiles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` int DEFAULT NULL,
  `transNo` int DEFAULT NULL,
  `photo_pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_visibility` tinyint(1) NOT NULL DEFAULT '0',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_visibility` tinyint(1) NOT NULL DEFAULT '0',
  `summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `date_birth` date DEFAULT NULL,
  `home_country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userprofiles_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=567 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userprofiles`
--

INSERT INTO `userprofiles` (`id`, `code`, `transNo`, `photo_pic`, `contact_no`, `contact_visibility`, `email`, `email_visibility`, `summary`, `date_birth`, `home_country`, `home_state`, `current_location`, `current_state`, `created_at`, `updated_at`) VALUES
(2, 701, 2, 'http://localhost:8000/storage/uploads/701/cvphoto/c70d2c93-0e95-4aec-95da-32052cd69bbd/1795099561.jpg', '+639999990909', 1, 'pedroyorpo17@gmail.com', 1, 'asc', '2000-08-29', 'Philippines', 'CORDOVA', 'Philippines', 'LAPU-LAPU CITY', '2025-08-10 03:19:09', '2026-11-22 22:26:19'),
(20, 718, 7, 'https://exploredition.com/storage/app/public/uploads/718/cvphoto/9320e6c0-7673-45fc-8eee-6acd7ae03239/1763882516.jpg', '+639938151373', 0, 'nexsuz.official13@gmail.com', 0, 'avvsdvsdv', '2000-10-17', 'USA', 'asc', 'Russia', 'ascasc', '2025-10-30 14:48:16', '2025-12-08 13:38:18'),
(360, 720, 9, 'http://192.168.1.68:8000/storage/uploads/720/cvphoto/ad69884f-1b88-4ca1-bed8-5cc731fddb5f/1795409626.jpg', '+6309994589906', 0, 'elizabethpunay01@gmail.com', 0, 'Dedicated and passionate teacher Who want to help students to achieve their full potential.', '1999-12-07', 'Philippines', 'Catarman, Cordova, Cebu', 'Philippines', 'Catarman, Cordova, Cebu', '2026-11-22 20:53:46', '2026-11-22 21:04:10'),
(443, 721, 10, 'http://192.168.1.68:8000/storage/uploads/721/cvphoto/efa7882e-6a7e-45a9-95fd-6d69f63d191b/1795439282.jpg', '09938151373', 0, 'pedroyorpo22@gmail.com', 0, 'As a dedicated and versatile Full Stack Developer, I bring a strong background in both front-end and back-end development, ensuring seamless integration and high-quality user experiences. With a passion for crafting efficient, scalable, and maintainable', '1999-09-01', 'Philippines', 'Cordova, Cebu City', 'Philippines', 'Cordova, Cebu City', '2026-11-23 01:42:33', '2026-11-23 05:36:54');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `coverphoto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default.jpg',
  `fname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contactno` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fullname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'I',
  `company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` int NOT NULL,
  `role_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `planId` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11419 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `coverphoto`, `fname`, `lname`, `mname`, `contactno`, `fullname`, `email`, `email_verified_at`, `password`, `status`, `company`, `code`, `role_code`, `remember_token`, `created_at`, `updated_at`, `planId`, `is_online`) VALUES
(91, 'http://localhost:8000/storage/uploads/701/coverphoto/1df5d1df-1aed-4a89-b441-41fb95d4edc1/1795099544.jpg', 'Admin', 'Nexsuz', '', '+639999990909', 'Admin Nexsuz', 'pedroyorpo17@gmail.com', NULL, '$2y$10$odneo31WuVN0Dke2xxhLiOyHPfP3NjV4aPLHseSNfPn.M13QNOPca', 'A', 'Nexsuz', 701, 'DEF-MASTERADMIN', NULL, '2024-11-27 05:28:16', '2026-11-22 22:26:19', 'PLN000003', 1),
(102, '', 'human$', 'crazy', '', '+639453570677', 'Human$ crazy', 'reinjunelaride34@gmail.com', NULL, '$2y$10$u3WRh3ltKE/k6G8JSgavzeRsiWEFpv6X0ADq9GX8PykPxgwQSbya6', 'A', 'VFI', 711, 'DEF-MASTERADMIN', NULL, '2024-12-01 21:12:05', '2026-11-19 08:16:14', '', 0),
(108, '', 'TJ', 'Kim', '', '+8618321452737', 'TJ Kim', 'kimtaejung@163.com', NULL, '$2y$10$ebx/Ys7Rgwo/VaxNZZUhRe2/00RsdeKxw8AhmVp72Shqw98F1Xewu', 'A', 'Nexsuz China', 714, 'DEF-ADMIN', NULL, '2025-09-20 06:39:59', '2025-09-20 06:50:37', '', 0),
(141, 'https://exploredition.com/storage/app/public/uploads/718/coverphoto/13eba524-7d6a-4e53-b784-85ad728f8d52/1765201178.PNG', 'Nexsuz User', '-', '', '+639938151373', 'Nexsuz User -', 'nexsuz.official13@gmail.com', NULL, '$2y$10$odneo31WuVN0Dke2xxhLiOyHPfP3NjV4aPLHseSNfPn.M13QNOPca', 'A', NULL, 718, 'DEF-ADMIN', NULL, '2025-10-30 14:36:47', '2026-06-20 22:53:51', '', 1),
(8274, 'default.jpg', 'Elizabeth', 'Punay', '', '+6309994589906', 'Elizabeth Punay', 'elizabethpunay01@gmail.com', NULL, '$2y$10$klx3o2UdioayCw2Q/yS3R.XfO7p2hPav2t.DcATOUI0.pdWmIEIwC', 'A', NULL, 720, 'DEF-USERS', NULL, '2026-11-22 20:46:47', '2026-11-22 20:49:29', NULL, 1),
(8822, 'default.jpg', 'Pedro', 'Yorpo', '', '+639938151373', 'Pedro Yorpo', 'pedroyorpo22@gmail.com', NULL, '$2y$10$NFxDAsreWv5FEqVtHfwmaOJXQ0VWDzlnRs7xt.YSFsKqvYElCdPv6', 'A', NULL, 721, 'DEF-USERS', NULL, '2026-11-22 23:32:06', '2026-11-23 05:13:28', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `userseminars`
--

DROP TABLE IF EXISTS `userseminars`;
CREATE TABLE IF NOT EXISTS `userseminars` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `seminar_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seminar_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_completed` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userseminars`
--

INSERT INTO `userseminars` (`id`, `code`, `transNo`, `seminar_title`, `seminar_provider`, `date_completed`, `created_at`, `updated_at`) VALUES
(26, '718', 1, 'DCS', 'DCSDV', '2025-10-07', '2025-10-30 14:40:56', '2025-10-30 14:40:56');

-- --------------------------------------------------------

--
-- Table structure for table `userskills`
--

DROP TABLE IF EXISTS `userskills`;
CREATE TABLE IF NOT EXISTS `userskills` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int NOT NULL,
  `skills` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=463 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userskills`
--

INSERT INTO `userskills` (`id`, `code`, `transNo`, `skills`, `created_at`, `updated_at`) VALUES
(31, '718', 1, 'Network Protocols (TCP/IP, UDP, HTTP, DNS, etc.)', '2025-10-30 14:39:58', '2025-10-30 14:39:58'),
(32, '718', 2, 'Network Configuration', '2025-10-30 14:39:58', '2025-10-30 14:39:58'),
(33, '701', 1, 'Network Configuration', '2026-06-11 20:19:42', '2026-06-11 20:19:42'),
(346, '721', 1, 'HETZNER CLOUD SERVER', '2026-11-23 01:45:57', '2026-11-23 01:45:57'),
(347, '721', 2, 'Technology Used', '2026-11-23 01:45:57', '2026-11-23 01:45:57'),
(348, '721', 3, 'BOOTSTRAP', '2026-11-23 01:45:57', '2026-11-23 01:45:57');

-- --------------------------------------------------------

--
-- Table structure for table `usersubscription`
--

DROP TABLE IF EXISTS `usersubscription`;
CREATE TABLE IF NOT EXISTS `usersubscription` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transNo` varchar(50) NOT NULL,
  `planId` varchar(50) NOT NULL,
  `plan_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `remarks` text,
  `code` varchar(50) NOT NULL,
  `is_active` enum('pending','active','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=654 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usersubscription`
--

INSERT INTO `usersubscription` (`id`, `transNo`, `planId`, `plan_name`, `amount`, `payment_method`, `start_date`, `end_date`, `cancelled_at`, `remarks`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(198, 'SUB000147', 'PLN000001', 'Free', '0.00', 'Free', '2026-11-22 04:41:36', '2026-12-22 04:41:36', NULL, NULL, '721', 'active', '2026-11-21 20:41:36', '2026-11-21 20:41:36'),
(197, 'SUB000146', 'PLN000001', 'Free', '0.00', 'Free', '2026-11-22 04:41:36', '2026-12-22 04:41:36', NULL, NULL, '720', 'active', '2026-11-21 20:41:36', '2026-11-21 20:41:36'),
(457, 'SUB000344', 'PLN000001', 'Free', '0.00', 'Free', '2026-11-23 13:39:41', '2026-12-23 13:39:41', NULL, NULL, '723', 'active', '2026-11-23 05:39:41', '2026-11-23 05:39:41'),
(456, 'SUB000343', 'PLN000001', 'Free', '0.00', 'Free', '2026-11-23 13:39:40', '2026-12-23 13:39:40', NULL, NULL, '722', 'active', '2026-11-23 05:39:40', '2026-11-23 05:39:40');

-- --------------------------------------------------------

--
-- Table structure for table `usersubscription_details`
--

DROP TABLE IF EXISTS `usersubscription_details`;
CREATE TABLE IF NOT EXISTS `usersubscription_details` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `transNo` varchar(50) NOT NULL,
  `code` varchar(30) NOT NULL,
  `planId` varchar(30) NOT NULL,
  `plan_name` varchar(30) NOT NULL,
  `fid` varchar(30) NOT NULL,
  `feature_code` varchar(100) NOT NULL,
  `feature_name` varchar(150) NOT NULL,
  `feature_value` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` enum('pending','active','expired','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5588 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usersubscription_details`
--

INSERT INTO `usersubscription_details` (`id`, `transNo`, `code`, `planId`, `plan_name`, `fid`, `feature_code`, `feature_name`, `feature_value`, `created_at`, `updated_at`, `is_active`) VALUES
(2099, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2098, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2097, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2096, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2095, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2094, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2093, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2092, 'SUB000143', '893223', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2091, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2090, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2089, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2088, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2087, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2086, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2085, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2084, 'SUB000142', '893197', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2083, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2082, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2081, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2080, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2079, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2078, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2077, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2076, 'SUB000141', '893072', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2075, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2074, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2073, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2072, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2071, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2070, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2069, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2068, 'SUB000140', '893486', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2067, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2066, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2065, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2064, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2063, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2062, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2061, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2060, 'SUB000139', '893459', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:23', '2026-11-22 22:01:39', 'expired'),
(2059, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2058, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2057, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2056, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2055, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2054, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2053, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2052, 'SUB000138', '893352', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2051, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2050, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2049, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2048, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2047, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2046, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2045, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2044, 'SUB000137', '893265', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:23', '2026-11-21 20:41:23', 'active'),
(2043, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2042, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2041, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2040, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2039, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2038, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2037, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2036, 'SUB000136', '893061', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:23', '2026-11-21 22:29:01', 'expired'),
(2035, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2034, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2033, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2032, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2031, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2030, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2029, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2028, 'SUB000135', '893033', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:16', '2026-11-21 20:41:16', 'active'),
(2027, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2026, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2025, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2024, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2023, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2022, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2021, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2020, 'SUB000134', '893417', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2019, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2018, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2017, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2016, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2015, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2012, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2013, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2014, 'SUB000133', '893367', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2011, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2010, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2009, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2008, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2007, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2006, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2004, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2005, 'SUB000132', '893252', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:09', 'active'),
(2003, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(2002, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(2001, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(2000, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(1999, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(1998, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(1997, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(1996, 'SUB000131', '893188', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:09', '2026-11-21 20:41:51', 'expired'),
(1995, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1994, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1993, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1992, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1991, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1990, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1989, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1988, 'SUB000130', '893062', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:08', '2026-11-21 20:41:08', 'active'),
(1987, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1986, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1985, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1984, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1983, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1982, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1981, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1980, 'SUB000129', '893031', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:59', '2026-11-21 20:40:59', 'active'),
(1979, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1978, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1977, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1976, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1975, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1974, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1973, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1972, 'SUB000128', '893022', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:51', '2026-11-21 20:40:51', 'active'),
(1971, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1970, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1969, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1968, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1967, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1966, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1964, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1965, 'SUB000127', '893433', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1963, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1962, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1961, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1960, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1959, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1958, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1957, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1956, 'SUB000126', '893383', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1955, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1954, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1953, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1952, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1951, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1950, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1947, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1949, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1948, 'SUB000125', '893252', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:41:09', 'expired'),
(1946, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1945, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1944, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1943, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1942, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1935, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1936, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1937, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1938, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1939, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1940, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1941, 'SUB000124', '893177', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:44', '2026-11-21 20:40:44', 'active'),
(1933, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1934, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(1932, 'SUB000123', '893041', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:40:43', '2026-11-21 20:40:43', 'active'),
(3609, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3610, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3608, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3607, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3606, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3605, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(2100, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2101, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2102, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2103, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2104, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2105, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2106, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2107, 'SUB000144', '893345', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2108, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2109, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2110, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2111, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2112, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2113, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2114, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2115, 'SUB000145', '893399', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:34', '2026-11-21 20:41:34', 'active'),
(2116, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2117, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2118, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2119, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2120, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2121, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2122, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2123, 'SUB000146', '720', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2124, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2125, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2126, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2127, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2128, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2129, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2130, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2131, 'SUB000147', '721', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:36', '2026-11-21 20:41:36', 'active'),
(2227, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2225, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2226, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2224, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2223, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2222, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2221, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2220, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2218, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2219, 'SUB000156', '893392', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:51', '2026-11-21 22:29:02', 'expired'),
(2217, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2216, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2214, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2215, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2213, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2212, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2211, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2210, 'SUB000155', '893249', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2208, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2209, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2207, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2206, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2205, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2204, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2203, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2202, 'SUB000154', '893188', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2201, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2200, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2199, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2198, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2197, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2196, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2195, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2194, 'SUB000153', '893084', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2228, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2229, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2230, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2231, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2232, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2233, 'SUB000157', '893463', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:41:51', '2026-11-21 20:41:51', 'active'),
(2294, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2293, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2292, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2291, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2290, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2289, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2288, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2287, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2286, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2285, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2284, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2283, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2282, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2281, 'SUB000161', '893297', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2280, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2279, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2278, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2277, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2276, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2275, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2274, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2273, 'SUB000160', '893232', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2272, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2271, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2270, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2269, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2268, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2267, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2266, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2265, 'SUB000159', '893078', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:42:26', '2026-11-21 20:42:26', 'active'),
(2295, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2296, 'SUB000162', '893423', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2297, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2298, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2299, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2300, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2301, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2302, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2303, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2304, 'SUB000163', '893517', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2305, 'SUB000164', '893526', 'ZZPLN905', 'x', 'ZZ240120', 'ZZ_LIMIT', 'ZZ Limited thing', '25', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2306, 'SUB000164', '893526', 'ZZPLN905', 'x', 'ZZ515171', 'ZZ_BASIC', 'ZZ Basic thing', 'YES', '2026-11-21 20:42:27', '2026-11-21 20:42:27', 'active'),
(2402, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2400, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2401, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2399, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2398, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2397, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2396, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2395, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2393, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2394, 'SUB000173', '893381', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2392, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2391, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2389, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2390, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2388, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active');
INSERT INTO `usersubscription_details` (`id`, `transNo`, `code`, `planId`, `plan_name`, `fid`, `feature_code`, `feature_name`, `feature_value`, `created_at`, `updated_at`, `is_active`) VALUES
(2387, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2386, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2385, 'SUB000172', '893276', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2383, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2384, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2382, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2381, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2380, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2379, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2378, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2377, 'SUB000171', '893212', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:16:14', '2026-11-21 22:16:14', 'active'),
(2376, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2375, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2374, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2373, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2372, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2371, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2370, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2369, 'SUB000170', '893097', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:16:14', '2026-11-22 22:01:38', 'expired'),
(2403, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2404, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2405, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2406, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2407, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2408, 'SUB000174', '893401', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2409, 'SUB000175', '893422', 'ZZPLN905', 'x', 'ZZ884386', 'ZZ_LIMIT', 'ZZ Limited thing', '25', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2410, 'SUB000175', '893422', 'ZZPLN905', 'x', 'ZZ283394', 'ZZ_BASIC', 'ZZ Basic thing', 'YES', '2026-11-21 22:16:15', '2026-11-21 22:16:15', 'active'),
(2538, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2537, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2536, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2534, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2535, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2532, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2533, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2530, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2531, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2528, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2529, 'SUB000185', '893409', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:27:49', '2026-11-21 22:27:49', 'active'),
(2526, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2527, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2525, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2524, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2523, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2522, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2521, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2518, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2520, 'SUB000184', '893283', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2519, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2517, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2516, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2515, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2514, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2513, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2512, 'SUB000183', '893228', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2511, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2510, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2509, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2508, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2507, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2506, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2505, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2504, 'SUB000182', '893091', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:27:48', '2026-11-21 22:27:48', 'active'),
(2539, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2540, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2541, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2542, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2543, 'SUB000186', '893512', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:27:49', '2026-11-23 05:40:21', 'expired'),
(2544, 'SUB000187', '893529', 'ZZPLN905', 'x', 'ZZ532562', 'ZZ_LIMIT', 'ZZ Limited thing', '25', '2026-11-21 22:27:49', '2026-11-22 21:49:01', 'expired'),
(2545, 'SUB000187', '893529', 'ZZPLN905', 'x', 'ZZ208866', 'ZZ_BASIC', 'ZZ Basic thing', 'YES', '2026-11-21 22:27:49', '2026-11-22 21:49:01', 'expired'),
(2673, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2672, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2671, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2669, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2670, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2667, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2668, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2665, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2666, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2663, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2664, 'SUB000197', '893340', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2661, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2662, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2660, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2659, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2658, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2657, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2656, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2653, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2655, 'SUB000196', '893244', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2654, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2652, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2651, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2650, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2649, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2648, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2647, 'SUB000195', '893202', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2646, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2645, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2644, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2643, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2642, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2641, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2640, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2639, 'SUB000194', '893061', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:29:01', '2026-11-21 22:29:01', 'active'),
(2674, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2675, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2676, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2677, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2678, 'SUB000198', '893373', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2679, 'SUB000199', '893392', 'ZZPLN905', 'x', 'ZZ214443', 'ZZ_LIMIT', 'ZZ Limited thing', '25', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2680, 'SUB000199', '893392', 'ZZPLN905', 'x', 'ZZ127301', 'ZZ_BASIC', 'ZZ Basic thing', 'YES', '2026-11-21 22:29:02', '2026-11-21 22:29:02', 'active'),
(2808, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2807, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2806, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2804, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2805, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2802, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2803, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2800, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2801, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2798, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2799, 'SUB000209', '893462', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2796, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2797, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2795, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2794, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2793, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2792, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2791, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2788, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2790, 'SUB000208', '893310', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2789, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2787, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2786, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2785, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2784, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2783, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2782, 'SUB000207', '893248', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2781, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2780, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2779, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2778, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2777, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2776, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2775, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2774, 'SUB000206', '893101', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 22:30:00', '2026-11-21 22:30:00', 'active'),
(2809, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2810, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2811, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2812, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2813, 'SUB000210', '893499', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2814, 'SUB000211', '893513', 'ZZPLN905', 'x', 'ZZ521350', 'ZZ_LIMIT', 'ZZ Limited thing', '25', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2815, 'SUB000211', '893513', 'ZZPLN905', 'x', 'ZZ605732', 'ZZ_BASIC', 'ZZ Basic thing', 'YES', '2026-11-21 22:30:01', '2026-11-21 22:30:01', 'active'),
(2950, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2948, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2949, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2947, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2946, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2945, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2944, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2943, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2941, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2942, 'SUB000222', '893434', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2940, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2939, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2937, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2938, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2936, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2935, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2934, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2933, 'SUB000221', '893294', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2931, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2932, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2930, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2929, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2928, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2927, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2926, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2925, 'SUB000220', '893204', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2924, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2923, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2922, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2921, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2920, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2919, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2918, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2917, 'SUB000219', '893055', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:49:00', '2026-11-22 21:49:00', 'active'),
(2951, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2952, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2953, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2954, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2955, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2956, 'SUB000223', '893511', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2957, 'SUB000224', '893529', 'ZZPLN905', 'x', 'ZZ636773', 'ZZ_LIMIT', 'ZZ Limited thing', '25', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(2958, 'SUB000224', '893529', 'ZZPLN905', 'x', 'ZZ971697', 'ZZ_BASIC', 'ZZ Basic thing', 'YES', '2026-11-22 21:49:01', '2026-11-22 21:49:01', 'active'),
(3086, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3085, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3084, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3082, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3083, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3080, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3081, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3078, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3079, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3076, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3077, 'SUB000236', '893492', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3074, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3075, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3073, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3072, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3071, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3070, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3069, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3066, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3068, 'SUB000234', '893299', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3067, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3065, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3064, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3063, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3062, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3061, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3060, 'SUB000233', '893217', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3059, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3058, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3057, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3056, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3055, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3054, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3053, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3052, 'SUB000231', '893082', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:57:52', '2026-11-22 21:57:52', 'active'),
(3087, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3088, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3089, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3090, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3091, 'SUB000237', '893527', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:57:53', '2026-11-22 21:57:53', 'active'),
(3219, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3218, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3217, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3215, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3216, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3213, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3214, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3211, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3212, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3209, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3210, 'SUB000250', '893431', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3207, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3208, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3206, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3205, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3204, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3203, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3202, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3199, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3201, 'SUB000248', '893257', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:58:03', '2026-11-22 22:01:38', 'expired'),
(3200, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3198, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3197, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3196, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3195, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3194, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3193, 'SUB000247', '893199', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3192, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3191, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3190, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3189, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3188, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3187, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3186, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3185, 'SUB000245', '893079', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 21:58:02', '2026-11-22 21:58:02', 'active'),
(3220, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3221, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3222, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3223, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3224, 'SUB000251', '893468', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 21:58:03', '2026-11-22 21:58:03', 'active'),
(3285, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3284, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3283, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3282, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3281, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3280, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3279, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3278, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3277, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3276, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3275, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3274, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3273, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3272, 'SUB000257', '893336', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3271, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3270, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3269, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3268, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3267, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3266, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3265, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3264, 'SUB000256', '893257', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 22:01:38', '2026-11-23 05:40:20', 'expired'),
(3263, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3262, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3261, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3260, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3259, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3258, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3257, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active');
INSERT INTO `usersubscription_details` (`id`, `transNo`, `code`, `planId`, `plan_name`, `fid`, `feature_code`, `feature_name`, `feature_value`, `created_at`, `updated_at`, `is_active`) VALUES
(3256, 'SUB000254', '893097', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 22:01:38', '2026-11-22 22:01:38', 'active'),
(3286, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3287, 'SUB000259', '893481', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3288, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3289, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3290, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3291, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3292, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3293, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3294, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3295, 'SUB000260', '893548', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-22 22:01:39', '2026-11-22 22:01:39', 'active'),
(3603, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3604, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3601, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3602, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3600, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3599, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3598, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3593, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3594, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3595, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3596, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3597, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3592, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3591, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3590, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3589, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3588, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3587, 'SUB000284', '893239', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3586, 'SUB000283', '893134', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3585, 'SUB000283', '893134', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3584, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3583, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3582, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3581, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3580, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3579, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3578, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3577, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3576, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3575, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3573, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3574, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3572, 'SUB000282', '893106', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3611, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3612, 'SUB000285', '893351', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3613, 'SUB000286', '893440', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3614, 'SUB000286', '893440', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3615, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3616, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3617, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3618, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3619, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3620, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3621, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3622, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3623, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3624, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3625, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3626, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3627, 'SUB000287', '893484', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3628, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3629, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3630, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3631, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3632, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3633, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3634, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3635, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3636, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3637, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3638, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3639, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3640, 'SUB000288', '893547', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3641, 'SUB000289', '893561', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(3642, 'SUB000289', '893561', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:34:29', '2026-11-23 05:34:29', 'active'),
(4313, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4314, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4312, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4310, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4311, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4308, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4309, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4302, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4303, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4304, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4305, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4306, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4307, 'SUB000379', '893257', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4301, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4300, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4299, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4297, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4000, 'SUB000344', '723', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(4298, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4295, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(3999, 'SUB000344', '723', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3998, 'SUB000344', '723', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3997, 'SUB000344', '723', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3996, 'SUB000344', '723', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3995, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3994, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3993, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3992, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3991, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3990, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3988, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3989, 'SUB000344', '723', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:39:41', '2026-11-23 05:39:41', 'active'),
(3986, 'SUB000343', '722', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3987, 'SUB000343', '722', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3985, 'SUB000343', '722', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3984, 'SUB000343', '722', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3983, 'SUB000343', '722', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3980, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3981, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3982, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3979, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3978, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3977, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3976, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(3975, 'SUB000343', '722', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:39:40', '2026-11-23 05:39:40', 'active'),
(4296, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4293, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4294, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4292, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4291, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4290, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4289, 'SUB000378', '893196', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4288, 'SUB000377', '893099', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4287, 'SUB000377', '893099', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4286, 'SUB000377', '893099', 'PLN000008', 'Free', 'FID001959', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4285, 'SUB000377', '893099', 'PLN000008', 'Free', 'FID002301', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4284, 'SUB000377', '893099', 'PLN000008', 'Free', 'FID002302', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4279, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4280, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4281, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4277, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4283, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4282, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4278, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4276, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4275, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4274, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4272, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4273, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4271, 'SUB000376', '893064', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4315, 'SUB000380', '893359', 'PLN000008', 'Free', 'FID002302', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4316, 'SUB000380', '893359', 'PLN000008', 'Free', 'FID002301', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4317, 'SUB000380', '893359', 'PLN000008', 'Free', 'FID001959', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4318, 'SUB000380', '893359', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4319, 'SUB000380', '893359', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:40:20', '2026-11-23 05:40:20', 'active'),
(4320, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4321, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4322, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4323, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4324, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4325, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4326, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4327, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4328, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4329, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4330, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4331, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4332, 'SUB000381', '893405', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4333, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4334, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4335, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4336, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4337, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4338, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4339, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4340, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4341, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4342, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4343, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4344, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4345, 'SUB000382', '893477', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4346, 'SUB000383', '893512', 'PLN000008', 'Free', 'FID002302', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4347, 'SUB000383', '893512', 'PLN000008', 'Free', 'FID002301', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4348, 'SUB000383', '893512', 'PLN000008', 'Free', 'FID001959', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4349, 'SUB000383', '893512', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(4350, 'SUB000383', '893512', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:40:21', '2026-11-23 05:40:21', 'active'),
(5230, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5231, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5232, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5229, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5228, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5227, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5225, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5226, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5224, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5223, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5220, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5221, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5222, 'SUB000484', '893300', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5219, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5218, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5217, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5215, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5216, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5214, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5213, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5212, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5211, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5205, 'SUB000482', '893107', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5210, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5209, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5208, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5207, 'SUB000483', '893229', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5206, 'SUB000482', '893107', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5204, 'SUB000482', '893107', 'PLN000008', 'Free', 'FID001959', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5203, 'SUB000482', '893107', 'PLN000008', 'Free', 'FID002301', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5202, 'SUB000482', '893107', 'PLN000008', 'Free', 'FID002302', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5201, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5200, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5199, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5198, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5196, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5197, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5194, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5195, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5193, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5192, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5191, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5190, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5189, 'SUB000481', '893102', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:52:10', '2026-11-23 05:52:10', 'active'),
(5233, 'SUB000485', '893403', 'PLN000008', 'Free', 'FID002302', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5234, 'SUB000485', '893403', 'PLN000008', 'Free', 'FID002301', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5235, 'SUB000485', '893403', 'PLN000008', 'Free', 'FID001959', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5236, 'SUB000485', '893403', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5237, 'SUB000485', '893403', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5238, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5239, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5240, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5241, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5242, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5243, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5244, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5245, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5246, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5247, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5248, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5249, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5250, 'SUB000486', '893452', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5251, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5252, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5253, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5254, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5255, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5256, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5257, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5258, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5259, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID001958', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5260, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID001957', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5261, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID001956', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5262, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID001955', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5263, 'SUB000487', '893465', 'PLN000001', 'Free', 'FID001954', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5264, 'SUB000488', '893475', 'PLN000008', 'Free', 'FID002302', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5265, 'SUB000488', '893475', 'PLN000008', 'Free', 'FID002301', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5266, 'SUB000488', '893475', 'PLN000008', 'Free', 'FID001959', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5267, 'SUB000488', '893475', 'PLN000008', 'STARTER', 'FID001953', 'JOB_APPLICANTS', '30 applicants per month', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active'),
(5268, 'SUB000488', '893475', 'PLN000008', 'STARTER', 'FID000048', 'JOB_POSTING', '1 active job posting', 'YES', '2026-11-23 05:52:11', '2026-11-23 05:52:11', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `usertrainings`
--

DROP TABLE IF EXISTS `usertrainings`;
CREATE TABLE IF NOT EXISTS `usertrainings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `transNo` int DEFAULT NULL,
  `training_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `training_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_completed` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usertrainings`
--

INSERT INTO `usertrainings` (`id`, `code`, `transNo`, `training_title`, `training_provider`, `date_completed`, `created_at`, `updated_at`) VALUES
(15, '718', 1, 'SDV', 'SDV', '2025-10-24', '2025-10-30 14:41:03', '2025-10-30 14:41:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `viewer_code` int NOT NULL,
  `viewed_code` int NOT NULL,
  `activity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `viewer_code` (`viewer_code`),
  KEY `viewed_code` (`viewed_code`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`id`, `viewer_code`, `viewed_code`, `activity_type`, `timestamp`) VALUES
(192, 718, 701, 'view', '2025-11-23 07:31:16');

-- --------------------------------------------------------

--
-- Table structure for table `user_blocks`
--

DROP TABLE IF EXISTS `user_blocks`;
CREATE TABLE IF NOT EXISTS `user_blocks` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `blocker_code` bigint UNSIGNED NOT NULL,
  `blocked_code` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_blocks_blocker_code_blocked_code_unique` (`blocker_code`,`blocked_code`),
  KEY `user_blocks_blocked_code_index` (`blocked_code`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notification_settings`
--

DROP TABLE IF EXISTS `user_notification_settings`;
CREATE TABLE IF NOT EXISTS `user_notification_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `settings` json DEFAULT NULL,
  `seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_notification_settings_user_id_unique` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=741 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_notification_settings`
--

INSERT INTO `user_notification_settings` (`id`, `user_id`, `settings`, `seen_at`, `created_at`, `updated_at`) VALUES
(1, 1629, '{\"messages_enabled\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(2, 1632, '{\"message_preview\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(3, 1635, '{\"unread_badge\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(4, 1638, '{\"desktop_notifications\": false}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(5, 1641, '{\"toast\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(6, 1644, '{\"sound\": false}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(7, 1647, '{\"friend_requests\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(8, 1650, '{\"friend_accepts\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(9, 1653, '{\"post_reactions\": true}', NULL, '2026-11-18 07:59:00', '2026-11-18 07:59:00'),
(10, 1656, '{\"post_comments\": true}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(11, 1659, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(12, 1662, '{\"sound_volume\": 100}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(13, 1671, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(14, 1672, NULL, NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(15, 1674, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(16, 1677, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(17, 1680, NULL, NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(18, 1683, '{\"sound\": true, \"toast\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(19, 1689, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 07:59:01', '2026-11-18 07:59:01'),
(20, 1692, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(21, 1695, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(22, 1698, NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(23, 1701, '{\"friend_requests\": true}', '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(24, 1704, '{\"friend_accepts\": true}', '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(25, 1707, '{\"post_reactions\": true}', '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(26, 1710, '{\"post_comments\": true}', '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(27, 1713, '{\"sound\": false, \"toast\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(28, 1716, NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(29, 1719, NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(30, 1721, NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(31, 1720, NULL, '2026-11-18 07:59:02', '2026-11-18 07:59:02', '2026-11-18 07:59:02'),
(32, 1722, NULL, '2026-11-18 07:59:03', '2026-11-18 07:59:03', '2026-11-18 07:59:03'),
(33, 1725, NULL, '2026-11-18 07:59:03', '2026-11-18 07:59:03', '2026-11-18 07:59:03'),
(34, 1728, NULL, '2026-11-18 07:59:03', '2026-11-18 07:59:03', '2026-11-18 07:59:03'),
(35, 1731, NULL, '2026-11-18 07:59:03', '2026-11-18 07:59:03', '2026-11-18 07:59:03'),
(36, 1740, '{\"messages_enabled\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(37, 1743, '{\"message_preview\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(38, 1746, '{\"unread_badge\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(39, 1749, '{\"desktop_notifications\": false}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(40, 1752, '{\"toast\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(41, 1755, '{\"sound\": false}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(42, 1758, '{\"friend_requests\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(43, 1761, '{\"friend_accepts\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(44, 1764, '{\"post_reactions\": true}', NULL, '2026-11-18 07:59:35', '2026-11-18 07:59:35'),
(45, 1767, '{\"post_comments\": true}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(46, 1770, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(47, 1773, '{\"sound_volume\": 100}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(48, 1782, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(49, 1783, NULL, NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(50, 1785, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(51, 1788, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(52, 1791, NULL, NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(53, 1794, '{\"sound\": true, \"toast\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:36'),
(54, 1800, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 07:59:36', '2026-11-18 07:59:37'),
(55, 1803, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(56, 1806, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(57, 1809, NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(58, 1812, '{\"friend_requests\": true}', '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(59, 1815, '{\"friend_accepts\": true}', '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(60, 1818, '{\"post_reactions\": true}', '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(61, 1821, '{\"post_comments\": true}', '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(62, 1824, '{\"sound\": false, \"toast\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(63, 1827, NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(64, 1830, NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(65, 1832, NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(66, 1831, NULL, '2026-11-18 07:59:37', '2026-11-18 07:59:37', '2026-11-18 07:59:37'),
(67, 1833, NULL, '2026-11-18 08:09:38', '2026-11-18 07:59:38', '2026-11-18 08:09:38'),
(68, 1836, NULL, '2026-11-18 07:59:38', '2026-11-18 07:59:38', '2026-11-18 07:59:38'),
(69, 1839, NULL, '2026-11-18 07:59:38', '2026-11-18 07:59:38', '2026-11-18 07:59:38'),
(70, 1842, NULL, '2026-11-18 07:59:38', '2026-11-18 07:59:38', '2026-11-18 07:59:38'),
(321, 4798, '{\"toast\": true}', NULL, '2026-11-21 20:41:45', '2026-11-21 20:41:45'),
(322, 4801, '{\"sound\": false}', NULL, '2026-11-21 20:41:45', '2026-11-21 20:41:45'),
(323, 4804, '{\"friend_requests\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(324, 4807, '{\"friend_accepts\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(72, 1915, '{\"messages_enabled\": true}', NULL, '2026-11-18 08:23:33', '2026-11-18 08:23:33'),
(73, 1918, '{\"message_preview\": true}', NULL, '2026-11-18 08:23:33', '2026-11-18 08:23:33'),
(74, 1921, '{\"unread_badge\": true}', NULL, '2026-11-18 08:23:33', '2026-11-18 08:23:33'),
(75, 1924, '{\"desktop_notifications\": false}', NULL, '2026-11-18 08:23:33', '2026-11-18 08:23:33'),
(76, 1927, '{\"toast\": true}', NULL, '2026-11-18 08:23:33', '2026-11-18 08:23:33'),
(77, 1930, '{\"sound\": false}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(78, 1933, '{\"friend_requests\": true}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(79, 1936, '{\"friend_accepts\": true}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(80, 1939, '{\"post_reactions\": true}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(81, 1942, '{\"post_comments\": true}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(82, 1945, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(83, 1948, '{\"sound_volume\": 100}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(84, 1957, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(85, 1958, NULL, NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(86, 1960, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-18 08:23:34', '2026-11-18 08:23:34'),
(87, 1963, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(88, 1966, NULL, NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(89, 1969, '{\"sound\": true, \"toast\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(90, 1975, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(91, 1978, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(92, 1981, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(93, 1984, NULL, '2026-11-18 08:23:35', '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(94, 1987, '{\"friend_requests\": true}', '2026-11-18 08:23:35', '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(95, 1990, '{\"friend_accepts\": true}', '2026-11-18 08:23:35', '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(96, 1993, '{\"post_reactions\": true}', '2026-11-18 08:23:35', '2026-11-18 08:23:35', '2026-11-18 08:23:35'),
(97, 1996, '{\"post_comments\": true}', '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(98, 1999, '{\"sound\": false, \"toast\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(99, 2002, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(100, 2005, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(101, 2007, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(102, 2006, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(103, 2008, NULL, '2026-11-18 08:33:36', '2026-11-18 08:23:36', '2026-11-18 08:33:36'),
(104, 2011, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(105, 2014, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(106, 2017, NULL, '2026-11-18 08:23:36', '2026-11-18 08:23:36', '2026-11-18 08:23:36'),
(107, 2220, '{\"messages_enabled\": true}', NULL, '2026-11-18 08:28:21', '2026-11-18 08:28:21'),
(108, 2223, '{\"message_preview\": true}', NULL, '2026-11-18 08:28:21', '2026-11-18 08:28:21'),
(109, 2226, '{\"unread_badge\": true}', NULL, '2026-11-18 08:28:21', '2026-11-18 08:28:21'),
(110, 2229, '{\"desktop_notifications\": false}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(111, 2232, '{\"toast\": true}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(112, 2235, '{\"sound\": false}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(113, 2238, '{\"friend_requests\": true}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(114, 2241, '{\"friend_accepts\": true}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(115, 2244, '{\"post_reactions\": true}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(116, 2247, '{\"post_comments\": true}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(117, 2250, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(118, 2253, '{\"sound_volume\": 100}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(119, 2262, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(120, 2263, NULL, NULL, '2026-11-18 08:28:22', '2026-11-18 08:28:22'),
(121, 2265, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(122, 2268, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(123, 2271, NULL, NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(124, 2274, '{\"sound\": true, \"toast\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(125, 2280, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(126, 2283, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(127, 2286, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(128, 2289, NULL, '2026-11-18 08:28:23', '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(129, 2292, '{\"friend_requests\": true}', '2026-11-18 08:28:23', '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(130, 2295, '{\"friend_accepts\": true}', '2026-11-18 08:28:23', '2026-11-18 08:28:23', '2026-11-18 08:28:23'),
(131, 2298, '{\"post_reactions\": true}', '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(132, 2301, '{\"post_comments\": true}', '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(133, 2304, '{\"sound\": false, \"toast\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(134, 2307, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(135, 2310, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(136, 2312, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(137, 2311, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(138, 2313, NULL, '2026-11-18 08:38:24', '2026-11-18 08:28:24', '2026-11-18 08:38:24'),
(139, 2316, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(140, 2319, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(141, 2322, NULL, '2026-11-18 08:28:24', '2026-11-18 08:28:24', '2026-11-18 08:28:24'),
(142, 91, '{\"sound\": true, \"toast\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": false}', '2026-11-19 06:43:49', '2026-11-18 08:28:39', '2026-11-21 07:48:58'),
(143, 2331, '{\"messages_enabled\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(144, 2334, '{\"message_preview\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(145, 2337, '{\"unread_badge\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(146, 2340, '{\"desktop_notifications\": false}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(147, 2343, '{\"toast\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(148, 2346, '{\"sound\": false}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(149, 2349, '{\"friend_requests\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(150, 2352, '{\"friend_accepts\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(151, 2355, '{\"post_reactions\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(152, 2358, '{\"post_comments\": true}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(153, 2361, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(154, 2364, '{\"sound_volume\": 100}', NULL, '2026-11-18 08:28:44', '2026-11-18 08:28:44'),
(155, 2373, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(156, 2374, NULL, NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(157, 2376, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(158, 2379, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(159, 2382, NULL, NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(160, 2385, '{\"sound\": true, \"toast\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(161, 2391, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(162, 2394, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(163, 2397, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(164, 2400, NULL, '2026-11-18 08:28:45', '2026-11-18 08:28:45', '2026-11-18 08:28:45'),
(165, 2403, '{\"friend_requests\": true}', '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(166, 2406, '{\"friend_accepts\": true}', '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(167, 2409, '{\"post_reactions\": true}', '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(168, 2412, '{\"post_comments\": true}', '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(169, 2415, '{\"sound\": false, \"toast\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(170, 2418, NULL, '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(171, 2421, NULL, '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(172, 2423, NULL, '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(173, 2422, NULL, '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(174, 2424, NULL, '2026-11-18 08:38:46', '2026-11-18 08:28:46', '2026-11-18 08:38:46'),
(175, 2427, NULL, '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(176, 2430, NULL, '2026-11-18 08:28:46', '2026-11-18 08:28:46', '2026-11-18 08:28:46'),
(177, 2433, NULL, '2026-11-18 08:28:47', '2026-11-18 08:28:47', '2026-11-18 08:28:47'),
(178, 2439, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-18 08:28:47', '2026-11-18 08:28:47'),
(179, 2445, '{\"nav_color\": null}', NULL, '2026-11-18 08:28:47', '2026-11-18 08:28:47'),
(180, 2448, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-18 08:28:47', '2026-11-18 08:28:47'),
(181, 2451, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-18 08:28:47', '2026-11-18 08:28:47'),
(182, 2454, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-18 08:28:47', '2026-11-18 08:28:47'),
(183, 2463, '{\"messages_enabled\": true}', NULL, '2026-11-19 04:01:53', '2026-11-19 04:01:53'),
(184, 2466, '{\"message_preview\": true}', NULL, '2026-11-19 04:01:53', '2026-11-19 04:01:53'),
(185, 2469, '{\"unread_badge\": true}', NULL, '2026-11-19 04:01:53', '2026-11-19 04:01:53'),
(186, 2472, '{\"desktop_notifications\": false}', NULL, '2026-11-19 04:01:53', '2026-11-19 04:01:53'),
(187, 2475, '{\"toast\": true}', NULL, '2026-11-19 04:01:53', '2026-11-19 04:01:53'),
(188, 2478, '{\"sound\": false}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(189, 2481, '{\"friend_requests\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(190, 2484, '{\"friend_accepts\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(191, 2487, '{\"post_reactions\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(192, 2490, '{\"post_comments\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(193, 2493, '{\"job_applications\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(194, 2496, '{\"job_interviews\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(195, 2499, '{\"job_status\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(196, 2502, '{\"job_reviews\": true}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(197, 2505, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(198, 2508, '{\"sound_volume\": 100}', NULL, '2026-11-19 04:01:54', '2026-11-19 04:01:54'),
(199, 2517, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(200, 2518, NULL, NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(201, 2520, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(202, 2523, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(203, 2526, NULL, NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(204, 2529, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(205, 2535, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(206, 2538, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:01:55', '2026-11-19 04:01:55'),
(207, 2541, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:01:56', '2026-11-19 04:01:56'),
(208, 2544, NULL, '2026-11-19 04:01:56', '2026-11-19 04:01:56', '2026-11-19 04:01:56'),
(209, 2547, '{\"friend_requests\": true}', '2026-11-19 04:01:56', '2026-11-19 04:01:56', '2026-11-19 04:01:56'),
(210, 2550, '{\"friend_accepts\": true}', '2026-11-19 04:01:56', '2026-11-19 04:01:56', '2026-11-19 04:01:56'),
(211, 2553, '{\"post_reactions\": true}', '2026-11-19 04:01:56', '2026-11-19 04:01:56', '2026-11-19 04:01:56'),
(212, 2556, '{\"post_comments\": true}', '2026-11-19 04:01:56', '2026-11-19 04:01:56', '2026-11-19 04:01:56'),
(213, 2559, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(214, 2562, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(215, 2565, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(216, 2567, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(217, 2566, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(218, 2568, NULL, '2026-11-19 04:11:57', '2026-11-19 04:01:57', '2026-11-19 04:11:57'),
(219, 2571, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(220, 2574, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(221, 2577, NULL, '2026-11-19 04:01:57', '2026-11-19 04:01:57', '2026-11-19 04:01:57'),
(222, 2583, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-19 04:01:58', '2026-11-19 04:01:58'),
(223, 2589, '{\"nav_color\": null}', NULL, '2026-11-19 04:01:58', '2026-11-19 04:01:58'),
(224, 2592, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-19 04:01:58', '2026-11-19 04:01:58'),
(225, 2595, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-19 04:01:58', '2026-11-19 04:01:58'),
(226, 2598, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-19 04:01:58', '2026-11-19 04:01:58'),
(227, 2722, '{\"messages_enabled\": true}', NULL, '2026-11-19 04:04:51', '2026-11-19 04:04:51'),
(228, 2725, '{\"message_preview\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(229, 2728, '{\"unread_badge\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(230, 2731, '{\"desktop_notifications\": false}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(231, 2734, '{\"toast\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(232, 2737, '{\"sound\": false}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(233, 2740, '{\"friend_requests\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(234, 2743, '{\"friend_accepts\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(235, 2746, '{\"post_reactions\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(236, 2749, '{\"post_comments\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(237, 2752, '{\"job_applications\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(238, 2755, '{\"job_interviews\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(239, 2758, '{\"job_status\": true}', NULL, '2026-11-19 04:04:52', '2026-11-19 04:04:52'),
(240, 2761, '{\"job_reviews\": true}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(241, 2764, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(242, 2767, '{\"sound_volume\": 100}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(243, 2776, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(244, 2777, NULL, NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(245, 2779, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(246, 2782, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(247, 2785, NULL, NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(248, 2788, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-19 04:04:53', '2026-11-19 04:04:53'),
(249, 2794, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(250, 2797, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(251, 2800, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(252, 2803, NULL, '2026-11-19 04:04:54', '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(253, 2806, '{\"friend_requests\": true}', '2026-11-19 04:04:54', '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(254, 2809, '{\"friend_accepts\": true}', '2026-11-19 04:04:54', '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(255, 2812, '{\"post_reactions\": true}', '2026-11-19 04:04:54', '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(256, 2815, '{\"post_comments\": true}', '2026-11-19 04:04:54', '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(257, 2818, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-19 04:04:54', '2026-11-19 04:04:54', '2026-11-19 04:04:54'),
(258, 2821, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(259, 2824, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(260, 2826, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(261, 2825, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(262, 2827, NULL, '2026-11-19 04:14:55', '2026-11-19 04:04:55', '2026-11-19 04:14:55'),
(263, 2830, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(264, 2833, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(265, 2836, NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(266, 2842, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(267, 2848, '{\"nav_color\": null}', NULL, '2026-11-19 04:04:55', '2026-11-19 04:04:55'),
(268, 2851, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-19 04:04:56', '2026-11-19 04:04:56'),
(269, 2854, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-19 04:04:56', '2026-11-19 04:04:56'),
(270, 2857, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-19 04:04:56', '2026-11-19 04:04:56'),
(271, 3172, '{\"messages_enabled\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(272, 3175, '{\"message_preview\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(273, 3178, '{\"unread_badge\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(274, 3181, '{\"desktop_notifications\": false}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(275, 3184, '{\"toast\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(276, 3187, '{\"sound\": false}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(277, 3190, '{\"friend_requests\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(278, 3193, '{\"friend_accepts\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(279, 3196, '{\"post_reactions\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(280, 3199, '{\"post_comments\": true}', NULL, '2026-11-19 04:11:33', '2026-11-19 04:11:33'),
(281, 3202, '{\"job_applications\": true}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(282, 3205, '{\"job_interviews\": true}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(283, 3208, '{\"job_status\": true}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(284, 3211, '{\"job_reviews\": true}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(285, 3214, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(286, 3217, '{\"sound_volume\": 100}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(287, 3226, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(288, 3227, NULL, NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(289, 3229, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(290, 3232, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(291, 3235, NULL, NULL, '2026-11-19 04:11:34', '2026-11-19 04:11:34'),
(292, 3238, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(293, 3244, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(294, 3247, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(295, 3250, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(296, 3253, NULL, '2026-11-19 04:11:35', '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(297, 3256, '{\"friend_requests\": true}', '2026-11-19 04:11:35', '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(298, 3259, '{\"friend_accepts\": true}', '2026-11-19 04:11:35', '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(299, 3262, '{\"post_reactions\": true}', '2026-11-19 04:11:35', '2026-11-19 04:11:35', '2026-11-19 04:11:35'),
(300, 3265, '{\"post_comments\": true}', '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(301, 3268, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(302, 3271, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(303, 3274, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(304, 3276, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(305, 3275, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(306, 3277, NULL, '2026-11-19 04:21:36', '2026-11-19 04:11:36', '2026-11-19 04:21:36'),
(307, 3280, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(308, 3283, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(309, 3286, NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(310, 3292, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-19 04:11:36', '2026-11-19 04:11:36'),
(311, 3298, '{\"nav_color\": null}', NULL, '2026-11-19 04:11:37', '2026-11-19 04:11:37'),
(312, 3301, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-19 04:11:37', '2026-11-19 04:11:37'),
(313, 3304, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-19 04:11:37', '2026-11-19 04:11:37'),
(314, 3307, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-19 04:11:37', '2026-11-19 04:11:37'),
(595, 9145, '{\"unread_badge\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(317, 4786, '{\"messages_enabled\": true}', NULL, '2026-11-21 20:41:45', '2026-11-21 20:41:45'),
(318, 4789, '{\"message_preview\": true}', NULL, '2026-11-21 20:41:45', '2026-11-21 20:41:45'),
(319, 4792, '{\"unread_badge\": true}', NULL, '2026-11-21 20:41:45', '2026-11-21 20:41:45'),
(320, 4795, '{\"desktop_notifications\": false}', NULL, '2026-11-21 20:41:45', '2026-11-21 20:41:45'),
(325, 4810, '{\"post_reactions\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(326, 4813, '{\"post_comments\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(327, 4816, '{\"job_applications\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(328, 4819, '{\"job_interviews\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(329, 4822, '{\"job_status\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(330, 4825, '{\"job_reviews\": true}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(331, 4828, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(332, 4831, '{\"sound_volume\": 100}', NULL, '2026-11-21 20:41:46', '2026-11-21 20:41:46'),
(333, 4840, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(334, 4841, NULL, NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(335, 4843, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(336, 4846, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(337, 4849, NULL, NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(338, 4852, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(339, 4858, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(340, 4861, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(341, 4864, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(342, 4867, NULL, '2026-11-21 20:41:47', '2026-11-21 20:41:47', '2026-11-21 20:41:47'),
(343, 4870, '{\"friend_requests\": true}', '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(344, 4873, '{\"friend_accepts\": true}', '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(345, 4876, '{\"post_reactions\": true}', '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(346, 4879, '{\"post_comments\": true}', '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(347, 4882, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(348, 4885, NULL, '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(349, 4888, NULL, '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(350, 4890, NULL, '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(351, 4889, NULL, '2026-11-21 20:41:48', '2026-11-21 20:41:48', '2026-11-21 20:41:48'),
(352, 4891, NULL, '2026-11-21 20:51:48', '2026-11-21 20:41:48', '2026-11-21 20:51:48'),
(353, 4894, NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(354, 4897, NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(355, 4900, NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(356, 4906, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(357, 4912, '{\"nav_color\": null}', NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(358, 4915, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(359, 4918, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(360, 4921, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-21 20:41:49', '2026-11-21 20:41:49'),
(361, 5730, '{\"messages_enabled\": true}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(362, 5733, '{\"message_preview\": true}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(363, 5736, '{\"unread_badge\": true}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(364, 5739, '{\"desktop_notifications\": false}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(365, 5742, '{\"toast\": true}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(366, 5745, '{\"sound\": false}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(367, 5748, '{\"friend_requests\": true}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:08'),
(368, 5751, '{\"friend_accepts\": true}', NULL, '2026-11-21 22:16:08', '2026-11-21 22:16:09'),
(369, 5754, '{\"post_reactions\": true}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(370, 5757, '{\"post_comments\": true}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(371, 5760, '{\"job_applications\": true}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(372, 5763, '{\"job_interviews\": true}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(373, 5766, '{\"job_status\": true}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(374, 5769, '{\"job_reviews\": true}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(375, 5772, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(376, 5775, '{\"sound_volume\": 100}', NULL, '2026-11-21 22:16:09', '2026-11-21 22:16:09'),
(377, 5784, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(378, 5785, NULL, NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(379, 5787, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(380, 5790, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(381, 5793, NULL, NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(382, 5796, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(383, 5802, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(384, 5805, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(385, 5808, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(386, 5811, NULL, '2026-11-21 22:16:10', '2026-11-21 22:16:10', '2026-11-21 22:16:10'),
(387, 5814, '{\"friend_requests\": true}', '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(388, 5817, '{\"friend_accepts\": true}', '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(389, 5820, '{\"post_reactions\": true}', '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(390, 5823, '{\"post_comments\": true}', '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(391, 5826, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(392, 5829, NULL, '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(393, 5832, NULL, '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(394, 5834, NULL, '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(395, 5833, NULL, '2026-11-21 22:16:11', '2026-11-21 22:16:11', '2026-11-21 22:16:11'),
(396, 5835, NULL, '2026-11-21 22:26:11', '2026-11-21 22:16:11', '2026-11-21 22:26:11'),
(397, 5838, NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(398, 5841, NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(399, 5844, NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(400, 5850, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(401, 5856, '{\"nav_color\": null}', NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(402, 5859, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(403, 5862, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(404, 5865, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-21 22:16:12', '2026-11-21 22:16:12'),
(405, 6448, '{\"messages_enabled\": true}', NULL, '2026-11-21 22:27:42', '2026-11-21 22:27:42'),
(406, 6451, '{\"message_preview\": true}', NULL, '2026-11-21 22:27:42', '2026-11-21 22:27:42'),
(407, 6454, '{\"unread_badge\": true}', NULL, '2026-11-21 22:27:42', '2026-11-21 22:27:42'),
(408, 6457, '{\"desktop_notifications\": false}', NULL, '2026-11-21 22:27:42', '2026-11-21 22:27:42'),
(409, 6460, '{\"toast\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(410, 6463, '{\"sound\": false}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(411, 6466, '{\"friend_requests\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(412, 6469, '{\"friend_accepts\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(413, 6472, '{\"post_reactions\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(414, 6475, '{\"post_comments\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(415, 6478, '{\"job_applications\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(416, 6481, '{\"job_interviews\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(417, 6484, '{\"job_status\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(418, 6487, '{\"job_reviews\": true}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(419, 6490, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-21 22:27:43', '2026-11-21 22:27:43'),
(420, 6493, '{\"sound_volume\": 100}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(421, 6502, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(422, 6503, NULL, NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(423, 6505, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(424, 6508, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(425, 6511, NULL, NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(426, 6514, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(427, 6520, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(428, 6523, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:27:44', '2026-11-21 22:27:44'),
(429, 6526, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(430, 6529, NULL, '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(431, 6532, '{\"friend_requests\": true}', '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45');
INSERT INTO `user_notification_settings` (`id`, `user_id`, `settings`, `seen_at`, `created_at`, `updated_at`) VALUES
(432, 6535, '{\"friend_accepts\": true}', '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(433, 6538, '{\"post_reactions\": true}', '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(434, 6541, '{\"post_comments\": true}', '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(435, 6544, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(436, 6547, NULL, '2026-11-21 22:27:45', '2026-11-21 22:27:45', '2026-11-21 22:27:45'),
(437, 6550, NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(438, 6552, NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(439, 6551, NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(440, 6553, NULL, '2026-11-21 22:37:46', '2026-11-21 22:27:46', '2026-11-21 22:37:46'),
(441, 6556, NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(442, 6559, NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(443, 6562, NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(444, 6568, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(445, 6574, '{\"nav_color\": null}', NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(446, 6577, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-21 22:27:46', '2026-11-21 22:27:46'),
(447, 6580, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-21 22:27:47', '2026-11-21 22:27:47'),
(448, 6583, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-21 22:27:47', '2026-11-21 22:27:47'),
(449, 7136, '{\"messages_enabled\": true}', NULL, '2026-11-21 22:28:55', '2026-11-21 22:28:55'),
(450, 7139, '{\"message_preview\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(451, 7142, '{\"unread_badge\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(452, 7145, '{\"desktop_notifications\": false}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(453, 7148, '{\"toast\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(454, 7151, '{\"sound\": false}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(455, 7154, '{\"friend_requests\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(456, 7157, '{\"friend_accepts\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(457, 7160, '{\"post_reactions\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(458, 7163, '{\"post_comments\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(459, 7166, '{\"job_applications\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(460, 7169, '{\"job_interviews\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(461, 7172, '{\"job_status\": true}', NULL, '2026-11-21 22:28:56', '2026-11-21 22:28:56'),
(462, 7175, '{\"job_reviews\": true}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(463, 7178, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(464, 7181, '{\"sound_volume\": 100}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(465, 7190, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(466, 7191, NULL, NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(467, 7193, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(468, 7196, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(469, 7199, NULL, NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(470, 7202, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-21 22:28:57', '2026-11-21 22:28:57'),
(471, 7208, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(472, 7211, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(473, 7214, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(474, 7217, NULL, '2026-11-21 22:28:58', '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(475, 7220, '{\"friend_requests\": true}', '2026-11-21 22:28:58', '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(476, 7223, '{\"friend_accepts\": true}', '2026-11-21 22:28:58', '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(477, 7226, '{\"post_reactions\": true}', '2026-11-21 22:28:58', '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(478, 7229, '{\"post_comments\": true}', '2026-11-21 22:28:58', '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(479, 7232, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-21 22:28:58', '2026-11-21 22:28:58', '2026-11-21 22:28:58'),
(480, 7235, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(481, 7238, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(482, 7240, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(483, 7239, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(484, 7241, NULL, '2026-11-21 22:38:59', '2026-11-21 22:28:59', '2026-11-21 22:38:59'),
(485, 7244, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(486, 7247, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(487, 7250, NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(488, 7256, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-21 22:28:59', '2026-11-21 22:28:59'),
(489, 7262, '{\"nav_color\": null}', NULL, '2026-11-21 22:28:59', '2026-11-21 22:29:00'),
(490, 7265, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-21 22:29:00', '2026-11-21 22:29:00'),
(491, 7268, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-21 22:29:00', '2026-11-21 22:29:00'),
(492, 7271, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-21 22:29:00', '2026-11-21 22:29:00'),
(493, 7824, '{\"messages_enabled\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(494, 7827, '{\"message_preview\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(495, 7830, '{\"unread_badge\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(496, 7833, '{\"desktop_notifications\": false}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(497, 7836, '{\"toast\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(498, 7839, '{\"sound\": false}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(499, 7842, '{\"friend_requests\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(500, 7845, '{\"friend_accepts\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(501, 7848, '{\"post_reactions\": true}', NULL, '2026-11-21 22:29:55', '2026-11-21 22:29:55'),
(502, 7851, '{\"post_comments\": true}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(503, 7854, '{\"job_applications\": true}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(504, 7857, '{\"job_interviews\": true}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(505, 7860, '{\"job_status\": true}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(506, 7863, '{\"job_reviews\": true}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(507, 7866, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(508, 7869, '{\"sound_volume\": 100}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(509, 7878, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(510, 7879, NULL, NULL, '2026-11-21 22:29:56', '2026-11-21 22:29:56'),
(511, 7881, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(512, 7884, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(513, 7887, NULL, NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(514, 7890, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(515, 7896, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(516, 7899, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(517, 7902, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(518, 7905, NULL, '2026-11-21 22:29:57', '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(519, 7908, '{\"friend_requests\": true}', '2026-11-21 22:29:57', '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(520, 7911, '{\"friend_accepts\": true}', '2026-11-21 22:29:57', '2026-11-21 22:29:57', '2026-11-21 22:29:57'),
(521, 7914, '{\"post_reactions\": true}', '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(522, 7917, '{\"post_comments\": true}', '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(523, 7920, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(524, 7923, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(525, 7926, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(526, 7928, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(527, 7927, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(528, 7929, NULL, '2026-11-21 22:39:58', '2026-11-21 22:29:58', '2026-11-21 22:39:58'),
(529, 7932, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(530, 7935, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(531, 7938, NULL, '2026-11-21 22:29:58', '2026-11-21 22:29:58', '2026-11-21 22:29:58'),
(532, 7944, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-21 22:29:59', '2026-11-21 22:29:59'),
(533, 7950, '{\"nav_color\": null}', NULL, '2026-11-21 22:29:59', '2026-11-21 22:29:59'),
(534, 7953, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-21 22:29:59', '2026-11-21 22:29:59'),
(535, 7956, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false}', NULL, '2026-11-21 22:29:59', '2026-11-21 22:29:59'),
(536, 7959, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-21 22:29:59', '2026-11-21 22:29:59'),
(538, 8274, NULL, '2026-11-22 20:49:33', '2026-11-22 20:49:33', '2026-11-22 20:49:33'),
(591, 8822, NULL, '2026-11-22 23:32:50', '2026-11-22 23:32:50', '2026-11-22 23:32:50'),
(592, 9136, '{\"email_application_updates\": true}', NULL, '2026-11-23 01:31:51', '2026-11-23 01:31:51'),
(593, 9139, '{\"messages_enabled\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(594, 9142, '{\"message_preview\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(539, 8281, '{\"email_application_updates\": false}', NULL, '2026-11-22 21:38:26', '2026-11-22 21:38:26'),
(540, 8282, '{\"email_application_updates\": false}', NULL, '2026-11-22 21:38:26', '2026-11-22 21:38:26'),
(541, 8283, '{\"email_application_updates\": false}', NULL, '2026-11-22 21:38:26', '2026-11-22 21:38:26'),
(542, 8284, '{\"email_application_updates\": false}', NULL, '2026-11-22 21:38:26', '2026-11-22 21:38:26'),
(543, 8285, '{\"email_application_updates\": false}', NULL, '2026-11-22 21:38:26', '2026-11-22 21:38:26'),
(544, 8286, '{\"email_application_updates\": true}', NULL, '2026-11-22 21:38:26', '2026-11-22 21:38:27'),
(545, 8288, '{\"email_application_updates\": false}', NULL, '2026-11-22 21:38:27', '2026-11-22 21:38:27'),
(546, 8295, '{\"email_application_updates\": true}', NULL, '2026-11-22 21:38:32', '2026-11-22 21:38:32'),
(547, 8298, '{\"messages_enabled\": true}', NULL, '2026-11-22 21:38:32', '2026-11-22 21:38:32'),
(548, 8301, '{\"message_preview\": true}', NULL, '2026-11-22 21:38:32', '2026-11-22 21:38:32'),
(549, 8304, '{\"unread_badge\": true}', NULL, '2026-11-22 21:38:32', '2026-11-22 21:38:32'),
(550, 8307, '{\"desktop_notifications\": false}', NULL, '2026-11-22 21:38:32', '2026-11-22 21:38:32'),
(551, 8310, '{\"toast\": true}', NULL, '2026-11-22 21:38:32', '2026-11-22 21:38:32'),
(552, 8313, '{\"sound\": false}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(553, 8316, '{\"friend_requests\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(554, 8319, '{\"friend_accepts\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(555, 8322, '{\"post_reactions\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(556, 8325, '{\"post_comments\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(557, 8328, '{\"job_applications\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(558, 8331, '{\"job_interviews\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(559, 8334, '{\"job_status\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(560, 8337, '{\"job_reviews\": true}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(561, 8340, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(562, 8343, '{\"sound_volume\": 100}', NULL, '2026-11-22 21:38:33', '2026-11-22 21:38:33'),
(563, 8352, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(564, 8353, NULL, NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(565, 8355, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(566, 8358, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(567, 8361, NULL, NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(568, 8364, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true, \"email_application_updates\": true}', NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(569, 8370, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(570, 8373, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-22 21:38:34', '2026-11-22 21:38:34'),
(571, 8376, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(572, 8379, NULL, '2026-11-22 21:38:35', '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(573, 8382, '{\"friend_requests\": true}', '2026-11-22 21:38:35', '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(574, 8385, '{\"friend_accepts\": true}', '2026-11-22 21:38:35', '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(575, 8388, '{\"post_reactions\": true}', '2026-11-22 21:38:35', '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(576, 8391, '{\"post_comments\": true}', '2026-11-22 21:38:35', '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(577, 8394, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', '2026-11-22 21:38:35', '2026-11-22 21:38:35', '2026-11-22 21:38:35'),
(578, 8397, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(579, 8400, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(580, 8402, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(581, 8401, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(582, 8403, NULL, '2026-11-22 21:48:36', '2026-11-22 21:38:36', '2026-11-22 21:48:36'),
(583, 8406, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(584, 8409, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(585, 8412, NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(586, 8418, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(587, 8424, '{\"nav_color\": null}', NULL, '2026-11-22 21:38:36', '2026-11-22 21:38:36'),
(588, 8427, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-22 21:38:37', '2026-11-22 21:38:37'),
(589, 8430, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', NULL, '2026-11-22 21:38:37', '2026-11-22 21:38:37'),
(590, 8433, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-22 21:38:37', '2026-11-22 21:38:37'),
(596, 9148, '{\"desktop_notifications\": false}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(597, 9151, '{\"toast\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(598, 9154, '{\"sound\": false}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(599, 9157, '{\"friend_requests\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(600, 9160, '{\"friend_accepts\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(601, 9163, '{\"post_reactions\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(602, 9166, '{\"post_comments\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(603, 9169, '{\"job_applications\": true}', NULL, '2026-11-23 01:31:52', '2026-11-23 01:31:52'),
(604, 9172, '{\"job_interviews\": true}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(605, 9175, '{\"job_status\": true}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(606, 9178, '{\"job_reviews\": true}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(607, 9181, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(608, 9184, '{\"sound_volume\": 100}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(609, 9193, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(610, 9194, NULL, NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(611, 9196, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(612, 9199, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(613, 9202, NULL, NULL, '2026-11-23 01:31:53', '2026-11-23 01:31:53'),
(614, 9205, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true, \"email_application_updates\": true}', NULL, '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(615, 9211, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(616, 9214, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(617, 9217, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(618, 9220, NULL, '2026-11-23 01:31:54', '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(619, 9223, '{\"friend_requests\": true}', '2026-11-23 01:31:54', '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(620, 9226, '{\"friend_accepts\": true}', '2026-11-23 01:31:54', '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(621, 9229, '{\"post_reactions\": true}', '2026-11-23 01:31:54', '2026-11-23 01:31:54', '2026-11-23 01:31:54'),
(622, 9232, '{\"post_comments\": true}', '2026-11-23 01:31:54', '2026-11-23 01:31:54', '2026-11-23 01:31:55'),
(623, 9235, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(624, 9238, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(625, 9241, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(626, 9243, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(627, 9242, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(628, 9244, NULL, '2026-11-23 01:41:55', '2026-11-23 01:31:55', '2026-11-23 01:41:55'),
(629, 9247, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(630, 9250, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(631, 9253, NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(632, 9259, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-23 01:31:55', '2026-11-23 01:31:55'),
(633, 9265, '{\"nav_color\": null}', NULL, '2026-11-23 01:31:56', '2026-11-23 01:31:56'),
(634, 9268, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-23 01:31:56', '2026-11-23 01:31:56'),
(635, 9271, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', NULL, '2026-11-23 01:31:56', '2026-11-23 01:31:56'),
(636, 9274, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-23 01:31:56', '2026-11-23 01:31:56'),
(637, 9653, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:39:45', '2026-11-23 05:39:45'),
(638, 9654, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:39:45', '2026-11-23 05:39:45'),
(639, 9655, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:39:45', '2026-11-23 05:39:45'),
(640, 9656, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:39:46', '2026-11-23 05:39:46'),
(641, 9657, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:39:46', '2026-11-23 05:39:46'),
(642, 9658, '{\"email_application_updates\": true}', NULL, '2026-11-23 05:39:46', '2026-11-23 05:39:46'),
(643, 9660, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:39:46', '2026-11-23 05:39:46'),
(644, 9944, '{\"email_application_updates\": true}', NULL, '2026-11-23 05:40:13', '2026-11-23 05:40:13'),
(645, 9947, '{\"messages_enabled\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(646, 9950, '{\"message_preview\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(647, 9953, '{\"unread_badge\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(648, 9956, '{\"desktop_notifications\": false}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(649, 9959, '{\"toast\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(650, 9962, '{\"sound\": false}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(651, 9965, '{\"friend_requests\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(652, 9968, '{\"friend_accepts\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(653, 9971, '{\"post_reactions\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(654, 9974, '{\"post_comments\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(655, 9977, '{\"job_applications\": true}', NULL, '2026-11-23 05:40:14', '2026-11-23 05:40:14'),
(656, 9980, '{\"job_interviews\": true}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(657, 9983, '{\"job_status\": true}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(658, 9986, '{\"job_reviews\": true}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(659, 9989, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(660, 9992, '{\"sound_volume\": 100}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(661, 10001, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(662, 10002, NULL, NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(663, 10004, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(664, 10007, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(665, 10010, NULL, NULL, '2026-11-23 05:40:15', '2026-11-23 05:40:15'),
(666, 10013, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true, \"email_application_updates\": true}', NULL, '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(667, 10019, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(668, 10022, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(669, 10025, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(670, 10028, NULL, '2026-11-23 05:40:16', '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(671, 10031, '{\"friend_requests\": true}', '2026-11-23 05:40:16', '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(672, 10034, '{\"friend_accepts\": true}', '2026-11-23 05:40:16', '2026-11-23 05:40:16', '2026-11-23 05:40:16'),
(673, 10037, '{\"post_reactions\": true}', '2026-11-23 05:40:16', '2026-11-23 05:40:16', '2026-11-23 05:40:17'),
(674, 10040, '{\"post_comments\": true}', '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(675, 10043, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(676, 10046, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(677, 10049, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(678, 10051, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(679, 10050, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(680, 10052, NULL, '2026-11-23 05:50:17', '2026-11-23 05:40:17', '2026-11-23 05:50:17'),
(681, 10055, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(682, 10058, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(683, 10061, NULL, '2026-11-23 05:40:17', '2026-11-23 05:40:17', '2026-11-23 05:40:17'),
(684, 10067, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-23 05:40:18', '2026-11-23 05:40:18'),
(685, 10073, '{\"nav_color\": null}', NULL, '2026-11-23 05:40:18', '2026-11-23 05:40:18'),
(686, 10076, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-23 05:40:18', '2026-11-23 05:40:18'),
(687, 10079, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', NULL, '2026-11-23 05:40:18', '2026-11-23 05:40:18'),
(688, 10082, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-23 05:40:18', '2026-11-23 05:40:18'),
(689, 10611, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(690, 10612, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(691, 10613, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(692, 10614, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(693, 10615, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(694, 10616, '{\"email_application_updates\": true}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(695, 10618, '{\"email_application_updates\": false}', NULL, '2026-11-23 05:51:29', '2026-11-23 05:51:29'),
(696, 10905, '{\"email_application_updates\": true}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(697, 10908, '{\"messages_enabled\": true}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(698, 10911, '{\"message_preview\": true}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(699, 10914, '{\"unread_badge\": true}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(700, 10917, '{\"desktop_notifications\": false}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(701, 10920, '{\"toast\": true}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(702, 10923, '{\"sound\": false}', NULL, '2026-11-23 05:52:00', '2026-11-23 05:52:00'),
(703, 10926, '{\"friend_requests\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(704, 10929, '{\"friend_accepts\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(705, 10932, '{\"post_reactions\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(706, 10935, '{\"post_comments\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(707, 10938, '{\"job_applications\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(708, 10941, '{\"job_interviews\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(709, 10944, '{\"job_status\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:01'),
(710, 10947, '{\"job_reviews\": true}', NULL, '2026-11-23 05:52:01', '2026-11-23 05:52:02'),
(711, 10950, '{\"sound\": true, \"toast\": false, \"message_preview\": false}', NULL, '2026-11-23 05:52:02', '2026-11-23 05:52:02'),
(712, 10953, '{\"sound_volume\": 100}', NULL, '2026-11-23 05:52:02', '2026-11-23 05:52:02'),
(713, 10962, '{\"sound\": true, \"toast\": false, \"friend_requests\": false}', NULL, '2026-11-23 05:52:02', '2026-11-23 05:52:02'),
(714, 10963, NULL, NULL, '2026-11-23 05:52:02', '2026-11-23 05:52:02'),
(715, 10965, '{\"sound\": true, \"sound_volume\": 35, \"unread_badge\": false}', NULL, '2026-11-23 05:52:02', '2026-11-23 05:52:02'),
(716, 10968, '{\"sound\": true, \"toast\": false}', NULL, '2026-11-23 05:52:02', '2026-11-23 05:52:02'),
(717, 10971, NULL, NULL, '2026-11-23 05:52:03', '2026-11-23 05:52:03'),
(718, 10974, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": true, \"email_application_updates\": true}', NULL, '2026-11-23 05:52:03', '2026-11-23 05:52:03'),
(719, 10980, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 05:52:03', '2026-11-23 05:52:03'),
(720, 10983, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 05:52:03', '2026-11-23 05:52:03'),
(721, 10986, '{\"unread_badge\": false, \"messages_enabled\": false}', NULL, '2026-11-23 05:52:03', '2026-11-23 05:52:03'),
(722, 10989, NULL, '2026-11-23 05:52:04', '2026-11-23 05:52:04', '2026-11-23 05:52:04'),
(723, 10992, '{\"friend_requests\": true}', '2026-11-23 05:52:04', '2026-11-23 05:52:04', '2026-11-23 05:52:04'),
(724, 10995, '{\"friend_accepts\": true}', '2026-11-23 05:52:04', '2026-11-23 05:52:04', '2026-11-23 05:52:04'),
(725, 10998, '{\"post_reactions\": true}', '2026-11-23 05:52:04', '2026-11-23 05:52:04', '2026-11-23 05:52:04'),
(726, 11001, '{\"post_comments\": true}', '2026-11-23 05:52:04', '2026-11-23 05:52:04', '2026-11-23 05:52:04'),
(727, 11004, '{\"sound\": false, \"toast\": false, \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(728, 11007, NULL, '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(729, 11010, NULL, '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(730, 11012, NULL, '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(731, 11011, NULL, '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(732, 11013, NULL, '2026-11-23 06:02:05', '2026-11-23 05:52:05', '2026-11-23 06:02:05'),
(733, 11016, NULL, '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(734, 11019, NULL, '2026-11-23 05:52:05', '2026-11-23 05:52:05', '2026-11-23 05:52:05'),
(735, 11022, NULL, '2026-11-23 05:52:06', '2026-11-23 05:52:06', '2026-11-23 05:52:06'),
(736, 11028, '{\"nav_color\": \"#0a66c2\"}', NULL, '2026-11-23 05:52:06', '2026-11-23 05:52:06'),
(737, 11034, '{\"nav_color\": null}', NULL, '2026-11-23 05:52:06', '2026-11-23 05:52:06'),
(738, 11037, '{\"nav_color\": \"#1b8a4b\"}', NULL, '2026-11-23 05:52:06', '2026-11-23 05:52:06'),
(739, 11040, '{\"sound\": false, \"toast\": false, \"nav_color\": \"#6a1b9a\", \"job_status\": false, \"job_reviews\": false, \"unread_badge\": false, \"post_comments\": false, \"friend_accepts\": false, \"job_interviews\": false, \"post_reactions\": false, \"friend_requests\": false, \"message_preview\": false, \"job_applications\": false, \"messages_enabled\": false, \"desktop_notifications\": false, \"email_application_updates\": false}', NULL, '2026-11-23 05:52:06', '2026-11-23 05:52:06'),
(740, 11043, '{\"nav_color\": \"#c62828\"}', NULL, '2026-11-23 05:52:07', '2026-11-23 05:52:07');

-- --------------------------------------------------------

--
-- Table structure for table `user_resumes`
--

DROP TABLE IF EXISTS `user_resumes`;
CREATE TABLE IF NOT EXISTS `user_resumes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` bigint UNSIGNED NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int UNSIGNED NOT NULL DEFAULT '0',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_resumes_code_index` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=464 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_resumes`
--

INSERT INTO `user_resumes` (`id`, `code`, `original_name`, `path`, `size`, `is_default`, `created_at`, `updated_at`) VALUES
(15, 719, 'CV_Pedro_Yorpo (3).pdf', 'uploads/719/resumes/b131b9a7-1f61-497a-8e32-583bd343d72b.pdf', 251666, 1, '2026-11-22 23:30:08', '2026-11-22 23:30:08'),
(340, 721, 'YORPO-CV.pdf', 'uploads/721/resumes/2cb43f9d-f669-4039-94d8-d82a0223fa9d.pdf', 283213, 1, '2026-11-23 04:53:22', '2026-11-23 04:53:22');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`viewer_code`) REFERENCES `users` (`code`),
  ADD CONSTRAINT `user_activity_ibfk_2` FOREIGN KEY (`viewed_code`) REFERENCES `users` (`code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
