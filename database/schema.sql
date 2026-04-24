-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-12-2025 a las 19:07:15
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
-- Base de datos: `2_colegio_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nombre_completo` varchar(160) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `user_id`, `nombre_completo`) VALUES
(1, 1, 'Administrador Principal'),
(2, 5, 'Admin 2');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admisiones`
--

CREATE TABLE `admisiones` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nombre_completo` varchar(160) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admisiones`
--

INSERT INTO `admisiones` (`id`, `user_id`, `nombre_completo`) VALUES
(1, 4, 'Marcelo Morales'),
(3, 8, 'Marisol');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asesores`
--

CREATE TABLE `asesores` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nombre_completo` varchar(160) NOT NULL,
  `team` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `asesores`
--

INSERT INTO `asesores` (`id`, `user_id`, `nombre_completo`, `team`) VALUES
(1, 2, 'Ana Perez Rojas', 'ANA'),
(2, 3, 'Daniel Garcia', 'DANIEL');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL,
  `nombres` varchar(120) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `celular` varchar(20) NOT NULL,
  `email` varchar(160) NOT NULL,
  `departamento` varchar(120) NOT NULL,
  `provincia` varchar(120) NOT NULL,
  `distrito` varchar(120) NOT NULL,
  `nivel` enum('INICIAL','PRIMARIA','SECUNDARIA') NOT NULL,
  `copia_dni_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docentes`
--

INSERT INTO `docentes` (`id`, `nombres`, `apellidos`, `dni`, `celular`, `email`, `departamento`, `provincia`, `distrito`, `nivel`, `copia_dni_path`, `created_at`, `deleted_at`) VALUES
(1, 'Marcos', 'Sanchez', '87858785', '999888999', 'marcos@gmail.com', 'Lima', 'Lima', 'SJL', 'SECUNDARIA', 'uploads/dni/692a2ec1a71ca_diapo44jpeg-artguru.png', '2025-11-28 19:50:55', NULL),
(2, 'Maribel', 'Suarez', '88877788', '999888555', 'maribel@gmail.com', 'Lima', 'Lima', 'Miraflores', 'INICIAL', 'uploads/dni/6929fd82d209e_diapo43.jpg', '2025-11-28 19:52:34', NULL),
(3, 'Karen', 'Rodriguez', '78945612', '987541787', 'ka@gmail.com', 'Lima', 'Lima', 'SJL', 'PRIMARIA', 'uploads/dni/6929fda3683e6_diapo38.png', '2025-10-28 19:53:07', NULL),
(5, 'Jose', 'Mendez', '88855578', '995684000', 'jose@gmail.com', 'Lima', 'Lima', 'SJL', 'PRIMARIA', 'uploads/dni/692a0135c2034_diapo45-artguru.png', '2025-11-28 20:08:21', NULL),
(8, 'Manolo', 'Andres', '88899957', '998589569', 'manolo@gmail.com', 'Lima', 'Lima', 'SJL', 'SECUNDARIA', 'uploads/dni/692a1caac19ed_diapo38.png', '2025-11-28 22:05:30', NULL),
(9, 'Luis', 'Alvarez', '88556589', '999965202', 'luis@gmail.com', 'Lima', 'Lima', 'SJL', 'SECUNDARIA', 'uploads/dni/692a2ee475cd6_diapo44jpeg-artguru.png', '2025-11-28 22:21:20', NULL),
(10, 'Madison', 'Mejia', '88855520', '999562301', 'madison@gmail.com', 'Lima', 'Lima', 'SJL', 'SECUNDARIA', 'uploads/dni/692a30c4b1c09_diapo44jpeg-artguru.png', '2025-11-28 23:31:16', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente_especialidad`
--

CREATE TABLE `docente_especialidad` (
  `teacher_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docente_especialidad`
--

INSERT INTO `docente_especialidad` (`teacher_id`, `specialty_id`) VALUES
(1, 7),
(1, 8),
(1, 9),
(2, 4),
(3, 3),
(8, 8),
(9, 2),
(9, 7),
(9, 9),
(10, 2),
(10, 7),
(10, 8),
(10, 9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidades`
--

INSERT INTO `especialidades` (`id`, `nombre`) VALUES
(8, 'AIP'),
(7, 'Arte y cultura'),
(9, 'Ciencia y Tecnología'),
(2, 'Comunicación'),
(4, 'DPCC'),
(5, 'Educación física'),
(6, 'Educación religiosa'),
(3, 'EPT'),
(1, 'Matemática');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `tipo_pago` enum('CUOTA #1','CUOTA #2','CUOTA #3','CUOTA #4','CONTADO') NOT NULL,
  `forma_pago` enum('YAPE','PLIN','TRANSFERENCIA BANCARIA','DEPÓSITO EN AGENTE') NOT NULL,
  `fecha_pago` date NOT NULL,
  `banco` enum('BANCO DE LA NACIÓN','BCP','BBVA','INTERBANK','SCOTIABANK') NOT NULL,
  `codigo_operacion` varchar(80) NOT NULL,
  `titular_pago` varchar(160) NOT NULL,
  `voucher_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `sale_id`, `monto_total`, `tipo_pago`, `forma_pago`, `fecha_pago`, `banco`, `codigo_operacion`, `titular_pago`, `voucher_path`, `created_at`, `deleted_at`) VALUES
(2, 2, 100.00, 'CUOTA #1', 'YAPE', '2025-11-30', 'BCP', '404040', 'Marcos', 'uploads/vouchers/6929ffd311e71_diapo39-artguru.png', '2025-11-28 20:02:27', NULL),
(3, 2, 100.00, 'CUOTA #2', 'YAPE', '2025-12-12', 'BCP', '400404', 'Marcos', 'uploads/vouchers/692a0082df616_diapo39-artguru.png', '2025-11-28 20:05:22', NULL),
(5, 3, 200.00, 'CUOTA #1', 'TRANSFERENCIA BANCARIA', '2025-12-05', 'BANCO DE LA NACIÓN', '71040', 'Joe', 'uploads/vouchers/692a0135c231e_diapo46-artguru.png', '2025-11-28 20:08:21', NULL),
(6, 2, 100.00, 'CUOTA #3', 'YAPE', '2025-12-05', 'BCP', '40404040', 'Marcos', 'uploads/vouchers/692a029345f0a_diapo43-artguru.png', '2025-11-28 20:14:11', NULL),
(7, 4, 100.00, 'CUOTA #1', 'PLIN', '2025-11-21', 'BANCO DE LA NACIÓN', '404', 'Maribel', 'uploads/vouchers/692a04350614b_diapo46-artguru.png', '2025-11-28 20:21:09', NULL),
(15, 9, 400.00, 'CONTADO', 'YAPE', '2025-12-05', 'BANCO DE LA NACIÓN', '54545', 'Karen', 'uploads/vouchers/692a16e3cf108_diapo33.png', '2025-10-28 21:40:51', NULL),
(16, 10, 400.00, 'CONTADO', 'YAPE', '2025-12-05', 'BANCO DE LA NACIÓN', '54545', 'Manolo', 'uploads/vouchers/692a2060b755a_IMG_20251104_100824234.jpg', '2025-11-28 22:21:20', NULL),
(17, 11, 400.00, 'CONTADO', 'YAPE', '2025-11-28', 'BBVA', '54545', 'Karen', 'uploads/vouchers/692a2320f2815_diapo32.jpg', '2025-11-28 22:33:04', NULL),
(18, 12, 150.00, 'CUOTA #1', 'YAPE', '2025-12-02', 'BCP', '123456879', 'Maribel Suarez', 'uploads/vouchers/692f12c63da70_WhatsAppImage2025-10-28at125757PM.jpeg', '2025-12-02 16:24:38', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programas`
--

CREATE TABLE `programas` (
  `id` int(11) NOT NULL,
  `nombre_programa` varchar(160) NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `programas`
--

INSERT INTO `programas` (`id`, `nombre_programa`, `deleted_at`) VALUES
(1, 'Resolución de casuísticas', NULL),
(2, 'Diseño de clases modelo por competencias', NULL),
(3, 'Planificación curricular en el aula multigrado y polidocente', NULL),
(4, 'Nombramiento y Ascenso', NULL),
(5, 'MIL CASUÍSTICA RESUELTAS', NULL),
(6, 'Evaluación diagnóstica como punto de partida de la planificación curricular', NULL),
(7, 'Creación de aulas virtuales y gestión pedagógica en Google Classroom', NULL),
(8, 'Diseño del material educativo para el desarrollo del pensamiento creativo', NULL),
(9, 'Diseño del programa anual y la matriz de organización de unidades', NULL),
(10, 'Diseño y elaboración de la primera unidad diagnóstica', NULL),
(11, 'Diseño de proyectos y experiencias de aprendizaje', NULL),
(12, 'Diseño de clases modelo por áreas curriculares', NULL),
(13, 'Evaluación, planificación curricular y diseño de sesiones por áreas curricular', NULL),
(14, 'Super intensivo en resolución de casuísticas', NULL),
(15, 'Herramientas digitales colaborativas para la gestión y presentación de información', NULL),
(16, 'Diseño de sesiones de aprendizaje por competencias', NULL),
(17, 'Gestión escolar y liderazgo pedagógico', NULL),
(18, 'Comprensión lectora', NULL),
(19, 'Uso de las tics para el desarrollo de competencias aplicando la inteligencia artificial', NULL),
(20, 'Didáctica de la matemática', NULL),
(21, 'Didáctica de la educación inicial', NULL),
(22, 'Innovación y gamificación en el aprendizaje digital', NULL),
(23, 'Estrategias de enseñanza en entornos virtuales y aprendizaje híbrido', NULL),
(24, 'Didáctica en la educación básica alternativa', NULL),
(25, 'Desarollo 1', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_certificacion`
--

CREATE TABLE `tipo_certificacion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_certificacion`
--

INSERT INTO `tipo_certificacion` (`id`, `nombre`, `deleted_at`) VALUES
(1, 'ESPECIALIZACIÓN', NULL),
(2, 'DIPLOMADO', NULL),
(3, 'CURSO 120 HP', NULL),
(4, 'CURSO 200 HP', NULL),
(5, 'CURSO 300 HP', NULL),
(6, 'CURSO 400 HP', NULL),
(7, 'ESPECIALIZACIÓN 2', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ADMINISTRADOR','ADMISION','ASESOR') DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `fullname`, `email`, `profile_image`, `password`, `role`, `deleted_at`) VALUES
(1, 'Administrador Principal', 'admin@gmail.com', 'uploads/perfiles/user_1_1765994726.jpg', '$2y$10$Qc2ipft90yYN0CRA0HQzg.35YaeylRvNKpoe5p1NrbCpRw2/qJUeW', 'ADMINISTRADOR', NULL),
(2, 'Ana Perez Rojas', 'ana@gmail.com', NULL, '$2y$10$labRvM3hk66K3hi5hck0/ecL.b2IYEi/4vaFVhuSCp2MibhNdh4iW', 'ASESOR', NULL),
(3, 'Daniel Garcia', 'daniel@gmail.com', NULL, '$2y$10$/RBckppZ4v9RiIbwkw7U.e3sPiqCgIvknRv/pIFx5214lZ1EKGqm.', 'ASESOR', NULL),
(4, 'Marcelo Morales', 'marcelo@gmail.com', NULL, '$2y$10$Jmt2j4P5povYJkfuD7ZssO5aL81c8gSDnOCBV7jd7Xv.36yk43tjm', 'ADMISION', NULL),
(5, 'Admin 2', 'admin2@gmail.com', NULL, '$2y$10$ZyyoMq2/8Y0gHlKf2CmKcuchlSx1q1z3CgIGwXig9.Pjn16tdwS6q', 'ADMINISTRADOR', NULL),
(8, 'Marisol', 'marisol@gmail.com', NULL, '$2y$10$9CtKIaPOVcml0/tLyvuFMetbNH3i.56hv7aV3swvMrns/Y5DMFHca', 'ADMISION', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `tipo_transaccion` enum('VENTA','CUOTAS') NOT NULL,
  `curso_id` int(11) DEFAULT NULL,
  `programa_id` int(11) DEFAULT NULL,
  `certificado` enum('SI','NO') NOT NULL DEFAULT 'NO',
  `precio_programa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `proceso_certificacion` enum('No tiene','En Proceso','Aprobado','Rechazado') DEFAULT 'No tiene',
  `mencion` varchar(160) NOT NULL,
  `modalidad` enum('ONLINE','AUTOAPRENDIZAJE') NOT NULL,
  `inicio_programa` date NOT NULL,
  `obs_programa` text DEFAULT NULL,
  `advisor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `teacher_id`, `tipo_transaccion`, `curso_id`, `programa_id`, `certificado`, `precio_programa`, `proceso_certificacion`, `mencion`, `modalidad`, `inicio_programa`, `obs_programa`, `advisor_id`, `created_at`, `deleted_at`) VALUES
(2, 1, 'CUOTAS', 3, 4, 'NO', 400.00, 'No tiene', 'Ninguna', 'ONLINE', '2025-12-05', 'ninguna', 2, '2025-11-28 20:02:27', NULL),
(3, 5, 'CUOTAS', 6, 15, 'SI', 800.00, 'No tiene', 'Ninguna', 'ONLINE', '2025-12-05', 'Ninguna', 2, '2025-11-28 20:08:21', NULL),
(4, 2, 'CUOTAS', 5, 8, 'SI', 400.00, 'No tiene', '24240', 'AUTOAPRENDIZAJE', '2025-11-06', '404141', 1, '2025-11-28 20:21:09', NULL),
(9, 3, 'VENTA', 3, 2, 'SI', 400.00, 'No tiene', 'dsada', 'AUTOAPRENDIZAJE', '2025-12-05', 'dadad', 1, '2025-10-28 21:40:51', NULL),
(10, 9, 'VENTA', 2, 2, 'SI', 400.00, 'No tiene', 'Ninguna', 'ONLINE', '2025-12-05', 'dsada', 1, '2025-11-28 22:21:20', NULL),
(11, 3, 'VENTA', 3, 5, 'SI', 400.00, 'Aprobado', 'sdada', 'AUTOAPRENDIZAJE', '2025-12-03', 'asdad', 1, '2025-11-28 22:33:04', NULL),
(12, 2, 'CUOTAS', 1, 18, 'SI', 500.00, 'En Proceso', 'axds', 'AUTOAPRENDIZAJE', '2025-12-08', '', 1, '2025-12-02 16:24:38', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `admisiones`
--
ALTER TABLE `admisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `asesores`
--
ALTER TABLE `asesores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_asesores_usuario` (`user_id`);

--
-- Indices de la tabla `docentes`
--
ALTER TABLE `docentes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`);

--
-- Indices de la tabla `docente_especialidad`
--
ALTER TABLE `docente_especialidad`
  ADD PRIMARY KEY (`teacher_id`,`specialty_id`),
  ADD KEY `fk_de_especialidad` (`specialty_id`);

--
-- Indices de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pago_venta` (`sale_id`);

--
-- Indices de la tabla `programas`
--
ALTER TABLE `programas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_programa` (`nombre_programa`);

--
-- Indices de la tabla `tipo_certificacion`
--
ALTER TABLE `tipo_certificacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venta_docente` (`teacher_id`),
  ADD KEY `fk_venta_tipo_cert` (`curso_id`),
  ADD KEY `fk_venta_programa` (`programa_id`),
  ADD KEY `fk_venta_asesor` (`advisor_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `admisiones`
--
ALTER TABLE `admisiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `asesores`
--
ALTER TABLE `asesores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `docentes`
--
ALTER TABLE `docentes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `programas`
--
ALTER TABLE `programas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `tipo_certificacion`
--
ALTER TABLE `tipo_certificacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD CONSTRAINT `administradores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `admisiones`
--
ALTER TABLE `admisiones`
  ADD CONSTRAINT `admisiones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asesores`
--
ALTER TABLE `asesores`
  ADD CONSTRAINT `fk_asesores_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `docente_especialidad`
--
ALTER TABLE `docente_especialidad`
  ADD CONSTRAINT `fk_de_docente` FOREIGN KEY (`teacher_id`) REFERENCES `docentes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_de_especialidad` FOREIGN KEY (`specialty_id`) REFERENCES `especialidades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `fk_pago_venta` FOREIGN KEY (`sale_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_venta_asesor` FOREIGN KEY (`advisor_id`) REFERENCES `asesores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_venta_docente` FOREIGN KEY (`teacher_id`) REFERENCES `docentes` (`id`),
  ADD CONSTRAINT `fk_venta_programa` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`id`),
  ADD CONSTRAINT `fk_venta_tipo_cert` FOREIGN KEY (`curso_id`) REFERENCES `tipo_certificacion` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
