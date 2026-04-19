<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include("../database.php");

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexion con la base de datos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Metodo no permitido']);
    exit();
}

$id_cotizacion = isset($_POST['id_cotizacion']) ? (int) $_POST['id_cotizacion'] : 0;
$id_servicio = isset($_POST['id_servicio']) ? (int) $_POST['id_servicio'] : 0;

if ($id_cotizacion <= 0 || $id_servicio <= 0) {
    echo json_encode(['success' => false, 'error' => 'Parametros invalidos']);
    exit();
}

try {
    $sql = "UPDATE cotizaciones SET id_servicio = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("ii", $id_servicio, $id_cotizacion);

    if ($stmt->execute()) {
        // Obtener el nombre del servicio actualizado
        $sqlNombre = "SELECT Nombre FROM servicios WHERE id = ?";
        $stmtNombre = $conn->prepare($sqlNombre);
        $stmtNombre->bind_param("i", $id_servicio);
        $stmtNombre->execute();
        $resultNombre = $stmtNombre->get_result();
        $servicioNombre = '';
        if ($row = $resultNombre->fetch_assoc()) {
            $servicioNombre = $row['Nombre'];
        }
        $stmtNombre->close();

        echo json_encode([
            'success' => true,
            'message' => 'Servicio actualizado correctamente',
            'servicio_nombre' => $servicioNombre
        ]);
    } else {
        throw new Exception("Error al actualizar: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al actualizar servicio: ' . $e->getMessage()
    ]);
}

$conn->close();
?>