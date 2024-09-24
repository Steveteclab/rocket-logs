<?php
require '../config/conn.php'; // Cargar conexión

// Verificar si es un POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Recibir los datos
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username'], $data['password'])) {
    http_response_code(400); // Petición incorrecta
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$username = htmlspecialchars($data['username']);
$password = password_hash($data['password'], PASSWORD_BCRYPT); // Encriptar la contraseña

try {
    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password
    ]);

    http_response_code(201); // Creado
    echo json_encode(['message' => 'Usuario registrado correctamente']);
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al registrar el usuario', 'details' => $e->getMessage()]);
}
?>