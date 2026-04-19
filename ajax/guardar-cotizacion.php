<?php
include('../database.php');
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Datos incompletos'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_division = $_POST['id_division'] ?? null;
    $id_servicio = $_POST['id_servicio'] ?? null;
    $id_turno = $_POST['id_turno'] ?? null;
    $rut_cliente = $_POST['rut_cliente'] ?? null;
    $id_comuna_origen = $_POST['id_comuna_origen'] ?? null;
    $id_comuna_destino = $_POST['id_comuna_destino'] ?? null;
    $tipo_camino = $_POST['tipo_camino'] ?? null;
    $tipo_vehiculo = $_POST['tipo_vehiculo'] ?? null;
    $subtipo_vehiculo = $_POST['subtipo_vehiculo'] ?? null;
    $total_km = $_POST['total_km'] ?? null;
    $monto_inicial = $_POST['monto_inicial'] ?? null;
    $comentarios = $_POST['comentarios'] ?? null;

    if ($id_division && $id_servicio && $id_turno && $rut_cliente && $id_comuna_origen && $id_comuna_destino && $total_km && $monto_inicial) {
        $stmt = $conn->prepare("INSERT INTO cotizaciones 
            (ctz_fk_div, ctz_fk_tipo_servicio_cdg, ctz_fk_turno, ctz_fk_cli, ctz_fk_comuna_origen, ctz_fk_comuna_destino, 
            ctz_tipo_camino, ctz_tipo_vehiculo, ctz_subtipo_vehiculo, ctz_total_km, ctz_monto_inicial, ctz_comentarios, ctz_dt_fecha_servicio)
            VALUES (?, ?, ?, (SELECT id FROM clientes WHERE rut = ? LIMIT 1), ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiisssssdss", 
            $id_division, $id_servicio, $id_turno, $rut_cliente,
            $id_comuna_origen, $id_comuna_destino, $tipo_camino, $tipo_vehiculo, $subtipo_vehiculo,
            $total_km, $monto_inicial, $comentarios
        );

        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Cotización guardada con éxito'];
        } else {
            $response['message'] = 'Error al guardar: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Faltan campos obligatorios';
    }
}

echo json_encode($response);
