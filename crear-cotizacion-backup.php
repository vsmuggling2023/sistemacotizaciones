<?php
// Configuración inicial
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
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

// --- INICIO: Determinar si ambas comunas son de RM ---
$es_rm = false;

if (!empty($_POST['id_comuna_origen']) && !empty($_POST['id_comuna_destino'])) {
    $id_comuna_origen = (int)$_POST['id_comuna_origen'];
    $id_comuna_destino = (int)$_POST['id_comuna_destino'];

    // Obtener región de comuna origen
    $stmt = $conn->prepare("SELECT region FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna_origen);
    $stmt->execute();
    $stmt->bind_result($region_origen);
    $stmt->fetch();
    $stmt->close();

    // Obtener región de comuna destino
    $stmt = $conn->prepare("SELECT region FROM comunas WHERE id = ?");
    $stmt->bind_param("i", $id_comuna_destino);
    $stmt->execute();
    $stmt->bind_result($region_destino);
    $stmt->fetch();
    $stmt->close();

    if (strtolower(trim($region_origen)) === 'rm metropolitana de santiago' && strtolower(trim($region_destino)) === 'rm metropolitana de santiago') {
        $es_rm = true;
    }
}
// --- FIN: Determinar si ambas comunas son de RM ---

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

// Configurar flags para mostrar campos adicionales
if (!empty($tipo_vehiculo_actual)) {
    if (stripos($tipo_vehiculo_actual, 'camión') !== false || stripos($tipo_vehiculo_actual, 'camion') !== false) {
        $mostrar_camion_extra = true;
    } elseif (stripos($tipo_vehiculo_actual, 'tracto') !== false) {
        $mostrar_tracto_extra = true;
    }
}

// Obtener otras opciones (dirección, carga)
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_division']) && !isset($_POST['confirmar']) && !isset($_POST['enviar_confirmacion']) && !isset($_POST['accion'])) {
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

            // Obtener precio_base de ambas comunas
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

        // Aquí la asignación corregida:
        $total_km = $es_rm ? 0 : (isset($_POST['total_km']) && is_numeric($_POST['total_km']) ? floatval($_POST['total_km']) : 0);

        // Calcular monto solo si hay datos válidos
        if ($total_km >= 0) {
            $km_excedente = max(0, $total_km - $km_base_php);

            // Usar precio_base si es RM, sino costo_base_php
            if ($es_rm) {
                // Sin margen del 15% en RM
                $monto_inicial = $precio_base + ($costo_por_km_php * $km_excedente);
            } else {
                // Aplica margen solo fuera de RM
                $subtotal = $costo_base_php + ($costo_por_km_php * $km_excedente);
                $margen = $subtotal * 0.15;
                $monto_inicial = $subtotal + $margen;
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
        // Recalcular precio_base (por si no venía desde $_POST)
        $precio_base = 0;
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

        // Recalcular monto_inicial como en 'calcular'
        $total_km = $es_rm ? 0 : (isset($_POST['total_km']) && is_numeric($_POST['total_km']) ? floatval($_POST['total_km']) : 0);
        $km_excedente = max(0, $total_km - $km_base_php);

        if ($es_rm) {
            $monto_inicial = $precio_base + ($costo_por_km_php * $km_excedente);
        } else {
            $subtotal = $costo_base_php + ($costo_por_km_php * $km_excedente);
            $margen = $subtotal * 0.15;
            $monto_inicial = $subtotal + $margen;
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
            'Monto Inicial (con margen)' => number_format($monto_inicial, 2, ',', '.')
        ];

        $mostrar_modal_confirm = true;
    }
}


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
            $sql_insert = "INSERT INTO cotizaciones (
                id_division, id_turno, rut_CLIENTE, servicio, Fecha_servicio,
                telefono_CLIENTE, email_CLIENTE, Descripcion, Panne,
                tipo_camino, base_salida, estado,
                coordinador_id, id_comuna_origen, id_comuna_destino,
                tipo_direccion, carga, monto_inicial, total_km,
                id_tipo_vehiculo, id_sub_tipo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iissssssssssiiisssdis",
                $id_division, $id_turno, $rut_cliente, $nombre_servicio, $fecha_servicio,
                $telefono, $email, $descripcion, $panne,
                $tipo_camino, $base_salida, $estado,
                $coordinador_id, $id_comuna_origen, $id_comuna_destino,
                $tipo_direccion, $carga, $monto_inicial, $total_km,
                $id_tipo_vehiculo, $id_sub_tipo
            );
            if ($stmt_insert->execute()) {
                $id_cotizacion = $conn->insert_id;
                $mensaje = "Cotización creada correctamente. ID: " . $id_cotizacion;
                $_POST = [];
            } else {
                $error = "Error al guardar cotización: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
    }
}


// Funciones auxiliares y carga de selects (sin cambios)
function obtenerRegionDesdeComuna($id_comuna) {
    global $conexion; // Usa tu conexión mysqli

    $stmt = $conexion->prepare("SELECT region FROM comunas WHERE id = ?");
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

<div class="container mt-3">

<h2>Crear Cotización</h2>

<?php if ($mensaje): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="" novalidate>
    <input type="hidden" name="pestana_activa" id="pestana_activa" value="<?= htmlspecialchars($_POST['pestana_activa'] ?? 'tab1') ?>">
    <ul class="nav nav-tabs" id="tabsCotizacion" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab1') ? 'active' : '' ?>" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab1';">Datos Generales</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab2') ? 'active' : '' ?>" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab2';">Ubicación</button>
        </li>
        <?php if ($mostrar_tab_vehiculo): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($pestana_activa === 'tab3') ? 'active' : '' ?>" id="tab3-tab" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab" onclick="document.getElementById('pestana_activa').value='tab3';">Vehículos</button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content border border-top-0 p-3" id="tabsCotizacionContent">
        <div class="tab-pane fade <?= ($pestana_activa === 'tab1') ? 'show active' : '' ?>" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">

            <div class="mb-3">
                <label for="id_division" class="form-label">División</label>
                <select class="form-select" name="id_division" id="id_division" required onchange="this.form.submit()">
                    <option value="">Seleccione división</option>
                    <?php foreach ($divisiones as $div): ?>
                        <option value="<?= $div['id'] ?>" <?= ($sel_division == $div['id']) ? 'selected' : '' ?>><?= htmlspecialchars($div['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_servicio" class="form-label">Servicio</label>
                <select class="form-select" name="id_servicio" id="id_servicio" required onchange="this.form.submit()">
                    <option value="">Seleccione servicio</option>
                    <?php foreach ($servicios as $serv): ?>
                        <option value="<?= $serv['id'] ?>" <?= ($sel_servicio == $serv['id']) ? 'selected' : '' ?>><?= htmlspecialchars($serv['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_turno" class="form-label">Turno</label>
                <select class="form-select" name="id_turno" id="id_turno" required>
                    <option value="">Seleccione turno</option>
                    <?php foreach ($turnos as $turno): ?>
                        <option value="<?= $turno['id'] ?>" <?= ($sel_turno == $turno['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turno['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="rut_cliente" class="form-label">Cliente (RUT)</label>
                <select class="form-select" name="rut_cliente" id="rut_cliente" required>
                    <option value="">Seleccione cliente</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= htmlspecialchars($cli['rut']) ?>" <?= ($sel_cliente == $cli['rut']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cli['nombre']) ?> - <?= htmlspecialchars($cli['rut']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="Fecha_servicio" class="form-label">Fecha y Hora Servicio</label>
                <input type="datetime-local" name="Fecha_servicio" id="Fecha_servicio" required class="form-control" value="<?= htmlspecialchars($_POST['Fecha_servicio'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="TELEFONO" class="form-label">Teléfono</label>
                <input type="text" name="TELEFONO" id="TELEFONO" class="form-control" required value="<?= htmlspecialchars($_POST['TELEFONO'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="form-control"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label for="panne" class="form-label">Panne</label>
                <textarea name="panne" id="panne" class="form-control"><?= htmlspecialchars($_POST['panne'] ?? '') ?></textarea>
            </div>

        </div>

        <div class="tab-pane fade <?= ($pestana_activa === 'tab2') ? 'show active' : '' ?>" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">

            <div class="mb-3">
                <label for="id_comuna_origen" class="form-label">Comuna Origen</label>
                <select class="form-select" name="id_comuna_origen" id="id_comuna_origen" required>
                    <option value="">Seleccione comuna origen</option>
                    <?php foreach ($comunas as $com): ?>
                        <option value="<?= $com['id'] ?>" <?= ($sel_comuna_origen == $com['id']) ? 'selected' : '' ?>><?= htmlspecialchars($com['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="id_comuna_destino" class="form-label">Comuna Destino</label>
                <select class="form-select" name="id_comuna_destino" id="id_comuna_destino" required>
                    <option value="">Seleccione comuna destino</option>
                    <?php foreach ($comunas as $com): ?>
                        <option value="<?= $com['id'] ?>" <?= ($sel_comuna_destino == $com['id']) ? 'selected' : '' ?>><?= htmlspecialchars($com['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="base_salida" class="form-label">Base de Salida</label>
                <select name="base_salida" id="base_salida" class="form-select" required>
                    <option value="" disabled <?= !isset($_POST['base_salida']) ? 'selected' : '' ?>>Seleccione una base</option>
                    <option value="Bulnes" <?= ($_POST['base_salida'] ?? '') === 'Bulnes' ? 'selected' : '' ?>>Bulnes</option>
                    <option value="Chillán" <?= ($_POST['base_salida'] ?? '') === 'Chillán' ? 'selected' : '' ?>>Chillán</option>
                    <option value="Maipú" <?= ($_POST['base_salida'] ?? '') === 'Maipú' ? 'selected' : '' ?>>Maipú</option>
                </select>
            </div>


            <div class="mb-3">
                <label for="tipo_camino" class="form-label">Tipo de Camino</label>
                <input type="text" name="tipo_camino" id="tipo_camino" class="form-control" required value="<?= htmlspecialchars($_POST['tipo_camino'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label for="total_km" class="form-label">Total KM:</label>
                <input 
                    type="number" 
                    name="total_km" 
                    id="total_km" 
                    class="form-control" 
                    value="<?= htmlspecialchars($es_rm ? 0 : ($_POST['total_km'] ?? 0)) ?>" 
                    <?= $es_rm ? 'readonly' : '' ?> 
                    step="0.01" 
                    min="0" 
                />
                <?php if ($es_rm): ?>
                    <div class="form-text text-info">
                        Ambas comunas pertenecen a la RM. Se aplicará el mayor precio base automáticamente.
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3 d-flex gap-2">
                <button type="submit" name="accion" value="calcular" class="btn btn-secondary">Calcular monto</button>
            </div>

            <?php if (!empty($mensaje2)): ?>
                <div class="alert alert-info mt-3"><?= htmlspecialchars($mensaje2) ?></div>
            <?php endif; ?>

        </div>

        <?php if ($mostrar_tab_vehiculo): ?>
        <div class="tab-pane fade <?= ($pestana_activa === 'tab3') ? 'show active' : '' ?>" id="tab3" role="tabpanel" aria-labelledby="tab3-tab">
            <div class="mb-3">
                <label for="id_tipo_vehiculo" class="form-label">Tipo de Vehículo</label>
                <select class="form-select" name="id_tipo_vehiculo" id="id_tipo_vehiculo" required onchange="this.form.submit()">
                    <option value="">Seleccione tipo vehículo</option>
                    <?php foreach ($vehiculos as $veh): ?>
                        <option value="<?= $veh['id'] ?>" <?= (isset($_POST['id_tipo_vehiculo']) && $_POST['id_tipo_vehiculo'] == $veh['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($veh['Tipo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($mostrar_camion_extra): ?>
                <div class="mb-3">
                    <label for="sub_tipo" class="form-label">Tipo</label>
                    <select class="form-select" name="id_sub_tipo" id="id_sub_tipo" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($opciones_sub_tipo as $sub_tipo): ?>
                            <option value="<?= htmlspecialchars($sub_tipo) ?>" <?= ($_POST['sub_tipo'] ?? '') == $sub_tipo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sub_tipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="tipo_direccion" class="form-label">¿Es bidireccional?</label>
                    <select class="form-select" name="tipo_direccion" id="tipo_direccion" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($opciones_direccion as $direccion): ?>
                            <option value="<?= htmlspecialchars($direccion) ?>" <?= ($_POST['tipo_direccion'] ?? '') == $direccion ? 'selected' : '' ?>>
                                <?= htmlspecialchars($direccion) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="carga" class="form-label">Carga</label>
                    <select class="form-select" name="carga" id="carga" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($opciones_carga as $carga): ?>
                            <option value="<?= htmlspecialchars($carga) ?>" <?= ($_POST['carga'] ?? '') == $carga ? 'selected' : '' ?>>
                                <?= htmlspecialchars($carga) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($mostrar_tracto_extra): ?>
                <div class="mb-3">
                    <label for="sub_tipo" class="form-label">Tipo</label>
                    <select class="form-select" name="sub_tipo" id="sub_tipo" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($opciones_sub_tipo as $sub_tipo): ?>
                            <option value="<?= htmlspecialchars($sub_tipo) ?>" <?= ($_POST['sub_tipo'] ?? '') == $sub_tipo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sub_tipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="carga" class="form-label">Carga</label>
                    <select class="form-select" name="carga" id="carga" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($opciones_carga as $carga): ?>
                            <option value="<?= htmlspecialchars($carga) ?>" <?= ($_POST['carga'] ?? '') == $carga ? 'selected' : '' ?>>
                                <?= htmlspecialchars($carga) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <div class="mt-3">
        <button type="submit" name="enviar_confirmacion" class="btn btn-primary">Crear cotización</button>
    </div>

</form>

<?php if ($mostrar_modal_confirm): ?>
<!-- Modal Confirmación -->
<div class="modal show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);" aria-modal="true" role="dialog" id="modalConfirm">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="">
        <div class="modal-header">
          <h5 class="modal-title">Confirme la cotización</h5>
          <button type="button" class="btn-close" onclick="window.history.back()"></button>
        </div>
        <div class="modal-body">
            <?php foreach ($datos_confirmacion as $campo => $valor): ?>
                <?php if (strtolower($campo) !== 'monto inicial'): ?>
                    <p><strong><?= htmlspecialchars($campo) ?>:</strong> <?= htmlspecialchars($valor) ?></p>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Mostrar el monto_inicial real -->
            <p><strong>Monto Inicial (real):</strong> <?= number_format($monto_inicial, 2, ',', '.') ?></p>
            <input type="hidden" name="monto_inicial" value="<?= htmlspecialchars($monto_inicial) ?>" />

            <!-- Reenviar todos los campos del formulario -->
            <?php foreach ($_POST as $key => $val): ?>
                <?php if (!in_array($key, ['enviar_confirmacion', 'confirmar', 'monto_inicial'])): ?>
                    <?php if (is_array($val)): ?>
                        <?php foreach ($val as $subval): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>[]" value="<?= htmlspecialchars($subval) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($val) ?>">
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button type="submit" name="confirmar" class="btn btn-success">Confirmar</button>
          <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const bsTabs = document.querySelectorAll('[data-bs-toggle="tab"]');

    // Bloquear fechas pasadas en Fecha_servicio
    const fechaInput = document.getElementById('Fecha_servicio');
    if (fechaInput) {
        const ahora = new Date();
        ahora.setSeconds(0, 0);
        const hoyISO = ahora.toISOString().slice(0, 16);
        fechaInput.min = hoyISO;
    }

    // Restaurar pestaña activa
    const activa = document.getElementById('pestana_activa')?.value;
    if (activa) {
        const tabBtn = document.querySelector(`[data-bs-target="#${activa}"]`);
        if (tabBtn) {
            const tab = new bootstrap.Tab(tabBtn);
            tab.show();
        }
    }

    bsTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            document.getElementById('pestana_activa').value = this.getAttribute('data-bs-target').substring(1);
        });
    });

    form.addEventListener('submit', function(e) {
        const submitBtn = document.activeElement;
        const esConfirmacion = submitBtn?.name === 'confirmar';
        if (!esConfirmacion && !validarFormulario()) {
            e.preventDefault();
            mostrarPrimerError();
        }
    });

    function validarFormulario() {
        let valido = true;
        resetErrores();

        const camposRequeridos = obtenerCamposRequeridos();
        const tabsConErrores = new Set();

        camposRequeridos.forEach(campo => {
            const elemento = document.querySelector(`[name="${campo.id}"]`);
            // Validar sólo si el elemento existe, está visible y NO está deshabilitado
            if (elemento && elemento.offsetParent !== null && !elemento.disabled) {
                if (!elemento.value || elemento.value.trim() === '') {
                    marcarError(elemento, `❌ ${campo.mensaje || 'Este campo es obligatorio'}`);
                    valido = false;
                    if (campo.tab) tabsConErrores.add(campo.tab);
                }
            }
        });

        if (!validarKm()) valido = false;

        resaltarTabsConErrores(tabsConErrores);

        if (!valido) mostrarResumenErrores();
        return valido;
    }

    function obtenerCamposRequeridos() {
        const campos = [
            {id: 'id_division', mensaje: 'Seleccione una división', tab: 'tab1'},
            {id: 'id_servicio', mensaje: 'Seleccione un servicio', tab: 'tab1'},
            {id: 'id_turno', mensaje: 'Seleccione un turno', tab: 'tab1'},
            {id: 'rut_cliente', mensaje: 'Seleccione un cliente', tab: 'tab1'},
            {id: 'email', mensaje: 'Ingrese un email', tab: 'tab1'},
            {id: 'Fecha_servicio', mensaje: 'Ingrese fecha y hora', tab: 'tab1'},
            {id: 'TELEFONO', mensaje: 'Ingrese teléfono', tab: 'tab1'},
            {id: 'id_comuna_origen', mensaje: 'Seleccione comuna origen', tab: 'tab2'},
            {id: 'id_comuna_destino', mensaje: 'Seleccione comuna destino', tab: 'tab2'},
            {id: 'base_salida', mensaje: 'Ingrese base de salida', tab: 'tab2'},
            {id: 'tipo_camino', mensaje: 'Ingrese tipo de camino', tab: 'tab2'}
        ];

        // Solo agregar total_km si está habilitado (no deshabilitado)
        const kmInput = document.querySelector('[name="total_km"]');
        if (kmInput && !kmInput.disabled) {
            campos.push({id: 'total_km', mensaje: 'Ingrese total de KM', tab: 'tab2'});
        }

        if (document.getElementById('tab3')) {
            const vehiculo = document.querySelector('[name="id_tipo_vehiculo"]');
            if (vehiculo) campos.push({id: 'id_tipo_vehiculo', mensaje: 'Seleccione tipo de vehículo', tab: 'tab3'});

            const subtipo = document.querySelector('[name="id_sub_tipo"], [name="sub_tipo"]');
            if (subtipo) campos.push({id: subtipo.name, mensaje: 'Seleccione subtipo', tab: 'tab3'});

            const carga = document.querySelector('[name="carga"]');
            if (carga) campos.push({id: 'carga', mensaje: 'Seleccione carga', tab: 'tab3'});

            const direccion = document.querySelector('[name="tipo_direccion"]');
            if (direccion) campos.push({id: 'tipo_direccion', mensaje: 'Seleccione tipo de dirección', tab: 'tab3'});
        }

        return campos;
    }

    function validarKm() {
        const kmInput = document.querySelector('[name="total_km"]');
        // Solo validar si está visible Y habilitado
        if (kmInput && kmInput.offsetParent !== null && !kmInput.disabled) {
            if (!kmInput.value || isNaN(parseFloat(kmInput.value))) {
                marcarError(kmInput, '❌ Debe ser un número válido');
                return false;
            }
        }
        return true;
    }

    function marcarError(elemento, mensaje) {
        if (!elemento) return;
        elemento.classList.add('campo-faltante');

        let divError = elemento.nextElementSibling;
        if (!divError || !divError.classList.contains('mensaje-error-campo')) {
            divError = document.createElement('div');
            divError.className = 'mensaje-error-campo';
            elemento.parentNode.insertBefore(divError, elemento.nextSibling);
        }
        divError.innerHTML = mensaje;

        if (elemento.tagName === 'SELECT') {
            const arrow = document.createElement('span');
            arrow.className = 'arrow-indicator';
            arrow.innerHTML = '➤';
            elemento.parentNode.style.position = 'relative';
            elemento.parentNode.appendChild(arrow);
        }
    }

    function resaltarTabsConErrores(tabsConErrores) {
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.classList.remove('tab-con-error');
        });

        tabsConErrores.forEach(tabId => {
            const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
            if (tabButton) {
                tabButton.classList.add('tab-con-error');
            }
        });
    }

    function mostrarPrimerError() {
        const firstErrorField = document.querySelector('.campo-faltante');
        if (firstErrorField) {
            const tabPane = firstErrorField.closest('.tab-pane');
            if (tabPane) {
                const tabId = tabPane.id;
                const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                if (tabButton) {
                    const tab = new bootstrap.Tab(tabButton);
                    tab.show();
                    setTimeout(() => {
                        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstErrorField.focus();
                    }, 300);
                }
            }
        }
    }

    function mostrarResumenErrores() {
        const errores = [];
        document.querySelectorAll('.campo-faltante').forEach(el => {
            const label = document.querySelector(`label[for="${el.id}"]`);
            errores.push(label ? label.textContent : el.name);
        });

        let resumen = document.querySelector('.resumen-errores');
        if (!resumen) {
            resumen = document.createElement('div');
            resumen.className = 'resumen-errores';
            form.prepend(resumen);
        }

        resumen.innerHTML = `
            <h5><span style="color: #dc3545;">⚠️ ATENCIÓN:</span> Faltan datos obligatorios</h5>
            <p>Por favor complete los siguientes campos en las <span class="tab-con-error" style="padding: 2px 5px;">pestañas marcadas en rojo</span>:</p>
            <ul>
                ${errores.map(error => `<li><strong>${error}</strong></li>`).join('')}
            </ul>
            <p class="mb-0">Siga las flechas <span class="arrow-indicator" style="position: static; display: inline-block;">➤</span> y los mensajes en rojo.</p>
        `;

        resumen.scrollIntoView({ behavior: 'smooth' });
    }

    function resetErrores() {
        document.querySelectorAll('.campo-faltante').forEach(el => el.classList.remove('campo-faltante'));
        document.querySelectorAll('.mensaje-error-campo, .arrow-indicator').forEach(el => el.remove());
        const resumen = document.querySelector('.resumen-errores');
        if (resumen) resumen.remove();
        document.querySelectorAll('.nav-link.tab-con-error').forEach(el => el.classList.remove('tab-con-error'));
    }

    // Validación en tiempo real para limpiar errores cuando el usuario corrige
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value && this.value.trim() !== '') {
                this.classList.remove('campo-faltante');
                const errorMsg = this.nextElementSibling;
                if (errorMsg && errorMsg.classList.contains('mensaje-error-campo')) {
                    errorMsg.remove();
                }

                const tabPane = this.closest('.tab-pane');
                if (tabPane) {
                    const hasErrors = tabPane.querySelector('.campo-faltante');
                    if (!hasErrors) {
                        const tabId = tabPane.id;
                        const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                        if (tabButton) tabButton.classList.remove('tab-con-error');
                    }
                }
            }
        });
    });
});
</script>
</body>
</html>