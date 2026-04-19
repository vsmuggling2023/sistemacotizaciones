<?php
include('../database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'Método inválido']); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$rut = trim($_POST['rut'] ?? '');
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$celular = trim($_POST['celular'] ?? '');
$email = trim($_POST['email'] ?? '');
$id_cargo = isset($_POST['id_cargo']) ? (int)$_POST['id_cargo'] : 0;
$Banco = trim($_POST['Banco'] ?? '');
$tipo_cuenta = trim($_POST['tipo_cuenta'] ?? '');
$numero_cuenta = trim($_POST['numero_cuenta'] ?? '');

if ($id <= 0 || $rut === '' || $nombre_completo === '' || $id_cargo <= 0) { echo json_encode(['success' => false, 'error' => 'Datos inválidos']); exit; }

$sql = "UPDATE empleados SET rut=?, nombre_completo=?, celular=?, email=?, id_cargo=?, Banco=?, tipo_cuenta=?, numero_cuenta=NULLIF(?, '') WHERE id=?";
$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['success' => false, 'error' => $conn->error]); exit; }
$stmt->bind_param('ssssisssi', $rut, $nombre_completo, $celular, $email, $id_cargo, $Banco, $tipo_cuenta, $numero_cuenta, $id);
$ok = $stmt->execute();
if ($ok) { echo json_encode(['success' => true]); } else { echo json_encode(['success' => false, 'error' => $stmt->error]); }
$stmt->close();
$conn->close();
