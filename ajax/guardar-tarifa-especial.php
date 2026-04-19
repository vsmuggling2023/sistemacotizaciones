<?php
include('../database.php');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método inválido']);
    exit;
}

session_start();
if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}
$usuario = $_SESSION['NOMBRE_USUARIO'];

$accesoAdministrador = false;
$esInvitado = false;
$esCobranza = false;
$stmtRol = $conn->prepare("SELECT r.id AS rol_id, r.nombre AS rol FROM usuarios u LEFT JOIN roles r ON u.id_rol = r.id WHERE u.nombre_usuario = ?");
if ($stmtRol) {
    $stmtRol->bind_param('s', $usuario);
    $stmtRol->execute();
    $resRol = $stmtRol->get_result();
    if ($resRol && $resRol->num_rows > 0) {
        $rowRol = $resRol->fetch_assoc();
        $rolUsuario = $rowRol['rol'] ?? '';
        $rolId = (int)($rowRol['rol_id'] ?? 0);
        if ($rolUsuario === 'Administrador' || $rolId === 1) {
            $accesoAdministrador = true;
        } elseif ($rolUsuario === 'Cobranza' || $rolId === 3) {
            $esCobranza = true;
        } elseif ($rolUsuario === 'Invitado' || $rolId === 4) {
            $esInvitado = true;
        }
    }
    $stmtRol->close();
}
$soloLectura = $esInvitado;
$bloquearFueraDeOT = $esCobranza;
if (!$accesoAdministrador || $soloLectura || $bloquearFueraDeOT) {
    echo json_encode(['success' => false, 'error' => 'Sin permiso']);
    exit;
}

$precio_base = isset($_POST['precio_base']) ? (int)$_POST['precio_base'] : 0;
$valor_km = isset($_POST['valor_km']) ? (int)$_POST['valor_km'] : 0;
$tope_km = isset($_POST['tope_km']) ? (int)$_POST['tope_km'] : 0;
$valor_custodia = isset($_POST['valor_custodia']) ? (int)$_POST['valor_custodia'] : 0;
$id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;

if ($precio_base < 0 || $valor_km < 0 || $tope_km < 0 || $valor_custodia < 0 || $id_cliente <= 0) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO tarifa_especial (precio_base, valor_km, tope_km, valor_custodia, id_cliente) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param('iiiii', $precio_base, $valor_km, $tope_km, $valor_custodia, $id_cliente);
$ok = $stmt->execute();
if ($ok) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
