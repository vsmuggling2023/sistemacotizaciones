<?php
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include("../database.php");

if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión con la base de datos']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_division'])) {
    $id_division = (int)$_GET['id_division'];
    
    if ($id_division <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de división inválido']);
        exit();
    }
    
    try {
        $sql = "SELECT id, Nombre FROM servicios WHERE id_division = ? ORDER BY Nombre";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_division);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $servicios = [];
        while ($row = $result->fetch_assoc()) {
            $servicios[] = [
                'id' => $row['id'],
                'nombre' => $row['Nombre']
            ];
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'servicios' => $servicios
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener servicios: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
}
?>