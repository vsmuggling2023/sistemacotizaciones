<?php
require_once("database.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SHOW COLUMNS FROM cotizaciones";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Columnas de la tabla 'cotizaciones':</h2><ul>";
    while($row = $result->fetch_assoc()) {
        echo "<li>" . $row["Field"] . " - " . $row["Type"] . "</li>";
    }
    echo "</ul>";
} else {
    echo "0 results";
}
$conn->close();
?>
