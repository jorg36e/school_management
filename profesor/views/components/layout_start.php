<?php
/**
 * Inicio común para páginas del módulo del profesor.
 * Requiere sesión de profesor. Deja disponibles $pdo y $profesor.
 * Antes de incluirlo, la página puede definir $page_title y $page_css.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['profesor_id'])) {
    header('Location: /school_management/auth/profesor_login.php');
    exit();
}

require_once __DIR__ . '/../../../config/database.php';
$profesor_base_url = '/school_management/profesor';

if (!isset($profesor)) {
    $stmt = $pdo->prepare("
        SELECT p.*, s.nombre AS sede_nombre
        FROM profesores p LEFT JOIN sedes s ON s.id = p.sede_id
        WHERE p.id = ?
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $profesor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$profesor) {
        session_destroy();
        header('Location: /school_management/auth/profesor_login.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?php echo $profesor_base_url; ?>">
    <title><?php echo htmlspecialchars($page_title ?? 'Profesor'); ?> | <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/school_management/assets/css/admin.css">
    <style>
        .tabs { display:flex; gap:0.25rem; border-bottom:1px solid var(--border-color); margin-bottom:1.25rem; flex-wrap:wrap; }
        .tabs a { padding:0.65rem 1rem; color:var(--muted-text); font-weight:500; border-bottom:2px solid transparent; margin-bottom:-1px; }
        .tabs a.active { color:var(--primary-light); border-bottom-color:var(--primary-light); }
        .tabs .count { background:var(--danger-color); color:#fff; border-radius:999px; font-size:0.7rem; padding:0.05rem 0.45rem; margin-left:0.3rem; }
        <?php echo $page_css ?? ''; ?>
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/topbar.php'; ?>
            <div class="content-wrapper" style="padding-top: 1rem;">
