<?php
include("database.php");

if (!isset($_GET['id'])) {
    echo "<p>ID de cotización no proporcionado.</p>";
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM cotizaciones WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()):
?>
<p><strong>Cliente:</strong> <?= htmlspecialchars($row['nombre_cliente']) ?></p>
<p><strong>Servicio:</strong> <?= htmlspecialchars($row['servicio']) ?></p>
<p><strong>Fecha Servicio:</strong> <?= date("d/m/Y", strtotime($row['Fecha_servicio'])) ?></p>
<p><strong>Comuna Origen:</strong> <?= htmlspecialchars($row['comuna_origen']) ?></p>
<p><strong>Comuna Destino:</strong> <?= htmlspecialchars($row['comuna_destino']) ?></p>
<p><strong>Pago:</strong> $<?= number_format($row['monto_final'], 0, '', '.') ?></p>
<p><strong>Base Salida:</strong> <?= htmlspecialchars($row['base_salida']) ?></p>
<p><strong>Estado:</strong> <?= htmlspecialchars($row['estado']) ?></p>
<?php
else:
    echo "<p>Cotización no encontrada.</p>";
endif;

$stmt->close();
$conn->close();
?>
