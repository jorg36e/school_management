<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password']);
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $asignatura = trim($_POST['asignatura']);
        $sede = trim($_POST['sede']);
        $telefono = trim($_POST['telefono']);

        $stmt = $pdo->prepare("SELECT id FROM profesores WHERE usuario = ? OR email = ?");
        $stmt->execute([$usuario, $email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('El usuario o email ya existe en el sistema.');
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO profesores (usuario, password, nombre, apellido, email, asignatura, sede, telefono, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo')");
        
        if ($stmt->execute([$usuario, $password_hash, $nombre, $apellido, $email, $asignatura, $sede, $telefono])) {
            header('Location: list_teachers.php?message=Profesor agregado exitosamente');
            exit();
        } else {
            throw new Exception('Error al crear el profesor');
        }

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
    <title>Crear Profesor - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    
    <style>
        /* Estilos de la barra superior */
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
    color: #ecf0f1;
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

.breadcrumb {
    color: #ecf0f1;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
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

        /* Estilos del formulario rediseñado */
        .content-wrapper {
            padding: 20px;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }

        .create-form {
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
            overflow-y: auto;
        }

        .form-section {
            background: #f8fafc;
            padding: 15px;
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

        .required-field::after {
            content: '*';
            color: #e74c3c;
            margin-left: 4px;
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

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 15px;
            background: white;
            border-top: 1px solid #e2e8f0;
            position: sticky;
            bottom: 0;
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
            <!-- Barra superior -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="breadcrumb">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>/ Profesores / Crear Nuevo</span>
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

            <!-- Contenido principal -->
            <div class="content-wrapper">
                <div class="create-form">
                    <div class="form-header">
                        <h2>Crear Nuevo Profesor</h2>
                        <p>Complete la información del profesor para crear una nueva cuenta</p>
                    </div>

                    <form method="POST" action="">
                        <div class="form-grid">
                            <!-- Información de Cuenta -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user-shield"></i>
                                    Información de Cuenta
                                </h3>
                                
                                <div class="form-group">
                                    <label for="usuario" class="required-field">Usuario</label>
                                    <input type="text" id="usuario" name="usuario" required 
                                           class="form-control" 
                                           placeholder="Nombre de usuario">
                                </div>

                                <div class="form-group">
                                    <label for="password" class="required-field">Contraseña</label>
                                    <input type="password" id="password" name="password" required 
                                           class="form-control" 
                                           placeholder="Contraseña">
                                </div>

                                <div class="form-group">
                                    <label for="email" class="required-field">Email</label>
                                    <input type="email" id="email" name="email" required 
                                           class="form-control" 
                                           placeholder="correo@ejemplo.com">
                                </div>
                            </div>

                            <!-- Información Personal y Profesional -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user"></i>
                                    Información Personal y Profesional
                                </h3>
                                
                                <div class="form-group">
                                    <label for="nombre" class="required-field">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" required 
                                           class="form-control" 
                                           placeholder="Nombre del profesor">
                                </div>

                                <div class="form-group">
                                    <label for="apellido" class="required-field">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" required 
                                           class="form-control" 
                                           placeholder="Apellido del profesor">
                                </div>

                                <div class="form-group">
                                    <label for="asignatura" class="required-field">Asignatura</label>
                                    <input type="text" id="asignatura" name="asignatura" required
                                           class="form-control" 
                                           placeholder="Asignatura a impartir">
                                </div>

                                <div class="form-group">
                                    <label for="sede" class="required-field">Sede</label>
                                    <input type="text" id="sede" name="sede" required
                                           class="form-control" 
                                           placeholder="Sede del profesor">
                                </div>

                                <div class="form-group">
                                    <label for="telefono" class="required-field">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" required
                                           class="form-control" 
                                           placeholder="Número de contacto">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="list_teachers.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Profesor
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

        // Validación de formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const telefono = document.getElementById('telefono').value;

            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }

            if (!/^\d+$/.test(telefono)) {
                e.preventDefault();
                alert('El teléfono debe contener solo números');
                return;
            }
        });
    </script>
</body>
</html>