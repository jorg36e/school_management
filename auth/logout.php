<?php
session_start();

// Detectar el tipo de usuario por la sesión
$isProfesor = isset($_SESSION['profesor_id']);
$isPadre = isset($_SESSION['padre_id']);

// Destruir la sesión
session_destroy();

// Redirigir según el tipo de usuario
if($isProfesor) {
    header('Location: profesor_login.php');
} elseif($isPadre) {
    header('Location: padre_login.php');
} else {
    header('Location: /school_management/auth/login.php');
}
exit();
?>