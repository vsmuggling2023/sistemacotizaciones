<?php
include('../database.php');
header('Content-Type: application/json; charset=utf-8');

// Obtener parámetros
$tipoReporte = isset($_GET['tipo_reporte']) ? trim($_GET['tipo_reporte']) : '';
$fechaInicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fechaFin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$division = isset($_GET['division']) ? intval($_GET['division']) : 0;
$tipoServicio = isset($_GET['tipo_servicio']) ? intval($_GET['tipo_servicio']) : 0;
$cliente = isset($_GET['cliente']) ? intval($_GET['cliente']) : 0;
$empleado = isset($_GET['empleado']) ? intval($_GET['empleado']) : 0;
$tipoGasto = isset($_GET['tipo_gasto']) ? trim($_GET['tipo_gasto']) : '';

$resultado = [];
$error = null;

// Solo ejecutar la lógica si se llama directamente al archivo (AJAX), no si se incluye
if (basename($_SERVER['PHP_SELF']) === 'buscar-reporte.php') {
    try {
        switch ($tipoReporte) {
            case 'diario':
                $resultado = generarReporteDiario($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente, $empleado);
                break;

            case 'ordenes_gastos':
                $resultado = generarReporteOrdenesGastos($conn, $fechaInicio, $fechaFin, $division, $cliente, $empleado, $tipoGasto);
                break;

            case 'kilometros':
                $resultado = generarReporteKilometros($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente);
                break;

            case 'observaciones':
                $resultado = generarReporteObservaciones($conn, $fechaInicio, $fechaFin, $division, $cliente);
                break;

            default:
                throw new Exception('Tipo de reporte no válido');
        }

        echo json_encode([
            'success' => true,
            'data' => $resultado,
            'tipo_reporte' => $tipoReporte
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }

    $conn->close();
}

// ============= FUNCIONES DE GENERACIÓN DE REPORTES =============

function generarReporteDiario($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente, $empleado)
{
    $whereParts = [];
    $whereParts[] = "ot.estado != 'Cancelada'";

    if ($fechaInicio) {
        $whereParts[] = "DATE(ot.Fecha_creacion) >= '" . $conn->real_escape_string($fechaInicio) . "'";
    }
    if ($fechaFin) {
        $whereParts[] = "DATE(ot.Fecha_creacion) <= '" . $conn->real_escape_string($fechaFin) . "'";
    }
    if ($division > 0) {
        $whereParts[] = "s.id_division = " . intval($division);
    }
    if ($tipoServicio > 0) {
        $whereParts[] = "c.id_servicio = " . intval($tipoServicio);
    }
    if ($cliente > 0) {
        $whereParts[] = "c.rut_CLIENTE = (SELECT RUT FROM clientes WHERE ID = " . intval($cliente) . ")";
    }

    $where = implode(' AND ', $whereParts);

    $sql = "SELECT 
                ot.id as ot_id,
                ot.Fecha_creacion as ot_fecha_creacion,
                ot.Fecha_Modificacion as ot_fecha_modificacion,
                ot.estado as ot_estado,
                ot.Descripcion as ot_descripcion,
                ot.info_marca,
                ot.info_patente,
                ot.info_chofer,
                ot.info_contacto,
                ot.documento_tributario as ot_documento_tributario,
                ot.forma_pago as ot_forma_pago,
                ot.info_forma_pago,
                ot.estado_cobro,
                c.id as cotizacion_id,
                c.rut_CLIENTE,
                c.id_servicio,
                c.Fecha_servicio,
                c.telefono_CLIENTE,
                c.email_CLIENTE,
                c.id_division,
                c.Descripcion as cotizacion_descripcion,
                c.Panne,
                c.forma_de_pago,
                c.tipo_camino,
                c.documento_tributario as cot_documento_tributario,
                c.id_tipo_vehiculo,
                c.id_comuna_origen,
                c.id_comuna_destino,
                c.direccion_origen,
                c.direccion_destino,
                c.total_km,
                c.id_turno,
                c.estado as cotizacion_estado,
                c.id_sub_tipo,
                c.base_salida,
                c.coordinador_id,
                c.tipo_direccion,
                c.carga,
                c.monto_inicial,
                c.nombre_solicitante,
                c.monto_turno,
                c.monto_tipo_camino,
                c.credito_aplicado,
                c.monto_final,
                c.fecha_creacion as cotizacion_fecha_creacion,
                c.token_aprobacion,
                cl.NOMBRE as cliente_nombre,
                cl.RUT as cliente_rut,
                s.Nombre as servicio_nombre,
                u.NOMBRE_USUARIO as coordinador_nombre,
                co.nombre as comuna_origen,
                cd.nombre as comuna_destino,
                CONCAT_WS(' ', tv.marca, tv.modelo) as tipo_vehiculo,
                tur.nombre as turno_nombre,
                veh.Nombre as vehiculo_nombre,
                veh.Tipo as vehiculo_tipo,
                veh.sub_tipo as vehiculo_subtipo
            FROM ordenes_trabajo ot
            INNER JOIN cotizaciones c ON ot.id_cotizacion = c.id
            LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
            LEFT JOIN servicios s ON c.id_servicio = s.id
            LEFT JOIN usuarios u ON c.coordinador_id = u.id
            LEFT JOIN comunas co ON c.id_comuna_origen = co.id
            LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
            LEFT JOIN flota tv ON c.id_tipo_vehiculo = tv.id
            LEFT JOIN turno tur ON c.id_turno = tur.id
            LEFT JOIN vehiculos veh ON c.id_tipo_vehiculo = veh.id
            WHERE $where
            ORDER BY ot.Fecha_creacion DESC
            LIMIT 1000";

    $result = $conn->query($sql);
    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}

function generarReporteOrdenesGastos($conn, $fechaInicio, $fechaFin, $division, $cliente, $empleado, $tipoGasto)
{
    $whereParts = [];
    $whereParts[] = "1=1";

    if ($fechaInicio) {
        $whereParts[] = "DATE(g.fecha) >= '" . $conn->real_escape_string($fechaInicio) . "'";
    }
    if ($fechaFin) {
        $whereParts[] = "DATE(g.fecha) <= '" . $conn->real_escape_string($fechaFin) . "'";
    }

    if ($empleado > 0) {
        $whereParts[] = " g.id_empleado = " . intval($empleado);
    }

    if ($tipoGasto) {
        $whereParts[] = " LOWER(g.tipo) = LOWER('" . $conn->real_escape_string($tipoGasto) . "')";
    }

    if ($division > 0) {
        $whereParts[] = " s.id_division = " . intval($division);
    }

    if ($cliente > 0) {
        $whereParts[] = " cl.ID = " . intval($cliente);
    }

    $where = implode(' AND ', $whereParts);

    $sql = "SELECT 
                g.id, 
                g.fecha, 
                g.tipo AS tipo_gasto, 
                g.descripcion, 
                g.monto, 
                g.id_ot,
                g.id_empleado,
                ot.info_marca,
                ot.info_modelo, 
                ot.info_patente,
                e.rut, 
                e.nombre_completo AS empleado, 
                e.Banco, 
                e.tipo_cuenta, 
                e.numero_cuenta, 
                e.centro_costo
            FROM gastos g 
            LEFT JOIN empleados e      ON g.id_empleado    = e.id 
            LEFT JOIN ordenes_trabajo ot ON g.id_ot        = ot.id 
            LEFT JOIN cotizaciones c   ON ot.id_cotizacion = c.id
            LEFT JOIN clientes cl      ON c.rut_CLIENTE    = cl.RUT
            LEFT JOIN servicios s      ON c.id_servicio    = s.id
            WHERE $where 
            ORDER BY g.fecha DESC 
            LIMIT 1000";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Error SQL en gastos: ' . $conn->error);
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function generarReporteKilometros($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente)
{
    $whereParts = [];
    $whereParts[] = "ot.estado != 'Cancelada'";

    if ($fechaInicio) {
        $whereParts[] = "DATE(ot.Fecha_creacion) >= '" . $conn->real_escape_string($fechaInicio) . "'";
    }
    if ($fechaFin) {
        $whereParts[] = "DATE(ot.Fecha_creacion) <= '" . $conn->real_escape_string($fechaFin) . "'";
    }
    if ($division > 0) {
        $whereParts[] = " s.id_division=" . intval($division);
    }
    if ($tipoServicio > 0) {
        $whereParts[] = " c.id_servicio=" . intval($tipoServicio);
    }
    if ($cliente > 0) {
        // Here we might need to check against ctz_fk_cli too if that's what is storing the client ID
        $whereParts[] = " (cl.ID=" . intval($cliente) . ")";
    }

    $where = implode(' AND ', $whereParts);

    // Updated SQL based on detailed user schema
    $sql = "SELECT 
                ot.id as id_ot, 
                ot.id_cotizacion,
                ot.Fecha_creacion as fecha_creacion,
                
                -- Cotizacion fields
                c.rut_CLIENTE, 
                c.total_km, 
                c.id_comuna_origen, 
                c.id_comuna_destino, 
                c.id_servicio, 
                c.monto_final, 
                c.Panne as panne, 
                c.Descripcion as descripcion,
                
                -- Cliente fields
                cl.NOMBRE as cliente_nombre,
                cl.RUT as cliente_rut_confirmado, -- Para verificar match
                
                -- Asignaciones fields
                a.id_vehiculo, 
                a.id_empleado,
                
                -- Flota fields
                
                -- Empleado fields (con fallback manual)
                COALESCE(e.nombre_completo, ot.info_chofer, 'N/A') as empleado_nombre,
                
                -- Fallbacks/Formatted for UI
                CASE 
                    WHEN f.id IS NOT NULL THEN CONCAT_WS(' ', f.marca, f.modelo, f.patente)
                    WHEN ot.info_marca IS NOT NULL AND ot.info_marca != '' THEN ot.info_marca
                    ELSE 'N/A'
                END as vehiculo_detalle,
                
                -- Mostrar Nombre y RUT combinados si es posible
                CASE 
                    WHEN cl.NOMBRE IS NOT NULL THEN CONCAT(cl.NOMBRE, ' (', c.rut_CLIENTE, ')')
                    ELSE COALESCE(c.rut_CLIENTE, 'N/A')
                END as rut_cliente,
                
                -- Extra fields with fallbacks
                COALESCE(f.patente, ot.info_patente, 'N/A') as patente,
                f.codigo_movil

            FROM ordenes_trabajo ot 
            INNER JOIN cotizaciones c ON ot.id_cotizacion = c.id
            LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
            -- Join asignaciones using id_ot from assignments table as primary method
            LEFT JOIN asignaciones a ON ot.id = a.id_ot
            LEFT JOIN flota f ON a.id_vehiculo = f.id
            LEFT JOIN empleados e ON a.id_empleado = e.id
            WHERE $where 
            ORDER BY ot.Fecha_creacion DESC 
            LIMIT 1000";

    $result = $conn->query($sql);
    
    if ($result === false) {
         // It is possible some ctz_ columns do not exist if the DB migration wasn't fully applied.
         // In that case, we might want to catch the error. But for now, assuming fields exist as per guardar-cotizacion.php
         throw new Exception('Error SQL en kilometros: ' . $conn->error);
    }

    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}

function generarReporteObservaciones($conn, $fechaInicio, $fechaFin, $division, $cliente)
{
    $whereParts = [];
    $whereParts[] = "1=1";

    if ($fechaInicio) {
        $whereParts[] = "DATE(o.fecha_creacion) >= '" . $conn->real_escape_string($fechaInicio) . "'";
    }
    if ($fechaFin) {
        $whereParts[] = "DATE(o.fecha_creacion) <= '" . $conn->real_escape_string($fechaFin) . "'";
    }
    
    if ($division > 0) {
        $whereParts[] = " s.id_division=" . intval($division);
    }
    if ($cliente > 0) {
        $whereParts[] = " cl.ID=" . intval($cliente);
    }

    $where = implode(' AND ', $whereParts);

    $sql = " SELECT 
                o.id,
                o.id_ot,
                o.comentario,
                o.fecha_creacion,
                o.fecha_modificacion, 
                o.id_usuario,
                u.NOMBRE_USUARIO as usuario,
                ot.Fecha_creacion as ot_fecha,
                cl.NOMBRE as cliente,
                s.Nombre as servicio
            FROM observaciones o
            LEFT JOIN ordenes_trabajo ot ON o.id_ot = ot.id
            LEFT JOIN usuarios u ON o.id_usuario = u.id
            LEFT JOIN cotizaciones c ON ot.id_cotizacion = c.id
            LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
            LEFT JOIN servicios s ON c.id_servicio = s.id
            WHERE $where 
            ORDER BY o.fecha_creacion DESC 
            LIMIT 1000";

    $result = $conn->query($sql);
    
    if ($result === false) {
         throw new Exception('Error SQL en observaciones: ' . $conn->error);
    }

    $data = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }



    return $data;
}
?>