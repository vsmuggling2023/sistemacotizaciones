<?php
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include("../database.php");

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexion con la base de datos']);
    exit();
}

try {
    $sql = "SELECT id, Nombre FROM servicios ORDER BY Nombre";
    $result = $conn->query($sql);

    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        $servicios[] = [
            'id' => $row['id'],
            'nombre' => $row['Nombre']
        ];
    }

    echo json_encode([
        'success' => true,
        'servicios' => $servicios
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener servicios: ' . $e->getMessage()
    ]);
}

$conn->close();
?>