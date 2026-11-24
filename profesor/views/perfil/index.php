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
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');

    if ($nombre === '' || $apellido === '') {
        $error = 'El nombre y el apellido son obligatorios.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM profesores WHERE email = ? AND id <> ?");
        $stmt->execute([$email, $_SESSION['profesor_id']]);
        if ($email !== '' && $stmt->fetch()) {
            $error = 'Ese correo ya lo usa otro docente.';
        } else {
            $pdo->prepare("UPDATE profesores SET nombre = ?, apellido = ?, email = ?, telefono = ?, especialidad = ? WHERE id = ?")
                ->execute([$nombre, $apellido, $email ?: null, $telefono ?: null, $especialidad ?: null, $_SESSION['profesor_id']]);
            // Igual que lista_calificaciones.php: nombre y apellido por separado (la barra superior los une)
            $_SESSION['profesor_nombre'] = $nombre;
            $_SESSION['profesor_apellido'] = $apellido;
            $mensaje = 'Sus datos se actualizaron correctamente.';
        }
    }
}

$stmt = $pdo->prepare("SELECT p.*, s.nombre AS sede_nombre FROM profesores p LEFT JOIN sedes s ON s.id = p.sede_id WHERE p.id = ?");
$stmt->execute([$_SESSION['profesor_id']]);
$profesor = $stmt->fetch(PDO::FETCH_ASSOC);

// Si la especialidad guardada es el id de una asignatura (así la guarda el formulario del admin), mostrar su nombre
if (ctype_digit((string) $profesor['especialidad'])) {
    $stmt = $pdo->prepare("SELECT nombre FROM asignaturas WHERE id = ?");
    $stmt->execute([$profesor['especialidad']]);
    $profesor['especialidad'] = $stmt->fetchColumn() ?: $profesor['especialidad'];
}

// Asignaciones (materia + grado)
$stmt = $pdo->prepare("
    SELECT m.nombre AS materia, g.nombre AS grado, g.nivel, s.nombre AS sede,
           (SELECT COUNT(*) FROM estudiantes e WHERE e.grado_id = ap.grado_id AND e.estado = 'activo') AS estudiantes
    FROM asignaciones_profesor ap
    INNER JOIN materias m ON m.id = ap.materia_id
    LEFT JOIN grados g ON g.id = ap.grado_id
    LEFT JOIN sedes s ON s.id = g.sede_id
    WHERE ap.profesor_id = ? AND ap.estado = 'activo'
    ORDER BY g.nombre, m.nombre
");
$stmt->execute([$_SESSION['profesor_id']]);
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Mi Perfil';
$page_css = '
    .perfil-header { display:flex; align-items:center; gap:1.25rem; }
    .perfil-avatar { width:72px; height:72px; border-radius:16px; background:#1a2b40; color:#fff; display:flex;
                     align-items:center; justify-content:center; font-size:1.75rem; font-weight:700; }
';
include __DIR__ . '/../components/layout_start.php';
?>
<?php if ($mensaje): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="content-card">
    <div class="page-header" style="margin-bottom:0;">
        <div class="perfil-header">
            <div class="perfil-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($profesor['nombre'], 0, 1))); ?></div>
            <div>
                <h1 style="font-size:1.4rem;"><?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></h1>
                <div class="text-muted">
                    Docente · <?php echo htmlspecialchars($profesor['sede_nombre'] ?? 'Sin sede asignada'); ?>
                    · usuario <strong><?php echo htmlspecialchars($profesor['usuario']); ?></strong>
                </div>
            </div>
        </div>
        <a href="cambiar_password.php" class="btn btn-secondary"><i class="fas fa-key"></i> Cambiar contraseña</a>
    </div>
</div>

<div class="form-sections">
    <div class="content-card">
        <div class="card-header"><h2>Mis datos</h2><p>La sede y las asignaciones las gestiona la administración.</p></div>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre" class="required-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($profesor['nombre']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="apellido" class="required-label">Apellido</label>
                    <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($profesor['apellido']); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($profesor['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="telefono">Celular</label>
                    <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($profesor['telefono'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="especialidad">Especialidad</label>
                <input type="text" id="especialidad" name="especialidad" value="<?php echo htmlspecialchars($profesor['especialidad'] ?? ''); ?>" placeholder="Ej.: Licenciado en Matemáticas">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <div class="card-header"><h2>Mis asignaciones</h2><p>Materias y grados que tiene a cargo.</p></div>
        <?php if (!$asignaciones): ?>
            <div class="empty-state"><i class="fas fa-book"></i>Aún no tiene materias asignadas.</div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Materia</th><th>Grado</th><th>Sede</th><th class="text-center">Estudiantes</th></tr></thead>
                    <tbody>
                        <?php foreach ($asignaciones as $a): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($a['materia']); ?></strong></td>
                                <td><?php echo htmlspecialchars($a['grado'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($a['sede'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo (int) $a['estudiantes']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../components/layout_end.php'; ?>
