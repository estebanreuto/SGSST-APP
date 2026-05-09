-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 08-05-2026 a las 23:58:16
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
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `enlace_reunion` varchar(255) DEFAULT NULL,
  `estado` enum('programada','en_proceso','completada','cancelada') DEFAULT 'programada',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_capacitacion`
--

INSERT INTO `actividades_capacitacion` (`id`, `empresa_id`, `nombre_actividad`, `tipo_capacitacion`, `categoria`, `dirigido_a`, `fecha_inicio`, `fecha_fin`, `enlace_reunion`, `estado`, `fecha_creacion`) VALUES
(1, 1, 'Hola esto es una prueba', 'Inducción', 'Físico', 'Trabajador Específico', '2026-05-07 12:00:00', '2026-05-07 13:00:00', NULL, 'programada', '2026-05-08 04:01:30'),
(2, 1, 'Hola esto es una prueba 2', 'Inducción', 'Biológico', 'Toda la empresa', '2026-05-08 12:00:00', '2026-05-08 14:05:00', NULL, 'programada', '2026-05-08 17:42:14'),
(3, 1, 'Hola esto es una prueba 212', 'Re Inducción', 'Eléctrico', 'Grupo: Bodega', '2026-05-08 16:00:00', '2026-05-08 17:00:00', NULL, 'programada', '2026-05-08 17:42:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_trabajadores`
--

CREATE TABLE `actividades_trabajadores` (
  `actividad_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_trabajadores`
--

INSERT INTO `actividades_trabajadores` (`actividad_id`, `usuario_id`) VALUES
(1, 3);

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
(12, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-07 22:44:33'),
(13, 1, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-07 22:51:09'),
(14, 3, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-07 22:51:27'),
(15, 3, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-07 22:51:56'),
(16, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-07 22:52:29'),
(17, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-08 17:22:38'),
(18, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-08 20:18:32');

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
(12, 1, 'e35f2cf9abaf10fb588f4a2ee2add0e60147e9b7c1a92ebad0f857e40bed861b', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 22:44:33', '2026-05-08 13:44:33', 0),
(13, 3, 'de3bfb9a09b46504742e5936628c849dfc77358adc773f484128f0464080f0c4', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 22:51:27', '2026-05-08 13:51:27', 0),
(14, 2, '7c7bd98319ab078f1d8ef4927bbcf3983df7c3b740ceb80555304a35c4435f28', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-07 22:52:29', '2026-05-08 13:52:29', 1),
(15, 2, 'a861c5c3a7e03a8b23237e5391a259337c14017cedfcef9fdfc3dd142ac69ef9', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:22:37', '2026-05-09 08:22:37', 0);

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
(1, 'Constructora Vertix S.A.S', 'Reuto', '900111222', 'estebanreuto4@gmail.com', '3001112233', NULL, 'Bogotá', NULL, NULL, NULL, 'aprobada', '2026-03-24 02:45:36', 1, 0),
(2, 'Esteban', 'Reuto', '1116856979', 'estebanreuto27@gmail.com', '3012994599', 'Cra 3 # 13A - 55', 'Tame - Arauca', '', '', 'data:image/png;base64,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', 'pendiente', '2026-05-08 21:52:19', 1, 0);

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
(1, 1, 'Esteban', 'Reuto', '1010', 'estebanreuto4@gmail.com', '3001112233', 'representante', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', '2026-03-26 00:22:35', 1, 'uploads/logos/logo_empresa_1_1778194209.jpeg', 'Constructora Vertix S.A.S', 'Juridica', 'Responsable de IVA', 'NIT', '900111222', NULL, '4921', NULL, 'uploads/perfiles/user_1_1778194254.jpeg'),
(2, 1, 'WILLMER ESTEBAN', 'REUTO ROMERO', '2020', 'wreuto@estudiantes.areandina.edu.co', '3109998877', 'sst', 'si', 'Profesional', 'L-12345', NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/perfiles/user_2_1778192121.jpeg'),
(3, 1, 'Esteban', 'Reuto (Trab)', '3030', 'estebanreuto27@gmail.com', '3205554433', 'trabajador', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'uploads/perfiles/user_3_1778194305.jpeg');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `solicitudes_empresas`
--
ALTER TABLE `solicitudes_empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
