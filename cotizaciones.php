<?php
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}

$usuario = $_SESSION['NOMBRE_USUARIO'];
include("rol-cargos.php");
include("database.php");

// Control para mostrar el modal
$mostrar_modal = ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['confirmar'])) || isset($_GET['abrir_modal']);

// Paginación
$limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 50;
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina < 1)
    $pagina = 1;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Listado de Cotizaciones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="css/global-premium.css" rel="stylesheet" />
    <link href="css/cotizaciones.css" rel="stylesheet" />
    <style>
        /* El navbar de global-premium.css ya es light/glass */

        /* Corregir visibilidad de modales */
        .modal {
            z-index: 99999 !important;
        }

        /* Asegurar que Select2 sea visible sobre el modal */
        .select2-container {
            z-index: 1300 !important;
        }

        .filter-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .table-card {
            border-radius: 15px;
            /* overflow: hidden;  <-- ELIMINADO para permitir dropdowns visibles */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: visible !important;
            /* Forzar visibilidad */
        }

        .dropdown-menu {
            z-index: 10000 !important;
            /* Asegurar que floten sobre todo */
        }

        .status-badge {
            font-weight: 600;
            padding: 0.5em 1em;
            border-radius: 30px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light px-3 shadow-sm mb-4">
        <div class="container-fluid">
            <!-- Navegación izquierda -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="ot.php">Órdenes de Trabajo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active fw-bold" href="cotizaciones.php">Cotizaciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reporte.php">Reporte</a>
                </li>
                <?php if ($opcionesAdicionales2)
                    echo $opcionesAdicionales2; ?>
            </ul>

            <!-- Usuario + cerrar sesión -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-dark fw-bold" href="#" id="navbarUsuario" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($usuario); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarUsuario">
                        <?php if ($opcionesAdicionales)
                            echo $opcionesAdicionales; ?>
                        <li><a class="dropdown-item" href="clientes.php"></i>Clientes</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="cerrar-sesion.php"><i
                                    class="fas fa-sign-out-alt me-2"></i>Cerrar sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1">Listado de Cotizaciones</h2>
                <p class="text-muted mb-0">Gestión centralizada de servicios y propuestas comerciales.</p>
            </div>
            <div>
                <?php if ($puedeCrearCotizacion && !$soloLectura && !$bloquearFueraDeOT): ?>
                    <button type="button" class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#modalCrearCotizacion">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Cotización
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div id="globalAlert" class="mb-3"></div>

        <!-- Panel de Filtros Premium -->
        <div class="card filter-card border-0 mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">ID Cotización</label>
                        <input type="text" id="filterId" class="form-control" placeholder="Ej: 1234">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Cliente / RUT / Servicio</label>
                        <input type="text" id="filterDesc" class="form-control" placeholder="Buscar por texto...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Desde</label>
                        <input type="date" id="filterFrom" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Hasta</label>
                        <input type="date" id="filterTo" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Estado</label>
                        <select id="filterEstado" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Aprobada">Aprobada</option>
                            <option value="Rechazada">Rechazada</option>
                            <option value="Anulada">Anulada</option>
                            <option value="Finalizada">Finalizada</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted mb-0">Mostrar:</label>
                        <select id="limiteSelect" class="form-select form-select-sm w-auto">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                        </select>
                        <div class="form-check form-switch ms-3" style="display: none;">
                            <input class="form-check-input" type="checkbox" id="buscarRango">
                            <label class="form-check-label small text-muted" for="buscarRango">Filtrar por fecha</label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary px-4" id="btnLimpiarFiltros">
                            <i class="fas fa-undo me-2"></i>Limpiar
                        </button>
                        <button class="btn btn-primary px-4" id="btnBuscarFiltros">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botón Exportar a Excel -->
        <?php if ($puedeExportarExcel) { ?>
            <div class="mb-3">
                <button id="exportarExcel" class="btn btn-success shadow-sm">
                    <i class="fas fa-file-excel me-2"></i>Exportar a Excel
                </button>
            </div>
        <?php } ?>

        <div class="table-card bg-white border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>FECHA SERVICIO</th>
                            <th>CLIENTE / RUT</th>
                            <th>DESCRIPCIÓN</th>
                            <th>ORIGEN / DESTINO</th>
                            <th>SOLICITANTE</th>
                            <th>$ FINAL</th>
                            <th>ESTADO</th>
                            <th class="text-center">PDF</th>
                            <th class="pe-4 text-end">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="cotizaciones-tbody" class="small">
                        <!-- AJAX content -->
                    </tbody>
                </table>
            </div>
        </div>


    </div>

    <div id="modales-dinamicos-container"></div>

    <!-- Modal Crear Cotización -->
    <?php if ($puedeCrearCotizacion && !$soloLectura && !$bloquearFueraDeOT): ?>
        <div class="modal fade" id="modalCrearCotizacion" tabindex="-1" aria-labelledby="modalCrearCotizacionLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCrearCotizacionLabel">Crear Cotización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <?php include("crear-cotizacion-form.php"); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mostrar_modal): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('modalCrearCotizacion');
                if (el) {
                    var modal = new bootstrap.Modal(el);
                    modal.show();
                }
            });
        </script>
    <?php endif; ?>

    <!-- Modal Enviar a OT (DINÁMICO) -->
    <div class="modal fade" id="modalEnviarot" tabindex="-1" aria-labelledby="modalEnviarotLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <?php include("enviar-ot-form.php"); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Enviar Correo -->
    <div class="modal fade" id="modalEnviarCorreo" tabindex="-1" aria-labelledby="modalEnviarCorreoLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="mandar_correo.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEnviarCorreoLabel">Enviar Cotización</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Desea enviar la cotización al correo <strong id="correoDestino"></strong>?</p>
                        <input type="hidden" name="id_cotizacion" id="inputIdCotizacion" value="">
                        <input type="hidden" name="email_CLIENTE" id="inputEmailCliente" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Sí, enviar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, cancelar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Exportar Excel -->
    <?php if ($puedeExportarExcel) { ?>
        <div class="modal fade" id="excelModal" tabindex="-1" aria-labelledby="excelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="excelModalLabel">Exportar a Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tipo de filtro</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="excelFiltro" id="excelFilterDay" checked>
                                <label class="form-check-label" for="excelFilterDay">Por día</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="excelFiltro" id="excelFilterMonth">
                                <label class="form-check-label" for="excelFilterMonth">Por mes y año</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="excelFiltro" id="excelFilterRange">
                                <label class="form-check-label" for="excelFilterRange">Por rango de fechas</label>
                            </div>
                        </div>
                        <div id="excelDaySection" class="mb-3">
                            <label for="excelDate" class="form-label">Selecciona fecha</label>
                            <input type="date" id="excelDate" class="form-control">
                        </div>
                        <div id="excelMonthSection" class="mb-3" style="display:none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="excelMonth" class="form-label">Mes</label>
                                    <select id="excelMonth" class="form-select">
                                        <option value="1">Enero</option>
                                        <option value="2">Febrero</option>
                                        <option value="3">Marzo</option>
                                        <option value="4">Abril</option>
                                        <option value="5">Mayo</option>
                                        <option value="6">Junio</option>
                                        <option value="7">Julio</option>
                                        <option value="8">Agosto</option>
                                        <option value="9">Septiembre</option>
                                        <option value="10">Octubre</option>
                                        <option value="11">Noviembre</option>
                                        <option value="12">Diciembre</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="excelYear" class="form-label">Año</label>
                                    <input type="number" id="excelYear" class="form-control" min="2000" max="2100"
                                        placeholder="2025">
                                </div>
                            </div>
                        </div>
                        <div id="excelRangeSection" class="mb-3" style="display:none;">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="excelStartDate" class="form-label">Desde</label>
                                    <input type="date" id="excelStartDate" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label for="excelEndDate" class="form-label">Hasta</label>
                                    <input type="date" id="excelEndDate" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="excelExportBtn" class="btn btn-success" disabled>Exportar</button>
                        <small>
                            <font color="red">Se está actualizando la versión del host, por esa razón está deshabilitado
                            </font>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <!-- jQuery (Debe ir primero) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle (Incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="js/crear-cotizacion.js"></script>

    <script>
        const accesoAdministrador = <?= $accesoAdministrador ? 'true' : 'false' ?>;
        const esCentral = <?= $esCentral ? 'true' : 'false' ?>;
        const puedeCrearCotizacion = <?= $puedeCrearCotizacion ? 'true' : 'false' ?>;
        const puedeCrearOT = <?= $puedeCrearOT ? 'true' : 'false' ?>;
        const soloLectura = <?= $soloLectura ? 'true' : 'false' ?>;
        const bloquearFueraDeOT = <?= $bloquearFueraDeOT ? 'true' : 'false' ?>;
        let currentPagina = 1;

        // Helper robusto para obtener JSON
        async function fetchJSON(url, options) {
            try {
                const res = await fetch(url, options);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return await res.json();
            } catch (e) {
                console.error("Error Fetch:", e);
                throw e;
            }
        }

        function getStatusBadge(estado) {
            const s = (estado || '').toLowerCase();
            if (s === 'pendiente') return 'bg-warning text-dark';
            if (s === 'aprobada') return 'bg-success text-white';
            if (s === 'rechazada') return 'bg-danger text-white';
            if (s === 'anulada') return 'bg-secondary text-white';
            if (s === 'finalizada') return 'bg-info text-white';
            return 'bg-light text-muted';
        }

        async function cargarCotizaciones() {
            const tbody = document.getElementById('cotizaciones-tbody');
            tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 fw-bold">Cargando...</div></td></tr>`;

            try {
                const params = new URLSearchParams({
                    id: document.getElementById('filterId').value,
                    desc: document.getElementById('filterDesc').value,
                    estado: document.getElementById('filterEstado').value,
                    from: document.getElementById('filterFrom').value,
                    to: document.getElementById('filterTo').value,
                    rango: document.getElementById('buscarRango').checked ? 1 : 0,
                    limite: document.getElementById('limiteSelect').value,
                    pagina: currentPagina
                });

                const data = await fetchJSON(`ajax_cotizaciones.php?${params.toString()}`);

                if (!data.cotizaciones || data.cotizaciones.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-muted"><i class="fas fa-search me-2"></i>No se encontraron registros</td></tr>`;
                    return;
                }

                let html = '';
                data.cotizaciones.forEach(row => {
                    const fecha = row.Fecha_servicio ? new Date(row.Fecha_servicio).toLocaleString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
                    const mFinal = Number(row.monto_final || 0);

                    html += `
                <tr>
                    <td class="ps-4 fw-bold text-primary">#${row.id}</td>
                    <td>${fecha}</td>
                    <td>
                        <div class="fw-bold">${row.nombre_cliente || 'N/A'}</div>
                        <div class="text-muted small">${row.rut_CLIENTE || '-'}</div>
                    </td>
                    <td class="text-truncate" style="max-width: 250px;" title="${row.descripcion || ''}">${row.descripcion || '-'}</td>
                    <td>
                        <div class="small fw-semibold text-muted">${row.comuna_origen || '-'}</div>
                        <i class="fas fa-arrow-down text-light-subtle my-1 d-block ms-2" style="font-size: 0.7rem;"></i>
                        <div class="small fw-semibold text-primary">${row.comuna_destino || '-'}</div>
                    </td>
                    <td class="small">${row.nombre_solicitante || '-'}</td>
                    <td class="fw-bold text-dark">$${mFinal.toLocaleString('es-CL')}</td>
                    <td><span class="badge status-badge ${getStatusBadge(row.estado)}">${row.estado}</span></td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="ver_pdf_cotizacion.php?id=${row.id}" target="_blank" class="btn btn-sm btn-outline-danger border-0" title="PDF"><i class="fas fa-file-pdf fa-lg"></i></a>
                            <button class="btn btn-sm btn-outline-primary border-0 btn-action-email" data-id="${row.id}" data-email="${row.email_CLIENTE}" title="Enviar Correo"><i class="fas fa-envelope fa-lg"></i></button>
                        </div>
                    </td>
                    <td class="pe-4 text-end">
                        <div class="d-flex gap-2 justify-content-end">
                            <a class="btn btn-sm btn-outline-info btn-action-view rounded-circle" href="#" data-id="${row.id}" title="Ver detalles"><i class="fas fa-eye"></i></a>
                            ${puedeCrearCotizacion && !soloLectura && !bloquearFueraDeOT ? `<a class="btn btn-sm btn-outline-warning btn-action-edit rounded-circle" href="#" data-id="${row.id}" title="Modificar monto"><i class="fas fa-edit"></i></a>` : ''}
                            ${accesoAdministrador && !soloLectura && !bloquearFueraDeOT ? `<a class="btn btn-sm btn-outline-danger btn-action-delete rounded-circle" href="#" data-id="${row.id}" title="Eliminar"><i class="fas fa-trash-alt"></i></a>` : ''}
                            ${puedeCrearOT && !soloLectura && !bloquearFueraDeOT && row.estado.toLowerCase() === 'aprobada' ? `<a class="btn btn-sm btn-outline-primary btn-enviar-ot rounded-circle" href="#" data-id="${row.id}" title="Enviar a OT"><i class="fas fa-paper-plane"></i></a>` : ''}
                        </div>
                    </td>
                </tr>`;
                });
                tbody.innerHTML = html;
            } catch (error) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-5 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ${error.message}</td></tr>`;
            }
        }

        // Manejo de eventos para filtros
        document.getElementById('btnBuscarFiltros').addEventListener('click', () => {
            currentPagina = 1;
            cargarCotizaciones();
        });

        document.getElementById('btnLimpiarFiltros').addEventListener('click', () => {
            document.getElementById('filterId').value = '';
            document.getElementById('filterDesc').value = '';
            document.getElementById('filterEstado').value = '';
            document.getElementById('filterFrom').value = '';
            document.getElementById('filterTo').value = '';
            document.getElementById('buscarRango').checked = false;
            currentPagina = 1;
            cargarCotizaciones();
        });

        document.getElementById('limiteSelect').addEventListener('change', () => {
            currentPagina = 1;
            cargarCotizaciones();
        });

        // Delegación de eventos para acciones (Tabla + Modales dinámicos)
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('a, button');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const email = btn.getAttribute('data-email');

            // Si el botón no tiene clases relevantes, ignorar para no capturar todo click
            if (!btn.matches('.btn-action-email, .btn-action-view, .btn-action-edit, .btn-action-delete, .btn-enviar-ot')) {
                return;
            }

            // Acción: Email
            if (btn.classList.contains('btn-action-email')) {
                document.getElementById('correoDestino').textContent = email;
                document.getElementById('inputIdCotizacion').value = id;
                document.getElementById('inputEmailCliente').value = email;
                new bootstrap.Modal(document.getElementById('modalEnviarCorreo')).show();
                return;
            }

            // Acción: Ver (Dinámico)
            if (btn.classList.contains('btn-action-view')) {
                e.preventDefault();
                try {
                    const res = await fetch(`ver_cotizacion_detalle.php?id=${id}`);
                    const html = await res.text();
                    let container = document.getElementById('modales-dinamicos-container');
                    container.innerHTML = html; // Inyecta el modal
                    const modalEl = container.querySelector('.modal');
                    if (modalEl) {
                        new bootstrap.Modal(modalEl).show();
                    }
                } catch (err) { console.error(err); }
                return;
            }

            // Acción: Editar (Dinámico)
            if (btn.classList.contains('btn-action-edit')) {
                e.preventDefault();
                try {
                    const res = await fetch(`modificar_cotizacion_form.php?id=${id}`);
                    const html = await res.text();
                    let container = document.getElementById('modales-dinamicos-container');
                    container.innerHTML = html;
                    const modalEl = container.querySelector('.modal');
                    if (modalEl) {
                        new bootstrap.Modal(modalEl).show();
                    }
                } catch (err) { console.error(err); }
                return;
            }

            // Acción: Enviar a OT (Desde tabla o modal dinámico)
            if (btn.classList.contains('btn-enviar-ot')) {
                e.preventDefault();

                // Setear ID en el input oculto del formulario
                const inputId = document.getElementById('id_cotizacion_ot');
                if (inputId) inputId.value = id;

                // Cerrar modal actual si existe (el de Ver Cotización)
                const modalVer = btn.closest('.modal');
                if (modalVer) {
                    const modalInstance = bootstrap.Modal.getInstance(modalVer);
                    if (modalInstance) modalInstance.hide();
                }

                // Abrir modal de enviar OT
                const modalEnviar = new bootstrap.Modal(document.getElementById('modalEnviarot'));
                modalEnviar.show();
                return;
            }

            // Acción: Eliminar
            if (btn.classList.contains('btn-action-delete')) {
                e.preventDefault();
                if (confirm('¿Está seguro de que desea eliminar la cotización #' + id + '?')) {
                    const res = await fetch('eliminar_cotizacion.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}`
                    });
                    if (res.ok) cargarCotizaciones();
                    else alert('No se pudo eliminar la cotización.');
                }
                return;
            }

            // Acción: Enviar a OT (Cierra modal actual si existe)
            if (btn.classList.contains('btn-enviar-ot')) {
                e.preventDefault();
                // Cerrar modal de "Ver" si está abierto
                const currentModal = btn.closest('.modal');
                if (currentModal) bootstrap.Modal.getInstance(currentModal)?.hide();

                const modalOT = document.getElementById('modalEnviarot');
                const inputOT = modalOT.querySelector('#id_cotizacion_ot');
                if (inputOT) inputOT.value = id;

                new bootstrap.Modal(modalOT).show();
                return;
            }
        });

        // Inicialización
        document.addEventListener('DOMContentLoaded', () => {
            cargarCotizaciones();
        });


        // Exportar Excel con filtro
        <?php if ($puedeExportarExcel) { ?>
            var excelModal = new bootstrap.Modal(document.getElementById('excelModal'));
            $('#exportarExcel').click(function () {
                // Prefijar año actual si está vacío
                var yInput = $('#excelYear');
                if (!yInput.val()) {
                    yInput.val(new Date().getFullYear());
                }
                excelModal.show();
            });

            function updateExcelFilterUI() {
                var isDay = $('#excelFilterDay').is(':checked');
                var isMonth = $('#excelFilterMonth').is(':checked');
                var isRange = $('#excelFilterRange').is(':checked');
                $('#excelDaySection').toggle(isDay);
                $('#excelMonthSection').toggle(isMonth);
                $('#excelRangeSection').toggle(isRange);
            }
            $('#excelFilterDay, #excelFilterMonth, #excelFilterRange').on('change', updateExcelFilterUI);

            $('#excelExportBtn').click(function () {
                if ($('#excelFilterDay').is(':checked')) {
                    var date = $('#excelDate').val();
                    if (!date) {
                        alert('Seleccione una fecha.');
                        return;
                    }
                    window.location.href = 'exportar-cotizaciones-excel.php?filter=day&date=' + encodeURIComponent(date);
                    excelModal.hide();
                } else if ($('#excelFilterMonth').is(':checked')) {
                    var month = $('#excelMonth').val();
                    var year = $('#excelYear').val();
                    if (!month || !year) {
                        alert('Seleccione mes y año.');
                        return;
                    }
                    window.location.href = 'exportar-cotizaciones-excel.php?filter=month&month=' + encodeURIComponent(month) + '&year=' + encodeURIComponent(year);
                    excelModal.hide();
                } else if ($('#excelFilterRange').is(':checked')) {
                    var start = $('#excelStartDate').val();
                    var end = $('#excelEndDate').val();
                    if (!start || !end) {
                        alert('Seleccione ambas fechas: Desde y Hasta.');
                        return;
                    }
                    if (start > end) {
                        alert('La fecha "Desde" no puede ser mayor que "Hasta".');
                        return;
                    }
                    window.location.href = 'exportar-cotizaciones-excel.php?filter=range&start=' + encodeURIComponent(start) + '&end=' + encodeURIComponent(end);
                    excelModal.hide();
                }
            });
        <?php } ?>

    </script>


</body>

</html>

<?php $conn->close(); ?>