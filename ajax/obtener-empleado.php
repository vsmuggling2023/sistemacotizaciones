<?php
include('../database.php');
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['success' => false, 'error' => 'ID inválido']); exit; }

$sql = "SELECT e.id, e.rut, e.nombre_completo, e.celular, e.email, e.id_cargo, e.Banco, e.tipo_cuenta, e.numero_cuenta, c.nombre AS cargo
        FROM empleados e
        LEFT JOIN cargos c ON c.id = e.id_cargo
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['success' => false, 'error' => $conn->error]); exit; }
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$empleado = $res ? $res->fetch_assoc() : null;
$stmt->close();
if ($empleado) { echo json_encode(['success' => true, 'empleado' => $empleado]); } else { echo json_encode(['success' => false, 'error' => 'No encontrado']); }
