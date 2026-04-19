<?php
include('../database.php');
header('Content-Type: application/json');

$id_division = isset($_GET['id_division']) ? intval($_GET['id_division']) : 0;

if ($id_division > 0) {
    $sql = "SELECT id, Nombre FROM servicios WHERE id_division = ? ORDER BY Nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_division);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $sql = "SELECT id, Nombre FROM servicios ORDER BY Nombre";
    $res = $conn->query($sql);
}

$rows = [];
if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    echo json_encode(["success" => true, "data" => $rows]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error, "data" => []]);
}

$conn->close();
?>