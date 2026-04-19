<?php
include('../database.php');
header('Content-Type: application/json');

$sql = "SELECT id, nombre FROM cargos ORDER BY nombre";
$res = $conn->query($sql);
$rows = [];
if ($res && $res->num_rows > 0) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
echo json_encode(["data" => $rows]);
