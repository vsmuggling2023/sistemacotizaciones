<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Limpiar buffer
ob_start();
require_once __DIR__ . '/../database.php';
ob_end_clean();

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$id_ot = isset($_POST['id_ot']) ? intval($_POST['id_ot']) : 0;
$info_marca = isset($_POST['info_marca']) ? trim($_POST['info_marca']) : null;
$info_modelo = isset($_POST['info_modelo']) ? trim($_POST['info_modelo']) : null;
$info_patente = isset($_POST['info_patente']) ? trim($_POST['info_patente']) : null;
$info_chofer = isset($_POST['info_chofer']) ? trim($_POST['info_chofer']) : null;
$info_telefono = isset($_POST['info_telefono']) ? trim($_POST['info_telefono']) : null;

if ($id_ot <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID OT inválido']);
    exit();
}

// Actualizar información del vehículo
$sql = "UPDATE ordenes_trabajo SET 
            info_marca = ?, 
            info_modelo = ?, 
            info_patente = ?, 
            info_chofer = ?,
            info_telefono = ?,
            Fecha_Modificacion = NOW()
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param('sssssi', $info_marca, $info_modelo, $info_patente, $info_chofer, $info_telefono, $id_ot);
$ok = $stmt->execute();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al ejecutar consulta: ' . $stmt->error]);
    $stmt->close();
    exit();
}

$stmt->close();
echo json_encode(['success' => true]);
?>
