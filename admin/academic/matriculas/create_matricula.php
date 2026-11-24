<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

$error = null;

// Estudiantes activos
$estudiantes = $pdo->query("
    SELECT e.id, e.nombre, e.apellido, e.documento_numero, e.grado_id, g.nombre AS grado_nombre
    FROM estudiantes e
    LEFT JOIN grados g ON g.id = e.grado_id
    WHERE e.estado = 'activo'
    ORDER BY e.apellido, e.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Grados de las sedes activas
$grados = $pdo->query("
    SELECT g.id, g.nombre, g.nivel, g.sede_id, s.nombre AS sede
    FROM grados g
    INNER JOIN sedes s ON s.id = g.sede_id
    WHERE s.estado = 'activo'
    ORDER BY s.nombre, FIELD(g.nivel, 'preescolar', 'primaria', 'secundaria', 'media'), g.orden, g.nombre
")->fetchAll(PDO::FETCH_ASSOC);

// Año lectivo activo como periodo sugerido
$ano_activo = $pdo->query("SELECT nombre FROM anos_lectivos WHERE estado = 'activo' ORDER BY fecha_inicio DESC, id DESC LIMIT 1")->fetchColumn();

$datos = [
    'estudiante_id' => (int) ($_GET['estudiante_id'] ?? 0),
    'grado_id' => 0,
    'periodo' => $ano_activo ?: 'Año Lectivo ' . date('Y'),
    'fecha_matricula' => date('Y-m-d'),
    'estado' => 'Activa',
    'observaciones' => '',
    'actualizar_grado' => true,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'estudiante_id' => (int) ($_POST['estudiante_id'] ?? 0),
        'grado_id' => (int) ($_POST['grado_id'] ?? 0),
        'periodo' => trim($_POST['periodo'] ?? ''),
        'fecha_matricula' => $_POST['fecha_matricula'] ?? '',
        'estado' => $_POST['estado'] ?? 'Activa',
        'observaciones' => trim($_POST['observaciones'] ?? ''),
        'actualizar_grado' => !empty($_POST['actualizar_grado']),
    ];

    try {
        $stmt = $pdo->prepare("SELECT id, nombre, apellido, grado_id FROM estudiantes WHERE id = ?");
        $stmt->execute([$datos['estudiante_id']]);
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$estudiante) {
            throw new Exception('Seleccione un estudiante.');
        }

        $stmt = $pdo->prepare("SELECT id, nombre, nivel, sede_id FROM grados WHERE id = ?");
        $stmt->execute([$datos['grado_id']]);
        $grado = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grado) {
            throw new Exception('Seleccione el grado al que se matricula.');
        }

        if ($datos['periodo'] === '') {
            throw new Exception('Indique el periodo o año lectivo de la matrícula.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_matricula'])) {
            throw new Exception('La fecha de matrícula no es válida.');
        }
        if (!in_array($datos['estado'], ['Activa', 'Pendiente', 'Inactiva'], true)) {
            $datos['estado'] = 'Activa';
        }

        // Evitar matrículas duplicadas para el mismo periodo
        $stmt = $pdo->prepare("SELECT id FROM matriculas WHERE estudiante_id = ? AND periodo = ? AND estado <> 'Inactiva'");
        $stmt->execute([$estudiante['id'], $datos['periodo']]);
        if ($stmt->fetch()) {
            throw new Exception($estudiante['nombre'] . ' ' . $estudiante['apellido'] . ' ya tiene una matrícula vigente para ' . $datos['periodo'] . '. Edítela desde la lista.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO matriculas (estudiante_id, grado, grado_id, sede_id, periodo, fecha_matricula, observaciones, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $estudiante['id'], $grado['nombre'], $grado['id'], $grado['sede_id'], $datos['periodo'],
            $datos['fecha_matricula'], $datos['observaciones'] ?: null, $datos['estado'],
        ]);
        $matricula_id = (int) $pdo->lastInsertId();

        // Ubicar al estudiante en el grado de la matrícula
        if ($datos['actualizar_grado'] && $datos['estado'] === 'Activa' && (int) $estudiante['grado_id'] !== (int) $grado['id']) {
            $pdo->prepare("UPDATE estudiantes SET grado_id = ?, sede_id = ?, nivel = ? WHERE id = ?")
                ->execute([$grado['id'], $grado['sede_id'], $grado['nivel'], $estudiante['id']]);
            $pdo->prepare("INSERT INTO historial_cambios_grado (estudiante_id, grado_anterior, grado_nuevo, fecha_cambio) VALUES (?, ?, ?, NOW())")
                ->execute([$estudiante['id'], $estudiante['grado_id'] ?: null, $grado['id']]);
        }

        $pdo->prepare("
            INSERT INTO actividad_log (tabla, registro_id, accion, descripcion, usuario_id, fecha)
            VALUES ('matriculas', ?, 'crear', ?, ?, NOW())
        ")->execute([
            $matricula_id,
            'Matrícula de ' . $estudiante['nombre'] . ' ' . $estudiante['apellido'] . ' en ' . $grado['nombre'] . ' (' . $datos['periodo'] . ')',
            $_SESSION['admin_id'],
        ]);

        $pdo->commit();
        header('Location: list_matriculas.php?message=' . urlencode('Matrícula registrada correctamente.'));
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}

$grados_agrupados = [];
foreach ($grados as $g) {
    $grados_agrupados[$g['sede'] . ' · ' . ucfirst((string) $g['nivel'])][] = $g;
}

$topbar_title = 'Nueva matrícula';
include '../../components/layout_start.php';
?>
<div class="content-card">
    <div class="card-header">
        <h2>Registrar matrícula</h2>
        <p>Matricule a un estudiante en un grado para el año lectivo. Si la matrícula queda activa, el estudiante se ubica en ese grado.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!$estudiantes || !$grados): ?>
        <div class="alert alert-warning">
            <i class="fas fa-info-circle"></i>
            <?php echo !$estudiantes ? 'Primero registre estudiantes en «Estudiantes».' : 'Primero cree los grados de la sede en «Gestión de Sedes».'; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-sections">
            <div class="form-section">
                <h3><i class="fas fa-user-graduate"></i> Estudiante y grado</h3>
                <div class="form-group">
                    <label for="estudiante_id" class="required-label">Estudiante</label>
                    <select id="estudiante_id" name="estudiante_id" required>
                        <option value="">Seleccione…</option>
                        <?php foreach ($estudiantes as $e): ?>
                            <option value="<?php echo $e['id']; ?>" data-grado="<?php echo (int) $e['grado_id']; ?>"
                                <?php echo $datos['estudiante_id'] === (int) $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['apellido'] . ' ' . $e['nombre'] . ($e['documento_numero'] ? ' · ' . $e['documento_numero'] : '') . ($e['grado_nombre'] ? ' (' . $e['grado_nombre'] . ')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="grado_id" class="required-label">Grado</label>
                    <select id="grado_id" name="grado_id" required>
                        <option value="">Seleccione…</option>
                        <?php foreach ($grados_agrupados as $grupo => $lista): ?>
                            <optgroup label="<?php echo htmlspecialchars($grupo); ?>">
                                <?php foreach ($lista as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo $datos['grado_id'] === (int) $g['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="actualizar_grado" value="1" <?php echo $datos['actualizar_grado'] ? 'checked' : ''; ?>>
                    Ubicar al estudiante en este grado (si la matrícula queda activa)
                </label>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-file-signature"></i> Datos de la matrícula</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="periodo" class="required-label">Año lectivo / periodo</label>
                        <input type="text" id="periodo" name="periodo" value="<?php echo htmlspecialchars($datos['periodo']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_matricula" class="required-label">Fecha</label>
                        <input type="date" id="fecha_matricula" name="fecha_matricula" value="<?php echo htmlspecialchars($datos['fecha_matricula']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <?php foreach (['Activa', 'Pendiente', 'Inactiva'] as $estado): ?>
                            <option value="<?php echo $estado; ?>" <?php echo $datos['estado'] === $estado ? 'selected' : ''; ?>><?php echo $estado; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text">«Pendiente» sirve para matrículas a las que les falta documentación.</small>
                </div>
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" placeholder="Documentos pendientes, procedencia, etc."><?php echo htmlspecialchars($datos['observaciones']); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="list_matriculas.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Registrar matrícula</button>
        </div>
    </form>
</div>

<script>
    // Al elegir un estudiante, proponer su grado actual
    document.getElementById('estudiante_id').addEventListener('change', function () {
        const grado = this.selectedOptions[0] ? this.selectedOptions[0].dataset.grado : '';
        const select = document.getElementById('grado_id');
        if (grado && grado !== '0' && !select.value) {
            select.value = grado;
        }
    });
</script>
<?php include '../../components/layout_end.php'; ?>
