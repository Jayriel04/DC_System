-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2025 at 03:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `AdminRegdate` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`ID`, `AdminName`, `UserName`, `MobileNumber`, `Email`, `Password`, `AdminRegdate`) VALUES
(1, 'Admin', 'admin', 8979555558, 'admin@gmail.com', 'f925916e2754e5e03f75dd58a5733251', '2025-01-01 04:36:52');

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
(2, 'Jezrah Faith', 'Canonio', '2025-09-27', '14:36:00', NULL, 5, '2025-09-27 06:36:43', 'Cancelled', 'sample cancelation!', '2025-11-21 14:14:21'),
(3, 'Rodelyn', 'Estrera', '2025-09-27', '16:42:00', NULL, 8, '2025-09-27 08:42:44', 'Approved', NULL, NULL),
(4, 'John Mar', 'Ypil', '2025-09-27', '18:14:00', '19:00:00', 4, '2025-09-27 10:33:00', 'Approved', NULL, NULL),
(5, 'John Mar', 'Ypil', '2025-10-05', '13:47:00', '14:48:00', 4, '2025-10-05 05:49:13', 'Approved', NULL, NULL),
(6, 'John Mar', 'Ypil', '2025-10-05', '15:14:00', '16:14:00', 4, '2025-10-05 06:15:00', 'Approved', NULL, NULL),
(7, 'Nimfa', 'Conde', '2025-10-05', '17:39:00', '18:40:00', 10, '2025-10-05 09:40:49', 'walk-in', NULL, NULL),
(8, 'Sig', 'Canonio', '2025-10-05', '17:29:00', NULL, 13, '2025-10-20 10:36:59', 'Cancelled', 'sample cancel!', NULL);

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
(11, '2025-11-27', '22:06:00', '23:06:00');

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
(2, 'fsd', 'dsd', '2026-01-24', 0, 'Supply', 'Available'),
(3, 'Isoprophyl Alcohol', 'Doctor J', '2028-06-25', 3, 'Supply', 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `tblpage`
--

CREATE TABLE `tblpage` (
  `ID` int(10) NOT NULL,
  `PageType` varchar(200) DEFAULT NULL,
  `PageTitle` mediumtext DEFAULT NULL,
  `PageDescription` mediumtext DEFAULT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `MobileNumber` bigint(10) DEFAULT NULL,
  `UpdationDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tblpage`
--

INSERT INTO `tblpage` (`ID`, `PageType`, `PageTitle`, `PageDescription`, `Email`, `MobileNumber`, `UpdationDate`) VALUES
(1, 'aboutus', 'About Us', '<div style=\"text-align: start;\"><font color=\"#7b8898\" face=\"Mercury SSm A, Mercury SSm B, Georgia, Times, Times New Roman, Microsoft YaHei New, Microsoft Yahei, ????, ??, SimSun, STXihei, ????, serif\"><span style=\"font-size: 26px;\">Student Management System Developed using PHP and MySQL</span></font><br></div>', NULL, NULL, NULL),
(2, 'contactus', 'Contact Us', '890,Sector 62, Gyan Sarovar, GAIL Noida(Delhi/NCR)', 'studentms@test.com', 1234567890, NULL);

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
(5, 'Jezrah Faith', 'Canonio', '2004-05-13', 'Female', 'Single', 'Princess', 21, '09876543211', 'yati liloan', 'canonio.jezrahfaith.mcc@gmail.com', 'pfpjez.jfif', '{\"general\":[\"Increase frequency of urination\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Family history of diabetes\"],\"thyroid\":[\"Apprehension\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Numbness\\/Tingling\"],\"blood\":[\"Anemia\"],\"respiratory\":[\"Persistent cough\"]}', '2025-09-27 11:03:26', 'jezrah', '3b02af71589ec5ea4138a1ece1866008', NULL, NULL),
(8, 'Rodelyn', 'Estrera', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'estrera.rodelyn.mcc@gmail.com', NULL, '{\"general\":[\"Marked weight change\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Delayed healing of wounds\"],\"thyroid\":[\"Perspire easily\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Headache\"],\"blood\":[\"Bruise easily\"],\"respiratory\":[\"Difficulty in breathing\"]}', '2025-09-27 11:04:04', 'rodelyn', 'bc65be184bd685684a786ac70c6d2ef7', NULL, NULL),
(9, 'Justine', 'Aguinaldo', '0000-00-00', '', '', '', 0, '', '', 'aguinaldo.justinelouise.mcc@gmail.com', NULL, NULL, '2025-09-27 11:04:46', 'justine', 'f6f3e757ac491a3511a5198a39c5ce29', NULL, NULL),
(10, 'Nimfa', 'Conde', '1956-03-09', 'Female', 'Single', 'Mother', 69, '09876543211', 'idk', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'Seg', 'Canonio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'seg@gmail.com', NULL, NULL, NULL, 'seg', '54151f5b2b56a1345561afde6059ac63', NULL, NULL),
(12, 'Crazy', 'Rapidboots', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'crazy@test.com', NULL, NULL, NULL, 'crazy', 'fa2ee41779ef60891cbfdfcd0dccaa7c', NULL, NULL),
(13, 'Sig', 'Canonio', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'sig@test.com', NULL, '{\"general\":[\"Increase frequency of urination\",\"Burning sensation on urination\"],\"liver\":[\"History of liver ailment\"],\"diabetes\":[\"Delayed healing of wounds\"],\"thyroid\":[\"Apprehension\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Headache\",\"Dizziness\\/Fainting\"],\"blood\":[\"Bruise easily\"],\"respiratory\":[\"Persistent cough\"]}', NULL, 'Sig', '252e0253e865dec297431b97709794d5', NULL, NULL);

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
(1, 1, 4, 'John Mar', 'Ypil', '2025-09-26', '21:32:00', 4, '2025-09-26 05:32:21', 30, 'Ongoing', 'idk', '2025-10-05 19:05:16'),
(2, 2, 5, 'Jezrah Faith', 'Canonio', '2025-09-27', '14:39:00', 4, '2025-09-26 22:39:38', 45, 'Request Cancel', 'sample cancel', '2025-11-21 14:59:03'),
(3, 3, 8, 'Rodelyn', 'Estrera', '2025-09-27', '18:44:00', 4, '2025-09-27 02:44:52', 67, 'Ongoing', NULL, NULL),
(16, 4, 4, 'John Mar', 'Ypil', '2025-10-05', '16:29:00', 4, '2025-10-05 08:29:34', 50, 'Ongoing', NULL, NULL),
(17, 5, 4, 'John Mar', 'Ypil', '2025-10-05', '16:39:00', 4, '2025-10-05 08:39:17', 34, 'Done', NULL, NULL),
(18, 6, 4, 'John Mar', 'Ypil', '2025-10-05', '16:39:00', 4, '2025-10-05 08:39:42', 56, 'Cancelled', 'cancel', NULL);

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
(4, 'Etchant', 'Agoyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy', 1, 'images/588880344_1162759702712818_3890524668527245534_n.jpg'),
(5, 'Ibot ngipon', 'Pa ibot ka ngipon gaw', 2, 'images/services/sstudy laod.jpg'),
(6, 'Sample 3', 'Idk aahahahhansklwkldnqkw', 3, 'images/services/image.png'),
(7, 'Sample 4', 'Sample for number 5', 4, 'images/services/logo.png'),
(8, 'Sample 5', 'Sample number 5', 5, 'images/services/mcc_logo2.jpg'),
(9, 'Sample 6', 'Sample for number 6', NULL, 'images/services/mcc.jpg'),
(10, 'Sample 7', 'This is for sample service', NULL, 'images/services/Mandaue City College Student Profiling.png'),
(11, 'Sample service', 'Sample category for a service', NULL, 'images/mcc new logo.jpg');

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
-- Indexes for table `tblpage`
--
ALTER TABLE `tblpage`
  ADD PRIMARY KEY (`ID`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tblcalendar`
--
ALTER TABLE `tblcalendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- AUTO_INCREMENT for table `tblpage`
--
ALTER TABLE `tblpage`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblpatient`
--
ALTER TABLE `tblpatient`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tblschedule`
--
ALTER TABLE `tblschedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
