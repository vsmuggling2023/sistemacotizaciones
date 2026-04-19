<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada']);
    exit();
}

// Conexión a la base de datos
require_once("../database.php");
if (!$conn || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión con la base de datos']);
    exit();
}

// Procesar la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id <= 0) {
            throw new Exception('ID de gasto inválido');
        }

        // Eliminar el gasto
        $sql = "DELETE FROM gastos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Error al preparar la consulta: ' . $conn->error);
        }

        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Gasto eliminado exitosamente'
                ]);
            } else {
                throw new Exception('No se encontró el gasto con ID: ' . $id);
            }
        } else {
            throw new Exception('Error al ejecutar la consulta: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}

$conn->close();
?>
