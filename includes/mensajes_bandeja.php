<?php
/**
 * Vista de la bandeja de mensajes (la usan profesor/views/mensajes/index.php y padre/mensajes.php).
 * Variables esperadas: $pdo, $yo_tipo, $yo_id, $url_mensajes, $error_mensaje, $datos_formulario.
 */
if (!isset($pdo, $yo_tipo, $yo_id, $url_mensajes)) {
    http_response_code(404);
    exit();
}

$vista = $_GET['vista'] ?? 'recibidos';
$no_leidos = MensajesHelper::noLeidos($pdo, $yo_tipo, $yo_id);
$avisos = ['enviado' => 'Mensaje enviado.', 'eliminado' => 'Mensaje eliminado de su bandeja.'];

$mensaje_abierto = null;
if ($vista === 'ver') {
    $mensaje_abierto = MensajesHelper::abrir($pdo, $yo_tipo, $yo_id, (int) ($_GET['id'] ?? 0));
    if (!$mensaje_abierto) {
        $vista = 'recibidos';
        $error_mensaje = 'El mensaje no existe o fue eliminado.';
    }
    $no_leidos = MensajesHelper::noLeidos($pdo, $yo_tipo, $yo_id);
}

// Datos para redactar (nuevo / responder / reintento tras error)
$contactos = [];
$form = ['destino' => '', 'asunto' => '', 'contenido' => '', 'respuesta_a' => ''];
if ($error_mensaje && $datos_formulario) {
    $vista = 'nuevo';
    $form = array_merge($form, array_intersect_key($datos_formulario, $form));
}
if ($vista === 'nuevo') {
    $contactos = MensajesHelper::contactos($pdo, $yo_tipo, $yo_id);
    if (!empty($_GET['para'])) {
        $form['destino'] = $_GET['para'];
    }
    if (!empty($_GET['responder'])) {
        $original = MensajesHelper::abrir($pdo, $yo_tipo, $yo_id, (int) $_GET['responder']);
        if ($original) {
            $clave = $original['remitente_tipo'] . ':' . $original['remitente_id'];
            $form['destino'] = $clave;
            $form['respuesta_a'] = $original['id'];
            $form['asunto'] = preg_match('/^re:/i', $original['asunto']) ? $original['asunto'] : 'Re: ' . $original['asunto'];
            $form['contenido'] = "\n\n--- El " . date('d/m/Y H:i', strtotime($original['fecha_envio'])) . ', ' . $original['remitente_nombre'] . " escribió:\n> "
                . str_replace("\n", "\n> ", $original['contenido']);
            if (!isset($contactos[$clave])) {
                $contactos[$clave] = ['tipo' => $original['remitente_tipo'], 'id' => (int) $original['remitente_id'],
                    'nombre' => $original['remitente_nombre'], 'detalle' => '', 'grupo' => 'Responder a'];
            }
        }
    }
}

$lista = in_array($vista, ['recibidos', 'enviados'], true) ? MensajesHelper::bandeja($pdo, $yo_tipo, $yo_id, $vista) : [];
$etiqueta_tipo = ['profesor' => 'Docente', 'padre' => 'Padre de familia'];

if (!function_exists('fechaMensaje')) {
    function fechaMensaje(string $fecha): string
    {
        $t = strtotime($fecha);
        return date('Y-m-d') === date('Y-m-d', $t) ? date('h:i a', $t) : date('d/m/Y', $t);
    }
}
?>
<style>
    .tabs { display:flex; gap:0.25rem; border-bottom:1px solid var(--border-color); margin-bottom:1.25rem; flex-wrap:wrap; }
    .tabs a { padding:0.65rem 1rem; color:var(--muted-text); font-weight:500; border-bottom:2px solid transparent; margin-bottom:-1px; }
    .tabs a.active { color:var(--primary-light); border-bottom-color:var(--primary-light); }
    .tabs .count { background:var(--danger-color); color:#fff; border-radius:999px; font-size:0.7rem; padding:0.05rem 0.45rem; margin-left:0.3rem; }
    .msg-list { list-style: none; }
    .msg-item a { display: grid; grid-template-columns: 1fr auto; gap: 0.25rem 1rem; padding: 0.85rem 1rem;
                  border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .msg-item a:hover { background: var(--bg-subtle); }
    .msg-item.no-leido a { background: #f0f7ff; }
    .msg-item.no-leido .msg-quien, .msg-item.no-leido .msg-asunto { font-weight: 700; color: var(--text-primary); }
    .msg-quien { font-weight: 500; color: var(--text-primary); }
    .msg-fecha { font-size: 0.8rem; color: var(--muted-text); white-space: nowrap; }
    .msg-asunto { grid-column: 1 / -1; font-size: 0.9rem; }
    .msg-extracto { color: var(--muted-text); font-weight: 400; }
    .msg-detalle-meta { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; color: var(--muted-text); font-size: 0.875rem; margin-bottom: 1rem; }
    .msg-cuerpo { white-space: pre-wrap; line-height: 1.6; color: var(--text-primary); background: var(--bg-subtle);
                  border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem 1.25rem; }
</style>

<div class="content-card">
    <div class="card-header with-actions" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
        <h2><i class="fas fa-envelope"></i> Mensajes</h2>
        <a href="<?php echo $url_mensajes; ?>?vista=nuevo" class="btn btn-primary"><i class="fas fa-pen"></i> Nuevo mensaje</a>
    </div>

    <nav class="tabs" style="margin-top: 1rem;">
        <a href="<?php echo $url_mensajes; ?>?vista=recibidos" class="<?php echo in_array($vista, ['recibidos', 'ver'], true) && !($mensaje_abierto['es_remitente'] ?? false) ? 'active' : ''; ?>">
            Recibidos<?php if ($no_leidos): ?><span class="count"><?php echo $no_leidos; ?></span><?php endif; ?>
        </a>
        <a href="<?php echo $url_mensajes; ?>?vista=enviados" class="<?php echo $vista === 'enviados' || ($mensaje_abierto['es_remitente'] ?? false) ? 'active' : ''; ?>">Enviados</a>
        <a href="<?php echo $url_mensajes; ?>?vista=nuevo" class="<?php echo $vista === 'nuevo' ? 'active' : ''; ?>">Redactar</a>
    </nav>

    <?php if (isset($_GET['ok'], $avisos[$_GET['ok']])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $avisos[$_GET['ok']]; ?></div>
    <?php endif; ?>
    <?php if ($error_mensaje): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_mensaje); ?></div>
    <?php endif; ?>

    <?php if ($vista === 'ver' && $mensaje_abierto): $m = $mensaje_abierto; ?>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($m['asunto']); ?></h3>
        <div class="msg-detalle-meta">
            <span>
                <strong>De:</strong> <?php echo htmlspecialchars($m['remitente_nombre']); ?> (<?php echo $etiqueta_tipo[$m['remitente_tipo']] ?? ''; ?>)
                &nbsp;·&nbsp; <strong>Para:</strong> <?php echo htmlspecialchars($m['destinatario_nombre']); ?> (<?php echo $etiqueta_tipo[$m['destinatario_tipo']] ?? ''; ?>)
            </span>
            <span><?php echo date('d/m/Y h:i a', strtotime($m['fecha_envio'])); ?>
                <?php if ($m['es_remitente']): ?> · <?php echo $m['leido'] ? 'Leído' : 'Sin leer'; ?><?php endif; ?>
            </span>
        </div>
        <div class="msg-cuerpo"><?php echo htmlspecialchars($m['contenido']); ?></div>
        <div class="form-actions">
            <a href="<?php echo $url_mensajes; ?>?vista=<?php echo $m['es_remitente'] ? 'enviados' : 'recibidos'; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            <form method="POST" onsubmit="return confirm('¿Eliminar este mensaje de su bandeja?');" style="display:inline;">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                <input type="hidden" name="carpeta" value="<?php echo $m['es_remitente'] ? 'enviados' : 'recibidos'; ?>">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</button>
            </form>
            <?php if (!$m['es_remitente']): ?>
                <a href="<?php echo $url_mensajes; ?>?vista=nuevo&responder=<?php echo $m['id']; ?>" class="btn btn-primary"><i class="fas fa-reply"></i> Responder</a>
            <?php endif; ?>
        </div>

    <?php elseif ($vista === 'nuevo'): ?>
        <?php if (!$contactos): ?>
            <div class="empty-state">
                <i class="fas fa-address-book"></i>
                <?php echo $yo_tipo === 'padre'
                    ? 'Todavía no hay docentes asignados a los grados de sus hijos.'
                    : 'No tiene contactos disponibles: los acudientes aparecen cuando sus estudiantes tienen padres registrados.'; ?>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="accion" value="enviar">
                <input type="hidden" name="respuesta_a" value="<?php echo htmlspecialchars((string) $form['respuesta_a']); ?>">
                <div class="form-group">
                    <label for="destino" class="required-label">Para</label>
                    <select id="destino" name="destino" required>
                        <option value="">Seleccione…</option>
                        <?php
                        $grupos = [];
                        foreach ($contactos as $clave => $c) {
                            $grupos[$c['grupo']][$clave] = $c;
                        }
                        foreach ($grupos as $grupo => $lista_grupo): ?>
                            <optgroup label="<?php echo htmlspecialchars($grupo); ?>">
                                <?php foreach ($lista_grupo as $clave => $c): ?>
                                    <option value="<?php echo htmlspecialchars($clave); ?>" <?php echo $form['destino'] === $clave ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['nombre'] . ($c['detalle'] ? ' — ' . $c['detalle'] : '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="asunto" class="required-label">Asunto</label>
                    <input type="text" id="asunto" name="asunto" maxlength="200" value="<?php echo htmlspecialchars($form['asunto']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="contenido" class="required-label">Mensaje</label>
                    <textarea id="contenido" name="contenido" rows="9" required><?php echo htmlspecialchars($form['contenido']); ?></textarea>
                </div>
                <div class="form-actions">
                    <a href="<?php echo $url_mensajes; ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
                </div>
            </form>
        <?php endif; ?>

    <?php else: ?>
        <?php if (!$lista): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <?php echo $vista === 'enviados' ? 'No ha enviado mensajes.' : 'No tiene mensajes recibidos.'; ?>
            </div>
        <?php else: ?>
            <ul class="msg-list">
                <?php foreach ($lista as $m):
                    $no_leido = $vista === 'recibidos' && !$m['leido']; ?>
                    <li class="msg-item <?php echo $no_leido ? 'no-leido' : ''; ?>">
                        <a href="<?php echo $url_mensajes; ?>?vista=ver&id=<?php echo $m['id']; ?>">
                            <span class="msg-quien">
                                <?php echo $vista === 'enviados' ? 'Para: ' : ''; ?><?php echo htmlspecialchars($m['otro_nombre']); ?>
                                <span class="text-muted" style="font-weight:400; font-size:0.8rem;">· <?php echo $etiqueta_tipo[$m['otro_tipo']] ?? ''; ?></span>
                            </span>
                            <span class="msg-fecha"><?php echo fechaMensaje($m['fecha_envio']); ?></span>
                            <span class="msg-asunto">
                                <?php echo htmlspecialchars($m['asunto']); ?>
                                <span class="msg-extracto">— <?php echo htmlspecialchars(mb_strimwidth(preg_replace('/\s+/', ' ', $m['contenido']), 0, 90, '…')); ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>
