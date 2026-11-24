<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';
require_once __DIR__ . '/_parent_helpers.php';

$padre = [];
$vinculos = [];
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $padre = leerDatosPadre($_POST);
    $vinculos = leerVinculos($_POST);

    try {
        validarPadre($pdo, $padre, $_POST['password'] ?? '', null);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO padres_familia
                (usuario, password, nombre, apellido, documento_tipo, documento_numero,
                 telefono, email, direccion, ocupacion, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')
        ");
        $password = trim($_POST['password'] ?? '');
        $stmt->execute([
            $padre['usuario'],
            $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null,
            $padre['nombre'],
            $padre['apellido'],
            $padre['documento_tipo'],
            $padre['documento_numero'],
            $padre['telefono'],
            $padre['email'],
            $padre['direccion'],
            $padre['ocupacion'],
        ]);
        $padre_id = (int) $pdo->lastInsertId();

        guardarVinculos($pdo, $padre_id, $vinculos);

        registrarActividadPadre($pdo, $padre_id, 'crear',
            'Registro del acudiente ' . $padre['nombre'] . ' ' . $padre['apellido']);

        $pdo->commit();
        header('Location: list_parents.php?success=created');
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

$estudiantes = obtenerEstudiantesActivos($pdo);
$es_edicion = false;

$topbar_title = 'Nuevo acudiente';
include '../components/layout_start.php';
?>
<div class="content-card">
    <div class="card-header">
        <h2>Registrar padre de familia o acudiente</h2>
        <p>Registre sus datos, vincule a los estudiantes que tiene a cargo y, si lo desea, créele un acceso al portal de padres.</p>
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
