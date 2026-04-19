<?php
include('../database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'Método inválido']); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$patente = trim($_POST['patente'] ?? '');
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$codigo_movil = trim($_POST['codigo_movil'] ?? '');

if ($id <= 0 || $patente === '' || $marca === '' || $modelo === '') { echo json_encode(['success' => false, 'error' => 'Datos inválidos']); exit; }

$stmt = $conn->prepare("UPDATE flota SET patente=?, marca=?, modelo=?, codigo_movil=? WHERE id=?");
if (!$stmt) { echo json_encode(['success' => false, 'error' => $conn->error]); exit; }
$stmt->bind_param('ssssi', $patente, $marca, $modelo, $codigo_movil, $id);
$ok = $stmt->execute();
if ($ok) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
$stmt->close();
$conn->close();
