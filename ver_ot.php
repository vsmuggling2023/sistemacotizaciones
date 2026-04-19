<?php
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}

include("database.php");
require_once(__DIR__ . '/FPDF-1.8.6-NOTOCAR/fpdf.php');
require_once(__DIR__ . '/FPDI-2.6.3-NOTOCAR/src/autoload.php');
use setasign\Fpdi\Fpdi;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido");
}

$id = intval($_GET['id']);

$sql = "SELECT 
            ot.id AS ot_id,
            ot.id_cotizacion,
            ot.Descripcion AS ot_descripcion,
            ot.Fecha_creacion AS ot_fecha_creacion,
            ot.estado AS ot_estado,
            ot.info_marca,
            ot.info_modelo,
            ot.info_patente,
            ot.info_chofer,
            ot.info_contacto,
            ot.info_telefono,
            ot.fecha_aviso,
            ot.fecha_llegada,
            ot.fecha_termino,
            ot.fecha_documento,
            ot.documento_tributario,
            ot.forma_pago,
            ot.info_forma_pago,
            ot.estado_facturacion,
            ot.estado_cobro,
            ot.fecha_pago,
            ot.nombre_pagador,
            ot.num_caso,
            c.id AS cotizacion_id,
            c.Fecha_servicio,
            c.rut_CLIENTE,
            COALESCE(c.nombre_solicitante, '-') AS nombre_solicitante,
            cl.NOMBRE AS nombre_cliente,
            cl.RUT AS cliente_rut,
            cl.GIRO AS cliente_giro,
            cl.DIRECCION AS cliente_direccion,
            cl.TELEFONO AS cliente_telefono,
            cl.EMAIL AS cliente_email,
            COALESCE(c.email_CLIENTE, '') AS email_CLIENTE,
            c.telefono_CLIENTE,
            s.Nombre AS servicio_nombre,
            c.total_km,
            c.panne,
            c.descripcion,
            c.monto_final,
            c.base_salida,
            c.estado,
            u.NOMBRE_USUARIO AS coordinador,
            u.TELEFONO AS telefono_coordinador,
            u.EMAIL AS correo_coordinador,
            co.nombre AS comuna_origen,
            cd.nombre AS comuna_destino,
            COALESCE(c.direccion_origen, '-') AS direccion_origen,
            COALESCE(c.direccion_destino, '-') AS direccion_destino,
            d.NOMBRE AS division_nombre
        FROM ordenes_trabajo ot
        LEFT JOIN cotizaciones c ON ot.id_cotizacion = c.id
        LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
        LEFT JOIN usuarios u ON c.coordinador_id = u.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        LEFT JOIN division d ON c.id_division = d.id
        LEFT JOIN servicios s ON c.id_servicio = s.id
        WHERE ot.id = $id
        LIMIT 1";

$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

// Si no se encuentra por ID de OT, buscar por ID de cotización y redirigir
if ($result->num_rows == 0) {
    $check_sql = "SELECT id FROM ordenes_trabajo WHERE id_cotizacion = $id LIMIT 1";
    $check_result = $conn->query($check_sql);

    if ($check_result && $check_result->num_rows > 0) {
        $ot_row = $check_result->fetch_assoc();
        $ot_id = $ot_row['id'];
        // Redirigir a la URL con el ID de orden de trabajo
        header("Location: ver_ot.php?id=" . $ot_id);
        exit();
    }

    die("No se encontró ninguna orden de trabajo con el ID $id");
}

$row = $result->fetch_assoc();

// Buscar asignación para determinar si es Empleado o Asociado
$texto_tipo_asignacion = '';
$sql_asign = "SELECT id_empleado, id_asociado 
              FROM asignaciones 
              WHERE id_ot = ? 
              ORDER BY id DESC LIMIT 1";

$stmt_a = $conn->prepare($sql_asign);
if ($stmt_a) {
    $stmt_a->bind_param("i", $row['ot_id']);
    $stmt_a->execute();
    $res_a = $stmt_a->get_result();
    if ($row_a = $res_a->fetch_assoc()) {
        if (!empty($row_a['id_asociado'])) {
            // Es Asociado
            $texto_tipo_asignacion = "Asociado";
        } elseif (!empty($row_a['id_empleado'])) {
            // Es Empleado
            $texto_tipo_asignacion = "Empleado";
        }
    }
    $stmt_a->close();
}

// Helper para forzar mayúsculas en UTF-8 y convertir a ISO-8859-1 para FPDF
function pdf_upper($value, $fallback = '-')
{
    $s = isset($value) && $value !== '' ? (string) $value : (string) $fallback;
    if (function_exists('mb_strtoupper')) {
        $s = mb_strtoupper($s, 'UTF-8');
    } else {
        $s = strtoupper($s);
    }
    return utf8_decode($s);
}

// Helper para mayúsculas + trim y ajuste a ancho máximo (con "...")
function pdf_upper_fit($pdf, $value, $fallback = '-', $maxWidthMm = 120)
{
    $s = isset($value) && $value !== '' ? (string) $value : (string) $fallback;
    $s = trim($s);
    if (function_exists('mb_strtoupper')) {
        $s = mb_strtoupper($s, 'UTF-8');
    } else {
        $s = strtoupper($s);
    }
    $encoded = utf8_decode($s);
    $width = $pdf->GetStringWidth($encoded);
    if ($width <= $maxWidthMm) {
        return $encoded;
    }
    // Truncar con búsqueda binaria para ajustar al ancho y agregar "..."
    $len = function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
    $low = 0;
    $high = $len;
    $best = utf8_decode('...');
    while ($low <= $high) {
        $mid = intdiv($low + $high, 2);
        $substr = function_exists('mb_substr') ? mb_substr($s, 0, $mid, 'UTF-8') : substr($s, 0, $mid);
        $candidate = utf8_decode($substr . '...');
        if ($pdf->GetStringWidth($candidate) <= $maxWidthMm) {
            $best = $candidate;
            $low = $mid + 1;
        } else {
            $high = $mid - 1;
        }
    }
    return $best;
}

$pdf_base = "pdf/ot.pdf";
if (!file_exists($pdf_base)) {
    die("Plantilla PDF no encontrada");
}

$pdf = new Fpdi();
$pdf->AddPage();
$pdf->setSourceFile($pdf_base);
$tplIdx = $pdf->importPage(1);
$pdf->useTemplate($tplIdx, 0, 0);

$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(0, 0, 0);

$lineHeight = 8;
$pdf->SetXY(75, 24);
$pdf->Cell(40, $lineHeight, utf8_decode("Orden de Trabajo N°:"));
$pdf->SetXY(125, 23);
$pdf->Write(10, utf8_decode("" . $row['ot_id']));


$pdf->SetFont('Arial', '', 10);
$lineHeight = 8;
if (!isset($lineHeight) || !is_numeric($lineHeight) || $lineHeight <= 0) {
    $lineHeight = 8;
}

//Datos de Cliente
$pdf->SetXY(74, 41.75);
$pdf->Write($lineHeight, pdf_upper($row['nombre_cliente']));

$pdf->SetXY(74, 46.75);
$pdf->Write($lineHeight, pdf_upper($row['rut_CLIENTE']));

$pdf->SetXY(74, 51.75);
$pdf->Write($lineHeight, pdf_upper($row['nombre_solicitante']));

$pdf->SetXY(74, 56.75);
$pdf->Write($lineHeight, pdf_upper($row['email_CLIENTE']));

$pdf->SetXY(74, 61.75);
$pdf->Write($lineHeight, pdf_upper($row['telefono_CLIENTE']));

//Datos de Orden de Trabajo
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetXY(74, 80.5);
$pdf->Write($lineHeight, pdf_upper($row['division_nombre']));

$pdf->SetXY(74, 85.5);
$pdf->Write($lineHeight, pdf_upper($row['servicio_nombre']));

$pdf->SetXY(74, 90.5);
$pdf->Write($lineHeight, pdf_upper($row['comuna_origen']));

$pdf->SetXY(74, 95.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['direccion_origen'], '-', 120));

$pdf->SetXY(74, 100.5);
$pdf->Write($lineHeight, pdf_upper($row['comuna_destino']));

$pdf->SetXY(74, 105.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['direccion_destino'], '-', 120));

$pdf->SetXY(74, 110.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['info_marca'], '-', 120));

$pdf->SetXY(74, 115.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['info_modelo'], '-', 120));

$pdf->SetXY(74, 120.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['info_patente'], '-', 120));

$pdf->SetXY(74, 125.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['info_contacto'], '-', 120));

$pdf->SetXY(74, 130.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['info_telefono'], '-', 120));

$pdf->SetXY(74, 135.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['num_caso'], '-', 120));


// Fecha de servicio
$fechaServicio = '-';
if (!empty($row['Fecha_servicio'])) {
    $fechaServicio = date('d-m-Y', strtotime($row['Fecha_servicio']));
}
$pdf->SetXY(60, 145.5);
$pdf->Write($lineHeight, $fechaServicio);

// CONFIGURACION: Etiqueta de Tipo de Asignación (Empleado/Asociado)
if (!empty($texto_tipo_asignacion)) {
    $pdf->SetFont('Helvetica', '', 9); // Fuente en negrita para destacar
    $pdf->SetXY(117, 145.5); // Ajustado a la derecha (junto a Asignación) y misma altura que fecha
    $pdf->Write($lineHeight, utf8_decode($texto_tipo_asignacion));
    $pdf->SetFont('Helvetica', '', 9); // Volver a fuente normal
}


$pdf->SetXY(18, 159.5);
$pdf->Write($lineHeight, pdf_upper($row['descripcion']));


$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetXY(36, 191.5);
$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['total_km'], '-', 120));

// Calculo de Neto e IVA
$neto = round($row['monto_final'] / 1.19);
$iva = $row['monto_final'] - $neto;

$pdf->SetXY(74, 191.5);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($neto, 0, ',', '.')));

$pdf->SetXY(118, 191.5);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($iva, 0, ',', '.')));

$pdf->SetXY(162, 191.5);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($row['monto_final'], 0, ',', '.')));

//Datos de OC
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetXY(74, 208.5);
$pdf->Cell(40, $lineHeight, utf8_decode("76.785.917-1"));

$pdf->SetXY(74, 213.5);
$pdf->Cell(40, $lineHeight, utf8_decode("EMPRESA SPA"));

$pdf->SetXY(74, 218.5);
$pdf->Cell(40, $lineHeight, utf8_decode("SANTANDER"));

$pdf->SetXY(74, 223.5);
$pdf->Cell(40, $lineHeight, utf8_decode("CUENTA CORRIENTE"));

$pdf->SetXY(74, 228.5);
$pdf->Cell(40, $lineHeight, utf8_decode("71944026"));

$pdf->SetXY(74, 233.5);
$pdf->Cell(40, $lineHeight, utf8_decode("contacto@empresa.com"));

//Contacto
$pdf->SetFont('Helvetica', '', 9.5);
$pdf->SetXY(74, 250.5);
$pdf->Write($lineHeight, pdf_upper($row['coordinador'], 'NO ASIGNADO'));

$pdf->SetXY(74, 255.5);
$pdf->Write($lineHeight, utf8_decode('+569 ' . ($row['telefono_coordinador'] ?? '-')));

$pdf->SetXY(74, 260.5);
$pdf->Write($lineHeight, pdf_upper($row['telefono_coordinador']));

$pdf->SetFont('Helvetica', '', 8.5);
$pdf->SetXY(74, 265.9);
$pdf->Write($lineHeight, pdf_upper($row['correo_coordinador']));

$bufferLen = function_exists('ob_get_length') ? ob_get_length() : 0;
if ($bufferLen) {
    // Evitar el error de FPDF por salida previa (p.ej., notices)
    ob_end_clean();
}
$pdf->Output('I', "orden_trabajo_{$row['ot_id']}.pdf"); // Mostrar inline en navegador
exit;
