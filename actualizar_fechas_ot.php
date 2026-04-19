<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

require_once __DIR__ . '/database.php';
// Incluye el archivo de notificaciones por correo para facturación
require_once __DIR__ . '/envio-notificacion-facturacion.php';
if (!$conn || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

$id_ot = isset($_POST['id_ot']) ? intval($_POST['id_ot']) : 0;
$fecha_aviso = isset($_POST['fecha_aviso']) && $_POST['fecha_aviso'] !== '' ? $_POST['fecha_aviso'] : null;
$fecha_llegada = isset($_POST['fecha_llegada']) && $_POST['fecha_llegada'] !== '' ? $_POST['fecha_llegada'] : null;
$fecha_termino = isset($_POST['fecha_termino']) && $_POST['fecha_termino'] !== '' ? $_POST['fecha_termino'] : null;
// Estado OT opcional con validación de valores permitidos
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : null;
$allowedEstados = [
    'En proceso',
    'En Programación',
    'Pendiente por Cerrar',
    'Finalizada',
    'Fallido',
    'Anulado'
];
if ($estado !== null && !in_array($estado, $allowedEstados, true)) {
    $estado = null; // ignorar valores no permitidos
}

// Estado de facturación opcional
$estado_facturacion = isset($_POST['estado_facturacion']) ? trim($_POST['estado_facturacion']) : null;
$allowedFact = ['Pendiente', 'Enviado', 'Facturado'];
if ($estado_facturacion !== null && !in_array($estado_facturacion, $allowedFact, true)) {
    $estado_facturacion = null;
}

// Estado de cobro opcional
$estado_cobro = isset($_POST['estado_cobro']) ? trim($_POST['estado_cobro']) : null;
$allowedCobro = ['Pendiente', 'Pagado'];
if ($estado_cobro !== null && !in_array($estado_cobro, $allowedCobro, true)) {
    $estado_cobro = null;
}

if ($id_ot <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID OT inválido']);
    exit();
}

// Normalizar formatos: de "YYYY-MM-DDTHH:MM" a "YYYY-MM-DD HH:MM:SS"
function normalize_dt($dt)
{
    if ($dt === null)
        return null;
    $dt = str_replace('T', ' ', $dt);
    // Añadir segundos si faltan
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dt)) {
        $dt .= ':00';
    }
    return $dt;
}

$fecha_aviso = normalize_dt($fecha_aviso);
$fecha_llegada = normalize_dt($fecha_llegada);
$fecha_termino = normalize_dt($fecha_termino);

// Campos de documento/pago
$forma_pago = isset($_POST['forma_pago']) ? trim($_POST['forma_pago']) : null;
$allowedForma = ['Boleta', 'Factura'];
if ($forma_pago !== null && !in_array($forma_pago, $allowedForma, true)) {
    $forma_pago = null;
}
$info_forma_pago = isset($_POST['info_forma_pago']) ? trim($_POST['info_forma_pago']) : null;
$fecha_pago = isset($_POST['fecha_pago']) && $_POST['fecha_pago'] !== '' ? trim($_POST['fecha_pago']) : null;
// Normalizar fecha_pago: aceptar "YYYY-MM-DD" o "YYYY-MM-DDTHH:MM" -> "YYYY-MM-DD"
if ($fecha_pago !== null) {
    if (strpos($fecha_pago, 'T') !== false) {
        $fecha_pago = substr(str_replace('T', ' ', $fecha_pago), 0, 10);
    } else {
        // Si viene con tiempo separado por espacio, tomar solo fecha
        if (strpos($fecha_pago, ' ') !== false) {
            $fecha_pago = explode(' ', $fecha_pago)[0];
        }
    }
}
$nombre_pagador = isset($_POST['nombre_pagador']) ? trim($_POST['nombre_pagador']) : null;

// Construir UPDATE con valores que pueden ser NULL
$sql = "UPDATE ordenes_trabajo SET 
            estado = COALESCE(?, estado),
            estado_facturacion = COALESCE(?, estado_facturacion),
            estado_cobro = COALESCE(?, estado_cobro),
            forma_pago = COALESCE(?, forma_pago),
            info_forma_pago = COALESCE(?, info_forma_pago),
            fecha_pago = COALESCE(?, fecha_pago),
            nombre_pagador = COALESCE(?, nombre_pagador),
            fecha_aviso = ?, 
            fecha_llegada = ?, 
            fecha_termino = ?, 
            Fecha_Modificacion = NOW()
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al preparar consulta: ' . $conn->error]);
    exit();
}

$stmt->bind_param('ssssssssssi', $estado, $estado_facturacion, $estado_cobro, $forma_pago, $info_forma_pago, $fecha_pago, $nombre_pagador, $fecha_aviso, $fecha_llegada, $fecha_termino, $id_ot);
$ok = $stmt->execute();

if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al ejecutar consulta: ' . $stmt->error]);
    $stmt->close();
    exit();
}

$stmt->close();

// Si el estado de facturación cambió a "Enviado", enviar notificación
if ($estado_facturacion === 'Enviado') {
    // Obtener información de la OT y cotización para la notificación
    $stmt_info = $conn->prepare(
        "SELECT ot.id_cotizacion, ot.Descripcion, c.Fecha_servicio, c.nombre_solicitante, c.telefono_CLIENTE
         FROM ordenes_trabajo ot
         INNER JOIN cotizaciones c ON ot.id_cotizacion = c.id
         WHERE ot.id = ?"
    );
    if ($stmt_info) {
        $stmt_info->bind_param('i', $id_ot);
        $stmt_info->execute();
        $stmt_info->bind_result($id_cotizacion, $descripcion_ot, $fecha_servicio, $nombre_solicitante, $telefono_contacto);
        if ($stmt_info->fetch()) {
            $stmt_info->close();
            
            // Enviar notificación de facturación
            enviarNotificacionFacturacion(
                $id_ot,
                $id_cotizacion,
                $fecha_servicio,
                $descripcion_ot,
                $nombre_solicitante,
                $telefono_contacto,
                $conn
            );
        } else {
            $stmt_info->close();
        }
    }
}

echo json_encode(['success' => true]);
?>