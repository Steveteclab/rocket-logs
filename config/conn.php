<?php
// Cargar el autoload de Composer, que incluye la librería phpdotenv
require_once '../vendor/autoload.php';  // Correcta ruta hacia vendor/autoload.php

// Establecer el encabezado para que el cliente sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Cargar las variables de entorno desde el archivo .env (que está en la raíz del proyecto)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configurar la zona horaria desde el archivo .env, si está definida
$timezone = $_ENV['APP_TIMEZONE'] ?? 'UTC'; // Si no está definida, usa UTC como predeterminado
date_default_timezone_set($timezone); // Establecer la zona horaria

// Configurar las credenciales de la base de datos usando las variables de entorno
$host = $_ENV['DB_HOST'] ?? null;
$dbname = $_ENV['DB_NAME'] ?? null;
$username = $_ENV['DB_USER'] ?? null;
$password = $_ENV['DB_PASS'] ?? null;
$port = $_ENV['DB_PORT'] ?? 3306; // Por defecto puerto 3306 si no está especificado en .env

// Validar que las variables de entorno se hayan cargado correctamente
if (!$host || !$dbname || !$username || !$password) {
    http_response_code(500); // Error de servidor
    echo json_encode([
        'error' => 'Faltan las credenciales de la base de datos. Verifica el archivo .env'
    ]);
    exit();
}

// Opciones de PDO para un manejo seguro y eficiente
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Modo de errores como excepciones
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver los resultados como un array asociativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Deshabilitar la emulación de consultas preparadas
];

try {
    // Crear una nueva conexión PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Manejo del error de conexión
    http_response_code(500); // Error de servidor interno
    echo json_encode([
        'error' => 'Error de conexión a la base de datos',
        'details' => $e->getMessage()
    ]);
    exit();
}
?>