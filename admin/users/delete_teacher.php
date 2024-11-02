<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';

if(isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("UPDATE profesores SET estado = 'inactivo' WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: list_teachers.php?deleted=true');
        exit();
    } catch(PDOException $e) {
        header('Location: list_teachers.php?error=true');
        exit();
    }
}

header('Location: list_teachers.php');
exit();
?>