<?php
include("../database.php");
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

if ($id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM asociados WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $asociado = $result->fetch_assoc();

        if ($asociado) {
            echo json_encode(['success' => true, 'data' => $asociado]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Asociado no encontrado.']);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID inválido.']);
}
$conn->close();
?>