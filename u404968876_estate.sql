-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 24-11-2025 a las 05:29:49
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
(3, 'Finca Florencia', 'https://maps.app.goo.gl/UxpVqwJJZSqnsoUB8?g_st=ipc', '', '', '', 2);

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
(1, 'Rafael', 'Montes', '414425237', 'cuadrillero@gmail.com', '2025-11-21', 'activo', NULL, 2, '2025-11-23 05:31:44', '2025-11-24 04:31:54'),
(3, 'juan', 'pérez', '12346578', NULL, '2025-11-24', 'activo', NULL, 2, '2025-11-24 05:16:07', '2025-11-24 05:16:07');

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
(2, 'cuadrillero@gmial.com', '987654321', 'cuadrillero', 'Juan Cuadrillero', '2025-11-22 19:25:27', '2025-11-24 04:12:08'),
(3, 'cuadrillero2@gmial.com', '$2y$10$4UOkqj0lbfY5j2KiLFxfCOpMvWUxaYdQDJ.j/TmymAoJGhTutZyEe', 'cuadrillero', 'cuadrillero2', '2025-11-24 03:58:39', '2025-11-24 03:58:39');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fincas`
--
ALTER TABLE `fincas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `finca_peones`
--
ALTER TABLE `finca_peones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `peones`
--
ALTER TABLE `peones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
