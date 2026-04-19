<?php
require '../vendor/autoload.php';
include('../database.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

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

try {
    // Incluir el archivo de búsqueda para reutilizar las funciones
    include('buscar-reporte.php');

    // Validar tipo de reporte
    if (empty($tipoReporte)) {
        throw new Exception('Tipo de reporte no especificado');
    }

    // Obtener datos según el tipo de reporte
    $data = [];
    switch ($tipoReporte) {
        case 'diario':
            $data = generarReporteDiario($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente, $empleado);
            $nombreReporte = 'Reporte_Diario';
            break;

        case 'ordenes_gastos':
            $data = generarReporteOrdenesGastos($conn, $fechaInicio, $fechaFin, $division, $cliente, $empleado, $tipoGasto);
            $nombreReporte = 'Reporte_Ordenes_Gastos';
            break;

        case 'kilometros':
            $data = generarReporteKilometros($conn, $fechaInicio, $fechaFin, $division, $tipoServicio, $cliente);
            $nombreReporte = 'Reporte_Kilometros';
            break;

        case 'observaciones':
            $data = generarReporteObservaciones($conn, $fechaInicio, $fechaFin, $division, $cliente);
            $nombreReporte = 'Reporte_Observaciones';
            break;

        default:
            throw new Exception('Tipo de reporte no válido');
    }

    // Validar que haya datos
    if (empty($data)) {
        echo json_encode([
            'success' => false,
            'error' => 'No hay datos para exportar'
        ]);
        exit;
    }

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Configurar según el tipo de reporte
    switch ($tipoReporte) {
        case 'diario':
            $sheet->setTitle('Reporte Diario');
            $headers = ['Fecha', 'Total Órdenes', 'Completadas', 'En Proceso', 'Pendientes', 'Monto Total'];
            $sheet->fromArray($headers, NULL, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['fecha']);
                $sheet->setCellValue('B' . $row, $item['total_ordenes']);
                $sheet->setCellValue('C' . $row, $item['completadas']);
                $sheet->setCellValue('D' . $row, $item['en_proceso']);
                $sheet->setCellValue('E' . $row, $item['pendientes']);
                $sheet->setCellValue('F' . $row, $item['monto_total']);
                $row++;
            }
            break;

        case 'ordenes_gastos':
            $sheet->setTitle('Órdenes de Gastos');
            $headers = ['Fecha', 'Tipo Gasto', 'Descripción', 'Empleado', 'N° OT', 'Monto'];
            $sheet->fromArray($headers, NULL, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['fecha'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, $item['tipo_gasto'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item['descripcion'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $item['empleado'] ?? 'N/A');
                $sheet->setCellValue('E' . $row, $item['numero_ot'] ?? 'N/A');
                $sheet->setCellValue('F' . $row, $item['monto'] ?? 0);
                $row++;
            }
            break;

        case 'kilometros':
            $sheet->setTitle('Kilómetros Recorridos');
            $headers = ['N° OT', 'Fecha', 'Cliente', 'Servicio', 'Vehículo', 'Kilómetros', 'Monto'];
            $sheet->fromArray($headers, NULL, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['numero_ot'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, $item['fecha_creacion'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item['cliente'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $item['servicio'] ?? 'N/A');
                $sheet->setCellValue('E' . $row, $item['vehiculo'] ?? 'N/A');
                $sheet->setCellValue('F' . $row, $item['kilometros_recorridos'] ?? 0);
                $sheet->setCellValue('G' . $row, $item['monto_total'] ?? 0);
                $row++;
            }
            break;

        case 'observaciones':
            $sheet->setTitle('Observaciones');
            $headers = ['N° OT', 'Fecha', 'Cliente', 'Servicio', 'Estado', 'Observaciones'];
            $sheet->fromArray($headers, NULL, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['numero_ot'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, $item['fecha_creacion'] ?? 'N/A');
                $sheet->setCellValue('C' . $row, $item['cliente'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $item['servicio'] ?? 'N/A');
                $sheet->setCellValue('E' . $row, $item['estado'] ?? 'N/A');
                $sheet->setCellValue('F' . $row, $item['observaciones'] ?? 'N/A');
                $row++;
            }
            break;
    }

    // Aplicar estilos a los encabezados
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];

    $lastColumn = $sheet->getHighestColumn();
    $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray($headerStyle);

    // Ajustar ancho de columnas
    foreach (range('A', $lastColumn) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Generar el archivo
    $filename = $nombreReporte . '_' . date('Y-m-d_His') . '.xlsx';
    $filepath = sys_get_temp_dir() . '/' . $filename;

    $writer = new Xlsx($spreadsheet);
    $writer->save($filepath);

    // Retornar la ruta del archivo
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>