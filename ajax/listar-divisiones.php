<?php
include('../database.php');
header('Content-Type: application/json');

$sql = "SELECT id, NOMBRE as nombre_division 
        FROM division 
        ORDER BY id";

$res = $conn->query($sql);
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