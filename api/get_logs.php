<?php
require '../config/conn.php'; // Cargar conexión

// Establecer el encabezado para que el cliente sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Verificar si es un GET request
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    http_response_code(405); // Método no permitido
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Obtener filtros de la consulta
$application_name = isset($_GET['application_name']) ? htmlspecialchars($_GET['application_name']) : null;
$error_level = isset($_GET['error_level']) ? htmlspecialchars($_GET['error_level']) : null;
$from_date = isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : null;
$to_date = isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : null;

try {
    // Construir consulta
    $query = "SELECT * FROM logs_errors WHERE 1=1";
    $params = [];

    if ($application_name) {
        $query .= " AND application_name = :application_name";
        $params[':application_name'] = $application_name;
    }

    if ($error_level) {
        $query .= " AND error_level = :error_level";
        $params[':error_level'] = $error_level;
    }

    if ($from_date && $to_date) {
        $query .= " AND created_at BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $from_date;
        $params[':to_date'] = $to_date;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200); // OK
    echo json_encode($logs);
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al obtener los logs', 'details' => $e->getMessage()]);
}
?>