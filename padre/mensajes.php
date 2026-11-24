<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../helpers/MensajesHelper.php';

$yo_tipo = 'padre';
$yo_id = (int) $padre['id'];
$url_mensajes = 'mensajes.php';
[$error_mensaje, $datos_formulario] = MensajesHelper::procesarFormulario($pdo, $yo_tipo, $yo_id, $url_mensajes);

$titulo = 'Mensajes';
$pagina_activa = 'mensajes';
include __DIR__ . '/includes/layout_start.php';
include __DIR__ . '/../includes/mensajes_bandeja.php';
include __DIR__ . '/includes/layout_end.php';
