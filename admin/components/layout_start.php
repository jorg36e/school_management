<?php
/**
 * Inicio común de las páginas del panel de administración.
 *
 * Antes de incluirlo, la página puede definir:
 *   $topbar_title  Título que se muestra en la barra superior.
 *   $page_css      CSS adicional (cadena) para esa página.
 */
$base_url = $base_url ?? '/school_management';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topbar_title ?? 'Administración'); ?> - Sistema Escolar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <?php if (!empty($page_css)): ?>
        <style><?php echo $page_css; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/../sidebar.php'; ?>

        <main class="main-content">
            <?php include __DIR__ . '/topbar.php'; ?>
            <div class="content-wrapper">
