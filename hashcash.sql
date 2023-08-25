-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.27-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.5.0.6680
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for hashcash
CREATE DATABASE IF NOT EXISTS `hashcash` /*!40100 DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci */;
USE `hashcash`;

-- Dumping structure for table hashcash.activity
CREATE TABLE IF NOT EXISTS `activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `hash_id` int(11) DEFAULT NULL,
  `value` decimal(11,2) NOT NULL,
  `activity_date` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user` (`user_id`),
  KEY `hash` (`hash_id`),
  CONSTRAINT `hash` FOREIGN KEY (`hash_id`) REFERENCES `hash` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table hashcash.hash
CREATE TABLE IF NOT EXISTS `hash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(140) NOT NULL,
  `value` decimal(11,2) NOT NULL,
  `last_update` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6763 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table hashcash.points
CREATE TABLE IF NOT EXISTS `points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `available` datetime NOT NULL,
  `redeemed` datetime DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table hashcash.trades
CREATE TABLE IF NOT EXISTS `trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hash_id` int(11) NOT NULL,
  `cost` decimal(11,2) NOT NULL,
  `volume` int(11) NOT NULL,
  `original_volume` int(11) NOT NULL,
  `type` varchar(25) NOT NULL,
  `status` varchar(50) NOT NULL,
  `trade_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hashid` (`hash_id`),
  KEY `userid` (`user_id`),
  CONSTRAINT `hashid` FOREIGN KEY (`hash_id`) REFERENCES `hash` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `userid` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=822 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table hashcash.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `display` varchar(28) NOT NULL,
  `token` varchar(32) NOT NULL,
  `email` varchar(140) DEFAULT NULL,
  `pass` varchar(64) NOT NULL,
  `tag` varchar(140) DEFAULT NULL,
  `img` varchar(32) DEFAULT NULL,
  `cash` double(11,2) NOT NULL DEFAULT 0.00,
  `research` int(11) NOT NULL DEFAULT 0,
  `research_date` datetime DEFAULT NULL,
  `lotto` date DEFAULT NULL,
  `IP` varchar(50) DEFAULT NULL,
  `reset` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `display` (`display`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table hashcash.wip
CREATE TABLE IF NOT EXISTS `wip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(140) NOT NULL,
  `active` datetime NOT NULL,
  `listed` datetime DEFAULT NULL,
  `point_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `point_id` (`point_id`),
  CONSTRAINT `point_id` FOREIGN KEY (`point_id`) REFERENCES `points` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
