<?php
include("database.php");
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Debug Cotizaciones Duplicadas</h2>";

// Verificar cotización 15625
$id = 15625;
echo "<h3>Cotización ID: $id</h3>";

$sql = "SELECT 
            c.id,
            c.Fecha_servicio,
            c.rut_CLIENTE,
            c.email_CLIENTE,
            cl.NOMBRE AS nombre_cliente,
            c.Descripcion AS descripcion,
            c.monto_inicial,
            c.base_salida,
            c.estado,
            c.coordinador_id,
            u.NOMBRE_USUARIO AS coordinador,
            c.id_comuna_origen,
            co.nombre AS comuna_origen,
            c.id_comuna_destino,
            cd.nombre AS comuna_destino,
            c.monto_final,
            c.nombre_solicitante
        FROM cotizaciones c
        LEFT JOIN clientes cl ON c.rut_CLIENTE = cl.RUT
        LEFT JOIN usuarios u ON c.coordinador_id = u.id
        LEFT JOIN comunas co ON c.id_comuna_origen = co.id
        LEFT JOIN comunas cd ON c.id_comuna_destino = cd.id
        WHERE c.id = $id";

$resultado = $conn->query($sql);
echo "<p>Número de filas retornadas: " . $resultado->num_rows . "</p>";

if ($resultado->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    $first = true;
    while ($row = $resultado->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Verificar cotización 15627
$id = 15627;
echo "<h3>Cotización ID: $id</h3>";

$resultado2 = $conn->query(str_replace("15625", "15627", $sql));
echo "<p>Número de filas retornadas: " . $resultado2->num_rows . "</p>";

if ($resultado2->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>";
    $first = true;
    while ($row = $resultado2->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach (array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>