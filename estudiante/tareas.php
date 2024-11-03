<?php
session_start();
if(!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/estudiante_login.php');
    exit();
}

require_once '../config/database.php';

$estudiante = null;
$tareas = [];
$error_message = null;
$nombre_completo = '';

try {
    // Obtener información del estudiante
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            g.nombre as grupo_nombre
        FROM estudiantes e
        LEFT JOIN grupos g ON e.grupo_id = g.id
        WHERE e.id = ?
    ");
    
    if ($stmt->execute([$_SESSION['estudiante_id']])) {
        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($estudiante) {
            $nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
            
            // Obtener tareas del estudiante
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    a.nombre as asignatura_nombre,
                    p.nombre as profesor_nombre,
                    p.apellido as profesor_apellido
                FROM tareas t
                INNER JOIN asignaturas a ON t.asignatura_id = a.id
                INNER JOIN profesores p ON t.profesor_id = p.id
                WHERE t.grupo_id = ? 
                AND t.fecha_vencimiento >= CURDATE()
                ORDER BY t.fecha_vencimiento ASC
            ");
            $stmt->execute([$estudiante['grupo_id']]);
            $tareas_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener tareas pasadas
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    a.nombre as asignatura_nombre,
                    p.nombre as profesor_nombre,
                    p.apellido as profesor_apellido
                FROM tareas t
                INNER JOIN asignaturas a ON t.asignatura_id = a.id
                INNER JOIN profesores p ON t.profesor_id = p.id
                WHERE t.grupo_id = ? 
                AND t.fecha_vencimiento < CURDATE()
                ORDER BY t.fecha_vencimiento DESC
                LIMIT 10
            ");
            $stmt->execute([$estudiante['grupo_id']]);
            $tareas_pasadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            session_destroy();
            header('Location: ../auth/estudiante_login.php');
            exit();
        }
    }
} catch(Exception $e) {
    error_log("Error en tareas estudiante: " . $e->getMessage());
    $error_message = "Error al cargar las tareas";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .top-header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .tasks-content {
            padding: 20px;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .task-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            font-size: 1.1em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-counter {
            background: rgba(255,255,255,0.1);
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85em;
        }

        .task-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .task-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .task-item:last-child {
            border-bottom: none;
        }

        .task-item:hover {
            background: #f8fafc;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .task-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .task-subject {
            color: #3498db;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .task-meta {
            font-size: 0.85em;
            color: #64748b;
        }

        .task-date {
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.85em;
            color: #64748b;
        }

        .date-urgent {
            background: #fee2e2;
            color: #991b1b;
        }

        .date-warning {
            background: #fef9c3;
            color: #854d0e;
        }

        .date-normal {
            background: #dcfce7;
            color: #166534;
        }

        .task-description {
            color: #4b5563;
            font-size: 0.9em;
            margin: 10px 0;
        }

        .task-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 0.85em;
        }

        .task-teacher {
            color: #64748b;
        }

        .no-tasks {
            padding: 30px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 768px) {
            .tasks-grid {
                grid-template-columns: 1fr;
            }
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
                        <a href="tareas.php" class="active">
                            <i class="fas fa-tasks"></i>
                            <span>Tareas</span>
                        </a>
                    </li>
                    <li class="menu-section">
                        <span>MI CUENTA</span>
                    </li>
                    <li>
                        <a href="perfil.php">
                            <i class="fas fa-user"></i>
                            <span>Mi Perfil</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button id="sidebar-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <span>📝 / Tareas</span>
                </div>
                <div class="header-right">
                    <div class="clock">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="user-info">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($nombre_completo); ?> Estudiante</span>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <div class="tasks-content">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="tasks-grid">
                    <!-- Tareas Pendientes -->
                    <div class="task-section">
                        <div class="section-header">
                            <span>Tareas Pendientes</span>
                            <span class="task-counter"><?php echo count($tareas_pendientes); ?></span>
                        </div>
                        <?php if (empty($tareas_pendientes)): ?>
                            <div class="no-tasks">
                                <i class="fas fa-check-circle"></i>
                                No hay tareas pendientes
                            </div>
                        <?php else: ?>
                            <ul class="task-list">
                                <?php foreach ($tareas_pendientes as $tarea): ?>
                                    <li class="task-item">
                                        <div class="task-header">
                                            <div>
                                                <div class="task-title"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                                                <div class="task-subject"><?php echo htmlspecialchars($tarea['asignatura_nombre']); ?></div>
                                            </div>
                                            <?php
                                                $dias_restantes = (strtotime($tarea['fecha_vencimiento']) - time()) / (60 * 60 * 24);
                                                $clase_fecha = $dias_restantes <= 1 ? 'date-urgent' : 
                                                            ($dias_restantes <= 3 ? 'date-warning' : 'date-normal');
                                            ?>
                                            <div class="task-date <?php echo $clase_fecha; ?>">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo date('d/m/Y', strtotime($tarea['fecha_vencimiento'])); ?>
                                            </div>
                                        </div>
                                        <div class="task-description">
                                            <?php echo nl2br(htmlspecialchars($tarea['descripcion'])); ?>
                                        </div>
                                        <div class="task-footer">
                                            <span class="task-teacher">
                                                <i class="fas fa-user-tie"></i>
                                                Prof. <?php echo htmlspecialchars($tarea['profesor_nombre'] . ' ' . $tarea['profesor_apellido']); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Tareas Pasadas -->
                    <div class="task-section">
                        <div class="section-header">
                            <span>Tareas Pasadas</span>
                            <span class="task-counter"><?php echo count($tareas_pasadas); ?></span>
                        </div>
                        <?php if (empty($tareas_pasadas)): ?>
                            <div class="no-tasks">
                                <i class="fas fa-history"></i>
                                No hay tareas pasadas
                            </div>
                        <?php else: ?>
                            <ul class="task-list">
                                <?php foreach ($tareas_pasadas as $tarea): ?>
                                    <li class="task-item">
                                        <div class="task-header">
                                            <div>
                                                <div class="task-title"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
                                                <div class="task-subject"><?php echo htmlspecialchars($tarea['asignatura_nombre']); ?></div>
                                            </div>
                                            <div class="task-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo date('d/m/Y', strtotime($tarea['fecha_vencimiento'])); ?>
                                            </div>
                                        </div>
                                        <div class="task-footer">
                                            <span class="task-teacher">
                                                <i class="fas fa-user-tie"></i>
                                                Prof. <?php echo htmlspecialchars($tarea['profesor_nombre'] . ' ' . $tarea['profesor_apellido']); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
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

        // Animación para las tareas
        document.querySelectorAll('.task-item').forEach(task => {
            task.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            });

            task.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Actualizar contadores de tiempo restante
        function updateTimeCounters() {
            document.querySelectorAll('.task-date').forEach(dateElement => {
                const dateText = dateElement.textContent.trim();
                const taskDate = new Date(dateText.split('/').reverse().join('-'));
                const now = new Date();
                const diffDays = Math.ceil((taskDate - now) / (1000 * 60 * 60 * 24));

                if (diffDays <= 1) {
                    dateElement.classList.add('date-urgent');
                } else if (diffDays <= 3) {
                    dateElement.classList.add('date-warning');
                }
            });
        }

        updateTimeCounters();
        setInterval(updateTimeCounters, 60000); // Actualizar cada minuto
    </script>
</body>
</html>