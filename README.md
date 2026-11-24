# school_management

Sistema de gestión escolar en PHP + MySQL/MariaDB con cuatro tipos de usuario:
administradores, profesores, estudiantes y padres de familia.

## Instalación local con XAMPP

1. Copia el proyecto en `C:\xampp\htdocs\school_management`.
   El nombre de la carpeta importa: las rutas usan `/school_management`.
2. En el panel de XAMPP enciende **Apache** y **MySQL**.
3. Crea la base de datos importando `database/school_management.sql`:
   - Abre http://localhost/phpmyadmin
   - Pestaña **Importar** → **Seleccionar archivo** → `database/school_management.sql` → **Importar**
   - El script crea la base `school_management`, todas las tablas y un administrador inicial.
4. Entra a http://localhost/school_management
   - Administrador: usuario `admin`, contraseña `admin123` (cámbiala después)

La conexión se configura en `config/database.php` (por defecto `root` sin contraseña, como trae XAMPP).

### ¿Ya habías importado la base de datos antes?

- **Si no tienes datos que conservar:** borra la base `school_management` en phpMyAdmin
  e importa de nuevo `database/school_management.sql`.
- **Si ya cargaste datos:** importa `database/actualizar_v2.sql`. Añade las tablas y columnas
  nuevas sin borrar nada (se puede ejecutar más de una vez).

> No uses `database/update_schema.sql`: es un script antiguo e incompleto.

## Módulos

| Rol | Ingreso | Qué puede hacer |
|---|---|---|
| Administrador | `auth/login.php` | Sedes, niveles y grados; profesores, estudiantes y padres; matrículas; materias y asignaciones; periodos; boletines; historial de actividad |
| Profesor | `auth/profesor_login.php` | Calificaciones, asistencia, mensajes con acudientes, material didáctico, perfil y contraseña |
| Estudiante | `auth/estudiante_login.php` | Notas, material de clase, perfil |
| Padre de familia | `auth/padre_login.php` | Notas e inasistencias de sus hijos, mensajes con los docentes, perfil y contraseña |

Los usuarios de profesores, estudiantes y padres los crea el administrador.
Para que un padre pueda ingresar, al registrarlo en **Padres de familia** asígnele usuario y contraseña
y marque los estudiantes que tiene a cargo.

## Orden recomendado para cargar datos

1. Sedes → niveles y grados de cada sede
2. Profesores → estudiantes → padres de familia (vinculados a sus hijos)
3. Materias → año lectivo y periodo académico
4. Asignaciones de profesores (materia + grado)
5. Cada profesor crea sus tipos de nota y registra calificaciones

## Archivos subidos

El material didáctico se guarda en `uploads/recursos/` y solo se descarga a través de
`recursos/descargar.php`, que comprueba quién lo pide. El tamaño máximo depende de
`upload_max_filesize` y `post_max_size` en `php.ini` (hasta 20 MB).
