
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `businesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `businesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner_id` bigint unsigned DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UTC',
  `subscription_tier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `businesses_owner_id_foreign` (`owner_id`),
  CONSTRAINT `businesses_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `businesses` WRITE;
/*!40000 ALTER TABLE `businesses` DISABLE KEYS */;
INSERT INTO `businesses` VALUES (1,'Acme Corp',1,'UTC','basic','2026-06-22 15:29:28','2026-06-22 15:29:28');
/*!40000 ALTER TABLE `businesses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `idempotency_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `idempotency_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_body` json NOT NULL,
  `status_code` int NOT NULL DEFAULT '200',
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_keys_business_id_key_endpoint_unique` (`business_id`,`key`,`endpoint`),
  CONSTRAINT `idempotency_keys_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `idempotency_keys` WRITE;
/*!40000 ALTER TABLE `idempotency_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `idempotency_keys` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `reserved` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventories_business_id_product_id_unique` (`business_id`,`product_id`),
  KEY `inventories_product_id_foreign` (`product_id`),
  CONSTRAINT `inventories_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `inventories` WRITE;
/*!40000 ALTER TABLE `inventories` DISABLE KEYS */;
INSERT INTO `inventories` VALUES (1,1,1,500,0,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(2,1,2,500,0,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(3,1,3,500,0,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(4,1,4,500,0,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(5,1,5,500,0,'2026-06-22 15:29:28','2026-06-22 15:29:28');
/*!40000 ALTER TABLE `inventories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_22_191719_create_personal_access_tokens_table',1),(5,'2026_06_22_192115_create_businesses_table',1),(6,'2026_06_22_192115_create_orders_table',1),(7,'2026_06_22_192115_create_products_table',1),(8,'2026_06_22_192115_create_sales_table',1),(9,'2026_06_22_192116_create_idempotency_keys_table',1),(10,'2026_06_22_192116_create_inventories_table',1),(11,'2026_06_22_192116_create_order_audits_table',1),(12,'2026_06_22_192116_create_order_items_table',1),(13,'2026_06_22_192116_create_processed_webhooks_table',1),(14,'2026_06_22_192249_add_business_id_to_users_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `business_id` bigint unsigned NOT NULL,
  `from_state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_state` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_audits_order_id_foreign` (`order_id`),
  KEY `order_audits_user_id_foreign` (`user_id`),
  KEY `order_audits_business_id_order_id_index` (`business_id`,`order_id`),
  CONSTRAINT `order_audits_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_audits_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_audits` WRITE;
/*!40000 ALTER TABLE `order_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_audits` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  KEY `order_items_business_id_order_id_index` (`business_id`,`order_id`),
  CONSTRAINT `order_items_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,5,1,251.50,251.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(2,1,2,5,3,251.50,754.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(3,1,3,3,2,308.27,616.54,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(4,1,4,1,2,381.70,763.40,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(5,1,5,5,1,251.50,251.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(6,1,6,4,1,218.68,218.68,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(7,1,7,1,3,381.70,1145.10,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(8,1,8,5,2,251.50,503.00,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(9,1,9,3,3,308.27,924.81,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(10,1,10,1,2,381.70,763.40,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(11,1,11,5,4,251.50,1006.00,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(12,1,12,4,2,218.68,437.36,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(13,1,13,5,3,251.50,754.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(14,1,14,3,4,308.27,1233.08,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(15,1,15,1,3,381.70,1145.10,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(16,1,16,4,4,218.68,874.72,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(17,1,17,5,2,251.50,503.00,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(18,1,18,4,2,218.68,437.36,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(19,1,19,2,1,319.10,319.10,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(20,1,20,4,1,218.68,218.68,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(21,1,21,5,4,251.50,1006.00,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(22,1,22,4,4,218.68,874.72,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(23,1,23,5,2,251.50,503.00,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(24,1,24,4,4,218.68,874.72,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(25,1,25,3,1,308.27,308.27,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(26,1,26,4,2,218.68,437.36,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(27,1,27,2,4,319.10,1276.40,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(28,1,28,1,1,381.70,381.70,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(29,1,29,1,3,381.70,1145.10,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(30,1,30,5,1,251.50,251.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(31,1,31,5,1,251.50,251.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(32,1,32,4,4,218.68,874.72,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(33,1,33,3,2,308.27,616.54,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(34,1,34,1,1,381.70,381.70,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(35,1,35,2,3,319.10,957.30,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(36,1,36,5,3,251.50,754.50,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(37,1,37,2,2,319.10,638.20,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(38,1,38,4,4,218.68,874.72,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(39,1,39,1,2,381.70,763.40,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(40,1,40,2,1,319.10,319.10,'2026-06-22 15:29:28','2026-06-22 15:29:28');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `idempotency_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_business_id_idempotency_key_unique` (`business_id`,`idempotency_key`),
  KEY `orders_user_id_foreign` (`user_id`),
  KEY `orders_business_id_status_index` (`business_id`,`status`),
  KEY `orders_business_id_created_at_index` (`business_id`,`created_at`),
  CONSTRAINT `orders_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,1,'paid',251.50,NULL,'2026-06-11 10:28:28','2026-06-22 15:29:28'),(2,1,1,'paid',754.50,NULL,'2026-05-31 13:51:28','2026-06-22 15:29:28'),(3,1,1,'paid',616.54,NULL,'2026-06-05 23:05:28','2026-06-22 15:29:28'),(4,1,1,'paid',763.40,NULL,'2026-06-13 17:09:28','2026-06-22 15:29:28'),(5,1,1,'paid',251.50,NULL,'2026-06-07 19:32:28','2026-06-22 15:29:28'),(6,1,1,'paid',218.68,NULL,'2026-06-10 15:17:28','2026-06-22 15:29:28'),(7,1,1,'paid',1145.10,NULL,'2026-05-23 17:21:28','2026-06-22 15:29:28'),(8,1,1,'paid',503.00,NULL,'2026-05-29 09:56:28','2026-06-22 15:29:28'),(9,1,1,'paid',924.81,NULL,'2026-05-23 22:48:28','2026-06-22 15:29:28'),(10,1,1,'paid',763.40,NULL,'2026-05-26 09:32:28','2026-06-22 15:29:28'),(11,1,1,'paid',1006.00,NULL,'2026-06-10 03:04:28','2026-06-22 15:29:28'),(12,1,1,'paid',437.36,NULL,'2026-06-17 16:38:28','2026-06-22 15:29:28'),(13,1,1,'paid',754.50,NULL,'2026-05-29 15:36:28','2026-06-22 15:29:28'),(14,1,1,'paid',1233.08,NULL,'2026-06-07 02:43:28','2026-06-22 15:29:28'),(15,1,1,'paid',1145.10,NULL,'2026-06-21 17:44:28','2026-06-22 15:29:28'),(16,1,1,'paid',874.72,NULL,'2026-06-04 07:00:28','2026-06-22 15:29:28'),(17,1,1,'paid',503.00,NULL,'2026-05-29 16:25:28','2026-06-22 15:29:28'),(18,1,1,'paid',437.36,NULL,'2026-06-16 16:31:28','2026-06-22 15:29:28'),(19,1,1,'paid',319.10,NULL,'2026-06-09 01:09:28','2026-06-22 15:29:28'),(20,1,1,'paid',218.68,NULL,'2026-06-22 09:53:28','2026-06-22 15:29:28'),(21,1,1,'paid',1006.00,NULL,'2026-06-15 22:40:28','2026-06-22 15:29:28'),(22,1,1,'paid',874.72,NULL,'2026-06-03 03:15:28','2026-06-22 15:29:28'),(23,1,1,'paid',503.00,NULL,'2026-05-25 02:35:28','2026-06-22 15:29:28'),(24,1,1,'paid',874.72,NULL,'2026-06-07 04:18:28','2026-06-22 15:29:28'),(25,1,1,'paid',308.27,NULL,'2026-06-07 06:01:28','2026-06-22 15:29:28'),(26,1,1,'paid',437.36,NULL,'2026-05-27 07:36:28','2026-06-22 15:29:28'),(27,1,1,'paid',1276.40,NULL,'2026-06-18 19:21:28','2026-06-22 15:29:28'),(28,1,1,'paid',381.70,NULL,'2026-05-30 07:05:28','2026-06-22 15:29:28'),(29,1,1,'paid',1145.10,NULL,'2026-05-28 01:20:28','2026-06-22 15:29:28'),(30,1,1,'paid',251.50,NULL,'2026-06-21 00:22:28','2026-06-22 15:29:28'),(31,1,1,'paid',251.50,NULL,'2026-05-28 02:06:28','2026-06-22 15:29:28'),(32,1,1,'paid',874.72,NULL,'2026-05-27 00:33:28','2026-06-22 15:29:28'),(33,1,1,'paid',616.54,NULL,'2026-06-08 19:05:28','2026-06-22 15:29:28'),(34,1,1,'paid',381.70,NULL,'2026-05-25 03:01:28','2026-06-22 15:29:28'),(35,1,1,'paid',957.30,NULL,'2026-06-21 04:47:28','2026-06-22 15:29:28'),(36,1,1,'paid',754.50,NULL,'2026-06-06 04:21:28','2026-06-22 15:29:28'),(37,1,1,'paid',638.20,NULL,'2026-06-06 18:03:28','2026-06-22 15:29:28'),(38,1,1,'paid',874.72,NULL,'2026-05-30 16:50:28','2026-06-22 15:29:28'),(39,1,1,'paid',763.40,NULL,'2026-05-26 08:50:28','2026-06-22 15:29:28'),(40,1,1,'paid',319.10,NULL,'2026-06-20 12:16:28','2026-06-22 15:29:28');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `processed_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `processed_webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `processed_webhooks_transaction_id_unique` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `processed_webhooks` WRITE;
/*!40000 ALTER TABLE `processed_webhooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `processed_webhooks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_business_id_id_index` (`business_id`,`id`),
  CONSTRAINT `products_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'molestiae ad',381.70,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(2,1,'aspernatur libero',319.10,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(3,1,'dolor voluptate',308.27,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(4,1,'mollitia autem',218.68,'2026-06-22 15:29:28','2026-06-22 15:29:28'),(5,1,'omnis ducimus',251.50,'2026-06-22 15:29:28','2026-06-22 15:29:28');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_order_id_foreign` (`order_id`),
  KEY `sales_product_id_foreign` (`product_id`),
  KEY `sales_business_id_created_at_index` (`business_id`,`created_at`),
  KEY `sales_business_id_product_id_index` (`business_id`,`product_id`),
  CONSTRAINT `sales_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_business_id_foreign` (`business_id`),
  CONSTRAINT `users_business_id_foreign` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Test User','test@example.com','2026-06-22 15:29:28','$2y$12$.OwBCQXzvcIIKIS8cwo35u37YIMmo9ImXKodsa1t1w8OTSxZaTdnm','BRn8hhHkqM','2026-06-22 15:29:28','2026-06-22 15:29:28');
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

