-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 12, 2026 at 04:05 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ysj_juniors_application`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements_activity_log`
--

CREATE TABLE `announcements_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `birth_date` date NOT NULL,
  `institution` varchar(100) NOT NULL,
  `grade_year` varchar(50) NOT NULL,
  `gpa` varchar(255) NOT NULL,
  `interest` text NOT NULL,
  `grade_previous` varchar(255) NOT NULL,
  `first_essay` text DEFAULT NULL,
  `second_essay` text DEFAULT NULL,
  `third_essay` text DEFAULT NULL,
  `fourth_essay` text DEFAULT NULL,
  `additional_essay` text DEFAULT NULL,
  `hours_commitment` int(11) NOT NULL,
  `time_blocks` text NOT NULL,
  `hear_about` text NOT NULL,
  `status` enum('pending','under_review','reviewed','approved','rejected') DEFAULT 'pending',
  `assigned_to` varchar(10) DEFAULT NULL,
  `reviewer_suggestion` enum('accept','reject') DEFAULT NULL,
  `ta_tm_c` int(11) DEFAULT NULL,
  `academic_writing` int(11) DEFAULT NULL,
  `personal_quality` int(11) DEFAULT NULL,
  `demonstrated_interest` int(11) DEFAULT NULL,
  `research_background` int(11) DEFAULT NULL,
  `academics` int(11) DEFAULT NULL,
  `analytic_skills` int(11) DEFAULT NULL,
  `total_score` int(11) DEFAULT NULL,
  `overall_rating` enum('Excellent','Good','Mid','Weak') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `interest_ratings` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `full_name`, `email`, `contact_number`, `country`, `gender`, `birth_date`, `institution`, `grade_year`, `gpa`, `interest`, `grade_previous`, `first_essay`, `second_essay`, `third_essay`, `fourth_essay`, `additional_essay`, `hours_commitment`, `time_blocks`, `hear_about`, `status`, `assigned_to`, `reviewer_suggestion`, `ta_tm_c`, `academic_writing`, `personal_quality`, `demonstrated_interest`, `research_background`, `academics`, `analytic_skills`, `total_score`, `overall_rating`, `notes`, `submitted_at`, `interest_ratings`, `is_deleted`) VALUES
(30, 'JYSJ091', 'Eyad Ashraf', 'junior@gmail.com', '01111605702', 'Egypt', 'male', '2025-07-10', 'STEM', '2025', 'A+', 'Aerospace Engineering,Architecture', 'Grade of each respective field in previous academic years (if applicable)', 'What extracurricular activities and achievements have you pursued in your area of academic interest?\r\nThese may range from as simple as winning a school competition to more intensive experiences such as Olympiads or internships. ', 'What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\r\nYour response should be at least 200 words in length.\r\nWhat role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\r\nYour response should be at least 200 words in length.\r\nWhat role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\r\nYour response should be at least 200 words in length.\r\nWhat role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\r\nYour response should be at least 200 words in length.\r\nWhat role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\r\nYour response should be at least 200 words in length.\r\n', 'Why do you want to join YSJ? Please explain what aspects of YSJs mission and values resonate with you.\r\nYour response should be at least 150 words in length.\r\nWhy do you want to join YSJ? Please explain what aspects of YSJs mission and values resonate with you.\r\nYour response should be at least 150 words in length.\r\nWhy do you want to join YSJ? Please explain what aspects of YSJs mission and values resonate with you.\r\nYour response should be at least 150 words in length.\r\nWhy do you want to join YSJ? Please explain what aspects of YSJs mission and values resonate with you.\r\nYour response should be at least 150 words in length.\r\nWhy do you want to join YSJ? Please explain what aspects of YSJs mission and values resonate with you.\r\nYour response should be at least 150 words in length.Why do you want to join YSJ? Please explain what aspects of YSJs mission and values resonate with you.\r\nYour response should be at least 150 words in length.', 'Have you participated in any previous research projects or programs? Please describe your research experience, including the objectives, methodologies used, and any notable outcomes or contributions. ', 'free', 5, 'list', 'how', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-08 13:36:16', '{\"Aerospace Engineering\":\"5\",\"Architecture\":\"5\"}', 0);

-- --------------------------------------------------------

--
-- Table structure for table `application_drafts`
--

CREATE TABLE `application_drafts` (
  `user_id` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `files` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_drafts`
--

INSERT INTO `application_drafts` (`user_id`, `data`, `files`, `updated_at`) VALUES
('IYSJ090', '{\"interest\":[\"\"],\"full_name\":\"fg\",\"email-address\":\"test@gmail.com\",\"contact-number\":\"0111\",\"country\":\"\",\"birth-date\":\"\",\"institution\":\"\",\"grade-year\":\"\",\"gpa\":\"\",\"grade-previous\":\"\",\"first-essay\":\"\",\"second-essay\":\"\",\"third-essay\":\"\",\"fourth-essay\":\"\",\"hours_commitment\":\"\",\"time_blocks\":\"\",\"hear_about\":\"\",\"additional-essay\":\"\"}', NULL, '2025-07-07 12:56:02'),
('JYSJ091', '{\"full_name\":\"Eyad\",\"email-address\":\"junior@gmail.com\",\"contact-number\":\"01111605702\",\"country\":\"Egypt\",\"birth-date\":\"2025-07-21\",\"institution\":\"STEM\",\"grade-year\":\"2020\",\"gpa\":\"A+\",\"interest\":[\"Aerospace Engineering\"],\"grade-previous\":\"A\",\"first-essay\":\"5ytfhg\",\"second-essay\":\"What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * \\r\\nWhat role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * \\r\\nWhat role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * \",\"third-essay\":\"What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * What role has science played in your life, and what motivates you to pursue academic research? How do your personal strengths support your ambitions and shape your goals in scientific inquiry?\\r\\nYour response should be at least 200 words in length. * \",\"fourth-essay\":\"jhbjmn\",\"hours_commitment\":\"8\",\"time_blocks\":\"hjb\",\"hear_about\":\"jkkj\",\"additional-essay\":\"iujhb\",\"is_ajax\":\"1\",\"gender\":\"male\",\"interest_ratings\":{\"Aerospace Engineering\":\"5\"}}', NULL, '2025-07-23 11:50:44');

-- --------------------------------------------------------

--
-- Table structure for table `application_status_log`
--

CREATE TABLE `application_status_log` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `old_status` varchar(20) NOT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` varchar(10) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `app_action`
--

CREATE TABLE `app_action` (
  `id` int(11) NOT NULL,
  `application_status` enum('open','closed') NOT NULL DEFAULT 'open',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_action`
--

INSERT INTO `app_action` (`id`, `application_status`, `updated_at`) VALUES
(1, 'open', '2025-06-18 15:27:20');

-- --------------------------------------------------------

--
-- Table structure for table `deleted_applications`
--

CREATE TABLE `deleted_applications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `birth_date` date NOT NULL,
  `institution` varchar(100) NOT NULL,
  `grade_year` varchar(50) NOT NULL,
  `gpa` varchar(255) NOT NULL,
  `interest` text NOT NULL,
  `grade_previous` varchar(50) NOT NULL,
  `first_essay` text DEFAULT NULL,
  `second_essay` text DEFAULT NULL,
  `third_essay` text DEFAULT NULL,
  `fourth_essay` text DEFAULT NULL,
  `additional_essay` text DEFAULT NULL,
  `hours_commitment` int(11) NOT NULL,
  `time_blocks` text NOT NULL,
  `hear_about` text NOT NULL,
  `status` enum('pending','under_review','reviewed','approved','rejected') DEFAULT 'pending',
  `assigned_to` varchar(10) DEFAULT NULL,
  `reviewer_suggestion` enum('accept','reject') DEFAULT NULL,
  `personal_qualifications` int(11) DEFAULT NULL,
  `research_skills` int(11) DEFAULT NULL,
  `analytic_skills` int(11) DEFAULT NULL,
  `mentorship_skills` int(11) DEFAULT NULL,
  `academic_writing` int(11) DEFAULT NULL,
  `academic_performance` int(11) DEFAULT NULL,
  `commitments` int(11) DEFAULT NULL,
  `total_score` int(11) DEFAULT NULL,
  `overall_rating` enum('Excellent','Good','Mid','Weak') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `interest_ratings` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('applicant','admin','reviewer') NOT NULL DEFAULT 'applicant',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email_address`, `password`, `role`, `created_at`) VALUES
('IYSJ090', 'test', 'test@gmail.com', '$2y$10$xES58BFgi2POLVPehoxDOOpafZTkyqIpxvgFSQMOOh7FMH16J45M6', 'applicant', '2025-07-06 22:15:57'),
('JYSJ091', 'junior', 'junior@gmail.com', '$2y$10$VQid/k0/3WyJequ0Svyc3eD1X6Br2T2jXAeTf9CXMv7ItaoxWsbXG', 'applicant', '2025-07-23 08:34:41'),
('SYSJ089', 'reviewer', 'reviewer@gmail.com', '$2y$10$86pGq9agO0dkA10XstTPC.i8Q.IYcpBBBj9ZKyLrMj4dLLWvYQGTS', 'reviewer', '2025-07-06 21:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_id_sequence`
--

CREATE TABLE `user_id_sequence` (
  `seq` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_id_sequence`
--

INSERT INTO `user_id_sequence` (`seq`) VALUES
(90),
(91);

-- --------------------------------------------------------

--
-- Table structure for table `verifications`
--

CREATE TABLE `verifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(10) NOT NULL,
  `verification_code` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements_activity_log`
--
ALTER TABLE `announcements_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `application_drafts`
--
ALTER TABLE `application_drafts`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `application_status_log`
--
ALTER TABLE `application_status_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `app_action`
--
ALTER TABLE `app_action`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_applications`
--
ALTER TABLE `deleted_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email_address` (`email_address`);

--
-- Indexes for table `user_id_sequence`
--
ALTER TABLE `user_id_sequence`
  ADD PRIMARY KEY (`seq`);

--
-- Indexes for table `verifications`
--
ALTER TABLE `verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `announcements_activity_log`
--
ALTER TABLE `announcements_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `application_status_log`
--
ALTER TABLE `application_status_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `app_action`
--
ALTER TABLE `app_action`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deleted_applications`
--
ALTER TABLE `deleted_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `user_id_sequence`
--
ALTER TABLE `user_id_sequence`
  MODIFY `seq` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `verifications`
--
ALTER TABLE `verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_status_log`
--
ALTER TABLE `application_status_log`
  ADD CONSTRAINT `application_status_log_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_status_log_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `deleted_applications`
--
ALTER TABLE `deleted_applications`
  ADD CONSTRAINT `deleted_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deleted_applications_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `verifications`
--
ALTER TABLE `verifications`
  ADD CONSTRAINT `verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
