<?php
session_start();
if (!isset($_SESSION['profesor_id'])) {
    header('Location: /school_management/auth/profesor_login.php');
    exit();
}
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../helpers/MensajesHelper.php';

$yo_tipo = 'profesor';
$yo_id = (int) $_SESSION['profesor_id'];
$url_mensajes = 'index.php';
[$error_mensaje, $datos_formulario] = MensajesHelper::procesarFormulario($pdo, $yo_tipo, $yo_id, $url_mensajes);

$page_title = 'Mensajes';
include __DIR__ . '/../components/layout_start.php';
include __DIR__ . '/../../../includes/mensajes_bandeja.php';
include __DIR__ . '/../components/layout_end.php';
