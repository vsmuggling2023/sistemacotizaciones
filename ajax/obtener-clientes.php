<?php
include('../database.php');
header('Content-Type: application/json');
$rut = $_POST['rut'] ?? '';
$data = [];
if (!empty($rut)) {
    $stmt = $conn->prepare("SELECT nombre, rut, credito FROM clientes WHERE rut = ?");
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $stmt->bind_result($nombre, $rut_cliente, $credito);
    $stmt->fetch();
    $data = ['nombre' => $nombre, 'rut' => $rut_cliente, 'credito' => $credito];
    $stmt->close();
}
echo json_encode($data);
