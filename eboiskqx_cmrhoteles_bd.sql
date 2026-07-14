-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-07-2026 a las 04:27:52
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `eboiskqx_cmrhoteles_bd`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `beneficios_catalogo`
--

CREATE TABLE `beneficios_catalogo` (
  `id_beneficio` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `beneficios_catalogo`
--

INSERT INTO `beneficios_catalogo` (`id_beneficio`, `id_hotel`, `nombre`, `estado`) VALUES
(1, 3, 'Desayuno', 'ACTIVO'),
(2, 2, 'Desayuno', 'ACTIVO'),
(3, 1, 'Desayuno', 'ACTIVO'),
(4, 3, 'Acceso a piscina', 'ACTIVO'),
(5, 2, 'Acceso a piscina', 'ACTIVO'),
(6, 1, 'Acceso a piscina', 'ACTIVO'),
(7, 3, 'Limpieza diaria', 'ACTIVO'),
(8, 2, 'Limpieza diaria', 'ACTIVO'),
(9, 1, 'Limpieza diaria', 'ACTIVO'),
(10, 3, 'Toallas', 'ACTIVO'),
(11, 2, 'Toallas', 'ACTIVO'),
(12, 1, 'Toallas', 'ACTIVO'),
(13, 3, 'Amenities', 'ACTIVO'),
(14, 2, 'Amenities', 'ACTIVO'),
(15, 1, 'Amenities', 'ACTIVO'),
(16, 3, 'Recepción 24 horas', 'ACTIVO'),
(17, 2, 'Recepción 24 horas', 'ACTIVO'),
(18, 1, 'Recepción 24 horas', 'ACTIVO'),
(19, 3, 'Estacionamiento', 'ACTIVO'),
(20, 2, 'Estacionamiento', 'ACTIVO'),
(21, 1, 'Estacionamiento', 'ACTIVO'),
(22, 3, 'Traslado', 'ACTIVO'),
(23, 2, 'Traslado', 'ACTIVO'),
(24, 1, 'Traslado', 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `capacidad_pax` int(11) NOT NULL DEFAULT 2,
  `etiqueta` varchar(80) DEFAULT NULL,
  `tipo_cama` varchar(80) DEFAULT NULL,
  `servicios` text DEFAULT NULL,
  `incluye` text DEFAULT NULL,
  `imagen_1` varchar(500) DEFAULT NULL,
  `imagen_2` varchar(500) DEFAULT NULL,
  `imagen_3` varchar(500) DEFAULT NULL,
  `galeria_url` varchar(500) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `id_hotel`, `nombre`, `slug`, `precio_base`, `precio_anterior`, `capacidad_pax`, `etiqueta`, `tipo_cama`, `servicios`, `incluye`, `imagen_1`, `imagen_2`, `imagen_3`, `galeria_url`, `estado`) VALUES
(1, 1, 'Junior Suite', 'junior-suite', 230.00, 300.00, 5, 'Vista a selva / Promo', 'King', 'WiFi, A/C, Balcón, Jacuzzi', 'Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp', '#', 'ACTIVO'),
(2, 1, 'Habitación Matrimonial', 'habitacion-matrimonial', 180.00, 230.00, 4, 'Oferta especial', 'King', 'WiFi, A/C, Baño privado, Balcón', 'Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp', '#', 'INACTIVO'),
(3, 1, 'Habitación Triple', 'habitacion-triple', 220.00, 280.00, 3, 'Ideal familias', 'Triple', 'WiFi, A/C, Baño privado', 'Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp', '#', 'INACTIVO'),
(4, 1, 'Habitación Doble', 'habitacion-doble', 180.00, 230.00, 2, 'Disponibilidad limitada', '2 Camas', 'WiFi, A/C, Baño privado', 'Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp', '#', 'ACTIVO'),
(5, 1, 'Habitación Simple', 'habitacion-simple', 160.00, 180.00, 1, 'Económica', 'Simple', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp', '#', 'ACTIVO'),
(6, 2, 'Suite Inkarian', 'suite-inkarian', 260.00, 320.00, 2, 'Vista premium', 'King', 'A/C, WiFi, Balcón, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp', '#', 'ACTIVO'),
(7, 2, 'Matrimonial Inkarian', 'matrimonial-inkarian', 190.00, 240.00, 2, 'Oferta especial', 'King', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp', '#', 'ACTIVO'),
(8, 2, 'Triple Familiar', 'triple-familiar', 240.00, 290.00, 3, 'Ideal familias', 'Triple', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp', '#', 'ACTIVO'),
(9, 2, 'Doble Ejecutiva', 'doble-ejecutiva', 200.00, 260.00, 2, 'Disponibilidad limitada', '2 Camas', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp', '#', 'ACTIVO'),
(10, 2, 'Simple Económica', 'simple-economica', 150.00, 190.00, 1, 'Económica', 'Simple', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp', '#', 'ACTIVO'),
(11, 3, 'Suite Flora', 'suite-flora', 210.00, 270.00, 2, 'Vista jardín', 'King', 'A/C, WiFi, Balcón, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/7.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/8.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/9.webp', '#', 'ACTIVO'),
(12, 3, 'Matrimonial Flora', 'matrimonial-flora', 170.00, 220.00, 2, 'Oferta especial', 'King', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/1.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/2.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/3.webp', '#', 'ACTIVO'),
(13, 3, 'Triple Flora', 'triple-flora', 215.00, 270.00, 3, 'Ideal familias', 'Triple', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/10.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/11.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/12.webp', '#', 'ACTIVO'),
(14, 3, 'Doble Flora', 'doble-flora', 175.00, 230.00, 2, 'Disponibilidad limitada', '2 Camas', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/13.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/14.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/15.webp', '#', 'ACTIVO'),
(15, 3, 'Simple Flora', 'simple-flora', 145.00, 180.00, 1, 'Económica', 'Simple', 'A/C, WiFi, Baño privado', 'Desayuno americano, Horarios de Entrada: 14:00 y Salida: 11:00, Acceso a piscina', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/16.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/17.webp', 'https://aguiladoradaselvahotel.com/wp-content/uploads/2026/05/18.webp', '#', 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_beneficios`
--

CREATE TABLE `categoria_beneficios` (
  `id_categoria` int(11) NOT NULL,
  `id_beneficio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_servicios`
--

CREATE TABLE `categoria_servicios` (
  `id_categoria` int(11) NOT NULL,
  `id_servicio` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `documento_identidad` varchar(20) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `id_hotel`, `nombres`, `apellidos`, `documento_identidad`, `telefono`, `email`, `fecha_registro`) VALUES
(1, 1, 'io', 'io', '99999999', '+51 908890098', '89@gmail.com', '2026-07-10 15:57:27'),
(2, 1, 'io', 'io', '65555555', '+51 567893455', 'huil@gmail.com', '2026-07-10 16:22:10'),
(3, 1, 'huk', 'huk', '55644444', '+51 5464747373', 'huk@gmail.com', '2026-07-10 18:23:38'),
(4, 1, 'd', 'd', '23222222', '+51 2357667488888', '4564@gmail.com', '2026-07-10 18:32:51'),
(5, 1, 'n', 'nnm', '78590008', '+51 90000000007', 'nm@gmail.com', '2026-07-10 19:00:53'),
(6, 1, 'k', 'k', '78777777', '+51 777007777777777', '', '2026-07-10 19:07:51'),
(7, 1, '5', '5', '53111111', '+51 111111111111112', '', '2026-07-10 20:08:44'),
(8, 1, 'bnm', 'bnm', '11111111', '+51 1111133333311', '', '2026-07-10 20:35:22'),
(9, 1, 'cvb', 'cvb', '23111111', '+51 223455555', '', '2026-07-10 20:37:14'),
(10, 1, 'cgy', 'cgyu', '56678888', '+51 686354354', '', '2026-07-10 20:43:54'),
(11, 1, 'v', 'v', '44444444', '+51 22342343242', '', '2026-07-10 20:48:37'),
(12, 1, 'm', 'm', '89888888', '+51 6767676766', '', '2026-07-10 20:50:10'),
(13, 1, 'io', 'io', '99999997', '+51 90889009898', '89@gmail.com', '2026-07-11 04:41:16'),
(14, 1, 'a', 'a', '22222222', '+51 22222222222', '', '2026-07-11 04:50:34'),
(15, 1, 'i', 'i', '88888888', '+51 7899877789', '', '2026-07-11 05:01:45'),
(16, 1, 'io', 'io', '99999990', '+51 908890098', '89@gmail.com', '2026-07-13 14:13:06'),
(17, 1, 'n', '4', '34444444', '+51 4444444455', '', '2026-07-13 14:17:16'),
(18, 1, '9', '9', '98888888', '+51 88888888', '', '2026-07-13 15:49:13'),
(19, 1, '6', '6', '66666666', '+51 6666666', '', '2026-07-13 16:12:25'),
(20, 1, '7', '7', '77777777', '+51 77777777', '', '2026-07-13 16:23:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_no_gratos`
--

CREATE TABLE `clientes_no_gratos` (
  `id_no_grato` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `motivo` text NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes_no_gratos`
--

INSERT INTO `clientes_no_gratos` (`id_no_grato`, `id_hotel`, `dni`, `nombres`, `apellidos`, `motivo`, `estado`, `fecha_registro`) VALUES
(1, 1, '99999999', 'io', 'io', 's', 'ACTIVO', '2026-07-10 16:43:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestas`
--

CREATE TABLE `encuestas` (
  `id_encuesta` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `umbral_publicar` decimal(3,2) NOT NULL DEFAULT 4.00,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuestas`
--

INSERT INTO `encuestas` (`id_encuesta`, `id_hotel`, `titulo`, `descripcion`, `umbral_publicar`, `estado`, `fecha_creacion`) VALUES
(3, 1, 's', 's', 4.00, 'ACTIVA', '2026-07-13 15:48:21'),
(4, 1, '10% de descuento en lavado', 'l', 4.00, 'ACTIVA', '2026-07-13 20:55:29'),
(5, 1, '10% de descuento en lavado', 'l', 4.00, 'ACTIVA', '2026-07-13 21:05:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_areas`
--

CREATE TABLE `encuesta_areas` (
  `id_area` int(11) NOT NULL,
  `id_encuesta` int(11) NOT NULL,
  `id_area_catalogo` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuesta_areas`
--

INSERT INTO `encuesta_areas` (`id_area`, `id_encuesta`, `id_area_catalogo`, `nombre`, `orden`, `estado`) VALUES
(3, 3, 2, 'a', 1, 'ACTIVA'),
(4, 3, 1, 'recepcion', 2, 'ACTIVA'),
(5, 4, 2, 'a', 1, 'ACTIVA'),
(6, 5, 2, 'a', 1, 'ACTIVA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_areas_catalogo`
--

CREATE TABLE `encuesta_areas_catalogo` (
  `id_area_catalogo` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuesta_areas_catalogo`
--

INSERT INTO `encuesta_areas_catalogo` (`id_area_catalogo`, `id_hotel`, `nombre`, `descripcion`, `estado`, `fecha_creacion`) VALUES
(1, 1, 'recepcion', NULL, 'ACTIVA', '2026-07-10 22:41:59'),
(2, 1, 'a', '', 'ACTIVA', '2026-07-10 22:42:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_redes`
--

CREATE TABLE `encuesta_redes` (
  `id_encuesta` int(11) NOT NULL,
  `red` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuesta_redes`
--

INSERT INTO `encuesta_redes` (`id_encuesta`, `red`) VALUES
(3, 'tiktok'),
(4, 'tiktok'),
(5, 'tiktok');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_respuestas`
--

CREATE TABLE `encuesta_respuestas` (
  `id_respuesta` int(11) NOT NULL,
  `id_encuesta` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `nombre_cliente` varchar(160) DEFAULT NULL,
  `email_cliente` varchar(160) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `promedio` decimal(3,2) NOT NULL DEFAULT 0.00,
  `mostro_redes` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_respuesta` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuesta_respuestas`
--

INSERT INTO `encuesta_respuestas` (`id_respuesta`, `id_encuesta`, `id_reserva`, `nombre_cliente`, `email_cliente`, `comentario`, `promedio`, `mostro_redes`, `fecha_respuesta`) VALUES
(2, 3, NULL, 'andres', 'jeissonandrespacayacardenas@gmail.com', 'a', 4.50, 1, '2026-07-13 15:48:32'),
(3, 4, NULL, 'andres', 'jeissonandrespacayacardenas@gmail.com', 'l', 5.00, 1, '2026-07-13 20:55:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_respuesta_detalle`
--

CREATE TABLE `encuesta_respuesta_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_respuesta` int(11) NOT NULL,
  `id_area` int(11) NOT NULL,
  `calificacion` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuesta_respuesta_detalle`
--

INSERT INTO `encuesta_respuesta_detalle` (`id_detalle`, `id_respuesta`, `id_area`, `calificacion`) VALUES
(2, 2, 3, 4),
(3, 2, 4, 5),
(4, 3, 5, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones`
--

CREATE TABLE `habitaciones` (
  `id_habitacion` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `numero_habitacion` varchar(20) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `estado` enum('Disponible','Ocupada','Mantenimiento') DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitaciones`
--

INSERT INTO `habitaciones` (`id_habitacion`, `id_hotel`, `numero_habitacion`, `id_categoria`, `estado`) VALUES
(1, 1, '101', 1, 'Disponible'),
(2, 1, '102', 1, 'Disponible'),
(3, 1, '201', 2, 'Disponible'),
(4, 1, '202', 2, 'Disponible'),
(5, 1, '301', 3, 'Disponible'),
(6, 1, '401', 4, 'Disponible'),
(7, 1, '501', 5, 'Disponible'),
(8, 2, '101', 6, 'Disponible'),
(9, 2, '102', 6, 'Disponible'),
(10, 2, '201', 7, 'Disponible'),
(11, 2, '202', 7, 'Disponible'),
(12, 2, '301', 8, 'Disponible'),
(13, 2, '401', 9, 'Disponible'),
(14, 2, '501', 10, 'Disponible'),
(15, 3, '101', 11, 'Disponible'),
(16, 3, '102', 11, 'Disponible'),
(17, 3, '201', 12, 'Disponible'),
(18, 3, '202', 12, 'Disponible'),
(19, 3, '301', 13, 'Disponible'),
(20, 3, '401', 14, 'Disponible'),
(21, 3, '501', 15, 'Disponible'),
(25, 1, '666', 2, 'Disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitaciones_bloqueos`
--

CREATE TABLE `habitaciones_bloqueos` (
  `id_bloqueo` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `id_habitacion` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `motivo` varchar(250) DEFAULT NULL,
  `estado` enum('ACTIVO','CANCELADO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitaciones_bloqueos`
--

INSERT INTO `habitaciones_bloqueos` (`id_bloqueo`, `id_hotel`, `id_habitacion`, `fecha_inicio`, `fecha_fin`, `motivo`, `estado`, `fecha_registro`) VALUES
(1, 1, 1, '2026-07-02', '2026-07-10', '', 'ACTIVO', '2026-07-09 12:17:07'),
(2, 1, 1, '2026-07-12', '2026-07-13', '', 'CANCELADO', '2026-07-09 12:20:43'),
(3, 1, 1, '2026-07-10', '2026-07-25', '', 'CANCELADO', '2026-07-09 17:24:46'),
(4, 1, 1, '2026-07-12', '2026-07-13', '', 'CANCELADO', '2026-07-09 17:25:31'),
(5, 1, 1, '2026-07-12', '2026-07-13', '', 'CANCELADO', '2026-07-09 17:28:14'),
(6, 1, 1, '2026-07-12', '2026-07-13', '', 'CANCELADO', '2026-07-09 17:28:34'),
(7, 1, 1, '2026-07-13', '2026-07-15', '', 'CANCELADO', '2026-07-13 02:01:34'),
(8, 1, 1, '2026-07-22', '2026-07-24', '', 'CANCELADO', '2026-07-13 02:02:16'),
(9, 1, 2, '2026-07-13', '2026-07-15', '', 'CANCELADO', '2026-07-13 16:22:38'),
(10, 1, 4, '2026-07-13', '2026-07-14', '', 'CANCELADO', '2026-07-13 20:02:28'),
(11, 1, 6, '2026-07-14', '2026-07-16', '', 'CANCELADO', '2026-07-13 20:29:26'),
(12, 1, 3, '2026-07-16', '2026-07-16', '[OCUPADA_MANUAL] Ocupada asignada desde Resume', 'CANCELADO', '2026-07-14 01:44:28'),
(13, 1, 1, '2026-07-14', '2026-07-16', '[FUERA_SERVICIO] Fuera de Servicio asignada desde Resume', 'CANCELADO', '2026-07-14 01:45:37'),
(14, 1, 2, '2026-07-13', '2026-07-14', '[FUERA_SERVICIO] Fuera de Servicio asignada desde Resume', 'CANCELADO', '2026-07-14 01:46:14'),
(15, 1, 1, '2026-07-13', '2026-07-13', '[LIMPIEZA] Limpieza asignada desde Resume', 'CANCELADO', '2026-07-14 01:46:33'),
(16, 1, 4, '2026-07-17', '2026-07-17', '[RESERVADA_MANUAL] Reservada asignada desde Resume', 'CANCELADO', '2026-07-14 01:48:04'),
(17, 1, 6, '2026-07-19', '2026-07-19', '[LIMPIEZA] Limpieza asignada desde Resume', 'CANCELADO', '2026-07-14 01:49:09'),
(18, 1, 3, '2026-07-14', '2026-07-14', '[FUERA_SERVICIO] Fuera de Servicio desde Resume', 'CANCELADO', '2026-07-14 02:06:20'),
(19, 1, 6, '2026-07-19', '2026-07-19', '[BLOQUEADA] Bloqueada desde Resume', 'CANCELADO', '2026-07-14 02:18:59'),
(20, 1, 1, '2026-07-13', '2026-07-15', '[FUERA_SERVICIO] Fuera de Servicio desde Resume', 'ACTIVO', '2026-07-14 02:19:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitacion_categorias`
--

CREATE TABLE `habitacion_categorias` (
  `id_habitacion` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `precio_especial` decimal(10,2) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitacion_categorias`
--

INSERT INTO `habitacion_categorias` (`id_habitacion`, `id_categoria`, `precio_especial`, `estado`) VALUES
(1, 1, NULL, 'ACTIVO'),
(1, 2, NULL, 'ACTIVO'),
(1, 3, NULL, 'ACTIVO'),
(1, 4, NULL, 'ACTIVO'),
(1, 5, NULL, 'ACTIVO'),
(2, 1, NULL, 'ACTIVO'),
(2, 2, NULL, 'ACTIVO'),
(2, 3, NULL, 'ACTIVO'),
(2, 4, NULL, 'ACTIVO'),
(2, 5, NULL, 'ACTIVO'),
(3, 2, NULL, 'ACTIVO'),
(3, 3, NULL, 'ACTIVO'),
(3, 4, NULL, 'ACTIVO'),
(3, 5, NULL, 'ACTIVO'),
(4, 2, NULL, 'ACTIVO'),
(5, 3, NULL, 'ACTIVO'),
(6, 4, NULL, 'ACTIVO'),
(6, 5, NULL, 'ACTIVO'),
(7, 5, NULL, 'ACTIVO'),
(8, 6, NULL, 'ACTIVO'),
(9, 6, NULL, 'ACTIVO'),
(10, 7, NULL, 'ACTIVO'),
(11, 7, NULL, 'ACTIVO'),
(12, 8, NULL, 'ACTIVO'),
(13, 9, NULL, 'ACTIVO'),
(14, 10, NULL, 'ACTIVO'),
(15, 11, NULL, 'ACTIVO'),
(16, 11, NULL, 'ACTIVO'),
(17, 12, NULL, 'ACTIVO'),
(18, 12, NULL, 'ACTIVO'),
(19, 13, NULL, 'ACTIVO'),
(20, 14, NULL, 'ACTIVO'),
(21, 15, NULL, 'ACTIVO'),
(25, 2, NULL, 'ACTIVO'),
(25, 3, NULL, 'ACTIVO'),
(25, 4, NULL, 'ACTIVO'),
(25, 5, NULL, 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitacion_limpieza`
--

CREATE TABLE `habitacion_limpieza` (
  `id_limpieza` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `id_habitacion` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `id_personal` int(11) DEFAULT NULL,
  `fecha` date NOT NULL,
  `fecha_programada` date DEFAULT NULL,
  `hora_programada` time DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `responsable` varchar(120) DEFAULT NULL,
  `estado` enum('PENDIENTE','EN_LIMPIEZA','FINALIZADA','INSPECCIONADA','LISTA_PARA_VENDER') NOT NULL DEFAULT 'PENDIENTE',
  `inspeccionado` tinyint(1) NOT NULL DEFAULT 0,
  `id_usuario_inicio` int(11) DEFAULT NULL,
  `id_usuario_fin` int(11) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitacion_limpieza`
--

INSERT INTO `habitacion_limpieza` (`id_limpieza`, `id_hotel`, `id_habitacion`, `id_reserva`, `id_personal`, `fecha`, `fecha_programada`, `hora_programada`, `hora_inicio`, `hora_fin`, `responsable`, `estado`, `inspeccionado`, `id_usuario_inicio`, `id_usuario_fin`, `observacion`) VALUES
(5, 1, 2, NULL, NULL, '2026-07-13', '2026-07-16', '04:57:00', '11:01:00', '11:01:00', NULL, 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(8, 1, 1, NULL, 1, '2026-07-13', '2026-07-13', '13:02:00', '10:02:00', '14:03:00', 'jeisson andres pacaya', 'FINALIZADA', 0, 2, 2, NULL),
(9, 1, 7, NULL, 1, '2026-07-13', '2026-07-13', '10:48:00', '10:50:00', '10:50:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(11, 1, 1, NULL, 1, '2026-07-13', '2026-07-13', '11:00:00', '11:00:00', '11:00:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(12, 1, 1, NULL, 1, '2026-07-13', '2026-07-18', '11:06:00', '11:07:00', '11:07:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(18, 1, 1, NULL, 1, '2026-07-13', '2026-07-13', '13:31:00', '13:31:00', '13:31:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(20, 1, 25, NULL, 1, '2026-07-13', '2026-07-13', '13:51:00', '13:51:00', '13:52:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(21, 1, 7, NULL, 1, '2026-07-13', '2026-07-13', '14:00:00', '13:53:00', '13:53:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(22, 1, 6, NULL, 1, '2026-07-13', '2026-07-14', '15:03:00', '15:16:00', '15:16:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(23, 1, 6, NULL, 1, '2026-07-13', '2026-07-14', '15:28:00', '15:28:00', '15:28:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(24, 1, 3, NULL, 1, '2026-07-14', '2026-07-14', '20:47:00', '21:06:00', '21:06:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, NULL),
(25, 1, 6, NULL, 1, '2026-07-14', '2026-07-17', '21:07:42', '21:18:00', '21:18:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, 'Limpieza programada desde Resume'),
(26, 1, 6, NULL, 1, '2026-07-14', '2026-07-17', '21:21:08', '21:25:00', '21:25:00', 'jeisson andres pacaya', 'LISTA_PARA_VENDER', 1, 2, 2, 'Limpieza programada desde Resume');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `habitacion_operaciones`
--

CREATE TABLE `habitacion_operaciones` (
  `id_operacion` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `id_habitacion` int(11) NOT NULL,
  `id_reserva` int(11) DEFAULT NULL,
  `tipo_operacion` enum('CHECK_IN','CHECK_OUT','INICIAR_LIMPIEZA','FINALIZAR_LIMPIEZA','INSPECCIONAR','LISTA_PARA_VENDER','FUERA_SERVICIO','REACTIVAR') NOT NULL,
  `estado_resultante` enum('Disponible','Ocupada','Reservada','Check In','Check Out','Limpieza','Inspeccionada','Fuera de Servicio','Bloqueada','Lista para vender') NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) DEFAULT NULL,
  `responsable` varchar(120) DEFAULT NULL,
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `habitacion_operaciones`
--

INSERT INTO `habitacion_operaciones` (`id_operacion`, `id_hotel`, `id_habitacion`, `id_reserva`, `tipo_operacion`, `estado_resultante`, `fecha_hora`, `id_usuario`, `responsable`, `observacion`) VALUES
(1, 1, 3, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-12 21:17:04', 2, NULL, NULL),
(7, 1, 6, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:17:26', 2, NULL, NULL),
(8, 1, 6, NULL, 'FUERA_SERVICIO', 'Fuera de Servicio', '2026-07-12 21:17:28', 2, NULL, NULL),
(9, 1, 6, NULL, 'REACTIVAR', 'Disponible', '2026-07-12 21:17:29', 2, NULL, NULL),
(11, 1, 6, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:19:45', 2, NULL, NULL),
(12, 1, 6, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:19:47', 2, NULL, NULL),
(13, 1, 6, NULL, 'REACTIVAR', 'Disponible', '2026-07-12 21:19:51', 2, NULL, NULL),
(14, 1, 4, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:19:53', 2, NULL, NULL),
(15, 1, 25, NULL, 'LISTA_PARA_VENDER', 'Lista para vender', '2026-07-12 21:19:56', 2, NULL, NULL),
(16, 1, 25, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:19:58', 2, NULL, NULL),
(17, 1, 25, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:19:59', 2, NULL, NULL),
(18, 1, 25, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:19:59', 2, NULL, NULL),
(19, 1, 25, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:20:00', 2, NULL, NULL),
(20, 1, 4, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:20:01', 2, NULL, NULL),
(21, 1, 4, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:20:03', 2, NULL, NULL),
(22, 1, 4, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:20:03', 2, NULL, NULL),
(23, 1, 5, NULL, 'FUERA_SERVICIO', 'Fuera de Servicio', '2026-07-12 21:20:07', 2, NULL, NULL),
(24, 1, 6, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:20:18', 2, NULL, NULL),
(25, 1, 4, NULL, 'LISTA_PARA_VENDER', 'Lista para vender', '2026-07-12 21:23:15', 2, NULL, NULL),
(26, 1, 4, NULL, 'INSPECCIONAR', 'Lista para vender', '2026-07-12 21:23:15', 2, NULL, NULL),
(27, 1, 6, NULL, 'CHECK_IN', 'Check In', '2026-07-13 04:52:00', 2, NULL, NULL),
(28, 1, 6, NULL, 'CHECK_OUT', 'Check Out', '2026-07-14 04:52:00', 2, NULL, NULL),
(32, 1, 2, NULL, 'CHECK_IN', 'Check In', '2026-07-14 04:56:00', 2, NULL, NULL),
(33, 1, 2, NULL, 'CHECK_IN', 'Check In', '2026-07-12 04:56:00', 2, NULL, NULL),
(34, 1, 2, NULL, 'CHECK_OUT', 'Check Out', '2026-07-16 04:57:00', 2, NULL, NULL),
(40, 1, 7, NULL, 'INSPECCIONAR', 'Inspeccionada', '2026-07-13 09:06:00', 2, NULL, NULL),
(41, 1, 4, NULL, 'REACTIVAR', 'Disponible', '2026-07-13 09:06:00', 2, NULL, NULL),
(42, 1, 4, NULL, 'FUERA_SERVICIO', 'Fuera de Servicio', '2026-07-13 09:06:00', 2, NULL, NULL),
(43, 1, 1, NULL, 'CHECK_IN', 'Check In', '2026-07-18 09:19:00', 2, NULL, NULL),
(44, 1, 1, NULL, 'CHECK_OUT', 'Check Out', '2026-07-19 09:19:00', 2, NULL, NULL),
(45, 1, 4, NULL, 'REACTIVAR', 'Disponible', '2026-07-13 09:50:00', 2, NULL, NULL),
(46, 1, 3, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-13 09:55:00', 2, NULL, NULL),
(47, 1, 3, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-13 09:55:00', 2, NULL, NULL),
(48, 1, 3, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-13 09:55:00', 2, 'jeisson andres pacaya', NULL),
(49, 1, 3, NULL, 'LISTA_PARA_VENDER', 'Lista para vender', '2026-07-13 09:55:00', 2, NULL, NULL),
(50, 1, 7, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-13 09:55:00', 2, NULL, NULL),
(51, 1, 5, NULL, 'INSPECCIONAR', 'Inspeccionada', '2026-07-13 09:57:00', 2, NULL, NULL),
(52, 1, 5, NULL, 'LISTA_PARA_VENDER', 'Lista para vender', '2026-07-13 09:58:00', 2, NULL, NULL),
(53, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 14:00:00', 2, 'jeisson andres pacaya', NULL),
(54, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 13:02:00', 2, 'jeisson andres pacaya', NULL),
(55, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 14:02:00', 2, 'jeisson andres pacaya', NULL),
(56, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 10:02:00', 2, NULL, NULL),
(57, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-13 10:03:00', 2, NULL, NULL),
(58, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Limpieza', '2026-07-13 14:03:00', 2, 'jeisson andres pacaya', NULL),
(59, 1, 7, NULL, '', 'Limpieza', '2026-07-13 10:48:00', 2, 'jeisson andres pacaya', NULL),
(60, 1, 7, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 10:50:00', 2, 'jeisson andres pacaya', NULL),
(61, 1, 7, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 10:50:00', 2, NULL, NULL),
(67, 1, 1, NULL, '', 'Limpieza', '2026-07-13 11:00:00', 2, 'jeisson andres pacaya', NULL),
(68, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 11:00:00', 2, 'jeisson andres pacaya', NULL),
(69, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 11:00:00', 2, NULL, NULL),
(70, 1, 2, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 11:01:00', 2, 'jeisson andres pacaya', NULL),
(71, 1, 2, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 11:01:00', 2, NULL, NULL),
(72, 1, 1, NULL, '', 'Limpieza', '2026-07-18 11:06:00', 2, 'jeisson andres pacaya', NULL),
(73, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-09 11:07:00', 2, 'jeisson andres pacaya', NULL),
(74, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 11:07:00', 2, NULL, NULL),
(75, 1, 1, NULL, '', 'Limpieza', '2026-07-19 11:07:00', 2, 'jeisson andres pacaya', NULL),
(76, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 11:07:00', 2, NULL, NULL),
(77, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 11:07:00', 2, NULL, NULL),
(82, 1, 1, NULL, '', 'Limpieza', '2026-07-19 11:18:00', 2, 'jeisson andres pacaya', NULL),
(83, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 11:18:00', 2, NULL, NULL),
(84, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-19 11:18:00', 2, 'jeisson andres pacaya', NULL),
(87, 1, 1, NULL, '', 'Limpieza', '2026-07-19 13:26:00', 2, 'jeisson andres pacaya', NULL),
(88, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 13:26:00', 2, NULL, NULL),
(89, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-19 13:26:00', 2, NULL, NULL),
(90, 1, 1, NULL, '', 'Limpieza', '2026-07-13 13:31:00', 2, 'jeisson andres pacaya', NULL),
(91, 1, 1, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 13:31:00', 2, NULL, NULL),
(92, 1, 1, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 13:31:00', 2, NULL, NULL),
(98, 1, 25, NULL, '', 'Limpieza', '2026-07-13 13:51:00', 2, 'jeisson andres pacaya', NULL),
(99, 1, 25, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 13:51:00', 2, NULL, NULL),
(100, 1, 25, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 13:52:00', 2, 'jeisson andres pacaya', NULL),
(103, 1, 7, NULL, '', 'Limpieza', '2026-07-13 14:00:00', 2, 'jeisson andres pacaya', NULL),
(104, 1, 7, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 13:53:00', 2, NULL, NULL),
(105, 1, 7, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 13:53:00', 2, NULL, NULL),
(110, 1, 7, 29, 'CHECK_IN', 'Check In', '2026-07-20 14:12:00', 2, NULL, NULL),
(111, 1, 7, 29, 'CHECK_OUT', 'Check Out', '2026-07-22 14:12:00', 2, NULL, NULL),
(116, 1, 6, NULL, '', 'Limpieza', '2026-07-14 15:03:00', 2, 'jeisson andres pacaya', NULL),
(117, 1, 6, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 15:16:00', 2, NULL, NULL),
(118, 1, 6, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 15:16:00', 2, NULL, NULL),
(119, 1, 3, 34, 'CHECK_IN', 'Check In', '2026-07-13 15:27:00', 2, NULL, NULL),
(120, 1, 3, 34, 'CHECK_OUT', 'Check Out', '2026-07-16 15:27:00', 2, NULL, NULL),
(121, 1, 6, NULL, '', 'Limpieza', '2026-07-14 15:28:00', 2, 'jeisson andres pacaya', NULL),
(122, 1, 6, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-13 15:28:00', 2, NULL, NULL),
(123, 1, 6, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-13 15:28:00', 2, NULL, NULL),
(124, 1, 3, 32, 'CHECK_IN', 'Check In', '2026-07-20 20:47:00', 2, NULL, NULL),
(125, 1, 3, 32, 'CHECK_OUT', 'Check Out', '2026-07-23 20:47:00', 2, NULL, NULL),
(126, 1, 3, NULL, '', 'Limpieza', '2026-07-14 20:47:00', 2, 'jeisson andres pacaya', NULL),
(127, 1, 3, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-14 21:06:00', 2, NULL, NULL),
(128, 1, 3, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-14 21:06:00', 2, NULL, NULL),
(129, 1, 6, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-14 21:18:00', 2, NULL, NULL),
(130, 1, 6, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-14 21:18:00', 2, NULL, NULL),
(131, 1, 6, NULL, 'INICIAR_LIMPIEZA', 'Limpieza', '2026-07-14 21:25:00', 2, NULL, NULL),
(132, 1, 6, NULL, 'FINALIZAR_LIMPIEZA', 'Lista para vender', '2026-07-14 21:25:00', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hoteles`
--

CREATE TABLE `hoteles` (
  `id_hotel` int(11) NOT NULL,
  `id_plan` int(11) DEFAULT NULL,
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
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hoteles`
--

INSERT INTO `hoteles` (`id_hotel`, `id_plan`, `ruc`, `razon_social`, `nombre_comercial`, `slug`, `gestion_por_habitacion`, `direccion`, `email`, `whatsapp`, `fecha_inicio_plan`, `fecha_fin_plan`, `estado`, `fecha_registro`) VALUES
(1, 3, '20600000001', 'AGUILA DORADA SELVA HOTEL S.A.C.', 'Águila Dorada Selva Hotel', 'aguila-dorada', 1, 'Pucallpa, Ucayali', 'reservas@aguiladorada.local', '51960565050', '2026-07-09', '2027-01-09', 'ACTIVO', '2026-07-09 12:16:12'),
(2, 2, '20600000002', 'INKARIAN HOTEL S.A.C.', 'Inkarian Hotel', 'inkarian', 0, 'Pucallpa, Ucayali', 'reservas@inkarian.local', '51911111111', '2026-07-09', '2026-09-09', 'ACTIVO', '2026-07-09 12:16:12'),
(3, 1, '20600000003', 'FLORA HOTEL S.A.C.', 'Flora Hotel', 'flora', 0, 'Pucallpa, Ucayali', 'reservas@flora.local', '51922222222', '2026-07-09', '2026-08-09', 'ACTIVO', '2026-07-09 12:16:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `hotel_redes_sociales`
--

CREATE TABLE `hotel_redes_sociales` (
  `id_hotel` int(11) NOT NULL,
  `google_reviews` varchar(500) DEFAULT NULL,
  `facebook` varchar(500) DEFAULT NULL,
  `tripadvisor` varchar(500) DEFAULT NULL,
  `instagram` varchar(500) DEFAULT NULL,
  `tiktok` varchar(500) DEFAULT NULL,
  `sitio_web` varchar(500) DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `hotel_redes_sociales`
--

INSERT INTO `hotel_redes_sociales` (`id_hotel`, `google_reviews`, `facebook`, `tripadvisor`, `instagram`, `tiktok`, `sitio_web`, `actualizado_en`) VALUES
(1, '', '', '', '', 'https://hoteles.inkarian.com/views/webreservas.html?hotel=aguila-dorada', '', '2026-07-14 01:55:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_limpieza`
--

CREATE TABLE `personal_limpieza` (
  `id_personal` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `apellidos` varchar(120) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `observacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `personal_limpieza`
--

INSERT INTO `personal_limpieza` (`id_personal`, `id_hotel`, `nombres`, `apellidos`, `dni`, `telefono`, `estado`, `observacion`) VALUES
(1, 1, 'jeisson andres', 'pacaya', '76294886', '906328260', 'ACTIVO', 'i');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id_plan` int(11) NOT NULL,
  `nombre_plan` varchar(60) NOT NULL,
  `meses` int(11) NOT NULL,
  `precio_referencial` decimal(10,2) DEFAULT 0.00,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id_plan`, `nombre_plan`, `meses`, `precio_referencial`, `estado`) VALUES
(1, 'Plan 1 mes', 1, 99.00, 'ACTIVO'),
(2, 'Plan 2 meses', 2, 180.00, 'ACTIVO'),
(3, 'Plan 6 meses', 6, 480.00, 'ACTIVO'),
(4, 'Plan 12 meses', 12, 900.00, 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id_reserva` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_habitacion` int(11) DEFAULT NULL,
  `cantidad_habitaciones` int(11) NOT NULL DEFAULT 1,
  `id_usuario_registro` int(11) DEFAULT NULL,
  `id_usuario_confirmacion` int(11) DEFAULT NULL,
  `id_usuario_tomada` int(11) DEFAULT NULL,
  `fecha_tomada` datetime DEFAULT NULL,
  `bloqueo_hasta` datetime DEFAULT NULL,
  `fecha_checkin` date NOT NULL,
  `fecha_checkout` date NOT NULL,
  `fecha_hora_checkin_real` datetime DEFAULT NULL,
  `fecha_hora_checkout_real` datetime DEFAULT NULL,
  `id_usuario_checkin` int(11) DEFAULT NULL,
  `id_usuario_checkout` int(11) DEFAULT NULL,
  `precio_final` decimal(10,2) NOT NULL,
  `estado_reserva` enum('Pendiente','Confirmada','Atendida','Cancelada','Culminada') NOT NULL DEFAULT 'Pendiente',
  `canal_reserva` enum('Presencial','Redes sociales','Llamada','Consulta WhatsApp','Web') NOT NULL DEFAULT 'Web',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp(),
  `fecha_confirmacion` timestamp NULL DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id_reserva`, `id_hotel`, `id_categoria`, `id_cliente`, `id_habitacion`, `cantidad_habitaciones`, `id_usuario_registro`, `id_usuario_confirmacion`, `id_usuario_tomada`, `fecha_tomada`, `bloqueo_hasta`, `fecha_checkin`, `fecha_checkout`, `fecha_hora_checkin_real`, `fecha_hora_checkout_real`, `id_usuario_checkin`, `id_usuario_checkout`, `precio_final`, `estado_reserva`, `canal_reserva`, `fecha_registro`, `fecha_confirmacion`, `notas`) VALUES
(3, 2, 7, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2026-07-13', '2026-07-15', NULL, NULL, NULL, NULL, 380.00, 'Pendiente', 'Web', '2026-07-09 12:16:12', NULL, 'Consulta Inkarian'),
(4, 3, 12, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, '2026-07-12', '2026-07-14', NULL, NULL, NULL, NULL, 340.00, 'Pendiente', 'Web', '2026-07-09 12:16:12', NULL, 'Consulta Flora'),
(29, 1, 5, 20, 7, 1, 2, 2, NULL, NULL, NULL, '2026-07-20', '2026-07-22', '2026-07-20 14:12:00', '2026-07-22 14:12:00', 2, 2, 320.00, 'Culminada', 'Web', '2026-07-13 18:55:57', '2026-07-13 18:56:23', ''),
(32, 1, 5, 20, 3, 1, 2, 2, NULL, NULL, NULL, '2026-07-20', '2026-07-23', '2026-07-20 20:47:00', '2026-07-23 20:47:00', 2, 2, 540.00, 'Culminada', 'Web', '2026-07-13 19:31:09', '2026-07-13 19:49:17', ''),
(33, 1, 5, 20, 7, 1, 2, 2, NULL, NULL, NULL, '2026-07-13', '2026-07-15', NULL, NULL, NULL, NULL, 320.00, 'Confirmada', 'Web', '2026-07-13 19:50:40', '2026-07-13 19:54:00', ''),
(34, 1, 5, 20, 3, 1, 2, 2, NULL, NULL, NULL, '2026-07-13', '2026-07-16', '2026-07-13 15:27:00', '2026-07-16 15:27:00', 2, 2, 540.00, 'Culminada', 'Web', '2026-07-13 19:54:16', '2026-07-13 19:54:38', ''),
(36, 1, 1, 20, NULL, 1, 2, 2, NULL, NULL, NULL, '2026-07-13', '2026-07-15', NULL, NULL, NULL, NULL, 460.00, 'Atendida', 'Web', '2026-07-14 02:20:12', NULL, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reserva_detalle`
--

CREATE TABLE `reserva_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_reserva` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_habitacion` int(11) DEFAULT NULL,
  `precio_original` decimal(10,2) NOT NULL DEFAULT 0.00,
  `precio_noche` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `motivo_ajuste` varchar(255) DEFAULT NULL,
  `id_usuario_ajuste` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reserva_detalle`
--

INSERT INTO `reserva_detalle` (`id_detalle`, `id_reserva`, `id_categoria`, `id_habitacion`, `precio_original`, `precio_noche`, `descuento`, `motivo_ajuste`, `id_usuario_ajuste`, `subtotal`) VALUES
(25, 29, 5, 7, 160.00, 160.00, 0.00, NULL, 2, 320.00),
(27, 32, 5, 3, 180.00, 180.00, 0.00, NULL, 2, 540.00),
(28, 33, 5, 7, 160.00, 160.00, 0.00, NULL, 2, 320.00),
(29, 34, 5, 3, 180.00, 180.00, 0.00, NULL, 2, 540.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(40) NOT NULL,
  `descripcion` varchar(180) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'superadmin', 'Dueño del sistema: administra hoteles y planes'),
(2, 'admin_hotel', 'Dueño/administrador del hotel'),
(3, 'recepcionista', 'Recepción: registra, atiende y confirma reservas'),
(4, 'cajero', 'Solo lectura de reservas y reportes básicos');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios_catalogo`
--

CREATE TABLE `servicios_catalogo` (
  `id_servicio` int(11) NOT NULL,
  `id_hotel` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(80) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicios_catalogo`
--

INSERT INTO `servicios_catalogo` (`id_servicio`, `id_hotel`, `nombre`, `icono`, `estado`) VALUES
(1, 3, 'WiFi', NULL, 'ACTIVO'),
(2, 2, 'WiFi', NULL, 'ACTIVO'),
(3, 1, 'WiFi', NULL, 'ACTIVO'),
(4, 3, 'A/C', NULL, 'ACTIVO'),
(5, 2, 'A/C', NULL, 'ACTIVO'),
(6, 1, 'A/C', NULL, 'ACTIVO'),
(7, 3, 'Baño privado', NULL, 'ACTIVO'),
(8, 2, 'Baño privado', NULL, 'ACTIVO'),
(9, 1, 'Baño privado', NULL, 'ACTIVO'),
(10, 3, 'Balcón', NULL, 'ACTIVO'),
(11, 2, 'Balcón', NULL, 'ACTIVO'),
(12, 1, 'Balcón', NULL, 'ACTIVO'),
(13, 3, 'Jacuzzi', NULL, 'ACTIVO'),
(14, 2, 'Jacuzzi', NULL, 'ACTIVO'),
(15, 1, 'Jacuzzi', NULL, 'ACTIVO'),
(16, 3, 'TV', NULL, 'ACTIVO'),
(17, 2, 'TV', NULL, 'ACTIVO'),
(18, 1, 'TV', NULL, 'ACTIVO'),
(19, 3, 'Agua caliente', NULL, 'ACTIVO'),
(20, 2, 'Agua caliente', NULL, 'ACTIVO'),
(21, 1, 'Agua caliente', NULL, 'ACTIVO'),
(22, 3, 'Estacionamiento', NULL, 'ACTIVO'),
(23, 2, 'Estacionamiento', NULL, 'ACTIVO'),
(24, 1, 'Estacionamiento', NULL, 'ACTIVO'),
(25, 3, 'Piscina', NULL, 'ACTIVO'),
(26, 2, 'Piscina', NULL, 'ACTIVO'),
(27, 1, 'Piscina', NULL, 'ACTIVO'),
(32, 1, 'andres', NULL, 'ACTIVO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_hotel` int(11) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `fecha_creacion` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_hotel`, `id_rol`, `usuario`, `email`, `password_hash`, `nombres`, `apellidos`, `telefono`, `estado`, `fecha_creacion`) VALUES
(1, NULL, 1, 'superadmin', 'superadmin@crm.local', '$2y$12$jwPEIQyzz601KGOb4hcUYeYVyfvhMeX6iGteNuzR2yVyyzEeh0ijO', 'Super', 'Admin', NULL, 'ACTIVO', '2026-07-09 12:16:12'),
(2, 1, 2, 'aguila_admin', 'admin@aguiladorada.local', '$2y$10$fCekOURrBEJ8xaUDpCqYVe2tJWtB6Hb2iWlRHNoh0iqpVi43XJsMO', 'Admin', 'Águila', '51960565050', 'ACTIVO', '2026-07-09 12:16:12'),
(3, 1, 3, 'aguila_recep', 'recepcion@aguiladorada.local', '$2y$10$0ahsV85s5Ypnw1pWrOqCDek.P.GA7VuAtOWrnZ9PIbdc4/5SGDvSW', 'Recepción', 'Águila', NULL, 'ACTIVO', '2026-07-09 12:16:12'),
(4, 2, 2, 'inkarian_admin', 'admin@inkarian.local', '$2y$12$OheVGJxV9Ad1QwL2yPEVEuVsiS1NsUJsK3n2AGl12ri.CcgQKY772', 'Admin', 'Inkarian', NULL, 'ACTIVO', '2026-07-09 12:16:12'),
(5, 3, 2, 'flora_admin', 'admin@flora.local', '$2y$12$OheVGJxV9Ad1QwL2yPEVEuVsiS1NsUJsK3n2AGl12ri.CcgQKY772', 'Admin', 'Flora', NULL, 'ACTIVO', '2026-07-09 12:16:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_sesiones`
--

CREATE TABLE `usuario_sesiones` (
  `id_sesion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_hotel` int(11) DEFAULT NULL,
  `fecha_login` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_logout` datetime DEFAULT NULL,
  `ip` varchar(60) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `estado` enum('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_sesiones`
--

INSERT INTO `usuario_sesiones` (`id_sesion`, `id_usuario`, `id_hotel`, `fecha_login`, `fecha_logout`, `ip`, `user_agent`, `estado`) VALUES
(1, 2, 1, '2026-07-09 07:16:20', '2026-07-09 11:16:09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(2, 2, 1, '2026-07-09 12:01:33', '2026-07-09 12:24:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(3, 2, 1, '2026-07-09 12:24:07', '2026-07-09 12:24:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(4, 2, 1, '2026-07-09 12:24:16', '2026-07-09 12:24:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(5, 3, 1, '2026-07-09 12:24:26', '2026-07-09 12:24:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(6, 2, 1, '2026-07-09 12:24:34', '2026-07-10 11:18:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(7, 3, 1, '2026-07-10 11:18:57', '2026-07-10 11:21:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(8, 2, 1, '2026-07-10 11:21:22', '2026-07-10 11:30:25', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(9, 3, 1, '2026-07-10 11:30:29', '2026-07-10 11:33:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(10, 2, 1, '2026-07-10 11:33:48', '2026-07-10 11:35:02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(11, 3, 1, '2026-07-10 11:35:06', '2026-07-10 11:38:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(12, 3, 1, '2026-07-10 11:38:07', '2026-07-10 11:43:10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(13, 3, 1, '2026-07-10 11:43:12', '2026-07-10 11:46:17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(14, 2, 1, '2026-07-10 11:46:21', '2026-07-10 15:19:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(15, 3, 1, '2026-07-10 15:19:55', '2026-07-10 15:19:58', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(16, 2, 1, '2026-07-10 15:20:03', '2026-07-10 15:20:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(17, 3, 1, '2026-07-10 15:20:42', '2026-07-10 15:22:03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(18, 2, 1, '2026-07-10 15:22:07', '2026-07-12 21:03:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(19, 3, 1, '2026-07-12 21:03:31', '2026-07-12 21:03:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'CERRADA'),
(20, 2, 1, '2026-07-12 21:03:40', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 OPR/132.0.0.0', 'ABIERTA');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `beneficios_catalogo`
--
ALTER TABLE `beneficios_catalogo`
  ADD PRIMARY KEY (`id_beneficio`),
  ADD UNIQUE KEY `uk_beneficio_hotel_nombre` (`id_hotel`,`nombre`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uk_categoria_hotel_nombre` (`id_hotel`,`nombre`);

--
-- Indices de la tabla `categoria_beneficios`
--
ALTER TABLE `categoria_beneficios`
  ADD PRIMARY KEY (`id_categoria`,`id_beneficio`),
  ADD KEY `fk_cb_beneficio` (`id_beneficio`);

--
-- Indices de la tabla `categoria_servicios`
--
ALTER TABLE `categoria_servicios`
  ADD PRIMARY KEY (`id_categoria`,`id_servicio`),
  ADD KEY `fk_cs_servicio` (`id_servicio`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `uk_cliente_hotel_doc` (`id_hotel`,`documento_identidad`);

--
-- Indices de la tabla `clientes_no_gratos`
--
ALTER TABLE `clientes_no_gratos`
  ADD PRIMARY KEY (`id_no_grato`),
  ADD UNIQUE KEY `uk_no_grato_hotel_dni` (`id_hotel`,`dni`);

--
-- Indices de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD PRIMARY KEY (`id_encuesta`),
  ADD KEY `idx_encuestas_hotel_estado` (`id_hotel`,`estado`);

--
-- Indices de la tabla `encuesta_areas`
--
ALTER TABLE `encuesta_areas`
  ADD PRIMARY KEY (`id_area`),
  ADD KEY `idx_area_encuesta` (`id_encuesta`,`estado`,`orden`),
  ADD KEY `idx_encuesta_area_catalogo` (`id_area_catalogo`);

--
-- Indices de la tabla `encuesta_areas_catalogo`
--
ALTER TABLE `encuesta_areas_catalogo`
  ADD PRIMARY KEY (`id_area_catalogo`),
  ADD UNIQUE KEY `uk_area_catalogo_hotel_nombre` (`id_hotel`,`nombre`),
  ADD KEY `idx_area_catalogo_hotel_estado` (`id_hotel`,`estado`);

--
-- Indices de la tabla `encuesta_redes`
--
ALTER TABLE `encuesta_redes`
  ADD PRIMARY KEY (`id_encuesta`,`red`);

--
-- Indices de la tabla `encuesta_respuestas`
--
ALTER TABLE `encuesta_respuestas`
  ADD PRIMARY KEY (`id_respuesta`),
  ADD KEY `idx_respuesta_encuesta_fecha` (`id_encuesta`,`fecha_respuesta`),
  ADD KEY `fk_respuesta_reserva` (`id_reserva`);

--
-- Indices de la tabla `encuesta_respuesta_detalle`
--
ALTER TABLE `encuesta_respuesta_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD UNIQUE KEY `uk_respuesta_area` (`id_respuesta`,`id_area`),
  ADD KEY `idx_detalle_area` (`id_area`);

--
-- Indices de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD PRIMARY KEY (`id_habitacion`),
  ADD UNIQUE KEY `uk_habitacion_hotel_numero` (`id_hotel`,`numero_habitacion`),
  ADD KEY `idx_habitaciones_categoria` (`id_categoria`);

--
-- Indices de la tabla `habitaciones_bloqueos`
--
ALTER TABLE `habitaciones_bloqueos`
  ADD PRIMARY KEY (`id_bloqueo`),
  ADD KEY `idx_bloqueos_hotel` (`id_hotel`),
  ADD KEY `idx_bloqueos_habitacion` (`id_habitacion`),
  ADD KEY `idx_bloqueos_fechas` (`fecha_inicio`,`fecha_fin`);

--
-- Indices de la tabla `habitacion_categorias`
--
ALTER TABLE `habitacion_categorias`
  ADD PRIMARY KEY (`id_habitacion`,`id_categoria`),
  ADD KEY `fk_hc_categoria` (`id_categoria`);

--
-- Indices de la tabla `habitacion_limpieza`
--
ALTER TABLE `habitacion_limpieza`
  ADD PRIMARY KEY (`id_limpieza`),
  ADD KEY `idx_hl_hotel_fecha` (`id_hotel`,`fecha`),
  ADD KEY `idx_hl_habitacion` (`id_habitacion`),
  ADD KEY `fk_hl_reserva` (`id_reserva`),
  ADD KEY `fk_hl_usuario_inicio` (`id_usuario_inicio`),
  ADD KEY `fk_hl_usuario_fin` (`id_usuario_fin`),
  ADD KEY `idx_hl_personal` (`id_personal`);

--
-- Indices de la tabla `habitacion_operaciones`
--
ALTER TABLE `habitacion_operaciones`
  ADD PRIMARY KEY (`id_operacion`),
  ADD KEY `idx_ho_hotel_habitacion` (`id_hotel`,`id_habitacion`),
  ADD KEY `idx_ho_reserva` (`id_reserva`),
  ADD KEY `idx_ho_fecha` (`fecha_hora`),
  ADD KEY `fk_ho_habitacion` (`id_habitacion`),
  ADD KEY `fk_ho_usuario` (`id_usuario`);

--
-- Indices de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  ADD PRIMARY KEY (`id_hotel`),
  ADD UNIQUE KEY `uk_hoteles_ruc` (`ruc`),
  ADD UNIQUE KEY `uk_hoteles_slug` (`slug`),
  ADD KEY `idx_hoteles_plan` (`id_plan`);

--
-- Indices de la tabla `hotel_redes_sociales`
--
ALTER TABLE `hotel_redes_sociales`
  ADD PRIMARY KEY (`id_hotel`);

--
-- Indices de la tabla `personal_limpieza`
--
ALTER TABLE `personal_limpieza`
  ADD PRIMARY KEY (`id_personal`),
  ADD UNIQUE KEY `uk_personal_hotel_dni` (`id_hotel`,`dni`),
  ADD KEY `idx_personal_hotel_estado` (`id_hotel`,`estado`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id_plan`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id_reserva`),
  ADD KEY `idx_reservas_hotel` (`id_hotel`),
  ADD KEY `idx_reservas_categoria` (`id_categoria`),
  ADD KEY `idx_reservas_cliente` (`id_cliente`),
  ADD KEY `idx_reservas_usuario_registro` (`id_usuario_registro`),
  ADD KEY `idx_reservas_usuario_confirmacion` (`id_usuario_confirmacion`),
  ADD KEY `idx_reservas_usuario_tomada` (`id_usuario_tomada`),
  ADD KEY `idx_reservas_bloqueo_hasta` (`bloqueo_hasta`),
  ADD KEY `fk_reservas_habitacion` (`id_habitacion`);

--
-- Indices de la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `idx_rd_reserva` (`id_reserva`),
  ADD KEY `idx_rd_categoria` (`id_categoria`),
  ADD KEY `idx_rd_habitacion` (`id_habitacion`),
  ADD KEY `idx_rd_usuario_ajuste` (`id_usuario_ajuste`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `uk_roles_nombre` (`nombre_rol`);

--
-- Indices de la tabla `servicios_catalogo`
--
ALTER TABLE `servicios_catalogo`
  ADD PRIMARY KEY (`id_servicio`),
  ADD UNIQUE KEY `uk_servicio_hotel_nombre` (`id_hotel`,`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  ADD UNIQUE KEY `uk_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_rol` (`id_rol`),
  ADD KEY `idx_usuarios_hotel` (`id_hotel`);

--
-- Indices de la tabla `usuario_sesiones`
--
ALTER TABLE `usuario_sesiones`
  ADD PRIMARY KEY (`id_sesion`),
  ADD KEY `idx_sesiones_usuario` (`id_usuario`),
  ADD KEY `idx_sesiones_hotel` (`id_hotel`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `beneficios_catalogo`
--
ALTER TABLE `beneficios_catalogo`
  MODIFY `id_beneficio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `clientes_no_gratos`
--
ALTER TABLE `clientes_no_gratos`
  MODIFY `id_no_grato` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `encuestas`
--
ALTER TABLE `encuestas`
  MODIFY `id_encuesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `encuesta_areas`
--
ALTER TABLE `encuesta_areas`
  MODIFY `id_area` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `encuesta_areas_catalogo`
--
ALTER TABLE `encuesta_areas_catalogo`
  MODIFY `id_area_catalogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `encuesta_respuestas`
--
ALTER TABLE `encuesta_respuestas`
  MODIFY `id_respuesta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `encuesta_respuesta_detalle`
--
ALTER TABLE `encuesta_respuesta_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  MODIFY `id_habitacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `habitaciones_bloqueos`
--
ALTER TABLE `habitaciones_bloqueos`
  MODIFY `id_bloqueo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `habitacion_limpieza`
--
ALTER TABLE `habitacion_limpieza`
  MODIFY `id_limpieza` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `habitacion_operaciones`
--
ALTER TABLE `habitacion_operaciones`
  MODIFY `id_operacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT de la tabla `hoteles`
--
ALTER TABLE `hoteles`
  MODIFY `id_hotel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `personal_limpieza`
--
ALTER TABLE `personal_limpieza`
  MODIFY `id_personal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id_reserva` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `servicios_catalogo`
--
ALTER TABLE `servicios_catalogo`
  MODIFY `id_servicio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuario_sesiones`
--
ALTER TABLE `usuario_sesiones`
  MODIFY `id_sesion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `beneficios_catalogo`
--
ALTER TABLE `beneficios_catalogo`
  ADD CONSTRAINT `fk_beneficio_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categorias_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `categoria_beneficios`
--
ALTER TABLE `categoria_beneficios`
  ADD CONSTRAINT `fk_cb_beneficio` FOREIGN KEY (`id_beneficio`) REFERENCES `beneficios_catalogo` (`id_beneficio`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cb_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `categoria_servicios`
--
ALTER TABLE `categoria_servicios`
  ADD CONSTRAINT `fk_cs_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cs_servicio` FOREIGN KEY (`id_servicio`) REFERENCES `servicios_catalogo` (`id_servicio`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `clientes_no_gratos`
--
ALTER TABLE `clientes_no_gratos`
  ADD CONSTRAINT `fk_no_gratos_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuestas`
--
ALTER TABLE `encuestas`
  ADD CONSTRAINT `fk_encuesta_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuesta_areas`
--
ALTER TABLE `encuesta_areas`
  ADD CONSTRAINT `fk_area_encuesta` FOREIGN KEY (`id_encuesta`) REFERENCES `encuestas` (`id_encuesta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuesta_areas_catalogo`
--
ALTER TABLE `encuesta_areas_catalogo`
  ADD CONSTRAINT `fk_area_catalogo_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuesta_redes`
--
ALTER TABLE `encuesta_redes`
  ADD CONSTRAINT `fk_encuesta_redes_encuesta` FOREIGN KEY (`id_encuesta`) REFERENCES `encuestas` (`id_encuesta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuesta_respuestas`
--
ALTER TABLE `encuesta_respuestas`
  ADD CONSTRAINT `fk_respuesta_encuesta` FOREIGN KEY (`id_encuesta`) REFERENCES `encuestas` (`id_encuesta`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_respuesta_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `encuesta_respuesta_detalle`
--
ALTER TABLE `encuesta_respuesta_detalle`
  ADD CONSTRAINT `fk_detalle_area` FOREIGN KEY (`id_area`) REFERENCES `encuesta_areas` (`id_area`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_respuesta` FOREIGN KEY (`id_respuesta`) REFERENCES `encuesta_respuestas` (`id_respuesta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habitaciones`
--
ALTER TABLE `habitaciones`
  ADD CONSTRAINT `fk_habitaciones_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_habitaciones_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habitaciones_bloqueos`
--
ALTER TABLE `habitaciones_bloqueos`
  ADD CONSTRAINT `fk_bloqueos_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bloqueos_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habitacion_categorias`
--
ALTER TABLE `habitacion_categorias`
  ADD CONSTRAINT `fk_hc_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hc_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `habitacion_limpieza`
--
ALTER TABLE `habitacion_limpieza`
  ADD CONSTRAINT `fk_hl_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hl_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hl_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hl_usuario_fin` FOREIGN KEY (`id_usuario_fin`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hl_usuario_inicio` FOREIGN KEY (`id_usuario_inicio`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `habitacion_operaciones`
--
ALTER TABLE `habitacion_operaciones`
  ADD CONSTRAINT `fk_ho_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ho_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ho_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ho_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `hoteles`
--
ALTER TABLE `hoteles`
  ADD CONSTRAINT `fk_hoteles_plan` FOREIGN KEY (`id_plan`) REFERENCES `planes` (`id_plan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `hotel_redes_sociales`
--
ALTER TABLE `hotel_redes_sociales`
  ADD CONSTRAINT `fk_redes_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `personal_limpieza`
--
ALTER TABLE `personal_limpieza`
  ADD CONSTRAINT `fk_personal_limpieza_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_usuario_confirmacion` FOREIGN KEY (`id_usuario_confirmacion`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_usuario_registro` FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservas_usuario_tomada` FOREIGN KEY (`id_usuario_tomada`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `reserva_detalle`
--
ALTER TABLE `reserva_detalle`
  ADD CONSTRAINT `fk_rd_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rd_habitacion` FOREIGN KEY (`id_habitacion`) REFERENCES `habitaciones` (`id_habitacion`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rd_reserva` FOREIGN KEY (`id_reserva`) REFERENCES `reservas` (`id_reserva`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `servicios_catalogo`
--
ALTER TABLE `servicios_catalogo`
  ADD CONSTRAINT `fk_servicio_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_hoteles` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_sesiones`
--
ALTER TABLE `usuario_sesiones`
  ADD CONSTRAINT `fk_sesiones_hotel` FOREIGN KEY (`id_hotel`) REFERENCES `hoteles` (`id_hotel`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
