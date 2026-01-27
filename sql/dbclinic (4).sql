-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 27, 2026 at 09:43 AM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

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
  `AdminRegdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`ID`, `AdminName`, `UserName`, `MobileNumber`, `Email`, `Password`, `image`, `AdminRegdate`) VALUES
(1, 'Admin', 'admin', 9564780461, 'canoniokevin@gmail.com', '$2y$10$5kjYgb7R.pBKBAV9V1djd.YgcE57ro9MiSptIMrWRAx1QUxit4OCy', '1765551681_doctor.png', '2025-06-01 04:36:52');

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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT NULL,
  `cancel_reason` text,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tblappointment`
--

INSERT INTO `tblappointment` (`id`, `firstname`, `surname`, `date`, `start_time`, `end_time`, `patient_number`, `created_at`, `status`, `cancel_reason`, `cancelled_at`) VALUES
(1, 'Jezrah Faith', 'Canonio', '2025-12-14', '10:00:00', '10:30:00', 1, '2025-12-13 14:42:37', 'Pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblcalendar`
--

CREATE TABLE `tblcalendar` (
  `id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
(20, '2025-12-14', '10:00:00', '10:30:00'),
(21, '2025-12-14', '11:00:00', '11:30:00'),
(22, '2025-12-15', '13:00:00', '13:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `tblcategory`
--

CREATE TABLE `tblcategory` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  `is_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=unread, 1=read',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
(55, 1, 'admin', 'Item \'Fsd\' is running low on stock (1).', 'manage-inventory.php', 0, '2025-12-07 03:59:48'),
(56, 15, 'patient', 'Your consultation on December 8, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-08 08:33:55'),
(57, 5, 'patient', 'Your consultation on December 7, 2025 has been approved.', 'profile.php?tab=appointments', 0, '2025-12-12 17:13:00'),
(58, 15, 'patient', 'Your appointment for Etchant on December 11, 2025 has been marked as done.', 'profile.php?tab=appointments', 1, '2025-12-13 01:39:49'),
(59, 15, 'patient', 'Your consultation on December 6, 2025 has been approved.', 'profile.php?tab=appointments', 1, '2025-12-13 13:11:16'),
(60, 15, 'patient', 'Your appointment for your service on December 11, 2025 has been marked as cancelled.', 'profile.php?tab=appointments', 0, '2025-12-13 13:36:07'),
(61, 1, 'admin', 'Pio Canonio cancelled a consultation.', 'mac.php?filter=cancelled', 1, '2025-12-13 13:43:40'),
(62, 1, 'admin', 'New consultation request from Jezrah Faith Canonio.', 'mac.php?filter=pending', 0, '2025-12-13 14:42:37');

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
  `health_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `feedback` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tblpatient`
--

INSERT INTO `tblpatient` (`number`, `firstname`, `surname`, `date_of_birth`, `sex`, `status`, `occupation`, `age`, `contact_number`, `address`, `email`, `Image`, `health_conditions`, `created_at`, `username`, `password`, `rating`, `feedback`) VALUES
(1, 'Jezrah Faith', 'Canonio', '2004-05-13', 'Female', 'Single', 'Student', 21, '09564780461', 'Purok Kabulihan, Yati, Liloan, Cebu City', 'canonio.jezrahfaith.mcc@gmail.com', NULL, '{\"general\":[\"Marked weight change\"],\"ear\":[\"Loss of hearing, ringing of ears\"],\"nervous\":[\"Headache\",\"Numbness\\/Tingling\"],\"blood\":[\"Bruise easily\"],\"respiratory\":[\"Persistent cough\"],\"heart\":[\"Chest pain\\/discomfort\"],\"rheumatic_age\":\"\",\"stroke_when\":\"\",\"urinary\":[\"Burning sensation on urination\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Increase intake of food or water\"],\"thyroid\":[\"Apprehension\",\"Goiter\"],\"arthritis\":[\"Joint pain\"],\"radiograph\":[\"Undergo radiation therapy\"],\"pregnancy_months\":\"\",\"women\":[\"Breast feed\"],\"hospitalization_date\":\"\",\"hospitalization_specify\":\"\",\"allergy_specify\":\"\",\"extraction_date\":\"\",\"extraction_specify\":\"\",\"extraction_reaction_specify\":\"\"}', '2025-12-13 14:41:47', 'jezrah', '$2y$10$uqQExwI5KpPYcpYcP9TnkOd7/p6Yud32BPoso9o892YKdEPlhCDWu', NULL, NULL),
(3, 'John Mar', 'Ypil', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ypil.johnmar.mcc@gmail.com', NULL, NULL, '2026-01-27 08:55:23', 'jm', '$pa01$2y$10$33mLfuaIcy0Dx40bc1kr0e/JhXtxTUP/X8b82ZqLiip9AB37NTfxK', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblschedule`
--

CREATE TABLE `tblschedule` (
  `id` int(11) NOT NULL,
  `patient_number` int(11) DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `duration` int(11) DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tblschedule`
--

INSERT INTO `tblschedule` (`id`, `patient_number`, `firstname`, `surname`, `date`, `time`, `service_id`, `created_at`, `duration`, `status`, `cancel_reason`, `cancelled_at`) VALUES
(1, 1, 'Jezrah Faith', 'Canonio', '2025-12-19', '10:00:00', 1, '2025-12-13 15:15:30', 120, 'Ongoing', NULL, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tblservice`
--

INSERT INTO `tblservice` (`number`, `name`, `description`, `category_id`, `image`) VALUES
(1, 'Child Oral Prophylaxis', 'A professional cleaning procedure for children that removes plaque and minor stains to maintain good oral hygiene.', 1, 'images/child-oral.jpg'),
(2, 'Pit and Fissure Sealants (per tooth)', 'A thin protective coating applied to the chewing surfaces of a tooth to prevent cavities.', 1, 'images/pit-fissure.jpg'),
(3, 'Composite Filling', 'A tooth-colored material used to fill cavities or repair minor tooth damage, designed to blend naturally with the surrounding teeth.', 2, 'images/composite.jpg'),
(4, 'Porcelain Veneers (per tooth)', 'Thin porcelain shells bonded to the front surface of a tooth to enhance its color, shape, or alignment.', 3, 'images/pocelain.jpg'),
(5, 'Full Metal Crown (non-precious metal)', 'A crown made from non-precious metal alloys (such as nickel or chromium), offering strength at a lower cost.', 5, 'images/full metal non.png');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
  `verified` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `resettable` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `roles_mask` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `registered` int(10) UNSIGNED NOT NULL,
  `last_login` int(10) UNSIGNED DEFAULT NULL,
  `force_logout` mediumint(8) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `username`, `status`, `verified`, `resettable`, `roles_mask`, `registered`, `last_login`, `force_logout`) VALUES
(1, 'canonio.jezrahfaith.mcc@gmail.com', '$2y$10$uqQExwI5KpPYcpYcP9TnkOd7/p6Yud32BPoso9o892YKdEPlhCDWu', 'jezrah', 0, 1, 1, 0, 1765636907, 1769504036, 0),
(3, 'ypil.johnmar.mcc@gmail.com', '$pa01$2y$10$33mLfuaIcy0Dx40bc1kr0e/JhXtxTUP/X8b82ZqLiip9AB37NTfxK', 'jm', 0, 1, 1, 0, 1769504123, 1769504146, 0),
(4, 'canoniokevin@gmail.com', '$2y$10$5kjYgb7R.pBKBAV9V1djd.YgcE57ro9MiSptIMrWRAx1QUxit4OCy', 'admin', 0, 1, 1, 0, 1769504572, 1769504591, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users_2fa`
--

CREATE TABLE `users_2fa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mechanism` tinyint(3) UNSIGNED NOT NULL,
  `seed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  `expires_at` int(10) UNSIGNED DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_audit_log`
--

CREATE TABLE `users_audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event_at` int(10) UNSIGNED NOT NULL,
  `event_type` varchar(128) CHARACTER SET ascii NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(49) CHARACTER SET ascii DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `details_json` text COLLATE utf8mb4_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_audit_log`
--

INSERT INTO `users_audit_log` (`id`, `user_id`, `event_at`, `event_type`, `admin_id`, `ip_address`, `user_agent`, `details_json`) VALUES
(1, 1, 1769503446, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"c***c@g***l.com\",\"username\":null}'),
(2, 1, 1769503531, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"c***c@g***l.com\",\"username\":null}'),
(3, 1, 1769504014, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"c***c@g***l.com\",\"username\":null}'),
(4, 1, 1769504036, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"c***c@g***l.com\",\"username\":null}'),
(5, 3, 1769504123, 'register', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"y***c@g***l.com\",\"username\":\"jm\"}'),
(6, 3, 1769504123, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"y***c@g***l.com\",\"username\":null}'),
(7, 3, 1769504146, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"y***c@g***l.com\",\"username\":null}'),
(8, 4, 1769504591, 'login', NULL, '::/48', 'YtGJhHIu0Fd4G85DQqAJJx/Qv0eZmBoEjKNI8JrwNYQ=', '{\"email\":\"c***n@g***l.com\",\"username\":null}');

-- --------------------------------------------------------

--
-- Table structure for table `users_confirmations`
--

CREATE TABLE `users_confirmations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selector` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_remembered`
--

CREATE TABLE `users_remembered` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_resets`
--

CREATE TABLE `users_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `selector` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users_throttling`
--

CREATE TABLE `users_throttling` (
  `bucket` varchar(44) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `tokens` float UNSIGNED NOT NULL,
  `replenished_at` int(10) UNSIGNED NOT NULL,
  `expires_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_throttling`
--

INSERT INTO `users_throttling` (`bucket`, `tokens`, `replenished_at`, `expires_at`) VALUES
('5JzmOXt9vZmM0Kc-lUvE5UjlLXOu5UtX1BmaBuH0ivc', 499, 1769503511, 1769676311),
('CUeQSH1MUnRpuE3Wqv_fI3nADvMpK_cg6VpYK37vgIw', 4, 1769504123, 1769936123),
('ejWtPDKvxt-q7LZ3mFjzUoIWKJYzu47igC8Jd9mffFk', 61.5282, 1769504591, 1770044591),
('Jjl8HEbTSJpZBWoyXOajJXqciuUdngUbah061jwhliE', 19, 1769503511, 1769539511);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_2fa`
--
ALTER TABLE `users_2fa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_mechanism` (`user_id`,`mechanism`);

--
-- Indexes for table `users_audit_log`
--
ALTER TABLE `users_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_at` (`event_at`),
  ADD KEY `user_id_event_at` (`user_id`,`event_at`),
  ADD KEY `user_id_event_type_event_at` (`user_id`,`event_type`,`event_at`);

--
-- Indexes for table `users_confirmations`
--
ALTER TABLE `users_confirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `email_expires` (`email`,`expires`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users_remembered`
--
ALTER TABLE `users_remembered`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user` (`user`);

--
-- Indexes for table `users_resets`
--
ALTER TABLE `users_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `user_expires` (`user`,`expires`);

--
-- Indexes for table `users_throttling`
--
ALTER TABLE `users_throttling`
  ADD PRIMARY KEY (`bucket`),
  ADD KEY `expires_at` (`expires_at`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblcalendar`
--
ALTER TABLE `tblcalendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tblcategory`
--
ALTER TABLE `tblcategory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tblinventory`
--
ALTER TABLE `tblinventory`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblnotif`
--
ALTER TABLE `tblnotif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tblpatient`
--
ALTER TABLE `tblpatient`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tblservice`
--
ALTER TABLE `tblservice`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users_2fa`
--
ALTER TABLE `users_2fa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_audit_log`
--
ALTER TABLE `users_audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users_confirmations`
--
ALTER TABLE `users_confirmations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_remembered`
--
ALTER TABLE `users_remembered`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users_resets`
--
ALTER TABLE `users_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
