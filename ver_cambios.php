<?php
// =========================
// CONFIGURACIÓN DE LA BD
// =========================
$host = "mysql-smuggling.alwaysdata.net";
$dbname = "smuggling_smuggling";
$user = "smuggling";
$pass = "181730366u"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}

// =========================
// CONSULTA
// =========================
$query = $pdo->query("SELECT * FROM eventos_nera");
$eventos = $query->fetchAll(PDO::FETCH_ASSOC);

$totalEventos = count($eventos);
$listos = 0;
foreach ($eventos as $ev) {
    $estado = strtolower(trim($ev['estado'] ?? ''));
    if ($estado === 'listo') { $listos++; }
}
$porcentaje = $totalEventos > 0 ? round(($listos / $totalEventos) * 100) : 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambios Nera</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (CORRECTO) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    </style>

</head>
<body class="bg-light">

<div class="container mt-5">
    <h2 class="mb-4 text-center">Listado de Eventos NERA</h2>

    <div class="card shadow">
        <div class="card-body">
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span>Progreso: Listo</span>
                    <span class="fw-semibold"><?= $listos ?> / <?= $totalEventos ?> (<?= $porcentaje ?>%)</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $porcentaje ?>%;" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>

            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Observación</th>
                        <th>Estado</th>
                        <th>Fecha Finalización</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($eventos) > 0): ?>
                        <?php foreach ($eventos as $ev): ?>

                            <tr>
                                <td><?= $ev['id'] ?></td>
                                <td><?= htmlspecialchars($ev['nombre']) ?></td>
                                <td><?= nl2br(htmlspecialchars($ev['descripcion'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($ev['observacion'] ?? '')) ?></td>

                                <!-- ESTADO CON ÍCONOS -->
                                <td>
                                    <?php
                                        $estado = strtolower(trim($ev['estado'] ?? ''));
                                        $badge = 'secondary';
                                        $badgeTextClass = 'text-white';
                                        $icon = "<i class='bi bi-question-circle text-white'></i>";
                                        $spinner = '';

                                        switch ($estado) {
                                            case 'en cola':
                                                $badge = 'warning';
                                                $badgeTextClass = 'text-dark';
                                                $icon = "<i class='bi bi-hourglass-split text-dark'></i>";
                                                break;
                                            case 'en progreso':
                                                $badge = 'info';
                                                $badgeTextClass = 'text-white';
                                                $spinner = "<span class='spinner-border spinner-border-sm text-light me-1' role='status' aria-hidden='true'></span>";
                                                $icon = "<i class='bi bi-gear-fill text-white'></i>";
                                                break;
                                            case 'pendiente':
                                                $badge = 'secondary';
                                                $badgeTextClass = 'text-white';
                                                $icon = "<i class='bi bi-clock text-white'></i>";
                                                break;
                                            case 'listo':
                                                $badge = 'success';
                                                $badgeTextClass = 'text-white';
                                                $icon = "<i class='bi bi-check-circle-fill text-white'></i>";
                                                break;
                                        }
                                    ?>

                                    <span class="badge <?= $badgeTextClass ?> bg-<?= $badge ?> d-inline-flex align-items-center gap-2">
                                        <?= $spinner ?> <?= $icon ?> <?= htmlspecialchars($ev['estado']) ?>
                                    </span>
                                </td>
                                <td><?php
                                    $f = $ev['fecha_termino_evento'] ?? '';
                                    echo htmlspecialchars($f ? date('d-m-Y', strtotime($f)) : 'Proximamente');
                                ?></td>
                            </tr>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No hay eventos registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

</body>
</html>
