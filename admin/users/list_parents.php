<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

try {
    // Consulta base para padres de familia
    $sql = "SELECT * FROM padres_familia ORDER BY nombre ASC";
    $stmt = $pdo->query($sql);
    $padres = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener la lista de padres: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padres de Familia - Sistema Escolar</title>
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

        .status-activo { background: #e8f5e9; color: #2e7d32; }
        .status-inactivo { background: #ffebee; color: #c62828; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 500;
            color: #2c3e50;
        }

        .search-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-section input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .search-section select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9em;
        }

        .search-section button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-view, .btn-edit, .btn-delete {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: transform 0.2s;
        }

        .btn-view { background: #3498db; }
        .btn-edit { background: #f39c12; }
        .btn-delete { background: #e74c3c; }

        .btn-view:hover, .btn-edit:hover, .btn-delete:hover {
            transform: translateY(-2px);
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
                        <a href="list_parents.php" class="active">
                            <i class="fas fa-users"></i>
                            <span>Padres de Familia</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>SEDES</span>
                    </li>
                    <li>
                        <a href="list_headquarters.php">
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
                        <i class="fas fa-users"></i>
                        <span>/ Padres de Familia</span>
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
                        <span>Administrador</span>
                        <span>Administrador</span>
                    </div>
                    <a href="../../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="list-header">
                    <h2>Lista de Padres de Familia</h2>
                    <a href="create_parent.php" class="add-button">
                        <i class="fas fa-plus"></i> Nuevo Padre de Familia
                    </a>
                </div>

                <div class="search-section">
                    <select class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="inactivo">Inactivos</option>
                    </select>
                    <input type="text" placeholder="Buscar padre de familia..." class="search-input">
                    <button type="button">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Estudiantes</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($padres)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">
                                    No se encontraron padres de familia
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($padres as $padre): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($padre['documento_tipo'] ?? '') . ': ' . htmlspecialchars($padre['documento_numero'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($padre['telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($padre['email']); ?></td>
                                    <td><?php echo htmlspecialchars($padre['estudiantes_asociados'] ?? '0'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($padre['estado']); ?>">
                                            <?php echo ucfirst($padre['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="view_parent.php?id=<?php echo $padre['id']; ?>" 
                                           class="btn-view" title="Ver">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_parent.php?id=<?php echo $padre['id']; ?>" 
                                           class="btn-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" 
                                           onclick="confirmarEliminacion(<?php echo $padre['id']; ?>)"
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

        function confirmarEliminacion(id) {
            if(confirm('¿Está seguro que desif(confirm('¿Está seguro que desea eliminar este padre de familia?')) {
               window.location.href = `delete_parent.php?id=${id}`;
           }
       }

       // Funcionalidad de búsqueda y filtrado
       document.querySelector('.search-input').addEventListener('input', function(e) {
           const searchTerm = e.target.value.toLowerCase();
           filterTable(searchTerm);
       });

       document.querySelector('.filter-select').addEventListener('change', function(e) {
           const filterValue = e.target.value.toLowerCase();
           filterTable(document.querySelector('.search-input').value.toLowerCase(), filterValue);
       });

       function filterTable(searchTerm = '', filterValue = '') {
           const rows = document.querySelectorAll('tbody tr');
           
           rows.forEach(row => {
               const text = row.textContent.toLowerCase();
               const status = row.querySelector('.status-badge')?.textContent.toLowerCase() || '';
               
               const matchesSearch = searchTerm === '' || text.includes(searchTerm);
               const matchesFilter = filterValue === '' || status.includes(filterValue);
               
               row.style.display = matchesSearch && matchesFilter ? '' : 'none';
           });
       }
   </script>
</body>
</html>
