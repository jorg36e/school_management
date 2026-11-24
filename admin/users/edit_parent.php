<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';
require_once __DIR__ . '/_parent_helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM padres_familia WHERE id = ?");
$stmt->execute([$id]);
$padre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$padre) {
    header('Location: list_parents.php?error=not_found');
    exit();
}

$stmt = $pdo->prepare("SELECT estudiante_id, parentesco FROM padres_estudiantes WHERE padre_id = ?");
$stmt->execute([$id]);
$vinculos = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = leerDatosPadre($_POST);
    $vinculos = leerVinculos($_POST);
    $password = trim($_POST['password'] ?? '');

    try {
        validarPadre($pdo, $datos, $password, $id);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE padres_familia SET
                usuario = ?, nombre = ?, apellido = ?, documento_tipo = ?, documento_numero = ?,
                telefono = ?, email = ?, direccion = ?, ocupacion = ?, estado = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $datos['usuario'], $datos['nombre'], $datos['apellido'], $datos['documento_tipo'],
            $datos['documento_numero'], $datos['telefono'], $datos['email'], $datos['direccion'],
            $datos['ocupacion'], $datos['estado'], $id,
        ]);

        if ($password !== '') {
            $pdo->prepare("UPDATE padres_familia SET password = ? WHERE id = ?")
                ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        }

        guardarVinculos($pdo, $id, $vinculos);

        registrarActividadPadre($pdo, $id, 'actualizar',
            'Actualización del acudiente ' . $datos['nombre'] . ' ' . $datos['apellido']);

        $pdo->commit();
        header('Location: list_parents.php?success=updated');
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
        $padre = array_merge($padre, $datos);
    }
}

$estudiantes = obtenerEstudiantesActivos($pdo);
$es_edicion = true;

$topbar_title = 'Editar acudiente';
include '../components/layout_start.php';
?>
<div class="content-card">
    <div class="card-header">
        <h2>Editar a <?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?></h2>
        <p>Actualice sus datos, los estudiantes que tiene a cargo o su acceso al portal de padres.</p>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php include __DIR__ . '/_parent_form.php'; ?>
</div>
<?php include '../components/layout_end.php'; ?>
