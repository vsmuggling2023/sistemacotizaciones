<?php

$servername = "mysql-vsmuggling.alwaysdata.net";
$username = "vsmuggling";
$password = "181730366u";
$dbname = "vsmuggling_nera";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

?>