<?php
session_start();
ob_start(); // Inicia el buffer de salida
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 🧩 Verifica sesión
if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

require_once("database.php");

if (!$conn) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    if ($pagina < 1) $pagina = 1;
    $offset = ($pagina - 1) * $limite;
    
    // Construir condición de búsqueda:
    // - Si es numérico: buscar por ID exacto (un solo registro)
    // - Si no: buscar por RUT exacto, ignorando puntos y guión (todos los registros de ese RUT)
    $where = '';
    $params = [];
    $types = '';
    if ($buscar !== '') {
        if (ctype_digit($buscar)) {
            // Búsqueda por ID exacto
            $where = "WHERE c.id = ?";
            $params = [(int)$buscar];
            $types = 'i';
        } else {
            // Búsqueda por RUT exacto ignorando formato
            $where = "WHERE REPLACE(REPLACE(c.rut_CLIENTE, '.', ''), '-', '') = REPLACE(REPLACE(?, '.', ''), '-', '')";
            $params = [$buscar];
            $types = 's';
        }
    }

    // 🔍 Contar registros (con o sin filtro)
    $sql_count = "SELECT COUNT(*) AS total FROM cotizaciones c
                  LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.rut
                  LEFT JOIN usuarios u ON c.coordinador_id = u.id
                  LEFT JOIN comunas co ON c.id_comuna_origen = co.id
                  LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
                  $where";

    if ($where) {
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param($types, ...$params);
        $stmt_count->execute();
        $total = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt_count->close();
    } else {
        $total = $conn->query($sql_count)->fetch_assoc()['total'] ?? 0;
    }

    // 📋 Consulta principal (con o sin filtro)
    $sql = "SELECT 
                c.id,
                c.Fecha_servicio,
                c.rut_CLIENTE,
                c.email_CLIENTE,
                c.id_servicio,
                s.Nombre AS servicio_nombre,
                c.monto_inicial,
                c.monto_final,
                c.base_salida,
                c.estado,
                c.nombre_solicitante,
                c.total_km,
                c.tipo_direccion,
                c.carga,
                c.monto_turno,
                c.monto_tipo_camino,
                cl.nombre AS nombre_cliente,
                u.nombre_usuario AS coordinador,
                co.nombre AS comuna_origen,
                cd.nombre AS comuna_destino
            FROM cotizaciones c
            LEFT JOIN servicios s ON s.id = c.id_servicio
            LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.rut
            LEFT JOIN usuarios u ON c.coordinador_id = u.id
            LEFT JOIN comunas co ON c.id_comuna_origen = co.id
            LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
            $where
            ORDER BY c.id DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if ($where) {
        $typesWithLimit = $types . 'ii';
        $paramsWithLimit = array_merge($params, [$limite, $offset]);
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    } else {
        $stmt->bind_param("ii", $limite, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = [
            'id' => $row['id'],
            'Fecha_servicio' => $row['Fecha_servicio'],
            'rut_CLIENTE' => $row['rut_CLIENTE'],
            'email_CLIENTE' => $row['email_CLIENTE'],
            'nombre_cliente' => $row['nombre_cliente'] ?? 'Sin nombre',
            'servicio' => $row['servicio_nombre'] ?? '',
            'monto_inicial' => $row['monto_inicial'] ?? 0,
            'monto_final' => $row['monto_final'] ?? 0,
            'base_salida' => $row['base_salida'],
            'estado' => $row['estado'] ?? 'Pendiente',
            'coordinador' => $row['coordinador'] ?? 'Sin coordinador',
            'comuna_origen' => $row['comuna_origen'] ?? 'Sin especificar',
            'comuna_destino' => $row['comuna_destino'] ?? 'Sin especificar',
            'nombre_solicitante' => $row['nombre_solicitante'] ?? 'Sin especificar',
            'total_km' => $row['total_km'] ?? 0,
            'tipo_direccion' => $row['tipo_direccion'] ?? null,
            'carga' => $row['carga'] ?? null,
            'monto_turno' => $row['monto_turno'] ?? 0,
            'monto_tipo_camino' => $row['monto_tipo_camino'] ?? 0
        ];
    }

    $stmt->close();

    // 🔧 Limpia y envía JSON
    ob_clean();
    echo json_encode([
        'success' => true,
        'total_filas' => $total,
        'cotizaciones' => $datos,
        'pagina_actual' => $pagina,
        'limite' => $limite
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'total_filas' => 0,
        'cotizaciones' => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
