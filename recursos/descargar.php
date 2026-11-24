<?php
/**
 * Descarga (o abre, si es un enlace) un recurso de material didáctico.
 * Comprueba que quien lo pide sea el profesor dueño, un administrador,
 * un estudiante del grado o un acudiente de ese estudiante.
 */
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/RecursosHelper.php';

$stmt = $pdo->prepare("SELECT * FROM recursos WHERE id = ?");
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$recurso = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recurso || !RecursosHelper::puedeVer($pdo, $recurso)) {
    http_response_code(404);
    echo 'El recurso no existe o no tiene permiso para verlo.';
    exit();
}

// Contar la descarga/visita (no cuenta las del propio profesor)
if (empty($_SESSION['profesor_id']) || (int) $_SESSION['profesor_id'] !== (int) $recurso['profesor_id']) {
    $pdo->prepare("UPDATE recursos SET descargas = descargas + 1 WHERE id = ?")->execute([$recurso['id']]);
}

if ($recurso['tipo'] === 'enlace') {
    header('Location: ' . $recurso['url']);
    exit();
}

$ruta = RecursosHelper::directorioBase() . '/' . $recurso['archivo_ruta'];
if (strpos((string) $recurso['archivo_ruta'], '..') !== false || !is_file($ruta)) {
    http_response_code(404);
    echo 'El archivo ya no está disponible en el servidor.';
    exit();
}

// PDF e imágenes se abren en el navegador; el resto se descarga
$mime = $recurso['archivo_mime'] ?: 'application/octet-stream';
$en_linea = preg_match('#^(application/pdf|image/|audio/|video/)#', $mime);
$nombre = str_replace(['"', "\r", "\n"], '', $recurso['archivo_nombre'] ?: 'recurso');

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: ' . ($en_linea ? 'inline' : 'attachment') . '; filename="' . $nombre . '"; filename*=UTF-8\'\'' . rawurlencode($nombre));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0');
readfile($ruta);
exit();
