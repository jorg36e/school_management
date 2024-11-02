<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Construir la consulta base
    $sql = "SELECT p.*, pr.nombre as profesor_nombre, pr.apellido as profesor_apellido 
            FROM planeaciones p 
            INNER JOIN profesores pr ON p.profesor_id = pr.id 
            ORDER BY p.fecha_creacion DESC";
    $stmt = $pdo->query($sql);
    $planeaciones = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener las planeaciones: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DBA - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
        .top-nav {
            background: #2c3e50;
            color: white;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .top-nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout {
            background: #c0392b;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .main-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-button {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filters-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters-form {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 0.9em;
            color: #666;
        }

        .filter-select,
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }

        .btn-search {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
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
            <a href="../dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-section">
            <span>Gestión de Usuarios</span>
        </li>
        <li>
            <a href="../users/list_teachers.php">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Profesores</span>
            </a>
        </li>
        <li>
            <a href="../users/list_students.php">
                <i class="fas fa-user-graduate"></i>
                <span>Estudiantes</span>
            </a>
        </li>
        <li>
            <a href="../users/list_parents.php">
                <i class="fas fa-users"></i>
                <span>Padres de Familia</span>
            </a>
        </li>
        <li class="menu-section">
            <span>SEDES</span>
        </li>
        <li>
            <a href="../users/list_headquarters.php">
                <i class="fas fa-building"></i>
                <span>Lista de Sedes</span>
            </a>
        </li>
        <li class="menu-section">
            <span>PLANEACIÓN ACADÉMICA</span>
        </li>
        <li>
            <a href="list_dba.php" class="active">
                <i class="fas fa-book"></i>
                <span>DBA</span>
            </a>
        </li>
        <li class="menu-section">
            <span>MATRÍCULAS</span>
        </li>
        <li>
            <a href="matriculas/list_matriculas.php">
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
                        <i class="fas fa-book"></i>
                        <span>/ DBA</span>
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
                            <a href="../../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido principal -->
            <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <div class="main-title">
                    <h2>Derechos Básicos de Aprendizaje (DBA)</h2>
                    <a href="create_dba.php" class="add-button">
                        + Nueva Planeación
                    </a>
                </div>

                <!-- Filtros -->
                <div class="filters-container">
                    <form method="GET" action="" class="filters-form">
                        <div class="filter-group">
                            <label class="filter-label">Filtrar por:</label>
                            <select name="filtro_tipo" class="filter-select">
                                <option value="">Seleccione un filtro</option>
                                <option value="titulo">Título</option>
                                <option value="materia">Materia</option>
                                <option value="profesor">Profesor</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Buscar:</label>
                            <input type="text" name="busqueda" class="filter-input" 
                                   placeholder="Escriba su búsqueda..." 
                                   value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>">
                        </div>

                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </form>
                </div>

                <!-- Tabla -->
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <table>
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Materia</th>
                                <th>Grado</th>
                                <th>Profesor</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($planeaciones)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">
                                        No se encontraron planeaciones
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($planeaciones as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['materia']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['grado']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['profesor_nombre'] . ' ' . $plan['profesor_apellido']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo str_replace(' ', '', $plan['estado']); ?>">
                                                <?php echo $plan['estado']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($plan['fecha_creacion'])); ?></td>
                                        <td class="actions">
                                            <a href="view_dba.php?id=<?php echo $plan['id']; ?>" class="btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_dba.php?id=<?php echo $plan['id']; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" 
                                               onclick="confirmarEliminacion(<?php echo $plan['id']; ?>)" 
                                               class="btn-delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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

        // Función para confirmar eliminación
        function confirmarEliminacion(id) {
            if(confirm('¿Está seguro que desea eliminar esta planeación?')) {
                window.location.href = `delete_dba.php?id=${id}`;
            }
        }
    </script>
</body>
</html>