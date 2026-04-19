<?php
// Configuración inicial
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica sesión y redirige si no está autenticado
if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}

// Incluye la conexión a la base de datos
include("database.php");
// Incluye el archivo de notificaciones por correo
include("envio-notificacion-cotizacion.php");
// Nota: la activación de mysqli_report en modo estricto se hará solo al insertar

// Inicializa mensajes, errores y control de modal de confirmación
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
if ($tipo_vehiculo_actual && (stripos($tipo_vehiculo_actual, 'camión') !== false ||
                              stripos($tipo_vehiculo_actual, 'camion') !== false)) {
    $mostrar_camion_extra = true;
}

if ($tipo_vehiculo_actual && stripos($tipo_vehiculo_actual, 'tracto') !== false) {
    $mostrar_tracto_extra = true;
}

// Cargar datos para los selects
$divisiones = cargarDatosSelect($conn, 'division', 'id', 'NOMBRE');
$servicios = cargarDatosSelect($conn, 'servicios', 'id', 'Nombre');
$turnos = cargarDatosSelect($conn, 'turno');
$tipos_camino = cargarDatosSelect($conn, 'tipo_camino');
$comunas = cargarDatosSelect($conn, 'comunas');
// Regiones y comunas con detalle para filtrar por región
$regiones = [];
$comunas_detalle = [];
$resRegs = $conn->query("SELECT DISTINCT region FROM comunas WHERE region IS NOT NULL AND TRIM(region) <> '' ORDER BY region");
if ($resRegs) { while ($r = $resRegs->fetch_assoc()) { $regiones[] = $r['region']; } }
$resCom = $conn->query("SELECT id, nombre, region FROM comunas ORDER BY nombre");
if ($resCom) { while ($r = $resCom->fetch_assoc()) { $comunas_detalle[] = $r; } }
$vehiculos = cargarDatosSelect($conn, 'vehiculos', 'id', 'Tipo');
// Cargar clientes para el select de RUT
$clientes = cargarDatosSelect($conn, 'clientes', 'rut', 'nombre');

// Variables para mantener los valores seleccionados
$sel_division = $_POST['id_division'] ?? '';
$sel_servicio = $_POST['id_servicio'] ?? '';
$sel_turno = $_POST['id_turno'] ?? '';
$sel_tipo_camino = $_POST['tipo_camino'] ?? '';
$sel_comuna_origen = $_POST['id_comuna_origen'] ?? '';
$sel_comuna_destino = $_POST['id_comuna_destino'] ?? '';
$sel_region_origen = $_POST['region_origen'] ?? '';
$sel_region_destino = $_POST['region_destino'] ?? '';
$sel_tipo_vehiculo = $_POST['id_tipo_vehiculo'] ?? '';
$sel_sub_tipo = $_POST['id_sub_tipo'] ?? '';
$sel_cliente = $_POST['rut_cliente'] ?? '';

// Determinar pestaña activa
$pestana_activa = $_POST['pestana_activa'] ?? 'tab1';

// Limpia servicio si la división enviada no es válida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_division']) &&
    !isset($_POST['confirmar']) && !isset($_POST['enviar_confirmacion']) && !isset($_POST['accion'])) {
    $id_division = (int)$_POST['id_division'];
    if ($id_division <= 0) {
        $_POST['id_servicio'] = '';
    }
}

// Activa pestaña de vehículo según el servicio elegido
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
    if (in_array(strtolower(trim($nombre_servicio_check)), ['Remolque', 'Rescate', 'Despeje de via'])) {
        $mostrar_tab_vehiculo = true;
    }
}

// Configuración de costos
$costo_base_php = 0.0;
$costo_por_km_php = 0.0;
$km_base_php = 20;

$sql_costos = "SELECT grupo, Tipo, Nombre, monto FROM costos_variables";
$result_costos = $conn->query($sql_costos);
if ($result_costos) {
    $sum_base = 0.0;
    $sum_por_km = 0.0;
    while ($row = $result_costos->fetch_assoc()) {
        $grupo = trim($row['grupo'] ?? '');
        $monto = isset($row['monto']) ? floatval($row['monto']) : 0.0;
        if ($monto <= 0) { continue; }

        if ($grupo === 'Costo Base') {
            $sum_base += $monto;
        } elseif ($grupo === 'Costo por km') {
            $sum_por_km += $monto;
        }

        // Opcional: detectar 'Km Base' por Nombre si existe
        $nombre_item = strtolower(trim($row['Nombre'] ?? ''));
        $nombre_item = strtr($nombre_item, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'
        ]);
        if (preg_match('/\b(km|kilometro|kilometros)\b.*\bbase\b/', $nombre_item)) {
            $km_base_php = (int)$monto;
        }
    }
    $costo_base_php = $sum_base;
    $costo_por_km_php = $sum_por_km;
    error_log("COSTOS CARGADOS => base={$costo_base_php}, por_km={$costo_por_km_php}, km_base={$km_base_php}");
}

// Detectar tarifa especial del cliente (si aplica)
$tiene_tarifa_especial = false;
$te_precio_base = null;
$te_valor_km = null;
$te_tope_km = null;
$te_valor_custodia = null;
if (!empty($_POST['rut_cliente'])) {
    $stmt_cli = $conn->prepare("SELECT id FROM clientes WHERE rut = ?");
    if ($stmt_cli) {
        $stmt_cli->bind_param("s", $_POST['rut_cliente']);
        $stmt_cli->execute();
        $stmt_cli->bind_result($cli_id);
        $stmt_cli->fetch();
        $stmt_cli->close();
        if (!empty($cli_id)) {
            $stmt_te = $conn->prepare("SELECT precio_base, valor_km, tope_km, valor_custodia FROM tarifa_especial WHERE id_cliente = ?");
            if ($stmt_te) {
                $stmt_te->bind_param("i", $cli_id);
                $stmt_te->execute();
                $stmt_te->bind_result($te_precio_base, $te_valor_km, $te_tope_km, $te_valor_custodia);
                if ($stmt_te->fetch()) {
                    $tiene_tarifa_especial = (floatval($te_precio_base) > 0 || floatval($te_valor_km) > 0);
                }
                $stmt_te->close();
            }
        }
    }
}
if (!$tiene_tarifa_especial) {
    if ($costo_base_php <= 0) {
        $error .= "Error: Costo Base no configurado o es cero. ";
    }
    if ($costo_por_km_php <= 0) {
        $error .= "Error: Costo por km no configurado o es cero.";
    }
}

// Procesamiento de cálculos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'calcular') {
    $monto_calculado = 0;
    $monto_turno = 0;
    $monto_tipo_camino = 0;
    $km = 0; // Inicializar variable km

    if (!empty($_POST['id_comuna_origen']) && !empty($_POST['id_comuna_destino'])) {
        $id_comuna_origen = (int)$_POST['id_comuna_origen'];
        $id_comuna_destino = (int)$_POST['id_comuna_destino'];

        $sql_km = "SELECT km FROM distancias WHERE id_comuna_origen = ? AND id_comuna_destino = ?";
        $stmt_km = $conn->prepare($sql_km);
        $stmt_km->bind_param("ii", $id_comuna_origen, $id_comuna_destino);
        $stmt_km->execute();
        $stmt_km->bind_result($km);
        $stmt_km->fetch();
        $stmt_km->close();

        if ($km > 0) {
            if ($tiene_tarifa_especial) {
                // FORMULA CORREGIDA: precio base + (total km - tope km) * valor km
                // Si tope_km no está definido o es 0, se cobra todo el km (o comportamiento por defecto)
                // Asumimos que si hay tope, los km hasta el tope están cubiertos por el precio base
                
                $km_excedente = 0;
                if (!empty($te_tope_km) && intval($te_tope_km) > 0) {
                    $km_excedente = max(0, $km - intval($te_tope_km));
                } else {
                    $km_excedente = $km; // Si no hay tope, todo se cobra por km (o revisar lógica con cliente)
                }
                
                $monto_calculado = floatval($te_precio_base) + ($km_excedente * floatval($te_valor_km));
                $_POST['tarifa_especial_aplicada'] = 1;
            } else {
                $monto_calculado = $costo_base_php + ($km * $costo_por_km_php);
            }
        }
    }

    if (!empty($_POST['id_turno'])) {
        foreach ($turnos_con_monto as $turno_item) {
            if ((int)$turno_item['id'] === (int)$_POST['id_turno'] && $turno_item['monto'] > 0) {
                $monto_turno = floatval($turno_item['monto']);
                break;
            }
        }
    }

    if (!empty($_POST['tipo_camino'])) {
        foreach ($tipos_camino_con_monto as $tipo_camino_item) {
            if ((int)$tipo_camino_item['id'] === (int)$_POST['tipo_camino'] && $tipo_camino_item['monto'] > 0) {
                // CORRECCION: El monto es unitario por KM
                $monto_tipo_camino = floatval($tipo_camino_item['monto']) * floatval($km);
                break;
            }
        }
    }

    // Obtener tarifa del vehículo si está seleccionado
    $monto_vehiculo = 0;
    if (!empty($_POST['id_tipo_vehiculo'])) {
        $id_tipo_vehiculo = (int)$_POST['id_tipo_vehiculo'];
        
        // Obtener el tipo de vehículo
        $stmt_tipo = $conn->prepare("SELECT Tipo FROM vehiculos WHERE id = ?");
        $stmt_tipo->bind_param("i", $id_tipo_vehiculo);
        $stmt_tipo->execute();
        $stmt_tipo->bind_result($tipo_vehiculo);
        $stmt_tipo->fetch();
        $stmt_tipo->close();
        
        if ($tipo_vehiculo) {
            // 1. Buscar tarifa por TIPO + SUBTIPO (si existe subtipo)
            if (!empty($_POST['id_sub_tipo'])) {
                $sql = "SELECT monto_inicial FROM vehiculos 
                        WHERE Tipo = ? AND sub_tipo = ? 
                        AND (tipo_direccion IS NULL OR tipo_direccion = '') 
                        AND (carga IS NULL OR carga = '')
                        LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $tipo_vehiculo, $_POST['id_sub_tipo']);
                $stmt->execute();
                $stmt->bind_result($monto_subtipo);
                if ($stmt->fetch()) {
                    $monto_vehiculo += floatval($monto_subtipo);
                }
                $stmt->close();
            }
            
            // 2. Buscar tarifa por TIPO DE DIRECCIÓN (independiente)
            if (!empty($_POST['tipo_direccion'])) {
                $sql = "SELECT monto_inicial FROM vehiculos 
                        WHERE (Tipo IS NULL OR Tipo = '') 
                        AND (sub_tipo IS NULL OR sub_tipo = '') 
                        AND tipo_direccion = ? 
                        AND (carga IS NULL OR carga = '')
                        LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $_POST['tipo_direccion']);
                $stmt->execute();
                $stmt->bind_result($monto_direccion);
                if ($stmt->fetch()) {
                    $monto_vehiculo += floatval($monto_direccion);
                }
                $stmt->close();
            }
            
            // 3. Buscar tarifa por CARGA (independiente)
            if (!empty($_POST['carga'])) {
                $sql = "SELECT monto_inicial FROM vehiculos 
                        WHERE (Tipo IS NULL OR Tipo = '') 
                        AND (sub_tipo IS NULL OR sub_tipo = '') 
                        AND (tipo_direccion IS NULL OR tipo_direccion = '') 
                        AND carga = ?
                        LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $_POST['carga']);
                $stmt->execute();
                $stmt->bind_result($monto_carga);
                if ($stmt->fetch()) {
                    $monto_vehiculo += floatval($monto_carga);
                }
                $stmt->close();
            }
        }
    }

    $monto_final = $monto_calculado + $monto_turno + $monto_tipo_camino + $monto_vehiculo;
    $_POST['monto_inicial'] = $monto_final;
    $_POST['monto_turno'] = $monto_turno;
    $_POST['monto_tipo_camino'] = $monto_tipo_camino;
}

// Procesamiento de confirmación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_confirmacion'])) {
    $campos_requeridos = [
        'id_division', 'id_servicio', 'id_turno', 'rut_cliente', 'Fecha_servicio',
        'id_comuna_origen', 'base_salida'
    ];
    $debe_requerir_tipo_camino = false;
    if (!empty($_POST['id_servicio'])) {
        $id_servicio_chk = (int)$_POST['id_servicio'];
        $stmt_chk = $conn->prepare("SELECT Nombre FROM servicios WHERE id = ?");
        if ($stmt_chk) {
            $stmt_chk->bind_param("i", $id_servicio_chk);
            $stmt_chk->execute();
            $stmt_chk->bind_result($nombre_serv);
            $stmt_chk->fetch();
            $stmt_chk->close();
            $nombre_norm = strtolower(trim(strtr($nombre_serv ?? '', [
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'
            ])));
            $div_actual = (string)($_POST['id_division'] ?? '');
            if (($nombre_norm === 'remolque' && ($div_actual === '1' || $div_actual === '6')) ||
                $nombre_norm === 'despeje de via' ||
                $nombre_norm === 'rescate') {
                $debe_requerir_tipo_camino = true;
            }
        }
    }
    if ($debe_requerir_tipo_camino) {
        $campos_requeridos[] = 'tipo_camino';
        $campos_requeridos[] = 'id_comuna_destino';
    }

    $error = '';
    foreach ($campos_requeridos as $campo) {
        if (empty($_POST[$campo])) {
            $error .= "El campo $campo es requerido. (Debug: $nombre_norm) ";
        }
    }

    if (empty($error)) {
        $datos_confirmacion = [
            'division' => obtenerNombre('division', 'NOMBRE', $_POST['id_division'], $conn),
            'servicio' => obtenerNombre('servicios', 'Nombre', $_POST['id_servicio'], $conn),
            'turno' => obtenerNombre('turno', 'nombre', $_POST['id_turno'], $conn),
            'rut_cliente' => $_POST['rut_cliente'],
            'fecha_servicio' => $_POST['Fecha_servicio'],
            'comuna_origen' => obtenerNombre('comunas', 'nombre', $_POST['id_comuna_origen'], $conn),
            'direccion_origen' => $_POST['direccion_origen'] ?? '',
            'comuna_destino' => obtenerNombre('comunas', 'nombre', $_POST['id_comuna_destino'], $conn),
            'direccion_destino' => $_POST['direccion_destino'] ?? '',
            'tipo_camino' => obtenerNombre('tipo_camino', 'nombre', $_POST['tipo_camino'], $conn),
            'base_salida' => $_POST['base_salida']
        ];

        if (!empty($_POST['id_turno'])) {
            foreach ($turnos_con_monto as $turno_item) {
                if ((int)$turno_item['id'] === (int)$_POST['id_turno'] && $turno_item['monto'] > 0) {
                    $datos_confirmacion['monto_turno'] = floatval($turno_item['monto']);
                    break;
                }
            }
        }

        if (!empty($_POST['tipo_camino'])) {
            // Recalcular km para confirmación si es necesario o usar el ya calculado si está en scope
            // Como esto es otro bloque, aseguramos obtener km si no está
            $km_confirm = 0;
             if (!empty($_POST['id_comuna_origen']) && !empty($_POST['id_comuna_destino'])) {
                $stmt_km_c = $conn->prepare("SELECT km FROM distancias WHERE id_comuna_origen = ? AND id_comuna_destino = ?");
                if ($stmt_km_c) {
                    $stmt_km_c->bind_param("ii", $_POST['id_comuna_origen'], $_POST['id_comuna_destino']);
                    $stmt_km_c->execute();
                    $stmt_km_c->bind_result($km_val);
                    if ($stmt_km_c->fetch()) { $km_confirm = $km_val; }
                    $stmt_km_c->close();
                }
             }

            foreach ($tipos_camino_con_monto as $tipo_camino_item) {
                if ((int)$tipo_camino_item['id'] === (int)$_POST['tipo_camino'] && $tipo_camino_item['monto'] > 0) {
                    $datos_confirmacion['monto_tipo_camino'] = floatval($tipo_camino_item['monto']) * floatval($km_confirm);
                    break;
                }
            }
        }

        if (isset($_POST['monto_subtotal']) && is_numeric($_POST['monto_subtotal'])) {
            $datos_confirmacion['monto_subtotal'] = floatval($_POST['monto_subtotal']);
        }
        if (isset($_POST['monto_iva']) && is_numeric($_POST['monto_iva'])) {
            $datos_confirmacion['iva_19'] = floatval($_POST['monto_iva']);
        }
        if (isset($_POST['monto_final']) && is_numeric($_POST['monto_final']) && floatval($_POST['monto_final']) > 0) {
            $datos_confirmacion['monto_a_pagar'] = floatval($_POST['monto_final']);
        }

        if (!empty($_POST['usar_monto_manual']) && isset($_POST['monto_final']) && is_numeric($_POST['monto_final'])) {
            $datos_confirmacion['monto_manual'] = floatval($_POST['monto_final']);
        }

        $mostrar_modal_confirm = true;
    }
}

// Procesamiento final de inserción
// Registra los datos POST para depuración
error_log("DEBUG: POST data received: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar'])) {
    error_log("INICIO INSERCIÓN: POST confirmar recibido");
    // Activar reporte estricto SOLO durante la inserción para no afectar otros flujos
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
    $id_division = (int)$_POST['id_division'];
    $id_servicio = (int)$_POST['id_servicio'];
    $sql = "SELECT Nombre FROM servicios WHERE id = ? AND id_division = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_servicio, $id_division);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $error = "Error: El servicio no corresponde a la división";
        error_log("ERROR VALIDACIÓN: Servicio $id_servicio no corresponde a división $id_division");
    } else {
        error_log("VALIDACIÓN OK: Servicio encontrado para división");
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
            error_log("ERROR COORDINADOR: No se encontró coordinador para usuario: $nombre_usuario");
        } else {
            error_log("COORDINADOR OK: ID=$coordinador_id para usuario: $nombre_usuario");

            // Recoger variables POST
            $id_turno = (int)$_POST['id_turno'];
            // Aceptar tanto 'rut_cliente' (flujo nuevo) como 'rut_CLIENTE' (flujo antiguo)
            $rut_cliente = $_POST['rut_cliente'] ?? ($_POST['rut_CLIENTE'] ?? null);
            if (!$rut_cliente) {
                error_log("⚠️ No se recibió rut_cliente/rut_CLIENTE por POST.");
            }
            $fecha_servicio = $_POST['Fecha_servicio'] ? DateTime::createFromFormat('Y-m-d\TH:i', $_POST['Fecha_servicio'])->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
            $telefono = $_POST['TELEFONO'] ?? '';
            $email = $_POST['email'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $panne = $_POST['panne'] ?? '';
            $tipo_camino = $_POST['tipo_camino'];
            $base_salida = $_POST['base_salida'];
            $estado = "Pendiente";
            $nombre_solicitante = $_POST['nombre_solicitante'] ?? null;
            $id_comuna_origen = (int)$_POST['id_comuna_origen'];
            $id_comuna_destino = (int)$_POST['id_comuna_destino'];
            $tipo_direccion = $_POST['tipo_direccion'] ?? null;
            $carga = $_POST['carga'] ?? null;
            $monto_inicial = $_POST['monto_inicial'] ?? null;
            $total_km = isset($_POST['total_km']) ? floatval($_POST['total_km']) : 0.0;
            // Evitar que valores vacíos se conviertan a 0 y rompan FK
            $id_tipo_vehiculo = (!empty($_POST['id_tipo_vehiculo'])) ? (int)$_POST['id_tipo_vehiculo'] : null;
            $id_sub_tipo = (!empty($_POST['id_sub_tipo'])) ? $_POST['id_sub_tipo'] : null;
            $fecha_creacion = date('Y-m-d H:i:s');

            // Validar tipo de vehículo SOLO si el servicio lo requiere
            $servicio_normalizado = strtolower(trim($nombre_servicio));
            $requiere_vehiculo = in_array($servicio_normalizado, ['remolque', 'rescate', 'despeje de via', 'despeje de vía']);

            // Si el servicio NO requiere vehículo, forzar valores NULL para cumplir FK opcional
            if (!$requiere_vehiculo) {
                $id_tipo_vehiculo = null;
                $id_sub_tipo = null;
            }

            if ($requiere_vehiculo) {
                if (!empty($id_tipo_vehiculo) && $id_tipo_vehiculo > 0) {
                    $stmt_val = $conn->prepare("SELECT COUNT(*) FROM vehiculos WHERE id = ?");
                    $stmt_val->bind_param("i", $id_tipo_vehiculo);
                    $stmt_val->execute();
                    $stmt_val->bind_result($count);
                    $stmt_val->fetch();
                    $stmt_val->close();

                    if ($count === 0) {
                        $error = "Error: El tipo de vehículo seleccionado no es válido.";
                    }
                } else {
                    $error = "Error: Debe seleccionar un tipo de vehículo.";
                }
            }

            if (empty($error)) {
                // Obtener crédito actual del cliente
                $stmt_credito = $conn->prepare("SELECT credito FROM clientes WHERE rut = ?");
                $stmt_credito->bind_param("s", $rut_cliente);
                $stmt_credito->execute();
                $stmt_credito->bind_result($credito_actual);
                $stmt_credito->fetch();
                $stmt_credito->close();

                if ($credito_actual === null) {
                    $credito_actual = 0;
                }

                // Aplicar descuento con crédito según condiciones
                $monto_final = $monto_inicial;
                if ($credito_actual > 0) {
                    if ($monto_inicial > $credito_actual) {
                        $monto_final = $monto_inicial - $credito_actual;
                        $nuevo_credito = 0;
                    } else {
                        $monto_final = 0;
                        $nuevo_credito = $credito_actual - $monto_inicial;
                    }

                    if ($nuevo_credito < 0) {
                        $nuevo_credito = 0;
                    }

                    $stmt_update_credito = $conn->prepare("UPDATE clientes SET credito = ? WHERE rut = ?");
                    $stmt_update_credito->bind_param("ds", $nuevo_credito, $rut_cliente);
                    $stmt_update_credito->execute();
                    $stmt_update_credito->close();
                }

                // Priorizar monto_final si viene del formulario (cálculo por moneda), luego monto manual
                if (isset($_POST['monto_final']) && is_numeric($_POST['monto_final']) && floatval($_POST['monto_final']) > 0) {
                    $monto_final = floatval($_POST['monto_final']);
                    if (isset($_POST['monto_subtotal']) && is_numeric($_POST['monto_subtotal'])) {
                        $monto_inicial = floatval($_POST['monto_subtotal']);
                    } else {
                        $monto_inicial = round($monto_final / 1.19, 2);
                    }
                }
                // Aquí viene la clave: siempre usar el monto manual si existe, si no, usar el monto final calculado (con o sin descuento)
                if (isset($_POST['monto_manual']) && is_numeric($_POST['monto_manual'])) {
                    $monto_inicial = floatval($_POST['monto_manual']);
                    $monto_final = $monto_inicial;
                }

                // Obtener montos de turno y tipo de camino
                $monto_turno = 0;
                $monto_tipo_camino = 0;

                if (!empty($_POST['id_turno'])) {
                    foreach ($turnos_con_monto as $turno_item) {
                        if ((int)$turno_item['id'] === (int)$_POST['id_turno'] && $turno_item['monto'] > 0) {
                            $monto_turno = floatval($turno_item['monto']);
                            break;
                        }
                    }
                }

                if (!empty($_POST['tipo_camino'])) {
                    foreach ($tipos_camino_con_monto as $tipo_camino_item) {
                        if ((int)$tipo_camino_item['id'] === (int)$_POST['tipo_camino'] && $tipo_camino_item['monto'] > 0) {
                            $monto_tipo_camino = floatval($tipo_camino_item['monto']);
                            break;
                        }
                    }
                }

                // Resolver nombre de tipo_camino desde el id enviado en el formulario
                $tipo_camino_id = isset($_POST['tipo_camino']) ? (int)$_POST['tipo_camino'] : 0;
                $tipo_camino_nombre = obtenerNombre('tipo_camino', 'nombre', $tipo_camino_id, $conn);
                if (!$tipo_camino_nombre) { $tipo_camino_nombre = ''; }

                // Preparar e insertar cotización con monto final (manual o calculado)
                // Adaptado al esquema: usar id_servicio (int) y tipo_camino (varchar nombre)
                // SE AGREGA: direccion_origen y direccion_destino
                $direccion_origen = $_POST['direccion_origen'] ?? null;
                $direccion_destino = $_POST['direccion_destino'] ?? null;

                // DEBUG TEMPORAL: Guardar datos recibidos para inspección
                file_put_contents('debug_insert.txt', date('Y-m-d H:i:s') . " - Origen: " . json_encode($direccion_origen) . " - Destino: " . json_encode($direccion_destino) . " - POST: " . json_encode($_POST) . "\n", FILE_APPEND);

                $sql_insert = "INSERT INTO cotizaciones (
                    id_division, id_turno, rut_CLIENTE, id_servicio, Fecha_servicio,
                    telefono_CLIENTE, email_CLIENTE, Descripcion, Panne,
                    tipo_camino, base_salida, estado,
                    coordinador_id, id_comuna_origen, id_comuna_destino,
                    tipo_direccion, carga, monto_inicial, total_km,
                    id_tipo_vehiculo, id_sub_tipo, monto_turno, monto_tipo_camino, nombre_solicitante, monto_final, fecha_creacion,
                    direccion_origen, direccion_destino
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt_insert = $conn->prepare($sql_insert);

                if (!$stmt_insert) {
                    error_log("Error al preparar la consulta: " . $conn->error);
                    $error = "Error interno del sistema.";
                } else {
                    // Enlazar parámetros y validar que no falle
                    // s (string) x2 added at the end
                    $bind_ok = $stmt_insert->bind_param(
                        "iisissssssssiiissddisddsdsss",
                        $id_division,          // i
                        $id_turno,             // i
                        $rut_cliente,          // s
                        $id_servicio,          // i
                        $fecha_servicio,       // s (DATETIME)
                        $telefono,             // s
                        $email,                // s
                        $descripcion,          // s
                        $panne,                // s
                        $tipo_camino_nombre,   // s
                        $base_salida,          // s
                        $estado,               // s
                        $coordinador_id,       // i
                        $id_comuna_origen,     // i
                        $id_comuna_destino,    // i
                        $tipo_direccion,       // s (nullable)
                        $carga,                // s (nullable)
                        $monto_inicial,        // d
                        $total_km,             // d
                        $id_tipo_vehiculo,     // i (nullable)
                        $id_sub_tipo,          // s (nullable)
                        $monto_turno,          // d
                        $monto_tipo_camino,    // d
                        $nombre_solicitante,   // s
                        $monto_final,          // d
                        $fecha_creacion,       // s (DATETIME)
                        $direccion_origen,     // s
                        $direccion_destino     // s
                    );

                    if (!$bind_ok) {
                        $error = "Error al enlazar parámetros para la inserción.";
                        error_log("ERROR bind_param: " . $conn->error);
                    } else {
                        // Ejecutar y verificar que realmente se inserta 1 fila
                        if ($stmt_insert->execute()) {
                            if ($stmt_insert->affected_rows === 1) {
                                $id_cotizacion = $conn->insert_id;
                                $mensaje = "Cotización creada correctamente. ID: " . $id_cotizacion;
                                error_log("Inserción exitosa. ID=" . $id_cotizacion);
                                
                                // Obtener nombre del cliente para la notificación
                                $nombre_cliente_notif = '';
                                $stmt_cli_notif = $conn->prepare("SELECT nombre FROM clientes WHERE rut = ?");
                                if ($stmt_cli_notif) {
                                    $stmt_cli_notif->bind_param("s", $rut_cliente);
                                    $stmt_cli_notif->execute();
                                    $stmt_cli_notif->bind_result($nombre_cliente_notif);
                                    $stmt_cli_notif->fetch();
                                    $stmt_cli_notif->close();
                                }
                                
                                // Enviar notificación por correo (sin bloquear el proceso)
                                enviarNotificacionCotizacion($id_cotizacion, $nombre_cliente_notif, $nombre_usuario, $conn);
                                
                                $_POST = [];
                                $mostrar_modal_confirm = false;
                            } else {
                                $error = "La inserción no afectó filas. Revise los datos.";
                                error_log("INSERT ejecutado pero affected_rows=" . $stmt_insert->affected_rows . ", stmt_error=" . $stmt_insert->error . ", conn_error=" . $conn->error);
                                $mostrar_modal_confirm = false;
                            }
                        } else {
                            $error = "Error al guardar cotización: " . $stmt_insert->error;
                            error_log("ERROR execute: " . $stmt_insert->error . ", conn_error=" . $conn->error);
                            $mostrar_modal_confirm = false;
                        }
                    }

                    $stmt_insert->close();
                }
            }
        }
    }
    } catch (mysqli_sql_exception $ex) {
        $error = "Error al guardar cotización: " . $ex->getMessage();
        error_log("EXCEPCIÓN MYSQLI: " . $ex->getMessage());
        $mostrar_modal_confirm = false;
    }
    // Failsafe: si confirmamos y no hay ni mensaje ni error, mostrar un error genérico
    if (empty($mensaje) && empty($error)) {
        $error = "No se pudo completar la inserción ni obtener detalle del error.";
        $mostrar_modal_confirm = false;
        error_log("FAILSAFE: Confirmar ejecutado sin mensaje ni error visible.");
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
    // Normalizamos los nombres de columnas al alias 'id' y 'nombre' para el consumo del frontend
    $sql = "SELECT $id AS id, $nombre AS nombre FROM $tabla $where ORDER BY $nombre";
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
    return $nombre ?? '';
}

// Cargar servicios según división seleccionada
if (!empty($_POST['id_division'])) {
    $id_division_sel = (int)$_POST['id_division'];
    // Alias a 'nombre' para mantener compatibilidad con el render del select
    $sql_servicios = "SELECT id, Nombre AS nombre FROM servicios WHERE id_division = ? ORDER BY Nombre";
    $stmt_servicios = $conn->prepare($sql_servicios);
    $stmt_servicios->bind_param("i", $id_division_sel);
    $stmt_servicios->execute();
    $result_servicios = $stmt_servicios->get_result();
    $servicios = [];
    while ($row = $result_servicios->fetch_assoc()) {
        $servicios[] = $row;
    }
    $stmt_servicios->close();
}

?>
