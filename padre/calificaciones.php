<?php
require_once __DIR__ . '/includes/auth.php';

$periodos = NotasEstudianteHelper::obtenerPeriodos($pdo);
$periodo_actual = NotasEstudianteHelper::obtenerPeriodoActual($pdo);

// Hijo seleccionado (solo puede consultar a sus propios hijos)
$hijo = null;
if ($hijos) {
    $hijo = buscarHijo($hijos, (int) ($_GET['estudiante_id'] ?? 0)) ?? $hijos[0];
}

// Periodo seleccionado
$periodo = $periodo_actual;
if (!empty($_GET['periodo_id'])) {
    foreach ($periodos as $p) {
        if ((int) $p['id'] === (int) $_GET['periodo_id']) {
            $periodo = $p;
        }
    }
}

$materias = [];
$asistencia = ['ausente' => 0, 'tardanza' => 0, 'justificado' => 0];
if ($hijo) {
    $materias = NotasEstudianteHelper::obtenerNotas($pdo, (int) $hijo['id'], $periodo ? (int) $periodo['id'] : null);

    if ($periodo && $periodo['fecha_inicio'] && $periodo['fecha_fin']) {
        $stmt = $pdo->prepare("
            SELECT estado, COUNT(*) AS total
            FROM asistencias
            WHERE estudiante_id = ? AND fecha BETWEEN ? AND ?
            GROUP BY estado
        ");
        $stmt->execute([$hijo['id'], $periodo['fecha_inicio'], $periodo['fecha_fin']]);
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $estado => $total) {
            $asistencia[strtolower($estado)] = (int) $total;
        }
    }
}

$promedio = NotasEstudianteHelper::promedio($materias);
$materias_bajas = count(array_filter($materias, fn($m) => $m['tiene_notas'] && $m['definitiva'] < 3.0));
$nombres_categoria = ['TAREAS' => 'Tareas', 'EVALUACIONES' => 'Evaluaciones', 'AUTOEVALUACION' => 'Autoevaluación'];

$titulo = 'Notas';
$pagina_activa = 'calificaciones';
include __DIR__ . '/includes/layout_start.php';
?>
<section class="welcome">
    <h1>Notas</h1>
    <p>Consulte el rendimiento de sus hijos por materia y periodo.</p>
</section>

<?php if (!$hijo): ?>
    <div class="content-card">
        <div class="empty-state">
            <i class="fas fa-children"></i>
            No tiene estudiantes vinculados a su cuenta. Comuníquese con la institución.
        </div>
    </div>
<?php else: ?>
    <form method="GET" class="filtros">
        <?php if (count($hijos) > 1): ?>
            <div class="form-group">
                <label for="estudiante_id">Estudiante</label>
                <select id="estudiante_id" name="estudiante_id" class="form-control" onchange="this.form.submit()">
                    <?php foreach ($hijos as $h): ?>
                        <option value="<?php echo $h['id']; ?>" <?php echo $h['id'] == $hijo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['nombre'] . ' ' . $h['apellido']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <input type="hidden" name="estudiante_id" value="<?php echo $hijo['id']; ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="periodo_id">Periodo</label>
            <select id="periodo_id" name="periodo_id" class="form-control" onchange="this.form.submit()">
                <?php if (!$periodos): ?>
                    <option value="">Sin periodos</option>
                <?php endif; ?>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo $periodo && $p['id'] == $periodo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['nombre'] . (!empty($p['ano_lectivo']) ? ' · ' . $p['ano_lectivo'] : '')); ?>
                        <?php echo $p['estado_periodo'] === 'en_curso' ? '(en curso)' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button type="submit" class="btn btn-primary">Consultar</button></noscript>
    </form>

    <div class="content-card" style="padding: 1rem 1.25rem;">
        <strong><?php echo htmlspecialchars($hijo['nombre'] . ' ' . $hijo['apellido']); ?></strong>
        <span class="text-muted">
            · <?php echo htmlspecialchars($hijo['grado_nombre'] ?? 'Sin grado'); ?>
            <?php if (!empty($hijo['sede_nombre'])): ?>· <?php echo htmlspecialchars($hijo['sede_nombre']); ?><?php endif; ?>
        </span>
    </div>

    <div class="resumen">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div>
                <div class="stat-value"><?php echo $promedio !== null ? number_format($promedio, 1) : '—'; ?></div>
                <div class="stat-label">Promedio del periodo</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-book"></i></div>
            <div>
                <div class="stat-value"><?php echo count($materias); ?></div>
                <div class="stat-label">Materias</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon danger"><i class="fas fa-triangle-exclamation"></i></div>
            <div>
                <div class="stat-value"><?php echo $materias_bajas; ?></div>
                <div class="stat-label">Materias en desempeño bajo</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-calendar-xmark"></i></div>
            <div>
                <div class="stat-value"><?php echo $asistencia['ausente']; ?></div>
                <div class="stat-label">
                    Inasistencias
                    <?php if ($asistencia['tardanza'] || $asistencia['justificado']): ?>
                        · <?php echo $asistencia['tardanza']; ?> tarde · <?php echo $asistencia['justificado']; ?> justif.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($materias)): ?>
        <div class="content-card">
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                Aún no hay materias asignadas al grado de <?php echo htmlspecialchars($hijo['nombre']); ?>.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($materias as $m): ?>
            <details class="materia-card">
                <summary>
                    <div>
                        <div class="materia-nombre"><?php echo htmlspecialchars($m['materia']); ?></div>
                        <div class="materia-profesor"><i class="fas fa-chalkboard-user"></i> <?php echo htmlspecialchars($m['profesor'] ?? 'Sin docente'); ?></div>
                    </div>
                    <?php if ($m['tiene_notas']): ?>
                        <span class="desempeno <?php echo $m['desempeno']['clase']; ?>"><?php echo $m['desempeno']['texto']; ?></span>
                        <span class="nota-valor materia-definitiva <?php echo $m['definitiva'] < 3 ? 'bajo' : ''; ?>">
                            <?php echo number_format($m['definitiva'], 1); ?>
                        </span>
                    <?php else: ?>
                        <span class="desempeno sin-notas">Sin notas</span>
                        <span class="nota-valor materia-definitiva text-muted">—</span>
                    <?php endif; ?>
                </summary>
                <div class="materia-detalle">
                    <div class="categorias">
                        <?php foreach ($nombres_categoria as $clave => $nombre):
                            $cat = $m['categorias'][$clave] ?? ['valor' => 0, 'porcentaje' => 0]; ?>
                            <div class="categoria">
                                <div class="categoria-nombre"><?php echo $nombre; ?> (<?php echo (int) $cat['porcentaje']; ?>%)</div>
                                <div class="categoria-valor"><?php echo number_format((float) $cat['valor'], 1); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($m['tipos'])): ?>
                        <p class="text-muted">El docente aún no ha definido las actividades evaluativas de esta materia.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Actividad</th>
                                        <th>Categoría</th>
                                        <th class="text-right">Peso</th>
                                        <th class="text-right">Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($m['tipos'] as $t):
                                        $nota = $m['notas'][$t['id']] ?? null; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($t['nombre']); ?></td>
                                            <td><?php echo $nombres_categoria[$t['categoria']] ?? htmlspecialchars($t['categoria']); ?></td>
                                            <td class="text-right"><?php echo rtrim(rtrim(number_format((float) $t['porcentaje'], 2), '0'), '.'); ?>%</td>
                                            <td class="text-right">
                                                <?php if ($nota === null): ?>
                                                    <span class="text-muted">Pendiente</span>
                                                <?php else: ?>
                                                    <span class="nota-valor <?php echo $nota < 3 ? 'bajo' : ''; ?>"><?php echo number_format($nota, 1); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
        <p class="text-muted" style="font-size: 0.8rem; margin-top: 0.5rem;">
            Escala: Superior 4.6–5.0 · Alto 4.0–4.5 · Básico 3.0–3.9 · Bajo 1.0–2.9.
            Las actividades sin nota cuentan como 0 hasta que el docente las califique.
        </p>
    <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/includes/layout_end.php'; ?>
