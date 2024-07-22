<?php
require_once 'lib/config.php'; // Asegurarse de incluir el archivo de configuración de la base de datos.

function limpiarEntrada($data) {
    return trim(htmlspecialchars(strip_tags($data)));
}

$id = limpiarEntrada($_GET['id']);
$album = limpiarEntrada($_GET['album']);
?>

<center>
<?php
try {
    $query = $pdo->prepare("SELECT * FROM fotos WHERE usuario = :id AND album = :album ORDER BY id_fot DESC");
    $query->execute(['id' => $id, 'album' => $album]);

    while ($fot = $query->fetch(PDO::FETCH_ASSOC)) {
?>
        <a href="#"><img src="publicaciones/<?php echo htmlspecialchars($fot['ruta']); ?>" width="19%"> </a>
<?php
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
</center>
