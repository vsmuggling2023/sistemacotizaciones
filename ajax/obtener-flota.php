<?php
header('Content-Type: application/json');
require_once('../database.php');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Conexión fallida']);
    exit;
}

try {
    $sql = "SELECT id, codigo_movil, patente, marca, modelo
            FROM flota
            WHERE (codigo_movil IS NOT NULL AND TRIM(codigo_movil) <> '')
               OR (patente IS NOT NULL AND TRIM(patente) <> '')
            ORDER BY patente ASC";
    $res = $conn->query($sql);
    if (!$res) {
        throw new Exception($conn->error);
    }
    $flota = [];
    while ($row = $res->fetch_assoc()) {
        $flota[] = [
            'id' => (int) $row['id'],
            'codigo_movil' => trim($row['codigo_movil'] ?? ''),
            'patente' => trim($row['patente'] ?? ''),
            'marca' => trim($row['marca'] ?? ''),
            'modelo' => trim($row['modelo'] ?? '')
        ];
    }
    echo json_encode(['success' => true, 'flota' => $flota]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error al obtener flota: ' . $e->getMessage()]);
}
exit;
?>