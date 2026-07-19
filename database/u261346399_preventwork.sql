-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 19-07-2026 a las 03:27:50
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
-- Base de datos: `u261346399_preventwork`
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
  `calendar_provider` varchar(20) DEFAULT NULL,
  `calendar_event_id` varchar(255) DEFAULT NULL,
  `calendar_event_url` text DEFAULT NULL,
  `estado` enum('programada','en_proceso','completada','cancelada','ejecutada','reprogramada','no_ejecutada') DEFAULT 'programada',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `modalidad` varchar(50) DEFAULT 'Virtual',
  `lugar_exacto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_capacitacion`
--

INSERT INTO `actividades_capacitacion` (`id`, `empresa_id`, `nombre_actividad`, `tipo_capacitacion`, `categoria`, `dirigido_a`, `fecha_inicio`, `fecha_fin`, `enlace_reunion`, `calendar_provider`, `calendar_event_id`, `calendar_event_url`, `estado`, `fecha_creacion`, `modalidad`, `lugar_exacto`, `descripcion`) VALUES
(9, 1, 'Hola mundo', 'Inducción', 'Biológico', 'Toda la empresa', '2026-06-09 12:00:00', '2026-06-12 12:00:00', NULL, NULL, NULL, NULL, 'ejecutada', '2026-06-10 03:47:54', 'Sistema', '', ''),
(10, 1, 'Hola mundo', 'Inducción', 'Físico', 'Toda la empresa', '2026-06-10 12:00:00', '2026-06-12 12:00:00', NULL, NULL, NULL, NULL, 'ejecutada', '2026-06-11 01:20:22', 'Sistema', '', ''),
(12, 1, 'Hola mundocdscdsdcs', 'Charla de Seguridad', 'Biomecánicos', 'Toda la empresa', '2026-06-10 12:00:00', '2026-06-10 22:00:00', NULL, NULL, NULL, NULL, 'ejecutada', '2026-06-11 02:44:29', 'Sistema', '', ''),
(13, 1, 'Hola mundo', 'Inducción', 'Biológico', 'Toda la empresa', '2026-06-19 12:00:00', '2026-06-27 22:00:00', NULL, NULL, NULL, NULL, 'reprogramada', '2026-06-20 02:42:24', 'Virtual', '', 'Hola mundosacadcadscdsa'),
(14, 17, 'Prueba 1', 'Inducción', 'Legal', 'Trabajador Específico', '2026-07-14 08:00:00', '2026-07-14 09:00:00', NULL, NULL, NULL, NULL, 'programada', '2026-07-12 21:22:48', 'Virtual', '', 'Pruebas de Funcionamiento');

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
(14, 21);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenamiento_archivos`
--

CREATE TABLE `almacenamiento_archivos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `estandar_numero` smallint(5) UNSIGNED NOT NULL,
  `estandar_nombre` varchar(220) NOT NULL,
  `subestandar_slug` varchar(120) DEFAULT NULL,
  `subestandar_nombre` varchar(220) DEFAULT NULL,
  `carpeta_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `nombre_guardado` varchar(255) NOT NULL,
  `ruta_relativa` varchar(700) NOT NULL,
  `tipo_mime` varchar(150) DEFAULT NULL,
  `extension` varchar(20) DEFAULT NULL,
  `tamano_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `usuario_id` int(11) DEFAULT NULL,
  `codigo_documento` varchar(80) DEFAULT NULL,
  `version_documento` varchar(30) DEFAULT NULL,
  `fecha_documento` date DEFAULT NULL,
  `estado_documental` varchar(30) NOT NULL DEFAULT 'sin_control',
  `origen_modulo` varchar(80) DEFAULT NULL,
  `control_registro_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `almacenamiento_archivos`
--

INSERT INTO `almacenamiento_archivos` (`id`, `empresa_id`, `estandar_numero`, `estandar_nombre`, `subestandar_slug`, `subestandar_nombre`, `carpeta_id`, `nombre_original`, `nombre_guardado`, `ruta_relativa`, `tipo_mime`, `extension`, `tamano_bytes`, `usuario_id`, `codigo_documento`, `version_documento`, `fecha_documento`, `estado_documental`, `origen_modulo`, `control_registro_id`, `creado_en`, `actualizado_en`) VALUES
(3, 1, 1, 'Asignacion de persona que disena el Sistema de Gestion de SST', NULL, NULL, NULL, 'PW-SST-E01-ACTA_V1.0_Acta-Designacion-SST.pdf', '20260717-004232-bd9bcfea2e18f7.pdf', 'SGSST-APP/uploads/empresas/empresa-1/estandar-01-asignacion-de-persona-que-disena-el-sistema-de-gestion-de-sst/20260717-004232-bd9bcfea2e18f7.pdf', 'application/pdf', 'pdf', 65158, 1, 'PW-SST-E01-ACTA', 'V1.0', '2026-07-16', 'aprobado', 'estandar1_pdf', 2, '2026-07-16 22:42:32', '2026-07-16 22:43:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenamiento_carpetas`
--

CREATE TABLE `almacenamiento_carpetas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `estandar_numero` smallint(5) UNSIGNED NOT NULL,
  `subestandar_slug` varchar(120) DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(180) NOT NULL,
  `nombre_guardado` varchar(220) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `almacenamiento_compartidos`
--

CREATE TABLE `almacenamiento_compartidos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_objeto` enum('archivo','carpeta') NOT NULL,
  `objeto_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `vence_en` datetime NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias_capacitacion`
--

CREATE TABLE `asistencias_capacitacion` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `firma` mediumtext NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calendar_connections`
--

CREATE TABLE `calendar_connections` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `provider` enum('google','microsoft') NOT NULL,
  `account_email` varchar(190) DEFAULT NULL,
  `token_payload` longtext NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `connected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_actas`
--

CREATE TABLE `capacitaciones_actas` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `intento_id` int(11) NOT NULL,
  `firma` longtext NOT NULL,
  `aceptacion` text NOT NULL,
  `enviada_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_actas`
--

INSERT INTO `capacitaciones_actas` (`id`, `curso_id`, `usuario_id`, `intento_id`, `firma`, `aceptacion`, `enviada_en`) VALUES
(2, 5, 3, 2, 'data:image/png;base64,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ihEQDBczVQrNT0BFBAgQIECAAAECBAgQIECAQP0COiRQp4BguM551RUBAgQIECBAgAABAucKOI4AAQIECBAg0ICAYLiBSdYiAQIECBwWsJUAAQIECBAgQIAAAQIECLQm0GIw3Noc65cAAQIECBAgQIAAAQIECLQooGcCBAgQOCAgGD6AYxMBAgQIECBAgEBJAmolQIAAAQIECBAgQGCpgGB4qZT9CBDIT0BFBAgQIECAAAECBAgQIECAQP0COtxEQDC8CatBCRAgQIAAAQIECBAgQOBcAccRIECAAAEC2wsIhrc3dgYCBAgQIEDgsICtBAgQIECAAAECBAgQIHBlAcHwlcGdLgQsBAgQIECAAAECBAgQIECAQP0COiRAIGcBwXDOs6M2AgQIECBAgAABAiUJqJUAAQIECBAgQKAYAcFwMVOlUAIECOQnoCICBAgQIECAAAECBAgQIECgTIFTguEyO1Q1AQIECBAgQIAAAQIECBAgcIqAfQkQIECgAQHBcAOTrEUCBAgQIECAwGEBWwkQIECAAAECBAgQaE1AMNzajOuXQAhYCBAgQIAAAQIECBAgQIAAgfoFdEjggIBg+ACOTQQIECBAgAABAgQIEChJQK0ECBAgQIAAgaUCguGlUvYjQIAAAQL5CaiIAAECBAgQIECAAAECBAicJSAYPovtVgc5LwECBAgQIECAAAECBAgQIFC/gA4JECCwvYBgeHtjZyBAgAABAgQIECBwWMBWAgQIECBAgAABAlcWEAxfGdzpCBAgEAIWAgQIECBAgAABAgQIECBAoH6BnDsUDOc8O2ojQIAAAQIECBAgQIAAgZIE1EqAAAECBIoREAwXM1UKJUCAAAECBPITUBEBAgQIECBAgAABAgTKFBAMlzlvqr6VgPMSIECAAAECBAgQIECAAAEC9QvokEADAoLhBiZZiwQIECBAgAABAgQIHBawlQABAgQIECDQmoBguLUZ1y8BAgQIhICFAAECBAgQIECAAAECBAg0LdBIMNz0HGueAAECBAgQIECAAAECBAg0IqBNAgQIEFgqIBheKmU/AgQIECBAgACB/ARURIAAAQIECBAgQIDAWQKC4bPYHESAwK0EnJcAAQIECBAgQIAAAQIECBCoX0CH2wsIhrc3dgYCBAgQIECAAAECBAgQOCxgKwECBAgQIHBlAcHwlcGdjgABAgQIEAgBCwECBAgQIECAAAECBAjcUkAwfEv9ls6tVwIECBAgQIAAAQIECBAgQKB+AR0SIFCMgGC4mKlSKAECBAgQIECAAIH8BFREgAABAgQIECBQpoBguMx5UzUBAgRuJeC8BAgQIECAAAECBAgQIECAQAUCR4LhCjrUAgECBAgQIECAAAECBAgQIHBEwGYCBAgQaE1AMNzajOuXAAECBAgQIBACFgIECBAgQIAAAQIEmhYQDDc9/ZpvSUCvBAgQIECAAAECBAgQIECAQP0COiSwVEAwvFTKfgQIECBAgAABAgQIEMhPQEUECBAgQIAAgbMEBMNnsTmIAAECBAjcSsB5CRAgQIAAAQIECBAgQIDA5QKC4csNtx3B6AQIECBAgAABAgQIECBAgED9AjokQIDAlQUEw1cGdzoCBAgQIECAAAECIWAhQIAAAQIECBAgcEsBwfAt9Z2bAIGWBPRKgAABAgQIECBAgAABAgQI1C9QTIeC4WKmSqEECBAgQIAAAQIECBAgkJ+AiggQIECAQJkCguEy503VBAgQIECAwK0EnJcAAQIECBAgQIAAAQIVCAiGK5hELWwrYHQCBAgQIECAAAECBAgQIECgfgEdEmhNQDDc2ozrlwABAgQIECBAgACBELAQIECAAAECBJoWEAw3Pf2aJ0CAQEsCeiVAgAABAgQIECBAgAABAgR2AvUGw7sO/SRAgAABAgQIECBAgAABAgTqFdAZAQIECJwlIBg+i81BBAgQIECAAAECtxJwXgIECBAgQIAAAQIELhcQDF9uaAQCBLYVMDoBAgQIECBAgAABAgQIECBQv4AOrywgGL4yuNMRIECAAAECBAgQIECAQAhYCBAgQIAAgVsKCIZvqe/cBAgQIECgJQG9EiBAgAABAgQIECBAgEA2AoLhbKaivkJ0RIAAAQIECBAgQIAAAQIECNQvoEMCBMoUEAyXOW+qJkCAAAECBAgQIHArAeclQIAAAQIECBCoQEAwXMEkaoEAAQLbChidAAECBAgQIECAAAECBAgQqE3g42C4tg71Q4AAAQIECBAgQIAAAQIECHwsYA0BAgQINC0gGG56+jVPgAABAgQItCSgVwIECBAgQIAAAQIECOwEBMM7CT8J1CegIwIECBAgQIAAAQIECBAgQKB+AR0SOEtAMHwWm4MIECBAgAABAgQIECBwKwHnJUCAAAECBAhcLvD/AQAA//+N9AcPAAAABklEQVQDAGjE+PnGUqsTAAAAAElFTkSuQmCC', 'Declaro que participé en la actividad asignada, presenté y aprobé la evaluación correspondiente.', '2026-06-09 22:50:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_categorias_personalizadas`
--

CREATE TABLE `capacitaciones_categorias_personalizadas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_capacitacion` varchar(100) NOT NULL,
  `categoria` varchar(180) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_cursos`
--

CREATE TABLE `capacitaciones_cursos` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `tipo_contenido` enum('video','enlace') NOT NULL DEFAULT 'enlace',
  `contenido_url` varchar(500) DEFAULT NULL,
  `video_archivo` varchar(500) DEFAULT NULL,
  `imagen_portada` varchar(500) DEFAULT NULL,
  `instrucciones` text DEFAULT NULL,
  `escala_calificacion` enum('5','10','100') NOT NULL DEFAULT '100',
  `puntaje_aprobacion` decimal(8,2) NOT NULL DEFAULT 70.00,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_cursos`
--

INSERT INTO `capacitaciones_cursos` (`id`, `actividad_id`, `tipo_contenido`, `contenido_url`, `video_archivo`, `imagen_portada`, `instrucciones`, `escala_calificacion`, `puntaje_aprobacion`, `creado_en`, `actualizado_en`) VALUES
(5, 9, 'enlace', 'https://youtu.be/RR5YnzBlVtA?si=XXBxbcGre3wJddHq', NULL, 'uploads/capacitaciones/portada_auto_9.png', 'Responde con responsabilidad', '100', 60.00, '2026-06-10 03:47:54', '2026-06-10 03:47:54'),
(6, 10, 'enlace', 'https://youtu.be/RR5YnzBlVtA?si=XXBxbcGre3wJddHq', NULL, 'uploads/capacitaciones/portada_auto_10.png', 'sfvsdfvfdsvdfvsvfdsvsdvdfvdf', '100', 60.00, '2026-06-11 01:20:22', '2026-06-11 01:20:22'),
(8, 12, 'enlace', '', NULL, NULL, '', '100', 60.00, '2026-06-11 02:44:29', '2026-06-11 02:44:29'),
(9, 14, 'enlace', '', NULL, NULL, '', '5', 3.00, '2026-07-12 21:22:48', '2026-07-12 21:22:48');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_intentos`
--

CREATE TABLE `capacitaciones_intentos` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `puntaje_obtenido` decimal(8,2) NOT NULL DEFAULT 0.00,
  `puntaje_escala` decimal(8,2) NOT NULL DEFAULT 0.00,
  `aprobado` tinyint(1) NOT NULL DEFAULT 0,
  `iniciado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `finalizado_en` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_intentos`
--

INSERT INTO `capacitaciones_intentos` (`id`, `curso_id`, `usuario_id`, `puntaje_obtenido`, `puntaje_escala`, `aprobado`, `iniciado_en`, `finalizado_en`) VALUES
(2, 5, 3, 1.00, 100.00, 1, '2026-06-09 22:49:57', '2026-06-09 22:49:57'),
(4, 6, 3, 0.00, 0.00, 0, '2026-06-10 20:57:18', '2026-06-10 20:57:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_materiales`
--

CREATE TABLE `capacitaciones_materiales` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `tipo` enum('texto','video','enlace','documento','imagen') NOT NULL DEFAULT 'texto',
  `contenido` longtext DEFAULT NULL,
  `archivo` varchar(500) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_materiales`
--

INSERT INTO `capacitaciones_materiales` (`id`, `curso_id`, `titulo`, `tipo`, `contenido`, `archivo`, `orden`, `creado_en`) VALUES
(4, 8, 'Bienvenida', 'texto', 'sdfvfsdvsfdvsdvfsdfv', '', 0, '2026-06-11 02:44:29'),
(5, 8, 'adscadscasdcasdcasd', 'imagen', '', 'uploads/capacitaciones/material_12_1_1781145869.jpeg', 1, '2026-06-11 02:44:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_opciones`
--

CREATE TABLE `capacitaciones_opciones` (
  `id` int(11) NOT NULL,
  `pregunta_id` int(11) NOT NULL,
  `texto` varchar(500) NOT NULL,
  `es_correcta` tinyint(1) NOT NULL DEFAULT 0,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_opciones`
--

INSERT INTO `capacitaciones_opciones` (`id`, `pregunta_id`, `texto`, `es_correcta`, `orden`) VALUES
(12, 6, 'Verdadero', 1, 0),
(13, 6, 'Falso', 0, 1),
(14, 7, 'vfdsvdfvsdf', 1, 0),
(15, 7, 'vfdsvfdvsdvfd', 0, 1),
(16, 8, 'fgfbdgfbfg', 1, 0),
(17, 8, 'bfgbdfgbdfg', 0, 1),
(24, 12, 'dacdscvadscadscads', 1, 0),
(25, 12, 'cadscadscasdcasdcadscasd', 0, 1),
(26, 13, 'Verdadero', 1, 0),
(27, 13, 'Falso', 0, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_preguntas`
--

CREATE TABLE `capacitaciones_preguntas` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `enunciado` text NOT NULL,
  `tipo` enum('unica','multiple','verdadero_falso') NOT NULL DEFAULT 'unica',
  `puntos` decimal(8,2) NOT NULL DEFAULT 1.00,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_preguntas`
--

INSERT INTO `capacitaciones_preguntas` (`id`, `curso_id`, `enunciado`, `tipo`, `puntos`, `orden`) VALUES
(6, 5, 'Hola mundo', 'verdadero_falso', 1.00, 0),
(7, 6, 'vsdfvsdfvsdvsdf', 'unica', 1.00, 0),
(8, 6, 'fvsdvsdfvfdsvsdfvfds', 'multiple', 1.00, 1),
(12, 8, 'dsacasdcadscasdcads', 'unica', 100.00, 0),
(13, 9, 'Prueba 1 aparece si o no ?', 'verdadero_falso', 5.00, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_progreso`
--

CREATE TABLE `capacitaciones_progreso` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `porcentaje` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `seccion_actual` int(11) NOT NULL DEFAULT 0,
  `completado_en` datetime DEFAULT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_progreso`
--

INSERT INTO `capacitaciones_progreso` (`id`, `curso_id`, `usuario_id`, `porcentaje`, `seccion_actual`, `completado_en`, `actualizado_en`) VALUES
(2, 6, 3, 100, 0, '2026-06-10 20:57:18', '2026-06-11 01:57:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `capacitaciones_respuestas`
--

CREATE TABLE `capacitaciones_respuestas` (
  `id` int(11) NOT NULL,
  `intento_id` int(11) NOT NULL,
  `pregunta_id` int(11) NOT NULL,
  `opciones_json` longtext NOT NULL,
  `correcta` tinyint(1) NOT NULL DEFAULT 0,
  `puntos_obtenidos` decimal(8,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `capacitaciones_respuestas`
--

INSERT INTO `capacitaciones_respuestas` (`id`, `intento_id`, `pregunta_id`, `opciones_json`, `correcta`, `puntos_obtenidos`) VALUES
(3, 2, 6, '[12]', 1, 1.00),
(7, 4, 7, '[]', 0, 0.00),
(8, 4, 8, '[]', 0, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `control_documental_config`
--

CREATE TABLE `control_documental_config` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `estandar_numero` smallint(5) UNSIGNED NOT NULL,
  `codigo_prefijo` varchar(40) NOT NULL DEFAULT 'PW-SST',
  `separador` char(1) NOT NULL DEFAULT '-',
  `version_prefijo` varchar(8) NOT NULL DEFAULT 'V',
  `version_inicial` varchar(20) NOT NULL DEFAULT 'V1.0',
  `exigir_codigo_nombre` tinyint(1) NOT NULL DEFAULT 1,
  `actualizado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `control_documental_registros`
--

CREATE TABLE `control_documental_registros` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `estandar_numero` smallint(5) UNSIGNED NOT NULL,
  `almacenamiento_archivo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `doc_asignacion_id` int(11) DEFAULT NULL,
  `tipo_documento` enum('formato','soporte','pdf_legalizado') NOT NULL DEFAULT 'soporte',
  `nombre_documento` varchar(220) NOT NULL,
  `codigo_documento` varchar(80) NOT NULL,
  `version_documento` varchar(30) NOT NULL,
  `fecha_documento` date NOT NULL,
  `estado` enum('validado','aprobado','rechazado','obsoleto') NOT NULL DEFAULT 'validado',
  `archivo_original` varchar(255) DEFAULT NULL,
  `resultado_validacion` text DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `control_documental_registros`
--

INSERT INTO `control_documental_registros` (`id`, `empresa_id`, `estandar_numero`, `almacenamiento_archivo_id`, `doc_asignacion_id`, `tipo_documento`, `nombre_documento`, `codigo_documento`, `version_documento`, `fecha_documento`, `estado`, `archivo_original`, `resultado_validacion`, `observaciones`, `usuario_id`, `creado_en`, `actualizado_en`) VALUES
(2, 1, 1, 3, 1, 'pdf_legalizado', 'Acta de designación del Responsable SG-SST', 'PW-SST-E01-ACTA', 'V1.0', '2026-07-16', 'aprobado', 'PW-SST-E01-ACTA_V1.0_Acta-Designacion-SST.pdf', '{\"origen\":\"firmas_electronicas\",\"estado\":\"legalizado\"}', NULL, 1, '2026-07-16 22:42:32', '2026-07-16 22:42:32');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cpanel_admins`
--

CREATE TABLE `cpanel_admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `wompi_public` varchar(255) DEFAULT NULL,
  `wompi_private` varchar(255) DEFAULT NULL,
  `wompi_integrity` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cpanel_admins`
--

INSERT INTO `cpanel_admins` (`id`, `username`, `password`, `nombre`, `email`, `foto_perfil`, `wompi_public`, `wompi_private`, `wompi_integrity`, `created_at`) VALUES
(1, 'admin', '$2y$10$zHMbGuze.4su6uf2tm5V3.j0gThW22xzdRIYnezDu5pYRKcPLd1/e', 'Super Administrador', 'admin@preventwork.com', '../uploads/perfiles_admin/admin_1_1778286342.jpeg', 'pub_test_GDP0XsIX6xKx9Y4EviZd94yq3GOdgXQb', 'prv_test_xCaTFF4r5GgEdfMBY3sKFMzXAJDDMRfn', 'test_integrity_r6UkyxV3GsHe9noC6B58GQ5zslngU50P', '2026-03-20 19:32:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `demo_prospectos`
--

CREATE TABLE `demo_prospectos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `empresa` varchar(180) NOT NULL,
  `email` varchar(180) NOT NULL,
  `telefono` varchar(40) NOT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `cargo` varchar(120) DEFAULT NULL,
  `cantidad_trabajadores` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `interes` varchar(80) NOT NULL DEFAULT 'Plan PEM',
  `estado` varchar(30) NOT NULL DEFAULT 'nuevo',
  `notas` text DEFAULT NULL,
  `origen` varchar(60) NOT NULL DEFAULT 'demo_pem',
  `paginas_vistas` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `primera_visita` datetime NOT NULL DEFAULT current_timestamp(),
  `ultima_visita` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `acepta_contacto` tinyint(1) NOT NULL DEFAULT 1,
  `acceso_estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `acceso_token_hash` char(64) DEFAULT NULL,
  `acceso_token_sufijo` varchar(12) DEFAULT NULL,
  `acceso_generado_en` datetime DEFAULT NULL,
  `acceso_expira_en` datetime DEFAULT NULL,
  `acceso_revocado_en` datetime DEFAULT NULL,
  `acceso_decidido_en` datetime DEFAULT NULL,
  `acceso_decidido_por` int(11) DEFAULT NULL,
  `notificacion_enviada_en` datetime DEFAULT NULL,
  `notificacion_error` varchar(500) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `demo_prospectos`
--

INSERT INTO `demo_prospectos` (`id`, `nombre_completo`, `empresa`, `email`, `telefono`, `ciudad`, `cargo`, `cantidad_trabajadores`, `interes`, `estado`, `notas`, `origen`, `paginas_vistas`, `primera_visita`, `ultima_visita`, `ip_address`, `user_agent`, `acepta_contacto`, `acceso_estado`, `acceso_token_hash`, `acceso_token_sufijo`, `acceso_generado_en`, `acceso_expira_en`, `acceso_revocado_en`, `acceso_decidido_en`, `acceso_decidido_por`, `notificacion_enviada_en`, `notificacion_error`, `creado_en`, `actualizado_en`) VALUES
(2, 'Esteban Reuto', 'Vertix Developers', 'estebanreuto4@gmail.com', '3001259241', 'Tame', 'Representante', 20, 'Plan PEM', 'nuevo', NULL, 'demo_pem', 18, '2026-07-16 12:28:51', '2026-07-16 17:04:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', 1, 'aprobado', '0c598798f4e86dd2de806318e7711c3c289220642147391994d1cc0ed2d6e2f3', 'b8599f4c1a', '2026-07-16 17:03:45', '2026-07-24 00:03:45', NULL, '2026-07-16 17:03:45', 1, NULL, NULL, '2026-07-16 12:28:51', '2026-07-16 17:04:44');

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

--
-- Volcado de datos para la tabla `doc_asignacion_sst`
--

INSERT INTO `doc_asignacion_sst` (`id`, `sst_id`, `representante_id`, `estado`, `firma_sst`, `firma_representante`, `fecha_creacion`, `fecha_firma`, `archivo_pdf`) VALUES
(1, 2, 1, 'firmado', 'data:image/png;base64,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', 'data:image/png;base64,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', '2026-05-09 23:01:14', '2026-05-11 13:26:22', 'data:application/pdf;base64,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YXRlCi9CTSAvTm9ybWFsCi9jYSAxCi9DQSAxCj4+CmVuZG9iago2IDAgb2JqCjw8L1R5cGUgL0ZvbnQKL1N1YnR5cGUgL1R5cGUwCi9CYXNlRm9udCAvTVBERkFBK0RlamFWdVNlcmlmQ29uZGVuc2VkCi9FbmNvZGluZyAvSWRlbnRpdHktSAovRGVzY2VuZGFudEZvbnRzIFs3IDAgUl0KL1RvVW5pY29kZSA4IDAgUgo+PgplbmRvYmoKNyAwIG9iago8PC9UeXBlIC9Gb250Ci9TdWJ0eXBlIC9DSURGb250VHlwZTIKL0Jhc2VGb250IC9NUERGQUErRGVqYVZ1U2VyaWZDb25kZW5zZWQKL0NJRFN5c3RlbUluZm8gOSAwIFIKL0ZvbnREZXNjcmlwdG9yIDEwIDAgUgovRFcgNTQwCi9XIFsgMzIgWyAyODYgMzYxIDQxNCA3NTQgNTcyIDg1NSA4MDEgMjQ3IDM1MSAzNTEgNDUwIDc1NCAyODYgMzA0IDI4NiAzMDMgXQogNDggNTcgNTcyIDU4IDU5IDMwMyA2MCA2MiA3NTQgNjMgWyA0ODIgOTAwIDY1MCA2NjEgNjg4IDcyMSA2NTcgNjI0IDcxOSA3ODUgMzU1IDM2MCA2NzIgNTk4IDkyMSA3ODcgNzM4IDYwNSA3MzggNjc3IDYxNiA2MDAgNzU4IDY1MCA5MjUgNjQxIDU5NCA2MjUgMzUxIDMwMyAzNTEgNzU0IDQ1MCA0NTAgNTM2IDU3NiA1MDQgNTc2IDUzMiAzMzMgNTc2IDU4MCAyODggMjc5IDU0NSAyODggODUzIDU4MCA1NDIgNTc2IDU3NiA0MzAgNDYxIDM2MSA1ODAgNTA4IDc3MCA1MDcgNTA4IDQ3NCA1NzIgMzAzIDU3MiA3NTQgXQogXQovQ0lEVG9HSURNYXAgMTEgMCBSCj4+CmVuZG9iago4IDAgb2JqCjw8L0xlbmd0aCAzNDY+PgpzdHJlYW0KL0NJREluaXQgL1Byb2NTZXQgZmluZHJlc291cmNlIGJlZ2luCjEyIGRpY3QgYmVnaW4KYmVnaW5jbWFwCi9DSURTeXN0ZW1JbmZvCjw8L1JlZ2lzdHJ5IChBZG9iZSkKL09yZGVyaW5nIChVQ1MpCi9TdXBwbGVtZW50IDAKPj4gZGVmCi9DTWFwTmFtZSAvQWRvYmUtSWRlbnRpdHktVUNTIGRlZgovQ01hcFR5cGUgMiBkZWYKMSBiZWdpbmNvZGVzcGFjZXJhbmdlCjwwMDAwPiA8RkZGRj4KZW5kY29kZXNwYWNlcmFuZ2UKMSBiZWdpbmJmcmFuZ2UKPDAwMDA+IDxGRkZGPiA8MDAwMD4KZW5kYmZyYW5nZQplbmRjbWFwCkNNYXBOYW1lIGN1cnJlbnRkaWN0IC9DTWFwIGRlZmluZXJlc291cmNlIHBvcAplbmQKZW5kCgplbmRzdHJlYW0KZW5kb2JqCjkgMCBvYmoKPDwvUmVnaXN0cnkgKEFkb2JlKQovT3JkZXJpbmcgKFVDUykKL1N1cHBsZW1lbnQgMAo+PgplbmRvYmoKMTAgMCBvYmoKPDwvVHlwZSAvRm9udERlc2NyaXB0b3IKL0ZvbnROYW1lIC9NUERGQUErRGVqYVZ1U2VyaWZDb25kZW5zZWQKIC9DYXBIZWlnaHQgNzI5CiAvWEhlaWdodCA1MTkKIC9Gb250QkJveCBbLTY5MyAtMzQ3IDE1MTIgMTEwOV0KIC9GbGFncyA0CiAvQXNjZW50IDkyOAogL0Rlc2NlbnQgLTIzNgogL0xlYWRpbmcgMAogL0l0YWxpY0FuZ2xlIDAKIC9TdGVtViA4NwogL01pc3NpbmdXaWR0aCA1NDAKIC9TdHlsZSA8PCAvUGFub3NlIDwgMCAwIDIgNiA2IDYgNSA2IDUgMiAyIDQ+ID4+Ci9Gb250RmlsZTIgMTIgMCBSCj4+CmVuZG9iagoxMSAwIG9iago8PC9MZW5ndGggMzA0Ci9GaWx0ZXIgL0ZsYXRlRGVjb2RlCj4+CnN0cmVhbQp4nO3P51YIAACA0e+c7FFmZCV7ZFQqhGwtlMiI6P1foofon3PvG9zapYH2tLd97e9ABzvU4Y50tMGGOtbxTnSyU51uuDOdbaRzne9CF7vUaJcb60pXu9b1bnSzW93uTncb7173e9DDJppsqkdNN9Nsj3vS0+Z61vNeNN/LXvW6N73tXe/70EKLLbXcSh/71OdWW+tL633tWxt970c/+9Vmv/vTVn/71/Zu8wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAPB/2QF3vBKPCmVuZHN0cmVhbQplbmRvYmoKMTIgMCBvYmoKPDwvTGVuZ3RoIDExMjgwCi9GaWx0ZXIgL0ZsYXRlRGVjb2RlCi9MZW5ndGgxIDE5NzgwCj4+CnN0cmVhbQp4nM18CVxUVd//OffcO+wIAi6VegFxSQRlxF1jGxRlExD3dGAGmIFhcGYQUVHccd9xR0tTUys1LUsr057Meso2sx4zNbO93mdpE5nD/3fOvTMMZD3P533f/+f/Z7pzzz3L97f/zu9cMIQRQoGoFhE0Iys3Ns5y9IcV0PM9XPmFFn2FT7nvOoRwCjw3Fc5yyOu0R35CSEiAvs1FFcWWr2r/8RpC5EMYX1Wst1cgL/ggcQY8+xeXVReVfZbyGTyXIdT1dIlRbxCmj9uAUGR3GB9YAh0BTq+L8DwVnruXWByznx4b+Q481wJ+XZm1UG8LM29DKMobxl+36GdXiOcI0I56F57lcr3FOPdYUiE8/x2h8PcrrHZH80I0GaFRr7HxCpuxorJa8ws8f4GQ9CjC5Bhej0Roa6XtQKGrcidXUZHQHqTSaDSSRhIE8Qukac5Gd+/5ikgGJJRdpDOgBCQ3N2tCaSje4WXBt0HEPTeuIuWHqPeH1PZD8I35XUSL4S7Chz3Pb25mnDU3N9/mz2y2iCSkAa15Ix/ki/yQPwoAi7RDQSgYtUchKBSFoQ6oI+qEOqMH0IOA2QV1Rd2As3AUgSJRdxSFeqCeqBfqjR5GfVA06otiUCzqh/qjOKRFA1A8GogGocFoCBqKhqHhaAQaiR4BeRJREkpGKUiHUtEoNBqloTFoLEpHGSgTZaFsNA7loFyUh8ajfDQBTUSTQLdT0FQ0DT2KpqMZSA869EV2JKDT6C34fACtdGRGM9FytBfad9Bc3v+q6M0+0POJxOZ+gE7j3jBPYB8cC1oQ0MuAo4WxOzC/CJ73ogI+3kje5p8d5G2hCgkkG1rZfMVedJoME0XytnLxVW+BbC+iw6wtvY3qYV4OugqfJEAfg86iT/BitB9fQTVoDbKLTP9dsK90BXgxowLpCv/8HSRklFmfWbqiCQVKZpDzLKDvV/pxb5xDZpAiPBEkFPBhMhp6lyKzOAM+PfknhcunyCAIc4G+Ki96W5gq9BZ74sNAh9F4G/APoxHAbxFwOhougfFPvkF7iT9w2Fl6FY3xGqPxxxqvGrCGgOYSLd6h6QIWqCF5gJAJfWtQJr4KVMBdntNIIhEwipaDjglRaYZjCeMmym9MCu8b3eZRDvKSj6HsYwHV8unm5uyJ4oPSpGPSQ8dIlPcxMSry1h8N3uobPTZ7onzski5FRdXNSIG+3InQZE/QDf26lL6IWwosD35PoDWF1pNfpP3QhiwREhweHBUeHD6VbG96R/ircwCt9wr87Z82TW++6l7zbfwd2MYP4gANGjBQG9chLFQTGdEjREvCIuPv6bT9U1P7a3Xi1yOsJYMzMwcPysyQnmh6q6lJoXqSHBZ2wnpOCwdHArXIYJyyDn+5TrrivCL0YRfMWwv5YJ70MszrBvMiSbgf1gKJ8LBwfkWGRMIVHh/OL3KYpmM8cCqdtuNRHEbfGIMfpBem7ZzePG3HVPotHjKGfoEfeZQspSfIcqrHe6h+Bz2xnRbgBnZtx5k78B6gtIt+QJrAszQQ6/0hf43E8QN69CSBOCy0Q0ccrO2KOwbHQN/AQcHaYPYQGYN7QgMU4AVSxGDBUlRqLp48rehRI11VQFYsv3zw9WOTpxzKLvCbZ75+4r2XJ+e/mOJbPj1/djdhp5dpYrY53Dkah65YTfCdhIMr9573x/70J+/+vWmjY5kPHZ781IYDb7Sj/8SBghbykbH5tsYGkeAHeScScosWNBOhAea0cQOBU892T5dZgNsQLY4M8XjGu7MmTcnImDSJ5GROmpyROXFSxtpDT65Zf/BQt7qm0ys6rXvyyXXrDhyUNh3YsunQwc2bDzojD23ZdPDgps1PTv70zJlr186cvSbcvPsvTcC1My/97drZFz8Fey0B3toDbz6Q01AU5joDVqTwnoE4MgIxpXHVgb9wvUVyZjvikXhQZHx4WGQg9uqoJbedP2dlXt45dZ9goVvnPnp13tf07ytXx0S/c2L4YyVFAfbCqY6RevzuyFE+j+PDO9tNGrvzg68EY+apogO3Ru3bMDEfe3++9CvjiOqkPWciIigtrZg5eUT1TOfN7BdMpaWLPp/xPPggZj6IU7gPcg8E71Ncj41toK8K6eADAfDQQwgOaj8oTMNuHYX0azduXFtVXb2KvmrBn+BCbMCfWArMdAHdR/fTBWbEsRfB+pHK+pD4ge2Dg4Se8R3YbdGqOXNWfXrjBn21wIwX4ql4Gl5oLrDQnnQX3U17srVdhHQyD3QIkYUDsFdUSGSI1LMvHiQRLYki8+hePCOFvu4/x49eTMEz6N4UPMRvjj8eKl557oVZn9DFuOaTWc+frvoE19DFnwDeBYihVEkEfwE5IWpYtMWHB5N0fJd6rade+K4kOi8/7rws9H9c6K/wn0S/wdvQbcgOqONIPAJrwTDJeVknvBctvqg7szTqyM+T+bxx+KrQVbAzHYYA9Dj8NtUK9l187CnIEQ3oR0bXM0M8naKNS02N06a48gKbi5qTBItqC/BTfH09jVwvXblrAZ+qbr4N+U3xdxQVx7QYGcGNwb+1vEcYs7KubuXKFStWXvv552uf/vRTKs7GuTgPZ9Nj9Ch9ih4rxNtwObbibbSEbqAbaQmjews2+XWA7Qs1Srw2WIqP0gZrw7DmW/oPPHret2bxbxcWf984x8zm1sDcmzAX6oiQyOCRmEsEeR+zleHxmOcJcOjvjTNem/b+v+gPHfzoU0I/c9NuvG25fc28hWvEmdh3yPDPz1+jlzv60+Wn6X4Lfq3+3sqth9cC/mqQ0x/wHwZ8FV3jxdMKaK0nyztKUPfB8UoDaHlFPms6n/f69e2rn3je+aPpUqHRZ/falXv2N+T3bdi9cE71Mn+jVB8d89Kh5afkB64eee9GnBZHrN98fMexU0UbNi5eVTtfyceDoQwaDLQF7nXBPi6ZtNxhtMISOoBMdO762HlKSH8Wo2b0MW4kDzR9STVmHGUhPzX5ltJrIMMukMEL7MiyNSxkYrQPCxVaC8D5DiZ7nNtiazI/aaJfTb1gNHpvWrJu+/Z14+b2y5Cu7Kd53bvTn77+gf7KGF5b/+6F85cTkoUfGa9zgUY494co5g8qIqQR0kIOHAOFQ44J4zlGEKYsWLFiwcLlKw6M2Km/9M2997/4GUdg71G7cox+lYXPHEm9fP785TdeuviR8PexY4DubfpfuBo/iuvw0127/mI007+BbDtAR0EgG8SED7goZKvgcDHI+c9l+GfnJhOplKyNG6T+p/FU8GLGY2fVZ6GGwi4WQQHEo+02r2dihB4yZd2iRevYFTWvwlpTY62YV7Dj8H/d+OwfR3bUbbh54cKN9Xjb3uPH9z52/DiZVbdtW92KbdsudXx7z/u3b7+/5+2ODz5Td/Ly5ZN1zzB9OVReOjN9YZaHhfgBStigcDBMPDdMe899gzw6fHfBG1+Bnv5Fb9C7o3bnGP0rC54+Sj+rraurrV1eJ+weOwb7f30bB9Pl9HE6m+Z07fqbsQxHiRGgyncvgSq5X70COeAYqVZi1zMLBHu0z+nitLqUAdoUXdyAlJQBcTqeGQZnZAivDc5IHzIkPYNjsZz0LGB5QxnVkpVCPIDUDDU4NY5lmLhUV6oS/QZnZA4CQJ6XLqBHpVTxAOzvyEcIw/FY0jVNJgfuzRcXkSa6lC4/jN87gN/jNC/gIimVNKg1EcQC+1wQF7HJ9+aThr2/7W2LGRKPGSifRA6SpsM09gCNPYyrGV5N822yhPtFBPiuB+vEHfFhLLe4ogSn6AawSmqA7uL0o+lvfHXxDeuexxbbihaHTHCXVYWxMR899+L3WopH93lz14qGxXOVmK6muzTHpcfhLAFyRzG791B3Xohtpajp0Z1R0Qhhoe07dhDB9oM6snnde/ZgLjJoYHdtnNgRfDQIeZF4D78V18QvzW147bWG3KXxq7c/OWL4DPrjY+P2ZDz35uT8Ahywy/Fmfj/9r5v20w9nOqpm2+144DPn8MjSlFH0b068sKRszpzy4trfcrIbL126m52ztqkpqvGs9fW8pWt69JxGl/96kH5VXFWTnp46bdrSuQvwqJdO49QF8+sO7Cr4ch79iV4ieJ2pZuczDXue2Q3V6898f7sCEsM5EYcTSI+wh2jDIIWRcALZfCb+4Rfnmln4xlZ8cwntWD/T+VbFJqGL8Pq9LmaptHGL2YxH09Nmdt4zQoz/jcd4MJzdUFS4kl7C412pF8CDoVLxSM7h+CIOeLx+yz76rzub6+o236F7jhwRtr71zroVR4430Z/Nuw4/udM8v25FTWO9WUKlL5xe2tC+04X9n70PflPcfFsKBX/oApxrRJYmQfNK6lLKI2WHGzhICh1Ov/5o8a2JhgJswr3K764rCr906ur161frL/TBq967atBX4C4noaA4kpBID509AVve8/Q0fWpPA/MFkEuayuUKA6m0MmKxH6kKozzhi4ewBnei39C7h7gIHQBsCZ1LnzDjdDwQPqMVAegs+hjdSx3M75m+ngZciG6tAsavcLXN7kVHjohDrc56/EJ5OR0lzLZyjJPvUf8zNOiMc9179KRSczCsE4AVdn8sbRhDSrc6l+C3rFYaf7U1zny+RzN99gN9doUHT/VxBFCwIirTrrhLvvjs1198d6fohTwnPjmbft9A36UboUAYaP9lldjxzLP0JJQOz9OnBw/GC0udH2Rk4IN4Oi7ATwwbThsUfqXPgd8H3fyG8XMMv3P3C1a5FvyPCC8cEQKOHHH+64hz1BHGeamzSRBLS+8hs1lYxLlXdSDY+bkKrBQW7oLFF484f+I4ZjNfDHObjTRfgJKVZUMSr26uKnWeUnYWC9lZpePfE+KOnHg9YQnd8V2hvv5v0pDS0sZXfv00wkVPqgGMTp4yYH7zYSGEWwRI3ohf2ep8RVi6nb7hXKTK8JYQD99vO7Vm4S/OJ8xuO+4CzIBWelE8gQEVcQ8ovRfcSFex+VMQ8hJa8eCDW1QIOy3n4RRejj/GV3HFs0yJVLuAajkP9z4Re3ItQr79LzH4nuIHk8EPmgAzxI2JVUY43ORTp/AkEnUSf3zK0rT/JMcpEmNBM9Xit/d2m3n+ZL60kOdquW3d2UNNkJC0waNCBa9ALPz06eeff3r91q3rK+5AkBYYJn2+CnfHJQXGSZ9n4nF4DB6Lx9Fn6Cn6HH2mlEXrQ8BEl4qCQppP9z1Lb1kLCpW8zfS3lcdqZ1YN3yfxRDK/whd/XLZmzbIfeazOffO7796kX74r/PbYtu17lTi9/eq5L5zXVVnoaS5LB7bvSMCzm3lI7V3xMBwGZ1gPEYkvfYtucgtB//lD/1UFD237ZLIqZBf8BNa3sE8/ppahyQkveG+521ZUt0zSNh4r3dm7PdwxBKxLYnAfHOx2cy6aO+MS+yNTul/YTy+8P9w48dLLzlsg5ZOvXdnpfBH71i1cWEd/Ee6ETMmhOjO+tnKc8ySPi3df2XG8x7r6+rXMB3ZA3VMCMveGyiO4o6v4jMX3OYB2w65C7EixdOjo3ieL9h9/dfe/rj16Y35Ruz371m6pOHXg+V2Nv1nvjIYjxeF9a5aXOQYNSzz/5Mvv9+1DzzesWTi3dM7wQSNe2vPJp3GMNuxE4lmQt51yrnBnBma3GUdppVV8spxWHjWLP33DjnDf3PNXYicffO4VWMcsr/hrENtvO7sehHBZCMbCUSHw6KF3Lh86il8/8j29Q7/8h2QuLW1qop9//jnuSiCvNH1Nj9Av8UM4l+E2Q+EsZQFuIKt5lJQSyTFxEl6DV2/FKxdReSeNXIa/lcz3lolzIBTqGSbw5APxWaDkmfBIH3UdSw8+Skuq2Uzfpn/dTPcuxd8dwp1w3EY8CD+wEy9eIr58L4mh3DsuvnhviDiV48FZS+qk5E0fZZv2cUG2JJ54rVBATz9FUwCGVq0XwhqwjHs9hU9vpbvwofXO7x4TFji/g9xpF9aUltJu+BbkoVedj5i5Hpu/A/2/p/LMk4gHPnM5PPUo1c/HP+2GU2L2Nrx8AS07ahY6C18Byn4B+HRGOz9Uc3IOYL3gymdK2R/Gz0e5ROdMs4o9ms4Iv5WPltrfwJNHN/7o3s8a6D+FRE2oWjnyfNaA9/36K52mCTXfPWFuc+4NAc2u5wdfdu7VbOIYawGjv4KB1Z1Q6E+n/fqrJvS3z8yaTDOrPckhXv9o+LmEGYhMxd+spGl0zCr8jXTF+QF7EyD05fRoF7KDWvg5G5RMdjQVUUt2Nqe1XfxeiNcY+FhIJMZjvjXRdzQGWodn8xieAvHUUUwB+/WCGfHuYjCEh7Crvhzmrl3jeUkrfPbR3Oq5JSULnthMLw5ar99z5qML3yytMjjajc/fl3HxXZx5a9Yc66K1+KzzfbN9TOrZvU88l1a91FBwtXfva5xuPNBNAB2EonAWySyKPM+VQBu7zi2Mpw/3YF3/euuxGzeOWev70zNYt2T+gsWLF8xfYpYSzE6LYTp9s7GRXppuOGDGlecuQT67+eYrbB+C2vwkyPeA+5TEagfPNMEqCXIy8o0971L67pSTU2aELJi1bEXd8tLajviRwyewlqJm3D+2H/376tqvvvzy63k1Lr0VufiPculKOYMx9PjwVrU1eaOBnmUC3LxxHATYjZMPLJlfu3hxLQhgPmCYjgc1NuKB0w1Cb/O9t0yvvHnz+qefX1LpkK+A/1AWWeCnCr9ajwOfFxfEi3zlHLhxrWX1Q0t7XXn6fdr41idfv6xZNNe2uJ2AvK9eW1DzzDEQpQkPoFePv/jSyy+w90FQY3QGGR5ieYmjdMH8zYCavLVqSiWdJ+Cxo6aNxX705w+dXzc0NBx8bHj1MKnz2MyZG1aUNj1tNpOc0qUnnuvUWfEpOkQsAp67oWjm4cqrDrWYGYE99cRylYeW4skbt9+ffjR7QlB1xca6FnXhFHrWpS5ytmnKnbs9etwomfH8a+taNHeglK5W9XZOkU2sANm6tNSdymFMlU6hCg/pWxpwSt9N+j1bNtNB6/LL7bPXc5fKLyt/8yPn65CM0IV1L56gDyh7LuAK3aXbUD9ARRvS6h25a8t7dnif3sOHPdxn+Ie7qNwA6NLMmJSUmNikpHu9uKYAojmTDsHLOU7XNjiM1VYqiz894uHeI4b1eXhEb/CbYzsSZw0ar8kYk5PpxqVzSu/N3H8mPPzajMlzF/K4jwf5zSA/WKS3WmUEt8rE2CtYK7Sj/bZAZOFNK/CxffQ2LltJe62gtxZtYSpo8sO7IQt/CiFsxqeW0/dVXKEj4Pp41rJ4zm7adQ8AsUI2gedA0JPmcfCBfvBwf8X/7oFxx6eSM/02F9Zv3kLj1+cbrI71dNj6CYZyuMdzW62bbK0nEyurLr/HzRO1byU3j1mI3rNcaZE800vmirnI5QfAR4sfhP0nfjDBwvwAbPWShxtsgOMDdwM1Lu8Crvr+suV9VUdXXEImEx5eVle3bGndiiXvUud77zudqd9+8cU333zxxbeFEIsUx9F3KKVv83xI88UEwPNHkSyLe+S+ni6eW965cfvNcWXAA61yI7OEMNqkpsBTpQdaUiN9kJ0xWHzm8/jswGmpivjzsGyg3Xb/UTzek0pL8Z0/iETVBsQG9Drxd/JK3v2dzWdOl1KT5y56hWqYIfptL+81WexNf8nKPHvMZVJraSBSz4MkD/BYDRivvU8NGHafGrBkOllQY60cv3L2XOurh9MPTpouzjGXlI6rXlJXdfn5aceG/VY1a9qk1Pz+faOXFG08EP3w18WOnJzkcQ/3jV1j3fpUNPP95tvCVWkmszhEqTZYeY/qkZF7xF/cuPEAHkzfiH0kcZhQ41W7aF0VOVuKx9DnSp3TlqePn75h2ZonmQz9Yf+YLvbkNWG4x17LA1XRenCksBoP3E1f6rtRv6d+Cx7Gk5PYsynZfNZsvXRVGFLq3HJ+/dln8W1emzQ3gq/7AyZoKbx1cYQv0scs+PR2HIAD1uHiUroXuCp1RgnXoKhM4b+fgHhtD2uhtsIPqqsi/Vx14YOYVP788So82Uy/WPn990vpnVJ8fOdHvwIz54UPGAZ5sOkOx+xLRjK8DmDzu4D3IK87PPKOX6vqUOh0aSGumE+bcZ8amjrn3ffm0c0LsDe9PgsftQkrsBYSUBUdBAloJD0P35vxy8o5uRH2GCYr7F3SoNbCckW6kide+wjd2Ur0tOhyw8OT+46XBgzsP/zCcfKOSxH38uZNCWr3VsqwEcB/GvBfB/i/qw/HCBNoj+kk0Pmk4K3PJ0WncWR+0/ZX6RWlPvySniDPQH6EE5EUxg3qfrcfzOqoMO78ymsmZmfy9OCb2/S1gwbV6rfdHDxiQcbkyqqJGQvumDZfx0K9w+Sox8L1TSUT19BbDXVdI5btprfWTAQ6P9AuuFbTxf17rgUbNF1+Y2+xW/PQUSXM3kO7OOnACLdw0gd/+cj8jImzKidlzH9kyM2t+gWDBy/Qb7055M6Etbjb7mURXesacLe1E0o2XaeUc0Tp9c38/Si+LqUSxcZhTOuxGM7QrBjhVWI33KEj/z2hbvLjefm7fXxCl47P3ZU9+bHx8ODbftHE3AbSc/PEvCRR9BmSlrFtwjhoeQ8dw2WAPDXBV5CD70xvN/xn1M2b/+nGO49cPe26N1Y6h/p38r4J53hv5PqBdV4WCr7sP7exsumv/p3Uv/lo+SkQc9i7CYA/DBdYWfgC3SNZ6KTGG60VN6NdmqvIqClBS3AjOilcRhvgWkS2oy4wfgHmJwnlaBzcnxIsEG2bUTVct+CqgWs1XIMZBlxz4dqh3h0w9xW4khiG6yKfohovLaqWtM2/SGOQUVqEiqW/wD0RGUUK93OoWDMKGYWP4fq8uUjSQ/8byOg1HU3RpKPJ0leoWIQxdpdKYexHoPc2aq8JRvmA+aNXEfKRZOQrnmv+XkIoB+RoYDzDfS2nz/4OZzucD8agKeJOFC968/sUMQlNAd0k8fY+kDcDrmHNWeJUFA/teM0HMAb94nh1HcwjM1ESOY6KQZfxMNZfDG6+p+mNuojtUAfWJm+hNNDDl0D/B3Zn9MEEHdTPQDQGLUEf4gg8Gy/DB/Br+HtBIzwg9BJGCunCHGG9cE6gJILkkfXkAPlefFjMFh3iSrFBPCF+LU2VFkkvSj9qHtAkaAo02zTnNR9rGr26ew33KvKq83rN60fvGO8S7wPe571/9PH3GeBT5LPa55DPiz5v+nzq809f5Bvkm+5b63vU96ZfoN9Qv3l+h/z+6tfk38t/uH+t/1H/DwN8A5ICzAEHAv4a8EtgaODwwLLAXYGnA78PbGwX026q6qsFgg/qg7axv7tAQWg7/4ssP+j3Zn/7hB7AI91+OB2dU9uQI3Gm2haQiK3uv0/qgB9X2yK0v1LbEvIXXP6sQaFCltr2RsFCndr2Q12Ej9R2gM+mDg+p7UA0QH5AbQchf9mktoORj7yS/eWUCPUceplTZ22MorGstuH4gI1qm0C/Q22L0H5BbUuoE6ZqW4N6CVFq2xtFCAa17YeGCjvUdkBID+EntR2ISrodU9tBqJOcpbaDUXt5FkpGVlSBqpENmVAxKkEOiPdeqBD2fhnFQXXZD2mhVQAzZKgzTDBuh8uGjEiPLHD+kCGTl8P8GGglojL4yHD2d2HZ+ZMR7kZYMwu+DTDTF6VAywwI+agSZhTCXD2gFPOZMrQZvgwo5fBdAXMKANcE82RYbwW6ej7mi1CytaLaZioucci9CnvLcf36aeWCajnJ5LA7bEa9JVpOKy+MkRPLyuQcNssu5xjtRtssoyHGN8Vo1udXyoUl+vJio13W24yyqVyuqCwoMxXKBqtFbyoHAq05zeVymFARtJnmyoEfI3zbuWRIhcw12kxFcrK13GAstxuhPwmmWhHsq0lWa+n/Eub/Ckg+X2aHhVau6ziwDvv7OpRvtNlN1nI5LkY7oDWtFkp/SIeT+UNWi/gyxTccqh+5GCyyloONHGA5xP3HAdYfimLhY1AxZgFGDKy1wt0GHmHkeDbuOzGAa4Q1qMThqBgaG2sA0FmVMXZrpa3QWGS1FRtjyo0wnOrBgcvXXD7/ex9nY0w8I48DI3iiFVXBXObx/zt+zCLC976UFfvooeXJ8+9j1hf1/R98GPX/F3ng/tpukdmkalHm43ruAxau1VLos3Jn/3NemGTZHM/C0Vo8XcEu4WNGVa5iTqWce6WB4xTxUaObmmJhxduiOV9WzmE5X1+hRpNCwQqoDtXCJu4ViiyFqqZdmA7OReu40MOsQu4hFSq6C4HNVnhXPMkVfMxaER5eEsEtp+cByu52zlchrNGr8ik+WAheaeEoDj7i0k8RtMpUP+7l5rGFAks5jH8HxILi54xii05YTwV8W4FKJeezhRsDl8DBfa0ARh181EXjjylEq7FUCJxVchRFJ1XcB0p4TnComrHwPk+JXPi2Vl6pcFvJdRjtYR3WtnB7umzdEr92WB39B3JEu+WM5XlJ5shKPCjYJlWrra3/51K7NKdwW+H2aEcbr2uRqIrrw/IfUXBFQxHPqeWqhEYPigb+zWhE8zvThBlmFHI8ZY6nH5epWdJloUJ1qzC57WGHvM6iM09dpQdEK88MLTbwzEUtGvh9JiiH+Q41Guyt5rpipUVjnjnAc53MZdarlipw522XrynaUDK5/k/saeV7kKza3sLvLfnjP7GFAySv4PuaXpUoppWm/mwt00m1m38Ljz4Tj2VXRmO8O9Ssp/QonDKdGjxs7ul1rv2LUVH0VQkoer7OJZGBc8rsVe6hjWKYx6QpUftsHjlUz71H8V0Xjbb6sf9bmTxznKGVh+m5je7HwZ9z0ppeW73cj8do1e5lfJ3pT7K6Tc1ARs6fpRWuq8fu9kxX3LTdRYxqvjO2skAVl8rA10fcZ1+McMvddgWb79p1Izy8TYmd9Db7TAGPe6sHr5VqPLgsMQtGTffRmBHN5nouVyO6Aj7KLqbnmdXoXuFpf4XnP4+YEp7pZX63qzwauUf9sb8o0t0vh7PRSrW09dTX/bQqe2jO04b/3Zi18+zp2rNbos4VUayCKHPXIDZ1RWvECu7RpfBdrFpM2RfLuW7b1h//NzLWH0tVoMaIQ90Xi9yaGo10nE4WyoQnRicLnvLQBKgnc/hYGvTJUM/lwEg+PLF/55PC7ZLIR9h4BI/GCdBmiFloPMdSMHLgm2FPgh6GLfNn9jQW5mcCFlurQxM5DR2g5fKZORw7A3rT4a5T57EVydAzHp5ZexRi1ahCj/1rozweO2wd40XhNA/6W6i25iqNU3RxlgFPOYA/Wh1l/7IpjeMx/qO5plg7081nqsppItcRQ2aYycBROn9ivePhng3zcrk+E7nMCreZXIZUGFdk0XEOFEsoHCXzf0E1ic9g/7Yqj3PBKOWpM6O5hEyeFL6eUR3LexXOslQrs3YLSoyqS4UPpv98N+VcLn86fGQufx7/11vMNomA78J1+c4ojpDh9qPxXL5ErocsTiGJjzEtMn2mu2fmeFglmeuL2Y1xnsIpJXKN5N5XEhdaa+vczztcFEZx+XRcU+l8di7oUQfz09w9ij+mcVmTVd0qmIrfKz6R7qHdZC4js+w4oKpTfSqR6661FEqEMP5bpFAskKh+J3vorMX6map1k922zuJe9nutTOCxqOOzErmtc91aSOXxm6FyPt7Dw1x2HK/6Z5abs9b6dcWRa95/kjsULBft1hZM4f6UrnKY69bGv8dVcpcO9rVCft5xuPN2653bs3psqUo9689oj1zrWQkoWXgUn2tpM6+lV8nPyp7VcubxrOHut3O5TslKTd9S/bqqDyV3V6ovd1qqXwOv05Va0O6uSpT9w+quTKr4aMuerpwGLXyG53nPzukqklWqK9piKfWlnlcLjJr9Ptr8sx2q7Qmxgu/3CpUq3naolQmTr1Kdy/rntDkV29qcqv6dDVyy/Dv927i9K9QzlYlrmNWTMSquDbnOZy06YRpQ3n5Z2li9xfsY2lDUtg5lOij24NygWlx5k8Zo+iKUyl/Gsfei7N2q+52q3MtuNMoFxjJrVe8Y+T94ixrj69uyON9o08sKsvvdrW/fP/3x9f3vv+WV21A2AYuyw6Y3GC16W6lsLWqL4uubbbRZTHb+9hNmlxhtRqBVbNOXO4yGaLnIBsLDMhDYVmyMlh1WWV9eLVcYbXZYYC1wgMCm8mKgUghMs5mOEqP6XlNfWGi1VMB0NsFRAuigJPaOVO4VwVUS0RvADLLebrcWmvRADzRYWGkxljv0DsZPkakMdNyLIfIFcq61yFEFOo/ozTmxGStsVkNloZHDGEwgmKmg0mHkPLRaEA1WKiyrNDBOqkyOEmulA5ixmFRCbL5NUSXAVtphPhMnWrYYudTcvvaSaA8a0YxmrNUm241gB5htAlZV8duQZswBbAVTtENVHSdUVWK1/H4BM0NRpa0cCBr5QoNVtlujZXtlgdlY6GA9io7LwCWZQIXWcoOJyWEf6uubB0P6AussI5dA8SLOgNsJyq0OMINd6WVWqWjxAGVMtpfoQagCo6o1YAOcXN9KTms5+IVNtlhtxvuKLTuqK4xFeiAUozDVetSir2b4FqvBVGRijqYvc4DrQQNA9QYDl1xRHYsvvQ34qizT2zghg9FuKi7nbBSXVVeU2Nki5qH6QgCxsxUufuxtKSkeZ1AUpi/zAGgDoq5z8dKCCCyWl1XLplauDiLZjOz/Z8DnsoadKZPZxhUiRvA7oyJAldVmsMsR7liMYLRdA3IEC90IrjawTroaMwVGiCaGWgl2YELMsprcjBlnOyBqZH1FBYSYvqDMyAYU+QG5jWFK9A65RG8HRGN5a70AuRYPN8iV5QaV4YjWeSVCkfDPLGu3lrHI5qZjhtLLZSyDQLy4JlboC0v1xSAYxGK51Z0//nPHakUKkhawaCwrYkyN1smpWZl5cm5Wat6ExBydnJYrZ+dk5ael6FLkiMRceI6Iliek5Y3OGp8nw4ycxMy8SXJWqpyYOUkem5aZEi3rJmbn6HJz5awcOS0jOz1NB31pmcnp41PSMkfJSbAuMytPTk/LSMsD0LwsvlSFStPlMrAMXU7yaHhMTEpLT8ubFC2npuVlMsxUAE2UsxNz8tKSx6cn5sjZ43Oys3J1gJECsJlpmak5QEWXoQMhACg5K3tSTtqo0XnRsCgPOqPlvJzEFF1GYs7YaMZhFoicI/MpMcAlYMi6fLY4d3RierqclJaXm5ejS8xgc5l2RmVmZTAdjc9MScxLy8qUk3QgSmJSuk7hDURJTk9My4iWUxIzEkfpcluIsGmqOC3qYAtG6TJ1OYnp0XJuti45jTVAj2k5uuQ8PhN0D5pI5+wmZ2Xm6saNhw6Y5yIBBhmt4yRAgET4L5lzxsXPBHEZTl5WTp6blQlpubpoOTEnLZexkJqTBewye8IKJuN40CczXqbKL7MR6/u9d8AstloVMEWXmA6AuYyN380F79LNLjRWOJhvq8GtpEeeSpX8Gc29VkkC4MKjyiFwlT7eBH+GyOI7j5LhWoKLbcnRavpl6QO8G3YjJf0aZhkhC9pZKoH4sLJkUmWy80iHbdBiVfc9u74MiMEq9yzIl/oyWGZ3s9k6oFwbYoXNBEuqbCYHJBNZXwm9NtMcdSu2qVtVWwkYlbb824z2CtipTLOMZdUxMNfG9jPOiam8yGqzqKJz9RU6hrpyqEMu5uAGENxqK46Rff8nvxWN5VVwKVyxvHI08PdxMfzdaAX0tX7P9+e/Q42tMpWaYk2QDmfHVJRUxKo5+Y9/K93qN9Dofr869vh9sfv/PtM8n/2/b37/c1qoTbh5kZLXQ8hfXusu/cVA/pIgvtadXAgg51/tKZ03kFd7knPTyCs15KwfOeNHXnwhVHoxjrwQSk7HkecpeY6SU5Q8S8kJSo4fGyUdbyTHRpFnKHm6hjxFydFAcuSwv3QklBz2J0/GkUMGcrAr2R9H9j1ukPZR8riBPFYfKD0WRfbO9pX2RpE9Y0lDENkdQ3bVdZV2UbJzR5C0swvZEUS2bwuUtkeRbTBvWyDZliBuhYVbQ8nWWrE+kNQniFuiyOYl/aTNlGzaGCJtiiIbNwRIG0PIxtM4IcFH3LDeV9oQQDacxighTVzvS9afE9dZa6R1Z8jahX7S2mCyNkFcA601Q8nqVWek1ZSsWjlNWnWGrKoVV66IklZOIysTxBXA14ooUrc8WKrrSupON59LaBaXB5OlQHqpgSzpRxZ3IIvqyUI/UmswSLWULCgLkhZ0JvNrAqX5caQmkMyb206aF0LmtiNz6kl1MJntS6pmyVJVI5lV+ZA0SyaVDxEHLHJ0JXZKbJTMrAiQZlJSEUAqEkRrDSm3jJTKS4llJCkr9ZfKgkhZrVjqT0oTRDOQNDcSU8kZyURJSfE0qeQMKakVi4uipOJppDhBLIoiRphkbCQGAykMIwWU6CmZMT1GmkHJ9BjyKCXTKJk6lkypIZMpmZRCJlIygZL8M2Q8JbkGkhNKxsWR7Kx2UnYNyWpHMhMTkki6HxljIGkR3lJaPRkdR0aRIGlUCEltT3SCr6TrTFKSQ6SUUpKcFCQlh5CkRD8pKYgkJvhIiX4kwYckMD3mio/Uk5FiX2lkBhkxPFQaMZYMH+YrDQ8lwxPEYb5k6JD20tBpZMjgYGlIezI4mAwKIAMpiR8QKsVTMkAbIg0IJdo4X0kbQuL6+0hxviROsU9/H9IvtpPUL4XExoRJsZ1I7DkxpquvFBNGYmrFvj4GqW89ie4TKkWPJX1AiD6hpE+C+DCw/rCB9O7VT+qdSHoBY736kZ5w60lJj6EkKqCTFDWNdI9sL3XPJZGwLLI9iUwQI7xJuNxJCp9G5G7BktyJyOfEbkCsWzDpVit29SVdE8QukeShduTB7uSBzv2kB3JJZ0Dt3I90oqQjEO1ISYcgEhYaKoWVktCQECk0lIQmiCEhpD3Ma3+GBIN6gykJgltQEmkH/LerJ4EwFkhJAAAEdCIBCaI/JX7w4JcwuJT4whzfGuJjIN5ewZJ3KPEKJhopTtLUEAnWSXFEBDCxLwFQwZfgXIIowaexYeka3Of/2x/0/5qBP/3pgtD/Af8X4cMKZW5kc3RyZWFtCmVuZG9iagoxMyAwIG9iago8PC9UeXBlIC9Gb250Ci9TdWJ0eXBlIC9UeXBlMAovQmFzZUZvbnQgL01QREZBQStEZWphVnVTYW5zQ29uZGVuc2VkCi9FbmNvZGluZyAvSWRlbnRpdHktSAovRGVzY2VuZGFudEZvbnRzIFsxNCAwIFJdCi9Ub1VuaWNvZGUgMTUgMCBSCj4+CmVuZG9iagoxNCAwIG9iago8PC9UeXBlIC9Gb250Ci9TdWJ0eXBlIC9DSURGb250VHlwZTIKL0Jhc2VGb250IC9NUERGQUErRGVqYVZ1U2Fuc0NvbmRlbnNlZAovQ0lEU3lzdGVtSW5mbyAxNiAwIFIKL0ZvbnREZXNjcmlwdG9yIDE3IDAgUgovRFcgNTQwCi9XIFsgMzIgWyAyODYgMzYwIDQxNCA3NTQgNTcyIDg1NSA3MDIgMjQ3IDM1MSAzNTEgNDUwIDc1NCAyODYgMzI1IDI4NiAzMDMgXQogNDggNTcgNTcyIDU4IDU5IDMwMyA2MCA2MiA3NTQgNjMgWyA0NzggOTAwIDYxNSA2MTcgNjI4IDY5MyA1NjggNTE4IDY5NyA2NzcgMjY1IDI2NSA1OTAgNTAxIDc3NiA2NzMgNzA4IDU0MiA3MDggNjI1IDU3MSA1NDkgNjU5IDYxNSA4OTAgNjE2IDU0OSA2MTYgMzUxIDMwMyAzNTEgNzU0IDQ1MCA0NTAgNTUxIDU3MSA0OTUgNTcxIDU1NCAzMTYgNTcxIDU3MCAyNTAgMjUwIDUyMSAyNTAgODc2IDU3MCA1NTAgNTcxIDU3MSAzNzAgNDY5IDM1MyA1NzAgNTMyIDczNiA1MzIgNTMyIDQ3MiA1NzIgMzAzIDU3MiA3NTQgXQogMTYwIFsgMjg2IDM2MCBdCiAxNjIgMTY1IDU3MiAxNjYgWyAzMDMgNDUwIDQ1MCA5MDAgNDI0IDU1MCA3NTQgMzI1IDkwMCA0NTAgNDUwIDc1NCAzNjAgMzYwIDQ1MCA1NzIgNTcyIDI4NiA0NTAgMzYwIDQyNCA1NTAgXQogMTg4IDE5MCA4NzIgMTkxIDE5MSA0NzggMTkyIDE5NyA2MTUgMTk4IFsgODc2IDYyOCBdCiAyMDAgMjAzIDU2OCAyMDQgMjA3IDI2NSAyMDggWyA2OTcgNjczIF0KIDIxMCAyMTQgNzA4IDIxNSBbIDc1NCA3MDggXQogMjE3IDIyMCA2NTkgMjIxIFsgNTQ5IDU0NCA1NjcgXQogMjI0IDIyOSA1NTEgMjMwIFsgODgzIDQ5NSBdCiAyMzIgMjM1IDU1NCAyMzYgMjM5IDI1MCAyNDAgWyA1NTAgNTcwIDU1MCA1NTAgNTUwIDU1MCA1NTAgXQogMjQ3IFsgNzU0IDU1MCBdCiAyNDkgMjUyIDU3MCAyNTMgWyA1MzIgNTcxIDUzMiBdCiA4MjExIDgyMTEgNDUwIDY0MjU3IDY0MjU3IDU2NyBdCi9DSURUb0dJRE1hcCAxOCAwIFIKPj4KZW5kb2JqCjE1IDAgb2JqCjw8L0xlbmd0aCAzNDY+PgpzdHJlYW0KL0NJREluaXQgL1Byb2NTZXQgZmluZHJlc291cmNlIGJlZ2luCjEyIGRpY3QgYmVnaW4KYmVnaW5jbWFwCi9DSURTeXN0ZW1JbmZvCjw8L1JlZ2lzdHJ5IChBZG9iZSkKL09yZGVyaW5nIChVQ1MpCi9TdXBwbGVtZW50IDAKPj4gZGVmCi9DTWFwTmFtZSAvQWRvYmUtSWRlbnRpdHktVUNTIGRlZgovQ01hcFR5cGUgMiBkZWYKMSBiZWdpbmNvZGVzcGFjZXJhbmdlCjwwMDAwPiA8RkZGRj4KZW5kY29kZXNwYWNlcmFuZ2UKMSBiZWdpbmJmcmFuZ2UKPDAwMDA+IDxGRkZGPiA8MDAwMD4KZW5kYmZyYW5nZQplbmRjbWFwCkNNYXBOYW1lIGN1cnJlbnRkaWN0IC9DTWFwIGRlZmluZXJlc291cmNlIHBvcAplbmQKZW5kCgplbmRzdHJlYW0KZW5kb2JqCjE2IDAgb2JqCjw8L1JlZ2lzdHJ5IChBZG9iZSkKL09yZGVyaW5nIChVQ1MpCi9TdXBwbGVtZW50IDAKPj4KZW5kb2JqCjE3IDAgb2JqCjw8L1R5cGUgL0ZvbnREZXNjcmlwdG9yCi9Gb250TmFtZSAvTVBERkFBK0RlamFWdVNhbnNDb25kZW5zZWQKIC9DYXBIZWlnaHQgNzI5CiAvWEhlaWdodCA1NDcKIC9Gb250QkJveCBbLTkxOCAtNDYzIDE2MTQgMTIzMl0KIC9GbGFncyA0CiAvQXNjZW50IDkyOAogL0Rlc2NlbnQgLTIzNgogL0xlYWRpbmcgMAogL0l0YWxpY0FuZ2xlIDAKIC9TdGVtViA4NwogL01pc3NpbmdXaWR0aCA1NDAKIC9TdHlsZSA8PCAvUGFub3NlIDwgMCAwIDIgYiA2IDYgMyA4IDQgMiAyIDQ+ID4+Ci9Gb250RmlsZTIgMTkgMCBSCj4+CmVuZG9iagoxOCAwIG9iago8PC9MZW5ndGggMzMxCi9GaWx0ZXIgL0ZsYXRlRGVjb2RlCj4+CnN0cmVhbQp4nO3Px04QABRFwZPYxYaCvWMXsHexYUEE7L3X//8E98YdAUyc2d3kLc6reVrW8la0slWtbk1rG2hd69vQxjY12Oa2NNRwW9vW9na0s13tbk9729f+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NjA3IDY0NCA1MzMgNjQ0IDYxMCAzOTEgNjQ0IDY0MSAzMDggMzA4IDU5OCAzMDggOTM4IDY0MSA2MTggNjQ0IDY0NCA0NDQgNTM2IDQzMCA2NDEgNTg2IDgzMSA1ODAgNTg2IDUyMyA2NDEgMzI5IDY0MSA3NTQgXQogMTYwIFsgMzEzIDQxMCA2MjYgNjI2IDU3MiA2MjYgMzI5IDQ1MCA0NTAgOTAwIDUwNyA1ODEgNzU0IDM3NCA5MDAgNDUwIDQ1MCA3NTQgMzk0IDM5NCA0NTAgNjYyIDU3MiAzNDIgNDUwIDM5NCA1MDcgNTgxIF0KIDE4OCAxOTAgOTMyIDE5MSAxOTEgNTIyIDE5MiAxOTcgNjk2IDE5OCBbIDk3NiA2NjAgXQogMjAwIDIwMyA2MTUgMjA0IDIwNyAzMzQgMjA4IFsgNzU0IDc1MyBdCiAyMTAgMjE0IDc2NSAyMTUgWyA3NTQgNzY1IF0KIDIxNyAyMjAgNzMwIDIyMSBbIDY1MSA2NjQgNjQ3IDYwNyA2MDcgXQogXQovQ0lEVG9HSURNYXAgMjUgMCBSCj4+CmVuZG9iagoyMiAwIG9iago8PC9MZW5ndGggMzQ2Pj4Kc3RyZWFtCi9DSURJbml0IC9Qcm9jU2V0IGZpbmRyZXNvdXJjZSBiZWdpbgoxMiBkaWN0IGJlZ2luCmJlZ2luY21hcAovQ0lEU3lzdGVtSW5mbwo8PC9SZWdpc3RyeSAoQWRvYmUpCi9PcmRlcmluZyAoVUNTKQovU3VwcGxlbWVudCAwCj4+IGRlZgovQ01hcE5hbWUgL0Fkb2JlLUlkZW50aXR5LVVDUyBkZWYKL0NNYXBUeXBlIDIgZGVmCjEgYmVnaW5jb2Rlc3BhY2VyYW5nZQo8MDAwMD4gPEZGRkY+CmVuZGNvZGVzcGFjZXJhbmdlCjEgYmVnaW5iZnJhbmdlCjwwMDAwPiA8RkZGRj4gPDAwMDA+CmVuZGJmcmFuZ2UKZW5kY21hcApDTWFwTmFtZSBjdXJyZW50ZGljdCAvQ01hcCBkZWZpbmVyZXNvdXJjZSBwb3AKZW5kCmVuZAoKZW5kc3RyZWFtCmVuZG9iagoyMyAwIG9iago8PC9SZWdpc3RyeSAoQWRvYmUpCi9PcmRlcmluZyAoVUNTKQovU3VwcGxlbWVudCAwCj4+CmVu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NDggNjI5IDU3MiAzODcgNjI5IDY1NCAzNDIgMzI1IDYyNCAzNDIgOTUyIDY1NCA2MDAgNjI5IDYyOSA0NzQgNTA2IDQxNiA2NTQgNTIzIDc3NCA1MzYgNTIzIDUxMSA1NzkgMzI3IDU3OSA3NTQgXQogXQovQ0lEVG9HSURNYXAgMzIgMCBSCj4+CmVuZG9iagoyOSAwIG9iago8PC9MZW5ndGggMzQ2Pj4Kc3RyZWFtCi9DSURJbml0IC9Qcm9jU2V0IGZpbmRyZXNvdXJjZSBiZWdpbgoxMiBkaWN0IGJlZ2luCmJlZ2luY21hcAovQ0lEU3lzdGVtSW5mbwo8PC9SZWdpc3RyeSAoQWRvYmUpCi9PcmRlcmluZyAoVUNTKQovU3VwcGxlbWVudCAwCj4+IGRlZgovQ01hcE5hbWUgL0Fkb2JlLUlkZW50aXR5LVVDUyBkZWYKL0NNYXBUeXBlIDIgZGVmCjEgYmVnaW5jb2Rlc3BhY2VyYW5nZQo8MDAwMD4gPEZGRkY+CmVuZGNvZGVzcGFjZXJhbmdlCjEgYmVnaW5iZnJhbmdlCjwwMDAwPiA8RkZGRj4gPDAwMDA+CmVuZGJmcmFuZ2UKZW5kY21hcApDTWFwTmFtZSBjdXJyZW50ZGljdCAvQ01hcCBkZWZpbmVyZXNvdXJjZSBwb3AKZW5kCmVuZAoKZW5kc3RyZWFtCmVuZG9iagozMCAwIG9iago8PC9SZWdpc3RyeSAoQWRvYmUpCi9PcmRlcmluZyAoVUNTKQovU3VwcGxlbWVudCAwCj4+CmVuZG9iagozMSAwIG9iago8PC9UeXBlIC9Gb250RGVzY3JpcHRvcgovRm9udE5hbWUgL01QREZBQStEZWphVnVTZXJpZkNvbmRlbnNlZC1Cb2xkCiAvQ2FwSGVpZ2h0IDcyOQogL1hIZWlnaHQgNTE5CiAvRm9udEJCb3ggWy03NTIgLTM4OSAxNjE3IDExNDVdCiAvRmxhZ3MgMjYyMTQ4CiAvQXNjZW50IDkzOQogL0Rlc2NlbnQgLTIzNgogL0xlYWRpbmcgMAogL0l0YWxpY0FuZ2xlIDAKIC9TdGVtViAxNjUKIC9NaXNzaW5nV2lkdGggNTQwCiAvU3R5bGUgPDwgL1Bhbm9zZSA8IDAgMCAyIDYgOCA2IDUgNiA1IDIgMiA0PiA+PgovRm9udEZpbGUyIDMzIDAgUgo+PgplbmRvYmoKMzIgMCBvYmoKPDwvTGVuZ3RoIDMwNAovRmlsdGVyIC9GbGF0ZURlY29kZQo+PgpzdHJlYW0KeJztz+dWCAAAgNHvnOxRZmQle2RUKoRsLZTIiOj9X6KH6J9z7xvc2qWB9rS3fe3vQAc71OGOdLTBhjrW8U50slOdbrgznW2kc53vQhe71GiXG+tKV7vW9W50s1vd7k53G+9e93vQwyaabKpHTTfTbI970tPmetbzXjTfy171uje97V3v+9BCiy213Eof+9TnVlvrS+t97Vsbfe9HP/vVZr/701Z/+9f2bvMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADwf9kBd7wSjwplbmRzdHJlYW0KZW5kb2JqCjMzIDAgb2JqCjw8L0xlbmd0aCAxMTM2MQovRmlsdGVyIC9GbGF0ZURlY29kZQovTGVuZ3RoMSAxOTk2NAo+PgpzdHJlYW0KeJzVfAl8FMXWb1VX92QPWUiCQKCTEEIgBE0IERDINlnIShICyJZJZiYJJJlhZrIMIRAQBEQ2BVQ2UZE1KCoiih+CiHCvKCpycd9wu24ocnEhU3mnqnsmk4jc+773vt/7vbQ9Xd1V9T/7qVM9gwgjhPxRGyKovLBkRILx3QAfePI9nGWVdTqzV733WoRwBtx3VDba5Lu/2wP3Qgo822k0V9V9veLnVxAiX0D/qiqd1Yw84ECiGe59q2rtxln5m5+E+wUIDRSrDTq9sG3SIoSi2qB/VDU88Dd7GuH+CNwPqq6zNZ8bOPABuP8Q8FfUmip11n/VViMUrYf+V+t0zWbxU+FXhAbHwb1cr6szrPT45Cu4z0Mo0t9ssto6F6M7EcqewfrNFoP5vrL1Y+Ae6EuLEBaDhReRCO1E6SGgMEC5kovIKASBVBpPjaSRBEH8Amk6i9DP171FJAMSKjJq9dCSOzs1vWlvvNnjLnypHKGHkfOPqNf+ars/fGJ+FdFdcBXhYPcLOzsZZ52dnZf4PRstIglpQGueyAt5Ix/ki/zAIr1QAApEQSgY9UYhKBSFoT7oFtQX9QPMcDQADQR+IlAkikKDUDQajGLQEBSLhqJhKA4NR/FoBLoV3YYSUCIaiZLQKJSMbkej0Rg0Ft2BxqHxaAJKQakoDaWjDKRFmSgLZaMcNBHlojyUjwpQISpCk1AxKkGlaDIqQ1PQVDQNdDsdzUAz0Sw0G5UjHfAvIAG3sE84ytE1HIDmIDt/XgY9M3AF3C2Hz7WoHLfgB2FsASCMQ8vQFXi+mM8fiBfD0c71cQyeHFNa8LwdUBBwMA8tRg9yGqfFGaJRbBGNuAKvwqvgyUesTxwORyyMXQxnC/RVsDaeAEcsMoKMsehReHIN+u1oM/aWXgfkUzgaVQOdAjh3Yg32BF4uYH/BiH1hrFVkXIQz+aQLIFUDssO8C/z4Ce71yC5d0PQWroFc2dCnEcvRl4A+D83D3jiRJAq/wdwCeLKSSYX3IYHMAVcohyOGHxmoAnB2wQg72ipUCIliDBvFeR+HPkCHOd9GtB7ui9E+/olIBzpPkrAR+Ge6uUU6gco8JuIIjT8e4bEIdIU04WixYEQDSQayInCfwxpJJAJGcXLAQSE6R38wZdJU+cy0iOFxPW7lAA/5ICo66GeXj3R2Fk0V+0nTDkr9D5Joz4NidNRnf9X52fC43KKp8sG3tBkqqrY8A56VTIUmu4PH8FybMRxxzwA9QxwQaE2nm8g1aSe0IWsEB0YERkcERswgD3W8IZx1jKSbPPx/u2LRxPJZ33ZeElLAEj4QFyh55KjEhNCQ3pqoyMHBESQkKulbfWamwZCZqRfwpLmtjxaYzQWFZrO0pOPwsWN8/pNkHwmE+ZwWDowCalGBeNkFQQcWdVwQhrETxrVCfrhXOgbjBsK4KJLogxODwZwhESQCzqjgKHYmRfCTbP5xwo8xZT9MOT3ld3pxPEb0rbLTcHum7DqOmUDxrWWkmv4I5yK8mC66QH+8SBfjRey8iIMugP8T9Bi9IM7RhEEcD4N4RcmBiQPwWBwYj5NGjoKbwAE4LDAqHsdAA+T1AKbjMfaHdmhY8HgYNDjmYG2v1Ut8P335w1frDEem1vVaab5y6ovzdZWv4fi8aXnT6qbPmqobi6PuXoo3Jh977NA5Dfajv2iGxdCfbIsFeu/Y04eef11Dr4Dnx4krfUrHZZb1d/j7TJk4sXwg5Ka4zkua/eDxPpCD+kGeSQStRGqAfGLCKEbdaQZgNyIRR2G3+zC3cXjNmISEMWMSE8e0Hz7cvv+558inmzuObCHn2p99lt3uH5uQyLqlVat27b733t27VhneOnXq7bdPnXrr91/I8LdeOXX+/KlX3tI7e7lNc4C32cBbPGQ3FItj/HGUjJjeuPbAQ7jqohgbYaFheDxOBqOFRPljj7DEaEWJwJ1mNp1kvfO9xfQKPbd1V3z8b5+mPW2yBVTrplc/EuSFt48a5/kYfvnhXsXpLzzkuIeS2xKPb8hceldREUafPvZ1Sfr80TsP9wmjZxZYyotnHO7tRQdkPD2vufm+z6dcrBXy0h8o2fv+bczzMfNBvIz7IPdA8D7F9VjfAXqCeGp6Q/ZHeBAKDEDRIRohMCAojHjSN+k/cAxOXLNw4Rp6ohW/je+E4+zS5nK6nO6me+jycgV/Jj0hPK9gBCeNCgoMEGKSRIY1a01r6xqcgGPpO/REczm246l4GraXNy+lI+kuOOL5/CHCaLIV9AkRhv2wR3RwVLAUMxwnSySRRJMt9EWcMZW+12dnH/reNKylR6fhGLjBMeKLe/ZtuUxb8LLLW/bt2X4Fr6DNVwDvJBKkTEkE3wF5IXpY1CVFBJJa/B6NOUcH4/cl0XHlK8cVwf8rwV+RoQ99Ap/DE2A1RGHj8TgcEdJb8OgzayrGuw+cKn3znjLPj+iJOmVsJd4ubBbamT6DIXPohSTHa0I7vcL6NnZewj/gckbbPVtsNGRm6/XZmQZnimBjUWea8KhqF+bD752hg09LF36vY+tY5yXxgOr/KDphVGDAYHCowIDQMJlpNoJ/Cktqi4pq2Ul/oZ/ByuWHffEA+vnU9/Fz+NDH79NsmvN+A16Lm+BYR810JRzzuAyfwZqQC/jeCCUmJQZKSZADE0McHfQbPAn3d9jFI0c2fv7HSjsb2wJjZ8LYfiBvVOB4nCiLLCfgCJgYkZQ8KAgCDZj7pbb+Yj1GeOD2T+hKobWlY8Bp1Ll3ccucNlGP06Kj//nmhu8eoZcP0olXHzqJg5478OxaRZ9tIOtqwB8K+ICewNFZ3hEjIgfHsMykBPMwnKQ0gJhH1PamT+b+gsVN6w4eoP+gX875vKXO+94FKzduWXvHiKXLWhvmLPCrk/ZFRZ04tHxP+MCzT731yZAhOHvNxr1bHj3YvGL5ouWLWQWRC7I9B7T9mffFYi4RZqqICkzEgVh80NH/jONNIQEXf/31GccX1GzHAVZytaPvYvoTXi9ccAxTZHgAZGgCWw7kPseUlIBCeqPuAnC+A8UAx6133DfpNyzTT6mj/r3GWs2iBUvbWhdp58bfKV2gf9AjiSPpP3+6Sn8aMhRn3r/u1N9Pn5qQJlxiuccGdHYAv7dA/YUwIxMEZKIUp+jKPMFOepBr8Ktj1k8+9x399Sr9gX4BWX7UpOfn1IW2Vu5Yd2ZmYeHMGYWFpGHkSHr121/pL3geNuIH8MMDBtDfjDVXr7/15LvvPslOkBEqAnEOyOiJkBe4K6S0wAhxjuPCc0J/R00LeVe8eD1WfOHv+Evw6Nkqn2FQK45gVS83pzzImSYHKWYmTiZBQcFubTJtx276Df0Njq/3PLx6NUZXf8Fo9bmMktL09NLS9KhSY3VxSZWxlPQLP/v4Gx9++MbjZ8MH7V1y7MyZY0v24pjtTU3btzU0AG/33PPc86tX87xdCTyt5TEV3X01CYYMHikkjUSJih67jCbYQDszQEu1Y9dPfv077HEVB+P+9BR9bdILc+rCWvXb10ktThV1fJmUhH2++w370jV0C62hugEDsKex5l9Aey/khU7yvRLPwW6ZIdCtvVefmaXXZ2VVKtdMPRleaJ5XUFBf7zjpyhsCGkEPq1i9u2MRnrkgcbmA7DyDtbtwOgwl5+6Z+hl9sY7nyTQpUzwJ+wDkJYTgJCxpO9aSedcXikvIefoEPXgZv/k5Psd1dxIPlzJhz6LUTCypwnlSXMIGX18oBFwS9l1C3TGDkzAD5YPIPHL+J3rb5/TWy7iI4TV1XiJfcVtEQn7rLoPS9ghhucYZNXikITPLYMjKNLxo+vusS79892Hrwob6uZVzPetcVde0yMiPXv/7P4Nex8OGHdm87v6lS5TKz063ap6SdsAepwBYTHJzsxBm+MHqog2xr1Q2PII8NEJI76Aw7gXJYWzcoJjB4CNByaOY74aFBoUEoGFYtJunT6urmzbdPGRxUfupU+1Fi4ecWnxvpbH404UNr01edM+UIt31++pfn5lo/X3JE/RDi6Wx0WLFg5/fgT2qmpvopc7++Le8xdue3LDh4Ja7s/N+On/+cl5Wq6NvzLcH135bXlCaqZ1Dj5zaQn8wNM3PSJ9VWrpw4QKce/w4nti6cIGuwkS/eIJ+T8+CnJ0/Qw25H+KzF+zgUFRSIiwqUD8mJZIIqCPhw78O/3ERX29zrLrLRD3n7SB4xzm7NOOPnXY7vo2eswtRwli2N4QKX3oKcIIg4w9S1gcoYBSjKHFMnAk50Nkoa28n4deu7dv993fpeXrsi8YtG9c/3Lh4/67d+xbbxUv2I88v29o75NWdH79NjK2LlzReD3/w4R3bldyZ33lJmgW+0BduNKIzl4lqLmPVCLOBNDOJ/vAN3U8bYAs2uQmL6yxRzz946tixV5fvG4z3ff0pfgQbIHk9kpJK1794lL5En4Hj+K496r4QST/z3BUC/gZZE6qUYRgroikUhZmtb79y8nyr4/f2duFpPAcq5jb6gB3H4SysxcNATSAHe0Q30kWMb4Y5EDDDXDriZ4TaZldQi3hkgeM1XN7SQncImS0cY8dFGnHM0XHMsfMi3aHogGFJgBVyY6zEEIb0RIvjH3gyIO0/3x1ng92px42gxwFw4645jtBNseLVIf/V/tbJo6/mLkl6FH/xAD2/Haoxptg71+Gw1SJ69SnIcQfoQXpy8GC8uRnSTx7ejqtxDd7O1OviWcN2qf1cPIfw/Q2/cs8LVDkXDh0QQvcLzxw44Mjf7/j2AOPeTr3xNbv9erjdLugdW+0uTOLN91tumE4MZR7M4GM77bSMRMJYyIIkSV1k1Rk8lSxvkDIzpmS+Ipxt//Dn3Afo5u9mTd34ujTMbv/j9V8+jewmQx93ephfvFj04C4BRq/D156h4cK6ZfR2xz6FF8cnQgRIktjxrR1qgZ12J6b4DWD6ddOL4g0MKHC+Isfw7+kqbjeEPB7txoMX7lIhrLTQyt+/H/wV9nPC0PZ2UCKd/QXV7Wc4HWnkGNeiaL1+WQy8vpr7wiSQKxcwg12YWGWEw01qb8c7ybD9+B/t33f8jeNc/4TsAc3sFJM70pR3Kcyffu2qQVnIKE7lXoOSyPbdu9v379mzH/xlIb4LF+MS2HUunIHTcC7Ow2n0KH2OHqFH7XgrxNQcvI3qIYA2Ub0rLscBnwFQ0bgyDVQGIsstIcQtvQiOz7An/fXTa++9Z2hYdU+jgTH9w+kzl2kvu3DpwPp1+znPdC3nuTd7hyYN6uJTYvvbELbAuAlC+tN2uszF9Lv08oiVdfF993+ZoQoVrsEPgts7uX6Z2kaMTri2E8f2lE6VRePB42EQr3jCgtn2PR5zUSK6ZNNEO8UibxfVx75yD828OH5KfvIBRx6I2fkt6nzW8U7jwhUrFjYK7/nNmELL7fjkvfmOj+xM5LeOb3024sHVqzdxX7NDTXMK5I1V9/AK8gjsrLG6tqKhA9UiLHLQGku/l9tX3Ve55W+ndmCvy42oc6Wl38HHlq2YdfjvR/dA3XX5LtqRjSv27Gq03Kkflnz76SMf/xoXRw9vXltlnKkbmTzywxOff5fA6EPsSTl8zUE83p0ZACTGBU/TdS3SyPl09SG7eP573EBXfX99uBIjE2APzXLnLWxPwf0ygC3It2D1hlvtW0gZ7duOPb91P36nnV6DlHSGXpVmQNiF0H9+8hkOEb6DNqL76Ed4MJ7McsK/1LULtnVgbYgdNZSjkqSDZir9g0qLhMYlYND6HdIF8HaG1hFFPrLD3MsIecYp+SSCTXHOjfBSWprIb2BP/OY3tG2J0LoOYxwBu50YrNmBd9wjTrh+gmPNFf2vf0Z22dV8BvtJyMk8R3opq7FXF0vOJJOUKMyj3zxOo3bhCjq9Rdi6AYtY2Ibf2UUP4ecXOSo2ChPoGMiTYwUIUToDw4rt+MaRxIlwvqUklW+eMNzwmevhcc9Ss0VYuhG8YvjdeLGFrnrWjn8WrgDKaWE0fKY5PlD5zYDc1enMXUqJH8I3ehkk0tHaIq7p+FrY3bJafOkTXL36eupbdJcy7yt6RfhS01utDnnu+gp3Hj9OsaZ38+9vNPfY7waDdk/j98/QGLbf1dzPMZYAxhIFA6srn7CEouPHNb1/+7hZk9AMY14ge6RCwNDwPQgzEmzrPz9OG2njcfxP6YLjPB5LTwnDOT0aTjbTD/j+GpRMNncY6Qf8vRxGC8UfhUqNkfcFR2E8/5v76UGNka7AzTwv5UJctYkZKBwNYdwwIzlrxbGuojSJ7YVDcVLXtuXXlXT0hC2V92/7/NKeemt5Vaix7Dkjxj/Sl5unlWdmTbxTWO343r68tOjJx555esLilimVX0ZGvuP4+EKtwaCvBbpDge5LoAPYOrKIZpHEN3k8ZQ2OATawc9fCqD6zEp8ZstJw9rvvzhpWDqG34zOTyyuKiirKJ9ulqGbHvSWT6AV6HY53JpUst2PjC7sOfvnlk7te4DpgMgaAjKzu4u+wIOy6vb1ihJLFgKjzhzsR/QaHXNOfrTH4W4zL5tUvKG/wxXmHn4G488GeeMjQofS95Xed+fXnc23NTv19DHL4Mjm61dySU5dKecLIJUWQsLLZlUWTKmaX4Qkr6Ogh9xhe++c/XzPcMwTkw5/UvLDryS+/PLir3b68ZBLUYxLW4KGTSnBHl61SQQ4P9k0I5qTCsRSh8p/IaCmiBQmXMicXZwn3OerNC6ragjbFfP3G7/RX3OuXH6gw9e29u04GNXm+8dx827P7wLH8mVz0b4p/3w41xgKQh1FQ1BOOE11rNBTb6iaCLDB4ZIzPTvnppyOOxSvWrHntVP59mZJ3QZ518Up7x2t2O0myL3vqueBgxjcdLX4MfA9EcczHlNcdaiEzDrurCCIZu6kwSSyjn9E/yl+pMvi32jbYu9SFT9PRTk2SJzrKP/1XRAStMW47bXdX3HI73ayq9AVFNikIZAvvqj2VTZgqnUIZbiasWINfH73B/MjjKx2v2Mbnld9p427WOrv0/OuOC5CgkpbXbNtPo5T6YQJ8rAVcH1bVuu9TsWs53GvIYRvVHAOeucLx1IoV+LRUOslkmlRoqr/ewJUF+5omOloQOc6A7jhs5VBKvZgkZaeWtEM/MdPAAKMB683XRs1PMwpJY9NGMlhzEcDSffbrMx7b3Tv4c1yUNauFr0Ugfzjg36K+AWKoSvb0UDjFHoGJwkLHCcYfXmLFzz59Dd9f7fjATH+d1sw00HELXmy3X3OcgJ1Uy1T6keIzTvm92I7DKbGgUQW129lMdZyHDfzgVvWNzp8V/6cbxh0fKs5O21K987GVjletybmTZ1ocH1luzymD62lGI3ldZcN9xD5Hd+4tZh587XT1+rsdV9xboOJNM8sqnXoI4jnPVbf+lR8Adg8/YDg3cgM1PpcCbvd3meyrBDUu+bvMan3xJIOxpMToYK8yHQ48kH5aehxWWPTSCUpfngdllAZ74Fh6kf5O/6AXFOyhtEx8CbB9URTL8m65MdrJvzN3KlLgK84MSU90S573Ox5fKWTXqBmSp5qu3Emz7YpP5wI9FrOhnJ6qmJuH6grHrvv/KkKvw+4BH7lJbCZB3mH0+vB390pe7ukD5GOjlK2tbj7guHfFffit+M3m/kViIj05ueTss3QGN/F/meZ6cZ8s7LxETooZN6wXu391odaLLLrKjH7Lbbqq1Nl3bVz27meTX2owerbYdPoxuUteXPf11WlnBuLYptbczJTMvtGxDy05sHdgBL1aV69NGzU+JDpp66pD+wdw2nFAO1eax9+ARfHNIXuP5JalByc9t2oVhIA3vTZk5Jhk4W7P1QdO3UuesOOJ9LDd0bAqo+zOHYtXPgdYbJ2kYgyLWffVmceuonhoig2wlih+is+MLdRNs4kxHcWKnwrD7I77X527vR1/wGq/j9k7bsCDujFCif1+agGFt9ADNUK07crPrbjKRA8AO3ZHubADar1i5Ky/NFNhLtRf2DkrysdZP/bD5PP3DrVi4zz6yQevnX2fflwtDJj31D/EGMdYIY+hCJsdRo56QjiClG/oRYbXj9cmDIMoechHuQQrwMLyvY24YdFVHL7I8XDLoUNttHElpT/MFbJtwi7sCYXiMpoJ7tWHfgOfq/EetSbt/BrWnRmAD2uZxNXmzJ4TcDfRhQnD5lfH1aYYhXF3jM0e5E9nVwkxll8uLcJVtp8aKkJDPsb5E7Mqo0UoRB2rhAZnzZsCPvse4P+phkwVomnlXNLieB8fnWsnow/jYU0dp48719jN9GnSAflyKPDFdmox8Xgcdr72h9ao5PHKHg5CQHkVxUxNOu5+Z0Ncv9DyRcnJi8pD+8VteOfumYaJ02yNUyYaLthvGz+o3vzHA1a79YE/zHXR425rnrWCXnl4+YDIpQ/Tn5fPAro/0HB8UBPu+n7syROa8N/YW27o202fFj0UnsK4VynvrwNVtkJBceFQkSlsQUvj4Q+Javd0fe7UpoY7c/XTl76zYXjfUN3C5OSFutC+wze8s/TCrOW417a7Iwes2I79V8xqvm1cdJ2Lw/pB42/j71HxKSmTTOD1Bn9LGTOYHazsZFVnQhiEJ6+mtKV7pk9e2ctT47t5qnbTlCm77ixbEeDht3W69gEy4Y2y4jEaQqS07Jw3pkwa7UE0GdmKriGXTfF+5Nq7bbN73fEvNNCT//jhjQkXDzuvv1/vuOR72SsWxnoi5x/M87iLwvrg1xf6z/peVn9P0vVXIRaj6Tw174NzMXhCLPqWHEdPajxRqySgxzQXUZymDeUII9GTpBodgHMmOYmGQP9JGN9HcKBKuG4UHgEvgD01nJ/B2QJnG5y5cD4Apw3O9XDOhrNS2IP2wjmCYThP8XbU5JGI7NKmziuaEMA5ivI14+G6AM7+qEwzGO5fR2VkFpzVnXZNGjwPQGUev8HzDWiSRovypTkwjl2vQx/Dmo56a7LQBMC85hna+ZO0CXlJUXBNRRkgx1eMZ7guAfovEPYbn4dgn7EPePZEQ8UKfs0VN6Bc0gfdztrSHeh24UU0Qdjc2Sw64Aptj+2AfweaIL6uzGPjyGsoSfRDhaQExUHfUHFE5yeahM6fxImQKUZ0fiOGohTxEbRZ2I5+gOtuJj+YIFQ9RqGJaAl6G8u4Gd+N90BF/ZPgLchCvJAhFAtLhAeFM0RDhpJZZCuBCBTHiOWwXm8WnxBfFn+TaqV10hnpD80gzURNrWan5g3NFx4aj1s9sj0sHhs8XvX40TPO0+i5y/Nlzx+9fL1GeVV7rfHa5/Wi11mvj72uegveQd4F3ku9n/b+xqevT4bPCp9DPu/6evqO9M32vdf3iO9XfgP8yvwW+r3o97m/t/8Q/yL/Nv+D/md7efYK7VXca4nqqxWCFxqG9rDfcKAAyC/s117sizdP9rsq1BePd/nhbHRcbWMUigvUtoBEbHL99qkfblfbIuonqBkXSchXGKW2NegWoUlte6JA4Vm17YPCCVHbfl73h+aobX80Us5S2wHIV96ktgORv3yC/SpLZKvvMU6dtWEtxLLaFpAnNqhtgkZhm9oWoX1GbUsQF/3VtgYlCOlq2xNFCuvVtg8aI5xW237Bg0mE2vZH1QM/UtsBqI+8QG0Hov7yYygdmZAZ2ZEF1aAqVI1sSIYdbyXUBzJKgIr0VpQILfZ7KhmlwRgbssJpQQakQ3Wwb5FRDqqH8fHQSkW1cMio2IVl5XcGuBpgTiN86mGkN8qA1hxAKEMNMKISxuoApYqPlKHN8GVAqYdPM4ypANwaGCfDfBPQ1fE+b4TSTWa7paaq2iYPqYyVE269NVGusMtpNTarzWLQ1cXJOfWV8XJqba1czEZZ5WKD1WBpNOjjvTMMc3RlDXJlta6+ymCVdRaDXFMvmxsqamsqZb2pTldTDwS6c1rC5ahBRmgzzdUDPwb4tHLJkApZYrDUGOV0U73eUG81wPM0GFrLBqSZavX/e5hy1+Qbo8v/c5hlHMUKOCZuiQSwHftlHyozWKw1pno5IT5xZHfSXYT/THZ4T7Kcqovo8BsJYuQoiiPZVKdzMm001YNBbWBmxJ3NBq4yBo2AQ69iNAJGPMw1wdUC7mPgeBbuaPGAa4A5qNpmM48ZMUIPoI0N8VZTg6XSYDRZqgzx9QboznTjwOmYzgD5c0CwPiatgQeNAaQ1oSYYy8Lj/47Ts/DxviFlxVw6aLnz/OcA9wZL/PcPRv3/RdK4sba7ZK5RtSjzfh33gTqu1bnwzMRD4ea8MMmKOF4dR+tyfAW7mvcZVLmqOJV67pV6jmPkvQYXNcXCirfFcb5MnMN6Pt+sBpdCwQSoNtXCNdwrFFkqVU07MW2ci+5xoYNRldxDzCq6E4GNVnhXPMkZi8xakW5eEsktp+Pxyq5WzlclzNGp8ik+WAleWcdRbLzHqR8jtGpVPx7i4rGLAktIjH8bxILi54xil07YEzN8moBKA+ezixs9l8DGfa0Cem2810njrynEqbFUCZw1cBRFJ03cB6p5TrCpmqnjz9wlcuJbunmlwm0D12Gcm3VYu47b02nrrvi1wuy4v5AjziXnCJ6XZI6sxIOCXaNqtbv1by61U3MKt2aXR9t6eF2XRE1cH3X/EQVnNBh5Tq1XJTS4UdTzT0Yjjl8b+A+ODSCRzTXG3Y9r1SzptFClunLUuOxhhbzOorNUnaUDRBPPDF02cM9FXRr4cyaoh/E2NRqs3cY6Y6VLY+45wH2ezGXWqZaqcOVtp68p2lAyue4m9jTxNUhWbV/Hr1354z+xhQ0kN/N1TadKFN9NUzeby3Rid/Ffx6OvhseyM6Mx3m1q1lOeKJwynerdbO7udc71i1FR9NUAKDo+zymRnnPK7FXvpo0qGMekqVafWdxyqI57j+K7Tho99WP9tzK55zh9Nw/TcRvdiIObc9KdXk+93IjHONXutXxezU2yukXNQAbOX103XOcTq8sznXHTcxUxqPnO0M0CTVwqPZ8feYN1MdIld88ZbLxz1Y108zYldvJ6rDMVPO5Nbrw2qPHgtEQj9NbcQGMG1Mz1XK9GtBkOZRXT8cxqcM1wt7/C880jpppneplfrSqPBu5Rf+0vinQ3yuGst0GtdN31dSOtym6ac7fhfzdmrWo9LauSOKPOGVGsgqh11SAWdUZ3RDP36LnwWaVaTFkX67lue9Yf/xMZ66+lqlBjxKaui0aXprKRltMpRAVwx+gUwl0pmgL1ZDHvy4FnMtRzxdBTBnfsHxxlcLuk8h7WH8mjcQq0GWIhmsyxFIxi+GTY0+AJw5b5PbvLhfEFgMXmatFUTkMLaCV8ZDHHzoeneXDVquPYjHR4MhnuWTsLsWpUocf+2VMpjx02j/GicFoKz7uoducqh1N0cpYPd8WAn632sn9ilcPxGP9xXFOsXeDiM1PlNJXriCEzzHTgKI/fsaeT4VoE40q4PlO5zAq3BVyGTOhXZNFyDhRLKByl83/KNY2PYP/Iq5RzwSiVqiPjuIRMngw+n1HN5U8VzgpVK7N2F0q8qkuFD6b/MhflEi5/Hhwyl7+U/zMyZptUwHfiOn0niyPku/xoMpcvleuhkFNI431Mi0yfea6RxW5WSef6YnZjnGdwSqlcIyU3lMSJ1t06N/IOJ4UsLp+WayqPjy4BPWphfI7rieKPOVzWdFW3Cqbi94pP5LlpN53LyCw7CahqVZ9K5brrLoUSIYz/LikUC6Sqn+luOuuyfoFq3XSXrQu5l/1ZK1N4LGr5qFRu6xKXFjJ5/OarnE928zCnHSer/lno4qy7fp1x5Bz3n+QOBctJu7sFM7g/5akclri08e9xldylhXWtku93bK683X3ldq8eu6pS9/ozzi3XulcCShbO4mPreozreqrkZ2XN6trzuNdwN1q5nLtkpabvqn6d1YeSuxtcr5ic1a+e1+lKLWh1VSXK+mFyVSZNvLdrTVd2g3V8hPt+z8rpKpI1qDN6Yin1pY5XC4ya9QbavNkK1XOHaObrvUKlibdtamXC5GtQx7Ln83vsii09dlX/zgZOWf6d/i3c3mZ1T1XDNczqyXgV14Kc+7MunTANKG+/6npYvcv7GNoY1LMOZTqocuNcr1pceZPGaHojlMlfxrGXqOxFrOsFrDzEajDIFYZaU1NsvPwfvHKN9/bumlxmsOhkBdn1otd7+E3/vL3/+6+E5R6Ua4BF2WbR6Q11Ostc2WTsieLtXWSw1NVY+ctQGF1tsBiAVpVFV28z6ONkowWEh2kgsKXKECfbTLKu3i6bDRYrTDBV2EDgmvoqoFIJTLORtmqD+l5TV1lpqjPDcDbAVg3ooCT2olQeEslVEhkLYHpZZ7WaKmt0QA80WNlQZ6i36WyMH2NNLeh4CEPkE+QSk9HWBDqPjOWcWAxmi0nfUGngMPoaEKymosFm4Dx0mxAHVqqsbdAzTppqbNWmBhswU1ejEmLjLYoqAbbBCuOZOHFynYFLze1rrY5zoxHHaI4wWWSrAewAo2uAVVX8HqQZcwBrZoq2qarjhJqqTXV/nsDMYGyw1ANBA5+oN8lWU5xsbaiYY6i0sSeKjmvBJZlAlaZ6fQ2TwzrG27sUunQVpkYDl0DxIs6AywnqTTYwg1V5yqxi7vIApU+2VutAqAqDqjVgA5xc101OUz34hUWuM1kMNxRbttnNBqMOCMUrTHXvrdPZGX6dSV9jrGGOpqu1getBA0B1ej2XXFEdiy+dBfhqqNVZOCG9wVpTVc/ZqKq1m6utbBLzUF0lgFjZDCc/1p6UFI/TKwrT1boB9ABR5zl56UIEFutr7XJNN1cHkSwG9j9W4GNZw8qUyWzjDBED+J1BEaDJZNFb5UhXLEYy2s4OOZKFbiRXG1gnT42ZCgNEE0NtADswIRpNNS7GDM02iBpZZzZDiOkqag2sQ5EfkHsYplpnk6t1VkA01HfXC5Dr8nC93FCvVxmO7J5XIhUJb2ZZq6mWRTY3HTOUTq5lGQTixTnQrKucq6sCwSAW602u/PGfO1Y3UpC0gEVDrZExla2VMwsLSuWSwszSKanFWjmnRC4qLizLydBmyJGpJXAfGSdPySnNLpxcKsOI4tSC0mlyYaacWjBNzs0pyIiTtVOLirUlJXJhsZyTX5SXo4VnOQXpeZMzcgqy5DSYV1BYKufl5OeUAmhpIZ+qQuVoSxhYvrY4PRtuU9Ny8nJKp8XJmTmlBQwzE0BT5aLU4tKc9Ml5qcVy0eTiosISLWBkAGxBTkFmMVDR5mtBCABKLyyaVpyTlV0aB5NK4WGcXFqcmqHNTy3OjWMcFoLIxTIfEg9cAoasLWOTS7JT8/LktJzSktJibWo+G8u0k1VQmM90NLkgI7U0p7BATtOCKKlpeVqFNxAlPS81Jz9OzkjNT83SlnQRYcNUcbrUwSZkaQu0xal5cXJJkTY9hzVAjznF2vRSPhJ0D5rI4+ymFxaUaCdNhgcwzkkCDJKt5SRAgFT4L51zxsUvAHEZTmlhcamLlSk5Jdo4ObU4p4SxkFlcCOwye8IMJuNk0CczXoHKL7MRe/Zn74BRbLYqYIY2NQ8ASxgbfxoL3qVtrjSYbcy31eBW0iNPpUr+jONeqyQBcOGseghc5Rlvgj9DZPGVR8lwXcHFluQ4Nf2y9AHe3WBV06++0QBZ0MpSCcSHiSWTphorj3RYButM6rpn1dUCMZjlGgX5UlcL06wuNrsHlHNBNFtqYEqTpcYGyUTWNcBTS818dSm2qEtVTwkYlZ78WwxWM6xUNY2GWns8jLWw9YxzUlNvNFnqVNG5+iptY5w51CZXcXA9CG6yVMXL3v8n34qO4FXwXDhH8MpRz9/HxfN3o2Z41v09382/Qx3RVDO3ZkQNpMPmeHO1eYSak//6O+tu30qjm3+B3fM7a9f/FadzIft/8vz574jQlvLFOUreyCWvU3LWm/zNn5xJIKePklePklN/kFc2kZcpOUHJ8ZeypOOt5KUscuxW8l+t5EUfcpSSFyh5npIjvchhb/JsCDk0mDzjTZ5JEZ9+qq/0VF9y8Mm+0sEB5Mm+5ImH/aQnkskBuByIIO3JZL8P2bc3UNqXQPYGkr1t4p54snvzAGk3JbseD5J2hZPHg8jOx4ZJO4+Sx2zh0mPDyKNwefQoeWRHX+kRSnb0JQ/7ke3bjkrbKdm2daa07SjZ1iZu3RItbZ1JtqaIWwBtSzTZ/FCgtHkA2Xyk83hKp/hQIHnQlzyYIj4QTjb5kI2byAY/cv8t5L71euk+StYDifV6sm6tj7SuN1nrQ9amiGtW+0lrepPVfuTeVd7SvQlklTe5J5ysXNEqraRkBcxY0Uru9iHLBpClcLM0gdy1JFi6i5Il83pJS4JJ2yI/qY2SRX5kUYq4EEYspKR1wUCplZIFA0nL/KNSCyXz7TOl+UfJ/DbR3hwt2WcSe4rYHE2akkkjzGicSxrg0vAHsYUTKyUWQLZQMq8Xmdcmmk3xkpkSUzypp6SOktoAMjeXzPEm1ZRUeZOqFNEYQQytRE9JyurKuaTiKNG1knJKZoWSmT69pJmUTA8k06aGS9OGk6nhZEoCKfMhpSV9pdJNpKQvKe5LJhWFSpOiSZF/gFQUSgrhUhhGCvL7SwWtJD/HT8rvT/JTxLxe/aW820gudOcmkInwfGIryfEj2VneUnYryfImmVo/KTOBaDN8Ja0f0SomyfAl6Wl9pPRNJK0PSU3xl1JbScooLynFn6S0iRPGxUoTjpLxcBk/k4wDEuNiyR1j+0h3BJGxY4KksX3ImNHe0pggMtqb3J7cS7q9lSTD7OReJLlNHOVFRqWISSP7SEmbyMhhXtLIPiTRK1xK3EQSYv2kBEpu8ye3+vpItw4gIwbFSiOSSXyEtxQ/gAyPC5SGbyJxMCcukMSliMO8yNDBntLQcBLrR2JTxCExgdKQTSQGnsUEkpgUcbAniQaI6KNkUHCENCiWRMElipJIAIzcRCJkTynCm0S0ibInkVPEgdA7cChJOTQgcLg0YAwJjyD9W0m/ENI3gdySQPpAdx9KwkJjpbC5JBTuQmNJiOQthQwgvfuQYFBycAQJgrlBrSQQRAocTgJAOwGU9IK+Xv2JfwDxbxP9QDi/P4ivD/FNEX16EW8Y6n2UeIUTT49gyfMo8QgmGoDV9CaSN5FSRJEESWIYEdtEgntJJIiQFFGAlkDhnuA2EfkTfATrl63Gw/7//EP/rxno+gtH/wskVOJ0CmVuZHN0cmVhbQplbmRvYmoKMzQgMCBvYmoKPDwvVHlwZSAvWE9iamVjdAovU3VidHlwZSAvSW1hZ2UKL1dpZHRoIDMxOAovSGVpZ2h0IDEzMAovQ29sb3JTcGFjZSAvRGV2aWNlR3JheQovQml0c1BlckNvbXBvbmVudCA4Ci9GaWx0ZXIgL0ZsYXRlRGVjb2RlCi9EZWNvZGVQYXJtcyA8PC9QcmVkaWN0b3IgMTUgL0NvbG9ycyAxIC9CaXRzUGVyQ29tcG9uZW50IDggL0NvbHVtbnMgMzE4Pj4KL0xlbmd0aCAxMTg1Pj4Kc3RyZWFtCnic7ZxLkqMwDEC516xypByhD9HnSU31EXrd66ymurLIIkVRHgg/Yyz5IwE21tvMdABZvJZsTGWmqgRBEARBEARBEARB2Bw1cHQe+aF0jk4mM5TB0flkxMqa6PPGVnCizw+gV0WfG2SeE30u0DVC7OHgy6sUH4rr0UT0Ibif60QfiM9DsdgD8NpRFF18+JLgo6ZkfW9Df8AjPmLK1Qc/kAS8CCjbHnjAV0uh+mBDQe+gCu1dR+mFxGHLKR+Y5JWpj2HFmK9gyikfUHuhoVgyyghGeQUWn9ijgK0ZMcHICWUFY+mJPf3jCBOl6bNbipQn9qZPozwUZs9eLrHySiw+22dizwtee6Xpg+wxhgvi8kG7fl8spgilRxE/8FJftAB7kpy9NkQ++gB7lHi0hLoQDTXEXqxVUUqPZ9loY3ySg+xCiva6IDU9yg7Y7XHGOy7KDph50kqP675VJt1rs0cLF/xCHyKD7jXullh6wfZgd3mU3/J2qfaClg1cnbrX6Zff2h5jOMeZA9DB+2fyi8cie3Lpedtbqnu9WVXfv6pWV1I6m6Pf7072HC2r67uqX1I+W2PaI0fz+K4pxlSDz+fjUbX60u7e+X7ppee2Z7iq2461nzB98Jt09+5oz6wzy5pququqxLt3SpYuDw1hqoPdGSFc3UtPm8A4OEPpIfbcZYd8XxXv3u4a6zevd2FImEke/I02P3fWHP4i3QtftQv92NuVHtldhXbvsfIGfVvZ83E3H4VjQ917tLzZHkMgyxsvXV2suxd4xuH2XG0TFMcS1q/s4NG1XZz9emreNLaxx9Syiw2w2b08eRNhSkIP4nYXVHav1/3953LtTUIeUxZaEEMd6g6JWI/u2r939u7GxiMJeUxpTDGc7nzKbnI3bIWHk/XuTaP0Kt4HFrxl9YNouHp5/Xj+/OiSjDzGXS7oTpmgwWojwM90xdi9CcnjeS0/02j3vtLmMZRReL29n2mka2Ly6O8qlm66e2+aJtxbh1l4lfHLbbs3MXlkln7itPWsCq8yW+O88syfm8C7tBTesnHn4MSUE8KssLZto75MZis8YBsTlWea8NyRtfAqYBuT8iv7MHiqwV545oo7DnWW6tu28Pr4P/pI/VC/6kIcMgU45YGvnm0jXdSNOOjxBD+SWEHkfQ+ltx4of30s8hB35h5weeyWd/cqDnuovK70GvCXdA59hAjwcvFG3+9ZDufdvWR7aOH1pYePkfWjC1Feg8vTSg88Jefu3dTdXHpYlFvW3RtNLw/ZEXsuSnlPfpE45DlnvMW5+XZvHLi8EHdV3pNfDA2+kIY+SRbVvVjhKZ1v34iXrB9dgkDkaVXXfZkg4F88l9K9sDy9Yb9U/00Cb24qq//cIBJInjHZBZZeVcbkB6wX64UisPT6S4jJpY618Kxr7EM9g6OffPJby9OXWPPcR3D8jzN370KeWrI6+RFh78yT3yhPrbGd3k6REcvoWfdtFmmQuJ4aPQpx0skvwNvA9fMaYeKU3RvkjcQZ9217eBs5affuRRn7ts045eS3Iyec/PbkJvpIyNIhCIIgCIIgCIIgCIIgCIIg5MB/tWqxeQplbmRzdHJlYW0KZW5kb2JqCjM1IDAgb2JqCjw8L1R5cGUgL1hPYmplY3QKL1N1YnR5cGUgL0ltYWdlCi9XaWR0aCAzMTgKL0hlaWdodCAxMzAKL1NNYXNrIDM0IDAgUgovQ29sb3JTcGFjZSAvRGV2aWNlUkdCCi9CaXRzUGVyQ29tcG9uZW50IDgKL0ZpbHRlciAvRmxhdGVEZWNvZGUKL0RlY29kZVBhcm1zIDw8L1ByZWRpY3RvciAxNSAvQ29sb3JzIDMgL0JpdHNQZXJDb21wb25lbnQgOCAvQ29sdW1ucyAzMTg+PgovTGVuZ3RoIDIyMTA+PgpzdHJlYW0KeJzt3Ul62zgQhuGCn962pZXlK1jO/U+SpK9ge+VhH/QCFkNxBMACMeh7N51FW6Ik/qwCCJIiAAAAAAAAAAAAAAAAAAAAALAfk3sDsO7+4UlEjLn6say1n2//Zdoi5PdP7g3AhPuHp0FQgQGiW5DVxFprZVR+cZuIbmaTzbDjgjruig+n8w4bhsIR3TzmCuxcXIEBorurhcQSVwQhusktt8QkFnGIbkKTg1KtlpjM3ziim8S4MdYqsK6GA0RX2SC06i0xZ4bgEF01qUML9BFdBfuH9uP1d9LXR/mI7iY7h5aBLjq3Et3+ZK9KyRpPRO1QCRnoonMT0R2cpPn34fnr7Vf0q2UJLTDQeHT7MXMnVEXk6y0yaYWEliMFpO3o9ovtxlFouvO0QJw2oztI2sYyNei3c4WWC4bQ12B0FYttIaEFxlqLrtZMcrGh7UbsuHHtRHcwIxWdtDJD221VCRuDEjQSXa0mWbHZToG5ZXSqj67WjJTuzJYuJqgwVvfqnFsott22FXU0QXYVV90UM1LEA7WoNbr9hfgqTXJpxdah5GJOldHtRy56ny65SQZW1TfW3Z7bkmek+ii5WFBZ1dXNbRXFltxiUmXR3ZjbimakOCGEZTU1zFsayFqaZIdWGauqqbpb9mZmpNCeOqquVm6rKGINlNzj4w+RP+8vtW5/Fe5yb8C6blcOvWjm/uGp3txWzcofa+X42MJnKVbpDXM/t0GNbo1Nsso6kyJwYWJ6RUdXJbcVZWD7OpOicGVxUuU2zHG57TfJ1tqKMtDAEHfs+PCcexOaVWjV7ZdN/9zW2CQ7TeZWRGwd06BVKjG6ce1upU2ybJiHq0F7n6gU5TbM4p3Aeptk2TCerwU9cyLFVd3Q1rHeYiux44LUxjed3oKeOZGyvteg3Na1tnFM5dJFRbqJ7TPWvG94UgwmFVd1PVV3AdBYCbl1Z5InExs38O6/lBGxYkQshTeFgqLrX3KrbpKdvFPKcwXWxVXrPrgiYqxrmJms0lfK8dBzV669SXZy5XYhsYO43o9DGPF+Rj5efh9OzyLWiHl/pWfWVFDVXVXvadu+nXO70BJ3jDHp1k4bEUvZTaCI6PrszQ00ybLjKdx0c05yyeH41a21xtx1/8vH1ZVDhFdZEdFd1sCMlLPDqaCgxFr7N3+f3kfDybdY+F3eX3+5nvl4eqZnVpQ/usslt40mWVJ2DT4tcZ+18hn1cPDohwzTM6eQP7oL2miSJc0HCS6wopZYiTyMpgpvqyvAl2WeYZ770ptpkkV7qWNoYuPiOvde0R8h3Txz/7D4x5qvm1n7UWLVbabYah2AdmuJu7dTKrN/GWOtVS67g69XRL42fOrq5IzuuOS2cdrWUb/V+6oCE9t7IeUBbzOTINFyNsyD6DbZJEt4bvdM7PJCSN2fQKtnbun4vkW26A5y20yTLFGfJaIllthpJ1k8Oqgn9v50nnyn7TfBr/34vlH+sW5jB9Gg3O45iF290kAxBm4d5cKnav4m+DvIE93+/t1Mkywhud1toriEAjtgAnu9wUdoYD9Rkb/qOg0cRH1OAu2T2EIKrJuSuuvNTVlrjTH+U1WEdkGG6PbvNuzUntvVCTb/xKqvnVjYqo3uH86T72Z7yyqPp3MXVCNizJ3/GaLBFRG17yTqMkxTNfaTLJwE8h/KRid2zwJ7eceJxLo4DhZCTw4fDt9VemmeebCHUGwnldIwV2pucOtZZiNa4tXDQbodfRzahe2fH/abhbO7dMj+Mke36pI73jsVExs0+ZyowF62ZKrMzn+E4+m53xUPfuK5lRmENlTmhrnS6I73s8l1SDLK3niP70b+oZfXJo2rE1RmnX5u57riQc9MaONkqLrdVeaV/kITd2C63vPk8tF6c87SVZqI+1HskNK+0DLbOZzOXW4XD8rfPfO4San0UJ5FKfemqsVC8AblovyIjkWU2Y5/MzXoqH3+BGNMU/nyvyfbqtL6jugy6/g0yb33ehrklg45DlXXy2QJXV560f275P1yS5l1tiwgI7RbUHXXjXO7us8VvkduLLMdn9zOXUtojLncgw4xiO6S9uZRtpdZx6dJXi6zh9OZ+1VtQcM8ob3WTqvMOldXj4xy63nJ/vflu+bu/eVnxDaAqntlcrerutJqldnOQpMcdMgzxlhrW3yk8E6I7re0t3fJQT2019cS/C227X11VaBhnj3rU2mxVU+s47nqMyix9Mxb3HTVVTxVW4JEoe0XWxH5eP092RhL8fPqjbnp6LZRbHWnoAYO17kV1SvyGO5ucbsNc8TZ2tIkKrPOcRTa6zfS+a7cr1Dd4bIEN111OxWGdtyyqoVWpopt7410vysjYo+PPxjuhrrR6LqDPYkdmyu2Sb8reuYINxrd6jq0HVaJuLcYZyhpaN1wN9GLt+12x7q1SBraPZ9+MOf7yntOEQW60apbhXSh3fnekWuW7laFOUS3RIkue1i9dVaWcQSniOIQ3YKkKLOet6czIu+Zx/9ENwxj3SKoh3ZhoZiIfL79d3g8d2ExRt5fcuaWFZERqLqZ6YbWZ2nn8fF8OF1ya+Qja2iv/cm9ATUhutlohTZozunweLbFFNvOZbhLDxiA6GagMgu12hJP/1mJxVbeX35y04xQHOd2tXHt/sbTsG6l1PbnyqfAcDcUVXcncaHVfcKQHfy3JG5VFaeI/BHd5DxDG/JYwJgh8aXkZj8JNOeOaaogNMwJzYU26FFg3d/KtmvZu40pZ3ZqgBWRQai6SUzOIRljfJ5mkuzZCN/rDQvuSVkRGYDo6vN/2tCeTzAxImKNe7Z86veKw4rIIERX0/IpVvePXFcIFzirPIPoein0AFyjLrfZU1ovThH5o+qqIah6mGpex/OaUJBL20IzuI7ooiCXPpnh7jqii9JQcoE6HR9/5N4EAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAr+B1cvK3oKZW5kc3RyZWFtCmVuZG9iagozNiAwIG9iago8PC9UeXBlIC9YT2JqZWN0Ci9TdWJ0eXBlIC9JbWFnZQovV2lkdGggMzE4Ci9IZWlnaHQgMTMwCi9Db2xvclNwYWNlIC9EZXZpY2VHcmF5Ci9CaXRzUGVyQ29tcG9uZW50IDgKL0ZpbHRlciAvRmxhdGVEZWNvZGUKL0RlY29kZVBhcm1zIDw8L1ByZWRpY3RvciAxNSAvQ29sb3JzIDEgL0JpdHNQZXJDb21wb25lbnQgOCAvQ29sdW1ucyAzMTg+PgovTGVuZ3RoIDg5Mz4+CnN0cmVhbQp4nO2bTW6jMBiGucScZlZZzXl6hDlEDzAnyBGqiiOwHKEqK9RFVaFRVSFkeQyJgzH+AfyHo/fZhELw9/npZ5tAUhQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDG9F+pM8iZltLUKeQMhT4XoM8F2HMC+pygtEmdQsY0KD4XMHadOJY+eqx07BwqXZqbviOlSyn07YbC3m4ozdEeS7suxOTT9CBlbBdu+dIFcXPIUx5fdn8u9cXqzD1UnvauGf+SMo/kbwqTozzTuhFBnyQvO3u2ZTdkl8T6zlOeVU+wTs3mhkzlJdM3n1gf1h4J0S9pUcpWnlEfIWFmc2lBz3TJGNFc6wkQEiTgfIfXENGI7W55NyVjecXHkHvXq/EsrlhT6Sa8p+NMM9RXrGBu8o6osImW0WTgq+OMf7JXTfXPOaLCOlLxTe7kfVtaOZzCKIncu9yML4RRVXst9EcyGCELobO1PJP1XVeW25u8Kow2Z+thWVSB2xcrpR4g/GJ8DiFsgvv+9/bntLbl5P5I2EGgGWV8b1Xx5WPG79WN+853K6z2wv0TLfIEyvLMeB54elpXfMUB/HVMHfPXh2hbN737m/WD3MrYABs45ZgFn717qkF5uiF77Wlel8zE/tgl6/DS3ZPYpE8vQn/SXnu6vFb9mwNxsyds6PT9UJ+u3e8kb62qxPqmoiv5h4HensCJz+yaZA39MHXQxVkSfd005Q3btC3W6DvRv9cNda6GTqiPOOqYvSWqvvuIHWjbQWDbWTN4v13mGFzowsmHfNRPMn2daG+gtXegZKP8qk/xPuPZ80P+xpzU6u52ttIpYukFVlXFP2i1447Fm8wepmO+vE0NK7cDU1K5+EbeL4Z5aKBUZmqRwY/5NXdrWozipcl1YVX2GBedQEKqUjh9ORINsfgXf3yKk/OIac/0279Xlsj3R9M09Y1qeUtm/njWrMR/yc3aXmyFx/LLyVfbTQ8qfCXFJiWYOiGR6PbOxjecLDc9NhRUQHfFZC2mvTNfPnezoaICuivEYRAshCKmo70tD3nCdozyFT1kEDmks73DMIqLau+S+hatT4JOrCoeyl70b2Yxe5d40YIT2d7zY9mL/bCofyx7gdd1mc8wz9TSEdneZ8xwD8YL7LlAX1JnAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACAFPwHIK0zZAplbmRzdHJlYW0KZW5kb2JqCjM3IDAgb2JqCjw8L1R5cGUgL1hPYmplY3QKL1N1YnR5cGUgL0ltYWdlCi9XaWR0aCAzMTgKL0hlaWdodCAxMzAKL1NNYXNrIDM2IDAgUgovQ29sb3JTcGFjZSAvRGV2aWNlUkdCCi9CaXRzUGVyQ29tcG9uZW50IDgKL0ZpbHRlciAvRmxhdGVEZWNvZGUKL0RlY29kZVBhcm1zIDw8L1ByZWRpY3RvciAxNSAvQ29sb3JzIDMgL0JpdHNQZXJDb21wb25lbnQgOCAvQ29sdW1ucyAzMTg+PgovTGVuZ3RoIDE2MDc+PgpzdHJlYW0KeJzt3Ut64jgUhuGjqpp2YARsIZD9r6Sa2gLJCDLuinogIoRlyzdZtqXvHXVXEscP1u+ji+WIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACjQy+642Z/mPgsU7dfcJ7A+m/1RRET0zOeBsv2Y+wQADEF0gVVSc5/Aynz3luX2fp73TFA4qi6wSkR3CPoqmN3PuU9gTbb0lrEYVN0eWA7CcrCuiwy97F6Vug9rcu0iEd3ecm0K2bCrAHkjul0V0iBWzS222SO6yEFTaDPuIpVyixqJJzGWKVxm875YVN0elHOn69U3y7sNzSL8+WutPz/+pDyf9Ki67WpL7rChLxkeqcsds5APmarbldsg/tmd7Cqv1qHl3ko7qwS+kEY2Xm1iTWl1P9ISiq1F1W3RNMrd7N60/BWRjm2lqUqT3oCX3at4tz+bz8Lvg0Q3JPrslGmL4jTH0hpcR4Ey63+1qGJr0WFOym1h5r6w2R9JrxVOrFFsD7mC6DaaekFIa13O8wNhXRLrf1vhtzyiOxtyK51DKxRbD9GtN3XJtYPeMnVPrP/NhRdbi+gmUjtZKuU1RD+0gRLKdFQAfbYaaXYamAXhQtpirzJr0EMOI7pPEoS2qMRKzzJb+yOldUw6IroPm8PRfRGGlljvxSixYqx3/91a7hSr/HAnst0f3aiu5RIuzXpD61tyG2Caqt6Sr9kytSVW6eY+TOXHdM1/peCfvjuAWlqTILouxavjBgiGVt3e/+3+swuZjnrZHZcf40w6NlGwn76XwKRxZehRYX7m6/lhsoWE1lcbY2vGpkLVRW8dJo1D/RfzhcoRlPqxORzlO9jXy1Lunp8fjzPxY/yye53rjkPVfbBVV4lcKbyeXmuz2+e/PxwY6PZnz+F+TKWUiLpefsf7Fe1sa5mrv0DVvXt6AGDG81ikAWuzV2+I2zSsNX9TQlfzGFD9Dq21Uqkv2u39bNrMXNPpVN27+2Vw2gUjXhkU2taDdD/C9nAUeY6qfm6x5v9U6pJrzTg/QnRFnIcxbu9nZ4qlZXY0b5UHywZ3C/N+l8WMf2aB6D5yq9R9duQx6FX18yXhWcfuxg+TzKlGHG5FKbO1h8ostNZc6WWsa2c8Hym9vZ9NnoNvjItg5DDJucVEuJFEDK2sZ/NA0+7LYR9p92fgx4e89OjeP2slt+fqervcJyE2h+OteaEi/DrIVvrX3zE/bn39/G/Mj8cNbfpi68Yvm2cwWxUd3ccQty6cSonWIlq2+5M/X2pMXUy2hzcRqZ2DiTJBEje0Mk2xXU4yA3dqe2K9Xu47RrnRdYe4ta6X726z6M3+mH6xd3t40/qr9kvjc+uHdmR5HF9sm95GMICfn6lvsvaKhH9RxF2l5UbXH+L6bpfzfdVRRIuYAIuIzdOk73Nsun+PvPyxpo6bjtn9gIOzaj+ZxQ6hEyg0uveS6w1xfbbSmkWj77w7h4qdXrOYqRu2C7u9x16/N3rfuPawgVPqHtRckxl3EbjE6IaHuE1MhmsfrDeXZMD12NqH6Zx/dGut30sfsA4xUWilrdj2ymoeKR05bdlLcdHdHo7aL52dmSBt9ielqpdpQD82tL2mriff67Y94HVQ3bkHd4/ZZZ99xq/46fKqrVh9tOKia3Pbq+RWmKesnJ5tHEpaxt5dTJpYo7Jz9WX3WvifzGx9Ne8Uz0uWFV3b3R2TW8tk7FHGDSXOk/BNrVnLiPnq2svflFiJXd8qnQu/r5FxUR1mouecC4ruI7dRP8FKgKdbQ6q9tSdLrBEYFGRfWoeZbn9CKdG1uZ1oRf96OQcen4irKT9Tl7vaeweJDZh0X1ER0XVzO91jFXPtO5ulg0piW029H7CI6CbIbQKfH3/8upc+P7luAIorwT7e/KNr99CvOrfGvIWOMttRmv33mUfXzh5lkFssX8q9uz8mPfq8bG7p4yGNlHvus43uY7WmlP2bWIo0pSLPDvN2dzLzrk1vqAGmkLJ/l2fVNa8NJLdIKfG4LMPobvYnES2iyC2S4WWuY9nclvweVpQgq+iSW5Qjsw4zuQUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAYGn+B9vGfWwKZW5kc3RyZWFtCmVuZG9iagoyIDAgb2JqCjw8L1Byb2NTZXQgWy9QREYgL1RleHQgL0ltYWdlQiAvSW1hZ2VDIC9JbWFnZUldCi9Gb250IDw8Ci9GMSA2IDAgUgovRjIgMTMgMCBSCi9GMyAyMCAwIFIKL0Y0IDI3IDAgUgo+PgovRXh0R1N0YXRlIDw8Ci9HUzEgNSAwIFIKPj4KL1hPYmplY3QgPDwKL0kxIDM0IDAgUgovSTIgMzUgMCBSCi9JMyAzNiAwIFIKL0k0IDM3IDAgUgo+Pgo+PgplbmRvYmoKMzggMCBvYmoKPDwKL1Byb2R1Y2VyICj+/wBtAFAARABGACAAOAAuADIALgA3KQovQ3JlYXRpb25EYXRlIChEOjIwMjYwNTExMjAzNDI3KzAyJzAwJykKL01vZERhdGUgKEQ6MjAyNjA1MTEyMDM0MjcrMDInMDAnKQo+PgplbmRvYmoKMzkgMCBvYmoKPDwKL1R5cGUgL0NhdGFsb2cKL1BhZ2VzIDEgMCBSCi9PcGVuQWN0aW9uIFszIDAgUiAvWFlaIG51bGwgbnVsbCAxXQovUGFnZUxheW91dCAvT25lQ29sdW1uCj4+CmVuZG9iagp4cmVmCjAgNDAKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAxNjg4IDAwMDAwIG4gCjAwMDAwNjM3NTIgMDAwMDAgbiAKMDAwMDAwMDAxNSAwMDAwMCBuIAowMDAwMDAwMjIzIDAwMDAwIG4gCjAwMDAwMDE3NzcgMDAwMDAgbiAKMDAwMDAwMTgzOCAwMDAwMCBuIAowMDAwMDAxOTg5IDAwMDAwIG4gCjAwMDAwMDI1MjggMDAwMDAgbiAKMDAwMDAwMjkyMyAwMDAwMCBuIAowMDAwMDAyOTkxIDAwMDAwIG4gCjAwMDAwMDMyOTkgMDAwMDAgbiAKMDAwMDAwMzY3NSAwMDAwMCBuIAowMDAwMDE1MDQ0IDAwMDAwIG4gCjAwMDAwMTUxOTcgMDAwMDAgbiAKMDAwMDAxNjE4OSAwMDAwMCBuIAowMDAwMDE2NTg1IDAwMDAwIG4gCjAwMDAwMTY2NTQgMDAwMDAgbiAKMDAwMDAxNjk2MSAwMDAwMCBuIAowMDAwMDE3MzY0IDAwMDAwIG4gCjAwMDAwMjkxNTIgMDAwMDAgbiAKMDAwMDAyOTMxMCAwMDAwMCBuIAowMDAwMDMwMTQwIDAwMDAwIG4gCjAwMDAwMzA1MzYgMDAwMDAgbiAKMDAwMDAzMDYwNSAwMDAwMCBuIAowMDAwMDMwOTIzIDAwMDAwIG4gCjAwMDAwMzEzMTIgMDAwMDAgbiAKMDAwMDA0MzU0MCAwMDAwMCBuIAowMDAwMDQzNjk5IDAwMDAwIG4gCjAwMDAwNDQyNDYgMDAwMDAgbiAKMDAwMDA0NDY0MiAwMDAwMCBuIAowMDAwMDQ0NzExIDAwMDAwIG4gCjAwMDAwNDUwMzAgMDAwMDAgbiAKMDAwMDA0NTQwNiAwMDAwMCBuIAowMDAwMDU2ODU2IDAwMDAwIG4gCjAwMDAwNTgyODUgMDAwMDAgbiAKMDAwMDA2MDc1MiAwMDAwMCBuIAowMDAwMDYxODg4IDAwMDAwIG4gCjAwMDAwNjM5NjAgMDAwMDAgbiAKMDAwMDA2NDA5MiAwMDAwMCBuIAp0cmFpbGVyCjw8Ci9TaXplIDQwCi9Sb290IDM5IDAgUgovSW5mbyAzOCAwIFIKL0lEIFs8ZGUxOTllMTI0MzIzMGRjYjllNWMyNDRkMTRjNzlkODQ+IDxkZTE5OWUxMjQzMjMwZGNiOWU1YzI0NGQxNGM3OWQ4ND5dCj4+CnN0YXJ0eHJlZgo2NDIwMgolJUVPRg==');

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
  `fuma` enum('No fumo','Un cigarrillo al dia','Media caja al dia','Una caja completa al dia') DEFAULT NULL,
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
(1, 3, 35, 'Casado', 'Masculino', 2, 'Bachillerato', 'Propia', NULL, NULL, NULL, NULL, NULL, 'Indefinido', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-24 02:45:36', NULL),
(2, 21, 18, '', 'Masculino', 0, '', '', 'Labor doméstica, Estudio', 'Menos de 1 año', 2, 'Padres', 'Mestizo', 'Indefinido', 'Operativo 07:00 am - 04:00 pm / Sábado medio día', 'Menor a 1 año', 'Hipertension arterial', 'No fumo', '', 'Semanal', 'Operativo', '2026-07-12 21:08:52', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar2_planillas`
--

CREATE TABLE `estandar2_planillas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `mes` int(2) NOT NULL,
  `anio` int(4) NOT NULL,
  `archivo_url` varchar(255) NOT NULL,
  `almacenamiento_archivo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `version_actual` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `valor_total` decimal(14,2) DEFAULT NULL,
  `cedulas_detectadas` int(10) UNSIGNED DEFAULT NULL,
  `trabajadores_esperados` int(10) UNSIGNED DEFAULT NULL,
  `riesgos_detectados` varchar(120) DEFAULT NULL,
  `nit_coincide` enum('SI','NO') DEFAULT NULL,
  `novedades_resumen` text DEFAULT NULL,
  `subido_por` int(11) NOT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar2_planilla_versiones`
--

CREATE TABLE `estandar2_planilla_versiones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `planilla_id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `almacenamiento_archivo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `numero_version` int(10) UNSIGNED NOT NULL,
  `archivo_original` varchar(255) NOT NULL,
  `valor_total` decimal(14,2) DEFAULT NULL,
  `cedulas_detectadas` int(10) UNSIGNED DEFAULT NULL,
  `trabajadores_esperados` int(10) UNSIGNED DEFAULT NULL,
  `riesgos_detectados` varchar(120) DEFAULT NULL,
  `nit_coincide` enum('SI','NO') DEFAULT NULL,
  `novedades_resumen` text DEFAULT NULL,
  `subido_por` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar4_actividades`
--

CREATE TABLE `estandar4_actividades` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `actividad_capacitacion_id` int(11) DEFAULT NULL,
  `tema` varchar(180) NOT NULL,
  `actividad` varchar(255) NOT NULL,
  `responsable` varchar(180) NOT NULL,
  `programacion_json` longtext NOT NULL,
  `observaciones` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estandar4_actividades`
--

INSERT INTO `estandar4_actividades` (`id`, `plan_id`, `actividad_capacitacion_id`, `tema`, `actividad`, `responsable`, `programacion_json`, `observaciones`, `orden`, `creado_en`, `actualizado_en`) VALUES
(4, 1, 10, 'Físico', 'Hola mundo', 'Toda la empresa', '{\"6\":{\"estado\":\"E\",\"fecha\":\"2026-06-10\"}}', 'Importada automáticamente desde el Estándar 3.', 4, '2026-06-19 16:41:36', '2026-06-20 02:40:12'),
(5, 1, 9, 'Biológico', 'Hola mundo', 'Toda la empresa', '{\"6\":{\"estado\":\"E\",\"fecha\":\"2026-06-09\"}}', 'Importada automáticamente desde el Estándar 3.', 5, '2026-06-19 16:41:39', '2026-06-20 02:40:12'),
(6, 1, 12, 'Biomecánicos', 'Hola mundocdscdsdcs', 'Toda la empresa', '{\"6\":{\"estado\":\"E\",\"fecha\":\"2026-06-10\"}}', 'Importada automáticamente desde el Estándar 3.', 6, '2026-06-19 16:41:43', '2026-06-20 02:40:12'),
(7, 1, 13, 'Biológico', 'Hola mundo', 'Toda la empresa', '{\"6\":{\"estado\":\"R\",\"fecha\":\"2026-06-19\"}}', 'Importada automáticamente desde el Estándar 3.', 7, '2026-06-20 02:42:26', '2026-06-20 02:43:48'),
(9, 4, NULL, 'Prueba 1 Plan de Trabajo', 'Prueba 1 Plan de Trabajo', 'Responsable SST', '{\"7\":{\"estado\":\"P\",\"fecha\":null}}', 'Prueba 1 Plan de Trabajo', 2, '2026-07-12 21:56:13', '2026-07-12 21:56:13'),
(12, 4, 14, 'Legal', 'Prueba 1', 'Trabajador Específico', '{\"7\":{\"estado\":\"P\",\"fecha\":\"2026-07-14\"}}', 'Importada automáticamente desde el Estándar 3.', 3, '2026-07-12 21:57:27', '2026-07-12 21:57:27'),
(13, 4, NULL, 'Prueba 1 Plan de Trabajo', 'Prueba 1 Plan de Trabajo', 'COPASST', '{\"8\":{\"estado\":\"P\",\"fecha\":null}}', 'Prueba 1 Plan de Trabajo', 4, '2026-07-12 21:58:09', '2026-07-12 21:58:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar4_planes`
--

CREATE TABLE `estandar4_planes` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `anio` smallint(5) UNSIGNED NOT NULL,
  `meta_cumplimiento` tinyint(3) UNSIGNED NOT NULL DEFAULT 85,
  `estado` enum('borrador','pendiente_firma','firmado') NOT NULL DEFAULT 'borrador',
  `sst_id` int(11) DEFAULT NULL,
  `representante_id` int(11) DEFAULT NULL,
  `firma_sst` longtext DEFAULT NULL,
  `firma_representante` longtext DEFAULT NULL,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_firma` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estandar4_planes`
--

INSERT INTO `estandar4_planes` (`id`, `empresa_id`, `anio`, `meta_cumplimiento`, `estado`, `sst_id`, `representante_id`, `firma_sst`, `firma_representante`, `fecha_envio`, `fecha_firma`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 2026, 80, 'borrador', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-14 03:19:49', '2026-06-20 02:33:05'),
(2, 1, 2025, 85, 'borrador', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 16:20:45', '2026-06-19 16:20:45'),
(3, 1, 2027, 85, 'borrador', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 16:20:54', '2026-06-19 16:20:54'),
(4, 17, 2026, 85, 'borrador', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-21 03:21:57', '2026-06-21 03:21:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar4_seguimientos`
--

CREATE TABLE `estandar4_seguimientos` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `periodo` varchar(120) NOT NULL,
  `analisis_resultado` text NOT NULL,
  `accion_propuesta` text NOT NULL,
  `responsable` varchar(180) NOT NULL,
  `fecha_max_ejecucion` date DEFAULT NULL,
  `fecha_seguimiento` date DEFAULT NULL,
  `responsable_seguimiento` varchar(180) DEFAULT NULL,
  `resultado_seguimiento` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_centros_medicos`
--

CREATE TABLE `estandar5_centros_medicos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `nit` varchar(40) NOT NULL,
  `direccion_principal` varchar(220) NOT NULL,
  `sedes_json` longtext DEFAULT NULL,
  `telefono` varchar(60) NOT NULL,
  `correo` varchar(160) NOT NULL,
  `licencia_funcionamiento_archivo` varchar(500) DEFAULT NULL,
  `licencia_sst_archivo` varchar(500) DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_evaluaciones_medicas`
--

CREATE TABLE `estandar5_evaluaciones_medicas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `trabajador_id` int(11) NOT NULL,
  `perfil_cargo_id` int(11) NOT NULL,
  `centro_medico_id` int(11) NOT NULL,
  `tipo_examen` enum('Ingreso','Periodico','Egreso','Post incapacidad','Reubicacion') NOT NULL DEFAULT 'Periodico',
  `estado` enum('solicitada','programada','realizada','cancelada') NOT NULL DEFAULT 'solicitada',
  `correo_destino` varchar(160) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_evaluaciones_medicas_soportes`
--

CREATE TABLE `estandar5_evaluaciones_medicas_soportes` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `trabajador_id` int(11) NOT NULL,
  `evaluacion_id` int(11) DEFAULT NULL,
  `perfil_cargo_id` int(11) DEFAULT NULL,
  `centro_medico_id` int(11) DEFAULT NULL,
  `nombre_trabajador` varchar(180) NOT NULL,
  `cedula` varchar(40) NOT NULL,
  `cargo` varchar(180) DEFAULT NULL,
  `tipo_examen` varchar(80) DEFAULT NULL,
  `resultado` varchar(120) DEFAULT NULL,
  `tipo_aptitud` varchar(120) DEFAULT NULL,
  `centro_medico` varchar(180) DEFAULT NULL,
  `fecha_expedicion` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `tiempo_para_programar` varchar(80) DEFAULT NULL,
  `dias_accion` int(11) DEFAULT NULL,
  `altura_nivel_curso` varchar(120) DEFAULT NULL,
  `altura_centro_capacitador` varchar(180) DEFAULT NULL,
  `altura_fecha_expedicion` date DEFAULT NULL,
  `altura_fecha_vencimiento` date DEFAULT NULL,
  `altura_programar` varchar(80) DEFAULT NULL,
  `altura_dias_accion` int(11) DEFAULT NULL,
  `confinado_nivel_curso` varchar(120) DEFAULT NULL,
  `confinado_centro_capacitador` varchar(180) DEFAULT NULL,
  `confinado_fecha_expedicion` date DEFAULT NULL,
  `confinado_fecha_vencimiento` date DEFAULT NULL,
  `confinado_programar` varchar(80) DEFAULT NULL,
  `confinado_dias_accion` int(11) DEFAULT NULL,
  `archivo_pdf` varchar(500) NOT NULL,
  `texto_extraido` longtext DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_historia_clinica_custodias`
--

CREATE TABLE `estandar5_historia_clinica_custodias` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `centro_medico_id` int(11) NOT NULL,
  `archivo_pdf` varchar(500) NOT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_renovacion` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `texto_extraido` longtext DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_perfiles_cargo`
--

CREATE TABLE `estandar5_perfiles_cargo` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `centro_medico_id` int(11) DEFAULT NULL,
  `nombre_cargo` varchar(180) NOT NULL,
  `tipo_proceso` varchar(140) NOT NULL,
  `tipo_operacion` enum('Administrativo','Operativo','Mixto') NOT NULL DEFAULT 'Mixto',
  `jefe_inmediato` varchar(180) NOT NULL,
  `tareas_json` longtext NOT NULL,
  `tareas_alto_riesgo_json` longtext DEFAULT NULL,
  `herramientas_json` longtext DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_procesos_perfil`
--

CREATE TABLE `estandar5_procesos_perfil` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre` varchar(140) NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar5_restricciones_recomendaciones`
--

CREATE TABLE `estandar5_restricciones_recomendaciones` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `trabajador_id` int(11) NOT NULL,
  `cargo` varchar(180) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `carta_fecha` date DEFAULT NULL,
  `fecha_examen` date DEFAULT NULL,
  `ips_nombre` varchar(180) DEFAULT NULL,
  `concepto_medico` varchar(180) DEFAULT NULL,
  `proyecto` varchar(180) DEFAULT NULL,
  `carta_pdf` varchar(500) DEFAULT NULL,
  `carta_firmada` enum('Si','No') NOT NULL DEFAULT 'No',
  `fecha_entrega_carta` date DEFAULT NULL,
  `recomendaciones_laborales` text DEFAULT NULL,
  `recomendaciones_generales` text DEFAULT NULL,
  `pve_json` longtext DEFAULT NULL,
  `tipo_restriccion` varchar(140) DEFAULT NULL,
  `restriccion` text DEFAULT NULL,
  `sst_fecha_programada` date DEFAULT NULL,
  `sst_fecha_real` date DEFAULT NULL,
  `sst_responsable` varchar(180) DEFAULT NULL,
  `sst_estado` varchar(180) DEFAULT NULL,
  `sst_historial` text DEFAULT NULL,
  `arl_fecha_real` date DEFAULT NULL,
  `arl_responsable` varchar(180) DEFAULT NULL,
  `arl_historial` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar6_ipvr_registros`
--

CREATE TABLE `estandar6_ipvr_registros` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `sitio_trabajo` varchar(180) DEFAULT NULL,
  `cuadro_basico` varchar(180) DEFAULT NULL,
  `proceso` varchar(180) NOT NULL,
  `actividad` varchar(220) NOT NULL,
  `tarea` text DEFAULT NULL,
  `zona_lugar` varchar(180) DEFAULT NULL,
  `clase_actividad` enum('Rutinaria','No Rutinaria') NOT NULL DEFAULT 'Rutinaria',
  `origen_actividad` enum('Interna','Externa') NOT NULL DEFAULT 'Interna',
  `cargos` text DEFAULT NULL,
  `directos` int(11) NOT NULL DEFAULT 0,
  `contratistas` int(11) NOT NULL DEFAULT 0,
  `visitantes` int(11) NOT NULL DEFAULT 0,
  `total_expuestos` int(11) NOT NULL DEFAULT 0,
  `peligro` varchar(80) NOT NULL,
  `clasificacion_peligro` varchar(180) NOT NULL,
  `metodologia_ref` varchar(180) DEFAULT NULL,
  `descripcion_peligro` text DEFAULT NULL,
  `categoria` enum('Salud','Seguridad','Propiedad_Proceso') NOT NULL DEFAULT 'Seguridad',
  `nivel_danio` varchar(120) NOT NULL,
  `nivel_deficiencia` int(11) NOT NULL DEFAULT 2,
  `valoracion_nd` varchar(80) DEFAULT NULL,
  `valoracion_nd_descripcion` text DEFAULT NULL,
  `nivel_exposicion` int(11) NOT NULL DEFAULT 1,
  `nivel_probabilidad` int(11) NOT NULL DEFAULT 0,
  `interpretacion_probabilidad` varchar(40) DEFAULT NULL,
  `nivel_consecuencia` int(11) NOT NULL DEFAULT 10,
  `nivel_riesgo` int(11) NOT NULL DEFAULT 0,
  `significado_riesgo` varchar(180) DEFAULT NULL,
  `aceptabilidad` varchar(120) DEFAULT NULL,
  `peor_consecuencia` varchar(220) DEFAULT NULL,
  `requisito_legal` enum('SI','NO') NOT NULL DEFAULT 'NO',
  `control_fuente` text DEFAULT NULL,
  `control_medio` text DEFAULT NULL,
  `control_persona` text DEFAULT NULL,
  `instrumento` text DEFAULT NULL,
  `nivel_deficiencia_residual` int(11) NOT NULL DEFAULT 2,
  `nivel_exposicion_residual` int(11) NOT NULL DEFAULT 1,
  `nivel_probabilidad_residual` int(11) NOT NULL DEFAULT 0,
  `interpretacion_probabilidad_residual` varchar(40) DEFAULT NULL,
  `nivel_consecuencia_residual` int(11) NOT NULL DEFAULT 10,
  `nivel_riesgo_residual` int(11) NOT NULL DEFAULT 0,
  `significado_riesgo_residual` varchar(180) DEFAULT NULL,
  `aceptabilidad_residual` varchar(120) DEFAULT NULL,
  `eliminacion` text DEFAULT NULL,
  `sustitucion` text DEFAULT NULL,
  `controles_ingenieria` text DEFAULT NULL,
  `senalizacion_advertencia` text DEFAULT NULL,
  `administrativos` text DEFAULT NULL,
  `epp` text DEFAULT NULL,
  `factor_reduccion` decimal(6,2) NOT NULL DEFAULT 0.00,
  `accidentes_anterior` int(11) DEFAULT NULL,
  `accidentes_actual` int(11) DEFAULT NULL,
  `eficacia_controles` enum('SI','NO','') NOT NULL DEFAULT '',
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar7_epp_entregas`
--

CREATE TABLE `estandar7_epp_entregas` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `trabajador_id` int(11) NOT NULL,
  `nombre_trabajador` varchar(180) NOT NULL,
  `cedula` varchar(40) NOT NULL,
  `cargo` varchar(180) DEFAULT NULL,
  `fecha_entrega` date NOT NULL,
  `items_json` longtext NOT NULL,
  `tipo_entrega` enum('Ordinaria','Desgaste','Perdida') NOT NULL DEFAULT 'Ordinaria',
  `entregado_por_tipo` varchar(60) NOT NULL,
  `entregado_por_usuario_id` int(11) DEFAULT NULL,
  `entregado_por_nombre` varchar(180) NOT NULL,
  `estado` enum('pendiente_firma','firmado') NOT NULL DEFAULT 'pendiente_firma',
  `firma_trabajador` longtext DEFAULT NULL,
  `firma_codigo_hash` varchar(255) DEFAULT NULL,
  `firma_codigo_expira` datetime DEFAULT NULL,
  `firma_codigo_validado_at` datetime DEFAULT NULL,
  `fecha_firma` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar7_mantenimiento_equipos`
--

CREATE TABLE `estandar7_mantenimiento_equipos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `codigo_interno` varchar(20) NOT NULL,
  `tipo_elemento` enum('Maquina','Equipo','Herramienta') NOT NULL DEFAULT 'Equipo',
  `nombre_elemento` varchar(180) NOT NULL,
  `marca` varchar(120) DEFAULT NULL,
  `serie` varchar(120) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `tipo_energia_json` longtext DEFAULT NULL,
  `ubicacion` varchar(180) DEFAULT NULL,
  `seccion` varchar(180) DEFAULT NULL,
  `tipo_combustible` varchar(120) DEFAULT NULL,
  `fabricante` varchar(180) DEFAULT NULL,
  `direccion` varchar(220) DEFAULT NULL,
  `telefono` varchar(80) DEFAULT NULL,
  `foto_equipo` varchar(255) DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar7_mantenimiento_registros`
--

CREATE TABLE `estandar7_mantenimiento_registros` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `localizacion_averia_json` longtext DEFAULT NULL,
  `orden_no` varchar(80) DEFAULT NULL,
  `mecanismo` varchar(180) DEFAULT NULL,
  `tipo_mantenimiento` tinyint(4) NOT NULL DEFAULT 1,
  `descripcion_trabajo` text DEFAULT NULL,
  `horas_maquina_parada` decimal(8,2) DEFAULT NULL,
  `costo_mano_obra` decimal(14,2) NOT NULL DEFAULT 0.00,
  `costo_repuestos` decimal(14,2) NOT NULL DEFAULT 0.00,
  `costo_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `quien_realizo` varchar(180) DEFAULT NULL,
  `quien_recibio` varchar(180) DEFAULT NULL,
  `soporte_mantenimiento` varchar(255) DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar7_programas_documentales`
--

CREATE TABLE `estandar7_programas_documentales` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `programa_slug` varchar(80) NOT NULL,
  `programa_nombre` varchar(220) NOT NULL,
  `contenido_json` longtext NOT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar7_recursos_analisis_consumo`
--

CREATE TABLE `estandar7_recursos_analisis_consumo` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `anio` smallint(6) NOT NULL,
  `trimestre` tinyint(4) NOT NULL,
  `seguimiento` text DEFAULT NULL,
  `accion` text DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estandar7_recursos_presupuesto`
--

CREATE TABLE `estandar7_recursos_presupuesto` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `anio` smallint(6) NOT NULL,
  `categoria_slug` varchar(80) NOT NULL,
  `categoria_nombre` varchar(180) NOT NULL,
  `item_slug` varchar(100) NOT NULL,
  `item_nombre` varchar(220) NOT NULL,
  `periodo` tinyint(4) NOT NULL,
  `presupuestado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `ejecutado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `creado_por` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(3, 1, 'Contabilidad', '2026-04-02 04:55:42'),
(4, 17, 'COPASST', '2026-07-12 21:19:34'),
(5, 17, 'COCOLA', '2026-07-12 21:19:43'),
(6, 17, 'BRIGADA', '2026-07-12 21:19:50');

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
(18, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-08 20:18:32'),
(19, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-08 22:12:14'),
(20, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 01:54:05'),
(21, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-09 01:55:05'),
(22, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 02:18:50'),
(23, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 14:23:54'),
(24, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-09 15:02:09'),
(25, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 22:28:17'),
(26, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-11 18:25:40'),
(27, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-11 18:25:49'),
(28, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-11 18:26:13'),
(29, 1, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-11 18:38:32'),
(30, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-27 22:01:51'),
(31, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-01 19:07:05'),
(32, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-10 02:28:06'),
(33, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-10 02:32:07'),
(34, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-10 03:47:59'),
(35, 3, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-10 03:49:34'),
(36, 3, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-10 03:50:14'),
(37, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-10 03:50:32'),
(38, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-11 01:12:18'),
(39, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-11 01:12:40'),
(40, 3, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-11 01:18:07'),
(41, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-11 01:18:56'),
(42, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-11 02:04:53'),
(43, 3, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-11 02:05:22'),
(44, 3, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.1.45', '2026-06-11 02:20:58'),
(45, 3, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-11 02:41:15'),
(46, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-11 02:41:41'),
(47, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-11 02:44:44'),
(48, 3, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-11 02:45:03'),
(49, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-14 03:25:29'),
(50, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-14 03:30:59'),
(51, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-14 03:31:15'),
(52, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-19 16:03:57'),
(53, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-19 16:28:57'),
(54, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-19 16:31:57'),
(55, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-20 01:43:31'),
(56, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-20 03:16:08'),
(57, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-20 03:41:08'),
(58, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-21 03:18:37'),
(59, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-23 00:49:04'),
(60, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-06-23 00:49:18'),
(61, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-23 00:59:41'),
(62, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-23 01:44:00'),
(63, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-23 23:00:30'),
(64, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-29 13:08:10'),
(65, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-29 21:52:42'),
(66, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-06-30 21:16:55'),
(67, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-05 03:06:46'),
(68, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-05 03:15:15'),
(69, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-05 03:15:47'),
(70, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '127.0.0.1', '2026-07-05 22:22:12'),
(71, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-09 21:14:20'),
(72, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-09 21:45:00'),
(73, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '127.0.0.1', '2026-07-11 22:41:55'),
(74, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-12 13:02:50'),
(75, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-12 13:30:18'),
(76, 21, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-12 21:14:50'),
(77, 21, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-12 21:16:24'),
(78, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-12 21:16:57'),
(79, 19, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-12 21:25:28'),
(80, 21, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-12 21:26:09'),
(81, 21, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-12 21:27:15'),
(82, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-12 21:27:42'),
(83, 19, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-13 03:35:54'),
(84, 21, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-13 03:36:47'),
(85, 21, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-13 03:37:43'),
(86, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-13 03:38:48'),
(87, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-13 03:41:32'),
(88, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-13 03:42:11'),
(89, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-13 20:54:50'),
(90, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-14 22:53:10'),
(91, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-14 23:08:23'),
(92, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-15 12:17:25'),
(93, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-15 22:38:43'),
(94, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 04:40:47'),
(95, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 16:14:56'),
(96, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 16:45:55'),
(97, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 16:51:13'),
(98, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 17:10:40'),
(99, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 18:14:43'),
(100, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 18:15:01'),
(101, 19, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 18:15:49'),
(102, 21, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 18:16:18'),
(103, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 18:21:19'),
(104, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 18:22:27'),
(105, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 19:08:34'),
(106, 21, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 19:24:45'),
(107, 1, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 22:07:34'),
(108, 1, 'LOGOUT', 'Cierre de sesión', '::1', '2026-07-16 23:01:27'),
(109, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-07-16 23:02:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_financieros`
--

CREATE TABLE `movimientos_financieros` (
  `id` int(11) NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `iva` decimal(10,2) DEFAULT 0.00,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `comprobante` varchar(100) DEFAULT NULL,
  `fecha` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `referencia_tipo` varchar(80) DEFAULT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `titulo`, `mensaje`, `enlace`, `referencia_tipo`, `referencia_id`, `leida`, `fecha_creacion`) VALUES
(1, 1, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', NULL, NULL, 1, '2026-05-09 23:01:14'),
(2, 5, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', NULL, NULL, 0, '2026-05-09 23:01:17'),
(3, 6, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', NULL, NULL, 0, '2026-05-09 23:01:20'),
(4, 7, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', NULL, NULL, 0, '2026-05-09 23:01:22'),
(5, 2, 'Acta Aprobada y Firmada', 'El Representante Legal (Esteban Reuto) ha firmado y legalizado tu acta.', 'estandar1.php', NULL, NULL, 1, '2026-05-11 18:26:22'),
(6, 19, 'Tarea asignada del Plan de Trabajo', 'Se te asignó la actividad \"Prueba 1 Plan de Trabajo\" del tema \"Prueba 1 Plan de Trabajo\" para julio de 2026. Responsable asignado: Responsable SST.', 'estandar4.php?anio=2026', 'estandar4_actividad', 9, 0, '2026-07-12 21:56:13'),
(7, 21, 'Tarea asignada del Plan de Trabajo', 'Se te asignó la actividad \"Prueba 1 Plan de Trabajo\" del tema \"Prueba 1 Plan de Trabajo\" para agosto de 2026. Responsable asignado: COPASST.', 'estandar4.php?anio=2026', 'estandar4_actividad', 13, 0, '2026-07-12 21:58:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos_suscripciones`
--

CREATE TABLE `pagos_suscripciones` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `transaccion_wompi` varchar(100) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `plan_nombre` varchar(100) NOT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'APPROVED',
  `fecha_pago` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos_suscripciones`
--

INSERT INTO `pagos_suscripciones` (`id`, `empresa_id`, `transaccion_wompi`, `monto`, `plan_nombre`, `estado`, `fecha_pago`) VALUES
(1, 1, 'TX-MIGRADA-1', 50000.00, 'Básico', 'APPROVED', '2026-03-24 02:45:36'),
(2, 2, 'TX-MIGRADA-2', 50000.00, 'Básico', 'APPROVED', '2026-05-08 21:52:19'),
(3, 3, 'TX-MIGRADA-3', 120000.00, 'Pro SG-SST', 'APPROVED', '2026-05-08 22:19:44'),
(4, 4, 'TX-MIGRADA-4', 250000.00, 'Enterprise', 'APPROVED', '2026-05-09 01:09:36'),
(5, 5, 'TX-MIGRADA-5', 250000.00, 'Enterprise', 'APPROVED', '2026-05-09 01:50:46'),
(6, 17, '12058644-1782011067-61778', 2443903.00, 'EMPRESA PEM', 'APPROVED', '2026-06-21 03:12:45');

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
  `almacenamiento_gb` decimal(10,2) NOT NULL DEFAULT 0.00,
  `popular` tinyint(1) DEFAULT 0,
  `clase_btn` varchar(50) DEFAULT 'btn-outline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `nombre`, `precio_normal`, `precio_descuento`, `trabajadores`, `almacenamiento_gb`, `popular`, `clase_btn`) VALUES
(1, 'EMPRESA PEM', 2500000.00, 0.00, 12, 30.00, 0, 'btn-outline'),
(2, 'Empresas MEM', 3000000.00, 0.00, 52, 100.00, 1, 'btn-solid'),
(3, 'EMPRESAS GEM', 4000000.00, 0.00, 100, 200.00, 0, 'btn-outline');

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

--
-- Volcado de datos para la tabla `plan_caracteristicas`
--

INSERT INTO `plan_caracteristicas` (`id`, `plan_id`, `texto`, `incluido`) VALUES
(205, 1, 'Alcance de 1 a 7 Estandares Normativos Res. 0312', 1),
(206, 1, 'Alcance de 1 a 22 Estandares Normativos Res. 0313', 0),
(207, 1, 'Alcance de 1 a 60 Estandares Normativos Res. 0314', 0),
(208, 1, '12 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 10 Colaboradores)', 1),
(209, 1, '52 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 50 Colaboradores)', 0),
(210, 1, '102 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 100 Colaboradores)', 0),
(211, 1, 'Firmas Digitales en tiempo real', 1),
(212, 1, 'Notifocaiones Digitales en tiempo real', 1),
(213, 1, 'Recopilación y Bases de datos docuemntales 30 GB', 1),
(214, 1, 'Planificación de eventos en tiempo real con conexción a calendarios', 1),
(215, 1, 'Soporte tecnico y acompañamiento', 1),
(216, 1, 'Networking de proveedores y prestadores de servicio asociado a la actividad económica', 1),
(217, 1, 'Creación de nuevos usuarios asociados a los requerimientos de los estándares', 1),
(218, 2, 'Alcance de 1 a 7 Estandares Normativos Res. 0312', 0),
(219, 2, 'Alcance de 1 a 22 Estandares Normativos Res. 0313', 1),
(220, 2, 'Alcance de 1 a 60 Estandares Normativos Res. 0314', 0),
(221, 2, '12 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 10 Colaboradores)', 0),
(222, 2, '52 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 50 Colaboradores)', 1),
(223, 2, '102 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 100 Colaboradores)', 0),
(224, 2, 'Firmas Digitales en tiempo real', 1),
(225, 2, 'Notifocaiones Digitales en tiempo real', 1),
(226, 2, 'Recopilación y Bases de datos docuemntales 100 GB', 1),
(227, 2, 'Planificación de eventos en tiempo real con conexción a calendarios', 1),
(228, 2, 'Soporte tecnico y acompañamiento', 1),
(229, 2, 'Networking de proveedores y prestadores de servicio asociado a la actividad económica', 1),
(230, 2, 'Creación de nuevos usuarios asociados a los requerimientos de los estándares', 1),
(231, 3, 'Alcance de 1 a 7 Estandares Normativos Res. 0312', 0),
(232, 3, 'Alcance de 1 a 22 Estandares Normativos Res. 0313', 0),
(233, 3, 'Alcance de 1 a 60 Estandares Normativos Res. 0314', 1),
(234, 3, '12 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 10 Colaboradores)', 0),
(235, 3, '52 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 50 Colaboradores)', 0),
(236, 3, '102 Usuarios( 1 Representante Legal, 1 Responsable del SG-SST, 100 Colaboradores)', 1),
(237, 3, 'Firmas Digitales en tiempo real', 1),
(238, 3, 'Notifocaiones Digitales en tiempo real', 1),
(239, 3, 'Recopilación y Bases de datos docuemntales 200 GB', 1),
(240, 3, 'Planificación de eventos en tiempo real con conexción a calendarios', 1),
(241, 3, 'Soporte tecnico y acompañamiento', 1),
(242, 3, 'Networking de proveedores y prestadores de servicio asociado a la actividad económica', 1),
(243, 3, 'Creación de nuevos usuarios asociados a los requerimientos de los estándares', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas_frecuentes`
--

CREATE TABLE `preguntas_frecuentes` (
  `id` int(11) NOT NULL,
  `pregunta` varchar(255) NOT NULL,
  `respuesta` text NOT NULL,
  `orden` int(11) DEFAULT 0,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `preguntas_frecuentes`
--

INSERT INTO `preguntas_frecuentes` (`id`, `pregunta`, `respuesta`, `orden`, `estado`, `fecha_creacion`) VALUES
(1, '¿Qué es PreventWork SG-SST Pro?', 'Es una plataforma tecnológica (SaaS) diseñada para facilitar la administración, control y ejecución del Sistema de Gestión de Seguridad y Salud en el Trabajo bajo la normativa colombiana (Decreto 1072 y Res. 0312).', 1, 'activo', '2026-05-12 21:00:17'),
(2, '¿Son válidas las firmas digitales recolectadas en la plataforma?', 'Sí. Las firmas recolectadas mediante trazos en pantalla (celular o PC) tienen plena validez jurídica y probatoria bajo la <strong>Ley 527 de 1999</strong> en Colombia.', 2, 'activo', '2026-05-12 21:00:17'),
(3, '¿Cómo funciona el pago de la suscripción?', 'Todos los pagos se procesan de forma 100% segura a través de la pasarela de pagos <strong>Wompi</strong> (Bancolombia). Puedes elegir facturación mensual o anual según la capacidad de tu empresa.', 3, 'activo', '2026-05-12 21:00:17'),
(4, '¿Qué pasa si mi empresa supera el límite de trabajadores del plan?', 'Si tu empresa crece y necesitas registrar más trabajadores de los que permite tu plan actual, el sistema te notificará para que realices un <em>upgrade</em> (mejora de plan) pagando únicamente el excedente correspondiente.', 4, 'activo', '2026-05-12 21:00:17'),
(5, '¿Quién es el dueño de la información que se sube a la plataforma?', 'Tu empresa. Según la Ley 1581 de Habeas Data, la Empresa contratante actúa como <strong>Responsable</strong> de los datos. PreventWork actúa únicamente como proveedor tecnológico (Encargado) asegurando que tus datos estén encriptados y respaldados.', 5, 'activo', '2026-05-12 21:00:17'),
(6, 'Quien ocdmosdvc fsdvfs', 'Pendiente de revisión y respuesta por el equipo de PreventWork.', 0, 'inactivo', '2026-05-12 21:04:49'),
(7, 'Quien ocdmosdvc fsdvfs', 'Pendiente de revisión y respuesta por el equipo de PreventWork.', 0, 'inactivo', '2026-05-12 21:06:27'),
(8, 'Quien ocdmosdvc fsdvfs', 'Pendiente de revisión y respuesta por el equipo de PreventWork.', 0, 'inactivo', '2026-05-12 21:08:48'),
(9, 'Holaaaa', 'Pendiente de revisión y respuesta por el equipo de PreventWork.', 0, 'inactivo', '2026-05-12 21:09:00');

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
(15, 2, 'a861c5c3a7e03a8b23237e5391a259337c14017cedfcef9fdfc3dd142ac69ef9', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 17:22:37', '2026-05-09 08:22:37', 0),
(16, 2, 'a94e4e7aae8c080d63bebf6d14864157718908ba487c96769d97d0645d3c38de', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-08 22:11:22', '2026-05-09 13:11:22', 0),
(17, 7, '0bede366c286a82a3a01a05a26f77a3215bf7bc50ef301fc402a93360afa91ef', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 01:54:05', '2026-05-09 16:54:05', 0),
(18, 2, '1264f8e9f71ecfa78bd525a4d2b80d7a7f7966d638ed753baebbd93c9e73de92', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 02:18:50', '2026-05-09 17:18:50', 1),
(19, 2, 'f34fd4a7941f794a34653d0506140c48e81467bf1a7a3ca3f5ed525e0dbabda9', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 14:23:54', '2026-05-10 05:23:54', 1),
(20, 2, '7326670ec14bb796ce1575aebc761cc495240234f48efadce1dcebdbff2f0be6', NULL, NULL, '192.168.18.23', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_4_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/148.0.7778.100 Mobile/15E148 Safari/604.1', '2026-05-09 15:02:09', '2026-05-10 06:02:09', 1),
(21, 2, '8a5e910433dec47095911812218e1230a69e4e00ef506de5c38a015d028ac64d', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 22:28:17', '2026-05-10 13:28:17', 1),
(22, 2, 'e4129d3643b6dc2610f4b2faef06694f996c5c05e7f3241d0479283d51fcc670', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-11 18:25:40', '2026-05-12 09:25:40', 0),
(23, 1, '94bca10d132c56bff8f7566a72b06370e18d6b27e1554473c35ff0f6c38e4a55', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-11 18:26:13', '2026-05-12 09:26:13', 0),
(24, 2, 'c87770c0ce3195695271d559b8c5d15453f36a06126de41a350c8ae728393566', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 22:01:51', '2026-05-28 13:01:51', 1),
(25, 2, 'b443a7726e5ebb099eaad007d23e1365a92943d3bb0dd2ecd2582de945e37a08', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-01 19:07:05', '2026-06-02 10:07:05', 1),
(26, 2, '2a17bd2bb3a25742dbc2f7a1ad50b66956a31309bd3c7fb8b78d03bb7a759e5a', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 02:28:06', '2026-06-10 17:28:06', 0),
(27, 2, 'ae2c504ec85b403f43dd9f1142b9140e0cc0fcd243302a0e61124732d2dcc1e3', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-10 02:32:07', '2026-06-10 17:32:07', 1),
(34, 3, 'c985a3cdf20fa1b35f00c368724d7e8730f1b09dce2255b6e4e94a45f0da2301', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 03:49:34', '2026-06-10 18:49:34', 0),
(35, 2, '709e7ede6ee22042859ebc0ce41c9fe28aa29f64bedc95654aa7c67c796ed05e', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-10 03:50:32', '2026-06-10 18:50:32', 1),
(36, 3, 'bef87a3b8229e06d2a6231fa2dcadb919c0dc2000345efffb0942910a90e746a', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-11 01:18:07', '2026-06-11 16:18:07', 1),
(37, 2, 'aedb7e8dd81f3f2c976ef0254eb123294dc0e2022ae45a448e1ca7e4ab7a4667', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 01:18:56', '2026-06-11 16:18:56', 0),
(39, 3, '0e28ef6e73290e091c8995527f5e136ffc3aef98f2d7f2abd1463f2d773e5171', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 02:05:22', '2026-06-11 17:05:22', 0),
(40, 3, '3821c9acfbca072b84e586f4f8924615893d891253c3bad6e24f5aaa5bdbf969', NULL, NULL, '192.168.1.45', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_6_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/149.0.7827.45 Mobile/15E148 Safari/604.1', '2026-06-11 02:20:58', '2026-06-11 17:20:58', 1),
(41, 2, 'a5dbf84c6882e4d3a752af055bd0a0b2c2b4041c9e8c0e901d78d1bc0daa2cc6', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 02:41:41', '2026-06-11 17:41:41', 0),
(42, 3, '07d428b2d135c9de5204a39df3bf9e53c8438ea2791147f2b381684221699129', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-06-11 02:45:03', '2026-06-11 17:45:03', 1),
(43, 2, 'd704f2584be6cf668861b64b7ce0cd15042301b236f34a2e53b1a9607e3b2c11', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 03:19:49', '2026-06-14 12:19:49', 1),
(44, 2, '1064aa3a1821e8cd8ca207edeba2855976a336ac9ae8290c77c50d7482402b6c', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 03:25:29', '2026-06-14 18:25:29', 0),
(45, 2, 'dc31e585fafe29f6fe6de8fa2c58ecc09b0fc87b7e4a870c3c40a22a7201f312', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-14 03:31:15', '2026-06-14 18:31:15', 1),
(46, 2, 'c2efb813f622868974b7172722f30f7830aabad0644364b6570d48834dae373e', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 16:03:57', '2026-06-20 07:03:57', 0),
(47, 2, '95850f54a7f8eea7828ac0a7d41feddc7ba2c1f027697ac08fa4b7f42074e447', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 16:21:18', '2026-06-20 00:21:18', 1),
(48, 2, '1c1b329b46356c909f8de211b7dffe4f3e01bb708e9de471c2e385d5f0eaa2e4', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 16:31:57', '2026-06-20 07:31:57', 1),
(49, 2, '1815286d9689d837333e84f8fba97a401dafd80dc7835e66d701b132f7e8842d', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 01:43:31', '2026-06-20 16:43:31', 1),
(50, 2, '33eef6e536b43692bf522f3be8934c5b9cb0cf0fd056761e1d7198544fcc01b4', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:16:08', '2026-06-20 18:16:08', 0),
(51, 2, '46edf9fc81f1a440b4044d7e540e24ad673ded81a2d895c685b8ef3a5f1b9a77', NULL, NULL, '127.0.0.1', 'Codex Browser Review', '2026-06-20 05:29:10', '2026-06-20 20:29:10', 0),
(52, 7, 'ce713b57b8ff532505d98a214fb62952bfcf7139d1391a196d9088f55b2efee4', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:18:37', '2026-06-21 18:18:37', 1),
(53, 7, '131415a549439dc9fb825bac16b4f27061139746ddbecb1d0a8025421b3ebcf8', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 00:49:04', '2026-06-23 15:49:04', 0),
(54, 19, '8c67e356cdda5ef83a393a94712c760544932cc62aa8098fcc31ec7cd55506bf', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 00:59:41', '2026-06-23 15:59:41', 1),
(55, 7, '3e6bc90c40d84619f9e10d2c2067bb503df04464b57a45c79939a40f7004cae0', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 01:44:00', '2026-06-23 16:44:00', 1),
(56, 7, 'da50b25b0f86a4728d42630e2b356e1a00a655424061c445bc77fa58fa608faa', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 23:00:30', '2026-06-24 14:00:30', 1),
(57, 19, '5942eb29b3234a70efd133fb0fe80aaa4cfc5579d687120d9cdaf0f469fe1020', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 13:08:10', '2026-06-30 04:08:10', 1),
(58, 19, '0a0703e6b9ff7d298a40c831bb1d413a7dee58c2eae8c74d6358c562a0f1169b', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 13:08:10', '2026-07-29 20:08:10', 1),
(59, 19, 'd33252a0cf26fa696a0b196ff09ff90be2a9dc366c46192306b688b8b44fa2b6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 21:52:42', '2026-06-30 12:52:42', 1),
(60, 19, 'be3e9e34f2d09b2560f8cf33297978c10998d24ccda8ce361e1ce886635a9fbd', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-29 21:52:42', '2026-07-30 04:52:42', 1),
(61, 19, '109f0657f8e1757a2dc10830ddaab6fe606a577842a073b913f02bbc3fefeb97', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 21:16:55', '2026-07-01 12:16:55', 1),
(62, 19, '0f84b78249b419616b61c8724ce5f8b6cf672daac1301977e0dc212903c12433', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-30 21:16:55', '2026-07-31 04:16:55', 1),
(63, 7, '81e5ccfeb679b1b280fe82e63c89d2a6cb0476a498ae8cb452027e12615a22d3', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-05 03:06:46', '2026-07-05 18:06:46', 0),
(64, 19, '7a2b387bc5d021b9f1606a77912318dc83b03e870e7b89af78803ee2adf56cd2', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-05 03:15:47', '2026-07-05 18:15:47', 1),
(65, 19, '20a6be07899cd6757aa5450a2c358aff6bf389a7aa47c57fd8a21af66f87cec1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-05 03:15:47', '2026-08-04 10:15:47', 1),
(66, 19, '19f2e1c73cb96fe6d59c3d0721ae59842841babda3a18adf8d1be9f2211000e7', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-05 22:22:12', '2026-07-06 13:22:12', 1),
(67, 19, 'c0ec980682dcad772372bf682053f4cdf7a962c5abd322d8a995e859eb5f952d', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-05 22:22:12', '2026-08-05 05:22:12', 1),
(68, 19, 'ba26ad8faf8fade5e75fa54929d1b60efd45bfa7475939baa66f07bcc7046a85', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-09 21:14:20', '2026-07-10 12:14:20', 1),
(69, 19, '6a9046515da20ad49c57169622ede64ec2691cac47ed2d79dcb79d2d08d01048', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-09 21:14:20', '2026-08-09 04:14:20', 1),
(70, 19, '9b981972549d9bf4eb84f4006f6ee2f2a09a6f9246b47ed09b92c8aaf8c9f718', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-07-09 21:45:00', '2026-07-10 12:45:00', 1),
(71, 7, 'a2880fc9e3a59c80f380a5898620ca6355020fc288885374bf8e984973d1a3d8', NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-11 22:41:55', '2026-07-12 13:41:55', 1),
(72, 7, 'bf53fbd3e69aad0b292e228848da8a83765e1bf277280856b763f3d327f6d055', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-12 13:02:50', '2026-07-13 04:02:50', 0),
(74, 21, 'ab8b658b9bfc63479f1708eb42d69d0bb6cb54abc707bd52bc1003b0f1e92bc9', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-12 21:14:50', '2026-07-13 12:14:50', 0),
(75, 19, '49266050d76bf592d1a83c3b95646f4a3432aea692222a73dcd39c2a033b63cd', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-12 21:16:57', '2026-07-13 12:16:57', 0),
(76, 21, 'a93a4733b4033a6e99dc06986588031a918c63b57d7c1fcfc72ef3b2c9b9c050', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-12 21:26:09', '2026-07-13 12:26:09', 0),
(77, 19, 'e887cd0811bb6e28cfc7a228d983c22e08b5f9a7f728e928b430d7ab3ae5d03b', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-12 21:27:42', '2026-07-13 12:27:42', 0),
(78, 21, '1e435442c3ef68c02f76e0b55439a54de64853f3cf974b2155ab66c69780c48a', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 03:36:47', '2026-07-13 18:36:47', 0),
(79, 7, '0fe7eba35921f937966b46cea6ace54014e425ad50f4538ef153561ed77e65da', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 03:38:48', '2026-07-13 18:38:48', 0),
(80, 19, 'cbccb207d0fdf751e83fdcc8b63f990a5e1c336d11e710bc8fe55f04b10760c1', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 03:42:11', '2026-07-13 18:42:11', 1),
(81, 19, 'b99849a839e7b6257e52b161b2cfb204d71f7901dd4368eddbed50b63b8e782c', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 20:54:50', '2026-07-14 11:54:50', 1),
(82, 19, '099181233cf53b73250f822ceffae7a47cb9ff43edfb85ce4515bb198b58a517', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-13 20:54:50', '2026-08-13 03:54:50', 1),
(83, 2, '0c91cd1d50bfffad05d3bf14d7bb31aeeca8a7db40234b1801ca73f2b7c028dd', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 22:53:10', '2026-07-15 13:53:10', 1),
(84, 2, 'aaa2e8726d66c28528ba40a63197d75739602be9837c10bdf90d31254c82c15c', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-14 23:08:23', '2026-07-15 14:08:23', 1),
(85, 2, '65411dcce7df21bbba33c776cf20adbc72acc7dcdc41ee7a03bfd489f371e9d7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 12:17:25', '2026-07-16 03:17:25', 1),
(86, 2, '5f9ed9e348eb312831eb23e2d98492ffd70638718d6e8bde9161b805d6d83ed6', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-15 22:38:43', '2026-07-16 13:38:43', 0),
(87, 2, '86f2fd11da61ca98adb5717f2259a4d6159fb010898fda18e3e4994bbfa7115d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-16 16:14:56', '2026-07-17 07:14:56', 0),
(92, 2, 'c77714bedbe15af22b4b4690e34d836abbf64161cab12612d384e14a358f197c', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-16 16:51:13', '2026-07-17 07:51:13', 1),
(93, 2, 'b46d70c0c2ef4e436e1ba71bbbf9ded6ad864a32bdeeae628939e8339565806d', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-16 17:10:40', '2026-07-17 08:10:40', 0),
(103, 7, 'c2c98eee184d697b68d3371b946909122ca2f08f52f83da93e9d89a88c1bfdb0', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-16 18:14:43', '2026-07-17 09:14:43', 0),
(104, 19, '94f169d48efaf6fe489b8bb734d2f60cb96bc6894907560c5af3f4280a3a314b', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-16 18:15:49', '2026-07-17 09:15:49', 1),
(105, 21, '0db22ed260261a66f166a5851b931baebeb6f5322feadadb4c1549703567ce8a', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-16 18:16:18', '2026-07-17 09:16:18', 0),
(106, 7, 'cefff9cbef422966dc7f25772c48f39ea61c0304b9e6598aff825b2698576bde', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-16 18:22:27', '2026-07-17 09:22:27', 0),
(107, 1, '665f35f3492987302bc88670a7f6745db1ba0d89ad32286e6f75ea2c22b1ce53', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-16 22:07:34', '2026-07-17 13:07:34', 0),
(117, 2, '244cd1edda7938cc315ac80204bb611b736c1b069e05387754014c6ec868f0b7', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36 Edg/150.0.0.0', '2026-07-16 23:02:44', '2026-07-17 14:02:44', 1);

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
  `empresa_nombre` varchar(150) DEFAULT NULL,
  `empresa_nit` varchar(50) DEFAULT NULL,
  `empresa_clase_riesgo` varchar(10) DEFAULT NULL,
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

INSERT INTO `solicitudes_empresas` (`id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `empresa_nombre`, `empresa_nit`, `empresa_clase_riesgo`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `estado`, `fecha_creacion`, `plan_id`, `trabajadores_extra`) VALUES
(1, 'Constructora Vertix S.A.S', 'Reuto', '900111222', 'estebanreuto4@gmail.com', '3001112233', NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, 'aprobada', '2026-03-24 02:45:36', 1, 0),
(2, 'Esteban', 'Reuto', '1116856979', 'estebanreuto27@gmail.com', '3012994599', NULL, NULL, NULL, 'Cra 3 # 13A - 55', 'Tame - Arauca', '', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAArUAAACWCAYAAADAB3DwAAAQAElEQVR4AeydS8scRRfH6+RNNCAqGHNBiCYoaCKCxlUgwaULTT6C4ELINxBcuM/SnZCFkI8QzMJ1EsRNIkgCgmBUCHniJRgJSC76+u/JmaeeTs9Mz0xfqqt/IWeqL1V1qn41z+n/1FT3bPuXfxCAAAQgAAEIQAACEBg4gW2BfxCAAAQgsIAApyEAAQhAIHUCiNrUR4j2QQACEIAABCAAgSEQ6LmNiNqeBwD3EIAABCAAAQhAAALrE0DUrs+QGiAAgfYJ4AECEIAABCAwlwCidi4eTkIAAhCAAAQgAIGhEBh3OxG14x5/eg8BCEAAAhCAAASyIICozWIY6QQE2ieABwhAAAIQgEDKBBC1KY8ObYMABCAAAQhAYEgEaGuPBBC1PcLHNQQgAAEIQAACEIBAMwQQtc1wpBYItE8ADxCAAAQgAAEIzCSAqJ2JhhMQgAAEIAABCAyNAO0dLwFE7XjHnp5DAAIQgAAEIACBbAggarMZSjrSPgE8QAACEIAABCCQKgFEbaojQ7sgAAEIQAACQyRAmyHQEwFEbU/gcQsBCEAAAhCAAAQg0BwBRG1zLKmpfQJ4gAAEIAABCEAAApUEELWVWDgIAQhAAAIQGCoB2g2BcRJA1I5z3Ok1BCAAAQhAAAIQyIoAojar4Wy/M3iAAAQgAAEIQAACKRJA1KY4KrQJAhCAAASGTIC2d0zg+eefD7F17B53iRBA1CYyECk348CBAyk3j7ZBAAIQgMBICezatSs899xz4Z9//tliI8Ux+m4jaof2FuihvXfu3AkvvfRSD55xCQEIQAACENhKQDOyErKyf//9d+vJR3sSu482SUZEAFE7osFep6t//fVXYMZ2HYKUhQAEuiSAr/wIuJjVrGzcu23btoU//vijMG3r3Cyxq3NYvgQQtfmObeM904xt45VSIQQgAAEIQGAOgUVi9rfffpuWjrdVbnqCjVEQQNQuPczjK2Bm4+s0PYYABCAAgV4JaAmBlhjEM7NmVszIamY2FrBxQ322Ni4Xn2c7XwKI2nzHdq2e6ROum9mmqPVjOaRrAaIwBCAwnwBnIbAiARez8RICCVUJ2d9//33FWik2BgKI2kxHuSw6FSRi06ffeaZPuLE5pvjY0Lfn9T9m5dvO1FmQQgACEIBAcwQUYxWXq8TsrFnZKu/L5K0qz7HhEhiiqB0u7QZbvrGxMX0mn0SXAkFsZcGpIBFbg03JsqqYlW87U3EW8yw7TqcgAAEI9EBAMVUx1l37zCwC1YmQ1iGAqK1DKZE8t27dKoSsRNWhQ4emz+ST6FrURDMLZpumgBGbvtaZZ16/2eZ6pnn5h3gu5mG2ycpssu0MlIq5xkGBWPsYBNIjQIsgkD6BqtlZXT8Qs+mPXYotRNSmOCoz2vTaa68VQrZ82sxCLMgUEMqmdUixKWDEFhb827dvX5FDYq7YyPAl5hGz8u2YqZkVBMRD4laBuTjACwQgAAEI1CKgSYGq2dlahedkIh7PgZP5qZVEbeZMku2e2URIScD+9NNP0ztAJbpiQZZsBzJqmJhL5JpNxiQOzBl1k65AAAIQaJyARKcmAzQp4JUrnuo65vvrpF6v2SQ+r1MXZYdFAFE7oPFyIaU//KeffrrTll+7dq1Tf0NxpjHxtmrWwbdJIRBCAAIEIFAioDgZTwJokkaCtpRtrV0XtWtVQuFBEkDUDnLYum+0Pll373UYHhWU1VICqShgEIAABB4noGtIm7Ozj3sMIZ50qDrPsVQINNcORG1zLLOuyT9Zu4DLurNLdk4z515Egdu3SSEAAQhAIBQ3OPs1RDx0HWl6dlb1yjQTrNSMpQfiMDZD1I5txFfobyzUYgG3QlUUgUCnBHAGAQj0S0DXj1jQSsxyHel3THL2jqjNeXQb7ps+XTdcZTbVOZs4eGfTOToCAQhAYAUCmjX1mGhmxc3NK1RTu4gEtC8DY+lBbWzKmI0harMZyvY64kGJT9ftMaZmCEAAAjkRkKB1gWlmnaxv9WuVGUsPcnovLdMXRO0ytEaYV5981W2fidQ29jgBBP/jTIojvEAAAqMj0Ieg1U1oDppZWicxvhRRO74xX6rH/sl3qUJkhgAEIACBLAncvn07yGZ1rg9BK5/eHq3Z9e0hpbS1GQKI2mY4ZlnLnj17in5plpaZyAIFLxCAAARGTeDll18OsioImi31JQe6bnQxYypBG/usahfHxkMAUTuesV6qpxK0Dx48KMogaAsMA32h2RCAAATaJyBB614kaLu4bpQFbRc+vY+kaRJA1KY5Lr23ygXt9u3be28LDYAABCAAgTQJ6L6LvgWtmYW1BW2aeGnVkgQQtUsCG0N2zdJ6P2/duuWbpHMIKLDPOc0pCEAAAoMnoJlRdcJs8nQBxb34vgutZ+1CXMqvLzkws06erKB+Y+kTQNSmP0adt5BZ2uWRxwG2VJpdCEAAAoMnIEHrcU5rZSUsy4K2i07Gfs0QtF0wH5IPRO2QRquDtvosrZYdMEvbAXBcQAACEBgAARe0ZhYkcF3QmjX1owqLISBoFzMaew5E7djfAVH/JWh9lhZBG4GpsRkH/BrZyQIBCEBgMAQkYtVYs8mygzjeadZW59o2tSEW0l35bbtf1N8sAURtszwHXZsLWs3SDrojpcZ3udvFerIu+4MvCEBg3AQkJl3EioRvm3X31X/cBrPu/Kq/2LAIIGqHNV6ttVaztF45s7ROol6qgFsvJ7kgAAEIJEugsmEuYnXSt826E5aKr334VX+x4RFA1A5vzFppMbO062M1m3w1t35N1AABCECgfwISlOVW6Bm0XX31L/8I2vIIsD+PAKJ2Hp2RnPNZWi07aGWWNnOOHnS7CvSZ46R7EIBAAgRiQenNkaDtaolV7N+su5lh7yvpMAkgaoc5bo21WoLWZ2kRtMtjVeBdvhQlIAABCDxOIJUjesqAf1j3NiFonQRpygQQtSmPTgdtc0GrWdoO3GXrwiyPpQeHDx/OdozoGAQgsJiABK0/ZcBzd/WjCvKnXydzQS0hzTdgooLVJYCorUtq0PmqG6/goTMStMzSisRypuDvwTeXwHvz5s1w6NCh5UCQGwIQyIKAYlqVoO2qc35Nkj8J2q6WOsgflgcBRG0e47h0L1544YVpGQTtFEXtjTj4m+UxS+ud39jYCMzYOg3SUREYcWfjmCYMZt39qIL8IWhFAVuXAKJ2XYIDLC9B+/fffxct19dKxQYvtQnEwd8szxsYNGNbGwgZIQCBQROIY5o6YtZdXJPvWNDqmsQMrUYBW4UAonYVasuXSaZELGiPHj2aTLuG0hAFYP96zqy7wN8VH7PNWWf1tSu/+IEABPohoL9zj2lqgVl3ca3sW4JWbcAgsCoBRO2q5AZYLha0O3fuDOfPnx9gL/prchyAzboL/F32WGuDDxw4ULiML3TFAV4g0DoBHHRJII5p8qt1rIoB2m7b9OSYOMYgaNsmPo76EbXjGOfw3nvvBV9yIEF748aNkfS8mW7Gwd8sT0HrpC5fvuybQf2e7rABAQhkQ0B/27GolKDt4mt/+dVyA7/J1qzbtbvZDCAdqSQwGlFb2fsRHfz666+L3iJoCwxLvcQzCmZ5C1oHw2ytkyCFQH4E4pim3mmWtG1B62K2LKS7mhlWP7H8CSBq8x/joE/F3k1maJ1EvVTBP55RGEsAZra23vsjw1x0KXMC5ZgmQdtml2eJWfltW0i32S/qTpMAojbNcWmsVVpH65UpiPg26WIC5eA/FkHrZJitdRKkEMiDQNcxTf7imVmzyVIDxGwe76cUe9GdqE2x95m3SYLW19HypIPlBluz22OcoY0pxbO1x48fj0+xDQEIDIxAlzFNYjb2J1SaVBnbxID6jXVLAFHbLe/OvMWCVutoedJBffQKxp5bN08QiJ0GaRcE8AGBJgn41/9ep1l79wW4L58QkE/FUAlabWMQaJsAorZtwj3Uz5MOVoPuAdlLKxiP/Wuy119/vcBx9erVIuUFAhAYDgHFtPLX/218SJcfTQbEvhQ/JWbHHkOH824ZXEsrG4yorcQy7IM86WC58asKyATj5RiSGwIQSIuAlgDEIlOta0PQlsWsGetmxRrrhwCith/urXlVgPHKedKBk6hOq8SsckrQKsVCuHDhwhTDaNbVTnvMBgSGSUCCNl4CoF5o5lRpE+axM77emE3EbBvCuYk2U8c4CCBqMxpnraP17iDMnMTWNA7G8SyGmQUFfbgF/kEAAgMmIKFZFrRmFppYBuDxM46dQqXYiZgViXFZir1F1KY4Kiu0SYKWJx3MBjcvGEvIKiA3EfRnt2C4Z7Zv3140nnW1BQZeIJAkAY9x5caZWVB8Kx+vu+/1SizHYtbMphMBxM7Av0QIIGoTGYh1mhELWp50sEmSYLzJYp2tV199dZ3iK5SlCAQgsAwBxbpYcHpZs9UFreosC1nVq1lZJgJEAkuRAKI2xVFZok086WArLA/EBOOtXNbZi9fVrlMPZSEAgeYJaP1slaCVp2VnaGfFTzNjVjYk+I8mPUYAUfsYkmEd4EkHIcwKxBpJn1XQzAJfkYnIesbNYuvxozQEmiQgQevrZ80smNm0esW86c6CDY+hZXHs8VPimPi5ACKnkyCAqE1iGFZrhAKaSmrJwdiedLB79+6pmC0HYjHxYEwgFo2lbWaB77//fuY5TkAAAt0RUPyPBa08+77in/bnmQvZ8rdaZsasbODfUAkgagc6cgcPHgwKYGYWcha0Eq8yBXAFX7eHDx+GsphVINfshAwx2+wb23+E4cGDB81WTG0QgMDSBBQHFf9VUHFPqe+b2dwnHbiYnRU/mZUVzbpGvtQIIGpTG5Ea7ZGg/fPPP4ucCkDFxsBf9uzZE2aJVwlYD9jlbiqgS8TKELJlOuxDAAI5EXBB6n1S/NO2x0czq3zSgZeTGI7FrJkxKxv4lxMBRO3ARjMWtO+//37yrd+7d2+QYJVptlWmwFo2zQDOE6/q6P/+978gk4B1y1XIqr/zTBznnW/6XHyzmG5ObLp+6oMABOYTkDCNBalioOKfHzN7XNCqjGKt53EPEsMqr0kR1eHHSSEwdALbht6BMbU/FrTPPvtsOHv27Jbu79+/P+jxXnVs3759YVWTUHWTuJIpcFbZ/fv3gwSrTLMJsi2NrtiRcJUp6Mb266+/BllFkdEdqsNxdFDoMAQyJaAY68LUbPLLXeqqjiuVSaAqlS0SsxkJWXUXg8CUAKJ2iiLtjQ8++CD4kgMJ2h9//HFLg0+ePBnu3r0b9AMMdezevXthVZNQdZO4km1pzIwdMyvuztXD/N1i0erbEq6yGdVw+BGB+IL26FCryc6dO4v6r1y5UqS8QAAC7RPQ37nHWLPN2dj4uGKnWqJjmlxwAaxjZhMRrDyIWRHBciaAqB3I6H755ZdFS6sErU588803Sjo3s4lQ3bFjR3BT8KwyzSTIbt26Fdw6b3Bdhy3m0yyKbF0XfqFbt5665d96660iI+KzPwAADwVJREFUqz40FRu8QAACrRKQQPW/c7NqQWtmQflknleNipcYaB+DwBgIIGoHMMr69K1mzhK0OrexsRFcSCq4mU3Eps6tagqKsgMHDkzrdh+eSqTK5N9tVX+5l9M4amw0iyJror+qc9l6NKt/8ODBZYuRHwIQ6IiAPvQqVrg7xWHFWe3rXCxe422dV17F565mZeUTg0AqBBC1qYzEjHZIfChomVkoLzmYUST88MMPxR2wCoIKbmXztbRmE+FrNknL9Ul4ya5fvz6dCVBAlR05cqScnf0KAmKli5NM41iRZaVDZlaUW6XO7777rljKInFbVFLz5fz58zVzdp/t0qVL4fPPP5/axx9/HNy0dMdNN7m5dd9KPEKgHgHFXc+p+O0CVfEkPud54nsQPK+fI4XAmAggahMebQlaX0crgdpUU69duxZkqjM2BU83zc7qE78s9quAKqsSunG+9beHXYNmUCVkxaqNnmjcvF49WcK366QSe8p38eJFJSuZhOFKBVsoJEF74sSJ8Mknn0ztzJkzwU1Ld9z0C3xuLTSFKiGwNgHFDa9E8di3lZbjidlkvSz3IIgOBoEQELWJvgtiQdvHo7suX75cPMBbn/oVWGWLhK6CsZuElixRvK01SzMpYhDPoJrZ1J/Z5CIkntODK27oZjsV1ZMllNa1U6dO1c36WL4UbxY7ffp00U4tz5GpjWIj04cyMytuUDSzIp+/jPH96X0nTZOAPgx7y8oxQnHFz5lN3suKM7t27fLDpBAYPQFEbYJvgVjQ6iJdfnRXX02uEroSDbJymyS0ZArEbhIRsnLenPbjmRRxkenCoz6abd7oof0hWgo3i+mDQ2xvv/12sdRAy3Nk+oU9vxFRH8o0q+0mocCvow3xnZd/myVoPVYobsQ9Vgz1fb2H9X422xS2Oq/ynocUAmMlgKhNbOS19s+XHEjQ6iLdUxNruZVokCnQummGTFauQCJXpgCsZ+mWzw99P76oiIX64yLXrHlBK+EmH2MzMY3ts88+K0RtXQ7xD0lIHNctRz4ItEnABa2ZFd+SuS/FS9/2uKJ9CVvtmz0ubuNYpLwYBMZCAFGb0EhrraPW/qlJQxC0ameVSWzJFHDdJHJlnl+PhVKwzkXcShzFFyXtS3ipv2ZW3Lin7aFb3zeLVV2szSYX9WXYaimN8vsYaRuDwGoE1i+lWKhazLbGivj9rliqPGWrEreKRaozLl8uxz4EciSAqE1oVL/44ouiNUMWtEUHKl4kcmUKzFrz6FkkbocubMsC1syCiyWzrRcp73cOqd8sdvz48SB75513gpbOtNk3XaxVv9nm2mRd1HVsGdNSGs9/+PBh3ySFQOcEYuEZv5d13N/v5eUIVY1UWcVXs80PeSovcStTnJJVleUYBHIhgKhNZCS11lRfzWs205ccJNK0xpuhNY8Kvi5uJWwVdCWIZHo0U+NOW6wwFrBm4xC0Mc6rV68GmT8qLD7X5LYu8qrPrNkPCjdv3lS1GAQ6J6D3tISnHMfCtXxcS7yUp47F4tZsU+AqTskUa3W90VK3ocXaOv0nz7gJIGoTGP8333wzuKDVbGYCTeqkCWVxq7XEMj2aSeK2k0Y06MSsP0GrZw832JW5VR09erQ4r0dj6eJb7Pz3YmbBP6j8t9v4f7/4N1Vxl8yaajP1VBIY5EHNmvp72sym62j1N+XHJXSXEbQxCIlbmSYQDhw4EFSXTHl0vdFSt6HGWvUBg0AVAURtFZUOj0nQ/vzzz4XHDz/8sEjH9uLiVssuZJqtfuONNwaHQbMgarSZdbaG9oknnpDLcO/evSKt8+LLBurkjfNIBOqCKzHrx/3iq31dQDWW2m7a5Fd1mjXHVs9qVp0yliCIAtYlgap4ofe5/02Z2VTortsuLbeROJZJ5CrGqk6lQ4y1ajsGgSoCiNoqKn6s5TQWtB999FHw52227DbZ6rXsQqbZ6nPnziXbzkUNk7hblKep8/FX5xKddeq9cuVKka3urKoutPrKUsLZL7hFBY9edE4Xyke7rSRVfltxRKUQ6ICA/qbcjccLHfP3uZm1+sFYMVZ/s0qHHGudISkEnACi1kl0nMaC9sUXXxy9oO0Yf5buJDqb7JjW3Umw+oXW647FsJYi6GeZ/VwbqS72qtes+Qu9fxCIPxzIF7YcAXLXJ6D3s/9NSViqZHzMrPn3uXxgEBgDAURtD6NcFrTffvttD63AZRsEzDZvzGij/qo6fQlC1bmqY7oxT8f9hxS0XWVad+fHd+zYEXQBlrW1xMB9lVMXAOXj7ENgaATK62jV/vIxn7nVOQwCEFiOQOKidrnODCG3nkXra2g1Q4ugHcKozW+j2aaQffLJJ+dnbuFsPMu4zOPR6j5zVkJ2Y2NjS8t9tjZeX7slQ0M7msFSVWbtzF7F62pfeeUVucIg0BqB8jpaCdrysdacUzEERkAAUdvxIJ85c6bwiKAtMGTxopkVs4mw9VnQrjvmInORfy0pqNs2MwtmVpl90SxvZaEVDjJLWxMa2ZIn4B/Q1FDFDKUIWlHAINAcAURtcywX1nTy5MkiD4K2wJDVi1+k1CmtQ1Xal+3fv3+ha931vCiT+iSryld3lreq7CrHZrVjlbpmlbl9+/asUxyHwFoEJGj9A5q++VBlHifM2vkWQj4wCIyNwCJROzYerfZXD6eXA4lapVheBJ566qlph+oIy2nmBja0ztVsMqt69+7dUOX/yJEjxfOQ5U53PSvFQnBx4aIDJhBokkAsaM0mf6M65j66+MDmvkghkDsBRG2HI6znAR47dizwCJUOoXfo6pdffpl+XS9h2bZrPQJOguzSpUuFK10czSYXTfnXhVPrRGV6Duv169eLfP4A9mJnjReziS/9MtEa1WRSlG5AoJqAf1gym8zI6u/Sj/msbXVJjkIAAssSQNQuS2yN/BKzsjWqoGjiBCQsvYkSnL7dZnrixIlp9fJvNhGbunDqoinzm8kkaPUA9mkBNkLbjyQDMQREQH+bsaDV36KOYxAYFYGWO4uobRkw1Y+PwDPPPDPttH6ecrrT8IaepFFVpS6eErJmE3HrecyssV8o8jpJIQCB2QT0dAM/GwtaM/4WnQspBJokgKhtkiZ1QeA/Avqa32wiKO/cuRN0MfvvcOf/XdxK4Mq033kjunOIJwgkR0DflnijfNvMWv21MPdHCoExEkDUjnHU6XPrBCQgzSbCVhczLUXoS9y21Vmfkb5w4UJbLqb1xjNe04NsQGBgBMwQtAMbsgybm3eXELV5jy+965GAhK1mSM22ilsXuBK5uoGrxyYm79pswk7P80TYJj9cNLBEQB9o/ZAZgtZZkEKgLQKI2rbIUi8EHhEoi1sd1sVOphu4JHJlErkyPXqryo4fPx5iW+bXw+SzaVNbVKeWWChtw8TObFPYik/Tftqos+k2Ut/wCei9PPxe0AMIpE0AUZv2+NC6jAjooqaZ23379hWP/jKbiDXvokSuTGtyq+zq1ashNv/1MLOt9Xh9uaTiZjbpo/i0JULNJj5y4UY/0iFgxnsrndHovSU0oEUCiNoW4VI1BKoIXLt2rbhRRGJNIlfmQrcq/7xjR48eLeqalyeHc2JlNhEGErY+s91E31Sf6pEPpRgEmiRgxrKDJnlSFwTmEUDUzqPDOQh0RMCFrgTuMrbl52o7aqu7OXv2bLHporDYafFFolNszDbFbVuzti12g6pHRkDv25F1me5CoDcCiNre0OMYAhBYhYBEgtmmsNWsrZtErmz37t2hyvbs2RPc9u7dG2SrtIEyEKhDwGzyPq2TlzzdEcBTvgQQtfmOLT2DQLYEJGzjWVvvqGaNZQ8fPgxV9uDBg+B2//79IFNZM8SHOGDNEtD7tNkaqQ0CEJhHAFE7jw7nILAUgfFl3r59e9HpWb9uVpxs8UWiQeJWZmbFDXjLujNjzeOyzMgPAQhAIEUCiNoUR4U2QQACSxOQwJVJ4C5jKrO0MwpAAAKrE6AkBFoigKhtCSzVQmAMBPxZuV999dUYuksfIQABCEAgYQKI2oQHh6YtTYACHRN49913C483btwoUl4gAAEIQAACfRFA1PZFHr8QgAAEIACBXgjgFAJ5EkDU5jmu9AoCnRA4ffp04UdPFCg2eIEABCAAAQj0RABR2xP4XN3Sr/ER2LlzZ9HpkydPFikvEIAABCAAgT4IIGr7oI5PCGRE4NNPPy16c/HixSLlBQIQWEiADBCAQAsEELUtQKVKCIyJwKlTp8KxY8cKG1O/6SsEIAABCKRFAFGb1nis3xpqgEAPBM6dOxdkPbjGJQQgAAEIQKAggKgtMPACAQhAAAJjIkBfIQCB/AggavMbU3oEAQhAAAIQgAAERkcAUdv4kFMhBCAAAQhAAAIQgEDXBBC1XRPHHwQgAAEIhAADCEAAAg0TQNQ2DJTqIAABCEAAAhCAAAS6J5CjqO2eIh4hAAEIQAACEIAABHolgKjtFT/OIQABCPRFAL8QgAAE8iKAqM1rPOkNBCAAAQhAAAIQGCWBVkTtKEnSaQhAAAIQgAAEIACB3gggantDj2MIQGDkBOg+BCAAAQg0SABR2yBMqoIABCAAAQhAAAIQaJJA/boQtfVZkRMCEIAABCAAAQhAIFECiNpEB4ZmQQAC7RPAAwQgAAEI5EMAUZvPWNITCEAAAhCAAAQg0DSBwdSHqB3MUNFQCEAAAhCAAAQgAIFZBBC1s8hwHAIQaJ8AHiAAAQhAAAINEUDUNgSSaiAAAQhAAAIQgEAbBKizHgFEbT1O5IIABCAAAQhAAAIQSJgAojbhwaFpEGifAB4gAAEIQAACeRBA1OYxjvQCAhCAAAQgAIG2CFDvIAggagcxTDQSAhCAAAQgAAEIQGAeAUTtPDqcg0D7BPAAAQhAAAIQgEADBBC1DUCkCghAAAIQgAAE2iRA3RBYTABRu5gROSAAAQhAAAIQgAAEEieAqE18gGhe+wTwAAEIQAACEIDA8Akgaoc/hvQAAhCAAAQg0DYB6odA8gQQtckPEQ2EAAQgAAEIQAACEFhEAFG7iBDn2yeABwhAAAIQgAAEILAmAUTtmgApDgEIQAACEOiCAD4gAIH5BBC18/lwFgIQgAAEIAABCEBgAAQQtQMYpPabiAcIQAACEIAABCAwbAL/BwAA//+ZcDNdAAAABklEQVQDAF34FcHvBIpLAAAAAElFTkSuQmCC', 'aprobada', '2026-05-08 21:52:19', 1, 0),
(3, 'Esteban 2', 'Reuto', '5050', 'contacto.funness@gmail.com', '3012994599', NULL, NULL, NULL, 'La Cira Barrancabermeja 687039', 'Tame', '', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAArUAAACWCAYAAADAB3DwAAAQAElEQVR4AeydS67sNBdGvRGPDgIJEEK0QKKFxAgYD2NgDIjZMBk6SNADXcFt0EIgwf+vOuyDyzeppKqSVB4r4sN52d572Ym/qnMOvPaPmwQkIAEJSEACEpCABDZO4LXiJgEJSEACAwS8LAEJSEACayegqV37CBmfBCQgAQlIQAIS2AKBB8eoqX3wANi9BCQgAQlIQAISkMD9BDS19zO0BQlIYH4C9iABCUhAAhK4SEBTexGPFyUgAQlIQAISkMBWCBw7Tk3tscff7CUgAQlIQAISkMAuCGhqdzGMJiGB+QnYgwQkIAEJSGDNBDS1ax4dY5OABCQgAQlIYEsEjPWBBDS1D4Rv1xKQgAQkIAEJSEAC0xDQ1E7D0VYkMD8Be5CABCQgAQlIoJeAprYXjRckIAEJSEACEtgaAeM9LgFN7XHH3swlIAEJSEACEpDAbghoanczlCYyPwF7kIAEJCABCUhgrQQ0tWsdGeOSgAQkIAEJbJGAMUvgQQQ0tQ8Cb7cSkIAEJCABCUhAAtMR0NROx9KW5idgDxKQgAQkIAEJSKCTgKa2E4snJSABCUhAAlslYNwSOCYBTe0xx92sJSABCUhAAhKQwK4IaGp3NZzzJ2MPEpCABCQgAQlIYI0ENLVrHBVjkoAEJCCBLRMwdglI4AEENLUPgG6XEpCABCQgAQlIQALTEtDUTstz/tbsQQISkIAEJCABCUjgFQKa2leQeEICEpCABLZOwPglIIHjEdDUHm/MzVgCEpCABCQgAQnsjoCm9uohtYIEJCABCUhAAhKQwNoIaGrXNiLGIwEJSGAPBMxBAhKQwMIENLULA7c7CUhAAhKQgAQkIIHpCWzR1E5PwRYlIAEJSEACEpCABDZNQFO76eEzeAlIQAJ9BDwvAQlI4FgENLXHGm+zlYAEJCABCUhAArskcJOp3SUJk5KABCQgAQlIQAIS2CwBTe1mh87A90Dg+++/L2gPuZjDKwQ8IQEJSEACCxLQ1C4I264kUBP44IMPypdffnlSfd59CUhAAhKQwHEITJeppnY6lrYkgdEEMLR///336f6IOJX+SwISkIAEJCCB2wloam9nZ00J3ESgNbS//fbbTe1YaZiAd0hAAhKQwHEIaGqPM9ZmugICGtoVDIIhSEACEpBATWA3+5ra3QyliaydgIZ27SNkfBKQgAQksGUCmtotj56xb4bAYQ3tZkbIQCUgAQlIYOsENLVbH0HjXz2B999/v9R/FObv0K5+yAxQAhKQwKIE7GwaApraaTjaigQ6CWBo//nnn9O1iCga2hMK/3UwAvykInWw1E1XAhJYkICmdkHYdnUcAizg7733Xnm8oT0OczN9DAHmeooPccz7VvykIvWYKO1VAhI4AgFN7RFG2RwXJcACzwKenb722mt+Q5swLDdFgLmMMKuoNascM9dT+SFuU0karAQgoHZBQFO7i2E0ibUQYOFngc94Xr58WX799dc8tJTAqglgYBFmFTGXEWYVrTp4g5OABA5PQFN7+CkggKkIYAJy4Y+IgqEtpUzVvO1IYBYCXSa27SgiSkQUfuoQ8bRfRmzcz3NQa0Q1b5GABCRwEwFN7U3YrCSB/wikKcgzLOT+QVjSsFwbAeYrP1HgQxjim9g2RuYwSjMaEaffD+dePrihtk5EnExv1qH0pxTFbTQBb5TA/QQ0tfcztIUDE8AcsNAnAoyAC3nSsFwDAUwswsAi5mtrSiPizJDmHOZ+RJ3SbMx1hHlFfJDLes2tHkpAAhJYhICmdhHMdvJIAnP1jaFNcxDx9OsGLupz0bbdawi0JrbPlGJGURrStl7bZ21imeuovWfqY2Lq09R92Z4EJLBtAprabY+f0T+AAAss317VhhZT8IBQ7FICJwLMST5kMS9Rn4ltTSmVqUsd1FcP44vmMrHEgOociAcRU5eIXU1KwMYksHkCmtrND6EJLEmAhZcFNvvEJGhok4blUgSYhwjTh5iT+SErY2BuIswowpAirrd1OVerrVdfu3WfPlPEXIv4UZtDV18ZW+bSdY/nJCCBYxLQ1B5z3JfNeie98S0SC2+mk0Yhjy0lMCcBDGEaQeYhavtLw5dzszZ+bf2huu31scf0g3heMl5K4k2Nbau+L3Orc6qvuy8BCUhAU+sckMAIAizK+S1SxNPvz46o5i0SuJkAxpB5l8IQto1h9BAmFrWGr26jrz71UFu37as+pl3UGldipR+Uz0tdL/cj4vSHacSOSs/GNWJD18TX09zqTxugBCRwHwFN7X38rL1zAizcLNSZJousv26QNCynJMBcQ8w3hDFs22f+IUwewuih9r5sp68N6qKuutkWbaApjCt91YqIQmyp7JOyzY9zSgISkMAYApraMZQ2f48J3EKAxZxFN+uy2F4yAXmfpQQuEcAoIuYX5jXFXENtXeZdGkLmH2rvyWPapb22nbaN+n7qtLFkG7Qz9hvXjDFLPvwRK6I/+qFdRLucS/XFl9ctJSABCYwhoKkdQ8l7DkeART4X84inXzfIxflwMEz4JgKYOMRcwsilMHQo51fbeG3wMIhD844+6razvWyHknNtHNQhDtQXC/Ui4vlXBYinVmtcS7P1xcZtxJVtDeXI/YvITiQggU0T0NRuevgMfg4CLP65yEdEYeGeox/b3A8BzBvCKKYwiyjnUpttRHSaxbEGL/ujj9KxcZ5YKFFfHFSNiM5YMJ3Mf2JCZcSWcWXfdRWNbE3DfQlIYGoCmtqpiXa359mNEGAhzsWfBZgFfSOhG+YCBDBsiHlSC9OIukKIiE7DyNzCKKIyYqNfxIcu+u7rr6+piOiM4xbjWpqNuIgJtXHxHCH6GZtr07yHEpCABEYR0NSOwuRNeyeQi3LmySLsApw0jlcyH1AaSMwawrChPiLMG4SBS91rXukX0S/KD11dMUTEbMa1NBt8iAsRV30ZBggGPEeovt6/7xUJSEACtxPQ1N7Ozpo7IYBxqRflXIh3kp5pXCCAMUPMAcxZivmA+gxkRHSaR8wbKiM3+kbZLyX9or6+aRrDiJirta410LR1jepYibGtmzHBALXXPZaABCQwJ4HDmNo5Idr2dglgZtI8RDz9Qdh2szHySwQwZAjjmMKYoZwDbf2I6DSv15pH+kXMt+ybkr5RGbk9wjQSN7GirlgzJsy1RnbkQHqbBCQwCwFN7SxYbXQLBDAYaWYi/IOwLYzZUIwYsBQmrBaGDHW1ERGTmNfy/y37Z35l//SLcr79/7azfyLi1H9ElK4tjeMCprFk/HXsbUwZj0a2JeOxBCTwSAKa2kfSt++HEWDBToPBAs03bw8Lxo5HE0jDRVmbRsYTYRxTfY0y3ghDlmL8MYyor17X+a44sv+cX209+kbZN/vcSz3KvJ/zec+1cWUbY8s6D+JAdd2IOJnupeIpbhKQgARuILCcqb0hOKtIYGoCLN6Yn2wX4zC3Yci+LIcJMD4Iw4oYq1qYrVRtALtajoiTEWOM04xRMt6oXLERE2pjIpa+OCJisH/ao41SbRnvtTFWTYzaJZ9kSwxtHsSBYJamf1TD3iQBCUjgQQQ0tQ8Cb7fLE2gNBIv13MZh+SzX2yMmKsVYpKGqS8wVwmChS9lExLNpTPPFmKbSiF07xhljX1ylY6N/lH1TDvUPgzrHrH9tvB3hdJ5q84Jze2PGQPzEgdp7PJaABCSwVgKa2rWOjHFNSqA2EBH+QdikcP9tLE0TJbxrU8g+JipVm7l/q58VEVEi4tm0YrJa1abxFvNFnKiNNWMsHVtEdMZE/6iM3OCRDCKe5uM19Ud2c/r9WPpCXXlhYlGynSOGsbF6nwQkIIErCHTeqqntxOLJPRHAtNQGAjO0p/yWygUDiOCJMEq10jRRJu++2CLi2RzWpirNFWOEMFmo3LkRN+qKty9W4kIZE+W9MWUMmQ7t02YeT1FmH+TKWLRt0ie5INii9h6PJSABCWyRgKZ2i6NmzKMJsLCnaWExn9pAjA5k5TdihFJdhhWOGCQET3QppYgYNK2YqVSZaOvLgbhRVzcR8RwrRu/ly5eFcurY4FrHkH2UO7fMmTFCdR/ZNHOf/hB55XlLCUhAAnsioKnd02iayzMBFnoW+DzBon7UxRwWKYwVXFphhFJjDGtEdBpBTBPiwwO8U2WGLXOqcxnKgXmAiDFVxzpDmKcm4Z5cI55+3eB04cZ/kXvmnTnXTZEjyhwZh/q6+xKQgATuJbDG+praNY6KMd1FAAPBQp+NsLDvdVHH3KTIO41OXcIilcYq2XSVEfFsWGtjBEeECUQwRWXmrS+/zKmr+4h4zoGYU8SLyoIbY5HcI27/7yHDgbYQubcp1GNFjqi9x2MJSEACeyagqd3z6B4wNxb82kBgZraKARODMKuI3FphblKZ96V8I+b/A6xL/V+6Rq5P+qDUeQ7lh5lDjHVqSdPdlxO5kEdeJ0biyuOhkvqINhAc2jq0mTlrYls6HkvgnEA+Tx9++OH5BY92Q0BTu5uhPHYi+bJKCiz21xiIrLdUSbwIs4owLa0wMQizioZii4jnbyfJP81OXcIEYYBQWWgj19RQvuTcFVZEPOdX50QeqKxsq/Mg3qEYk0/OA+qjOi3GFdEeGmqzruu+BI5K4PPPPz99UM7nKcvN8zCBVwhoal9B4omtEcAk1S8pFv01LfatWcG0EC/CrKJLzCPOv10lPwxNq9qsLpl/5kfJWJBfK3JNDeULi4g4Gdg6xzq/sqGN8YINasPmXLJKPu091E8OjCtq7/FYAhJ4lUCa2V9++eX54ltvvVV8hp5x7G5HU7u7IT1WQhiCNEkRT3+A88gXFiYFEVeqz6wwUhHDhhUzh8grRd0Z9dw0uaSmNKwRcTKttWFL40aZ+ZYdbDn+lDknsuRcm2LE+ZxgzNt7PJaABLoJYGTzXVWbWe5+9913y88//8yu2ikBTe1OB3bvaWG0MAaZJ+YII5THS5TEgIgjhUlBXf0TI8K0pYgZYVxSXXXnOEfsiAUAZQ51SS6p/PBwKZaIeDarba59ORe3MwJwRsm9Ho9L+4xhK8a31VlnHkhgJwQwszwfGFmenzqt119/vXz11Vflxx9/rE9PsG8TayOgqV3biBjPIAEWbhb8vBGzhCHM46VKYkBd/WHoELGliBF13T/ludrEwArxsm9F7IgFAA3FEBGjDCs5psrBtmQP677UI+KMI/Mk4unb2Ygot26MYSvGtxWxDYk506XMb0x5ax7Wk8A1BGozW9f76KOPytdff33SixcvyjfffFNfdn+nBDS1Ox3YPabFQspizMJNfhFPv27A/tqURoJ4r1Eaib4SBqm8p20/+6aEFRriExFnRiuNeF0+6hvlsuItxyLHAOaoDRnjmixbjnwA4Fwq77tU0l6riCgR52rjGHvMnOkSuY1VMlm6zOfi1jLH1PKDsmYGOa/4ZjbndUQ8v8cwst9++21Bc+RR3FZJQFO7ymExqJYALyUW0zzPgo4JyOO9lF1Goj4H9opqYAAADXlJREFUg1Sev5R7xJPJgVeqyyzBEnOVutTm0a8xF3NBzbFombSs4drec88x7bViDFt1jXXXuYw3y4ineRNxXt4T81J187m4tcwxtfy7rJnBv/PprGDMl4r5rGMPVkNAU7uaoTCQPgJ848KLKq+zKLOg5/GjSuK4RWkcusqIcxMRcX5c5xrxdK1up40nTQ68UnUb7g8TwMQyB2sj29bKMUj+W2Od8WaZ86YtM7+5y+R5Sxnx9FxE3Fa2Y+uxBCSwHQKa2u2M1eEixUxgJPj0TfIRC/66AR3OpDQOXWVrItrj2kzktbqdmUI+VLPMO8TcQ3ygyjmYICLi9GPOHI8cg+I2CYHkeUuZz8WtZY6p5cvySAY8e12Tid+VzbjyQ0/Efx9guup47jgENLXHGetNZco3Y5iJDJqXF4tUHltKYGoCaWSZd6htnzmYiylzEcPV3uOxBI5GYMp8P/vss9P/JAFDy7PW1Ta/Q8t1xHOK+NCZ6qrTdy7iyQzzbNei7yH1ten5xxLQ1D6Wv713EMDQ8oLiUsTTt7MaCGioqQmkkc0Fsm6fRa5e2JyDNR33JTANAYws73yeQZ63W1uNuN6g8uEU8WzXujUG6z2egKb28WNgBP8SSIPxZGjL6S+5eeEUNwlMSCDnGYso3/LUTddGlkWuvua+BCRwOwGeO5QGlucPYWTznX+p9Yg4/cpP/YxSN8VagXhuaxW3QxHQ1B5quNebLC+62mDw4uIFtd6IjWxLBFhMEYtoPc/IgbmWCyOLIeeUBDZDYEWB8owh3uc8a7V47tAlAxsRvcaV9YDnExU3CfQQ0NT2gPH0cgR4AeaLLsJfN1iO/P57YoFlYWUxRXXGaWZdJGsq7kvgMgGeqRTvbp6vFM8Yyvf55ZbKycDmB0pKjesQMa8PEdDUDhE67vVFMuelmC/AiCi81Bbp2E52S4AFt15k60TTyLKAamZrMu5L4JwAzxHiHZ3PEyWmNZXv7vOaw0e04zM4zMk7riegqb2emTUmIsCLLV+KERraibAeshkWX+YTYsGtIWhkaxruT09guy3y3KDWuOZzxLOU7+iuLCPi9G1rPmPvvPNO6dtoEyOLfvjhh77bPC+Buwhoau/CZ+VbCfCCy7q8EP2GNmlYXkOABZm5xOJb14uI02LLAuo3ssXtwAR4RtBUxpVnKsV7++233z79n8d4Dn///fcz0pzLezWyZ2g8mImApnYmsFM0u8c2eLnyosvcMLSajqRhOZZAzqPWzDKfWERZbJ1XY2l639YJ8DykWvPKM4Ku+caVZyiVz1L9PH3yySfP/z3Z1sjCknc89TWy0FBLEtDULkn74H3x0uXlmhh46dUvyjxvKYFLBFgw63nEvRH+gSEcDqrDpM07FPUZV56LPvMaEaefXuQHP96/qS7jWpptyMjyqwfZnma2gefhYgQ0tYuhPnZHvIR54UIh4smAsK8kcC0BFs6IOKvGQo7Z7RPzrxbGIHXWkAcSeDCBnJfM13Y+8w5FzPe+MCOi07yOMa6l2a4xsj/99FNT20MJLE9AU3uJudcmIcDLOV/CEf5B2CRQD94IC3SXue3DwvyrhTFItcahPmbu1krDkWVff56XwBCBnEPMr3rO5bxkvva1ERGdxpVngmeDn4ChcsN2ycjybSyiH6SRvQGwVWYloKmdFa+N88LOl3OEhtYZMS0BFnAW1y7xY9ZURJz+D3URT+XYKJi7tdJwZFmbkaF9noUupbnJcmxsffdlO0uW9vVBucSAcW/nR84h5lffWOb8paznOPMe04r66o49j4lFGV/7O7KYWET/mFg0tm3vk8DSBDS1SxM/UH+8JPOFzUuZF/GB0jfVBxNgwU8x92qxQPeJuZqKeDLBEU/lPSnxLHQpzU2WPDf3KNux/Pv0V/lr4MC4X5o7EU/zK+ddlnWdS6a5vVbX69rHxCLmGSYW1felieUZwcSi+rr7ElgrgZWb2rViM65LBHjB8rLMe3hBYy7y2FICayPAnE3VsUVEffj8be/ZSQ8kcCcBTC+ayoDz/u1SfmOMiUV12K2Rra+5L4GtENDUbmWkNhInxoAXc4bLJ30NbdKwvJUA86pLLNKtuhbzoXPM2T5hNlrdmkdfvYh4NswR/+3zgfCSIv69tyn7+vH8sQkwj7sIRETx29guMp7bGgFN7dZGbMXxYjowBhkihjb3LY9JgDnR6hYTyrzqEot0q6lJR3Qbxz6zyby/VvWvRtT7fCC8pPreev/a/o90fz1uEXH1dImI3j/SWgvHN998s7zxxhsnlYEtwr91GEDk5Q0RGDK1G0rFUB9JAOOC6SCGCP+TXXDYuhjTVrUhHfr2k+vMiVZTmtCIeOUbztq0tPu3mI7aLNb7fWZz6+O+9fhzzjJXmYOt6vnIXLyUb86fet4wB3LsL9V91LWPPvqo/Pnnn+Wvv/466VIcERraS3y8tj0CmtrtjdnqImYRYaEgsAhfknB4pBiPVizwtdqFvuuYMW2FCUjdmmPEuRFN41CXtYm4tI/BaJWGo6u8Nebhet6xNIGc48zrev7mnGWeDsUUEc/fujL/2rmWc6hsZPv4449PhvZSuK+//npB5Mqzc+ler0lgawQ0tVsbsZXFy8LCIkJYERpaOIwR3MaKRTtVL959+4xHKxb4WmNi7Lon4rIhZaEcEgtprTQOddnVt+eOSyCflXrO5xxnXg+RiYhn81rPT+bhnubdH3/8UXKLePqJWZ0v+y9evCgo77OUwKIEZu5MUzsz4D03z0LDwpI5RsTF/1Yj9y+hNIDXlPViucQ+3MaKRTuVrK8tIy6b0a5vqVgAW2ECatWGgP1r4/J+CdQE8v3As1s/h/ms1Pe2+xHRaVyZw8xZ5icqO97INUXOO07V1CTQSUBT24nFk2MIsEBExPOtufA8ukwDeE35nMQKdyLi7PdGMaC1chG7VLLA1WLsWq0w9WtC8t4NERgyrzy7felERKd5ZX7nnC5uEpDAIQloag857NMlzUKCmYqI6Rq9s6WIODOBEcPHtUm8Zp/c5xaMa+XCneWduKwugckJpGmlbL915RvY/ODbZ14jotO48qzxLDj3i5sEbiSw72qa2n2P72LZsdCw4KxBxHKtcpG8tlwMsB1JYCYCGM9amNBWGNFrlKaVss+4Zjr5IbJ+d/D85rOY91lKQAISGCKgqR0i5HUJSGAUAW9aJ4HWsLbmFONZCxPaaorMIuL07WttXtnXvBY3CUhgIgKa2olA2owEJCCBpQjURpX9+pvVS6YVszpXjBFxMq1884pZbZXfvhY3CRybgNnPSEBTOyNcm5aABCQwNYFPP/201N+sso9ZTd3aX8R/v3uOMW3VmtT2OE0r37zeGoP1JCABCdxDQFN7Dz3rSmBNBIzlEAS++OKL3jwj4vnb0tqUtga06xhTmsKYtipuEpCABFZOQFO78gEyPAlIQAI1ge+++650mVLOYUpbM8pxXd99CRydgPnvl4Cmdr9ja2YSkIAEJCABCUjgMAQ0tYcZahOdn4A9SEACEpCABCTwKAKa2keRt18JSEACEpDAEQmYswRmIqCpnQmszUpAAhKQgAQkIAEJLEdAU7sca3uan4A9SEACEpCABCRwUAKa2oMOvGlLQAISkMBRCZi3BPZJQFO7z3E1KwlIQAISkIAEJHAoApraQw33/MnagwQkIAEJSEACEngEAU3tI6jbpwQkIAEJHJmAuUtAAjMQ0NTOANUmJSABCUhAAhKQgASWJaCpXZb3/L3ZgwQkIAEJSEACEjggAU3tAQfdlCUgAQkcnYD5S0AC+yOgqd3fmJqRBCQgAQlIQAISOBwBTe3kQ26DEpCABCQgAQlIQAJLE9DULk3c/iQgAQlIoBQZSEACEpiYgKZ2YqA2JwEJSEACEpCABCSwPIE9mtrlKdqjBCQgAQlIQAISkMBDCWhqH4rfziUgAQk8ioD9SkACEtgXAU3tvsbTbCQgAQlIQAISkMAhCcxiag9J0qQlIAEJSEACEpCABB5GQFP7MPR2LAEJHJyA6UtAAhKQwIQENLUTwrQpCUhAAhKQgAQkIIEpCYxvS1M7npV3SkACEpCABCQgAQmslICmdqUDY1gSkMD8BOxBAhKQgAT2Q0BTu5+xNBMJSEACEpCABCQwNYHNtKep3cxQGagEJCABCUhAAhKQQB8BTW0fGc9LQALzE7AHCUhAAhKQwEQENLUTgbQZCUhAAhKQgAQkMAcB2xxHQFM7jpN3SUACEpCABCQgAQmsmICmdsWDY2gSmJ+APUhAAhKQgAT2QUBTu49xNAsJSEACEpCABOYiYLubIKCp3cQwGaQEJCABCUhAAhKQwCUCmtpLdLwmgfkJ2IMEJCABCUhAAhMQ0NROANEmJCABCUhAAhKYk4BtS2CYgKZ2mJF3SEACEpCABCQgAQmsnICmduUDZHjzE7AHCUhAAhKQgAS2T0BTu/0xNAMJSEACEpDA3ARsXwKrJ6CpXf0QGaAEJCABCUhAAhKQwBABTe0QIa/PT8AeJCABCUhAAhKQwJ0ENLV3ArS6BCQgAQlIYAkC9iEBCVwmoKm9zMerEpCABCQgAQlIQAIbIKCp3cAgzR+iPUhAAhKQgAQkIIFtE/gfAAAA//85/CeUAAAABklEQVQDAIp1uoSSQ5aBAAAAAElFTkSuQmCC', 'aprobada', '2026-05-08 22:19:44', 2, 0),
(4, 'Wilmer', 'Reuto', '20204232', 'dannareuto@gmail.com', '3012994599', NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', 'aprobada', '2026-05-09 01:09:36', 3, 0),
(5, 'Wilmer', 'Reuto', '2020435', 'sistemas.p.besst@gmail.com', '3012994599', NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', 'aprobada', '2026-05-09 01:50:46', 3, 0);
INSERT INTO `solicitudes_empresas` (`id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `empresa_nombre`, `empresa_nit`, `empresa_clase_riesgo`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `estado`, `fecha_creacion`, `plan_id`, `trabajadores_extra`) VALUES
(6, 'Wilmer', 'Reuto', '20204232', 'estebanreuto4@gmail.com', '3012994599', NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', 'rechazada', '2026-05-10 03:22:03', 1, 0),
(7, 'Wilmer', 'Reuto', '20204232', 'estebanreuto4@gmail.com', '3012994599', NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', 'pendiente', '2026-05-26 23:56:05', 1, 0),
(8, 'Wilmer', 'Reuto', '20204232', 'estebanreuto4@gmail.com', '3012994599', NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAApEAAACWCAYAAACGsdNaAAAQAElEQVR4Aezdy6ocVRvG8fVGBcFBIB6CgoFMBSGIswQcOBS9CyF3EPAChAydCTp05kxvQXG6QdRhIIIQNYGAgqAx3/dU523XrlRXV1XXYR3+4tpVXV1d9a7f6k0/e1V358Jj/kMAAQQQQAABBBBAYKTAhcB/CCCAAAKZCVAuAgggsL0AIXL7MaACBBBAAAEEEEAgOwFC5MghY3cEahf49ttvg1rtDvQfAQQQqF2AEFn7M4D+IzBQQMHxgw8+CO+//37TBj6M3RBIQYAaEEBgAQFC5AKoHBKBkgTi8PjNN980Xbt161az5AcCCCCAQL0ChMh6x36dnnOWbAUOhccHDx4EQmS2w0rhCCCAwGwChMjZKDkQAmUI3L59O1y6dKm5ZK2Zxxs3boSvv/46EB7LGF96gcAQAfZBYIgAIXKIEvsgULiAzzoqPCpEqrseHr/66qtw/fp1baIhgAACCCCwFyBE7ilYQSAFgXVr8PCoD8to1lFnJzxKgYYAAgggcEyAEHlMiPsRKFCA8FjgoNIlBBDYTqDSMxMiKx14ul2nQFd41Idk9J5HLlvX+Zyg1wgggMBUAULkVDkeh0BGAnqfo3/Hoy5b+yVr/7BMxu95zGgUKBUBBBAoS4AQWdZ40hsE9gKadVR49A/LxOGRWcc9EysIIIAAAhMFpofIiSfkYQggsKzAp59+Gt55553mK3oUInU2n3kkPEqDhgACCCAwhwAhcg5FjoFAIgK6ZP3RRx+F77//vqmI8Ngw8CMSYBUBBBCYS4AQOZckx0FgYwEFSF2yVhlXrlxpviCcmUdp0BBAAAEElhAgRC6h2nlMNiKwjIAuX1+9ejV4gNTs49nZGV8Qvgw3R0UAAQQQeCJAiHwCwQKBHAUUIHX5+uHDh+HixYvh448/Dpp9zLEv1IxAkgIUhQACBwUIkQdpuAOBtAV0+VoBUlUqQN65cyfcvHlTN2kIIIAAAggsLkCIXJyYE0wU4GE9Au3L1wqQPbtzFwIIIIAAArMLECJnJ+WACCwnoMvXCpC6fK2zcPlaCjQEEEhHgEpqEiBE1jTa9DVrAb98rQCpy9f612a4fJ31kFI8AgggkLUAITLr4aP4WgQUIONPX3ddvq7Fgn4igAACCKQhQIhMYxyoAoFOAb987QGSy9edTGxEAAEEchXIum5CZNbDR/ElC2j2UZ++9svXCpBcvi55xOkbAgggkJcAITKv8aLaSgQUIH32UV8ersvXBMiZB5/DIYAAAgicJECIPImPByMwr0D78rUCJF8ePq8xR0MAAQQQmEdgixA5T+UcBYHCBBQg25evCZCFDTLdQQABBAoSIEQWNJh0JV8BXb5WgFQP9PU9XL6WBC0tAapBAAEEzgsQIs97cAuB1QX05eHt9z+uXgQnRAABBBBAYKQAIXIk2Ba7c84yBXT5WgFSn75WD/Xpay5fS4KGAAIIIJCDACEyh1GixuIE/PK1AqQuX/OvzxQ3xHQIAQQQKF6AEFn8ENPB1AQUILl8ndqoUA8CCCCAwFgBQuRYMfZPXyDRCnX5+vXXXw8eILl8nehAURYCCCCAwCABQuQgJnZC4HQBffr6zz//DLp8rQDJl4efbsoREECgHAF6kp8AITK/MaPiDAVeeeWVpuorV64Evr6noeAHAggggEDmAoTIzAeQ8tMXuHbtWvjnn3+aQs/OzpplWj+oBgEEEEAAgfEChMjxZjwCgcECCpB3795t9v/www+bJT8QQAABBBA4WSCBAxAiExgESihT4NatW8EDpC5j3759u8yO0isEEEAAgSoFCJFVDjudXkPgs88+a06jAMll7IailB/0AwEEEEDg/wKEyP8j8D8CcwvEH6QhQM6ty/EQQAABBFIQyCtEpiBGDQgcEdD7IEv7IM2PP/4YXnrppSM9524Epglcvnw5vPjii/um71OddiQehQACawoQItfU5lzFCyhA+vsgS/ogzY0bN8K///4bLl261LzQFz+QdHBWgb6Dadb+77//Do8fP26a9v3555+1oCGAQOIChMjEB4jythfQDFw8S9K37gHSzMLnn3/eBK6+/XO5Lx4FvdgrTKptXb/GJq6N9bwEXn755f3XX6lyMwv379/XKg0BBDIQIERmMEjTS+SRUwUUThSS1DQDp+A0pPn5huyb0z7er/Zy6z5obNo1cTsPAf0B8ujRo3PFEiDPcXADgeQFCJHJDxEFriXQDo7t85pZMDvctL/Z4fvNuM9sXgP985Fyp+UjoNlH/XGmP0Diqs0svln3Or1HIBMBQmQmA0WZywgcC44XLlwIDx48aJpmSbqaPgTgL4hd97PtfnOJcgmHmzdvLvPE4KiLCHTNPvqJ9PzwdZYIIJCHACEyj3GiygUE9ILWuhzanCUOjr///nuz7dCPUj9Ic6i/bEdgioD/seZ/bJlZ84eZH8uMWUi3YIlATgKEyJxGi1pnEVB4bF9OGxMcvQj+RRqXYInAYQH9vsV/rD3zzDPNzLS2+6OYhXQJltMEeNRWAoTIreQ57+oC7dkQFeDh8diMo/ZtN/5FmrYItxH4T6D9+2a2m3387bffmp3iWclmAz8QQCA7AUJkdkNGwWMF/MUsng05JTzq/PpuOy1r/ycNZUBDoC2gWcb271s826j7/THxdt/GEgEE8hAgROYxTlR5gkD8Yma2mw2ZMvPoJeh9kKX9izTeN5YInCLgf7DFs4z6YFr79y2+/5Tz8VgEEJgkMNuDCJGzUXKgFAX03kevSy9mp856KED6F4qX9C/SuBFLBKYKaHYx/oNNs/1dv2/aT+cw44vF5UBDIGcBQmTOo0ftvQL+YqWdFCC1PKXxQZpT9HhsI1Dgj6Gzj+r6G2+8sf+nDbsCpvahIYBAPgKEyHzGikpHCChA+iUzzYiMeOjBXfkgzUEa7qhUQL9nQ2YfnefevXvNqhlf6dNA8AOBzAVqCZGZDxPljxHQzIgHSDML7fdjjTmW78sHaVyCJQIh6HdMbxWJf88029/3u6bA6fszC8mzCIEyBAiRZYwjvYgEfGbEzJrvo4vumrSq90HyQZpJdDyoMAEPj/47pu5ppn9IKPQAaTZmFlJnoCGAQKoChMhUR4a6JglotsMfOOSFzfc9tFSA5IM0h3TYXpOAfrfa4fHY7KP7KHz6+hy/l34slgggsK0AIXJb/2TPnmthc8528EGaXJ8F1D2ngALg2EvX7fN7+NSsZfs+biOAQL4ChMh8x47KWwKaKdEms3kuY/NBGmnSahXw8OgBUA4KgWNnEnUcf2zfeya1D+1kAQ6AwKoChMhVuTnZUgIKkD4LOfZFrqumq1ev7jefnZ3t11lBoAYB/T61w+PQS9exz1tvvRX8OATIWIZ1BMoQIESWMY7V98IDpNm8b9q/ePHiMFv2QqAAAc0annrpOmbw9xNrBjPezjoCCJQhQIgsYxzpxROBOWYhdaiHDx9qEe7cudMs+YFAyQIeHn3WUH1V8Dvl90nH9OMxCylRWooC1HSaACHyND8enYCAXqzmLMMvZTMLOacqx0pRQL87mnn0sKcaFR6nXLrWY+Pmx9Tx4u2sI4BAOQKEyHLGstqezH0pm1nINZ5KnGNLga7waGZhjvCofvmX82udWUgp0BAoU4AQWea40quJAsxCToTjYVkIdIVHFa7ZwlMuXesYcfMv53/22WfjzawjgEDuAq36CZEtEG7mJzDnTKTPQuanQMUIHBboC49zzT762eNZyF9//dU3s0QAgQIFCJEFDipdmibgs5B6NB+okQItIYFJpawZHr1AZiFdgiUC5QsQIssfY3o4UoAP1IwEK2h3hS59R6I33c6xe6q7/YEZ9UOXreeeedRxvTEL6RIsEahDgBB5bJy5vwoBzUL6pWxmIasY8nOdjEOX3h7h7dxOGdyI+xGXu3R49HMxC+kSLBGoQ4AQWcc408uBAsxCDoQqaDcFL/86mly7pT5sMfMYe7322mv7mym8F3JfDCsIILCYACFyMVoOnJMAs5A5jdZ8tSp8xQFSM3Zq851h2SOp/q3Do3qoAPnXX39ptfmaoGaFHwggULwAIbL4IV67g/mdT5eyVTWzkFKopymAeYA0++87EuNtqX7HoWpPITzq2fLee+8FD5DPP/+8NtEQQKASAUJkJQNNNw8L+Czk4T24pzQBhbA4LHZ9R6KZJddt1Z1KeHSc7777rllVgPzll1+adX5kKEDJCEwQIEROQOMh5QjEs5B8oKacce3riYLYoQCpT2X7Y1OahVTNqYVHOfnvj9YJkFKgIVCXACGyrvGmty0Bn4V89OhR657VbnKiFQUUxvoCpD6VrXJSeV+k6k0xPMro7bffDv77w1tBJEJDoD4BQmR9Y15cj812lx09HEzp4JtvvjnlYTwmIwEFMn+OmFmIL2FrBtIDpJmFrWchVWuq4dGH/O7du83q5cuXA7P4DQU/qhKgsxIgREqBlrVAHAb0wpt1Zyh+EQGFsq4Aqe16zsQBMn4+LVJMz0G9Hq9Vu5rtPvSz5JeE6zxj2wsvvBDefffd8NNPP419KPsjgEAhAoTIQgay9m7Elx/1Qly7R8n9H9s3PR88lJlZMwOpbQqPvl3H1HNoqwCpmdCuehQct6pJJn3tiy++CF9++WXfLtyHAAKFCxAiCx/gWrqny49m0y9r//HHH7VQVdVPhUUPima7AKnA5tuEofCosKbnkG6v2VSLwqPPhOrcW9aj8w9t169fH7or+yGAQKECI0JkoQJ0qxiBeMZGL8xjOvbJJ5+M2Z19MxBoB0iVrOeFBzaz3WXitcOj6lIdal6LasslPKpWGgIIICABQqQUaMUI6IXYO6MXac30+O2u5XPPPRdeffXVcO3ata672ZapgIJaPNuosKbm3dHzJP6jw7cvuVRNek7Gdel8qmXRmVCdhIYAAggsIECIXACVQ24noFklM9sXoOCgIKkX8P3GaOXevXvhhx9+iLawmruAxrod1LxPWwQ21UN49BFgiQACJQkQIpcbTY68kYBmmDSzY7YLkwqSChUKkxuVxGlXElBg01i3T2e2/qVr1dIOj2YWtgiygf8QQACBBQQIkQugcsg0BLrCpF7UCZNpjM9cVXhY09h2BUj9QaHnwlznO3YcryeuxWwXYlWHZsuPHYP7txTg3AggMFSAEDlUiv2yFdALt4KE2X8zkwochMlshzR4UNM4xmHNe+SzfRp337b0Us+ndj1eh56DS5+f4yOAAAJrCxAi1xbnfAcFlr5DL+R6UTcjTC5tvcTxjwVHndNsN+O31mxfXJPeNqEa1PQ8U4Bdqw6dk4YAAgisLUCIXFuc820qoBd1hUmzXZBUMXrx1wySZpLUtI22vYACmsZDY6N2aMbRKzXbfQ+k315yqdq6aiI8LqnOsRMVoKyKBQiRFQ9+zV1XkNRMkdn5MOmBUgFBAaZmo7X7rmAmc9mrKTRqPNp1eFDT+Gkf3W+2fIBUfWpem87rzWvSHym+jSUCCCBQugAh6C5bDwAABphJREFUsvQRpn+9Ah4mFUjMLJjZfn8FGAUGNYUbBYj9nSmsZF6DPOUqXzUFQpm3u+UBTWOk5kFNj/V9NY6+PvdSdXp9qjE+vtfmNcX3sY4AAgiULkCILH2E6d9gAQURNQUVM3sqUCpAKEwovKgNPjA7NgIKY3KToZo8x4TG5iBPfug4/lgFuSebZ1uoVtWopjrjA+t8eo6oER5jGdYRQGCIQEn7ECJLGk36MpuAwqSagoJCg5ntj63woqaAoTCjptChtt+p4hU5eJONnNQUxuTWppGvnL0dC2Y6th/HzMKx/dvnO3Rbx1Wdaqo13i+uca7zxcdnHQEEEMhRgBCZ46hR86oCCg3HAqVCh5oCSFdTmIqbAovaqh054WSq1Zv3o6uf2iYHbx724lPHgUzBUb7x/cfWdWztY2ZB46L1Ye3pvbxPXne8h+pUm1JjfBzWEUAAgVIFCJGljiz9WkRAgUfBRcFCzczOXfY+dFKFqbgpCKkpvHQ1D2prL7tq0TbV6s37caiv8XYz2/8LLfJSk2GY+J88/KEaB18fu1R4jPsVPz4OjqfUGh+TdQQQQKBEgVVCZIlw9AkBCSjIqCkctZvCiDezXdg02y312L7mQW3tZV9Nfp/Zrg/eN1+2+6/bspkriClAykN16NhajmkeHD08xo9VH3RMtbnqjY/POgIIIFCiACGyxFGlT0kIKIx4U5iKm8JKuynIeDPbBTWzdZd+fl+2a9Rt74f3zZdLoisAeoA0s8Gn0uMUGtU0kxo/UH1Uf9TUh/g+1hsBfiCAAAK9AoTIXh7uRGA9AQUZbx7U1l76+X25Xu/7z+QB0Kz/fZAKjZqxVGhU88f50RUc1QiOLsISAQQQmC5AiJxut9wjOTICCOwFFAr9hkK1r2vZFRp9xlL3e4uDowKyb2eJAAIIIDBdgBA53Y5HIoDAwgIKiXEoVKBU0yyjmmYa4/u9HA+NmnFUIzi6DMslBTg2ArUJECJrG3H6i0AmAgqQColxuQqMavE2rRMapUBDAAEE1hUgRK7rzdkWEeCgJQooLJpZ8xVKZueXhMYSR5w+IYBAbgKEyNxGjHoRqERA73881Lg8XcmTgG6WLUDvshcgRGY/hHQAAQQQQAABBBBYX4AQub45Z0RgawHOjwACCCCAwMkChMiTCTkAAggggAACCCCwtEB6xydEpjcmVIQAAggggAACCCQvQIhMfogoEAEEthbg/AgggAACTwsQIp82YQsCCCCAAAIIIIDAEYHEQ+SR6rkbAQQQQAABBBBAYBMBQuQm7JwUAQQQKFiAriGAQBUChMgqhplOIoAAAggggAAC8woQIuf13PponB8BBBBAAAEEEFhFgBC5CjMnQQABBBBA4JAA2xHIU4AQmee4UTUCCCCAAAIIILCpACFyU35OvrUA50cAAQQQQACBaQKEyGluPAoBBBBAAAEEthHgrIkIECITGQjKQAABBBBAAAEEchIgROY0WtSKwNYCnB8BBBBAAIEnAoTIJxAsEEAAAQQQQACBEgWW6hMhcilZjosAAggggAACCBQsQIgseHDpGgIIbC3A+RFAAIFyBQiR5Y4tPUMAAQQQQAABBBYTKDZELibGgRFAAAEEEEAAAQQCIZInAQIIIIBAKgLUgQACGQkQIjMaLEpFAAEEEEAAAQRSESBEpjISW9fB+RFAAAEEEEAAgREChMgRWOyKAAIIIIBASgLUgsCWAoTILfU5NwIIIIAAAgggkKkAITLTgaPsrQU4PwIIIIAAAnULECLrHn96jwACCCCAQD0C9HRWAULkrJwcDAEEEEAAAQQQqEOAEFnHONNLBLYW4PwIIIAAAoUJECILG1C6gwACCCCAAAIIzCPQfxRCZL8P9yKAAAIIIIAAAgh0CBAiO1DYhAACCGwtwPkRQACB1AUIkamPEPUhgAACCCCAAAIJChAinxoUNiCAAAIIIIAAAggcEyBEHhPifgQQQACB9AWoEAEEVhcgRK5OzgkRQAABBBBAAIH8BQiR+Y/h1j3g/AgggAACCCBQoQAhssJBp8sIIIAAArUL0H8EThcgRJ5uyBEQQAABBBBAAIHqBAiR1Q05Hd5agPMjgAACCCBQggAhsoRRpA8IIIAAAgggsKQAx+4QIER2oLAJAQQQQAABBBBAoF+AENnvw70IILC1AOdHAAEEEEhSgBCZ5LBQFAIIIIAAAgggkLZAX4hMu3KqQwABBBBAAAEEENhMgBC5GT0nRgABBJYQ4JgIIIDAOgL/AwAA///xEA3BAAAABklEQVQDAMEpvxVBj8n+AAAAAElFTkSuQmCC', 'pendiente', '2026-05-27 22:00:38', 2, 0),
(9, 'Yenny ', 'Martínez ', '123456', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-20 04:00:50', 1, 0);
INSERT INTO `solicitudes_empresas` (`id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `empresa_nombre`, `empresa_nit`, `empresa_clase_riesgo`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `estado`, `fecha_creacion`, `plan_id`, `trabajadores_extra`) VALUES
(10, 'Yenny ', 'MM', '52983138', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAiwAAACWCAYAAAD9udPTAAAQAElEQVR4AeydCfgVVfnHzyuKiPqPyhVcQFDcyDWX3MolTRMl01R8fNTMNS23cEsiQQspiQzxqVh8SoRcwkxASiSCSPZUFAxk01g0AWV16f/7znjO79z7m3vv7HfmzpeHM+edM+e855zP3N+d975nma3+x38kQAIkQAIkQAIkkHECWyn+IwESIAESIAESiEiAxZMmQIMlacLUTwIkQAIkQAIkEJkADZbICKmABEiABLJPgC0kgbwToMGS9zvI9pMACZAACZBAAQjQYCnATWYXSSD7BNhCEiABEqhOgAZLdT68SgIkQAIkQAIkkAECNFgycBPYhOwTYAtJgARIgATqS4AGS335s3YSIAESIAESIAEfBGiw+ICU/SxsIQmQAAmQAAk0NgEaLI19f9k7EiABEiABEmgIAqkYLA1Bip0gARIgARIgARKoGwEaLHVDz4pJgARIgARIIBCBQmemwVLo28/OkwAJkAAJkEA+CNBgycd9YitJgARIIPsE2EISSJAADZYE4VI1CZAACZAACZBAPARosMTDkVpIgASyT4AtJAESyDEBGiw5vnlsOgmQAAmkQeDiiy9OoxrWQQJVCdBgqYqHF0kgRQKsigQyRuDzn/+8+tznPqfGjRunLrvssoy1js0pGgEaLEW74+wvCZAACfggAGPlf//7n8n5zDPPGJkCCdSDAA2WelDPZ51sNQmQQEEIrFmzRmljRUSUiDg932mnnZyYBxKoBwEaLPWgzjpJgARIIMME9ttvP9O6ZcuWGfmTTz5RV199tTmnQAJpEmgcgyVNaqyLBEiABBqYwEcffeT0TkRU27Zt1bvvvuuc4/DEE08gYiCB1AnQYEkdOSskARIggWwTEHGHgETcGK3dais+LsChCCGrfeQnMKt3hu0iARIggToQ2LRpk5m/0q1bN9OCfv36OTLmttx4442OzAMJpEmABkuatAta1x133KEOPvhgtWHDhoISYLdJID8E9tprL9PYiRMnGhlzV0Rcj8vvf/97k56+wBqLSoAGS1HvfIr9fuSRR9Tbb7+t7C/CFKtnVSSQCIFDDjlE3X333YnorqdSPX/Fqw0irsECL4vXdaaRQJIEaLAkSZe6SYAEGpYAVs8MHjzY2VjN7mSjyCKucWL3Rw8LIa13796I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', 'pendiente', '2026-06-20 04:13:27', 1, 0),
(11, 'Yenny', 'MM', '52983138', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-20 04:21:46', 1, 0),
(12, 'Yenny ', 'MM', '52983138', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-20 04:25:20', 1, 0),
(13, 'Yenny ', 'MM', '52983138', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-20 04:37:54', 1, 0);
INSERT INTO `solicitudes_empresas` (`id`, `nombre`, `apellido`, `cedula`, `email`, `telefono`, `empresa_nombre`, `empresa_nit`, `empresa_clase_riesgo`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `estado`, `fecha_creacion`, `plan_id`, `trabajadores_extra`) VALUES
(14, 'Yenny ', 'MM', '52983138', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-20 05:02:03', 1, 0),
(15, 'Yenny ', 'MM', '52983138', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-21 02:44:49', 1, 0),
(16, 'Yenny', 'MM', '123456', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'pendiente', '2026-06-21 02:52:54', 1, 0),
(17, 'Yenny ', 'MM', '123456', 'sistemas.p.besst@gmail.com', '3134536936', NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', 'aprobada', '2026-06-21 03:04:03', 1, 0);

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
(1, 'admin', '$2y$10$obkzF0GpHZ2fjNjxXak8W.MllPgGx/6TSPuKZWbDYT2Nkp7mBnIDC', 'Super Administrador', '2026-05-09 00:45:49');

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
  `correo_seguridad` varchar(150) DEFAULT NULL,
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

INSERT INTO `usuarios` (`id`, `empresa_id`, `nombre`, `apellido`, `cedula`, `email`, `correo_seguridad`, `telefono`, `rol`, `licencia_sst`, `tipo_licencia`, `numero_licencia`, `fecha_licencia`, `expedida_por`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `fecha_registro`, `ultimo_acceso`, `activo`, `logo_empresa`, `nombre_empresa`, `tipo_persona`, `regimen_tributario`, `tipo_doc_empresa`, `num_doc_empresa`, `clase_riesgo`, `actividad_economica`, `grupo_id`, `foto_perfil`) VALUES
(1, 1, 'Esteban', 'Reuto', '1010', 'estebanreuto4@gmail.com', 'estebanreuto4@gmail.com', '3001112233', 'representante', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', '2026-03-26 00:22:35', 1, 'uploads/logos/logo_empresa_1_1778194209.jpeg', 'Constructora Vertix S.A.S', 'Juridica', 'Responsable de IVA', 'NIT', '900111222', NULL, '4921', NULL, 'uploads/perfiles/user_1_1778194254.jpeg'),
(2, 1, 'WILLMER ESTEBAN', 'REUTO ROMERO', '2020', 'wreuto@estudiantes.areandina.edu.co', 'wreuto@estudiantes.areandina.edu.co', '3109998877', 'sst', 'si', 'Profesional', 'L-12345', NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', '2026-05-08 22:11:22', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/perfiles/user_2_1778192121.jpeg'),
(3, 1, 'Esteban', 'Reuto (Trab)', '3030', 'estebanreuto27@gmail.com', 'estebanreuto27@gmail.com', '3205554433', 'trabajador', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'uploads/perfiles/user_3_1778194305.jpeg'),
(5, 3, 'Esteban 2', 'Reuto', '5050', 'contacto.funness@gmail.com', 'contacto.funness@gmail.com', '3012994599', 'representante', NULL, NULL, NULL, NULL, NULL, 'La Cira Barrancabermeja 687039', 'Tame', '', '', 'data:image/png;base64,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', '2026-05-08 22:20:04', NULL, 1, NULL, 'Esteban 2', NULL, NULL, 'NIT', '5050', NULL, NULL, NULL, NULL),
(6, 4, 'Wilmer', 'Reuto', '20204232', 'dannareuto@gmail.com', 'dannareuto@gmail.com', '3012994599', 'representante', NULL, NULL, NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', '2026-05-09 01:10:06', NULL, 1, NULL, 'Wilmer', NULL, NULL, 'NIT', '20204232', NULL, NULL, NULL, NULL),
(7, 17, 'Yenny ', 'MM', '123456', 'sistemas.p.besst@gmail.com', 'sistemas.p.besst@gmail.com', '3134536936', 'representante', NULL, NULL, NULL, NULL, NULL, '2-37', 'Cajicá', '', '', 'data:image/png;base64,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', '2026-05-09 01:52:38', NULL, 1, 'uploads/logos/logo_empresa_7_1782012015.jpg', 'Yenny', 'Juridica', 'Responsable de IVA', 'NIT', '123456', NULL, '6201', NULL, NULL),
(19, 17, 'Arturo', 'OO', '1106308222', 'laboralproSST@gmail.com', 'laboralproSST@gmail.com', '3242909504', 'sst', 'si', 'Profesional', ' 10355', '2021-09-09', 'Secretaria de Salud de Bogotá', '2-37', 'Cajicá', '', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAiwAAACWCAYAAAD9udPTAAAQAElEQVR4AeydB9wUxfnHZ7Ag2LAjNrAgWBHR2CNqFBtiLLGgWGIvaKxYQY1RY68RW+z6sfcae0FRbNhiwYY9iqCiJvr/v985nmXfe9uVvbvdux8fZmd2d8oz37339rlnnpnp9H/6JwIiIAIiIAIiIAIpJ9DJ6Z8INDiB+eabz80///wNTkHdFwERKI+ASleagBSWShNW/akn8Ntvv7n//e9/bp555nG9e/d2+icCIiACIpA+AlJY0vdMJFEVCWBdabKChhaJv/76azd06NBwroMI1BMB9UUEsk5ACkvWn6DkL4vAr7/+GspjXQmJpsOYMWOajvovAiIgAiKQJgJSWNL0NCRLVQn06NEjau+dd95xnTt3DueTJk0KsQ7VJKC2REAERKB9AlJY2ueju3VM4Oeff27Wu2WXXTac49MSEjqIgAiIgAikhoAUltQ8CglSTQJDhgxx+KzQpvmsjB49mtNWgy6KgAiIgAjUloAUltryV+s1IvDkk09GLZ977rkhvfjii4eYw+mnn06kIAIiIAIikBICUlhS8iDKE0OliyVg1pVZZ521WVHvfTi/+uqrQ6yDCIiACIhAOghIYUnHc5AUVSQQd7b9+OOPW235s88+a/W6LoqACIiACNSGQFUUltp0Ta2KQOsEzNnW+5w1JZ5rjjnmCKcsJBcSOoiACIiACKSCgBSWVDwGCVEtAnFn2w033LBFs2uvvXaLa7ogAiIgAikh0NBiSGFp6MffeJ03Z1vvvbv++utbALjqqquia3fddVeUVkIEREAERKC2BKSw1Ja/Wq8yAXO2nXHGGTts+dhjj+0wjzKIgAjECCgpAhUkIIWlgnBVdboILLnkkpFAb731VpRuKzF58uS2bum6CIiACIhAlQlIYakycDVXOwL/+c9/osbnmmuuKJ2f6NQp92cxderU/Fs6zzYBSS8CIpBhArlv5gx3QKKLQKEEvM/NCpphhhnaLTLTTDOF+//9739DrIMIiIAIiEDtCUhhqf0zkARVIBAfDvroo4/abbFr167hftX3FAqt6iACIiACItAaASksrVHRtbojEB8O6tKlS7v96969e7v3dVMEREAERKD6BKSwVJ95VlvMtNze54aDvM/F7XVmpZVWau+27omACIiACNSAgBSWGkBXk9UlEB8O+vLLL6PG2exw7rnndhbsxu67725JxSIgAiIgAikhUD8KS0qASoz0EbDhIO+9izvcfvfddy7+r2fPnuE0bmG59NJLwzUdREAEREAEaktACktt+av1KhDwPjcMZNOVrcnOnTs7FpIjcI11V/L3ELrnnnu4pSACIiACDUMgrR2VwpLWJyO5EiEQHw766quvmtX56aefum+//TYEu7HQQgtZMsTvvvtuiHUQAREQARGoLQEpLLXlr9YrTMCGgzpqZoEFFghZWHvlmGOOcWaNmTRpUriugwiIQFoISI5GJSCFpVGffIP02/vccJAtBtdWt998883o1oUXXhgpLD///HN0XQkREAEREIHaEZDCUjv2arnCBOLDQV988UWHrW2xxRZRHrOwaPG4CIkSBRJQNhEQgcoQkMJSGa6qNQUECh0OMlGvuOKKyLJizrdSWIyO4nomgMP5Bx984F599VX3+OOPu7vuustdd9117h//+If7+9//7o4//nh3yCGHuH322cfttNNObv3113dbbbVVPSNR31JIQApLCh+KREqGgPe54aDZZput4AoXXXTRkNdmDoWTujqoM/VG4LPPPgtKxllnneU23XRTN3DgQLfmmmu6AQMGuOWXX9716dPHYW1cbLHFHE7l3bt3d/PNN5+bd955ozWImNLfv39/t+6667ott9zSDRs2zO2///7uqKOOcn/729/ceeed51Dob7zxRsfMuZdeesk9+uijoTztPPzww/WGVf1JIQEpLCl8KBKpfALzzDNPqMR77zraOyhknHYYN26c8947KSzTgChKBQEsfW+//XawfJx55pnB0oGVAwV72WWXDUrGiSee6J599ln3yiuvOHyy3n//fTdx4kTHYonffPONmzJlips6dar75Zdf3K+//uqoM945731Yp2jmmWd2s8wyi5t11lldt27dgnLTo0cP16tXL4fys+KKK7revXuHvK7pH+1su+22joUYR4wY0XRF/0WgMgQ6VaZa1SoCpRFIqpQpHN7nrCzF1Ot98zL8miymvPKKQKkEJk2a5MaOHeuuvfZaN3LkSLfjjju63/3ud27++ed3q6++usPycdJJJzksHXwuv//++6BUrLLKKsGasvbaa7tBgwYFBYaye++9tzv00EPdqFGjHIoOCyHefPPN7qGHHgrtMG0fZYbAECpT/z///HPHlP+P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5tDAtEOnTp3cwIEDp505d84550TpUhM///xzKOp97gs8nDToYZlllnH2TItBoLzZJfDpp59mV3hJXjMCUlhqhr7xGmbzQnrdo0cP98ADD5AsKdg6DmZlKamSEgqhuFAsydV0zTeGehsprLDCCo4p3YTPP//c8UyZ5t1IDBqxr/Y3hP9aI/ZffS6PgBSW8vipdIEEWD5/6tSpjp1bb7vttgJLtcy28MILRxcPOeSQKF2NBLOZaKewBeTI2XoYPnx4dGPdddeN0vWeYL0dfI9QUj755JNm3UX51JLtzZDU5Unnzp1Dv2xKfzjRQQQKJCCFpUBQylY6gZ122sm98847wcmW5fdZc6XU2lB6KMsL7uijjyZZlfDcc881awdHYXxwrrjiCvf99983u9fRSdwH5oYbbugoeybvYzFZZJFFnCkoKCmst8Nzsw6RZrHAb775xn377bfh82H3FNcnAfzB6BnPnlhBBIohIIWlGFp1lLdaXTnmmGPcPffcE5rbfffd3dChQ0O6lANWGvui49d6KXUUU8bauu+++9zGG2/sWJXXyr/77ruOdWCw8hTrQMjL3OqplxiFAwUFxYSATwqrEBtD6yfncSXlo48+sluKG4BAr169Qi/5HISEDiJQBAEpLEXAUtbiCLDk/IUXXhgKrb322s72CgoXSjhgpaGY997lWzy4nlRgX6N55503qs6+XL2f7iCL7wlrDzd9gAAAEABJREFUyTA9OT5dMypUQML76fUVkD1VWVA0FlxwwcgPZYkllnAoKHEhvc/1j60NxowZ48ySQtl4PqUbh0AjDYE2zlOtXk9TqrBUD4BaqgyBF1980R166KGhcla0LXfdhb/+9a+hLg6lLDJHuY6CKSp33323s/VWUFYY1hg3blyYkWRt//rrr47hnF133dVhMeiobrsf30LAnJDtXhrjww8/3OE3BAOUOKwnBJQ0m+kUl9t779jKAOWEGVzEOClXwyIWl0PpjgnwHBmiIZCuxhDrnnvuGQmGlTI6UUIECiAghaUASMpSHAFe9ttvv32Y+cGva4ZOiquhZW4sGXY132HTrpcat6aosLOs9z74VTAU1bNnz1A9U3BJ0EfiYgMzYijjvQ97J5GudWCaNkolCgmKCS8vC5deeqljw0cUt/w+cw1Oe++9d7CeoJygpDz//PO17pLab4MAz5fA8yWL97nPOOmL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5RUBERABEeiYgBSWjhlNz6FUqwR++umn6DpKCwtTRReUEAEREAEREIEECEhhSQBio1fhvQ87Go8fP96xUVqj81D/RUAEREAE2idQyl0pLKVQU5lmBL7++uugqPTo0aPZdZ2IgAiIgAiIQFIEpLAkRVL1iIAIiIAI1AkBdSONBKSwpPGpSCYREAEREAEREIFmBKSwNMOhExEQARFIPwFJKAKNSEAKSyM+dfVZBERABERABDJGQApLxh6YxBWB9BOQhCIgAiKQPAEpLMkzVY0iIAIiIAIiIAIJE5DCkjBQVZd+ApJQBERABEQgewSksGTvmUliERABERABEWg4AlJYUvfIJZAIiIAIiIAIiEA+ASks+UR0LgIiIAIiIAIikDoCRSssqeuBBBKBFBDo3r17kKJ3794h1kEEREAERCBZAlJYkuWp2hqUwHzzzRd63rlz5xDrIAIiIAIdENDtIglIYSkSmLKLgAiIgAiIgAhUn4AUluozV4siIAIikH4CklAEUkZACkvKHojEEQEREAEREAERaElACktLJroiAiKQfgKSUAREoMEISGFpsAeu7oqACIiACIhAFglIYcniU5PM6ScgCUVABERABBIlIIUlUZyqTAREQAREQAREoBIEpLBUgmr665SEIiACIiACIpApAlJYMvW4JKwIiIAIiIAINCaBdCosjfks1GsREAEREAEREIE2CEhhaQOMLouACIiACIhA1gnUk/xSWOrpaaovIiACIiACIlCnBKSw1OmDVbdEQAREIP0EJKEIFE5ACkvhrJRTBERABERABESgRgSksNQIvJoVARFIPwFJKAIikB4CUljS8ywkiQiIgAiIgAiIQBsEpLC0AUaXRSD9BCShCIiACDQOASksjfOs1VMREAEREAERyCwBKSyZfXTpF7yRJBw2bJg78cQTXf/+/Rup2+qrCIiACFSNgBSWqqFWQ/VMYKuttnL77bef69q1az13U30TAREQgZoRaGCFpWbM1bAIiIAIiIAIiECRBP4fAAD//8uW3WsAAAAGSURBVAMAOAiF1qSq8XcAAAAASUVORK5CYII=', '2026-06-23 00:59:09', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `usuarios` (`id`, `empresa_id`, `nombre`, `apellido`, `cedula`, `email`, `correo_seguridad`, `telefono`, `rol`, `licencia_sst`, `tipo_licencia`, `numero_licencia`, `fecha_licencia`, `expedida_por`, `direccion`, `ciudad`, `barrio`, `localidad`, `firma`, `fecha_registro`, `ultimo_acceso`, `activo`, `logo_empresa`, `nombre_empresa`, `tipo_persona`, `regimen_tributario`, `tipo_doc_empresa`, `num_doc_empresa`, `clase_riesgo`, `actividad_economica`, `grupo_id`, `foto_perfil`) VALUES
(21, 17, 'Samuel', 'Pruebas', '654321', 'carlosospina1994@gmail.com', NULL, '3214810298', 'trabajador', 'si', '', '', '0000-00-00', '', 'Carrera 3 # 2 - 37 ', 'Cajicá', 'La laguna', '', 'data:image/png;base64,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', '2026-07-12 21:08:52', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `whatsapp_prospectos`
--

CREATE TABLE `whatsapp_prospectos` (
  `id` int(11) NOT NULL,
  `empresa_nombre` varchar(180) DEFAULT NULL,
  `contacto_nombre` varchar(140) NOT NULL,
  `cargo` varchar(120) DEFAULT NULL,
  `telefono` varchar(40) NOT NULL,
  `email` varchar(160) DEFAULT NULL,
  `ciudad` varchar(120) DEFAULT NULL,
  `nivel_riesgo` varchar(30) DEFAULT NULL,
  `trabajadores` int(11) DEFAULT NULL,
  `interes` varchar(80) NOT NULL DEFAULT 'diagnostico',
  `etapa` varchar(40) NOT NULL DEFAULT 'nuevo',
  `prioridad` varchar(20) NOT NULL DEFAULT 'media',
  `origen` varchar(80) NOT NULL DEFAULT 'WhatsApp Business',
  `mensaje_inicial` text DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `ultimo_contacto` datetime DEFAULT NULL,
  `proximo_seguimiento` date DEFAULT NULL,
  `creado_por_admin_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `whatsapp_prospectos`
--

INSERT INTO `whatsapp_prospectos` (`id`, `empresa_nombre`, `contacto_nombre`, `cargo`, `telefono`, `email`, `ciudad`, `nivel_riesgo`, `trabajadores`, `interes`, `etapa`, `prioridad`, `origen`, `mensaje_inicial`, `notas`, `ultimo_contacto`, `proximo_seguimiento`, `creado_por_admin_id`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Vertix', 'Arturo 00', 'Gerente', '3001234567', 'laboralprosst@gmail.com', 'Bogota', 'III', 10, 'diagnostico', 'llamada', 'alta', 'WhatsApp Business', 'Vertix, 10 trabajadores, nivel de riesgo 3, bogota. laboralprosst@gmail.com', 'Cliente de prueba respondio datos minimos por WhatsApp. Recomendar plan Pequena empresa y agendar llamada de diagnostico.\\nCliente de prueba agenda llamada para hoy a las 5:30 p. m.', '2026-07-09 17:11:59', '2026-07-09', 1, '2026-07-09 21:52:28', '2026-07-09 22:11:59');

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
-- Indices de la tabla `almacenamiento_archivos`
--
ALTER TABLE `almacenamiento_archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_storage_empresa` (`empresa_id`),
  ADD KEY `idx_storage_carpeta` (`empresa_id`,`estandar_numero`,`subestandar_slug`),
  ADD KEY `idx_storage_usuario` (`usuario_id`),
  ADD KEY `idx_storage_carpeta_personalizada` (`empresa_id`,`carpeta_id`),
  ADD KEY `idx_storage_control_documental` (`empresa_id`,`estandar_numero`,`codigo_documento`,`version_documento`);

--
-- Indices de la tabla `almacenamiento_carpetas`
--
ALTER TABLE `almacenamiento_carpetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_storage_folder_company` (`empresa_id`,`estandar_numero`,`subestandar_slug`),
  ADD KEY `idx_storage_folder_parent` (`empresa_id`,`parent_id`);

--
-- Indices de la tabla `almacenamiento_compartidos`
--
ALTER TABLE `almacenamiento_compartidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_storage_share_token` (`token_hash`),
  ADD KEY `idx_storage_share_object` (`empresa_id`,`tipo_objeto`,`objeto_id`),
  ADD KEY `idx_storage_share_expiry` (`activo`,`vence_en`);

--
-- Indices de la tabla `asistencias_capacitacion`
--
ALTER TABLE `asistencias_capacitacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `calendar_connections`
--
ALTER TABLE `calendar_connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_calendar_user` (`usuario_id`),
  ADD KEY `idx_calendar_company` (`empresa_id`),
  ADD KEY `idx_calendar_provider` (`provider`);

--
-- Indices de la tabla `capacitaciones_actas`
--
ALTER TABLE `capacitaciones_actas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_acta_curso_usuario` (`curso_id`,`usuario_id`),
  ADD KEY `fk_acta_usuario` (`usuario_id`),
  ADD KEY `fk_acta_intento` (`intento_id`);

--
-- Indices de la tabla `capacitaciones_categorias_personalizadas`
--
ALTER TABLE `capacitaciones_categorias_personalizadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cap_cat_empresa_tipo_categoria` (`empresa_id`,`tipo_capacitacion`,`categoria`),
  ADD KEY `idx_cap_cat_empresa_tipo` (`empresa_id`,`tipo_capacitacion`),
  ADD KEY `fk_cap_cat_usuario` (`creado_por`);

--
-- Indices de la tabla `capacitaciones_cursos`
--
ALTER TABLE `capacitaciones_cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `actividad_id` (`actividad_id`);

--
-- Indices de la tabla `capacitaciones_intentos`
--
ALTER TABLE `capacitaciones_intentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_intento_curso_usuario` (`curso_id`,`usuario_id`),
  ADD KEY `fk_intento_usuario` (`usuario_id`);

--
-- Indices de la tabla `capacitaciones_materiales`
--
ALTER TABLE `capacitaciones_materiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_material_curso` (`curso_id`);

--
-- Indices de la tabla `capacitaciones_opciones`
--
ALTER TABLE `capacitaciones_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_opcion_pregunta` (`pregunta_id`);

--
-- Indices de la tabla `capacitaciones_preguntas`
--
ALTER TABLE `capacitaciones_preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pregunta_curso` (`curso_id`);

--
-- Indices de la tabla `capacitaciones_progreso`
--
ALTER TABLE `capacitaciones_progreso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_progreso_curso_usuario` (`curso_id`,`usuario_id`),
  ADD KEY `fk_progreso_usuario` (`usuario_id`);

--
-- Indices de la tabla `capacitaciones_respuestas`
--
ALTER TABLE `capacitaciones_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_respuesta_intento_pregunta` (`intento_id`,`pregunta_id`),
  ADD KEY `fk_respuesta_pregunta` (`pregunta_id`);

--
-- Indices de la tabla `control_documental_config`
--
ALTER TABLE `control_documental_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_doc_config_empresa_estandar` (`empresa_id`,`estandar_numero`),
  ADD KEY `idx_doc_config_empresa` (`empresa_id`);

--
-- Indices de la tabla `control_documental_registros`
--
ALTER TABLE `control_documental_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_control_empresa_estandar` (`empresa_id`,`estandar_numero`,`creado_en`),
  ADD KEY `idx_doc_control_archivo` (`almacenamiento_archivo_id`),
  ADD KEY `idx_doc_control_acta` (`doc_asignacion_id`);

--
-- Indices de la tabla `cpanel_admins`
--
ALTER TABLE `cpanel_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `demo_prospectos`
--
ALTER TABLE `demo_prospectos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_demo_prospectos_email` (`email`),
  ADD UNIQUE KEY `uq_demo_prospectos_token` (`acceso_token_hash`),
  ADD KEY `idx_demo_prospectos_estado` (`estado`),
  ADD KEY `idx_demo_prospectos_ultima_visita` (`ultima_visita`),
  ADD KEY `idx_demo_prospectos_creado_en` (`creado_en`),
  ADD KEY `idx_demo_prospectos_acceso` (`acceso_estado`,`acceso_expira_en`);

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
  ADD UNIQUE KEY `uq_e2_empresa_periodo` (`empresa_id`,`anio`,`mes`),
  ADD KEY `subido_por` (`subido_por`);

--
-- Indices de la tabla `estandar2_planilla_versiones`
--
ALTER TABLE `estandar2_planilla_versiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_e2_planilla_version` (`planilla_id`,`numero_version`),
  ADD KEY `idx_e2_version_empresa` (`empresa_id`,`creado_en`),
  ADD KEY `idx_e2_version_archivo` (`almacenamiento_archivo_id`);

--
-- Indices de la tabla `estandar4_actividades`
--
ALTER TABLE `estandar4_actividades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estandar4_actividad_capacitacion` (`plan_id`,`actividad_capacitacion_id`),
  ADD KEY `idx_estandar4_actividad_plan` (`plan_id`),
  ADD KEY `fk_estandar4_actividad_capacitacion` (`actividad_capacitacion_id`);

--
-- Indices de la tabla `estandar4_planes`
--
ALTER TABLE `estandar4_planes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estandar4_empresa_anio` (`empresa_id`,`anio`),
  ADD KEY `idx_estandar4_estado` (`estado`),
  ADD KEY `fk_estandar4_sst` (`sst_id`),
  ADD KEY `fk_estandar4_representante` (`representante_id`);

--
-- Indices de la tabla `estandar4_seguimientos`
--
ALTER TABLE `estandar4_seguimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estandar4_seguimiento_plan` (`plan_id`);

--
-- Indices de la tabla `estandar5_centros_medicos`
--
ALTER TABLE `estandar5_centros_medicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estandar5_centro_empresa` (`empresa_id`),
  ADD KEY `idx_estandar5_centro_estado` (`estado`),
  ADD KEY `fk_estandar5_centro_creador` (`creado_por`);

--
-- Indices de la tabla `estandar5_evaluaciones_medicas`
--
ALTER TABLE `estandar5_evaluaciones_medicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estandar5_eval_empresa` (`empresa_id`),
  ADD KEY `idx_estandar5_eval_trabajador` (`trabajador_id`),
  ADD KEY `idx_estandar5_eval_perfil` (`perfil_cargo_id`),
  ADD KEY `idx_estandar5_eval_centro` (`centro_medico_id`),
  ADD KEY `idx_estandar5_eval_estado` (`estado`),
  ADD KEY `fk_estandar5_eval_creador` (`creado_por`);

--
-- Indices de la tabla `estandar5_evaluaciones_medicas_soportes`
--
ALTER TABLE `estandar5_evaluaciones_medicas_soportes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_e5_soporte_empresa` (`empresa_id`),
  ADD KEY `idx_e5_soporte_trabajador` (`trabajador_id`),
  ADD KEY `idx_e5_soporte_eval` (`evaluacion_id`),
  ADD KEY `idx_e5_soporte_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_e5_soporte_altura_vencimiento` (`altura_fecha_vencimiento`),
  ADD KEY `idx_e5_soporte_confinado_vencimiento` (`confinado_fecha_vencimiento`),
  ADD KEY `fk_e5_soporte_perfil` (`perfil_cargo_id`),
  ADD KEY `fk_e5_soporte_centro` (`centro_medico_id`),
  ADD KEY `fk_e5_soporte_creador` (`creado_por`);

--
-- Indices de la tabla `estandar5_historia_clinica_custodias`
--
ALTER TABLE `estandar5_historia_clinica_custodias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_e5_custodia_empresa` (`empresa_id`),
  ADD KEY `idx_e5_custodia_centro` (`centro_medico_id`),
  ADD KEY `idx_e5_custodia_fecha` (`fecha_emision`),
  ADD KEY `fk_e5_custodia_creador` (`creado_por`),
  ADD KEY `idx_e5_custodia_renovacion` (`fecha_renovacion`);

--
-- Indices de la tabla `estandar5_perfiles_cargo`
--
ALTER TABLE `estandar5_perfiles_cargo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estandar5_perfil_empresa` (`empresa_id`),
  ADD KEY `idx_estandar5_perfil_centro` (`centro_medico_id`),
  ADD KEY `idx_estandar5_perfil_estado` (`estado`),
  ADD KEY `fk_estandar5_perfil_creador` (`creado_por`);

--
-- Indices de la tabla `estandar5_procesos_perfil`
--
ALTER TABLE `estandar5_procesos_perfil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estandar5_proceso_empresa_nombre` (`empresa_id`,`nombre`),
  ADD KEY `idx_estandar5_proceso_empresa` (`empresa_id`),
  ADD KEY `fk_estandar5_proceso_creador` (`creado_por`);

--
-- Indices de la tabla `estandar5_restricciones_recomendaciones`
--
ALTER TABLE `estandar5_restricciones_recomendaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_e5_restr_empresa` (`empresa_id`),
  ADD KEY `idx_e5_restr_trabajador` (`trabajador_id`),
  ADD KEY `idx_e5_restr_estado` (`sst_estado`),
  ADD KEY `idx_e5_restr_programada` (`sst_fecha_programada`),
  ADD KEY `fk_e5_restr_creador` (`creado_por`),
  ADD KEY `idx_e5_restr_carta_fecha` (`carta_fecha`);

--
-- Indices de la tabla `estandar6_ipvr_registros`
--
ALTER TABLE `estandar6_ipvr_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_e6_ipvr_empresa` (`empresa_id`),
  ADD KEY `idx_e6_ipvr_numero` (`empresa_id`,`numero`),
  ADD KEY `idx_e6_ipvr_peligro` (`peligro`),
  ADD KEY `idx_e6_ipvr_riesgo` (`nivel_riesgo`),
  ADD KEY `idx_e6_ipvr_riesgo_residual` (`nivel_riesgo_residual`),
  ADD KEY `fk_e6_ipvr_creador` (`creado_por`);

--
-- Indices de la tabla `estandar7_epp_entregas`
--
ALTER TABLE `estandar7_epp_entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_e7_epp_empresa` (`empresa_id`),
  ADD KEY `idx_e7_epp_trabajador` (`trabajador_id`),
  ADD KEY `idx_e7_epp_estado` (`estado`),
  ADD KEY `idx_e7_epp_fecha` (`fecha_entrega`),
  ADD KEY `idx_e7_epp_creador` (`creado_por`),
  ADD KEY `fk_e7_epp_entregado_usuario` (`entregado_por_usuario_id`);

--
-- Indices de la tabla `estandar7_mantenimiento_equipos`
--
ALTER TABLE `estandar7_mantenimiento_equipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_e7_mant_codigo_empresa` (`empresa_id`,`codigo_interno`),
  ADD KEY `idx_e7_mant_empresa` (`empresa_id`),
  ADD KEY `idx_e7_mant_tipo` (`tipo_elemento`),
  ADD KEY `idx_e7_mant_creador` (`creado_por`);

--
-- Indices de la tabla `estandar7_mantenimiento_registros`
--
ALTER TABLE `estandar7_mantenimiento_registros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_e7_mant_reg_empresa` (`empresa_id`),
  ADD KEY `idx_e7_mant_reg_equipo` (`equipo_id`),
  ADD KEY `idx_e7_mant_reg_fecha` (`fecha`),
  ADD KEY `idx_e7_mant_reg_creador` (`creado_por`);

--
-- Indices de la tabla `estandar7_programas_documentales`
--
ALTER TABLE `estandar7_programas_documentales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_e7_programa_empresa` (`empresa_id`,`programa_slug`),
  ADD KEY `idx_e7_programa_empresa` (`empresa_id`),
  ADD KEY `idx_e7_programa_slug` (`programa_slug`),
  ADD KEY `idx_e7_programa_creador` (`creado_por`);

--
-- Indices de la tabla `estandar7_recursos_analisis_consumo`
--
ALTER TABLE `estandar7_recursos_analisis_consumo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_e7_analisis_trimestre` (`empresa_id`,`anio`,`trimestre`),
  ADD KEY `idx_e7_analisis_empresa_anio` (`empresa_id`,`anio`),
  ADD KEY `idx_e7_analisis_creador` (`creado_por`);

--
-- Indices de la tabla `estandar7_recursos_presupuesto`
--
ALTER TABLE `estandar7_recursos_presupuesto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_e7_recursos_periodo` (`empresa_id`,`anio`,`item_slug`,`periodo`),
  ADD KEY `idx_e7_recursos_empresa_anio` (`empresa_id`,`anio`),
  ADD KEY `idx_e7_recursos_categoria` (`categoria_slug`),
  ADD KEY `idx_e7_recursos_item` (`item_slug`),
  ADD KEY `idx_e7_recursos_creador` (`creado_por`);

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
-- Indices de la tabla `movimientos_financieros`
--
ALTER TABLE `movimientos_financieros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_notificaciones_referencia` (`referencia_tipo`,`referencia_id`,`usuario_id`);

--
-- Indices de la tabla `pagos_suscripciones`
--
ALTER TABLE `pagos_suscripciones`
  ADD PRIMARY KEY (`id`);

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
-- Indices de la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  ADD PRIMARY KEY (`id`);

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
-- Indices de la tabla `whatsapp_prospectos`
--
ALTER TABLE `whatsapp_prospectos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_whatsapp_prospectos_etapa` (`etapa`),
  ADD KEY `idx_whatsapp_prospectos_prioridad` (`prioridad`),
  ADD KEY `idx_whatsapp_prospectos_telefono` (`telefono`),
  ADD KEY `idx_whatsapp_prospectos_seguimiento` (`proximo_seguimiento`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades_capacitacion`
--
ALTER TABLE `actividades_capacitacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `almacenamiento_archivos`
--
ALTER TABLE `almacenamiento_archivos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `almacenamiento_carpetas`
--
ALTER TABLE `almacenamiento_carpetas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `almacenamiento_compartidos`
--
ALTER TABLE `almacenamiento_compartidos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `asistencias_capacitacion`
--
ALTER TABLE `asistencias_capacitacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `calendar_connections`
--
ALTER TABLE `calendar_connections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_actas`
--
ALTER TABLE `capacitaciones_actas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_categorias_personalizadas`
--
ALTER TABLE `capacitaciones_categorias_personalizadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_cursos`
--
ALTER TABLE `capacitaciones_cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_intentos`
--
ALTER TABLE `capacitaciones_intentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_materiales`
--
ALTER TABLE `capacitaciones_materiales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_opciones`
--
ALTER TABLE `capacitaciones_opciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_preguntas`
--
ALTER TABLE `capacitaciones_preguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_progreso`
--
ALTER TABLE `capacitaciones_progreso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `capacitaciones_respuestas`
--
ALTER TABLE `capacitaciones_respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `control_documental_config`
--
ALTER TABLE `control_documental_config`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `control_documental_registros`
--
ALTER TABLE `control_documental_registros`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cpanel_admins`
--
ALTER TABLE `cpanel_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `demo_prospectos`
--
ALTER TABLE `demo_prospectos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `doc_asignacion_sst`
--
ALTER TABLE `doc_asignacion_sst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `encuesta_sociodemografica`
--
ALTER TABLE `encuesta_sociodemografica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `estandar2_planillas`
--
ALTER TABLE `estandar2_planillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estandar2_planilla_versiones`
--
ALTER TABLE `estandar2_planilla_versiones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estandar4_actividades`
--
ALTER TABLE `estandar4_actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `estandar4_planes`
--
ALTER TABLE `estandar4_planes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estandar4_seguimientos`
--
ALTER TABLE `estandar4_seguimientos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_centros_medicos`
--
ALTER TABLE `estandar5_centros_medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_evaluaciones_medicas`
--
ALTER TABLE `estandar5_evaluaciones_medicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_evaluaciones_medicas_soportes`
--
ALTER TABLE `estandar5_evaluaciones_medicas_soportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_historia_clinica_custodias`
--
ALTER TABLE `estandar5_historia_clinica_custodias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_perfiles_cargo`
--
ALTER TABLE `estandar5_perfiles_cargo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_procesos_perfil`
--
ALTER TABLE `estandar5_procesos_perfil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar5_restricciones_recomendaciones`
--
ALTER TABLE `estandar5_restricciones_recomendaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar6_ipvr_registros`
--
ALTER TABLE `estandar6_ipvr_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar7_epp_entregas`
--
ALTER TABLE `estandar7_epp_entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar7_mantenimiento_equipos`
--
ALTER TABLE `estandar7_mantenimiento_equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar7_mantenimiento_registros`
--
ALTER TABLE `estandar7_mantenimiento_registros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar7_programas_documentales`
--
ALTER TABLE `estandar7_programas_documentales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar7_recursos_analisis_consumo`
--
ALTER TABLE `estandar7_recursos_analisis_consumo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estandar7_recursos_presupuesto`
--
ALTER TABLE `estandar7_recursos_presupuesto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `grupos_personal`
--
ALTER TABLE `grupos_personal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `logs_actividad`
--
ALTER TABLE `logs_actividad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT de la tabla `movimientos_financieros`
--
ALTER TABLE `movimientos_financieros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `pagos_suscripciones`
--
ALTER TABLE `pagos_suscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `plan_caracteristicas`
--
ALTER TABLE `plan_caracteristicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

--
-- AUTO_INCREMENT de la tabla `preguntas_frecuentes`
--
ALTER TABLE `preguntas_frecuentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT de la tabla `solicitudes_empresas`
--
ALTER TABLE `solicitudes_empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `whatsapp_prospectos`
--
ALTER TABLE `whatsapp_prospectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Filtros para la tabla `capacitaciones_actas`
--
ALTER TABLE `capacitaciones_actas`
  ADD CONSTRAINT `fk_acta_curso` FOREIGN KEY (`curso_id`) REFERENCES `capacitaciones_cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_acta_intento` FOREIGN KEY (`intento_id`) REFERENCES `capacitaciones_intentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_acta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_categorias_personalizadas`
--
ALTER TABLE `capacitaciones_categorias_personalizadas`
  ADD CONSTRAINT `fk_cap_cat_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `solicitudes_empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cap_cat_usuario` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `capacitaciones_cursos`
--
ALTER TABLE `capacitaciones_cursos`
  ADD CONSTRAINT `fk_curso_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades_capacitacion` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_intentos`
--
ALTER TABLE `capacitaciones_intentos`
  ADD CONSTRAINT `fk_intento_curso` FOREIGN KEY (`curso_id`) REFERENCES `capacitaciones_cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_intento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_materiales`
--
ALTER TABLE `capacitaciones_materiales`
  ADD CONSTRAINT `fk_material_curso` FOREIGN KEY (`curso_id`) REFERENCES `capacitaciones_cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_opciones`
--
ALTER TABLE `capacitaciones_opciones`
  ADD CONSTRAINT `fk_opcion_pregunta` FOREIGN KEY (`pregunta_id`) REFERENCES `capacitaciones_preguntas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_preguntas`
--
ALTER TABLE `capacitaciones_preguntas`
  ADD CONSTRAINT `fk_pregunta_curso` FOREIGN KEY (`curso_id`) REFERENCES `capacitaciones_cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_progreso`
--
ALTER TABLE `capacitaciones_progreso`
  ADD CONSTRAINT `fk_progreso_curso` FOREIGN KEY (`curso_id`) REFERENCES `capacitaciones_cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progreso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `capacitaciones_respuestas`
--
ALTER TABLE `capacitaciones_respuestas`
  ADD CONSTRAINT `fk_respuesta_intento` FOREIGN KEY (`intento_id`) REFERENCES `capacitaciones_intentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_respuesta_pregunta` FOREIGN KEY (`pregunta_id`) REFERENCES `capacitaciones_preguntas` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `estandar4_actividades`
--
ALTER TABLE `estandar4_actividades`
  ADD CONSTRAINT `fk_estandar4_actividad_capacitacion` FOREIGN KEY (`actividad_capacitacion_id`) REFERENCES `actividades_capacitacion` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_estandar4_actividad_plan` FOREIGN KEY (`plan_id`) REFERENCES `estandar4_planes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar4_planes`
--
ALTER TABLE `estandar4_planes`
  ADD CONSTRAINT `fk_estandar4_representante` FOREIGN KEY (`representante_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_estandar4_sst` FOREIGN KEY (`sst_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar4_seguimientos`
--
ALTER TABLE `estandar4_seguimientos`
  ADD CONSTRAINT `fk_estandar4_seguimiento_plan` FOREIGN KEY (`plan_id`) REFERENCES `estandar4_planes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar5_centros_medicos`
--
ALTER TABLE `estandar5_centros_medicos`
  ADD CONSTRAINT `fk_estandar5_centro_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar5_evaluaciones_medicas`
--
ALTER TABLE `estandar5_evaluaciones_medicas`
  ADD CONSTRAINT `fk_estandar5_eval_centro` FOREIGN KEY (`centro_medico_id`) REFERENCES `estandar5_centros_medicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_estandar5_eval_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_estandar5_eval_perfil` FOREIGN KEY (`perfil_cargo_id`) REFERENCES `estandar5_perfiles_cargo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_estandar5_eval_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar5_evaluaciones_medicas_soportes`
--
ALTER TABLE `estandar5_evaluaciones_medicas_soportes`
  ADD CONSTRAINT `fk_e5_soporte_centro` FOREIGN KEY (`centro_medico_id`) REFERENCES `estandar5_centros_medicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e5_soporte_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e5_soporte_eval` FOREIGN KEY (`evaluacion_id`) REFERENCES `estandar5_evaluaciones_medicas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e5_soporte_perfil` FOREIGN KEY (`perfil_cargo_id`) REFERENCES `estandar5_perfiles_cargo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e5_soporte_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar5_historia_clinica_custodias`
--
ALTER TABLE `estandar5_historia_clinica_custodias`
  ADD CONSTRAINT `fk_e5_custodia_centro` FOREIGN KEY (`centro_medico_id`) REFERENCES `estandar5_centros_medicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_e5_custodia_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar5_perfiles_cargo`
--
ALTER TABLE `estandar5_perfiles_cargo`
  ADD CONSTRAINT `fk_estandar5_perfil_centro` FOREIGN KEY (`centro_medico_id`) REFERENCES `estandar5_centros_medicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_estandar5_perfil_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar5_procesos_perfil`
--
ALTER TABLE `estandar5_procesos_perfil`
  ADD CONSTRAINT `fk_estandar5_proceso_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar5_restricciones_recomendaciones`
--
ALTER TABLE `estandar5_restricciones_recomendaciones`
  ADD CONSTRAINT `fk_e5_restr_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e5_restr_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar6_ipvr_registros`
--
ALTER TABLE `estandar6_ipvr_registros`
  ADD CONSTRAINT `fk_e6_ipvr_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar7_epp_entregas`
--
ALTER TABLE `estandar7_epp_entregas`
  ADD CONSTRAINT `fk_e7_epp_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e7_epp_entregado_usuario` FOREIGN KEY (`entregado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e7_epp_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar7_mantenimiento_equipos`
--
ALTER TABLE `estandar7_mantenimiento_equipos`
  ADD CONSTRAINT `fk_e7_mant_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar7_mantenimiento_registros`
--
ALTER TABLE `estandar7_mantenimiento_registros`
  ADD CONSTRAINT `fk_e7_mant_reg_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_e7_mant_reg_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `estandar7_mantenimiento_equipos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `estandar7_programas_documentales`
--
ALTER TABLE `estandar7_programas_documentales`
  ADD CONSTRAINT `fk_e7_programa_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar7_recursos_analisis_consumo`
--
ALTER TABLE `estandar7_recursos_analisis_consumo`
  ADD CONSTRAINT `fk_e7_analisis_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estandar7_recursos_presupuesto`
--
ALTER TABLE `estandar7_recursos_presupuesto`
  ADD CONSTRAINT `fk_e7_recursos_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

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
