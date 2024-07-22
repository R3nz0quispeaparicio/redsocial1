<?php
session_start();
require_once 'lib/config.php';
require_once 'lib/socialnetwork-lib.php';

// Redirigir si no hay sesión de usuario
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Obtener la lista de chats del usuario actual
$stmt = $pdo->prepare("SELECT id_cch, de, para, usuario, avatar 
                       FROM c_chats 
                       JOIN usuarios ON (c_chats.para = usuarios.id_use OR c_chats.de = usuarios.id_use)
                       WHERE (c_chats.de = ? OR c_chats.para = ?) AND usuarios.id_use != ?
                       ORDER BY id_cch DESC");

$stmt->execute([$_SESSION['id'], $_SESSION['id'], $_SESSION['id']]);

$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Mensajes - REDSOCIAL</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

    <?php echo Headerb($pdo); ?>
    <?php echo Side($pdo); ?>

    <div class="content-wrapper">
        <section class="content-header">
            <h1>
                Mensajes
                <small>Tus conversaciones</small>
            </h1>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-md-3">
                    <a href="nuevochat.php" class="btn btn-primary btn-block margin-bottom">Nuevo Mensaje</a>

                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <h3 class="box-title">Carpetas</h3>
                        </div>
                        <div class="box-body no-padding">
                            <ul class="nav nav-pills nav-stacked">
                                <li class="active"><a href="#"><i class="fa fa-inbox"></i> Bandeja de entrada</a></li>
                                <li><a href="#"><i class="fa fa-envelope-o"></i> Enviados</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Chats</h3>
                        </div>
                        <div class="box-body no-padding">
                            <div class="table-responsive mailbox-messages">
                                <table class="table table-hover table-striped">
                                    <tbody>
                                    <?php foreach ($chats as $chat): ?>
                                        <?php
                                        $other_user_id = ($chat['de'] == $_SESSION['id']) ? $chat['para'] : $chat['de'];
                                        ?>
                                        <tr>
                                            <td class="mailbox-name">
                                                <a href="chat.php?usuario=<?php echo $other_user_id; ?>">
                                                    <?php echo htmlspecialchars($chat['usuario']); ?>
                                                </a>
                                            </td>
                                            <td class="mailbox-subject">
                                                <b>Último mensaje</b> - Contenido del último mensaje...
                                            </td>
                                            <td class="mailbox-attachment"></td>
                                            <td class="mailbox-date">Hace un tiempo...</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="pull-right hidden-xs">
            <b>Version</b> 2.3.8
        </div>
    </footer>
    <?php echo ControlSidebar(); ?>
</div>

<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="plugins/fastclick/fastclick.js"></script>
<script src="dist/js/app.min.js"></script>
</body>
</html>