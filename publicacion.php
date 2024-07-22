<?php
session_start();
require_once 'lib/config.php';
require_once 'lib/socialnetwork-lib.php';

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

// Redirigir si no hay sesión de usuario
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

$publicacion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($publicacion_id == 0) {
    die("ID de publicación no válido");
}

// Obtener la publicación específica
$stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id_pub = :id_pub");
$stmt->bindParam(':id_pub', $publicacion_id, PDO::PARAM_INT);
$stmt->execute();
$publicacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$publicacion) {
    die("Publicación no encontrada");
}

// Obtener información del usuario que hizo la publicación
$stmt_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :userid");
$stmt_usuario->bindParam(':userid', $publicacion['usuario'], PDO::PARAM_INT);
$stmt_usuario->execute();
$use = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

// Contar número de comentarios
$stmt_numcomen = $pdo->prepare("SELECT COUNT(*) FROM comentarios WHERE publicacion = :publicacion_id");
$stmt_numcomen->bindParam(':publicacion_id', $publicacion['id_pub'], PDO::PARAM_INT);
$stmt_numcomen->execute();
$numcomen = $stmt_numcomen->fetchColumn();

// Verificar si el usuario ya dio like
$ya_dio_like = false;
$stmt_like = $pdo->prepare("SELECT * FROM likes WHERE post = :post_id AND usuario = :usuario_id");
$stmt_like->execute([':post_id' => $publicacion['id_pub'], ':usuario_id' => $_SESSION['id']]);
if ($stmt_like->rowCount() > 0) {
    $ya_dio_like = true;
}
?>

<!DOCTYPE html>
<html class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Publicación - REDSOCIAL</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        .comment-box {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    
    <?php echo Headerb($pdo); ?>
    <?php echo Side($pdo); ?>

    <div class="content-wrapper">
        <section class="content">
            <div class="row">
                <div class="col-md-8">
                    <div class="box box-widget">
                        <div class="box-header with-border">
                            <div class="user-block">
                                <img class="img-circle" src="http://localhost/redsocial-master/redsocial-master/avatars/<?php echo $use['avatar']; ?>" alt="User Image">
                                <span class="username"><a href="#"><?php echo $use['usuario'];?></a></span>
                                <span class="description"><?php echo $publicacion['fecha'];?></span>
                            </div>
                        </div>
                        <div class="box-body">
                            <p><?php echo $publicacion['contenido'];?></p>
                            <?php
                                if (!empty($publicacion['imagen'])) {
                                    echo "<img src='http://localhost/redsocial-master/redsocial-master/publicaciones/" . htmlspecialchars($publicacion['imagen']) . "' class='img-responsive pad' alt='Imagen de la publicación'>";
                                }
                            ?>
                            <button type="button" class="btn btn-default btn-xs"><i class="fa fa-share"></i> Compartir</button>
                            <button type="button" class="btn btn-default btn-xs like" id="<?php echo $publicacion['id_pub']; ?>">
                                <i class="fa <?php echo $ya_dio_like ? 'fa-thumbs-up' : 'fa-thumbs-o-up'; ?>"></i> 
                                <?php echo $ya_dio_like ? 'No me gusta' : 'Me gusta'; ?>
                            </button>
                            <span class="pull-right text-muted" id="likes_<?php echo $publicacion['id_pub']; ?>">
                                <?php echo $publicacion['likes']; ?> Me gusta - <?php echo $numcomen; ?> comentarios
                            </span>
                        </div>
                        <div class="box-footer box-comments">
                            <?php
                            // Obtener todos los comentarios para esta publicación
                            $stmt_comentarios = $pdo->prepare("SELECT * FROM comentarios WHERE publicacion = :publicacion_id ORDER BY fecha DESC");
                            $stmt_comentarios->bindParam(':publicacion_id', $publicacion['id_pub'], PDO::PARAM_INT);
                            $stmt_comentarios->execute();

                            echo "<div class='comment-box'>";
                            while ($com = $stmt_comentarios->fetch(PDO::FETCH_ASSOC)) {
                                // Obtener información del usuario que hizo el comentario
                                $stmt_usuarioc = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :usuario_id");
                                $stmt_usuarioc->bindParam(':usuario_id', $com['usuario'], PDO::PARAM_INT);
                                $stmt_usuarioc->execute();
                                $usec = $stmt_usuarioc->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <div class="box-comment">
                                    <img class="img-circle img-sm" src="http://localhost/redsocial-master/redsocial-master/avatars/<?php echo $usec['avatar'];?>" alt="User Image">
                                    <div class="comment-text">
                                        <span class="username">
                                            <?php echo $usec['usuario'];?>
                                            <span class="text-muted pull-right"><?php echo $com['fecha'];?></span>
                                        </span>
                                        <?php echo $com['comentario'];?>
                                    </div>
                                </div>
                            <?php 
                            }
                            echo "</div>";
                            ?>
                        </div>
                        <div class="box-footer">
                            <form action="#" method="post">
                                <img class="img-responsive img-circle img-sm" src="http://localhost/redsocial-master/redsocial-master/avatars/<?php echo $_SESSION['avatar']; ?>" alt="Alt Text">
                                <div class="img-push">
                                    <input type="text" class="form-control input-sm" placeholder="Presiona enter para publicar comentario">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php echo ControlSidebar(); ?>
</div>

<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="plugins/fastclick/fastclick.js"></script>
<script src="dist/js/app.min.js"></script>
<script src="dist/js/demo.js"></script>

<script>
$(document).ready(function() {
    $('.like').click(function(e) {
        e.preventDefault();
        var id = $(this).attr('id');
        var $likeButton = $(this);
        var $likeCount = $('#likes_' + id);
        
        $.ajax({
            url: 'megusta.php',
            type: 'POST',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    console.log('Response:', response); // Para depuración
                    $likeCount.text(' (' + response.likes + ')');
                    if(response.action === 'liked') {
                        $likeButton.find('i').removeClass('fa-thumbs-o-up').addClass('fa-thumbs-up');
                        $likeButton.html('<i class="fa fa-thumbs-up"></i> No me gusta');
                    } else {
                        $likeButton.find('i').removeClass('fa-thumbs-up').addClass('fa-thumbs-o-up');
                        $likeButton.html('<i class="fa fa-thumbs-o-up"></i> Me gusta');
                    }
                } else {
                    console.error('Error:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
            }
        });
    });

    $('input[type="text"]').keypress(function(e) {
        if(e.which == 13) {
            e.preventDefault();
            var comentario = $(this).val();
            var publicacion = <?php echo $publicacion['id_pub']; ?>;
            var usuario = <?php echo $_SESSION['id']; ?>;
            
            $.ajax({
                url: 'agregarcomentario.php',
                type: 'POST',
                data: {
                    comentario: comentario,
                    usuario: usuario,
                    publicacion: publicacion
                },
                success: function(response) {
                    // Agregar el nuevo comentario al DOM
                    var nuevoComentario = '<div class="box-comment">' +
                        '<img class="img-circle img-sm" src="http://localhost/redsocial-master/redsocial-master/avatars/<?php echo $_SESSION['avatar']; ?>" alt="User Image">' +
                        '<div class="comment-text">' +
                        '<span class="username">' +
                        '<?php echo $_SESSION['usuario']; ?>' +
                        '<span class="text-muted pull-right">Ahora</span>' +
                        '</span>' +
                        comentario +
                        '</div>' +
                        '</div>';
                    $('.comment-box').prepend(nuevoComentario);
                    $('input[type="text"]').val('');

                    // Actualizar el contador de comentarios
                    var $likeCount = $('#likes_<?php echo $publicacion['id_pub']; ?>');
                    var likeCountText = $likeCount.text();
                    var parts = likeCountText.split('-');
                    var likesPart = parts[0].trim();
                    var commentCount = parseInt(parts[1].trim().split(' ')[0]) + 1;
                    $likeCount.text(likesPart + ' - ' + commentCount + ' comentarios');
                }
            });
        }
    });
});
</script>
</body>
</html>