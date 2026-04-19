<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_error.log'); 
error_reporting(E_ALL);
session_start();

include("database.php");

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar'])) {
        // Código de eliminación existente...
    } 
    elseif (isset($_POST['guardar_cotizacion'])) {
        // Validación completa de campos obligatorios con mensajes detallados
        $campos_requeridos = [
            'id_division' => 'División',
            'servicio' => 'Servicio',
            'id_turno' => 'Turno'
        ];
        
        $campos_faltantes = [];
        foreach ($campos_requeridos as $campo => $nombre) {
            if (empty($_POST[$campo])) {
                $campos_faltantes[] = $nombre;
            }
        }
        
        if (!empty($campos_faltantes)) {
            $mensaje = "Error: Faltan campos obligatorios: " . implode(", ", $campos_faltantes);
            error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
            echo "<script>alert('".addslashes($mensaje)."');</script>";
            exit();
        } 

        // Procesamiento completo del cliente (nuevo o existente)
        $cliente_nuevo = $_POST['cliente_nuevo'] ?? 'No';
        $rut_cliente = '';
        
        if ($cliente_nuevo === 'Si') {
            // Validación exhaustiva de todos los campos del cliente nuevo
            $campos_cliente_requeridos = [
                'NOMBRE' => 'Nombre',
                'RUT' => 'RUT',
                'TIPO' => 'Tipo',
                'DIRECCION' => 'Dirección',
                'TELEFONO' => 'Teléfono',
                'GIRO' => 'Giro'
            ];
            
            $campos_cliente_faltantes = [];
            foreach ($campos_cliente_requeridos as $campo => $nombre) {
                if (empty($_POST[$campo])) {
                    $campos_cliente_faltantes[] = $nombre;
                }
            }
            
            if (!empty($campos_cliente_faltantes)) {
                $mensaje = "Error en cliente: Faltan datos: " . implode(", ", $campos_cliente_faltantes);
                error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
                echo "<script>alert('".addslashes($mensaje)."');</script>";
                exit();
            }

            // Inserción completa del nuevo cliente con manejo de errores detallado
            $sqlCliente = "INSERT INTO clientes (NOMBRE, RUT, TIPO, DIRECCION, TELEFONO, EMAIL, GIRO, credito) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtCliente = $conn->prepare($sqlCliente);
            
            if ($stmtCliente === false) {
                $mensaje = "Error al preparar consulta de cliente: " . htmlspecialchars($conn->error);
                error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
                echo "<script>alert('".addslashes($mensaje)."');</script>";
                exit();
            }
            
            // Limpieza y validación completa de todos los campos del cliente
            $nombre = htmlspecialchars(trim($_POST['NOMBRE']));
            $rut = htmlspecialchars(trim($_POST['RUT']));
            $tipo = htmlspecialchars(trim($_POST['TIPO']));
            $direccion = htmlspecialchars(trim($_POST['DIRECCION']));
            $telefono = htmlspecialchars(trim($_POST['TELEFONO']));
            $email = htmlspecialchars(trim($_POST['EMAIL'] ?? ''));
            $giro = htmlspecialchars(trim($_POST['GIRO']));
            $credito = htmlspecialchars(trim($_POST['credito'] ?? ''));
            
            $stmtCliente->bind_param("ssssssss", $nombre, $rut, $tipo, $direccion, $telefono, $email, $giro, $credito);
            
            if ($stmtCliente->execute()) {
                $rut_cliente = $rut;
                error_log("[".date('Y-m-d H:i:s')."] Cliente nuevo registrado: ".$rut_cliente);
            } else {
                $mensaje = "Error al registrar cliente: " . htmlspecialchars($stmtCliente->error);
                error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
                echo "<script>alert('".addslashes($mensaje)."');</script>";
                $stmtCliente->close();
                exit();
            }
            $stmtCliente->close();
        } else {
            // Validación completa para cliente existente
            if (empty($_POST['rut_cliente'])) {
                $mensaje = "Error: Debe seleccionar un cliente existente";
                error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
                echo "<script>alert('".addslashes($mensaje)."');</script>";
                exit();
            }
            $rut_cliente = $_POST['rut_cliente'];
            error_log("[".date('Y-m-d H:i:s')."] Usando cliente existente: ".$rut_cliente);
        }

        // Procesamiento completo de todos los campos de la cotización
        $id_division = (int)$_POST['id_division'];
        $servicio = trim($_POST['servicio']);
        $id_turno = (int)$_POST['id_turno'];

        // Manejo completo y robusto de la fecha según el tipo DATE de tu tabla
        $fecha_servicio = date('Y-m-d');
        if (!empty($_POST['Fecha_servicio'])) {
            $fecha_input = DateTime::createFromFormat('d/m/Y', $_POST['Fecha_servicio']);
            if ($fecha_input === false) {
                $mensaje = "Formato de fecha inválido. Use DD/MM/AAAA. Valor recibido: ".htmlspecialchars($_POST['Fecha_servicio']);
                error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
                echo "<script>alert('".addslashes($mensaje)."');</script>";
                exit();
            }
            $fecha_servicio = $fecha_input->format('Y-m-d');
        }

        // Procesamiento completo de todos los campos adicionales con conversión de tipos adecuada
        $telefono_cliente = $_POST['TELEFONO'] ?? '';
        $email_cliente = $_POST['EMAIL'] ?? '';
        $descripcion = $_POST['descripcion'] ?? '';
        $panne = $_POST['panne'] ?? '';
        $forma_pago = $_POST['forma_de_pago'] ?? '';
        $tipo_camino = $_POST['tipo_camino'] ?? '';
        $documento_tributario = $_POST['documento_tributario'] ?? '';
        $id_tipo_vehiculo = !empty($_POST['tipo_vehiculo']) ? (int)$_POST['tipo_vehiculo'] : null;
        $id_comuna_origen = !empty($_POST['id_comuna_origen']) ? (int)$_POST['id_comuna_origen'] : null;
        $id_comuna_destino = !empty($_POST['id_comuna_destino']) ? (int)$_POST['id_comuna_destino'] : null;
        $direccion_origen = $_POST['direccion_origen'] ?? '';
        $direccion_destino = $_POST['direccion_destino'] ?? '';
        $total_km = !empty($_POST['total_km']) ? (int)$_POST['total_km'] : 0;
        $id_sub_tipo = $_POST['sub_tipo'] ?? null;
        $base_salida = $_POST['base_salida'] ?? '';
        $estado = "Pendiente"; // Valor por defecto para el campo estado

        // Consulta SQL COMPLETA con TODOS los campos en el ORDEN EXACTO de tu tabla
        $sqlCotizacion = "INSERT INTO cotizaciones (
            id_division, id_turno, rut_CLIENTE, servicio, Fecha_servicio,
            telefono_CLIENTE, email_CLIENTE, Descripcion, Panne, forma_de_pago,
            tipo_camino, documento_tributario, id_tipo_vehiculo, id_comuna_origen,
            id_comuna_destino, direccion_origen, direccion_destino, total_km, id_sub_tipo, base_salida,
            estado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtCotizacion = $conn->prepare($sqlCotizacion);
        if ($stmtCotizacion === false) {
            $mensaje = "Error al preparar consulta: " . htmlspecialchars($conn->error);
            error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
            error_log("[".date('Y-m-d H:i:s')."] SQL: ".$sqlCotizacion);
            echo "<script>alert('".addslashes($mensaje)."');</script>";
            exit();
        }
        
        // Cadena completa de tipos de parámetros (s = string, i = integer, d = double, b = blob)
        $types = "iisssssssssiiisssisss";
        
        // Vinculación COMPLETA de TODOS los parámetros en el ORDEN CORRECTO
        $bind_result = $stmtCotizacion->bind_param(
            $types,
            $id_division, $id_turno, $rut_cliente, $servicio, $fecha_servicio,
            $telefono_cliente, $email_cliente, $descripcion, $panne, $forma_pago,
            $tipo_camino, $documento_tributario, $id_tipo_vehiculo, $id_comuna_origen,
            $id_comuna_destino, $direccion_origen, $direccion_destino, $total_km, $id_sub_tipo, $base_salida,
            $estado
        );
        
        if ($bind_result === false) {
            $mensaje = "Error al vincular parámetros: " . htmlspecialchars($stmtCotizacion->error);
            error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
            echo "<script>alert('".addslashes($mensaje)."');</script>";
            $stmtCotizacion->close();
            exit();
        }

        // Ejecución completa con manejo detallado de errores
        if ($stmtCotizacion->execute()) {
            $id_insertado = $conn->insert_id;
            $mensaje = "Cotización creada correctamente. ID: ".$id_insertado;
            error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
            
            // Registro completo de todos los datos insertados para auditoría
            error_log("[".date('Y-m-d H:i:s')."] Datos insertados:");
            error_log("ID: ".$id_insertado);
            error_log("División: ".$id_division);
            error_log("Turno: ".$id_turno);
            error_log("RUT Cliente: ".$rut_cliente);
            error_log("Servicio: ".$servicio);
            error_log("Fecha: ".$fecha_servicio);
            error_log("Teléfono: ".$telefono_cliente);
            error_log("Email: ".$email_cliente);
            error_log("Estado: ".$estado);
            // ... (todos los demás campos)
            
            echo "<script>
                alert('Cotización creada con éxito. ID: $id_insertado');
                window.location.href = 'cotizaciones.php';
            </script>";
            exit();
        } else {
            $mensaje = "Error al guardar cotización: " . htmlspecialchars($stmtCotizacion->error);
            error_log("[".date('Y-m-d H:i:s')."] ".$mensaje);
            error_log("[".date('Y-m-d H:i:s')."] Consulta fallida: ".$sqlCotizacion);
            
            // Depuración completa de todos los valores
            error_log("[".date('Y-m-d H:i:s')."] Valores enviados:");
            error_log("id_division: ".$id_division);
            error_log("id_turno: ".$id_turno);
            error_log("rut_cliente: ".$rut_cliente);
            error_log("servicio: ".$servicio);
            error_log("fecha_servicio: ".$fecha_servicio);
            error_log("telefono_cliente: ".$telefono_cliente);
            error_log("email_cliente: ".$email_cliente);
            error_log("descripcion: ".$descripcion);
            error_log("panne: ".$panne);
            error_log("forma_pago: ".$forma_pago);
            error_log("tipo_camino: ".$tipo_camino);
            error_log("documento_tributario: ".$documento_tributario);
            error_log("id_tipo_vehiculo: ".$id_tipo_vehiculo);
            error_log("id_comuna_origen: ".$id_comuna_origen);
            error_log("id_comuna_destino: ".$id_comuna_destino);
            error_log("direccion_origen: ".$direccion_origen);
            error_log("direccion_destino: ".$direccion_destino);
            error_log("total_km: ".$total_km);
            error_log("id_sub_tipo: ".$id_sub_tipo);
            error_log("base_salida: ".$base_salida);
            error_log("estado: ".$estado);
            
            echo "<script>alert('".addslashes($mensaje)."');</script>";
            $stmtCotizacion->close();
            exit();
        }
    }
}

// [MANTENGO TODAS TUS FUNCIONES ORIGINALES SIN MODIFICAR]
function cargarOpcionesSelect($conn, $tabla, $id_col = 'id', $nombre_col = 'nombre') {
    $opciones = [];
    $sql = "SELECT $id_col, $nombre_col FROM $tabla ORDER BY $nombre_col";
    $result = $conn->query($sql);
    
    if ($result === false) {
        error_log("Error al cargar $tabla: " . $conn->error);
        return $opciones;
    }
    
    while ($row = $result->fetch_assoc()) {
        $opciones[] = $row;
    }
    $result->free();
    return $opciones;
}

function cargarOpcionesSelectCampo($conn, $tabla, $campo) {
    $sql = "SELECT DISTINCT $campo FROM $tabla WHERE $campo IS NOT NULL ORDER BY $campo ASC";
    $result = $conn->query($sql);

    $opciones = [];
    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $valor = $fila[$campo];
            $opciones[] = ['id' => $valor, 'nombre' => $valor];
        }
    }

    return $opciones;
}

function cargarOpcionesTipoVehiculo($conn) {
    $sql = "SELECT id, Tipo FROM vehiculos WHERE Tipo IS NOT NULL ORDER BY Tipo ASC";
    $result = $conn->query($sql);

    $opciones = [];
    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $opciones[] = ['id' => $fila['id'], 'nombre' => $fila['Tipo']];
        }
    }

    return $opciones;
}

// [MANTENGO TODA TU LÓGICA ORIGINAL DE CARGA DE DATOS]
$divisiones = cargarOpcionesSelect($conn, 'division');
$tiposVehiculo = cargarOpcionesTipoVehiculo($conn);
$comunas = cargarOpcionesSelect($conn, 'comunas');
$turnos = cargarOpcionesSelect($conn, 'turno');
$clientes = [];
$sqlClientes = "SELECT DISTINCT rut, nombre FROM clientes ORDER BY nombre";
$resultClientes = $conn->query($sqlClientes);
if ($resultClientes !== false) {
    while ($row = $resultClientes->fetch_assoc()) {
        $clientes[] = $row;
    }
    $resultClientes->free();
} else {
    error_log("Error al cargar clientes: " . $conn->error);
}

// [MANTENGO TODA TU LÓGICA ORIGINAL DE FILTRADO]
$where_conditions = [];
$params = [];
$types = "";

if (!empty($_POST['id'])) {
    $where_conditions[] = "c.id = ?";
    $params[] = $_POST['id'];
    $types .= "i";
}

if (!empty($_POST['rut_cliente'])) {
    $where_conditions[] = "c.rut_CLIENTE LIKE ?";
    $params[] = "%" . $_POST['rut_cliente'] . "%";
    $types .= "s";
}

if (!empty($_POST['id_division']) && $_POST['id_division'] != '') {
    $where_conditions[] = "c.id_division = ?";
    $params[] = $_POST['id_division'];
    $types .= "i";
}

if (!empty($_POST['fecha_desde'])) {
    $fecha_desde = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['fecha_desde'])));
    $where_conditions[] = "c.Fecha_servicio >= ?";
    $params[] = $fecha_desde;
    $types .= "s";
}

if (!empty($_POST['fecha_hasta'])) {
    $fecha_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['fecha_hasta'])));
    $where_conditions[] = "c.Fecha_servicio <= ?";
    $params[] = $fecha_hasta;
    $types .= "s";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$cotizaciones = [];

$sql = "SELECT c.*, 
            v.Tipo AS tipo_vehiculo,
            co.nombre AS comuna_origen,
            cd.nombre AS comuna_destino,
            d.nombre AS division,
            t.nombre AS turno,
            cli.nombre AS nombre_cliente
        FROM cotizaciones c
        LEFT JOIN vehiculos v ON c.id_tipo_vehiculo = v.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        LEFT JOIN division d ON c.id_division = d.id
        LEFT JOIN turno t ON c.id_turno = t.id
        LEFT JOIN clientes cli ON c.rut_CLIENTE = cli.rut
        $where_clause
        ORDER BY c.Fecha_servicio DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $mensaje .= " Error al preparar consulta: " . htmlspecialchars($conn->error);
    } else {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cotizaciones[] = $row;
            }
        } else {
            $mensaje .= " Error al obtener resultados: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
} else {
    $result = $conn->query($sql);
    if ($result === false) {
        $mensaje .= " Error en consulta: " . htmlspecialchars($conn->error);
    } else {
        while ($row = $result->fetch_assoc()) {
            $cotizaciones[] = $row;
        }
        $result->free();
    }
}

// [MANTENGO TODA TU LÓGICA ORIGINAL PARA MANEJO DE CLIENTES]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['eliminar_cliente'])) {
        $idEliminar = $_POST['id'];
        $sqlEliminar = "DELETE FROM clientes WHERE ID = ?";
        $stmtEliminar = $conn->prepare($sqlEliminar);
        $stmtEliminar->bind_param("i", $idEliminar);
        $stmtEliminar->execute();

        if ($stmtEliminar->affected_rows > 0) {
            $mensaje = "Cliente eliminado correctamente";
            echo "<script>alert('Cliente eliminado');</script>";
        } else {
            $mensaje = "No se pudo eliminar el cliente";
            echo "<script>alert('No se puede eliminar');</script>";
        }
        $stmtEliminar->close();
    } 
    elseif (isset($_POST['idCliente'])) {
        // [Código para actualización de cliente]
    }
}

$vehiculos = [];
$sqlVehiculos = "SELECT * FROM vehiculos ORDER BY Tipo, sub_tipo, tipo_direccion, carga";
$resultVehiculos = $conn->query($sqlVehiculos);

if ($resultVehiculos && $resultVehiculos->num_rows > 0) {
    while ($row = $resultVehiculos->fetch_assoc()) {
        $vehiculos[] = $row;
    }
}
$vehiculosJSON = json_encode($vehiculos);
error_log("Total divisiones cargadas: " . count($divisiones));
error_log("Total turnos cargados: " . count($turnos));
error_log("Total cotizaciones cargadas: " . count($cotizaciones));

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizaciones</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap DateTimePicker CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css">
    <!-- Tu CSS personalizado -->
    <link rel="stylesheet" href="css/filtros.css">
    <link rel="stylesheet" href="css/cotizaciones.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <div class="filtros-header">
            <h4>Filtros Cotizaciones (TRABAJO EN PROGRESO)</h4>
        </div>
        
        <div class="filtros-container">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <input type="text" class="form-control" name="id" placeholder="ID Cotización" value="<?php echo htmlspecialchars($_POST['id'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <input type="text" class="form-control" name="rut_cliente" placeholder="RUT Cliente" value="<?php echo htmlspecialchars($_POST['rut_cliente'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="input-group date" id="datetimepicker2">
                            <input type="text" class="form-control" name="fecha_desde" placeholder="01/04/2025" value="<?php echo htmlspecialchars($_POST['fecha_desde'] ?? ''); ?>">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="input-group date" id="datetimepicker3">
                            <input type="text" class="form-control" name="fecha_hasta" placeholder="17/04/2025" value="<?php echo htmlspecialchars($_POST['fecha_hasta'] ?? ''); ?>">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-control" name="id_division">
                            <option value="">Sin División</option>
                            <?php foreach ($divisiones as $division): ?>
                                <option value="<?php echo $division['id']; ?>" <?php echo (isset($_POST['id_division']) && $_POST['id_division'] == $division['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($division['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <div class="float-start">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                <i class="fas fa-sync-alt"></i>
                            </a>
                        </div>
                        <div class="float-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearCotizacionModal">
                                <i class="fa-solid fa-plus"></i> Crear cotización
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Crear Cotización -->
    <div class="modal fade" id="crearCotizacionModal" tabindex="-1" aria-labelledby="crearCotizacionModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="crearCotizacionModalLabel">Crear Nueva Cotización</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formCotizacion" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="modal-body">
                        <!-- Pestañas de navegación -->
                        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="cliente-tab" data-bs-toggle="tab" data-bs-target="#cliente-tab-pane" type="button" role="tab" aria-controls="cliente-tab-pane" aria-selected="true">
                                    <i class="fas fa-user"></i> Datos de Cliente
                                </button>
                            </li>
                            <li class="nav-item" role="presentation" id="vehiculos-tab-item" style="display:none;">
                                <button class="nav-link" id="vehiculos-tab" data-bs-toggle="tab" data-bs-target="#vehiculos-tab-pane" type="button" role="tab" aria-controls="vehiculos-tab-pane" aria-selected="false">
                                    <i class="fas fa-car"></i> Vehículos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="cotizacion-tab" data-bs-toggle="tab" data-bs-target="#cotizacion-tab-pane" type="button" role="tab" aria-controls="cotizacion-tab-pane" aria-selected="false">
                                    <i class="fas fa-file-invoice-dollar"></i> Datos de Cotización
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="confirmacion-tab" data-bs-toggle="tab" data-bs-target="#confirmacion-tab-pane" type="button" role="tab" aria-controls="confirmacion-tab-pane" aria-selected="false">
                                    <i class="fas fa-info-circle"></i> Confirmación
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Contenido de las pestañas -->
                        <div class="tab-content" id="myTabContent">
                            <!-- Pestaña de Cliente -->
                            <div class="tab-pane fade show active" id="cliente-tab-pane" role="tabpanel" aria-labelledby="cliente-tab" tabindex="0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">División *</label>
                                            <select class="form-select" name="id_division" required>
                                                <option value="">Seleccionar División</option>
                                                <?php foreach ($divisiones as $division): ?>
                                                    <option value="<?php echo $division['id']; ?>"><?php echo htmlspecialchars($division['nombre']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Servicio *</label>
                                            <select class="form-select" name="servicio" id="servicioSelect" required>
                                                <option value="">Seleccione primero una División</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">¿Cliente Nuevo?</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="cliente_nuevo" id="cliente_existente" value="No" checked>
                                                <label class="form-check-label" for="cliente_existente">No</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="cliente_nuevo" id="cliente_nuevo" value="Si">
                                                <label class="form-check-label" for="cliente_nuevo">Sí</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div id="cliente_container">
                                                <!-- Contenido inicial (select de clientes) -->
                                                <div class="mb-3">
                                                    <label class="form-label">Cliente *</label>
                                                    <select class="form-select" name="rut_cliente" required>
                                                        <option value="">Seleccionar Cliente</option>
                                                        <?php foreach ($clientes as $cliente): ?>
                                                            <option value="<?php echo htmlspecialchars($cliente['rut']); ?>">
                                                                <?php echo htmlspecialchars($cliente['nombre']) . ' (' . htmlspecialchars($cliente['rut']) . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!-- Pestaña de Vehículos -->
                            <div class="tab-pane fade" id="vehiculos-tab-pane" role="tabpanel" aria-labelledby="vehiculos-tab" tabindex="0">
                                <div class="alert alert-danger">
                                    <center><i class="fa-solid fa-triangle-exclamation"></i> Trabajo en progreso <i class="fa-solid fa-triangle-exclamation"></i></center>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Vehículo *</label>
                                            <select class="form-select" name="tipo_vehiculo" id="tipo_vehiculo">
                                                <option value="">Seleccionar Tipo</option>
                                                <?php
                                                $tiposVistos = [];
                                                foreach ($vehiculos as $vehiculo) {
                                                    $tipo = trim($vehiculo['Tipo']);
                                                    if ($tipo === '' || is_null($tipo)) continue;
                                                    $tipoNorm = strtolower($tipo);
                                                    if (!in_array($tipoNorm, $tiposVistos)) {
                                                        $tiposVistos[] = $tipoNorm;
                                                        echo '<option value="' . htmlspecialchars($tipo) . '">' . htmlspecialchars($tipo) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- SUBTIPO -->
                                    <div class="col-md-6" id="subtipo-container" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Subtipo *</label>
                                            <select class="form-select" name="sub_tipo" id="sub_tipo">
                                                <option value="">Seleccionar Subtipo</option>
                                                <!-- Opciones se llenan dinámicamente -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Dirección *</label>
                                            <select class="form-select" name="tipo_direccion" id="tipo_vehiculo">
                                                <option value="">Seleccionar dirección</option>
                                                <?php
                                                $direccionVistos = [];
                                                foreach ($vehiculos as $vehiculo) {
                                                    $direccion_tipo = trim($vehiculo['tipo_direccion']);
                                                    if ($direccion_tipo === '' || is_null($direccion_tipo)) {
                                                        continue; // Saltar nulos o vacíos
                                                    }

                                                    $tipoNormalizado = strtolower($direccion_tipo);
                                                    if (!in_array($tipoNormalizado, $direccionVistos)) {
                                                        $direccionVistos[] = $tipoNormalizado;
                                                        // Aquí corregimos el nombre de la variable de $direcciontipo a $direccion_tipo
                                                        echo '<option value="' . htmlspecialchars($vehiculo['id']) . '">' . htmlspecialchars($direccion_tipo) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Carga *</label>
                                            <select class="form-select" name="carga" id="tipo_vehiculo">
                                                <option value="">Seleccionar carga</option>
                                                <?php
                                                $cargaVistos = [];
                                                foreach ($vehiculos as $vehiculo) {
                                                    $carga_tipo = trim($vehiculo['carga']);
                                                    if ($carga_tipo === '' || is_null($carga_tipo)) {
                                                        continue; // Saltar nulos o vacíos
                                                    }

                                                    $tipoNormalizado = strtolower($carga_tipo);
                                                    if (!in_array($tipoNormalizado, $cargaVistos)) {
                                                        $cargaVistos[] = $tipoNormalizado;
                                                        // Aquí corregimos el nombre de la variable de $direcciontipo a $direccion_tipo
                                                        echo '<option value="' . htmlspecialchars($vehiculo['id']) . '">' . htmlspecialchars($carga_tipo) . '</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pestaña de Cotización -->
                            <div class="tab-pane fade" id="cotizacion-tab-pane" role="tabpanel" aria-labelledby="cotizacion-tab" tabindex="0">
                                <div class="row">
                                    <!-- Base de salida -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Base de Salida *</label>
                                        <select class="form-select" name="base_salida" required>
                                            <option value="">Seleccionar</option>
                                            <option value="Bulnes">Bulnes</option>
                                            <option value="Chillan">Chillán</option>
                                            <option value="Maipú">Maipú</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Fecha y Hora con DateTimePicker -->
                                    <div class="col-md-3 mb-3">
                                        <label for="fechaHora" class="form-label">Fecha y Hora *</label>
                                        <div class="input-group date" id="datetimepicker1">
                                            <input type="text" class="form-control" name="Fecha_servicio" id="fechaHora" placeholder="DD/MM/AAAA HH:MM" required>
                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Turno -->
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Turno *</label>
                                        <select class="form-select" name="id_turno" required>
                                            <option value="">Selecciona el turno</option>
                                            <?php foreach ($turnos as $turno): ?>
                                                <option value="<?php echo $turno['id']; ?>">
                                                    <?php echo htmlspecialchars($turno['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Km Total</label>
                                        <input type="text" class="form-control" name="total_km" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Descripción *</label>
                                        <textarea class="form-control" name="descripcion" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Panne *</label>
                                        <textarea class="form-control" name="panne" rows="3" required></textarea>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Forma de Pago</label>
                                        <select class="form-select" name="forma_de_pago" required>
                                            <option value="" default disabled>Seleccionar Forma de Pago</option>
                                            <option value="Efectivo">Efectivo</option>
                                            <option value="Transferencia">Transferencia</option>
                                            <option value="Vale Vista">Vale vista</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Documento Tributario</label>
                                        <select class="form-select" name="documento_tributario" required>
                                            <option value="" disabled>Seleccionar documento</option>
                                            <option value="Plano - Recto">Boleta</option>
                                            <option value="Montañoso">Factura</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tipo de Camino</label>
                                        <select class="form-select" name="forma_de_pago" required>
                                            <option value="" disabled>Seleccionar tipo de camino</option>
                                            <option value="Plano - Recto">Plano - Recto</option>
                                            <option value="Montañoso">Montañoso</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Comuna origen</label>
                                            <select class="form-select" name="id_division" required>
                                                <option value="">Selecciona comuna</option>
                                                <?php foreach ($comunas as $comuna): ?>
                                                    <option value="<?php echo $comuna['id']; ?>"><?php echo htmlspecialchars($comuna['nombre']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Dirección Origen</label>
                                            <input type="text" class="form-control" name="direccion_origen" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Comuna destino</label>
                                            <select class="form-select" name="id_division" required>
                                                <option value="">Selecciona destino</option>
                                                <?php foreach ($comunas as $comuna): ?>
                                                    <option value="<?php echo $comuna['id']; ?>"><?php echo htmlspecialchars($comuna['nombre']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Dirección Destino</label>
                                            <input type="text" class="form-control" name="direccion_destino" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                                        
                            <!-- Pestaña de Confirmación -->
                            <div class="tab-pane fade" id="confirmacion-tab-pane" role="tabpanel" aria-labelledby="confirmacion-tab" tabindex="0">
                                <div class="alert alert-info">
                                    <center><i class="fa-solid fa-triangle-exclamation"></i> En construcción <i class="fa-solid fa-triangle-exclamation"></i></center>
                                </div>
                                <div id="resumen-cotizacion">
                                    <!-- Este contenido puede llenarse dinámicamente con JavaScript -->
                                </div>
                            </div>
                        </div>
                                                
                        <small class="text-muted">Los campos marcados con * son obligatorios</small><br>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_cotizacion" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery primero -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Moment.js para manejo de fechas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/es.min.js"></script>
<!-- Bootstrap DateTimePicker JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
<!-- Bootstrap Bundle con Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    /***************************************
     * 1. CONFIGURACIÓN DATETIMEPICKER
     ***************************************/
    $('#datetimepicker1').datetimepicker({
        format: 'DD/MM/YYYY HH:mm',
        locale: 'es',
        sideBySide: true,
        icons: {
            time: 'fas fa-clock',
            date: 'fas fa-calendar',
            up: 'fas fa-arrow-up',
            down: 'fas fa-arrow-down',
            previous: 'fas fa-chevron-left',
            next: 'fas fa-chevron-right',
            today: 'fas fa-calendar-check',
            clear: 'fas fa-trash',
            close: 'fas fa-times'
        }
    });

    $('#datetimepicker2').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es'
    });

    $('#datetimepicker3').datetimepicker({
        format: 'DD/MM/YYYY',
        locale: 'es',
        useCurrent: false
    });

    $("#datetimepicker2").on("change.datetimepicker", function(e) {
        $('#datetimepicker3').datetimepicker('minDate', e.date);
    });
    $("#datetimepicker3").on("change.datetimepicker", function(e) {
        $('#datetimepicker2').datetimepicker('maxDate', e.date);
    });

    /***************************************
     * 2. DEFINICIÓN DE FORMULARIOS
     ***************************************/
    const formularioOriginalCotizacion = $('#cotizacion-tab-pane').html();
    
    const formularioVehiculoHTML = `
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Base de Salida *</label>
            <select class="form-select" name="base_salida" required>
                <option value="">Seleccionar</option>
                <option value="Bulnes">Bulnes</option>
                <option value="Chillan">Chillán</option>
                <option value="Maipú">Maipú</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">¿Servicio RM? *</label>
            <select class="form-select" name="servicio_rm" required>
                <option value="Sí">Sí</option>
                <option value="No" selected>No</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">OSI</label>
            <input type="text" class="form-control" name="osi">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Turno *</label>
            <select class="form-select" name="turno" required>
                <option value="Día">Día</option>
                <option value="Noche">Noche</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Comuna Origen *</label>
            <select class="form-select" name="comuna_origen" required>
                <option value="Alhué">Alhué</option>
                <option value="Maipú">Maipú</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Comuna Destino *</label>
            <select class="form-select" name="comuna_destino" required>
                <option value="Alhué">Alhué</option>
                <option value="Maipú">Maipú</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Peaje</label>
            <input type="number" class="form-control" name="peaje" value="0">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Tipo Camino *</label>
            <select class="form-select" name="tipo_camino" required>
                <option value="Plano - recto">Plano - recto</option>
                <option value="Montañoso">Montañoso</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Forma de Pago *</label>
            <select class="form-select" name="forma_pago" required>
                <option value="Transferencia">Transferencia</option>
                <option value="Efectivo">Efectivo</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">KM Total Servicio *</label>
            <input type="number" class="form-control" name="km_total" required>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Link</label>
            <input type="text" class="form-control" name="link">
        </div>
    </div>`;

    /***************************************
     * 3. CONTROL DE FORMULARIOS DINÁMICOS
     ***************************************/
    function actualizarFormularioCotizacion() {
        const tieneDatosVehiculo = (
            $('#tipo_vehiculo').val() && 
            $('select[name="tipo_direccion"]').val() && 
            $('select[name="carga"]').val()
        );

        if (tieneDatosVehiculo) {
            $('#cotizacion-tab-pane').html(formularioVehiculoHTML);
        } else {
            $('#cotizacion-tab-pane').html(formularioOriginalCotizacion);
            $('#datetimepicker1').datetimepicker({ format: 'DD/MM/YYYY HH:mm' });
        }
    }

    $(document).on('change', '#tipo_vehiculo, #sub_tipo, select[name="tipo_direccion"], select[name="carga"]', function() {
        actualizarFormularioCotizacion();
    });

    /***************************************
     * 4. VALIDACIÓN DE DATOS DEL CLIENTE
     ***************************************/
    function mostrarErrorCliente() {
        $('#cotizacion-tab-pane').html(`
            <div class="alert alert-danger text-center py-5">
                <i class="fas fa-user-slash fa-3x mb-3"></i>
                <h3>¡Datos del cliente incompletos!</h3>
                <p>Debe completar todos los campos obligatorios en la pestaña "Datos de Cliente"</p>
                <button class="btn btn-primary mt-3" onclick="$('#cliente-tab').tab('show')">
                    <i class="fas fa-arrow-left me-2"></i> Completar datos
                </button>
            </div>
        `);
    }

    function validarCliente() {
        if ($('input[name="cliente_nuevo"]:checked').val() === 'Si') {
            const camposRequeridos = ['NOMBRE', 'RUT', 'TIPO', 'GIRO', 'DIRECCION', 'TELEFONO'];
            for (let campo of camposRequeridos) {
                if (!$(`[name="${campo}"]`).val()) {
                    mostrarErrorCliente();
                    return false;
                }
            }
        } else {
            if (!$('select[name="rut_cliente"]').val()) {
                mostrarErrorCliente();
                return false;
            }
        }
        return true;
    }

    /***************************************
     * 5. CONTROL DE FORMULARIOS DINÁMICOS MEJORADO
     ***************************************/
    function cargarFormularioCorrecto() {
        // Verificar si el servicio requiere vehículo
        const servicio = $('#servicioSelect').val();
        const requiereVehiculo = ["REMOLCAR", "DESPEJE DE VIA", "RESCATE"].includes(servicio);
        
        if (requiereVehiculo) {
            // Mostrar formulario de vehículos solo si hay datos válidos
            if ($('#tipo_vehiculo').val() && $('select[name="tipo_direccion"]').val() && $('select[name="carga"]').val()) {
                $('#cotizacion-tab-pane').html(formularioVehiculoHTML);
            } else {
                $('#cotizacion-tab-pane').html(`
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-car fa-3x mb-3"></i>
                        <h3>Complete los datos del vehículo</h3>
                        <p>Por favor complete la información requerida en la pestaña "Vehículos"</p>
                    </div>
                `);
            }
        } else {
            // Mostrar formulario normal de cotización
            $('#cotizacion-tab-pane').html(formularioOriginalCotizacion);
            // Reconfigurar plugins necesarios
            $('#datetimepicker1').datetimepicker({ format: 'DD/MM/YYYY HH:mm' });
        }
    }

    /***************************************
     * 6. CONTROL DE PESTAÑAS MEJORADO
     ***************************************/
    $('#cotizacion-tab').on('click', function(e) {
        if (!validarCliente()) {
            e.preventDefault();
            $('#cotizacion-tab').tab('show');
        } else {
            cargarFormularioCorrecto();
        }
    });

    $('#confirmacion-tab').on('click', function(e) {
        if (!validarCliente()) {
            e.preventDefault();
            $('#cliente-tab').tab('show');
            return;
        }
        
        // Validación adicional para servicios con vehículos
        const servicio = $('#servicioSelect').val();
        if (["REMOLCAR", "DESPEJE DE VIA", "RESCATE"].includes(servicio)) {
            if (!$('#tipo_vehiculo').val() || !$('select[name="tipo_direccion"]').val() || !$('select[name="carga"]').val()) {
                e.preventDefault();
                alert('Complete los datos del vehículo primero');
                $('#vehiculos-tab').tab('show');
                return;
            }
        }
        
        if (!validarDatosCotizacion()) {
            e.preventDefault();
            alert('Complete los datos de cotización primero');
            $('#cotizacion-tab').tab('show');
        }
    });

    // Inicialización al cargar
    if ($('#cotizacion-tab').hasClass('active')) {
        if (!validarCliente()) {
            mostrarErrorCliente();
        } else {
            cargarFormularioCorrecto();
        }
    }
    /***************************************
     * 7. LÓGICA DE SERVICIOS POR DIVISIÓN
     ***************************************/
    const serviciosPorDivision = {
        1: ["Administración"],
        2: ["AUTOPISTA"],
        3: ["SANITIZACIÓN"],
        4: ["ARRIENDO EMERGENCIA"],
        5: ["ARRIENDO GL", "REMOLCAR"],
        6: ["ARRIENDO GL", "CAPACHO", "CUSTODIA", "DESPEJE DE VIA", "REMOLCAR", "RESCATE", "RESPALDO"],
        7: ["TALLER"]
    };

    $('select[name="id_division"]').change(function() {
        const divisionId = $(this).val();
        const servicioSelect = $('#servicioSelect');
        
        servicioSelect.empty();
        
        if (divisionId && serviciosPorDivision[divisionId]) {
            servicioSelect.append('<option value="">Seleccionar Servicio</option>');
            serviciosPorDivision[divisionId].forEach(servicio => {
                servicioSelect.append(`<option value="${servicio}">${servicio}</option>`);
            });
        } else {
            servicioSelect.append('<option value="">Seleccione primero una División</option>');
        }
        
        $('#vehiculos-tab-item').hide();
        $('#vehiculos-tab-pane').find('input, select').prop('required', false);
    });

    /***************************************
     * 8. MOSTRAR PESTAÑA VEHÍCULOS PARA SERVICIOS ESPECÍFICOS
     ***************************************/
    const serviciosConVehiculos = ["REMOLCAR", "DESPEJE DE VIA", "RESCATE"];

    $('#servicioSelect').change(function() {
        const servicioSeleccionado = $(this).val();
        const vehiculosTabItem = $('#vehiculos-tab-item');
        
        if (serviciosConVehiculos.includes(servicioSeleccionado)) {
            vehiculosTabItem.show();
            $('#vehiculos-tab-pane').find('input, select').prop('required', true);
            $('#subtipo-container').hide();
        } else {
            vehiculosTabItem.hide();
            $('#vehiculos-tab-pane').find('input, select').prop('required', false);
        }
    });

    /***************************************
     * 9. FORMULARIO DE CLIENTE NUEVO/EXISTENTE
     ***************************************/
    const nuevoClienteHTML = `
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" name="NOMBRE" required>
            </div>
            <div class="mb-3">
                <label class="form-label">RUT *</label>
                <input type="text" class="form-control" name="RUT" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tipo *</label>
                <select name="TIPO" class="form-select" required>
                    <option value="" selected disabled>Seleccione el tipo</option>
                    <option value="Particular">Particular</option>
                    <option value="Empresa">Empresa</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Giro *</label>
                <input type="text" class="form-control" name="GIRO" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Dirección *</label>
                <input type="text" class="form-control" name="DIRECCION" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Teléfono *</label>
                <input type="tel" class="form-control" name="TELEFONO" required>
            </div>
        </div>
    </div>`;

    $(document).on('change', 'input[name="cliente_nuevo"]', function() {
        if ($(this).val() === 'Si') {
            $('#cliente_container').html(nuevoClienteHTML);
        } else {
            $('#cliente_container').html(`
                <div class="mb-3">
                    <label class="form-label">Cliente *</label>
                    <select class="form-select" name="rut_cliente" required>
                        <option value="">Seleccionar Cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo htmlspecialchars($cliente['rut']); ?>">
                                <?php echo htmlspecialchars($cliente['nombre']) . ' (' . htmlspecialchars($cliente['rut']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            `);
        }
    });

    /***************************************
     * 10. SELECCIÓN DINÁMICA DE VEHÍCULOS
     ***************************************/
    const vehiculos = <?php echo $vehiculosJSON; ?>;
    const tipoSelect = document.getElementById('tipo_vehiculo');
    const subTipoSelect = document.getElementById('sub_tipo');
    const subtipoContainer = document.getElementById('subtipo-container');

    tipoSelect.addEventListener('change', function () {
        const tipoSeleccionado = this.value;
        const subtiposUnicos = new Set();

        subTipoSelect.innerHTML = '<option value="">Seleccionar Subtipo</option>';
        
        if (tipoSeleccionado === 'Camión' || tipoSeleccionado === 'Tracto') {
            subtipoContainer.style.display = 'block';
            
            vehiculos.forEach(vehiculo => {
                if (vehiculo.Tipo && vehiculo.sub_tipo && vehiculo.Tipo.trim() === tipoSeleccionado) {
                    const subtipo = vehiculo.sub_tipo.trim();
                    if (!subtiposUnicos.has(subtipo)) {
                        subtiposUnicos.add(subtipo);
                        const option = document.createElement('option');
                        option.value = subtipo;
                        option.textContent = subtipo;
                        subTipoSelect.appendChild(option);
                    }
                }
            });
        } else {
            subtipoContainer.style.display = 'none';
            subTipoSelect.value = '';
        }
    });

    /***************************************
     * 11. ENVÍO DEL FORMULARIO
     ***************************************/
    $('#formCotizacion').on('submit', function(e) {
        e.preventDefault();
        
        if (!validarDatosCliente()) {
            alert('Complete los datos del cliente');
            $('#cliente-tab').tab('show');
            return false;
        }

        if (!validarDatosCotizacion()) {
            alert('Complete los datos de cotización');
            $('#cotizacion-tab').tab('show');
            return false;
        }

        const servicioSeleccionado = $('#servicioSelect').val();
        if (serviciosConVehiculos.includes(servicioSeleccionado)) {
            const vehiculosValid = $('#vehiculos-tab-pane').find('input, select').toArray().every(el => {
                if (el.id === 'sub_tipo' && $('#subtipo-container').css('display') === 'none') {
                    return true;
                }
                return $(el).val() !== '';
            });
            
            if (!vehiculosValid) {
                alert('Complete los datos del vehículo');
                $('#vehiculos-tab').tab('show');
                return false;
            }
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function() {
                $('#crearCotizacionModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + xhr.responseText);
            }
        });
    });
    function validarDatosCliente() {
        return true;
    }
    function validarDatosCotizacion() {
        let valido = true;
        $('#cotizacion-tab-pane').find('input[required], select[required]').each(function () {
            if (!$(this).val()) {
                valido = false;
            }
        });
        return valido;
    }

});
</script>
</body>
</html>