-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 03:33 PM
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
-- Database: `blood_donation_db`
--
CREATE DATABASE IF NOT EXISTS `blood_donation_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `blood_donation_db`;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_role`, `action_type`, `description`, `created_at`) VALUES
(1, 22, 'recipient', 'create_request', 'Recipient ayub submitted blood request #18 for blood group AB-.', '2026-03-24 23:29:30'),
(2, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #18.', '2026-03-24 23:29:57'),
(3, 20, 'donor', 'express_interest', 'Donor Sadeka expressed interest in request #10.', '2026-03-24 23:31:49'),
(4, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to inactive.', '2026-03-25 18:11:28'),
(5, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to active.', '2026-03-25 18:11:34'),
(6, 23, 'recipient', 'create_request', 'Recipient Abir submitted blood request #19 for blood group B-.', '2026-03-28 17:44:37'),
(7, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #19.', '2026-03-28 18:00:39'),
(8, NULL, 'donor', 'express_interest', 'Donor Jahan expressed interest in request #19.', '2026-03-28 18:04:08'),
(9, 18, 'blood_bank', 'select_donor', 'Blood bank Central Blood Bank Network selected donor Jahan (ID 24) for request #19.', '2026-03-28 18:05:17'),
(10, 18, 'blood_bank', 'schedule_appointment', 'Blood bank Central Blood Bank Network scheduled an appointment for request #19 on 2026-03-29T11:00 at Frederiksberg Hospital.', '2026-03-28 18:08:21'),
(11, NULL, 'donor', 'confirm_appointment', 'Donor Jahan confirmed appointment #3 for request #19.', '2026-03-28 18:09:53'),
(12, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #19 with donor Jahan.', '2026-03-28 18:12:56'),
(13, 21, 'donor', 'confirm_appointment', 'Donor Hamja confirmed appointment #2 for request #17.', '2026-04-02 16:42:50'),
(14, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #17 with donor Hamja.', '2026-04-02 16:43:56'),
(15, 18, 'blood_bank', 'select_donor', 'Blood bank Central Blood Bank Network selected donor Sadeka (ID 20) for request #10.', '2026-04-02 17:41:50'),
(16, 18, 'blood_bank', 'reject_request', 'Blood bank Central Blood Bank Network rejected request #12.', '2026-04-02 17:50:33'),
(17, 21, 'donor', 'express_interest', 'Donor Hamja expressed interest in request #13.', '2026-04-06 09:39:59'),
(18, 23, 'recipient', 'create_request', 'Recipient Abir submitted blood request #20 for blood group B-.', '2026-04-12 17:04:21'),
(19, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #20.', '2026-04-12 17:29:52'),
(20, 25, 'donor', 'express_interest', 'Donor jumon expressed interest in request #20.', '2026-04-12 17:37:58'),
(21, 26, 'donor', 'express_interest', 'Donor Sam expressed interest in request #18.', '2026-04-12 17:59:21'),
(22, 17, 'recipient', 'create_request', 'Recipient Azrin submitted blood request #21 for blood group AB-.', '2026-04-12 18:00:38'),
(23, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #21.', '2026-04-12 18:01:04'),
(24, 26, 'donor', 'express_interest', 'Donor Sam expressed interest in request #21.', '2026-04-12 18:03:17'),
(25, 18, 'blood_bank', 'schedule_appointment', 'Blood bank Central Blood Bank Network scheduled appointment for request #16 with donor #21 on 2026-04-13T10:40.', '2026-04-12 20:43:29'),
(26, 21, 'donor', 'confirm_appointment', 'Donor Hamja confirmed appointment #4 for request #16.', '2026-04-12 20:47:00'),
(27, 17, 'recipient', 'create_request', 'Recipient Azrin submitted urgent blood request #22 for blood group A-.', '2026-04-12 21:55:10'),
(28, 22, 'recipient', 'create_request', 'Recipient ayub submitted normal blood request #23 for blood group O+.', '2026-04-12 22:28:01'),
(29, 18, 'blood_bank', 'schedule_appointment', 'Blood bank Central Blood Bank Network scheduled appointment for request #10 with donor #20 on 2026-04-14T13:45.', '2026-04-13 10:46:18'),
(30, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #26 (Sam) for request #18.', '2026-04-13 20:12:25'),
(31, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to inactive.', '2026-04-14 16:56:10'),
(32, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor Sam (ID 26) to inactive.', '2026-04-14 17:07:08'),
(33, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to active.', '2026-04-14 17:07:28'),
(34, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor Sam (ID 26) to active.', '2026-04-14 17:11:22'),
(35, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor Sam (ID 26) to inactive.', '2026-04-14 17:18:45'),
(36, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor Sam (ID 26) to active.', '2026-04-14 17:22:18'),
(37, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to inactive.', '2026-04-14 17:24:34'),
(38, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to active.', '2026-04-14 17:30:24'),
(39, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to inactive.', '2026-04-14 17:30:33'),
(40, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Muna (ID 14) to inactive.', '2026-04-14 17:30:43'),
(41, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Ayesha (ID 4) to active.', '2026-04-14 17:30:53'),
(42, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Azrin (ID 17) to inactive.', '2026-04-14 17:30:59'),
(43, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of blood_bank Central Blood Bank Network (ID 18) to inactive.', '2026-04-14 17:31:25'),
(44, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of blood_bank Central Blood Bank Network (ID 18) to active.', '2026-04-14 17:32:14'),
(45, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Azrin (ID 17) to active.', '2026-04-14 17:32:20'),
(46, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of recipient Muna (ID 14) to active.', '2026-04-14 17:32:26'),
(47, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor Sam (ID 26) to inactive.', '2026-04-14 17:33:12'),
(48, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor jumon (ID 25) to inactive.', '2026-04-14 17:33:29'),
(49, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor jumon (ID 25) to active.', '2026-04-14 18:10:10'),
(50, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor Sam (ID 26) to active.', '2026-04-14 18:15:01'),
(51, 10, 'admin', 'toggle_user_status', 'Admin Amir changed account status of donor samia (ID 19) to inactive.', '2026-04-14 18:15:15'),
(52, 17, 'recipient', 'create_request', 'Recipient Azrin submitted urgent blood request #24 for blood group B-.', '2026-04-16 20:25:45'),
(53, 14, 'recipient', 'create_request', 'Recipient Muna submitted urgent blood request #25 for blood group AB-.', '2026-04-16 20:37:12'),
(54, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #25.', '2026-04-16 20:38:37'),
(55, 26, 'donor', 'express_interest', 'Donor Sam expressed interest in request #25.', '2026-04-16 20:39:28'),
(56, 25, 'donor', 'express_interest', 'Donor jumon expressed interest in request #24.', '2026-04-16 20:57:24'),
(57, 18, 'blood_bank', 'schedule_appointment', 'Blood bank Central Blood Bank Network scheduled appointment for request #18 with donor #26 on 2026-04-17T01:00.', '2026-04-16 21:03:37'),
(58, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #23.', '2026-04-16 21:06:41'),
(59, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #25 (jumon) for request #24.', '2026-04-16 21:28:38'),
(60, 26, 'donor', 'request_reschedule', 'Donor Sam requested appointment reschedule for appointment #6 and request #18.', '2026-04-16 21:36:58'),
(61, 18, 'blood_bank', 'propose_reschedule_slot', 'Blood bank Central Blood Bank Network proposed a new slot for appointment #6 and request #18.', '2026-04-16 22:53:46'),
(62, 26, 'donor', 'confirm_appointment', 'Donor Sam confirmed appointment #6 for request #18.', '2026-04-16 22:54:54'),
(63, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #18 with donor Sam.', '2026-04-16 22:57:13'),
(64, 25, 'donor', 'express_interest', 'Donor jumon expressed interest in request #25.', '2026-04-16 23:05:34'),
(65, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #25 (jumon) for request #25.', '2026-04-16 23:06:29'),
(66, 18, 'blood_bank', 'schedule_appointment', 'Blood bank Central Blood Bank Network scheduled appointment for request #25 with donor #25 on 2026-04-18T02:00.', '2026-04-16 23:08:08'),
(67, 17, 'recipient', 'create_request', 'Recipient Azrin submitted urgent blood request #26 for blood group A+.', '2026-04-17 08:17:41'),
(68, 18, 'blood_bank', 'approve_request', 'Blood bank Central Blood Bank Network approved request #26.', '2026-04-17 08:29:54'),
(69, 17, 'recipient', 'create_request_stock_fulfilled', 'Recipient Azrin submitted urgent request #27, fulfilled directly from stock.', '2026-04-17 21:48:59'),
(70, 28, 'donor', 'express_interest', 'Donor Sadeka samia expressed interest in request #26.', '2026-04-17 21:52:39'),
(71, 17, 'recipient', 'create_request', 'Recipient Azrin submitted normal blood request #28 for blood group A+.', '2026-04-17 22:12:24'),
(72, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #28 and fulfilled it from stock.', '2026-04-17 22:24:29'),
(73, 14, 'recipient', 'create_request', 'Recipient Muna submitted normal blood request #29 for blood group AB+.', '2026-04-17 22:25:59'),
(74, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #29 and fulfilled it from stock.', '2026-04-17 22:27:00'),
(75, 14, 'recipient', 'create_request', 'Recipient Muna submitted urgent blood request #30 for blood group O+.', '2026-04-18 12:28:45'),
(76, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #30 and fulfilled it from stock.', '2026-04-18 12:29:24'),
(77, 18, 'blood_bank', 'schedule_stock_donation', 'Central Blood Bank Network scheduled stock donation request #1.', '2026-04-18 16:52:10'),
(78, 18, 'blood_bank', 'schedule_stock_donation', 'Central Blood Bank Network scheduled stock donation request #2.', '2026-04-18 17:07:33'),
(79, 18, 'blood_bank', 'complete_stock_donation', 'Central Blood Bank Network completed stock donation request #2 and updated branch stock.', '2026-04-18 17:11:04'),
(80, 18, 'blood_bank', 'schedule_stock_donation', 'Central Blood Bank Network scheduled stock donation request #3.', '2026-04-18 21:03:29'),
(81, 18, 'blood_bank', 'schedule_stock_donation', 'Central Blood Bank Network scheduled stock donation request #4.', '2026-04-19 10:02:09'),
(82, 28, 'donor', 'express_interest', 'Donor Sadeka samia expressed interest in request #13.', '2026-04-19 16:32:35'),
(83, 20, 'donor', 'express_interest', 'Donor Sadeka expressed interest in request #23.', '2026-04-19 16:40:44'),
(84, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #28 (Sadeka samia) for request #13.', '2026-04-19 16:49:14'),
(85, 18, 'blood_bank', 'schedule_appointment', 'Central Blood Bank Network scheduled request-based appointment for request #13 at branch Amager Branch.', '2026-04-19 16:50:05'),
(86, 28, 'donor', 'express_interest', 'Donor Sadeka samia expressed interest in request #9.', '2026-04-19 17:36:58'),
(87, 22, 'recipient', 'create_request', 'Recipient ayub submitted normal blood request #31 for blood group A+.', '2026-04-19 17:44:28'),
(88, 18, 'blood_bank', 'reject_request', 'Blood bank Central Blood Bank Network rejected request #31.', '2026-04-19 17:46:26'),
(89, 32, 'recipient', 'create_request', 'Recipient Adnan submitted normal blood request #32 for blood group B+.', '2026-04-19 18:18:24'),
(90, 18, 'blood_bank', 'schedule_stock_donation', 'Central Blood Bank Network scheduled stock donation request #5.', '2026-04-19 20:18:18'),
(91, 28, 'donor', 'request_reschedule_appointment', 'Donor Sadeka samia requested reschedule for appointment #8.', '2026-04-19 20:26:00'),
(92, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #28 (Sadeka samia) for request #9.', '2026-04-19 20:33:18'),
(93, 28, 'donor', 'confirm_stock_appointment', 'Donor Sadeka samia confirmed stock donation appointment request #3.', '2026-04-19 21:30:14'),
(94, 28, 'donor', 'request_stock_reschedule', 'Donor Sadeka samia requested a new slot for stock donation request #5.', '2026-04-19 21:37:30'),
(95, 17, 'recipient', 'create_request', 'Recipient Azrin submitted normal blood request #33 for blood group B+.', '2026-04-19 22:33:07'),
(96, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #33 and fulfilled it from stock.', '2026-04-19 22:34:37'),
(97, 18, 'blood_bank', 'schedule_stock_donation', 'Blood bank scheduled/rescheduled stock donation request #6.', '2026-04-19 22:40:05'),
(98, 33, 'donor', 'request_stock_reschedule', 'Donor sami requested a new slot for stock donation request #6.', '2026-04-19 22:42:38'),
(99, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #22 and fulfilled it from stock.', '2026-04-20 11:44:13'),
(100, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #28 (Sadeka samia) for request #26.', '2026-04-20 11:47:41'),
(101, 25, 'donor', 'express_interest', 'Donor jumon expressed interest in request #21.', '2026-04-20 12:01:10'),
(102, 34, 'recipient', 'create_request', 'Recipient Sanju submitted normal blood request #34 for blood group A-.', '2026-04-20 21:16:22'),
(103, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #34 to donor matching.', '2026-04-20 22:57:29'),
(104, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #32 to donor matching.', '2026-04-20 23:53:53'),
(105, 4, 'recipient', 'create_request', 'Recipient Ayesha submitted urgent blood request #35 for blood group AB+.', '2026-04-21 10:57:43'),
(106, 18, 'blood_bank', 'reject_request', 'Blood bank rejected request #35. Reason: Too many requests', '2026-04-21 17:08:56'),
(107, 22, 'recipient', 'create_request', 'Recipient ayub submitted urgent blood request #36 for blood group O+.', '2026-04-21 20:31:37'),
(108, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #36 and fulfilled it from stock.', '2026-04-21 20:32:56'),
(109, 22, 'recipient', 'create_request', 'Recipient ayub submitted urgent blood request #37 for blood group A+.', '2026-04-21 20:34:50'),
(110, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #37 to donor matching.', '2026-04-21 20:35:35'),
(111, 20, 'donor', 'express_interest', 'Donor Sadeka expressed interest in request #37.', '2026-04-21 20:36:43'),
(112, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #20 (Sadeka) for request #37.', '2026-04-21 20:38:27'),
(113, 18, 'blood_bank', 'schedule_appointment', 'Central Blood Bank Network scheduled request-based appointment for request #37 at branch Amager Branch.', '2026-04-21 20:39:23'),
(114, 20, 'donor', 'confirm_appointment', 'Donor Sadeka confirmed appointment #9 for request #37.', '2026-04-21 20:41:07'),
(115, 17, 'recipient', 'create_request', 'Recipient Azrin submitted urgent blood request #38 for blood group A+.', '2026-04-25 16:03:01'),
(116, 14, 'recipient', 'create_request', 'Recipient Muna submitted urgent blood request #39 for blood group O+.', '2026-04-27 16:32:39'),
(117, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #39 to donor matching.', '2026-04-27 16:33:15'),
(118, 41, 'recipient', 'create_request', 'Recipient Recipient 1 submitted urgent blood request #40 for blood group A+.', '2026-04-29 20:50:59'),
(119, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #40 to donor matching.', '2026-04-29 20:51:39'),
(120, 40, 'donor', 'express_interest', 'Donor Donor 1 expressed interest in request #40.', '2026-04-29 20:52:23'),
(121, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #40 (Donor 1) for request #40.', '2026-04-29 20:53:45'),
(122, 41, 'recipient', 'create_request', 'Recipient Recipient 1 submitted urgent blood request #41 for blood group AB+.', '2026-04-30 20:29:57'),
(123, 18, 'blood_bank', 'reject_request', 'Blood bank rejected request #41. Reason: Invalid request', '2026-04-30 20:50:44'),
(124, 41, 'recipient', 'create_request', 'Recipient Recipient 1 submitted urgent blood request #42 for blood group A+.', '2026-04-30 20:54:39'),
(125, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #42 and fulfilled it from stock.', '2026-04-30 20:55:10'),
(126, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #16 with donor Hamja.', '2026-04-30 20:55:48'),
(127, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #38 to donor matching.', '2026-04-30 21:36:38'),
(128, 42, 'recipient', 'create_request', 'Recipient Recipient 2 submitted normal blood request #43 for blood group A-.', '2026-04-30 21:46:45'),
(129, 42, 'recipient', 'create_request', 'Recipient Recipient 2 submitted normal blood request #44 for blood group A-.', '2026-04-30 21:59:30'),
(130, 44, 'recipient', 'create_request', 'Recipient Recipient 3 submitted urgent blood request #45 for blood group B+.', '2026-04-30 22:04:28'),
(131, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #44 to donor matching.', '2026-04-30 22:41:56'),
(132, 43, 'donor', 'express_interest', 'Donor Donor 2 expressed interest in request #44.', '2026-04-30 22:45:06'),
(133, 18, 'blood_bank', 'schedule_appointment', 'Central Blood Bank Network scheduled request-based appointment for request #40 at branch Amager Branch.', '2026-04-30 22:51:30'),
(134, 41, 'recipient', 'create_request', 'Recipient Recipient 1 submitted urgent blood request #46 for blood group A+.', '2026-05-01 07:23:59'),
(135, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #25 (jumon) for request #21.', '2026-05-01 07:27:39'),
(136, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #46 to donor matching.', '2026-05-01 13:45:03'),
(137, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 3 (recipient3@gmail.com, ID 44) to inactive.', '2026-05-02 18:36:51'),
(138, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 3 (recipient3@gmail.com, ID 44) to active.', '2026-05-02 18:37:11'),
(139, 10, 'admin', 'delete_user', 'Admin Amir deleted donor user Jahan (jahan@gmail.com, ID 24). Reason: Suspicious', '2026-05-02 18:37:53'),
(140, 40, 'donor', 'confirm_appointment', 'Donor Donor 1 confirmed appointment #10 for request #40.', '2026-05-03 09:17:38'),
(141, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 3 (recipient3@gmail.com, ID 44) to inactive.', '2026-05-03 09:52:02'),
(142, 44, 'recipient', 'create_request', 'Recipient Recipient 3 submitted normal blood request #47 for blood group B+.', '2026-05-03 16:07:35'),
(143, 18, 'blood_bank', 'approve_request_stock_fulfilled', 'Central Blood Bank Network reviewed request #47 and fulfilled it from stock.', '2026-05-03 16:08:21'),
(144, 40, 'donor', 'express_interest', 'Donor Donor 1 expressed interest in request #46.', '2026-05-03 17:56:26'),
(145, 42, 'recipient', 'create_request', 'Recipient Recipient 2 submitted urgent blood request #48 for blood group A-.', '2026-05-03 17:58:59'),
(146, 43, 'donor', 'express_interest', 'Donor Donor 2 expressed interest in request #34.', '2026-05-03 17:59:51'),
(147, 18, 'blood_bank', 'schedule_stock_donation', 'Blood bank scheduled/rescheduled stock donation request #9.', '2026-05-03 18:04:25'),
(148, 43, 'donor', 'confirm_stock_appointment', 'Donor Donor 2 confirmed stock donation appointment request #9.', '2026-05-03 18:05:25'),
(149, 22, 'recipient', 'create_request', 'Recipient ayub submitted urgent blood request #49 for blood group O-.', '2026-05-03 18:35:51'),
(150, 32, 'recipient', 'create_request', 'Recipient Adnan submitted urgent blood request #50 for blood group O+.', '2026-05-03 19:56:40'),
(151, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #37 with donor Sadeka.', '2026-05-03 20:01:33'),
(152, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #43 (Donor 2) for request #34.', '2026-05-03 20:02:06'),
(153, 18, 'blood_bank', 'schedule_appointment', 'Central Blood Bank Network scheduled request-based appointment for request #26 at branch Amager Branch.', '2026-05-03 20:02:50'),
(154, 18, 'blood_bank', 'schedule_stock_donation', 'Blood bank scheduled/rescheduled stock donation request #10.', '2026-05-03 20:13:16'),
(155, 28, 'donor', 'confirm_stock_appointment', 'Donor Sadeka samia confirmed stock donation appointment request #10.', '2026-05-03 20:14:15'),
(156, 18, 'blood_bank', 'complete_stock_donation', 'Central Blood Bank Network completed stock donation request #10 from donor Sadeka samia and added 1 unit of A+ to branch stock.', '2026-05-03 21:40:01'),
(157, 28, 'donor', 'confirm_appointment', 'Donor Sadeka samia confirmed appointment #11 for request #26.', '2026-05-03 21:42:02'),
(158, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #26 with donor Sadeka samia.', '2026-05-03 21:43:32'),
(159, 18, 'blood_bank', 'select_interested_donor', 'Blood bank Central Blood Bank Network selected interested donor #40 (Donor 1) for request #46.', '2026-05-03 22:08:29'),
(160, 18, 'blood_bank', 'schedule_appointment', 'Central Blood Bank Network scheduled request-based appointment for request #46 at branch Copenhagen Branch.', '2026-05-03 22:09:17'),
(161, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network marked donation complete for request #40 with donor Donor 1.', '2026-05-03 22:11:04'),
(162, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 3 (recipient3@gmail.com, ID 44) to active.', '2026-05-03 22:35:00'),
(163, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 1 (recipient1@gmail.com, ID 41) to inactive.', '2026-05-03 22:35:08'),
(164, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 1 (recipient1@gmail.com, ID 41) to active.', '2026-05-03 22:48:52'),
(165, 10, 'admin', 'toggle_user_status', 'Admin Amir changed status of recipient user Recipient 1 (recipient1@gmail.com, ID 41) to inactive.', '2026-05-03 22:49:03'),
(166, 18, 'blood_bank', 'find_donor', 'Blood bank moved request #48 to donor matching.', '2026-05-03 22:54:37'),
(167, 25, 'donor', 'confirm_stock_appointment', 'Donor jumon confirmed stock donation appointment request #1.', '2026-05-03 22:58:02'),
(168, 25, 'donor', 'confirm_appointment', 'Donor jumon confirmed appointment #7 for request #25.', '2026-05-03 22:58:19'),
(169, 18, 'blood_bank', 'complete_donation', 'Blood bank Central Blood Bank Network completed appointment #7 for request #25 with donor jumon.', '2026-05-03 22:59:21'),
(170, 43, 'donor', 'express_interest', 'Donor Donor 2 expressed interest in request #48.', '2026-05-03 23:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `blood_bank_id` int(11) NOT NULL,
  `appointment_date` datetime NOT NULL,
  `appointment_location` varchar(255) NOT NULL,
  `appointment_address` varchar(255) DEFAULT NULL,
  `appointment_city` varchar(100) DEFAULT NULL,
  `appointment_zipcode` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','confirmed','declined','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  `appointment_type` enum('request_based','stock_donation') DEFAULT 'request_based',
  PRIMARY KEY (`id`),
  KEY `fk_appointments_request` (`request_id`),
  KEY `fk_appointments_donor` (`donor_id`),
  KEY `fk_appointments_recipient` (`recipient_id`),
  KEY `fk_appointments_blood_bank` (`blood_bank_id`),
  KEY `fk_appointments_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `request_id`, `donor_id`, `recipient_id`, `blood_bank_id`, `appointment_date`, `appointment_location`, `appointment_address`, `appointment_city`, `appointment_zipcode`, `notes`, `status`, `created_at`, `branch_id`, `appointment_type`) VALUES
(1, 14, 19, 17, 18, '2026-03-24 13:00:00', 'Amagar blood bank', NULL, NULL, NULL, 'Italiensvej 1\r\n\r\n2300 København S', 'completed', '2026-03-22 22:51:42', NULL, 'request_based'),
(2, 17, 21, 22, 18, '2026-03-25 14:00:00', 'Rigshospitalets Blodbank', NULL, NULL, NULL, 'Address: Blegdamsvej 56, 2100 København', 'completed', '2026-03-24 19:02:05', NULL, 'request_based'),
(4, 16, 21, 17, 18, '2026-04-13 10:40:00', 'Bispebjerg Hospital, Bispebjerg Bakke 23,, copenhagen, 2400', 'Bispebjerg Hospital, Bispebjerg Bakke 23,', 'copenhagen', '2400', '', 'completed', '2026-04-12 20:43:29', NULL, 'request_based'),
(5, 10, 20, 14, 18, '2026-04-14 13:45:00', 'Amagar Hospital, copenhagen, 2300', 'Amagar Hospital', 'copenhagen', '2300', 'Adress: Amager Hospital, Italiensvej 1, 2300 København', 'scheduled', '2026-04-13 10:46:18', NULL, 'request_based'),
(6, 18, 26, 22, 18, '2026-04-17 03:00:00', 'Amagar Hospital, copenhagen, 2300', 'Amagar Hospital', 'copenhagen', '2300', '', 'completed', '2026-04-16 21:03:37', NULL, 'request_based'),
(7, 25, 25, 14, 18, '2026-04-18 02:00:00', 'Bispebjerg Hospital, Bispebjerg Bakke 23,, copenhagen, 2300', 'Bispebjerg Hospital, Bispebjerg Bakke 23,', 'copenhagen', '2300', 'Bispebjerg Bakke 23,', 'completed', '2026-04-16 23:08:08', NULL, 'request_based'),
(8, 13, 28, 4, 18, '2026-04-20 10:50:00', 'Italiensvej 1, Copenhagen, 2300', 'Italiensvej 1', 'Copenhagen', '2300', 'Kindly be on time', 'scheduled', '2026-04-19 16:50:05', 1, 'request_based'),
(9, 37, 20, 22, 18, '2026-04-22 09:30:00', 'Italiensvej 1, Copenhagen, 2300', 'Italiensvej 1', 'Copenhagen', '2300', 'Kindly be on time', 'completed', '2026-04-21 20:39:23', 1, 'request_based'),
(10, 40, 40, 41, 18, '2026-05-01 14:50:00', 'Italiensvej 1, Copenhagen, 2300', 'Italiensvej 1', 'Copenhagen', '2300', 'Eat well before donation.', 'completed', '2026-04-30 22:51:30', 1, 'request_based'),
(11, 26, 28, 17, 18, '2026-05-04 14:00:00', 'Italiensvej 1, Copenhagen, 2300', 'Italiensvej 1', 'Copenhagen', '2300', '', 'completed', '2026-05-03 20:02:50', 1, 'request_based'),
(12, 46, 40, 41, 18, '2026-05-05 12:00:00', 'Blegdamsvej 56, Copenhagen, 2100', 'Blegdamsvej 56', 'Copenhagen', '2100', '', 'scheduled', '2026-05-03 22:09:17', 3, 'request_based');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_reschedule_requests`
--

CREATE TABLE IF NOT EXISTS `appointment_reschedule_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `blood_bank_id` int(11) NOT NULL,
  `preferred_datetime` datetime DEFAULT NULL,
  `current_appointment_datetime` datetime DEFAULT NULL,
  `proposed_datetime` datetime DEFAULT NULL,
  `donor_reason` text DEFAULT NULL,
  `blood_bank_note` text DEFAULT NULL,
  `status` enum('requested','resolved') NOT NULL DEFAULT 'requested',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_reschedule_requests`
--

INSERT INTO `appointment_reschedule_requests` (`id`, `appointment_id`, `request_id`, `donor_id`, `recipient_id`, `blood_bank_id`, `preferred_datetime`, `current_appointment_datetime`, `proposed_datetime`, `donor_reason`, `blood_bank_note`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, 18, 26, 22, 18, '2026-04-17 03:00:00', '2026-04-17 01:00:00', '2026-04-17 03:00:00', 'I will be at work on that time', '', 'resolved', '2026-04-16 21:36:58', '2026-04-16 22:53:46'),
(2, 8, 0, 0, 0, 0, '2026-04-20 12:00:00', NULL, NULL, 'I have work on that time.', NULL, 'requested', '2026-04-19 20:26:00', '2026-04-19 20:26:00');

-- --------------------------------------------------------

--
-- Table structure for table `blood_requests`
--

CREATE TABLE IF NOT EXISTS `blood_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_id` int(11) DEFAULT NULL,
  `blood_group` varchar(5) NOT NULL,
  `location` varchar(100) NOT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `urgency` enum('normal','urgent') DEFAULT 'normal',
  `status` enum('pending_review','approved','rejected','matched','scheduled','completed','cancelled') DEFAULT 'pending_review',
  `approved_by` int(11) DEFAULT NULL,
  `matched_donor_id` int(11) DEFAULT NULL,
  `assigned_blood_bank_id` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `patient_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fulfilled_branch_id` int(11) DEFAULT NULL,
  `fulfillment_source` enum('none','stock','donor') DEFAULT 'none',
  `stock_units_used` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `fk_blood_requests_fulfilled_branch` (`fulfilled_branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_requests`
--

INSERT INTO `blood_requests` (`id`, `recipient_id`, `blood_group`, `location`, `address_line`, `city`, `zipcode`, `quantity`, `urgency`, `status`, `approved_by`, `matched_donor_id`, `assigned_blood_bank_id`, `approved_at`, `rejection_reason`, `patient_note`, `created_at`, `fulfilled_branch_id`, `fulfillment_source`, `stock_units_used`) VALUES
(4, 4, 'O-', 'Harlev', NULL, NULL, NULL, 1, 'normal', 'completed', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-13 20:17:18', NULL, 'none', 0),
(5, 4, 'A-', 'Copenhagen', NULL, NULL, NULL, 2, 'urgent', 'completed', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-15 17:23:38', NULL, 'none', 0),
(6, 4, 'B-', 'Harlev', NULL, NULL, NULL, 1, 'normal', 'matched', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-20 19:41:59', NULL, 'none', 0),
(7, 4, 'B-', 'Norrebro', NULL, NULL, NULL, 2, 'normal', 'completed', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-07 13:12:31', NULL, 'none', 0),
(8, 14, 'B+', 'vesterbro', NULL, NULL, NULL, 1, 'urgent', 'completed', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-12 10:50:56', NULL, 'none', 0),
(9, 4, 'A+', 'Copenhagen', NULL, NULL, NULL, 1, 'normal', 'matched', 18, 28, 18, '2026-03-22 23:08:14', NULL, NULL, '2026-03-12 14:52:48', NULL, 'none', 0),
(10, 14, 'B+', 'Copenhagen', NULL, NULL, NULL, 1, 'urgent', 'scheduled', 18, 20, 18, '2026-03-22 23:08:20', NULL, NULL, '2026-03-16 10:34:23', NULL, 'none', 0),
(12, 17, 'A-', 'Harlev', NULL, NULL, NULL, 1, 'normal', 'rejected', 18, NULL, 18, NULL, 'Rejected by blood bank after review.', NULL, '2026-03-16 10:38:30', NULL, 'none', 0),
(13, 4, 'A+', 'Copenhagen', NULL, NULL, NULL, 1, 'urgent', 'scheduled', 18, 28, 18, '2026-03-22 01:53:23', NULL, 'Urgently needed', '2026-03-22 00:42:48', 1, 'donor', 0),
(14, 17, 'AB+', 'Copenhagen', NULL, NULL, NULL, 1, 'urgent', 'completed', 18, 19, 18, '2026-03-22 23:04:01', NULL, 'For pregnant women', '2026-03-22 22:02:09', NULL, 'none', 0),
(15, 14, 'O-', 'Harlev', NULL, NULL, NULL, 1, 'normal', 'approved', 18, NULL, 18, '2026-03-22 23:03:54', NULL, '', '2026-03-22 22:03:12', NULL, 'none', 0),
(16, 17, 'O+', 'Copenhagen', NULL, NULL, NULL, 1, 'normal', 'completed', 18, 21, 18, '2026-03-24 01:28:24', NULL, 'for my mother', '2026-03-24 00:27:54', NULL, 'none', 0),
(17, 22, 'O+', 'Copenhagen', NULL, NULL, NULL, 1, 'normal', 'completed', 18, 21, 18, '2026-03-24 15:16:41', NULL, 'For my sister.', '2026-03-24 14:15:55', NULL, 'none', 0),
(18, 22, 'AB-', 'Norrebro', NULL, NULL, NULL, 1, 'urgent', 'completed', 18, 26, 18, '2026-03-25 00:29:57', NULL, 'urgently needed', '2026-03-24 23:29:30', NULL, 'none', 0),
(19, 23, 'B-', 'Vanlose', NULL, NULL, NULL, 1, 'normal', 'completed', 18, NULL, 18, '2026-03-28 19:00:39', NULL, 'Blood needed for myself.', '2026-03-28 17:44:37', NULL, 'none', 0),
(20, 23, 'B-', 'Gammel Køge Landevej 642, Brøndby Strand, 2660', 'Gammel Køge Landevej 642', 'Brøndby Strand', '2660', 1, 'urgent', 'approved', 18, NULL, 18, '2026-04-12 19:29:52', NULL, 'It\'s really urgent.', '2026-04-12 17:04:21', NULL, 'none', 0),
(21, 17, 'AB-', 'Bronshoj, copenhagen, 2700', 'Bronshoj', 'copenhagen', '2700', 1, 'normal', 'matched', 18, 25, 18, '2026-04-12 20:01:04', NULL, '', '2026-04-12 18:00:38', NULL, 'none', 0),
(22, 17, 'A-', 'Norrebrogade, Copenhagen, 2300', 'Norrebrogade', 'Copenhagen', '2300', 1, 'urgent', 'completed', 18, NULL, 18, '2026-04-20 13:44:13', NULL, '', '2026-04-12 21:55:10', 1, 'stock', 1),
(23, 22, 'O+', 'Køge, Brøndby Strand, 2660', 'Køge', 'Brøndby Strand', '2660', 1, 'normal', 'approved', 18, NULL, 18, '2026-04-16 23:06:41', NULL, '', '2026-04-12 22:28:01', NULL, 'none', 0),
(24, 17, 'B-', 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', 1, 'urgent', 'matched', 18, 25, 18, '2026-04-16 22:29:29', NULL, 'Needed for a ICU patient', '2026-04-16 20:25:45', NULL, 'none', 0),
(25, 14, 'AB-', 'Norrebro, copenhagen, 2300', 'Norrebro', 'copenhagen', '2300', 2, 'urgent', 'completed', 18, 25, 18, '2026-04-16 22:38:37', NULL, 'Urgently Needed', '2026-04-16 20:37:12', NULL, 'none', 0),
(26, 17, 'A+', 'Kastrup, København S, 2300', 'Kastrup', 'København S', '2300', 1, 'urgent', 'completed', 18, 28, 18, '2026-04-17 10:29:54', NULL, 'Needed for ICU patient', '2026-04-17 08:17:40', 1, 'donor', 0),
(27, 17, 'A+', 'Kastrupvej 78, København S, 2300', 'Kastrupvej 78', 'København S', '2300', 1, 'urgent', 'completed', NULL, NULL, 18, NULL, NULL, 'urgent', '2026-04-17 21:48:59', 1, 'stock', 1),
(28, 17, 'A+', 'Kastrupvej 68, København S, 2300', 'Kastrupvej 68', 'København S', '2300', 1, 'normal', 'completed', 18, NULL, 18, '2026-04-18 00:24:29', NULL, '', '2026-04-17 22:12:24', 1, 'stock', 1),
(29, 14, 'AB+', 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', 1, 'normal', 'completed', 18, NULL, 18, '2026-04-18 00:27:00', NULL, 'for my sister', '2026-04-17 22:25:59', 1, 'stock', 1),
(30, 14, 'O+', 'Landevej 52, Brøndby Strand, 2660', 'Landevej 52', 'Brøndby Strand', '2660', 1, 'urgent', 'completed', 18, NULL, 18, '2026-04-18 14:29:24', NULL, 'Serious condition', '2026-04-18 12:28:45', 2, 'stock', 1),
(31, 22, 'A+', 'Bronshoj, copenhagen, 2700', 'Bronshoj', 'copenhagen', '2700', 1, 'normal', 'rejected', 18, NULL, 18, NULL, 'Rejected by blood bank after review.', 'blood Needed for my father', '2026-04-19 17:44:28', NULL, 'none', 0),
(32, 32, 'B+', 'Norrebro 54, copenhagen, 2300', 'Norrebro 54', 'copenhagen', '2300', 2, 'normal', 'approved', NULL, NULL, 18, NULL, NULL, 'Thank you!', '2026-04-19 18:18:24', NULL, 'none', 0),
(33, 17, 'B+', 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', 1, 'normal', 'completed', 18, NULL, 18, '2026-04-20 00:34:37', NULL, '', '2026-04-19 22:33:07', 1, 'stock', 1),
(34, 34, 'A-', 'Gammel Køge Landevej, Brøndby Strand, 2660', 'Gammel Køge Landevej', 'Brøndby Strand', '2660', 1, 'normal', 'matched', NULL, 43, 18, NULL, NULL, 'Thank you in advance', '2026-04-20 21:16:22', NULL, 'none', 0),
(35, 4, 'AB+', 'Norrebro, copenhagen, 2300', 'Norrebro', 'copenhagen', '2300', 1, 'urgent', 'rejected', NULL, NULL, 18, NULL, 'Too many requests', 'Needed for my mother.', '2026-04-21 10:57:43', NULL, 'none', 0),
(36, 22, 'O+', 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', 1, 'urgent', 'completed', 18, NULL, 18, '2026-04-21 22:32:56', NULL, '', '2026-04-21 20:31:37', 1, 'stock', 1),
(37, 22, 'A+', 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', 1, 'urgent', 'completed', NULL, 20, 18, NULL, NULL, '', '2026-04-21 20:34:50', 1, 'donor', 0),
(38, 17, 'A+', 'Gammel Køge, Brøndby Strand, 2660', 'Gammel Køge', 'Brøndby Strand', '2660', 1, 'urgent', 'approved', NULL, NULL, 18, NULL, NULL, 'For my mother', '2026-04-25 16:03:01', NULL, 'none', 0),
(39, 14, 'O+', 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', 1, 'urgent', 'approved', NULL, NULL, 18, NULL, NULL, 'Patient is in ICU', '2026-04-27 16:32:39', NULL, 'none', 0),
(40, 41, 'A+', 'Lundtoftegade 107, København, 2300', 'Lundtoftegade 107', 'København', '2300', 1, 'urgent', 'completed', NULL, 40, 18, NULL, NULL, 'It\'s urgent', '2026-04-29 20:50:59', 1, 'donor', 0),
(41, 41, 'AB+', 'Lundtoftegade 98, copenhagen, 2300', 'Lundtoftegade 98', 'copenhagen', '2300', 1, 'urgent', 'rejected', NULL, NULL, 18, NULL, 'Invalid request', 'Urgently needed.', '2026-04-30 20:29:57', NULL, 'none', 0),
(42, 41, 'A+', 'Lundtoftegade, copenhagen, 2100', 'Lundtoftegade', 'copenhagen', '2100', 1, 'urgent', 'completed', 18, NULL, 18, '2026-04-30 22:55:10', NULL, '', '2026-04-30 20:54:39', 3, 'stock', 1),
(43, 42, 'A-', 'Rødovre Centrum, Rødovre, 2660', 'Rødovre Centrum', 'Rødovre', '2660', 1, 'normal', 'pending_review', NULL, NULL, 18, NULL, NULL, '', '2026-04-30 21:46:45', NULL, 'none', 0),
(44, 42, 'A-', 'Rødovre Centrum, Rødovre, 2610', 'Rødovre Centrum', 'Rødovre', '2610', 1, 'normal', 'approved', NULL, NULL, 18, NULL, NULL, '', '2026-04-30 21:59:30', NULL, 'none', 0),
(45, 44, 'B+', 'Køge, Brøndby Strand, 2660', 'Køge', 'Brøndby Strand', '2660', 2, 'urgent', 'pending_review', NULL, NULL, 18, NULL, NULL, 'For CCU patient', '2026-04-30 22:04:28', NULL, 'none', 0),
(46, 41, 'A+', 'Lundtoftegade 100, copenhagen, 2100', 'Lundtoftegade 100', 'copenhagen', '2100', 1, 'urgent', 'scheduled', NULL, 40, 18, NULL, NULL, '', '2026-05-01 07:23:59', 3, 'donor', 0),
(47, 44, 'B+', 'Hvidovre, Hvidovre, 2650', 'Hvidovre', 'Hvidovre', '2650', 2, 'normal', 'completed', 18, NULL, 18, '2026-05-03 18:08:21', NULL, '', '2026-05-03 16:07:35', 4, 'stock', 2),
(48, 42, 'A-', 'Hvidovre 158, Hvidovre, 2650', 'Hvidovre 158', 'Hvidovre', '2650', 1, 'urgent', 'approved', NULL, NULL, 18, NULL, NULL, '', '2026-05-03 17:58:59', NULL, 'none', 0),
(49, 22, 'O-', 'Kastrupvej 6, 2tv, København S, 2300', 'Kastrupvej 6, 2tv', 'København S', '2300', 1, 'urgent', 'pending_review', NULL, NULL, 18, NULL, NULL, '', '2026-05-03 18:35:51', NULL, 'none', 0),
(50, 32, 'O+', 'Gammel Køge Landevej 6, Brøndby Strand, 2660', 'Gammel Køge Landevej 6', 'Brøndby Strand', '2660', 1, 'urgent', 'pending_review', NULL, NULL, 18, NULL, NULL, '', '2026-05-03 19:56:40', NULL, 'none', 0);

-- --------------------------------------------------------

--
-- Table structure for table `blood_stock`
--

CREATE TABLE IF NOT EXISTS `blood_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `blood_group` varchar(5) NOT NULL,
  `units_available` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_branch_blood_group` (`branch_id`,`blood_group`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_stock`
--

INSERT INTO `blood_stock` (`id`, `branch_id`, `blood_group`, `units_available`, `updated_at`) VALUES
(1, 1, 'A+', 4, '2026-05-03 21:40:01'),
(2, 2, 'A+', 5, '2026-04-17 20:11:32'),
(3, 3, 'A+', 4, '2026-04-30 20:55:10'),
(4, 4, 'A+', 5, '2026-04-17 20:11:32'),
(5, 1, 'A-', 1, '2026-04-20 11:44:13'),
(6, 2, 'A-', 2, '2026-04-17 20:11:32'),
(7, 3, 'A-', 2, '2026-04-17 20:11:32'),
(8, 4, 'A-', 2, '2026-04-17 20:11:32'),
(9, 1, 'B+', 3, '2026-04-19 22:34:37'),
(10, 2, 'B+', 4, '2026-04-17 20:11:32'),
(11, 3, 'B+', 4, '2026-04-17 20:11:32'),
(12, 4, 'B+', 2, '2026-05-03 16:08:21'),
(13, 1, 'B-', 3, '2026-04-18 17:11:04'),
(14, 2, 'B-', 2, '2026-04-17 20:11:32'),
(15, 3, 'B-', 2, '2026-04-17 20:11:32'),
(16, 4, 'B-', 2, '2026-04-17 20:11:32'),
(17, 1, 'O+', 5, '2026-04-21 20:32:56'),
(18, 2, 'O+', 5, '2026-04-18 12:29:24'),
(19, 3, 'O+', 6, '2026-04-17 20:11:32'),
(20, 4, 'O+', 6, '2026-04-17 20:11:32'),
(21, 1, 'O-', 3, '2026-04-17 20:11:32'),
(22, 2, 'O-', 3, '2026-04-17 20:11:32'),
(23, 3, 'O-', 3, '2026-04-17 20:11:32'),
(24, 4, 'O-', 3, '2026-04-17 20:11:32'),
(25, 1, 'AB+', 1, '2026-04-17 22:27:00'),
(26, 2, 'AB+', 2, '2026-04-17 20:11:32'),
(27, 3, 'AB+', 2, '2026-04-17 20:11:32'),
(28, 4, 'AB+', 2, '2026-04-17 20:11:32'),
(29, 1, 'AB-', 1, '2026-04-17 20:11:32'),
(30, 2, 'AB-', 1, '2026-04-17 20:11:32'),
(31, 3, 'AB-', 1, '2026-04-17 20:11:32'),
(32, 4, 'AB-', 1, '2026-04-17 20:11:32');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE IF NOT EXISTS `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blood_bank_user_id` int(11) NOT NULL,
  `branch_name` varchar(150) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `zipcode` varchar(20) NOT NULL,
  `contact_number` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_branches_blood_bank_user` (`blood_bank_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `blood_bank_user_id`, `branch_name`, `address_line`, `city`, `zipcode`, `contact_number`, `is_active`, `created_at`) VALUES
(1, 18, 'Amager Branch', 'Italiensvej 1', 'Copenhagen', '2300', '+4511111111', 1, '2026-04-17 20:11:32'),
(2, 18, 'Brøndby Branch', 'Køgevej 120', 'Brøndby Strand', '2660', '+4522222222', 1, '2026-04-17 20:11:32'),
(3, 18, 'Copenhagen Branch', 'Blegdamsvej 56', 'Copenhagen', '2100', '+4533333333', 1, '2026-04-17 20:11:32'),
(4, 18, 'Hvidovre Branch', 'Kettegård Alle 30', 'Hvidovre', '2650', '+4544444444', 1, '2026-04-17 20:11:32');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE IF NOT EXISTS `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) DEFAULT NULL,
  `blood_bank_id` int(11) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `donation_date` date DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('confirmed','cancelled') DEFAULT 'confirmed',
  `branch_id` int(11) DEFAULT NULL,
  `donation_type` enum('request_based','stock_donation') DEFAULT 'request_based',
  PRIMARY KEY (`id`),
  KEY `donor_id` (`donor_id`),
  KEY `request_id` (`request_id`),
  KEY `fk_donations_blood_bank` (`blood_bank_id`),
  KEY `fk_donations_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `donor_id`, `blood_bank_id`, `request_id`, `donation_date`, `scheduled_date`, `completed_at`, `notes`, `status`, `branch_id`, `donation_type`) VALUES
(7, 19, 18, 14, '2026-03-23', '2026-03-24 13:00:00', '2026-03-23 00:29:51', 'Italiensvej 1\r\n\r\n2300 København S', 'confirmed', NULL, 'request_based'),
(9, 21, 18, 17, '2026-04-02', '2026-03-25 14:00:00', '2026-04-02 18:43:56', 'Address: Blegdamsvej 56, 2100 København', 'confirmed', NULL, 'request_based'),
(10, 26, 18, 18, '2026-04-17', '2026-04-17 03:00:00', '2026-04-17 00:57:13', '', 'confirmed', NULL, 'request_based'),
(12, 21, 18, 16, '2026-04-30', '2026-04-13 10:40:00', '2026-04-30 22:55:48', '', 'confirmed', NULL, 'request_based'),
(13, 20, 18, 37, '2026-05-03', '2026-04-22 09:30:00', '2026-05-03 22:01:33', 'Kindly be on time', 'confirmed', NULL, 'request_based'),
(14, 28, 18, NULL, '2026-05-03', '2026-05-03 12:10:00', '2026-05-03 23:40:01', '[Blood Bank Schedule Note]\nEat well', 'confirmed', 1, 'stock_donation'),
(15, 28, 18, 26, '2026-05-03', '2026-05-04 14:00:00', '2026-05-03 23:43:32', '', 'confirmed', NULL, 'request_based'),
(16, 40, 18, 40, '2026-05-04', '2026-05-01 14:50:00', '2026-05-04 00:11:04', 'Eat well before donation.', 'confirmed', NULL, 'request_based'),
(17, 25, 18, 25, '2026-05-04', '2026-04-18 02:00:00', '2026-05-04 00:59:21', 'Bispebjerg Bakke 23,', 'confirmed', NULL, 'request_based');

-- --------------------------------------------------------

--
-- Table structure for table `donor_interests`
--

CREATE TABLE IF NOT EXISTS `donor_interests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `status` enum('interested','selected','rejected') DEFAULT 'interested',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `donor_id` (`donor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donor_interests`
--

INSERT INTO `donor_interests` (`id`, `request_id`, `donor_id`, `status`, `created_at`) VALUES
(1, 10, 19, 'rejected', '2026-03-24 11:58:59'),
(2, 17, 21, 'selected', '2026-03-24 14:17:52'),
(3, 10, 20, 'selected', '2026-03-24 23:31:49'),
(5, 13, 21, 'rejected', '2026-04-06 09:39:59'),
(6, 20, 25, 'interested', '2026-04-12 17:37:58'),
(7, 18, 26, 'selected', '2026-04-12 17:59:21'),
(8, 21, 26, 'rejected', '2026-04-12 18:03:17'),
(9, 25, 26, 'rejected', '2026-04-16 20:39:28'),
(10, 24, 25, 'selected', '2026-04-16 20:57:24'),
(11, 25, 25, 'selected', '2026-04-16 23:05:34'),
(12, 26, 28, 'selected', '2026-04-17 21:52:39'),
(13, 13, 28, 'selected', '2026-04-19 16:32:35'),
(14, 23, 20, 'interested', '2026-04-19 16:40:44'),
(15, 9, 28, 'selected', '2026-04-19 17:36:58'),
(16, 21, 25, 'selected', '2026-04-20 12:01:10'),
(17, 37, 20, 'selected', '2026-04-21 20:36:43'),
(18, 40, 40, 'selected', '2026-04-29 20:52:23'),
(19, 44, 43, 'interested', '2026-04-30 22:45:06'),
(20, 46, 40, 'selected', '2026-05-03 17:56:26'),
(21, 34, 43, 'selected', '2026-05-03 17:59:51'),
(22, 48, 43, 'interested', '2026-05-03 23:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 10, 'A new blood request has been created by a recipient.', 1, '2026-03-12 10:50:56'),
(2, 14, 'Your blood request has been accepted by a donor.', 1, '2026-03-12 10:54:58'),
(3, 10, 'A donor has responded to a blood request.', 1, '2026-03-12 10:54:58'),
(4, 4, 'Your blood request has been accepted by a donor.', 1, '2026-03-12 11:52:59'),
(6, 10, 'A donor has responded to a blood request.', 1, '2026-03-12 11:52:59'),
(7, 14, 'Your blood request has been marked as completed by the admin.', 1, '2026-03-12 11:54:37'),
(9, 10, 'A new blood request has been created by a recipient.', 1, '2026-03-12 14:52:48'),
(10, 10, 'A new blood request has been created by a recipient.', 1, '2026-03-16 10:34:23'),
(11, 10, 'A new blood request has been created by a recipient.', 1, '2026-03-16 10:36:15'),
(12, 10, 'A new blood request has been created by a recipient.', 1, '2026-03-16 10:38:30'),
(13, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-22 00:42:48'),
(14, 4, 'Your blood request has been approved by the blood bank.', 1, '2026-03-22 00:53:23'),
(15, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-22 22:02:09'),
(16, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-22 22:03:12'),
(17, 14, 'Your blood request has been approved by the blood bank.', 0, '2026-03-22 22:03:54'),
(18, 17, 'Your blood request has been approved by the blood bank.', 1, '2026-03-22 22:04:01'),
(19, 4, 'Your blood request has been approved by the blood bank.', 0, '2026-03-22 22:08:14'),
(20, 14, 'Your blood request has been approved by the blood bank.', 0, '2026-03-22 22:08:20'),
(22, 19, 'You have been selected by the blood bank for a blood donation request.', 1, '2026-03-22 22:29:10'),
(23, 17, 'Your blood request has been matched with a donor by the blood bank.', 1, '2026-03-22 22:29:10'),
(24, 19, 'A blood donation appointment has been scheduled for you by the blood bank.', 0, '2026-03-22 22:51:42'),
(25, 17, 'Your request has been scheduled for donation by the blood bank.', 1, '2026-03-22 22:51:42'),
(26, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-03-22 23:18:44'),
(27, 19, 'Your blood donation has been marked as completed by the blood bank.', 0, '2026-03-22 23:29:51'),
(28, 17, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 1, '2026-03-22 23:29:51'),
(29, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-24 00:27:54'),
(30, 17, 'Your blood request has been approved by the blood bank.', 1, '2026-03-24 00:28:24'),
(31, 21, 'You have been selected by the blood bank for a blood donation request.', 0, '2026-03-24 00:40:05'),
(32, 17, 'Your blood request has been matched with a donor by the blood bank.', 1, '2026-03-24 00:40:05'),
(33, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-24 14:15:55'),
(34, 22, 'Your blood request has been approved by the blood bank.', 0, '2026-03-24 14:16:41'),
(35, 21, 'You have been selected by the blood bank for a blood donation request.', 0, '2026-03-24 14:19:28'),
(36, 22, 'Your blood request has been matched with an interested donor by the blood bank.', 0, '2026-03-24 14:19:28'),
(37, 21, 'A blood donation appointment has been scheduled for you by the blood bank.', 0, '2026-03-24 19:02:05'),
(38, 22, 'Your request has been scheduled for donation by the blood bank.', 0, '2026-03-24 19:02:05'),
(39, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-24 23:29:30'),
(40, 22, 'Your blood request has been approved by the blood bank.', 0, '2026-03-24 23:29:57'),
(41, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-03-28 17:44:37'),
(42, 23, 'Your blood request has been approved by the blood bank.', 0, '2026-03-28 18:00:39'),
(44, 23, 'Your blood request has been matched with an interested donor by the blood bank.', 0, '2026-03-28 18:05:17'),
(46, 23, 'Your request has been scheduled for donation by the blood bank.', 0, '2026-03-28 18:08:21'),
(47, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-03-28 18:09:53'),
(49, 23, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-03-28 18:12:56'),
(50, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-04-02 16:42:50'),
(51, 21, 'Your blood donation has been marked as completed by the blood bank.', 0, '2026-04-02 16:43:56'),
(52, 22, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-04-02 16:43:56'),
(53, 20, 'You have been selected by the blood bank for a blood donation request.', 0, '2026-04-02 17:41:50'),
(54, 14, 'Your blood request has been matched with an interested donor by the blood bank.', 0, '2026-04-02 17:41:50'),
(55, 17, 'Your blood request has been rejected by the blood bank.', 1, '2026-04-02 17:50:33'),
(56, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-12 17:04:21'),
(57, 23, 'Your blood request has been approved by the blood bank.', 0, '2026-04-12 17:29:52'),
(58, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-12 18:00:38'),
(59, 17, 'Your blood request has been approved by the blood bank.', 1, '2026-04-12 18:01:04'),
(60, 21, 'A blood donation appointment has been scheduled for you by the blood bank.', 0, '2026-04-12 20:43:29'),
(61, 17, 'Your request has been scheduled for donation by the blood bank.', 1, '2026-04-12 20:43:29'),
(62, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-04-12 20:47:00'),
(63, 18, '[URGENT] Emergency blood request submitted. Request #22 needs immediate review.', 1, '2026-04-12 21:55:10'),
(64, 10, '[URGENT] A new emergency blood request (#22) has been submitted and requires priority monitoring.', 1, '2026-04-12 21:55:10'),
(65, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-12 22:28:01'),
(66, 10, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-12 22:28:01'),
(67, 20, 'A blood donation appointment has been scheduled for you by the blood bank.', 0, '2026-04-13 10:46:18'),
(68, 14, 'Your request has been scheduled for donation by the blood bank.', 0, '2026-04-13 10:46:18'),
(69, 26, 'You have been selected by the blood bank for a blood donation request.', 0, '2026-04-13 20:12:25'),
(70, 22, 'Your blood request has been matched with an interested donor by the blood bank.', 0, '2026-04-13 20:12:25'),
(71, 18, '[URGENT] Emergency blood request submitted. Request #24 needs immediate review.', 1, '2026-04-16 20:25:45'),
(72, 10, '[URGENT] A new emergency blood request (#24) has been submitted and requires priority monitoring.', 1, '2026-04-16 20:25:45'),
(73, 18, '[URGENT] Emergency blood request submitted. Request #25 needs immediate review.', 1, '2026-04-16 20:37:12'),
(74, 10, '[URGENT] A new emergency blood request (#25) has been submitted and requires priority monitoring.', 1, '2026-04-16 20:37:12'),
(75, 14, 'Your blood request has been approved by the blood bank.', 0, '2026-04-16 20:38:37'),
(76, 26, 'A blood donation appointment has been scheduled for you by the blood bank.', 0, '2026-04-16 21:03:37'),
(77, 22, 'Your request has been scheduled for donation by the blood bank.', 0, '2026-04-16 21:03:37'),
(78, 22, 'Your blood request has been approved by the blood bank.', 0, '2026-04-16 21:06:41'),
(79, 25, 'You have been selected by the blood bank for blood request #24.', 0, '2026-04-16 21:28:38'),
(80, 17, 'Your blood request #24 has been matched with an interested donor by the blood bank.', 0, '2026-04-16 21:28:38'),
(81, 18, 'A donor requested to reschedule appointment #6. Please review and propose a new slot.', 1, '2026-04-16 21:36:58'),
(82, 22, 'The donor requested a reschedule for your appointment related to request #18. The blood bank will review a new slot.', 0, '2026-04-16 21:36:58'),
(83, 26, 'The blood bank proposed a new slot for appointment #6. Please review the updated appointment details.', 0, '2026-04-16 22:53:46'),
(84, 22, 'The blood bank updated the appointment slot for request #18. Please check the latest appointment details.', 0, '2026-04-16 22:53:46'),
(85, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-04-16 22:54:54'),
(86, 26, 'Your blood donation has been marked as completed by the blood bank.', 0, '2026-04-16 22:57:13'),
(87, 22, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-04-16 22:57:13'),
(88, 25, 'You have been selected by the blood bank for blood request #25.', 0, '2026-04-16 23:06:29'),
(89, 14, 'Your blood request #25 has been matched with an interested donor by the blood bank.', 0, '2026-04-16 23:06:29'),
(90, 25, 'A blood donation appointment has been scheduled for you by the blood bank.', 0, '2026-04-16 23:08:08'),
(91, 14, 'Your request has been scheduled for donation by the blood bank.', 0, '2026-04-16 23:08:08'),
(92, 18, '[URGENT] Emergency blood request submitted. Request #26 needs immediate review.', 1, '2026-04-17 08:17:41'),
(93, 10, '[URGENT] A new emergency blood request (#26) has been submitted and requires priority monitoring.', 1, '2026-04-17 08:17:41'),
(94, 17, 'Your blood request has been approved by the blood bank.', 0, '2026-04-17 08:29:54'),
(95, 17, 'Your blood request #27 was fulfilled from available stock at Amager Branch.', 0, '2026-04-17 21:48:59'),
(96, 18, '[STOCK] Request #27 was fulfilled directly from stock at Amager Branch.', 1, '2026-04-17 21:48:59'),
(97, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-17 22:12:24'),
(98, 17, 'Your blood request has been reviewed and fulfilled from available stock at Amager Branch.', 0, '2026-04-17 22:24:29'),
(99, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-17 22:25:59'),
(100, 14, 'Your blood request has been reviewed and fulfilled from available stock at Amager Branch.', 0, '2026-04-17 22:27:00'),
(101, 18, '[URGENT] Emergency blood request submitted. Request #30 needs immediate review.', 1, '2026-04-18 12:28:45'),
(102, 14, 'Your blood request has been reviewed and fulfilled from available stock at Brøndby Branch.', 0, '2026-04-18 12:29:24'),
(103, 18, 'A donor submitted a stock donation request.', 1, '2026-04-18 16:20:43'),
(104, 25, 'Your stock donation request has been scheduled by Central Blood Bank Network.', 0, '2026-04-18 16:52:10'),
(105, 18, 'A donor submitted a stock donation request.', 1, '2026-04-18 16:57:22'),
(108, 18, 'A donor submitted a stock donation request.', 1, '2026-04-18 21:01:35'),
(109, 28, 'Your stock donation request has been scheduled by Central Blood Bank Network.', 0, '2026-04-18 21:03:29'),
(110, 18, 'A donor submitted a stock donation request.', 1, '2026-04-19 10:00:53'),
(111, 20, 'Your stock donation request has been scheduled by Central Blood Bank Network.', 0, '2026-04-19 10:02:09'),
(112, 28, 'You have been selected by the blood bank for blood request #13.', 0, '2026-04-19 16:49:14'),
(113, 4, 'Your blood request #13 has been matched with an interested donor by the blood bank.', 0, '2026-04-19 16:49:14'),
(114, 28, 'A donation appointment has been scheduled for you at Amager Branch.', 0, '2026-04-19 16:50:05'),
(115, 4, 'Your request has been scheduled at Amager Branch.', 0, '2026-04-19 16:50:05'),
(116, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-19 17:44:28'),
(117, 22, 'Your blood request has been rejected by the blood bank.', 0, '2026-04-19 17:46:26'),
(118, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-19 18:18:24'),
(119, 28, 'Your stock donation request has been scheduled by Central Blood Bank Network.', 0, '2026-04-19 20:18:18'),
(120, 18, 'A donor requested reschedule for appointment #8. Please review and propose a new slot.', 1, '2026-04-19 20:26:00'),
(121, 4, 'The donor requested a reschedule for your blood donation appointment. The blood bank will review it.', 0, '2026-04-19 20:26:00'),
(122, 28, 'You have been selected by the blood bank for blood request #9.', 0, '2026-04-19 20:33:18'),
(123, 4, 'Your blood request #9 has been matched with an interested donor by the blood bank.', 0, '2026-04-19 20:33:18'),
(124, 18, 'A donor confirmed the scheduled stock donation appointment (#3).', 1, '2026-04-19 21:30:14'),
(125, 18, 'A donor requested reschedule for stock donation request #5. Please review and schedule a new slot.', 1, '2026-04-19 21:37:30'),
(126, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-19 22:33:07'),
(127, 17, 'Your blood request has been reviewed and fulfilled from available stock at Amager Branch.', 0, '2026-04-19 22:34:37'),
(128, 33, 'A stock donation appointment has been scheduled for request #6 on 2026-04-20T11:30.', 0, '2026-04-19 22:40:05'),
(129, 18, 'A donor requested reschedule for stock donation request #6. Please review and schedule a new slot.', 1, '2026-04-19 22:42:38'),
(130, 17, 'Your blood request has been reviewed and fulfilled from available stock at Amager Branch.', 0, '2026-04-20 11:44:13'),
(131, 28, 'You have been selected by the blood bank for blood request #26.', 0, '2026-04-20 11:47:41'),
(132, 17, 'Your blood request #26 has been matched with an interested donor by the blood bank.', 0, '2026-04-20 11:47:41'),
(133, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-20 21:16:22'),
(134, 34, 'Your blood request #34 has been approved for donor matching.', 0, '2026-04-20 22:57:29'),
(135, 32, 'Your blood request #32 has been approved for donor matching.', 0, '2026-04-20 23:53:53'),
(136, 18, '[URGENT] Emergency blood request submitted. Request #35 needs immediate review.', 1, '2026-04-21 10:57:43'),
(137, 4, 'Your blood request #35 was rejected. Reason: Too many requests', 0, '2026-04-21 17:08:56'),
(138, 18, '[URGENT] Emergency blood request submitted. Request #36 needs immediate review.', 1, '2026-04-21 20:31:37'),
(139, 22, 'Your blood request has been reviewed and fulfilled from available stock at Amager Branch.', 0, '2026-04-21 20:32:56'),
(140, 18, '[URGENT] Emergency blood request submitted. Request #37 needs immediate review.', 1, '2026-04-21 20:34:50'),
(141, 22, 'Your blood request #37 has been approved for donor matching.', 0, '2026-04-21 20:35:35'),
(142, 20, 'You have been selected by the blood bank for blood request #37.', 0, '2026-04-21 20:38:27'),
(143, 22, 'Your blood request #37 has been matched with an interested donor by the blood bank.', 0, '2026-04-21 20:38:27'),
(144, 20, 'A donation appointment has been scheduled for you at Amager Branch.', 0, '2026-04-21 20:39:23'),
(145, 22, 'Your request has been scheduled at Amager Branch.', 0, '2026-04-21 20:39:23'),
(146, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-04-21 20:41:07'),
(147, 18, '[URGENT] Emergency blood request submitted. Request #38 needs immediate review.', 1, '2026-04-25 16:03:01'),
(148, 18, '[URGENT] Emergency blood request submitted. Request #39 needs immediate review.', 1, '2026-04-27 16:32:39'),
(149, 14, 'Your blood request #39 has been approved for donor matching.', 0, '2026-04-27 16:33:15'),
(150, 18, '[URGENT] Emergency blood request submitted. Request #40 needs immediate review.', 1, '2026-04-29 20:50:59'),
(151, 41, 'Your blood request #40 has been approved for donor matching.', 0, '2026-04-29 20:51:39'),
(152, 40, 'You have been selected by the blood bank for blood request #40.', 1, '2026-04-29 20:53:45'),
(153, 41, 'Your blood request #40 has been matched with an interested donor by the blood bank.', 0, '2026-04-29 20:53:45'),
(154, 18, '[URGENT] Emergency blood request submitted. Request #41 needs immediate review.', 1, '2026-04-30 20:29:57'),
(155, 41, 'Your blood request #41 was rejected. Reason: Invalid request', 0, '2026-04-30 20:50:44'),
(156, 18, '[URGENT] Emergency blood request submitted. Request #42 needs immediate review.', 1, '2026-04-30 20:54:39'),
(157, 41, 'Your blood request has been reviewed and fulfilled from available stock at Copenhagen Branch.', 0, '2026-04-30 20:55:10'),
(158, 21, 'Your blood donation has been marked as completed. You are temporarily unavailable until your next eligible date. Next Eligible Date: 2026-07-30.', 0, '2026-04-30 20:55:48'),
(159, 17, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-04-30 20:55:48'),
(160, 17, 'Your blood request #38 has been approved for donor matching.', 0, '2026-04-30 21:36:38'),
(161, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-30 21:46:45'),
(162, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-04-30 21:59:30'),
(163, 18, '[URGENT] Emergency blood request submitted. Request #45 needs immediate review.', 1, '2026-04-30 22:04:28'),
(164, 42, 'Your blood request #44 has been approved for donor matching.', 1, '2026-04-30 22:41:56'),
(165, 40, 'A donation appointment has been scheduled for you at Amager Branch.', 1, '2026-04-30 22:51:30'),
(166, 41, 'Your request has been scheduled at Amager Branch.', 0, '2026-04-30 22:51:30'),
(167, 18, '[URGENT] Emergency blood request submitted. Request #46 needs immediate review.', 1, '2026-05-01 07:23:59'),
(168, 25, 'You have been selected by the blood bank for blood request #21.', 0, '2026-05-01 07:27:39'),
(169, 17, 'Your blood request #21 has been matched with an interested donor by the blood bank.', 0, '2026-05-01 07:27:39'),
(170, 41, 'Your blood request #46 has been approved for donor matching.', 0, '2026-05-01 13:45:03'),
(171, 18, 'A donor has confirmed the scheduled donation appointment.', 1, '2026-05-03 09:17:38'),
(172, 18, 'A new blood request has been submitted and is waiting for review.', 1, '2026-05-03 16:07:35'),
(173, 44, 'Your blood request has been reviewed and fulfilled from available stock at Hvidovre Branch.', 0, '2026-05-03 16:08:21'),
(174, 18, '[URGENT] Emergency blood request submitted. Request #48 needs immediate review.', 1, '2026-05-03 17:58:59'),
(175, 43, 'A stock donation appointment has been scheduled for request #9 on 2026-05-03T15:05.', 1, '2026-05-03 18:04:25'),
(176, 18, 'A donor confirmed the scheduled stock donation appointment (#9).', 1, '2026-05-03 18:05:25'),
(177, 18, '[URGENT] Emergency blood request submitted. Request #49 needs immediate review.', 1, '2026-05-03 18:35:51'),
(178, 18, '[URGENT] Emergency blood request submitted. Request #50 needs immediate review.', 1, '2026-05-03 19:56:40'),
(179, 20, 'Your blood donation has been marked as completed. You are temporarily unavailable until your next eligible date. Next Eligible Date: 2026-08-03.', 0, '2026-05-03 20:01:33'),
(180, 22, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-05-03 20:01:33'),
(181, 43, 'You have been selected by the blood bank for blood request #34.', 1, '2026-05-03 20:02:06'),
(182, 34, 'Your blood request #34 has been matched with an interested donor by the blood bank.', 0, '2026-05-03 20:02:06'),
(183, 28, 'A donation appointment has been scheduled for you at Amager Branch.', 0, '2026-05-03 20:02:50'),
(184, 17, 'Your request has been scheduled at Amager Branch.', 0, '2026-05-03 20:02:50'),
(185, 28, 'A stock donation appointment has been scheduled for request #10 on 2026-05-03T12:10.', 0, '2026-05-03 20:13:16'),
(186, 18, 'A donor confirmed the scheduled stock donation appointment (#10).', 1, '2026-05-03 20:14:15'),
(187, 28, 'Your stock donation has been marked as completed. You are temporarily unavailable for donation. Next Eligible Date: 2026-08-03.', 0, '2026-05-03 21:40:01'),
(188, 18, 'A donor has confirmed the scheduled donation appointment.', 0, '2026-05-03 21:42:02'),
(189, 28, 'Your blood donation has been marked as completed. You are temporarily unavailable until your next eligible date. Next Eligible Date: 2026-08-03.', 0, '2026-05-03 21:43:32'),
(190, 17, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-05-03 21:43:32'),
(191, 40, 'You have been selected by the blood bank for blood request #46.', 1, '2026-05-03 22:08:29'),
(192, 41, 'Your blood request #46 has been matched with an interested donor by the blood bank.', 0, '2026-05-03 22:08:29'),
(193, 40, 'A donation appointment has been scheduled for you at Copenhagen Branch.', 1, '2026-05-03 22:09:17'),
(194, 41, 'Your request has been scheduled at Copenhagen Branch.', 0, '2026-05-03 22:09:17'),
(195, 40, 'Your blood donation has been marked as completed. You are temporarily unavailable until your next eligible date. Next Eligible Date: 2026-08-04.', 1, '2026-05-03 22:11:04'),
(196, 41, 'Your blood request has been fulfilled and marked as completed by the blood bank.', 0, '2026-05-03 22:11:04'),
(197, 42, 'Your blood request #48 has been approved for donor matching.', 0, '2026-05-03 22:54:37'),
(198, 18, 'A donor confirmed the scheduled stock donation appointment (#1).', 0, '2026-05-03 22:58:02'),
(199, 18, 'A donor has confirmed the scheduled donation appointment for request #25.', 0, '2026-05-03 22:58:19'),
(200, 25, 'Your blood donation for request #25 has been marked as completed. You are temporarily unavailable for donation. Next Eligible Date: 2026-08-04.', 0, '2026-05-03 22:59:21'),
(201, 14, 'Your blood request #25 has been fulfilled and marked as completed by the blood bank.', 0, '2026-05-03 22:59:21'),
(202, 18, 'Donor Donor 2 expressed interest in blood request #48.', 0, '2026-05-03 23:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(8, 'sadekasamia@gmail.com', 'c37959c167289a1ef72a5926deffd3a5a139e7134c72fa728214449c857e064b', '2026-05-01 16:09:17', '2026-05-01 13:39:17');

-- --------------------------------------------------------

--
-- Table structure for table `stock_donation_requests`
--

CREATE TABLE IF NOT EXISTS `stock_donation_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_id` int(11) NOT NULL,
  `blood_bank_user_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `preferred_date` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completion_note` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','scheduled','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_stock_donation_requests_donor` (`donor_id`),
  KEY `fk_stock_donation_requests_blood_bank` (`blood_bank_user_id`),
  KEY `fk_stock_donation_requests_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_donation_requests`
--

INSERT INTO `stock_donation_requests` (`id`, `donor_id`, `blood_bank_user_id`, `branch_id`, `preferred_date`, `scheduled_date`, `completed_at`, `completion_note`, `notes`, `status`, `created_at`) VALUES
(1, 25, 18, 2, '2026-04-18 16:20:00', '2026-04-19 17:00:00', NULL, NULL, 'I\'ll be on time.', 'confirmed', '2026-04-18 16:20:43'),
(3, 28, 18, 1, '2026-04-19 18:05:00', '2026-04-19 18:05:00', NULL, NULL, '', 'confirmed', '2026-04-18 21:01:35'),
(4, 20, 18, 1, '2026-04-20 13:00:00', '2026-04-20 13:00:00', NULL, NULL, 'I can donate 1 unit', 'scheduled', '2026-04-19 10:00:53'),
(5, 28, 18, 1, '2026-04-21 13:30:00', NULL, NULL, NULL, 'I want to donate blood.\n\n[Donor Reschedule Request]\nReason: I\'ll be at work on Monday.', 'pending', '2026-04-19 20:09:58'),
(6, 33, 18, 3, '2026-04-20 12:30:00', NULL, NULL, NULL, 'Glad to donate.\n\n[Blood Bank Schedule Note]\nCan You come on this time?\n\n[Donor Reschedule Request]\nReason: 12.30 would be okey.', 'pending', '2026-04-19 22:38:03'),
(7, 28, 18, 1, '2026-04-20 18:20:00', NULL, NULL, NULL, '', 'pending', '2026-04-19 23:21:57'),
(8, 25, 18, 2, '2026-04-20 17:02:00', NULL, NULL, NULL, '', 'pending', '2026-04-20 12:00:56'),
(9, 43, 18, 1, '2026-05-03 15:05:00', '2026-05-03 15:05:00', NULL, NULL, '[Blood Bank Schedule Note]', 'confirmed', '2026-05-03 18:00:19'),
(10, 28, 18, 1, '2026-05-03 12:10:00', '2026-05-03 12:10:00', '2026-05-03 23:40:01', 'Stock donation completed by Central Blood Bank Network on 2026-05-03 23:40:01.', '[Blood Bank Schedule Note]\nEat well', 'completed', '2026-05-03 20:11:18'),
(11, 39, 18, 2, '2026-05-06 10:10:00', NULL, NULL, NULL, '', 'pending', '2026-05-04 18:14:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','blood_bank','donor','recipient') NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `institution_name` varchar(150) DEFAULT NULL,
  `availability` enum('available','not_available') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_status` enum('active','inactive') DEFAULT 'active',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `next_eligible_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `contact_number`, `address`, `date_of_birth`, `weight`, `password`, `role`, `blood_group`, `weight_kg`, `location`, `address_line`, `city`, `zipcode`, `institution_name`, `availability`, `created_at`, `account_status`, `status`, `next_eligible_date`) VALUES
(4, 'Ayesha', 'ayesha@gmail.com', '55277757', NULL, NULL, NULL, '$2y$10$frEFE0f5tiXOguye4aECIOoRgEJLW2bmj3Tj9pw1cGBNwvTYRZ7.G', 'recipient', 'B+', NULL, 'Norrebro, copenhagen, 2300', 'Norrebro', 'copenhagen', '2300', NULL, 'available', '2026-02-13 20:15:19', 'active', 'active', NULL),
(10, 'Amir', 'amir@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$3UZ78BKL5BvaChY.VYn45.PPE/F0euxIwCuzEVfR0M7ueWdaBRy.2', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'available', '2026-02-14 17:46:55', 'active', 'active', NULL),
(14, 'Muna', 'muna@gmail.com', '55277297', NULL, NULL, NULL, '$2y$10$P1DlRMBTIaQuIf.IRposRusHodk.OC9rXpagZEnLb9av2pUtUHsXe', 'recipient', 'B+', NULL, 'glasvej, copenhagen, 2300', 'glasvej', 'copenhagen', '2300', NULL, 'available', '2026-03-12 10:49:59', 'active', 'active', NULL),
(17, 'Azrin', 'azrin@gmail.com', '55277297', NULL, NULL, NULL, '$2y$10$LsPGGy0WnP8Ej0S6R1XTcOEEgoGhZ/8leHcXSxcPOeF9bozAFIEL.', 'recipient', 'A-', NULL, 'Gammel Køge Landevej 642, Brøndby Strand, 2660', 'Gammel Køge Landevej 642', 'Brøndby Strand', '2660', NULL, 'available', '2026-03-16 10:37:45', 'active', 'active', NULL),
(18, 'Central Blood Bank Network', 'bloodbank@gmail.com', '01700000000', NULL, NULL, NULL, '$2y$10$Rf8xRFz7YOE7mvnGN/oy4uML.lbpYkYtJTVkYg/RXZqttG263/Yry', 'blood_bank', NULL, NULL, 'Amager', NULL, NULL, NULL, 'Central Blood Bank Network', 'available', '2026-03-21 23:35:42', 'active', 'active', NULL),
(19, 'samia', 'samia@gmail.com', '+4555277290', NULL, '2002-09-10', NULL, '$2y$10$mccoCUodI6GFXJ5xk7S6W.pmeHfuv1zuDRnrSP3guGFNxCuwKwXR.', 'donor', 'B+', 60.00, 'Amagar', NULL, NULL, NULL, NULL, 'not_available', '2026-03-22 21:52:50', 'inactive', 'active', NULL),
(20, 'Sadeka', 'sadeka@gmail.com', '55277297', NULL, '2000-09-01', NULL, '$2y$10$OPtJTxAKmdniBDanYnt9nej8hTXR5q9scInRrLRC18Ut9sVgFK6u2', 'donor', 'O+', 65.00, 'Norrebro', NULL, NULL, NULL, NULL, 'not_available', '2026-03-23 22:58:42', 'active', 'active', '2026-08-03'),
(21, 'Hamja', 'hamja@gmail.com', '+4555277257', NULL, '1997-11-04', NULL, '$2y$10$/XuQw/gP/tyfcrElLWPAIe6iREKOGXGFfWRjyuz8bWCn.VfKLXvUi', 'donor', 'O+', 80.00, 'Norrebro', NULL, NULL, NULL, NULL, 'not_available', '2026-03-24 00:24:39', 'active', 'active', NULL),
(22, 'ayub', 'ayub@gmail.com', '+45 5527790', NULL, NULL, NULL, '$2y$10$Hx66VbBu.gOl7xYPkrMJdeAAmxU.WCF5DhcqvbTechW/tE08b2Pf2', 'recipient', 'O+', NULL, 'Køge, Brøndby Strand, 2660', 'Køge', 'Brøndby Strand', '2660', NULL, 'available', '2026-03-24 14:14:36', 'active', 'active', NULL),
(23, 'Abir', 'abir@gmail.com', '+4590008909', NULL, NULL, NULL, '$2y$10$Ae57..briThHM2R312SpfuCbjQSWPmTIHXDKCGC5RPXeDEYPbiYtK', 'recipient', 'B-', NULL, 'Gammel Køge Landevej 642, Brøndby Strand, 2660', 'Gammel Køge Landevej 642', 'Brøndby Strand', '2660', NULL, 'available', '2026-03-28 17:42:15', 'active', 'active', NULL),
(25, 'jumon', 'jumon@gmail.com', '55277288', NULL, '1999-01-11', NULL, '$2y$10$gJGbHHM5trkJhfPzGnopNum8WIJ55WD0O4tPQeut34u.oFO2Jiopu', 'donor', 'B-', 50.00, 'Gammel Køge Landevej  501, Brøndby Strand, 2660', 'Gammel Køge Landevej  501', 'Brøndby Strand', '2660', NULL, 'not_available', '2026-04-12 17:27:51', 'active', 'active', '2026-08-04'),
(26, 'Sam', 'sam@gmail.com', '55277111', NULL, '1997-05-05', NULL, '$2y$10$myk3eKOnH/qu0OgG6K5Fpue0WLZvOGr7qSzb9IrcSJMyLpbzF5duy', 'donor', 'AB-', 68.00, 'Norrebro, Copenhagen, 2300', 'Norrebro', 'Copenhagen', '2300', NULL, 'not_available', '2026-04-12 17:58:28', 'active', 'active', NULL),
(28, 'Sadeka samia', 'sadekasamia@gmail.com', '55277297', NULL, '2002-09-10', NULL, '$2y$10$ONk5dsKUlhGsbbHmWe2m/Otf4/5X/v8ybDWHYNRB./cHiACxkEiLW', 'donor', 'A+', 60.00, 'Kastrupvej 6,, København S, 2300', 'Kastrupvej 6,', 'København S', '2300', NULL, 'not_available', '2026-04-17 08:14:30', 'active', 'active', '2026-08-03'),
(31, 'ahj', 'ahj@gmail.com', '55277297', NULL, '2008-01-17', NULL, '$2y$10$3vRqtU8LfQOLlnIdfdvm5uuezDL22nloSB.bSabxPrEmfZf1oXLR2', 'donor', 'O+', 60.00, 'Kastrup, København S, 2300', 'Kastrup', 'København S', '2300', NULL, 'available', '2026-04-17 10:05:24', 'active', 'active', NULL),
(32, 'Adnan', 'adnan@gmail.com', '55209890', NULL, NULL, NULL, '$2y$10$xtYodA4X04TJgG8SMUMncecm.6Wcl4ANzO.nDEHuYDp8buSnBW1ke', 'recipient', 'B+', NULL, 'Norrebro 54, copenhagen, 2300', 'Norrebro 54', 'copenhagen', '2300', NULL, 'available', '2026-04-19 18:16:47', 'active', 'active', NULL),
(33, 'sami', 'sami@gmail.com', '55277297', NULL, '2003-01-02', NULL, '$2y$10$gOoAkL2SGWPGrG6YsfVct.U71rD0CQUkn69r.9NXe6cw6KQT4DI/O', 'donor', 'B+', 68.00, 'Norrebrogade 108, København S, 2300', 'Norrebrogade 108', 'København S', '2300', NULL, 'available', '2026-04-19 18:23:43', 'active', 'active', NULL),
(34, 'Sanju', 'sanju@gmail.com', '55277297', NULL, NULL, NULL, '$2y$10$7Wa70TI1k.tOBBzRrYPjQ.sfDuVKOec511NCLdxXM/E6vkDRVZ9fC', 'recipient', 'A-', NULL, 'Gammel Køge Landevej, Brøndby Strand, 2660', 'Gammel Køge Landevej', 'Brøndby Strand', '2660', NULL, 'available', '2026-04-20 20:58:01', 'active', 'active', NULL),
(35, 'Sadeka jahan samia', 'a@gmail.com', '55277297', NULL, '2001-12-12', 55.00, '$2y$10$qtNVQyIhFc/Ro.Zdeo2SnuPA4hKGqiQiL.OrAlClYS1pdF0o2Vgxy', 'donor', 'A+', NULL, 'Kastrupvej 6, 2tv, København S, 2300', 'Kastrupvej 6, 2tv', 'København S', '2300', NULL, 'available', '2026-04-29 19:41:54', 'active', 'active', NULL),
(39, 'Donor 3', 'donor3@gmail.com', '12323454', NULL, '2003-11-23', NULL, '$2y$10$38zW82fP2qnxQ14pY8YQnu4gGatCrECsvINwW4M2drodBCxLgmHHe', 'donor', 'B+', 70.00, 'Gammel Køge, Brøndby Strand, 2660', 'Gammel Køge', 'Brøndby Strand', '2660', NULL, 'available', '2026-04-29 20:38:29', 'active', 'active', NULL),
(40, 'Donor 1', 'donor1@gmail.com', '+4534432312', NULL, '2001-09-23', NULL, '$2y$10$nzdq82OHBVlBbSoDFFXKOuE8kVVvLPgMngrpA2FjmIJ.AO/jrJ.qe', 'donor', 'A+', 58.00, 'Norrebrohallen, København, 2100', 'Norrebrohallen', 'København', '2100', NULL, 'not_available', '2026-04-29 20:43:13', 'active', 'active', '2026-08-04'),
(41, 'Recipient 1', 'recipient1@gmail.com', '', NULL, NULL, NULL, '$2y$10$s8d3Fr7rZdEBnLZbzc7FqOGYWjvipZz3srISaEbqbQDT6nBJA8xmi', 'recipient', 'A+', NULL, 'Lundtoftegade, copenhagen, 2100', 'Lundtoftegade', 'copenhagen', '2100', NULL, 'available', '2026-04-29 20:46:39', 'inactive', 'inactive', NULL),
(42, 'Recipient 2', 'recipient2@gmail.com', '67890998', NULL, NULL, NULL, '$2y$10$UldAMsMrnAKRbeRs3I7r4ux0b0m8X5KUpjbroMorr.lpNdl/6Tb6S', 'recipient', 'A-', NULL, 'Rødovre Centrum, Rødovre, 2610', 'Rødovre Centrum', 'Rødovre', '2610', NULL, 'available', '2026-04-30 21:43:56', 'active', 'active', NULL),
(43, 'Donor 2', 'donor2@gmail.com', '+4523657998', NULL, '1998-03-30', NULL, '$2y$10$4DN7DCKIhJaCTULvQD2NYu44KUvt6hD5/nauHoDECE0fNQrGAiXjq', 'donor', 'A-', 69.90, 'Rødovre 32, Rødovre, 2610', 'Rødovre 32', 'Rødovre', '2610', NULL, 'available', '2026-04-30 21:50:09', 'active', 'active', NULL),
(44, 'Recipient 3', 'recipient3@gmail.com', '+4512213443', NULL, NULL, NULL, '$2y$10$y.sxsFrATCTjmigIxZzHW.I5uxM1SoHa2O7iXW9ZzXdpb2MkoEB5i', 'recipient', 'B+', NULL, 'Køge, Brøndby Strand, 2660', 'Køge', 'Brøndby Strand', '2660', NULL, 'available', '2026-04-30 21:56:01', 'active', 'active', NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_blood_bank` FOREIGN KEY (`blood_bank_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_appointments_donor` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appointments_request` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blood_requests`
--
ALTER TABLE `blood_requests`
  ADD CONSTRAINT `blood_requests_ibfk_1` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_blood_requests_fulfilled_branch` FOREIGN KEY (`fulfilled_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blood_stock`
--
ALTER TABLE `blood_stock`
  ADD CONSTRAINT `fk_blood_stock_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `fk_branches_blood_bank_user` FOREIGN KEY (`blood_bank_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_donations_blood_bank` FOREIGN KEY (`blood_bank_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_donations_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donor_interests`
--
ALTER TABLE `donor_interests`
  ADD CONSTRAINT `donor_interests_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `blood_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donor_interests_ibfk_2` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_donation_requests`
--
ALTER TABLE `stock_donation_requests`
  ADD CONSTRAINT `fk_stock_donation_requests_blood_bank` FOREIGN KEY (`blood_bank_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stock_donation_requests_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_stock_donation_requests_donor` FOREIGN KEY (`donor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
