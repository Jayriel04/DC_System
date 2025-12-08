-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 08:05 AM
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
-- Database: `dbclinic`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

CREATE TABLE `tbladmin` (
  `ID` int(10) NOT NULL,
  `AdminName` varchar(120) DEFAULT NULL,
  `UserName` varchar(120) DEFAULT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `image` varchar(250) DEFAULT NULL,
  `AdminRegdate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`ID`, `AdminName`, `UserName`, `MobileNumber`, `Email`, `Password`, `image`, `AdminRegdate`) VALUES
(1, 'Admin', 'admin', 9564780461, 'canoniokevin@gmail.com', '$2y$10$fdyJgvrdq3O0y9YM0OhDV.FGnhh7iZYCvCESl0YUEOZ/wzkIMpz3O', 'doctor.png', '2025-06-01 04:36:52');

-- --------------------------------------------------------

--
-- Table structure for table `tblappointment`
--

CREATE TABLE `tblappointment` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `patient_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblappointment`
--

INSERT INTO `tblappointment` (`id`, `firstname`, `surname`, `date`, `start_time`, `end_time`, `patient_number`, `created_at`, `status`, `cancel_reason`, `cancelled_at`) VALUES
(1, 'John Mar', 'Ypil', '2025-09-26', '17:47:00', NULL, 4, '2025-09-26 09:54:12', 'Declined', NULL, NULL),
(2, 'Jezrah Faith', 'Canonio', '2025-09-27', '14:36:00', NULL, 5, '2025-09-27 06:36:43', 'Cancelled', 'ahhahaha', '2025-11-29 17:53:10'),
(3, 'Rodelyn', 'Estrera', '2025-09-27', '16:42:00', NULL, 8, '2025-09-27 08:42:44', 'Approved', NULL, NULL),
(4, 'John Mar', 'Ypil', '2025-09-27', '18:14:00', '19:00:00', 4, '2025-09-27 10:33:00', 'Approved', NULL, NULL),
(5, 'John Mar', 'Ypil', '2025-10-05', '13:47:00', '14:48:00', 4, '2025-10-05 05:49:13', 'Approved', NULL, NULL),
(6, 'John Mar', 'Ypil', '2025-10-05', '15:14:00', '16:14:00', 4, '2025-10-05 06:15:00', 'Approved', NULL, NULL),
(7, 'Nimfa', 'Conde', '2025-10-05', '17:39:00', '18:40:00', 10, '2025-10-05 09:40:49', 'walk-in', NULL, NULL),
(8, 'Sig', 'Canonio', '2025-10-05', '17:29:00', NULL, 13, '2025-10-20 10:36:59', 'Cancelled', 'sample cancel!', NULL),
(9, 'Jaynard', 'Senilla', '2025-11-29', '12:08:00', NULL, 14, '2025-11-29 04:41:14', 'Approved', NULL, NULL),
(17, 'Jezrah Faith', 'Canonio', '2025-11-29', '15:11:00', '16:11:00', 5, '2025-11-29 10:07:41', 'Cancelled', 'emergency', '2025-12-06 11:48:09'),
(18, 'Jezrah Faith', 'Canonio', '2025-11-29', '18:38:00', '19:39:00', 5, '2025-11-29 10:39:21', 'Cancelled', 'emergecny', '2025-12-06 11:59:01'),
(19, 'Pio', 'Canonio', '2025-12-06', '13:00:00', '13:30:00', 15, '2025-12-06 03:16:54', 'Cancelled', 'emergency', '2025-12-06 18:15:38'),
(20, 'Jezrah Faith', 'Canonio', '2025-12-07', '10:00:00', '10:30:00', 5, '2025-12-06 04:00:24', 'Cancelled', 'emergwncy', '2025-12-06 12:01:27'),
(21, 'Pio', 'Canonio', '2025-12-08', '10:00:00', '10:30:00', 15, '2025-12-06 10:19:47', 'Cancelled', 'emergency', '2025-12-06 18:20:55');

-- --------------------------------------------------------

--
-- Table structure for table `tblcalendar`
--

CREATE TABLE `tblcalendar` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcalendar`
--

INSERT INTO `tblcalendar` (`id`, `date`, `start_time`, `end_time`) VALUES
(1, '2025-09-19', '17:00:00', '17:05:00'),
(2, '2025-09-24', '20:49:00', '17:49:00'),
(3, '2025-09-24', '20:49:00', '17:49:00'),
(4, '2025-09-26', '21:14:00', '21:45:00'),
(5, '2025-09-27', '18:14:00', '19:00:00'),
(6, '2025-09-27', '20:00:00', '21:00:00'),
(7, '2025-10-05', '13:47:00', '14:48:00'),
(8, '2025-10-05', '15:14:00', '16:14:00'),
(9, '2025-10-05', '17:29:00', '18:30:00'),
(10, '2025-10-05', '18:55:00', '19:55:00'),
(11, '2025-11-27', '22:06:00', '23:06:00'),
(12, '2025-11-29', '12:08:00', '13:08:00'),
(13, '2025-11-29', '15:11:00', '16:11:00'),
(14, '2025-11-29', '18:38:00', '19:39:00'),
(15, '2025-12-06', '13:00:00', '13:30:00'),
(16, '2025-12-07', '10:00:00', '10:30:00'),
(17, '2025-12-08', '10:00:00', '10:30:00'),
(18, '2025-12-08', '14:23:00', '15:23:00'),
(19, '2025-12-11', '10:00:00', '10:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `tblcategory`
--

CREATE TABLE `tblcategory` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcategory`
--

INSERT INTO `tblcategory` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Preventive Dentistry', 'Preventive dentistry focuses on maintaining optimal oral health and preventing dental issues before they arise.', 'images/categories/preventive-dentistry.png', '2025-11-27 11:46:10'),
(2, 'Restorative Dentistry', 'Restorative dentistry focuses on repairing or replacing damaged or missing teeth to improve oral health, function, and appearance.', 'images/categories/restorative-dentistry.png', '2025-11-27 11:46:10'),
(3, 'All Porcelain Crowns/Veneers', 'All-porcelain crowns and veneers are dental restorations that improve a tooth\'s appearance and/or function by covering it with a thin layer of porcelain.', 'images/categories/all-porcelain-crowns.png', '2025-11-27 11:46:10'),
(4, 'Porcelain-Fused To Metal Crowns', 'These crowns combine strength and aesthetics, with a metal base covered by a layer of porcelain to match natural teeth.', 'images/categories/porcelain-fused-crowns.png', '2025-11-27 11:46:10'),
(5, 'Full-Metal Crowns', 'Made entirely of metal, these crowns are highly durable and are used primarily for back teeth due to their strength.', 'images/categories/full-metal-crowns.png', '2025-11-27 11:46:10'),
(6, 'Plastic Crowns', 'Often used as temporary solutions, these crowns are made from acrylic and are less durable than other types.', 'images/categories/plastic-crowns.png', '2025-11-27 11:46:10'),
(7, 'Complete Dentures', 'Full dentures replace all missing teeth in the upper and/or lower jaw, providing improved function and appearance.', 'images/categories/complete-dentures.png', '2025-11-27 11:46:10'),
(8, 'Removable Partial Dentures', 'These dentures replace some missing teeth and can be taken out for cleaning and maintenance.', 'images/categories/removable-partial-dentures.png', '2025-11-27 11:46:10'),
(9, 'Provisional Dentures', 'Temporary dentures used while waiting for permanent solutions, they provide functionality and aesthetics during the transition.', 'images/categories/provisional-dentures.png', '2025-11-27 11:46:10'),
(10, 'Esthetic/Cosmetic Dentistry', 'Focused on improving the appearance of teeth, gums, and smiles through various procedures like whitening and veneers.', 'images/categories/cosmetic-dentistry.png', '2025-11-27 11:46:10'),
(11, 'Orthodontics', 'This specialty involves correcting misaligned teeth and jaws using braces, aligners, and other devices for improved function and appearance.', 'images/categories/orthodontics.png', '2025-11-27 11:46:10'),
(12, 'Oral Surgery', 'Surgical procedures in the mouth, including tooth extractions, jaw realignment, and treatment of oral diseases.', 'images/categories/oral-surgery.png', '2025-11-27 11:46:10'),
(13, 'Root Canal Treatment', 'A procedure to remove infected pulp from inside a tooth, relieving pain and saving the tooth from extraction.', 'images/categories/root-canal-treatment.png', '2025-11-27 11:46:10'),
(14, 'Pediatric Dentistry', 'Specialized dental care for children, focusing on their unique dental needs, preventive care, and education.', 'images/categories/pediatric-dentistry.png', '2025-11-27 11:46:10');

-- --------------------------------------------------------

--
-- Table structure for table `tblinventory`
--

CREATE TABLE `tblinventory` (
  `number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblinventory`
--

INSERT INTO `tblinventory` (`number`, `name`, `brand`, `expiration_date`, `quantity`, `category`, `status`) VALUES
(1, 'Etchant', 'FGM', '2025-09-19', 1, 'Medicine', 'Available'),
(2, 'Fsd', 'Dsd', '2026-01-24', 1, 'Supply', 'Available'),
(3, 'Isoprophyl Alcohol', 'Doctor J', '2028-06-25', 3, 'Supply', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `tblnotif`
--

CREATE TABLE `tblnotif` (
  `id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL COMMENT 'ID of the admin or patient',
  `recipient_type` enum('admin','patient') NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unread, 1=read',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblnotif`
--

INSERT INTO `tblnotif` (`id`, `recipient_id`, `recipient_type`, `message`, `url`, `is_read`, `created_at`) VALUES
(1, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-11-29 10:37:58'),
(2, 1, 'admin', 'New consultation request from Jezrah Faith Canonio.', 'mac.php?filter=pending', 1, '2025-11-29 10:39:21'),
(3, 1, 'admin', 'Jezrah Faith Canonio requested to cancel a service.', 'mas.php?filter=for_cancellation', 1, '2025-11-29 10:39:56'),
(4, 1, 'admin', 'New feedback received from Jezrah Faith Canonio.', 'manage-reviews.php', 1, '2025-11-29 10:45:12'),
(5, 5, 'patient', 'Your consultation on November 29, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 02:59:45'),
(6, 5, 'patient', 'Your consultation on November 29, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:00:10'),
(7, 5, 'patient', 'Your consultation on November 29, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:00:39'),
(8, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 03:04:27'),
(9, 5, 'patient', 'Your consultation on November 29, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:05:16'),
(10, 5, 'patient', 'Your consultation on November 29, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:05:21'),
(11, 5, 'patient', 'Your consultation on November 29, 2025 has been declined.', 'profile.php?tab=appointments', 1, '2025-12-06 03:05:53'),
(12, 5, 'patient', 'Your appointment for Etchant on September 27, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 1, '2025-12-06 03:11:15'),
(13, 5, 'patient', 'Your appointment for Etchant on September 27, 2025 has been marked as done.', 'profile.php?tab=appointments', 1, '2025-12-06 03:13:01'),
(14, 1, 'admin', 'New feedback received from Pio Canonio.', 'manage-reviews.php', 1, '2025-12-06 03:14:23'),
(15, 1, 'admin', 'New consultation request from Pio Canonio.', 'mac.php?filter=pending', 1, '2025-12-06 03:16:54'),
(16, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:22:37'),
(17, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:22:40'),
(18, 15, 'patient', 'Your consultation on December 6, 2025 has been declined.', 'profile.php?tab=appointments', 1, '2025-12-06 03:24:30'),
(19, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 03:25:07'),
(20, 5, 'patient', 'Your appointment for Etchant on September 27, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 1, '2025-12-06 03:26:21'),
(21, 1, 'admin', 'A service for Jezrah Faith Canonio was cancelled by an admin.', 'mas.php?filter=cancelled', 1, '2025-12-06 03:27:48'),
(22, 5, 'patient', 'Your appointment for Etchant on September 27, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 1, '2025-12-06 03:31:47'),
(23, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 03:40:47'),
(24, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 03:42:53'),
(25, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 03:48:09'),
(26, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 03:52:27'),
(27, 5, 'patient', 'Your consultation on November 29, 2025 has been declined.', 'profile.php?tab=appointments', 1, '2025-12-06 03:54:22'),
(28, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 03:59:01'),
(29, 1, 'admin', 'New consultation request from Jezrah Faith Canonio.', 'mac.php?filter=pending', 0, '2025-12-06 04:00:24'),
(30, 1, 'admin', 'Jezrah Faith Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 04:01:27'),
(31, 1, 'admin', 'Jezrah Faith Canonio requested to cancel a service.', 'mas.php?filter=for_cancellation', 0, '2025-12-06 04:02:33'),
(32, 5, 'patient', 'Your appointment for Etchant on September 27, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 1, '2025-12-06 04:03:38'),
(33, 15, 'patient', 'Your consultation on December 6, 2025 has been declined.', 'profile.php?tab=appointments', 1, '2025-12-06 09:09:54'),
(34, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 09:40:30'),
(35, 14, 'patient', 'Your appointment for Ibot ngipon on November 29, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 0, '2025-12-06 09:43:32'),
(36, 5, 'patient', 'Your appointment for Etchant on September 27, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 0, '2025-12-06 09:44:20'),
(37, 1, 'admin', 'A service for Jezrah Faith Canonio was cancelled by an admin.', 'mas.php?filter=cancelled', 0, '2025-12-06 09:45:26'),
(38, 1, 'admin', 'A service for Jezrah Faith Canonio was cancelled by an admin.', 'mas.php?filter=cancelled', 0, '2025-12-06 09:47:26'),
(39, 1, 'admin', 'Pio Canonio requested to cancel a service.', 'mas.php?filter=for_cancellation', 0, '2025-12-06 09:50:56'),
(40, 1, 'admin', 'Pio Canonio requested to cancel a service.', 'mas.php?filter=for_cancellation', 0, '2025-12-06 09:54:58'),
(41, 1, 'admin', 'Pio Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 09:55:35'),
(42, 15, 'patient', 'Your appointment for Etchant on December 11, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 1, '2025-12-06 09:56:33'),
(43, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 10:01:48'),
(44, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 10:01:53'),
(45, 1, 'admin', 'Pio Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 10:02:35'),
(46, 1, 'admin', 'Pio Canonio requested to cancel a service.', 'mas.php?filter=for_cancellation', 0, '2025-12-06 10:03:25'),
(47, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-06 10:15:26'),
(48, 1, 'admin', 'Pio Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 1, '2025-12-06 10:15:38'),
(49, 15, 'patient', 'Your appointment for Etchant on December 11, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 1, '2025-12-06 10:16:16'),
(50, 1, 'admin', 'New consultation request from Pio Canonio.', 'mac.php?filter=pending', 0, '2025-12-06 10:19:47'),
(51, 1, 'admin', 'Pio Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 0, '2025-12-06 10:20:55'),
(52, 1, 'admin', 'Item \'Etchant\' is running low on stock (1).', 'manage-inventory.php', 1, '2025-12-07 03:57:51'),
(53, 1, 'admin', 'Item \'fsd\' is out of stock.', 'manage-inventory.php', 1, '2025-12-07 03:57:51'),
(54, 1, 'admin', 'Item \'Etchant\' is running low on stock (1).', 'manage-inventory.php', 0, '2025-12-07 03:59:48'),
(55, 1, 'admin', 'Item \'Fsd\' is running low on stock (1).', 'manage-inventory.php', 0, '2025-12-07 03:59:48');

-- --------------------------------------------------------

--
-- Table structure for table `tblpatient`
--

CREATE TABLE `tblpatient` (
  `number` int(11) NOT NULL,
  `firstname` varchar(50) DEFAULT NULL,
  `surname` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `occupation` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `contact_number` varchar(15) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `health_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblpatient`
--

INSERT INTO `tblpatient` (`number`, `firstname`, `surname`, `date_of_birth`, `sex`, `status`, `occupation`, `age`, `contact_number`, `address`, `email`, `Image`, `health_conditions`, `created_at`, `username`, `password`, `rating`, `feedback`) VALUES
(4, 'John Mar', 'Ypil', '2002-12-21', 'Male', 'Married', 'Student', 22, '09374939832', 'idk', 'ypil.johnmar.mcc@gmail.com', 'anonymous-girl.png', '{\"general\":[\"Increase frequency of urination\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Increase intake of food or water\"],\"thyroid\":[\"Apprehension\"],\"nervous\":[\"Headache\"],\"blood\":[\"Bruise easily\"],\"respiratory\":[\"Persistent cough\"],\"urinary\":[]}', '2025-09-27 11:02:35', 'jm', '763c3f1b6fe4707b8c39df149788c70b', 5, 'lami kayu'),
(5, 'Jezrah Faith', 'Canonio', '2004-05-13', 'Female', 'Single', 'Princess', 21, '09876543211', 'Yati liloan', 'canonio.jezrahfaith.mcc@gmail.com', '1.jpg', '{\"general\":[\"Increase frequency of urination\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Family history of diabetes\"],\"thyroid\":[\"Apprehension\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Numbness\\/Tingling\"],\"blood\":[\"Anemia\"],\"respiratory\":[\"Persistent cough\"]}', '2025-09-27 11:03:26', 'jezrah', '3b02af71589ec5ea4138a1ece1866008', 4, 'nice service my nigga'),
(8, 'Rodelyn', 'Estrera', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'estrera.rodelyn.mcc@gmail.com', NULL, '{\"general\":[\"Marked weight change\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Delayed healing of wounds\"],\"thyroid\":[\"Perspire easily\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Headache\"],\"blood\":[\"Bruise easily\"],\"respiratory\":[\"Difficulty in breathing\"]}', '2025-09-27 11:04:04', 'rodelyn', 'bc65be184bd685684a786ac70c6d2ef7', NULL, NULL),
(9, 'Justine', 'Aguinaldo', '0000-00-00', '', '', '', 0, '', '', 'aguinaldo.justinelouise.mcc@gmail.com', NULL, NULL, '2025-09-27 11:04:46', 'justine', 'f6f3e757ac491a3511a5198a39c5ce29', NULL, NULL),
(10, 'Nimfa', 'Conde', '1956-03-09', 'Female', 'Single', 'Mother', 69, '09876543211', 'idk', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Seg', 'Canonio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'seg@gmail.com', NULL, NULL, NULL, 'seg', '54151f5b2b56a1345561afde6059ac63', NULL, NULL),
(12, 'Crazy', 'Rapidboots', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crazy@test.com', NULL, NULL, NULL, 'crazy', 'fa2ee41779ef60891cbfdfcd0dccaa7c', NULL, NULL),
(13, 'Sig', 'Canonio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sig@test.com', NULL, '{\"general\":[\"Increase frequency of urination\",\"Burning sensation on urination\"],\"liver\":[\"History of liver ailment\"],\"diabetes\":[\"Delayed healing of wounds\"],\"thyroid\":[\"Apprehension\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Headache\",\"Dizziness\\/Fainting\"],\"blood\":[\"Bruise easily\"],\"respiratory\":[\"Persistent cough\"]}', NULL, 'Sig', '252e0253e865dec297431b97709794d5', NULL, NULL),
(14, 'Jaynard', 'Senilla', '0000-00-00', 'Male', '', '', 0, '', '', 'saging@gmail.com', NULL, NULL, '2025-11-29 04:07:01', 'jaynard', 'fa2c395447a8ad82849d3a7129830102', NULL, NULL),
(15, 'Pio', 'Canonio', '0000-00-00', '', '', '', 0, '', '', 'canoniopio@gmail.com', NULL, NULL, '2025-12-06 03:09:32', 'pio', '$2y$10$6R9t1Nyj1aN6IimOTh5jfudMGS985UGAOg9tjrMAolKjstVhemBXu', 4, 'amazing!');

-- --------------------------------------------------------

--
-- Table structure for table `tblschedule`
--

CREATE TABLE `tblschedule` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `patient_number` int(11) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblschedule`
--

INSERT INTO `tblschedule` (`id`, `appointment_id`, `patient_number`, `firstname`, `surname`, `date`, `time`, `service_id`, `created_at`, `duration`, `status`, `cancel_reason`, `cancelled_at`) VALUES
(1, 1, 4, 'John Mar', 'Ypil', '2025-09-26', '21:32:00', 3, '2025-09-26 05:32:21', 30, 'Ongoing', 'idk', '2025-10-05 19:05:16'),
(2, 2, 5, 'Jezrah Faith', 'Canonio', '2025-09-27', '14:39:00', 1, '2025-09-26 22:39:38', 45, 'Cancelled', 'emergency', '2025-12-06 12:02:33'),
(3, 3, 8, 'Rodelyn', 'Estrera', '2025-09-27', '18:44:00', 4, '2025-09-27 02:44:52', 67, 'Ongoing', NULL, NULL),
(4, 4, 4, 'John Mar', 'Ypil', '2025-10-05', '16:29:00', 5, '2025-10-05 08:29:34', 50, 'Ongoing', NULL, NULL),
(5, 5, 4, 'John Mar', 'Ypil', '2025-10-05', '16:39:00', 6, '2025-10-05 08:39:17', 34, 'Ongoing', NULL, NULL),
(6, 6, 4, 'John Mar', 'Ypil', '2025-10-05', '16:39:00', 2, '2025-10-05 08:39:42', 56, 'Cancelled', 'cancel', NULL),
(7, NULL, 14, 'Jaynard', 'Senilla', '2025-11-29', '17:46:00', 2, '2025-11-29 08:46:54', 45, 'Ongoing', NULL, NULL),
(8, NULL, 15, 'Pio', 'Canonio', '2025-12-11', '07:30:00', 1, '2025-12-06 09:49:40', 160, 'Cancelled', 'emergency', '2025-12-06 18:03:25');

-- --------------------------------------------------------

--
-- Table structure for table `tblservice`
--

CREATE TABLE `tblservice` (
  `number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblservice`
--

INSERT INTO `tblservice` (`number`, `name`, `description`, `category_id`, `image`) VALUES
(1, 'Etchant', 'Agoyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 1, 'images/588880344_1162759702712818_3890524668527245534_n.jpg'),
(2, 'Ibot ngipon', 'Pa ibot ka ngipon gaw', 2, 'images/services/sstudy laod.jpg'),
(3, 'Sample 3', 'Idk aahahahhansklwkldnqkw', 3, 'images/services/image.png'),
(4, 'Sample 4', 'Sample for number 5', 4, 'images/services/logo.png'),
(5, 'Sample 5', 'Sample number 5', 5, 'images/services/mcc_logo2.jpg'),
(6, 'Sample 6', 'Sample for number 6', 6, 'images/services/mcc.jpg'),
(7, 'Sample 7', 'This is for sample service', 7, 'images/services/Mandaue City College Student Profiling.png'),
(8, 'Sample service', 'Sample category for a service', 8, 'images/mcc new logo.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `tblappointment`
--
ALTER TABLE `tblappointment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcalendar`
--
ALTER TABLE `tblcalendar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblcategory`
--
ALTER TABLE `tblcategory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblinventory`
--
ALTER TABLE `tblinventory`
  ADD PRIMARY KEY (`number`);

--
-- Indexes for table `tblnotif`
--
ALTER TABLE `tblnotif`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient` (`recipient_id`,`recipient_type`,`is_read`);

--
-- Indexes for table `tblpatient`
--
ALTER TABLE `tblpatient`
  ADD PRIMARY KEY (`number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tblschedule`
--
ALTER TABLE `tblschedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tblservice`
--
ALTER TABLE `tblservice`
  ADD PRIMARY KEY (`number`),
  ADD KEY `fk_service_category` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbladmin`
--
ALTER TABLE `tbladmin`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblappointment`
--
ALTER TABLE `tblappointment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tblcalendar`
--
ALTER TABLE `tblcalendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `tblcategory`
--
ALTER TABLE `tblcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tblinventory`
--
ALTER TABLE `tblinventory`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblnotif`
--
ALTER TABLE `tblnotif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `tblpatient`
--
ALTER TABLE `tblpatient`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1879;

--
-- AUTO_INCREMENT for table `tblservice`
--
ALTER TABLE `tblservice`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblservice`
--
ALTER TABLE `tblservice`
  ADD CONSTRAINT `fk_service_category` FOREIGN KEY (`category_id`) REFERENCES `tblcategory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
