-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2025 at 01:47 PM
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
  `time` time NOT NULL,
  `patient_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblappointment`
--

INSERT INTO `tblappointment` (`id`, `firstname`, `surname`, `date`, `time`, `patient_number`, `created_at`, `status`) VALUES
(1, 'John Mar', 'Ypil', '2025-09-26', '17:47:00', 4, '2025-09-26 09:54:12', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `tblcalendar`
--

CREATE TABLE `tblcalendar` (
  `id` int(11) NOT NULL,
  `slots` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcalendar`
--

INSERT INTO `tblcalendar` (`id`, `slots`, `date`, `start_time`, `duration`, `end_time`) VALUES
(1, '45', '2025-09-19', '17:00:00', 56, '17:05:00'),
(2, '3', '2025-09-24', '20:49:00', 30, '17:49:00'),
(3, '3', '2025-09-24', '20:49:00', 30, '17:49:00');

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
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `health_conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`health_conditions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblpatient`
--

INSERT INTO `tblpatient` (`number`, `firstname`, `surname`, `date_of_birth`, `sex`, `status`, `occupation`, `age`, `contact_number`, `address`, `username`, `password`, `Image`, `health_conditions`) VALUES
(1, 'Jayriel', 'Senilla', '2004-04-09', NULL, 'Single', '', 21, '09319106644', 'casili, consolacion', 'jay', 'jay123', NULL, NULL),
(2, 'Jezrah Faith', 'Canonio', '2004-05-13', NULL, NULL, NULL, 21, '09876543211', 'IDK', NULL, NULL, NULL, NULL),
(3, 'Jezrah Faith', 'Canonio', '2004-05-13', NULL, NULL, NULL, 21, '09876543211', 'IDK', NULL, NULL, NULL, NULL),
(4, 'John Mar', 'Ypil', '2002-12-21', NULL, NULL, NULL, NULL, '09374939832', 'idk', 'jm', 'a2d1bd818ac8a8419ccfecc4bef27035', 'pfpjm.jfif', '{\"general\":[\"Increase frequency of urination\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Delayed healing of wounds\"],\"thyroid\":[\"Apprehension\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Headache\"],\"blood\":[\"Anemia\"],\"respiratory\":[\"Persistent cough\"]}'),
(5, 'Jezrah Faith', 'Canonio', '2004-05-13', 'Male', 'idk', 'Princess', 21, '09876543211', 'yati liloan', 'jezrah', '3b02af71589ec5ea4138a1ece1866008', 'pfpjez.jfif', '{\"general\":[\"Increase frequency of urination\"],\"liver_specify\":\"\",\"liver\":[\"Jaundice\"],\"diabetes\":[\"Family history of diabetes\"],\"thyroid\":[\"Apprehension\"],\"urinary\":[\"Increase frequency of urination\"],\"nervous\":[\"Numbness\\/Tingling\"],\"blood\":[\"Anemia\"],\"respiratory\":[\"Persistent cough\"]}'),
(6, 'Rodelyn', 'Estrera', '2004-06-30', NULL, NULL, NULL, 21, '09564780461', 'Bakilid, Mandaue City', NULL, NULL, NULL, NULL),
(7, 'Justine Louise', 'Aguinaldo', '0003-06-28', 'Female', 'Single', 'student', 2022, '09476372827', 'Bakilid, Mandaue City', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblpatient_history`
--

CREATE TABLE `tblpatient_history` (
  `id` int(11) NOT NULL,
  `patient_number` int(11) NOT NULL,
  `date` date NOT NULL,
  `details` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tblservice`
--

CREATE TABLE `tblservice` (
  `number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(500) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblservice`
--

INSERT INTO `tblservice` (`number`, `name`, `description`, `image`) VALUES
(4, 'Etchant', 'HAHAHAAHAHAHAHHHAHAHAHA', 'images/services/cat-1.png');

-- --------------------------------------------------------

--
-- Table structure for table `tbluploadedhomeworks`
--

CREATE TABLE `tbluploadedhomeworks` (
  `id` int(11) NOT NULL,
  `homeworkId` int(11) DEFAULT NULL,
  `studentId` int(11) DEFAULT NULL,
  `homeworkDescription` longtext DEFAULT NULL,
  `homeworkFile` varchar(255) DEFAULT NULL,
  `postinDate` timestamp NULL DEFAULT current_timestamp(),
  `adminRemark` mediumtext DEFAULT NULL,
  `adminRemarkDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbluploadedhomeworks`
--

INSERT INTO `tbluploadedhomeworks` (`id`, `homeworkId`, `studentId`, `homeworkDescription`, `homeworkFile`, `postinDate`, `adminRemark`, `adminRemarkDate`) VALUES
(1, 2, 4, 'upload', '869d2b4df212b9b55402b8fca8e28870.pdf', '2025-01-01 05:47:45', 'ok', '2025-01-01 09:44:36'),
(2, 4, 6, 'Done', 'a375fcfbcac4b897b4574fbd4003467d.pdf', '2025-01-04 04:13:46', NULL, NULL);

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
  ADD PRIMARY KEY (`number`);

--
-- Indexes for table `tblpatient_history`
--
ALTER TABLE `tblpatient_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patient_number` (`patient_number`);

--
-- Indexes for table `tblservice`
--
ALTER TABLE `tblservice`
  ADD PRIMARY KEY (`number`);

--
-- Indexes for table `tbluploadedhomeworks`
--
ALTER TABLE `tbluploadedhomeworks`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tblpatient_history`
--
ALTER TABLE `tblpatient_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tblservice`
--
ALTER TABLE `tblservice`
  MODIFY `number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbluploadedhomeworks`
--
ALTER TABLE `tbluploadedhomeworks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblpatient_history`
--
ALTER TABLE `tblpatient_history`
  ADD CONSTRAINT `tblpatient_history_ibfk_1` FOREIGN KEY (`patient_number`) REFERENCES `tblpatient` (`number`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
