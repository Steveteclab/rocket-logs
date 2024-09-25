<?php
session_start(); // Iniciar sesión

// Establecer el encabezado para que el cliente sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Ruta del archivo de log general
$logFile = realpath(dirname(__FILE__)) . "/../logs/general_" . date('Y-m-d_H-i-s') . '.log'; // Obtener la ruta absoluta

// Función para registrar errores en el archivo .log
function logError($message, $details = null) {
    global $logFile;
    $logData = [
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($fileHandle = fopen($logFile, 'a')) { // 'a' para añadir al final del archivo
        fwrite($fileHandle, json_encode($logData) . "\n\n"); // Escribir los detalles con doble salto de línea
        fclose($fileHandle);
    } else {
        error_log("No se pudo abrir el archivo .log: $logFile"); // Registrar el error en el log del servidor
        // Registrar el error también en el log del servidor
        error_log("Error: " . $message . " - Detalles: " . $details);
    }
}

// Intentar cargar la conexión a la base de datos
try {
    require '../config/conn.php'; // Cargar conexión
} catch (PDOException $e) {
    // Registrar el error de conexión en el archivo .log
    logError('Error de conexión a la base de datos', $e->getMessage());

    // Enviar la respuesta JSON al cliente
    http_response_code(500); // Error de servidor
    echo json_encode(['error' => 'Error de conexión a la base de datos', 'details' => $e->getMessage()]);
    exit();
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

// Recibir los datos del error
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['application_name'], $data['error_message'], $data['error_level'])) {
    http_response_code(400); // Petición incorrecta
    logError('Petición incorrecta', 'Datos incompletos en la solicitud');
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
$created_at = date('Y-m-d H:i:s');
$additional_data = isset($data['additional_data']) ? json_encode($data['additional_data']) : null;

try {
    // Intentar insertar en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO logs_errors (application_name, error_message, error_level, file, line, user_id, created_at, additional_data)
        VALUES (:application_name, :error_message, :error_level, :file, :line, :user_id, :created_at, :additional_data)
    ");
    $stmt->execute([
        ':application_name' => $application_name,
        ':error_message' => $error_message,
        ':error_level' => $error_level,
        ':file' => $file,
        ':line' => $line,
        ':user_id' => $user_id,
        ':created_at' => $created_at,
        ':additional_data' => $additional_data
    ]);

    http_response_code(201); // Creado
    echo json_encode(['message' => 'Log registrado correctamente']);
} catch (PDOException $e) {
    // Registrar el error en un archivo .log
    $cleanAppName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $application_name); // Limpiar caracteres especiales
    $logFile = realpath(dirname(__FILE__)) . "/../logs/{$cleanAppName}_log_error_" . date('Y-m-d_H-i-s') . '.log'; // Ruta absoluta

    // Estructura del log a escribir
    $logData = [
        'application_name' => $application_name,
        'error_message' => $error_message,
        'error_level' => $error_level,
        'file' => $file,
        'line' => $line,
        'user_id' => $user_id,
        'additional_data' => $additional_data,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Escribir los datos en el archivo de log
    if ($fileHandle = fopen($logFile, 'a')) {
        fwrite($fileHandle, json_encode($logData) . "\n"); // Escribir los datos del log
        fwrite($fileHandle, "Error details: " . $e->getMessage() . "\n\n"); // Escribir los detalles del error
        fclose($fileHandle);
    } else {
        error_log("No se pudo abrir el archivo .log: $logFile"); // Registrar el error en el log del servidor
    }

    // Registrar siempre el error aunque no se pueda abrir el archivo
    logError('Error al registrar el log', $e->getMessage());

    // Enviar la respuesta al cliente
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al registrar el log', 'details' => $e->getMessage()]);
    exit();
}
?>