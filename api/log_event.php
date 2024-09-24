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

// Recibir los datos del evento
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['event_type'], $data['event_description'])) {
    http_response_code(400); // Petición incorrecta
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

// Validar datos
$event_type = htmlspecialchars($data['event_type']);
$event_description = htmlspecialchars($data['event_description']);
$user_id = $_SESSION['user_id']; // Usar el ID del usuario autenticado
$additional_data = isset($data['additional_data']) ? json_encode($data['additional_data']) : null;

try {
    // Insertar el evento en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO events (event_type, event_description, user_id, additional_data)
        VALUES (:event_type, :event_description, :user_id, :additional_data)
    ");
    $stmt->execute([
        ':event_type' => $event_type,
        ':event_description' => $event_description,
        ':user_id' => $user_id,
        ':additional_data' => $additional_data
    ]);

    http_response_code(201); // Creado
    echo json_encode(['message' => 'Evento registrado correctamente']);
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al registrar el evento', 'details' => $e->getMessage()]);
}
?>