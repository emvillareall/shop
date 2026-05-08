-- MySQL dump 10.13  Distrib 8.4.7, for Linux (x86_64)
--
-- Host: localhost    Database: guias
-- ------------------------------------------------------
-- Server version	8.4.7

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
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias_productos`
--

LOCK TABLES `categorias_productos` WRITE;
/*!40000 ALTER TABLE `categorias_productos` DISABLE KEYS */;
INSERT INTO `categorias_productos` VALUES (6,'Shorts',1,'1',NULL,NULL),(7,'Shorts',2,'1',NULL,NULL),(8,'Shorts',3,'1',NULL,NULL),(9,'Shorts',4,'1',NULL,NULL),(10,'Tops',1,'1',NULL,NULL),(11,'Tops',2,'1',NULL,NULL),(12,'Tops',3,'1',NULL,NULL),(13,'Tops',4,'1',NULL,NULL),(14,'Buzo',1,'1',NULL,NULL),(15,'Buzo',2,'1',NULL,NULL),(16,'Buzo',3,'1',NULL,NULL),(17,'Buzo',4,'1',NULL,NULL),(18,'Leggins',1,'1',NULL,NULL),(19,'Leggins',2,'1',NULL,NULL),(20,'Leggins',3,'1',NULL,NULL),(21,'Leggins',4,'1',NULL,NULL),(22,'Enterizos',1,'1',NULL,NULL),(23,'Enterizos',2,'1',NULL,NULL),(24,'Enterizos',3,'1',NULL,NULL),(25,'Enterizos',4,'1',NULL,NULL),(26,'Conjuntos',1,'1',NULL,NULL),(27,'Conjuntos',2,'1',NULL,NULL),(28,'Conjuntos',3,'1',NULL,NULL),(29,'Conjuntos',4,'1',NULL,NULL),(30,'Accesorios',1,'1',NULL,NULL),(31,'Accesorios',2,'1',NULL,NULL),(32,'Accesorios',3,'1',NULL,NULL),(33,'Accesorios',4,'1',NULL,NULL),(34,'Oversize',1,'1',NULL,NULL),(35,'Oversize',2,'1',NULL,NULL),(36,'Oversize',3,'1',NULL,NULL),(37,'Oversize',4,'1',NULL,NULL),(38,'Jogger',1,'1',NULL,NULL),(39,'Jogger',2,'1',NULL,NULL),(40,'Jogger',3,'1',NULL,NULL),(41,'Jogger',4,'1',NULL,NULL),(42,'Faldas',1,'1',NULL,NULL),(43,'Faldas',2,'1',NULL,NULL),(44,'Faldas',3,'1',NULL,NULL),(45,'Faldas',4,'1',NULL,NULL),(46,'Bodys',1,'1',NULL,NULL),(47,'Bodys',2,'1',NULL,NULL),(48,'Bodys',3,'1',NULL,NULL),(49,'Bodys',4,'1',NULL,NULL),(50,'Jackets',1,'1',NULL,NULL),(51,'Jackets',2,'1',NULL,NULL),(52,'Jackets',3,'1',NULL,NULL),(53,'Jackets',4,'1',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'No','ingresado','','','','',NULL,'1',NULL,NULL),(4,'Noemi Bety','Solorzano Guevara','1726439449','0978879775','Santo Domingo','Santo Domingo de los Tsachilas','emvillareall@unitec.com','1','2025-06-26 23:39:11','2025-06-26 23:39:11'),(5,'Sandra Guaman','Aime','0957022254','+593997892948','Duran','Oficina servientrega recreo 1ra etapa',NULL,'1','2025-10-03 21:50:40','2025-10-03 21:50:40'),(6,'Pablo','Abad','0705156776','0999616267','El Oro','Pasaje, Ochoa leon y azuay casa 326',NULL,'1','2025-10-03 21:51:47','2025-10-03 21:51:47'),(7,'Nallely','Castillo','0750174591','0989942061','El Oro','Santa Rosa Sixto y Eloy Alfaro en la tercena el pato',NULL,'1','2025-10-03 21:52:53','2025-10-03 21:52:53'),(8,'Andrea Nathaly','Rivas Sánchez','2300572308','0979557443','Babahoyo','Retiro en oficina de servientrega, Babahoyo, servientrega de la av. universitaria, frente a la universidad técnica de Babahoyo.',NULL,'1','2025-10-03 21:54:10','2025-10-03 21:54:10'),(9,'Sonia Elizabeth','Tayupanta Chipugsi','1727501809','0998116384','Loja','Comando de Policía Argentina y Bolivia',NULL,'1','2025-10-03 21:55:11','2025-10-03 21:55:11'),(10,'José Andrés','Marrero','21581054','0995336709','Azuay','Camilo Ponce Enrrique',NULL,'1','2025-10-03 22:03:57','2025-10-03 22:03:57'),(11,'Cedeño','Stefania Párraga','0986442092','0802254763','Portoviejo','Urbanización Colinas Ceibos frente a Portoaguas entrando por el restaurante Tronquito Ardiente casa n:24',NULL,'1','2025-10-03 22:47:34','2025-10-03 22:47:34'),(12,'María José','Borja Loyola','0603861782','0939176142','Gualaquiza','retirar de oficina',NULL,'1','2025-10-06 22:22:59','2025-10-06 22:22:59'),(13,'Andrea','Santin','1104688120001','0993386204','Quito','Calles: Av. Los Granados E 10-25 y Av. 6 de Diciembre/ Edificio Asosiación de Imbabureños / Segundo Piso, Gimnasio Total Fitness',NULL,'1','2025-10-06 22:25:10','2025-10-06 22:25:10'),(14,'Judith Janneth','Maldonado López','1401227408','0982947701','Morona Santiago','Santiago de Méndez/ Barrio Sixto Durán, calle cuenca junto al redondel la Chonta',NULL,'1','2025-10-06 22:26:10','2025-10-06 22:26:10'),(15,'Domenica','solis','0930000351','0988301714','Manta','Urbanización Ciudad del Sol Mz p15',NULL,'1','2025-10-07 21:03:09','2025-10-07 21:03:09'),(16,'Doménica','Cervantes','0106640683','0991039496','Cuenca','Av. 27 de febrero y Jacinto Flores',NULL,'1','2025-10-07 21:04:10','2025-10-07 21:04:10'),(17,'Vanessa','Aguilar','0705162873','0991832624','Machala','Retiro en oficina de la calle Colon Tinoco',NULL,'1','2025-10-07 21:09:50','2025-10-07 21:09:50'),(18,'Gabriela Lisseth','Benites Borja','0503459984','0998382443','Latacunga','retiro en Oficina Bellavista Av. Miguel Iturralde, Barrio San Silvestre',NULL,'1','2025-10-07 21:13:23','2025-10-07 21:13:23'),(19,'Cinthia Abigail','Núñez Chaquinga','1804526281','0995574813','Tungurahua - Pillaro','Retiro en oficina de servientrega Pillaro',NULL,'1','2025-10-07 21:15:18','2025-10-07 21:15:18'),(20,'Ines','Rodriguez','0920163995','0992091888','Guayaquil','Samanes 7 mz 2201 villa 13',NULL,'1','2025-10-07 21:16:43','2025-10-07 21:16:43'),(21,'Sandy','Burgos Triviño','1314573906','0999444022','Santa Ana-Manabi','Retiro en oficina',NULL,'1','2025-10-07 21:18:31','2025-10-07 21:18:31'),(22,'Daniela Juleisi','Pugo Zambrano','0929711109','0985944892','Tenguel','Retiro en oficina',NULL,'1','2025-10-07 21:19:32','2025-10-07 21:19:32'),(23,'Veronica Maricela','Guzhñay Rivas','0705946028','0939766475','Pasaje','Eloy Alfaro entre Piedrahita y olmedo frente a la plaza San Antonio comercial Morocho',NULL,'1','2025-10-08 22:40:04','2025-10-08 22:40:04'),(24,'Ailyn','Cardenas','1729345551','0983836620','Quito','Urb. Balcon del norte Kyrios casa 12 familia cárdenas',NULL,'1','2025-10-08 22:41:51','2025-10-08 22:41:51'),(25,'Diana Lady','Cueva Martínez','0928362284','0967088459','Milagro','Av. Victor H vicuña y Ezequiel calle diagonal a una panaderia',NULL,'1','2025-10-08 22:48:59','2025-10-08 22:48:59'),(26,'Tania Gabriela','Tipan Villalba','2000082475','0979415903','Galapagos - San Cristobal','Av. Loja y Guido Sánchez',NULL,'1','2025-10-08 22:51:58','2025-10-08 22:51:58'),(27,'Valeria KATHERINE','Angulo Cazares','0804137693','0968987442','ESMERALDAS','PARADA 12 avenida libertad arriba de la PANADERÍA LA SUPREMA',NULL,'1','2025-10-08 22:55:20','2025-10-08 22:55:20'),(28,'ee','ee','1234567890','0990706454','Santo Domingo','Av. Chone Km 2',NULL,'1','2026-01-12 22:47:54','2026-01-12 22:47:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colores`
--

LOCK TABLES `colores` WRITE;
/*!40000 ALTER TABLE `colores` DISABLE KEYS */;
INSERT INTO `colores` VALUES (1,'azul','#0a17e1',NULL,NULL),(2,'gris azulado','#a2acc1',NULL,NULL),(3,'celeste','#4ff0fd',NULL,NULL),(4,'rosado goma de mascar','#fc4ebf',NULL,NULL),(5,'rosa','#f781ce',NULL,NULL),(6,'morado haspe','#ae9dc6',NULL,NULL),(7,'negro','#000000',NULL,NULL),(8,'azul marino','#1b1d81',NULL,NULL),(9,'moca','#c19574',NULL,NULL),(10,'cafe','#8a2f2f',NULL,NULL),(11,'rojo','#ea1515',NULL,NULL),(12,'beige','#fff8de',NULL,NULL),(13,'azul petroleo','#026881',NULL,NULL),(14,'lila','#f2deff',NULL,NULL),(15,'blanco','#ffffff',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colores_productos`
--

LOCK TABLES `colores_productos` WRITE;
/*!40000 ALTER TABLE `colores_productos` DISABLE KEYS */;
INSERT INTO `colores_productos` VALUES (1,8,2,'2','0','XS','2025-07-17 00:21:44','2025-07-17 00:21:44'),(2,8,2,'2','0','S','2025-07-17 00:21:44','2025-07-17 00:21:44'),(3,8,2,'2','0','M','2025-07-17 00:21:44','2025-07-17 00:21:44'),(4,8,3,'2','0','XS','2025-07-17 00:21:44','2025-07-17 00:21:44'),(5,8,3,'2','0','S','2025-07-17 00:21:44','2025-07-17 00:21:44'),(6,8,3,'2','0','M','2025-07-17 00:21:44','2025-07-17 00:21:44'),(7,8,6,'2','0','XS','2025-07-17 00:21:44','2025-07-17 00:21:44'),(8,8,4,'2','0','XS','2025-07-17 00:21:44','2025-07-17 00:21:44'),(9,9,2,'2','0','S','2025-07-17 00:29:09','2025-07-17 00:29:09'),(10,9,2,'2','0','M','2025-07-17 00:29:09','2025-07-17 00:29:09'),(11,9,3,'2','0','S','2025-07-17 00:29:09','2025-07-17 00:29:09'),(12,9,3,'2','0','M','2025-07-17 00:29:09','2025-07-17 00:29:09'),(13,10,4,'2','1','S','2025-07-17 00:34:56','2025-07-17 00:34:56'),(14,10,4,'2','1','M','2025-07-17 00:34:56','2025-07-17 00:34:56'),(15,10,6,'2','1','S','2025-07-17 00:34:56','2025-07-17 00:34:56'),(16,10,6,'2','1','M','2025-07-17 00:34:56','2025-07-17 00:34:56'),(17,10,2,'2','1','S','2025-07-17 00:34:56','2025-07-17 00:34:56'),(18,10,2,'2','1','M','2025-07-17 00:34:56','2025-07-17 00:34:56'),(19,10,3,'2','1','S','2025-07-17 00:34:56','2025-07-17 00:34:56'),(20,10,3,'2','1','M','2025-07-17 00:34:56','2025-07-17 00:34:56'),(21,10,7,'2','2','S','2025-07-17 00:34:56','2025-07-17 00:34:56'),(22,10,7,'2','1','M','2025-07-17 00:34:56','2025-07-17 00:34:56'),(23,11,5,'3','0','S','2025-07-17 00:47:18','2025-07-17 00:47:18'),(24,11,5,'3','3','M','2025-07-17 00:47:18','2025-07-17 00:47:18'),(25,11,8,'3','3','M','2025-07-17 00:47:18','2025-07-17 00:47:18'),(26,11,7,'3','3','S','2025-07-17 00:47:18','2025-07-17 00:47:18'),(27,11,7,'3','3','M','2025-07-17 00:47:18','2025-07-17 00:47:18'),(28,12,5,'3','3','S','2025-07-17 00:53:26','2025-07-17 00:53:26'),(29,12,5,'3','3','M','2025-07-17 00:53:26','2025-07-17 00:53:26'),(30,12,7,'3','3','S','2025-07-17 00:53:26','2025-07-17 00:53:26'),(31,12,7,'3','3','M','2025-07-17 00:53:26','2025-07-17 00:53:26'),(32,12,2,'2','2','S','2025-07-17 00:53:26','2025-07-17 00:53:26'),(33,12,2,'2','2','M','2025-07-17 00:53:26','2025-07-17 00:53:26'),(34,13,5,'3','3','S','2025-07-17 00:56:23','2025-07-17 00:56:23'),(35,13,5,'3','3','M','2025-07-17 00:56:23','2025-07-17 00:56:23'),(36,13,7,'3','3','S','2025-07-17 00:56:23','2025-07-17 00:56:23'),(37,13,7,'3','3','M','2025-07-17 00:56:23','2025-07-17 00:56:23'),(38,14,5,'3','2','S','2025-07-17 01:02:33','2025-07-17 01:02:33'),(39,14,5,'3','3','M','2025-07-17 01:02:34','2025-07-17 01:02:34'),(40,14,8,'4','3','S','2025-07-17 01:02:34','2025-07-17 01:02:34'),(41,14,8,'4','4','M','2025-07-17 01:02:34','2025-07-17 01:02:34'),(42,14,7,'4','4','S','2025-07-17 01:02:34','2025-07-17 01:02:34'),(43,14,7,'4','3','M','2025-07-17 01:02:34','2025-07-17 01:02:34'),(44,14,9,'2','2','S','2025-07-17 01:02:34','2025-07-17 01:02:34'),(45,14,9,'2','2','M','2025-07-17 01:02:34','2025-07-17 01:02:34'),(46,15,10,'3','3','S','2025-07-17 01:08:01','2025-07-17 01:08:01'),(47,15,10,'3','3','M','2025-07-17 01:08:01','2025-07-17 01:08:01'),(48,15,11,'3','3','S','2025-07-17 01:08:01','2025-07-17 01:08:01'),(49,15,11,'3','3','M','2025-07-17 01:08:01','2025-07-17 01:08:01'),(50,15,7,'4','4','S','2025-07-17 01:08:01','2025-07-17 01:08:01'),(51,15,7,'4','4','M','2025-07-17 01:08:01','2025-07-17 01:08:01'),(52,15,12,'3','3','S','2025-07-17 01:08:01','2025-07-17 01:08:01'),(53,15,12,'3','3','M','2025-07-17 01:08:01','2025-07-17 01:08:01'),(54,16,7,'2','1','S','2025-07-17 03:26:50','2025-07-17 03:26:50'),(55,16,7,'2','1','M','2025-07-17 03:26:50','2025-07-17 03:26:50'),(56,16,6,'2','1','S','2025-07-17 03:26:50','2025-07-17 03:26:50'),(57,16,6,'2','2','M','2025-07-17 03:26:50','2025-07-17 03:26:50'),(58,16,4,'2','1','S','2025-07-17 03:26:50','2025-07-17 03:26:50'),(59,16,4,'2','1','M','2025-07-17 03:26:50','2025-07-17 03:26:50'),(60,17,7,'3','0','S','2025-07-17 03:37:03','2025-07-17 03:37:03'),(61,17,7,'3','0','M','2025-07-17 03:37:03','2025-07-17 03:37:03'),(62,17,13,'3','0','S','2025-07-17 03:37:03','2025-07-17 03:37:03'),(63,17,13,'3','0','M','2025-07-17 03:37:03','2025-07-17 03:37:03'),(64,18,15,'2','0','S','2025-07-17 18:33:18','2025-07-17 18:33:18'),(65,18,15,'2','0','L','2025-07-17 18:33:18','2025-07-17 18:33:18'),(66,18,14,'2','0','L','2025-07-17 18:33:18','2025-07-17 18:33:18'),(67,18,9,'2','0','S','2025-07-17 18:33:18','2025-07-17 18:33:18'),(68,18,9,'2','0','M','2025-07-17 18:33:18','2025-07-17 18:33:18'),(69,18,9,'2','0','L','2025-07-17 18:33:18','2025-07-17 18:33:18'),(70,18,7,'3','0','M','2025-07-17 18:33:18','2025-07-17 18:33:18'),(71,18,7,'3','0','L','2025-07-17 18:33:18','2025-07-17 18:33:18'),(72,19,7,'3','3','S','2025-07-17 18:35:36','2025-07-17 18:35:36'),(73,19,7,'3','3','M','2025-07-17 18:35:36','2025-07-17 18:35:36');
/*!40000 ALTER TABLE `colores_productos` ENABLE KEYS */;
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
  `envio_compra` double(8,2) NOT NULL DEFAULT '0.00',
  `total_pesos_compra` double(8,2) NOT NULL DEFAULT '0.00',
  `proveedor_id` bigint unsigned NOT NULL,
  `estado_compra` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `total_dolares_compra` double(8,2) NOT NULL DEFAULT '0.00',
  `importacion_compra` double(8,2) NOT NULL DEFAULT '0.00',
  `total_final_compra` double(8,2) NOT NULL DEFAULT '0.00',
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
INSERT INTO `compras` VALUES (2,'bras_2025_001','Paquete de ropa linea brasilena',0.00,0.00,2,'1',2093.40,0.00,2093.40,'2025-07-11 21:15:28','2025-07-11 21:15:28');
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
  `pedido_id` bigint unsigned NOT NULL,
  `id_color_producto` int DEFAULT NULL,
  `talla_por_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_dtpedidos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detalle_pedidos_producto_id_foreign` (`producto_id`),
  KEY `detalle_pedidos_pedido_id_foreign` (`pedido_id`),
  CONSTRAINT `detalle_pedidos_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `detalle_pedidos_producto_id_foreign` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_pedidos`
--

LOCK TABLES `detalle_pedidos` WRITE;
/*!40000 ALTER TABLE `detalle_pedidos` DISABLE KEYS */;
INSERT INTO `detalle_pedidos` VALUES (2,'1',18,26,NULL,NULL,'0','2025-10-23 02:47:42','2025-10-23 02:47:42'),(3,'4',18,26,NULL,NULL,'0','2025-10-23 02:48:59','2025-10-23 02:48:59'),(4,'4',18,26,NULL,NULL,'0','2025-10-23 02:49:53','2025-10-23 02:49:53'),(5,'6',17,27,NULL,NULL,'0','2025-10-23 03:03:10','2025-10-23 03:03:10'),(6,'6',8,27,NULL,NULL,'0','2025-10-23 03:03:49','2025-10-23 03:03:49'),(7,'1',10,29,NULL,NULL,'0','2025-10-23 21:03:43','2025-10-23 21:03:43'),(8,'1',10,29,NULL,NULL,'0','2025-10-23 21:03:43','2025-10-23 21:03:43'),(9,'1',10,29,NULL,NULL,'0','2025-10-23 21:03:43','2025-10-23 21:03:43'),(10,'1',10,29,NULL,NULL,'0','2025-10-23 21:03:43','2025-10-23 21:03:43'),(11,'1',10,29,NULL,NULL,'0','2025-10-23 21:03:43','2025-10-23 21:03:43'),(12,'1',10,29,NULL,NULL,'0','2025-10-23 21:07:20','2025-10-23 21:07:20'),(13,'1',10,29,NULL,NULL,'0','2025-10-23 21:07:20','2025-10-23 21:07:20'),(14,'1',10,29,NULL,NULL,'0','2025-10-23 21:07:20','2025-10-23 21:07:20'),(15,'1',14,29,NULL,NULL,'0','2025-10-23 21:08:02','2025-10-23 21:08:02'),(16,'1',14,29,NULL,NULL,'0','2025-10-23 21:08:02','2025-10-23 21:08:02'),(17,'1',14,29,NULL,NULL,'0','2025-10-23 21:08:02','2025-10-23 21:08:02'),(18,'1',16,30,NULL,NULL,'0','2025-10-23 21:10:34','2025-10-23 21:10:34'),(19,'1',16,30,NULL,NULL,'0','2025-10-23 21:10:34','2025-10-23 21:10:34'),(20,'1',16,30,NULL,NULL,'0','2025-10-23 21:10:34','2025-10-23 21:10:34'),(21,'2',10,30,NULL,NULL,'0','2025-10-23 21:10:56','2025-10-23 21:10:56'),(22,'3',11,30,NULL,NULL,'0','2025-10-23 21:11:50','2025-10-23 21:11:50'),(23,'1',16,30,7,'M','0','2025-10-23 21:19:16','2025-10-23 21:19:16'),(24,'1',16,30,4,'M','0','2025-10-23 21:19:16','2025-10-23 21:19:16'),(25,'2',8,30,4,'XS','0','2025-10-23 21:21:24','2025-10-23 21:21:24'),(26,'1',9,32,2,'S','0','2025-10-23 22:15:43','2025-10-23 22:15:43'),(27,'1',9,32,3,'M','0','2025-10-23 22:15:43','2025-10-23 22:15:43'),(28,'2',19,32,7,'S','0','2025-10-23 22:15:54','2025-10-23 22:15:54'),(29,'2',19,32,7,'M','0','2025-10-23 22:15:54','2025-10-23 22:15:54'),(30,'2',16,32,6,'M','0','2025-10-23 22:16:04','2025-10-23 22:16:04'),(31,'2',15,32,10,'S','0','2025-10-23 22:17:44','2025-10-23 22:17:44'),(32,'1',15,32,11,'S','0','2025-10-23 22:17:44','2025-10-23 22:17:44'),(33,'3',15,32,7,'S','0','2025-10-23 22:17:44','2025-10-23 22:17:44'),(34,'1',15,32,12,'S','0','2025-10-23 22:17:44','2025-10-23 22:17:44'),(35,'1',10,32,4,'S','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(36,'1',10,32,4,'M','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(37,'1',10,32,6,'S','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(38,'1',10,32,6,'M','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(39,'1',10,32,2,'S','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(40,'1',10,32,3,'S','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(41,'1',10,32,3,'M','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(42,'2',10,32,7,'S','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(43,'1',10,32,7,'M','0','2025-10-23 22:33:54','2025-10-23 22:33:54'),(44,'1',10,33,4,'S','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(45,'1',10,33,4,'M','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(46,'1',10,33,6,'S','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(47,'1',10,33,6,'M','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(48,'1',10,33,2,'S','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(49,'1',10,33,2,'M','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(50,'1',10,33,3,'S','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(51,'1',10,33,3,'M','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(52,'1',10,33,7,'S','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(53,'1',10,33,7,'M','0','2025-10-23 22:35:58','2025-10-23 22:35:58'),(54,'1',10,33,7,'S','0','2025-10-23 22:36:28','2025-10-23 22:36:28'),(55,'1',16,34,7,'S','0','2025-10-24 01:13:02','2025-10-24 01:13:02'),(56,'1',16,34,6,'S','0','2025-10-24 01:13:02','2025-10-24 01:13:02'),(57,'1',16,34,6,'M','0','2025-10-24 01:13:02','2025-10-24 01:13:02'),(58,'1',16,34,4,'M','0','2025-10-24 01:13:02','2025-10-24 01:13:02'),(59,'1',9,37,2,'S','0','2026-01-11 05:07:34','2026-01-11 05:07:34'),(60,'1',9,37,3,'S','0','2026-01-11 05:07:34','2026-01-11 05:07:34'),(61,'1',10,39,4,'S','0','2026-01-12 14:02:57','2026-01-12 14:02:57'),(62,'1',10,39,6,'M','0','2026-01-12 14:02:57','2026-01-12 14:02:57'),(63,'1',9,40,2,'S','0','2026-01-13 02:42:10','2026-01-13 02:42:10'),(64,'1',9,44,2,'S','1','2026-02-05 22:57:06','2026-02-05 22:57:06'),(65,'1',9,44,2,'M','1','2026-02-05 22:57:36','2026-02-05 22:57:36'),(66,'1',9,44,3,'S','1','2026-02-05 23:03:06','2026-02-05 23:03:06'),(67,'1',9,44,3,'M','1','2026-02-05 23:04:31','2026-02-05 23:04:31');
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
INSERT INTO `lineas_ropa` VALUES (1,'GymShark','1',NULL,NULL),(2,'linea_brasilena','1',NULL,NULL),(3,'segunda_piel','1',NULL,NULL),(4,'americana','1',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2014_10_12_100000_create_password_resets_table',1),(4,'2019_08_19_000000_create_failed_jobs_table',1),(5,'2019_12_14_000001_create_personal_access_tokens_table',1),(6,'2024_01_02_041412_create_clientes_table',1),(7,'2024_01_02_041428_create_tiendas_table',1),(8,'2024_01_02_041455_create_pedidos_table',1),(9,'2024_01_09_160050_create_proveedores_table',1),(10,'2024_01_09_221539_create_compras_table',1),(11,'2024_01_09_221540_create_productos_table',1),(12,'2024_01_11_015440_create_parametros_table',1),(13,'2024_01_12_192908_create_detalle_pedidos_table',1),(14,'2024_07_15_025251_create_colores_table',1),(15,'2024_07_15_025259_create_colores__productos_table',1),(16,'2025_03_24_210204_add_imagen_to_productos_table',1),(17,'2025_06_20_214803_create_lineas_ropa_table',1),(18,'2025_06_20_214826_create_categorias_productos_table',1),(19,'2025_06_20_214833_add_categoria_producto_id_to_productos_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
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
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clientes_id` bigint unsigned NOT NULL,
  `tienda_id` bigint unsigned NOT NULL,
  `subtotal_pedido` double(8,2) DEFAULT '0.00',
  `iva_pedido` double(8,2) DEFAULT '0.00',
  `descuentos_pedido` double(8,2) DEFAULT '0.00',
  `total_pedido` double(8,2) DEFAULT '0.00',
  `estado_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN ESPERA',
  `estado_pedidos` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pedidos_clientes_id_foreign` (`clientes_id`),
  KEY `pedidos_tienda_id_foreign` (`tienda_id`),
  CONSTRAINT `pedidos_clientes_id_foreign` FOREIGN KEY (`clientes_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `pedidos_tienda_id_foreign` FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,'tay dai 3c7',4,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-06-26 23:38:19','2025-06-26 23:38:19'),(2,'pedido 50',5,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 21:31:09','2025-10-03 21:31:09'),(3,'prueba 51',6,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 21:44:45','2025-10-03 21:44:45'),(4,'prueba 52',7,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 21:44:52','2025-10-03 21:44:52'),(5,'prueba 53',8,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 21:45:00','2025-10-03 21:45:00'),(6,'prueba 54',9,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 21:45:09','2025-10-03 21:45:09'),(7,'prueba 55',10,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 21:45:25','2025-10-03 21:45:25'),(8,'prueba 56',11,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-03 22:46:39','2025-10-03 22:46:39'),(9,'PEDIDO 60',12,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-06 22:20:07','2025-10-06 22:20:07'),(10,'PEDIDO 61',13,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-06 22:20:18','2025-10-06 22:20:18'),(11,'PEDIDO 63',14,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-06 22:21:02','2025-10-06 22:21:02'),(12,'Pedido 70',15,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:55:44','2025-10-07 20:55:44'),(13,'prueba 71',16,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:58:15','2025-10-07 20:58:15'),(14,'prueba 72',17,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:58:25','2025-10-07 20:58:25'),(15,'prueba 73',18,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:58:34','2025-10-07 20:58:34'),(16,'prueba 74',19,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:58:41','2025-10-07 20:58:41'),(17,'prueba 75',20,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:58:48','2025-10-07 20:58:48'),(18,'prueba 76',21,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:59:00','2025-10-07 20:59:00'),(19,'prueba 77',22,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-07 20:59:06','2025-10-07 20:59:06'),(20,'prueba 78',1,1,0.00,0.00,0.00,0.00,'EN ESPERA','1','2025-10-07 20:59:21','2025-10-07 20:59:21'),(21,'prueba 80',23,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-08 22:38:03','2025-10-08 22:38:03'),(22,'prueba 81',24,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-08 22:38:21','2025-10-08 22:38:21'),(23,'prueba 83',25,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-08 22:38:28','2025-10-08 22:38:28'),(24,'prueba 82',26,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-08 22:38:36','2025-10-08 22:38:36'),(25,'prueba 86',27,2,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-08 22:54:17','2025-10-08 22:54:17'),(26,'prueba 90',5,1,221.40,0.00,0.00,221.40,'ENVIADO','0','2025-10-23 02:46:47','2025-10-23 02:46:47'),(27,'prueba 90',1,1,267.60,0.00,0.00,267.60,'EN ESPERA','0','2025-10-23 02:54:36','2025-10-23 02:54:36'),(28,'prueba 100',5,1,0.00,0.00,0.00,0.00,'EN ESPERA','1','2025-10-23 03:05:18','2025-10-23 03:05:18'),(29,'prueba 110',6,1,214.60,0.00,0.00,214.60,'EN ESPERA','0','2025-10-23 20:20:28','2025-10-23 20:20:28'),(30,'prueba 120',4,1,261.40,0.00,0.00,261.40,'EN ESPERA','0','2025-10-23 21:09:18','2025-10-23 21:09:18'),(31,'130',6,1,0.00,0.00,0.00,0.00,'EN ESPERA','0','2025-10-23 21:21:36','2025-10-23 21:21:36'),(32,'13',1,1,570.80,0.00,NULL,570.80,'EN ESPERA','0','2025-10-23 22:15:24','2025-10-23 22:15:24'),(33,'14',4,1,233.20,0.00,0.00,233.20,'EN ESPERA','0','2025-10-23 22:35:42','2025-10-23 22:35:42'),(34,'60',24,1,106.40,0.00,0.00,106.40,'EN ESPERA','0','2025-10-24 01:12:41','2025-10-24 01:12:41'),(35,'Pedido 200',23,1,0.00,0.00,0.00,0.00,'ENVIADO','1','2025-10-25 03:14:14','2025-10-25 03:14:14'),(36,'EJEMPLO NEW LINE',4,1,0.00,0.00,0.00,0.00,'EN ESPERA','1','2026-01-09 21:15:57','2026-01-09 21:15:57'),(37,'EJEMPLO NEW LINE',13,2,27.20,0.00,0.00,27.20,'ENVIADO','0','2026-01-11 05:07:04','2026-01-11 05:07:04'),(38,'EJEMPLO NEW LINE',4,1,0.00,0.00,0.00,0.00,'EN ESPERA','0','2026-01-11 05:09:48','2026-01-11 05:09:48'),(39,'pedido 2026',28,1,42.40,0.00,0.00,42.40,'ENVIADO','0','2026-01-12 13:57:34','2026-01-12 13:57:34'),(40,'EJEMPLO NEW LINE',1,1,13.60,0.00,0.00,13.60,'EN ESPERA','0','2026-01-12 23:02:31','2026-01-12 23:02:31'),(41,NULL,1,1,0.00,0.00,NULL,0.00,'EN ESPERA','0','2026-01-13 02:41:11','2026-01-13 02:41:11'),(42,'EJEMPLO NEW LINE',1,1,0.00,0.00,0.00,0.00,'EN ESPERA','0','2026-01-13 04:12:53','2026-01-13 04:12:53'),(43,'EJEMPLO NEW LINE',1,1,0.00,0.00,0.00,0.00,'EN ESPERA','0','2026-01-13 04:13:08','2026-01-13 04:13:08'),(44,'pedido 2026',1,1,54.40,0.00,0.00,54.40,'ENVIADO','1','2026-02-05 21:40:46','2026-02-05 21:40:46');
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
  `precio_pesos_producto` double(8,2) NOT NULL DEFAULT '0.00',
  `precio_dolares_producto` double(8,2) NOT NULL DEFAULT '0.00',
  `precio_venta_producto` double(8,2) NOT NULL DEFAULT '0.00',
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (8,'short_lb_1672025','Short Linea brasileña pushup','productos/b1c0a560-3d6a-402f-aa34-ac6faddd0b35.jpg','16','16',0.00,6.50,13.00,2,'1','2025-07-17 00:21:44','2025-07-17 00:21:44',7),(9,'top_lb_1572025','Top Linea brasileña sin tiras','productos/8ba2c43d-48bf-462e-a515-b2e5333e1595.jpg','8','4',0.00,6.80,13.60,2,'1','2025-07-17 00:29:09','2025-07-17 00:29:09',11),(10,'enterizo_lb_1572025','Enterizo Linea brasileña','productos/5bb9a4e1-ec6c-4c7f-8964-2f5787251f0a.jpg','20','16',0.00,10.60,21.20,2,'1','2025-07-17 00:34:56','2025-07-17 00:34:56',23),(11,'faldas_lb_1672025','Faldas Plizadas Linea brasileña','productos/b0a0b157-6eea-4f11-8981-b491f1bcbdce.jpg','15','17',0.00,10.00,20.00,2,'1','2025-07-17 00:47:18','2025-07-17 00:47:18',43),(12,'Falda_larga_lb_1672025','Faldas Largas Linea brasileña','productos/c5d58f71-ebd0-4af7-9417-7a24973ea525.jpg','16','16',0.00,13.00,26.00,2,'1','2025-07-17 00:53:26','2025-07-17 00:53:26',43),(13,'top_tiras_lb_1672025','Top Linea brasileña tiras','productos/3a4f84d2-5966-499d-813a-2df032a9fc87.jpg','12','12',0.00,15.50,31.00,2,'1','2025-07-17 00:56:22','2025-07-17 00:56:23',11),(14,'leggins_jean_lb_1672025','Leggins Jean Linea brasileña','productos/0a7b2cc1-6816-4b78-bb4b-c777da4c6b3e.jpg','26','17',0.00,7.50,15.00,2,'1','2025-07-17 01:02:33','2025-07-17 01:02:33',19),(15,'enterizo_mangacorta_lb_1672025','Enterizo Manga Corta Linea brasileña','productos/5cb46109-f289-4fc4-aa15-2a8dceb61cc5.jpg','26','26',0.00,13.20,26.40,2,'1','2025-07-17 01:08:01','2025-07-17 01:08:01',23),(16,'short_2_lb_1672025','Short Linea brasileña pushup','productos/1f3ca21a-8dc7-43bc-8b34-8a947d4cddc9.jpg','12','15',0.00,13.30,26.60,2,'1','2025-07-17 03:26:49','2025-07-17 03:26:50',7),(17,'enterizo_acampanado_lb_1572025','Enterizo Acampanado Linea brasileña','productos/8a5e5fba-3de0-47f2-a664-d92363c6dc27.jpg','12','12',0.00,15.80,31.60,2,'1','2025-07-17 03:37:03','2025-07-17 03:37:03',23),(18,'jacket_lb_1672025','Jacket Linea brasileña','productos/7912df78-6c19-4785-917e-d61dafbb3d95.jpg','18','20',0.00,12.30,24.60,2,'1','2025-07-17 18:33:16','2025-07-17 18:33:18',51),(19,'body_lb_1672025','Body Linea brasileña','productos/ec90a59d-9c72-4fd8-bfae-854ccc0b7754.jpg','6','6',0.00,11.70,23.40,2,'1','2025-07-17 18:35:36','2025-07-17 18:35:36',47);
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (2,'BrasiliaStore','Administrador','BrasiliaStore','0000000000','0000000000','Brasil',NULL,'1','2025-07-11 21:02:03','2025-07-11 21:02:03');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
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
INSERT INTO `tiendas` VALUES (1,'Booty Fitennes','Noemi Bety','Solorzano Guevara','1726439449','0978879775','Santo Domingo de los Tsachilas','noemi.ejemplo@gmail.com','1','2025-06-26 23:38:10','2025-06-26 23:38:10'),(2,'Modafit','Eduardo Miguel','Villareal Luque','1725042871','0990706472','Santo Domingo de los Tsachilas',NULL,'1',NULL,NULL);
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
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Admin','Admin_2025@ejemplo.com',NULL,'$2y$12$uEUI/AZDnqm3V5yzl4JLDOPHy.Iod0Qce4Jqz0.lU/Zg9fPPOz9xK','1','M146pM2TzAGdZPS6yh2CqqmeCoXiRnH5E0ydMVFsyTEcLFnzDBnRGAjRzTWh','2025-06-21 03:28:51','2025-06-21 03:28:51'),(3,'NewLine','newline@local.com',NULL,'$2y$12$9Htp1T0g7vNaGsQGdMLlc.NyS1IS10RpfpdmFnl.bJnAv3Msh2dAW','1',NULL,'2026-01-09 20:54:35','2026-01-09 20:54:35');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'guias'
--

--
-- Dumping routines for database 'guias'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-04  9:42:36
