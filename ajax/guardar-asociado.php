<?php
include("../database.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['NOMBRE'] ?? '';
    $rut = $_POST['RUT'] ?? '';
    $telefono = $_POST['TELEFONO'] ?? '';
    $email = $_POST['EMAIL'] ?? '';
    $vehiculo = $_POST['VEHICULO'] ?? '';

    if (empty($nombre) || empty($rut)) {
        echo json_encode(['success' => false, 'error' => 'Nombre y RUT son obligatorios.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO asociados (NOMBRE, RUT, TELEFONO, EMAIL, VEHICULO) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $rut, $telefono, $email, $vehiculo);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
}
$conn->close();
?>