<?php
include('../database.php');
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo json_encode(['success' => false, 'error' => 'ID inválido']); exit; }

$stmt = $conn->prepare("SELECT id, patente, marca, modelo, codigo_movil FROM flota WHERE id = ?");
if (!$stmt) { echo json_encode(['success' => false, 'error' => $conn->error]); exit; }
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$vehiculo = $res ? $res->fetch_assoc() : null;
$stmt->close();
if ($vehiculo) { echo json_encode(['success' => true, 'vehiculo' => $vehiculo]); } else { echo json_encode(['success' => false, 'error' => 'No encontrado']); }
