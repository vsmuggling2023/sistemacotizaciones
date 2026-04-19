<?php
header('Content-Type: application/json; charset=utf-8');
include("database.php");



$limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina < 1)
    $pagina = 1;
$offset = ($pagina - 1) * $limite;

// Filtros adicionales
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$desc = isset($_GET['desc']) ? trim($_GET['desc']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';
$rango = isset($_GET['rango']) ? (int) $_GET['rango'] : 0;

$whereParts = [];

if ($id > 0) {
    $whereParts[] = "c.id = $id";
}

if ($desc !== '') {
    $descEsc = $conn->real_escape_string($desc);
    $whereParts[] = "(cl.NOMBRE LIKE '%$descEsc%' OR c.Descripcion LIKE '%$descEsc%' OR c.rut_CLIENTE LIKE '%$descEsc%')";
}

if ($estado !== '') {
    $estadoEsc = $conn->real_escape_string($estado);
    $whereParts[] = "c.estado = '$estadoEsc'";
}

if ($rango && ($from !== '' || $to !== '')) {
    if ($from !== '') {
        $fromEsc = $conn->real_escape_string($from . ' 00:00:00');
        $whereParts[] = "c.Fecha_servicio >= '$fromEsc'";
    }
    if ($to !== '') {
        $toEsc = $conn->real_escape_string($to . ' 23:59:59');
        $whereParts[] = "c.Fecha_servicio <= '$toEsc'";
    }
}

$where = !empty($whereParts) ? ' WHERE ' . implode(' AND ', $whereParts) : '';

$sql_total = "SELECT COUNT(DISTINCT c.id) AS total FROM cotizaciones c LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT" . $where;
$total_resultado = $conn->query($sql_total);
$total_filas = 0;
if ($total_resultado instanceof mysqli_result) {
    $rowT = $total_resultado->fetch_assoc();
    if ($rowT && isset($rowT['total'])) {
        $total_filas = (int) $rowT['total'];
    }
}
$total_paginas = ceil($total_filas / $limite);


// Primero obtener los IDs únicos con filtros
$sql_ids = "SELECT DISTINCT c.id 
            FROM cotizaciones c
            LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
            $where
            ORDER BY c.id DESC
            LIMIT $limite OFFSET $offset";

$resultado_ids = $conn->query($sql_ids);
if ($resultado_ids === false) {
    http_response_code(200);
    echo json_encode(["cotizaciones" => [], "total_filas" => $total_filas, "pagina" => $pagina, "error" => $conn->error]);
    exit;
}

// Obtener los IDs
$ids = [];
while ($row = $resultado_ids->fetch_assoc()) {
    $ids[] = $row['id'];
}

// Si no hay IDs, retornar vacío
if (empty($ids)) {
    echo json_encode([
        "cotizaciones" => [],
        "total_filas" => $total_filas,
        "total_paginas" => $total_paginas,
        "pagina" => $pagina,
        "filtros_aplicados" => count($whereParts)
    ]);
    exit;
}

// Ahora obtener los detalles completos solo para esos IDs
$ids_str = implode(',', $ids);
$sql = "SELECT 
            c.id,
            c.Fecha_servicio,
            c.rut_CLIENTE,
            c.email_CLIENTE,
            cl.NOMBRE AS nombre_cliente,
            c.Descripcion AS descripcion,
            c.monto_inicial,
            c.base_salida,
            c.estado,
            u.NOMBRE_USUARIO AS coordinador,
            co.nombre AS comuna_origen,
            cd.nombre AS comuna_destino,
            c.monto_final,
            c.nombre_solicitante
        FROM cotizaciones c
        LEFT JOIN (
            SELECT c1.RUT, c1.NOMBRE
            FROM clientes c1
            INNER JOIN (
                SELECT RUT, MIN(ID) as min_id
                FROM clientes
                GROUP BY RUT
            ) c2 ON c1.RUT = c2.RUT AND c1.ID = c2.min_id
        ) cl ON c.rut_CLIENTE = cl.RUT
        LEFT JOIN usuarios u ON c.coordinador_id = u.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        WHERE c.id IN ($ids_str)
        ORDER BY c.id DESC";

$resultado = $conn->query($sql);
if ($resultado === false) {
    http_response_code(200);
    echo json_encode(["cotizaciones" => [], "total_filas" => $total_filas, "pagina" => $pagina, "error" => $conn->error]);
    exit;
}

$data = [];
while ($row = $resultado->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "cotizaciones" => $data,
    "total_filas" => $total_filas,
    "total_paginas" => $total_paginas,
    "pagina" => $pagina,
    "filtros_aplicados" => count($whereParts)
]);
