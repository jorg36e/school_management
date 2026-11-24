<?php
/**
 * Material didáctico: subida, permisos y descarga de recursos de los profesores.
 *
 * Los archivos se guardan en uploads/recursos/<profesor_id>/ con un nombre aleatorio.
 * Esa carpeta tiene un .htaccess que impide abrirlos directamente: siempre se
 * descargan a través de recursos/descargar.php, que comprueba los permisos.
 */
class RecursosHelper
{
    /** Extensiones permitidas => tipo MIME con el que se entregan */
    const EXTENSIONES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'zip' => 'application/zip',
    ];

    const MAX_MB = 20;

    public static function directorioBase(): string
    {
        return dirname(__DIR__) . '/uploads/recursos';
    }

    /** Tamaño máximo real: el menor entre MAX_MB y los límites de PHP (upload_max_filesize, post_max_size) */
    public static function tamanoMaximo(): int
    {
        $aBytes = function ($valor) {
            $valor = trim((string) $valor);
            $n = (float) $valor;
            switch (strtolower(substr($valor, -1))) {
                case 'g': $n *= 1024;
                // no break
                case 'm': $n *= 1024;
                // no break
                case 'k': $n *= 1024;
            }
            return (int) $n;
        };
        $limites = [self::MAX_MB * 1024 * 1024];
        foreach (['upload_max_filesize', 'post_max_size'] as $directiva) {
            $b = $aBytes(ini_get($directiva));
            if ($b > 0) {
                $limites[] = $b;
            }
        }
        return min($limites);
    }

    public static function formatoTamano(?int $bytes): string
    {
        if (!$bytes) {
            return '';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        return max(1, round($bytes / 1024)) . ' KB';
    }

    public static function icono(array $r): string
    {
        if ($r['tipo'] === 'enlace') {
            return 'fa-link';
        }
        $ext = strtolower(pathinfo((string) $r['archivo_nombre'], PATHINFO_EXTENSION));
        $iconos = [
            'pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'odt' => 'fa-file-word',
            'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel', 'ods' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint', 'odp' => 'fa-file-powerpoint',
            'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image', 'gif' => 'fa-file-image',
            'mp3' => 'fa-file-audio', 'mp4' => 'fa-file-video', 'zip' => 'fa-file-zipper', 'txt' => 'fa-file-lines',
        ];
        return $iconos[$ext] ?? 'fa-file';
    }

    /** Asignaciones activas (materia + grado) de un profesor */
    public static function asignacionesProfesor(PDO $pdo, int $profesorId): array
    {
        $stmt = $pdo->prepare("
            SELECT ap.id, m.nombre AS materia, g.nombre AS grado
            FROM asignaciones_profesor ap
            INNER JOIN materias m ON m.id = ap.materia_id
            LEFT JOIN grados g ON g.id = ap.grado_id
            WHERE ap.profesor_id = ? AND ap.estado = 'activo'
            ORDER BY g.nombre, m.nombre
        ");
        $stmt->execute([$profesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda el archivo subido y devuelve [ruta_relativa, nombre_original, mime, tamaño].
     * Lanza Exception con un mensaje para el usuario si no es válido.
     */
    public static function guardarArchivo(array $archivo, int $profesorId): array
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new Exception('Seleccione el archivo que desea publicar.');
        }
        if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $archivo['size'] > self::tamanoMaximo()) {
            throw new Exception('El archivo supera el tamaño máximo permitido (' . self::formatoTamano(self::tamanoMaximo()) . ').');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            throw new Exception('No se pudo recibir el archivo. Intente de nuevo.');
        }

        $nombre = basename((string) $archivo['name']);
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (!isset(self::EXTENSIONES[$ext])) {
            throw new Exception('Tipo de archivo no permitido. Formatos aceptados: ' . implode(', ', array_keys(self::EXTENSIONES)) . '.');
        }

        $carpeta = self::directorioBase() . '/' . $profesorId;
        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
            throw new Exception('No se pudo crear la carpeta de archivos en el servidor.');
        }

        $guardado = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $guardado)) {
            throw new Exception('No se pudo guardar el archivo en el servidor.');
        }

        return [$profesorId . '/' . $guardado, $nombre, self::EXTENSIONES[$ext], (int) $archivo['size']];
    }

    public static function borrarArchivo(?string $ruta): void
    {
        if (!$ruta || strpos($ruta, '..') !== false) {
            return;
        }
        $completa = self::directorioBase() . '/' . $ruta;
        if (is_file($completa)) {
            unlink($completa);
        }
    }

    /** ¿La sesión actual puede ver este recurso? (dueño, estudiante del grado o acudiente de ese estudiante) */
    public static function puedeVer(PDO $pdo, array $recurso): bool
    {
        if (!empty($_SESSION['profesor_id']) && (int) $_SESSION['profesor_id'] === (int) $recurso['profesor_id']) {
            return true;
        }
        if (!empty($_SESSION['admin_id'])) {
            return true;
        }
        if (!$recurso['visible_estudiantes'] || !$recurso['asignacion_id']) {
            return false;
        }

        if (!empty($_SESSION['estudiante_id'])) {
            $stmt = $pdo->prepare("
                SELECT 1 FROM asignaciones_profesor ap
                INNER JOIN estudiantes e ON e.grado_id = ap.grado_id
                WHERE ap.id = ? AND e.id = ?
            ");
            $stmt->execute([$recurso['asignacion_id'], $_SESSION['estudiante_id']]);
            return (bool) $stmt->fetchColumn();
        }
        if (!empty($_SESSION['padre_id'])) {
            $stmt = $pdo->prepare("
                SELECT 1 FROM asignaciones_profesor ap
                INNER JOIN estudiantes e ON e.grado_id = ap.grado_id
                INNER JOIN padres_estudiantes pe ON pe.estudiante_id = e.id
                WHERE ap.id = ? AND pe.padre_id = ?
            ");
            $stmt->execute([$recurso['asignacion_id'], $_SESSION['padre_id']]);
            return (bool) $stmt->fetchColumn();
        }
        return false;
    }

    /** Recursos visibles para el grado de un estudiante */
    public static function delGrado(PDO $pdo, int $gradoId): array
    {
        $stmt = $pdo->prepare("
            SELECT r.*, m.nombre AS materia, CONCAT(p.nombre, ' ', p.apellido) AS profesor
            FROM recursos r
            INNER JOIN asignaciones_profesor ap ON ap.id = r.asignacion_id AND ap.estado = 'activo'
            INNER JOIN materias m ON m.id = ap.materia_id
            LEFT JOIN profesores p ON p.id = r.profesor_id
            WHERE ap.grado_id = ? AND r.visible_estudiantes = 1
            ORDER BY m.nombre, r.fecha_creacion DESC
        ");
        $stmt->execute([$gradoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
