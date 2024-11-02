-- Primero, crear las nuevas tablas que no existen

-- Tabla periodos_academicos
CREATE TABLE IF NOT EXISTS periodos_academicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('activo', 'inactivo', 'finalizado') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla asignaturas
CREATE TABLE IF NOT EXISTS asignaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla grupos
CREATE TABLE IF NOT EXISTS grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sede_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    grado VARCHAR(20) NOT NULL,
    capacidad INT NOT NULL DEFAULT 30,
    año_escolar INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla asignaciones_profesor
CREATE TABLE IF NOT EXISTS asignaciones_profesor (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profesor_id INT NOT NULL,
    grupo_id INT NOT NULL,
    asignatura_id INT NOT NULL,
    periodo_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES profesores(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asignacion (profesor_id, grupo_id, asignatura_id, periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla calificaciones
CREATE TABLE IF NOT EXISTS calificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricula_id INT NOT NULL,
    asignatura_id INT NOT NULL,
    periodo_id INT NOT NULL,
    nota DECIMAL(3,1) NOT NULL,
    tipo_evaluacion VARCHAR(50) NOT NULL,
    fecha_evaluacion DATE NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla asistencias
CREATE TABLE IF NOT EXISTS asistencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricula_id INT NOT NULL,
    asignatura_id INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente', 'ausente', 'tardanza', 'justificado') NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear índices para optimizar búsquedas
CREATE INDEX IF NOT EXISTS idx_estudiantes_documento ON estudiantes(documento_numero);
CREATE INDEX IF NOT EXISTS idx_profesores_documento ON profesores(documento_numero);
CREATE INDEX IF NOT EXISTS idx_calificaciones_fecha ON calificaciones(fecha_evaluacion);
CREATE INDEX IF NOT EXISTS idx_asistencias_fecha ON asistencias(fecha);
CREATE INDEX IF NOT EXISTS idx_matriculas_fecha ON matriculas(fecha_matricula);