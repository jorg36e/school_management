<?php
/** Encabezado del portal de padres. Definir $titulo y $pagina_activa antes de incluir. */
$base_url = '/school_management';
require_once __DIR__ . '/../../helpers/MensajesHelper.php';
$mensajes_sin_leer = MensajesHelper::noLeidos($pdo, 'padre', (int) $padre['id']);
$menu_padre = [
    'dashboard' => ['url' => 'dashboard.php', 'icono' => 'fa-house', 'texto' => 'Inicio'],
    'calificaciones' => ['url' => 'calificaciones.php', 'icono' => 'fa-chart-line', 'texto' => 'Notas'],
    'mensajes' => ['url' => 'mensajes.php', 'icono' => 'fa-envelope', 'texto' => 'Mensajes'],
    'perfil' => ['url' => 'perfil.php', 'icono' => 'fa-user', 'texto' => 'Mi perfil'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo ?? 'Portal de padres'); ?> - Sistema Escolar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/portal.css">
</head>
<body class="portal">
    <header class="portal-header">
        <div class="portal-header-inner">
            <a href="dashboard.php" class="portal-brand">
                <span class="portal-brand-icon"><i class="fas fa-graduation-cap"></i></span>
                <span>Portal de padres</span>
            </a>
            <nav class="portal-nav" aria-label="Menú principal">
                <?php foreach ($menu_padre as $clave => $item): ?>
                    <a href="<?php echo $item['url']; ?>" class="<?php echo ($pagina_activa ?? '') === $clave ? 'active' : ''; ?>">
                        <i class="fas <?php echo $item['icono']; ?>"></i>
                        <span><?php echo $item['texto']; ?></span>
                        <?php if ($clave === 'mensajes' && !empty($mensajes_sin_leer)): ?>
                            <span class="portal-badge"><?php echo $mensajes_sin_leer; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="portal-user">
                <span class="portal-user-name"><?php echo htmlspecialchars($padre['nombre']); ?></span>
                <a href="<?php echo $base_url; ?>/auth/logout.php" class="btn btn-sm btn-secondary" title="Cerrar sesión">
                    <i class="fas fa-right-from-bracket"></i> <span class="hide-sm">Salir</span>
                </a>
            </div>
        </div>
    </header>
    <main class="portal-main">
