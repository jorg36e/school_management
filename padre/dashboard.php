<?php
require_once __DIR__ . '/includes/auth.php';

$periodo = NotasEstudianteHelper::obtenerPeriodoActual($pdo);

// Resumen académico de cada hijo en el periodo actual
foreach ($hijos as &$hijo) {
    $materias = NotasEstudianteHelper::obtenerNotas($pdo, (int) $hijo['id'], $periodo ? (int) $periodo['id'] : null);
    $hijo['total_materias'] = count($materias);
    $hijo['promedio'] = NotasEstudianteHelper::promedio($materias);
    $hijo['materias_bajas'] = count(array_filter($materias, fn($m) => $m['tiene_notas'] && $m['definitiva'] < 3.0));
}
unset($hijo);

$titulo = 'Inicio';
$pagina_activa = 'dashboard';
include __DIR__ . '/includes/layout_start.php';
?>
<section class="welcome">
    <h1>Hola, <?php echo htmlspecialchars($padre['nombre']); ?></h1>
    <p>
        <?php if ($periodo): ?>
            Periodo actual: <strong><?php echo htmlspecialchars($periodo['nombre']); ?></strong>
            <?php if (!empty($periodo['ano_lectivo'])): ?>· <?php echo htmlspecialchars($periodo['ano_lectivo']); ?><?php endif; ?>
            <?php if (!empty($periodo['fecha_fin'])): ?>
                · termina el <?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?>
            <?php endif; ?>
        <?php else: ?>
            Aún no hay un periodo académico configurado.
        <?php endif; ?>
    </p>
</section>

<?php if (empty($hijos)): ?>
    <div class="content-card">
        <div class="empty-state">
            <i class="fas fa-children"></i>
            Todavía no tiene estudiantes vinculados a su cuenta.<br>
            Comuníquese con la institución para que los asocien.
        </div>
    </div>
<?php else: ?>
    <div class="children-grid">
        <?php foreach ($hijos as $hijo):
            $iniciales = mb_strtoupper(mb_substr($hijo['nombre'], 0, 1) . mb_substr($hijo['apellido'], 0, 1));
            $desempeno = $hijo['promedio'] !== null ? NotasEstudianteHelper::desempeno($hijo['promedio']) : null;
        ?>
            <article class="child-card">
                <div class="child-card-header">
                    <div class="child-avatar"><?php echo htmlspecialchars($iniciales); ?></div>
                    <div>
                        <h2><?php echo htmlspecialchars($hijo['nombre'] . ' ' . $hijo['apellido']); ?></h2>
                        <div class="child-meta">
                            <?php echo htmlspecialchars($hijo['grado_nombre'] ?? 'Sin grado asignado'); ?>
                            <?php if (!empty($hijo['sede_nombre'])): ?>· <?php echo htmlspecialchars($hijo['sede_nombre']); ?><?php endif; ?>
                        </div>
                        <div class="child-meta"><?php echo htmlspecialchars(ucfirst($hijo['parentesco'])); ?></div>
                    </div>
                </div>
                <div class="child-stats">
                    <div>
                        <div class="child-stat-value"><?php echo $hijo['promedio'] !== null ? number_format($hijo['promedio'], 1) : '—'; ?></div>
                        <div class="child-stat-label">Promedio</div>
                    </div>
                    <div>
                        <div class="child-stat-value"><?php echo $hijo['total_materias']; ?></div>
                        <div class="child-stat-label">Materias</div>
                    </div>
                    <div>
                        <div class="child-stat-value" style="<?php echo $hijo['materias_bajas'] > 0 ? 'color:#b91c1c' : ''; ?>">
                            <?php echo $hijo['materias_bajas']; ?>
                        </div>
                        <div class="child-stat-label">En bajo</div>
                    </div>
                </div>
                <div class="child-card-footer">
                    <?php if ($desempeno): ?>
                        <p style="margin-bottom: 0.75rem; text-align:center;">
                            Desempeño general: <span class="desempeno <?php echo $desempeno['clase']; ?>"><?php echo $desempeno['texto']; ?></span>
                        </p>
                    <?php endif; ?>
                    <a href="calificaciones.php?estudiante_id=<?php echo $hijo['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Ver notas
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/layout_end.php'; ?>
