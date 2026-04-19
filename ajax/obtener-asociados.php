<?php
// ajax/obtener-asociados.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include("../database.php");

try {
    // Obtener todos los asociados
    $sql = "SELECT id, NOMBRE, RUT, TELEFONO, EMAIL, VEHICULO 
            FROM asociados 
            ORDER BY NOMBRE ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $asociados = [];
    while ($row = $result->fetch_assoc()) {
        $asociados[] = [
            'id' => $row['id'],
            'nombre' => $row['NOMBRE'],
            'rut' => $row['RUT'],
            'telefono' => $row['TELEFONO'],
            'email' => $row['EMAIL'],
            'vehiculo' => $row['VEHICULO']
        ];
    }

    echo json_encode([
        'success' => true,
        'asociados' => $asociados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>