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

// Función para validar caracteres
function validarCaracteres($str) {
    return preg_match('/^[A-Za-zñ!#$%&()=?¿¡*+0-9-_á-úÁ-Ú :;,.]+$/', $str);
}

// Procesamiento del formulario de publicación
if (isset($_POST['publicar'])) {
    $publicacion = $_POST['publicacion'];

    
    // Obtener el siguiente ID de la tabla 'publicaciones'
    $stmt = $pdo->query("SHOW TABLE STATUS WHERE `Name` = 'publicaciones'");
    $next_increment = $stmt->fetch(PDO::FETCH_ASSOC)['Auto_increment'];

    $alea = substr(strtoupper(md5(microtime(true))), 0, 12);
    $code = $next_increment . $alea;

    // Procesar imagen subida
    $nombre_imagen = "publicaciones/" . $code . '.' . $type;
    if (!empty($_FILES['foto']['tmp_name'])) {
        $rfoto = $_FILES['foto']['tmp_name'];
        $type = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_imagen = $code . '.' . $type;
        $destino = $_SERVER['DOCUMENT_ROOT'] . '/' . $nombre_imagen;
        move_uploaded_file($rfoto, $destino);
        $nombre_imagen = $destino; // Guardamos la ruta completa
    }

    // Insertar publicación
    $stmt = $pdo->prepare("INSERT INTO publicaciones (usuario, fecha, contenido, imagen) VALUES (:usuario, NOW(), :contenido, :imagen)");
    $stmt->bindParam(':usuario', $_SESSION['id'], PDO::PARAM_INT);
    $stmt->bindParam(':contenido', $publicacion, PDO::PARAM_STR);
    $stmt->bindParam(':imagen', $nombre_imagen, PDO::PARAM_STR);
    $stmt->execute();

    // Redirigir después de la inserción
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>REDSOCIAL</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <script>(function(e,t,n){var r=e.querySelectorAll("html")[0];r.className=r.className.replace(/(^|\s)no-js(\s|$)/,"$1js$2")})(document,window,0);</script>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script src="js/jquery.jscroll.js"></script>
    <style>
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
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-primary direct-chat direct-chat-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title">¿Qué estás pensando?</h3>
                                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                        <i class="fa fa-minus"></i>
                                    </button>
                                </div>
                                <div class="box-footer">
                                    <form action="" method="post" enctype="multipart/form-data">
                                        <div class="input-group">
                                            <textarea name="publicacion" onkeypress="return validarn(event)" placeholder="¿Qué estás pensando?" class="form-control" cols="200" rows="3" required></textarea>
                                            <div class="input-group-btn">
                                                <div class="btn-group">
                                                    <input type="file" name="foto" id="file-1" class="inputfile inputfile-1" data-multiple-caption="{count} files selected"/>
                                                    <label for="file-1"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="17" viewBox="0 0 20 17"><path d="M10 0l-5.2 4.9h3.3v5.1h3.8v-5.1h3.3l-5.2-4.9zm9.3 11.5l-3.2-2.1h-2l3.4 2.6h-3.5c-.1 0-.2.1-.2.1l-.8 2.3h-6l-.8-2.2c-.1-.1-.1-.2-.2-.2h-3.6l3.4-2.6h-2l-3.2 2.1c-.4.3-.7 1-.6 1.5l.6 3.1c.1.5.7.9 1.2.9h16.3c.6 0 1.1-.4 1.3-.9l.6-3.1c.1-.5-.2-1.2-.7-1.5z"/></svg> <span>Sube una foto</span></label>
                                                    <button type="submit" name="publicar" class="btn btn-primary btn-flat">Publicar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="scroll">
                        <?php require_once 'publicaciones.php'; ?>
                    </div>
                    <script>
                        $(document).ready(function() {
                            $('.scroll').jscroll({
                                loadingHtml: '<img src="images/invisible.png" alt="Loading" />'
                            });
                        });
                    </script>
                </div>
                <div class="col-md-4">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Solicitudes de amistad</h3>
                        </div>
                        <div class="box-body">
                            <ul class="products-list product-list-in-box">
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM amigos WHERE para = :id AND estado = '0' ORDER BY id_ami DESC LIMIT 4");
                                $stmt->bindParam(':id', $_SESSION['id'], PDO::PARAM_INT);
                                $stmt->execute();
                                while ($am = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                    $stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :id");
                                    $stmt_user->bindParam(':id', $am['de'], PDO::PARAM_INT);
                                    $stmt_user->execute();
                                    $us = $stmt_user->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <li class="item">
                                    <div class="product-img">
                                        <img src="avatars/<?php echo $us['avatar']; ?>" alt="Product Image">
                                    </div>
                                    <div class="product-info">
                                        <?php echo $us['usuario']; ?>
                                        <a href="solicitud.php?action=aceptar&id=<?php echo $am['id_ami']; ?>"><span class="label label-success pull-right">Aceptar</span></a>
                                        <br>
                                        <a href="solicitud.php?action=rechazar&id=<?php echo $am['id_ami']; ?>"><span class="label label-danger pull-right">Rechazar</span></a>
                                        <span class="product-description">
                                            <?php echo $us['sexo']; ?>
                                        </span>
                                    </div>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <div class="box-footer text-center">
                            <a href="solicitudes.php" class="uppercase">Ver todas las solicitudes</a>
                        </div>
                    </div>
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Tendencias</h3>
                        </div>
                        <div class="box-body">
                            <?php
                            $tendencias = array("CSS3", "HTML5", "JAVA", "DESARROLLO WEB", "MYSQL");
                            foreach ($tendencias as $tendencia) {
                            ?>
                            <div class="post">
                                <strong><?php echo $tendencia; ?></strong>
                                <div class="user-block">
                                    <span class="username">
                                        <a href="#"><?php echo $tendencia; ?></a>
                                    </span>
                                    <span class="description">Publicado: <?php echo date('d-m-Y'); ?></span>
                                </div>
                            </div>
                            <?php } ?>
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
<script src="dist/js/app.min.js"></script>
<script src="js/custom-file-input.js"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '.load-more', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        $.ajax({
            url: 'publicaciones.php',
            type: 'GET',
            data: {pag: page},
            success: function(response) {
                $('.scroll').append(response);
                $('.load-more').remove();
            }
        });
    });
});
</script>
</body>
</html>