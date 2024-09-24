<?php
session_start(); // Iniciar sesión

// Destruir la sesión
session_destroy();

http_response_code(200); // OK
echo json_encode(['message' => 'Sesión cerrada correctamente']);
?>