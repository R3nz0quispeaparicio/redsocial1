<?php
$db_host = "localhost";
$db_name = "redsocial";
$db_user = "root";
$db_pass = "";
$socket_io_url = "http://localhost:3000";
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "";
} catch (PDOException $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage();
}
