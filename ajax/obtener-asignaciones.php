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

require_once __DIR__ . '/../database.php';

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit();
}

$id_ot = isset($_GET['id_ot']) ? intval($_GET['id_ot']) : 0;
// Debug logging
file_put_contents('debug_obtener_asignaciones.log', date('Y-m-d H:i:s') . " - GET id_ot: " . $id_ot . "\n", FILE_APPEND);

if ($id_ot <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID OT inválido']);
    exit();
}

try {
    $sql = "SELECT a.id, a.id_ot, a.id_vehiculo, a.id_empleado, a.id_asociado, a.fecha,
                   e.nombre_completo AS empleado_nombre,
                   assoc.NOMBRE AS asociado_nombre,
                   f.patente AS vehiculo_patente,
                   f.codigo_movil AS vehiculo_codigo,
                   c.nombre AS cargo_nombre
            FROM asignaciones a
            LEFT JOIN empleados e ON a.id_empleado = e.id
            LEFT JOIN asociados assoc ON a.id_asociado = assoc.id
            LEFT JOIN flota f ON a.id_vehiculo = f.id
            LEFT JOIN cargos c ON e.id_cargo = c.id
            WHERE a.id_ot = ?
            ORDER BY a.id DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }

    $stmt->bind_param('i', $id_ot);
    $stmt->execute();
    $result = $stmt->get_result();

    $asignaciones = [];
    while ($row = $result->fetch_assoc()) {
        $asignaciones[] = [
            'id' => $row['id'],
            'id_ot' => $row['id_ot'],
            'id_vehiculo' => $row['id_vehiculo'],
            'id_empleado' => $row['id_empleado'],
            'id_asociado' => $row['id_asociado'],
            'fecha' => $row['fecha'],
            'empleado_nombre' => trim($row['empleado_nombre']),
            'asociado_nombre' => $row['asociado_nombre'],
            'vehiculo_patente' => $row['vehiculo_patente'],
            'vehiculo_codigo' => $row['vehiculo_codigo'],
            'cargo_nombre' => $row['cargo_nombre']
        ];
    }

    echo json_encode(['success' => true, 'asignaciones' => $asignaciones]);
    // Debug result count
    file_put_contents('debug_obtener_asignaciones.log', date('Y-m-d H:i:s') . " - Found: " . count($asignaciones) . "\n", FILE_APPEND);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
