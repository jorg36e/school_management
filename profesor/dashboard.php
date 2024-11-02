<?php
session_start();
if(!isset($_SESSION['profesor_id'])) {
    header('Location: ../auth/profesor_login.php');
    exit();
}

require_once '../config/database.php';

try {
    // Obtener información del profesor actual
    $stmt = $pdo->prepare("SELECT * FROM profesores WHERE id = ?");
    $stmt->execute([$_SESSION['profesor_id']]);
    $profesor = $stmt->fetch();

    // Obtener total de estudiantes asignados al profesor
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT e.id) 
        FROM estudiantes e 
        INNER JOIN asignaciones_profesor ap ON e.grupo_id = ap.grupo_id 
        WHERE ap.profesor_id = ? AND e.estado = 'activo'
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_estudiantes = $stmt->fetchColumn();

    // Obtener total de grupos asignados
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT grupo_id) 
        FROM asignaciones_profesor 
        WHERE profesor_id = ?
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_grupos = $stmt->fetchColumn();

    // Obtener total de materias que imparte
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT asignatura_id) 
        FROM asignaciones_profesor 
        WHERE profesor_id = ?
    ");
    $stmt->execute([$_SESSION['profesor_id']]);
    $total_materias = $stmt->fetchColumn();

} catch(PDOException $e) {
    error_log("Error en dashboard del profesor: " . $e->getMessage());
    $error = "Error al obtener estadísticas";
    $total_estudiantes = 0;
    $total_grupos = 0;
    $total_materias = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profesor - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .breadcrumb {
            color: #ecf0f1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .top-bar-time {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ecf0f1;
            font-size: 0.9rem;
            padding: 5px 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 5px;
            border-radius: 8px;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            border: 2px solid #ecf0f1;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.8rem;
            color: #bdc3c7;
        }

        .logout-btn {
            background: #c0392b;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.students { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .stat-icon.groups { background: linear-gradient(135deg, #4E54C8, #8F94FB); }
        .stat-icon.subjects { background: linear-gradient(135deg, #F2994A, #F2C94C); }

        .quick-actions {
            margin: 20px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #2d3436;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .action-card i {
            font-size: 2em;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
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
                        <a href="grupos/mis_grupos.php">
                            <i class="fas fa-users"></i>
                            <span>Mis Grupos</span>
                        </a>
                    </li>
                    <li>
                        <a href="calificaciones/lista_calificaciones.php">
                            <i class="fas fa-star"></i>
                            <span>Calificaciones</span>
                        </a>
                    </li>
                    <li>
                        <a href="asistencia/lista_asistencia.php">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Asistencia</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>PLANEACIÓN</span>
                    </li>
                    <li>
                        <a href="planeacion/mis_planeaciones.php">
                            <i class="fas fa-book"></i>
                            <span>Mis Planeaciones</span>
                        </a>
                    </li>
                    <li>
                        <a href="recursos/mis_recursos.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Recursos Didácticos</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <main class="main-content">
           <header class="top-bar">
               <div class="top-bar-left">
                   <button id="sidebar-toggle">
                       <i class="fas fa-bars"></i>
                   </button>
                   <div class="breadcrumb">
                       <i class="fas fa-home"></i>
                       <span>/ Dashboard</span>
                   </div>
               </div>
               <div class="top-bar-right">
                   <div class="top-bar-time">
                       <i class="fas fa-clock"></i>
                       <span id="current-time"></span>
                   </div>
                   <div class="user-info">
                       <div class="user-avatar">
                           <i class="fas fa-user"></i>
                       </div>
                       <span>Profesor</span>
                       <span>Profesor</span>
                   </div>
                   <a href="../auth/logout.php" class="logout-btn">
                       <i class="fas fa-sign-out-alt"></i>
                       <span>Cerrar Sesión</span>
                   </a>
               </div>
           </header>

           <div class="content-wrapper">
               <div class="page-header">
                   <h1>Dashboard del Profesor</h1>
               </div>

               <!-- Tarjetas de Estadísticas -->
               <div class="stats-grid">
                   <div class="stat-card">
                       <div class="stat-icon students">
                           <i class="fas fa-user-graduate"></i>
                       </div>
                       <div class="stat-details">
                           <h3>Estudiantes</h3>
                           <p class="stat-number"><?php echo $total_estudiantes; ?></p>
                       </div>
                   </div>

                   <div class="stat-card">
                       <div class="stat-icon groups">
                           <i class="fas fa-users"></i>
                       </div>
                       <div class="stat-details">
                           <h3>Grupos</h3>
                           <p class="stat-number"><?php echo $total_grupos; ?></p>
                       </div>
                   </div>

                   <div class="stat-card">
                       <div class="stat-icon subjects">
                           <i class="fas fa-book"></i>
                       </div>
                       <div class="stat-details">
                           <h3>Materias</h3>
                           <p class="stat-number"><?php echo $total_materias; ?></p>
                       </div>
                   </div>
               </div>

               <!-- Acciones Rápidas -->
               <section class="quick-actions">
                   <h2>Acciones Rápidas</h2>
                   <div class="actions-grid">
                       <a href="grupos/mis_grupos.php" class="action-card">
                           <i class="fas fa-users"></i>
                           <span>Mis Grupos</span>
                       </a>
                       
                       <a href="calificaciones/lista_calificaciones.php" class="action-card">
                           <i class="fas fa-star"></i>
                           <span>Calificaciones</span>
                       </a>
                       
                       <a href="asistencia/lista_asistencia.php" class="action-card">
                           <i class="fas fa-clipboard-list"></i>
                           <span>Asistencia</span>
                       </a>
                       
                       <a href="planeacion/mis_planeaciones.php" class="action-card">
                           <i class="fas fa-book"></i>
                           <span>Planeaciones</span>
                       </a>

                       <a href="recursos/mis_recursos.php" class="action-card">
                           <i class="fas fa-file-alt"></i>
                           <span>Recursos</span>
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
           const timeString = now.toLocaleTimeString();
           document.getElementById('current-time').textContent = timeString;
       }
       
       updateTime();
       setInterval(updateTime, 1000);

       // Toggle sidebar
       document.getElementById('sidebar-toggle').addEventListener('click', function() {
           document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
       });
   </script>
</body>
</html>