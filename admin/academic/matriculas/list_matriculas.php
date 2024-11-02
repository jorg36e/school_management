<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../../auth/login.php');
    exit();
}

require_once '../../../config/database.php';

try {
    // Construir la consulta base
    $sql = "SELECT m.*, 
            e.nombre as estudiante_nombre, 
            e.apellido as estudiante_apellido 
            FROM matriculas m 
            INNER JOIN estudiantes e ON m.estudiante_id = e.id 
            ORDER BY m.fecha_matricula DESC";
    $stmt = $pdo->query($sql);
    $matriculas = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener la lista de matrículas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrículas - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/admin.css">
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

        .content-wrapper {
            padding: 20px;
        }

        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .add-button {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .add-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
        }

        .status-Activa { background: #e8f5e9; color: #2e7d32; }
        .status-Inactiva { background: #ffebee; color: #c62828; }
        .status-Pendiente { background: #fff3e0; color: #ef6c00; }

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
                        <a href="../../dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>Gestión de Usuarios</span>
                    </li>
                    <li>
                        <a href="../../users/list_teachers.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Profesores</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../users/list_students.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Estudiantes</span>
                        </a>
                    </li>
                    <li>
                        <a href="../../users/list_parents.php">
                            <i class="fas fa-users"></i>
                            <span>Padres de Familia</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>SEDES</span>
                    </li>
                    <li>
                        <a href="../../users/list_headquarters.php">
                            <i class="fas fa-building"></i>
                            <span>Lista de Sedes</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>PLANEACIÓN ACADÉMICA</span>
                    </li>
                    <li>
                        <a href="../list_dba.php">
                            <i class="fas fa-book"></i>
                            <span>DBA</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>MATRÍCULAS</span>
                    </li>
                    <li>
                        <a href="list_matriculas.php" class="active">
                            <i class="fas fa-user-plus"></i>
                            <span>Matrículas</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Barra superior -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <i class="fas fa-user-plus"></i>
                        <span>/ Matrículas</span>
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
                            <a href="../../../auth/logout.php" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Contenido principal -->
            <div class="content-wrapper">
                <div class="matriculas-list">
                    <div class="list-header">
                        <h2>Lista de Matrículas</h2>
                        <a href="create_matricula.php" class="add-button">
                            <i class="fas fa-plus"></i> Nueva Matrícula
                        </a>
                    </div>

                    <!-- Tabla -->
                    <table>
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Grado</th>
                                <th>Periodo</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($matriculas)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px;">
                                        No se encontraron matrículas
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($matriculas as $matricula): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($matricula['estudiante_nombre'] . ' ' . $matricula['estudiante_apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($matricula['grado']); ?></td>
                                        <td><?php echo htmlspecialchars($matricula['periodo']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($matricula['fecha_matricula'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $matricula['estado']; ?>">
                                                <?php echo $matricula['estado']; ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <a href="view_matricula.php?id=<?php echo $matricula['id']; ?>" 
                                               class="btn-view" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_matricula.php?id=<?php echo $matricula['id']; ?>" 
                                               class="btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" 
                                               onclick="confirmarEliminacion(<?php echo $matricula['id']; ?>)"
                                               class="btn-delete" title="Eliminar">
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
            if(confirm('¿Está seguro que desea eliminar esta matrícula?')) {
                window.location.href = `delete_matricula.php?id=${id}`;
            }
        }
    </script>
</body>
</html>