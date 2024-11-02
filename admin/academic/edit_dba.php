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

// Obtener datos de la planeación
try {
    $stmt = $pdo->prepare("SELECT * FROM planeaciones WHERE id = ?");
    $stmt->execute([$id]);
    $planeacion = $stmt->fetch();

    if(!$planeacion) {
        header('Location: list_dba.php?error=Planeación no encontrada');
        exit();
    }

    // Obtener lista de profesores
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM profesores WHERE estado = 'activo' ORDER BY nombre, apellido");
    $profesores = $stmt->fetchAll();

} catch(PDOException $e) {
    header('Location: list_dba.php?error=' . urlencode($e->getMessage()));
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $materia = trim($_POST['materia']);
        $grado = trim($_POST['grado']);
        $profesor_id = $_POST['profesor_id'];
        $estado = $_POST['estado'];
        
        // Inicializar variables para el archivo
        $archivo_nombre = $planeacion['archivo_url']; // Mantener el archivo actual por defecto
        $actualizar_archivo = false;
        
        // Verificar si se subió un nuevo archivo
        if ($_FILES['archivo']['size'] > 0) {
            $archivo = $_FILES['archivo'];
            $extensiones_permitidas = ['pdf', 'doc', 'docx'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensiones_permitidas)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten archivos PDF, DOC y DOCX.');
            }
            
            // Generar nombre único para el nuevo archivo
            $archivo_nombre = uniqid() . '_' . date('Ymd') . '.' . $extension;
            $ruta_destino = '../../uploads/dba/' . $archivo_nombre;
            
            // Mover el nuevo archivo
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                throw new Exception('Error al subir el archivo.');
            }
            
            $actualizar_archivo = true;
            
            // Eliminar archivo anterior si existe
            if ($planeacion['archivo_url'] && file_exists('../../uploads/dba/' . $planeacion['archivo_url'])) {
                unlink('../../uploads/dba/' . $planeacion['archivo_url']);
            }
        }
        
        // Actualizar en la base de datos
        $sql = "UPDATE planeaciones SET 
                titulo = ?, 
                descripcion = ?, 
                materia = ?, 
                grado = ?, 
                profesor_id = ?, 
                estado = ?";
        
        $params = [$titulo, $descripcion, $materia, $grado, $profesor_id, $estado];
        
        if ($actualizar_archivo) {
            $sql .= ", archivo_url = ?";
            $params[] = $archivo_nombre;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        header('Location: list_dba.php?message=Planeación actualizada exitosamente');
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Planeación DBA - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 800px;
            margin: 20px auto;
        }

        .form-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }

        .current-file {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .current-file i {
            color: #3498db;
        }

        .file-input-wrapper {
            margin-top: 10px;
        }

        .status-select {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            width: 100%;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-danger { background: #e74c3c; color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
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
                <div class="form-container">
                    <div class="form-header">
                        <h2>Editar Planeación DBA</h2>
                        <div class="status-badge status-<?php echo $planeacion['estado']; ?>">
                            <?php echo $planeacion['estado']; ?>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="titulo">Título</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" 
                                   value="<?php echo htmlspecialchars($planeacion['titulo']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" class="form-control"><?php 
                                echo htmlspecialchars($planeacion['descripcion']); 
                            ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="materia">Materia</label>
                            <input type="text" id="materia" name="materia" class="form-control" 
                                   value="<?php echo htmlspecialchars($planeacion['materia']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="grado">Grado</label>
                            <input type="text" id="grado" name="grado" class="form-control" 
                                   value="<?php echo htmlspecialchars($planeacion['grado']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="profesor_id">Profesor</label>
                            <select id="profesor_id" name="profesor_id" class="form-control" required>
                                <option value="">Seleccione un profesor</option>
                                <?php foreach($profesores as $profesor): ?>
                                    <option value="<?php echo $profesor['id']; ?>" 
                                            <?php echo $profesor['id'] == $planeacion['profesor_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" class="status-select">
                                <option value="Pendiente" <?php echo $planeacion['estado'] == 'Pendiente' ? 'selected' : ''; ?>>
                                    Pendiente
                                </option>
                                <option value="En Revisión" <?php echo $planeacion['estado'] == 'En Revisión' ? 'selected' : ''; ?>>
                                    En Revisión
                                </option>
                                <option value="Aprobado" <?php echo $planeacion['estado'] == 'Aprobado' ? 'selected' : ''; ?>>
                                    Aprobado
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Documento Actual</label>
                            <?php if ($planeacion['archivo_url']): ?>
                                <div class="current-file">
                                    <i class="fas fa-file-alt"></i>
                                    <span><?php echo htmlspecialchars($planeacion['archivo_url']); ?></span>
                                    <a href="../../uploads/dba/<?php echo urlencode($planeacion['archivo_url']); ?>" 
                                       target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i> Descargar
                                    </a>
                                </div>
                            <?php else: ?>
                                <p>No hay archivo adjunto</p>
                            <?php endif; ?>

                            <div class="file-input-wrapper">
                                <label for="archivo">Cambiar documento (opcional)</label>
                                <input type="file" id="archivo" name="archivo" accept=".pdf,.doc,.docx">
                                <small>Formatos permitidos: PDF, DOC, DOCX</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="list_dba.php" class="btn btn-secondary">
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
        // Mostrar nombre del archivo seleccionado
        document.getElementById('archivo').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                this.nextElementSibling.textContent = `Archivo seleccionado: ${fileName}`;
            }
        });
    </script>
</body>
</html>