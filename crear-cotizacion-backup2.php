<?php
// Configuración inicial
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}

include("database.php");

$mensaje = '';
$error = '';
$mostrar_modal_confirm = false;
$datos_confirmacion = [];

// Variables para subtipos y tipos de vehículo
$opciones_sub_tipo = [];
$tipo_vehiculo_actual = '';
$mostrar_camion_extra = false;
$mostrar_tracto_extra = false;

// Nuevas variables para cargos adicionales
$turnos_con_monto = [];
$tipos_camino_con_monto = [];

// Cargar turnos con sus montos
$sql_turnos = "SELECT id, nombre, monto FROM turno";
$result_turnos = $conn->query($sql_turnos);
if ($result_turnos) {
    while ($row = $result_turnos->fetch_assoc()) {
        $turnos_con_monto[] = $row;
    }
}

// Cargar tipos de camino con sus montos
$sql_tipos_camino = "SELECT id, nombre, monto FROM tipo_camino";
$result_tipos_camino = $conn->query($sql_tipos_camino);
if ($result_tipos_camino) {
    while ($row = $result_tipos_camino->fetch_assoc()) {
        $tipos_camino_con_monto[] = $row;
    }
}

// Determinar si ambas comunas son de RM
$es_rm = false;
if (!empty($_POST['id_comuna_origen']) && !empty($_POST['id_comuna_destino'])) {
    $id_comuna_origen = (int)$_POST['id_comuna_origen'];
    $id_comuna_destino = (int)$_POST['id_comuna_destino'];

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

    if (strtolower(trim($region_origen)) === 'rm metropolitana de santiago' &&
        strtolower(trim($region_destino)) === 'rm metropolitana de santiago') {
        $es_rm = true;
    }
}

// Obtener subtipos basados en el tipo de vehículo seleccionado
if (isset($_POST['id_tipo_vehiculo']) && !empty($_POST['id_tipo_vehiculo'])) {
    $id_tipo_vehiculo = (int)$_POST['id_tipo_vehiculo'];
    $sql_tipo = "SELECT Tipo FROM vehiculos WHERE id = ?";
    $stmt_tipo = $conn->prepare($sql_tipo);
    $stmt_tipo->bind_param("i", $id_tipo_vehiculo);
    $stmt_tipo->execute();
    $stmt_tipo->bind_result($tipo_vehiculo_actual);
    $stmt_tipo->fetch();
    $stmt_tipo->close();

    if ($tipo_vehiculo_actual && (stripos($tipo_vehiculo_actual, 'camión') !== false ||
                                  stripos($tipo_vehiculo_actual, 'camion') !== false ||
                                  stripos($tipo_vehiculo_actual, 'tracto') !== false)) {

        $sql_subtipo = "SELECT DISTINCT sub_tipo FROM vehiculos WHERE Tipo = ? AND sub_tipo IS NOT NULL AND sub_tipo <> ''";
        $stmt_subtipo = $conn->prepare($sql_subtipo);
        $stmt_subtipo->bind_param("s", $tipo_vehiculo_actual);
        $stmt_subtipo->execute();
        $result_sub_tipo = $stmt_subtipo->get_result();
        while ($row = $result_sub_tipo->fetch_assoc()) {
            $opciones_sub_tipo[] = $row['sub_tipo'];
        }
        $stmt_subtipo->close();
    }
}

// Mostrar campos adicionales según tipo de vehículo
if (!empty($tipo_vehiculo_actual)) {
    if (stripos($tipo_vehiculo_actual, 'camión') !== false || stripos($tipo_vehiculo_actual, 'camion') !== false) {
        $mostrar_camion_extra = true;
    } elseif (stripos($tipo_vehiculo_actual, 'tracto') !== false) {
        $mostrar_tracto_extra = true;
    }
}

// Opciones adicionales (dirección, carga)
$sql_direccion = "SELECT DISTINCT tipo_direccion FROM vehiculos WHERE tipo_direccion IS NOT NULL AND tipo_direccion <> ''";
$result_direccion = $conn->query($sql_direccion);
$opciones_direccion = [];
if ($result_direccion) {
    while ($row = $result_direccion->fetch_assoc()) {
        $opciones_direccion[] = $row['tipo_direccion'];
    }
}

$sql_carga = "SELECT DISTINCT carga FROM vehiculos WHERE carga IS NOT NULL AND carga <> '' ORDER BY carga DESC";
$result_carga = $conn->query($sql_carga);
$opciones_carga = [];
if ($result_carga) {
    while ($row = $result_carga->fetch_assoc()) {
        $opciones_carga[] = $row['carga'];
    }
}

// Procesamiento del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_division']) &&
    !isset($_POST['confirmar']) && !isset($_POST['enviar_confirmacion']) && !isset($_POST['accion'])) {
    $id_division = (int)$_POST['id_division'];
    if ($id_division <= 0) {
        $_POST['id_servicio'] = '';
    }
}

$mostrar_tab_vehiculo = false;
if (!empty($_POST['id_servicio'])) {
    $id_servicio_check = (int)$_POST['id_servicio'];
    $sql_check_servicio = "SELECT Nombre FROM servicios WHERE id = ?";
    $stmt_check = $conn->prepare($sql_check_servicio);
    $stmt_check->bind_param("i", $id_servicio_check);
    $stmt_check->execute();
    $stmt_check->bind_result($nombre_servicio_check);
    $stmt_check->fetch();
    $stmt_check->close();
    if (in_array(strtolower(trim($nombre_servicio_check)), ['remolque', 'rescate', 'despeje de via'])) {
        $mostrar_tab_vehiculo = true;
    }
}

// Configuración de costos
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

if ($costo_base_php <= 0) {
    $error .= "Error: Costo Base no configurado o es cero. ";
}
if ($costo_por_km_php <= 0) {
    $error .= "Error: Costo por km no configurado o es cero.";
}

// Procesamiento de cálculos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'calcular') {
    if (!empty($error)) {
        $mensaje = $error;
    } else {
        $precio_base = 0;
        if (!empty($_POST['id_comuna_origen']) && !empty($_POST['id_comuna_destino'])) {
            $id_comuna_origen = (int)$_POST['id_comuna_origen'];
            $id_comuna_destino = (int)$_POST['id_comuna_destino'];

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
        }

        $total_km = $es_rm ? 0 : (isset($_POST['total_km']) && is_numeric($_POST['total_km']) ? floatval($_POST['total_km']) : 0);
        if ($total_km >=0) {
            $km_excedente = max(0, $total_km - $km_base_php);
            
            if ($es_rm) {
                $monto_inicial = $precio_base + ($costo_por_km_php * $km_excedente);
            } else {
                $subtotal = $costo_base_php + ($costo_por_km_php * $km_excedente);
                $margen = $subtotal * 0.15;
                $monto_inicial = $subtotal + $margen;
            }

            // Aplicar cargo por turno (si aplica)
            if (!empty($_POST['id_turno'])) {
                $id_turno = (int)$_POST['id_turno'];
                foreach ($turnos_con_monto as $turno) {
                    if ($turno['id'] == $id_turno && $turno['monto'] > 0) {
                        $monto_inicial += $turno['monto'];
                        break;
                    }
                }
            }

            // Aplicar cargo por tipo de camino (si aplica)
            if (!empty($_POST['tipo_camino'])) {
                $id_tipo_camino = (int)$_POST['tipo_camino'];
                foreach ($tipos_camino_con_monto as $tipo_camino) {
                    if ((int)$tipo_camino['id'] === $id_tipo_camino && $tipo_camino['monto'] > 0) {
                        $monto_inicial += $tipo_camino['monto'];
                        break;
                    }
                }
            }

            $_POST['monto_inicial'] = $monto_inicial;
            $mensaje2 = "Cálculo realizado: Total KM = {$total_km}, Monto inicial = $" . number_format($monto_inicial, 2, ',', '.');
        }
    }
}

// Confirmación y guardado de datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_confirmacion'])) {
    $campos_requeridos = [
        'id_division' => 'División',
        'id_servicio' => 'Servicio',
        'id_turno' => 'Turno',
        'rut_cliente' => 'Cliente',
        'id_comuna_origen' => 'Comuna Origen',
        'id_comuna_destino' => 'Comuna Destino',
        'base_salida' => 'Base de Salida',
        'tipo_camino' => 'Tipo de Camino'
    ];
    $campos_faltantes = [];
    foreach ($campos_requeridos as $campo => $nombre) {
        if (empty($_POST[$campo])) {
            $campos_faltantes[] = $nombre;
        }
    }
    if (!empty($campos_faltantes)) {
        $error = "Error: Faltan campos obligatorios: " . implode(", ", $campos_faltantes);
    } else {
        // Recalcular precios
        $id_comuna_origen = (int)$_POST['id_comuna_origen'];
        $id_comuna_destino = (int)$_POST['id_comuna_destino'];

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
        $total_km = $es_rm ? 0 : (isset($_POST['total_km']) ? floatval($_POST['total_km']) : 0);
        $km_excedente = max(0, $total_km - $km_base_php);

        if ($es_rm) {
            $monto_inicial = $precio_base + ($costo_por_km_php * $km_excedente);
        } else {
            $subtotal = $costo_base_php + ($costo_por_km_php * $km_excedente);
            $margen = $subtotal * 0.15;
            $monto_inicial = $subtotal + $margen;
        }

        // Aplicar cargos adicionales
        $cargo_turno = 0;
        $cargo_camino = 0;

        if (!empty($_POST['id_turno'])) {
            $id_turno = (int)$_POST['id_turno'];
            foreach ($turnos_con_monto as $turno) {
                if ($turno['id'] == $id_turno && $turno['monto'] > 0) {
                    $cargo_turno = $turno['monto'];
                    $monto_inicial += $cargo_turno;
                    break;
                }
            }
        }

        if (!empty($_POST['tipo_camino'])) {
            $id_tipo_camino = (int)$_POST['tipo_camino'];
            foreach ($tipos_camino_con_monto as $tipo_camino) {
                if ((int)$tipo_camino['id'] === $id_tipo_camino && $tipo_camino['monto'] > 0) {
                    $cargo_camino = $tipo_camino['monto'];
                    $monto_inicial += $cargo_camino;
                    break;
                }
            }
        }

        $_POST['monto_inicial'] = $monto_inicial;

        $datos_confirmacion = [
            'División' => obtenerNombre('division', 'NOMBRE', (int)$_POST['id_division'], $conn),
            'Servicio' => obtenerNombre('servicios', 'Nombre', (int)$_POST['id_servicio'], $conn),
            'Cliente' => obtenerClienteNombreRut($_POST['rut_cliente'], $conn),
            'Turno' => obtenerNombre('turno', 'nombre', (int)$_POST['id_turno'], $conn),
            'Fecha Servicio' => $_POST['Fecha_servicio'] ?: 'No especificada',
            'Teléfono' => $_POST['TELEFONO'] ?? '',
            'Comuna Origen' => obtenerNombre('comunas', 'nombre', (int)$_POST['id_comuna_origen'], $conn),
            'Comuna Destino' => obtenerNombre('comunas', 'nombre', (int)$_POST['id_comuna_destino'], $conn),
            'Base de Salida' => $_POST['base_salida'],
            'Tipo de Camino' => $_POST['tipo_camino'],
            'Descripción' => $_POST['descripcion'] ?? '',
            'Panne' => $_POST['panne'] ?? '',
            'Total KM' => $total_km,
            'Precio Base' => number_format($precio_base, 2, ',', '.'),
            'Cargo por Turno' => number_format($cargo_turno, 2, ',', '.'),
            'Cargo por Tipo de Camino' => number_format($cargo_camino, 2, ',', '.'),
            'Monto Inicial (con margen)' => number_format($monto_inicial, 2, ',', '.')
        ];

        $mostrar_modal_confirm = true;
    }
}

// Guardar cotización confirmada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    $id_division = (int)$_POST['id_division'];
    $id_servicio = (int)$_POST['id_servicio'];
    $sql = "SELECT Nombre FROM servicios WHERE id = ? AND id_division = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_servicio, $id_division);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $error = "Error: El servicio no corresponde a la división";
    } else {
        $stmt->bind_result($nombre_servicio);
        $stmt->fetch();
        $stmt->close();

        $nombre_usuario = $_SESSION['NOMBRE_USUARIO'];
        $sql_coordinador = "SELECT id FROM usuarios WHERE nombre_usuario = ?";
        $stmt_coor = $conn->prepare($sql_coordinador);
        $stmt_coor->bind_param("s", $nombre_usuario);
        $stmt_coor->execute();
        $stmt_coor->bind_result($coordinador_id);
        $stmt_coor->fetch();
        $stmt_coor->close();

        if (!$coordinador_id) {
            $error = "Error: Coordinador no encontrado.";
        } else {
            // Aquí se recogen todas las variables y se inserta la cotización
            // Incluyendo `monto_turno` y `monto_tipo_camino`
            $id_turno = (int)$_POST['id_turno'];
            $rut_cliente = $_POST['rut_cliente'];
            $fecha_servicio = $_POST['Fecha_servicio'] ? DateTime::createFromFormat('Y-m-d\TH:i', $_POST['Fecha_servicio'])->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
            $telefono = $_POST['TELEFONO'] ?? '';
            $email = $_POST['email'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $panne = $_POST['panne'] ?? '';
            $tipo_camino = $_POST['tipo_camino'];
            $base_salida = $_POST['base_salida'];
            $estado = "Pendiente";
            $id_comuna_origen = (int)$_POST['id_comuna_origen'];
            $id_comuna_destino = (int)$_POST['id_comuna_destino'];
            $tipo_direccion = $_POST['tipo_direccion'] ?? null;
            $carga = $_POST['carga'] ?? null;
            $monto_inicial = $_POST['monto_inicial'] ?? null;
            $total_km = isset($_POST['total_km']) ? floatval($_POST['total_km']) : 0.0;
            $id_tipo_vehiculo = isset($_POST['id_tipo_vehiculo']) ? (int)$_POST['id_tipo_vehiculo'] : null;
            $id_sub_tipo = isset($_POST['id_sub_tipo']) ? $_POST['id_sub_tipo'] : null;

            $monto_turno = 0;
            foreach ($turnos_con_monto as $turno) {
                if ($turno['id'] == $id_turno && $turno['monto'] > 0) {
                    $monto_turno = floatval($turno['monto']);
                    break;
                }
            }

            $monto_tipo_camino = 0;
            foreach ($tipos_camino_con_monto as $tipo_camino_item) {
                if ((int)$tipo_camino_item['id'] === (int)$_POST['tipo_camino'] && $tipo_camino_item['monto'] > 0) {
                    $monto_tipo_camino = floatval($tipo_camino_item['monto']);
                    break;
                }
            }

            $sql_insert = "INSERT INTO cotizaciones (
                id_division, id_turno, rut_CLIENTE, servicio, Fecha_servicio,
                telefono_CLIENTE, email_CLIENTE, Descripcion, Panne,
                tipo_camino, base_salida, estado,
                coordinador_id, id_comuna_origen, id_comuna_destino,
                tipo_direccion, carga, monto_inicial, total_km,
                id_tipo_vehiculo, id_sub_tipo, monto_turno, monto_tipo_camino
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = $conn->prepare($sql_insert);

            if (!$stmt_insert) {
                error_log("Error al preparar la consulta: " . $conn->error);
                $error = "Error interno del sistema.";
            } else {
                $stmt_insert->bind_param("iissssssssssiiisssdisss",
                    $id_division,
                    $id_turno,
                    $rut_cliente,
                    $nombre_servicio,
                    $fecha_servicio,
                    $telefono,
                    $email,
                    $descripcion,
                    $panne,
                    $tipo_camino,
                    $base_salida,
                    $estado,
                    $coordinador_id,
                    $id_comuna_origen,
                    $id_comuna_destino,
                    $tipo_direccion,
                    $carga,
                    $monto_inicial,
                    $total_km,
                    $id_tipo_vehiculo,
                    $id_sub_tipo,
                    $monto_turno,
                    $monto_tipo_camino
                );

                if ($stmt_insert->execute()) {
                    $id_cotizacion = $conn->insert_id;
                    $mensaje = "Cotización creada correctamente. ID: " . $id_cotización;
                    error_log("PASO POR AQUÍ: inserción exitosa");
                    $_POST = [];
                } else {
                    $error = "Error al guardar cotización: " . $stmt_insert->error;
                    error_log("ERROR: " . $stmt_insert->error);
                }

                $stmt_insert->close();
            }
        }
    }
}

// Funciones auxiliares

function obtenerRegionDesdeComuna($id_comuna) {
    global $conn;
    $stmt = $conn->prepare("SELECT region FROM comunas WHERE id = ?");
    if (!$stmt) return '';
    $stmt->bind_param("i", $id_comuna);
    $stmt->execute();
    $stmt->bind_result($region);
    $stmt->fetch();
    $stmt->close();
    return $region ?? '';
}

function cargarDatosSelect($conn, $tabla, $id = 'id', $nombre = 'nombre', $where = '') {
    $datos = [];
    $sql = "SELECT $id, $nombre FROM $tabla $where ORDER BY $nombre";
    $result = $conn->query($sql);
    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $datos[] = $fila;
        }
    }
    return $datos;
}

function obtenerNombre($tabla, $campo_nombre, $id, $conn) {
    $sql = "SELECT $campo_nombre FROM $tabla WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($nombre);
    $stmt->fetch();
    $stmt->close();
    return $nombre ?: '';
}

function obtenerClienteNombreRut($rut, $conn) {
    $sql = "SELECT nombre, rut FROM clientes WHERE rut = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rut);
    $stmt->execute();
    $stmt->bind_result($nombre, $rut_cliente);
    $stmt->fetch();
    $stmt->close();
    return $nombre . " - " . $rut_cliente;
}

function cargarVehiculos($conn) {
    $vehiculos = [];
    $sql = "SELECT id, Tipo FROM vehiculos WHERE Tipo IS NOT NULL GROUP BY Tipo ORDER BY Tipo";
    $result = $conn->query($sql);
    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $vehiculos[] = $fila;
        }
    }
    return $vehiculos;
}

// Cargar datos para selects
$divisiones = cargarDatosSelect($conn, 'division', 'id', 'NOMBRE');
$turnos = cargarDatosSelect($conn, 'turno', 'id', 'nombre');
$clientes = cargarDatosSelect($conn, 'clientes', 'rut', 'nombre');
$comunas = cargarDatosSelect($conn, 'comunas', 'id', 'nombre');
$servicios = [];
if (!empty($_POST['id_division'])) {
    $id_div = (int)$_POST['id_division'];
    $sql_ser = "SELECT id, Nombre FROM servicios WHERE id_division = ? ORDER BY Nombre";
    $stmt_ser = $conn->prepare($sql_ser);
    $stmt_ser->bind_param("i", $id_div);
    $stmt_ser->execute();
    $result_ser = $stmt_ser->get_result();
    $servicios = $result_ser->fetch_all(MYSQLI_ASSOC);
    $stmt_ser->close();
}
$vehiculos = cargarVehiculos($conn);

// Mantener selecciones actuales
$sel_division = $_POST['id_division'] ?? '';
$sel_servicio = $_POST['id_servicio'] ?? '';
$sel_turno = $_POST['id_turno'] ?? '';
$sel_cliente = $_POST['rut_cliente'] ?? '';
$sel_comuna_origen = $_POST['id_comuna_origen'] ?? '';
$sel_comuna_destino = $_POST['id_comuna_destino'] ?? '';
$sel_tipocamino = $_POST['tipo_camino'] ?? '';
$pestana_activa = $_POST['pestana_activa'] ?? 'tab1';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Crear Cotización</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="css/crear-cotizaciones.css" rel="stylesheet">
</head>
<body>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>


</script>
</body>
</html>