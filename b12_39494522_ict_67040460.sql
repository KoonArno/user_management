-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.byethost12.com
-- Generation Time: Oct 29, 2025 at 05:27 AM
-- Server version: 10.6.22-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `b12_39494522_ict_67040460`
--

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `title_name` varchar(10) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `surname` varchar(50) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `title_name`, `name`, `surname`, `age`, `address`, `phone`, `mail`, `status`, `department`, `profile_image`) VALUES
(670001, 'นาย', 'ป๊อป', 'สยาม', 30, 'กรุงเทพฯ', '0891111111', 'anan@example.com', '3', 'แผนกบุคคล', 'profile_6892d42e803ae.jpg'),
(670002, 'นางสาว', 'ลูกเกด', 'เมทินี', 27, 'ชลบุรี', '0273098102', 'patcharee@example.com', '3', 'แผนกบริการ', 'profile_6892d602a74b1.jpg'),
(670003, 'นาย', 'โตสมชาย', 'ใหญ่ดี', 34, 'นนทบุรี', '0891111113', 'somchai@example.com', '3', 'แผนกIT', 'profile_6892d6bf0e429.jpg'),
(670004, 'นาง', 'แตน', 'ศรีแสง', 32, 'เชียงใหม่', '0891111114', 'suree@example.com', '3', 'แผนกบุคคล', 'profile_6892d40985a35.jpg'),
(670005, 'นาย', 'ธีรภัทร', 'จิตรดี', 29, 'ขอนแก่น', '0891111115', 'teerapat@example.com', '3', 'แผนกบัญชี', 'profile_6892d71f3ea8f.jpg'),
(670006, 'นางสาว', 'มะเดี่ยว', 'เริ่ดเลยล่ะ', 26, 'ระยอง', '0891111116', 'patama@example.com', '3', 'แผนกบริการ', 'profile_6892dabedef62.jpg'),
(670007, 'นาย', 'กิตติพงศ์', 'ใจดี', 31, 'นครสวรรค์', '0891111117', 'kittipong@example.com', '3', 'แผนกIT', 'profile_6894faedd2e87.jpeg'),
(670008, 'นาย', 'เพชรชี่', 'ปากปราร้าหน้าเป๊ะ', 33, 'ศรีสะเกษ', '0891111118', 'darin@example.com', '3', 'แผนกบุคคล', 'profile_6892db3cdba1d.jpg'),
(670009, 'นาย', 'หงส์', 'รักดี', 35, 'สระบุรี', '0891111119', 'witawat@example.com', '3', 'แผนกบัญชี', 'profile_6892dcdcd492f.jpg'),
(670010, 'นางสาว', 'การินดา', 'วงศ์ผู้ดี', 28, 'อุบลราชธานี', '0891111120', 'ornuma@example.com', '3', 'แผนกบริการ', 'profile_6892dc4b1f1d5.jpg'),
(670011, 'นาย', 'ทรงพล', 'มณีสุข', 36, 'นครราชสีมา', '0891111121', 'songpol@example.com', '3', 'แผนกIT', 'profile_6892e4282f34c.jpg'),
(670012, 'นาง', 'บุญนาค', 'รัตนมาลย์', 30, 'ราชบุรี', '0891111122', 'boonnak@example.com', '3', 'แผนกบุคคล', 'profile_6892e1ebb8c37.jpg'),
(670013, 'นาย', 'สิทธัตถะ', 'เอเมอรัล', 32, 'ตรัง', '0891111123', 'weeraphon@example.com', '3', 'แผนกบัญชี', 'profile_6894faa93fd82.jpeg'),
(670014, 'นางสาว', 'จินดา', 'ใจมั่น', 29, 'มหาสารคาม', '0891111124', 'laddawan@example.com', '3', 'แผนกบริการ', 'profile_6894fb7186b6c.jpeg'),
(670015, 'นาย', 'ภานุวัฒน์', 'นาคดี', 30, 'พิษณุโลก', '0891111125', 'panuwat@example.com', '3', 'แผนกIT', NULL),
(670016, 'นาง', 'จิตติมา', 'มีศรี', 31, 'ปทุมธานี', '0891111126', 'jittima@example.com', '3', 'แผนกบุคคล', NULL),
(670017, 'นาย', 'ชัยวัฒน์', 'ศิริกุล', 33, 'ยะลา', '0891111127', 'chaiwat@example.com', '3', 'แผนกบัญชี', NULL),
(670018, 'นาย', 'ประวิตร', 'ชัยเสรี', 40, 'กรุงเทพฯ', '0891111128', 'prawit@example.com', '2', 'แผนกบริการ', 'profile_6892dd44f13b9.jpg'),
(670019, 'นาง', 'ปวีณา', 'วงศ์คำ', 38, 'ลำปาง', '0891111129', 'suwimon@example.com', '2', 'แผนกIT', 'profile_6892dc88e5a94.jpg'),
(670020, 'นาย', 'กรรชัย', 'รุ่งโรจน์', 45, 'กรุงเทพฯ', '0891111130', 'adisak@example.com', '1', 'แผนกบุคคล', 'profile_6892df6c577b3.jpg'),
(670023, 'นางสาว', 'วรรณา', 'สุขดี', 50, 'บางบ่อ 25/600 ', '0856325448', 'uuuui@mn.com', '3', 'แผนกบัญชี', 'profile_6894fa3039351.jpeg'),
(670024, 'นาย', 'อุดร', 'วิเชียร', 56, 'ดอนเมือง 69/855', '0542398798', 'uuuui@mn.com', '1', 'แผนกบริการ', NULL),
(670026, 'นาย', 'ณัฐกานต์', 'กลองกระโทก', 21, '5555555', '0858326159', 'test@admin.com', '1', 'แผนกบุคคล', 'profile_689461ad032b1.jpg'),
(670027, 'นาย', 'admin', 'test', 22, '123456789', '0123456789', 'test@ad.com', '2', 'แผนกบัญชี', 'profile_6892e0f84a6b1.jpg'),
(670028, 'นาย', 'ใจดี', 'ใจดี', 35, 'บางแสนสายใต้4', '0624215787', 'student1@example.com', '3', 'แผนกบัญชี', NULL),
(670029, 'นาย', 'ณัฐกานต์', 'กลองกระโทก', 23, '24 หรือ 4/5 ถ.ศรีนครินทร์ แขวงหัวหมาก เขตบางกะปิ', '0858326159', 'test@gmm.com', '1', 'แผนกIT', 'profile_6892dd933ea2f.jpg'),
(670031, 'นาย', 'Nattakarn', 'Klongkratok', 24, 'Srinagarindra', '0858326159', 'nklongkratok@gmail.com', '2', 'แผนกบุคคล', NULL),
(670037, 'นาย', 'ณัฐกานต์', 'กลองกระโทก', 22, '24 หรือ 4/5 ถ.ศรีนครินทร์ แขวงหัวหมาก เขตบางกะปิ', '0858326159', 'tt@test.com', '3', 'แผนกบุคคล', 'profile_6892d14987f39.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `login_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `member_id`, `username`, `password`, `login_count`) VALUES
(21, 670026, 'jaono', '$2y$10$Vug.0UKqlHkhCVQmdZsZAOVJGkwG3mmNmxG0Y9GLrgt8ajYJYY9Jm', 64),
(22, 670001, 'test', '$2y$10$vAZ82Cf5NmW0QMfBn8BQhedDB9jq0baLBeIrLlEcYOaBLamcnM/P2', 7),
(23, 670018, 'Head', '$2y$10$vpkz38JkwBe5XKDg36.z9ugBaCC5DhuvVN0yPKbNGsaZjg1WasaWK', 7),
(24, 670027, 'Test_Head', '$2y$10$a2G19Kr3XlgktBpbxPyzPeWUy7kqUcBOzj/xnWgz39qhBDgcdB1LS', 0),
(25, 670002, '670002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(26, 670003, '670003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(27, 670004, '670004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(28, 670005, '670005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(29, 670006, '670006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(30, 670007, '670007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(31, 670008, '670008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(32, 670009, '670009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(33, 670010, '670010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(34, 670011, '670011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(35, 670012, '670012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(36, 670013, '670013', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(37, 670014, '670014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(38, 670015, '670015', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(39, 670016, '670016', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(40, 670017, '670017', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(41, 670019, '670019', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(42, 670020, '670020', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(43, 670023, '670023', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(44, 670024, '670024', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(46, 670028, '670028', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(47, 670029, '670029', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0),
(51, 670037, '670037', '$2y$10$YjCRSY1DfoKPreOajAbkTeQPxqOsTenVQ5WjxeEPnUC6Buj4vXDUq', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `member_id` (`member_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=670039;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
