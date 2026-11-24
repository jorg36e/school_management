<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';
require_once __DIR__ . '/_parent_helpers.php';

$id = (int) ($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT nombre, apellido FROM padres_familia WHERE id = ?");
    $stmt->execute([$id]);
    $padre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$padre) {
        header('Location: list_parents.php?error=not_found');
        exit();
    }

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM padres_estudiantes WHERE padre_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM padres_familia WHERE id = ?")->execute([$id]);
    registrarActividadPadre($pdo, $id, 'eliminar',
        'Eliminación del acudiente ' . $padre['nombre'] . ' ' . $padre['apellido']);
    $pdo->commit();

    header('Location: list_parents.php?success=deleted');
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al eliminar acudiente: ' . $e->getMessage());
    header('Location: list_parents.php?error=delete_failed');
}
exit();
