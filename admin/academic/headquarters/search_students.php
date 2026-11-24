<?php
/**
 * Búsqueda de estudiantes (JSON) para el modal "Asignar estudiante" de view_grade.php.
 * Parámetros: q (texto, mínimo 3 caracteres), sede_id (opcional: primero los de esa sede).
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit();
}

require_once '../../../config/database.php';

$q = trim($_GET['q'] ?? '');
$sede_id = (int) ($_GET['sede_id'] ?? 0);

if (mb_strlen($q) < 3) {
    echo json_encode([]);
    exit();
}

$like = '%' . $q . '%';
$stmt = $pdo->prepare("
    SELECT e.id,
           CONCAT(e.nombre, ' ', e.apellido) AS nombre_completo,
           COALESCE(e.documento_tipo, 'Doc') AS documento_tipo,
           COALESCE(e.documento_numero, 'sin número') AS documento_numero,
           g.nombre AS grado_actual,
           s.nombre AS sede_actual
    FROM estudiantes e
    LEFT JOIN grados g ON g.id = e.grado_id
    LEFT JOIN sedes s ON s.id = e.sede_id
    WHERE e.estado = 'activo'
      AND (CONCAT(e.nombre, ' ', e.apellido) LIKE ? OR CONCAT(e.apellido, ' ', e.nombre) LIKE ? OR e.documento_numero LIKE ?)
    ORDER BY (e.sede_id = ?) DESC, e.apellido, e.nombre
    LIMIT 20
");
$stmt->execute([$like, $like, $like, $sede_id]);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mostrar dónde está matriculado actualmente cada estudiante
foreach ($resultados as &$r) {
    if ($r['grado_actual']) {
        $r['nombre_completo'] .= ' (actualmente en ' . $r['grado_actual']
            . ($r['sede_actual'] ? ', ' . $r['sede_actual'] : '') . ')';
    } else {
        $r['nombre_completo'] .= ' (sin grado)';
    }
}
unset($r);

echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
