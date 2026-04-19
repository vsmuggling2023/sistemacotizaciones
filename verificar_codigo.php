<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
ob_start(); // Iniciar buffer de salida para controlar salida limpia

include('database.php');

$rut_cliente = $_POST['rut_cliente'] ?? null;
$codigo = $_POST['codigo'] ?? null;

if (!$rut_cliente || !$codigo) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['valid' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT codigo FROM codigos_autorizacion WHERE rut_cliente = ? AND estado = 'pendiente' ORDER BY creado_en DESC LIMIT 1");
$stmt->bind_param("s", $rut_cliente);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    if ($row['codigo'] === $codigo) {
        $stmt_update = $conn->prepare("UPDATE codigos_autorizacion SET estado = 'usado' WHERE rut_cliente = ? AND codigo = ?");
        $stmt_update->bind_param("ss", $rut_cliente, $codigo);
        $stmt_update->execute();

        if (ob_get_length()) ob_clean();
        echo json_encode(['valid' => true]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['valid' => false]);
    }
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode(['valid' => false]);
}

$stmt->close();
$conn->close();
exit;
