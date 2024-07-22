<?php
session_start();
require_once 'lib/config.php'; // Incluye el archivo de configuración con la conexión PDO

// Verifica si hay un ID de usuario en la URL y valida la página actual
$CantidadMostrar = 5;
$aid = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Sanitiza y convierte a entero
$compag = isset($_GET['pag']) ? (int)$_GET['pag'] : 1; // Sanitiza y convierte a entero
$inicio = ($compag - 1) * $CantidadMostrar;

// Consulta para obtener el total de publicaciones del usuario
$totalr = 0;
if ($aid > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM publicaciones WHERE usuario = :aid");
    $stmt->execute(['aid' => $aid]);
    $totalr = $stmt->fetchColumn();
}

$TotalRegistro = ceil($totalr / $CantidadMostrar);
$IncrimentNum = ($compag + 1) <= $TotalRegistro ? ($compag + 1) : 0;

// Consulta para obtener las publicaciones paginadas del usuario
$consultavistas = "SELECT * FROM publicaciones WHERE usuario = :aid ORDER BY id_pub DESC LIMIT :inicio, :CantidadMostrar";
$stmt = $pdo->prepare($consultavistas);
$stmt->bindParam(':aid', $aid, PDO::PARAM_INT);
$stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindParam(':CantidadMostrar', $CantidadMostrar, PDO::PARAM_INT);
$stmt->execute();
$consulta = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iteración sobre las publicaciones encontradas
foreach ($consulta as $lista) {
    $userid = $lista['usuario'];

    // Consulta para obtener el usuario relacionado con la publicación
    $usuariob = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :userid");
    $usuariob->execute(['userid' => $userid]);
    $use = $usuariob->fetch(PDO::FETCH_ASSOC);

    // Consulta para obtener la foto relacionada con la publicación (si existe)
    $fotos = $pdo->prepare("SELECT * FROM fotos WHERE publicacion = :id_pub LIMIT 1");
    $fotos->execute(['id_pub' => $lista['id_pub']]);
    $fot = $fotos->fetch(PDO::FETCH_ASSOC);
?>
  <!-- START PUBLICACIONES -->
  <div class="box box-widget">
    <div class="box-header with-border">
      <div class="user-block">
        <img class="img-circle" src="avatars/<?php echo htmlspecialchars($use['avatar']); ?>" alt="User Image">
        <span class="description" onclick="location.href='perfil.php?id=<?php echo htmlspecialchars($use['id_use']); ?>';" style="cursor:pointer; color: #3C8DBC;"><?php echo htmlspecialchars($use['usuario']); ?></span>
        <span class="description"><?php echo htmlspecialchars($lista['fecha']); ?></span>
      </div>
      <div class="box-tools">
        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
      </div>
    </div>
    <div class="box-body">
      <p><?php echo htmlspecialchars($lista['contenido']); ?></p>
      <?php if (!empty($fot['ruta'])): ?>
        <img src="publicaciones/<?php echo htmlspecialchars($fot['ruta']); ?>" width="100%">
      <?php endif; ?>
      <br><br>
      <?php 
      // Contar comentarios para la publicación actual
      $numcomen = 0;
      $stmt = $pdo->prepare("SELECT COUNT(*) AS numcomen FROM comentarios WHERE publicacion = :id_pub");
      $stmt->execute(['id_pub' => $lista['id_pub']]);
      $numcomen = $stmt->fetchColumn();
      ?>
      <ul class="list-inline">
        <?php
        // Verificar si el usuario actual dio like a la publicación actual
        $stmt = $pdo->prepare("SELECT * FROM likes WHERE post = :id_pub AND usuario = :usuario");
        $stmt->execute(['id_pub' => $lista['id_pub'], 'usuario' => $_SESSION['id']]);
        $query = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$query) {
          echo '<li><div class="btn btn-default btn-xs like" id="' . htmlspecialchars($lista['id_pub']) . '"><i class="fa fa-thumbs-o-up"></i> Me gusta </div><span id="likes_' . htmlspecialchars($lista['id_pub']) . '"> (' . htmlspecialchars($lista['likes']) . ')</span></li>';
        } else {
          echo '<li><div class="btn btn-default btn-xs like" id="' . htmlspecialchars($lista['id_pub']) . '"><i class="fa fa-thumbs-o-up"></i> No me gusta </div><span id="likes_' . htmlspecialchars($lista['id_pub']) . '"> (' . htmlspecialchars($lista['likes']) . ')</span></li>';
        }
        ?>
        <li class="pull-right">
          <span href="#" class="link-black text-sm"><i class="fa fa-comments-o margin-r-5"></i> Comentarios (<?php echo htmlspecialchars($numcomen); ?>)</span>
        </li>
      </ul>
    </div>
    <div class="box-footer box-comments">
      <?php
      // Obtener los últimos 2 comentarios para la publicación actual
      $comentarios = $pdo->prepare("SELECT * FROM comentarios WHERE publicacion = :id_pub ORDER BY id_com DESC LIMIT 2");
      $comentarios->execute(['id_pub' => $lista['id_pub']]);
      $comentarios = $comentarios->fetchAll(PDO::FETCH_ASSOC);

      foreach ($comentarios as $com) {
        $usuarioc = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :id_use");
        $usuarioc->execute(['id_use' => $com['usuario']]);
        $usec = $usuarioc->fetch(PDO::FETCH_ASSOC);
      ?>
        <div class="box-comment">
          <img class="img-circle img-sm" src="avatars/<?php echo htmlspecialchars($usec['avatar']); ?>">
          <div class="comment-text">
            <span class="username">
              <?php echo htmlspecialchars($usec['usuario']); ?>
              <span class="text-muted pull-right"><?php echo htmlspecialchars($com['fecha']); ?></span>
            </span>
            <?php echo htmlspecialchars($com['comentario']); ?>
          </div>
        </div>
      <?php } ?>
      <?php if ($numcomen > 2): ?>
        <br>
        <center><span onclick="location.href='publicacion.php?id=<?php echo htmlspecialchars($lista['id_pub']); ?>';" style="cursor:pointer; color: #3C8DBC;">Ver todos los comentarios</span></center>
      <?php endif; ?>
      <div id="nuevocomentario<?php echo htmlspecialchars($lista['id_pub']); ?>"></div>
      <br>
      <form method="post" action="">
        <label id="record-<?php echo htmlspecialchars($lista['id_pub']); ?>">
          <input type="text" class="enviar-btn form-control input-sm" style="width: 800px;" placeholder="Escribe un comentario" name="comentario" id="comentario-<?php echo htmlspecialchars($lista['id_pub']); ?>">
          <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($_SESSION['id']); ?>" id="usuario">
          <input type="hidden" name="publicacion" value="<?php echo htmlspecialchars($lista['id_pub']); ?>" id="publicacion">
          <input type="hidden" name="avatar" value="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" id="avatar">
          <input type="hidden" name="nombre" value="<?php echo htmlspecialchars($_SESSION['usuario']); ?>" id="nombre">
        </label>
      </form>
    </div>
  </div>
  <br><br>
<?php
}

if ($IncrimentNum > 0) {
  echo "<a href=\"miactividad.php?id=$aid&pag=$IncrimentNum\">Siguiente</a>";
}
?>
<script>
  $(document).ready(function() {
    $('.scroll').jscroll({
      loadingHtml: '<img src="images/invisible.png" alt="Loading" />'
    });
  });
</script>
<!-- Fin del código de scroll -->
