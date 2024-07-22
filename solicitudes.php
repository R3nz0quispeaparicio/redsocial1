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

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $accion = $_GET['action'];
    
    if ($accion == 'aceptar') {
        $stmt = $pdo->prepare("UPDATE amigos SET estado = 1 WHERE id_ami = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } elseif ($accion == 'rechazar') {
        $stmt = $pdo->prepare("DELETE FROM amigos WHERE id_ami = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
    
    header("Location: solicitudes.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Solicitudes de Amistad</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
    <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
    <link rel="stylesheet" type="text/css" href="css/component.css" />
    <style>
        .friend-request {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .friend-request img {
            border-radius: 50%;
            margin-right: 15px;
        }
        .friend-request .info {
            flex-grow: 1;
        }
        .friend-request .actions a {
            margin-left: 10px;
        }
    </style>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">
    <?php echo Headerb($pdo); ?>
    <?php echo Side($pdo); ?>
    <div class="content-wrapper">
        <section class="content">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Solicitudes de Amistad</h3>
                </div>
                <div class="box-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM amigos WHERE para = :id AND estado = '0' ORDER BY id_ami DESC");
                    $stmt->bindParam(':id', $_SESSION['id'], PDO::PARAM_INT);
                    $stmt->execute();
                    while ($am = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id_use = :id");
                        $stmt_user->bindParam(':id', $am['de'], PDO::PARAM_INT);
                        $stmt_user->execute();
                        $us = $stmt_user->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="friend-request">
                        <img src="avatars/<?php echo $us['avatar']; ?>" alt="User Image" width="50" height="50">
                        <div class="info">
                            <strong><?php echo $us['usuario']; ?></strong><br>
                            <small><?php echo $us['sexo']; ?></small>
                        </div>
                        <div class="actions">
                            <a href="solicitudes.php?action=aceptar&id=<?php echo $am['id_ami']; ?>" class="btn btn-success btn-xs">Aceptar</a>
                            <a href="solicitudes.php?action=rechazar&id=<?php echo $am['id_ami']; ?>" class="btn btn-danger btn-xs">Rechazar</a>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </section>
    </div>
</div>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
