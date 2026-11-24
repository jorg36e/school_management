<?php
/**
 * Ficha del estudiante: datos personales, acudientes, notas del periodo actual,
 * asistencia, historial de grados y formulario para cambiarlo de grado.
 */
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';
require_once '../../../helpers/NotasEstudianteHelper.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT e.*, g.nombre AS grado_nombre, g.nivel AS grado_nivel, s.nombre AS sede_nombre
    FROM estudiantes e
    LEFT JOIN grados g ON g.id = e.grado_id
    LEFT JOIN sedes s ON s.id = e.sede_id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    header('Location: ../../users/list_students.php');
    exit();
}

// Enlace de regreso al grado desde el que se llegó
$volver = '../../users/list_students.php';
if (!empty($_GET['sede_id']) && !empty($_GET['nivel'])) {
    $params = ['sede_id' => $_GET['sede_id'], 'nivel' => $_GET['nivel']];
    if (($_GET['tipo'] ?? '') === 'multigrado') {
        $params['tipo'] = 'multigrado';
    } elseif (!empty($_GET['grado_id'])) {
        $params = ['id' => $_GET['grado_id']] + $params;
    }
    $volver = 'view_grade.php?' . http_build_query($params);
} elseif ($estudiante['grado_id']) {
    $volver = 'view_grade.php?' . http_build_query(['id' => $estudiante['grado_id'], 'sede_id' => $estudiante['sede_id'], 'nivel' => $estudiante['grado_nivel']]);
}

// Padres / acudientes registrados
$stmt = $pdo->prepare("
    SELECT p.id, p.nombre, p.apellido, p.telefono, p.email, pe.parentesco
    FROM padres_estudiantes pe
    INNER JOIN padres_familia p ON p.id = pe.padre_id
    WHERE pe.estudiante_id = ?
    ORDER BY p.nombre
");
$stmt->execute([$id]);
$padres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Notas del periodo actual
$periodo = NotasEstudianteHelper::obtenerPeriodoActual($pdo);
$materias = NotasEstudianteHelper::obtenerNotas($pdo, $id, $periodo ? (int) $periodo['id'] : null);
$promedio = NotasEstudianteHelper::promedio($materias);

// Asistencia del periodo actual
$asistencia = ['presente' => 0, 'ausente' => 0, 'tardanza' => 0, 'justificado' => 0];
if ($periodo && $periodo['fecha_inicio'] && $periodo['fecha_fin']) {
    $stmt = $pdo->prepare("
        SELECT estado, COUNT(*) FROM asistencias
        WHERE estudiante_id = ? AND fecha BETWEEN ? AND ?
        GROUP BY estado
    ");
    $stmt->execute([$id, $periodo['fecha_inicio'], $periodo['fecha_fin']]);
    foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $estado => $total) {
        $asistencia[strtolower($estado)] = (int) $total;
    }
}

// Historial de grados
$stmt = $pdo->prepare("
    SELECT h.fecha_cambio, ga.nombre AS anterior, gn.nombre AS nuevo
    FROM historial_cambios_grado h
    LEFT JOIN grados ga ON ga.id = h.grado_anterior
    LEFT JOIN grados gn ON gn.id = h.grado_nuevo
    WHERE h.estudiante_id = ?
    ORDER BY h.fecha_cambio DESC, h.id DESC
");
$stmt->execute([$id]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grados disponibles para el cambio (todas las sedes activas, agrupados por sede y nivel)
$grados = $pdo->query("
    SELECT g.id, g.nombre, g.nivel, s.nombre AS sede
    FROM grados g
    INNER JOIN sedes s ON s.id = g.sede_id
    WHERE s.estado = 'activo' AND (g.estado = 'activo' OR g.estado IS NULL)
    ORDER BY s.nombre, FIELD(g.nivel, 'preescolar', 'primaria', 'secundaria', 'media'), g.orden, g.nombre
")->fetchAll(PDO::FETCH_ASSOC);
$grados_agrupados = [];
foreach ($grados as $g) {
    $grados_agrupados[$g['sede'] . ' · ' . ucfirst((string) $g['nivel'])][] = $g;
}

$edad = $estudiante['fecha_nacimiento'] ? (new DateTime($estudiante['fecha_nacimiento']))->diff(new DateTime())->y : null;
$activo = strtolower($estudiante['estado']) === 'activo';

$topbar_title = 'Ficha del estudiante';
$page_css = '
    .ficha-header { display:flex; align-items:center; gap:1.25rem; flex-wrap:wrap; }
    .ficha-avatar { width:72px; height:72px; border-radius:50%; background:var(--info-soft); color:var(--primary-light);
                    display:flex; align-items:center; justify-content:center; font-size:1.6rem; font-weight:700; overflow:hidden; }
    .ficha-avatar img { width:100%; height:100%; object-fit:cover; }
    .ficha-header h1 { font-size:1.5rem; }
    .ficha-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem; }
    .datos { display:grid; grid-template-columns: 140px 1fr; gap:0.5rem 1rem; font-size:0.925rem; }
    .datos dt { color:var(--muted-text); }
    .datos dd { color:var(--text-primary); word-break:break-word; }
    .nota-baja, .data-table td.nota-baja { color:#b91c1c; font-weight:700; }
    .mini-stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem; text-align:center; }
    .mini-stats strong { display:block; font-size:1.35rem; }
    .mini-stats span { font-size:0.75rem; color:var(--muted-text); }
    #cambiar-grado:target { box-shadow:0 0 0 3px rgba(37,99,235,0.35); }
';
include '../../components/layout_start.php';
?>
<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i>
        <?php echo $_GET['success'] === 'grado_cambiado' ? 'El estudiante cambió de grado correctamente.' : 'Cambios guardados.'; ?>
    </div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="content-card">
    <div class="page-header" style="margin-bottom:0;">
        <div class="ficha-header">
            <div class="ficha-avatar">
                <?php if (!empty($estudiante['foto'])): ?>
                    <img src="<?php echo htmlspecialchars($estudiante['foto']); ?>" alt="">
                <?php else: ?>
                    <?php echo htmlspecialchars(mb_strtoupper(mb_substr($estudiante['nombre'], 0, 1) . mb_substr($estudiante['apellido'], 0, 1))); ?>
                <?php endif; ?>
            </div>
            <div>
                <h1><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></h1>
                <div class="text-muted">
                    <?php echo htmlspecialchars($estudiante['grado_nombre'] ?? 'Sin grado'); ?>
                    <?php if ($estudiante['sede_nombre']): ?>· <?php echo htmlspecialchars($estudiante['sede_nombre']); ?><?php endif; ?>
                    · <span class="status-badge <?php echo $activo ? 'status-active' : 'status-inactive'; ?>"><?php echo $activo ? 'Activo' : 'Inactivo'; ?></span>
                </div>
            </div>
        </div>
        <div class="page-actions">
            <a href="<?php echo htmlspecialchars($volver); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            <a href="../../users/edit_student.php?id=<?php echo $id; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Editar datos</a>
        </div>
    </div>
</div>

<div class="ficha-grid">
    <div class="content-card">
        <div class="card-header"><h2>Datos personales</h2></div>
        <dl class="datos">
            <dt>Documento</dt><dd><?php echo htmlspecialchars(trim(($estudiante['documento_tipo'] ?? '') . ' ' . ($estudiante['documento_numero'] ?? '')) ?: '—'); ?></dd>
            <dt>Nacimiento</dt><dd><?php echo $estudiante['fecha_nacimiento'] ? date('d/m/Y', strtotime($estudiante['fecha_nacimiento'])) . " ($edad años)" : '—'; ?></dd>
            <dt>Género</dt><dd><?php echo htmlspecialchars($estudiante['genero'] ?? '—'); ?></dd>
            <dt>Dirección</dt><dd><?php echo htmlspecialchars($estudiante['direccion'] ?? '—'); ?></dd>
            <dt>Teléfono</dt><dd><?php echo htmlspecialchars($estudiante['telefono'] ?? '—'); ?></dd>
            <dt>Correo</dt><dd><?php echo htmlspecialchars($estudiante['email'] ?? '—'); ?></dd>
            <dt>Usuario</dt><dd><?php echo htmlspecialchars($estudiante['usuario'] ?? '—'); ?></dd>
        </dl>
    </div>

    <div class="content-card">
        <div class="card-header with-actions">
            <h2>Padres y acudientes</h2>
            <a href="../../users/list_parents.php" class="btn btn-sm btn-outline">Gestionar</a>
        </div>
        <?php if (!$padres && empty($estudiante['nombre_acudiente'])): ?>
            <p class="text-muted">No hay acudientes registrados. Puede vincularlos desde «Padres de familia».</p>
        <?php endif; ?>
        <?php foreach ($padres as $p): ?>
            <div style="padding:0.6rem 0; border-bottom:1px solid var(--border-color);">
                <strong><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido']); ?></strong>
                <span class="badge badge-info"><?php echo htmlspecialchars(ucfirst($p['parentesco'])); ?></span>
                <div class="text-muted" style="font-size:0.85rem;">
                    <?php echo htmlspecialchars(implode(' · ', array_filter([$p['telefono'], $p['email']])) ?: 'Sin datos de contacto'); ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!empty($estudiante['nombre_acudiente'])): ?>
            <div style="padding:0.6rem 0;">
                <strong><?php echo htmlspecialchars($estudiante['nombre_acudiente']); ?></strong>
                <span class="badge">Acudiente (ficha de matrícula)</span>
                <div class="text-muted" style="font-size:0.85rem;"><?php echo htmlspecialchars($estudiante['telefono_acudiente'] ?? ''); ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="content-card">
    <div class="card-header with-actions">
        <h2>Rendimiento <?php echo $periodo ? '· ' . htmlspecialchars($periodo['nombre']) : ''; ?></h2>
        <span>Promedio: <strong class="<?php echo $promedio !== null && $promedio < 3 ? 'nota-baja' : ''; ?>"><?php echo $promedio !== null ? number_format($promedio, 1) : '—'; ?></strong></span>
    </div>
    <div class="mini-stats" style="margin-bottom:1.25rem;">
        <div><strong><?php echo $asistencia['presente']; ?></strong><span>Asistencias</span></div>
        <div><strong><?php echo $asistencia['ausente']; ?></strong><span>Fallas</span></div>
        <div><strong><?php echo $asistencia['tardanza']; ?></strong><span>Llegadas tarde</span></div>
        <div><strong><?php echo $asistencia['justificado']; ?></strong><span>Justificadas</span></div>
    </div>
    <?php if (!$materias): ?>
        <p class="text-muted">El grado del estudiante aún no tiene materias asignadas.</p>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead><tr><th>Materia</th><th>Docente</th><th class="text-center">Tareas</th><th class="text-center">Evaluaciones</th><th class="text-center">Autoevaluación</th><th class="text-center">Definitiva</th><th>Desempeño</th></tr></thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($m['materia']); ?></strong></td>
                            <td><?php echo htmlspecialchars($m['profesor'] ?? '—'); ?></td>
                            <?php foreach (['TAREAS', 'EVALUACIONES', 'AUTOEVALUACION'] as $cat): ?>
                                <td class="text-center"><?php echo number_format((float) ($m['categorias'][$cat]['valor'] ?? 0), 1); ?></td>
                            <?php endforeach; ?>
                            <td class="text-center <?php echo $m['tiene_notas'] && $m['definitiva'] < 3 ? 'nota-baja' : ''; ?>">
                                <strong><?php echo $m['tiene_notas'] ? number_format($m['definitiva'], 1) : '—'; ?></strong>
                            </td>
                            <td>
                                <?php if ($m['tiene_notas']): ?>
                                    <span class="badge <?php echo ['superior' => 'badge-success', 'alto' => 'badge-info', 'basico' => 'badge-warning', 'bajo' => 'badge-danger'][$m['desempeno']['clase']]; ?>"><?php echo $m['desempeno']['texto']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin notas</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="ficha-grid">
    <div class="content-card" id="cambiar-grado">
        <div class="card-header">
            <h2>Cambiar de grado</h2>
            <p>El cambio queda registrado en el historial. Las notas ya registradas se conservan.</p>
        </div>
        <?php if (!$grados): ?>
            <p class="text-muted">No hay grados creados. Créelos desde «Gestión de Sedes».</p>
        <?php else: ?>
            <form method="POST" action="assign_student.php" onsubmit="return confirm('¿Confirma el cambio de grado?');">
                <input type="hidden" name="estudiante_id" value="<?php echo $id; ?>">
                <input type="hidden" name="origen" value="view_student">
                <div class="form-group">
                    <label for="grado_id" class="required-label">Nuevo grado</label>
                    <select id="grado_id" name="grado_id" required>
                        <option value="">Seleccione…</option>
                        <?php foreach ($grados_agrupados as $grupo => $lista): ?>
                            <optgroup label="<?php echo htmlspecialchars($grupo); ?>">
                                <?php foreach ($lista as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo $g['id'] == $estudiante['grado_id'] ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($g['nombre']); ?><?php echo $g['id'] == $estudiante['grado_id'] ? ' (actual)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-right-left"></i> Cambiar de grado</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="content-card">
        <div class="card-header"><h2>Historial de grados</h2></div>
        <?php if (!$historial): ?>
            <p class="text-muted">Sin cambios registrados.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead><tr><th>Fecha</th><th>De</th><th>A</th></tr></thead>
                    <tbody>
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($h['fecha_cambio'])); ?></td>
                                <td><?php echo htmlspecialchars($h['anterior'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($h['nuevo'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../components/layout_end.php'; ?>
