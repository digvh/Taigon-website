-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 12, 2026 at 06:09 PM
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
-- Database: `taigondb`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_customer_orders` (IN `p_email` VARCHAR(100))   BEGIN
    SELECT 
        o.id,
        o.tracking_id,
        o.order_date,
        o.order_status,
        SUM(oi.quantity * oi.price) as total_amount,
        COUNT(oi.id) as items_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.email = p_email
    GROUP BY o.id
    ORDER BY o.order_date DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_product_stock` (IN `p_product_id` INT, IN `p_quantity_changed` INT, IN `p_change_type` VARCHAR(20), IN `p_reason` VARCHAR(255), IN `p_changed_by` VARCHAR(100))   BEGIN
    DECLARE current_qty INT;
    
    -- Get current quantity
    SELECT quantity INTO current_qty FROM products WHERE id = p_product_id;
    
    -- Update product quantity
    UPDATE products 
    SET quantity = quantity - p_quantity_changed
    WHERE id = p_product_id;
    
    -- Log the change
    INSERT INTO inventory_logs (product_id, previous_quantity, new_quantity, change_type, quantity_changed, reason, changed_by)
    VALUES (p_product_id, current_qty, current_qty - p_quantity_changed, p_change_type, p_quantity_changed, p_reason, p_changed_by);
    
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`) VALUES
(2, 'admin', '$2y$10$YourNewHashedPasswordHere', 'System Administrator', 'admin@taigoninvestment.co.tz', 'super_admin', '2026-06-09 11:18:41'),
(3, 'newadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'New Administrator', 'newadmin@taigoninvestment.co.tz', 'super_admin', '2026-06-12 14:17:19');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `product_id`, `quantity`, `added_at`, `updated_at`) VALUES
(1, 3, 1, 1, '2026-06-09 11:49:39', '2026-06-09 11:49:39'),
(2, 3, 2, 1, '2026-06-09 11:53:45', '2026-06-09 11:53:45'),
(3, 3, 27, 1, '2026-06-10 16:29:14', '2026-06-10 16:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `categore`
--

CREATE TABLE `categore` (
  `id` int(11) NOT NULL,
  `categories_name` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT 'default-category.jpg',
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categore`
--

INSERT INTO `categore` (`id`, `categories_name`, `image`, `description`, `display_order`, `status`, `created_at`) VALUES
(1, 'Laptops - Dell', 'image/laptops.jpg', 'High-performance Dell laptops for business and personal use', 1, 'active', '2026-06-08 14:40:32'),
(2, 'Laptops - HP', 'image/laptops.jpg', 'Reliable HP laptops with latest processors', 2, 'active', '2026-06-08 14:40:32'),
(3, 'Laptops - Lenovo', 'image/laptops.jpg', 'Durable Lenovo laptops for professional use', 3, 'active', '2026-06-08 14:40:32'),
(4, 'Laptops - Apple MacBook', 'image/laptops.jpg', 'Premium Apple MacBooks for creative professionals', 4, 'active', '2026-06-08 14:40:32'),
(5, 'Desktop Computers', 'image/desktop.jpg', 'Powerful desktop computers for home and office', 5, 'active', '2026-06-08 14:40:32'),
(6, 'Printers & Scanners', 'image/printers.jpg', 'Quality printers and scanners for all your needs', 6, 'active', '2026-06-08 14:40:32'),
(7, 'CCTV Cameras', 'image/cctv.jpg', 'High-definition surveillance cameras', 7, 'active', '2026-06-08 14:40:32'),
(8, 'Network Equipment', 'image/network.jpg', 'Networking solutions including routers, switches, and access points', 8, 'active', '2026-06-08 14:40:32'),
(9, 'Computer Spare Parts', 'image/spares.jpg', 'Genuine computer components and spare parts', 9, 'active', '2026-06-08 14:40:32'),
(10, 'IT Accessories', 'image/accessories.jpg', 'Essential computer accessories and peripherals', 10, 'active', '2026-06-08 14:40:32');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'dickson', 'dickson@gmail.com', 'ie83287397', 'Installation Service', 'wsj3utd97p5d0', 'new', '2026-06-12 13:21:03');

-- --------------------------------------------------------

--
-- Table structure for table `couriers`
--

CREATE TABLE `couriers` (
  `courier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `assigned_zone` varchar(100) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 5.0,
  `total_deliveries` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `couriers`
--

INSERT INTO `couriers` (`courier_id`, `user_id`, `vehicle_type`, `license_plate`, `status`, `assigned_zone`, `rating`, `total_deliveries`, `created_at`) VALUES
(1, 2, 'Motorcycle', 'T123ABC', 'active', 'Arusha Central', 5.0, 0, '2026-06-08 14:40:32');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `change_type` enum('add','subtract','order','restock','adjustment') NOT NULL,
  `quantity_changed` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_sales`
-- (See below for the actual view)
--
CREATE TABLE `monthly_sales` (
`month` varchar(7)
,`total_orders` bigint(21)
,`items_sold` decimal(32,0)
,`revenue` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `tracking_id` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `order_status` enum('Pending','Processing','Shipped','Delivered','Completed','Cancelled') DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `courier_id` int(11) DEFAULT NULL,
  `notification_method` varchar(20) DEFAULT 'email',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `tracking_id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `city`, `state`, `address`, `order_status`, `order_date`, `updated_at`, `courier_id`, `notification_method`, `notes`) VALUES
(1, 'TGN-82D79F83', 3, 'dickson', 'ibrahim', 'dickson@gmail.com', '766686921', 'Arusha', 'Arusha', NULL, 'Completed', '2026-06-09 11:56:18', '2026-06-12 14:23:54', NULL, 'email', NULL);

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `order_status_change_log` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.order_status != NEW.order_status THEN
        INSERT INTO order_status_history (order_id, old_status, new_status, changed_by)
        VALUES (NEW.id, OLD.order_status, NEW.order_status, 'system');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 2500000.00),
(2, 1, 2, 1, 1200000.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` varchar(50) DEFAULT 'system',
  `notes` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `old_status`, `new_status`, `changed_by`, `notes`, `changed_at`) VALUES
(1, 1, 'Pending', 'Completed', 'system', NULL, '2026-06-12 14:23:54');

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_summary`
-- (See below for the actual view)
--
CREATE TABLE `order_summary` (
`order_id` int(11)
,`tracking_id` varchar(50)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`email` varchar(100)
,`order_date` timestamp
,`order_status` enum('Pending','Processing','Shipped','Delivered','Completed','Cancelled')
,`item_count` bigint(21)
,`total_amount` decimal(42,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `category` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT 'default-product.jpg',
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `warranty_months` int(11) DEFAULT 12,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `quantity`, `category`, `image`, `brand`, `model`, `warranty_months`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Dell XPS 15', '15.6\" 4K OLED Display, Intel Core i7-12700H, 16GB RAM, 1TB SSD, NVIDIA RTX 3050 Ti', 2500000.00, 10, 'Laptops - Dell', 'image/dell-xps.jpg', 'Dell', 'XPS 15', 24, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:48'),
(2, 'Dell Latitude 5420', '14\" FHD, Intel Core i5-1135G7, 8GB RAM, 256GB SSD, Windows 11 Pro', 1200000.00, 15, 'Laptops - Dell', 'image/dell-latitude.jpg', 'Dell', 'Latitude 5420', 24, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(3, 'HP Spectre x360', '13.5\" 3K2K Touch Display, Intel Core i7-1255U, 16GB RAM, 512GB SSD', 2200000.00, 8, 'Laptops - HP', 'image/hp-spectre.jpg', 'HP', 'Spectre x360', 24, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(4, 'HP EliteBook 840 G8', '14\" FHD, Intel Core i7-1165G7, 16GB RAM, 512GB SSD', 1800000.00, 12, 'Laptops - HP', 'image/hp-elitebook.jpg', 'HP', 'EliteBook 840 G8', 24, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(5, 'Lenovo ThinkPad X1 Carbon', '14\" WUXGA, Intel Core i7-1260P, 16GB RAM, 512GB SSD', 2100000.00, 12, 'Laptops - Lenovo', 'image/lenovo-thinkpad.jpg', 'Lenovo', 'ThinkPad X1 Carbon', 36, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(6, 'Lenovo IdeaPad 3', '15.6\" FHD, AMD Ryzen 5 5500U, 8GB RAM, 256GB SSD', 750000.00, 20, 'Laptops - Lenovo', 'image/lenovo-ideapad.jpg', 'Lenovo', 'IdeaPad 3', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(7, 'Apple MacBook Pro 14\"', 'M2 Pro Chip, 16GB RAM, 512GB SSD, 14-inch Liquid Retina XDR', 3200000.00, 5, 'Laptops - Apple MacBook', 'image/macbook-pro.jpg', 'Apple', 'MacBook Pro 14\"', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(8, 'Apple MacBook Air 13\"', 'M2 Chip, 8GB RAM, 256GB SSD, 13.6-inch Liquid Retina', 1900000.00, 8, 'Laptops - Apple MacBook', 'image/macbook-air.jpg', 'Apple', 'MacBook Air 13\"', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(9, 'Dell OptiPlex 3090 Desktop', 'Intel Core i5-11400, 8GB RAM, 256GB SSD, Windows 11 Pro', 850000.00, 7, 'Desktop Computers', 'image/dell-optiplex.jpg', 'Dell', 'OptiPlex 3090', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(10, 'HP ProDesk 400 G7', 'Intel Core i5-10500, 8GB RAM, 256GB SSD, DOS', 650000.00, 10, 'Desktop Computers', 'image/hp-prodesk.jpg', 'HP', 'ProDesk 400 G7', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(11, 'HP LaserJet Pro M404dn', 'Monochrome Laser Printer, Print Speed: 40ppm, Duplex Printing', 850000.00, 15, 'Printers & Scanners', 'image/hp-printer.jpg', 'HP', 'LaserJet Pro M404dn', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(12, 'HP OfficeJet Pro 9025e', 'All-in-One Printer, Print/Copy/Scan/Fax, Auto Document Feeder', 450000.00, 10, 'Printers & Scanners', 'image/hp-officejet.jpg', 'HP', 'OfficeJet Pro 9025e', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(13, 'Canon imageCLASS MF445dw', 'Monochrome All-in-One, Print/Copy/Scan/Fax, Duplex Printing', 550000.00, 8, 'Printers & Scanners', 'image/canon-printer.jpg', 'Canon', 'imageCLASS MF445dw', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(14, 'Hikvision 4MP CCTV Camera', '4MP IR Bullet Camera, 30m Night Vision, IP67 Weatherproof', 180000.00, 30, 'CCTV Cameras', 'image/hikvision-camera.jpg', 'Hikvision', 'DS-2CE12DFT-PIR', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(15, 'Hikvision 8MP CCTV Camera', '8MP 4K IR Bullet Camera, 40m Night Vision, Smart Hybrid Light', 320000.00, 20, 'CCTV Cameras', 'image/hikvision-8mp.jpg', 'Hikvision', 'DS-2CE72HFT-PIR', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(16, 'Dahua 5MP CCTV Camera', '5MP IR Eyeball Camera, 30m Night Vision, IP67', 220000.00, 25, 'CCTV Cameras', 'image/dahua-camera.jpg', 'Dahua', 'DH-HAC-HDW1500TL', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(17, 'TP-Link Gigabit Router', 'AC1200 Wireless Dual-Band Router, 4 Gigabit Ports, VPN Support', 120000.00, 25, 'Network Equipment', 'image/tplink-router.jpg', 'TP-Link', 'Archer C6', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(18, 'TP-Link 24-Port Switch', '24-Port Gigabit Unmanaged Switch, Plug and Play', 250000.00, 10, 'Network Equipment', 'image/tplink-switch.jpg', 'TP-Link', 'TL-SG1024', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(19, 'Ubiquiti UniFi AP AC Pro', 'Enterprise Wi-Fi Access Point, 2.4/5GHz, 450Mbps', 280000.00, 12, 'Network Equipment', 'image/unifi-ap.jpg', 'Ubiquiti', 'UAP-AC-Pro', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(20, 'Intel Core i7-12700K', '12th Gen Intel Core i7-12700K, 12 Cores, Up to 5.0GHz, LGA1700', 550000.00, 20, 'Computer Spare Parts', 'image/intel-i7.jpg', 'Intel', 'Core i7-12700K', 36, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(21, 'Intel Core i5-12400', '12th Gen Intel Core i5-12400, 6 Cores, Up to 4.4GHz, LGA1700', 320000.00, 25, 'Computer Spare Parts', 'image/intel-i5.jpg', 'Intel', 'Core i5-12400', 36, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(22, 'Kingston 16GB DDR4 RAM', '16GB DDR4 3200MHz Desktop Memory', 120000.00, 40, 'Computer Spare Parts', 'image/kingston-ram.jpg', 'Kingston', 'KVR32N22D8/16', 60, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(23, 'Samsung 1TB SSD', 'Samsung 870 EVO 1TB SATA III Internal SSD', 180000.00, 30, 'Computer Spare Parts', 'image/samsung-ssd.jpg', 'Samsung', '870 EVO', 60, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(24, 'Logitech MX Master 3S Mouse', 'Wireless Mouse, 8K DPI, Silent Clicks, USB-C Charging', 95000.00, 18, 'IT Accessories', 'image/logitech-mouse.jpg', 'Logitech', 'MX Master 3S', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(25, 'Logitech K380 Keyboard', 'Multi-Device Bluetooth Keyboard, Compact Design', 45000.00, 25, 'IT Accessories', 'image/logitech-keyboard.jpg', 'Logitech', 'K380', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(26, 'Dell 27\" Monitor', '27\" FHD IPS Monitor, 75Hz, HDMI, VGA, DP', 350000.00, 15, 'IT Accessories', 'image/dell-monitor.jpg', 'Dell', 'S2721H', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(27, 'HP 24\" Monitor', '24\" FHD IPS Monitor, 60Hz, HDMI, VGA', 250000.00, 20, 'IT Accessories', 'image/hp-monitor.jpg', 'HP', 'M24f', 12, 'active', '2026-06-08 14:40:32', '2026-06-11 17:22:49'),
(28, 'HP LaserJet Power Supply Board', 'Original power supply board for HP LaserJet printers. High-quality replacement part.', 85000.00, 15, 'Printers & Scanners', 'image/placeholder.png', 'HP', 'RM2-7642-000', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(29, 'HP LaserJet M426 ADF Cable', '10 PIN 46CM ADF cable for HP LaserJet M426 series printers. Essential for document feeder function.', 35000.00, 20, 'Printers & Scanners', 'image/placeholder.png', 'HP', 'M426', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(30, 'Canon iR ADV ADF Pickup Roller', 'ADF Pickup Roller compatible with Canon iR ADV 4525/4535/4545/4551/4725/4735/4745/4751. Part No: FM1-D470-010', 45000.00, 25, 'Printers & Scanners', 'image/placeholder.png', 'Canon', 'iR ADV Series', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(31, 'Fujitsu Scanner Roller Set', 'Scanner Roller Set compatible with Fujitsu Fi-7160, Fi-7140, Fi-7240, Fi-7180, Fi-7260, Fi-7280, Fi-7300NX. Part No: PA03670-0001', 55000.00, 12, 'Printers & Scanners', 'image/placeholder.png', 'Fujitsu', 'Fi Series', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(32, 'Photocopier Spare Parts Kit', 'Complete set of high-quality photocopier spare parts. Includes various components for printer maintenance.', 125000.00, 10, 'Printers & Scanners', 'image/placeholder.png', 'Generic', 'Various', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(33, 'HP M477/479/452 Pressure Roller', 'High-quality pressure roller compatible with HP M477, M479, M452 series printers. Essential for paper feeding and print quality.', 38000.00, 30, 'Printers & Scanners', 'image/placeholder.png', 'HP', 'M477/M479/M452', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(34, 'Ricoh Pickup Roller Set', 'Pickup Roller Set compatible with Ricoh MP2554SP, MP C3054, MP C3554, MP C4054, MP C5504, MP C2003. Part No: AF03-0094, AF03-1094', 48000.00, 18, 'Printers & Scanners', 'image/placeholder.png', 'Ricoh', 'MP Series', 6, 'active', '2026-06-10 17:41:09', '2026-06-11 16:47:20'),
(35, 'Printer Drums Chips Set', 'Compatible drum chips for multiple brands including Konica (DR512, DR711, DR312/DR314, DR313, DR215K), Canon (IRC3320/3020/3125, iR ADV 4525/4535/4545, GPR-30/31, GPR-51/C-EXV47, C-EXV51/NPG71), and HP M580', 25000.00, 50, 'Printers & Scanners', 'image/placeholder.png', 'Multi-Brand', 'Various', 6, 'active', '2026-06-10 17:41:10', '2026-06-11 16:47:20'),
(36, 'HP LaserJet Power Supply Board', 'Original power supply board for HP LaserJet printers. High-quality replacement part.', 85000.00, 15, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-06 at 08.35.08 (1).jpeg', 'HP', 'RM2-7642-000', 6, 'active', '2026-06-11 17:40:26', '2026-06-11 17:40:26'),
(37, 'HP LaserJet M426 ADF Cable', '10 PIN 46CM ADF cable for HP LaserJet M426 series printers. Essential for document feeder function.', 35000.00, 20, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-06 at 08.35.08.jpeg', 'HP', 'M426', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27'),
(38, 'Canon iR ADV ADF Pickup Roller', 'ADF Pickup Roller compatible with Canon iR ADV 4525/4535/4545/4551/4725/4735/4745/4751. Part No: FM1-D470-010', 45000.00, 25, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-09 at 09.18.44.jpeg', 'Canon', 'iR ADV Series', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27'),
(39, 'Fujitsu Scanner Roller Set', 'Scanner Roller Set compatible with Fujitsu Fi-7160, Fi-7140, Fi-7240, Fi-7180, Fi-7260, Fi-7280, Fi-7300NX. Part No: PA03670-0001', 55000.00, 12, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-10 at 02.02.51 (1).jpeg', 'Fujitsu', 'Fi Series', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27'),
(40, 'Photocopier Spare Parts Kit', 'Complete set of high-quality photocopier spare parts. Includes various components for printer maintenance.', 125000.00, 10, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-10 at 02.02.51.jpeg', 'Generic', 'Various', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27'),
(41, 'HP M477/479/452 Pressure Roller', 'High-quality pressure roller compatible with HP M477, M479, M452 series printers. Essential for paper feeding and print quality.', 38000.00, 30, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-10 at 02.02.52.jpeg', 'HP', 'M477/M479/M452', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27'),
(42, 'Ricoh Pickup Roller Set', 'Pickup Roller Set compatible with Ricoh MP2554SP, MP C3054, MP C3554, MP C4054, MP C5504, MP C2003. Part No: AF03-0094, AF03-1094', 48000.00, 18, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-10 at 02.02.53 (1).jpeg', 'Ricoh', 'MP Series', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27'),
(43, 'Printer Drums Chips Set', 'Compatible drum chips for multiple brands including Konica (DR512, DR711, DR312/DR314, DR313, DR215K), Canon (IRC3320/3020/3125, iR ADV 4525/4535/4545, GPR-30/31, GPR-51/C-EXV47, C-EXV51/NPG71), and HP M580', 25000.00, 50, 'Printers & Scanners', 'image/WhatsApp Image 2026-06-10 at 02.02.53.jpeg', 'Multi-Brand', 'Various', 6, 'active', '2026-06-11 17:40:27', '2026-06-11 17:40:27');

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_stock_status`
-- (See below for the actual view)
--
CREATE TABLE `product_stock_status` (
`id` int(11)
,`name` varchar(255)
,`category` varchar(100)
,`quantity` int(11)
,`price` decimal(10,2)
,`stock_status` varchar(14)
);

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

CREATE TABLE `service_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `device_type` varchar(100) NOT NULL,
  `device_brand` varchar(100) DEFAULT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `service_type` enum('repair','maintenance','installation','consultation') DEFAULT 'repair',
  `preferred_date` date DEFAULT NULL,
  `preferred_time` varchar(50) DEFAULT NULL,
  `status` enum('pending','scheduled','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_technician_id` int(11) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `actual_cost` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','courier','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `address`, `remember_token`, `token_expiry`, `reset_token`, `reset_expiry`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', 'john@example.com', '0712345678', '123 Main Street, Arusha', NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '2026-06-08 14:40:32', '2026-06-08 14:40:32'),
(2, 'James Courier', 'james@taigon.co.tz', '0787654321', 'Arusha, Tanzania', NULL, NULL, NULL, NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'courier', '2026-06-08 14:40:32', '2026-06-08 14:40:32'),
(3, 'dickson', 'dickson@gmail.com', '0766686921', '123', NULL, NULL, NULL, NULL, '$2y$10$9f1PabEOoDGuLIrm2RP2puOBnFBhqdnAJY0sXYQRxJ0LwfO2hXV1q', 'customer', '2026-06-09 11:49:13', '2026-06-09 11:49:13');

-- --------------------------------------------------------

--
-- Structure for view `monthly_sales`
--
DROP TABLE IF EXISTS `monthly_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthly_sales`  AS SELECT date_format(`o`.`order_date`,'%Y-%m') AS `month`, count(distinct `o`.`id`) AS `total_orders`, sum(`oi`.`quantity`) AS `items_sold`, sum(`oi`.`quantity` * `oi`.`price`) AS `revenue` FROM (`orders` `o` join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) WHERE `o`.`order_status` in ('Completed','Delivered') GROUP BY date_format(`o`.`order_date`,'%Y-%m') ORDER BY date_format(`o`.`order_date`,'%Y-%m') DESC ;

-- --------------------------------------------------------

--
-- Structure for view `order_summary`
--
DROP TABLE IF EXISTS `order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_summary`  AS SELECT `o`.`id` AS `order_id`, `o`.`tracking_id` AS `tracking_id`, `o`.`first_name` AS `first_name`, `o`.`last_name` AS `last_name`, `o`.`email` AS `email`, `o`.`order_date` AS `order_date`, `o`.`order_status` AS `order_status`, count(`oi`.`id`) AS `item_count`, sum(`oi`.`quantity` * `oi`.`price`) AS `total_amount` FROM (`orders` `o` left join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) GROUP BY `o`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `product_stock_status`
--
DROP TABLE IF EXISTS `product_stock_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `product_stock_status`  AS SELECT `products`.`id` AS `id`, `products`.`name` AS `name`, `products`.`category` AS `category`, `products`.`quantity` AS `quantity`, `products`.`price` AS `price`, CASE WHEN `products`.`quantity` = 0 THEN 'Out of Stock' WHEN `products`.`quantity` < 5 THEN 'Very Low Stock' WHEN `products`.`quantity` < 10 THEN 'Low Stock' WHEN `products`.`quantity` < 25 THEN 'Medium Stock' ELSE 'In Stock' END AS `stock_status` FROM `products` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `categore`
--
ALTER TABLE `categore`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name` (`categories_name`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `couriers`
--
ALTER TABLE `couriers`
  ADD PRIMARY KEY (`courier_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_id` (`tracking_id`),
  ADD KEY `idx_tracking` (`tracking_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`order_status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `courier_id` (`courier_id`),
  ADD KEY `idx_order_status_date` (`order_status`,`order_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_order_product` (`order_id`,`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_brand` (`brand`),
  ADD KEY `idx_status` (`status`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_search` (`name`,`description`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_technician_id` (`assigned_technician_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_remember_token` (`remember_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categore`
--
ALTER TABLE `categore`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `couriers`
--
ALTER TABLE `couriers`
  MODIFY `courier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `couriers`
--
ALTER TABLE `couriers`
  ADD CONSTRAINT `couriers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`courier_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category`) REFERENCES `categore` (`categories_name`) ON UPDATE CASCADE;

--
-- Constraints for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD CONSTRAINT `service_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_requests_ibfk_2` FOREIGN KEY (`assigned_technician_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
