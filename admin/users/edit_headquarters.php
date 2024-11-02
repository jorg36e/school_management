<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Obtener los datos de la sede
if(isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sedes WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $sede = $stmt->fetch();

        if(!$sede) {
            header('Location: list_headquarters.php?error=Sede no encontrada');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: list_headquarters.php?error=Error al obtener datos de la sede');
        exit();
    }
}

// Procesar el formulario de edición
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);

        // Verificar si el nombre ya existe para otra sede
        $stmt = $pdo->prepare("SELECT id FROM sedes WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if($stmt->rowCount() > 0) {
            throw new Exception('Ya existe otra sede con este nombre.');
        }

        // Actualizar sede
        $sql = "UPDATE sedes SET nombre = ?, direccion = ?, telefono = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nombre, $direccion, $telefono, $id])) {
            header('Location: list_headquarters.php?message=Sede actualizada exitosamente');
            exit();
        } else {
            throw new Exception('Error al actualizar la sede');
        }

    } catch(Exception $e) {
        header('Location: list_headquarters.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Sede - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .content-wrapper {
            padding: 20px;
        }

        .edit-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #2c3e50;
            font-size: 1.8em;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95em;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border: none;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
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
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-bar">
                <button id="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <span>Administrador - <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></span>
                    <a href="../../auth/logout.php" class="logout-btn" title="Cerrar Sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="edit-form">
                    <div class="form-header">
                        <h2>Editar Sede</h2>
                        <p>Actualice la información de la sede</p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $sede['id']; ?>">
                        
                        <div class="form-group">
                            <label for="nombre">Nombre de la Sede:</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" 
                                   required value="<?php echo htmlspecialchars($sede['nombre']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="direccion">Dirección:</label>
                            <input type="text" id="direccion" name="direccion" class="form-control" 
                                   value="<?php echo htmlspecialchars($sede['direccion']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="telefono">Teléfono:</label>
                            <input type="text" id="telefono" name="telefono" class="form-control" 
                                   value="<?php echo htmlspecialchars($sede['telefono']); ?>">
                        </div>

                        <div class="form-actions">
                            <a href="list_headquarters.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });
    </script>
</body>
</html>