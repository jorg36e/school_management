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

    // Obtener estadísticas de calificaciones
    $stmt = $pdo->prepare("
        SELECT 
            AVG(c.nota) as promedio_general,
            COUNT(*) as total_evaluaciones,
            COUNT(CASE WHEN c.nota >= 4.0 THEN 1 END) as notas_altas,
            COUNT(CASE WHEN c.nota < 3.0 THEN 1 END) as notas_bajas
        FROM calificaciones c
        INNER JOIN matriculas m ON c.matricula_id = m.id
        WHERE m.estudiante_id = ?
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calcular porcentajes
    $total_evaluaciones = $estadisticas['total_evaluaciones'] > 0 ? $estadisticas['total_evaluaciones'] : 1;
    $promedio_general = number_format($estadisticas['promedio_general'] ?? 0, 2);
    $porcentaje_altas = number_format(($estadisticas['notas_altas'] * 100) / $total_evaluaciones, 1);
    $porcentaje_bajas = number_format(($estadisticas['notas_bajas'] * 100) / $total_evaluaciones, 1);

    // Obtener lista de calificaciones
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            asig.nombre as asignatura_nombre,
            p.nombre as profesor_nombre,
            p.apellido as profesor_apellido
        FROM calificaciones c
        INNER JOIN matriculas m ON c.matricula_id = m.id
        INNER JOIN asignaturas asig ON c.asignatura_id = asig.id
        INNER JOIN profesores p ON asig.profesor_id = p.id
        WHERE m.estudiante_id = ?
        ORDER BY c.fecha_evaluacion DESC
    ");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $calificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error en calificaciones estudiante: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificaciones - Sistema Escolar</title>
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

        /* Top Header */
        .top-header {
            background: #1e293b;
            color: white;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .current-time {
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-image {
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .user-role {
            font-size: 0.8rem;
            background: #3b82f6;
            padding: 2px 8px;
            border-radius: 4px;
            color: white;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background-color 0.2s;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        .page-title {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Stats Grid */
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .promedio-icon { background: #10b981; }
        .alta-icon { background: #6366f1; }
        .baja-icon { background: #ef4444; }

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

        /* Grades Table */
        .grades-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #f8fafc;
            padding: 15px 20px;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
            font-weight: 500;
            color: #4b5563;
        }

        .grade-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .grade-alta {
            background: #dcfce7;
            color: #166534;
        }

        .grade-media {
            background: #fef3c7;
            color: #92400e;
        }

        .grade-baja {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .sidebar.active {
                display: block;
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 1000;
            }

            .menu-toggle {
                display: block;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .grades-table {
                overflow-x: auto;
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
                Sistema Escolar
            </div>
            <nav class="sidebar-nav">
                <div class="menu-section">Dashboard</div>
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>

                <div class="menu-section">ACADÉMICO</div>
                <a href="calificaciones.php" class="active">
                    <i class="fas fa-star"></i>
                    <span>Calificaciones</span>
                </a>
                <a href="asistencia.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Asistencia</span>
                </a>
                <a href="horario.php">
                    <i class="fas fa-clock"></i>
                    <span>Horario</span>
                </a>

                <div class="menu-section">MI CUENTA</div>
                <a href="perfil.php">
                    <i class="fas fa-user"></i>
                    <span>Mi Perfil</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    ⭐ / Calificaciones
                </div>
                <div class="header-right">
                    <div class="current-time">
                        <i class="far fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="user-info">
                        <div class="user-image">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($nombre_completo); ?></span>
                            <span class="user-role">Estudiante</span>
                        </div>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <h1 class="page-title">Registro de Calificaciones</h1>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon promedio-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Promedio General</div>
                        <div class="stat-value"><?php echo $promedio_general; ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon alta-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Notas Altas</div>
                        <div class="stat-value"><?php echo $porcentaje_altas; ?>%</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon baja-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-label">Notas Bajas</div>
                        <div class="stat-value"><?php echo $porcentaje_bajas; ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Grades Table -->
            <div class="grades-table">
                <div class="table-header">
                    Historial de Calificaciones
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Asignatura</th>
                            <th>Profesor</th>
                            <th>Evaluación</th>
                            <th>Nota</th>
                            <th>Observaciones</th>
                       </tr>
                   </thead>
                   <tbody>
                       <?php if (empty($calificaciones)): ?>
                           <tr>
                               <td colspan="6" style="text-align: center; padding: 20px;">
                                   No hay registros de calificaciones
                               </td>
                           </tr>
                       <?php else: ?>
                           <?php foreach ($calificaciones as $calificacion): ?>
                               <tr>
                                   <td><?php echo date('d/m/Y', strtotime($calificacion['fecha_evaluacion'])); ?></td>
                                   <td><?php echo htmlspecialchars($calificacion['asignatura_nombre']); ?></td>
                                   <td><?php echo htmlspecialchars($calificacion['profesor_nombre'] . ' ' . $calificacion['profesor_apellido']); ?></td>
                                   <td><?php echo htmlspecialchars($calificacion['tipo_evaluacion']); ?></td>
                                   <td>
                                       <span class="grade-badge <?php 
                                           echo $calificacion['nota'] >= 4.0 ? 'grade-alta' : 
                                               ($calificacion['nota'] >= 3.0 ? 'grade-media' : 'grade-baja'); 
                                       ?>">
                                           <?php echo number_format($calificacion['nota'], 1); ?>
                                       </span>
                                   </td>
                                   <td><?php echo htmlspecialchars($calificacion['observaciones'] ?? ''); ?></td>
                               </tr>
                           <?php endforeach; ?>
                       <?php endif; ?>
                   </tbody>
               </table>
           </div>
       </main>
   </div>

   <script>
       function updateTime() {
           const now = new Date();
           let hours = now.getHours();
           let minutes = now.getMinutes();
           let seconds = now.getSeconds();
           let meridiem = hours >= 12 ? 'p.m.' : 'a.m.';
           
           hours = hours % 12;
           hours = hours ? hours : 12;
           minutes = minutes < 10 ? '0' + minutes : minutes;
           seconds = seconds < 10 ? '0' + seconds : seconds;
           
           const timeString = `${hours}:${minutes}:${seconds} ${meridiem}`;
           document.getElementById('current-time').textContent = timeString;
       }
       
       updateTime();
       setInterval(updateTime, 1000);

       // Toggle sidebar en móviles
       const menuBtn = document.querySelector('.menu-toggle');
       const sidebar = document.querySelector('.sidebar');
       
       if (menuBtn) {
           menuBtn.addEventListener('click', () => {
               sidebar.classList.toggle('active');
           });
       }

       // Cerrar sidebar al hacer clic fuera en móviles
       document.addEventListener('click', (e) => {
           if (window.innerWidth <= 768 && 
               !sidebar.contains(e.target) && 
               !menuBtn.contains(e.target)) {
               sidebar.classList.remove('active');
           }
       });

       // Animaciones para las cards
       document.querySelectorAll('.stat-card').forEach(card => {
           card.addEventListener('mouseenter', function() {
               this.style.transform = 'translateY(-5px)';
               this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
           });

           card.addEventListener('mouseleave', function() {
               this.style.transform = '';
               this.style.boxShadow = '';
           });
       });
   </script>
</body>
</html>