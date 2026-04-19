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
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

// Total para paginación
$sql_total = "SELECT COUNT(*) AS total FROM cotizaciones";
$total_resultado = $conn->query($sql_total);
$total_filas = $total_resultado->fetch_assoc()['total'];
$total_paginas = ceil($total_filas / $limite);

// Consulta principal
$sql = "SELECT 
            c.id,
            c.Fecha_servicio,
            c.rut_CLIENTE,
            c.email_CLIENTE,
            cl.nombre AS nombre_cliente,
            c.servicio,
            c.monto_inicial,
            c.base_salida,
            c.estado,
            u.nombre_usuario AS coordinador,
            co.nombre AS comuna_origen,
            cd.nombre AS comuna_destino,
            c.monto_final,
            c.nombre_solicitante
        FROM cotizaciones c
        LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.rut
        LEFT JOIN usuarios u ON c.coordinador_id = u.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        ORDER BY c.id DESC
        LIMIT $limite OFFSET $offset";

$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Listado de Cotizaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="css/cotizaciones.css" rel="stylesheet" />
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light px-3">
    <div class="container-fluid">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item"><a class="nav-link" href="ot.php">Órdenes de Trabajo</a></li>
            <li class="nav-item"><a class="nav-link active bg-secondary text-white" href="cotizaciones.php">Cotizaciones</a></li>
            <?php if ($opcionesAdicionales2) echo $opcionesAdicionales2; ?>
        </ul>
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-dark" href="#" id="navbarUsuario" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= htmlspecialchars($_SESSION['NOMBRE_USUARIO']) ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarUsuario">
                    <?php if ($opcionesAdicionales) echo $opcionesAdicionales; ?>
                    <li><a class="dropdown-item" href="clientes.php">Clientes</a></li>
                </ul>
            </li>
            <li class="nav-item ms-3">
                <a class="btn btn-sm btn-outline-danger" href="cerrar-sesion.php">
                    <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                </a>
            </li>
        </ul>
    </div>
</nav>

<div class="container py-5">
    <h2 class="mb-4">Listado de Cotizaciones</h2>

    <!-- Botón para abrir el modal -->
    <div class="container mb-3">
        <button type="button" class="btn btn-primary" onclick="window.location.href='?abrir_modal=1'" data-bs-toggle="modal" data-bs-target="#modalCrearCotizacion">
            Crear Cotización
        </button>
    </div>

    <!-- Filtro de cantidad -->
    <form method="get" class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <label for="limite" class="form-label mb-0 me-2">Mostrar:</label>
            <select name="limite" id="limite" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                <?php foreach ([10, 25, 50, 75, 100] as $opcion): ?>
                    <option value="<?= $opcion ?>" <?= $limite == $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
                <?php endforeach; ?>
            </select> cotizaciones
        </div>
    </form>

    <div class="table-fixed-container mt-3">
    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Rut</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Comuna Origen</th>
                    <th>Comuna Destino</th>
                    <th>Solicitante</th>
                    <th>Pago Inicial</th>
                    <th>Pago Final</th>
                    <th>Base Salida</th>
                    <th>Estado</th>
                    <th>PDF</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= date("d/m/Y", strtotime($row['Fecha_servicio'])) ?></td>
                <td><?= htmlspecialchars($row['rut_CLIENTE']) ?></td>
                <td><?= htmlspecialchars($row['nombre_cliente']) ?></td>
                <td><?= htmlspecialchars($row['servicio']) ?></td>
                <td><?= htmlspecialchars($row['comuna_origen'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['comuna_destino'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['nombre_solicitante'] ?? '-') ?></td>
                <?php
                // echo "<td>" . htmlspecialchars($row['coordinador'] ?? 'No asignado') . "</td>";
                ?>
                <td>$<?= number_format($row['monto_inicial'], 0, '', '.') ?></td>
                <td>$<?= number_format($row['monto_final'], 0, '', '.') ?></td>
                <td><?= htmlspecialchars($row['base_salida']) ?></td>
                <td><?= htmlspecialchars($row['estado']) ?></td>
                <td>
                    <div class='d-flex align-items-center gap-2'>
                        <a href="ver_pdf_cotizacion.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i></a>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEnviarCorreo" data-id="<?= $row['id'] ?>" data-email="<?= htmlspecialchars($row['email_CLIENTE']) ?>"><i class="fas fa-envelope"></i></button>
                    </div>
                </td>
                <td>
                    <div class='d-flex align-items-center gap-2'>
                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalVer<?= $row['id'] ?>"><i class="fas fa-eye"></i></button>
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $row['id'] ?>"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar<?= $row['id'] ?>"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </td>
            </tr>

            <!-- Modal Ver Detalles -->
            <div class="modal fade" id="modalVer<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalVerLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="modalVerLabel<?= $row['id'] ?>">Detalle de Cotización ID <?= $row['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Cliente:</strong> <?= htmlspecialchars($row['nombre_cliente']) ?></p>
                            <p><strong>Servicio:</strong> <?= htmlspecialchars($row['servicio']) ?></p>
                            <p><strong>Fecha:</strong> <?= date("d/m/Y", strtotime($row['Fecha_servicio'])) ?></p>
                            <p><strong>Comuna Origen:</strong> <?= htmlspecialchars($row['comuna_origen']) ?></p>
                            <p><strong>Comuna Destino:</strong> <?= htmlspecialchars($row['comuna_destino']) ?></p>
                            <p><strong>Pago:</strong> $<?= number_format($row['monto_final'], 0, '', '.') ?></p>
                            <p><strong>Base Salida:</strong> <?= htmlspecialchars($row['base_salida']) ?></p>
                            <p><strong>Estado:</strong> <?= htmlspecialchars($row['estado']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Editar -->
            <div class="modal fade" id="modalEditar<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalEditarLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title" id="modalEditarLabel<?= $row['id'] ?>">Modificar Cotización ID <?= $row['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="modificar_cotizacion.php">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <!-- Aquí agregar campos editables, ejemplo: -->
                            <div class="mb-3">
                                <label class="form-label">Monto:</label>
                                <input type="number" class="form-control" name="monto_final" value="<?= $row['monto_final'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Estado:</label>
                                <input type="text" class="form-control" name="estado" value="<?= htmlspecialchars($row['estado']) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Eliminar -->
            <div class="modal fade" id="modalEliminar<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalEliminarLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="modalEliminarLabel<?= $row['id'] ?>">Eliminar Cotización ID <?= $row['id'] ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            ¿Estás seguro de que deseas eliminar esta cotización?
                        </div>
                        <div class="modal-footer">
                            <form method="post" action="eliminar_cotizacion.php">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Paginación completa -->
        <form method="get" class="d-flex justify-content-center align-items-center gap-2 mt-4 flex-wrap">
            <input type="hidden" name="limite" value="<?= $limite ?>">

            <ul class="pagination m-0">
                <!-- Botón Primero -->
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=1&limite=<?= $limite ?>" aria-label="Primero">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>

                <!-- Botón Anterior -->
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= max(1, $pagina - 1) ?>&limite=<?= $limite ?>" aria-label="Anterior">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                </li>

                <!-- Input para ir a una página específica -->
                <li class="page-item">
                    <input type="number" name="pagina" value="<?= $pagina ?>" min="1" max="<?= $total_paginas ?>" class="form-control" style="width: 80px;" />
                </li>

                <!-- Botón Siguiente -->
                <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= min($total_paginas, $pagina + 1) ?>&limite=<?= $limite ?>" aria-label="Siguiente">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                </li>

                <!-- Botón Último -->
                <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $total_paginas ?>&limite=<?= $limite ?>" aria-label="Último">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>

            <button type="submit" class="btn btn-primary ms-2">Ir</button>
        </form>


    <?php else: ?>
        <div class="alert alert-warning">No hay cotizaciones registradas.</div>
    <?php endif; ?>
    </div>
</div>

<!-- Modal corregido -->
    <div class="modal fade <?= $mostrar_modal ? 'show' : '' ?>" style="<?= $mostrar_modal ? 'display: block; background: rgba(0,0,0,0.5);' : '' ?>" id="modalCrearCotizacion">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearCotizacionLabel">Crear Cotización</h5>
                    <button type="button" class="btn-close" onclick="window.location.href='?'" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <?php include("crear-cotizacion-form-ajax.php"); ?>
                </div>
            </div>
        </div>
    </div>

<!-- Modal para enviar correo -->
<div class="modal fade" id="modalEnviarCorreo" tabindex="-1" aria-labelledby="modalEnviarCorreoLabel" aria-hidden="true">
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

<?php if (isset($_SESSION['correo_estado'])): ?>
<div class="modal fade show" id="modalCorreoEstado" tabindex="-1" style="display:block; background:rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Resultado del envío de correo</h5>
        <button type="button" class="btn-close" onclick="window.location.href='cotizaciones.php'"></button>
      </div>
      <div class="modal-body">
        <?= htmlspecialchars($_SESSION['correo_estado']) ?>
      </div>
    </div>
  </div>
</div>
<?php unset($_SESSION['correo_estado']); ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/crear-cotizacion.js"></script>
<script>
    var modalEnviarCorreo = document.getElementById('modalEnviarCorreo');
    modalEnviarCorreo.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var idCotizacion = button.getAttribute('data-id');
        var emailCliente = button.getAttribute('data-email');

        modalEnviarCorreo.querySelector('#inputIdCotizacion').value = idCotizacion;
        modalEnviarCorreo.querySelector('#inputEmailCliente').value = emailCliente;
        modalEnviarCorreo.querySelector('#correoDestino').textContent = emailCliente;
    });
</script>

</body>
</html>
<?php $conn->close(); ?>
