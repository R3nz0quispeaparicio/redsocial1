<?php
// Obtener el ID de usuario de manera segura
$id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '';

// Conexión a la base de datos usando PDO
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: No se pudo conectar. " . $e->getMessage());
}

// Consulta para obtener los álbumes del usuario
try {
    $stmt = $pdo->prepare("SELECT * FROM albumes WHERE usuario = :id ORDER BY id_alb ASC");
    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $albumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al ejecutar la consulta: " . $e->getMessage());
}

?>

<center>
    <?php foreach ($albumes as $album) : ?>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT ruta FROM fotos WHERE album = :album_id ORDER BY id_fot DESC LIMIT 1");
            $stmt->bindParam(':album_id', $album['id_alb'], PDO::PARAM_INT);
            $stmt->execute();
            $foto = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al ejecutar la consulta: " . $e->getMessage());
        }
        ?>
        <a href="?id=<?php echo htmlspecialchars($id); ?>&album=<?php echo htmlspecialchars($album['id_alb']); ?>&perfil=albumes">
            <img src="publicaciones/<?php echo htmlspecialchars($foto['ruta']); ?>" width="19%">
        </a>
        <br>
        <?php echo htmlspecialchars($album['nombre']); ?>
    <?php endforeach; ?>
</center>

<?php
$pdo = null;
?>
