<?php
// calcular_monto.php
include("database.php");

// Aquí copiamos la lógica de cálculo que tienes en tu código PHP principal, pero solo el cálculo

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_comuna_origen = isset($_POST['id_comuna_origen']) ? (int)$_POST['id_comuna_origen'] : 0;
    $id_comuna_destino = isset($_POST['id_comuna_destino']) ? (int)$_POST['id_comuna_destino'] : 0;
    $total_km = isset($_POST['total_km']) && is_numeric($_POST['total_km']) ? floatval($_POST['total_km']) : 0;
    $id_turno = isset($_POST['id_turno']) ? (int)$_POST['id_turno'] : 0;
    $id_tipo_camino = isset($_POST['tipo_camino']) ? (int)$_POST['tipo_camino'] : 0;
    $rut_cliente = isset($_POST['rut_cliente']) ? trim($_POST['rut_cliente']) : '';

    if (($total_km <= 0) && $id_comuna_origen > 0 && $id_comuna_destino > 0) {
        $stmtKm = $conn->prepare("SELECT km FROM distancias WHERE id_comuna_origen = ? AND id_comuna_destino = ?");
        if ($stmtKm) {
            $stmtKm->bind_param("ii", $id_comuna_origen, $id_comuna_destino);
            $stmtKm->execute();
            $stmtKm->bind_result($kmCalc);
            if ($stmtKm->fetch()) {
                $total_km = floatval($kmCalc);
            }
            $stmtKm->close();
        }
    }

    // Verificar si comunas son RM (igual que en tu código)
    $stmt = $conn->prepare("SELECT region FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna_origen);
    $stmt->execute();
    $stmt->bind_result($region_origen);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT region FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna_destino);
    $stmt->execute();
    $stmt->bind_result($region_destino);
    $stmt->fetch();
    $stmt->close();

    $es_rm = false;
    if (strtolower(trim($region_origen)) === 'rm metropolitana de santiago' &&
        strtolower(trim($region_destino)) === 'rm metropolitana de santiago') {
        $es_rm = true;
    }

    // Obtener precios base
    $precio_base_origen = 0;
    $precio_base_destino = 0;

    $stmt = $conn->prepare("SELECT precio_base FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna_origen);
    $stmt->execute();
    $stmt->bind_result($precio_base_origen);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT precio_base FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna_destino);
    $stmt->execute();
    $stmt->bind_result($precio_base_destino);
    $stmt->fetch();
    $stmt->close();

    $precio_base = max($precio_base_origen, $precio_base_destino);

    // Obtener costos base y por km (puedes cachearlos si quieres)
    $costo_base_php = 0.0;
    $costo_por_km_php = 0.0;
    $km_base_php = 20;

    $sql_costos = "SELECT grupo, SUM(monto) AS total_monto FROM costos_variables GROUP BY grupo";
    $result_costos = $conn->query($sql_costos);
    if ($result_costos) {
        while ($row = $result_costos->fetch_assoc()) {
            $grupo = trim($row['grupo']);
            $total_monto = floatval($row['total_monto']);
            if ($grupo === 'Costo Base') {
                $costo_base_php = $total_monto;
            } elseif ($grupo === 'Costo por km') {
                $costo_por_km_php = $total_monto;
            }
        }
    }

    // Tarifa especial por cliente (si existe)
    $tiene_tarifa_especial = false;
    $te_precio_base = 0.0;
    $te_valor_km = 0.0;
    $te_tope_km = 0;
    $te_valor_custodia = 0.0;
    if ($rut_cliente !== '') {
        $stmtCli = $conn->prepare("SELECT id FROM clientes WHERE rut = ?");
        if ($stmtCli) {
            $stmtCli->bind_param("s", $rut_cliente);
            $stmtCli->execute();
            $stmtCli->bind_result($cli_id);
            $stmtCli->fetch();
            $stmtCli->close();
            if (!empty($cli_id)) {
                $stmtTe = $conn->prepare("SELECT precio_base, valor_km, tope_km, valor_custodia FROM tarifa_especial WHERE id_cliente = ?");
                if ($stmtTe) {
                    $stmtTe->bind_param("i", $cli_id);
                    $stmtTe->execute();
                    $stmtTe->bind_result($te_precio_base, $te_valor_km, $te_tope_km, $te_valor_custodia);
                    if ($stmtTe->fetch()) {
                        $tiene_tarifa_especial = (floatval($te_precio_base) > 0 || floatval($te_valor_km) > 0);
                    }
                    $stmtTe->close();
                }
            }
        }
    }

    $km_excedente = max(0, $total_km - $km_base_php);

    if ($tiene_tarifa_especial) {
        $km_cobrable = $total_km;
        if (intval($te_tope_km) > 0) {
            $km_cobrable = min($total_km, intval($te_tope_km));
        }
        $monto_inicial = floatval($te_precio_base) + ($km_cobrable * floatval($te_valor_km));
    } else if ($es_rm) {
        $monto_inicial = $precio_base + ($costo_por_km_php * $km_excedente);
    } else {
        $subtotal = $costo_base_php + ($costo_por_km_php * $km_excedente);
        $margen = $subtotal * 0.15;
        $monto_inicial = $subtotal + $margen;
    }

    // Cargar turnos y tipos de camino con monto (igual que en tu código)
    $turnos_con_monto = [];
    $tipos_camino_con_monto = [];

    $sql_turnos = "SELECT id, nombre, monto FROM turno";
    $result_turnos = $conn->query($sql_turnos);
    if ($result_turnos) {
        while ($row = $result_turnos->fetch_assoc()) {
            $turnos_con_monto[] = $row;
        }
    }

    $sql_tipos_camino = "SELECT id, monto FROM tipo_camino";
    $result_tipos_camino = $conn->query($sql_tipos_camino);
    if ($result_tipos_camino) {
        while ($row = $result_tipos_camino->fetch_assoc()) {
            $tipos_camino_con_monto[] = $row;
        }
    }

    // Cargar costos variables en un mapa para buscar coincidencias por nombre (ej: Turno Noche)
    $mapa_costos_variables = [];
    // Re-usamos la query de costos pero guardamos todo
    $result_costos->data_seek(0); // Volver al inicio del result set
    while ($row = $result_costos->fetch_assoc()) {
        $nombre_cv = trim($row['Nombre'] ?? ''); // Asumiendo que la columna es Nombre o nombre
        if (!$nombre_cv && isset($row['nombre'])) $nombre_cv = trim($row['nombre']);
        
        $monto_cv = floatval($row['total_monto']); // En el query anterior era SUM(monto). Si se necesita por nombre individual, ajustar query.
        // El query anterior agrupaba por GRUPO. Eso no nos sirve para buscar por NOMBRE específico (ej: "Noche").
        // Necesitamos un query adicional o modificar el anterior.
    }

    // MODIFICACION: Consultar costos variables completos para mapeo por nombre
    $sql_cv_det = "SELECT Nombre, monto FROM costos_variables";
    $res_cv_det = $conn->query($sql_cv_det);
    if ($res_cv_det) {
        while ($r = $res_cv_det->fetch_assoc()) {
            $n = mb_strtolower(trim($r['Nombre']), 'UTF-8');
            $mapa_costos_variables[$n] = floatval($r['monto']);
        }
    }

    // Aplicar cargo por turno
    if ($id_turno > 0) {
        // Necesitamos el nombre del turno para buscarlo en costos variables
        // La query de turnos arriba debe incluir 'nombre'
        foreach ($turnos_con_monto as $turno) {
            if ($turno['id'] == $id_turno) {
                $monto_turno_final = floatval($turno['monto']);
                
                // Intentar buscar override en costos variables
                $nombre_turno_norm = mb_strtolower(trim($turno['nombre'] ?? ''), 'UTF-8');
                
                // Logica de coincidencia: Si existe una entrada en costos_variables con el mismo nombre
                if (isset($mapa_costos_variables[$nombre_turno_norm])) {
                     $monto_turno_final = $mapa_costos_variables[$nombre_turno_norm];
                }
                
                if ($monto_turno_final > 0) {
                    $monto_inicial += $monto_turno_final;
                }
                break;
            }
        }
    }

    // Aplicar cargo por tipo camino
    if ($id_tipo_camino > 0) {
        foreach ($tipos_camino_con_monto as $tipo_camino) {
            if ($tipo_camino['id'] == $id_tipo_camino && $tipo_camino['monto'] > 0) {
                // CORRECCION: El monto es unitario por KM
                // Usamos $total_km para el cálculo, o $km_excedente si fuera lo requerido (pero usualmente es total)
                // Asumimos total_km ya que "tipo de camino" suele aplicar a todo el trayecto
                $monto_inicial += ($tipo_camino['monto'] * $total_km);
                break;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'monto' => round($monto_inicial),
        'tarifa_especial' => $tiene_tarifa_especial ? 1 : 0
    ]);
    exit();

}

echo json_encode(['success' => false, 'error' => 'Método no permitido']);
exit();
