<?php
include('../database.php');
header('Content-Type: application/json');

$id_comuna_origen = (int)($_POST['id_comuna_origen'] ?? 0);
$id_comuna_destino = (int)($_POST['id_comuna_destino'] ?? 0);
$total_km = floatval($_POST['total_km'] ?? 0);
$id_turno = (int)($_POST['id_turno'] ?? 0);
$id_tipo_camino = (int)($_POST['tipo_camino'] ?? 0);
$km_base_php = 20;
$costo_base_php = 0;
$costo_por_km_php = 0;
$monto_inicial = 0;

// Costos base
$sql_costos = "SELECT grupo, SUM(monto) AS total_monto FROM costos_variables GROUP BY grupo";
$result_costos = $conn->query($sql_costos);
while ($row = $result_costos->fetch_assoc()) {
    if ($row['grupo'] === 'Costo Base') $costo_base_php = floatval($row['total_monto']);
    if ($row['grupo'] === 'Costo por km') $costo_por_km_php = floatval($row['total_monto']);
}

// Precios base comunas
$stmt = $conn->prepare("SELECT precio_base FROM comunas WHERE id = ?");
$stmt->bind_param("i", $id_comuna_origen);
$stmt->execute();
$stmt->bind_result($pb_origen);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT precio_base FROM comunas WHERE id = ?");
$stmt->bind_param("i", $id_comuna_destino);
$stmt->execute();
$stmt->bind_result($pb_destino);
$stmt->fetch();
$stmt->close();

$precio_base = max($pb_origen, $pb_destino);
$km_excedente = max(0, $total_km - $km_base_php);
$es_rm = false;

// Regiones
function getRegion($id_comuna, $conn) {
    $stmt = $conn->prepare("SELECT region FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna);
    $stmt->execute();
    $stmt->bind_result($region);
    $stmt->fetch();
    $stmt->close();
    return strtolower(trim($region));
}
if (getRegion($id_comuna_origen, $conn) === 'rm metropolitana de santiago' &&
    getRegion($id_comuna_destino, $conn) === 'rm metropolitana de santiago') {
    $es_rm = true;
}

if ($es_rm) {
    $monto_inicial = $precio_base + ($costo_por_km_php * $km_excedente);
} else {
    $subtotal = $costo_base_php + ($costo_por_km_php * $km_excedente);
    $monto_inicial = $subtotal + ($subtotal * 0.15);
}

// Turno
$stmt = $conn->prepare("SELECT monto FROM turno WHERE id = ?");
$stmt->bind_param("i", $id_turno);
$stmt->execute();
$stmt->bind_result($monto_turno);
$stmt->fetch();
$stmt->close();
$monto_inicial += floatval($monto_turno);

// Tipo camino
$stmt = $conn->prepare("SELECT monto FROM tipo_camino WHERE id = ?");
$stmt->bind_param("i", $id_tipo_camino);
$stmt->execute();
$stmt->bind_result($monto_camino);
$stmt->fetch();
$stmt->close();
$monto_inicial += floatval($monto_camino);

echo json_encode([
    'monto_inicial' => $monto_inicial,
    'total_km' => $total_km,
    'precio_base' => $precio_base,
    'km_excedente' => $km_excedente
]);
