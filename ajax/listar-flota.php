<?php
include('../database.php');
header('Content-Type: application/json');

$sql = "SELECT id, patente, marca, modelo, codigo_movil FROM flota ORDER BY id DESC";
$res = $conn->query($sql);
$rows = [];
if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
echo json_encode(["data" => $rows]);
