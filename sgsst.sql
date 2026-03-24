-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 24-03-2026 a las 04:43:23
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sgsst`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doc_asignacion_sst`
--

CREATE TABLE `doc_asignacion_sst` (
  `id` int(11) NOT NULL,
  `sst_id` int(11) NOT NULL,
  `representante_id` int(11) DEFAULT NULL,
  `estado` enum('borrador','pendiente_firma','firmado') DEFAULT 'borrador',
  `firma_sst` longtext DEFAULT NULL,
  `firma_representante` longtext DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_firma` datetime DEFAULT NULL,
  `archivo_pdf` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta_sociodemografica`
--

CREATE TABLE `encuesta_sociodemografica` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `edad` int(11) NOT NULL,
  `estado_civil` enum('Soltero','Casado','Union libre','Divorciado','Viudo') NOT NULL,
  `genero` enum('Masculino','Femenino','Otro') NOT NULL,
  `personas_cargo` int(11) DEFAULT 0,
  `escolaridad` enum('Primaria','Bachillerato','Técnico','Tecnólogo','Profesional','Posgrado') NOT NULL,
  `vivienda` enum('Propia','Arriendo','Familiar','Otra') NOT NULL,
  `tiempo_libre` varchar(100) DEFAULT NULL,
  `experiencia` varchar(100) DEFAULT NULL,
  `estrato` int(11) DEFAULT NULL,
  `convive_con` varchar(100) DEFAULT NULL,
  `raza` varchar(50) DEFAULT NULL,
  `tipo_contrato` enum('Fijo','Indefinido','Prestacion de servicios') NOT NULL,
  `turno` varchar(100) DEFAULT NULL,
  `antiguedad` varchar(100) DEFAULT NULL,
  `enfermedad` varchar(255) DEFAULT NULL,
  `fuma` enum('No fumo','Ocasionalmente','Frecuentemente') DEFAULT NULL,
  `alcohol` enum('No consumo','Ocasionalmente','Frecuentemente') DEFAULT NULL,
  `deporte` varchar(100) DEFAULT NULL,
  `tipo_personal` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `encuesta_sociodemografica`
--

INSERT INTO `encuesta_sociodemografica` (`id`, `usuario_id`, `edad`, `estado_civil`, `genero`, `personas_cargo`, `escolaridad`, `vivienda`, `tiempo_libre`, `experiencia`, `estrato`, `convive_con`, `raza`, `tipo_contrato`, `turno`, `antiguedad`, `enfermedad`, `fuma`, `alcohol`, `deporte`, `tipo_personal`, `fecha_registro`, `fecha_actualizacion`) VALUES
(1, 3, 35, 'Casado', 'Masculino', 2, 'Bachillerato', 'Propia', NULL, NULL, NULL, NULL, NULL, 'Indefinido', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 02:45:36', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar2_planillas`
--

CREATE TABLE `estandar2_planillas` (
  `id` int(11) NOT NULL,
  `mes` int(2) NOT NULL,
  `anio` int(4) NOT NULL,
  `archivo_url` varchar(255) NOT NULL,
  `subido_por` int(11) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_actividad`
--

CREATE TABLE `logs_actividad` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `logs_actividad`
--

INSERT INTO `logs_actividad` (`id`, `usuario_id`, `accion`, `descripcion`, `ip_address`, `fecha`) VALUES
(1, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-03-24 02:46:14'),
(2, 1, 'LOGOUT', 'Cierre de sesión', '::1', '2026-03-24 02:46:35'),
(3, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-03-24 02:49:08'),
(4, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-03-24 02:52:35'),
(5, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-03-24 02:52:59'),
(6, 1, 'LOGOUT', 'Cierre de sesión', '::1', '2026-03-24 02:53:24'),
(7, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-03-24 03:05:51'),
(8, 1, 'LOGOUT', 'Cierre de sesión', '::1', '2026-03-24 03:10:58'),
(9, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-03-24 03:40:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `enlace` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `precio_normal` decimal(10,2) NOT NULL,
  `precio_descuento` decimal(10,2) DEFAULT 0.00,
  `trabajadores` int(11) NOT NULL,
  `popular` tinyint(1) DEFAULT 0,
  `clase_btn` varchar(50) DEFAULT 'btn-outline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `nombre`, `precio_normal`, `precio_descuento`, `trabajadores`, `popular`, `clase_btn`) VALUES
(1, 'Básico', 70000.00, 50000.00, 15, 0, 'btn-outline'),
(2, 'Pro SG-SST', 120000.00, 0.00, 50, 1, 'btn-solid'),
(3, 'Enterprise', 350000.00, 250000.00, 999, 0, 'btn-outline');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_caracteristicas`
--

CREATE TABLE `plan_caracteristicas` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `texto` varchar(255) NOT NULL,
  `incluido` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `codigo_2fa` varchar(6) DEFAULT NULL,
  `codigo_2fa_expira` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_expiracion` timestamp NULL DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones`
--

INSERT INTO `sesiones` (`id`, `usuario_id`, `token`, `codigo_2fa`, `codigo_2fa_expira`, `ip_address`, `user_agent`, `fecha_creacion`, `fecha_expiracion`, `activa`) VALUES
(1, 1, 'a62dd2859892b68e98ef497843d951adf93ee155e3e57359d5a9c506b6ffccf7', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 02:46:14', '2026-03-24 16:46:14', 0),
(2, 2, 'e83e14196d9a68c0dd782dfa2faf8ec5e1a786a28f7f0fdf7b6d758ae2053b7b', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 02:49:08', '2026-03-24 16:49:08', 0),
(3, 1, 'c53354be96a9468f5b5314aa0902f5b93c4148eb8620cd2a424f01654f10b739', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 02:52:59', '2026-03-24 16:52:59', 0),
(4, 1, '5ce153081785d18188e3897438f20d820935d4ed483a32f59bbc1823c81849ab', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 03:05:51', '2026-03-24 17:05:51', 0),
(5, 1, '2fa9801b299431c98e1bb492c08ba2f18fe6b0eb2222912bf2ac1f61cc5428ad', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 03:40:44', '2026-03-24 17:40:44', 1),
(6, 1, '582bddb96bce8f003b94ae0ab06b818d6db6482c09b3a392304a6ac071c51cf5', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-24 03:40:44', '2026-04-23 10:40:44', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_empresas`
--

CREATE TABLE `solicitudes_empresas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `apellido` varchar(150) NOT NULL,
  `cedula` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `firma` longtext DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `plan_id` int(11) DEFAULT NULL,
  `trabajadores_extra` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudes_empresas`
--

INSERT INTO `solicitudes_empresas` (`id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `estado`, `fecha_creacion`, `plan_id`, `trabajadores_extra`) VALUES
(1, 'Constructora Vertix S.A.S', 'Reuto', '900111222', 'estebanreuto4@gmail.com', '3001112233', NULL, 'Bogotá', NULL, NULL, NULL, 'aprobada', '2026-03-24 02:45:36', 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `super_admins`
--

CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `super_admins`
--

INSERT INTO `super_admins` (`id`, `username`, `password_hash`, `nombre`, `created_at`) VALUES
(1, 'admin', '$2y$10$zHMbGuze.4su6uf2tm5V3.j0gThW22xzdRIYnezDu5pYRKcPLd1/e', 'Super Administrador', '2026-03-20 14:32:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `rol` enum('representante','sst','trabajador') NOT NULL,
  `licencia_sst` enum('si','no') DEFAULT NULL,
  `tipo_licencia` varchar(100) DEFAULT NULL,
  `numero_licencia` varchar(50) DEFAULT NULL,
  `fecha_licencia` date DEFAULT NULL,
  `expedida_por` varchar(150) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `barrio` varchar(100) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `firma` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `logo_empresa` varchar(255) DEFAULT NULL,
  `nombre_empresa` varchar(150) DEFAULT NULL,
  `tipo_persona` varchar(50) DEFAULT NULL,
  `regimen_tributario` varchar(100) DEFAULT NULL,
  `tipo_doc_empresa` varchar(20) DEFAULT NULL,
  `num_doc_empresa` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `rol`, `licencia_sst`, `tipo_licencia`, `numero_licencia`, `fecha_licencia`, `expedida_por`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `fecha_registro`, `ultimo_acceso`, `activo`, `logo_empresa`, `nombre_empresa`, `tipo_persona`, `regimen_tributario`, `tipo_doc_empresa`, `num_doc_empresa`) VALUES
(1, 1, 'Esteban', 'Reuto (Rep)', '1010', 'estebanreuto4@gmail.com', '3001112233', 'representante', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 'W', 'Reuto (SST)', '2020', 'wreuto@estudiantes.areandina.edu.co', '3109998877', 'sst', 'si', 'Profesional', 'L-12345', NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 1, 'Esteban', 'Reuto (Trab)', '3030', 'estebanreuto27@gmail.com', '3205554433', 'trabajador', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `doc_asignacion_sst`
--
ALTER TABLE `doc_asignacion_sst`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sst_id` (`sst_id`);

--
-- Indices de la tabla `encuesta_sociodemografica`
--
ALTER TABLE `encuesta_sociodemografica`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- Indices de la tabla `estandar2_planillas`
--
ALTER TABLE `estandar2_planillas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_mes_anio` (`mes`,`anio`),
  ADD KEY `subido_por` (`subido_por`);

--
-- Indices de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `plan_caracteristicas`
--
ALTER TABLE `plan_caracteristicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_activa_expira` (`activa`,`fecha_expiracion`);

--
-- Indices de la tabla `solicitudes_empresas`
--
ALTER TABLE `solicitudes_empresas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `super_admins`
--
ALTER TABLE `super_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_cedula` (`cedula`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `doc_asignacion_sst`
--
ALTER TABLE `doc_asignacion_sst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuesta_sociodemografica`
--
ALTER TABLE `encuesta_sociodemografica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `estandar2_planillas`
--
ALTER TABLE `estandar2_planillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `plan_caracteristicas`
--
ALTER TABLE `plan_caracteristicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `solicitudes_empresas`
--
ALTER TABLE `solicitudes_empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `doc_asignacion_sst`
--
ALTER TABLE `doc_asignacion_sst`
  ADD CONSTRAINT `doc_asignacion_sst_ibfk_1` FOREIGN KEY (`sst_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `encuesta_sociodemografica`
--
ALTER TABLE `encuesta_sociodemografica`
  ADD CONSTRAINT `encuesta_sociodemografica_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar2_planillas`
--
ALTER TABLE `estandar2_planillas`
  ADD CONSTRAINT `estandar2_planillas_ibfk_1` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  ADD CONSTRAINT `logs_actividad_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plan_caracteristicas`
--
ALTER TABLE `plan_caracteristicas`
  ADD CONSTRAINT `plan_caracteristicas_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `sesiones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
