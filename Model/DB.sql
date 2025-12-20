-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: 2entral
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `inventory_logs`
--

DROP TABLE IF EXISTS `inventory_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_logs` (
  `LogsID` int NOT NULL AUTO_INCREMENT,
  `LogsDetails` varchar(255) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `ProductID` char(10) NOT NULL DEFAULT '2025DEF000',
  `UserID` int NOT NULL,
  PRIMARY KEY (`LogsID`),
  KEY `ProductID_idx` (`ProductID`),
  KEY `UserID_idx` (`UserID`),
  CONSTRAINT `Product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`),
  CONSTRAINT `User` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_logs`
--

LOCK TABLES `inventory_logs` WRITE;
/*!40000 ALTER TABLE `inventory_logs` DISABLE KEYS */;
INSERT INTO `inventory_logs` VALUES (181,'Deleted Supplier: Sunrise-Sports SDN BHD','2025-12-21 02:22:50','Active','2025DEF000',1);
/*!40000 ALTER TABLE `inventory_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `ProductID` char(10) NOT NULL,
  `ProductName` varchar(100) NOT NULL,
  `Description` varchar(200) NOT NULL,
  `Category` varchar(50) NOT NULL,
  `Stock` int NOT NULL,
  `Price` decimal(6,2) NOT NULL,
  `LowStockAlert` int NOT NULL,
  `ImagePath` varchar(100) NOT NULL,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `SupplierID` int NOT NULL,
  PRIMARY KEY (`ProductID`),
  UNIQUE KEY `ImagePath_UNIQUE` (`ImagePath`),
  KEY `SupplierID_idx` (`SupplierID`),
  CONSTRAINT `SupplierID` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES ('2025DEF000','Default','Default','Default',0,0.00,0,'Default','Inactive',0),('25SPO00001','LI-NING HALBERTEC 8000 BADMINTON RACQUECT BLUE/PINK','6.8mm slim shaft for faster swings and reduced drag High Modulus Carbon Fibre for strength and responsiveness.','Racquet',30,899.00,5,'product_1766236412_6946a0fc6c801.jpeg','Active',3),('25SPO00002','YONEX NANOFLARE 800 PRO	','ISOMETRIC technology continues to help the world’s greatest players achieve global success.','Racquet',55,859.00,5,'yonex_800.jpg','Active',1),('25SPO00003','YONEX ASTROX 100ZZ','For advanced players looking for immediate access to power to maintain a relentless attack','Racquet',115,950.00,5,'yonex_100zz.jpg','Active',1),('25SPO00004','YONEX STRING AEROBITE','Mains - 0.67 mm; Crosses - 0.61 mm','Accessories',100,56.00,30,'yonex_arb.jpg','Active',1),('25SPO00005','VICTOR A970TD BADMINTON SHOES','HYPEREVA + ENERGYMAX3.0 + TPU','Shoes',50,420.00,15,'product_1766236493_6946a14db59fa.jpeg','Active',2),('25SPO00006','YONEX 88D PRO','SO PRO','Racquet',100,899.00,5,'yonex_88d.jpg','Active',1),('25SPO00007','VICTOR Thruster K Ryuga II','Lee Zii Jia\'s weapon of choice. Enhanced with WES 2.0 technology for sharper smash angles and aggressive offensive play.','Racquet',31,750.00,5,'product_1766236885_6946a2d58b13a.jpg','Active',17),('25SPO00008','VICTOR Brave Sword 12','A classic speed racket with a diamond-shaped frame that cuts through air, making it excellent for fast defense and net play.','Racquet',30,300.00,5,'product_1766240269_6946b00d6e2cc.jpg','Active',15),('25SPO00009','VICTOR Auraspeed 90K II','The flagship of the Speed series, featuring the Whipping Enhancement System for snappy shots and rapid recovery.','Racquet',30,755.00,5,'product_1766240338_6946b052ab929.jpg','Active',3),('25SPO00010','VICTOR Jetspeed S 12 II','A continuation of the legendary speed series, offering a stable frame and precise control for fast-paced doubles rallies.','Racquet',30,800.00,5,'product_1766240560_6946b130dc564.webp','Active',1),('25SPO00011','VICTOR DriveX 10 Metallic','Integrates Metallic Carbon Fiber for a crisp hitting feel and stiff feedback, designed for players who value stability and precision.','Racquet',30,888.00,5,'product_1766240705_6946b1c1345d5.jpeg','Active',13),('25SPO00012','LI-NING Axforce 80','Chen Long\'s choice. Features a slim shaft and heavy head balance for thunderous smashes. Great for both singles and doubles.','Racquet',30,888.00,5,'product_1766240783_6946b20fb7a05.jpeg','Active',12),('25SPO00013','LI-NING Tectonic 9','Built on the Tectonic energy platform, this racket offers high elasticity and quick rebound for continuous, high-intensity attacks.','Racquet',30,750.00,5,'product_1766240833_6946b241ef050.jpg','Active',14),('25SPO00014','LI-NING Tectonic 9','Features distinctive air-stream channels in the frame head to reduce drag, providing stability and power for heavy hitters.','Racquet',30,750.00,5,'product_1766240885_6946b2752e47e.jpg','Active',16),('25SPO00015','LI-NING 3D Calibar 900','Designed with a high-tech geometric frame to significantly reduce air resistance, maximizing swing speed and smash power.','Racquet',30,750.00,5,'product_1766240923_6946b29b9cb85.jpg','Active',2),('25SPO00016','LI-NING G-Force 9000','An ultra-lightweight racket capable of withstanding high tension. High value for money and easy to handle for intermediate players.','Racquet',30,750.00,5,'product_1766240977_6946b2d1863d4.jpeg','Active',17),('25SPO00017','YONEX Aerosensa 30 (Tube of 12)','The standard for international tournaments. Premium goose feathers ensure consistent flight trajectory and excellent durability.','Accessories',50,125.00,10,'product_1766241101_6946b34d9e9e7.jpeg','Active',1),('25SPO00018','VICTOR Champion No.1 (Tube of 12)','Victor\'s best-selling duck feather shuttlecock. Known for its flight stability and outstanding durability-to-price ratio.','Accessories',50,120.00,10,'product_1766241152_6946b38080a0e.jpg','Active',14),('25SPO00019','RSL Classic Tourney No.1 (Tube of 12)','A legendary shuttlecock made from premium goose feathers. Offers perfect speed and solid feel, favored by club players worldwide.','Accessories',50,120.00,10,'product_1766241190_6946b3a611c58.jpeg','Active',17),('25SPO00020','YONEX AC102EX Super Grap (3-in-1)','The world\'s best-selling overgrip. Provides a tacky feel and excellent sweat absorption. Comes in a pack of 3.','Accessories',50,10.00,10,'product_1766241239_6946b3d7826b6.jpeg','Active',12),('25SPO00021','VICTOR GR233 Overgrip','A high-friction overgrip with a moisture-wicking texture, ensuring a non-slip hold even during intense matches.','Accessories',50,10.00,10,'product_1766241281_6946b40120711.jpeg','Active',14),('25SPO00022','YONEX BG66 Ultimax String','With a 0.65mm thin gauge, this string delivers instant repulsion power and a crisp hitting sound. The top choice for pros.','Accessories',50,40.00,10,'product_1766241334_6946b4364f72d.jpg','Active',17),('25SPO00023','LI-NING No.1 Boost String','Li-Ning\'s primary string, famous for its incredible repulsion power and high durability. Offers a hard hitting feel.','Accessories',50,45.00,10,'product_1766241411_6946b483efaf8.jpeg','Active',2),('25SPO00024','Molten GG7X Official Match Ball','FIBA approved competition ball featuring a premium composite leather cover for superior grip and ball control.','Balls',30,150.00,5,'product_1766241502_6946b4de5056a.jpg','Active',12),('25SPO00025','Spalding NBA Street Outdoor Basketball','Built for concrete courts. Features a durable rubber cover and deep channels for great grip and longevity outdoors.','Balls',30,140.00,5,'product_1766241573_6946b525720aa.jpg','Active',17),('25SPO00026','Nike Dominate 8P Basketball','An outdoor basketball with a textured pebble pattern that repels dust and dirt, offering excellent grip and durability.','Balls',30,140.00,5,'product_1766241613_6946b54dd2cee.jpg','Active',13),('25SPO00027','Wilson Evolution Indoor Basketball','The #1 indoor game ball in America. Microfiber composite leather provides a soft touch, while moisture-wicking channels enhance grip.','Balls',30,100.00,5,'product_1766241675_6946b58b8265a.jpg','Active',3),('25SPO00028','Adidas Al Rihla League Ball','Inspired by the 2022 World Cup. Seamless TSBE construction ensures predictable flight and low water uptake.','Balls',30,250.00,5,'product_1766241725_6946b5bdc48d5.jpg','Active',16),('25SPO00029','Nike Strike Premier League Ball','Designed for everyday play with Nike Aerowsculpt grooves for consistent flight spin and accurate passing.','Balls',30,200.00,5,'product_1766241766_6946b5e6372d8.jpeg','Active',17),('25SPO00030','Molten Vantaggio 5000 Football','A professional-grade match ball using Acentec technology for a seamless surface, superior water resistance, and true flight.','Balls',30,300.00,5,'product_1766241809_6946b6114083e.jpeg','Active',3),('25SPO00031','Kipsta F500 Hybrid Soccer Ball','Hybrid construction combining machine stitching and bonded seams for increased durability and pressure retention.','Balls',30,280.00,5,'product_1766241872_6946b650bd92d.jpg','Active',17),('25SPO00032','YONEX Power Cushion 65 Z3','Worn by Kento Momota. Equipped with Power Cushion+ for shock absorption and repulsion, offering an all-around comfortable fit.','Shoes',40,550.00,10,'product_1766241975_6946b6b78e2b0.jpg','Active',12),('25SPO00033','VICTOR P9200 Crown Collection','Tai Tzu Ying\'s exclusive series. Features a thick midsole for enhanced shock protection and a high-cut design for ankle stability.','Shoes',40,550.00,10,'product_1766242051_6946b703a53bc.jpg','Active',12),('25SPO00034','LI-NING Saga II Pro','Features full-palm BOOM technology for lightweight bounce and cushioning. High-strength upper ensures a locked-in feel.','Shoes',40,550.00,10,'product_1766242113_6946b741b573f.jpeg','Active',16),('25SPO00035','Nike Air Zoom Pegasus 40','A daily workhorse runner with Nike React foam and dual Zoom Air units for a responsive, springy ride.','Shoes',40,550.00,10,'product_1766242173_6946b77d810e7.jpeg','Active',3),('25SPO00036','Adidas Ultraboost Light','The lightest Ultraboost ever. Made with Light BOOST material for epic energy return and cloud-like comfort over long distances.','Shoes',40,300.00,10,'product_1766242209_6946b7a163264.jpeg','Active',17),('25SPO00037','Under Armour Curry Flow 10','Steph Curry\'s 10th signature shoe. UA Flow cushioning technology eliminates the rubber outsole for insane grip and lightness.','Shoes',40,350.00,10,'product_1766242250_6946b7ca1e4a0.jpeg','Active',13),('25SPO00038','Speed Rope Pro (Adjustable)','Professional steel wire jump rope with a ball-bearing system for smooth, fast rotation. Fully adjustable length for fitness training.','Equipment',50,30.00,15,'product_1766242317_6946b80d28357.jpeg','Active',15),('25SPO00039','Non-Slip Yoga Mat 6mm','Made from eco-friendly TPE material. The 6mm thickness offers optimal joint cushioning, while the dual-sided texture prevents slipping.','Equipment',50,28.00,15,'product_1766242368_6946b8405ffa7.jpeg','Active',16),('25SPO00040','100PLUS Isotonic Drink (Zero Sugar)','Malaysia\'s most popular isotonic sports drink, making it perfect for quenching thirst after exercise.','Beverages',100,2.50,30,'product_1766242521_6946b8d98ed09.jpeg','Active',2),('25SPO00041','White Monster Energy Drink','White Monster, officially known as Monster Energy Zero Ultra, (or Monster Ultra for short), is a zero-calorie, zero-sugar beverage.','Beverages',100,2.50,30,'product_1766242623_6946b93f4c6c7.jpg','Active',3),('25SPO00042','Pocari Sweat Ion Supply Drink','This electrolyte supplement drink, developed in Japan, has a composition similar to human body fluids. It can be gently and quickly absorbed by the body, effectively preventing dehydration.','Beverages',100,2.50,30,'product_1766242762_6946b9ca80435.png','Active',17),('25SPO00043','YONEX Arcsaber 11 Pro','An upgraded classic for control players, offering extended shuttle hold time and pinpoint accuracy for all-around play.','Racquet',30,950.00,5,'product_1766236688_6946a21096d4d.jpg','Active',2),('25SPO00044','YONEX Duora Z-Strike','Features a unique dual-frame design: box-shape for power forehands and aero-shape for quick backhands. Ideal for technical players.','Racquet',30,888.00,5,'product_1766236770_6946a2625b391.jpeg','Active',17);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_details`
--

DROP TABLE IF EXISTS `purchase_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_details` (
  `DetailID` int NOT NULL AUTO_INCREMENT,
  `Quantity` int NOT NULL,
  `Subtotal` decimal(7,2) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `ProductID` char(10) NOT NULL,
  `PurchaseID` char(10) NOT NULL,
  PRIMARY KEY (`DetailID`),
  KEY `ProductID_idx` (`ProductID`),
  KEY `PurchaseID_idx` (`PurchaseID`),
  CONSTRAINT `ProductID` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`),
  CONSTRAINT `PurchaseID` FOREIGN KEY (`PurchaseID`) REFERENCES `purchase_order` (`PurchaseID`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_details`
--

LOCK TABLES `purchase_details` WRITE;
/*!40000 ALTER TABLE `purchase_details` DISABLE KEYS */;
INSERT INTO `purchase_details` VALUES (35,10,8590.00,'2025-12-21 01:47:27','Active','25SPO00002','25PUR00001');
/*!40000 ALTER TABLE `purchase_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order`
--

DROP TABLE IF EXISTS `purchase_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order` (
  `PurchaseID` char(10) NOT NULL,
  `TotalAmount` decimal(7,2) NOT NULL,
  `Status` enum('Pending','Approved','Shipping','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `UserID` int NOT NULL,
  `SupplierID` int NOT NULL,
  PRIMARY KEY (`PurchaseID`),
  KEY `UserID_idx` (`UserID`),
  KEY `SupplierID_idx` (`SupplierID`),
  CONSTRAINT `OrderToSupplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`),
  CONSTRAINT `UserID` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order`
--

LOCK TABLES `purchase_order` WRITE;
/*!40000 ALTER TABLE `purchase_order` DISABLE KEYS */;
INSERT INTO `purchase_order` VALUES ('25PUR00001',8590.00,'Pending','2025-12-21 01:47:27','Active',1,1);
/*!40000 ALTER TABLE `purchase_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `SupplierID` int NOT NULL AUTO_INCREMENT,
  `SupplierName` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL DEFAULT '^[a-zA-Z0-9._%+-]+@gmail\\.com$',
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ImagePath` varchar(100) NOT NULL DEFAULT 'default.jpg',
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`SupplierID`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (0,'Default','Default','2025-12-16 20:43:47','Default','Inactive'),(1,'Sunrise-Sports SDN BHD','sunyonex@gmail.com','2025-11-26 13:42:19','1766222867_sunrise.png','Active'),(2,'MERU SPORT SDN BHD','meruvictor@gmail.com','2025-11-26 13:42:19','1766222486_Gemini_Generated_Image_lcgcjrlcgcjrlcgc.png','Active'),(3,'Sunlight Galaxy SDN BH','liningmy@gmail.com.my','2025-11-26 13:42:19','1766224056_sunlight (1).png','Active'),(12,'Apex Athletics','info@apexathletics.com','2025-12-20 17:12:07','1766222383_apex.png','Active'),(13,'Velocity Vigor','support@velocityvigor.com','2025-12-20 17:12:07','1766222388_velocity.png','Active'),(14,'IronCore Sports','sales@ironcoresports.com','2025-12-20 17:12:07','1766222395_ironcore.png','Active'),(15,'Summit & Stride','hello@summitandstride.com','2025-12-20 17:12:07','1766222399_summit.png','Active'),(16,'Kinetix Lab','innovate@kinetixlab.io','2025-12-20 17:12:07','1766222403_kinetix.png','Active'),(17,'Pulse Activewear','contact@pulseactivewear.com','2025-12-20 17:12:07','1766222407_Gemini_Generated_Image_7fammt7fammt7fam.png','Active');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `UserID` int NOT NULL AUTO_INCREMENT,
  `UserName` varchar(80) NOT NULL,
  `Email` varchar(100) NOT NULL DEFAULT '^[a-zA-Z0-9._%+-]+@gmail\\.com$',
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Role` enum('Unknown','Employee','Manager') NOT NULL DEFAULT 'Unknown',
  `ImagePath` varchar(100) DEFAULT NULL,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `ImagePath_UNIQUE` (`ImagePath`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (0,'Unregistered User','system@2entral.com','2025-12-20 23:24:58','Unknown','default.png','Inactive'),(1,'Ter Kean Sen','huoyan0928@gmail.com','2025-11-23 20:02:18','Manager','images.jpeg','Active'),(2,'Chan Jun Di','chanjundi04@gmail.com','2025-11-26 13:40:01','Employee','default_user_2.png','Active'),(3,'Ong Ei Jie','ongej-am24@student.tarc.edu.my','2025-11-26 13:40:01','Employee',NULL,'Active'),(4,'Ter Kean Sen','terks-am24@student.tarc.edu.my','2025-12-16 18:17:27','Employee',NULL,'Active'),(5,'Phon Mei Xin','phonmx-am24@student.tarc.edu.my','2025-12-16 15:51:37','Manager',NULL,'Active'),(6,'Tek Shao Xian ','teksx-am24@student.tarc.edu.my','2025-12-16 15:51:37','Employee',NULL,'Active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-21  2:33:17
