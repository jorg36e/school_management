<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Prueba de Conexión a Base de Datos</h2>";

// Incluir el archivo de configuración
require_once 'config/database.php';

try {
    // Probar la consulta
    $stmt = $pdo->query("SELECT * FROM administradores");
    $usuarios = $stmt->fetchAll();
    
    echo "Usuarios encontrados: " . count($usuarios) . "<br><br>";
    
    echo "Intentando redirección al dashboard...<br>";
    
    // Iniciar sesión y establecer variables
    session_start();
    $_SESSION['admin_id'] = $usuarios[0]['id'];
    $_SESSION['admin_nombre'] = $usuarios[0]['nombre'];
    
    echo "Variables de sesión establecidas:<br>";
    echo "admin_id: " . $_SESSION['admin_id'] . "<br>";
    echo "admin_nombre: " . $_SESSION['admin_nombre'] . "<br>";
    
    // Verificar que el archivo dashboard existe
    $dashboard_path = __DIR__ . '/admin/dashboard.php';
    echo "Buscando dashboard en: " . $dashboard_path . "<br>";
    echo "¿El archivo existe? " . (file_exists($dashboard_path) ? 'SÍ' : 'NO') . "<br>";
    
    // Intentar incluir el dashboard
    echo "<a href='admin/dashboard.php'>Ir al Dashboard</a>";
    
} catch(PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage();
}
?>