<?php
include('../database.php');
header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$like = '%' . $conn->real_escape_string($q) . '%';
$rutClean = preg_replace('/[^0-9kK]/', '', $q);

$sql = "SELECT id, NOMBRE, RUT FROM clientes 
        WHERE (? = '' OR NOMBRE LIKE ? OR REPLACE(REPLACE(REPLACE(RUT, '.', ''), '-', ''), ' ', '') LIKE ?) 
        ORDER BY NOMBRE 
        LIMIT 20";
$stmt = $conn->prepare($sql);
$rutPattern = '%' . $rutClean . '%';
$stmt->bind_param('sss', $q, $like, $rutPattern);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
if ($res && $res->num_rows > 0) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
$stmt->close();
echo json_encode(["data" => $rows]);
