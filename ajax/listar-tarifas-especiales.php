<?php
include('../database.php');
header('Content-Type: application/json');

$sql = "SELECT te.id, te.precio_base, te.valor_km, te.tope_km, te.valor_custodia, c.NOMBRE AS cliente, c.RUT AS rut
        FROM tarifa_especial te
        LEFT JOIN clientes c ON c.id = te.id_cliente
        ORDER BY te.id DESC";
$res = $conn->query($sql);
$rows = [];
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}
echo json_encode(["data" => $rows]);
