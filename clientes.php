<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

include("database.php");

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}
$mensaje = '';
$usuario = $_SESSION['NOMBRE_USUARIO'];
include("rol-cargos.php");

function limpiarRut($rut)
{
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    return strtoupper($rut);
}

function formatearRut($rut)
{
    $rut = limpiarRut($rut);
    if (strlen($rut) < 2)
        return $rut;

    $cuerpo = substr($rut, 0, -1);
    $dv = substr($rut, -1);

    $cuerpoFormateado = '';
    $cont = 0;
    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $cuerpoFormateado = $cuerpo[$i] . $cuerpoFormateado;
        $cont++;
        if ($cont == 3 && $i != 0) {
            $cuerpoFormateado = '.' . $cuerpoFormateado;
            $cont = 0;
        }
    }
    return $cuerpoFormateado . '-' . $dv;
}

function rutParaHtml($rut)
{
    $limpio = limpiarRut($rut);
    return htmlspecialchars($limpio, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Primero verificar si es una eliminación
    if (isset($_POST['eliminar'])) {
        if (!$accesoAdministrador || $soloLectura || $bloquearFueraDeOT) {
            $mensaje = "Sin permiso para eliminar cliente";
            echo "<script>alert('Sin permiso para eliminar');</script>";
        } else {
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

    }

    // Si es una actualización de cliente
    elseif (isset($_POST['idCliente'])) {
        if ($soloLectura || $bloquearFueraDeOT || !($accesoAdministrador || $esCentral)) {
            $mensaje = "Sin permiso para actualizar cliente";
            echo "<script>alert('Sin permiso para actualizar');</script>";
        } else {
            $id = $_POST['idCliente'];
            $nombre = htmlspecialchars(trim($_POST['NOMBRE'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $rut = htmlspecialchars(trim($_POST['RUT'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $tipo = htmlspecialchars(trim($_POST['TIPO'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $direccion = htmlspecialchars(trim($_POST['DIRECCION'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $telefono = htmlspecialchars(trim($_POST['TELEFONO'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $email = htmlspecialchars(trim($_POST['EMAIL'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $giro = htmlspecialchars(trim($_POST['GIRO'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $esAdmin = isset($accesoAdministrador) && $accesoAdministrador === true;
            $credito = $esAdmin ? htmlspecialchars(trim($_POST['credito'] ?? ''), ENT_NOQUOTES, 'UTF-8') : null;

            if ($esAdmin) {
                $sqlActualizar = "UPDATE clientes SET NOMBRE=?, RUT=?, TIPO=?, DIRECCION=?, TELEFONO=?, EMAIL=?, GIRO=?, credito=? WHERE ID=?";
                $stmt = $conn->prepare($sqlActualizar);
                if ($stmt) {
                    $stmt->bind_param(
                        "ssssssssi",
                        $nombre,
                        $rut,
                        $tipo,
                        $direccion,
                        $telefono,
                        $email,
                        $giro,
                        $credito,
                        $id
                    );
                    if ($stmt->execute()) {
                        $mensaje = "Cliente actualizado correctamente";
                        echo "<script>alert('Cliente actualizado');</script>";
                    } else {
                        $mensaje = "Error al actualizar el cliente: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $mensaje = "Error al preparar la consulta: " . $conn->error;
                }
            } else {
                $sqlActualizar = "UPDATE clientes SET NOMBRE=?, RUT=?, TIPO=?, DIRECCION=?, TELEFONO=?, EMAIL=?, GIRO=? WHERE ID=?";
                $stmt = $conn->prepare($sqlActualizar);
                if ($stmt) {
                    $stmt->bind_param(
                        "sssssssi",
                        $nombre,
                        $rut,
                        $tipo,
                        $direccion,
                        $telefono,
                        $email,
                        $giro,
                        $id
                    );
                    if ($stmt->execute()) {
                        $mensaje = "Cliente actualizado correctamente";
                        echo "<script>alert('Cliente actualizado');</script>";
                    } else {
                        $mensaje = "Error al actualizar el cliente: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $mensaje = "Error al preparar la consulta: " . $conn->error;
                }
            }
        }
    }
    // Si no es eliminación ni actualización, procesar como nuevo cliente
    else {
        if ($soloLectura || $bloquearFueraDeOT || !$puedeCrearCliente) {
            $mensaje = "Sin permiso para crear cliente";
            echo "<script>alert('Sin permiso para crear');</script>";
        } else {
            $nombre = htmlspecialchars(trim($_POST['NOMBRE'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $rut = htmlspecialchars(trim($_POST['RUT'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $tipo = htmlspecialchars(trim($_POST['TIPO'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $direccion = htmlspecialchars(trim($_POST['DIRECCION'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $telefono = htmlspecialchars(trim($_POST['TELEFONO'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $email = htmlspecialchars(trim($_POST['EMAIL'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $giro = htmlspecialchars(trim($_POST['GIRO'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $esAdmin = isset($accesoAdministrador) && $accesoAdministrador === true;
            $creditoInput = htmlspecialchars(trim($_POST['credito'] ?? ''), ENT_NOQUOTES, 'UTF-8');
            $credito = $esAdmin ? ($creditoInput !== '' ? $creditoInput : '300000') : '300000';
            $sqlinsertar = "INSERT INTO clientes (NOMBRE, RUT, TIPO, DIRECCION, TELEFONO, EMAIL, GIRO, credito) VALUES(?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sqlinsertar);
            if ($stmt) {
                $stmt->bind_param(
                    "ssssssss",
                    $nombre,
                    $rut,
                    $tipo,
                    $direccion,
                    $telefono,
                    $email,
                    $giro,
                    $credito
                );
                if ($stmt->execute()) {
                    $mensaje = "El cliente se registró exitosamente.";
                } else {
                    $mensaje = "Hubo un error en el registro: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $mensaje = "Error al preparar la consulta: " . $conn->error;
            }
        }
    }
}
// Consulta a la base de datos para obtener todos los clientes (siempre después de las operaciones)

$sql = "SELECT DISTINCT * FROM clientes";
$result = $conn->query($sql);
$clientes = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }

}
// Establecer el charset a UTF-8
header('Content-Type: text/html; charset=UTF-8');
$conn->close();

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Clientes | Premium Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/global-premium.css" rel="stylesheet">
    <link href="css/clientes.css" rel="stylesheet">
</head>

<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light px-3 shadow-sm mb-4">
        <div class="container-fluid">
            <!-- Navegación izquierda -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="ot.php">Órdenes de Trabajo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cotizaciones.php">Cotizaciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reporte.php">Reporte</a>
                </li>
                <?php if ($opcionesAdicionales2)
                    echo $opcionesAdicionales2; ?>
            </ul>

            <!-- Usuario + cerrar sesión -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link" href="#" id="notifBell" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger rounded-pill" id="notifCount">0</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-dark fw-bold" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($usuario); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdown">
                        <?php if ($opcionesAdicionales)
                            echo $opcionesAdicionales; ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="cerrar-sesion.php"><i
                                    class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-title">
                <h2><i class="fas fa-building me-2"></i>Gestión de Clientes</h2>
                <p class="text-muted mb-0">Administre la base de datos de clientes, RUT y líneas de crédito.</p>
            </div>
            <div class="header-actions">
                <?php if ($puedeCrearCliente && !$bloquearFueraDeOT && !$soloLectura): ?>
                    <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#crearClienteModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Cliente
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="search-container mb-4">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="buscarInput" class="form-control border-start-0 ps-0"
                    placeholder="Buscar por RUT o Nombre...">
            </div>
        </div>


        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>RUT</th>
                            <th>Nombre</th>
                            <th>Giro</th>
                            <?php if ($accesoAdministrador): ?>
                                <th>Línea de Crédito</th>
                            <?php endif; ?>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($clientes) > 0) {
                            foreach ($clientes as $row) {
                                echo "<tr>";
                                echo "<td class='col-rut' title=\"" . rutParaHtml($row['RUT']) . "\">" . formatearRut($row['RUT']) . "</td>";
                                echo "<td class='col-nombre' title=\"" . htmlspecialchars($row['NOMBRE'], ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($row['NOMBRE'], ENT_QUOTES, 'UTF-8') . "</td>";
                                echo "<td class='col-giro' title=\"" . htmlspecialchars($row['GIRO'], ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($row['GIRO'], ENT_QUOTES, 'UTF-8') . "</td>";
                                if ($accesoAdministrador) {
                                    echo "<td class='col-credito' title=\"" . htmlspecialchars($row['credito'], ENT_QUOTES, 'UTF-8') . "\">" . htmlspecialchars($row['credito'], ENT_QUOTES, 'UTF-8') . "</td>";
                                }
                                echo "<td class='acciones-cell col-acciones'><div class='acciones-wrap d-flex align-items-center justify-content-center gap-2 flex-nowrap'>";
                                if ($puedeCrearCliente && !$bloquearFueraDeOT && !$soloLectura) {
                                    echo "<button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#modificarClienteModal' 
                                            data-id=\"" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-nombre=\"" . htmlspecialchars($row['NOMBRE'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-rut=\"" . htmlspecialchars($row['RUT'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-direccion=\"" . htmlspecialchars($row['DIRECCION'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-telefono=\"" . htmlspecialchars($row['TELEFONO'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-email=\"" . htmlspecialchars($row['EMAIL'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-giro=\"" . htmlspecialchars($row['GIRO'], ENT_QUOTES, 'UTF-8') . "\" 
                                            data-credito=\"" . htmlspecialchars($row['credito'], ENT_QUOTES, 'UTF-8') . "\"> 
                                        <i class='fa-solid fa-edit'></i>
                                    </button>";
                                }
                                if ($accesoAdministrador && !$bloquearFueraDeOT && !$soloLectura) {
                                    echo "<form method='POST' class='d-inline'>
                                        <input type='hidden' name='id' value=\"" . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . "\">";
                                    echo "<button type='submit' class='btn btn-danger btn-sm' name='eliminar' onclick='return confirm(`¿Está seguro de eliminar este cliente?`);'>
                                            <i class='fa-solid fa-trash'></i>
                                        </button></form>";
                                }
                                echo "</div></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No hay clientes registrados.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php if ($puedeCrearCliente && !$bloquearFueraDeOT && !$soloLectura): ?>
                <div class="modal fade" id="crearClienteModal" tabindex="-1" aria-labelledby="crearClienteModalLabel"
                    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="crearClienteModalLabel">Crear Nuevo Cliente</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formCliente" method="POST"
                                    action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="nombre" class="form-label">Nombre *</label>
                                                <input type="text" class="form-control" id="nombre" name="NOMBRE" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="rut" class="form-label">RUT *</label>
                                                <input type="text" class="form-control" id="rut" name="RUT" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Correo electrónico</label>
                                                <input type="text" class="form-control" id="email" name="EMAIL">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="giro" class="form-label">Giro</label>
                                                <input type="text" class="form-control" id="giro" name="GIRO">
                                            </div>
                                            <div class="mb-3">
                                                <label for="direccion" class="form-label">Dirección</label>
                                                <input type="text" class="form-control" id="direccion" name="DIRECCION">
                                            </div>
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Teléfono *</label>
                                                <input type="tel" class="form-control" id="telefono" name="TELEFONO"
                                                    placeholder="912345678">
                                            </div>
                                            <?php if ($accesoAdministrador): ?>
                                                <div class="mb-3">
                                                    <label for="credito" class="form-label">Crédito</label>
                                                    <input type="text" class="form-control" id="credito" name="credito">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <small class="text-muted">Los campos marcados con * son obligatorios</small>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" form="formCliente" class="btn btn-primary">Guardar Cliente</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="modal fade" id="modificarClienteModal" tabindex="-1"
                aria-labelledby="modificarClienteModalLabel" aria-hidden="true" data-bs-backdrop="static"
                data-bs-keyboard="false">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title" id="modificarClienteModalLabel">Modificar Cliente</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="modal-body">
                                <input type="hidden" id="idCliente" name="idCliente">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editNombre" class="form-label">Nombre *</label>
                                            <input type="text" class="form-control" id="editNombre" name="NOMBRE"
                                                readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editRut" class="form-label">RUT *</label>
                                            <input type="text" class="form-control" id="editRut" name="RUT" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editTipo" class="form-label">Tipo *</label>
                                            <select id="editTipo" name="TIPO" class="form-select" readonly>
                                                <option value="Particular">Particular</option>
                                                <option value="Empresa">Empresa</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editEmail" class="form-label">Correo electrónico *</label>
                                            <input type="text" class="form-control" id="editEmail" name="EMAIL"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="editGiro" class="form-label">Giro *</label>
                                            <input type="text" class="form-control" id="editGiro" name="GIRO" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editDireccion" class="form-label">Dirección *</label>
                                            <input type="text" class="form-control" id="editDireccion" name="DIRECCION"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="editTelefono" class="form-label">Teléfono *</label>
                                            <input type="tel" class="form-control" id="editTelefono" name="TELEFONO"
                                                required placeholder="912345678">
                                        </div>
                                        <?php if ($accesoAdministrador): ?>
                                            <div class="mb-3">
                                                <label for="editCredito" class="form-label">Crédito</label>
                                                <input type="text" class="form-control" id="editCredito" name="credito">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-muted">Los campos marcados con * son obligatorios</small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            // Llenar el formulario con los datos del cliente para modificar
            const modificarClienteModal = document.getElementById('modificarClienteModal');
            modificarClienteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('idCliente').value = button.getAttribute('data-id');
                document.getElementById('editNombre').value = button.getAttribute('data-nombre');
                document.getElementById('editRut').value = button.getAttribute('data-rut');
                document.getElementById('editTipo').value = button.getAttribute('data-tipo');
                document.getElementById('editDireccion').value = button.getAttribute('data-direccion');
                document.getElementById('editTelefono').value = button.getAttribute('data-telefono');
                document.getElementById('editEmail').value = button.getAttribute('data-email');
                document.getElementById('editGiro').value = button.getAttribute('data-giro');
                const editCreditoEl = document.getElementById('editCredito');
                if (editCreditoEl) {
                    editCreditoEl.value = button.getAttribute('data-credito');
                }
            });

            // Buscador por RUT o Nombre en la tabla de clientes
            document.getElementById('buscarInput').addEventListener('input', function () {
                const filtro = this.value.toLowerCase();
                const filas = document.querySelectorAll('table tbody tr');

                filas.forEach(fila => {
                    // Obtener texto de RUT y Nombre (columnas 0 y 1)
                    const rut = fila.cells[0].textContent.toLowerCase();
                    const nombre = fila.cells[1].textContent.toLowerCase();

                    if (rut.includes(filtro) || nombre.includes(filtro)) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
            });
        </script>

</body>

</html>