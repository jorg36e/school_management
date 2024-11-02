<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Obtener lista de sedes
    $sql = "SELECT * FROM sedes ORDER BY nombre";
    $stmt = $pdo->query($sql);
    $sedes = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener la lista de sedes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Sedes - Sistema Escolar</title>
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

        .content-wrapper {
            padding: 20px;
        }

        .headquarters-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .list-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 600;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
        }

        .status-activo {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactivo {
            background: #ffebee;
            color: #c62828;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit, 
        .btn-delete, 
        .btn-activate {
            padding: 4px 8px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .btn-edit { background: #3498db; }
        .btn-delete { background: #e74c3c; }
        .btn-activate { background: #27ae60; }

        .btn-edit:hover, 
        .btn-delete:hover, 
        .btn-activate:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 3rem;
            color: #a0aec0;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: #4a5568;
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
            <a href="list_teachers.php">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Profesores</span>
            </a>
        </li>
        <li>
            <a href="list_students.php">
                <i class="fas fa-user-graduate"></i>
                <span>Estudiantes</span>
            </a>
        </li>
        <li>
            <a href="list_parents.php">
                <i class="fas fa-users"></i>
                <span>Padres de Familia</span>
            </a>
        </li>
        <li class="menu-section">
            <span>SEDES</span>
        </li>
        <li>
            <a href="list_headquarters.php" class="active">
                <i class="fas fa-building"></i>
                <span>Lista de Sedes</span>
            </a>
        </li>
        <li class="menu-section">
            <span>PLANEACIÓN ACADÉMICA</span>
        </li>
        <li>
            <a href="../academic/list_dba.php">
                <i class="fas fa-book"></i>
                <span>DBA</span>
            </a>
        </li>
        <li class="menu-section">
            <span>MATRÍCULAS</span>
        </li>
        <li>
            <a href="../academic/matriculas/list_matriculas.php">
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
                        <i class="fas fa-building"></i>
                        <span>/ Sedes</span>
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

            <div class="content-wrapper">
                <?php if (isset($_GET['message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="headquarters-list">
                    <div class="list-header">
                        <h2>Lista de Sedes</h2>
                        <a href="create_headquarters.php" class="add-button">
                            <i class="fas fa-plus"></i> Agregar Sede
                        </a>
                    </div>

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sedes)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="fas fa-building"></i>
                                                <h3>No hay sedes registradas</h3>
                                                <p>Comience agregando una nueva sede</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($sedes as $sede): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sede['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($sede['direccion'] ?? 'No especificada'); ?></td>
                                            <td><?php echo htmlspecialchars($sede['telefono'] ?? 'No especificado'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $sede['estado']; ?>">
                                                    <?php echo ucfirst($sede['estado']); ?>
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <a href="edit_headquarters.php?id=<?php echo $sede['id']; ?>" 
                                                   class="btn-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if($sede['estado'] == 'activo'): ?>
                                                    <a href="javascript:void(0);" 
                                                       onclick="confirmarCambioEstado(<?php echo $sede['id']; ?>, 'inactivo')" 
                                                       class="btn-delete" title="Inhabilitar">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="javascript:void(0);" 
                                                       onclick="confirmarCambioEstado(<?php echo $sede['id']; ?>, 'activo')" 
                                                       class="btn-activate" title="Activar">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('current-time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });

        function confirmarCambioEstado(id, nuevoEstado) {
            let mensaje = nuevoEstado === 'inactivo' 
                ? '¿Está seguro que desea inhabilitar esta sede?' 
                : '¿Está seguro que desea reactivar esta sede?';
            
            if(confirm(mensaje)) {
                window.location.href = `toggle_headquarters_status.php?id=${id}&estado=${nuevoEstado}`;
            }
        }
    </script>
</body>
</html>