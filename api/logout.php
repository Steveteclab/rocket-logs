<?php
session_start(); // Iniciar sesión

// Establecer el encabezado para que el cliente sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Destruir la sesión
session_destroy();

http_response_code(200); // OK
echo json_encode(['message' => 'Sesión cerrada correctamente']);
?>