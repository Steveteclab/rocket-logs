<?php
require '../config/conn.php'; // Cargar conexión

// Establecer el encabezado para que el cliente sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Verificar si es un POST request
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Recibir los datos
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username'], $data['password'], $data['email'], $data['phone'], $data['pais'])) {
    http_response_code(400); // Petición incorrecta
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

// Sanitizar los datos recibidos
$username = htmlspecialchars($data['username']);
$password = password_hash($data['password'], PASSWORD_BCRYPT); // Encriptar la contraseña
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL); // Validar el formato de correo electrónico
$phone = htmlspecialchars($data['phone']);
$pais = htmlspecialchars($data['pais']);

// Verificar que el email sea válido
if (!$email) {
    http_response_code(400); // Petición incorrecta
    echo json_encode(['error' => 'Email inválido']);
    exit();
}

try {
    // Insertar en la base de datos
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, email, phone, pais) 
        VALUES (:username, :password, :email, :phone, :pais)
    ");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':email' => $email,
        ':phone' => $phone,
        ':pais' => $pais
    ]);

    http_response_code(201); // Creado
    echo json_encode(['message' => 'Usuario registrado correctamente']);
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al registrar el usuario', 'details' => $e->getMessage()]);
}
?>