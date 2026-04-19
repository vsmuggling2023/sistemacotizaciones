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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Obtener datos
// Obtener datos
$id_ot = isset($_POST['id_ot']) ? intval($_POST['id_ot']) : 0;
// Debug logging
// Obtener datos del post


// id_vehiculo antes era id_flota en el front, nos aseguramos de recibirlo bien
// Si viene vacio o 0, tratamos de dejarlo NULL si el campo lo permite, pero intval devuelve 0.
// Mejor chequear si tiene valor real.
$vehiculo_val = isset($_POST['id_vehiculo']) && $_POST['id_vehiculo'] !== '' ? $_POST['id_vehiculo'] : (isset($_POST['id_flota']) && $_POST['id_flota'] !== '' ? $_POST['id_flota'] : null);
$id_vehiculo = $vehiculo_val !== null ? intval($vehiculo_val) : null;

$id_empleado = isset($_POST['id_empleado']) && $_POST['id_empleado'] !== '' ? intval($_POST['id_empleado']) : null;
$id_asociado = isset($_POST['id_asociado']) && $_POST['id_asociado'] !== '' ? intval($_POST['id_asociado']) : null;
$id_usuario = isset($_SESSION['ID_USUARIO']) ? intval($_SESSION['ID_USUARIO']) : 0;
$fecha = date('Y-m-d');

if ($id_ot <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID OT inválido']);
    exit();
}

// Validar que se haya seleccionado empleado O asociado
if (!$id_empleado && !$id_asociado) {
    echo json_encode(['success' => false, 'error' => 'Debe seleccionar un empleado o un asociado']);
    exit();
}

// Insertar
$sql = "INSERT INTO asignaciones (id_ot, id_vehiculo, id_empleado, id_asociado, fecha, id_usuario) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param('iiiisi', $id_ot, $id_vehiculo, $id_empleado, $id_asociado, $fecha, $id_usuario);

if ($stmt->execute()) {
    $id_asignacion = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'id_asignacion' => $id_asignacion,
        'message' => 'Asignación creada correctamente'
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al ejecutar: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
