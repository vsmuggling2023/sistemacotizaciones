<?php
// ajax/obtener-empleados-gasto.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

include("../database.php");

try {
    // Obtener empleados con disp_gasto = "Sí"
    $sql = "SELECT id, rut, nombre_completo, celular, email 
            FROM empleados 
            WHERE disp_gasto = 'Sí' 
            ORDER BY nombre_completo ASC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $empleados = [];
    while ($row = $result->fetch_assoc()) {
        $empleados[] = [
            'id' => $row['id'],
            'rut' => $row['rut'],
            'nombre_completo' => $row['nombre_completo'],
            'celular' => $row['celular'],
            'email' => $row['email']
        ];
    }

    echo json_encode([
        'success' => true,
        'empleados' => $empleados
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>