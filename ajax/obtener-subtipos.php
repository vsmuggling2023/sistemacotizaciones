<?php
include('../database.php');
header('Content-Type: application/json');
$id_tipo_vehiculo = (int)($_POST['id_tipo_vehiculo'] ?? 0);
$subtipos = [];
if ($id_tipo_vehiculo > 0) {
    $stmt = $conn->prepare("SELECT Tipo FROM vehiculos WHERE id = ?");
    $stmt->bind_param("i", $id_tipo_vehiculo);
    $stmt->execute();
    $stmt->bind_result($tipo);
    $stmt->fetch();
    $stmt->close();

    if ($tipo) {
        $stmt = $conn->prepare("SELECT DISTINCT sub_tipo FROM vehiculos WHERE Tipo = ? AND sub_tipo IS NOT NULL AND sub_tipo <> ''");
        $stmt->bind_param("s", $tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $subtipos[] = $row['sub_tipo'];
        }
        $stmt->close();
    }
}
echo json_encode($subtipos);
