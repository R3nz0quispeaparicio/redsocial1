<?php
session_start();
require 'config.php';

if (!isset($_SESSION['id'])) {
    exit('No autorizado');
}

$pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);

// Marcar mensajes como leídos
$stmt = $pdo->prepare("UPDATE chats SET leido = 1 WHERE para = :userId AND leido = 0");
$stmt->bindParam(':userId', $_SESSION['id']);
$stmt->execute();

// Obtener el nuevo conteo de mensajes no leídos
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM chats WHERE para = :userId AND leido = 0");
$stmt->bindParam(':userId', $_SESSION['id']);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$unreadMessages = $result['count'];

echo json_encode(['unreadCount' => $unreadMessages]);