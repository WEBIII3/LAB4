-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: 100.75.67.24    Database: mybox
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.24.04.2

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
-- Table structure for table `compartidos`
--

DROP TABLE IF EXISTS `compartidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compartidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `propietario` varchar(15) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `compartido_con` varchar(15) NOT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `propietario` (`propietario`),
  KEY `compartido_con` (`compartido_con`),
  CONSTRAINT `compartidos_ibfk_1` FOREIGN KEY (`propietario`) REFERENCES `usuarios` (`usuario`),
  CONSTRAINT `compartidos_ibfk_2` FOREIGN KEY (`compartido_con`) REFERENCES `usuarios` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compartidos`
--

LOCK TABLES `compartidos` WRITE;
/*!40000 ALTER TABLE `compartidos` DISABLE KEYS */;
INSERT INTO `compartidos` VALUES (1,'tc','MyBox.txt','tc1','2025-10-31 10:52:50'),(2,'tc','MyBox.txt','tc1','2025-10-31 10:57:13'),(3,'uu','MyBox.txt','tt','2025-10-31 11:03:58'),(4,'tt','TEST','UU','2025-10-31 11:04:20'),(5,'tt','TEST','tt','2025-10-31 11:04:37'),(6,'tc','MyBox__2_.txt','brianw','2025-10-31 11:24:04'),(7,'tc','MyBox__2_.txt','tc1','2025-10-31 11:24:10'),(8,'tc','MyBox__2_.txt','bw','2025-10-31 11:28:14'),(9,'tc','tess','bw','2025-10-31 11:28:39'),(10,'bw','tess','uu','2025-10-31 11:32:08'),(11,'bw','sdsd','uu','2025-10-31 11:32:36'),(12,'jk','Laboratorio_04__2_.pdf','tt','2025-10-31 16:56:30'),(13,'jk','carpeta/MyBox__2___2_.txt','tt','2025-10-31 16:57:27'),(14,'tt','sdsd','jk','2025-10-31 16:58:08');
/*!40000 ALTER TABLE `compartidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `usuario` varchar(15) NOT NULL,
  `contra` varchar(80) NOT NULL,
  `nombre` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL,
  PRIMARY KEY (`usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES ('brianw','eb703f79de3d42b7e68baeb999ff22fbe82dd9c33d073b16fe774bffcb33a952','brianw','brianw'),('bw','ab02eedd0712a148636d87b46a0c4c741ad4634745d829717b84433d7c4940c2','bw','bw'),('jk','31b25869b39f1baa9e7fc279255901b696c36629e57294d4455f479534139852','jk','jk'),('tc','8f455bba4522752c63bbcd2b6d4c954e66a2bade8792dbe67be7c088affa422e','tc','tc'),('tc1','f3bc653428114e5a71e9ddf0ecb7db536088855b85487b28f60e81fc5e1e4cb0','tc1','tc1'),('tt','0e07cf830957701d43c183f1515f63e6b68027e528f43ef52b1527a520ddec82','tt','tt'),('uu','5afab9a620f6f11284505be2fb9a975b4dccfdd30970dffc7ed875490160e4d0','uu','uu'),('vc','522a45442726c9f9ddc6cdd60b3b627c4a34268a2a30910475a7347cfe32900d','vc','vc'),('xc','207f5c1d8a3ec09f296b468caaa73f5900e1b2d668e05dfa4dcc638b1e2d868d','xc','xc'),('yy','ef90d9c1ec76b1edc9edfaf2c0c05359c10ccc49ae8ecf7b7fd25ce9c02e86a4','yy','yy');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-31 11:33:46
