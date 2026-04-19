<?php
session_start();

if (!isset($_SESSION['NOMBRE_USUARIO'])) {
    header("Location: index.php");
    exit();
}

$usuario = $_SESSION['NOMBRE_USUARIO'];
include("rol-cargos.php");
include("database.php");

$sql = "SELECT 
            c.id,
            c.Fecha_servicio,
            c.rut_CLIENTE,
            cl.nombre AS nombre_cliente,
            c.servicio,
            c.monto_inicial,
            c.base_salida,
            c.estado,
            u.nombre_usuario AS coordinador,
            co.nombre AS comuna_origen,
            cd.nombre AS comuna_destino
        FROM cotizaciones c
        LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.rut
        LEFT JOIN usuarios u ON c.coordinador_id = u.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        ORDER BY c.id ASC";

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
    <a href="crear-cotizacion.php" class="btn btn-primary">Crear Cotización</a><br />

    <div class="table-fixed-container mt-3">
    <?php if ($resultado && $resultado->num_rows > 0): ?>
        <br />
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Comuna Origen</th>
                    <th>Comuna Destino</th>
                    <th>Coordinador</th>
                    <th>Pago</th>
                    <th>Base Salida</th>
                    <th>Estado</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= date("d/m/Y", strtotime($row['Fecha_servicio'])) ?></td>
                    <td><?= htmlspecialchars($row['nombre_cliente']) ?></td>
                    <td><?= htmlspecialchars($row['servicio']) ?></td>
                    <td><?= htmlspecialchars($row['comuna_origen'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['comuna_destino'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['coordinador'] ?? 'No asignado') ?></td>
                    <td>$<?= number_format($row['monto_inicial'], 0, '', '.') ?></td>
                    <td><?= htmlspecialchars($row['base_salida']) ?></td>
                    <td><?= htmlspecialchars($row['estado']) ?></td>
                    <td>
                        <a href="ver_pdf_cotizacion.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-danger">
                            <i class="fas fa-file-pdf"></i> Ver PDF
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <br />
    <?php else: ?>
        <div class="alert alert-warning">No hay cotizaciones registradas.</div>
    <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
