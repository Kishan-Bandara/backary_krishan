-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 03, 2026 at 02:23 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bakery`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_shipping_address`
--

CREATE TABLE `backup_shipping_address` (
  `id` int(10) NOT NULL,
  `fk_customer_id` int(10) NOT NULL,
  `fk_delivery_method` int(10) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` varchar(200) NOT NULL,
  `fk_city` int(10) NOT NULL,
  `contact_no` varchar(15) NOT NULL,
  `update_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `bakup_customer`
--

CREATE TABLE `bakup_customer` (
  `customer_id` int(10) NOT NULL,
  `customer_email` varchar(50) DEFAULT NULL,
  `customer_password` varchar(250) NOT NULL,
  `customer_activated` int(1) NOT NULL,
  `customer_locked` int(1) NOT NULL,
  `customer_title` varchar(10) DEFAULT NULL,
  `customer_name` varchar(50) DEFAULT NULL,
  `customer_nic` varchar(15) NOT NULL,
  `customer_avtive_code` text NOT NULL,
  `customer_address` text DEFAULT NULL,
  `customer_discount` double(20,0) DEFAULT 0,
  `customer_tell` int(20) DEFAULT NULL,
  `customer_mobile` int(20) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `customer_outstanding_balance` double(20,2) DEFAULT 0.00,
  `customer_cradit_limite` double(20,2) DEFAULT 0.00,
  `customer_update_date` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `banks`
--

CREATE TABLE `banks` (
  `Id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `path` text DEFAULT NULL,
  `image` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `banks`
--

INSERT INTO `banks` (`Id`, `name`, `path`, `image`) VALUES
(1, 'Sampath Bank', 'images/creditcards/', 'hnb-credit-cards.png'),
(2, 'HNB Bank', 'images/creditcards/', 'sampath-credit-cards.png'),
(3, 'People\'s Bank', 'images/creditcards/', 'peoplesBank.png');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(10) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `imagePath` varchar(255) DEFAULT NULL,
  `link` text DEFAULT NULL,
  `link_label` varchar(100) DEFAULT NULL,
  `bg_color` varchar(20) DEFAULT NULL,
  `group_id` varchar(255) NOT NULL,
  `columns` int(10) DEFAULT NULL,
  `SelectedOrder` int(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `description`, `image`, `imagePath`, `link`, `link_label`, `bg_color`, `group_id`, `columns`, `SelectedOrder`) VALUES
(1, NULL, NULL, 'ba13.jpg', 'img/promotion/', NULL, NULL, NULL, 'group_1', 6, 100),
(2, NULL, NULL, 'ba14.jpg', 'img/promotion/', NULL, NULL, NULL, 'group_1', 6, 200);

-- --------------------------------------------------------

--
-- Table structure for table `categorymappingitem`
--

CREATE TABLE `categorymappingitem` (
  `Id` int(10) NOT NULL,
  `ItemId` int(10) NOT NULL DEFAULT 0,
  `CategoryId` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `category_master`
--

CREATE TABLE `category_master` (
  `category_id` int(10) NOT NULL,
  `clean_url` varchar(50) NOT NULL,
  `type_id` int(10) DEFAULT NULL,
  `category_name` varchar(50) NOT NULL,
  `category_discription` text DEFAULT NULL,
  `value1` text DEFAULT NULL,
  `value2` text DEFAULT NULL,
  `website_status` enum('N','Y') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `category_master`
--

INSERT INTO `category_master` (`category_id`, `clean_url`, `type_id`, `category_name`, `category_discription`, `value1`, `value2`, `website_status`) VALUES
(1, '', 1, 'White Bread', NULL, NULL, NULL, 'Y'),
(2, '', 1, 'Seeded Bread', NULL, NULL, NULL, 'Y'),
(3, '', 2, 'Cake', NULL, NULL, NULL, 'Y'),
(4, '', 2, 'Cupcakes', NULL, NULL, NULL, 'Y'),
(5, '', 3, 'Cookies', NULL, NULL, NULL, 'Y'),
(6, '', 3, 'Biscuits', NULL, NULL, NULL, 'Y'),
(7, '', 13, 'Raw matirials', NULL, NULL, NULL, 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `city_master`
--

CREATE TABLE `city_master` (
  `id` int(10) NOT NULL,
  `city` varchar(50) NOT NULL,
  `rate` double(22,2) NOT NULL DEFAULT 0.00,
  `flag` int(1) NOT NULL,
  `area` int(1) NOT NULL,
  `perKgRate` double(22,2) NOT NULL,
  `SetRateMinOrder` double(22,2) NOT NULL DEFAULT 0.00,
  `countryId` int(10) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `city_master`
--

INSERT INTO `city_master` (`id`, `city`, `rate`, `flag`, `area`, `perKgRate`, `SetRateMinOrder`, `countryId`) VALUES
(1, 'Akkaraipattu', 0.00, 1, 3, 0.00, 0.00, 57),
(2, 'Ambagahawatta', 0.00, 1, 3, 0.00, 0.00, 57),
(3, 'Ampara', 0.00, 1, 3, 0.00, 0.00, 57),
(4, 'Bakmitiyawa', 0.00, 1, 3, 0.00, 0.00, 57),
(5, 'Deegawapiya', 0.00, 1, 3, 0.00, 0.00, 57),
(6, 'Devalahinda', 0.00, 1, 3, 0.00, 0.00, 57),
(7, 'Digamadulla Weeragoda', 0.00, 1, 3, 0.00, 0.00, 57),
(8, 'Dorakumbura', 0.00, 1, 3, 0.00, 0.00, 57),
(9, 'Gonagolla', 0.00, 1, 3, 0.00, 0.00, 57),
(10, 'Hulannuge', 0.00, 1, 3, 0.00, 0.00, 57),
(11, 'Kalmunai', 0.00, 1, 3, 0.00, 0.00, 57),
(12, 'Kannakipuram', 0.00, 1, 3, 0.00, 0.00, 57),
(13, 'Karativu', 0.00, 1, 3, 0.00, 0.00, 57),
(14, 'Kekirihena', 0.00, 1, 3, 0.00, 0.00, 57),
(15, 'Koknahara', 0.00, 1, 3, 0.00, 0.00, 57),
(16, 'Kolamanthalawa', 0.00, 1, 3, 0.00, 0.00, 57),
(17, 'Komari', 0.00, 1, 3, 0.00, 0.00, 57),
(18, 'Lahugala', 0.00, 1, 3, 0.00, 0.00, 57),
(19, 'lmkkamam', 0.00, 1, 3, 0.00, 0.00, 57),
(20, 'Mahaoya', 0.00, 1, 3, 0.00, 0.00, 57),
(21, 'Marathamune', 0.00, 1, 3, 0.00, 0.00, 57),
(22, 'Namaloya', 0.00, 1, 3, 0.00, 0.00, 57),
(23, 'Navithanveli', 0.00, 1, 3, 0.00, 0.00, 57),
(24, 'Nintavur', 0.00, 1, 3, 0.00, 0.00, 57),
(25, 'Oluvil', 0.00, 1, 3, 0.00, 0.00, 57),
(26, 'Padiyatalawa', 0.00, 1, 3, 0.00, 0.00, 57),
(27, 'Pahalalanda', 0.00, 1, 3, 0.00, 0.00, 57),
(28, 'Panama', 0.00, 1, 3, 0.00, 0.00, 57),
(29, 'Pannalagama', 0.00, 0, 3, 0.00, 0.00, 57),
(30, 'Paragahakele', 0.00, 0, 3, 0.00, 0.00, 57),
(31, 'Periyaneelavanai', 0.00, 0, 3, 0.00, 0.00, 57),
(32, 'Polwaga Janapadaya', 0.00, 0, 3, 0.00, 0.00, 57),
(33, 'Pottuvil', 0.00, 0, 3, 0.00, 0.00, 57),
(34, 'Sainthamaruthu', 0.00, 0, 3, 0.00, 0.00, 57),
(35, 'Samanthurai', 0.00, 0, 3, 0.00, 0.00, 57),
(36, 'Serankada', 0.00, 0, 3, 0.00, 0.00, 57),
(37, 'Tempitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(38, 'Thambiluvil', 0.00, 0, 3, 0.00, 0.00, 57),
(39, 'Tirukovil', 0.00, 0, 3, 0.00, 0.00, 57),
(40, 'Uhana', 0.00, 0, 3, 0.00, 0.00, 57),
(41, 'Wadinagala', 0.00, 0, 3, 0.00, 0.00, 57),
(42, 'Wanagamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(43, 'Angamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(44, 'Anuradhapura', 0.00, 0, 3, 0.00, 0.00, 57),
(45, 'Awukana', 0.00, 0, 3, 0.00, 0.00, 57),
(46, 'Bogahawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(47, 'Dematawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(48, 'Dimbulagala', 0.00, 0, 3, 0.00, 0.00, 57),
(49, 'Dutuwewa', 0.00, 0, 3, 0.00, 0.00, 57),
(50, 'Elayapattuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(51, 'Ellewewa', 0.00, 0, 3, 0.00, 0.00, 57),
(52, 'Eppawala', 0.00, 0, 3, 0.00, 0.00, 57),
(53, 'Etawatunuwewa', 0.00, 0, 3, 0.00, 0.00, 57),
(54, 'Etaweeragollewa', 0.00, 0, 3, 0.00, 0.00, 57),
(55, 'Galapitagala', 0.00, 0, 3, 0.00, 0.00, 57),
(56, 'Galenbindunuwewa', 0.00, 0, 3, 0.00, 0.00, 57),
(57, 'Galkadawala', 0.00, 0, 3, 0.00, 0.00, 57),
(58, 'Galkiriyagama', 0.00, 0, 3, 0.00, 0.00, 57),
(59, 'Galkulama', 0.00, 0, 3, 0.00, 0.00, 57),
(60, 'Galnewa', 0.00, 0, 3, 0.00, 0.00, 57),
(61, 'Gambirigaswewa', 0.00, 0, 3, 0.00, 0.00, 57),
(62, 'Ganewalpola', 0.00, 0, 3, 0.00, 0.00, 57),
(63, 'Gemunupura', 0.00, 0, 3, 0.00, 0.00, 57),
(64, 'Getalawa', 0.00, 0, 3, 0.00, 0.00, 57),
(65, 'Gnanikulama', 0.00, 0, 3, 0.00, 0.00, 57),
(66, 'Gonahaddenawa', 0.00, 0, 3, 0.00, 0.00, 57),
(67, 'Habarana', 0.00, 0, 3, 0.00, 0.00, 57),
(68, 'Halmillawa Dambulla', 0.00, 0, 3, 0.00, 0.00, 57),
(69, 'Halmillawetiya', 0.00, 0, 3, 0.00, 0.00, 57),
(70, 'Hidogama', 0.00, 0, 3, 0.00, 0.00, 57),
(71, 'Horawpatana', 0.00, 0, 3, 0.00, 0.00, 57),
(72, 'Horiwila', 0.00, 0, 3, 0.00, 0.00, 57),
(73, 'Hurigaswewa', 0.00, 0, 3, 0.00, 0.00, 57),
(74, 'Hurulunikawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(75, 'Ihala Puliyankulama', 0.00, 0, 3, 0.00, 0.00, 57),
(76, 'Kagama', 0.00, 0, 3, 0.00, 0.00, 57),
(77, 'Kahatagasdigiliya', 0.00, 0, 3, 0.00, 0.00, 57),
(78, 'Kahatagollewa', 0.00, 0, 3, 0.00, 0.00, 57),
(79, 'Kalakarambewa', 0.00, 0, 3, 0.00, 0.00, 57),
(80, 'Kalaoya', 0.00, 0, 3, 0.00, 0.00, 57),
(81, 'Kalawedi Ulpotha', 0.00, 0, 3, 0.00, 0.00, 57),
(82, 'Kallanchiya', 0.00, 0, 3, 0.00, 0.00, 57),
(83, 'Kalpitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(84, 'Kalukele Badanagala', 0.00, 0, 3, 0.00, 0.00, 57),
(85, 'Kapugallawa', 0.00, 0, 3, 0.00, 0.00, 57),
(86, 'Karagahawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(87, 'Kashyapapura', 0.00, 0, 3, 0.00, 0.00, 57),
(88, 'Kebithigollewa', 0.00, 0, 3, 0.00, 0.00, 57),
(89, 'Kekirawa', 0.00, 0, 3, 0.00, 0.00, 57),
(90, 'Kendewa', 0.00, 0, 3, 0.00, 0.00, 57),
(91, 'Kiralogama', 0.00, 0, 3, 0.00, 0.00, 57),
(92, 'Kirigalwewa', 0.00, 0, 3, 0.00, 0.00, 57),
(93, 'Kirimundalama', 0.00, 0, 3, 0.00, 0.00, 57),
(94, 'Kitulhitiyawa', 0.00, 0, 3, 0.00, 0.00, 57),
(95, 'Kurundankulama', 0.00, 0, 3, 0.00, 0.00, 57),
(96, 'Labunoruwa', 0.00, 0, 3, 0.00, 0.00, 57),
(97, 'Ihalagama', 0.00, 0, 3, 0.00, 0.00, 57),
(98, 'Ipologama', 0.00, 0, 3, 0.00, 0.00, 57),
(99, 'Madatugama', 0.00, 0, 3, 0.00, 0.00, 57),
(100, 'Maha Elagamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(101, 'Mahabulankulama', 0.00, 0, 3, 0.00, 0.00, 57),
(102, 'Mahailluppallama', 0.00, 0, 3, 0.00, 0.00, 57),
(103, 'Mahakanadarawa', 0.00, 0, 3, 0.00, 0.00, 57),
(104, 'Mahapothana', 0.00, 0, 3, 0.00, 0.00, 57),
(105, 'Mahasenpura', 0.00, 0, 3, 0.00, 0.00, 57),
(106, 'Mahawilachchiya', 0.00, 0, 3, 0.00, 0.00, 57),
(107, 'Mailagaswewa', 0.00, 0, 3, 0.00, 0.00, 57),
(108, 'Malwanagama', 0.00, 0, 3, 0.00, 0.00, 57),
(109, 'Maneruwa', 0.00, 0, 3, 0.00, 0.00, 57),
(110, 'Maradankadawala', 0.00, 0, 3, 0.00, 0.00, 57),
(111, 'Maradankalla', 0.00, 0, 3, 0.00, 0.00, 57),
(112, 'Medawachchiya', 0.00, 0, 3, 0.00, 0.00, 57),
(113, 'Megodawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(114, 'Mihintale', 0.00, 0, 3, 0.00, 0.00, 57),
(115, 'Morakewa', 0.00, 0, 3, 0.00, 0.00, 57),
(116, 'Mulkiriyawa', 0.00, 0, 3, 0.00, 0.00, 57),
(117, 'Muriyakadawala', 0.00, 0, 3, 0.00, 0.00, 57),
(118, 'Colombo 15', 190.00, 0, 1, 50.00, 0.00, 57),
(119, 'Nachchaduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(120, 'Namalpura', 0.00, 0, 3, 0.00, 0.00, 57),
(121, 'Negampaha', 0.00, 0, 3, 0.00, 0.00, 57),
(122, 'Nochchiyagama', 0.00, 0, 3, 0.00, 0.00, 57),
(123, 'Nuwaragala', 0.00, 0, 3, 0.00, 0.00, 57),
(124, 'Padavi Maithripura', 0.00, 0, 3, 0.00, 0.00, 57),
(125, 'Padavi Parakramapura', 0.00, 0, 3, 0.00, 0.00, 57),
(126, 'Padavi Sripura', 0.00, 0, 3, 0.00, 0.00, 57),
(127, 'Padavi Sritissapura', 0.00, 0, 3, 0.00, 0.00, 57),
(128, 'Padaviya', 0.00, 0, 3, 0.00, 0.00, 57),
(129, 'Padikaramaduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(130, 'Pahala Halmillewa', 0.00, 0, 3, 0.00, 0.00, 57),
(131, 'Pahala Maragahawe', 0.00, 0, 3, 0.00, 0.00, 57),
(132, 'Pahalagama', 0.00, 0, 3, 0.00, 0.00, 57),
(133, 'Palugaswewa', 0.00, 0, 3, 0.00, 0.00, 57),
(134, 'Pandukabayapura', 0.00, 0, 3, 0.00, 0.00, 57),
(135, 'Pandulagama', 0.00, 0, 3, 0.00, 0.00, 57),
(136, 'Parakumpura', 0.00, 0, 3, 0.00, 0.00, 57),
(137, 'Parangiyawadiya', 0.00, 0, 3, 0.00, 0.00, 57),
(138, 'Parasangahawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(139, 'Pelatiyawa', 0.00, 0, 3, 0.00, 0.00, 57),
(140, 'Pemaduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(141, 'Perimiyankulama', 0.00, 0, 3, 0.00, 0.00, 57),
(142, 'Pihimbiyagolewa', 0.00, 0, 3, 0.00, 0.00, 57),
(143, 'Pubbogama', 0.00, 0, 3, 0.00, 0.00, 57),
(144, 'Punewa', 0.00, 0, 3, 0.00, 0.00, 57),
(145, 'Rajanganaya', 0.00, 0, 3, 0.00, 0.00, 57),
(146, 'Rambewa', 0.00, 0, 3, 0.00, 0.00, 57),
(147, 'Rampathwila', 0.00, 0, 3, 0.00, 0.00, 57),
(148, 'Rathmalgahawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(149, 'Saliyapura', 0.00, 0, 3, 0.00, 0.00, 57),
(150, 'Seeppukulama', 0.00, 0, 3, 0.00, 0.00, 57),
(151, 'Senapura', 0.00, 0, 3, 0.00, 0.00, 57),
(152, 'Sivalakulama', 0.00, 0, 3, 0.00, 0.00, 57),
(153, 'Siyambalewa', 0.00, 0, 3, 0.00, 0.00, 57),
(154, 'Sravasthipura', 0.00, 0, 3, 0.00, 0.00, 57),
(155, 'Talawa', 0.00, 0, 3, 0.00, 0.00, 57),
(156, 'Tambuttegama', 0.00, 0, 3, 0.00, 0.00, 57),
(157, 'Tammennawa', 0.00, 0, 3, 0.00, 0.00, 57),
(158, 'Tantirimale', 0.00, 0, 3, 0.00, 0.00, 57),
(159, 'Telhiriyawa', 0.00, 0, 3, 0.00, 0.00, 57),
(160, 'Tirappane', 0.00, 0, 3, 0.00, 0.00, 57),
(161, 'Tittagonewa', 0.00, 0, 3, 0.00, 0.00, 57),
(162, 'Udunuwara Colony', 0.00, 0, 3, 0.00, 0.00, 57),
(163, 'Upuldeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(164, 'Uttimaduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(165, 'Vellamanal', 0.00, 0, 3, 0.00, 0.00, 57),
(166, 'Viharapalugama', 0.00, 0, 3, 0.00, 0.00, 57),
(167, 'Wahalkada', 0.00, 0, 3, 0.00, 0.00, 57),
(168, 'Wahamalgollewa', 0.00, 0, 3, 0.00, 0.00, 57),
(169, 'Walagambahuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(170, 'Walahaviddawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(171, 'Welimuwapotana', 0.00, 0, 3, 0.00, 0.00, 57),
(172, 'Welioya Project', 0.00, 0, 3, 0.00, 0.00, 57),
(173, 'Akkarasiyaya', 0.00, 0, 1, 0.00, 0.00, 57),
(174, 'Aluketiyawa', 0.00, 0, 1, 0.00, 0.00, 57),
(175, 'Aluttaramma', 0.00, 0, 1, 0.00, 0.00, 57),
(176, 'Ambadandegama', 0.00, 0, 1, 0.00, 0.00, 57),
(177, 'Ambagasdowa', 0.00, 0, 1, 0.00, 0.00, 57),
(178, 'Arawa', 0.00, 0, 1, 0.00, 0.00, 57),
(179, 'Arawakumbura', 0.00, 0, 1, 0.00, 0.00, 57),
(180, 'Arawatta', 0.00, 0, 1, 0.00, 0.00, 57),
(181, 'Atakiriya', 0.00, 0, 1, 0.00, 0.00, 57),
(182, 'Badulla', 0.00, 0, 1, 0.00, 0.00, 57),
(183, 'Baduluoya', 0.00, 0, 1, 0.00, 0.00, 57),
(184, 'Ballaketuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(185, 'Bambarapana', 0.00, 0, 1, 0.00, 0.00, 57),
(186, 'Bandarawela', 0.00, 0, 1, 0.00, 0.00, 57),
(187, 'Beramada', 0.00, 0, 1, 0.00, 0.00, 57),
(188, 'Bibilegama', 0.00, 0, 1, 0.00, 0.00, 57),
(189, 'Boragas', 0.00, 0, 1, 0.00, 0.00, 57),
(190, 'Boralanda', 0.00, 0, 1, 0.00, 0.00, 57),
(191, 'Bowela', 0.00, 0, 1, 0.00, 0.00, 57),
(192, 'Central Camp', 0.00, 0, 1, 0.00, 0.00, 57),
(193, 'Damanewela', 0.00, 0, 1, 0.00, 0.00, 57),
(194, 'Dambana', 0.00, 0, 1, 0.00, 0.00, 57),
(195, 'Dehiattakandiya', 0.00, 0, 1, 0.00, 0.00, 57),
(196, 'Demodara', 0.00, 0, 1, 0.00, 0.00, 57),
(197, 'Diganatenna', 0.00, 0, 1, 0.00, 0.00, 57),
(198, 'Dikkapitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(199, 'Dimbulana', 0.00, 0, 1, 0.00, 0.00, 57),
(200, 'Divulapelessa', 0.00, 0, 1, 0.00, 0.00, 57),
(201, 'Diyatalawa', 0.00, 0, 1, 0.00, 0.00, 57),
(202, 'Dulgolla', 0.00, 0, 1, 0.00, 0.00, 57),
(203, 'Ekiriyankumbura', 0.00, 0, 1, 0.00, 0.00, 57),
(204, 'Ella', 0.00, 0, 1, 0.00, 0.00, 57),
(205, 'Ettampitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(206, 'Galauda', 0.00, 0, 1, 0.00, 0.00, 57),
(207, 'Galporuyaya', 0.00, 0, 1, 0.00, 0.00, 57),
(208, 'Gawarawela', 0.00, 0, 1, 0.00, 0.00, 57),
(209, 'Girandurukotte', 0.00, 0, 1, 0.00, 0.00, 57),
(210, 'Godunna', 0.00, 0, 1, 0.00, 0.00, 57),
(211, 'Gurutalawa', 0.00, 0, 1, 0.00, 0.00, 57),
(212, 'Haldummulla', 0.00, 0, 1, 0.00, 0.00, 57),
(213, 'Hali Ela', 0.00, 0, 1, 0.00, 0.00, 57),
(214, 'Hangunnawa', 0.00, 0, 1, 0.00, 0.00, 57),
(215, 'Haputale', 0.00, 0, 1, 0.00, 0.00, 57),
(216, 'Hebarawa', 0.00, 0, 1, 0.00, 0.00, 57),
(217, 'Heeloya', 0.00, 0, 1, 0.00, 0.00, 57),
(218, 'Helahalpe', 0.00, 0, 1, 0.00, 0.00, 57),
(219, 'Helapupula', 0.00, 0, 1, 0.00, 0.00, 57),
(220, 'Hopton', 0.00, 0, 1, 0.00, 0.00, 57),
(221, 'Idalgashinna', 0.00, 0, 1, 0.00, 0.00, 57),
(222, 'Kahataruppa', 0.00, 0, 1, 0.00, 0.00, 57),
(223, 'Kalugahakandura', 0.00, 0, 1, 0.00, 0.00, 57),
(224, 'Kalupahana', 0.00, 0, 1, 0.00, 0.00, 57),
(225, 'Kebillawela', 0.00, 0, 1, 0.00, 0.00, 57),
(226, 'Kendagolla', 0.00, 0, 1, 0.00, 0.00, 57),
(227, 'Keselpotha', 0.00, 0, 1, 0.00, 0.00, 57),
(228, 'Ketawatta', 0.00, 0, 1, 0.00, 0.00, 57),
(229, 'Kiriwanagama', 0.00, 0, 1, 0.00, 0.00, 57),
(230, 'Koslanda', 0.00, 0, 1, 0.00, 0.00, 57),
(231, 'Kuruwitenna', 0.00, 0, 1, 0.00, 0.00, 57),
(232, 'Kuttiyagolla', 0.00, 0, 1, 0.00, 0.00, 57),
(233, 'Landewela', 0.00, 0, 1, 0.00, 0.00, 57),
(234, 'Liyangahawela', 0.00, 0, 1, 0.00, 0.00, 57),
(235, 'Lunugala', 0.00, 0, 1, 0.00, 0.00, 57),
(236, 'Lunuwatta', 0.00, 0, 1, 0.00, 0.00, 57),
(237, 'Madulsima', 0.00, 0, 1, 0.00, 0.00, 57),
(238, 'Mahiyanganaya', 0.00, 0, 1, 0.00, 0.00, 57),
(239, 'Makulella', 0.00, 0, 1, 0.00, 0.00, 57),
(240, 'Malgoda', 0.00, 0, 1, 0.00, 0.00, 57),
(241, 'Mapakadawewa', 0.00, 0, 1, 0.00, 0.00, 57),
(242, 'Maspanna', 0.00, 0, 1, 0.00, 0.00, 57),
(243, 'Maussagolla', 0.00, 0, 1, 0.00, 0.00, 57),
(244, 'Mawanagama', 0.00, 0, 1, 0.00, 0.00, 57),
(245, 'Medawela Udukinda', 0.00, 0, 1, 0.00, 0.00, 57),
(246, 'Meegahakiula', 0.00, 0, 1, 0.00, 0.00, 57),
(247, 'Metigahatenna', 0.00, 0, 1, 0.00, 0.00, 57),
(248, 'Mirahawatta', 0.00, 0, 1, 0.00, 0.00, 57),
(249, 'Miriyabedda', 0.00, 0, 1, 0.00, 0.00, 57),
(250, 'Nawamedagama', 0.00, 0, 1, 0.00, 0.00, 57),
(251, 'Nelumgama', 0.00, 0, 1, 0.00, 0.00, 57),
(252, 'Nikapotha', 0.00, 0, 1, 0.00, 0.00, 57),
(253, 'Nugatalawa', 0.00, 0, 1, 0.00, 0.00, 57),
(254, 'Ohiya', 0.00, 0, 1, 0.00, 0.00, 57),
(255, 'Pahalarathkinda', 0.00, 0, 1, 0.00, 0.00, 57),
(256, 'Pallekiruwa', 0.00, 0, 1, 0.00, 0.00, 57),
(257, 'Passara', 0.00, 0, 1, 0.00, 0.00, 57),
(258, 'Pattiyagedara', 0.00, 0, 1, 0.00, 0.00, 57),
(259, 'Pelagahatenna', 0.00, 0, 1, 0.00, 0.00, 57),
(260, 'Perawella', 0.00, 0, 1, 0.00, 0.00, 57),
(261, 'Pitamaruwa', 0.00, 0, 1, 0.00, 0.00, 57),
(262, 'Pitapola', 0.00, 0, 1, 0.00, 0.00, 57),
(263, 'Puhulpola', 0.00, 0, 1, 0.00, 0.00, 57),
(264, 'Rajagalatenna', 0.00, 0, 1, 0.00, 0.00, 57),
(265, 'Rathkarawwa', 0.00, 0, 1, 0.00, 0.00, 57),
(266, 'Ridimaliyadda', 0.00, 0, 1, 0.00, 0.00, 57),
(267, 'Silmiyapura', 0.00, 0, 1, 0.00, 0.00, 57),
(268, 'Sirimalgoda', 0.00, 0, 1, 0.00, 0.00, 57),
(269, 'Siripura', 0.00, 0, 1, 0.00, 0.00, 57),
(270, 'Sorabora Colony', 0.00, 0, 1, 0.00, 0.00, 57),
(271, 'Soragune', 0.00, 0, 1, 0.00, 0.00, 57),
(272, 'Soranathota', 0.00, 0, 1, 0.00, 0.00, 57),
(273, 'Taldena', 0.00, 0, 1, 0.00, 0.00, 57),
(274, 'Timbirigaspitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(275, 'Uduhawara', 0.00, 0, 1, 0.00, 0.00, 57),
(276, 'Uraniya', 0.00, 0, 1, 0.00, 0.00, 57),
(277, 'Uva Karandagolla', 0.00, 0, 1, 0.00, 0.00, 57),
(278, 'Uva Mawelagama', 0.00, 0, 1, 0.00, 0.00, 57),
(279, 'Uva Tenna', 0.00, 0, 1, 0.00, 0.00, 57),
(280, 'Uva Tissapura', 0.00, 0, 1, 0.00, 0.00, 57),
(281, 'Welimada', 0.00, 0, 1, 0.00, 0.00, 57),
(282, 'Weranketagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(283, 'Wewatta', 0.00, 0, 1, 0.00, 0.00, 57),
(284, 'Wineethagama', 0.00, 0, 1, 0.00, 0.00, 57),
(285, 'Yalagamuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(286, 'Yalwela', 0.00, 0, 1, 0.00, 0.00, 57),
(287, 'Addalaichenai', 0.00, 0, 3, 0.00, 0.00, 57),
(288, 'Ampilanthurai', 0.00, 0, 3, 0.00, 0.00, 57),
(289, 'Araipattai', 0.00, 0, 3, 0.00, 0.00, 57),
(290, 'Ayithiyamalai', 0.00, 0, 3, 0.00, 0.00, 57),
(291, 'Bakiella', 0.00, 0, 3, 0.00, 0.00, 57),
(292, 'Batticaloa', 0.00, 0, 3, 0.00, 0.00, 57),
(293, 'Cheddipalayam', 0.00, 0, 3, 0.00, 0.00, 57),
(294, 'Chenkaladi', 0.00, 0, 3, 0.00, 0.00, 57),
(295, 'Eravur', 0.00, 0, 3, 0.00, 0.00, 57),
(296, 'Kaluwanchikudi', 0.00, 0, 3, 0.00, 0.00, 57),
(297, 'Kaluwankemy', 0.00, 0, 3, 0.00, 0.00, 57),
(298, 'Kannankudah', 0.00, 0, 3, 0.00, 0.00, 57),
(299, 'Karadiyanaru', 0.00, 0, 3, 0.00, 0.00, 57),
(300, 'Kathiraveli', 0.00, 0, 3, 0.00, 0.00, 57),
(301, 'Kattankudi', 0.00, 0, 3, 0.00, 0.00, 57),
(302, 'Kiran', 0.00, 0, 3, 0.00, 0.00, 57),
(303, 'Kirankulam', 0.00, 0, 3, 0.00, 0.00, 57),
(304, 'Koddaikallar', 0.00, 0, 3, 0.00, 0.00, 57),
(305, 'Kokkaddicholai', 0.00, 0, 3, 0.00, 0.00, 57),
(306, 'Kurukkalmadam', 0.00, 0, 3, 0.00, 0.00, 57),
(307, 'Mandur', 0.00, 0, 3, 0.00, 0.00, 57),
(308, 'Miravodai', 0.00, 0, 3, 0.00, 0.00, 57),
(309, 'Murakottanchanai', 0.00, 0, 3, 0.00, 0.00, 57),
(310, 'Navagirinagar', 0.00, 0, 3, 0.00, 0.00, 57),
(311, 'Navatkadu', 0.00, 0, 3, 0.00, 0.00, 57),
(312, 'Oddamavadi', 0.00, 0, 3, 0.00, 0.00, 57),
(313, 'Palamunai', 0.00, 0, 3, 0.00, 0.00, 57),
(314, 'Pankudavely', 0.00, 0, 3, 0.00, 0.00, 57),
(315, 'Periyaporativu', 0.00, 0, 3, 0.00, 0.00, 57),
(316, 'Periyapullumalai', 0.00, 0, 3, 0.00, 0.00, 57),
(317, 'Pillaiyaradi', 0.00, 0, 3, 0.00, 0.00, 57),
(318, 'Punanai', 0.00, 0, 3, 0.00, 0.00, 57),
(319, 'Thannamunai', 0.00, 0, 3, 0.00, 0.00, 57),
(320, 'Thettativu', 0.00, 0, 3, 0.00, 0.00, 57),
(321, 'Thikkodai', 0.00, 0, 3, 0.00, 0.00, 57),
(322, 'Thirupalugamam', 0.00, 0, 3, 0.00, 0.00, 57),
(323, 'Unnichchai', 0.00, 0, 3, 0.00, 0.00, 57),
(324, 'Vakaneri', 0.00, 0, 3, 0.00, 0.00, 57),
(325, 'Vakarai', 0.00, 0, 3, 0.00, 0.00, 57),
(326, 'Valaichenai', 0.00, 0, 3, 0.00, 0.00, 57),
(327, 'Vantharumoolai', 0.00, 0, 3, 0.00, 0.00, 57),
(328, 'Vellavely', 0.00, 0, 3, 0.00, 0.00, 57),
(329, 'Akarawita', 0.00, 0, 1, 0.00, 0.00, 57),
(330, 'Ambalangoda', 0.00, 0, 1, 0.00, 0.00, 57),
(331, 'Athurugiriya', 0.00, 0, 1, 0.00, 0.00, 57),
(332, 'Avissawella', 0.00, 0, 1, 0.00, 0.00, 57),
(333, 'Batawala', 0.00, 0, 1, 0.00, 0.00, 57),
(334, 'Battaramulla', 0.00, 0, 1, 0.00, 0.00, 57),
(335, 'Biyagama', 0.00, 0, 1, 0.00, 0.00, 57),
(336, 'Bope', 0.00, 0, 1, 0.00, 0.00, 57),
(337, 'Boralesgamuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(338, 'Colombo 8', 190.00, 0, 1, 50.00, 0.00, 57),
(339, 'Dedigamuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(340, 'Dehiwala', 0.00, 0, 1, 0.00, 0.00, 57),
(341, 'Deltara', 0.00, 0, 1, 0.00, 0.00, 57),
(342, 'Habarakada', 0.00, 0, 1, 0.00, 0.00, 57),
(343, 'Hanwella', 0.00, 0, 1, 0.00, 0.00, 57),
(344, 'Hiripitya', 0.00, 0, 1, 0.00, 0.00, 57),
(345, 'Hokandara', 0.00, 0, 1, 0.00, 0.00, 57),
(346, 'Homagama', 0.00, 0, 1, 0.00, 0.00, 57),
(347, 'Horagala', 0.00, 0, 1, 0.00, 0.00, 57),
(348, 'Kaduwela', 0.00, 0, 1, 0.00, 0.00, 57),
(349, 'Kaluaggala', 0.00, 0, 1, 0.00, 0.00, 57),
(350, 'Kapugoda', 0.00, 0, 1, 0.00, 0.00, 57),
(351, 'Kehelwatta', 0.00, 0, 1, 0.00, 0.00, 57),
(352, 'Kiriwattuduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(353, 'Kolonnawa', 0.00, 0, 1, 0.00, 0.00, 57),
(354, 'Kosgama', 0.00, 0, 1, 0.00, 0.00, 57),
(355, 'Madapatha', 0.00, 0, 1, 0.00, 0.00, 57),
(356, 'Maharagama', 0.00, 0, 1, 0.00, 0.00, 57),
(357, 'Malabe', 0.00, 0, 1, 0.00, 0.00, 57),
(358, 'Moratuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(359, 'Mount Lavinia', 0.00, 0, 1, 0.00, 0.00, 57),
(360, 'Mullegama', 0.00, 0, 1, 0.00, 0.00, 57),
(361, 'Napawela', 0.00, 0, 1, 0.00, 0.00, 57),
(362, 'Nugegoda', 0.00, 0, 1, 0.00, 0.00, 57),
(363, 'Padukka', 0.00, 0, 1, 0.00, 0.00, 57),
(364, 'Pannipitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(365, 'Piliyandala', 0.00, 0, 1, 0.00, 0.00, 57),
(366, 'Pitipana Homagama', 0.00, 0, 1, 0.00, 0.00, 57),
(367, 'Polgasowita', 0.00, 0, 1, 0.00, 0.00, 57),
(368, 'Pugoda', 0.00, 0, 1, 0.00, 0.00, 57),
(369, 'Ranala', 0.00, 0, 1, 0.00, 0.00, 57),
(370, 'Siddamulla', 0.00, 0, 1, 0.00, 0.00, 57),
(371, 'Siyambalagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(372, 'Sri Jayawardenepura', 0.00, 0, 1, 0.00, 0.00, 57),
(373, 'Talawatugoda', 0.00, 0, 1, 0.00, 0.00, 57),
(374, 'Tummodara', 0.00, 0, 1, 0.00, 0.00, 57),
(375, 'Waga', 0.00, 0, 1, 0.00, 0.00, 57),
(376, 'Colombo 6', 190.00, 0, 1, 50.00, 0.00, 57),
(377, 'Agaliya', 0.00, 0, 3, 0.00, 0.00, 57),
(378, 'Ahangama', 0.00, 0, 3, 0.00, 0.00, 57),
(379, 'Ahungalla', 0.00, 0, 3, 0.00, 0.00, 57),
(380, 'Akmeemana', 0.00, 0, 3, 0.00, 0.00, 57),
(381, 'Alawatugoda', 0.00, 0, 3, 0.00, 0.00, 57),
(382, 'Aluthwala', 0.00, 0, 3, 0.00, 0.00, 57),
(383, 'Ampegama', 0.00, 0, 3, 0.00, 0.00, 57),
(384, 'Amugoda', 0.00, 0, 3, 0.00, 0.00, 57),
(385, 'Anangoda', 0.00, 0, 3, 0.00, 0.00, 57),
(386, 'Angulugaha', 0.00, 0, 3, 0.00, 0.00, 57),
(387, 'Ankokkawala', 0.00, 0, 3, 0.00, 0.00, 57),
(388, 'Aselapura', 0.00, 0, 3, 0.00, 0.00, 57),
(389, 'Baddegama', 0.00, 0, 3, 0.00, 0.00, 57),
(390, 'Balapitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(391, 'Banagala', 0.00, 0, 3, 0.00, 0.00, 57),
(392, 'Batapola', 0.00, 0, 3, 0.00, 0.00, 57),
(393, 'Bentota', 0.00, 0, 3, 0.00, 0.00, 57),
(394, 'Boossa', 0.00, 0, 3, 0.00, 0.00, 57),
(395, 'Dellawa', 0.00, 0, 3, 0.00, 0.00, 57),
(396, 'Dikkumbura', 0.00, 0, 3, 0.00, 0.00, 57),
(397, 'Dodanduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(398, 'Ella Tanabaddegama', 0.00, 0, 3, 0.00, 0.00, 57),
(399, 'Elpitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(400, 'Galle', 0.00, 0, 3, 0.00, 0.00, 57),
(401, 'Ginimellagaha', 0.00, 0, 3, 0.00, 0.00, 57),
(402, 'Gintota', 0.00, 0, 3, 0.00, 0.00, 57),
(403, 'Godahena', 0.00, 0, 3, 0.00, 0.00, 57),
(404, 'Gonamulla Junction', 0.00, 0, 3, 0.00, 0.00, 57),
(405, 'Gonapinuwala', 0.00, 0, 3, 0.00, 0.00, 57),
(406, 'Habaraduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(407, 'Haburugala', 0.00, 0, 3, 0.00, 0.00, 57),
(408, 'Hikkaduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(409, 'Hiniduma', 0.00, 0, 3, 0.00, 0.00, 57),
(410, 'Hiyare', 0.00, 0, 3, 0.00, 0.00, 57),
(411, 'Kahaduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(412, 'Kahawa', 0.00, 0, 3, 0.00, 0.00, 57),
(413, 'Karagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(414, 'Karandeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(415, 'Kosgoda', 0.00, 0, 3, 0.00, 0.00, 57),
(416, 'Kottawagama', 0.00, 0, 3, 0.00, 0.00, 57),
(417, 'Kottegoda', 0.00, 0, 3, 0.00, 0.00, 57),
(418, 'Kuleegoda', 0.00, 0, 3, 0.00, 0.00, 57),
(419, 'Magedara', 0.00, 0, 3, 0.00, 0.00, 57),
(420, 'Mahawela Sinhapura', 0.00, 0, 3, 0.00, 0.00, 57),
(421, 'Mapalagama', 0.00, 0, 3, 0.00, 0.00, 57),
(422, 'Mapalagama Central', 0.00, 0, 3, 0.00, 0.00, 57),
(423, 'Mattaka', 0.00, 0, 3, 0.00, 0.00, 57),
(424, 'Meda-Keembiya', 0.00, 0, 3, 0.00, 0.00, 57),
(425, 'Meetiyagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(426, 'Nagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(427, 'Nakiyadeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(428, 'Nawandagala', 0.00, 0, 3, 0.00, 0.00, 57),
(429, 'Neluwa', 0.00, 0, 3, 0.00, 0.00, 57),
(430, 'Nindana', 0.00, 0, 3, 0.00, 0.00, 57),
(431, 'Pahala Millawa', 0.00, 0, 3, 0.00, 0.00, 57),
(432, 'Panangala', 0.00, 0, 3, 0.00, 0.00, 57),
(433, 'Pannimulla Panagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(434, 'Parana Thanayamgoda', 0.00, 0, 3, 0.00, 0.00, 57),
(435, 'Patana', 0.00, 0, 3, 0.00, 0.00, 57),
(436, 'Pitigala', 0.00, 0, 3, 0.00, 0.00, 57),
(437, 'Poddala', 0.00, 0, 3, 0.00, 0.00, 57),
(438, 'Polgampola', 0.00, 0, 3, 0.00, 0.00, 57),
(439, 'Porawagama', 0.00, 0, 3, 0.00, 0.00, 57),
(440, 'Rantotuwila', 0.00, 0, 3, 0.00, 0.00, 57),
(441, 'Talagampola', 0.00, 0, 3, 0.00, 0.00, 57),
(442, 'Talgaspe', 0.00, 0, 3, 0.00, 0.00, 57),
(443, 'Talpe', 0.00, 0, 3, 0.00, 0.00, 57),
(444, 'Tawalama', 0.00, 0, 3, 0.00, 0.00, 57),
(445, 'Tiranagama', 0.00, 0, 3, 0.00, 0.00, 57),
(446, 'Udalamatta', 0.00, 0, 3, 0.00, 0.00, 57),
(447, 'Udugama', 0.00, 0, 3, 0.00, 0.00, 57),
(448, 'Uluvitike', 0.00, 0, 3, 0.00, 0.00, 57),
(449, 'Unawatuna', 0.00, 0, 3, 0.00, 0.00, 57),
(450, 'Unawitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(451, 'Uragaha', 0.00, 0, 3, 0.00, 0.00, 57),
(452, 'Uragasmanhandiya', 0.00, 0, 3, 0.00, 0.00, 57),
(453, 'Wakwella', 0.00, 0, 3, 0.00, 0.00, 57),
(454, 'Walahanduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(455, 'Wanchawela', 0.00, 0, 3, 0.00, 0.00, 57),
(456, 'Wanduramba', 0.00, 0, 3, 0.00, 0.00, 57),
(457, 'Warukandeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(458, 'Watugedara', 0.00, 0, 3, 0.00, 0.00, 57),
(459, 'Weihena', 0.00, 0, 3, 0.00, 0.00, 57),
(460, 'Welikanda', 0.00, 0, 3, 0.00, 0.00, 57),
(461, 'Wilanagama', 0.00, 0, 3, 0.00, 0.00, 57),
(462, 'Yakkalamulla', 0.00, 0, 3, 0.00, 0.00, 57),
(463, 'Yatalamatta', 0.00, 0, 3, 0.00, 0.00, 57),
(464, 'Akaragama', 0.00, 0, 1, 0.00, 0.00, 57),
(465, 'Ambagaspitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(466, 'Ambepussa', 0.00, 0, 1, 0.00, 0.00, 57),
(467, 'Andiambalama', 0.00, 0, 1, 0.00, 0.00, 57),
(468, 'Attanagalla', 0.00, 0, 1, 0.00, 0.00, 57),
(469, 'Badalgama', 0.00, 0, 1, 0.00, 0.00, 57),
(470, 'Banduragoda', 0.00, 0, 1, 0.00, 0.00, 57),
(471, 'Batuwatta', 0.00, 0, 1, 0.00, 0.00, 57),
(472, 'Bemmulla', 0.00, 0, 1, 0.00, 0.00, 57),
(473, 'Biyagama IPZ', 0.00, 0, 1, 0.00, 0.00, 57),
(474, 'Bokalagama', 0.00, 0, 1, 0.00, 0.00, 57),
(475, 'Bollete (WP)', 0.00, 0, 1, 0.00, 0.00, 57),
(476, 'Bopagama', 0.00, 0, 1, 0.00, 0.00, 57),
(477, 'Buthpitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(478, 'Dagonna', 0.00, 0, 1, 0.00, 0.00, 57),
(479, 'Danowita', 0.00, 0, 1, 0.00, 0.00, 57),
(480, 'Debahera', 0.00, 0, 1, 0.00, 0.00, 57),
(481, 'Dekatana', 0.00, 0, 1, 0.00, 0.00, 57),
(482, 'Delgoda', 0.00, 0, 1, 0.00, 0.00, 57),
(483, 'Delwagura', 0.00, 0, 1, 0.00, 0.00, 57),
(484, 'Demalagama', 0.00, 0, 1, 0.00, 0.00, 57),
(485, 'Demanhandiya', 0.00, 0, 1, 0.00, 0.00, 57),
(486, 'Dewalapola', 0.00, 0, 1, 0.00, 0.00, 57),
(487, 'Divulapitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(488, 'Divuldeniya', 0.00, 0, 1, 0.00, 0.00, 57),
(489, 'Dompe', 0.00, 0, 1, 0.00, 0.00, 57),
(490, 'Dunagaha', 0.00, 0, 1, 0.00, 0.00, 57),
(491, 'Ekala', 0.00, 0, 1, 0.00, 0.00, 57),
(492, 'Ellakkala', 0.00, 0, 1, 0.00, 0.00, 57),
(493, 'Essella', 0.00, 0, 1, 0.00, 0.00, 57),
(494, 'Galedanda', 0.00, 0, 1, 0.00, 0.00, 57),
(495, 'Gampaha', 0.00, 0, 1, 0.00, 0.00, 57),
(496, 'Ganemulla', 0.00, 0, 1, 0.00, 0.00, 57),
(497, 'Giriulla', 0.00, 0, 1, 0.00, 0.00, 57),
(498, 'Gonawala', 0.00, 0, 1, 0.00, 0.00, 57),
(499, 'Halpe', 0.00, 0, 1, 0.00, 0.00, 57),
(500, 'Hapugastenna', 0.00, 0, 1, 0.00, 0.00, 57),
(501, 'Heiyanthuduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(502, 'Hinatiyana Madawala', 0.00, 0, 1, 0.00, 0.00, 57),
(503, 'Hiswella', 0.00, 0, 1, 0.00, 0.00, 57),
(504, 'Horampella', 0.00, 0, 1, 0.00, 0.00, 57),
(505, 'Hunumulla', 0.00, 0, 1, 0.00, 0.00, 57),
(506, 'Hunupola', 0.00, 0, 1, 0.00, 0.00, 57),
(507, 'Ihala Madampella', 0.00, 0, 1, 0.00, 0.00, 57),
(508, 'Imbulgoda', 0.00, 0, 1, 0.00, 0.00, 57),
(509, 'Ja-Ela', 0.00, 0, 1, 0.00, 0.00, 57),
(510, 'Kadawatha', 0.00, 0, 1, 0.00, 0.00, 57),
(511, 'Kahatowita', 0.00, 0, 1, 0.00, 0.00, 57),
(512, 'Kalagedihena', 0.00, 0, 1, 0.00, 0.00, 57),
(513, 'Kaleliya', 0.00, 0, 1, 0.00, 0.00, 57),
(514, 'Kandana', 0.00, 0, 1, 0.00, 0.00, 57),
(515, 'Katana', 0.00, 0, 1, 0.00, 0.00, 57),
(516, 'Katudeniya', 0.00, 0, 1, 0.00, 0.00, 57),
(517, 'Katunayake', 0.00, 0, 1, 0.00, 0.00, 57),
(518, 'Katunayake Air Force Camp', 0.00, 0, 1, 0.00, 0.00, 57),
(519, 'Katunayake(FTZ)', 0.00, 0, 1, 0.00, 0.00, 57),
(520, 'Katuwellegama', 0.00, 0, 1, 0.00, 0.00, 57),
(521, 'Kelaniya', 0.00, 0, 1, 0.00, 0.00, 57),
(522, 'Kimbulapitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(523, 'Kirindiwela', 0.00, 0, 1, 0.00, 0.00, 57),
(524, 'Kitalawalana', 0.00, 0, 1, 0.00, 0.00, 57),
(525, 'Kochchikade', 0.00, 0, 1, 0.00, 0.00, 57),
(526, 'Kotadeniyawa', 0.00, 0, 1, 0.00, 0.00, 57),
(527, 'Kotugoda', 0.00, 0, 1, 0.00, 0.00, 57),
(528, 'Kumbaloluwa', 0.00, 0, 1, 0.00, 0.00, 57),
(529, 'Loluwagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(530, 'Mabodale', 0.00, 0, 1, 0.00, 0.00, 57),
(531, 'Madelgamuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(532, 'Makewita', 0.00, 0, 1, 0.00, 0.00, 57),
(533, 'Makola', 0.00, 0, 1, 0.00, 0.00, 57),
(534, 'Malwana', 0.00, 0, 1, 0.00, 0.00, 57),
(535, 'Mandawala', 0.00, 0, 1, 0.00, 0.00, 57),
(536, 'Marandagahamula', 0.00, 0, 1, 0.00, 0.00, 57),
(537, 'Mellawagedara', 0.00, 0, 1, 0.00, 0.00, 57),
(538, 'Minuwangoda', 0.00, 0, 1, 0.00, 0.00, 57),
(539, 'Mirigama', 0.00, 0, 1, 0.00, 0.00, 57),
(540, 'Miriswatta', 0.00, 0, 1, 0.00, 0.00, 57),
(541, 'Mithirigala', 0.00, 0, 1, 0.00, 0.00, 57),
(542, 'Muddaragama', 0.00, 0, 1, 0.00, 0.00, 57),
(543, 'Mudungoda', 0.00, 0, 1, 0.00, 0.00, 57),
(544, 'Mulleriyawa New Town', 0.00, 0, 1, 0.00, 0.00, 57),
(545, 'Naranwala', 0.00, 0, 1, 0.00, 0.00, 57),
(546, 'Nawana', 0.00, 0, 1, 0.00, 0.00, 57),
(547, 'Nedungamuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(548, 'Negombo', 0.00, 0, 1, 0.00, 0.00, 57),
(549, 'Nikadalupotha', 0.00, 0, 1, 0.00, 0.00, 57),
(550, 'Nikahetikanda', 0.00, 0, 1, 0.00, 0.00, 57),
(551, 'Nittambuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(552, 'Niwandama', 0.00, 0, 1, 0.00, 0.00, 57),
(553, 'Opatha', 0.00, 0, 1, 0.00, 0.00, 57),
(554, 'Pamunugama', 0.00, 0, 1, 0.00, 0.00, 57),
(555, 'Pamunuwatta', 0.00, 0, 1, 0.00, 0.00, 57),
(556, 'Panawala', 0.00, 0, 1, 0.00, 0.00, 57),
(557, 'Pasyala', 0.00, 0, 1, 0.00, 0.00, 57),
(558, 'Peliyagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(559, 'Pepiliyawala', 0.00, 0, 1, 0.00, 0.00, 57),
(560, 'Pethiyagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(561, 'Polpithimukulana', 0.00, 0, 1, 0.00, 0.00, 57),
(562, 'Puwakpitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(563, 'Radawadunna', 0.00, 0, 1, 0.00, 0.00, 57),
(564, 'Radawana', 0.00, 0, 1, 0.00, 0.00, 57),
(565, 'Raddolugama', 0.00, 0, 1, 0.00, 0.00, 57),
(566, 'Ragama', 0.00, 0, 1, 0.00, 0.00, 57),
(567, 'Ruggahawila', 0.00, 0, 1, 0.00, 0.00, 57),
(568, 'Seeduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(569, 'Siyambalape', 0.00, 0, 1, 0.00, 0.00, 57),
(570, 'Talahena', 0.00, 0, 1, 0.00, 0.00, 57),
(571, 'Thambagalla', 0.00, 0, 1, 0.00, 0.00, 57),
(572, 'Thimbirigaskatuwa', 0.00, 0, 1, 0.00, 0.00, 57),
(573, 'Tittapattara', 0.00, 0, 1, 0.00, 0.00, 57),
(574, 'Udathuthiripitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(575, 'Udugampola', 0.00, 0, 1, 0.00, 0.00, 57),
(576, 'Uggalboda', 0.00, 0, 1, 0.00, 0.00, 57),
(577, 'Urapola', 0.00, 0, 1, 0.00, 0.00, 57),
(578, 'Uswetakeiyawa', 0.00, 0, 1, 0.00, 0.00, 57),
(579, 'Veyangoda', 0.00, 0, 1, 0.00, 0.00, 57),
(580, 'Walgammulla', 0.00, 0, 1, 0.00, 0.00, 57),
(581, 'Walpita', 0.00, 0, 1, 0.00, 0.00, 57),
(582, 'Walpola (WP)', 0.00, 0, 1, 0.00, 0.00, 57),
(583, 'Wathurugama', 0.00, 0, 1, 0.00, 0.00, 57),
(584, 'Watinapaha', 0.00, 0, 1, 0.00, 0.00, 57),
(585, 'Wattala', 0.00, 0, 1, 0.00, 0.00, 57),
(586, 'Weboda', 0.00, 0, 1, 0.00, 0.00, 57),
(587, 'Wegowwa', 0.00, 0, 1, 0.00, 0.00, 57),
(588, 'Weweldeniya', 0.00, 0, 1, 0.00, 0.00, 57),
(589, 'Yakkala', 0.00, 0, 1, 0.00, 0.00, 57),
(590, 'Yatiyana', 0.00, 0, 1, 0.00, 0.00, 57),
(591, 'Ambalantota', 0.00, 0, 3, 0.00, 0.00, 57),
(592, 'Angunakolapelessa', 0.00, 0, 3, 0.00, 0.00, 57),
(593, 'Angunakolawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(594, 'Bandagiriya Colony', 0.00, 0, 3, 0.00, 0.00, 57),
(595, 'Barawakumbuka', 0.00, 0, 3, 0.00, 0.00, 57),
(596, 'Beliatta', 0.00, 0, 3, 0.00, 0.00, 57),
(597, 'Beragama', 0.00, 0, 3, 0.00, 0.00, 57),
(598, 'Beralihela', 0.00, 0, 3, 0.00, 0.00, 57),
(599, 'Bundala', 0.00, 0, 3, 0.00, 0.00, 57),
(600, 'Ellagala', 0.00, 0, 3, 0.00, 0.00, 57),
(601, 'Gangulandeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(602, 'Getamanna', 0.00, 0, 3, 0.00, 0.00, 57),
(603, 'Goda Koggalla', 0.00, 0, 3, 0.00, 0.00, 57),
(604, 'Gonagamuwa Uduwila', 0.00, 0, 3, 0.00, 0.00, 57),
(605, 'Gonnoruwa', 0.00, 0, 3, 0.00, 0.00, 57),
(606, 'Hakuruwela', 0.00, 0, 3, 0.00, 0.00, 57),
(607, 'Hambantota', 0.00, 0, 3, 0.00, 0.00, 57),
(608, 'Handugala', 0.00, 0, 3, 0.00, 0.00, 57),
(609, 'Hungama', 0.00, 0, 3, 0.00, 0.00, 57),
(610, 'Ihala Beligalla', 0.00, 0, 3, 0.00, 0.00, 57),
(611, 'Iththa Demaliya', 0.00, 0, 3, 0.00, 0.00, 57),
(612, 'Julampitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(613, 'Kahandamodara', 0.00, 0, 3, 0.00, 0.00, 57),
(614, 'Kariyamaditta', 0.00, 0, 3, 0.00, 0.00, 57),
(615, 'Katuwana', 0.00, 0, 3, 0.00, 0.00, 57),
(616, 'Kawantissapura', 0.00, 0, 3, 0.00, 0.00, 57),
(617, 'Kirama', 0.00, 0, 3, 0.00, 0.00, 57),
(618, 'Kirinda', 0.00, 0, 3, 0.00, 0.00, 57),
(619, 'Lunama', 0.00, 0, 3, 0.00, 0.00, 57),
(620, 'Lunugamwehera', 0.00, 0, 3, 0.00, 0.00, 57),
(621, 'Magama', 0.00, 0, 3, 0.00, 0.00, 57),
(622, 'Mahagalwewa', 0.00, 0, 3, 0.00, 0.00, 57),
(623, 'Mamadala', 0.00, 0, 3, 0.00, 0.00, 57),
(624, 'Medamulana', 0.00, 0, 3, 0.00, 0.00, 57),
(625, 'Middeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(626, 'Meegahajandura', 0.00, 0, 3, 0.00, 0.00, 57),
(627, 'Modarawana', 0.00, 0, 3, 0.00, 0.00, 57),
(628, 'Mulkirigala', 0.00, 0, 3, 0.00, 0.00, 57),
(629, 'Nakulugamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(630, 'Netolpitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(631, 'Nihiluwa', 0.00, 0, 3, 0.00, 0.00, 57),
(632, 'Padawkema', 0.00, 0, 3, 0.00, 0.00, 57),
(633, 'Pahala Andarawewa', 0.00, 0, 3, 0.00, 0.00, 57),
(634, 'Rammalawarapitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(635, 'Ranakeliya', 0.00, 0, 3, 0.00, 0.00, 57),
(636, 'Ranmuduwewa', 0.00, 0, 3, 0.00, 0.00, 57),
(637, 'Ranna', 0.00, 0, 3, 0.00, 0.00, 57),
(638, 'Ratmalwala', 0.00, 0, 3, 0.00, 0.00, 57),
(639, 'Ruhunu Ridiyagama', 0.00, 0, 3, 0.00, 0.00, 57),
(640, 'Sooriyawewa Town', 0.00, 0, 3, 0.00, 0.00, 57),
(641, 'Tangalla', 0.00, 0, 3, 0.00, 0.00, 57),
(642, 'Tissamaharama', 0.00, 0, 3, 0.00, 0.00, 57),
(643, 'Uda Gomadiya', 0.00, 0, 3, 0.00, 0.00, 57),
(644, 'Udamattala', 0.00, 0, 3, 0.00, 0.00, 57),
(645, 'Uswewa', 0.00, 0, 3, 0.00, 0.00, 57),
(646, 'Vitharandeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(647, 'Walasmulla', 0.00, 0, 3, 0.00, 0.00, 57),
(648, 'Weeraketiya', 0.00, 0, 3, 0.00, 0.00, 57),
(649, 'Weerawila', 0.00, 0, 3, 0.00, 0.00, 57),
(650, 'Weerawila NewTown', 0.00, 0, 3, 0.00, 0.00, 57),
(651, 'Wekandawela', 0.00, 0, 3, 0.00, 0.00, 57),
(652, 'Weligatta', 0.00, 0, 3, 0.00, 0.00, 57),
(653, 'Yatigala', 0.00, 0, 3, 0.00, 0.00, 57),
(654, 'Jaffna', 0.00, 0, 9, 0.00, 0.00, 57),
(655, 'Agalawatta', 0.00, 0, 1, 0.00, 0.00, 57),
(656, 'Alubomulla', 0.00, 0, 1, 0.00, 0.00, 57),
(657, 'Anguruwatota', 0.00, 0, 1, 0.00, 0.00, 57),
(658, 'Atale', 0.00, 0, 1, 0.00, 0.00, 57),
(659, 'Baduraliya', 0.00, 0, 1, 0.00, 0.00, 57),
(660, 'Bandaragama', 0.00, 0, 1, 0.00, 0.00, 57),
(661, 'Batugampola', 0.00, 0, 1, 0.00, 0.00, 57),
(662, 'Bellana', 0.00, 0, 1, 0.00, 0.00, 57),
(663, 'Beruwala', 0.00, 0, 1, 0.00, 0.00, 57),
(664, 'Bolossagama', 0.00, 0, 1, 0.00, 0.00, 57),
(665, 'Bombuwala', 0.00, 0, 1, 0.00, 0.00, 57),
(666, 'Boralugoda', 0.00, 0, 1, 0.00, 0.00, 57),
(667, 'Bulathsinhala', 0.00, 0, 1, 0.00, 0.00, 57),
(668, 'Danawala Thiniyawala', 0.00, 0, 1, 0.00, 0.00, 57),
(669, 'Delmella', 0.00, 0, 1, 0.00, 0.00, 57),
(670, 'Dharga Town', 0.00, 0, 1, 0.00, 0.00, 57),
(671, 'Diwalakada', 0.00, 0, 1, 0.00, 0.00, 57),
(672, 'Dodangoda', 0.00, 0, 1, 0.00, 0.00, 57),
(673, 'Dombagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(674, 'Ethkandura', 0.00, 0, 1, 0.00, 0.00, 57),
(675, 'Galpatha', 0.00, 0, 1, 0.00, 0.00, 57),
(676, 'Gamagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(677, 'Gonagalpura', 0.00, 0, 1, 0.00, 0.00, 57),
(678, 'Gonapola Junction', 0.00, 0, 1, 0.00, 0.00, 57),
(679, 'Govinna', 0.00, 0, 1, 0.00, 0.00, 57),
(680, 'Gurulubadda', 0.00, 0, 1, 0.00, 0.00, 57),
(681, 'Halkandawila', 0.00, 0, 1, 0.00, 0.00, 57),
(682, 'Haltota', 0.00, 0, 1, 0.00, 0.00, 57),
(683, 'Halvitigala Colony', 0.00, 0, 1, 0.00, 0.00, 57),
(684, 'Halwala', 0.00, 0, 1, 0.00, 0.00, 57),
(685, 'Halwatura', 0.00, 0, 1, 0.00, 0.00, 57),
(686, 'Handapangoda', 0.00, 0, 1, 0.00, 0.00, 57),
(687, 'Hedigalla Colony', 0.00, 0, 1, 0.00, 0.00, 57),
(688, 'Henegama', 0.00, 0, 1, 0.00, 0.00, 57),
(689, 'Hettimulla', 0.00, 0, 1, 0.00, 0.00, 57),
(690, 'Horana', 0.00, 0, 1, 0.00, 0.00, 57),
(691, 'Ittapana', 0.00, 0, 1, 0.00, 0.00, 57),
(692, 'Kahawala', 0.00, 0, 1, 0.00, 0.00, 57),
(693, 'Kalawila Kiranthidiya', 0.00, 0, 1, 0.00, 0.00, 57),
(694, 'Kalutara', 0.00, 0, 1, 0.00, 0.00, 57),
(695, 'Kananwila', 0.00, 0, 1, 0.00, 0.00, 57),
(696, 'Kandanagama', 0.00, 0, 1, 0.00, 0.00, 57),
(697, 'Kelinkanda', 0.00, 0, 1, 0.00, 0.00, 57),
(698, 'Kitulgoda', 0.00, 0, 1, 0.00, 0.00, 57),
(699, 'Koholana', 0.00, 0, 1, 0.00, 0.00, 57),
(700, 'Kuda Uduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(701, 'Labbala', 0.00, 0, 1, 0.00, 0.00, 57),
(702, 'lhalahewessa', 0.00, 0, 1, 0.00, 0.00, 57),
(703, 'lnduruwa', 0.00, 0, 1, 0.00, 0.00, 57),
(704, 'lngiriya', 0.00, 0, 1, 0.00, 0.00, 57),
(705, 'Maggona', 0.00, 0, 1, 0.00, 0.00, 57),
(706, 'Mahagama', 0.00, 0, 1, 0.00, 0.00, 57),
(707, 'Mahakalupahana', 0.00, 0, 1, 0.00, 0.00, 57),
(708, 'Maharangalla', 0.00, 0, 1, 0.00, 0.00, 57),
(709, 'Malgalla Talangalla', 0.00, 0, 1, 0.00, 0.00, 57),
(710, 'Matugama', 0.00, 0, 1, 0.00, 0.00, 57),
(711, 'Meegahatenna', 0.00, 0, 1, 0.00, 0.00, 57),
(712, 'Meegama', 0.00, 0, 1, 0.00, 0.00, 57),
(713, 'Meegoda', 0.00, 0, 1, 0.00, 0.00, 57),
(714, 'Millaniya', 0.00, 0, 1, 0.00, 0.00, 57),
(715, 'Millewa', 0.00, 0, 1, 0.00, 0.00, 57),
(716, 'Miwanapalana', 0.00, 0, 1, 0.00, 0.00, 57),
(717, 'Molkawa', 0.00, 0, 1, 0.00, 0.00, 57),
(718, 'Morapitiya', 0.00, 0, 1, 0.00, 0.00, 57),
(719, 'Morontuduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(720, 'Nawattuduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(721, 'Neboda', 0.00, 0, 1, 0.00, 0.00, 57),
(722, 'Padagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(723, 'Pahalahewessa', 0.00, 0, 1, 0.00, 0.00, 57),
(724, 'Paiyagala', 0.00, 0, 1, 0.00, 0.00, 57),
(725, 'Panadura', 0.00, 0, 1, 0.00, 0.00, 57),
(726, 'Pannala', 0.00, 0, 1, 0.00, 0.00, 57),
(727, 'Paragastota', 0.00, 0, 1, 0.00, 0.00, 57),
(728, 'Paragoda', 0.00, 0, 1, 0.00, 0.00, 57),
(729, 'Paraigama', 0.00, 0, 1, 0.00, 0.00, 57),
(730, 'Pelanda', 0.00, 0, 1, 0.00, 0.00, 57),
(731, 'Pelawatta', 0.00, 0, 1, 0.00, 0.00, 57),
(732, 'Pimbura', 0.00, 0, 1, 0.00, 0.00, 57),
(733, 'Pitagaldeniya', 0.00, 0, 1, 0.00, 0.00, 57),
(734, 'Pokunuwita', 0.00, 0, 1, 0.00, 0.00, 57),
(735, 'Poruwedanda', 0.00, 0, 1, 0.00, 0.00, 57),
(736, 'Ratmale', 0.00, 0, 1, 0.00, 0.00, 57),
(737, 'Remunagoda', 0.00, 0, 1, 0.00, 0.00, 57),
(738, 'Talgaswela', 0.00, 0, 1, 0.00, 0.00, 57),
(739, 'Tebuwana', 0.00, 0, 1, 0.00, 0.00, 57),
(740, 'Uduwara', 0.00, 0, 1, 0.00, 0.00, 57),
(741, 'Utumgama', 0.00, 0, 1, 0.00, 0.00, 57),
(742, 'Veyangalla', 0.00, 0, 1, 0.00, 0.00, 57),
(743, 'Wadduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(744, 'Walagedara', 0.00, 0, 1, 0.00, 0.00, 57),
(745, 'Walallawita', 0.00, 0, 1, 0.00, 0.00, 57),
(746, 'Waskaduwa', 0.00, 0, 1, 0.00, 0.00, 57),
(747, 'Welipenna', 0.00, 0, 1, 0.00, 0.00, 57),
(748, 'Weliveriya', 0.00, 0, 1, 0.00, 0.00, 57),
(749, 'Welmilla Junction', 0.00, 0, 1, 0.00, 0.00, 57),
(750, 'Weragala', 0.00, 0, 1, 0.00, 0.00, 57),
(751, 'Yagirala', 0.00, 0, 1, 0.00, 0.00, 57),
(752, 'Yatadolawatta', 0.00, 0, 1, 0.00, 0.00, 57),
(753, 'Yatawara Junction', 0.00, 0, 1, 0.00, 0.00, 57),
(754, 'Aludeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(755, 'Ambagahapelessa', 0.00, 0, 2, 0.00, 0.00, 57),
(756, 'Ambagamuwa Udabulathgama', 0.00, 0, 2, 0.00, 0.00, 57),
(757, 'Ambatenna', 0.00, 0, 2, 0.00, 0.00, 57),
(758, 'Ampitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(759, 'Ankumbura', 0.00, 0, 2, 0.00, 0.00, 57),
(760, 'Atabage', 0.00, 0, 2, 0.00, 0.00, 57),
(761, 'Balana', 0.00, 0, 2, 0.00, 0.00, 57),
(762, 'Bambaragahaela', 0.00, 0, 2, 0.00, 0.00, 57),
(763, 'Batagolladeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(764, 'Batugoda', 0.00, 0, 2, 0.00, 0.00, 57),
(765, 'Batumulla', 0.00, 0, 2, 0.00, 0.00, 57),
(766, 'Bawlana', 0.00, 0, 2, 0.00, 0.00, 57),
(767, 'Bopana', 0.00, 0, 2, 0.00, 0.00, 57),
(768, 'Danture', 0.00, 0, 2, 0.00, 0.00, 57),
(769, 'Dedunupitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(770, 'Dekinda', 0.00, 0, 2, 0.00, 0.00, 57),
(771, 'Deltota', 0.00, 0, 2, 0.00, 0.00, 57),
(772, 'Divulankadawala', 0.00, 0, 2, 0.00, 0.00, 57),
(773, 'Dolapihilla', 0.00, 0, 2, 0.00, 0.00, 57),
(774, 'Dolosbage', 0.00, 0, 2, 0.00, 0.00, 57),
(775, 'Dunuwila', 0.00, 0, 2, 0.00, 0.00, 57),
(776, 'Etulgama', 0.00, 0, 2, 0.00, 0.00, 57),
(777, 'Galaboda', 0.00, 0, 2, 0.00, 0.00, 57),
(778, 'Galagedara', 0.00, 0, 2, 0.00, 0.00, 57),
(779, 'Galaha', 0.00, 0, 2, 0.00, 0.00, 57),
(780, 'Galhinna', 0.00, 0, 2, 0.00, 0.00, 57),
(781, 'Gampola', 0.00, 0, 2, 0.00, 0.00, 57),
(782, 'Gelioya', 0.00, 0, 2, 0.00, 0.00, 57),
(783, 'Godamunna', 0.00, 0, 2, 0.00, 0.00, 57),
(784, 'Gomagoda', 0.00, 0, 2, 0.00, 0.00, 57),
(785, 'Gonagantenna', 0.00, 0, 2, 0.00, 0.00, 57),
(786, 'Gonawalapatana', 0.00, 0, 2, 0.00, 0.00, 57),
(787, 'Gunnepana', 0.00, 0, 2, 0.00, 0.00, 57),
(788, 'Gurudeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(789, 'Hakmana', 0.00, 0, 2, 0.00, 0.00, 57),
(790, 'Handaganawa', 0.00, 0, 2, 0.00, 0.00, 57),
(791, 'Handawalapitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(792, 'Handessa', 0.00, 0, 2, 0.00, 0.00, 57),
(793, 'Hanguranketha', 0.00, 0, 2, 0.00, 0.00, 57),
(794, 'Harangalagama', 0.00, 0, 2, 0.00, 0.00, 57),
(795, 'Hataraliyadda', 0.00, 0, 2, 0.00, 0.00, 57),
(796, 'Hindagala', 0.00, 0, 2, 0.00, 0.00, 57),
(797, 'Hondiyadeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(798, 'Hunnasgiriya', 0.00, 0, 2, 0.00, 0.00, 57),
(799, 'Inguruwatta', 0.00, 0, 2, 0.00, 0.00, 57),
(800, 'Jambugahapitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(801, 'Kadugannawa', 0.00, 0, 2, 0.00, 0.00, 57),
(802, 'Kahataliyadda', 0.00, 0, 2, 0.00, 0.00, 57),
(803, 'Kalugala', 0.00, 0, 2, 0.00, 0.00, 57),
(804, 'Kandy', 0.00, 0, 2, 0.00, 0.00, 57),
(805, 'Kapuliyadde', 0.00, 0, 2, 0.00, 0.00, 57),
(806, 'Katugastota', 0.00, 0, 2, 0.00, 0.00, 57),
(807, 'Katukitula', 0.00, 0, 2, 0.00, 0.00, 57),
(808, 'Kelanigama', 0.00, 0, 2, 0.00, 0.00, 57),
(809, 'Kengalla', 0.00, 0, 2, 0.00, 0.00, 57),
(810, 'Ketaboola', 0.00, 0, 2, 0.00, 0.00, 57),
(811, 'Ketakumbura', 0.00, 0, 2, 0.00, 0.00, 57),
(812, 'Kobonila', 0.00, 0, 2, 0.00, 0.00, 57),
(813, 'Kolabissa', 0.00, 0, 2, 0.00, 0.00, 57),
(814, 'Kolongoda', 0.00, 0, 2, 0.00, 0.00, 57),
(815, 'Kulugammana', 0.00, 0, 2, 0.00, 0.00, 57),
(816, 'Kumbukkandura', 0.00, 0, 2, 0.00, 0.00, 57),
(817, 'Kumburegama', 0.00, 0, 2, 0.00, 0.00, 57),
(818, 'Kundasale', 0.00, 0, 2, 0.00, 0.00, 57),
(819, 'Leemagahakotuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(820, 'lhala Kobbekaduwa', 0.00, 0, 2, 0.00, 0.00, 57),
(821, 'Lunugama', 0.00, 0, 2, 0.00, 0.00, 57),
(822, 'Lunuketiya Maditta', 0.00, 0, 2, 0.00, 0.00, 57),
(823, 'Madawala Bazaar', 0.00, 0, 2, 0.00, 0.00, 57),
(824, 'Madawalalanda', 0.00, 0, 2, 0.00, 0.00, 57),
(825, 'Madugalla', 0.00, 0, 2, 0.00, 0.00, 57),
(826, 'Madulkele', 0.00, 0, 2, 0.00, 0.00, 57),
(827, 'Mahadoraliyadda', 0.00, 0, 2, 0.00, 0.00, 57),
(828, 'Mahamedagama', 0.00, 0, 2, 0.00, 0.00, 57),
(829, 'Mahanagapura', 0.00, 0, 2, 0.00, 0.00, 57),
(830, 'Mailapitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(831, 'Makkanigama', 0.00, 0, 2, 0.00, 0.00, 57),
(832, 'Makuldeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(833, 'Mangalagama', 0.00, 0, 2, 0.00, 0.00, 57),
(834, 'Mapakanda', 0.00, 0, 2, 0.00, 0.00, 57),
(835, 'Marassana', 0.00, 0, 2, 0.00, 0.00, 57),
(836, 'Marymount Colony', 0.00, 0, 2, 0.00, 0.00, 57),
(837, 'Mawatura', 0.00, 0, 2, 0.00, 0.00, 57),
(838, 'Medamahanuwara', 0.00, 0, 2, 0.00, 0.00, 57),
(839, 'Medawala Harispattuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(840, 'Meetalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(841, 'Megoda Kalugamuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(842, 'Menikdiwela', 0.00, 0, 2, 0.00, 0.00, 57),
(843, 'Menikhinna', 0.00, 0, 2, 0.00, 0.00, 57),
(844, 'Mimure', 0.00, 0, 2, 0.00, 0.00, 57),
(845, 'Minigamuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(846, 'Minipe', 0.00, 0, 2, 0.00, 0.00, 57),
(847, 'Moragahapallama', 0.00, 0, 2, 0.00, 0.00, 57),
(848, 'Murutalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(849, 'Muruthagahamulla', 0.00, 0, 2, 0.00, 0.00, 57),
(850, 'Nanuoya', 0.00, 0, 2, 0.00, 0.00, 57),
(851, 'Naranpanawa', 0.00, 0, 2, 0.00, 0.00, 57),
(852, 'Narawelpita', 0.00, 0, 2, 0.00, 0.00, 57),
(853, 'Nawalapitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(854, 'Nawathispane', 0.00, 0, 2, 0.00, 0.00, 57),
(855, 'Nillambe', 0.00, 0, 2, 0.00, 0.00, 57),
(856, 'Nugaliyadda', 0.00, 0, 2, 0.00, 0.00, 57),
(857, 'Ovilikanda', 0.00, 0, 2, 0.00, 0.00, 57),
(858, 'Pallekotuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(859, 'Panwilatenna', 0.00, 0, 2, 0.00, 0.00, 57),
(860, 'Paradeka', 0.00, 0, 2, 0.00, 0.00, 57),
(861, 'Pasbage', 0.00, 0, 2, 0.00, 0.00, 57),
(862, 'Pattitalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(863, 'Peradeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(864, 'Pilimatalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(865, 'Poholiyadda', 0.00, 0, 2, 0.00, 0.00, 57),
(866, 'Pubbiliya', 0.00, 0, 2, 0.00, 0.00, 57),
(867, 'Pupuressa', 0.00, 0, 2, 0.00, 0.00, 57),
(868, 'Pussellawa', 0.00, 0, 2, 0.00, 0.00, 57),
(869, 'Putuhapuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(870, 'Rajawella', 0.00, 0, 2, 0.00, 0.00, 57),
(871, 'Rambukpitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(872, 'Rambukwella', 0.00, 0, 2, 0.00, 0.00, 57),
(873, 'Rangala', 0.00, 0, 2, 0.00, 0.00, 57),
(874, 'Rantembe', 0.00, 0, 2, 0.00, 0.00, 57),
(875, 'Sangarajapura', 0.00, 0, 2, 0.00, 0.00, 57),
(876, 'Senarathwela', 0.00, 0, 2, 0.00, 0.00, 57),
(877, 'Talatuoya', 0.00, 0, 2, 0.00, 0.00, 57),
(878, 'Teldeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(879, 'Tennekumbura', 0.00, 0, 2, 0.00, 0.00, 57),
(880, 'Uda Peradeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(881, 'Udahentenna', 0.00, 0, 2, 0.00, 0.00, 57),
(882, 'Udatalawinna', 0.00, 0, 2, 0.00, 0.00, 57),
(883, 'Udispattuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(884, 'Ududumbara', 0.00, 0, 2, 0.00, 0.00, 57),
(885, 'Uduwahinna', 0.00, 0, 2, 0.00, 0.00, 57),
(886, 'Uduwela', 0.00, 0, 2, 0.00, 0.00, 57),
(887, 'Ulapane', 0.00, 0, 2, 0.00, 0.00, 57),
(888, 'Unuwinna', 0.00, 0, 2, 0.00, 0.00, 57),
(889, 'Velamboda', 0.00, 0, 2, 0.00, 0.00, 57),
(890, 'Watagoda', 0.00, 0, 2, 0.00, 0.00, 57),
(891, 'Watagoda Harispattuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(892, 'Wattappola', 0.00, 0, 2, 0.00, 0.00, 57),
(893, 'Weligampola', 0.00, 0, 2, 0.00, 0.00, 57),
(894, 'Wendaruwa', 0.00, 0, 2, 0.00, 0.00, 57),
(895, 'Weragantota', 0.00, 0, 2, 0.00, 0.00, 57),
(896, 'Werapitya', 0.00, 0, 2, 0.00, 0.00, 57),
(897, 'Werellagama', 0.00, 0, 2, 0.00, 0.00, 57),
(898, 'Wettawa', 0.00, 0, 2, 0.00, 0.00, 57),
(899, 'Yahalatenna', 0.00, 0, 2, 0.00, 0.00, 57),
(900, 'Yatihalagala', 0.00, 0, 2, 0.00, 0.00, 57),
(901, 'Alawala', 0.00, 0, 5, 0.00, 0.00, 57),
(902, 'Alawatura', 0.00, 0, 5, 0.00, 0.00, 57),
(903, 'Alawwa', 0.00, 0, 5, 0.00, 0.00, 57),
(904, 'Algama', 0.00, 0, 5, 0.00, 0.00, 57),
(905, 'Alutnuwara', 0.00, 0, 5, 0.00, 0.00, 57),
(906, 'Ambalakanda', 0.00, 0, 5, 0.00, 0.00, 57),
(907, 'Ambulugala', 0.00, 0, 5, 0.00, 0.00, 57),
(908, 'Amitirigala', 0.00, 0, 5, 0.00, 0.00, 57),
(909, 'Ampagala', 0.00, 0, 5, 0.00, 0.00, 57),
(910, 'Anhandiya', 0.00, 0, 5, 0.00, 0.00, 57),
(911, 'Anhettigama', 0.00, 0, 5, 0.00, 0.00, 57),
(912, 'Aranayaka', 0.00, 0, 5, 0.00, 0.00, 57),
(913, 'Aruggammana', 0.00, 0, 5, 0.00, 0.00, 57),
(914, 'Batuwita', 0.00, 0, 5, 0.00, 0.00, 57),
(915, 'Beligala(Sab)', 0.00, 0, 5, 0.00, 0.00, 57),
(916, 'Belihuloya', 0.00, 0, 5, 0.00, 0.00, 57),
(917, 'Berannawa', 0.00, 0, 5, 0.00, 0.00, 57),
(918, 'Bopitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(919, 'Bopitiya (SAB)', 0.00, 0, 5, 0.00, 0.00, 57),
(920, 'Boralankada', 0.00, 0, 5, 0.00, 0.00, 57),
(921, 'Bossella', 0.00, 0, 5, 0.00, 0.00, 57),
(922, 'Bulathkohupitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(923, 'Damunupola', 0.00, 0, 5, 0.00, 0.00, 57),
(924, 'Debathgama', 0.00, 0, 5, 0.00, 0.00, 57),
(925, 'Dedugala', 0.00, 0, 5, 0.00, 0.00, 57),
(926, 'Deewala Pallegama', 0.00, 0, 5, 0.00, 0.00, 57),
(927, 'Dehiowita', 0.00, 0, 5, 0.00, 0.00, 57),
(928, 'Deldeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(929, 'Deloluwa', 0.00, 0, 5, 0.00, 0.00, 57),
(930, 'Deraniyagala', 0.00, 0, 5, 0.00, 0.00, 57),
(931, 'Dewalegama', 0.00, 0, 5, 0.00, 0.00, 57),
(932, 'Dewanagala', 0.00, 0, 5, 0.00, 0.00, 57),
(933, 'Dombemada', 0.00, 0, 5, 0.00, 0.00, 57),
(934, 'Dorawaka', 0.00, 0, 5, 0.00, 0.00, 57),
(935, 'Dunumala', 0.00, 0, 5, 0.00, 0.00, 57),
(936, 'Galapitamada', 0.00, 0, 5, 0.00, 0.00, 57),
(937, 'Galatara', 0.00, 0, 5, 0.00, 0.00, 57),
(938, 'Galigamuwa Town', 0.00, 0, 5, 0.00, 0.00, 57),
(939, 'Gallella', 0.00, 0, 5, 0.00, 0.00, 57),
(940, 'Galpatha(Sab)', 0.00, 0, 5, 0.00, 0.00, 57),
(941, 'Gantuna', 0.00, 0, 5, 0.00, 0.00, 57),
(942, 'Getahetta', 0.00, 0, 5, 0.00, 0.00, 57),
(943, 'Godagampola', 0.00, 0, 5, 0.00, 0.00, 57),
(944, 'Gonagala', 0.00, 0, 5, 0.00, 0.00, 57),
(945, 'Hakahinna', 0.00, 0, 5, 0.00, 0.00, 57),
(946, 'Hakbellawaka', 0.00, 0, 5, 0.00, 0.00, 57),
(947, 'Halloluwa', 0.00, 0, 5, 0.00, 0.00, 57),
(948, 'Hedunuwewa', 0.00, 0, 5, 0.00, 0.00, 57),
(949, 'Hemmatagama', 0.00, 0, 5, 0.00, 0.00, 57),
(950, 'Hewadiwela', 0.00, 0, 5, 0.00, 0.00, 57),
(951, 'Hingula', 0.00, 0, 5, 0.00, 0.00, 57),
(952, 'Hinguralakanda', 0.00, 0, 5, 0.00, 0.00, 57),
(953, 'Hingurana', 0.00, 0, 5, 0.00, 0.00, 57),
(954, 'Hiriwadunna', 0.00, 0, 5, 0.00, 0.00, 57),
(955, 'Ihala Walpola', 0.00, 0, 5, 0.00, 0.00, 57),
(956, 'Ihalagama', 0.00, 0, 5, 0.00, 0.00, 57),
(957, 'Imbulana', 0.00, 0, 5, 0.00, 0.00, 57),
(958, 'Imbulgasdeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(959, 'Kabagamuwa', 0.00, 0, 5, 0.00, 0.00, 57),
(960, 'Kahapathwala', 0.00, 0, 5, 0.00, 0.00, 57),
(961, 'Kandaketya', 0.00, 0, 5, 0.00, 0.00, 57),
(962, 'Kannattota', 0.00, 0, 5, 0.00, 0.00, 57),
(963, 'Karagahinna', 0.00, 0, 5, 0.00, 0.00, 57),
(964, 'Kegalle', 0.00, 0, 5, 0.00, 0.00, 57),
(965, 'Kehelpannala', 0.00, 0, 5, 0.00, 0.00, 57),
(966, 'Ketawala Leula', 0.00, 0, 5, 0.00, 0.00, 57),
(967, 'Kitulgala', 0.00, 0, 5, 0.00, 0.00, 57),
(968, 'Kondeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(969, 'Kotiyakumbura', 0.00, 0, 5, 0.00, 0.00, 57),
(970, 'Lewangama', 0.00, 0, 5, 0.00, 0.00, 57),
(971, 'Mahabage', 0.00, 0, 5, 0.00, 0.00, 57),
(972, 'Makehelwala', 0.00, 0, 5, 0.00, 0.00, 57),
(973, 'Malalpola', 0.00, 0, 5, 0.00, 0.00, 57),
(974, 'Maldeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(975, 'Maliboda', 0.00, 0, 5, 0.00, 0.00, 57),
(976, 'Maliyadda', 0.00, 0, 5, 0.00, 0.00, 57),
(977, 'Malmaduwa', 0.00, 0, 5, 0.00, 0.00, 57),
(978, 'Marapana', 0.00, 0, 5, 0.00, 0.00, 57),
(979, 'Mawanella', 0.00, 0, 5, 0.00, 0.00, 57),
(980, 'Meetanwala', 0.00, 0, 5, 0.00, 0.00, 57),
(981, 'Migastenna Sabara', 0.00, 0, 5, 0.00, 0.00, 57),
(982, 'Miyanawita', 0.00, 0, 5, 0.00, 0.00, 57),
(983, 'Molagoda', 0.00, 0, 5, 0.00, 0.00, 57),
(984, 'Morontota', 0.00, 0, 5, 0.00, 0.00, 57),
(985, 'Narangala', 0.00, 0, 5, 0.00, 0.00, 57),
(986, 'Narangoda', 0.00, 0, 5, 0.00, 0.00, 57),
(987, 'Nattarampotha', 0.00, 0, 5, 0.00, 0.00, 57),
(988, 'Nelundeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(989, 'Niyadurupola', 0.00, 0, 5, 0.00, 0.00, 57),
(990, 'Noori', 0.00, 0, 5, 0.00, 0.00, 57),
(991, 'Pannila', 0.00, 0, 5, 0.00, 0.00, 57),
(992, 'Pattampitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(993, 'Pilawala', 0.00, 0, 5, 0.00, 0.00, 57),
(994, 'Pothukoladeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(995, 'Puswelitenna', 0.00, 0, 5, 0.00, 0.00, 57),
(996, 'Rambukkana', 0.00, 0, 5, 0.00, 0.00, 57),
(997, 'Rilpola', 0.00, 0, 5, 0.00, 0.00, 57),
(998, 'Rukmale', 0.00, 0, 5, 0.00, 0.00, 57),
(999, 'Ruwanwella', 0.00, 0, 5, 0.00, 0.00, 57),
(1000, 'Samanalawewa', 0.00, 0, 5, 0.00, 0.00, 57),
(1001, 'Seaforth Colony', 0.00, 0, 5, 0.00, 0.00, 57),
(1002, 'Colombo 2', 190.00, 0, 1, 50.00, 0.00, 57),
(1003, 'Spring Valley', 0.00, 0, 5, 0.00, 0.00, 57),
(1004, 'Talgaspitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(1005, 'Teligama', 0.00, 0, 5, 0.00, 0.00, 57),
(1006, 'Tholangamuwa', 0.00, 0, 5, 0.00, 0.00, 57),
(1007, 'Thotawella', 0.00, 0, 5, 0.00, 0.00, 57),
(1008, 'Udaha Hawupe', 0.00, 0, 5, 0.00, 0.00, 57),
(1009, 'Udapotha', 0.00, 0, 5, 0.00, 0.00, 57),
(1010, 'Uduwa', 0.00, 0, 5, 0.00, 0.00, 57),
(1011, 'Undugoda', 0.00, 0, 5, 0.00, 0.00, 57),
(1012, 'Ussapitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(1013, 'Wahakula', 0.00, 0, 5, 0.00, 0.00, 57),
(1014, 'Waharaka', 0.00, 0, 5, 0.00, 0.00, 57),
(1015, 'Wanaluwewa', 0.00, 0, 5, 0.00, 0.00, 57),
(1016, 'Warakapola', 0.00, 0, 5, 0.00, 0.00, 57),
(1017, 'Watura', 0.00, 0, 5, 0.00, 0.00, 57),
(1018, 'Weeoya', 0.00, 0, 5, 0.00, 0.00, 57),
(1019, 'Wegalla', 0.00, 0, 5, 0.00, 0.00, 57),
(1020, 'Weligalla', 0.00, 0, 5, 0.00, 0.00, 57),
(1021, 'Welihelatenna', 0.00, 0, 5, 0.00, 0.00, 57),
(1022, 'Wewelwatta', 0.00, 0, 5, 0.00, 0.00, 57),
(1023, 'Yatagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1024, 'Yatapana', 0.00, 0, 5, 0.00, 0.00, 57),
(1025, 'Yatiyantota', 0.00, 0, 5, 0.00, 0.00, 57),
(1026, 'Yattogoda', 0.00, 0, 5, 0.00, 0.00, 57),
(1027, 'Kandavalai', 0.00, 0, 9, 0.00, 0.00, 57),
(1028, 'Karachchi', 0.00, 0, 9, 0.00, 0.00, 57),
(1029, 'Kilinochchi', 0.00, 0, 9, 0.00, 0.00, 57),
(1030, 'Pachchilaipalli', 0.00, 0, 9, 0.00, 0.00, 57),
(1031, 'Poonakary', 0.00, 0, 9, 0.00, 0.00, 57),
(1032, 'Akurana', 0.00, 0, 2, 0.00, 0.00, 57),
(1033, 'Alahengama', 0.00, 0, 4, 0.00, 0.00, 57),
(1034, 'Alahitiyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1035, 'Ambakote', 0.00, 0, 4, 0.00, 0.00, 57),
(1036, 'Ambanpola', 0.00, 0, 4, 0.00, 0.00, 57),
(1037, 'Andiyagala', 0.00, 0, 4, 0.00, 0.00, 57),
(1038, 'Anukkane', 0.00, 0, 4, 0.00, 0.00, 57),
(1039, 'Aragoda', 0.00, 0, 4, 0.00, 0.00, 57),
(1040, 'Ataragalla', 0.00, 0, 4, 0.00, 0.00, 57),
(1041, 'Awulegama', 0.00, 0, 4, 0.00, 0.00, 57),
(1042, 'Balalla', 0.00, 0, 4, 0.00, 0.00, 57),
(1043, 'Bamunukotuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1044, 'Bandara Koswatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1045, 'Bingiriya', 0.00, 0, 4, 0.00, 0.00, 57),
(1046, 'Bogamulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1047, 'Boraluwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1048, 'Boyagane', 0.00, 0, 4, 0.00, 0.00, 57),
(1049, 'Bujjomuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1050, 'Buluwala', 0.00, 0, 4, 0.00, 0.00, 57),
(1051, 'Dadayamtalawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1052, 'Dambadeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1053, 'Daraluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1054, 'Deegalla', 0.00, 0, 4, 0.00, 0.00, 57);
INSERT INTO `city_master` (`id`, `city`, `rate`, `flag`, `area`, `perKgRate`, `SetRateMinOrder`, `countryId`) VALUES
(1055, 'Demataluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1056, 'Demuwatha', 0.00, 0, 4, 0.00, 0.00, 57),
(1057, 'Diddeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1058, 'Digannewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1059, 'Divullegoda', 0.00, 0, 4, 0.00, 0.00, 57),
(1060, 'Diyasenpura', 0.00, 0, 4, 0.00, 0.00, 57),
(1061, 'Dodangaslanda', 0.00, 0, 4, 0.00, 0.00, 57),
(1062, 'Doluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1063, 'Doragamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1064, 'Doratiyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1065, 'Dunumadalawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1066, 'Dunuwilapitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1067, 'Ehetuwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1068, 'Elibichchiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1069, 'Embogama', 0.00, 0, 4, 0.00, 0.00, 57),
(1070, 'Etungahakotuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1071, 'Galadivulwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1072, 'Galgamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1073, 'Gallellagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1074, 'Gallewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1075, 'Ganegoda', 0.00, 0, 4, 0.00, 0.00, 57),
(1076, 'Girathalana', 0.00, 0, 4, 0.00, 0.00, 57),
(1077, 'Gokaralla', 0.00, 0, 4, 0.00, 0.00, 57),
(1078, 'Gonawila', 0.00, 0, 4, 0.00, 0.00, 57),
(1079, 'Halmillawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1080, 'Handungamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1081, 'Harankahawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1082, 'Helamada', 0.00, 0, 4, 0.00, 0.00, 57),
(1083, 'Hengamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1084, 'Hettipola', 0.00, 0, 4, 0.00, 0.00, 57),
(1085, 'Hewainna', 0.00, 0, 4, 0.00, 0.00, 57),
(1086, 'Hilogama', 0.00, 0, 4, 0.00, 0.00, 57),
(1087, 'Hindagolla', 0.00, 0, 4, 0.00, 0.00, 57),
(1088, 'Hiriyala Lenawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1089, 'Hiruwalpola', 0.00, 0, 4, 0.00, 0.00, 57),
(1090, 'Horambawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1091, 'Hulogedara', 0.00, 0, 4, 0.00, 0.00, 57),
(1092, 'Hulugalla', 0.00, 0, 4, 0.00, 0.00, 57),
(1093, 'Ihala Gomugomuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1094, 'Ihala Katugampala', 0.00, 0, 4, 0.00, 0.00, 57),
(1095, 'Indulgodakanda', 0.00, 0, 4, 0.00, 0.00, 57),
(1096, 'Ithanawatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1097, 'Kadigawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1098, 'Kalankuttiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1099, 'Kalatuwawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1100, 'Kalugamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1101, 'Kanadeniyawala', 0.00, 0, 4, 0.00, 0.00, 57),
(1102, 'Kanattewewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1103, 'Kandegedara', 0.00, 0, 4, 0.00, 0.00, 57),
(1104, 'Karagahagedara', 0.00, 0, 4, 0.00, 0.00, 57),
(1105, 'Karambe', 0.00, 0, 4, 0.00, 0.00, 57),
(1106, 'Katiyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1107, 'Katupota', 0.00, 0, 4, 0.00, 0.00, 57),
(1108, 'Kawudulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1109, 'Kawuduluwewa Stagell', 0.00, 0, 4, 0.00, 0.00, 57),
(1110, 'Kekunagolla', 0.00, 0, 4, 0.00, 0.00, 57),
(1111, 'Keppitiwalana', 0.00, 0, 4, 0.00, 0.00, 57),
(1112, 'Kimbulwanaoya', 0.00, 0, 4, 0.00, 0.00, 57),
(1113, 'Kirimetiyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1114, 'Kirindawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1115, 'Kirindigalla', 0.00, 0, 4, 0.00, 0.00, 57),
(1116, 'Kithalawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1117, 'Kitulwala', 0.00, 0, 4, 0.00, 0.00, 57),
(1118, 'Kobeigane', 0.00, 0, 4, 0.00, 0.00, 57),
(1119, 'Kohilagedara', 0.00, 0, 4, 0.00, 0.00, 57),
(1120, 'Konwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1121, 'Kosdeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1122, 'Kosgolla', 0.00, 0, 4, 0.00, 0.00, 57),
(1123, 'Kotagala', 0.00, 0, 4, 0.00, 0.00, 57),
(1124, 'Colombo 13', 190.00, 0, 1, 50.00, 0.00, 57),
(1125, 'Kotawehera', 0.00, 0, 4, 0.00, 0.00, 57),
(1126, 'Kudagalgamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1127, 'Kudakatnoruwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1128, 'Kuliyapitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1129, 'Kumaragama', 0.00, 0, 4, 0.00, 0.00, 57),
(1130, 'Kumbukgeta', 0.00, 0, 4, 0.00, 0.00, 57),
(1131, 'Kumbukwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1132, 'Kuratihena', 0.00, 0, 4, 0.00, 0.00, 57),
(1133, 'Kurunegala', 0.00, 0, 4, 0.00, 0.00, 57),
(1134, 'lbbagamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1135, 'lhala Kadigamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1136, 'Lihiriyagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1137, 'lllagolla', 0.00, 0, 4, 0.00, 0.00, 57),
(1138, 'llukhena', 0.00, 0, 4, 0.00, 0.00, 57),
(1139, 'Lonahettiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1140, 'Madahapola', 0.00, 0, 4, 0.00, 0.00, 57),
(1141, 'Madakumburumulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1142, 'Madalagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1143, 'Madawala Ulpotha', 0.00, 0, 4, 0.00, 0.00, 57),
(1144, 'Maduragoda', 0.00, 0, 4, 0.00, 0.00, 57),
(1145, 'Maeliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1146, 'Magulagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1147, 'Maha Ambagaswewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1148, 'Mahagalkadawala', 0.00, 0, 4, 0.00, 0.00, 57),
(1149, 'Mahagirilla', 0.00, 0, 4, 0.00, 0.00, 57),
(1150, 'Mahamukalanyaya', 0.00, 0, 4, 0.00, 0.00, 57),
(1151, 'Mahananneriya', 0.00, 0, 4, 0.00, 0.00, 57),
(1152, 'Mahapallegama', 0.00, 0, 4, 0.00, 0.00, 57),
(1153, 'Maharachchimulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1154, 'Mahatalakolawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1155, 'Mahawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1156, 'Maho', 0.00, 0, 4, 0.00, 0.00, 57),
(1157, 'Makulewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1158, 'Makulpotha', 0.00, 0, 4, 0.00, 0.00, 57),
(1159, 'Makulwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1160, 'Malagane', 0.00, 0, 4, 0.00, 0.00, 57),
(1161, 'Mandapola', 0.00, 0, 4, 0.00, 0.00, 57),
(1162, 'Maspotha', 0.00, 0, 4, 0.00, 0.00, 57),
(1163, 'Mawathagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1164, 'Medirigiriya', 0.00, 0, 4, 0.00, 0.00, 57),
(1165, 'Medivawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1166, 'Meegalawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1167, 'Meegaswewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1168, 'Meewellawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1169, 'Melsiripura', 0.00, 0, 4, 0.00, 0.00, 57),
(1170, 'Metikumbura', 0.00, 0, 4, 0.00, 0.00, 57),
(1171, 'Metiyagane', 0.00, 0, 4, 0.00, 0.00, 57),
(1172, 'Minhettiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1173, 'Minuwangete', 0.00, 0, 4, 0.00, 0.00, 57),
(1174, 'Mirihanagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1175, 'Monnekulama', 0.00, 0, 4, 0.00, 0.00, 57),
(1176, 'Moragane', 0.00, 0, 4, 0.00, 0.00, 57),
(1177, 'Moragollagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1178, 'Morathiha', 0.00, 0, 4, 0.00, 0.00, 57),
(1179, 'Munamaldeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1180, 'Muruthenge', 0.00, 0, 4, 0.00, 0.00, 57),
(1181, 'Mutugala', 0.00, 0, 4, 0.00, 0.00, 57),
(1182, 'Nabadewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1183, 'Nagollagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1184, 'Nagollagoda', 0.00, 0, 4, 0.00, 0.00, 57),
(1185, 'Nakkawatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1186, 'Narammala', 0.00, 0, 4, 0.00, 0.00, 57),
(1187, 'Nawasenapura', 0.00, 0, 4, 0.00, 0.00, 57),
(1188, 'Nawatalwatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1189, 'Nelliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1190, 'Nikaweratiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1191, 'Nugagolla', 0.00, 0, 4, 0.00, 0.00, 57),
(1192, 'Nugawela', 0.00, 0, 4, 0.00, 0.00, 57),
(1193, 'Padeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1194, 'Padiwela', 0.00, 0, 4, 0.00, 0.00, 57),
(1195, 'Pahalagiribawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1196, 'Pahamune', 0.00, 0, 4, 0.00, 0.00, 57),
(1197, 'Palagala', 0.00, 0, 4, 0.00, 0.00, 57),
(1198, 'Palapathwela', 0.00, 0, 4, 0.00, 0.00, 57),
(1199, 'Palaviya', 0.00, 0, 4, 0.00, 0.00, 57),
(1200, 'Pallewela', 0.00, 0, 4, 0.00, 0.00, 57),
(1201, 'Palukadawala', 0.00, 0, 4, 0.00, 0.00, 57),
(1202, 'Panadaragama', 0.00, 0, 4, 0.00, 0.00, 57),
(1203, 'Panagamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1204, 'Panaliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1205, 'Panapitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1206, 'Panliyadda', 0.00, 0, 4, 0.00, 0.00, 57),
(1207, 'Pansiyagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1208, 'Parape', 0.00, 0, 4, 0.00, 0.00, 57),
(1209, 'Pathanewatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1210, 'Pattiya Watta', 0.00, 0, 4, 0.00, 0.00, 57),
(1211, 'Perakanatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1212, 'Periyakadneluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1213, 'Pihimbiya Ratmale', 0.00, 0, 4, 0.00, 0.00, 57),
(1214, 'Pihimbuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1215, 'Pilessa', 0.00, 0, 4, 0.00, 0.00, 57),
(1216, 'Polgahawela', 0.00, 0, 4, 0.00, 0.00, 57),
(1217, 'Polgolla', 0.00, 0, 4, 0.00, 0.00, 57),
(1218, 'Polpithigama', 0.00, 0, 4, 0.00, 0.00, 57),
(1219, 'Pothuhera', 0.00, 0, 4, 0.00, 0.00, 57),
(1220, 'Pothupitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1221, 'Pujapitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1222, 'Rakwana', 0.00, 0, 4, 0.00, 0.00, 57),
(1223, 'Ranorawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1224, 'Rathukohodigala', 0.00, 0, 4, 0.00, 0.00, 57),
(1225, 'Ridibendiella', 0.00, 0, 4, 0.00, 0.00, 57),
(1226, 'Ridigama', 0.00, 0, 4, 0.00, 0.00, 57),
(1227, 'Saliya Asokapura', 0.00, 0, 4, 0.00, 0.00, 57),
(1228, 'Sandalankawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1229, 'Sevanapitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1230, 'Sirambiadiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1231, 'Sirisetagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1232, 'Siyambalangamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1233, 'Siyambalawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1234, 'Solepura', 0.00, 0, 4, 0.00, 0.00, 57),
(1235, 'Solewewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1236, 'Sunandapura', 0.00, 0, 4, 0.00, 0.00, 57),
(1237, 'Talawattegedara', 0.00, 0, 4, 0.00, 0.00, 57),
(1238, 'Tambutta', 0.00, 0, 4, 0.00, 0.00, 57),
(1239, 'Tennepanguwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1240, 'Thalahitimulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1241, 'Thalakolawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1242, 'Thalwita', 0.00, 0, 4, 0.00, 0.00, 57),
(1243, 'Tharana Udawela', 0.00, 0, 4, 0.00, 0.00, 57),
(1244, 'Thimbiriyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1245, 'Tisogama', 0.00, 0, 4, 0.00, 0.00, 57),
(1246, 'Thorayaya', 0.00, 0, 4, 0.00, 0.00, 57),
(1247, 'Tulhiriya', 0.00, 0, 4, 0.00, 0.00, 57),
(1248, 'Tuntota', 0.00, 0, 4, 0.00, 0.00, 57),
(1249, 'Tuttiripitigama', 0.00, 0, 4, 0.00, 0.00, 57),
(1250, 'Udagaldeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1251, 'Udahingulwala', 0.00, 0, 4, 0.00, 0.00, 57),
(1252, 'Udawatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1253, 'Udubaddawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1254, 'Udumulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1255, 'Uhumiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1256, 'Ulpotha Pallekele', 0.00, 0, 4, 0.00, 0.00, 57),
(1257, 'Ulpothagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1258, 'Usgala Siyabmalangamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1259, 'Vijithapura', 0.00, 0, 4, 0.00, 0.00, 57),
(1260, 'Wadakada', 0.00, 0, 4, 0.00, 0.00, 57),
(1261, 'Wadumunnegedara', 0.00, 0, 4, 0.00, 0.00, 57),
(1262, 'Walakumburumulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1263, 'Wannigama', 0.00, 0, 4, 0.00, 0.00, 57),
(1264, 'Wannikudawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1265, 'Wannilhalagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1266, 'Wannirasnayakapura', 0.00, 0, 4, 0.00, 0.00, 57),
(1267, 'Warawewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1268, 'Wariyapola', 0.00, 0, 4, 0.00, 0.00, 57),
(1269, 'Watareka', 0.00, 0, 4, 0.00, 0.00, 57),
(1270, 'Wattegama', 0.00, 0, 4, 0.00, 0.00, 57),
(1271, 'Watuwatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1272, 'Weerapokuna', 0.00, 0, 4, 0.00, 0.00, 57),
(1273, 'Welawa Juncton', 0.00, 0, 4, 0.00, 0.00, 57),
(1274, 'Welipennagahamulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1275, 'Wellagala', 0.00, 0, 4, 0.00, 0.00, 57),
(1276, 'Wellarawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1277, 'Wellawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1278, 'Welpalla', 0.00, 0, 4, 0.00, 0.00, 57),
(1279, 'Wennoruwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1280, 'Weuda', 0.00, 0, 4, 0.00, 0.00, 57),
(1281, 'Wewagama', 0.00, 0, 4, 0.00, 0.00, 57),
(1282, 'Wilgamuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1283, 'Yakwila', 0.00, 0, 4, 0.00, 0.00, 57),
(1284, 'Yatigaloluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1285, 'Mannar', 0.00, 0, 9, 0.00, 0.00, 57),
(1286, 'Puthukudiyiruppu', 0.00, 0, 9, 0.00, 0.00, 57),
(1287, 'Akuramboda', 0.00, 0, 2, 0.00, 0.00, 57),
(1288, 'Alawatuwala', 0.00, 0, 2, 0.00, 0.00, 57),
(1289, 'Alwatta', 0.00, 0, 2, 0.00, 0.00, 57),
(1290, 'Ambana', 0.00, 0, 2, 0.00, 0.00, 57),
(1291, 'Aralaganwila', 0.00, 0, 2, 0.00, 0.00, 57),
(1292, 'Ataragallewa', 0.00, 0, 2, 0.00, 0.00, 57),
(1293, 'Bambaragaswewa', 0.00, 0, 2, 0.00, 0.00, 57),
(1294, 'Barawardhana Oya', 0.00, 0, 2, 0.00, 0.00, 57),
(1295, 'Beligamuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1296, 'Damana', 0.00, 0, 2, 0.00, 0.00, 57),
(1297, 'Dambulla', 0.00, 0, 2, 0.00, 0.00, 57),
(1298, 'Damminna', 0.00, 0, 2, 0.00, 0.00, 57),
(1299, 'Dankanda', 0.00, 0, 2, 0.00, 0.00, 57),
(1300, 'Delwite', 0.00, 0, 2, 0.00, 0.00, 57),
(1301, 'Devagiriya', 0.00, 0, 2, 0.00, 0.00, 57),
(1302, 'Dewahuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1303, 'Divuldamana', 0.00, 0, 2, 0.00, 0.00, 57),
(1304, 'Dullewa', 0.00, 0, 2, 0.00, 0.00, 57),
(1305, 'Dunkolawatta', 0.00, 0, 2, 0.00, 0.00, 57),
(1306, 'Elkaduwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1307, 'Erawula Junction', 0.00, 0, 2, 0.00, 0.00, 57),
(1308, 'Etanawala', 0.00, 0, 2, 0.00, 0.00, 57),
(1309, 'Galewela', 0.00, 0, 2, 0.00, 0.00, 57),
(1310, 'Galoya Junction', 0.00, 0, 2, 0.00, 0.00, 57),
(1311, 'Gammaduwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1312, 'Gangala Puwakpitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(1313, 'Hasalaka', 0.00, 0, 2, 0.00, 0.00, 57),
(1314, 'Hattota Amuna', 0.00, 0, 2, 0.00, 0.00, 57),
(1315, 'Imbulgolla', 0.00, 0, 2, 0.00, 0.00, 57),
(1316, 'Inamaluwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1317, 'Iriyagolla', 0.00, 0, 2, 0.00, 0.00, 57),
(1318, 'Kaikawala', 0.00, 0, 2, 0.00, 0.00, 57),
(1319, 'Kalundawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1320, 'Kandalama', 0.00, 0, 2, 0.00, 0.00, 57),
(1321, 'Kavudupelella', 0.00, 0, 2, 0.00, 0.00, 57),
(1322, 'Kibissa', 0.00, 0, 2, 0.00, 0.00, 57),
(1323, 'Kiwula', 0.00, 0, 2, 0.00, 0.00, 57),
(1324, 'Kongahawela', 0.00, 0, 2, 0.00, 0.00, 57),
(1325, 'Laggala Pallegama', 0.00, 0, 2, 0.00, 0.00, 57),
(1326, 'Leliambe', 0.00, 0, 2, 0.00, 0.00, 57),
(1327, 'Lenadora', 0.00, 0, 2, 0.00, 0.00, 57),
(1328, 'lhala Halmillewa', 0.00, 0, 2, 0.00, 0.00, 57),
(1329, 'lllukkumbura', 0.00, 0, 2, 0.00, 0.00, 57),
(1330, 'Madipola', 0.00, 0, 2, 0.00, 0.00, 57),
(1331, 'Mahawela', 0.00, 0, 2, 0.00, 0.00, 57),
(1332, 'Mananwatta', 0.00, 0, 2, 0.00, 0.00, 57),
(1333, 'Maraka', 0.00, 0, 2, 0.00, 0.00, 57),
(1334, 'Matale', 0.00, 0, 2, 0.00, 0.00, 57),
(1335, 'Melipitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(1336, 'Metihakka', 0.00, 0, 2, 0.00, 0.00, 57),
(1337, 'Millawana', 0.00, 0, 2, 0.00, 0.00, 57),
(1338, 'Muwandeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(1339, 'Nalanda', 0.00, 0, 2, 0.00, 0.00, 57),
(1340, 'Naula', 0.00, 0, 2, 0.00, 0.00, 57),
(1341, 'Opalgala', 0.00, 0, 2, 0.00, 0.00, 57),
(1342, 'Pallepola', 0.00, 0, 2, 0.00, 0.00, 57),
(1343, 'Pimburattewa', 0.00, 0, 2, 0.00, 0.00, 57),
(1344, 'Pulastigama', 0.00, 0, 2, 0.00, 0.00, 57),
(1345, 'Ranamuregama', 0.00, 0, 2, 0.00, 0.00, 57),
(1346, 'Rattota', 0.00, 0, 2, 0.00, 0.00, 57),
(1347, 'Selagama', 0.00, 0, 2, 0.00, 0.00, 57),
(1348, 'Sigiriya', 0.00, 0, 2, 0.00, 0.00, 57),
(1349, 'Sinhagama', 0.00, 0, 2, 0.00, 0.00, 57),
(1350, 'Sungavila', 0.00, 0, 2, 0.00, 0.00, 57),
(1351, 'Talagoda Junction', 0.00, 0, 2, 0.00, 0.00, 57),
(1352, 'Talakiriyagama', 0.00, 0, 2, 0.00, 0.00, 57),
(1353, 'Tamankaduwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1354, 'Udasgiriya', 0.00, 0, 2, 0.00, 0.00, 57),
(1355, 'Udatenna', 0.00, 0, 2, 0.00, 0.00, 57),
(1356, 'Ukuwela', 0.00, 0, 2, 0.00, 0.00, 57),
(1357, 'Wahacotte', 0.00, 0, 2, 0.00, 0.00, 57),
(1358, 'Walawela', 0.00, 0, 2, 0.00, 0.00, 57),
(1359, 'Wehigala', 0.00, 0, 2, 0.00, 0.00, 57),
(1360, 'Welangahawatte', 0.00, 0, 2, 0.00, 0.00, 57),
(1361, 'Wewalawewa', 0.00, 0, 2, 0.00, 0.00, 57),
(1362, 'Yatawatta', 0.00, 0, 2, 0.00, 0.00, 57),
(1363, 'Akuressa', 0.00, 0, 3, 0.00, 0.00, 57),
(1364, 'Alapaladeniya', 0.00, 0, 3, 0.00, 0.00, 57),
(1365, 'Aparekka', 0.00, 0, 3, 0.00, 0.00, 57),
(1366, 'Athuraliya', 0.00, 0, 3, 0.00, 0.00, 57),
(1367, 'Bengamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(1368, 'Bopagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(1369, 'Dampahala', 0.00, 0, 3, 0.00, 0.00, 57),
(1370, 'Deegala Lenama', 0.00, 0, 3, 0.00, 0.00, 57),
(1371, 'Deiyandara', 0.00, 0, 3, 0.00, 0.00, 57),
(1372, 'Denagama', 0.00, 0, 3, 0.00, 0.00, 57),
(1373, 'Denipitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(1374, 'Deniyaya', 0.00, 0, 3, 0.00, 0.00, 57),
(1375, 'Derangala', 0.00, 0, 3, 0.00, 0.00, 57),
(1376, 'Devinuwara (Dondra)', 0.00, 0, 3, 0.00, 0.00, 57),
(1377, 'Dikwella', 0.00, 0, 3, 0.00, 0.00, 57),
(1378, 'Diyagaha', 0.00, 0, 3, 0.00, 0.00, 57),
(1379, 'Diyalape', 0.00, 0, 3, 0.00, 0.00, 57),
(1380, 'Gandara', 0.00, 0, 3, 0.00, 0.00, 57),
(1381, 'Godapitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(1382, 'Gomilamawarala', 0.00, 0, 3, 0.00, 0.00, 57),
(1383, 'Hawpe', 0.00, 0, 3, 0.00, 0.00, 57),
(1384, 'Horapawita', 0.00, 0, 3, 0.00, 0.00, 57),
(1385, 'Kalubowitiyana', 0.00, 0, 3, 0.00, 0.00, 57),
(1386, 'Kamburugamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(1387, 'Kamburupitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(1388, 'Karagoda Uyangoda', 0.00, 0, 3, 0.00, 0.00, 57),
(1389, 'Karaputugala', 0.00, 0, 3, 0.00, 0.00, 57),
(1390, 'Karatota', 0.00, 0, 3, 0.00, 0.00, 57),
(1391, 'Kekanadura', 0.00, 0, 3, 0.00, 0.00, 57),
(1392, 'Kiriweldola', 0.00, 0, 3, 0.00, 0.00, 57),
(1393, 'Kiriwelkele', 0.00, 0, 3, 0.00, 0.00, 57),
(1394, 'Kolawenigama', 0.00, 0, 3, 0.00, 0.00, 57),
(1395, 'Kotapola', 0.00, 0, 3, 0.00, 0.00, 57),
(1396, 'Lankagama', 0.00, 0, 3, 0.00, 0.00, 57),
(1397, 'Makandura', 0.00, 0, 3, 0.00, 0.00, 57),
(1398, 'Maliduwa', 0.00, 0, 3, 0.00, 0.00, 57),
(1399, 'Maramba', 0.00, 0, 3, 0.00, 0.00, 57),
(1400, 'Matara', 0.00, 0, 3, 0.00, 0.00, 57),
(1401, 'Mediripitiya', 0.00, 0, 3, 0.00, 0.00, 57),
(1402, 'Miella', 0.00, 0, 3, 0.00, 0.00, 57),
(1403, 'Mirissa', 0.00, 0, 3, 0.00, 0.00, 57),
(1404, 'Morawaka', 0.00, 0, 3, 0.00, 0.00, 57),
(1405, 'Mulatiyana Junction', 0.00, 0, 3, 0.00, 0.00, 57),
(1406, 'Nadugala', 0.00, 0, 3, 0.00, 0.00, 57),
(1407, 'Naimana', 0.00, 0, 3, 0.00, 0.00, 57),
(1408, 'Palatuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(1409, 'Parapamulla', 0.00, 0, 3, 0.00, 0.00, 57),
(1410, 'Pasgoda', 0.00, 0, 3, 0.00, 0.00, 57),
(1411, 'Penetiyana', 0.00, 0, 3, 0.00, 0.00, 57),
(1412, 'Pitabeddara', 0.00, 0, 3, 0.00, 0.00, 57),
(1413, 'Puhulwella', 0.00, 0, 3, 0.00, 0.00, 57),
(1414, 'Radawela', 0.00, 0, 3, 0.00, 0.00, 57),
(1415, 'Ransegoda', 0.00, 0, 3, 0.00, 0.00, 57),
(1416, 'Rotumba', 0.00, 0, 3, 0.00, 0.00, 57),
(1417, 'Sultanagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(1418, 'Telijjawila', 0.00, 0, 3, 0.00, 0.00, 57),
(1419, 'Thihagoda', 0.00, 0, 3, 0.00, 0.00, 57),
(1420, 'Urubokka', 0.00, 0, 3, 0.00, 0.00, 57),
(1421, 'Urugamuwa', 0.00, 0, 3, 0.00, 0.00, 57),
(1422, 'Urumutta', 0.00, 0, 3, 0.00, 0.00, 57),
(1423, 'Viharahena', 0.00, 0, 3, 0.00, 0.00, 57),
(1424, 'Walakanda', 0.00, 0, 3, 0.00, 0.00, 57),
(1425, 'Walasgala', 0.00, 0, 3, 0.00, 0.00, 57),
(1426, 'Waralla', 0.00, 0, 3, 0.00, 0.00, 57),
(1427, 'Weligama', 0.00, 0, 3, 0.00, 0.00, 57),
(1428, 'Wilpita', 0.00, 0, 3, 0.00, 0.00, 57),
(1429, 'Yatiyana', 0.00, 0, 3, 0.00, 0.00, 57),
(1430, 'Ayiwela', 0.00, 0, 7, 0.00, 0.00, 57),
(1431, 'Badalkumbura', 0.00, 0, 7, 0.00, 0.00, 57),
(1432, 'Baduluwela', 0.00, 0, 7, 0.00, 0.00, 57),
(1433, 'Bakinigahawela', 0.00, 0, 7, 0.00, 0.00, 57),
(1434, 'Balaharuwa', 0.00, 0, 7, 0.00, 0.00, 57),
(1435, 'Bibile', 0.00, 0, 7, 0.00, 0.00, 57),
(1436, 'Buddama', 0.00, 0, 7, 0.00, 0.00, 57),
(1437, 'Buttala', 0.00, 0, 7, 0.00, 0.00, 57),
(1438, 'Dambagalla', 0.00, 0, 7, 0.00, 0.00, 57),
(1439, 'Diyakobala', 0.00, 0, 7, 0.00, 0.00, 57),
(1440, 'Dombagahawela', 0.00, 0, 7, 0.00, 0.00, 57),
(1441, 'Ethimalewewa', 0.00, 0, 7, 0.00, 0.00, 57),
(1442, 'Ettiliwewa', 0.00, 0, 7, 0.00, 0.00, 57),
(1443, 'Galabedda', 0.00, 0, 7, 0.00, 0.00, 57),
(1444, 'Gamewela', 0.00, 0, 7, 0.00, 0.00, 57),
(1445, 'Hambegamuwa', 0.00, 0, 7, 0.00, 0.00, 57),
(1446, 'Hingurukaduwa', 0.00, 0, 7, 0.00, 0.00, 57),
(1447, 'Hulandawa', 0.00, 0, 7, 0.00, 0.00, 57),
(1448, 'Inginiyagala', 0.00, 0, 7, 0.00, 0.00, 57),
(1449, 'Kandaudapanguwa', 0.00, 0, 7, 0.00, 0.00, 57),
(1450, 'Kandawinna', 0.00, 0, 7, 0.00, 0.00, 57),
(1451, 'Kataragama', 0.00, 0, 7, 0.00, 0.00, 57),
(1452, 'Kotagama', 0.00, 0, 7, 0.00, 0.00, 57),
(1453, 'Kotamuduna', 0.00, 0, 7, 0.00, 0.00, 57),
(1454, 'Kotawehera Mankada', 0.00, 0, 7, 0.00, 0.00, 57),
(1455, 'Kudawewa', 0.00, 0, 7, 0.00, 0.00, 57),
(1456, 'Kumbukkana', 0.00, 0, 7, 0.00, 0.00, 57),
(1457, 'Marawa', 0.00, 0, 7, 0.00, 0.00, 57),
(1458, 'Mariarawa', 0.00, 0, 7, 0.00, 0.00, 57),
(1459, 'Medagana', 0.00, 0, 7, 0.00, 0.00, 57),
(1460, 'Medawelagama', 0.00, 0, 7, 0.00, 0.00, 57),
(1461, 'Miyanakandura', 0.00, 0, 7, 0.00, 0.00, 57),
(1462, 'Monaragala', 0.00, 0, 7, 0.00, 0.00, 57),
(1463, 'Moretuwegama', 0.00, 0, 7, 0.00, 0.00, 57),
(1464, 'Nakkala', 0.00, 0, 7, 0.00, 0.00, 57),
(1465, 'Namunukula', 0.00, 0, 7, 0.00, 0.00, 57),
(1466, 'Nannapurawa', 0.00, 0, 7, 0.00, 0.00, 57),
(1467, 'Nelliyadda', 0.00, 0, 7, 0.00, 0.00, 57),
(1468, 'Nilgala', 0.00, 0, 7, 0.00, 0.00, 57),
(1469, 'Obbegoda', 0.00, 0, 7, 0.00, 0.00, 57),
(1470, 'Okkampitiya', 0.00, 0, 7, 0.00, 0.00, 57),
(1471, 'Pangura', 0.00, 0, 7, 0.00, 0.00, 57),
(1472, 'Pitakumbura', 0.00, 0, 7, 0.00, 0.00, 57),
(1473, 'Randeniya', 0.00, 0, 7, 0.00, 0.00, 57),
(1474, 'Ruwalwela', 0.00, 0, 7, 0.00, 0.00, 57),
(1475, 'Sella Kataragama', 0.00, 0, 7, 0.00, 0.00, 57),
(1476, 'Siyambalagune', 0.00, 0, 7, 0.00, 0.00, 57),
(1477, 'Siyambalanduwa', 0.00, 0, 7, 0.00, 0.00, 57),
(1478, 'Suriara', 0.00, 0, 7, 0.00, 0.00, 57),
(1479, 'Thanamalwila', 0.00, 0, 7, 0.00, 0.00, 57),
(1480, 'Uva Gangodagama', 0.00, 0, 7, 0.00, 0.00, 57),
(1481, 'Uva Kudaoya', 0.00, 0, 7, 0.00, 0.00, 57),
(1482, 'Uva Pelwatta', 0.00, 0, 7, 0.00, 0.00, 57),
(1483, 'Warunagama', 0.00, 0, 7, 0.00, 0.00, 57),
(1484, 'Wedikumbura', 0.00, 0, 7, 0.00, 0.00, 57),
(1485, 'Weherayaya Handapanagala', 0.00, 0, 7, 0.00, 0.00, 57),
(1486, 'Wellawaya', 0.00, 0, 7, 0.00, 0.00, 57),
(1487, 'Wilaoya', 0.00, 0, 7, 0.00, 0.00, 57),
(1488, 'Yudaganawa', 0.00, 0, 7, 0.00, 0.00, 57),
(1489, 'Mullativu', 0.00, 0, 9, 0.00, 0.00, 57),
(1490, 'Agarapathana', 0.00, 0, 2, 0.00, 0.00, 57),
(1491, 'Ambatalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1492, 'Ambewela', 0.00, 0, 2, 0.00, 0.00, 57),
(1493, 'Bogawantalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1494, 'Bopattalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1495, 'Dagampitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(1496, 'Dayagama Bazaar', 0.00, 0, 2, 0.00, 0.00, 57),
(1497, 'Dikoya', 0.00, 0, 2, 0.00, 0.00, 57),
(1498, 'Doragala', 0.00, 0, 2, 0.00, 0.00, 57),
(1499, 'Dunukedeniya', 0.00, 0, 2, 0.00, 0.00, 57),
(1500, 'Egodawela', 0.00, 0, 2, 0.00, 0.00, 57),
(1501, 'Ekiriya', 0.00, 0, 2, 0.00, 0.00, 57),
(1502, 'Elamulla', 0.00, 0, 2, 0.00, 0.00, 57),
(1503, 'Ginigathena', 0.00, 0, 2, 0.00, 0.00, 57),
(1504, 'Gonakele', 0.00, 0, 2, 0.00, 0.00, 57),
(1505, 'Haggala', 0.00, 0, 2, 0.00, 0.00, 57),
(1506, 'Halgranoya', 0.00, 0, 2, 0.00, 0.00, 57),
(1507, 'Hangarapitiya', 0.00, 0, 2, 0.00, 0.00, 57),
(1508, 'Hapugasthalawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1509, 'Harasbedda', 0.00, 0, 2, 0.00, 0.00, 57),
(1510, 'Hatton', 0.00, 0, 2, 0.00, 0.00, 57),
(1511, 'Hewaheta', 0.00, 0, 2, 0.00, 0.00, 57),
(1512, 'Hitigegama', 0.00, 0, 2, 0.00, 0.00, 57),
(1513, 'Jangulla', 0.00, 0, 2, 0.00, 0.00, 57),
(1514, 'Kalaganwatta', 0.00, 0, 2, 0.00, 0.00, 57),
(1515, 'Kandapola', 0.00, 0, 2, 0.00, 0.00, 57),
(1516, 'Karandagolla', 0.00, 0, 2, 0.00, 0.00, 57),
(1517, 'Keerthi Bandarapura', 0.00, 0, 2, 0.00, 0.00, 57),
(1518, 'Kiribathkumbura', 0.00, 0, 2, 0.00, 0.00, 57),
(1519, 'Kotiyagala', 0.00, 0, 2, 0.00, 0.00, 57),
(1520, 'Kotmale', 0.00, 0, 2, 0.00, 0.00, 57),
(1521, 'Kottellena', 0.00, 0, 2, 0.00, 0.00, 57),
(1522, 'Kumbalgamuwa', 0.00, 0, 2, 0.00, 0.00, 57),
(1523, 'Kumbukwela', 0.00, 0, 2, 0.00, 0.00, 57),
(1524, 'Kurupanawela', 0.00, 0, 2, 0.00, 0.00, 57),
(1525, 'Labukele', 0.00, 0, 2, 0.00, 0.00, 57),
(1526, 'Laxapana', 0.00, 0, 2, 0.00, 0.00, 57),
(1527, 'Lindula', 0.00, 0, 2, 0.00, 0.00, 57),
(1528, 'Madulla', 0.00, 0, 2, 0.00, 0.00, 57),
(1529, 'Mandaram Nuwara', 0.00, 0, 2, 0.00, 0.00, 57),
(1530, 'Maskeliya', 0.00, 0, 2, 0.00, 0.00, 57),
(1531, 'Maswela', 0.00, 0, 2, 0.00, 0.00, 57),
(1532, 'Maturata', 0.00, 0, 2, 0.00, 0.00, 57),
(1533, 'Mipanawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1534, 'Mipilimana', 0.00, 0, 2, 0.00, 0.00, 57),
(1535, 'Morahenagama', 0.00, 0, 2, 0.00, 0.00, 57),
(1536, 'Munwatta', 0.00, 0, 2, 0.00, 0.00, 57),
(1537, 'Nayapana Janapadaya', 0.00, 0, 2, 0.00, 0.00, 57),
(1538, 'Nildandahinna', 0.00, 0, 2, 0.00, 0.00, 57),
(1539, 'Nissanka Uyana', 0.00, 0, 2, 0.00, 0.00, 57),
(1540, 'Norwood', 0.00, 0, 2, 0.00, 0.00, 57),
(1541, 'Nuwara Eliya', 0.00, 0, 2, 0.00, 0.00, 57),
(1542, 'Padiyapelella', 0.00, 0, 2, 0.00, 0.00, 57),
(1543, 'Pallebowala', 0.00, 0, 2, 0.00, 0.00, 57),
(1544, 'Panvila', 0.00, 0, 2, 0.00, 0.00, 57),
(1545, 'Pitawala', 0.00, 0, 2, 0.00, 0.00, 57),
(1546, 'Pundaluoya', 0.00, 0, 2, 0.00, 0.00, 57),
(1547, 'Ramboda', 0.00, 0, 2, 0.00, 0.00, 57),
(1548, 'Rikillagaskada', 0.00, 0, 2, 0.00, 0.00, 57),
(1549, 'Rozella', 0.00, 0, 2, 0.00, 0.00, 57),
(1550, 'Rupaha', 0.00, 0, 2, 0.00, 0.00, 57),
(1551, 'Ruwaneliya', 0.00, 0, 2, 0.00, 0.00, 57),
(1552, 'Santhipura', 0.00, 0, 2, 0.00, 0.00, 57),
(1553, 'Talawakele', 0.00, 0, 2, 0.00, 0.00, 57),
(1554, 'Tawalantenna', 0.00, 0, 2, 0.00, 0.00, 57),
(1555, 'Teripeha', 0.00, 0, 2, 0.00, 0.00, 57),
(1556, 'Udamadura', 0.00, 0, 2, 0.00, 0.00, 57),
(1557, 'Udapussallawa', 0.00, 0, 2, 0.00, 0.00, 57),
(1558, 'Uva Deegalla', 0.00, 0, 2, 0.00, 0.00, 57),
(1559, 'Uva Uduwara', 0.00, 0, 2, 0.00, 0.00, 57),
(1560, 'Uvaparanagama', 0.00, 0, 2, 0.00, 0.00, 57),
(1561, 'Walapane', 0.00, 0, 2, 0.00, 0.00, 57),
(1562, 'Watawala', 0.00, 0, 2, 0.00, 0.00, 57),
(1563, 'Widulipura', 0.00, 0, 2, 0.00, 0.00, 57),
(1564, 'Wijebahukanda', 0.00, 0, 2, 0.00, 0.00, 57),
(1565, 'Attanakadawala', 0.00, 0, 8, 0.00, 0.00, 57),
(1566, 'Bakamuna', 0.00, 0, 8, 0.00, 0.00, 57),
(1567, 'Diyabeduma', 0.00, 0, 8, 0.00, 0.00, 57),
(1568, 'Elahera', 0.00, 0, 8, 0.00, 0.00, 57),
(1569, 'Giritale', 0.00, 0, 8, 0.00, 0.00, 57),
(1570, 'Hingurakdamana', 0.00, 0, 8, 0.00, 0.00, 57),
(1571, 'Hingurakgoda', 0.00, 0, 8, 0.00, 0.00, 57),
(1572, 'Jayanthipura', 0.00, 0, 8, 0.00, 0.00, 57),
(1573, 'Kalingaela', 0.00, 0, 8, 0.00, 0.00, 57),
(1574, 'Lakshauyana', 0.00, 0, 8, 0.00, 0.00, 57),
(1575, 'Mankemi', 0.00, 0, 8, 0.00, 0.00, 57),
(1576, 'Minneriya', 0.00, 0, 8, 0.00, 0.00, 57),
(1577, 'Onegama', 0.00, 0, 8, 0.00, 0.00, 57),
(1578, 'Orubendi Siyambalawa', 0.00, 0, 8, 0.00, 0.00, 57),
(1579, 'Palugasdamana', 0.00, 0, 8, 0.00, 0.00, 57),
(1580, 'Panichankemi', 0.00, 0, 8, 0.00, 0.00, 57),
(1581, 'Polonnaruwa', 0.00, 0, 8, 0.00, 0.00, 57),
(1582, 'Talpotha', 0.00, 0, 8, 0.00, 0.00, 57),
(1583, 'Tambala', 0.00, 0, 8, 0.00, 0.00, 57),
(1584, 'Unagalavehera', 0.00, 0, 8, 0.00, 0.00, 57),
(1585, 'Wijayabapura', 0.00, 0, 8, 0.00, 0.00, 57),
(1586, 'Adippala', 0.00, 0, 4, 0.00, 0.00, 57),
(1587, 'Alutgama', 0.00, 0, 4, 0.00, 0.00, 57),
(1588, 'Alutwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1589, 'Ambakandawila', 0.00, 0, 4, 0.00, 0.00, 57),
(1590, 'Anamaduwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1591, 'Andigama', 0.00, 0, 4, 0.00, 0.00, 57),
(1592, 'Angunawila', 0.00, 0, 4, 0.00, 0.00, 57),
(1593, 'Attawilluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1594, 'Bangadeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1595, 'Baranankattuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1596, 'Battuluoya', 0.00, 0, 4, 0.00, 0.00, 57),
(1597, 'Bujjampola', 0.00, 0, 4, 0.00, 0.00, 57),
(1598, 'Chilaw', 0.00, 0, 4, 0.00, 0.00, 57),
(1599, 'Dalukana', 0.00, 0, 4, 0.00, 0.00, 57),
(1600, 'Dankotuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1601, 'Dewagala', 0.00, 0, 4, 0.00, 0.00, 57),
(1602, 'Dummalasuriya', 0.00, 0, 4, 0.00, 0.00, 57),
(1603, 'Dunkannawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1604, 'Eluwankulama', 0.00, 0, 4, 0.00, 0.00, 57),
(1605, 'Ettale', 0.00, 0, 4, 0.00, 0.00, 57),
(1606, 'Galamuna', 0.00, 0, 4, 0.00, 0.00, 57),
(1607, 'Galmuruwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1608, 'Hansayapalama', 0.00, 0, 4, 0.00, 0.00, 57),
(1609, 'Ihala Kottaramulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1610, 'Ilippadeniya', 0.00, 0, 4, 0.00, 0.00, 57),
(1611, 'Inginimitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1612, 'Ismailpuram', 0.00, 0, 4, 0.00, 0.00, 57),
(1613, 'Jayasiripura', 0.00, 0, 4, 0.00, 0.00, 57),
(1614, 'Kakkapalliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1615, 'Kalkudah', 0.00, 0, 4, 0.00, 0.00, 57),
(1616, 'Kalladiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1617, 'Kandakuliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1618, 'Karathivu', 0.00, 0, 4, 0.00, 0.00, 57),
(1619, 'Karawitagara', 0.00, 0, 4, 0.00, 0.00, 57),
(1620, 'Karuwalagaswewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1621, 'Katuneriya', 0.00, 0, 4, 0.00, 0.00, 57),
(1622, 'Koswatta', 0.00, 0, 4, 0.00, 0.00, 57),
(1623, 'Kottantivu', 0.00, 0, 4, 0.00, 0.00, 57),
(1624, 'Kottapitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1625, 'Kottukachchiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1626, 'Kumarakattuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1627, 'Kurinjanpitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1628, 'Kuruketiyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1629, 'Lunuwila', 0.00, 0, 4, 0.00, 0.00, 57),
(1630, 'Madampe', 0.00, 0, 4, 0.00, 0.00, 57),
(1631, 'Madurankuliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1632, 'Mahakumbukkadawala', 0.00, 0, 4, 0.00, 0.00, 57),
(1633, 'Mahauswewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1634, 'Mampitiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1635, 'Mampuri', 0.00, 0, 4, 0.00, 0.00, 57),
(1636, 'Mangalaeliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1637, 'Marawila', 0.00, 0, 4, 0.00, 0.00, 57),
(1638, 'Mudalakkuliya', 0.00, 0, 4, 0.00, 0.00, 57),
(1639, 'Mugunuwatawana', 0.00, 0, 4, 0.00, 0.00, 57),
(1640, 'Mukkutoduwawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1641, 'Mundel', 0.00, 0, 4, 0.00, 0.00, 57),
(1642, 'Muttibendiwila', 0.00, 0, 4, 0.00, 0.00, 57),
(1643, 'Nainamadama', 0.00, 0, 4, 0.00, 0.00, 57),
(1644, 'Nalladarankattuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1645, 'Nattandiya', 0.00, 0, 4, 0.00, 0.00, 57),
(1646, 'Nawagattegama', 0.00, 0, 4, 0.00, 0.00, 57),
(1647, 'Nelumwewa', 0.00, 0, 4, 0.00, 0.00, 57),
(1648, 'Norachcholai', 0.00, 0, 4, 0.00, 0.00, 57),
(1649, 'Pallama', 0.00, 0, 4, 0.00, 0.00, 57),
(1650, 'Palliwasalturai', 0.00, 0, 4, 0.00, 0.00, 57),
(1651, 'Panirendawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1652, 'Parakramasamudraya', 0.00, 0, 4, 0.00, 0.00, 57),
(1653, 'Pothuwatawana', 0.00, 0, 4, 0.00, 0.00, 57),
(1654, 'Puttalam', 0.00, 0, 4, 0.00, 0.00, 57),
(1655, 'Puttalam Cement Factory', 0.00, 0, 4, 0.00, 0.00, 57),
(1656, 'Rajakadaluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1657, 'Saliyawewa Junction', 0.00, 0, 4, 0.00, 0.00, 57),
(1658, 'Serukele', 0.00, 0, 4, 0.00, 0.00, 57),
(1659, 'Siyambalagashene', 0.00, 0, 4, 0.00, 0.00, 57),
(1660, 'Tabbowa', 0.00, 0, 4, 0.00, 0.00, 57),
(1661, 'Talawila Church', 0.00, 0, 4, 0.00, 0.00, 57),
(1662, 'Toduwawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1663, 'Udappuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1664, 'Uridyawa', 0.00, 0, 4, 0.00, 0.00, 57),
(1665, 'Vanathawilluwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1666, 'Waikkal', 0.00, 0, 4, 0.00, 0.00, 57),
(1667, 'Watugahamulla', 0.00, 0, 4, 0.00, 0.00, 57),
(1668, 'Wennappuwa', 0.00, 0, 4, 0.00, 0.00, 57),
(1669, 'Wijeyakatupotha', 0.00, 0, 4, 0.00, 0.00, 57),
(1670, 'Wilpotha', 0.00, 0, 4, 0.00, 0.00, 57),
(1671, 'Yodaela', 0.00, 0, 4, 0.00, 0.00, 57),
(1672, 'Yogiyana', 0.00, 0, 4, 0.00, 0.00, 57),
(1673, 'Akarella', 0.00, 0, 5, 0.00, 0.00, 57),
(1674, 'Amunumulla', 0.00, 0, 5, 0.00, 0.00, 57),
(1675, 'Atakalanpanna', 0.00, 0, 5, 0.00, 0.00, 57),
(1676, 'Ayagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1677, 'Balangoda', 0.00, 0, 5, 0.00, 0.00, 57),
(1678, 'Batatota', 0.00, 0, 5, 0.00, 0.00, 57),
(1679, 'Beralapanathara', 0.00, 0, 5, 0.00, 0.00, 57),
(1680, 'Bogahakumbura', 0.00, 0, 5, 0.00, 0.00, 57),
(1681, 'Bolthumbe', 0.00, 0, 5, 0.00, 0.00, 57),
(1682, 'Bomluwageaina', 0.00, 0, 5, 0.00, 0.00, 57),
(1683, 'Bowalagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1684, 'Bulutota', 0.00, 0, 5, 0.00, 0.00, 57),
(1685, 'Dambuluwana', 0.00, 0, 5, 0.00, 0.00, 57),
(1686, 'Daugala', 0.00, 0, 5, 0.00, 0.00, 57),
(1687, 'Dela', 0.00, 0, 5, 0.00, 0.00, 57),
(1688, 'Delwala', 0.00, 0, 5, 0.00, 0.00, 57),
(1689, 'Dodampe', 0.00, 0, 5, 0.00, 0.00, 57),
(1690, 'Doloswalakanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1691, 'Dumbara Manana', 0.00, 0, 5, 0.00, 0.00, 57),
(1692, 'Eheliyagoda', 0.00, 0, 5, 0.00, 0.00, 57),
(1693, 'Ekamutugama', 0.00, 0, 5, 0.00, 0.00, 57),
(1694, 'Elapatha', 0.00, 0, 5, 0.00, 0.00, 57),
(1695, 'Ellagawa', 0.00, 0, 5, 0.00, 0.00, 57),
(1696, 'Ellaulla', 0.00, 0, 5, 0.00, 0.00, 57),
(1697, 'Ellawala', 0.00, 0, 5, 0.00, 0.00, 57),
(1698, 'Embilipitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(1699, 'Eratna', 0.00, 0, 5, 0.00, 0.00, 57),
(1700, 'Erepola', 0.00, 0, 5, 0.00, 0.00, 57),
(1701, 'Gabbela', 0.00, 0, 5, 0.00, 0.00, 57),
(1702, 'Gangeyaya', 0.00, 0, 5, 0.00, 0.00, 57),
(1703, 'Gawaragiriya', 0.00, 0, 5, 0.00, 0.00, 57),
(1704, 'Gillimale', 0.00, 0, 5, 0.00, 0.00, 57),
(1705, 'Godakawela', 0.00, 0, 5, 0.00, 0.00, 57),
(1706, 'Gurubewilagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1707, 'Halwinna', 0.00, 0, 5, 0.00, 0.00, 57),
(1708, 'Handagiriya', 0.00, 0, 5, 0.00, 0.00, 57),
(1709, 'Hatangala', 0.00, 0, 5, 0.00, 0.00, 57),
(1710, 'Hatarabage', 0.00, 0, 5, 0.00, 0.00, 57),
(1711, 'Hewanakumbura', 0.00, 0, 5, 0.00, 0.00, 57),
(1712, 'Hidellana', 0.00, 0, 5, 0.00, 0.00, 57),
(1713, 'Hiramadagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1714, 'Horewelagoda', 0.00, 0, 5, 0.00, 0.00, 57),
(1715, 'Ittakanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1716, 'Kahangama', 0.00, 0, 5, 0.00, 0.00, 57),
(1717, 'Kahawatta', 0.00, 0, 5, 0.00, 0.00, 57),
(1718, 'Kalawana', 0.00, 0, 5, 0.00, 0.00, 57),
(1719, 'Kaltota', 0.00, 0, 5, 0.00, 0.00, 57),
(1720, 'Kalubululanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1721, 'Kananke Bazaar', 0.00, 0, 5, 0.00, 0.00, 57),
(1722, 'Kandepuhulpola', 0.00, 0, 5, 0.00, 0.00, 57),
(1723, 'Karandana', 0.00, 0, 5, 0.00, 0.00, 57),
(1724, 'Karangoda', 0.00, 0, 5, 0.00, 0.00, 57),
(1725, 'Kella Junction', 0.00, 0, 5, 0.00, 0.00, 57),
(1726, 'Keppetipola', 0.00, 0, 5, 0.00, 0.00, 57),
(1727, 'Kiriella', 0.00, 0, 5, 0.00, 0.00, 57),
(1728, 'Kiriibbanwewa', 0.00, 0, 5, 0.00, 0.00, 57),
(1729, 'Kolambage Ara', 0.00, 0, 5, 0.00, 0.00, 57),
(1730, 'Kolombugama', 0.00, 0, 5, 0.00, 0.00, 57),
(1731, 'Kolonna', 0.00, 0, 5, 0.00, 0.00, 57),
(1732, 'Kudawa', 0.00, 0, 5, 0.00, 0.00, 57),
(1733, 'Kuruwita', 0.00, 0, 5, 0.00, 0.00, 57),
(1734, 'Lellopitiya', 0.00, 0, 5, 0.00, 0.00, 57),
(1735, 'Imaduwa', 0.00, 0, 5, 0.00, 0.00, 57),
(1736, 'Imbulpe', 0.00, 0, 5, 0.00, 0.00, 57),
(1737, 'Mahagama Colony', 0.00, 0, 5, 0.00, 0.00, 57),
(1738, 'Mahawalatenna', 0.00, 0, 5, 0.00, 0.00, 57),
(1739, 'Makandura', 0.00, 0, 5, 0.00, 0.00, 57),
(1740, 'Malwala Junction', 0.00, 0, 5, 0.00, 0.00, 57),
(1741, 'Malwatta', 0.00, 0, 5, 0.00, 0.00, 57),
(1742, 'Matuwagalagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1743, 'Medagalature', 0.00, 0, 5, 0.00, 0.00, 57),
(1744, 'Meddekanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1745, 'Minipura Dumbara', 0.00, 0, 5, 0.00, 0.00, 57),
(1746, 'Mitipola', 0.00, 0, 5, 0.00, 0.00, 57),
(1747, 'Moragala Kirillapone', 0.00, 0, 5, 0.00, 0.00, 57),
(1748, 'Morahela', 0.00, 0, 5, 0.00, 0.00, 57),
(1749, 'Mulendiyawala', 0.00, 0, 5, 0.00, 0.00, 57),
(1750, 'Mulgama', 0.00, 0, 5, 0.00, 0.00, 57),
(1751, 'Nawalakanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1752, 'Nawinnapinnakanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1753, 'Niralagama', 0.00, 0, 5, 0.00, 0.00, 57),
(1754, 'Nivitigala', 0.00, 0, 5, 0.00, 0.00, 57),
(1755, 'Omalpe', 0.00, 0, 5, 0.00, 0.00, 57),
(1756, 'Opanayaka', 0.00, 0, 5, 0.00, 0.00, 57),
(1757, 'Padalangala', 0.00, 0, 5, 0.00, 0.00, 57),
(1758, 'Pallebedda', 0.00, 0, 5, 0.00, 0.00, 57),
(1759, 'Pallekanda', 0.00, 0, 5, 0.00, 0.00, 57),
(1760, 'Pambagolla', 0.00, 0, 5, 0.00, 0.00, 57),
(1761, 'Panamura', 0.00, 0, 5, 0.00, 0.00, 57),
(1762, 'Panapola', 0.00, 0, 5, 0.00, 0.00, 57),
(1763, 'Paragala', 0.00, 0, 5, 0.00, 0.00, 57),
(1764, 'Parakaduwa', 0.00, 0, 5, 0.00, 0.00, 57),
(1765, 'Pebotuwa', 0.00, 0, 5, 0.00, 0.00, 57),
(1766, 'Pelmadulla', 0.00, 0, 5, 0.00, 0.00, 57),
(1767, 'Pinnawala', 0.00, 0, 5, 0.00, 0.00, 57),
(1768, 'Pothdeniya', 0.00, 0, 5, 0.00, 0.00, 57),
(1769, 'Rajawaka', 0.00, 0, 5, 0.00, 0.00, 57),
(1770, 'Ranwala', 0.00, 0, 5, 0.00, 0.00, 57),
(1771, 'Rassagala', 0.00, 0, 5, 0.00, 0.00, 57),
(1772, 'Rathgama', 0.00, 0, 5, 0.00, 0.00, 57),
(1773, 'Ratna Hangamuwa', 0.00, 0, 5, 0.00, 0.00, 57),
(1774, 'Ratnapura', 0.00, 0, 5, 0.00, 0.00, 57),
(1775, 'Sewanagala', 0.00, 0, 5, 0.00, 0.00, 57),
(1776, 'Sri Palabaddala', 0.00, 0, 5, 0.00, 0.00, 57),
(1777, 'Sudagala', 0.00, 0, 5, 0.00, 0.00, 57),
(1778, 'Thalakolahinna', 0.00, 0, 5, 0.00, 0.00, 57),
(1779, 'Thanjantenna', 0.00, 0, 5, 0.00, 0.00, 57),
(1780, 'Theppanawa', 0.00, 0, 5, 0.00, 0.00, 57),
(1781, 'Thunkama', 0.00, 0, 5, 0.00, 0.00, 57),
(1782, 'Udakarawita', 0.00, 0, 5, 0.00, 0.00, 57),
(1783, 'Udaniriella', 0.00, 0, 5, 0.00, 0.00, 57),
(1784, 'Udawalawe', 0.00, 0, 5, 0.00, 0.00, 57),
(1785, 'Ullinduwawa', 0.00, 0, 5, 0.00, 0.00, 57),
(1786, 'Veddagala', 0.00, 0, 5, 0.00, 0.00, 57),
(1787, 'Vijeriya', 0.00, 0, 5, 0.00, 0.00, 57),
(1788, 'Waleboda', 0.00, 0, 5, 0.00, 0.00, 57),
(1789, 'Watapotha', 0.00, 0, 5, 0.00, 0.00, 57),
(1790, 'Waturawa', 0.00, 0, 5, 0.00, 0.00, 57),
(1791, 'Weligepola', 0.00, 0, 5, 0.00, 0.00, 57),
(1792, 'Welipathayaya', 0.00, 0, 5, 0.00, 0.00, 57),
(1793, 'Wikiliya', 0.00, 0, 5, 0.00, 0.00, 57),
(1794, 'Agbopura', 0.00, 0, 6, 0.00, 0.00, 57),
(1795, 'Buckmigama', 0.00, 0, 6, 0.00, 0.00, 57),
(1796, 'China Bay', 0.00, 0, 6, 0.00, 0.00, 57),
(1797, 'Dehiwatte', 0.00, 0, 6, 0.00, 0.00, 57),
(1798, 'Echchilampattai', 0.00, 0, 6, 0.00, 0.00, 57),
(1799, 'Galmetiyawa', 0.00, 0, 6, 0.00, 0.00, 57),
(1800, 'Gomarankadawala', 0.00, 0, 6, 0.00, 0.00, 57),
(1801, 'Kaddaiparichchan', 0.00, 0, 6, 0.00, 0.00, 57),
(1802, 'Kallar', 0.00, 0, 6, 0.00, 0.00, 57),
(1803, 'Kanniya', 0.00, 0, 6, 0.00, 0.00, 57),
(1804, 'Kantalai', 0.00, 0, 6, 0.00, 0.00, 57),
(1805, 'Kantalai Sugar Factory', 0.00, 0, 6, 0.00, 0.00, 57),
(1806, 'Kiliveddy', 0.00, 0, 6, 0.00, 0.00, 57),
(1807, 'Kinniya', 0.00, 0, 6, 0.00, 0.00, 57),
(1808, 'Kuchchaveli', 0.00, 0, 6, 0.00, 0.00, 57),
(1809, 'Kumburupiddy', 0.00, 0, 6, 0.00, 0.00, 57),
(1810, 'Kurinchakemy', 0.00, 0, 6, 0.00, 0.00, 57),
(1811, 'Lankapatuna', 0.00, 0, 6, 0.00, 0.00, 57),
(1812, 'Mahadivulwewa', 0.00, 0, 6, 0.00, 0.00, 57),
(1813, 'Maharugiramam', 0.00, 0, 6, 0.00, 0.00, 57),
(1814, 'Mallikativu', 0.00, 0, 6, 0.00, 0.00, 57),
(1815, 'Mawadichenai', 0.00, 0, 6, 0.00, 0.00, 57),
(1816, 'Mullipothana', 0.00, 0, 6, 0.00, 0.00, 57),
(1817, 'Mutur', 0.00, 0, 6, 0.00, 0.00, 57),
(1818, 'Neelapola', 0.00, 0, 6, 0.00, 0.00, 57),
(1819, 'Nilaveli', 0.00, 0, 6, 0.00, 0.00, 57),
(1820, 'Pankulam', 0.00, 0, 6, 0.00, 0.00, 57),
(1821, 'Pulmoddai', 0.00, 0, 6, 0.00, 0.00, 57),
(1822, 'Rottawewa', 0.00, 0, 6, 0.00, 0.00, 57),
(1823, 'Sampaltivu', 0.00, 0, 6, 0.00, 0.00, 57),
(1824, 'Sampoor', 0.00, 0, 6, 0.00, 0.00, 57),
(1825, 'Serunuwara', 0.00, 0, 6, 0.00, 0.00, 57),
(1826, 'Seruwila', 0.00, 0, 6, 0.00, 0.00, 57),
(1827, 'Sirajnagar', 0.00, 0, 6, 0.00, 0.00, 57),
(1828, 'Somapura', 0.00, 0, 6, 0.00, 0.00, 57),
(1829, 'Tampalakamam', 0.00, 0, 6, 0.00, 0.00, 57),
(1830, 'Thuraineelavanai', 0.00, 0, 6, 0.00, 0.00, 57),
(1831, 'Tiriyayi', 0.00, 0, 6, 0.00, 0.00, 57),
(1832, 'Toppur', 0.00, 0, 6, 0.00, 0.00, 57),
(1833, 'Trincomalee', 0.00, 0, 6, 0.00, 0.00, 57),
(1834, 'Wanela', 0.00, 0, 6, 0.00, 0.00, 57),
(1835, 'Vavuniya', 0.00, 0, 9, 0.00, 0.00, 57),
(1836, 'Colombo 1', 190.00, 0, 1, 50.00, 0.00, 57),
(1837, 'Colombo 3', 190.00, 0, 1, 50.00, 0.00, 57),
(1838, 'Colombo 4', 190.00, 0, 1, 50.00, 0.00, 57),
(1839, 'Colombo 5', 190.00, 0, 1, 50.00, 0.00, 57),
(1840, 'Colombo 7', 190.00, 0, 1, 50.00, 0.00, 57),
(1841, 'Colombo 9', 190.00, 0, 1, 50.00, 0.00, 57),
(1842, 'Colombo 10', 190.00, 0, 1, 50.00, 0.00, 57),
(1843, 'Colombo 11', 190.00, 0, 1, 50.00, 0.00, 57),
(1844, 'Colombo 12', 190.00, 0, 1, 50.00, 0.00, 57),
(1845, 'Colombo 14', 190.00, 0, 1, 50.00, 0.00, 57);

-- --------------------------------------------------------

--
-- Table structure for table `comapny_message`
--

CREATE TABLE `comapny_message` (
  `Id` int(10) NOT NULL,
  `Message` text NOT NULL,
  `class` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `comapny_message`
--

INSERT INTO `comapny_message` (`Id`, `Message`, `class`) VALUES
(1, '', 'alert-success');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `country_id` int(10) NOT NULL,
  `country_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`country_id`, `country_name`) VALUES
(1, 'Australia'),
(2, 'New Zealand'),
(3, 'United States'),
(4, 'United Kingdom'),
(5, 'Canada');

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `pk_id` int(10) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `currencyId` int(10) NOT NULL DEFAULT 0,
  `DispatchType` enum('Ship','Delivery') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`pk_id`, `name`, `active`, `currencyId`, `DispatchType`) VALUES
(1, 'Bangladesh', 0, 0, 'Ship'),
(2, 'Bhutan', 0, 0, 'Ship'),
(3, 'Brunei', 0, 0, 'Ship'),
(4, 'Cambodia', 0, 0, 'Ship'),
(5, 'China, People\'s Republic of', 0, 0, 'Ship'),
(6, 'Hong Kong', 0, 0, 'Ship'),
(7, 'India', 0, 0, 'Ship'),
(8, 'Indonesia', 0, 0, 'Ship'),
(9, 'Japan', 0, 0, 'Ship'),
(10, 'Korea, South', 0, 0, 'Ship'),
(11, 'Macau', 0, 0, 'Ship'),
(12, 'Malaysia', 0, 0, 'Ship'),
(13, 'Maldives', 1, 0, 'Ship'),
(14, 'Myanmar', 0, 0, 'Ship'),
(15, 'Nepal', 0, 0, 'Ship'),
(16, 'Pakistan', 0, 0, 'Ship'),
(17, 'Philippines', 0, 0, 'Ship'),
(18, 'Singapore', 0, 0, 'Ship'),
(19, 'Taiwan', 0, 0, 'Ship'),
(20, 'Thailand', 0, 0, 'Ship'),
(21, 'Vietnam', 0, 0, 'Ship'),
(22, 'Australia', 0, 0, 'Ship'),
(23, 'Bahrain', 0, 0, 'Ship'),
(24, 'Belgium', 0, 0, 'Ship'),
(25, 'Bulgaria', 0, 0, 'Ship'),
(26, 'Canada', 0, 0, 'Ship'),
(27, 'Cyprus', 0, 0, 'Ship'),
(28, 'Czech Republic', 0, 0, 'Ship'),
(29, 'Denmark', 0, 0, 'Ship'),
(30, 'Finland', 0, 0, 'Ship'),
(31, 'France', 0, 0, 'Ship'),
(32, 'Germany', 0, 0, 'Ship'),
(33, 'Hungary', 0, 0, 'Ship'),
(34, 'Iran (Islamic Republic of)', 0, 0, 'Ship'),
(35, 'Ireland, Republic of', 0, 0, 'Ship'),
(36, 'Italy', 1, 0, 'Ship'),
(37, 'Jordan', 0, 0, 'Ship'),
(38, 'Kuwait', 1, 0, 'Ship'),
(39, 'Laos', 0, 0, 'Ship'),
(40, 'Latvia', 0, 0, 'Ship'),
(41, 'Liechtenstein', 0, 0, 'Ship'),
(42, 'Lithuania', 0, 0, 'Ship'),
(43, 'Luxembourg', 0, 0, 'Ship'),
(44, 'Malta', 0, 0, 'Ship'),
(45, 'Mexico', 0, 0, 'Ship'),
(46, 'Netherlands', 0, 0, 'Ship'),
(47, 'New Zealand', 0, 0, 'Ship'),
(48, 'Oman', 0, 0, 'Ship'),
(49, 'Poland', 0, 0, 'Ship'),
(50, 'Portugal', 0, 0, 'Ship'),
(51, 'Qatar', 1, 0, 'Ship'),
(52, 'Romania', 0, 0, 'Ship'),
(53, 'Saudi Arabia', 0, 0, 'Ship'),
(54, 'Slovakia', 0, 0, 'Ship'),
(55, 'Slovenia', 0, 0, 'Ship'),
(56, 'Spain', 0, 0, 'Ship'),
(57, 'Sri Lanka', 1, 0, 'Delivery'),
(58, 'Sweden', 0, 0, 'Ship'),
(59, 'Switzerland', 0, 0, 'Ship'),
(60, 'United Arab Emirates', 0, 0, 'Ship'),
(61, 'United Kingdom', 0, 0, 'Ship'),
(62, 'United States', 0, 0, 'Ship');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_codes`
--

CREATE TABLE `coupon_codes` (
  `id` int(10) NOT NULL,
  `code` varchar(20) NOT NULL,
  `type` enum('PCT','SUM') NOT NULL DEFAULT 'PCT',
  `rate` float(22,2) NOT NULL DEFAULT 0.00,
  `offer_value` float(22,2) NOT NULL DEFAULT 0.00,
  `limited` int(10) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `coupon_codes`
--

INSERT INTO `coupon_codes` (`id`, `code`, `type`, `rate`, `offer_value`, `limited`, `visible`, `user_id`) VALUES
(1, 'Test60', 'PCT', 5.00, 1000.00, 8, 0, 0),
(2, 'Open', 'PCT', 5.00, 5000.00, 1, 0, 0),
(3, 'open offer', 'PCT', 5.00, 10000.00, 1, 0, 0),
(4, 'COD25', 'PCT', 25.00, 1000.00, 10, 0, 0),
(5, '4456', 'PCT', 5.00, 100.00, 1, 1, 0),
(6, 'test', 'PCT', 5.00, 1000.00, 2, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `crm_category_master`
--

CREATE TABLE `crm_category_master` (
  `category_id` int(11) NOT NULL,
  `segment_id` int(11) NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `crm_company_master`
--

CREATE TABLE `crm_company_master` (
  `company_id` int(11) NOT NULL,
  `company_code` varchar(30) NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `company_type` varchar(100) NOT NULL,
  `segment_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sales_person_id` int(11) DEFAULT NULL,
  `contact_details` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_company_master`
--

INSERT INTO `crm_company_master` (`company_id`, `company_code`, `company_name`, `company_type`, `segment_id`, `category_id`, `sales_person_id`, `contact_details`, `address`, `created_at`, `updated_at`) VALUES
(1, 'COM-20260402-6733', 'POS360', 'IT', NULL, NULL, NULL, '', '', '2026-04-02 04:18:05', '2026-04-02 04:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `crm_company_person`
--

CREATE TABLE `crm_company_person` (
  `company_person_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `person_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_company_person`
--

INSERT INTO `crm_company_person` (`company_person_id`, `company_id`, `person_id`, `created_at`) VALUES
(1, 1, 1, '2026-04-02 04:18:05');

-- --------------------------------------------------------

--
-- Table structure for table `crm_designation_master`
--

CREATE TABLE `crm_designation_master` (
  `designation_id` int(11) NOT NULL,
  `designation_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_designation_master`
--

INSERT INTO `crm_designation_master` (`designation_id`, `designation_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Transport Oparator', NULL, '2026-04-03 09:45:50', '2026-04-03 09:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `crm_opportunity`
--

CREATE TABLE `crm_opportunity` (
  `opportunity_id` int(11) NOT NULL,
  `opportunity_code` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `person_id` int(11) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `sales_cycle_id` int(11) DEFAULT NULL,
  `current_sales_cycle_stage_id` int(11) DEFAULT NULL,
  `segment_id` int(11) DEFAULT NULL,
  `sales_person_id` int(11) DEFAULT NULL,
  `contact_no` varchar(30) NOT NULL,
  `contact_name` varchar(180) NOT NULL,
  `phone_no` varchar(50) DEFAULT NULL,
  `mobile_phone_no` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_company_name` varchar(180) DEFAULT NULL,
  `sales_document_type` varchar(100) DEFAULT NULL,
  `sales_document_no` varchar(100) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'In Progress',
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `creation_date` date DEFAULT NULL,
  `date_closed` date DEFAULT NULL,
  `estimated_sales_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `chance_of_success_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `estimated_closing_date_for_stage` date DEFAULT NULL,
  `estimated_gp` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_opportunity`
--

INSERT INTO `crm_opportunity` (`opportunity_id`, `opportunity_code`, `description`, `person_id`, `company_id`, `sales_cycle_id`, `current_sales_cycle_stage_id`, `segment_id`, `sales_person_id`, `contact_no`, `contact_name`, `phone_no`, `mobile_phone_no`, `email`, `contact_company_name`, `sales_document_type`, `sales_document_no`, `status`, `is_closed`, `creation_date`, `date_closed`, `estimated_sales_value`, `chance_of_success_percent`, `estimated_closing_date_for_stage`, `estimated_gp`, `created_at`, `updated_at`) VALUES
(1, 'OPP-20260403-4142', 'Engine Rapir', 1, 1, 1, 2, NULL, NULL, 'PER-20260402-9144', 'malith', '', '771998880', '', 'POS360', 'Order', '22321', 'In Progress', 0, '2026-04-03', NULL, '0.00', '20.00', '2026-04-03', '20.00', '2026-04-03 10:24:09', '2026-04-03 11:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `crm_opportunity_update`
--

CREATE TABLE `crm_opportunity_update` (
  `opportunity_update_id` int(11) NOT NULL,
  `opportunity_id` int(11) NOT NULL,
  `action_type` varchar(30) NOT NULL DEFAULT 'Current',
  `sales_cycle_stage_id` int(11) NOT NULL,
  `date_of_change` date NOT NULL,
  `estimated_sales_value` decimal(14,2) NOT NULL DEFAULT 0.00,
  `chance_of_success_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `estimated_closing_date_for_stage` date DEFAULT NULL,
  `opportunity_closing_date` date DEFAULT NULL,
  `cancel_existing_open_tasks` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_opportunity_update`
--

INSERT INTO `crm_opportunity_update` (`opportunity_update_id`, `opportunity_id`, `action_type`, `sales_cycle_stage_id`, `date_of_change`, `estimated_sales_value`, `chance_of_success_percent`, `estimated_closing_date_for_stage`, `opportunity_closing_date`, `cancel_existing_open_tasks`, `created_at`, `updated_at`) VALUES
(1, 1, 'Next', 1, '2026-04-03', '0.00', '10.00', '2026-04-03', NULL, 0, '2026-04-03 10:24:27', '2026-04-03 10:24:27'),
(2, 1, 'Next', 1, '2026-04-03', '0.00', '10.00', '2026-04-03', NULL, 0, '2026-04-03 10:24:30', '2026-04-03 10:24:30'),
(3, 1, 'Previous', 1, '2026-04-03', '0.00', '10.00', '2026-04-03', NULL, 0, '2026-04-03 11:27:18', '2026-04-03 11:27:18'),
(4, 1, 'Next', 2, '2026-04-03', '0.00', '20.00', '2026-04-03', NULL, 0, '2026-04-03 11:27:21', '2026-04-03 11:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `crm_person_master`
--

CREATE TABLE `crm_person_master` (
  `person_id` int(11) NOT NULL,
  `person_code` varchar(30) NOT NULL,
  `title` varchar(10) NOT NULL DEFAULT 'Mr',
  `contact_name` varchar(150) NOT NULL,
  `contact_no` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `designation` varchar(150) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_person_master`
--

INSERT INTO `crm_person_master` (`person_id`, `person_code`, `title`, `contact_name`, `contact_no`, `email`, `address`, `designation`, `designation_id`, `created_at`, `updated_at`) VALUES
(1, 'PER-20260402-9144', 'Mr', 'malith', '771998880', NULL, '', 'Transport Oparator', 1, '2026-04-02 04:17:41', '2026-04-03 09:45:50');

-- --------------------------------------------------------

--
-- Table structure for table `crm_sales_cycle_master`
--

CREATE TABLE `crm_sales_cycle_master` (
  `sales_cycle_id` int(11) NOT NULL,
  `cycle_code` varchar(50) NOT NULL,
  `cycle_description` varchar(255) NOT NULL,
  `probability_calculation` varchar(100) NOT NULL DEFAULT 'Chances of Success %',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_sales_cycle_master`
--

INSERT INTO `crm_sales_cycle_master` (`sales_cycle_id`, `cycle_code`, `cycle_description`, `probability_calculation`, `created_at`, `updated_at`) VALUES
(1, 'TESTSD2', 'dasda', 'Chances of Success %', '2026-04-03 09:58:26', '2026-04-03 09:59:01'),
(2, 'TESTSD3', 'dasda', 'Chances of Success %', '2026-04-03 09:59:15', '2026-04-03 09:59:15');

-- --------------------------------------------------------

--
-- Table structure for table `crm_sales_cycle_stage`
--

CREATE TABLE `crm_sales_cycle_stage` (
  `sales_cycle_stage_id` int(11) NOT NULL,
  `sales_cycle_id` int(11) NOT NULL,
  `stage_no` int(11) NOT NULL,
  `stage_description` varchar(255) NOT NULL,
  `completed_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `chance_of_success_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `activity_code` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `crm_sales_cycle_stage`
--

INSERT INTO `crm_sales_cycle_stage` (`sales_cycle_stage_id`, `sales_cycle_id`, `stage_no`, `stage_description`, `completed_percent`, `chance_of_success_percent`, `activity_code`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'efs', '10.00', '10.00', 'eeer', '2026-04-03 09:58:45', '2026-04-03 09:58:45'),
(2, 1, 2, 'dasda', '20.00', '20.00', 'ssd3', '2026-04-03 11:27:07', '2026-04-03 11:27:07');

-- --------------------------------------------------------

--
-- Table structure for table `crm_sales_person_master`
--

CREATE TABLE `crm_sales_person_master` (
  `sales_person_id` int(11) NOT NULL,
  `sales_person_name` varchar(150) NOT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `crm_segment_master`
--

CREATE TABLE `crm_segment_master` (
  `segment_id` int(11) NOT NULL,
  `segment_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `currency`
--

CREATE TABLE `currency` (
  `currency_id` int(10) NOT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `activated` enum('N','Y') NOT NULL DEFAULT 'N',
  `rate` double(22,4) NOT NULL DEFAULT 0.0000,
  `primary_store_currency` int(1) NOT NULL DEFAULT 0,
  `primary_exchange_rate_currency` int(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `currency`
--

INSERT INTO `currency` (`currency_id`, `currency`, `activated`, `rate`, `primary_store_currency`, `primary_exchange_rate_currency`) VALUES
(1, 'AUD', 'Y', 1.0000, 1, 1),
(2, 'USD', 'N', 0.0055, 0, 0),
(10, 'LKR', 'N', 360.0000, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(10) NOT NULL,
  `customer_code` varchar(30) DEFAULT NULL,
  `customer_email` varchar(50) DEFAULT NULL,
  `customer_password` varchar(250) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `customer_title` varchar(10) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `legal_name` varchar(150) DEFAULT NULL,
  `trading_name` varchar(150) DEFAULT NULL,
  `customer_nic` varchar(15) NOT NULL,
  `customer_avtive_code` text NOT NULL,
  `customer_address` text DEFAULT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `customer_discount` double(20,0) DEFAULT 0,
  `customer_tell` varchar(50) DEFAULT NULL,
  `customer_mobile` varchar(50) DEFAULT NULL,
  `customer_note` text DEFAULT NULL,
  `customer_remarks` text DEFAULT NULL,
  `customer_outstanding_balance` double(20,2) DEFAULT 0.00,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `account_hold` tinyint(1) NOT NULL DEFAULT 0,
  `abn_no` varchar(32) DEFAULT NULL,
  `acn_no` varchar(32) DEFAULT NULL,
  `vat_registered` tinyint(1) NOT NULL DEFAULT 0,
  `gst_no` varchar(32) DEFAULT NULL,
  `payment_terms_id` int(10) DEFAULT NULL,
  `customer_logo` varchar(255) DEFAULT NULL,
  `customer_price_type_id` int(10) DEFAULT NULL,
  `new_customer` int(1) NOT NULL DEFAULT 0,
  `country` varchar(64) DEFAULT NULL,
  `state` varchar(64) DEFAULT NULL,
  `RepeatInterval` int(11) DEFAULT NULL COMMENT 'e.g., 7',
  `RepeatUnit` int(11) DEFAULT NULL,
  `min_order_amount` decimal(12,2) DEFAULT 0.00,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_email` varchar(100) DEFAULT NULL,
  `emergency_contact_telephone` varchar(50) DEFAULT NULL,
  `custom_url_link` varchar(255) DEFAULT NULL,
  `google_map_link` varchar(255) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_telephone` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `customer_code`, `customer_email`, `customer_password`, `is_active`, `locked`, `customer_title`, `customer_name`, `legal_name`, `trading_name`, `customer_nic`, `customer_avtive_code`, `customer_address`, `address_line_1`, `address_line_2`, `city`, `postal_code`, `customer_discount`, `customer_tell`, `customer_mobile`, `customer_note`, `customer_remarks`, `customer_outstanding_balance`, `credit_limit`, `account_hold`, `abn_no`, `acn_no`, `vat_registered`, `gst_no`, `payment_terms_id`, `customer_logo`, `customer_price_type_id`, `new_customer`, `country`, `state`, `RepeatInterval`, `RepeatUnit`, `min_order_amount`, `emergency_contact_name`, `emergency_contact_email`, `emergency_contact_telephone`, `custom_url_link`, `google_map_link`, `contact_name`, `contact_email`, `contact_telephone`) VALUES
(241, 'CUST-20260222-5508', 'rao@Gun.com', 'asd', 1, 0, NULL, 'Rao G', NULL, NULL, '1122334455', '83df18fe29d3628b856bb7343a2b637f', NULL, '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 1234567, 456789345, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, 'western', NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(289, '289u', 'malith.sachinthana@gmail.com', 'asd', 0, 0, NULL, 'Malith SachinthanaDD', NULL, NULL, '', '4c65d65266c4a1432177ab4c74c0c43a', NULL, '375/12 , 8th lane, Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 77199880, 2147483647, NULL, NULL, 0.00, '50.00', 0, NULL, NULL, 1, NULL, 3, '../images/customer_logo/customer_289_1763477468.png', 1, 1, NULL, 'West', 2, 3, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(307, 'CUST-20260222-6595', 'amarasinghe.upul@gmail.com', '123', 1, 0, NULL, 'JAMES BOND', NULL, NULL, '', 'dc1a4fd6c32389fa8fbf6b0d6c85f0ab', NULL, '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 'western', NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(305, NULL, 'gunneshwar@gmail.com', '1234', 0, 0, NULL, 'VSG Bha', NULL, NULL, '', 'e126bdc157efc7397f7ee28a6e507401', NULL, NULL, NULL, NULL, NULL, 0, 0, 493941449, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(306, NULL, 'malith.sachinthana@gmail.com', 'asd', 1, 0, NULL, 'malith sachinthana', NULL, NULL, '', 'e48ea21f1e607435f64b28eba53ab095', NULL, NULL, NULL, NULL, NULL, 0, 2147483647, 2147483647, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(308, 'CUST-00308', 'malith.sachinthana@gmail.com', '', 1, 0, NULL, 'Malith Sachinthana', 'teasd', 'rsdas', '', '', '375/12 , 8th lane, Rathnarama Road Hokandara North', '375/12 , 8th lane, Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, 771998880, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(309, 'CUST-0033', 'malith.sachinthana@gmail.com', '', 1, 0, NULL, 'Malith Sachinthana', 'teasd', 'rsdas', '', '', '375/12 , 8th lane, Rathnarama Road Hokandara North', '375/12 , 8th lane, Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, 771998880, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(310, 'TSTPG-20260125-1', 'test.page.1@example.com', 'test', 1, 0, NULL, 'Test Page Cust 1', NULL, NULL, '0000000310', '961e31b59c1c2d1be9e9f9ee9251ce68', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(311, 'TSTPG-20260125-2', 'test.page.2@example.com', 'test', 1, 0, NULL, 'Test Page Cust 2', NULL, NULL, '0000000311', '743759dbe12ff757bb583fe586d08a9d', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(312, 'TSTPG-20260125-3', 'test.page.3@example.com', 'test', 1, 0, NULL, 'Test Page Cust 3', NULL, NULL, '0000000312', 'db1a68e1217525c5bbb5a5552d0823fe', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(313, 'TSTPG-20260125-4', 'test.page.4@example.com', 'test', 1, 0, NULL, 'Test Page Cust 4', NULL, NULL, '0000000313', '1836c79c430a2a4ee0ed616ef496b4f8', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(314, 'TSTPG-20260125-5', 'test.page.5@example.com', 'test', 1, 0, NULL, 'Test Page Cust 5', NULL, NULL, '0000000314', 'd5c454c9a623c22c79a0017c1be2f013', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(315, 'TSTPG-20260125-6', 'test.page.6@example.com', 'test', 1, 0, NULL, 'Test Page Cust 6', NULL, NULL, '0000000315', 'ef6b3b80b628b556a6a6bd98572eb574', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(316, 'TSTPG-20260125-7', 'test.page.7@example.com', 'test', 1, 0, NULL, 'Test Page Cust 7', NULL, NULL, '0000000316', 'a11c4b8388ad871749c2ffabfb03294e', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(317, 'TSTPG-20260125-8', 'test.page.8@example.com', 'test', 1, 0, NULL, 'Test Page Cust 8', NULL, NULL, '0000000317', '5ffcdd5b98c823dc1d30fb3b1f3f64d8', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(318, 'TSTPG-20260125-9', 'test.page.9@example.com', 'test', 1, 0, NULL, 'Test Page Cust 9', NULL, NULL, '0000000318', '82e978a476727af4348922cf5a34bc73', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(319, 'TSTPG-20260125-10', 'test.page.10@example.com', 'test', 1, 0, NULL, 'Test Page Cust 10', NULL, NULL, '0000000319', '6a87b97b3bd3a5fd60473d37b03a5cc2', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(320, 'TSTPG-20260125-11', 'test.page.11@example.com', 'test', 1, 0, NULL, 'Test Page Cust 11', NULL, NULL, '0000000320', 'e71453e3caf4d7690a1502a043aff461', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(321, 'TSTPG-20260125-12', 'test.page.12@example.com', 'test', 1, 0, NULL, 'Test Page Cust 12', NULL, NULL, '0000000321', 'dbb285622889a96d5256b5af184e00fe', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(322, 'TSTPG-20260125-13', 'test.page.13@example.com', 'test', 1, 0, NULL, 'Test Page Cust 13', NULL, NULL, '0000000322', '69a9b9ef4175ecc0955afb5e0468d244', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(323, 'TSTPG-20260125-14', 'test.page.14@example.com', 'test', 1, 0, NULL, 'Test Page Cust 14', NULL, NULL, '0000000323', '48d51b7fdb2e8be805577a133d61e485', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(324, 'TSTPG-20260125-15', 'test.page.15@example.com', 'test', 1, 0, NULL, 'Test Page Cust 15', NULL, NULL, '0000000324', '68eb743b722f0cf630f703a68921ca41', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(325, 'TSTPG-20260125-16', 'test.page.16@example.com', 'test', 1, 0, NULL, 'Test Page Cust 16', NULL, NULL, '0000000325', 'af29c36635f48211317b7a2518357f92', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(326, 'TSTPG-20260125-17', 'test.page.17@example.com', 'test', 1, 0, NULL, 'Test Page Cust 17', NULL, NULL, '0000000326', 'e7ed5e1f689d2b85b680a613d3c330b6', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(327, 'TSTPG-20260125-18', 'test.page.18@example.com', 'test', 1, 0, NULL, 'Test Page Cust 18', NULL, NULL, '0000000327', '6f7ec4cf79598396b61203d69cc78436', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(328, 'TSTPG-20260125-19', 'test.page.19@example.com', 'test', 1, 0, NULL, 'Test Page Cust 19', NULL, NULL, '0000000328', 'ac0727532d7a42e0888972dd46f95bfe', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(329, 'TSTPG-20260125-20', 'test.page.20@example.com', 'test', 1, 0, NULL, 'Test Page Cust 20', NULL, NULL, '0000000329', '544ecf8383385615ddaae9e5b075f76b', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(330, 'CUST-00330', 'lankagreenhost99@gmail.com', '', 0, 0, NULL, 'fadsa', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(331, 'SEEDCUST-00331', 'sample.customer.331@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 1', NULL, NULL, '0000000331', '4eb73058684f51d25299bb35540c198a', 'No. 100, Sample Street', 'No. 100, Sample Street', NULL, 'Colombo', '10000', 0, 701000000, 701000000, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 1', 'sample.customer.331@example.com', '0701000000'),
(332, 'SEEDCUST-00332', 'sample.customer.332@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 2', NULL, NULL, '0000000332', 'b1d5b5310489126583d2d4ed29479b32', 'No. 101, Sample Street', 'No. 101, Sample Street', NULL, 'Colombo', '10000', 0, 701000001, 701000001, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 2', 'sample.customer.332@example.com', '0701000001'),
(333, 'SEEDCUST-00333', 'sample.customer.333@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 3', NULL, NULL, '0000000333', 'ef22a0db201e27b0d951b7d4b46e198f', 'No. 102, Sample Street', 'No. 102, Sample Street', NULL, 'Colombo', '10000', 0, 701000002, 701000002, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 3', 'sample.customer.333@example.com', '0701000002'),
(334, 'SEEDCUST-00334', 'sample.customer.334@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 4', NULL, NULL, '0000000334', '1808ff243573d1966303c04fee4f72fb', 'No. 103, Sample Street', 'No. 103, Sample Street', NULL, 'Colombo', '10000', 0, 701000003, 701000003, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 4', 'sample.customer.334@example.com', '0701000003'),
(335, 'SEEDCUST-00335', 'sample.customer.335@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 5', NULL, NULL, '0000000335', 'f673b8f60529f4a815e6a336bd8e5fdf', 'No. 104, Sample Street', 'No. 104, Sample Street', NULL, 'Colombo', '10000', 0, 701000004, 701000004, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 5', 'sample.customer.335@example.com', '0701000004'),
(336, 'SEEDCUST-00336', 'sample.customer.336@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 6', NULL, NULL, '0000000336', '6c025c7557b589e982dc5e117babe9c0', 'No. 105, Sample Street', 'No. 105, Sample Street', NULL, 'Colombo', '10000', 0, 701000005, 701000005, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 6', 'sample.customer.336@example.com', '0701000005'),
(337, 'SEEDCUST-00337', 'sample.customer.337@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 7', NULL, NULL, '0000000337', 'cb91cba5179fcb5b3dc41ee618e49e5b', 'No. 106, Sample Street', 'No. 106, Sample Street', NULL, 'Colombo', '10000', 0, 701000006, 701000006, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 7', 'sample.customer.337@example.com', '0701000006'),
(338, 'SEEDCUST-00338', 'sample.customer.338@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 8', NULL, NULL, '0000000338', 'f5f1679f430e26d33c17b5a205ec137e', 'No. 107, Sample Street', 'No. 107, Sample Street', NULL, 'Colombo', '10000', 0, 701000007, 701000007, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 8', 'sample.customer.338@example.com', '0701000007'),
(339, 'SEEDCUST-00339', 'sample.customer.339@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 9', NULL, NULL, '0000000339', '89a78bae7ddf4cff86f7bdd217a555fe', 'No. 108, Sample Street', 'No. 108, Sample Street', NULL, 'Colombo', '10000', 0, 701000008, 701000008, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 9', 'sample.customer.339@example.com', '0701000008'),
(340, 'SEEDCUST-00340', 'sample.customer.340@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 10', NULL, NULL, '0000000340', '4cf213f7b830428dd302171de464e46a', 'No. 109, Sample Street', 'No. 109, Sample Street', NULL, 'Colombo', '10000', 0, 701000009, 701000009, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 10', 'sample.customer.340@example.com', '0701000009'),
(341, 'SEEDCUST-00341', 'sample.customer.341@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 1', NULL, NULL, '0000000341', '616d925c9d2acbb77587e0af0b4a6426', 'No. 100, Sample Street', 'No. 100, Sample Street', NULL, 'Colombo', '10000', 0, 701000000, 701000000, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 1', 'sample.customer.341@example.com', '0701000000'),
(342, 'SEEDCUST-00342', 'sample.customer.342@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 2', NULL, NULL, '0000000342', '40e750c2c7a87cf6f309c8899a36df23', 'No. 101, Sample Street', 'No. 101, Sample Street', NULL, 'Colombo', '10000', 0, 701000001, 701000001, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 2', 'sample.customer.342@example.com', '0701000001'),
(343, 'SEEDCUST-00343', 'sample.customer.343@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 3', NULL, NULL, '0000000343', '25cbba733213d6f14cc5bcc7057ce5b3', 'No. 102, Sample Street', 'No. 102, Sample Street', NULL, 'Colombo', '10000', 0, 701000002, 701000002, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 3', 'sample.customer.343@example.com', '0701000002'),
(344, 'SEEDCUST-00344', 'sample.customer.344@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 4', NULL, NULL, '0000000344', '81938c86bcdc0db79536c173e0e1a190', 'No. 103, Sample Street', 'No. 103, Sample Street', NULL, 'Colombo', '10000', 0, 701000003, 701000003, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 4', 'sample.customer.344@example.com', '0701000003'),
(345, 'SEEDCUST-00345', 'sample.customer.345@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 5', NULL, NULL, '0000000345', '9f17179b775132460157831ab863c0b1', 'No. 104, Sample Street', 'No. 104, Sample Street', NULL, 'Colombo', '10000', 0, 701000004, 701000004, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 5', 'sample.customer.345@example.com', '0701000004'),
(346, 'SEEDCUST-00346', 'sample.customer.346@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 6', NULL, NULL, '0000000346', '5758364d4ffda22c581aed91cbb9703f', 'No. 105, Sample Street', 'No. 105, Sample Street', NULL, 'Colombo', '10000', 0, 701000005, 701000005, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 6', 'sample.customer.346@example.com', '0701000005'),
(347, 'SEEDCUST-00347', 'sample.customer.347@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 7', NULL, NULL, '0000000347', '4169675a667d161fd45bfdc39bd15ec0', 'No. 106, Sample Street', 'No. 106, Sample Street', NULL, 'Colombo', '10000', 0, 701000006, 701000006, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 7', 'sample.customer.347@example.com', '0701000006'),
(348, 'SEEDCUST-00348', 'sample.customer.348@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 8', NULL, NULL, '0000000348', '0da98cbdd72fb2c0e39e1dedc4bd2a09', 'No. 107, Sample Street', 'No. 107, Sample Street', NULL, 'Colombo', '10000', 0, 701000007, 701000007, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 8', 'sample.customer.348@example.com', '0701000007'),
(349, 'SEEDCUST-00349', 'sample.customer.349@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 9', NULL, NULL, '0000000349', '3087911634319f697189898905e98366', 'No. 108, Sample Street', 'No. 108, Sample Street', NULL, 'Colombo', '10000', 0, 701000008, 701000008, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 9', 'sample.customer.349@example.com', '0701000008'),
(350, 'SEEDCUST-00350', 'sample.customer.350@example.com', 'seed123', 1, 0, NULL, 'Sample Customer 10', NULL, NULL, '0000000350', '183b71b64095f8b5211b8c04cf952c24', 'No. 109, Sample Street', 'No. 109, Sample Street', NULL, 'Colombo', '10000', 0, 701000009, 701000009, 'Seeded on 2026-02-19', NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, 'Sample Customer 10', 'sample.customer.350@example.com', '0701000009'),
(351, 'CUST-00333223', 'dsad@gmail.copm', '', 0, 0, NULL, 'CUST-0033322asdada', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(352, 'CUST-00352', 'araliya@gmail.com', '', 0, 0, NULL, 'araliya', NULL, NULL, '', '', 'araliya foods colormbo sri lanka', 'araliya foods colormbo sri lanka', NULL, 'Hokandara', '10118', 0, 771998880, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(353, 'CUST-00353', 'anura@gmail.com', '', 0, 0, NULL, 'Anura DIsa', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 711885544, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', NULL, NULL, NULL, NULL, NULL),
(354, 'CUST-00354', 'araliya@gmail.com', '', 0, 0, NULL, 'araliya', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(355, 'CUST-00355', 'mahinda@gmail.com', '', 0, 0, NULL, 'mahinda raj', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, 771998880, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(356, 'CUST-00356', 'niouna@gmail.com', '', 0, 0, NULL, 'niouna', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(357, 'CUST-00357', 'sanath@gmail.com', '', 0, 0, NULL, 'sanathj', NULL, NULL, '', '', '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 0, 771998880, 771998880, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(358, NULL, 'copilot-reg-test-1775035154@example.com', 'secret', 0, 0, NULL, 'Copilot Test', NULL, NULL, '', 'f023ff08ee163cb419a3c4748dbe5c87', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(359, NULL, 'e.teensolutios@gmail.com', 'asd', 1, 0, NULL, 'Malith Sachinthana', NULL, NULL, '', 'c9843520d1b14338a0fc2a5d6ef248a2', NULL, NULL, NULL, NULL, NULL, 0, NULL, 771998880, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(360, NULL, 'e.teensolution32@gmail.com', 'asd', 1, 0, NULL, 'Malith Sachinthana', NULL, NULL, '', '95788b8ddf0cf14b729a4c54d7f4ce0b', NULL, NULL, NULL, NULL, NULL, 0, NULL, 771998880, NULL, NULL, 0.00, '0.00', 0, NULL, NULL, 0, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_attachments`
--

CREATE TABLE `customer_attachments` (
  `id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `customer_balance`
--

CREATE TABLE `customer_balance` (
  `id` int(10) NOT NULL,
  `code` text NOT NULL,
  `invoice_h_id` int(10) NOT NULL,
  `amount` double(20,2) DEFAULT 0.00,
  `amountDate` datetime NOT NULL,
  `invoice_h_pay_type` int(10) DEFAULT NULL,
  `invoice_h_check_Ref` text DEFAULT NULL,
  `invoice_h_card_Ref` text DEFAULT NULL,
  `makeBy` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customer_balance`
--

INSERT INTO `customer_balance` (`id`, `code`, `invoice_h_id`, `amount`, `amountDate`, `invoice_h_pay_type`, `invoice_h_check_Ref`, `invoice_h_card_Ref`, `makeBy`) VALUES
(1, '', 1, 108.00, '2022-08-14 00:00:00', 8, NULL, NULL, 0),
(2, '', 2, 0.30, '2022-08-14 00:00:00', 8, NULL, NULL, 0),
(3, '', 3, 1.00, '2022-08-14 00:00:00', 8, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `customer_payment_options`
--

CREATE TABLE `customer_payment_options` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_type` enum('card','bank') NOT NULL,
  `card_no` varchar(20) DEFAULT NULL,
  `card_name` varchar(100) DEFAULT NULL,
  `exp_month` int(11) DEFAULT NULL,
  `exp_year` int(11) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `customer_shipping_address`
--

CREATE TABLE `customer_shipping_address` (
  `id` int(10) NOT NULL,
  `customer_id` int(10) NOT NULL,
  `address_label` varchar(50) DEFAULT NULL,
  `address_line_1` varchar(255) NOT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `contact_person_name` varchar(100) DEFAULT NULL,
  `contact_person_email` varchar(100) DEFAULT NULL,
  `contact_person_phone` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `delivery_time_from` time DEFAULT NULL,
  `delivery_time_till` time DEFAULT NULL,
  `has_door_key` tinyint(1) NOT NULL DEFAULT 0,
  `has_shop_alarm` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_route_id` int(11) DEFAULT NULL,
  `attribute_1` varchar(100) DEFAULT NULL,
  `attribute_2` varchar(100) DEFAULT NULL,
  `attribute_3` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `country` varchar(64) DEFAULT NULL,
  `state` varchar(64) DEFAULT NULL,
  `note_to_deliver` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `customer_shipping_address`
--

INSERT INTO `customer_shipping_address` (`id`, `customer_id`, `address_label`, `address_line_1`, `address_line_2`, `city`, `postal_code`, `contact_no`, `contact_person_name`, `contact_person_email`, `contact_person_phone`, `remarks`, `delivery_time_from`, `delivery_time_till`, `has_door_key`, `has_shop_alarm`, `delivery_route_id`, `attribute_1`, `attribute_2`, `attribute_3`, `is_default`, `created_at`, `updated_at`, `country`, `state`, `note_to_deliver`) VALUES
(65, 308, 'Malith Sachinthana - Primary', '375/12 , 8th lane, Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2025-11-26 23:26:01', '2025-11-26 23:26:01', NULL, NULL, NULL),
(64, 289, 'Outlet 001', '375/12 , 8th lane, Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 0, '2025-11-23 21:52:26', '2025-11-23 22:28:27', 'United States', 'western', NULL),
(62, 289, 'Outlet 002', '375/12,', 'rathnarama Road Hokandara', 'Hokandara North', '10118', '771998880', NULL, NULL, NULL, NULL, '21:54:00', '21:54:00', 1, 1, 3, NULL, NULL, NULL, 1, '2025-11-23 21:52:26', '2025-11-23 22:28:32', NULL, 'West', NULL),
(63, 289, 'Outlet 003', '375/12,', 'rathnarama Road Hokandara', 'Hokandara North', '10118', '771998880', NULL, NULL, NULL, NULL, '20:39:00', '20:41:00', 1, 1, 2, NULL, NULL, NULL, 0, '2025-11-23 21:52:26', '2025-11-23 22:28:37', NULL, 'North', NULL),
(114, 353, 'Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 08:11:48', '2026-02-20 08:11:48', NULL, NULL, NULL),
(67, 241, 'Default Address', '123 Test Street', 'Apt 1', 'Test City', '12345', '123-456-7890', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 1, NULL, NULL, NULL, 1, '2025-11-28 16:52:43', '2026-02-25 11:45:07', NULL, NULL, NULL),
(68, 330, 'fadsa - Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-12 01:03:33', '2026-02-12 01:03:33', NULL, NULL, NULL),
(69, 331, 'Primary', 'No. 100, Sample Street', NULL, 'Colombo', '10000', '0701000000', 'Sample Customer 1', 'sample.customer.331@example.com', '0701000000', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(70, 332, 'Primary', 'No. 101, Sample Street', NULL, 'Colombo', '10000', '0701000001', 'Sample Customer 2', 'sample.customer.332@example.com', '0701000001', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(71, 333, 'Primary', 'No. 102, Sample Street', NULL, 'Colombo', '10000', '0701000002', 'Sample Customer 3', 'sample.customer.333@example.com', '0701000002', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(72, 334, 'Primary', 'No. 103, Sample Street', NULL, 'Colombo', '10000', '0701000003', 'Sample Customer 4', 'sample.customer.334@example.com', '0701000003', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(89, 335, 'Primary', 'No. 104, Sample Street', NULL, 'Colombo', '10000', '0701000004', 'Sample Customer 5', 'sample.customer.335@example.com', '0701000004', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:43:34', '2026-02-19 11:43:34', NULL, NULL, NULL),
(74, 336, 'Primary', 'No. 105, Sample Street', NULL, 'Colombo', '10000', '0701000005', 'Sample Customer 6', 'sample.customer.336@example.com', '0701000005', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(75, 337, 'Primary', 'No. 106, Sample Street', NULL, 'Colombo', '10000', '0701000006', 'Sample Customer 7', 'sample.customer.337@example.com', '0701000006', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(76, 338, 'Primary', 'No. 107, Sample Street', NULL, 'Colombo', '10000', '0701000007', 'Sample Customer 8', 'sample.customer.338@example.com', '0701000007', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(77, 339, 'Primary', 'No. 108, Sample Street', NULL, 'Colombo', '10000', '0701000008', 'Sample Customer 9', 'sample.customer.339@example.com', '0701000008', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(78, 340, 'Primary', 'No. 109, Sample Street', NULL, 'Colombo', '10000', '0701000009', 'Sample Customer 10', 'sample.customer.340@example.com', '0701000009', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:18:40', '2026-02-19 11:18:40', NULL, NULL, NULL),
(79, 341, 'Primary', 'No. 100, Sample Street', NULL, 'Colombo', '10000', '0701000000', 'Sample Customer 1', 'sample.customer.341@example.com', '0701000000', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(80, 342, 'Primary', 'No. 101, Sample Street', NULL, 'Colombo', '10000', '0701000001', 'Sample Customer 2', 'sample.customer.342@example.com', '0701000001', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(81, 343, 'Primary', 'No. 102, Sample Street', NULL, 'Colombo', '10000', '0701000002', 'Sample Customer 3', 'sample.customer.343@example.com', '0701000002', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(82, 344, 'Primary', 'No. 103, Sample Street', NULL, 'Colombo', '10000', '0701000003', 'Sample Customer 4', 'sample.customer.344@example.com', '0701000003', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(83, 345, 'Primary', 'No. 104, Sample Street', NULL, 'Colombo', '10000', '0701000004', 'Sample Customer 5', 'sample.customer.345@example.com', '0701000004', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(84, 346, 'Primary', 'No. 105, Sample Street', NULL, 'Colombo', '10000', '0701000005', 'Sample Customer 6', 'sample.customer.346@example.com', '0701000005', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(85, 347, 'Primary', 'No. 106, Sample Street', NULL, 'Colombo', '10000', '0701000006', 'Sample Customer 7', 'sample.customer.347@example.com', '0701000006', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(86, 348, 'Primary', 'No. 107, Sample Street', NULL, 'Colombo', '10000', '0701000007', 'Sample Customer 8', 'sample.customer.348@example.com', '0701000007', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(87, 349, 'Primary', 'No. 108, Sample Street', NULL, 'Colombo', '10000', '0701000008', 'Sample Customer 9', 'sample.customer.349@example.com', '0701000008', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(88, 350, 'Primary', 'No. 109, Sample Street', NULL, 'Colombo', '10000', '0701000009', 'Sample Customer 10', 'sample.customer.350@example.com', '0701000009', 'Default address created by seed script', '09:00:00', '17:00:00', 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-19 11:19:37', '2026-02-19 11:19:37', NULL, NULL, NULL),
(112, 309, 'Office Address', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', 'Malith Sachinthana', 'lankagreenhost99@gmail.com', NULL, NULL, '02:47:00', '08:47:00', 0, 0, 2, NULL, NULL, NULL, 1, '2026-02-20 02:52:00', '2026-02-20 02:52:00', NULL, 'western', NULL),
(113, 352, 'araliya - Primary', 'araliya foods colormbo sri lanka', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 08:09:27', '2026-02-20 08:09:27', NULL, NULL, NULL),
(111, 351, 'CUST-0033322asdada - Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 02:48:20', '2026-02-20 02:48:20', NULL, NULL, NULL),
(115, 354, 'araliya - Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 08:21:29', '2026-02-20 08:21:29', NULL, NULL, NULL),
(116, 355, 'mahinda raj - Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 08:42:17', '2026-02-20 08:42:17', NULL, NULL, NULL),
(117, 356, 'niouna - Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 09:37:18', '2026-02-20 09:37:18', NULL, NULL, NULL),
(118, 357, 'sanathj - Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0771998880', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-20 09:53:04', '2026-02-20 09:53:04', NULL, NULL, NULL),
(119, 307, 'Primary', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', '0', 'Malith Sachinthana', 'lankagreenhost99@gmail.com', NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, 1, '2026-02-22 23:27:08', '2026-02-22 23:27:08', NULL, 'western', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `custompage`
--

CREATE TABLE `custompage` (
  `id` int(10) NOT NULL,
  `pageUrl` varchar(100) DEFAULT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_sinhala_ci DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `custompage`
--

INSERT INTO `custompage` (`id`, `pageUrl`, `body`) VALUES
(1, 'About-us', '<br><br>\r\n<h3 class=\"ps-section__title\">OUR STORY</h3><br>\r\n<div class=\"ps-section__desc\">While marketing our products through the trade over the year we noticed that some or the most of the customers were having a difficulty in finding the required products \r\non the shelf at the real time and then we had the urge of serving the customers directly to avoid further disappointments, that is where PURE BEAUTY ITALY .was launched.</div>\r\n<br>\r\n<div class=\"ps-section__desc\">Today the PURE BEAUTY ITALY is one of the leading if not the one and only digital marketing company exclusively dedicated to market Premium and award winning \r\ncosmetics brands and essential products. Strives to be the biggest online shopping store for cosmetics product Italy , offering the most varied beauty and healthcare \r\nproducts range including a range of Vegan and Cruelty free products that are nontoxic. Most importantly presenting a unique online shopping experience to our customers.\r\n</div>\r\n<br>\r\n<div class=\"ps-section__desc\">We will be supplemented with an efficient, reliable and cost-effective distribution network which strives hard to exceed customer expectations. Cutting edg\r\ne e-commerce website with exclusive and premium product ranges for 24 hours shopping experience with multiple payment options, equipped with fully loaded \r\nsocial media team and the state-of-the-art Call center to guide you for the right and most suitable product(s) all the time.\r\n</div>\r\n<br>\r\n<h3 class=\"ps-section__title\">The  PURE BEAUTY ITALY.COM way</h3>\r\n<br>\r\n<div class=\"ps-section__desc\">Always believe in a young and thriving team when creating the  PURE BEAUTY ITALY.COM and we believe in a solid gender balance and perspective diversity across all levels.\r\n</div>\r\n<br>\r\n<h3 class=\"ps-section__title\">Our Vision</h3>\r\n<br>\r\n<div class=\"ps-section__desc\">“To be the market leader in providing an easy access for Beauty, Wellness & Personal Care products and personalized services.”\r\n </div>\r\n<br>\r\n\r\n<h3 class=\"ps-section__title\">Mission</h3>\r\n<br>\r\n<div class=\"ps-section__desc\">Enhance beauty and confidence in our clients by giving superior products and services.\r\nBuild and Maintain a strong & true relationship with our employees, suppliers and most importantly with customers in terms of marketing & distribution.\r\nProvide an excellent customer service through a skilled & professional sales and marketing team\r\n</div>\r\n<br><br>\r\n\r\n\r\nOur Values<br>\r\nINTERGRITY<br>\r\nPROFESSIONALISM<br>\r\nCARING<br>\r\nPARTNERSHIP<br>\r\nTEAMWORK<br>'),
(2, 'GRP', '<br><br>\r\n<h3 class=\"ps-section__title\">CUSTOMER COMPLAINTS / GOODS RETURN POLICY</h3>\r\n<br>\r\n<div class=\"ps-section__desc\">Product(s) purchased from our eCommerce site is/are should be examined physically at the real-time of the delivery in the presence of the rider.\r\n If there’s a complained product(s), \r\nYou are also kindly advised to log a complaint to one of the following contact numbers and obtain an ID number for your complaint about the follow-up.</div>\r\n\r\n<br>\r\n<b>\r\nHot Line – 0039 3518089016 / 0039 3278237191<br>\r\nMobile – 0039 3278237191<br>\r\nAlso please note that any complaint (s) receiving afterward will not be entertained<br>\r\n </b>\r\n<br>\r\n<div class=\"ps-section__desc\">Change of the formulation will be accepted in a situation where the appearance has unusually changed or when it’s in bad odor. In a Special case – you are eligible\r\n</div>\r\n<br>\r\n<div class=\"ps-section__desc\">to exchange the product(s) within three (3) days after receiving the product(s).\r\nInsufficient product Shelf Life – Short Expiry.</div>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">In a change of mind, the product could be exchanged with another product(s) / brand within three (7) days after receiving. Goods must be returned to us in a\r\n</div>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">saleable condition with all original packaging to qualify. For hygiene reasons, we regret we cannot offer refunds or exchanges for goods that are not in a re-sellable condition.\r\n</div>\r\n<br>\r\n<div class=\"ps-section__desc\">All products under the warranties will be in order to obtain the after-sale service for any quality defect(s) which could occur during the warranty period.\r\n</div>\r\n<br>\r\n<h3 class=\"ps-section__title\">Clothings</h3>\r\n<div class=\"ps-section__desc\">\r\nAll garment products could be exchanged within three (7) days and should be in a hygiene condition.</div> <br><br>\r\n\r\n'),
(3, 'TOC', '<br><br>\r\n<h3 class=\"ps-section__title\">Terms & Conditions (T&C) for an Incident of an Allergy Reaction</h3>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">It is understood that sometimes certain products do not agree with your skin type, therefore, you are kindly requested to carefully look into the nature of the skin and\r\n</div>\r\n<br>\r\n<div class=\"ps-section__desc\">communicate with our agent accordingly. Also watchfully follow up the given product instructions on the package prior to using any of the cosmetic product(s), especially if it’s a first-time use\r\n</div>\r\n<br>\r\n<div class=\"ps-section__desc\">All reactions must be reported within the first 48 hours of use and PURE BEAUTY ITALY  will arrange a refund or replacement with a suitable product with the individual\r\nproduct that caused the Allergic Reaction.</div>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">Also please note that all instructions, ingredients, opinions, and suggestions regarding products on our site have been provided by the manufacturers or distributors. If\r\nyou have any concerns regarding the safety of certain ingredients, please contact your doctor or you can contact us for further information.</div>\r\n\r\n\r\n\r\n<br>\r\n<h3 class=\"ps-section__title\">Delivery Charges will not be borne from PURE BEAUTY ITALY  for returns.</h3>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">The Customer should inform about the complaint to our hotline or relevant merchant & after that the merchant should inform it to customer care department and\r\nthe customer complaint department will handle the complaint.</div>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">If there are no sufficient stocks for the replacement, we will arrange a fund transfer after an agreement with the customer.</div>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">Your thesis statement is the most vital element that you write in the essay. This is by far the most crucial element of the essay. You must ensure that you write the thesis\r\n\r\nstrong and check your essay prior to sending. Here are some tips to get you started on the process of writing your essay. Best Online Essay Service Start by selecting a\r\n\r\nsuitable topic and forming a solid thesis statement. Then, you should write your body and conclusion. This will help you avoid creating any mistakes on your route.</div>\r\n<br><br>'),
(4, 'Delivery', '<br><br>\r\n<h3 class=\"ps-section__title\">DELIVERIES –</h3>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">All orders collected and confirmed on each day before 5.00 PM will take all our best efforts to deliver within a minimum time and, deliveries will be arranged as per the below given.\r\n</div>\r\n\r\n<br>\r\n<div class=\"ps-section__desc\">Napoli all Metro Stations  – Orders will be delivered in the first 24hours excluding the Sundays and Mercantile holidays.\r\n</div>\r\n<br>\r\n<div class=\"ps-section__desc\">Outstation – Maximum within 4 days excluding Sundays and Mercantile<br>\r\nIsland-wide free delivery for orders above Rs. 200E<br><br>\r\nFor orders below  200E  Outstation Delivery 9E<br></div>\r\n<br><br>');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_area`
--

CREATE TABLE `delivery_area` (
  `pk_id` int(10) NOT NULL,
  `area` varchar(50) NOT NULL,
  `rate` float(22,2) NOT NULL DEFAULT 0.00,
  `FirstKgRate` double(22,2) NOT NULL DEFAULT 0.00,
  `additionalKgRate` double(22,2) NOT NULL DEFAULT 0.00,
  `SetRateMinOrder` double(22,2) NOT NULL DEFAULT 0.00,
  `countryId` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `delivery_area`
--

INSERT INTO `delivery_area` (`pk_id`, `area`, `rate`, `FirstKgRate`, `additionalKgRate`, `SetRateMinOrder`, `countryId`) VALUES
(1, 'Western', 230.00, 50.00, 0.00, 0.00, 57),
(2, 'Central', 300.00, 70.00, 0.00, 0.00, 57),
(3, 'Southern', 250.00, 70.00, 0.00, 0.00, 57),
(4, 'North Western', 300.00, 70.00, 0.00, 0.00, 57),
(5, 'Sabaragamuwa', 300.00, 70.00, 0.00, 0.00, 57),
(6, 'Eastern', 300.00, 70.00, 0.00, 0.00, 57),
(7, 'Uva', 300.00, 70.00, 0.00, 0.00, 57),
(8, 'North Central', 300.00, 70.00, 0.00, 0.00, 57),
(9, 'Northern', 300.00, 70.00, 0.00, 0.00, 57);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_master`
--

CREATE TABLE `delivery_master` (
  `id` int(10) NOT NULL,
  `method` varchar(100) NOT NULL,
  `rate` float(22,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `delivery_master`
--

INSERT INTO `delivery_master` (`id`, `method`, `rate`) VALUES
(1, ' Standard Delivery (3 - 5 working days)', 0.00),
(2, ' Collect from Store(will call once the order is fulfilled)', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_route_master`
--

CREATE TABLE `delivery_route_master` (
  `id` int(11) NOT NULL,
  `route_name` varchar(100) NOT NULL,
  `route_description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `delivery_route_master`
--

INSERT INTO `delivery_route_master` (`id`, `route_name`, `route_description`, `amount`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Route A - North', 'Northern delivery route covering suburbs north of the city', '50.00', 1, '2025-11-23 15:09:09', '2026-02-25 06:10:52'),
(2, 'Route B - South', 'Southern delivery route covering suburbs south of the city', '0.00', 1, '2025-11-23 15:09:09', '2025-11-23 15:09:09'),
(3, 'Route C - East', 'Eastern delivery route covering suburbs east of the city', '0.00', 1, '2025-11-23 15:09:09', '2025-11-23 15:09:09'),
(4, 'Route D - West', 'Western delivery route covering suburbs west of the city', '0.00', 1, '2025-11-23 15:09:09', '2025-11-23 15:09:09'),
(5, 'Route E - Central', 'Central delivery route for city center deliveries', '0.00', 1, '2025-11-23 15:09:09', '2025-11-23 15:09:09'),
(6, 'Route F - Rural', 'Rural delivery route for outlying areas', '0.00', 1, '2025-11-23 15:09:09', '2025-11-23 15:09:09');

-- --------------------------------------------------------

--
-- Table structure for table `discount_type`
--

CREATE TABLE `discount_type` (
  `id` int(10) NOT NULL,
  `name` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT '',
  `subject` varchar(500) NOT NULL,
  `template_type` varchar(50) NOT NULL COMMENT 'cart_order, standing_order, etc.',
  `reference_id` int(11) DEFAULT NULL COMMENT 'invoice_h_id or standing_order id',
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `to_email`, `to_name`, `subject`, `template_type`, `reference_id`, `status`, `error_message`, `sent_at`) VALUES
(1, 'malith.sachinthana@gmail.com', '', 'Bakery Admin - SMTP Test Email', 'test', NULL, 'sent', NULL, '2026-02-22 23:20:45'),
(2, 'malith.sachinthana@gmail.com', 'Malith SachinthanaDD', 'Order Confirmation - CART7403174226', 'cart_order', 226, 'sent', NULL, '2026-02-22 23:24:14'),
(3, 'malith.sachinthana@gmail.com', 'Malith SachinthanaDD', 'Standing Order Confirmation - Malith SachinthanaDD', 'standing_order', 1, 'sent', NULL, '2026-02-22 23:25:15'),
(4, 'amarasinghe.upul@gmail.com', 'JAMES BOND', 'Order Confirmation - CART6849479276', 'cart_order', 276, 'sent', NULL, '2026-02-22 23:27:33'),
(5, 'amarasinghe.upul@gmail.com', 'JAMES BOND', 'Standing Order Confirmation - JAMES BOND', 'standing_order', 6, 'sent', NULL, '2026-02-22 23:28:05'),
(6, 'araliya@gmail.com', 'araliya', 'Order Confirmation - CART7786959282', 'cart_order', 282, 'sent', NULL, '2026-02-23 22:51:27'),
(7, 'araliya@gmail.com', 'araliya', 'Order Confirmation - CART9129119283', 'cart_order', 283, 'sent', NULL, '2026-02-23 22:54:13'),
(8, 'araliya@gmail.com', 'araliya', 'Order Confirmation - CART7038833284', 'cart_order', 284, 'sent', NULL, '2026-02-23 23:05:17'),
(9, 'dsad@gmail.copm', 'CUST-0033322asdada', 'Order Confirmation - CART6919786285', 'cart_order', 285, 'sent', NULL, '2026-02-25 10:58:25'),
(10, 'dsad@gmail.copm', 'CUST-0033322asdada', 'Order Confirmation - CART7559097286', 'cart_order', 286, 'sent', NULL, '2026-02-25 11:20:21'),
(11, 'amarasinghe.upul@gmail.com', 'JAMES BOND', 'Order Confirmation - CART9816623287', 'cart_order', 287, 'sent', NULL, '2026-02-26 07:37:38'),
(12, 'dsad@gmail.copm', 'CUST-0033322asdada', 'Order Confirmation - CART8270664288', 'cart_order', 288, 'sent', NULL, '2026-02-26 11:51:11'),
(13, 'dsad@gmail.copm', 'CUST-0033322asdada', 'Order Confirmation - CART8168602289', 'cart_order', 289, 'sent', NULL, '2026-02-26 11:53:45');

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(10) NOT NULL,
  `employee_name` varchar(50) DEFAULT NULL,
  `employee_address` text DEFAULT NULL,
  `employee_tell` int(11) DEFAULT NULL,
  `employee_note` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `fifo`
--

CREATE TABLE `fifo` (
  `ft_id` int(10) NOT NULL,
  `ft_location` int(10) DEFAULT NULL,
  `ft_document` int(10) DEFAULT NULL,
  `ft_item` int(10) DEFAULT NULL,
  `ft_qty` double(20,2) DEFAULT NULL,
  `ft_blanace` double(20,2) DEFAULT NULL,
  `ft_rate` double(20,2) DEFAULT NULL,
  `ft_date` datetime NOT NULL DEFAULT current_timestamp(),
  `ft_type` int(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `fifo`
--

INSERT INTO `fifo` (`ft_id`, `ft_location`, `ft_document`, `ft_item`, `ft_qty`, `ft_blanace`, `ft_rate`, `ft_date`, `ft_type`) VALUES
(1, 1, 1, 60, 1.00, 1.00, 10.00, '2024-04-23 11:10:01', 1),
(2, 1, 1, 61, 1.00, 0.00, 10.00, '2024-04-23 11:10:01', 1),
(3, 1, 1, 57, 1.00, 1.00, 8.00, '2024-04-23 11:10:01', 1),
(4, 1, 1, 113, 1.00, 0.00, 10.00, '2024-04-23 11:10:01', 1),
(5, 1, 2, 60, 1.00, 1.00, 10.00, '2024-04-23 11:34:18', 1),
(6, 1, 2, 94, 1.00, 0.00, 10.00, '2024-04-23 11:34:18', 1),
(7, 1, 2, 113, 1.00, 1.00, 10.00, '2024-04-23 11:34:18', 1),
(8, 1, 1, 61, 1.00, NULL, 10.00, '2024-04-23 11:36:13', 2),
(9, 1, 1, 94, 1.00, NULL, 10.00, '2024-04-23 11:36:13', 2),
(10, 1, 1, 113, 1.00, NULL, 10.00, '2024-04-23 11:36:13', 2),
(11, 1, 3, 61, 10.00, 10.00, 10.00, '2024-05-21 10:52:22', 1),
(12, 1, 4, 1, 1.00, 0.00, 1.00, '2025-10-15 12:16:38', 1),
(13, 1, 5, 2, 1.00, 0.00, 5.00, '2025-10-15 12:20:22', 1),
(14, 1, 6, 2, 10.00, 0.00, 5.00, '2025-11-18 08:59:30', 1),
(15, 1, 6, 1, 10.00, 0.00, 1.00, '2025-11-18 08:59:30', 1),
(16, 1, 7, 2, 10.00, 0.00, 5.00, '2026-01-15 11:01:53', 1),
(17, 1, 7, 3, 10.00, 0.00, 1.00, '2026-01-15 11:01:53', 1),
(18, 1, 8, 2, 1.00, 0.00, 5.00, '2026-01-15 15:19:43', 1),
(19, 1, 8, 3, 1.00, 0.00, 1.00, '2026-01-15 15:19:43', 1),
(20, 1, 9, 2, 1.00, 0.00, 5.00, '2026-01-15 15:53:08', 1),
(21, 1, 9, 3, 1.00, 0.00, 1.00, '2026-01-15 15:53:08', 1),
(22, 1, 10, 1, 5.00, 0.00, 1.00, '2026-01-15 17:43:52', 1),
(23, 1, 11, 2, 3.00, 0.00, 5.00, '2026-01-15 18:27:29', 1),
(24, 5, 1, 2, 10.00, 5.00, 5.00, '2026-01-15 20:34:51', 1),
(25, 1, 9, 2, 1.00, NULL, 5.00, '2026-01-16 04:33:21', 2),
(26, 1, 9, 1, 1.00, NULL, 1.00, '2026-01-16 04:33:21', 2),
(27, 1, 9, 3, 1.00, NULL, 1.00, '2026-01-16 04:33:21', 2),
(28, 1, 12, 1, 4.00, 0.00, 1.00, '2026-01-17 22:44:38', 1),
(29, 1, 12, 3, 12.00, 0.00, 1.00, '2026-01-17 22:44:38', 1),
(30, 5, 2, 3, 3.00, 3.00, 1.00, '2026-01-17 22:50:00', 1),
(31, 5, 3, 1, 4.00, 4.00, 1.00, '2026-01-18 12:26:13', 1),
(32, 5, 4, 1, 3.00, 3.00, 1.00, '2026-01-18 12:28:02', 1),
(33, 1, 13, 1, 5.00, 0.00, 1.00, '2026-01-18 13:14:06', 1),
(34, 1, 13, 3, 3.00, 0.00, 1.00, '2026-01-18 13:14:06', 1),
(35, 1, 14, 1, 6.00, 0.00, 1.00, '2026-01-18 16:13:34', 1),
(36, 1, 14, 3, 2.00, 0.00, 1.00, '2026-01-18 16:13:34', 1),
(37, 1, 15, 1, 1.00, 1.00, 1.00, '2026-01-18 16:14:43', 1),
(38, 1, 15, 3, 1.00, 0.00, 1.00, '2026-01-18 16:14:43', 1),
(39, 5, 7, 3, 2.00, 2.00, 1.00, '2026-02-14 11:46:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `front_web_settings`
--

CREATE TABLE `front_web_settings` (
  `id` int(11) NOT NULL,
  `header_notice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_badge` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_button_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_button_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_one_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_one_badge` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_one_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_one_button_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_one_button_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_two_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_two_badge` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_two_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_two_button_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `banner_two_button_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_badge` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_button_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `promo_button_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `front_web_settings`
--

INSERT INTO `front_web_settings` (`id`, `header_notice`, `hero_badge`, `hero_title`, `hero_button_label`, `hero_button_link`, `hero_image`, `banner_one_image`, `banner_one_badge`, `banner_one_title`, `banner_one_button_label`, `banner_one_button_link`, `banner_two_image`, `banner_two_badge`, `banner_two_title`, `banner_two_button_label`, `banner_two_button_link`, `promo_image`, `promo_badge`, `promo_title`, `promo_description`, `promo_button_label`, `promo_button_link`, `updated_at`) VALUES
(1, 'Welcome to Our Bakery Shop - Free Shipping on orders above $50', 'Fresh Daily', 'Quality Bakery Products', 'Shop Now', 'search.php', 'uploads/frontweb/banners/64ffe694b6c50-20260401122708-8849.webp', 'uploads/frontweb/banners/login-img2-20260401122812-8220.png', 'Fresh Daily', 'Best Quality Products', 'Shop Now', 'search.php', 'uploads/frontweb/banners/chatgpt-image-feb-22-2026-12-25-05-pm-20260401131247-7800.png', 'Hot & Spicy', 'Freshly Baked Pastry', 'Shop Now', 'search.php', '', 'Fresh Everyday', 'Best Quality Bakery Products', 'Handcrafted with love using the finest ingredients.\r\nOrder online and get delivered fresh to your doorstep.', 'Shop Now', 'search.php', '2026-04-01 11:12:47');

-- --------------------------------------------------------

--
-- Table structure for table `general_settings`
--

CREATE TABLE `general_settings` (
  `id` int(10) NOT NULL,
  `SiteName` varchar(70) DEFAULT NULL,
  `logo` text DEFAULT NULL,
  `footerLogo` text DEFAULT NULL,
  `favIcon` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `maintainMode` int(11) NOT NULL DEFAULT 0,
  `system_email` varchar(200) DEFAULT NULL,
  `contactUs` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `general_settings`
--

INSERT INTO `general_settings` (`id`, `SiteName`, `logo`, `footerLogo`, `favIcon`, `address`, `maintainMode`, `system_email`, `contactUs`) VALUES
(1, 'bakery eCommerce', 'assets/img/logo/pureBeautyLogo.png', '', '', '', 0, 'info@bakery.com', '+39 327 823 7191');

-- --------------------------------------------------------

--
-- Table structure for table `goods`
--

CREATE TABLE `goods` (
  `goods_id` int(10) NOT NULL,
  `goods_item` int(10) DEFAULT NULL,
  `goods_grn` int(10) DEFAULT NULL,
  `goods_invoice` int(10) DEFAULT NULL,
  `goods_supplier_return_id` int(10) DEFAULT NULL,
  `goods_customer_return_id` int(10) DEFAULT NULL,
  `goods_manufacture_serial_number` text DEFAULT NULL,
  `GOODS_status` int(10) DEFAULT NULL,
  `goods_location` int(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `gorup_master`
--

CREATE TABLE `gorup_master` (
  `group_id` int(10) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `group_discription` text DEFAULT NULL,
  `website_status` enum('N','Y') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `gorup_master`
--

INSERT INTO `gorup_master` (`group_id`, `group_name`, `group_discription`, `website_status`) VALUES
(1, 'Selling Products ', NULL, 'Y'),
(2, 'Raw materials', NULL, 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `grn_details`
--

CREATE TABLE `grn_details` (
  `grn_d_id` int(10) NOT NULL,
  `grn_h_id` int(10) DEFAULT NULL,
  `grn_d_item_id` int(10) DEFAULT NULL,
  `purchase_note_item_id` int(10) DEFAULT NULL,
  `grn_d_qty` double(20,2) DEFAULT NULL,
  `grn_d_blance` double(20,2) DEFAULT NULL,
  `grn_d_rate` double(20,2) DEFAULT NULL,
  `grn_d_vat` double(20,2) DEFAULT NULL,
  `grn_d_vat_rate` double(20,2) DEFAULT NULL,
  `grn_d_total` double(20,2) DEFAULT NULL,
  `grn_d_warranty_month` int(4) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `grn_details`
--

INSERT INTO `grn_details` (`grn_d_id`, `grn_h_id`, `grn_d_item_id`, `purchase_note_item_id`, `grn_d_qty`, `grn_d_blance`, `grn_d_rate`, `grn_d_vat`, `grn_d_vat_rate`, `grn_d_total`, `grn_d_warranty_month`) VALUES
(1, 1, 60, NULL, 1.00, 1.00, 10.00, NULL, 0.00, 10.00, NULL),
(2, 1, 61, NULL, 1.00, 1.00, 10.00, NULL, 0.00, 10.00, NULL),
(3, 1, 57, NULL, 1.00, 1.00, 8.00, NULL, 0.00, 8.00, NULL),
(4, 1, 113, NULL, 1.00, 1.00, 10.00, NULL, 0.00, 10.00, NULL),
(5, 2, 60, NULL, 1.00, 1.00, 10.00, NULL, 0.00, 10.00, NULL),
(6, 2, 94, NULL, 1.00, 1.00, 10.00, NULL, 0.00, 10.00, NULL),
(7, 2, 113, NULL, 1.00, 1.00, 10.00, NULL, 0.00, 10.00, NULL),
(8, 3, 61, NULL, 10.00, 10.00, 10.00, NULL, 0.00, 100.00, NULL),
(9, 4, 1, NULL, 1.00, 1.00, 1.00, NULL, 0.00, 1.00, NULL),
(10, 5, 2, NULL, 1.00, 1.00, 5.00, NULL, 0.00, 5.00, NULL),
(11, 6, 2, NULL, 10.00, 10.00, 5.00, NULL, 0.00, 50.00, NULL),
(12, 6, 1, NULL, 10.00, 10.00, 1.00, NULL, 0.00, 10.00, NULL),
(13, 7, 2, NULL, 10.00, 10.00, 5.00, NULL, 0.00, 50.00, NULL),
(14, 7, 3, NULL, 10.00, 10.00, 1.00, NULL, 0.00, 10.00, NULL),
(15, 8, 2, 1, 1.00, 1.00, 5.00, NULL, 0.00, 5.00, NULL),
(16, 8, 3, 2, 1.00, 1.00, 1.00, NULL, 0.00, 1.00, NULL),
(17, 9, 2, 1, 1.00, 1.00, 5.00, NULL, 0.00, 5.00, NULL),
(18, 9, 3, 2, 1.00, 1.00, 1.00, NULL, 0.00, 1.00, NULL),
(19, 10, 1, 3, 5.00, 5.00, 1.00, NULL, 0.00, 5.00, NULL),
(20, 11, 2, 4, 3.00, 3.00, 5.00, NULL, 0.00, 15.00, NULL),
(21, 12, 1, 5, 4.00, 4.00, 1.00, NULL, 0.00, 4.00, NULL),
(22, 12, 3, 6, 12.00, 12.00, 1.00, NULL, 0.00, 12.00, NULL),
(23, 13, 1, 5, 5.00, 5.00, 1.00, 0.00, 0.00, 5.00, NULL),
(24, 13, 3, 6, 3.00, 3.00, 1.00, 0.00, 0.00, 3.00, NULL),
(25, 14, 1, 7, 6.00, 6.00, 1.00, 0.00, 0.00, 6.00, NULL),
(26, 14, 3, 8, 2.00, 2.00, 1.00, 0.00, 0.00, 2.00, NULL),
(27, 15, 1, 7, 1.00, 1.00, 1.00, 0.00, 0.00, 1.00, NULL),
(28, 15, 3, 8, 1.00, 1.00, 1.00, 0.00, 0.00, 1.00, NULL),
(29, 16, 3, 0, 2.00, 2.00, 1.00, 0.00, 0.00, 2.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grn_hedder`
--

CREATE TABLE `grn_hedder` (
  `grn_h_id` int(10) NOT NULL,
  `grn_h_code` varchar(20) DEFAULT NULL,
  `purchase_note_id` int(10) DEFAULT NULL,
  `grn_h_supplier_id` int(10) DEFAULT NULL,
  `grn_h_supplier_invoice_code` text DEFAULT NULL,
  `grn_h_location` int(10) NOT NULL DEFAULT 0,
  `grn_h_date` datetime NOT NULL DEFAULT current_timestamp(),
  `grn_h_pay_type` int(10) DEFAULT NULL,
  `grn_h_net_value` double(20,2) DEFAULT 0.00,
  `grn_h_vat_value` double(20,2) DEFAULT 0.00,
  `grn_h_gross_value` double(20,2) DEFAULT 0.00,
  `add_by` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `grn_hedder`
--

INSERT INTO `grn_hedder` (`grn_h_id`, `grn_h_code`, `purchase_note_id`, `grn_h_supplier_id`, `grn_h_supplier_invoice_code`, `grn_h_location`, `grn_h_date`, `grn_h_pay_type`, `grn_h_net_value`, `grn_h_vat_value`, `grn_h_gross_value`, `add_by`) VALUES
(1, 'PUR76912871', NULL, 1, '', 1, '2024-04-23 11:10:01', 3, 38.00, 0.00, 38.00, '1'),
(2, 'PUR47929122', NULL, 1, '', 1, '2024-04-23 11:34:18', 1, 30.00, 0.00, 30.00, '1'),
(3, 'PUR90278403', NULL, 1, '', 1, '2024-05-21 10:52:22', 1, 100.00, 0.00, 100.00, '1'),
(4, 'PUR35225694', NULL, 1, '', 1, '2025-10-15 12:16:38', 1, 1.00, 0.00, 1.00, '1'),
(5, 'PUR89711705', NULL, 1, '', 1, '2025-10-15 12:20:22', 1, 5.00, 0.00, 5.00, '1'),
(6, 'PUR38949566', NULL, 13, '', 1, '2025-11-18 08:59:30', 1, 60.00, 0.00, 60.00, '1'),
(7, 'PUR66702567', NULL, 13, '', 1, '2026-01-15 11:01:53', 1, 60.00, 0.00, 60.00, '1'),
(8, 'GRN8185598', 1, 13, '', 1, '2026-01-15 15:19:43', NULL, 6.00, 0.00, 6.00, '1'),
(9, 'GRN9637359', 1, 13, '', 1, '2026-01-15 15:53:08', NULL, 6.00, 0.00, 6.00, '1'),
(10, 'GRN70625610', 2, 14, '', 1, '2026-01-15 17:43:52', NULL, 5.00, 0.00, 5.00, '1'),
(11, 'GRN57618511', 3, 14, '', 1, '2026-01-15 18:27:29', NULL, 15.00, 0.00, 15.00, '1'),
(12, 'GRN60407212', 4, 13, '', 1, '2026-01-17 22:44:38', NULL, 16.00, 0.00, 16.00, '1'),
(13, 'GRN58929813', 4, 13, '', 1, '2026-01-18 13:14:06', NULL, 8.00, 0.00, 8.00, '8'),
(14, 'GRN37982714', 5, 13, '', 1, '2026-01-18 16:13:34', NULL, 8.00, 0.00, 8.00, '1'),
(15, 'GRN39646315', 5, 13, '', 1, '2026-01-18 16:14:43', NULL, 2.00, 0.00, 2.00, '1'),
(16, 'GRN119216', 0, 0, 'Stock Transfer: ST1362047', 5, '2026-02-14 11:46:46', NULL, 2.00, 0.00, 2.00, '1');

-- --------------------------------------------------------

--
-- Table structure for table `groupmappingitem`
--

CREATE TABLE `groupmappingitem` (
  `Id` int(10) NOT NULL,
  `ItemId` int(10) NOT NULL DEFAULT 0,
  `GroupId` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `hampers`
--

CREATE TABLE `hampers` (
  `pk_id` int(10) NOT NULL,
  `hamper_id` int(10) NOT NULL,
  `item_id` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `home_slider`
--

CREATE TABLE `home_slider` (
  `id` int(10) NOT NULL,
  `image` text DEFAULT NULL,
  `path` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `home_slider`
--

INSERT INTO `home_slider` (`id`, `image`, `path`, `link`, `active`, `text`) VALUES
(9, 'mainbanner1-1920x750.jpg', 'assets/img/slider/', '', 1, '45');

-- --------------------------------------------------------

--
-- Table structure for table `immediatepickup`
--

CREATE TABLE `immediatepickup` (
  `Id` int(10) NOT NULL,
  `name` varchar(40) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `immediatepickup`
--

INSERT INTO `immediatepickup` (`Id`, `name`) VALUES
(1, 'No'),
(2, 'Yes');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_details`
--

CREATE TABLE `invoice_details` (
  `invoice_d_id` int(10) NOT NULL,
  `invoice_h_id` int(10) DEFAULT NULL,
  `invoice_d_item_id` int(10) DEFAULT NULL,
  `invoice_d_qty` int(10) DEFAULT NULL,
  `invoice_d_balance` int(10) DEFAULT NULL,
  `invoice_d_item_price` double(20,2) DEFAULT 0.00,
  `invoice_d_vat` varchar(1) NOT NULL DEFAULT 'N',
  `invoice_d_vat_rate` double(20,2) DEFAULT 0.00,
  `invoice_d_discount_value` double(20,2) DEFAULT 0.00,
  `invoice_d_discount_type` double(20,2) DEFAULT 0.00,
  `invoice_d_discount_total` double(20,2) DEFAULT 0.00,
  `invoice_d_item_total` float(22,2) NOT NULL,
  `invoice_d_warranty_month` int(10) DEFAULT NULL,
  `invoice_d_sales_rap` int(10) DEFAULT NULL,
  `order_note` text NOT NULL,
  `is_cart_item` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Added via cart, 0 = Standing order item'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `invoice_details`
--

INSERT INTO `invoice_details` (`invoice_d_id`, `invoice_h_id`, `invoice_d_item_id`, `invoice_d_qty`, `invoice_d_balance`, `invoice_d_item_price`, `invoice_d_vat`, `invoice_d_vat_rate`, `invoice_d_discount_value`, `invoice_d_discount_type`, `invoice_d_discount_total`, `invoice_d_item_total`, `invoice_d_warranty_month`, `invoice_d_sales_rap`, `order_note`, `is_cart_item`) VALUES
(1, 1, 1, 2, 2, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 0),
(23, 2, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(24, 9, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(27, 10, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(26, 9, 3, 1, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 0),
(25, 9, 1, 1, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 1.00, NULL, NULL, '', 0),
(28, 10, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(29, 11, 1, 3, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 3.00, NULL, NULL, '', 0),
(30, 11, 3, 2, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(31, 12, 2, 2, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(32, 12, 1, 4, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(33, 13, 3, 5, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(34, 14, 1, 1, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 1.00, NULL, NULL, '', 0),
(35, 14, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(36, 14, 3, 1, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 0),
(37, 15, 3, 5, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(38, 15, 2, 5, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 25.00, NULL, NULL, '', 0),
(39, 15, 1, 4, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(40, 16, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(41, 16, 1, 6, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(42, 16, 3, 3, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(43, 17, 2, 5, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 25.00, NULL, NULL, '', 0),
(44, 17, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(45, 17, 1, 3, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 3.00, NULL, NULL, '', 0),
(46, 18, 3, 3, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(47, 18, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(48, 18, 2, 5, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 25.00, NULL, NULL, '', 0),
(49, 19, 1, 4, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(50, 19, 2, 4, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(51, 19, 3, 1, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 0),
(52, 20, 2, 4, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(53, 20, 3, 2, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(54, 20, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(55, 21, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(56, 21, 2, 4, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(57, 21, 3, 1, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 0),
(58, 22, 1, 3, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 3.00, NULL, NULL, '', 0),
(59, 22, 3, 4, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(60, 22, 2, 3, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(61, 23, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(62, 23, 2, 5, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 25.00, NULL, NULL, '', 0),
(63, 23, 1, 6, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(64, 24, 3, 2, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(65, 24, 2, 5, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 25.00, NULL, NULL, '', 0),
(66, 24, 1, 6, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(67, 25, 2, 2, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(68, 25, 3, 4, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(69, 25, 1, 3, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 3.00, NULL, NULL, '', 0),
(70, 26, 1, 1, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 1.00, NULL, NULL, '', 0),
(71, 26, 2, 4, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(72, 26, 3, 3, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(73, 27, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(74, 27, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(75, 27, 1, 4, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(76, 28, 2, 4, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(77, 28, 3, 2, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(78, 28, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(79, 29, 2, 6, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 30.00, NULL, NULL, '', 0),
(80, 29, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(81, 29, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(82, 30, 3, 1, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 0),
(83, 30, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(84, 30, 2, 6, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 30.00, NULL, NULL, '', 0),
(85, 31, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(86, 31, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(87, 31, 1, 3, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 3.00, NULL, NULL, '', 0),
(88, 32, 1, 6, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(89, 32, 2, 3, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(90, 32, 3, 5, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(91, 33, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(92, 33, 2, 4, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(93, 33, 1, 4, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(94, 34, 3, 6, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 12.00, NULL, NULL, '', 0),
(95, 34, 1, 5, NULL, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(96, 34, 2, 6, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 30.00, NULL, NULL, '', 0),
(97, 35, 4, 3, NULL, 65.20, 'N', 0.00, 0.00, 0.00, 0.00, 195.60, NULL, NULL, '', 0),
(98, 36, 5, 2, NULL, 23.10, 'N', 0.00, 0.00, 0.00, 0.00, 46.20, NULL, NULL, '', 0),
(99, 37, 6, 3, NULL, 85.60, 'N', 0.00, 0.00, 0.00, 0.00, 256.80, NULL, NULL, '', 0),
(100, 38, 7, 1, NULL, 48.70, 'N', 0.00, 0.00, 0.00, 0.00, 48.70, NULL, NULL, '', 0),
(101, 39, 8, 5, NULL, 13.50, 'N', 0.00, 0.00, 0.00, 0.00, 67.50, NULL, NULL, '', 0),
(102, 40, 9, 5, NULL, 64.50, 'N', 0.00, 0.00, 0.00, 0.00, 322.50, NULL, NULL, '', 0),
(103, 41, 10, 5, NULL, 35.00, 'N', 0.00, 0.00, 0.00, 0.00, 175.00, NULL, NULL, '', 0),
(104, 42, 11, 3, NULL, 25.50, 'N', 0.00, 0.00, 0.00, 0.00, 76.50, NULL, NULL, '', 0),
(105, 43, 12, 4, NULL, 21.80, 'N', 0.00, 0.00, 0.00, 0.00, 87.20, NULL, NULL, '', 0),
(106, 44, 13, 4, NULL, 62.00, 'N', 0.00, 0.00, 0.00, 0.00, 248.00, NULL, NULL, '', 0),
(107, 45, 14, 5, NULL, 57.00, 'N', 0.00, 0.00, 0.00, 0.00, 285.00, NULL, NULL, '', 0),
(108, 46, 15, 3, NULL, 79.00, 'N', 0.00, 0.00, 0.00, 0.00, 237.00, NULL, NULL, '', 0),
(109, 47, 16, 4, NULL, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, '', 0),
(110, 48, 17, 3, NULL, 84.10, 'N', 0.00, 0.00, 0.00, 0.00, 252.30, NULL, NULL, '', 0),
(111, 49, 18, 4, NULL, 15.30, 'N', 0.00, 0.00, 0.00, 0.00, 61.20, NULL, NULL, '', 0),
(112, 50, 19, 4, NULL, 23.40, 'N', 0.00, 0.00, 0.00, 0.00, 93.60, NULL, NULL, '', 0),
(113, 51, 20, 2, NULL, 42.80, 'N', 0.00, 0.00, 0.00, 0.00, 85.60, NULL, NULL, '', 0),
(114, 52, 21, 3, NULL, 61.80, 'N', 0.00, 0.00, 0.00, 0.00, 185.40, NULL, NULL, '', 0),
(115, 53, 22, 3, NULL, 98.00, 'N', 0.00, 0.00, 0.00, 0.00, 294.00, NULL, NULL, '', 0),
(116, 54, 23, 1, NULL, 14.40, 'N', 0.00, 0.00, 0.00, 0.00, 14.40, NULL, NULL, '', 0),
(117, 55, 24, 1, NULL, 97.60, 'N', 0.00, 0.00, 0.00, 0.00, 97.60, NULL, NULL, '', 0),
(118, 56, 25, 1, NULL, 71.20, 'N', 0.00, 0.00, 0.00, 0.00, 71.20, NULL, NULL, '', 0),
(119, 57, 26, 2, NULL, 10.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 0),
(120, 58, 27, 4, NULL, 42.60, 'N', 0.00, 0.00, 0.00, 0.00, 170.40, NULL, NULL, '', 0),
(121, 59, 28, 3, NULL, 18.60, 'N', 0.00, 0.00, 0.00, 0.00, 55.80, NULL, NULL, '', 0),
(122, 60, 29, 4, NULL, 90.10, 'N', 0.00, 0.00, 0.00, 0.00, 360.40, NULL, NULL, '', 0),
(123, 61, 30, 1, NULL, 95.60, 'N', 0.00, 0.00, 0.00, 0.00, 95.60, NULL, NULL, '', 0),
(124, 62, 31, 1, NULL, 39.20, 'N', 0.00, 0.00, 0.00, 0.00, 39.20, NULL, NULL, '', 0),
(125, 63, 32, 2, NULL, 38.30, 'N', 0.00, 0.00, 0.00, 0.00, 76.60, NULL, NULL, '', 0),
(126, 64, 33, 4, NULL, 71.40, 'N', 0.00, 0.00, 0.00, 0.00, 285.60, NULL, NULL, '', 0),
(127, 65, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(128, 66, 2, 5, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 25.00, NULL, NULL, '', 0),
(129, 66, 1, 5, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 250.00, NULL, NULL, '', 0),
(130, 67, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(131, 67, 1, 1, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 50.00, NULL, NULL, '', 0),
(132, 68, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(133, 68, 1, 1, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 50.00, NULL, NULL, '', 0),
(134, 69, 1, 2, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 100.00, NULL, NULL, '', 0),
(135, 69, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(136, 70, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, '', 0),
(238, 71, 16, 3, NULL, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, '', 0),
(237, 71, 3, 2, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(220, 73, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(235, 74, 3, 3, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(222, 76, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(223, 77, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(224, 78, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(413, 79, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(276, 106, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(277, 107, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(279, 108, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(273, 99, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(263, 110, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, 'Standing Order', 0),
(236, 74, 1, 3, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 150.00, NULL, NULL, '', 1),
(281, 109, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(274, 105, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(264, 111, 2, 4, 4, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, 'Standing Order', 0),
(265, 112, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(266, 113, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(364, 114, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(365, 115, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(366, 116, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(367, 117, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(412, 79, 3, 15, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 30.00, NULL, NULL, '', 0),
(275, 105, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(278, 107, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(280, 108, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(282, 118, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(283, 118, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(284, 119, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 'Standing Order', 0),
(285, 120, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 'Standing Order', 0),
(286, 120, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(418, 121, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(420, 122, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(419, 122, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(422, 123, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(421, 123, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(423, 124, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(425, 125, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(424, 125, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(426, 126, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(428, 127, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(427, 127, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(429, 128, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(431, 129, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(430, 129, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(433, 130, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(432, 130, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(434, 131, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(507, 132, 3, 4, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(506, 132, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(437, 133, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(439, 134, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(438, 134, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(440, 135, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(442, 136, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(441, 136, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(444, 137, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(443, 137, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(445, 138, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(447, 139, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(446, 139, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(448, 140, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(450, 141, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(449, 141, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(451, 142, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(453, 143, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(452, 143, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(455, 144, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(454, 144, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(456, 145, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(458, 146, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(457, 146, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(459, 147, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(461, 148, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(460, 148, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(462, 149, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(464, 150, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(463, 150, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(466, 151, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(465, 151, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(467, 152, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(469, 153, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(468, 153, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(470, 154, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(472, 155, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(471, 155, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(473, 156, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(475, 157, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(474, 157, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(477, 158, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(476, 158, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(478, 159, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(480, 160, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(479, 160, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(481, 161, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(483, 162, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(482, 162, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(484, 163, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(486, 164, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 0),
(485, 164, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(488, 165, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 0),
(487, 165, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, '', 0),
(489, 166, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(491, 167, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(490, 167, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, '', 0),
(492, 168, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(494, 169, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 0),
(493, 169, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, '', 0),
(368, 170, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(369, 171, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(370, 172, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(371, 173, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(372, 174, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(373, 175, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(374, 176, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(375, 177, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(376, 178, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(377, 179, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(378, 180, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(379, 181, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(380, 182, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(381, 183, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(382, 184, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(383, 185, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(384, 186, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(385, 187, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(386, 188, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(387, 189, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(388, 190, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(389, 191, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(390, 192, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(391, 193, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(392, 194, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(393, 195, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(394, 196, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(395, 197, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(396, 198, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(397, 199, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(398, 200, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(399, 201, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(400, 202, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(401, 203, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(402, 204, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(403, 205, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(404, 206, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(405, 207, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(406, 208, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(407, 209, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(408, 210, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(409, 211, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(410, 212, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(411, 213, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(495, 214, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(496, 215, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 'Standing Order', 0),
(497, 215, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(498, 216, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(499, 216, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, 'Standing Order', 0),
(500, 217, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 'Standing Order', 0),
(501, 218, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(502, 218, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(503, 219, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 'Standing Order', 0),
(504, 220, 3, 3, 3, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 'Standing Order', 0),
(505, 220, 2, 1, 1, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(509, 221, 1, 1, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 50.00, NULL, NULL, '', 1),
(510, 222, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(511, 223, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(512, 224, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 1),
(513, 225, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 1),
(514, 226, 2, 3, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, '', 1),
(515, 227, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(516, 227, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(517, 228, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(518, 228, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(519, 229, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(520, 230, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(521, 230, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(522, 231, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(523, 232, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(524, 233, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(525, 233, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(526, 234, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(527, 234, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(528, 235, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(529, 236, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(530, 236, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(531, 237, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(532, 238, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(533, 239, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(534, 239, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(535, 240, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(536, 240, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(537, 241, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(538, 242, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(539, 242, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(540, 243, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(541, 244, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(542, 245, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(543, 245, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(544, 246, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(545, 246, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(546, 247, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(547, 248, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(548, 248, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(549, 249, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(550, 250, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(551, 251, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(552, 251, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(553, 252, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(554, 252, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(555, 253, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(556, 254, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(557, 254, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(558, 255, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(559, 256, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(560, 257, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(561, 257, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(562, 258, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(563, 258, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(564, 259, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(565, 260, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(566, 260, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(567, 261, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(568, 262, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(569, 263, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(570, 263, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(571, 264, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(572, 264, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(573, 265, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(574, 266, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(575, 266, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(576, 267, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(577, 268, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(578, 269, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(579, 269, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(580, 270, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(581, 270, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(582, 271, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(583, 272, 3, 2, 2, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(584, 272, 16, 4, 4, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 145.20, NULL, NULL, 'Standing Order', 0),
(585, 273, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(586, 274, 16, 2, 2, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 72.60, NULL, NULL, 'Standing Order', 0),
(587, 275, 3, 4, 4, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 0),
(588, 275, 16, 3, 3, 36.30, 'N', 0.00, 0.00, 0.00, 0.00, 108.90, NULL, NULL, 'Standing Order', 0),
(589, 276, 1, 1, NULL, 50.00, 'N', 0.00, 0.00, 0.00, 0.00, 50.00, NULL, NULL, '', 1),
(590, 276, 2, 2, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 1),
(591, 277, 2, 3, 3, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 15.00, NULL, NULL, 'Standing Order', 0),
(592, 277, 1, 4, 4, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 4.00, NULL, NULL, 'Standing Order', 0),
(593, 278, 2, 2, 2, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 'Standing Order', 0),
(594, 279, 1, 5, 5, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 0),
(595, 280, 2, 4, 4, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, 'Standing Order', 0),
(596, 281, 1, 2, 2, 1.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, 'Standing Order', 0),
(597, 282, 3, 1, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 2.00, NULL, NULL, '', 1),
(598, 283, 3, 1, NULL, 10.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 1),
(599, 284, 3, 1, NULL, 30.00, 'N', 0.00, 0.00, 0.00, 0.00, 30.00, NULL, NULL, '', 1),
(600, 285, 2, 10, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 50.00, NULL, NULL, '', 1),
(601, 286, 2, 10, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 50.00, NULL, NULL, '', 1),
(602, 287, 2, 1, NULL, 5.00, 'N', 0.00, 0.00, 0.00, 0.00, 5.00, NULL, NULL, '', 1),
(603, 288, 3, 5, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 10.00, NULL, NULL, '', 1),
(604, 289, 3, 10, NULL, 2.00, 'N', 0.00, 0.00, 0.00, 0.00, 20.00, NULL, NULL, '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_hedder`
--

CREATE TABLE `invoice_hedder` (
  `invoice_h_id` int(1) UNSIGNED ZEROFILL NOT NULL,
  `invoice_h_code` varchar(20) DEFAULT NULL,
  `invoice_h_customer_id` int(10) DEFAULT NULL,
  `invoice_h_date` date NOT NULL,
  `invoice_h_datetime` datetime NOT NULL,
  `invoice_h_location` int(10) DEFAULT NULL,
  `invoice_h_delivery_city` int(10) NOT NULL,
  `delivery_city_name` varchar(100) DEFAULT NULL,
  `invoice_h_delivery_cost` float(22,2) NOT NULL,
  `invoice_h_delivery_mode` int(10) NOT NULL,
  `invoice_h_pay_type` int(10) DEFAULT NULL,
  `invoice_h_coupun_code` varchar(100) NOT NULL,
  `invoice_h_coupon_type` enum('PCT','SUM') NOT NULL,
  `invoice_h_coupon_rate` float(22,2) NOT NULL,
  `invoice_h_coupon_value` float(22,2) NOT NULL,
  `invoice_h_net_value` double(20,2) DEFAULT 0.00,
  `invoice_h_vat_value` double(20,2) DEFAULT 0.00,
  `invoice_h_gross_value` double(20,2) DEFAULT 0.00,
  `invoice_h_check_Ref` text DEFAULT NULL,
  `invoice_h_card_Ref` text DEFAULT NULL,
  `invoice_h_order_note` text NOT NULL,
  `invoice_h_delivery_name` varchar(100) DEFAULT NULL,
  `invoice_h_delivery_address` varchar(500) NOT NULL,
  `invoice_h_delivery_contact_no` varchar(15) NOT NULL,
  `invoice_h_delivery_date` date NOT NULL,
  `invoice_h_delivery_time` varchar(50) NOT NULL,
  `invoice_h_status` int(2) NOT NULL DEFAULT 1,
  `order_type` enum('POS','CART','STANDING','ONLINE') DEFAULT NULL,
  `shipping_address_id` int(11) DEFAULT NULL,
  `add_by` varchar(20) NOT NULL,
  `updated_at` datetime DEFAULT NULL COMMENT 'Last update timestamp',
  `invoice_h_approve_date` datetime NOT NULL,
  `CustomerCurrencyId` varchar(10) NOT NULL DEFAULT '0',
  `CurrencyRate` decimal(18,4) DEFAULT 0.0000
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `invoice_hedder`
--

INSERT INTO `invoice_hedder` (`invoice_h_id`, `invoice_h_code`, `invoice_h_customer_id`, `invoice_h_date`, `invoice_h_datetime`, `invoice_h_location`, `invoice_h_delivery_city`, `delivery_city_name`, `invoice_h_delivery_cost`, `invoice_h_delivery_mode`, `invoice_h_pay_type`, `invoice_h_coupun_code`, `invoice_h_coupon_type`, `invoice_h_coupon_rate`, `invoice_h_coupon_value`, `invoice_h_net_value`, `invoice_h_vat_value`, `invoice_h_gross_value`, `invoice_h_check_Ref`, `invoice_h_card_Ref`, `invoice_h_order_note`, `invoice_h_delivery_name`, `invoice_h_delivery_address`, `invoice_h_delivery_contact_no`, `invoice_h_delivery_date`, `invoice_h_delivery_time`, `invoice_h_status`, `order_type`, `shipping_address_id`, `add_by`, `updated_at`, `invoice_h_approve_date`, `CustomerCurrencyId`, `CurrencyRate`) VALUES
(1, 'INV00001', 289, '2025-11-28', '2025-11-28 16:10:59', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 2.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12, rathnarama Road Hokandara', '771998880', '2025-11-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2025-11-28 16:10:59', '0', '1.0000'),
(2, 'INV00002', 289, '2025-11-29', '2025-11-28 16:14:33', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12, rathnarama Road Hokandara', '771998880', '2025-11-29', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2025-11-28 16:14:33', '0', '1.0000'),
(15, 'EXTRA-20251128-1', 310, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 44.00, 0.00, 44.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(13, 'TEST-20251128-4', 306, '2025-11-28', '2025-11-28 09:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 10.00, 0.00, 10.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(14, 'TEST-20251128-5', 307, '2025-11-28', '2025-11-28 09:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 8.00, 0.00, 8.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(12, 'TEST-20251128-3', 305, '2025-11-28', '2025-11-28 09:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 14.00, 0.00, 14.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(6, 'INV00006', 308, '2025-11-29', '2025-11-28 16:29:11', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2025-11-29', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2025-11-28 16:29:11', '0', '1.0000'),
(11, 'TEST-20251128-2', 289, '2025-11-28', '2025-11-28 09:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 7.00, 0.00, 7.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(9, 'SAL50246337', 289, '2026-01-16', '2026-01-16 12:03:00', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 8.00, 0.00, 8.00, NULL, NULL, '', NULL, '', '', '0000-00-00', '', 1, NULL, NULL, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(10, 'TEST-20251128-1', 241, '2025-11-28', '2025-11-28 09:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 10.00, 0.00, 10.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(16, 'EXTRA-20251128-2', 311, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 27.00, 0.00, 27.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(17, 'EXTRA-20251128-3', 312, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 26.00, 0.00, 26.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(18, 'EXTRA-20251128-4', 313, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 22.00, 0.00, 22.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(19, 'EXTRA-20251128-5', 314, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 22.00, 0.00, 22.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(20, 'EXTRA-20251128-6', 315, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 16.00, 0.00, 16.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(21, 'EXTRA-20251128-7', 316, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 43.00, 0.00, 43.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(22, 'EXTRA-20251128-8', 317, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 37.00, 0.00, 37.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(23, 'EXTRA-20251128-9', 318, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 33.00, 0.00, 33.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(24, 'EXTRA-20251128-10', 319, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 22.00, 0.00, 22.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(25, 'EXTRA-20251128-11', 320, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 32.00, 0.00, 32.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(26, 'EXTRA-20251128-12', 321, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 41.00, 0.00, 41.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(27, 'EXTRA-20251128-13', 322, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 17.00, 0.00, 17.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(28, 'EXTRA-20251128-14', 323, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 10.00, 0.00, 10.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(29, 'EXTRA-20251128-15', 324, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 46.00, 0.00, 46.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(30, 'EXTRA-20251128-16', 325, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 38.00, 0.00, 38.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(31, 'EXTRA-20251128-17', 326, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 33.00, 0.00, 33.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(32, 'EXTRA-20251128-18', 327, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 26.00, 0.00, 26.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(33, 'EXTRA-20251128-19', 328, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 40.00, 0.00, 40.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(34, 'EXTRA-20251128-20', 329, '2025-11-28', '2025-11-28 10:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 24.00, 0.00, 24.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(35, 'PRD-20251128-1', 289, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 195.60, 0.00, 195.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(36, 'PRD-20251128-2', 310, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 46.20, 0.00, 46.20, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(37, 'PRD-20251128-3', 306, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 256.80, 0.00, 256.80, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(38, 'PRD-20251128-4', 307, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 48.70, 0.00, 48.70, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(39, 'PRD-20251128-5', 305, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 67.50, 0.00, 67.50, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(40, 'PRD-20251128-6', 241, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 322.50, 0.00, 322.50, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(41, 'PRD-20251128-7', 311, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 175.00, 0.00, 175.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(42, 'PRD-20251128-8', 312, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 76.50, 0.00, 76.50, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(43, 'PRD-20251128-9', 313, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 87.20, 0.00, 87.20, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(44, 'PRD-20251128-10', 314, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 248.00, 0.00, 248.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(45, 'PRD-20251128-11', 315, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 285.00, 0.00, 285.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(46, 'PRD-20251128-12', 316, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 237.00, 0.00, 237.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(47, 'PRD-20251128-13', 317, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 145.20, 0.00, 145.20, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(48, 'PRD-20251128-14', 318, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 252.30, 0.00, 252.30, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(49, 'PRD-20251128-15', 319, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 61.20, 0.00, 61.20, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(50, 'PRD-20251128-16', 320, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 93.60, 0.00, 93.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(51, 'PRD-20251128-17', 321, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 85.60, 0.00, 85.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(52, 'PRD-20251128-18', 322, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 185.40, 0.00, 185.40, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(53, 'PRD-20251128-19', 323, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 294.00, 0.00, 294.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(54, 'PRD-20251128-20', 324, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 14.40, 0.00, 14.40, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(55, 'PRD-20251128-21', 325, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 97.60, 0.00, 97.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(56, 'PRD-20251128-22', 326, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 71.20, 0.00, 71.20, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(57, 'PRD-20251128-23', 327, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 20.00, 0.00, 20.00, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(58, 'PRD-20251128-24', 328, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 170.40, 0.00, 170.40, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(59, 'PRD-20251128-25', 329, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 55.80, 0.00, 55.80, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(60, 'PRD-20251128-26', 289, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 360.40, 0.00, 360.40, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(61, 'PRD-20251128-27', 310, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 95.60, 0.00, 95.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(62, 'PRD-20251128-28', 306, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 39.20, 0.00, 39.20, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(63, 'PRD-20251128-29', 307, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 76.60, 0.00, 76.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(64, 'PRD-20251128-30', 305, '2025-11-28', '2025-11-28 11:00:00', 1, 0, NULL, 0.00, 0, NULL, '', 'PCT', 0.00, 0.00, 285.60, 0.00, 285.60, NULL, NULL, '', 'Test Delivery', 'Test Address', '0000000000', '2025-11-28', 'AM', 1, NULL, NULL, 'test-script', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(65, 'CART292656865', 289, '2026-02-02', '2026-02-02 22:57:08', 1, 0, NULL, 5.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 10.00, NULL, NULL, '', NULL, '', '', '2026-02-02', '', 1, 'CART', 63, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(66, 'CART351990166', 289, '2026-02-02', '2026-02-02 23:17:15', 1, 0, NULL, 5.00, 0, 1, '', '', 0.00, 0.00, 275.00, 0.00, 280.00, NULL, NULL, '', NULL, '', '', '2026-02-02', '', 1, 'CART', 62, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(67, 'CART623125967', 289, '2026-02-02', '2026-02-02 23:24:26', 1, 0, NULL, 10.00, 0, 1, '', '', 0.00, 0.00, 55.00, 0.00, 65.00, NULL, NULL, '', NULL, '', '', '2026-02-05', '', 1, 'CART', 62, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(68, 'CART857348168', 289, '2026-02-02', '2026-02-02 23:26:11', 1, 0, NULL, 3.00, 0, 1, '', '', 0.00, 0.00, 55.00, 0.00, 58.00, NULL, NULL, '', NULL, '', '', '2026-02-04', '', 1, 'CART', 63, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(69, 'CART111441769', 289, '2026-02-04', '2026-02-04 12:08:07', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 105.00, 0.00, 105.00, NULL, NULL, '', NULL, '', '', '2026-02-04', '', 1, 'CART', 62, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(70, 'INV00070', 289, '2026-02-04', '2026-02-04 23:08:38', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-04 23:08:38', '0', '1.0000'),
(71, 'INV00071', 289, '2026-02-05', '2026-02-04 23:09:49', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 112.90, 0.00, 115.90, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-05', '10:00-12:00', 1, NULL, 62, 'System', '2026-02-05 08:53:07', '2026-02-04 23:09:49', '0', '1.0000'),
(72, 'INV00072', 289, '2026-02-04', '2026-02-04 23:10:24', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-04 23:10:24', '0', '1.0000'),
(73, 'INV00073', 241, '2026-02-04', '2026-02-04 23:22:32', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-04 23:22:32', '0', '1.0000'),
(74, 'INV00074', 241, '2026-02-04', '2026-02-04 23:25:09', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 156.00, 0.00, 159.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-04', '10:00-12:00', 1, NULL, 67, 'System', '2026-02-05 00:31:25', '2026-02-04 23:25:09', '0', '1.0000'),
(76, 'INV00075', 241, '2026-02-04', '2026-02-04 23:27:08', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-04 23:27:08', '0', '1.0000'),
(77, 'INV00077', 241, '2026-02-04', '2026-02-04 23:28:59', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-04 23:28:59', '0', '1.0000'),
(78, 'INV00078', 241, '2026-02-04', '2026-02-04 23:30:06', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-04', '10:00-12:00', 1, NULL, 67, 'System', '2026-02-04 23:44:47', '2026-02-04 23:30:06', '0', '1.0000'),
(79, 'INV00079', 241, '2026-02-05', '2026-02-04 23:33:03', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 35.00, 0.00, 38.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-05', '10:00-12:00', 1, NULL, 67, 'System', '2026-02-11 05:18:17', '2026-02-04 23:33:03', '0', '1.0000'),
(106, 'INV00106', 241, '2026-02-08', '2026-02-05 10:38:33', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-08', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:38:33', '0', '1.0000'),
(99, 'INV00080', 241, '2026-02-06', '2026-02-05 10:37:50', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-06', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:37:50', '0', '1.0000'),
(109, 'INV00109', 241, '2026-02-11', '2026-02-05 10:38:33', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-11', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:38:33', '0', '1.0000'),
(108, 'INV00108', 241, '2026-02-10', '2026-02-05 10:38:33', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:38:33', '0', '1.0000'),
(107, 'INV00107', 241, '2026-02-09', '2026-02-05 10:38:33', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:38:33', '0', '1.0000'),
(105, 'INV00100', 241, '2026-02-07', '2026-02-05 10:38:33', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:38:33', '0', '1.0000'),
(110, 'INV00110', 309, '2026-02-05', '2026-02-05 10:39:31', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 15.00, 0.00, 18.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '', '2026-02-05', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:39:31', '0', '1.0000'),
(111, 'INV00111', 309, '2026-02-07', '2026-02-05 10:39:31', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 20.00, 0.00, 23.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '', '2026-02-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:39:31', '0', '1.0000'),
(112, 'INV00112', 309, '2026-02-09', '2026-02-05 10:39:31', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '', '2026-02-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:39:31', '0', '1.0000'),
(113, 'INV00113', 309, '2026-02-10', '2026-02-05 10:39:31', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '', '2026-02-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:39:31', '0', '1.0000'),
(114, 'INV00114', 308, '2026-02-05', '2026-02-05 10:41:08', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-05', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:41:08', '0', '1.0000'),
(115, 'INV00115', 308, '2026-02-07', '2026-02-05 10:41:08', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:41:08', '0', '1.0000'),
(116, 'INV00116', 308, '2026-02-09', '2026-02-05 10:41:08', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:41:08', '0', '1.0000'),
(117, 'INV00117', 308, '2026-02-10', '2026-02-05 10:41:08', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:41:08', '0', '1.0000'),
(118, 'INV00118', 241, '2026-02-12', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-12', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(119, 'INV00119', 241, '2026-02-13', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-13', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(120, 'INV00120', 241, '2026-02-14', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(121, 'INV00121', 241, '2026-02-15', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-15', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(122, 'INV00122', 241, '2026-02-16', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(123, 'INV00123', 241, '2026-02-17', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-17', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(124, 'INV00124', 241, '2026-02-18', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-18', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(125, 'INV00125', 241, '2026-02-19', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-19', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(126, 'INV00126', 241, '2026-02-20', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-20', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(127, 'INV00127', 241, '2026-02-21', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-21', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(128, 'INV00128', 241, '2026-02-22', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-22', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(129, 'INV00129', 241, '2026-02-23', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(130, 'INV00130', 241, '2026-02-24', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(131, 'INV00131', 241, '2026-02-25', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-25', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(132, 'INV00132', 241, '2026-02-26', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street, Apt 1, Test City, 12345 test', '123-456-7890', '2026-02-26', '10:00-12:00', 1, NULL, NULL, 'System', '2026-02-18 16:00:28', '2026-02-05 10:46:34', '0', '1.0000'),
(133, 'INV00133', 241, '2026-02-27', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-27', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(134, 'INV00134', 241, '2026-02-28', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-02-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(135, 'INV00135', 241, '2026-03-01', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-01', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(136, 'INV00136', 241, '2026-03-02', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-02', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(137, 'INV00137', 241, '2026-03-03', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-03', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(138, 'INV00138', 241, '2026-03-04', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(139, 'INV00139', 241, '2026-03-05', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-05', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(140, 'INV00140', 241, '2026-03-06', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-06', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(141, 'INV00141', 241, '2026-03-07', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(142, 'INV00142', 241, '2026-03-08', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-08', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(143, 'INV00143', 241, '2026-03-09', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(144, 'INV00144', 241, '2026-03-10', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(145, 'INV00145', 241, '2026-03-11', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-11', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(146, 'INV00146', 241, '2026-03-12', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-12', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(147, 'INV00147', 241, '2026-03-13', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-13', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(148, 'INV00148', 241, '2026-03-14', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(149, 'INV00149', 241, '2026-03-15', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-15', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(150, 'INV00150', 241, '2026-03-16', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(151, 'INV00151', 241, '2026-03-17', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-17', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(152, 'INV00152', 241, '2026-03-18', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-18', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(153, 'INV00153', 241, '2026-03-19', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-19', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(154, 'INV00154', 241, '2026-03-20', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-20', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(155, 'INV00155', 241, '2026-03-21', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-21', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(156, 'INV00156', 241, '2026-03-22', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-22', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(157, 'INV00157', 241, '2026-03-23', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(158, 'INV00158', 241, '2026-03-24', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(159, 'INV00159', 241, '2026-03-25', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-25', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(160, 'INV00160', 241, '2026-03-26', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-26', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(161, 'INV00161', 241, '2026-03-27', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-27', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(162, 'INV00162', 241, '2026-03-28', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(163, 'INV00163', 241, '2026-03-29', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-29', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(164, 'INV00164', 241, '2026-03-30', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-30', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(165, 'INV00165', 241, '2026-03-31', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-03-31', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(166, 'INV00166', 241, '2026-04-01', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-01', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(167, 'INV00167', 241, '2026-04-02', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-02', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(168, 'INV00168', 241, '2026-04-03', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-03', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:46:34', '0', '1.0000'),
(169, 'INV00169', 241, '2026-04-04', '2026-02-05 10:46:34', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-04', '10:00-12:00', 1, NULL, 67, 'System', '2026-02-12 05:45:58', '2026-02-05 10:46:34', '0', '1.0000'),
(170, 'INV00170', 308, '2026-02-12', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-12', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(171, 'INV00171', 308, '2026-02-14', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(172, 'INV00172', 308, '2026-02-16', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(173, 'INV00173', 308, '2026-02-17', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-17', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(174, 'INV00174', 308, '2026-02-19', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-19', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(175, 'INV00175', 308, '2026-02-21', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-21', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(176, 'INV00176', 308, '2026-02-23', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(177, 'INV00177', 308, '2026-02-24', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(178, 'INV00178', 308, '2026-02-26', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-26', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(179, 'INV00179', 308, '2026-02-28', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-02-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(180, 'INV00180', 308, '2026-03-02', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-02', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(181, 'INV00181', 308, '2026-03-03', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-03', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(182, 'INV00182', 308, '2026-03-05', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-05', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(183, 'INV00183', 308, '2026-03-07', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(184, 'INV00184', 308, '2026-03-09', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(185, 'INV00185', 308, '2026-03-10', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(186, 'INV00186', 308, '2026-03-12', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-12', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(187, 'INV00187', 308, '2026-03-14', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(188, 'INV00188', 308, '2026-03-16', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(189, 'INV00189', 308, '2026-03-17', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-17', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000');
INSERT INTO `invoice_hedder` (`invoice_h_id`, `invoice_h_code`, `invoice_h_customer_id`, `invoice_h_date`, `invoice_h_datetime`, `invoice_h_location`, `invoice_h_delivery_city`, `delivery_city_name`, `invoice_h_delivery_cost`, `invoice_h_delivery_mode`, `invoice_h_pay_type`, `invoice_h_coupun_code`, `invoice_h_coupon_type`, `invoice_h_coupon_rate`, `invoice_h_coupon_value`, `invoice_h_net_value`, `invoice_h_vat_value`, `invoice_h_gross_value`, `invoice_h_check_Ref`, `invoice_h_card_Ref`, `invoice_h_order_note`, `invoice_h_delivery_name`, `invoice_h_delivery_address`, `invoice_h_delivery_contact_no`, `invoice_h_delivery_date`, `invoice_h_delivery_time`, `invoice_h_status`, `order_type`, `shipping_address_id`, `add_by`, `updated_at`, `invoice_h_approve_date`, `CustomerCurrencyId`, `CurrencyRate`) VALUES
(190, 'INV00190', 308, '2026-03-19', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-19', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(191, 'INV00191', 308, '2026-03-21', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-21', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(192, 'INV00192', 308, '2026-03-23', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(193, 'INV00193', 308, '2026-03-24', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(194, 'INV00194', 308, '2026-03-26', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-26', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(195, 'INV00195', 308, '2026-03-28', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(196, 'INV00196', 308, '2026-03-30', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-30', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(197, 'INV00197', 308, '2026-03-31', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-03-31', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(198, 'INV00198', 308, '2026-04-02', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-02', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(199, 'INV00199', 308, '2026-04-04', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(200, 'INV00200', 308, '2026-04-06', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-06', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(201, 'INV00201', 308, '2026-04-07', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(202, 'INV00202', 308, '2026-04-09', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(203, 'INV00203', 308, '2026-04-11', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-11', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(204, 'INV00204', 308, '2026-04-13', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-13', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(205, 'INV00205', 308, '2026-04-14', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(206, 'INV00206', 308, '2026-04-16', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(207, 'INV00207', 308, '2026-04-18', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-18', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(208, 'INV00208', 308, '2026-04-20', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-20', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(209, 'INV00209', 308, '2026-04-21', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-21', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(210, 'INV00210', 308, '2026-04-23', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(211, 'INV00211', 308, '2026-04-25', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-25', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(212, 'INV00212', 308, '2026-04-27', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-27', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(213, 'INV00213', 308, '2026-04-28', '2026-02-05 10:49:53', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'Malith Sachinthana', '375/12 , 8th lane, Rathnarama Road Hokandara North', '0771998880', '2026-04-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-05 10:49:53', '0', '1.0000'),
(214, 'INV00214', 241, '2026-04-05', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-05', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(215, 'INV00215', 241, '2026-04-06', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 16.00, 0.00, 19.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-06', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(216, 'INV00216', 241, '2026-04-07', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(217, 'INV00217', 241, '2026-04-08', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-08', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(218, 'INV00218', 241, '2026-04-09', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 13.00, 0.00, 16.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(219, 'INV00219', 241, '2026-04-10', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 6.00, 0.00, 9.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(220, 'INV00220', 241, '2026-04-11', '2026-02-15 22:25:21', 1, 0, 'Test City', 3.00, 0, 1, '', '', 0.00, 0.00, 11.00, 0.00, 14.00, NULL, NULL, 'Standing Order', 'Rao G', '123 Test Street Apt 1', '123-456-7890', '2026-04-11', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-15 22:25:21', '0', '1.0000'),
(221, 'CART8621452221', 289, '2026-02-18', '2026-02-18 16:01:36', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 50.00, 0.00, 50.00, NULL, NULL, '', NULL, '375/12 , 8th lane, Rathnarama Road Hokandara North, Hokandara, western, 10118 test address, United States', '', '2026-02-18', '', 1, 'CART', NULL, 'Malith', '2026-02-18 16:05:12', '0000-00-00 00:00:00', '0', '0.0000'),
(222, 'INV00222', 351, '2026-02-20', '2026-02-20 02:58:19', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 8.00, 0.00, 11.00, NULL, NULL, 'Standing Order', 'CUST-0033322asdada', '375/12 Rathnarama Road Hokandara North', '0771998880', '2026-02-20', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-20 02:58:19', '0', '1.0000'),
(223, 'INV00223', 351, '2026-02-22', '2026-02-20 02:58:19', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 4.00, 0.00, 7.00, NULL, NULL, 'Standing Order', 'CUST-0033322asdada', '375/12 Rathnarama Road Hokandara North', '0771998880', '2026-02-22', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-20 02:58:19', '0', '1.0000'),
(224, 'CART1704266224', 352, '2026-02-22', '2026-02-22 18:35:42', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 5.00, NULL, NULL, '', NULL, 'araliya - Primary, araliya foods colormbo sri lanka, Hokandara, 10118', '', '2026-02-26', '', 1, 'CART', 113, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(225, 'CART3584917225', 309, '2026-02-22', '2026-02-22 23:22:30', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 5.00, NULL, NULL, '', NULL, 'Office Address, 375/12 Rathnarama Road Hokandara North, Hokandara, western, 10118', '', '2026-02-26', '', 1, 'CART', 112, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(226, 'CART7403174226', 289, '2026-02-22', '2026-02-22 23:24:10', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 15.00, 0.00, 15.00, NULL, NULL, '', NULL, 'Outlet 002, 375/12,, rathnarama Road Hokandara, Hokandara North, West, 10118', '', '2026-02-26', '', 1, 'CART', 62, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(227, 'INV00227', 289, '2026-02-23', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(228, 'INV00228', 289, '2026-02-24', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(229, 'INV00229', 289, '2026-02-25', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-25', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(230, 'INV00230', 289, '2026-02-26', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-26', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(231, 'INV00231', 289, '2026-02-27', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-27', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(232, 'INV00232', 289, '2026-02-28', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-02-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(233, 'INV00233', 289, '2026-03-02', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-02', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(234, 'INV00234', 289, '2026-03-03', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-03', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(235, 'INV00235', 289, '2026-03-04', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(236, 'INV00236', 289, '2026-03-05', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-05', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(237, 'INV00237', 289, '2026-03-06', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-06', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(238, 'INV00238', 289, '2026-03-07', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(239, 'INV00239', 289, '2026-03-09', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(240, 'INV00240', 289, '2026-03-10', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(241, 'INV00241', 289, '2026-03-11', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-11', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(242, 'INV00242', 289, '2026-03-12', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-12', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(243, 'INV00243', 289, '2026-03-13', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-13', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(244, 'INV00244', 289, '2026-03-14', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(245, 'INV00245', 289, '2026-03-16', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(246, 'INV00246', 289, '2026-03-17', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-17', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(247, 'INV00247', 289, '2026-03-18', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-18', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(248, 'INV00248', 289, '2026-03-19', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-19', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(249, 'INV00249', 289, '2026-03-20', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-20', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(250, 'INV00250', 289, '2026-03-21', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-21', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(251, 'INV00251', 289, '2026-03-23', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(252, 'INV00252', 289, '2026-03-24', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(253, 'INV00253', 289, '2026-03-25', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-25', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(254, 'INV00254', 289, '2026-03-26', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-26', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(255, 'INV00255', 289, '2026-03-27', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-27', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(256, 'INV00256', 289, '2026-03-28', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-28', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(257, 'INV00257', 289, '2026-03-30', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-30', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(258, 'INV00258', 289, '2026-03-31', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-03-31', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(259, 'INV00259', 289, '2026-04-01', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-01', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(260, 'INV00260', 289, '2026-04-02', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-02', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(261, 'INV00261', 289, '2026-04-03', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-03', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(262, 'INV00262', 289, '2026-04-04', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-04', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(263, 'INV00263', 289, '2026-04-06', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-06', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(264, 'INV00264', 289, '2026-04-07', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-07', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(265, 'INV00265', 289, '2026-04-08', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-08', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(266, 'INV00266', 289, '2026-04-09', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-09', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(267, 'INV00267', 289, '2026-04-10', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-10', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(268, 'INV00268', 289, '2026-04-11', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-11', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(269, 'INV00269', 289, '2026-04-13', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-13', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(270, 'INV00270', 289, '2026-04-14', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-14', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(271, 'INV00271', 289, '2026-04-15', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 108.90, 0.00, 111.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-15', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(272, 'INV00272', 289, '2026-04-16', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 149.20, 0.00, 152.20, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-16', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(273, 'INV00273', 289, '2026-04-17', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-17', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(274, 'INV00274', 289, '2026-04-18', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 72.60, 0.00, 75.60, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-18', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(275, 'INV00275', 289, '2026-04-20', '2026-02-22 23:25:11', 1, 0, 'Hokandara North', 3.00, 0, 1, '', '', 0.00, 0.00, 116.90, 0.00, 119.90, NULL, NULL, 'Standing Order', 'Malith SachinthanaDD', '375/12, rathnarama Road Hokandara', '771998880', '2026-04-20', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:25:11', '0', '1.0000'),
(276, 'CART6849479276', 307, '2026-02-22', '2026-02-22 23:27:29', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 60.00, 0.00, 60.00, NULL, NULL, '', NULL, 'Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, western, 10118', '', '2026-02-25', '', 1, 'CART', 119, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(277, 'INV00277', 307, '2026-02-23', '2026-02-22 23:28:01', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 19.00, 0.00, 22.00, NULL, NULL, 'Standing Order', 'JAMES BOND', '375/12 Rathnarama Road Hokandara North', '0', '2026-02-23', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:28:01', '0', '1.0000'),
(278, 'INV00278', 307, '2026-02-24', '2026-02-22 23:28:01', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 13.00, NULL, NULL, 'Standing Order', 'JAMES BOND', '375/12 Rathnarama Road Hokandara North', '0', '2026-02-24', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:28:01', '0', '1.0000'),
(279, 'INV00279', 307, '2026-02-25', '2026-02-22 23:28:01', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 8.00, NULL, NULL, 'Standing Order', 'JAMES BOND', '375/12 Rathnarama Road Hokandara North', '0', '2026-02-25', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:28:01', '0', '1.0000'),
(280, 'INV00280', 307, '2026-02-26', '2026-02-22 23:28:01', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 20.00, 0.00, 23.00, NULL, NULL, 'Standing Order', 'JAMES BOND', '375/12 Rathnarama Road Hokandara North', '0', '2026-02-26', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:28:01', '0', '1.0000'),
(281, 'INV00281', 307, '2026-02-27', '2026-02-22 23:28:01', 1, 0, 'Hokandara', 3.00, 0, 1, '', '', 0.00, 0.00, 2.00, 0.00, 5.00, NULL, NULL, 'Standing Order', 'JAMES BOND', '375/12 Rathnarama Road Hokandara North', '0', '2026-02-27', '10:00-12:00', 1, NULL, NULL, 'System', NULL, '2026-02-22 23:28:01', '0', '1.0000'),
(282, 'CART7786959282', 354, '2026-02-23', '2026-02-23 22:51:22', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 2.00, 0.00, 2.00, NULL, NULL, '', NULL, 'araliya - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-02-26', '', 1, 'CART', 115, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(283, 'CART9129119283', 354, '2026-02-23', '2026-02-23 22:54:10', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 10.00, NULL, NULL, '', NULL, 'araliya - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-02-28', '', 1, 'CART', 115, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(284, 'CART7038833284', 354, '2026-02-23', '2026-02-23 23:05:14', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 30.00, 0.00, 30.00, NULL, NULL, '', NULL, 'araliya - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-02-25', '', 1, 'CART', 115, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(285, 'CART6919786285', 351, '2026-02-25', '2026-02-25 10:58:21', 1, 0, NULL, 0.00, 0, 1, '', 'PCT', 10.00, 5.00, 50.00, 0.00, 45.00, NULL, NULL, '', NULL, 'CUST-0033322asdada - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-02-25', '', 1, 'CART', 111, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(286, 'CART7559097286', 351, '2026-02-25', '2026-02-25 11:20:17', 1, 0, NULL, 0.00, 0, 1, '', 'PCT', 10.00, 5.00, 50.00, 0.00, 45.00, NULL, NULL, '', NULL, 'CUST-0033322asdada - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-02-26', '', 1, 'CART', 111, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(287, 'CART9816623287', 307, '2026-02-26', '2026-02-26 07:37:14', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 5.00, 0.00, 5.00, NULL, NULL, '', NULL, 'Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, western, 10118', '', '2026-02-28', '', 1, 'CART', 119, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(288, 'CART8270664288', 351, '2026-02-26', '2026-02-26 11:51:07', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 10.00, 0.00, 10.00, NULL, NULL, '', NULL, 'CUST-0033322asdada - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-02-28', '', 1, 'CART', 111, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000'),
(289, 'CART8168602289', 351, '2026-02-26', '2026-02-26 11:53:41', 1, 0, NULL, 0.00, 0, 1, '', '', 0.00, 0.00, 20.00, 0.00, 20.00, NULL, NULL, '', NULL, 'CUST-0033322asdada - Primary, 375/12 Rathnarama Road Hokandara North, Hokandara, 10118', '', '2026-08-29', '', 1, 'CART', 111, 'Malith', NULL, '0000-00-00 00:00:00', '0', '0.0000');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_item_details`
--

CREATE TABLE `invoice_item_details` (
  `invoice_d_id` int(10) NOT NULL,
  `invoice_h_id` int(10) DEFAULT NULL,
  `invoice_d_item` int(10) DEFAULT NULL,
  `invoice_d_qty` double(20,2) DEFAULT NULL,
  `invoice_d_rate` double(20,2) DEFAULT NULL,
  `invoice_d_discount_value` double(20,2) DEFAULT NULL,
  `invoice_d_discount_type` int(10) DEFAULT NULL,
  `invoice_d_warranty_month` int(4) DEFAULT NULL,
  `invoice_d_vat` double(20,2) DEFAULT NULL,
  `invoice_d_vat_rate` double(20,2) DEFAULT NULL,
  `invoice_d_qty_balance` double(20,2) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_settings`
--

CREATE TABLE `invoice_settings` (
  `id` int(11) NOT NULL,
  `invoice_logo` varchar(500) DEFAULT NULL,
  `receipt_name` varchar(255) DEFAULT NULL,
  `receipt_address` text DEFAULT NULL,
  `receipt_phone` varchar(100) DEFAULT NULL,
  `receipt_email` varchar(255) DEFAULT NULL,
  `receipt_footer` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `invoice_settings`
--

INSERT INTO `invoice_settings` (`id`, `invoice_logo`, `receipt_name`, `receipt_address`, `receipt_phone`, `receipt_email`, `receipt_footer`, `created_at`, `updated_at`) VALUES
(1, 'uploads/invoice/invoice_logo_1770268204.png', 'BAKERY', '375/12 , 8th lane, Rathnarama Road Hokandara North', '+94771998880', 'malith.sachinthana@gmail.com', 'Thank you!', '2026-02-04 19:07:23', '2026-02-05 05:10:04');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_status`
--

CREATE TABLE `invoice_status` (
  `id` int(10) NOT NULL,
  `status` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `invoice_status`
--

INSERT INTO `invoice_status` (`id`, `status`) VALUES
(1, 'completed '),
(2, 'pending'),
(3, 'Quotation');

-- --------------------------------------------------------

--
-- Table structure for table `itemmapping`
--

CREATE TABLE `itemmapping` (
  `id` int(10) NOT NULL,
  `itemId` int(10) NOT NULL DEFAULT 0,
  `groupId` int(10) NOT NULL DEFAULT 0,
  `typeId` int(10) NOT NULL DEFAULT 0,
  `categoryId` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `itemmapping`
--

INSERT INTO `itemmapping` (`id`, `itemId`, `groupId`, `typeId`, `categoryId`) VALUES
(1, 60, 1, 1, 5),
(61, 1, 1, 7, 60),
(67, 108, 2, 1, 3),
(66, 121, 1, 2, 13),
(68, 116, 2, 1, 8),
(69, 109, 2, 1, 2),
(70, 106, 2, 1, 3),
(71, 99, 2, 1, 1),
(72, 104, 2, 2, 12),
(73, 110, 1, 1, 1),
(156, 111, 2, 7, 28),
(157, 97, 1, 1, 2),
(76, 98, 2, 2, 12),
(77, 120, 2, 10, 22),
(78, 107, 2, 1, 3),
(162, 36, 2, 7, 26),
(80, 35, 1, 1, 4),
(81, 40, 1, 6, 22),
(82, 38, 1, 1, 2),
(83, 113, 2, 1, 9),
(84, 69, 1, 1, 10),
(85, 28, 1, 1, 9),
(86, 112, 1, 1, 5),
(87, 13, 1, 1, 2),
(88, 14, 1, 1, 2),
(89, 12, 1, 1, 2),
(90, 7, 1, 1, 7),
(171, 8, 1, 1, 5),
(92, 9, 1, 1, 10),
(93, 6, 1, 1, 4),
(94, 68, 1, 1, 10),
(95, 22, 1, 1, 7),
(96, 3, 1, 1, 2),
(185, 29, 2, 7, 39),
(98, 23, 1, 1, 2),
(99, 27, 2, 2, 13),
(100, 26, 1, 1, 2),
(101, 25, 1, 11, 21),
(190, 15, 2, 7, 26),
(192, 117, 1, 1, 3),
(194, 74, 1, 1, 8),
(195, 57, 2, 7, 26),
(196, 57, 1, 1, 4),
(107, 58, 1, 1, 1),
(108, 62, 2, 1, 1),
(203, 119, 2, 10, 34),
(206, 94, 1, 1, 5),
(207, 94, 1, 1, 1),
(211, 95, 2, 7, 24),
(113, 96, 1, 1, 1),
(214, 59, 2, 7, 30),
(216, 34, 2, 7, 25),
(219, 32, 1, 1, 3),
(221, 45, 1, 2, 13),
(118, 45, 1, 1, 2),
(225, 114, 1, 1, 9),
(227, 70, 1, 10, 34),
(121, 115, 1, 1, 1),
(228, 70, 2, 10, 34),
(231, 19, 1, 1, 5),
(124, 19, 1, 1, 1),
(235, 20, 1, 1, 3),
(237, 123, 2, 7, 28),
(127, 122, 1, 1, 3),
(128, 123, 1, 2, 12),
(129, 124, 1, 2, 13),
(130, 125, 1, 2, 13),
(245, 61, 1, 1, 5),
(132, 41, 1, 1, 1),
(133, 44, 1, 1, 1),
(134, 56, 1, 1, 1),
(135, 61, 1, 1, 1),
(250, 67, 1, 1, 3),
(137, 66, 1, 8, 20),
(138, 67, 1, 1, 1),
(139, 101, 1, 1, 2),
(140, 103, 1, 1, 2),
(141, 100, 2, 1, 3),
(142, 102, 2, 1, 3),
(143, 25, 1, 10, 33),
(144, 109, 1, 1, 2),
(147, 60, 2, 7, 30),
(148, 121, 2, 7, 28),
(149, 108, 1, 1, 3),
(150, 116, 1, 1, 8),
(151, 105, 1, 1, 4),
(152, 105, 2, 7, 25),
(153, 104, 1, 2, 15),
(154, 110, 2, 7, 25),
(155, 111, 1, 2, 12),
(158, 107, 1, 1, 3),
(161, 36, 1, 1, 3),
(160, 35, 2, 7, 25),
(163, 40, 2, 10, 34),
(164, 38, 2, 7, 25),
(169, 6, 2, 7, 26),
(166, 38, 1, 2, 15),
(168, 9, 2, 7, 36),
(170, 8, 2, 7, 30),
(172, 7, 2, 7, 30),
(173, 12, 2, 7, 30),
(174, 12, 1, 1, 5),
(175, 14, 2, 7, 37),
(176, 13, 2, 7, 38),
(177, 69, 2, 7, 36),
(178, 68, 2, 7, 36),
(179, 22, 2, 7, 30),
(180, 3, 1, 1, 5),
(181, 3, 1, 1, 7),
(182, 3, 2, 7, 27),
(183, 3, 2, 7, 30),
(184, 29, 1, 1, 9),
(186, 23, 2, 7, 28),
(187, 27, 1, 1, 7),
(188, 27, 1, 1, 2),
(189, 15, 1, 1, 4),
(191, 16, 1, 1, 4),
(193, 117, 2, 7, 25),
(197, 58, 2, 7, 24),
(198, 58, 2, 7, 38),
(199, 58, 1, 1, 2),
(200, 62, 1, 1, 5),
(201, 62, 2, 7, 30),
(202, 119, 1, 10, 34),
(204, 118, 1, 10, 34),
(205, 118, 2, 10, 34),
(208, 94, 2, 7, 30),
(209, 95, 1, 1, 5),
(210, 95, 1, 1, 2),
(212, 95, 2, 7, 30),
(213, 59, 1, 1, 5),
(215, 34, 1, 1, 3),
(217, 33, 1, 1, 3),
(218, 33, 2, 7, 25),
(220, 32, 2, 7, 25),
(222, 45, 2, 7, 28),
(223, 39, 1, 10, 34),
(224, 39, 2, 10, 34),
(226, 114, 2, 7, 39),
(229, 18, 1, 1, 10),
(230, 18, 2, 7, 36),
(232, 19, 2, 7, 30),
(233, 19, 1, 1, 2),
(234, 11, 1, 1, 4),
(236, 20, 2, 7, 25),
(238, 30, 1, 1, 10),
(239, 30, 2, 7, 36),
(244, 56, 1, 1, 11),
(242, 56, 1, 1, 2),
(243, 56, 2, 7, 37),
(246, 61, 2, 7, 24),
(247, 61, 2, 7, 30),
(248, 64, 1, 1, 5),
(249, 64, 2, 7, 30),
(251, 101, 1, 1, 15),
(252, 101, 1, 1, 3),
(253, 101, 2, 7, 25),
(254, 103, 1, 1, 3),
(255, 103, 2, 7, 25),
(256, 102, 1, 1, 3),
(257, 102, 2, 7, 26),
(258, 121, 1, 1, 2),
(259, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `item_master`
--

CREATE TABLE `item_master` (
  `item_id` int(10) NOT NULL,
  `item_code` varchar(40) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_group` int(10) DEFAULT NULL,
  `item_type` int(10) DEFAULT NULL,
  `item_category` int(10) DEFAULT NULL,
  `item_discription` text DEFAULT NULL,
  `item_uom` int(10) DEFAULT NULL,
  `order_qty_min` decimal(10,2) DEFAULT NULL,
  `order_qty_max` decimal(10,2) DEFAULT NULL,
  `item_purchase_price` decimal(10,2) DEFAULT 0.00,
  `item_min_selling_price` double(20,2) DEFAULT 0.00,
  `item_normal_selling_price` double(20,2) DEFAULT 0.00,
  `others_selling_price` double(22,2) DEFAULT NULL,
  `item_cash_selling_price` double(20,2) DEFAULT 0.00,
  `item_cradit_selling_price` double(20,2) DEFAULT 0.00,
  `item_promotion_status` int(1) NOT NULL DEFAULT 0,
  `item_promotion_price` double(22,2) NOT NULL,
  `item_image` varchar(100) NOT NULL,
  `imageParth` text DEFAULT NULL,
  `item_discount` float(22,2) NOT NULL DEFAULT 0.00,
  `item_active` enum('N','Y') NOT NULL DEFAULT 'Y',
  `item_warranty` varchar(40) DEFAULT '1',
  `item_barcode` text DEFAULT NULL,
  `is_hamper` int(1) NOT NULL DEFAULT 0,
  `item_has_sirial` enum('N','Y') NOT NULL DEFAULT 'N',
  `item_vat` enum('N','Y') NOT NULL DEFAULT 'N',
  `item_dispay_home` int(11) NOT NULL DEFAULT 1,
  `item_product_of_day` int(11) NOT NULL,
  `item_cod` enum('enable','disable') NOT NULL,
  `item_mode` enum('Normal','Offline','OutofStock') NOT NULL DEFAULT 'Normal',
  `view_count` int(10) NOT NULL DEFAULT 0,
  `url` varchar(100) NOT NULL,
  `item_weight` double(22,2) NOT NULL,
  `low_stock_qty` int(11) NOT NULL DEFAULT 5,
  `pack_size` varchar(50) DEFAULT NULL,
  `acc_posting_grp_code` varchar(50) DEFAULT NULL,
  `gst_vat_code` varchar(50) DEFAULT NULL,
  `immediate_pickups` enum('No','Yes') DEFAULT 'No',
  `nutritional_label` varchar(255) DEFAULT NULL,
  `sale_or_return` tinyint(1) NOT NULL DEFAULT 0,
  `product_specification` varchar(255) DEFAULT NULL,
  `live` varchar(3) NOT NULL DEFAULT 'yes' CHECK (`live` in ('yes','no')),
  `hide_to_all_customers` tinyint(1) NOT NULL DEFAULT 0,
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_weight_g` int(11) DEFAULT NULL,
  `pack_weight_g` int(11) DEFAULT NULL,
  `minimum_order` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `default_label` varchar(255) DEFAULT NULL,
  `food_declarations` text DEFAULT NULL,
  `seasonal_rule` varchar(255) DEFAULT NULL,
  `avail_monday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_monday` in (0,1)),
  `avail_tuesday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_tuesday` in (0,1)),
  `avail_wednesday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_wednesday` in (0,1)),
  `avail_thursday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_thursday` in (0,1)),
  `avail_friday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_friday` in (0,1)),
  `avail_saturday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_saturday` in (0,1)),
  `avail_sunday` smallint(6) NOT NULL DEFAULT 1 CHECK (`avail_sunday` in (0,1)),
  `unit_of_measure` varchar(20) NOT NULL DEFAULT 'Gram',
  `pack_type` varchar(20) NOT NULL DEFAULT 'Bag',
  `is_raw_material` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag to identify raw materials for purchase orders'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `item_master`
--

INSERT INTO `item_master` (`item_id`, `item_code`, `item_name`, `item_group`, `item_type`, `item_category`, `item_discription`, `item_uom`, `order_qty_min`, `order_qty_max`, `item_purchase_price`, `item_min_selling_price`, `item_normal_selling_price`, `others_selling_price`, `item_cash_selling_price`, `item_cradit_selling_price`, `item_promotion_status`, `item_promotion_price`, `item_image`, `imageParth`, `item_discount`, `item_active`, `item_warranty`, `item_barcode`, `is_hamper`, `item_has_sirial`, `item_vat`, `item_dispay_home`, `item_product_of_day`, `item_cod`, `item_mode`, `view_count`, `url`, `item_weight`, `low_stock_qty`, `pack_size`, `acc_posting_grp_code`, `gst_vat_code`, `immediate_pickups`, `nutritional_label`, `sale_or_return`, `product_specification`, `live`, `hide_to_all_customers`, `wholesale_price`, `retail_price`, `item_weight_g`, `pack_weight_g`, `minimum_order`, `description`, `default_label`, `food_declarations`, `seasonal_rule`, `avail_monday`, `avail_tuesday`, `avail_wednesday`, `avail_thursday`, `avail_friday`, `avail_saturday`, `avail_sunday`, `unit_of_measure`, `pack_type`, `is_raw_material`) VALUES
(1, 'IG01', 'Classic White Bread Loaf', 1, 1, 1, '', 1, NULL, NULL, '1.00', 0.00, 1.00, 0.00, 0.00, 0.00, 0, 0.00, 'classic-white-bread-loaf-1.png', 'images/product_img/2026/04/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Classic-White-Bread-Loaf-', 0.50, 10, NULL, NULL, NULL, 'Yes', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, 10, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(2, 'IG02', 'Chocolate Fudge Cake (1kg)', 1, 2, 3, '', 1, NULL, NULL, '5.00', 0.00, 5.00, 5.00, 0.00, 0.00, 0, 0.00, 'Chocolate-Fudge-Cake-1kg-2.png', 'images/product_img/2025/10/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Chocolate-Fudge-Cake-1kg-2', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(3, 'IG03', 'Omega-Red', 1, 1, 1, '', 1, '1.00', '10.00', '1.00', 0.00, 2.00, 0.00, 0.00, 0.00, 0, 0.00, 'OmegaRed-3.png', 'images/product_img/2025/11/', 0.00, 'Y', NULL, NULL, 0, 'N', 'N', 1, 0, 'disable', 'Normal', 0, 'OmegaRed-3', 1.00, 5, '1', 'Te33', 'NOGST', 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(4, 'TP20260125-1', 'Test Product 1', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '39.12', 0.00, 65.20, 65.20, 0.00, 0.00, 0, 0.00, 'Test Product 1.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 1-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 1),
(5, 'TP20260125-2', 'Test Product 2', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '13.86', 0.00, 23.10, 23.10, 0.00, 0.00, 0, 0.00, 'Test Product 2.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 2-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(6, 'TP20260125-3', 'Test Product 3', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '51.36', 0.00, 85.60, 85.60, 0.00, 0.00, 0, 0.00, 'Test Product 3.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 3-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(7, 'TP20260125-4', 'Test Product 4', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '29.22', 0.00, 48.70, 48.70, 0.00, 0.00, 0, 0.00, 'Test Product 4.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 4-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(8, 'TP20260125-5', 'Test Product 5', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '8.10', 0.00, 13.50, 13.50, 0.00, 0.00, 0, 0.00, 'Test Product 5.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 5-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(9, 'TP20260125-6', 'Test Product 6', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '38.70', 0.00, 64.50, 64.50, 0.00, 0.00, 0, 0.00, 'Test Product 6.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 6-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(10, 'TP20260125-7', 'Test Product 7', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '21.00', 0.00, 35.00, 35.00, 0.00, 0.00, 0, 0.00, 'Test Product 7.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 7-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(11, 'TP20260125-8', 'Test Product 8', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '15.30', 0.00, 25.50, 25.50, 0.00, 0.00, 0, 0.00, 'Test Product 8.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 8-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(12, 'TP20260125-9', 'Test Product 9', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '13.08', 0.00, 21.80, 21.80, 0.00, 0.00, 0, 0.00, 'Test Product 9.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 9-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(13, 'TP20260125-10', 'Test Product 102', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '37.20', 0.00, 62.00, 0.00, 0.00, 0.00, 0, 0.00, 'Test Product 10.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 10-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 1),
(14, 'TP20260125-11', 'Test Product 11', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '34.20', 0.00, 57.00, 57.00, 0.00, 0.00, 0, 0.00, 'Test Product 11.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 11-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(15, 'TP20260125-12', 'Test Product 12', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '47.40', 0.00, 79.00, 79.00, 0.00, 0.00, 0, 0.00, 'Test Product 12.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 12-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(16, 'TP20260125-13', 'Test Product 13', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '21.78', 0.00, 36.30, 36.30, 0.00, 0.00, 0, 0.00, 'Test Product 13.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 13-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(17, 'TP20260125-14', 'Test Product 14', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '50.46', 0.00, 84.10, 84.10, 0.00, 0.00, 0, 0.00, 'Test Product 14.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 14-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(18, 'TP20260125-15', 'Test Product 15', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '9.18', 0.00, 15.30, 15.30, 0.00, 0.00, 0, 0.00, 'Test Product 15.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 15-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(19, 'TP20260125-16', 'Test Product 16', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '14.04', 0.00, 23.40, 23.40, 0.00, 0.00, 0, 0.00, 'Test Product 16.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 16-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(20, 'TP20260125-17', 'Test Product 17', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '25.68', 0.00, 42.80, 42.80, 0.00, 0.00, 0, 0.00, 'Test Product 17.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 17-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(21, 'TP20260125-18', 'Test Product 18', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '37.08', 0.00, 61.80, 61.80, 0.00, 0.00, 0, 0.00, 'Test Product 18.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 18-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(22, 'TP20260125-19', 'Test Product 19', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '58.80', 0.00, 98.00, 98.00, 0.00, 0.00, 0, 0.00, 'Test Product 19.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 19-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(23, 'TP20260125-20', 'Test Product 20', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '8.64', 0.00, 14.40, 14.40, 0.00, 0.00, 0, 0.00, 'Test Product 20.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 20-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(24, 'TP20260125-21', 'Test Product 21', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '58.56', 0.00, 97.60, 97.60, 0.00, 0.00, 0, 0.00, 'Test Product 21.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 21-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(25, 'TP20260125-22', 'Test Product 22', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '42.72', 0.00, 71.20, 71.20, 0.00, 0.00, 0, 0.00, 'Test Product 22.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 22-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(26, 'TP20260125-23', 'Test Product 23', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '6.00', 0.00, 10.00, 10.00, 0.00, 0.00, 0, 0.00, 'Test Product 23.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 23-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(27, 'TP20260125-24', 'Test Product 24', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '25.56', 0.00, 42.60, 42.60, 0.00, 0.00, 0, 0.00, 'Test Product 24.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 24-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(28, 'TP20260125-25', 'Test Product 25', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '11.16', 0.00, 18.60, 18.60, 0.00, 0.00, 0, 0.00, 'Test Product 25.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 25-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(29, 'TP20260125-26', 'Test Product 26', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '54.06', 0.00, 90.10, 90.10, 0.00, 0.00, 0, 0.00, 'Test Product 26.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 26-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(30, 'TP20260125-27', 'Test Product 27', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '57.36', 0.00, 95.60, 95.60, 0.00, 0.00, 0, 0.00, 'Test Product 27.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 27-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(31, 'TP20260125-28', 'Test Product 28', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '23.52', 0.00, 39.20, 39.20, 0.00, 0.00, 0, 0.00, 'Test Product 28.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 28-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(32, 'TP20260125-29', 'Test Product 29', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '22.98', 0.00, 38.30, 38.30, 0.00, 0.00, 0, 0.00, 'Test Product 29.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 29-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(33, 'TP20260125-30', 'Test Product 30', 1, 1, 1, 'Test product for pagination', 1, NULL, NULL, '42.84', 0.00, 71.40, 71.40, 0.00, 0.00, 0, 0.00, 'Test Product 30.png', 'images/product_img/test/', 0.00, 'Y', '1', NULL, 0, 'N', 'N', 1, 0, 'enable', 'Normal', 0, 'Test Product 30-', 0.50, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0),
(34, 'IG034', 'Test Product 1023', 2, 13, 7, '', 2, NULL, NULL, '23.00', 0.00, 23.00, 0.00, 0.00, 0.00, 0, 0.00, '', NULL, 0.00, 'Y', NULL, NULL, 0, 'Y', 'N', 1, 0, 'enable', 'Normal', 0, 'Test-Product-1023-34', 0.00, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, '', NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 1),
(35, 'IG035', 'Tsdadas', 1, 1, 1, '', 1, NULL, NULL, '0.00', 0.00, 2.00, NULL, 0.00, 0.00, 0, 0.00, '', NULL, 0.00, 'Y', '', NULL, 0, 'Y', 'N', 1, 0, 'enable', 'Normal', 0, 'Tsdadas-35', 0.00, 5, NULL, NULL, NULL, 'No', NULL, 0, NULL, 'yes', 0, '0.00', '0.00', NULL, NULL, NULL, '', NULL, NULL, NULL, 1, 1, 1, 1, 1, 1, 1, 'Gram', 'Bag', 0);

-- --------------------------------------------------------

--
-- Table structure for table `item_specification`
--

CREATE TABLE `item_specification` (
  `Id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `key` varchar(50) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `item_specification`
--

INSERT INTO `item_specification` (`Id`, `product_id`, `key`, `value`) VALUES
(6, 1, 'Flour', 'Gluten free');

-- --------------------------------------------------------

--
-- Table structure for table `item_uom`
--

CREATE TABLE `item_uom` (
  `uom_id` int(10) NOT NULL,
  `uom_name` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `item_uom`
--

INSERT INTO `item_uom` (`uom_id`, `uom_name`) VALUES
(1, 'item'),
(2, 'kg');

-- --------------------------------------------------------

--
-- Table structure for table `item_warranty`
--

CREATE TABLE `item_warranty` (
  `warranty_id` int(10) NOT NULL,
  `warranty` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `item_warranty`
--

INSERT INTO `item_warranty` (`warranty_id`, `warranty`) VALUES
(1, 'No Warranty'),
(2, 'Authorized Agent Warranty'),
(3, 'Seller Warranty');

-- --------------------------------------------------------

--
-- Table structure for table `location_master`
--

CREATE TABLE `location_master` (
  `id` int(10) NOT NULL,
  `location_code` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `phone_no` int(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `location_master`
--

INSERT INTO `location_master` (`id`, `location_code`, `name`, `address`, `phone_no`, `email`, `logo`) VALUES
(1, 'Store0001', 'Main store', '375/12 , 8th lane, Rathnarama Road Hokandara North', 1, '', ''),
(5, 'Store0002-01', 'Malabe store kitchen', '375/12 athnarama oad okandara orth', 1, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `over_head_master`
--

CREATE TABLE `over_head_master` (
  `over_head_id` int(10) NOT NULL,
  `over_head_name` text DEFAULT NULL,
  `over_head_discribe` text DEFAULT NULL,
  `over_head_charge` double(20,0) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `payments_in_delivery`
--

CREATE TABLE `payments_in_delivery` (
  `Id` int(10) NOT NULL,
  `deliveryId` int(10) NOT NULL,
  `paymentId` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `payments_in_delivery`
--

INSERT INTO `payments_in_delivery` (`Id`, `deliveryId`, `paymentId`) VALUES
(1, 1, 6),
(2, 1, 7),
(3, 1, 8),
(4, 1, 9),
(6, 2, 7),
(7, 2, 8),
(8, 2, 9),
(11, 3, 7),
(12, 3, 8),
(13, 3, 9);

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `id` int(10) NOT NULL,
  `type` varchar(50) NOT NULL,
  `img` text NOT NULL,
  `status` enum('N','Y') NOT NULL DEFAULT 'N',
  `website_status` enum('N','Y') NOT NULL,
  `DeliveryTypeId` int(10) DEFAULT NULL,
  `orderProcess` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `payment_method`
--

INSERT INTO `payment_method` (`id`, `type`, `img`, `status`, `website_status`, `DeliveryTypeId`, `orderProcess`) VALUES
(1, 'Cash Payment', '', 'N', 'Y', NULL, 1),
(2, 'Cheque Payment', '', 'Y', 'N', NULL, 0),
(3, 'Credit Card', '', 'Y', 'N', NULL, 0),
(4, 'Pending Order', '', 'N', 'N', NULL, 0),
(5, 'Credit', '', 'Y', 'N', NULL, 0),
(6, ' Cash on Delivery', 'images/payment/cash-on-delivery.png', 'N', 'N', NULL, 1),
(7, ' Bank Transfer', 'images/payment/bank-transfer.png', 'N', 'Y', NULL, 1),
(8, ' Credit Cart / Debit Cart', 'images/payment/card334.png', 'Y', 'Y', NULL, 0),
(9, 'Mobile Wallet (Ez Cash / M Cash)', 'images/payment/ezcash.png', 'N', 'N', NULL, 0),
(10, 'American Express', 'image/__114.png', 'Y', 'N', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment_terms`
--

CREATE TABLE `payment_terms` (
  `payment_terms_id` int(10) NOT NULL,
  `payment_terms_name` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `payment_terms`
--

INSERT INTO `payment_terms` (`payment_terms_id`, `payment_terms_name`) VALUES
(1, 'Net 30'),
(2, 'Net 60'),
(3, 'Cash on Delivery'),
(4, 'Due on Receipt');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_key` varchar(100) NOT NULL,
  `permission_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `permission_name`, `description`) VALUES
(1, 'dashboard.view', 'View Dashboard', 'Access dashboard'),
(2, 'purchase.supplier.create', 'Create Supplier', 'Add suppliers'),
(3, 'purchase.supplier.view', 'View Supplier', 'Manage suppliers'),
(4, 'purchase.purchase.create', 'Create Purchase Note', 'Create purchase notes'),
(5, 'purchase.purchase.view', 'View Purchase Notes', 'List purchase notes'),
(6, 'purchase.purchase.add', 'Add Purchase', 'Add purchase entries'),
(7, 'purchase.purchase.history', 'Purchase History', 'View purchase history'),
(8, 'stock.transfer.create', 'Create Stock Transfer', 'Create stock transfers'),
(9, 'stock.transfer.view', 'View Stock Transfers', 'List stock transfers'),
(10, 'stock.issue.create', 'Create Stock Issue', 'Create stock issues'),
(11, 'stock.issue.view', 'View Stock Issues', 'List stock issues'),
(12, 'orders.create', 'Create Sales', 'Add new sales'),
(13, 'orders.view', 'View Orders', 'Manage orders'),
(14, 'product.create', 'Create Product', 'Add products'),
(15, 'product.view', 'View Products', 'List products'),
(16, 'product.price_map', 'Product Price Mapping', 'Price type mapping'),
(17, 'product.standing_orders', 'Standing Orders', 'Standing order management'),
(18, 'item_master.group.create', 'Create Group', 'Add product groups'),
(19, 'item_master.type.create', 'Create Type', 'Add product types'),
(20, 'item_master.category.create', 'Create Category', 'Add categories'),
(21, 'item_master.price_types', 'Price Types', 'Manage price types'),
(22, 'warehouse.create', 'Create Warehouse', 'Add warehouses'),
(23, 'warehouse.view', 'View Warehouses', 'Manage warehouses'),
(24, 'customer.create', 'Create Customer', 'Add customers'),
(25, 'customer.view', 'View Customers', 'Manage customers'),
(26, 'customer.price_map', 'Customer Price Mapping', 'Price type mapping'),
(28, 'crm.view', 'View CRM', 'Access CRM menu and dashboard'),
(29, 'crm.person.create', 'Create Person Master', 'Add CRM contact persons'),
(30, 'crm.person.view', 'View Person Master', 'Manage CRM contact persons'),
(31, 'crm.company.create', 'Create Company Master', 'Add CRM companies'),
(32, 'crm.company.view', 'View Company Master', 'Manage CRM companies');

-- --------------------------------------------------------

--
-- Table structure for table `price_type`
--

CREATE TABLE `price_type` (
  `id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_type`
--

INSERT INTO `price_type` (`id`, `description`) VALUES
(1, 'Retail'),
(2, 'Wholesale'),
(3, 'Trade');

-- --------------------------------------------------------

--
-- Table structure for table `price_type_customer_mapping`
--

CREATE TABLE `price_type_customer_mapping` (
  `id` int(10) UNSIGNED NOT NULL,
  `price_type_id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `price_type_customer_mapping`
--

INSERT INTO `price_type_customer_mapping` (`id`, `price_type_id`, `customer_id`, `created_at`) VALUES
(1, 1, 289, '2025-11-16 19:00:36');

-- --------------------------------------------------------

--
-- Table structure for table `productimages`
--

CREATE TABLE `productimages` (
  `Id` int(10) NOT NULL,
  `itemId` int(10) NOT NULL,
  `imagePath` text NOT NULL,
  `image` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `productimages`
--

INSERT INTO `productimages` (`Id`, `itemId`, `imagePath`, `image`) VALUES
(8, 2155, 'images/product_img/2020/02/', 'packageMainImg_3b57b7ea0cb49907a301b0585bcb7eb8.png'),
(9, 2131, 'images/product_img/2020/02/', 'packageMainImg_7ad5bb2c36d2239c8c26951d022318c8.png'),
(10, 2131, 'images/product_img/2020/02/', 'packageMainImg_5c499bab20b5373540e59761c3b6b6d6.png'),
(11, 2131, 'images/product_img/2020/02/', 'packageMainImg_d309cd6396e744600c943fada20f686a.png'),
(14, 2133, 'images/product_img/2020/02/', 'packageMainImg_ca3de2a395b78721ff371d8bbf00a461.png'),
(15, 2133, 'images/product_img/2020/02/', 'packageMainImg_0183e4f6ecf3efd66438a27cb4ec2d68.png'),
(16, 2132, 'images/product_img/2020/02/', 'packageMainImg_28862e85283cab671bc2f7cf9249ccf8.png'),
(17, 2134, 'images/product_img/2020/02/', 'packageMainImg_092bbb48d4ef553460d46938a96f750f.png'),
(18, 2135, 'images/product_img/2020/02/', 'packageMainImg_25a90534d71b90ce5f3ad25cb8da224f.png'),
(19, 2136, 'images/product_img/2020/02/', 'packageMainImg_df5008662615244094efae2e3511a455.png'),
(20, 2137, 'images/product_img/2020/02/', 'packageMainImg_61c08ef8d86ce5b023529f35146fd58e.png'),
(21, 2138, 'images/product_img/2020/02/', 'packageMainImg_70d31b87bd021441e5e6bf23eb84a306.png'),
(22, 2139, 'images/product_img/2020/02/', 'packageMainImg_fc6de09e6ca5ea83e22be3e246b09421.png'),
(23, 2141, 'images/product_img/2020/02/', 'packageMainImg_02379d076c7ea5fa70d163507a628079.png'),
(24, 2142, 'images/product_img/2020/02/', 'packageMainImg_985b5a2999b6b2180004aad757ec1ce7.png'),
(25, 2143, 'images/product_img/2020/02/', 'packageMainImg_3821b53f947061855966b35b98d23a1c.png'),
(26, 2144, 'images/product_img/2020/02/', 'packageMainImg_4acab8fc438e8b985183cb5607036387.png'),
(27, 2145, 'images/product_img/2020/02/', 'packageMainImg_c0dc0dd377c68f4e57ad6d0b0ecf79d5.png'),
(28, 2147, 'images/product_img/2020/02/', 'packageMainImg_1475b0b5094e6f4f7dfc284649259195.png'),
(29, 2148, 'images/product_img/2020/02/', 'packageMainImg_3a115dda5b7c4ef65d4b8811b996d607.png'),
(30, 2149, 'images/product_img/2020/02/', 'packageMainImg_25253f88af72196040d1e2819d3b4d90.png'),
(31, 2150, 'images/product_img/2020/02/', 'packageMainImg_a85b76bfd19b55a476c65b51c84e9c98.png'),
(32, 2151, 'images/product_img/2020/02/', 'packageMainImg_4b26dc4663ccf960c8538d595d0a1d3a.png'),
(33, 2152, 'images/product_img/2020/02/', 'packageMainImg_a16c79a7163a854541bda1816b63de8d.png'),
(34, 2153, 'images/product_img/2020/02/', 'packageMainImg_0cfdb9ef8c59c798e1e2ec98a1bcb54b.png'),
(35, 2154, 'images/product_img/2020/02/', 'packageMainImg_9137f1e4042083d0140a6d548d8c5891.png'),
(36, 2156, 'images/product_img/2020/02/', 'packageMainImg_15bcdc507219f867af64801cdb9aa0fe.png'),
(37, 2157, 'images/product_img/2020/02/', 'packageMainImg_9d521299d659fc724d1b8af2bdb417c8.png'),
(38, 2158, 'images/product_img/2020/02/', 'packageMainImg_caa15a5eef7bc7edc9feee37016b65ff.png'),
(41, 2159, 'images/product_img/2020/02/', 'packageMainImg_de2fcb6997b692e0337721742418a95c.png'),
(42, 2159, 'images/product_img/2020/02/', 'packageMainImg_c862bd39f55a8e086ce46ed5c15c415c.png'),
(43, 2159, 'images/product_img/2020/02/', 'packageMainImg_2d01216e288ff2a1a0fd90c4a4b6bd0c.png'),
(44, 2159, 'images/product_img/2020/02/', 'packageMainImg_70304089b4f2cb8673c1275cf5d90d9a.png'),
(45, 2160, 'images/product_img/2020/02/', 'packageMainImg_7c03b7a5fdf575f1734a19f9ce357cf7.png'),
(46, 2160, 'images/product_img/2020/02/', 'packageMainImg_86994a2739ef1d58267d9b583acd18e1.png'),
(47, 2160, 'images/product_img/2020/02/', 'packageMainImg_e272c73404caa457cc2556b335452f6d.png'),
(48, 2160, 'images/product_img/2020/02/', 'packageMainImg_0a634763cc1a09532dd8cbf0e74e3d48.png'),
(49, 2161, 'images/product_img/2020/02/', 'packageMainImg_470ada290aba8299c777de4fc080f6d3.png'),
(50, 2161, 'images/product_img/2020/02/', 'packageMainImg_38b0725407c32db8da89c0b139cd627e.png'),
(51, 2161, 'images/product_img/2020/02/', 'packageMainImg_ee3552c274c9aff84ffe4465516491da.png'),
(52, 2161, 'images/product_img/2020/02/', 'packageMainImg_ce3a613942b71dba2e163c9aba1351f2.png'),
(53, 2162, 'images/product_img/2020/02/', 'packageMainImg_4629b5aca2dc671c58285e3ad62ff6e5.png'),
(54, 2163, 'images/product_img/2020/02/', 'packageMainImg_08cb5d19a3f41a88972760e1bb94bf93.png'),
(55, 2164, 'images/product_img/2020/02/', 'packageMainImg_063cba860d94f5faae3ec7a2c4f0dbfd.png'),
(56, 2165, 'images/product_img/2020/02/', 'packageMainImg_09a5fbb0b8155753fce619be1bd50c4e.png'),
(57, 2166, 'images/product_img/2020/02/', 'packageMainImg_cc78ceffa77282bdf49c034d14917ed4.png'),
(58, 2167, 'images/product_img/2020/02/', 'packageMainImg_819c6baf536cc4b994ec6dcf9f415994.png'),
(59, 2168, 'images/product_img/2020/02/', 'packageMainImg_a8430b206d9e7446f0d652f93d2974be.png'),
(60, 2169, 'images/product_img/2020/02/', 'packageMainImg_0cb8b62a6aaf2c2e0f53a54483c14423.png'),
(61, 2170, 'images/product_img/2020/02/', 'packageMainImg_fc115e30db7b1d325947f40e1cb04dac.png'),
(62, 2171, 'images/product_img/2020/02/', 'packageMainImg_2dcd82e1f449765dfd555d7441d52096.png'),
(64, 2172, 'images/product_img/2020/02/', 'packageMainImg_52162e95658cb15d1a3f73151ceb4a3f.png'),
(65, 2173, 'images/product_img/2020/02/', 'packageMainImg_4b78fb192fa0674f553eaf144f4de21c.png'),
(66, 2174, 'images/product_img/2020/02/', 'packageMainImg_1f86b9ee31ba4f404c6c0e6fc1bf2fd2.png'),
(67, 2175, 'images/product_img/2020/02/', 'packageMainImg_3ab9b6125b69be3b7c0b02fafc19dc34.png'),
(68, 2176, 'images/product_img/2020/02/', 'packageMainImg_592e58538b6886575fa0c37e5035e629.png'),
(69, 2177, 'images/product_img/2020/02/', 'packageMainImg_88f7e6d4c57beec07dd0fe02c25c4e34.png'),
(70, 2178, 'images/product_img/2020/02/', 'packageMainImg_771f64a6271a6c41312ca266c67c898f.png'),
(71, 2179, 'images/product_img/2020/02/', 'packageMainImg_59e69da3ba5a27e845bc26a791fe03e2.png'),
(72, 2180, 'images/product_img/2020/02/', 'packageMainImg_9afad4c3fe50b3ea745831dcabdbccd5.png'),
(73, 2181, 'images/product_img/2020/02/', 'packageMainImg_87dc5f033c63120366400e1b4cdbefcd.png'),
(74, 2182, 'images/product_img/2020/02/', 'packageMainImg_8f14cbaa926df2328b2b345ad3933786.png'),
(75, 2183, 'images/product_img/2020/02/', 'packageMainImg_719cd36fe3741c60b7bdc234b8867fe9.png'),
(76, 2184, 'images/product_img/2020/02/', 'packageMainImg_398c068f768d3623e091d8c4c9b2a54a.png'),
(77, 2185, 'images/product_img/2020/02/', 'packageMainImg_4b81c5dc1d314efeb3c5c8cf6e6873a8.png'),
(78, 2186, 'images/product_img/2020/02/', 'packageMainImg_51f4efbfb3e18f4ea053c4d3d282c4e2.png'),
(79, 2187, 'images/product_img/2020/02/', 'packageMainImg_223cbeea72ad6d8bdc7413036415830b.png'),
(80, 2188, 'images/product_img/2020/02/', 'packageMainImg_dffea459b271e2b7336d252e0f9bd57f.png'),
(81, 2189, 'images/product_img/2020/02/', 'packageMainImg_bed0d0859405ac05d00731bb68bdf412.png'),
(82, 2189, 'images/product_img/2020/02/', 'packageMainImg_6d19f293a17b82e5f131fe5ce1e4f60c.png'),
(83, 2190, 'images/product_img/2020/02/', 'packageMainImg_232615331bf1c117f8bb6204dc42653a.png'),
(84, 2191, 'images/product_img/2020/02/', 'packageMainImg_b92e26a764c530314cd8e5cb41f288b0.png'),
(85, 2192, 'images/product_img/2020/02/', 'packageMainImg_e2fcdb545cb7b9310e77274405a70253.png'),
(86, 2193, 'images/product_img/2020/02/', 'packageMainImg_af993071d9ee195399bed7cdd9967f50.png'),
(87, 2194, 'images/product_img/2020/02/', 'packageMainImg_b3906d9bec5680dd4725b0a77909ffab.png'),
(88, 2195, 'images/product_img/2020/02/', 'packageMainImg_bca22c57e830db4c16d3953508004eb5.png'),
(89, 2196, 'images/product_img/2020/02/', 'packageMainImg_580170bdeb9c1acc1b8967e4bb524692.png'),
(90, 2197, 'images/product_img/2020/02/', 'packageMainImg_893bdd13afb4caea39a9c3ae07bbc4fc.png'),
(91, 2200, 'images/product_img/2020/02/', 'packageMainImg_d428eed92bdeb06f60b7af3d7d83b7b8.png'),
(92, 2201, 'images/product_img/2020/02/', 'packageMainImg_8fea41b4e260c469196d4cc4ddf1a702.png'),
(93, 2202, 'images/product_img/2020/02/', 'packageMainImg_1a4758769d34c5e0c957c48ea879af23.png'),
(94, 2203, 'images/product_img/2020/02/', 'packageMainImg_f435c47e87c29d5bc29e73ff23929d24.png'),
(95, 2204, 'images/product_img/2020/02/', 'packageMainImg_a194eaca9e0439f87f4738b04eacac74.png'),
(96, 2205, 'images/product_img/2020/02/', 'packageMainImg_dffe2f9dbb551efe6e610f4a77c508e9.png'),
(97, 2206, 'images/product_img/2020/02/', 'packageMainImg_787b642b0c477d45658b37bd4c8b0bc5.png'),
(98, 2207, 'images/product_img/2020/02/', 'packageMainImg_db8bdc788823cc781f69ec45a92047cb.png'),
(99, 2208, 'images/product_img/2020/02/', 'packageMainImg_f5f803c6069f6a22cd308cdef4df14a8.png'),
(100, 2209, 'images/product_img/2020/02/', 'packageMainImg_1a5c761b6d0717285d1da5c527cd9726.png'),
(101, 2210, 'images/product_img/2020/02/', 'packageMainImg_f235a88ab7d252c34174216e0eb78788.png'),
(102, 2211, 'images/product_img/2020/02/', 'packageMainImg_ee926f8ffd07ee12a332d2b3ac0deac3.png'),
(103, 2212, 'images/product_img/2020/02/', 'packageMainImg_ba3dec7d484d6fefd36b1f9a7a3f7df4.png'),
(104, 2213, 'images/product_img/2020/02/', 'packageMainImg_aac11bf56c351f216eeb793afc24d71a.png'),
(105, 2214, 'images/product_img/2020/02/', 'packageMainImg_62f34ecbdb0b31be33629e809dcf1326.png'),
(106, 2215, 'images/product_img/2020/02/', 'packageMainImg_68e830faadac052f08c20982d875f029.png'),
(107, 2216, 'images/product_img/2020/02/', 'packageMainImg_a2ed5bb8de0f57cbfd876b31da8481d5.png'),
(108, 2217, 'images/product_img/2020/02/', 'packageMainImg_c151e57162f490550b743e688a9abe1e.png'),
(109, 2218, 'images/product_img/2020/02/', 'packageMainImg_157d84aa8cf4e0814f0a7f7333f1e0a2.png'),
(110, 2219, 'images/product_img/2020/02/', 'packageMainImg_57556defa9e50f6c7843676e6d3048ea.png'),
(111, 2220, 'images/product_img/2020/02/', 'packageMainImg_f862d9ac95c0f0b3d31c4934f36023c8.png'),
(112, 2221, 'images/product_img/2020/02/', 'packageMainImg_5a4b2df3e2c5e51353edce5eaef18479.png'),
(113, 2222, 'images/product_img/2020/02/', 'packageMainImg_99d3505d54853bb380dc49ac827704f7.png'),
(114, 2223, 'images/product_img/2020/02/', 'packageMainImg_2329b5a85a9bece2c946e1834aa5d7f9.png'),
(115, 2224, 'images/product_img/2020/02/', 'packageMainImg_31093a319763ba6e1987f1fdeb54959e.png'),
(116, 2225, 'images/product_img/2020/02/', 'packageMainImg_8edadf4bbd8528ab71ed3ecbd02e553d.png'),
(117, 2226, 'images/product_img/2020/02/', 'packageMainImg_f5ff4a13039552115a898004bd1cfe20.png'),
(118, 2227, 'images/product_img/2020/02/', 'packageMainImg_45028792b0daa25c5b379c435b05bbd3.png'),
(119, 2228, 'images/product_img/2020/02/', 'packageMainImg_879baeb253991c6dc31b563f586b0004.png'),
(120, 2229, 'images/product_img/2020/02/', 'packageMainImg_eca17516aa293aeab7bac43b17974afb.png'),
(121, 2230, 'images/product_img/2020/02/', 'packageMainImg_05566f41445244da5122d018e2efdffe.png'),
(122, 2231, 'images/product_img/2020/02/', 'packageMainImg_6f6adc9bf95f108c01e0d2a43a61e1e9.png'),
(123, 2232, 'images/product_img/2020/02/', 'packageMainImg_0af70c26da13b12ec39940d5099bd3e9.png'),
(124, 2233, 'images/product_img/2020/02/', 'packageMainImg_642b7e9b5044703b3ef14c11b58677cd.png'),
(125, 2234, 'images/product_img/2020/02/', 'packageMainImg_42c35faf9a55f884263407396aef25d0.png'),
(126, 2235, 'images/product_img/2020/02/', 'packageMainImg_5fb03c2bc8b414334f4d863d7b5d51a5.png'),
(127, 2236, 'images/product_img/2020/02/', 'packageMainImg_fdf301019dd996435263a651bd60d739.png'),
(128, 2237, 'images/product_img/2020/02/', 'packageMainImg_f2486162cd3883cd380752982b56e5e6.png'),
(129, 2238, 'images/product_img/2020/02/', 'packageMainImg_84d21d361fe72dbdbacab9ed99a74b1a.png'),
(130, 2239, 'images/product_img/2020/02/', 'packageMainImg_c5407e1d377f665f5a938749268fcca3.png'),
(131, 2240, 'images/product_img/2020/02/', 'packageMainImg_4f6150324417a9ecd479a6a7ef20158d.png'),
(132, 2241, 'images/product_img/2020/02/', 'packageMainImg_c1043d7b9449ec4379bd74c7663f7b7f.png'),
(133, 2242, 'images/product_img/2020/02/', 'packageMainImg_27353dbfaeabd15a530533f2b36b9620.png'),
(134, 2243, 'images/product_img/2020/02/', 'packageMainImg_13e050bf582cbdd05af4ec7247bd1bf9.png'),
(135, 2244, 'images/product_img/2020/02/', 'packageMainImg_cc428ad8421139f486a7476e31eae5cc.png'),
(136, 2245, 'images/product_img/2020/02/', 'packageMainImg_012c900eed822ba5687788d91aa8c297.png'),
(137, 2246, 'images/product_img/2020/02/', 'packageMainImg_74ea5f949ed3a8816127942615ed3716.png'),
(138, 2247, 'images/product_img/2020/02/', 'packageMainImg_031fcd253ac5e5ed05a1984d8af8acfb.png'),
(139, 2250, 'images/product_img/2020/02/', 'packageMainImg_84ffb024ae85f47ce3c049506fc3f5f6.png'),
(140, 2248, 'images/product_img/2020/02/', 'packageMainImg_fa8edf495b7302b10ca1aeb0e4284b15.png'),
(141, 2249, 'images/product_img/2020/02/', 'packageMainImg_7ed9d0d94842b5a8060bf220c5d87cda.png'),
(142, 2251, 'images/product_img/2020/02/', 'packageMainImg_601fca976da7285ee707df0b8f6dfdf0.png'),
(143, 2252, 'images/product_img/2020/02/', 'packageMainImg_26090083552cb1f60ac66fcb1e7c14c3.png'),
(144, 2253, 'images/product_img/2020/02/', 'packageMainImg_ee885a1e841c9eb5a8e846ee157c411d.png'),
(145, 2254, 'images/product_img/2020/02/', 'packageMainImg_c9fe0fcbe156447a949fd4b0e1a925f1.png'),
(146, 2255, 'images/product_img/2020/02/', 'packageMainImg_99761a2e7fc7421f32494e0131214c7f.png'),
(147, 2256, 'images/product_img/2020/02/', 'packageMainImg_3d591fb517a0d6c00cc6f9d02ce8e5b0.png'),
(148, 2257, 'images/product_img/2020/02/', 'packageMainImg_1aab3ecb451c42e3c3c94ea13da56031.png'),
(149, 2258, 'images/product_img/2020/02/', 'packageMainImg_904a3a35c36fa22150ef4b97e8f6cb1b.png'),
(150, 2259, 'images/product_img/2020/02/', 'packageMainImg_6835795797a9990627be38cde838532f.png'),
(151, 2260, 'images/product_img/2020/02/', 'packageMainImg_abb2abb5c496c929a8f59c13cc0cf7a9.png'),
(152, 2261, 'images/product_img/2020/02/', 'packageMainImg_7fd60e83598ba7103b06e21feac9f435.png'),
(153, 2262, 'images/product_img/2020/02/', 'packageMainImg_dc116cd8f72b02d098580fe198983052.png'),
(154, 2263, 'images/product_img/2020/02/', 'packageMainImg_6e346056ecfeaed073d2d117336dc113.png'),
(155, 2264, 'images/product_img/2020/02/', 'packageMainImg_4660bb3fb5a6609b1015f38512a34404.png'),
(156, 2265, 'images/product_img/2020/02/', 'packageMainImg_d34f2377cdf13dcccf0ad67e0c32b58b.png'),
(157, 2266, 'images/product_img/2020/02/', 'packageMainImg_a565aa7604ba7bacb2abbb5cfbde8c04.png'),
(158, 2267, 'images/product_img/2020/02/', 'packageMainImg_1a96f8b81c774a1c836356eb4385dce7.png'),
(159, 2268, 'images/product_img/2020/02/', 'packageMainImg_a69db7601894ad836dd6b9d77c716e92.png'),
(160, 2269, 'images/product_img/2020/02/', 'packageMainImg_db39d217965d5b67f585d45a3dce0aaa.png'),
(161, 2270, 'images/product_img/2020/02/', 'packageMainImg_49ed63fe399a7f101de0a4e7ed90941a.png'),
(162, 2272, 'images/product_img/2020/02/', 'packageMainImg_a7dff860109d7892b023bab07e44aed3.png'),
(163, 2271, 'images/product_img/2020/02/', 'packageMainImg_d3af1620cb986e1e64593a276fc06bca.png'),
(164, 2273, 'images/product_img/2020/02/', 'packageMainImg_5108c5a920d9c32dc15a30f18f0a71a2.png'),
(165, 2274, 'images/product_img/2020/02/', 'packageMainImg_f738a119b733b6051b120881c14e5ee3.png'),
(166, 2275, 'images/product_img/2020/02/', 'packageMainImg_250b164d84ea39a488422da8500786e6.png'),
(167, 2276, 'images/product_img/2020/02/', 'packageMainImg_fd62c1ed20ea6c955cae4a4e501a7d89.png'),
(168, 2277, 'images/product_img/2020/02/', 'packageMainImg_fca54659ec670d9911f5583ad8a64283.png'),
(169, 2278, 'images/product_img/2020/02/', 'packageMainImg_8b4dba9338761af5867b0cd4144f2332.png'),
(170, 2279, 'images/product_img/2020/02/', 'packageMainImg_f4d94d1631e7422874c01b9f04a9c567.png'),
(171, 2280, 'images/product_img/2020/02/', 'packageMainImg_1dd998e1f9d739d412c67e87d64a0877.png'),
(172, 2281, 'images/product_img/2020/02/', 'packageMainImg_2c78d1679e9502e23a52d06c436dac67.png'),
(173, 2282, 'images/product_img/2020/02/', 'packageMainImg_04b442a1852c959c376fa17259b82976.png'),
(174, 2283, 'images/product_img/2020/02/', 'packageMainImg_a117cce30217f7a309b3559f90caf548.png'),
(175, 2284, 'images/product_img/2020/02/', 'packageMainImg_cf7c5f86c8b2aecadea7f8cf85fa64ac.png'),
(176, 2285, 'images/product_img/2020/02/', 'packageMainImg_59cf4bbdf30366f6ac81254d58b47318.png'),
(177, 2286, 'images/product_img/2020/02/', 'packageMainImg_25e2a30f44898b9f3e978b1786dcd85c.png'),
(178, 2287, 'images/product_img/2020/02/', 'packageMainImg_81db22471c741538fe06130fd51b631a.png'),
(179, 2288, 'images/product_img/2020/02/', 'packageMainImg_96a49fa7c0ac58ef564a013456064cc8.png'),
(180, 2289, 'images/product_img/2020/02/', 'packageMainImg_897e9aa8caf19e3469d55161b72407af.png'),
(181, 2290, 'images/product_img/2020/02/', 'packageMainImg_95abe2bfc9c05a63964787ded4e424b8.png'),
(182, 2291, 'images/product_img/2020/02/', 'packageMainImg_ab511eda0c9d80d9f540e6fa8d04c409.png'),
(183, 2292, 'images/product_img/2020/02/', 'packageMainImg_19928a81df269292d4fde98228f80025.png'),
(184, 2293, 'images/product_img/2020/02/', 'packageMainImg_873049a191e3d068684d0a054648e654.png'),
(185, 2295, 'images/product_img/2020/02/', 'packageMainImg_224cbccb01400f9f6cee69290eaf8f0f.png'),
(186, 2296, 'images/product_img/2020/02/', 'packageMainImg_b2095993facf99bba8eea06241b39152.png'),
(187, 2297, 'images/product_img/2020/02/', 'packageMainImg_8cad5ce2369843d976db049244ee7f18.png'),
(188, 2298, 'images/product_img/2020/02/', 'packageMainImg_8aa2b7f14698ec502314eed97237f260.png'),
(189, 2299, 'images/product_img/2020/02/', 'packageMainImg_8bf9f668c7dec640f99f7b49f097ff8d.png'),
(190, 2300, 'images/product_img/2020/02/', 'packageMainImg_e0d3b4e32329d9a7d77df207ab1ecebc.png'),
(191, 2301, 'images/product_img/2020/02/', 'packageMainImg_d969f1354f848be1f5998515c0854117.png'),
(192, 2302, 'images/product_img/2020/02/', 'packageMainImg_9b1a910c1f30f636c1d970e0b88bdc3d.png'),
(193, 2303, 'images/product_img/2020/02/', 'packageMainImg_d8c6e0db5743496f0e53899e25321c26.png'),
(194, 2304, 'images/product_img/2020/02/', 'packageMainImg_0d25bd6148eb83adfe08707b9e809d9d.png'),
(195, 2305, 'images/product_img/2020/02/', 'packageMainImg_cc056b1a6ed9b5eb4a51dd860eaacb87.png'),
(196, 2306, 'images/product_img/2020/02/', 'packageMainImg_d2ac71782272659e7171150d20d59158.png'),
(197, 2307, 'images/product_img/2020/02/', 'packageMainImg_df099083450295b1ead6bc471c031a88.png'),
(198, 2308, 'images/product_img/2020/02/', 'packageMainImg_1e2e2aa5847a82f5cf11d6e39f2d45b7.png'),
(199, 2309, 'images/product_img/2020/02/', 'packageMainImg_0390f9b71d62eb51f6a3f88777bc3f9e.png'),
(200, 2310, 'images/product_img/2020/02/', 'packageMainImg_4929cea47f3826ece740953beafd29f0.png'),
(201, 2312, 'images/product_img/2020/02/', 'packageMainImg_5b95ecc10d1f439cf8be95743c17c90c.png'),
(202, 2294, 'images/product_img/2020/02/', 'packageMainImg_8f25161134e9e47bef7771b4f27e2d23.png'),
(203, 2313, 'images/product_img/2020/02/', 'packageMainImg_3fbf6149e55b6fcb76e41faba8e0eca3.png'),
(204, 2311, 'images/product_img/2020/02/', 'packageMainImg_09d79b4b3965be14577ace6f4e40d148.png'),
(205, 2314, 'images/product_img/2020/02/', 'packageMainImg_61c21e8605c0ceb20d444f657e243021.png'),
(206, 2315, 'images/product_img/2020/02/', 'packageMainImg_d4aec17c30f59704f811d7ae8b54af53.png'),
(207, 2316, 'images/product_img/2020/02/', 'packageMainImg_ce72f48b5e8edf8c921fcc1a3a758a86.png'),
(208, 2317, 'images/product_img/2020/02/', 'packageMainImg_0eda666a2704efc2e5b37dd165607c0f.png'),
(209, 2318, 'images/product_img/2020/02/', 'packageMainImg_a82621f1ac6b42d8532b88cfc77b4fea.png'),
(210, 2319, 'images/product_img/2020/02/', 'packageMainImg_c611db57954159b01bdca6d099953aa7.png'),
(211, 2320, 'images/product_img/2020/02/', 'packageMainImg_6449f44a102fde848669bdd9eb6b76fa.png'),
(212, 2320, 'images/product_img/2020/02/', 'packageMainImg_8d9b9b7f2473c5c3d0f68248a03a3288.png'),
(213, 2320, 'images/product_img/2020/02/', 'packageMainImg_3d93eeedad581af1e4ba71917dff8dbc.png'),
(214, 2320, 'images/product_img/2020/02/', 'packageMainImg_812149a7cee73ca185fa43a693b051f5.png'),
(215, 2321, 'images/product_img/2020/02/', 'packageMainImg_930d3c22e359be6faaa1cb50fdb8c651.png'),
(216, 2321, 'images/product_img/2020/02/', 'packageMainImg_0eb43bcc5a09fdbd6ea92e18b0907190.png'),
(217, 2321, 'images/product_img/2020/02/', 'packageMainImg_0462bb5e80741d2ae9cad95447719a0c.png'),
(218, 2321, 'images/product_img/2020/02/', 'packageMainImg_77bd72db97214060b9903363c3e3d993.png'),
(219, 2322, 'images/product_img/2020/02/', 'packageMainImg_002aa411eaf2f6a11ccce7ef17140295.png'),
(220, 2322, 'images/product_img/2020/02/', 'packageMainImg_f9bb6fae45b409a023fc23ff39073e15.png'),
(221, 2322, 'images/product_img/2020/02/', 'packageMainImg_83cc505fa9b9abefeeaf48d419d5ff36.png'),
(222, 2322, 'images/product_img/2020/02/', 'packageMainImg_9b8e6ef60e97771bb8e4db8c68d0440f.png'),
(223, 2323, 'images/product_img/2020/02/', 'packageMainImg_d6916cb344c8d1f188477064d1e2f70e.png'),
(224, 2324, 'images/product_img/2020/02/', 'packageMainImg_f6054270e05f2fc2450f75c7462bc030.png'),
(225, 2325, 'images/product_img/2020/02/', 'packageMainImg_7ff9ecd5d9bca1ec7e740a92c6c2b8b3.png'),
(226, 2326, 'images/product_img/2020/02/', 'packageMainImg_a8152a92cc4c25448d5379f6b9fcf3d2.png'),
(227, 2327, 'images/product_img/2020/02/', 'packageMainImg_8aceb0eca164965d241bb86fa846da1b.png'),
(228, 2328, 'images/product_img/2020/02/', 'packageMainImg_772e059a1285d596d4b2687b98fb912b.png'),
(229, 2329, 'images/product_img/2020/02/', 'packageMainImg_ae448625bec8d963f82c2cd54f4ae4a1.png'),
(230, 2330, 'images/product_img/2020/02/', 'packageMainImg_bda433b1ff68932b7029643e86b3fffc.png'),
(231, 2331, 'images/product_img/2020/02/', 'packageMainImg_b9ae3986cb63a3c78bbf2a2b815991e6.png'),
(232, 2332, 'images/product_img/2020/02/', 'packageMainImg_60410877774df72918ef9b5bec65b386.png'),
(233, 2333, 'images/product_img/2020/02/', 'packageMainImg_4ce0aeac6eb161e17c1ca35b4c85d388.png'),
(234, 2334, 'images/product_img/2020/02/', 'packageMainImg_34c05a225682507ea58451e18a67613e.png'),
(235, 2335, 'images/product_img/2020/02/', 'packageMainImg_7afcff7b307413d6ff4185f67b098e90.png'),
(236, 2336, 'images/product_img/2020/02/', 'packageMainImg_1eee856a96742c9d5612709291922a9c.png'),
(237, 2337, 'images/product_img/2020/02/', 'packageMainImg_526b15839aec2f04d733c8669e7881d4.png'),
(238, 2338, 'images/product_img/2020/02/', 'packageMainImg_56be57cc8dd661dfdbb921608cf93ded.png'),
(239, 2339, 'images/product_img/2020/02/', 'packageMainImg_c2cc7d87a797d2d86d1da7e235fbcc1d.png'),
(240, 2340, 'images/product_img/2020/02/', 'packageMainImg_bc5d74dda84f7b851369b3cb0aaf3223.png'),
(241, 2340, 'images/product_img/2020/02/', 'packageMainImg_c6f9d83e9c0e73a15b364aba1aca88c1.png'),
(242, 2341, 'images/product_img/2020/02/', 'packageMainImg_fb56c120d6ae0dff1009152ba45759f4.png'),
(243, 2342, 'images/product_img/2020/02/', 'packageMainImg_8e1093f4384d18cc8a6f84720b1db629.png'),
(244, 2343, 'images/product_img/2020/02/', 'packageMainImg_98439fa1d4bdc47bb4bb5282c7f3775c.png'),
(245, 2345, 'images/product_img/2020/02/', 'packageMainImg_821718d0413f23eeb626ddb895bddb51.png'),
(246, 2346, 'images/product_img/2020/02/', 'packageMainImg_837a425a4d378d4a4a3a3d430b1573ef.png'),
(247, 2347, 'images/product_img/2020/02/', 'packageMainImg_3b94b11710146b04fa12896290377264.png'),
(248, 2348, 'images/product_img/2020/02/', 'packageMainImg_a6f413a75686867ef5010ac90b5ceef9.png'),
(249, 2349, 'images/product_img/2020/02/', 'packageMainImg_9e77638d63038fb9cbeed96ed1a9f4f2.png'),
(250, 2350, 'images/product_img/2020/02/', 'packageMainImg_f4c8e5806f83d9906c26d70b83864164.png'),
(251, 2351, 'images/product_img/2020/02/', 'packageMainImg_bf516d41729f63f335de35bbaa6d1ea5.png'),
(252, 2352, 'images/product_img/2020/02/', 'packageMainImg_48d9ce297bbf855a249af429ada6b607.png'),
(253, 2353, 'images/product_img/2020/02/', 'packageMainImg_e47052c5dc1f7d7fbd7300f2eadcff7c.png'),
(254, 2354, 'images/product_img/2020/02/', 'packageMainImg_ecc88ed4c438574dd9bf50b919ea247d.png'),
(255, 2355, 'images/product_img/2020/02/', 'packageMainImg_07621b413d2144bcc7b4b82dc868986d.png'),
(256, 2356, 'images/product_img/2020/02/', 'packageMainImg_e8ae024348eea7e231841a3c89c2ee2c.png'),
(257, 2357, 'images/product_img/2020/02/', 'packageMainImg_00755a54ea07fa69087afa4ffe9955e7.png'),
(258, 2358, 'images/product_img/2020/02/', 'packageMainImg_3401c9172ed22e06d279c09033dc6a53.png'),
(259, 2359, 'images/product_img/2020/02/', 'packageMainImg_701deaf83b7c5957fd2fe1c4ee60ee9b.png'),
(260, 2360, 'images/product_img/2020/02/', 'packageMainImg_e9351e337f893668ecf11a217be23637.png'),
(261, 2361, 'images/product_img/2020/02/', 'packageMainImg_b72ae06b6d3af92c887396f89edd736a.png'),
(262, 2362, 'images/product_img/2020/02/', 'packageMainImg_430b5ab780f665e7d4ac6d07693995e4.png'),
(263, 2363, 'images/product_img/2020/02/', 'packageMainImg_2006cee074c793f70e756dbee2febd3e.png'),
(264, 2364, 'images/product_img/2020/02/', 'packageMainImg_bdc12c01d18bc7243390e4015c58b623.png'),
(265, 2365, 'images/product_img/2020/02/', 'packageMainImg_428719ccf94a203676e178fe15b4b91b.png'),
(266, 2366, 'images/product_img/2020/02/', 'packageMainImg_5e83c76ec5d89994187432739a574195.png'),
(267, 2367, 'images/product_img/2020/02/', 'packageMainImg_2adfe5f10741c871696e1c0c2a0a1d58.png'),
(268, 2368, 'images/product_img/2020/02/', 'packageMainImg_4d76db7568c1afce8ab47f2e72ef40c2.png'),
(269, 2369, 'images/product_img/2020/02/', 'packageMainImg_c1ceafca9444101f96adceb627c472aa.png'),
(270, 2370, 'images/product_img/2020/02/', 'packageMainImg_b00cdc8926c7600576e6a1f7c0209683.png'),
(273, 2371, 'images/product_img/2020/02/', 'packageMainImg_52fae819101f0a21fce1820608f07cb2.png'),
(274, 2371, 'images/product_img/2020/02/', 'packageMainImg_fcb76e0d3139286be63234eb5e348cc1.png'),
(275, 2372, 'images/product_img/2020/02/', 'packageMainImg_ced939048b045de2afb4e39179b212d7.png'),
(276, 2373, 'images/product_img/2020/02/', 'packageMainImg_ba81d0c64c8af7b34334ef6a644c694a.png'),
(277, 2374, 'images/product_img/2020/02/', 'packageMainImg_6f599e962d0e4c0366483388c144ecc0.png'),
(278, 2375, 'images/product_img/2020/02/', 'packageMainImg_2b78fa34529f0ca93aa69a67152bbc2c.png'),
(279, 2376, 'images/product_img/2020/02/', 'packageMainImg_6fb5b84f5fe4f2d6a64215b25765233a.png'),
(280, 2377, 'images/product_img/2020/02/', 'packageMainImg_1935201d65cd7ae6e3c0c9ebe65d843a.png'),
(281, 2378, 'images/product_img/2020/02/', 'packageMainImg_a3730c053af5cc03c79a0ec559f404c5.png'),
(282, 2379, 'images/product_img/2020/02/', 'packageMainImg_38714f1bfdd41ce60eaeeafdeeb22ae4.png'),
(283, 2380, 'images/product_img/2020/02/', 'packageMainImg_8c8056a53ae4f5ce0dfe0b23533ab68a.png'),
(284, 2381, 'images/product_img/2020/02/', 'packageMainImg_97ad4b97d3e16806de52811b625f8792.png'),
(285, 2382, 'images/product_img/2020/02/', 'packageMainImg_bb62401542d181a489abba8ffce0cd62.png'),
(286, 2383, 'images/product_img/2020/02/', 'packageMainImg_1c6ef63ab07b1279ecd6b3874dc51eb9.png'),
(287, 2384, 'images/product_img/2020/02/', 'packageMainImg_f6f4837f06279f26cf0d9191491c29ef.png'),
(288, 2385, 'images/product_img/2020/02/', 'packageMainImg_5e26675d059aa472b2663d6f0e7a9efb.png'),
(289, 2386, 'images/product_img/2020/02/', 'packageMainImg_5c0b4c16f3aba6a9eef6fa902812758b.png'),
(290, 2387, 'images/product_img/2020/02/', 'packageMainImg_d17c9b8ee4b977fae849666c4ab13a0f.png'),
(291, 2388, 'images/product_img/2020/02/', 'packageMainImg_c6701dd2b897a8127a089a12e36c7569.png'),
(292, 2389, 'images/product_img/2020/02/', 'packageMainImg_c635600fa750a7ef261bd517d3ae4717.png'),
(293, 2390, 'images/product_img/2020/02/', 'packageMainImg_b2c4c97c162b887b9262a3f046042f5c.png'),
(294, 2391, 'images/product_img/2020/02/', 'packageMainImg_fe8fe4dbc59e7d3cf3741fc2d85e3590.png'),
(295, 2392, 'images/product_img/2020/02/', 'packageMainImg_dba22cdc8e05cbe5eb65017e22e2b12f.png'),
(296, 2393, 'images/product_img/2020/02/', 'packageMainImg_8c370b1efd8fbcd16b541507db4efb4d.png'),
(297, 2394, 'images/product_img/2020/02/', 'packageMainImg_fa4418c9e72696a3b922dce4f84ccb56.png'),
(298, 2395, 'images/product_img/2020/02/', 'packageMainImg_dba00016676364d9dd15ba9f485e9780.png'),
(299, 2396, 'images/product_img/2020/02/', 'packageMainImg_17256f049f1e3fede17c7a313f7657f4.png'),
(300, 2397, 'images/product_img/2020/02/', 'packageMainImg_d80f9ef3ea64364765558df7929ba0ab.png'),
(301, 2398, 'images/product_img/2020/02/', 'packageMainImg_adcf9ee470977997370493cf8c747655.png'),
(302, 2399, 'images/product_img/2020/02/', 'packageMainImg_351f3d20c0a0bda35d3e29e0fd194ccf.png'),
(303, 2400, 'images/product_img/2020/02/', 'packageMainImg_c964991bdb743389bcae678aaa3c3f19.png'),
(304, 2401, 'images/product_img/2020/02/', 'packageMainImg_cf8bc751c115d8fcaaaadacf625a4606.png'),
(305, 2402, 'images/product_img/2020/02/', 'packageMainImg_1875db8778a788997bec7f772b3bb22f.png'),
(306, 2403, 'images/product_img/2020/02/', 'packageMainImg_d195af4db442040d9c80a5bded5d42ee.png'),
(307, 2404, 'images/product_img/2020/02/', 'packageMainImg_72722b5c761167be1432c6587db47001.png'),
(308, 2405, 'images/product_img/2020/02/', 'packageMainImg_ef7871dea1b5965a7f9bc21575142596.png'),
(309, 2406, 'images/product_img/2020/02/', 'packageMainImg_8ac3cf639e6716e8fb3c41deb5a2156a.png'),
(310, 2407, 'images/product_img/2020/02/', 'packageMainImg_6c7dbdd98cd70f67f102524761f3b4d2.png'),
(312, 2408, 'images/product_img/2020/02/', 'packageMainImg_b1d7e8e07b7c67933f9d22d3b7fb8950.png'),
(313, 2409, 'images/product_img/2020/02/', 'packageMainImg_5697883587005357a7876de187a8db24.png'),
(314, 2410, 'images/product_img/2020/02/', 'packageMainImg_65cc5aa894fd1733812e093aecc3b67f.png'),
(315, 2411, 'images/product_img/2020/02/', 'packageMainImg_b09c7b1f87dc243b4c2f97edc103cb55.png'),
(316, 2412, 'images/product_img/2020/02/', 'packageMainImg_a97f6e2fedcabc887911dc9b5fd3ccc3.png'),
(317, 2413, 'images/product_img/2020/02/', 'packageMainImg_b6727d28105544d5cded36fb6dd258cb.png'),
(318, 2414, 'images/product_img/2020/02/', 'packageMainImg_852e8ed4d435a0eb4b1f3c4601272bd0.png'),
(319, 2415, 'images/product_img/2020/02/', 'packageMainImg_7f4c6b53a424a4029eeda7634a2e235f.png'),
(320, 2416, 'images/product_img/2020/02/', 'packageMainImg_daf125406287301681c48a43ddb88e0f.png'),
(321, 2417, 'images/product_img/2020/02/', 'packageMainImg_1e95ed3b4b8f37e7a640a355fd08dd64.png'),
(322, 2418, 'images/product_img/2020/02/', 'packageMainImg_16fb6f3e49d43c67ffdfd0999e81cf11.png'),
(323, 2419, 'images/product_img/2020/02/', 'packageMainImg_51e746c30306c5c42c41ef2d6be8d6c7.png'),
(324, 2420, 'images/product_img/2020/02/', 'packageMainImg_b6212144b39f876b7ab71c0b640276a5.png'),
(325, 2421, 'images/product_img/2020/02/', 'packageMainImg_27c5ed27af0fb881834370f4c7ea369d.png'),
(326, 2422, 'images/product_img/2020/02/', 'packageMainImg_3a17f7f43e8892bb75e6bc7934b905dd.png'),
(327, 2423, 'images/product_img/2020/02/', 'packageMainImg_2577363e180a3688ed916f871e970700.png'),
(328, 2424, 'images/product_img/2020/02/', 'packageMainImg_d5e5d410d437d01b4d018f7791f80dad.png'),
(329, 2425, 'images/product_img/2020/02/', 'packageMainImg_8fb64fcf4a3b8f4579ad13705d59d383.png'),
(330, 2426, 'images/product_img/2020/02/', 'packageMainImg_2b493a5ad2d748a97469708bbeb9ff5f.png'),
(331, 2427, 'images/product_img/2020/02/', 'packageMainImg_0f9617d10a7505cae0277a10d755d960.png'),
(332, 2428, 'images/product_img/2020/02/', 'packageMainImg_71548e4c1872755e6873416c2f49afe4.png'),
(333, 2429, 'images/product_img/2020/02/', 'packageMainImg_26bd5da8b2ed39834d6dbabe0a20566c.png'),
(334, 2430, 'images/product_img/2020/02/', 'packageMainImg_a7423121ce219b0308ae99a08ed7ebc3.png'),
(335, 2431, 'images/product_img/2020/02/', 'packageMainImg_aa1592a1c5db9fe17e5665411f3dc98f.png'),
(336, 2432, 'images/product_img/2020/02/', 'packageMainImg_12b24fefc1bdf3f378c0899959369f8a.png'),
(337, 2433, 'images/product_img/2020/02/', 'packageMainImg_c94e0c7586468b6e0c684e65e4fa0231.png'),
(338, 2434, 'images/product_img/2020/02/', 'packageMainImg_dbe1e8befc831cfe8be427ec1d52ec2d.png'),
(339, 2435, 'images/product_img/2020/02/', 'packageMainImg_9e1ddb766dd906ab3aa01ac9b5d4c316.png'),
(340, 2436, 'images/product_img/2020/02/', 'packageMainImg_a2ccbe2f1b3958e0babfa4d238899dd7.png'),
(341, 2437, 'images/product_img/2020/02/', 'packageMainImg_91e82999cf7e45da1070ebd673690716.png'),
(342, 2438, 'images/product_img/2020/02/', 'packageMainImg_02b15d615f6cdba2ff963cdcd4b11984.png'),
(343, 2439, 'images/product_img/2020/02/', 'packageMainImg_47b6f0fd843768e22fd3b09370a7a13a.png'),
(344, 2440, 'images/product_img/2020/02/', 'packageMainImg_2c40a04b13d9559fb17a1fc44650e38b.png'),
(345, 2441, 'images/product_img/2020/02/', 'packageMainImg_2b316ebfc4e8820ea692995167504784.png'),
(346, 2442, 'images/product_img/2020/02/', 'packageMainImg_579ab7df8fda2d082b053640adb92a68.png'),
(347, 2443, 'images/product_img/2020/02/', 'packageMainImg_d2f89b4616e9df198a9c517d88cfd2d9.png'),
(348, 2444, 'images/product_img/2020/02/', 'packageMainImg_c26e3cccf31d4d2e535ad2d3916ae634.png'),
(349, 2445, 'images/product_img/2020/02/', 'packageMainImg_7660e73ce417d701d5e327e2d6e82b2d.png'),
(350, 2446, 'images/product_img/2020/02/', 'packageMainImg_cf7653c0e4f9ed65c89ccde883cb2ec5.png'),
(351, 2447, 'images/product_img/2020/02/', 'packageMainImg_7c792a8279211dece3b4df04719c818a.png'),
(352, 2448, 'images/product_img/2020/02/', 'packageMainImg_112233232c0e0c2c2666aa94d5740f9d.png'),
(353, 2449, 'images/product_img/2020/02/', 'packageMainImg_a7004a38ebb25a8a24edc6d0faa8ab87.png'),
(354, 2450, 'images/product_img/2020/02/', 'packageMainImg_5328733c78c192d4036d2fdbbf097b79.png'),
(355, 2451, 'images/product_img/2020/02/', 'packageMainImg_9d309abd62d13c46adc15a325c0a8f01.png'),
(356, 2452, 'images/product_img/2020/02/', 'packageMainImg_40df22ca8635e586046ee0bd7e9354c3.png'),
(357, 2453, 'images/product_img/2020/02/', 'packageMainImg_514c897cd392b608f89bb510ad95aee9.png'),
(358, 2454, 'images/product_img/2020/02/', 'packageMainImg_c15793bdf380dc150526df8abb3be85f.png'),
(359, 2455, 'images/product_img/2020/02/', 'packageMainImg_d4009d0883cd079d9f871b622b1781c9.png'),
(360, 2456, 'images/product_img/2020/02/', 'packageMainImg_ee34f51ecb0ae7948db211ae1e1c1258.png'),
(361, 2457, 'images/product_img/2020/02/', 'packageMainImg_8db2f54bf4d4e989cdaee0964c462f7c.png'),
(362, 2458, 'images/product_img/2020/02/', 'packageMainImg_664b2af8f5d6aa1220c94d907957aa41.png'),
(363, 2459, 'images/product_img/2020/02/', 'packageMainImg_728b4d334a1c026c4f7204fccff7b9c3.png'),
(364, 2460, 'images/product_img/2020/02/', 'packageMainImg_52f3060ad223714bed3b4f564367123d.png'),
(365, 2461, 'images/product_img/2020/02/', 'packageMainImg_843f00aa7f71df8e513498ad19d421f4.png'),
(366, 2462, 'images/product_img/2020/02/', 'packageMainImg_f2a4d34f5e4caeab38d3d6b5720e7e48.png'),
(367, 2463, 'images/product_img/2020/02/', 'packageMainImg_efbdfef1884dccd9d44597c70ad79f5d.png'),
(368, 2464, 'images/product_img/2020/02/', 'packageMainImg_c386cba5332d11385672ee52d036e8c1.png'),
(369, 2465, 'images/product_img/2020/02/', 'packageMainImg_8017e1dcc7cc23465c9dcaede4153e2d.png'),
(370, 2466, 'images/product_img/2020/02/', 'packageMainImg_f82bce74406925edf8bb3af8c433d64b.png'),
(371, 2467, 'images/product_img/2020/02/', 'packageMainImg_65d0b3c371bb55295c3d4a73d4afe2b4.png'),
(372, 2468, 'images/product_img/2020/02/', 'packageMainImg_1a4075a4c0809717e4b1744aa08d898e.png'),
(373, 2469, 'images/product_img/2020/02/', 'packageMainImg_90c0b830992564a8845ade0db473edad.png'),
(374, 2470, 'images/product_img/2020/02/', 'packageMainImg_fcb77058b84fcb853a498c36fc20a8d6.png'),
(375, 2471, 'images/product_img/2020/02/', 'packageMainImg_b15c85dc15a80894d856c9779a3311c8.png'),
(376, 2472, 'images/product_img/2020/02/', 'packageMainImg_fc75937c2dea06e84c4dbe8449d82e42.png'),
(377, 2473, 'images/product_img/2020/02/', 'packageMainImg_c48bf3956d9b3dd5527ccd285d6d5fc8.png'),
(378, 2474, 'images/product_img/2020/02/', 'packageMainImg_64be99a54ae8df06a2f604b2b038ef7f.png'),
(379, 2475, 'images/product_img/2020/02/', 'packageMainImg_4927ad36e4fd538d6ed99d641778befb.png'),
(380, 2476, 'images/product_img/2020/02/', 'packageMainImg_a7b4c9d71f07b92c3a315016797baabd.png'),
(381, 2477, 'images/product_img/2020/02/', 'packageMainImg_be568d6d90e73183c90e571596d4c8d8.png'),
(382, 2478, 'images/product_img/2020/02/', 'packageMainImg_8b1364fb14bc6ba5b3cf81ea852dbd4f.png'),
(383, 2479, 'images/product_img/2020/02/', 'packageMainImg_3bc23eb70b3a04b50813197f720e01d4.png'),
(384, 2480, 'images/product_img/2020/02/', 'packageMainImg_faadfc9c3dd8ec37d53b2984d961da74.png'),
(385, 2481, 'images/product_img/2020/02/', 'packageMainImg_954561c6b667c9aaf8c426b9cf8c7278.png'),
(386, 2484, 'images/product_img/2020/02/', 'packageMainImg_67b878df6cd42d142f2924f3ace85c78.png'),
(387, 2485, 'images/product_img/2020/02/', 'packageMainImg_3029e66b52f20bd4636759c92a3833a6.png'),
(388, 2486, 'images/product_img/2020/02/', 'packageMainImg_b04c123b74e3f7e94251eab06ac511fd.png'),
(389, 2487, 'images/product_img/2020/02/', 'packageMainImg_cca88ea1152e884c7bc54a8698818782.png'),
(390, 2488, 'images/product_img/2020/02/', 'packageMainImg_e0763ddd0c6175f454b394fdf2f2d5c1.png'),
(391, 2489, 'images/product_img/2020/02/', 'packageMainImg_9b5b31424241401ae0ad58660a9f8984.png'),
(392, 2490, 'images/product_img/2020/02/', 'packageMainImg_1b0d222ed998f7eadb3f32e3790fd90d.png'),
(393, 2491, 'images/product_img/2020/02/', 'packageMainImg_57787633a185b701f1e135be880cef20.png'),
(394, 2492, 'images/product_img/2020/02/', 'packageMainImg_22852db60a3406b42630a69fbc08e4a2.png'),
(395, 2493, 'images/product_img/2020/02/', 'packageMainImg_0a0fd7f94c4f2bb4a7bb32764cb14671.png'),
(396, 2494, 'images/product_img/2020/02/', 'packageMainImg_a945a6406132c8cf9a67bf20a88fac76.png'),
(397, 2495, 'images/product_img/2020/02/', 'packageMainImg_28d891fe7e5eb2fc73d50339f1f91e76.png'),
(398, 2496, 'images/product_img/2020/02/', 'packageMainImg_80f069e6cc281d5edca11ae8d6bba567.png'),
(399, 2497, 'images/product_img/2020/02/', 'packageMainImg_21d370797422ccf4deed42ef0ac2fddd.png'),
(400, 2498, 'images/product_img/2020/02/', 'packageMainImg_761bf97b69158b0e4208819ce304326d.png'),
(401, 2499, 'images/product_img/2020/02/', 'packageMainImg_4e97616fa58d4a6184af83a9e400ca7d.png'),
(402, 2500, 'images/product_img/2020/02/', 'packageMainImg_59c28a87d1223b78669f84ce790c2fb1.png'),
(403, 2501, 'images/product_img/2020/02/', 'packageMainImg_afa373132f90c826a6f2e29e4fa2584d.png'),
(404, 2502, 'images/product_img/2020/02/', 'packageMainImg_dde06f5c2a07ba9aecd70c3c4b0cf9ce.png'),
(405, 2503, 'images/product_img/2020/02/', 'packageMainImg_ab1359008c2dae6d1fb08a3d27282895.png'),
(406, 2504, 'images/product_img/2020/02/', 'packageMainImg_16cde28fb2ad785b599ed12007bf3e33.png'),
(407, 2505, 'images/product_img/2020/02/', 'packageMainImg_9b1c6cd43a1436d25b345f6dada03126.png'),
(408, 2506, 'images/product_img/2020/02/', 'packageMainImg_2f544a27435dfcc130b828ab54e3f7ab.png'),
(409, 2507, 'images/product_img/2020/02/', 'packageMainImg_cb2c8bc1a6468e9499505750baccbf2e.png'),
(410, 2508, 'images/product_img/2020/02/', 'packageMainImg_88bb259ed0cecdea3901972ca367a777.png'),
(411, 2509, 'images/product_img/2020/02/', 'packageMainImg_f717b2664d36fc57e8a2968b5bcdaeb0.png'),
(412, 2510, 'images/product_img/2020/02/', 'packageMainImg_4f21fa4cad65bde12501ac3f85271928.png'),
(413, 2511, 'images/product_img/2020/02/', 'packageMainImg_4ef28a639a2fcca9ce7f0a4d50656573.png'),
(414, 2512, 'images/product_img/2020/02/', 'packageMainImg_6ac7185be65521cec06952dd337fdeb0.png'),
(415, 2513, 'images/product_img/2020/02/', 'packageMainImg_1a33757a2a0cd3f4f214d4edc52a3f2f.png'),
(416, 2514, 'images/product_img/2020/02/', 'packageMainImg_0bdeb05362faf444f4f2a0f954e371ac.png'),
(417, 2515, 'images/product_img/2020/02/', 'packageMainImg_83630f232671763415650a4dc0cb03b7.png'),
(418, 2516, 'images/product_img/2020/02/', 'packageMainImg_53665867ff0d44049702ff25022e5a2d.png'),
(419, 2517, 'images/product_img/2020/02/', 'packageMainImg_f370d78730fa28714863a68dda7e12ae.png'),
(420, 2518, 'images/product_img/2020/02/', 'packageMainImg_ed2384594442db5463b162864878cce1.png'),
(421, 2519, 'images/product_img/2020/02/', 'packageMainImg_ad3aa53eeb50c85f55a3f12b8fcaac4b.png'),
(422, 2520, 'images/product_img/2020/02/', 'packageMainImg_68088c8a43e8d40aa265a428fd2a28fb.png'),
(423, 2523, 'images/product_img/2020/02/', 'packageMainImg_96d4cee9b97bc1e091256a60cbf64cdf.png'),
(424, 2524, 'images/product_img/2020/02/', 'packageMainImg_d465343e85d455fe418f4939e6c14a68.png'),
(425, 2525, 'images/product_img/2020/02/', 'packageMainImg_66777cfa12d147e4836069653448250b.png'),
(426, 2526, 'images/product_img/2020/02/', 'packageMainImg_96a0cc2f93365fadfcc06ba14e95fae1.png'),
(427, 2527, 'images/product_img/2020/02/', 'packageMainImg_db8b73f0cfadd0faa8c2121af6f28fb3.png'),
(428, 2528, 'images/product_img/2020/02/', 'packageMainImg_bda5fb5ac38f978dd2a67124882a5d62.png'),
(429, 2529, 'images/product_img/2020/02/', 'packageMainImg_42a6d945d958b7afe1c06d8e5ed56600.png'),
(430, 2530, 'images/product_img/2020/02/', 'packageMainImg_18e0c41d0d700c75b3b287cc80a26a4f.png'),
(431, 2531, 'images/product_img/2020/02/', 'packageMainImg_3f5cf933f88f8abad65266f7e55ad249.png'),
(432, 2532, 'images/product_img/2020/02/', 'packageMainImg_2695490d3bc4cd5fd1b5886788775e25.png'),
(433, 2533, 'images/product_img/2020/02/', 'packageMainImg_99fb69d6010333cdf1155190b7169714.png'),
(434, 2534, 'images/product_img/2020/02/', 'packageMainImg_94f73dad3598a297d2b1c911c6f918e1.png'),
(435, 2535, 'images/product_img/2020/02/', 'packageMainImg_438a2387ea89de0bc096b72b4c854c10.png'),
(436, 2536, 'images/product_img/2020/02/', 'packageMainImg_32df38d18d0df97f394f4b2b5fdca17d.png'),
(437, 2537, 'images/product_img/2020/02/', 'packageMainImg_bc6b18d6776aeecf52c653432cf7358c.png'),
(438, 2538, 'images/product_img/2020/02/', 'packageMainImg_9622d87a9ac4abca1586a0f28e10beae.png'),
(439, 2539, 'images/product_img/2020/02/', 'packageMainImg_9f755868fe0fed8f0b561c21cbddf525.png'),
(440, 2540, 'images/product_img/2020/02/', 'packageMainImg_88caebe1d6a3e146bcaa05f487bf127c.png'),
(441, 2541, 'images/product_img/2020/02/', 'packageMainImg_dab14a350ac311c7a2589ab7d48358ad.png'),
(442, 2542, 'images/product_img/2020/02/', 'packageMainImg_a929a3d529b3c7d54644f6c53e8a6504.png'),
(443, 2543, 'images/product_img/2020/02/', 'packageMainImg_cf1759c4f2a8f2db557d60e88cebc7bf.png'),
(444, 2544, 'images/product_img/2020/02/', 'packageMainImg_1861082fee7d1e9a96e0647940ca5e18.png'),
(445, 2545, 'images/product_img/2020/02/', 'packageMainImg_3a5a64a567a75090add692e2a4377e8d.png'),
(446, 2546, 'images/product_img/2020/02/', 'packageMainImg_ae638e5f0a9e8ada46020aca43efe637.png'),
(447, 2547, 'images/product_img/2020/02/', 'packageMainImg_d3dbf59097ef8e63af637bf3d2caa365.png'),
(448, 2548, 'images/product_img/2020/02/', 'packageMainImg_01981f1bad6bd63af8d54ae083eb3b89.png'),
(449, 2549, 'images/product_img/2020/02/', 'packageMainImg_7204ff130ab8854004ddf9f65fec8051.png'),
(450, 2550, 'images/product_img/2020/02/', 'packageMainImg_1ea9eb69344fa3274e12b6cd5c1ec5c5.png'),
(451, 2551, 'images/product_img/2020/03/', 'packageMainImg_5e0f8eee7d6e8ae4dc983e618d8fd2d9.png'),
(452, 2552, 'images/product_img/2020/03/', 'packageMainImg_8c647fe637f505a6e8efed76f223f69b.png'),
(453, 2553, 'images/product_img/2020/03/', 'packageMainImg_6f7be6c6d00d882eeb528e46236509a3.png'),
(454, 2554, 'images/product_img/2020/03/', 'packageMainImg_ca9c5ad305c61767ad977ea1bcffc76e.png'),
(455, 2555, 'images/product_img/2020/03/', 'packageMainImg_29b7a7f004de40d5261e8f18f80567f3.png'),
(456, 2556, 'images/product_img/2020/03/', 'packageMainImg_690df45ad289f8470a87f7f7a1f1f2bf.png'),
(457, 2557, 'images/product_img/2020/03/', 'packageMainImg_82533d97f40c8bbc0e43993562eedff7.png'),
(458, 2558, 'images/product_img/2020/03/', 'packageMainImg_664fdb55dba26e994e2c29cd457cdbfb.png'),
(459, 2559, 'images/product_img/2020/03/', 'packageMainImg_f8f57b2910d8f076700f8e3d49c79a52.png'),
(460, 2560, 'images/product_img/2020/03/', 'packageMainImg_0af3fb4aac4cf06a6b3b6d3bb7190e38.png'),
(461, 2561, 'images/product_img/2020/03/', 'packageMainImg_9d524e4f9839b9dd5b0b9555c66bfeb5.png'),
(462, 2562, 'images/product_img/2020/03/', 'packageMainImg_3b380a307dab484ee2d4c56c34aa3dcf.png'),
(463, 2563, 'images/product_img/2020/03/', 'packageMainImg_e866a5be7f86692bb89c392b8f1a1f2c.png'),
(464, 2564, 'images/product_img/2020/03/', 'packageMainImg_3ee1f349fb7b0ab5beaa15070077e976.png'),
(465, 2565, 'images/product_img/2020/03/', 'packageMainImg_53067c4d43bebb5b87d969606468bc1b.png'),
(466, 2566, 'images/product_img/2020/03/', 'packageMainImg_7b7bf97181739eec82ffe64dab39abe9.png'),
(467, 2569, 'images/product_img/2020/03/', 'packageMainImg_5401dcd73b04026a909623f2d3c2d691.png'),
(468, 2570, 'images/product_img/2020/03/', 'packageMainImg_6046058b8671fe9031779ec4193808cf.png'),
(469, 2571, 'images/product_img/2020/03/', 'packageMainImg_7370a591496d0812d37a683d2397e75e.png'),
(470, 2572, 'images/product_img/2020/03/', 'packageMainImg_11afb81e810ff49fff66cb07889ec0c1.png'),
(471, 2573, 'images/product_img/2020/03/', 'packageMainImg_3bed72e8fbf1478bbfc0b4d5b72a0a5f.png'),
(472, 2574, 'images/product_img/2020/03/', 'packageMainImg_74800273107a502bdd8dbccb7529546b.png'),
(473, 2575, 'images/product_img/2020/03/', 'packageMainImg_87bd3ce6ec8af92cf1c3f7d330f1fdc0.png'),
(474, 2576, 'images/product_img/2020/03/', 'packageMainImg_f88a427eb9f8c04218fdd193884c0ac6.png'),
(475, 2577, 'images/product_img/2020/03/', 'packageMainImg_52b4dd484a9d821442d98a763096c3fc.png'),
(476, 2578, 'images/product_img/2020/03/', 'packageMainImg_338890d05d2b3624a1ee45d1fe5f4a29.png'),
(477, 2579, 'images/product_img/2020/03/', 'packageMainImg_8aa797a77571ee8e29f633f396a0b669.png'),
(478, 2580, 'images/product_img/2020/03/', 'packageMainImg_c0b9031eabb6c34699a6427622186cdc.png'),
(479, 2582, 'images/product_img/2020/03/', 'packageMainImg_be8122a3d793d8cc0a09b7febb8ed0ae.png'),
(480, 2583, 'images/product_img/2020/03/', 'packageMainImg_b65e5bab2f3892af0f30ba31aac68205.png'),
(481, 2584, 'images/product_img/2020/03/', 'packageMainImg_bc870f725a0bf8cf3b062a8475eb8a95.png'),
(482, 2585, 'images/product_img/2020/03/', 'packageMainImg_dad6027f0f61693f24642e9beff69be3.png'),
(483, 2586, 'images/product_img/2020/03/', 'packageMainImg_c1d6de056b0cd039651df980064bfc42.png'),
(484, 2587, 'images/product_img/2020/03/', 'packageMainImg_c66a2bd7378234a7f7347acb11865bd3.png'),
(485, 2588, 'images/product_img/2020/03/', 'packageMainImg_a8f2feccfa40ba24292dc0f3c2eb671d.png'),
(486, 2589, 'images/product_img/2020/03/', 'packageMainImg_5bb1035a59baa958fdbd475a13b97587.png'),
(487, 2590, 'images/product_img/2020/03/', 'packageMainImg_25102a5d75eb9ae3132096ab3263c943.png'),
(488, 2591, 'images/product_img/2020/03/', 'packageMainImg_94aecbd76c03c7880ecbefaddc7c6234.png'),
(489, 2592, 'images/product_img/2020/03/', 'packageMainImg_4978937c4e9a378b775fa2f90cbb9118.png'),
(490, 2593, 'images/product_img/2020/03/', 'packageMainImg_da8f04e297ef8e5dd9c89d40b9304e95.png'),
(491, 2594, 'images/product_img/2020/03/', 'packageMainImg_fc095d19b9a041d22cb36cb8419edaec.png'),
(492, 2595, 'images/product_img/2020/03/', 'packageMainImg_87c9a5bbe1aabccfbb373243e6a14667.png'),
(493, 2596, 'images/product_img/2020/03/', 'packageMainImg_2ae6c32ce062176cd7bd7d2932b2097c.png'),
(494, 2597, 'images/product_img/2020/03/', 'packageMainImg_c513d4795ad5f4e87249252bc971b9d8.png'),
(495, 2598, 'images/product_img/2020/03/', 'packageMainImg_2a15c20b7d59e2193c1c62facd25d702.png'),
(496, 2600, 'images/product_img/2020/03/', 'packageMainImg_cd4a70053af7e56844fc07c8bd197e99.png'),
(497, 2601, 'images/product_img/2020/03/', 'packageMainImg_bd11052f284c5e16b711c882521f8993.png'),
(498, 2602, 'images/product_img/2020/03/', 'packageMainImg_f7cfbc8c0e5e81d95b4de3d3601e0c0d.png'),
(499, 2603, 'images/product_img/2020/03/', 'packageMainImg_9a33ca7ff5e4de697c5d63deddd1703d.png'),
(500, 2604, 'images/product_img/2020/03/', 'packageMainImg_de444d3bc4d4928df617e5c35166ea43.png'),
(501, 2605, 'images/product_img/2020/03/', 'packageMainImg_7a0b4c69b1ecec5256ad82cd854e3a48.png'),
(502, 2606, 'images/product_img/2020/03/', 'packageMainImg_e987671d710099760f2ad231066348e5.png'),
(503, 2607, 'images/product_img/2020/03/', 'packageMainImg_b588e25a443b65e48fa1be96f605df2d.png'),
(504, 2608, 'images/product_img/2020/03/', 'packageMainImg_e8011cd5330a4b355c7a8d96f557ce69.png'),
(505, 2599, 'images/product_img/2020/03/', 'packageMainImg_2c0da97c8192ab7fa770d5ddb1982d1f.png'),
(506, 2609, 'images/product_img/2020/03/', 'packageMainImg_382c26b11f2b03430435dbbc838e185b.png'),
(507, 2610, 'images/product_img/2020/03/', 'packageMainImg_b64534f0674c5c7cc1877aa458778d2e.png'),
(508, 2611, 'images/product_img/2020/03/', 'packageMainImg_40b13aeac01b807d380c100f6f0f78c4.png'),
(509, 2612, 'images/product_img/2020/03/', 'packageMainImg_da7715816dc3294691e9a4f4543c2579.png'),
(510, 2613, 'images/product_img/2020/03/', 'packageMainImg_35d5c6b689a4bd2db8843f661814fb4b.png'),
(511, 2630, 'images/product_img/2020/04/', 'packageMainImg_2ba3fb10169ac4d8a3a9eb3085b9ab68.png'),
(513, 2632, 'images/product_img/2020/04/', 'packageMainImg_11d0f51d9aced381efa5ce3214d9228e.png'),
(514, 2633, 'images/product_img/2020/04/', 'packageMainImg_f2cf481d5f6b129864f7ce908974033e.png'),
(515, 2634, 'images/product_img/2020/05/', 'packageMainImg_c024ba85523dcfb781495f23d3af276e.png'),
(516, 2635, 'images/product_img/2020/05/', 'packageMainImg_75d4446b16845111a4059809b455acf8.png'),
(517, 2636, 'images/product_img/2020/05/', 'packageMainImg_84a0947a8943fff421055a4e4abfa1dc.png'),
(518, 2637, 'images/product_img/2020/05/', 'packageMainImg_fd8fceee135b55d06adeacf4ba57fa08.png'),
(519, 2638, 'images/product_img/2020/05/', 'packageMainImg_0895e500af81246eae98dbfb6a26d3fb.png'),
(520, 2639, 'images/product_img/2020/05/', 'packageMainImg_fa188d41fef969b9258dda1cbac7e02c.jpg'),
(521, 2640, 'images/product_img/2020/05/', 'packageMainImg_fd0e56ea557cd62b11b39dbb113b27fd.jpg'),
(522, 2641, 'images/product_img/2020/05/', 'packageMainImg_3ee3555524194d41afd914384e03e4c7.png'),
(523, 2642, 'images/product_img/2020/05/', 'packageMainImg_049d95db2e961bc4d608885bfc7e55e7.png'),
(524, 2643, 'images/product_img/2020/05/', 'packageMainImg_fab14f5a45497b114eab0759e6d42382.jpg'),
(525, 2644, 'images/product_img/2020/05/', 'packageMainImg_efbd43b394eef7761983d9e45df6b7f4.jpg'),
(526, 2645, 'images/product_img/2020/05/', 'packageMainImg_ed775eaf81c43e1d256e5b68b177629e.png'),
(527, 2646, 'images/product_img/2020/05/', 'packageMainImg_8a85470394970892f744b0995cf268a2.png'),
(528, 2647, 'images/product_img/2020/05/', 'packageMainImg_065eca429e6090e4bb7bc477f6d5b7e9.png'),
(529, 2648, 'images/product_img/2020/05/', 'packageMainImg_21ee18c7c0902a0e6a5c22d5eed73c51.jpg'),
(530, 2649, 'images/product_img/2020/05/', 'packageMainImg_d857b37584d7d13c933573ccbc66cccb.jpg'),
(531, 2656, 'images/product_img/2020/05/', 'packageMainImg_066dbbd764d883749dce02e87d070037.png');
INSERT INTO `productimages` (`Id`, `itemId`, `imagePath`, `image`) VALUES
(532, 2657, 'images/product_img/2020/05/', 'packageMainImg_e42e3cde68798edc66a636a347f2b4e4.jpg'),
(533, 2658, 'images/product_img/2020/05/', 'packageMainImg_a87fd1142382df998f797f80262c5621.png'),
(534, 2659, 'images/product_img/2020/05/', 'packageMainImg_3b6b9e2fc09580c025d689d47ab26d55.jpg'),
(535, 2660, 'images/product_img/2020/05/', 'packageMainImg_4f6b914f73713079ba96baff5170c434.jpg'),
(536, 2661, 'images/product_img/2020/05/', 'packageMainImg_0de31079b6ceb9241e7bb8744b91c37b.png'),
(537, 2662, 'images/product_img/2020/05/', 'packageMainImg_429e4a44bec547a527df987730b19aab.jpg'),
(538, 2663, 'images/product_img/2020/05/', 'packageMainImg_2e8115ce44da69e34faa629027424a00.jpg'),
(539, 2664, 'images/product_img/2020/05/', 'packageMainImg_7c78b12691f2b2a94639b7674eba07e4.jpg'),
(540, 2665, 'images/product_img/2020/05/', 'packageMainImg_8e17350e42e68a55c9aef39b618e84a8.jpg'),
(541, 2666, 'images/product_img/2020/05/', 'packageMainImg_88fc53e19df9688296b34b6bf89d9f75.jpg'),
(542, 2667, 'images/product_img/2020/05/', 'packageMainImg_883c3648ae56c74c6ed4faf0ce1cc007.jpg'),
(543, 2668, 'images/product_img/2020/05/', 'packageMainImg_f73201b0d1ed43a7f99cc491c12de4af.jpg'),
(544, 2669, 'images/product_img/2020/05/', 'packageMainImg_cd4b1303679288700d79aeddabbff9b1.jpg'),
(545, 2670, 'images/product_img/2020/05/', 'packageMainImg_c3b188020c2fb8931a5150eb619d2f14.jpg'),
(546, 2671, 'images/product_img/2020/05/', 'packageMainImg_0f8aea7de67dbfb3c61df6285a1eff48.jpg'),
(547, 2672, 'images/product_img/2020/05/', 'packageMainImg_c5e1ab9c931df8f5e4c5a8aa53837d52.jpg'),
(548, 2673, 'images/product_img/2020/05/', 'packageMainImg_16bd931476b9163f1ec2fb17cdfd6a07.jpg'),
(550, 2674, 'images/product_img/2020/05/', 'packageMainImg_a3d0f0ff1f5c487c5c7efe13d249aa5d.jpg'),
(551, 2675, 'images/product_img/2020/05/', 'packageMainImg_863a9f936a6d421f399ac82838110537.jpg'),
(552, 2676, 'images/product_img/2020/05/', 'packageMainImg_a7b7accbd455bf2d82c56e90b1af9126.jpg'),
(553, 2677, 'images/product_img/2020/05/', 'packageMainImg_487cf32249fbaaa5a79258e26d54fe19.jpg'),
(554, 2678, 'images/product_img/2020/05/', 'packageMainImg_50c76dc2a7044fce1d7125b1292e373c.jpg'),
(555, 2679, 'images/product_img/2020/05/', 'packageMainImg_5603209ca9319f0e39b51f2abfcd6f46.jpg'),
(556, 2680, 'images/product_img/2020/05/', 'packageMainImg_97b61322bacb22c4ff2887b3caa9804a.jpg'),
(557, 2681, 'images/product_img/2020/05/', 'packageMainImg_79515ada6593b92c2dc6b4938fb9ef84.jpg'),
(558, 2682, 'images/product_img/2020/05/', 'packageMainImg_bbecdaa14b7f862b6a4fe6c40e474641.png'),
(559, 2683, 'images/product_img/2020/05/', 'packageMainImg_485a14fd62b66db23227ab287b1eea1b.jpg'),
(560, 2684, 'images/product_img/2020/05/', 'packageMainImg_b739f0c45e9c5f0e6787be890edf693f.jpg'),
(561, 2685, 'images/product_img/2020/05/', 'packageMainImg_7cef80d72afe744e02fb45f64b57b9de.jpg'),
(562, 2686, 'images/product_img/2020/05/', 'packageMainImg_0512e6ba3d992b11f70534eb767d44ee.jpg'),
(563, 2686, 'images/product_img/2020/05/', 'packageMainImg_a3318963ae7c81f2e261d27beb00f79c.jpg'),
(564, 2687, 'images/product_img/2020/05/', 'packageMainImg_9b31229c5fd58c1ddb694f0232d0dd1f.jpg'),
(565, 2688, 'images/product_img/2020/05/', 'packageMainImg_bed435a6c7f23d7464da5dca7d7cf9a6.jpg'),
(566, 2689, 'images/product_img/2020/05/', 'packageMainImg_10cc05b82e144366c50629b9e87c56ed.jpg'),
(567, 2690, 'images/product_img/2020/05/', 'packageMainImg_91c53a37f97ad3a17e65a698ad386d60.jpg'),
(568, 2690, 'images/product_img/2020/05/', 'packageMainImg_8a7125cf21c48bbbea35c93af07b648b.jpg'),
(569, 2691, 'images/product_img/2020/05/', 'packageMainImg_b46e4d83eb0c080e114a32ee1f842b7d.jpg'),
(570, 2692, 'images/product_img/2020/05/', 'packageMainImg_5fff84383a30541c85e7606b0ec48b36.jpg'),
(571, 2693, 'images/product_img/2020/05/', 'packageMainImg_b29b96b78762d7c4eb6089af70c1ffbf.jpg'),
(572, 2696, 'images/product_img/2020/05/', 'packageMainImg_1cfa81af29c6f2d8cacb44921722e753.jpg'),
(573, 2697, 'images/product_img/2020/05/', 'packageMainImg_defb94f972f10f6cb018d2374002e647.jpg'),
(574, 2698, 'images/product_img/2020/05/', 'packageMainImg_c9670434d3a2c99b933bfda40825a8af.jpg'),
(575, 2699, 'images/product_img/2020/05/', 'packageMainImg_891f5cfb128a8a4f254cd74eaf2173a5.jpg'),
(576, 2700, 'images/product_img/2020/05/', 'packageMainImg_1a5c101c263fdde18ea340218d73a7de.jpg'),
(577, 2701, 'images/product_img/2020/05/', 'packageMainImg_657ac61cd172131b253d5eca72235040.jpg'),
(578, 2702, 'images/product_img/2020/05/', 'packageMainImg_7885d6f7d93ff334809b18a0c1874d61.jpg'),
(579, 2703, 'images/product_img/2020/05/', 'packageMainImg_7237aa8d3abcc0e1ce1df7ef512a0eb0.jpg'),
(580, 2704, 'images/product_img/2020/05/', 'packageMainImg_0fbd30a58e586ad7b0e3ae2485e81c34.jpg'),
(581, 2704, 'images/product_img/2020/05/', 'packageMainImg_ac751535aa6df891f8367e06d7b68cd3.jpg'),
(582, 2705, 'images/product_img/2020/05/', 'packageMainImg_75f9d915e39f44976c05951f8511b977.jpg'),
(583, 2706, 'images/product_img/2020/05/', 'packageMainImg_252d621c02301a73f609ef1a98415565.jpg'),
(584, 2707, 'images/product_img/2020/05/', 'packageMainImg_503f25bdc2eb35a8468ff1663781509e.jpg'),
(585, 2617, 'images/product_img/2020/05/', 'packageMainImg_6584759a4f33d0af8ac0898b6b37efe9.jpg'),
(587, 2708, 'images/product_img/2020/05/', 'packageMainImg_e64e1a795115e0da5a4ab985e5d8f674.jpg'),
(588, 2709, 'images/product_img/2020/05/', 'packageMainImg_974e4141b8c8d4b2258921e42b810595.jpg'),
(589, 2709, 'images/product_img/2020/05/', 'packageMainImg_5a2874a1d32de13714170d1a9221ca32.jpg'),
(590, 2710, 'images/product_img/2020/05/', 'packageMainImg_0bd450add734ad2972132bb82ed385f9.jpg'),
(595, 2130, 'images/product_img/2020/05/', 'packageMainImg_532d216803e2894d179827f378e5d232.jpg'),
(596, 2711, 'images/product_img/2020/05/', 'packageMainImg_1de77f62112ca5add5de20c6074f34b6.png'),
(597, 2712, 'images/product_img/2020/05/', 'packageMainImg_b2c9477534180fb3f4e3753fc9753128.jpg'),
(598, 2713, 'images/product_img/2020/05/', 'packageMainImg_236d1a48d9e416fe104f873bc8a1c0c1.jpg'),
(599, 2714, 'images/product_img/2020/05/', 'packageMainImg_c8b54070263aa2efd26d6b8753b0695b.jpg'),
(600, 2715, 'images/product_img/2020/05/', 'packageMainImg_bd5313f20683035122412cd8adaf951f.jpg'),
(601, 2716, 'images/product_img/2020/05/', 'packageMainImg_1179ff83d232cf99bd01e86f8e2433fa.jpg'),
(602, 2717, 'images/product_img/2020/05/', 'packageMainImg_d51e9369962ac774d36ccea0f29f9d65.jpg'),
(603, 2718, 'images/product_img/2020/05/', 'packageMainImg_16d5d834dae0bd5db4191916f71131ec.jpg'),
(604, 2719, 'images/product_img/2020/05/', 'packageMainImg_fc16a65cfc9e2ef3bdf439d6199464cb.jpg'),
(605, 2720, 'images/product_img/2020/05/', 'packageMainImg_181c49d9b533171e446045171bbc848e.jpg'),
(606, 2721, 'images/product_img/2020/05/', 'packageMainImg_d995cc966b4586d3e196cd945d0f8cbf.jpg'),
(607, 2722, 'images/product_img/2020/05/', 'packageMainImg_44cff6d738239294e5b1fdcda8a31da2.jpg'),
(608, 2723, 'images/product_img/2020/05/', 'packageMainImg_f63e5405a8bea586fae213c00547fc8b.jpg'),
(609, 2724, 'images/product_img/2020/05/', 'packageMainImg_bbc743967308639dd8d8f000a299aeef.jpg'),
(610, 2725, 'images/product_img/2020/05/', 'packageMainImg_b23bcd9f28062e8b615950ac290decb9.jpg'),
(611, 2726, 'images/product_img/2020/05/', 'packageMainImg_0d528dd5e517c8a3cb62411cbd0ff5f3.jpg'),
(612, 2728, 'images/product_img/2020/05/', 'packageMainImg_5335070ac7209de713d925ab05cea19e.jpg'),
(613, 2729, 'images/product_img/2020/05/', 'packageMainImg_4fe397438236c4649800186ffc7f1085.jpg'),
(614, 2730, 'images/product_img/2020/05/', 'packageMainImg_6c6aeeae492aabed2f3b873d33541c7c.jpg'),
(615, 2731, 'images/product_img/2020/05/', 'packageMainImg_b2ba3b9f8631b3951ba97efc642b4c33.jpg'),
(616, 2732, 'images/product_img/2020/05/', 'packageMainImg_e4e851e11530bab54932c88eb1b6a121.jpg'),
(617, 2733, 'images/product_img/2020/05/', 'packageMainImg_9565b6109b8298f7a816cf1cb7803e96.jpg'),
(618, 2734, 'images/product_img/2020/05/', 'packageMainImg_ec698d146655a805d3cef1c607f9d035.jpg'),
(619, 2735, 'images/product_img/2020/05/', 'packageMainImg_10965cd8e71b18e0bc0c3e7de725a01f.jpg'),
(620, 2736, 'images/product_img/2020/05/', 'packageMainImg_50e0b75a18979f835cc8eee5196605d9.jpg'),
(621, 2737, 'images/product_img/2020/05/', 'packageMainImg_eb475b60e644c83d9f3b149e94ce5a89.jpg'),
(622, 2738, 'images/product_img/2020/05/', 'packageMainImg_29d6f494f888284d18fa14ee6160356c.jpg'),
(623, 2739, 'images/product_img/2020/05/', 'packageMainImg_d422c1ccef74ba6c42a5011c76b5f769.jpg'),
(624, 2740, 'images/product_img/2020/05/', 'packageMainImg_3ec38cf395fba6c0023e388fbd20792c.jpg'),
(625, 2741, 'images/product_img/2020/05/', 'packageMainImg_2214f73b27293c32f946da63bb2c41ce.jpg'),
(626, 2742, 'images/product_img/2020/05/', 'packageMainImg_df5ac49b760c9e24b52bf147c47ce0ea.jpg'),
(627, 2743, 'images/product_img/2020/05/', 'packageMainImg_8930bd66fc205b67472770d2e732be8b.jpg'),
(628, 2744, 'images/product_img/2020/05/', 'packageMainImg_2d2fd02b494c2a639134fb382ac6d0bd.jpg'),
(629, 2745, 'images/product_img/2020/05/', 'packageMainImg_a6eab5560609e8a2db70c40fd7499791.jpg'),
(630, 2746, 'images/product_img/2020/05/', 'packageMainImg_f633296de636b91483faae99a949eb26.jpg'),
(631, 2747, 'images/product_img/2020/06/', 'packageMainImg_4086fa76a5e73e5552cd1f9ae7906c3e.jpg'),
(632, 2748, 'images/product_img/2020/06/', 'packageMainImg_46aa3b1f0babd15e3de880e7704cec6f.jpg'),
(633, 2749, 'images/product_img/2020/06/', 'packageMainImg_7f7e3b7be9aec4420369732f864c0c22.jpg'),
(634, 2750, 'images/product_img/2020/06/', 'packageMainImg_845707c07503b403b20eb02c4454b6c2.jpg'),
(635, 2750, 'images/product_img/2020/06/', 'packageMainImg_775a46e8c6d09ce5548db66cc249435c.jpg'),
(636, 2751, 'images/product_img/2020/06/', 'packageMainImg_da8d4a7a100fbf61020bcdba7457a967.jpg'),
(637, 2752, 'images/product_img/2020/06/', 'packageMainImg_3b296a414315b9185cd5055a38d98b46.jpg'),
(638, 2752, 'images/product_img/2020/06/', 'packageMainImg_e4e425eb0829907da6fd216e1e984389.jpg'),
(639, 2754, 'images/product_img/2020/06/', 'packageMainImg_7ad3f86772236118c1013c59481be41a.jpg'),
(641, 2755, 'images/product_img/2020/06/', 'packageMainImg_76972b0170348904155d826276086a6f.jpg'),
(642, 2756, 'images/product_img/2020/06/', 'packageMainImg_e650fa070b81e570aaec97f0af2245f2.jpg'),
(643, 2757, 'images/product_img/2020/06/', 'packageMainImg_3af4d6c0ca7f7572bfcec52188fb907b.jpg'),
(644, 2758, 'images/product_img/2020/06/', 'packageMainImg_5330f5fa350679fb680ac438824048bd.jpg'),
(645, 2758, 'images/product_img/2020/06/', 'packageMainImg_2a51f1327274ea6934061e143d6563a5.jpg'),
(646, 2759, 'images/product_img/2020/06/', 'packageMainImg_cb464ffc8998b3ae15f9c74c57fc7b2c.jpg'),
(647, 2760, 'images/product_img/2020/06/', 'packageMainImg_ac54b5686a48663e1ae3a163ff7cd109.jpg'),
(648, 2761, 'images/product_img/2020/06/', 'packageMainImg_71196e8682c1d4cc2fb7492784e12f15.jpg'),
(649, 2762, 'images/product_img/2020/06/', 'packageMainImg_f64d73e4b5beda129a02c3a42e584129.jpg'),
(650, 2763, 'images/product_img/2020/06/', 'packageMainImg_6cc6562b023845fee40ada01dc9a1449.jpg'),
(651, 2764, 'images/product_img/2020/06/', 'packageMainImg_4a313bdf94d11aad262859138c1bd9c7.jpg'),
(652, 2765, 'images/product_img/2020/06/', 'packageMainImg_2941069e6dc29426de0c90702d7482ff.jpg'),
(653, 2766, 'images/product_img/2020/06/', 'packageMainImg_7e6cb5628570987e8f2da936009b4043.jpg'),
(654, 2767, 'images/product_img/2020/06/', 'packageMainImg_b00513d30834a628a19788f5a1d20e6c.jpg'),
(655, 2768, 'images/product_img/2020/06/', 'packageMainImg_3d5b42bf7d5bef202a441fce7252611b.jpg'),
(656, 2768, 'images/product_img/2020/06/', 'packageMainImg_c913303f392ffc643f7240b180602652.jpg'),
(657, 2769, 'images/product_img/2020/06/', 'packageMainImg_c82a69882f6ba11855f9c1db9e7c1602.jpg'),
(658, 2770, 'images/product_img/2020/06/', 'packageMainImg_2078750ea356ba9fd472655dec36c068.jpg'),
(659, 2771, 'images/product_img/2020/06/', 'packageMainImg_4e05dc8e52de02609ea4859612a8262b.jpg'),
(660, 2772, 'images/product_img/2020/06/', 'packageMainImg_2e4d7672fe3571f59df8448384ec8b29.jpg'),
(661, 2773, 'images/product_img/2020/06/', 'packageMainImg_975dec86e557a82aa40ee02126721d2e.jpg'),
(662, 2774, 'images/product_img/2020/06/', 'packageMainImg_98168dab882d360b33a7e9829372cd74.jpg'),
(663, 2775, 'images/product_img/2020/06/', 'packageMainImg_23be1ae664c918c04af4304e03a6df92.jpg'),
(664, 2775, 'images/product_img/2020/06/', 'packageMainImg_5bbab4bc9056f39f7ca0bdd313112289.jpg'),
(666, 2776, 'images/product_img/2020/06/', 'packageMainImg_a7949d52970c88c7ee899ef1bf0da04e.jpg'),
(667, 2777, 'images/product_img/2020/06/', 'packageMainImg_ef8d7b232e61be36c0b9b6e72df4a62f.jpg'),
(668, 2778, 'images/product_img/2020/06/', 'packageMainImg_b834def2af37978401134229363b0b23.jpg'),
(669, 2779, 'images/product_img/2020/06/', 'packageMainImg_89da20a8a13c626a8d548921efab2754.jpg'),
(670, 2780, 'images/product_img/2020/06/', 'packageMainImg_2dfa9121b459312eb5c71e117f2d5e82.jpg'),
(671, 2781, 'images/product_img/2020/06/', 'packageMainImg_794cb9655cee4b3376275dfdb06ea4ea.jpg'),
(672, 2782, 'images/product_img/2020/08/', 'packageMainImg_acb61c2d804bdc36c70376da6c64b23e.jpg'),
(673, 2783, 'images/product_img/2020/08/', 'packageMainImg_5355a6dead6a38f1885e99467ef24369.png'),
(674, 2784, 'images/product_img/2020/08/', 'packageMainImg_84d8df76e535e01ca740d9879fb26978.png'),
(675, 2785, 'images/product_img/2020/08/', 'packageMainImg_ec79e4f0497f02ae943fa7566879834d.png'),
(676, 2786, 'images/product_img/2020/08/', 'packageMainImg_cf0dcafb63734e2bb6d76486db5b8008.png'),
(677, 2787, 'images/product_img/2020/08/', 'packageMainImg_04db022594c11d5c5a5f9dc6d427f81d.png'),
(678, 2788, 'images/product_img/2020/08/', 'packageMainImg_c542c229592c744d3eb23b85f46b93c9.png'),
(679, 2790, 'images/product_img/2020/08/', 'packageMainImg_d01016b522179c23b1a4830a85a8c1a3.png'),
(680, 2791, 'images/product_img/2020/08/', 'packageMainImg_322e03b788964c64c6a1806a36f21daf.jpg'),
(681, 2792, 'images/product_img/2020/08/', 'packageMainImg_1513f44d9d0dbca1c3d60597b0e8dc50.png'),
(682, 2793, 'images/product_img/2020/08/', 'packageMainImg_06ad34a3bc5df0e730dc94874e9241b2.png'),
(683, 2794, 'images/product_img/2020/08/', 'packageMainImg_2cf0bd9c4461d53743d55bd4bdcf950a.png'),
(684, 2795, 'images/product_img/2020/08/', 'packageMainImg_5a023228c9215bb780300477a24b5625.png'),
(685, 2796, 'images/product_img/2020/08/', 'packageMainImg_9e1f6c3e18b7ba1956ad04a805b2ba7f.png'),
(686, 2797, 'images/product_img/2020/08/', 'packageMainImg_b09000c8c8d7de04d6db251bf5b6d9a0.jpg'),
(687, 2798, 'images/product_img/2020/08/', 'packageMainImg_e514144dee215befddcdb4dba38ab0cc.jpg'),
(688, 2799, 'images/product_img/2020/08/', 'packageMainImg_3441a1e7a8ef0b84d1a32512dbcaed68.png'),
(689, 2800, 'images/product_img/2020/08/', 'packageMainImg_06e1a0aba973396ed3e00f377fdd0fa5.png'),
(690, 2801, 'images/product_img/2020/08/', 'packageMainImg_391b927d76db50160f4274a579d9a2f4.png'),
(691, 2802, 'images/product_img/2020/08/', 'packageMainImg_fa5726ab5e7eb95dd76b23854ed2f0b4.png'),
(692, 2803, 'images/product_img/2020/08/', 'packageMainImg_3b6387db00e4a980de0ea770a089528c.png'),
(694, 2804, 'images/product_img/2020/08/', 'packageMainImg_0e55b71365fd454d0645a5ce8807a8c8.png'),
(695, 2805, 'images/product_img/2020/08/', 'packageMainImg_98b40ab1e4bd97d54afb9113b40c72fd.png'),
(696, 2807, 'images/product_img/2020/08/', 'packageMainImg_c4e65a9a59a73b44ff12147ae8b560a0.png'),
(698, 2808, 'images/product_img/2020/08/', 'packageMainImg_8e8fa9d156fa577c3bc18ca4165cb3dd.jpg'),
(699, 2809, 'images/product_img/2020/08/', 'packageMainImg_3ae09362e39fd5ea97f96f1695286e91.jpg'),
(702, 2812, 'images/product_img/2020/08/', 'packageMainImg_2d072a041a2b8c334b56030f38928b52.jpg'),
(703, 2813, 'images/product_img/2020/08/', 'packageMainImg_7ad04fcfb8e34710e55511efad1af444.jpg'),
(704, 2814, 'images/product_img/2020/08/', 'packageMainImg_84b08f04d5b50958441953bec5aa531d.png'),
(705, 2815, 'images/product_img/2020/08/', 'packageMainImg_23f8c8e9c274e95bacb1a1bfcfc4051d.png'),
(706, 2816, 'images/product_img/2020/08/', 'packageMainImg_588cf7c2643c22ceb02317bcb64a7a6a.jpg'),
(707, 2817, 'images/product_img/2020/08/', 'packageMainImg_043f631a6138c2eaed2322fae21e977d.png'),
(708, 2818, 'images/product_img/2020/08/', 'packageMainImg_709336d8b15286caf8a83fc77a8f0f99.png'),
(709, 2819, 'images/product_img/2020/08/', 'packageMainImg_dd3cd8b31f21e6ff4f74584f8710277f.png'),
(710, 2820, 'images/product_img/2020/08/', 'packageMainImg_9c11070a9daeaf0626b44cf8b1b453fb.png'),
(711, 2821, 'images/product_img/2020/08/', 'packageMainImg_cf5705a0d56224741fb74726c25732f8.png'),
(712, 2822, 'images/product_img/2020/08/', 'packageMainImg_2fb935fe618c44aea77d4a1378a2cf2c.png'),
(713, 2823, 'images/product_img/2020/08/', 'packageMainImg_ba4250b13d7c5a69739d5dd3196179dd.png'),
(714, 2824, 'images/product_img/2020/09/', 'packageMainImg_e540ce8c568c48bd1eeee1380484c4df.png'),
(715, 2825, 'images/product_img/2021/03/', 'packageMainImg_376263cde9ab3cfd89e3e1949d38d39c.jpg'),
(716, 2827, 'images/product_img/2021/03/', 'packageMainImg_e2f611fc2b8b483ce53774609154c089.jpeg'),
(717, 2828, 'images/product_img/2021/07/', 'packageMainImg_11c771471cf7a579e69962f8f3e83a34.jpg'),
(718, 2828, 'images/product_img/2021/07/', 'packageMainImg_397c1d54db9987005bab782a51e3c815.jpg'),
(719, 2829, 'images/product_img/2021/07/', 'packageMainImg_ac873fa8c17dba5e45c449c688c0ed9b.jpg'),
(720, 2830, 'images/product_img/2021/07/', 'packageMainImg_c96cde17c6ce57e1c459f8328b55a17f.jpg'),
(721, 2831, 'images/product_img/2021/07/', 'packageMainImg_aeefd0036128130006afd1bdbd2cc480.jpg'),
(722, 2832, 'images/product_img/2021/07/', 'packageMainImg_6928ccd6bfad85629857d971cc1b46ca.jpg'),
(723, 2833, 'images/product_img/2021/11/', 'packageMainImg_db4e4e0ee339b1893b327807344aa576.jpg'),
(724, 2833, 'images/product_img/2021/11/', 'packageMainImg_19ae6f9fda6e3dd414052956e3bd0915.jpg'),
(725, 2834, 'images/product_img/2021/11/', 'packageMainImg_ffc7d6a474159eb87ff0b0b14459ded1.jpg'),
(726, 2835, 'images/product_img/2021/11/', 'packageMainImg_ddd39a06e304f7126e2e27cc03ca04a8.jpg'),
(730, 11, 'images/product_img/2023/01/', 'packageMainImg_d9639bfd60f2dd0054317ba6e4f8fa40.jpg'),
(735, 35, 'images/product_img/2023/04/', 'packageMainImg_228e5bb425540c8249852b8995d56a90.jpg'),
(739, 116, 'images/product_img/2023/04/', 'packageMainImg_1c64df71889fd2a55c372534864c54bf.jpg'),
(746, 12, 'images/product_img/2023/04/', 'packageMainImg_2dfe07a9c25f58c97e2a0ecb54e84be4.jpg'),
(750, 28, 'images/product_img/2023/04/', 'packageMainImg_26c9d4be8af2e96fda03f8d9464cd1c7.jpg'),
(751, 108, 'images/product_img/2024/08/', 'packageMainImg_564c68ea17a0bd0e3f0744ba9586db66.jpg'),
(752, 116, 'images/product_img/2024/08/', 'packageMainImg_921c348b89556ecd98baa7aa1db72021.jpg'),
(753, 109, 'images/product_img/2024/08/', 'packageMainImg_9e05c1b42fe44486bc403cfa11bdc25d.jpg'),
(754, 106, 'images/product_img/2024/08/', 'packageMainImg_d77f634297c4fb000d10f65569e29b87.jpg'),
(755, 99, 'images/product_img/2024/08/', 'packageMainImg_9b4e1b8830cb1833ffbf5a899ec02ae8.jpg'),
(756, 105, 'images/product_img/2024/08/', 'packageMainImg_84092da02158b10b9271ad4b04f46815.jpg'),
(757, 104, 'images/product_img/2024/08/', 'packageMainImg_0f796cb23065116d8b0959b37a528551.jpg'),
(758, 110, 'images/product_img/2024/08/', 'packageMainImg_5602fc5d1c8e099edd37c46976813a86.jpg'),
(759, 111, 'images/product_img/2024/08/', 'packageMainImg_b37180582030ef42c1858a6356a165ed.jpg'),
(760, 97, 'images/product_img/2024/08/', 'packageMainImg_3e85ae4f335d6b74dd26dd7b5a0d11fa.jpg'),
(761, 36, 'images/product_img/2024/08/', 'packageMainImg_1ce52c472e42b4bf3e430db05c225eb1.jpg'),
(762, 35, 'images/product_img/2024/08/', 'packageMainImg_4d9ecb9a78d3008fe4691c3335557603.jpg'),
(763, 40, 'images/product_img/2024/08/', 'packageMainImg_9b9b3549adef15eb5a381eccb77ba9a8.jpg'),
(764, 38, 'images/product_img/2024/08/', 'packageMainImg_b61fe22625f12f6b74863b0ce1916e49.jpg'),
(765, 121, 'images/product_img/2024/08/', 'packageMainImg_cea80cdde2db022880a99273beaa87e5.jpg'),
(766, 6, 'images/product_img/2024/08/', 'packageMainImg_40b5e99b4de2e8f98105d85b4391059b.jpg'),
(767, 9, 'images/product_img/2024/08/', 'packageMainImg_bbbc5abe55131b012c3de6795c09fc87.jpg'),
(768, 8, 'images/product_img/2024/08/', 'packageMainImg_5ae8f6400daf4265b4b0bc072f5d21d9.jpg'),
(769, 7, 'images/product_img/2024/08/', 'packageMainImg_bedd2d1077eb761b7b6832af32ec475f.jpg'),
(770, 12, 'images/product_img/2024/08/', 'packageMainImg_9cc057ea91033f15f171048dca8997fd.jpg'),
(771, 14, 'images/product_img/2024/08/', 'packageMainImg_8a3d3e9f0b7730bc9e4e20cd3314bbcc.jpg'),
(772, 13, 'images/product_img/2024/08/', 'packageMainImg_da5cf3128f5b2033afaa9bfbf24f355b.jpg'),
(773, 112, 'images/product_img/2024/08/', 'packageMainImg_507c5a543e53a1b916729e68ce45f8fe.jpg'),
(774, 69, 'images/product_img/2024/08/', 'packageMainImg_d56b4466ee7afb68fc6ff5bb7995229f.jpg'),
(775, 69, 'images/product_img/2024/08/', 'packageMainImg_cbb1c2cb31e20ab5e074a28ecafc1794.jpg'),
(776, 68, 'images/product_img/2024/08/', 'packageMainImg_0bd9ae422dcb56d202e22543553ec402.jpg'),
(777, 68, 'images/product_img/2024/08/', 'packageMainImg_c9341b89fc3eb7a699f07a09ca2a84d8.jpg'),
(778, 22, 'images/product_img/2024/08/', 'packageMainImg_2c48a6568cbd493abcf5d67544757b31.jpg'),
(779, 22, 'images/product_img/2024/08/', 'packageMainImg_b522ca9493fd4833b7efe8d287d29b38.jpg'),
(780, 3, 'images/product_img/2024/08/', 'packageMainImg_34d78b26ecf3aa2a47ebd9af1a082eea.jpg'),
(781, 3, 'images/product_img/2024/08/', 'packageMainImg_14f7cb6f3969134f8bc50834ecb5c46b.jpg'),
(782, 23, 'images/product_img/2024/08/', 'packageMainImg_293a8ef7837b3fad16001201ba43479e.jpg'),
(783, 23, 'images/product_img/2024/08/', 'packageMainImg_c8c41c4a18675a74e01c8a20e8a0f662.jpg'),
(784, 27, 'images/product_img/2024/08/', 'packageMainImg_9b2162e6f6fb0e13b6995ac94ae19bd6.jpg'),
(785, 27, 'images/product_img/2024/08/', 'packageMainImg_9c9bc3676ef683ba148c739770f18a59.jpg'),
(786, 26, 'images/product_img/2024/08/', 'packageMainImg_ee5ebad3450a667adad848c6746723a4.jpg'),
(787, 26, 'images/product_img/2024/08/', 'packageMainImg_ad38b711d2f4a3613dc20bfe7235fee6.jpg'),
(788, 25, 'images/product_img/2024/08/', 'packageMainImg_a74b3c801fd6d2bd81e94eae982381c5.jpg'),
(789, 25, 'images/product_img/2024/08/', 'packageMainImg_d3f05b80b18ed8b0aa5a887e4dfa2936.jpg'),
(790, 25, 'images/product_img/2024/08/', 'packageMainImg_0103bd9aa6009b2a270694e078c36e5a.jpg'),
(791, 15, 'images/product_img/2024/08/', 'packageMainImg_ed8023cf1976fd865d3b8ce69d22cb25.jpg'),
(792, 15, 'images/product_img/2024/08/', 'packageMainImg_dac2ff3d084361aad2e887ff994caa1c.jpg'),
(793, 16, 'images/product_img/2024/08/', 'packageMainImg_246c757140c3dcd73ec3a7a605955f48.jpg'),
(794, 117, 'images/product_img/2024/08/', 'packageMainImg_e727bbab702f010b0af3950289bcc2a8.jpg'),
(795, 117, 'images/product_img/2024/08/', 'packageMainImg_c3ee8be1c14db0287b0a0e9e8217f5d9.jpg'),
(796, 74, 'images/product_img/2024/08/', 'packageMainImg_c51f138238825a537382ba646664aad4.jpg'),
(797, 74, 'images/product_img/2024/08/', 'packageMainImg_6dffac834e83eacb50a6a49097441b0f.jpg'),
(798, 57, 'images/product_img/2024/08/', 'packageMainImg_ac756208e5af31c23aa13352680c4690.jpg'),
(799, 57, 'images/product_img/2024/08/', 'packageMainImg_324b627275f0225617707e8dc2f68814.jpg'),
(800, 57, 'images/product_img/2024/08/', 'packageMainImg_fdc210e25c7a92f3f1df16696893896a.jpg'),
(801, 58, 'images/product_img/2024/08/', 'packageMainImg_09b7a4a038775fbc2e32531b8e7d104d.jpg'),
(802, 58, 'images/product_img/2024/08/', 'packageMainImg_811aed46e50e29bd8c57e010644b8ec5.jpg'),
(803, 58, 'images/product_img/2024/08/', 'packageMainImg_55f7bc3a9c59a07ec9d84d296e37d9e9.jpg'),
(804, 62, 'images/product_img/2024/08/', 'packageMainImg_974801307e01f4431c6d1be78cb13ee1.jpg'),
(805, 62, 'images/product_img/2024/08/', 'packageMainImg_109512d4fe012a127d2ff7d058ab28cd.jpg'),
(806, 62, 'images/product_img/2024/08/', 'packageMainImg_9dd2079af1c7022f2ceadc724a6a7c64.jpg'),
(807, 119, 'images/product_img/2024/08/', 'packageMainImg_0d4d93caad10dc15f6f946b300de48e2.jpg'),
(808, 119, 'images/product_img/2024/08/', 'packageMainImg_f73488ea4a54e6c8dc39b03853685861.jpg'),
(809, 119, 'images/product_img/2024/08/', 'packageMainImg_e03518408ba3251eab84fa54bb4c3dc5.jpg'),
(810, 118, 'images/product_img/2024/08/', 'packageMainImg_9ac27aee2780395f9c2dea0ab968697c.jpg'),
(811, 118, 'images/product_img/2024/08/', 'packageMainImg_5a63d6ea9cc03c4225faa0beccca91b3.jpg'),
(812, 118, 'images/product_img/2024/08/', 'packageMainImg_33c7f134750220edffd2923d1c81d82d.jpg'),
(813, 94, 'images/product_img/2024/08/', 'packageMainImg_ee2d5bb331bcf3d69b73a8aef0397890.jpg'),
(814, 94, 'images/product_img/2024/08/', 'packageMainImg_2fe9c0ecf1f29be51b15c43d4f69cf73.jpg'),
(815, 94, 'images/product_img/2024/08/', 'packageMainImg_bf98172d65e98df41ae8cd6f8e0754f7.jpg'),
(816, 95, 'images/product_img/2024/08/', 'packageMainImg_62d5148a3b3b5f81a036be2dd3cc3919.jpg'),
(817, 95, 'images/product_img/2024/08/', 'packageMainImg_451d7e400b5899865442dfdb1e5133d9.jpg'),
(818, 95, 'images/product_img/2024/08/', 'packageMainImg_ac192a7c5879784dfc4fdec4075160da.jpg'),
(819, 59, 'images/product_img/2024/08/', 'packageMainImg_dfd2671211054be2c93f96f6faad85b4.jpg'),
(820, 59, 'images/product_img/2024/08/', 'packageMainImg_6b6461cf206da30d10d66783bb9bad80.jpg'),
(821, 34, 'images/product_img/2024/08/', 'packageMainImg_181fd099ea59e59b8685ad7792efde73.jpg'),
(822, 34, 'images/product_img/2024/08/', 'packageMainImg_346618ee9affa8d83400bb705af17457.jpg'),
(823, 34, 'images/product_img/2024/08/', 'packageMainImg_976b1a9632d56fe807128f14925de66b.jpg'),
(824, 33, 'images/product_img/2024/08/', 'packageMainImg_4a820556b3895109dd3de280433b608e.jpg'),
(825, 33, 'images/product_img/2024/08/', 'packageMainImg_2fda71fedd0bde2ec0d68eb7362ea96f.jpg'),
(826, 32, 'images/product_img/2024/08/', 'packageMainImg_9dff4e9a3dd78f2c4cfb49ea21b086f2.jpg'),
(827, 32, 'images/product_img/2024/08/', 'packageMainImg_e448b61db17d98a1ab11b1f604b5a280.jpg'),
(828, 45, 'images/product_img/2024/08/', 'packageMainImg_8f331c913c24a805a8f4b8cf3f4de489.jpg'),
(829, 45, 'images/product_img/2024/08/', 'packageMainImg_45a5bc37056e7d6f491b26c73b28e2d6.jpg'),
(830, 39, 'images/product_img/2024/08/', 'packageMainImg_816f2ccf0482a425ec1922ac3964bd79.jpg'),
(831, 114, 'images/product_img/2024/08/', 'packageMainImg_dcee2f6f8b0cc99e88d4858e9e8ef843.jpg'),
(832, 114, 'images/product_img/2024/08/', 'packageMainImg_c748995b8211ac043447828af33b7cce.jpg'),
(833, 70, 'images/product_img/2024/08/', 'packageMainImg_ec8fecff3f8b2f5c263efaae3c9c7b71.jpg'),
(835, 18, 'images/product_img/2024/08/', 'packageMainImg_5587ccc6330a6b17a66f3c972ef2fd9f.jpg'),
(836, 18, 'images/product_img/2024/08/', 'packageMainImg_f7ae01bae1a5af5dc5bada2ff2db040e.jpg'),
(837, 18, 'images/product_img/2024/08/', 'packageMainImg_b29cd8f2d653fab568dc7f4eff2d411d.jpg'),
(838, 19, 'images/product_img/2024/08/', 'packageMainImg_e10cc779c1e585710c9b4c6234eebc72.jpg'),
(839, 19, 'images/product_img/2024/08/', 'packageMainImg_387b2fe9ea4e275eb0f061ef18d8cdd5.jpg'),
(840, 19, 'images/product_img/2024/08/', 'packageMainImg_6ef468af6b37f41200a47869e244339b.jpg'),
(841, 11, 'images/product_img/2024/08/', 'packageMainImg_1ef5664a064617d13fa09ba09fad89d4.jpg'),
(842, 11, 'images/product_img/2024/08/', 'packageMainImg_f4dc2365910ecf32e20127d7abeb5147.jpg'),
(843, 11, 'images/product_img/2024/08/', 'packageMainImg_27a2fc9cb4183912a9358a838995a906.jpg'),
(844, 20, 'images/product_img/2024/08/', 'packageMainImg_2a0e84144b48b1a817a0873e20360298.jpg'),
(845, 20, 'images/product_img/2024/08/', 'packageMainImg_07cd5f2e03110800d515d116a4b02315.jpg'),
(846, 20, 'images/product_img/2024/08/', 'packageMainImg_8bfadc712b77b981b6c43b2c3d5677b0.jpg'),
(847, 122, 'images/product_img/2024/08/', 'packageMainImg_4e9d8467679ef6947175a38e559d7805.jpg'),
(848, 122, 'images/product_img/2024/08/', 'packageMainImg_762a76119a1331b0d71989ece1035f3e.jpg'),
(849, 123, 'images/product_img/2024/08/', 'packageMainImg_7b5a1e3a8196193526e75cadef97399f.jpg'),
(850, 123, 'images/product_img/2024/08/', 'packageMainImg_5558a08ae7f7e97f5a0d92cad4bce2bd.jpg'),
(851, 30, 'images/product_img/2024/08/', 'packageMainImg_76f6d92711de6f72ff03aafa7a3a963d.jpg'),
(852, 30, 'images/product_img/2024/08/', 'packageMainImg_2adf6ab600c8f21ac1d29eefd4ba0088.jpg'),
(853, 30, 'images/product_img/2024/08/', 'packageMainImg_ff818a5718fe413682dbeb2f5d0ade01.jpg'),
(854, 41, 'images/product_img/2024/08/', 'packageMainImg_1a23729659e442d9712753e92016e292.jpg'),
(855, 41, 'images/product_img/2024/08/', 'packageMainImg_48415469b0f5d46021093ee5acdabe1d.jpg'),
(856, 41, 'images/product_img/2024/08/', 'packageMainImg_4826d3fe94f7d9cd404c67249b854bdd.jpg'),
(857, 44, 'images/product_img/2024/08/', 'packageMainImg_2739bf6009ba49c951bf9f80a0ba54b3.jpg'),
(858, 44, 'images/product_img/2024/08/', 'packageMainImg_47b494b7958833a5f2f3cfdb60567ca8.jpg'),
(859, 44, 'images/product_img/2024/08/', 'packageMainImg_5555ccd55abc6733f66630163059e023.jpg'),
(860, 56, 'images/product_img/2024/08/', 'packageMainImg_a894d8b92cb0deaacf2cf46338ee9220.jpg'),
(861, 56, 'images/product_img/2024/08/', 'packageMainImg_380927d0df5268b118c093e71ff04156.jpg'),
(862, 61, 'images/product_img/2024/08/', 'packageMainImg_88d31d09b03b53c8b698f8e1b7501239.jpg'),
(863, 61, 'images/product_img/2024/08/', 'packageMainImg_d268a1b87c44ec1cf7795d86b396fb45.jpg'),
(864, 61, 'images/product_img/2024/08/', 'packageMainImg_ba5dbe452154c1213c2d91aa5d2b5676.jpg'),
(865, 64, 'images/product_img/2024/08/', 'packageMainImg_b50dc3cce02ba098341f34bf1d086fb0.jpg'),
(866, 64, 'images/product_img/2024/08/', 'packageMainImg_7c612ff52885afca3be158a3831cb439.jpg'),
(867, 64, 'images/product_img/2024/08/', 'packageMainImg_415f59655d3780e18fa366d2e65d6d05.jpg'),
(868, 101, 'images/product_img/2024/08/', 'packageMainImg_7f47286570cf93f4fbaa6c20d26feccd.jpg'),
(869, 101, 'images/product_img/2024/08/', 'packageMainImg_67e50040b19f27a8f5f3380b8567a2c7.jpg'),
(870, 103, 'images/product_img/2024/08/', 'packageMainImg_9f2e6415d51db823700ebec4b81c2eb4.jpg'),
(871, 103, 'images/product_img/2024/08/', 'packageMainImg_2db43bca3a5a2f7d668280a96baa03be.jpg'),
(872, 100, 'images/product_img/2024/08/', 'packageMainImg_cb12f0e92b6b5603733578db4b06ddbb.jpg'),
(873, 100, 'images/product_img/2024/08/', 'packageMainImg_89c018ee5535f2a98eab5142e5e6e62a.jpg'),
(874, 102, 'images/product_img/2024/08/', 'packageMainImg_fdfd89b80b69467ec9831afd4df7c7d0.jpg'),
(875, 102, 'images/product_img/2024/08/', 'packageMainImg_2c137536637deec82a748145a1f83e8d.jpg'),
(877, 2, 'images/product_img/2025/10/', 'packageMainImg_3387e834f3ecc5d41e45eae5c3686d1b.jpg'),
(881, 1, 'images/product_img/2025/10/', 'packageMainImg_8b8da110350441b3a4ca65795e8fe0e8.png');

-- --------------------------------------------------------

--
-- Table structure for table `product_availability`
--

CREATE TABLE `product_availability` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `mon` tinyint(1) DEFAULT 1,
  `tue` tinyint(1) DEFAULT 1,
  `wed` tinyint(1) DEFAULT 1,
  `thu` tinyint(1) DEFAULT 1,
  `fri` tinyint(1) DEFAULT 1,
  `sat` tinyint(1) DEFAULT 1,
  `sun` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `product_availability`
--

INSERT INTO `product_availability` (`id`, `product_id`, `mon`, `tue`, `wed`, `thu`, `fri`, `sat`, `sun`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 0, 1, 0, 1, 0, 0, '2025-11-23 02:31:11', '2025-11-23 02:57:05'),
(2, 2, 1, 1, 0, 1, 0, 1, 0, '2025-11-23 02:31:11', '2025-11-23 17:19:50'),
(3, 3, 1, 1, 1, 1, 1, 1, 1, '2025-11-23 02:31:11', '2025-11-23 02:31:11');

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `ingredient_id` int(11) NOT NULL,
  `quantity` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `process_step` int(11) DEFAULT 1,
  `process_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`id`, `product_id`, `ingredient_id`, `quantity`, `process_step`, `process_note`, `created_at`, `updated_at`) VALUES
(1, 2, 4, '25.0000', 1, NULL, '2026-02-05 03:08:34', '2026-02-05 03:08:34'),
(2, 2, 13, '32.0000', 1, NULL, '2026-02-05 04:04:24', '2026-02-05 04:04:24'),
(3, 3, 13, '9.0000', 1, NULL, '2026-02-10 21:52:55', '2026-02-10 21:52:55'),
(4, 3, 34, '100.0000', 1, NULL, '2026-02-26 06:19:10', '2026-02-26 06:19:10'),
(5, 1, 13, '600.0000', 1, NULL, '2026-02-26 06:20:04', '2026-02-26 06:20:04');

-- --------------------------------------------------------

--
-- Table structure for table `product_price_mapping`
--

CREATE TABLE `product_price_mapping` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `price_type_id` int(10) UNSIGNED NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_price_mapping`
--

INSERT INTO `product_price_mapping` (`id`, `product_id`, `price_type_id`, `location_id`, `price`, `created_at`) VALUES
(1, 1, 1, NULL, '50.00', '2025-11-16 18:55:08'),
(3, 1, 2, NULL, '500.00', '2025-11-16 19:43:49');

-- --------------------------------------------------------

--
-- Table structure for table `product_settlement_plan`
--

CREATE TABLE `product_settlement_plan` (
  `Id` int(11) NOT NULL,
  `productId` int(10) NOT NULL,
  `bankId` int(10) NOT NULL,
  `months` int(11) NOT NULL,
  `installment` double(22,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `product_settlement_plan`
--

INSERT INTO `product_settlement_plan` (`Id`, `productId`, `bankId`, `months`, `installment`) VALUES
(1, 2410, 1, 6, 2540.00),
(2, 2410, 1, 12, 1750.00),
(3, 2410, 2, 12, 4580.00),
(4, 2410, 2, 20, 1250.00),
(16, 2130, 3, 6, 13331.00),
(25, 2799, 3, 6, 13332.00),
(21, 2130, 3, 24, 3333.00),
(22, 2139, 3, 6, 16665.00),
(24, 2139, 3, 24, 4166.00),
(23, 2139, 3, 12, 8333.00),
(19, 2130, 3, 12, 6665.00),
(26, 2799, 3, 12, 6666.00),
(27, 2799, 3, 24, 3333.00),
(28, 2216, 3, 24, 2916.00),
(29, 2216, 3, 12, 5833.00),
(30, 2216, 3, 6, 11665.00),
(31, 2426, 3, 6, 6665.00),
(32, 2426, 3, 12, 3333.00),
(33, 2426, 3, 24, 1666.00),
(34, 2204, 3, 6, 9165.00),
(35, 2204, 3, 12, 4583.00),
(36, 2204, 3, 24, 2291.00),
(37, 2827, 3, 6, 6665.00),
(38, 2827, 3, 12, 3333.00),
(39, 2827, 3, 24, 1666.00),
(40, 2432, 3, 12, 3333.00),
(41, 2432, 3, 6, 6665.00),
(42, 2432, 3, 24, 1666.00),
(43, 2444, 3, 6, 8332.00),
(44, 2444, 3, 12, 4166.00),
(45, 2444, 3, 24, 2083.00),
(46, 2790, 3, 6, 11665.00),
(47, 2790, 3, 12, 5833.00),
(48, 2790, 3, 24, 2916.00),
(49, 2801, 3, 6, 11665.00),
(50, 2801, 3, 12, 5833.00),
(51, 2801, 3, 24, 2916.00),
(52, 2804, 3, 6, 11665.00),
(53, 2804, 3, 12, 5833.00),
(54, 2804, 3, 24, 2916.00),
(55, 2321, 3, 6, 99998.00),
(56, 2321, 3, 12, 49999.00),
(57, 2321, 3, 24, 25000.00),
(58, 2321, 3, 40, 15000.00),
(59, 2172, 3, 6, 33332.00),
(60, 2172, 3, 12, 16666.00),
(61, 2172, 3, 24, 8333.00),
(62, 2172, 3, 40, 5000.00),
(63, 2161, 3, 6, 58332.00),
(64, 2161, 3, 12, 29166.00),
(65, 2161, 3, 24, 14583.00),
(66, 2161, 3, 40, 8750.00),
(67, 2160, 3, 6, 33332.00),
(68, 2160, 3, 12, 16666.00),
(69, 2160, 3, 24, 8333.00),
(70, 2160, 3, 40, 5000.00),
(71, 2441, 3, 6, 16665.00),
(72, 2441, 3, 12, 8333.00),
(73, 2441, 3, 24, 4166.00),
(74, 2442, 3, 6, 9165.00),
(75, 2442, 3, 12, 4583.00),
(76, 2442, 3, 24, 2291.00),
(77, 2447, 3, 6, 14998.00),
(78, 2447, 3, 12, 7499.00),
(79, 2447, 3, 24, 3750.00),
(80, 2451, 3, 6, 9998.00),
(81, 2451, 3, 12, 4999.00),
(82, 2451, 3, 24, 2500.00),
(83, 2448, 3, 6, 9998.00),
(84, 2448, 3, 12, 4999.00),
(85, 2448, 3, 24, 2500.00),
(86, 2443, 3, 6, 9998.00),
(87, 2443, 3, 12, 4999.00),
(88, 2443, 3, 24, 2500.00),
(89, 2224, 3, 6, 9165.00),
(90, 2224, 3, 12, 4583.00),
(91, 2224, 3, 24, 2291.00),
(93, 2156, 3, 6, 19998.00),
(95, 2156, 3, 12, 9999.00),
(96, 2156, 3, 24, 4999.00),
(98, 2159, 3, 6, 19998.00),
(99, 2159, 3, 12, 9999.00),
(100, 2159, 3, 24, 4999.00),
(101, 2169, 3, 6, 13332.00),
(102, 2169, 3, 12, 6666.00),
(103, 2169, 3, 24, 3333.00),
(104, 2170, 3, 6, 19998.00),
(105, 2170, 3, 12, 9999.00),
(106, 2170, 3, 24, 4999.00),
(107, 2140, 3, 6, 19998.00),
(109, 2140, 3, 12, 9999.00),
(111, 2140, 3, 24, 4999.00),
(112, 2140, 3, 40, 2999.00),
(113, 2141, 3, 6, 21665.00),
(115, 2141, 3, 12, 10832.00),
(116, 2141, 3, 24, 5416.00),
(125, 2142, 3, 24, 3333.00),
(119, 2141, 3, 40, 3249.00),
(124, 2142, 3, 12, 6666.00),
(123, 2142, 3, 6, 13332.00),
(126, 2145, 3, 6, 18331.00),
(127, 2145, 3, 12, 9166.00),
(128, 2145, 3, 24, 4583.00),
(129, 2145, 3, 40, 2749.00),
(130, 2147, 3, 6, 21665.00),
(131, 2147, 3, 12, 10832.00),
(132, 2147, 3, 24, 5416.00),
(133, 2147, 3, 40, 3249.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_vat_master`
--

CREATE TABLE `product_vat_master` (
  `id` int(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) NOT NULL,
  `rate` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `product_vat_master`
--

INSERT INTO `product_vat_master` (`id`, `name`, `code`, `rate`) VALUES
(1, 'No GST', 'NOGST', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_note_header`
--

CREATE TABLE `purchase_note_header` (
  `purchase_note_id` int(10) NOT NULL,
  `purchase_note_code` varchar(30) NOT NULL,
  `purchase_date` date NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `location_id` int(10) NOT NULL DEFAULT 1,
  `status` enum('OPEN','PARTIALLY_RECEIVED','COMPLETED') NOT NULL DEFAULT 'OPEN',
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `purchase_note_header`
--

INSERT INTO `purchase_note_header` (`purchase_note_id`, `purchase_note_code`, `purchase_date`, `supplier_id`, `location_id`, `status`, `created_by`, `created_at`, `remarks`) VALUES
(1, 'PN5237811', '2026-01-15', 13, 1, 'COMPLETED', '1', '2026-01-15 15:19:23', ''),
(2, 'PN8507212', '2026-01-15', 14, 1, 'PARTIALLY_RECEIVED', '1', '2026-01-15 17:00:30', 'test'),
(3, 'PN6045783', '2026-01-15', 14, 1, 'COMPLETED', '1', '2026-01-15 18:07:56', ''),
(4, 'PN7520874', '2026-01-17', 13, 1, 'PARTIALLY_RECEIVED', '1', '2026-01-17 22:34:34', 'te'),
(5, 'PN4230195', '2026-01-18', 13, 1, 'PARTIALLY_RECEIVED', '1', '2026-01-18 16:12:10', 'test'),
(6, 'PN8384216', '2026-02-05', 13, 1, 'OPEN', '1', '2026-02-05 08:06:47', '');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_note_items`
--

CREATE TABLE `purchase_note_items` (
  `purchase_note_item_id` int(10) NOT NULL,
  `purchase_note_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `requested_qty` double(20,2) NOT NULL,
  `total_received_qty` double(20,2) NOT NULL DEFAULT 0.00,
  `balance_qty` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `purchase_note_items`
--

INSERT INTO `purchase_note_items` (`purchase_note_item_id`, `purchase_note_id`, `product_id`, `requested_qty`, `total_received_qty`, `balance_qty`) VALUES
(1, 1, 2, 2.00, 2.00, 0.00),
(2, 1, 3, 2.00, 2.00, 0.00),
(3, 2, 1, 13.00, 5.00, 8.00),
(4, 3, 2, 3.00, 3.00, 0.00),
(5, 4, 1, 10.00, 9.00, 1.00),
(6, 4, 3, 15.00, 15.00, 0.00),
(7, 5, 1, 10.00, 7.00, 3.00),
(8, 5, 3, 5.00, 3.00, 2.00),
(9, 6, 4, 2.00, 0.00, 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_details`
--

CREATE TABLE `purchase_return_details` (
  `pr_d_id` int(10) NOT NULL,
  `pr_h_id` int(10) NOT NULL,
  `grn_d_id` int(10) NOT NULL,
  `item_id` int(10) NOT NULL,
  `pr_d_qty` double(20,2) NOT NULL,
  `pr_d_rate` double(20,2) NOT NULL,
  `pr_d_vat_rate` double(20,2) NOT NULL,
  `pr_d_vat` double(20,2) NOT NULL DEFAULT 0.00,
  `pr_d_total` double(20,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `purchase_return_details`
--

INSERT INTO `purchase_return_details` (`pr_d_id`, `pr_h_id`, `grn_d_id`, `item_id`, `pr_d_qty`, `pr_d_rate`, `pr_d_vat_rate`, `pr_d_vat`, `pr_d_total`) VALUES
(1, 1, 20, 2, 2.00, 5.00, 0.00, 0.00, 10.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_return_header`
--

CREATE TABLE `purchase_return_header` (
  `pr_h_id` int(10) NOT NULL,
  `pr_h_code` varchar(30) NOT NULL,
  `grn_h_id` int(10) NOT NULL,
  `supplier_id` int(10) NOT NULL,
  `location_id` int(10) NOT NULL,
  `pr_date` datetime NOT NULL,
  `pr_net` double(20,2) NOT NULL DEFAULT 0.00,
  `pr_vat` double(20,2) NOT NULL DEFAULT 0.00,
  `pr_gross` double(20,2) NOT NULL DEFAULT 0.00,
  `created_by` varchar(20) NOT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `purchase_return_header`
--

INSERT INTO `purchase_return_header` (`pr_h_id`, `pr_h_code`, `grn_h_id`, `supplier_id`, `location_id`, `pr_date`, `pr_net`, `pr_vat`, `pr_gross`, `created_by`, `remarks`) VALUES
(1, 'PRN00001', 11, 14, 1, '2026-01-18 13:26:14', 10.00, 0.00, 10.00, '1', '');

-- --------------------------------------------------------

--
-- Table structure for table `refer_email`
--

CREATE TABLE `refer_email` (
  `pk_id` int(10) NOT NULL,
  `refer_by_email` varchar(50) NOT NULL,
  `refer_To_email` varchar(50) NOT NULL,
  `coupon` varchar(50) NOT NULL,
  `add_by` int(10) NOT NULL,
  `add_name` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `repeat_units`
--

CREATE TABLE `repeat_units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `repeat_units`
--

INSERT INTO `repeat_units` (`id`, `name`, `display_name`, `created_at`) VALUES
(1, 'day', 'Day', '2025-11-22 17:22:21'),
(2, 'week', 'Week', '2025-11-22 17:22:21'),
(3, 'month', 'Month', '2025-11-22 17:22:21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` int(5) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer`, `description`, `rating`) VALUES
(1, 'Gimhani Yasodara', 'I highly recommend this page to the people in Italy. Good quality products delivering in an accurate time for a considerable amount. products are 100% original products. Thank you so much for your great service.', 5),
(2, 'Kithmi Fernando', 'Best Products.100% Original Products', 5),
(3, 'Asheni Imalsha ', 'Thank you so much\r\nI received my pack\r\nIt’s actually good and packing is superb \r\nGreat work darling \r\nKeep it up\r\n❤️❤️❤️❤️❤️❤️❤️', 5),
(4, 'Sachii Perera', 'Thank you so much 🥰🥰🥰🥰\r\nKeep it up  ❤️❤️❤️', 5);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `user_level_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`user_level_id`, `permission_id`) VALUES
(1, 8),
(1, 9),
(2, 1),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 15),
(2, 16),
(2, 17),
(2, 24),
(2, 25),
(2, 26);

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_details`
--

CREATE TABLE `sales_return_details` (
  `sales_return_d_id` int(10) NOT NULL,
  `sales_return_d_h_id` int(10) DEFAULT NULL,
  `sales_return_d_item_id` int(10) DEFAULT NULL,
  `sales_return_d_qty` double(20,2) DEFAULT NULL,
  `sales_return_d_rate` double(22,2) NOT NULL,
  `sales_return_d_vat_rate` double(22,2) NOT NULL,
  `sales_return_d_tot` double(22,2) NOT NULL,
  `sales_return_d_invoice_item` int(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sales_return_details`
--

INSERT INTO `sales_return_details` (`sales_return_d_id`, `sales_return_d_h_id`, `sales_return_d_item_id`, `sales_return_d_qty`, `sales_return_d_rate`, `sales_return_d_vat_rate`, `sales_return_d_tot`, `sales_return_d_invoice_item`) VALUES
(117, 106, 2520, 1.00, 990.00, 0.00, 990.00, 1925),
(118, 107, 2520, 1.00, 990.00, 0.00, 990.00, 1925);

-- --------------------------------------------------------

--
-- Table structure for table `sales_return_hedder`
--

CREATE TABLE `sales_return_hedder` (
  `sales_return_h_id` int(10) NOT NULL,
  `sales_return_h_code` varchar(20) DEFAULT NULL,
  `sales_return_h_invoice` int(10) DEFAULT NULL,
  `sales_retrun_h_total` double(22,2) NOT NULL,
  `sales_retrun_h_date` datetime NOT NULL DEFAULT current_timestamp(),
  `sales_return_user` int(10) DEFAULT NULL,
  `sales_return_location` int(10) DEFAULT NULL,
  `sales_return_process` int(1) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `sales_return_hedder`
--

INSERT INTO `sales_return_hedder` (`sales_return_h_id`, `sales_return_h_code`, `sales_return_h_invoice`, `sales_retrun_h_total`, `sales_retrun_h_date`, `sales_return_user`, `sales_return_location`, `sales_return_process`) VALUES
(106, 'SR0001', 221, 990.00, '2020-03-06 03:41:17', 1, 1, 1),
(107, 'SR000107', 221, 990.00, '2020-03-26 10:51:54', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_address`
--

CREATE TABLE `shipping_address` (
  `id` int(10) NOT NULL,
  `fk_customer_id` int(10) NOT NULL,
  `fk_delivery_method` int(10) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` varchar(200) NOT NULL,
  `fk_city` int(10) NOT NULL,
  `contact_no` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `shipping_address_availability`
--

CREATE TABLE `shipping_address_availability` (
  `id` int(11) NOT NULL,
  `shipping_address_id` int(11) NOT NULL,
  `mon` tinyint(1) DEFAULT 1,
  `tue` tinyint(1) DEFAULT 1,
  `wed` tinyint(1) DEFAULT 1,
  `thu` tinyint(1) DEFAULT 1,
  `fri` tinyint(1) DEFAULT 1,
  `sat` tinyint(1) DEFAULT 1,
  `sun` tinyint(1) DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `shipping_address_availability`
--

INSERT INTO `shipping_address_availability` (`id`, `shipping_address_id`, `mon`, `tue`, `wed`, `thu`, `fri`, `sat`, `sun`) VALUES
(1, 62, 0, 1, 1, 1, 1, 1, 1),
(2, 64, 1, 1, 1, 1, 1, 1, 1),
(3, 67, 1, 0, 1, 1, 1, 0, 1),
(13, 118, 0, 0, 0, 1, 1, 1, 1),
(12, 117, 0, 0, 1, 1, 1, 1, 0),
(11, 116, 0, 0, 1, 1, 1, 0, 1),
(7, 111, 0, 1, 0, 1, 1, 0, 1),
(8, 113, 0, 1, 1, 1, 1, 1, 0),
(9, 114, 0, 1, 1, 1, 0, 1, 1),
(10, 115, 0, 1, 1, 1, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_method`
--

CREATE TABLE `shipping_method` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `provider` varchar(100) DEFAULT NULL,
  `attribute1` varchar(100) NOT NULL,
  `attribute12` varchar(100) NOT NULL,
  `rate` float(10,2) NOT NULL DEFAULT 0.00,
  `active` int(1) NOT NULL DEFAULT 0,
  `DispatchType` enum('Ship','Delivery') NOT NULL DEFAULT 'Ship',
  `addShipingCharges` int(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `shipping_method`
--

INSERT INTO `shipping_method` (`id`, `title`, `provider`, `attribute1`, `attribute12`, `rate`, `active`, `DispatchType`, `addShipingCharges`) VALUES
(1, 'Standard Shipping DHL', 'DHL', '6-23 days', '', 0.00, 1, 'Ship', 1),
(2, 'Standard Delivery (3 - 5 working days)', '', '3 - 5 working days', '', 9.00, 1, 'Delivery', 1),
(3, 'Standard Shipping UPS', 'UPS', '2-5 days', '', 9.00, 0, 'Ship', 1);

-- --------------------------------------------------------

--
-- Table structure for table `site_banners`
--

CREATE TABLE `site_banners` (
  `id` int(10) NOT NULL,
  `location` int(10) DEFAULT NULL,
  `image` text DEFAULT NULL,
  `path` text DEFAULT NULL,
  `link` text DEFAULT NULL,
  `style1` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `site_banners`
--

INSERT INTO `site_banners` (`id`, `location`, `image`, `path`, `link`, `style1`) VALUES
(1, 101, '1_1.jpeg', 'images/banner', NULL, NULL),
(2, 102, '1_2.jpeg', 'images/banner', NULL, NULL),
(3, 103, 'ca916322-e87f-4920-8676-490aaee84a3e.gif', 'images/banner', 'https://grocery.morichmall.lk/', ''),
(4, 104, '2_3.jpeg', 'images/banner', NULL, NULL),
(5, 105, '2_4.jpeg', 'images/banner', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `smtp_settings`
--

CREATE TABLE `smtp_settings` (
  `id` int(11) NOT NULL,
  `smtp_host` varchar(255) NOT NULL DEFAULT '',
  `smtp_port` int(11) NOT NULL DEFAULT 587,
  `smtp_username` varchar(255) NOT NULL DEFAULT '',
  `smtp_password` varchar(255) NOT NULL DEFAULT '',
  `smtp_encryption` enum('tls','ssl','none') NOT NULL DEFAULT 'tls',
  `smtp_from_email` varchar(255) NOT NULL DEFAULT '',
  `smtp_from_name` varchar(255) NOT NULL DEFAULT '',
  `smtp_reply_to_email` varchar(255) NOT NULL DEFAULT '',
  `smtp_reply_to_name` varchar(255) NOT NULL DEFAULT '',
  `smtp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `smtp_settings`
--

INSERT INTO `smtp_settings` (`id`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`, `smtp_from_email`, `smtp_from_name`, `smtp_reply_to_email`, `smtp_reply_to_name`, `smtp_enabled`, `created_at`, `updated_at`) VALUES
(1, 'mail.stock.web.lk', 587, 'sales@stock.web.lk', 'asd123asd_', 'tls', 'sales@stock.web.lk', 'GP Bakery', '', '', 1, '2026-02-22 22:54:48', '2026-02-22 23:20:38');

-- --------------------------------------------------------

--
-- Table structure for table `standing_order`
--

CREATE TABLE `standing_order` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `shipping_address_id` int(10) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `DeliveryAmount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `RepeatInterval` int(11) DEFAULT NULL COMMENT 'e.g., 7',
  `RepeatUnit` int(11) DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `standing_order`
--

INSERT INTO `standing_order` (`id`, `customer_id`, `shipping_address_id`, `active`, `DeliveryAmount`, `created_at`, `updated_at`, `RepeatInterval`, `RepeatUnit`, `date_from`, `date_to`) VALUES
(1, 289, 63, 1, '3.00', '2025-11-28 16:10:59', '2026-02-22 23:25:11', 2, 3, '2026-02-22', '2026-04-21'),
(2, 308, 65, 1, '3.00', '2025-11-28 16:28:59', '2026-02-05 10:49:53', 12, 2, NULL, NULL),
(3, 241, 67, 1, '3.00', '2025-11-28 16:53:15', '2026-02-15 22:25:20', 2, 3, '2026-02-15', '2026-04-12'),
(4, 309, 66, 1, '3.00', '2026-02-05 10:39:30', '2026-02-05 10:39:30', 5, 3, NULL, NULL),
(5, 351, 111, 1, '3.00', '2026-02-20 02:58:18', '2026-02-20 02:58:18', NULL, NULL, '2026-02-20', '2026-02-23'),
(6, 307, 119, 1, '3.00', '2026-02-22 23:28:01', '2026-02-22 23:28:01', NULL, NULL, '2026-02-22', '2026-02-28');

-- --------------------------------------------------------

--
-- Table structure for table `standing_order_item`
--

CREATE TABLE `standing_order_item` (
  `id` int(11) NOT NULL,
  `standing_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `mon_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tue_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wed_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `thu_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fri_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sat_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sun_qty` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `standing_order_item`
--

INSERT INTO `standing_order_item` (`id`, `standing_order_id`, `item_id`, `mon_qty`, `tue_qty`, `wed_qty`, `thu_qty`, `fri_qty`, `sat_qty`, `sun_qty`, `created_at`, `updated_at`) VALUES
(37, 4, 2, '1.00', '2.00', '0.00', '3.00', '0.00', '4.00', '0.00', '2026-02-05 10:39:31', '2026-02-05 10:39:31'),
(41, 2, 2, '1.00', '2.00', '0.00', '2.00', '0.00', '2.00', '0.00', '2026-02-05 10:49:53', '2026-02-05 10:49:53'),
(42, 3, 3, '3.00', '2.00', '3.00', '4.00', '3.00', '3.00', '2.00', '2026-02-15 22:25:21', '2026-02-15 22:25:21'),
(43, 3, 2, '2.00', '3.00', '0.00', '1.00', '0.00', '1.00', '0.00', '2026-02-15 22:25:21', '2026-02-15 22:25:21'),
(44, 5, 3, '0.00', '2.00', '0.00', '3.00', '4.00', '0.00', '2.00', '2026-02-20 02:58:19', '2026-02-20 02:58:19'),
(45, 1, 3, '4.00', '2.00', '0.00', '2.00', '0.00', '0.00', '0.00', '2026-02-22 23:25:11', '2026-02-22 23:25:11'),
(46, 1, 16, '3.00', '4.00', '3.00', '4.00', '2.00', '2.00', '0.00', '2026-02-22 23:25:11', '2026-02-22 23:25:11'),
(47, 6, 2, '3.00', '2.00', '0.00', '4.00', '0.00', '2.00', '0.00', '2026-02-22 23:28:01', '2026-02-22 23:28:01'),
(48, 6, 1, '4.00', '0.00', '5.00', '0.00', '2.00', '0.00', '0.00', '2026-02-22 23:28:01', '2026-02-22 23:28:01');

-- --------------------------------------------------------

--
-- Table structure for table `stock_issue_expected_products`
--

CREATE TABLE `stock_issue_expected_products` (
  `id` int(10) NOT NULL,
  `issue_id` int(10) NOT NULL COMMENT 'FK ??? stock_issue_header.issue_id',
  `product_id` int(10) NOT NULL COMMENT 'FK ??? item_master.item_id (finished product)',
  `expected_qty` double(20,2) NOT NULL DEFAULT 0.00,
  `received_qty` double(20,2) NOT NULL DEFAULT 0.00,
  `status` enum('PENDING','PARTIALLY_RECEIVED','COMPLETED') NOT NULL DEFAULT 'PENDING'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `stock_issue_expected_products`
--

INSERT INTO `stock_issue_expected_products` (`id`, `issue_id`, `product_id`, `expected_qty`, `received_qty`, `status`) VALUES
(1, 3, 3, 5.00, 0.00, 'PENDING'),
(2, 4, 3, 3.00, 0.00, 'PENDING'),
(3, 5, 3, 2.00, 0.00, 'PENDING'),
(4, 6, 3, 2.00, 0.00, 'PENDING');

-- --------------------------------------------------------

--
-- Table structure for table `stock_issue_header`
--

CREATE TABLE `stock_issue_header` (
  `issue_id` int(10) NOT NULL,
  `issue_code` varchar(30) NOT NULL,
  `issue_date` date NOT NULL,
  `location_id` int(10) NOT NULL,
  `to_location_id` int(10) DEFAULT NULL COMMENT 'Destination location for finished products',
  `issued_to` varchar(100) DEFAULT NULL,
  `status` enum('ISSUED','CANCELLED') NOT NULL DEFAULT 'ISSUED',
  `production_status` enum('PENDING','PARTIALLY_RECEIVED','COMPLETED') DEFAULT NULL COMMENT 'NULL = no expected products, PENDING = awaiting finished goods',
  `remarks` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `stock_issue_header`
--

INSERT INTO `stock_issue_header` (`issue_id`, `issue_code`, `issue_date`, `location_id`, `to_location_id`, `issued_to`, `status`, `production_status`, `remarks`, `created_by`, `created_at`) VALUES
(1, 'SI7622491', '2026-01-16', 5, NULL, 'Kitchen', 'ISSUED', NULL, '', '8', '2026-01-16 07:45:54'),
(2, 'SI5439422', '2026-01-17', 1, NULL, 'Kitchen', 'ISSUED', NULL, 'test', '1', '2026-01-17 23:15:31'),
(3, 'SI3265433', '2026-02-15', 1, 1, 'Production Used', 'ISSUED', 'PENDING', '', '1', '2026-02-15 23:35:04'),
(4, 'SI5220204', '2026-02-15', 1, 1, 'Production Used', 'ISSUED', 'PENDING', '', '1', '2026-02-15 23:35:47'),
(5, 'SI4406705', '2026-02-15', 1, 1, 'Production Used', 'ISSUED', 'PENDING', '', '1', '2026-02-15 23:38:58'),
(6, 'SI1529336', '2026-02-15', 1, 1, 'Production Used', 'ISSUED', 'PENDING', '', '1', '2026-02-15 23:40:58');

-- --------------------------------------------------------

--
-- Table structure for table `stock_issue_items`
--

CREATE TABLE `stock_issue_items` (
  `issue_item_id` int(10) NOT NULL,
  `issue_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `qty` double(20,2) NOT NULL,
  `rate` double(20,2) NOT NULL DEFAULT 0.00,
  `total` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `stock_issue_items`
--

INSERT INTO `stock_issue_items` (`issue_item_id`, `issue_id`, `product_id`, `qty`, `rate`, `total`) VALUES
(1, 1, 2, 5.00, 5.00, 25.00),
(2, 2, 3, 3.00, 1.00, 3.00),
(3, 3, 2, 1.00, 5.00, 5.00),
(4, 4, 1, 2.00, 1.00, 2.00),
(5, 5, 1, 3.00, 1.00, 3.00),
(6, 6, 1, 1.00, 1.00, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_header`
--

CREATE TABLE `stock_transfer_header` (
  `transfer_id` int(10) NOT NULL,
  `transfer_code` varchar(30) NOT NULL,
  `transfer_date` date NOT NULL,
  `from_location_id` int(10) NOT NULL,
  `to_location_id` int(10) NOT NULL,
  `status` enum('PENDING','COMPLETED','CANCELLED') NOT NULL DEFAULT 'COMPLETED',
  `remarks` text DEFAULT NULL,
  `created_by` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `stock_transfer_header`
--

INSERT INTO `stock_transfer_header` (`transfer_id`, `transfer_code`, `transfer_date`, `from_location_id`, `to_location_id`, `status`, `remarks`, `created_by`, `created_at`) VALUES
(1, 'ST2361171', '2026-01-15', 1, 5, 'COMPLETED', 'test', '1', '2026-01-15 20:34:51'),
(2, 'ST1486682', '2026-01-17', 1, 5, 'COMPLETED', 'test', '1', '2026-01-17 22:50:00'),
(3, 'ST3606753', '2026-01-18', 1, 5, 'COMPLETED', 'test', '1', '2026-01-18 12:25:07'),
(4, 'ST6134464', '2026-01-18', 1, 5, 'COMPLETED', 'test', '1', '2026-01-18 12:27:17'),
(5, 'ST2970045', '2026-01-18', 1, 5, 'PENDING', 'test Remarks', '1', '2026-01-18 16:17:14'),
(6, 'ST1001556', '2026-02-04', 1, 5, 'PENDING', 'ssa', '1', '2026-02-04 15:35:20'),
(7, 'ST1362047', '2026-02-05', 1, 5, 'COMPLETED', '', '1', '2026-02-05 08:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transfer_items`
--

CREATE TABLE `stock_transfer_items` (
  `transfer_item_id` int(10) NOT NULL,
  `transfer_id` int(10) NOT NULL,
  `product_id` int(10) NOT NULL,
  `qty` double(20,2) NOT NULL,
  `rate` double(20,2) NOT NULL DEFAULT 0.00,
  `total` double(20,2) NOT NULL DEFAULT 0.00
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `stock_transfer_items`
--

INSERT INTO `stock_transfer_items` (`transfer_item_id`, `transfer_id`, `product_id`, `qty`, `rate`, `total`) VALUES
(1, 1, 2, 10.00, 5.00, 50.00),
(2, 2, 3, 3.00, 1.00, 3.00),
(3, 3, 1, 4.00, 1.00, 4.00),
(4, 4, 1, 3.00, 1.00, 3.00),
(5, 5, 3, 5.00, 1.00, 5.00),
(6, 5, 1, 2.00, 1.00, 2.00),
(7, 6, 3, 1.00, 1.00, 1.00),
(8, 7, 3, 2.00, 1.00, 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `subcribers`
--

CREATE TABLE `subcribers` (
  `id` int(10) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(10) NOT NULL,
  `supplier_code` varchar(30) DEFAULT NULL,
  `supplier_name` varchar(50) DEFAULT NULL,
  `legal_name` varchar(100) DEFAULT NULL,
  `trading_name` varchar(100) DEFAULT NULL,
  `supplier_address` text DEFAULT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `supplier_contact_person` varchar(50) DEFAULT NULL,
  `supplier_email` varchar(60) DEFAULT NULL,
  `supplier_contact_no` varchar(11) DEFAULT NULL,
  `supplier_mobile` varchar(20) DEFAULT NULL,
  `supplier_note` text DEFAULT NULL,
  `supplier_remarks` text DEFAULT NULL,
  `supplier_outstanding_balance` double(20,2) DEFAULT 0.00,
  `credit_limit` double(20,2) DEFAULT 0.00,
  `abn_no` varchar(20) DEFAULT NULL,
  `acn_no` varchar(20) DEFAULT NULL,
  `vat_registered` tinyint(1) DEFAULT 0,
  `gst_no` varchar(20) DEFAULT NULL,
  `payment_terms_id` int(11) DEFAULT NULL,
  `supplier_price_type_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `locked` tinyint(1) DEFAULT 0,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_email` varchar(100) DEFAULT NULL,
  `emergency_contact_telephone` varchar(20) DEFAULT NULL,
  `custom_url_link` varchar(255) DEFAULT NULL,
  `google_map_link` varchar(255) DEFAULT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_telephone` varchar(20) DEFAULT NULL,
  `account_hold` tinyint(1) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `supplier_code`, `supplier_name`, `legal_name`, `trading_name`, `supplier_address`, `address_line_1`, `address_line_2`, `city`, `postal_code`, `supplier_contact_person`, `supplier_email`, `supplier_contact_no`, `supplier_mobile`, `supplier_note`, `supplier_remarks`, `supplier_outstanding_balance`, `credit_limit`, `abn_no`, `acn_no`, `vat_registered`, `gst_no`, `payment_terms_id`, `supplier_price_type_id`, `is_active`, `locked`, `min_order_amount`, `emergency_contact_name`, `emergency_contact_email`, `emergency_contact_telephone`, `custom_url_link`, `google_map_link`, `contact_name`, `contact_email`, `contact_telephone`, `account_hold`) VALUES
(13, NULL, 'Lee fast elivery', NULL, NULL, 'Monash rescent', NULL, NULL, NULL, NULL, 'Bret ee', 'Bret@Lee.com', '0759988300', NULL, 'Delivery very eek', NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(14, NULL, 'Slow pin eliveries', NULL, NULL, 'Upper erntree ully, ictoria, ustralia', NULL, NULL, NULL, NULL, 'Shane arne', 'ShaneW@warneCompany.com', '0765643532', NULL, 'Googley in ayments', NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(17, 'SUPP-00015', 'Lankagreenhost', 'Lankagreenhost', NULL, '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, 'Malith Sachinthana', NULL, NULL, NULL, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', 0),
(18, 'SUPP-00018', 'Lankagreenhost', 'Lankagreenhost', NULL, '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', NULL, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', 0),
(19, 'SUPP-00019', 'Lankagreenhost', NULL, NULL, '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, 'Malith Sachinthana', NULL, NULL, NULL, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', 0),
(20, 'SUPP-00020', 'Lankagreenhost', NULL, NULL, '375/12 Rathnarama Road Hokandara North', '375/12 Rathnarama Road Hokandara North', NULL, 'Hokandara', '10118', 'Malith Sachinthana', 'lankagreenhost99s@gmail.com', '0771998880', '0771998880', NULL, NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, 'Malith Sachinthana', NULL, NULL, NULL, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', 0),
(21, 'SUPP-00021', 'Lankagreenhost', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Malith Sachinthana', 'lankagreenhost99@gmail.com', '0771998880', NULL, NULL, NULL, 0.00, 0.00, NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL, NULL, NULL, NULL, NULL, NULL, 'Malith Sachinthana', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_balance`
--

CREATE TABLE `supplier_balance` (
  `id` int(10) NOT NULL,
  `code` text NOT NULL,
  `grn_h_id` int(10) DEFAULT NULL,
  `amount` double(20,2) DEFAULT 0.00,
  `amountDate` date NOT NULL,
  `method` varchar(30) NOT NULL,
  `makeBy` int(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `supplier_balance`
--

INSERT INTO `supplier_balance` (`id`, `code`, `grn_h_id`, `amount`, `amountDate`, `method`, `makeBy`) VALUES
(45, 'er33', 710, 4662200.00, '2020-02-06', 'Cash Payment', 1);

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payment_options`
--

CREATE TABLE `supplier_payment_options` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_type` varchar(20) NOT NULL,
  `card_no` varchar(50) DEFAULT NULL,
  `card_name` varchar(100) DEFAULT NULL,
  `exp_month` int(11) DEFAULT NULL,
  `exp_year` int(11) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `account_no` varchar(50) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_shipping_address`
--

CREATE TABLE `supplier_shipping_address` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `address_label` varchar(100) DEFAULT NULL,
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `attribute_1` varchar(100) DEFAULT NULL,
  `attribute_2` varchar(100) DEFAULT NULL,
  `attribute_3` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `contact_person_name` varchar(100) DEFAULT NULL,
  `contact_person_phone` varchar(20) DEFAULT NULL,
  `contact_person_email` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `note_to_deliver` text DEFAULT NULL,
  `delivery_time_from` time DEFAULT NULL,
  `delivery_time_till` time DEFAULT NULL,
  `delivery_route_id` int(11) DEFAULT NULL,
  `delivery_start_time` varchar(10) DEFAULT NULL,
  `delivery_end_time` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `supplier_shipping_address`
--

INSERT INTO `supplier_shipping_address` (`id`, `supplier_id`, `address_label`, `address_line_1`, `address_line_2`, `city`, `postal_code`, `contact_no`, `attribute_1`, `attribute_2`, `attribute_3`, `is_default`, `contact_person_name`, `contact_person_phone`, `contact_person_email`, `remarks`, `note_to_deliver`, `delivery_time_from`, `delivery_time_till`, `delivery_route_id`, `delivery_start_time`, `delivery_end_time`) VALUES
(1, 13, 'Main Warehouse', '123 Industrial Road', 'Suite 456', 'Business District', '12345', NULL, NULL, NULL, NULL, 1, 'John Smith', '+1-555-0123', NULL, NULL, 'Ring doorbell and wait for pickup', NULL, NULL, NULL, NULL, NULL),
(2, 14, 'Secondary Location', '456 Commerce Street', '', 'Downtown', '67890', NULL, NULL, NULL, NULL, 1, 'Jane Doe', '+1-555-0456', NULL, NULL, 'Call ahead for delivery', NULL, NULL, NULL, NULL, NULL),
(3, 20, 'Primary', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 21, 'Primary', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `temp_cart`
--

CREATE TABLE `temp_cart` (
  `cart_h_pk_id` int(10) NOT NULL,
  `temp_cart_code` int(10) NOT NULL,
  `cart_h_customer_id` int(10) NOT NULL,
  `cart_h_date` date NOT NULL,
  `cart_h_datetime` datetime NOT NULL,
  `cart_h_location` int(10) NOT NULL,
  `cart_h_delivery_city` int(10) NOT NULL,
  `cart_h_delivery_cost` double(22,2) NOT NULL,
  `cart_h_delivery_mode` int(10) NOT NULL,
  `temp_cart_discount_type` int(11) NOT NULL,
  `temp_cart_discount_rate` double(22,2) NOT NULL,
  `cart_h_pay_type` int(10) NOT NULL,
  `cart_h_net_value` double(22,2) NOT NULL,
  `cart_h_vat_value` double(22,2) NOT NULL,
  `cart_h_gross_value` double(22,2) NOT NULL,
  `cart_h_check_Ref` text NOT NULL,
  `cart_h_card_Ref` text NOT NULL,
  `cart_h_order_note` text NOT NULL,
  `cart_h_delivery_name` varchar(100) NOT NULL,
  `cart_h_delivery_address` varchar(500) NOT NULL,
  `cart_h_delivery_contact_no` varchar(15) NOT NULL,
  `cart_h_delivery_date` date NOT NULL,
  `cart_h_delivery_time` varchar(50) NOT NULL,
  `cart_h_status` int(2) NOT NULL,
  `cart_h_add_by` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `temp_cart_details`
--

CREATE TABLE `temp_cart_details` (
  `temp_car_d_id` int(10) NOT NULL,
  `temp_car_h_id` int(10) NOT NULL,
  `temp_car_d_item_id` int(10) NOT NULL,
  `temp_car_d_qty` double(22,2) NOT NULL,
  `temp_car_d_balance` double(22,2) NOT NULL,
  `temp_car_d_item_price` double(22,2) NOT NULL,
  `temp_car_d_vat` varchar(1) NOT NULL DEFAULT 'N',
  `temp_car_d_vat_value` double(22,2) NOT NULL DEFAULT 0.00,
  `temp_car_d_item_code` varchar(100) NOT NULL DEFAULT '0.00',
  `temp_car_d_discount_type` double(22,2) NOT NULL DEFAULT 0.00,
  `temp_car_d_discount_total` double(22,2) NOT NULL DEFAULT 0.00,
  `temp_car_d_item_total` double(22,2) NOT NULL DEFAULT 0.00,
  `temp_car_d_warranty_month` int(10) NOT NULL,
  `temp_car_d_sales_rap` int(10) NOT NULL,
  `temp_car_d_order_note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `id` int(10) NOT NULL,
  `name` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`id`, `name`) VALUES
(1, 'GRN'),
(2, 'INV'),
(6, 'R-INV');

-- --------------------------------------------------------

--
-- Table structure for table `typemappingitem`
--

CREATE TABLE `typemappingitem` (
  `Id` int(10) NOT NULL,
  `ItemId` int(10) NOT NULL DEFAULT 0,
  `TypeId` int(10) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `type_master`
--

CREATE TABLE `type_master` (
  `type_id` int(10) NOT NULL,
  `clean_url` varchar(50) NOT NULL,
  `group_id` int(10) DEFAULT NULL,
  `type_name` varchar(50) NOT NULL,
  `type_discription` text DEFAULT NULL,
  `image` text DEFAULT NULL,
  `website_status` enum('N','Y') NOT NULL DEFAULT 'Y'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `type_master`
--

INSERT INTO `type_master` (`type_id`, `clean_url`, `group_id`, `type_name`, `type_discription`, `image`, `website_status`) VALUES
(1, 'Face-care--1', 1, 'Breads', NULL, 'biscuits.png', 'Y'),
(2, 'Body-Care--2', 1, 'Cakes & Cupcakes', NULL, 'cupcakes.png', 'Y'),
(3, 'Hair-Care--3', 1, 'Cookies & Biscuits', NULL, 'bread.png', 'Y'),
(5, 'Sun--Tan--5', 1, 'Pastries & Croissants', NULL, NULL, 'Y'),
(13, '', 2, 'Raw materials', NULL, NULL, 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `url`
--

CREATE TABLE `url` (
  `id` int(10) NOT NULL,
  `url` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `url`
--

INSERT INTO `url` (`id`, `url`) VALUES
(1, 'http://localhost/bakery/');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `user_level` int(10) DEFAULT NULL,
  `activated` enum('N','Y') NOT NULL DEFAULT 'N',
  `locked` enum('Y','N') DEFAULT 'N',
  `profile` text DEFAULT NULL,
  `location_status` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userid`, `username`, `password`, `first_name`, `last_name`, `email`, `user_level`, `activated`, `locked`, `profile`, `location_status`) VALUES
(1, 'admin', 'admin', 'Malith', '', 'malith.sachinthana@gmail.com', 1, 'Y', 'N', NULL, 1),
(8, 'malith', 'malith', 'Malith', '', 'malith.sachinthana@gmail.com', 2, 'Y', 'N', NULL, 5);

-- --------------------------------------------------------

--
-- Table structure for table `user_levels`
--

CREATE TABLE `user_levels` (
  `user_level_id` int(10) NOT NULL,
  `user_level_name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_levels`
--

INSERT INTO `user_levels` (`user_level_id`, `user_level_name`) VALUES
(1, 'supper Admin'),
(2, 'Staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_shipping_address`
--
ALTER TABLE `backup_shipping_address`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bakup_customer`
--
ALTER TABLE `bakup_customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categorymappingitem`
--
ALTER TABLE `categorymappingitem`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `category_master`
--
ALTER TABLE `category_master`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `city_master`
--
ALTER TABLE `city_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comapny_message`
--
ALTER TABLE `comapny_message`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`country_id`);

--
-- Indexes for table `country`
--
ALTER TABLE `country`
  ADD PRIMARY KEY (`pk_id`);

--
-- Indexes for table `coupon_codes`
--
ALTER TABLE `coupon_codes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `crm_category_master`
--
ALTER TABLE `crm_category_master`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_crm_segment_category` (`segment_id`,`category_name`),
  ADD KEY `idx_crm_category_segment` (`segment_id`);

--
-- Indexes for table `crm_company_master`
--
ALTER TABLE `crm_company_master`
  ADD PRIMARY KEY (`company_id`),
  ADD UNIQUE KEY `uq_crm_company_code` (`company_code`);

--
-- Indexes for table `crm_company_person`
--
ALTER TABLE `crm_company_person`
  ADD PRIMARY KEY (`company_person_id`),
  ADD UNIQUE KEY `uq_crm_company_person` (`company_id`,`person_id`),
  ADD KEY `idx_crm_company_person_person` (`person_id`);

--
-- Indexes for table `crm_designation_master`
--
ALTER TABLE `crm_designation_master`
  ADD PRIMARY KEY (`designation_id`),
  ADD UNIQUE KEY `uq_crm_designation_name` (`designation_name`);

--
-- Indexes for table `crm_opportunity`
--
ALTER TABLE `crm_opportunity`
  ADD PRIMARY KEY (`opportunity_id`),
  ADD UNIQUE KEY `uq_crm_opportunity_code` (`opportunity_code`),
  ADD KEY `idx_crm_opportunity_person` (`person_id`),
  ADD KEY `idx_crm_opportunity_company` (`company_id`),
  ADD KEY `idx_crm_opportunity_cycle` (`sales_cycle_id`),
  ADD KEY `idx_crm_opportunity_segment` (`segment_id`),
  ADD KEY `idx_crm_opportunity_sales_person` (`sales_person_id`);

--
-- Indexes for table `crm_opportunity_update`
--
ALTER TABLE `crm_opportunity_update`
  ADD PRIMARY KEY (`opportunity_update_id`),
  ADD KEY `idx_crm_opportunity_update_opportunity` (`opportunity_id`),
  ADD KEY `idx_crm_opportunity_update_stage` (`sales_cycle_stage_id`);

--
-- Indexes for table `crm_person_master`
--
ALTER TABLE `crm_person_master`
  ADD PRIMARY KEY (`person_id`),
  ADD UNIQUE KEY `uq_crm_person_code` (`person_code`);

--
-- Indexes for table `crm_sales_cycle_master`
--
ALTER TABLE `crm_sales_cycle_master`
  ADD PRIMARY KEY (`sales_cycle_id`),
  ADD UNIQUE KEY `uq_crm_sales_cycle_code` (`cycle_code`);

--
-- Indexes for table `crm_sales_cycle_stage`
--
ALTER TABLE `crm_sales_cycle_stage`
  ADD PRIMARY KEY (`sales_cycle_stage_id`),
  ADD UNIQUE KEY `uq_crm_sales_cycle_stage` (`sales_cycle_id`,`stage_no`),
  ADD KEY `idx_crm_sales_cycle_stage_cycle` (`sales_cycle_id`);

--
-- Indexes for table `crm_sales_person_master`
--
ALTER TABLE `crm_sales_person_master`
  ADD PRIMARY KEY (`sales_person_id`),
  ADD UNIQUE KEY `uq_crm_sales_person_name` (`sales_person_name`);

--
-- Indexes for table `crm_segment_master`
--
ALTER TABLE `crm_segment_master`
  ADD PRIMARY KEY (`segment_id`),
  ADD UNIQUE KEY `uq_crm_segment_name` (`segment_name`);

--
-- Indexes for table `currency`
--
ALTER TABLE `currency`
  ADD PRIMARY KEY (`currency_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `idx_customer_code` (`customer_code`),
  ADD KEY `fk_customer_repeat_unit` (`RepeatUnit`);

--
-- Indexes for table `customer_attachments`
--
ALTER TABLE `customer_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customer_balance`
--
ALTER TABLE `customer_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_h_id` (`invoice_h_id`);

--
-- Indexes for table `customer_payment_options`
--
ALTER TABLE `customer_payment_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customer_shipping_address`
--
ALTER TABLE `customer_shipping_address`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_shipping_customer` (`customer_id`),
  ADD KEY `fk_delivery_route` (`delivery_route_id`);

--
-- Indexes for table `custompage`
--
ALTER TABLE `custompage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_area`
--
ALTER TABLE `delivery_area`
  ADD PRIMARY KEY (`pk_id`);

--
-- Indexes for table `delivery_master`
--
ALTER TABLE `delivery_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_route_master`
--
ALTER TABLE `delivery_route_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_route_name` (`route_name`);

--
-- Indexes for table `discount_type`
--
ALTER TABLE `discount_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_reference` (`template_type`,`reference_id`),
  ADD KEY `idx_email_date` (`sent_at`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `fifo`
--
ALTER TABLE `fifo`
  ADD PRIMARY KEY (`ft_id`),
  ADD KEY `ft_item` (`ft_item`),
  ADD KEY `ft_type` (`ft_type`);

--
-- Indexes for table `front_web_settings`
--
ALTER TABLE `front_web_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `general_settings`
--
ALTER TABLE `general_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods`
--
ALTER TABLE `goods`
  ADD PRIMARY KEY (`goods_id`),
  ADD KEY `goods_item` (`goods_item`),
  ADD KEY `goods_grn` (`goods_grn`),
  ADD KEY `goods_invoice` (`goods_invoice`),
  ADD KEY `GOODS_status` (`GOODS_status`),
  ADD KEY `goods_location` (`goods_location`);

--
-- Indexes for table `gorup_master`
--
ALTER TABLE `gorup_master`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `grn_details`
--
ALTER TABLE `grn_details`
  ADD PRIMARY KEY (`grn_d_id`),
  ADD KEY `grn_h_id` (`grn_h_id`),
  ADD KEY `grn_d_item_id` (`grn_d_item_id`),
  ADD KEY `purchase_note_item_id` (`purchase_note_item_id`);

--
-- Indexes for table `grn_hedder`
--
ALTER TABLE `grn_hedder`
  ADD PRIMARY KEY (`grn_h_id`),
  ADD KEY `purchase_note_id` (`purchase_note_id`);

--
-- Indexes for table `groupmappingitem`
--
ALTER TABLE `groupmappingitem`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `hampers`
--
ALTER TABLE `hampers`
  ADD PRIMARY KEY (`pk_id`);

--
-- Indexes for table `home_slider`
--
ALTER TABLE `home_slider`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `immediatepickup`
--
ALTER TABLE `immediatepickup`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `invoice_details`
--
ALTER TABLE `invoice_details`
  ADD PRIMARY KEY (`invoice_d_id`),
  ADD KEY `invoice_h_id` (`invoice_h_id`),
  ADD KEY `invoice_d_item_id` (`invoice_d_item_id`),
  ADD KEY `invoice_d_warranty_month` (`invoice_d_warranty_month`),
  ADD KEY `invoice_d_sales_rap` (`invoice_d_sales_rap`);

--
-- Indexes for table `invoice_hedder`
--
ALTER TABLE `invoice_hedder`
  ADD PRIMARY KEY (`invoice_h_id`),
  ADD KEY `invoice_h_customer_id` (`invoice_h_customer_id`),
  ADD KEY `invoice_h_location` (`invoice_h_location`);

--
-- Indexes for table `invoice_item_details`
--
ALTER TABLE `invoice_item_details`
  ADD PRIMARY KEY (`invoice_d_id`),
  ADD KEY `invoice_h_id` (`invoice_h_id`),
  ADD KEY `invoice_d_item` (`invoice_d_item`),
  ADD KEY `invoice_d_discount_type` (`invoice_d_discount_type`);

--
-- Indexes for table `invoice_settings`
--
ALTER TABLE `invoice_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoice_status`
--
ALTER TABLE `invoice_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `itemmapping`
--
ALTER TABLE `itemmapping`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `item_master`
--
ALTER TABLE `item_master`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `item_group` (`item_group`),
  ADD KEY `item_type` (`item_type`),
  ADD KEY `item_category` (`item_category`),
  ADD KEY `item_uom` (`item_uom`);

--
-- Indexes for table `item_specification`
--
ALTER TABLE `item_specification`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `item_uom`
--
ALTER TABLE `item_uom`
  ADD PRIMARY KEY (`uom_id`);

--
-- Indexes for table `item_warranty`
--
ALTER TABLE `item_warranty`
  ADD PRIMARY KEY (`warranty_id`);

--
-- Indexes for table `location_master`
--
ALTER TABLE `location_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `location_code` (`location_code`);

--
-- Indexes for table `over_head_master`
--
ALTER TABLE `over_head_master`
  ADD PRIMARY KEY (`over_head_id`);

--
-- Indexes for table `payments_in_delivery`
--
ALTER TABLE `payments_in_delivery`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_terms`
--
ALTER TABLE `payment_terms`
  ADD PRIMARY KEY (`payment_terms_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `price_type`
--
ALTER TABLE `price_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `price_type_customer_mapping`
--
ALTER TABLE `price_type_customer_mapping`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_price_type_customer` (`price_type_id`,`customer_id`),
  ADD KEY `idx_price_type` (`price_type_id`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indexes for table `productimages`
--
ALTER TABLE `productimages`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `product_availability`
--
ALTER TABLE `product_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product` (`product_id`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_ingredient` (`product_id`,`ingredient_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_ingredient` (`ingredient_id`);

--
-- Indexes for table `product_price_mapping`
--
ALTER TABLE `product_price_mapping`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_price_type_location` (`product_id`,`price_type_id`,`location_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_price_type` (`price_type_id`),
  ADD KEY `idx_location` (`location_id`);

--
-- Indexes for table `product_settlement_plan`
--
ALTER TABLE `product_settlement_plan`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `product_vat_master`
--
ALTER TABLE `product_vat_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_note_header`
--
ALTER TABLE `purchase_note_header`
  ADD PRIMARY KEY (`purchase_note_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `purchase_note_items`
--
ALTER TABLE `purchase_note_items`
  ADD PRIMARY KEY (`purchase_note_item_id`),
  ADD KEY `purchase_note_id` (`purchase_note_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `purchase_return_details`
--
ALTER TABLE `purchase_return_details`
  ADD PRIMARY KEY (`pr_d_id`),
  ADD KEY `pr_h_id` (`pr_h_id`),
  ADD KEY `grn_d_id` (`grn_d_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `purchase_return_header`
--
ALTER TABLE `purchase_return_header`
  ADD PRIMARY KEY (`pr_h_id`),
  ADD KEY `grn_h_id` (`grn_h_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `refer_email`
--
ALTER TABLE `refer_email`
  ADD PRIMARY KEY (`pk_id`);

--
-- Indexes for table `repeat_units`
--
ALTER TABLE `repeat_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_name` (`name`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`user_level_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `sales_return_details`
--
ALTER TABLE `sales_return_details`
  ADD PRIMARY KEY (`sales_return_d_id`),
  ADD KEY `sales_return_d_h_id` (`sales_return_d_h_id`),
  ADD KEY `sales_return_d_item_id` (`sales_return_d_item_id`),
  ADD KEY `sales_return_d_invoice_item` (`sales_return_d_invoice_item`);

--
-- Indexes for table `sales_return_hedder`
--
ALTER TABLE `sales_return_hedder`
  ADD PRIMARY KEY (`sales_return_h_id`),
  ADD KEY `sales_return_h_code` (`sales_return_h_code`),
  ADD KEY `sales_return_user` (`sales_return_user`),
  ADD KEY `sales_return_location` (`sales_return_location`);

--
-- Indexes for table `shipping_address`
--
ALTER TABLE `shipping_address`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipping_address_availability`
--
ALTER TABLE `shipping_address_availability`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shipping_method`
--
ALTER TABLE `shipping_method`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_banners`
--
ALTER TABLE `site_banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `smtp_settings`
--
ALTER TABLE `smtp_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `standing_order`
--
ALTER TABLE `standing_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_so_customer` (`customer_id`),
  ADD KEY `fk_standing_order_repeat_unit` (`RepeatUnit`),
  ADD KEY `idx_so_shipping_address` (`shipping_address_id`);

--
-- Indexes for table `standing_order_item`
--
ALTER TABLE `standing_order_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_soi_soid` (`standing_order_id`),
  ADD KEY `idx_soi_item` (`item_id`);

--
-- Indexes for table `stock_issue_expected_products`
--
ALTER TABLE `stock_issue_expected_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_issue_id` (`issue_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `stock_issue_header`
--
ALTER TABLE `stock_issue_header`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `stock_issue_items`
--
ALTER TABLE `stock_issue_items`
  ADD PRIMARY KEY (`issue_item_id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `stock_transfer_header`
--
ALTER TABLE `stock_transfer_header`
  ADD PRIMARY KEY (`transfer_id`),
  ADD KEY `from_location_id` (`from_location_id`),
  ADD KEY `to_location_id` (`to_location_id`);

--
-- Indexes for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  ADD PRIMARY KEY (`transfer_item_id`),
  ADD KEY `transfer_id` (`transfer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `subcribers`
--
ALTER TABLE `subcribers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `supplier_balance`
--
ALTER TABLE `supplier_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grn_h_id` (`grn_h_id`);

--
-- Indexes for table `supplier_payment_options`
--
ALTER TABLE `supplier_payment_options`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_shipping_address`
--
ALTER TABLE `supplier_shipping_address`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temp_cart`
--
ALTER TABLE `temp_cart`
  ADD PRIMARY KEY (`cart_h_pk_id`);

--
-- Indexes for table `temp_cart_details`
--
ALTER TABLE `temp_cart_details`
  ADD PRIMARY KEY (`temp_car_d_id`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `typemappingitem`
--
ALTER TABLE `typemappingitem`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `type_master`
--
ALTER TABLE `type_master`
  ADD PRIMARY KEY (`type_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `url`
--
ALTER TABLE `url`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`);

--
-- Indexes for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD PRIMARY KEY (`user_level_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_shipping_address`
--
ALTER TABLE `backup_shipping_address`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bakup_customer`
--
ALTER TABLE `bakup_customer`
  MODIFY `customer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `banks`
--
ALTER TABLE `banks`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categorymappingitem`
--
ALTER TABLE `categorymappingitem`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `category_master`
--
ALTER TABLE `category_master`
  MODIFY `category_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `city_master`
--
ALTER TABLE `city_master`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1846;

--
-- AUTO_INCREMENT for table `comapny_message`
--
ALTER TABLE `comapny_message`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `country_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `country`
--
ALTER TABLE `country`
  MODIFY `pk_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `coupon_codes`
--
ALTER TABLE `coupon_codes`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `crm_category_master`
--
ALTER TABLE `crm_category_master`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_company_master`
--
ALTER TABLE `crm_company_master`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crm_company_person`
--
ALTER TABLE `crm_company_person`
  MODIFY `company_person_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crm_designation_master`
--
ALTER TABLE `crm_designation_master`
  MODIFY `designation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crm_opportunity`
--
ALTER TABLE `crm_opportunity`
  MODIFY `opportunity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crm_opportunity_update`
--
ALTER TABLE `crm_opportunity_update`
  MODIFY `opportunity_update_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crm_person_master`
--
ALTER TABLE `crm_person_master`
  MODIFY `person_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crm_sales_cycle_master`
--
ALTER TABLE `crm_sales_cycle_master`
  MODIFY `sales_cycle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crm_sales_cycle_stage`
--
ALTER TABLE `crm_sales_cycle_stage`
  MODIFY `sales_cycle_stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crm_sales_person_master`
--
ALTER TABLE `crm_sales_person_master`
  MODIFY `sales_person_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `crm_segment_master`
--
ALTER TABLE `crm_segment_master`
  MODIFY `segment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currency`
--
ALTER TABLE `currency`
  MODIFY `currency_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=361;

--
-- AUTO_INCREMENT for table `customer_attachments`
--
ALTER TABLE `customer_attachments`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `customer_balance`
--
ALTER TABLE `customer_balance`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_payment_options`
--
ALTER TABLE `customer_payment_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_shipping_address`
--
ALTER TABLE `customer_shipping_address`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `custompage`
--
ALTER TABLE `custompage`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery_area`
--
ALTER TABLE `delivery_area`
  MODIFY `pk_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `delivery_master`
--
ALTER TABLE `delivery_master`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `delivery_route_master`
--
ALTER TABLE `delivery_route_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `discount_type`
--
ALTER TABLE `discount_type`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employee_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fifo`
--
ALTER TABLE `fifo`
  MODIFY `ft_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `general_settings`
--
ALTER TABLE `general_settings`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `goods`
--
ALTER TABLE `goods`
  MODIFY `goods_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `gorup_master`
--
ALTER TABLE `gorup_master`
  MODIFY `group_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grn_details`
--
ALTER TABLE `grn_details`
  MODIFY `grn_d_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `grn_hedder`
--
ALTER TABLE `grn_hedder`
  MODIFY `grn_h_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `groupmappingitem`
--
ALTER TABLE `groupmappingitem`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hampers`
--
ALTER TABLE `hampers`
  MODIFY `pk_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `home_slider`
--
ALTER TABLE `home_slider`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `immediatepickup`
--
ALTER TABLE `immediatepickup`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `invoice_details`
--
ALTER TABLE `invoice_details`
  MODIFY `invoice_d_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=605;

--
-- AUTO_INCREMENT for table `invoice_hedder`
--
ALTER TABLE `invoice_hedder`
  MODIFY `invoice_h_id` int(1) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `invoice_item_details`
--
ALTER TABLE `invoice_item_details`
  MODIFY `invoice_d_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_settings`
--
ALTER TABLE `invoice_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice_status`
--
ALTER TABLE `invoice_status`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `itemmapping`
--
ALTER TABLE `itemmapping`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=260;

--
-- AUTO_INCREMENT for table `item_master`
--
ALTER TABLE `item_master`
  MODIFY `item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `item_specification`
--
ALTER TABLE `item_specification`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `item_uom`
--
ALTER TABLE `item_uom`
  MODIFY `uom_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `item_warranty`
--
ALTER TABLE `item_warranty`
  MODIFY `warranty_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `location_master`
--
ALTER TABLE `location_master`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `over_head_master`
--
ALTER TABLE `over_head_master`
  MODIFY `over_head_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments_in_delivery`
--
ALTER TABLE `payments_in_delivery`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment_terms`
--
ALTER TABLE `payment_terms`
  MODIFY `payment_terms_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `price_type`
--
ALTER TABLE `price_type`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `price_type_customer_mapping`
--
ALTER TABLE `price_type_customer_mapping`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `productimages`
--
ALTER TABLE `productimages`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=882;

--
-- AUTO_INCREMENT for table `product_availability`
--
ALTER TABLE `product_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `product_price_mapping`
--
ALTER TABLE `product_price_mapping`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_settlement_plan`
--
ALTER TABLE `product_settlement_plan`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- AUTO_INCREMENT for table `product_vat_master`
--
ALTER TABLE `product_vat_master`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_note_header`
--
ALTER TABLE `purchase_note_header`
  MODIFY `purchase_note_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `purchase_note_items`
--
ALTER TABLE `purchase_note_items`
  MODIFY `purchase_note_item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_return_details`
--
ALTER TABLE `purchase_return_details`
  MODIFY `pr_d_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_return_header`
--
ALTER TABLE `purchase_return_header`
  MODIFY `pr_h_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `refer_email`
--
ALTER TABLE `refer_email`
  MODIFY `pk_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `repeat_units`
--
ALTER TABLE `repeat_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_return_details`
--
ALTER TABLE `sales_return_details`
  MODIFY `sales_return_d_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `sales_return_hedder`
--
ALTER TABLE `sales_return_hedder`
  MODIFY `sales_return_h_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `shipping_address`
--
ALTER TABLE `shipping_address`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipping_address_availability`
--
ALTER TABLE `shipping_address_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `shipping_method`
--
ALTER TABLE `shipping_method`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `site_banners`
--
ALTER TABLE `site_banners`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `smtp_settings`
--
ALTER TABLE `smtp_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `standing_order`
--
ALTER TABLE `standing_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `standing_order_item`
--
ALTER TABLE `standing_order_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `stock_issue_expected_products`
--
ALTER TABLE `stock_issue_expected_products`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_issue_header`
--
ALTER TABLE `stock_issue_header`
  MODIFY `issue_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_issue_items`
--
ALTER TABLE `stock_issue_items`
  MODIFY `issue_item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_transfer_header`
--
ALTER TABLE `stock_transfer_header`
  MODIFY `transfer_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_transfer_items`
--
ALTER TABLE `stock_transfer_items`
  MODIFY `transfer_item_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subcribers`
--
ALTER TABLE `subcribers`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `supplier_balance`
--
ALTER TABLE `supplier_balance`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `supplier_payment_options`
--
ALTER TABLE `supplier_payment_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_shipping_address`
--
ALTER TABLE `supplier_shipping_address`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `temp_cart`
--
ALTER TABLE `temp_cart`
  MODIFY `cart_h_pk_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temp_cart_details`
--
ALTER TABLE `temp_cart_details`
  MODIFY `temp_car_d_id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `typemappingitem`
--
ALTER TABLE `typemappingitem`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `type_master`
--
ALTER TABLE `type_master`
  MODIFY `type_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `url`
--
ALTER TABLE `url`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_levels`
--
ALTER TABLE `user_levels`
  MODIFY `user_level_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crm_category_master`
--
ALTER TABLE `crm_category_master`
  ADD CONSTRAINT `fk_crm_category_segment` FOREIGN KEY (`segment_id`) REFERENCES `crm_segment_master` (`segment_id`) ON DELETE CASCADE;

--
-- Constraints for table `crm_company_person`
--
ALTER TABLE `crm_company_person`
  ADD CONSTRAINT `fk_crm_company_person_company` FOREIGN KEY (`company_id`) REFERENCES `crm_company_master` (`company_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crm_company_person_person` FOREIGN KEY (`person_id`) REFERENCES `crm_person_master` (`person_id`) ON DELETE CASCADE;

--
-- Constraints for table `crm_opportunity`
--
ALTER TABLE `crm_opportunity`
  ADD CONSTRAINT `fk_crm_opportunity_company` FOREIGN KEY (`company_id`) REFERENCES `crm_company_master` (`company_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_crm_opportunity_person` FOREIGN KEY (`person_id`) REFERENCES `crm_person_master` (`person_id`),
  ADD CONSTRAINT `fk_crm_opportunity_sales_cycle` FOREIGN KEY (`sales_cycle_id`) REFERENCES `crm_sales_cycle_master` (`sales_cycle_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_crm_opportunity_sales_person` FOREIGN KEY (`sales_person_id`) REFERENCES `crm_sales_person_master` (`sales_person_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_crm_opportunity_segment` FOREIGN KEY (`segment_id`) REFERENCES `crm_segment_master` (`segment_id`) ON DELETE SET NULL;

--
-- Constraints for table `crm_opportunity_update`
--
ALTER TABLE `crm_opportunity_update`
  ADD CONSTRAINT `fk_crm_opportunity_update_opportunity` FOREIGN KEY (`opportunity_id`) REFERENCES `crm_opportunity` (`opportunity_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_crm_opportunity_update_stage` FOREIGN KEY (`sales_cycle_stage_id`) REFERENCES `crm_sales_cycle_stage` (`sales_cycle_stage_id`);

--
-- Constraints for table `crm_sales_cycle_stage`
--
ALTER TABLE `crm_sales_cycle_stage`
  ADD CONSTRAINT `fk_crm_sales_cycle_stage_cycle` FOREIGN KEY (`sales_cycle_id`) REFERENCES `crm_sales_cycle_master` (`sales_cycle_id`) ON DELETE CASCADE;

--
-- Constraints for table `price_type_customer_mapping`
--
ALTER TABLE `price_type_customer_mapping`
  ADD CONSTRAINT `fk_ptcm_price_type` FOREIGN KEY (`price_type_id`) REFERENCES `price_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_price_mapping`
--
ALTER TABLE `product_price_mapping`
  ADD CONSTRAINT `fk_ppm_price_type` FOREIGN KEY (`price_type_id`) REFERENCES `price_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `standing_order`
--
ALTER TABLE `standing_order`
  ADD CONSTRAINT `fk_standing_order_repeat_unit` FOREIGN KEY (`RepeatUnit`) REFERENCES `repeat_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `standing_order_item`
--
ALTER TABLE `standing_order_item`
  ADD CONSTRAINT `fk_soi_so` FOREIGN KEY (`standing_order_id`) REFERENCES `standing_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
