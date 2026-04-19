<?php
session_start();
include("../database.php");

header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$tipo = $_POST['tipo'] ?? '';
$sub_tipo = $_POST['sub_tipo'] ?? null;
$tipo_direccion = $_POST['tipo_direccion'] ?? null;
$carga = $_POST['carga'] ?? null;

try {
    $monto_total = 0;
    
    // 1. Buscar tarifa por TIPO + SUBTIPO (si existe subtipo)
    if (!empty($tipo) && !empty($sub_tipo)) {
        $sql = "SELECT monto_inicial FROM vehiculos 
                WHERE Tipo = ? AND sub_tipo = ? 
                AND (tipo_direccion IS NULL OR tipo_direccion = '') 
                AND (carga IS NULL OR carga = '')
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $tipo, $sub_tipo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $monto_total += floatval($row['monto_inicial']);
        }
        $stmt->close();
    }
    
    // 2. Buscar tarifa por TIPO DE DIRECCIÓN (independiente)
    if (!empty($tipo_direccion)) {
        $sql = "SELECT monto_inicial FROM vehiculos 
                WHERE (Tipo IS NULL OR Tipo = '') 
                AND (sub_tipo IS NULL OR sub_tipo = '') 
                AND tipo_direccion = ? 
                AND (carga IS NULL OR carga = '')
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $tipo_direccion);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $monto_total += floatval($row['monto_inicial']);
        }
        $stmt->close();
    }
    
    // 3. Buscar tarifa por CARGA (independiente)
    if (!empty($carga)) {
        $sql = "SELECT monto_inicial FROM vehiculos 
                WHERE (Tipo IS NULL OR Tipo = '') 
                AND (sub_tipo IS NULL OR sub_tipo = '') 
                AND (tipo_direccion IS NULL OR tipo_direccion = '') 
                AND carga = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $carga);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $monto_total += floatval($row['monto_inicial']);
        }
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'monto_inicial' => $monto_total
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
