<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Obtener ID de la planeación
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if(!$id) {
    header('Location: list_dba.php?error=ID de planeación no válido');
    exit();
}

try {
    // Obtener datos de la planeación
    $sql = "SELECT p.*, pr.nombre as profesor_nombre, pr.apellido as profesor_apellido 
            FROM planeaciones p 
            INNER JOIN profesores pr ON p.profesor_id = pr.id 
            WHERE p.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $planeacion = $stmt->fetch();

    if(!$planeacion) {
        header('Location: list_dba.php?error=Planeación no encontrada');
        exit();
    }

} catch(PDOException $e) {
    header('Location: list_dba.php?error=' . urlencode($e->getMessage()));
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Planeación DBA - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .view-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
        }

        .view-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .detail-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #34495e;
        }

        .file-preview {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-preview i {
            font-size: 24px;
            color: #3498db;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary { background: #3498db; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #27ae60; color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status-timeline {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .timeline-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .timeline-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-Pendiente .timeline-dot { background: #f39c12; }
        .status-EnRevisión .timeline-dot { background: #3498db; }
        .status-Aprobado .timeline-dot { background: #27ae60; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include_once '../includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Top Bar -->
            <?php include_once '../includes/topbar.php'; ?>

            <div class="content-wrapper">
                <div class="view-container">
                    <div class="view-header">
                        <h2><?php echo htmlspecialchars($planeacion['titulo']); ?></h2>
                        <span class="status-badge status-<?php echo $planeacion['estado']; ?>">
                            <?php echo $planeacion['estado']; ?>
                        </span>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Materia y Grado</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($planeacion['materia']); ?> - 
                            <?php echo htmlspecialchars($planeacion['grado']); ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Profesor</div>
                        <div class="detail-value">
                            <?php echo htmlspecialchars($planeacion['profesor_nombre'] . ' ' . $planeacion['profesor_apellido']); ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Descripción</div>
                        <div class="detail-value">
                            <?php echo nl2br(htmlspecialchars($planeacion['descripcion'])); ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Fechas</div>
                        <div class="detail-value">
                            <p><strong>Creación:</strong> <?php echo date('d/m/Y H:i', strtotime($planeacion['fecha_creacion'])); ?></p>
                            <?php if($planeacion['fecha_modificacion']): ?>
                                <p><strong>Última modificación:</strong> <?php echo date('d/m/Y H:i', strtotime($planeacion['fecha_modificacion'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if($planeacion['archivo_url']): ?>
                        <div class="file-preview">
                            <i class="fas fa-file-alt"></i>
                            <span><?php echo htmlspecialchars($planeacion['archivo_url']); ?></span>
                            <a href="../../uploads/dba/<?php echo urlencode($planeacion['archivo_url']); ?>" 
                               target="_blank" class="btn btn-primary">
                                <i class="fas fa-download"></i> Descargar
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <a href="list_dba.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="edit_dba.php?id=<?php echo $planeacion['id']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <?php if($planeacion['estado'] !== 'Aprobado'): ?>
                            <a href="toggle_status.php?id=<?php echo $planeacion['id']; ?>&estado=Aprobado" 
                               class="btn btn-success"
                               onclick="return confirm('¿Está seguro que desea aprobar esta planeación?')">
                                <i class="fas fa-check"></i> Aprobar
                            </a>
                        <?php endif; ?>
                        <a href="javascript:void(0);" 
                           onclick="confirmarEliminacion(<?php echo $planeacion['id']; ?>)" 
                           class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function confirmarEliminacion(id) {
            if(confirm('¿Está seguro que desea eliminar esta planeación?')) {
                window.location.href = `delete_dba.php?id=${id}`;
            }
        }
    </script>
</body>
</html>