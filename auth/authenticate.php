<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'admin';

    try {
        switch($role) {
            case 'estudiante':
                // Autenticación de estudiante
                $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE usuario = ? AND estado = 'activo'");
                $stmt->execute([$usuario]);
                $estudiante = $stmt->fetch();

                if ($estudiante && password_verify($password, $estudiante['password'])) {
                    // Login exitoso
                    $_SESSION['estudiante_id'] = $estudiante['id'];
                    $_SESSION['estudiante_nombre'] = $estudiante['nombre'] . ' ' . $estudiante['apellido'];
                    $_SESSION['rol'] = 'estudiante';
                    header('Location: ../estudiante/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login estudiante: " . $usuario);
                    header('Location: student_login.php?error=1');
                    exit();
                }
                break;

            case 'profesor':
                // Autenticación de profesor
                $stmt = $pdo->prepare("SELECT * FROM profesores WHERE usuario = ? AND estado = 'activo'");
                $stmt->execute([$usuario]);
                $profesor = $stmt->fetch();

                if ($profesor && password_verify($password, $profesor['password'])) {
                    // Login exitoso
                    $_SESSION['profesor_id'] = $profesor['id'];
                    $_SESSION['profesor_nombre'] = $profesor['nombre'] . ' ' . $profesor['apellido'];
                    $_SESSION['rol'] = 'profesor';
                    header('Location: ../profesor/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login profesor: " . $usuario);
                    header('Location: profesor_login.php?error=1');
                    exit();
                }
                break;

            case 'padre':
                // Autenticación de padre de familia / acudiente
                $stmt = $pdo->prepare("SELECT * FROM padres_familia WHERE usuario = ? AND estado = 'activo'");
                $stmt->execute([$usuario]);
                $padre = $stmt->fetch();

                if ($padre && !empty($padre['password']) && password_verify($password, $padre['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['padre_id'] = $padre['id'];
                    $_SESSION['padre_nombre'] = $padre['nombre'] . ' ' . $padre['apellido'];
                    $_SESSION['rol'] = 'padre';
                    header('Location: ../padre/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login padre: " . $usuario);
                    header('Location: padre_login.php?error=1');
                    exit();
                }
                break;

            default:
                // Autenticación de administrador (código original)
                $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
                $stmt->execute([$usuario]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_nombre'] = $admin['nombre'];
                    $_SESSION['rol'] = 'admin';
                    header('Location: ../admin/dashboard.php');
                    exit();
                } else {
                    error_log("Error de login admin: " . $usuario);
                    header('Location: login.php?error=1');
                    exit();
                }
                break;
        }
    } catch(PDOException $e) {
        error_log("Error de base de datos en login: " . $e->getMessage());
        $paginas_login = ['estudiante' => 'student_login.php', 'profesor' => 'profesor_login.php', 'padre' => 'padre_login.php'];
        header('Location: ' . ($paginas_login[$role] ?? 'login.php') . '?error=2');
        exit();
    }
}

// Si alguien intenta acceder directamente a este archivo
header('Location: login.php');
exit();
?>