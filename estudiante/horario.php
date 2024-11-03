<?php
session_start();
if(!isset($_SESSION['estudiante_id'])) {
    header('Location: ../auth/estudiante_login.php');
    exit();
}

require_once '../config/database.php';

try {
    $stmt = $pdo->prepare("SELECT nombre, apellido, grupo_id FROM estudiantes WHERE id = ?");
    $stmt->execute([$_SESSION['estudiante_id']]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        session_destroy();
        header('Location: ../auth/estudiante_login.php');
        exit();
    }

    $nombre_completo = $estudiante['nombre'] . ' ' . $estudiante['apellido'];

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horario - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .top-bar {
            background: #1e293b;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
        }

        .right-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .time {
            color: #fff;
        }

        .user-info {
            background: #3b82f6;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
        }

        .content {
            padding: 20px;
        }

        .title {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .no-group {
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="left-section">
            <button class="menu-toggle">☰</button>
            / Horario
        </div>
        <div class="right-section">
            <span class="time" id="current-time"></span>
            <div class="user-info">
                <?php echo htmlspecialchars($nombre_completo); ?> Estudiante
            </div>
            <a href="../auth/logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
    </div>

    <div class="content">
        <h1 class="title">Mi Horario de Clases</h1>
        <?php if (!isset($estudiante['grupo_id'])): ?>
            <p class="no-group">No tienes un grupo asignado actualmente.</p>
        <?php endif; ?>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            minutes = minutes < 10 ? '0' + minutes : minutes;
            const timeString = hours + ':' + minutes + ' a.m.';
            document.getElementById('current-time').textContent = timeString;
        }
        
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>