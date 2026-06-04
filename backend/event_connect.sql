-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2026 at 02:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event_connect`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('draft','active','ongoing','completed','cancelled') NOT NULL DEFAULT 'draft',
  `budget` decimal(15,2) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `expected_participants` int(11) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `name`, `description`, `location`, `start_date`, `end_date`, `start_time`, `end_time`, `status`, `budget`, `category`, `expected_participants`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Festival Budaya Nusantara 2026', 'Festival budaya tahunan yang menampilkan keberagaman seni dan budaya Indonesia dari Sabang sampai Merauke.', 'Lapangan Banteng, Jakarta Pusat', '2026-06-15', '2026-06-17', '08:00:00', '22:00:00', 'active', 500000000.00, 'Cultural', 5000, 2, '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(2, 'Tech Summit Indonesia 2026', 'Konferensi teknologi terbesar di Indonesia dengan pembicara dari perusahaan teknologi global.', 'Jakarta Convention Center', '2026-07-20', '2026-07-22', '09:00:00', '18:00:00', 'draft', 750000000.00, 'Conference', 2000, 2, '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(3, 'Gala Dinner Alumni Akbar', 'Malam gala dinner tahunan para alumni Universitas Darma Persada.', 'Hotel Grand Sahid Jaya, Jakarta', '2026-08-10', '2026-08-10', '18:00:00', '23:00:00', 'draft', 200000000.00, 'Gala Dinner', 500, 2, '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(4, 'Seminar Kewirausahaan Muda', 'Seminar inspiratif untuk para pengusaha muda Indonesia bersama mentor-mentor berpengalaman.', 'Auditorium Universitas Indonesia, Depok', '2026-05-05', '2026-05-05', '08:00:00', '17:00:00', 'completed', 50000000.00, 'Seminar', 800, 2, '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(5, 'Konser Amal Peduli Anak', 'Konser musik amal untuk penggalangan dana bagi anak-anak kurang mampu di Indonesia.', 'Istora Senayan, Jakarta', '2026-05-11', '2026-05-15', '16:00:00', '22:00:00', 'active', 300000000.00, 'Concert', 10000, 2, '2026-04-24 22:34:50', '2026-05-09 02:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `event_personnel`
--

CREATE TABLE `event_personnel` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_in_event` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_personnel`
--

INSERT INTO `event_personnel` (`id`, `event_id`, `user_id`, `role_in_event`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 3, 'Event Coordinator', 'Ditugaskan ke Festival Budaya Nusantara 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(2, 1, 4, 'Promotion Lead', 'Ditugaskan ke Festival Budaya Nusantara 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(3, 1, 5, 'Budget Manager', 'Ditugaskan ke Festival Budaya Nusantara 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(4, 1, 6, 'Operations Head', 'Ditugaskan ke Festival Budaya Nusantara 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(5, 1, 7, 'Creative Director', 'Ditugaskan ke Festival Budaya Nusantara 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(6, 1, 8, 'Talent Handler', 'Ditugaskan ke Festival Budaya Nusantara 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(7, 1, 2, 'Project Manager', 'Penanggung jawab utama project', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(8, 2, 3, 'Event Coordinator', 'Ditugaskan ke Tech Summit Indonesia 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(9, 2, 4, 'Promotion Lead', 'Ditugaskan ke Tech Summit Indonesia 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(10, 2, 5, 'Budget Manager', 'Ditugaskan ke Tech Summit Indonesia 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(11, 2, 6, 'Operations Head', 'Ditugaskan ke Tech Summit Indonesia 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(12, 2, 7, 'Creative Director', 'Ditugaskan ke Tech Summit Indonesia 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(13, 2, 8, 'Talent Handler', 'Ditugaskan ke Tech Summit Indonesia 2026', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(14, 2, 2, 'Project Manager', 'Penanggung jawab utama project', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(15, 3, 3, 'Event Coordinator', 'Ditugaskan ke Gala Dinner Alumni Akbar', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(16, 3, 4, 'Promotion Lead', 'Ditugaskan ke Gala Dinner Alumni Akbar', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(17, 3, 5, 'Budget Manager', 'Ditugaskan ke Gala Dinner Alumni Akbar', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(18, 3, 6, 'Operations Head', 'Ditugaskan ke Gala Dinner Alumni Akbar', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(19, 3, 7, 'Creative Director', 'Ditugaskan ke Gala Dinner Alumni Akbar', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(20, 3, 8, 'Talent Handler', 'Ditugaskan ke Gala Dinner Alumni Akbar', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(21, 3, 2, 'Project Manager', 'Penanggung jawab utama project', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(22, 4, 3, 'Event Coordinator', 'Ditugaskan ke Seminar Kewirausahaan Muda', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(23, 4, 4, 'Promotion Lead', 'Ditugaskan ke Seminar Kewirausahaan Muda', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(24, 4, 5, 'Budget Manager', 'Ditugaskan ke Seminar Kewirausahaan Muda', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(25, 4, 6, 'Operations Head', 'Ditugaskan ke Seminar Kewirausahaan Muda', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(26, 4, 7, 'Creative Director', 'Ditugaskan ke Seminar Kewirausahaan Muda', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(27, 4, 8, 'Talent Handler', 'Ditugaskan ke Seminar Kewirausahaan Muda', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(28, 4, 2, 'Project Manager', 'Penanggung jawab utama project', '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(29, 5, 3, 'Event Coordinator', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40'),
(30, 5, 4, 'Promotion Lead', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40'),
(31, 5, 5, 'Budget Manager', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40'),
(32, 5, 6, 'Operations Head', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40'),
(33, 5, 7, 'Creative Director', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40'),
(34, 5, 8, 'Talent Handler', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40'),
(35, 5, 2, 'Project Manager', NULL, '2026-04-24 22:34:50', '2026-05-09 02:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `event_rundowns`
--

CREATE TABLE `event_rundowns` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `event_date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `status` enum('pending','ready','live','delayed','completed') NOT NULL DEFAULT 'pending',
  `pic_id` bigint(20) UNSIGNED DEFAULT NULL,
  `location_note` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `order_number` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `delay_minutes` int(11) NOT NULL DEFAULT 0,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2024_01_01_000010_create_events_table', 1),
(5, '2024_01_01_000011_create_event_personnel_table', 1),
(6, '2026_04_25_055306_create_personal_access_tokens_table', 2),
(7, '2026_05_02_000001_create_tasks_table', 3),
(8, '2026_05_02_000002_create_task_comments_table', 3),
(9, '2026_05_07_000001_create_event_rundowns_table', 4),
(10, '2026_05_07_000002_create_rundown_dependencies_table', 4),
(11, '2026_05_07_000003_create_rundown_logs_table', 4);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(8, 'App\\Models\\User', 1, 'auth_token', '29b21fb1f3fc1d8ac9a8add79f87cb30d4d3199d84bac05af3d9f9a3eb3a2dc2', '[\"*\"]', '2026-05-09 06:17:42', NULL, '2026-05-09 03:24:07', '2026-05-09 06:17:42'),
(9, 'App\\Models\\User', 2, 'auth_token', 'b72d1f67bdd4bf3594c3b89e6d434b7aa8bd1d90f1ad87ed54f7d3b98453794d', '[\"*\"]', '2026-05-15 19:56:24', NULL, '2026-05-15 19:54:24', '2026-05-15 19:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `rundown_dependencies`
--

CREATE TABLE `rundown_dependencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rundown_id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rundown_logs`
--

CREATE TABLE `rundown_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rundown_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `old_status` varchar(255) DEFAULT NULL,
  `new_status` varchar(255) DEFAULT NULL,
  `delay_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `parent_task_id` bigint(20) UNSIGNED DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','review','completed','cancelled') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `event_id`, `assigned_to`, `created_by`, `parent_task_id`, `priority`, `status`, `due_date`, `due_time`, `category`, `notes`, `order`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'Siapkan Dekoarasai Panggung', 'dsvsvfvdfv', 5, 5, 1, NULL, 'high', 'completed', '2026-05-09', '14:28:00', 'Dekorasi', NULL, 0, '2026-05-06 20:46:26', '2026-05-01 21:25:41', '2026-05-06 20:46:26'),
(2, 'Siapkan Lightuing', 'fgdfgdfg', 1, 6, 1, NULL, 'high', 'completed', '2026-05-15', '19:13:00', 'technical', NULL, 0, NULL, '2026-05-09 02:13:23', '2026-05-09 02:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','project_manager','event_planner','promotion_team','partnership_manager','budgeting','operations_team','creative_team','rundown_coordinator','talent_coordinator','registration_guest_management','technical_team','documentation_team','liaison_officer') NOT NULL DEFAULT 'event_planner',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `role`, `status`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@eventconnect.com', '081234567890', 'superadmin', 'active', NULL, '$2y$12$TjRjurVhBCPJR/bHRRYJ9e1BV0Ybx.ZUemrONQ3XGeOzRyLsr/Ml.', NULL, '2026-04-24 22:34:44', '2026-04-24 22:52:16'),
(2, 'Rizky', 'pm@eventconnect.com', '081234567891', 'project_manager', 'active', NULL, '$2y$12$.8bJ9RlSUfzlwnYwvhZ6meXVOCWfdyQF5tBx1RLC1cLk7eIImhRo6', NULL, '2026-04-24 22:34:44', '2026-04-24 22:34:44'),
(3, 'Siti Rahayu', 'planner@eventconnect.com', '081234567892', 'event_planner', 'active', NULL, '$2y$12$9Et/nicFvGwF/v8O7c33W.Jwa5SQtpsACduudzOEKbfWKJsG9DQkm', NULL, '2026-04-24 22:34:45', '2026-04-24 22:34:45'),
(4, 'Ahmad Fauzi', 'promo@eventconnect.com', '081234567893', 'promotion_team', 'active', NULL, '$2y$12$M6cBe5q2uM8bSHAr3/pRce1kXBztbbmb7pTJprMWvv4a6UmhhDjy6', NULL, '2026-04-24 22:34:45', '2026-04-24 22:34:45'),
(5, 'Diana Putri', 'partner@eventconnect.com', '081234567894', 'partnership_manager', 'active', NULL, '$2y$12$7pxJEESin2BwiLMFUVZ1auC6ZcN6nCC.1uEgoRbZxnmXIxPs8Tzcy', NULL, '2026-04-24 22:34:46', '2026-04-24 22:34:46'),
(6, 'Rizky Maulana', 'budget@eventconnect.com', '081234567895', 'budgeting', 'active', NULL, '$2y$12$H7NtcS5L86xwC25dr35Fru3RWAW6vQuBL1mttmY1EhNUxYj97PGrC', NULL, '2026-04-24 22:34:46', '2026-04-24 22:34:46'),
(7, 'Eka Fitriani', 'ops@eventconnect.com', '081234567896', 'operations_team', 'active', NULL, '$2y$12$nyLZ471EaQgDQmLKlB.EF.pwi40HSDkoSJJwGlbzByqqEFK1YQg5O', NULL, '2026-04-24 22:34:47', '2026-04-24 22:34:47'),
(8, 'Farhan Hidayat', 'creative@eventconnect.com', '081234567897', 'creative_team', 'active', NULL, '$2y$12$nRJMR7yoLMK6222JV3nrROyuoOKwJoxVnFaibXQdij.AEavr5.XW6', NULL, '2026-04-24 22:34:47', '2026-04-24 22:34:47'),
(9, 'Gina Marlina', 'rundown@eventconnect.com', '081234567898', 'rundown_coordinator', 'active', NULL, '$2y$12$bQFTkYFu4yPRB9CfU1VoEevaOljXBhXR29dNk/Y4jyoAKu7aLQP8G', NULL, '2026-04-24 22:34:48', '2026-04-24 22:34:48'),
(10, 'Hendra Wijaya', 'talent@eventconnect.com', '081234567899', 'talent_coordinator', 'active', NULL, '$2y$12$sm4OdJ/VET/jGQRwGxKKKeDCdS3JEo5KIR4xvRsPPyBZgJ3zjqOTe', NULL, '2026-04-24 22:34:48', '2026-04-24 22:34:48'),
(11, 'Indah Permata', 'registration@eventconnect.com', '081234567900', 'registration_guest_management', 'active', NULL, '$2y$12$GktYd24g3OKchtV4Bjd3qeDiXxFQ0n5ZH2bNh38RmS6SNmaoSGn0u', NULL, '2026-04-24 22:34:49', '2026-04-24 22:34:49'),
(12, 'Joko Susilo', 'tech@eventconnect.com', '081234567901', 'technical_team', 'active', NULL, '$2y$12$AbAQhGvqB3WyQGhFu38ZCeUMlNiS2iZtfZOvZmGBdXEVY78pxFw3.', NULL, '2026-04-24 22:34:49', '2026-04-24 22:34:49'),
(13, 'Kartika Dewi', 'doc@eventconnect.com', '081234567902', 'documentation_team', 'active', NULL, '$2y$12$2LERslJzv0xhYZRSjopVA..hg3s61SHTs/3QetDVIPbek7.u8tA6i', NULL, '2026-04-24 22:34:50', '2026-04-24 22:34:50'),
(14, 'Luhut Pangaribuan', 'lo@eventconnect.com', '081234567903', 'liaison_officer', 'active', NULL, '$2y$12$Ynffc9.hTV0rxLcG86wC4er/12PZvFZG1wGE.ufV2FQw1U4Dvm91O', NULL, '2026-04-24 22:34:50', '2026-04-24 22:34:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `events_created_by_foreign` (`created_by`);

--
-- Indexes for table `event_personnel`
--
ALTER TABLE `event_personnel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_personnel_event_id_user_id_unique` (`event_id`,`user_id`),
  ADD KEY `event_personnel_user_id_foreign` (`user_id`);

--
-- Indexes for table `event_rundowns`
--
ALTER TABLE `event_rundowns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_rundowns_event_id_foreign` (`event_id`),
  ADD KEY `event_rundowns_pic_id_foreign` (`pic_id`),
  ADD KEY `event_rundowns_created_by_foreign` (`created_by`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indexes for table `rundown_dependencies`
--
ALTER TABLE `rundown_dependencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rundown_dependencies_rundown_id_task_id_unique` (`rundown_id`,`task_id`),
  ADD KEY `rundown_dependencies_task_id_foreign` (`task_id`);

--
-- Indexes for table `rundown_logs`
--
ALTER TABLE `rundown_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rundown_logs_rundown_id_foreign` (`rundown_id`),
  ADD KEY `rundown_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tasks_event_id_foreign` (`event_id`),
  ADD KEY `tasks_assigned_to_foreign` (`assigned_to`),
  ADD KEY `tasks_created_by_foreign` (`created_by`),
  ADD KEY `tasks_parent_task_id_foreign` (`parent_task_id`);

--
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_comments_task_id_foreign` (`task_id`),
  ADD KEY `task_comments_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_personnel`
--
ALTER TABLE `event_personnel`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `event_rundowns`
--
ALTER TABLE `event_rundowns`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `rundown_dependencies`
--
ALTER TABLE `rundown_dependencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rundown_logs`
--
ALTER TABLE `rundown_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_personnel`
--
ALTER TABLE `event_personnel`
  ADD CONSTRAINT `event_personnel_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_personnel_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `event_rundowns`
--
ALTER TABLE `event_rundowns`
  ADD CONSTRAINT `event_rundowns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_rundowns_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_rundowns_pic_id_foreign` FOREIGN KEY (`pic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rundown_dependencies`
--
ALTER TABLE `rundown_dependencies`
  ADD CONSTRAINT `rundown_dependencies_rundown_id_foreign` FOREIGN KEY (`rundown_id`) REFERENCES `event_rundowns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rundown_dependencies_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rundown_logs`
--
ALTER TABLE `rundown_logs`
  ADD CONSTRAINT `rundown_logs_rundown_id_foreign` FOREIGN KEY (`rundown_id`) REFERENCES `event_rundowns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rundown_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_parent_task_id_foreign` FOREIGN KEY (`parent_task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
