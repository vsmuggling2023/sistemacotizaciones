<?php
include("../database.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $nombre = $_POST['NOMBRE'] ?? '';
    $rut = $_POST['RUT'] ?? '';
    $telefono = $_POST['TELEFONO'] ?? '';
    $email = $_POST['EMAIL'] ?? '';
    $vehiculo = $_POST['VEHICULO'] ?? '';

    if ($id <= 0 || empty($nombre) || empty($rut)) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("UPDATE asociados SET NOMBRE = ?, RUT = ?, TELEFONO = ?, EMAIL = ?, VEHICULO = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $nombre, $rut, $telefono, $email, $vehiculo, $id);

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