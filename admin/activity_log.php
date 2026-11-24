<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

// Nombres legibles de las tablas que registran actividad
$modulos = [
    'estudiantes' => 'Estudiantes',
    'profesores' => 'Profesores',
    'padres_familia' => 'Padres de familia',
    'asignaturas' => 'Asignaturas',
    'materias' => 'Materias',
    'sedes' => 'Sedes',
    'grados' => 'Grados',
    'grupos' => 'Grupos',
    'periodos_academicos' => 'Periodos',
    'matriculas' => 'Matrículas',
    'asignaciones_profesor' => 'Asignaciones',
    'niveles_configuracion' => 'Niveles',
    'configuracion' => 'Configuración',
];
$iconos_accion = [
    'crear' => ['fa-plus', 'badge-success'],
    'actualizar' => ['fa-pen', 'badge-info'],
    'editar' => ['fa-pen', 'badge-info'],
    'eliminar' => ['fa-trash', 'badge-danger'],
];

// Filtros
$f_tabla = $_GET['tabla'] ?? '';
$f_accion = $_GET['accion'] ?? '';
$f_desde = $_GET['desde'] ?? '';
$f_hasta = $_GET['hasta'] ?? '';
$f_buscar = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($f_tabla !== '') {
    $where[] = 'al.tabla = ?';
    $params[] = $f_tabla;
}
if ($f_accion !== '') {
    $where[] = 'al.accion = ?';
    $params[] = $f_accion;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_desde)) {
    $where[] = 'al.fecha >= ?';
    $params[] = $f_desde . ' 00:00:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_hasta)) {
    $where[] = 'al.fecha <= ?';
    $params[] = $f_hasta . ' 23:59:59';
}
if ($f_buscar !== '') {
    $where[] = 'al.descripcion LIKE ?';
    $params[] = '%' . $f_buscar . '%';
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Paginación
$por_pagina = 25;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM actividad_log al $sql_where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$total_paginas = max(1, (int) ceil($total / $por_pagina));
$pagina = min($pagina, $total_paginas);
$offset = ($pagina - 1) * $por_pagina;

$stmt = $pdo->prepare("
    SELECT al.*, a.nombre AS usuario_nombre
    FROM actividad_log al
    LEFT JOIN administradores a ON al.usuario_id = a.id
    $sql_where
    ORDER BY al.fecha DESC, al.id DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Valores existentes para los filtros
$tablas_existentes = $pdo->query("SELECT DISTINCT tabla FROM actividad_log WHERE tabla IS NOT NULL ORDER BY tabla")->fetchAll(PDO::FETCH_COLUMN);
$acciones_existentes = $pdo->query("SELECT DISTINCT accion FROM actividad_log WHERE accion IS NOT NULL ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);

// Resumen de los últimos 7 días
$resumen = $pdo->query("
    SELECT accion, COUNT(*) AS total
    FROM actividad_log
    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY accion
")->fetchAll(PDO::FETCH_KEY_PAIR);

function urlPagina(int $p): string
{
    return '?' . http_build_query(array_merge($_GET, ['pagina' => $p]));
}

$topbar_title = 'Historial de actividad';
include __DIR__ . '/components/layout_start.php';
?>
<div class="page-header">
    <div>
        <h1>Historial de actividad</h1>
        <p class="page-subtitle">Registro de los cambios hechos en el sistema: quién, qué y cuándo.</p>
    </div>
    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al dashboard</a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-list"></i></div>
        <div><div class="stat-value"><?php echo array_sum($resumen); ?></div><div class="stat-label">Cambios en los últimos 7 días</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-plus"></i></div>
        <div><div class="stat-value"><?php echo $resumen['crear'] ?? 0; ?></div><div class="stat-label">Registros creados</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-pen"></i></div>
        <div><div class="stat-value"><?php echo ($resumen['actualizar'] ?? 0) + ($resumen['editar'] ?? 0); ?></div><div class="stat-label">Registros actualizados</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger"><i class="fas fa-trash"></i></div>
        <div><div class="stat-value"><?php echo $resumen['eliminar'] ?? 0; ?></div><div class="stat-label">Registros eliminados</div></div>
    </div>
</div>

<div class="content-card">
    <form method="GET" class="toolbar">
        <div class="form-group grow">
            <label for="q">Buscar en la descripción</label>
            <input type="search" id="q" name="q" class="form-control" value="<?php echo htmlspecialchars($f_buscar); ?>" placeholder="Ej.: nombre de un estudiante">
        </div>
        <div class="form-group">
            <label for="tabla">Módulo</label>
            <select id="tabla" name="tabla" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($tablas_existentes as $t): ?>
                    <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $f_tabla === $t ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($modulos[$t] ?? ucfirst(str_replace('_', ' ', $t))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="accion">Acción</label>
            <select id="accion" name="accion" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($acciones_existentes as $a): ?>
                    <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $f_accion === $a ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($a)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?php echo htmlspecialchars($f_desde); ?>">
        </div>
        <div class="form-group">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?php echo htmlspecialchars($f_hasta); ?>">
        </div>
        <div class="page-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
            <?php if ($where): ?>
                <a href="activity_log.php" class="btn btn-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                    <th>Realizado por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registros)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fas fa-clock-rotate-left"></i>
                                <?php echo $where ? 'No hay actividad que coincida con los filtros.' : 'Todavía no hay actividad registrada.'; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registros as $r):
                        [$icono, $clase] = $iconos_accion[$r['accion']] ?? ['fa-circle-info', ''];
                    ?>
                        <tr>
                            <td style="white-space: nowrap;">
                                <?php echo date('d/m/Y', strtotime($r['fecha'])); ?>
                                <div class="text-muted" style="font-size: 0.8rem;"><?php echo date('h:i a', strtotime($r['fecha'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($modulos[$r['tabla']] ?? ucfirst(str_replace('_', ' ', (string) $r['tabla']))); ?></td>
                            <td><span class="badge <?php echo $clase; ?>"><i class="fas <?php echo $icono; ?>"></i> <?php echo htmlspecialchars(ucfirst((string) $r['accion'])); ?></span></td>
                            <td><?php echo htmlspecialchars((string) $r['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($r['usuario_nombre'] ?? 'Sistema'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1): ?>
        <nav class="pagination" aria-label="Paginación">
            <?php if ($pagina > 1): ?><a href="<?php echo urlPagina($pagina - 1); ?>">&laquo;</a><?php endif; ?>
            <?php for ($p = max(1, $pagina - 3); $p <= min($total_paginas, $pagina + 3); $p++): ?>
                <?php if ($p === $pagina): ?>
                    <span class="active"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="<?php echo urlPagina($p); ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($pagina < $total_paginas): ?><a href="<?php echo urlPagina($pagina + 1); ?>">&raquo;</a><?php endif; ?>
        </nav>
    <?php endif; ?>
    <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.75rem;">
        <?php echo $total; ?> registro<?php echo $total === 1 ? '' : 's'; ?> en total.
    </p>
</div>
<?php include __DIR__ . '/components/layout_end.php'; ?>
