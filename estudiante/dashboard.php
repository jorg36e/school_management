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

    $nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido'];

    // Obtener promedio
    $stmt = $pdo->prepare("
        SELECT COALESCE(AVG(c.nota), 0) as promedio
        FROM calificaciones c
        INNER JOIN matriculas m ON c.matricula_id = m.id
        WHERE m.estudiante_id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $result = $stmt->fetch();
    $promedio = number_format($result['promedio'] ?? 0, 2);

    // Obtener asistencia
    $stmt = $pdo->prepare("
        SELECT COUNT(CASE WHEN estado = 'presente' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as porcentaje_asistencia
        FROM asistencias a
        INNER JOIN matriculas m ON a.matricula_id = m.id
        WHERE m.estudiante_id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $result = $stmt->fetch();
    $porcentaje_asistencia = number_format($result['porcentaje_asistencia'] ?? 0, 1);

    // Obtener materias
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT asignatura_id) as total_materias
        FROM matriculas m
        INNER JOIN asignaciones_profesor ap ON m.grupo_id = ap.grupo_id
        WHERE m.estudiante_id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $result = $stmt->fetch();
    $total_materias = $result['total_materias'] ?? 0;

} catch(PDOException $e) {
    error_log("Error en dashboard estudiante: " . $e->getMessage());
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
            background: #f6f8fa;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            z-index: 1000;
        }

        .logo {
            padding: 20px;
            color: #2563eb;
            font-size: 1.2em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .menu-section {
            padding: 15px 20px 8px;
            font-size: 0.75em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
        }

        .sidebar-nav ul {
            list-style: none;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #1f2937;
            text-decoration: none;
            gap: 10px;
            transition: all 0.3s;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: #f8fafc;
            color: #2563eb;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        .top-header {
            background: #1e293b;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.25rem;
            cursor: pointer;
            display: none;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 20px;
        }

        .dashboard-title {
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 1.5rem;
        }

        .icon-promedio { background: #22c55e; color: white; }
        .icon-asistencia { background: #6366f1; color: white; }
        .icon-materias { background: #f59e0b; color: white; }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 4px;
        }

        .stat-value {
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Quick Actions */
        .quick-actions {
            margin-bottom: 30px;
        }

        .section-title {
            color: #1f2937;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #1f2937;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s;
        }

        .action-card:hover {
            transform: translateY(-5px);
        }

        .action-icon {
            font-size: 1.5rem;
            color: #2563eb;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                position: fixed;
                left: -260px;
                height: 100vh;
                transition: 0.3s;
            }

            .sidebar.active {
                left: 0;
            }

            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <span>Sistema Escolar</span>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="dashboard.php" class="active">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="menu-section">ACADÉMICO</li>
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

                    <li class="menu-section">MI CUENTA</li>
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
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    🏠 / Dashboard
                </div>
                <div class="header-right">
                    <div class="current-time">
                        <i class="far fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($nombre_completo); ?> Estudiante
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </header>

            <div class="dashboard-content">
                <h1 class="dashboard-title">Dashboard del Estudiante</h1>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon icon-promedio">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Promedio General</div>
                            <div class="stat-value"><?php echo $promedio; ?></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon icon-asistencia">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Asistencia</div>
                            <div class="stat-value"><?php echo $porcentaje_asistencia; ?>%</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon icon-materias">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Materias</div>
                            <div class="stat-value"><?php echo $total_materias; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <section class="quick-actions">
                    <h2 class="section-title">Acciones Rápidas</h2>
                    <div class="actions-grid">
                        <a href="calificaciones.php" class="action-card">
                            <i class="fas fa-star action-icon"></i>
                            <span>Ver Calificaciones</span>
                        </a>
                        <a href="asistencia.php" class="action-card">
                            <i class="fas fa-calendar-check action-icon"></i>
                            <span>Ver Asistencia</span>
                        </a>
                        <a href="horario.php" class="action-card">
                            <i class="fas fa-clock action-icon"></i>
                            <span>Ver Horario</span>
                        </a>
                        <a href="tareas.php" class="action-card">
                            <i class="fas fa-tasks action-icon"></i>
                            <span>Ver Tareas</span>
                        </a>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        // Actualizar reloj
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
        
        updateTime();
        setInterval(updateTime, 1000);

        // Toggle sidebar en móviles
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Cerrar sidebar al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active
                    sidebar.classList.remove('active');
           }
       });

       // Cerrar sesión confirmación
       document.querySelector('.logout-btn').addEventListener('click', function(e) {
           if (!confirm('¿Está seguro que desea cerrar sesión?')) {
               e.preventDefault();
           }
       });

       // Detectar cambios de tamaño de ventana
       window.addEventListener('resize', function() {
           if (window.innerWidth > 768) {
               sidebar.classList.remove('active');
           }
       });

       // Animaciones suaves para las cards
       document.querySelectorAll('.stat-card, .action-card').forEach(card => {
           card.addEventListener('mouseover', function() {
               this.style.transform = 'translateY(-5px)';
               this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
           });

           card.addEventListener('mouseout', function() {
               this.style.transform = '';
               this.style.boxShadow = '';
           });
       });
   </script>
</body>
</html>