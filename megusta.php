<?php
session_start();
require_once 'lib/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

$post_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
$usuario_id = $_SESSION['id'];

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM likes WHERE post = ? AND usuario = ?");
    $stmt->execute([$post_id, $usuario_id]);
    $existing_like = $stmt->fetch();

    if (!$existing_like) {
        $stmt = $pdo->prepare("INSERT INTO likes (usuario, post, fecha) VALUES (?, ?, NOW())");
        $stmt->execute([$usuario_id, $post_id]);
        $action = 'liked';
        $likes_change = 1;
    } else {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post = ? AND usuario = ?");
        $stmt->execute([$post_id, $usuario_id]);
        $action = 'unliked';
        $likes_change = -1;
    }

    $stmt = $pdo->prepare("UPDATE publicaciones SET likes = likes + ? WHERE id_pub = ?");
    $stmt->execute([$likes_change, $post_id]);

    $stmt = $pdo->prepare("SELECT likes FROM publicaciones WHERE id_pub = ?");
    $stmt->execute([$post_id]);
    $likes = $stmt->fetchColumn();

    $pdo->commit();

    echo json_encode(['success' => true, 'likes' => $likes, 'action' => $action]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}