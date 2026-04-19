<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Limpiar cualquier output previo (espacios, warnings) que rompa el JSON
ob_start();
include("../database.php");
ob_end_clean(); // Descartar cualquier salida del include

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexion con la base de datos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo no permitido']);
    exit();
}

$id_ot = isset($_POST['id_ot']) ? intval($_POST['id_ot']) : 0;
// Permitir string vacio para limpiar el num_caso
$num_caso = isset($_POST['num_caso']) ? trim($_POST['num_caso']) : '';

if ($id_ot <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID OT invalido']);
    exit();
}

try {
    $sql = "UPDATE ordenes_trabajo SET num_caso = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }

    $stmt->bind_param("si", $num_caso, $id_ot);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Num Caso actualizado correctamente'
        ]);
    } else {
        throw new Exception("Error al actualizar: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al actualizar Num Caso: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
