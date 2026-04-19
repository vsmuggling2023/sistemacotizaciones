<?php
include("../database.php");
header('Content-Type: application/json');

try {
    $sql = "SELECT id, NOMBRE, RUT, TELEFONO, EMAIL, VEHICULO FROM asociados ORDER BY id DESC";
    $result = $conn->query($sql);

    $asociados = [];
    while ($row = $result->fetch_assoc()) {
        $asociados[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $asociados]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>