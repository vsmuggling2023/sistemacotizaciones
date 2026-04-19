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
<form method="post" action="modificar_cotizacion.php">
    <input type="hidden" name="id" value="<?= $row['id'] ?>">
    <div class="mb-3">
        <label class="form-label">Monto Inicial</label>
        <input type="number" class="form-control" name="monto_inicial" value="<?= $row['monto_inicial'] ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Monto Final</label>
        <input type="number" class="form-control" name="monto_final" value="<?= $row['monto_final'] ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Estado</label>
        <select class="form-select" name="estado">
            <option value="Pendiente" <?= $row['estado']=="Pendiente" ? "selected" : "" ?>>Pendiente</option>
            <option value="En Progreso" <?= $row['estado']=="En Progreso" ? "selected" : "" ?>>En Progreso</option>
            <option value="Finalizado" <?= $row['estado']=="Finalizado" ? "selected" : "" ?>>Finalizado</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
</form>
<?php
else:
    echo "<p>Cotización no encontrada.</p>";
endif;

$stmt->close();
$conn->close();
?>
