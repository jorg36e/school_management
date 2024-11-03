<?php
session_start();
if(!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/estudiante_login.php');
    exit();
}

require_once '../config/database.php';

$estudiante = null;
$acudiente = null;
$error_message = null;

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            g.nombre as grupo_nombre,
            s.nombre as sede_nombre
        FROM estudiantes e
        LEFT JOIN grupos g ON e.grupo_id = g.id
        LEFT JOIN sedes s ON e.sede_id = s.id
        WHERE e.id = ?
    ");
    
    if ($stmt->execute([$_SESSION['estudiante_id']])) {
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($estudiante) {
            $stmt = $pdo->prepare("
                SELECT * FROM acudientes 
                WHERE estudiante_id = ?
            ");
            $stmt->execute([$_SESSION['estudiante_id']]);
            $acudiente = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            session_destroy();
            header('Location: ../auth/estudiante_login.php');
            exit();
        }
    }
} catch(Exception $e) {
    error_log("Error en perfil estudiante: " . $e->getMessage());
    $error_message = "Error al cargar la información del perfil";
}

function mostrar_dato($array, $key, $default = 'No registrado') {
    return isset($array[$key]) && !empty($array[$key]) ? htmlspecialchars($array[$key]) : $default;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .profile-content {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .profile-header {
            background: #2c3e50;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
        }
        .profile-name {
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        .profile-role {
            opacity: 0.9;
            font-size: 0.9em;
        }
        .profile-body {
            padding: 20px;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-section h3 {
            color: #2c3e50;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .info-item {
            margin-bottom: 15px;
        }
        .info-label {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
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
                        <a href="dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>ACADÉMICO</span>
                    </li>
                    <li>
                        <a href="calificaciones.php">
                            <i class="fas fa-star"></i>
                            <span>Calificaciones</span>
                        </a>
                    </li>
                    <li>
                        <a href="asistencia.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Asistencia</span>
                        </a>
                    </li>
                    <li>
                        <a href="horario.php">
                            <i class="fas fa-clock"></i>
                            <span>Horario</span>
                        </a>
                    </li>
                    <li>
                        <a href="tareas.php">
                            <i class="fas fa-tasks"></i>
                            <span>Tareas</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>MI CUENTA</span>
                    </li>
                    <li>
                        <a href="perfil.php" class="active">
                            <i class="fas fa-user"></i>
                            <span>Mi Perfil</span>
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
                        <i class="fas fa-user"></i>
                        <span>/ Mi Perfil</span>
                    </div>
                </div>
                <div class="top-bar-right">
                    <div class="top-bar-time">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="user-details">
                            <span class="user-name"><?php echo mostrar_dato($estudiante, 'nombre') . ' ' . mostrar_dato($estudiante, 'apellido'); ?></span>
                            <span class="user-role">Estudiante</span>
                        </div>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="profile-content">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h2 class="profile-name">
                            <?php echo mostrar_dato($estudiante, 'nombre') . ' ' . mostrar_dato($estudiante, 'apellido'); ?>
                        </h2>
                        <div class="profile-role">
                            <?php echo mostrar_dato($estudiante, 'grupo_nombre', 'Sin grupo asignado'); ?> - 
                            <?php echo mostrar_dato($estudiante, 'sede_nombre', 'Sin sede asignada'); ?>
                        </div>
                    </div>

                    <div class="profile-body">
                        <div class="info-section">
                            <h3>Información Personal</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Estado</div>
                                    <div class="info-value">
                                        <span class="badge <?php echo mostrar_dato($estudiante, 'estado') == 'activo' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst(mostrar_dato($estudiante, 'estado', 'indefinido')); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Tipo de Documento</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($estudiante, 'tipo_documento'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Número de Documento</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($estudiante, 'documento'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Teléfono</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($estudiante, 'telefono'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Dirección</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($estudiante, 'direccion'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($estudiante, 'email'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($acudiente): ?>
                        <div class="info-section">
                            <h3>Información del Acudiente</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Nombre del Acudiente</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($acudiente, 'nombre'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Teléfono del Acudiente</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($acudiente, 'telefono'); ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email del Acudiente</div>
                                    <div class="info-value">
                                        <?php echo mostrar_dato($acudiente, 'email'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            const options = { 
                hour: 'numeric', 
                minute: 'numeric',
                second: 'numeric',
                hour12: true 
            };
            const timeString = now.toLocaleTimeString('es-ES', options);
            document.getElementById('current-time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);

        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });

        function handleResponsive() {
            if (window.innerWidth <= 768) {
                document.querySelector('.admin-container').classList.add('sidebar-collapsed');
            } else {
                document.querySelector('.admin-container').classList.remove('sidebar-collapsed');
            }
        }

        window.addEventListener('resize', handleResponsive);
        handleResponsive();

        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            if (!confirm('¿Está seguro que desea cerrar sesión?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>