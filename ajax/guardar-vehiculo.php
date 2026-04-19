<?php
include('../database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'Método inválido']); exit; }

$patente = trim($_POST['patente'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$codigo_movil = trim($_POST['codigo_movil'] ?? '');

if ($patente === '' || $marca === '' || $modelo === '') { echo json_encode(['success' => false, 'error' => 'Datos requeridos faltantes']); exit; }

$stmt = $conn->prepare("INSERT INTO flota (patente, marca, modelo, codigo_movil) VALUES (?, ?, ?, ?)");
if (!$stmt) { echo json_encode(['success' => false, 'error' => $conn->error]); exit; }
$stmt->bind_param('ssss', $patente, $marca, $modelo, $codigo_movil);
$ok = $stmt->execute();
if ($ok) { echo json_encode(['success' => true, 'id' => $conn->insert_id]); } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
$stmt->close();
$conn->close();
