<?php
include('../database.php');
header('Content-Type: application/json');

$sql = "SELECT id, NOMBRE, RUT FROM clientes ORDER BY NOMBRE";
$res = $conn->query($sql);
$rows = [];
if ($res && $res->num_rows > 0) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
echo json_encode(["data" => $rows]);
