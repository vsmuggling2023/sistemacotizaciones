<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Limpiar buffer para evitar errores de JSON
ob_start();
include("../database.php");
ob_end_clean();

$id_ot = isset($_GET['id_ot']) ? intval($_GET['id_ot']) : 0;

if ($id_ot <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID OT inválido']);
    exit();
}

$sql = "SELECT g.id, g.tipo, g.monto, g.descripcion, g.fecha, g.id_usuario, g.id_empleado, g.id_asociado,
               u.NOMBRE_USUARIO, 
               e.nombre_completo as nombre_empleado,
               a.NOMBRE as nombre_asociado
        FROM gastos g
        LEFT JOIN usuarios u ON g.id_usuario = u.id
        LEFT JOIN empleados e ON g.id_empleado = e.id
        LEFT JOIN asociados a ON g.id_asociado = a.id
        WHERE g.id_ot = ?
        ORDER BY g.fecha DESC, g.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_ot);
$stmt->execute();
$result = $stmt->get_result();

$gastos = [];
while ($row = $result->fetch_assoc()) {
    $gastos[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'gastos' => $gastos]);
$conn->close();
?>
