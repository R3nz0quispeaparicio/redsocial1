<?php
session_start();
include 'lib/config.php';
include 'lib/socialnetwork-lib.php';

ini_set('error_reporting', 0);

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['id']) && isset($_GET['action'])) {
        $id = $_GET['id'];
        $action = $_GET['action'];

        if ($action == 'aceptar') {
            $stmt = $pdo->prepare("UPDATE amigos SET estado = '1' WHERE id_ami = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            header('Location: ' . getenv('HTTP_REFERER'));
            exit;
        }

        if ($action == 'rechazar') {
            $stmt = $pdo->prepare("DELETE FROM amigos WHERE id_ami = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            header('Location: ' . getenv('HTTP_REFERER'));
            exit;
        }
    }
} catch (PDOException $e) {
    echo "Error al conectar a la base de datos: " . $e->getMessage();
    exit;
}
