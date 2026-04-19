<?php
include "database.php";
header('Content-Type: application/json');

$vehiculos = [];
$subtipos_por_tipo = [];
$direcciones_por_tipo = [];
$cargas_por_tipo = [];

// Obtener vehículos distintos (id, Tipo)
$sql = "SELECT id, Tipo FROM vehiculos WHERE Tipo IS NOT NULL GROUP BY Tipo ORDER BY Tipo";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $vehiculos[] = $row;
    }
}

// Obtener subtipos agrupados por Tipo
$sql_subtipos = "SELECT Tipo, sub_tipo FROM vehiculos WHERE sub_tipo IS NOT NULL ORDER BY Tipo, sub_tipo";
$result_subtipos = $conn->query($sql_subtipos);
if ($result_subtipos) {
    while ($row = $result_subtipos->fetch_assoc()) {
        $tipo = $row['Tipo'];
        $subtipo = $row['sub_tipo'];

        if (!isset($subtipos_por_tipo[$tipo])) {
            $subtipos_por_tipo[$tipo] = [];
        }
        if ($subtipo && !in_array($subtipo, $subtipos_por_tipo[$tipo])) {
            $subtipos_por_tipo[$tipo][] = $subtipo;
        }
    }
}

// Obtener tipos de direccion agrupados por Tipo
$sql_direcciones = "SELECT tipo_direccion FROM vehiculos WHERE tipo_direccion IS NOT NULL GROUP BY tipo_direccion ORDER BY tipo_direccion";
$result_direcciones = $conn->query($sql_direcciones);
if ($result_direcciones) {
    while ($row = $result_direcciones->fetch_assoc()) {
        $td = $row['tipo_direccion'];
        if ($td) {
            if (!isset($direcciones_por_tipo['Camión'])) {
                $direcciones_por_tipo['Camión'] = [];
            }
            if (!in_array($td, $direcciones_por_tipo['Camión'])) {
                $direcciones_por_tipo['Camión'][] = $td;
            }
        }
    }
}

// Obtener cargas agrupadas por Tipo
$sql_cargas = "SELECT carga FROM vehiculos WHERE carga IS NOT NULL GROUP BY carga ORDER BY carga";
$result_cargas = $conn->query($sql_cargas);
if ($result_cargas) {
    while ($row = $result_cargas->fetch_assoc()) {
        $carga = $row['carga'];
        if ($carga) {
            // Asumiendo cargas aplican a Camión y Tracto
            if (!isset($cargas_por_tipo['Camión'])) {
                $cargas_por_tipo['Camión'] = [];
            }
            if (!in_array($carga, $cargas_por_tipo['Camión'])) {
                $cargas_por_tipo['Camión'][] = $carga;
            }
            if (!isset($cargas_por_tipo['Tracto'])) {
                $cargas_por_tipo['Tracto'] = [];
            }
            if (!in_array($carga, $cargas_por_tipo['Tracto'])) {
                $cargas_por_tipo['Tracto'][] = $carga;
            }
        }
    }
}

echo json_encode([
    'vehiculos' => $vehiculos,
    'subtipos' => $subtipos_por_tipo,
    'direcciones' => $direcciones_por_tipo,
    'cargas' => $cargas_por_tipo
]);
?>