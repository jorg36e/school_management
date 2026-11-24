<?php
session_start();
if (!isset($_SESSION['profesor_id'])) {
    header('Location: /school_management/auth/profesor_login.php');
    exit();
}
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../helpers/RecursosHelper.php';

$profesor_id = (int) $_SESSION['profesor_id'];
$asignaciones = RecursosHelper::asignacionesProfesor($pdo, $profesor_id);
$ids_asignaciones = array_map('intval', array_column($asignaciones, 'id'));
$error = null;
$form = ['titulo' => '', 'descripcion' => '', 'asignacion_id' => '', 'tipo' => 'archivo', 'url' => '', 'visible_estudiantes' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        $stmt = $pdo->prepare("SELECT * FROM recursos WHERE id = ? AND profesor_id = ?");
        $stmt->execute([(int) ($_POST['id'] ?? 0), $profesor_id]);
        if ($recurso = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdo->prepare("DELETE FROM recursos WHERE id = ?")->execute([$recurso['id']]);
            RecursosHelper::borrarArchivo($recurso['archivo_ruta']);
        }
        header('Location: index.php?ok=eliminado');
        exit();
    }

    if ($accion === 'visibilidad') {
        $pdo->prepare("UPDATE recursos SET visible_estudiantes = 1 - visible_estudiantes WHERE id = ? AND profesor_id = ?")
            ->execute([(int) ($_POST['id'] ?? 0), $profesor_id]);
        header('Location: index.php?ok=actualizado');
        exit();
    }

    if ($accion === 'crear') {
        $form = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'asignacion_id' => (int) ($_POST['asignacion_id'] ?? 0),
            'tipo' => ($_POST['tipo'] ?? '') === 'enlace' ? 'enlace' : 'archivo',
            'url' => trim($_POST['url'] ?? ''),
            'visible_estudiantes' => !empty($_POST['visible_estudiantes']) ? 1 : 0,
        ];

        try {
            // Si el formulario supera post_max_size, PHP descarta todo el contenido
            if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                throw new Exception('El archivo supera el tamaño máximo permitido (' . RecursosHelper::formatoTamano(RecursosHelper::tamanoMaximo()) . ').');
            }
            if ($form['titulo'] === '') {
                throw new Exception('Escriba un título para el recurso.');
            }
            if (!in_array($form['asignacion_id'], $ids_asignaciones, true)) {
                throw new Exception('Seleccione la materia y el grado del recurso.');
            }

            $archivo = [null, null, null, null];
            if ($form['tipo'] === 'enlace') {
                if (!filter_var($form['url'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $form['url'])) {
                    throw new Exception('Escriba un enlace válido que empiece por http:// o https://');
                }
            } else {
                $archivo = RecursosHelper::guardarArchivo($_FILES['archivo'] ?? [], $profesor_id);
            }

            $stmt = $pdo->prepare("
                INSERT INTO recursos (profesor_id, asignacion_id, titulo, descripcion, tipo,
                                      archivo_ruta, archivo_nombre, archivo_mime, archivo_tamano, url, visible_estudiantes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $profesor_id, $form['asignacion_id'], $form['titulo'], $form['descripcion'] ?: null, $form['tipo'],
                $archivo[0], $archivo[1], $archivo[2], $archivo[3],
                $form['tipo'] === 'enlace' ? $form['url'] : null, $form['visible_estudiantes'],
            ]);
            header('Location: index.php?ok=creado');
            exit();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Listado (con filtro opcional por asignación)
$filtro = (int) ($_GET['asignacion'] ?? 0);
$sql = "
    SELECT r.*, m.nombre AS materia, g.nombre AS grado
    FROM recursos r
    LEFT JOIN asignaciones_profesor ap ON ap.id = r.asignacion_id
    LEFT JOIN materias m ON m.id = ap.materia_id
    LEFT JOIN grados g ON g.id = ap.grado_id
    WHERE r.profesor_id = ?" . ($filtro ? ' AND r.asignacion_id = ?' : '') . "
    ORDER BY r.fecha_creacion DESC, r.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($filtro ? [$profesor_id, $filtro] : [$profesor_id]);
$recursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$avisos = ['creado' => 'Recurso publicado.', 'eliminado' => 'Recurso eliminado.', 'actualizado' => 'Visibilidad actualizada.'];
$max = RecursosHelper::tamanoMaximo();

$page_title = 'Material Didáctico';
$page_css = '
    .recurso { display:grid; grid-template-columns:auto 1fr auto; gap:1rem; align-items:center; padding:1rem 0; border-bottom:1px solid var(--border-color); }
    .recurso:last-child { border-bottom:none; }
    .recurso-icono { width:46px; height:46px; border-radius:12px; background:var(--info-soft); color:var(--primary-light);
                     display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
    .recurso-titulo { font-weight:600; color:var(--text-primary); }
    .recurso-meta { font-size:0.82rem; color:var(--muted-text); }
    .recurso-desc { font-size:0.9rem; color:var(--text-secondary); margin-top:0.25rem; }
    .recurso-acciones { display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
    .tipo-opciones { display:flex; gap:1rem; flex-wrap:wrap; }
    @media (max-width: 700px) { .recurso { grid-template-columns:auto 1fr; } .recurso-acciones { grid-column:1 / -1; justify-content:flex-start; } }
';
include __DIR__ . '/../components/layout_start.php';
?>
<?php if (isset($_GET['ok'], $avisos[$_GET['ok']])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $avisos[$_GET['ok']]; ?></div>
<?php endif; ?>

<div class="form-sections" style="grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); align-items: start;">
    <div class="content-card">
        <div class="card-header">
            <h2>Publicar material</h2>
            <p>Guías, talleres, presentaciones o enlaces para sus estudiantes.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$asignaciones): ?>
            <div class="empty-state"><i class="fas fa-book"></i>Necesita tener al menos una materia asignada para publicar material.</div>
        <?php else: ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max; ?>">

                <div class="form-group">
                    <label for="titulo" class="required-label">Título</label>
                    <input type="text" id="titulo" name="titulo" maxlength="200" value="<?php echo htmlspecialchars($form['titulo']); ?>" required placeholder="Ej.: Guía 3 - Fracciones">
                </div>
                <div class="form-group">
                    <label for="asignacion_id" class="required-label">Materia y grado</label>
                    <select id="asignacion_id" name="asignacion_id" required>
                        <option value="">Seleccione…</option>
                        <?php foreach ($asignaciones as $a): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo (int) $form['asignacion_id'] === (int) $a['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['materia'] . ' · ' . ($a['grado'] ?? 'Sin grado')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" placeholder="Instrucciones o comentarios para los estudiantes"><?php echo htmlspecialchars($form['descripcion']); ?></textarea>
                </div>
                <div class="form-group">
                    <span class="form-label">Tipo</span>
                    <div class="tipo-opciones">
                        <label class="form-check"><input type="radio" name="tipo" value="archivo" <?php echo $form['tipo'] === 'archivo' ? 'checked' : ''; ?>> Archivo</label>
                        <label class="form-check"><input type="radio" name="tipo" value="enlace" <?php echo $form['tipo'] === 'enlace' ? 'checked' : ''; ?>> Enlace (YouTube, Drive, página web)</label>
                    </div>
                </div>
                <div class="form-group" id="campo-archivo">
                    <label for="archivo" class="required-label">Archivo</label>
                    <input type="file" id="archivo" name="archivo" accept=".<?php echo implode(',.', array_keys(RecursosHelper::EXTENSIONES)); ?>">
                    <small class="form-text">Máximo <?php echo RecursosHelper::formatoTamano($max); ?>. PDF, Word, Excel, PowerPoint, imágenes, audio, video o ZIP.</small>
                </div>
                <div class="form-group" id="campo-url">
                    <label for="url" class="required-label">Enlace</label>
                    <input type="url" id="url" name="url" value="<?php echo htmlspecialchars($form['url']); ?>" placeholder="https://…">
                </div>
                <label class="form-check">
                    <input type="checkbox" name="visible_estudiantes" value="1" <?php echo $form['visible_estudiantes'] ? 'checked' : ''; ?>>
                    Visible para los estudiantes del grado y sus acudientes
                </label>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Publicar</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="content-card">
        <div class="card-header with-actions">
            <h2>Mis recursos (<?php echo count($recursos); ?>)</h2>
            <?php if ($asignaciones): ?>
                <form method="GET">
                    <select name="asignacion" class="form-control" onchange="this.form.submit()" style="min-width: 200px;">
                        <option value="">Todas las materias</option>
                        <?php foreach ($asignaciones as $a): ?>
                            <option value="<?php echo $a['id']; ?>" <?php echo $filtro === (int) $a['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['materia'] . ' · ' . ($a['grado'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </div>

        <?php if (!$recursos): ?>
            <div class="empty-state"><i class="fas fa-folder-open"></i>Aún no ha publicado material<?php echo $filtro ? ' para esta materia' : ''; ?>.</div>
        <?php endif; ?>

        <?php foreach ($recursos as $r): ?>
            <div class="recurso">
                <div class="recurso-icono"><i class="fas <?php echo RecursosHelper::icono($r); ?>"></i></div>
                <div>
                    <div class="recurso-titulo"><?php echo htmlspecialchars($r['titulo']); ?></div>
                    <div class="recurso-meta">
                        <?php echo htmlspecialchars(($r['materia'] ?? 'Sin materia') . ' · ' . ($r['grado'] ?? '')); ?>
                        · <?php echo date('d/m/Y', strtotime($r['fecha_creacion'])); ?>
                        <?php if ($r['tipo'] === 'archivo'): ?>· <?php echo RecursosHelper::formatoTamano((int) $r['archivo_tamano']); ?><?php endif; ?>
                        · <?php echo (int) $r['descargas']; ?> <?php echo $r['tipo'] === 'enlace' ? 'visitas' : 'descargas'; ?>
                    </div>
                    <?php if ($r['descripcion']): ?><div class="recurso-desc"><?php echo nl2br(htmlspecialchars($r['descripcion'])); ?></div><?php endif; ?>
                </div>
                <div class="recurso-acciones">
                    <span class="badge <?php echo $r['visible_estudiantes'] ? 'badge-success' : ''; ?>">
                        <?php echo $r['visible_estudiantes'] ? 'Visible' : 'Oculto'; ?>
                    </span>
                    <a class="btn btn-sm btn-outline" href="/school_management/recursos/descargar.php?id=<?php echo $r['id']; ?>" target="_blank" rel="noopener">
                        <i class="fas <?php echo $r['tipo'] === 'enlace' ? 'fa-up-right-from-square' : 'fa-download'; ?>"></i> Abrir
                    </a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="accion" value="visibilidad">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-secondary" title="Mostrar u ocultar a los estudiantes">
                            <i class="fas <?php echo $r['visible_estudiantes'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i> <?php echo $r['visible_estudiantes'] ? 'Ocultar' : 'Mostrar'; ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar «<?php echo htmlspecialchars(addslashes($r['titulo'])); ?>»?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <button type="submit" class="btn-action btn-delete" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    (function () {
        const radios = document.querySelectorAll('input[name="tipo"]');
        const campoArchivo = document.getElementById('campo-archivo');
        const campoUrl = document.getElementById('campo-url');
        if (!campoArchivo) return;
        function actualizar() {
            const tipo = document.querySelector('input[name="tipo"]:checked').value;
            campoArchivo.style.display = tipo === 'archivo' ? '' : 'none';
            campoUrl.style.display = tipo === 'enlace' ? '' : 'none';
            document.getElementById('archivo').required = tipo === 'archivo';
            document.getElementById('url').required = tipo === 'enlace';
        }
        radios.forEach(r => r.addEventListener('change', actualizar));
        actualizar();
    })();
</script>
<?php include __DIR__ . '/../components/layout_end.php'; ?>
