<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/../database.php';

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit();
}

try {
    // Obtener el máximo ID actual para predecir el siguiente
    $sql = "SELECT MAX(id) as max_id FROM asignaciones";
    $result = $conn->query($sql);
    
    $next_id = 1;
    if ($result && $row = $result->fetch_assoc()) {
        $next_id = intval($row['max_id']) + 1;
    }

    echo json_encode(['success' => true, 'next_id' => $next_id]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
