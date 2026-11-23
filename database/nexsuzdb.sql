-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 22, 2026 at 01:40 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `application_status_history`
--

INSERT INTO `application_status_history` (`id`, `applied_id`, `transNo`, `from_status`, `to_status`, `reason`, `source`, `changed_by`, `changed_by_name`, `ai_snapshot`, `notified`, `created_at`) VALUES
(1, 49, 'TR-000004', NULL, 'applied_active', 'Application received', 'applicant', '702', 'Pedro Yorpo', NULL, 0, '2026-07-03 05:49:17'),
(3, 51, 'TR-000002', NULL, 'applied_active', 'Application received', 'applicant', '702', 'Pedro Yorpo', NULL, 0, '2026-08-08 22:40:03'),
(27, 76, 'TR-000001', NULL, 'applied_active', 'Application received', 'applicant', '702', 'Pedro Yorpo', NULL, 0, '2026-11-21 09:17:26'),
(28, 76, 'TR-000001', 'applied_active', 'review', 'Automatic screening: 100% on the questions with a correct answer (pass mark 100%)', 'auto_screen', NULL, NULL, NULL, 0, '2026-11-21 09:17:26'),
(29, 76, 'TR-000001', 'review', 'interview', 'Interview scheduled', 'recruiter', '703', 'Elizabeth Punay', '{\"evaluation_id\":21,\"score\":80,\"recommendation\":\"qualified\",\"provider\":\"local\"}', 1, '2026-11-21 09:23:27'),
(30, 76, 'TR-000001', 'interview', 'approved', NULL, 'recruiter', '703', 'Elizabeth Punay', '{\"evaluation_id\":22,\"score\":80,\"recommendation\":\"qualified\",\"provider\":\"local\"}', 1, '2026-11-21 09:26:32');

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

--
-- Dumping data for table `applied_answers`
--

INSERT INTO `applied_answers` (`id`, `applied_id`, `question_id`, `transNo`, `code`, `answer_text`, `created_at`, `updated_at`) VALUES
(9, 76, 261, 'TR-000001', '702', 'Yes', '2026-11-21 09:17:26', '2026-11-21 09:17:26'),
(10, 76, 262, 'TR-000001', '702', '3', '2026-11-21 09:17:26', '2026-11-21 09:17:26');

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
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applied_jobs`
--

INSERT INTO `applied_jobs` (`applied_id`, `transNo`, `job_name`, `email`, `country_code`, `phone_number`, `code`, `role_code`, `fullname`, `applied_status`, `created_at`, `updated_at`) VALUES
(49, 'TR-000004', 'test33', 'pedroyorpo22@gmail.com', '63', '09072493545', '702', 'DEF-USERS', 'Pedro Yorpo', 'applied_active', '2026-07-03 05:49:17', '2026-07-03 05:49:17'),
(51, 'TR-000002', 'ESL', 'pedroyorpo22@gmail.com', '880', '09072493545', '702', 'DEF-USERS', 'Pedro Yorpo', 'applied_active', '2026-08-08 22:40:03', '2026-08-08 22:40:03'),
(76, 'TR-000001', 'Software Developer', 'pedroyorpo22@gmail.com', '63', '9072493545', '702', 'DEF-USERS', 'Pedro Yorpo', 'approved', '2026-11-21 09:17:26', '2026-11-21 09:26:32');

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
(260, 'TR-000006', 'Are you comfortable communicating in English?', NULL, 'yes', '701', 'DEF-MASTERADMIN', 'Customer Service Representative (CSR)', 'ABC Company', 'active', '2026-11-21 06:45:13', '2026-11-21 06:45:13', 'yes_no', NULL, 1, 'yes', 3),
(261, 'TR-000001', 'Are you willing to relocate?', 'Yes', 'general', '703', 'DEF-CLIENT', 'Software Developer', 'ELIZABETH Company', 'active', '2026-11-21 08:38:35', '2026-11-21 09:17:26', 'multiple_choice', '[\"Yes\",\"No\"]', 1, 'Yes', 0),
(262, 'TR-000001', 'How many year\'s experience?', '3', 'general', '703', 'DEF-CLIENT', 'Software Developer', 'ELIZABETH Company', 'active', '2026-11-21 08:38:35', '2026-11-21 09:17:26', 'numeric', NULL, 1, '3', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applied_resumes`
--

INSERT INTO `applied_resumes` (`resume_id`, `transNo`, `resume_pdf`, `job_name`, `role_code`, `code`, `fullname`, `company`, `created_at`, `updated_at`) VALUES
(48, 'TR-000004', 'uploads/702/AppliedJobsResume/cceccdb7-c37c-4f0e-b374-4b43c9577e22.pdf', 'test33', 'DEF-USERS', '702', 'Pedro Yorpo', NULL, '2026-07-03 05:49:17', '2026-07-03 05:49:17'),
(50, 'TR-000002', 'uploads/702/AppliedJobsResume/bf8680cb-35bc-41a6-b732-9a221c862a59.pdf', 'ESL', 'DEF-USERS', '702', 'Pedro Yorpo', NULL, '2026-08-08 22:40:03', '2026-08-08 22:40:03'),
(70, 'TR-000001', 'uploads/702/AppliedJobsResume/282f263a-968b-41c4-9e75-25e7fc8e2070.pdf', 'Software Developer', 'DEF-USERS', '702', 'Pedro Yorpo', NULL, '2026-11-21 09:17:26', '2026-11-21 09:17:26');

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
) ENGINE=InnoDB AUTO_INCREMENT=819 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attachmentposts`
--

INSERT INTO `attachmentposts` (`id`, `code`, `transNo`, `posts_uuid`, `posts_uuind`, `status`, `path_url`, `posts_type`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 711, 1, 'c225d726-722c-4949-a7a8-8b2f23a360d2', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 1, 'storage/app/public/uploads/posts/711/c225d726-722c-4949-a7a8-8b2f23a360d2/1744183737_cutedog.jpg', 'image', 'Human$ crazy', NULL, NULL, NULL),
(2, 711, 1, 'c225d726-722c-4949-a7a8-8b2f23a360d2', '399a6fe7-d1ac-42bf-825c-648294be889f', 1, 'storage/app/public/uploads/posts/711/c225d726-722c-4949-a7a8-8b2f23a360d2/1744183737_DEFAULTPROFILE.png', 'image', 'Human$ crazy', NULL, NULL, NULL),
(3, 711, 1, 'c225d726-722c-4949-a7a8-8b2f23a360d2', '5eeec985-96c9-48e6-886b-6443b0dc5185', 1, 'storage/app/public/uploads/posts/711/c225d726-722c-4949-a7a8-8b2f23a360d2/1744183737_nexsuzlogo.jpg', 'image', 'Human$ crazy', NULL, NULL, NULL),
(29, 712, 10, 'be5fdcdf-e86c-4285-a609-d5263815fad2', '2343d813-724f-4953-8c39-1016f07112a1', 1, 'storage/app/public/uploads/posts/712/be5fdcdf-e86c-4285-a609-d5263815fad2/1744550441_clients.PNG', 'image', 'Nexsuz', NULL, NULL, NULL),
(30, 712, 11, '98a1244c-24a8-451c-9fa9-ec6f51521d6c', '2d1f36ac-1702-4a8a-b933-faed7945454d', 1, 'storage/app/public/uploads/posts/712/98a1244c-24a8-451c-9fa9-ec6f51521d6c/1744550725_logo.jfif', 'image', 'Nexsuz', NULL, NULL, NULL),
(78, 703, 17, '1e7cfa1e-672f-42ab-8283-446ffb902ebc', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 1, 'storage/app/public/uploads/posts/703/1e7cfa1e-672f-42ab-8283-446ffb902ebc/1745647622_boo.jpg', 'image', 'Elizabeth Punay', NULL, NULL, NULL),
(88, 702, 24, '36aade6f-e355-46ca-8d34-bf4970513c06', '928785b3-af8d-4124-a889-a40fd4da9c85', 1, 'storage/app/public/uploads/posts/702/36aade6f-e355-46ca-8d34-bf4970513c06/1747756213_clients.PNG', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(100, 702, 30, '796eed33-74d0-40f0-a608-11136d627ff1', '6b84ecab-7bcb-4725-84b6-64e8b1a7aaa0', 0, 'storage/app/public/uploads/posts/702/bf71e69f-a933-4b3a-be1d-1ce1e9ceef15/1755525055_2.PNG', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(111, 702, 33, '63bca6e7-a1ce-4ad1-8d96-df7a5baeb1ce', 'ce2d96b9-62a8-4558-933d-f2429372ce5b', 0, '/storage/app/public/uploads/posts/702/63bca6e7-a1ce-4ad1-8d96-df7a5baeb1ce/1755611355_small-vecteezy_digital-marketing-media-website-ad-social-network-seo_13556205_small.mp4', 'video', 'Pedro Yorpo', NULL, NULL, NULL),
(112, 702, 34, 'cba585bb-e0e1-41c9-b154-1c7e8eecdbd3', 'e4e2749d-34c0-45d5-9919-b417591fac7f', 0, '/storage/app/public/uploads/posts/702/cba585bb-e0e1-41c9-b154-1c7e8eecdbd3/1755748791_1000003460.mp4', 'video', 'Pedro Yorpo', NULL, NULL, NULL),
(113, 702, 35, 'f2191956-3576-48c8-90a9-c79d6062149f', '808febaf-b8ee-41f8-8e38-bdc85747bf2c', 1, '/storage/app/public/uploads/posts/702/f2191956-3576-48c8-90a9-c79d6062149f/1756094841_SampleVideo_1280x720_5mb.mp4', 'video', 'Pedro Yorpo', NULL, NULL, NULL),
(114, 702, 36, '7267ee5a-dee4-4fe6-8c50-76b1cf4ddbd0', 'd4b0026a-f768-4d98-b0c8-2a17e27b7c61', 1, '/storage/app/public/uploads/posts/702/7267ee5a-dee4-4fe6-8c50-76b1cf4ddbd0/1756120599_small-vecteezy_digital-marketing-media-website-ad-social-network-seo_13556205_small.mp4', 'video', 'Pedro Yorpo', NULL, NULL, NULL),
(119, 705, 41, '91b2619b-bb66-4070-ab85-5d2ba2d5bac7', 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 1, '/storage/app/public/uploads/posts/705/91b2619b-bb66-4070-ab85-5d2ba2d5bac7/1757944895_sample.png', 'image', 'David Dela Cruz', NULL, NULL, NULL),
(120, 705, 41, '91b2619b-bb66-4070-ab85-5d2ba2d5bac7', '764f512d-88e2-46e6-971f-f249b3966b27', 1, '/storage/app/public/uploads/posts/705/91b2619b-bb66-4070-ab85-5d2ba2d5bac7/1757944895_users-logo-275-5851.png', 'image', 'David Dela Cruz', NULL, NULL, NULL),
(121, 705, 41, '91b2619b-bb66-4070-ab85-5d2ba2d5bac7', '0c01fe92-660e-4c4c-af54-cacd9cec6925', 1, '/storage/app/public/uploads/posts/705/91b2619b-bb66-4070-ab85-5d2ba2d5bac7/1757944895_2.PNG', 'image', 'David Dela Cruz', NULL, NULL, NULL),
(122, 714, 42, '3f5bd873-2425-4b87-87f4-9b560428b777', '0d8d64f2-1e95-458d-a931-ecdf0aacd160', 1, '/storage/app/public/uploads/posts/714/3f5bd873-2425-4b87-87f4-9b560428b777/1758350795_FPSO1.jpg', 'image', 'TJ Kim', NULL, NULL, NULL),
(124, 702, 45, 'f3194f52-17c7-418d-9b6a-60f48032d1f7', 'ca5746bf-adf6-48a5-8f1d-39a44ca03461', 1, '/storage/app/public/uploads/posts/702/f3194f52-17c7-418d-9b6a-60f48032d1f7/1760651707_pic.PNG', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(125, 713, 47, '5cf3afd0-0a01-41c4-a119-32bd3dfffa33', 'af2aa354-4c34-40c6-b48f-aadaf67f38bc', 1, '/storage/app/public/uploads/posts/713/5cf3afd0-0a01-41c4-a119-32bd3dfffa33/1760753015_1000083292.png', 'image', 'STEVEN YORPO', NULL, NULL, NULL),
(132, 701, 77, '200eb893-a052-44bf-b6e7-005ba6302b77', '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 1, '/storage/app/public/uploads/posts/701/200eb893-a052-44bf-b6e7-005ba6302b77/1762668316_wallpaper.jpg', 'image', 'Pedro yorpo', NULL, NULL, NULL),
(133, 701, 78, 'a66c6328-afe7-4a3b-8b4d-ecb096c4d587', '684b81eb-0baa-4975-88f5-361d48feef46', 1, '/storage/app/public/uploads/posts/701/a66c6328-afe7-4a3b-8b4d-ecb096c4d587/1762696047_wallpaper.jpg', 'image', 'Pedro yorpo', NULL, NULL, NULL),
(134, 701, 78, 'a66c6328-afe7-4a3b-8b4d-ecb096c4d587', '1ab65dc5-a251-4e83-8509-a6d5f21b8e3b', 1, '/storage/app/public/uploads/posts/701/a66c6328-afe7-4a3b-8b4d-ecb096c4d587/1762696047_cover.jpg', 'image', 'Pedro yorpo', NULL, NULL, NULL),
(135, 701, 79, '1b858cf1-456c-4ac0-b90b-c45e917a05e9', '970ea541-a10d-4358-b006-1932fa7d2701', 1, '/storage/app/public/uploads/posts/701/1b858cf1-456c-4ac0-b90b-c45e917a05e9/1762697099_Web Design Promotional Video - After Effects Template.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(136, 701, 80, 'c99c24b6-223b-4ae2-94b9-18c453c421d0', '0dd91479-ad18-4fe1-9d31-368a4d3fd517', 1, '/storage/app/public/uploads/posts/701/c99c24b6-223b-4ae2-94b9-18c453c421d0/1763206440_Web Design Promotional Video - After Effects Template.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(137, 701, 81, 'c61b6ec4-0228-4e50-8c5e-f2c5e080b9ed', 'ab97e310-ec61-4e2a-bd28-cef5979e8cc4', 1, '/storage/app/public/uploads/posts/701/c61b6ec4-0228-4e50-8c5e-f2c5e080b9ed/1763564037_17.11.2025_22.26.44_REC.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(138, 702, 82, 'a56e0907-017f-4beb-a702-ddbc18fec35f', '817455b7-a109-454a-a792-b0f46e495bb5', 0, '/storage/app/public/uploads/posts/702/a56e0907-017f-4beb-a702-ddbc18fec35f/1763892131_1000020918.jpg', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(139, 702, 85, '85198b1b-54e8-4989-a145-4a305e02a5c8', 'bab70809-93b5-4e24-abd7-58068416ead9', 1, '/storage/app/public/uploads/posts/702/85198b1b-54e8-4989-a145-4a305e02a5c8/1764105061_devsped.PNG', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(140, 702, 85, '85198b1b-54e8-4989-a145-4a305e02a5c8', 'efa7aaca-ba99-4980-bfb7-990e55aff80c', 1, '/storage/app/public/uploads/posts/702/85198b1b-54e8-4989-a145-4a305e02a5c8/1764105061_logo.png', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(147, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', 'a24a8552-d33c-40fd-b792-4a38c5be6105', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_ESL.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(148, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '96dab581-8d7c-4b47-89b5-b55e1276092b', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_logo1.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(149, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '85281e5e-6c38-4c98-9aa7-7b3e23b5119f', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_logo2.PNG', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(150, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '4b1332e4-11fa-4a4b-90b0-a552479a721e', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_logo.png', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(151, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', 'c807e863-bea0-41dc-8f80-3e90489caf7b', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_devsped.PNG', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(152, 701, 86, '648819fb-f683-4cee-a628-121be1ad09bf', '752817b2-6869-477b-8198-914cc10aaa70', 1, '/storage/app/public/uploads/posts/701/648819fb-f683-4cee-a628-121be1ad09bf/1764108346_ESL.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-25 22:05:46', NULL),
(153, 701, 19, 'd6eb1ba6-58c6-43b3-8c0c-950de1916a33', '79d38165-610f-4d03-8b5b-3542e2ee665c', 1, '/storage/app/public/uploads/posts/701/d6eb1ba6-58c6-43b3-8c0c-950de1916a33/1764427401_ESL.jpg', 'image', 'Pedro yorpo', NULL, '2025-11-29 14:43:21', NULL),
(154, 701, 87, '654e0ffc-c811-43b0-94c6-0149e7a15596', 'f0f1c044-b731-4afb-b1d6-80e9459f01f1', 1, '/storage/app/public/uploads/posts/701/654e0ffc-c811-43b0-94c6-0149e7a15596/1764683908_SampleVideo_1280x720_5mb.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(156, 701, 88, '3cf1b8b5-90cd-4497-8200-bfcc514acd4f', '26e8fd68-87c0-4076-95bc-f402a3d77065', 1, '/storage/app/public/uploads/posts/701/3cf1b8b5-90cd-4497-8200-bfcc514acd4f/1764767247_20251202223145.mp4', 'video', 'Pedro yorpo', NULL, NULL, NULL),
(157, 705, 90, 'a4c10f6e-cbcc-4152-a5e5-03210433f56f', 'dabfdd4a-0bf1-4ba3-94ff-65eae91e0301', 1, '/storage/app/public/uploads/posts/705/a4c10f6e-cbcc-4152-a5e5-03210433f56f/1770110049_Platform1.jpg', 'image', 'David Dela Cruz', NULL, NULL, NULL),
(399, 702, 2, 'b75cedcc-202b-480b-bb0f-f3fc316590ed', '0fd055d0-49a9-4877-a102-bc8aeebbc7dc', 1, 'http://localhost:8000/storage/uploads/posts/702/b75cedcc-202b-480b-bb0f-f3fc316590ed/9a4694d0-a0c9-4bdf-93b1-0c1d408010e2.png', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(400, 702, 2, 'b75cedcc-202b-480b-bb0f-f3fc316590ed', '90c33632-d5ee-4ea9-893f-541498d998fe', 1, 'http://localhost:8000/storage/uploads/posts/702/b75cedcc-202b-480b-bb0f-f3fc316590ed/82bcda95-bc90-4166-9256-290664acef58.webp', 'image', 'Pedro Yorpo', NULL, '2026-11-15 20:18:56', NULL),
(401, 702, 2, 'b75cedcc-202b-480b-bb0f-f3fc316590ed', '3cfdb89f-a908-49db-9f18-76abc5e681b7', 1, 'http://localhost:8000/storage/uploads/posts/702/b75cedcc-202b-480b-bb0f-f3fc316590ed/17f75a56-6c64-418e-996d-de63f44de111.png', 'image', 'Pedro Yorpo', NULL, '2026-11-15 20:18:56', NULL),
(540, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', '947d03c9-f8e8-4060-bf6c-8e81b1a2c8aa', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/44dd544c-7a47-47dc-9394-1877212e7d3a.jpg', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(541, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', '95913f94-f4d2-4c99-9104-e15b1d2286be', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/dd7696a4-12a7-489e-ad3f-0c9cb3f2c608.jpg', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(542, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', '076c4243-2c35-478b-8963-9a388a6076f8', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/786c1103-00f9-46bb-bccb-45622707b5df.jpg', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(543, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', '95a21a2d-50b7-43fc-bbc1-21da5774d93a', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/4bf04521-c576-4fbb-a47f-b2923036f0cf.png', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(544, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', 'd0781641-f7f2-4241-b0f6-c7ddab34b945', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/d6ee6b71-35c6-45a2-912e-6fcbeef67851.png', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(545, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', '060b6620-ceee-46ff-890e-2d06609d851d', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/1191aced-5b25-487f-8cc3-957303fa809d.png', 'image', 'Pedro Yorpo', NULL, NULL, NULL),
(546, 702, 4, '7b8ddcf7-9e47-4171-8da1-ba498473959c', '935bc20a-005c-410d-ba90-95a497b4e9ae', 1, 'http://localhost:8000/storage/uploads/posts/702/7b8ddcf7-9e47-4171-8da1-ba498473959c/e9699d2d-beaa-4d51-b562-c8296fd224d4.png', 'image', 'Pedro Yorpo', NULL, NULL, NULL);

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

--
-- Dumping data for table `candidate_evaluations`
--

INSERT INTO `candidate_evaluations` (`id`, `applied_id`, `transNo`, `overall_score`, `final_score`, `recommendation`, `category_scores`, `strengths`, `weaknesses`, `matched`, `missing`, `answer_evals`, `explanation`, `interview_questions`, `provider`, `model`, `version`, `weights`, `criteria`, `thresholds`, `status`, `evaluated_by`, `reviewed_by`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(21, 76, 'TR-000001', 80, NULL, 'qualified', '{\"education\":{\"score\":85,\"evidence\":\"The job states no specific education requirement. Candidate lists: Bachelor of Science in Computer Science \\u2013 University of Iloilo.\"},\"experience\":{\"score\":70,\"evidence\":\"1 of 1 listed roles relate to this job (e.g. Software Engineering). Years of experience cannot be measured because the CV stores no start dates.\"},\"skills\":{\"score\":70,\"evidence\":\"The job lists no required skills, so only the presence of skills on the CV was considered.\"},\"answers\":{\"score\":100,\"evidence\":\"2 screening answer(s) were evaluated.\"},\"competencies\":{\"score\":null,\"evidence\":\"No job-specific competencies were configured for this job.\"}}', '[\"Has directly relevant work experience as Software Engineering.\",\"Strong answer: Are you willing to relocate?\",\"Strong answer: How many year\'s experience?\"]', '[]', '[\"Relevant work experience\"]', '[]', '[{\"question_id\":261,\"question\":\"Are you willing to relocate?\",\"type\":\"multiple_choice\",\"answer\":\"Yes\",\"score\":100,\"final_score\":null,\"overridden\":false,\"status\":\"complete\",\"explanation\":\"Matches the answer the recruiter marked as correct.\",\"follow_up\":\"\"},{\"question_id\":262,\"question\":\"How many year\'s experience?\",\"type\":\"numeric\",\"answer\":\"3\",\"score\":100,\"final_score\":null,\"overridden\":false,\"status\":\"complete\",\"explanation\":\"Matches the answer the recruiter marked as correct.\",\"follow_up\":\"\"}]', 'Rules-based review of the candidate\'s education, work history, skills and answers against this job. Skills 70, education 85, experience 70, answers 100. Matching uses meaning-aware word groups (synonyms and word forms) rather than exact keywords, but it cannot read between the lines like a person: please review the evidence below.', '[\"Walk us through your role as Software Engineering and what you achieved.\",\"What interests you about this role, and what would you want to learn in the first 90 days?\"]', 'local', 'rules-v1', '1', '{\"education\":80,\"experience\":90,\"skills\":80,\"answers\":70,\"competencies\":85}', '{\"required_skills\":[],\"education\":null,\"competencies\":[],\"passing_score\":50,\"scope\":\"job\"}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 'ai', '703', NULL, NULL, '2026-11-21 09:19:28', '2026-11-21 09:19:28'),
(22, 76, 'TR-000001', 80, NULL, 'qualified', '{\"education\":{\"score\":85,\"evidence\":\"The job states no specific education requirement. Candidate lists: Bachelor of Science in Computer Science \\u2013 University of Iloilo.\"},\"experience\":{\"score\":70,\"evidence\":\"1 of 1 listed roles relate to this job (e.g. Software Engineering). Years of experience cannot be measured because the CV stores no start dates.\"},\"skills\":{\"score\":70,\"evidence\":\"The job lists no required skills, so only the presence of skills on the CV was considered.\"},\"answers\":{\"score\":100,\"evidence\":\"2 screening answer(s) were evaluated.\"},\"competencies\":{\"score\":null,\"evidence\":\"No job-specific competencies were configured for this job.\"}}', '[\"Has directly relevant work experience as Software Engineering.\",\"Strong answer: Are you willing to relocate?\",\"Strong answer: How many year\'s experience?\"]', '[]', '[\"Relevant work experience\"]', '[]', '[{\"question_id\":261,\"question\":\"Are you willing to relocate?\",\"type\":\"multiple_choice\",\"answer\":\"Yes\",\"score\":100,\"final_score\":null,\"overridden\":false,\"status\":\"complete\",\"explanation\":\"Matches the answer the recruiter marked as correct.\",\"follow_up\":\"\"},{\"question_id\":262,\"question\":\"How many year\'s experience?\",\"type\":\"numeric\",\"answer\":\"3\",\"score\":100,\"final_score\":null,\"overridden\":false,\"status\":\"complete\",\"explanation\":\"Matches the answer the recruiter marked as correct.\",\"follow_up\":\"\"}]', 'Rules-based review of the candidate\'s education, work history, skills and answers against this job. Skills 70, education 85, experience 70, answers 100. Matching uses meaning-aware word groups (synonyms and word forms) rather than exact keywords, but it cannot read between the lines like a person: please review the evidence below.', '[\"Walk us through your role as Software Engineering and what you achieved.\",\"What interests you about this role, and what would you want to learn in the first 90 days?\"]', 'local', 'rules-v1', '1', '{\"education\":80,\"experience\":90,\"skills\":80,\"answers\":70,\"competencies\":85}', '{\"required_skills\":[],\"education\":null,\"competencies\":[],\"passing_score\":50,\"scope\":\"job\"}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 'ai', '703', NULL, NULL, '2026-11-21 09:25:08', '2026-11-21 09:25:08'),
(23, 76, 'TR-000001', 80, NULL, 'qualified', '{\"education\":{\"score\":85,\"evidence\":\"The job states no specific education requirement. Candidate lists: Bachelor of Science in Computer Science \\u2013 University of Iloilo.\"},\"experience\":{\"score\":70,\"evidence\":\"1 of 1 listed roles relate to this job (e.g. Software Engineering). Years of experience cannot be measured because the CV stores no start dates.\"},\"skills\":{\"score\":70,\"evidence\":\"The job lists no required skills, so only the presence of skills on the CV was considered.\"},\"answers\":{\"score\":100,\"evidence\":\"2 screening answer(s) were evaluated.\"},\"competencies\":{\"score\":null,\"evidence\":\"No job-specific competencies were configured for this job.\"}}', '[\"Has directly relevant work experience as Software Engineering.\",\"Strong answer: Are you willing to relocate?\",\"Strong answer: How many year\'s experience?\"]', '[]', '[\"Relevant work experience\"]', '[]', '[{\"question_id\":261,\"question\":\"Are you willing to relocate?\",\"type\":\"multiple_choice\",\"answer\":\"Yes\",\"score\":100,\"final_score\":null,\"overridden\":false,\"status\":\"complete\",\"explanation\":\"Matches the answer the recruiter marked as correct.\",\"follow_up\":\"\"},{\"question_id\":262,\"question\":\"How many year\'s experience?\",\"type\":\"numeric\",\"answer\":\"3\",\"score\":100,\"final_score\":null,\"overridden\":false,\"status\":\"complete\",\"explanation\":\"Matches the answer the recruiter marked as correct.\",\"follow_up\":\"\"}]', 'Rules-based review of the candidate\'s education, work history, skills and answers against this job. Skills 70, education 85, experience 70, answers 100. Matching uses meaning-aware word groups (synonyms and word forms) rather than exact keywords, but it cannot read between the lines like a person: please review the evidence below.', '[\"Walk us through your role as Software Engineering and what you achieved.\",\"What interests you about this role, and what would you want to learn in the first 90 days?\"]', 'local', 'rules-v1', '1', '{\"education\":80,\"experience\":90,\"skills\":80,\"answers\":70,\"competencies\":85}', '{\"required_skills\":[],\"education\":null,\"competencies\":[],\"passing_score\":50,\"scope\":\"job\"}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 'ai', '703', NULL, NULL, '2026-11-21 09:27:03', '2026-11-21 09:27:03');

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
) ENGINE=InnoDB AUTO_INCREMENT=668 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commentposts`
--

INSERT INTO `commentposts` (`id`, `comment_uuid`, `post_uuidOrUind`, `status`, `code`, `comment`, `attachment_url`, `date_comment`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'a721aa1f-ea44-4adb-9213-d7081c00839d', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 711, 'amazing posts peds', NULL, '2025-05-05', 'Human$ crazy', NULL, '2025-05-05 13:29:11', '2025-05-05 13:29:11'),
(2, '9554ea19-6ca6-44ea-852a-68efe594e12a', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'thanks rj', NULL, '2025-05-05', 'Pedro Yorpo', NULL, '2025-05-05 13:36:55', '2025-05-05 13:36:55'),
(3, '7950913c-6243-445b-83fc-f0dd5f6e5439', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'nice pic', NULL, '2025-05-05', 'Pedro Yorpo', NULL, '2025-05-05 15:22:18', '2025-05-05 15:22:18'),
(4, '1ac9835d-0a6f-4978-a169-ee439039d034', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'testcomments', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 13:06:55', '2025-05-06 13:06:55'),
(5, '3e624d70-588f-4b8d-9357-5f70f7cdc7ea', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'testcomments22', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 13:07:45', '2025-05-06 13:07:45'),
(6, 'd4bc2f71-f326-47d2-ac4b-38220dca9550', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 13:08:29', '2025-05-06 13:08:29'),
(7, '5df4d984-c53b-4602-9df3-4daf9a976e91', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'sample', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 13:17:14', '2025-05-06 13:17:14'),
(8, '8b01bf9a-b514-4fb9-b224-e3399af668ba', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:17:33', '2025-05-06 14:17:33'),
(9, '4f18ecb6-19fb-45c0-8a32-3b8ba0f8af30', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'amazing', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:24:56', '2025-05-06 14:24:56'),
(10, '80a1fac9-8dea-4afa-9eee-6fb468ad76ee', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'test2', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:25:43', '2025-05-06 14:25:43'),
(11, 'e3d1c34e-d399-41da-a167-ac9b48e97577', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'test22', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:27:30', '2025-05-06 14:27:30'),
(12, 'c11bc312-2eab-47da-919f-96130b939f77', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'something', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:27:49', '2025-05-06 14:27:49'),
(13, 'd0e82769-0aaf-4431-8244-08afb987bb11', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'somethings22', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:28:19', '2025-05-06 14:28:19'),
(14, '8863874d-4d0a-49db-b59b-a91a1c2d9adf', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'testing', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 15:23:39', '2025-05-06 15:23:39'),
(15, '45988dc3-2ba9-40a5-b6b0-d47aa5c78022', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'test', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 12:18:20', '2025-05-07 12:18:20'),
(16, 'f209bb8c-bac7-4b7e-8dd2-ad36f42533e3', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'tess', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 12:39:43', '2025-05-07 12:39:43'),
(17, 'e1240787-bfa8-4ce8-8e99-31eb8e1560a3', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'test', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 13:16:04', '2025-05-07 13:16:04'),
(18, '7b9e9107-0618-4a98-ac0f-d0c02d0e6b77', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'today', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 13:16:20', '2025-05-07 13:16:20'),
(19, 'e517d99f-79e9-4fcb-8866-612ab7f7aadd', '1e7cfa1e-672f-42ab-8283-446ffb902ebc', 0, 702, 'nice picture elizabeth mura kag 2BOL!!', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 13:53:02', '2025-05-07 13:53:02'),
(20, '9d2a0f7c-2a84-40fd-910c-ed054c03c78f', 'c225d726-722c-4949-a7a8-8b2f23a360d2', 0, 702, 'amazing boss', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 14:13:12', '2025-05-07 14:13:12'),
(21, '66e32530-9d62-43f2-b607-0279465e6c65', '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758', 0, 701, 'testcomment', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:17:51', '2025-05-07 14:17:51'),
(22, '599723f5-8a3f-4ced-8a1b-a36960ab1a92', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 711, 'nc update peds', NULL, '2025-05-09', 'Human$ crazy', 'Human$ crazy', '2025-05-09 13:05:11', '2025-05-23 13:15:32'),
(23, '3bf134ae-d974-4ea7-94fa-eee5464fe141', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 1, 711, 'This is a test comment update.', NULL, '2025-05-09', 'Human$ crazy', NULL, '2025-05-09 14:15:04', '2025-05-09 14:15:04'),
(24, 'c3b773c6-d3fb-4f9c-bc5b-73989e739869', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 711, 'wazzup', NULL, '2025-05-09', 'Human$ crazy', NULL, '2025-05-09 14:27:51', '2025-05-09 14:27:51'),
(25, 'd479c331-68f7-4a41-a8a0-3f1c24c1cd42', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'sample individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:27:43', '2025-05-10 02:27:43'),
(26, 'a2905f00-6c02-4b2d-a222-9f7ed655a952', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'sample individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:28:28', '2025-05-10 02:28:28'),
(27, 'd73ac8f8-15cc-459e-b3f7-f5186ae876bc', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'sample individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:29:47', '2025-05-10 02:29:47'),
(28, 'abcdd91b-a854-4828-b7b0-69c937e4d795', '4a544d17-2824-40b2-9a2b-f6ad95469814', 1, 702, 'sample individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:29:53', '2025-05-10 02:29:53'),
(29, '07cc60d3-e88d-41dd-9bde-c45f0f9ab2c2', '4a544d17-2824-40b2-9a2b-f6ad95469814', 1, 702, 'sample individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:31:16', '2025-05-10 02:31:16'),
(30, '0f965e37-1e4e-42d8-968a-2ed365f2077f', '35e045fe-7da2-4236-aed7-b2091b73291a', 1, 702, 'sample individual comment22', NULL, '2025-05-10', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-10 02:31:35', '2025-06-01 05:19:38'),
(31, 'f87111ee-b228-4967-b60f-41b08ad0a4ff', '35e045fe-7da2-4236-aed7-b2091b73291a', 0, 702, 'sample 2 individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:34:19', '2025-05-10 02:34:19'),
(32, '4aba0f3c-1f6f-4dbf-b63c-2295349ed128', '35e045fe-7da2-4236-aed7-b2091b73291a', 0, 702, 'sample 2 individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:36:44', '2025-05-10 02:36:44'),
(33, 'b87fd3f4-d014-4ef6-b4e9-de705a86a4c9', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:40:25', '2025-05-10 02:40:25'),
(34, 'b4211a2b-5ca8-49c2-b988-9a90a779d19d', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:41:37', '2025-05-10 02:41:37'),
(35, '8906e090-1059-4191-8628-217723a94cb7', '506d922c-bd3f-4d93-bfa0-1ebd4d702749', 0, 702, 'individual comment', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:57:02', '2025-05-10 02:57:02'),
(36, '6631df56-8d99-4b9d-95a6-baf648f217ec', '506d922c-bd3f-4d93-bfa0-1ebd4d702749', 0, 702, 'comment2', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 04:36:22', '2025-05-10 04:36:22'),
(37, '901865d6-a375-4b80-865f-86e42d20d6f2', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'comment2', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 04:42:05', '2025-05-10 04:42:05'),
(38, '7a41f61e-7112-4d95-9ff3-3ef57fdd830e', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'comment222', NULL, '2025-05-10', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-10 05:36:57', '2025-06-01 05:19:26'),
(39, '044f024a-369c-44fc-bd4f-b812cc3fe3b5', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'comment3', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 05:39:36', '2025-05-10 05:39:36'),
(40, 'da57dcce-935d-4041-9c62-8436b2cf978a', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'comment 4csdvsdv', NULL, '2025-05-10', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-10 05:49:23', '2025-06-01 05:40:37'),
(41, '818f148b-c518-42d9-93ff-6f74ab5cec50', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'comment 5', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 05:57:11', '2025-05-10 05:57:11'),
(42, '1ccca110-0c89-43bc-ab70-b50362a57b7f', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'comment 6', NULL, '2025-05-10', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-10 05:59:31', '2025-06-01 05:18:21'),
(43, '40341b62-6027-45f8-beba-383232c4ad11', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'comment 7', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:00:39', '2025-05-10 06:00:39'),
(44, '719c3eb7-c705-4e97-ad80-be6af3da0423', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:07:46', '2025-05-10 06:07:46'),
(45, '94beae76-9315-40be-917a-4a52780bc715', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'test22', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:08:30', '2025-05-10 06:08:30'),
(46, '6733d966-4150-4b7b-a3bf-179e31ba9881', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'send', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:10:17', '2025-05-10 06:10:17'),
(47, '7d5c6ac5-89b3-4968-9057-d9ff614f9f8d', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'now', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:11:24', '2025-05-10 06:11:24'),
(48, '6f04a1a7-49cd-4905-a517-7033eb9bdbb0', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'test123', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:11:59', '2025-05-10 06:11:59'),
(49, '064720ce-74ea-41f7-997c-2149c8828947', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'we', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:12:48', '2025-05-10 06:12:48'),
(50, 'fb2de280-5f6f-4fca-82d7-e3eaa079c318', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'see', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:13:00', '2025-05-10 06:13:00'),
(51, 'f8204f76-1a66-48ad-899e-b6687f2ea275', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'sdcsdc', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:17:23', '2025-05-10 06:17:23'),
(52, '1feb6f3f-8670-45e4-9924-26e4ac528c5d', 'a571faae-bf64-4dd6-a026-abb78269c16a', 0, 702, 'add', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:18:54', '2025-05-10 06:18:54'),
(53, '9a2c3c2c-b6c5-4ee7-a023-22af04ed1fe1', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'test1233', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:20:35', '2025-05-10 06:20:35'),
(54, '318a4272-f8cd-44dd-b843-a32da8746a3e', 'a571faae-bf64-4dd6-a026-abb78269c16a', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:25:15', '2025-05-10 06:25:15'),
(55, 'a1526674-0486-4920-93f4-03f7d0fe1a5a', 'a571faae-bf64-4dd6-a026-abb78269c16a', 0, 702, 'test22', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:25:26', '2025-05-10 06:25:26'),
(56, '066cb0c3-ef6a-4cab-833a-77ad43aac173', 'a571faae-bf64-4dd6-a026-abb78269c16a', 0, 702, 'test233', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:25:35', '2025-05-10 06:25:35'),
(57, 'eb6c2880-8bd7-46d8-b41b-44173176a949', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'test123', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:28:01', '2025-05-10 06:28:01'),
(58, '91b49982-c99b-448d-9b72-4d5d6cfa2ef0', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'test1234', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:28:43', '2025-05-10 06:28:43'),
(59, '65096644-1546-4081-abab-9bc58727759b', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:32:35', '2025-05-10 06:32:35'),
(60, '2576a9a7-26b6-4ee2-be8c-801b1cd51e4f', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'test22', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:33:16', '2025-05-10 06:33:16'),
(61, '1ae94fc7-981a-462c-be6f-c14176c356a0', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'dvdv', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:33:31', '2025-05-10 06:33:31'),
(62, '63e07919-2910-4d5c-b5a4-7220677b9eeb', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'sdcsdc', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:34:15', '2025-05-10 06:34:15'),
(63, '59da341d-3343-4036-a8b9-b408a88d8968', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'new update', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:40:00', '2025-06-01 05:04:17'),
(64, '19846833-00a1-4a1f-9693-d55a53da5ce7', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 701, 'nice images222', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:42:08', '2025-06-01 05:11:28'),
(65, '9da38c90-adaf-44af-a260-e253c6a4acbb', '5eeec985-96c9-48e6-886b-6443b0dc5185', 0, 701, 'nice images', NULL, '2025-05-10', 'Pedro yorpo', NULL, '2025-05-10 06:43:02', '2025-05-10 06:43:02'),
(66, '3e359464-695f-4a43-9792-ca47c2c02f21', '399a6fe7-d1ac-42bf-825c-648294be889f', 0, 701, 'nice pic', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:44:04', '2025-06-01 05:12:15'),
(67, '6d95b600-6928-475f-9cc1-b53af8e31c18', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 701, 'nice 1222', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:44:55', '2025-06-01 05:11:33'),
(68, '59b6cd45-d2e3-4d46-b7f2-f9c518fa4a14', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:56:26', '2025-05-10 06:56:26'),
(69, '6f1cc06f-fdd5-4da4-b30f-81f3d9a726bb', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 702, 'nice images i Love it', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:58:40', '2025-05-10 06:58:40'),
(70, '584849c3-cf45-46a9-bf51-7fdd5f895121', '5eeec985-96c9-48e6-886b-6443b0dc5185', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 07:09:35', '2025-05-10 07:09:35'),
(71, '03f34ddd-c787-4d36-a6fc-177a8ed0149e', 'c225d726-722c-4949-a7a8-8b2f23a360d2', 0, 702, 'test comment', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 11:57:19', '2025-05-13 11:57:19'),
(72, '82feac02-38db-4c8d-884c-34ae854c63eb', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, 'this is test comment', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 12:03:03', '2025-05-13 12:03:03'),
(73, 'fcc0616b-6482-4855-8037-023f53868cca', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, 'this test comment', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 12:07:36', '2025-05-13 12:07:36'),
(74, '53842fc6-dfbd-4563-8111-0c57df545808', '91a6e786-faf0-4c5a-ad6b-370cfd35ce6e', 0, 702, 'up', NULL, '2025-05-13', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-13 12:08:05', '2025-06-02 13:16:01'),
(75, 'c65747af-f2dd-439b-bc6d-fec387b5f26c', '1e7cfa1e-672f-42ab-8283-446ffb902ebc', 0, 711, 'nc pic  update', NULL, '2025-05-15', 'Human$ crazy', 'Human$ crazy', '2025-05-15 00:30:40', '2025-05-19 09:39:16'),
(76, '2053c1a9-85b4-4db9-93ec-5280ce856134', '506d922c-bd3f-4d93-bfa0-1ebd4d702749', 0, 702, 'Twst', NULL, '2025-05-15', 'Pedro Yorpo', NULL, '2025-05-15 10:06:34', '2025-05-15 10:06:34'),
(77, '7f1008ff-2a43-44ce-a263-d959a5c2267e', '5eeec985-96c9-48e6-886b-6443b0dc5185', 0, 702, 'testing', NULL, '2025-05-15', 'Pedro Yorpo', NULL, '2025-05-15 14:03:56', '2025-05-15 14:03:56'),
(78, '64c9341b-fdf3-47c6-9d2f-4e6672baad63', '2343d813-724f-4953-8c39-1016f07112a1', 0, 712, 'test', NULL, '2025-05-16', 'Nexsuz', NULL, '2025-05-16 15:01:33', '2025-05-16 15:01:33'),
(79, '3042df21-9273-4a45-8c4e-322007604452', 'be5fdcdf-e86c-4285-a609-d5263815fad2', 0, 712, 'test', NULL, '2025-05-16', 'Nexsuz', NULL, '2025-05-16 15:02:00', '2025-05-16 15:02:00'),
(80, 'c403d0ca-577f-4330-834d-f5a5ccff31e6', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'nice', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 01:25:38', '2025-05-18 01:25:38'),
(81, '0ad57952-3e9a-4619-9c8b-cbfcfbf81fab', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'acacasc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 01:32:44', '2025-05-18 01:32:44'),
(82, 'f8a5194b-f3e1-454d-8acc-d08b42435743', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'ascasc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 01:32:47', '2025-05-18 01:32:47'),
(83, 'f15985d5-8fbc-4dc6-b00a-bf5afed20946', '4a752eaa-16b3-4716-9db3-878d70a826d2', 0, 702, 'dvdvsdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 01:33:59', '2025-05-18 01:33:59'),
(84, '0eb1db8e-6daf-4f51-b048-65cc26f90865', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'eli', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 01:56:08', '2025-05-18 01:56:08'),
(85, 'baa01b57-cff5-4a29-9677-7f7660b7b754', '8904f1f5-77d7-41aa-9de6-98f21480e95e', 0, 702, 'test', NULL, '2025-05-18', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-18 04:17:13', '2025-07-05 01:43:00'),
(86, '95a17107-837c-4b3b-b39b-b027bd1bc4c7', 'ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'testting', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:17:37', '2025-05-18 04:17:37'),
(87, 'a7fe87aa-326c-483b-b935-275f1d21babe', '9f3439ef-a734-4a82-bbb4-cbb78e5c289f', 0, 702, 'sdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:20:13', '2025-05-18 04:20:13'),
(88, 'bd81af01-8496-4e27-88da-71aabd3ac973', '9f3439ef-a734-4a82-bbb4-cbb78e5c289f', 0, 702, 'sdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:20:16', '2025-05-18 04:20:16'),
(89, 'e76e7a65-29db-4a80-a4fe-b410c8a068b4', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, 'acsdc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:21:30', '2025-05-18 04:21:30'),
(90, '2c41d718-0596-47e2-bf16-2e9ac0c96e5b', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, 'sdvsdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:24:59', '2025-05-18 04:24:59'),
(91, '966f8058-054b-4966-bdd5-14edd7a4cf1d', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, 'sdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:25:07', '2025-05-18 04:25:07'),
(92, 'eae647d7-088b-4fdb-88a7-034e2ead912c', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'nice123', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:26:55', '2025-05-18 04:26:55'),
(93, 'fcab9430-9a6c-4e2d-8901-bf506d5f81b3', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, '123', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:27:36', '2025-05-18 04:27:36'),
(94, '79c3a32d-bb28-4368-a212-09295c9cecd6', 'fe15601d-f201-405f-814a-122d5bb2ead5', 0, 702, '123444', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:30:51', '2025-05-18 04:30:51'),
(95, '637cfe16-3c87-4f6b-a9ef-d01e99c875b1', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'dcsdc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:31:41', '2025-05-18 04:31:41'),
(96, 'cd6adf14-103c-4942-a768-d514054ff0a6', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'vv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:32:39', '2025-05-18 04:32:39'),
(97, 'bc230446-fdcd-414d-ad30-14be2c197a26', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, '123', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:34:13', '2025-05-18 04:34:13'),
(98, '3038334b-2869-4ba3-b87f-b88e44701d68', '4a544d17-2824-40b2-9a2b-f6ad95469814', 0, 702, 'sdcsd', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:34:27', '2025-05-18 04:34:27'),
(99, '4eb8ca80-0768-48e8-959c-bfbdf28b9c90', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, '12333', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:35:41', '2025-05-18 04:35:41'),
(100, 'c155bca2-9fcd-42e2-b4f4-4c70e905933e', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, '12w12w12', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:35:45', '2025-05-18 04:35:45'),
(101, '6bb5ebbb-7d7e-4366-8377-d4bc9780d35d', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'svsdvsd', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:36:37', '2025-05-18 04:36:37'),
(102, '867daa6b-da93-40af-8d42-923116ee9139', '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 0, 702, 'vsdvsdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:44:34', '2025-05-18 04:44:34'),
(103, 'e4191549-1f16-4351-bd7f-608edfca905d', 'ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'ascasc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:49:05', '2025-05-18 04:49:05'),
(104, 'ef2156c4-0842-4d9f-8827-4078d8d53701', 'ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'sdcsdc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:49:23', '2025-05-18 04:49:23'),
(105, 'f0c8cf2c-440e-4d84-8635-2b0d1fa1682d', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12123', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:51:44', '2025-05-18 04:51:44'),
(106, '83e8fefc-2edf-406f-8036-0b253d29b405', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'aca', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:53:08', '2025-05-18 04:53:08'),
(107, 'cc02efce-6897-49bd-8619-0a80275b844b', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'sdsdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:53:23', '2025-05-18 04:53:23'),
(108, 'ad635c7e-2eed-43e1-91ed-43c310768342', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'ascasc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:54:12', '2025-05-18 04:54:12'),
(109, 'eeec551b-48d0-46a9-a47a-a8f437bcee13', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'acasc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:55:11', '2025-05-18 04:55:11'),
(110, '47ff2928-f7af-4a14-a20f-7553f06d867e', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'asc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:55:28', '2025-05-18 04:55:28'),
(111, '200eeb38-1d7f-4d10-95ad-cdf6f0b700bd', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'acsas', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:57:41', '2025-05-18 04:57:41'),
(112, '4c99ee2b-a1c7-4be4-b7bc-1948bb017aa3', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'dsdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:58:21', '2025-05-18 04:58:21'),
(113, '20e12957-c65e-4206-b1b3-377ccfbc842e', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '123', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:00:30', '2025-05-18 05:00:30'),
(114, '6eb5b72e-f7d1-47dd-b01d-39d78ccbedc1', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:00:34', '2025-05-18 05:00:34'),
(115, 'd3862a2e-83ce-43b9-8715-71b4a72aa90a', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12222', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:03:03', '2025-05-18 05:03:03'),
(116, '9c51f535-35b8-4f30-a3cf-5e7e9a2af9f7', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'nice123', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:03:35', '2025-05-18 05:03:35'),
(117, '69128c89-8f02-423b-aa9d-3a055eb89949', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '1233455', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:07:18', '2025-05-18 05:07:18'),
(118, '6f8a0b54-ae25-4051-9973-f666dfec7d17', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '122', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:07:48', '2025-05-18 05:07:48'),
(119, '167aeaab-6548-4351-871b-dcc3f1e27f2a', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '45', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:09:47', '2025-05-18 05:09:47'),
(120, 'f07e9375-c5b6-4e56-9fa8-55b2f56ab51f', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'nice', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:10:25', '2025-05-18 05:10:25'),
(121, '0e8bf504-9eda-4597-9dfc-ea944e859dbb', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'nice', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:29:17', '2025-05-18 05:29:17'),
(122, '30dcf521-9edc-4e17-801c-3b7eb8bff77a', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12333', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:32:34', '2025-05-18 05:32:34'),
(123, '36684000-60aa-44e7-ac36-a0020662b598', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'nice', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:33:04', '2025-05-18 05:33:04'),
(124, '757d02e6-7e7f-4da2-8146-e4914124d563', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12222', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:33:59', '2025-05-18 05:33:59'),
(125, 'd03ab84e-669e-4739-b927-a8ab7e8e7882', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12333', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:35:03', '2025-05-18 05:35:03'),
(126, 'fa24dd24-2227-4971-b34a-309585e401f9', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'dsvsdv', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:35:25', '2025-05-18 05:35:25'),
(127, '3e14042c-4c80-401d-ae94-9a4afedd4212', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '123333', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:36:42', '2025-05-18 05:36:42'),
(128, 'c9bb026e-b3f6-406f-8450-8afeeeb56739', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, 'test', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:37:52', '2025-05-18 05:37:52'),
(129, 'cbe0457f-c900-467c-a2c4-704a3bacd07c', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 0, 702, '12333', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:38:57', '2025-05-18 05:38:57'),
(130, '1467a063-a4eb-422f-849f-f47312c7bee0', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'test', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:52:00', '2025-05-20 15:52:00'),
(131, '81d8123b-d9c3-4b57-bda1-141357cf9a12', '928785b3-af8d-4124-a889-a40fd4da9c85', 0, 702, 'test', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:52:50', '2025-05-20 15:52:50'),
(132, 'c0f4acd0-0e5d-40bc-ba48-49c6f59d7748', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'sdfsdsdv', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:53:02', '2025-05-20 15:53:02'),
(133, '338ec541-04e9-4b85-9eb0-df464497bb20', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'sdvsdv', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:53:06', '2025-05-20 15:53:06'),
(134, 'ab651f0d-409a-42e5-8d0c-b82610569546', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'sdbdsb', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:55:28', '2025-05-20 15:55:28'),
(135, '5d0a8e57-6e55-46ec-8528-887a46652e0d', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'sdbsdb', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:55:30', '2025-05-20 15:55:30'),
(136, '6b1720c1-d528-4641-a3c1-2f020f62edb1', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'fdfndf', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:55:42', '2025-05-20 15:55:42'),
(137, '376c2f50-a069-4c8c-bf80-ee61595d7e73', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, '123', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:56:50', '2025-05-20 15:56:50'),
(138, 'c1d28834-df3c-40cf-b081-39eb7ad6b0a6', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'gnfgnfgn', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:57:14', '2025-05-20 15:57:14'),
(139, 'b047b068-ddad-4dcc-96bf-3cc6b1bcbbf1', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'nfgnfgn', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:57:36', '2025-05-20 15:57:36'),
(140, '2d17692c-ee91-4005-b693-15add494cd35', '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'test', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:58:39', '2025-05-20 15:58:39'),
(141, '99a3936f-22ec-4427-bc76-3711936176de', 'undefined', 0, 702, 'sfaf', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 16:03:06', '2025-05-20 16:03:06'),
(142, '23b9ab3b-fa4b-432c-9226-7aab6330f9f4', 'undefined', 0, 702, 'sfafa', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 16:03:26', '2025-05-20 16:03:26'),
(143, 'b0e21d5f-0a82-4612-9297-dbb0c1141e2f', 'undefined', 0, 702, 'vzvzszx', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 16:04:00', '2025-05-20 16:04:00'),
(144, '0bb691ef-5207-413a-9dde-6f28d75b021f', '36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'dbsdbsdb', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 16:06:09', '2025-05-20 16:06:09'),
(145, '69d4028d-5f5c-4f3a-982c-151a16f27f07', '36aade6f-e355-46ca-8d34-bf4970513c06', 0, 702, 'sfasf', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 16:06:30', '2025-05-20 16:06:30'),
(146, 'd06fdfaa-9746-4a92-885e-2e1910ff57fa', 'aab5830c-ca18-4058-9a18-9e736e874518', 0, 702, 'now', NULL, '2025-05-25', 'Pedro Yorpo', NULL, '2025-05-25 04:29:20', '2025-05-25 04:29:20'),
(147, '88456bee-228b-43ac-8b08-6c675337818c', '91a6e786-faf0-4c5a-ad6b-370cfd35ce6e', 0, 702, 'wewe', NULL, '2025-05-25', 'Pedro Yorpo', NULL, '2025-05-25 05:37:53', '2025-05-25 05:37:53'),
(148, '8b4c00be-8da3-4dc2-8d2b-4aa6651f2165', 'd6eb1ba6-58c6-43b3-8c0c-950de1916a33', 0, 701, 'savav', NULL, '2025-05-28', 'Pedro yorpo', NULL, '2025-05-28 15:01:20', '2025-05-28 15:01:20'),
(149, '6fd802cf-a17b-4ac4-b3cd-bfdd72f312b9', '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758', 0, 701, 'SDCSDC', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:07:45', '2025-05-31 13:07:45'),
(150, '6ed81b14-a31d-49e6-b5be-cb36893c5cc6', 'e4705480-0469-4423-b261-827f672bd958', 0, 701, 'test', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:37:16', '2025-05-31 13:37:16'),
(151, '096d680f-579c-471a-b0a1-fae6a1df672b', 'e4705480-0469-4423-b261-827f672bd958', 0, 701, 'dfdfn', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:42:40', '2025-05-31 13:42:40'),
(152, '1bc20a75-5f07-4a50-925e-241fb7cbb5e6', '01e889b5-7d3e-4fc4-a62d-8ce0e61556dc', 0, 702, 'test', NULL, '2025-05-31', 'Pedro Yorpo', NULL, '2025-05-31 13:48:43', '2025-05-31 13:48:43'),
(153, '6ce468d6-7d6c-4dcc-aba1-aa2e219c9d5d', '01e889b5-7d3e-4fc4-a62d-8ce0e61556dc', 0, 702, 'sdbsdb', NULL, '2025-05-31', 'Pedro Yorpo', NULL, '2025-05-31 13:50:25', '2025-05-31 13:50:25'),
(154, '34ff0f2a-e7c9-4c5d-8c9c-37b449d4167a', '01e889b5-7d3e-4fc4-a62d-8ce0e61556dc', 0, 702, 'dvsdv', NULL, '2025-05-31', 'Pedro Yorpo', NULL, '2025-05-31 13:54:04', '2025-05-31 13:54:04'),
(155, 'ecc770c7-fb4d-4229-aae4-475166d2bb79', '928785b3-af8d-4124-a889-a40fd4da9c85', 0, 702, 'ssvd', NULL, '2025-05-31', 'Pedro Yorpo', NULL, '2025-05-31 14:03:37', '2025-05-31 14:03:37'),
(156, '3666872e-b792-47be-9d2c-a719865029d9', 'a60aacd5-d9fc-48f8-b37d-61bef03df903', 0, 702, 'FSBDF', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 00:52:14', '2025-06-01 00:52:14'),
(157, 'c3336fa3-8f88-40e5-91e4-16a91782305b', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'test2', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:28:45', '2025-06-01 05:03:51'),
(158, 'd29463ab-ecef-49b0-9c98-c0e8cd0983bc', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'dfdf', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:33:29', '2025-06-01 02:33:29'),
(159, '7c4fb0b3-8486-4d02-ae4b-d2c54ca9f577', '95af83cd-04a1-4121-9c6c-4406fd01fb63', 0, 701, 'dfdf', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:33:29', '2025-06-01 02:33:29'),
(160, '09737212-cb51-4a3a-b885-f2c38550f170', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'sdsdv', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 03:26:27', '2025-06-01 03:26:27'),
(161, 'd42ca941-d464-4cba-ab54-9dcd9f1788eb', '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 0, 702, 'test', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 03:26:35', '2025-06-01 03:26:35'),
(162, '2bd0a7e3-e88a-4838-8d77-2c911f132046', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 702, 'test', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 03:29:00', '2025-06-01 03:29:00'),
(163, '1548ff74-0de7-40b1-afbb-681e8de17fc1', '91a6e786-faf0-4c5a-ad6b-370cfd35ce6e', 0, 702, 'fgnfgn', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 05:26:55', '2025-06-01 05:26:55'),
(164, '5a4308bb-ef05-440c-babb-1b08666620b7', '085a4269-1732-4c61-951e-179a0ee6c77f', 0, 702, 'update', NULL, '2025-06-06', 'Pedro Yorpo', 'Pedro Yorpo', '2025-06-06 05:12:01', '2025-06-06 05:12:07'),
(165, '0b74316c-6200-4171-b202-db872218c838', '4859c8d7-55d4-4d93-b74b-93b091198585', 0, 701, 'test22', NULL, '2025-07-01', 'Pedro yorpo', 'Pedro yorpo', '2025-07-01 14:54:40', '2025-07-01 14:54:59'),
(166, '29ef583d-b675-4e12-805a-e6cd9c7d8946', '5df8c371-cc91-48c4-bcc6-9ed5637296ad', 0, 701, 'sdc', NULL, '2025-07-12', 'Pedro yorpo', 'Pedro yorpo', '2025-07-12 06:23:40', '2025-07-12 06:23:47'),
(167, '92fbcd75-0c5b-4417-a20d-3ed141ce5880', 'e4705480-0469-4423-b261-827f672bd958', 0, 702, 'test', NULL, '2025-07-13', 'Pedro Yorpo', NULL, '2025-07-13 13:50:15', '2025-07-13 13:50:15'),
(168, 'd4facd98-6d51-4d36-8227-e1a01ea4139f', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 701, 'amazing pic', NULL, '2025-07-13', 'Pedro yorpo', NULL, '2025-07-13 14:14:52', '2025-07-13 14:14:52'),
(169, 'c6b7790f-c56e-4131-9329-e8c5be099bea', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 703, 'test', NULL, '2025-08-10', 'Elizabeth Punay', NULL, '2025-08-10 08:21:11', '2025-08-10 08:21:11'),
(170, 'aa5790fa-4bc3-4d1a-b917-62024257cfc2', 'e86ed95f-70f7-48b2-b569-3fc6502fce3a', 0, 702, 'sdvsdvsdv', NULL, '2025-08-17', 'Pedro Yorpo', 'Pedro Yorpo', '2025-08-17 07:00:22', '2025-08-17 07:00:29'),
(171, '0ec9844f-5280-4258-b87c-12f2cb1a949e', '38409faf-bdb9-41ea-b96b-01bba8c6ec8a', 0, 702, 'asccsa', NULL, '2025-08-18', 'Pedro Yorpo', NULL, '2025-08-18 14:02:21', '2025-08-18 14:02:21'),
(172, 'bfc74bbc-7a8a-4d16-9367-846d4737d89f', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 702, 'sdv', NULL, '2025-08-19', 'Pedro Yorpo', NULL, '2025-08-19 14:39:47', '2025-08-19 14:39:47'),
(173, '03cfd887-67b5-4aff-a586-6479c9cb366e', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 702, 'peds', NULL, '2025-08-19', 'Pedro Yorpo', 'Pedro Yorpo', '2025-08-19 14:39:57', '2025-09-24 13:17:46'),
(174, '997b6c4b-3947-4c77-ab14-ab441cf7ab01', 'ce2d96b9-62a8-4558-933d-f2429372ce5b', 0, 702, 'test', NULL, '2025-08-19', 'Pedro Yorpo', NULL, '2025-08-19 14:51:02', '2025-08-19 14:51:02'),
(175, 'b01f59d6-1b19-4969-a02e-784097807e7c', 'a76a51ff-f459-408b-8658-0ae4f0ffd15c', 0, 703, 'HTRH', NULL, '2025-08-20', 'Elizabeth Punay', NULL, '2025-08-20 13:02:30', '2025-08-20 13:02:30'),
(176, 'eef1080d-0511-439a-997f-e8eb3d9f4768', 'e4e2749d-34c0-45d5-9919-b417591fac7f', 0, 702, 'gwapa kaayu', NULL, '2025-08-21', 'Pedro Yorpo', NULL, '2025-08-21 12:32:33', '2025-08-21 12:32:33'),
(177, '4bcc24c6-ae49-45f9-b208-7f89db80f433', '808febaf-b8ee-41f8-8e38-bdc85747bf2c', 0, 702, 'ddsdb', NULL, '2025-08-25', 'Pedro Yorpo', NULL, '2025-08-25 04:24:18', '2025-08-25 04:24:18'),
(178, '2b8ebe8f-c992-41cd-944a-9705650a7639', '928785b3-af8d-4124-a889-a40fd4da9c85', 0, 701, 'SBDBSSDB', NULL, '2025-08-26', 'Pedro yorpo', 'Pedro yorpo', '2025-08-26 16:07:04', '2025-08-26 16:07:14'),
(179, 'acaa5718-5410-4b09-aa8e-4f57cd7d908a', '129c8393-8d44-4f06-89fd-836ebe09bb72', 0, 701, 'ascacs', NULL, '2025-09-03', 'Pedro yorpo', NULL, '2025-09-03 14:21:48', '2025-09-03 14:21:48'),
(180, 'a96b3664-2e96-4d70-9432-b1f5852bd480', '2343d813-724f-4953-8c39-1016f07112a1', 0, 703, 'sasas', NULL, '2025-09-12', 'Elizabeth Punay', NULL, '2025-09-12 13:31:07', '2025-09-12 13:31:07'),
(181, '0add5ee2-1143-4995-a862-15a58e9c109d', '2343d813-724f-4953-8c39-1016f07112a1', 0, 703, 'sssa', NULL, '2025-09-12', 'Elizabeth Punay', NULL, '2025-09-12 13:31:13', '2025-09-12 13:31:13'),
(182, '1e7983d5-ddfa-43be-bd03-25e1dbe0a1f9', '2343d813-724f-4953-8c39-1016f07112a1', 0, 703, 'ass', NULL, '2025-09-12', 'Elizabeth Punay', NULL, '2025-09-12 13:31:16', '2025-09-12 13:31:16'),
(183, '152567bc-7f86-4a7a-8cf8-e22e1d895291', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 705, 'test', NULL, '2025-09-15', 'David Dela Cruz', NULL, '2025-09-15 14:11:38', '2025-09-15 14:11:38'),
(184, 'b2fb746a-c592-43c8-9738-0622ec9afabb', 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 0, 701, 'amazing', NULL, '2025-09-23', 'Pedro yorpo', NULL, '2025-09-23 14:14:25', '2025-09-23 14:14:25'),
(185, '00fe5578-8adc-4adc-ac76-baa65c50fa87', '97c65aee-3a27-4e0c-8067-0689f9e8609b', 0, 702, 'test 1234', NULL, '2025-09-24', 'Pedro Yorpo', 'Pedro Yorpo', '2025-09-24 13:18:00', '2025-09-24 13:18:24'),
(186, 'c7b71e42-7c6c-4dc6-b390-ba9c35606df7', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 702, 'csdcsdc', NULL, '2025-10-05', 'Pedro Yorpo', NULL, '2025-10-05 10:03:54', '2025-10-05 10:03:54'),
(187, '1eca77d4-ccfb-45b1-a007-48aa61e3cac2', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 702, 'sdcsdc', NULL, '2025-10-05', 'Pedro Yorpo', NULL, '2025-10-05 10:03:58', '2025-10-05 10:03:58'),
(188, '14165690-15b5-49e4-9abc-2b7a3664fc4f', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 702, 'sdccc', NULL, '2025-10-05', 'Pedro Yorpo', 'Pedro Yorpo', '2025-10-05 10:04:09', '2025-10-11 14:34:23'),
(189, '6530e676-fede-4af7-bfa4-b8864fc832a0', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 703, '👍', NULL, '2025-10-08', 'Elizabeth Punay', NULL, '2025-10-08 14:19:16', '2025-10-08 14:19:16'),
(190, '70f34c8c-52b6-457c-a2dc-4989b6e8f25e', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 703, '👍', NULL, '2025-10-08', 'Elizabeth Punay', NULL, '2025-10-08 14:19:16', '2025-10-08 14:19:16'),
(191, '016d7d04-2471-413a-b8bf-6368585edebd', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 703, '👍', NULL, '2025-10-08', 'Elizabeth Punay', NULL, '2025-10-08 14:19:16', '2025-10-08 14:19:16'),
(192, '8ff544ee-57a9-42b9-9df4-bc962a93eeab', '62810936-01c5-4e39-aaec-fe563e84d26a', 0, 703, '👍', NULL, '2025-10-08', 'Elizabeth Punay', NULL, '2025-10-08 14:19:18', '2025-10-08 14:19:18'),
(193, '714f3686-f6ac-4026-8ae9-e7c35fa97701', '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 0, 701, 'csdc', NULL, '2025-10-08', 'Pedro yorpo', NULL, '2025-10-08 14:25:41', '2025-10-08 14:25:41'),
(194, 'd2f3b50e-aa88-4555-ba98-c550cd03d957', 'c0ccfb5d-7717-4605-8ca3-346abd8ea2b5', 0, 701, 'test', NULL, '2025-10-09', 'Pedro yorpo', NULL, '2025-10-09 12:52:54', '2025-10-09 12:52:54'),
(195, '11d8f722-16bd-4e42-a608-e9a42c6d6f58', '129c8393-8d44-4f06-89fd-836ebe09bb72', 0, 702, 'sdvsdv', NULL, '2025-10-10', 'Pedro Yorpo', NULL, '2025-10-10 13:28:24', '2025-10-10 13:28:24'),
(196, '577a0f84-73e4-4e22-b56f-40a057480110', '1c9f685a-a1f6-4158-b977-2056846f56a5', 0, 702, 'Sdcsdc', NULL, '2025-10-11', 'Pedro Yorpo', NULL, '2025-10-11 13:20:22', '2025-10-11 13:20:22'),
(197, '0b8739da-486a-449e-b8b6-019ec3f78d11', '1c9f685a-a1f6-4158-b977-2056846f56a5', 0, 702, 'sddv', NULL, '2025-10-11', 'Pedro Yorpo', NULL, '2025-10-11 14:16:39', '2025-10-11 14:16:39'),
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
(215, '0d00e5e2-d7e1-4cf9-aa21-ebfa4e1e61ee', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 702, 'Technology is advancing faster than society can adapt. We need better digital education to prepare people for these changes.', NULL, '2025-10-25', 'Pedro Yorpo', NULL, '2025-10-25 07:42:12', '2025-10-25 07:42:12'),
(216, '2374901f-8d3a-4bd8-a49b-d382ee3f9a35', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'Totally agree. Most jobs in the next decade will require at least some understanding of automation and data.', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 07:45:12', '2025-10-25 07:45:12'),
(217, '61550367-f662-4754-9901-d39a3c222ea2', '943be758-3a71-4d1d-8422-7154641c9ec1', 0, 701, 'vdfvdfv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 09:07:53', '2025-10-25 09:07:53'),
(218, 'baa2f528-2a18-4328-9972-9c0cb6793ade', '943be758-3a71-4d1d-8422-7154641c9ec1', 0, 701, 'dfvdfv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 09:07:56', '2025-10-25 09:07:56'),
(219, '50bc310a-4e28-4eac-9e0d-8cfcde31bd1a', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'sdcsdcsc', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 09:11:53', '2025-10-25 09:11:53'),
(220, 'd55a1953-6e44-48b6-b042-ff3b8a1c942d', '943be758-3a71-4d1d-8422-7154641c9ec1', 0, 701, 'csdcscsdv ssvd ssvsv', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 10:43:56', '2025-10-25 10:43:56'),
(221, '6f3e7e3a-9d3d-4f8a-8dd2-0edd288e5fe4', 'd74b6090-18a8-46f2-a202-e0c73c719bbe', 0, 703, 'tssssk!', NULL, '2025-10-25', 'Elizabeth Punay', NULL, '2025-10-25 11:02:12', '2025-10-25 11:02:12'),
(222, '66676b9e-a0a9-4c12-85a0-12ae84a23b60', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'amazing', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:39:36', '2025-10-25 11:39:36'),
(223, '8b0e9b36-7f0f-447a-b99d-8199ddf5f9d5', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'your so beatiful', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:43:58', '2025-10-25 11:43:58'),
(224, 'fcbfddb1-315a-41ca-b33c-14391eb734be', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'your so beautiful', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:44:06', '2025-10-25 11:44:06'),
(225, '0ea63925-ccae-463d-888b-b5581bef0458', 'c94a2b5b-2f5a-4a8a-a2db-4230b15c5744', 0, 701, 'your so beatiful', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:44:06', '2025-10-25 11:44:06'),
(226, '6e40e1f1-e230-493e-8f50-2dadf08bf508', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'Test', NULL, '2025-10-28', 'Pedro yorpo', NULL, '2025-10-28 04:34:36', '2025-10-28 04:34:36'),
(227, 'a030392c-cd31-4b07-9f96-b6febe5c3159', '5c418d45-7567-44d2-9730-e5b34618a9d8', 0, 702, 'test', NULL, '2025-11-08', 'Pedro Yorpo', NULL, '2025-11-08 13:56:22', '2025-11-08 13:56:22'),
(228, 'f494a66b-55a5-4ded-8984-2d5b88054cc8', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 701, 'sdvsdvsdv', NULL, '2025-11-09', 'Pedro yorpo', NULL, '2025-11-09 04:20:31', '2025-11-09 04:20:31'),
(229, '93aeeaa7-1bc0-424c-b256-659cf353e67f', '5f05e0ef-3b59-4d47-a302-4b067839cfab', 0, 718, 'test', NULL, '2025-11-09', 'Nexsuz User -', NULL, '2025-11-09 04:47:59', '2025-11-09 04:47:59');
INSERT INTO `commentposts` (`id`, `comment_uuid`, `post_uuidOrUind`, `status`, `code`, `comment`, `attachment_url`, `date_comment`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
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
(240, 'e7ac05d4-3abf-4559-a261-0c832bb46001', 'ab97e310-ec61-4e2a-bd28-cef5979e8cc4', 0, 702, 'nice', NULL, '2025-12-06', 'Pedro Yorpo', NULL, '2025-12-06 00:58:29', '2025-12-06 00:58:29'),
(241, '62edf1a1-5697-4f01-93b6-efb28d37a489', 'f0f1c044-b731-4afb-b1d6-80e9459f01f1', 0, 703, 'sdvsdvsdv', NULL, '2025-12-18', 'Elizabeth Punay', NULL, '2025-12-18 15:19:26', '2025-12-18 15:19:26'),
(242, 'b6b82bca-c8aa-424a-9fbe-451c316a8cf2', 'ef9d291f-18e3-45d7-b8c4-4fd66fb25bc1', 0, 703, 'bdfbdf', NULL, '2025-12-20', 'Elizabeth Punay', NULL, '2025-12-20 23:08:48', '2025-12-20 23:08:48'),
(243, 'd43cdce7-b786-41c2-89a8-5a22f566b55b', 'a24a8552-d33c-40fd-b792-4a38c5be6105', 0, 702, 'sdvsdv', NULL, '2025-12-29', 'Pedro Yorpo', NULL, '2025-12-29 05:20:25', '2025-12-29 05:20:25'),
(244, '02db8270-e19b-449f-9118-0e851e1d6687', 'dabfdd4a-0bf1-4ba3-94ff-65eae91e0301', 0, 705, 'i submitted my CV to your email please check', NULL, '2026-02-03', 'David Dela Cruz', 'David Dela Cruz', '2026-02-03 09:15:32', '2026-02-03 09:16:16'),
(245, 'a2795e65-108a-44a9-ba87-c493c88b756c', 'ab075319-3c5c-415d-85b1-fedee7bbe38b', 0, 702, 'ghngh', NULL, '2026-04-21', 'Pedro Yorpo', NULL, '2026-04-21 13:21:40', '2026-04-21 13:21:40'),
(246, 'f4ac2e5d-a9e2-4445-91d0-02c7eff82f7d', 'f128af8c-557c-4b0e-9cf2-4c0c5058cbae', 0, 701, 'TYTYK', NULL, '2026-04-22', 'Pedro yorpo', NULL, '2026-04-22 09:42:23', '2026-04-22 09:42:23'),
(247, '0f90e0b0-8271-40c2-9373-914a1cfa391b', '41e62ccf-eca9-4e83-b3d8-ff8cfd2a3f1f', 0, 702, 'ddasd', NULL, '2026-07-09', 'Pedro Yorpo', NULL, '2026-07-09 06:15:02', '2026-07-09 06:15:02'),
(248, '4044130a-822e-4d62-a373-6ceb312b6307', '1909d00b-25b6-4b05-9b48-6f213a9ff54d', 0, 702, 'test', NULL, '2026-11-16', 'Pedro Yorpo', NULL, '2026-11-15 19:24:12', '2026-11-15 19:24:12'),
(343, '2779f4b2-4725-4902-8aee-05bb2f205050', '06a96dbf-41a6-4188-97f8-b7769dc5491d', 0, 701, 'wecsdc', NULL, '2026-11-16', 'Pedro yorpo', NULL, '2026-11-15 21:27:45', '2026-11-15 21:27:45'),
(344, 'f0607075-e46c-471c-aa36-ff8a86c451c5', 'b75cedcc-202b-480b-bb0f-f3fc316590ed', 0, 702, 'nice', NULL, '2026-11-18', 'Pedro Yorpo', 'Pedro Yorpo', '2026-11-18 07:07:02', '2026-11-18 07:07:05'),
(665, '6f71ac33-58c4-4ef4-a81b-bd6782fedd45', 'd5e98a6f-6b44-477d-b225-dc3305a9e950', 0, 702, 'Gwapaha uy😘😍', NULL, '2026-11-21', 'Pedro Yorpo', NULL, '2026-11-21 07:11:50', '2026-11-21 07:11:50'),
(666, '710c9c0e-16fc-446f-af53-5a3d28a3f5e6', '06a96dbf-41a6-4188-97f8-b7769dc5491d', 0, 702, 'Ni pm ko nimu sir!', NULL, '2026-11-21', 'Pedro Yorpo', NULL, '2026-11-21 07:12:24', '2026-11-21 07:12:24'),
(667, '6b92a276-efea-485d-af37-e3813338e90f', 'b75cedcc-202b-480b-bb0f-f3fc316590ed', 0, 702, 'Literal nga lami jud siya🤤🤤', NULL, '2026-11-21', 'Pedro Yorpo', NULL, '2026-11-21 07:12:57', '2026-11-21 07:12:57');

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
) ENGINE=MyISAM AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(33, 'comment', '2779f4b2-4725-4902-8aee-05bb2f205050', 702, 'Love', '2026-11-15 21:29:08', '2026-11-15 21:29:08'),
(34, 'reply', '161', 702, 'Love', '2026-11-18 05:06:22', '2026-11-18 05:06:22'),
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
(90, 'comment', '6f71ac33-58c4-4ef4-a81b-bd6782fedd45', 703, 'Love', '2026-11-21 07:13:43', '2026-11-21 07:13:43'),
(91, 'reply', '270', 702, 'Love', '2026-11-21 07:15:02', '2026-11-21 07:15:02'),
(92, 'comment', '710c9c0e-16fc-446f-af53-5a3d28a3f5e6', 703, 'Haha', '2026-11-21 07:16:21', '2026-11-21 07:16:26');

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
) ENGINE=InnoDB AUTO_INCREMENT=272 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `commentreplies`
--

INSERT INTO `commentreplies` (`id`, `comment_uuid`, `status`, `code`, `comment`, `attachment_url`, `date_comment`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'a721aa1f-ea44-4adb-9213-d7081c00839d', 0, 702, 'This is a test comment.', NULL, '2025-05-05', 'Pedro Yorpo', NULL, '2025-05-05 13:42:17', '2025-05-05 13:42:17'),
(2, 'a721aa1f-ea44-4adb-9213-d7081c00839d', 0, 702, 'This is a second comment.', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:32:32', '2025-05-06 14:32:32'),
(3, '8b01bf9a-b514-4fb9-b224-e3399af668ba', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:48:11', '2025-05-06 14:48:11'),
(4, '8b01bf9a-b514-4fb9-b224-e3399af668ba', 0, 702, 'testing', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:48:59', '2025-05-06 14:48:59'),
(5, '4f18ecb6-19fb-45c0-8a32-3b8ba0f8af30', 0, 702, 'testing', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:49:27', '2025-05-06 14:49:27'),
(6, '1ac9835d-0a6f-4978-a169-ee439039d034', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:50:27', '2025-05-06 14:50:27'),
(7, '1ac9835d-0a6f-4978-a169-ee439039d034', 0, 702, 'dcsd', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:50:30', '2025-05-06 14:50:30'),
(8, '1ac9835d-0a6f-4978-a169-ee439039d034', 0, 702, 'sdcsdc', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:50:36', '2025-05-06 14:50:36'),
(9, 'd4bc2f71-f326-47d2-ac4b-38220dca9550', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:50:49', '2025-05-06 14:50:49'),
(10, '3e624d70-588f-4b8d-9357-5f70f7cdc7ea', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:51:17', '2025-05-06 14:51:17'),
(11, '3e624d70-588f-4b8d-9357-5f70f7cdc7ea', 0, 702, 'sdsdc', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:51:20', '2025-05-06 14:51:20'),
(12, '5df4d984-c53b-4602-9df3-4daf9a976e91', 0, 702, 'sdvvsdv', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:51:27', '2025-05-06 14:51:27'),
(13, '4f18ecb6-19fb-45c0-8a32-3b8ba0f8af30', 0, 702, 'good', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:54:41', '2025-05-06 14:54:41'),
(14, '9554ea19-6ca6-44ea-852a-68efe594e12a', 0, 702, 'dvdfvdfv', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:59:12', '2025-05-06 14:59:12'),
(15, '9554ea19-6ca6-44ea-852a-68efe594e12a', 0, 702, 'test', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:59:24', '2025-05-06 14:59:24'),
(16, '4f18ecb6-19fb-45c0-8a32-3b8ba0f8af30', 0, 702, 'good', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 14:59:40', '2025-05-06 14:59:40'),
(17, '80a1fac9-8dea-4afa-9eee-6fb468ad76ee', 0, 702, 'ascsac', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 15:14:48', '2025-05-06 15:14:48'),
(18, 'undefined', 0, 702, 'testing222', NULL, '2025-05-06', 'Pedro Yorpo', NULL, '2025-05-06 15:24:03', '2025-05-06 15:24:03'),
(19, 'a721aa1f-ea44-4adb-9213-d7081c00839d', 0, 702, 'this is test', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 12:01:30', '2025-05-07 12:01:30'),
(20, 'd0e82769-0aaf-4431-8244-08afb987bb11', 0, 702, 'yes it good', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 12:10:17', '2025-05-07 12:10:17'),
(21, 'a721aa1f-ea44-4adb-9213-d7081c00839d', 0, 702, 'test', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 13:10:00', '2025-05-07 13:10:00'),
(22, 'undefined', 0, 702, 'testing today', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 13:16:36', '2025-05-07 13:16:36'),
(23, '7b9e9107-0618-4a98-ac0f-d0c02d0e6b77', 0, 702, 'sample', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 13:18:18', '2025-05-07 13:18:18'),
(24, 'undefined', 0, 702, 'acscsdc', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 14:10:03', '2025-05-07 14:10:03'),
(25, '9d2a0f7c-2a84-40fd-910c-ed054c03c78f', 0, 702, 'nice pic', NULL, '2025-05-07', 'Pedro Yorpo', NULL, '2025-05-07 14:13:29', '2025-05-07 14:13:29'),
(26, 'undefined', 0, 701, 'ok', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:17:59', '2025-05-07 14:17:59'),
(27, '66e32530-9d62-43f2-b607-0279465e6c65', 0, 701, 'ok', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:18:33', '2025-05-07 14:18:33'),
(28, '66e32530-9d62-43f2-b607-0279465e6c65', 0, 701, 'fine', NULL, '2025-05-07', 'Pedro yorpo', NULL, '2025-05-07 14:18:53', '2025-05-07 14:18:53'),
(29, '8b01bf9a-b514-4fb9-b224-e3399af668ba', 0, 702, 'so nice', NULL, '2025-05-08', 'Pedro Yorpo', NULL, '2025-05-08 15:10:32', '2025-05-08 15:10:32'),
(30, '9d2a0f7c-2a84-40fd-910c-ed054c03c78f', 0, 711, 'nc bossing', NULL, '2025-05-09', 'Human$ crazy', NULL, '2025-05-09 13:04:30', '2025-05-09 13:04:30'),
(31, '9d2a0f7c-2a84-40fd-910c-ed054c03c78f', 0, 711, 'nc update', NULL, '2025-05-09', 'Human$ crazy', 'Human$ crazy', '2025-05-09 14:39:31', '2025-05-23 13:13:20'),
(32, '4f18ecb6-19fb-45c0-8a32-3b8ba0f8af30', 0, 702, 'test', NULL, '2025-05-09', 'Pedro Yorpo', NULL, '2025-05-09 15:50:52', '2025-05-09 15:50:52'),
(33, 'a721aa1f-ea44-4adb-9213-d7081c00839d', 0, 702, 'This is a second comment.', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:22:28', '2025-05-10 02:22:28'),
(34, 'a2905f00-6c02-4b2d-a222-9f7ed655a952', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 02:35:08', '2025-05-10 02:35:08'),
(35, '8906e090-1059-4191-8628-217723a94cb7', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 03:31:58', '2025-05-10 03:31:58'),
(36, '8906e090-1059-4191-8628-217723a94cb7', 0, 702, 'test 2', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 03:32:15', '2025-05-10 03:32:15'),
(37, '8906e090-1059-4191-8628-217723a94cb7', 0, 702, 'test3', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 03:51:38', '2025-05-10 03:51:38'),
(38, '0f965e37-1e4e-42d8-968a-2ed365f2077f', 0, 702, 'nice2222', NULL, '2025-05-10', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-10 04:05:32', '2025-06-06 05:48:37'),
(39, '6631df56-8d99-4b9d-95a6-baf648f217ec', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 04:36:55', '2025-05-10 04:36:55'),
(40, 'b4211a2b-5ca8-49c2-b988-9a90a779d19d', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 04:39:23', '2025-05-10 04:39:23'),
(41, '7a41f61e-7112-4d95-9ff3-3ef57fdd830e', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 05:37:21', '2025-05-10 05:37:21'),
(42, 'undefined', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:00:48', '2025-05-10 06:00:48'),
(43, '40341b62-6027-45f8-beba-383232c4ad11', 0, 702, 'test', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:01:21', '2025-05-10 06:01:21'),
(44, 'fb2de280-5f6f-4fca-82d7-e3eaa079c318', 0, 702, 'i see', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:13:11', '2025-05-10 06:13:11'),
(45, '064720ce-74ea-41f7-997c-2149c8828947', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:15:43', '2025-05-10 06:15:43'),
(46, '7d5c6ac5-89b3-4968-9057-d9ff614f9f8d', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:15:54', '2025-05-10 06:15:54'),
(47, '901865d6-a375-4b80-865f-86e42d20d6f2', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:16:26', '2025-05-10 06:16:26'),
(48, '901865d6-a375-4b80-865f-86e42d20d6f2', 0, 702, 'nice 1', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:16:39', '2025-05-10 06:16:39'),
(49, '6f04a1a7-49cd-4905-a517-7033eb9bdbb0', 0, 702, 'nice 2', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:16:55', '2025-05-10 06:16:55'),
(50, 'f8204f76-1a66-48ad-899e-b6687f2ea275', 0, 702, 'ncie', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:17:34', '2025-05-10 06:17:34'),
(51, '1feb6f3f-8670-45e4-9924-26e4ac528c5d', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:19:01', '2025-05-10 06:19:01'),
(52, '9a2c3c2c-b6c5-4ee7-a023-22af04ed1fe1', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:20:48', '2025-05-10 06:20:48'),
(53, '318a4272-f8cd-44dd-b843-a32da8746a3e', 0, 702, 'nicee', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:25:55', '2025-05-10 06:25:55'),
(54, 'undefined', 0, 702, 'scsdcsd', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:34:33', '2025-05-10 06:34:33'),
(55, 'undefined', 0, 702, 'ascasc', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:35:08', '2025-05-10 06:35:08'),
(56, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'new update22', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:40:17', '2025-06-01 05:14:55'),
(57, '3bf134ae-d974-4ea7-94fa-eee5464fe141', 0, 701, 'edit reply comment', NULL, '2025-05-10', 'Pedro yorpo', 'Pedro yorpo', '2025-05-10 06:41:58', '2025-06-01 05:11:03'),
(58, '6f04a1a7-49cd-4905-a517-7033eb9bdbb0', 0, 702, 'dcsdc', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:57:10', '2025-05-10 06:57:10'),
(59, '6f1cc06f-fdd5-4da4-b30f-81f3d9a726bb', 0, 702, 'nice', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:58:48', '2025-05-10 06:58:48'),
(60, 'e517d99f-79e9-4fcb-8866-612ab7f7aadd', 0, 702, 'hahahaha', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 06:59:03', '2025-05-10 06:59:03'),
(61, '9da38c90-adaf-44af-a260-e253c6a4acbb', 0, 702, 'nice 1', NULL, '2025-05-10', 'Pedro Yorpo', NULL, '2025-05-10 07:09:45', '2025-05-10 07:09:45'),
(62, '8906e090-1059-4191-8628-217723a94cb7', 0, 702, 'csc', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 11:54:22', '2025-05-13 11:54:22'),
(63, '3bf134ae-d974-4ea7-94fa-eee5464fe141', 0, 702, 'this is reply comment', NULL, '2025-05-13', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-13 11:56:39', '2025-06-05 15:39:10'),
(64, '3e359464-695f-4a43-9792-ca47c2c02f21', 0, 702, 'test', NULL, '2025-05-13', 'Pedro Yorpo', 'Pedro Yorpo', '2025-05-13 11:57:00', '2025-07-10 14:41:04'),
(65, '9d2a0f7c-2a84-40fd-910c-ed054c03c78f', 0, 702, 'test', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 11:57:36', '2025-05-13 11:57:36'),
(66, 'undefined', 0, 702, 'nice', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 12:07:46', '2025-05-13 12:07:46'),
(67, '53842fc6-dfbd-4563-8111-0c57df545808', 0, 702, 'nice', NULL, '2025-05-13', 'Pedro Yorpo', NULL, '2025-05-13 12:08:15', '2025-05-13 12:08:15'),
(68, '2053c1a9-85b4-4db9-93ec-5280ce856134', 0, 702, 'Nice', NULL, '2025-05-15', 'Pedro Yorpo', NULL, '2025-05-15 10:06:53', '2025-05-15 10:06:53'),
(69, '2053c1a9-85b4-4db9-93ec-5280ce856134', 0, 702, 'dsvsv', NULL, '2025-05-15', 'Pedro Yorpo', NULL, '2025-05-15 13:29:13', '2025-05-15 13:29:13'),
(70, 'c3b773c6-d3fb-4f9c-bc5b-73989e739869', 0, 702, 'dcsdc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:03:08', '2025-05-18 04:03:08'),
(71, '4f18ecb6-19fb-45c0-8a32-3b8ba0f8af30', 0, 702, 'ascasc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:03:27', '2025-05-18 04:03:27'),
(72, '95a17107-837c-4b3b-b39b-b027bd1bc4c7', 0, 702, 'dscsdc', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 04:49:18', '2025-05-18 04:49:18'),
(73, '167aeaab-6548-4351-871b-dcc3f1e27f2a', 0, 702, 'wdwd', NULL, '2025-05-18', 'Pedro Yorpo', NULL, '2025-05-18 05:10:02', '2025-05-18 05:10:02'),
(74, '8b01bf9a-b514-4fb9-b224-e3399af668ba', 0, 701, 'test edit reply', NULL, '2025-05-20', 'Pedro yorpo', NULL, '2025-05-20 14:43:47', '2025-05-20 14:43:47'),
(75, '8b01bf9a-b514-4fb9-b224-e3399af668ba', 0, 702, 'edit', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 14:46:56', '2025-05-20 14:46:56'),
(76, '79c3a32d-bb28-4368-a212-09295c9cecd6', 0, 702, 'fasf', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:39:46', '2025-05-20 15:39:46'),
(77, 'a7fe87aa-326c-483b-b935-275f1d21babe', 0, 702, 'sdgsd', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:40:12', '2025-05-20 15:40:12'),
(78, '1467a063-a4eb-422f-849f-f47312c7bee0', 0, 702, 'vsdvsdv', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:52:07', '2025-05-20 15:52:07'),
(79, 'ef2156c4-0842-4d9f-8827-4078d8d53701', 0, 702, 'dfndfn', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:56:18', '2025-05-20 15:56:18'),
(80, 'c1d28834-df3c-40cf-b081-39eb7ad6b0a6', 0, 702, 'fgnfgn', NULL, '2025-05-20', 'Pedro Yorpo', NULL, '2025-05-20 15:57:25', '2025-05-20 15:57:25'),
(81, 'd06fdfaa-9746-4a92-885e-2e1910ff57fa', 0, 702, 'okay', NULL, '2025-05-25', 'Pedro Yorpo', NULL, '2025-05-25 04:29:31', '2025-05-25 04:29:31'),
(82, 'undefined', 0, 701, 'asvasv', NULL, '2025-05-28', 'Pedro yorpo', NULL, '2025-05-28 15:01:29', '2025-05-28 15:01:29'),
(83, 'undefined', 0, 701, 'asvasvasv', NULL, '2025-05-28', 'Pedro yorpo', NULL, '2025-05-28 15:01:39', '2025-05-28 15:01:39'),
(84, '8b4c00be-8da3-4dc2-8d2b-4aa6651f2165', 0, 701, 'sdvsdv', NULL, '2025-05-31', 'Pedro yorpo', NULL, '2025-05-31 13:37:05', '2025-05-31 13:37:05'),
(85, '6ed81b14-a31d-49e6-b5be-cb36893c5cc6', 0, 701, 'new', NULL, '2025-05-31', 'Pedro yorpo', 'Pedro yorpo', '2025-05-31 13:44:49', '2025-06-01 05:14:48'),
(86, '3666872e-b792-47be-9d2c-a719865029d9', 0, 702, 'update', NULL, '2025-06-01', 'Pedro Yorpo', 'Pedro Yorpo', '2025-06-01 00:52:24', '2025-06-02 13:10:19'),
(87, '3666872e-b792-47be-9d2c-a719865029d9', 0, 702, 'NFGNFG', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 00:52:39', '2025-06-01 00:52:39'),
(88, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'test2', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 01:54:46', '2025-06-01 04:46:06'),
(89, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'soon', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:01:49', '2025-06-01 05:08:08'),
(90, 'c3336fa3-8f88-40e5-91e4-16a91782305b', 0, 701, 'new', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:38:24', '2025-06-01 05:04:01'),
(91, '59da341d-3343-4036-a8b9-b408a88d8968', 0, 701, 'update', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:42:50', '2025-06-02 12:23:03'),
(92, 'd29463ab-ecef-49b0-9c98-c0e8cd0983bc', 0, 701, 'fdvdf', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:44:14', '2025-06-01 02:44:14'),
(93, 'c3336fa3-8f88-40e5-91e4-16a91782305b', 0, 701, 'new2', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 02:49:07', '2025-06-01 05:04:09'),
(94, '7c4fb0b3-8486-4d02-ae4b-d2c54ca9f577', 0, 701, 'sdvsdv', NULL, '2025-06-01', 'Pedro yorpo', NULL, '2025-06-01 02:52:44', '2025-06-01 02:52:44'),
(95, '59b6cd45-d2e3-4d46-b7f2-f9c518fa4a14', 0, 702, 'dfbdfb', NULL, '2025-06-01', 'Pedro Yorpo', NULL, '2025-06-01 04:16:15', '2025-06-01 04:16:15'),
(96, '3e359464-695f-4a43-9792-ca47c2c02f21', 0, 701, 'fgfgnfgn', NULL, '2025-06-01', 'Pedro yorpo', 'Pedro yorpo', '2025-06-01 05:12:26', '2025-06-01 05:12:32'),
(97, '81d8123b-d9c3-4b57-bda1-141357cf9a12', 0, 701, 'test', NULL, '2025-07-17', 'Pedro yorpo', NULL, '2025-07-17 14:02:53', '2025-07-17 14:02:53'),
(98, 'c6b7790f-c56e-4131-9329-e8c5be099bea', 0, 702, 'test reply', NULL, '2025-08-19', 'Pedro Yorpo', 'Pedro Yorpo', '2025-08-19 14:40:30', '2025-09-24 13:17:14'),
(99, '152567bc-7f86-4a7a-8cf8-e22e1d895291', 0, 702, 'test', NULL, '2025-09-25', 'Pedro Yorpo', 'Pedro Yorpo', '2025-09-25 12:34:53', '2025-09-25 12:35:07'),
(100, '2bd0a7e3-e88a-4838-8d77-2c911f132046', 0, 701, 'Jwis', NULL, '2025-10-06', 'Pedro yorpo', NULL, '2025-10-06 23:55:08', '2025-10-06 23:55:08'),
(101, '8ff544ee-57a9-42b9-9df4-bc962a93eeab', 0, 701, 'no', NULL, '2025-10-08', 'Pedro yorpo', 'Pedro yorpo', '2025-10-08 14:20:15', '2025-10-08 14:20:34'),
(102, '342843dc-fa0c-4d51-a985-494a1a62e153', 0, 701, 'nice 2', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:20:28', '2025-10-25 10:46:23'),
(103, '95decad3-7b5f-481a-8766-c0aad7a1457f', 0, 701, 'reply222', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:23:31', '2025-10-25 05:55:47'),
(104, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'dvsvdfv', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:24:00', '2025-10-25 10:12:29'),
(105, '342843dc-fa0c-4d51-a985-494a1a62e153', 0, 701, 'nice2', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:25:40', '2025-10-25 05:25:40'),
(106, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'update 2 vdfvdf', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 05:49:34', '2025-10-25 07:00:59'),
(107, 'ca71d3fb-b37c-4ef2-b9da-c3ceef7b73b9', 0, 701, 'update', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:55:59', '2025-10-25 05:55:59'),
(108, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 701, 'reply 2', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 05:59:49', '2025-10-25 05:59:49'),
(109, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 702, 'peds amazing', NULL, '2025-10-25', 'Pedro Yorpo', NULL, '2025-10-25 06:09:58', '2025-10-25 06:09:58'),
(110, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 701, 'reply amazing', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 06:10:58', '2025-10-25 06:10:58'),
(111, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'update', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 06:53:33', '2025-10-25 11:54:39'),
(112, '3f5c43cd-a560-4e56-93dd-5aa9e15f60ac', 0, 701, 'DVD', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 07:05:34', '2025-10-25 07:05:34'),
(113, 'db419f90-c624-4684-aa7d-bd3f42bea0ce', 0, 701, 'GNFGNFG', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 07:06:33', '2025-10-25 07:06:33'),
(114, '0d00e5e2-d7e1-4cf9-aa21-ebfa4e1e61ee', 0, 703, 'Exactly! Schools should teach AI literacy, not just basic computer skills.', NULL, '2025-10-25', 'Elizabeth Punay', 'Elizabeth Punay', '2025-10-25 07:43:38', '2025-10-25 07:43:49'),
(115, 'baa2f528-2a18-4328-9972-9c0cb6793ade', 0, 701, 'dfvdfv', NULL, '2025-10-25', 'Pedro yorpo', 'Pedro yorpo', '2025-10-25 09:08:03', '2025-10-25 10:44:14'),
(116, '66676b9e-a0a9-4c12-85a0-12ae84a23b60', 0, 703, 'Okay', NULL, '2025-10-25', 'Elizabeth Punay', NULL, '2025-10-25 11:43:23', '2025-10-25 11:43:23'),
(117, '8b0e9b36-7f0f-447a-b99d-8199ddf5f9d5', 0, 703, 'I know😎', NULL, '2025-10-25', 'Elizabeth Punay', NULL, '2025-10-25 11:45:26', '2025-10-25 11:45:26'),
(118, '66676b9e-a0a9-4c12-85a0-12ae84a23b60', 0, 701, 'TEST', NULL, '2025-10-25', 'Pedro yorpo', NULL, '2025-10-25 11:49:07', '2025-10-25 11:49:07'),
(119, 'a030392c-cd31-4b07-9f96-b6febe5c3159', 0, 702, 'dfvdfvdf', NULL, '2025-11-08', 'Pedro Yorpo', 'Pedro Yorpo', '2025-11-08 13:56:27', '2025-11-17 13:51:06'),
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
(130, '4ed81e1d-db8f-438d-b292-04a2c90fd013', 0, 702, 'test', NULL, '2025-11-17', 'Pedro Yorpo', NULL, '2025-11-17 14:27:23', '2025-11-17 14:27:23'),
(131, '6e40e1f1-e230-493e-8f50-2dadf08bf508', 0, 703, 'dfbdfbdfb', NULL, '2025-12-10', 'Elizabeth Punay', NULL, '2025-12-10 22:29:18', '2025-12-10 22:29:18'),
(132, 'f4ac2e5d-a9e2-4445-91d0-02c7eff82f7d', 0, 701, 'KTYK', NULL, '2026-04-22', 'Pedro yorpo', NULL, '2026-04-22 09:42:27', '2026-04-22 09:42:27'),
(133, '0f90e0b0-8271-40c2-9373-914a1cfa391b', 0, 702, 'sadasdasd', NULL, '2026-07-09', 'Pedro Yorpo', 'Pedro Yorpo', '2026-07-09 06:15:08', '2026-07-09 06:15:14'),
(134, '4044130a-822e-4d62-a373-6ceb312b6307', 0, 702, 'sdcsdc', NULL, '2026-11-16', 'Pedro Yorpo', NULL, '2026-11-15 19:24:15', '2026-11-15 19:24:15'),
(160, '2779f4b2-4725-4902-8aee-05bb2f205050', 0, 702, 'amazing', NULL, '2026-11-16', 'Pedro Yorpo', NULL, '2026-11-15 21:29:03', '2026-11-15 21:29:03'),
(161, '2779f4b2-4725-4902-8aee-05bb2f205050', 0, 702, '@Pedro Yorpo test', NULL, '2026-11-18', 'Pedro Yorpo', 'Pedro Yorpo', '2026-11-18 05:06:17', '2026-11-19 03:55:13'),
(270, '6f71ac33-58c4-4ef4-a81b-bd6782fedd45', 0, 703, 'I love her!', 'http://192.168.1.68:8000/storage/uploads/comments/703/68463f7c-3c69-4b35-b81b-c7ed8350930d.png', '2026-11-21', 'Elizabeth Punay', NULL, '2026-11-21 07:14:37', '2026-11-21 07:14:37');

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
(3, 'USER_SUBSCRIPTION', 'SUB', 91, NULL, '2026-11-21 17:36:38');

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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_codes`
--

INSERT INTO `email_codes` (`id`, `email`, `code`, `created_at`, `updated_at`) VALUES
(2, 'stevenyorpo23@gmail.com', 'uUmpwQC', NULL, NULL),
(4, 'marlonmanigos@gmail.com', 'TunjIQL', NULL, NULL),
(7, 'grantarts7@gmail.com', 'xLwg5EM', NULL, NULL),
(8, 'nexsuz.official13@gmail.com', '2xrHlHc', NULL, NULL),
(9, 'nexsuz.official13@gmail.com', 'uRRTxKu', NULL, NULL),
(10, 'nexsuz.official13@gmail.com', 'lfVC51D', NULL, NULL),
(11, 'nexsuz.official13@gmail.com', 'AA2l39z', NULL, NULL),
(17, 'leahyorpo17@gmail.com', 'Slh72ni', NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_settings`
--

INSERT INTO `evaluation_settings` (`id`, `transNo`, `weights`, `thresholds`, `passing_score`, `competencies`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, NULL, '{\"education\":15,\"experience\":25,\"skills\":30,\"answers\":20,\"competencies\":10}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 70, NULL, NULL, '2026-11-21 08:01:24', '2026-11-21 08:01:24'),
(5, 'TR-000001', '{\"education\":80,\"experience\":90,\"skills\":80,\"answers\":70,\"competencies\":85}', '{\"strong\":85,\"qualified\":70,\"review\":50}', 50, NULL, '703', '2026-11-21 08:25:23', '2026-11-21 08:25:23');

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
) ENGINE=InnoDB AUTO_INCREMENT=769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`id`, `follower_code`, `following_code`, `follow_status`, `created_at`, `updated_at`) VALUES
(744, 702, 701, 'accepted', '2026-11-19 14:44:00', '2026-11-19 06:42:38'),
(762, 701, 716, 'pending', '2026-11-21 05:59:18', '2026-11-21 05:59:18'),
(763, 701, 719, 'pending', '2026-11-21 06:03:22', '2026-11-21 06:03:22');

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

--
-- Dumping data for table `interviews`
--

INSERT INTO `interviews` (`id`, `applied_id`, `transNo`, `scheduled_at`, `duration_min`, `type`, `location`, `meeting_link`, `interviewer_name`, `interviewer_code`, `status`, `feedback`, `score`, `result`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(8, 76, 'TR-000001', '2026-11-22 10:00:00', 30, 'onsite', 'Cebu', NULL, 'pedro', '703', 'completed', 'you good job', 80, 'pass', 'you pass', '703', '2026-11-21 09:23:27', '2026-11-21 09:24:48');

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
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobposting`
--

INSERT INTO `jobposting` (`job_id`, `transNo`, `code`, `company`, `role_code`, `job_name`, `location`, `benefits`, `currency`, `min_salary`, `max_salary`, `job_position`, `job_description`, `job_about`, `qualification`, `work_type`, `job_image`, `fullname`, `created_at`, `updated_at`, `recordstatus`, `is_online`, `vacancies`, `employment_type`, `experience_level`, `salary_period`, `education`, `required_skills`, `preferred_qualifications`, `application_deadline`, `allow_cv`, `application_limit`, `min_pass_score`, `auto_screen`, `template_id`) VALUES
(131, 'TR-000001', 703, 'ELIZABETH Company', 'DEF-CLIENT', 'Software Developer', 'Gun-ob,Lapu-lapu City', 'Competitive salary with performance-based bonuses. Health, dental, and vision insurance. Flexible/remote work options. Paid time off and holidays. 401(k) with company match.', 'PHP', '20000.00', '35000.00', 'Web Developer', 'As a Software Developer, you will design, develop, and maintain high-quality software solutions. You\'ll write clean, efficient, and well-documented code, participate in code reviews, debug and resolve technical issues, and collaborate with cross-functional teams to deliver features on time. You\'ll also stay current with emerging technologies and contribute ideas to improve our development practices, tools, and overall product quality.\r\n\r\nKey responsibilities:\r\n- Develop and maintain web/software applications using [your tech stack, e.g., Angular, Node.js, .NET]\r\n- Write unit and integration tests to ensure code reliability\r\n- Collaborate with UI/UX designers to implement responsive, user-friendly interfaces\r\n- Participate in sprint planning, stand-ups, and retrospectives\r\n- Troubleshoot, debug, and optimize application performance\r\n- Document technical specifications and maintain code repositories', 'We\'re looking for a motivated Software Developer to join our engineering team and help build, improve, and maintain scalable web applications. You\'ll work closely with designers.', 'As a Software Developer, you will design, develop, and maintain high-quality software solutions. You\'ll write clean, efficient, and well-documented code, participate in code reviews, debug and resolve technical issues, and collaborate with cross-functional teams to deliver features on time. You\'ll also stay current with emerging technologies and contribute ideas to improve our development practices, tools, and overall product quality.\r\n\r\nKey responsibilities:\r\n- Develop and maintain web/software applications using [your tech stack, e.g., Angular, Node.js, .NET]\r\n- Write unit and integration tests to ensure code reliability\r\n- Collaborate with UI/UX designers to implement responsive, user-friendly interfaces\r\n- Participate in sprint planning, stand-ups, and retrospectives\r\n- Troubleshoot, debug, and optimize application performance\r\n- Document technical specifications and maintain code repositories', 'Onsite', '/storage/uploads/703/JobPosting/fc30249a-b170-46a2-9f5b-44de1df21122/1795102053.jpg', 'Elizabeth Punay', '2026-07-31 05:28:02', '2026-11-21 08:38:35', 'active', 1, 50, 'Full-time', NULL, 'Monthly', NULL, NULL, NULL, '2026-11-27', 1, 50, 100, 1, 1),
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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_templates`
--

INSERT INTO `job_templates` (`id`, `name`, `description`, `theme`, `sections`, `is_default`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Classic', 'The standard NEXSUZ job layout.', '{\"preset\":\"custom\",\"primary\":\"#0a66c2\",\"accent\":\"#7a5af8\",\"background\":\"#f3f2ef\",\"surface\":\"#ffffff\",\"text\":\"#1b2a4a\",\"font\":\"verdana\",\"radius\":14,\"buttonStyle\":\"filled\",\"buttonShape\":\"pill\",\"headerStyle\":\"gradient\",\"backgroundStyle\":\"solid\",\"layout\":\"two_column\"}', '[{\"key\":\"summary\",\"label\":\"About the job\",\"enabled\":true},{\"key\":\"responsibilities\",\"label\":\"Job description\",\"enabled\":true},{\"key\":\"qualifications\",\"label\":\"Qualifications\",\"enabled\":true},{\"key\":\"education\",\"label\":\"Education\",\"enabled\":true},{\"key\":\"skills\",\"label\":\"Required skills\",\"enabled\":true},{\"key\":\"benefits\",\"label\":\"Benefits and perks\",\"enabled\":true},{\"key\":\"questions\",\"label\":\"Screening questions\",\"enabled\":true}]', 1, 'published', NULL, '701', '2026-11-21 07:45:15', '2026-11-21 07:54:07', NULL),
(2, 'Classic (copy)', 'The standard NEXSUZ job layout.', '{\"preset\":\"classic\",\"primary\":\"#0a66c2\",\"accent\":\"#7a5af8\",\"background\":\"#f3f2ef\",\"surface\":\"#ffffff\",\"text\":\"#1b2a4a\",\"font\":\"verdana\",\"radius\":14,\"buttonStyle\":\"filled\",\"buttonShape\":\"pill\",\"headerStyle\":\"gradient\",\"backgroundStyle\":\"solid\",\"layout\":\"single\"}', '[{\"key\":\"summary\",\"label\":\"About the job\",\"enabled\":true},{\"key\":\"responsibilities\",\"label\":\"Job description\",\"enabled\":true},{\"key\":\"qualifications\",\"label\":\"Qualifications\",\"enabled\":true},{\"key\":\"education\",\"label\":\"Education\",\"enabled\":true},{\"key\":\"skills\",\"label\":\"Required skills\",\"enabled\":true},{\"key\":\"benefits\",\"label\":\"Benefits and perks\",\"enabled\":true},{\"key\":\"questions\",\"label\":\"Screening questions\",\"enabled\":true}]', 0, 'draft', '701', '701', '2026-11-21 07:53:32', '2026-11-21 07:53:38', '2026-11-21 07:53:38');

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
(41, 25, 'top_navigation', 'Account', 'fa-solid fa-building', 'clients-company', 'accountmanagement', 8, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, '2026-05-30 20:25:49'),
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
) ENGINE=InnoDB AUTO_INCREMENT=1390 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `code`, `sender_id`, `receiver_id`, `message`, `created_at`, `updated_at`, `is_read`) VALUES
(885, 702, 92, 91, 'test', '2025-04-24 13:28:00', '2025-04-24 13:40:23', 1),
(886, 702, 92, 91, 'test', '2025-04-24 13:57:48', '2025-04-24 14:02:25', 1),
(887, 702, 92, 91, 'welcome abourad acascasc', '2025-04-24 14:00:20', '2025-04-24 14:02:14', 1),
(888, 702, 92, 91, 'sacasc', '2025-04-24 14:05:31', '2025-04-26 08:24:13', 1),
(889, 701, 91, 92, 'test', '2025-04-24 14:07:55', '2025-04-26 08:24:22', 1),
(890, 701, 91, 102, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:22', '2025-05-03 11:50:39', 1),
(891, 701, 91, 93, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:28', '2025-04-26 06:13:15', 1),
(892, 701, 91, 95, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:33', '2025-09-15 15:07:29', 1),
(893, 701, 91, 98, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:37', '2025-04-24 14:12:37', 0),
(894, 701, 91, 101, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:41', '2025-04-24 14:12:41', 0),
(895, 701, 91, 104, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:45', '2025-04-24 14:12:45', 0),
(896, 701, 91, 103, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:12:51', '2025-04-26 14:45:20', 1),
(897, 701, 91, 91, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:13:01', '2025-04-26 13:27:38', 1),
(898, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2025-04-24 14:13:09', '2025-04-26 09:29:50', 1),
(899, 702, 92, 92, '.message-preview {\n  font-size: 1rem;\n  color: #555;\n  white-space: normal;       /* Allows wrapping */\n  overflow: visible;         /* No clipping */\n  text-overflow: unset;      /* Disables ellipsis */\n}', '2025-04-24 14:21:53', '2025-04-26 13:08:36', 1),
(900, 712, 103, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-24 14:39:01', '2025-04-26 10:10:50', 1),
(901, 703, 93, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 06:08:35', '2025-04-26 09:20:09', 1),
(902, 703, 93, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 06:25:50', '2025-04-26 09:28:14', 1),
(903, 703, 93, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 06:25:51', '2025-04-26 09:41:13', 1),
(904, 703, 93, 92, 'v', '2025-04-26 06:25:52', '2025-04-26 10:11:57', 1),
(905, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 07:44:48', '2025-04-26 07:52:09', 1),
(906, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 07:46:06', '2025-04-26 12:45:50', 1),
(907, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 07:46:08', '2025-04-26 10:12:21', 1),
(908, 701, 91, 92, 'test', '2025-04-26 07:46:11', '2025-04-26 08:20:33', 1),
(909, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 08:07:18', '2025-04-26 08:25:32', 1),
(910, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 08:08:13', '2025-04-26 09:41:08', 1),
(911, 701, 91, 91, 'test', '2025-04-26 08:50:41', '2025-04-26 13:27:38', 1),
(912, 702, 92, 92, 'sdasasc', '2025-04-26 09:00:28', '2025-04-26 13:08:36', 1),
(913, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 09:01:29', '2025-04-26 09:19:18', 1),
(914, 701, 91, 92, 'testing', '2025-04-26 09:02:04', '2025-04-26 09:41:06', 1),
(915, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 09:10:19', '2025-04-26 09:48:10', 1),
(916, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou\nd and clear! her feature', '2025-04-26 09:16:19', '2025-04-26 09:57:04', 1),
(917, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 09:25:55', '2025-04-26 13:27:38', 1),
(918, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 09:32:34', '2025-04-26 10:02:15', 1),
(919, 701, 91, 92, 'testing', '2025-04-26 09:33:41', '2025-04-26 09:45:42', 1),
(920, 702, 92, 92, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 09:41:31', '2025-04-26 13:08:36', 1),
(921, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 09:57:15', '2025-04-26 12:52:50', 1),
(922, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 10:03:45', '2025-04-26 12:31:47', 1),
(923, 702, 92, 92, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 10:20:04', '2025-04-26 13:08:36', 1),
(924, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 12:32:08', '2025-04-26 13:27:38', 1),
(925, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 12:47:28', '2025-04-26 13:17:21', 1),
(926, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 12:51:45', '2025-04-26 12:55:52', 1),
(927, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-04-26 13:26:24', '2025-04-26 13:27:38', 1),
(928, 702, 92, 91, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 13:27:25', '2025-04-26 13:29:33', 1),
(929, 702, 92, 92, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 13:27:30', '2025-04-26 13:31:37', 1),
(930, 702, 92, 91, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 13:30:06', '2025-04-26 13:30:27', 1),
(931, 702, 92, 91, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 13:30:08', '2025-04-26 13:30:27', 1),
(932, 702, 92, 91, '.message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 13:30:09', '2025-04-26 13:30:27', 1),
(933, 702, 92, 92, 'message-preview { font-size: 1rem; color: #555; white-space: normal; /* Allows wrapping */ overflow: visible; /* No clipping */ text-overflow: unset; /* Disables ellipsis */ }', '2025-04-26 13:36:07', '2025-04-26 13:36:18', 1),
(934, 702, 92, 92, 'test', '2025-04-26 13:47:00', '2025-04-26 13:47:16', 1),
(935, 702, 92, 92, 'test', '2025-04-26 13:48:30', '2025-04-26 13:49:05', 1),
(936, 702, 92, 92, 'test', '2025-04-26 13:58:44', '2025-04-26 14:01:34', 1),
(937, 702, 92, 92, 'sacsc', '2025-04-26 13:59:02', '2025-04-26 14:01:34', 1),
(938, 702, 92, 92, 'asc', '2025-04-26 13:59:06', '2025-04-26 14:01:34', 1),
(939, 702, 92, 92, 'test', '2025-04-26 14:03:35', '2025-04-26 14:04:17', 1),
(940, 702, 92, 92, 'asc', '2025-04-26 14:05:11', '2025-04-26 14:07:53', 1),
(941, 702, 92, 92, 'test', '2025-04-26 14:07:57', '2025-04-26 14:09:42', 1),
(942, 702, 92, 92, 'asccasc', '2025-04-26 14:08:06', '2025-04-26 14:09:42', 1),
(943, 702, 92, 92, 'ascasc', '2025-04-26 14:08:19', '2025-04-26 14:09:42', 1),
(944, 702, 92, 91, 'svsdvsdv', '2025-04-26 14:13:42', '2025-04-26 14:14:03', 1),
(945, 701, 91, 92, 'test', '2025-04-26 14:14:07', '2025-04-26 14:14:16', 1),
(946, 701, 91, 92, 'test', '2025-04-26 14:28:26', '2025-04-26 14:28:39', 1),
(947, 701, 91, 92, 'ascasc', '2025-04-26 14:28:28', '2025-04-26 14:28:39', 1),
(948, 701, 91, 92, 'ascascsac', '2025-04-26 14:28:47', '2025-04-26 14:29:29', 1),
(949, 701, 91, 92, 'dvsdv', '2025-04-26 14:29:02', '2025-04-26 14:29:29', 1),
(950, 701, 91, 92, 'ikaw', '2025-04-26 14:29:11', '2025-04-26 14:29:29', 1),
(951, 702, 92, 91, 'musta', '2025-04-26 14:29:37', '2025-04-26 14:29:48', 1),
(952, 701, 91, 92, 'okay ra', '2025-04-26 14:30:01', '2025-04-26 14:30:10', 1),
(953, 701, 91, 92, 'sacasc', '2025-04-26 14:30:02', '2025-04-26 14:30:10', 1),
(954, 702, 92, 91, 'test', '2025-04-26 14:42:28', '2025-04-26 14:48:55', 1),
(955, 702, 92, 91, 'xc c', '2025-04-26 14:43:04', '2025-04-26 14:48:55', 1),
(956, 702, 92, 103, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 14:45:01', '2025-04-26 14:46:51', 1),
(957, 701, 91, 93, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 14:46:04', '2025-04-26 15:08:24', 1),
(958, 702, 92, 103, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 14:46:19', '2025-04-26 14:46:51', 1),
(959, 701, 91, 103, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 14:46:22', '2025-04-26 14:46:51', 1),
(960, 701, 91, 103, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature?', '2025-04-26 14:46:26', '2025-04-26 14:46:51', 1),
(961, 712, 103, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature', '2025-04-26 14:47:18', '2025-04-26 14:48:27', 1),
(962, 712, 103, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature', '2025-04-26 14:47:47', '2025-04-26 14:48:27', 1),
(963, 712, 103, 102, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature', '2025-04-26 14:49:21', '2025-05-03 11:50:39', 1),
(964, 702, 92, 103, 'ascascsac', '2025-04-26 14:50:21', '2025-04-26 14:50:48', 1),
(965, 701, 91, 93, 'ELI BEBE TUG NA!!', '2025-04-26 15:07:32', '2025-04-26 15:08:24', 1),
(966, 703, 93, 91, 'pedroooooo ungoooo', '2025-04-26 15:09:04', '2025-04-26 15:09:04', 1),
(967, 703, 93, 91, 'Boo-ang', '2025-04-26 15:11:43', '2025-04-26 15:12:03', 1),
(968, 701, 91, 93, 'IKAY BOANG HAHHA', '2025-04-26 15:12:14', '2025-04-26 15:13:42', 1),
(969, 703, 93, 91, 'HAHAHHAHA 2 years ka AB', '2025-04-26 15:12:34', '2025-04-26 15:13:03', 1),
(970, 701, 91, 93, 'TUG NA BEBE', '2025-04-26 15:13:10', '2025-04-26 15:13:42', 1),
(971, 701, 91, 93, 'I LOVE YOU...', '2025-04-26 15:13:42', '2025-04-26 15:13:42', 1),
(972, 701, 91, 93, 'Ndjs', '2025-04-26 22:52:56', '2025-05-13 12:05:55', 1),
(973, 702, 92, 103, 'test', '2025-05-04 22:41:49', '2025-05-16 15:00:48', 1),
(974, 702, 92, 93, 'test', '2025-05-05 12:00:26', '2025-05-13 12:05:55', 1),
(975, 702, 92, 103, 'test', '2025-05-05 15:08:48', '2025-05-16 15:00:49', 1),
(976, 711, 102, 92, 'bossing', '2025-05-15 00:37:16', '2025-05-15 10:05:15', 1),
(977, 711, 102, 102, 'bossing', '2025-05-15 00:37:28', '2025-05-15 00:37:34', 1),
(978, 702, 92, 102, 'boss', '2025-05-15 14:25:20', '2025-05-16 01:27:14', 1),
(979, 711, 102, 92, 'meeting ta unya bossing ?', '2025-05-16 01:27:34', '2025-05-16 14:47:16', 1),
(980, 702, 92, 102, 'Okay boss', '2025-05-16 14:47:23', '2025-05-16 15:10:40', 1),
(981, 701, 91, 92, 'Embracing Change: Why Growth Begins Outside Your Comfort Zone\n\nChange is one of the few constants in life, yet it\'s something many of us resist. Whether it\'s switching careers, moving to a new city, starting a business, or even adopting a new habit, change can be daunting. But it’s also the very thing that propels us forward, challenges our limits, and helps us grow.\n\nGrowth rarely happens in the comfort zone. It\'s in the moments of uncertainty, challenge, and even discomfort that we discover who we really are. Every risk taken, every failure faced, and every unexpected turn can teach us valuable lessons—if we let them.\n\nThink back to a time when you faced a difficult decision or stepped into unfamiliar territory. At the moment, it may have felt scary or overwhelming. But chances are, you came out stronger, wiser, and more capable. That’s the beauty of change—it molds us into better versions of ourselves.\n\nSo the next time life nudges you in a new direction, don’t resist. Lean into the change. Embrace the discomfort. Trust the process. Because everything you want—growth, success, fulfillment—is just on the other side of your comfort zone. See Less', '2025-05-20 15:47:42', '2025-05-20 15:48:57', 1),
(982, 701, 91, 93, 'test', '2025-06-01 05:15:30', '2025-07-17 14:01:25', 1),
(983, 701, 91, 95, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-06-01 05:15:57', '2025-09-15 15:07:29', 1),
(984, 701, 91, 98, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-06-01 05:16:05', '2025-06-01 05:16:05', 0),
(985, 701, 91, 92, 'test', '2025-06-06 03:26:14', '2025-06-06 05:57:32', 1),
(986, 701, 91, 92, 'dfbdb', '2025-06-06 03:26:21', '2025-06-06 05:57:32', 1),
(987, 701, 91, 92, 'ddfbdfb', '2025-06-06 03:26:28', '2025-06-06 03:26:55', 1),
(988, 712, 103, 93, 'In today\'s fast-paced world, technology is evolving at an unprecedented rate, transforming almost every aspect of our lives. From communication to healthcare, education, and even the way we work, technology continues to shape the future. The rise of artificial intelligence, machine learning, and automation has made it possible for businesses to operate more efficiently and for people to accomplish tasks that were once unimaginable. However, this rapid pace of innovation also brings with it significant challenges. As technology continues to advance, society faces ethical dilemmas and questions about privacy, security, and the impact on jobs and industries. The debate over how to balance progress with responsibility is ongoing, and policymakers, industry leaders, and communities must work together to ensure that the benefits of technology are maximized while minimizing its potential risks.', '2025-06-06 03:32:19', '2025-07-17 14:01:25', 1),
(989, 712, 103, 92, 'In today\'s fast-paced world, technology is evolving at an unprecedented rate, transforming almost every aspect of our lives. From communication to healthcare, education, and even the way we work, technology continues to shape the future. The rise of artificial intelligence, machine learning, and automation has made it possible for businesses to operate more efficiently and for people to accomplish tasks that were once unimaginable. However, this rapid pace of innovation also brings with it significant challenges. As technology continues to advance, society faces ethical dilemmas and questions about privacy, security, and the impact on jobs and industries. The debate over how to balance progress with responsibility is ongoing, and policymakers, industry leaders, and communities must work together to ensure that the benefits of technology are maximized while minimizing its potential risks.', '2025-06-06 03:32:24', '2025-06-06 03:33:48', 1),
(990, 712, 103, 95, 'In today\'s fast-paced world, technology is evolving at an unprecedented rate, transforming almost every aspect of our lives. From communication to healthcare, education, and even the way we work, technology continues to shape the future. The rise of artificial intelligence, machine learning, and automation has made it possible for businesses to operate more efficiently and for people to accomplish tasks that were once unimaginable. However, this rapid pace of innovation also brings with it significant challenges. As technology continues to advance, society faces ethical dilemmas and questions about privacy, security, and the impact on jobs and industries. The debate over how to balance progress with responsibility is ongoing, and policymakers, industry leaders, and communities must work together to ensure that the benefits of technology are maximized while minimizing its potential risks.', '2025-06-06 03:32:37', '2025-09-15 15:07:29', 1),
(991, 712, 103, 98, 'In today\'s fast-paced world, technology is evolving at an unprecedented rate, transforming almost every aspect of our lives. From communication to healthcare, education, and even the way we work, technology continues to shape the future. The rise of artificial intelligence, machine learning, and automation has made it possible for businesses to operate more efficiently and for people to accomplish tasks that were once unimaginable. However, this rapid pace of innovation also brings with it significant challenges. As technology continues to advance, society faces ethical dilemmas and questions about privacy, security, and the impact on jobs and industries. The debate over how to balance progress with responsibility is ongoing, and policymakers, industry leaders, and communities must work together to ensure that the benefits of technology are maximized while minimizing its potential risks.', '2025-06-06 03:32:41', '2025-06-06 03:32:41', 0),
(992, 701, 91, 102, 'Boss wa jud ka code Karun na week boss Kay Wala Koy wifi nibalhin ko og boarding house', '2025-06-17 09:02:05', '2025-07-17 14:37:14', 1),
(993, 701, 91, 92, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-07-17 14:00:03', '2025-07-19 01:56:29', 1),
(994, 703, 93, 91, 'test', '2025-07-17 14:01:31', '2025-07-17 14:01:51', 1),
(995, 703, 93, 92, 'test', '2025-07-27 03:02:32', '2025-07-27 03:03:34', 1),
(996, 703, 93, 92, 'aascasc', '2025-07-27 03:02:57', '2025-07-27 03:03:34', 1),
(997, 703, 93, 92, 'dcsdcsdv', '2025-07-27 03:03:09', '2025-07-27 03:03:34', 1),
(998, 703, 93, 92, 'sdvsdv', '2025-07-27 03:03:19', '2025-07-27 03:03:34', 1),
(999, 702, 92, 93, 'TEST', '2025-08-11 13:44:39', '2025-08-19 14:13:23', 1),
(1000, 702, 92, 93, 'scsac', '2025-08-11 13:44:44', '2025-08-19 14:13:23', 1),
(1001, 702, 92, 93, 'FD XCV', '2025-08-17 01:19:31', '2025-08-19 14:13:23', 1),
(1002, 702, 92, 92, 'sdvvsdv', '2025-08-25 03:43:44', '2025-08-25 03:44:00', 1),
(1003, 702, 92, 92, 'sdvsdv', '2025-08-25 03:43:51', '2025-08-25 03:44:00', 1),
(1004, 702, 92, 92, 'sdvsdv', '2025-08-25 03:44:03', '2025-09-04 13:56:59', 1),
(1005, 702, 92, 92, 'svdsdv', '2025-08-25 03:44:07', '2025-09-04 13:56:59', 1),
(1006, 702, 92, 92, 'sdvsdv', '2025-08-25 03:44:11', '2025-09-04 13:56:59', 1),
(1007, 702, 92, 92, 'test', '2025-09-06 14:50:43', '2025-09-13 14:02:29', 1),
(1008, 703, 93, 95, 'hi sir dhave good evening', '2025-09-15 14:28:04', '2025-09-15 14:30:25', 1),
(1009, 703, 93, 95, 'test', '2025-09-15 14:29:40', '2025-09-15 14:30:24', 1),
(1010, 701, 91, 93, 'test', '2025-09-23 14:12:57', '2025-10-08 14:14:31', 1),
(1011, 702, 92, 92, 'test', '2025-09-24 22:01:36', '2025-10-18 07:02:48', 1),
(1012, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-09-24 22:02:02', '2025-09-24 22:02:43', 1),
(1013, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-09-24 22:02:18', '2025-09-25 02:16:18', 1),
(1014, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-09-24 22:02:26', '2025-09-25 02:16:18', 1),
(1015, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-09-24 22:02:33', '2025-09-25 02:16:10', 1),
(1016, 701, 91, 91, 'test', '2025-10-04 04:21:56', '2025-10-13 13:01:31', 1),
(1017, 701, 91, 93, 'boo', '2025-10-08 14:22:13', '2025-10-18 03:55:40', 1),
(1018, 701, 91, 93, 'tug na', '2025-10-08 14:22:20', '2025-10-18 03:55:40', 1),
(1019, 702, 92, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-10-09 13:22:24', '2025-10-13 13:01:31', 1),
(1020, 701, 91, 91, 'fvdfvdfv', '2025-10-10 12:47:26', '2025-10-13 13:01:31', 1),
(1021, 702, 92, 98, 'Boo', '2025-10-13 14:15:54', '2025-10-13 14:15:54', 0),
(1022, 701, 91, 104, 'Ulokkkk', '2025-10-18 02:06:45', '2025-10-18 02:06:45', 0),
(1023, 702, 92, 91, 'csdcsd', '2025-10-22 14:15:25', '2025-10-24 00:48:26', 1),
(1024, 701, 91, 93, 'ELIII OPAWWWW!!!!', '2025-10-25 11:45:34', '2025-10-25 11:45:43', 1),
(1025, 701, 91, 93, 'HAHAHAHAA', '2025-10-25 11:46:04', '2025-10-31 14:47:39', 1),
(1026, 701, 91, 136, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-10-30 13:05:59', '2025-10-30 13:06:13', 1),
(1027, 717, 136, 91, 'test', '2025-10-30 13:06:21', '2025-10-30 13:18:57', 1),
(1028, 701, 91, 91, 'cscsdc', '2025-11-08 14:08:31', '2025-11-09 06:07:25', 1),
(1029, 701, 91, 91, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-11-09 06:06:44', '2025-11-09 06:07:15', 1),
(1030, 701, 91, 141, 'Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature\nGot your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature Got your test message — loud and clear! ✅ Want to continue tweaking your chat UI or test another feature? Got your test message — lou d and clear! her feature', '2025-11-09 06:07:03', '2025-12-08 13:13:26', 1),
(1031, 703, 93, 91, 'test', '2025-11-23 09:50:48', '2025-11-23 09:50:59', 1),
(1032, 701, 91, 93, 'test22', '2025-11-23 09:51:03', '2025-12-18 14:40:24', 1),
(1033, 702, 92, 104, 'Test', '2025-11-23 10:01:37', '2025-11-23 10:01:37', 0),
(1034, 701, 91, 92, 'Your application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:18:58', '2025-12-06 04:20:17', 1),
(1035, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:23:05', '2025-12-06 04:23:24', 1),
(1036, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'approved\'.', '2025-12-06 04:24:49', '2025-12-06 04:41:44', 1),
(1037, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:29:16', '2025-12-06 04:31:21', 1),
(1038, 701, 91, 92, 'Subject: Application Status Update\n\n Your application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:30:49', '2025-12-06 04:31:20', 1),
(1039, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'approved\'.', '2025-12-06 04:32:03', '2025-12-06 04:41:44', 1),
(1040, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:32:32', '2025-12-06 04:41:44', 1),
(1041, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:39:50', '2025-12-06 04:41:44', 1),
(1042, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'approved\'.', '2025-12-06 04:40:53', '2025-12-06 04:41:44', 1),
(1043, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:41:27', '2025-12-06 04:41:44', 1),
(1044, 702, 92, 91, 'nice', '2025-12-06 04:42:16', '2025-12-06 04:42:26', 1),
(1045, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 04:43:28', '2025-12-06 04:43:44', 1),
(1046, 701, 91, 92, 'Subject: Application Status Update\nYour application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 04:45:00', '2025-12-06 04:45:50', 1),
(1047, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 04:45:20', '2025-12-06 04:45:50', 1),
(1048, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'Ongoing\'.', '2025-12-06 05:00:20', '2025-12-06 06:24:02', 1),
(1049, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 05:01:00', '2025-12-06 06:24:02', 1),
(1050, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 05:02:04', '2025-12-18 14:40:24', 1),
(1051, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:17:05', '2025-12-06 06:24:02', 1),
(1052, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:21:11', '2025-12-18 14:40:24', 1),
(1053, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 05:21:21', '2025-12-06 06:24:02', 1),
(1054, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'ongoing\'.', '2025-12-06 05:21:35', '2025-12-18 14:40:24', 1),
(1055, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'approved\'.', '2025-12-06 05:21:56', '2025-12-18 14:40:24', 1),
(1056, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:29:38', '2025-12-06 06:24:02', 1),
(1057, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 05:31:03', '2025-12-06 06:24:02', 1),
(1058, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:34:50', '2025-12-06 06:24:02', 1),
(1059, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 05:35:10', '2025-12-06 06:24:02', 1),
(1060, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:36:21', '2025-12-06 06:24:02', 1),
(1061, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 05:36:49', '2025-12-06 06:24:02', 1),
(1062, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:38:58', '2025-12-06 06:24:02', 1),
(1063, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 05:39:42', '2025-12-06 06:24:02', 1),
(1064, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:42:36', '2025-12-06 06:24:02', 1),
(1065, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 05:43:22', '2025-12-06 06:24:02', 1),
(1066, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 05:45:52', '2025-12-06 06:24:02', 1),
(1067, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 05:46:57', '2025-12-06 06:24:02', 1),
(1068, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'approved\'.', '2025-12-06 05:47:44', '2025-12-06 06:24:02', 1),
(1069, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:07:12', '2025-12-06 06:24:02', 1),
(1070, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:07:21', '2025-12-18 14:40:24', 1),
(1071, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:07:54', '2025-12-06 06:24:02', 1),
(1072, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:12:51', '2025-12-06 06:24:02', 1),
(1073, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:13:39', '2025-12-06 06:24:02', 1),
(1074, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:13:53', '2025-12-18 14:40:24', 1),
(1075, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'approved\'.', '2025-12-06 06:14:18', '2025-12-18 14:40:24', 1),
(1076, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'reject\'.', '2025-12-06 06:17:20', '2025-12-06 06:24:02', 1),
(1077, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:18:15', '2025-12-18 14:40:24', 1),
(1078, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:20:26', '2025-12-06 06:24:02', 1),
(1079, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:21:03', '2025-12-06 06:24:02', 1),
(1080, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'approved\'.', '2025-12-06 06:21:49', '2025-12-06 06:24:02', 1),
(1081, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:24:56', '2025-12-06 06:25:57', 1),
(1082, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:25:34', '2025-12-06 06:25:57', 1),
(1083, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'reject\'.', '2025-12-06 06:25:46', '2025-12-06 06:25:57', 1),
(1084, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:27:36', '2025-12-06 06:52:43', 1),
(1085, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:31:59', '2025-12-06 06:52:43', 1),
(1086, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'reject\'.', '2025-12-06 06:32:22', '2025-12-06 06:52:43', 1),
(1087, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:34:46', '2025-12-06 06:52:43', 1),
(1088, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:53:37', '2025-12-06 12:41:51', 1),
(1089, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:56:23', '2025-12-18 14:40:24', 1),
(1090, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'reject\'.', '2025-12-06 06:56:59', '2025-12-18 14:40:24', 1),
(1091, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 06:57:35', '2025-12-18 14:40:24', 1),
(1092, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'review\'.', '2025-12-06 06:58:09', '2025-12-18 14:40:24', 1),
(1093, 701, 91, 93, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'approved\'.', '2025-12-06 06:58:19', '2025-12-18 14:40:24', 1),
(1094, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'review\'.', '2025-12-06 13:56:22', '2025-12-06 13:57:05', 1),
(1095, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'approved\'.', '2025-12-06 13:58:20', '2025-12-13 01:49:32', 1),
(1096, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 13:59:01', '2025-12-13 01:49:32', 1),
(1097, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'review\'.', '2025-12-06 13:59:15', '2025-12-13 01:49:32', 1),
(1098, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'approved\'.', '2025-12-06 14:06:19', '2025-12-13 01:49:32', 1),
(1099, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 14:11:03', '2025-12-13 01:49:32', 1),
(1100, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'review\'.', '2025-12-06 14:15:45', '2025-12-13 01:49:32', 1),
(1101, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'approved\'.', '2025-12-06 14:16:01', '2025-12-13 01:49:32', 1),
(1102, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 14:19:53', '2025-12-13 01:49:32', 1),
(1103, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'review\'.', '2025-12-06 14:20:22', '2025-12-13 01:49:32', 1),
(1104, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'approved\'.', '2025-12-06 14:21:04', '2025-12-13 01:49:32', 1),
(1105, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 14:21:39', '2025-12-13 01:49:32', 1),
(1106, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'reject\'.', '2025-12-06 14:33:03', '2025-12-13 01:49:32', 1),
(1107, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 14:34:45', '2025-12-13 01:49:32', 1),
(1108, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'reject\'.', '2025-12-06 14:35:30', '2025-12-13 01:49:32', 1),
(1109, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 14:37:53', '2025-12-13 01:49:32', 1),
(1110, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'review\'.', '2025-12-06 14:41:23', '2025-12-13 01:49:32', 1),
(1111, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'approved\'.', '2025-12-06 14:42:16', '2025-12-13 01:49:32', 1),
(1112, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'reject\'.', '2025-12-06 15:21:37', '2025-12-13 01:49:32', 1),
(1113, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-06 15:21:44', '2025-12-13 01:49:32', 1),
(1114, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-06 15:21:49', '2025-12-13 01:49:32', 1),
(1115, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'review\'.', '2025-12-06 23:32:56', '2025-12-13 01:49:32', 1),
(1116, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'reject\'.', '2025-12-06 23:33:50', '2025-12-13 01:49:32', 1),
(1117, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'reject\'.', '2025-12-08 15:42:25', '2025-12-13 01:49:32', 1),
(1118, 701, 91, 92, 'Subject: Application Status Update Your application for \'asc\' has been updated to \'applied_active\'.', '2025-12-08 15:46:03', '2025-12-13 01:49:32', 1),
(1119, 701, 91, 92, 'Subject: Application Status Update Your application for \'PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-08 15:46:05', '2025-12-13 01:49:32', 1),
(1120, 701, 91, 92, 'Subject: Application Status Update Your application for \'test\' has been updated to \'review\'.', '2025-12-09 13:22:29', '2025-12-13 01:49:32', 1),
(1121, 701, 91, 92, 'Subject: Application Status Update Your application for \'test\' has been updated to \'reject\'.', '2025-12-09 13:30:06', '2025-12-13 01:49:32', 1),
(1122, 701, 91, 92, 'Subject: Application Status Update Your application for \'test\' has been updated to \'applied_active\'.', '2025-12-09 13:30:15', '2025-12-13 01:49:32', 1),
(1123, 701, 91, 93, 'Subject: Application Status Update Your application for \'test\' has been updated to \'reject\'.', '2025-12-09 13:30:28', '2025-12-18 14:40:24', 1),
(1124, 701, 91, 93, 'Subject: Application Status Update Your application for \'test\' has been updated to \'applied_active\'.', '2025-12-09 13:30:35', '2025-12-18 14:40:24', 1),
(1125, 701, 91, 92, 'Subject: Application Status Update Your application for \'test\' has been updated to \'applied_active\'.', '2025-12-09 15:43:44', '2025-12-13 01:49:32', 1),
(1126, 701, 91, 92, 'Subject: Application Status Update Your application for \'test\' has been updated to \'review\'.', '2025-12-09 15:47:01', '2025-12-13 01:49:32', 1),
(1127, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'reject\'.', '2025-12-09 15:47:22', '2025-12-13 01:49:32', 1),
(1128, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-09 15:47:29', '2025-12-13 01:49:32', 1),
(1129, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'review\'.', '2025-12-09 15:47:37', '2025-12-13 01:49:32', 1),
(1130, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'reject\'.', '2025-12-10 09:14:55', '2025-12-13 01:49:32', 1),
(1131, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-10 09:15:01', '2025-12-13 01:49:32', 1),
(1132, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'review\'.', '2025-12-10 14:32:33', '2025-12-13 01:49:32', 1),
(1133, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'approved\'.', '2025-12-10 15:07:55', '2025-12-13 01:49:32', 1),
(1134, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'applied_active\'.', '2025-12-10 15:11:15', '2025-12-13 01:49:32', 1),
(1135, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'review\'.', '2025-12-13 00:47:00', '2025-12-13 01:49:32', 1),
(1136, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'reject\'.', '2025-12-13 01:08:51', '2025-12-13 01:49:32', 1),
(1137, 718, 141, 92, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'applied_active\'.', '2025-12-13 01:09:02', '2025-12-13 01:49:32', 1),
(1138, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'review\'.', '2025-12-16 14:19:33', '2025-12-29 05:20:59', 1),
(1139, 703, 93, 91, 'test', '2025-12-18 14:40:37', '2025-12-19 14:24:49', 1),
(1140, 701, 91, 91, 'dfbfdb', '2025-12-19 14:31:43', '2026-04-28 23:30:54', 1),
(1141, 702, 92, 93, 'nice', '2025-12-19 14:55:25', '2026-01-12 13:12:04', 1),
(1142, 702, 92, 91, 'dddd', '2025-12-24 01:19:29', '2026-04-28 23:30:54', 1),
(1143, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'applied_active\'.', '2025-12-24 02:33:45', '2025-12-29 05:20:59', 1),
(1144, 703, 93, 91, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'applied_active\'.', '2025-12-30 03:20:30', '2026-04-28 23:30:54', 1),
(1145, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'review\'.', '2025-12-30 03:26:29', '2026-01-07 02:54:25', 1),
(1146, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'interview\'.', '2025-12-30 03:38:46', '2026-01-07 02:54:25', 1),
(1147, 703, 93, 91, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'review\'.', '2025-12-30 04:11:45', '2026-04-28 23:30:54', 1),
(1148, 703, 93, 91, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'interview\'.', '2025-12-30 04:12:06', '2026-04-28 23:30:54', 1),
(1149, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'applied_active\'.', '2026-01-12 14:30:06', '2026-02-28 23:49:22', 1),
(1150, 703, 93, 91, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'applied_active\'.', '2026-01-12 14:30:07', '2026-04-28 23:30:54', 1),
(1151, 703, 93, 92, 'Subject: Application Status Update Your application for \'Teacher\' has been updated to \'review\'.', '2026-01-12 14:59:17', '2026-02-28 23:49:22', 1),
(1152, 703, 93, 92, 'Subject: Application Status Update Your application for \'ESL\' has been updated to \'review\'.', '2026-02-28 23:49:08', '2026-02-28 23:49:22', 1),
(1153, 703, 93, 92, 'Subject: Application Status Update Your application for \'ESL\' has been updated to \'reject\'.', '2026-02-28 23:56:56', '2026-02-28 23:57:33', 1),
(1154, 703, 93, 92, 'Subject: Application Status Update Your application for \'ESL\' has been updated to \'applied_active\'.', '2026-03-01 00:00:38', '2026-04-21 13:12:17', 1),
(1209, 702, 92, 141, 'sdcdcsdc', '2026-11-18 07:43:34', '2026-11-18 07:43:34', 0),
(1210, 702, 92, 141, 'sdcdcsdc', '2026-11-18 07:43:39', '2026-11-18 07:43:39', 0),
(1211, 702, 92, 141, '🤝🎉', '2026-11-18 07:43:46', '2026-11-18 07:43:46', 0),
(1237, 702, 92, 141, 'Subject: Application Status Update Your application for \'IT PROGRAMMER\' has been updated to \'reject\'.', '2026-11-18 07:53:16', '2026-11-18 07:53:16', 0),
(1250, 702, 92, 93, 'sdsdvdsv', '2026-11-18 08:21:13', '2026-11-19 09:01:49', 1),
(1294, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-18 08:29:20', '2026-11-18 08:31:12', 1),
(1295, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-18 08:29:37', '2026-11-18 08:30:17', 1),
(1296, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-18 08:29:48', '2026-11-18 08:31:12', 1),
(1297, 702, 92, 91, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-18 08:31:18', '2026-11-19 04:25:49', 1),
(1298, 702, 92, 103, 'In today\'s fast-paced world, technology is evolving at an unprecedented rate, transforming almost every aspect of our lives. From communication to healthcare, education, and even the way we work, technology continues to shape the future. The rise of artificial intelligence, machine learning, and automation has made it possible for businesses to operate more efficiently and for people to accomplish tasks that were once unimaginable. However, this rapid pace of innovation also brings with it significant challenges. As technology continues to advance, society faces ethical dilemmas and questions about privacy, security, and the impact on jobs and industries. The debate over how to balance progress with responsibility is ongoing, and policymakers, industry leaders, and communities must work together to ensure that the benefits of technology are maximized while minimizing its potential risks.', '2026-11-19 03:58:11', '2026-11-19 03:58:11', 0),
(1367, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-19 06:48:48', '2026-11-19 06:49:39', 1),
(1368, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-19 06:49:14', '2026-11-19 06:49:39', 1),
(1369, 701, 91, 92, 'Got your test message — loud and clear! ✅\nWant to continue tweaking your chat UI or test another feature?', '2026-11-19 06:49:25', '2026-11-19 06:49:37', 1),
(1374, 702, 92, 91, 'Twst', '2026-11-21 07:06:44', '2026-11-21 07:07:13', 1),
(1375, 702, 92, 91, '😮', '2026-11-21 07:06:50', '2026-11-21 07:07:12', 1),
(1376, 702, 92, 91, 'Hi', '2026-11-21 07:10:15', '2026-11-21 07:44:21', 1),
(1377, 703, 93, 92, 'Hi m\'amm', '2026-11-21 07:18:19', '2026-11-21 07:18:25', 1),
(1378, 701, 91, 92, 'HELLOW BOO MWAHH', '2026-11-21 07:44:57', '2026-11-21 08:42:13', 1),
(1382, 703, 93, 92, 'Update on your application for Software Developer\n\nHi Pedro,\n\nThank you for your interest in Software Developer at ELIZABETH Company and for the time you put into applying. After careful consideration we have decided not to move forward with your application. We wish you every success in your search.', '2026-11-21 08:20:37', '2026-11-21 08:40:34', 1);
INSERT INTO `messages` (`id`, `code`, `sender_id`, `receiver_id`, `message`, `created_at`, `updated_at`, `is_read`) VALUES
(1383, 703, 93, 92, 'We received your application for Software Developer\n\nHi Pedro,\n\nThank you for applying for Software Developer at ELIZABETH Company. We have received your application and will let you know as soon as it has been reviewed.\n\nGood luck!', '2026-11-21 09:17:26', '2026-11-21 09:17:45', 1),
(1384, 703, 93, 92, 'Interview invitation: Software Developer\n\nHi Pedro,\n\nWe would like to invite you to an interview for Software Developer at ELIZABETH Company.\n\nPlease reply to this message if you need to change the time.', '2026-11-21 09:23:27', '2026-11-21 09:25:32', 1),
(1385, 703, 93, 92, 'Good news about your application for Software Developer\n\nHi Pedro,\n\nCongratulations! Your application for Software Developer at ELIZABETH Company has been approved. We will contact you shortly with the next steps.', '2026-11-21 09:26:32', '2026-11-21 09:27:57', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(28, '2026_11_22_100000_create_subscription_features_table', 10);

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
('kimtaejung@163.com', 'zzKSiUhK1I7Ym8HsqypT3IKdpNupzlPSEZuYJAYKnrJe2teiYkNWevsRjRbodO4L', '2026-02-03 09:44:42'),
('pedroyorpo17@gmail.com', 'LeHf7TSOounBdOt3W49wxfAeMablrMpZhszVACeMM3tXrNuyl9s6WuZo7xanOg5y', '2025-09-10 14:38:56'),
('pedroyorpo22@gmail.com', '1Oh9fLArgjyloCkBNmaBQaTo90XWN6FtxGilCHqMFvD5SS44ZBNLHY1JBWNrZMJp', '2026-11-19 06:29:47');

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
) ENGINE=InnoDB AUTO_INCREMENT=2373 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(193, 'App\\Models\\User', 103, 'Personal Access Token', '6ea467c4a264af0c5472847b001c959b45dbb0b995db68b0de09e2e29d638f5e', '[\"*\"]', '2025-01-17 17:15:24', NULL, '2025-01-17 17:04:30', '2025-01-17 17:15:24'),
(195, 'App\\Models\\User', 103, 'Personal Access Token', '6ab7dcd01c66b9144f023ae869017bd5339eaf55c14959a9b8188d4dbd5d45c2', '[\"*\"]', '2025-01-17 17:09:58', NULL, '2025-01-17 17:09:27', '2025-01-17 17:09:58'),
(199, 'App\\Models\\User', 103, 'Personal Access Token', '02d90e89fcddb2a6fffa6d3c939ae831bc6127e46fbffa09b575a0346cc61229', '[\"*\"]', '2025-01-17 17:22:10', NULL, '2025-01-17 17:21:59', '2025-01-17 17:22:10'),
(204, 'App\\Models\\User', 103, 'Personal Access Token', '6ae0238cd5932d977ba4dd23da90713f44e51e9186ebcef7c3ca11f85981131e', '[\"*\"]', '2025-01-18 12:09:47', NULL, '2025-01-18 12:09:42', '2025-01-18 12:09:47'),
(774, 'App\\Models\\User', 103, 'Personal Access Token', 'cfe318b74a47f79129a3092027a92e02715193ea2f62d4f909c41d4df1158028', '[\"*\"]', '2025-04-13 14:18:33', NULL, '2025-04-13 13:08:43', '2025-04-13 14:18:33'),
(784, 'App\\Models\\User', 103, 'Personal Access Token', '2adc62b558c377f5187c7065f68b6b845488b8782cbc6b375cb538062ed0ceb0', '[\"*\"]', '2025-04-13 15:10:08', NULL, '2025-04-13 15:08:50', '2025-04-13 15:10:08'),
(791, 'App\\Models\\User', 103, 'Personal Access Token', 'fe6fb99db2493df2656e87461e4adea6d2d773ed7a1226dee96dc3c8f6124ea4', '[\"*\"]', '2025-04-13 15:16:11', NULL, '2025-04-13 15:16:08', '2025-04-13 15:16:11'),
(1596, 'App\\Models\\User', 108, 'Personal Access Token', 'd51c3cb9fd9d1cc72d871eaaf1bb22c9f7f936ea719ea2512b2a373c3e0d97a5', '[\"*\"]', '2025-09-20 06:41:54', NULL, '2025-09-20 06:41:50', '2025-09-20 06:41:54'),
(1738, 'App\\Models\\User', 104, 'Personal Access Token', 'a004e47621e9871a888dd3f90eed79042ef081cd004b869552b5a27ed0a7f30b', '[\"*\"]', '2025-10-18 02:07:01', NULL, '2025-10-18 01:55:45', '2025-10-18 02:07:01'),
(1799, 'App\\Models\\User', 136, 'Personal Access Token', 'dc3e38b0998f06c84d4d41c641b300a06d81399a80e28b8ebbc5287a2e76c4ec', '[\"*\"]', '2025-10-30 13:17:27', NULL, '2025-10-30 13:05:17', '2025-10-30 13:17:27'),
(2194, 'App\\Models\\User', 141, 'Personal Access Token', 'aef91d9e744259abd2e43be7c341b3183ec303e2ce94f7aa089fd1989a2b9e4d', '[\"*\"]', '2026-06-20 23:31:30', NULL, '2026-06-20 22:53:51', '2026-06-20 23:31:30'),
(2365, 'App\\Models\\User', 92, 'Personal Access Token', 'fb624fa3f62ded617a14c5fcf84bef3819383a954245bf05a545888080fbeb5a', '[\"*\"]', '2026-11-21 17:39:11', NULL, '2026-11-21 15:42:29', '2026-11-21 17:39:11'),
(2366, 'App\\Models\\User', 91, 'Personal Access Token', 'e6a881c20f30362be4182128ec693a9800d7651007eb2f8d55f0304096780d91', '[\"*\"]', '2026-11-21 17:40:08', NULL, '2026-11-21 15:45:29', '2026-11-21 17:40:08');

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
) ENGINE=InnoDB AUTO_INCREMENT=1279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `code`, `posts_uuid`, `transNo`, `caption`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(325, 702, 'b75cedcc-202b-480b-bb0f-f3fc316590ed', 2, 'test', 1, 'Pedro Yorpo', 'Pedro Yorpo', '2026-11-15 20:18:41', '2026-11-18 07:23:53'),
(518, 702, '7b8ddcf7-9e47-4171-8da1-ba498473959c', 4, '# NEXSUZ – ENHANCE POST PRIVACY, REACTIONS & EMOJI EFFECTS\r\n\r\n**Target:** Existing NEXSUZ Social Media Platform  \r\n**Development:** Angular 13 + Laravel\r\n\r\nInspect the existing code before making changes. Enhance the current post system without creating new projects or replacing existing modules.\r\n\r\n### 1. Post Privacy\r\nImplement post visibility:\r\n- **Public** – Everyone can view and react.\r\n- **Private** – Only the post owner can view.\r\n- **Friends** – Only accepted friends can view and react.\r\n\r\nAdd a privacy selector when creating or editing posts. Display a suitable privacy icon beside each post.\r\n\r\n### 2. Post Reactions\r\nImplement Facebook-style reactions:\r\n- 👍 Like\r\n- ❤️ Love\r\n- 😂 Haha\r\n- 😮 Wow\r\n- 😢 Sad\r\n- 😡 Angry\r\n\r\nRequirements:\r\n- Click Like to apply or remove a reaction.\r\n- Long-press or hover on Like to display the reaction picker.\r\n- Allow users to change their reaction.\r\n- Display reaction counts and reaction details.\r\n- Prevent duplicate reactions per user per post.\r\n- Update reactions dynamically without refreshing the page.\r\n\r\n### 3. Animated Emoji Effects\r\nAdd attractive reaction animations:\r\n- Floating emoji animation when reacting.\r\n- Small pop/bounce effect when selecting a reaction.\r\n- Smooth fade and transition effects.\r\n- Optional floating emoji particles for Love and Haha.\r\n- Keep animations lightweight and mobile-friendly.\r\n\r\n### 4. Reaction Sound Effects\r\nAdd optional sound effects:\r\n- Like: subtle click/pop.\r\n- Love: soft heart sound.\r\n- Haha: playful sound.\r\n- Wow: short surprise sound.\r\n- Sad: soft notification sound.\r\n- Angry: subtle impact sound.\r\n\r\nRequirements:\r\n- Add sound ON/OFF setting.\r\n- Default sound OFF.\r\n- Play sound only after direct user interaction.\r\n- Avoid autoplay restrictions.\r\n- Prevent overlapping sounds.\r\n- Respect browser and mobile audio policies.\r\n\r\n### 5. UI/UX\r\n- Modern Facebook-style reaction picker.\r\n- Responsive desktop and mobile layouts.\r\n- Smooth animations without affecting scrolling.\r\n- Display the selected reaction instead of the default Like button.\r\n- Use existing NEXSUZ design conventions and shared styles.\r\n\r\n### 6. Backend & Security\r\n- Inspect existing Laravel APIs, database tables, and relationships.\r\n- Enforce post privacy on the backend, not just Angular.\r\n- Validate permissions before viewing or reacting.\r\n- Prevent unauthorized access to private posts.\r\n- Reuse existing tables and APIs where possible.\r\n- Only add necessary migrations.\r\n\r\n### 7. Testing\r\nTest:\r\n- Public, private, and friends-only visibility.\r\n- Reaction creation, removal, and switching.\r\n- Reaction counts and concurrent updates.\r\n- Unauthorized access prevention.\r\n- Sound settings and animations.\r\n- Desktop and mobile responsiveness.\r\n\r\n### IMPORTANT\r\n- Preserve all existing modules and functionality.\r\n- Do not create duplicate components, APIs, or database tables.\r\n- Do not modify unrelated files.\r\n- Avoid unnecessary refactoring.\r\n- Inspect first, implement only what is needed, and run focused tests.\r\n\r\n**Expected Result:** A responsive social media post system with privacy controls, six interactive reactions, animated emoji effects, and optional reaction sounds.', 1, 'Pedro Yorpo', 'Pedro Yorpo', '2026-11-18 07:22:43', '2026-11-18 07:28:09');

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
) ENGINE=InnoDB AUTO_INCREMENT=609 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reactionpost`
--

INSERT INTO `reactionpost` (`id`, `post_id`, `code`, `reaction`, `updated_at`, `create_at`) VALUES
(1, 69, '702', 'Haha', '2025-10-05 14:57:25', '2025-10-05 14:57:25'),
(2, 68, '702', 'Like', '2025-10-10 23:29:21', '2025-10-10 23:29:21'),
(3, 37, '702', 'Love', '2025-10-05 10:19:15', '2025-10-05 10:19:15'),
(4, 67, '702', 'Love', '2025-10-04 14:59:12', '2025-10-04 14:59:12'),
(5, 65, '702', 'Wow', '2025-10-05 14:58:43', '2025-10-05 14:58:43'),
(6, 44, '702', 'Like', '2025-10-10 13:27:36', '2025-10-10 13:27:36'),
(7, 39, '702', 'Haha', '2025-11-22 02:58:43', '2025-11-22 02:58:43'),
(8, 66, '702', 'Haha', '2025-10-05 14:39:23', '2025-10-05 14:39:23'),
(9, 70, '701', 'Love', '2025-11-09 01:00:58', '2025-11-09 01:00:58'),
(10, 69, '701', 'Wow', '2025-10-08 14:02:16', '2025-10-08 14:02:16'),
(11, 67, '701', 'Love', '2025-10-18 14:01:41', '2025-10-18 14:01:41'),
(12, 66, '701', 'Like', '2025-10-19 12:45:27', '2025-10-19 12:45:27'),
(13, 68, '701', 'Love', '2025-10-10 13:00:06', '2025-10-10 13:00:06'),
(14, 65, '701', 'Love', '2025-10-10 12:59:44', '2025-10-10 12:59:44'),
(15, 64, '701', 'Love', '2025-10-06 12:35:34', '2025-10-06 12:35:34'),
(16, 65, '703', 'Angry', '2025-10-21 12:29:20', '2025-10-21 12:29:20'),
(17, 64, '703', 'Love', '2025-10-05 15:05:29', '2025-10-05 15:05:29'),
(18, 37, '703', 'Love', '2025-10-05 15:02:26', '2025-10-05 15:02:26'),
(19, 44, '703', 'Wow', '2025-10-05 15:03:43', '2025-10-05 15:03:43'),
(20, 69, '703', 'Love', '2025-10-05 15:06:46', '2025-10-05 15:06:46'),
(21, 68, '703', 'Haha', '2025-10-07 12:51:04', '2025-10-07 12:51:04'),
(22, 67, '703', 'Angry', '2025-10-05 15:16:00', '2025-10-05 15:16:00'),
(23, 44, '701', 'Love', '2025-10-19 14:45:51', '2025-10-19 14:45:51'),
(24, 39, '701', 'Love', '2025-10-06 12:35:52', '2025-10-06 12:35:52'),
(25, 1, '701', 'Haha', '2025-11-08 14:23:28', '2025-11-08 14:23:28'),
(26, 72, '701', 'Love', '2025-10-20 13:57:52', '2025-10-20 13:57:52'),
(27, 72, '703', 'Love', '2025-10-08 14:15:09', '2025-10-08 14:15:09'),
(28, 72, '702', 'Love', '2025-10-20 12:20:18', '2025-10-20 12:20:18'),
(29, 73, '701', 'Like', '2025-10-19 13:44:36', '2025-10-19 13:44:36'),
(30, 73, '702', 'Love', '2025-10-14 04:30:51', '2025-10-14 04:30:51'),
(31, 74, '701', 'Love', '2025-10-16 21:56:02', '2025-10-16 21:56:02'),
(32, 75, '702', 'Like', '2025-10-20 14:45:01', '2025-10-20 14:45:01'),
(33, 76, '701', 'Love', '2025-10-25 10:46:07', '2025-10-25 10:46:07'),
(34, 75, '701', 'Love', '2025-11-08 14:23:02', '2025-11-08 14:23:02'),
(35, 74, '702', 'Wow', '2025-10-20 14:45:05', '2025-10-20 14:45:05'),
(36, 80, '701', 'Haha', '2025-10-25 00:48:04', '2025-10-25 00:48:04'),
(37, 79, '701', 'Like', '2025-10-25 00:41:18', '2025-10-25 00:41:18'),
(38, 88, '701', 'Love', '2025-10-25 00:47:50', '2025-10-25 00:47:50'),
(39, 94, '701', 'Haha', '2025-10-25 00:48:28', '2025-10-25 00:48:28'),
(40, 85, '701', 'Love', '2025-10-25 00:50:41', '2025-10-25 00:50:41'),
(41, 92, '701', 'Like', '2025-10-28 04:35:09', '2025-10-28 04:35:09'),
(42, 96, '702', 'Wow', '2026-01-12 06:30:47', '2026-01-12 06:30:47'),
(43, 96, '703', 'Wow', '2025-12-17 13:35:29', '2025-12-17 13:35:29'),
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
(54, 93, '703', 'Like', '2025-10-25 11:00:59', '2025-10-25 11:00:59'),
(55, 92, '703', 'Haha', '2025-10-25 11:01:04', '2025-10-25 11:01:04'),
(56, 91, '703', 'Wow', '2025-10-25 11:01:09', '2025-10-25 11:01:09'),
(57, 90, '703', 'Like', '2025-10-31 14:46:50', '2025-10-31 14:46:50'),
(58, 89, '703', 'Angry', '2025-10-25 11:01:18', '2025-10-25 11:01:18'),
(59, 88, '703', 'Like', '2025-11-09 03:09:50', '2025-11-09 03:09:50'),
(60, 97, '703', 'Wow', '2025-10-25 11:38:05', '2025-10-25 11:38:05'),
(61, 97, '701', 'Love', '2025-10-25 11:39:24', '2025-10-25 11:39:24'),
(62, 91, '701', 'Love', '2025-10-28 04:35:15', '2025-10-28 04:35:15'),
(63, 89, '701', 'Sad', '2025-10-28 04:35:25', '2025-10-28 04:35:25'),
(64, 90, '701', 'Love', '2025-10-30 13:20:33', '2025-10-30 13:20:33'),
(65, 98, '702', 'Love', '2025-10-30 14:57:31', '2025-10-30 14:57:31'),
(66, 108, '702', 'Love', '2025-10-30 14:58:03', '2025-10-30 14:58:03'),
(67, 107, '702', 'Love', '2025-10-31 14:22:16', '2025-10-31 14:22:16'),
(68, 92, '702', 'Love', '2025-11-08 13:55:35', '2025-11-08 13:55:35'),
(69, 110, '702', 'Haha', '2025-11-08 13:55:56', '2025-11-08 13:55:56'),
(70, 109, '702', 'Love', '2025-11-08 13:55:59', '2025-11-08 13:55:59'),
(71, 113, '701', 'Wow', '2025-11-09 13:48:06', '2025-11-09 13:48:06'),
(72, 114, '702', 'Love', '2025-11-15 07:04:33', '2025-11-15 07:04:33'),
(73, 115, '701', 'Sad', '2025-11-19 13:46:16', '2025-11-19 13:46:16'),
(74, 108, '701', 'Haha', '2025-11-16 03:01:18', '2025-11-16 03:01:18'),
(75, 115, '702', 'Like', '2025-11-17 14:27:36', '2025-11-17 14:27:36'),
(76, 85, '702', 'Haha', '2025-11-17 13:03:23', '2025-11-17 13:03:23'),
(77, 116, '702', 'Love', '2025-12-06 00:58:23', '2025-12-06 00:58:23'),
(78, 112, '701', 'Love', '2025-11-23 09:16:33', '2025-11-23 09:16:33'),
(79, 123, '701', 'Love', '2025-11-25 22:06:14', '2025-11-25 22:06:14'),
(80, 120, '701', 'Haha', '2025-11-29 14:42:30', '2025-11-29 14:42:30'),
(81, 124, '701', 'Love', '2025-12-02 13:44:13', '2025-12-02 13:44:13'),
(82, 127, '702', 'Love', '2025-12-08 13:29:04', '2025-12-08 13:29:04'),
(83, 94, '702', 'Love', '2025-12-08 13:29:43', '2025-12-08 13:29:43'),
(84, 127, '703', 'Haha', '2025-12-18 15:19:57', '2025-12-18 15:19:57'),
(85, 125, '702', 'Love', '2025-12-24 01:19:08', '2025-12-24 01:19:08'),
(86, 128, '702', 'Love', '2026-01-07 02:53:43', '2026-01-07 02:53:43'),
(87, 129, '705', 'Like', '2026-02-03 09:15:07', '2026-02-03 09:15:07'),
(88, 129, '701', 'Love', '2026-02-17 15:22:58', '2026-02-17 15:22:58'),
(89, 130, '702', 'Haha', '2026-04-22 09:47:24', '2026-04-22 09:47:24'),
(90, 131, '702', 'Love', '2026-05-30 18:16:31', '2026-05-30 18:16:31'),
(91, 131, '703', 'Haha', '2026-06-08 05:11:45', '2026-06-08 05:11:45'),
(92, 133, '702', 'Wow', '2026-07-23 05:24:12', '2026-07-23 05:24:12'),
(93, 134, '702', 'Love', '2026-11-15 19:30:25', '2026-11-15 19:30:25'),
(103, 201, '702', 'Love', '2026-11-15 19:57:39', '2026-11-15 19:57:39'),
(113, 232, '702', 'Care', '2026-11-15 20:31:30', '2026-11-15 20:05:36'),
(196, 489, '701', 'Love', '2026-11-15 21:27:41', '2026-11-15 21:27:39'),
(236, 489, '702', 'Angry', '2026-11-18 07:17:29', '2026-11-18 07:17:20'),
(476, 518, '702', 'Haha', '2026-11-19 03:52:34', '2026-11-18 08:34:03'),
(605, 325, '702', 'Haha', '2026-11-21 05:40:08', '2026-11-21 05:40:08'),
(606, 1278, '702', 'Love', '2026-11-21 05:43:07', '2026-11-21 05:43:07'),
(608, 325, '703', 'Like', '2026-11-21 09:01:50', '2026-11-21 09:01:50');

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
(7, 702, '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 'Love', '2025-05-28 15:11:30'),
(8, 703, '3a746bc2-9d58-493f-8c0e-e908a69a53c9', 'Haha', '2025-05-16 02:07:00'),
(9, 702, 'c225d726-722c-4949-a7a8-8b2f23a360d2', 'Heart', '2025-05-16 15:09:14'),
(15, 711, 'c225d726-722c-4949-a7a8-8b2f23a360d2', 'Angry', '2025-06-06 04:42:07'),
(16, 702, '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 'Love', '2025-06-01 05:39:06'),
(17, 702, '35e045fe-7da2-4236-aed7-b2091b73291a', 'Like', '2025-06-01 03:08:16'),
(18, 702, 'aab5830c-ca18-4058-9a18-9e736e874518', 'Wow', '2025-07-13 00:29:37'),
(19, 702, 'a571faae-bf64-4dd6-a026-abb78269c16a', 'Wow', '2025-05-31 13:46:07'),
(20, 701, '95af83cd-04a1-4121-9c6c-4406fd01fb63', 'Love', '2025-06-30 12:18:38'),
(21, 702, '95af83cd-04a1-4121-9c6c-4406fd01fb63', 'Wow', '2025-05-25 03:37:37'),
(22, 702, 'e4705480-0469-4423-b261-827f672bd958', 'Love', '2025-10-04 15:10:11'),
(23, 701, 'e4705480-0469-4423-b261-827f672bd958', 'Haha', '2025-10-28 14:01:52'),
(24, 701, '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 'Love', '2025-05-25 04:13:35'),
(25, 701, '35e045fe-7da2-4236-aed7-b2091b73291a', 'Like', '2025-05-25 04:30:10'),
(26, 701, 'undefined', 'Sad', '2025-05-31 01:29:54'),
(27, 702, 'undefined', 'Haha', '2025-05-26 13:24:50'),
(28, 712, '2d1f36ac-1702-4a8a-b933-faed7945454d', 'Love', '2025-05-25 05:17:57'),
(29, 712, 'a571faae-bf64-4dd6-a026-abb78269c16a', 'Love', '2025-05-25 05:18:17'),
(30, 712, '1a6b8865-29fc-48f3-b1ed-afe8d1110c66', 'Love', '2025-05-25 05:18:44'),
(31, 712, 'aab5830c-ca18-4058-9a18-9e736e874518', 'Love', '2025-05-25 05:18:32'),
(32, 712, '35e045fe-7da2-4236-aed7-b2091b73291a', 'Like', '2025-05-25 05:18:36'),
(33, 702, '8904f1f5-77d7-41aa-9de6-98f21480e95e', 'Love', '2025-07-05 01:43:04'),
(34, 702, '91a6e786-faf0-4c5a-ad6b-370cfd35ce6e', 'Love', '2025-06-02 13:16:08'),
(35, 702, '3a746bc2-9d58-493f-8c0e-e908a69a53c9,4a544d17-2824-40b2-9a2b-f6ad95469814,fe15601d-f201-405f-814a-122d5bb2ead5,9f3439ef-a734-4a82-bbb4-cbb78e5c289f,ed22a4a2-5856-49f0-afd5-5b4b0a5355db,36aade6f-e355-46ca-8d34-bf4970513c06', 'Like', '2025-05-25 05:58:14'),
(36, 702, '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 'Love', '2025-08-19 13:42:40'),
(37, 702, '399a6fe7-d1ac-42bf-825c-648294be889f', 'Like', '2025-05-25 06:12:52'),
(38, 702, '5eeec985-96c9-48e6-886b-6443b0dc5185', 'Like', '2025-06-01 04:11:05'),
(39, 702, '97c65aee-3a27-4e0c-8067-0689f9e8609b', 'Wow', '2025-11-08 14:01:09'),
(40, 702, '2d1f36ac-1702-4a8a-b933-faed7945454d', 'Love', '2025-05-25 06:16:06'),
(41, 702, '4a544d17-2824-40b2-9a2b-f6ad95469814', 'Love', '2025-06-01 00:51:57'),
(42, 702, 'fe15601d-f201-405f-814a-122d5bb2ead5', 'Sad', '2025-07-05 01:43:24'),
(43, 702, 'ed22a4a2-5856-49f0-afd5-5b4b0a5355db', 'Like', '2025-06-06 05:48:10'),
(44, 701, '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758,d6eb1ba6-58c6-43b3-8c0c-950de1916a33', '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758,d6eb1ba6-58c6-43b3-8c0c-950de1916a33', '2025-05-31 00:33:18'),
(45, 701, '5e4dbdf7-8af8-4ae9-9154-a6c88d3b6758', 'Love', '2025-06-02 08:06:47'),
(46, 701, 'd6eb1ba6-58c6-43b3-8c0c-950de1916a33', 'Like', '2025-05-31 13:39:30'),
(47, 701, '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 'Like', '2025-05-26 14:49:52'),
(48, 701, '399a6fe7-d1ac-42bf-825c-648294be889f', 'Love', '2025-05-26 14:51:04'),
(49, 712, 'be5fdcdf-e86c-4285-a609-d5263815fad2', 'Love', '2025-05-26 14:57:15'),
(50, 712, '2343d813-724f-4953-8c39-1016f07112a1', 'Love', '2025-05-26 14:57:25'),
(51, 711, 'undefined', 'Love', '2025-05-27 00:23:24'),
(52, 711, '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 'Love', '2025-06-06 04:40:50'),
(53, 702, 'a60aacd5-d9fc-48f8-b37d-61bef03df903', 'Love', '2025-06-01 00:52:09'),
(54, 701, '[object Object]', '[object Object]', '2025-05-31 01:22:01'),
(55, 702, '9f3439ef-a734-4a82-bbb4-cbb78e5c289f', 'Like', '2025-05-31 14:05:00'),
(56, 702, '01e889b5-7d3e-4fc4-a62d-8ce0e61556dc', 'Sad', '2025-06-01 01:29:04'),
(57, 702, '703ce24b-9c46-476a-91db-6e8fabcb5491', 'Wow', '2025-06-01 01:28:51'),
(58, 702, '928785b3-af8d-4124-a889-a40fd4da9c85', 'Like', '2025-07-27 01:16:08'),
(59, 702, '085a4269-1732-4c61-951e-179a0ee6c77f', 'Like', '2025-06-06 05:12:15'),
(60, 711, 'aab5830c-ca18-4058-9a18-9e736e874518', 'Love', '2025-06-06 04:42:52'),
(61, 702, '36aade6f-e355-46ca-8d34-bf4970513c06', 'Like', '2025-06-06 04:56:46'),
(62, 702, '1e7cfa1e-672f-42ab-8283-446ffb902ebc', 'Love', '2025-06-06 06:56:59'),
(63, 701, '4859c8d7-55d4-4d93-b74b-93b091198585', 'Love', '2025-07-01 14:54:28'),
(64, 701, 'b493ee6a-3753-4ccf-ad76-cc69aabb17b5', 'Love', '2025-07-01 14:55:11'),
(65, 703, '5df8c371-cc91-48c4-bcc6-9ed5637296ad', 'Love', '2025-07-13 14:13:31'),
(66, 701, '97c65aee-3a27-4e0c-8067-0689f9e8609b', 'Haha', '2025-07-25 03:32:07'),
(67, 702, '5df8c371-cc91-48c4-bcc6-9ed5637296ad', 'Love', '2025-07-17 13:58:16'),
(68, 701, '928785b3-af8d-4124-a889-a40fd4da9c85', 'Love', '2025-07-17 14:03:07'),
(69, 703, '97c65aee-3a27-4e0c-8067-0689f9e8609b', 'Like', '2025-08-10 08:26:16'),
(70, 702, '06bbe863-1300-4c11-ad83-dbd7b8fea2df', 'Love', '2025-08-17 01:55:29'),
(71, 702, 'b8780136-26c8-47f0-9a5a-aa8102aa2ec9', 'Love', '2025-08-18 01:53:45'),
(72, 702, '38409faf-bdb9-41ea-b96b-01bba8c6ec8a', 'Love', '2025-08-18 14:02:25'),
(73, 702, 'e4e2749d-34c0-45d5-9919-b417591fac7f', 'Love', '2025-08-21 12:32:44'),
(74, 712, 'f2191956-3576-48c8-90a9-c79d6062149f', 'Love', '2025-08-25 04:32:25'),
(75, 702, 'ff6854bb-c788-4533-9020-9f6dbb1323c3', 'Love', '2025-08-28 14:03:50'),
(76, 701, '7267ee5a-dee4-4fe6-8c50-76b1cf4ddbd0', 'Love', '2025-09-03 09:39:31'),
(77, 701, 'f07fdbc5-70aa-486b-815c-cafe9d97247d', 'Like', '2025-09-05 12:48:10'),
(78, 702, 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 'Love', '2025-09-17 14:44:05'),
(79, 701, 'eeb29eaf-4cc8-4271-9118-6fb69056337f', 'Love', '2025-11-09 01:01:05'),
(80, 702, 'f37a0222-83e9-4a91-9175-d7020d57c72b', 'Love', '2025-09-25 12:33:11'),
(81, 702, '62810936-01c5-4e39-aaec-fe563e84d26a', 'Like', '2025-10-05 10:00:16'),
(82, 702, '8f13c229-3152-40ed-bd8d-b33c2ea93c5d', 'Love', '2025-09-25 13:07:48'),
(83, 701, 'f2191956-3576-48c8-90a9-c79d6062149f', 'Love', '2025-10-04 12:03:44'),
(84, 702, 'c0ccfb5d-7717-4605-8ca3-346abd8ea2b5', 'Love', '2025-10-04 15:09:44'),
(85, 701, '764f512d-88e2-46e6-971f-f249b3966b27', 'Love', '2025-10-05 10:41:26'),
(86, 701, '129c8393-8d44-4f06-89fd-836ebe09bb72', 'Love', '2025-10-05 11:01:18'),
(87, 702, 'd4b0026a-f768-4d98-b0c8-2a17e27b7c61', 'Love', '2025-10-05 14:58:49'),
(88, 701, 'c0ccfb5d-7717-4605-8ca3-346abd8ea2b5', 'Haha', '2025-10-09 12:52:46'),
(89, 703, '60924fcc-8ecb-4b8c-b3b5-cabc51914858', 'Haha', '2025-10-25 01:31:26'),
(90, 701, '62810936-01c5-4e39-aaec-fe563e84d26a', 'Wow', '2025-10-25 01:41:55'),
(91, 701, 'af2aa354-4c34-40c6-b48f-aadaf67f38bc', 'Love', '2025-10-25 10:47:57'),
(92, 701, '2ba7cfb0-a32c-41d4-bc1a-dcec68309052', 'Haha', '2025-11-09 13:34:14'),
(93, 701, '684b81eb-0baa-4975-88f5-361d48feef46', 'Haha', '2025-11-12 12:40:58'),
(94, 701, 'd4b0026a-f768-4d98-b0c8-2a17e27b7c61', 'Love', '2025-11-12 12:41:44'),
(95, 702, '970ea541-a10d-4358-b006-1932fa7d2701', 'Love', '2025-11-15 07:04:51'),
(96, 701, '0dd91479-ad18-4fe1-9d31-368a4d3fd517', 'Wow', '2025-11-15 11:35:11'),
(97, 701, '970ea541-a10d-4358-b006-1932fa7d2701', 'Haha', '2025-11-23 09:16:49'),
(98, 701, '26e8fd68-87c0-4076-95bc-f402a3d77065', 'Love', '2025-12-19 15:15:51'),
(99, 701, 'dabfdd4a-0bf1-4ba3-94ff-65eae91e0301', 'Love', '2026-02-17 15:23:12'),
(100, 702, '19334c16-5a86-4318-8854-8e2438ec1b38', 'Love', '2026-11-16 03:34:21'),
(101, 702, 'c425ecc6-0ca2-4ac3-857e-2d1690b48160', 'Love', '2026-11-16 04:05:40'),
(102, 702, '0fd055d0-49a9-4877-a102-bc8aeebbc7dc', 'Love', '2026-11-16 04:23:33');

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
) ENGINE=InnoDB AUTO_INCREMENT=403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `code`, `fname`, `lname`, `mname`, `fullname`, `contact_no`, `age`, `email`, `profession`, `company`, `industry`, `companywebsite`, `role_code`, `designation`, `coverphoto`, `date_birth`, `home_country`, `current_location`, `profile_picture`, `resumepdf`, `h1_fname`, `h1_lname`, `h1_mname`, `h1_fullname`, `h1_contact_no`, `h1_email`, `h1_address1`, `h1_address2`, `h1_city`, `h1_province`, `h1_postal_code`, `h1_companycode`, `h1_rolecode`, `h1_designation`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(89, 701, 'Pedro', 'yorpo', '', 'Pedro yorpo', '+639999990909', NULL, 'pedroyorpo17@gmail.com', 'programmer', 'ABC Company', 'Civil Services (Government, Armed Forces)', 'nexsuz.com', 'DEF-MASTERADMIN', 'position', 'http://localhost:8000/storage/uploads/701/coverphoto/1df5d1df-1aed-4a89-b441-41fb95d4edc1/1795099544.jpg', '2000-08-29', 'USA', 'Canada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-27 05:28:16', '2026-11-21 16:24:45'),
(90, 702, 'Pedro', 'Yorpo', '', 'Pedro Yorpo', '09072493545', '1', 'pedroyorpo22@gmail.com', 'programmer', NULL, NULL, NULL, 'DEF-USERS', NULL, 'http://localhost:8000/storage/uploads/702/coverphoto/a8d0f601-eba7-4376-960d-cf1e6b87da34/1794806562.jpg', '1999-09-01', 'USA', 'Romania', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-27 06:10:49', '2026-11-19 07:09:30'),
(91, 703, 'Elizabeth', 'Punay', '', 'Elizabeth Punay', '+639994589906', '1', 'elizabethpunay01@gmail.com', 'Teacher', 'ELIZABETH COMPANY', NULL, NULL, 'DEF-CLIENT', NULL, 'http://localhost:8000/storage/uploads/703/coverphoto/12798cfb-408b-403c-8acb-1ef2aeeec0a6/1795102709.jpg', '2000-08-26', 'Philippines', 'Philippines', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-27 06:18:12', '2026-11-19 07:38:29'),
(93, 705, 'David', 'Dela Cruz', '', 'David Dela Cruz', '09172581339', '1', 'dhave.cdc83@gmail.com', 'Recruitment Manager', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', '1983-11-13', 'Philippines', 'China', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-28 16:36:59', '2025-09-15 13:37:39'),
(95, 707, 'David', 'Dela Cruz', '', 'David Dela Cruz', '+8613061767765', NULL, 'manpower@hraintl.com', NULL, 'HRA International', 'Human Resources Management/Consultancy', 'hraintl.com', 'DEF-CLIENT', 'Recruitment Manager', 'https://exploredition.com/storage/app/public/uploads/707/coverphoto/65a1dde5-85c0-41a6-8539-970caa70069a/1770113637.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-28 18:22:55', '2026-02-03 10:13:57'),
(97, 709, 'Angel', 'Angeles', '', 'Angel Angeles', '+639052237858', '1', 'strategichrtaprofessional@gmail.com', 'Regional Manager', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-30 04:03:52', '2024-11-30 04:03:52'),
(98, 710, 'Francis', 'Angeles', '', 'Francis Angeles', '+8615900603216', '1', 'francis.angeles710@gmail.com', 'Regional Manager', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-11-30 04:14:40', '2024-11-30 04:14:40'),
(99, 711, 'human$', 'crazy', '', 'Human$ crazy', '1234567890', NULL, 'reinjunelaride34@gmail.com', NULL, 'VFI', 'Laboratory', 'mail.com', 'DEF-MASTERADMIN', 'PROFESSIONAL', 'default.jpg', '2025-03-14', 'Canada', 'Canada', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2024-12-01 21:12:05', '2025-03-01 08:33:40'),
(101, 713, 'STEVEN', 'YORPO', '', 'STEVEN YORPO', '+709922072861', '1', 'stevenyorpo23@gmail.com', 'Student', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-03-01 14:43:52', '2025-03-01 14:43:52'),
(102, 714, 'TJ', 'Kim', '', 'TJ Kim', '+8618321452737', NULL, 'kimtaejung@163.com', NULL, 'Nexsuz China', 'Human Resources Management/Consultancy', 'nexsuz.com', 'DEF-CLIENT', 'CEO', 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-20 06:39:59', '2025-09-20 06:39:59'),
(103, 715, 'MARLON', 'MANIGOS', '', 'MARLON MANIGOS', '+709651604248', '1', 'marlonmanigos@gmail.com', 'POLICE MAN', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 03:44:36', '2025-10-15 03:44:36'),
(104, 716, 'MARLON', 'MANIGOS', '', 'MARLON MANIGOS', '+2009651604248', '1', 'marlonmanigos07@gmail.com', 'CRIMININOLOGY', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-15 03:51:25', '2025-10-15 03:51:25'),
(106, 717, 'test', 'test', '', 'Test test', '+639169725420', '1', 'grantarts7@gmail.com', '3d artist', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', '2001-12-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-30 12:44:43', '2025-10-30 12:51:09'),
(111, 718, 'Nexsuz User', '-', '', 'Nexsuz User -', '+639938151373', '1', 'nexsuz.official13@gmail.com', 'Nexsuz', NULL, NULL, NULL, 'DEF-ADMIN', NULL, 'https://exploredition.com/storage/app/public/uploads/718/coverphoto/13eba524-7d6a-4e53-b784-85ad728f8d52/1765201178.PNG', '2000-10-17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-30 14:36:47', '2025-12-08 13:39:38'),
(116, 719, 'Leah', 'Yorpo', '', 'Leah Yorpo', '+639460994512', '1', 'leahyorpo17@gmail.com', 'BS AGRI CULTURE', NULL, NULL, NULL, 'DEF-USERS', NULL, 'default.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-24 02:35:14', '2025-12-24 02:35:14');

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
(280, 'DEF-MASTERADMIN', 125, 41, NULL, NULL, '2025-12-24 04:22:45', '2025-12-24 04:22:45'),
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
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(145, 'DEF-MASTERADMIN', 125, 19, NULL, NULL, '2026-05-30 20:26:40', '2026-05-30 20:26:40'),
(146, 'DEF-MASTERADMIN', 125, 20, NULL, NULL, '2026-05-30 20:26:40', '2026-05-30 20:26:40'),
(148, 'DEF-MASTERADMIN', 125, 21, NULL, NULL, '2026-05-30 21:08:08', '2026-05-30 21:08:08'),
(149, 'DEF-ADMIN', 129, 22, NULL, NULL, '2026-06-08 05:00:47', '2026-06-08 05:00:47'),
(150, 'DEF-MASTERADMIN', 125, 22, NULL, NULL, '2026-06-08 05:01:17', '2026-06-08 05:01:17'),
(151, 'DEF-MASTERADMIN', 125, 23, NULL, NULL, '2026-06-20 23:24:57', '2026-06-20 23:24:57'),
(152, 'DEF-ADMIN', 129, 23, NULL, NULL, '2026-06-20 23:25:07', '2026-06-20 23:25:07'),
(153, 'DEF-MASTERADMIN', 10, 24, NULL, NULL, '2026-11-19 07:18:21', '2026-11-19 07:18:21'),
(154, 'DEF-CLIENT', 136, 24, NULL, NULL, '2026-11-19 07:37:22', '2026-11-19 07:37:22'),
(155, 'DEF-MASTERADMIN', 10, 25, NULL, NULL, '2026-11-21 17:22:12', '2026-11-21 17:22:12');

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
) ENGINE=MyISAM AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submenus`
--

INSERT INTO `submenus` (`id`, `transNo`, `desc_code`, `description`, `icon`, `class`, `routes`, `sort`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 2, 'top_navigation', 'Security roles', 'icon-security', 'class-security', 'security', 1, 'A', NULL, NULL, NULL, NULL),
(2, 2, 'top_navigation', 'Users', 'icon-user', 'class-user', 'user', 2, 'A', NULL, NULL, NULL, NULL),
(3, 2, 'top_navigation', 'Menus', 'icon-menu', 'class-menu', 'menu', 3, 'A', NULL, NULL, NULL, NULL),
(4, 2, 'top_navigation', 'Roles', 'icon-role', 'class-role', 'role', 4, 'A', NULL, NULL, NULL, NULL),
(19, 25, 'top_navigation', 'User Plan', '-', '-', 'userplan', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL),
(20, 25, 'top_navigation', 'Client Plan', '-', '-', 'clientplan', 2, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-19 08:38:43'),
(21, 25, 'top_navigation', 'Plan Category', '-', '-', 'plancategory', 3, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-19 08:44:00'),
(22, 25, 'top_navigation', 'Features UserPlan', '-', '-', 'featuresplan', 1, 'A', 'Pedro yorpo', 'system', NULL, '2026-06-20 23:23:27'),
(23, 25, 'top_navigation', 'Features ClientPlan', '-', '-', 'features_clientplan', 1, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-19 08:41:01'),
(24, 2, 'top_navigation', 'Company Information', '-', '-', 'company', 1, 'I', 'Pedro yorpo', 'system', NULL, '2026-11-21 17:05:19'),
(25, 2, 'top_navigation', 'Database', 'database', '-', 'database', 1, 'A', 'Pedro yorpo', 'Pedro yorpo', NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(30, '1-DAY_PROFILE', '1-Day Profile Boost per Month', 'Profile', 'star', NULL, 'toggle', 1, '2026-11-21 16:35:28', '2026-11-21 16:35:28');

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usercapabilities`
--

INSERT INTO `usercapabilities` (`id`, `code`, `transNo`, `language`, `created_at`, `updated_at`) VALUES
(1, 702, 1, 'English', '2025-08-10 02:07:38', '2025-08-10 02:07:38'),
(2, 702, 2, 'Tagalog', '2025-08-10 02:07:38', '2025-08-10 02:07:38'),
(3, 712, 1, 'Afrikaans', '2025-08-10 03:58:04', '2025-08-10 03:58:04'),
(4, 712, 2, 'Albanian', '2025-08-10 03:58:04', '2025-08-10 03:58:04'),
(5, 703, 1, 'Afrikaans', '2025-08-10 04:09:01', '2025-08-10 04:09:01'),
(6, 703, 2, 'Albanian', '2025-08-10 04:09:01', '2025-08-10 04:09:01'),
(10, 702, 4, 'Amharic', '2025-09-21 13:12:52', '2025-09-21 13:12:52'),
(11, 702, 5, 'Armenian', '2025-09-21 13:12:52', '2025-09-21 13:12:52'),
(12, 702, 6, 'Chinese', '2025-09-21 13:12:52', '2025-09-21 13:12:52'),
(13, 713, 1, 'Filipino', '2025-10-18 01:56:47', '2025-10-18 01:56:47'),
(14, 717, 1, 'English', '2025-10-30 12:48:04', '2025-10-30 12:48:04'),
(15, 718, 1, 'Afrikaans', '2025-10-30 14:45:49', '2025-10-30 14:45:49'),
(16, 718, 2, 'Amharic', '2025-10-30 14:45:49', '2025-10-30 14:45:49');

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usercertificates`
--

INSERT INTO `usercertificates` (`id`, `code`, `transNo`, `certificate_title`, `certificate_provider`, `date_completed`, `created_at`, `updated_at`) VALUES
(2, '702', 2, 'Service Anniversary (2 years)', 'Mariosoft Solutions', '2024-09-28', '2025-08-10 02:22:15', '2025-09-20 14:59:10'),
(3, '712', 1, 'zx', 'zx', '2025-08-29', '2025-08-10 03:59:05', '2025-08-10 03:59:05'),
(4, '703', 1, 'xc', 'xc', '2025-08-30', '2025-08-10 04:54:43', '2025-08-10 04:54:43'),
(5, '705', 1, 'TEST', 'TEST', '2025-09-16', '2025-09-15 13:45:11', '2025-09-15 13:45:11'),
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usereducations`
--

INSERT INTO `usereducations` (`id`, `code`, `transNo`, `highest_education`, `school_name`, `start_month`, `start_year`, `end_month`, `end_year`, `status`, `created_at`, `updated_at`) VALUES
(2, 712, 1, 'asc', 'zx', 'February', 2001, 'February', 2001, 'ongoing', '2025-08-10 03:58:33', '2025-08-10 03:58:33'),
(3, 703, 1, 'xc', 'xc', 'February', 2001, 'February', 2001, 'undergraduate', '2025-08-10 04:53:05', '2025-08-10 04:53:05'),
(4, 705, 1, 'TEST', 'TEST', 'January', 2001, 'February', 2004, 'graduate', '2025-09-15 13:38:42', '2025-09-15 13:38:42'),
(5, 705, 2, 'TEST 2', 'TEST 2', 'February', 2001, 'May', 2005, 'graduate', '2025-09-15 13:38:42', '2025-09-15 13:38:42'),
(9, 702, 5, 'Bachelor of Science in Computer Science', 'University of Iloilo', 'January', 2025, 'April', 2025, 'graduate', '2025-09-20 13:14:28', '2026-04-28 23:29:26'),
(12, 718, 1, 'TEST', 'TEST', 'February', 2002, 'February', 2008, 'graduate', '2025-10-30 14:39:43', '2025-10-30 14:39:43');

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `useremploymentrecords`
--

INSERT INTO `useremploymentrecords` (`id`, `code`, `transNo`, `company_name`, `position`, `job_description`, `date_completed`, `created_at`, `updated_at`) VALUES
(2, '702', 2, 'Mariosoft Solutions test', 'Software Engineering', 'Experience 2 years Angular typescript and Laravel for Maintain the Company System in Mariosoft Solutions', '2025-08-06', '2025-08-10 02:25:27', '2025-09-20 05:40:49'),
(3, '712', 1, 'zx', 'zx', 'zx', '2025-08-29', '2025-08-10 03:59:14', '2025-08-10 03:59:14'),
(4, '703', 1, 'sdv', 'sdv', 'sdv', '2025-08-30', '2025-08-10 05:40:08', '2025-08-10 05:40:08'),
(6, '705', 1, 'TEST', 'TEST', 'TEST', '2025-09-15', '2025-09-15 13:46:20', '2025-09-15 13:46:20'),
(10, '718', 1, 'SDV', 'SDV', 'SDV', '2025-10-08', '2025-10-30 14:41:19', '2025-10-30 14:41:19');

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
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `userplandetails`
--

INSERT INTO `userplandetails` (`id`, `planId`, `plan_name`, `fid`, `feature_name`, `recordStatus`, `created_at`, `updated_at`, `feature_code`, `feature_value`, `feature_type`, `button_name`) VALUES
(5, 'PLN000001', 'Free', 'FID000005', 'Apply to Jobs', 'active', '2026-06-05 22:28:42', '2026-06-26 16:24:41', 'APPLY_JOBS', 'YES', 'Everything in Free', ''),
(4, 'PLN000001', 'Free', 'FID000004', 'Create Resume', 'active', '2026-06-05 22:28:36', '2026-11-21 09:32:36', 'CREATE_RESUME', 'NO', 'Everything in Free', ''),
(3, 'PLN000001', 'Free', 'FID000001', 'Create Profile', 'active', '2026-06-05 22:22:48', '2026-06-20 23:04:47', 'CREATE_PROFILE', 'YES', 'Everything in Free', ''),
(7, 'PLN000001', 'Free', 'FID000007', 'Join Networking Feed', 'active', '2026-06-05 22:28:59', '2026-06-05 22:28:59', 'NETWORK_FEED', 'YES', 'Everything in Free', ''),
(8, 'PLN000001', 'Free', 'FID000008', 'Receive Job Alerts', 'active', '2026-06-05 22:29:07', '2026-06-20 21:34:09', 'JOB_ALERTS', 'YES', 'Everything in Free', ''),
(9, 'PLN000001', 'Free', 'FID000009', '15 Connections Limit', 'active', '2026-06-05 22:29:17', '2026-06-05 22:29:17', 'CONNECTION_LIMIT', '15', 'Everything in Free', ''),
(11, 'PLN000001', 'Free', 'FID000010', 'Restricted Search Access', 'active', '2026-06-05 22:31:16', '2026-07-23 05:26:25', 'SEARCH_ACCESS', 'NO', 'Everything in Free', ''),
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
(47, 'PLN000004', 'Business Unlimited', 'FID000047', 'Custom Recruitment Workflow', 'active', '2026-06-06 01:13:22', '2026-06-06 01:13:22', NULL, NULL, 'Business Unlimited', '');

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
) ENGINE=MyISAM AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `userplanheader`
--

INSERT INTO `userplanheader` (`id`, `planId`, `plan_name`, `button_name`, `price`, `tagmonthYear`, `tag`, `button_color`, `description`, `created_at`, `sort_number`, `role_code`, `updated_at`, `recordStatus`, `plan_level`) VALUES
(5, 'PLN000002', 'Basic', 'Upgrade Basic', '199.00', 'Monthly', 'MOST POPULAR', 'accent', 'Ideal for professionals actively growing their career and connections.', '2026-06-05 22:25:58', 2, '\nDEF-USERS', '2026-11-21 17:12:04', 'active', 2),
(4, 'PLN000001', 'Free', 'Current Plan', '0.00', 'Monthly', 'STARTER', 'basic', 'Perfect for new users exploring jobs and networking opportunities.', '2026-06-05 21:17:02', 1, '\nDEF-USERS', '2026-11-21 17:02:23', 'active', 1),
(6, 'PLN000003', 'Premium', 'Go Premium', '499.00', 'Monthly', 'PREMIUM', 'primary', 'Premium career tools for serious professionals and top candidates.', '2026-06-05 22:26:28', 3, '\nDEF-USERS', '2026-11-21 17:12:06', 'active', 3),
(7, 'PLN000004', 'Business Unlimited', 'Go Business Unlimited', '999.00', 'Monthly', 'BUSINESS', 'warn', 'Full enterprise solution for companies, recruiters, and large organizations.', '2026-06-06 01:04:20', 4, '\nDEF-USERS', '2026-11-21 17:12:06', 'active', 4);

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
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userprofiles`
--

INSERT INTO `userprofiles` (`id`, `code`, `transNo`, `photo_pic`, `contact_no`, `contact_visibility`, `email`, `email_visibility`, `summary`, `date_birth`, `home_country`, `home_state`, `current_location`, `current_state`, `created_at`, `updated_at`) VALUES
(1, 702, 1, 'http://localhost:8000/storage/uploads/702/cvphoto/eea72145-915f-425c-8a57-b174cb88a7ce/1795010366.jpg', '09072493545', 1, 'pedroyorpo22@gmail.com', 1, 'My skilled detail-oriented Web Developer with over 2 years of experience in designing, developing, and\r\nmaintaining responsive websites and web applications. Proficient in front-end like HTML, CSS, JavaScript, Angular, and back-end technologies.', '1999-09-01', 'Philippines', 'Negros Occidental', 'Philippines', 'Cordova, Cebu City', '2025-08-10 02:08:59', '2026-11-19 07:09:30'),
(2, 701, 2, 'http://localhost:8000/storage/uploads/701/cvphoto/c70d2c93-0e95-4aec-95da-32052cd69bbd/1795099561.jpg', '+639999990909', 0, 'pedroyorpo17@gmail.com', 0, 'asc', '2000-08-29', 'Canada', 'CORDOVA', 'USA', 'LAPU-LAPU CITY', '2025-08-10 03:19:09', '2026-11-21 16:24:45'),
(8, 703, 4, 'http://localhost:8000/storage/uploads/703/cvphoto/aabaf4e1-dc45-4dbd-8b7b-f7eef51ee55f/1795102702.jpg', '+639994589906', 0, 'elizabethpunay01@gmail.com', 0, 'sdb', '2000-08-26', 'Canada', 'sdb', 'Russia', 'db', '2025-08-10 08:06:21', '2026-11-19 07:38:22'),
(9, 705, 5, 'https://exploredition.com/storage/app/public/uploads/705/cvphoto/cd6d2c6a-5fe9-4ecc-9c73-f7489453afe3/1757944617.jfif', '09172581339', 0, 'dhave.cdc83@gmail.com', 0, 'Individuals who are over 18 years old are legally recognized as adults in most parts of the world. This age generally marks the transition from adolescence into adulthood.', '1983-11-13', 'Philippines', 'Manila', 'China', 'Shanghai', '2025-09-15 13:33:57', '2025-09-15 13:56:57'),
(10, 717, 6, 'https://exploredition.com/storage/app/public/uploads/717/cvphoto/6e6b2c2b-ecd8-4da4-95ec-101bd4859bfb/1761828669.jpg', '+639169725420', 0, 'grantarts7@gmail.com', 0, 'Hi there.', '2001-12-15', 'Philippines', 'Cebu City', 'Philippines', 'Cebu City', '2025-10-30 12:48:23', '2025-10-30 12:51:09'),
(20, 718, 7, 'https://exploredition.com/storage/app/public/uploads/718/cvphoto/9320e6c0-7673-45fc-8eee-6acd7ae03239/1763882516.jpg', '+639938151373', 0, 'nexsuz.official13@gmail.com', 0, 'avvsdvsdv', '2000-10-17', 'USA', 'asc', 'Russia', 'ascasc', '2025-10-30 14:48:16', '2025-12-08 13:38:18');

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
) ENGINE=InnoDB AUTO_INCREMENT=3741 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `coverphoto`, `fname`, `lname`, `mname`, `contactno`, `fullname`, `email`, `email_verified_at`, `password`, `status`, `company`, `code`, `role_code`, `remember_token`, `created_at`, `updated_at`, `planId`, `is_online`) VALUES
(91, 'http://localhost:8000/storage/uploads/701/coverphoto/1df5d1df-1aed-4a89-b441-41fb95d4edc1/1795099544.jpg', 'Pedro', 'yorpo', '', '+639999990909', 'Pedro yorpo', 'pedroyorpo17@gmail.com', NULL, '$2y$10$odneo31WuVN0Dke2xxhLiOyHPfP3NjV4aPLHseSNfPn.M13QNOPca', 'A', 'ABC Company', 701, 'DEF-MASTERADMIN', NULL, '2024-11-27 05:28:16', '2026-11-21 09:29:12', 'PLN000003', 1),
(92, 'http://localhost:8000/storage/uploads/702/coverphoto/a8d0f601-eba7-4376-960d-cf1e6b87da34/1794806562.jpg', 'Pedro', 'Yorpo', '', '+6392999990909', 'Pedro Yorpo', 'pedroyorpo22@gmail.com', NULL, '$2y$10$46mxTl7bYjF64.53JQvDaOUWKPAKzbT.ZrqNEHokjkCxvg38HTdlC', 'A', NULL, 702, 'DEF-USERS', NULL, '2024-11-27 06:10:49', '2026-11-21 08:51:33', 'PLN000003', 1),
(93, 'http://localhost:8000/storage/uploads/703/coverphoto/12798cfb-408b-403c-8acb-1ef2aeeec0a6/1795102709.jpg', 'Elizabeth', 'Punay', '', '+639994589906', 'Elizabeth Punay', 'elizabethpunay01@gmail.com', NULL, '$2y$10$xdg7I8F3IRI/2LGXW6g2E.sT88ZESVVjPQoDyJT6qp3DBmd6BMWve', 'A', 'ELIZABETH Company', 703, 'DEF-CLIENT', NULL, '2024-11-27 06:18:12', '2026-11-21 09:29:03', '', 0),
(95, '', 'David', 'Dela Cruz', '', '+8613061767765', 'David Dela Cruz', 'dhave.cdc83@gmail.com', NULL, '$2y$10$rCQENOd6BXXyRkTSH68hj.Gfp47r7lcUMpR5gmTrKcfQ8JMrDUMNG', 'A', NULL, 705, 'DEF-USERS', NULL, '2024-11-28 16:36:59', '2026-11-19 08:16:14', 'PLN000003', 0),
(98, 'https://exploredition.com/storage/app/public/uploads/707/coverphoto/65a1dde5-85c0-41a6-8539-970caa70069a/1770113637.jpg', 'David', 'Dela Cruz', '', '+8613061767765', 'David Dela Cruz', 'manpower@hraintl.com', NULL, '$2y$10$bK1MPbNvL4zM3dUqroLRR.vmEz284DU9RMbO14uypSLi3iEsITfVa', 'A', 'HRA International', 707, 'DEF-CLIENT', NULL, '2024-11-28 18:22:55', '2026-02-03 10:17:17', '', 0),
(100, '', 'Angel', 'Angeles', '', '+639052237858', 'Angel Angeles', 'strategichrtaprofessional@gmail.com', NULL, '$2y$10$ovrH1.Nlrevb3I.1mD.NyumDnwSLKGhjTP/I/Ht..p52iOHyxyQ9O', 'I', NULL, 709, 'DEF-USERS', NULL, '2024-11-30 04:03:52', '2024-11-30 04:03:52', 'PLN000003', 0),
(101, '', 'Francis', 'Angeles', '', '+8615900603216', 'Francis Angeles', 'francis.angeles710@gmail.com', NULL, '$2y$10$DbdXNBNO/KuKiWANGrET5.RIdNPKEUNV74FNlV1GPJpZNTwTRJEQ6', 'A', NULL, 710, 'DEF-USERS', NULL, '2024-11-30 04:14:40', '2024-11-30 04:15:52', 'PLN000003', 0),
(102, '', 'human$', 'crazy', '', '+639453570677', 'Human$ crazy', 'reinjunelaride34@gmail.com', NULL, '$2y$10$u3WRh3ltKE/k6G8JSgavzeRsiWEFpv6X0ADq9GX8PykPxgwQSbya6', 'A', 'VFI', 711, 'DEF-MASTERADMIN', NULL, '2024-12-01 21:12:05', '2026-11-19 08:16:14', '', 0),
(103, '', 'Admin', 'test', '', '+6399072493545', 'Nexsuz', 'nexsuz.official13X@gmail.com', NULL, '$2y$10$odneo31WuVN0Dke2xxhLiOyHPfP3NjV4aPLHseSNfPn.M13QNOPca', 'I', NULL, 712, 'DEF-CLIENT', NULL, '2025-01-17 16:59:15', '2025-10-08 14:12:33', '', 0),
(104, '', 'STEVEN', 'YORPO', '', '+709922072861', 'STEVEN YORPO', 'stevenyorpo23@gmail.com', NULL, '$2y$10$9kDeTg4jZ4nyQFeoAR6YUeJqCy.jm2703LxSYpx7zwEda8a5GU6Ye', 'A', NULL, 713, 'DEF-USERS', NULL, '2025-03-01 14:43:52', '2025-10-18 01:55:44', 'PLN000003', 1),
(108, '', 'TJ', 'Kim', '', '+8618321452737', 'TJ Kim', 'kimtaejung@163.com', NULL, '$2y$10$ebx/Ys7Rgwo/VaxNZZUhRe2/00RsdeKxw8AhmVp72Shqw98F1Xewu', 'A', 'Nexsuz China', 714, 'DEF-ADMIN', NULL, '2025-09-20 06:39:59', '2025-09-20 06:50:37', '', 0),
(109, '', 'MARLON', 'MANIGOS', '', '+709651604248', 'MARLON MANIGOS', 'marlonmanigos@gmail.com', NULL, '$2y$10$W4Glg0/Pi9dvObzYeouQjubdGd47Cq8mgGDfd9RCSmK62tzbuj0yq', 'I', NULL, 715, 'DEF-USERS', NULL, '2025-10-15 03:44:36', '2026-11-19 08:19:07', 'PLN000003', 0),
(112, '', 'MARLON', 'MANIGOS', '', '+2009651604248', 'MARLON MANIGOS', 'marlonmanigos07@gmail.com', NULL, '$2y$10$lV99G6K1rbxNjySs4chZeOadcud3QNTGq2PypmCtzUicw3OE.iY9e', 'A', NULL, 716, 'DEF-USERS', NULL, '2025-10-15 03:51:25', '2025-10-16 22:24:36', 'PLN000003', 0),
(136, 'default.jpg', 'test', 'test', '', '+639169725420', 'Test test', 'grantarts7@gmail.com', NULL, '$2y$10$j11EerkYG0102YEN3x562Od.jm4Ag2Gpw6rzMxovdQqQqdOjPmJyS', 'A', NULL, 717, 'DEF-USERS', NULL, '2025-10-30 12:44:43', '2025-10-30 12:45:55', 'PLN000003', 1),
(141, 'https://exploredition.com/storage/app/public/uploads/718/coverphoto/13eba524-7d6a-4e53-b784-85ad728f8d52/1765201178.PNG', 'Nexsuz User', '-', '', '+639938151373', 'Nexsuz User -', 'nexsuz.official13@gmail.com', NULL, '$2y$10$odneo31WuVN0Dke2xxhLiOyHPfP3NjV4aPLHseSNfPn.M13QNOPca', 'A', NULL, 718, 'DEF-ADMIN', NULL, '2025-10-30 14:36:47', '2026-06-20 22:53:51', '', 1),
(146, 'default.jpg', 'Leah', 'Yorpo', '', '+639460994512', 'Leah Yorpo', 'leahyorpo17@gmail.com', NULL, '$2y$10$6oqK035FYgT29EFh77vv0OtHvbWwBBAq2OqTnH1APcLOqCq6Zjoli', 'A', NULL, 719, 'DEF-USERS', NULL, '2025-12-24 02:35:14', '2026-11-19 08:19:13', 'PLN000003', 0);

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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userseminars`
--

INSERT INTO `userseminars` (`id`, `code`, `transNo`, `seminar_title`, `seminar_provider`, `date_completed`, `created_at`, `updated_at`) VALUES
(2, '712', 1, 'xz', 'zx', '2025-08-30', '2025-08-10 03:58:49', '2025-08-10 03:58:49'),
(4, '703', 2, 'sdv', 'sdv', '2025-08-26', '2025-08-10 04:54:00', '2025-08-10 04:54:00'),
(5, '705', 1, 'SEMINAR', 'TEST', '2025-09-27', '2025-09-15 13:40:02', '2025-09-15 13:40:02'),
(25, '702', 1, 'sdc', 'scsd', '2025-10-04', '2025-10-04 06:35:45', '2025-10-04 06:35:45'),
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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `userskills`
--

INSERT INTO `userskills` (`id`, `code`, `transNo`, `skills`, `created_at`, `updated_at`) VALUES
(3, '712', 1, 'Network Configuration', '2025-08-10 03:58:41', '2025-08-10 03:58:41'),
(4, '712', 2, 'Network Protocols (TCP/IP, UDP, HTTP, DNS, etc.)', '2025-08-10 03:58:41', '2025-08-10 03:58:41'),
(6, '703', 2, 'Network Security', '2025-08-10 04:53:13', '2025-08-10 04:53:13'),
(8, '703', 4, 'Network Automation', '2025-08-10 04:53:34', '2025-08-10 04:53:34'),
(10, '705', 1, 'Network Configuration', '2025-09-15 13:39:37', '2025-09-15 13:39:37'),
(11, '705', 2, 'Network Protocols (TCP/IP, UDP, HTTP, DNS, etc.)', '2025-09-15 13:39:37', '2025-09-15 13:39:37'),
(12, '705', 3, 'Network Security', '2025-09-15 13:39:37', '2025-09-15 13:39:37'),
(29, '702', 1, 'Network Protocols (TCP/IP, UDP, HTTP, DNS, etc.)', '2025-10-04 06:35:06', '2025-10-04 06:35:06'),
(30, '702', 2, 'Network Configuration', '2025-10-04 06:35:06', '2025-10-04 06:35:06'),
(31, '718', 1, 'Network Protocols (TCP/IP, UDP, HTTP, DNS, etc.)', '2025-10-30 14:39:58', '2025-10-30 14:39:58'),
(32, '718', 2, 'Network Configuration', '2025-10-30 14:39:58', '2025-10-30 14:39:58'),
(33, '701', 1, 'Network Configuration', '2026-06-11 20:19:42', '2026-06-11 20:19:42');

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
) ENGINE=MyISAM AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usersubscription`
--

INSERT INTO `usersubscription` (`id`, `transNo`, `planId`, `plan_name`, `amount`, `payment_method`, `start_date`, `end_date`, `cancelled_at`, `remarks`, `code`, `is_active`, `created_at`, `updated_at`) VALUES
(66, 'SUB000067', 'PLN000003', 'Premium', '499.00', 'PayPal', '2026-08-09 14:51:06', '2026-09-09 14:51:06', NULL, NULL, '702', 'expired', '2026-08-09 06:51:06', '2026-11-15 18:09:21'),
(65, 'SUB000066', 'PLN000002', 'Basic', '199.00', 'GCash', '2026-08-02 13:29:05', '2026-09-02 13:29:05', NULL, NULL, '702', 'expired', '2026-08-02 05:29:05', '2026-08-09 06:51:06'),
(64, 'SUB000065', 'PLN000001', 'Free', '0.00', 'Free', '2026-07-19 03:27:34', '2026-08-19 03:27:34', NULL, NULL, '702', 'expired', '2026-07-18 19:27:34', '2026-08-02 05:29:05'),
(67, 'SUB000068', 'PLN000004', 'Business Unlimited', '999.00', 'GCash', '2026-11-16 02:09:21', '2026-12-16 02:09:21', NULL, NULL, '702', 'expired', '2026-11-15 18:09:21', '2026-11-19 07:01:51'),
(69, 'SUB000070', 'PLN000001', 'Free', '0.00', 'ADMIN_ASSIGNED', '2026-11-19 15:01:51', '2026-12-19 15:01:51', NULL, NULL, '702', 'expired', '2026-11-19 07:01:51', '2026-11-21 16:46:51'),
(70, 'SUB000071', 'PLN000001', 'Free', '0.00', 'ADMIN_ASSIGNED', '2026-11-19 15:24:23', '2026-12-19 15:24:23', NULL, NULL, '703', 'active', '2026-11-19 07:24:23', '2026-11-19 07:24:23'),
(85, 'SUB000078', 'PLN000001', 'Free', '0.00', 'ADMIN_ASSIGNED', '2026-11-22 00:46:59', '2026-12-22 00:46:59', NULL, 'Assigned by 701.', '702', 'active', '2026-11-21 16:46:59', '2026-11-21 16:46:59'),
(84, 'SUB000077', 'PLN000002', 'Basic', '0.00', 'ADMIN_ASSIGNED', '2026-11-22 00:46:51', '2026-12-22 00:46:51', NULL, 'Assigned by 701.', '702', 'expired', '2026-11-21 16:46:51', '2026-11-21 16:46:59');

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
) ENGINE=MyISAM AUTO_INCREMENT=1459 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usersubscription_details`
--

INSERT INTO `usersubscription_details` (`id`, `transNo`, `code`, `planId`, `plan_name`, `fid`, `feature_code`, `feature_name`, `feature_value`, `created_at`, `updated_at`, `is_active`) VALUES
(874, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000035', '1-DAY_PROFILE', '1-Day Profile Boost per Month', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(873, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000034', 'EARLY_ACCESS_JOBS', 'Early Access to New Jobs', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(872, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000033', 'RECRUITER_DIRECT_ACCESS', 'Recruiter Direct Access', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(871, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000032', 'ACCESS_TO_PREMIUM', 'Access to Premium Networking Events', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(870, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000031', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(869, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000030', 'AI_INTERVIEW_PREPARATION', 'AI Interview Preparation', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(868, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000029', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(867, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000028', 'VERIFIED_PREMIUM_BADGE', 'Verified Premium Badge', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(865, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000026', 'PROFILE_BOOSTING', 'Profile Boosting', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(866, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000027', 'APPLICANT_PLACEMENT', 'Top Applicant Placement', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(863, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000024', 'EVERYTHING_BASIC', 'Everything in Basic', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(864, 'SUB000067', '702', 'PLN000003', 'Premium', 'FID000025', 'UNLIMITED_CONNECTIONS', 'Unlimited Connections', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(860, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000021', 'JOB_RECOMMENDATIONS', 'AI Job Recommendations', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(861, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000022', 'APPLICANT_BADGE', 'Featured Applicant Badge', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(862, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000023', 'PRIORITY_APPLICATION', 'Priority Application Queue', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(858, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000019', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(859, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000020', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(856, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000016', 'UNLIMITED_JOB', 'Unlimited Job Applications', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(857, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000018', 'UNLIMITED_MESSAGING', 'Unlimited Messaging', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(854, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000017', 'ADVANCED_JOB_SEARCH', 'Advanced Job Search Filters', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(855, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000015', '100_CONNECTIONS_LIMIT', '100 Connections Limit', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(853, 'SUB000067', '702', 'PLN000002', 'Basic', 'FID000013', 'EVERYTHING_FREE', 'Everything in Free', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(852, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(851, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(849, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(850, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(848, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(847, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(842, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000021', 'JOB_RECOMMENDATIONS', 'AI Job Recommendations', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(843, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000022', 'APPLICANT_BADGE', 'Featured Applicant Badge', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(846, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(845, 'SUB000067', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-08-09 06:51:06', '2026-11-15 18:09:21', 'expired'),
(844, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000023', 'PRIORITY_APPLICATION', 'Priority Application Queue', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(841, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000020', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(839, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000018', 'UNLIMITED_MESSAGING', 'Unlimited Messaging', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(840, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000019', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(838, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000016', 'UNLIMITED_JOB', 'Unlimited Job Applications', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(835, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000013', 'EVERYTHING_FREE', 'Everything in Free', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(837, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000015', '100_CONNECTIONS_LIMIT', '100 Connections Limit', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(836, 'SUB000066', '702', 'PLN000002', 'Basic', 'FID000017', 'ADVANCED_JOB_SEARCH', 'Advanced Job Search Filters', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(834, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(833, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(832, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(831, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(830, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(829, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(828, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(827, 'SUB000066', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-08-02 05:29:05', '2026-08-09 06:51:06', 'expired'),
(826, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(825, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(824, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(823, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(822, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(821, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(820, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(819, 'SUB000065', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-07-18 19:27:34', '2026-08-02 05:29:05', 'expired'),
(875, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(876, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(877, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(878, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(879, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(880, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(881, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(882, 'SUB000068', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(883, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000013', 'EVERYTHING_FREE', 'Everything in Free', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(884, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000017', 'ADVANCED_JOB_SEARCH', 'Advanced Job Search Filters', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(885, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000015', '100_CONNECTIONS_LIMIT', '100 Connections Limit', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(886, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000016', 'UNLIMITED_JOB', 'Unlimited Job Applications', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(887, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000018', 'UNLIMITED_MESSAGING', 'Unlimited Messaging', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(888, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000019', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(889, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000020', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(890, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000021', 'JOB_RECOMMENDATIONS', 'AI Job Recommendations', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(891, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000022', 'APPLICANT_BADGE', 'Featured Applicant Badge', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(892, 'SUB000068', '702', 'PLN000002', 'Basic', 'FID000023', 'PRIORITY_APPLICATION', 'Priority Application Queue', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(893, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000024', 'EVERYTHING_BASIC', 'Everything in Basic', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(894, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000025', 'UNLIMITED_CONNECTIONS', 'Unlimited Connections', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(895, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000026', 'PROFILE_BOOSTING', 'Profile Boosting', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(896, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000027', 'APPLICANT_PLACEMENT', 'Top Applicant Placement', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(897, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000028', 'VERIFIED_PREMIUM_BADGE', 'Verified Premium Badge', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(898, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000029', 'AI_RESUME_OPTIMIZATION', 'AI Resume Optimization', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(899, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000030', 'AI_INTERVIEW_PREPARATION', 'AI Interview Preparation', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(900, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000031', 'CAREER_ANALYTICS_DASHBOARD', 'Career Analytics Dashboard', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(901, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000032', 'ACCESS_TO_PREMIUM', 'Access to Premium Networking Events', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(902, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000033', 'RECRUITER_DIRECT_ACCESS', 'Recruiter Direct Access', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(903, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000034', 'EARLY_ACCESS_JOBS', 'Early Access to New Jobs', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(904, 'SUB000068', '702', 'PLN000003', 'Premium', 'FID000035', '1-DAY_PROFILE', '1-Day Profile Boost per Month', 'YES', '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(905, 'SUB000068', '702', 'PLN000004', 'Business Unlimited', 'FID000036', '', 'Everything in Premium', NULL, '2026-11-15 18:09:21', '2026-11-19 07:01:51', 'expired'),
(936, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(937, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(938, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(939, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(940, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(941, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(942, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(943, 'SUB000070', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-19 07:01:51', '2026-11-21 16:46:51', 'expired'),
(944, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(945, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'YES', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(946, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(947, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(948, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(949, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(950, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(951, 'SUB000071', '703', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-19 07:24:23', '2026-11-19 07:24:23', 'active'),
(1241, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1239, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1240, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1238, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1237, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1236, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1235, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1234, 'SUB000078', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 16:46:59', '2026-11-21 16:46:59', 'active'),
(1233, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000023', 'PRIORITY_APPLICATION', 'Priority Application Queue', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1232, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000022', 'APPLICANT_BADGE', 'Featured Applicant Badge', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1231, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000021', 'JOB_RECOMMENDATIONS', 'AI Job Recommendations', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1229, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000019', 'PROFILE_INSIGHTS', 'Profile Insights', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1230, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000020', 'VIEWED_YOUR_PROFILE', 'See Who Viewed Your Profile', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1228, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000018', 'UNLIMITED_MESSAGING', 'Unlimited Messaging', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1227, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000016', 'UNLIMITED_JOB', 'Unlimited Job Applications', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1225, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000017', 'ADVANCED_JOB_SEARCH', 'Advanced Job Search Filters', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1226, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000015', '100_CONNECTIONS_LIMIT', '100 Connections Limit', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1224, 'SUB000077', '702', 'PLN000002', 'Basic', 'FID000013', 'EVERYTHING_FREE', 'Everything in Free', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1223, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000012', 'MESSAGING_ACCESS', 'Limited Messaging', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1222, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000010', 'SEARCH_ACCESS', 'Restricted Search Access', 'NO', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1221, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000009', 'CONNECTION_LIMIT', '15 Connections Limit', '15', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1220, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000008', 'JOB_ALERTS', 'Receive Job Alerts', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1219, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000007', 'NETWORK_FEED', 'Join Networking Feed', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1218, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000001', 'CREATE_PROFILE', 'Create Profile', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1217, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000004', 'CREATE_RESUME', 'Create Resume', 'NO', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired'),
(1216, 'SUB000077', '702', 'PLN000001', 'Free', 'FID000005', 'APPLY_JOBS', 'Apply to Jobs', 'YES', '2026-11-21 16:46:51', '2026-11-21 16:46:59', 'expired');

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usertrainings`
--

INSERT INTO `usertrainings` (`id`, `code`, `transNo`, `training_title`, `training_provider`, `date_completed`, `created_at`, `updated_at`) VALUES
(2, '712', 1, 'zx', 'zx', '2025-08-28', '2025-08-10 03:58:57', '2025-08-10 03:58:57'),
(4, '703', 2, 'sdv', 'sdv', '2025-08-29', '2025-08-10 04:54:20', '2025-08-10 04:54:20'),
(7, '705', 1, 'TEST', 'TEST', '2025-09-15', '2025-09-15 13:40:25', '2025-09-15 13:40:25'),
(8, '705', 2, 'TEST', 'TEST', '2025-09-16', '2025-09-15 13:40:59', '2025-09-15 13:40:59'),
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
) ENGINE=InnoDB AUTO_INCREMENT=206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`id`, `viewer_code`, `viewed_code`, `activity_type`, `timestamp`) VALUES
(188, 703, 701, 'view', '2025-11-22 14:12:45'),
(189, 703, 702, 'view', '2025-11-22 14:13:02'),
(191, 718, 702, 'view', '2025-11-23 07:31:14'),
(192, 718, 701, 'view', '2025-11-23 07:31:16'),
(193, 718, 705, 'view', '2025-12-09 14:07:22'),
(194, 718, 707, 'view', '2025-12-09 14:07:47');

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
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=316 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(71, 92, '{\"sound\": true, \"toast\": true, \"sound_volume\": 50, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"messages_enabled\": true, \"desktop_notifications\": true}', '2026-11-21 09:28:27', '2026-11-18 08:14:13', '2026-11-21 09:28:27'),
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
(315, 93, '{\"sound\": true, \"toast\": true, \"job_status\": true, \"job_reviews\": true, \"sound_volume\": 55, \"unread_badge\": true, \"post_comments\": true, \"friend_accepts\": true, \"job_interviews\": true, \"post_reactions\": true, \"friend_requests\": true, \"message_preview\": true, \"job_applications\": true, \"messages_enabled\": true, \"desktop_notifications\": false}', '2026-11-19 09:02:00', '2026-11-19 07:19:34', '2026-11-21 07:12:57');

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
