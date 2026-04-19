<?php
include("database.php");

$id_cotizacion = 15161;

$sql = "SELECT id, id_cotizacion FROM ordenes_trabajo WHERE id_cotizacion = $id_cotizacion";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "La cotización $id_cotizacion tiene la orden de trabajo con ID: " . $row['id'];
} else {
    echo "La cotización $id_cotizacion NO tiene orden de trabajo asociada";
}
?>