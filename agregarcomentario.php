<?php
require_once 'lib/config.php';

function limpiarEntrada($data) {
    return trim(htmlspecialchars(strip_tags($data)));
}

// Obtener datos del formulario
$usuario = limpiarEntrada($_POST['usuario']);
$comentario = limpiarEntrada($_POST['comentario']);
$publicacion = limpiarEntrada($_POST['publicacion']);

try {
    // Iniciar transacción
    $pdo->beginTransaction();

    // Insertar comentario en la base de datos
    $insert_comentario = $pdo->prepare("INSERT INTO comentarios (usuario, comentario, fecha, publicacion) VALUES (:usuario, :comentario, NOW(), :publicacion)");
    $insert_comentario->execute(['usuario' => $usuario, 'comentario' => $comentario, 'publicacion' => $publicacion]);

    // Obtener información del usuario que hizo la publicación
    $query_usuario_pub = $pdo->prepare("SELECT usuario FROM publicaciones WHERE id_pub = :publicacion");
    $query_usuario_pub->execute(['publicacion' => $publicacion]);
    $usuario_pub = $query_usuario_pub->fetch(PDO::FETCH_ASSOC);

    // Insertar notificación
    if ($usuario_pub) {
        $insert_notificacion = $pdo->prepare("INSERT INTO notificaciones (user1, user2, tipo, leido, fecha, id_pub) VALUES (:usuario, :usuario_pub, 'ha comentado', '0', NOW(), :publicacion)");
        $insert_notificacion->execute(['usuario' => $usuario, 'usuario_pub' => $usuario_pub['usuario'], 'publicacion' => $publicacion]);
    }

    // Confirmar transacción
    $pdo->commit();

    // Responder con éxito y la fecha del comentario
    echo json_encode(['success' => true, 'fecha' => date('Y-m-d H:i:s')]);
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al agregar comentario: ' . $e->getMessage()]);
}
