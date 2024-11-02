<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Obtener lista de profesores para el select
try {
    $stmt = $pdo->query("SELECT id, nombre, apellido FROM profesores WHERE estado = 'activo' ORDER BY nombre, apellido");
    $profesores = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error al obtener la lista de profesores: " . $e->getMessage();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $materia = trim($_POST['materia']);
        $grado = trim($_POST['grado']);
        $profesor_id = $_POST['profesor_id'];
        
        // Validar archivo
        $archivo = $_FILES['archivo'];
        $archivo_nombre = '';
        
        if ($archivo['error'] == 0) {
            $extensiones_permitidas = ['pdf', 'doc', 'docx'];
            $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensiones_permitidas)) {
                throw new Exception('Tipo de archivo no permitido. Solo se permiten archivos PDF, DOC y DOCX.');
            }
            
            // Generar nombre único para el archivo
            $archivo_nombre = uniqid() . '_' . date('Ymd') . '.' . $extension;
            $ruta_destino = '../../uploads/dba/' . $archivo_nombre;
            
            // Crear directorio si no existe
            if (!file_exists('../../uploads/dba/')) {
                mkdir('../../uploads/dba/', 0777, true);
            }
            
            // Mover archivo
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
                throw new Exception('Error al subir el archivo.');
            }
        }
        
        // Insertar en la base de datos
        $sql = "INSERT INTO planeaciones (titulo, descripcion, materia, grado, profesor_id, archivo_url, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pendiente')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$titulo, $descripcion, $materia, $grado, $profesor_id, $archivo_nombre]);
        
        header('Location: list_dba.php?message=Planeación creada exitosamente');
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
    <title>Nueva Planeación DBA - Sistema Escolar</title>
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
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

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #b91c1c;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            display: none;
        }

        .file-input-button {
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            background: #f8f9fa;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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
                        <h2>Nueva Planeación DBA</h2>
                    </div>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="titulo">Título</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="materia">Materia</label>
                            <input type="text" id="materia" name="materia" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="grado">Grado</label>
                            <input type="text" id="grado" name="grado" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="profesor_id">Profesor</label>
                            <select id="profesor_id" name="profesor_id" class="form-control" required>
                                <option value="">Seleccione un profesor</option>
                                <?php foreach($profesores as $profesor): ?>
                                    <option value="<?php echo $profesor['id']; ?>">
                                        <?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="archivo">Documento</label>
                            <div class="file-input-wrapper">
                                <label for="archivo" class="file-input-button">
                                    <i class="fas fa-upload"></i>
                                    <span>Seleccionar archivo</span>
                                </label>
                                <input type="file" id="archivo" name="archivo" accept=".pdf,.doc,.docx" required>
                            </div>
                            <small>Formatos permitidos: PDF, DOC, DOCX</small>
                            <div id="archivo-seleccionado"></div>
                        </div>

                        <div class="form-actions">
                            <a href="list_dba.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar
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
            document.getElementById('archivo-seleccionado').textContent = fileName || '';
        });
    </script>
</body>
</html>