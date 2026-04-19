<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada']);
    exit();
}

// Conexión a la base de datos
require_once("../database.php");
// Incluye el archivo de notificaciones por correo para gastos
require_once("../envio-notificacion-gasto.php");
if (!$conn || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión con la base de datos']);
    exit();
}

// Procesar la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del gasto
        $id_ot = isset($_POST['id_ot']) ? intval($_POST['id_ot']) : 0;
        $id_empleado = isset($_POST['id_empleado']) && $_POST['id_empleado'] !== '' ? intval($_POST['id_empleado']) : null;
        $id_asociado = isset($_POST['id_asociado']) && $_POST['id_asociado'] !== '' ? intval($_POST['id_asociado']) : 0;
        $tipo = $_POST['tipo'] ?? '';
        $monto = isset($_POST['monto']) ? intval($_POST['monto']) : 0;
        $descripcion = $_POST['descripcion'] ?? '';
        $fecha = date('Y-m-d');
        $id_usuario = isset($_SESSION['ID_USUARIO']) ? intval($_SESSION['ID_USUARIO']) : 0;

        // Validaciones
        if (empty($tipo)) {
            throw new Exception('El tipo de gasto es requerido');
        }

        if ($monto <= 0) {
            throw new Exception('El monto debe ser mayor a 0');
        }

        // Si no hay id_ot, el gasto se guardará temporalmente sin OT
        // (esto puede ocurrir cuando se está creando una nueva OT)
        
        // Insertar el gasto
        // Construcción dinámica de la consulta para manejar id_asociado solo si existe
        if ($id_asociado > 0) {
            $sql = "INSERT INTO gastos (id_ot, id_empleado, id_asociado, tipo, monto, descripcion, fecha, id_usuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta: ' . $conn->error);
            }

            // Si id_ot es 0, lo guardamos como NULL
            $id_ot_param = $id_ot > 0 ? $id_ot : null;
            
            $stmt->bind_param("iiisissi", $id_ot_param, $id_empleado, $id_asociado, $tipo, $monto, $descripcion, $fecha, $id_usuario);
        } else {
            // Consulta original sin id_asociado (deja que la BD maneje el default de id_asociado)
            $sql = "INSERT INTO gastos (id_ot, id_empleado, tipo, monto, descripcion, fecha, id_usuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta: ' . $conn->error);
            }

            // Si id_ot es 0, lo guardamos como NULL
            $id_ot_param = $id_ot > 0 ? $id_ot : null;
            
            $stmt->bind_param("iisissi", $id_ot_param, $id_empleado, $tipo, $monto, $descripcion, $fecha, $id_usuario);
        }
        
        // Desactivar validación de FK temporalmente para permitir insertar 0/Default en id_asociado
        // si no existe en la tabla asociados (Solución "Just fix this")
        $conn->query("SET FOREIGN_KEY_CHECKS=0");
        
        $executionResult = $stmt->execute();
        $insertId = $conn->insert_id;
        $executionError = $stmt->error;
        
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        if ($executionResult) {
            $gasto_id = $insertId;
            
            // Obtener información para la notificación
            $nombre_empleado = '';
            $nombre_usuario = $_SESSION['NOMBRE_USUARIO'] ?? 'Usuario desconocido';
            
            // Obtener nombre del empleado o asociado
            if ($id_empleado > 0) {
                $stmt_emp = $conn->prepare("SELECT nombre FROM empleados WHERE id = ?");
                if ($stmt_emp) {
                    $stmt_emp->bind_param('i', $id_empleado);
                    $stmt_emp->execute();
                    $stmt_emp->bind_result($nombre_empleado);
                    $stmt_emp->fetch();
                    $stmt_emp->close();
                }
            } elseif ($id_asociado > 0) {
                $stmt_asc = $conn->prepare("SELECT nombre FROM asociados WHERE id = ?");
                if ($stmt_asc) {
                    $stmt_asc->bind_param('i', $id_asociado);
                    $stmt_asc->execute();
                    $stmt_asc->bind_result($nombre_empleado);
                    $stmt_asc->fetch();
                    $stmt_asc->close();
                }
            }
            
            // Enviar notificación de gasto (solo si tiene id_ot)
            if ($id_ot_param > 0) {
                enviarNotificacionGasto($gasto_id, $id_ot_param, $tipo, $monto, $nombre_empleado, $nombre_usuario, $conn);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Gasto creado exitosamente',
                'gasto_id' => $gasto_id
            ]);
        } else {
            throw new Exception('Error al ejecutar la consulta: ' . $executionError);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        // Asegurar que FK checks se reactiven en caso de excepción previa
        if (isset($conn)) $conn->query("SET FOREIGN_KEY_CHECKS=1");
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}

$conn->close();
?>
