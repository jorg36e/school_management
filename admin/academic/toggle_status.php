<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

// Verificar parámetros necesarios
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$nuevo_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Validar el estado
$estados_permitidos = ['Pendiente', 'En Revisión', 'Aprobado'];

if(!$id || !in_array($nuevo_estado, $estados_permitidos)) {
    header('Location: list_dba.php?error=Parámetros inválidos');
    exit();
}

try {
    // Verificar si la planeación existe
    $stmt = $pdo->prepare("SELECT estado FROM planeaciones WHERE id = ?");
    $stmt->execute([$id]);
    $planeacion = $stmt->fetch();

    if(!$planeacion) {
        header('Location: list_dba.php?error=Planeación no encontrada');
        exit();
    }

    // Actualizar el estado
    $stmt = $pdo->prepare("UPDATE planeaciones SET estado = ?, fecha_modificacion = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$nuevo_estado, $id]);

    // Registrar el cambio en el log de actividad
    $admin_id = $_SESSION['admin_id'];
    $log_sql = "INSERT INTO log_actividad (usuario_id, tipo_usuario, accion, detalles) 
                VALUES (?, 'admin', 'cambio_estado_planeacion', ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $detalles = "Cambio de estado en planeación ID: $id, Nuevo estado: $nuevo_estado";
    $log_stmt->execute([$admin_id, $detalles]);

    // Enviar notificación al profesor (opcional)
    $notif_sql = "INSERT INTO notificaciones (usuario_id, tipo, mensaje, estado) 
                  SELECT profesor_id, 'cambio_estado', ?, 'no_leido' 
                  FROM planeaciones WHERE id = ?";
    $mensaje = "El estado de tu planeación ha sido actualizado a: $nuevo_estado";
    $notif_stmt = $pdo->prepare($notif_sql);
    $notif_stmt->execute([$mensaje, $id]);

    header('Location: list_dba.php?message=Estado actualizado exitosamente');
    exit();

} catch(PDOException $e) {
    error_log("Error al cambiar estado de planeación: " . $e->getMessage());
    header('Location: list_dba.php?error=Error al actualizar el estado');
    exit();
}
?>