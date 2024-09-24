<?php
session_start(); // Iniciar sesión
require '../config/conn.php'; // Cargar conexión

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No autorizado
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// Obtener filtros de la consulta
$event_type = isset($_GET['event_type']) ? htmlspecialchars($_GET['event_type']) : null;
$from_date = isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : null;
$to_date = isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : null;

try {
    // Construir consulta
    $query = "SELECT * FROM events WHERE 1=1";
    $params = [];

    if ($event_type) {
        $query .= " AND event_type = :event_type";
        $params[':event_type'] = $event_type;
    }

    if ($from_date && $to_date) {
        $query .= " AND created_at BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $from_date;
        $params[':to_date'] = $to_date;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    http_response_code(200); // OK
    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500); // Error interno
    echo json_encode(['error' => 'Error al obtener los eventos', 'details' => $e->getMessage()]);
}
?>