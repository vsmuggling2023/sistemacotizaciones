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
    die("ID de cotización inválido");
}

$id_cotizacion = intval($_GET['id']);

$sql = "SELECT 
            c.id,
            c.Fecha_servicio,
            c.rut_CLIENTE,
            COALESCE(c.nombre_solicitante, '-') AS nombre_solicitante,
            cl.nombre AS nombre_cliente,
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
        FROM cotizaciones c
        LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.rut
        LEFT JOIN usuarios u ON c.coordinador_id = u.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        LEFT JOIN division d ON c.id_division = d.id
        LEFT JOIN servicios s ON c.id_servicio = s.id
        WHERE c.id = $id_cotizacion
        LIMIT 1
        ";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("Cotización no encontrada");
}
$row = $result->fetch_assoc();
// Helper para forzar mayúsculas en UTF-8 y convertir a ISO-8859-1 para FPDF
function pdf_upper($value, $fallback = '-') {
    $s = isset($value) && $value !== '' ? (string)$value : (string)$fallback;
    if (function_exists('mb_strtoupper')) {
        $s = mb_strtoupper($s, 'UTF-8');
    } else {
        $s = strtoupper($s);
    }
    return utf8_decode($s);
}

// Helper para mayúsculas + trim y ajuste a ancho máximo (con "...")
function pdf_upper_fit($pdf, $value, $fallback = '-', $maxWidthMm = 120) {
    $s = isset($value) && $value !== '' ? (string)$value : (string)$fallback;
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
    $low = 0; $high = $len; $best = utf8_decode('...');
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

$pdf_base = "pdf/cotizacion.pdf";
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
$pdf->SetXY(75, 24);
$pdf->Cell(40, $lineHeight, utf8_decode("Cotización N°:"));
$pdf->SetXY(115, 19);
$pdf->Write(10, utf8_decode("" . $row['id']));

$pdf->SetFont('Helvetica', '', 9);
$lineHeight = 8;
if (!isset($lineHeight) || !is_numeric($lineHeight) || $lineHeight <= 0) {
    $lineHeight = 8;
}
//Datos de Cliente
$pdf->SetXY(64, 49.75);
$pdf->Write($lineHeight, pdf_upper($row['nombre_cliente']));

$pdf->SetXY(64, 54.5);
$pdf->Write($lineHeight, pdf_upper($row['rut_CLIENTE']));

$pdf->SetXY(64, 59.5);
$pdf->Write($lineHeight, pdf_upper($row['nombre_solicitante']));

$pdf->SetXY(64, 64);
$pdf->Write($lineHeight, pdf_upper($row['email_CLIENTE']));

$pdf->SetXY(64, 69);
$pdf->Write($lineHeight, pdf_upper($row['telefono_CLIENTE']));

//Datos de Cotización

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetXY(64, 83);
$pdf->Write($lineHeight, pdf_upper($row['division_nombre']));

$pdf->SetXY(64, 88);
$pdf->Write($lineHeight, pdf_upper($row['comuna_origen']));

$pdf->SetXY(64, 93);

$pdf->Write($lineHeight, pdf_upper($row['servicio_nombre']));
$pdf->SetXY(64, 98);

$pdf->Write($lineHeight, pdf_upper_fit($pdf, $row['direccion_origen'], '-', 120));
$pdf->SetXY(64, 102.5);

$pdf->Write($lineHeight, pdf_upper($row['panne']));
$pdf->SetXY(64, 107.5);

$pdf->Write($lineHeight, pdf_upper($row['descripcion']));
$pdf->SetFont('Helvetica', '', 10);

$pdf->SetXY(64, 112.5);
$pdf->Write($lineHeight, utf8_decode($row['total_km'] ?: '-'));

$pdf->SetXY(64, 117.5);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($row['monto_final'], 0, ',', '.')));

// Calculo de Neto e IVA

$pdf->SetXY(37, 141);
$pdf->Write($lineHeight, utf8_decode($row['total_km'] ?: '-'));

$neto = round($row['monto_final'] / 1.19);
$iva = $row['monto_final'] - $neto;
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetXY(75, 141);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($neto, 0, ',', '.')));

$pdf->SetXY(120, 141);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($iva, 0, ',', '.')));

$pdf->SetXY(165, 141);
$pdf->Write($lineHeight, utf8_decode("$" . number_format($row['monto_final'], 0, ',', '.')));

//Datos de OC
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetXY(64, 161.5);
$pdf->Cell(40, $lineHeight, utf8_decode("76.785.917-1"));

$pdf->SetXY(64, 166.5);
$pdf->Cell(40, $lineHeight, utf8_decode("EMPRESA SPA"));
$pdf->SetXY(64, 171.5);
$pdf->Cell(40, $lineHeight, utf8_decode("SAN ANTONIO DE PADUA 11160, MAIPÚ"));

$pdf->SetXY(64, 176);
$pdf->Cell(40, $lineHeight, utf8_decode("SERVICIOS DE EMERGENCIA"));

$pdf->SetXY(64, 181);
$pdf->Cell(40, $lineHeight, utf8_decode("2 2611 0330"));

$pdf->SetXY(64, 186.5);
$pdf->Cell(40, $lineHeight, utf8_decode("contacto@empresa.com"));

//Contacto

$pdf->SetFont('Helvetica', '', 9.5);
$pdf->SetXY(64, 204); 
$pdf->Write($lineHeight, pdf_upper($row['coordinador'], 'NO ASIGNADO'));
$pdf->SetXY(64, 209);
$pdf->Write($lineHeight, utf8_decode('+569 ' . ($row['telefono_coordinador'] ?? '-')));
$pdf->SetXY(64, 214);
$pdf->Write($lineHeight, pdf_upper($row['telefono_coordinador']));

$pdf->SetFont('Helvetica', '', 8.5);
$pdf->SetXY(64, 219);
$pdf->Write($lineHeight, pdf_upper($row['correo_coordinador']));

$bufferLen = function_exists('ob_get_length') ? ob_get_length() : 0;
if ($bufferLen) {
    // Evitar el error de FPDF por salida previa (p.ej., notices)

    ob_end_clean();

}

$pdf->Output('I', "cotizacion_{$row['id']}.pdf"); // Mostrar inline en navegador
exit;

