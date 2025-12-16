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
  `LogsDetails` varchar(100) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `ProductID` char(10) NOT NULL DEFAULT '2025DEF000',
  `UserID` int NOT NULL,
  PRIMARY KEY (`LogsID`),
  KEY `ProductID_idx` (`ProductID`),
  KEY `UserID_idx` (`UserID`),
  CONSTRAINT `Product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`),
  CONSTRAINT `User` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_logs`
--

LOCK TABLES `inventory_logs` WRITE;
/*!40000 ALTER TABLE `inventory_logs` DISABLE KEYS */;
INSERT INTO `inventory_logs` VALUES (14,'Update: LI-NING HALBERTEC 8000 BADMINTON RACQUECT BLUE/PINK New Shipment (35 ➡️ 36)','2025-12-15 15:58:07','Active','25BAD00001',1),(15,'User Login','2025-12-15 23:58:54','Active','2025DEF000',1),(16,'Update: LI-NING HALBERTEC 8000 BADMINTON RACQUECT BLUE/PINK Manual Adjustment (36 ➡️ 58)','2025-12-16 00:10:59','Active','25BAD00001',1),(17,'Update: LI-NING HALBERTEC 8000 BADMINTON RACQUECT BLUE/PINK New Shipment (58 ➡️ 68)','2025-12-16 00:11:19','Active','25BAD00001',1),(18,'Update: LI-NING HALBERTEC 8000 BADMINTON RACQUECT BLUE/PINK New Shipment (68 ➡️ 168)','2025-12-16 00:11:30','Active','25BAD00001',1),(19,'Update: YONEX ASTROX 100ZZ New Shipment (15 ➡️ 115)','2025-12-16 00:15:12','Active','25BAD00003',1),(20,'Edited user: Chan Jun Di (ID: 2)','2025-12-16 10:20:56','Active','25BAD00001',1),(21,'User Login','2025-12-16 18:17:52','Active','2025DEF000',4),(22,'User Login','2025-12-16 18:23:26','Active','2025DEF000',1),(23,'Edited user: Ter Kean (ID: 1)','2025-12-16 20:53:05','Active','25BAD00001',1),(24,'Edited user: Ter Kean Sen (ID: 1)','2025-12-16 20:53:11','Active','25BAD00001',1),(25,'User Logout','2025-12-16 21:00:54','Active','2025DEF000',1),(26,'User Login','2025-12-16 21:01:34','Active','2025DEF000',1),(27,'Purchase Order #25PUR00010: Added 30 units of \'YONEX NANOFLARE 800 PRO	\'.','2025-12-16 21:08:11','Active','25BAD00002',1),(28,'Updated Supplier: Sunrise-Sports SDN BH','2025-12-16 21:11:51','Active','2025DEF000',1),(29,'Updated Supplier: Sunrise-Sports SDN BHD','2025-12-16 21:12:04','Active','2025DEF000',1),(30,'Set Supplier Inactive: Sunrise-Sports SDN BHD','2025-12-16 21:12:09','Active','2025DEF000',1),(31,'Deleted Supplier: MERU SPORT SDN BHD','2025-12-16 21:14:51','Active','2025DEF000',1),(32,'Deleted Supplier: Sunrise-Sports SDN BHD','2025-12-16 21:14:54','Active','2025DEF000',1),(33,'Deleted Supplier: Sunrise-Sports SDN BHD','2025-12-16 21:20:49','Active','2025DEF000',1),(34,'Deleted Supplier: Sunrise-Sports SDN BHD','2025-12-16 21:24:56','Active','2025DEF000',1);
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
INSERT INTO `products` VALUES ('2025DEF000','Default','Default','Default',0,0.00,0,'Default','Inactive',0),('25BAD00001','LI-NING HALBERTEC 8000 BADMINTON RACQUECT BLUE/PINK','6.8mm slim shaft for faster swings and reduced drag High Modulus Carbon Fibre for strength and responsiveness.','Racquet',168,899.00,5,'product_1764817022_6930f87e16a03.png','Active',3),('25BAD00002','YONEX NANOFLARE 800 PRO	','ISOMETRIC technology continues to help the world’s greatest players achieve global success.','Racquet',45,859.00,5,'yonex_800.jpg','Active',1),('25BAD00003','YONEX ASTROX 100ZZ','For advanced players looking for immediate access to power to maintain a relentless attack','Racquet',115,950.00,5,'yonex_100zz.jpg','Active',1),('25BAD00006','YONEX 88D PRO','SO PRO','Racquest',100,899.00,5,'yonex_88d.jpg','Active',1),('25SHO00005','VICTOR A970TD BADMINTON SHOES','HYPEREVA + ENERGYMAX3.0 + TPU','Shoes',50,420.00,15,'victor_a970.jpg','Active',2),('25STR00004','YONEX STRING AEROBITE','Mains - 0.67 mm; Crosses - 0.61 mm','String',100,56.00,30,'yonex_arb.jpg','Active',1);
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_details`
--

LOCK TABLES `purchase_details` WRITE;
/*!40000 ALTER TABLE `purchase_details` DISABLE KEYS */;
INSERT INTO `purchase_details` VALUES (21,5,4495.00,'2025-11-26 14:35:24','Active','25BAD00001','25PUR00001'),(22,5,4495.00,'2025-11-26 14:35:24','Active','25BAD00002','25PUR00002'),(23,5,4750.00,'2025-11-26 14:35:24','Active','25BAD00003','25PUR00002'),(24,10,8990.00,'2025-11-26 14:35:24','Active','25BAD00001','25PUR00003'),(25,30,1680.00,'2025-11-26 14:35:24','Active','25STR00004','25PUR00004'),(26,10,8590.00,'2025-11-26 14:35:24','Active','25BAD00002','25PUR00004'),(27,15,13485.00,'2025-11-26 14:35:24','Active','25BAD00001','25PUR00005'),(28,10,9500.00,'2025-11-26 14:35:24','Active','25BAD00003','25PUR00006'),(29,70,3920.00,'2025-11-26 14:35:24','Active','25STR00004','25PUR00007'),(30,40,16900.00,'2025-11-26 14:35:24','Active','25SHO00005','25PUR00008'),(31,10,4200.00,'2025-12-08 09:15:49','Active','25SHO00005','25PUR00009'),(32,30,25770.00,'2025-12-16 21:08:11','Active','25BAD00002','25PUR00010');
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
INSERT INTO `purchase_order` VALUES ('25PUR00001',4495.00,'Pending','2025-11-26 13:46:05','Active',1,3),('25PUR00002',9245.00,'Pending','2025-11-26 13:46:05','Active',1,1),('25PUR00003',8990.00,'Pending','2025-11-26 13:46:05','Active',2,3),('25PUR00004',10270.00,'Pending','2025-11-26 13:46:05','Active',3,1),('25PUR00005',13485.00,'Pending','2025-11-26 13:46:05','Active',2,3),('25PUR00006',9500.00,'Pending','2025-11-26 13:46:05','Active',2,1),('25PUR00007',3920.00,'Pending','2025-11-26 13:46:05','Active',3,1),('25PUR00008',16900.00,'Pending','2025-11-26 14:35:07','Active',1,2),('25PUR00009',4200.00,'Pending','2025-12-08 09:15:49','Active',1,2),('25PUR00010',25770.00,'Pending','2025-12-16 21:08:11','Active',1,1);
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (0,'Default','Default','2025-12-16 20:43:47','Default','Inactive'),(1,'Sunrise-Sports SDN BHD','sunyonex@gmail.com','2025-11-26 13:42:19','images1.png','Inactive'),(2,'MERU SPORT SDN BHD','meruvictor@gmail.com','2025-11-26 13:42:19','images2.png','Active'),(3,'Sunlight Galaxy SDN BH','liningmy@gmail.com','2025-11-26 13:42:19','images3.png','Active');
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
  `Role` enum('Employee','Manager') NOT NULL DEFAULT 'Employee',
  `ImagePath` varchar(100) DEFAULT NULL,
  `IsActive` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `ImagePath_UNIQUE` (`ImagePath`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Ter Kean Sen','huoyan0928@gmail.com','2025-11-23 20:02:18','Manager','images.jpeg','Active'),(2,'Chan Jun Di','chanjundi04@gmail.com','2025-11-26 13:40:01','Employee','default_user_2.png','Inactive'),(3,'Ong Ei Jie','ongej-am24@student.tarc.edu.my','2025-11-26 13:40:01','Employee',NULL,'Active'),(4,'Ter Kean Sen','terks-am24@student.tarc.edu.my','2025-12-16 18:17:27','Employee',NULL,'Inactive'),(5,'Phon Mei Xin','phonmx-am24@student.tarc.edu.my','2025-12-16 15:51:37','Manager',NULL,'Active'),(6,'Tek Shao Xian ','teksx-am24@student.tarc.edu.my','2025-12-16 15:51:37','Employee',NULL,'Active');
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

-- Dump completed on 2025-12-16 21:42:11
