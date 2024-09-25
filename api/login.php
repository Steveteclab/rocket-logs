<?php
// Permitir CORS desde cualquier origen (para desarrollo local)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Iniciar la sesión
session_start();

// Manejo de solicitudes OPTIONS (preflight request para CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Conectar a la base de datos
require '../config/conn.php'; // Asegúrate de que el archivo conn.php contenga la configuración correcta

// Recibir los datos enviados desde el cliente
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username'], $data['password'])) {
    http_response_code(400); // Solicitud incorrecta
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$username = htmlspecialchars($data['username']);
$password = $data['password'];

try {
    // Consulta para obtener el usuario desde la base de datos
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe y si la contraseña es correcta
    if ($user && password_verify($password, $user['password'])) {
        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        http_response_code(200); // OK
        echo json_encode(['message' => 'Autenticación exitosa']);
    } else {
        // Credenciales incorrectas
        http_response_code(401); // No autorizado
        echo json_encode(['error' => 'Credenciales incorrectas']);
    }

} catch (PDOException $e) {
    // Manejo de errores de la base de datos
    http_response_code(500); // Error interno del servidor
    echo json_encode(['error' => 'Error durante la autenticación', 'details' => $e->getMessage()]);
    exit();
}
?>