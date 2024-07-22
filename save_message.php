<?php
session_start();
require 'lib/config.php';
require 'lib/socialnetwork-lib.php';

if (!isset($_SESSION['usuario'])) {
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Error de conexión: ' . $e->getMessage());
    die(json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = filter_input(INPUT_POST, 'from', FILTER_VALIDATE_INT);
    $to = filter_input(INPUT_POST, 'to', FILTER_VALIDATE_INT);
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_UNSAFE_RAW));
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    if ($from !== false && $to !== false && $message !== '') {
        // Verificar si el mensaje ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chats WHERE de = ? AND para = ? AND mensaje = ? AND fecha > NOW() - INTERVAL 5 SECOND");
        $stmt->execute([$from, $to, $message]);
        $count = $stmt->fetchColumn();

        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT INTO chats (de, para, mensaje, fecha, leido) VALUES (?, ?, ?, NOW(), 0)");
            if ($stmt->execute([$from, $to, $message])) {
                echo json_encode(['success' => true, 'message' => 'Mensaje guardado']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al guardar el mensaje']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Mensaje duplicado']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
}