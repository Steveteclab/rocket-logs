<?php
session_start(); // Iniciar sesión
require '../config/conn.php'; // Cargar conexión

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No autorizado
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// Verificar si es un POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Recibir los datos del error
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['application_name'], $data['error_message'], $data['error_level'])) {
    http_response_code(400); // Petición incorrecta
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

// Validar datos
$application_name = htmlspecialchars($data['application_name']);
$error_message = htmlspecialchars($data['error_message']);
$error_level = htmlspecialchars($data['error_level']);
$file = isset($data['file']) ? htmlspecialchars($data['file']) : 'N/A';
$line = isset($data['line']) ? (int)$data['line'] : 0;
$user_id = $_SESSION['user_id']; // Asignar el ID del usuario autenticado
$additional_data = isset($data['additional_data']) ? json_encode($data['additional_data']) : null;

try {
    // Insertar en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO logs_errors (application_name, error_message, error_level, file, line, user_id, additional_data)
        VALUES (:application_name, :error_message, :error_level, :file, :line, :user_id, :additional_data)
    ");
    $stmt->execute([
        ':application_name' => $application_name,
        ':error_message' => $error_message,
        ':error_level' => $error_level,
        ':file' => $file,
        ':line' => $line,
        ':user_id' => $user_id,
        ':additional_data' => $additional_data
    ]);

    http_response_code(201); // Creado
    echo json_encode(['message' => 'Log registrado correctamente']);
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al registrar el log', 'details' => $e->getMessage()]);
}
?>