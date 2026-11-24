<?php
/**
 * Exporta a CSV (se abre directamente en Excel) la lista de estudiantes de un grado
 * o de todos los grados de un nivel multigrado.
 * Parámetros: sede_id, nivel, grado_id (o tipo=multigrado).
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$sede_id = (int) ($_GET['sede_id'] ?? 0);
$nivel = $_GET['nivel'] ?? '';
$grado_id = (int) ($_GET['grado_id'] ?? 0);
$multigrado = ($_GET['tipo'] ?? '') === 'multigrado';

if (!$sede_id || $nivel === '' || (!$grado_id && !$multigrado)) {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

$where = 'e.sede_id = ? AND g.nivel = ?';
$params = [$sede_id, $nivel];
if (!$multigrado) {
    $where .= ' AND e.grado_id = ?';
    $params[] = $grado_id;
}

$stmt = $pdo->prepare("
    SELECT e.apellido, e.nombre, e.documento_tipo, e.documento_numero, g.nombre AS grado,
           e.genero, e.fecha_nacimiento, e.direccion, e.telefono, e.email,
           e.nombre_acudiente, e.telefono_acudiente, e.usuario, e.estado,
           (SELECT GROUP_CONCAT(CONCAT(p.nombre, ' ', p.apellido, ' (', pe.parentesco,
                    IF(p.telefono IS NULL, '', CONCAT(' - ', p.telefono)), ')') SEPARATOR '; ')
            FROM padres_estudiantes pe JOIN padres_familia p ON p.id = pe.padre_id
            WHERE pe.estudiante_id = e.id) AS padres
    FROM estudiantes e
    INNER JOIN grados g ON g.id = e.grado_id
    WHERE $where AND e.estado = 'activo'
    ORDER BY g.nombre, e.apellido, e.nombre
");
$stmt->execute($params);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT nombre FROM sedes WHERE id = ?");
$stmt->execute([$sede_id]);
$sede = $stmt->fetchColumn() ?: 'sede';

if ($multigrado) {
    $titulo = 'multigrado_' . $nivel;
} else {
    $stmt = $pdo->prepare("SELECT nombre FROM grados WHERE id = ?");
    $stmt->execute([$grado_id]);
    $titulo = $stmt->fetchColumn() ?: 'grado';
}

// Nombre de archivo seguro: estudiantes_<sede>_<grado>_<fecha>.csv
$slug = fn($t) => trim(preg_replace('/[^a-z0-9]+/', '_', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $t) ?: $t)), '_');
$archivo = 'estudiantes_' . $slug($sede) . '_' . $slug($titulo) . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $archivo . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel reconoce las tildes
$sep = ';';                   // Excel en español usa punto y coma

fputcsv($out, ['Sede: ' . $sede, 'Lista: ' . ($multigrado ? 'Multigrado ' . ucfirst($nivel) : $titulo), 'Generado: ' . date('d/m/Y H:i')], $sep);
fputcsv($out, [], $sep);
fputcsv($out, ['N°', 'Apellidos', 'Nombres', 'Tipo doc.', 'Documento', 'Grado', 'Género', 'Fecha de nacimiento',
               'Dirección', 'Teléfono', 'Correo', 'Acudiente', 'Tel. acudiente', 'Padres / acudientes registrados', 'Usuario'], $sep);

foreach ($estudiantes as $i => $e) {
    fputcsv($out, [
        $i + 1,
        $e['apellido'],
        $e['nombre'],
        $e['documento_tipo'],
        $e['documento_numero'],
        $e['grado'],
        $e['genero'],
        $e['fecha_nacimiento'] ? date('d/m/Y', strtotime($e['fecha_nacimiento'])) : '',
        $e['direccion'],
        $e['telefono'],
        $e['email'],
        $e['nombre_acudiente'],
        $e['telefono_acudiente'],
        $e['padres'],
        $e['usuario'],
    ], $sep);
}

fputcsv($out, [], $sep);
fputcsv($out, ['Total de estudiantes: ' . count($estudiantes)], $sep);
fclose($out);
exit();
