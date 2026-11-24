<?php
// ---------------------------------------------------------------
// Configuración de la conexión a la base de datos
// Valores por defecto de XAMPP: usuario "root" sin contraseña.
// ---------------------------------------------------------------
$host     = 'localhost';
$dbname   = 'school_management';
$username = 'root';  // Cambiar a usuario específico
$password = '';      // Añadir contraseña segura

// Algunos módulos (p. ej. fotos de estudiantes) leen la configuración desde este arreglo
$db_config = [
    'host'     => $host,
    'dbname'   => $dbname,
    'user'     => $username,
    'password' => $password,
];

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
