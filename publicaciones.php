<?php
include 'lib/config.php'; // Incluir archivo de configuración   
?>

<!-- Estilos CSS -->
<style>
    .form-control.input-sm {
        width: 100%;
        margin-bottom: 5px;
        height: 60px;
        resize: vertical;
    }

    .input-group-btn {
        vertical-align: top;
    }

    .input-group-btn .btn-group {
        display: flex;
    }

    .input-group-btn .btn-group label,
    .input-group-btn .btn-group button {
        height: 34px;
        margin-left: 5px;
    }

    .user-block {
        display: flex;
        align-items: center;
    }

    .user-block img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .user-block .description {
        cursor: pointer;
        color: #3C8DBC;
    }

    .box-comments {
        margin-top: 10px;
    }

    .box-comment {
        margin-bottom: 10px;
    }

    .box-comment img {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        margin-right: 10px;
    }
    .comment-text {
        display: flex;
        align-items: center;
    }
    .comment-text .username {
        font-weight: bold;
        margin-right: 5px;
    }
    .comment-text .text-muted {
        font-size: 12px;
    }
    .comment-box {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ddd;
        padding: 10px;
        margin-top: 10px;
    }
</style>

<?php
$CantidadMostrar = 5; // Cantidad de registros por página

// Conexión a la base de datos utilizando PDO
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Validar y obtener la página actual
    $compag = isset($_GET['pag']) ? (int)$_GET['pag'] : 1;

    // Contar total de registros
    $stmt_total = $pdo->query("SELECT COUNT(*) FROM publicaciones");
    $totalr = $stmt_total->fetchColumn();

    // Calcular total de páginas
    $TotalRegistro = ceil($totalr / $CantidadMostrar);

    // Calcular el número de página siguiente
    $IncrimentNum = (($compag + 1) <= $TotalRegistro) ? ($compag + 1) : 0;

    // Consulta SQL con PDO para obtener las publicaciones paginadas
    $stmt = $pdo->prepare("SELECT * FROM publicaciones ORDER BY id_pub DESC LIMIT :inicio, :cantidad");
    $inicio = ($compag - 1) * $CantidadMostrar;
    $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindParam(':cantidad', $CantidadMostrar, PDO::PARAM_INT);
    $stmt->execute();

    while ($lista = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Consultar información del usuario
        $stmt_usuario = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :userid");
        $stmt_usuario->bindParam(':userid', $lista['usuario'], PDO::PARAM_INT);
        $stmt_usuario->execute();
        $use = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

        // Contar número de comentarios
        $stmt_numcomen = $pdo->prepare("SELECT COUNT(*) FROM comentarios WHERE publicacion = :publicacion_id");
        $stmt_numcomen->bindParam(':publicacion_id', $lista['id_pub'], PDO::PARAM_INT);
        $stmt_numcomen->execute();
        $numcomen = $stmt_numcomen->fetchColumn();
?>
<!-- START PUBLICACIONES -->
<div class="box box-widget">
    <div class="box-header with-border">
        <div class="user-block">
            <img class="img-circle" src="http://localhost/redsocial-master/redsocial-master/avatars/<?php echo $use['avatar']; ?>" alt="User Image">
            <span class="description" onclick="location.href='http://localhost/redsocial-master/redsocial-master/perfil.php?id=<?php echo $use['id_use'];?>';"><?php echo $use['usuario'];?></span>
            <span class="description"><?php echo $lista['fecha'];?></span>
        </div>
        <div class="box-tools">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <p><?php echo $lista['contenido'];?></p>
        <?php
            // Mostrar imagen si existe
            if (!empty($lista['imagen'])) {
                echo "<img src='publicaciones/" . htmlspecialchars($lista['imagen']) . "' width='100%' alt='Imagen de la publicación' class='img-responsive'>";
            }
        ?>
        <ul class="list-inline">
            <?php
            $query = $pdo->prepare("SELECT * FROM likes WHERE post = :post_id AND usuario = :usuario_id");
            $query->bindParam(':post_id', $lista['id_pub'], PDO::PARAM_INT);
            $query->bindParam(':usuario_id', $_SESSION['id'], PDO::PARAM_INT);
            $query->execute();
            $ya_dio_like = ($query->rowCount() > 0);
            ?>
            <li>
                <div class="btn btn-default btn-xs like" id="<?php echo $lista['id_pub']; ?>">
                    <i class="fa <?php echo $ya_dio_like ? 'fa-thumbs-up' : 'fa-thumbs-o-up'; ?>"></i>
                    <?php echo $ya_dio_like ? 'No me gusta' : 'Me gusta'; ?>
                </div>
                <span id="likes_<?php echo $lista['id_pub']; ?>"> (<?php echo $lista['likes']; ?>)</span>
            </li>
            <li class="pull-right">
                <span class="link-black text-sm toggle-comments" data-pub-id="<?php echo $lista['id_pub']; ?>"><i class="fa fa-comments-o margin-r-5"></i> Comentarios (<?php echo $numcomen; ?>)</span>
            </li>
        </ul>
    </div>
    <div class="box-footer box-comments" id="comments-<?php echo $lista['id_pub']; ?>" style="display: none;">
        <?php
        // Obtener todos los comentarios
        $stmt_comentarios = $pdo->prepare("SELECT * FROM comentarios WHERE publicacion = :publicacion_id ORDER BY fecha DESC");
        $stmt_comentarios->bindParam(':publicacion_id', $lista['id_pub'], PDO::PARAM_INT);
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
                <img class="img-circle img-sm" src="http://localhost/redsocial-master/redsocial-master/avatars/<?php echo $usec['avatar'];?>">
                <div class="comment-text">
                    <span class="username">
                        <?php echo $usec['usuario'];?>
                        <span class="text-muted"><?php echo $com['fecha'];?></span>
                    </span>
                    <?php echo $com['comentario'];?>
                </div>
            </div>
        <?php 
        }
        echo "</div>";
        ?>
        <form method="post" action="" class="form-comentario">
            <div class="form-group">
                <input type="text" class="form-control input-sm" placeholder="Escribe un comentario" name="comentario" id="comentario-<?php echo $lista['id_pub'];?>">
                <input type="hidden" name="usuario" value="<?php echo $_SESSION['id'];?>">
                <input type="hidden" name="publicacion" value="<?php echo $lista['id_pub'];?>">
                <input type="hidden" name="avatar" value="<?php echo isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'default-avatar.jpg'; ?>">
                <input type="hidden" name="nombre" value="<?php echo isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : ''; ?>">
            </div>
        </form>
    </div>
</div>
<!-- END PUBLICACIONES -->
<?php
    } // Fin del bucle while

    // Enlace para la siguiente página si es necesario
    if ($IncrimentNum > 0) {
        echo "<a href='#' class='btn btn-primary load-more' data-page='".$IncrimentNum."'>Siguiente</a>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<script>
$(document).ready(function() {
    $('.toggle-comments').click(function() {
        var pubId = $(this).data('pub-id');
        $('#comments-' + pubId).toggle();
    });

    $('.form-comentario .form-control').keypress(function(e) {
        if(e.which == 13) {
            e.preventDefault();
            var form = $(this).closest('form');
            var comentario = form.find('input[name="comentario"]').val();
            var usuario = form.find('input[name="usuario"]').val();
            var publicacion = form.find('input[name="publicacion"]').val();
            var avatar = form.find('input[name="avatar"]').val();
            var nombre = form.find('input[name="nombre"]').val();

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
                        '<img class="img-circle img-sm" src="avatars/' + avatar + '">' +
                        '<div class="comment-text">' +
                        '<span class="username">' + nombre +
                        '<span class="text-muted pull-right">' + ' ' + 'Ahora' + '</span>' +
                        '</span>' + comentario +
                        '</div>' +
                        '</div>';
                    $('#comments-' + publicacion + ' .comment-box').prepend(nuevoComentario);
                    form.find('input[name="comentario"]').val('');
                }
            });
        }
    });

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
});
</script>