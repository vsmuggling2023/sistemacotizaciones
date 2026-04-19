<?php
include("../database.php");
header('Content-Type: application/json');

$id_cargo = isset($_GET['id_cargo']) ? (int) $_GET['id_cargo'] : 0;

try {
    if ($id_cargo > 0) {
        // Obtener empleados de un cargo específico
        $sql = "SELECT id, rut, nombre_completo, celular, email 
                FROM empleados 
                WHERE id_cargo = ? 
                ORDER BY nombre_completo";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conn->error);
        }

        $stmt->bind_param("i", $id_cargo);
        $stmt->execute();
        $result = $stmt->get_result();

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

        $stmt->close();

        echo json_encode([
            'success' => true,
            'empleados' => $empleados
        ]);
    } else {
        // Obtener todos los empleados con información de cargo
        $sql = "SELECT e.id, e.rut, e.nombre_completo, e.celular, e.email, e.id_cargo, c.nombre as cargo_nombre
                FROM empleados e
                LEFT JOIN cargos c ON e.id_cargo = c.id
                ORDER BY e.nombre_completo";

        $result = $conn->query($sql);

        $empleados = [];
        while ($row = $result->fetch_assoc()) {
            $empleados[] = [
                'id' => $row['id'],
                'rut' => $row['rut'],
                'nombre_completo' => $row['nombre_completo'],
                'celular' => $row['celular'],
                'email' => $row['email'],
                'id_cargo' => $row['id_cargo'],
                'cargo_nombre' => $row['cargo_nombre']
            ];
        }

        echo json_encode([
            'success' => true,
            'empleados' => $empleados
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>