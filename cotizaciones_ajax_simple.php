<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

include("database.php");

if (!$conn) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit();
}

try {
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    
    if ($pagina < 1) $pagina = 1;
    $offset = ($pagina - 1) * $limite;

    // Contar total
    $sql_count = "SELECT COUNT(*) AS total FROM cotizaciones";
    $result_count = $conn->query($sql_count);
    $total = $result_count->fetch_assoc()['total'];

    // Consulta simple
    $sql = "SELECT 
                c.id,
                c.Fecha_servicio,
                c.rut_CLIENTE,
                c.email_CLIENTE,
                c.servicio,
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
            LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.rut
            LEFT JOIN usuarios u ON c.coordinador_id = u.id
            LEFT JOIN comunas co ON c.id_comuna_origen = co.id
            LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
            ORDER BY c.id DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conn->error);
    }

    $stmt->bind_param("ii", $limite, $offset);
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
            'servicio' => $row['servicio'],
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

    echo json_encode([
        'success' => true,
        'total_filas' => $total,
        'cotizaciones' => $datos,
        'pagina_actual' => $pagina,
        'limite' => $limite
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'total_filas' => 0,
        'cotizaciones' => []
    ]);
}
?>
