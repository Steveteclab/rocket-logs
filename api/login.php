<?php
session_start(); // Iniciar sesión

require '../config/conn.php'; // Cargar conexión

// Verificar si es un POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Recibir los datos de autenticación
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username'], $data['password'])) {
    http_response_code(400); // Petición incorrecta
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$username = htmlspecialchars($data['username']);
$password = $data['password'];

try {
    // Verificar si el usuario existe
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        http_response_code(200); // OK
        echo json_encode(['message' => 'Autenticación exitosa']);
    } else {
        http_response_code(401); // No autorizado
        echo json_encode(['error' => 'Credenciales incorrectas']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error durante la autenticación', 'details' => $e->getMessage()]);
}
?>