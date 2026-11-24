<?php
/**
 * Asigna (o cambia) el grado de un estudiante.
 * Lo usan:
 *  - El modal "Asignar estudiante" de view_grade.php (estudiante_id, grado_id, sede_id, nivel, [tipo]).
 *  - El formulario "Cambiar de grado" de view_student.php (además envía origen=view_student).
 *  - El formulario de view_group.php (estudiante_id, grupo_id).
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

$estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);
$grado_id = (int) ($_POST['grado_id'] ?? 0);
$grupo_id = (int) ($_POST['grupo_id'] ?? 0);
$tipo = ($_POST['tipo'] ?? '') === 'multigrado' ? 'multigrado' : 'unigrado';
$origen = $_POST['origen'] ?? 'view_grade';

/** Arma la URL de regreso según la página que envió el formulario */
function urlRegreso(string $origen, int $estudiante_id, array $grado, string $tipo, array $extra): string
{
    if ($origen === 'view_student') {
        return 'view_student.php?' . http_build_query(array_merge(['id' => $estudiante_id], $extra));
    }
    $params = ['sede_id' => $grado['sede_id'], 'nivel' => $grado['nivel']];
    if ($tipo === 'multigrado') {
        $params['tipo'] = 'multigrado';
    } else {
        $params = ['id' => $grado['id']] + $params;
    }
    return 'view_grade.php?' . http_build_query(array_merge($params, $extra));
}

try {
    $stmt = $pdo->prepare("SELECT id, nombre, apellido, grado_id, sede_id FROM estudiantes WHERE id = ?");
    $stmt->execute([$estudiante_id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$estudiante) {
        throw new Exception('El estudiante seleccionado no existe.');
    }

    // Asignación a un grupo (módulo de grupos)
    if ($grupo_id > 0 && $grado_id === 0) {
        $stmt = $pdo->prepare("SELECT id FROM grupos WHERE id = ?");
        $stmt->execute([$grupo_id]);
        if (!$stmt->fetch()) {
            throw new Exception('El grupo no existe.');
        }
        $pdo->prepare("UPDATE estudiantes SET grupo_id = ? WHERE id = ?")->execute([$grupo_id, $estudiante_id]);
        header('Location: view_group.php?id=' . $grupo_id . '&success=1');
        exit();
    }

    $stmt = $pdo->prepare("SELECT id, nombre, sede_id, nivel FROM grados WHERE id = ?");
    $stmt->execute([$grado_id]);
    $grado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$grado) {
        throw new Exception('Seleccione un grado válido.');
    }

    $grado_anterior = $estudiante['grado_id'] ? (int) $estudiante['grado_id'] : null;
    if ($grado_anterior === (int) $grado['id']) {
        header('Location: ' . urlRegreso($origen, $estudiante_id, $grado, $tipo,
            ['error' => $estudiante['nombre'] . ' ya está en ' . $grado['nombre'] . '.']));
        exit();
    }

    $pdo->beginTransaction();

    // El estudiante pasa a la sede y nivel del grado
    $pdo->prepare("UPDATE estudiantes SET grado_id = ?, sede_id = ?, nivel = ? WHERE id = ?")
        ->execute([$grado['id'], $grado['sede_id'], $grado['nivel'], $estudiante_id]);

    $pdo->prepare("
        INSERT INTO historial_cambios_grado (estudiante_id, grado_anterior, grado_nuevo, fecha_cambio)
        VALUES (?, ?, ?, NOW())
    ")->execute([$estudiante_id, $grado_anterior, $grado['id']]);

    $pdo->prepare("
        INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha)
        VALUES ('estudiantes', ?, 'actualizar', ?, ?, NOW())
    ")->execute([
        $estudiante_id,
        ($grado_anterior ? 'Cambio de grado de ' : 'Asignación de grado a ')
            . $estudiante['nombre'] . ' ' . $estudiante['apellido'] . ': ' . $grado['nombre'],
        $_SESSION['admin_id'],
    ]);

    $pdo->commit();

    $exito = $grado_anterior ? 'grado_cambiado' : 'estudiante_asignado';
    header('Location: ' . urlRegreso($origen, $estudiante_id, $grado, $tipo, ['success' => $exito]));
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al asignar estudiante: ' . $e->getMessage());

    $regreso = $origen === 'view_student'
        ? 'view_student.php?' . http_build_query(['id' => $estudiante_id, 'error' => $e->getMessage()])
        : 'view_level.php?' . http_build_query(['sede_id' => $_POST['sede_id'] ?? '', 'nivel' => $_POST['nivel'] ?? '', 'error' => $e->getMessage()]);
    header('Location: ' . $regreso);
    exit();
}
