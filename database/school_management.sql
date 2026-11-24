-- =====================================================================
--  Sistema de Gestión Escolar - Estructura completa de la base de datos
-- ---------------------------------------------------------------------
--  Reconstruida a partir de las consultas SQL del código fuente.
--  Compatible con MySQL 5.7+ y MariaDB 10.3+ (XAMPP).
--
--  Uso (phpMyAdmin): pestaña "Importar" -> elegir este archivo -> Continuar.
--  Uso (consola):    mysql -u root < database/school_management.sql
--
--  Crea la base de datos `school_management` y un usuario administrador:
--      usuario:    admin
--      contraseña: admin123   (cámbiala después de entrar)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `school_management`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `school_management`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
--  USUARIOS
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `administradores` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `usuario`         VARCHAR(50)  NOT NULL,
  `password`        VARCHAR(255) NOT NULL,
  `nombre`          VARCHAR(100) NOT NULL,
  `email`           VARCHAR(100) DEFAULT NULL,
  `estado`          VARCHAR(20)  NOT NULL DEFAULT 'activo',
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_administradores_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `profesores` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `usuario`          VARCHAR(50)  NOT NULL,
  `password`         VARCHAR(255) NOT NULL,
  `nombre`           VARCHAR(100) NOT NULL,
  `apellido`         VARCHAR(100) NOT NULL DEFAULT '',
  `documento_tipo`   VARCHAR(20)  DEFAULT NULL,
  `documento_numero` VARCHAR(30)  DEFAULT NULL,
  `email`            VARCHAR(100) DEFAULT NULL,
  `telefono`         VARCHAR(30)  DEFAULT NULL,
  `especialidad`     VARCHAR(100) DEFAULT NULL,
  `sede_id`          INT DEFAULT NULL,
  `estado`           VARCHAR(20)  NOT NULL DEFAULT 'activo',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_profesores_usuario` (`usuario`),
  KEY `idx_profesores_sede` (`sede_id`),
  KEY `idx_profesores_documento` (`documento_numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id`                  INT NOT NULL AUTO_INCREMENT,
  `usuario`             VARCHAR(50)  DEFAULT NULL,
  `password`            VARCHAR(255) DEFAULT NULL,
  `nombre`              VARCHAR(100) NOT NULL,
  `apellido`            VARCHAR(100) NOT NULL DEFAULT '',
  `documento_tipo`      VARCHAR(20)  DEFAULT NULL,
  `documento_numero`    VARCHAR(30)  DEFAULT NULL,
  `fecha_nacimiento`    DATE DEFAULT NULL,
  `genero`              VARCHAR(20)  DEFAULT NULL,
  `direccion`           VARCHAR(255) DEFAULT NULL,
  `telefono`            VARCHAR(30)  DEFAULT NULL,
  `email`               VARCHAR(100) DEFAULT NULL,
  `nombre_acudiente`    VARCHAR(150) DEFAULT NULL,
  `telefono_acudiente`  VARCHAR(30)  DEFAULT NULL,
  `sede_id`             INT DEFAULT NULL,
  `nivel`               VARCHAR(30)  DEFAULT NULL,
  `grado_id`            INT DEFAULT NULL,
  `grupo_id`            INT DEFAULT NULL,
  `foto`                VARCHAR(255) DEFAULT NULL,
  `estado`              VARCHAR(20)  NOT NULL DEFAULT 'activo',
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Alias de solo lectura: parte del código (p. ej. asistencia del profesor) usa "nombres"/"apellidos"
  `nombres`             VARCHAR(100) GENERATED ALWAYS AS (`nombre`) VIRTUAL,
  `apellidos`           VARCHAR(100) GENERATED ALWAYS AS (`apellido`) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `idx_estudiantes_usuario` (`usuario`),
  KEY `idx_estudiantes_documento` (`documento_numero`),
  KEY `idx_estudiantes_sede` (`sede_id`),
  KEY `idx_estudiantes_grado` (`grado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `estudiantes_fotos` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `estudiante_id`      INT NOT NULL,
  `foto_url`           VARCHAR(255) NOT NULL,
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `fecha_creacion`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fotos_estudiante` (`estudiante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `padres_familia` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100) NOT NULL,
  `apellido`         VARCHAR(100) NOT NULL DEFAULT '',
  `documento_numero` VARCHAR(30)  DEFAULT NULL,
  `telefono`         VARCHAR(30)  DEFAULT NULL,
  `email`            VARCHAR(100) DEFAULT NULL,
  `estudiante_id`    INT DEFAULT NULL,
  `estado`           VARCHAR(20)  NOT NULL DEFAULT 'activo',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  ESTRUCTURA INSTITUCIONAL: sedes, niveles, grados, grupos
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sedes` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(150) NOT NULL,
  `codigo_dane`     VARCHAR(30)  DEFAULT NULL,
  `direccion`       VARCHAR(255) DEFAULT NULL,
  `telefono`        VARCHAR(30)  DEFAULT NULL,
  `tipo_ensenanza`  VARCHAR(20)  NOT NULL DEFAULT 'unigrado',
  `estado`          VARCHAR(20)  NOT NULL DEFAULT 'activo',
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Niveles habilitados por sede (preescolar, primaria, secundaria, media)
CREATE TABLE IF NOT EXISTS `niveles_sede` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `sede_id`    INT NOT NULL,
  `nivel`      VARCHAR(30) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_niveles_sede` (`sede_id`, `nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tipo de enseñanza (unigrado / multigrado) por nivel de cada sede
CREATE TABLE IF NOT EXISTS `niveles_configuracion` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `sede_id`         INT NOT NULL,
  `nivel`           VARCHAR(30) NOT NULL,
  `tipo_ensenanza`  VARCHAR(20) NOT NULL DEFAULT 'unigrado',
  `observaciones`   TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nivconf_sede_nivel` (`sede_id`, `nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `niveles_educativos` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `sede_id`            INT NOT NULL,
  `nombre`             VARCHAR(50) NOT NULL,
  `codigo`             VARCHAR(20) DEFAULT NULL,
  `orden`              INT NOT NULL DEFAULT 0,
  `color_tema`         VARCHAR(20) DEFAULT NULL,
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `fecha_creacion`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_niveles_sede` (`sede_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `grados` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `sede_id`            INT DEFAULT NULL,
  `nivel`              VARCHAR(30) DEFAULT NULL,
  `nivel_id`           INT DEFAULT NULL,
  `nombre`             VARCHAR(50) NOT NULL,
  `codigo`             VARCHAR(20) DEFAULT NULL,
  `orden`              INT NOT NULL DEFAULT 0,
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `fecha_creacion`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grados_sede_nivel` (`sede_id`, `nivel`),
  KEY `idx_grados_nivel_id` (`nivel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `grupos` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `sede_id`     INT DEFAULT NULL,
  `nivel`       VARCHAR(30) DEFAULT NULL,
  `grado`       VARCHAR(50) DEFAULT NULL,
  `grado_id`    INT DEFAULT NULL,
  `nombre`      VARCHAR(100) NOT NULL,
  `capacidad`   INT NOT NULL DEFAULT 30,
  `estado`      VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_grupos_sede_nivel` (`sede_id`, `nivel`),
  KEY `idx_grupos_grado` (`grado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `historial_cambios_grado` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `estudiante_id`   INT NOT NULL,
  `grado_anterior`  INT DEFAULT NULL,
  `grado_nuevo`     INT DEFAULT NULL,
  `fecha_cambio`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hcg_estudiante` (`estudiante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  CALENDARIO ACADÉMICO
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `anos_lectivos` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `nombre`        VARCHAR(50) NOT NULL,
  `ano`           INT DEFAULT NULL,
  `fecha_inicio`  DATE DEFAULT NULL,
  `fecha_fin`     DATE DEFAULT NULL,
  `estado`        VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- estado_periodo: 'en_curso' | 'finalizado' | 'cerrado'
CREATE TABLE IF NOT EXISTS `periodos_academicos` (
  `id`                       INT NOT NULL AUTO_INCREMENT,
  `ano_lectivo_id`           INT DEFAULT NULL,
  `numero_periodo`           INT NOT NULL DEFAULT 1,
  `nombre`                   VARCHAR(100) NOT NULL,
  `descripcion`              TEXT DEFAULT NULL,
  `fecha_inicio`             DATE DEFAULT NULL,
  `fecha_fin`                DATE DEFAULT NULL,
  `porcentaje_calificacion`  DECIMAL(5,2) NOT NULL DEFAULT 25.00,
  `estado_periodo`           VARCHAR(20) NOT NULL DEFAULT 'en_curso',
  `estado`                   VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_periodos_ano` (`ano_lectivo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  ASIGNATURAS Y ASIGNACIONES DOCENTES
-- ---------------------------------------------------------------------

-- `materias` es la tabla que usan el módulo del profesor y las asignaciones.
CREATE TABLE IF NOT EXISTS `materias` (
  `id`                  INT NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(100) NOT NULL,
  `descripcion`         TEXT DEFAULT NULL,
  `intensidad_horaria`  INT DEFAULT NULL,
  `profesor_id`         INT DEFAULT NULL,
  `estado`              VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- `asignaturas` la usa el módulo de administración (lista DBA / desempeños).
CREATE TABLE IF NOT EXISTS `asignaturas` (
  `id`                  INT NOT NULL AUTO_INCREMENT,
  `nombre`              VARCHAR(100) NOT NULL,
  `descripcion`         TEXT DEFAULT NULL,
  `intensidad_horaria`  INT DEFAULT NULL,
  `sede_id`             INT DEFAULT NULL,
  `profesor_id`         INT DEFAULT NULL,
  `estado`              VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `asignaciones_profesor` (
  `id`                INT NOT NULL AUTO_INCREMENT,
  `profesor_id`       INT NOT NULL,
  `sede_id`           INT DEFAULT NULL,
  `grado_id`          INT DEFAULT NULL,
  `grupo_id`          INT DEFAULT NULL,
  `materia_id`        INT DEFAULT NULL,
  `asignatura_id`     INT DEFAULT NULL,
  `periodo_id`        INT DEFAULT NULL,
  `asignado_por`      INT DEFAULT NULL,
  `fecha_asignacion`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  `estado`            VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ap_profesor` (`profesor_id`),
  KEY `idx_ap_grado` (`grado_id`),
  KEY `idx_ap_materia` (`materia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `historial_asignaciones` (
  `id`              INT NOT NULL AUTO_INCREMENT,
  `asignacion_id`   INT DEFAULT NULL,
  `profesor_id`     INT DEFAULT NULL,
  `grupo_id`        INT DEFAULT NULL,
  `asignatura_id`   INT DEFAULT NULL,
  `periodo_id`      INT DEFAULT NULL,
  `tipo_cambio`     VARCHAR(30) NOT NULL,
  `realizado_por`   INT DEFAULT NULL,
  `fecha`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `profesor_materia` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `profesor_id`    INT NOT NULL,
  `asignatura_id`  INT DEFAULT NULL,
  `grupo_id`       INT DEFAULT NULL,
  `periodo_id`     INT DEFAULT NULL,
  `estado`         VARCHAR(20) NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Desempeños / DBA por asignatura
CREATE TABLE IF NOT EXISTS `desempenos` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `asignatura_id`      INT DEFAULT NULL,
  `periodo_id`         INT DEFAULT NULL,
  `tipo`               VARCHAR(50) DEFAULT NULL,
  `descripcion`        TEXT DEFAULT NULL,
  `porcentaje`         DECIMAL(5,2) DEFAULT NULL,
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `fecha_creacion`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_desempenos_asignatura` (`asignatura_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `planeaciones` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `asignatura_id` INT DEFAULT NULL,
  `profesor_id`   INT DEFAULT NULL,
  `titulo`        VARCHAR(200) DEFAULT NULL,
  `archivo_url`   VARCHAR(255) DEFAULT NULL,
  `estado`        VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tareas` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `asignacion_id`      INT DEFAULT NULL,
  `titulo`             VARCHAR(200) DEFAULT NULL,
  `descripcion`        TEXT DEFAULT NULL,
  `fecha_entrega`      DATE DEFAULT NULL,
  `fecha_vencimiento`  DATE DEFAULT NULL,
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  MATRÍCULAS, CALIFICACIONES Y ASISTENCIA
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `matriculas` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `estudiante_id`    INT NOT NULL,
  `grado`            VARCHAR(50) DEFAULT NULL,
  `grupo_id`         INT DEFAULT NULL,
  `periodo`          VARCHAR(50) DEFAULT NULL,
  `fecha_matricula`  DATE DEFAULT NULL,
  `observaciones`    TEXT DEFAULT NULL,
  `estado`           VARCHAR(20) NOT NULL DEFAULT 'activo',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_matriculas_estudiante` (`estudiante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tipos de nota que define cada profesor por asignación
-- categoria: 'TAREAS' | 'EVALUACIONES' | 'AUTOEVALUACION'
CREATE TABLE IF NOT EXISTS `tipos_notas` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `asignacion_id`      INT NOT NULL,
  `nombre`             VARCHAR(100) NOT NULL,
  `porcentaje`         DECIMAL(5,2) NOT NULL DEFAULT 0,
  `categoria`          VARCHAR(30) NOT NULL DEFAULT 'TAREAS',
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `fecha_creacion`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipos_notas_asignacion` (`asignacion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id`                 INT NOT NULL AUTO_INCREMENT,
  `estudiante_id`      INT NOT NULL,
  `tipo_nota_id`       INT DEFAULT NULL,
  `materia_id`         INT DEFAULT NULL,
  `periodo_id`         INT DEFAULT NULL,
  `periodo_actual_id`  INT DEFAULT NULL,
  `valor`              DECIMAL(4,2) DEFAULT NULL,
  `observacion`        TEXT DEFAULT NULL,
  `fecha`              DATE DEFAULT NULL,
  `estado`             VARCHAR(20) NOT NULL DEFAULT 'activo',
  `fecha_registro`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_calif_estudiante_tipo` (`estudiante_id`, `tipo_nota_id`),
  KEY `idx_calif_periodo_actual` (`periodo_actual_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `historial_calificaciones` (
  `id`               INT NOT NULL AUTO_INCREMENT,
  `calificacion_id`  INT DEFAULT NULL,
  `estudiante_id`    INT NOT NULL,
  `tipo_nota_id`     INT NOT NULL,
  `periodo_id`       INT NOT NULL,
  `valor`            DECIMAL(4,2) DEFAULT NULL,
  `fecha_registro`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hist_calif` (`estudiante_id`, `periodo_id`, `tipo_nota_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- estado: 'presente' | 'ausente' | 'tardanza' | 'justificado'
CREATE TABLE IF NOT EXISTS `asistencias` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `estudiante_id`  INT NOT NULL,
  `grado_id`       INT DEFAULT NULL,
  `profesor_id`    INT DEFAULT NULL,
  `fecha`          DATE NOT NULL,
  `estado`         VARCHAR(20) NOT NULL DEFAULT 'presente',
  `observaciones`  TEXT DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asistencias_est_fecha` (`estudiante_id`, `fecha`),
  KEY `idx_asistencias_grado_fecha` (`grado_id`, `fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  CONFIGURACIÓN Y REGISTROS DE ACTIVIDAD
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `configuracion` (
  `id`                  INT NOT NULL AUTO_INCREMENT,
  `school_name`         VARCHAR(255) DEFAULT 'Sistema Escolar',
  `school_logo`         VARCHAR(255) DEFAULT NULL,
  `address`             VARCHAR(255) DEFAULT NULL,
  `current_year`        INT DEFAULT NULL,
  `year_status`         VARCHAR(20) DEFAULT 'activo',
  `smtp_host`           VARCHAR(255) DEFAULT NULL,
  `smtp_port`           INT DEFAULT NULL,
  `session_timeout`     INT DEFAULT 30,
  `max_login_attempts`  INT DEFAULT 3,
  `sede`                VARCHAR(255) DEFAULT 'Sede Principal',
  `favicon`             VARCHAR(255) DEFAULT NULL,
  `sidebar_color`       VARCHAR(7)  DEFAULT '#1a2b40',
  `sidebar_text_color`  VARCHAR(7)  DEFAULT '#FFFFFF',
  `sidebar_style`       VARCHAR(20) DEFAULT 'default',
  `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `actividad_log` (
  `id`           INT NOT NULL AUTO_INCREMENT,
  `tabla`        VARCHAR(50) DEFAULT NULL,
  `registro_id`  INT DEFAULT NULL,
  `accion`       VARCHAR(50) DEFAULT NULL,
  `descripcion`  TEXT DEFAULT NULL,
  `usuario_id`   INT DEFAULT NULL,
  `fecha`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actividad_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `log_actividad` (
  `id`            INT NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT DEFAULT NULL,
  `tipo_usuario`  VARCHAR(20) DEFAULT NULL,
  `accion`        VARCHAR(100) DEFAULT NULL,
  `detalles`      TEXT DEFAULT NULL,
  `fecha`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `registros_actividad` (
  `id`          INT NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT DEFAULT NULL,
  `accion`      VARCHAR(100) DEFAULT NULL,
  `detalles`    TEXT DEFAULT NULL,
  `fecha`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  DATOS INICIALES
-- =====================================================================

-- Administrador: usuario "admin" / contraseña "admin123"
INSERT INTO `administradores` (`usuario`, `password`, `nombre`, `email`, `estado`)
SELECT 'admin', '$2y$10$lDiNugRlffY4QgW6VbZg6OEsg/0hmbttDFcct1qXMhpTbowijnrGi', 'Administrador', NULL, 'activo'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `administradores` WHERE `usuario` = 'admin');

INSERT INTO `configuracion` (`id`, `school_name`, `sede`, `sidebar_color`, `sidebar_text_color`, `sidebar_style`)
SELECT 1, 'Sistema Escolar', 'Sede Principal', '#1a2b40', '#FFFFFF', 'default'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `configuracion` WHERE `id` = 1);
