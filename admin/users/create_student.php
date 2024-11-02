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
        // Datos de cuenta
        $usuario = trim($_POST['usuario']);
        $password = trim($_POST['password']);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Datos personales
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $documento_tipo = trim($_POST['documento_tipo']);
        $documento_numero = trim($_POST['documento_numero']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $genero = trim($_POST['genero']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);
        $email = trim($_POST['email']);
        $nombre_acudiente = trim($_POST['nombre_acudiente']);
        $telefono_acudiente = trim($_POST['telefono_acudiente']);
        $email_acudiente = trim($_POST['email_acudiente']);

        // Verificar si el usuario ya existe
        $stmt = $pdo->prepare("SELECT id FROM estudiantes WHERE usuario = ?");
        $stmt->execute([$usuario]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('El nombre de usuario ya existe');
        }

        // Insertar el nuevo estudiante
        $stmt = $pdo->prepare("
            INSERT INTO estudiantes (
                usuario, password, nombre, apellido, documento_tipo, 
                documento_numero, fecha_nacimiento, genero, direccion, 
                telefono, email, nombre_acudiente, telefono_acudiente, 
                email_acudiente, estado
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo'
            )
        ");
        
        if ($stmt->execute([
            $usuario, $password_hash, $nombre, $apellido, $documento_tipo,
            $documento_numero, $fecha_nacimiento, $genero, $direccion,
            $telefono, $email, $nombre_acudiente, $telefono_acudiente,
            $email_acudiente
        ])) {
            header('Location: list_students.php?message=Estudiante agregado exitosamente');
            exit();
        } else {
            throw new Exception('Error al crear el estudiante');
        }

    } catch(Exception $e) {
        header('Location: list_students.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Estudiante - Sistema Escolar</title>
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

        .btn-secondary {
            background: #95a5a6;
            color: white;
            text-decoration: none;
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
                        <a href="list_students.php" class="active">
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
                        <i class="fas fa-user-graduate"></i>
                        <span>/ Estudiantes / Crear Nuevo</span>
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
                <div class="create-form">
                    <div class="form-header">
                        <h2>Crear Nuevo Estudiante</h2>
                        <p>Complete la información del estudiante para crear un nuevo registro</p>
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
                            </div>

                            <!-- Información Personal -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user"></i>
                                    Información Personal
                                </h3>
                                
                                <div class="form-group">
                                    <label for="nombre" class="required-field">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" required 
                                           class="form-control" 
                                           placeholder="Nombre del estudiante">
                                </div>

                                <div class="form-group">
                                    <label for="apellido" class="required-field">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" required 
                                           class="form-control" 
                                           placeholder="Apellido del estudiante">
                                </div>

                                <div class="form-group">
                                    <label for="documento_tipo" class="required-field">Tipo de Documento</label>
                                    <select id="documento_tipo" name="documento_tipo" required class="form-control">
                                        <option value="">Seleccione tipo</option>
                                        <option value="TI">Tarjeta de Identidad</option>
                                        <option value="RC">Registro Civil</option>
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                        <option value="PASAPORTE">Pasaporte</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="documento_numero" class="required-field">Número de Documento</label>
                                    <input type="text" id="documento_numero" name="documento_numero" required 
                                           class="form-control" 
                                           placeholder="Número de documento">
                                </div>

                                <div class="form-group">
                                    <label for="fecha_nacimiento" class="required-field">Fecha de Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required 
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="genero" class="required-field">Género</label>
                                    <select id="genero" name="genero" required class="form-control">
                                        <option value="">Seleccione género</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                        <option value="O">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-address-card"></i>
                                    Información de Contacto
                                </h3>

                                <div class="form-group">
                                    <label for="direccion" class="required-field">Dirección</label>
                                    <input type="text" id="direccion" name="direccion" required 
                                           class="form-control" 
                                           placeholder="Dirección de residencia">
                                </div>

                                <div class="form-group">
                                    <label for="telefono" class="required-field">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" required 
                                           class="form-control" 
                                           placeholder="Número de contacto">
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" 
                                           class="form-control" 
                                           placeholder="correo@ejemplo.com">
                                </div>
                            </div>

                            <!-- Información del Acudiente -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user-friends"></i>
                                    Información del Acudiente
                                </h3>

                                <div class="form-group">
                                    <label for="nombre_acudiente" class="required-field">Nombre del Acudiente</label>
                                    <input type="text" id="nombre_acudiente" name="nombre_acudiente" required 
                                           class="form-control" 
                                           placeholder="Nombre completo del acudiente">
                                </div>

                                <div class="form-group">
                                    <label for="telefono_acudiente" class="required-field">Teléfono del Acudiente</label>
                                    <input type="tel" id="telefono_acudiente" name="telefono_acudiente" required 
                                           class="form-control" 
                                           placeholder="Teléfono del acudiente">
                                </div>

                                <div class="form-group">
                                    <label for="email_acudiente">Email del Acudiente</label>
                                    <input type="email" id="email_acudiente" name="email_acudiente" 
                                           class="form-control" 
                                           placeholder="correo.acudiente@ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="list_students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Estudiante
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
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
                        <a href="list_students.php" class="active">
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
                        <i class="fas fa-user-graduate"></i>
                        <span>/ Estudiantes / Crear Nuevo</span>
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
                <div class="create-form">
                    <div class="form-header">
                        <h2>Crear Nuevo Estudiante</h2>
                        <p>Complete la información del estudiante para crear un nuevo registro</p>
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
                            </div>

                            <!-- Información Personal -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user"></i>
                                    Información Personal
                                </h3>
                                
                                <div class="form-group">
                                    <label for="nombre" class="required-field">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" required 
                                           class="form-control" 
                                           placeholder="Nombre del estudiante">
                                </div>

                                <div class="form-group">
                                    <label for="apellido" class="required-field">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" required 
                                           class="form-control" 
                                           placeholder="Apellido del estudiante">
                                </div>

                                <div class="form-group">
                                    <label for="documento_tipo" class="required-field">Tipo de Documento</label>
                                    <select id="documento_tipo" name="documento_tipo" required class="form-control">
                                        <option value="">Seleccione tipo</option>
                                        <option value="TI">Tarjeta de Identidad</option>
                                        <option value="RC">Registro Civil</option>
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                        <option value="PASAPORTE">Pasaporte</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="documento_numero" class="required-field">Número de Documento</label>
                                    <input type="text" id="documento_numero" name="documento_numero" required 
                                           class="form-control" 
                                           placeholder="Número de documento">
                                </div>

                                <div class="form-group">
                                    <label for="fecha_nacimiento" class="required-field">Fecha de Nacimiento</label>
                                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required 
                                           class="form-control">
                                </div>

                                <div class="form-group">
                                    <label for="genero" class="required-field">Género</label>
                                    <select id="genero" name="genero" required class="form-control">
                                        <option value="">Seleccione género</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                        <option value="O">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Información de Contacto -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-address-card"></i>
                                    Información de Contacto
                                </h3>

                                <div class="form-group">
                                    <label for="direccion" class="required-field">Dirección</label>
                                    <input type="text" id="direccion" name="direccion" required 
                                           class="form-control" 
                                           placeholder="Dirección de residencia">
                                </div>

                                <div class="form-group">
                                    <label for="telefono" class="required-field">Teléfono</label>
                                    <input type="tel" id="telefono" name="telefono" required 
                                           class="form-control" 
                                           placeholder="Número de contacto">
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" 
                                           class="form-control" 
                                           placeholder="correo@ejemplo.com">
                                </div>
                            </div>

                            <!-- Información del Acudiente -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-user-friends"></i>
                                    Información del Acudiente
                                </h3>

                                <div class="form-group">
                                    <label for="nombre_acudiente" class="required-field">Nombre del Acudiente</label>
                                    <input type="text" id="nombre_acudiente" name="nombre_acudiente" required 
                                           class="form-control" 
                                           placeholder="Nombre completo del acudiente">
                                </div>

                                <div class="form-group">
                                    <label for="telefono_acudiente" class="required-field">Teléfono del Acudiente</label>
                                    <input type="tel" id="telefono_acudiente" name="telefono_acudiente" required 
                                           class="form-control" 
                                           placeholder="Teléfono del acudiente">
                                </div>

                                <div class="form-group">
                                    <label for="email_acudiente">Email del Acudiente</label>
                                    <input type="email" id="email_acudiente" name="email_acudiente" 
                                           class="form-control" 
                                           placeholder="correo.acudiente@ejemplo.com">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="list_students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Estudiante
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>