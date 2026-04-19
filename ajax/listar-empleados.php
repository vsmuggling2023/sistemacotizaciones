<?php
include('../database.php');
header('Content-Type: application/json');

$sql = "SELECT e.id, e.rut, e.nombre_completo, e.celular, e.email, e.id_cargo, c.nombre AS cargo, e.Banco, e.tipo_cuenta, e.numero_cuenta\n        FROM empleados e\n        LEFT JOIN cargos c ON c.id = e.id_cargo\n        ORDER BY e.id";
$res = $conn->query($sql);
$rows = [];
if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    echo json_encode(["data" => $rows]);
    exit;
}

// Fallback: sin JOIN si la tabla de cargo no existe o falla el JOIN
$fallback = "SELECT e.id, e.rut, e.nombre_completo, e.celular, e.email, e.id_cargo, e.Banco, e.tipo_cuenta, e.numero_cuenta
             FROM empleados e
             ORDER BY e.id";
$res2 = $conn->query($fallback);
$rows2 = [];
if ($res2 instanceof mysqli_result) {
    while ($r = $res2->fetch_assoc()) { $rows2[] = $r; }
    echo json_encode(["data" => $rows2, "info" => "fallback"]);
    exit;
}

echo json_encode(["data" => [], "error" => $conn->error]);
