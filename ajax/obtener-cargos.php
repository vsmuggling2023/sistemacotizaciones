<?php
include("../database.php");
header('Content-Type: application/json');

// IDs específicos de cargos que se deben mostrar
$cargos_permitidos = [3, 4, 5, 6, 9, 14, 15, 16, 19, 20, 21, 22, 23, 24, 25, 26, 27];

try {
    // Obtener solo los cargos permitidos desde la base de datos
    $placeholders = str_repeat('?,', count($cargos_permitidos) - 1) . '?';
    $sql = "SELECT id, nombre, descripcion FROM cargos WHERE id IN ($placeholders) ORDER BY nombre";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }
    
    $stmt->bind_param(str_repeat('i', count($cargos_permitidos)), ...$cargos_permitidos);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cargos = [];
    while ($row = $result->fetch_assoc()) {
        $cargos[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'cargos' => $cargos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
