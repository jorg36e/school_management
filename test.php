<?php
require_once 'config/database.php';

echo "<h2>Prueba de Conexión y Base de Datos</h2>";

try {
    // Probar la consulta
    $stmt = $pdo->query("SELECT * FROM administradores");
    $usuarios = $stmt->fetchAll();
    
    echo "<h3>Usuarios en la base de datos:</h3>";
    echo "<pre>";
    print_r($usuarios);
    echo "</pre>";
    
    // Probar la verificación de contraseña
    $password_prueba = "admin123";
    foreach($usuarios as $usuario) {
        echo "<h3>Probando contraseña para usuario: " . $usuario['usuario'] . "</h3>";
        echo "¿Contraseña correcta? : ";
        echo password_verify($password_prueba, $usuario['password']) ? "SÍ" : "NO";
        echo "<br>";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>