<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Obtener los datos del profesor
if(isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM profesores WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $profesor = $stmt->fetch();

        if(!$profesor) {
            header('Location: list_teachers.php?error=Profesor no encontrado');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: list_teachers.php?error=Error al obtener datos del profesor');
        exit();
    }
}

// Procesar el formulario de edición
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id = $_POST['id'];
        $usuario = trim($_POST['usuario']);
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $especialidad = trim($_POST['especialidad']);
        $sede = trim($_POST['sede']);
        $telefono = trim($_POST['telefono']);
        $password = trim($_POST['password']);

        // Verificar si el email ya existe para otro profesor
        $stmt = $pdo->prepare("SELECT id FROM profesores WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if($stmt->rowCount() > 0) {
            throw new Exception('El email ya está registrado para otro profesor.');
        }

        // Verificar si el usuario ya existe para otro profesor
        $stmt = $pdo->prepare("SELECT id FROM profesores WHERE usuario = ? AND id != ?");
        $stmt->execute([$usuario, $id]);
        if($stmt->rowCount() > 0) {
            throw new Exception('El nombre de usuario ya está registrado.');
        }

        // Preparar la consulta base
        $sql = "UPDATE profesores SET 
                usuario = ?,
                nombre = ?, 
                apellido = ?, 
                email = ?, 
                especialidad = ?,
                sede = ?, 
                telefono = ?";
        $params = [$usuario, $nombre, $apellido, $email, $especialidad, $sede, $telefono];

        // Si se proporcionó una nueva contraseña, actualizarla
        if(!empty($password)) {
            $sql .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        // Ejecutar la actualización
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header('Location: list_teachers.php?message=Profesor actualizado exitosamente');
        exit();

    } catch(Exception $e) {
        header('Location: list_teachers.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Profesor - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Estilos para la barra superior */
        .top-bar {
            background: #2c3e50;
            color: white;
            padding: 0.8rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 25px;
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
            color: #fff;
        }

        .user-role {
            font-size: 0.8rem;
            color: #bdc3c7;
        }

        /* Estilos para el formulario */
        .content-wrapper {
            padding: 20px;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }

        .edit-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }

        .form-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-header h2 {
            color: #2c3e50;
            font-size: 1.5em;
            margin-bottom: 5px;
        }

        .form-header p {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 15px;
            flex: 1;
        }

        .form-section {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .section-title {
            color: #2c3e50;
            font-size: 1.1em;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3498db;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #3498db;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #34495e;
            font-weight: 500;
            font-size: 0.9em;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .password-info {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-1px);
        }

        .breadcrumb {
            color: #ecf0f1;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
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
                        <a href="list_teachers.php" class="active">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Profesores</span>
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
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>/ Profesores / Editar Profesor</span>
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
                    </div>
                    <a href="../../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="edit-form">
                    <div class="form-header">
                        <h2>Editar Profesor</h2>
                        <p>Actualice la información del profesor</p>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $profesor['id']; ?>">
                        
                        <div class="form-grid">
                            <!-- Información Personal -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user"></i>
                                    Información Personal
                                </h3>

                                <div class="form-group">
                                    <label for="usuario">Nombre de Usuario</label>
                                    <input type="text" id="usuario" name="usuario" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['usuario']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nombre">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['nombre']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="apellido">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['apellido']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="password">Nueva Contraseña</label>
                                    <input type="password" id="password" name="password" class="form-control">
                                    <p class="password-info">Dejar en blanco para mantener la contraseña actual</p>
                                </div>
                            </div>

                            <!-- Información Profesional -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-briefcase"></i>
                                    Información Profesional
                                </h3>
                                
                                <div class="form-group">
                                    <label for="especialidad">Asignatura</label>
                                    <input type="text" id="especialidad" name="especialidad" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['especialidad']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="sede">Sede</label>
                                    <input type="text" id="sede" name="sede" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['sede']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" class="form-control" 
                                           value="<?php echo htmlspecialchars($profesor['telefono']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="list_teachers.php" class="btn btn-secondary">
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

        // Validación del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const telefono = document.getElementById('telefono').value;
            const password = document.getElementById('password').value;

            // Validar formato de teléfono (solo números)
            if (!/^\d+$/.test(telefono)) {
                e.preventDefault();
                alert('El teléfono debe contener solo números');
                return;
            }

            // Validar contraseña si se está cambiando
            if (password && password.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return;
            }
        });
    </script>
</body>
</html>