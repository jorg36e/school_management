<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'admin';

    try {
        if($role == 'profesor') {
            // Autenticación de profesor
            $stmt = $pdo->prepare("SELECT * FROM profesores WHERE usuario = ? AND estado = 'activo'");
            $stmt->execute([$usuario]);
            $profesor = $stmt->fetch();

            if ($profesor) {
                if (password_verify($password, $profesor['password'])) {
                    // Login exitoso
                    $_SESSION['profesor_id'] = $profesor['id'];
                    $_SESSION['profesor_nombre'] = $profesor['nombre'] . ' ' . $profesor['apellido'];
                    $_SESSION['rol'] = 'profesor';
                    header('Location: ../profesor/dashboard.php');
                    exit();
                } else {
                    error_log("Contraseña incorrecta para profesor: " . $usuario);
                    header('Location: profesor_login.php?error=1');
                    exit();
                }
            } else {
                error_log("Profesor no encontrado: " . $usuario);
                header('Location: profesor_login.php?error=1');
                exit();
            }
        } else {
            // Autenticación de administrador (código original)
            $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $admin = $stmt->fetch();

            if ($admin) {
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_nombre'] = $admin['nombre'];
                    $_SESSION['rol'] = 'admin';
                    header('Location: ../admin/dashboard.php');
                    exit();
                } else {
                    error_log("Contraseña incorrecta para usuario: " . $usuario);
                    header('Location: login.php?error=1');
                    exit();
                }
            } else {
                error_log("Usuario no encontrado: " . $usuario);
                header('Location: login.php?error=1');
                exit();
            }
        }
    } catch(PDOException $e) {
        error_log("Error de base de datos: " . $e->getMessage());
        header('Location: ' . ($role == 'profesor' ? 'profesor_login.php' : 'login.php') . '?error=2');
        exit();
    }
}
?>