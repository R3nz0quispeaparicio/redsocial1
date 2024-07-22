<?php
session_start();
require 'lib/config.php';
require 'lib/socialnetwork-lib.php';

if (!isset($_SESSION['usuario'])) {
    die('No autorizado');
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Error de conexión: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = filter_input(INPUT_POST, 'from', FILTER_SANITIZE_NUMBER_INT);
    $to = filter_input(INPUT_POST, 'to', FILTER_SANITIZE_NUMBER_INT);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if ($from && $to && $message) {
        $stmt = $pdo->prepare("INSERT INTO chats (de, para, mensaje, fecha, leido) VALUES (?, ?, ?, NOW(), 0)");
        if ($stmt->execute([$from, $to, $message])) {
            echo json_encode(['success' => true, 'message' => 'Mensaje guardado']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar el mensaje']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}