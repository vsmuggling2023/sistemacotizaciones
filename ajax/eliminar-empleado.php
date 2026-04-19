<?php
include('../database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'Método inválido']); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { echo json_encode(['success' => false, 'error' => 'ID inválido']); exit; }

$stmt = $conn->prepare("DELETE FROM empleados WHERE id = ?");
if (!$stmt) { echo json_encode(['success' => false, 'error' => $conn->error]); exit; }
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
if ($ok) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
$stmt->close();
$conn->close();
