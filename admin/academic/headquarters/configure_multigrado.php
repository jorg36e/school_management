<?php
/**
 * Configuración del grupo multigrado de un nivel en una sede:
 * tipo de enseñanza, docente orientador, observaciones y grados que lo integran.
 * Parámetros: sede_id, nivel.
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$sede_id = (int) ($_REQUEST['sede_id'] ?? 0);
$nivel = trim($_REQUEST['nivel'] ?? '');

$stmt = $pdo->prepare("SELECT id, nombre, tipo_ensenanza FROM sedes WHERE id = ?");
$stmt->execute([$sede_id]);
$sede = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sede || $nivel === '') {
    header('Location: ../../users/list_headquarters.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM niveles_configuracion WHERE sede_id = ? AND nivel = ?");
$stmt->execute([$sede_id, $nivel]);
$nivel_config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'tipo_ensenanza' => $sede['tipo_ensenanza'] ?: 'unigrado',
    'observaciones' => '',
    'profesor_id' => null,
];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = in_array($_POST['tipo_ensenanza'] ?? '', ['unigrado', 'multigrado'], true) ? $_POST['tipo_ensenanza'] : 'multigrado';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $profesor_id = (int) ($_POST['profesor_id'] ?? 0) ?: null;

    try {
        if (!empty($nivel_config['id'])) {
            $pdo->prepare("UPDATE niveles_configuracion SET tipo_ensenanza = ?, observaciones = ?, profesor_id = ? WHERE id = ?")
                ->execute([$tipo, $observaciones ?: null, $profesor_id, $nivel_config['id']]);
        } else {
            $pdo->prepare("INSERT INTO niveles_configuracion (sede_id, nivel, tipo_ensenanza, observaciones, profesor_id) VALUES (?, ?, ?, ?, ?)")
                ->execute([$sede_id, $nivel, $tipo, $observaciones ?: null, $profesor_id]);
        }

        $pdo->prepare("
            INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha)
            VALUES ('niveles_configuracion', ?, 'actualizar', ?, ?, NOW())
        ")->execute([$sede_id, 'Configuración de ' . ucfirst($nivel) . ' en ' . $sede['nombre'] . ': ' . $tipo, $_SESSION['admin_id']]);

        header('Location: view_level.php?' . http_build_query(['sede_id' => $sede_id, 'nivel' => $nivel, 'success' => 'config_actualizada']));
        exit();
    } catch (PDOException $e) {
        $error = 'No se pudo guardar la configuración: ' . $e->getMessage();
        $nivel_config = array_merge($nivel_config, ['tipo_ensenanza' => $tipo, 'observaciones' => $observaciones, 'profesor_id' => $profesor_id]);
    }
}

// Grados del nivel con su número de estudiantes
$stmt = $pdo->prepare("
    SELECT g.id, g.nombre,
           (SELECT COUNT(*) FROM estudiantes e WHERE e.grado_id = g.id AND e.estado = 'activo') AS estudiantes
    FROM grados g
    WHERE g.sede_id = ? AND g.nivel = ?
    ORDER BY g.orden, g.nombre
");
$stmt->execute([$sede_id, $nivel]);
$grados = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_estudiantes = array_sum(array_column($grados, 'estudiantes'));

// Docentes de la sede (y los que no tienen sede) para orientar el grupo
$stmt = $pdo->prepare("
    SELECT id, nombre, apellido FROM profesores
    WHERE estado = 'activo' AND (sede_id = ? OR sede_id IS NULL)
    ORDER BY nombre, apellido
");
$stmt->execute([$sede_id]);
$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$topbar_title = 'Configurar multigrado';
include '../../components/layout_start.php';
?>
<div class="page-header">
    <div>
        <h1><?php echo htmlspecialchars(ucfirst($nivel)); ?> · <?php echo htmlspecialchars($sede['nombre']); ?></h1>
        <p class="page-subtitle">Configuración del grupo multigrado: un mismo docente atiende varios grados en una sola aula.</p>
    </div>
    <a href="view_level.php?<?php echo http_build_query(['sede_id' => $sede_id, 'nivel' => $nivel]); ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver al nivel
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="form-sections">
    <div class="content-card">
        <div class="card-header"><h2>Configuración</h2></div>
        <form method="POST">
            <input type="hidden" name="sede_id" value="<?php echo $sede_id; ?>">
            <input type="hidden" name="nivel" value="<?php echo htmlspecialchars($nivel); ?>">

            <div class="form-group">
                <label class="form-label">Tipo de enseñanza</label>
                <label class="form-check">
                    <input type="radio" name="tipo_ensenanza" value="multigrado" <?php echo $nivel_config['tipo_ensenanza'] === 'multigrado' ? 'checked' : ''; ?>>
                    <span><strong>Multigrado</strong> — los grados del nivel forman un solo grupo.</span>
                </label>
                <label class="form-check">
                    <input type="radio" name="tipo_ensenanza" value="unigrado" <?php echo $nivel_config['tipo_ensenanza'] !== 'multigrado' ? 'checked' : ''; ?>>
                    <span><strong>Unigrado</strong> — cada grado tiene su propio grupo.</span>
                </label>
            </div>

            <div class="form-group">
                <label for="profesor_id">Docente orientador del grupo</label>
                <select id="profesor_id" name="profesor_id">
                    <option value="">Sin asignar</option>
                    <?php foreach ($profesores as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo (int) ($nivel_config['profesor_id'] ?? 0) === (int) $p['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text">Las materias de cada grado se siguen asignando desde «Profesores».</small>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea id="observaciones" name="observaciones" placeholder="Aula, jornada, modelo pedagógico (p. ej. Escuela Nueva)…"><?php echo htmlspecialchars($nivel_config['observaciones'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar configuración</button>
            </div>
        </form>
    </div>

    <div class="content-card">
        <div class="card-header with-actions">
            <h2>Grados del grupo</h2>
            <span class="badge badge-info"><?php echo $total_estudiantes; ?> estudiantes</span>
        </div>
        <?php if (!$grados): ?>
            <div class="empty-state">
                <i class="fas fa-school"></i>
                Este nivel aún no tiene grados. Agréguelos desde la vista del nivel.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Grado</th><th class="text-center">Estudiantes</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($grados as $g): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($g['nombre']); ?></strong></td>
                                <td class="text-center"><?php echo (int) $g['estudiantes']; ?></td>
                                <td class="text-right">
                                    <a class="btn btn-sm btn-outline" href="view_grade.php?<?php echo http_build_query(['id' => $g['id'], 'sede_id' => $sede_id, 'nivel' => $nivel]); ?>">Ver estudiantes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted" style="font-size:0.85rem; margin-top:0.75rem;">
                Para agregar o quitar grados use «Agregar grado» en la vista del nivel.
            </p>
        <?php endif; ?>
    </div>
</div>
<?php include '../../components/layout_end.php'; ?>
