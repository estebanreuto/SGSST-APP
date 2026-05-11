-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 11-05-2026 a las 20:05:20
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
  `estado` enum('programada','en_proceso','completada','cancelada','ejecutada','reprogramada','no_ejecutada') DEFAULT 'programada',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `modalidad` varchar(50) DEFAULT 'Virtual',
  `lugar_exacto` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL
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
(1, 2, NULL, 'pendiente_firma', 'data:image/png;base64,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', NULL, '2026-05-09 23:01:14', NULL, NULL);

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
(18, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-08 20:18:32'),
(19, 2, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-08 22:12:14'),
(20, 7, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 01:54:05'),
(21, 7, 'LOGOUT', 'Cierre de sesión', '::1', '2026-05-09 01:55:05'),
(22, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 02:18:50'),
(23, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 14:23:54'),
(24, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '192.168.18.23', '2026-05-09 15:02:09'),
(25, 2, 'LOGIN_OK', 'Ingreso exitoso con 2FA', '::1', '2026-05-09 22:28:17');

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
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `titulo`, `mensaje`, `enlace`, `leida`, `fecha_creacion`) VALUES
(1, 1, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', 0, '2026-05-09 23:01:14'),
(2, 5, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', 0, '2026-05-09 23:01:17'),
(3, 6, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', 0, '2026-05-09 23:01:20'),
(4, 7, 'Firma Requerida: Acta SG-SST', 'El Responsable SG-SST (WILLMER ESTEBAN REUTO ROMERO) ha enviado un acta para tu aprobación.', 'estandar1.php', 0, '2026-05-09 23:01:22');

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
(5, 5, 'TX-MIGRADA-5', 250000.00, 'Enterprise', 'APPROVED', '2026-05-09 01:50:46');

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
(1, 'Básico', 2000000.00, 0.00, 15, 0, 'btn-outline'),
(2, 'Pro SG-SST', 2500000.00, 0.00, 50, 1, 'btn-solid'),
(3, 'Enterprise', 3000000.00, 0.00, 999, 0, 'btn-outline');

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
(21, 2, '8a5e910433dec47095911812218e1230a69e4e00ef506de5c38a015d028ac64d', NULL, NULL, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 22:28:17', '2026-05-10 13:28:17', 1);

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
(2, 'Esteban', 'Reuto', '1116856979', 'estebanreuto27@gmail.com', '3012994599', 'Cra 3 # 13A - 55', 'Tame - Arauca', '', '', 'data:image/png;base64,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', 'aprobada', '2026-05-08 21:52:19', 1, 0),
(3, 'Esteban 2', 'Reuto', '5050', 'contacto.funness@gmail.com', '3012994599', 'La Cira Barrancabermeja 687039', 'Tame', '', '', 'data:image/png;base64,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', 'aprobada', '2026-05-08 22:19:44', 2, 0),
(4, 'Wilmer', 'Reuto', '20204232', 'dannareuto@gmail.com', '3012994599', 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', 'aprobada', '2026-05-09 01:09:36', 3, 0),
(5, 'Wilmer', 'Reuto', '2020435', 'sistemas.p.besst@gmail.com', '3012994599', 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAArUAAACWCAYAAADAB3DwAAAQAElEQVR4Aezd34ocRRvH8XpCeAkIRrISJWAwkCP1QDwT8QoMXkLOArkDwQsI5A4Ez3IJcb2CIJ4KYo6EhAgiiwkoCHsQ3Ndfd55NbW9Pz+x2V3dX1XexpmemZ+rPp9rMb2t7ey8c8YUAAggggAACCCCAQOYCFwJfCCCAAAJbBNiNAAIIILB2AULt2meI/iGAAAIIIIAAAjkILNxHQu3CE0DzCCCAAAIIIIAAAuMFCLXjDakBAQTSC9ACAggggAACgwKE2kEediKAAAIIIIAAArkI1N1PQm3d88/oEUAAAQQQQACBIgQItUVMI4NAIL0ALSCAAAIIILBmAULtmmeHviGAAAIIIIBATgL0dUEBQu2C+DSNAAIIIIAAAgggMI0AoXYaR2pBIL0ALSCAAAIIIIDARgFC7UYadiCAAAIIIIBAbgL0t14BQm29c8/IEUBgIYFr166Fvb29hVqnWQQQQKBMAUJtmfPKqJIIUCkC0wgcHh6Go6OjcPPmzWkqpBYEEEAAgUCo5SBAAAEEZhYws6bFFy9eNFtuEChKgMEgsJAAoXYheJpFAIF6BZ4/f17v4Bk5AgggkEiAUJsIlmqTCFApAkUIcD5tEdPIIBBAYGUChNqVTQjdQQCBegTM2tMQ6hkxI51HgFYQqFOAUFvnvDNqBBBYUEC/JKbm33nnHW0oCCCAAAITCBBqJ0CsqQrGigAC0wk8fvx4usqoCQEEEKhcgFBb+QHA8BFAYF4Bzqed13uh1mgWAQQWECDULoBOkwgggIAZ59NyFCCAAAJTChBqp9Scoy7aQACBrAU4nzbr6aPzCCCwYgFC7Yonh64hgEC5ApxPm3ZuqR0BBOoTINTWN+eMGAEEFhLgfNqF4GkWAQSqECDUnnmaeQMCCCAwTsCM82nHCfJuBBBA4LQAofa0Cc8ggAACSQSqOp82iSCVIoAAApsFCLWbbdiDAAIITCbw9ttvH9fF+bTHFNxBAAEEJhPIMdRONngqQgABBOYS+Pfff5umLlzgn90GghsEEEBgYgH+dZ0YlOoQQACBroCv0irQ/vnnn93diR5TLQIIIFCXAKG2rvlmtAggMLOAAq2v0hJoZ8anOQQQqErgXKG2KiEGiwACCIwQ8ECrVdoR1fBWBBBAAIEtAoTaLUDsRgABBM4pELRKq/cq0LJKKwkKAgggkE6AUJvOlpoRQKBiAQVaX6Ul0FZ8IDB0BBDYIjDdbkLtdJbUhAACCBwLeKDVKu3xk9xBAAEEEEgmQKhNRkvFCCCwtMBS7WuVVm0r0LJKKwkKAgggkF6AUJvemBYQQKAiAQVaX6Ul0FY08QwVgXwFiuk5obaYqWQgCCCwBgEPtFqlXUN/6AMCCCBQiwChtpaZZpwILCFQWZtapdWQFWhZpZUEBQEEEJhPgFA7nzUtIYBAwQIKtL5KS6AteKIZGgIJBKhyGgFC7TSO1IIAApULeKDVKm3lFAwfAQQQWESAULsIO40iMJcA7aQW0ArtlStXmmbMLLBK21BwgwACCMwuQKidnZwGEUCgJAFfodWYjo6Owt7enu5SEEAgJwH6WoQAobaIaWQQCCCwhMDVq1ePmzWz5j7BtmHgBgEEEJhdgFA7OzkNVibAcAsV+Pzzz8PLly+b0V28eDE8f/48+Pm0CrY6LaHZyQ0CCCCAwCwChNpZmGkEAQRKE/jll1+aISnQHhwcNPd1Pq1Zu2Ibn5bQ7OQGAQQGBNiFwHgBQu14Q2pAAIHKBPwXwzRsD7S6r6IVW21VWK2VAgUBBBCYR4BQO48zrSwoQNMITCkQn0f74sWL3qrNWK3theFJBBBAIKEAoTYhLlUjgEBZAgq0fh7thx9+uHFwrNZupGHHegXoGQLZCxBqs59CBoAAAnMIxIFW59E+evRosFkzVmsHgdiJAAIITCxAqJ0YlOp6BHgKgcwFulc66J5H2zc8Vmv7VHgOAQQQSCdAqE1nS80IIFCIQN+VDgoZGsNYkQBdQQCBcQKE2nF+vBsBBAoXGLrSwbah+3VrubzXNin2I4AAAuMFCLXjDTOogS4igMB5BHQerb9v05UOfH/fVtet9ee5vJdLsEUAAQTSCBBq07hSKwIIZC6gQLvLlQ4yHybdjwW4jwACWQsQarOePjqPAAIpBOJAu8uVDob6wCkIQzrsQwABBKYTINROZzlUE/sQQCATgfNc6WBoaJyCMKTDPgQQQGA6AULtdJbUhAACBQhwpYMlJ5G2xwro3G39cqOX+/fvj62S9yOQjQChNpupoqMIIJBaQEHA29jlWrT+2m1bTkHYJsT+MQIKsio6frtX2vjhhx/GVM17EchKoJpQm9Ws0FkEEJhdQOfReqPnudKBv7dvyykIfSo8N1YgDrLdMKu69c3Uw4cPdZeCQBUChNoqpplBIoDAkIACLVc6aIS4WbmAB9m+VVmFWDNrRmBmIf5mqnmSGwQKFyDUFj7BDA8BBIYF4kA79koHQy0pcGh/34qanqcgsElgW5DVTxZUjo6Ogorqif9Msx5TEKhBYL5QW4MmY0QAgawEpr7SwdDgWTUb0mFfn4CH2e43QvoGSUVBtu+4MmtXa/vq5DkEShYg1JY8u4wNAQQGBdZ4pYPBDrOzCoGhMOtBti/MskpbxeHBIAcECLUDOOxCAIFyBXROoo9uyisdeJ1DW4WWof3sq09Ax4SOSZV4ZdZXZD3MbpLZ29trdpmxSttAcFO6QO/4CLW9LDyJAAIlC+g8Wh+fwoLfZ4vA3AIeZuMgqz54mO1bkdX+bvFVWjNCbdeGx/UIEGrrmWtGigAC/wloJWzwSgf/vYb/EEgtoJVVHYtjw6z6qbq0Vdk1BOu1FARKEyDUljajjAcBBHoFvvjii6AQ4Ts//fTT8OjRI384y9asXUXzVbVZGqWR1Qj4qqyOw/gY8FVZ/dRgTCg1a4+v1QyYjhQtsMbBEWrXOCv0CQEEJhW4du1a+PHHH4/rVHj4/vvvjx9zpx6BOFgqXO5axgh5m1Osyvb1wwMyl/Hq0+G5mgQItTXNNmNFIAuBaTup0HJ4eNhUeunSpaBA2zxY4MasXUnzELJAF6pr0gOljgOVbrBMBaJ2dVpAX5u+MjtmVTZVv6kXgZwFCLU5zx59RwCBjQJ9pxv8/vvvG1/PjjIEFCYVJL30hViFyl3LWVW8fbUbf/Oi9vQNlQph9qyqvL5XgCdPCRBqT5HwBAII5C7QPd1A589yukHus9rffw+Ru4RYBUoVhcpdiodSs3aFvb8H7bPeD4XZ9pn21sOs2muf4RYBBFIJEGpTyVIvAvkKZN1zBdru6QZrCbQEm/GHlofH84TYs7a+LdTGfYnDrJkFwmzgC4HZBQi1s5PTIAIIpBJQ0IkDLacbpJKet14/N1XzG4dH74UCpIpWYVX0zYOK7z/PVm36+7p1eZjt9sX7oF/Y6r7H62JbkgBjWZsAoXZtM0J/EEDgzAK5nD+rMHTmwVX8BgVLBVlfMXUKhUcVBVgVBUgV3z/l1qw99UBz5/3ZFGZT9WHK8VAXAiULEGpLnl3Glq0AHd9dQKcb5HK5Lg9nZm1Q2n2Udb3Sw6N7afRzhVi1pfa9bTNrrm+sIOvP6TXd/ug5CgIILCtAqF3Wn9YRQGCEgFbx4tMNtGo3ojreurCAwqTmtC88zrUKqj7E7SvMxiweZufqT9w2908J8AQCJwQItSc4eIAAAjkI5HK6QQ6Wa+ijfrS/dJiVg/oRB1o9p+JBVt80rTnMylChXH2mIFCjAKG2xllnzNsFeMVqBbqnG+R0uS4PTGacfqADTCFSQSxeDfUAOWd47OuH+rdEX9TuWYvCtll7TOkYkynh9qyK5b3+9u3bQUXHwo0bN5r75Y3y5IgItSc9eIQAAisWUKDtnm6wlst1nYVtzsB2ln7N9dq+EDl3gPQ+KADGodrMsrwcl664IMPw6kvhVmFG5dVTSTZUug6BTz75JKjoePayv78f9O+jfrL15MmT8ODBg3V0NmEvCLUJcakaAQSmE9A/1HGg5XJd09nOVZMHyThEKohppXGuoN/Xh3j8Codz9SVud4r76rcszV6v2irc6v8dL4TcKaSXr+ODDz5ofoHR5/Xp06fh6X/Fe2Zm4datW0HHcw1h1sdNqHUJtisToDsItAJaZdA/3O2jEHS6QY6BVmHKx1DbVmPXHC4VZr39bh/M2vDn86GA7fdz3irIeLg1OzlGhVyCbb6z62H2jz/+6B2EWZ1h1jEItS7BFgEEVieg0w1yuVzXNjyFCb3G7GTI0HOlFg+TcZg1s6DApVXF1OPua19tKryqD7rvRc/N0Sdvb7LtQEUKtyoaq8Zn1h57fiwOvJVdKxJQkNU3IvqmbFOYvXjxYrhz5051K7PdaSLUdkV4jAACqxDQP+Dx6Qb6YF5Fx+jEVoG+MGnWhlmFrK0VjHiBt63jJw7TqlLBTseRwqtCgoc7Mwt6Tq8ptWh8qe1LtVtqXAqzOo4VZP1YVV/eeuutcP36dd1tyr1798LBwUG4f/9+87jmG0JtzbM/PHb2IrCIQCmnG3Tx/EPJrF0t6+4v4bEHyjhMms0bZuO2ZepB1sOsnlM/4/kg7EmFshYBfcPlYTbu07vvvtv8lOOjjz4Kz549a3Yp0N69e7e5z00IhFqOAgQQWI1A93QDnT+r395dTQcn6IhWzCaoZlVVKCTqQ7gbKBUkUwfGTW17mO3z9n6aWfPj2nGYvBuB8QK+Kqv/j/wbLtXqQVb/Lz1+/FhPhYcPH4bvvvuuKQTahuT4hlB7TMEdBBBYUkCBtnu6QSmBVisvS9qmbFtj85Do7Xig9McptmpXASBu26xdFVYA6Auz6ofeo61K6sCtNigIDAl4mNUpBvHrPMx6kI336f5nn30WVHSf8lqAUPvaYnX36BACtQgoaMSBNserGwzNla+8mNnQy7La5yukPjZ13sPspkCp14wp3qaOl752t4VUBWFvX8HX77NFYE4BD7I6juMwa2ZhW5ids585tkWozXHW6DMChQiUev5sPD364NJjszJ+1O3BMl4hnSvMxm3K9FW7W3/Jy/vsQVjv0/spCMwp4GE2DrJq34OsvinbtDKr11G2CxBqtxvxCgQQSCCg0w1KuVzXJp54ZVAfWJtel8vzGk8cLM3aH/enXpmN25SVQqlWWndpV4E2fr/eu8v71E7JRXNZ8viWHtvNmzeDipz1ja3KpjBLkJ1utgi1Q5bsQwCBJAL6Bz4+3UABJUlDC1aqDzNfGTTL+7QDBUPNmY9HrJqzFEFdbclO7cVh1KwN0Gp311CqeuI6zvJejbHE8r///a8ZluZSPs0Dbs4toOCqIksds150rKnIOa7cV2W1jzAby0xzn1A7jSO1IIDADgI1nG7gDP5hZpb3aQf6kI6DoVY69YHs49R2iqIw6225ner19s4aoFWX12PWBmLVV3vRamEcbOWkQFa7y7bxK7iqyEpmXvT/goofa3316LUeZgmyfULTPUeonc6Sv8XIfAAAClJJREFUmhBAYECge7pBiZfr8uHrg8/vnzWM+fuW3moM+jD2fpi1wXDXVVJ/37ZtHGbj13qYPWt7Xp/XpXpynQMfw9RbBdtLly4dV6tApvl+7733jp+r9Y6Cq4o8dPx7UXBVkVWfjZmF7mv1epVff/01EGb71KZ/buWhdvoBUyMCCMwvoEDbPd2glMt1dTX1YegffPpA6+5f+2MPhT4G9TdFMPR24lVgb0tuZw2zeq/s4/rOW4/qKr3oCiPyMWtPjdF8//PPP00wk2Pp41dwVdFYPYxqKxMVefQZmPWHV33jpPCq0vc+nptHgFA7jzOtIFClwO3bt4M+NOJAqw/TkjH8w9CsDQuzjHWiRjRXcShUmNUH/HkCpndJ4dWL6ldwUOlrZ0xbqjO2V13eB7abBRTGZGX2+niVozw1Xyqb373+PQquKhpPXDRmFY21bxRmhNc+l7U/R6hd+wzRPwQyFbhx40bY398P+tAws3Dr1q1QeqDVh6amyyy/82gVXjRX3n994O8SZj2waqs6ZBAXhVcvXr/aUJkqNKs91aeiOhXUdJ+yu4DMNOdmFsyseaPmS0W+XjTHu5T3338/vL+lNI1McKPQ6sX76VuNSWVTM2aE1002OT6/LdTmOCb6jAACCwr46uxff/3V9OLy5ctBH5gPHjxoHpd6ow96H5vG6/dz25qdDOQKq140Rg8LvvXAqq0C0NB4zSwodKooaOwSmsPAl/qjdv0lqndsnV5XrVsduyqaH7M23MYWmuNdyt9//x22FT+Gxm7VVy9xX+P7ZoTX2KPU+4TaUmeWcSGwgEDf6uyTJ08W6Mm8Te7t7TUr0mrV7HQQ0PNrLwoq6qO2cchQaPSifXrNpmJmx6FVAdODhm8VlhQ6VcLILwVa749Zml9iG9nF7N+u+dLcvfHGG+HNN99sipk1K7lmw9ulBh8fu+q7F41F57uqLNU32k0vQKhNb0wLCBQvUOvqrE9sHK704enPl7Y1s51D6xTBNfR8adVYwaUW8x6C2Z/67bffwtOnT5ui43uX4mFy7NbDdN9Wx0G3foVWL7ND0eB2gcSvINQmBqZ6BEoXUKD1c2c1Vp07W8PqrMaqohVDbVX0Ya9tjkUrq93SDQwan8Kql7nHqUCrVWNvV/1Vn/wx2/IEnr4K031bhdfyRsyIxggQasfo8V4EKhdQoFOgFYNZ+yPghc6dVRdmLQpYWinyFUMFwFk7MHFjHlTj7cRNjKpOx5oHWrP2WFNfR1XKmxFAoCgBQm1R08lgEJhHQKuzcaDzXwabp/XlW4kDlnqjFUNtKWkE4mPN7OQvsqVpkVoRKFWg7HERasueX0aHwOQC/stgqtisvVRXLacbdFdnzVgx1HGQqri3169vHjjdwDXYIoBAV4BQ2xXhMQII9ApodVYrlJsu1dX7poKe1Nj9x98aFgFLCulK11und3C6QTpvakagBAFCbQmzyBgQSCzgq7M6f9SM1VkCVtoDrnu6gbzTtkjtCMwmQEMJBQi1CXGpGoHcBVid3Quszs53FHO6wXzWtIRAiQKE2hJnlTHVKTDxqFmdvXLiDypotZAff098kEXVdU830OkdeEdA3EUAga0ChNqtRLwAgboEWJ1ldXbuI16BVqe2qF0zfvlODpR0AtRcrgChtty5ZWQInFkgXp3Vm2v6Qwr+o2/ClWZ+ntJnztUN5rGnFQRKFCDUljirjGkhgbyb1WqZX9nArF0tq+EPKXiw4tzZeY9fuWM+rzmtIVC6AKG29BlmfAhsEdDpBvFvm2t1tuTVMoUpBXiNWSUOVmZtmI/P5dTrNxXV0y2qk3IlxAYykqEfinrs7manzf11bAsVYFgIJBIg1CaCpVoEchDw0w3UV7P2Ul2lrc4qTKl4yFKY8lMMNO646Hl/nW/1+k1Fr++WuD7utwIykqGb6rH2mPHXweRAQQCBaQQItdM4Uss6BOjFjgJandVqmZ9ucPny5aDV2RICrQKsigcohSmVHWnO9DIzC2Yni35rn3IhxAZmdsrVjEB7CoUnEEBglAChdhQfb0YgL4GvvvoqKPDt7+83l6sya1dnc/wztxqHisK5B1htFWBVNs1MHLbi+7pk11mLvhHoFp26QPkzxAYykq3Z63Cr5zbNEc+nFqB+BMoUINSWOa+MCoETAgqzV69eDd9+++3xHxPIcXU2DrAKrir+o+wTA/7vgZkdrxYqUHmJw1Z8P/CVXEBBVt9ImL0Ot8kbpQEEEKhGgFBbzVTPM1BaWZdAHGZfvnzZdO7ixYvhzp07IafVWQ+zmwKsBmZmTYj18KoA5aE18LUaAc2J5mY1HaIjCCBQjAChtpipZCAItAIeZPWjeK3MKsx6kFXgOzg4CPfv329fvPLbbWFWq34ak4qCkgLTyodE9xCQAAUBBBIIEGoToFIlAksIeJj1IKs+eJjNKciq3yo6X9bMmtVXhde4KMSqEGIDXwgggAACrwQIta8gitkwkKoEFGQ//vjj5pqgpYRZn0AF1k3FX8MWAQQQQAABFyDUugRbBDISUJj1X/x69uxZ03NfldUKZo4rs80guEFgJgGaQQCB8gQIteXNKSMqWCAOszpXVkP1MEuQlQYFAQQQQKBWAULt5DNPhQhMK/DNN9+Ea9eunTjFQEH2+vXrzVUMCLPTelMbAggggECeAoTaPOeNXhcuoCCrP2Gr8vXXX4fDw8NmxAqzuhyXguxPP/2UzVUMms5zg0AswH0EEEBgYgFC7cSgVIfAeQW6QVZ/wlZFfyRBxcNsLpfjOq8D70MAAQQQQOA8AiWG2vM48B4EFhP48ssvg6/IKsSqKMSq3Lt3r/kjCfpDCYTZxaaIhhFAAAEEMhAg1GYwSXSxbIGff/45bAqyd+/eLXvwjG5BAZpGAAEEyhIg1JY1n4wmQwGtwsYrsgTZDCeRLiOAAAIILC6QJNQuPio6gEBmAgTZzCaM7iKAAAIIrE6AULu6KaFDCCBQiQDDRAABBBCYUIBQOyEmVSGAAAIIIIAAAghMKbB7XYTa3a14JQIIIIAAAggggMBKBQi1K50YuoUAAukFaAEBBBBAoBwBQm05c8lIEEAAAQQQQACBqQWyqY9Qm81U0VEEEEAAAQQQQACBTQKE2k0yPI8AAukFaAEBBBBAAIGJBAi1E0FSDQIIIIAAAgggkEKAOncTINTu5sSrEEAAAQQQQAABBFYsQKhd8eTQNQTSC9ACAggggAACZQgQasuYR0aBAAIIIIAAAqkEqDcLAUJtFtNEJxFAAAEEEEAAAQSGBAi1QzrsQyC9AC0ggAACCCCAwAQChNoJEKkCAQQQQAABBFIKUDcC2wUItduNeAUCCCCAAAIIIIDAygUItSufILqXXoAWEEAAAQQQQCB/AUJt/nPICBBAAAEEEEgtQP0IrF6AULv6KaKDCCCAAAIIIIAAAtsECLXbhNifXoAWEEAAAQQQQACBkQKE2pGAvB0BBBBAAIE5BGgDAQSGBQi1wz7sRQABBBBAAAEEEMhAgFCbwSSl7yItIIAAAggggAACeQv8HwAA//+5RYy4AAAABklEQVQDAKIrWaNBvgeuAAAAAElFTkSuQmCC', 'aprobada', '2026-05-09 01:50:46', 3, 0),
(6, 'Wilmer', 'Reuto', '20204232', 'estebanreuto4@gmail.com', '3012994599', 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', 'pendiente', '2026-05-10 03:22:03', 1, 0);

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
(2, 1, 'WILLMER ESTEBAN', 'REUTO ROMERO', '2020', 'wreuto@estudiantes.areandina.edu.co', '3109998877', 'sst', 'si', 'Profesional', 'L-12345', NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', '2026-05-08 22:11:22', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'uploads/perfiles/user_2_1778192121.jpeg'),
(3, 1, 'Esteban', 'Reuto (Trab)', '3030', 'estebanreuto27@gmail.com', '3205554433', 'trabajador', NULL, NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, NULL, '2026-03-24 02:45:36', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 'uploads/perfiles/user_3_1778194305.jpeg'),
(5, 3, 'Esteban 2', 'Reuto', '5050', 'contacto.funness@gmail.com', '3012994599', 'representante', NULL, NULL, NULL, NULL, NULL, 'La Cira Barrancabermeja 687039', 'Tame', '', '', 'data:image/png;base64,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', '2026-05-08 22:20:04', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 4, 'Wilmer', 'Reuto', '20204232', 'dannareuto@gmail.com', '3012994599', 'representante', NULL, NULL, NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', '2026-05-09 01:10:06', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 5, 'Wilmer', 'Reuto', '2020435', 'sistemas.p.besst@gmail.com', '3012994599', 'representante', NULL, NULL, NULL, NULL, NULL, 'Cra 3 # 13 A 55', 'Cristo Rey', '', '', 'data:image/png;base64,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', '2026-05-09 01:52:38', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
-- Indices de la tabla `asistencias_capacitacion`
--
ALTER TABLE `asistencias_capacitacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cpanel_admins`
--
ALTER TABLE `cpanel_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

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
-- Indices de la tabla `movimientos_financieros`
--
ALTER TABLE `movimientos_financieros`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

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
-- AUTO_INCREMENT de la tabla `asistencias_capacitacion`
--
ALTER TABLE `asistencias_capacitacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cpanel_admins`
--
ALTER TABLE `cpanel_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `doc_asignacion_sst`
--
ALTER TABLE `doc_asignacion_sst`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `movimientos_financieros`
--
ALTER TABLE `movimientos_financieros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pagos_suscripciones`
--
ALTER TABLE `pagos_suscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `solicitudes_empresas`
--
ALTER TABLE `solicitudes_empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `super_admins`
--
ALTER TABLE `super_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
