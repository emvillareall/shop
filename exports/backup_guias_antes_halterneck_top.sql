-- MySQL dump 10.13  Distrib 8.4.9, for Linux (x86_64)
--
-- Host: localhost    Database: guias
-- ------------------------------------------------------
-- Server version	8.4.9

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

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `accion` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valores_antes` json DEFAULT NULL,
  `valores_despues` json DEFAULT NULL,
  `ip` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_entidad_entidad_id_index` (`entidad`,`entidad_id`),
  KEY `audit_logs_accion_created_at_index` (`accion`,`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (18,NULL,'REINICIO_OPERATIVO','base_datos',NULL,'{\"registros\": {\"pagos\": 4, \"ventas\": 0, \"compras\": 4, \"pedidos\": 4, \"productos\": 16, \"audit_logs\": 17, \"compra_items\": 110, \"pagos_paypal\": 0, \"venta_abonos\": 0, \"pagos_payphone\": 4, \"stock_reservas\": 0, \"detalle_pedidos\": 4, \"colores_productos\": 110, \"producto_imagenes\": 0, \"pagos_transferencias\": 0, \"inventario_movimientos\": 115, \"payment_webhook_events\": 0}}','{\"fecha\": \"2026-09-04T23:13:14+00:00\", \"estado\": \"limpieza_operativa_completada\", \"respaldo\": \"/var/backups/bootyfitness/bootyfitness-antes-reinicio-operativo-20260904-231122.sql.gz\"}',NULL,'Codex CLI','2026-09-05 03:13:14','2026-09-05 03:13:14');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorias_productos`
--

DROP TABLE IF EXISTS `categorias_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `linea_ropa_id` bigint unsigned NOT NULL,
  `estado_categoria` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categorias_productos_linea_ropa_id_foreign` (`linea_ropa_id`),
  CONSTRAINT `categorias_productos_linea_ropa_id_foreign` FOREIGN KEY (`linea_ropa_id`) REFERENCES `lineas_ropa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_productos`
--

LOCK TABLES `categorias_productos` WRITE;
/*!40000 ALTER TABLE `categorias_productos` DISABLE KEYS */;
INSERT INTO `categorias_productos` VALUES (8,'BLUSAS BÁSICAS',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(9,'CHAQUETAS',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(10,'CONJUNTO SHORTS',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(11,'LEGGINS',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(12,'SHORTS',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(13,'TOPS',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(14,'TOPS LARGO',4,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(15,'LEGGINS SCULPT SCRUNCH',4,'1','2026-05-18 21:07:30','2026-05-18 21:07:30'),(16,'TOPS',2,'1','2026-05-18 21:07:30','2026-05-18 21:07:30'),(17,'VESTIDO SHORTS',2,'1','2026-05-18 21:07:30','2026-05-18 21:07:30'),(18,'TOP',2,'1','2026-05-18 21:14:59','2026-05-18 21:14:59');
/*!40000 ALTER TABLE `categorias_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombres_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ciudad_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_clientes` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_cedula_clientes_unique` (`cedula_clientes`),
  UNIQUE KEY `clientes_email_clientes_unique` (`email_clientes`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Eduardo','Villareal','1725042871','0990706472','Santo Domingo','Av. Chone Km 2','ejemplo@ejemplo.com','1','2026-05-16 02:17:54','2026-09-05 01:06:38'),(2,'edu','villa','1725042854','0990706582','quito','Av. Chone Km 2','eduedmy6485@gmail.com','1','2026-05-18 19:21:07','2026-05-18 19:24:38');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colores`
--

DROP TABLE IF EXISTS `colores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `colores_codigo_color_unique` (`codigo_color`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colores`
--

LOCK TABLES `colores` WRITE;
/*!40000 ALTER TABLE `colores` DISABLE KEYS */;
INSERT INTO `colores` VALUES (1,'azul','#0a17e1',NULL,'2026-05-15 18:00:04'),(2,'gris azulado','#a2acc1',NULL,'2026-05-15 18:00:04'),(3,'celeste','#4ff0fd',NULL,'2026-05-15 18:00:04'),(4,'rosado goma de mascar','#fc4ebf',NULL,'2026-05-15 18:00:04'),(5,'rosa','#f781ce',NULL,'2026-05-15 18:00:04'),(6,'morado haspe','#ae9dc6',NULL,'2026-05-15 18:00:04'),(7,'negro','#000000',NULL,'2026-05-15 18:00:04'),(8,'azul marino','#1b1d81',NULL,'2026-05-15 18:00:04'),(9,'moca','#c19574',NULL,'2026-05-15 18:00:04'),(10,'cafe','#8a2f2f',NULL,'2026-05-15 18:00:04'),(11,'rojo','#ea1515',NULL,'2026-05-15 18:00:04'),(12,'beige','#fff8de',NULL,'2026-05-08 20:46:31'),(13,'azul petroleo','#026881',NULL,'2026-05-15 18:00:04'),(14,'lila','#f2deff',NULL,'2026-05-15 18:00:04'),(15,'blanco','#ffffff',NULL,'2026-05-15 18:00:04'),(16,'blanco hueso','#F8F4E3','2026-05-15 18:00:04','2026-05-15 18:00:04'),(17,'blanco perla','#F7F6F2','2026-05-15 18:00:04','2026-05-15 18:00:04'),(18,'marfil','#FFFFF0','2026-05-15 18:00:04','2026-05-15 18:00:04'),(19,'crema','#FFFDD0','2026-05-15 18:00:04','2026-05-15 18:00:04'),(20,'beige','#F5F5DC','2026-05-15 18:00:04','2026-05-15 18:00:04'),(21,'arena','#C2B280','2026-05-15 18:00:04','2026-05-15 18:00:04'),(22,'champagne','#F7E7CE','2026-05-15 18:00:04','2026-05-15 18:00:04'),(23,'nude claro','#E3BC9A','2026-05-15 18:00:04','2026-05-15 18:00:04'),(24,'nude medio','#C6866A','2026-05-15 18:00:04','2026-05-15 18:00:04'),(25,'taupe','#8B8589','2026-05-15 18:00:04','2026-05-15 18:00:04'),(26,'gris claro','#D3D3D3','2026-05-15 18:00:04','2026-05-15 18:00:04'),(27,'gris medio','#808080','2026-05-15 18:00:04','2026-05-15 18:00:04'),(28,'gris oscuro','#4A4A4A','2026-05-15 18:00:04','2026-05-15 18:00:04'),(29,'gris humo','#6E6E6E','2026-05-15 18:00:04','2026-05-15 18:00:04'),(30,'plomo','#5C5C5C','2026-05-15 18:00:04','2026-05-15 18:00:04'),(31,'negro humo','#1C1C1C','2026-05-15 18:00:04','2026-05-15 18:00:04'),(32,'negro carbon','#2B2B2B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(33,'azul rey','#0033A0','2026-05-15 18:00:04','2026-05-15 18:00:04'),(34,'azul electrico','#007FFF','2026-05-15 18:00:04','2026-05-15 18:00:04'),(35,'azul noche','#0B1D39','2026-05-15 18:00:04','2026-05-15 18:00:04'),(36,'azul petroleo oscuro','#004953','2026-05-15 18:00:04','2026-05-15 18:00:04'),(37,'azul acero','#4682B4','2026-05-15 18:00:04','2026-05-15 18:00:04'),(38,'azul denim','#1560BD','2026-05-15 18:00:04','2026-05-15 18:00:04'),(39,'azul cielo','#87CEEB','2026-05-15 18:00:04','2026-05-15 18:00:04'),(40,'celeste pastel','#BDEBFF','2026-05-15 18:00:04','2026-05-15 18:00:04'),(41,'turquesa','#40E0D0','2026-05-15 18:00:04','2026-05-15 18:00:04'),(42,'aqua','#00FFFF','2026-05-15 18:00:04','2026-05-15 18:00:04'),(43,'menta azulada','#7FFFD4','2026-05-15 18:00:04','2026-05-15 18:00:04'),(44,'verde','#008000','2026-05-15 18:00:04','2026-05-15 18:00:04'),(45,'verde quemado','#4A5D23','2026-05-15 18:00:04','2026-05-15 18:00:04'),(46,'verde militar','#4B5320','2026-05-15 18:00:04','2026-05-15 18:00:04'),(47,'verde oliva','#808000','2026-05-15 18:00:04','2026-05-15 18:00:04'),(48,'verde botella','#006A4E','2026-05-15 18:00:04','2026-05-15 18:00:04'),(49,'verde bosque','#228B22','2026-05-15 18:00:04','2026-05-15 18:00:04'),(50,'verde esmeralda','#50C878','2026-05-15 18:00:04','2026-05-15 18:00:04'),(51,'verde jade','#00A86B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(52,'verde menta','#98FF98','2026-05-15 18:00:04','2026-05-15 18:00:04'),(53,'verde pistacho','#93C572','2026-05-15 18:00:04','2026-05-15 18:00:04'),(54,'verde lima','#32CD32','2026-05-15 18:00:04','2026-05-15 18:00:04'),(55,'verde neon','#39FF14','2026-05-15 18:00:04','2026-05-15 18:00:04'),(56,'verde salvia','#9CAF88','2026-05-15 18:00:04','2026-05-15 18:00:04'),(57,'verde musgo','#8A9A5B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(58,'rosado claro','#FFB6C1','2026-05-15 18:00:04','2026-05-15 18:00:04'),(59,'rosado pastel','#FFD1DC','2026-05-15 18:00:04','2026-05-15 18:00:04'),(60,'fucsia','#FF00FF','2026-05-15 18:00:04','2026-05-15 18:00:04'),(61,'magenta','#C71585','2026-05-15 18:00:04','2026-05-15 18:00:04'),(62,'coral','#FF7F50','2026-05-15 18:00:04','2026-05-15 18:00:04'),(63,'salmon','#FA8072','2026-05-15 18:00:04','2026-05-15 18:00:04'),(64,'rojo cereza','#D2042D','2026-05-15 18:00:04','2026-05-15 18:00:04'),(65,'rojo vino','#722F37','2026-05-15 18:00:04','2026-05-15 18:00:04'),(66,'vino','#800020','2026-05-15 18:00:04','2026-05-15 18:00:04'),(67,'borgona','#7C0A02','2026-05-15 18:00:04','2026-05-15 18:00:04'),(68,'granate','#7B1113','2026-05-15 18:00:04','2026-05-15 18:00:04'),(69,'terracota rojizo','#B55239','2026-05-15 18:00:04','2026-05-15 18:00:04'),(70,'morado','#800080','2026-05-15 18:00:04','2026-05-15 18:00:04'),(71,'morado oscuro','#4B0082','2026-05-15 18:00:04','2026-05-15 18:00:04'),(72,'uva','#6F2DA8','2026-05-15 18:00:04','2026-05-15 18:00:04'),(73,'lavanda','#E6E6FA','2026-05-15 18:00:04','2026-05-15 18:00:04'),(74,'lila pastel','#C8A2C8','2026-05-15 18:00:04','2026-05-15 18:00:04'),(75,'malva','#B784A7','2026-05-15 18:00:04','2026-05-15 18:00:04'),(76,'purpura','#6A0DAD','2026-05-15 18:00:04','2026-05-15 18:00:04'),(77,'amarillo','#FFFF00','2026-05-15 18:00:04','2026-05-15 18:00:04'),(78,'amarillo pastel','#FFFACD','2026-05-15 18:00:04','2026-05-15 18:00:04'),(79,'amarillo mostaza','#FFDB58','2026-05-15 18:00:04','2026-05-15 18:00:04'),(80,'dorado','#D4AF37','2026-05-15 18:00:04','2026-05-15 18:00:04'),(81,'oro viejo','#CFB53B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(82,'naranja','#FFA500','2026-05-15 18:00:04','2026-05-15 18:00:04'),(83,'naranja neon','#FF5F1F','2026-05-15 18:00:04','2026-05-15 18:00:04'),(84,'mandarina','#F28500','2026-05-15 18:00:04','2026-05-15 18:00:04'),(85,'durazno','#FFE5B4','2026-05-15 18:00:04','2026-05-15 18:00:04'),(86,'terracota','#E2725B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(87,'cafe chocolate','#7B3F00','2026-05-15 18:00:04','2026-05-15 18:00:04'),(88,'cafe oscuro','#4B2E2B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(89,'cafe claro','#A67B5B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(90,'caramelo','#AF6E4D','2026-05-15 18:00:04','2026-05-15 18:00:04'),(91,'camel','#C19A6B','2026-05-15 18:00:04','2026-05-15 18:00:04'),(92,'tabaco','#715D47','2026-05-15 18:00:04','2026-05-15 18:00:04'),(93,'canela','#D2691E','2026-05-15 18:00:04','2026-05-15 18:00:04'),(94,'cobre','#B87333','2026-05-15 18:00:04','2026-05-15 18:00:04'),(95,'plateado','#C0C0C0','2026-05-15 18:00:04','2026-05-15 18:00:04'),(96,'grafito','#383838','2026-05-15 18:00:04','2026-05-15 18:00:04'),(97,'rose gold','#B76E79','2026-05-15 18:00:04','2026-05-15 18:00:04'),(98,'neon rosa','#FF10F0','2026-05-15 18:00:04','2026-05-15 18:00:04'),(99,'neon azul','#1F51FF','2026-05-15 18:00:04','2026-05-15 18:00:04'),(100,'neon amarillo','#FFFF33','2026-05-15 18:00:04','2026-05-15 18:00:04'),(101,'neon naranja','#FF6700','2026-05-15 18:00:04','2026-05-15 18:00:04'),(123,'celeste bebe','#B7E4F9','2026-05-15 20:41:24','2026-05-15 20:41:24'),(124,'fucsia neon','#FF1493','2026-05-15 20:41:24','2026-05-15 20:41:24'),(125,'morado claro','#C084FC','2026-05-15 20:41:24','2026-05-15 20:41:24'),(126,'rosa barbie','#E0218A','2026-05-15 20:41:24','2026-05-15 20:41:24'),(127,'vino oscuro','#4B0000','2026-05-15 20:41:24','2026-05-15 20:41:24'),(130,'coffy','#6F4E37','2026-05-18 21:14:59','2026-05-18 21:14:59'),(131,'vainilla','#F3E5AB','2026-05-18 21:14:59','2026-05-18 21:14:59'),(132,'verde aqua','#00BFA6','2026-05-18 21:14:59','2026-05-18 21:14:59');
/*!40000 ALTER TABLE `colores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colores_productos`
--

DROP TABLE IF EXISTS `colores_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `colores_productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `colores_id` bigint unsigned NOT NULL,
  `cantidad_por_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock_por_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `talla_por_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `colores_productos_colores_id_foreign` (`colores_id`),
  KEY `colores_productos_producto_id_foreign` (`producto_id`),
  CONSTRAINT `colores_productos_colores_id_foreign` FOREIGN KEY (`colores_id`) REFERENCES `colores` (`id`),
  CONSTRAINT `colores_productos_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colores_productos`
--

LOCK TABLES `colores_productos` WRITE;
/*!40000 ALTER TABLE `colores_productos` DISABLE KEYS */;
/*!40000 ALTER TABLE `colores_productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compra_items`
--

DROP TABLE IF EXISTS `compra_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compra_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `compra_id` bigint unsigned NOT NULL,
  `producto_id` bigint unsigned DEFAULT NULL,
  `colores_id` bigint unsigned DEFAULT NULL,
  `talla` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `stock_ingresado` int NOT NULL DEFAULT '0',
  `costo_unitario_pesos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `costo_unitario_dolares` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal_pesos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal_dolares` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVO',
  `observacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compra_items_producto_id_foreign` (`producto_id`),
  KEY `compra_items_compra_id_producto_id_index` (`compra_id`,`producto_id`),
  KEY `compra_items_compra_id_colores_id_talla_index` (`compra_id`,`colores_id`,`talla`),
  CONSTRAINT `compra_items_compra_id_foreign` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`),
  CONSTRAINT `compra_items_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compra_items`
--

LOCK TABLES `compra_items` WRITE;
/*!40000 ALTER TABLE `compra_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `compra_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `envio_compra` double NOT NULL DEFAULT '0',
  `total_pesos_compra` double NOT NULL DEFAULT '0',
  `proveedor_id` bigint unsigned NOT NULL,
  `estado_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `total_dolares_compra` double NOT NULL DEFAULT '0',
  `importacion_compra` double NOT NULL DEFAULT '0',
  `total_final_compra` double NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compras_proveedor_id_foreign` (`proveedor_id`),
  CONSTRAINT `compras_proveedor_id_foreign` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compras`
--

LOCK TABLES `compras` WRITE;
/*!40000 ALTER TABLE `compras` DISABLE KEYS */;
/*!40000 ALTER TABLE `compras` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_pedidos`
--

DROP TABLE IF EXISTS `detalle_pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cantidad_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `nombre_producto_snapshot` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pedido_id` bigint unsigned NOT NULL,
  `id_color_producto` int DEFAULT NULL,
  `color_snapshot` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `talla_por_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `talla_snapshot` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio_unitario_snapshot` decimal(12,2) DEFAULT NULL,
  `subtotal_linea` decimal(12,2) DEFAULT NULL,
  `descuento_linea` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuesto_linea` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado_dtpedidos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalle_pedidos_producto_id_foreign` (`producto_id`),
  KEY `detalle_pedidos_pedido_id_foreign` (`pedido_id`),
  CONSTRAINT `detalle_pedidos_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `detalle_pedidos_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedidos`
--

LOCK TABLES `detalle_pedidos` WRITE;
/*!40000 ALTER TABLE `detalle_pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `detalle_pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventario_movimientos`
--

DROP TABLE IF EXISTS `inventario_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_movimientos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `colores_productos_id` bigint unsigned DEFAULT NULL,
  `pedido_id` bigint unsigned DEFAULT NULL,
  `compra_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `tipo_movimiento` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int NOT NULL,
  `stock_antes` int DEFAULT NULL,
  `stock_despues` int DEFAULT NULL,
  `motivo` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventario_movimientos_user_id_foreign` (`user_id`),
  KEY `inventario_movimientos_producto_id_created_at_index` (`producto_id`,`created_at`),
  KEY `inventario_movimientos_tipo_movimiento_created_at_index` (`tipo_movimiento`,`created_at`),
  KEY `inventario_movimientos_pedido_id_compra_id_index` (`pedido_id`,`compra_id`),
  CONSTRAINT `inventario_movimientos_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `inventario_movimientos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventario_movimientos`
--

LOCK TABLES `inventario_movimientos` WRITE;
/*!40000 ALTER TABLE `inventario_movimientos` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventario_movimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lineas_ropa`
--

DROP TABLE IF EXISTS `lineas_ropa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lineas_ropa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_linea` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_linea` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lineas_ropa_nombre_linea_unique` (`nombre_linea`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lineas_ropa`
--

LOCK TABLES `lineas_ropa` WRITE;
/*!40000 ALTER TABLE `lineas_ropa` DISABLE KEYS */;
INSERT INTO `lineas_ropa` VALUES (1,'GymShark','1',NULL,'2026-05-08 20:46:31'),(2,'linea_brasilena','1',NULL,'2026-05-08 20:46:31'),(3,'segunda_piel','1',NULL,'2026-05-08 20:46:31'),(4,'americana','1',NULL,'2026-05-08 20:46:31');
/*!40000 ALTER TABLE `lineas_ropa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2014_10_12_100000_create_password_resets_table',1),(4,'2019_08_19_000000_create_failed_jobs_table',1),(5,'2019_12_14_000001_create_personal_access_tokens_table',1),(6,'2024_01_02_041412_create_clientes_table',1),(7,'2024_01_02_041428_create_tiendas_table',1),(8,'2024_01_02_041455_create_pedidos_table',1),(9,'2024_01_09_160050_create_proveedores_table',1),(10,'2024_01_09_221539_create_compras_table',1),(11,'2024_01_09_221540_create_productos_table',1),(12,'2024_01_11_015440_create_parametros_table',1),(13,'2024_01_12_192908_create_detalle_pedidos_table',1),(14,'2024_07_15_025251_create_colores_table',1),(15,'2024_07_15_025259_create_colores__productos_table',1),(16,'2025_03_24_210204_add_imagen_to_productos_table',1),(17,'2025_06_20_214803_create_lineas_ropa_table',1),(18,'2025_06_20_214826_create_categorias_productos_table',1),(19,'2025_06_20_214833_add_categoria_producto_id_to_productos_table',1),(20,'2026_01_11_012634_add_talla_por_color_to_colores_productos_table',2),(21,'2026_01_11_013034_add_color_y_talla_to_detalle_pedidos_table',2),(22,'2026_05_04_120000_align_schema_with_bootyfitness_sql',2),(23,'2026_05_04_121000_align_money_columns_precision',3),(24,'2026_05_05_140000_add_workflow_columns_to_pedidos_table',4),(25,'2026_05_05_150000_add_snapshot_columns_to_detalle_pedidos_table',5),(26,'2026_05_05_170000_create_pagos_tables',6),(27,'2026_05_05_193000_add_processing_columns_to_payment_webhook_events_table',7),(28,'2026_05_05_201000_add_replay_columns_to_payment_webhook_events_table',8),(29,'2026_05_05_214000_create_audit_logs_table',9),(30,'2026_05_05_220000_create_inventario_movimientos_table',9),(31,'2026_05_05_231000_create_compra_items_table',10),(32,'2026_05_06_180000_create_stock_reservas_table',11),(33,'2026_05_06_210000_create_ventas_table',12),(34,'2026_05_11_100000_add_modalidad_to_ventas_and_create_venta_abonos_table',13),(35,'2026_05_12_090000_add_apartado_tracking_columns_to_ventas_table',14),(36,'2026_05_12_153000_add_comprobante_to_venta_abonos_table',15),(37,'2026_05_12_180000_create_payment_gateway_configs_table',16),(38,'2026_05_12_190000_enhance_pagos_payphone_for_box_flow',17),(39,'2026_05_16_090000_create_producto_imagenes_table',18),(40,'2026_05_17_150000_add_color_fields_to_producto_imagenes_table',18),(41,'2026_05_18_160000_add_promocion_columns_to_productos_table',19),(42,'2026_09_04_180500_add_product_permissions_to_users_table',20);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint unsigned NOT NULL,
  `metodo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `moneda` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `referencia_externa` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `revisado_por` bigint unsigned DEFAULT NULL,
  `revisado_at` timestamp NULL DEFAULT NULL,
  `aprobado_at` timestamp NULL DEFAULT NULL,
  `rechazado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_revisado_por_foreign` (`revisado_por`),
  KEY `pagos_pedido_id_estado_index` (`pedido_id`,`estado`),
  KEY `pagos_metodo_estado_index` (`metodo`,`estado`),
  CONSTRAINT `pagos_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `pagos_revisado_por_foreign` FOREIGN KEY (`revisado_por`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos_paypal`
--

DROP TABLE IF EXISTS `pagos_paypal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_paypal` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pago_id` bigint unsigned NOT NULL,
  `paypal_order_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paypal_capture_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paypal_status` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_paypal_pago_id_foreign` (`pago_id`),
  CONSTRAINT `pagos_paypal_pago_id_foreign` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_paypal`
--

LOCK TABLES `pagos_paypal` WRITE;
/*!40000 ALTER TABLE `pagos_paypal` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_paypal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos_payphone`
--

DROP TABLE IF EXISTS `pagos_payphone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_payphone` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pago_id` bigint unsigned NOT NULL,
  `client_transaction_id` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payphone_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorization_code` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payphone_status` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_code` int DEFAULT NULL,
  `transaction_status` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `raw_request_json` json DEFAULT NULL,
  `raw_response_json` json DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pagos_payphone_client_tx_unique` (`client_transaction_id`),
  KEY `pagos_payphone_pago_id_foreign` (`pago_id`),
  CONSTRAINT `pagos_payphone_pago_id_foreign` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_payphone`
--

LOCK TABLES `pagos_payphone` WRITE;
/*!40000 ALTER TABLE `pagos_payphone` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_payphone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos_transferencias`
--

DROP TABLE IF EXISTS `pagos_transferencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pagos_transferencias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pago_id` bigint unsigned NOT NULL,
  `banco_origen` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titular_origen` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_referencia` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_transferencia` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_transferencias_pago_id_foreign` (`pago_id`),
  CONSTRAINT `pagos_transferencias_pago_id_foreign` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos_transferencias`
--

LOCK TABLES `pagos_transferencias` WRITE;
/*!40000 ALTER TABLE `pagos_transferencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos_transferencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parametros`
--

DROP TABLE IF EXISTS `parametros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametros` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_parametro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_parametro` double(8,5) NOT NULL DEFAULT '0.00000',
  `estado_parametro` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametros`
--

LOCK TABLES `parametros` WRITE;
/*!40000 ALTER TABLE `parametros` DISABLE KEYS */;
INSERT INTO `parametros` VALUES (1,'recargo_paypal',0.05250,'1',NULL,NULL),(2,'cambio_moneda',0.06240,'1',NULL,NULL);
/*!40000 ALTER TABLE `parametros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
INSERT INTO `password_reset_tokens` VALUES ('Admin_2025@ejemplo.com','$2y$12$4dVAvKAO/70ViWqV.iAJx.151WEEjhLTiTwoBHfusv73BSHCv8j9i','2026-09-05 01:02:04');
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_gateway_configs`
--

DROP TABLE IF EXISTS `payment_gateway_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateway_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gateway` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `environment` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `base_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret_key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `merchant_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `webhook_id` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_secret` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verify_path` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `strict_webhook` tinyint(1) NOT NULL DEFAULT '1',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_gateway_configs_gateway_unique` (`gateway`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_gateway_configs`
--

LOCK TABLES `payment_gateway_configs` WRITE;
/*!40000 ALTER TABLE `payment_gateway_configs` DISABLE KEYS */;
INSERT INTO `payment_gateway_configs` VALUES (1,'payphone','production',1,'https://pay.payphonetodoesposible.com/api',NULL,'eyJpdiI6Im5sY2ZLRGdZOXREZmk1OHdlV0hicnc9PSIsInZhbHVlIjoiUXFIUUlOdG9KclQ4VHA5S0pXUzdXRTQ1cHppK2VvNFBaS0JVY2tmWjFYMFRVVmZmekFzZXVSY1M5bzMxRHRkWlJlbnpsNVkxNC9RbjNRcWhySHJwejU0TVpRSjJadXdxS09FcjNCVEdXRGJmSnRlb3JKcDdXUVBWbG44TTJ5amVQTFYxdmdCZDdWY2liZmpnc3ZKaGlBZDZGYTBFeHhYT2JTT2VTMTI3eGljNjBwemtQSlo2a3JBcTd4R1FRME5tUGZYckJyVWVUbERTa3FhRk03MzBBMHN5b0xIVTNGMThtQ0s4N1RCUDhPTEFScTh6VmNmN3l5dCtjYitFTElFMERwUkxQRENOazJtUzlDZ0h4b3VKQkI2Z09SZmNta3BpSE91ZWpMRjVvM3h4a293U3J4L3VURGtHOTZjZnRPRVp6RmhWWE9Ua1hYT2NyampQS1NwMGdLTitMaW5OWHZkYlJwaUJPWnl4WGcyWTVsNFBDUmJ4bHhFQS90cGpBaU10LzlsWWtrY0JCeUE1ak9xeVhDSWVROVNPSDNNRlBHUlErL3NNTkdzSG9obmxxUkpSSVlkemdybVgvUzdIazZqbiIsIm1hYyI6IjljYjU3ZWMyZWUyYTE0ODk4MDM3OTEzMzg4MTM2ZWM0N2M5YzBlNmVmMDFmOTkyZjg5NmM5YmMyYTQwMjFhZTQiLCJ0YWciOiIifQ==',NULL,'USD',NULL,NULL,'/sale/{id}',1,'{\"store_id\": \"e6dc8561-8a87-49f5-b3c0-5c7d7a806e0a\", \"iva_percent\": 0, \"payphone_fee_percent\": 7}','2026-05-12 23:03:42','2026-05-18 19:23:58');
/*!40000 ALTER TABLE `payment_gateway_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_webhook_events`
--

DROP TABLE IF EXISTS `payment_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `proveedor` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `evento` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `evento_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `payload_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nonce` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transmitido_at` timestamp NULL DEFAULT NULL,
  `firma_valida` tinyint(1) NOT NULL DEFAULT '0',
  `procesado` tinyint(1) NOT NULL DEFAULT '0',
  `intentos` smallint unsigned NOT NULL DEFAULT '0',
  `error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pago_id` bigint unsigned DEFAULT NULL,
  `procesado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_webhook_events_proveedor_evento_id_index` (`proveedor`,`evento_id`),
  KEY `payment_webhook_events_pago_id_foreign` (`pago_id`),
  KEY `pwe_provider_hash_idx` (`proveedor`,`payload_hash`),
  KEY `pwe_provider_nonce_idx` (`proveedor`,`nonce`),
  CONSTRAINT `payment_webhook_events_pago_id_foreign` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_webhook_events`
--

LOCK TABLES `payment_webhook_events` WRITE;
/*!40000 ALTER TABLE `payment_webhook_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_pedido` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clientes_id` bigint unsigned NOT NULL,
  `tienda_id` bigint unsigned NOT NULL,
  `subtotal_pedido` double DEFAULT '0',
  `iva_pedido` double DEFAULT '0',
  `descuentos_pedido` double DEFAULT '0',
  `total_pedido` double DEFAULT '0',
  `estado_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN ESPERA',
  `estado_pedido` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE_PAGO',
  `estado_pago` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SIN_PAGO',
  `estado_envio` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SIN_ENVIO',
  `confirmado_at` timestamp NULL DEFAULT NULL,
  `pagado_at` timestamp NULL DEFAULT NULL,
  `despachado_at` timestamp NULL DEFAULT NULL,
  `cancelado_at` timestamp NULL DEFAULT NULL,
  `estado_pedidos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedidos_clientes_id_foreign` (`clientes_id`),
  KEY `pedidos_tienda_id_foreign` (`tienda_id`),
  KEY `pedidos_codigo_pedido_index` (`codigo_pedido`),
  KEY `pedidos_estado_pedido_index` (`estado_pedido`),
  KEY `pedidos_estado_pago_index` (`estado_pago`),
  KEY `pedidos_estado_envio_index` (`estado_envio`),
  CONSTRAINT `pedidos_clientes_id_foreign` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `pedidos_tienda_id_foreign` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `producto_imagenes`
--

DROP TABLE IF EXISTS `producto_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `producto_imagenes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` bigint unsigned NOT NULL,
  `color_id` bigint unsigned DEFAULT NULL,
  `color_nombre` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_normalizado` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peso_original` bigint unsigned DEFAULT NULL,
  `peso_optimizado` bigint unsigned DEFAULT NULL,
  `ancho` int unsigned DEFAULT NULL,
  `alto` int unsigned DEFAULT NULL,
  `orden` int unsigned NOT NULL DEFAULT '0',
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_producto_imagenes_producto_orden` (`producto_id`,`orden`),
  KEY `idx_producto_imagenes_producto_color` (`producto_id`,`color_id`),
  KEY `idx_producto_imagenes_producto_color_norm` (`producto_id`,`color_normalizado`),
  CONSTRAINT `producto_imagenes_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `producto_imagenes`
--

LOCK TABLES `producto_imagenes` WRITE;
/*!40000 ALTER TABLE `producto_imagenes` DISABLE KEYS */;
/*!40000 ALTER TABLE `producto_imagenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `imagen_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cantidad_compra_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stock_venta_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `precio_pesos_producto` double NOT NULL DEFAULT '0',
  `precio_dolares_producto` double NOT NULL DEFAULT '0',
  `precio_venta_producto` double NOT NULL DEFAULT '0',
  `precio_promocional` decimal(12,2) DEFAULT NULL,
  `promocion_activa` tinyint(1) NOT NULL DEFAULT '0',
  `promocion_fecha_inicio` datetime DEFAULT NULL,
  `promocion_fecha_fin` datetime DEFAULT NULL,
  `promocion_etiqueta` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `compras_id` bigint unsigned NOT NULL,
  `estado_producto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `categoria_producto_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productos_compras_id_foreign` (`compras_id`),
  KEY `productos_categoria_producto_id_foreign` (`categoria_producto_id`),
  CONSTRAINT `productos_categoria_producto_id_foreign` FOREIGN KEY (`categoria_producto_id`) REFERENCES `categorias_productos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `productos_compras_id_foreign` FOREIGN KEY (`compras_id`) REFERENCES `compras` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tienda_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_proveedor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proveedores_cedula_proveedor_unique` (`cedula_proveedor`),
  UNIQUE KEY `proveedores_email_proveedor_unique` (`email_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Proveedor 1','Proveedor','1',NULL,NULL,NULL,NULL,'1','2026-05-15 22:58:46','2026-05-15 22:58:46'),(3,'Proveedor Línea Americana','Proveedor','Línea Americana',NULL,NULL,NULL,NULL,'1','2026-05-15 20:41:24','2026-05-15 20:41:24'),(4,'Proveedor Lote Mixto BootyFitness','Proveedor','Lote Mixto',NULL,NULL,NULL,NULL,'1','2026-05-18 21:07:30','2026-05-18 21:07:30'),(5,'Proveedor Línea Brasileña','Proveedor','Línea Brasileña',NULL,NULL,NULL,NULL,'1','2026-05-18 21:14:59','2026-05-18 21:14:59');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_reservas`
--

DROP TABLE IF EXISTS `stock_reservas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_reservas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `producto_id` bigint unsigned NOT NULL,
  `color_id` bigint unsigned NOT NULL,
  `talla` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` int unsigned NOT NULL,
  `expires_at` timestamp NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reserva_session_variante` (`session_id`,`producto_id`,`color_id`,`talla`),
  KEY `stock_reservas_session_id_index` (`session_id`),
  KEY `stock_reservas_producto_id_index` (`producto_id`),
  KEY `stock_reservas_color_id_index` (`color_id`),
  KEY `stock_reservas_talla_index` (`talla`),
  KEY `stock_reservas_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_reservas`
--

LOCK TABLES `stock_reservas` WRITE;
/*!40000 ALTER TABLE `stock_reservas` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_reservas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tiendas`
--

DROP TABLE IF EXISTS `tiendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tiendas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres_dueno_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos_dueno_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula_dueno_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_dueno_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion_dueno_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_dueno_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_tienda` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tiendas_email_dueno_tienda_unique` (`email_dueno_tienda`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tiendas`
--

LOCK TABLES `tiendas` WRITE;
/*!40000 ALTER TABLE `tiendas` DISABLE KEYS */;
INSERT INTO `tiendas` VALUES (1,'Booty Fitness Pruebas','Administrador','Pruebas','1700000001','0999999901','Dirección de pruebas','tienda1@pruebas.local','1','2025-06-26 23:38:10','2026-05-08 20:46:31'),(2,'Tienda Pruebas 2','Administrador','Pruebas','1700000002','0999999902','Dirección de pruebas','tienda2@pruebas.local','1',NULL,'2026-05-08 20:46:31');
/*!40000 ALTER TABLE `tiendas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_user` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `acceso_administrativo_completo` tinyint(1) NOT NULL DEFAULT '0',
  `puede_ver_productos` tinyint(1) NOT NULL DEFAULT '0',
  `puede_crear_productos` tinyint(1) NOT NULL DEFAULT '0',
  `puede_editar_productos` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Admin','Admin_2025@ejemplo.com',NULL,'$2y$12$uEUI/AZDnqm3V5yzl4JLDOPHy.Iod0Qce4Jqz0.lU/Zg9fPPOz9xK','1',1,0,0,0,'M146pM2TzAGdZPS6yh2CqqmeCoXiRnH5E0ydMVFsyTEcLFnzDBnRGAjRzTWh','2025-06-21 03:28:51','2026-05-08 20:46:31'),(3,'Administrador Pruebas','newline@local.com',NULL,'$2y$12$bHSAz0GfVVIgCF1w13cTu.O7FHRfQtVM0XWHgrjS7A92vh7KOMvZm','1',1,0,0,0,NULL,'2026-01-09 20:54:35','2026-05-08 20:46:31'),(4,'Noemi Solorzano','noemisolorzano12@outlook.com',NULL,'$2y$12$O/1cWHnpCEvEwtsEIL.B7.aFZOcvNq5A/PyQkL6KpBP9M00LKHmay','1',1,0,0,0,NULL,'2026-09-05 02:33:45','2026-09-05 02:49:39');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venta_abonos`
--

DROP TABLE IF EXISTS `venta_abonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `venta_abonos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `venta_id` bigint unsigned NOT NULL,
  `pedido_id` bigint unsigned NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `referencia` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fecha_abono` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_abonos_venta_id_foreign` (`venta_id`),
  KEY `venta_abonos_pedido_id_fecha_abono_index` (`pedido_id`,`fecha_abono`),
  CONSTRAINT `venta_abonos_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `venta_abonos_venta_id_foreign` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venta_abonos`
--

LOCK TABLES `venta_abonos` WRITE;
/*!40000 ALTER TABLE `venta_abonos` DISABLE KEYS */;
/*!40000 ALTER TABLE `venta_abonos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventas`
--

DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ventas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` bigint unsigned NOT NULL,
  `clientes_id` bigint unsigned DEFAULT NULL,
  `tienda_id` bigint unsigned DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado_venta` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BORRADOR',
  `modalidad_venta` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CONTADO',
  `fecha_venta` timestamp NULL DEFAULT NULL,
  `fecha_proximo_abono` timestamp NULL DEFAULT NULL,
  `fecha_ultimo_abono` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ventas_pedido_id_unique` (`pedido_id`),
  KEY `ventas_estado_venta_created_at_index` (`estado_venta`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventas`
--

LOCK TABLES `ventas` WRITE;
/*!40000 ALTER TABLE `ventas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventas` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-04 19:56:34
