<?php
include('../database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

$rut = trim($_POST['rut'] ?? '');
$nombre_completo = trim($_POST['nombre_completo'] ?? '');
$celular = trim($_POST['celular'] ?? '');
$email = trim($_POST['email'] ?? '');
$id_cargo = isset($_POST['id_cargo']) ? (int)$_POST['id_cargo'] : 0;
$Banco = trim($_POST['Banco'] ?? '');
$tipo_cuenta = trim($_POST['tipo_cuenta'] ?? '');
$numero_cuenta_raw = trim($_POST['numero_cuenta'] ?? '');
$numero_cuenta = ($numero_cuenta_raw === '') ? null : $numero_cuenta_raw;

if ($rut === '' || $nombre_completo === '' || $id_cargo <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos requeridos faltantes']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO empleados (rut, nombre_completo, celular, email, id_cargo, Banco, tipo_cuenta, numero_cuenta) VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''))");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param('ssssisss', $rut, $nombre_completo, $celular, $email, $id_cargo, $Banco, $tipo_cuenta, $numero_cuenta);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
