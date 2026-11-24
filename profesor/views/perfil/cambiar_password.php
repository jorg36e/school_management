<?php
session_start();
if (!isset($_SESSION['profesor_id'])) {
    header('Location: /school_management/auth/profesor_login.php');
    exit();
}
require_once __DIR__ . '/../../../config/database.php';

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['password_actual'] ?? '';
    $nueva = $_POST['password_nueva'] ?? '';
    $confirmar = $_POST['password_confirmar'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM profesores WHERE id = ?");
    $stmt->execute([$_SESSION['profesor_id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($actual, $hash)) {
        $error = 'La contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 6) {
        $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $error = 'La confirmación no coincide con la nueva contraseña.';
    } elseif ($nueva === $actual) {
        $error = 'La nueva contraseña debe ser diferente de la actual.';
    } else {
        $pdo->prepare("UPDATE profesores SET password = ? WHERE id = ?")
            ->execute([password_hash($nueva, PASSWORD_DEFAULT), $_SESSION['profesor_id']]);
        $mensaje = 'Su contraseña se cambió correctamente. Úsela la próxima vez que inicie sesión.';
    }
}

$page_title = 'Cambiar contraseña';
include __DIR__ . '/../components/layout_start.php';
?>
<div class="content-card" style="max-width: 560px;">
    <div class="card-header">
        <h2>Cambiar contraseña</h2>
        <p>Use al menos 6 caracteres. Se recomienda combinar letras y números.</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label for="password_actual" class="required-label">Contraseña actual</label>
            <input type="password" id="password_actual" name="password_actual" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label for="password_nueva" class="required-label">Nueva contraseña</label>
            <input type="password" id="password_nueva" name="password_nueva" minlength="6" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="password_confirmar" class="required-label">Confirmar nueva contraseña</label>
            <input type="password" id="password_confirmar" name="password_confirmar" minlength="6" required autocomplete="new-password">
        </div>
        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al perfil</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Cambiar contraseña</button>
        </div>
    </form>
</div>
<?php include __DIR__ . '/../components/layout_end.php'; ?>
