<?php
session_start(); // Iniciar sesión
require '../config/conn.php'; // Cargar conexión

// Establecer el encabezado para que el cliente sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Recibir los datos del evento
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['event_type'], $data['event_description'], $data['application_name'])) {
    http_response_code(400); // Petición incorrecta
    logError('Petición incorrecta', 'Datos incompletos al registrar el evento');
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

// Validar datos
$event_type = htmlspecialchars($data['event_type']);
$event_description = htmlspecialchars($data['event_description']);
$application_name = htmlspecialchars($data['application_name']); // Nuevo campo
$user_id = $_SESSION['user_id']; // Usar el ID del usuario autenticado
$created_at = date('Y-m-d H:i:s');
$additional_data = isset($data['additional_data']) ? json_encode($data['additional_data']) : null;

// Crear la ruta para el archivo de log usando `application_name`
$cleanAppName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $application_name); // Limpiar caracteres no permitidos
$logFile = realpath(dirname(__FILE__)) . "/../logs/{$cleanAppName}_events_" . date('Y-m-d_H-i-s') . '.log'; // Archivo de log para eventos

// Función para registrar errores en el archivo .log
function logError($message, $details = null, $eventData = null) {
    global $logFile;
    $logData = [
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s'),
        'event_data' => $eventData
    ];
    
    if ($fileHandle = fopen($logFile, 'a')) { // 'a' para añadir al final del archivo
        fwrite($fileHandle, json_encode($logData) . "\n\n"); // Escribir los detalles con doble salto de línea
        fclose($fileHandle);
    } else {
        error_log("No se pudo abrir el archivo .log: $logFile"); // Registrar el error en el log del servidor
        error_log("Error: " . $message . " - Detalles: " . $details);
    }
}

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

// Datos del evento para registrar en el archivo .log en caso de error
$eventData = [
    'application_name' => $application_name,
    'event_type' => $event_type,
    'event_description' => $event_description,
    'user_id' => $user_id,
    'additional_data' => $additional_data
];

try {
    // Insertar el evento en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO events (application_name, event_type, event_description, user_id, created_at, additional_data)
        VALUES (:application_name, :event_type, :event_description, :user_id, :created_at, :additional_data)
    ");
    $stmt->execute([
        ':application_name' => $application_name,
        ':event_type' => $event_type,
        ':event_description' => $event_description,
        ':user_id' => $user_id,
        ':created_at' => $created_at,
        ':additional_data' => $additional_data
    ]);

    http_response_code(201); // Creado
    echo json_encode(['message' => 'Evento registrado correctamente']);
} catch (PDOException $e) {
    // Registrar el error y los datos del evento en el archivo de log
    logError('Error al registrar el evento en la base de datos', $e->getMessage(), $eventData);

    // Enviar la respuesta al cliente
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al registrar el evento', 'details' => $e->getMessage()]);
}
?>