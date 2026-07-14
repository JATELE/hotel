-- CRM HOTELES V3.4 COMPLETO MULTI-HOTEL
-- Importar completo en phpMyAdmin o MySQL Workbench.
-- Base usada por api/config/Database.php: eboiskqx_cmrhoteles_bd
-- Usuarios de prueba:
-- superadmin / super123
-- aguila_admin / admin123
-- aguila_recep / recepcion123
-- inkarian_admin / admin123
-- flora_admin / admin123

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `eboiskqx_cmrhoteles_bd` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `eboiskqx_cmrhoteles_bd`;

DROP TABLE IF EXISTS `usuario_sesiones`;
DROP TABLE IF EXISTS `reservas`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `habitaciones`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `hoteles`;
DROP TABLE IF EXISTS `planes`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `planes` (
  `id_plan` int NOT NULL AUTO_INCREMENT,
  `nombre_plan` varchar(60) NOT NULL,
  `meses` int NOT NULL,
  `precio_referencial` decimal(10,2) DEFAULT 0.00,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY (`id_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `hoteles` (
  `id_hotel` int NOT NULL AUTO_INCREMENT,
  `id_plan` int DEFAULT NULL,
  `ruc` varchar(20) NOT NULL,
  `razon_social` varchar(180) NOT NULL,
  `nombre_comercial` varchar(140) NOT NULL,
  `slug` varchar(90) NOT NULL,
  `gestion_por_habitacion` tinyint(1) NOT NULL DEFAULT 0,
  `direccion` varchar(220) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `fecha_inicio_plan` date NOT NULL,
  `fecha_fin_plan` date NOT NULL,
  `estado` enum('ACTIVO','VENCIDO','SUSPENDIDO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_hotel`),
  UNIQUE KEY `uk_hoteles_ruc` (`ruc`),
  UNIQUE KEY `uk_hoteles_slug` (`slug`),
  KEY `idx_hoteles_plan` (`id_plan`),
  CONSTRAINT `fk_hoteles_plan` FOREIGN KEY (`id_plan`) REFERENCES `planes` (`id_plan`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(40) NOT NULL,
  `descripcion` varchar(180) DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uk_roles_nombre` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `id_hotel` int DEFAULT NULL,
  `id_rol` int NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_rol` (`id_rol`),
  KEY `idx_usuarios_hotel` (`id_hotel`),
  CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_hoteles` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `id_hotel` int NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `capacidad_pax` int NOT NULL DEFAULT 2,
  `etiqueta` varchar(80) DEFAULT NULL,
  `tipo_cama` varchar(80) DEFAULT NULL,
  `servicios` text DEFAULT NULL,
  `incluye` text DEFAULT NULL,
  `imagen_1` varchar(500) DEFAULT NULL,
  `imagen_2` varchar(500) DEFAULT NULL,
  `imagen_3` varchar(500) DEFAULT NULL,
  `galeria_url` varchar(500) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `uk_categoria_hotel_nombre` (`id_hotel`,`nombre`),
  CONSTRAINT `fk_categorias_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `habitaciones` (
  `id_habitacion` int NOT NULL AUTO_INCREMENT,
  `id_hotel` int NOT NULL,
  `numero_habitacion` varchar(20) NOT NULL,
  `id_categoria` int NOT NULL,
  `estado` enum('Disponible','Ocupada','Mantenimiento') DEFAULT 'Disponible',
  PRIMARY KEY (`id_habitacion`),
  UNIQUE KEY `uk_habitacion_hotel_numero` (`id_hotel`,`numero_habitacion`),
  KEY `idx_habitaciones_categoria` (`id_categoria`),
  CONSTRAINT `fk_habitaciones_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_habitaciones_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `id_hotel` int NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento_identidad` varchar(20) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `uk_cliente_hotel_doc` (`id_hotel`,`documento_identidad`),
  CONSTRAINT `fk_clientes_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reservas` (
  `id_reserva` int NOT NULL AUTO_INCREMENT,
  `id_hotel` int NOT NULL,
  `id_categoria` int NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_habitacion` int DEFAULT NULL,
  `id_usuario_registro` int DEFAULT NULL,
  `id_usuario_confirmacion` int DEFAULT NULL,
  `fecha_checkin` date NOT NULL,
  `fecha_checkout` date NOT NULL,
  `precio_final` decimal(10,2) NOT NULL,
  `estado_reserva` enum('Pendiente','Confirmada','Atendida','Cancelada') DEFAULT 'Pendiente',
  `canal_reserva` enum('Presencial','Redes sociales','Llamada','Consulta WhatsApp','Web') NOT NULL DEFAULT 'Web',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  `notas` text DEFAULT NULL,
  PRIMARY KEY (`id_reserva`),
  KEY `idx_reservas_hotel` (`id_hotel`),
  KEY `idx_reservas_categoria` (`id_categoria`),
  KEY `idx_reservas_cliente` (`id_cliente`),
  KEY `idx_reservas_usuario_registro` (`id_usuario_registro`),
  KEY `idx_reservas_usuario_confirmacion` (`id_usuario_confirmacion`),
  CONSTRAINT `fk_reservas_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_reservas_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_reservas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_reservas_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_reservas_usuario_registro` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_reservas_usuario_confirmacion` FOREIGN KEY (`id_usuario_confirmacion`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario_sesiones` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `id_hotel` int DEFAULT NULL,
  `fecha_login` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_logout` datetime DEFAULT NULL,
  `ip` varchar(60) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `estado` enum('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  PRIMARY KEY (`id_sesion`),
  KEY `idx_sesiones_usuario` (`id_usuario`),
  KEY `idx_sesiones_hotel` (`id_hotel`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_sesiones_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `planes` (`id_plan`,`nombre_plan`,`meses`,`precio_referencial`) VALUES
(1,'Plan 1 mes',1,99.00),(2,'Plan 2 meses',2,180.00),(3,'Plan 6 meses',6,480.00),(4,'Plan 12 meses',12,900.00);

INSERT INTO `hoteles` (`id_hotel`,`id_plan`,`ruc`,`razon_social`,`nombre_comercial`,`slug`,`gestion_por_habitacion`,`direccion`,`email`,`whatsapp`,`fecha_inicio_plan`,`fecha_fin_plan`,`estado`) VALUES
(1,3,'20600000001','AGUILA DORADA SELVA HOTEL S.A.C.','Águila Dorada Selva Hotel','aguila-dorada',0,'Pucallpa, Ucayali','reservas@aguiladorada.local','51960565050',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 6 MONTH),'ACTIVO'),
(2,2,'20600000002','INKARIAN HOTEL S.A.C.','Inkarian Hotel','inkarian',0,'Pucallpa, Ucayali','reservas@inkarian.local','51911111111',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 2 MONTH),'ACTIVO'),
(3,1,'20600000003','FLORA HOTEL S.A.C.','Flora Hotel','flora',0,'Pucallpa, Ucayali','reservas@flora.local','51922222222',CURDATE(),DATE_ADD(CURDATE(), INTERVAL 1 MONTH),'ACTIVO');

INSERT INTO `roles` (`id_rol`,`nombre_rol`,`descripcion`) VALUES
(1,'superadmin','Dueño del sistema: administra hoteles y planes'),
(2,'admin_hotel','Dueño/administrador del hotel'),
(3,'recepcionista','Recepción: registra, atiende y confirma reservas'),
(4,'cajero','Solo lectura de reservas y reportes básicos');

INSERT INTO `usuarios` (`id_usuario`,`id_hotel`,`id_rol`,`usuario`,`email`,`password_hash`,`nombres`,`apellidos`,`telefono`,`estado`) VALUES
(1,NULL,1,'superadmin','superadmin@crm.local','$2y$12$jwPEIQyzz601KGOb4hcUYeYVyfvhMeX6iGteNuzR2yVyyzEeh0ijO','Super','Admin',NULL,'ACTIVO'),
(2,1,2,'aguila_admin','admin@aguiladorada.local','$2y$12$OheVGJxV9Ad1QwL2yPEVEuVsiS1NsUJsK3n2AGl12ri.CcgQKY772','Admin','Águila','51960565050','ACTIVO'),
(3,1,3,'aguila_recep','recepcion@aguiladorada.local','$2y$12$C7qBgDdCpW6DUI7WGrDD2.Igo8z.G0V2cflgwnakZDiek2TAgAv3a','Recepción','Águila',NULL,'ACTIVO'),
(4,2,2,'inkarian_admin','admin@inkarian.local','$2y$12$OheVGJxV9Ad1QwL2yPEVEuVsiS1NsUJsK3n2AGl12ri.CcgQKY772','Admin','Inkarian',NULL,'ACTIVO'),
(5,3,2,'flora_admin','admin@flora.local','$2y$12$OheVGJxV9Ad1QwL2yPEVEuVsiS1NsUJsK3n2AGl12ri.CcgQKY772','Admin','Flora',NULL,'ACTIVO');

INSERT INTO `categorias` (`id_categoria`,`id_hotel`,`nombre`,`precio_base`,`precio_anterior`,`capacidad_pax`,`etiqueta`,`tipo_cama`,`servicios`,`incluye`,`imagen_1`,`imagen_2`,`imagen_3`,`galeria_url`,`estado`) VALUES
(1,1,'Junior Suite',230,300,2,'Vista a selva / Promo','King','A/C, WiFi, Balcón, Jacuzzi','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp','#','ACTIVO'),
(2,1,'Habitación Matrimonial',180,230,2,'Oferta especial','King','A/C, WiFi, Balcón, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp','#','ACTIVO'),
(3,1,'Habitación Triple',220,280,3,'Ideal familias','Triple','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp','#','ACTIVO'),
(4,1,'Habitación Doble',180,230,2,'Disponibilidad limitada','2 Camas','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp','#','ACTIVO'),
(5,1,'Habitación Simple',160,180,1,'Económica','Simple','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp','#','ACTIVO'),
(6,2,'Suite Inkarian',260,320,2,'Vista premium','King','A/C, WiFi, Balcón, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp','#','ACTIVO'),
(7,2,'Matrimonial Inkarian',190,240,2,'Oferta especial','King','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp','#','ACTIVO'),
(8,2,'Triple Familiar',240,290,3,'Ideal familias','Triple','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp','#','ACTIVO'),
(9,2,'Doble Ejecutiva',200,260,2,'Disponibilidad limitada','2 Camas','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp','#','ACTIVO'),
(10,2,'Simple Económica',150,190,1,'Económica','Simple','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp','#','ACTIVO'),
(11,3,'Suite Flora',210,270,2,'Vista jardín','King','A/C, WiFi, Balcón, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp','#','ACTIVO'),
(12,3,'Matrimonial Flora',170,220,2,'Oferta especial','King','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp','#','ACTIVO'),
(13,3,'Triple Flora',215,270,3,'Ideal familias','Triple','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp','#','ACTIVO'),
(14,3,'Doble Flora',175,230,2,'Disponibilidad limitada','2 Camas','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp','#','ACTIVO'),
(15,3,'Simple Flora',145,180,1,'Económica','Simple','A/C, WiFi, Baño privado','Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp','https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp','#','ACTIVO');

INSERT INTO `habitaciones` (`id_hotel`,`numero_habitacion`,`id_categoria`,`estado`) VALUES
(1,'101',1,'Disponible'),(1,'102',1,'Disponible'),(1,'201',2,'Disponible'),(1,'202',2,'Disponible'),(1,'301',3,'Disponible'),(1,'401',4,'Disponible'),(1,'501',5,'Disponible'),
(2,'101',6,'Disponible'),(2,'102',6,'Disponible'),(2,'201',7,'Disponible'),(2,'202',7,'Disponible'),(2,'301',8,'Disponible'),(2,'401',9,'Disponible'),(2,'501',10,'Disponible'),
(3,'101',11,'Disponible'),(3,'102',11,'Disponible'),(3,'201',12,'Disponible'),(3,'202',12,'Disponible'),(3,'301',13,'Disponible'),(3,'401',14,'Disponible'),(3,'501',15,'Disponible');

INSERT INTO `reservas` (`id_hotel`,`id_categoria`,`fecha_checkin`,`fecha_checkout`,`precio_final`,`estado_reserva`,`canal_reserva`,`notas`) VALUES
(1,2,DATE_ADD(CURDATE(), INTERVAL 5 DAY),DATE_ADD(CURDATE(), INTERVAL 7 DAY),360,'Pendiente','Web','Consulta inicial desde web'),
(1,3,DATE_ADD(CURDATE(), INTERVAL 10 DAY),DATE_ADD(CURDATE(), INTERVAL 12 DAY),440,'Confirmada','Consulta WhatsApp','Cliente confirmó por WhatsApp'),
(2,7,DATE_ADD(CURDATE(), INTERVAL 4 DAY),DATE_ADD(CURDATE(), INTERVAL 6 DAY),380,'Pendiente','Web','Consulta Inkarian'),
(3,12,DATE_ADD(CURDATE(), INTERVAL 3 DAY),DATE_ADD(CURDATE(), INTERVAL 5 DAY),340,'Pendiente','Web','Consulta Flora');

COMMIT;
