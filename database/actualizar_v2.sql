-- =====================================================================
--  Actualización de la base de datos (versión 2)
-- ---------------------------------------------------------------------
--  Úsalo SOLO si ya habías importado la versión anterior de
--  database/school_management.sql y no quieres perder tus datos.
--  Si estás instalando desde cero, importa directamente
--  database/school_management.sql (ya incluye todo esto).
--
--  Requiere MariaDB (la base de datos que trae XAMPP).
--  Se puede ejecutar varias veces sin causar errores.
-- =====================================================================

USE `school_management`;

-- ---------- Padres de familia: acceso al portal y más datos ----------
ALTER TABLE `padres_familia`
  ADD COLUMN IF NOT EXISTS `usuario`        VARCHAR(50)  DEFAULT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `password`       VARCHAR(255) DEFAULT NULL AFTER `usuario`,
  ADD COLUMN IF NOT EXISTS `documento_tipo` VARCHAR(20)  DEFAULT NULL AFTER `apellido`,
  ADD COLUMN IF NOT EXISTS `direccion`      VARCHAR(255) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `ocupacion`      VARCHAR(100) DEFAULT NULL AFTER `direccion`,
  ADD COLUMN IF NOT EXISTS `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
  ADD UNIQUE INDEX IF NOT EXISTS `uk_padres_usuario` (`usuario`),
  ADD INDEX IF NOT EXISTS `idx_padres_documento` (`documento_numero`);

-- Relación padre/acudiente <-> estudiante (un padre puede tener varios hijos y viceversa)
-- parentesco: 'madre' | 'padre' | 'acudiente' | 'abuelo(a)' | 'tio(a)' | 'otro'
CREATE TABLE IF NOT EXISTS `padres_estudiantes` (
  `id`             INT NOT NULL AUTO_INCREMENT,
  `padre_id`       INT NOT NULL,
  `estudiante_id`  INT NOT NULL,
  `parentesco`     VARCHAR(30) NOT NULL DEFAULT 'acudiente',
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_padre_estudiante` (`padre_id`, `estudiante_id`),
  KEY `idx_pe_estudiante` (`estudiante_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Si la versión anterior guardaba un estudiante por padre, pasarlo a la nueva relación
SET @tiene_columna := (SELECT COUNT(*) FROM information_schema.columns
                       WHERE table_schema = DATABASE() AND table_name = 'padres_familia' AND column_name = 'estudiante_id');
SET @sql := IF(@tiene_columna > 0,
  'INSERT IGNORE INTO padres_estudiantes (padre_id, estudiante_id, parentesco) SELECT id, estudiante_id, ''acudiente'' FROM padres_familia WHERE estudiante_id IS NOT NULL',
  'DO 0');
PREPARE paso FROM @sql; EXECUTE paso; DEALLOCATE PREPARE paso;
ALTER TABLE `padres_familia` DROP COLUMN IF EXISTS `estudiante_id`;

-- ---------- Matrículas: grado y sede como referencia ----------
ALTER TABLE `matriculas`
  ADD COLUMN IF NOT EXISTS `grado_id` INT DEFAULT NULL AFTER `grado`,
  ADD COLUMN IF NOT EXISTS `sede_id`  INT DEFAULT NULL AFTER `grado_id`;

-- ---------- Configuración de niveles: docente orientador (multigrado) ----------
ALTER TABLE `niveles_configuracion`
  ADD COLUMN IF NOT EXISTS `profesor_id` INT DEFAULT NULL AFTER `observaciones`;

-- ---------- Mensajería y material didáctico ----------
-- Mensajes internos entre profesores y padres de familia
-- remitente_tipo / destinatario_tipo: 'profesor' | 'padre'
CREATE TABLE IF NOT EXISTS `mensajes` (
  `id`                      INT NOT NULL AUTO_INCREMENT,
  `remitente_tipo`          VARCHAR(20) NOT NULL,
  `remitente_id`            INT NOT NULL,
  `destinatario_tipo`       VARCHAR(20) NOT NULL,
  `destinatario_id`         INT NOT NULL,
  `estudiante_id`           INT DEFAULT NULL,
  `asunto`                  VARCHAR(200) NOT NULL,
  `contenido`               TEXT NOT NULL,
  `respuesta_a`             INT DEFAULT NULL,
  `leido`                   TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_lectura`           DATETIME DEFAULT NULL,
  `eliminado_remitente`     TINYINT(1) NOT NULL DEFAULT 0,
  `eliminado_destinatario`  TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_envio`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mensajes_destinatario` (`destinatario_tipo`, `destinatario_id`, `leido`),
  KEY `idx_mensajes_remitente` (`remitente_tipo`, `remitente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Material didáctico que publica cada profesor (archivos o enlaces)
-- tipo: 'archivo' | 'enlace'
CREATE TABLE IF NOT EXISTS `recursos` (
  `id`                   INT NOT NULL AUTO_INCREMENT,
  `profesor_id`          INT NOT NULL,
  `asignacion_id`        INT DEFAULT NULL,
  `titulo`               VARCHAR(200) NOT NULL,
  `descripcion`          TEXT DEFAULT NULL,
  `tipo`                 VARCHAR(20) NOT NULL DEFAULT 'archivo',
  `archivo_ruta`         VARCHAR(255) DEFAULT NULL,
  `archivo_nombre`       VARCHAR(255) DEFAULT NULL,
  `archivo_mime`         VARCHAR(100) DEFAULT NULL,
  `archivo_tamano`       INT DEFAULT NULL,
  `url`                  VARCHAR(500) DEFAULT NULL,
  `visible_estudiantes`  TINYINT(1) NOT NULL DEFAULT 1,
  `descargas`            INT NOT NULL DEFAULT 0,
  `fecha_creacion`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recursos_profesor` (`profesor_id`),
  KEY `idx_recursos_asignacion` (`asignacion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
