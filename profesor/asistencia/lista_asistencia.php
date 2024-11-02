<?php
session_start();
if (!isset($_SESSION['profesor_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia - Sistema Escolar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        /* Estilos adicionales para personalizar la interfaz */
        .content-wrapper {
            padding: 20px;
        }
        .page-header h2 {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 20px;
        }
        /* Sidebar styling */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #ecf0f1;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            padding-top: 20px;
        }
        .sidebar .sidebar-header {
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .sidebar .sidebar-header .logo i {
            font-size: 1.5em;
            margin-right: 10px;
        }
        .sidebar-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-nav ul li {
            margin-bottom: 10px;
        }
        .sidebar-nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
            border-radius: 4px;
        }
        .sidebar-nav ul li a:hover, .sidebar-nav ul li a.active {
            background-color: #3498db;
        }
        .sidebar-nav ul li a i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        /* Table styling */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .attendance-table th, .attendance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .attendance-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .btn-mark {
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 0.9em;
            color: #fff;
        }
        .btn-mark.present { background: #27ae60; }
        .btn-mark.absent { background: #e74c3c; }
        .btn-mark:hover { opacity: 0.9; }
        .status-tag {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .status-present { background: #e8f5e9; color: #2e7d32; }
        .status-absent { background: #ffebee; color: #c62828; }
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
                    <li><a href="../dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="../grupos/mis_grupos.php"><i class="fas fa-users"></i><span>Mis Grupos</span></a></li>
                    <li><a href="../calificaciones/lista_calificaciones.php"><i class="fas fa-star"></i><span>Calificaciones</span></a></li>
                    <li><a href="lista_asistencia.php" class="active"><i class="fas fa-clipboard-list"></i><span>Asistencia</span></a></li>
                    <li><a href="../planeacion/mis_planeaciones.php"><i class="fas fa-book"></i><span>Planeaciones</span></a></li>
                    <li><a href="../recursos/mis_recursos.php"><i class="fas fa-file-alt"></i><span>Recursos</span></a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" style="margin-left: 250px;">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <div class="breadcrumb">
                        <i class="fas fa-clipboard-list"></i>
                        <span> / Asistencia</span>
                    </div>
                </div>
                <div class="top-bar-right">
                    <div class="user-info">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <span>Profesor</span>
                    </div>
                    <a href="../../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span>
                    </a>
                </div>
            </header>

            <!-- Contenido principal -->
            <div class="content-wrapper">
                <div class="page-header">
                    <h2>Lista de Asistencia</h2>
                </div>

                <!-- Tabla de asistencia -->
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Ejemplo de datos de estudiantes -->
                        <tr>
                            <td>Juan Pérez</td>
                            <td><span class="status-tag status-present">Presente</span></td>
                            <td>
                                <button class="btn-mark present" onclick="marcarAsistencia(this, 'absent')">Marcar Ausente</button>
                            </td>
                        </tr>
                        <tr>
                            <td>Maria García</td>
                            <td><span class="status-tag status-absent">Ausente</span></td>
                            <td>
                                <button class="btn-mark absent" onclick="marcarAsistencia(this, 'present')">Marcar Presente</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.admin-container').classList.toggle('sidebar-collapsed');
        });

        function marcarAsistencia(button, estado) {
            const row = button.parentElement.parentElement;
            const statusCell = row.querySelector('.status-tag');

            if (estado === 'present') {
                statusCell.classList.remove('status-absent');
                statusCell.classList.add('status-present');
                statusCell.textContent = 'Presente';
                button.classList.remove('absent');
                button.classList.add('present');
                button.textContent = 'Marcar Ausente';
                button.setAttribute('onclick', "marcarAsistencia(this, 'absent')");
            } else {
                statusCell.classList.remove('status-present');
                statusCell.classList.add('status-absent');
                statusCell.textContent = 'Ausente';
                button.classList.remove('present');
                button.classList.add('absent');
                button.textContent = 'Marcar Presente';
                button.setAttribute('onclick', "marcarAsistencia(this, 'present')");
            }
        }
    </script>
</body>
</html>
