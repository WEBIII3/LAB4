CREATE DATABASE mybox;
use mybox;

-- Tabla: usuarios
CREATE TABLE `usuarios` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(15) NOT NULL,
  `contra` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(25) NOT NULL,
  `email` VARCHAR(50) NOT NULL,
  `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tabla: carpetas
-- Estructura jerárquica de carpetas

CREATE TABLE `carpetas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `carpeta_padre_id` INT DEFAULT NULL,
  `ruta_completa` VARCHAR(500) NOT NULL,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `carpeta_padre_id` (`carpeta_padre_id`),
  UNIQUE KEY `unique_path` (`usuario_id`, `ruta_completa`),
  CONSTRAINT `carpetas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carpetas_ibfk_2` FOREIGN KEY (`carpeta_padre_id`) REFERENCES `carpetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- Almacena los archivos en formato BLOB
CREATE TABLE `archivos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario_id` INT NOT NULL,
  `carpeta_id` INT DEFAULT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `extension` VARCHAR(10) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `tamano` BIGINT NOT NULL COMMENT 'Tamaño en bytes',
  `contenido` LONGBLOB NOT NULL,
  `ruta_completa` VARCHAR(500) NOT NULL,
  `fecha_subida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `carpeta_id` (`carpeta_id`),
  KEY `idx_ruta` (`ruta_completa`),
  UNIQUE KEY `unique_file_path` (`usuario_id`, `ruta_completa`),
  CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `archivos_ibfk_2` FOREIGN KEY (`carpeta_id`) REFERENCES `carpetas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


CREATE TABLE `compartidos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `propietario_id` INT NOT NULL,
  `archivo_id` INT DEFAULT NULL,
  `carpeta_id` INT DEFAULT NULL,
  `compartido_con_id` INT NOT NULL,
  `tipo` ENUM('archivo', 'carpeta') NOT NULL,
  `fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `propietario_id` (`propietario_id`),
  KEY `archivo_id` (`archivo_id`),
  KEY `carpeta_id` (`carpeta_id`),
  KEY `compartido_con_id` (`compartido_con_id`),
  CONSTRAINT `compartidos_ibfk_1` FOREIGN KEY (`propietario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compartidos_ibfk_2` FOREIGN KEY (`archivo_id`) REFERENCES `archivos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compartidos_ibfk_3` FOREIGN KEY (`carpeta_id`) REFERENCES `carpetas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `compartidos_ibfk_4` FOREIGN KEY (`compartido_con_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CHECK (
    (archivo_id IS NOT NULL AND carpeta_id IS NULL AND tipo = 'archivo') OR
    (carpeta_id IS NOT NULL AND archivo_id IS NULL AND tipo = 'carpeta')
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


INSERT INTO `usuarios` (`usuario`, `contra`, `nombre`, `email`) VALUES
('msanchez', 'eb703f79de3d42b7e68baeb999ff22fbe82dd9c33d073b16fe774bffcb33a952', 'Brian Walker', 'bwalker@example.com'),
('tc', '8f455bba4522752c63bbcd2b6d4c954e66a2bade8792dbe67be7c088affa422e', 'TC User', 'tc@example.com'),
('tt', '0e07cf830957701d43c183f1515f63e6b68027e528f43ef52b1527a520ddec82', 'TT User', 'tt@example.com'),
('jk', '31b25869b39f1baa9e7fc279255901b696c36629e57294d4455f479534139852', 'JK User', 'jk@example.com');


CREATE INDEX idx_usuario_nombre ON usuarios(usuario);
CREATE INDEX idx_archivo_usuario_carpeta ON archivos(usuario_id, carpeta_id);
CREATE INDEX idx_carpeta_usuario_padre ON carpetas(usuario_id, carpeta_padre_id);

CREATE VIEW archivos_con_info AS
SELECT 
    a.id,
    a.nombre,
    a.nombre_original,
    a.extension,
    a.mime_type,
    a.tamano,
    a.ruta_completa,
    a.fecha_subida,
    a.fecha_modificacion,
    u.usuario AS propietario,
    u.nombre AS propietario_nombre,
    c.nombre AS carpeta_nombre,
    c.ruta_completa AS carpeta_ruta
FROM archivos a
INNER JOIN usuarios u ON a.usuario_id = u.id
LEFT JOIN carpetas c ON a.carpeta_id = c.id;


DELIMITER $$

-- Procedimiento para obtener el espacio usado por usuario
CREATE PROCEDURE sp_espacio_usuario(IN p_usuario_id INT)
BEGIN
    SELECT 
        u.usuario,
        u.nombre,
        COUNT(a.id) AS total_archivos,
        COALESCE(SUM(a.tamano), 0) AS espacio_bytes,
        ROUND(COALESCE(SUM(a.tamano), 0) / 1048576, 2) AS espacio_mb
    FROM usuarios u
    LEFT JOIN archivos a ON u.id = a.usuario_id
    WHERE u.id = p_usuario_id
    GROUP BY u.id, u.usuario, u.nombre;
END$$