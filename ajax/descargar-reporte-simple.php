<?php
include('../database.php');

// Obtener parámetros
$tipoReporte = isset($_GET['tipo_reporte']) ? trim($_GET['tipo_reporte']) : '';
$fechaInicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fechaFin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$division = isset($_GET['division']) ? intval($_GET['division']) : 0;
$tipoServicio = isset($_GET['tipo_servicio']) ? intval($_GET['tipo_servicio']) : 0;
$cliente = isset($_GET['cliente']) ? intval($_GET['cliente']) : 0;
$empleado = isset($_GET['empleado']) ? intval($_GET['empleado']) : 0;
$tipoGasto = isset($_GET['tipo_gasto']) ? trim($_GET['tipo_gasto']) : '';

// Incluir funciones de búsqueda
require_once('buscar-reporte.php');

try {
    // Validar tipo de reporte
    if (empty($tipoReporte)) {
        throw new Exception('Tipo de reporte no especificado');
    }

    // Obtener datos según el tipo de reporte
    $data = [];
    $nombreReporte = '';
    $headers = [];
    $campos = [];

    switch ($tipoReporte) {
        case 'diario':
            $data = generarReporteDiario($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente, $empleado);
            $nombreReporte = 'Reporte_Diario_Completo';
            $headers = ['ID OT', 'Fecha Creación OT', 'Estado OT', 'Descripción OT', 'Marca', 'Patente', 'Chofer', 'Contacto', 'Doc Trib OT', 'Forma Pago OT', 'Info Forma Pago', 'Estado Cobro', 'ID Cotización', 'RUT Cliente', 'Teléfono', 'Email', 'Fecha Servicio', 'Descripción Cot', 'Panne', 'Forma Pago Cot', 'Tipo Camino', 'Doc Trib Cot', 'Dirección Origen', 'Dirección Destino', 'Total KM', 'Estado Cot', 'Base Salida', 'Tipo Dirección', 'Carga', 'Monto Inicial', 'Solicitante', 'Monto Turno', 'Monto Tipo Camino', 'Crédito Aplicado', 'Monto Final', 'Fecha Creación Cot', 'Cliente', 'RUT', 'Servicio', 'Coordinador', 'Comuna Origen', 'Comuna Destino', 'Flota', 'Turno', 'Vehículo Nombre', 'Vehículo Tipo', 'Vehículo Subtipo'];
            $campos = ['ot_id', 'ot_fecha_creacion', 'ot_estado', 'ot_descripcion', 'info_marca', 'info_patente', 'info_chofer', 'info_contacto', 'ot_documento_tributario', 'ot_forma_pago', 'info_forma_pago', 'estado_cobro', 'cotizacion_id', 'rut_CLIENTE', 'telefono_CLIENTE', 'email_CLIENTE', 'Fecha_servicio', 'cotizacion_descripcion', 'Panne', 'forma_de_pago', 'tipo_camino', 'cot_documento_tributario', 'direccion_origen', 'direccion_destino', 'total_km', 'cotizacion_estado', 'base_salida', 'tipo_direccion', 'carga', 'monto_inicial', 'nombre_solicitante', 'monto_turno', 'monto_tipo_camino', 'credito_aplicado', 'monto_final', 'cotizacion_fecha_creacion', 'cliente_nombre', 'cliente_rut', 'servicio_nombre', 'coordinador_nombre', 'comuna_origen', 'comuna_destino', 'tipo_vehiculo', 'turno_nombre', 'vehiculo_nombre', 'vehiculo_tipo', 'vehiculo_subtipo'];
            break;

        case 'ordenes_gastos':
            $data = generarReporteOrdenesGastos($conn, $fechaInicio, $fechaFin, $division, $cliente, $empleado, $tipoGasto);
            $nombreReporte = 'Reporte_Ordenes_Gastos';
            $headers = [
                'ID Gasto', 
                'Fecha', 
                'Tipo Gasto', 
                'Descripción', 
                'Monto', 
                'ID OT', 
                'Marca', 
                'Modelo', 
                'Patente', 
                'ID Empleado',
                'RUT Empleado', 
                'Nombre Empleado', 
                'Banco', 
                'Tipo Cuenta', 
                'Número Cuenta', 
                'Centro Costo'
            ];
            $campos = [
                'id', 
                'fecha', 
                'tipo_gasto', 
                'descripcion', 
                'monto', 
                'id_ot', 
                'info_marca', 
                'info_modelo', 
                'info_patente', 
                'id_empleado',
                'rut', 
                'empleado', 
                'Banco', 
                'tipo_cuenta', 
                'numero_cuenta', 
                'centro_costo'
            ];
            break;

        case 'kilometros':
            $data = generarReporteKilometros($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente);
            $nombreReporte = 'Reporte_Kilometros';
            $headers = ['ID OT', 'Vehículo', 'Empleado', 'Patente', 'Cód. Móvil', 'ID Cotización', 'RUT Cliente', 'Descripción', 'Panne', 'Total KM', 'ID Com. Origen', 'ID Com. Destino', 'Fecha'];
            $campos = ['id_ot', 'vehiculo_detalle', 'empleado_nombre', 'patente', 'codigo_movil', 'id_cotizacion', 'rut_cliente', 'descripcion', 'panne', 'total_km', 'id_comuna_origen', 'id_comuna_destino', 'fecha_creacion'];
            break;

        case 'observaciones':
            $data = generarReporteObservaciones($conn, $fechaInicio, $fechaFin, $division, $cliente);
            $nombreReporte = 'Reporte_Observaciones';
            $headers = ['ID', 'ID OT', 'Comentario', 'F. Creación', 'F. Modificación', 'ID Usuario', 'Usuario'];
            $campos = ['id', 'id_ot', 'comentario', 'fecha_creacion', 'fecha_modificacion', 'id_usuario', 'usuario'];
            break;

        default:
            throw new Exception('Tipo de reporte no válido');
    }

    // Validar que haya datos
    if (empty($data)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'No hay datos para exportar'
        ]);
        exit;
    }

    // Configurar headers para descarga de Excel
    $filename = $nombreReporte . '_' . date('Y-m-d_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Generar contenido HTML para Excel
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th { background-color: #4472C4; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; }';
    echo 'td { padding: 8px; border: 1px solid #ddd; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    echo '<table>';

    // Encabezados
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . htmlspecialchars($header) . '</th>';
    }
    echo '</tr>';

    // Datos
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($campos as $campo) {
            $valor = isset($row[$campo]) ? $row[$campo] : 'N/A';
            echo '<td>' . htmlspecialchars($valor) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';
    echo '</body>';
    echo '</html>';

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>