<?php
include('../database.php');
header('Content-Type: application/json');
$turnos = [];
$sql = "SELECT id, nombre, monto FROM turno";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $turnos[] = $row;
}
echo json_encode($turnos);
