-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 08-05-2026 a las 00:47:59
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
-- Estructura de tabla para la tabla `actividades_capacitacion`
--

CREATE TABLE `actividades_capacitacion` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre_actividad` varchar(255) NOT NULL,
  `tipo_capacitacion` varchar(100) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `dirigido_a` varchar(100) NOT NULL,
  `estado` enum('programada','en_proceso','completada','cancelada') DEFAULT 'programada',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_trabajadores`
--

CREATE TABLE `actividades_trabajadores` (
  `actividad_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estructura de tabla para la tabla `grupos_personal`
--

CREATE TABLE `grupos_personal` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupos_personal`
--

INSERT INTO `grupos_personal` (`id`, `empresa_id`, `nombre`, `fecha_creacion`) VALUES
(1, 1, 'Bodega', '2026-04-01 21:31:50'),
(2, 1, 'Tecnologia', '2026-04-02 04:55:30'),
(3, 1, 'Contabilidad', '2026-04-02 04:55:42');

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
(1, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-05 00:05:46'),
(2, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-05 16:38:01'),
(3, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-05 22:13:33'),
(4, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-05 22:35:16'),
(5, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-06 00:27:41'),
(6, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-06 00:40:47'),
(7, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-06 01:21:34'),
(8, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-06 03:01:08'),
(9, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-07 18:57:40'),
(10, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-07 22:18:12'),
(11, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-07 22:44:13'),
(12, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-07 22:44:33');

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
(1, 2, '94f5dac642df356f370d839484c28423568376c41e4e2f648aca9da0c75822c9', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 00:05:46', '2026-05-05 15:05:46', 1),
(2, 2, '3d99cdd6959042423af7307e24cc1241cf1bfee791036c6af2be25bb9e18a261', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:38:01', '2026-05-06 07:38:01', 1),
(3, 2, 'c320e73199aca6a72dbd91e574efca9e9192960f9207cd6439bc9beeaac3b7b5', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 16:38:01', '2026-06-04 23:38:01', 1),
(4, 2, '12e24a73d3c8c63a5c9bcfd062d08dad22175dd5e450c5fb844ab00239354d53', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-05 22:13:33', '2026-05-06 13:13:33', 1),
(5, 2, '56a1d8dac115a97e9da62b63396d519bd9815977c8c7f2c596ca70dfb6e2b80d', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-05 22:35:16', '2026-05-06 13:35:16', 1),
(6, 2, 'd553e2f4e729395479c9e2279f2aa2e7bb0491282cc774e5ebd4e9bf5fe96276', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-06 00:27:41', '2026-05-06 15:27:41', 1),
(7, 2, '33d83c5667bba23007eb53732efab3642461f18b83b1118502f8d21ce9869be5', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-06 00:40:47', '2026-05-06 15:40:47', 1),
(8, 2, '648033e7a2a3645686634742eda7e0fd28657b31db1c9b5ff5401f9ff3e54953', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-06 01:21:34', '2026-05-06 16:21:34', 1),
(9, 2, '1406a30970a9c24e547c961fb6f878c4e10cabda66b315b053795eda21ab3ded', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-06 03:01:08', '2026-05-06 18:01:08', 1),
(10, 2, 'e4ca7b5717ef279debb92c82b8cfd42242deb1d3e4d2aa3bb7f90393036c59e9', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 18:57:40', '2026-05-08 09:57:40', 0),
(11, 2, 'f07ba525d517347155ec093fe503653a7e8c3eda1d9cbea1a2cbe8ef8d0450c6', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/147.0.7727.99 Mobile/15E148 Safari/604.1', '2026-05-07 22:18:12', '2026-05-08 13:18:12', 1),
(12, 1, 'e35f2cf9abaf10fb588f4a2ee2add0e60147e9b7c1a92ebad0f857e40bed861b', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 22:44:33', '2026-05-08 13:44:33', 1);

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
  `num_doc_empresa` varchar(50) DEFAULT NULL,
  `clase_riesgo` varchar(10) DEFAULT NULL,
  `actividad_economica` varchar(255) DEFAULT NULL,
  `grupo_id` int(11) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `rol`, `licencia_sst`, `tipo_licencia`, `numero_licencia`, `fecha_licencia`, `expedida_por`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `fecha_registro`, `ultimo_acceso`, `activo`, `logo_empresa`, `nombre_empresa`, `tipo_persona`, `regimen_tributario`, `tipo_doc_empresa`, `num_doc_empresa`, `clase_riesgo`, `actividad_economica`, `grupo_id`, `foto_perfil`) VALUES
(1, 1, 'Esteban', 'Reuto', '1010', 'estebanreuto4@gmail.com', '3001112233', 'representante', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', '2026-03-26 00:22:35', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/perfiles/user_1_1778193949.jpeg'),
(2, 1, 'WILLMER ESTEBAN', 'REUTO ROMERO', '2020', 'wreuto@estudiantes.areandina.edu.co', '3109998877', 'sst', 'si', 'Profesional', 'L-12345', NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/perfiles/user_2_1778192121.jpeg'),
(3, 1, 'Esteban', 'Reuto (Trab)', '3030', 'estebanreuto27@gmail.com', '3205554433', 'trabajador', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades_capacitacion`
--
ALTER TABLE `actividades_capacitacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa_actividad` (`empresa_id`);

--
-- Indices de la tabla `actividades_trabajadores`
--
ALTER TABLE `actividades_trabajadores`
  ADD PRIMARY KEY (`actividad_id`,`usuario_id`),
  ADD KEY `idx_usuario_actividad` (`usuario_id`);

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
-- Indices de la tabla `grupos_personal`
--
ALTER TABLE `grupos_personal`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `idx_rol` (`rol`),
  ADD KEY `fk_grupo_usuario` (`grupo_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades_capacitacion`
--
ALTER TABLE `actividades_capacitacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT de la tabla `grupos_personal`
--
ALTER TABLE `grupos_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Filtros para la tabla `actividades_trabajadores`
--
ALTER TABLE `actividades_trabajadores`
  ADD CONSTRAINT `fk_act_trab_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades_capacitacion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_act_trab_usr` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

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

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_grupo_usuario` FOREIGN KEY (`grupo_id`) REFERENCES `grupos_personal` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
