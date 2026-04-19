<?php
session_start();
include("database.php");
$usuario = $_SESSION['NOMBRE_USUARIO'];
include("rol-cargos.php");

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asociados | Premium Panel</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="css/global-premium.css" rel="stylesheet" />
    <link href="css/gestion-wide.css" rel="stylesheet" />
    <link href="css/asociados.css" rel="stylesheet" />
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
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($usuario); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdown">
                        <?php if ($opcionesAdicionales)
                            echo $opcionesAdicionales; ?>
                        <li><a class="dropdown-item" href="clientes.php"></i>Clientes</a>
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

    <div class="main-container-wide">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-title">
                <h2><i class="fas fa-users-cog me-2"></i>Gestión de Asociados</h2>
                <p class="text-muted mb-0">Administre la información de sus asociados de forma centralizada.</p>
            </div>
            <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                <i class="fas fa-plus me-2"></i>Nuevo Asociado
            </button>
        </div>

        <!-- Alertas -->
        <div id="alertPlaceholder"></div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="asociadosTable">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>RUT</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Vehículo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAsociados">
                        <!-- Se cargará vía AJAX -->
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0">Cargando asociados...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Agregar -->
    <div class="modal fade" id="modalAgregar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Agregar Nuevo Asociado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formAgregar">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="NOMBRE" required placeholder="Ej: Juan Pérez">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RUT</label>
                            <input type="text" class="form-control" name="RUT" required placeholder="12.345.678-9">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="number" class="form-control" name="TELEFONO" placeholder="912345678">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="EMAIL" placeholder="correo@ejemplo.com">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehículo</label>
                            <input type="text" class="form-control" name="VEHICULO" placeholder="Ej: Volvo FH16">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-premium">Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title text-primary"><i class="fas fa-edit me-2"></i>Editar Asociado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditar">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="NOMBRE" id="edit_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RUT</label>
                            <input type="text" class="form-control" name="RUT" id="edit_rut" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="number" class="form-control" name="TELEFONO" id="edit_telefono">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="EMAIL" id="edit_email">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Vehículo</label>
                            <input type="text" class="form-control" name="VEHICULO" id="edit_vehiculo">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-premium">Actualizar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JS Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            listarAsociados();

            // Guardar Nuevo
            $('#formAgregar').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'ajax/guardar-asociado.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (resp) {
                        if (resp.success) {
                            $('#modalAgregar').modal('hide');
                            $('#formAgregar')[0].reset();
                            showAlert('success', 'Asociado guardado correctamente.');
                            listarAsociados();
                        } else {
                            showAlert('danger', resp.error || 'Error al guardar.');
                        }
                    }
                });
            });

            // Actualizar
            $('#formEditar').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'ajax/actualizar-asociado.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (resp) {
                        if (resp.success) {
                            $('#modalEditar').modal('hide');
                            showAlert('success', 'Asociado actualizado correctamente.');
                            listarAsociados();
                        } else {
                            showAlert('danger', resp.error || 'Error al actualizar.');
                        }
                    }
                });
            });
        });

        function listarAsociados() {
            $.get('ajax/listar-asociados.php', function (resp) {
                if (resp.success) {
                    let html = '';
                    if (resp.data.length === 0) {
                        html = '<tr><td colspan="6" class="text-center py-4">No se encontraron asociados.</td></tr>';
                    } else {
                        resp.data.forEach(item => {
                            html += `
                                <tr style="animation-delay: ${Math.random() * 0.5}s">
                                    <td><div class="fw-600">${item.NOMBRE}</div></td>
                                    <td>${item.RUT}</td>
                                    <td>${item.TELEFONO || '-'}</td>
                                    <td>${item.EMAIL || '-'}</td>
                                    <td><span class="text-primary"><i class="fas fa-truck me-1"></i>${item.VEHICULO || '-'}</span></td>
                                    <td class="text-end">
                                        <div class="action-btns justify-content-end">
                                            <button class="btn btn-action btn-edit" title="Editar" onclick="abrirEditar(${item.id})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-action btn-delete" title="Eliminar" onclick="eliminarAsociado(${item.id})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#tbodyAsociados').html(html);
                }
            });
        }

        function abrirEditar(id) {
            $.get('ajax/obtener-asociado.php', { id: id }, function (resp) {
                if (resp.success) {
                    const d = resp.data;
                    $('#edit_id').val(d.id);
                    $('#edit_nombre').val(d.NOMBRE);
                    $('#edit_rut').val(d.RUT);
                    $('#edit_telefono').val(d.TELEFONO);
                    $('#edit_email').val(d.EMAIL);
                    $('#edit_vehiculo').val(d.VEHICULO);
                    $('#modalEditar').modal('show');
                }
            });
        }

        function eliminarAsociado(id) {
            if (confirm('¿Está seguro de que desea eliminar este asociado? Esta acción no se puede deshacer.')) {
                $.post('ajax/eliminar-asociado.php', { id: id }, function (resp) {
                    if (resp.success) {
                        showAlert('success', 'Asociado eliminado correctamente.');
                        listarAsociados();
                    } else {
                        showAlert('danger', resp.error || 'Error al eliminar.');
                    }
                });
            }
        }

        function showAlert(type, msg) {
            const html = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                    ${msg}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('#alertPlaceholder').html(html);
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }
    </script>
</body>

</html>