-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2026 at 03:52 PM
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
-- Database: `btl_ltw`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `sp_add_to_cart`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_to_cart` (IN `p_cus_id` INT, IN `p_product_id` INT, IN `p_quantity` INT)   BEGIN
    DECLARE v_cart_id INT DEFAULT NULL;
    DECLARE v_price DECIMAL(12,2) DEFAULT NULL;

    IF p_quantity <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng thêm vào giỏ hàng phải lớn hơn 0';
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM `Customer`
        WHERE `user_id` = p_cus_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Khách hàng không tồn tại';
    END IF;

    SET v_price = (
        SELECT `price`
        FROM `Product`
        WHERE `product_id` = p_product_id
        LIMIT 1
    );

    IF v_price IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Sản phẩm không tồn tại';
    END IF;

    SET v_cart_id = (
        SELECT `cart_id`
        FROM `Cart`
        WHERE `cus_id` = p_cus_id
          AND `status` = 'Đang chọn hàng'
        ORDER BY `cart_id` DESC
        LIMIT 1
    );

    IF v_cart_id IS NULL THEN
        INSERT INTO `Cart` (`created_at`, `status`, `cus_id`)
        VALUES (NOW(), 'Đang chọn hàng', p_cus_id);

        SET v_cart_id = LAST_INSERT_ID();
    END IF;

    INSERT INTO `CartItem` (`cart_id`, `product_id`, `quantity`, `unit_price`)
    VALUES (v_cart_id, p_product_id, p_quantity, v_price)
    ON DUPLICATE KEY UPDATE
        `quantity` = `quantity` + p_quantity,
        `unit_price` = v_price;

    SELECT v_cart_id AS `cart_id`;
END$$

DROP PROCEDURE IF EXISTS `sp_cancel_order`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cancel_order` (IN `p_order_id` INT)   BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM `Orders`
        WHERE `order_id` = p_order_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Đơn hàng không tồn tại';
    END IF;

    DELETE FROM `Orders`
    WHERE `order_id` = p_order_id;
END$$

DROP PROCEDURE IF EXISTS `sp_change_user_status`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_change_user_status` (IN `p_user_id` INT, IN `p_status` VARCHAR(50))   BEGIN
    IF p_status NOT IN ('Hoạt động', 'Bị cấm', 'Ngừng hoạt động') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Trạng thái người dùng không hợp lệ';
    END IF;

    UPDATE `User`
    SET `status` = p_status
    WHERE `user_id` = p_user_id;
END$$

DROP PROCEDURE IF EXISTS `sp_create_order_from_cart`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_order_from_cart` (IN `p_cus_id` INT, IN `p_address_id` INT, IN `p_voucher_code` VARCHAR(50), IN `p_shipping_fee` DECIMAL(12,2), IN `p_payment_method` VARCHAR(50))   BEGIN
    DECLARE v_cart_id INT DEFAULT NULL;
    DECLARE v_voucher_id INT DEFAULT NULL;
    DECLARE v_order_id INT DEFAULT NULL;
    DECLARE v_item_count INT DEFAULT 0;
    DECLARE v_payment_method VARCHAR(50) DEFAULT 'COD';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    SET v_payment_method = COALESCE(NULLIF(p_payment_method, ''), 'COD');

    START TRANSACTION;

    SET v_cart_id = (
        SELECT `cart_id`
        FROM `Cart`
        WHERE `cus_id` = p_cus_id
          AND `status` = 'Đang chọn hàng'
        ORDER BY `cart_id` DESC
        LIMIT 1
    );

    IF v_cart_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Khách hàng chưa có giỏ hàng đang chọn';
    END IF;

    SELECT COUNT(*)
    INTO v_item_count
    FROM `CartItem`
    WHERE `cart_id` = v_cart_id;

    IF v_item_count = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giỏ hàng đang trống';
    END IF;

    IF p_voucher_code IS NOT NULL AND p_voucher_code <> '' THEN
        SET v_voucher_id = (
            SELECT `voucher_id`
            FROM `Voucher`
            WHERE `code` = p_voucher_code
              AND CURDATE() BETWEEN `start_date` AND `end_date`
            LIMIT 1
        );

        IF v_voucher_id IS NULL THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Voucher không tồn tại hoặc đã hết hạn';
        END IF;
    END IF;

    INSERT INTO `Orders`
    (
        `order_date`,
        `total_amount`,
        `shipping_fee`,
        `cus_id`,
        `address_id`,
        `voucher_id`
    )
    VALUES
    (
        NOW(),
        0,
        COALESCE(p_shipping_fee, 0),
        p_cus_id,
        p_address_id,
        v_voucher_id
    );

    SET v_order_id = LAST_INSERT_ID();

    INSERT INTO `OrderItem`
    (
        `order_id`,
        `product_id`,
        `quantity`,
        `sold_price`,
        `subtotal`
    )
    SELECT
        v_order_id,
        ci.`product_id`,
        ci.`quantity`,
        ci.`unit_price`,
        ci.`quantity` * ci.`unit_price`
    FROM `CartItem` ci
    WHERE ci.`cart_id` = v_cart_id;

    CALL `sp_recalculate_order_total`(v_order_id);

    INSERT INTO `Payment`
    (
        `payment_method`,
        `payment_date`,
        `amount`,
        `order_id`
    )
    SELECT
        v_payment_method,
        NOW(),
        `total_amount`,
        `order_id`
    FROM `Orders`
    WHERE `order_id` = v_order_id;

    UPDATE `Cart`
    SET `status` = 'Đã đặt hàng'
    WHERE `cart_id` = v_cart_id;

    COMMIT;

    SELECT
        o.`order_id`,
        o.`order_date`,
        o.`total_amount`,
        o.`shipping_fee`,
        o.`cus_id`,
        o.`address_id`,
        o.`voucher_id`
    FROM `Orders` o
    WHERE o.`order_id` = v_order_id;
END$$

DROP PROCEDURE IF EXISTS `sp_get_customer_orders`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_customer_orders` (IN `p_cus_id` INT)   BEGIN
    SELECT
        o.`order_id`,
        o.`order_date`,
        o.`total_amount`,
        o.`shipping_fee`,
        o.`cus_id`,
        a.`recipient_name`,
        a.`phone`,
        a.`ward`,
        a.`city`,
        v.`code` AS `voucher_code`,
        p.`payment_method`,
        p.`payment_date`
    FROM `Orders` o
    JOIN `Address` a ON o.`address_id` = a.`address_id`
    LEFT JOIN `Voucher` v ON o.`voucher_id` = v.`voucher_id`
    LEFT JOIN `Payment` p ON o.`order_id` = p.`order_id`
    WHERE o.`cus_id` = p_cus_id
    ORDER BY o.`order_date` DESC;
END$$

DROP PROCEDURE IF EXISTS `sp_get_products`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_products` (IN `p_keyword` VARCHAR(150), IN `p_category_id` INT)   BEGIN
    SELECT
        p.`product_id`,
        p.`product_name`,
        p.`price`,
        p.`stock_quantity`,
        p.`material`,
        p.`color`,
        p.`warranty_period`,
        p.`url`,
        c.`category_id`,
        c.`category_name`,
        CASE
            WHEN p.`stock_quantity` > 0 THEN 'Còn hàng'
            ELSE 'Hết hàng'
        END AS `stock_status`
    FROM `Product` p
    JOIN `Category` c ON p.`category_id` = c.`category_id`
    WHERE
        (p_keyword IS NULL OR p_keyword = '' OR p.`product_name` LIKE CONCAT('%', p_keyword, '%'))
        AND
        (p_category_id IS NULL OR p_category_id = 0 OR p.`category_id` = p_category_id)
    ORDER BY p.`product_id` ASC;
END$$

DROP PROCEDURE IF EXISTS `sp_recalculate_order_total`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_recalculate_order_total` (IN `p_order_id` INT)   BEGIN
    DECLARE v_item_total DECIMAL(12,2) DEFAULT 0;
    DECLARE v_shipping_fee DECIMAL(12,2) DEFAULT 0;
    DECLARE v_voucher_id INT DEFAULT NULL;
    DECLARE v_discount_type VARCHAR(50);
    DECLARE v_discount_value DECIMAL(12,2) DEFAULT 0;
    DECLARE v_max_discount_value DECIMAL(12,2) DEFAULT NULL;
    DECLARE v_discount_amount DECIMAL(12,2) DEFAULT 0;

    SELECT COALESCE(SUM(`subtotal`), 0)
    INTO v_item_total
    FROM `OrderItem`
    WHERE `order_id` = p_order_id;

    SELECT COALESCE(`shipping_fee`, 0), `voucher_id`
    INTO v_shipping_fee, v_voucher_id
    FROM `Orders`
    WHERE `order_id` = p_order_id;

    IF v_voucher_id IS NOT NULL THEN
        SELECT `discount_type`, `discount_value`, `max_discount_value`
        INTO v_discount_type, v_discount_value, v_max_discount_value
        FROM `Voucher`
        WHERE `voucher_id` = v_voucher_id;

        IF v_discount_type = 'percent' THEN
            SET v_discount_amount = v_item_total * v_discount_value / 100;

            IF v_max_discount_value IS NOT NULL THEN
                SET v_discount_amount = LEAST(v_discount_amount, v_max_discount_value);
            END IF;

        ELSEIF v_discount_type = 'fixed' THEN
            SET v_discount_amount = v_discount_value;
        END IF;

        SET v_discount_amount = LEAST(v_discount_amount, v_item_total);
    END IF;

    UPDATE `Orders`
    SET `total_amount` = GREATEST(v_item_total + v_shipping_fee - v_discount_amount, 0)
    WHERE `order_id` = p_order_id;
END$$

DROP PROCEDURE IF EXISTS `sp_update_product_image`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_product_image` (IN `p_product_id` INT, IN `p_image_path` VARCHAR(255))   BEGIN
    UPDATE `Product`
    SET `url` = COALESCE(NULLIF(p_image_path, ''), 'uploads/products/default.jpg')
    WHERE `product_id` = p_product_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `about_content`
--

DROP TABLE IF EXISTS `about_content`;
CREATE TABLE IF NOT EXISTS `about_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_content`
--

INSERT INTO `about_content` (`id`, `content`, `updated_at`) VALUES
(1, 'header(What is wood? How to recognize it?)\r\n/image(1)\r\nContrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \"de Finibus Bonorum et Malorum\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \"Lorem ipsum dolor sit amet..\", comes from a line in section 1.10.32.', '2026-05-10 06:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `about_images`
--

DROP TABLE IF EXISTS `about_images`;
CREATE TABLE IF NOT EXISTS `about_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about_images`
--

INSERT INTO `about_images` (`image_id`, `filename`, `path`, `alt_text`, `uploaded_at`) VALUES
(1, 'about_20260510075427_159.jpg', 'uploads/about/about_20260510075427_159.jpg', 'thiếu nữ xinh đẹp', '2026-05-10 05:54:27');

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
CREATE TABLE IF NOT EXISTS `address` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `cus_id` int(11) NOT NULL,
  PRIMARY KEY (`address_id`),
  KEY `fk_address_customer` (`cus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `phone`, `ward`, `city`, `recipient_name`, `cus_id`) VALUES
(1, '0910000001', 'Phường Bến Nghé', 'TP.HCM', 'Nguyễn Văn A', 11),
(2, '0910000002', 'Phường Tân Định', 'TP.HCM', 'Trần Thị B', 12),
(3, '0910000003', 'Phường Linh Trung', 'TP.HCM', 'Lê Minh C', 13),
(4, '0910000004', 'Phường Dĩ An', 'Bình Dương', 'Phạm Hoàng D', 14),
(5, '0910000005', 'Phường An Phú', 'TP.HCM', 'Võ Bảo E', 15),
(6, '0910000006', 'Phường Thảo Điền', 'TP.HCM', 'Đặng Ngọc F', 16),
(7, '0910000007', 'Phường Hiệp Bình Chánh', 'TP.HCM', 'Bùi Thanh G', 17),
(8, '0910000008', 'Phường Long Bình', 'TP.HCM', 'Hoàng Anh H', 18),
(9, '0910000009', 'Phường Tân Phú', 'Đồng Nai', 'Đỗ Quốc I', 19),
(10, '0910000010', 'Phường Phú Mỹ', 'Bình Dương', 'Mai Khánh J', 20);

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
CREATE TABLE IF NOT EXISTS `admin` (
  `user_id` int(11) NOT NULL,
  `permission_level` varchar(50) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`user_id`, `permission_level`) VALUES
(1, 'FULL'),
(2, 'PRODUCT_MANAGER'),
(3, 'ORDER_MANAGER'),
(4, 'CUSTOMER_MANAGER'),
(5, 'VOUCHER_MANAGER'),
(6, 'CONTENT_MANAGER'),
(7, 'SUPPORT_MANAGER'),
(8, 'INVENTORY_MANAGER'),
(9, 'REPORT_MANAGER'),
(10, 'LIMITED'),
(21, '');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Đang chọn hàng',
  `cus_id` int(11) NOT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `fk_cart_customer` (`cus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `created_at`, `status`, `cus_id`) VALUES
(1, '2026-04-02 08:00:00', 'Đang chọn hàng', 11),
(2, '2026-04-02 08:05:00', 'Đang chọn hàng', 12),
(3, '2026-04-02 08:10:00', 'Đang chọn hàng', 13),
(4, '2026-04-02 08:15:00', 'Đang chọn hàng', 14),
(5, '2026-04-02 08:20:00', 'Đang chọn hàng', 15),
(6, '2026-04-02 08:25:00', 'Đang chọn hàng', 16),
(7, '2026-04-02 08:30:00', 'Đang chọn hàng', 17),
(8, '2026-04-02 08:35:00', 'Đang chọn hàng', 18),
(9, '2026-04-02 08:40:00', 'Đang chọn hàng', 19),
(10, '2026-04-02 08:45:00', 'Đang chọn hàng', 20),
(17, '2026-05-10 13:08:55', 'Đang chọn hàng', 21),
(18, '2026-05-10 13:09:03', 'active', 21),
(19, '2026-05-10 20:39:33', 'Đang chọn hàng', 22),
(20, '2026-05-10 20:40:21', 'active', 22);

-- --------------------------------------------------------

--
-- Table structure for table `cartitem`
--

DROP TABLE IF EXISTS `cartitem`;
CREATE TABLE IF NOT EXISTS `cartitem` (
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`cart_id`,`product_id`),
  KEY `fk_cartitem_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cartitem`
--

INSERT INTO `cartitem` (`cart_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 8500000.00),
(2, 2, 1, 24000000.00),
(3, 3, 1, 12500000.00),
(4, 4, 1, 31000000.00),
(5, 5, 1, 18500000.00),
(6, 6, 1, 22000000.00),
(7, 7, 1, 42000000.00),
(8, 8, 2, 6500000.00),
(9, 9, 1, 15500000.00),
(10, 10, 1, 9000000.00);

--
-- Triggers `cartitem`
--
DROP TRIGGER IF EXISTS `trg_cartitem_bi_validate`;
DELIMITER $$
CREATE TRIGGER `trg_cartitem_bi_validate` BEFORE INSERT ON `cartitem` FOR EACH ROW BEGIN
    DECLARE v_price DECIMAL(12,2);
    DECLARE v_stock INT;

    SELECT `price`, `stock_quantity`
    INTO v_price, v_stock
    FROM `Product`
    WHERE `product_id` = NEW.`product_id`;

    IF NEW.`quantity` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng trong giỏ hàng phải lớn hơn 0';
    END IF;

    IF NEW.`quantity` > v_stock THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng trong giỏ hàng vượt quá tồn kho';
    END IF;

    IF NEW.`unit_price` <= 0 THEN
        SET NEW.`unit_price` = v_price;
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_cartitem_bu_validate`;
DELIMITER $$
CREATE TRIGGER `trg_cartitem_bu_validate` BEFORE UPDATE ON `cartitem` FOR EACH ROW BEGIN
    DECLARE v_stock INT;

    SELECT `stock_quantity`
    INTO v_stock
    FROM `Product`
    WHERE `product_id` = NEW.`product_id`;

    IF NEW.`quantity` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng trong giỏ hàng phải lớn hơn 0';
    END IF;

    IF NEW.`quantity` > v_stock THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng trong giỏ hàng vượt quá tồn kho';
    END IF;

    IF NEW.`unit_price` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Đơn giá trong giỏ hàng phải lớn hơn 0';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
CREATE TABLE IF NOT EXISTS `category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`) VALUES
(4, 'Armchair'),
(9, 'Bed'),
(10, 'Cabinet'),
(1, 'Chair'),
(3, 'Console'),
(6, 'Desk'),
(8, 'Shelf'),
(5, 'Sideboard'),
(7, 'Sofa'),
(2, 'Table');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `mail` varchar(150) NOT NULL,
  `question` text NOT NULL,
  `status` varchar(50) DEFAULT 'Chưa đọc',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
CREATE TABLE IF NOT EXISTS `customer` (
  `user_id` int(11) NOT NULL,
  `loyalty_point` int(11) DEFAULT 0,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`user_id`, `loyalty_point`) VALUES
(11, 120),
(12, 90),
(13, 45),
(14, 75),
(15, 30),
(16, 150),
(17, 10),
(18, 60),
(19, 25),
(20, 200),
(21, 0),
(22, 0);

-- --------------------------------------------------------

--
-- Table structure for table `faq`
--

DROP TABLE IF EXISTS `faq`;
CREATE TABLE IF NOT EXISTS `faq` (
  `faq_id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`faq_id`),
  KEY `fk_faq_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faq`
--

INSERT INTO `faq` (`faq_id`, `question`, `answer`, `category_id`) VALUES
(1, 'Sản phẩm ghế có bảo hành không?', 'Có. Các sản phẩm ghế được bảo hành theo thời gian ghi trên từng sản phẩm.', 1),
(2, 'Bàn có hỗ trợ lắp ráp tại nhà không?', 'Có. Cửa hàng hỗ trợ lắp ráp tại nhà tùy khu vực giao hàng.', 2),
(3, 'Console có phù hợp đặt ở phòng khách không?', 'Có. Console thường được dùng ở phòng khách, hành lang hoặc khu vực trang trí.', 3),
(4, 'Armchair có thể đặt theo màu riêng không?', 'Một số mẫu có thể đặt theo màu riêng tùy tình trạng vật liệu.', 4),
(5, 'Sideboard dùng để làm gì?', 'Sideboard thường dùng để lưu trữ đồ dùng, trang trí phòng ăn hoặc phòng khách.', 5),
(6, 'Bàn làm việc có chống trầy không?', 'Sản phẩm có lớp hoàn thiện bảo vệ bề mặt, tuy nhiên vẫn nên tránh vật sắc nhọn.', 6),
(7, 'Sofa có được vệ sinh trước khi giao không?', 'Có. Sản phẩm được kiểm tra và vệ sinh trước khi giao cho khách.', 7),
(8, 'Kệ treo tường có kèm phụ kiện lắp đặt không?', 'Có. Sản phẩm có kèm phụ kiện cơ bản để lắp đặt.', 8),
(9, 'Giường có bao gồm nệm không?', 'Thông thường giường không bao gồm nệm, trừ khi có ghi rõ trong mô tả sản phẩm.', 9),
(10, 'Tủ cabinet có chống ẩm không?', 'Sản phẩm có xử lý bề mặt cơ bản, nên đặt ở nơi khô ráo để tăng độ bền.', 10);

-- --------------------------------------------------------

--
-- Table structure for table `orderitem`
--

DROP TABLE IF EXISTS `orderitem`;
CREATE TABLE IF NOT EXISTS `orderitem` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `sold_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`order_id`,`product_id`),
  KEY `fk_orderitem_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orderitem`
--

INSERT INTO `orderitem` (`order_id`, `product_id`, `quantity`, `sold_price`, `subtotal`) VALUES
(1001, 1, 1, 8500000.00, 8500000.00),
(1002, 2, 1, 24000000.00, 24000000.00),
(1003, 3, 1, 12500000.00, 12500000.00),
(1004, 4, 1, 31000000.00, 31000000.00),
(1005, 5, 1, 18500000.00, 18500000.00),
(1006, 6, 1, 22000000.00, 22000000.00),
(1007, 7, 1, 42000000.00, 42000000.00),
(1008, 8, 2, 6500000.00, 13000000.00),
(1009, 9, 1, 15500000.00, 15500000.00),
(1010, 10, 3, 9000000.00, 27000000.00);

--
-- Triggers `orderitem`
--
DROP TRIGGER IF EXISTS `trg_orderitem_ad_restore_stock_total`;
DELIMITER $$
CREATE TRIGGER `trg_orderitem_ad_restore_stock_total` AFTER DELETE ON `orderitem` FOR EACH ROW BEGIN
    UPDATE `Product`
    SET `stock_quantity` = `stock_quantity` + OLD.`quantity`
    WHERE `product_id` = OLD.`product_id`;

    CALL `sp_recalculate_order_total`(OLD.`order_id`);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_orderitem_ai_update_stock_total`;
DELIMITER $$
CREATE TRIGGER `trg_orderitem_ai_update_stock_total` AFTER INSERT ON `orderitem` FOR EACH ROW BEGIN
    UPDATE `Product`
    SET `stock_quantity` = `stock_quantity` - NEW.`quantity`
    WHERE `product_id` = NEW.`product_id`;

    CALL `sp_recalculate_order_total`(NEW.`order_id`);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_orderitem_au_update_stock_total`;
DELIMITER $$
CREATE TRIGGER `trg_orderitem_au_update_stock_total` AFTER UPDATE ON `orderitem` FOR EACH ROW BEGIN
    UPDATE `Product`
    SET `stock_quantity` = `stock_quantity` + OLD.`quantity` - NEW.`quantity`
    WHERE `product_id` = NEW.`product_id`;

    CALL `sp_recalculate_order_total`(NEW.`order_id`);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_orderitem_bi_prepare`;
DELIMITER $$
CREATE TRIGGER `trg_orderitem_bi_prepare` BEFORE INSERT ON `orderitem` FOR EACH ROW BEGIN
    DECLARE v_price DECIMAL(12,2);
    DECLARE v_stock INT;

    SELECT `price`, `stock_quantity`
    INTO v_price, v_stock
    FROM `Product`
    WHERE `product_id` = NEW.`product_id`;

    IF NEW.`quantity` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng sản phẩm trong đơn hàng phải lớn hơn 0';
    END IF;

    IF NEW.`quantity` > v_stock THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Không đủ hàng trong kho để tạo đơn hàng';
    END IF;

    IF NEW.`sold_price` <= 0 THEN
        SET NEW.`sold_price` = v_price;
    END IF;

    SET NEW.`subtotal` = NEW.`quantity` * NEW.`sold_price`;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_orderitem_bu_prepare`;
DELIMITER $$
CREATE TRIGGER `trg_orderitem_bu_prepare` BEFORE UPDATE ON `orderitem` FOR EACH ROW BEGIN
    DECLARE v_stock INT;

    IF NEW.`order_id` <> OLD.`order_id` THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Không được đổi order_id của OrderItem';
    END IF;

    IF NEW.`product_id` <> OLD.`product_id` THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Không được đổi product_id của OrderItem';
    END IF;

    SELECT `stock_quantity`
    INTO v_stock
    FROM `Product`
    WHERE `product_id` = NEW.`product_id`;

    IF NEW.`quantity` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng sản phẩm trong đơn hàng phải lớn hơn 0';
    END IF;

    IF NEW.`quantity` > v_stock + OLD.`quantity` THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Không đủ hàng trong kho để cập nhật đơn hàng';
    END IF;

    IF NEW.`sold_price` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giá bán phải lớn hơn 0';
    END IF;

    SET NEW.`subtotal` = NEW.`quantity` * NEW.`sold_price`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_fee` decimal(12,2) DEFAULT 0.00,
  `cus_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Chờ xử lý',
  PRIMARY KEY (`order_id`),
  KEY `fk_orders_customer` (`cus_id`),
  KEY `fk_orders_address` (`address_id`),
  KEY `fk_orders_voucher` (`voucher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1014 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_date`, `total_amount`, `shipping_fee`, `cus_id`, `address_id`, `voucher_id`, `status`) VALUES
(1001, '2026-04-03 10:00:00', 8250000.00, 50000.00, 11, 1, 1, 'Chờ xử lý'),
(1002, '2026-04-03 10:10:00', 23500000.00, 0.00, 12, 2, 2, 'Chờ xử lý'),
(1003, '2026-04-03 10:20:00', 12480000.00, 80000.00, 13, 3, 3, 'Chờ xử lý'),
(1004, '2026-04-03 10:30:00', 31000000.00, 0.00, 14, 4, NULL, 'Chờ xử lý'),
(1005, '2026-04-03 10:40:00', 18310000.00, 60000.00, 15, 5, 4, 'Đã xác nhận'),
(1006, '2026-04-03 10:50:00', 22000000.00, 0.00, 16, 6, NULL, 'Chờ xử lý'),
(1007, '2026-04-03 11:00:00', 41100000.00, 100000.00, 17, 7, 5, 'Chờ xử lý'),
(1008, '2026-04-03 11:10:00', 12850000.00, 50000.00, 18, 8, 6, 'Chờ xử lý'),
(1009, '2026-04-03 11:20:00', 15500000.00, 0.00, 19, 9, NULL, 'Chờ xử lý'),
(1010, '2026-04-03 11:30:00', 26400000.00, 100000.00, 20, 10, 7, 'Chờ xử lý'),
(1011, '2026-05-10 20:29:49', 8500001.00, 0.00, 21, NULL, NULL, 'Chờ xử lý'),
(1012, '2026-05-10 20:30:12', 17000002.00, 0.00, 21, NULL, NULL, 'Chờ xử lý'),
(1013, '2026-05-10 20:40:26', 32500002.00, 0.00, 22, NULL, NULL, 'Chờ xử lý');

--
-- Triggers `orders`
--
DROP TRIGGER IF EXISTS `trg_orders_bd_restore_stock`;
DELIMITER $$
CREATE TRIGGER `trg_orders_bd_restore_stock` BEFORE DELETE ON `orders` FOR EACH ROW BEGIN
    UPDATE `Product` p
    JOIN (
        SELECT `product_id`, SUM(`quantity`) AS total_quantity
        FROM `OrderItem`
        WHERE `order_id` = OLD.`order_id`
        GROUP BY `product_id`
    ) oi ON p.`product_id` = oi.`product_id`
    SET p.`stock_quantity` = p.`stock_quantity` + oi.total_quantity;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_orders_bu_validate`;
DELIMITER $$
CREATE TRIGGER `trg_orders_bu_validate` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
    DECLARE v_count INT DEFAULT 0;

    IF NEW.`shipping_fee` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Phí vận chuyển không được âm';
    END IF;

    SELECT COUNT(*)
    INTO v_count
    FROM `Address`
    WHERE `address_id` = NEW.`address_id`
      AND `cus_id` = NEW.`cus_id`;

    IF v_count = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Địa chỉ không thuộc về khách hàng này';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_method` varchar(50) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `amount` decimal(12,2) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `payment_method`, `payment_date`, `amount`, `order_id`) VALUES
(1, 'COD', '2026-04-03 10:05:00', 8250000.00, 1001),
(2, 'Banking', '2026-04-03 10:15:00', 23500000.00, 1002),
(3, 'COD', '2026-04-03 10:25:00', 12480000.00, 1003),
(4, 'Credit Card', '2026-04-03 10:35:00', 31000000.00, 1004),
(5, 'COD', '2026-04-03 10:45:00', 18310000.00, 1005),
(6, 'Banking', '2026-04-03 10:55:00', 22000000.00, 1006),
(7, 'COD', '2026-04-03 11:05:00', 41100000.00, 1007),
(8, 'Credit Card', '2026-04-03 11:15:00', 12850000.00, 1008),
(9, 'COD', '2026-04-03 11:25:00', 15500000.00, 1009),
(10, 'Banking', '2026-04-03 11:35:00', 26400000.00, 1010);

--
-- Triggers `payment`
--
DROP TRIGGER IF EXISTS `trg_payment_bi_validate`;
DELIMITER $$
CREATE TRIGGER `trg_payment_bi_validate` BEFORE INSERT ON `payment` FOR EACH ROW BEGIN
    IF NEW.`amount` <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số tiền thanh toán phải lớn hơn 0';
    END IF;

    IF NEW.`payment_method` IS NULL OR NEW.`payment_method` = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Phương thức thanh toán không được rỗng';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
CREATE TABLE IF NOT EXISTS `product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `material` varchar(100) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `warranty_period` varchar(50) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT 'uploads/products/default.jpg',
  PRIMARY KEY (`product_id`),
  KEY `fk_product_category` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_name`, `price`, `stock_quantity`, `material`, `color`, `warranty_period`, `category_id`, `url`) VALUES
(1, 'Olivewood Dining Chair', 8500001.00, 22, 'Olivewood', 'Natural Beige', '24 tháng', 1, '../uploads/products/prod_1778417251_406.jpg'),
(2, 'Minimalist Oak Table', 24000001.00, 13, 'Oak Wood', 'Natural Oak', '36 tháng', 2, '../uploads/products/prod_1778417225_559.jpg'),
(3, 'Velvet Lounge Armchair', 12500001.00, 19, 'Velvet, Wood', 'Cream', '24 tháng', 4, '../uploads/products/prod_1778417189_907.jpg'),
(4, 'Artisan Sideboard', 31000001.00, 7, 'Olivewood', 'Natural Brown', '36 tháng', 5, '../uploads/products/prod_1778417163_651.jpg'),
(5, 'Marble Top Console', 18499001.00, 11, 'Marble, Wood', 'White Marble', '24 tháng', 3, '../uploads/products/prod_1778417133_364.jpg'),
(6, 'Curved Walnut Desk', 22000001.00, 9, 'Walnut Wood', 'Walnut Brown', '36 tháng', 6, '../uploads/products/prod_1778417098_684.jpg'),
(7, 'Nordic Fabric Sofa', 42000001.00, 4, 'Fabric, Wood', 'Gray', '36 tháng', 7, '../uploads/products/prod_1778417028_705.jpg'),
(8, 'Bamboo Wall Shelf', 6499001.00, 38, 'Bamboo', 'Light Brown', '12 tháng', 8, '../uploads/products/prod_1778416996_691.jpg'),
(9, 'Platform Storage Bed', 15500001.00, 17, 'Plywood, Fabric', 'Dark Gray', '24 tháng', 9, '../uploads/products/prod_1778416951_545.jpg'),
(10, 'Rattan Storage Cabinet', 50001.00, 22, 'Rattan, Wood', 'Natural Brown', '18 tháng', 10, '../uploads/products/prod_1778416898_153.jpg');

--
-- Triggers `product`
--
DROP TRIGGER IF EXISTS `trg_product_bi_validate`;
DELIMITER $$
CREATE TRIGGER `trg_product_bi_validate` BEFORE INSERT ON `product` FOR EACH ROW BEGIN
    IF NEW.`price` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giá sản phẩm không được âm';
    END IF;

    IF NEW.`stock_quantity` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng tồn kho không được âm';
    END IF;

    IF NEW.`url` IS NULL OR NEW.`url` = '' THEN
        SET NEW.`url` = 'uploads/products/default.jpg';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_product_bu_validate`;
DELIMITER $$
CREATE TRIGGER `trg_product_bu_validate` BEFORE UPDATE ON `product` FOR EACH ROW BEGIN
    IF NEW.`price` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giá sản phẩm không được âm';
    END IF;

    IF NEW.`stock_quantity` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Số lượng tồn kho không được âm';
    END IF;

    IF NEW.`url` IS NULL OR NEW.`url` = '' THEN
        SET NEW.`url` = 'uploads/products/default.jpg';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

DROP TABLE IF EXISTS `profile`;
CREATE TABLE IF NOT EXISTS `profile` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `member_since` int(4) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`user_id`, `name`, `email`, `phone`, `address`, `avatar_url`, `member_since`) VALUES
(1, 'Huy Nguyen', 'huy.nguyen@example.com', '+84 90 123 4567', '123 Dong Khoi, District 1, Ho Chi Minh City, Vietn...', 'https://images.pexels.com/photos/220453/pexels-pho...', 2026),
(21, 'Đức Huy', '05duchuy@gmail.com', '1234567890', 'ktx khu A, linh trung, thu duc', '', 2026),
(22, 'Nam Bad Boy', 'user1@gmail.com', '0123456789', 'ktx khu A', '../uploads/avatar/avatar_22_1778420482.jpg', 2026);

-- --------------------------------------------------------

--
-- Table structure for table `qaa`
--

DROP TABLE IF EXISTS `qaa`;
CREATE TABLE IF NOT EXISTS `qaa` (
  `q_id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pub',
  `role` varchar(20) DEFAULT 'admin',
  PRIMARY KEY (`q_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qaa`
--

INSERT INTO `qaa` (`q_id`, `content`, `title`, `category`, `status`, `role`) VALUES
(1, 'Yes. We offer design support and customization for...', 'Does Olivewood offer custom interior design services...', 'product', 'pub', 'admin'),
(2, 'Yes. You can add products to your cart, fill in yo...', 'Can I place an order directly on the website?', 'order', 'pub', 'admin'),
(3, 'Delivery usually takes 3 to 7 business days for in...', 'How long does delivery usually take?', 'shipping', 'pub', 'admin'),
(4, 'Our products are covered for technical defects cau...', 'What is Olivewood\'s warranty policy?', 'warranty', 'pub', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `review_cmt`
--

DROP TABLE IF EXISTS `review_cmt`;
CREATE TABLE IF NOT EXISTS `review_cmt` (
  `review_cmt_id` int(11) NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'approved',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`review_cmt_id`),
  KEY `fk_review_cmt_post` (`review_id`),
  KEY `fk_review_cmt_customer` (`user_id`),
  KEY `fk_review_cmt_admin` (`admin_id`),
  KEY `idx_review_cmt_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_cmt`
--

INSERT INTO `review_cmt` (`review_cmt_id`, `content`, `status`, `admin_note`, `created_at`, `review_id`, `user_id`, `admin_id`) VALUES
(3, 'I agree that the website is easy to use, especially the product list page.', 'approved', 'Approved sample comment.', '2026-05-03 11:20:00', 2, 11, 2),
(4, 'Does the chair feel heavy when moving it around?', 'approved', 'Approved sample comment.', '2026-04-29 09:15:00', 3, 15, 2),
(6, 'Delivery packaging is important for wooden furniture, so this comment helps.', 'approved', 'Approved sample comment.', '2026-04-22 18:00:00', 5, 17, 3),
(8, 'huy quá dz', 'approved', 'Hidden by admin.', '2026-05-10 13:27:08', 1, 21, 21),
(9, 'hay quá', 'approved', NULL, '2026-05-10 20:41:36', 3, 22, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `review_post`
--

DROP TABLE IF EXISTS `review_post`;
CREATE TABLE IF NOT EXISTS `review_post` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT 'uploads/reviews/default.jpg',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `review_type` varchar(50) NOT NULL DEFAULT 'product',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`review_id`),
  KEY `fk_review_post_customer` (`user_id`),
  KEY `fk_review_post_product` (`product_id`),
  KEY `fk_review_post_admin` (`admin_id`),
  KEY `idx_review_post_status_created` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `review_post`
--

INSERT INTO `review_post` (`review_id`, `title`, `content`, `image`, `status`, `review_type`, `admin_note`, `created_at`, `user_id`, `product_id`, `admin_id`) VALUES
(1, 'Natural Wood Table Review After Two Weeks of Use', 'The table feels solid, the surface is smooth, and the color looks close to the product photos on the website. It works well for daily use and gives the room a warmer feeling.', 'uploads/reviews/natural-wood-table.jpg', 'approved', 'product', 'Approved sample review.', '2026-05-05 09:00:00', 11, 2, 21),
(2, 'Good Shopping Experience on the Website', 'The website is easy to browse. Product information is clear enough for customers to compare options before buying. The cart and order process are also simple to follow.', 'uploads/reviews/website-shopping-experience.jpg', 'approved', 'website', 'Approved sample review.', '2026-05-03 10:00:00', 12, NULL, 1),
(3, 'Product Quality Compared to Website Photos', 'The real product looks very similar to the images shown online. The material and color are close to what I expected, so the product description is helpful.', 'uploads/reviews/product-quality-photo.jpg', 'approved', 'product', 'Approved sample review.', '2026-04-29 08:30:00', 13, 1, 2),
(4, 'Customer Service Before Purchase Was Helpful', 'The staff answered my questions clearly and suggested suitable products based on my room size and budget. This made the purchase decision easier.', 'uploads/reviews/customer-service.jpg', 'approved', 'service', 'Approved by admin.', '2026-04-26 14:20:00', 14, NULL, NULL),
(5, 'Packaging and Delivery Review', 'The product was packed carefully and delivered in good condition. The corners and surface were protected well during shipping.', 'uploads/reviews/packaging-delivery.jpg', 'approved', 'delivery', 'Approved sample review.', '2026-04-22 16:45:00', 15, NULL, NULL),
(6, 'Comfortable Wooden Chair for Daily Use', 'The wooden chair is comfortable for daily use and feels stable. The finish is simple but elegant, and it matches the style of my room.', 'uploads/reviews/wooden-chair.jpg', 'approved', 'product', 'Hidden for admin review in sample data.', '2026-04-18 11:10:00', 16, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Hoạt động',
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_name`, `email`, `phone`, `created_at`, `status`, `password`, `role`) VALUES
(1, 'Admin Huy', 'admin1@gmail.com', '0900000001', '2026-04-01 08:00:00', 'Hoạt động', '123456', 'admin'),
(2, 'Admin Trang', 'admin2@gmail.com', '0900000002', '2026-04-01 08:05:00', 'Hoạt động', '123456', 'admin'),
(3, 'Admin Khoa', 'admin3@gmail.com', '0900000003', '2026-04-01 08:10:00', 'Hoạt động', '123456', 'admin'),
(4, 'Admin Minh', 'admin4@gmail.com', '0900000004', '2026-04-01 08:15:00', 'Hoạt động', '123456', 'admin'),
(5, 'Admin Nhi', 'admin5@gmail.com', '0900000005', '2026-04-01 08:20:00', 'Hoạt động', '123456', 'admin'),
(6, 'Admin Phúc', 'admin6@gmail.com', '0900000006', '2026-04-01 08:25:00', 'Hoạt động', '123456', 'admin'),
(7, 'Admin An', 'admin7@gmail.com', '0900000007', '2026-04-01 08:30:00', 'Hoạt động', '123456', 'admin'),
(8, 'Admin Bảo', 'admin8@gmail.com', '0900000008', '2026-04-01 08:35:00', 'Hoạt động', '123456', 'admin'),
(9, 'Admin Long', 'admin9@gmail.com', '0900000009', '2026-04-01 08:40:00', 'Hoạt động', '123456', 'admin'),
(10, 'Admin Vy', 'admin10@gmail.com', '0900000010', '2026-04-01 08:45:00', 'Hoạt động', '123456', 'admin'),
(11, 'Nguyễn Văn A', 'customer1@gmail.com', '0910000001', '2026-04-01 09:00:00', 'Hoạt động', '123456', 'customer'),
(12, 'Trần Thị B', 'customer2@gmail.com', '0910000002', '2026-04-01 09:05:00', 'Hoạt động', '123456', 'customer'),
(13, 'Lê Minh C', 'customer3@gmail.com', '0910000003', '2026-04-01 09:10:00', 'Hoạt động', '123456', 'customer'),
(14, 'Phạm Hoàng D', 'customer4@gmail.com', '0910000004', '2026-04-01 09:15:00', 'Hoạt động', '123456', 'customer'),
(15, 'Võ Bảo E', 'customer5@gmail.com', '0910000005', '2026-04-01 09:20:00', 'Hoạt động', '123456', 'customer'),
(16, 'Đặng Ngọc F', 'customer6@gmail.com', '0910000006', '2026-04-01 09:25:00', 'Hoạt động', '123456', 'customer'),
(17, 'Bùi Thanh G', 'customer7@gmail.com', '0910000007', '2026-04-01 09:30:00', 'Hoạt động', '123456', 'customer'),
(18, 'Hoàng Anh H', 'customer8@gmail.com', '0910000008', '2026-04-01 09:35:00', 'Hoạt động', '123456', 'customer'),
(19, 'Đỗ Quốc I', 'customer9@gmail.com', '0910000009', '2026-04-01 09:40:00', 'Hoạt động', '123456', 'customer'),
(20, 'Mai Khánh J', 'customer10@gmail.com', '0910000010', '2026-04-01 09:45:00', 'Hoạt động', '123456', 'customer'),
(21, 'Đức Huy', '05duchuy@gmail.com', '1234567890', '2026-05-10 13:08:55', 'Hoạt động', '$2y$10$dtOZhkNO8sZNxsmIbp8STutwnlzg1FyKAvDUhUM6p7cyKj/cIqfbC', 'admin'),
(22, 'Nam Bad Boy', 'user1@gmail.com', '0123456789', '2026-05-10 20:39:33', 'Hoạt động', '$2y$10$C0gcbq0Ee3UeqBaf55yEyeDZg.tBHSYCds9QVxyelT7vOh4fwrz5u', 'customer');

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

DROP TABLE IF EXISTS `voucher`;
CREATE TABLE IF NOT EXISTS `voucher` (
  `voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_value` decimal(12,2) NOT NULL,
  `max_discount_value` decimal(12,2) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `discount_type` varchar(50) NOT NULL,
  `admin_id` int(11) NOT NULL,
  PRIMARY KEY (`voucher_id`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_voucher_admin` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`voucher_id`, `code`, `discount_value`, `max_discount_value`, `start_date`, `end_date`, `discount_type`, `admin_id`) VALUES
(1, 'WELCOME10', 10.00, 300000.00, '2026-01-01', '2026-12-31', 'percent', 1),
(2, 'FURNI5', 5.00, 500000.00, '2026-01-01', '2026-12-31', 'percent', 2),
(3, 'FREESHIP', 100000.00, 100000.00, '2026-01-01', '2026-12-31', 'fixed', 3),
(4, 'NEWHOME250', 250000.00, 250000.00, '2026-01-01', '2026-12-31', 'fixed', 4),
(5, 'VIP15', 15.00, 1000000.00, '2026-01-01', '2026-12-31', 'percent', 5),
(6, 'SAVE200', 200000.00, 200000.00, '2026-01-01', '2026-12-31', 'fixed', 6),
(7, 'SALE20', 20.00, 700000.00, '2026-01-01', '2026-12-31', 'percent', 7),
(8, 'CHAIR100', 100000.00, 100000.00, '2026-01-01', '2026-12-31', 'fixed', 8),
(9, 'TABLE300', 300000.00, 300000.00, '2026-01-01', '2026-12-31', 'fixed', 9),
(10, 'HOME8', 8.00, 400000.00, '2026-01-01', '2026-12-31', 'percent', 10);

--
-- Triggers `voucher`
--
DROP TRIGGER IF EXISTS `trg_voucher_bi_validate`;
DELIMITER $$
CREATE TRIGGER `trg_voucher_bi_validate` BEFORE INSERT ON `voucher` FOR EACH ROW BEGIN
    IF NEW.`discount_value` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giá trị giảm giá không được âm';
    END IF;

    IF NEW.`max_discount_value` IS NOT NULL AND NEW.`max_discount_value` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giảm giá tối đa không được âm';
    END IF;

    IF NEW.`start_date` > NEW.`end_date` THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ngày bắt đầu không được lớn hơn ngày kết thúc';
    END IF;

    IF NEW.`discount_type` NOT IN ('percent', 'fixed') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'discount_type chỉ được là percent hoặc fixed';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `trg_voucher_bu_validate`;
DELIMITER $$
CREATE TRIGGER `trg_voucher_bu_validate` BEFORE UPDATE ON `voucher` FOR EACH ROW BEGIN
    IF NEW.`discount_value` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giá trị giảm giá không được âm';
    END IF;

    IF NEW.`max_discount_value` IS NOT NULL AND NEW.`max_discount_value` < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Giảm giá tối đa không được âm';
    END IF;

    IF NEW.`start_date` > NEW.`end_date` THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ngày bắt đầu không được lớn hơn ngày kết thúc';
    END IF;

    IF NEW.`discount_type` NOT IN ('percent', 'fixed') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'discount_type chỉ được là percent hoặc fixed';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `web_info`
--

DROP TABLE IF EXISTS `web_info`;
CREATE TABLE IF NOT EXISTS `web_info` (
  `info_id` int(11) NOT NULL DEFAULT 1,
  `phone` varchar(20) DEFAULT NULL,
  `mail` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `key_value` text DEFAULT NULL,
  PRIMARY KEY (`info_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `web_info`
--

INSERT INTO `web_info` (`info_id`, `phone`, `mail`, `address`, `key_value`) VALUES
(1, '0123456666', 'atelier@olivewood.com', '123 Dong Khoi Street, District 1, HCM', 'Cửa hàng nội thất thủ công Olivewood');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `address`
--
ALTER TABLE `address`
  ADD CONSTRAINT `fk_address_customer` FOREIGN KEY (`cus_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`cus_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD CONSTRAINT `fk_cartitem_cart` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cartitem_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `fk_customer_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faq`
--
ALTER TABLE `faq`
  ADD CONSTRAINT `fk_faq_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orderitem`
--
ALTER TABLE `orderitem`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`cus_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orders_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `voucher` (`voucher_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE;

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `review_cmt`
--
ALTER TABLE `review_cmt`
  ADD CONSTRAINT `fk_review_cmt_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_cmt_customer` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_cmt_post` FOREIGN KEY (`review_id`) REFERENCES `review_post` (`review_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `review_post`
--
ALTER TABLE `review_post`
  ADD CONSTRAINT `fk_review_post_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_post_customer` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_post_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `voucher`
--
ALTER TABLE `voucher`
  ADD CONSTRAINT `fk_voucher_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
