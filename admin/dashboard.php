<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/database.php';

try {
    // Contar profesores activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM profesores WHERE estado = 'activo'");
    $total_profesores = $stmt->fetchColumn();

    // Contar estudiantes (cuando exista la tabla)
    $total_estudiantes = 0;

    // Contar padres (cuando exista la tabla)
    $total_padres = 0;

    // Contar sedes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sedes");
    $total_sedes = $stmt->fetch()['total'];

} catch(PDOException $e) {
    $error = "Error al obtener estadísticas: " . $e->getMessage();
    $total_profesores = 0;
    $total_estudiantes = 0;
    $total_padres = 0;
    $total_sedes = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Escolar</title>
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
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #e74c3c;
            transform: translateY(-2px);
        }

        #sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        #sidebar-toggle:hover {
            background: rgba(255,255,255,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .stat-icon.teacher { background: linear-gradient(135deg, #4E54C8, #8F94FB); }
        .stat-icon.student { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .stat-icon.parent { background: linear-gradient(135deg, #6B8DD6, #8E37D7); }
        .stat-icon.building { background: linear-gradient(135deg, #F2994A, #F2C94C); }

        .stat-details h3 {
            margin: 0;
            color: #666;
            font-size: 0.9em;
            font-weight: 500;
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: 600;
            color: #2d3436;
            margin: 0;
        }

        .quick-actions {
            margin-top: 2rem;
        }

        .quick-actions h2 {
            color: #2d3436;
            margin-bottom: 1rem;
            font-size: 1.5em;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-card i {
            font-size: 2em;
            color: #3498db;
        }

        .action-card span {
            font-weight: 500;
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
                        <span>Gestión de Usuarios</span>
                    </li>
                    <li>
                        <a href="users/list_teachers.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Profesores</span>
                        </a>
                    </li>
                    <li>
                        <a href="users/list_students.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Estudiantes</span>
                        </a>
                    </li>
                    <li>
                        <a href="users/list_parents.php">
                            <i class="fas fa-users"></i>
                            <span>Padres de Familia</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>SEDES</span>
                    </li>
                    <li>
                        <a href="users/list_headquarters.php">
                            <i class="fas fa-building"></i>
                            <span>Lista de Sedes</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>PLANEACIÓN ACADÉMICA</span>
                    </li>
                    <li>
                        <a href="academic/list_dba.php">
                            <i class="fas fa-book"></i>
                            <span>DBA</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>MATRÍCULAS</span>
                    </li>
                    <li>
                        <a href="academic/matriculas/list_matriculas.php">
                            <i class="fas fa-user-plus"></i>
                            <span>Matrículas</span>
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
                        <div class="user-details">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></span>
                            <span class="user-role">Administrador</span>
                        </div>
                        <div class="user-menu">
                            <a href="../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="page-header">
                    <h1>Dashboard</h1>
                </div>

                <!-- Tarjetas de Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon teacher">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Profesores</h3>
                            <p class="stat-number"><?php echo $total_profesores; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon student">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Estudiantes</h3>
                            <p class="stat-number"><?php echo $total_estudiantes; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon parent">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Padres</h3>
                            <p class="stat-number"><?php echo $total_padres; ?></p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon building">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Sedes</h3>
                            <p class="stat-number"><?php echo $total_sedes; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <section class="quick-actions">
                    <h2>Acciones Rápidas</h2>
                    <div class="actions-grid">
                        <a href="users/list_teachers.php" class="action-card">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Profesores</span>
                        </a>
                        
                        <a href="users/list_students.php" class="action-card">
                            <i class="fas fa-user-graduate"></i>
                            <span>Estudiantes</span>
                        </a>
                        
                        <a href="users/list_parents.php" class="action-card">
                            <i class="fas fa-users"></i>
                            <span>Padres</span>
                        </a>
                        
                        <a href="users/list_headquarters.php" class="action-card">
                            <i class="fas fa-building"></i>
                            <span>Sedes</span>
                        </a>

                        <a href="academic/list_dba.php" class="action-card">
                            <i class="fas fa-book"></i>
                            <span>DBA</span>
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