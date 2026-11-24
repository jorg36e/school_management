<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

$mensajes_exito = [
    'created' => 'Acudiente registrado correctamente.',
    'updated' => 'Datos del acudiente actualizados.',
    'deleted' => 'Acudiente eliminado.',
];
$mensajes_error = [
    'not_found' => 'El acudiente solicitado no existe.',
    'delete_failed' => 'No se pudo eliminar el acudiente. Intente de nuevo.',
];

try {
    $padres = $pdo->query("
        SELECT p.*,
               GROUP_CONCAT(CONCAT(e.nombre, ' ', e.apellido, ' (', pe.parentesco, ')')
                            ORDER BY e.apellido SEPARATOR '||') AS hijos
        FROM padres_familia p
        LEFT JOIN padres_estudiantes pe ON pe.padre_id = p.id
        LEFT JOIN estudiantes e ON e.id = pe.estudiante_id
        GROUP BY p.id
        ORDER BY p.apellido, p.nombre
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error en la base de datos: ' . $e->getMessage();
    $padres = [];
}

$total = count($padres);
$activos = count(array_filter($padres, fn($p) => strtolower($p['estado']) === 'activo'));
$con_acceso = count(array_filter($padres, fn($p) => !empty($p['usuario'])));

$topbar_title = 'Padres de familia';
include '../components/layout_start.php';
?>
<div class="page-header">
    <div>
        <h1>Padres de familia y acudientes</h1>
        <p class="page-subtitle">Personas responsables de los estudiantes y su acceso al portal de padres.</p>
    </div>
    <a href="create_parent.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nuevo acudiente
    </a>
</div>

<?php if (isset($_GET['success'], $mensajes_exito[$_GET['success']])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $mensajes_exito[$_GET['success']]; ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'], $mensajes_error[$_GET['error']])): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $mensajes_error[$_GET['error']]; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div><div class="stat-value"><?php echo $total; ?></div><div class="stat-label">Acudientes registrados</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
        <div><div class="stat-value"><?php echo $activos; ?></div><div class="stat-label">Activos</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-key"></i></div>
        <div><div class="stat-value"><?php echo $con_acceso; ?></div><div class="stat-label">Con acceso al portal</div></div>
    </div>
</div>

<div class="content-card">
    <div class="toolbar">
        <div class="form-group grow">
            <label for="buscar">Buscar</label>
            <input type="search" id="buscar" class="form-control" placeholder="Nombre, documento, teléfono o estudiante…">
        </div>
        <div class="form-group">
            <label for="filtro-estado">Estado</label>
            <select id="filtro-estado" class="form-control">
                <option value="">Todos</option>
                <option value="activo">Activos</option>
                <option value="inactivo">Inactivos</option>
            </select>
        </div>
    </div>

    <div class="table-container">
        <table class="data-table" id="tabla-padres">
            <thead>
                <tr>
                    <th>Acudiente</th>
                    <th>Documento</th>
                    <th>Contacto</th>
                    <th>Estudiantes a cargo</th>
                    <th>Portal</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($padres)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                No hay padres registrados. Use «Nuevo acudiente» para registrar el primero.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($padres as $padre):
                        $hijos = $padre['hijos'] ? explode('||', $padre['hijos']) : [];
                        $estado = strtolower($padre['estado']);
                        $buscar = mb_strtolower(implode(' ', [$padre['nombre'], $padre['apellido'], $padre['documento_numero'], $padre['telefono'], $padre['email'], implode(' ', $hijos)]));
                    ?>
                        <tr data-buscar="<?php echo htmlspecialchars($buscar); ?>" data-estado="<?php echo $estado; ?>">
                            <td>
                                <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?></strong>
                                <?php if (!empty($padre['ocupacion'])): ?>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars($padre['ocupacion']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo !empty($padre['documento_numero'])
                                    ? htmlspecialchars(trim(($padre['documento_tipo'] ?? '') . ' ' . $padre['documento_numero']))
                                    : '<span class="text-muted">No registrado</span>'; ?>
                            </td>
                            <td>
                                <?php if (!empty($padre['telefono'])): ?><div><i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($padre['telefono']); ?></div><?php endif; ?>
                                <?php if (!empty($padre['email'])): ?><div><i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($padre['email']); ?></div><?php endif; ?>
                                <?php if (empty($padre['telefono']) && empty($padre['email'])): ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hijos): ?>
                                    <?php foreach ($hijos as $hijo): ?>
                                        <div><?php echo htmlspecialchars($hijo); ?></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin estudiantes</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($padre['usuario'])): ?>
                                    <span class="badge badge-info"><i class="fas fa-key"></i> <?php echo htmlspecialchars($padre['usuario']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sin acceso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $estado === 'activo' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo ucfirst($estado); ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="btn-action btn-edit" title="Editar" href="edit_parent.php?id=<?php echo $padre['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn-action btn-delete" title="Eliminar"
                                            onclick="confirmarEliminacion(<?php echo $padre['id']; ?>, '<?php echo htmlspecialchars(addslashes($padre['nombre'] . ' ' . $padre['apellido'])); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmarEliminacion(id, nombre) {
        if (confirm('¿Eliminar a ' + nombre + '? Se quitará su vínculo con los estudiantes y su acceso al portal.')) {
            window.location.href = 'delete_parent.php?id=' + id;
        }
    }

    (function () {
        const buscar = document.getElementById('buscar');
        const estado = document.getElementById('filtro-estado');
        const filas = document.querySelectorAll('#tabla-padres tbody tr[data-buscar]');
        function filtrar() {
            const q = buscar.value.trim().toLowerCase();
            const e = estado.value;
            filas.forEach(f => {
                const ok = f.dataset.buscar.includes(q) && (!e || f.dataset.estado === e);
                f.style.display = ok ? '' : 'none';
            });
        }
        buscar.addEventListener('input', filtrar);
        estado.addEventListener('change', filtrar);
    })();
</script>
<?php include '../components/layout_end.php'; ?>
