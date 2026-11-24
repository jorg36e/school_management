<?php
/**
 * Verifica la sesión del padre de familia y carga sus datos.
 * Deja disponibles: $pdo, $padre (datos del acudiente) y $hijos (estudiantes a cargo).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['padre_id'])) {
    header('Location: /school_management/auth/padre_login.php');
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/NotasEstudianteHelper.php';

$stmt = $pdo->prepare("SELECT * FROM padres_familia WHERE id = ? AND estado = 'activo'");
$stmt->execute([$_SESSION['padre_id']]);
$padre = $stmt->fetch(PDO::FETCH_ASSOC);

// Si la cuenta fue desactivada o eliminada, cerrar la sesión
if (!$padre) {
    session_destroy();
    header('Location: /school_management/auth/padre_login.php?error=1');
    exit();
}

$stmt = $pdo->prepare("
    SELECT e.id, e.nombre, e.apellido, e.documento_numero, e.grado_id, e.estado,
           pe.parentesco, g.nombre AS grado_nombre, g.nivel, s.nombre AS sede_nombre
    FROM padres_estudiantes pe
    INNER JOIN estudiantes e ON e.id = pe.estudiante_id
    LEFT JOIN grados g ON g.id = e.grado_id
    LEFT JOIN sedes s ON s.id = e.sede_id
    WHERE pe.padre_id = ?
    ORDER BY e.nombre, e.apellido
");
$stmt->execute([$padre['id']]);
$hijos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/** Devuelve el hijo con ese id solo si está a cargo de este padre */
function buscarHijo(array $hijos, int $id): ?array
{
    foreach ($hijos as $h) {
        if ((int) $h['id'] === $id) {
            return $h;
        }
    }
    return null;
}
