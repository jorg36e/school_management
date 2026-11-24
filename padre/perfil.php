<?php
require_once __DIR__ . '/includes/auth.php';

$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'datos') {
        $telefono = trim($_POST['telefono'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo electrónico no es válido.';
        } else {
            $stmt = $pdo->prepare("UPDATE padres_familia SET telefono = ?, email = ?, direccion = ? WHERE id = ?");
            $stmt->execute([$telefono ?: null, $email ?: null, $direccion ?: null, $padre['id']]);
            $padre['telefono'] = $telefono;
            $padre['email'] = $email;
            $padre['direccion'] = $direccion;
            $mensaje = 'Sus datos de contacto se actualizaron.';
        }
    } elseif ($accion === 'password') {
        $actual = $_POST['password_actual'] ?? '';
        $nueva = $_POST['password_nueva'] ?? '';
        $confirmar = $_POST['password_confirmar'] ?? '';

        if (!password_verify($actual, $padre['password'] ?? '')) {
            $error = 'La contraseña actual no es correcta.';
        } elseif (strlen($nueva) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $error = 'La confirmación no coincide con la nueva contraseña.';
        } else {
            $stmt = $pdo->prepare("UPDATE padres_familia SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), $padre['id']]);
            $mensaje = 'Su contraseña se cambió correctamente.';
        }
    }
}

$titulo = 'Mi perfil';
$pagina_activa = 'perfil';
include __DIR__ . '/includes/layout_start.php';
?>
<section class="welcome">
    <h1>Mi perfil</h1>
    <p>Mantenga actualizados sus datos para que la institución pueda contactarle.</p>
</section>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-sections">
    <div class="content-card">
        <div class="card-header">
            <h2>Datos de contacto</h2>
            <p>
                <?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?>
                <?php if (!empty($padre['documento_numero'])): ?>
                    · <?php echo htmlspecialchars(trim(($padre['documento_tipo'] ?? '') . ' ' . $padre['documento_numero'])); ?>
                <?php endif; ?>
            </p>
        </div>
        <form method="POST">
            <input type="hidden" name="accion" value="datos">
            <div class="form-group">
                <label for="telefono">Celular</label>
                <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($padre['telefono'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($padre['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($padre['direccion'] ?? ''); ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar datos</button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2>Cambiar contraseña</h2>
            <p>Usuario: <strong><?php echo htmlspecialchars($padre['usuario']); ?></strong></p>
        </div>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="accion" value="password">
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
                <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Cambiar contraseña</button>
            </div>
        </form>
    </div>
</div>

<div class="content-card">
    <div class="card-header">
        <h2>Estudiantes a cargo</h2>
    </div>
    <?php if (empty($hijos)): ?>
        <p class="text-muted">No tiene estudiantes vinculados.</p>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Estudiante</th><th>Parentesco</th><th>Grado</th><th>Sede</th></tr></thead>
                <tbody>
                    <?php foreach ($hijos as $h): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($h['nombre'] . ' ' . $h['apellido']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($h['parentesco'])); ?></td>
                            <td><?php echo htmlspecialchars($h['grado_nombre'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($h['sede_nombre'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.75rem;">
            Si falta algún estudiante o hay un dato incorrecto, comuníquese con la institución.
        </p>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/layout_end.php'; ?>
