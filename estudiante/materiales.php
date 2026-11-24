<?php
session_start();

$page_title = 'Material de clase';
$page_description = 'Guías, talleres y enlaces que publican tus profesores';

if (!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/student_login.php');
    exit();
}

require_once '../config/database.php';
require_once '../helpers/RecursosHelper.php';

$stmt = $pdo->prepare("SELECT grado_id FROM estudiantes WHERE id = ?");
$stmt->execute([$_SESSION['estudiante_id']]);
$grado_id = (int) $stmt->fetchColumn();

$recursos = $grado_id ? RecursosHelper::delGrado($pdo, $grado_id) : [];
$por_materia = [];
foreach ($recursos as $r) {
    $por_materia[$r['materia']][] = $r;
}

ob_start();
?>
<?php if (!$recursos): ?>
    <div class="content-card material-vacio">
        <i class="fas fa-folder-open"></i>
        <p>Tus profesores aún no han publicado material.</p>
    </div>
<?php endif; ?>

<?php foreach ($por_materia as $materia => $lista): ?>
    <div class="content-card material-materia">
        <h2><?php echo htmlspecialchars($materia); ?> <span>(<?php echo count($lista); ?>)</span></h2>
        <?php foreach ($lista as $r): ?>
            <a class="material-item" href="../recursos/descargar.php?id=<?php echo $r['id']; ?>" target="_blank" rel="noopener">
                <span class="material-icono"><i class="fas <?php echo RecursosHelper::icono($r); ?>"></i></span>
                <span class="material-texto">
                    <strong><?php echo htmlspecialchars($r['titulo']); ?></strong>
                    <small>
                        <?php echo htmlspecialchars($r['profesor'] ?? ''); ?> · <?php echo date('d/m/Y', strtotime($r['fecha_creacion'])); ?>
                        <?php if ($r['tipo'] === 'archivo'): ?>· <?php echo RecursosHelper::formatoTamano((int) $r['archivo_tamano']); ?><?php endif; ?>
                    </small>
                    <?php if ($r['descripcion']): ?><em><?php echo htmlspecialchars($r['descripcion']); ?></em><?php endif; ?>
                </span>
                <span class="material-accion">
                    <i class="fas <?php echo $r['tipo'] === 'enlace' ? 'fa-up-right-from-square' : 'fa-download'; ?>"></i>
                    <?php echo $r['tipo'] === 'enlace' ? 'Abrir' : 'Descargar'; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
<?php
$content = ob_get_clean();

$page_styles = "
    .material-vacio { text-align: center; padding: 3rem 1rem; color: #64748b; }
    .material-vacio i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: 0.75rem; }
    .material-materia { margin-bottom: 1.25rem; }
    .material-materia h2 { font-size: 1.1rem; margin-bottom: 0.75rem; }
    .material-materia h2 span { color: #64748b; font-weight: 400; }
    .material-item { display: grid; grid-template-columns: auto 1fr auto; gap: 1rem; align-items: center; padding: 0.85rem 0;
                     border-top: 1px solid #e5e7eb; color: inherit; text-decoration: none; }
    .material-item:hover strong { color: #2563eb; }
    .material-icono { width: 42px; height: 42px; border-radius: 10px; background: #eff6ff; color: #2563eb;
                      display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .material-texto { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
    .material-texto small { color: #64748b; }
    .material-texto em { color: #334155; font-style: normal; font-size: 0.9rem; }
    .material-accion { color: #2563eb; font-size: 0.875rem; white-space: nowrap; }
    @media (max-width: 600px) { .material-accion { display: none; } }
";

include __DIR__ . '/layouts/main.php';
