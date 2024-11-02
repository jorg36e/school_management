<?php
session_start();
if(!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/estudiante_login.php');
    exit();
}

require_once '../config/database.php';

try {
    // Obtener información del estudiante
    $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante) {
        session_destroy();
        header('Location: ../auth/estudiante_login.php');
        exit();
    }

    // Formatear nombre completo
    $nombre_completo = htmlspecialchars($estudiante['nombre'] ?? '') . ' ' . htmlspecialchars($estudiante['apellido'] ?? '');

    // Obtener promedio actual
    $stmt = $pdo->prepare("
        SELECT COALESCE(AVG(c.nota), 0) as promedio
        FROM calificaciones c
        INNER JOIN matriculas m ON c.matricula_id = m.id
        WHERE m.estudiante_id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $result = $stmt->fetch();
    $promedio = number_format($result['promedio'] ?? 0, 2);

    // Obtener porcentaje de asistencia
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN estado = 'presente' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as porcentaje_asistencia
        FROM asistencias a
        INNER JOIN matriculas m ON a.matricula_id = m.id
        WHERE m.estudiante_id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $result = $stmt->fetch();
    $porcentaje_asistencia = number_format($result['porcentaje_asistencia'] ?? 0, 1);

    // Obtener calificaciones recientes
    $stmt = $pdo->prepare("
        SELECT c.*, a.nombre as asignatura_nombre
        FROM calificaciones c
        INNER JOIN matriculas m ON c.matricula_id = m.id
        INNER JOIN asignaturas a ON c.asignatura_id = a.id
        WHERE m.estudiante_id = ?
        ORDER BY c.fecha_evaluacion DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $calificaciones_recientes = $stmt->fetchAll();

    // Obtener asistencias recientes
    $stmt = $pdo->prepare("
        SELECT a.*, asig.nombre as asignatura_nombre
        FROM asistencias a
        INNER JOIN matriculas m ON a.matricula_id = m.id
        INNER JOIN asignaturas asig ON a.asignatura_id = asig.id
        WHERE m.estudiante_id = ?
        ORDER BY a.fecha DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $asistencias_recientes = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Error en dashboard estudiante: " . $e->getMessage());
    // Inicializar variables con valores por defecto en caso de error
    $promedio = '0.00';
    $porcentaje_asistencia = '0.0';
    $calificaciones_recientes = [];
    $asistencias_recientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiante - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f5f6fa;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
            font-size: 1.2em;
            font-weight: 600;
        }

        .sidebar-nav ul {
            list-style: none;
            padding: 10px 0;
        }

        .menu-section {
            padding: 15px 20px 8px;
            color: #95a5a6;
            font-size: 0.8em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 10px;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: #f8f9fa;
            color: #3498db;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ecf0f1;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
        }

        .content {
            padding: 20px;
            flex: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.grades {
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
        }

        .stat-icon.attendance {
            background: linear-gradient(135deg, #10B981, #34D399);
        }

        .stat-info h3 {
            font-size: 0.9em;
            color: #64748b;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 1.5em;
            font-weight: 600;
            color: #1e293b;
        }

        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .activity-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .activity-card h3 {
            font-size: 1.1em;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Sistema Escolar</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php" class="active">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>ACADÉMICO</span>
                    </li>
                    <li>
                        <a href="calificaciones.php">
                            <i class="fas fa-star"></i>
                            <span>Calificaciones</span>
                        </a>
                    </li>
                    <li>
                        <a href="asistencia.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Asistencia</span>
                        </a>
                    </li>
                    <li>
                        <a href="horario.php">
                            <i class="fas fa-clock"></i>
                            <span>Horario</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>MI CUENTA</span>
                    </li>
                    <li>
                        <a href="perfil.php">
                            <i class="fas fa-user"></i>
                            <span>Mi Perfil</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <i class="fas fa-home"></i>
                    <span>/ Dashboard</span>
                </div>
                <div class="user-info">
                    <span id="current-time"></span>
                    <div class="user-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <span><?php echo $nombre_completo; ?></span>
                        <small>Estudiante</small>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <div class="content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon grades">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Promedio General</h3>
                            <p><?php echo $promedio; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon attendance">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Asistencia</h3>
                            <p><?php echo $porcentaje_asistencia; ?>%</p>
                        </div>
                    </div>
                </div>

                <div class="activities-grid">
                    <div class="activity-card">
                        <h3>
                            <i class="fas fa-star"></i>
                            Calificaciones Recientes
                        </h3>
                        <ul class="activity-list">
                            <?php if (!empty($calificaciones_recientes)): ?>
                                <?php foreach ($calificaciones_recientes as $calificacion): ?>
                                    <li class="activity-item">
                                        <span>
                                            <?php echo htmlspecialchars($calificacion['asignatura_nombre'] ?? 'N/A'); ?>
                                            <small>(<?php echo date('d/m/Y', strtotime($calificacion['fecha_evaluacion'])); ?>)</small>
                                        </span>
                                        <span class="badge <?php 
                                            echo $calificacion['nota'] >= 4.0 ? 'badge-success' : 
                                                ($calificacion['nota'] >= 3.0 ? 'badge-warning' : 'badge-danger'); 
                                        ?>">
                                            <?php echo number_format($calificacion['nota'], 1); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="activity-item">No hay calificaciones recientes</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="activity-card">
                        <h3>
                            <i class="fas fa-calendar-check"></i>
                            Asistencias Recientes
                        </h3>
                        <ul class="activity-list">
                            <?php if (!empty($asistencias_recientes)): ?>
                                <?php foreach ($asistencias_recientes as $asistencia): ?>
                                    <li class="activity-item">
                                        <span>
                                            <?php echo htmlspecialchars($asistencia['asignatura_nombre'] ?? 'N/A'); ?>
                                            <small>(<?php echo date('d/m/Y', strtotime($asistencia['fecha'])); ?>)</small>
                                        </span>
                                        <span class="badge <?php 
                                            echo $asistencia['estado'] == 'presente' ? 'badge-success' : 
                                                ($asistencia['estado'] == 'tardanza' ? 'badge-warning' : 'badge-danger'); 
                                        ?>">
                                            <?php echo ucfirst($asistencia['estado']); ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="activity-item">No hay registros de asistencia recientes</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Función para actualizar el reloj en tiempo real
        function updateTime() {
            const now = new Date();
            const options = { 
                hour: 'numeric', 
                minute: 'numeric', 
                second: 'numeric',
                hour12: true 
            };
            const timeString = now.toLocaleTimeString('es-ES', options);
            document.getElementById('current-time').textContent = timeString;
        }
        
        // Iniciar reloj y actualizarlo cada segundo
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle para el sidebar en dispositivos móviles
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        document.addEventListener('DOMContentLoaded', function() {
            const mediaQuery = window.matchMedia('(max-width: 768px)');
            
            function handleScreenChange(e) {
                if (e.matches) {
                    sidebar.style.position = 'fixed';
                    sidebar.style.left = '-260px';
                } else {
                    sidebar.style.position = 'relative';
                    sidebar.style.left = '0';
                }
            }
            
            mediaQuery.addListener(handleScreenChange);
            handleScreenChange(mediaQuery);
        });

        // Animación para las tarjetas de estadísticas
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach(card => {
            card.addEventListener('mouseover', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
            });

            card.addEventListener('mouseout', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';
            });
        });

        // Confirmación antes de cerrar sesión
        const logoutBtn = document.querySelector('.logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                if (!confirm('¿Está seguro que desea cerrar sesión?')) {
                    e.preventDefault();
                }
            });
        }

        // Manejo de errores global
        window.addEventListener('error', function(e) {
            console.error('Error en la aplicación:', e.message);
            // Aquí podrías implementar un sistema de registro de errores
        });

        // Función para refrescar los datos del dashboard (opcional)
        function refreshDashboardData() {
            fetch('get_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.promedio) {
                        document.querySelector('.grades p').textContent = data.promedio;
                    }
                    if (data.asistencia) {
                        document.querySelector('.attendance p').textContent = data.asistencia + '%';
                    }
                })
                .catch(error => console.error('Error al actualizar datos:', error));
        }

        // Actualizar datos cada 5 minutos (opcional, comentado por defecto)
        // setInterval(refreshDashboardData, 300000);
    </script>
</body>
</html>