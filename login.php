<?php

ini_set('display_errors', 1);

session_start();
require_once 'lib/config.php';

if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

function limpiarEntrada($data) {
    return trim(htmlspecialchars(strip_tags($data)));
}

if (isset($_POST['login'])) {
    $usuario = limpiarEntrada($_POST['usuario']);
    $contrasena = limpiarEntrada($_POST['contrasena']);

    $query = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $query->execute(['usuario' => $usuario]);

    $usuarioData = $query->fetch(PDO::FETCH_ASSOC);

    // Verificar la contraseña comparándola directamente con la almacenada
    if ($usuarioData && $contrasena == $usuarioData['contrasena']) {
        var_dump($usuarioData); 

        $_SESSION['usuario'] = $usuario;
        $_SESSION['id'] = $usuarioData['id_use'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Los datos ingresados no son correctos';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Bienvenido a REDSOCIAL</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="plugins/iCheck/square/blue.css">
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href=""><b>RED</b>SOCIAL</a>
  </div>
  <div class="login-box-body">
    <p class="login-box-msg">Bienvenido a REDSOCIAL</p>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <form action="" method="post">
      <div class="form-group has-feedback">
        <input type="text" class="form-control" placeholder="Usuario" name="usuario" pattern="[A-Za-z_-0-9]{1,20}" required>
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="password" class="form-control" placeholder="Contraseña" name="contrasena" required>
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      <div class="row">
        <div class="col-xs-12">
          <button type="submit" name="login" class="btn btn-primary btn-block btn-flat">Iniciar Sesión</button>
        </div>
      </div>
    </form>
    <br>
    <a href="#">Olvidé mi contraseña</a><br>
    <a href="registro.php" class="text-center">Registrarme en REDSOCIAL</a>
  </div>
</div>
<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="plugins/iCheck/icheck.min.js"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' // optional
    });
  });
</script>
</body>
</html>
