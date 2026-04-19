<?php
include('../database.php');
header('Content-Type: application/json');
$tipos = [];
$sql = "SELECT id, nombre, monto FROM tipo_camino";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $tipos[] = $row;
}
echo json_encode($tipos);
