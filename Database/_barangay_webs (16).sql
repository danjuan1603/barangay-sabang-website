-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2025 at 06:58 AM
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
-- Database: ` barangay_webs`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_accounts`
--

CREATE TABLE `admin_accounts` (
  `admin_id` int(30) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `is_online` tinyint(1) DEFAULT 0 COMMENT 'Admin online status: 1=online, 0=offline',
  `last_active` datetime DEFAULT NULL COMMENT 'Last activity timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_accounts`
--

INSERT INTO `admin_accounts` (`admin_id`, `admin_name`, `admin_password`, `is_online`, `last_active`) VALUES
(1234, 'DAN JUAN', '1234', 1, '2025-10-24 10:16:46'),
(12345, 'Christian Mhico D ', '123456', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_chats`
--

CREATE TABLE `admin_chats` (
  `chat_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `sender` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_chats`
--

INSERT INTO `admin_chats` (`chat_id`, `userid`, `sender`, `message`, `created_at`, `is_read`) VALUES
(151, 55305, 'admin', 'USER', '2025-09-03 23:04:58', 1),
(152, 55305, 'user', 'ADMIN', '2025-09-03 23:05:59', 1),
(153, 55305, 'user', 'admin', '2025-09-04 01:10:54', 1),
(154, 55305, 'admin', 'boyy', '2025-09-04 02:37:01', 1),
(155, 55305, 'user', 'hellooo admin', '2025-09-04 02:51:00', 1),
(156, 55305, 'admin', 'heloo user', '2025-09-04 02:52:24', 1),
(157, 55305, 'admin', 'heyyy', '2025-09-05 10:23:26', 1),
(158, 55305, 'admin', 'helooo', '2025-09-06 14:20:57', 1),
(159, 9692, 'user', 'admin', '2025-09-06 16:08:51', 1),
(160, 55305, 'user', 'hoy admin', '2025-09-20 07:36:04', 1),
(161, 4735, 'admin', 'sf', '2025-09-21 09:15:51', 1),
(162, 55305, 'admin', 'd', '2025-09-21 09:17:11', 1),
(163, 55305, 'admin', 'sdf', '2025-09-21 09:17:43', 1),
(164, 10775, 'admin', 'xxxz', '2025-09-21 09:19:39', 1),
(165, 10775, 'admin', 'cd', '2025-09-21 09:20:51', 1),
(166, 10775, 'admin', 'dsdsd', '2025-09-21 09:21:35', 1),
(167, 55305, 'admin', 'hi po', '2025-09-21 09:38:47', 1),
(168, 4735, 'admin', 'z', '2025-09-22 09:57:13', 1),
(169, 10775, 'admin', 'hi', '2025-09-25 05:53:03', 1),
(170, 55305, 'admin', 'bossing', '2025-09-25 05:54:28', 1),
(171, 55305, 'admin', 'booss', '2025-09-25 05:54:35', 1),
(172, 55305, 'admin', 'ee', '2025-09-25 05:54:39', 1),
(173, 10775, 'admin', 'ssds', '2025-09-26 14:41:24', 1),
(174, 55305, 'user', 'sd', '2025-09-26 14:47:00', 1),
(175, 55305, 'user', 'wd', '2025-09-26 14:50:22', 1),
(176, 55305, 'admin', 'asda', '2025-09-26 15:15:41', 1),
(177, 10775, 'user', 'dadad', '2025-09-30 14:09:35', 1),
(178, 10775, 'user', 'ds', '2025-09-30 14:09:39', 1),
(179, 55305, 'user', 'admin', '2025-10-02 10:55:06', 1),
(180, 55305, 'user', 'afsf', '2025-10-02 10:55:24', 1),
(181, 55305, 'user', 'awda', '2025-10-02 10:55:48', 1),
(182, 55305, 'user', 'awfa', '2025-10-02 10:55:50', 1),
(183, 55305, 'user', 'afsafsf', '2025-10-02 10:58:13', 1),
(184, 55305, 'user', 'sfds', '2025-10-02 11:00:26', 1),
(185, 55305, 'user', 'sdfsdf', '2025-10-02 11:00:42', 1),
(186, 55305, 'user', 'asd', '2025-10-02 11:01:48', 1),
(187, 55305, 'user', 'it fix', '2025-10-02 11:02:43', 1),
(188, 55305, 'user', 'df', '2025-10-02 11:03:43', 1),
(189, 55305, 'user', 'vxv', '2025-10-02 11:04:40', 1),
(190, 55305, 'user', 'asda', '2025-10-02 11:06:14', 1),
(191, 55305, 'user', 'fsf', '2025-10-02 11:06:52', 1),
(192, 55305, 'user', 'bcb', '2025-10-02 11:08:17', 1),
(193, 96585, 'admin', 'sdad', '2025-10-02 11:18:41', 1),
(194, 55305, 'user', 'adadaf', '2025-10-02 11:20:25', 1),
(195, 55305, 'user', 'it fix', '2025-10-02 11:21:09', 1),
(196, 55305, 'user', 'sfdf', '2025-10-02 11:23:15', 1),
(197, 55305, 'user', 'dfsdg', '2025-10-02 11:23:16', 1),
(198, 55305, 'admin', 'eef', '2025-10-02 11:24:37', 1),
(199, 55305, 'user', 'dadaddsdsd', '2025-10-03 08:06:06', 1),
(200, 55305, 'user', 'it fix', '2025-10-03 13:14:44', 1),
(201, 55305, 'user', 'dsf', '2025-10-03 13:14:50', 1),
(202, 55305, 'user', 'xc', '2025-10-03 13:16:51', 1),
(203, 55305, 'user', 'asdad', '2025-10-03 13:18:37', 1),
(204, 55305, 'user', 'sd', '2025-10-03 13:18:41', 1),
(205, 55305, 'admin', 'ablsbjkfbjaflnka', '2025-10-05 00:52:04', 1),
(206, 55305, 'admin', 'afafsf', '2025-10-05 00:52:06', 1),
(207, 55305, 'admin', 'helooo po', '2025-10-05 00:58:12', 1),
(208, 55305, 'admin', 'gddg', '2025-10-05 00:58:16', 1),
(209, 55305, 'admin', 'adasda', '2025-10-05 01:00:22', 1),
(210, 55305, 'admin', 'xvxxfb', '2025-10-05 01:01:41', 1),
(211, 96585, 'admin', 'adfsf', '2025-10-05 01:02:09', 1),
(212, 55305, 'user', 'fsfsdgdgdg', '2025-10-05 01:03:21', 1),
(213, 55305, 'admin', 'fsdsfdsfd', '2025-10-05 01:04:40', 1),
(214, 55305, 'admin', 'bfdbdb', '2025-10-05 01:06:34', 1),
(217, 55305, 'admin', 'helooo', '2025-10-05 01:13:06', 1),
(218, 55305, 'admin', '123', '2025-10-05 01:13:11', 1),
(219, 55305, 'admin', 'sdffsdg', '2025-10-05 01:16:32', 1),
(220, 55305, 'admin', 'df', '2025-10-05 01:16:36', 1),
(221, 55305, 'admin', 'userrr', '2025-10-05 01:47:13', 1),
(222, 55305, 'admin', 'helooo', '2025-10-05 01:47:18', 1),
(223, 55305, 'admin', 'ds', '2025-10-05 01:54:39', 1),
(224, 55305, 'user', 'it fix', '2025-10-05 01:59:10', 1),
(225, 55305, 'admin', 'it fixxx', '2025-10-05 22:52:52', 1),
(226, 55305, 'admin', 'heyy', '2025-10-05 22:52:58', 1),
(227, 55305, 'admin', 'helooo', '2025-10-05 23:02:26', 1),
(228, 55305, 'user', 'HEEYEYEYEEYEYEYEYE', '2025-10-06 06:50:17', 1),
(229, 55305, 'user', 'DWADWADAWDWA', '2025-10-06 06:50:21', 1),
(230, 55305, 'user', 'qeqveqvrvrwvwevr', '2025-10-08 14:02:46', 1),
(231, 55305, 'user', 'it fix gsrg  se esg  essg', '2025-10-08 14:23:46', 1),
(232, 55305, 'user', 'Helooo', '2025-10-09 01:04:14', 1),
(233, 55305, 'admin', 'helloo', '2025-10-10 02:30:40', 1),
(234, 55305, 'user', 'Hei po', '2025-10-10 02:34:00', 1),
(235, 55305, 'user', 'He', '2025-10-10 02:34:30', 1),
(236, 55305, 'admin', 'helloo', '2025-10-10 05:43:19', 1),
(237, 55305, 'admin', 'WHat can i help u', '2025-10-10 05:43:35', 1),
(239, 55305, 'admin', 'heloo', '2025-10-10 11:14:42', 1),
(240, 55305, 'admin', 'hrloo', '2025-10-10 11:14:46', 1),
(241, 10775, 'admin', 'sd', '2025-10-10 13:54:58', 1),
(242, 55305, 'admin', 'hyyy', '2025-10-10 14:19:18', 1),
(243, 55305, 'admin', 'hey', '2025-10-10 14:21:08', 1),
(247, 55305, 'user', 'Hy', '2025-10-10 14:22:05', 1),
(249, 55305, 'admin', 'hii', '2025-10-10 14:28:27', 1),
(251, 55305, 'user', 'Hy', '2025-10-10 14:32:01', 1),
(252, 55305, 'admin', 'gjh', '2025-10-10 18:12:19', 1),
(264, 55305, 'admin', 'helloo', '2025-10-16 15:49:26', 1),
(274, 55305, 'user', 'Hiii', '2025-10-17 02:14:29', 1),
(275, 55305, 'admin', 'wadaf', '2025-10-17 02:16:57', 1),
(277, 55305, 'user', 'Yhi', '2025-10-17 02:32:08', 1),
(279, 10775, 'user', 'heloo', '2025-10-19 00:51:58', 1),
(280, 55305, 'user', 'Heloi7u admibnnnnh ehgggu', '2025-10-20 01:00:05', 1),
(281, 10775, 'admin', 'hey', '2025-10-21 03:22:06', 1),
(282, 10775, 'user', 'ewfw', '2025-10-21 03:33:13', 1),
(284, 10775, 'user', 'sfd', '2025-10-21 03:42:04', 1),
(285, 10775, 'user', 'afsfafsfaf  fafa f faf f eaf s', '2025-10-21 03:42:15', 1),
(286, 10775, 'admin', 'ad', '2025-10-21 04:03:58', 1),
(289, 55305, 'user', 'Who are the barangay officials?', '2025-10-22 13:04:15', 1),
(290, 55305, 'admin', 'You can view the complete list of barangay officials on our Officials page. This includes the Barangay Captain, Kagawads, SK Chairman, and Barangay Secretary. Visit the website or barangay hall for updated information.', '2025-10-22 13:04:15', 1),
(291, 55305, 'user', 'What are the office hours?', '2025-10-22 13:10:25', 1),
(292, 55305, 'admin', 'The barangay office is open Monday to Friday, 8:00 AM to 5:00 PM. We are closed on weekends and public holidays. For urgent matters, you may contact our hotline.', '2025-10-22 13:10:25', 1),
(293, 55305, 'user', 'df', '2025-10-22 13:16:25', 1),
(294, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:16:25', 1),
(295, 55305, 'user', '23', '2025-10-22 13:17:19', 1),
(296, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:17:19', 1),
(297, 55305, 'user', 'What are the office hours?', '2025-10-22 13:17:41', 1),
(298, 55305, 'admin', 'The barangay office is open Monday to Friday, 8:00 AM to 5:00 PM. We are closed on weekends and public holidays. For urgent matters, you may contact our hotline.', '2025-10-22 13:17:41', 1),
(299, 55305, 'user', 'How to report an incident?', '2025-10-22 13:22:59', 1),
(300, 55305, 'admin', 'To report an incident: 1) Contact the barangay hotline immediately, 2) Visit the barangay hall to file a formal report, 3) Provide details of the incident (date, time, location, persons involved), 4) Submit any evidence if available. For emergencies, call 911 first.', '2025-10-22 13:22:59', 1),
(301, 55305, 'user', 'What are the office hours?', '2025-10-22 13:25:38', 1),
(302, 55305, 'admin', 'The barangay office is open Monday to Friday, 8:00 AM to 5:00 PM. We are closed on weekends and public holidays. For urgent matters, you may contact our hotline.', '2025-10-22 13:25:38', 1),
(303, 55305, 'user', 'df', '2025-10-22 13:32:33', 1),
(304, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:32:33', 1),
(305, 55305, 'user', 'sf', '2025-10-22 13:34:31', 1),
(306, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:34:31', 1),
(307, 55305, 'user', 'hey', '2025-10-22 13:37:13', 1),
(308, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:37:13', 1),
(309, 55305, 'user', 'What are the office hours?', '2025-10-22 13:37:25', 1),
(310, 55305, 'admin', 'The barangay office is open Monday to Friday, 8:00 AM to 5:00 PM. We are closed on weekends and public holidays. For urgent matters, you may contact our hotline.', '2025-10-22 13:37:25', 1),
(311, 55305, 'user', 'df', '2025-10-22 13:46:15', 1),
(312, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:46:15', 1),
(313, 55305, 'user', 'hI', '2025-10-22 13:47:10', 1),
(314, 55305, 'admin', 'HELOOOO', '2025-10-22 13:47:10', 1),
(315, 55305, 'user', 'Who are the barangay officials?', '2025-10-22 13:51:46', 1),
(316, 55305, 'admin', 'You can view the complete list of barangay officials on our Officials page. This includes the Barangay Captain, Kagawads, SK Chairman, and Barangay Secretary. Visit the website or barangay hall for updated information.', '2025-10-22 13:51:46', 1),
(317, 55305, 'user', 'eerg', '2025-10-22 13:55:56', 0),
(318, 55305, 'admin', 'I\'m sorry, I don\'t have information about that yet. Please go to Barangay Resident Support', '2025-10-22 13:55:56', 1),
(319, 55305, 'user', 'wfq', '2025-10-22 13:58:08', 0),
(320, 55305, 'admin', 'Sorry, I don\'t have the answer to that. Please wait for the admin to respond.', '2025-10-22 13:58:08', 1),
(321, 55305, 'user', 'sdd', '2025-10-22 13:59:50', 0),
(322, 55305, 'admin', 'Sorry, I don\'t have the answer to that. Please wait for the admin to respond.', '2025-10-22 13:59:50', 1);

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `action` text NOT NULL,
  `action_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `username`, `login_time`, `logout_time`, `action`, `action_time`) VALUES
(17, 'admin', '2025-09-05 18:22:47', NULL, '', NULL),
(18, 'admin', NULL, NULL, 'Edited resident: rona, Christian (ID: 10775)', '2025-09-05 18:23:12'),
(19, 'admin', NULL, NULL, 'Sent message to user ID 55305: heyyy', '2025-09-05 18:23:26'),
(20, '1234', '2025-09-05 18:23:44', '2025-09-05 18:27:57', '', NULL),
(21, '1234', NULL, NULL, 'Edited resident: monip, miks (ID: 78777)', '2025-09-05 18:27:51'),
(22, 'admin', '2025-09-05 18:28:05', '2025-09-05 18:29:57', '', NULL),
(23, '1234', '2025-09-05 18:30:05', '2025-09-05 18:30:44', '', NULL),
(24, '1234', NULL, NULL, 'Edited resident: miko, mikoooo (ID: 55305)', '2025-09-05 18:30:34'),
(25, 'admin', '2025-09-05 18:30:51', '2025-09-05 18:35:25', '', NULL),
(26, 'admin', NULL, NULL, 'Edited resident: Doe, John  (ID: 46365)', '2025-09-05 18:35:15'),
(27, '1234', '2025-09-05 18:35:30', '2025-09-05 18:58:05', '', NULL),
(28, '1234', NULL, NULL, 'Deleted suggestion ID 4 (Subject: miko)', '2025-09-05 18:38:56'),
(29, '1234', '2025-09-05 18:59:13', '2025-09-05 19:19:11', '', NULL),
(30, 'admin', '2025-09-05 19:19:20', '2025-09-05 20:25:45', '', NULL),
(31, 'admin', NULL, NULL, 'Updated status of report ID 40 to \'Resolved\'', '2025-09-05 19:51:34'),
(32, 'admin', NULL, NULL, 'Updated status of report ID 40 to \'In Review\'', '2025-09-05 19:51:36'),
(33, 'admin', NULL, NULL, 'Updated status of report ID 40 to \'Resolved\'', '2025-09-05 19:51:37'),
(34, 'admin', '2025-09-06 06:21:49', '2025-09-06 06:48:42', '', NULL),
(35, '1234', '2025-09-06 06:48:55', '2025-09-06 06:53:52', '', NULL),
(36, 'admin', '2025-09-06 06:54:01', '2025-09-06 09:22:17', '', NULL),
(37, 'admin', NULL, NULL, 'Reset password for User ID: 55305', '2025-09-06 06:56:30'),
(38, 'admin', '2025-09-06 15:36:19', '2025-09-06 15:53:09', '', NULL),
(39, 'admin', '2025-09-06 15:55:57', '2025-09-06 15:56:56', '', NULL),
(40, 'admin', '2025-09-06 15:57:07', '2025-09-06 17:31:13', '', NULL),
(41, '1234', '2025-09-06 17:37:44', '2025-09-06 18:55:14', '', NULL),
(42, 'admin', '2025-09-06 19:02:10', '2025-09-06 19:06:33', '', NULL),
(43, 'admin', NULL, NULL, 'Added announcement: \"Anti-dengue\" (ID: ann_68bc153187fc47.54685504)', '2025-09-06 19:04:17'),
(44, 'admin', NULL, NULL, 'Added announcement: Linggo Ng Kabataan (ID: ann_68bc1597b89207.95697209)', '2025-09-06 19:05:59'),
(45, 'admin', '2025-09-06 19:27:14', '2025-09-06 19:29:40', '', NULL),
(46, 'admin', NULL, NULL, 'Archived announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-09-06 19:27:38'),
(47, 'admin', '2025-09-06 19:37:21', '2025-09-06 19:40:13', '', NULL),
(48, 'admin', '2025-09-06 19:41:01', '2025-09-06 22:21:03', '', NULL),
(49, 'admin', NULL, NULL, 'Added resident: dalit, jester (ID: 04735)', '2025-09-06 20:36:39'),
(50, 'admin', NULL, NULL, 'Added resident: tanag, Ivan (ID: 92718)', '2025-09-06 20:38:09'),
(51, '1', NULL, NULL, 'Deleted Resident: dalit, jester (ID: 4735)', '2025-09-06 21:40:29'),
(52, '1', NULL, NULL, 'Deleted Resident: tanag, Ivan (ID: 92718)', '2025-09-06 21:40:36'),
(53, 'admin', NULL, NULL, 'Restored Resident: tanag, Ivan (ID: 92718)', '2025-09-06 21:41:02'),
(54, 'admin', NULL, NULL, 'Restored Resident: dalit, jester (ID: 4735)', '2025-09-06 21:41:03'),
(55, '1', NULL, NULL, 'Deleted Resident: dalit, jester (ID: 4735)', '2025-09-06 21:48:28'),
(56, '1', NULL, NULL, 'Deleted Resident: tanag, Ivan (ID: 92718)', '2025-09-06 21:48:34'),
(57, '1', NULL, NULL, 'Deleted Resident: monip, miks (ID: 78777)', '2025-09-06 21:48:41'),
(58, '1', NULL, NULL, 'Deleted Resident: ronario, mico (ID: 83127)', '2025-09-06 21:48:51'),
(59, 'admin', NULL, NULL, 'Added resident: Angel, Christian (ID: 57527)', '2025-09-06 21:50:45'),
(60, 'admin', NULL, NULL, 'Added resident: step, by  (ID: 61803)', '2025-09-06 21:54:08'),
(61, 'admin', NULL, NULL, 'Restored Resident: monip, miks (ID: 78777)', '2025-09-06 21:55:08'),
(62, 'admin', NULL, NULL, 'Restored Resident: tanag, Ivan (ID: 92718)', '2025-09-06 21:55:12'),
(63, 'admin', NULL, NULL, 'Restored Resident: dalit, jester (ID: 4735)', '2025-09-06 21:55:14'),
(64, 'admin', NULL, NULL, 'Restored Resident: ronario, christian Mhico (ID: 61099)', '2025-09-06 21:57:04'),
(65, 'admin', NULL, NULL, 'Restored Resident: Juan, dan (ID: 68674)', '2025-09-06 21:58:54'),
(66, '1', NULL, NULL, 'Deleted Resident: Angel, Christian (ID: 57527)', '2025-09-06 21:59:19'),
(67, '1', NULL, NULL, 'Deleted Resident: dalit, jester (ID: 4735)', '2025-09-06 21:59:23'),
(68, '1', NULL, NULL, 'Deleted Resident: Doe, John  (ID: 46365)', '2025-09-06 21:59:25'),
(69, '1', NULL, NULL, 'Deleted Resident: Juan, Dan Marvic (ID: 39105)', '2025-09-06 21:59:27'),
(70, '1', NULL, NULL, 'Deleted Resident: Juan, dan (ID: 68674)', '2025-09-06 21:59:29'),
(71, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 21:59:31'),
(72, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 21:59:33'),
(73, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 21:59:36'),
(74, '1', NULL, NULL, 'Deleted Resident: ronario, christian Mhico (ID: 61099)', '2025-09-06 21:59:46'),
(75, '1', NULL, NULL, 'Deleted Resident: tanag, Ivan (ID: 92718)', '2025-09-06 21:59:49'),
(76, '1', NULL, NULL, 'Deleted Resident: step, by  (ID: 61803)', '2025-09-06 21:59:54'),
(77, '1', NULL, NULL, 'Deleted Resident: rona, Christian (ID: 10775)', '2025-09-06 21:59:56'),
(78, '1', NULL, NULL, 'Deleted Resident: monip, miks (ID: 78777)', '2025-09-06 21:59:59'),
(79, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 22:00:00'),
(80, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 22:00:03'),
(81, 'admin', NULL, NULL, 'Restored Resident: Ronario, Christian (ID: 9692)', '2025-09-06 22:00:32'),
(82, 'admin', NULL, NULL, 'Restored Resident: ronario, mico (ID: 83127)', '2025-09-06 22:00:34'),
(83, 'admin', NULL, NULL, 'Restored Resident: Angel, Christian (ID: 57527)', '2025-09-06 22:00:37'),
(84, 'admin', NULL, NULL, 'Restored Resident: dalit, jester (ID: 4735)', '2025-09-06 22:00:39'),
(85, 'admin', NULL, NULL, 'Restored Resident: Doe, John  (ID: 46365)', '2025-09-06 22:00:42'),
(86, 'admin', NULL, NULL, 'Restored Resident: Juan, Dan Marvic (ID: 39105)', '2025-09-06 22:00:44'),
(87, 'admin', NULL, NULL, 'Restored Resident: Juan, dan (ID: 68674)', '2025-09-06 22:00:49'),
(88, 'admin', NULL, NULL, 'Restored Resident: step, by  (ID: 61803)', '2025-09-06 22:00:53'),
(89, 'admin', NULL, NULL, 'Restored Resident: tanag, Ivan (ID: 92718)', '2025-09-06 22:00:55'),
(90, 'admin', NULL, NULL, 'Restored Resident: rona, Christian (ID: 10775)', '2025-09-06 22:00:57'),
(91, 'admin', NULL, NULL, 'Permanently deleted resident: miko, mikoooo (ID: 55305)', '2025-09-06 22:01:16'),
(92, 'admin', NULL, NULL, 'Permanently deleted resident: monip, miks (ID: 78777)', '2025-09-06 22:01:18'),
(93, 'admin', NULL, NULL, 'Permanently deleted resident: ronario, christian Mhico (ID: 61099)', '2025-09-06 22:01:19'),
(94, '1', NULL, NULL, 'Deleted Resident: Doe, John  (ID: 46365)', '2025-09-06 22:03:09'),
(95, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 22:03:15'),
(96, '1', NULL, NULL, 'Deleted Resident: miko, mikoooo (ID: 55305)', '2025-09-06 22:03:21'),
(97, 'admin', NULL, NULL, 'Sent message to user ID 55305: helooo', '2025-09-06 22:20:57'),
(98, 'admin', '2025-09-06 23:00:39', NULL, '', NULL),
(99, 'admin', NULL, NULL, 'Approved certificate request ID: 12', '2025-09-06 23:04:01'),
(100, 'admin', NULL, NULL, 'Printed certificate ID: 12 for resident rona, Christian miko', '2025-09-06 23:04:10'),
(101, 'admin', NULL, NULL, 'Approved certificate request ID: 11', '2025-09-06 23:21:32'),
(102, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 11', '2025-09-06 23:21:42'),
(103, 'admin', '2025-09-06 23:22:44', '2025-09-07 00:06:14', '', NULL),
(104, 'admin', NULL, NULL, 'Archived resolved incident report ID 41', '2025-09-06 23:48:55'),
(105, 'admin', NULL, NULL, 'Deleted incident report ID 40 (Type: hiii bosos)', '2025-09-06 23:49:31'),
(106, 'admin', NULL, NULL, 'Deleted incident report ID 39 (Type: TRAFFIC)', '2025-09-06 23:49:34'),
(107, '1234', '2025-09-07 00:09:41', '2025-09-07 07:46:06', '', NULL),
(108, '1234', NULL, NULL, 'Updated status of report ID 44 to \'In Review\'', '2025-09-07 00:10:35'),
(109, '1234', NULL, NULL, 'Updated status of report ID 42 to \'In Review\'', '2025-09-07 00:10:39'),
(110, '1234', NULL, NULL, 'Archived resolved incident report ID 38', '2025-09-07 00:10:43'),
(111, '1234', NULL, NULL, 'Edited resident: rona, Christian (ID: 10775)', '2025-09-07 07:31:43'),
(112, 'admin', '2025-09-07 07:50:16', '2025-09-07 19:05:37', '', NULL),
(113, 'admin', NULL, NULL, 'Updated status of report ID 41 to \'In Review\'', '2025-09-07 17:52:06'),
(114, 'admin', NULL, NULL, 'Updated status of report ID 41 to \'In Review\'', '2025-09-07 17:52:11'),
(115, 'admin', '2025-09-07 19:29:37', '2025-09-07 20:23:27', '', NULL),
(116, 'admin', NULL, NULL, 'Approved certificate request ID: 14', '2025-09-07 19:34:56'),
(117, 'admin', '2025-09-07 22:06:05', NULL, '', NULL),
(118, 'admin', '2025-09-08 23:16:01', '2025-09-09 09:48:21', '', NULL),
(119, 'admin', NULL, NULL, 'Edited announcement: Linggo Ng Kabataan (ID: ann_68bc1597b89207.95697209)', '2025-09-09 08:47:30'),
(120, 'admin', NULL, NULL, 'Edited announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-09-09 08:47:41'),
(121, 'admin', '2025-09-09 09:48:52', NULL, '', NULL),
(122, 'admin', NULL, NULL, 'Added announcement: mikomikk (ID: ann_68bf87f4c4d5c5.93208365)', '2025-09-09 09:50:44'),
(123, 'admin', NULL, NULL, 'Deleted announcement: mikomikk (ID: ann_68bf87f4c4d5c5.93208365)', '2025-09-09 09:56:02'),
(124, 'admin', NULL, NULL, 'Archived announcement: Linggo Ng Kabataan (ID: ann_68bc1597b89207.95697209)', '2025-09-09 09:56:09'),
(125, 'admin', NULL, NULL, 'Archived announcement: Linggo Ng Kabataan (ID: ann_68bc1597b89207.95697209)', '2025-09-09 10:19:14'),
(126, 'admin', '2025-09-09 11:03:57', '2025-09-09 11:24:24', '', NULL),
(127, 'admin', NULL, NULL, 'Deleted suggestion ID 2 (Subject: miko)', '2025-09-09 11:14:07'),
(128, 'admin', NULL, NULL, 'Deleted suggestion ID 6 (Subject: Improvements)', '2025-09-09 11:14:20'),
(129, 'admin', NULL, NULL, 'Archived announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-09-09 11:20:52'),
(130, 'admin', '2025-09-09 11:25:07', NULL, '', NULL),
(131, 'admin', '2025-09-09 11:32:24', NULL, '', NULL),
(132, 'admin', '2025-09-09 13:36:40', '2025-09-09 13:43:44', '', NULL),
(133, 'admin', NULL, NULL, 'Archived announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-09-09 13:36:55'),
(134, 'admin', '2025-09-09 13:44:32', '2025-09-09 20:04:54', '', NULL),
(135, 'admin', NULL, NULL, 'Deleted incident report ID 57 (Type: sdff)', '2025-09-09 14:05:18'),
(136, 'admin', NULL, NULL, 'Deleted incident report ID 56 (Type: sfsd)', '2025-09-09 14:05:21'),
(137, 'admin', NULL, NULL, 'Deleted incident report ID 55 (Type: dfs)', '2025-09-09 14:05:24'),
(138, 'admin', NULL, NULL, 'Deleted incident report ID 54 (Type: ds)', '2025-09-09 14:05:28'),
(139, 'admin', NULL, NULL, 'Deleted incident report ID 53 (Type: sad)', '2025-09-09 14:05:31'),
(140, 'admin', NULL, NULL, 'Added announcement: ilaw at gabay (ID: ann_68c00fc27b78e8.03394148)', '2025-09-09 19:30:10'),
(141, 'admin', '2025-09-09 20:05:46', '2025-09-10 21:26:37', '', NULL),
(142, 'admin', NULL, NULL, 'Archived resolved incident report ID 80', '2025-09-09 21:09:52'),
(143, 'admin', NULL, NULL, 'Archived resolved incident report ID 78', '2025-09-09 21:44:50'),
(144, 'admin', NULL, NULL, 'Archived resolved incident report ID 81', '2025-09-09 22:17:27'),
(145, 'admin', NULL, NULL, 'Archived resolved incident report ID 43', '2025-09-09 22:17:40'),
(146, 'admin', NULL, NULL, 'Archived resolved incident report ID 79', '2025-09-09 22:17:45'),
(147, 'admin', NULL, NULL, 'Archived resolved incident report ID 44', '2025-09-09 22:17:53'),
(148, 'admin', NULL, NULL, 'Archived resolved incident report ID 67', '2025-09-09 22:19:34'),
(149, 'admin', NULL, NULL, 'Archived resolved incident report ID 45', '2025-09-09 22:19:39'),
(150, 'admin', NULL, NULL, 'Archived resolved incident report ID 76', '2025-09-09 22:23:16'),
(151, 'admin', NULL, NULL, 'Updated status of report ID 46 to \'In Review\'', '2025-09-09 22:23:24'),
(152, 'admin', NULL, NULL, 'Archived resolved incident report ID 77', '2025-09-09 22:24:47'),
(153, 'admin', NULL, NULL, 'Archived resolved incident report ID 73', '2025-09-09 22:27:06'),
(154, 'admin', NULL, NULL, 'Archived resolved incident report ID 75', '2025-09-09 22:27:30'),
(155, 'admin', NULL, NULL, 'Updated status of report ID 46 to \'In Review\'', '2025-09-09 22:28:44'),
(156, 'admin', NULL, NULL, 'Archived resolved incident report ID 82', '2025-09-10 20:55:24'),
(157, 'admin', NULL, NULL, 'Archived resolved incident report ID 72', '2025-09-10 20:58:06'),
(158, 'admin', NULL, NULL, 'Archived resolved incident report ID 70', '2025-09-10 21:00:34'),
(159, 'admin', NULL, NULL, 'Deleted incident report ID 71 (Type: as)', '2025-09-10 21:05:19'),
(160, 'admin', NULL, NULL, 'Archived resolved incident report ID 69', '2025-09-10 21:06:27'),
(161, 'admin', NULL, NULL, 'Archived resolved incident report ID 68', '2025-09-10 21:07:08'),
(162, 'admin', NULL, NULL, 'Archived resolved incident report ID 74', '2025-09-10 21:10:06'),
(163, 'admin', NULL, NULL, 'Archived resolved incident report ID 66', '2025-09-10 21:10:54'),
(164, 'admin', NULL, NULL, 'Archived resolved incident report ID 65', '2025-09-10 21:15:50'),
(165, 'admin', NULL, NULL, 'Archived resolved incident report ID 64', '2025-09-10 21:15:58'),
(166, 'admin', NULL, NULL, 'Archived resolved incident report ID 63', '2025-09-10 21:23:34'),
(167, 'admin', '2025-09-10 21:26:44', '2025-09-11 08:23:55', '', NULL),
(168, 'admin', NULL, NULL, 'Archived resolved incident report ID 62', '2025-09-10 21:26:54'),
(169, 'admin', NULL, NULL, 'Archived resolved incident report ID 60', '2025-09-10 21:29:02'),
(170, 'admin', NULL, NULL, 'Archived resolved incident report ID 61', '2025-09-10 21:33:23'),
(171, 'admin', NULL, NULL, 'Deleted incident report ID 57 (Type: )', '2025-09-10 21:33:33'),
(172, 'admin', NULL, NULL, 'Deleted incident report ID 57 (Type: )', '2025-09-10 21:33:44'),
(173, 'admin', NULL, NULL, 'Deleted incident report ID 49 (Type: )', '2025-09-10 21:33:59'),
(174, 'admin', NULL, NULL, 'Deleted incident_report ID 47 (Type: )', '2025-09-10 21:34:41'),
(175, 'admin', NULL, NULL, 'Archived resolved incident report ID 59', '2025-09-10 21:34:57'),
(176, 'admin', NULL, NULL, 'Archived resolved incident report ID 52', '2025-09-10 21:35:09'),
(177, 'admin', NULL, NULL, 'Archived resolved incident report ID 58', '2025-09-10 21:41:21'),
(178, 'admin', NULL, NULL, 'Deleted archived_incident_report ID 60 (Type: miko)', '2025-09-10 21:41:38'),
(179, 'admin', NULL, NULL, 'Added official: dets', '2025-09-10 22:07:53'),
(180, 'admin', NULL, NULL, 'Archived official: dets (ID: 7)', '2025-09-10 22:07:53'),
(181, 'admin', NULL, NULL, 'Approved certificate request ID: 17', '2025-09-11 08:11:27'),
(182, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:14:06'),
(183, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:14:10'),
(184, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-09-11 08:14:14'),
(185, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-09-11 08:14:18'),
(186, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:17:45'),
(187, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:17:49'),
(188, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:23:39'),
(189, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-09-11 08:23:43'),
(190, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:23:50'),
(191, 'Unknown', NULL, NULL, 'Unblocked resident: 55305', '2025-09-11 08:24:35'),
(192, 'Unknown', NULL, NULL, 'Blocked resident: 55305', '2025-09-11 08:26:18'),
(193, 'admin', '2025-09-11 08:29:34', NULL, '', NULL),
(194, 'admin', NULL, NULL, 'Added resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-11 08:30:30'),
(195, 'admin', NULL, NULL, 'Rejected & Archived certificate request ID: 18', '2025-09-11 08:41:11'),
(196, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-09-11 08:42:07'),
(197, 'admin', NULL, NULL, 'Approved certificate request ID: 15', '2025-09-11 11:25:47'),
(198, 'admin', '2025-09-13 12:52:56', NULL, '', NULL),
(199, 'admin', '2025-09-13 13:07:03', NULL, '', NULL),
(200, 'admin', '2025-09-13 13:24:20', '2025-09-13 14:00:58', '', NULL),
(201, 'admin', NULL, NULL, 'Added resident: faafa, fwaeawba (ID: 83929)', '2025-09-13 13:50:39'),
(202, 'admin', NULL, NULL, 'Added resident: qbabwa, dwa (ID: 52040)', '2025-09-13 13:57:50'),
(203, 'admin', '2025-09-14 13:52:57', '2025-09-14 14:09:22', '', NULL),
(204, 'admin', '2025-09-14 15:31:40', '2025-09-14 15:45:59', '', NULL),
(205, 'admin', NULL, NULL, 'Added resident: jokenow, ejay (ID: 91530)', '2025-09-14 15:34:41'),
(206, 'admin', '2025-09-15 20:48:48', '2025-09-15 21:58:07', '', NULL),
(207, 'admin', NULL, NULL, 'Edited resident: miko, mikoooo (ID: 55305)', '2025-09-15 20:49:29'),
(208, 'admin', '2025-09-15 22:10:34', '2025-09-15 22:20:11', '', NULL),
(209, 'admin', NULL, NULL, 'Deleted archived_incident_report ID 46 (Type: asd)', '2025-09-15 22:10:54'),
(210, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 17', '2025-09-15 22:11:17'),
(211, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 15', '2025-09-15 22:14:38'),
(212, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 14', '2025-09-15 22:17:15'),
(213, 'admin', NULL, NULL, 'Approved certificate request ID: 16', '2025-09-15 22:17:43'),
(214, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 16', '2025-09-15 22:17:45'),
(215, 'admin', NULL, NULL, 'Approved certificate request ID: 13', '2025-09-15 22:19:05'),
(216, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 13', '2025-09-15 22:19:22'),
(217, 'admin', '2025-09-15 22:21:28', '2025-09-15 22:37:58', '', NULL),
(218, 'admin', NULL, NULL, 'Approved certificate request ID: 23', '2025-09-15 22:21:59'),
(219, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 23', '2025-09-15 22:22:00'),
(220, 'admin', NULL, NULL, 'Approved certificate request ID: 22', '2025-09-15 22:26:26'),
(221, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 22', '2025-09-15 22:26:27'),
(222, 'admin', NULL, NULL, 'Approved certificate request ID: 21', '2025-09-15 22:28:38'),
(223, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 21', '2025-09-15 22:28:39'),
(224, 'admin', NULL, NULL, 'Approved certificate request ID: 20', '2025-09-15 22:31:35'),
(225, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 20', '2025-09-15 22:31:37'),
(226, 'admin', NULL, NULL, 'Approved certificate request ID: 19', '2025-09-15 22:34:43'),
(227, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 19', '2025-09-15 22:34:44'),
(228, 'admin', '2025-09-15 22:38:54', NULL, '', NULL),
(229, 'admin', NULL, NULL, 'Approved certificate request ID: 28', '2025-09-15 22:39:07'),
(230, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 28', '2025-09-15 22:39:08'),
(231, 'admin', NULL, NULL, 'Approved certificate request ID: 27', '2025-09-15 22:42:08'),
(232, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 27', '2025-09-15 22:42:09'),
(233, 'admin', NULL, NULL, 'Approved certificate request ID: 26', '2025-09-15 22:44:29'),
(234, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 26', '2025-09-15 22:44:31'),
(235, 'admin', NULL, NULL, 'Approved certificate request ID: 25', '2025-09-15 22:45:00'),
(236, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 25', '2025-09-15 22:45:01'),
(237, 'admin', NULL, NULL, 'Approved certificate request ID: 24', '2025-09-15 22:46:47'),
(238, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 24', '2025-09-15 22:46:48'),
(239, 'admin', NULL, NULL, 'Approved certificate request ID: 34', '2025-09-15 22:49:42'),
(240, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 34', '2025-09-15 22:49:44'),
(241, 'admin', NULL, NULL, 'Approved certificate request ID: 33', '2025-09-15 22:53:26'),
(242, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 33', '2025-09-15 22:53:27'),
(243, 'admin', NULL, NULL, 'Approved certificate request ID: 32', '2025-09-15 23:05:05'),
(244, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 32', '2025-09-15 23:05:16'),
(245, 'admin', NULL, NULL, 'Approved certificate request ID: 30', '2025-09-15 23:05:46'),
(246, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 30', '2025-09-15 23:05:48'),
(247, 'admin', NULL, NULL, 'Approved certificate request ID: 31', '2025-09-15 23:07:13'),
(248, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 31', '2025-09-15 23:07:16'),
(249, 'admin', NULL, NULL, 'Approved certificate request ID: 29', '2025-09-15 23:09:58'),
(250, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 29', '2025-09-15 23:09:59'),
(251, 'admin', NULL, NULL, 'Approved certificate request ID: 35', '2025-09-15 23:26:19'),
(252, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 35', '2025-09-15 23:26:20'),
(253, 'admin', NULL, NULL, 'Approved certificate request ID: 37', '2025-09-15 23:27:01'),
(254, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 37', '2025-09-15 23:27:02'),
(255, 'admin', NULL, NULL, 'Approved certificate request ID: 36', '2025-09-15 23:27:24'),
(256, 'admin', NULL, NULL, 'Printed & Archived certificate request ID: 36', '2025-09-15 23:27:25'),
(257, 'admin', '2025-09-20 06:57:27', NULL, '', NULL),
(258, 'admin', '2025-09-20 08:03:53', NULL, '', NULL),
(259, 'admin', '2025-09-20 08:26:03', NULL, '', NULL),
(260, 'admin', '2025-09-20 09:58:29', '2025-09-20 10:22:27', '', NULL),
(261, '1234', '2025-09-20 10:23:16', NULL, '', NULL),
(262, 'admin', '2025-09-20 10:36:01', '2025-09-20 11:27:17', '', NULL),
(263, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-09-20 10:38:01'),
(264, 'admin', NULL, NULL, 'Deleted suggestion ID 7 (Subject: helooo)', '2025-09-20 11:06:38'),
(265, 'admin', NULL, NULL, 'Deleted suggestion ID 8 (Subject: harnvhearv er)', '2025-09-20 11:16:04'),
(266, 'admin', NULL, NULL, 'Deleted suggestion ID 9 (Subject: rentn)', '2025-09-20 11:17:52'),
(267, 'admin', NULL, NULL, 'Deleted suggestion ID 10 (Subject: dasd)', '2025-09-20 11:23:46'),
(268, 'admin', '2025-09-20 11:27:24', NULL, '', NULL),
(269, 'admin', '2025-09-20 12:21:27', '2025-09-20 15:06:45', '', NULL),
(270, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96587)', '2025-09-20 13:32:43'),
(271, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96586)', '2025-09-20 13:32:45'),
(272, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96590)', '2025-09-20 13:32:47'),
(273, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96591)', '2025-09-20 13:32:55'),
(274, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96588)', '2025-09-20 13:33:04'),
(275, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96589)', '2025-09-20 13:38:59'),
(276, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 13:44:15'),
(277, 'admin', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 13:44:15'),
(278, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 13:44:30'),
(279, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 13:44:30'),
(280, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96592)', '2025-09-20 13:44:47'),
(281, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96595)', '2025-09-20 13:44:55'),
(282, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96593)', '2025-09-20 13:49:27'),
(283, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96594)', '2025-09-20 13:49:31'),
(284, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 13:49:46'),
(285, 'admin', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 13:49:46'),
(286, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 13:50:18'),
(287, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 13:50:18'),
(288, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 13:50:57'),
(289, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 13:50:57'),
(290, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 13:58:22'),
(291, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 13:58:22'),
(292, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 14:08:17'),
(293, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 14:08:17'),
(294, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 14:08:52'),
(295, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 14:08:52'),
(296, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 14:13:07'),
(297, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 14:13:07'),
(298, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 14:13:47'),
(299, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 14:13:47'),
(300, 'unknown', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-20 14:13:52'),
(301, 'unknown', NULL, NULL, 'Added resident: Reyes, Maria (ID: )', '2025-09-20 14:13:52'),
(302, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96608)', '2025-09-20 14:14:27'),
(303, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96610)', '2025-09-20 14:14:29'),
(304, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96612)', '2025-09-20 14:14:33'),
(305, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96609)', '2025-09-20 14:14:42'),
(306, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96611)', '2025-09-20 14:14:49'),
(307, '1', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96613)', '2025-09-20 14:14:54'),
(308, 'admin', '2025-09-20 15:37:05', NULL, '', NULL),
(309, 'admin', '2025-09-20 15:37:29', NULL, '', NULL),
(310, 'admin', '2025-09-20 16:05:15', NULL, '', NULL),
(311, 'admin', '2025-09-20 16:05:31', '2025-09-20 16:32:51', '', NULL),
(312, 'admin', '2025-09-20 16:34:22', '2025-09-20 16:40:04', '', NULL),
(313, 'admin', '2025-09-20 16:41:39', '2025-09-20 18:17:58', '', NULL),
(314, 'admin', NULL, NULL, 'Edited resident: miko, mikoooo (ID: 55305)', '2025-09-20 18:14:36'),
(315, 'admin', '2025-09-20 18:18:19', '2025-09-20 18:18:39', '', NULL),
(316, 'admin', '2025-09-20 20:03:08', '2025-09-20 20:08:41', '', NULL),
(317, 'admin', '2025-09-20 20:30:35', '2025-09-21 08:52:19', '', NULL),
(318, 'admin', NULL, NULL, 'Added resident: De jesus, Angel (ID: 68018)', '2025-09-20 20:34:06'),
(319, 'admin', NULL, NULL, 'Edited resident: Ronario, Christian (ID: 9692)', '2025-09-20 20:34:44'),
(320, 'admin', '2025-09-21 10:33:18', '2025-09-21 10:34:55', '', NULL),
(321, 'admin', NULL, NULL, 'Edited resident: miko, Christian (ID: 55305)', '2025-09-21 10:34:38'),
(322, 'admin', '2025-09-21 11:46:44', '2025-09-21 13:42:47', '', NULL),
(323, 'unknown', NULL, NULL, 'Added resident: Dela ruz, Juan (ID: )', '2025-09-21 11:54:33'),
(324, 'unknown', NULL, NULL, 'Added resident: Dela ruz, Juan (ID: )', '2025-09-21 13:21:31'),
(325, 'admin', NULL, NULL, 'Archived announcement: Linggo Ng Kabataan (ID: ann_68bc1597b89207.95697209)', '2025-09-21 13:37:22'),
(326, 'admin', '2025-09-21 16:11:57', '2025-09-21 17:21:15', '', NULL),
(327, 'admin', NULL, NULL, 'Sent message to user ID 4735: sf', '2025-09-21 17:15:51'),
(328, 'admin', NULL, NULL, 'Sent message to user ID 55305: d', '2025-09-21 17:17:11'),
(329, 'admin', NULL, NULL, 'Sent message to user ID 55305: sdf', '2025-09-21 17:17:43'),
(330, 'admin', NULL, NULL, 'Sent message to user ID 10775: xxxz', '2025-09-21 17:19:39'),
(331, 'admin', NULL, NULL, 'Sent message to user ID 10775: cd', '2025-09-21 17:20:51'),
(332, 'admin', '2025-09-21 17:21:25', '2025-09-22 17:51:57', '', NULL),
(333, 'admin', NULL, NULL, 'Sent message to user ID 10775: dsdsd', '2025-09-21 17:21:35'),
(334, 'admin', NULL, NULL, 'Sent message to user ID 55305: hi po', '2025-09-21 17:38:47'),
(335, 'admin', '2025-09-22 17:52:03', '2025-09-22 17:59:48', '', NULL),
(336, 'admin', NULL, NULL, 'Sent message to user ID 4735: z', '2025-09-22 17:57:13'),
(337, 'admin', '2025-09-22 18:11:01', '2025-09-22 18:11:03', '', NULL),
(338, 'admin', '2025-09-23 09:44:08', '2025-09-23 09:45:02', '', NULL),
(339, 'admin', NULL, NULL, 'Rejected & Archived certificate request ID: 42', '2025-09-23 09:44:21'),
(340, 'admin', NULL, NULL, 'Approved certificate request ID: 41', '2025-09-23 09:44:29'),
(341, 'admin', NULL, NULL, 'Approved certificate request ID: 39', '2025-09-23 09:44:33'),
(342, 'admin', NULL, NULL, 'Printed certificate ID: 39 for resident Christian D miko', '2025-09-23 09:44:34'),
(343, 'admin', NULL, NULL, 'Approved certificate request ID: 40', '2025-09-23 09:44:46'),
(344, 'admin', NULL, NULL, 'Archived certificate request ID: 39 (status Printed)', '2025-09-23 09:44:52'),
(345, 'admin', NULL, NULL, 'Printed certificate ID: 40 for resident Christian D miko', '2025-09-23 09:44:54'),
(346, '1234', '2025-09-23 09:57:44', '2025-09-23 10:21:45', '', NULL),
(347, '1234', NULL, NULL, 'Archived certificate request ID: 40 (status Printed)', '2025-09-23 09:57:51'),
(348, '1234', '2025-09-23 10:24:10', '2025-09-23 10:25:29', '', NULL),
(349, 'admin', '2025-09-24 19:00:12', '2025-09-24 19:00:42', '', NULL),
(350, 'admin', '2025-09-24 19:07:11', '2025-09-24 20:56:35', '', NULL),
(351, 'admin', '2025-09-25 08:46:57', NULL, '', NULL),
(352, 'admin', '2025-09-25 08:58:09', NULL, '', NULL),
(353, 'admin', '2025-09-25 09:31:24', NULL, '', NULL),
(354, 'admin', NULL, NULL, 'Added resident: Juan, Christian (ID: 13833)', '2025-09-25 09:39:39'),
(355, 'admin', '2025-09-25 10:02:47', '2025-09-25 10:16:24', '', NULL),
(356, 'admin', '2025-09-25 10:16:56', '2025-09-25 10:26:45', '', NULL),
(357, 'admin', '2025-09-25 10:27:14', NULL, '', NULL),
(358, 'admin', NULL, NULL, 'Added resident: ronario, saddii (ID: 43640)', '2025-09-25 10:37:09'),
(359, 'admin', '2025-09-25 11:00:35', NULL, '', NULL),
(360, 'admin', NULL, NULL, 'Permanently deleted resident: miko, mikoooo (ID: 55305)', '2025-09-25 11:27:11'),
(361, 'admin', NULL, NULL, 'Permanently deleted resident: Dela Cruz, Juan (ID: 96587)', '2025-09-25 11:27:16'),
(362, 'admin', '2025-09-25 12:08:55', NULL, '', NULL),
(363, '1', NULL, NULL, 'Deleted Resident: qbabwa, dwa (ID: 52040)', '2025-09-25 12:10:46'),
(364, 'admin', '2025-09-25 12:16:05', NULL, '', NULL),
(365, 'admin', '2025-09-25 12:21:54', NULL, '', NULL),
(366, 'admin', '2025-09-25 12:33:53', '2025-09-25 14:32:56', '', NULL),
(367, 'admin', NULL, NULL, 'Sent message to user ID 10775: hi', '2025-09-25 13:53:03'),
(368, 'admin', NULL, NULL, 'Sent message to user ID 55305: bossing', '2025-09-25 13:54:28'),
(369, 'admin', NULL, NULL, 'Sent message to user ID 55305: booss', '2025-09-25 13:54:35'),
(370, 'admin', NULL, NULL, 'Sent message to user ID 55305: ee', '2025-09-25 13:54:39'),
(371, '1234', '2025-09-25 14:33:03', '2025-09-25 14:33:19', '', NULL),
(372, 'admin', '2025-09-25 14:33:26', '2025-09-25 14:34:51', '', NULL),
(373, '1234', '2025-09-25 14:34:57', '2025-09-25 14:35:16', '', NULL),
(374, 'admin', '2025-09-25 15:48:20', NULL, '', NULL),
(375, '1', NULL, NULL, 'Deleted Resident: faafa, fwaeawba (ID: 83929)', '2025-09-25 15:50:16'),
(376, '1', NULL, NULL, 'Deleted Resident: Dela ruz, Juan (ID: 96615)', '2025-09-25 16:07:41'),
(377, 'admin', '2025-09-25 17:11:52', '2025-09-26 22:45:47', '', NULL),
(378, 'admin', NULL, NULL, 'Edited resident: Juan, Christian (ID: 13833)', '2025-09-25 17:55:41'),
(379, 'admin', NULL, NULL, 'Edited resident: Juan53252, Christian (ID: 13833)', '2025-09-25 17:56:28'),
(380, 'admin', NULL, NULL, 'Edited resident: ANGEL2, CHRISTINE (ID: 96585)', '2025-09-25 17:57:25'),
(381, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-25 17:57:37'),
(382, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-25 17:58:00'),
(383, 'admin', NULL, NULL, 'Edited resident: De jesus, Angel (ID: 68018)', '2025-09-25 17:58:12'),
(384, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-25 18:00:02'),
(385, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-25 18:00:15'),
(386, 'admin', NULL, NULL, 'Edited resident: step, Step (ID: 61803)', '2025-09-25 18:03:21'),
(387, '1', NULL, NULL, 'Deleted Resident: Dela ruz, Juan (ID: 96614)', '2025-09-25 18:12:01'),
(388, 'admin', NULL, NULL, 'Edited resident: rona, Christian (ID: 10775)', '2025-09-25 18:16:17'),
(389, 'admin', NULL, NULL, 'Edited resident: Juan, Dan Marvic (ID: 39105)', '2025-09-25 18:16:29'),
(390, '1', NULL, NULL, 'Deleted Resident: Juan53252, Christian (ID: 13833)', '2025-09-25 18:17:40'),
(391, 'admin', NULL, NULL, 'Edited resident: Juan, Marvic (ID: 39105)', '2025-09-25 18:18:53'),
(392, '1', NULL, NULL, 'Deleted Resident: step, Step (ID: 61803)', '2025-09-25 18:20:28'),
(393, '1', NULL, NULL, 'Deleted Resident: Juan, Marvic (ID: 39105)', '2025-09-25 18:24:56'),
(394, 'admin', NULL, NULL, 'Permanently deleted resident: Juan, Marvic (ID: 39105)', '2025-09-25 21:18:09'),
(395, 'admin', NULL, NULL, 'Restored Resident: step, Step (ID: 61803)', '2025-09-25 21:29:52'),
(396, 'admin', NULL, NULL, 'Restored Resident: Doe, John  (ID: 46365)', '2025-09-25 21:31:28'),
(397, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-09-25 21:49:17'),
(398, 'admin', NULL, NULL, 'Edited resident: Doe, John  (ID: 46365)', '2025-09-26 08:34:24'),
(399, 'admin', NULL, NULL, 'Edited resident: Doe, John  (ID: 46365)', '2025-09-26 08:34:46'),
(400, 'admin', NULL, NULL, 'Restored Resident: Juan53252, Christian (ID: 13833)', '2025-09-26 08:35:04'),
(401, 'admin', NULL, NULL, 'Restored Resident: Dela ruz, Juan (ID: 96614)', '2025-09-26 08:41:38'),
(402, 'admin', NULL, NULL, 'Restored Resident: Dela ruz, Juan (ID: 96615)', '2025-09-26 08:41:56'),
(403, 'admin', NULL, NULL, 'Restored Resident: qbabwa, dwa (ID: 52040)', '2025-09-26 08:44:16'),
(404, 'admin', NULL, NULL, 'Restored Resident: Reyes, Maria (ID: 96613)', '2025-09-26 09:43:00'),
(405, '1', NULL, NULL, 'Deleted Resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-26 10:00:59'),
(406, 'admin', NULL, NULL, 'Restored Resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-26 10:01:37'),
(407, 'admin', NULL, NULL, 'Permanently deleted resident with ID: 96585', '2025-09-26 10:01:58'),
(408, 'admin', NULL, NULL, 'Restored Resident: faafa, fwaeawba (ID: 83929)', '2025-09-26 10:03:32'),
(409, 'admin', NULL, NULL, 'Restored Resident: Reyes, Maria (ID: 96611)', '2025-09-26 10:03:45'),
(410, 'admin', NULL, NULL, 'Permanently deleted resident: Reyes, Maria (ID: 96609)', '2025-09-26 10:06:47'),
(411, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-26 10:15:09'),
(412, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-26 10:15:39'),
(413, 'admin', NULL, NULL, 'Added resident: cong, king (ID: 65756)', '2025-09-26 10:17:21'),
(414, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-26 10:19:51'),
(415, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-26 10:20:12'),
(416, 'admin', NULL, NULL, 'Added resident: Money , Me (ID: 39003)', '2025-09-26 10:21:25'),
(417, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-26 10:22:11'),
(418, 'admin', NULL, NULL, 'Added resident: gy, ads (ID: 63696)', '2025-09-26 10:23:01'),
(419, 'admin', NULL, NULL, 'Added resident: Dela Cruz, Juan (ID: )', '2025-09-26 10:32:44'),
(420, 'admin', NULL, NULL, 'Added resident: Lim, dete (ID: 2500)', '2025-09-26 21:31:28'),
(421, 'admin', NULL, NULL, 'Added resident: Lim, dete (ID: 64797)', '2025-09-26 21:31:36'),
(422, 'admin', NULL, NULL, 'Added resident: Lim, dete (ID: 29350)', '2025-09-26 21:31:40'),
(423, 'admin', NULL, NULL, 'Added resident: gs, sfd (ID: 53565)', '2025-09-26 21:37:25'),
(424, 'admin', NULL, NULL, 'Added resident: sc, sdf (ID: 56081)', '2025-09-26 21:38:25'),
(425, 'admin', NULL, NULL, 'Added resident: Dela ruz, Juan (ID: )', '2025-09-26 21:38:48'),
(426, 'admin', NULL, NULL, 'Added resident: Dela ruz, Juan (ID: )', '2025-09-26 21:42:56'),
(427, 'admin', NULL, NULL, 'Approved certificate request ID: 43', '2025-09-26 22:40:54'),
(428, 'admin', NULL, NULL, 'Sent message to user ID 10775: ssds', '2025-09-26 22:41:24'),
(429, 'admin', '2025-09-26 23:15:24', '2025-09-26 23:15:44', '', NULL),
(430, 'admin', NULL, NULL, 'Sent message to user ID 55305: asda', '2025-09-26 23:15:41'),
(431, 'admin', '2025-09-27 00:19:39', '2025-09-27 00:19:42', '', NULL),
(432, 'admin', '2025-09-27 03:27:59', NULL, '', NULL),
(433, 'admin', '2025-09-27 03:55:30', NULL, '', NULL),
(434, 'admin', '2025-09-27 04:05:09', NULL, '', NULL),
(435, 'admin', '2025-09-27 04:15:20', NULL, '', NULL),
(436, 'admin', '2025-09-27 04:26:53', NULL, '', NULL),
(437, 'admin', '2025-09-27 04:29:04', NULL, '', NULL),
(438, 'admin', NULL, NULL, 'Archived resolved incident report ID 96 (AJAX)', '2025-09-27 04:29:22'),
(439, 'admin', NULL, NULL, 'Deleted incident report ID 98 (AJAX)', '2025-09-27 04:31:54'),
(440, 'admin', NULL, NULL, 'Deleted incident report ID 89 (AJAX)', '2025-09-27 04:31:59'),
(441, 'admin', NULL, NULL, 'Deleted incident report ID 88 (AJAX)', '2025-09-27 04:32:01'),
(442, 'admin', '2025-09-27 09:48:14', NULL, '', NULL),
(443, 'admin', NULL, NULL, 'Deleted incident report ID 90 (AJAX)', '2025-09-27 09:58:08'),
(444, 'admin', '2025-09-27 21:34:03', '2025-09-27 22:34:27', '', NULL),
(445, 'admin', '2025-09-28 21:27:29', NULL, '', NULL),
(446, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:30:24'),
(447, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:31:45'),
(448, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:35:28'),
(449, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:37:19'),
(450, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:38:32'),
(451, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:40:02'),
(452, 'admin', NULL, NULL, 'Edited resident: Angel2, Christian (ID: 57527)', '2025-09-28 21:41:18'),
(453, 'admin', NULL, NULL, 'Edited resident: dalit, jester (ID: 4735)', '2025-09-28 21:41:29'),
(454, 'admin', NULL, NULL, 'Edited resident: cong, king (ID: 65756)', '2025-09-28 21:41:49'),
(455, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:45:15'),
(456, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:45:27'),
(457, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:45:47'),
(458, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:49:03'),
(459, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:49:32'),
(460, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:50:52'),
(461, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:54:53'),
(462, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:55:25'),
(463, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:56:43'),
(464, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:57:00'),
(465, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 21:59:15'),
(466, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:00:54'),
(467, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:01:04'),
(468, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:03:31'),
(469, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:04:56'),
(470, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:05:11'),
(471, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:07:05'),
(472, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:08:57'),
(473, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:09:11'),
(474, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:09:48'),
(475, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:43'),
(476, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:44'),
(477, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:47'),
(478, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:48'),
(479, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:50'),
(480, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:53'),
(481, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:54'),
(482, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:56'),
(483, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:12:58'),
(484, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:13:01'),
(485, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:13:13'),
(486, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:20:13'),
(487, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:36:47'),
(488, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:37:35'),
(489, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:38:50'),
(490, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:39:21'),
(491, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:40:52'),
(492, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:42:40'),
(493, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:43:36'),
(494, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:45:47'),
(495, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:46:18'),
(496, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:48:24'),
(497, 'admin', '2025-09-28 22:49:05', NULL, '', NULL),
(498, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:49:17'),
(499, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:54:05'),
(500, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:55:50'),
(501, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:57:26'),
(502, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:58:06'),
(503, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 22:59:18'),
(504, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:00:28'),
(505, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:00:55'),
(506, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:01:57'),
(507, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:02:07'),
(508, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:03:53'),
(509, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:04:21'),
(510, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:04:59'),
(511, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:06:34'),
(512, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:06:59'),
(513, 'admin', NULL, NULL, 'Edited resident: Dela Cruz, Juan (ID: 96616)', '2025-09-28 23:07:59'),
(514, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:15:00'),
(515, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:15:22'),
(516, 'admin', NULL, NULL, 'Edited resident: Christian, Christian (ID: 57527)', '2025-09-28 23:17:21'),
(517, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:19:15'),
(518, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:24:22'),
(519, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:24:31'),
(520, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:26:10'),
(521, 'admin', NULL, NULL, 'Edited resident: ANGELlll, CHRISTINE (ID: 96585)', '2025-09-28 23:26:35'),
(522, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-28 23:28:54'),
(523, 'admin', '2025-09-29 07:15:22', NULL, '', NULL),
(524, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 07:24:32'),
(525, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 07:25:08'),
(526, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 07:26:17'),
(527, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 07:27:35'),
(528, 'admin', '2025-09-29 08:53:17', '2025-09-29 09:09:36', '', NULL),
(529, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-09-29 08:58:05'),
(530, 'admin', NULL, NULL, 'Printed certificate ID: 41 for resident Christian D miko', '2025-09-29 09:04:13'),
(531, 'admin', NULL, NULL, 'Archived resolved incident report ID 97 (AJAX)', '2025-09-29 09:07:18'),
(532, 'admin', '2025-09-29 09:13:19', '2025-09-29 09:14:18', '', NULL),
(533, 'admin', '2025-09-29 09:17:15', '2025-09-29 09:18:52', '', NULL),
(534, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 09:18:08'),
(535, 'admin', '2025-09-29 09:57:34', '2025-09-29 09:58:35', '', NULL),
(536, 'admin', NULL, NULL, 'Added official: qwwq', '2025-09-29 09:58:14'),
(537, 'admin', NULL, NULL, 'Archived official: qwwq (ID: 8)', '2025-09-29 09:58:14'),
(538, 'admin', '2025-09-29 10:08:47', '2025-09-29 10:09:39', '', NULL),
(539, 'admin', NULL, NULL, 'Added official: Christian Mhico Ronario', '2025-09-29 10:09:15'),
(540, 'admin', NULL, NULL, 'Edited official: Christian Mhico Ronario (ID: 9)', '2025-09-29 10:09:36'),
(541, 'admin', '2025-09-29 10:13:26', NULL, '', NULL),
(542, 'admin', '2025-09-29 21:10:07', NULL, '', NULL),
(543, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 21:27:40'),
(544, 'admin', NULL, NULL, 'Edited resident: Christian, Christian (ID: 57527)', '2025-09-29 21:27:51'),
(545, 'admin', NULL, NULL, 'Restored Resident: Dela Cruz, Juan (ID: 96612)', '2025-09-29 21:28:04'),
(546, 'admin', '2025-09-29 21:44:19', NULL, '', NULL),
(547, 'admin', NULL, NULL, 'Edited resident: cong, king (ID: 65756)', '2025-09-29 21:44:41'),
(548, 'admin', NULL, NULL, 'Edited resident: dalit, jester (ID: 4735)', '2025-09-29 21:45:03'),
(549, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 21:50:03'),
(550, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 21:50:13'),
(551, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 21:53:21'),
(552, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 21:53:41'),
(553, 'admin', '2025-09-29 21:55:59', NULL, '', NULL),
(554, 'admin', '2025-09-29 21:59:50', NULL, '', NULL),
(555, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 22:00:08'),
(556, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-29 22:12:44'),
(557, 'admin', NULL, NULL, 'Edited resident: cong, king (ID: 65756)', '2025-09-29 22:12:59'),
(558, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-09-29 22:23:46'),
(559, 'admin', NULL, NULL, 'Added resident: sw, wad (ID: 35278)', '2025-09-29 22:24:26'),
(560, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-09-29 22:33:35'),
(561, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-09-29 22:35:08'),
(562, 'admin', NULL, NULL, 'Added resident: s, ss (ID: 56851)', '2025-09-29 22:39:45'),
(563, 'admin', '2025-09-30 11:14:42', '2025-09-30 11:58:37', '', NULL),
(564, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-09-30 11:45:46'),
(565, 'admin', '2025-09-30 11:58:43', '2025-09-30 12:09:49', '', NULL),
(566, 'admin', '2025-09-30 12:27:55', '2025-09-30 13:44:57', '', NULL),
(567, 'admin', NULL, NULL, 'Approved certificate request ID: 38', '2025-09-30 13:42:54'),
(568, '1234', '2025-09-30 13:45:12', '2025-09-30 16:45:23', '', NULL),
(569, 'admin', '2025-09-30 19:53:52', '2025-09-30 20:00:30', '', NULL),
(570, 'admin', '2025-09-30 21:36:38', '2025-09-30 21:43:38', '', NULL),
(571, 'admin', '2025-09-30 21:44:41', '2025-09-30 21:45:50', '', NULL),
(572, 'admin', '2025-09-30 21:47:17', NULL, '', NULL),
(573, 'admin', NULL, NULL, 'Deleted incident report ID 112 (AJAX)', '2025-09-30 21:49:18'),
(574, 'admin', '2025-09-30 21:56:59', '2025-09-30 22:09:22', '', NULL),
(575, 'admin', '2025-09-30 22:09:53', '2025-10-01 16:41:06', '', NULL),
(576, 'admin', '2025-10-01 20:09:12', NULL, '', NULL),
(577, 'admin', '2025-10-02 18:38:04', '2025-10-02 18:54:35', '', NULL),
(578, 'admin', NULL, NULL, 'Added resident: Baritua , Ivan (ID: 65021)', '2025-10-02 18:49:22'),
(579, 'admin', NULL, NULL, 'Added resident: Baritua , Ivan (ID: 71856)', '2025-10-02 18:49:28'),
(580, 'admin', NULL, NULL, 'Added resident: Baritua , Ivan (ID: 51063)', '2025-10-02 18:49:30'),
(581, 'admin', '2025-10-02 18:56:02', '2025-10-02 18:57:49', '', NULL),
(582, 'admin', '2025-10-02 18:58:27', '2025-10-02 19:09:20', '', NULL),
(583, 'admin', '2025-10-02 19:09:27', '2025-10-02 19:19:56', '', NULL),
(584, 'admin', NULL, NULL, 'Sent message to user ID 96585: sdad', '2025-10-02 19:18:41');
INSERT INTO `admin_logs` (`id`, `username`, `login_time`, `logout_time`, `action`, `action_time`) VALUES
(585, 'admin', '2025-10-02 19:21:22', '2025-10-02 19:22:52', '', NULL),
(586, 'admin', '2025-10-02 19:23:37', '2025-10-02 19:53:13', '', NULL),
(587, 'admin', NULL, NULL, 'Sent message to user ID 55305: eef', '2025-10-02 19:24:37'),
(588, 'admin', '2025-10-03 14:03:03', NULL, '', NULL),
(589, 'admin', '2025-10-03 16:05:28', '2025-10-03 16:05:35', '', NULL),
(590, 'admin', '2025-10-03 16:06:57', '2025-10-03 17:58:50', '', NULL),
(591, '12345', '2025-10-03 17:58:57', '2025-10-03 18:19:43', '', NULL),
(592, 'admin', '2025-10-03 18:20:48', '2025-10-03 20:48:37', '', NULL),
(593, 'admin', NULL, NULL, 'Approved certificate request ID: 45', '2025-10-03 18:27:13'),
(594, 'admin', NULL, NULL, 'Printed certificate ID: 43 for resident Christian miko rona', '2025-10-03 18:43:08'),
(595, '12345', '2025-10-03 20:48:49', '2025-10-03 21:14:25', '', NULL),
(596, 'admin', '2025-10-03 21:15:29', '2025-10-03 21:24:57', '', NULL),
(597, 'admin', '2025-10-03 21:28:09', '2025-10-03 21:28:48', '', NULL),
(598, 'admin', NULL, NULL, 'Approved certificate request ID: 47', '2025-10-03 21:28:19'),
(599, 'admin', NULL, NULL, 'Printed certificate ID: 47 for resident mico d ronario', '2025-10-03 21:28:30'),
(600, 'admin', '2025-10-03 21:31:29', '2025-10-03 21:31:43', '', NULL),
(601, 'admin', NULL, NULL, 'Archived certificate request ID: 47 (status Printed)', '2025-10-03 21:31:41'),
(602, 'admin', '2025-10-04 06:27:01', '2025-10-04 06:28:47', '', NULL),
(603, 'admin', '2025-10-04 06:30:17', NULL, '', NULL),
(604, 'admin', '2025-10-05 08:51:55', '2025-10-05 08:52:08', '', NULL),
(605, 'admin', NULL, NULL, 'Sent message to user ID 55305: ablsbjkfbjaflnka', '2025-10-05 08:52:04'),
(606, 'admin', NULL, NULL, 'Sent message to user ID 55305: afafsf', '2025-10-05 08:52:06'),
(607, 'admin', '2025-10-05 08:57:59', '2025-10-05 08:58:18', '', NULL),
(608, 'admin', NULL, NULL, 'Sent message to user ID 55305: helooo po', '2025-10-05 08:58:12'),
(609, 'admin', NULL, NULL, 'Sent message to user ID 55305: gddg', '2025-10-05 08:58:16'),
(610, 'admin', '2025-10-05 09:00:15', '2025-10-05 09:03:00', '', NULL),
(611, 'admin', NULL, NULL, 'Sent message to user ID 55305: adasda', '2025-10-05 09:00:22'),
(612, 'admin', NULL, NULL, 'Sent message to user ID 55305: xvxxfb', '2025-10-05 09:01:41'),
(613, 'admin', NULL, NULL, 'Sent message to user ID 96585: adfsf', '2025-10-05 09:02:09'),
(614, 'admin', '2025-10-05 09:03:34', '2025-10-05 09:13:12', '', NULL),
(615, 'admin', NULL, NULL, 'Sent message to user ID 55305: fsdsfdsfd', '2025-10-05 09:04:40'),
(616, 'admin', NULL, NULL, 'Sent message to user ID 55305: bfdbdb', '2025-10-05 09:06:34'),
(617, 'admin', NULL, NULL, 'Sent message to user ID 96585: esfse', '2025-10-05 09:12:41'),
(618, 'admin', NULL, NULL, 'Sent message to user ID 96585: 123', '2025-10-05 09:12:44'),
(619, 'admin', NULL, NULL, 'Sent message to user ID 55305: helooo', '2025-10-05 09:13:06'),
(620, 'admin', NULL, NULL, 'Sent message to user ID 55305: 123', '2025-10-05 09:13:11'),
(621, 'admin', '2025-10-05 09:16:25', '2025-10-05 09:16:38', '', NULL),
(622, 'admin', NULL, NULL, 'Sent message to user ID 55305: sdffsdg', '2025-10-05 09:16:32'),
(623, 'admin', NULL, NULL, 'Sent message to user ID 55305: df', '2025-10-05 09:16:36'),
(624, 'admin', '2025-10-05 09:44:54', '2025-10-05 09:47:19', '', NULL),
(625, 'admin', NULL, NULL, 'Sent message to user ID 55305: userrr', '2025-10-05 09:47:13'),
(626, 'admin', NULL, NULL, 'Sent message to user ID 55305: helooo', '2025-10-05 09:47:18'),
(627, 'admin', '2025-10-05 09:54:28', '2025-10-05 09:54:45', '', NULL),
(628, 'admin', NULL, NULL, 'Sent message to user ID 55305: ds', '2025-10-05 09:54:39'),
(629, 'admin', '2025-10-05 09:56:25', '2025-10-05 09:58:28', '', NULL),
(630, 'admin', '2025-10-05 09:59:23', '2025-10-05 10:00:35', '', NULL),
(631, 'admin', '2025-10-05 14:48:41', '2025-10-05 14:52:02', '', NULL),
(632, 'admin', '2025-10-06 06:52:42', '2025-10-06 06:57:53', '', NULL),
(633, 'admin', NULL, NULL, 'Sent message to user ID 55305: it fixxx', '2025-10-06 06:52:52'),
(634, 'admin', NULL, NULL, 'Sent message to user ID 55305: heyy', '2025-10-06 06:52:58'),
(635, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96616)', '2025-10-06 06:53:31'),
(636, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96617)', '2025-10-06 06:53:44'),
(637, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96618)', '2025-10-06 06:54:02'),
(638, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96619)', '2025-10-06 06:54:04'),
(639, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96620)', '2025-10-06 06:54:07'),
(640, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96621)', '2025-10-06 06:54:09'),
(641, '1', NULL, NULL, 'Deleted Resident: Dela ruz, Juan (ID: 96622)', '2025-10-06 06:54:11'),
(642, '1', NULL, NULL, 'Deleted Resident: Dela ruz, Juan (ID: 96623)', '2025-10-06 06:54:13'),
(643, '1', NULL, NULL, 'Deleted Resident: gs, sfd (ID: 53565)', '2025-10-06 06:54:20'),
(644, '1', NULL, NULL, 'Deleted Resident: faafa, fwaeawba (ID: 83929)', '2025-10-06 06:54:22'),
(645, '1', NULL, NULL, 'Deleted Resident: gy, ads (ID: 63696)', '2025-10-06 06:54:24'),
(646, '1', NULL, NULL, 'Deleted Resident: Dela Cruz, Juan (ID: 96612)', '2025-10-06 06:54:30'),
(647, '1', NULL, NULL, 'Deleted Resident: Dela ruz, Juan (ID: 96614)', '2025-10-06 06:54:32'),
(648, '1', NULL, NULL, 'Deleted Resident: Dela ruz, Juan (ID: 96615)', '2025-10-06 06:54:36'),
(649, '1', NULL, NULL, 'Deleted Resident: Lim, dete (ID: 64797)', '2025-10-06 06:54:45'),
(650, '1', NULL, NULL, 'Deleted Resident: Lim, dete (ID: 29350)', '2025-10-06 06:54:49'),
(651, '1', NULL, NULL, 'Deleted Resident: qbabwa, dwa (ID: 52040)', '2025-10-06 06:55:00'),
(652, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96626)', '2025-10-06 06:55:04'),
(653, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96624)', '2025-10-06 06:55:06'),
(654, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96625)', '2025-10-06 06:55:15'),
(655, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96627)', '2025-10-06 06:55:19'),
(656, '1', NULL, NULL, 'Deleted Resident: s, ss (ID: 56851)', '2025-10-06 06:55:28'),
(657, '1', NULL, NULL, 'Deleted Resident: sc, sdf (ID: 56081)', '2025-10-06 06:55:30'),
(658, '1', NULL, NULL, 'Deleted Resident: sw, wad (ID: 35278)', '2025-10-06 06:55:33'),
(659, 'admin', '2025-10-06 07:02:13', '2025-10-06 07:02:43', '', NULL),
(660, 'admin', NULL, NULL, 'Sent message to user ID 55305: helooo', '2025-10-06 07:02:26'),
(661, 'admin', '2025-10-06 08:25:59', '2025-10-06 08:27:12', '', NULL),
(662, 'admin', '2025-10-06 10:11:04', '2025-10-06 12:19:28', '', NULL),
(663, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 10:12:51'),
(664, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 10:52:35'),
(665, 'admin', NULL, NULL, 'Archived resolved incident report ID 95 (AJAX)', '2025-10-06 10:52:55'),
(666, 'admin', NULL, NULL, 'Archived resolved incident report ID 94 (AJAX)', '2025-10-06 11:02:25'),
(667, 'admin', NULL, NULL, 'Archived resolved incident report ID 99 (AJAX)', '2025-10-06 11:02:27'),
(668, 'admin', NULL, NULL, 'Archived resolved incident report ID 118 (AJAX)', '2025-10-06 11:13:48'),
(669, '1234', '2025-10-06 12:19:36', NULL, '', NULL),
(670, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:28:54'),
(671, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:31:09'),
(672, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:31:28'),
(673, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:31:39'),
(674, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:31:45'),
(675, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:32:28'),
(676, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:34:26'),
(677, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:34:26'),
(678, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:34:49'),
(679, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:35:01'),
(680, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:38:26'),
(681, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:38:41'),
(682, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:40:54'),
(683, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:47:15'),
(684, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:48:54'),
(685, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:51:31'),
(686, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:52:53'),
(687, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:54:57'),
(688, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:55:11'),
(689, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 12:56:45'),
(690, '1234', '2025-10-06 12:59:03', NULL, '', NULL),
(691, '1234', '2025-10-06 13:00:31', NULL, '', NULL),
(692, '1234', '2025-10-06 13:02:38', '2025-10-06 13:07:01', '', NULL),
(693, '1234', '2025-10-06 13:11:34', '2025-10-06 13:40:31', '', NULL),
(694, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 13:11:49'),
(695, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 13:13:53'),
(696, 'admin', '2025-10-06 13:40:40', '2025-10-06 14:45:10', '', NULL),
(697, 'admin', NULL, NULL, 'Added resident: 21, 21 (ID: 66629)', '2025-10-06 13:49:41'),
(698, 'admin', NULL, NULL, 'Added resident: 21, 21 (ID: 1555)', '2025-10-06 13:49:49'),
(699, 'admin', NULL, NULL, 'Edited resident: 21, 21 (ID: 1555)', '2025-10-06 14:09:34'),
(700, 'admin', NULL, NULL, 'Added resident: sfsd, 123 (ID: 96652)', '2025-10-06 14:13:24'),
(701, 'admin', NULL, NULL, 'Added resident: sfsd, 123 (ID: 96653)', '2025-10-06 14:13:28'),
(702, '1', NULL, NULL, 'Deleted Resident: 21, 21 (ID: 1555)', '2025-10-06 14:13:48'),
(703, '1', NULL, NULL, 'Deleted Resident: 21, 21 (ID: 66629)', '2025-10-06 14:13:52'),
(704, '1', NULL, NULL, 'Deleted Resident: Baritua , Ivan (ID: 51063)', '2025-10-06 14:14:09'),
(705, '1', NULL, NULL, 'Deleted Resident: Baritua , Ivan (ID: 71856)', '2025-10-06 14:14:11'),
(706, '1', NULL, NULL, 'Deleted Resident: Baritua , Ivan (ID: 65021)', '2025-10-06 14:14:13'),
(707, 'admin', NULL, NULL, 'Added resident: heyy, hh (ID: 96654)', '2025-10-06 14:16:19'),
(708, 'admin', NULL, NULL, 'Added resident: fff, fff (ID: 96655)', '2025-10-06 14:18:29'),
(709, 'admin', NULL, NULL, 'Added resident: Pizano, aliyo (ID: 96656)', '2025-10-06 14:36:33'),
(710, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 14:37:35'),
(711, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-06 14:43:08'),
(712, 'admin', '2025-10-06 14:57:32', NULL, '', NULL),
(713, 'admin', NULL, NULL, 'Archived resolved incident report ID 121 (AJAX)', '2025-10-06 15:16:59'),
(714, 'admin', NULL, NULL, 'Archived resolved incident report ID 120 (AJAX)', '2025-10-06 15:17:02'),
(715, 'admin', NULL, NULL, 'Archived resolved incident report ID 119 (AJAX)', '2025-10-06 15:17:04'),
(716, 'admin', NULL, NULL, 'Archived resolved incident report ID 117 (AJAX)', '2025-10-06 15:21:32'),
(717, 'admin', NULL, NULL, 'Archived resolved incident report ID 113 (AJAX)', '2025-10-06 15:21:35'),
(718, 'admin', NULL, NULL, 'Archived resolved incident report ID 116 (AJAX)', '2025-10-06 15:21:48'),
(719, 'admin', NULL, NULL, 'Archived resolved incident report ID 93 (AJAX)', '2025-10-06 15:22:44'),
(720, 'admin', NULL, NULL, 'Archived resolved incident report ID 101 (AJAX)', '2025-10-06 15:26:31'),
(721, 'admin', NULL, NULL, 'Archived resolved incident report ID 105 (AJAX)', '2025-10-06 15:26:36'),
(722, 'admin', NULL, NULL, 'Archived resolved incident report ID 83 (AJAX)', '2025-10-06 15:26:41'),
(723, 'admin', NULL, NULL, 'Archived resolved incident report ID 86 (AJAX)', '2025-10-06 15:28:15'),
(724, 'admin', NULL, NULL, 'Archived resolved incident report ID 91 (AJAX)', '2025-10-06 15:28:37'),
(725, 'admin', NULL, NULL, 'Archived resolved incident report ID 84 (AJAX)', '2025-10-06 15:31:52'),
(726, 'admin', NULL, NULL, 'Archived resolved incident report ID 115 (AJAX)', '2025-10-06 15:33:05'),
(727, 'admin', NULL, NULL, 'Archived resolved incident report ID 114 (AJAX)', '2025-10-06 16:09:58'),
(728, 'admin', NULL, NULL, 'Archived resolved incident report ID 85 (AJAX)', '2025-10-06 16:10:16'),
(729, 'admin', NULL, NULL, 'Archived resolved incident report ID 111 (AJAX)', '2025-10-06 16:22:49'),
(730, 'admin', NULL, NULL, 'Archived resolved incident report ID 110 (AJAX)', '2025-10-06 16:23:13'),
(731, 'admin', '2025-10-06 16:25:37', '2025-10-07 07:15:27', '', NULL),
(732, 'admin', NULL, NULL, 'Archived resolved incident report ID 109 (AJAX)', '2025-10-06 16:36:15'),
(733, 'admin', NULL, NULL, 'Archived certificate request ID: 43 (status Printed)', '2025-10-07 06:54:44'),
(734, 'admin', '2025-10-07 07:15:35', '2025-10-07 07:15:37', '', NULL),
(735, 'admin', '2025-10-07 07:16:40', '2025-10-07 07:20:46', '', NULL),
(736, 'admin', '2025-10-07 07:21:56', '2025-10-07 07:24:58', '', NULL),
(737, 'admin', '2025-10-07 07:25:47', '2025-10-07 07:26:41', '', NULL),
(738, '12345', '2025-10-07 07:26:52', '2025-10-07 08:54:39', '', NULL),
(739, '12345', NULL, NULL, 'Archived resolved incident report ID 124 (AJAX)', '2025-10-07 08:15:03'),
(740, '12345', NULL, NULL, 'Deleted incident report ID 41 (AJAX)', '2025-10-07 08:20:14'),
(741, '12345', NULL, NULL, 'Deleted incident report ID 61 (AJAX)', '2025-10-07 08:20:16'),
(742, '12345', NULL, NULL, 'Deleted incident report ID 59 (AJAX)', '2025-10-07 08:20:18'),
(743, '12345', NULL, NULL, 'Deleted incident report ID 58 (AJAX)', '2025-10-07 08:20:20'),
(744, '12345', NULL, NULL, 'Deleted incident report ID 57 (AJAX)', '2025-10-07 08:20:22'),
(745, '12345', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-07 08:28:26'),
(746, '12345', NULL, NULL, 'Deleted Resident: heyy, hh (ID: 96654)', '2025-10-07 08:31:26'),
(747, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96628)', '2025-10-07 08:31:36'),
(748, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96629)', '2025-10-07 08:31:39'),
(749, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96630)', '2025-10-07 08:31:41'),
(750, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96631)', '2025-10-07 08:31:43'),
(751, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96632)', '2025-10-07 08:31:45'),
(752, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96633)', '2025-10-07 08:31:49'),
(753, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96635)', '2025-10-07 08:31:51'),
(754, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96637)', '2025-10-07 08:31:53'),
(755, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96636)', '2025-10-07 08:31:55'),
(756, '12345', NULL, NULL, 'Deleted Resident: fff, fff (ID: 96655)', '2025-10-07 08:33:29'),
(757, '12345', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96611)', '2025-10-07 08:33:36'),
(758, '12345', NULL, NULL, 'Deleted Resident: Reyes, Maria (ID: 96613)', '2025-10-07 08:33:39'),
(759, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96634)', '2025-10-07 08:33:46'),
(760, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96638)', '2025-10-07 08:33:48'),
(761, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96639)', '2025-10-07 08:33:50'),
(762, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96640)', '2025-10-07 08:33:52'),
(763, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96641)', '2025-10-07 08:33:55'),
(764, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96642)', '2025-10-07 08:33:56'),
(765, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96643)', '2025-10-07 08:33:58'),
(766, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96644)', '2025-10-07 08:34:00'),
(767, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96645)', '2025-10-07 08:34:01'),
(768, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96646)', '2025-10-07 08:34:02'),
(769, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96647)', '2025-10-07 08:34:04'),
(770, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96648)', '2025-10-07 08:34:06'),
(771, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96649)', '2025-10-07 08:34:08'),
(772, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96650)', '2025-10-07 08:34:09'),
(773, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96651)', '2025-10-07 08:34:11'),
(774, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96657)', '2025-10-07 08:34:13'),
(775, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96658)', '2025-10-07 08:34:15'),
(776, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96659)', '2025-10-07 08:34:17'),
(777, '12345', NULL, NULL, 'Deleted Resident: sfsd, 123 (ID: 96652)', '2025-10-07 08:34:23'),
(778, '12345', NULL, NULL, 'Deleted Resident: sfsd, 123 (ID: 96653)', '2025-10-07 08:34:25'),
(779, '12345', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-07 08:34:44'),
(780, '12345', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-07 08:39:35'),
(781, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96660)', '2025-10-07 08:40:40'),
(782, '12345', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96661)', '2025-10-07 08:40:43'),
(783, '12345', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-07 08:44:20'),
(784, '12345', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-07 08:51:01'),
(785, '12345', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-07 08:51:16'),
(786, 'admin', '2025-10-07 08:54:53', '2025-10-07 08:56:13', '', NULL),
(787, 'admin', '2025-10-07 10:33:10', '2025-10-07 10:46:39', '', NULL),
(788, 'admin', NULL, NULL, 'Deleted suggestion ID 11 (Subject: street llights)', '2025-10-07 10:33:55'),
(789, 'admin', NULL, NULL, 'Added announcement: WALANG PASOK (ID: ann_68e47ce945e427.16405958)', '2025-10-07 10:37:29'),
(790, 'admin', NULL, NULL, 'Added announcement: sad (ID: ann_68e47d05271218.74641287)', '2025-10-07 10:37:57'),
(791, 'admin', NULL, NULL, 'Deleted announcement: sad (ID: ann_68e47d05271218.74641287)', '2025-10-07 10:38:02'),
(792, 'admin', NULL, NULL, 'Edited announcement: WALANG PASOK (ID: ann_68e47ce945e427.16405958)', '2025-10-07 10:39:47'),
(793, 'admin', NULL, NULL, 'Edited announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-10-07 10:39:54'),
(794, 'admin', '2025-10-07 15:43:04', '2025-10-07 15:54:28', '', NULL),
(795, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:47:15'),
(796, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:50:53'),
(797, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:50:57'),
(798, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:52:40'),
(799, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:52:58'),
(800, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:53:01'),
(801, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-07 15:53:17'),
(802, 'admin', '2025-10-09 03:17:43', '2025-10-09 09:02:38', '', NULL),
(803, 'admin', '2025-10-09 21:12:34', '2025-10-10 09:03:13', '', NULL),
(804, '1234', '2025-10-10 09:06:49', '2025-10-10 10:15:20', '', NULL),
(805, '1234', NULL, NULL, 'Archived resolved incident report ID 132 (AJAX)', '2025-10-10 09:11:27'),
(806, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:16:45'),
(807, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:23:51'),
(808, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:33:35'),
(809, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:36:03'),
(810, '1234', NULL, NULL, 'Added resident: Bello, Ronak (ID: 96669)', '2025-10-10 09:37:13'),
(811, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:43:23'),
(812, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96666)', '2025-10-10 09:43:39'),
(813, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96667)', '2025-10-10 09:43:41'),
(814, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96665)', '2025-10-10 09:43:43'),
(815, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96670)', '2025-10-10 09:43:45'),
(816, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:45:40'),
(817, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:46:21'),
(818, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:47:44'),
(819, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:48:19'),
(820, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:48:38'),
(821, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:50:27'),
(822, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:55:24'),
(823, '1234', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 09:55:50'),
(824, '1234', NULL, NULL, 'Added resident: MAX, thr (ID: 96679)', '2025-10-10 09:59:52'),
(825, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96678)', '2025-10-10 10:00:32'),
(826, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96677)', '2025-10-10 10:00:40'),
(827, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96671)', '2025-10-10 10:00:44'),
(828, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96672)', '2025-10-10 10:00:45'),
(829, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96673)', '2025-10-10 10:00:47'),
(830, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96674)', '2025-10-10 10:00:48'),
(831, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96675)', '2025-10-10 10:00:50'),
(832, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96676)', '2025-10-10 10:00:52'),
(833, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96662)', '2025-10-10 10:01:02'),
(834, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96663)', '2025-10-10 10:01:04'),
(835, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96664)', '2025-10-10 10:01:06'),
(836, '1234', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96668)', '2025-10-10 10:01:08'),
(837, '1234', NULL, NULL, 'Deleted Resident: MAX, thr (ID: 96679)', '2025-10-10 10:01:21'),
(838, '1234', NULL, NULL, 'Reset password for User ID: 9692', '2025-10-10 10:02:37'),
(839, '1234', NULL, NULL, 'Reset password for User ID: 55305', '2025-10-10 10:03:29'),
(840, '1234', NULL, NULL, 'Reset password for User ID: 55305', '2025-10-10 10:08:04'),
(841, '1234', NULL, NULL, 'Reset password for User ID: 10775', '2025-10-10 10:10:13'),
(842, '1234', NULL, NULL, 'Reset password for User ID: 55305', '2025-10-10 10:14:58'),
(843, 'admin', '2025-10-10 10:15:32', '2025-10-10 12:14:48', '', NULL),
(844, 'admin', NULL, NULL, 'Registered new admin: 57234', '2025-10-10 10:18:21'),
(845, 'admin', NULL, NULL, 'Deleted admin: 1111', '2025-10-10 10:21:42'),
(846, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-10 10:21:53'),
(847, 'admin', NULL, NULL, 'Sent message to user ID 55305: helloo', '2025-10-10 10:30:40'),
(848, 'admin', NULL, NULL, 'Permanently deleted resident: MAX, thr (ID: 96679)', '2025-10-10 11:00:03'),
(849, 'admin', '2025-10-10 13:38:31', '2025-10-10 13:44:01', '', NULL),
(850, 'admin', NULL, NULL, 'Deleted suggestion ID 12 (Subject: HELOOOO)', '2025-10-10 13:41:58'),
(851, 'admin', NULL, NULL, 'Sent message to user ID 55305: helloo', '2025-10-10 13:43:19'),
(852, 'admin', NULL, NULL, 'Sent message to user ID 55305: WHat can i help u', '2025-10-10 13:43:35'),
(853, 'admin', '2025-10-10 15:05:40', '2025-10-10 15:06:20', '', NULL),
(854, 'admin', '2025-10-10 18:05:43', '2025-10-10 22:55:47', '', NULL),
(855, 'admin', NULL, NULL, 'Edited announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-10-10 18:13:21'),
(856, 'admin', NULL, NULL, 'Edited announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-10-10 18:13:27'),
(857, 'admin', NULL, NULL, 'Edited announcement: WALANG PASOK (ID: ann_68e47ce945e427.16405958)', '2025-10-10 18:13:36'),
(858, 'admin', NULL, NULL, 'Sent message to user ID 55305: heloo', '2025-10-10 19:14:42'),
(859, 'admin', NULL, NULL, 'Sent message to user ID 55305: hrloo', '2025-10-10 19:14:46'),
(860, 'admin', NULL, NULL, 'Archived announcement: F Y I📣 (ID: ann_68b56ff52e6297.20159339)', '2025-10-10 21:13:40'),
(861, 'admin', NULL, NULL, 'Added announcement: afaf (ID: ann_68e9075ac95906.47428847, affected rows: 1)', '2025-10-10 21:17:14'),
(862, 'admin', NULL, NULL, 'Edited announcement: afaf (ID: ann_68e9075ac95906.47428847)', '2025-10-10 21:17:29'),
(863, 'admin', NULL, NULL, 'Edited announcement: afaf (ID: ann_68e9075ac95906.47428847)', '2025-10-10 21:18:44'),
(864, 'admin', NULL, NULL, 'Deleted announcement: afaf (ID: ann_68e9075ac95906.47428847)', '2025-10-10 21:22:23'),
(865, 'admin', NULL, NULL, 'Archived resolved incident report ID 136 (AJAX)', '2025-10-10 21:37:14'),
(866, 'admin', NULL, NULL, 'Added official: Christian Mhico Ronario', '2025-10-10 21:38:04'),
(867, 'admin', NULL, NULL, 'Edited official: Christian Mhico Ronario (ID: 10)', '2025-10-10 21:38:20'),
(868, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-10 21:39:36'),
(869, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-10 21:40:16'),
(870, 'admin', NULL, NULL, 'Edited official: Christian Mhico Ronario (ID: 9)', '2025-10-10 21:41:27'),
(871, 'admin', NULL, NULL, 'Deleted official: Christian Mhico Ronario (ID: 9)', '2025-10-10 21:41:37'),
(872, 'admin', NULL, NULL, 'Deleted official:  (ID: 9)', '2025-10-10 21:41:54'),
(873, 'admin', NULL, NULL, 'Deleted official: Christian Mhico Ronario (ID: 10)', '2025-10-10 21:43:09'),
(874, 'admin', NULL, NULL, 'Deleted archived official: qwwq (ID: 2)', '2025-10-10 21:43:28'),
(875, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 21:45:24'),
(876, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 21:53:18'),
(877, 'admin', NULL, NULL, 'Added resident: heii, adwa (ID: 96682)', '2025-10-10 21:54:16'),
(878, '1', NULL, NULL, 'Deleted Resident: heii, adwa (ID: 96682)', '2025-10-10 21:54:39'),
(879, 'admin', NULL, NULL, 'Sent message to user ID 10775: sd', '2025-10-10 21:54:58'),
(880, 'admin', NULL, NULL, 'Deleted suggestion ID 16 (Subject: fsd)', '2025-10-10 21:55:06'),
(881, 'admin', NULL, NULL, 'Deleted suggestion ID 13 (Subject: we)', '2025-10-10 21:56:33'),
(882, 'admin', NULL, NULL, 'Reset password for User ID: 55305', '2025-10-10 21:56:41'),
(883, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-10 21:57:33'),
(884, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96680)', '2025-10-10 21:57:57'),
(885, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96681)', '2025-10-10 21:58:00'),
(886, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96683)', '2025-10-10 21:58:02'),
(887, 'admin', NULL, NULL, 'Reset password for User ID: 55305', '2025-10-10 21:59:02'),
(888, 'admin', NULL, NULL, 'Reset password for User ID: 55305', '2025-10-10 22:00:35'),
(889, 'admin', NULL, NULL, 'Registered new admin: wefwg', '2025-10-10 22:01:03'),
(890, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-10 22:01:18'),
(891, 'admin', NULL, NULL, 'Deleted admin: 0', '2025-10-10 22:01:28'),
(892, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-10 22:04:38'),
(893, 'admin', NULL, NULL, 'Deleted admin: 57234', '2025-10-10 22:04:51'),
(894, 'admin', NULL, NULL, 'Approved certificate request ID: 44', '2025-10-10 22:08:03'),
(895, 'admin', NULL, NULL, 'Rejected & Archived certificate request ID: 46', '2025-10-10 22:08:12'),
(896, 'admin', NULL, NULL, 'Printed certificate request ID: 45', '2025-10-10 22:12:52'),
(897, 'admin', NULL, NULL, 'Printed certificate ID: 45 for resident Christian D miko', '2025-10-10 22:12:52'),
(898, 'admin', NULL, NULL, 'Printed certificate ID: 44 for resident Christian D miko', '2025-10-10 22:13:07'),
(899, 'admin', NULL, NULL, 'Printed certificate request ID: 44', '2025-10-10 22:13:07'),
(900, 'admin', NULL, NULL, 'Approved certificate request ID: 49', '2025-10-10 22:13:21'),
(901, 'admin', NULL, NULL, 'Deleted incident report ID 129 (AJAX)', '2025-10-10 22:13:50'),
(902, 'admin', NULL, NULL, 'Sent message to user ID 55305: hyyy', '2025-10-10 22:19:18'),
(903, 'admin', NULL, NULL, 'Sent message to user ID 55305: hey', '2025-10-10 22:21:08'),
(904, 'admin', NULL, NULL, 'Sent message to user ID 55305: hii', '2025-10-10 22:28:27'),
(905, 'admin', NULL, NULL, 'Sent message to user ID 96585: as', '2025-10-10 22:29:54'),
(906, 'admin', NULL, NULL, 'Archived announcement: August 30, 2025 (ID: ann_68b5707138d2c2.74464599)', '2025-10-10 22:40:25'),
(907, 'admin', NULL, NULL, 'Archived announcement: August 30, 2025 (ID: ann_68b5707138d2c2.74464599)', '2025-10-10 22:42:08'),
(908, 'admin', NULL, NULL, 'Archived announcement: August 30, 2025 (ID: ann_68b5707138d2c2.74464599)', '2025-10-10 22:43:48'),
(909, 'admin', NULL, NULL, 'Archived announcement: August 30, 2025 (ID: ann_68b5707138d2c2.74464599)', '2025-10-10 22:44:05'),
(910, 'admin', '2025-10-10 23:03:19', '2025-10-11 02:14:37', '', NULL),
(911, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96683)', '2025-10-11 02:05:33'),
(912, 'admin', NULL, NULL, 'Sent message to user ID 55305: gjh', '2025-10-11 02:12:19'),
(913, 'admin', '2025-10-11 02:22:54', '2025-10-11 02:23:42', '', NULL),
(914, 'admin', '2025-10-11 02:25:21', NULL, '', NULL),
(915, '1', NULL, NULL, 'Deleted Resident: ronario, mico (ID: 83127)', '2025-10-11 02:25:44'),
(916, '1', NULL, NULL, 'Deleted Resident: ronario, saddii (ID: 43640)', '2025-10-11 02:25:48'),
(917, 'admin', '2025-10-11 02:26:32', '2025-10-11 02:28:28', '', NULL),
(918, 'admin', '2025-10-11 02:29:13', '2025-10-11 21:40:27', '', NULL),
(919, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:31:36'),
(920, 'admin', NULL, NULL, 'Edited resident: Bello, Ronak (ID: 96669)', '2025-10-11 02:31:52'),
(921, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:34:13'),
(922, 'admin', NULL, NULL, 'Edited resident: De jesus, Angel (ID: 68018)', '2025-10-11 02:34:51'),
(923, 'admin', NULL, NULL, 'Edited resident: Doe, John  (ID: 46365)', '2025-10-11 02:35:29'),
(924, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:55'),
(925, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:57'),
(926, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:57'),
(927, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:58'),
(928, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:58'),
(929, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:58'),
(930, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:58'),
(931, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:59'),
(932, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:59'),
(933, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:36:59'),
(934, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:38:07'),
(935, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:40:04'),
(936, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:43:38'),
(937, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:43:43'),
(938, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:43:52'),
(939, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:44:05'),
(940, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:44:38'),
(941, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:45:29'),
(942, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:45:58'),
(943, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:46:05'),
(944, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:47:27'),
(945, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:47:37'),
(946, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-11 02:48:01'),
(947, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-11 02:49:10'),
(948, '1', NULL, NULL, 'Deleted Resident: Juan, dan (ID: 68674)', '2025-10-11 02:50:35'),
(949, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Christian (ID: 9692)', '2025-10-11 02:51:53'),
(950, 'admin', NULL, NULL, 'Permanently deleted resident: Juan, dan (ID: 68674)', '2025-10-11 02:52:02'),
(951, '1', NULL, NULL, 'Deleted Resident: Bello, Ronak (ID: 96669)', '2025-10-11 02:52:31'),
(952, 'admin', NULL, NULL, 'Restored Resident: Bello, Ronak (ID: 96669)', '2025-10-11 02:52:49'),
(953, '1', NULL, NULL, 'Deleted Resident: Juan53252, Christian (ID: 13833)', '2025-10-11 02:55:36'),
(954, '1', NULL, NULL, 'Deleted Resident: De jesus, Angel (ID: 68018)', '2025-10-11 02:58:22'),
(955, '1', NULL, NULL, 'Deleted Resident: jokenow, ejay (ID: 91530)', '2025-10-11 03:00:11'),
(956, '1', NULL, NULL, 'Deleted Resident: Lim, dete (ID: 2500)', '2025-10-11 03:00:14'),
(957, '1', NULL, NULL, 'Deleted Resident: cong, king (ID: 65756)', '2025-10-11 03:00:19'),
(958, 'admin', NULL, NULL, 'Added resident: Sabbii, Jeromi (ID: 96684)', '2025-10-11 03:01:54'),
(959, 'admin', NULL, NULL, 'Edited resident: tanag, Ivan (ID: 92718)', '2025-10-11 03:02:29'),
(960, 'admin', NULL, NULL, 'Archived resolved incident report ID 102 (AJAX)', '2025-10-11 03:02:57'),
(961, 'admin', NULL, NULL, 'Archived resolved incident report ID 128 (AJAX)', '2025-10-11 03:06:07'),
(962, 'admin', NULL, NULL, 'Deleted incident report ID 131 (AJAX)', '2025-10-11 03:10:24'),
(963, 'admin', NULL, NULL, 'Added official: we', '2025-10-11 03:10:59'),
(964, 'admin', NULL, NULL, 'Deleted official: we (ID: 11)', '2025-10-11 03:11:03'),
(965, 'admin', NULL, NULL, 'Deleted admin: 4572', '2025-10-11 03:13:45'),
(966, 'admin', NULL, NULL, 'Edited resident: Doe, John  (ID: 46365)', '2025-10-11 07:38:14'),
(967, 'admin', NULL, NULL, 'Archived resolved incident report ID 107 (AJAX)', '2025-10-11 07:38:30'),
(968, 'admin', NULL, NULL, 'Deleted announcement: \"Anti-dengue\" (ID: ann_68bc153187fc47.54685504)', '2025-10-11 20:55:18'),
(969, 'admin', NULL, NULL, 'Deleted announcement: August 30, 2025 (ID: ann_68b5707138d2c2.74464599)', '2025-10-11 20:56:38'),
(970, 'admin', NULL, NULL, 'Added announcement: qwd (ID: ann_68ea54ad3fbb01.54048386, affected rows: 1)', '2025-10-11 20:59:25'),
(971, 'admin', NULL, NULL, 'Added announcement: qwd (ID: ann_68ea54b63711d2.87508315, affected rows: 1)', '2025-10-11 20:59:34'),
(972, 'admin', NULL, NULL, 'Added announcement: qwd (ID: ann_68ea54c2a78bb0.64756968, affected rows: 1)', '2025-10-11 20:59:46'),
(973, 'admin', NULL, NULL, 'Added announcement: qwwf (ID: ann_68ea54d19f5264.50471366, affected rows: 1)', '2025-10-11 21:00:01'),
(974, 'admin', NULL, NULL, 'Deleted announcement: qwd (ID: ann_68ea54c2a78bb0.64756968)', '2025-10-11 21:01:46'),
(975, 'admin', NULL, NULL, 'Deleted announcement: qwd (ID: ann_68ea54b63711d2.87508315)', '2025-10-11 21:01:53'),
(976, 'admin', NULL, NULL, 'Archived announcement: qwd (ID: ann_68ea54ad3fbb01.54048386)', '2025-10-11 21:04:12'),
(977, 'admin', NULL, NULL, 'Deleted announcement: qwwf (ID: ann_68ea54d19f5264.50471366)', '2025-10-11 21:04:29'),
(978, 'admin', NULL, NULL, 'Deleted announcement: qwd (ID: ann_68ea54ad3fbb01.54048386)', '2025-10-11 21:04:46'),
(979, 'admin', NULL, NULL, 'Added announcement: wda (ID: ann_68ea56044cdd50.94353675, affected rows: 1)', '2025-10-11 21:05:08'),
(980, 'admin', NULL, NULL, 'Added announcement: afc (ID: ann_68ea565839f991.63574994, affected rows: 1)', '2025-10-11 21:06:32'),
(981, 'admin', NULL, NULL, 'Deleted announcement: afc (ID: ann_68ea565839f991.63574994)', '2025-10-11 21:07:08'),
(982, 'admin', NULL, NULL, 'Added announcement: waf (ID: ann_68ea56e95b83f6.90060626, affected rows: 1)', '2025-10-11 21:08:57'),
(983, 'admin', NULL, NULL, 'Added announcement: asdad (ID: ann_68ea5751067a68.41516196, affected rows: 1)', '2025-10-11 21:10:41'),
(984, 'admin', NULL, NULL, 'Deleted announcement: asdad (ID: ann_68ea5751067a68.41516196)', '2025-10-11 21:10:49'),
(985, 'admin', NULL, NULL, 'Archived announcement: wda (ID: ann_68ea56044cdd50.94353675)', '2025-10-11 21:11:12'),
(986, 'admin', NULL, NULL, 'Edited announcement: waf (ID: ann_68ea56e95b83f6.90060626)', '2025-10-11 21:11:27'),
(987, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-11 21:13:10'),
(988, 'admin', NULL, NULL, 'Added official: sg', '2025-10-11 21:13:22'),
(989, 'admin', NULL, NULL, 'Deleted official: sg (ID: 12)', '2025-10-11 21:13:31'),
(990, 'admin', NULL, NULL, 'Deleted incident report ID 91 (AJAX)', '2025-10-11 21:14:54'),
(991, 'admin', NULL, NULL, 'Deleted incident report ID 76 (AJAX)', '2025-10-11 21:15:07'),
(992, 'admin', NULL, NULL, 'Deleted incident report ID 100 (AJAX)', '2025-10-11 21:15:23'),
(993, 'admin', NULL, NULL, 'Deleted incident report ID 75 (AJAX)', '2025-10-11 21:15:38'),
(994, 'admin', NULL, NULL, 'Deleted incident report ID 106 (AJAX)', '2025-10-11 21:17:58'),
(995, 'admin', NULL, NULL, 'Deleted incident report ID 81 (AJAX)', '2025-10-11 21:18:07'),
(996, 'admin', NULL, NULL, 'Deleted incident report ID 68 (AJAX)', '2025-10-11 21:22:20'),
(997, 'admin', NULL, NULL, 'Deleted incident report ID 73 (AJAX)', '2025-10-11 21:24:09'),
(998, 'admin', NULL, NULL, 'Archived resolved incident report ID 125 (AJAX)', '2025-10-11 21:24:24'),
(999, 'admin', '2025-10-11 22:40:29', '2025-10-12 08:29:55', '', NULL),
(1000, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-11 22:41:05'),
(1001, 'admin', NULL, NULL, 'Edited announcement: waf (ID: ann_68ea56e95b83f6.90060626)', '2025-10-11 22:41:21'),
(1002, 'admin', NULL, NULL, 'Archived announcement: waf (ID: ann_68ea56e95b83f6.90060626)', '2025-10-11 22:41:33'),
(1003, 'admin', NULL, NULL, 'Deleted announcement: waf (ID: ann_68ea56e95b83f6.90060626)', '2025-10-11 22:41:45'),
(1004, 'admin', NULL, NULL, 'Permanently deleted archived announcement (ID: ann_68ea56044cdd50.94353675)', '2025-10-11 22:41:51'),
(1005, 'admin', NULL, NULL, 'Approved certificate request ID: 48', '2025-10-12 07:22:46'),
(1006, 'admin', NULL, NULL, 'Printed certificate request ID: 49', '2025-10-12 07:22:49'),
(1007, 'admin', NULL, NULL, 'Printed certificate ID: 49 for resident Christian D miko', '2025-10-12 07:22:49'),
(1008, 'admin', NULL, NULL, 'Printed certificate ID: 49 for resident Christian D miko', '2025-10-12 07:22:49'),
(1009, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 07:26:58'),
(1010, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 07:27:03'),
(1011, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 07:27:07'),
(1012, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 07:27:10'),
(1013, 'admin', NULL, NULL, 'Printed certificate request ID: 48', '2025-10-12 07:29:56'),
(1014, 'admin', NULL, NULL, 'Printed certificate ID: 48 for resident Christian D miko', '2025-10-12 07:29:56'),
(1015, 'admin', NULL, NULL, 'Printed certificate ID: 48 for resident Christian D miko', '2025-10-12 07:29:56'),
(1016, 'admin', NULL, NULL, 'Archived certificate request ID: 49 (status Printed)', '2025-10-12 07:30:22'),
(1017, 'admin', NULL, NULL, 'Archived certificate request ID: 48 (status Printed)', '2025-10-12 07:31:28'),
(1018, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 07:31:32'),
(1019, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 07:31:40'),
(1020, 'admin', NULL, NULL, 'Archived certificate request ID: 45 (status Printed)', '2025-10-12 07:41:48'),
(1021, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 07:41:52'),
(1022, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 07:41:54'),
(1023, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 08:01:07'),
(1024, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 08:01:08'),
(1025, 'admin', NULL, NULL, 'Archived resolved incident report ID 135 (AJAX)', '2025-10-12 08:04:51'),
(1026, 'admin', NULL, NULL, 'Added official: Christian Mhico Ronario', '2025-10-12 08:13:39'),
(1027, '1234', '2025-10-12 08:30:19', '2025-10-12 08:30:49', '', NULL),
(1028, '1234', NULL, NULL, 'Sent message to user ID 55305: heloo', '2025-10-12 08:30:42'),
(1029, '1234', '2025-10-12 08:59:36', '2025-10-12 09:39:51', '', NULL),
(1030, '1234', NULL, NULL, 'Sent message to user ID 55305: helooo', '2025-10-12 08:59:55'),
(1031, '1234', NULL, NULL, 'Deleted incident report ID 152 (AJAX)', '2025-10-12 09:32:24'),
(1032, 'admin', '2025-10-12 09:58:32', NULL, '', NULL),
(1033, 'admin', '2025-10-12 10:09:22', '2025-10-12 10:45:53', '', NULL),
(1034, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:09:38'),
(1035, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:09:45'),
(1036, 'admin', NULL, NULL, 'Deleted incident report ID 154 (AJAX)', '2025-10-12 10:12:16'),
(1037, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:13:10'),
(1038, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:13:16'),
(1039, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:14:17'),
(1040, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:14:20'),
(1041, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:15:53'),
(1042, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:24:10'),
(1043, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:24:18'),
(1044, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:24:22'),
(1045, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:24:25'),
(1046, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:24:27'),
(1047, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:28:22'),
(1048, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:28:33'),
(1049, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:29:33'),
(1050, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:29:47'),
(1051, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:29:52'),
(1052, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:32:29'),
(1053, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:32:35'),
(1054, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:32:44'),
(1055, 'admin', NULL, NULL, 'Deleted incident report ID 155 (AJAX)', '2025-10-12 10:32:47'),
(1056, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-12 10:35:24'),
(1057, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 10:40:52'),
(1058, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 10:44:17'),
(1059, 'admin', NULL, NULL, 'Blocked resident: 55305', '2025-10-12 10:44:20'),
(1060, 'admin', '2025-10-12 10:51:21', '2025-10-12 11:01:05', '', NULL),
(1061, 'admin', NULL, NULL, 'Unblocked resident: 55305', '2025-10-12 10:51:38'),
(1062, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-12 10:51:46'),
(1063, 'admin', NULL, NULL, 'Deleted suggestion ID 15 (Subject: fsd)', '2025-10-12 10:57:39'),
(1064, 'admin', '2025-10-12 11:41:33', '2025-10-12 13:39:17', '', NULL),
(1065, 'admin', NULL, NULL, 'Archived announcement: ilaw at gabay (ID: ann_68c00fc27b78e8.03394148)', '2025-10-12 13:05:50'),
(1066, 'admin', NULL, NULL, 'Added announcement: wfuwsijg (ID: ann_68eb3744c17899.71064861, affected rows: 1)', '2025-10-12 13:06:12'),
(1067, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-12 13:13:08'),
(1068, 'admin', NULL, NULL, 'Edited official: Christian Mhico Ronario (ID: 13)', '2025-10-12 13:13:13'),
(1069, 'admin', NULL, NULL, 'Deleted incident report ID 89 (AJAX)', '2025-10-12 13:19:41'),
(1070, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-12 13:25:24'),
(1071, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96685)', '2025-10-12 13:25:42'),
(1072, 'admin', '2025-10-12 14:02:08', '2025-10-12 14:03:31', '', NULL),
(1073, 'admin', '2025-10-12 19:20:13', NULL, '', NULL),
(1074, 'admin', NULL, NULL, 'Added official: Carl Monzon', '2025-10-12 19:22:14'),
(1075, 'admin', NULL, NULL, 'Deleted official: Christian Mhico Ronario (ID: 13)', '2025-10-12 19:22:17'),
(1076, 'admin', NULL, NULL, 'Added official: Idong Dela Rea', '2025-10-12 19:23:48'),
(1077, 'admin', NULL, NULL, 'Added official: Minerva Mendoza Gerona', '2025-10-12 19:25:39'),
(1078, 'admin', NULL, NULL, 'Added official: Eldrence Ivan Clorina', '2025-10-12 19:27:09'),
(1079, 'admin', NULL, NULL, 'Added official: Christopher Alvarez', '2025-10-12 19:28:26'),
(1080, 'admin', '2025-10-12 21:35:42', '2025-10-12 21:37:53', '', NULL),
(1081, 'admin', NULL, NULL, 'Deleted announcement: wfuwsijg (ID: ann_68eb3744c17899.71064861)', '2025-10-12 21:36:03'),
(1082, 'admin', '2025-10-12 22:10:04', NULL, '', NULL),
(1083, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-13 06:22:10'),
(1084, 'admin', '2025-10-13 08:14:21', '2025-10-13 08:19:37', '', NULL),
(1085, 'admin', '2025-10-13 08:43:16', '2025-10-13 08:57:10', '', NULL),
(1086, 'admin', '2025-10-13 11:13:17', '2025-10-13 11:18:38', '', NULL),
(1087, 'admin', '2025-10-13 21:25:08', '2025-10-13 22:35:01', '', NULL),
(1088, 'admin', NULL, NULL, 'Blocked resident 55305 from Jobfinder', '2025-10-13 21:53:46'),
(1089, 'admin', NULL, NULL, 'Blocked resident 96684 from Jobfinder', '2025-10-13 22:07:40'),
(1090, 'admin', NULL, NULL, 'Blocked resident 10775 from Jobfinder', '2025-10-13 22:14:01'),
(1091, 'admin', NULL, NULL, 'Unblocked resident 55305 from Jobfinder', '2025-10-13 22:14:10'),
(1092, 'admin', NULL, NULL, 'Unblocked resident 10775 from Jobfinder', '2025-10-13 22:16:00'),
(1093, 'admin', NULL, NULL, 'Unblocked resident 96684 from Jobfinder', '2025-10-13 22:20:49'),
(1094, 'admin', '2025-10-13 23:18:15', '2025-10-13 23:37:27', '', NULL),
(1095, 'admin', NULL, NULL, 'Blocked resident 55305 from Jobfinder', '2025-10-13 23:18:30'),
(1096, 'admin', NULL, NULL, 'Unblocked resident 55305 from Jobfinder', '2025-10-13 23:33:02'),
(1097, 'admin', NULL, NULL, 'Verified resident 55305 in Jobfinder', '2025-10-13 23:33:08'),
(1098, 'admin', '2025-10-13 23:54:10', '2025-10-14 08:34:43', '', NULL),
(1099, 'admin', NULL, NULL, 'Unverified resident 55305 in Jobfinder', '2025-10-13 23:57:34'),
(1100, 'admin', NULL, NULL, 'Verified resident 55305 in Jobfinder', '2025-10-13 23:57:37'),
(1101, 'admin', NULL, NULL, 'Blocked resident 55305 from Jobfinder', '2025-10-13 23:57:40'),
(1102, 'admin', NULL, NULL, 'Unblocked resident 55305 from Jobfinder', '2025-10-13 23:57:46'),
(1103, 'admin', '2025-10-14 08:44:30', '2025-10-14 09:32:21', '', NULL),
(1104, 'admin', NULL, NULL, 'Printed certificate request ID: 38', '2025-10-14 09:28:07'),
(1105, 'admin', NULL, NULL, 'Printed certificate ID: 38 for resident Christian D miko', '2025-10-14 09:28:07'),
(1106, 'admin', NULL, NULL, 'Printed certificate ID: 38 for resident Christian D miko', '2025-10-14 09:28:07'),
(1107, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-14 09:30:13'),
(1108, 'admin', NULL, NULL, 'Edited resident: Christian, Christian (ID: 57527)', '2025-10-14 09:30:30'),
(1109, 'admin', '2025-10-14 10:49:43', NULL, '', NULL),
(1110, 'admin', NULL, NULL, 'Updated chat report #1 to status: resolved', '2025-10-14 10:53:56'),
(1111, 'admin', NULL, NULL, 'Updated chat report #1 to status: resolved', '2025-10-14 10:55:17'),
(1112, 'admin', NULL, NULL, 'Updated chat report #1 to status: dismissed', '2025-10-14 10:55:29'),
(1113, 'admin', NULL, NULL, 'Updated chat report #3 to status: resolved', '2025-10-14 11:05:51'),
(1114, 'admin', NULL, NULL, 'Updated chat report #2 to status: resolved', '2025-10-14 11:09:32'),
(1115, 'admin', NULL, NULL, 'Blocked resident 96585 from Jobfinder', '2025-10-14 11:37:45'),
(1116, 'admin', NULL, NULL, 'Unblocked resident 96585 from Jobfinder', '2025-10-14 11:37:50'),
(1117, 'admin', NULL, NULL, 'Blocked resident 55305 from Jobfinder', '2025-10-14 12:01:42'),
(1118, 'admin', NULL, NULL, 'Blocked resident 39003 from Jobfinder', '2025-10-14 12:12:40'),
(1119, 'admin', '2025-10-15 07:26:58', NULL, '', NULL),
(1120, 'admin', '2025-10-15 07:27:17', NULL, '', NULL),
(1121, 'admin', '2025-10-15 07:28:17', '2025-10-15 13:02:47', '', NULL),
(1122, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-15 07:28:42'),
(1123, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96686)', '2025-10-15 07:29:08'),
(1124, 'admin', NULL, NULL, 'Deleted incident report ID 165 (AJAX)', '2025-10-15 07:45:24'),
(1125, 'admin', NULL, NULL, 'Deleted incident report ID 164 (AJAX)', '2025-10-15 07:45:26'),
(1126, 'admin', NULL, NULL, 'Deleted incident report ID 163 (AJAX)', '2025-10-15 07:45:28'),
(1127, 'admin', NULL, NULL, 'Deleted incident report ID 162 (AJAX)', '2025-10-15 07:45:30');
INSERT INTO `admin_logs` (`id`, `username`, `login_time`, `logout_time`, `action`, `action_time`) VALUES
(1128, 'admin', NULL, NULL, 'Deleted incident report ID 161 (AJAX)', '2025-10-15 07:45:31'),
(1129, 'admin', NULL, NULL, 'Deleted incident report ID 160 (AJAX)', '2025-10-15 07:45:33'),
(1130, 'admin', NULL, NULL, 'Deleted incident report ID 159 (AJAX)', '2025-10-15 07:45:35'),
(1131, 'admin', NULL, NULL, 'Deleted incident report ID 158 (AJAX)', '2025-10-15 07:45:37'),
(1132, 'admin', NULL, NULL, 'Deleted incident report ID 157 (AJAX)', '2025-10-15 07:45:38'),
(1133, 'admin', NULL, NULL, 'Deleted incident report ID 156 (AJAX)', '2025-10-15 07:45:40'),
(1134, 'admin', NULL, NULL, 'Deleted incident report ID 153 (AJAX)', '2025-10-15 07:45:49'),
(1135, 'admin', NULL, NULL, 'Deleted incident report ID 151 (AJAX)', '2025-10-15 07:45:51'),
(1136, 'admin', NULL, NULL, 'Deleted incident report ID 150 (AJAX)', '2025-10-15 07:45:52'),
(1137, 'admin', NULL, NULL, 'Deleted incident report ID 149 (AJAX)', '2025-10-15 07:45:54'),
(1138, 'admin', NULL, NULL, 'Deleted incident report ID 148 (AJAX)', '2025-10-15 07:45:55'),
(1139, 'admin', NULL, NULL, 'Deleted incident report ID 147 (AJAX)', '2025-10-15 07:45:56'),
(1140, 'admin', NULL, NULL, 'Deleted incident report ID 146 (AJAX)', '2025-10-15 07:45:58'),
(1141, 'admin', NULL, NULL, 'Deleted incident report ID 145 (AJAX)', '2025-10-15 07:46:00'),
(1142, 'admin', NULL, NULL, 'Deleted incident report ID 87 (AJAX)', '2025-10-15 07:46:07'),
(1143, 'admin', NULL, NULL, 'Deleted incident report ID 92 (AJAX)', '2025-10-15 07:46:08'),
(1144, 'admin', NULL, NULL, 'Deleted incident report ID 103 (AJAX)', '2025-10-15 07:46:10'),
(1145, 'admin', NULL, NULL, 'Deleted incident report ID 104 (AJAX)', '2025-10-15 07:46:12'),
(1146, 'admin', NULL, NULL, 'Deleted incident report ID 144 (AJAX)', '2025-10-15 07:46:15'),
(1147, 'admin', NULL, NULL, 'Deleted incident report ID 143 (AJAX)', '2025-10-15 07:46:17'),
(1148, 'admin', NULL, NULL, 'Deleted incident report ID 142 (AJAX)', '2025-10-15 07:46:19'),
(1149, 'admin', NULL, NULL, 'Deleted incident report ID 140 (AJAX)', '2025-10-15 07:46:21'),
(1150, 'admin', NULL, NULL, 'Deleted incident report ID 141 (AJAX)', '2025-10-15 07:46:23'),
(1151, 'admin', NULL, NULL, 'Deleted incident report ID 138 (AJAX)', '2025-10-15 07:46:25'),
(1152, 'admin', NULL, NULL, 'Deleted incident report ID 139 (AJAX)', '2025-10-15 07:46:26'),
(1153, 'admin', NULL, NULL, 'Deleted incident report ID 130 (AJAX)', '2025-10-15 07:46:29'),
(1154, 'admin', NULL, NULL, 'Deleted incident report ID 126 (AJAX)', '2025-10-15 07:46:33'),
(1155, 'admin', NULL, NULL, 'Archived resolved incident report ID 123 (AJAX)', '2025-10-15 07:46:43'),
(1156, 'admin', NULL, NULL, 'Deleted incident report ID 127 (AJAX)', '2025-10-15 07:46:46'),
(1157, 'admin', '2025-10-15 13:03:40', NULL, '', NULL),
(1158, 'admin', '2025-10-15 16:15:21', '2025-10-15 18:10:55', '', NULL),
(1159, 'admin', '2025-10-15 18:11:04', NULL, '', NULL),
(1160, 'admin', '2025-10-15 18:16:13', NULL, '', NULL),
(1161, 'admin', '2025-10-15 18:22:47', NULL, '', NULL),
(1162, 'admin', '2025-10-15 18:53:24', NULL, '', NULL),
(1163, 'admin', '2025-10-15 19:19:23', NULL, '', NULL),
(1164, 'admin', '2025-10-15 19:19:53', NULL, '', NULL),
(1165, 'admin', '2025-10-15 19:20:04', '2025-10-15 19:40:49', '', NULL),
(1166, '1234', '2025-10-15 19:41:34', NULL, '', NULL),
(1167, '1234', NULL, NULL, 'Approved certificate request ID: 50', '2025-10-15 19:55:58'),
(1168, '1234', NULL, NULL, 'Archived certificate request ID: 38 (status Printed)', '2025-10-15 19:56:02'),
(1169, '1234', NULL, NULL, 'Archived certificate request ID: 44 (status Printed)', '2025-10-15 19:56:04'),
(1170, '1234', NULL, NULL, 'Archived resolved incident report ID 133 (AJAX)', '2025-10-15 19:56:28'),
(1171, 'admin', '2025-10-15 19:58:33', NULL, '', NULL),
(1172, 'admin', NULL, NULL, 'Updated chat report #4 to status: resolved', '2025-10-15 20:02:07'),
(1173, 'admin', '2025-10-15 20:08:51', '2025-10-15 21:38:40', '', NULL),
(1174, 'admin', NULL, NULL, 'Approved certificate request ID: 53', '2025-10-15 21:13:44'),
(1175, 'admin', NULL, NULL, 'Printed certificate request ID: 53', '2025-10-15 21:13:53'),
(1176, 'admin', NULL, NULL, 'Printed certificate ID: 53 for resident Christian D miko', '2025-10-15 21:13:53'),
(1177, 'admin', NULL, NULL, 'Printed certificate ID: 53 for resident Christian D miko', '2025-10-15 21:13:53'),
(1178, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-15 21:19:22'),
(1179, 'admin', '2025-10-15 21:35:26', NULL, '', NULL),
(1180, '1234', '2025-10-15 21:38:50', '2025-10-15 21:45:37', '', NULL),
(1181, 'admin', '2025-10-15 21:45:44', '2025-10-15 22:33:08', '', NULL),
(1182, 'admin', '2025-10-15 22:33:51', '2025-10-15 22:34:08', '', NULL),
(1183, 'admin', NULL, NULL, 'Unblocked resident 55305 from Jobfinder', '2025-10-15 22:34:06'),
(1184, 'admin', NULL, NULL, 'Verified resident 10775 in Jobfinder', '2025-10-15 22:35:12'),
(1185, 'admin', '2025-10-16 07:46:36', '2025-10-16 07:51:22', '', NULL),
(1186, 'admin', '2025-10-16 07:52:45', '2025-10-16 07:59:32', '', NULL),
(1187, 'admin', '2025-10-16 07:59:39', '2025-10-16 08:13:36', '', NULL),
(1188, '1234', '2025-10-16 08:13:43', '2025-10-16 10:03:17', '', NULL),
(1189, '1234', NULL, NULL, 'Printed certificate ID: 50 for resident Christian D miko', '2025-10-16 08:46:17'),
(1190, '1234', NULL, NULL, 'Printed certificate request ID: 50', '2025-10-16 08:46:17'),
(1191, '1234', NULL, NULL, 'Printed certificate ID: 50 for resident Christian D miko', '2025-10-16 08:46:17'),
(1192, '1234', NULL, NULL, 'Archived certificate request ID: 53 (status Printed)', '2025-10-16 08:46:25'),
(1193, '1234', NULL, NULL, 'Archived certificate request ID: 50 (status Printed)', '2025-10-16 08:46:31'),
(1194, '1234', NULL, NULL, 'Archived certificate request ID: 41 (status Printed)', '2025-10-16 08:46:33'),
(1195, '1234', NULL, NULL, 'Approved certificate request ID: 56', '2025-10-16 08:46:42'),
(1196, '1234', NULL, NULL, 'Approved certificate request ID: 55', '2025-10-16 08:46:43'),
(1197, '1234', NULL, NULL, 'Printed certificate ID: 55 for resident Christian D miko', '2025-10-16 08:46:44'),
(1198, '1234', NULL, NULL, 'Printed certificate request ID: 55', '2025-10-16 08:46:44'),
(1199, '1234', NULL, NULL, 'Printed certificate ID: 55 for resident Christian D miko', '2025-10-16 08:46:45'),
(1200, '1234', NULL, NULL, 'Archived certificate request ID: 55 (status Printed)', '2025-10-16 08:46:59'),
(1201, '1234', NULL, NULL, 'Sent message to user ID 55305: heloo', '2025-10-16 09:43:49'),
(1202, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96686)', '2025-10-16 09:54:23'),
(1203, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96685)', '2025-10-16 09:54:25'),
(1204, '1234', NULL, NULL, 'Permanently deleted resident: cong, king (ID: 65756)', '2025-10-16 09:54:27'),
(1205, '1234', NULL, NULL, 'Permanently deleted resident: Lim, dete (ID: 2500)', '2025-10-16 09:54:29'),
(1206, '1234', NULL, NULL, 'Permanently deleted resident: jokenow, ejay (ID: 91530)', '2025-10-16 09:54:31'),
(1207, '1234', NULL, NULL, 'Permanently deleted resident: De jesus, Angel (ID: 68018)', '2025-10-16 09:54:33'),
(1208, '1234', NULL, NULL, 'Permanently deleted resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-16 09:54:36'),
(1209, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Christian (ID: 9692)', '2025-10-16 09:54:37'),
(1210, '1234', NULL, NULL, 'Permanently deleted resident: ronario, saddii (ID: 43640)', '2025-10-16 09:54:39'),
(1211, '1234', NULL, NULL, 'Permanently deleted resident with ID: 56081', '2025-10-16 09:55:53'),
(1212, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96642)', '2025-10-16 09:55:55'),
(1213, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96643)', '2025-10-16 09:55:57'),
(1214, '1234', NULL, NULL, 'Permanently deleted resident: Juan53252, Christian (ID: 13833)', '2025-10-16 09:56:03'),
(1215, '1234', NULL, NULL, 'Permanently deleted resident: ronario, mico (ID: 83127)', '2025-10-16 09:56:05'),
(1216, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96681)', '2025-10-16 09:56:07'),
(1217, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96680)', '2025-10-16 09:56:12'),
(1218, '1234', NULL, NULL, 'Permanently deleted resident: heii, adwa (ID: 96682)', '2025-10-16 09:56:14'),
(1219, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96664)', '2025-10-16 09:56:17'),
(1220, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96668)', '2025-10-16 09:56:19'),
(1221, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96663)', '2025-10-16 09:56:22'),
(1222, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96662)', '2025-10-16 09:56:24'),
(1223, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96676)', '2025-10-16 09:56:29'),
(1224, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96675)', '2025-10-16 09:56:31'),
(1225, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96673)', '2025-10-16 09:56:32'),
(1226, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96674)', '2025-10-16 09:56:35'),
(1227, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96671)', '2025-10-16 09:56:36'),
(1228, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96672)', '2025-10-16 09:56:41'),
(1229, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96677)', '2025-10-16 09:56:42'),
(1230, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96665)', '2025-10-16 09:56:45'),
(1231, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96670)', '2025-10-16 09:56:46'),
(1232, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96678)', '2025-10-16 09:57:53'),
(1233, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96667)', '2025-10-16 09:57:55'),
(1234, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96666)', '2025-10-16 09:57:57'),
(1235, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96661)', '2025-10-16 09:57:58'),
(1236, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96660)', '2025-10-16 09:58:00'),
(1237, '1234', NULL, NULL, 'Permanently deleted resident: sfsd, 123 (ID: 96653)', '2025-10-16 09:58:01'),
(1238, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96658)', '2025-10-16 09:58:04'),
(1239, '1234', NULL, NULL, 'Permanently deleted resident: sfsd, 123 (ID: 96652)', '2025-10-16 09:58:06'),
(1240, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96651)', '2025-10-16 09:58:07'),
(1241, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96650)', '2025-10-16 09:58:10'),
(1242, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96657)', '2025-10-16 09:58:14'),
(1243, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96659)', '2025-10-16 09:58:16'),
(1244, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96648)', '2025-10-16 09:58:17'),
(1245, '1234', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96649)', '2025-10-16 09:58:19'),
(1246, 'admin', '2025-10-16 10:03:26', '2025-10-16 10:18:32', '', NULL),
(1247, 'admin', '2025-10-16 10:26:53', '2025-10-16 10:36:31', '', NULL),
(1248, 'admin', NULL, NULL, 'Added chatbot response: Hi', '2025-10-16 10:32:08'),
(1249, 'admin', NULL, NULL, 'Added chatbot response: Hi', '2025-10-16 10:34:20'),
(1250, 'admin', '2025-10-16 10:49:39', NULL, '', NULL),
(1251, 'admin', NULL, NULL, 'Updated chatbot response ID: 1', '2025-10-16 10:49:49'),
(1252, 'admin', NULL, NULL, 'Updated chatbot response ID: 2', '2025-10-16 10:55:28'),
(1253, 'admin', NULL, NULL, 'Added chatbot response: OFFICIALS', '2025-10-16 10:56:15'),
(1254, 'admin', NULL, NULL, 'Deleted chatbot response ID: 2', '2025-10-16 10:56:20'),
(1255, 'admin', NULL, NULL, 'Deleted chatbot response ID: 3', '2025-10-16 11:06:04'),
(1256, 'admin', NULL, NULL, 'Updated chatbot response ID: 11', '2025-10-16 11:09:20'),
(1257, 'admin', '2025-10-16 22:35:33', '2025-10-16 22:40:38', '', NULL),
(1258, 'admin', NULL, NULL, 'Unsent a message (chat_id: 250)', '2025-10-16 22:35:48'),
(1259, 'admin', NULL, NULL, 'Unsent a message (chat_id: 216)', '2025-10-16 22:40:22'),
(1260, 'admin', '2025-10-16 23:03:01', '2025-10-16 23:34:12', '', NULL),
(1261, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-16 23:04:12'),
(1262, 'admin', NULL, NULL, 'Edited resident: Money , Me (ID: 39003)', '2025-10-16 23:05:03'),
(1263, 'admin', NULL, NULL, 'Edited official: Minerva Mendoza Gerona (ID: 16)', '2025-10-16 23:30:42'),
(1264, 'admin', NULL, NULL, 'Archived resolved incident report ID 137 (AJAX)', '2025-10-16 23:33:45'),
(1265, 'admin', '2025-10-16 23:48:45', '2025-10-17 00:55:13', '', NULL),
(1266, 'admin', NULL, NULL, 'Printed certificate request ID: 56', '2025-10-16 23:49:01'),
(1267, 'admin', NULL, NULL, 'Printed certificate ID: 56 for resident Christian D miko', '2025-10-16 23:49:01'),
(1268, 'admin', NULL, NULL, 'Printed certificate ID: 56 for resident Christian D miko', '2025-10-16 23:49:01'),
(1269, 'admin', NULL, NULL, 'Approved certificate request ID: 51', '2025-10-16 23:49:05'),
(1270, 'admin', NULL, NULL, 'Approved certificate request ID: 54', '2025-10-16 23:49:08'),
(1271, 'admin', NULL, NULL, 'Approved certificate request ID: 52', '2025-10-16 23:49:11'),
(1272, 'admin', NULL, NULL, 'Printed certificate request ID: 54', '2025-10-16 23:49:14'),
(1273, 'admin', NULL, NULL, 'Printed certificate ID: 54 for resident Christian D miko', '2025-10-16 23:49:14'),
(1274, 'admin', NULL, NULL, 'Printed certificate ID: 54 for resident Christian D miko', '2025-10-16 23:49:14'),
(1275, 'admin', NULL, NULL, 'Sent message to user ID 55305: helloo', '2025-10-16 23:49:26'),
(1276, 'admin', '2025-10-17 09:16:04', '2025-10-17 09:17:30', '', NULL),
(1277, 'admin', '2025-10-17 10:15:32', '2025-10-17 10:36:10', '', NULL),
(1278, 'admin', NULL, NULL, 'Sent message to user ID 55305: wadaf', '2025-10-17 10:16:57'),
(1279, 'admin', NULL, NULL, 'Unsent message (Chat ID: 253)', '2025-10-17 10:17:00'),
(1280, 'admin', NULL, NULL, 'Unsent message (Chat ID: 259)', '2025-10-17 10:17:04'),
(1281, 'admin', NULL, NULL, 'Unsent message (Chat ID: 254)', '2025-10-17 10:17:17'),
(1282, 'admin', '2025-10-17 10:53:50', '2025-10-17 11:21:53', '', NULL),
(1283, 'admin', '2025-10-17 11:52:27', '2025-10-17 12:01:24', '', NULL),
(1284, 'admin', NULL, NULL, 'Blocked resident ID 55305 from submitting incident reports', '2025-10-17 11:56:01'),
(1285, 'admin', NULL, NULL, 'Unblocked resident ID 55305 from submitting incident reports', '2025-10-17 11:56:13'),
(1286, 'admin', '2025-10-17 12:02:25', '2025-10-17 13:20:55', '', NULL),
(1287, 'admin', NULL, NULL, 'Sent message to user ID 55305: helooo', '2025-10-17 12:08:04'),
(1288, 'admin', NULL, NULL, 'Unsent message (Chat ID: 278)', '2025-10-17 12:08:23'),
(1289, 'admin', NULL, NULL, 'Added resident: Fitalvo, Josephine (ID: 96687)', '2025-10-17 12:13:22'),
(1290, 'admin', NULL, NULL, 'Blocked resident 55305 from Jobfinder', '2025-10-17 12:18:00'),
(1291, 'admin', NULL, NULL, 'Blocked resident 96687 from Jobfinder', '2025-10-17 12:20:35'),
(1292, 'admin', '2025-10-17 13:21:35', '2025-10-17 13:21:51', '', NULL),
(1293, 'admin', NULL, NULL, 'Unblocked resident 55305 from Jobfinder', '2025-10-17 13:21:50'),
(1294, 'admin', '2025-10-17 13:24:34', '2025-10-17 13:27:11', '', NULL),
(1295, 'admin', '2025-10-17 13:30:36', '2025-10-17 23:30:36', '', NULL),
(1296, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-17 18:40:33'),
(1297, 'admin', '2025-10-18 20:23:56', '2025-10-18 23:05:33', '', NULL),
(1298, 'admin', NULL, NULL, 'Registered new admin: 1111', '2025-10-18 21:12:06'),
(1299, 'admin', NULL, NULL, 'Registered new admin: 123', '2025-10-18 21:14:26'),
(1300, 'admin', NULL, NULL, 'Deleted admin: 123', '2025-10-18 21:14:31'),
(1301, 'admin', NULL, NULL, 'Deleted admin: 1111', '2025-10-18 21:14:34'),
(1302, '1', NULL, NULL, 'Deleted Resident: step, Step (ID: 61803)', '2025-10-18 21:28:01'),
(1303, 'admin', NULL, NULL, 'Restored Resident: step, Step (ID: 61803)', '2025-10-18 21:28:19'),
(1304, 'admin', NULL, NULL, 'Edited resident: step, Step (ID: 61803)', '2025-10-18 21:28:43'),
(1305, '1', NULL, NULL, 'Deleted Resident: step, Step (ID: 61803)', '2025-10-18 21:28:53'),
(1306, 'admin', NULL, NULL, 'Restored Resident: step, Step (ID: 61803)', '2025-10-18 21:29:14'),
(1307, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96647)', '2025-10-18 21:29:19'),
(1308, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96646)', '2025-10-18 21:29:21'),
(1309, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96645)', '2025-10-18 21:29:23'),
(1310, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96644)', '2025-10-18 21:29:36'),
(1311, 'admin', NULL, NULL, 'Restored Resident: Ronario, Juan (ID: 96641)', '2025-10-18 21:29:56'),
(1312, 'admin', NULL, NULL, 'Restored Resident: Ronario, Juan (ID: 96640)', '2025-10-18 21:30:17'),
(1313, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96639)', '2025-10-18 21:30:51'),
(1314, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96641)', '2025-10-18 21:32:57'),
(1315, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96640)', '2025-10-18 21:33:01'),
(1316, 'admin', NULL, NULL, 'Restored Resident: Ronario, Juan (ID: 96640)', '2025-10-18 21:33:07'),
(1317, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96641)', '2025-10-18 21:33:11'),
(1318, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96638)', '2025-10-18 21:33:13'),
(1319, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96634)', '2025-10-18 21:33:14'),
(1320, 'admin', NULL, NULL, 'Permanently deleted resident: Reyes, Maria (ID: 96613)', '2025-10-18 21:33:17'),
(1321, 'admin', NULL, NULL, 'Permanently deleted resident: Reyes, Maria (ID: 96611)', '2025-10-18 21:33:19'),
(1322, 'admin', NULL, NULL, 'Permanently deleted resident: fff, fff (ID: 96655)', '2025-10-18 21:33:21'),
(1323, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96636)', '2025-10-18 21:33:23'),
(1324, '1', NULL, NULL, 'Deleted Resident: Ronario, Juan (ID: 96640)', '2025-10-18 21:33:42'),
(1325, '1', NULL, NULL, 'Deleted Resident: Pizano, aliyo (ID: 96656)', '2025-10-18 21:36:33'),
(1326, 'admin', NULL, NULL, 'Restored Resident: Pizano, aliyo (ID: 96656)', '2025-10-18 21:36:37'),
(1327, 'admin', NULL, NULL, 'Reset password for User ID: 4735', '2025-10-18 21:52:34'),
(1328, 'admin', NULL, NULL, 'Edited resident: ANGEL, CHRISTINE (ID: 96585)', '2025-10-18 22:36:33'),
(1329, 'admin', '2025-10-18 23:20:28', '2025-10-19 00:16:42', '', NULL),
(1330, 'admin', NULL, NULL, 'Approved certificate request ID: 60', '2025-10-19 00:00:41'),
(1331, 'admin', NULL, NULL, 'Approved certificate request ID: 58', '2025-10-19 00:00:47'),
(1332, 'admin', NULL, NULL, 'Printed certificate request ID: 58', '2025-10-19 00:00:49'),
(1333, 'admin', NULL, NULL, 'Printed certificate ID: 58 for resident Christian D miko', '2025-10-19 00:00:49'),
(1334, 'admin', NULL, NULL, 'Printed certificate ID: 58 for resident Christian D miko', '2025-10-19 00:00:49'),
(1335, 'admin', NULL, NULL, 'Printed certificate request ID: 60', '2025-10-19 00:08:33'),
(1336, 'admin', NULL, NULL, 'Archived certificate request ID: 60 (status Printed)', '2025-10-19 00:10:46'),
(1337, 'admin', NULL, NULL, 'Approved certificate request ID: 59', '2025-10-19 00:10:49'),
(1338, 'admin', NULL, NULL, 'Printed certificate request ID: 59', '2025-10-19 00:10:51'),
(1339, 'admin', NULL, NULL, 'Printed certificate ID: 59 for resident Christian D miko', '2025-10-19 00:10:51'),
(1340, 'admin', NULL, NULL, 'Printed certificate ID: 59 for resident Christian D miko', '2025-10-19 00:10:51'),
(1341, 'admin', NULL, NULL, 'Printed certificate request ID: 52', '2025-10-19 00:12:40'),
(1342, 'admin', NULL, NULL, 'Printed certificate ID: 52 for resident Christian D miko', '2025-10-19 00:12:40'),
(1343, 'admin', NULL, NULL, 'Printed certificate ID: 52 for resident Christian D miko', '2025-10-19 00:12:40'),
(1344, 'admin', '2025-10-19 00:16:15', '2025-10-19 07:25:37', '', NULL),
(1345, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 00:26:06'),
(1346, 'admin', NULL, NULL, 'Printed certificate ID: 51 for resident Christian D miko', '2025-10-19 00:26:10'),
(1347, 'admin', NULL, NULL, 'Printed certificate request ID: 51', '2025-10-19 00:26:10'),
(1348, 'admin', NULL, NULL, 'Printed certificate ID: 51 for resident Christian D miko', '2025-10-19 00:26:10'),
(1349, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 00:26:40'),
(1350, 'admin', NULL, NULL, 'Approved certificate request ID: 57', '2025-10-19 00:28:31'),
(1351, 'admin', NULL, NULL, 'Printed certificate ID: 57 for resident Christian D miko', '2025-10-19 00:28:32'),
(1352, 'admin', NULL, NULL, 'Printed certificate request ID: 57', '2025-10-19 00:28:32'),
(1353, 'admin', NULL, NULL, 'Printed certificate ID: 57 for resident Christian D miko', '2025-10-19 00:28:32'),
(1354, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 00:35:31'),
(1355, 'admin', NULL, NULL, 'Approved certificate request ID: 61', '2025-10-19 00:35:38'),
(1356, 'admin', NULL, NULL, 'Printed certificate request ID: 61', '2025-10-19 00:35:39'),
(1357, 'admin', NULL, NULL, 'Printed certificate ID: 61 for resident Christian miko rona', '2025-10-19 00:35:39'),
(1358, 'admin', NULL, NULL, 'Printed certificate ID: 61 for resident Christian miko rona', '2025-10-19 00:35:39'),
(1359, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 00:36:00'),
(1360, 'admin', NULL, NULL, 'Archived certificate request ID: 61 (status Printed)', '2025-10-19 06:53:54'),
(1361, 'admin', NULL, NULL, 'Archived certificate request ID: 59 (status Printed)', '2025-10-19 06:53:56'),
(1362, 'admin', NULL, NULL, 'Archived certificate request ID: 58 (status Printed)', '2025-10-19 06:54:00'),
(1363, 'admin', NULL, NULL, 'Archived certificate request ID: 57 (status Printed)', '2025-10-19 06:54:01'),
(1364, 'admin', NULL, NULL, 'Archived certificate request ID: 54 (status Printed)', '2025-10-19 06:54:03'),
(1365, 'admin', NULL, NULL, 'Archived certificate request ID: 56 (status Printed)', '2025-10-19 06:54:05'),
(1366, 'admin', '2025-10-19 08:39:27', '2025-10-19 08:40:06', '', NULL),
(1367, 'admin', '2025-10-19 08:44:04', '2025-10-19 09:03:40', '', NULL),
(1368, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 08:51:06'),
(1369, 'admin', NULL, NULL, 'Approved certificate request ID: 62', '2025-10-19 08:51:10'),
(1370, 'admin', NULL, NULL, 'Printed certificate request ID: 62', '2025-10-19 08:51:11'),
(1371, 'admin', NULL, NULL, 'Printed certificate ID: 62 for resident Christian miko rona', '2025-10-19 08:51:11'),
(1372, 'admin', NULL, NULL, 'Printed certificate ID: 62 for resident Christian miko rona', '2025-10-19 08:51:11'),
(1373, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 08:51:46'),
(1374, 'admin', NULL, NULL, 'Approved certificate request ID: 65', '2025-10-19 08:53:11'),
(1375, 'admin', NULL, NULL, 'Approved certificate request ID: 64', '2025-10-19 08:53:12'),
(1376, 'admin', NULL, NULL, 'Printed certificate ID: 64 for resident Christian miko rona', '2025-10-19 08:53:13'),
(1377, 'admin', NULL, NULL, 'Printed certificate request ID: 64', '2025-10-19 08:53:13'),
(1378, 'admin', NULL, NULL, 'Printed certificate ID: 64 for resident Christian miko rona', '2025-10-19 08:53:13'),
(1379, 'admin', '2025-10-19 09:04:01', '2025-10-19 09:05:39', '', NULL),
(1380, 'admin', '2025-10-19 09:05:49', '2025-10-19 09:05:54', '', NULL),
(1381, 'admin', '2025-10-19 09:07:01', '2025-10-19 09:07:22', '', NULL),
(1382, 'admin', '2025-10-19 09:07:30', '2025-10-19 09:09:21', '', NULL),
(1383, 'admin', '2025-10-19 09:09:28', '2025-10-19 09:09:45', '', NULL),
(1384, 'admin', '2025-10-19 09:09:58', '2025-10-19 09:10:30', '', NULL),
(1385, 'admin', NULL, NULL, 'Added resident: Ronario, Juan (ID: )', '2025-10-19 09:10:14'),
(1386, 'admin', '2025-10-19 09:15:44', '2025-10-19 09:15:54', '', NULL),
(1387, 'admin', '2025-10-19 09:17:35', '2025-10-19 09:17:39', '', NULL),
(1388, 'admin', '2025-10-19 09:17:53', '2025-10-19 09:18:42', '', NULL),
(1389, 'admin', '2025-10-19 09:18:52', '2025-10-19 09:18:55', '', NULL),
(1390, 'admin', '2025-10-19 09:19:54', '2025-10-19 09:19:58', '', NULL),
(1391, 'admin', '2025-10-19 09:22:59', '2025-10-19 09:23:50', '', NULL),
(1392, 'admin', '2025-10-19 10:23:09', '2025-10-19 11:33:30', '', NULL),
(1393, 'admin', NULL, NULL, 'Printed certificate request ID: 65', '2025-10-19 10:23:19'),
(1394, 'admin', NULL, NULL, 'Printed certificate ID: 65 for resident Christian miko rona', '2025-10-19 10:23:19'),
(1395, 'admin', NULL, NULL, 'Printed certificate ID: 65 for resident Christian miko rona', '2025-10-19 10:23:19'),
(1396, 'admin', NULL, NULL, 'Printed certificate ID: 65 for resident Christian miko rona', '2025-10-19 10:24:37'),
(1397, 'admin', NULL, NULL, 'Updated certificate content settings', '2025-10-19 10:29:02'),
(1398, 'admin', NULL, NULL, 'Approved certificate request ID: 66', '2025-10-19 10:29:30'),
(1399, 'admin', NULL, NULL, 'Printed certificate request ID: 66', '2025-10-19 10:29:34'),
(1400, 'admin', NULL, NULL, 'Printed certificate ID: 66 for resident Christian D miko', '2025-10-19 10:29:34'),
(1401, 'admin', NULL, NULL, 'Printed certificate ID: 66 for resident Christian D miko', '2025-10-19 10:29:34'),
(1402, 'admin', NULL, NULL, 'Approved certificate request ID: 63', '2025-10-19 10:31:53'),
(1403, 'admin', NULL, NULL, 'Printed certificate ID: 63 for resident Christian miko rona', '2025-10-19 10:31:54'),
(1404, 'admin', NULL, NULL, 'Printed certificate request ID: 63', '2025-10-19 10:31:54'),
(1405, 'admin', NULL, NULL, 'Printed certificate ID: 63 for resident Christian miko rona', '2025-10-19 10:31:54'),
(1406, 'admin', NULL, NULL, 'Approved certificate request ID: 67', '2025-10-19 10:34:34'),
(1407, 'admin', NULL, NULL, 'Printed certificate ID: 67 for resident Christian D miko', '2025-10-19 10:34:36'),
(1408, 'admin', NULL, NULL, 'Printed certificate request ID: 67', '2025-10-19 10:34:36'),
(1409, 'admin', NULL, NULL, 'Printed certificate ID: 67 for resident Christian D miko', '2025-10-19 10:34:36'),
(1410, 'admin', NULL, NULL, 'Approved certificate request ID: 68', '2025-10-19 10:40:52'),
(1411, 'admin', NULL, NULL, 'Printed certificate ID: 68 for resident Christian D miko', '2025-10-19 10:40:53'),
(1412, 'admin', NULL, NULL, 'Printed certificate request ID: 68', '2025-10-19 10:40:53'),
(1413, 'admin', NULL, NULL, 'Printed certificate ID: 68 for resident Christian D miko', '2025-10-19 10:40:53'),
(1414, 'admin', NULL, NULL, 'Archived certificate request ID: 68 (status Printed)', '2025-10-19 10:44:36'),
(1415, 'admin', NULL, NULL, 'Archived certificate request ID: 67 (status Printed)', '2025-10-19 10:44:39'),
(1416, 'admin', NULL, NULL, 'Archived certificate request ID: 66 (status Printed)', '2025-10-19 10:44:41'),
(1417, 'admin', NULL, NULL, 'Approved certificate request ID: 69', '2025-10-19 10:46:33'),
(1418, 'admin', NULL, NULL, 'Printed certificate ID: 69 for resident Christian miko rona', '2025-10-19 10:47:36'),
(1419, 'admin', NULL, NULL, 'Printed certificate ID: 69 for resident Christian miko rona', '2025-10-19 10:48:36'),
(1420, 'admin', NULL, NULL, 'Approved certificate request ID: 70', '2025-10-19 10:48:51'),
(1421, 'admin', NULL, NULL, 'Printed certificate ID: 70 for resident Christian miko rona', '2025-10-19 10:52:38'),
(1422, 'admin', NULL, NULL, 'Approved certificate request ID: 71', '2025-10-19 10:54:00'),
(1423, 'admin', NULL, NULL, 'Printed certificate ID: 71 for resident Christian miko rona', '2025-10-19 10:54:06'),
(1424, 'admin', NULL, NULL, 'Printed certificate request ID: 71', '2025-10-19 10:54:07'),
(1425, 'admin', NULL, NULL, 'Printed certificate ID: 71 for resident Christian miko rona', '2025-10-19 10:54:07'),
(1426, 'admin', NULL, NULL, 'Archived certificate request ID: 71 (status Printed)', '2025-10-19 10:54:25'),
(1427, 'admin', NULL, NULL, 'Archived certificate request ID: 69 (status Printed)', '2025-10-19 10:54:27'),
(1428, 'admin', NULL, NULL, 'Archived certificate request ID: 70 (status Printed)', '2025-10-19 10:54:29'),
(1429, 'admin', NULL, NULL, 'Archived certificate request ID: 64 (status Printed)', '2025-10-19 10:54:31'),
(1430, 'admin', NULL, NULL, 'Archived certificate request ID: 65 (status Printed)', '2025-10-19 10:54:33'),
(1431, 'admin', NULL, NULL, 'Archived certificate request ID: 63 (status Printed)', '2025-10-19 10:54:58'),
(1432, 'admin', NULL, NULL, 'Archived certificate request ID: 52 (status Printed)', '2025-10-19 10:55:00'),
(1433, 'admin', NULL, NULL, 'Approved certificate request ID: 72', '2025-10-19 10:58:04'),
(1434, 'admin', NULL, NULL, 'Printed certificate ID: 72 for resident Christian miko rona', '2025-10-19 10:58:05'),
(1435, 'admin', NULL, NULL, 'Printed certificate request ID: 72', '2025-10-19 10:58:05'),
(1436, 'admin', NULL, NULL, 'Printed certificate ID: 72 for resident Christian miko rona', '2025-10-19 10:58:05'),
(1437, 'admin', NULL, NULL, 'Permanently deleted resident: Ronario, Juan (ID: 96640)', '2025-10-19 11:32:10'),
(1438, 'admin', NULL, NULL, 'Unsent message (Chat ID: 215)', '2025-10-19 11:32:30'),
(1439, 'admin', '2025-10-20 10:00:21', '2025-10-20 10:02:29', '', NULL),
(1440, 'admin', '2025-10-20 10:09:09', NULL, '', NULL),
(1441, 'admin', NULL, NULL, 'Approved certificate request ID: 74', '2025-10-20 10:09:33'),
(1442, 'admin', NULL, NULL, 'Printed certificate ID: 74 for resident Christian D miko', '2025-10-20 10:09:36'),
(1443, 'admin', NULL, NULL, 'Printed certificate request ID: 74', '2025-10-20 10:09:36'),
(1444, 'admin', NULL, NULL, 'Printed certificate ID: 74 for resident Christian D miko', '2025-10-20 10:09:36'),
(1445, 'admin', NULL, NULL, 'Approved certificate request ID: 75', '2025-10-20 10:20:20'),
(1446, 'admin', NULL, NULL, 'Approved certificate request ID: 73', '2025-10-20 10:20:22'),
(1447, 'admin', NULL, NULL, 'Printed certificate request ID: 75', '2025-10-20 10:20:26'),
(1448, 'admin', NULL, NULL, 'Printed certificate ID: 75 for resident Christian miko rona', '2025-10-20 10:20:26'),
(1449, 'admin', NULL, NULL, 'Printed certificate ID: 75 for resident Christian miko rona', '2025-10-20 10:20:26'),
(1450, 'admin', NULL, NULL, 'Approved certificate request ID: 78', '2025-10-20 10:30:56'),
(1451, 'admin', NULL, NULL, 'Printed certificate request ID: 78', '2025-10-20 10:31:02'),
(1452, 'admin', NULL, NULL, 'Printed certificate ID: 78 for resident Christian miko rona', '2025-10-20 10:31:02'),
(1453, 'admin', NULL, NULL, 'Printed certificate ID: 78 for resident Christian miko rona', '2025-10-20 10:31:02'),
(1454, 'admin', NULL, NULL, 'Archived certificate request ID: 75 (status Printed)', '2025-10-20 10:31:30'),
(1455, 'admin', '2025-10-20 10:58:47', NULL, '', NULL),
(1456, 'admin', '2025-10-20 10:58:48', '2025-10-20 11:44:10', '', NULL),
(1457, 'admin', NULL, NULL, 'Approved certificate request ID: 80', '2025-10-20 11:02:38'),
(1458, 'admin', NULL, NULL, 'Approved certificate request ID: 76', '2025-10-20 11:05:45'),
(1459, 'admin', NULL, NULL, 'Printed certificate request ID: 80', '2025-10-20 11:08:42'),
(1460, 'admin', NULL, NULL, 'Printed certificate ID: 80 for resident Christian miko rona', '2025-10-20 11:08:42'),
(1461, 'admin', NULL, NULL, 'Printed certificate ID: 80 for resident Christian miko rona', '2025-10-20 11:08:42'),
(1462, 'admin', NULL, NULL, 'Archived certificate request ID: 80 (status Printed)', '2025-10-20 11:08:57'),
(1463, 'admin', NULL, NULL, 'Archived certificate request ID: 74 (status Printed)', '2025-10-20 11:14:16'),
(1464, 'admin', NULL, NULL, 'Unblocked resident 96687 from Jobfinder', '2025-10-20 11:16:07'),
(1465, 'admin', '2025-10-20 11:30:16', NULL, '', NULL),
(1466, 'admin', NULL, NULL, 'Approved certificate request ID: 79', '2025-10-20 11:30:34'),
(1467, 'admin', '2025-10-20 19:26:21', NULL, '', NULL),
(1468, 'admin', '2025-10-21 00:45:27', '2025-10-21 01:03:40', '', NULL),
(1469, 'admin', NULL, NULL, 'Added announcement: sadad (ID: ann_68f6694b8a3ba3.10212478, affected rows: 1)', '2025-10-21 00:54:35'),
(1470, 'admin', NULL, NULL, 'Added announcement: asdad (ID: ann_68f669534b3354.80237616, affected rows: 1)', '2025-10-21 00:54:43'),
(1471, 'admin', NULL, NULL, 'Added announcement: asfaf (ID: ann_68f6695aeae022.05025552, affected rows: 1)', '2025-10-21 00:54:50'),
(1472, 'admin', NULL, NULL, 'Added announcement: asfafsa (ID: ann_68f66960dd0c91.58592696, affected rows: 1)', '2025-10-21 00:54:56'),
(1473, 'admin', NULL, NULL, 'Added announcement: asadaf (ID: ann_68f66968b4ffa9.85780955, affected rows: 1)', '2025-10-21 00:55:04'),
(1474, 'admin', NULL, NULL, 'Added announcement: 2323 (ID: ann_68f6696f5a2582.21808927, affected rows: 1)', '2025-10-21 00:55:11'),
(1475, 'admin', NULL, NULL, 'Added announcement: asffa (ID: ann_68f669777b8e37.15356133, affected rows: 1)', '2025-10-21 00:55:19'),
(1476, 'admin', NULL, NULL, 'Added announcement: SAF (ID: ann_68f6697d8ea302.69118375, affected rows: 1)', '2025-10-21 00:55:25'),
(1477, 'admin', '2025-10-21 01:17:32', NULL, '', NULL),
(1478, 'admin', '2025-10-21 10:32:17', '2025-10-21 10:32:57', '', NULL),
(1479, 'admin', '2025-10-21 11:21:56', NULL, '', NULL),
(1480, 'admin', NULL, NULL, 'Sent message to user ID 10775: hey', '2025-10-21 11:22:06'),
(1481, 'admin', NULL, NULL, 'Sent message to user ID 10775: ad', '2025-10-21 12:03:58'),
(1482, 'admin', NULL, NULL, 'Sent message to user ID 10775: dsfsdf wa fes e g', '2025-10-21 12:13:08'),
(1483, 'admin', NULL, NULL, 'Unsent message (Chat ID: 287)', '2025-10-21 12:13:14'),
(1484, 'admin', '2025-10-21 18:41:32', NULL, '', NULL),
(1485, 'admin', NULL, NULL, 'Deleted chatbot response ID: 1', '2025-10-21 18:54:56'),
(1486, 'admin', '2025-10-22 20:47:17', NULL, '', NULL),
(1487, 'admin', '2025-10-22 20:53:13', '2025-10-22 20:53:46', '', NULL),
(1488, 'admin', '2025-10-22 21:05:14', '2025-10-22 22:11:17', '', NULL),
(1489, 'admin', NULL, NULL, 'Added chatbot response: hI', '2025-10-22 21:46:54'),
(1490, 'admin', NULL, NULL, 'Updated admin: 1234 to 1234', '2025-10-22 22:11:13'),
(1491, 'admin', '2025-10-22 22:14:53', '2025-10-22 22:16:29', '', NULL),
(1492, 'admin', '2025-10-22 22:18:48', NULL, '', NULL),
(1493, 'admin', NULL, NULL, 'Changed main admin password', '2025-10-22 22:19:12'),
(1494, 'admin', NULL, NULL, 'Edited official: Kenneth Sapida Saria (ID: 6)', '2025-10-22 22:20:58'),
(1495, 'admin', '2025-10-23 06:58:02', '2025-10-23 16:17:07', '', NULL),
(1496, 'admin', '2025-10-23 20:44:32', NULL, '', NULL),
(1497, 'admin', NULL, NULL, 'Changed main admin password', '2025-10-23 20:45:02'),
(1498, '1234', '2025-10-24 10:16:46', NULL, '', NULL),
(1499, '1234', NULL, NULL, 'Rejected & Archived certificate request ID: 81', '2025-10-24 10:20:17'),
(1500, '1234', NULL, NULL, 'Deleted announcement: asfafsa (ID: ann_68f66960dd0c91.58592696)', '2025-10-24 10:37:03'),
(1501, '1234', NULL, NULL, 'Deleted announcement: asfaf (ID: ann_68f6695aeae022.05025552)', '2025-10-24 10:37:10'),
(1502, '1234', NULL, NULL, 'Deleted announcement: SAF (ID: ann_68f6697d8ea302.69118375)', '2025-10-24 10:37:16'),
(1503, '1234', NULL, NULL, 'Deleted announcement: asffa (ID: ann_68f669777b8e37.15356133)', '2025-10-24 10:37:20'),
(1504, '1234', NULL, NULL, 'Deleted announcement: 2323 (ID: ann_68f6696f5a2582.21808927)', '2025-10-24 10:37:28'),
(1505, '1234', NULL, NULL, 'Deleted announcement: asadaf (ID: ann_68f66968b4ffa9.85780955)', '2025-10-24 10:37:35'),
(1506, '1234', NULL, NULL, 'Deleted announcement: asdad (ID: ann_68f669534b3354.80237616)', '2025-10-24 10:37:42'),
(1507, '1234', NULL, NULL, 'Deleted announcement: sadad (ID: ann_68f6694b8a3ba3.10212478)', '2025-10-24 10:37:50');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_posted` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `image`, `date_posted`, `status`) VALUES
('ann_68b56ff52e6297.20159339', 'F Y I📣', 'As declared by the Department of Interior and Local Government, CLASSES IN ALL LEVELS (PUBLIC AND PRIVATE) and WORK IN THE CITY GOVERNMENT OF DASMARIÑAS are SUSPENDED, 1 SEPTEMBER 2025.\r\nHowever, those agencies whose functions involve the delivery of basic and health services, preparedness/response to disasters and calamities, and/or the performance of other vital services shall continue with their operations and render the necessary services.\r\nThe suspension of work in other offices and private companies is left to the discretion of their respective heads.\r\nStay safe, Dasmarineños!\r\n#OnwardForwardCityofDasmariñas\r\n#SulongNaSulongPaDasmariñas', 'img_68b56ff52dc0a8.24129114.jpg', '2025-10-10 21:13:50', 'news'),
('ann_68e47ce945e427.16405958', 'WALANG PASOK', 'In preparation for the landfall of Severe Tropical Storm \"Opong\", WORK IN GOVERNMENT OFFICES and CLASSES IN ALL LEVELS (PUBLIC AND PRIVATE) are SUSPENDED tomorrow 26 September until 27 September 2025.\r\nHowever, those agencies whose functions involve the delivery of basic and health services, preparedness/response to disasters and calamities, and/or the performance of other vital services shall continue with their operations and render the necessary services.\r\nThe suspension of work in other offices and private companies is left to the discretion of their respective heads.\r\nManatiling ligtas, Dasmarineños!\r\n#OnwardForwardCityofDasmariñas\r\n#SulongNaSulongPaDasmariñas\r\n#ManatilingLigtasDasmarineños', 'img_68e47ce94514b2.23373036.jpg', '2025-10-10 18:13:36', 'news');

-- --------------------------------------------------------

--
-- Table structure for table `archived_announcements`
--

CREATE TABLE `archived_announcements` (
  `id` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_posted` datetime NOT NULL,
  `archived_at` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_announcements`
--

INSERT INTO `archived_announcements` (`id`, `title`, `content`, `image`, `date_posted`, `archived_at`, `status`) VALUES
('ann_68bc1597b89207.95697209', 'Linggo Ng Kabataan', '𝐓𝐈𝐍𝐆𝐍𝐀𝐍: Narito ang mga larawan sa ating naganap na Chess Tournament ngayong Sabado, September 6, 2025, bilang bahagi ng pagdiriwang ng Linggo ng Kabataan at Fiesta Celebration 2025 \r\n#SangguniangKabataanNgBarangaySabang \r\n#LNK2025', 'img_68bc1597b86f94.77771179.jpg', '2025-10-12 13:06:02', '2025-10-20 10:00:21', 'normal'),
('ann_68c00fc27b78e8.03394148', 'ilaw at gabay', 'Pagkamalikhain at pananampalataya, nagsasanib para sa karangalan ni Ina ng Peñafrancia! Sama-sama nating saksihan ang ganda ng mga arko handog ng bawat grupo sa ating barangay. Higit sa palamuti at paligsahan, ang mga arkong ito ay sumasalamin sa ating pagkakaisa, pananampalataya, at walang hanggang pag-ibig kay Ina. Nawa’y magsilbi itong inspirasyon upang patuloy tayong maging ilaw at gabay sa isa’t isa, habang sabay-sabay nating ipinagdiriwang ang kapistahan nang may galak at pananalig.', 'img_68c00fc27ab356.16223573.jpg', '2025-10-12 13:05:57', '2025-10-20 10:00:21', 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `archived_brgy_officials`
--

CREATE TABLE `archived_brgy_officials` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `position` varchar(200) NOT NULL,
  `description` varchar(2000) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `archived_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_brgy_officials`
--

INSERT INTO `archived_brgy_officials` (`id`, `name`, `position`, `description`, `photo`, `start_date`, `end_date`, `archived_at`) VALUES
(1, 'dets', 'President', 'fszddszf', 'uploads/1757513273_7a9f5567-0295-447e-877e-a13094f7e460.jpg', '2025-09-09', '2025-09-09', '2025-09-10 22:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `archived_certificate_requests`
--

CREATE TABLE `archived_certificate_requests` (
  `id` int(11) NOT NULL,
  `resident_unique_id` int(11) NOT NULL,
  `certificate_type` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_certificate_requests`
--

INSERT INTO `archived_certificate_requests` (`id`, `resident_unique_id`, `certificate_type`, `purpose`, `description`, `created_at`, `status`, `completed_at`) VALUES
(2, 55305, 'Barangay Clearance', 'hi po', NULL, '2025-08-27 13:18:11', 'Printed', NULL),
(3, 55305, 'Barangay Clearance', 'csdcsv', NULL, '2025-08-27 15:16:51', 'Printed', NULL),
(6, 55305, 'Barangay Clearance', 'hippp', NULL, '2025-08-29 11:03:29', 'Printed', NULL),
(7, 55305, 'Barangay Clearance', 'edrbtf7gyhujiok', NULL, '2025-08-31 23:24:50', 'Printed', NULL),
(8, 55305, 'Barangay Clearance', 'for work', NULL, '2025-09-04 00:38:08', 'Printed', NULL),
(9, 55305, 'Barangay Clearance', 'rccfqwtcu  tew rcuwer  wet grewicrg wer iuwr wcerg crgwr gyew', NULL, '2025-09-04 01:30:37', 'Printed', NULL),
(11, 10775, 'Certificate of Indigency', 'word', NULL, '2025-09-06 14:26:00', 'Printed', NULL),
(12, 10775, 'Certificate of Residency', 'life', NULL, '2025-09-06 14:59:06', 'Printed', NULL),
(13, 9692, 'Certificate of Residency', 'for may scholarship', NULL, '2025-09-06 16:06:54', 'Printed', NULL),
(14, 9692, 'Certificate of Indigency', 'for may house', NULL, '2025-09-06 16:07:07', 'Printed', NULL),
(15, 10775, 'Barangay Clearance', '10775', NULL, '2025-09-09 12:05:16', 'Printed', NULL),
(16, 10775, 'Certificate of Residency', '10775', NULL, '2025-09-09 12:05:24', 'Printed', NULL),
(17, 55305, 'Barangay Clearance', '829', NULL, '2025-09-09 12:29:24', 'Printed', NULL),
(18, 55305, 'Barangay Clearance', 'sd', NULL, '2025-09-11 00:24:10', 'Rejected', NULL),
(19, 55305, 'Barangay Clearance', 'hryyy', NULL, '2025-09-15 14:20:29', 'Printed', NULL),
(20, 55305, 'Certificate of Indigency', '12435', NULL, '2025-09-15 14:20:36', 'Printed', NULL),
(21, 55305, 'Certificate of Residency', '124r253', NULL, '2025-09-15 14:20:41', 'Printed', NULL),
(22, 55305, 'Certificate of Indigency', '32r32', NULL, '2025-09-15 14:20:46', 'Printed', NULL),
(23, 55305, 'Barangay Clearance', '32r2', NULL, '2025-09-15 14:20:51', 'Printed', NULL),
(24, 55305, 'Barangay Clearance', 'r3wrw', NULL, '2025-09-15 14:38:09', 'Printed', NULL),
(25, 55305, 'Certificate of Indigency', 'qwrwqrqr', NULL, '2025-09-15 14:38:14', 'Printed', NULL),
(26, 55305, 'Certificate of Residency', 'qwrqwwqr', NULL, '2025-09-15 14:38:20', 'Printed', NULL),
(27, 55305, 'Certificate of Indigency', 'sfvsgvs', NULL, '2025-09-15 14:38:33', 'Printed', NULL),
(28, 55305, 'Certificate of Residency', 'afcasf', NULL, '2025-09-15 14:38:38', 'Printed', NULL),
(29, 55305, 'Barangay Clearance', 'qawdawwq', NULL, '2025-09-15 14:48:39', 'Printed', NULL),
(30, 55305, 'Certificate of Indigency', 'awddad', NULL, '2025-09-15 14:48:44', 'Printed', NULL),
(31, 55305, 'Certificate of Residency', 'geerwrr', NULL, '2025-09-15 14:48:50', 'Printed', NULL),
(32, 55305, 'Barangay Clearance', 'efwew', NULL, '2025-09-15 14:48:55', 'Printed', NULL),
(33, 55305, 'Certificate of Indigency', 'wefwfwq', NULL, '2025-09-15 14:48:59', 'Printed', NULL),
(34, 55305, 'Certificate of Residency', 'wefwe', NULL, '2025-09-15 14:49:04', 'Printed', NULL),
(35, 55305, 'Barangay Clearance', 'heyyy 1127', NULL, '2025-09-15 15:25:50', 'Printed', NULL),
(36, 55305, 'Certificate of Indigency', '1125', NULL, '2025-09-15 15:26:00', 'Printed', NULL),
(37, 55305, 'Certificate of Residency', '1126', NULL, '2025-09-15 15:26:07', 'Printed', NULL),
(38, 55305, 'Barangay Clearance', 'heyyyyy 647', NULL, '2025-09-19 22:47:45', 'Printed', NULL),
(39, 55305, 'Barangay Clearance', 'opio', NULL, '2025-09-20 07:34:05', 'Printed', NULL),
(40, 55305, 'Certificate of Indigency', 'dwa', NULL, '2025-09-20 07:34:33', 'Printed', NULL),
(41, 55305, 'Certificate of Residency', 'retty', NULL, '2025-09-20 07:34:39', 'Printed', NULL),
(42, 55305, 'Certificate of Residency', '941', NULL, '2025-09-23 01:41:53', 'Rejected', NULL),
(43, 10775, 'Certificate of Residency', 'saaasdd', NULL, '2025-09-24 12:54:05', 'Printed', NULL),
(44, 55305, 'Certificate of Residency', 'qweqeqew', NULL, '2025-10-03 10:19:59', 'Printed', NULL),
(45, 55305, 'Certificate of Indigency', 'qewqe', NULL, '2025-10-03 10:20:04', 'Printed', NULL),
(46, 83127, 'Barangay Clearance', '83127', NULL, '2025-10-03 13:27:29', 'Rejected', NULL),
(47, 83127, 'Certificate of Residency', '83127', NULL, '2025-10-03 13:27:35', 'Printed', NULL),
(48, 55305, 'Barangay Clearance', '1020', NULL, '2025-10-08 14:20:38', 'Printed', NULL),
(49, 55305, 'Barangay Clearance', 'Hehehe', NULL, '2025-10-08 16:43:25', 'Printed', NULL),
(50, 55305, 'Barangay Clearance', 'efewfgweag', NULL, '2025-10-15 05:03:18', 'Printed', '2025-10-16 08:46:17'),
(52, 55305, 'Certificate of Residency', 'ergweg', NULL, '2025-10-15 05:03:28', 'Printed', '2025-10-19 00:12:40'),
(53, 55305, 'Barangay Clearance', 'Hehheheh', NULL, '2025-10-15 12:07:45', 'Printed', NULL),
(54, 55305, 'Certificate of Indigency', 'Hdhdj', NULL, '2025-10-15 12:07:52', 'Printed', '2025-10-16 23:49:14'),
(55, 55305, 'Certificate of Residency', 'Helooo', NULL, '2025-10-15 12:07:59', 'Printed', '2025-10-16 08:46:45'),
(56, 55305, 'Barangay Clearance', 'Hdhhdhhd', NULL, '2025-10-15 12:12:17', 'Printed', '2025-10-16 23:49:01'),
(57, 55305, 'Barangay Clearance', 'Hdhdhheheb', NULL, '2025-10-16 01:12:20', 'Printed', '2025-10-19 00:28:32'),
(58, 55305, 'Certificate of Residency', 'Hehehh', NULL, '2025-10-16 01:14:36', 'Printed', '2025-10-19 00:00:49'),
(59, 55305, 'Barangay Clearance', 'Hehehg', NULL, '2025-10-16 01:17:44', 'Printed', '2025-10-19 00:10:51'),
(60, 96687, 'Barangay Clearance', 'dasdsa', NULL, '2025-10-17 04:39:46', 'Printed', '2025-10-19 00:08:33'),
(61, 10775, 'Barangay Clearance', 'wdwadafaf', NULL, '2025-10-18 14:42:14', 'Printed', '2025-10-19 00:35:39'),
(63, 10775, 'Barangay Clearance', 'adsafsd', NULL, '2025-10-19 00:52:32', 'Printed', '2025-10-19 10:31:54'),
(64, 10775, 'Certificate of Indigency', 'safsfds', NULL, '2025-10-19 00:52:37', 'Printed', '2025-10-19 08:53:13'),
(65, 10775, 'Certificate of Residency', 'asfafsaf', NULL, '2025-10-19 00:52:42', 'Printed', '2025-10-19 10:24:37'),
(66, 55305, 'Certificate of Indigency', 'q w ewfwff', NULL, '2025-10-19 02:22:44', 'Printed', '2025-10-19 10:29:34'),
(67, 55305, 'Barangay Clearance', 'wqdqdfq', NULL, '2025-10-19 02:22:48', 'Printed', '2025-10-19 10:34:36'),
(68, 55305, 'Certificate of Residency', 'qwfqfqf', NULL, '2025-10-19 02:22:54', 'Printed', '2025-10-19 10:40:53'),
(69, 10775, 'Barangay Clearance', 'sefsgsg', NULL, '2025-10-19 02:46:15', 'Printed', '2025-10-19 10:48:36'),
(70, 10775, 'Certificate of Indigency', 'egsgs', NULL, '2025-10-19 02:46:20', 'Printed', '2025-10-19 10:52:38'),
(71, 10775, 'Certificate of Residency', 'sggdht', NULL, '2025-10-19 02:46:25', 'Printed', '2025-10-19 10:54:07'),
(74, 55305, 'Certificate of Indigency', 'Visa Application - sdsgdsg', NULL, '2025-10-20 02:08:56', 'Printed', '2025-10-20 10:09:36'),
(75, 10775, 'Barangay Clearance', 'Employment', 'hasdhabfjaflafje', '2025-10-20 02:19:51', 'Printed', '2025-10-20 10:20:26'),
(80, 10775, 'Barangay Clearance', 'Scholarship Application', 'Now all action buttons (Approve, Reject, Block) or (Print, Block) or (Archive, Block) will display horizontally in a single line without wrapping, making the table more compact and easier to read! ✅\n\nThe buttons are now:', '2025-10-20 02:39:22', 'Printed', '2025-10-20 11:08:42'),
(81, 55305, 'Barangay Clearance', 'heyy', 'aDDASFDASF', '2025-10-20 03:29:01', 'Rejected', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `archived_incident_reports`
--

CREATE TABLE `archived_incident_reports` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `incident_type` varchar(255) NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `incident_description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  `date_ended` datetime DEFAULT NULL,
  `seen` tinyint(1) DEFAULT 0,
  `admin_comment` varchar(1000) DEFAULT NULL,
  `admin_who_resolved` varchar(150) DEFAULT NULL,
  `incident_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archived_incident_reports`
--

INSERT INTO `archived_incident_reports` (`id`, `userid`, `incident_type`, `contact_number`, `incident_description`, `created_at`, `status`, `date_ended`, `seen`, `admin_comment`, `admin_who_resolved`, `incident_image`) VALUES
(38, 55305, 'ertet', '', '<td><?= $row[\'id\'] ?></td>\r\n                            <td><?= $row[\'userid\'] ?></td>\r\n                            <td><?= htmlspecialchars($row[\'incident_type\']) ?></td>\r\n                            <td><?= htmlspecialchars($row[\'incident_description\']) ?></td>\r\n                            <td><?= date(\"M d, Y h:i A\", strtotime($row[\'created_at\'])) ?></td>\r\n                            <td>\r\n                                <form method=\"POST\" action=\"<?= basename($_SERVER[\'PHP_SELF\']) . \'?\' . http_build_query($_GET) ?>\">\r\n                                    <input type=\"hidden\" name=\"id\" value=\"<?= $row[\'id\'] ?>\">\r\n                                    <input type=\"hidden\" name=\"update_status\" value=\"1\">\r\n                                    <select class=\"form-select form-select-sm\" name=\"status\" onchange=\"this.form.submit()\">\r\n                                        <option value=\"Pending\" <?= ($row[\'status\']==\"Pending\") ? \"selected\" : \"\" ?>>Pending</option>\r\n                                        <option value=\"In Review\" <?= ($row[\'status\']==\"In Review\") ? \"selected\" : \"\" ?>>In Review</option>\r\n                                        <option value=\"Resolved\" <?= ($row[\'status\']==\"Resolved\") ? \"selected\" : \"\" ?>>Resolved</option>\r\n                                    </select>\r\n                                </form>\r\n                            </td>\r\n                            <td><?= $row[\'date_ended\'] ? date(\"M d, Y h:i A\", strtotime($row[\'date_ended\'])) : \'-\' ?></td>\r\n                            <td class=\"text-center\">\r\n                                <a href=\"<?= basename($_SERVER[\'PHP_SELF\']) . \'?delete=\' . $row[\'id\'] . \'&\' . http_build_query($_GET) ?>\"\r\n                                   class=\"btn btn-sm btn-danger\"', '2025-09-03 15:40:42', 'Resolved', '2025-09-07 00:10:43', 0, NULL, NULL, NULL),
(42, 55305, 'sadad', '', 'a d', '2025-09-09 12:56:59', 'Resolved', '2025-09-09 21:44:50', 0, NULL, NULL, 'uploads/1757422619_543076333_122231031212136978_3077864921636238613_n.jpg'),
(43, 55305, 'Theft', '09988664533', 'zxcxzc', '2025-09-09 14:13:21', 'Resolved', '2025-09-09 22:17:40', 0, NULL, NULL, 'uploads/1757427201_368368203_6988917077788066_2179475306579483557_n.jpg'),
(44, 55305, 'sdf', '', 'sdfs', '2025-09-09 13:00:40', 'Resolved', '2025-09-09 22:17:53', 0, NULL, NULL, NULL),
(45, 55305, 'csa', '', 'saca', '2025-09-09 06:17:47', 'Resolved', '2025-09-09 22:19:39', 0, NULL, NULL, NULL),
(47, 55305, 'df', '', 'sfs', '2025-09-09 06:24:03', 'Resolved', '2025-09-10 20:58:06', 0, NULL, NULL, NULL),
(48, 55305, 'as', '', 'sad', '2025-09-09 06:22:41', 'Resolved', '2025-09-10 21:00:34', 0, NULL, NULL, NULL),
(49, 55305, 'as', '', 'as', '2025-09-09 06:21:39', 'Resolved', '2025-09-10 21:06:27', 1, NULL, NULL, NULL),
(50, 55305, 'sad', '', 'asd', '2025-09-09 06:21:25', 'Resolved', '2025-09-10 21:07:08', 1, NULL, NULL, NULL),
(51, 55305, 'tridsfsf', '', 'sdfsf', '2025-09-09 12:46:00', 'Resolved', '2025-09-10 21:10:06', 1, NULL, NULL, 'uploads/1757421960_544933317_122231292284136978_3402456412227171350_n.jpg'),
(52, 55305, 'sad', '', 'asdad', '2025-09-09 06:16:02', 'Resolved', '2025-09-10 21:10:54', 1, NULL, NULL, NULL),
(53, 55305, 'zcxxz', '', 'zcz', '2025-09-09 06:14:49', 'Resolved', '2025-09-10 21:15:49', 1, NULL, NULL, NULL),
(54, 55305, 'sdsd', '', 'dfds', '2025-09-09 06:14:36', 'Resolved', '2025-09-10 21:15:58', 1, NULL, NULL, NULL),
(55, 55305, 'sad', '', 'sada', '2025-09-09 06:11:47', 'Resolved', '2025-09-10 21:23:34', 1, NULL, NULL, NULL),
(56, 55305, 'asds', '', 'asda', '2025-09-09 06:11:21', 'Resolved', '2025-09-10 21:26:54', 1, NULL, NULL, NULL),
(62, 55305, 's', '12345678901', 's', '2025-09-24 23:54:39', 'Resolved', '2025-09-27 04:29:22', 1, NULL, NULL, NULL),
(63, 55305, 'Illegal Parking', '09569832666', 'Illegal ParkingIllegal ParkingIllegal ParkingIllegal Parking', '2025-09-25 00:46:39', 'Resolved', '2025-09-29 09:07:18', 1, NULL, NULL, NULL),
(64, 10775, 'w', '11111111111', 'e', '2025-09-24 11:18:58', 'Resolved', '2025-10-06 10:52:55', 1, NULL, NULL, NULL),
(65, 10775, 'Suspicious Activity', '11111111111', 'Suspicious ActivitySuspicious ActivitySuspicious Activity', '2025-09-24 11:09:41', 'Resolved', '2025-10-06 11:02:25', 1, NULL, NULL, 'uploads/1758712181_04_Image_2(2).png'),
(66, 55305, 'Theft', '09569832666', 'dfsfeef', '2025-09-30 03:14:29', 'Resolved', '2025-10-06 11:02:27', 1, NULL, NULL, 'uploads/1759202069_t1.png'),
(67, 55305, 'Public Disturbance', '09569832666', 'qweas', '2025-10-06 03:08:45', 'Resolved', '2025-10-06 11:13:48', 1, NULL, NULL, NULL),
(69, 55305, 'Trespassing', '09569832666', 'DWADWAAD', '2025-10-06 03:24:57', 'Resolved', '2025-10-06 15:17:02', 1, NULL, NULL, NULL),
(70, 55305, 'Vandalism', '09988664533', 'mbnbnkghjgh', '2025-10-06 03:19:25', 'Resolved', '2025-10-06 15:17:04', 1, NULL, NULL, NULL),
(71, 55305, 'Public Disturbance', '09988664533', 'dadwadaw', '2025-10-06 02:59:27', 'Resolved', '2025-10-06 15:21:32', 1, NULL, NULL, NULL),
(72, 55305, 'Damage to Property', '09988664533', 'wrweagrwqtetbertnery', '2025-10-03 22:29:36', 'Resolved', '2025-10-06 15:21:35', 1, NULL, NULL, 'uploads/1759530576_04_Image_4(2).png'),
(74, 10775, 'Harassment', '09569832666', 'ada', '2025-09-21 11:10:29', 'Resolved', '2025-10-06 15:22:44', 1, NULL, NULL, NULL),
(77, 55305, 'Lost Item', '09569832666', 'i lost', '2025-09-10 13:46:18', 'Resolved', '2025-10-06 15:26:41', 1, NULL, NULL, 'uploads/1757511978_7a9f5567-0295-447e-877e-a13094f7e460.jpg'),
(78, 55305, 'Animal Control Issue (e.g., stray dog)', '09569832666', 'Animal Control Issue (e.g., stray dog)', '2025-09-20 08:45:59', 'Resolved', '2025-10-06 15:28:15', 1, NULL, NULL, NULL),
(79, 10775, 'Domestic Dispute', '09569832666', 'Domestic Dispute', '2025-09-21 10:51:57', 'Resolved', '2025-10-06 15:28:37', 1, NULL, NULL, NULL),
(80, 55305, 'Accident/Injury', '09988664533', 'may banggaan sa kwarto ni mhcio', '2025-09-20 07:35:21', 'Resolved', '2025-10-06 15:31:52', 1, NULL, NULL, NULL),
(82, 55305, 'Public Disturbance', '09569832666', 'resbfbd g sg', '2025-10-03 22:29:54', 'Resolved', '2025-10-06 16:09:58', 1, 'DAWAW', NULL, 'uploads/1759530594_04_Image_4(2).png'),
(83, 55305, 'Noise Complaint', '09569832666', 'Noise Complaint', '2025-09-20 08:40:42', 'Resolved', '2025-10-06 16:10:16', 1, 'asdqwezxc', NULL, 'uploads/1758357642_checckk.png'),
(84, 55305, 'Fire Incident', '09569832666', 'xasdadd', '2025-09-30 13:44:08', 'Resolved', '2025-10-06 16:22:49', 1, 'heloooo', NULL, NULL),
(85, 10775, 'Public Disturbance', '09569832666', 'Public DisturbancePublic Disturbance', '2025-09-30 04:01:14', 'Resolved', '2025-10-06 16:23:13', 1, 'helooooooooadadadf', NULL, NULL),
(86, 10775, 'Public Disturbance', '09569832666', 'Public Disturbance', '2025-09-30 03:59:25', 'Resolved', '2025-10-06 16:36:15', 1, 'A comment example can be \"The most frequent comment was that the service was slow,\" which shows the noun form as an opinion or observation, or \"She commented that the service seemed slow,\" demonstrating the verb form as providing an opinion or explanation. Examples also include \"I find your comments offensive,\" expressing a negative reaction to someone\'s words, and \"We haven\'t gotten any comments on the new design\" to show how \"comments\" refers to feedback.', NULL, NULL),
(87, 55305, 'sunog', '09569832666', 'watfwfwe', '2025-10-06 23:25:36', 'Resolved', '2025-10-07 08:15:03', 1, 'vqv', NULL, NULL),
(88, 55305, 'Fire', '09569832666', '12314', '2025-10-10 01:06:34', 'Resolved', '2025-10-10 09:11:27', 1, '123456789', NULL, NULL),
(90, 55305, 'Public Disturbance', '09569832666', 'Public Disturbance', '2025-09-30 03:23:37', 'Resolved', '2025-10-11 03:02:57', 1, 'asasd', NULL, NULL),
(92, 55305, 'Vandalism', '09569832666', 'Vandalism', '2025-09-30 03:57:23', 'Resolved', '2025-10-11 07:38:30', 1, 'alkfndsenfnfewen', NULL, NULL),
(93, 55305, 'Curfew Violation', '09569832666', 'dfass fvafvasvfs', '2025-10-08 19:16:07', 'Resolved', '2025-10-11 21:24:24', 1, 'asfasdasdadadsda', NULL, 'uploads/1759950967_admin-panel.png'),
(94, 55305, 'Chemical Spill', '12345678901', '12', '2025-10-10 01:10:39', 'Resolved', '2025-10-12 08:04:51', 1, 'sd', NULL, NULL),
(95, 55305, 'sunog', '09569832666', '\'sunog\', \'pagsabog\', \'tagas ng gas\', \'pagtagas ng kemikal\',', '2025-10-06 23:21:39', 'Resolved', '2025-10-15 07:46:43', 1, 'asdfghjklkjhgffghjjhg', NULL, 'uploads/1759792899_image.jpg'),
(96, 55305, 'Homicide', '12345678901', 'Dvdvh', '2025-10-10 01:09:16', 'Resolved', '2025-10-15 19:56:28', 1, 'fjhfgfh', NULL, NULL),
(97, 55305, 'Noise Complaint', '09569832666', 'Gehejehv', '2025-10-12 01:00:46', 'Resolved', '2025-10-16 23:33:45', 1, 'dasad', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `certificate_content`
--

CREATE TABLE `certificate_content` (
  `id` int(11) NOT NULL,
  `barangay_captain` varchar(255) DEFAULT 'Hon. Kenneth S. Saria',
  `barangay_name` varchar(255) DEFAULT 'Barangay Sabang',
  `city` varchar(255) DEFAULT 'Dasmariñas City, Cavite',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate_content`
--

INSERT INTO `certificate_content` (`id`, `barangay_captain`, `barangay_name`, `city`, `updated_at`) VALUES
(1, 'Kenneth Sapida Saria', 'Barangay Sabang', 'Dasmarinas City, Cavite', '2025-10-19 02:29:02');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_options`
--

CREATE TABLE `certificate_options` (
  `id` int(11) NOT NULL,
  `certificate_name` varchar(255) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate_options`
--

INSERT INTO `certificate_options` (`id`, `certificate_name`, `is_enabled`) VALUES
(1, 'Barangay Clearance', 1),
(2, 'Certificate of Indigency', 1),
(3, 'Certificate of Residency', 1);

-- --------------------------------------------------------

--
-- Table structure for table `certificate_requests`
--

CREATE TABLE `certificate_requests` (
  `id` int(11) NOT NULL,
  `resident_unique_id` int(5) NOT NULL,
  `certificate_type` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate_requests`
--

INSERT INTO `certificate_requests` (`id`, `resident_unique_id`, `certificate_type`, `purpose`, `description`, `created_at`, `status`, `completed_at`) VALUES
(51, 55305, 'Certificate of Indigency', 'ewgew', NULL, '2025-10-15 05:03:23', 'Printed', '2025-10-19 00:26:10'),
(62, 10775, 'Certificate of Residency', 'awfafae', NULL, '2025-10-18 14:42:19', 'Printed', '2025-10-19 08:51:11'),
(72, 10775, 'Barangay Clearance', 'Educational assistance', NULL, '2025-10-19 02:57:30', 'Printed', '2025-10-19 10:58:05'),
(73, 55305, 'Certificate of Indigency', 'Medical Assistance - hyyyy', NULL, '2025-10-20 02:08:45', 'Approved', NULL),
(76, 10775, 'Certificate of Indigency', 'Overseas Employment', 'efaegagesgreg', '2025-10-20 02:20:53', 'Approved', NULL),
(77, 10775, 'Certificate of Residency', 'Hospital Admission', 'awgaegage', '2025-10-20 02:21:00', 'Pending', NULL),
(78, 10775, 'Barangay Clearance', 'Business Registration', 'wfaagagaeg', '2025-10-20 02:21:08', 'Printed', '2025-10-20 10:31:02'),
(79, 10775, 'Barangay Clearance', 'School Enrollment', 'asdfsafaf', '2025-10-20 02:30:36', 'Approved', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_responses`
--

CREATE TABLE `chatbot_responses` (
  `id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `keywords` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot_responses`
--

INSERT INTO `chatbot_responses` (`id`, `question`, `answer`, `keywords`, `category`, `is_active`, `created_at`, `updated_at`, `created_by`) VALUES
(4, 'How do I request a barangay clearance?', 'To request a barangay clearance: 1) Visit the barangay hall during office hours, 2) Fill out the application form, 3) Submit required documents (valid ID, proof of residency), 4) Pay the clearance fee, 5) Wait for processing (usually same day). You can also check if online application is available.', 'barangay clearance, request, apply, application, how to get', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(5, 'What are the office hours?', 'The barangay office is open Monday to Friday, 8:00 AM to 5:00 PM. We are closed on weekends and public holidays. For urgent matters, you may contact our hotline.', 'office hours, schedule, time, open, operating hours', 'General Information', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(6, 'How to report an incident?', 'To report an incident: 1) Contact the barangay hotline immediately, 2) Visit the barangay hall to file a formal report, 3) Provide details of the incident (date, time, location, persons involved), 4) Submit any evidence if available. For emergencies, call 911 first.', 'report incident, emergency, complaint, blotter, report', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(7, 'Who are the barangay officials?', 'You can view the complete list of barangay officials on our Officials page. This includes the Barangay Captain, Kagawads, SK Chairman, and Barangay Secretary. Visit the website or barangay hall for updated information.', 'officials, barangay captain, kagawad, SK chairman, leaders', 'General Information', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(8, 'How to use the suggestion box?', 'To submit a suggestion: 1) Navigate to the Suggestion Box page on our website, 2) Fill out the form with your name, contact details, and suggestion, 3) Submit the form. You can also drop written suggestions in the physical suggestion box at the barangay hall. All suggestions are reviewed regularly.', 'suggestion box, feedback, suggestions, submit suggestion', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(9, 'What are the requirements for certificate of residency?', 'Requirements for Certificate of Residency: 1) Valid government-issued ID, 2) Proof of residency (utility bill, lease contract, or affidavit), 3) Barangay clearance (if applicable), 4) Accomplished application form, 5) Payment of processing fee. Processing time is usually same day.', 'certificate of residency, requirements, documents needed, residency certificate', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(10, 'How much is the barangay clearance fee?', 'The barangay clearance fee is typically PHP 50-100, depending on the purpose. Senior citizens and PWDs may be entitled to discounts. Please visit the barangay hall or contact us for the exact current fee.', 'clearance fee, cost, price, how much, payment', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(11, 'Where is the barangay hall located?', 'The barangay hall is located at Barangay Sabang, Dasmarinas, Cavite. You can find us near [INSERT LANDMARK]. For directions, please check the Contact Us page or use the map on our website.', 'location, address, where, barangay hall, directions', 'General Information', 1, '2025-10-16 03:05:40', '2025-10-16 03:09:20', 'admin'),
(12, 'How do I register as a resident?', 'To register as a resident: 1) Visit the barangay hall, 2) Fill out the resident registration form, 3) Submit valid ID and proof of address (lease contract, utility bill), 4) Wait for verification, 5) Receive your resident certificate. Registration is usually free of charge.', 'register, resident registration, new resident, how to register', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(13, 'How to file a complaint?', 'To file a complaint: 1) Visit the barangay hall during office hours, 2) Proceed to the Lupon/Mediation desk, 3) Fill out the complaint form with details, 4) Submit supporting documents if any, 5) Schedule a mediation hearing. The barangay will facilitate settlement between parties.', 'file complaint, complaint, dispute, mediation, lupon', 'Services', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(14, 'What events are coming up?', 'To view upcoming barangay events, please check the Events & Announcements page on our website. We regularly post information about community activities, meetings, and special programs. You can also follow our official social media pages for updates.', 'events, upcoming events, activities, announcements, programs', 'General Information', 1, '2025-10-16 03:05:40', '2025-10-16 03:05:40', 'admin'),
(15, 'hI', 'HELOOOO', 'hi', 'greatings', 1, '2025-10-22 13:46:54', '2025-10-22 13:46:54', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `chat_ratings`
--

CREATE TABLE `chat_ratings` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_ratings`
--

INSERT INTO `chat_ratings` (`id`, `userid`, `receiver_id`, `rating`, `comment`, `created_at`) VALUES
(1, 55305, 10775, 4, 'segasfsgsgsgsgsfs', '2025-09-30 09:27:22'),
(2, 55305, 10775, 2, 'helooooo', '2025-09-30 09:39:20'),
(3, 55305, 4735, 4, NULL, '2025-09-30 09:48:03'),
(4, 55305, 96612, 2, NULL, '2025-09-30 09:48:21'),
(5, 55305, 96616, 4, NULL, '2025-09-30 09:48:27'),
(6, 55305, 96618, 5, NULL, '2025-09-30 09:48:33'),
(7, 55305, 96618, 4, NULL, '2025-09-30 10:12:15'),
(8, 55305, 96618, 2, NULL, '2025-09-30 10:13:51'),
(9, 55305, 4735, 3, '', '2025-09-30 10:15:02'),
(10, 55305, 96617, 5, '', '2025-09-30 10:17:32'),
(11, 55305, 96617, 5, '', '2025-09-30 10:19:24'),
(12, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:05'),
(13, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:06'),
(14, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:07'),
(15, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:07'),
(16, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:08'),
(17, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:09'),
(18, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:10'),
(19, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:10'),
(20, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:10'),
(21, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:10'),
(22, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:11'),
(23, 55305, 96617, 5, 'awdadad', '2025-09-30 10:22:11'),
(24, 55305, 96617, 5, 'ada', '2025-09-30 10:22:33'),
(25, 55305, 96617, 5, 'sadac', '2025-09-30 10:23:43'),
(26, 55305, 96617, 5, 'adadassfsf', '2025-09-30 10:25:38'),
(27, 55305, 96617, 5, 'good job', '2025-09-30 10:27:23'),
(28, 55305, 96617, 5, 'good job', '2025-09-30 10:27:35'),
(29, 55305, 96617, 5, 'hey', '2025-09-30 10:28:40'),
(30, 55305, 96617, 5, 'fix it', '2025-09-30 10:30:15'),
(31, 55305, 96617, 5, 'wqadaw', '2025-09-30 11:18:39'),
(32, 55305, 96617, 5, 'goodjob', '2025-09-30 11:19:09'),
(33, 55305, 10775, 5, '\"Good job!\" is a phrase to express approval or praise for someone\'s accomplishment. It\'s a form of positive reinforcement, encouraging and acknowledging that a task has been done well.', '2025-09-30 12:03:11'),
(34, 10775, 55305, 5, 'GOOOOOOODDDD JOBBBB', '2025-10-01 10:43:53'),
(35, 10775, 96617, 5, '\"Good job!\" is a phrase to express approval or praise for someone\'s accomplishment. It\'s a form of positive reinforcement, encouraging and acknowledging that a task has been done well. For example, you might say, \"The house looks great – good job, guys!\"', '2025-10-01 10:57:08'),
(36, 55305, 96611, 5, 'gsgdg', '2025-10-06 01:50:56'),
(37, 55305, 96662, 5, 'Heyy', '2025-10-09 01:04:51'),
(38, 55305, 10775, 5, 'heyy', '2025-10-11 14:27:26'),
(39, 55305, 10775, 5, '', '2025-10-11 14:29:20'),
(40, 55305, 10775, 5, 'good', '2025-10-17 03:40:03');

-- --------------------------------------------------------

--
-- Table structure for table `chat_reports`
--

CREATE TABLE `chat_reports` (
  `id` int(11) NOT NULL,
  `reporter_id` varchar(50) NOT NULL COMMENT 'User who is reporting',
  `reported_id` varchar(50) NOT NULL COMMENT 'User being reported',
  `reason` varchar(255) NOT NULL COMMENT 'Reason for report',
  `details` text DEFAULT NULL COMMENT 'Additional details',
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL COMMENT 'Admin who reviewed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_reports`
--

INSERT INTO `chat_reports` (`id`, `reporter_id`, `reported_id`, `reason`, `details`, `status`, `created_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, '55305', '10775', 'Spam', 'ewfsgfsgfs', 'dismissed', '2025-10-14 02:48:59', '2025-10-14 02:55:29', 1),
(2, '55305', '4735', 'Harassment', 'etrret', 'resolved', '2025-10-14 03:05:16', '2025-10-14 03:09:32', 1),
(3, '55305', '96669', 'Spam', 'waefdwfew', 'resolved', '2025-10-14 03:05:25', '2025-10-14 03:05:51', 1),
(4, '55305', '57527', 'Spam', 'sadad', 'resolved', '2025-10-14 03:18:05', '2025-10-15 12:02:07', 1),
(5, '55305', '96684', 'Spam', 'asdsad', 'pending', '2025-10-14 03:18:14', NULL, NULL),
(6, '55305', '10775', 'Other', '', 'pending', '2025-10-17 01:02:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deleted_residents`
--

CREATE TABLE `deleted_residents` (
  `id` int(11) NOT NULL,
  `unique_id` varchar(50) NOT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `household_id` varchar(50) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `is_head` tinyint(1) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `civil_status` varchar(50) DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `occupation_skills` varchar(150) DEFAULT NULL,
  `education` varchar(150) DEFAULT NULL,
  `is_pwd` tinyint(1) DEFAULT NULL,
  `deleted_at` datetime DEFAULT current_timestamp(),
  `pending_email` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `skill_description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deleted_residents`
--

INSERT INTO `deleted_residents` (`id`, `unique_id`, `surname`, `first_name`, `middle_name`, `age`, `sex`, `address`, `household_id`, `relationship`, `is_head`, `birthdate`, `place_of_birth`, `civil_status`, `citizenship`, `occupation_skills`, `education`, `is_pwd`, `deleted_at`, `pending_email`, `profile_image`, `skill_description`) VALUES
(102, '96637', 'Ronario', 'Juan', 'Santos', 33, 'Male', '123 Main St', 'H001', 'Head', 0, '2000-01-01', 'Cebu', 'Single', 'Filipino', 'Carpenter', 'College', 0, '2025-10-07 08:31:53', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--

CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `incident_type` varchar(255) NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `incident_description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  `date_ended` datetime DEFAULT NULL,
  `seen` tinyint(1) DEFAULT 0,
  `incident_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incident_reports`
--

INSERT INTO `incident_reports` (`id`, `userid`, `incident_type`, `contact_number`, `incident_description`, `created_at`, `status`, `date_ended`, `seen`, `incident_image`) VALUES
(108, 55305, 'Theft', '09569832666', 'awda', '2025-09-30 03:58:21', 'In Review', NULL, 1, NULL),
(122, 55305, 'Lost Item', '09569832666', 'tbternytryrtn', '2025-10-06 23:16:11', 'Pending', NULL, 1, NULL),
(134, 55305, 'Other: Wla lng', '12345678901', '1', '2025-10-10 01:10:06', 'In Review', NULL, 1, NULL),
(166, 55305, 'Gas Leak', '65626626629', 'Hdhdh', '2025-10-16 01:18:01', 'In Review', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `main_admin`
--

CREATE TABLE `main_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('main') DEFAULT 'main',
  `is_online` tinyint(1) DEFAULT 0 COMMENT 'Admin online status: 1=online, 0=offline',
  `last_active` datetime DEFAULT NULL COMMENT 'Last activity timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `main_admin`
--

INSERT INTO `main_admin` (`id`, `username`, `password`, `role`, `is_online`, `last_active`) VALUES
(1, 'admin', '12345678', 'main', 1, '2025-10-23 20:44:32');

-- --------------------------------------------------------

--
-- Table structure for table `manage_brgy_officials`
--

CREATE TABLE `manage_brgy_officials` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `position` varchar(200) NOT NULL,
  `description` varchar(2000) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manage_brgy_officials`
--

INSERT INTO `manage_brgy_officials` (`id`, `name`, `position`, `description`, `photo`, `start_date`, `end_date`) VALUES
(6, 'Kenneth Sapida Saria', 'Barangay Captain', 'Kenneth Sapida Saria is the Barangay Captain of Sabang, Dasmariñas City, Cavite, a former Barangay Kagawad (2010–2013), and a community leader recognized for his dedication, service, and involvement in local development initiatives.', 'uploads/1756721510_368368203_6988917077788066_2179475306579483557_n.jpg', '2025-09-01', '2028-01-01'),
(14, 'Carl Monzon', 'SK Chairperson', 'Carl Monzon serves as the Sangguniang Kabataan (SK) Chairperson, representing the youth in the barangay. He leads programs focused on leadership development, sports, education, and environmental initiatives that engage young residents. Carl plays a vital role in bridging the youth and the barangay council, ensuring that the voices and concerns of the younger generation are heard in community planning and projects.', 'uploads/1760268134_dbfd5adc-20c0-4ef0-8719-f91038b8e8af.jpg', '2025-10-12', '2026-02-27'),
(15, 'Idong Dela Rea', 'Barangay Kagawad', 'Kagawad Idong Dela Rea is known for his dedication to maintaining peace and order within the barangay. He often oversees security and safety programs, collaborating with barangay tanods to ensure a safe environment for residents. His hands-on leadership and approachable demeanor make him a trusted figure in community problem-solving and local initiatives.', 'uploads/1760268228_d66428ef-e38a-4513-8eb8-58afa8b4d400.jpg', '2025-10-12', '2026-02-12'),
(16, 'Minerva Mendoza Gerona', 'Barangay Kagawad', 'Kagawad Minerva Mendoza Gerona actively participates in community development projects, especially those focusing on women’s welfare, health programs, and education. She advocates for inclusivity and cooperation among residents, ensuring that every project addresses the needs of families and vulnerable groups within the barangay.', 'uploads/1760268339_25cf709f-f0ff-480a-8202-38deeb0a9b9c.jpg', '2025-10-12', '2026-03-12'),
(17, 'Eldrence Ivan Clorina', 'Barangay Kagawad', 'Kagawad Eldrence Ivan Clorina handles projects related to finance, livelihood, and infrastructure development. His analytical approach and transparency in managing barangay resources contribute to the smooth execution of local initiatives. Ivan is known for his commitment to accountability and for encouraging residents to take part in community improvement programs.', 'uploads/1760268429_323a8bc8-03c2-4c5e-aa28-c296c096be4a.jpg', '2025-10-12', '2026-02-12'),
(18, 'Christopher Alvarez', 'Barangay Kagawad', 'Kagawad Christopher Alvarez focuses on public service programs that improve the daily lives of residents. He plays a key role in coordinating community clean-ups, disaster preparedness drills, and youth outreach projects. His leadership style emphasizes teamwork, collaboration, and proactive response to community concerns.', 'uploads/1760268506_c82fbe13-a71b-4231-87cd-1836993a0c1c.jpg', '2025-10-12', '2026-03-12');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` varchar(50) NOT NULL,
  `receiver_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 1,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message`, `timestamp`, `is_read`, `image_path`) VALUES
(27, '55305', '10775', 'dsd', '2025-09-04 02:53:51', 1, NULL),
(28, '46365', '55305', 'Hello', '2025-09-05 06:58:07', 1, NULL),
(29, '55305', '10775', 'hetyyt', '2025-09-06 14:21:35', 1, NULL),
(30, '55305', '10775', 'hi po', '2025-09-20 12:12:51', 1, NULL),
(31, '55305', '4735', 'ds', '2025-09-20 13:34:24', 1, NULL),
(32, '55305', '4735', '', '2025-09-21 00:48:56', 1, 'uploads/img_68cf4b783b9e34.59172225.jpg'),
(33, '55305', '10775', '', '2025-09-21 00:51:58', 1, 'uploads/img_68cf4c2e06c5b3.80238362.jpg'),
(34, '10775', '55305', 'hii booss', '2025-09-21 00:52:41', 1, NULL),
(35, '10775', '55305', '', '2025-09-21 00:52:48', 1, 'uploads/img_68cf4c6030fa53.24011842.jpg'),
(36, '10775', '55305', '', '2025-09-21 01:00:47', 1, 'uploads/img_68cf4e3f19f3f8.21424693.jpg'),
(37, '10775', '55305', 'sss', '2025-09-21 01:11:08', 1, NULL),
(38, '10775', '55305', 'heloo', '2025-09-21 01:11:15', 1, NULL),
(40, '55305', '10775', 'sss', '2025-09-21 08:28:06', 1, NULL),
(41, '10775', '55305', 'miki miki', '2025-09-23 02:22:03', 1, NULL),
(42, '10775', '55305', '', '2025-09-23 02:22:13', 1, 'uploads/img_68d20455a38349.52652456.png'),
(43, '55305', '4735', '', '2025-09-27 22:00:17', 1, 'uploads/img_68d85e71c6db42.52474689.jpg'),
(46, '55305', '4735', '', '2025-09-27 22:08:47', 1, 'uploads/img_68d8606f066ae5.26870806.png'),
(48, '55305', '4735', '', '2025-09-27 22:16:51', 1, 'uploads/img_68d862531dc470.10818427.png'),
(49, '55305', '4735', 'e', '2025-09-28 00:10:17', 1, NULL),
(51, '10775', '55305', 'sdsd', '2025-09-28 00:43:14', 1, NULL),
(52, '10775', '55305', 'hi', '2025-09-28 00:43:26', 1, NULL),
(53, '10775', '55305', 'a', '2025-10-01 09:19:00', 1, NULL),
(54, '10775', '96617', 'sa', '2025-10-01 09:29:41', 0, NULL),
(55, '55305', '10775', 'sss', '2025-10-02 11:52:56', 1, NULL),
(56, '55305', '10775', '', '2025-10-04 11:44:43', 1, 'uploads/img_68e108abe58a81.70739093.jpg'),
(57, '55305', '10775', 'afsdfa', '2025-10-05 00:48:05', 1, NULL),
(58, '55305', '10775', 'afasf', '2025-10-05 00:48:07', 1, NULL),
(59, '10775', '55305', 'HEY', '2025-10-05 01:49:09', 1, NULL),
(60, '10775', '55305', 'HEYY', '2025-10-05 01:49:27', 1, 'uploads/img_68e1cea7b08d29.44273113.png'),
(61, '10775', '55305', 'heyyy', '2025-10-05 23:01:48', 1, NULL),
(62, '10775', '55305', 'helooo', '2025-10-05 23:01:58', 1, NULL),
(63, '55305', '96611', 'dgdg', '2025-10-06 01:50:28', 0, NULL),
(64, '55305', '10775', '', '2025-10-08 16:09:44', 1, 'uploads/img_68e68cc88654f4.93454216.jpg'),
(65, '55305', '4735', 'sss', '2025-10-08 16:27:25', 1, NULL),
(66, '55305', '96662', 'Hi', '2025-10-08 17:06:48', 0, NULL),
(67, '55305', '10775', 'heloo', '2025-10-11 14:30:30', 1, NULL),
(68, '55305', '96669', 'wd', '2025-10-14 03:05:22', 0, NULL),
(69, '55305', '57527', 'asd', '2025-10-14 03:18:01', 0, NULL),
(70, '55305', '96684', 'asdad', '2025-10-14 03:18:10', 0, NULL),
(71, '55305', '10775', 'Hii', '2025-10-16 11:42:23', 1, NULL),
(74, '10775', '55305', 'hie', '2025-10-17 01:05:26', 1, NULL),
(75, '55305', '10775', 'heloo', '2025-10-17 01:25:16', 1, 'uploads/img_68f19afc7a1c29.40131048.png'),
(77, '55305', '46365', 'Heloo', '2025-10-17 02:50:34', 0, 'uploads/img_68f1aefa424911.53097340.jpg'),
(78, '10775', '55305', 'heloo', '2025-10-18 23:55:13', 1, NULL),
(79, '55305', '10775', 'hii', '2025-10-18 23:55:25', 1, NULL),
(80, '55305', '10775', 'sss', '2025-10-19 01:36:22', 1, NULL),
(81, '55305', '10775', 'Helooo po.  How are yoiuuu', '2025-10-20 01:00:34', 1, 'uploads/img_68f589b27a62d2.42373097.jpg'),
(82, '55305', '10775', 'hey', '2025-10-21 03:18:18', 1, NULL),
(83, '55305', '10775', 'sss', '2025-10-21 03:19:50', 1, NULL),
(84, '10775', '55305', 'hey', '2025-10-21 03:25:03', 1, NULL),
(85, '55305', '10775', 'Ge', '2025-10-21 03:57:03', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `unique_id` int(5) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) NOT NULL,
  `sex` varchar(10) NOT NULL,
  `education` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `household_id` varchar(20) DEFAULT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `is_head` varchar(5) DEFAULT NULL,
  `place_of_birth` varchar(150) NOT NULL,
  `civil_status` varchar(20) NOT NULL,
  `citizenship` varchar(50) NOT NULL,
  `occupation_skills` text NOT NULL,
  `is_pwd` varchar(5) DEFAULT NULL,
  `can_request` tinyint(1) DEFAULT 1,
  `email` varchar(255) DEFAULT NULL,
  `pending_email` varchar(255) DEFAULT NULL,
  `verify_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `skill_description` varchar(255) DEFAULT NULL,
  `can_submit_incidents` tinyint(1) DEFAULT 1 COMMENT 'Allow user to submit incident reports (1=allowed, 0=blocked)',
  `blocked_from_jobfinder` tinyint(1) DEFAULT 0,
  `jobfinder_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Jobfinder profile active status (0=deactivated, 1=active)',
  `jobfinder_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`unique_id`, `surname`, `first_name`, `middle_name`, `birthdate`, `age`, `sex`, `education`, `address`, `household_id`, `relationship`, `is_head`, `place_of_birth`, `civil_status`, `citizenship`, `occupation_skills`, `is_pwd`, `can_request`, `email`, `pending_email`, `verify_token`, `is_verified`, `profile_image`, `skill_description`, `can_submit_incidents`, `blocked_from_jobfinder`, `jobfinder_active`, `jobfinder_verified`) VALUES
(4735, 'dalit', 'jester', 'A', '1999-06-29', 25, 'Male', 'College', 'imus', '123', 'son', 'Yes', 'cavite', 'Single', 'filipino', 'Developer', 'Yes', 1, NULL, '', NULL, 0, '', '', 1, 0, 1, 0),
(9692, 'Ronario', 'Christian', 'd', '0000-00-00', 21, 'Male', 'High School', 'Maragondon', '111', 'head', 'Yes', 'cvite', 'Single', 'filipino', '', 'Yes', 1, 'mikronario824@gmail.com', NULL, NULL, 1, NULL, NULL, 1, 0, 1, 0),
(10775, 'rona', 'Christian', 'miko', '0000-00-00', 32, 'Male', 'High School', 'huhu', '233', 'son', 'Yes', 'cvite', 'Single', 'filipino', 'farmer', 'Yes', 1, 'christianmhicor@gmail.com', NULL, NULL, 1, 'uploads/10775_368368203_6988917077788066_2179475306579483557_n.jpg', 'hiii po farmer po ako', 1, 0, 1, 1),
(39003, 'Money ', 'Me', 'No', '2002-06-27', 23, 'Male', 'Undergrad', 'canite', '32', 'son', 'No', 'tabya', 'Married', 'sdsd', '', 'Yes', 1, NULL, '', NULL, 0, '', '', 1, 1, 1, 0),
(46365, 'Doe', 'John ', 'Cruz', '2004-05-11', 21, 'Female', 'Graduate', 'BIK 1 LOT 123 Brgy Sabang', 'H0055', 'Father', 'Yes', 'Cavite', 'Single', 'Filipino', 'Computer Technician', 'Yes', 1, NULL, '', NULL, 0, '', '', 1, 0, 1, 0),
(55305, 'miko', 'Christian', 'D', '2004-02-26', 21, 'Male', 'Vocationall', 'Blk 18 lot 20 Ora Boulevard Kahaya Place Dasmarinas Cavite', '54', 'fon', 'No', 'refe', 'Married', 'refe', 'farmer', 'No', 1, 'mikronario824@gmail.com', NULL, NULL, 1, 'uploads/55305_490801734_2097920620635068_2009929446491494947_n.jpg', '1233', 1, 0, 1, 1),
(57527, 'kupal', 'MIL', 'LIM', '2025-09-29', 23, 'Female', 'Elementary', 'Maragondon', '233', 'son', 'Yes', 'cvite', 'Single', 'filipino', '', 'Yes', 1, 'hiikhik094@gmail.com', 'ronariomiksss@gmail.com', 'c970e26118dbf1ee22b582b1b00624d1', 0, '', '', 1, 0, 1, 0),
(61803, 'step', 'Step', 'step', '2000-02-02', 25, 'Female', 'College', 'dasma', '233', 'Wife', '0', 'cvite', 'Married', 'filipino', '', '0', 1, NULL, '', NULL, 0, '', '', 1, 0, 1, 0),
(92718, 'tanag', 'Ivan', 'C', '1923-02-01', 102, 'Female', 'Elementary', 'Maragondon', '123', '', 'Yes', 'cvite', 'Divorced', 'filipino', '', 'Yes', 1, NULL, '', NULL, 0, '', '', 1, 0, 1, 0),
(96585, 'ANGEL', 'CHRISTINE', 'D', '1990-09-02', 35, 'Male', 'High School', 'Maragondon', '233', 'son', 'No', 'imus', 'Single', 'filipino', 'bahy', 'Yes', 1, NULL, '', NULL, 0, '', '', 1, 0, 1, 0),
(96656, 'Pizano', 'aliyo', 'juan', '2003-12-10', 21, 'Male', 'Vocational', 'dscfvgbhjk', '432', 'pader', '0', 'lpkoiuytr', 'Single', 'rtygjhklk', 'Maniniyot', '0', 1, NULL, NULL, NULL, 0, NULL, 'sadfghjkl', 1, 0, 1, 0),
(96669, 'Bello', 'Ronak', 'w', '2004-02-10', 21, 'Male', 'High School', 'BRW#', '21', 'Son', '0', 'CMBD', 'Single', 'DFS', 'TECK', '0', 1, NULL, '', NULL, 0, '', '', 1, 0, 1, 0),
(96684, 'Sabbii', 'Jeromi', 'A', '1998-02-12', 27, 'Male', 'Vocational', 'DASMA', '21', '', 'Yes', 'CAVITE', 'Married', 'FILIFINO', 'DEV', 'No', 1, NULL, NULL, NULL, 0, NULL, '', 1, 0, 1, 0),
(96687, 'Fitalvo', 'Josephine', 'Delloro', '2000-02-01', 25, 'Female', 'Graduate', 'brgy dito lng', '324', 'head', 'Yes', 'Cavite', 'Single', 'Filipino', 'Developer', 'No', 1, NULL, NULL, NULL, 0, 'uploads/96687_image_1760665412475.jpg', 'developer', 1, 0, 1, 0),
(96688, 'Ronario', 'Juan', 'Santos', '2000-01-01', 33, 'Male', 'College', '123 Main St', 'H001', 'Head', 'Yes', 'Cebu', 'Single', 'Filipino', 'Carpenter', 'No', 1, '', NULL, NULL, 0, NULL, NULL, 1, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `message_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suggestions`
--

INSERT INTO `suggestions` (`message_id`, `userid`, `subject`, `message`, `created_at`) VALUES
(14, 55305, 'fsd', 'wffwe', '2025-10-10 05:14:41'),
(17, 55305, 'Hi', 'Po', '2025-10-17 02:51:00'),
(18, 55305, 'miko', 'helooo', '2025-10-17 03:37:20');

-- --------------------------------------------------------

--
-- Table structure for table `useraccounts`
--

CREATE TABLE `useraccounts` (
  `account_id` int(11) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0 COMMENT 'User online status: 1=online, 0=offline',
  `last_active` datetime DEFAULT NULL COMMENT 'Last activity timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `useraccounts`
--

INSERT INTO `useraccounts` (`account_id`, `userid`, `email`, `password`, `reset_token`, `reset_expires`, `surname`, `is_online`, `last_active`) VALUES
(1, 55305, 'mikronario824@gmail.com', '12345678', '0518b1025dcbacc3ab0484c42dbf61eb', '2025-10-10 08:20:54', NULL, 1, '2025-10-24 10:46:34'),
(19, 9692, 'mikronario824@gmail.com', '123456789', NULL, NULL, 'Ronario', 0, NULL),
(21, 57527, 'hiikhik094@gmail.com', '123456789', NULL, NULL, 'Angel', 0, '2025-10-24 10:15:18'),
(22, 4735, '', '4735', NULL, NULL, 'dalit', 0, NULL),
(27, 92718, '', NULL, NULL, NULL, 'tanag', 0, NULL),
(28, 10775, 'christianmhicor@gmail.com', '10775', NULL, NULL, 'rona', 0, '2025-10-21 12:18:43'),
(49, 46365, '', NULL, NULL, NULL, 'Doe', 0, NULL),
(55, 96585, '', NULL, NULL, NULL, 'ANGEL', 0, NULL),
(63, 39003, '', '39003', NULL, NULL, 'Money ', 0, NULL),
(142, 96669, '', NULL, NULL, NULL, 'Bello', 0, NULL),
(143, 96684, '', NULL, NULL, NULL, 'Sabbii', 0, NULL),
(146, 96687, '', '12345678', NULL, NULL, 'Fitalvo', 0, NULL),
(148, 61803, '', NULL, NULL, NULL, 'step', 0, NULL),
(152, 96656, '', NULL, NULL, NULL, 'Pizano', 0, NULL),
(153, 96688, '', NULL, NULL, NULL, 'Ronario', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_accounts`
--
ALTER TABLE `admin_accounts`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `idx_is_online` (`is_online`),
  ADD KEY `idx_last_active` (`last_active`);

--
-- Indexes for table `admin_chats`
--
ALTER TABLE `admin_chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_announcements`
--
ALTER TABLE `archived_announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_brgy_officials`
--
ALTER TABLE `archived_brgy_officials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archived_certificate_requests`
--
ALTER TABLE `archived_certificate_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_certificate_requests_residents` (`resident_unique_id`);

--
-- Indexes for table `archived_incident_reports`
--
ALTER TABLE `archived_incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `certificate_content`
--
ALTER TABLE `certificate_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificate_options`
--
ALTER TABLE `certificate_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_certificate_requests_residents` (`resident_unique_id`);

--
-- Indexes for table `chatbot_responses`
--
ALTER TABLE `chatbot_responses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_ratings`
--
ALTER TABLE `chat_ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chat_reports`
--
ALTER TABLE `chat_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_id` (`reported_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `deleted_residents`
--
ALTER TABLE `deleted_residents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `main_admin`
--
ALTER TABLE `main_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_is_online` (`is_online`),
  ADD KEY `idx_last_active` (`last_active`);

--
-- Indexes for table `manage_brgy_officials`
--
ALTER TABLE `manage_brgy_officials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`unique_id`),
  ADD UNIQUE KEY `unique_id` (`unique_id`),
  ADD KEY `idx_jobfinder_active` (`jobfinder_active`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `userid` (`userid`);

--
-- Indexes for table `useraccounts`
--
ALTER TABLE `useraccounts`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `userid` (`userid`),
  ADD KEY `idx_is_online` (`is_online`),
  ADD KEY `idx_last_active` (`last_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_chats`
--
ALTER TABLE `admin_chats`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1508;

--
-- AUTO_INCREMENT for table `archived_brgy_officials`
--
ALTER TABLE `archived_brgy_officials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `archived_certificate_requests`
--
ALTER TABLE `archived_certificate_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `archived_incident_reports`
--
ALTER TABLE `archived_incident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `certificate_content`
--
ALTER TABLE `certificate_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `certificate_options`
--
ALTER TABLE `certificate_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `chatbot_responses`
--
ALTER TABLE `chatbot_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `chat_ratings`
--
ALTER TABLE `chat_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `chat_reports`
--
ALTER TABLE `chat_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `deleted_residents`
--
ALTER TABLE `deleted_residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `main_admin`
--
ALTER TABLE `main_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `manage_brgy_officials`
--
ALTER TABLE `manage_brgy_officials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `unique_id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96689;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `useraccounts`
--
ALTER TABLE `useraccounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_chats`
--
ALTER TABLE `admin_chats`
  ADD CONSTRAINT `admin_chats_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `useraccounts` (`userid`);

--
-- Constraints for table `certificate_requests`
--
ALTER TABLE `certificate_requests`
  ADD CONSTRAINT `fk_certificate_requests_residents` FOREIGN KEY (`resident_unique_id`) REFERENCES `residents` (`unique_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_certificate_resident` FOREIGN KEY (`resident_unique_id`) REFERENCES `residents` (`unique_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD CONSTRAINT `incident_reports_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `useraccounts` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD CONSTRAINT `suggestions_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `useraccounts` (`userid`) ON DELETE CASCADE;

--
-- Constraints for table `useraccounts`
--
ALTER TABLE `useraccounts`
  ADD CONSTRAINT `useraccounts_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `residents` (`unique_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
