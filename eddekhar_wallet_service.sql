/*
SQLyog Ultimate v11.33 (64 bit)
MySQL - 10.4.27-MariaDB : Database - eddekhar_wallet_service
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `idempotency_keys` */

DROP TABLE IF EXISTS `idempotency_keys`;

CREATE TABLE `idempotency_keys` (
  `idempotency_key_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `idempotency_key` varchar(255) NOT NULL,
  `idempotency_endpoint` varchar(255) NOT NULL,
  `request_hash` varchar(64) NOT NULL COMMENT 'Hash of request body for validation',
  `request_body` text DEFAULT NULL,
  `response_code` int(11) unsigned NOT NULL,
  `response_body` text NOT NULL,
  `idempotency_key_created_at` datetime DEFAULT NULL,
  `idempotency_key_expired_at` datetime NOT NULL,
  PRIMARY KEY (`idempotency_key_id`),
  UNIQUE KEY `idempotency_key_987cda` (`idempotency_key`),
  KEY `idempotency_key_expires_at_98acde` (`idempotency_key_expired_at`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `idempotency_keys` */

insert  into `idempotency_keys`(`idempotency_key_id`,`idempotency_key`,`idempotency_endpoint`,`request_hash`,`request_body`,`response_code`,`response_body`,`idempotency_key_created_at`,`idempotency_key_expired_at`) values (95,'test-idempotency-123','deposit:41','b1b16977ced7c304d6c3d363d0d6595f0414601dae63d88ce4f8efac2015deb9','{\"transaction_amount\":5000,\"transaction_description\":\"Test duplicate deposit\"}',404,'{\"error\":\"Wallet not found\"}','2025-12-26 12:04:03','2025-12-27 12:04:03');

/*Table structure for table `transactions` */

DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `transaction_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` int(11) unsigned NOT NULL,
  `transaction_type` enum('deposit','withdraw','transfer_debit','transfer_credit') NOT NULL,
  `transaction_amount` bigint(20) unsigned NOT NULL COMMENT 'Amount in minor units',
  `transaction_balance_after` bigint(20) unsigned NOT NULL COMMENT 'Balance after transaction',
  `related_wallet_id` int(11) unsigned DEFAULT NULL COMMENT 'For transfers, the other wallet involved',
  `reference_id` varchar(100) DEFAULT NULL COMMENT 'External reference or idempotency key',
  `transaction_description` text DEFAULT NULL,
  `transaction_created_at` datetime NOT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `wallet_id_ac87de` (`wallet_id`),
  KEY `transaction_type_ac65aa` (`transaction_type`),
  KEY `transaction_created_at_abbe45` (`transaction_created_at`),
  KEY `reference_id_98aaee` (`reference_id`),
  KEY `related_wallet_id_76aeb1` (`related_wallet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `transactions` */

/*Table structure for table `wallets` */

DROP TABLE IF EXISTS `wallets`;

CREATE TABLE `wallets` (
  `wallet_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_owner_name` varchar(255) NOT NULL,
  `wallet_currency` varchar(3) NOT NULL,
  `wallet_balance` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Balance in minor units (e.g., cents)',
  `wallet_created_at` datetime NOT NULL,
  `wallet_updated_at` datetime NOT NULL,
  PRIMARY KEY (`wallet_id`),
  KEY `wallet_owner_name_65bcd1` (`wallet_owner_name`),
  KEY `wallet_currency_87abeb` (`wallet_currency`),
  CONSTRAINT `chk_balance` CHECK (`wallet_balance` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `wallets` */

insert  into `wallets`(`wallet_id`,`wallet_owner_name`,`wallet_currency`,`wallet_balance`,`wallet_created_at`,`wallet_updated_at`) values (50,'Faisal','PKR',0,'2025-12-26 12:04:27','2025-12-26 12:04:27');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
