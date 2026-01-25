-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 25-01-2026 a las 15:28:49
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u404968876_estate`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias_peones`
--

CREATE TABLE `asistencias_peones` (
  `id` int(11) NOT NULL,
  `finca_id` int(11) NOT NULL,
  `peon_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `presente` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencias_peones`
--

INSERT INTO `asistencias_peones` (`id`, `finca_id`, `peon_id`, `fecha`, `presente`) VALUES
(1, 3, 3, '2025-11-24', 1),
(2, 3, 1, '2025-11-24', 0),
(6, 3, 3, '2025-11-28', 1),
(7, 3, 1, '2025-11-28', 0),
(9, 3, 1, '2025-12-05', 1),
(10, 3, 3, '2026-01-07', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fincas`
--

CREATE TABLE `fincas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `link_ubicacion` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `tarea_asignada` text DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `cuadrillero_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fincas`
--

INSERT INTO `fincas` (`id`, `nombre`, `link_ubicacion`, `descripcion`, `tarea_asignada`, `observacion`, `cuadrillero_id`) VALUES
(6, 'Finca 1', 'https://maps.app.goo.gl/gYeiQ5sJxHUhxHvBA', '', 'Poda de viñedos. Atado de sarmientos. Riego por goteo. Control de malezas.', 'Finca a la izquierda, con portón negro y árboles al frente.', 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `finca_peones`
--

CREATE TABLE `finca_peones` (
  `id` int(11) NOT NULL,
  `finca_id` int(11) NOT NULL,
  `peon_id` int(11) NOT NULL,
  `asignado_en` date NOT NULL DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peones`
--

CREATE TABLE `peones` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `apellido` varchar(120) NOT NULL DEFAULT '',
  `dni` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `telefono` varchar(30) DEFAULT NULL,
  `cuadrilla_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `peones`
--

INSERT INTO `peones` (`id`, `nombre`, `apellido`, `dni`, `email`, `fecha_ingreso`, `estado`, `telefono`, `cuadrilla_id`, `created_at`, `updated_at`) VALUES
(4, 'Usuario', 'Peón', '11223344', NULL, '2026-01-25', 'activo', '5556667898', 4, '2026-01-25 15:23:12', '2026-01-25 15:23:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','cuadrillero') NOT NULL DEFAULT 'cuadrillero',
  `nombre` varchar(100) DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `password_hash`, `rol`, `nombre`, `creado_en`, `actualizado_en`) VALUES
(1, 'admin@gmail.com', '12345678', 'admin', 'Administrador', '2025-11-22 19:23:29', '2025-11-22 20:12:03'),
(4, 'cuadrillero@gmial.com', '$2y$10$J7JohiQEtdgoX6zFjwG.KeWHQYRHLaAUpalt3vfAtVbFYMFvTyOiu', 'cuadrillero', 'Usuario Cuadrillero', '2026-01-25 15:14:18', '2026-01-25 15:27:16');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencias_peones`
--
ALTER TABLE `asistencias_peones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asistencia` (`finca_id`,`peon_id`,`fecha`);

--
-- Indices de la tabla `fincas`
--
ALTER TABLE `fincas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `finca_peones`
--
ALTER TABLE `finca_peones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_finca_peon` (`finca_id`,`peon_id`);

--
-- Indices de la tabla `peones`
--
ALTER TABLE `peones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_trabajadores_email` (`email`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencias_peones`
--
ALTER TABLE `asistencias_peones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `fincas`
--
ALTER TABLE `fincas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `finca_peones`
--
ALTER TABLE `finca_peones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `peones`
--
ALTER TABLE `peones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
