<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

if(isset($_GET['id']) && isset($_GET['estado'])) {
    try {
        $id = $_GET['id'];
        $estado = $_GET['estado'];
        
        // Validar que el estado sea válido
        if(!in_array($estado, ['activo', 'inactivo'])) {
            throw new Exception('Estado no válido');
        }

        // Verificar si la sede existe
        $stmt = $pdo->prepare("SELECT id FROM sedes WHERE id = ?");
        $stmt->execute([$id]);
        if($stmt->rowCount() == 0) {
            throw new Exception('La sede no existe');
        }
        
        // Actualizar el estado de la sede
        $stmt = $pdo->prepare("UPDATE sedes SET estado = ? WHERE id = ?");
        if($stmt->execute([$estado, $id])) {
            $mensaje = $estado === 'activo' ? 'Sede activada exitosamente' : 'Sede desactivada exitosamente';
            header("Location: list_headquarters.php?message=" . urlencode($mensaje));
        } else {
            throw new Exception('Error al actualizar el estado de la sede');
        }
        
    } catch(Exception $e) {
        header('Location: list_headquarters.php?error=' . urlencode($e->getMessage()));
    }
    exit();
}

header('Location: list_headquarters.php');
exit();
?>